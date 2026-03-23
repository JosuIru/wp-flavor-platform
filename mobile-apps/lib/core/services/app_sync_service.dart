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
import '../utils/logger.dart';
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

      Logger.i('Initialized - Site: $_currentSiteName', tag: 'AppSync');
    } catch (e) {
      Logger.e('Error initializing', tag: 'AppSync', error: e);
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

      Logger.i('Syncing with $cleanUrl', tag: 'AppSync');

      // Detectar namespace API preferido del sitio (evitar forzar siempre chat-ia-mobile)
      String preferredApiNamespace = ServerConfig.defaultApiNamespace;
      final discoveryProbe = await ApiClient.discoverSiteAt(cleanUrl);
      if (discoveryProbe.success && discoveryProbe.data != null) {
        preferredApiNamespace = await ApiClient.detectPreferredApiNamespace(
          cleanUrl,
          discoveryData: discoveryProbe.data,
        );
      }

      // Limpiar cachés para forzar configuración nueva
      await DynamicConfig().clearCache();
      await LayoutService().clearCache();

      // Guardar URL en ServerConfig
      await ServerConfig.setCurrentBusiness(
        serverUrl: cleanUrl,
        apiNamespace: preferredApiNamespace,
      );

      // Crear cliente API
      final apiClient = await ApiClient.fromSavedConfig();

      // 1. Obtener información de descubrimiento (incluye layouts y tema)
      final discoveryResponse = await apiClient.getAppDiscoveryInfo();
      Map<String, dynamic> siteInfo;

      final bool usingLegacySync;
      if (discoveryResponse.success && discoveryResponse.data != null) {
        siteInfo = discoveryResponse.data!;
        usingLegacySync = false;
      } else {
        // Fallback legado: sitios sin app-discovery pero con chat-ia-mobile.
        final siteInfoResponse = await apiClient.getSiteInfo();
        if (!siteInfoResponse.success || siteInfoResponse.data == null) {
          throw Exception(
            discoveryResponse.error ??
                siteInfoResponse.error ??
                'Error al conectar con el sitio',
          );
        }

        final legacy = siteInfoResponse.data!;
        final legacyTheme = <String, dynamic>{
          'primary_color': legacy['config']?['primary_color'],
          'logo_url': legacy['logo_url'],
        };

        siteInfo = {
          'site_name': legacy['name'] ?? cleanUrl,
          'app_name': legacy['name'] ?? cleanUrl,
          'theme': legacyTheme,
          'layouts': {'available': false},
          'legacy_sync': true,
        };
        usingLegacySync = true;
      }

      // Extraer información del sitio
      final siteName =
          (siteInfo['app_name'] ?? siteInfo['site_name'])?.toString();
      if (siteName != null && siteName.trim().isNotEmpty) {
        await ServerConfig.updateCurrentBusinessName(siteName);
      }

      // 2. Actualizar DynamicConfig con tema
      if (siteInfo['theme'] != null) {
        final theme = Map<String, dynamic>.from(siteInfo['theme'] as Map);
        final colors = <String, dynamic>{
          'primary': theme['primary_color'] ?? theme['primary'],
          'secondary': theme['secondary_color'] ?? theme['secondary'],
          'accent': theme['accent_color'] ?? theme['accent'],
          'background': theme['background_color'] ?? theme['background'],
          'surface': theme['surface_color'] ?? theme['surface'],
          'text_primary': theme['text_primary_color'] ??
              theme['text_primary'] ??
              theme['text_color'],
          'text_secondary':
              theme['text_secondary_color'] ?? theme['text_secondary'],
        };

        await DynamicConfig().updateConfig({
          'colors': colors,
          'branding': {
            'business_name': siteInfo['site_name'],
            'app_name': siteInfo['app_name'],
            'logo_url': theme['logo_url'],
          },
        });
      }

      // En modo legado (sin app-discovery), cargar también tabs/features desde
      // /chat-ia-mobile/v1/client-app-config para que la app refleje el sitio.
      if (usingLegacySync) {
        final clientConfigResponse = await apiClient.getClientAppConfig();
        if (clientConfigResponse.success && clientConfigResponse.data != null) {
          final rawConfig = clientConfigResponse.data!['config'];
          if (rawConfig is Map) {
            await DynamicConfig()
                .updateConfig(Map<String, dynamic>.from(rawConfig));
            Logger.i(
              'Legacy sync: client-app-config loaded',
              tag: 'AppSync',
            );
          }
        } else {
          Logger.w(
            'Legacy sync: client-app-config unavailable (${clientConfigResponse.error})',
            tag: 'AppSync',
          );
        }
      }

      // 3. Actualizar LayoutService con layouts
      if (siteInfo['layouts'] != null &&
          siteInfo['layouts']['available'] == true) {
        final theme = siteInfo['theme'] != null
            ? Map<String, dynamic>.from(siteInfo['theme'] as Map)
            : <String, dynamic>{};
        final layoutTheme = <String, dynamic>{
          ...theme,
          'text_color': theme['text_color'] ??
              theme['text_primary_color'] ??
              theme['text_primary'],
        };
        await LayoutService().updateConfig(
          siteInfo['layouts'],
          themeData: layoutTheme,
        );
        Logger.i('Layouts updated from site', tag: 'AppSync');
      } else {
        Logger.d('No layouts available, using defaults', tag: 'AppSync');
      }

      // 4. Guardar estado de sincronización
      _currentSiteUrl = cleanUrl;
      _currentSiteName = siteName;
      _lastSyncTime = DateTime.now();

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_siteUrlKey, cleanUrl);
      if (siteName != null && siteName.trim().isNotEmpty) {
        await prefs.setString(_siteNameKey, siteName);
      }
      await prefs.setInt(_lastSyncKey, DateTime.now().millisecondsSinceEpoch);
      await prefs.setString(_siteInfoKey, siteInfo.toString());

      _status = SyncStatus.success;
      _notifyListeners();

      Logger.i('Sync successful - $siteName', tag: 'AppSync');

      final resolvedSiteName = (siteName != null && siteName.trim().isNotEmpty)
          ? siteName
          : cleanUrl;
      return SyncResult.success(
        siteName: resolvedSiteName,
        siteInfo: siteInfo,
      );
    } catch (e) {
      Logger.e('Sync error', tag: 'AppSync', error: e);
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
    Logger.i('Disconnected', tag: 'AppSync');
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
      return dynamicConfig
          .buildColorScheme(brightness: brightness)
          .toThemeData();
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
