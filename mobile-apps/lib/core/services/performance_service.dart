import 'dart:async';
import 'dart:collection';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';

/// Servicio de monitoreo de rendimiento
class PerformanceService {
  static PerformanceService? _instance;

  final ApiClient _apiClient;

  // Métricas de tiempo
  final Map<String, Stopwatch> _activeTimers = {};
  final Map<String, List<Duration>> _durationHistory = {};

  // Métricas de frames
  int _totalFrames = 0;
  int _slowFrames = 0;
  int _frozenFrames = 0;

  // Métricas de memoria
  int _peakMemoryUsage = 0;

  // Cola de eventos para envío batch
  final Queue<PerformanceEvent> _eventQueue = Queue();
  Timer? _flushTimer;

  // Configuración
  static const int _maxQueueSize = 100;
  static const Duration _flushInterval = Duration(minutes: 1);
  static const Duration _slowFrameThreshold = Duration(milliseconds: 16);
  static const Duration _frozenFrameThreshold = Duration(milliseconds: 700);

  PerformanceService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory PerformanceService({required ApiClient apiClient}) {
    _instance ??= PerformanceService._(apiClient: apiClient);
    return _instance!;
  }

  /// Inicializar servicio
  void initialize() {
    _startFrameMonitoring();
    _startFlushTimer();
    debugPrint('[Performance] Servicio inicializado');
  }

  /// Iniciar monitoreo de frames
  void _startFrameMonitoring() {
    SchedulerBinding.instance.addTimingsCallback((timings) {
      for (final timing in timings) {
        _totalFrames++;

        final frameDuration = timing.totalSpan;

        if (frameDuration > _frozenFrameThreshold) {
          _frozenFrames++;
          _recordEvent(PerformanceEvent(
            type: PerformanceEventType.frozenFrame,
            duration: frameDuration,
            metadata: {'frame_duration_ms': frameDuration.inMilliseconds},
          ));
        } else if (frameDuration > _slowFrameThreshold) {
          _slowFrames++;
        }
      }
    });
  }

  /// Iniciar timer de flush
  void _startFlushTimer() {
    _flushTimer?.cancel();
    _flushTimer = Timer.periodic(_flushInterval, (_) => flush());
  }

  /// === TRACES ===

  /// Iniciar un trace de rendimiento
  void startTrace(String traceName) {
    _activeTimers[traceName] = Stopwatch()..start();
  }

  /// Detener un trace y obtener duración
  Duration? stopTrace(String traceName) {
    final timer = _activeTimers.remove(traceName);
    if (timer == null) return null;

    timer.stop();
    final duration = timer.elapsed;

    // Guardar en historial
    _durationHistory.putIfAbsent(traceName, () => []);
    _durationHistory[traceName]!.add(duration);

    // Limitar historial
    if (_durationHistory[traceName]!.length > 100) {
      _durationHistory[traceName]!.removeAt(0);
    }

    _recordEvent(PerformanceEvent(
      type: PerformanceEventType.trace,
      name: traceName,
      duration: duration,
    ));

    return duration;
  }

  /// Medir una operación async
  Future<T> measureAsync<T>(String name, Future<T> Function() operation) async {
    startTrace(name);
    try {
      final result = await operation();
      stopTrace(name);
      return result;
    } catch (e) {
      stopTrace(name);
      rethrow;
    }
  }

  /// Medir una operación sync
  T measureSync<T>(String name, T Function() operation) {
    startTrace(name);
    try {
      final result = operation();
      stopTrace(name);
      return result;
    } catch (e) {
      stopTrace(name);
      rethrow;
    }
  }

  /// === SCREEN TRACES ===

  /// Iniciar trace de pantalla
  void startScreenTrace(String screenName) {
    startTrace('screen_$screenName');
  }

  /// Detener trace de pantalla
  void stopScreenTrace(String screenName) {
    stopTrace('screen_$screenName');
  }

  /// === NETWORK METRICS ===

  /// Registrar métricas de red
  void recordNetworkMetric({
    required String url,
    required String method,
    required int statusCode,
    required int requestSize,
    required int responseSize,
    required Duration duration,
  }) {
    _recordEvent(PerformanceEvent(
      type: PerformanceEventType.network,
      name: url,
      duration: duration,
      metadata: {
        'method': method,
        'status_code': statusCode,
        'request_size': requestSize,
        'response_size': responseSize,
      },
    ));
  }

  /// === CUSTOM METRICS ===

  /// Registrar métrica personalizada
  void recordMetric(String name, double value, {String? unit, Map<String, dynamic>? metadata}) {
    _recordEvent(PerformanceEvent(
      type: PerformanceEventType.custom,
      name: name,
      value: value,
      unit: unit,
      metadata: metadata,
    ));
  }

  /// Incrementar contador
  void incrementCounter(String name, {int delta = 1}) {
    recordMetric(name, delta.toDouble(), unit: 'count');
  }

  /// === MEMORY METRICS ===

  /// Registrar uso de memoria
  void recordMemoryUsage() {
    // En release, usar ProcessInfo si está disponible
    if (!kDebugMode) {
      try {
        final rss = ProcessInfo.currentRss;
        if (rss > _peakMemoryUsage) {
          _peakMemoryUsage = rss;
        }
        recordMetric('memory_usage', rss.toDouble(), unit: 'bytes');
      } catch (_) {}
    }
  }

  /// === FRAME METRICS ===

  /// Obtener estadísticas de frames
  FrameStats getFrameStats() {
    return FrameStats(
      totalFrames: _totalFrames,
      slowFrames: _slowFrames,
      frozenFrames: _frozenFrames,
    );
  }

  /// === TRACE STATISTICS ===

  /// Obtener estadísticas de un trace
  TraceStats? getTraceStats(String traceName) {
    final history = _durationHistory[traceName];
    if (history == null || history.isEmpty) return null;

    final sorted = List<Duration>.from(history)..sort();
    final sum = history.reduce((a, b) => a + b);

    return TraceStats(
      name: traceName,
      count: history.length,
      average: sum ~/ history.length,
      min: sorted.first,
      max: sorted.last,
      p50: sorted[sorted.length ~/ 2],
      p90: sorted[(sorted.length * 0.9).floor()],
      p99: sorted[(sorted.length * 0.99).floor()],
    );
  }

  /// === INTERNAL ===

  /// Registrar evento
  void _recordEvent(PerformanceEvent event) {
    _eventQueue.add(event);

    // Flush si la cola está llena
    if (_eventQueue.length >= _maxQueueSize) {
      flush();
    }
  }

  /// Enviar eventos al servidor
  Future<void> flush() async {
    if (_eventQueue.isEmpty) return;

    final events = List<PerformanceEvent>.from(_eventQueue);
    _eventQueue.clear();

    try {
      await _apiClient.post('/flavor-app/v2/analytics/performance', data: {
        'events': events.map((e) => e.toJson()).toList(),
        'device_info': _getDeviceInfo(),
        'frame_stats': getFrameStats().toJson(),
        'timestamp': DateTime.now().toIso8601String(),
      });
      debugPrint('[Performance] ${events.length} eventos enviados');
    } catch (e) {
      debugPrint('[Performance] Error enviando eventos: $e');
      // Re-añadir eventos a la cola
      _eventQueue.addAll(events);
    }
  }

  /// Obtener info del dispositivo
  Map<String, dynamic> _getDeviceInfo() {
    return {
      'platform': Platform.operatingSystem,
      'os_version': Platform.operatingSystemVersion,
      'dart_version': Platform.version,
      'is_physical_device': !kDebugMode,
    };
  }

  /// Limpiar recursos
  void dispose() {
    _flushTimer?.cancel();
    flush();
  }
}

/// Tipos de eventos de rendimiento
enum PerformanceEventType {
  trace,
  network,
  custom,
  slowFrame,
  frozenFrame,
  startup,
}

/// Evento de rendimiento
class PerformanceEvent {
  final PerformanceEventType type;
  final String? name;
  final Duration? duration;
  final double? value;
  final String? unit;
  final Map<String, dynamic>? metadata;
  final DateTime timestamp;

  PerformanceEvent({
    required this.type,
    this.name,
    this.duration,
    this.value,
    this.unit,
    this.metadata,
  }) : timestamp = DateTime.now();

  Map<String, dynamic> toJson() => {
    'type': type.name,
    'name': name,
    'duration_ms': duration?.inMilliseconds,
    'value': value,
    'unit': unit,
    'metadata': metadata,
    'timestamp': timestamp.toIso8601String(),
  };
}

/// Estadísticas de frames
class FrameStats {
  final int totalFrames;
  final int slowFrames;
  final int frozenFrames;

  const FrameStats({
    required this.totalFrames,
    required this.slowFrames,
    required this.frozenFrames,
  });

  double get slowFrameRate => totalFrames > 0 ? slowFrames / totalFrames : 0;
  double get frozenFrameRate => totalFrames > 0 ? frozenFrames / totalFrames : 0;

  Map<String, dynamic> toJson() => {
    'total_frames': totalFrames,
    'slow_frames': slowFrames,
    'frozen_frames': frozenFrames,
    'slow_frame_rate': slowFrameRate,
    'frozen_frame_rate': frozenFrameRate,
  };
}

/// Estadísticas de trace
class TraceStats {
  final String name;
  final int count;
  final Duration average;
  final Duration min;
  final Duration max;
  final Duration p50;
  final Duration p90;
  final Duration p99;

  const TraceStats({
    required this.name,
    required this.count,
    required this.average,
    required this.min,
    required this.max,
    required this.p50,
    required this.p90,
    required this.p99,
  });

  Map<String, dynamic> toJson() => {
    'name': name,
    'count': count,
    'average_ms': average.inMilliseconds,
    'min_ms': min.inMilliseconds,
    'max_ms': max.inMilliseconds,
    'p50_ms': p50.inMilliseconds,
    'p90_ms': p90.inMilliseconds,
    'p99_ms': p99.inMilliseconds,
  };
}

/// Métricas predefinidas para Flavor Platform
class FlavorMetrics {
  // Startup
  static const String appStartup = 'app_startup';
  static const String coldStart = 'cold_start';
  static const String warmStart = 'warm_start';

  // Navigation
  static const String screenLoad = 'screen_load';
  static const String screenTransition = 'screen_transition';

  // Network
  static const String apiCall = 'api_call';
  static const String imageLoad = 'image_load';

  // User Actions
  static const String buttonTap = 'button_tap';
  static const String formSubmit = 'form_submit';
  static const String searchQuery = 'search_query';

  // Features
  static const String checkout = 'checkout_flow';
  static const String reservation = 'reservation_flow';
  static const String eventSignup = 'event_signup';
}

/// Mixin para monitoreo de pantallas
mixin ScreenPerformanceMonitor<T extends StatefulWidget> on State<T> {
  late final String _screenName;
  late final PerformanceService _performanceService;

  void initScreenMonitoring(String screenName, PerformanceService service) {
    _screenName = screenName;
    _performanceService = service;
    _performanceService.startScreenTrace(screenName);
  }

  @override
  void dispose() {
    _performanceService.stopScreenTrace(_screenName);
    super.dispose();
  }
}

/// Widget observer para monitoreo automático
class PerformanceRouteObserver extends NavigatorObserver {
  final PerformanceService performanceService;
  final Map<Route, DateTime> _screenStartTimes = {};

  PerformanceRouteObserver({required this.performanceService});

  @override
  void didPush(Route route, Route? previousRoute) {
    _screenStartTimes[route] = DateTime.now();
    performanceService.startScreenTrace(route.settings.name ?? 'unknown');
  }

  @override
  void didPop(Route route, Route? previousRoute) {
    performanceService.stopScreenTrace(route.settings.name ?? 'unknown');
    _screenStartTimes.remove(route);
  }
}
