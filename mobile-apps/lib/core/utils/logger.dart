import 'package:flutter/foundation.dart';

/// Sistema de logging condicional para producción/desarrollo
///
/// Uso: Logger.d('mensaje debug');
///      Logger.e('error', error: exception, stackTrace: stack);
///      Logger.i('info');
///      Logger.w('warning');
class Logger {
  static const String _tag = 'FlavorApp';

  /// Log de debug - solo visible en modo debug
  static void d(String message, {String? tag}) {
    if (kDebugMode) {
      debugPrint('[${tag ?? _tag}] DEBUG: $message');
    }
  }

  /// Log de información - solo visible en modo debug
  static void i(String message, {String? tag}) {
    if (kDebugMode) {
      debugPrint('[${tag ?? _tag}] INFO: $message');
    }
  }

  /// Log de advertencia - solo visible en modo debug
  static void w(String message, {String? tag}) {
    if (kDebugMode) {
      debugPrint('[${tag ?? _tag}] WARN: $message');
    }
  }

  /// Log de error - visible en modo debug, en producción solo registra internamente
  static void e(String message, {String? tag, Object? error, StackTrace? stackTrace}) {
    if (kDebugMode) {
      debugPrint('[${tag ?? _tag}] ERROR: $message');
      if (error != null) {
        debugPrint('Error: $error');
      }
      if (stackTrace != null) {
        debugPrint('Stack: $stackTrace');
      }
    }
    // En producción, aquí se podría enviar a un servicio de crash reporting
    // como Firebase Crashlytics, Sentry, etc.
  }

  /// Log de API request/response - solo visible en modo debug
  static void api(String endpoint, {String? method, dynamic body, dynamic response, int? statusCode}) {
    if (kDebugMode) {
      debugPrint('[${_tag}/API] $method $endpoint');
      if (body != null) {
        debugPrint('  Request: $body');
      }
      if (response != null) {
        debugPrint('  Response ($statusCode): $response');
      }
    }
  }

  /// Log de navegación - solo visible en modo debug
  static void nav(String route, {Map<String, dynamic>? params}) {
    if (kDebugMode) {
      final paramsStr = params != null ? ' params=$params' : '';
      debugPrint('[${_tag}/NAV] -> $route$paramsStr');
    }
  }

  /// Log de estado/provider - solo visible en modo debug
  static void state(String provider, String action, {dynamic data}) {
    if (kDebugMode) {
      final dataStr = data != null ? ': $data' : '';
      debugPrint('[${_tag}/STATE] $provider.$action$dataStr');
    }
  }

  /// Log de timer/performance - solo visible en modo debug
  static Stopwatch? startTimer(String operation) {
    if (kDebugMode) {
      final stopwatch = Stopwatch()..start();
      debugPrint('[${_tag}/PERF] Starting: $operation');
      return stopwatch;
    }
    return null;
  }

  static void endTimer(Stopwatch? stopwatch, String operation) {
    if (kDebugMode && stopwatch != null) {
      stopwatch.stop();
      debugPrint('[${_tag}/PERF] Completed: $operation in ${stopwatch.elapsedMilliseconds}ms');
    }
  }
}
