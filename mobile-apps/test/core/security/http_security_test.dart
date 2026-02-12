import 'package:flutter_test/flutter_test.dart';
import 'package:chat_ia_apps/core/security/http_security.dart';

void main() {
  group('HttpSecurity', () {
    late HttpSecurity httpSecurity;

    setUp(() {
      httpSecurity = HttpSecurity();
    });

    group('URL Validation', () {
      test('should accept valid HTTPS URLs', () {
        expect(httpSecurity.isSecureUrl('https://api.example.com'), isTrue);
        expect(httpSecurity.isSecureUrl('https://example.com/path'), isTrue);
        expect(httpSecurity.isSecureUrl('https://sub.domain.com:8443'), isTrue);
      });

      test('should reject HTTP URLs in production mode', () {
        // En modo producción, HTTP debe ser rechazado
        expect(httpSecurity.isSecureUrl('http://api.example.com'), isFalse);
        expect(httpSecurity.isSecureUrl('http://example.com'), isFalse);
      });

      test('should handle malformed URLs gracefully', () {
        expect(httpSecurity.isSecureUrl(''), isFalse);
        expect(httpSecurity.isSecureUrl('not-a-url'), isFalse);
        expect(httpSecurity.isSecureUrl('ftp://example.com'), isFalse);
      });

      test('should allow localhost in debug mode', () {
        // localhost es permitido en debug para desarrollo
        final isDebugAllowed = httpSecurity.isDebugUrl('http://localhost:8080');
        expect(isDebugAllowed, isTrue);
        expect(httpSecurity.isDebugUrl('http://127.0.0.1:3000'), isTrue);
        expect(httpSecurity.isDebugUrl('http://10.0.2.2:8080'), isTrue);
      });
    });

    group('Security Headers', () {
      test('should generate required security headers', () {
        final headers = httpSecurity.getSecurityHeaders();

        expect(headers, isA<Map<String, String>>());
        expect(headers.containsKey('X-Content-Type-Options'), isTrue);
        expect(headers['X-Content-Type-Options'], equals('nosniff'));
      });
    });

    group('Certificate Pinning', () {
      test('should validate certificate fingerprint format', () {
        // SHA-256 fingerprint válido (64 caracteres hex)
        const validFingerprint = 'sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
        expect(httpSecurity.isValidFingerprint(validFingerprint), isTrue);
      });

      test('should reject invalid fingerprint format', () {
        expect(httpSecurity.isValidFingerprint('invalid'), isFalse);
        expect(httpSecurity.isValidFingerprint(''), isFalse);
        expect(httpSecurity.isValidFingerprint('sha256/'), isFalse);
      });
    });
  });

  group('SecurityHeaders', () {
    test('should contain all required headers', () {
      final headers = SecurityHeaders.all;

      expect(headers, contains('X-Content-Type-Options'));
      expect(headers, contains('X-Frame-Options'));
      expect(headers, contains('X-XSS-Protection'));
    });
  });
}
