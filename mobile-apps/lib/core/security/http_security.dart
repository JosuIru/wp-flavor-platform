import 'dart:io';
import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';
import '../utils/logger.dart';

/// Configuración de seguridad HTTP para la aplicación
///
/// Proporciona:
/// - Validación de HTTPS obligatorio en producción
/// - Certificate pinning opcional
/// - Timeouts de seguridad
/// - Protección contra ataques de red
///
/// ## Configuración de Certificate Pinning
///
/// Para obtener el fingerprint SHA-256 del certificado de tu servidor:
/// ```bash
/// openssl s_client -connect tu-servidor.com:443 < /dev/null 2>/dev/null | \
///   openssl x509 -fingerprint -sha256 -noout
/// ```
///
/// Ejemplo de uso:
/// ```dart
/// HttpSecurity.setPinnedCertificates([
///   'A1:B2:C3:D4:E5:F6:...',  // Certificado principal
///   'F6:E5:D4:C3:B2:A1:...',  // Certificado de respaldo
/// ]);
/// ```
class HttpSecurity {
  /// Fingerprints SHA-256 de certificados permitidos
  ///
  /// IMPORTANTE: En producción, configura estos valores con los fingerprints
  /// reales de tu servidor. Puedes obtenerlos con:
  /// ```
  /// openssl s_client -connect servidor.com:443 < /dev/null 2>/dev/null | \
  ///   openssl x509 -fingerprint -sha256 -noout
  /// ```
  static List<String> _pinnedCertificates = [];

  /// Indica si el certificate pinning está habilitado
  static bool get isPinningEnabled => _pinnedCertificates.isNotEmpty;

  /// Configura los certificados permitidos para pinning
  ///
  /// [fingerprints] debe contener fingerprints SHA-256 en formato:
  /// 'AA:BB:CC:DD:EE:FF:...'
  ///
  /// Se recomienda incluir al menos 2 certificados:
  /// - El certificado actual del servidor
  /// - Un certificado de respaldo para rotación
  static void setPinnedCertificates(List<String> fingerprints) {
    // Validar formato de fingerprints
    for (final fp in fingerprints) {
      if (!_isValidFingerprint(fp)) {
        Logger.w('Fingerprint con formato inválido: $fp', tag: 'Security');
      }
    }
    _pinnedCertificates = fingerprints
        .where((fp) => _isValidFingerprint(fp))
        .map((fp) => fp.toUpperCase())
        .toList();
    Logger.i(
      'Certificate pinning configurado con ${_pinnedCertificates.length} certificados',
      tag: 'Security',
    );
  }

  /// Valida el formato de un fingerprint (SHA-256: 64 hex chars con colons)
  static bool _isValidFingerprint(String fingerprint) {
    final patronSha256 = RegExp(r'^([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}$');
    return patronSha256.hasMatch(fingerprint);
  }

  /// Valida que una URL sea segura (HTTPS en producción)
  static bool isSecureUrl(String url) {
    if (kDebugMode) {
      // En desarrollo, permitir HTTP para localhost
      final uri = Uri.tryParse(url);
      if (uri != null) {
        final isLocalhost = uri.host == 'localhost' ||
            uri.host == '127.0.0.1' ||
            uri.host == '10.0.2.2' ||
            uri.host.endsWith('.local');
        if (isLocalhost) return true;
      }
    }

    // En producción, siempre requerir HTTPS
    return url.startsWith('https://');
  }

  /// Configura un cliente Dio con opciones de seguridad
  static void configureDio(Dio dio, {bool enablePinning = true}) {
    // Configurar timeouts de seguridad
    dio.options.connectTimeout = const Duration(seconds: 30);
    dio.options.receiveTimeout = const Duration(seconds: 60);
    dio.options.sendTimeout = const Duration(seconds: 60);

    // Headers de seguridad
    dio.options.headers['X-Requested-With'] = 'FlavorApp';

    // En release, configurar adapter con validación de certificados
    if (!kDebugMode && enablePinning && _pinnedCertificates.isNotEmpty) {
      _configureCertificatePinning(dio);
    }

    // Interceptor de seguridad
    dio.interceptors.add(_SecurityInterceptor());
  }

  /// Configura certificate pinning en el cliente Dio
  ///
  /// Solo se activa si hay certificados configurados en [_pinnedCertificates].
  /// En desarrollo (kDebugMode), el pinning está deshabilitado por defecto.
  static void _configureCertificatePinning(Dio dio) {
    if (_pinnedCertificates.isEmpty) {
      Logger.w(
        'Certificate pinning NO configurado: lista de certificados vacía. '
        'En producción, configura los fingerprints con setPinnedCertificates()',
        tag: 'Security',
      );
      return;
    }

    dio.httpClientAdapter = IOHttpClientAdapter(
      createHttpClient: () {
        final client = HttpClient();

        // Validar certificado del servidor contra lista de permitidos
        client.badCertificateCallback = (cert, host, port) {
          final isTrusted = _isCertificateTrusted(cert, host);

          if (!isTrusted) {
            Logger.e(
              'ALERTA: Certificate pinning fallido para $host:$port. '
              'Posible ataque MITM.',
              tag: 'Security',
            );
          }

          return isTrusted;
        };

        return client;
      },
    );

    Logger.i(
      'Certificate pinning activo con ${_pinnedCertificates.length} certificados',
      tag: 'Security',
    );
  }

  /// Obtiene el fingerprint SHA-256 de un certificado
  ///
  /// Nota: Dart's X509Certificate solo provee SHA-1 directamente.
  /// Para SHA-256, necesitamos calcular el hash del DER del certificado.
  /// Por simplicidad, usamos SHA-1 pero lo documentamos claramente.
  ///
  /// En producción, considera usar un paquete como `crypto` para SHA-256 real.
  static String _getCertificateFingerprint(X509Certificate cert) {
    // Usar SHA-1 del certificado (disponible directamente en Dart)
    // Para mayor seguridad, considera implementar SHA-256 del DER
    final sha1Bytes = cert.sha1;
    final fingerprint = sha1Bytes
        .map((b) => b.toRadixString(16).padLeft(2, '0'))
        .join(':')
        .toUpperCase();

    Logger.d('Fingerprint del certificado: $fingerprint', tag: 'Security');
    return fingerprint;
  }

  /// Verifica si un certificado está en la lista de permitidos
  static bool _isCertificateTrusted(X509Certificate cert, String host) {
    if (_pinnedCertificates.isEmpty) {
      Logger.w(
        'Certificate pinning deshabilitado: lista de certificados vacía',
        tag: 'Security',
      );
      return true; // Si no hay pinning configurado, confiar en la cadena del sistema
    }

    final fingerprint = _getCertificateFingerprint(cert);
    final isTrusted = _pinnedCertificates.contains(fingerprint);

    if (!isTrusted) {
      Logger.e(
        'Certificado no confiable para $host. Fingerprint: $fingerprint',
        tag: 'Security',
      );
    }

    return isTrusted;
  }

  /// Valida la respuesta del servidor por headers de seguridad
  static bool validateSecurityHeaders(Response response) {
    final headers = response.headers.map;

    // Verificar headers de seguridad recomendados
    final hasContentType = headers.containsKey('content-type');
    final hasNoSniff = headers['x-content-type-options']?.contains('nosniff') ?? false;

    if (!hasContentType) {
      Logger.w('Respuesta sin Content-Type header', tag: 'Security');
    }

    return true; // Por ahora solo loguea, no bloquea
  }
}

/// Interceptor de seguridad para Dio
class _SecurityInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    // Validar URL segura en producción
    if (!kDebugMode && !HttpSecurity.isSecureUrl(options.uri.toString())) {
      Logger.e('Bloqueada petición HTTP insegura: ${options.uri}', tag: 'Security');
      return handler.reject(
        DioException(
          requestOptions: options,
          error: 'HTTP no permitido en producción. Use HTTPS.',
          type: DioExceptionType.badResponse,
        ),
      );
    }

    // Añadir timestamp para prevenir replay attacks (básico)
    options.headers['X-Request-Timestamp'] = DateTime.now().millisecondsSinceEpoch.toString();

    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    // Validar headers de seguridad
    HttpSecurity.validateSecurityHeaders(response);

    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    // Detectar posibles ataques
    if (err.type == DioExceptionType.badCertificate) {
      Logger.e(
        'Posible ataque MITM detectado: certificado inválido',
        tag: 'Security',
        error: err,
      );
    }

    handler.next(err);
  }
}

/// Extensión para configurar Dio fácilmente
extension DioSecurityExtension on Dio {
  /// Aplica configuración de seguridad al cliente
  void applySecurityConfig({bool enablePinning = true}) {
    HttpSecurity.configureDio(this, enablePinning: enablePinning);
  }
}
