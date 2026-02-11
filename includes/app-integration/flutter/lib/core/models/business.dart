/// Modelo para representar un negocio/comunidad
class Business {
  final String url;
  final String name;
  final String description;
  final String? logoUrl;
  final String? apiToken;
  final String region;
  final String category;
  final List<String> systems;
  final List<String> modules;
  final String language;
  final DateTime lastUpdated;

  Business({
    required this.url,
    required this.name,
    required this.description,
    this.logoUrl,
    this.apiToken,
    required this.region,
    required this.category,
    required this.systems,
    required this.modules,
    required this.language,
    required this.lastUpdated,
  });

  /// Crear desde JSON
  factory Business.fromJson(Map<String, dynamic> json) {
    return Business(
      url: json['url'] ?? '',
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      logoUrl: json['logo'] ?? json['logo_url'],
      apiToken: json['api_token'] ?? json['token'],
      region: json['region'] ?? '',
      category: json['category'] ?? '',
      systems: (json['systems'] as List<dynamic>?)
          ?.map((e) => e.toString())
          .toList() ?? [],
      modules: (json['modules'] as List<dynamic>?)
          ?.map((e) => e.toString())
          .toList() ?? [],
      language: json['language'] ?? 'es',
      lastUpdated: json['last_updated'] != null
          ? DateTime.parse(json['last_updated'])
          : DateTime.now(),
    );
  }

  /// Convertir a JSON
  Map<String, dynamic> toJson() {
    return {
      'url': url,
      'name': name,
      'description': description,
      'logo_url': logoUrl,
      'api_token': apiToken,
      'region': region,
      'category': category,
      'systems': systems,
      'modules': modules,
      'language': language,
      'last_updated': lastUpdated.toIso8601String(),
    };
  }

  /// Verifica si tiene un sistema específico
  bool hasSystem(String systemId) {
    return systems.contains(systemId);
  }

  /// Verifica si tiene un módulo específico
  bool hasModule(String moduleId) {
    return modules.contains(moduleId);
  }

  /// Verifica si tiene Flavor Chat IA
  bool get hasFlavorChatIA {
    return hasSystem('flavor-chat-ia');
  }

  /// Verifica si tiene wp-calendario-experiencias
  bool get hasCalendarioExperiencias {
    return hasSystem('calendario-experiencias');
  }

  /// Obtiene la lista de módulos formateada
  String get modulesDescription {
    final moduleNames = <String>[];

    if (hasModule('grupos_consumo')) moduleNames.add('Grupos de Consumo');
    if (hasModule('banco_tiempo')) moduleNames.add('Banco de Tiempo');
    if (hasModule('marketplace')) moduleNames.add('Marketplace');
    if (hasModule('woocommerce')) moduleNames.add('Tienda');

    if (moduleNames.isEmpty) return 'Sin módulos';
    return moduleNames.join(' · ');
  }

  /// Obtiene el nombre de la región formateado
  String get regionName {
    const regions = {
      'euskal_herria': 'Euskal Herria',
      'cataluna': 'Cataluña',
      'madrid': 'Madrid',
      'andalucia': 'Andalucía',
      'other_spain': 'España',
      'international': 'Internacional',
    };
    return regions[region] ?? region;
  }

  /// Obtiene el nombre de la categoría formateado
  String get categoryName {
    const categories = {
      'cooperativa': 'Cooperativa',
      'asociacion': 'Asociación',
      'comunidad': 'Comunidad',
      'grupo_consumo': 'Grupo de Consumo',
      'economia_social': 'Economía Social',
      'comercio_local': 'Comercio Local',
      'other': 'Otra',
    };
    return categories[category] ?? category;
  }

  @override
  String toString() {
    return 'Business{name: $name, url: $url, region: $region}';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Business && other.url == url;
  }

  @override
  int get hashCode => url.hashCode;
}
