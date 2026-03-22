import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';

/// Evento de analytics
class AnalyticsEvent {
  final String name;
  final Map<String, dynamic> properties;
  final DateTime timestamp;
  final String? userId;
  final String? sessionId;

  AnalyticsEvent({
    required this.name,
    this.properties = const {},
    DateTime? timestamp,
    this.userId,
    this.sessionId,
  }) : timestamp = timestamp ?? DateTime.now();

  Map<String, dynamic> toJson() {
    return {
      'event': name,
      'properties': properties,
      'timestamp': timestamp.toIso8601String(),
      if (userId != null) 'user_id': userId,
      if (sessionId != null) 'session_id': sessionId,
    };
  }
}

/// Servicio de Analytics para tracking de eventos
class AnalyticsService {
  static const String _prefsKeyQueue = 'analytics_queue';
  static const String _prefsKeySessionId = 'analytics_session_id';
  static const String _prefsKeyUserId = 'analytics_user_id';
  static const int _batchSize = 20;
  static const Duration _flushInterval = Duration(minutes: 5);

  /// Cliente API
  final ApiClient _apiClient;

  /// ID de dispositivo
  final String deviceId;

  /// Cola de eventos pendientes
  final List<AnalyticsEvent> _eventQueue = [];

  /// ID de sesión actual
  String? _sessionId;

  /// ID de usuario actual
  String? _userId;

  /// Timer para flush automático
  Timer? _flushTimer;

  /// Versión de la app
  String? _appVersion;

  /// Plataforma
  String? _platform;

  /// Si está habilitado
  bool _enabled = true;

  AnalyticsService({
    required ApiClient apiClient,
    required this.deviceId,
  }) : _apiClient = apiClient;

  /// Inicializa el servicio
  Future<void> initialize({
    String? appVersion,
    String? platform,
  }) async {
    _appVersion = appVersion;
    _platform = platform;

    // Cargar sesión y usuario guardados
    await _loadState();

    // Generar nueva sesión si no existe
    _sessionId ??= _generateSessionId();

    // Cargar eventos pendientes
    await _loadPendingEvents();

    // Iniciar timer de flush automático
    _startFlushTimer();

    debugPrint('[Analytics] Initialized. Session: $_sessionId');
  }

  /// Carga estado guardado
  Future<void> _loadState() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _sessionId = prefs.getString(_prefsKeySessionId);
      _userId = prefs.getString(_prefsKeyUserId);
    } catch (e) {
      debugPrint('[Analytics] Error loading state: $e');
    }
  }

  /// Guarda estado
  Future<void> _saveState() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (_sessionId != null) {
        await prefs.setString(_prefsKeySessionId, _sessionId!);
      }
      if (_userId != null) {
        await prefs.setString(_prefsKeyUserId, _userId!);
      }
    } catch (e) {
      debugPrint('[Analytics] Error saving state: $e');
    }
  }

  /// Carga eventos pendientes de enviar
  Future<void> _loadPendingEvents() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queueJson = prefs.getString(_prefsKeyQueue);
      if (queueJson != null) {
        final list = json.decode(queueJson) as List<dynamic>;
        for (final item in list) {
          final map = item as Map<String, dynamic>;
          _eventQueue.add(AnalyticsEvent(
            name: map['event'] as String,
            properties: map['properties'] as Map<String, dynamic>? ?? {},
            timestamp: DateTime.parse(map['timestamp'] as String),
            userId: map['user_id'] as String?,
            sessionId: map['session_id'] as String?,
          ));
        }
        debugPrint('[Analytics] Loaded ${_eventQueue.length} pending events');
      }
    } catch (e) {
      debugPrint('[Analytics] Error loading pending events: $e');
    }
  }

  /// Guarda eventos pendientes
  Future<void> _savePendingEvents() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queueJson = json.encode(_eventQueue.map((e) => e.toJson()).toList());
      await prefs.setString(_prefsKeyQueue, queueJson);
    } catch (e) {
      debugPrint('[Analytics] Error saving pending events: $e');
    }
  }

  /// Inicia timer de flush automático
  void _startFlushTimer() {
    _flushTimer?.cancel();
    _flushTimer = Timer.periodic(_flushInterval, (_) {
      flush();
    });
  }

  /// Genera ID de sesión
  String _generateSessionId() {
    return 'session_${DateTime.now().millisecondsSinceEpoch}';
  }

  // =========================================================================
  // MÉTODOS PÚBLICOS
  // =========================================================================

  /// Establece el ID de usuario
  Future<void> setUserId(String? userId) async {
    _userId = userId;
    await _saveState();
    
    if (userId != null) {
      track('user_identified', properties: {'user_id': userId});
    }
  }

  /// Obtiene el ID de usuario actual
  String? get userId => _userId;

  /// Obtiene el ID de sesión actual
  String? get sessionId => _sessionId;

  /// Inicia nueva sesión
  Future<void> startNewSession() async {
    await track('session_end');
    
    _sessionId = _generateSessionId();
    await _saveState();
    
    await track('session_start');
  }

  /// Habilita/deshabilita tracking
  void setEnabled(bool enabled) {
    _enabled = enabled;
  }

  /// Registra un evento
  Future<void> track(
    String eventName, {
    Map<String, dynamic> properties = const {},
  }) async {
    if (!_enabled) return;

    final enrichedProperties = {
      ...properties,
      'device_id': deviceId,
      if (_appVersion != null) 'app_version': _appVersion,
      if (_platform != null) 'platform': _platform,
    };

    final event = AnalyticsEvent(
      name: eventName,
      properties: enrichedProperties,
      userId: _userId,
      sessionId: _sessionId,
    );

    _eventQueue.add(event);
    debugPrint('[Analytics] Tracked: $eventName');

    // Flush si alcanzamos el tamaño del batch
    if (_eventQueue.length >= _batchSize) {
      await flush();
    } else {
      await _savePendingEvents();
    }
  }

  /// Registra vista de pantalla
  Future<void> trackScreen(String screenName, {Map<String, dynamic>? properties}) async {
    await track('screen_view', properties: {
      'screen_name': screenName,
      ...?properties,
    });
  }

  /// Registra uso de módulo
  Future<void> trackModuleUsage(String moduleName, String action, {Map<String, dynamic>? properties}) async {
    await track('module_$action', properties: {
      'module': moduleName,
      ...?properties,
    });
  }

  /// Registra error
  Future<void> trackError(String errorType, String message, {String? stackTrace}) async {
    await track('error', properties: {
      'error_type': errorType,
      'message': message,
      if (stackTrace != null) 'stack_trace': stackTrace.substring(0, 500),
    });
  }

  /// Registra tiempo de carga
  Future<void> trackTiming(String category, String variable, int milliseconds) async {
    await track('timing', properties: {
      'category': category,
      'variable': variable,
      'value': milliseconds,
    });
  }

  /// Envía eventos pendientes al servidor
  Future<bool> flush() async {
    if (_eventQueue.isEmpty) return true;

    final eventsToSend = List<AnalyticsEvent>.from(_eventQueue);
    
    try {
      final response = await _apiClient.postData('/analytics/batch', {
        'events': eventsToSend.map((e) => e.toJson()).toList(),
        'device_id': deviceId,
      });

      if (response.success) {
        _eventQueue.removeWhere((e) => eventsToSend.contains(e));
        await _savePendingEvents();
        debugPrint('[Analytics] Flushed ${eventsToSend.length} events');
        return true;
      }
    } catch (e) {
      debugPrint('[Analytics] Flush error: $e');
    }

    return false;
  }

  /// Obtiene número de eventos pendientes
  int get pendingEventsCount => _eventQueue.length;

  /// Limpia todos los eventos pendientes
  Future<void> clearPendingEvents() async {
    _eventQueue.clear();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyQueue);
  }

  /// Limpia recursos
  void dispose() {
    _flushTimer?.cancel();
    flush(); // Intentar enviar eventos pendientes
  }
}

/// Eventos predefinidos comunes
class AnalyticsEvents {
  static const String appOpen = 'app_open';
  static const String appClose = 'app_close';
  static const String sessionStart = 'session_start';
  static const String sessionEnd = 'session_end';
  static const String screenView = 'screen_view';
  static const String buttonClick = 'button_click';
  static const String formSubmit = 'form_submit';
  static const String search = 'search';
  static const String share = 'share';
  static const String login = 'login';
  static const String logout = 'logout';
  static const String signup = 'signup';
  static const String purchase = 'purchase';
  static const String addToCart = 'add_to_cart';
  static const String error = 'error';
  static const String notification = 'notification_received';
  static const String notificationClick = 'notification_click';
}
