/// App Sync Service
///
/// Coordina la sincronización de la app con sitios WordPress.
/// Cuando la app se conecta a un nuevo sitio, este servicio:
/// 1. Descarga la configuración del sitio
/// 2. Aplica los layouts (menús, footers)
/// 3. Aplica el tema (colores, tipografía)
/// 4. Cachea todo localmente
///
/// Esto permite que una única APK se transforme en la app
/// de cualquier sitio que tenga el plugin Flavor Chat IA.

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../config/server_config.dart';
import '../config/dynamic_config.dart';
import '../../features/layouts/layout_config.dart';

/// Estado de sincronización
enum SyncStatus {
  idle,
  syncing,
  success,
  error,
}

/// Resultado de la sincronización
class SyncResult {
  final bool success;
  final String? siteName;
  final String? error;
  final Map<String, dynamic>? siteInfo;

  SyncResult({
    required this.success,
    this.siteName,
    this.error,
    this.siteInfo,
  });

  factory SyncResult.success({
    required String siteName,
    Map<String, dynamic>? siteInfo,
  }) {
    return SyncResult(
      success: true,
      siteName: siteName,
      siteInfo: siteInfo,
    );
  }

  factory SyncResult.error(String message) {
    return SyncResult(
      success: false,
      error: message,
    );
  }
}

/// Servicio de sincronización de la app
class AppSyncService {
  // Singleton
  static final AppSyncService _instance = AppSyncService._internal();
  factory AppSyncService() => _instance;
  AppSyncService._internal();

  // Estado
  SyncStatus _status = SyncStatus.idle;
  String? _currentSiteUrl;
  String? _currentSiteName;
  DateTime? _lastSyncTime;

  // Cache keys
  static const String _siteUrlKey = 'sync_site_url';
  static const String _siteNameKey = 'sync_site_name';
  static const String _lastSyncKey = 'sync_last_time';
  static const String _siteInfoKey = 'sync_site_info';

  // Listeners
  final List<VoidCallback> _listeners = [];

  /// Estado actual de sincronización
  SyncStatus get status => _status;

  /// URL del sitio actual
  String? get currentSiteUrl => _currentSiteUrl;

  /// Nombre del sitio actual
  String? get currentSiteName => _currentSiteName;

  /// Última sincronización
  DateTime? get lastSyncTime => _lastSyncTime;

  /// Indica si está sincronizado con un sitio
  bool get isSynced => _currentSiteUrl != null && _currentSiteUrl!.isNotEmpty;

  /// Añadir listener de cambios
  void addListener(VoidCallback listener) {
    _listeners.add(listener);
  }

  /// Remover listener
  void removeListener(VoidCallback listener) {
    _listeners.remove(listener);
  }

  /// Notificar cambios
  void _notifyListeners() {
    for (final listener in _listeners) {
      listener();
    }
  }

  /// Inicializar servicio (cargar estado desde caché)
  Future<void> initialize() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _currentSiteUrl = prefs.getString(_siteUrlKey);
      _currentSiteName = prefs.getString(_siteNameKey);

      final lastSyncMs = prefs.getInt(_lastSyncKey);
      if (lastSyncMs != null) {
        _lastSyncTime = DateTime.fromMillisecondsSinceEpoch(lastSyncMs);
      }

      // Cargar configuración de layouts desde caché
      await LayoutService().loadFromCache();

      debugPrint('AppSyncService: Initialized - Site: $_currentSiteName');
    } catch (e) {
      debugPrint('AppSyncService: Error initializing: $e');
    }
  }

  /// Sincronizar con un sitio WordPress
  ///
  /// Este es el método principal que transforma la app
  /// para que funcione con el sitio especificado.
  Future<SyncResult> syncWithSite(String siteUrl) async {
    if (_status == SyncStatus.syncing) {
      return SyncResult.error('Ya hay una sincronización en progreso');
    }

    _status = SyncStatus.syncing;
    _notifyListeners();

    try {
      // Limpiar URL
      String cleanUrl = siteUrl.trim();
      if (cleanUrl.endsWith('/')) {
        cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
      }

      // Validar URL
      if (!ServerConfig.isValidUrl(cleanUrl)) {
        throw Exception('URL no válida');
      }

      debugPrint('AppSyncService: Syncing with $cleanUrl');

      // Guardar URL en ServerConfig
      await ServerConfig.setServerUrl(cleanUrl);

      // Crear cliente API
      final apiClient = await ApiClient.fromSavedConfig();

      // 1. Obtener información de descubrimiento (incluye layouts y tema)
      final discoveryResponse = await apiClient.getAppDiscoveryInfo();

      if (!discoveryResponse.success || discoveryResponse.data == null) {
        throw Exception(discoveryResponse.error ?? 'Error al conectar con el sitio');
      }

      final siteInfo = discoveryResponse.data!;

      // Extraer información del sitio
      final siteName = siteInfo['app_name'] ?? siteInfo['site_name'] ?? 'Mi App';

      // 2. Actualizar DynamicConfig con tema
      if (siteInfo['theme'] != null) {
        await DynamicConfig().updateConfig({
          'colors': siteInfo['theme'],
          'branding': {
            'business_name': siteInfo['site_name'],
            'app_name': siteInfo['app_name'],
            'logo_url': siteInfo['theme']['logo_url'],
          },
        });
      }

      // 3. Actualizar LayoutService con layouts
      if (siteInfo['layouts'] != null && siteInfo['layouts']['available'] == true) {
        await LayoutService().updateConfig(
          siteInfo['layouts'],
          themeData: siteInfo['theme'],
        );
        debugPrint('AppSyncService: Layouts updated from site');
      } else {
        debugPrint('AppSyncService: No layouts available, using defaults');
      }

      // 4. Guardar estado de sincronización
      _currentSiteUrl = cleanUrl;
      _currentSiteName = siteName;
      _lastSyncTime = DateTime.now();

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_siteUrlKey, cleanUrl);
      await prefs.setString(_siteNameKey, siteName);
      await prefs.setInt(_lastSyncKey, DateTime.now().millisecondsSinceEpoch);
      await prefs.setString(_siteInfoKey, siteInfo.toString());

      _status = SyncStatus.success;
      _notifyListeners();

      debugPrint('AppSyncService: Sync successful - $siteName');

      return SyncResult.success(
        siteName: siteName,
        siteInfo: siteInfo,
      );
    } catch (e) {
      debugPrint('AppSyncService: Sync error - $e');
      _status = SyncStatus.error;
      _notifyListeners();

      return SyncResult.error(e.toString());
    }
  }

  /// Refrescar configuración del sitio actual
  Future<SyncResult> refreshCurrentSite() async {
    if (_currentSiteUrl == null || _currentSiteUrl!.isEmpty) {
      return SyncResult.error('No hay sitio configurado');
    }

    return syncWithSite(_currentSiteUrl!);
  }

  /// Desconectar del sitio actual
  Future<void> disconnect() async {
    _currentSiteUrl = null;
    _currentSiteName = null;
    _lastSyncTime = null;
    _status = SyncStatus.idle;

    // Limpiar caché
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_siteUrlKey);
    await prefs.remove(_siteNameKey);
    await prefs.remove(_lastSyncKey);
    await prefs.remove(_siteInfoKey);

    // Limpiar configuraciones
    await DynamicConfig().clearCache();
    await LayoutService().clearCache();
    await ServerConfig.resetToDefaults();

    _notifyListeners();
    debugPrint('AppSyncService: Disconnected');
  }

  /// Verificar si necesita sincronización
  bool get needsSync {
    if (!isSynced) return true;
    if (_lastSyncTime == null) return true;

    // Re-sincronizar si han pasado más de 24 horas
    final hoursSinceSync = DateTime.now().difference(_lastSyncTime!).inHours;
    return hoursSinceSync >= 24;
  }

  /// Obtener ThemeData basado en la configuración sincronizada
  ThemeData getThemeData({Brightness brightness = Brightness.light}) {
    final layoutService = LayoutService();
    if (layoutService.isLoaded) {
      return layoutService.buildThemeData(brightness: brightness);
    }

    // Fallback a DynamicConfig
    final dynamicConfig = DynamicConfig();
    if (dynamicConfig.isLoaded) {
      return dynamicConfig.buildColorScheme(brightness: brightness).toThemeData();
    }

    // Tema por defecto
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
    );
  }
}

/// Extension para convertir ColorScheme a ThemeData
extension ColorSchemeToThemeData on ColorScheme {
  ThemeData toThemeData() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: this,
    );
  }
}
