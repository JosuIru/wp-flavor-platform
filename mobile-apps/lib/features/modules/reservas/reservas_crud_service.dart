import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Espacio/Recurso reservable
class EspacioReservable {
  final String id;
  final String nombre;
  final String? descripcion;
  final String? imagen;
  final List<String> imagenes;
  final String? ubicacion;
  final Map<String, double>? coordenadas;
  final int? capacidad;
  final double? precioPorHora;
  final bool gratuito;
  final List<String> equipamiento;
  final Map<String, dynamic>? horarios; // horarios de disponibilidad
  final bool activo;
  final String? categoria;

  EspacioReservable({
    required this.id,
    required this.nombre,
    this.descripcion,
    this.imagen,
    this.imagenes = const [],
    this.ubicacion,
    this.coordenadas,
    this.capacidad,
    this.precioPorHora,
    this.gratuito = true,
    this.equipamiento = const [],
    this.horarios,
    this.activo = true,
    this.categoria,
  });

  factory EspacioReservable.fromJson(Map<String, dynamic> json) {
    return EspacioReservable(
      id: json['id']?.toString() ?? '',
      nombre: json['nombre'] ?? json['name'] ?? '',
      descripcion: json['descripcion'] ?? json['description'],
      imagen: json['imagen'] ?? json['image'],
      imagenes: List<String>.from(json['imagenes'] ?? json['images'] ?? []),
      ubicacion: json['ubicacion'] ?? json['location'],
      coordenadas: json['coordenadas'] != null
          ? Map<String, double>.from(json['coordenadas'])
          : null,
      capacidad: json['capacidad'] ?? json['capacity'],
      precioPorHora: (json['precio_hora'] ?? json['price_per_hour'])?.toDouble(),
      gratuito: json['gratuito'] == true || json['free'] == true,
      equipamiento: List<String>.from(json['equipamiento'] ?? json['equipment'] ?? []),
      horarios: json['horarios'] ?? json['schedule'],
      activo: json['activo'] != false,
      categoria: json['categoria'] ?? json['category'],
    );
  }
}

/// Modelo de Reserva
class Reserva {
  final String? id;
  final String espacioId;
  final String? espacioNombre;
  final String? usuarioId;
  final String? usuarioNombre;
  final DateTime fechaInicio;
  final DateTime fechaFin;
  final String? proposito;
  final int? asistentes;
  final String estado; // pendiente, confirmada, cancelada, completada
  final double? precioTotal;
  final String? notas;
  final bool requiereAprobacion;
  final DateTime? fechaCreacion;
  final bool isPending;

  Reserva({
    this.id,
    required this.espacioId,
    this.espacioNombre,
    this.usuarioId,
    this.usuarioNombre,
    required this.fechaInicio,
    required this.fechaFin,
    this.proposito,
    this.asistentes,
    this.estado = 'pendiente',
    this.precioTotal,
    this.notas,
    this.requiereAprobacion = false,
    this.fechaCreacion,
    this.isPending = false,
  });

  factory Reserva.fromJson(Map<String, dynamic> json) {
    return Reserva(
      id: json['id']?.toString(),
      espacioId: json['espacio_id']?.toString() ?? json['resource_id']?.toString() ?? '',
      espacioNombre: json['espacio_nombre'] ?? json['resource_name'],
      usuarioId: json['usuario_id']?.toString(),
      usuarioNombre: json['usuario_nombre'] ?? json['user_name'],
      fechaInicio: DateTime.tryParse(json['fecha_inicio'] ?? json['start_date'] ?? '') ?? DateTime.now(),
      fechaFin: DateTime.tryParse(json['fecha_fin'] ?? json['end_date'] ?? '') ?? DateTime.now(),
      proposito: json['proposito'] ?? json['purpose'],
      asistentes: json['asistentes'] ?? json['attendees'],
      estado: json['estado'] ?? json['status'] ?? 'pendiente',
      precioTotal: (json['precio_total'] ?? json['total_price'])?.toDouble(),
      notas: json['notas'] ?? json['notes'],
      requiereAprobacion: json['requiere_aprobacion'] == true,
      fechaCreacion: json['fecha_creacion'] != null
          ? DateTime.tryParse(json['fecha_creacion'])
          : null,
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'espacio_id': espacioId,
      'fecha_inicio': fechaInicio.toIso8601String(),
      'fecha_fin': fechaFin.toIso8601String(),
      if (proposito != null) 'proposito': proposito,
      if (asistentes != null) 'asistentes': asistentes,
      if (notas != null) 'notas': notas,
    };
  }

  /// Duración en horas
  double get duracionHoras => fechaFin.difference(fechaInicio).inMinutes / 60;

  /// ¿Está activa (no ha terminado)?
  bool get estaActiva => fechaFin.isAfter(DateTime.now()) && estado == 'confirmada';

  /// ¿Se puede cancelar?
  bool get sePuedeCancelar =>
      estado == 'pendiente' ||
      (estado == 'confirmada' && fechaInicio.isAfter(DateTime.now()));
}

/// Slot de disponibilidad
class SlotDisponibilidad {
  final DateTime inicio;
  final DateTime fin;
  final bool disponible;
  final String? motivo;

  SlotDisponibilidad({
    required this.inicio,
    required this.fin,
    required this.disponible,
    this.motivo,
  });

  factory SlotDisponibilidad.fromJson(Map<String, dynamic> json) {
    return SlotDisponibilidad(
      inicio: DateTime.parse(json['inicio'] ?? json['start']),
      fin: DateTime.parse(json['fin'] ?? json['end']),
      disponible: json['disponible'] == true || json['available'] == true,
      motivo: json['motivo'] ?? json['reason'],
    );
  }
}

/// Servicio CRUD para Reservas
class ReservasCrudService extends CrudService<Reserva> {
  static ReservasCrudService? _instance;

  ReservasCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'reservas',
          endpoint: '/flavor-app/v2/reservas',
          fromJson: Reserva.fromJson,
          toJson: (reserva) => reserva.toJson(),
        );

  factory ReservasCrudService({required ApiClient apiClient}) {
    _instance ??= ReservasCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener espacios disponibles
  Future<List<EspacioReservable>> getEspacios({String? categoria}) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/reservas/espacios',
        queryParameters: {
          if (categoria != null) 'categoria': categoria,
        },
      );

      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => EspacioReservable.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[ReservasCrudService] Error obteniendo espacios: $e');
      return [];
    }
  }

  /// Obtener detalle de un espacio
  Future<EspacioReservable?> getEspacio(String espacioId) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/reservas/espacios/$espacioId',
      );
      return EspacioReservable.fromJson(response.data ?? {});
    } catch (e) {
      debugPrint('[ReservasCrudService] Error obteniendo espacio: $e');
      return null;
    }
  }

  /// Obtener disponibilidad de un espacio para una fecha
  Future<List<SlotDisponibilidad>> getDisponibilidad(
    String espacioId,
    DateTime fecha,
  ) async {
    try {
      final response = await apiClient.get(
        '/flavor-app/v2/reservas/espacios/$espacioId/disponibilidad',
        queryParameters: {
          'fecha': fecha.toIso8601String().split('T')[0],
        },
      );

      final List<dynamic> data = response.data?['slots'] ?? response.data;
      return data.map((json) => SlotDisponibilidad.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[ReservasCrudService] Error obteniendo disponibilidad: $e');
      return [];
    }
  }

  /// Verificar disponibilidad para un rango
  Future<bool> verificarDisponibilidad(
    String espacioId,
    DateTime inicio,
    DateTime fin,
  ) async {
    try {
      final response = await apiClient.post(
        '/flavor-app/v2/reservas/verificar',
        data: {
          'espacio_id': espacioId,
          'fecha_inicio': inicio.toIso8601String(),
          'fecha_fin': fin.toIso8601String(),
        },
      );
      return response.data?['disponible'] == true;
    } catch (e) {
      debugPrint('[ReservasCrudService] Error verificando disponibilidad: $e');
      return false;
    }
  }

  /// Crear una reserva
  Future<Reserva?> crearReserva(Reserva reserva) async {
    return create(reserva);
  }

  /// Obtener mis reservas
  Future<PaginatedResult<Reserva>> getMisReservas({
    int page = 1,
    int perPage = 20,
    String? estado,
    bool soloFuturas = false,
  }) async {
    return getList(
      page: page,
      perPage: perPage,
      filters: {
        'mis_reservas': true,
        if (estado != null) 'estado': estado,
        if (soloFuturas) 'fecha_desde': DateTime.now().toIso8601String(),
      },
      orderBy: 'fecha_inicio',
      order: soloFuturas ? 'asc' : 'desc',
    );
  }

  /// Obtener reservas de hoy
  Future<List<Reserva>> getReservasHoy() async {
    final hoy = DateTime.now();
    final inicioHoy = DateTime(hoy.year, hoy.month, hoy.day);
    final finHoy = inicioHoy.add(const Duration(days: 1));

    final result = await getList(
      filters: {
        'mis_reservas': true,
        'fecha_desde': inicioHoy.toIso8601String(),
        'fecha_hasta': finHoy.toIso8601String(),
      },
      orderBy: 'fecha_inicio',
      order: 'asc',
    );
    return result.items;
  }

  /// Cancelar reserva
  Future<bool> cancelarReserva(String reservaId, {String? motivo}) async {
    try {
      await apiClient.post(
        '/flavor-app/v2/reservas/$reservaId/cancelar',
        data: {
          if (motivo != null) 'motivo': motivo,
        },
      );

      // Actualizar cache
      final reserva = cache[reservaId];
      if (reserva != null) {
        final actualizada = Reserva.fromJson({
          ...reserva.toJson(),
          'estado': 'cancelada',
        });
        cache[reservaId] = actualizada;
        notifyListeners(CrudEvent(type: CrudEventType.updated, item: actualizada));
      }

      return true;
    } catch (e) {
      debugPrint('[ReservasCrudService] Error cancelando reserva: $e');
      return false;
    }
  }

  /// Modificar reserva (cambiar fecha/hora)
  Future<bool> modificarReserva(
    String reservaId, {
    DateTime? nuevaFechaInicio,
    DateTime? nuevaFechaFin,
  }) async {
    try {
      await apiClient.patch(
        '/flavor-app/v2/reservas/$reservaId',
        data: {
          if (nuevaFechaInicio != null) 'fecha_inicio': nuevaFechaInicio.toIso8601String(),
          if (nuevaFechaFin != null) 'fecha_fin': nuevaFechaFin.toIso8601String(),
        },
      );

      // Refrescar desde servidor
      await getById(reservaId, forceRefresh: true);

      return true;
    } catch (e) {
      debugPrint('[ReservasCrudService] Error modificando reserva: $e');
      return false;
    }
  }

  /// Añadir reserva al calendario del dispositivo
  Future<bool> addToCalendar(Reserva reserva) async {
    // Implementar con add_2_calendar package
    debugPrint('[ReservasCrudService] Añadir al calendario: ${reserva.espacioNombre}');
    return true;
  }

  /// Obtener categorías de espacios
  Future<List<Map<String, dynamic>>> getCategorias() async {
    try {
      final response = await apiClient.get('/flavor-app/v2/reservas/categorias');
      final List<dynamic> data = response.data?['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[ReservasCrudService] Error obteniendo categorías: $e');
      return [];
    }
  }
}
