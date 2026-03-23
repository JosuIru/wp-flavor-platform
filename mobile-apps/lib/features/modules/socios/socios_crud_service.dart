import 'package:flutter/foundation.dart';
import '../../../core/services/crud_service.dart';
import '../../../core/api/api_client.dart';

/// Modelo de Socio
class Socio {
  final String? id;
  final String nombre;
  final String? apellidos;
  final String? email;
  final String? telefono;
  final String? dni;
  final String? direccion;
  final String? avatar;
  final String? numeroSocio;
  final String tipoSocio; // fundador, ordinario, colaborador, honorario
  final String estado; // activo, pendiente, baja, suspendido
  final DateTime? fechaAlta;
  final DateTime? fechaBaja;
  final double? cuotaMensual;
  final bool cuotaAlDia;
  final int? puntos;
  final Map<String, dynamic>? metadatos;
  final bool isPending;

  Socio({
    this.id,
    required this.nombre,
    this.apellidos,
    this.email,
    this.telefono,
    this.dni,
    this.direccion,
    this.avatar,
    this.numeroSocio,
    this.tipoSocio = 'ordinario',
    this.estado = 'pendiente',
    this.fechaAlta,
    this.fechaBaja,
    this.cuotaMensual,
    this.cuotaAlDia = false,
    this.puntos,
    this.metadatos,
    this.isPending = false,
  });

  String get nombreCompleto => apellidos != null ? '$nombre $apellidos' : nombre;

  factory Socio.fromJson(Map<String, dynamic> json) {
    return Socio(
      id: json['id']?.toString(),
      nombre: json['nombre'] ?? json['name'] ?? '',
      apellidos: json['apellidos'] ?? json['lastname'],
      email: json['email'],
      telefono: json['telefono'] ?? json['phone'],
      dni: json['dni'],
      direccion: json['direccion'] ?? json['address'],
      avatar: json['avatar'],
      numeroSocio: json['numero_socio']?.toString() ?? json['member_number']?.toString(),
      tipoSocio: json['tipo_socio'] ?? json['member_type'] ?? 'ordinario',
      estado: json['estado'] ?? json['status'] ?? 'pendiente',
      fechaAlta: json['fecha_alta'] != null
          ? DateTime.tryParse(json['fecha_alta'])
          : null,
      fechaBaja: json['fecha_baja'] != null
          ? DateTime.tryParse(json['fecha_baja'])
          : null,
      cuotaMensual: (json['cuota_mensual'] ?? json['monthly_fee'])?.toDouble(),
      cuotaAlDia: json['cuota_al_dia'] == true || json['fees_paid'] == true,
      puntos: json['puntos'] ?? json['points'],
      metadatos: json['metadatos'] ?? json['metadata'],
      isPending: json['_pending'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'nombre': nombre,
      if (apellidos != null) 'apellidos': apellidos,
      if (email != null) 'email': email,
      if (telefono != null) 'telefono': telefono,
      if (dni != null) 'dni': dni,
      if (direccion != null) 'direccion': direccion,
      'tipo_socio': tipoSocio,
      if (cuotaMensual != null) 'cuota_mensual': cuotaMensual,
      if (metadatos != null) 'metadatos': metadatos,
    };
  }

  bool get estaActivo => estado == 'activo';
}

/// Modelo de Cuota/Pago
class CuotaSocio {
  final String id;
  final String socioId;
  final double importe;
  final String concepto;
  final String periodo; // 2024-01, 2024-02, etc.
  final String estado; // pagada, pendiente, vencida
  final DateTime? fechaPago;
  final DateTime fechaVencimiento;
  final String? metodoPago;
  final String? referencia;

  CuotaSocio({
    required this.id,
    required this.socioId,
    required this.importe,
    required this.concepto,
    required this.periodo,
    required this.estado,
    this.fechaPago,
    required this.fechaVencimiento,
    this.metodoPago,
    this.referencia,
  });

  factory CuotaSocio.fromJson(Map<String, dynamic> json) {
    return CuotaSocio(
      id: json['id']?.toString() ?? '',
      socioId: json['socio_id']?.toString() ?? '',
      importe: (json['importe'] ?? json['amount'] ?? 0).toDouble(),
      concepto: json['concepto'] ?? json['concept'] ?? '',
      periodo: json['periodo'] ?? json['period'] ?? '',
      estado: json['estado'] ?? json['status'] ?? 'pendiente',
      fechaPago: json['fecha_pago'] != null
          ? DateTime.tryParse(json['fecha_pago'])
          : null,
      fechaVencimiento: DateTime.tryParse(json['fecha_vencimiento'] ?? '') ?? DateTime.now(),
      metodoPago: json['metodo_pago'] ?? json['payment_method'],
      referencia: json['referencia'] ?? json['reference'],
    );
  }

  bool get estaPagada => estado == 'pagada';
  bool get estaVencida => estado == 'vencida' ||
      (estado == 'pendiente' && fechaVencimiento.isBefore(DateTime.now()));
}

/// Servicio CRUD para Socios
class SociosCrudService extends CrudService<Socio> {
  static SociosCrudService? _instance;

  SociosCrudService._({required ApiClient apiClient})
      : super(
          apiClient: apiClient,
          moduleName: 'socios',
          endpoint: '/flavor-app/v2/socios',
          fromJson: Socio.fromJson,
          toJson: (socio) => socio.toJson(),
        );

  factory SociosCrudService({required ApiClient apiClient}) {
    _instance ??= SociosCrudService._(apiClient: apiClient);
    return _instance!;
  }

  /// Obtener mi perfil de socio
  Future<Socio?> getMiPerfil() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/socios/me');
      return Socio.fromJson(response.data);
    } catch (e) {
      debugPrint('[SociosCrudService] Error obteniendo perfil: $e');
      return null;
    }
  }

  /// Solicitar alta como socio
  Future<bool> solicitarAlta(Socio solicitud) async {
    try {
      await _apiClient.post(
        '/flavor-app/v2/socios/solicitar',
        data: solicitud.toJson(),
      );
      return true;
    } catch (e) {
      debugPrint('[SociosCrudService] Error solicitando alta: $e');
      return false;
    }
  }

  /// Obtener mis cuotas
  Future<List<CuotaSocio>> getMisCuotas({String? estado}) async {
    try {
      final response = await _apiClient.get(
        '/flavor-app/v2/socios/me/cuotas',
        queryParameters: {
          if (estado != null) 'estado': estado,
        },
      );

      final List<dynamic> data = response.data['items'] ?? response.data;
      return data.map((json) => CuotaSocio.fromJson(json)).toList();
    } catch (e) {
      debugPrint('[SociosCrudService] Error obteniendo cuotas: $e');
      return [];
    }
  }

  /// Obtener cuotas pendientes
  Future<List<CuotaSocio>> getCuotasPendientes() async {
    return getMisCuotas(estado: 'pendiente');
  }

  /// Pagar cuota
  Future<Map<String, dynamic>?> pagarCuota(String cuotaId, String metodoPago) async {
    try {
      final response = await _apiClient.post(
        '/flavor-app/v2/socios/cuotas/$cuotaId/pagar',
        data: {
          'metodo_pago': metodoPago,
        },
      );
      return response.data;
    } catch (e) {
      debugPrint('[SociosCrudService] Error pagando cuota: $e');
      return null;
    }
  }

  /// Obtener historial de pagos
  Future<List<CuotaSocio>> getHistorialPagos({int limit = 20}) async {
    return getMisCuotas(estado: 'pagada');
  }

  /// Actualizar datos de contacto
  Future<bool> actualizarContacto({
    String? email,
    String? telefono,
    String? direccion,
  }) async {
    try {
      await _apiClient.patch(
        '/flavor-app/v2/socios/me',
        data: {
          if (email != null) 'email': email,
          if (telefono != null) 'telefono': telefono,
          if (direccion != null) 'direccion': direccion,
        },
      );
      return true;
    } catch (e) {
      debugPrint('[SociosCrudService] Error actualizando contacto: $e');
      return false;
    }
  }

  /// Solicitar baja
  Future<bool> solicitarBaja(String motivo) async {
    try {
      await _apiClient.post(
        '/flavor-app/v2/socios/me/baja',
        data: {'motivo': motivo},
      );
      return true;
    } catch (e) {
      debugPrint('[SociosCrudService] Error solicitando baja: $e');
      return false;
    }
  }

  /// Obtener carnet digital
  Future<Map<String, dynamic>?> getCarnetDigital() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/socios/me/carnet');
      return response.data;
    } catch (e) {
      debugPrint('[SociosCrudService] Error obteniendo carnet: $e');
      return null;
    }
  }

  /// Obtener beneficios del socio
  Future<List<Map<String, dynamic>>> getBeneficios() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/socios/beneficios');
      final List<dynamic> data = response.data['items'] ?? response.data;
      return data.map((json) => Map<String, dynamic>.from(json)).toList();
    } catch (e) {
      debugPrint('[SociosCrudService] Error obteniendo beneficios: $e');
      return [];
    }
  }
}
