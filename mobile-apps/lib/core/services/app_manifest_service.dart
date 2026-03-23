/// App Manifest Service v2.0
///
/// Servicio mejorado de sincronización que usa el endpoint unificado
/// /flavor-app/v2/manifest para obtener toda la configuración.
///
/// Características:
/// - Versionado de configuración con checksum
/// - Verificación rápida antes de descargar todo
/// - Confirmación de sincronización bidireccional
/// - Resolución automática de dependencias de módulos
/// - Caché inteligente con invalidación

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import '../config/server_config.dart';
import '../config/dynamic_config.dart';
import '../utils/logger.dart';
import '../../features/layouts/layout_config.dart';

/// Estado de sincronización
enum ManifestSyncStatus {
  idle,
  checking,
  syncing,
  success,
  upToDate,
  error,
}

/// Resultado de verificación de versión
class VersionCheckResult {
  final bool needsUpdate;
  final String? reason;
  final String? currentVersion;
  final String? localVersion;

  VersionCheckResult({
    required this.needsUpdate,
    this.reason,
    this.currentVersion,
    this.localVersion,
  });
}

/// Resultado de sincronización
class ManifestSyncResult {
  final bool success;
  final String? siteName;
  final String? version;
  final String? error;
  final Map<String, dynamic>? manifest;
  final bool wasUpToDate;

  ManifestSyncResult({
    required this.success,
    this.siteName,
    this.version,
    this.error,
    this.manifest,
    this.wasUpToDate = false,
  });

  factory ManifestSyncResult.success({
    required String siteName,
    required String version,
    Map<String, dynamic>? manifest,
  }) {
    return ManifestSyncResult(
      success: true,
      siteName: siteName,
      version: version,
      manifest: manifest,
    );
  }

  factory ManifestSyncResult.upToDate({
    required String siteName,
    required String version,
  }) {
    return ManifestSyncResult(
      success: true,
      siteName: siteName,
      version: version,
      wasUpToDate: true,
    );
  }

  factory ManifestSyncResult.error(String message) {
    return ManifestSyncResult(
      success: false,
      error: message,
    );
  }
}

/// Servicio de manifiesto de app
class AppManifestService {
  // Singleton
  static final AppManifestService _instance = AppManifestService._internal();
  factory AppManifestService() => _instance;
  AppManifestService._internal();

  // Estado
  ManifestSyncStatus _status = ManifestSyncStatus.idle;
  String? _currentSiteUrl;
  String? _currentSiteName;
  String? _currentVersion;
  String? _currentChecksum;
  DateTime? _lastSyncTime;
  Map<String, dynamic>? _cachedManifest;

  // Cache keys
  static const String _siteUrlKey = 'manifest_site_url';
  static const String _siteNameKey = 'manifest_site_name';
  static const String _versionKey = 'manifest_version';
  static const String _checksumKey = 'manifest_checksum';
  static const String _lastSyncKey = 'manifest_last_sync';
  static const String _manifestKey = 'manifest_data';
  static const String _deviceIdKey = 'manifest_device_id';

  // Listeners
  final List<VoidCallback> _listeners = [];

  // API endpoints
  static const String _manifestEndpoint = '/wp-json/flavor-app/v2/manifest';
  static const String _checkEndpoint = '/wp-json/flavor-app/v2/manifest/check';
  static const String _confirmEndpoint = '/wp-json/flavor-app/v2/sync/confirm';

  /// Estado actual
  ManifestSyncStatus get status => _status;

  /// URL del sitio actual
  String? get currentSiteUrl => _currentSiteUrl;

  /// Nombre del sitio actual
  String? get currentSiteName => _currentSiteName;

  /// Versión de configuración actual
  String? get currentVersion => _currentVersion;

  /// Última sincronización
  DateTime? get lastSyncTime => _lastSyncTime;

  /// Indica si está sincronizado
  bool get isSynced =>
      _currentSiteUrl != null &&
      _currentSiteUrl!.isNotEmpty &&
      _currentVersion != null;

  /// Manifiesto cacheado
  Map<String, dynamic>? get cachedManifest => _cachedManifest;

  /// Añadir listener
  void addListener(VoidCallback listener) {
    _listeners.add(listener);
  }

  /// Remover listener
  void removeListener(VoidCallback listener) {
    _listeners.remove(listener);
  }

  void _notifyListeners() {
    for (final listener in _listeners) {
      listener();
    }
  }

  void _setStatus(ManifestSyncStatus newStatus) {
    _status = newStatus;
    _notifyListeners();
  }

  /// Inicializar servicio desde caché
  Future<void> initialize() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      _currentSiteUrl = prefs.getString(_siteUrlKey);
      _currentSiteName = prefs.getString(_siteNameKey);
      _currentVersion = prefs.getString(_versionKey);
      _currentChecksum = prefs.getString(_checksumKey);

      final lastSyncMs = prefs.getInt(_lastSyncKey);
      if (lastSyncMs != null) {
        _lastSyncTime = DateTime.fromMillisecondsSinceEpoch(lastSyncMs);
      }

      // Cargar manifiesto cacheado
      final manifestJson = prefs.getString(_manifestKey);
      if (manifestJson != null) {
        _cachedManifest = jsonDecode(manifestJson) as Map<String, dynamic>;
      }

      Logger.i(
        'Initialized - Site: $_currentSiteName, Version: $_currentVersion',
        tag: 'Manifest',
      );
    } catch (e) {
      Logger.e('Error initializing', tag: 'Manifest', error: e);
    }
  }

  /// Obtener o generar device ID
  Future<String> _getDeviceId() async {
    final prefs = await SharedPreferences.getInstance();
    var deviceId = prefs.getString(_deviceIdKey);

    if (deviceId == null) {
      // Generar nuevo device ID
      deviceId = 'device_${DateTime.now().millisecondsSinceEpoch}_'
          '${(1000 + (DateTime.now().microsecond % 9000))}';
      await prefs.setString(_deviceIdKey, deviceId);
    }

    return deviceId;
  }

  /// Verificar si hay actualización disponible sin descargar todo
  Future<VersionCheckResult> checkForUpdates() async {
    if (_currentSiteUrl == null || _currentSiteUrl!.isEmpty) {
      return VersionCheckResult(
        needsUpdate: true,
        reason: 'no_site_configured',
      );
    }

    _setStatus(ManifestSyncStatus.checking);

    try {
      final checkUrl = '$_currentSiteUrl$_checkEndpoint'
          '?version=$_currentVersion'
          '&checksum=$_currentChecksum';

      final response = await http.get(
        Uri.parse(checkUrl),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;

        _setStatus(ManifestSyncStatus.idle);

        return VersionCheckResult(
          needsUpdate: data['needs_update'] ?? true,
          reason: data['reason'] as String?,
          currentVersion: data['current_version'] as String?,
          localVersion: _currentVersion,
        );
      } else {
        _setStatus(ManifestSyncStatus.error);
        return VersionCheckResult(
          needsUpdate: true,
          reason: 'check_failed',
        );
      }
    } catch (e) {
      Logger.e('Error checking for updates', tag: 'Manifest', error: e);
      _setStatus(ManifestSyncStatus.error);
      return VersionCheckResult(
        needsUpdate: true,
        reason: 'network_error',
      );
    }
  }

  /// Sincronizar con un sitio usando el nuevo endpoint de manifest
  Future<ManifestSyncResult> syncWithSite(
    String siteUrl, {
    bool forceRefresh = false,
  }) async {
    if (_status == ManifestSyncStatus.syncing) {
      return ManifestSyncResult.error('Ya hay una sincronización en progreso');
    }

    _setStatus(ManifestSyncStatus.syncing);

    try {
      // Limpiar URL
      String cleanUrl = siteUrl.trim();
      if (cleanUrl.endsWith('/')) {
        cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
      }

      Logger.i('Syncing with $cleanUrl', tag: 'Manifest');

      // Si no es forzado y ya tenemos versión, verificar primero
      if (!forceRefresh && _currentSiteUrl == cleanUrl && _currentVersion != null) {
        final checkResult = await checkForUpdates();
        if (!checkResult.needsUpdate) {
          Logger.i('Already up to date: $_currentVersion', tag: 'Manifest');
          _setStatus(ManifestSyncStatus.upToDate);
          return ManifestSyncResult.upToDate(
            siteName: _currentSiteName ?? cleanUrl,
            version: _currentVersion!,
          );
        }
      }

      // Obtener manifiesto completo
      final manifestUrl =
          '$cleanUrl$_manifestEndpoint${forceRefresh ? '?refresh=1' : ''}';

      final response = await http.get(
        Uri.parse(manifestUrl),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode != 200) {
        throw Exception('Error al obtener manifiesto: ${response.statusCode}');
      }

      final manifest = jsonDecode(response.body) as Map<String, dynamic>;

      // Extraer información
      final version = manifest['version'] as String?;
      final checksum = manifest['checksum'] as String?;
      final siteName = manifest['branding']?['app_name'] as String? ??
          manifest['site']?['name'] as String? ??
          cleanUrl;

      // Aplicar configuración
      await _applyManifest(manifest);

      // Guardar estado
      _currentSiteUrl = cleanUrl;
      _currentSiteName = siteName;
      _currentVersion = version;
      _currentChecksum = checksum;
      _lastSyncTime = DateTime.now();
      _cachedManifest = manifest;

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_siteUrlKey, cleanUrl);
      await prefs.setString(_siteNameKey, siteName);
      if (version != null) await prefs.setString(_versionKey, version);
      if (checksum != null) await prefs.setString(_checksumKey, checksum);
      await prefs.setInt(_lastSyncKey, DateTime.now().millisecondsSinceEpoch);
      await prefs.setString(_manifestKey, jsonEncode(manifest));

      // Confirmar sincronización al servidor
      await _confirmSync(cleanUrl, version ?? '');

      _setStatus(ManifestSyncStatus.success);
      Logger.i('Sync successful - $siteName v$version', tag: 'Manifest');

      return ManifestSyncResult.success(
        siteName: siteName,
        version: version ?? 'unknown',
        manifest: manifest,
      );
    } catch (e) {
      Logger.e('Sync error', tag: 'Manifest', error: e);
      _setStatus(ManifestSyncStatus.error);
      return ManifestSyncResult.error(e.toString());
    }
  }

  /// Aplicar manifiesto a la configuración de la app
  Future<void> _applyManifest(Map<String, dynamic> manifest) async {
    // 1. Aplicar tema
    if (manifest['theme'] != null) {
      final theme = manifest['theme'] as Map<String, dynamic>;
      final lightTheme = theme['light'] as Map<String, dynamic>?;
      final darkTheme = theme['dark'] as Map<String, dynamic>?;

      await DynamicConfig().updateConfig({
        'colors': {
          'primary': lightTheme?['primary'],
          'secondary': lightTheme?['secondary'],
          'background': lightTheme?['background'],
          'surface': lightTheme?['surface'],
          'error': lightTheme?['error'],
        },
        'dark_colors': {
          'primary': darkTheme?['primary'],
          'secondary': darkTheme?['secondary'],
          'background': darkTheme?['background'],
          'surface': darkTheme?['surface'],
        },
        'theme_mode': theme['theme_mode'],
      });

      Logger.d('Theme applied', tag: 'Manifest');
    }

    // 2. Aplicar branding
    if (manifest['branding'] != null) {
      final branding = manifest['branding'] as Map<String, dynamic>;

      await DynamicConfig().updateConfig({
        'branding': {
          'app_name': branding['app_name'],
          'app_description': branding['app_description'],
          'logo_url': branding['logo_url'],
        },
      });

      // Actualizar ServerConfig si es necesario
      final appName = branding['app_name'] as String?;
      if (appName != null && appName.isNotEmpty) {
        await ServerConfig.updateCurrentBusinessName(appName);
      }

      Logger.d('Branding applied', tag: 'Manifest');
    }

    // 3. Aplicar navegación
    if (manifest['navigation'] != null) {
      final navigation = manifest['navigation'] as Map<String, dynamic>;

      await LayoutService().updateConfig(
        {
          'available': true,
          'navigation': navigation['bottom_tabs'],
          'drawer': navigation['drawer'],
          'client_tabs': navigation['bottom_tabs'],
          'settings': {
            'navigation_style': navigation['style'],
            'use_bottom_navigation': true,
          },
        },
        themeData: manifest['theme']?['light'] as Map<String, dynamic>?,
      );

      Logger.d('Navigation applied', tag: 'Manifest');
    }

    // 4. Aplicar módulos activos
    if (manifest['modules'] != null) {
      final modules = manifest['modules'] as Map<String, dynamic>;
      final activeModules = modules['active'] as List<dynamic>?;

      if (activeModules != null) {
        final moduleIds =
            activeModules.map((m) => (m as Map<String, dynamic>)['id'] as String).toList();

        await DynamicConfig().updateConfig({
          'modules': {
            'active': moduleIds,
            'total': modules['total'],
          },
        });

        Logger.d('Modules applied: ${moduleIds.length}', tag: 'Manifest');
      }
    }

    // 5. Aplicar features
    if (manifest['features'] != null) {
      await DynamicConfig().updateConfig({
        'features': manifest['features'],
      });

      Logger.d('Features applied', tag: 'Manifest');
    }
  }

  /// Confirmar sincronización al servidor
  Future<void> _confirmSync(String siteUrl, String version) async {
    try {
      final deviceId = await _getDeviceId();

      await http.post(
        Uri.parse('$siteUrl$_confirmEndpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'device_id': deviceId,
          'config_version': version,
          'platform': _getPlatform(),
          'app_version': await _getAppVersion(),
        }),
      ).timeout(const Duration(seconds: 10));

      Logger.d('Sync confirmed to server', tag: 'Manifest');
    } catch (e) {
      // No es crítico si falla la confirmación
      Logger.w('Could not confirm sync: $e', tag: 'Manifest');
    }
  }

  String _getPlatform() {
    // Detectar plataforma
    return 'flutter_mobile';
  }

  Future<String> _getAppVersion() async {
    // TODO: Obtener versión real del pubspec
    return '1.0.0';
  }

  /// Refrescar sitio actual
  Future<ManifestSyncResult> refreshCurrentSite({bool force = false}) async {
    if (_currentSiteUrl == null || _currentSiteUrl!.isEmpty) {
      return ManifestSyncResult.error('No hay sitio configurado');
    }

    return syncWithSite(_currentSiteUrl!, forceRefresh: force);
  }

  /// Desconectar del sitio actual
  Future<void> disconnect() async {
    _currentSiteUrl = null;
    _currentSiteName = null;
    _currentVersion = null;
    _currentChecksum = null;
    _lastSyncTime = null;
    _cachedManifest = null;
    _setStatus(ManifestSyncStatus.idle);

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_siteUrlKey);
    await prefs.remove(_siteNameKey);
    await prefs.remove(_versionKey);
    await prefs.remove(_checksumKey);
    await prefs.remove(_lastSyncKey);
    await prefs.remove(_manifestKey);

    await DynamicConfig().clearCache();
    await LayoutService().clearCache();
    await ServerConfig.resetToDefaults();

    Logger.i('Disconnected', tag: 'Manifest');
  }

  /// Verificar si necesita sincronización
  bool get needsSync {
    if (!isSynced) return true;
    if (_lastSyncTime == null) return true;

    // Re-sincronizar si han pasado más de 24 horas
    final hoursSinceSync = DateTime.now().difference(_lastSyncTime!).inHours;
    return hoursSinceSync >= 24;
  }

  /// Obtener módulos activos del manifiesto
  List<Map<String, dynamic>> getActiveModules() {
    if (_cachedManifest == null) return [];

    final modules = _cachedManifest!['modules'] as Map<String, dynamic>?;
    if (modules == null) return [];

    final active = modules['active'] as List<dynamic>?;
    if (active == null) return [];

    return active.cast<Map<String, dynamic>>();
  }

  /// Obtener features habilitados
  Map<String, bool> getFeatures() {
    if (_cachedManifest == null) return {};

    final features = _cachedManifest!['features'] as Map<String, dynamic>?;
    if (features == null) return {};

    return features.map((key, value) => MapEntry(key, value == true));
  }

  /// Obtener endpoints de API
  Map<String, String> getApiEndpoints() {
    if (_cachedManifest == null) return {};

    final endpoints = _cachedManifest!['api_endpoints'] as Map<String, dynamic>?;
    if (endpoints == null) return {};

    return endpoints.map((key, value) => MapEntry(key, value.toString()));
  }

  /// Obtener ThemeData basado en la configuración
  ThemeData getThemeData({Brightness brightness = Brightness.light}) {
    if (_cachedManifest == null) {
      return ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
      );
    }

    final theme = _cachedManifest!['theme'] as Map<String, dynamic>?;
    if (theme == null) {
      return ThemeData(useMaterial3: true);
    }

    final colors = brightness == Brightness.light
        ? theme['light'] as Map<String, dynamic>?
        : theme['dark'] as Map<String, dynamic>?;

    if (colors == null) {
      return ThemeData(useMaterial3: true, brightness: brightness);
    }

    try {
      return ThemeData(
        useMaterial3: true,
        brightness: brightness,
        colorScheme: ColorScheme(
          brightness: brightness,
          primary: _parseColor(colors['primary']),
          onPrimary: _parseColor(colors['on_primary']),
          secondary: _parseColor(colors['secondary']),
          onSecondary: _parseColor(colors['on_secondary']),
          error: _parseColor(colors['error']),
          onError: _parseColor(colors['on_error']),
          surface: _parseColor(colors['surface']),
          onSurface: _parseColor(colors['on_surface']),
        ),
      );
    } catch (e) {
      Logger.e('Error building theme', tag: 'Manifest', error: e);
      return ThemeData(useMaterial3: true, brightness: brightness);
    }
  }

  Color _parseColor(dynamic colorValue) {
    if (colorValue == null) return Colors.grey;

    if (colorValue is String) {
      final hex = colorValue.replaceAll('#', '');
      if (hex.length == 6) {
        return Color(int.parse('FF$hex', radix: 16));
      } else if (hex.length == 8) {
        return Color(int.parse(hex, radix: 16));
      }
    }

    return Colors.grey;
  }
}
