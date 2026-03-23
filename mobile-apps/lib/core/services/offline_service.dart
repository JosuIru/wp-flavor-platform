import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';
import '../api/api_client.dart';

/// Estado de conectividad
enum ConnectivityStatus {
  online,
  offline,
  syncing,
}

/// Acción pendiente de sincronización
class PendingAction {
  final String id;
  final String type;
  final String endpoint;
  final String method;
  final Map<String, dynamic> data;
  final DateTime createdAt;
  final int retryCount;

  PendingAction({
    required this.id,
    required this.type,
    required this.endpoint,
    required this.method,
    required this.data,
    required this.createdAt,
    this.retryCount = 0,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'type': type,
      'endpoint': endpoint,
      'method': method,
      'data': json.encode(data),
      'created_at': createdAt.toIso8601String(),
      'retry_count': retryCount,
    };
  }

  factory PendingAction.fromMap(Map<String, dynamic> map) {
    return PendingAction(
      id: map['id'] as String,
      type: map['type'] as String,
      endpoint: map['endpoint'] as String,
      method: map['method'] as String,
      data: json.decode(map['data'] as String) as Map<String, dynamic>,
      createdAt: DateTime.parse(map['created_at'] as String),
      retryCount: map['retry_count'] as int? ?? 0,
    );
  }

  PendingAction copyWith({int? retryCount}) {
    return PendingAction(
      id: id,
      type: type,
      endpoint: endpoint,
      method: method,
      data: data,
      createdAt: createdAt,
      retryCount: retryCount ?? this.retryCount,
    );
  }
}

/// Resultado de sincronización
class SyncResult {
  final int totalActions;
  final int successCount;
  final int failedCount;
  final List<String> errors;
  final DateTime syncedAt;

  SyncResult({
    required this.totalActions,
    required this.successCount,
    required this.failedCount,
    required this.errors,
    required this.syncedAt,
  });

  bool get hasErrors => failedCount > 0;
  bool get allSuccess => successCount == totalActions;
}

/// Servicio de modo offline con caché local y sincronización
class OfflineService {
  static const String _dbName = 'flavor_offline.db';
  static const int _dbVersion = 1;
  static const int _maxRetries = 3;
  static const String _prefsCacheTimestamp = 'offline_cache_timestamp';

  /// Base de datos SQLite
  Database? _database;

  /// Cliente API
  final ApiClient _apiClient;

  /// Estado de conectividad actual
  ConnectivityStatus _status = ConnectivityStatus.online;

  /// Stream de cambios de conectividad
  final _statusController = StreamController<ConnectivityStatus>.broadcast();

  /// Suscripción a cambios de conectividad
  StreamSubscription<List<ConnectivityResult>>? _connectivitySubscription;

  /// Listeners de sincronización
  final List<void Function(SyncResult)> _syncListeners = [];

  /// Timer para reintentos automáticos
  Timer? _retryTimer;

  OfflineService({required ApiClient apiClient}) : _apiClient = apiClient;

  /// Stream de estado de conectividad
  Stream<ConnectivityStatus> get statusStream => _statusController.stream;

  /// Estado actual
  ConnectivityStatus get status => _status;

  /// Verifica si está online
  bool get isOnline => _status == ConnectivityStatus.online;

  /// Verifica si está offline
  bool get isOffline => _status == ConnectivityStatus.offline;

  /// Verifica si está sincronizando
  bool get isSyncing => _status == ConnectivityStatus.syncing;

  /// Inicializa el servicio
  Future<void> initialize() async {
    // Inicializar base de datos
    await _initDatabase();

    // Escuchar cambios de conectividad
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen(
      _handleConnectivityChange,
    );

    // Verificar estado inicial
    final results = await Connectivity().checkConnectivity();
    await _handleConnectivityChange(results);

    debugPrint('[OfflineService] Initialized. Status: $_status');
  }

  /// Inicializa la base de datos SQLite
  Future<void> _initDatabase() async {
    final databasesPath = await getDatabasesPath();
    final path = join(databasesPath, _dbName);

    _database = await openDatabase(
      path,
      version: _dbVersion,
      onCreate: (db, version) async {
        // Tabla de acciones pendientes
        await db.execute('''
          CREATE TABLE pending_actions (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            endpoint TEXT NOT NULL,
            method TEXT NOT NULL,
            data TEXT NOT NULL,
            created_at TEXT NOT NULL,
            retry_count INTEGER DEFAULT 0
          )
        ''');

        // Tabla de caché de datos
        await db.execute('''
          CREATE TABLE cache_data (
            key TEXT PRIMARY KEY,
            data TEXT NOT NULL,
            endpoint TEXT,
            updated_at TEXT NOT NULL,
            expires_at TEXT
          )
        ''');

        // Tabla de configuración offline
        await db.execute('''
          CREATE TABLE offline_config (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
          )
        ''');

        debugPrint('[OfflineService] Database created');
      },
    );
  }

  /// Maneja cambios de conectividad
  Future<void> _handleConnectivityChange(List<ConnectivityResult> results) async {
    final wasOffline = _status == ConnectivityStatus.offline;
    final hasConnection = results.isNotEmpty && 
        !results.contains(ConnectivityResult.none);

    if (hasConnection) {
      // Verificar conexión real haciendo ping al servidor
      final reallyOnline = await _checkRealConnectivity();
      
      if (reallyOnline) {
        _updateStatus(ConnectivityStatus.online);
        
        // Si estábamos offline, sincronizar
        if (wasOffline) {
          await syncPendingActions();
        }
      } else {
        _updateStatus(ConnectivityStatus.offline);
      }
    } else {
      _updateStatus(ConnectivityStatus.offline);
    }
  }

  /// Verifica conectividad real con el servidor
  Future<bool> _checkRealConnectivity() async {
    try {
      final response = await _apiClient.getData('/manifest/check');
      return response.success;
    } catch (e) {
      return false;
    }
  }

  /// Actualiza el estado y notifica
  void _updateStatus(ConnectivityStatus newStatus) {
    if (_status != newStatus) {
      _status = newStatus;
      _statusController.add(newStatus);
      debugPrint('[OfflineService] Status changed to: $newStatus');
    }
  }

  // =========================================================================
  // CACHÉ DE DATOS
  // =========================================================================

  /// Guarda datos en caché
  Future<void> cacheData(
    String key,
    Map<String, dynamic> data, {
    String? endpoint,
    Duration? expiration,
  }) async {
    if (_database == null) return;

    final now = DateTime.now();
    final expiresAt = expiration != null ? now.add(expiration) : null;

    await _database!.insert(
      'cache_data',
      {
        'key': key,
        'data': json.encode(data),
        'endpoint': endpoint,
        'updated_at': now.toIso8601String(),
        'expires_at': expiresAt?.toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );

    debugPrint('[OfflineService] Cached data for key: $key');
  }

  /// Obtiene datos de caché
  Future<Map<String, dynamic>?> getCachedData(String key) async {
    if (_database == null) return null;

    final results = await _database!.query(
      'cache_data',
      where: 'key = ?',
      whereArgs: [key],
    );

    if (results.isEmpty) return null;

    final row = results.first;
    final expiresAtStr = row['expires_at'] as String?;

    // Verificar si expiró
    if (expiresAtStr != null) {
      final expiresAt = DateTime.parse(expiresAtStr);
      if (DateTime.now().isAfter(expiresAt)) {
        // Eliminar datos expirados
        await _database!.delete('cache_data', where: 'key = ?', whereArgs: [key]);
        return null;
      }
    }

    return json.decode(row['data'] as String) as Map<String, dynamic>;
  }

  /// Elimina datos de caché
  Future<void> removeCachedData(String key) async {
    if (_database == null) return;
    await _database!.delete('cache_data', where: 'key = ?', whereArgs: [key]);
  }

  /// Limpia toda la caché
  Future<void> clearCache() async {
    if (_database == null) return;
    await _database!.delete('cache_data');
    debugPrint('[OfflineService] Cache cleared');
  }

  /// Limpia caché expirada
  Future<int> cleanExpiredCache() async {
    if (_database == null) return 0;

    final now = DateTime.now().toIso8601String();
    final deleted = await _database!.delete(
      'cache_data',
      where: 'expires_at IS NOT NULL AND expires_at < ?',
      whereArgs: [now],
    );

    debugPrint('[OfflineService] Cleaned $deleted expired cache entries');
    return deleted;
  }

  // =========================================================================
  // ACCIONES PENDIENTES
  // =========================================================================

  /// Encola una acción para ejecutar cuando haya conexión
  Future<String> queueAction({
    required String type,
    required String endpoint,
    required String method,
    required Map<String, dynamic> data,
  }) async {
    final action = PendingAction(
      id: '${DateTime.now().millisecondsSinceEpoch}_${type.hashCode}',
      type: type,
      endpoint: endpoint,
      method: method,
      data: data,
      createdAt: DateTime.now(),
    );

    await _saveAction(action);
    debugPrint('[OfflineService] Queued action: ${action.id}');

    // Si estamos online, intentar ejecutar inmediatamente
    if (isOnline) {
      syncPendingActions();
    }

    return action.id;
  }

  /// Guarda una acción en la base de datos
  Future<void> _saveAction(PendingAction action) async {
    if (_database == null) return;

    await _database!.insert(
      'pending_actions',
      action.toMap(),
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// Obtiene todas las acciones pendientes
  Future<List<PendingAction>> getPendingActions() async {
    if (_database == null) return [];

    final results = await _database!.query(
      'pending_actions',
      orderBy: 'created_at ASC',
    );

    return results.map((r) => PendingAction.fromMap(r)).toList();
  }

  /// Cuenta acciones pendientes
  Future<int> getPendingActionsCount() async {
    if (_database == null) return 0;

    final result = await _database!.rawQuery(
      'SELECT COUNT(*) as count FROM pending_actions',
    );
    
    return result.first['count'] as int? ?? 0;
  }

  /// Elimina una acción pendiente
  Future<void> removeAction(String actionId) async {
    if (_database == null) return;
    await _database!.delete(
      'pending_actions',
      where: 'id = ?',
      whereArgs: [actionId],
    );
  }

  /// Sincroniza todas las acciones pendientes
  Future<SyncResult> syncPendingActions() async {
    if (!isOnline || isSyncing) {
      return SyncResult(
        totalActions: 0,
        successCount: 0,
        failedCount: 0,
        errors: ['Not online or already syncing'],
        syncedAt: DateTime.now(),
      );
    }

    _updateStatus(ConnectivityStatus.syncing);

    final actions = await getPendingActions();
    int successCount = 0;
    int failedCount = 0;
    final errors = <String>[];

    debugPrint('[OfflineService] Syncing ${actions.length} pending actions...');

    for (final action in actions) {
      try {
        final success = await _executeAction(action);

        if (success) {
          await removeAction(action.id);
          successCount++;
        } else {
          // Incrementar contador de reintentos
          if (action.retryCount < _maxRetries) {
            await _saveAction(action.copyWith(retryCount: action.retryCount + 1));
          } else {
            // Máximo de reintentos alcanzado, eliminar
            await removeAction(action.id);
            errors.add('Action ${action.id} failed after $_maxRetries retries');
          }
          failedCount++;
        }
      } catch (e) {
        failedCount++;
        errors.add('Action ${action.id}: $e');
      }
    }

    _updateStatus(ConnectivityStatus.online);

    final result = SyncResult(
      totalActions: actions.length,
      successCount: successCount,
      failedCount: failedCount,
      errors: errors,
      syncedAt: DateTime.now(),
    );

    // Notificar a listeners
    for (final listener in _syncListeners) {
      listener(result);
    }

    debugPrint('[OfflineService] Sync completed: $successCount success, $failedCount failed');

    return result;
  }

  /// Ejecuta una acción pendiente
  Future<bool> _executeAction(PendingAction action) async {
    try {
      ApiResponse response;

      switch (action.method.toUpperCase()) {
        case 'POST':
          response = await _apiClient.postData(action.endpoint, action.data);
          break;
        case 'PUT':
          response = await _apiClient.putData(action.endpoint, action.data);
          break;
        case 'DELETE':
          response = await _apiClient.deleteData(action.endpoint);
          break;
        default:
          return false;
      }

      return response.success;
    } catch (e) {
      debugPrint('[OfflineService] Action execution error: $e');
      return false;
    }
  }

  // =========================================================================
  // LISTENERS Y UTILIDADES
  // =========================================================================

  /// Añade listener de sincronización
  void addSyncListener(void Function(SyncResult) listener) {
    _syncListeners.add(listener);
  }

  /// Elimina listener de sincronización
  void removeSyncListener(void Function(SyncResult) listener) {
    _syncListeners.remove(listener);
  }

  /// Obtiene estadísticas de uso offline
  Future<Map<String, dynamic>> getOfflineStats() async {
    if (_database == null) {
      return {'error': 'Database not initialized'};
    }

    final pendingCount = await getPendingActionsCount();
    
    final cacheResult = await _database!.rawQuery(
      'SELECT COUNT(*) as count FROM cache_data',
    );
    final cacheCount = cacheResult.first['count'] as int? ?? 0;

    final prefs = await SharedPreferences.getInstance();
    final lastSync = prefs.getString('last_sync_timestamp');

    return {
      'status': _status.name,
      'pending_actions': pendingCount,
      'cached_items': cacheCount,
      'last_sync': lastSync,
      'database_path': _database?.path,
    };
  }

  /// Fuerza una verificación de conectividad
  Future<void> checkConnectivity() async {
    final results = await Connectivity().checkConnectivity();
    await _handleConnectivityChange(results);
  }

  /// Limpia recursos
  Future<void> dispose() async {
    _retryTimer?.cancel();
    await _connectivitySubscription?.cancel();
    await _statusController.close();
    await _database?.close();
    _syncListeners.clear();
  }
}

/// Widget helper para mostrar estado de conexión
class ConnectionStatusIndicator {
  static String getStatusText(ConnectivityStatus status) {
    switch (status) {
      case ConnectivityStatus.online:
        return 'Conectado';
      case ConnectivityStatus.offline:
        return 'Sin conexión';
      case ConnectivityStatus.syncing:
        return 'Sincronizando...';
    }
  }

  static String getStatusIcon(ConnectivityStatus status) {
    switch (status) {
      case ConnectivityStatus.online:
        return '🟢';
      case ConnectivityStatus.offline:
        return '🔴';
      case ConnectivityStatus.syncing:
        return '🔄';
    }
  }
}
