import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/logger.dart';

/// Servicio de autenticación biométrica
///
/// Proporciona autenticación mediante huella dactilar o reconocimiento facial
/// con fallback a PIN/patrón del dispositivo.
class BiometricService {
  static BiometricService? _instance;
  static BiometricService get instance => _instance ??= BiometricService._();

  BiometricService._();

  final LocalAuthentication _localAuth = LocalAuthentication();
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  // Claves de almacenamiento
  static const String _keyBiometricEnabled = 'biometric_enabled';
  static const String _keyLastBiometricAuth = 'last_biometric_auth';
  static const String _keyBiometricFailures = 'biometric_failures';

  // Configuración
  static const int maxFailureAttempts = 5;
  static const Duration lockoutDuration = Duration(minutes: 30);
  static const Duration sessionTimeout = Duration(hours: 1);

  /// Verifica si el dispositivo soporta autenticación biométrica
  Future<bool> isDeviceSupported() async {
    try {
      return await _localAuth.isDeviceSupported();
    } on PlatformException catch (e) {
      Logger.e('Error verificando soporte biométrico: $e', tag: 'Biometric', error: e);
      return false;
    }
  }

  /// Verifica si hay biométricos configurados en el dispositivo
  Future<bool> hasBiometrics() async {
    try {
      final canCheck = await _localAuth.canCheckBiometrics;
      if (!canCheck) return false;

      final biometrics = await _localAuth.getAvailableBiometrics();
      return biometrics.isNotEmpty;
    } on PlatformException catch (e) {
      Logger.e('Error obteniendo biométricos: $e', tag: 'Biometric', error: e);
      return false;
    }
  }

  /// Obtiene los tipos de biométricos disponibles
  Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _localAuth.getAvailableBiometrics();
    } on PlatformException catch (e) {
      Logger.e('Error obteniendo tipos biométricos: $e', tag: 'Biometric', error: e);
      return [];
    }
  }

  /// Verifica si la autenticación biométrica está habilitada por el usuario
  Future<bool> isBiometricEnabled() async {
    final enabled = await _secureStorage.read(key: _keyBiometricEnabled);
    return enabled == 'true';
  }

  /// Habilita o deshabilita la autenticación biométrica
  Future<void> setBiometricEnabled(bool enabled) async {
    await _secureStorage.write(
      key: _keyBiometricEnabled,
      value: enabled.toString(),
    );
    Logger.i('Autenticación biométrica ${enabled ? "habilitada" : "deshabilitada"}', tag: 'Biometric');
  }

  /// Verifica si el usuario está bloqueado por intentos fallidos
  Future<bool> isLockedOut() async {
    final failuresStr = await _secureStorage.read(key: _keyBiometricFailures);
    if (failuresStr == null) return false;

    try {
      final parts = failuresStr.split('|');
      if (parts.length != 2) return false;

      final failures = int.parse(parts[0]);
      final lastFailure = DateTime.parse(parts[1]);

      if (failures >= maxFailureAttempts) {
        final lockoutEnd = lastFailure.add(lockoutDuration);
        if (DateTime.now().isBefore(lockoutEnd)) {
          Logger.w('Usuario bloqueado hasta $lockoutEnd', tag: 'Biometric');
          return true;
        }
        // Lockout expirado, resetear
        await _resetFailures();
      }
    } catch (e) {
      await _resetFailures();
    }

    return false;
  }

  /// Obtiene el tiempo restante de bloqueo
  Future<Duration?> getLockoutRemaining() async {
    final failuresStr = await _secureStorage.read(key: _keyBiometricFailures);
    if (failuresStr == null) return null;

    try {
      final parts = failuresStr.split('|');
      if (parts.length != 2) return null;

      final failures = int.parse(parts[0]);
      final lastFailure = DateTime.parse(parts[1]);

      if (failures >= maxFailureAttempts) {
        final lockoutEnd = lastFailure.add(lockoutDuration);
        final remaining = lockoutEnd.difference(DateTime.now());
        if (remaining.isNegative) return null;
        return remaining;
      }
    } catch (e) {
      // Ignorar
    }

    return null;
  }

  /// Registra un intento fallido
  Future<void> _recordFailure() async {
    final failuresStr = await _secureStorage.read(key: _keyBiometricFailures);
    int failures = 0;

    if (failuresStr != null) {
      try {
        failures = int.parse(failuresStr.split('|')[0]);
      } catch (e) {
        // Ignorar
      }
    }

    failures++;
    await _secureStorage.write(
      key: _keyBiometricFailures,
      value: '$failures|${DateTime.now().toIso8601String()}',
    );

    Logger.w('Intento fallido de autenticación biométrica ($failures/$maxFailureAttempts)', tag: 'Biometric');
  }

  /// Resetea los intentos fallidos
  Future<void> _resetFailures() async {
    await _secureStorage.delete(key: _keyBiometricFailures);
  }

  /// Actualiza la última autenticación exitosa
  Future<void> _updateLastAuth() async {
    await _secureStorage.write(
      key: _keyLastBiometricAuth,
      value: DateTime.now().toIso8601String(),
    );
    await _resetFailures();
  }

  /// Verifica si la sesión biométrica aún es válida
  Future<bool> isSessionValid() async {
    final lastAuthStr = await _secureStorage.read(key: _keyLastBiometricAuth);
    if (lastAuthStr == null) return false;

    try {
      final lastAuth = DateTime.parse(lastAuthStr);
      final sessionEnd = lastAuth.add(sessionTimeout);
      return DateTime.now().isBefore(sessionEnd);
    } catch (e) {
      return false;
    }
  }

  /// Solicita autenticación biométrica
  ///
  /// [reason] - Mensaje que se muestra al usuario
  /// [useErrorDialogs] - Si se deben mostrar diálogos de error del sistema
  /// [stickyAuth] - Si la autenticación debe persistir durante interrupciones
  /// [sensitiveTransaction] - Si es una transacción sensible (requiere biométrico fuerte)
  Future<BiometricResult> authenticate({
    String reason = 'Autentícate para continuar',
    bool useErrorDialogs = true,
    bool stickyAuth = true,
    bool sensitiveTransaction = false,
  }) async {
    // Verificar si está bloqueado
    if (await isLockedOut()) {
      final remaining = await getLockoutRemaining();
      return BiometricResult.lockedOut(remaining);
    }

    // Verificar si hay biométricos disponibles
    if (!await hasBiometrics()) {
      return BiometricResult.notAvailable();
    }

    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: reason,
        options: AuthenticationOptions(
          useErrorDialogs: useErrorDialogs,
          stickyAuth: stickyAuth,
          sensitiveTransaction: sensitiveTransaction,
          biometricOnly: sensitiveTransaction, // Solo biométrico para transacciones sensibles
        ),
      );

      if (authenticated) {
        await _updateLastAuth();
        Logger.i('Autenticación biométrica exitosa', tag: 'Biometric');
        return BiometricResult.success();
      } else {
        await _recordFailure();
        return BiometricResult.failed('Autenticación cancelada o fallida');
      }
    } on PlatformException catch (e) {
      Logger.e('Error en autenticación biométrica: ${e.message}', tag: 'Biometric', error: e);

      switch (e.code) {
        case 'NotEnrolled':
          return BiometricResult.notEnrolled();
        case 'LockedOut':
        case 'PermanentlyLockedOut':
          return BiometricResult.lockedOut(lockoutDuration);
        case 'NotAvailable':
          return BiometricResult.notAvailable();
        default:
          await _recordFailure();
          return BiometricResult.error(e.message ?? 'Error desconocido');
      }
    }
  }

  /// Invalida la sesión biométrica actual
  Future<void> invalidateSession() async {
    await _secureStorage.delete(key: _keyLastBiometricAuth);
    Logger.i('Sesión biométrica invalidada', tag: 'Biometric');
  }

  /// Limpia todos los datos de autenticación biométrica
  Future<void> clearAll() async {
    await _secureStorage.delete(key: _keyBiometricEnabled);
    await _secureStorage.delete(key: _keyLastBiometricAuth);
    await _secureStorage.delete(key: _keyBiometricFailures);
    Logger.i('Datos biométricos limpiados', tag: 'Biometric');
  }
}

/// Resultado de autenticación biométrica
class BiometricResult {
  final BiometricStatus status;
  final String? message;
  final Duration? lockoutRemaining;

  BiometricResult._({
    required this.status,
    this.message,
    this.lockoutRemaining,
  });

  factory BiometricResult.success() => BiometricResult._(
    status: BiometricStatus.success,
  );

  factory BiometricResult.failed(String message) => BiometricResult._(
    status: BiometricStatus.failed,
    message: message,
  );

  factory BiometricResult.notAvailable() => BiometricResult._(
    status: BiometricStatus.notAvailable,
    message: 'Autenticación biométrica no disponible en este dispositivo',
  );

  factory BiometricResult.notEnrolled() => BiometricResult._(
    status: BiometricStatus.notEnrolled,
    message: 'No hay biométricos configurados. Configura huella o Face ID en ajustes del dispositivo.',
  );

  factory BiometricResult.lockedOut(Duration? remaining) => BiometricResult._(
    status: BiometricStatus.lockedOut,
    message: 'Demasiados intentos fallidos. Intenta más tarde.',
    lockoutRemaining: remaining,
  );

  factory BiometricResult.error(String message) => BiometricResult._(
    status: BiometricStatus.error,
    message: message,
  );

  bool get isSuccess => status == BiometricStatus.success;
  bool get isLockedOut => status == BiometricStatus.lockedOut;
  bool get requiresSetup => status == BiometricStatus.notEnrolled;
}

/// Estados de autenticación biométrica
enum BiometricStatus {
  success,
  failed,
  notAvailable,
  notEnrolled,
  lockedOut,
  error,
}

/// Extensión para obtener nombres legibles de tipos biométricos
extension BiometricTypeExtension on BiometricType {
  String get displayName {
    switch (this) {
      case BiometricType.face:
        return 'Face ID';
      case BiometricType.fingerprint:
        return 'Huella dactilar';
      case BiometricType.iris:
        return 'Iris';
      case BiometricType.strong:
        return 'Biométrico fuerte';
      case BiometricType.weak:
        return 'Biométrico débil';
    }
  }

  String get icon {
    switch (this) {
      case BiometricType.face:
        return '👤';
      case BiometricType.fingerprint:
        return '👆';
      case BiometricType.iris:
        return '👁️';
      default:
        return '🔐';
    }
  }
}
