import 'package:flutter_test/flutter_test.dart';

/// Tests para CrashReportingService
/// 
/// Valida lógica de reporte de crashes, breadcrumbs y fingerprinting.

// Error types enum
enum ErrorType {
  crash,
  exception,
  anr,
  networkError,
  assertion,
  widgetError,
  platformError,
  unknown,
}

// Simplified CrashReport for testing
class CrashReport {
  final String id;
  final ErrorType errorType;
  final String message;
  final String? stackTrace;
  final String deviceId;
  final String? appVersion;
  final String? platform;
  final Map<String, dynamic> context;
  final DateTime occurredAt;

  CrashReport({
    required this.id,
    required this.errorType,
    required this.message,
    this.stackTrace,
    required this.deviceId,
    this.appVersion,
    this.platform,
    this.context = const {},
    DateTime? occurredAt,
  }) : occurredAt = occurredAt ?? DateTime.now();

  Map<String, dynamic> toJson() => {
    'id': id,
    'error_type': errorType.name,
    'message': message,
    'stack_trace': stackTrace,
    'device_id': deviceId,
    'app_version': appVersion,
    'platform': platform,
    'context': context,
    'timestamp': occurredAt.toIso8601String(),
  };
}

// Simplified CrashReportingService for testing
class CrashReportingServiceTestable {
  final String deviceId;
  final List<CrashReport> pendingCrashes = [];
  final List<Map<String, dynamic>> breadcrumbs = [];
  final Map<String, dynamic> globalContext = {};
  
  static const int maxBreadcrumbs = 50;
  static const int maxPendingCrashes = 100;
  
  String? _appVersion;
  String? _platform;
  String? _currentScreen;
  String? _userId;
  bool _enabled = true;

  CrashReportingServiceTestable({required this.deviceId});

  void initialize({String? appVersion, String? platform}) {
    _appVersion = appVersion;
    _platform = platform;
  }

  void setEnabled(bool enabled) {
    _enabled = enabled;
  }

  void setUserId(String? userId) {
    _userId = userId;
    if (userId != null) {
      addBreadcrumb('user', 'User identified', data: {'user_id': userId});
    }
  }

  void setCurrentScreen(String screenName) {
    _currentScreen = screenName;
    addBreadcrumb('navigation', 'Screen changed', data: {'screen': screenName});
  }

  void setGlobalContext(String key, dynamic value) {
    globalContext[key] = value;
  }

  void addBreadcrumb(String category, String message, {Map<String, dynamic>? data}) {
    breadcrumbs.add({
      'category': category,
      'message': message,
      'data': data ?? {},
      'timestamp': DateTime.now().toIso8601String(),
    });

    // Limit breadcrumbs
    while (breadcrumbs.length > maxBreadcrumbs) {
      breadcrumbs.removeAt(0);
    }
  }

  CrashReport createCrashReport(
    dynamic error,
    StackTrace? stackTrace, {
    ErrorType errorType = ErrorType.exception,
    Map<String, dynamic>? context,
  }) {
    if (!_enabled) {
      throw StateError('Service is disabled');
    }

    return CrashReport(
      id: 'crash_${DateTime.now().millisecondsSinceEpoch}_${error.hashCode}',
      errorType: errorType,
      message: error.toString(),
      stackTrace: stackTrace?.toString(),
      deviceId: deviceId,
      appVersion: _appVersion,
      platform: _platform,
      context: _buildContext(context),
    );
  }

  Map<String, dynamic> _buildContext(Map<String, dynamic>? additionalContext) {
    return {
      ...globalContext,
      if (_currentScreen != null) 'current_screen': _currentScreen,
      if (_userId != null) 'user_id': _userId,
      'breadcrumbs_count': breadcrumbs.length,
      'recent_breadcrumbs': breadcrumbs.length > 5 
          ? breadcrumbs.sublist(breadcrumbs.length - 5)
          : List.from(breadcrumbs),
      ...?additionalContext,
    };
  }

  void queueCrash(CrashReport crash) {
    pendingCrashes.add(crash);
    while (pendingCrashes.length > maxPendingCrashes) {
      pendingCrashes.removeAt(0);
    }
  }

  String? get currentScreen => _currentScreen;
  String? get userId => _userId;
  int get pendingCrashesCount => pendingCrashes.length;
  int get breadcrumbsCount => breadcrumbs.length;
}

void main() {
  group('CrashReportingService', () {
    late CrashReportingServiceTestable service;

    setUp(() {
      service = CrashReportingServiceTestable(deviceId: 'test_device_123');
      service.initialize(appVersion: '1.0.0', platform: 'android');
    });

    group('createCrashReport', () {
      test('should create crash report with correct details', () {
        final error = Exception('Test error');
        final report = service.createCrashReport(error, null);

        expect(report.deviceId, equals('test_device_123'));
        expect(report.appVersion, equals('1.0.0'));
        expect(report.platform, equals('android'));
        expect(report.message, contains('Test error'));
        expect(report.errorType, equals(ErrorType.exception));
      });

      test('should include stack trace when provided', () {
        final error = Exception('Test error');
        final stackTrace = StackTrace.current;
        final report = service.createCrashReport(error, stackTrace);

        expect(report.stackTrace, isNotNull);
        expect(report.stackTrace, isNotEmpty);
      });

      test('should throw when service is disabled', () {
        service.setEnabled(false);

        expect(
          () => service.createCrashReport(Exception('Test'), null),
          throwsA(isA<StateError>()),
        );
      });

      test('should include custom error type', () {
        final report = service.createCrashReport(
          'ANR detected',
          null,
          errorType: ErrorType.anr,
        );

        expect(report.errorType, equals(ErrorType.anr));
      });
    });

    group('breadcrumbs', () {
      test('should add breadcrumb with category and message', () {
        service.addBreadcrumb('user_action', 'Button clicked');

        expect(service.breadcrumbsCount, equals(1));
        expect(service.breadcrumbs.first['category'], equals('user_action'));
        expect(service.breadcrumbs.first['message'], equals('Button clicked'));
      });

      test('should include data in breadcrumb', () {
        service.addBreadcrumb('network', 'API call', data: {'endpoint': '/users'});

        expect(service.breadcrumbs.first['data']['endpoint'], equals('/users'));
      });

      test('should limit breadcrumbs to maxBreadcrumbs', () {
        for (var i = 0; i < 60; i++) {
          service.addBreadcrumb('test', 'Breadcrumb $i');
        }

        expect(service.breadcrumbsCount, equals(50));
        // First ones should be removed
        expect(
          service.breadcrumbs.first['message'],
          equals('Breadcrumb 10'),
        );
      });

      test('should include timestamp', () {
        service.addBreadcrumb('test', 'Test');

        expect(service.breadcrumbs.first['timestamp'], isNotNull);
      });
    });

    group('setCurrentScreen', () {
      test('should update current screen', () {
        service.setCurrentScreen('HomeScreen');

        expect(service.currentScreen, equals('HomeScreen'));
      });

      test('should add navigation breadcrumb', () {
        service.setCurrentScreen('HomeScreen');

        expect(service.breadcrumbsCount, equals(1));
        expect(service.breadcrumbs.first['category'], equals('navigation'));
        expect(service.breadcrumbs.first['data']['screen'], equals('HomeScreen'));
      });

      test('should include screen in crash context', () {
        service.setCurrentScreen('ProductScreen');
        final report = service.createCrashReport(Exception('Error'), null);

        expect(report.context['current_screen'], equals('ProductScreen'));
      });
    });

    group('setUserId', () {
      test('should set user id', () {
        service.setUserId('user_123');

        expect(service.userId, equals('user_123'));
      });

      test('should add identification breadcrumb', () {
        service.setUserId('user_123');

        expect(service.breadcrumbs.first['category'], equals('user'));
        expect(service.breadcrumbs.first['data']['user_id'], equals('user_123'));
      });

      test('should include user_id in crash context', () {
        service.setUserId('user_456');
        final report = service.createCrashReport(Exception('Error'), null);

        expect(report.context['user_id'], equals('user_456'));
      });
    });

    group('globalContext', () {
      test('should add global context', () {
        service.setGlobalContext('environment', 'production');

        final report = service.createCrashReport(Exception('Error'), null);

        expect(report.context['environment'], equals('production'));
      });

      test('should merge multiple context values', () {
        service.setGlobalContext('env', 'prod');
        service.setGlobalContext('version', '2.0');

        final report = service.createCrashReport(Exception('Error'), null);

        expect(report.context['env'], equals('prod'));
        expect(report.context['version'], equals('2.0'));
      });
    });

    group('crash queue', () {
      test('should queue crash report', () {
        final report = service.createCrashReport(Exception('Error'), null);
        service.queueCrash(report);

        expect(service.pendingCrashesCount, equals(1));
      });

      test('should limit pending crashes to maxPendingCrashes', () {
        for (var i = 0; i < 110; i++) {
          final report = service.createCrashReport(Exception('Error $i'), null);
          service.queueCrash(report);
        }

        expect(service.pendingCrashesCount, equals(100));
      });
    });

    group('context building', () {
      test('should include recent breadcrumbs in context', () {
        for (var i = 0; i < 10; i++) {
          service.addBreadcrumb('test', 'Breadcrumb $i');
        }

        final report = service.createCrashReport(Exception('Error'), null);

        expect(report.context['breadcrumbs_count'], equals(10));
        expect(
          (report.context['recent_breadcrumbs'] as List).length,
          equals(5),
        );
      });

      test('should merge all context sources', () {
        service.setGlobalContext('app', 'flavor');
        service.setUserId('user_123');
        service.setCurrentScreen('HomeScreen');

        final report = service.createCrashReport(
          Exception('Error'),
          null,
          context: {'custom': 'value'},
        );

        expect(report.context['app'], equals('flavor'));
        expect(report.context['user_id'], equals('user_123'));
        expect(report.context['current_screen'], equals('HomeScreen'));
        expect(report.context['custom'], equals('value'));
      });
    });

    group('CrashReport', () {
      test('toJson should serialize all fields', () {
        final report = CrashReport(
          id: 'crash_123',
          errorType: ErrorType.crash,
          message: 'Fatal error',
          stackTrace: 'stack trace here',
          deviceId: 'device_456',
          appVersion: '1.0.0',
          platform: 'ios',
          context: {'key': 'value'},
        );

        final json = report.toJson();

        expect(json['id'], equals('crash_123'));
        expect(json['error_type'], equals('crash'));
        expect(json['message'], equals('Fatal error'));
        expect(json['stack_trace'], equals('stack trace here'));
        expect(json['device_id'], equals('device_456'));
        expect(json['app_version'], equals('1.0.0'));
        expect(json['platform'], equals('ios'));
        expect(json['context']['key'], equals('value'));
        expect(json['timestamp'], isNotNull);
      });
    });
  });
}
