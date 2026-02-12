import 'package:flutter_test/flutter_test.dart';
import 'package:chat_ia_apps/core/security/biometric_service.dart';
import 'package:local_auth/local_auth.dart';

void main() {
  group('BiometricResult', () {
    test('success result should have correct status', () {
      final result = BiometricResult.success();

      expect(result.isSuccess, isTrue);
      expect(result.status, equals(BiometricStatus.success));
      expect(result.message, isNull);
      expect(result.lockoutRemaining, isNull);
    });

    test('failed result should contain message', () {
      final result = BiometricResult.failed('Test failure');

      expect(result.isSuccess, isFalse);
      expect(result.status, equals(BiometricStatus.failed));
      expect(result.message, equals('Test failure'));
    });

    test('notAvailable result should indicate device limitation', () {
      final result = BiometricResult.notAvailable();

      expect(result.isSuccess, isFalse);
      expect(result.status, equals(BiometricStatus.notAvailable));
      expect(result.message, contains('no disponible'));
    });

    test('notEnrolled result should suggest setup', () {
      final result = BiometricResult.notEnrolled();

      expect(result.isSuccess, isFalse);
      expect(result.requiresSetup, isTrue);
      expect(result.status, equals(BiometricStatus.notEnrolled));
      expect(result.message, contains('configurados'));
    });

    test('lockedOut result should contain remaining duration', () {
      const duration = Duration(minutes: 15);
      final result = BiometricResult.lockedOut(duration);

      expect(result.isSuccess, isFalse);
      expect(result.isLockedOut, isTrue);
      expect(result.status, equals(BiometricStatus.lockedOut));
      expect(result.lockoutRemaining, equals(duration));
    });

    test('error result should contain error message', () {
      final result = BiometricResult.error('Platform error');

      expect(result.isSuccess, isFalse);
      expect(result.status, equals(BiometricStatus.error));
      expect(result.message, equals('Platform error'));
    });
  });

  group('BiometricTypeExtension', () {
    test('face should return Face ID', () {
      expect(BiometricType.face.displayName, equals('Face ID'));
      expect(BiometricType.face.icon, equals('👤'));
    });

    test('fingerprint should return Huella dactilar', () {
      expect(BiometricType.fingerprint.displayName, equals('Huella dactilar'));
      expect(BiometricType.fingerprint.icon, equals('👆'));
    });

    test('iris should return Iris', () {
      expect(BiometricType.iris.displayName, equals('Iris'));
      expect(BiometricType.iris.icon, equals('👁️'));
    });

    test('strong should return generic name', () {
      expect(BiometricType.strong.displayName, equals('Biométrico fuerte'));
    });

    test('weak should return generic name', () {
      expect(BiometricType.weak.displayName, equals('Biométrico débil'));
    });
  });

  group('BiometricService Configuration', () {
    test('maxFailureAttempts should be 5', () {
      expect(BiometricService.maxFailureAttempts, equals(5));
    });

    test('lockoutDuration should be 30 minutes', () {
      expect(
        BiometricService.lockoutDuration,
        equals(const Duration(minutes: 30)),
      );
    });

    test('sessionTimeout should be 1 hour', () {
      expect(
        BiometricService.sessionTimeout,
        equals(const Duration(hours: 1)),
      );
    });
  });
}
