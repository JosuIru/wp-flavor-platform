import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:flavor_app/core/services/feature_flags_service.dart';
import 'package:flavor_app/core/api/api_client.dart';

@GenerateMocks([ApiClient])
import 'feature_flags_test.mocks.dart';

void main() {
  late MockApiClient mockApiClient;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    mockApiClient = MockApiClient();
  });

  group('FeatureFlagsService', () {
    group('Flag Checking', () {
      test('should return default value for unknown flag', () {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        expect(service.isEnabled('unknown_flag'), isFalse);
        expect(service.isEnabled('unknown_flag', defaultValue: true), isTrue);
      });

      test('should support bracket operator for flag access', () {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        // Override flag for testing
        service.overrideFlag('test_flag', true);

        expect(service['test_flag'], isTrue);
        expect(service['nonexistent'], isFalse);
      });

      test('should override flag value locally', () {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        expect(service.isEnabled('override_test'), isFalse);

        service.overrideFlag('override_test', true);

        expect(service.isEnabled('override_test'), isTrue);
      });
    });

    group('Multiple Flag Checks', () {
      test('should check if all flags are enabled', () {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        service.overrideFlag('flag_a', true);
        service.overrideFlag('flag_b', true);
        service.overrideFlag('flag_c', false);

        expect(service.allEnabled(['flag_a', 'flag_b']), isTrue);
        expect(service.allEnabled(['flag_a', 'flag_c']), isFalse);
        expect(service.allEnabled(['flag_a', 'flag_b', 'flag_c']), isFalse);
      });

      test('should check if any flag is enabled', () {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        service.overrideFlag('flag_x', false);
        service.overrideFlag('flag_y', true);
        service.overrideFlag('flag_z', false);

        expect(service.anyEnabled(['flag_x', 'flag_y']), isTrue);
        expect(service.anyEnabled(['flag_x', 'flag_z']), isFalse);
        expect(service.anyEnabled(['flag_y']), isTrue);
      });
    });

    group('Cache', () {
      test('should clear cache', () async {
        final service = FeatureFlagsService(apiClient: mockApiClient);

        service.overrideFlag('cached_flag', true);
        expect(service.isEnabled('cached_flag'), isTrue);

        await service.clearCache();

        // After clearing, flags should be empty
        expect(service.flags, isEmpty);
      });
    });
  });

  group('CommonFlags', () {
    test('should have correct constant values', () {
      expect(CommonFlags.darkMode, equals('dark_mode'));
      expect(CommonFlags.biometricLogin, equals('biometric_login'));
      expect(CommonFlags.offlineMode, equals('offline_mode'));
      expect(CommonFlags.pushNotifications, equals('push_notifications'));
      expect(CommonFlags.analyticsEnabled, equals('analytics_enabled'));
      expect(CommonFlags.crashReporting, equals('crash_reporting'));
      expect(CommonFlags.inAppReview, equals('in_app_review'));
      expect(CommonFlags.newUiEnabled, equals('new_ui_enabled'));
    });
  });

  group('FeatureFlagExtension', () {
    test('should execute callback when flag is enabled', () {
      final service = FeatureFlagsService(apiClient: mockApiClient);
      service.overrideFlag('enabled_flag', true);

      var executed = false;
      final result = service.whenEnabled('enabled_flag', () {
        executed = true;
        return 'success';
      });

      expect(executed, isTrue);
      expect(result, equals('success'));
    });

    test('should return default when flag is disabled', () {
      final service = FeatureFlagsService(apiClient: mockApiClient);
      service.overrideFlag('disabled_flag', false);

      var executed = false;
      final result = service.whenEnabled(
        'disabled_flag',
        () {
          executed = true;
          return 'success';
        },
        defaultValue: 'default',
      );

      expect(executed, isFalse);
      expect(result, equals('default'));
    });

    test('should return null when flag disabled and no default', () {
      final service = FeatureFlagsService(apiClient: mockApiClient);

      final result = service.whenEnabled('unknown', () => 'value');

      expect(result, isNull);
    });
  });
}
