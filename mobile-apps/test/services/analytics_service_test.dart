import 'package:flutter_test/flutter_test.dart';

/// Tests para AnalyticsService
/// 
/// Estos tests validan la lógica del servicio de analytics.

// Simplified AnalyticsEvent for testing
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

// Simplified AnalyticsService for testing
class AnalyticsServiceTestable {
  final List<AnalyticsEvent> eventQueue = [];
  final int batchSize;
  
  String? _sessionId;
  String? _userId;
  bool _enabled = true;
  String? _appVersion;
  String? _platform;
  final String deviceId;

  AnalyticsServiceTestable({
    required this.deviceId,
    this.batchSize = 20,
  });

  void initialize({String? appVersion, String? platform}) {
    _appVersion = appVersion;
    _platform = platform;
    _sessionId = 'session_${DateTime.now().millisecondsSinceEpoch}';
  }

  void setUserId(String? userId) {
    _userId = userId;
    if (userId != null) {
      track('user_identified', properties: {'user_id': userId});
    }
  }

  void setEnabled(bool enabled) {
    _enabled = enabled;
  }

  void track(String eventName, {Map<String, dynamic> properties = const {}}) {
    if (!_enabled) return;

    final enrichedProperties = {
      ...properties,
      'device_id': deviceId,
      if (_appVersion != null) 'app_version': _appVersion,
      if (_platform != null) 'platform': _platform,
    };

    eventQueue.add(AnalyticsEvent(
      name: eventName,
      properties: enrichedProperties,
      userId: _userId,
      sessionId: _sessionId,
    ));
  }

  void trackScreen(String screenName, {Map<String, dynamic>? properties}) {
    track('screen_view', properties: {
      'screen_name': screenName,
      ...?properties,
    });
  }

  void trackModuleUsage(String moduleName, String action, {Map<String, dynamic>? properties}) {
    track('module_$action', properties: {
      'module': moduleName,
      ...?properties,
    });
  }

  void trackError(String errorType, String message, {String? stackTrace}) {
    track('error', properties: {
      'error_type': errorType,
      'message': message,
      if (stackTrace != null) 'stack_trace': stackTrace.substring(0, stackTrace.length.clamp(0, 500)),
    });
  }

  bool get shouldFlush => eventQueue.length >= batchSize;
  int get pendingEventsCount => eventQueue.length;
  String? get userId => _userId;
  String? get sessionId => _sessionId;

  void clearEvents() {
    eventQueue.clear();
  }
}

void main() {
  group('AnalyticsService', () {
    late AnalyticsServiceTestable service;

    setUp(() {
      service = AnalyticsServiceTestable(
        deviceId: 'test_device_123',
        batchSize: 5,
      );
      service.initialize(appVersion: '1.0.0', platform: 'android');
    });

    group('track', () {
      test('should add event to queue', () {
        service.track('test_event');

        expect(service.eventQueue.length, equals(1));
        expect(service.eventQueue.first.name, equals('test_event'));
      });

      test('should enrich event with device info', () {
        service.track('test_event');

        final event = service.eventQueue.first;
        expect(event.properties['device_id'], equals('test_device_123'));
        expect(event.properties['app_version'], equals('1.0.0'));
        expect(event.properties['platform'], equals('android'));
      });

      test('should include session_id', () {
        service.track('test_event');

        expect(service.eventQueue.first.sessionId, isNotNull);
        expect(service.eventQueue.first.sessionId, startsWith('session_'));
      });

      test('should not track when disabled', () {
        service.setEnabled(false);
        service.track('test_event');

        expect(service.eventQueue.length, equals(0));
      });

      test('should include custom properties', () {
        service.track('test_event', properties: {
          'custom_key': 'custom_value',
          'number': 42,
        });

        final props = service.eventQueue.first.properties;
        expect(props['custom_key'], equals('custom_value'));
        expect(props['number'], equals(42));
      });
    });

    group('setUserId', () {
      test('should set user_id and track identification event', () {
        service.setUserId('user_123');

        expect(service.userId, equals('user_123'));
        expect(service.eventQueue.length, equals(1));
        expect(service.eventQueue.first.name, equals('user_identified'));
      });

      test('should include user_id in subsequent events', () {
        service.setUserId('user_123');
        service.track('test_event');

        expect(service.eventQueue.last.userId, equals('user_123'));
      });
    });

    group('trackScreen', () {
      test('should track screen_view event with screen_name', () {
        service.trackScreen('HomeScreen');

        expect(service.eventQueue.length, equals(1));
        expect(service.eventQueue.first.name, equals('screen_view'));
        expect(service.eventQueue.first.properties['screen_name'], equals('HomeScreen'));
      });

      test('should include additional properties', () {
        service.trackScreen('ProductScreen', properties: {'product_id': '123'});

        expect(service.eventQueue.first.properties['product_id'], equals('123'));
      });
    });

    group('trackModuleUsage', () {
      test('should track module action with correct event name', () {
        service.trackModuleUsage('eventos', 'view');

        expect(service.eventQueue.first.name, equals('module_view'));
        expect(service.eventQueue.first.properties['module'], equals('eventos'));
      });

      test('should handle different actions', () {
        service.trackModuleUsage('marketplace', 'purchase');
        service.trackModuleUsage('foros', 'create_post');

        expect(service.eventQueue[0].name, equals('module_purchase'));
        expect(service.eventQueue[1].name, equals('module_create_post'));
      });
    });

    group('trackError', () {
      test('should track error event with details', () {
        service.trackError('NetworkError', 'Connection failed');

        expect(service.eventQueue.first.name, equals('error'));
        expect(service.eventQueue.first.properties['error_type'], equals('NetworkError'));
        expect(service.eventQueue.first.properties['message'], equals('Connection failed'));
      });

      test('should truncate long stack traces', () {
        final longStackTrace = 'a' * 1000;
        service.trackError('Error', 'Test', stackTrace: longStackTrace);

        final stackTrace = service.eventQueue.first.properties['stack_trace'] as String;
        expect(stackTrace.length, lessThanOrEqualTo(500));
      });
    });

    group('batch logic', () {
      test('should indicate flush needed when batch size reached', () {
        for (var i = 0; i < 5; i++) {
          service.track('event_$i');
        }

        expect(service.shouldFlush, isTrue);
      });

      test('should not indicate flush needed below batch size', () {
        for (var i = 0; i < 4; i++) {
          service.track('event_$i');
        }

        expect(service.shouldFlush, isFalse);
      });
    });

    group('AnalyticsEvent', () {
      test('toJson should include all fields', () {
        final event = AnalyticsEvent(
          name: 'test_event',
          properties: {'key': 'value'},
          userId: 'user_123',
          sessionId: 'session_456',
        );

        final json = event.toJson();

        expect(json['event'], equals('test_event'));
        expect(json['properties']['key'], equals('value'));
        expect(json['user_id'], equals('user_123'));
        expect(json['session_id'], equals('session_456'));
        expect(json['timestamp'], isNotNull);
      });

      test('toJson should omit null optional fields', () {
        final event = AnalyticsEvent(
          name: 'test_event',
          properties: {},
        );

        final json = event.toJson();

        expect(json.containsKey('user_id'), isFalse);
        expect(json.containsKey('session_id'), isFalse);
      });
    });
  });
}
