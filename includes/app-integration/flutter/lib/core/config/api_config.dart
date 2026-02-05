/// Configuración global de la API
class ApiConfig {
  /// URL base del sitio WordPress actual
  static String baseUrl = 'https://basabere.com';

  /// URL del servidor de directorio central
  /// Si es null, usa el baseUrl local
  static String? directoryServerUrl;

  /// Token de API para autenticación
  static String apiToken = '';

  /// Headers comunes para las peticiones
  static Map<String, String> get headers => {
    'Authorization': 'Bearer $apiToken',
    'Content-Type': 'application/json',
  };

  /// Timeout para peticiones HTTP
  static const Duration timeout = Duration(seconds: 30);

  /// Versión de la API
  static const String apiVersion = '1.0';

  /// Endpoints
  static const String discoveryEndpoint = '/wp-json/app-discovery/v1';
  static const String modulesEndpoint = '/wp-json/flavor-chat-ia/v1';

  /// Actualiza la URL base y el token
  static void configure({
    required String newBaseUrl,
    String? newApiToken,
  }) {
    baseUrl = newBaseUrl;
    if (newApiToken != null) {
      apiToken = newApiToken;
    }
  }

  /// Obtiene la URL completa para un endpoint
  static String getUrl(String endpoint) {
    return '$baseUrl$endpoint';
  }
}
