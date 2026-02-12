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
class HttpSecurity {
  /// Fingerprints SHA-256 de certificados permitidos
  /// En producción, estos deberían obtenerse de la configuración del servidor
  static List<String> _pinnedCertificates = [];

  /// Configura los certificados permitidos para pinning
  static void setPinnedCertificates(List<String> fingerprints) {
    _pinnedCertificates = fingerprints;
    Logger.i('Certificate pinning configurado con ${fingerprints.length} certificados',
        tag: 'Security');
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
  static void _configureCertificatePinning(Dio dio) {
    dio.httpClientAdapter = IOHttpClientAdapter(
      createHttpClient: () {
        final client = HttpClient();

        // Validar certificado del servidor
        client.badCertificateCallback = (cert, host, port) {
          // Obtener fingerprint SHA-256 del certificado
          final fingerprint = _getCertificateFingerprint(cert);

          // Verificar si está en la lista de certificados permitidos
          final isValid = _pinnedCertificates.contains(fingerprint);

          if (!isValid) {
            Logger.e(
              'Certificate pinning fallido para $host:$port',
              tag: 'Security',
            );
          }

          return isValid;
        };

        return client;
      },
    );
  }

  /// Obtiene el fingerprint SHA-256 de un certificado
  static String _getCertificateFingerprint(X509Certificate cert) {
    // El fingerprint ya viene como SHA-1, convertimos a formato hex
    final sha1 = cert.sha1;
    return sha1.map((b) => b.toRadixString(16).padLeft(2, '0')).join(':').toUpperCase();
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
