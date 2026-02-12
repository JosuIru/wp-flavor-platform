import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/logger.dart';

/// Gestión segura de tokens JWT con rotación automática
///
/// Características:
/// - Almacenamiento seguro con flutter_secure_storage
/// - Detección de expiración de tokens
/// - Rotación automática antes de expirar
/// - Refresh token para renovación sin login
class TokenManager {
  static const String _accessTokenKey = 'auth_token';
  static const String _refreshTokenKey = 'refresh_token';
  static const String _tokenExpiryKey = 'token_expiry';

  /// Tiempo antes de expiración para renovar (5 minutos)
  static const int _refreshBeforeExpirySeconds = 300;

  final FlutterSecureStorage _storage;

  /// Callback para renovar el token (debe implementarse en ApiClient)
  final Future<TokenPair?> Function(String refreshToken)? onRefreshToken;

  /// Callback cuando el token es inválido y requiere re-login
  final void Function()? onTokenInvalid;

  TokenManager({
    FlutterSecureStorage? storage,
    this.onRefreshToken,
    this.onTokenInvalid,
  }) : _storage = storage ?? const FlutterSecureStorage();

  /// Guarda un par de tokens (access + refresh)
  Future<void> saveTokens({
    required String accessToken,
    String? refreshToken,
    int? expiresIn,
  }) async {
    await _storage.write(key: _accessTokenKey, value: accessToken);

    if (refreshToken != null) {
      await _storage.write(key: _refreshTokenKey, value: refreshToken);
    }

    // Calcular y guardar tiempo de expiración
    if (expiresIn != null) {
      final expiryTime = DateTime.now()
          .add(Duration(seconds: expiresIn))
          .millisecondsSinceEpoch;
      await _storage.write(key: _tokenExpiryKey, value: expiryTime.toString());
    } else {
      // Intentar extraer expiración del JWT
      final expiry = _extractExpiryFromJwt(accessToken);
      if (expiry != null) {
        await _storage.write(
            key: _tokenExpiryKey, value: expiry.millisecondsSinceEpoch.toString());
      }
    }

    Logger.i('Tokens guardados exitosamente', tag: 'TokenManager');
  }

  /// Obtiene el access token válido, renovando si es necesario
  Future<String?> getValidAccessToken() async {
    final accessToken = await _storage.read(key: _accessTokenKey);

    if (accessToken == null) {
      return null;
    }

    // Verificar si necesita renovación
    if (await _shouldRefreshToken()) {
      Logger.i('Token próximo a expirar, intentando renovar...', tag: 'TokenManager');

      final refreshToken = await _storage.read(key: _refreshTokenKey);
      if (refreshToken != null && onRefreshToken != null) {
        try {
          final newTokens = await onRefreshToken!(refreshToken);
          if (newTokens != null) {
            await saveTokens(
              accessToken: newTokens.accessToken,
              refreshToken: newTokens.refreshToken,
              expiresIn: newTokens.expiresIn,
            );
            return newTokens.accessToken;
          }
        } catch (e) {
          Logger.e('Error al renovar token', tag: 'TokenManager', error: e);
        }
      }

      // Si no se pudo renovar, verificar si el token actual aún es válido
      if (await isTokenExpired()) {
        Logger.w('Token expirado y no se pudo renovar', tag: 'TokenManager');
        onTokenInvalid?.call();
        return null;
      }
    }

    return accessToken;
  }

  /// Verifica si el token ha expirado
  Future<bool> isTokenExpired() async {
    final expiryStr = await _storage.read(key: _tokenExpiryKey);
    if (expiryStr == null) {
      // Si no hay info de expiración, asumir que es válido
      return false;
    }

    final expiry = int.tryParse(expiryStr);
    if (expiry == null) return false;

    return DateTime.now().millisecondsSinceEpoch > expiry;
  }

  /// Verifica si el token debería renovarse (próximo a expirar)
  Future<bool> _shouldRefreshToken() async {
    final expiryStr = await _storage.read(key: _tokenExpiryKey);
    if (expiryStr == null) return false;

    final expiry = int.tryParse(expiryStr);
    if (expiry == null) return false;

    final now = DateTime.now().millisecondsSinceEpoch;
    final refreshThreshold = expiry - (_refreshBeforeExpirySeconds * 1000);

    return now > refreshThreshold;
  }

  /// Extrae la fecha de expiración de un token JWT
  DateTime? _extractExpiryFromJwt(String token) {
    try {
      final parts = token.split('.');
      if (parts.length != 3) return null;

      // Decodificar payload (parte central del JWT)
      String payload = parts[1];
      // Añadir padding si es necesario
      switch (payload.length % 4) {
        case 2:
          payload += '==';
          break;
        case 3:
          payload += '=';
          break;
      }

      final decoded = utf8.decode(base64Decode(payload));
      final json = jsonDecode(decoded) as Map<String, dynamic>;

      if (json.containsKey('exp')) {
        final exp = json['exp'] as int;
        return DateTime.fromMillisecondsSinceEpoch(exp * 1000);
      }
    } catch (e) {
      Logger.w('No se pudo extraer expiración del JWT', tag: 'TokenManager');
    }
    return null;
  }

  /// Obtiene el tiempo restante del token en segundos
  Future<int?> getTokenRemainingTime() async {
    final expiryStr = await _storage.read(key: _tokenExpiryKey);
    if (expiryStr == null) return null;

    final expiry = int.tryParse(expiryStr);
    if (expiry == null) return null;

    final remaining = expiry - DateTime.now().millisecondsSinceEpoch;
    return remaining > 0 ? remaining ~/ 1000 : 0;
  }

  /// Limpia todos los tokens almacenados
  Future<void> clearTokens() async {
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _refreshTokenKey);
    await _storage.delete(key: _tokenExpiryKey);
    Logger.i('Tokens eliminados', tag: 'TokenManager');
  }

  /// Verifica si hay un token guardado
  Future<bool> hasToken() async {
    final token = await _storage.read(key: _accessTokenKey);
    return token != null && token.isNotEmpty;
  }

  /// Obtiene información del usuario desde el JWT (si está disponible)
  Future<Map<String, dynamic>?> getTokenClaims() async {
    final token = await _storage.read(key: _accessTokenKey);
    if (token == null) return null;

    try {
      final parts = token.split('.');
      if (parts.length != 3) return null;

      String payload = parts[1];
      switch (payload.length % 4) {
        case 2:
          payload += '==';
          break;
        case 3:
          payload += '=';
          break;
      }

      final decoded = utf8.decode(base64Decode(payload));
      return jsonDecode(decoded) as Map<String, dynamic>;
    } catch (e) {
      return null;
    }
  }
}

/// Par de tokens (access + refresh)
class TokenPair {
  final String accessToken;
  final String? refreshToken;
  final int? expiresIn;

  TokenPair({
    required this.accessToken,
    this.refreshToken,
    this.expiresIn,
  });

  factory TokenPair.fromJson(Map<String, dynamic> json) {
    return TokenPair(
      accessToken: json['token'] ?? json['access_token'] ?? '',
      refreshToken: json['refresh_token'],
      expiresIn: json['expires_in'],
    );
  }
}
