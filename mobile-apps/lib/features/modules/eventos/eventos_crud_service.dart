import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Evento
class Evento {
  final String? id;
  final String titulo;
  final String descripcion;
  final DateTime fechaInicio;
  final DateTime? fechaFin;
  final String? ubicacion;
  final Map<String, double>? coordenadas;
  final String? imagen;
  final List<String> imagenes;
  final String? categoria;
  final String? categoriaId;
  final String? organizadorId;
  final String? organizadorNombre;
  final int? capacidad;
  final int inscritos;
  final double? precio;
  final bool gratuito;
  final bool requiereInscripcion;
  final bool inscripcionAbierta;
  final bool estaInscrito;
  final bool destacado;
  final String? estado; // programado, en_curso, finalizado, cancelado
  final Map<String, dynamic>? metadatos;
  final bool isPending;

  Evento({
    this.id,
    required this.titulo,
    required this.descripcion,
    required this.fechaInicio,
    this.fechaFin,
    this.ubicacion,
    this.coordenadas,
    this.imagen,
    this.imagenes = const [],
    this.categoria,
    this.categoriaId,
    this.organizadorId,
    this.organizadorNombre,
    this.capacidad,
    this.inscritos = 0,
    this.precio,
    this.gratuito = true,
    this.requiereInscripcion = false,
    this.inscripcionAbierta = true,
    this.estaInscrito = false,
    this.destacado = false,
    this.estado,
    this.metadatos,
    this.isPending = false,
  });

  factory Evento.fromJson(Map<String, dynamic> json) {
    return Evento(
      id: json['id']?.toString(),
      titulo: json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      fechaInicio: DateTime.tryParse(json['fecha_inicio'] ?? json['start_date'] ?? '') ?? DateTime.now(),
      fechaFin: json['fecha_fin'] != null || json['end_date'] != null
          ? DateTime.tryParse(json['fecha_fin'] ?? json['end_date'])
          : null,
      ubicacion: json['ubicacion'] ?? json['location'],
      coordenadas: json['coordenadas'] != null
          ? Map<String, double>.from(json['coordenadas'])
          : null,
      imagen: json['imagen'] ?? json['image'],
      imagenes: List<String>.from(json['imagenes'] ?? json['images'] ?? []),
      categoria: json['categoria'] ?? json['category'],
      categoriaId: json['categoria_id']?.toString(),
      organizadorId: json['organizador_id']?.toString() ?? json['organizer_id']?.toString(),
      organizadorNombre: json['organizador_nombre'] ?? json['organizer_name'],
      capacidad: json['capacidad'] ?? json['capacity'],
      inscritos: json['inscritos'] ?? json['attendees'] ?? 0,
      precio: (json['precio'] ?? json['price'])?.toDouble(),
      gratuito: json['gratuito'] == true || json['free'] == true,
      requiereInscripcion: json['requiere_inscripcion'] == true || json['requires_registration'] == true,
      inscripcionAbierta: json['inscripcion_abierta'] != false,
      estaInscrito: json['esta_inscrito'] == true || json['is_registered'] == true,
      destacado: json['destacado'] == true || json['featured'] == true,
      estado: json['estado'] ?? json['status'],
      metadatos: json['metadatos'] ?? json['metadata'],
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'titulo': titulo,
      'descripcion': descripcion,
      'fecha_inicio': fechaInicio.toIso8601String(),
      if (fechaFin != null) 'fecha_fin': fechaFin!.toIso8601String(),
      if (ubicacion != null) 'ubicacion': ubicacion,
      if (coordenadas != null) 'coordenadas': coordenadas,
      if (imagen != null) 'imagen': imagen,
      'imagenes': imagenes,
      if (categoriaId != null) 'categoria_id': categoriaId,
      if (capacidad != null) 'capacidad': capacidad,
      if (precio != null) 'precio': precio,
      'gratuito': gratuito,
      'requiere_inscripcion': requiereInscripcion,
      'destacado': destacado,
      if (metadatos != null) 'metadatos': metadatos,
    };
  }

  /// ¿El evento ya pasó?
  bool get haTerminado {
    final endDate = fechaFin ?? fechaInicio;
    return endDate.isBefore(DateTime.now());
  }

  /// ¿El evento está en curso?
  bool get enCurso {
    final now = DateTime.now();
    final end = fechaFin ?? fechaInicio.add(const Duration(hours: 2));
    return fechaInicio.isBefore(now) && end.isAfter(now);
  }

  /// ¿Hay plazas disponibles?
  bool get hayPlazas {
    if (capacidad == null) return true;
    return inscritos < capacidad!;
  }

  Evento copyWith({
    String? id,
    String? titulo,
    String? descripcion,
    DateTime? fechaInicio,
    DateTime? fechaFin,
    String? ubicacion,
    Map<String, double>? coordenadas,
    String? imagen,
    List<String>? imagenes,
    String? categoriaId,
    int? capacidad,
    double? precio,
    bool? gratuito,
    bool? requiereInscripcion,
    bool? destacado,
    Map<String, dynamic>? metadatos,
  }) {
    return Evento(
      id: id ?? this.id,
      titulo: titulo ?? this.titulo,
      descripcion: descripcion ?? this.descripcion,
      fechaInicio: fechaInicio ?? this.fechaInicio,
      fechaFin: fechaFin ?? this.fechaFin,
      ubicacion: ubicacion ?? this.ubicacion,
      coordenadas: coordenadas ?? this.coordenadas,
      imagen: imagen ?? this.imagen,
      imagenes: imagenes ?? this.imagenes,
      categoria: categoria,
      categoriaId: categoriaId ?? this.categoriaId,
      organizadorId: organizadorId,
      organizadorNombre: organizadorNombre,
      capacidad: capacidad ?? this.capacidad,
      inscritos: inscritos,
      precio: precio ?? this.precio,
      gratuito: gratuito ?? this.gratuito,
      requiereInscripcion: requiereInscripcion ?? this.requiereInscripcion,
      inscripcionAbierta: inscripcionAbierta,
      estaInscrito: estaInscrito,
      destacado: destacado ?? this.destacado,
      estado: estado,
      metadatos: metadatos ?? this.metadatos,
      isPending: isPending,
    );
  }
}

/// Modelo de Inscripción
class InscripcionEvento {
  final String id;
  final String eventoId;
  final String usuarioId;
  final String? nombreUsuario;
  final DateTime fechaInscripcion;
  final String estado; // confirmada, pendiente, cancelada
  final bool asistio;

  InscripcionEvento({
    required this.id,
    required this.eventoId,
    required this.usuarioId,
    this.nombreUsuario,
    required this.fechaInscripcion,
    required this.estado,
    this.asistio = false,
  });

  factory InscripcionEvento.fromJson(Map<String, dynamic> json) {
    return InscripcionEvento(
      id: json['id']?.toString() ?? '',
      eventoId: json['evento_id']?.toString() ?? '',
      usuarioId: json['usuario_id']?.toString() ?? '',
      nombreUsuario: json['nombre_usuario'],
      fechaInscripcion: DateTime.tryParse(json['fecha_inscripcion'] ?? '') ?? DateTime.now(),
      estado: json['estado'] ?? 'pendiente',
      asistio: json['asistio'] == true,
    );
  }
}

/// Servicio CRUD para Eventos
class EventosCrudService extends CrudService<Evento> with EventosCrudMixin<Evento> {
  static EventosCrudService? _instance;

  EventosCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'eventos',
          endpoint: '/flavor-app/v2/eventos',
          fromJson: Evento.fromJson,
          toJson: (evento) => evento.toJson(),
        );

  factory EventosCrudService({required ApiClient apiClient}) {
    _instance ??= EventosCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener eventos próximos
  Future<List<Evento>> getProximos({int limit = 10}) async {
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

  /// Obtener eventos de hoy
  Future<List<Evento>> getDeHoy() async {
    final hoy = DateTime.now();
    final inicioHoy = DateTime(hoy.year, hoy.month, hoy.day);
    final finHoy = inicioHoy.add(const Duration(days: 1));

    final result = await getList(
      filters: {
        'fecha_desde': inicioHoy.toIso8601String(),
        'fecha_hasta': finHoy.toIso8601String(),
      },
      orderBy: 'fecha_inicio',
      order: 'asc',
    );
    return result.items;
  }

  /// Obtener eventos por mes
  Future<List<Evento>> getByMes(int year, int month) async {
    final inicioMes = DateTime(year, month, 1);
    final finMes = DateTime(year, month + 1, 0);

    final result = await getList(
      perPage: 100,
      filters: {
        'fecha_desde': inicioMes.toIso8601String(),
        'fecha_hasta': finMes.toIso8601String(),
      },
      orderBy: 'fecha_inicio',
      order: 'asc',
    );
    return result.items;
  }

  /// Obtener mis eventos (donde estoy inscrito)
  Future<PaginatedResult<Evento>> getMisEventos({
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'mis_eventos': true},
    );
  }

  /// Obtener eventos que organizo
  Future<PaginatedResult<Evento>> getMisEventosOrganizados({
    int page = 1,
    int perPage = 20,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {'mis_organizados': true},
    );
  }

  /// Inscribirse a un evento
  @override
  Future<bool> inscribirse(String eventoId) async {
    try {
      await apiClient.post('/flavor-app/v2/eventos/$eventoId/inscribir');

      // Actualizar cache
      final evento = await getById(eventoId);
      if (evento != null) {
        final actualizado = Evento.fromJson({
          ...evento.toJson(),
          'esta_inscrito': true,
          'inscritos': evento.inscritos + 1,
        });
        cache[eventoId] = actualizado;

        notifyListeners(CrudEvent(
          type: CrudEventType.updated,
          item: actualizado,
        ));
      }

      return true;
    } catch (e) {
      debugPrint('[EventosCrudService] Error inscribiéndose: $e');
      return false;
    }
  }

  /// Cancelar inscripción
  @override
  Future<bool> cancelarInscripcion(String eventoId) async {
    try {
      await apiClient.delete('/flavor-app/v2/eventos/$eventoId/inscripcion');

      // Actualizar cache
      final evento = await getById(eventoId);
      if (evento != null) {
        final actualizado = Evento.fromJson({
          ...evento.toJson(),
          'esta_inscrito': false,
          'inscritos': evento.inscritos > 0 ? evento.inscritos - 1 : 0,
        });
        cache[eventoId] = actualizado;

        notifyListeners(CrudEvent(
          type: CrudEventType.updated,
          item: actualizado,
        ));
      }

      return true;
    } catch (e) {
      debugPrint('[EventosCrudService] Error cancelando inscripción: $e');
      return false;
    }
  }

  /// Obtener inscripciones de un evento (solo organizadores)
  Future<List<InscripcionEvento>> getInscripciones(String eventoId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/eventos/$eventoId/inscripciones',
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => InscripcionEvento.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[EventosCrudService] Error obteniendo inscripciones: $e');
      return [];
    }
  }

  /// Marcar asistencia
  Future<bool> marcarAsistencia(String eventoId, String inscripcionId, bool asistio) async {
    try {
      await apiClient.patch(
        '/flavor-app/v2/eventos/$eventoId/inscripciones/$inscripcionId',
        data: {'asistio': asistio},
      );
      return true;
    } catch (e) {
      debugPrint('[EventosCrudService] Error marcando asistencia: $e');
      return false;
    }
  }

  /// Añadir evento al calendario del dispositivo
  Future<bool> addToCalendar(Evento evento) async {
    // Esto se implementaría usando add_2_calendar package
    // Por ahora retornamos true
    debugPrint('[EventosCrudService] Añadir al calendario: ${evento.titulo}');
    return true;
  }

  /// Compartir evento
  Future<void> compartir(Evento evento) async {
    // Esto se implementaría usando share_plus package
    debugPrint('[EventosCrudService] Compartir evento: ${evento.titulo}');
  }
}
