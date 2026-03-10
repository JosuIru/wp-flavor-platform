import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/app_config.dart';
import '../config/server_config.dart';
import '../security/http_security.dart';

/// Cliente HTTP para comunicación con la API de WordPress
class ApiClient {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final String? _customBaseUrl;

  static const String _tokenKey = 'auth_token';

  /// Constructor con URL por defecto desde configuracion
  ApiClient({String? baseUrl, bool enableSecurityFeatures = true}) : _customBaseUrl = baseUrl {
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

    // Aplicar configuración de seguridad HTTP
    if (enableSecurityFeatures) {
      _dio.applySecurityConfig(enablePinning: !AppConfig.isDebug);
    }

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

  /// Expone el cliente Dio para uso interno (ej: E2E API)
  Dio get dio => _dio;

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

  /// Obtener configuración completa de la app cliente
  /// Incluye tabs, features, info_sections, colores, etc.
  Future<ApiResponse<Map<String, dynamic>>> getClientAppConfig() async {
    try {
      final response = await _dio.get('/client-app-config');
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
  // DASHBOARD CHARTS
  // ==========================================

  /// Obtener datos para graficos del dashboard del cliente
  /// [type] puede ser: 'weekly_activity', 'distribution', 'trends'
  /// [days] numero de dias de datos a obtener (por defecto 7)
  Future<ApiResponse<Map<String, dynamic>>> getDashboardCharts({
    required String type,
    int days = 7,
    String? moduleFilter,
  }) async {
    try {
      final response = await _dio.get('/client/charts', queryParameters: {
        'type': type,
        'days': days,
        if (moduleFilter != null) 'module': moduleFilter,
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

  /// Grupos de consumo: pedidos públicos
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoPedidos({
    String estado = 'abierto',
    int perPage = 10,
    int page = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/pedidos',
        queryParameters: {
          'estado': estado,
          'per_page': perPage,
          'page': page,
        },
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

  /// Grupos de consumo: mis pedidos (auth)
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoMisPedidos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/mis-pedidos',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: perfil
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoPerfil() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/perfil',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: suscripciones
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoSuscripciones() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/suscripciones',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: crear suscripción
  Future<ApiResponse<Map<String, dynamic>>> createGruposConsumoSuscripcion({
    required int productoId,
    String frecuencia = 'semanal',
    double cantidad = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/suscripciones',
        data: {
          'producto_id': productoId,
          'frecuencia': frecuencia,
          'cantidad': cantidad,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: pausar suscripción
  Future<ApiResponse<Map<String, dynamic>>> pauseGruposConsumoSuscripcion(int id) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/suscripciones/$id/pausar',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: cancelar suscripción
  Future<ApiResponse<Map<String, dynamic>>> cancelGruposConsumoSuscripcion(int id) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/suscripciones/$id/cancelar',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: historial de pedidos
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoHistorial() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/pedidos/historial',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: calendario de ciclos
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoCiclos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/ciclos/calendario',
        queryParameters: {
          'include_productores': '1',
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: productores cercanos
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoProductoresCercanos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/productores-cercanos',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: productores
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoProductores({
    bool soloEco = false,
    bool conEntrega = false,
    int perPage = 50,
    int page = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/productores',
        queryParameters: {
          'eco': soloEco ? '1' : '0',
          'con_entrega': conEntrega ? '1' : '0',
          'per_page': perPage,
          'page': page,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: productos
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoProductos({
    String? categoria,
    int? productorId,
    String? busqueda,
    int perPage = 50,
    int page = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/productos',
        queryParameters: {
          if (categoria != null && categoria.isNotEmpty) 'categoria': categoria,
          if (productorId != null && productorId > 0) 'productor_id': productorId,
          if (busqueda != null && busqueda.isNotEmpty) 'busqueda': busqueda,
          'per_page': perPage,
          'page': page,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: lista compra
  Future<ApiResponse<Map<String, dynamic>>> getGruposConsumoListaCompra() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/lista-compra',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: agregar a lista compra
  Future<ApiResponse<Map<String, dynamic>>> addGruposConsumoListaCompra({
    required int productoId,
    double cantidad = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/lista-compra/agregar',
        data: {
          'producto_id': productoId,
          'cantidad': cantidad,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: quitar de lista compra
  Future<ApiResponse<Map<String, dynamic>>> removeGruposConsumoListaCompra(int itemId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/gc/lista-compra/$itemId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Banco de tiempo: servicios públicos
  Future<ApiResponse<Map<String, dynamic>>> getBancoTiempoServicios({
    String categoria = 'todos',
    int limite = 10,
    int pagina = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios',
        queryParameters: {
          'categoria': categoria,
          'limite': limite,
          'pagina': pagina,
        },
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

  /// Banco de tiempo: mis servicios (auth)
  Future<ApiResponse<Map<String, dynamic>>> getBancoTiempoMisServicios() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/banco-tiempo/mis-servicios',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Marketplace: anuncios públicos
  Future<ApiResponse<Map<String, dynamic>>> getMarketplaceAnuncios({
    String categoria = 'todos',
    String tipo = 'todos',
    int limite = 10,
    int pagina = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/anuncios',
        queryParameters: {
          'categoria': categoria,
          'tipo': tipo,
          'limite': limite,
          'pagina': pagina,
        },
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

  /// Marketplace: mis anuncios (auth)
  Future<ApiResponse<Map<String, dynamic>>> getMarketplaceMisAnuncios() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/mis-anuncios',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: unirse a pedido
  Future<ApiResponse<Map<String, dynamic>>> joinGruposConsumoPedido({
    required int pedidoId,
    required double cantidad,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/pedidos/$pedidoId/unirse',
        data: {
          'cantidad': cantidad,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: marcar pagado (auth)
  Future<ApiResponse<Map<String, dynamic>>> markGruposConsumoPagado(int pedidoId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/pedidos/$pedidoId/marcar-pagado',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Grupos de consumo: marcar recogido (auth)
  Future<ApiResponse<Map<String, dynamic>>> markGruposConsumoRecogido(int pedidoId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/pedidos/$pedidoId/marcar-recogido',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Banco de tiempo: crear servicio
  Future<ApiResponse<Map<String, dynamic>>> createBancoTiempoServicio({
    required String titulo,
    required String descripcion,
    required String categoria,
    required double horasEstimadas,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios',
        data: {
          'titulo': titulo,
          'descripcion': descripcion,
          'categoria': categoria,
          'horas_estimadas': horasEstimadas,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Banco Tiempo: editar servicio
  Future<ApiResponse<Map<String, dynamic>>> updateBancoTiempoServicio({
    required int servicioId,
    String? titulo,
    String? descripcion,
    String? categoria,
    double? horasEstimadas,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.put(
        '$serverUrl/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios/$servicioId',
        data: {
          if (titulo != null) 'titulo': titulo,
          if (descripcion != null) 'descripcion': descripcion,
          if (categoria != null) 'categoria': categoria,
          if (horasEstimadas != null) 'horas_estimadas': horasEstimadas,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Banco Tiempo: eliminar servicio
  Future<ApiResponse<Map<String, dynamic>>> deleteBancoTiempoServicio(int servicioId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios/$servicioId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Marketplace: crear anuncio
  Future<ApiResponse<Map<String, dynamic>>> createMarketplaceAnuncio({
    required String titulo,
    required String descripcion,
    required String tipo,
    required String categoria,
    double? precio,
    String? estado,
    String? ubicacion,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/anuncios',
        data: {
          'titulo': titulo,
          'descripcion': descripcion,
          'tipo': tipo,
          'categoria': categoria,
          'precio': precio,
          'estado': estado,
          'ubicacion': ubicacion,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Marketplace: marcar anuncio como vendido (auth)
  /// Marketplace: editar anuncio
  Future<ApiResponse<Map<String, dynamic>>> updateMarketplaceAnuncio({
    required int anuncioId,
    String? titulo,
    String? descripcion,
    String? tipo,
    String? categoria,
    double? precio,
    String? ubicacion,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.put(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/anuncios/$anuncioId',
        data: {
          if (titulo != null) 'titulo': titulo,
          if (descripcion != null) 'descripcion': descripcion,
          if (tipo != null) 'tipo': tipo,
          if (categoria != null) 'categoria': categoria,
          if (precio != null) 'precio': precio,
          if (ubicacion != null) 'ubicacion': ubicacion,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> markMarketplaceSold(int anuncioId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/anuncios/$anuncioId/marcar-vendido',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Marketplace: eliminar anuncio (auth)
  Future<ApiResponse<Map<String, dynamic>>> deleteMarketplaceAnuncio(int anuncioId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/marketplace/anuncios/$anuncioId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // INCIDENCIAS
  // ==========================================

  /// Incidencias: obtener todas las incidencias
  Future<ApiResponse<Map<String, dynamic>>> getIncidencias({
    String? estado,
    String? prioridad,
    String? categoria,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias',
        queryParameters: {
          if (estado != null) 'estado': estado,
          if (prioridad != null) 'prioridad': prioridad,
          if (categoria != null) 'categoria': categoria,
          'limite': limite,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Incidencias: obtener mis incidencias (auth)
  Future<ApiResponse<Map<String, dynamic>>> getMisIncidencias() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias/mis-incidencias',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Incidencias: obtener detalle de una incidencia
  Future<ApiResponse<Map<String, dynamic>>> getIncidencia(int incidenciaId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias/$incidenciaId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Incidencias: crear nueva incidencia (auth)
  Future<ApiResponse<Map<String, dynamic>>> createIncidencia({
    required String titulo,
    required String descripcion,
    required String categoria,
    String? ubicacion,
    String prioridad = 'media',
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias',
        data: {
          'titulo': titulo,
          'descripcion': descripcion,
          'categoria': categoria,
          'ubicacion': ubicacion,
          'prioridad': prioridad,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Incidencias: agregar comentario (auth)
  Future<ApiResponse<Map<String, dynamic>>> addIncidenciaComment({
    required int incidenciaId,
    required String comentario,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias/$incidenciaId/comentarios',
        data: {
          'comentario': comentario,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Incidencias: actualizar estado (generalmente solo admin)
  Future<ApiResponse<Map<String, dynamic>>> updateIncidenciaEstado({
    required int incidenciaId,
    required String estado,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.put(
        '$serverUrl/wp-json/flavor-chat-ia/v1/incidencias/$incidenciaId/estado',
        data: {
          'estado': estado,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // EVENTOS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getEventos({
    String? tipo,
    String? categoria,
    String? desde,
    String? hasta,
    int limite = 20,
    String? estado,
    bool soloGratuitos = false,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor/v1/eventos',
        queryParameters: {
          if (tipo != null) 'tipo': tipo,
          if (categoria != null) 'categoria': categoria,
          if (desde != null) 'desde': desde,
          if (hasta != null) 'hasta': hasta,
          'limite': limite,
          if (estado != null) 'estado': estado,
          'solo_gratuitos': soloGratuitos ? '1' : '0',
        },
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

  Future<ApiResponse<Map<String, dynamic>>> getEvento(int id) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor/v1/eventos/$id',
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

  Future<ApiResponse<Map<String, dynamic>>> inscribirseEvento({
    required int eventoId,
    int numPlazas = 1,
    String? nombre,
    String? email,
    String? telefono,
    String? notas,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/eventos/$eventoId/inscribirse',
        data: {
          'num_plazas': numPlazas,
          if (nombre != null) 'nombre': nombre,
          if (email != null) 'email': email,
          if (telefono != null) 'telefono': telefono,
          if (notas != null) 'notas': notas,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getMisEventos({int limite = 20}) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/eventos/mis',
        queryParameters: {'limite': limite},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getMisInscripciones({int limite = 20}) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/eventos/mis-inscripciones',
        queryParameters: {'limite': limite},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CURSOS
  // ==========================================

  /// Cursos: obtener catálogo
  Future<ApiResponse<Map<String, dynamic>>> getCursos({
    String? categoria,
    String? nivel,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = <String, dynamic>{
        'limite': limite,
        if (categoria != null && categoria.isNotEmpty) 'categoria': categoria,
        if (nivel != null && nivel.isNotEmpty) 'nivel': nivel,
      };
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/cursos',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Cursos: obtener mis cursos inscritos
  Future<ApiResponse<Map<String, dynamic>>> getMisCursos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/cursos/mis-cursos',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Cursos: obtener detalle de un curso
  Future<ApiResponse<Map<String, dynamic>>> getCurso(int cursoId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/cursos/$cursoId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Cursos: inscribirse a un curso
  Future<ApiResponse<Map<String, dynamic>>> inscribirseCurso({
    required int cursoId,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/cursos/$cursoId/inscripcion',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // BIBLIOTECA
  // ==========================================

  /// Biblioteca: obtener catálogo de libros
  Future<ApiResponse<Map<String, dynamic>>> getBibliotecaCatalogo({
    String? genero,
    String? autor,
    bool? disponible,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = <String, dynamic>{
        'limite': limite,
        if (genero != null && genero.isNotEmpty) 'genero': genero,
        if (autor != null && autor.isNotEmpty) 'autor': autor,
        if (disponible != null) 'disponible': disponible ? 1 : 0,
      };
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/catalogo',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: obtener mis préstamos
  Future<ApiResponse<Map<String, dynamic>>> getBibliotecaMisPrestamos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/mis-prestamos',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: obtener mis reservas
  Future<ApiResponse<Map<String, dynamic>>> getBibliotecaReservas() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/reservas',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: obtener detalle de un libro
  Future<ApiResponse<Map<String, dynamic>>> getBibliotecaLibro(int libroId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/libros/$libroId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: solicitar préstamo de un libro
  Future<ApiResponse<Map<String, dynamic>>> solicitarBibliotecaPrestamo(int libroId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/prestamos',
        data: {'libro_id': libroId},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: reservar un libro
  Future<ApiResponse<Map<String, dynamic>>> reservarBibliotecaLibro(int libroId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/reservas',
        data: {'libro_id': libroId},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: renovar préstamo
  Future<ApiResponse<Map<String, dynamic>>> renovarBibliotecaPrestamo(int prestamoId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/prestamos/$prestamoId/renovar',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Biblioteca: cancelar reserva
  Future<ApiResponse<Map<String, dynamic>>> cancelarBibliotecaReserva(int reservaId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/biblioteca/reservas/$reservaId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // ESPACIOS COMUNES
  // ==========================================

  /// Espacios Comunes: obtener lista de espacios
  Future<ApiResponse<Map<String, dynamic>>> getEspaciosComunes({
    String? tipo,
    bool? disponible,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = <String, dynamic>{
        'limite': limite,
        if (tipo != null && tipo.isNotEmpty) 'tipo': tipo,
        if (disponible != null) 'disponible': disponible ? 1 : 0,
      };
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/espacios-comunes',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Espacios Comunes: obtener mis reservas
  Future<ApiResponse<Map<String, dynamic>>> getMisReservasEspacios() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/espacios-comunes/mis-reservas',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Espacios Comunes: obtener detalle de un espacio
  Future<ApiResponse<Map<String, dynamic>>> getEspacioComun(int espacioId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/espacios-comunes/$espacioId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Espacios Comunes: crear reserva
  Future<ApiResponse<Map<String, dynamic>>> crearReservaEspacio({
    required int espacioId,
    required String fecha,
    required String horaInicio,
    required String horaFin,
    String? motivo,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/espacios-comunes/reservas',
        data: {
          'espacio_id': espacioId,
          'fecha': fecha,
          'hora_inicio': horaInicio,
          'hora_fin': horaFin,
          if (motivo != null && motivo.isNotEmpty) 'motivo': motivo,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Espacios Comunes: cancelar reserva
  Future<ApiResponse<Map<String, dynamic>>> cancelarReservaEspacio(int reservaId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/espacios-comunes/reservas/$reservaId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // TALLERES
  // ==========================================

  /// Talleres: obtener catálogo
  Future<ApiResponse<Map<String, dynamic>>> getTalleres({
    String? categoria,
    String? nivel,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = <String, dynamic>{
        'limite': limite,
        if (categoria != null && categoria.isNotEmpty) 'categoria': categoria,
        if (nivel != null && nivel.isNotEmpty) 'nivel': nivel,
      };
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/talleres',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Talleres: obtener mis talleres inscritos
  Future<ApiResponse<Map<String, dynamic>>> getMisTalleres() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/talleres/mis-talleres',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Talleres: obtener detalle de un taller
  Future<ApiResponse<Map<String, dynamic>>> getTaller(int tallerId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/talleres/$tallerId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Talleres: inscribirse a un taller
  Future<ApiResponse<Map<String, dynamic>>> inscribirseTaller(int tallerId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/talleres/$tallerId/inscripcion',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Talleres: cancelar inscripción
  Future<ApiResponse<Map<String, dynamic>>> cancelarInscripcionTaller(int inscripcionId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete(
        '$serverUrl/wp-json/flavor-chat-ia/v1/talleres/inscripciones/$inscripcionId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // TRÁMITES
  // ==========================================

  /// Trámites: obtener catálogo
  Future<ApiResponse<Map<String, dynamic>>> getTramites({
    String? categoria,
    bool? online,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = <String, dynamic>{
        'limite': limite,
        if (categoria != null && categoria.isNotEmpty) 'categoria': categoria,
        if (online != null) 'online': online ? 1 : 0,
      };
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/tramites',
        queryParameters: queryParams,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Trámites: obtener mis solicitudes
  Future<ApiResponse<Map<String, dynamic>>> getMisSolicitudesTramites() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/tramites/mis-solicitudes',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Trámites: obtener detalle de un trámite
  Future<ApiResponse<Map<String, dynamic>>> getTramite(int tramiteId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/tramites/$tramiteId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Trámites: iniciar solicitud
  Future<ApiResponse<Map<String, dynamic>>> iniciarSolicitudTramite({
    required int tramiteId,
    String? observaciones,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/tramites/$tramiteId/solicitud',
        data: {
          if (observaciones != null && observaciones.isNotEmpty) 'observaciones': observaciones,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Trámites: obtener detalle de una solicitud
  Future<ApiResponse<Map<String, dynamic>>> getSolicitudTramite(int solicitudId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/tramites/solicitudes/$solicitudId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // HUERTOS URBANOS
  // ==========================================

  /// Huertos Urbanos: obtener dashboard
  Future<ApiResponse<Map<String, dynamic>>> getHuertosUrbanosDashboard() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/huertos-urbanos/dashboard',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Huertos Urbanos: solicitar parcela
  Future<ApiResponse<Map<String, dynamic>>> solicitarParcelaHuerto({
    required String tamanio,
    String? observaciones,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/huertos-urbanos/solicitar-parcela',
        data: {
          'tamanio': tamanio,
          if (observaciones != null && observaciones.isNotEmpty) 'observaciones': observaciones,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Huertos Urbanos: completar tarea
  Future<ApiResponse<Map<String, dynamic>>> completarTareaHuerto(int tareaId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/huertos-urbanos/tareas/$tareaId/completar',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Huertos Urbanos: contactar para intercambio
  Future<ApiResponse<Map<String, dynamic>>> contactarIntercambioHuerto({
    required int intercambioId,
    required String mensaje,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/huertos-urbanos/intercambios/$intercambioId/contactar',
        data: {'mensaje': mensaje},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // RECICLAJE
  // ==========================================

  /// Reciclaje: obtener dashboard
  Future<ApiResponse<Map<String, dynamic>>> getReciclajeDashboard() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat-ia/v1/reciclaje/dashboard',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // BICICLETAS COMPARTIDAS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getBicicletasCompartidas() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor-chat-ia/v1/bicicletas-compartidas');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> alquilarBicicleta(int estacionId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post('$serverUrl/wp-json/flavor-chat-ia/v1/bicicletas-compartidas/alquilar', data: {'estacion_id': estacionId});
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> finalizarAlquilerBicicleta() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post('$serverUrl/wp-json/flavor-chat-ia/v1/bicicletas-compartidas/finalizar');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // PARKINGS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getParkings() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor-chat-ia/v1/parkings');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> reservarParking({
    required int parkingId,
    required String fechaEntrada,
    required String fechaSalida,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/parkings/reservar',
        data: {'parking_id': parkingId, 'fecha_entrada': fechaEntrada, 'fecha_salida': fechaSalida},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> extenderReservaParking(int reservaId, int horas) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/parkings/reservas/$reservaId/extender',
        data: {'horas': horas},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> cancelarReservaParking(int reservaId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete('$serverUrl/wp-json/flavor-chat-ia/v1/parkings/reservas/$reservaId');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // AVISOS MUNICIPALES
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getAvisosMunicipales({String? categoria}) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final queryParams = categoria != null ? {'categoria': categoria} : null;
      final response = await _dio.get('$serverUrl/wp-json/flavor-chat-ia/v1/avisos-municipales', queryParameters: queryParams);
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> marcarAvisoLeido(int avisoId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post('$serverUrl/wp-json/flavor-chat-ia/v1/avisos-municipales/$avisoId/leer');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> actualizarSuscripcionesAvisos(List<String> categorias) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post('$serverUrl/wp-json/flavor-chat-ia/v1/avisos-municipales/suscripciones', data: {'categorias': categorias});
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // AYUDA VECINAL
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getAyudaVecinal() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor-chat-ia/v1/ayuda-vecinal');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> crearSolicitudAyuda({
    required String titulo,
    required String descripcion,
    required String categoria,
    String urgencia = 'normal',
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor-chat-ia/v1/ayuda-vecinal/solicitudes',
        data: {'titulo': titulo, 'descripcion': descripcion, 'categoria': categoria, 'urgencia': urgencia},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> ofrecerAyuda(int solicitudId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post('$serverUrl/wp-json/flavor-chat-ia/v1/ayuda-vecinal/solicitudes/$solicitudId/ofrecer');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> cancelarSolicitudAyuda(int solicitudId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.delete('$serverUrl/wp-json/flavor-chat-ia/v1/ayuda-vecinal/solicitudes/$solicitudId');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // SOCIOS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getSociosPerfil() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/socios/perfil',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getSociosCuotas({
    String? estado,
    int limite = 12,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/socios/cuotas',
        queryParameters: {
          if (estado != null) 'estado': estado,
          'limite': limite,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> updateSociosDatos({
    String? telefono,
    String? direccion,
    String? iban,
    String? notas,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/socios/actualizar',
        data: {
          if (telefono != null) 'telefono': telefono,
          if (direccion != null) 'direccion': direccion,
          if (iban != null) 'iban': iban,
          if (notas != null) 'notas': notas,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> pagarSociosCuota({
    required int cuotaId,
    String? metodoPago,
    String? referencia,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/socios/cuotas/pagar',
        data: {
          'cuota_id': cuotaId,
          if (metodoPago != null) 'metodo_pago': metodoPago,
          if (referencia != null) 'referencia': referencia,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // FACTURAS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getFacturas({
    String? estado,
    int limite = 50,
    int pagina = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat/v1/facturas',
        queryParameters: {
          if (estado != null) 'estado': estado,
          'limite': limite,
          'pagina': pagina,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getFactura(int id) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat/v1/facturas/$id',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getFacturaPagos(int id) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor-chat/v1/facturas/$id/pagos',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<String> getFacturaPdfUrl(int id) async {
    final serverUrl = await ServerConfig.getServerUrl();
    return '$serverUrl/wp-json/flavor-chat/v1/facturas/$id/pdf';
  }

  // ==========================================
  // CHAT GRUPOS
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getChatGrupos() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor/v1/chat-grupos');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> explorarChatGrupos({
    String? categoria,
    String? busqueda,
    int pagina = 1,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor/v1/chat-grupos/explorar',
        queryParameters: {
          if (categoria != null) 'categoria': categoria,
          if (busqueda != null) 'busqueda': busqueda,
          'pagina': pagina,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getChatGrupoMensajes({
    required int grupoId,
    int limite = 50,
    String? antesDe,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await Dio().get(
        '$serverUrl/wp-json/flavor/v1/chat-grupos/$grupoId/mensajes',
        queryParameters: {
          'limite': limite,
          if (antesDe != null) 'antes_de': antesDe,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> sendChatGrupoMensaje({
    required int grupoId,
    required String mensaje,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/chat-grupos/$grupoId/mensajes',
        data: {'mensaje': mensaje},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // CHAT INTERNO
  // ==========================================

  Future<ApiResponse<Map<String, dynamic>>> getChatInternoConversaciones() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor/v1/chat-interno');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getChatInternoMensajes({
    required int conversacionId,
    int limite = 50,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/chat-interno/conversacion/$conversacionId/mensajes',
        queryParameters: {'limite': limite},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> sendChatInternoMensaje({
    required int conversacionId,
    required String mensaje,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/chat-interno/conversacion/$conversacionId/enviar',
        data: {'mensaje': mensaje},
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Envia un mensaje cifrado E2E
  Future<ApiResponse<Map<String, dynamic>>> sendChatInternoMensajeCifrado({
    required int conversacionId,
    required String ciphertext,
    required String e2eHeader,
    required int e2eVersion,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/chat-interno/conversacion/$conversacionId/enviar',
        data: {
          'mensaje': '', // Texto plano vacio
          'cifrado': 1,
          'ciphertext': ciphertext,
          'e2e_header': e2eHeader,
          'e2e_version': e2eVersion,
        },
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // MÓDULOS
  // ==========================================

  /// Obtiene información de un módulo específico
  Future<ApiResponse<Map<String, dynamic>>> getModuleInfo(String moduleId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/modules/$moduleId',
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Ejecuta una acción de un módulo
  Future<ApiResponse<Map<String, dynamic>>> executeModuleAction({
    required String moduleId,
    required String actionName,
    Map<String, dynamic>? params,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/modules/$moduleId/actions/$actionName',
        data: params,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // HELPERS
  // ==========================================

  // ===== FICHAJE EMPLEADOS =====

  /// Registrar entrada de fichaje
  Future<ApiResponse<Map<String, dynamic>>> registrarEntrada({
    String? notas,
    double? latitud,
    double? longitud,
    String? dispositivo,
  }) async {
    try {
      final response = await _dio.post('/flavor/v1/fichaje/entrada', data: {
        if (notas != null) 'notas': notas,
        if (latitud != null) 'latitud': latitud,
        if (longitud != null) 'longitud': longitud,
        if (dispositivo != null) 'dispositivo': dispositivo,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Registrar salida de fichaje
  Future<ApiResponse<Map<String, dynamic>>> registrarSalida({
    String? notas,
    double? latitud,
    double? longitud,
    String? dispositivo,
  }) async {
    try {
      final response = await _dio.post('/flavor/v1/fichaje/salida', data: {
        if (notas != null) 'notas': notas,
        if (latitud != null) 'latitud': latitud,
        if (longitud != null) 'longitud': longitud,
        if (dispositivo != null) 'dispositivo': dispositivo,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener estado actual del fichaje
  Future<ApiResponse<Map<String, dynamic>>> getFichajeEstado() async {
    try {
      final response = await _dio.get('/flavor/v1/fichaje/estado');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener historial de fichajes
  Future<ApiResponse<Map<String, dynamic>>> getFichajeHistorial({
    String? desde,
    String? hasta,
    String? tipo,
  }) async {
    try {
      final response = await _dio.get('/flavor/v1/fichaje/historial', queryParameters: {
        if (desde != null) 'desde': desde,
        if (hasta != null) 'hasta': hasta,
        if (tipo != null) 'tipo': tipo,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener todos los fichajes (admin)
  Future<ApiResponse<Map<String, dynamic>>> getFichajes({
    String? desde,
    String? hasta,
    int? usuarioId,
    int? pagina,
    int? limite,
  }) async {
    try {
      final response = await _dio.get('/flavor/v1/fichaje/admin/listado', queryParameters: {
        if (desde != null) 'desde': desde,
        if (hasta != null) 'hasta': hasta,
        if (usuarioId != null) 'usuario_id': usuarioId,
        if (pagina != null) 'pagina': pagina,
        if (limite != null) 'limite': limite,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ===== BARES =====

  /// Obtener listado de bares
  Future<ApiResponse<Map<String, dynamic>>> getBares({
    int? limite,
    int? pagina,
  }) async {
    try {
      final response = await _dio.get('/flavor/v1/bares', queryParameters: {
        if (limite != null) 'per_page': limite,
        if (pagina != null) 'page': pagina,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener detalle de un bar
  Future<ApiResponse<Map<String, dynamic>>> getBarDetail(int id) async {
    try {
      final response = await _dio.get('/flavor/v1/bares/$id');
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ===== WOOCOMMERCE =====

  /// Obtener pedidos de WooCommerce
  Future<ApiResponse<Map<String, dynamic>>> getWooPedidos({
    String? estado,
    int? limite,
    int? pagina,
  }) async {
    try {
      final response = await _dio.get('/flavor-chat-ia/v1/woocommerce/pedidos', queryParameters: {
        if (estado != null) 'estado': estado,
        if (limite != null) 'limite': limite,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Obtener productos de WooCommerce
  Future<ApiResponse<Map<String, dynamic>>> getWooProductos({
    String? busqueda,
    int? limite,
    int? pagina,
  }) async {
    try {
      final response = await _dio.get('/flavor-chat-ia/v1/woocommerce/productos', queryParameters: {
        if (busqueda != null) 'busqueda': busqueda,
        if (limite != null) 'limite': limite,
        if (pagina != null) 'pagina': pagina,
      });
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  // ==========================================
  // METODOS GENERICOS
  // ==========================================

  /// Metodo GET generico para endpoints personalizados
  /// [endpoint] - El endpoint relativo a la baseUrl (ej: '/client/dashboard')
  Future<ApiResponse<Map<String, dynamic>>> get(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Metodo POST generico para endpoints personalizados
  /// [endpoint] - El endpoint relativo a la baseUrl
  /// [data] - Datos a enviar en el body
  Future<ApiResponse<Map<String, dynamic>>> post(
    String endpoint, {
    required Map<String, dynamic> data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.post(
        endpoint,
        data: data,
        queryParameters: queryParameters,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Metodo PUT generico para endpoints personalizados
  Future<ApiResponse<Map<String, dynamic>>> put(
    String endpoint, {
    required Map<String, dynamic> data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.put(
        endpoint,
        data: data,
        queryParameters: queryParameters,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

  /// Metodo DELETE generico para endpoints personalizados
  Future<ApiResponse<Map<String, dynamic>>> delete(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.delete(
        endpoint,
        queryParameters: queryParameters,
      );
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(_handleError(e));
    }
  }

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
        return 'Tiempo de conexion agotado';
      case DioExceptionType.connectionError:
        return 'Error de conexion. Verifica tu internet.';
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
