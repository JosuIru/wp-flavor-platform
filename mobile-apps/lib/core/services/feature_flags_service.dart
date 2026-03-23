import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';
import 'dart:io';

/// Servicio de Feature Flags
class FeatureFlagsService {
  static FeatureFlagsService? _instance;

  final ApiClient _apiClient;
  static const String _prefsKeyFlags = 'feature_flags';
  static const String _prefsKeyTimestamp = 'feature_flags_timestamp';
  static const Duration _cacheValidDuration = Duration(hours: 1);

  Map<String, bool> _flags = {};
  DateTime? _lastFetchTime;

  FeatureFlagsService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory FeatureFlagsService({required ApiClient apiClient}) {
    _instance ??= FeatureFlagsService._(apiClient: apiClient);
    return _instance!;
  }

  /// Flags actuales
  Map<String, bool> get flags => Map.unmodifiable(_flags);

  /// Inicializar servicio
  Future<void> initialize() async {
    await _loadFromCache();
    await refresh();
  }

  /// Cargar flags desde cache
  Future<void> _loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final flagsJson = prefs.getString(_prefsKeyFlags);
      final timestampStr = prefs.getString(_prefsKeyTimestamp);

      if (flagsJson != null) {
        _flags = Map<String, bool>.from(jsonDecode(flagsJson));
      }

      if (timestampStr != null) {
        _lastFetchTime = DateTime.tryParse(timestampStr);
      }

      debugPrint('[FeatureFlags] Cargados ${_flags.length} flags desde cache');
    } catch (e) {
      debugPrint('[FeatureFlags] Error cargando cache: $e');
    }
  }

  /// Guardar flags en cache
  Future<void> _saveToCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_prefsKeyFlags, jsonEncode(_flags));
      await prefs.setString(_prefsKeyTimestamp, DateTime.now().toIso8601String());
    } catch (e) {
      debugPrint('[FeatureFlags] Error guardando cache: $e');
    }
  }

  /// Refrescar flags desde el servidor
  Future<bool> refresh({bool force = false}) async {
    // Usar cache si es válido
    if (!force && _lastFetchTime != null) {
      final elapsed = DateTime.now().difference(_lastFetchTime!);
      if (elapsed < _cacheValidDuration) {
        debugPrint('[FeatureFlags] Usando cache (válido por ${(_cacheValidDuration - elapsed).inMinutes}m)');
        return true;
      }
    }

    try {
      final platform = Platform.isIOS ? 'ios' : 'android';

      final response = await _apiClient.get(
        '/flavor-app/v2/feature-flags',
        queryParameters: {
          'platform': platform,
          'app_version': AppConfig.appVersion,
          'user_id': AppConfig.userId,
        },
      );

      final responseData = response.data;
      if (responseData?['flags'] != null) {
        _flags = Map<String, bool>.from(responseData?['flags']);
        _lastFetchTime = DateTime.now();
        await _saveToCache();

        debugPrint('[FeatureFlags] Actualizados ${_flags.length} flags');
        return true;
      }

      return false;
    } catch (e) {
      debugPrint('[FeatureFlags] Error obteniendo flags: $e');
      return false;
    }
  }

  /// Verificar si un flag está habilitado
  bool isEnabled(String flagKey, {bool defaultValue = false}) {
    return _flags[flagKey] ?? defaultValue;
  }

  /// Alias para isEnabled
  bool operator [](String key) => isEnabled(key);

  /// Verificar múltiples flags
  bool allEnabled(List<String> flagKeys) {
    return flagKeys.every((key) => isEnabled(key));
  }

  /// Verificar si algún flag está habilitado
  bool anyEnabled(List<String> flagKeys) {
    return flagKeys.any((key) => isEnabled(key));
  }

  /// Obtener valor con tipo específico (para flags con valores)
  T getValue<T>(String flagKey, T defaultValue) {
    final value = _flags[flagKey];
    if (value != null && value is T) return value as T;
    return defaultValue;
  }

  /// Forzar un valor localmente (para testing)
  void overrideFlag(String flagKey, bool value) {
    _flags[flagKey] = value;
    debugPrint('[FeatureFlags] Override: $flagKey = $value');
  }

  /// Limpiar overrides
  Future<void> clearOverrides() async {
    await refresh(force: true);
  }

  /// Limpiar cache
  Future<void> clearCache() async {
    _flags.clear();
    _lastFetchTime = null;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_prefsKeyFlags);
      await prefs.remove(_prefsKeyTimestamp);
    } catch (e) {
      debugPrint('[FeatureFlags] Error limpiando cache: $e');
    }
  }
}

/// Extensión para usar flags como condicionales
extension FeatureFlagExtension on FeatureFlagsService {
  /// Ejecutar código si el flag está habilitado
  T? whenEnabled<T>(String flagKey, T Function() callback, {T? defaultValue}) {
    if (isEnabled(flagKey)) {
      return callback();
    }
    return defaultValue;
  }

  /// Widget condicional basado en flag
  // Se usa así: featureFlags.widget('new_ui', NewWidget(), OldWidget())
}

/// Flags comunes predefinidos
class CommonFlags {
  static const String darkMode = 'dark_mode';
  static const String biometricLogin = 'biometric_login';
  static const String offlineMode = 'offline_mode';
  static const String pushNotifications = 'push_notifications';
  static const String analyticsEnabled = 'analytics_enabled';
  static const String crashReporting = 'crash_reporting';
  static const String inAppReview = 'in_app_review';
  static const String newUiEnabled = 'new_ui_enabled';
}
