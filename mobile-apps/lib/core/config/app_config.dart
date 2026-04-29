import '../utils/flavor_url_launcher.dart';

// Configuración generada / editable manualmente.
// Última edición auditoría 2026-04-29: serverUrl/apiKey/isDebug se leen
// desde --dart-define para que el binario release no embeba el host de
// desarrollo ni una key vacía. Si se regenera este archivo desde
// includes/api/class-app-config-api.php, mantener el patrón
// `String.fromEnvironment(...)` para esos tres campos.

class AppConfig {
  static const String appName = 'Komunitatea';
  static const String clientAppName = appName;
  static const String adminAppName = '$appName Admin';
  static const String appId = 'com.komunitatea.app';
  static const String packageName = appId;

  // Server URL: leído desde --dart-define=SERVER_URL=https://...
  // Default solo se aplica en desarrollo. Build release sin define
  // mantendrá el default — añadir guard en pre-build script si se
  // quiere fallar la compilación.
  static const String serverUrl = String.fromEnvironment(
    'SERVER_URL',
    defaultValue: 'https://sitio-prueba.local',
  );
  static const String siteUrl = serverUrl;
  static const String apiUrl = '$serverUrl/wp-json/chat-ia-mobile/v1';
  static const String apiVersion = '2.1.0';
  static const String appVersion = '2.1.0';
  static const String appBuild = '1';
  static const String flavor = 'client';
  static const bool isAdminApp = false;

  // Debug: en release debe ser false. Permite HTTP y certs autofirmados
  // en dev (httpOverrides en main.dart respeta este flag).
  // Build: --dart-define=DEBUG_MODE=false
  static const bool isDebug = bool.fromEnvironment(
    'DEBUG_MODE',
    defaultValue: true,
  );
  static const int httpTimeout = 30;

  // API key: leída desde --dart-define=API_KEY=...
  // Vacía en release fallará al primer hit del backend si éste la exige.
  static const String apiKey = String.fromEnvironment('API_KEY', defaultValue: '');

  /// Fingerprints SHA-256 de certificados SSL para certificate pinning
  ///
  /// IMPORTANTE: En producción, configura estos valores con los fingerprints
  /// reales de tu servidor. Obtén el fingerprint con:
  /// ```
  /// openssl s_client -connect tu-servidor.com:443 < /dev/null 2>/dev/null | \
  ///   openssl x509 -fingerprint -sha256 -noout
  /// ```
  ///
  /// Incluye al menos 2 certificados:
  /// - El certificado actual del servidor
  /// - Un certificado de respaldo para rotación sin downtime
  ///
  /// Como const list no acepta fromEnvironment, los fingerprints se
  /// inyectan vía --dart-define=PINNED_CERTS="hash1,hash2" y se parsean
  /// en `pinnedCertificates`.
  static const String _pinnedCertsRaw = String.fromEnvironment(
    'PINNED_CERTS',
    defaultValue: '',
  );

  /// Lista parseada de fingerprints. Vacía si no se inyectaron.
  static List<String> get pinnedCertificates {
    if (_pinnedCertsRaw.isEmpty) {
      return const <String>[];
    }
    return _pinnedCertsRaw
        .split(',')
        .map((s) => s.trim())
        .where((s) => s.isNotEmpty)
        .toList(growable: false);
  }

  static const String userId = '';
  static const String developerName = 'Flavor';
  static const String developerEmail = '';
  static const String developerPhone = '';
  static const String appStoreId = '';
  static const String themeMode = 'system';

  static const bool chatEnabled = true;
  static const bool reservationsEnabled = true;
  static const bool offlineTicketsEnabled = true;
  static const bool pushNotificationsEnabled = true;
  static const bool analyticsEnabled = false;

  static const List<String> enabledModules = [
    'eventos',
    'socios',
    'foros',
    'marketplace',
    'encuestas',
    'transparencia',
  ];

  static Future<bool> openClientApp() async {
    return FlavorUrlLauncher.openExternalRaw('flavor-client://open');
  }

  static Future<bool> openAdminApp() async {
    return FlavorUrlLauncher.openExternalRaw('flavor-admin://open');
  }
}
