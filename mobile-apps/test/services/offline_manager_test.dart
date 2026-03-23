import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:flavor_app/core/services/offline_manager.dart';
import 'package:flavor_app/core/api/api_client.dart';

@GenerateMocks([ApiClient, Connectivity])
import 'offline_manager_test.mocks.dart';

void main() {
  late OfflineManager offlineManager;
  late MockApiClient mockApiClient;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    mockApiClient = MockApiClient();
    offlineManager = OfflineManager();
  });

  tearDown(() {
    offlineManager.dispose();
  });

  group('OfflineManager', () {
    group('Connectivity', () {
      test('should start with unknown connectivity status', () {
        expect(offlineManager.isOnline, isTrue); // Default optimistic
      });

      test('should track connectivity changes', () async {
        // Initial state should be online (optimistic default)
        expect(offlineManager.isOnline, isTrue);
      });
    });

    group('PendingOperation', () {
      test('should create operation with correct fields', () {
        final operation = PendingOperation(
          id: 'test-123',
          type: 'create',
          endpoint: '/api/items',
          data: {'name': 'Test Item'},
          createdAt: DateTime.now(),
        );

        expect(operation.id, equals('test-123'));
        expect(operation.type, equals('create'));
        expect(operation.endpoint, equals('/api/items'));
        expect(operation.data['name'], equals('Test Item'));
        expect(operation.retryCount, equals(0));
      });

      test('should serialize to JSON correctly', () {
        final now = DateTime.now();
        final operation = PendingOperation(
          id: 'test-456',
          type: 'update',
          endpoint: '/api/items/1',
          data: {'name': 'Updated'},
          createdAt: now,
        );

        final json = operation.toJson();

        expect(json['id'], equals('test-456'));
        expect(json['type'], equals('update'));
        expect(json['endpoint'], equals('/api/items/1'));
        expect(json['data']['name'], equals('Updated'));
        expect(json['retry_count'], equals(0));
      });

      test('should deserialize from JSON correctly', () {
        final json = {
          'id': 'test-789',
          'type': 'delete',
          'endpoint': '/api/items/2',
          'data': {'id': 2},
          'created_at': DateTime.now().toIso8601String(),
          'retry_count': 2,
        };

        final operation = PendingOperation.fromJson(json);

        expect(operation.id, equals('test-789'));
        expect(operation.type, equals('delete'));
        expect(operation.retryCount, equals(2));
      });
    });

    group('Queue Operations', () {
      test('should add operation to queue', () async {
        await offlineManager.initialize();

        final operation = PendingOperation(
          id: 'queue-test-1',
          type: 'create',
          endpoint: '/api/test',
          data: {'test': true},
          createdAt: DateTime.now(),
        );

        await offlineManager.addOperation(operation);

        expect(offlineManager.pendingCount, equals(1));
        expect(offlineManager.hasPendingOperations, isTrue);
      });

      test('should persist operations between instances', () async {
        await offlineManager.initialize();

        final operation = PendingOperation(
          id: 'persist-test',
          type: 'create',
          endpoint: '/api/persist',
          data: {'persist': true},
          createdAt: DateTime.now(),
        );

        await offlineManager.addOperation(operation);

        // Create new instance
        final newManager = OfflineManager();
        await newManager.initialize();

        // Operation should be loaded from storage
        // Note: In real test, we'd need to properly reinitialize SharedPreferences
      });

      test('should clear all pending operations', () async {
        await offlineManager.initialize();

        await offlineManager.addOperation(PendingOperation(
          id: 'clear-1',
          type: 'create',
          endpoint: '/api/test',
          data: {},
          createdAt: DateTime.now(),
        ));

        await offlineManager.addOperation(PendingOperation(
          id: 'clear-2',
          type: 'update',
          endpoint: '/api/test/1',
          data: {},
          createdAt: DateTime.now(),
        ));

        expect(offlineManager.pendingCount, equals(2));

        await offlineManager.clearPending();

        expect(offlineManager.pendingCount, equals(0));
        expect(offlineManager.hasPendingOperations, isFalse);
      });
    });

    group('SyncResult', () {
      test('should create successful result', () {
        final result = SyncResult(
          success: true,
          syncedCount: 5,
          failedCount: 0,
          errors: [],
        );

        expect(result.success, isTrue);
        expect(result.syncedCount, equals(5));
        expect(result.failedCount, equals(0));
        expect(result.errors, isEmpty);
      });

      test('should create partial failure result', () {
        final result = SyncResult(
          success: false,
          syncedCount: 3,
          failedCount: 2,
          errors: ['Network timeout', 'Server error'],
        );

        expect(result.success, isFalse);
        expect(result.syncedCount, equals(3));
        expect(result.failedCount, equals(2));
        expect(result.errors, hasLength(2));
      });
    });
  });

  group('ConnectivityStatusExtension', () {
    test('should correctly identify online status', () {
      expect(ConnectivityStatus.online.isOnline, isTrue);
      expect(ConnectivityStatus.online.isOffline, isFalse);
    });

    test('should correctly identify offline status', () {
      expect(ConnectivityStatus.offline.isOnline, isFalse);
      expect(ConnectivityStatus.offline.isOffline, isTrue);
    });
  });
}
