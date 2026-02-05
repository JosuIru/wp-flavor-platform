/// Información del sistema WordPress
class SystemInfo {
  final String wordpressUrl;
  final String siteName;
  final String siteDescription;
  final String appName;
  final String appDescription;
  final List<Map<String, dynamic>> activeSystems;
  final bool unifiedApi;
  final String apiVersion;
  final Map<String, dynamic> theme;
  final String timezone;
  final String language;

  SystemInfo({
    required this.wordpressUrl,
    required this.siteName,
    required this.siteDescription,
    required this.appName,
    required this.appDescription,
    required this.activeSystems,
    required this.unifiedApi,
    required this.apiVersion,
    required this.theme,
    required this.timezone,
    required this.language,
  });

  /// Crear desde JSON
  factory SystemInfo.fromJson(Map<String, dynamic> json) {
    return SystemInfo(
      wordpressUrl: json['wordpress_url'] ?? '',
      siteName: json['site_name'] ?? '',
      siteDescription: json['site_description'] ?? '',
      appName: json['app_name'] ?? json['site_name'] ?? '',
      appDescription: json['app_description'] ?? json['site_description'] ?? '',
      activeSystems: (json['active_systems'] as List<dynamic>?)
          ?.map((e) => e as Map<String, dynamic>)
          .toList() ?? [],
      unifiedApi: json['unified_api'] ?? false,
      apiVersion: json['api_version'] ?? '1.0',
      theme: json['theme'] as Map<String, dynamic>? ?? {},
      timezone: json['timezone'] ?? 'Europe/Madrid',
      language: json['language'] ?? 'es',
    );
  }

  /// Convertir a JSON
  Map<String, dynamic> toJson() {
    return {
      'wordpress_url': wordpressUrl,
      'site_name': siteName,
      'site_description': siteDescription,
      'app_name': appName,
      'app_description': appDescription,
      'active_systems': activeSystems,
      'unified_api': unifiedApi,
      'api_version': apiVersion,
      'theme': theme,
      'timezone': timezone,
      'language': language,
    };
  }

  /// Lista de módulos disponibles (extraídos de active_systems)
  List<Map<String, dynamic>> get availableModules {
    final List<Map<String, dynamic>> modules = [];

    for (final system in activeSystems) {
      if (system['modules'] != null) {
        modules.addAll(
          (system['modules'] as List).map((m) => m as Map<String, dynamic>),
        );
      }
    }

    return modules;
  }

  /// Verifica si tiene un sistema activo
  bool hasSystem(String systemId) {
    return activeSystems.any((s) => s['id'] == systemId);
  }

  /// Verifica si tiene Flavor Chat IA
  bool get hasFlavorChatIA => hasSystem('flavor-chat-ia');

  /// Verifica si tiene wp-calendario-experiencias
  bool get hasCalendarioExperiencias => hasSystem('calendario-experiencias');

  @override
  String toString() {
    return 'SystemInfo{appName: $appName, wordpressUrl: $wordpressUrl}';
  }
}
