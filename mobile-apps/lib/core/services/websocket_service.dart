import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../config/app_config.dart';

/// Estados de conexión WebSocket
enum WebSocketState {
  disconnected,
  connecting,
  connected,
  reconnecting,
}

/// Evento WebSocket recibido
class WebSocketEvent {
  final String type;
  final String? module;
  final String? action;
  final Map<String, dynamic> data;
  final DateTime timestamp;

  WebSocketEvent({
    required this.type,
    this.module,
    this.action,
    required this.data,
    DateTime? timestamp,
  }) : timestamp = timestamp ?? DateTime.now();

  factory WebSocketEvent.fromJson(Map<String, dynamic> json) {
    return WebSocketEvent(
      type: json['type'] ?? 'unknown',
      module: json['module'],
      action: json['action'],
      data: json['data'] ?? {},
      timestamp: json['timestamp'] != null
          ? DateTime.tryParse(json['timestamp'])
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
    'type': type,
    if (module != null) 'module': module,
    if (action != null) 'action': action,
    'data': data,
    'timestamp': timestamp.toIso8601String(),
  };
}

/// Servicio de WebSocket para sincronización en tiempo real
class WebSocketService {
  static WebSocketService? _instance;

  /// Canal WebSocket
  WebSocketChannel? _channel;

  /// Estado actual
  WebSocketState _state = WebSocketState.disconnected;

  /// Token de autenticación
  String? _authToken;

  /// URL base del servidor
  String? _serverUrl;

  /// Intentos de reconexión
  int _reconnectAttempts = 0;
  static const int _maxReconnectAttempts = 5;
  static const Duration _reconnectDelay = Duration(seconds: 5);

  /// Timer de heartbeat
  Timer? _heartbeatTimer;
  static const Duration _heartbeatInterval = Duration(seconds: 30);

  /// Timer de reconexión
  Timer? _reconnectTimer;

  /// Stream controllers
  final StreamController<WebSocketState> _stateController =
      StreamController<WebSocketState>.broadcast();
  final StreamController<WebSocketEvent> _eventController =
      StreamController<WebSocketEvent>.broadcast();

  /// Suscripciones activas por módulo
  final Set<String> _subscriptions = {};

  /// Callbacks por tipo de evento
  final Map<String, List<void Function(WebSocketEvent)>> _eventListeners = {};

  WebSocketService._();

  factory WebSocketService() {
    _instance ??= WebSocketService._();
    return _instance!;
  }

  /// Estado actual
  WebSocketState get state => _state;

  /// Stream de cambios de estado
  Stream<WebSocketState> get stateStream => _stateController.stream;

  /// Stream de eventos
  Stream<WebSocketEvent> get eventStream => _eventController.stream;

  /// ¿Está conectado?
  bool get isConnected => _state == WebSocketState.connected;

  /// Configurar conexión
  void configure({
    required String serverUrl,
    required String authToken,
  }) {
    _serverUrl = serverUrl;
    _authToken = authToken;
  }

  /// Conectar al servidor WebSocket
  Future<bool> connect() async {
    if (_state == WebSocketState.connected || _state == WebSocketState.connecting) {
      return _state == WebSocketState.connected;
    }

    if (_serverUrl == null || _authToken == null) {
      debugPrint('[WebSocketService] No configurado');
      return false;
    }

    _setState(WebSocketState.connecting);

    try {
      // Construir URL con token
      final wsUrl = _buildWebSocketUrl();
      debugPrint('[WebSocketService] Conectando a: $wsUrl');

      _channel = WebSocketChannel.connect(Uri.parse(wsUrl));

      // Escuchar mensajes
      _channel!.stream.listen(
        _handleMessage,
        onError: _handleError,
        onDone: _handleDisconnect,
      );

      // Enviar handshake
      _sendHandshake();

      // Iniciar heartbeat
      _startHeartbeat();

      _setState(WebSocketState.connected);
      _reconnectAttempts = 0;

      // Resuscribir a módulos previos
      _resubscribe();

      return true;
    } catch (e) {
      debugPrint('[WebSocketService] Error conectando: $e');
      _setState(WebSocketState.disconnected);
      _scheduleReconnect();
      return false;
    }
  }

  /// Desconectar
  Future<void> disconnect() async {
    _reconnectTimer?.cancel();
    _heartbeatTimer?.cancel();

    if (_channel != null) {
      await _channel!.sink.close();
      _channel = null;
    }

    _setState(WebSocketState.disconnected);
    _reconnectAttempts = 0;
  }

  /// Suscribirse a eventos de un módulo
  void subscribe(String module) {
    _subscriptions.add(module);

    if (isConnected) {
      _send({
        'type': 'subscribe',
        'module': module,
      });
    }
  }

  /// Desuscribirse de un módulo
  void unsubscribe(String module) {
    _subscriptions.remove(module);

    if (isConnected) {
      _send({
        'type': 'unsubscribe',
        'module': module,
      });
    }
  }

  /// Enviar evento al servidor
  void sendEvent(WebSocketEvent event) {
    _send(event.toJson());
  }

  /// Enviar datos al servidor
  void send(String type, Map<String, dynamic> data, {String? module}) {
    _send({
      'type': type,
      if (module != null) 'module': module,
      'data': data,
      'timestamp': DateTime.now().toIso8601String(),
    });
  }

  /// Registrar listener para un tipo de evento
  void on(String eventType, void Function(WebSocketEvent) callback) {
    _eventListeners.putIfAbsent(eventType, () => []);
    _eventListeners[eventType]!.add(callback);
  }

  /// Eliminar listener
  void off(String eventType, void Function(WebSocketEvent) callback) {
    _eventListeners[eventType]?.remove(callback);
  }

  /// Construir URL de WebSocket
  String _buildWebSocketUrl() {
    final baseUrl = _serverUrl!.replaceFirst('https://', 'wss://').replaceFirst('http://', 'ws://');
    return '$baseUrl/wp-json/flavor-app/v2/ws?token=$_authToken';
  }

  /// Enviar handshake inicial
  void _sendHandshake() {
    _send({
      'type': 'handshake',
      'data': {
        'app_version': AppConfig.appVersion,
        'platform': defaultTargetPlatform.toString(),
      },
    });
  }

  /// Enviar mensaje
  void _send(Map<String, dynamic> message) {
    if (!isConnected || _channel == null) {
      debugPrint('[WebSocketService] No conectado, mensaje descartado');
      return;
    }

    try {
      final jsonStr = jsonEncode(message);
      _channel!.sink.add(jsonStr);
    } catch (e) {
      debugPrint('[WebSocketService] Error enviando: $e');
    }
  }

  /// Manejar mensaje recibido
  void _handleMessage(dynamic message) {
    try {
      final data = jsonDecode(message as String) as Map<String, dynamic>;
      final event = WebSocketEvent.fromJson(data);

      debugPrint('[WebSocketService] Evento: ${event.type} - ${event.module}');

      // Emitir al stream general
      _eventController.add(event);

      // Llamar listeners específicos
      final listeners = _eventListeners[event.type];
      if (listeners != null) {
        for (final listener in listeners) {
          listener(event);
        }
      }

      // Manejar tipos especiales
      _handleSpecialEvent(event);
    } catch (e) {
      debugPrint('[WebSocketService] Error parseando mensaje: $e');
    }
  }

  /// Manejar eventos especiales
  void _handleSpecialEvent(WebSocketEvent event) {
    switch (event.type) {
      case 'pong':
        // Respuesta al heartbeat, todo OK
        break;
      case 'error':
        debugPrint('[WebSocketService] Error del servidor: ${event.data}');
        break;
      case 'force_disconnect':
        debugPrint('[WebSocketService] Desconexión forzada: ${event.data['reason']}');
        disconnect();
        break;
    }
  }

  /// Manejar error
  void _handleError(dynamic error) {
    debugPrint('[WebSocketService] Error: $error');
    _setState(WebSocketState.disconnected);
    _scheduleReconnect();
  }

  /// Manejar desconexión
  void _handleDisconnect() {
    debugPrint('[WebSocketService] Desconectado');
    _setState(WebSocketState.disconnected);
    _heartbeatTimer?.cancel();
    _scheduleReconnect();
  }

  /// Programar reconexión
  void _scheduleReconnect() {
    if (_reconnectAttempts >= _maxReconnectAttempts) {
      debugPrint('[WebSocketService] Máximo de intentos alcanzado');
      return;
    }

    _reconnectTimer?.cancel();
    _setState(WebSocketState.reconnecting);

    final delay = _reconnectDelay * (_reconnectAttempts + 1);
    debugPrint('[WebSocketService] Reintentando en ${delay.inSeconds}s');

    _reconnectTimer = Timer(delay, () {
      _reconnectAttempts++;
      connect();
    });
  }

  /// Resuscribir a módulos
  void _resubscribe() {
    for (final module in _subscriptions) {
      _send({
        'type': 'subscribe',
        'module': module,
      });
    }
  }

  /// Iniciar heartbeat
  void _startHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = Timer.periodic(_heartbeatInterval, (_) {
      if (isConnected) {
        _send({'type': 'ping'});
      }
    });
  }

  /// Actualizar estado
  void _setState(WebSocketState newState) {
    if (_state != newState) {
      _state = newState;
      _stateController.add(newState);
    }
  }

  /// Limpiar recursos
  void dispose() {
    disconnect();
    _stateController.close();
    _eventController.close();
    _eventListeners.clear();
  }
}

/// Extensión para facilitar suscripción a módulos
extension WebSocketModuleExtension on WebSocketService {
  /// Suscribirse a eventos de CRUD de un módulo
  void subscribeToCrud(String module, {
    void Function(WebSocketEvent)? onCreate,
    void Function(WebSocketEvent)? onUpdate,
    void Function(WebSocketEvent)? onDelete,
  }) {
    subscribe(module);

    if (onCreate != null) {
      on('${module}_created', onCreate);
    }
    if (onUpdate != null) {
      on('${module}_updated', onUpdate);
    }
    if (onDelete != null) {
      on('${module}_deleted', onDelete);
    }
  }

  /// Notificar cambio CRUD
  void notifyCrudChange(String module, String action, Map<String, dynamic> data) {
    send('${module}_$action', data, module: module);
  }
}
