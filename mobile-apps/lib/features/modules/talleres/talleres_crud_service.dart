import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Taller
class Taller {
  final String? id;
  final String titulo;
  final String descripcion;
  final String? categoria;
  final String? categoriaId;
  final String? imagen;
  final List<String> imagenes;
  final String? instructorId;
  final String? instructorNombre;
  final String? instructorAvatar;
  final DateTime fechaInicio;
  final DateTime? fechaFin;
  final String? horario;
  final int? duracionHoras;
  final int? numeroSesiones;
  final String? ubicacion;
  final Map<String, double>? coordenadas;
  final int? capacidad;
  final int inscritos;
  final double? precio;
  final bool gratuito;
  final String? nivel; // principiante, intermedio, avanzado
  final List<String> materiales;
  final String? requisitos;
  final bool inscripcionAbierta;
  final bool estaInscrito;
  final String? estado; // programado, en_curso, finalizado, cancelado
  final bool isPending;

  Taller({
    this.id,
    required this.titulo,
    required this.descripcion,
    this.categoria,
    this.categoriaId,
    this.imagen,
    this.imagenes = const [],
    this.instructorId,
    this.instructorNombre,
    this.instructorAvatar,
    required this.fechaInicio,
    this.fechaFin,
    this.horario,
    this.duracionHoras,
    this.numeroSesiones,
    this.ubicacion,
    this.coordenadas,
    this.capacidad,
    this.inscritos = 0,
    this.precio,
    this.gratuito = true,
    this.nivel,
    this.materiales = const [],
    this.requisitos,
    this.inscripcionAbierta = true,
    this.estaInscrito = false,
    this.estado,
    this.isPending = false,
  });

  factory Taller.fromJson(Map<String, dynamic> json) {
    return Taller(
      id: json['id']?.toString(),
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      categoria: json['categoria'] ?? json['category'],
      categoriaId: json['categoria_id']?.toString(),
      imagen: json['imagen'] ?? json['image'],
      imagenes: List<String>.from(json['imagenes'] ?? json['images'] ?? []),
      instructorId: json['instructor_id']?.toString(),
      instructorNombre: json['instructor_nombre'] ?? json['instructor_name'],
      instructorAvatar: json['instructor_avatar'],
      fechaInicio: DateTime.tryParse(json['fecha_inicio'] ?? json['start_date'] ?? '') ?? DateTime.now(),
      fechaFin: json['fecha_fin'] != null || json['end_date'] != null
          ? DateTime.tryParse(json['fecha_fin'] ?? json['end_date'])
          : null,
      horario: json['horario'] ?? json['schedule'],
      duracionHoras: json['duracion_horas'] ?? json['duration_hours'],
      numeroSesiones: json['numero_sesiones'] ?? json['session_count'],
      ubicacion: json['ubicacion'] ?? json['location'],
      coordenadas: json['coordenadas'] != null
          ? Map<String, double>.from(json['coordenadas'])
          : null,
      capacidad: json['capacidad'] ?? json['capacity'],
      inscritos: json['inscritos'] ?? json['enrolled'] ?? 0,
      precio: (json['precio'] ?? json['price'])?.toDouble(),
      gratuito: json['gratuito'] == true || json['free'] == true,
      nivel: json['nivel'] ?? json['level'],
      materiales: List<String>.from(json['materiales'] ?? json['materials'] ?? []),
      requisitos: json['requisitos'] ?? json['requirements'],
      inscripcionAbierta: json['inscripcion_abierta'] != false,
      estaInscrito: json['esta_inscrito'] == true || json['is_enrolled'] == true,
      estado: json['estado'] ?? json['status'],
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'titulo': titulo,
      'descripcion': descripcion,
      if (categoriaId != null) 'categoria_id': categoriaId,
      if (imagen != null) 'imagen': imagen,
      'imagenes': imagenes,
      'fecha_inicio': fechaInicio.toIso8601String(),
      if (fechaFin != null) 'fecha_fin': fechaFin!.toIso8601String(),
      if (horario != null) 'horario': horario,
      if (duracionHoras != null) 'duracion_horas': duracionHoras,
      if (numeroSesiones != null) 'numero_sesiones': numeroSesiones,
      if (ubicacion != null) 'ubicacion': ubicacion,
      if (coordenadas != null) 'coordenadas': coordenadas,
      if (capacidad != null) 'capacidad': capacidad,
      if (precio != null) 'precio': precio,
      'gratuito': gratuito,
      if (nivel != null) 'nivel': nivel,
      'materiales': materiales,
      if (requisitos != null) 'requisitos': requisitos,
    };
  }

  bool get hayPlazas => capacidad == null || inscritos < capacidad!;
  bool get haTerminado => fechaFin?.isBefore(DateTime.now()) ??
      fechaInicio.add(Duration(hours: duracionHoras ?? 2)).isBefore(DateTime.now());
}

/// Modelo de Inscripción a Taller
class InscripcionTaller {
  final String id;
  final String tallerId;
  final String usuarioId;
  final String? nombreUsuario;
  final DateTime fechaInscripcion;
  final String estado; // confirmada, pendiente, cancelada, lista_espera
  final int sesionesAsistidas;
  final bool completado;
  final String? certificadoUrl;

  InscripcionTaller({
    required this.id,
    required this.tallerId,
    required this.usuarioId,
    this.nombreUsuario,
    required this.fechaInscripcion,
    required this.estado,
    this.sesionesAsistidas = 0,
    this.completado = false,
    this.certificadoUrl,
  });

  factory InscripcionTaller.fromJson(Map<String, dynamic> json) {
    return InscripcionTaller(
      id: json['id']?.toString() ?? '',
      tallerId: json['taller_id']?.toString() ?? '',
      usuarioId: json['usuario_id']?.toString() ?? '',
      nombreUsuario: json['nombre_usuario'],
      fechaInscripcion: DateTime.tryParse(json['fecha_inscripcion'] ?? '') ?? DateTime.now(),
      estado: json['estado'] ?? 'pendiente',
      sesionesAsistidas: json['sesiones_asistidas'] ?? 0,
      completado: json['completado'] == true,
      certificadoUrl: json['certificado_url'],
    );
  }
}

/// Servicio CRUD para Talleres
class TalleresCrudService extends CrudService<Taller> {
  static TalleresCrudService? _instance;

  TalleresCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'talleres',
          endpoint: '/flavor-app/v2/talleres',
          fromJson: Taller.fromJson,
          toJson: (taller) => taller.toJson(),
        );

  factory TalleresCrudService({required ApiClient apiClient}) {
    _instance ??= TalleresCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener talleres próximos
  Future<List<Taller>> getProximos({int limit = 10}) async {
    final result = await getList(
      perPage: limit,
      filters: {
        'fecha_desde': DateTime.now().toIso8601String(),
        'estado': 'programado',
      },
      orderBy: 'fecha_inicio',
      order: 'asc',
    );
    return result.items;
  }

  /// Obtener talleres por categoría
  Future<PaginatedResult<Taller>> getByCategoria(
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

  /// Obtener talleres por nivel
  Future<PaginatedResult<Taller>> getByNivel(
    String nivel, {
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'nivel': nivel},
    );
  }

  /// Obtener mis talleres (donde estoy inscrito)
  Future<PaginatedResult<Taller>> getMisTalleres({
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'mis_talleres': true},
    );
  }

  /// Inscribirse a un taller
  Future<bool> inscribirse(String tallerId) async {
    try {
      await apiClient.post('/flavor-app/v2/talleres/$tallerId/inscribir');

      // Actualizar cache
      final taller = await getById(tallerId);
      if (taller != null) {
        final actualizado = Taller.fromJson({
          ...taller.toJson(),
          'esta_inscrito': true,
          'inscritos': taller.inscritos + 1,
        });
        cache[tallerId] = actualizado;
        notifyListeners(CrudEvent(type: CrudEventType.updated, item: actualizado));
      }

      return true;
    } catch (e) {
      debugPrint('[TalleresCrudService] Error inscribiéndose: $e');
      return false;
    }
  }

  /// Cancelar inscripción
  Future<bool> cancelarInscripcion(String tallerId) async {
    try {
      await apiClient.delete('/flavor-app/v2/talleres/$tallerId/inscripcion');

      // Actualizar cache
      final taller = await getById(tallerId);
      if (taller != null) {
        final actualizado = Taller.fromJson({
          ...taller.toJson(),
          'esta_inscrito': false,
          'inscritos': taller.inscritos > 0 ? taller.inscritos - 1 : 0,
        });
        cache[tallerId] = actualizado;
        notifyListeners(CrudEvent(type: CrudEventType.updated, item: actualizado));
      }

      return true;
    } catch (e) {
      debugPrint('[TalleresCrudService] Error cancelando inscripción: $e');
      return false;
    }
  }

  /// Obtener materiales del taller
  Future<List<Map<String, dynamic>>> getMateriales(String tallerId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/talleres/$tallerId/materiales',
      );
      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[TalleresCrudService] Error obteniendo materiales: $e');
      return [];
    }
  }

  /// Obtener sesiones del taller
  Future<List<Map<String, dynamic>>> getSesiones(String tallerId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/talleres/$tallerId/sesiones',
      );
      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[TalleresCrudService] Error obteniendo sesiones: $e');
      return [];
    }
  }

  /// Obtener progreso del usuario en el taller
  Future<Map<String, dynamic>?> getMiProgreso(String tallerId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/talleres/$tallerId/mi-progreso',
      );
      return response.data;
    } catch (e) {
      debugPrint('[TalleresCrudService] Error obteniendo progreso: $e');
      return null;
    }
  }

  /// Obtener categorías de talleres
  Future<List<Map<String, dynamic>>> getCategorias() async {
    try {
      final response = await apiClient.get('/flavor-app/v2/talleres/categorias');
      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[TalleresCrudService] Error obteniendo categorías: $e');
      return [];
    }
  }
}
