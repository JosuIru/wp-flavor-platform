import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';
import 'package:chat_ia_apps/core/providers/biometric_provider.dart';

void main() {
  group('BiometricState', () {
    test('default state should have all flags false', () {
      const state = BiometricState();

      expect(state.isAvailable, isFalse);
      expect(state.isEnabled, isFalse);
      expect(state.isAuthenticated, isFalse);
      expect(state.availableTypes, isEmpty);
      expect(state.errorMessage, isNull);
      expect(state.lockoutRemaining, isNull);
    });

    test('copyWith should update only specified fields', () {
      const state = BiometricState(
        isAvailable: true,
        isEnabled: true,
      );

      final newState = state.copyWith(isAuthenticated: true);

      expect(newState.isAvailable, isTrue);
      expect(newState.isEnabled, isTrue);
      expect(newState.isAuthenticated, isTrue);
    });

    test('copyWith should clear error when set to null', () {
      const state = BiometricState(
        errorMessage: 'Some error',
      );

      final newState = state.copyWith(errorMessage: null);

      expect(newState.errorMessage, isNull);
    });

    group('biometricDescription', () {
      test('should return Face ID when face is available', () {
        const state = BiometricState(
          availableTypes: [BiometricType.face],
        );

        expect(state.biometricDescription, equals('Face ID'));
      });

      test('should return Huella dactilar when fingerprint is available', () {
        const state = BiometricState(
          availableTypes: [BiometricType.fingerprint],
        );

        expect(state.biometricDescription, equals('Huella dactilar'));
      });

      test('should prefer Face ID over fingerprint', () {
        const state = BiometricState(
          availableTypes: [BiometricType.fingerprint, BiometricType.face],
        );

        expect(state.biometricDescription, equals('Face ID'));
      });

      test('should return Sin biométricos when empty', () {
        const state = BiometricState(
          availableTypes: [],
        );

        expect(state.biometricDescription, equals('Sin biométricos'));
      });

      test('should return generic when only strong/weak', () {
        const state = BiometricState(
          availableTypes: [BiometricType.strong],
        );

        expect(state.biometricDescription, equals('Biométricos'));
      });
    });
  });

  group('BiometricNotifier', () {
    test('should initialize with default state', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final state = container.read(biometricProvider);

      // Estado inicial antes de la inicialización asíncrona
      expect(state, isA<BiometricState>());
    });

    test('clearError should remove error message', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final notifier = container.read(biometricProvider.notifier);
      notifier.clearError();

      final state = container.read(biometricProvider);
      expect(state.errorMessage, isNull);
    });
  });

  group('biometricAvailableProvider', () {
    test('should return a FutureProvider of bool', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final provider = container.read(biometricAvailableProvider);

      expect(provider, isA<AsyncValue<bool>>());
    });
  });

  group('biometricTypesProvider', () {
    test('should return a FutureProvider of List<BiometricType>', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final provider = container.read(biometricTypesProvider);

      expect(provider, isA<AsyncValue<List<BiometricType>>>());
    });
  });
}
