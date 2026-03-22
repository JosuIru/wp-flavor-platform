import 'package:flutter_test/flutter_test.dart';
import 'dart:convert';

/// Tests para QrSetupService
/// 
/// Valida detección de formatos QR y procesamiento de códigos.

// QR Code format enum
enum QrCodeFormat {
  v2UrlWithPayload,
  v2DirectPayload,
  legacyJson,
  simpleUrl,
  unknown,
}

// QR validation result
class QrValidationResult {
  final bool isValid;
  final String? siteUrl;
  final String? siteName;
  final Map<String, dynamic>? config;
  final String? errorMessage;

  QrValidationResult({
    required this.isValid,
    this.siteUrl,
    this.siteName,
    this.config,
    this.errorMessage,
  });
}

// Simplified QrSetupService for testing
class QrSetupServiceTestable {
  static const String qrMagicPrefix = 'FLAVOR-APP:';

  /// Detecta el formato del código QR
  static QrCodeFormat detectFormat(String qrContent) {
    // Formato v2: URL con payload base64 en parámetro
    if (qrContent.contains('flavor-app://setup?data=') ||
        qrContent.contains('?app_setup=')) {
      return QrCodeFormat.v2UrlWithPayload;
    }

    // Formato v2: Payload directo con prefijo mágico
    if (qrContent.startsWith(qrMagicPrefix)) {
      return QrCodeFormat.v2DirectPayload;
    }

    // Formato legacy: JSON directo
    if (qrContent.startsWith('{') && qrContent.endsWith('}')) {
      try {
        final decoded = json.decode(qrContent) as Map<String, dynamic>;
        if (decoded.containsKey('site_url') || decoded.containsKey('api_url')) {
          return QrCodeFormat.legacyJson;
        }
      } catch (_) {}
    }

    // URL simple (sin parámetros de setup)
    if (qrContent.startsWith('http://') || qrContent.startsWith('https://')) {
      return QrCodeFormat.simpleUrl;
    }

    return QrCodeFormat.unknown;
  }

  /// Extrae payload base64 de URL con parámetro
  static String? extractPayloadFromUrl(String url) {
    // flavor-app://setup?data=BASE64
    if (url.contains('flavor-app://setup?data=')) {
      final dataStart = url.indexOf('data=') + 5;
      final dataEnd = url.contains('&') 
          ? url.indexOf('&', dataStart) 
          : url.length;
      return url.substring(dataStart, dataEnd);
    }

    // https://site.com?app_setup=BASE64
    if (url.contains('app_setup=')) {
      final dataStart = url.indexOf('app_setup=') + 10;
      final dataEnd = url.contains('&', dataStart) 
          ? url.indexOf('&', dataStart) 
          : url.length;
      return url.substring(dataStart, dataEnd);
    }

    return null;
  }

  /// Decodifica payload base64 a JSON
  static Map<String, dynamic>? decodePayload(String payload) {
    try {
      // Remover prefijo mágico si existe
      var cleanPayload = payload;
      if (payload.startsWith(qrMagicPrefix)) {
        cleanPayload = payload.substring(qrMagicPrefix.length);
      }

      // URL-decode y base64-decode
      final decoded = utf8.decode(base64Decode(cleanPayload));
      return json.decode(decoded) as Map<String, dynamic>;
    } catch (e) {
      return null;
    }
  }

  /// Procesa código QR y extrae configuración
  static QrValidationResult processQrCode(String qrContent) {
    final format = detectFormat(qrContent);

    switch (format) {
      case QrCodeFormat.v2UrlWithPayload:
        final payload = extractPayloadFromUrl(qrContent);
        if (payload == null) {
          return QrValidationResult(
            isValid: false,
            errorMessage: 'No se encontró payload en URL',
          );
        }
        final data = decodePayload(payload);
        if (data == null) {
          return QrValidationResult(
            isValid: false,
            errorMessage: 'Error decodificando payload',
          );
        }
        return QrValidationResult(
          isValid: true,
          siteUrl: data['site_url'] as String?,
          siteName: data['site_name'] as String?,
          config: data,
        );

      case QrCodeFormat.v2DirectPayload:
        final data = decodePayload(qrContent);
        if (data == null) {
          return QrValidationResult(
            isValid: false,
            errorMessage: 'Error decodificando payload directo',
          );
        }
        return QrValidationResult(
          isValid: true,
          siteUrl: data['site_url'] as String?,
          siteName: data['site_name'] as String?,
          config: data,
        );

      case QrCodeFormat.legacyJson:
        try {
          final data = json.decode(qrContent) as Map<String, dynamic>;
          return QrValidationResult(
            isValid: true,
            siteUrl: data['site_url'] as String? ?? data['api_url'] as String?,
            siteName: data['site_name'] as String?,
            config: data,
          );
        } catch (e) {
          return QrValidationResult(
            isValid: false,
            errorMessage: 'Error parseando JSON legacy',
          );
        }

      case QrCodeFormat.simpleUrl:
        return QrValidationResult(
          isValid: true,
          siteUrl: qrContent,
          config: {'site_url': qrContent},
        );

      case QrCodeFormat.unknown:
        return QrValidationResult(
          isValid: false,
          errorMessage: 'Formato de QR no reconocido',
        );
    }
  }

  /// Genera payload base64 desde configuración
  static String generatePayload(Map<String, dynamic> config) {
    final jsonString = json.encode(config);
    final base64String = base64Encode(utf8.encode(jsonString));
    return qrMagicPrefix + base64String;
  }
}

void main() {
  group('QrSetupService', () {
    group('detectFormat', () {
      test('should detect v2 URL with payload (custom scheme)', () {
        final qr = 'flavor-app://setup?data=eyJzaXRlX3VybCI6Imh0dHBzOi8vdGVzdC5jb20ifQ==';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.v2UrlWithPayload),
        );
      });

      test('should detect v2 URL with payload (https)', () {
        final qr = 'https://mysite.com?app_setup=eyJzaXRlX3VybCI6Imh0dHBzOi8vdGVzdC5jb20ifQ==';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.v2UrlWithPayload),
        );
      });

      test('should detect v2 direct payload', () {
        final qr = 'FLAVOR-APP:eyJzaXRlX3VybCI6Imh0dHBzOi8vdGVzdC5jb20ifQ==';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.v2DirectPayload),
        );
      });

      test('should detect legacy JSON', () {
        final qr = '{"site_url": "https://test.com", "api_key": "123"}';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.legacyJson),
        );
      });

      test('should detect simple URL', () {
        final qr = 'https://mysite.com';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.simpleUrl),
        );
      });

      test('should return unknown for unrecognized format', () {
        final qr = 'random text that is not a valid QR';
        
        expect(
          QrSetupServiceTestable.detectFormat(qr),
          equals(QrCodeFormat.unknown),
        );
      });
    });

    group('extractPayloadFromUrl', () {
      test('should extract from custom scheme URL', () {
        final url = 'flavor-app://setup?data=abc123payload';
        
        expect(
          QrSetupServiceTestable.extractPayloadFromUrl(url),
          equals('abc123payload'),
        );
      });

      test('should extract from https URL', () {
        final url = 'https://site.com?app_setup=xyz789payload';
        
        expect(
          QrSetupServiceTestable.extractPayloadFromUrl(url),
          equals('xyz789payload'),
        );
      });

      test('should handle URL with additional parameters', () {
        final url = 'flavor-app://setup?data=payload123&other=param';
        
        expect(
          QrSetupServiceTestable.extractPayloadFromUrl(url),
          equals('payload123'),
        );
      });

      test('should return null for URL without payload', () {
        final url = 'https://site.com/page';
        
        expect(
          QrSetupServiceTestable.extractPayloadFromUrl(url),
          isNull,
        );
      });
    });

    group('decodePayload', () {
      test('should decode valid base64 payload', () {
        // Base64 of {"site_url":"https://test.com"}
        final payload = 'eyJzaXRlX3VybCI6Imh0dHBzOi8vdGVzdC5jb20ifQ==';
        
        final result = QrSetupServiceTestable.decodePayload(payload);
        
        expect(result, isNotNull);
        expect(result!['site_url'], equals('https://test.com'));
      });

      test('should handle magic prefix', () {
        final payload = 'FLAVOR-APP:eyJzaXRlX3VybCI6Imh0dHBzOi8vdGVzdC5jb20ifQ==';
        
        final result = QrSetupServiceTestable.decodePayload(payload);
        
        expect(result, isNotNull);
        expect(result!['site_url'], equals('https://test.com'));
      });

      test('should return null for invalid base64', () {
        final payload = 'not-valid-base64!!!';
        
        expect(QrSetupServiceTestable.decodePayload(payload), isNull);
      });

      test('should return null for invalid JSON', () {
        // Base64 of "not json"
        final payload = base64Encode(utf8.encode('not json'));
        
        expect(QrSetupServiceTestable.decodePayload(payload), isNull);
      });
    });

    group('processQrCode', () {
      test('should process v2 URL with payload successfully', () {
        // Create valid QR content
        final config = {'site_url': 'https://test.com', 'site_name': 'Test Site'};
        final payload = base64Encode(utf8.encode(json.encode(config)));
        final qr = 'flavor-app://setup?data=$payload';

        final result = QrSetupServiceTestable.processQrCode(qr);

        expect(result.isValid, isTrue);
        expect(result.siteUrl, equals('https://test.com'));
        expect(result.siteName, equals('Test Site'));
        expect(result.config, isNotNull);
      });

      test('should process v2 direct payload successfully', () {
        final config = {'site_url': 'https://test.com', 'api_key': 'secret'};
        final payload = 'FLAVOR-APP:${base64Encode(utf8.encode(json.encode(config)))}';

        final result = QrSetupServiceTestable.processQrCode(payload);

        expect(result.isValid, isTrue);
        expect(result.siteUrl, equals('https://test.com'));
      });

      test('should process legacy JSON successfully', () {
        final qr = '{"site_url": "https://legacy.com", "site_name": "Legacy"}';

        final result = QrSetupServiceTestable.processQrCode(qr);

        expect(result.isValid, isTrue);
        expect(result.siteUrl, equals('https://legacy.com'));
        expect(result.siteName, equals('Legacy'));
      });

      test('should process simple URL successfully', () {
        final qr = 'https://simple-site.com';

        final result = QrSetupServiceTestable.processQrCode(qr);

        expect(result.isValid, isTrue);
        expect(result.siteUrl, equals('https://simple-site.com'));
      });

      test('should return error for unknown format', () {
        final qr = 'random garbage';

        final result = QrSetupServiceTestable.processQrCode(qr);

        expect(result.isValid, isFalse);
        expect(result.errorMessage, isNotNull);
      });

      test('should return error for URL without payload', () {
        final qr = 'flavor-app://setup';

        final result = QrSetupServiceTestable.processQrCode(qr);

        expect(result.isValid, isFalse);
        expect(result.errorMessage, contains('payload'));
      });
    });

    group('generatePayload', () {
      test('should generate valid payload with magic prefix', () {
        final config = {'site_url': 'https://test.com', 'key': 'value'};

        final payload = QrSetupServiceTestable.generatePayload(config);

        expect(payload, startsWith('FLAVOR-APP:'));
      });

      test('should be decodable', () {
        final config = {
          'site_url': 'https://test.com',
          'site_name': 'Test',
          'modules': ['eventos', 'socios'],
        };

        final payload = QrSetupServiceTestable.generatePayload(config);
        final decoded = QrSetupServiceTestable.decodePayload(payload);

        expect(decoded, equals(config));
      });
    });
  });
}
