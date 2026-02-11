import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Configuracion del servidor - persistente
class ServerConfig {
  static const String _keyServerUrl = 'server_url';
  static const String _keyApiNamespace = 'api_namespace';
  static const String _keyIsConfigured = 'is_configured';
  static const String _keyBusinesses = 'businesses';
  static const String _keyCurrentBusinessId = 'current_business_id';
  static const String _keyFavoriteNodes = 'favorite_nodes';

  /// =====================================================
  /// CONFIGURACIÓN PARA GOOGLE PLAY (GENÉRICA)
  /// =====================================================
  /// Las apps son genéricas y funcionan con cualquier sitio
  /// que tenga el plugin instalado. El usuario configura
  /// el servidor en el primer uso escaneando un QR o
  /// introduciendo la URL manualmente.
  /// =====================================================

  /// URL por defecto del servidor (vacío = pedir configuración)
  /// Solo cambiar si quieres una app preconfigurada para un sitio específico
  /// CONFIGURADO PARA BASABERE:
  // Leave empty so the app always prompts for setup on fresh installs.
  static const String defaultServerUrl = '';

  /// Namespace de la API por defecto
  static const String defaultApiNamespace = '/wp-json/chat-ia-mobile/v1';

  /// Verifica si la app está configurada
  static Future<bool> isConfigured() async {
    // Si hay URL por defecto preconfigurada, siempre está configurada
    if (defaultServerUrl.isNotEmpty) {
      return true;
    }

    // Si no hay URL por defecto, verificar si ya se configuró manualmente
    final prefs = await SharedPreferences.getInstance();
    final isConfigured = prefs.getBool(_keyIsConfigured) ?? false;
    final serverUrl = prefs.getString(_keyServerUrl) ?? '';
    return isConfigured && serverUrl.isNotEmpty;
  }

  /// Marca la app como configurada
  static Future<bool> setConfigured(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.setBool(_keyIsConfigured, value);
  }

  /// Obtiene la URL del servidor guardada
  static Future<String> getServerUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final savedUrl = prefs.getString(_keyServerUrl);

    // Si hay URL guardada, usarla
    if (savedUrl != null && savedUrl.isNotEmpty) {
      return savedUrl;
    }

    // Si no hay URL guardada, usar la por defecto
    return defaultServerUrl;
  }

  /// Guarda la URL del servidor
  static Future<bool> setServerUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    final cleanUrl = _normalizeServerUrl(url);
    await prefs.setString(_keyServerUrl, cleanUrl);
    // Marcar como configurado
    await prefs.setBool(_keyIsConfigured, true);
    return true;
  }

  /// Obtiene el namespace de la API
  static Future<String> getApiNamespace() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyApiNamespace) ?? defaultApiNamespace;
  }

  /// Guarda el namespace de la API
  static Future<bool> setApiNamespace(String namespace) async {
    final prefs = await SharedPreferences.getInstance();
    final cleanNamespace = _normalizeApiNamespace(namespace);
    return prefs.setString(_keyApiNamespace, cleanNamespace);
  }

  /// Obtiene la URL completa de la API
  static Future<String> getFullApiUrl() async {
    final serverUrl = _normalizeServerUrl(await getServerUrl());
    final namespace = _normalizeApiNamespace(await getApiNamespace());
    return '$serverUrl$namespace';
  }

  /// Obtiene la lista de negocios guardados
  static Future<List<SavedBusiness>> getBusinesses() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_keyBusinesses);
    if (raw == null || raw.isEmpty) {
      return [];
    }

    try {
      final data = jsonDecode(raw) as List<dynamic>;
      return data
          .whereType<Map<String, dynamic>>()
          .map(SavedBusiness.fromJson)
          .toList();
    } catch (e) {
      return [];
    }
  }

  /// Guarda la lista de negocios
  static Future<void> _saveBusinesses(List<SavedBusiness> businesses) async {
    final prefs = await SharedPreferences.getInstance();
    final data = businesses.map((b) => b.toJson()).toList();
    await prefs.setString(_keyBusinesses, jsonEncode(data));
  }

  /// Obtiene el negocio actual
  static Future<SavedBusiness?> getCurrentBusiness() async {
    final prefs = await SharedPreferences.getInstance();
    final currentId = prefs.getString(_keyCurrentBusinessId);
    if (currentId == null || currentId.isEmpty) {
      return null;
    }
    final businesses = await getBusinesses();
    for (final business in businesses) {
      if (business.id == currentId) {
        return business;
      }
    }
    return null;
  }

  /// Establece el negocio actual y lo guarda en historial
  static Future<SavedBusiness> setCurrentBusiness({
    required String serverUrl,
    String? apiNamespace,
    String? name,
    String? type,
  }) async {
    final cleanUrl = _normalizeServerUrl(serverUrl);
    final cleanNamespace = _normalizeApiNamespace(apiNamespace ?? defaultApiNamespace);
    final business = SavedBusiness(
      id: SavedBusiness.makeId(cleanUrl, cleanNamespace, type),
      name: (name ?? '').trim(),
      serverUrl: cleanUrl,
      apiNamespace: cleanNamespace,
      type: type?.trim(),
      lastUsedAt: DateTime.now().toIso8601String(),
    );

    final businesses = await getBusinesses();
    final existingIndex = businesses.indexWhere((b) => b.id == business.id);
    if (existingIndex >= 0) {
      final existing = businesses[existingIndex];
      businesses[existingIndex] = existing.copyWith(
        name: business.name.isNotEmpty ? business.name : existing.name,
        lastUsedAt: business.lastUsedAt,
      );
    } else {
      businesses.add(business);
    }

    await _saveBusinesses(businesses);

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyCurrentBusinessId, business.id);
    await prefs.setString(_keyServerUrl, cleanUrl);
    await prefs.setString(_keyApiNamespace, cleanNamespace);
    await prefs.setBool(_keyIsConfigured, true);

    return business;
  }

  /// Actualiza solo el nombre del negocio actual
  static Future<void> updateCurrentBusinessName(String name) async {
    final cleanName = name.trim();
    if (cleanName.isEmpty) {
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final currentId = prefs.getString(_keyCurrentBusinessId);
    if (currentId == null || currentId.isEmpty) {
      return;
    }

    final businesses = await getBusinesses();
    final index = businesses.indexWhere((b) => b.id == currentId);
    if (index < 0) {
      return;
    }

    businesses[index] = businesses[index].copyWith(name: cleanName);
    await _saveBusinesses(businesses);
  }

  /// Resetea a valores por defecto
  static Future<void> resetToDefaults() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyServerUrl);
    await prefs.remove(_keyApiNamespace);
    await prefs.remove(_keyBusinesses);
    await prefs.remove(_keyCurrentBusinessId);
    await prefs.remove(_keyFavoriteNodes);
  }

  /// Valida si una URL es correcta
  static bool isValidUrl(String url) {
    try {
      final uri = Uri.parse(url);
      return uri.hasScheme && (uri.scheme == 'http' || uri.scheme == 'https');
    } catch (e) {
      return false;
    }
  }

  static String _normalizeServerUrl(String url) {
    String cleanUrl = url.trim().replaceAll(r'\/', '/');
    if (cleanUrl.isEmpty) {
      return cleanUrl;
    }

    final wpJsonIndex = cleanUrl.indexOf('/wp-json');
    if (wpJsonIndex > 0) {
      cleanUrl = cleanUrl.substring(0, wpJsonIndex);
    }

    if (!cleanUrl.startsWith('http://') && !cleanUrl.startsWith('https://')) {
      if (cleanUrl.contains('.') && !cleanUrl.contains(' ')) {
        cleanUrl = 'https://$cleanUrl';
      }
    }

    if (cleanUrl.endsWith('/')) {
      cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
    }

    return cleanUrl;
  }

  static String _normalizeApiNamespace(String namespace) {
    String clean = namespace.trim().replaceAll(r'\/', '/');
    if (clean.isEmpty) {
      return defaultApiNamespace;
    }

    if (clean.startsWith('http://') || clean.startsWith('https://')) {
      final wpJsonIndex = clean.indexOf('/wp-json');
      if (wpJsonIndex >= 0) {
        clean = clean.substring(wpJsonIndex);
      }
    }

    if (!clean.startsWith('/')) {
      clean = '/$clean';
    }

    return clean;
  }

  static Future<Set<String>> getFavoriteNodeKeys() async {
    final prefs = await SharedPreferences.getInstance();
    final list = prefs.getStringList(_keyFavoriteNodes) ?? [];
    return list.toSet();
  }

  static Future<bool> isFavoriteNode({
    required String siteUrl,
    required int nodeId,
  }) async {
    final key = _makeFavoriteKey(siteUrl, nodeId);
    final keys = await getFavoriteNodeKeys();
    return keys.contains(key);
  }

  static Future<bool> toggleFavoriteNode({
    required String siteUrl,
    required int nodeId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final key = _makeFavoriteKey(siteUrl, nodeId);
    final keys = await getFavoriteNodeKeys();
    final updated = keys.contains(key) ? (keys..remove(key)) : (keys..add(key));
    return prefs.setStringList(_keyFavoriteNodes, updated.toList());
  }

  static String _makeFavoriteKey(String siteUrl, int nodeId) {
    return '${siteUrl.toLowerCase()}::$nodeId';
  }
}

class SavedBusiness {
  final String id;
  final String name;
  final String serverUrl;
  final String apiNamespace;
  final String? type;
  final String? lastUsedAt;

  const SavedBusiness({
    required this.id,
    required this.name,
    required this.serverUrl,
    required this.apiNamespace,
    this.type,
    this.lastUsedAt,
  });

  factory SavedBusiness.fromJson(Map<String, dynamic> json) {
    return SavedBusiness(
      id: json['id'] as String? ?? '',
      name: json['name'] as String? ?? '',
      serverUrl: json['serverUrl'] as String? ?? '',
      apiNamespace: json['apiNamespace'] as String? ?? ServerConfig.defaultApiNamespace,
      type: json['type'] as String?,
      lastUsedAt: json['lastUsedAt'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'serverUrl': serverUrl,
      'apiNamespace': apiNamespace,
      if (type != null) 'type': type,
      if (lastUsedAt != null) 'lastUsedAt': lastUsedAt,
    };
  }

  SavedBusiness copyWith({
    String? name,
    String? lastUsedAt,
  }) {
    return SavedBusiness(
      id: id,
      name: name ?? this.name,
      serverUrl: serverUrl,
      apiNamespace: apiNamespace,
      type: type,
      lastUsedAt: lastUsedAt ?? this.lastUsedAt,
    );
  }

  bool get isEmpty => id.isEmpty || serverUrl.isEmpty;

  static String makeId(String serverUrl, String apiNamespace, String? type) {
    final safeType = (type ?? 'unknown').trim();
    return '${serverUrl.toLowerCase()}|${apiNamespace.toLowerCase()}|${safeType.toLowerCase()}';
  }

  static SavedBusiness empty() => const SavedBusiness(
        id: '',
        name: '',
        serverUrl: '',
        apiNamespace: ServerConfig.defaultApiNamespace,
      );
}

/// Provider para la configuracion del servidor
final serverConfigProvider = StateNotifierProvider<ServerConfigNotifier, ServerConfigState>((ref) {
  return ServerConfigNotifier();
});

/// Estado de la configuracion del servidor
class ServerConfigState {
  final String serverUrl;
  final String apiNamespace;
  final bool isLoading;
  final String? error;

  ServerConfigState({
    this.serverUrl = ServerConfig.defaultServerUrl,
    this.apiNamespace = ServerConfig.defaultApiNamespace,
    this.isLoading = true,
    this.error,
  });

  String get fullApiUrl => '$serverUrl$apiNamespace';

  ServerConfigState copyWith({
    String? serverUrl,
    String? apiNamespace,
    bool? isLoading,
    String? error,
  }) {
    return ServerConfigState(
      serverUrl: serverUrl ?? this.serverUrl,
      apiNamespace: apiNamespace ?? this.apiNamespace,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Notifier para la configuracion del servidor
class ServerConfigNotifier extends StateNotifier<ServerConfigState> {
  ServerConfigNotifier() : super(ServerConfigState()) {
    _loadConfig();
  }

  Future<void> _loadConfig() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final apiNamespace = await ServerConfig.getApiNamespace();
      state = state.copyWith(
        serverUrl: serverUrl,
        apiNamespace: apiNamespace,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Error al cargar configuracion',
      );
    }
  }

  Future<bool> updateServerUrl(String url) async {
    final cleanUrl = ServerConfig._normalizeServerUrl(url);
    if (!ServerConfig.isValidUrl(cleanUrl)) {
      state = state.copyWith(error: 'URL no valida');
      return false;
    }

    try {
      await ServerConfig.setCurrentBusiness(
        serverUrl: cleanUrl,
        apiNamespace: state.apiNamespace,
      );
      state = state.copyWith(serverUrl: cleanUrl, error: null);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'Error al guardar URL');
      return false;
    }
  }

  Future<bool> updateApiNamespace(String namespace) async {
    try {
      final cleanNamespace = ServerConfig._normalizeApiNamespace(namespace);
      await ServerConfig.setCurrentBusiness(
        serverUrl: state.serverUrl,
        apiNamespace: cleanNamespace,
      );
      state = state.copyWith(apiNamespace: cleanNamespace, error: null);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'Error al guardar namespace');
      return false;
    }
  }

  Future<void> resetToDefaults() async {
    await ServerConfig.resetToDefaults();
    state = ServerConfigState(
      serverUrl: ServerConfig.defaultServerUrl,
      apiNamespace: ServerConfig.defaultApiNamespace,
      isLoading: false,
    );
  }

  Future<void> setCurrentBusiness(SavedBusiness business) async {
    final cleanUrl = ServerConfig._normalizeServerUrl(business.serverUrl);
    final cleanNamespace = ServerConfig._normalizeApiNamespace(business.apiNamespace);
    await ServerConfig.setCurrentBusiness(
      serverUrl: cleanUrl,
      apiNamespace: cleanNamespace,
      name: business.name,
      type: business.type,
    );
    state = state.copyWith(
      serverUrl: cleanUrl,
      apiNamespace: cleanNamespace,
      error: null,
    );
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}
