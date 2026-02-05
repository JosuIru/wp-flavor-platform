import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Configuracion del servidor - persistente
class ServerConfig {
  static const String _keyServerUrl = 'server_url';
  static const String _keyApiNamespace = 'api_namespace';
  static const String _keyIsConfigured = 'is_configured';

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
  static const String defaultServerUrl = 'https://basabere.com';

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
    // Limpiar URL (quitar / final si existe)
    String cleanUrl = url.trim();
    if (cleanUrl.endsWith('/')) {
      cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
    }
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
    return prefs.setString(_keyApiNamespace, namespace);
  }

  /// Obtiene la URL completa de la API
  static Future<String> getFullApiUrl() async {
    final serverUrl = await getServerUrl();
    final namespace = await getApiNamespace();
    return '$serverUrl$namespace';
  }

  /// Resetea a valores por defecto
  static Future<void> resetToDefaults() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyServerUrl);
    await prefs.remove(_keyApiNamespace);
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
    if (!ServerConfig.isValidUrl(url)) {
      state = state.copyWith(error: 'URL no valida');
      return false;
    }

    try {
      await ServerConfig.setServerUrl(url);
      state = state.copyWith(serverUrl: url, error: null);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'Error al guardar URL');
      return false;
    }
  }

  Future<bool> updateApiNamespace(String namespace) async {
    try {
      await ServerConfig.setApiNamespace(namespace);
      state = state.copyWith(apiNamespace: namespace, error: null);
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

  void clearError() {
    state = state.copyWith(error: null);
  }
}
