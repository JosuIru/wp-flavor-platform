import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:isolate';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';

/// Tipo de error
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

/// Reporte de crash
class CrashReport {
  final String id;
  final ErrorType errorType;
  final String message;
  final String? stackTrace;
  final String deviceId;
  final String? appVersion;
  final String? platform;
  final String? osVersion;
  final String? deviceModel;
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
    this.osVersion,
    this.deviceModel,
    this.context = const {},
    DateTime? occurredAt,
  }) : occurredAt = occurredAt ?? DateTime.now();

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'error_type': errorType.name,
      'message': message,
      'stack_trace': stackTrace,
      'device_id': deviceId,
      'app_version': appVersion,
      'platform': platform,
      'os_version': osVersion,
      'device_model': deviceModel,
      'context': context,
      'timestamp': occurredAt.toIso8601String(),
    };
  }

  factory CrashReport.fromJson(Map<String, dynamic> json) {
    return CrashReport(
      id: json['id'] as String,
      errorType: ErrorType.values.firstWhere(
        (e) => e.name == json['error_type'],
        orElse: () => ErrorType.unknown,
      ),
      message: json['message'] as String,
      stackTrace: json['stack_trace'] as String?,
      deviceId: json['device_id'] as String,
      appVersion: json['app_version'] as String?,
      platform: json['platform'] as String?,
      osVersion: json['os_version'] as String?,
      deviceModel: json['device_model'] as String?,
      context: json['context'] as Map<String, dynamic>? ?? {},
      occurredAt: DateTime.parse(json['timestamp'] as String),
    );
  }
}

/// Resultado de reporte
class CrashReportResult {
  final bool success;
  final String? crashId;
  final String? error;

  CrashReportResult({
    required this.success,
    this.crashId,
    this.error,
  });
}

/// Servicio de Crash Reporting
class CrashReportingService {
  static const String _prefsKeyPendingCrashes = 'pending_crashes';
  static const String _prefsKeyBreadcrumbs = 'crash_breadcrumbs';
  static const int _maxBreadcrumbs = 50;
  static const int _maxPendingCrashes = 100;

  /// Cliente API
  final ApiClient _apiClient;

  /// ID de dispositivo
  final String deviceId;

  /// Versión de la app
  String? _appVersion;

  /// Plataforma
  String? _platform;

  /// Versión del OS
  String? _osVersion;

  /// Modelo del dispositivo
  String? _deviceModel;

  /// Cola de crashes pendientes
  final List<CrashReport> _pendingCrashes = [];

  /// Breadcrumbs (migas de pan para contexto)
  final List<Map<String, dynamic>> _breadcrumbs = [];

  /// Si está habilitado
  bool _enabled = true;

  /// Si está inicializado
  bool _initialized = false;

  /// Pantalla actual
  String? _currentScreen;

  /// Usuario actual
  String? _userId;

  /// Contexto global personalizado
  final Map<String, dynamic> _globalContext = {};

  CrashReportingService({
    required ApiClient apiClient,
    required this.deviceId,
  }) : _apiClient = apiClient;

  /// Inicializa el servicio
  Future<void> initialize({
    String? appVersion,
    String? platform,
    String? osVersion,
    String? deviceModel,
  }) async {
    if (_initialized) return;

    _appVersion = appVersion;
    _platform = platform ?? (Platform.isAndroid ? 'android' : Platform.isIOS ? 'ios' : 'unknown');
    _osVersion = osVersion ?? Platform.operatingSystemVersion;
    _deviceModel = deviceModel;

    // Cargar crashes pendientes
    await _loadPendingCrashes();
    await _loadBreadcrumbs();

    // Configurar handlers de errores
    _setupErrorHandlers();

    _initialized = true;
    debugPrint('[CrashReporting] Initialized');

    // Intentar enviar crashes pendientes
    await flushPendingCrashes();
  }

  /// Configura handlers de errores globales
  void _setupErrorHandlers() {
    // Errores de Flutter
    FlutterError.onError = (FlutterErrorDetails details) {
      _handleFlutterError(details);
    };

    // Errores de plataforma (no capturados)
    PlatformDispatcher.instance.onError = (error, stack) {
      _handlePlatformError(error, stack);
      return true;
    };

    // Errores en isolates
    Isolate.current.addErrorListener(RawReceivePort((pair) async {
      final List<dynamic> errorAndStacktrace = pair as List<dynamic>;
      await reportError(
        errorAndStacktrace[0],
        StackTrace.fromString(errorAndStacktrace[1]?.toString() ?? ''),
        errorType: ErrorType.crash,
      );
    }).sendPort);
  }

  /// Maneja errores de Flutter
  void _handleFlutterError(FlutterErrorDetails details) {
    reportError(
      details.exception,
      details.stack,
      errorType: ErrorType.widgetError,
      context: {
        'library': details.library,
        'context': details.context?.toString(),
      },
    );

    // También llamar al handler original si existe
    if (!kReleaseMode) {
      FlutterError.presentError(details);
    }
  }

  /// Maneja errores de plataforma
  void _handlePlatformError(Object error, StackTrace stack) {
    reportError(
      error,
      stack,
      errorType: ErrorType.platformError,
    );
  }

  // =========================================================================
  // MÉTODOS PÚBLICOS
  // =========================================================================

  /// Habilita/deshabilita el servicio
  void setEnabled(bool enabled) {
    _enabled = enabled;
  }

  /// Establece el usuario actual
  void setUserId(String? userId) {
    _userId = userId;
    if (userId != null) {
      addBreadcrumb('user', 'User identified', data: {'user_id': userId});
    }
  }

  /// Establece la pantalla actual
  void setCurrentScreen(String screenName) {
    _currentScreen = screenName;
    addBreadcrumb('navigation', 'Screen changed', data: {'screen': screenName});
  }

  /// Añade contexto global
  void setGlobalContext(String key, dynamic value) {
    _globalContext[key] = value;
  }

  /// Añade breadcrumb (miga de pan)
  void addBreadcrumb(String category, String message, {Map<String, dynamic>? data}) {
    final breadcrumb = {
      'category': category,
      'message': message,
      'data': data ?? {},
      'timestamp': DateTime.now().toIso8601String(),
    };

    _breadcrumbs.add(breadcrumb);

    // Limitar cantidad
    while (_breadcrumbs.length > _maxBreadcrumbs) {
      _breadcrumbs.removeAt(0);
    }

    // Guardar async
    _saveBreadcrumbs();
  }

  /// Reporta un error/excepción
  Future<CrashReportResult> reportError(
    dynamic error,
    StackTrace? stackTrace, {
    ErrorType errorType = ErrorType.exception,
    Map<String, dynamic>? context,
  }) async {
    if (!_enabled) {
      return CrashReportResult(success: false, error: 'Service disabled');
    }

    final crashReport = CrashReport(
      id: 'crash_${DateTime.now().millisecondsSinceEpoch}_${error.hashCode}',
      errorType: errorType,
      message: error.toString(),
      stackTrace: stackTrace?.toString(),
      deviceId: deviceId,
      appVersion: _appVersion,
      platform: _platform,
      osVersion: _osVersion,
      deviceModel: _deviceModel,
      context: _buildContext(context),
    );

    debugPrint('[CrashReporting] Error captured: ${crashReport.errorType.name} - ${crashReport.message}');

    // Intentar enviar inmediatamente
    final result = await _sendCrash(crashReport);

    if (!result.success) {
      // Si falla, guardar para enviar después
      await _queueCrash(crashReport);
    }

    return result;
  }

  /// Reporta un crash fatal
  Future<CrashReportResult> reportFatalCrash(
    dynamic error,
    StackTrace? stackTrace, {
    Map<String, dynamic>? context,
  }) async {
    return reportError(
      error,
      stackTrace,
      errorType: ErrorType.crash,
      context: {...?context, 'fatal': true},
    );
  }

  /// Reporta un ANR (Application Not Responding)
  Future<CrashReportResult> reportANR({
    Duration? duration,
    String? lastAction,
    Map<String, dynamic>? context,
  }) async {
    return reportError(
      'Application Not Responding${duration != null ? ' for ${duration.inSeconds}s' : ''}',
      null,
      errorType: ErrorType.anr,
      context: {
        ...?context,
        if (lastAction != null) 'last_action': lastAction,
        if (duration != null) 'duration_ms': duration.inMilliseconds,
      },
    );
  }

  /// Reporta error de red
  Future<CrashReportResult> reportNetworkError(
    String endpoint,
    int? statusCode,
    String? message, {
    Map<String, dynamic>? context,
  }) async {
    return reportError(
      'Network error: $endpoint - ${statusCode ?? 'unknown'} - ${message ?? 'no message'}',
      null,
      errorType: ErrorType.networkError,
      context: {
        ...?context,
        'endpoint': endpoint,
        'status_code': statusCode,
      },
    );
  }

  /// Envía crashes pendientes
  Future<int> flushPendingCrashes() async {
    if (_pendingCrashes.isEmpty) return 0;

    final crashesToSend = List<CrashReport>.from(_pendingCrashes);
    int sentCount = 0;

    // Intentar envío en batch
    try {
      final response = await _apiClient.postData('/crashes/batch', data: {
        'crashes': crashesToSend.map((c) => c.toJson()).toList(),
      });

      if (response.success) {
        _pendingCrashes.removeWhere((c) => crashesToSend.contains(c));
        sentCount = crashesToSend.length;
        await _savePendingCrashes();
        debugPrint('[CrashReporting] Flushed $sentCount pending crashes');
      }
    } catch (e) {
      debugPrint('[CrashReporting] Batch send failed: $e');
      
      // Intentar envío individual
      for (final crash in crashesToSend) {
        final result = await _sendCrash(crash);
        if (result.success) {
          _pendingCrashes.remove(crash);
          sentCount++;
        }
      }
      await _savePendingCrashes();
    }

    return sentCount;
  }

  /// Obtiene número de crashes pendientes
  int get pendingCrashesCount => _pendingCrashes.length;

  /// Obtiene breadcrumbs actuales
  List<Map<String, dynamic>> get breadcrumbs => List.unmodifiable(_breadcrumbs);

  /// Limpia breadcrumbs
  Future<void> clearBreadcrumbs() async {
    _breadcrumbs.clear();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyBreadcrumbs);
  }

  /// Limpia crashes pendientes
  Future<void> clearPendingCrashes() async {
    _pendingCrashes.clear();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyPendingCrashes);
  }

  // =========================================================================
  // MÉTODOS PRIVADOS
  // =========================================================================

  /// Construye contexto completo
  Map<String, dynamic> _buildContext(Map<String, dynamic>? additionalContext) {
    return {
      ..._globalContext,
      if (_currentScreen != null) 'current_screen': _currentScreen,
      if (_userId != null) 'user_id': _userId,
      'breadcrumbs': _breadcrumbs.length,
      'recent_breadcrumbs': _breadcrumbs.length > 5 
          ? _breadcrumbs.sublist(_breadcrumbs.length - 5)
          : _breadcrumbs,
      ...?additionalContext,
    };
  }

  /// Envía un crash al servidor
  Future<CrashReportResult> _sendCrash(CrashReport crash) async {
    try {
      final response = await _apiClient.postData('/crashes', data: crash.toJson());

      if (response.success) {
        final data = response.data;
        return CrashReportResult(
          success: true,
          crashId: data?['crash_id'] as String?,
        );
      } else {
        return CrashReportResult(
          success: false,
          error: response.errorMessage ?? 'Unknown error',
        );
      }
    } catch (e) {
      return CrashReportResult(
        success: false,
        error: e.toString(),
      );
    }
  }

  /// Encola un crash para envío posterior
  Future<void> _queueCrash(CrashReport crash) async {
    _pendingCrashes.add(crash);

    // Limitar cantidad
    while (_pendingCrashes.length > _maxPendingCrashes) {
      _pendingCrashes.removeAt(0);
    }

    await _savePendingCrashes();
  }

  /// Carga crashes pendientes
  Future<void> _loadPendingCrashes() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final crashesJson = prefs.getString(_prefsKeyPendingCrashes);
      
      if (crashesJson != null) {
        final list = json.decode(crashesJson) as List<dynamic>;
        for (final item in list) {
          _pendingCrashes.add(CrashReport.fromJson(item as Map<String, dynamic>));
        }
        debugPrint('[CrashReporting] Loaded ${_pendingCrashes.length} pending crashes');
      }
    } catch (e) {
      debugPrint('[CrashReporting] Error loading pending crashes: $e');
    }
  }

  /// Guarda crashes pendientes
  Future<void> _savePendingCrashes() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final crashesJson = json.encode(_pendingCrashes.map((c) => c.toJson()).toList());
      await prefs.setString(_prefsKeyPendingCrashes, crashesJson);
    } catch (e) {
      debugPrint('[CrashReporting] Error saving pending crashes: $e');
    }
  }

  /// Carga breadcrumbs
  Future<void> _loadBreadcrumbs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final breadcrumbsJson = prefs.getString(_prefsKeyBreadcrumbs);
      
      if (breadcrumbsJson != null) {
        final list = json.decode(breadcrumbsJson) as List<dynamic>;
        for (final item in list) {
          _breadcrumbs.add(item as Map<String, dynamic>);
        }
      }
    } catch (e) {
      debugPrint('[CrashReporting] Error loading breadcrumbs: $e');
    }
  }

  /// Guarda breadcrumbs
  Future<void> _saveBreadcrumbs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final breadcrumbsJson = json.encode(_breadcrumbs);
      await prefs.setString(_prefsKeyBreadcrumbs, breadcrumbsJson);
    } catch (e) {
      debugPrint('[CrashReporting] Error saving breadcrumbs: $e');
    }
  }
}

/// Wrapper para capturar errores en un bloque de código
Future<T?> captureErrors<T>(
  CrashReportingService service,
  Future<T> Function() operation, {
  String? operationName,
  Map<String, dynamic>? context,
}) async {
  try {
    return await operation();
  } catch (e, stack) {
    await service.reportError(
      e,
      stack,
      context: {
        ...?context,
        if (operationName != null) 'operation': operationName,
      },
    );
    return null;
  }
}

/// Categorías predefinidas de breadcrumbs
class BreadcrumbCategories {
  static const String navigation = 'navigation';
  static const String userAction = 'user_action';
  static const String network = 'network';
  static const String state = 'state';
  static const String lifecycle = 'lifecycle';
  static const String system = 'system';
  static const String custom = 'custom';
}
