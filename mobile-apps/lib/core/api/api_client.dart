import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/app_config.dart';
import '../config/server_config.dart';

/// Cliente HTTP para comunicación con la API de WordPress
class ApiClient {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final String? _customBaseUrl;

  static const String _tokenKey = 'auth_token';

  /// Constructor con URL por defecto desde configuracion
  ApiClient({String? baseUrl}) : _customBaseUrl = baseUrl {
    final effectiveBaseUrl = baseUrl ?? AppConfig.apiUrl;

    _dio = Dio(BaseOptions(
      baseUrl: effectiveBaseUrl,
      connectTimeout: Duration(seconds: AppConfig.httpTimeout),
      receiveTimeout: Duration(seconds: AppConfig.httpTimeout),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));

    // Interceptor para añadir token de autenticación
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        // Manejar errores globalmente
        if (error.response?.statusCode == 401) {
          // Token expirado, limpiar y redirigir a login
          clearToken();
        }
        return handler.next(error);
      },
    ));

    // Interceptor de logging en modo debug
    if (AppConfig.isDebug) {
      _dio.interceptors.add(LogInterceptor(
        requestBody: true,
        responseBody: true,
      ));
    }
  }

  /// Crea un ApiClient con la URL guardada en configuracion
  static Future<ApiClient> fromSavedConfig() async {
    final apiUrl = await ServerConfig.getFullApiUrl();
    return ApiClient(baseUrl: apiUrl);
  }

  /// Actualiza la baseUrl del cliente
  void updateBaseUrl(String newBaseUrl) {
    _dio.options.baseUrl = newBaseUrl;
  }

  /// Obtiene la baseUrl actual
  String get currentBaseUrl => _dio.options.baseUrl;

  // ==========================================
  // GESTIÓN DE TOKEN
  // ==========================================

  Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  Future<void> clearToken() async {
    await _storage.delete(key: _tokenKey);
  }

  Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // ==========================================
  // AUTENTICACIÓN
  // ==========================================

  /// Login de usuario
  Future<ApiResponse<Map<String, dynamic>>> login({
    required String username,
    required String password,
    required String appType, // 'client' o 'admin'
  }) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'username': username,
        'password': password,
        'app_type': appType,
      });

      if (response.data['success'] == true) {
        await saveToken(response.data['token']);
      }

      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Verificar token actual
  Future<ApiResponse<Map<String, dynamic>>> verifyToken() async {
    try {
      final response = await _dio.get('/auth/verify');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Logout
  Future<void> logout() async {
    await clearToken();
  }

  // ==========================================
  // INFO DEL SITIO
  // ==========================================

  /// Obtener información del sitio (logo, nombre, etc.)
  Future<ApiResponse<Map<String, dynamic>>> getSiteInfo() async {
    try {
      final response = await _dio.get('/site-info');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener contenido inteligente del sitio (autoconfiguración)
  Future<ApiResponse<Map<String, dynamic>>> getSiteContent() async {
    try {
      final response = await _dio.get('/site-content');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Validar token de seguridad para app Admin
  Future<ApiResponse<Map<String, dynamic>>> validateAdminSiteToken(String siteToken) async {
    try {
      final response = await _dio.post('/admin/validate-site-token', data: {
        'site_token': siteToken,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CHAT
  // ==========================================

  /// Crear sesión de chat
  Future<ApiResponse<Map<String, dynamic>>> createChatSession({
    String language = 'es',
    String? deviceId,
  }) async {
    try {
      final response = await _dio.post('/chat/session', data: {
        'language': language,
        'device_id': deviceId,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Enviar mensaje al chat
  Future<ApiResponse<Map<String, dynamic>>> sendChatMessage({
    required String message,
    String? sessionId,
    String language = 'es',
  }) async {
    try {
      final response = await _dio.post('/chat/send', data: {
        'message': message,
        'session_id': sessionId,
        'language': language,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Enviar mensaje al chat admin
  Future<ApiResponse<Map<String, dynamic>>> sendAdminChatMessage({
    required String message,
    String? sessionId,
  }) async {
    try {
      final response = await _dio.post('/admin/chat/send', data: {
        'message': message,
        'session_id': sessionId,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // INFORMACIÓN PÚBLICA
  // ==========================================

  /// Obtener información del negocio
  Future<ApiResponse<Map<String, dynamic>>> getBusinessInfo({
    String language = 'es',
  }) async {
    try {
      final response = await _dio.get('/public/business-info', queryParameters: {
        'language': language,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener disponibilidad
  Future<ApiResponse<Map<String, dynamic>>> getAvailability({
    String? from,
    String? to,
  }) async {
    try {
      final response = await _dio.get('/public/availability', queryParameters: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener tipos de tickets
  /// Si se pasa [state], filtra los tickets según el mapeo estado-tickets
  Future<ApiResponse<Map<String, dynamic>>> getTicketTypes({String? state}) async {
    try {
      final response = await _dio.get('/public/tickets', queryParameters: {
        if (state != null && state.isNotEmpty) 'state': state,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener experiencias
  Future<ApiResponse<Map<String, dynamic>>> getExperiences() async {
    try {
      final response = await _dio.get('/public/experiences');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener últimas publicaciones del blog
  Future<ApiResponse<Map<String, dynamic>>> getLatestPosts({int limit = 5}) async {
    try {
      final response = await _dio.get('/public/posts', queryParameters: {
        'limit': limit,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener novedades/actualizaciones del sitio
  Future<ApiResponse<Map<String, dynamic>>> getSiteUpdates({int limit = 5}) async {
    try {
      final response = await _dio.get('/public/updates', queryParameters: {
        'limit': limit,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // RESERVAS
  // ==========================================

  /// Verificar disponibilidad para reserva
  Future<ApiResponse<Map<String, dynamic>>> checkAvailability({
    required String date,
    String? ticket,
    int quantity = 1,
  }) async {
    try {
      final response = await _dio.post('/reservations/check', data: {
        'date': date,
        'ticket': ticket,
        'quantity': quantity,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Preparar reserva
  Future<ApiResponse<Map<String, dynamic>>> prepareReservation({
    required String date,
    required List<Map<String, dynamic>> tickets,
    Map<String, dynamic>? customer,
  }) async {
    try {
      final response = await _dio.post('/reservations/prepare', data: {
        'date': date,
        'tickets': tickets,
        'customer': customer,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Añadir al carrito
  Future<ApiResponse<Map<String, dynamic>>> addToCart({
    required String date,
    required List<Map<String, dynamic>> tickets,
    Map<String, dynamic>? customer,
  }) async {
    try {
      final response = await _dio.post('/reservations/add-to-cart', data: {
        'date': date,
        'tickets': tickets,
        'customer': customer,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener URL del carrito
  Future<ApiResponse<Map<String, dynamic>>> getCartUrl() async {
    try {
      final response = await _dio.get('/reservations/cart-url');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener URL de checkout para móvil
  /// Esta URL contiene los datos del carrito codificados y cuando se abre
  /// en el navegador, añade los productos al carrito del navegador
  Future<ApiResponse<Map<String, dynamic>>> getMobileCheckoutUrl({
    required String date,
    required List<Map<String, dynamic>> tickets,
  }) async {
    try {
      final response = await _dio.post('/reservations/mobile-checkout-url', data: {
        'date': date,
        'tickets': tickets,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // ADMIN
  // ==========================================

  /// Obtener dashboard admin
  Future<ApiResponse<Map<String, dynamic>>> getAdminDashboard() async {
    try {
      final response = await _dio.get('/admin/dashboard');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener reservas (admin)
  Future<ApiResponse<Map<String, dynamic>>> getAdminReservations({
    String? date,
    String? from,
    String? to,
    String? status,
    String? ticketType,
    String? search,
    int limit = 50,
    int offset = 0,
  }) async {
    try {
      final response = await _dio.get('/admin/reservations', queryParameters: {
        if (date != null) 'date': date,
        if (from != null) 'from': from,
        if (to != null) 'to': to,
        if (status != null) 'status': status,
        if (ticketType != null && ticketType.isNotEmpty) 'ticket_type': ticketType,
        if (search != null && search.isNotEmpty) 'search': search,
        'limit': limit,
        'offset': offset,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener detalle de reserva
  Future<ApiResponse<Map<String, dynamic>>> getReservationDetail(int id) async {
    try {
      final response = await _dio.get('/admin/reservations/$id');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Realizar check-in de una reserva
  Future<ApiResponse<Map<String, dynamic>>> doCheckin(int reservationId) async {
    try {
      final response = await _dio.post('/admin/reservations/$reservationId/checkin');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Cancelar una reserva
  Future<ApiResponse<Map<String, dynamic>>> cancelReservation(int reservationId, {String? reason}) async {
    try {
      final response = await _dio.post('/admin/reservations/$reservationId/cancel', data: {
        if (reason != null) 'reason': reason,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Buscar reserva por codigo de ticket (para QR)
  Future<ApiResponse<Map<String, dynamic>>> findReservationByCode(String code) async {
    try {
      final response = await _dio.get('/admin/reservations/find', queryParameters: {
        'code': code,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener estadísticas
  Future<ApiResponse<Map<String, dynamic>>> getAdminStats({
    String? from,
    String? to,
  }) async {
    try {
      final response = await _dio.get('/admin/stats', queryParameters: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener clientes
  Future<ApiResponse<Map<String, dynamic>>> getCustomers({
    String? from,
    String? to,
    String? search,
    int limit = 50,
  }) async {
    try {
      final response = await _dio.get('/admin/customers', queryParameters: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
        if (search != null) 'search': search,
        'limit': limit,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Exportar CSV
  Future<ApiResponse<Map<String, dynamic>>> exportCsv({
    required String type,
    required String from,
    required String to,
  }) async {
    try {
      final response = await _dio.post('/admin/export/csv', data: {
        'type': type,
        'from': from,
        'to': to,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Ver datos (sin exportar)
  Future<ApiResponse<Map<String, dynamic>>> viewData({
    required String type,
    required String from,
    required String to,
  }) async {
    try {
      final response = await _dio.post('/admin/export/csv', data: {
        'type': type,
        'from': from,
        'to': to,
        'view_only': true,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Ver datos con filtro de ticket (sin exportar)
  Future<ApiResponse<Map<String, dynamic>>> viewDataWithTicket({
    required String type,
    required String from,
    required String to,
    String? ticketType,
  }) async {
    try {
      final response = await _dio.post('/admin/export/csv', data: {
        'type': type,
        'from': from,
        'to': to,
        'view_only': true,
        if (ticketType != null && ticketType.isNotEmpty) 'ticket_type': ticketType,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Exportar CSV con filtro de ticket
  Future<ApiResponse<Map<String, dynamic>>> exportCsvWithTicket({
    required String type,
    required String from,
    required String to,
    String? ticketType,
  }) async {
    try {
      final response = await _dio.post('/admin/export/csv', data: {
        'type': type,
        'from': from,
        'to': to,
        if (ticketType != null && ticketType.isNotEmpty) 'ticket_type': ticketType,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CLIENTES MANUALES
  // ==========================================

  /// Obtener clientes manuales
  Future<ApiResponse<Map<String, dynamic>>> getManualCustomers({
    String? from,
    String? to,
  }) async {
    try {
      final response = await _dio.get('/admin/manual-customers', queryParameters: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Crear cliente manual
  Future<ApiResponse<Map<String, dynamic>>> createManualCustomer(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post('/admin/manual-customers', data: data);
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Actualizar cliente manual
  Future<ApiResponse<Map<String, dynamic>>> updateManualCustomer(int id, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put('/admin/manual-customers/$id', data: data);
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Eliminar cliente manual
  Future<ApiResponse<Map<String, dynamic>>> deleteManualCustomer(int id) async {
    try {
      final response = await _dio.delete('/admin/manual-customers/$id');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Guardar notas de cliente (manual o WooCommerce)
  Future<ApiResponse<Map<String, dynamic>>> saveCustomerNotes({
    required String origin,
    required int id,
    required String notes,
    String? date,
  }) async {
    try {
      final response = await _dio.post('/admin/customer-notes', data: {
        'origin': origin,
        if (origin == 'manual') 'id': id else 'order_id': id,
        'notes': notes,
        if (date != null) 'date': date,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener clientes unificados (WooCommerce + Manuales)
  Future<ApiResponse<Map<String, dynamic>>> getUnifiedCustomers({
    required String from,
    required String to,
  }) async {
    try {
      final response = await _dio.get('/admin/unified-customers', queryParameters: {
        'from': from,
        'to': to,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // PUSH NOTIFICATIONS
  // ==========================================

  /// Registrar token de push
  Future<ApiResponse<Map<String, dynamic>>> registerPushToken({
    required String token,
    required String platform,
  }) async {
    try {
      final response = await _dio.post('/push/register', data: {
        'token': token,
        'platform': platform,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CLIENTE: MIS RESERVAS / BILLETERA
  // ==========================================

  /// Obtener reservas del cliente (para billetera de tickets)
  Future<ApiResponse<Map<String, dynamic>>> getClientReservations({
    required String email,
    String? deviceId,
    bool includePast = false,
  }) async {
    try {
      final response = await _dio.get('/client/my-reservations', queryParameters: {
        'email': email,
        if (deviceId != null) 'device_id': deviceId,
        'include_past': includePast.toString(),
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener reserva por código (para verificación QR)
  Future<ApiResponse<Map<String, dynamic>>> getClientReservationByCode(String code) async {
    try {
      final response = await _dio.get('/client/reservation/$code');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener configuración de la app cliente
  Future<ApiResponse<Map<String, dynamic>>> getClientAppConfig() async {
    try {
      final response = await _dio.get('/client/config');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // VERIFICACIÓN DE EMAIL
  // ==========================================

  /// Enviar código de verificación al email
  Future<ApiResponse<Map<String, dynamic>>> sendEmailVerificationCode({
    required String email,
    required String deviceId,
  }) async {
    try {
      final response = await _dio.post('/client/email/send-code', data: {
        'email': email,
        'device_id': deviceId,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Verificar código de email
  Future<ApiResponse<Map<String, dynamic>>> verifyEmailCode({
    required String email,
    required String code,
    required String deviceId,
  }) async {
    try {
      final response = await _dio.post('/client/email/verify-code', data: {
        'email': email,
        'code': code,
        'device_id': deviceId,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener estado de verificación del dispositivo
  Future<ApiResponse<Map<String, dynamic>>> getEmailVerificationStatus({
    required String deviceId,
  }) async {
    try {
      final response = await _dio.get('/client/email/status', queryParameters: {
        'device_id': deviceId,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CHATS ESCALADOS (ADMIN)
  // ==========================================

  /// Obtener lista de chats escalados
  Future<ApiResponse<Map<String, dynamic>>> getEscalatedChats({
    String? status,
    int limit = 50,
    int offset = 0,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'limit': limit,
        'offset': offset,
      };
      if (status != null) {
        queryParams['status'] = status;
      }

      final response = await _dio.get(
        '/admin/chat/escalated',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener detalle de un chat escalado
  Future<ApiResponse<Map<String, dynamic>>> getEscalatedChatDetail(
    String sessionId,
  ) async {
    try {
      final response = await _dio.get('/admin/chat/escalated/$sessionId');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Enviar respuesta a un chat escalado
  Future<ApiResponse<Map<String, dynamic>>> replyEscalatedChat(
    String sessionId,
    String message,
    String adminName,
  ) async {
    try {
      final response = await _dio.post(
        '/admin/chat/escalated/$sessionId/reply',
        data: {
          'message': message,
          'admin_name': adminName,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Marcar un chat escalado como resuelto
  Future<ApiResponse<Map<String, dynamic>>> resolveEscalatedChat(
    String sessionId, {
    String? notes,
  }) async {
    try {
      final data = <String, dynamic>{};
      if (notes != null && notes.isNotEmpty) {
        data['notes'] = notes;
      }

      final response = await _dio.post(
        '/admin/chat/escalated/$sessionId/resolve',
        data: data,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CAMPAMENTOS
  // ==========================================

  /// Obtener lista de campamentos
  Future<ApiResponse<Map<String, dynamic>>> getCamps({
    String? category,
    String? age,
    String? language,
    String? status,
    String? search,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (category != null) queryParams['category'] = category;
      if (age != null) queryParams['age'] = age;
      if (language != null) queryParams['language'] = language;
      if (status != null) queryParams['status'] = status;
      if (search != null && search.isNotEmpty) queryParams['search'] = search;

      final response = await _dio.get(
        '/camps/v1/camps',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener detalle de un campamento
  Future<ApiResponse<Map<String, dynamic>>> getCampDetail(int campId) async {
    try {
      final response = await _dio.get('/camps/v1/camps/$campId');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Crear inscripción a un campamento
  Future<ApiResponse<Map<String, dynamic>>> createCampInscription({
    required int campId,
    required Map<String, dynamic> participant,
    required Map<String, dynamic> guardian,
    String paymentMethod = 'stripe',
  }) async {
    try {
      final response = await _dio.post(
        '/camps/v1/camps/$campId/inscribe',
        data: {
          'participant': participant,
          'guardian': guardian,
          'payment_method': paymentMethod,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener lista de campamentos para admin
  Future<ApiResponse<Map<String, dynamic>>> getAdminCamps() async {
    try {
      final response = await _dio.get('/camps/v1/admin/camps');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener inscripciones de un campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> getCampInscriptions({
    required int campId,
    String? search,
    String? paymentStatus,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (search != null && search.isNotEmpty) queryParams['search'] = search;
      if (paymentStatus != null) {
        queryParams['payment_status'] = paymentStatus;
      }

      final response = await _dio.get(
        '/camps/v1/admin/camps/$campId/inscriptions',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener estadísticas de campamentos (admin)
  Future<ApiResponse<Map<String, dynamic>>> getCampStats({
    String period = 'month',
  }) async {
    try {
      final response = await _dio.get(
        '/camps/v1/admin/stats',
        queryParameters: {'period': period},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Toggle estado de inscripción de un campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> toggleCampInscription(
    int campId,
  ) async {
    try {
      final response = await _dio.post(
        '/camps/v1/admin/camps/$campId/toggle-inscription',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Exportar inscripciones a Excel (admin)
  Future<ApiResponse<Map<String, dynamic>>> exportCampInscriptionsExcel(
    int campId,
  ) async {
    try {
      final response = await _dio.get(
        '/camps/v1/admin/camps/$campId/export-excel',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Crear nuevo campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> createCamp({
    required String title,
    String? description,
    String? excerpt,
    int? featuredImageId,
    double? price,
    double? priceTotal,
    String? duration,
    String? label,
    bool inscriptionClosed = false,
    String? startDate,
    String? endDate,
    String? schedule,
    String? location,
    String? includes,
    String? requirements,
    List<int>? categoryIds,
    List<int>? ageIds,
    List<int>? languageIds,
  }) async {
    try {
      final data = <String, dynamic>{
        'title': title,
      };

      if (description != null) data['description'] = description;
      if (excerpt != null) data['excerpt'] = excerpt;
      if (featuredImageId != null) data['featured_image_id'] = featuredImageId;
      if (price != null) data['price'] = price;
      if (priceTotal != null) data['price_total'] = priceTotal;
      if (duration != null) data['duration'] = duration;
      if (label != null) data['label'] = label;
      data['inscription_closed'] = inscriptionClosed;
      if (startDate != null) data['start_date'] = startDate;
      if (endDate != null) data['end_date'] = endDate;
      if (schedule != null) data['schedule'] = schedule;
      if (location != null) data['location'] = location;
      if (includes != null) data['includes'] = includes;
      if (requirements != null) data['requirements'] = requirements;
      if (categoryIds != null) data['category_ids'] = categoryIds;
      if (ageIds != null) data['age_ids'] = ageIds;
      if (languageIds != null) data['language_ids'] = languageIds;

      final response = await _dio.post(
        '/camps/v1/admin/camps',
        data: data,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Actualizar campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> updateCamp({
    required int campId,
    String? title,
    String? description,
    String? excerpt,
    int? featuredImageId,
    double? price,
    double? priceTotal,
    String? duration,
    String? label,
    bool? inscriptionClosed,
    String? startDate,
    String? endDate,
    String? schedule,
    String? location,
    String? includes,
    String? requirements,
    List<int>? categoryIds,
    List<int>? ageIds,
    List<int>? languageIds,
  }) async {
    try {
      final data = <String, dynamic>{};

      if (title != null) data['title'] = title;
      if (description != null) data['description'] = description;
      if (excerpt != null) data['excerpt'] = excerpt;
      if (featuredImageId != null) data['featured_image_id'] = featuredImageId;
      if (price != null) data['price'] = price;
      if (priceTotal != null) data['price_total'] = priceTotal;
      if (duration != null) data['duration'] = duration;
      if (label != null) data['label'] = label;
      if (inscriptionClosed != null) {
        data['inscription_closed'] = inscriptionClosed;
      }
      if (startDate != null) data['start_date'] = startDate;
      if (endDate != null) data['end_date'] = endDate;
      if (schedule != null) data['schedule'] = schedule;
      if (location != null) data['location'] = location;
      if (includes != null) data['includes'] = includes;
      if (requirements != null) data['requirements'] = requirements;
      if (categoryIds != null) data['category_ids'] = categoryIds;
      if (ageIds != null) data['age_ids'] = ageIds;
      if (languageIds != null) data['language_ids'] = languageIds;

      final response = await _dio.put(
        '/camps/v1/admin/camps/$campId',
        data: data,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Eliminar campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> deleteCamp(int campId) async {
    try {
      final response = await _dio.delete('/camps/v1/admin/camps/$campId');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Toggle activar/desactivar campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> toggleCampStatus(
    int campId,
  ) async {
    try {
      final response = await _dio.post(
        '/camps/v1/admin/camps/$campId/toggle-status',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener enlace compartible de campamento (admin)
  Future<ApiResponse<Map<String, dynamic>>> getCampShareableLink(
    int campId,
  ) async {
    try {
      final response = await _dio.get(
        '/camps/v1/admin/camps/$campId/shareable-link',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener taxonomías disponibles (admin)
  Future<ApiResponse<Map<String, dynamic>>> getCampTaxonomies() async {
    try {
      final response = await _dio.get('/camps/v1/admin/taxonomies');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // APP DISCOVERY / SYNC
  // ==========================================

  /// Obtener información de descubrimiento del sitio (incluye layouts)
  /// Este es el endpoint principal para sincronizar la app con un sitio nuevo
  Future<ApiResponse<Map<String, dynamic>>> getAppDiscoveryInfo() async {
    try {
      // Usar el namespace de descubrimiento
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/app-discovery/v1/info',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener configuración de layouts específica
  Future<ApiResponse<Map<String, dynamic>>> getLayoutsConfig() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/app-discovery/v1/layouts',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener tema del sitio
  Future<ApiResponse<Map<String, dynamic>>> getThemeConfig() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/app-discovery/v1/theme',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener módulos disponibles en el sitio
  Future<ApiResponse<Map<String, dynamic>>> getAvailableModules() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/app-discovery/v1/modules',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ),
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // HELPERS
  // ==========================================

  String _handleError(DioException error) {
    if (error.response != null) {
      final data = error.response!.data;
      if (data is Map && data.containsKey('message')) {
        return data['message'];
      }
      if (data is Map && data.containsKey('error')) {
        return data['error'];
      }
      return 'Error ${error.response!.statusCode}';
    }

    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return 'Tiempo de conexión agotado';
      case DioExceptionType.connectionError:
        return 'Error de conexión. Verifica tu internet.';
      default:
        return 'Error de red desconocido';
    }
  }
}

/// Respuesta de la API
class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? error;

  ApiResponse._({
    required this.success,
    this.data,
    this.error,
  });

  factory ApiResponse.success(T data) => ApiResponse._(
        success: true,
        data: data,
      );

  factory ApiResponse.error(String error) => ApiResponse._(
        success: false,
        error: error,
      );
}
