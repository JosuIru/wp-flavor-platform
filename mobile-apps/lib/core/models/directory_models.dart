class DirectoryNode {
  final int id;
  final String nombre;
  final String slug;
  final String descripcionCorta;
  final String siteUrl;
  final String logoUrl;
  final String tipoEntidad;
  final String sector;
  final String nivelConsciencia;
  final String ciudad;
  final String pais;
  final double? latitud;
  final double? longitud;
  final bool verificado;
  final double? distanciaKm;

  DirectoryNode({
    required this.id,
    required this.nombre,
    required this.slug,
    required this.descripcionCorta,
    required this.siteUrl,
    required this.logoUrl,
    required this.tipoEntidad,
    required this.sector,
    required this.nivelConsciencia,
    required this.ciudad,
    required this.pais,
    required this.latitud,
    required this.longitud,
    required this.verificado,
    this.distanciaKm,
  });

  factory DirectoryNode.fromJson(Map<String, dynamic> json) {
    return DirectoryNode(
      id: json['id'] as int? ?? 0,
      nombre: (json['nombre'] as String? ?? '').trim(),
      slug: (json['slug'] as String? ?? '').trim(),
      descripcionCorta: (json['descripcion_corta'] as String? ?? '').trim(),
      siteUrl: (json['site_url'] as String? ?? '').trim(),
      logoUrl: (json['logo_url'] as String? ?? '').trim(),
      tipoEntidad: (json['tipo_entidad'] as String? ?? '').trim(),
      sector: (json['sector'] as String? ?? '').trim(),
      nivelConsciencia: (json['nivel_consciencia'] as String? ?? '').trim(),
      ciudad: (json['ciudad'] as String? ?? '').trim(),
      pais: (json['pais'] as String? ?? '').trim(),
      latitud: (json['latitud'] ?? json['lat']) != null
          ? double.tryParse(json['latitud']?.toString() ?? json['lat'].toString())
          : null,
      longitud: (json['longitud'] ?? json['lng']) != null
          ? double.tryParse(json['longitud']?.toString() ?? json['lng'].toString())
          : null,
      verificado: json['verificado'] == true,
      distanciaKm: json['distancia_km'] != null ? double.tryParse(json['distancia_km'].toString()) : null,
    );
  }
}

class DirectoryResponse {
  final List<DirectoryNode> nodos;
  final int total;
  final int pagina;
  final int paginas;

  DirectoryResponse({
    required this.nodos,
    required this.total,
    required this.pagina,
    required this.paginas,
  });

  factory DirectoryResponse.fromJson(Map<String, dynamic> json) {
    return DirectoryResponse(
      nodos: (json['nodos'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(DirectoryNode.fromJson)
          .toList(),
      total: json['total'] as int? ?? 0,
      pagina: json['pagina'] as int? ?? 1,
      paginas: json['paginas'] as int? ?? 1,
    );
  }
}

class DirectoryNearbyResponse {
  final List<DirectoryNode> nodos;
  final double? radioKm;

  DirectoryNearbyResponse({
    required this.nodos,
    required this.radioKm,
  });

  factory DirectoryNearbyResponse.fromJson(Map<String, dynamic> json) {
    return DirectoryNearbyResponse(
      nodos: (json['nodos'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(DirectoryNode.fromJson)
          .toList(),
      radioKm: json['radio_km'] != null ? double.tryParse(json['radio_km'].toString()) : null,
    );
  }
}
