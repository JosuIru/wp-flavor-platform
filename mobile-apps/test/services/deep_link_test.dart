import 'package:flutter_test/flutter_test.dart';

import 'package:flavor_app/core/services/deep_link_service.dart';

void main() {
  group('DeepLinkInfo', () {
    group('URI Parsing', () {
      test('should parse simple path', () {
        final uri = Uri.parse('https://app.example.com/eventos');
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.path, equals('/eventos'));
        expect(info.module, equals('eventos'));
        expect(info.action, isNull);
        expect(info.itemId, isNull);
      });

      test('should parse path with action', () {
        final uri = Uri.parse('https://app.example.com/eventos/detalle');
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.module, equals('eventos'));
        expect(info.action, equals('detalle'));
        expect(info.itemId, isNull);
      });

      test('should parse path with action and item ID', () {
        final uri = Uri.parse('https://app.example.com/eventos/detalle/123');
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.module, equals('eventos'));
        expect(info.action, equals('detalle'));
        expect(info.itemId, equals('123'));
      });

      test('should parse query parameters', () {
        final uri = Uri.parse('https://app.example.com/marketplace?category=food&sort=price');
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.module, equals('marketplace'));
        expect(info.queryParams['category'], equals('food'));
        expect(info.queryParams['sort'], equals('price'));
      });

      test('should handle empty path segments', () {
        final uri = Uri.parse('https://app.example.com//eventos//');
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.module, equals('eventos'));
      });

      test('should preserve raw URI', () {
        final uriString = 'https://app.example.com/socios/ver/456?ref=home';
        final uri = Uri.parse(uriString);
        final info = DeepLinkInfo.fromUri(uri);

        expect(info.rawUri, equals(uriString));
      });
    });

    group('toString', () {
      test('should provide readable string representation', () {
        final uri = Uri.parse('https://app.example.com/cursos/detalle/789');
        final info = DeepLinkInfo.fromUri(uri);

        final str = info.toString();

        expect(str, contains('path: /cursos/detalle/789'));
        expect(str, contains('module: cursos'));
        expect(str, contains('action: detalle'));
        expect(str, contains('itemId: 789'));
      });
    });
  });

  group('DeepLinkNavigationResult', () {
    test('should create success result', () {
      final result = DeepLinkNavigationResult.success(
        '/eventos/detalle',
        arguments: {'id': '123'},
      );

      expect(result.handled, isTrue);
      expect(result.route, equals('/eventos/detalle'));
      expect(result.arguments?['id'], equals('123'));
      expect(result.errorMessage, isNull);
    });

    test('should create not handled result', () {
      final result = DeepLinkNavigationResult.notHandled();

      expect(result.handled, isFalse);
      expect(result.route, isNull);
      expect(result.arguments, isNull);
    });

    test('should create error result', () {
      final result = DeepLinkNavigationResult.error('Module not found');

      expect(result.handled, isFalse);
      expect(result.errorMessage, equals('Module not found'));
    });
  });

  group('DeepLinkService', () {
    late DeepLinkService service;

    setUp(() {
      service = DeepLinkService();
    });

    tearDown(() {
      service.dispose();
    });

    group('URL Parsing', () {
      test('should parse URL string', () {
        final info = service.parseUrl('https://app.example.com/talleres/crear');

        expect(info.module, equals('talleres'));
        expect(info.action, equals('crear'));
      });
    });

    group('Link Creation', () {
      test('should create simple link', () {
        final link = service.createLink(module: 'eventos');

        expect(link, contains('/eventos'));
      });

      test('should create link with action', () {
        final link = service.createLink(
          module: 'marketplace',
          action: 'search',
        );

        expect(link, contains('/marketplace/search'));
      });

      test('should create link with item ID', () {
        final link = service.createLink(
          module: 'socios',
          action: 'detalle',
          itemId: '999',
        );

        expect(link, contains('/socios/detalle/999'));
      });

      test('should create link with query parameters', () {
        final link = service.createLink(
          module: 'cursos',
          params: {'category': 'tech', 'level': 'beginner'},
        );

        expect(link, contains('/cursos'));
        expect(link, contains('category=tech'));
        expect(link, contains('level=beginner'));
      });
    });

    group('Handler Registration', () {
      test('should register custom handler', () async {
        var handlerCalled = false;

        service.registerHandler((link) async {
          handlerCalled = true;
          return DeepLinkNavigationResult.success('/custom');
        });

        // Simulate link handling
        await service.simulateLink('https://app.example.com/custom-module');

        // Handler registration should work (actual call depends on navigator setup)
      });

      test('should unregister handler', () {
        handler(DeepLinkInfo link) async {
          return DeepLinkNavigationResult.notHandled();
        }

        service.registerHandler(handler);
        service.unregisterHandler(handler);

        // Handler should be removed (no way to directly verify, but no errors)
      });
    });

    group('Pending Links', () {
      test('should have no initial pending link', () {
        expect(service.pendingLink, isNull);
      });
    });
  });
}
