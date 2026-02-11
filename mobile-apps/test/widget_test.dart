// Test básico de la app Chat IA

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:chat_ia_apps/main.dart';

void main() {
  testWidgets('App Selector renders correctly', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(
      const ProviderScope(
        child: AppSelectorApp(),
      ),
    );

    // Verify that the app title is displayed
    expect(find.text('Basabere'), findsOneWidget);

    // Verify that both app options are displayed (default locale: en)
    expect(find.text('Book Experience'), findsOneWidget);
    expect(find.text('Administration'), findsOneWidget);
  });
}
