import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Estado de conectividad
enum ConnectivityState {
  online,
  offline,
  syncing,
}

/// Operación pendiente en cola
class PendingOperation {
  final String id;
  final String module;
  final String type; // create, update, delete
  final String endpoint;
  final Map<String, dynamic>? data;
  final DateTime createdAt;
  int retryCount;
  String? lastError;

  PendingOperation({
    required this.id,
    required this.module,
    required this.type,
    required this.endpoint,
    this.data,
    required this.createdAt,
    this.retryCount = 0,
    this.lastError,
  });

  factory PendingOperation.fromJson(Map<String, dynamic> json) {
    return PendingOperation(
      id: json['id'] ?? '',
      module: json['module'] ?? '',
      type: json['type'] ?? '',
      endpoint: json['endpoint'] ?? '',
      data: json['data'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      retryCount: json['retry_count'] ?? 0,
      lastError: json['last_error'],
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'module': module,
    'type': type,
    'endpoint': endpoint,
    'data': data,
    'created_at': createdAt.toIso8601String(),
    'retry_count': retryCount,
    'last_error': lastError,
  };

  String get typeLabel {
    switch (type) {
      case 'create':
        return 'Crear';
      case 'update':
        return 'Actualizar';
      case 'delete':
        return 'Eliminar';
      default:
        return type;
    }
  }
}

/// Resultado de sincronización
class SyncResult {
  final int total;
  final int successful;
  final int failed;
  final List<String> errors;
  final Duration duration;

  SyncResult({
    required this.total,
    required this.successful,
    required this.failed,
    required this.errors,
    required this.duration,
  });

  bool get allSuccessful => failed == 0;
  bool get partialSuccess => successful > 0 && failed > 0;
  bool get allFailed => successful == 0 && failed > 0;
}

/// Gestor de modo offline
class OfflineManager {
  static OfflineManager? _instance;

  final Connectivity _connectivity;
  static const String _prefsKeyQueue = 'offline_pending_queue';
  static const int _maxRetries = 3;

  ConnectivityState _state = ConnectivityState.online;
  List<PendingOperation> _pendingQueue = [];
  StreamSubscription<ConnectivityResult>? _connectivitySubscription;

  // Callbacks
  final List<void Function(ConnectivityState)> _stateListeners = [];
  final List<void Function(PendingOperation)> _operationAddedListeners = [];
  final List<void Function(SyncResult)> _syncCompletedListeners = [];

  // Stream controllers
  final StreamController<ConnectivityState> _stateController =
      StreamController<ConnectivityState>.broadcast();
  final StreamController<List<PendingOperation>> _queueController =
      StreamController<List<PendingOperation>>.broadcast();

  // Función de ejecución de operaciones (inyectada)
  Future<bool> Function(PendingOperation)? _executeOperation;

  OfflineManager._() : _connectivity = Connectivity();

  factory OfflineManager() {
    _instance ??= OfflineManager._();
    return _instance!;
  }

  /// Estado actual de conectividad
  ConnectivityState get state => _state;

  /// ¿Está online?
  bool get isOnline => _state == ConnectivityState.online;

  /// ¿Está offline?
  bool get isOffline => _state == ConnectivityState.offline;

  /// ¿Está sincronizando?
  bool get isSyncing => _state == ConnectivityState.syncing;

  /// Cola de operaciones pendientes
  List<PendingOperation> get pendingQueue => List.unmodifiable(_pendingQueue);

  /// Número de operaciones pendientes
  int get pendingCount => _pendingQueue.length;

  /// Stream de cambios de estado
  Stream<ConnectivityState> get stateStream => _stateController.stream;

  /// Stream de cambios en la cola
  Stream<List<PendingOperation>> get queueStream => _queueController.stream;

  /// Inicializar el gestor
  Future<void> initialize({
    Future<bool> Function(PendingOperation)? executeOperation,
  }) async {
    _executeOperation = executeOperation;

    // Cargar cola persistida
    await _loadQueue();

    // Verificar conectividad inicial
    final result = await _connectivity.checkConnectivity();
    _updateState([result]);

    // Escuchar cambios de conectividad
    _connectivitySubscription = _connectivity.onConnectivityChanged.listen(
      (result) => _handleConnectivityChange([result]),
    );

    debugPrint('[OfflineManager] Inicializado. Pendientes: ${_pendingQueue.length}');
  }

  void _handleConnectivityChange(List<ConnectivityResult> results) {
    final wasOffline = _state == ConnectivityState.offline;
    _updateState(results);

    // Si volvemos online, intentar sincronizar
    if (wasOffline && isOnline && _pendingQueue.isNotEmpty) {
      debugPrint('[OfflineManager] Conexión restaurada. Sincronizando...');
      syncPending();
    }
  }

  void _updateState(List<ConnectivityResult> results) {
    final newState = results.contains(ConnectivityResult.none)
        ? ConnectivityState.offline
        : ConnectivityState.online;

    if (_state != newState && _state != ConnectivityState.syncing) {
      _state = newState;
      _notifyStateChange();
    }
  }

  void _notifyStateChange() {
    _stateController.add(_state);
    for (final listener in _stateListeners) {
      listener(_state);
    }
  }

  void _notifyQueueChange() {
    _queueController.add(_pendingQueue);
  }

  /// Agregar operación a la cola
  Future<void> addOperation(PendingOperation operation) async {
    _pendingQueue.add(operation);
    await _saveQueue();
    _notifyQueueChange();

    for (final listener in _operationAddedListeners) {
      listener(operation);
    }

    debugPrint('[OfflineManager] Operación añadida: ${operation.type} - ${operation.module}');
  }

  /// Crear operación pendiente
  Future<String> enqueue({
    required String module,
    required String type,
    required String endpoint,
    Map<String, dynamic>? data,
  }) async {
    final operation = PendingOperation(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      module: module,
      type: type,
      endpoint: endpoint,
      data: data,
      createdAt: DateTime.now(),
    );

    await addOperation(operation);
    return operation.id;
  }

  /// Eliminar operación de la cola
  Future<void> removeOperation(String operationId) async {
    _pendingQueue.removeWhere((op) => op.id == operationId);
    await _saveQueue();
    _notifyQueueChange();
  }

  /// Sincronizar operaciones pendientes
  Future<SyncResult> syncPending() async {
    if (_pendingQueue.isEmpty) {
      return SyncResult(
        total: 0,
        successful: 0,
        failed: 0,
        errors: [],
        duration: Duration.zero,
      );
    }

    if (_executeOperation == null) {
      debugPrint('[OfflineManager] No hay función de ejecución configurada');
      return SyncResult(
        total: _pendingQueue.length,
        successful: 0,
        failed: _pendingQueue.length,
        errors: ['No hay función de ejecución configurada'],
        duration: Duration.zero,
      );
    }

    final startTime = DateTime.now();
    _state = ConnectivityState.syncing;
    _notifyStateChange();

    int successful = 0;
    int failed = 0;
    final errors = <String>[];
    final toRemove = <String>[];

    for (final operation in _pendingQueue.toList()) {
      try {
        final success = await _executeOperation!(operation);

        if (success) {
          successful++;
          toRemove.add(operation.id);
        } else {
          operation.retryCount++;
          if (operation.retryCount >= _maxRetries) {
            failed++;
            errors.add('${operation.module}/${operation.type}: Máximo de reintentos alcanzado');
            toRemove.add(operation.id);
          }
        }
      } catch (e) {
        operation.lastError = e.toString();
        operation.retryCount++;

        if (operation.retryCount >= _maxRetries) {
          failed++;
          errors.add('${operation.module}/${operation.type}: $e');
          toRemove.add(operation.id);
        }
      }
    }

    // Eliminar operaciones completadas o fallidas definitivamente
    _pendingQueue.removeWhere((op) => toRemove.contains(op.id));
    await _saveQueue();
    _notifyQueueChange();

    _state = ConnectivityState.online;
    _notifyStateChange();

    final duration = DateTime.now().difference(startTime);

    final result = SyncResult(
      total: successful + failed,
      successful: successful,
      failed: failed,
      errors: errors,
      duration: duration,
    );

    for (final listener in _syncCompletedListeners) {
      listener(result);
    }

    debugPrint('[OfflineManager] Sync completado: $successful éxitos, $failed fallos');

    return result;
  }

  /// Limpiar toda la cola
  Future<void> clearQueue() async {
    _pendingQueue.clear();
    await _saveQueue();
    _notifyQueueChange();
  }

  /// Obtener operaciones por módulo
  List<PendingOperation> getOperationsForModule(String module) {
    return _pendingQueue.where((op) => op.module == module).toList();
  }

  /// Guardar cola en storage
  Future<void> _saveQueue() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonList = _pendingQueue.map((op) => op.toJson()).toList();
      await prefs.setString(_prefsKeyQueue, jsonEncode(jsonList));
    } catch (e) {
      debugPrint('[OfflineManager] Error guardando cola: $e');
    }
  }

  /// Cargar cola desde storage
  Future<void> _loadQueue() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonStr = prefs.getString(_prefsKeyQueue);

      if (jsonStr != null) {
        final jsonList = jsonDecode(jsonStr) as List;
        _pendingQueue = jsonList
            .map((json) => PendingOperation.fromJson(json))
            .toList();
      }
    } catch (e) {
      debugPrint('[OfflineManager] Error cargando cola: $e');
      _pendingQueue = [];
    }
  }

  /// Registrar listener de estado
  void addStateListener(void Function(ConnectivityState) listener) {
    _stateListeners.add(listener);
  }

  /// Eliminar listener de estado
  void removeStateListener(void Function(ConnectivityState) listener) {
    _stateListeners.remove(listener);
  }

  /// Registrar listener de operaciones añadidas
  void addOperationAddedListener(void Function(PendingOperation) listener) {
    _operationAddedListeners.add(listener);
  }

  /// Registrar listener de sync completado
  void addSyncCompletedListener(void Function(SyncResult) listener) {
    _syncCompletedListeners.add(listener);
  }

  /// Limpiar recursos
  void dispose() {
    _connectivitySubscription?.cancel();
    _stateController.close();
    _queueController.close();
    _stateListeners.clear();
    _operationAddedListeners.clear();
    _syncCompletedListeners.clear();
  }
}
