import 'dart:convert';
import 'package:flutter/foundation.dart';
import '../api/api_client.dart';
import '../config/server_config.dart';

/// Resultado de validación de QR
class QrValidationResult {
  final bool isValid;
  final String? siteUrl;
  final String? siteName;
  final String? siteDescription;
  final String? apiBase;
  final String? manifestUrl;
  final List<String>? modules;
  final Map<String, dynamic>? branding;
  final Map<String, dynamic>? theme;
  final String? error;
  final bool isV2Format;

  QrValidationResult({
    required this.isValid,
    this.siteUrl,
    this.siteName,
    this.siteDescription,
    this.apiBase,
    this.manifestUrl,
    this.modules,
    this.branding,
    this.theme,
    this.error,
    this.isV2Format = false,
  });

  factory QrValidationResult.error(String message) {
    return QrValidationResult(isValid: false, error: message);
  }

  factory QrValidationResult.fromV2Response(Map<String, dynamic> response) {
    if (response['valid'] != true) {
      return QrValidationResult(
        isValid: false,
        error: response['error'] as String? ?? 'Unknown error',
        isV2Format: true,
      );
    }

    final site = response['site'] as Map<String, dynamic>? ?? {};
    final modulesList = (response['modules'] as List<dynamic>?)
        ?.map((e) => e.toString())
        .toList();

    return QrValidationResult(
      isValid: true,
      siteUrl: site['url'] as String?,
      siteName: site['name'] as String?,
      siteDescription: site['description'] as String?,
      apiBase: site['api_base'] as String?,
      manifestUrl: response['manifest_url'] as String?,
      modules: modulesList,
      branding: response['branding'] as Map<String, dynamic>?,
      theme: response['theme'] as Map<String, dynamic>?,
      isV2Format: true,
    );
  }

  factory QrValidationResult.fromLegacyQr(Map<String, dynamic> data) {
    return QrValidationResult(
      isValid: true,
      siteUrl: data['url'] as String?,
      siteName: data['name'] as String?,
      modules: (data['modules'] as List<dynamic>?)
          ?.map((e) => e.toString())
          .toList(),
      isV2Format: false,
    );
  }
}

/// Servicio para gestión de QR codes de setup
class QrSetupService {
  static const String _v2ApiNamespace = '/wp-json/flavor-app/v2';

  /// Detecta el formato del código QR escaneado
  static QrCodeFormat detectFormat(String qrContent) {
    // Formato 1: URL con payload (nuevo formato v2)
    if (qrContent.contains('qr/validate') && qrContent.contains('payload=')) {
      return QrCodeFormat.v2UrlWithPayload;
    }

    // Formato 2: Payload base64 directo
    try {
      final decoded = base64Decode(qrContent);
      final jsonStr = utf8.decode(decoded);
      final data = json.decode(jsonStr) as Map<String, dynamic>;
      if (data.containsKey('v') && data.containsKey('code') && data.containsKey('url')) {
        return QrCodeFormat.v2PayloadDirect;
      }
    } catch (_) {
      // No es base64 válido, continuar
    }

    // Formato 3: JSON legacy
    try {
      final data = json.decode(qrContent) as Map<String, dynamic>;
      if (data.containsKey('url')) {
        return QrCodeFormat.legacyJson;
      }
    } catch (_) {
      // No es JSON válido
    }

    // Formato 4: URL simple
    if (qrContent.contains('.') || qrContent.startsWith('http')) {
      return QrCodeFormat.simpleUrl;
    }

    return QrCodeFormat.unknown;
  }

  /// Procesa un código QR escaneado
  static Future<QrValidationResult> processQrCode(String qrContent, {String? deviceId}) async {
    final format = detectFormat(qrContent);
    debugPrint('QR Format detected: $format');

    switch (format) {
      case QrCodeFormat.v2UrlWithPayload:
        return _processV2UrlPayload(qrContent, deviceId: deviceId);
      case QrCodeFormat.v2PayloadDirect:
        return _processV2DirectPayload(qrContent, deviceId: deviceId);
      case QrCodeFormat.legacyJson:
        return _processLegacyJson(qrContent);
      case QrCodeFormat.simpleUrl:
        return _processSimpleUrl(qrContent);
      case QrCodeFormat.unknown:
        return QrValidationResult.error('Unrecognized QR code format');
    }
  }

  /// Procesa URL con payload (formato: https://site.com/wp-json/.../qr/validate?action=...&payload=...)
  static Future<QrValidationResult> _processV2UrlPayload(String url, {String? deviceId}) async {
    try {
      final uri = Uri.parse(url);
      final payload = uri.queryParameters['payload'];

      if (payload == null) {
        return QrValidationResult.error('Missing payload in QR URL');
      }

      // Extraer URL base del sitio
      final siteUrl = '${uri.scheme}://${uri.host}';

      return await _validatePayloadWithApi(siteUrl, payload, deviceId: deviceId);
    } catch (e) {
      return QrValidationResult.error('Error processing QR URL: $e');
    }
  }

  /// Procesa payload base64 directo
  static Future<QrValidationResult> _processV2DirectPayload(String payload, {String? deviceId}) async {
    try {
      // Decodificar para obtener la URL del sitio
      final decoded = base64Decode(payload);
      final jsonStr = utf8.decode(decoded);
      final data = json.decode(jsonStr) as Map<String, dynamic>;

      final siteUrl = data['url'] as String?;
      if (siteUrl == null) {
        return QrValidationResult.error('Missing site URL in payload');
      }

      return await _validatePayloadWithApi(siteUrl, payload, deviceId: deviceId);
    } catch (e) {
      return QrValidationResult.error('Error decoding payload: $e');
    }
  }

  /// Valida payload con la API del servidor
  static Future<QrValidationResult> _validatePayloadWithApi(
    String siteUrl, 
    String payload, 
    {String? deviceId}
  ) async {
    try {
      final apiUrl = '$siteUrl$_v2ApiNamespace';
      final apiClient = ApiClient(baseUrl: apiUrl);

      final response = await apiClient.postData('/qr/validate', data: {
        'payload': payload,
        if (deviceId != null) 'device_id': deviceId,
      });

      if (response.success && response.data != null) {
        return QrValidationResult.fromV2Response(response.data as Map<String, dynamic>);
      }

      return QrValidationResult.error(response.error ?? 'Validation failed');
    } catch (e) {
      debugPrint('Error validating QR with API: $e');
      // Fallback: intentar decodificar el payload localmente
      return _decodepayloadLocally(payload);
    }
  }

  /// Decodifica payload localmente (fallback si el servidor no responde)
  static QrValidationResult _decodepayloadLocally(String payload) {
    try {
      final decoded = base64Decode(payload);
      final jsonStr = utf8.decode(decoded);
      final data = json.decode(jsonStr) as Map<String, dynamic>;

      // Verificar expiración
      final exp = data['exp'] as int?;
      if (exp != null && exp < DateTime.now().millisecondsSinceEpoch ~/ 1000) {
        return QrValidationResult.error('QR code has expired');
      }

      return QrValidationResult(
        isValid: true,
        siteUrl: data['url'] as String?,
        siteName: data['name'] as String?,
        apiBase: data['api'] as String?,
        modules: (data['mods'] as List<dynamic>?)?.map((e) => e.toString()).toList(),
        isV2Format: true,
      );
    } catch (e) {
      return QrValidationResult.error('Invalid payload: $e');
    }
  }

  /// Procesa JSON legacy
  static Future<QrValidationResult> _processLegacyJson(String jsonContent) async {
    try {
      final data = json.decode(jsonContent) as Map<String, dynamic>;
      return QrValidationResult.fromLegacyQr(data);
    } catch (e) {
      return QrValidationResult.error('Invalid JSON: $e');
    }
  }

  /// Procesa URL simple
  static Future<QrValidationResult> _processSimpleUrl(String url) async {
    // Normalizar URL
    String normalizedUrl = url;
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      normalizedUrl = 'https://$url';
    }
    if (normalizedUrl.endsWith('/')) {
      normalizedUrl = normalizedUrl.substring(0, normalizedUrl.length - 1);
    }

    return QrValidationResult(
      isValid: true,
      siteUrl: normalizedUrl,
      isV2Format: false,
    );
  }

  /// Genera un ID de dispositivo único si no existe
  static String generateDeviceId() {
    // Usar timestamp + random para generar ID único
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final random = (DateTime.now().microsecond * 1000).toString();
    return 'flutter_${timestamp}_$random';
  }
}

/// Formatos de código QR soportados
enum QrCodeFormat {
  /// URL con payload: https://site.com/wp-json/flavor-app/v2/qr/validate?payload=...
  v2UrlWithPayload,

  /// Payload base64 directo (decodifica a JSON con v, code, url, etc.)
  v2PayloadDirect,

  /// JSON legacy con url, type, token, etc.
  legacyJson,

  /// URL simple del sitio
  simpleUrl,

  /// Formato no reconocido
  unknown,
}
