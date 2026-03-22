import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'dart:convert';

// Mock classes
class MockApiClient extends Mock {
  Future<MockApiResponse> getData(String endpoint);
  Future<MockApiResponse> postData(String endpoint, Map<String, dynamic> data);
}

class MockApiResponse {
  final bool success;
  final Map<String, dynamic>? data;
  final String? errorMessage;

  MockApiResponse({
    this.success = true,
    this.data,
    this.errorMessage,
  });
}

// Simplified service for testing
class AppManifestServiceTestable {
  final MockApiClient apiClient;
  final String deviceId;

  Map<String, dynamic>? _cachedManifest;
  String? _lastVersion;

  AppManifestServiceTestable({
    required this.apiClient,
    required this.deviceId,
  });

  Future<Map<String, dynamic>?> fetchManifest({bool forceRefresh = false}) async {
    try {
      final response = await apiClient.getData(
        '/manifest${forceRefresh ? '?refresh=1' : ''}',
      );
      
      if (response.success && response.data != null) {
        _cachedManifest = response.data;
        _lastVersion = response.data!['version'] as String?;
        return response.data;
      }
      return null;
    } catch (e) {
      return _cachedManifest; // Return cached on error
    }
  }

  bool hasNewerVersion(String? currentVersion, String? newVersion) {
    if (currentVersion == null || newVersion == null) return newVersion != null;
    return currentVersion != newVersion;
  }

  Map<String, dynamic>? get cachedManifest => _cachedManifest;
  String? get lastVersion => _lastVersion;
}

void main() {
  group('AppManifestService', () {
    late MockApiClient mockApiClient;
    late AppManifestServiceTestable service;

    setUp(() {
      mockApiClient = MockApiClient();
      service = AppManifestServiceTestable(
        apiClient: mockApiClient,
        deviceId: 'test_device_123',
      );
    });

    group('fetchManifest', () {
      test('should fetch manifest successfully', () async {
        final testManifest = {
          'version': 'v2.0-20260321-abc123',
          'schema_version': '2.0',
          'site': {'name': 'Test Site'},
          'modules': {'active': []},
        };

        when(mockApiClient.getData('/manifest'))
            .thenAnswer((_) async => MockApiResponse(
                  success: true,
                  data: testManifest,
                ));

        final result = await service.fetchManifest();

        expect(result, isNotNull);
        expect(result!['version'], equals('v2.0-20260321-abc123'));
        expect(service.lastVersion, equals('v2.0-20260321-abc123'));
      });

      test('should return cached manifest on error', () async {
        // First successful fetch
        final testManifest = {
          'version': 'v2.0-cached',
          'site': {'name': 'Cached Site'},
        };

        when(mockApiClient.getData('/manifest'))
            .thenAnswer((_) async => MockApiResponse(
                  success: true,
                  data: testManifest,
                ));

        await service.fetchManifest();
        expect(service.cachedManifest, isNotNull);

        // Simulate network error
        when(mockApiClient.getData('/manifest'))
            .thenThrow(Exception('Network error'));

        final result = await service.fetchManifest();

        expect(result, isNotNull);
        expect(result!['version'], equals('v2.0-cached'));
      });

      test('should pass refresh parameter when forceRefresh is true', () async {
        when(mockApiClient.getData('/manifest?refresh=1'))
            .thenAnswer((_) async => MockApiResponse(
                  success: true,
                  data: {'version': 'v2.0-fresh'},
                ));

        await service.fetchManifest(forceRefresh: true);

        verify(mockApiClient.getData('/manifest?refresh=1')).called(1);
      });
    });

    group('hasNewerVersion', () {
      test('should return true when versions are different', () {
        expect(
          service.hasNewerVersion('v2.0-old', 'v2.0-new'),
          isTrue,
        );
      });

      test('should return false when versions are the same', () {
        expect(
          service.hasNewerVersion('v2.0-same', 'v2.0-same'),
          isFalse,
        );
      });

      test('should return true when current is null but new is not', () {
        expect(
          service.hasNewerVersion(null, 'v2.0-new'),
          isTrue,
        );
      });

      test('should return false when new version is null', () {
        expect(
          service.hasNewerVersion('v2.0-current', null),
          isFalse,
        );
      });
    });
  });
}
