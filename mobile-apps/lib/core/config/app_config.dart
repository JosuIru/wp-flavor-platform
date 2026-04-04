import '../utils/flavor_url_launcher.dart';

// Configuración generada automáticamente
// Generado: 2026-03-18T01:30:29+00:00

class AppConfig {
  static const String appName = 'Komunitatea';
  static const String clientAppName = appName;
  static const String adminAppName = '$appName Admin';
  static const String appId = 'com.komunitatea.app';
  static const String packageName = appId;
  static const String serverUrl = 'http://sitio-prueba.local';
  static const String siteUrl = serverUrl;
  static const String apiUrl = '$serverUrl/wp-json/chat-ia-mobile/v1';
  static const String apiVersion = '2.1.0';
  static const String appVersion = '2.1.0';
  static const String appBuild = '1';
  static const String flavor = 'client';
  static const bool isAdminApp = false;
  static const bool isDebug = false;
  static const int httpTimeout = 30;
  static const String apiKey = '';
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
