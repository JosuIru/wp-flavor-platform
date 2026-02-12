import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';
import '../security/biometric_service.dart';

/// Estado de autenticación biométrica
class BiometricState {
  final bool isAvailable;
  final bool isEnabled;
  final bool isAuthenticated;
  final List<BiometricType> availableTypes;
  final String? errorMessage;
  final Duration? lockoutRemaining;

  const BiometricState({
    this.isAvailable = false,
    this.isEnabled = false,
    this.isAuthenticated = false,
    this.availableTypes = const [],
    this.errorMessage,
    this.lockoutRemaining,
  });

  BiometricState copyWith({
    bool? isAvailable,
    bool? isEnabled,
    bool? isAuthenticated,
    List<BiometricType>? availableTypes,
    String? errorMessage,
    Duration? lockoutRemaining,
  }) {
    return BiometricState(
      isAvailable: isAvailable ?? this.isAvailable,
      isEnabled: isEnabled ?? this.isEnabled,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      availableTypes: availableTypes ?? this.availableTypes,
      errorMessage: errorMessage,
      lockoutRemaining: lockoutRemaining,
    );
  }

  /// Descripción legible del tipo de biometría disponible
  String get biometricDescription {
    if (availableTypes.isEmpty) return 'Sin biométricos';
    if (availableTypes.contains(BiometricType.face)) return 'Face ID';
    if (availableTypes.contains(BiometricType.fingerprint)) return 'Huella dactilar';
    return 'Biométricos';
  }
}

/// Notifier para gestionar la autenticación biométrica
class BiometricNotifier extends StateNotifier<BiometricState> {
  final BiometricService _service = BiometricService.instance;

  BiometricNotifier() : super(const BiometricState()) {
    _initialize();
  }

  /// Inicializa el estado de biometría
  Future<void> _initialize() async {
    final isAvailable = await _service.hasBiometrics();
    final isEnabled = await _service.isBiometricEnabled();
    final types = await _service.getAvailableBiometrics();
    final isAuthenticated = await _service.isSessionValid();

    state = state.copyWith(
      isAvailable: isAvailable,
      isEnabled: isEnabled,
      isAuthenticated: isAuthenticated,
      availableTypes: types,
    );
  }

  /// Habilita o deshabilita la autenticación biométrica
  Future<bool> toggleBiometric(bool enable) async {
    if (enable && !state.isAvailable) {
      state = state.copyWith(
        errorMessage: 'No hay biométricos configurados en el dispositivo',
      );
      return false;
    }

    if (enable) {
      // Solicitar autenticación antes de habilitar
      final result = await _service.authenticate(
        reason: 'Confirma tu identidad para habilitar la autenticación biométrica',
      );

      if (!result.isSuccess) {
        state = state.copyWith(
          errorMessage: result.message,
          lockoutRemaining: result.lockoutRemaining,
        );
        return false;
      }
    }

    await _service.setBiometricEnabled(enable);
    state = state.copyWith(
      isEnabled: enable,
      isAuthenticated: enable,
      errorMessage: null,
    );

    return true;
  }

  /// Solicita autenticación biométrica
  Future<bool> authenticate({
    String reason = 'Autentícate para continuar',
    bool sensitiveTransaction = false,
  }) async {
    // Verificar si la sesión aún es válida
    if (await _service.isSessionValid() && !sensitiveTransaction) {
      state = state.copyWith(isAuthenticated: true);
      return true;
    }

    final result = await _service.authenticate(
      reason: reason,
      sensitiveTransaction: sensitiveTransaction,
    );

    if (result.isSuccess) {
      state = state.copyWith(
        isAuthenticated: true,
        errorMessage: null,
      );
      return true;
    }

    state = state.copyWith(
      isAuthenticated: false,
      errorMessage: result.message,
      lockoutRemaining: result.lockoutRemaining,
    );

    return false;
  }

  /// Invalida la sesión actual
  Future<void> invalidateSession() async {
    await _service.invalidateSession();
    state = state.copyWith(isAuthenticated: false);
  }

  /// Limpia el mensaje de error
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }

  /// Refresca el estado
  Future<void> refresh() async {
    await _initialize();
  }
}

/// Provider para el estado de autenticación biométrica
final biometricProvider = StateNotifierProvider<BiometricNotifier, BiometricState>((ref) {
  return BiometricNotifier();
});

/// Provider para verificar si biometría está disponible
final biometricAvailableProvider = FutureProvider<bool>((ref) async {
  return BiometricService.instance.hasBiometrics();
});

/// Provider para los tipos de biométricos disponibles
final biometricTypesProvider = FutureProvider<List<BiometricType>>((ref) async {
  return BiometricService.instance.getAvailableBiometrics();
});
