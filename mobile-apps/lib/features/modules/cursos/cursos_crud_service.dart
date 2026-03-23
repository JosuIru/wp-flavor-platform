import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Curso
class Curso {
  final String? id;
  final String titulo;
  final String descripcion;
  final String? resumen;
  final String? imagen;
  final String? videoIntro;
  final String? categoria;
  final String? categoriaId;
  final String? instructorId;
  final String? instructorNombre;
  final String? instructorAvatar;
  final String? instructorBio;
  final int duracionMinutos;
  final int numeroLecciones;
  final int numeroModulos;
  final String? nivel; // principiante, intermedio, avanzado
  final List<String> requisitos;
  final List<String> objetivos;
  final double? precio;
  final bool gratuito;
  final double? valoracion;
  final int numeroValoraciones;
  final int matriculados;
  final bool estaMatriculado;
  final double? progreso; // 0.0 a 1.0
  final int leccionesCompletadas;
  final bool certificadoDisponible;
  final String? certificadoUrl;
  final DateTime? fechaPublicacion;
  final DateTime? ultimaActualizacion;
  final bool isPending;

  Curso({
    this.id,
    required this.titulo,
    required this.descripcion,
    this.resumen,
    this.imagen,
    this.videoIntro,
    this.categoria,
    this.categoriaId,
    this.instructorId,
    this.instructorNombre,
    this.instructorAvatar,
    this.instructorBio,
    this.duracionMinutos = 0,
    this.numeroLecciones = 0,
    this.numeroModulos = 0,
    this.nivel,
    this.requisitos = const [],
    this.objetivos = const [],
    this.precio,
    this.gratuito = true,
    this.valoracion,
    this.numeroValoraciones = 0,
    this.matriculados = 0,
    this.estaMatriculado = false,
    this.progreso,
    this.leccionesCompletadas = 0,
    this.certificadoDisponible = false,
    this.certificadoUrl,
    this.fechaPublicacion,
    this.ultimaActualizacion,
    this.isPending = false,
  });

  factory Curso.fromJson(Map<String, dynamic> json) {
    return Curso(
      id: json['id']?.toString(),
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      resumen: json['resumen'] ?? json['summary'],
      imagen: json['imagen'] ?? json['image'],
      videoIntro: json['video_intro'] ?? json['intro_video'],
      categoria: json['categoria'] ?? json['category'],
      categoriaId: json['categoria_id']?.toString(),
      instructorId: json['instructor_id']?.toString(),
      instructorNombre: json['instructor_nombre'] ?? json['instructor_name'],
      instructorAvatar: json['instructor_avatar'],
      instructorBio: json['instructor_bio'],
      duracionMinutos: json['duracion_minutos'] ?? json['duration_minutes'] ?? 0,
      numeroLecciones: json['numero_lecciones'] ?? json['lesson_count'] ?? 0,
      numeroModulos: json['numero_modulos'] ?? json['module_count'] ?? 0,
      nivel: json['nivel'] ?? json['level'],
      requisitos: List<String>.from(json['requisitos'] ?? json['requirements'] ?? []),
      objetivos: List<String>.from(json['objetivos'] ?? json['objectives'] ?? []),
      precio: (json['precio'] ?? json['price'])?.toDouble(),
      gratuito: json['gratuito'] == true || json['free'] == true,
      valoracion: (json['valoracion'] ?? json['rating'])?.toDouble(),
      numeroValoraciones: json['numero_valoraciones'] ?? json['rating_count'] ?? 0,
      matriculados: json['matriculados'] ?? json['enrolled'] ?? 0,
      estaMatriculado: json['esta_matriculado'] == true || json['is_enrolled'] == true,
      progreso: (json['progreso'] ?? json['progress'])?.toDouble(),
      leccionesCompletadas: json['lecciones_completadas'] ?? json['completed_lessons'] ?? 0,
      certificadoDisponible: json['certificado_disponible'] == true,
      certificadoUrl: json['certificado_url'],
      fechaPublicacion: json['fecha_publicacion'] != null
          ? DateTime.tryParse(json['fecha_publicacion'])
          : null,
      ultimaActualizacion: json['ultima_actualizacion'] != null
          ? DateTime.tryParse(json['ultima_actualizacion'])
          : null,
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'titulo': titulo,
      'descripcion': descripcion,
      if (resumen != null) 'resumen': resumen,
      if (imagen != null) 'imagen': imagen,
      if (videoIntro != null) 'video_intro': videoIntro,
      if (categoriaId != null) 'categoria_id': categoriaId,
      'duracion_minutos': duracionMinutos,
      if (nivel != null) 'nivel': nivel,
      'requisitos': requisitos,
      'objetivos': objetivos,
      if (precio != null) 'precio': precio,
      'gratuito': gratuito,
    };
  }

  /// Duración formateada
  String get duracionFormateada {
    final horas = duracionMinutos ~/ 60;
    final minutos = duracionMinutos % 60;
    if (horas > 0) {
      return '${horas}h ${minutos}m';
    }
    return '${minutos}m';
  }

  /// ¿Está completo?
  bool get estaCompleto => progreso != null && progreso! >= 1.0;
}

/// Modelo de Módulo del curso
class ModuloCurso {
  final String id;
  final String titulo;
  final String? descripcion;
  final int orden;
  final List<LeccionCurso> lecciones;
  final int duracionMinutos;
  final bool bloqueado;
  final double? progreso;

  ModuloCurso({
    required this.id,
    required this.titulo,
    this.descripcion,
    required this.orden,
    this.lecciones = const [],
    this.duracionMinutos = 0,
    this.bloqueado = false,
    this.progreso,
  });

  factory ModuloCurso.fromJson(Map<String, dynamic> json) {
    return ModuloCurso(
      id: json['id']?.toString() ?? '',
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'],
      orden: json['orden'] ?? json['order'] ?? 0,
      lecciones: (json['lecciones'] ?? json['lessons'] ?? [])
          .map<LeccionCurso>((l) => LeccionCurso.fromJson(l))
          .toList(),
      duracionMinutos: json['duracion_minutos'] ?? 0,
      bloqueado: json['bloqueado'] == true,
      progreso: json['progreso']?.toDouble(),
    );
  }
}

/// Modelo de Lección
class LeccionCurso {
  final String id;
  final String titulo;
  final String? descripcion;
  final String tipo; // video, texto, quiz, ejercicio
  final int orden;
  final int duracionMinutos;
  final String? contenidoUrl;
  final bool completada;
  final bool bloqueada;
  final Map<String, dynamic>? recursos;

  LeccionCurso({
    required this.id,
    required this.titulo,
    this.descripcion,
    required this.tipo,
    required this.orden,
    this.duracionMinutos = 0,
    this.contenidoUrl,
    this.completada = false,
    this.bloqueada = false,
    this.recursos,
  });

  factory LeccionCurso.fromJson(Map<String, dynamic> json) {
    return LeccionCurso(
      id: json['id']?.toString() ?? '',
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'],
      tipo: json['tipo'] ?? json['type'] ?? 'video',
      orden: json['orden'] ?? json['order'] ?? 0,
      duracionMinutos: json['duracion_minutos'] ?? 0,
      contenidoUrl: json['contenido_url'] ?? json['content_url'],
      completada: json['completada'] == true || json['completed'] == true,
      bloqueada: json['bloqueada'] == true || json['locked'] == true,
      recursos: json['recursos'] ?? json['resources'],
    );
  }
}

/// Servicio CRUD para Cursos
class CursosCrudService extends CrudService<Curso> {
  static CursosCrudService? _instance;

  CursosCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'cursos',
          endpoint: '/flavor-app/v2/cursos',
          fromJson: Curso.fromJson,
          toJson: (curso) => curso.toJson(),
        );

  factory CursosCrudService({required ApiClient apiClient}) {
    _instance ??= CursosCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener cursos destacados
  Future<List<Curso>> getDestacados({int limit = 10}) async {
    final result = await getList(
      perPage: limit,
      filters: {'destacado': true},
      orderBy: 'valoracion',
      order: 'desc',
    );
    return result.items;
  }

  /// Obtener cursos por categoría
  Future<PaginatedResult<Curso>> getByCategoria(
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

  /// Obtener cursos por nivel
  Future<PaginatedResult<Curso>> getByNivel(
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

  /// Obtener mis cursos (matriculados)
  Future<PaginatedResult<Curso>> getMisCursos({
    int page = 1,
    int perPage = 20,
    bool soloEnProgreso = false,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {
        'mis_cursos': true,
        if (soloEnProgreso) 'en_progreso': true,
      },
    );
  }

  /// Matricularse en un curso
  Future<bool> matricularse(String cursoId) async {
    try {
      await apiClient.post('/flavor-app/v2/cursos/$cursoId/matricular');

      // Actualizar cache
      final curso = await getById(cursoId);
      if (curso != null) {
        final actualizado = Curso.fromJson({
          ...curso.toJson(),
          'esta_matriculado': true,
          'matriculados': curso.matriculados + 1,
          'progreso': 0.0,
        });
        cache[cursoId] = actualizado;
        notifyListeners(CrudEvent(type: CrudEventType.updated, item: actualizado));
      }

      return true;
    } catch (e) {
      debugPrint('[CursosCrudService] Error matriculándose: $e');
      return false;
    }
  }

  /// Obtener contenido del curso (módulos y lecciones)
  Future<List<ModuloCurso>> getContenido(String cursoId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/cursos/$cursoId/contenido',
      );

      final List<dynamic> data = response.data?['modulos'] ?? response.data;
      return data.map((json) => ModuloCurso.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[CursosCrudService] Error obteniendo contenido: $e');
      return [];
    }
  }

  /// Obtener detalle de una lección
  Future<Map<String, dynamic>?> getLeccion(String cursoId, String leccionId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/cursos/$cursoId/lecciones/$leccionId',
      );
      return response.data;
    } catch (e) {
      debugPrint('[CursosCrudService] Error obteniendo lección: $e');
      return null;
    }
  }

  /// Marcar lección como completada
  Future<bool> completarLeccion(String cursoId, String leccionId) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/cursos/$cursoId/lecciones/$leccionId/completar',
      );

      // Refrescar curso para actualizar progreso
      await getById(cursoId, forceRefresh: true);

      return true;
    } catch (e) {
      debugPrint('[CursosCrudService] Error completando lección: $e');
      return false;
    }
  }

  /// Guardar progreso de video
  Future<bool> guardarProgresoVideo(
    String cursoId,
    String leccionId,
    int segundosVistos,
  ) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/cursos/$cursoId/lecciones/$leccionId/progreso',
        data: {'segundos_vistos': segundosVistos},
      );
      return true;
    } catch (e) {
      debugPrint('[CursosCrudService] Error guardando progreso: $e');
      return false;
    }
  }

  /// Enviar respuesta de quiz
  Future<Map<String, dynamic>?> enviarRespuestaQuiz(
    String cursoId,
    String leccionId,
    Map<String, dynamic> respuestas,
  ) async {
    try {
      final response = await apiClient.post(
        '/flavor-app/v2/cursos/$cursoId/lecciones/$leccionId/quiz',
        data: {'respuestas': respuestas},
      );
      return response.data;
    } catch (e) {
      debugPrint('[CursosCrudService] Error enviando quiz: $e');
      return null;
    }
  }

  /// Valorar curso
  Future<bool> valorar(String cursoId, int valoracion, String? comentario) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/cursos/$cursoId/valorar',
        data: {
          'valoracion': valoracion,
          if (comentario != null) 'comentario': comentario,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[CursosCrudService] Error valorando: $e');
      return false;
    }
  }

  /// Obtener certificado
  Future<String?> getCertificado(String cursoId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/cursos/$cursoId/certificado',
      );
      return response.data?['url'];
    } catch (e) {
      debugPrint('[CursosCrudService] Error obteniendo certificado: $e');
      return null;
    }
  }

  /// Obtener categorías de cursos
  Future<List<Map<String, dynamic>>> getCategorias() async {
    try {
      final response = await apiClient.get('/flavor-app/v2/cursos/categorias');
      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[CursosCrudService] Error obteniendo categorías: $e');
      return [];
    }
  }
}
