import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Anuncio del Marketplace
class Anuncio {
  final String? id;
  final String titulo;
  final String descripcion;
  final double precio;
  final String? categoria;
  final String? categoriaId;
  final List<String> imagenes;
  final String? estado; // nuevo, usado, como_nuevo
  final String? tipoAnuncio; // venta, intercambio, regalo
  final String? ubicacion;
  final Map<String, dynamic>? contacto;
  final String? vendedorId;
  final String? vendedorNombre;
  final DateTime? fechaPublicacion;
  final DateTime? fechaExpiracion;
  final bool destacado;
  final bool activo;
  final int visitas;
  final bool isPending;

  Anuncio({
    this.id,
    required this.titulo,
    required this.descripcion,
    required this.precio,
    this.categoria,
    this.categoriaId,
    this.imagenes = const [],
    this.estado,
    this.tipoAnuncio,
    this.ubicacion,
    this.contacto,
    this.vendedorId,
    this.vendedorNombre,
    this.fechaPublicacion,
    this.fechaExpiracion,
    this.destacado = false,
    this.activo = true,
    this.visitas = 0,
    this.isPending = false,
  });

  factory Anuncio.fromJson(Map<String, dynamic> json) {
    return Anuncio(
      id: json['id']?.toString(),
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      precio: (json['precio'] ?? json['price'] ?? 0).toDouble(),
      categoria: json['categoria'] ?? json['category'],
      categoriaId: json['categoria_id']?.toString(),
      imagenes: List<String>.from(json['imagenes'] ?? json['images'] ?? []),
      estado: json['estado'] ?? json['condition'],
      tipoAnuncio: json['tipo_anuncio'] ?? json['type'],
      ubicacion: json['ubicacion'] ?? json['location'],
      contacto: json['contacto'] ?? json['contact'],
      vendedorId: json['vendedor_id']?.toString() ?? json['seller_id']?.toString(),
      vendedorNombre: json['vendedor_nombre'] ?? json['seller_name'],
      fechaPublicacion: json['fecha_publicacion'] != null
          ? DateTime.tryParse(json['fecha_publicacion'])
          : null,
      fechaExpiracion: json['fecha_expiracion'] != null
          ? DateTime.tryParse(json['fecha_expiracion'])
          : null,
      destacado: json['destacado'] == true || json['featured'] == true,
      activo: json['activo'] != false && json['status'] != 'inactive',
      visitas: json['visitas'] ?? json['views'] ?? 0,
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'titulo': titulo,
      'descripcion': descripcion,
      'precio': precio,
      if (categoriaId != null) 'categoria_id': categoriaId,
      'imagenes': imagenes,
      if (estado != null) 'estado': estado,
      if (tipoAnuncio != null) 'tipo_anuncio': tipoAnuncio,
      if (ubicacion != null) 'ubicacion': ubicacion,
      if (contacto != null) 'contacto': contacto,
      'destacado': destacado,
      'activo': activo,
    };
  }

  Anuncio copyWith({
    String? id,
    String? titulo,
    String? descripcion,
    double? precio,
    String? categoria,
    String? categoriaId,
    List<String>? imagenes,
    String? estado,
    String? tipoAnuncio,
    String? ubicacion,
    Map<String, dynamic>? contacto,
    bool? destacado,
    bool? activo,
  }) {
    return Anuncio(
      id: id ?? this.id,
      titulo: titulo ?? this.titulo,
      descripcion: descripcion ?? this.descripcion,
      precio: precio ?? this.precio,
      categoria: categoria ?? this.categoria,
      categoriaId: categoriaId ?? this.categoriaId,
      imagenes: imagenes ?? this.imagenes,
      estado: estado ?? this.estado,
      tipoAnuncio: tipoAnuncio ?? this.tipoAnuncio,
      ubicacion: ubicacion ?? this.ubicacion,
      contacto: contacto ?? this.contacto,
      vendedorId: vendedorId,
      vendedorNombre: vendedorNombre,
      fechaPublicacion: fechaPublicacion,
      fechaExpiracion: fechaExpiracion,
      destacado: destacado ?? this.destacado,
      activo: activo ?? this.activo,
      visitas: visitas,
      isPending: isPending,
    );
  }
}

/// Modelo de Categoría
class CategoriaMarketplace {
  final String id;
  final String nombre;
  final String? descripcion;
  final String? icono;
  final int anunciosCount;
  final String? parentId;

  CategoriaMarketplace({
    required this.id,
    required this.nombre,
    this.descripcion,
    this.icono,
    this.anunciosCount = 0,
    this.parentId,
  });

  factory CategoriaMarketplace.fromJson(Map<String, dynamic> json) {
    return CategoriaMarketplace(
      id: json['id']?.toString() ?? '',
      nombre: json['nombre'] ?? json['name'] ?? '',
      descripcion: json['descripcion'] ?? json['description'],
      icono: json['icono'] ?? json['icon'],
      anunciosCount: json['anuncios_count'] ?? json['count'] ?? 0,
      parentId: json['parent_id']?.toString(),
    );
  }
}

/// Servicio CRUD para Marketplace
class MarketplaceCrudService extends CrudService<Anuncio> with MarketplaceCrudMixin<Anuncio> {
  static MarketplaceCrudService? _instance;

  MarketplaceCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'marketplace',
          endpoint: '/flavor-app/v2/marketplace/anuncios',
          fromJson: Anuncio.fromJson,
          toJson: (anuncio) => anuncio.toJson(),
        );

  factory MarketplaceCrudService({required ApiClient apiClient}) {
    _instance ??= MarketplaceCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener anuncios por categoría
  Future<PaginatedResult<Anuncio>> getByCategoria(
    String categoriaId, {
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'categoria_id': categoriaId},
    );
  }

  /// Obtener mis anuncios
  Future<PaginatedResult<Anuncio>> getMisAnuncios({
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'mis_anuncios': true},
    );
  }

  /// Obtener anuncios destacados
  Future<List<Anuncio>> getDestacados({int limit = 10}) async {
    final result = await getList(
      perPage: limit,
      filters: {'destacado': true},
      orderBy: 'fecha_publicacion',
      order: 'desc',
    );
    return result.items;
  }

  /// Obtener categorías
  Future<List<CategoriaMarketplace>> getCategorias() async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/marketplace/categorias',
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => CategoriaMarketplace.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[MarketplaceCrudService] Error getting categorias: $e');
      return [];
    }
  }

  /// Contactar vendedor
  Future<bool> contactarVendedor(String anuncioId, String mensaje) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/marketplace/anuncios/$anuncioId/contactar',
        data: {'mensaje': mensaje},
      );
      return true;
    } catch (e) {
      debugPrint('[MarketplaceCrudService] Error contactando vendedor: $e');
      return false;
    }
  }

  /// Reportar anuncio
  Future<bool> reportarAnuncio(String anuncioId, String motivo) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/marketplace/anuncios/$anuncioId/reportar',
        data: {'motivo': motivo},
      );
      return true;
    } catch (e) {
      debugPrint('[MarketplaceCrudService] Error reportando: $e');
      return false;
    }
  }

  /// Subir imagen
  Future<String?> subirImagen(String filePath) async {
    try {
      final response = await apiClient.uploadSingleFile(
        filePath,
        context: 'marketplace',
        fieldName: 'image',
      );
      return response.data?['url']?.toString();
    } catch (e) {
      debugPrint('[MarketplaceCrudService] Error subiendo imagen: $e');
      return null;
    }
  }
}
