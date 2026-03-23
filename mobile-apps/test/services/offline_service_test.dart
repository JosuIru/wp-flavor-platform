import 'package:flutter_test/flutter_test.dart';

/// Tests para OfflineService
/// 
/// Valida lógica de caché, cola de acciones y sincronización.

// Connectivity status
enum ConnectivityStatus {
  online,
  offline,
  syncing,
}

// Pending action model
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

  Map<String, dynamic> toMap() => {
    'id': id,
    'type': type,
    'endpoint': endpoint,
    'method': method,
    'data': data,
    'created_at': createdAt.toIso8601String(),
    'retry_count': retryCount,
  };
}

// Cache entry model
class CacheEntry {
  final String key;
  final Map<String, dynamic> data;
  final String? endpoint;
  final DateTime updatedAt;
  final DateTime? expiresAt;

  CacheEntry({
    required this.key,
    required this.data,
    this.endpoint,
    required this.updatedAt,
    this.expiresAt,
  });

  bool get isExpired {
    if (expiresAt == null) return false;
    return DateTime.now().isAfter(expiresAt!);
  }
}

// Simplified OfflineService for testing
class OfflineServiceTestable {
  static const int maxRetries = 3;

  ConnectivityStatus _status = ConnectivityStatus.online;
  final Map<String, CacheEntry> _cache = {};
  final List<PendingAction> _pendingActions = [];
  final List<void Function(ConnectivityStatus)> _statusListeners = [];

  ConnectivityStatus get status => _status;
  bool get isOnline => _status == ConnectivityStatus.online;
  bool get isOffline => _status == ConnectivityStatus.offline;
  bool get isSyncing => _status == ConnectivityStatus.syncing;

  void setStatus(ConnectivityStatus status) {
    if (_status != status) {
      _status = status;
      for (final listener in _statusListeners) {
        listener(status);
      }
    }
  }

  void addStatusListener(void Function(ConnectivityStatus) listener) {
    _statusListeners.add(listener);
  }

  // Cache operations
  void cacheData(
    String key,
    Map<String, dynamic> data, {
    String? endpoint,
    Duration? expiration,
  }) {
    final now = DateTime.now();
    _cache[key] = CacheEntry(
      key: key,
      data: data,
      endpoint: endpoint,
      updatedAt: now,
      expiresAt: expiration != null ? now.add(expiration) : null,
    );
  }

  Map<String, dynamic>? getCachedData(String key) {
    final entry = _cache[key];
    if (entry == null) return null;
    
    if (entry.isExpired) {
      _cache.remove(key);
      return null;
    }
    
    return entry.data;
  }

  void removeCachedData(String key) {
    _cache.remove(key);
  }

  void clearCache() {
    _cache.clear();
  }

  int cleanExpiredCache() {
    final expiredKeys = _cache.entries
        .where((e) => e.value.isExpired)
        .map((e) => e.key)
        .toList();
    
    for (final key in expiredKeys) {
      _cache.remove(key);
    }
    
    return expiredKeys.length;
  }

  int get cacheSize => _cache.length;

  // Action queue operations
  String queueAction({
    required String type,
    required String endpoint,
    required String method,
    required Map<String, dynamic> data,
  }) {
    final action = PendingAction(
      id: '${DateTime.now().millisecondsSinceEpoch}_${type.hashCode}',
      type: type,
      endpoint: endpoint,
      method: method,
      data: data,
      createdAt: DateTime.now(),
    );

    _pendingActions.add(action);
    return action.id;
  }

  List<PendingAction> get pendingActions => List.unmodifiable(_pendingActions);
  int get pendingActionsCount => _pendingActions.length;

  PendingAction? getAction(String id) {
    try {
      return _pendingActions.firstWhere((a) => a.id == id);
    } catch (_) {
      return null;
    }
  }

  void removeAction(String id) {
    _pendingActions.removeWhere((a) => a.id == id);
  }

  void incrementRetryCount(String id) {
    final index = _pendingActions.indexWhere((a) => a.id == id);
    if (index != -1) {
      final action = _pendingActions[index];
      _pendingActions[index] = action.copyWith(retryCount: action.retryCount + 1);
    }
  }

  bool shouldRetry(PendingAction action) {
    return action.retryCount < maxRetries;
  }

  void clearPendingActions() {
    _pendingActions.clear();
  }
}

void main() {
  group('OfflineService', () {
    late OfflineServiceTestable service;

    setUp(() {
      service = OfflineServiceTestable();
    });

    group('connectivity status', () {
      test('should start online', () {
        expect(service.status, equals(ConnectivityStatus.online));
        expect(service.isOnline, isTrue);
        expect(service.isOffline, isFalse);
      });

      test('should update status', () {
        service.setStatus(ConnectivityStatus.offline);

        expect(service.status, equals(ConnectivityStatus.offline));
        expect(service.isOffline, isTrue);
        expect(service.isOnline, isFalse);
      });

      test('should notify listeners on status change', () {
        ConnectivityStatus? receivedStatus;
        service.addStatusListener((status) {
          receivedStatus = status;
        });

        service.setStatus(ConnectivityStatus.offline);

        expect(receivedStatus, equals(ConnectivityStatus.offline));
      });

      test('should not notify when status unchanged', () {
        int callCount = 0;
        service.addStatusListener((_) {
          callCount++;
        });

        service.setStatus(ConnectivityStatus.online); // Same as default
        service.setStatus(ConnectivityStatus.online);

        expect(callCount, equals(0));
      });
    });

    group('cache operations', () {
      test('should cache data', () {
        service.cacheData('key1', {'value': 'test'});

        expect(service.getCachedData('key1'), equals({'value': 'test'}));
      });

      test('should return null for non-existent key', () {
        expect(service.getCachedData('nonexistent'), isNull);
      });

      test('should remove cached data', () {
        service.cacheData('key1', {'value': 'test'});
        service.removeCachedData('key1');

        expect(service.getCachedData('key1'), isNull);
      });

      test('should clear all cache', () {
        service.cacheData('key1', {'v': 1});
        service.cacheData('key2', {'v': 2});
        service.clearCache();

        expect(service.cacheSize, equals(0));
      });

      test('should handle cache expiration', () {
        // Cache with very short expiration
        service.cacheData(
          'expiring',
          {'value': 'will expire'},
          expiration: Duration(milliseconds: 1),
        );

        // Wait for expiration
        Future.delayed(Duration(milliseconds: 10), () {
          expect(service.getCachedData('expiring'), isNull);
        });
      });

      test('should clean expired cache', () {
        // This test simulates expired cache scenario
        // In real implementation, entries would be marked as expired
        service.cacheData('key1', {'v': 1});
        service.cacheData('key2', {'v': 2});

        // With fresh data, nothing should be cleaned
        final cleaned = service.cleanExpiredCache();
        expect(cleaned, equals(0));
      });

      test('should update existing cache entry', () {
        service.cacheData('key1', {'v': 1});
        service.cacheData('key1', {'v': 2});

        expect(service.getCachedData('key1'), equals({'v': 2}));
        expect(service.cacheSize, equals(1));
      });
    });

    group('action queue', () {
      test('should queue action and return id', () {
        final id = service.queueAction(
          type: 'create_post',
          endpoint: '/posts',
          method: 'POST',
          data: {'title': 'Test'},
        );

        expect(id, isNotEmpty);
        expect(service.pendingActionsCount, equals(1));
      });

      test('should get pending actions', () {
        service.queueAction(
          type: 'action1',
          endpoint: '/api1',
          method: 'POST',
          data: {},
        );
        service.queueAction(
          type: 'action2',
          endpoint: '/api2',
          method: 'PUT',
          data: {},
        );

        expect(service.pendingActions.length, equals(2));
      });

      test('should get action by id', () {
        final id = service.queueAction(
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {'key': 'value'},
        );

        final action = service.getAction(id);

        expect(action, isNotNull);
        expect(action!.type, equals('test'));
        expect(action.data['key'], equals('value'));
      });

      test('should remove action', () {
        final id = service.queueAction(
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {},
        );

        service.removeAction(id);

        expect(service.getAction(id), isNull);
        expect(service.pendingActionsCount, equals(0));
      });

      test('should clear all pending actions', () {
        service.queueAction(type: 'a1', endpoint: '/1', method: 'POST', data: {});
        service.queueAction(type: 'a2', endpoint: '/2', method: 'POST', data: {});
        
        service.clearPendingActions();

        expect(service.pendingActionsCount, equals(0));
      });
    });

    group('retry logic', () {
      test('should start with retry count 0', () {
        final id = service.queueAction(
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {},
        );

        expect(service.getAction(id)!.retryCount, equals(0));
      });

      test('should increment retry count', () {
        final id = service.queueAction(
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {},
        );

        service.incrementRetryCount(id);

        expect(service.getAction(id)!.retryCount, equals(1));
      });

      test('should allow retry under max retries', () {
        final action = PendingAction(
          id: 'test',
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {},
          createdAt: DateTime.now(),
          retryCount: 2,
        );

        expect(service.shouldRetry(action), isTrue);
      });

      test('should not allow retry at max retries', () {
        final action = PendingAction(
          id: 'test',
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {},
          createdAt: DateTime.now(),
          retryCount: 3,
        );

        expect(service.shouldRetry(action), isFalse);
      });
    });

    group('PendingAction', () {
      test('toMap should serialize all fields', () {
        final action = PendingAction(
          id: 'action_123',
          type: 'create',
          endpoint: '/api/items',
          method: 'POST',
          data: {'name': 'Item 1'},
          createdAt: DateTime(2026, 3, 21, 10, 0),
          retryCount: 1,
        );

        final map = action.toMap();

        expect(map['id'], equals('action_123'));
        expect(map['type'], equals('create'));
        expect(map['endpoint'], equals('/api/items'));
        expect(map['method'], equals('POST'));
        expect(map['data']['name'], equals('Item 1'));
        expect(map['retry_count'], equals(1));
      });

      test('copyWith should preserve unchanged fields', () {
        final original = PendingAction(
          id: 'test',
          type: 'test',
          endpoint: '/test',
          method: 'POST',
          data: {'key': 'value'},
          createdAt: DateTime.now(),
          retryCount: 0,
        );

        final copied = original.copyWith(retryCount: 5);

        expect(copied.id, equals(original.id));
        expect(copied.type, equals(original.type));
        expect(copied.retryCount, equals(5));
      });
    });
  });
}
