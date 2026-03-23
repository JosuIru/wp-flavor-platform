import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart' as local_auth;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Tipos de biometría disponibles
enum BiometricType {
  fingerprint,
  faceId,
  iris,
  none,
}

/// Estado de la autenticación biométrica
enum BiometricAuthState {
  notAvailable,
  notEnrolled,
  disabled,
  enabled,
}

/// Resultado de autenticación
class BiometricAuthResult {
  final bool success;
  final String? errorMessage;
  final String? errorCode;

  BiometricAuthResult({
    required this.success,
    this.errorMessage,
    this.errorCode,
  });

  factory BiometricAuthResult.success() => BiometricAuthResult(success: true);

  factory BiometricAuthResult.failed(String message, {String? code}) =>
      BiometricAuthResult(
        success: false,
        errorMessage: message,
        errorCode: code,
      );
}

/// Servicio de autenticación biométrica
class BiometricAuthService {
  static BiometricAuthService? _instance;

  final local_auth.LocalAuthentication _localAuth;
  final FlutterSecureStorage _secureStorage;

  static const String _prefsKeyEnabled = 'biometric_auth_enabled';
  static const String _storageKeyCredentials = 'biometric_credentials';

  bool _isAvailable = false;
  bool _isEnabled = false;
  List<BiometricType> _availableBiometrics = [];

  BiometricAuthService._()
      : _localAuth = local_auth.LocalAuthentication(),
        _secureStorage = const FlutterSecureStorage(
          aOptions: AndroidOptions(
            encryptedSharedPreferences: true,
          ),
          iOptions: IOSOptions(
            accessibility: KeychainAccessibility.first_unlock_this_device,
          ),
        );

  factory BiometricAuthService() {
    _instance ??= BiometricAuthService._();
    return _instance!;
  }

  /// ¿Está disponible la autenticación biométrica?
  bool get isAvailable => _isAvailable;

  /// ¿Está habilitada?
  bool get isEnabled => _isEnabled;

  /// Tipos de biometría disponibles
  List<BiometricType> get availableBiometrics => _availableBiometrics;

  /// Tipo principal de biometría
  BiometricType get primaryBiometric =>
      _availableBiometrics.isNotEmpty ? _availableBiometrics.first : BiometricType.none;

  /// Estado actual
  BiometricAuthState get state {
    if (!_isAvailable) return BiometricAuthState.notAvailable;
    if (_availableBiometrics.isEmpty) return BiometricAuthState.notEnrolled;
    if (!_isEnabled) return BiometricAuthState.disabled;
    return BiometricAuthState.enabled;
  }

  /// Inicializar servicio
  Future<void> initialize() async {
    try {
      // Verificar soporte del dispositivo
      _isAvailable = await _localAuth.canCheckBiometrics ||
          await _localAuth.isDeviceSupported();

      if (_isAvailable) {
        // Obtener tipos disponibles
        final biometrics = await _localAuth.getAvailableBiometrics();
        _availableBiometrics = biometrics.map(_mapLocalAuthBiometricType).toList();
      }

      // Cargar preferencia de usuario
      final prefs = await SharedPreferences.getInstance();
      _isEnabled = prefs.getBool(_prefsKeyEnabled) ?? false;

      debugPrint('[BiometricAuth] Available: $_isAvailable');
      debugPrint('[BiometricAuth] Types: $_availableBiometrics');
      debugPrint('[BiometricAuth] Enabled: $_isEnabled');
    } catch (e) {
      debugPrint('[BiometricAuth] Error inicializando: $e');
      _isAvailable = false;
    }
  }

  BiometricType _mapLocalAuthBiometricType(local_auth.BiometricType type) {
    // El paquete local_auth usa su propio BiometricType
    // Mapeamos a nuestro enum local
    switch (type) {
      case local_auth.BiometricType.fingerprint:
        return BiometricType.fingerprint;
      case local_auth.BiometricType.face:
        return BiometricType.faceId;
      case local_auth.BiometricType.iris:
        return BiometricType.iris;
      default:
        return BiometricType.fingerprint;
    }
  }

  /// Habilitar autenticación biométrica
  Future<bool> enable(String email, String password) async {
    if (!_isAvailable || _availableBiometrics.isEmpty) {
      return false;
    }

    try {
      // Primero autenticar para confirmar identidad
      final authResult = await authenticate(
        reason: 'Confirma tu identidad para habilitar el acceso biométrico',
      );

      if (!authResult.success) {
        return false;
      }

      // Guardar credenciales de forma segura
      final credentials = jsonEncode({
        'email': email,
        'password': password,
        'timestamp': DateTime.now().toIso8601String(),
      });

      await _secureStorage.write(
        key: _storageKeyCredentials,
        value: credentials,
      );

      // Guardar preferencia
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_prefsKeyEnabled, true);
      _isEnabled = true;

      debugPrint('[BiometricAuth] Habilitado correctamente');
      return true;
    } catch (e) {
      debugPrint('[BiometricAuth] Error habilitando: $e');
      return false;
    }
  }

  /// Deshabilitar autenticación biométrica
  Future<bool> disable() async {
    try {
      // Eliminar credenciales
      await _secureStorage.delete(key: _storageKeyCredentials);

      // Actualizar preferencia
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_prefsKeyEnabled, false);
      _isEnabled = false;

      debugPrint('[BiometricAuth] Deshabilitado');
      return true;
    } catch (e) {
      debugPrint('[BiometricAuth] Error deshabilitando: $e');
      return false;
    }
  }

  /// Autenticar con biometría
  Future<BiometricAuthResult> authenticate({
    String reason = 'Autentícate para continuar',
    bool useErrorDialogs = true,
    bool stickyAuth = true,
  }) async {
    if (!_isAvailable) {
      return BiometricAuthResult.failed(
        'La autenticación biométrica no está disponible en este dispositivo',
        code: 'not_available',
      );
    }

    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: reason,
        options: local_auth.AuthenticationOptions(
          useErrorDialogs: useErrorDialogs,
          stickyAuth: stickyAuth,
          biometricOnly: false, // Permitir PIN/patrón como fallback
        ),
      );

      if (authenticated) {
        return BiometricAuthResult.success();
      } else {
        return BiometricAuthResult.failed(
          'Autenticación cancelada',
          code: 'cancelled',
        );
      }
    } on PlatformException catch (e) {
      debugPrint('[BiometricAuth] Error plataforma: ${e.code} - ${e.message}');

      String message;
      switch (e.code) {
        case 'NotEnrolled':
          message = 'No hay datos biométricos registrados en el dispositivo';
          break;
        case 'LockedOut':
          message = 'Demasiados intentos fallidos. Intenta más tarde';
          break;
        case 'PermanentlyLockedOut':
          message = 'La autenticación biométrica está bloqueada. Usa tu PIN';
          break;
        case 'PasscodeNotSet':
          message = 'Configura un PIN o contraseña en tu dispositivo primero';
          break;
        default:
          message = e.message ?? 'Error de autenticación';
      }

      return BiometricAuthResult.failed(message, code: e.code);
    } catch (e) {
      debugPrint('[BiometricAuth] Error: $e');
      return BiometricAuthResult.failed(
        'Error inesperado durante la autenticación',
        code: 'unknown',
      );
    }
  }

  /// Autenticar y obtener credenciales guardadas
  Future<Map<String, String>?> authenticateAndGetCredentials() async {
    if (!_isEnabled) {
      debugPrint('[BiometricAuth] No habilitado');
      return null;
    }

    final authResult = await authenticate(
      reason: 'Autentícate para acceder',
    );

    if (!authResult.success) {
      return null;
    }

    try {
      final credentialsJson = await _secureStorage.read(
        key: _storageKeyCredentials,
      );

      if (credentialsJson == null) {
        return null;
      }

      final credentials = jsonDecode(credentialsJson) as Map<String, dynamic>;
      return {
        'email': credentials['email'] as String,
        'password': credentials['password'] as String,
      };
    } catch (e) {
      debugPrint('[BiometricAuth] Error leyendo credenciales: $e');
      return null;
    }
  }

  /// Verificar si hay credenciales guardadas
  Future<bool> hasStoredCredentials() async {
    try {
      final credentials = await _secureStorage.read(key: _storageKeyCredentials);
      return credentials != null;
    } catch (e) {
      return false;
    }
  }

  /// Obtener texto descriptivo del tipo de biometría
  String getBiometricLabel() {
    switch (primaryBiometric) {
      case BiometricType.fingerprint:
        return 'Huella dactilar';
      case BiometricType.faceId:
        return 'Face ID';
      case BiometricType.iris:
        return 'Reconocimiento de iris';
      case BiometricType.none:
        return 'Biometría';
    }
  }

  /// Obtener icono según tipo de biometría
  String getBiometricIcon() {
    switch (primaryBiometric) {
      case BiometricType.fingerprint:
        return 'fingerprint';
      case BiometricType.faceId:
        return 'face';
      case BiometricType.iris:
        return 'visibility';
      case BiometricType.none:
        return 'lock';
    }
  }
}
