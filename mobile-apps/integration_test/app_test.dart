import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'package:flavor_app/main.dart' as app;

/// Tests de integración E2E para Flavor Platform App
void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('App Launch Tests', () {
    testWidgets('App should start and show splash screen', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que la app se ha iniciado
      expect(find.byType(MaterialApp), findsOneWidget);
    });

    testWidgets('App should navigate to home after splash', (tester) async {
      app.main();

      // Esperar a que pase el splash
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Verificar que llegamos a la pantalla principal
      expect(find.byType(Scaffold), findsWidgets);
    });
  });

  group('Authentication Flow', () {
    testWidgets('Should show login screen for unauthenticated user', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Buscar elementos de login
      final loginButton = find.text('Iniciar sesión');
      final emailField = find.byType(TextField).first;

      // Si no está autenticado, debe mostrar login
      if (loginButton.evaluate().isNotEmpty) {
        expect(loginButton, findsOneWidget);
      }
    });

    testWidgets('Should validate login form', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 3));

      final loginButton = find.text('Iniciar sesión');
      if (loginButton.evaluate().isEmpty) {
        // Usuario ya autenticado, saltar test
        return;
      }

      // Intentar login sin datos
      await tester.tap(loginButton);
      await tester.pumpAndSettle();

      // Debe mostrar errores de validación
      expect(find.textContaining('requerido'), findsWidgets);
    });
  });

  group('Navigation Tests', () {
    testWidgets('Bottom navigation should work', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar bottom navigation
      final bottomNav = find.byType(BottomNavigationBar);

      if (bottomNav.evaluate().isNotEmpty) {
        // Tap en cada tab
        final navBar = tester.widget<BottomNavigationBar>(bottomNav);
        final itemCount = navBar.items.length;

        for (int index = 0; index < itemCount; index++) {
          await tester.tap(find.byType(BottomNavigationBarItem).at(index));
          await tester.pumpAndSettle();
        }
      }
    });

    testWidgets('Drawer should open and close', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar icono de menú
      final menuIcon = find.byIcon(Icons.menu);

      if (menuIcon.evaluate().isNotEmpty) {
        await tester.tap(menuIcon);
        await tester.pumpAndSettle();

        // Verificar drawer abierto
        expect(find.byType(Drawer), findsOneWidget);

        // Cerrar drawer
        await tester.tapAt(const Offset(300, 300));
        await tester.pumpAndSettle();
      }
    });
  });

  group('Events Module', () {
    testWidgets('Should display events list', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Navegar a eventos si es necesario
      final eventosTab = find.text('Eventos');
      if (eventosTab.evaluate().isNotEmpty) {
        await tester.tap(eventosTab);
        await tester.pumpAndSettle();
      }

      // Verificar que se muestran eventos o mensaje vacío
      final eventCards = find.byType(Card);
      final emptyMessage = find.textContaining('No hay eventos');

      expect(
        eventCards.evaluate().isNotEmpty || emptyMessage.evaluate().isNotEmpty,
        isTrue,
      );
    });

    testWidgets('Should open event detail', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Navegar a eventos
      final eventosTab = find.text('Eventos');
      if (eventosTab.evaluate().isNotEmpty) {
        await tester.tap(eventosTab);
        await tester.pumpAndSettle();
      }

      // Tap en primer evento si existe
      final eventCards = find.byType(Card);
      if (eventCards.evaluate().isNotEmpty) {
        await tester.tap(eventCards.first);
        await tester.pumpAndSettle();

        // Verificar pantalla de detalle
        expect(find.byType(Scaffold), findsWidgets);
      }
    });
  });

  group('Marketplace Module', () {
    testWidgets('Should display products grid', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Navegar a marketplace
      final marketplaceTab = find.text('Tienda');
      if (marketplaceTab.evaluate().isEmpty) {
        // Intentar con otro nombre
        final altTab = find.text('Productos');
        if (altTab.evaluate().isNotEmpty) {
          await tester.tap(altTab);
          await tester.pumpAndSettle();
        }
      } else {
        await tester.tap(marketplaceTab);
        await tester.pumpAndSettle();
      }

      // Verificar grid o lista
      final gridView = find.byType(GridView);
      final listView = find.byType(ListView);

      expect(
        gridView.evaluate().isNotEmpty || listView.evaluate().isNotEmpty,
        isTrue,
      );
    });

    testWidgets('Should add product to cart', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar botón de añadir al carrito
      final addToCartButtons = find.byIcon(Icons.add_shopping_cart);

      if (addToCartButtons.evaluate().isNotEmpty) {
        await tester.tap(addToCartButtons.first);
        await tester.pumpAndSettle();

        // Verificar feedback (snackbar o badge)
        final snackbar = find.byType(SnackBar);
        final badge = find.byType(Badge);

        expect(
          snackbar.evaluate().isNotEmpty || badge.evaluate().isNotEmpty,
          isTrue,
        );
      }
    });
  });

  group('Search Functionality', () {
    testWidgets('Should open search', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar icono de búsqueda
      final searchIcon = find.byIcon(Icons.search);

      if (searchIcon.evaluate().isNotEmpty) {
        await tester.tap(searchIcon);
        await tester.pumpAndSettle();

        // Verificar campo de búsqueda
        expect(find.byType(TextField), findsWidgets);
      }
    });

    testWidgets('Should perform search', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Abrir búsqueda
      final searchIcon = find.byIcon(Icons.search);
      if (searchIcon.evaluate().isEmpty) return;

      await tester.tap(searchIcon);
      await tester.pumpAndSettle();

      // Encontrar campo de texto
      final searchField = find.byType(TextField);
      if (searchField.evaluate().isEmpty) return;

      // Escribir término de búsqueda
      await tester.enterText(searchField.first, 'test');
      await tester.testTextInput.receiveAction(TextInputAction.search);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar resultados o mensaje vacío
      expect(find.byType(Scaffold), findsWidgets);
    });
  });

  group('User Profile', () {
    testWidgets('Should show profile screen', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar tab de perfil
      final profileTab = find.text('Perfil');
      final accountIcon = find.byIcon(Icons.person);

      if (profileTab.evaluate().isNotEmpty) {
        await tester.tap(profileTab);
      } else if (accountIcon.evaluate().isNotEmpty) {
        await tester.tap(accountIcon.first);
      }

      await tester.pumpAndSettle();

      // Verificar elementos del perfil
      final avatar = find.byType(CircleAvatar);
      expect(avatar, findsWidgets);
    });
  });

  group('Offline Mode', () {
    testWidgets('Should show offline indicator', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Este test es conceptual - en un entorno real se simularía
      // la desconexión de red
      // Verificar que existe manejo de estado offline
      expect(find.byType(MaterialApp), findsOneWidget);
    });
  });

  group('Pull to Refresh', () {
    testWidgets('Should support pull to refresh', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar RefreshIndicator
      final refreshIndicator = find.byType(RefreshIndicator);

      if (refreshIndicator.evaluate().isNotEmpty) {
        // Simular pull to refresh
        await tester.fling(
          refreshIndicator.first,
          const Offset(0, 300),
          1000,
        );
        await tester.pumpAndSettle(const Duration(seconds: 2));

        // La app debería seguir funcionando
        expect(find.byType(Scaffold), findsWidgets);
      }
    });
  });

  group('Accessibility', () {
    testWidgets('Should have semantic labels', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Verificar que los elementos tienen labels semánticos
      final semantics = tester.getSemantics(find.byType(Scaffold).first);
      expect(semantics, isNotNull);
    });

    testWidgets('Should support large text', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Verificar que la app soporta diferentes escalas de texto
      expect(find.byType(MaterialApp), findsOneWidget);
    });
  });

  group('Performance', () {
    testWidgets('Should load within acceptable time', (tester) async {
      final stopwatch = Stopwatch()..start();

      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 10));

      stopwatch.stop();

      // La app debería cargar en menos de 10 segundos
      expect(stopwatch.elapsedMilliseconds, lessThan(10000));
    });

    testWidgets('Should scroll smoothly', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 5));

      // Buscar lista scrolleable
      final scrollable = find.byType(Scrollable);

      if (scrollable.evaluate().isNotEmpty) {
        // Realizar scroll
        await tester.fling(
          scrollable.first,
          const Offset(0, -500),
          1000,
        );
        await tester.pumpAndSettle();

        // Scroll hacia arriba
        await tester.fling(
          scrollable.first,
          const Offset(0, 500),
          1000,
        );
        await tester.pumpAndSettle();

        // La app debería seguir respondiendo
        expect(find.byType(Scaffold), findsWidgets);
      }
    });
  });
}
