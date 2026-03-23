import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:network_image_mock/network_image_mock.dart';

import 'package:flavor_app/core/services/image_cache_service.dart';

void main() {
  group('CachedImage Widget', () {
    testWidgets('should render with required parameters', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
              ),
            ),
          ),
        );

        expect(find.byType(CachedImage), findsOneWidget);
      });
    });

    testWidgets('should apply width and height', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
                width: 100,
                height: 100,
              ),
            ),
          ),
        );

        final cachedImage = tester.widget<CachedImage>(find.byType(CachedImage));
        expect(cachedImage.width, equals(100));
        expect(cachedImage.height, equals(100));
      });
    });

    testWidgets('should apply border radius', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
                borderRadius: BorderRadius.circular(16),
              ),
            ),
          ),
        );

        expect(find.byType(ClipRRect), findsOneWidget);
      });
    });

    testWidgets('should use custom placeholder', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
                placeholder: Icon(Icons.image),
              ),
            ),
          ),
        );

        // Placeholder should be provided
        final cachedImage = tester.widget<CachedImage>(find.byType(CachedImage));
        expect(cachedImage.placeholder, isNotNull);
      });
    });

    testWidgets('should use custom error widget', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
                errorWidget: Icon(Icons.error),
              ),
            ),
          ),
        );

        final cachedImage = tester.widget<CachedImage>(find.byType(CachedImage));
        expect(cachedImage.errorWidget, isNotNull);
      });
    });

    testWidgets('should apply box fit', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedImage(
                imageUrl: 'https://example.com/image.jpg',
                fit: BoxFit.contain,
              ),
            ),
          ),
        );

        final cachedImage = tester.widget<CachedImage>(find.byType(CachedImage));
        expect(cachedImage.fit, equals(BoxFit.contain));
      });
    });
  });

  group('CachedAvatar Widget', () {
    testWidgets('should render initials when no image', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedAvatar(
              name: 'John Doe',
              radius: 24,
            ),
          ),
        ),
      );

      expect(find.byType(CircleAvatar), findsOneWidget);
      expect(find.text('JD'), findsOneWidget);
    });

    testWidgets('should render single initial for single name', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedAvatar(
              name: 'John',
              radius: 24,
            ),
          ),
        ),
      );

      expect(find.text('J'), findsOneWidget);
    });

    testWidgets('should render question mark for empty name', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedAvatar(
              name: '',
              radius: 24,
            ),
          ),
        ),
      );

      expect(find.text('?'), findsOneWidget);
    });

    testWidgets('should apply custom radius', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedAvatar(
              name: 'Test User',
              radius: 48,
            ),
          ),
        ),
      );

      final cachedAvatar = tester.widget<CachedAvatar>(find.byType(CachedAvatar));
      expect(cachedAvatar.radius, equals(48));
    });

    testWidgets('should apply custom background color', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedAvatar(
              name: 'Test',
              radius: 24,
              backgroundColor: Colors.red,
            ),
          ),
        ),
      );

      final cachedAvatar = tester.widget<CachedAvatar>(find.byType(CachedAvatar));
      expect(cachedAvatar.backgroundColor, equals(Colors.red));
    });

    testWidgets('should render with image URL', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedAvatar(
                imageUrl: 'https://example.com/avatar.jpg',
                name: 'John Doe',
                radius: 24,
              ),
            ),
          ),
        );

        expect(find.byType(CachedAvatar), findsOneWidget);
      });
    });
  });

  group('CachedProductImage Widget', () {
    testWidgets('should render with aspect ratio', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedProductImage(
              imageUrl: null,
              aspectRatio: 16 / 9,
            ),
          ),
        ),
      );

      expect(find.byType(AspectRatio), findsOneWidget);
    });

    testWidgets('should show placeholder for null image', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedProductImage(
              imageUrl: null,
            ),
          ),
        ),
      );

      expect(find.byIcon(Icons.image), findsOneWidget);
    });

    testWidgets('should show placeholder for empty image URL', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedProductImage(
              imageUrl: '',
            ),
          ),
        ),
      );

      expect(find.byIcon(Icons.image), findsOneWidget);
    });

    testWidgets('should apply custom border radius', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: CachedProductImage(
              imageUrl: null,
              borderRadius: BorderRadius.all(Radius.circular(16)),
            ),
          ),
        ),
      );

      final clipRRect = tester.widget<ClipRRect>(find.byType(ClipRRect));
      expect(clipRRect.borderRadius, equals(const BorderRadius.all(Radius.circular(16))));
    });

    testWidgets('should render image when URL provided', (tester) async {
      await mockNetworkImagesFor(() async {
        await tester.pumpWidget(
          const MaterialApp(
            home: Scaffold(
              body: CachedProductImage(
                imageUrl: 'https://example.com/product.jpg',
                aspectRatio: 1.0,
              ),
            ),
          ),
        );

        expect(find.byType(CachedProductImage), findsOneWidget);
      });
    });
  });

  group('ImageCacheConfig', () {
    test('should have default values', () {
      const config = ImageCacheConfig();

      expect(config.stalePeriod, equals(const Duration(days: 7)));
      expect(config.maxNrOfCacheObjects, equals(200));
      expect(config.cacheKey, equals('flavorImageCache'));
    });

    test('should accept custom values', () {
      const config = ImageCacheConfig(
        stalePeriod: Duration(days: 14),
        maxNrOfCacheObjects: 500,
        cacheKey: 'customCache',
      );

      expect(config.stalePeriod, equals(const Duration(days: 14)));
      expect(config.maxNrOfCacheObjects, equals(500));
      expect(config.cacheKey, equals('customCache'));
    });
  });
}
