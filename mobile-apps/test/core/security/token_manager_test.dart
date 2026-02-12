import 'package:flutter_test/flutter_test.dart';
import 'package:chat_ia_apps/core/security/token_manager.dart';

void main() {
  group('TokenManager', () {
    group('JWT Parsing', () {
      test('should extract claims from valid JWT', () {
        // JWT de prueba con payload: {"sub":"123","exp":9999999999,"iat":1234567890}
        const testJwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.'
            'eyJzdWIiOiIxMjMiLCJleHAiOjk5OTk5OTk5OTksImlhdCI6MTIzNDU2Nzg5MH0.'
            'signature';

        final claims = TokenManager.extractClaims(testJwt);

        expect(claims, isNotNull);
        expect(claims?['sub'], equals('123'));
      });

      test('should return null for invalid JWT', () {
        expect(TokenManager.extractClaims('invalid'), isNull);
        expect(TokenManager.extractClaims(''), isNull);
        expect(TokenManager.extractClaims('only.two'), isNull);
      });

      test('should return null for malformed payload', () {
        const malformedJwt = 'header.!!!notbase64!!!.signature';
        expect(TokenManager.extractClaims(malformedJwt), isNull);
      });
    });

    group('Token Expiration', () {
      test('should detect expired token', () {
        // Token expirado (exp en el pasado)
        const expiredJwt = 'eyJhbGciOiJIUzI1NiJ9.'
            'eyJleHAiOjF9.' // exp: 1 (1970)
            'sig';

        expect(TokenManager.isTokenExpired(expiredJwt), isTrue);
      });

      test('should detect valid token', () {
        // Token válido (exp en el futuro lejano)
        const validJwt = 'eyJhbGciOiJIUzI1NiJ9.'
            'eyJleHAiOjk5OTk5OTk5OTl9.' // exp: 9999999999
            'sig';

        expect(TokenManager.isTokenExpired(validJwt), isFalse);
      });

      test('should treat invalid token as expired', () {
        expect(TokenManager.isTokenExpired('invalid'), isTrue);
        expect(TokenManager.isTokenExpired(''), isTrue);
      });
    });

    group('Token Refresh Threshold', () {
      test('should indicate refresh needed when close to expiration', () {
        // Token que expira en 2 minutos (threshold es 5 min)
        final expSoon = DateTime.now().millisecondsSinceEpoch ~/ 1000 + 120;
        final payload = '{"exp":$expSoon}';
        final encoded = _base64UrlEncode(payload);
        final jwt = 'header.$encoded.sig';

        expect(TokenManager.needsRefresh(jwt), isTrue);
      });

      test('should not indicate refresh for fresh token', () {
        // Token que expira en 1 hora
        final expLater = DateTime.now().millisecondsSinceEpoch ~/ 1000 + 3600;
        final payload = '{"exp":$expLater}';
        final encoded = _base64UrlEncode(payload);
        final jwt = 'header.$encoded.sig';

        expect(TokenManager.needsRefresh(jwt), isFalse);
      });
    });

    group('Configuration', () {
      test('refreshThreshold should be 5 minutes', () {
        expect(
          TokenManager.refreshThreshold,
          equals(const Duration(minutes: 5)),
        );
      });
    });
  });
}

/// Helper para codificar en base64url
String _base64UrlEncode(String input) {
  final bytes = input.codeUnits;
  var encoded = '';
  for (var i = 0; i < bytes.length; i += 3) {
    final chunk = bytes.sublist(i, i + 3 > bytes.length ? bytes.length : i + 3);
    final b1 = chunk[0];
    final b2 = chunk.length > 1 ? chunk[1] : 0;
    final b3 = chunk.length > 2 ? chunk[2] : 0;

    final c1 = b1 >> 2;
    final c2 = ((b1 & 3) << 4) | (b2 >> 4);
    final c3 = ((b2 & 15) << 2) | (b3 >> 6);
    final c4 = b3 & 63;

    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    encoded += chars[c1];
    encoded += chars[c2];
    if (chunk.length > 1) encoded += chars[c3];
    if (chunk.length > 2) encoded += chars[c4];
  }
  return encoded;
}
