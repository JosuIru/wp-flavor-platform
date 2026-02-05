/// Configuración completa de un sitio/negocio
class SiteConfig {
  final String baseUrl;
  final String siteName;
  final String siteDescription;
  final String? logoUrl;
  final String primaryColor;
  final String secondaryColor;
  final String accentColor;
  final List<Map<String, dynamic>> activeSystems;
  final List<Map<String, dynamic>> availableModules;
  final String language;
  final String timezone;

  SiteConfig({
    required this.baseUrl,
    required this.siteName,
    required this.siteDescription,
    this.logoUrl,
    required this.primaryColor,
    required this.secondaryColor,
    required this.accentColor,
    required this.activeSystems,
    required this.availableModules,
    required this.language,
    required this.timezone,
  });

  /// Crear desde JSON
  factory SiteConfig.fromJson(Map<String, dynamic> json) {
    return SiteConfig(
      baseUrl: json['base_url'] ?? '',
      siteName: json['site_name'] ?? '',
      siteDescription: json['site_description'] ?? '',
      logoUrl: json['logo_url'],
      primaryColor: json['primary_color'] ?? '#4CAF50',
      secondaryColor: json['secondary_color'] ?? '#8BC34A',
      accentColor: json['accent_color'] ?? '#FF9800',
      activeSystems: (json['active_systems'] as List<dynamic>?)
          ?.map((e) => e as Map<String, dynamic>)
          .toList() ?? [],
      availableModules: (json['available_modules'] as List<dynamic>?)
          ?.map((e) => e as Map<String, dynamic>)
          .toList() ?? [],
      language: json['language'] ?? 'es',
      timezone: json['timezone'] ?? 'Europe/Madrid',
    );
  }

  /// Convertir a JSON
  Map<String, dynamic> toJson() {
    return {
      'base_url': baseUrl,
      'site_name': siteName,
      'site_description': siteDescription,
      'logo_url': logoUrl,
      'primary_color': primaryColor,
      'secondary_color': secondaryColor,
      'accent_color': accentColor,
      'active_systems': activeSystems,
      'available_modules': availableModules,
      'language': language,
      'timezone': timezone,
    };
  }

  /// Verifica si tiene un sistema activo
  bool hasSystem(String systemId) {
    return activeSystems.any((s) => s['id'] == systemId);
  }

  /// Verifica si tiene un módulo disponible
  bool hasModule(String moduleId) {
    return availableModules.any((m) => m['id'] == moduleId);
  }

  @override
  String toString() {
    return 'SiteConfig{siteName: $siteName, baseUrl: $baseUrl}';
  }
}
