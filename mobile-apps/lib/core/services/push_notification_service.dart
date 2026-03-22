import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';

/// Resultado de registro de token
class PushRegistrationResult {
  final bool success;
  final String? deviceKey;
  final String? error;

  PushRegistrationResult({
    required this.success,
    this.deviceKey,
    this.error,
  });
}

/// Configuración de notificación recibida
class PushNotification {
  final String? title;
  final String? body;
  final String? imageUrl;
  final Map<String, dynamic> data;
  final DateTime receivedAt;

  PushNotification({
    this.title,
    this.body,
    this.imageUrl,
    required this.data,
    required this.receivedAt,
  });

  factory PushNotification.fromFcmMessage(Map<String, dynamic> message) {
    final notification = message['notification'] as Map<String, dynamic>?;
    final data = message['data'] as Map<String, dynamic>? ?? {};

    return PushNotification(
      title: notification?['title'] as String?,
      body: notification?['body'] as String?,
      imageUrl: notification?['image'] as String?,
      data: data,
      receivedAt: DateTime.now(),
    );
  }
}

/// Servicio para gestionar Push Notifications con Firebase Cloud Messaging
class PushNotificationService {
  static const String _apiNamespace = '/wp-json/flavor-app/v2';
  static const String _prefsKeyToken = 'fcm_token';
  static const String _prefsKeyDeviceKey = 'push_device_key';
  static const String _prefsKeyTopics = 'push_topics';

  /// Cliente API
  final ApiClient _apiClient;

  /// Token FCM actual
  String? _fcmToken;

  /// Clave de dispositivo asignada por el servidor
  String? _deviceKey;

  /// Topics suscritos
  final Set<String> _subscribedTopics = {};

  /// Callbacks de notificaciones
  final List<void Function(PushNotification)> _notificationListeners = [];

  /// Callbacks de token actualizado
  final List<void Function(String)> _tokenListeners = [];

  PushNotificationService({required ApiClient apiClient}) : _apiClient = apiClient;

  /// Inicializa el servicio de notificaciones
  Future<void> initialize() async {
    // Cargar token y topics guardados
    await _loadSavedState();

    debugPrint('[PushService] Initialized');
    debugPrint('[PushService] Token: ${_fcmToken?.substring(0, 20)}...');
    debugPrint('[PushService] Topics: $_subscribedTopics');
  }

  /// Carga el estado guardado
  Future<void> _loadSavedState() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _fcmToken = prefs.getString(_prefsKeyToken);
      _deviceKey = prefs.getString(_prefsKeyDeviceKey);
      
      final topicsJson = prefs.getString(_prefsKeyTopics);
      if (topicsJson != null) {
        final topics = json.decode(topicsJson) as List<dynamic>;
        _subscribedTopics.addAll(topics.map((e) => e.toString()));
      }
    } catch (e) {
      debugPrint('[PushService] Error loading state: $e');
    }
  }

  /// Guarda el estado actual
  Future<void> _saveState() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (_fcmToken != null) {
        await prefs.setString(_prefsKeyToken, _fcmToken!);
      }
      if (_deviceKey != null) {
        await prefs.setString(_prefsKeyDeviceKey, _deviceKey!);
      }
      await prefs.setString(_prefsKeyTopics, json.encode(_subscribedTopics.toList()));
    } catch (e) {
      debugPrint('[PushService] Error saving state: $e');
    }
  }

  /// Actualiza el token FCM y lo registra en el servidor
  Future<PushRegistrationResult> updateToken(String token, {String? deviceId}) async {
    _fcmToken = token;

    // Notificar a los listeners
    for (final listener in _tokenListeners) {
      listener(token);
    }

    // Registrar en el servidor
    return await registerDevice(deviceId: deviceId);
  }

  /// Registra el dispositivo en el servidor
  Future<PushRegistrationResult> registerDevice({String? deviceId}) async {
    if (_fcmToken == null) {
      return PushRegistrationResult(
        success: false,
        error: 'No FCM token available',
      );
    }

    try {
      final response = await _apiClient.postData('/push/register', {
        'token': _fcmToken,
        'device_id': deviceId ?? _generateDeviceId(),
        'platform': Platform.isAndroid ? 'android' : 'ios',
        'app_version': '1.0.0', // TODO: Obtener versión real
      });

      if (response.success && response.data != null) {
        _deviceKey = response.data['device_key'] as String?;
        await _saveState();

        return PushRegistrationResult(
          success: true,
          deviceKey: _deviceKey,
        );
      }

      return PushRegistrationResult(
        success: false,
        error: response.error ?? 'Registration failed',
      );
    } catch (e) {
      return PushRegistrationResult(
        success: false,
        error: e.toString(),
      );
    }
  }

  /// Elimina el registro del dispositivo
  Future<bool> unregisterDevice() async {
    if (_fcmToken == null) return true;

    try {
      final response = await _apiClient.postData('/push/unregister', {
        'token': _fcmToken,
      });

      if (response.success) {
        _fcmToken = null;
        _deviceKey = null;
        _subscribedTopics.clear();
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.remove(_prefsKeyToken);
        await prefs.remove(_prefsKeyDeviceKey);
        await prefs.remove(_prefsKeyTopics);
      }

      return response.success;
    } catch (e) {
      debugPrint('[PushService] Unregister error: $e');
      return false;
    }
  }

  /// Suscribe a un topic
  Future<bool> subscribeToTopic(String topic) async {
    if (_fcmToken == null) return false;

    try {
      final response = await _apiClient.postData('/push/topics/subscribe', {
        'token': _fcmToken,
        'topic': topic,
      });

      if (response.success) {
        _subscribedTopics.add(topic);
        await _saveState();
        debugPrint('[PushService] Subscribed to topic: $topic');
      }

      return response.success;
    } catch (e) {
      debugPrint('[PushService] Subscribe error: $e');
      return false;
    }
  }

  /// Desuscribe de un topic
  Future<bool> unsubscribeFromTopic(String topic) async {
    if (_fcmToken == null) return false;

    try {
      final response = await _apiClient.postData('/push/topics/unsubscribe', {
        'token': _fcmToken,
        'topic': topic,
      });

      if (response.success) {
        _subscribedTopics.remove(topic);
        await _saveState();
        debugPrint('[PushService] Unsubscribed from topic: $topic');
      }

      return response.success;
    } catch (e) {
      debugPrint('[PushService] Unsubscribe error: $e');
      return false;
    }
  }

  /// Procesa una notificación recibida
  void handleNotificationReceived(Map<String, dynamic> message) {
    final notification = PushNotification.fromFcmMessage(message);
    
    debugPrint('[PushService] Notification received: ${notification.title}');

    // Notificar a los listeners
    for (final listener in _notificationListeners) {
      listener(notification);
    }
  }

  /// Registra un listener para notificaciones
  void addNotificationListener(void Function(PushNotification) listener) {
    _notificationListeners.add(listener);
  }

  /// Elimina un listener de notificaciones
  void removeNotificationListener(void Function(PushNotification) listener) {
    _notificationListeners.remove(listener);
  }

  /// Registra un listener para actualizaciones de token
  void addTokenListener(void Function(String) listener) {
    _tokenListeners.add(listener);
  }

  /// Elimina un listener de token
  void removeTokenListener(void Function(String) listener) {
    _tokenListeners.remove(listener);
  }

  /// Obtiene el token FCM actual
  String? get fcmToken => _fcmToken;

  /// Obtiene la clave de dispositivo
  String? get deviceKey => _deviceKey;

  /// Obtiene los topics suscritos
  Set<String> get subscribedTopics => Set.unmodifiable(_subscribedTopics);

  /// Genera un ID de dispositivo único
  String _generateDeviceId() {
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final platform = Platform.isAndroid ? 'android' : 'ios';
    return '${platform}_$timestamp';
  }

  /// Verifica si las notificaciones están habilitadas
  bool get isEnabled => _fcmToken != null;

  /// Limpia recursos
  void dispose() {
    _notificationListeners.clear();
    _tokenListeners.clear();
  }
}

/// Configuración de Firebase para la app
class FirebaseConfig {
  /// Obtiene la configuración desde el manifest
  static Future<Map<String, dynamic>?> getConfig(ApiClient apiClient) async {
    try {
      final response = await apiClient.getData('/push/status');
      
      if (response.success && response.data != null) {
        return response.data['config'] as Map<String, dynamic>?;
      }

      return null;
    } catch (e) {
      debugPrint('[FirebaseConfig] Error getting config: $e');
      return null;
    }
  }

  /// Verifica si Firebase está configurado en el servidor
  static Future<bool> isConfigured(ApiClient apiClient) async {
    try {
      final response = await apiClient.getData('/push/status');
      
      if (response.success && response.data != null) {
        return response.data['configured'] == true;
      }

      return false;
    } catch (e) {
      return false;
    }
  }
}
