import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Provider para el idioma seleccionado
final languageProvider = StateNotifierProvider<LanguageNotifier, String>((ref) {
  return LanguageNotifier();
});

class LanguageNotifier extends StateNotifier<String> {
  LanguageNotifier() : super('es') {
    _loadLanguage();
  }

  static const String _key = 'app_language';

  Future<void> _loadLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    final savedLanguage = prefs.getString(_key);
    if (savedLanguage != null) {
      state = savedLanguage;
    }
  }

  Future<void> setLanguage(String language) async {
    state = language;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, language);
  }
}

/// Pantalla de seleccion de idioma
class LanguageScreen extends ConsumerWidget {
  const LanguageScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selectedLanguage = ref.watch(languageProvider);

    final languages = [
      (code: 'es', name: 'Espanol', flag: '🇪🇸', native: 'Espanol'),
      (code: 'eu', name: 'Euskera', flag: '🏴', native: 'Euskara'),
      (code: 'en', name: 'Ingles', flag: '🇬🇧', native: 'English'),
      (code: 'fr', name: 'Frances', flag: '🇫🇷', native: 'Francais'),
      (code: 'ca', name: 'Catalan', flag: '🏳️', native: 'Catala'),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocalizations.of(context)!.languageTitle),
      ),
      body: ListView(
        children: [
          // Info
          Container(
            padding: const EdgeInsets.all(16),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        AppLocalizations.of(context)!.languageDescription,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Lista de idiomas
          ...languages.map((lang) {
            final isSelected = selectedLanguage == lang.code;
            return ListTile(
              leading: Text(
                lang.flag,
                style: const TextStyle(fontSize: 28),
              ),
              title: Text(
                lang.name,
                style: TextStyle(
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
              ),
              subtitle: Text(lang.native),
              trailing: isSelected
                  ? Icon(
                      Icons.check_circle,
                      color: Theme.of(context).colorScheme.primary,
                    )
                  : null,
              onTap: () {
                ref.read(languageProvider.notifier).setLanguage(lang.code);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      AppLocalizations.of(context)!.languageChanged(lang.name),
                    ),
                    duration: const Duration(seconds: 2),
                  ),
                );
              },
            );
          }),
        ],
      ),
    );
  }
}
