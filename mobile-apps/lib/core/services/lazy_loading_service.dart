import 'dart:async';
import 'package:flutter/foundation.dart';
import '../api/api_client.dart';

/// Estado de carga de un recurso
enum LoadingState {
  idle,
  loading,
  loaded,
  error,
}

/// Recurso cargado con lazy loading
class LazyResource<T> {
  final String key;
  LoadingState state;
  T? data;
  String? error;
  DateTime? loadedAt;
  DateTime? expiresAt;

  LazyResource({
    required this.key,
    this.state = LoadingState.idle,
    this.data,
    this.error,
    this.loadedAt,
    this.expiresAt,
  });

  bool get isLoaded => state == LoadingState.loaded && data != null;
  bool get isLoading => state == LoadingState.loading;
  bool get hasError => state == LoadingState.error;
  bool get isExpired => expiresAt != null && DateTime.now().isAfter(expiresAt!);
  bool get needsRefresh => !isLoaded || isExpired;
}

/// Configuración de carga
class LoadConfig {
  final Duration? cacheDuration;
  final bool forceRefresh;
  final int maxRetries;
  final Duration retryDelay;

  const LoadConfig({
    this.cacheDuration = const Duration(minutes: 5),
    this.forceRefresh = false,
    this.maxRetries = 3,
    this.retryDelay = const Duration(seconds: 1),
  });

  static const LoadConfig defaultConfig = LoadConfig();
}

/// Servicio de Lazy Loading para recursos de la API
class LazyLoadingService {
  final ApiClient _apiClient;

  /// Cache de recursos cargados
  final Map<String, LazyResource<dynamic>> _cache = {};

  /// Listeners de cambios
  final Map<String, List<void Function(LazyResource<dynamic>)>> _listeners = {};

  /// Peticiones en progreso (para evitar duplicados)
  final Map<String, Completer<dynamic>> _pendingRequests = {};

  LazyLoadingService({required ApiClient apiClient}) : _apiClient = apiClient;

  // =========================================================================
  // CARGA DE RECURSOS GENÉRICA
  // =========================================================================

  /// Obtiene un recurso con lazy loading
  Future<T?> load<T>({
    required String key,
    required String endpoint,
    required T Function(Map<String, dynamic>) parser,
    LoadConfig config = LoadConfig.defaultConfig,
  }) async {
    // Verificar cache
    final cached = _cache[key];
    if (cached != null && cached.isLoaded && !cached.needsRefresh && !config.forceRefresh) {
      debugPrint('[LazyLoading] Cache hit: $key');
      return cached.data as T?;
    }

    // Si ya hay una petición en progreso, esperar
    if (_pendingRequests.containsKey(key)) {
      debugPrint('[LazyLoading] Waiting for pending request: $key');
      return await _pendingRequests[key]!.future as T?;
    }

    // Crear nueva petición
    final completer = Completer<T?>();
    _pendingRequests[key] = completer as Completer<dynamic>;

    // Actualizar estado
    _updateResource(key, LoadingState.loading);

    try {
      final response = await _fetchWithRetry(endpoint, config);

      if (response.success && response.data != null) {
        final parsedData = parser(response.data!);
        
        final resource = LazyResource<T>(
          key: key,
          state: LoadingState.loaded,
          data: parsedData,
          loadedAt: DateTime.now(),
          expiresAt: config.cacheDuration != null 
              ? DateTime.now().add(config.cacheDuration!)
              : null,
        );

        _cache[key] = resource;
        _notifyListeners(key, resource);
        
        completer.complete(parsedData);
        debugPrint('[LazyLoading] Loaded: $key');
        
        return parsedData;
      } else {
        throw Exception(response.errorMessage ?? 'Error loading resource');
      }
    } catch (e) {
      _updateResource(key, LoadingState.error, error: e.toString());
      completer.completeError(e);
      debugPrint('[LazyLoading] Error loading $key: $e');
      return null;
    } finally {
      _pendingRequests.remove(key);
    }
  }

  /// Fetch con reintentos
  Future<ApiResponse> _fetchWithRetry(String endpoint, LoadConfig config) async {
    var attempts = 0;
    Exception? lastError;

    while (attempts < config.maxRetries) {
      try {
        final response = await _apiClient.getData(endpoint);
        if (response.success) {
          return response;
        }
        lastError = Exception(response.errorMessage);
      } catch (e) {
        lastError = e as Exception;
      }

      attempts++;
      if (attempts < config.maxRetries) {
        await Future.delayed(config.retryDelay * attempts);
      }
    }

    throw lastError ?? Exception('Max retries reached');
  }

  // =========================================================================
  // CARGA DE MÓDULOS
  // =========================================================================

  /// Carga información de un módulo específico
  Future<ModuleData?> loadModule(String moduleId, {LoadConfig? config}) async {
    return await load<ModuleData>(
      key: 'module_$moduleId',
      endpoint: '/modules/$moduleId',
      parser: (data) => ModuleData.fromJson(data),
      config: config ?? LoadConfig.defaultConfig,
    );
  }

  /// Carga lista de módulos disponibles (solo IDs y nombres)
  Future<List<ModuleSummary>?> loadModuleList({LoadConfig? config}) async {
    return await load<List<ModuleSummary>>(
      key: 'module_list',
      endpoint: '/modules?fields=id,name,icon,enabled',
      parser: (data) {
        final modules = data['modules'] as List<dynamic>? ?? [];
        return modules
            .map((m) => ModuleSummary.fromJson(m as Map<String, dynamic>))
            .toList();
      },
      config: config ?? LoadConfig.defaultConfig,
    );
  }

  /// Carga contenido de un módulo (items, posts, etc.)
  Future<ModuleContent?> loadModuleContent(
    String moduleId, {
    int page = 1,
    int perPage = 20,
    LoadConfig? config,
  }) async {
    return await load<ModuleContent>(
      key: 'module_content_${moduleId}_${page}_$perPage',
      endpoint: '/modules/$moduleId/content?page=$page&per_page=$perPage',
      parser: (data) => ModuleContent.fromJson(data),
      config: config ?? const LoadConfig(cacheDuration: Duration(minutes: 2)),
    );
  }

  // =========================================================================
  // CARGA DE CONFIGURACIÓN
  // =========================================================================

  /// Carga configuración del sitio
  Future<SiteConfig?> loadSiteConfig({LoadConfig? config}) async {
    return await load<SiteConfig>(
      key: 'site_config',
      endpoint: '/manifest?fields=site,branding,theme',
      parser: (data) => SiteConfig.fromJson(data),
      config: config ?? const LoadConfig(cacheDuration: Duration(hours: 1)),
    );
  }

  /// Carga configuración de navegación
  Future<NavigationConfig?> loadNavigation({LoadConfig? config}) async {
    return await load<NavigationConfig>(
      key: 'navigation',
      endpoint: '/manifest?fields=navigation',
      parser: (data) => NavigationConfig.fromJson(data),
      config: config ?? const LoadConfig(cacheDuration: Duration(hours: 1)),
    );
  }

  // =========================================================================
  // GESTIÓN DE CACHÉ
  // =========================================================================

  /// Obtiene recurso del cache sin cargar
  T? getCached<T>(String key) {
    final resource = _cache[key];
    if (resource != null && resource.isLoaded && !resource.isExpired) {
      return resource.data as T?;
    }
    return null;
  }

  /// Verifica si un recurso está en cache
  bool isCached(String key) {
    final resource = _cache[key];
    return resource != null && resource.isLoaded && !resource.isExpired;
  }

  /// Invalida un recurso del cache
  void invalidate(String key) {
    _cache.remove(key);
    debugPrint('[LazyLoading] Invalidated: $key');
  }

  /// Invalida recursos que coinciden con un patrón
  void invalidatePattern(String pattern) {
    final regex = RegExp(pattern);
    final keysToRemove = _cache.keys.where((k) => regex.hasMatch(k)).toList();
    
    for (final key in keysToRemove) {
      _cache.remove(key);
    }
    
    debugPrint('[LazyLoading] Invalidated ${keysToRemove.length} keys matching: $pattern');
  }

  /// Limpia todo el cache
  void clearCache() {
    _cache.clear();
    debugPrint('[LazyLoading] Cache cleared');
  }

  /// Limpia recursos expirados
  int cleanExpired() {
    final expiredKeys = _cache.entries
        .where((e) => e.value.isExpired)
        .map((e) => e.key)
        .toList();

    for (final key in expiredKeys) {
      _cache.remove(key);
    }

    debugPrint('[LazyLoading] Cleaned ${expiredKeys.length} expired entries');
    return expiredKeys.length;
  }

  // =========================================================================
  // LISTENERS
  // =========================================================================

  /// Añade listener para cambios en un recurso
  void addListener(String key, void Function(LazyResource<dynamic>) listener) {
    _listeners[key] ??= [];
    _listeners[key]!.add(listener);
  }

  /// Elimina listener
  void removeListener(String key, void Function(LazyResource<dynamic>) listener) {
    _listeners[key]?.remove(listener);
  }

  /// Notifica a listeners
  void _notifyListeners(String key, LazyResource<dynamic> resource) {
    final listeners = _listeners[key];
    if (listeners != null) {
      for (final listener in listeners) {
        listener(resource);
      }
    }
  }

  /// Actualiza estado de recurso
  void _updateResource(String key, LoadingState state, {String? error}) {
    var resource = _cache[key];
    
    if (resource == null) {
      resource = LazyResource<dynamic>(key: key);
      _cache[key] = resource;
    }

    resource.state = state;
    if (error != null) resource.error = error;

    _notifyListeners(key, resource);
  }

  // =========================================================================
  // ESTADÍSTICAS
  // =========================================================================

  /// Obtiene estadísticas del cache
  Map<String, dynamic> getStats() {
    final now = DateTime.now();
    var loadedCount = 0;
    var expiredCount = 0;
    var errorCount = 0;

    for (final resource in _cache.values) {
      if (resource.isLoaded) loadedCount++;
      if (resource.isExpired) expiredCount++;
      if (resource.hasError) errorCount++;
    }

    return {
      'total_entries': _cache.length,
      'loaded': loadedCount,
      'expired': expiredCount,
      'errors': errorCount,
      'pending_requests': _pendingRequests.length,
      'listeners': _listeners.length,
    };
  }

  /// Obtiene estado de un recurso
  LoadingState? getState(String key) {
    return _cache[key]?.state;
  }
}

// =========================================================================
// MODELOS DE DATOS
// =========================================================================

/// Resumen de módulo (para lista)
class ModuleSummary {
  final String id;
  final String name;
  final String? icon;
  final bool enabled;

  ModuleSummary({
    required this.id,
    required this.name,
    this.icon,
    required this.enabled,
  });

  factory ModuleSummary.fromJson(Map<String, dynamic> json) {
    return ModuleSummary(
      id: json['id'] as String,
      name: json['name'] as String,
      icon: json['icon'] as String?,
      enabled: json['enabled'] as bool? ?? true,
    );
  }
}

/// Datos completos de módulo
class ModuleData {
  final String id;
  final String name;
  final String? description;
  final String? icon;
  final bool enabled;
  final Map<String, dynamic> config;
  final List<String> capabilities;
  final Map<String, dynamic> endpoints;

  ModuleData({
    required this.id,
    required this.name,
    this.description,
    this.icon,
    required this.enabled,
    this.config = const {},
    this.capabilities = const [],
    this.endpoints = const {},
  });

  factory ModuleData.fromJson(Map<String, dynamic> json) {
    return ModuleData(
      id: json['id'] as String,
      name: json['name'] as String,
      description: json['description'] as String?,
      icon: json['icon'] as String?,
      enabled: json['enabled'] as bool? ?? true,
      config: json['config'] as Map<String, dynamic>? ?? {},
      capabilities: (json['capabilities'] as List<dynamic>?)
              ?.map((c) => c.toString())
              .toList() ??
          [],
      endpoints: json['endpoints'] as Map<String, dynamic>? ?? {},
    );
  }
}

/// Contenido de módulo
class ModuleContent {
  final List<Map<String, dynamic>> items;
  final int total;
  final int page;
  final int perPage;
  final bool hasMore;

  ModuleContent({
    required this.items,
    required this.total,
    required this.page,
    required this.perPage,
    required this.hasMore,
  });

  factory ModuleContent.fromJson(Map<String, dynamic> json) {
    return ModuleContent(
      items: (json['items'] as List<dynamic>?)
              ?.map((i) => i as Map<String, dynamic>)
              .toList() ??
          [],
      total: json['total'] as int? ?? 0,
      page: json['page'] as int? ?? 1,
      perPage: json['per_page'] as int? ?? 20,
      hasMore: json['has_more'] as bool? ?? false,
    );
  }
}

/// Configuración del sitio
class SiteConfig {
  final String url;
  final String name;
  final String? description;
  final Map<String, dynamic> branding;
  final Map<String, dynamic> theme;

  SiteConfig({
    required this.url,
    required this.name,
    this.description,
    this.branding = const {},
    this.theme = const {},
  });

  factory SiteConfig.fromJson(Map<String, dynamic> json) {
    final site = json['site'] as Map<String, dynamic>? ?? {};
    return SiteConfig(
      url: site['url'] as String? ?? '',
      name: site['name'] as String? ?? '',
      description: site['description'] as String?,
      branding: json['branding'] as Map<String, dynamic>? ?? {},
      theme: json['theme'] as Map<String, dynamic>? ?? {},
    );
  }
}

/// Configuración de navegación
class NavigationConfig {
  final String style;
  final List<NavItem> tabs;

  NavigationConfig({
    required this.style,
    required this.tabs,
  });

  factory NavigationConfig.fromJson(Map<String, dynamic> json) {
    final nav = json['navigation'] as Map<String, dynamic>? ?? {};
    return NavigationConfig(
      style: nav['style'] as String? ?? 'bottom_tabs',
      tabs: (nav['tabs'] as List<dynamic>?)
              ?.map((t) => NavItem.fromJson(t as Map<String, dynamic>))
              .toList() ??
          [],
    );
  }
}

/// Item de navegación
class NavItem {
  final String id;
  final String label;
  final String? icon;
  final String? route;

  NavItem({
    required this.id,
    required this.label,
    this.icon,
    this.route,
  });

  factory NavItem.fromJson(Map<String, dynamic> json) {
    return NavItem(
      id: json['id'] as String,
      label: json['label'] as String,
      icon: json['icon'] as String?,
      route: json['route'] as String?,
    );
  }
}
