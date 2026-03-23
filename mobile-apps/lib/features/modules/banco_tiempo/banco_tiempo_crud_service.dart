import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Servicio del Banco de Tiempo
class ServicioBancoTiempo {
  final String? id;
  final String titulo;
  final String descripcion;
  final String tipo; // oferta, demanda
  final String? categoria;
  final String? categoriaId;
  final int? horasEstimadas;
  final String? usuarioId;
  final String? usuarioNombre;
  final String? usuarioAvatar;
  final double? valoracionUsuario;
  final String? ubicacion;
  final Map<String, double>? coordenadas;
  final List<String> imagenes;
  final String? disponibilidad;
  final bool activo;
  final DateTime? fechaPublicacion;
  final DateTime? fechaExpiracion;
  final int intercambiosCompletados;
  final bool isPending;

  ServicioBancoTiempo({
    this.id,
    required this.titulo,
    required this.descripcion,
    required this.tipo,
    this.categoria,
    this.categoriaId,
    this.horasEstimadas,
    this.usuarioId,
    this.usuarioNombre,
    this.usuarioAvatar,
    this.valoracionUsuario,
    this.ubicacion,
    this.coordenadas,
    this.imagenes = const [],
    this.disponibilidad,
    this.activo = true,
    this.fechaPublicacion,
    this.fechaExpiracion,
    this.intercambiosCompletados = 0,
    this.isPending = false,
  });

  factory ServicioBancoTiempo.fromJson(Map<String, dynamic> json) {
    return ServicioBancoTiempo(
      id: json['id']?.toString(),
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      tipo: json['tipo'] ?? json['type'] ?? 'oferta',
      categoria: json['categoria'] ?? json['category'],
      categoriaId: json['categoria_id']?.toString(),
      horasEstimadas: json['horas_estimadas'] ?? json['estimated_hours'],
      usuarioId: json['usuario_id']?.toString() ?? json['user_id']?.toString(),
      usuarioNombre: json['usuario_nombre'] ?? json['user_name'],
      usuarioAvatar: json['usuario_avatar'] ?? json['user_avatar'],
      valoracionUsuario: (json['valoracion_usuario'] ?? json['user_rating'])?.toDouble(),
      ubicacion: json['ubicacion'] ?? json['location'],
      coordenadas: json['coordenadas'] != null
          ? Map<String, double>.from(json['coordenadas'])
          : null,
      imagenes: List<String>.from(json['imagenes'] ?? json['images'] ?? []),
      disponibilidad: json['disponibilidad'] ?? json['availability'],
      activo: json['activo'] != false,
      fechaPublicacion: json['fecha_publicacion'] != null
          ? DateTime.tryParse(json['fecha_publicacion'])
          : null,
      fechaExpiracion: json['fecha_expiracion'] != null
          ? DateTime.tryParse(json['fecha_expiracion'])
          : null,
      intercambiosCompletados: json['intercambios_completados'] ?? 0,
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'titulo': titulo,
      'descripcion': descripcion,
      'tipo': tipo,
      if (categoriaId != null) 'categoria_id': categoriaId,
      if (horasEstimadas != null) 'horas_estimadas': horasEstimadas,
      if (ubicacion != null) 'ubicacion': ubicacion,
      if (coordenadas != null) 'coordenadas': coordenadas,
      'imagenes': imagenes,
      if (disponibilidad != null) 'disponibilidad': disponibilidad,
      'activo': activo,
    };
  }

  bool get esOferta => tipo == 'oferta';
  bool get esDemanda => tipo == 'demanda';

  ServicioBancoTiempo copyWith({
    String? id,
    String? titulo,
    String? descripcion,
    String? tipo,
    String? categoriaId,
    int? horasEstimadas,
    String? ubicacion,
    Map<String, double>? coordenadas,
    List<String>? imagenes,
    String? disponibilidad,
    bool? activo,
  }) {
    return ServicioBancoTiempo(
      id: id ?? this.id,
      titulo: titulo ?? this.titulo,
      descripcion: descripcion ?? this.descripcion,
      tipo: tipo ?? this.tipo,
      categoria: categoria,
      categoriaId: categoriaId ?? this.categoriaId,
      horasEstimadas: horasEstimadas ?? this.horasEstimadas,
      usuarioId: usuarioId,
      usuarioNombre: usuarioNombre,
      usuarioAvatar: usuarioAvatar,
      valoracionUsuario: valoracionUsuario,
      ubicacion: ubicacion ?? this.ubicacion,
      coordenadas: coordenadas ?? this.coordenadas,
      imagenes: imagenes ?? this.imagenes,
      disponibilidad: disponibilidad ?? this.disponibilidad,
      activo: activo ?? this.activo,
      fechaPublicacion: fechaPublicacion,
      fechaExpiracion: fechaExpiracion,
      intercambiosCompletados: intercambiosCompletados,
      isPending: isPending,
    );
  }
}

/// Modelo de Solicitud de Intercambio
class SolicitudIntercambio {
  final String id;
  final String servicioId;
  final String solicitanteId;
  final String? solicitanteNombre;
  final String? mensaje;
  final int? horasPropuestas;
  final String estado; // pendiente, aceptada, rechazada, completada
  final DateTime fechaSolicitud;
  final DateTime? fechaRespuesta;

  SolicitudIntercambio({
    required this.id,
    required this.servicioId,
    required this.solicitanteId,
    this.solicitanteNombre,
    this.mensaje,
    this.horasPropuestas,
    required this.estado,
    required this.fechaSolicitud,
    this.fechaRespuesta,
  });

  factory SolicitudIntercambio.fromJson(Map<String, dynamic> json) {
    return SolicitudIntercambio(
      id: json['id']?.toString() ?? '',
      servicioId: json['servicio_id']?.toString() ?? '',
      solicitanteId: json['solicitante_id']?.toString() ?? '',
      solicitanteNombre: json['solicitante_nombre'],
      mensaje: json['mensaje'],
      horasPropuestas: json['horas_propuestas'],
      estado: json['estado'] ?? 'pendiente',
      fechaSolicitud: DateTime.tryParse(json['fecha_solicitud'] ?? '') ?? DateTime.now(),
      fechaRespuesta: json['fecha_respuesta'] != null
          ? DateTime.tryParse(json['fecha_respuesta'])
          : null,
    );
  }
}

/// Balance de horas del usuario
class BalanceHoras {
  final int horasDisponibles;
  final int horasOfrecidas;
  final int horasRecibidas;
  final int horasPendientes;

  BalanceHoras({
    required this.horasDisponibles,
    required this.horasOfrecidas,
    required this.horasRecibidas,
    required this.horasPendientes,
  });

  factory BalanceHoras.fromJson(Map<String, dynamic> json) {
    return BalanceHoras(
      horasDisponibles: json['horas_disponibles'] ?? 0,
      horasOfrecidas: json['horas_ofrecidas'] ?? 0,
      horasRecibidas: json['horas_recibidas'] ?? 0,
      horasPendientes: json['horas_pendientes'] ?? 0,
    );
  }
}

/// Servicio CRUD para Banco de Tiempo
class BancoTiempoCrudService extends CrudService<ServicioBancoTiempo>
    with BancoTiempoCrudMixin<ServicioBancoTiempo> {
  static BancoTiempoCrudService? _instance;

  BancoTiempoCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'banco_tiempo',
          endpoint: '/flavor-app/v2/banco-tiempo/servicios',
          fromJson: ServicioBancoTiempo.fromJson,
          toJson: (servicio) => servicio.toJson(),
        );

  factory BancoTiempoCrudService({required ApiClient apiClient}) {
    _instance ??= BancoTiempoCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener ofertas
  Future<PaginatedResult<ServicioBancoTiempo>> getOfertas({
    int page = 1,
    int perPage = 20,
    String? categoriaId,
    String? search,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      search: search,
      filters: {
        'tipo': 'oferta',
        if (categoriaId != null) 'categoria_id': categoriaId,
      },
    );
  }

  /// Obtener demandas
  Future<PaginatedResult<ServicioBancoTiempo>> getDemandas({
    int page = 1,
    int perPage = 20,
    String? categoriaId,
    String? search,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      search: search,
      filters: {
        'tipo': 'demanda',
        if (categoriaId != null) 'categoria_id': categoriaId,
      },
    );
  }

  /// Obtener mis servicios
  Future<PaginatedResult<ServicioBancoTiempo>> getMisServicios({
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'mis_servicios': true},
    );
  }

  /// Obtener servicios cercanos
  Future<List<ServicioBancoTiempo>> getCercanos(
    double lat,
    double lng, {
    double radio = 10.0, // km
    int limit = 20,
  }) async {
    final result = await getList(
      perPage: limit,
      filters: {
        'lat': lat,
        'lng': lng,
        'radio': radio,
      },
    );
    return result.items;
  }

  /// Solicitar servicio
  @override
  Future<bool> solicitarServicio(String servicioId, String mensaje) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/banco-tiempo/servicios/$servicioId/solicitar',
        data: {
          'mensaje': mensaje,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error solicitando: $e');
      return false;
    }
  }

  /// Obtener solicitudes recibidas
  Future<List<SolicitudIntercambio>> getSolicitudesRecibidas() async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/banco-tiempo/solicitudes/recibidas',
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => SolicitudIntercambio.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error obteniendo solicitudes: $e');
      return [];
    }
  }

  /// Obtener solicitudes enviadas
  Future<List<SolicitudIntercambio>> getSolicitudesEnviadas() async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/banco-tiempo/solicitudes/enviadas',
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => SolicitudIntercambio.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error obteniendo solicitudes: $e');
      return [];
    }
  }

  /// Aceptar solicitud
  Future<bool> aceptarSolicitud(String solicitudId) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/banco-tiempo/solicitudes/$solicitudId/aceptar',
      );
      return true;
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error aceptando: $e');
      return false;
    }
  }

  /// Rechazar solicitud
  Future<bool> rechazarSolicitud(String solicitudId, {String? motivo}) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/banco-tiempo/solicitudes/$solicitudId/rechazar',
        data: {
          if (motivo != null) 'motivo': motivo,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error rechazando: $e');
      return false;
    }
  }

  /// Completar intercambio
  Future<bool> completarIntercambio(String solicitudId, int horasReales) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/banco-tiempo/solicitudes/$solicitudId/completar',
        data: {
          'horas_reales': horasReales,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error completando: $e');
      return false;
    }
  }

  /// Valorar intercambio
  Future<bool> valorarIntercambio(
    String solicitudId,
    int valoracion, // 1-5
    String? comentario,
  ) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/banco-tiempo/solicitudes/$solicitudId/valorar',
        data: {
          'valoracion': valoracion,
          if (comentario != null) 'comentario': comentario,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error valorando: $e');
      return false;
    }
  }

  /// Obtener balance de horas
  Future<BalanceHoras?> getBalance() async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/banco-tiempo/balance',
      );
      return BalanceHoras.fromJson(response.data ?? {});
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error obteniendo balance: $e');
      return null;
    }
  }

  /// Obtener categorías
  Future<List<Map<String, dynamic>>> getCategorias() async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/banco-tiempo/categorias',
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[BancoTiempoCrudService] Error obteniendo categorías: $e');
      return [];
    }
  }
}
