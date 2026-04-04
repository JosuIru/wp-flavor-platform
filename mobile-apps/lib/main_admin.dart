import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/config/app_config.dart';
import 'core/theme/app_colors.dart';
import 'core/utils/logger.dart';
import 'core/utils/haptics.dart';
import 'core/config/server_config.dart';
import 'core/api/api_client.dart';
import 'core/providers/providers.dart'; // Importar providers compartidos
import 'core/providers/sync_provider.dart';
import 'core/providers/admin_modules_provider.dart';
import 'core/modules/lazy/module_lazy_loader.dart';
import 'core/modules/module_screen_registry.dart';
import 'features/admin/dashboard_screen_v2.dart';
import 'features/admin/admin_reservations_screen.dart';
import 'features/admin/admin_chat_screen.dart';
import 'features/admin/stats_screen.dart';
import 'features/admin/calendar_view_screen.dart';
import 'features/admin/customers_screen.dart';
import 'features/admin/manual_customers_screen.dart';
import 'features/admin/settings/language_screen.dart'
    show LanguageScreen, languageProvider;
import 'features/admin/settings/notifications_screen.dart';
import 'features/modules/module_hub_screen.dart';
import 'features/admin/modules_admin_screen_dynamic.dart';
import 'features/admin/settings/support_screen.dart';
import 'features/admin/settings/server_config_screen.dart';
import 'features/admin/camps/camps_management_screen.dart';

part 'main_admin_auth.dart';
part 'main_admin_shell.dart';

/// Punto de entrada para la app de ADMIN
void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  Logger.i('Iniciando app...', tag: 'AdminMain');

  // Cargar la URL del servidor antes de iniciar la app
  final serverUrl = await ServerConfig.getServerUrl();
  Logger.d('serverUrl obtenida: $serverUrl', tag: 'AdminMain');

  final apiUrl = serverUrl.isNotEmpty
      ? await ServerConfig.getFullApiUrl()
      : 'https://placeholder.local/wp-json/chat-ia-mobile/v1'; // URL temporal hasta que se configure

  Logger.d('apiUrl final: $apiUrl', tag: 'AdminMain');

  final apiClient = ApiClient(baseUrl: apiUrl);
  Logger.d('ApiClient creado con baseUrl: ${apiClient.currentBaseUrl}',
      tag: 'AdminMain');

  // Registrar todas las pantallas de módulos
  final registry = ModuleScreenRegistry();
  registry.registerAllScreens();
  Logger.d('Pantallas de módulos registradas', tag: 'AdminMain');

  runApp(
    ProviderScope(
      overrides: [
        // Inicializar el apiClient con la URL guardada
        apiClientProvider.overrideWithValue(apiClient),
      ],
      child: const ChatIAAdminApp(),
    ),
  );

  Logger.i('App iniciada con override de apiClientProvider', tag: 'AdminMain');
}

// apiClientProvider se importa de providers.dart y se sobreescribe en main()

/// Provider para estado de autenticación
final authStateProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(apiClientProvider));
});

/// Provider para información del sitio (logo, nombre, etc.)
final siteInfoProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getSiteInfo();
  if (response.success && response.data != null) {
    return response.data;
  }
  return null;
});

/// Estado de autenticación
class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final Map<String, dynamic>? user;
  final String? error;

  AuthState({
    this.isAuthenticated = false,
    this.isLoading = true,
    this.user,
    this.error,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    Map<String, dynamic>? user,
    String? error,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      user: user ?? this.user,
      error: error,
    );
  }
}

/// Notifier de autenticación
class AuthNotifier extends StateNotifier<AuthState> {
  final ApiClient _api;

  AuthNotifier(this._api) : super(AuthState()) {
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    try {
      // Verificar si hay servidor configurado
      final serverUrl = await ServerConfig.getServerUrl();
      if (serverUrl.isEmpty) {
        state = state.copyWith(isAuthenticated: false, isLoading: false);
        return;
      }

      final hasToken = await _api.hasToken();
      if (hasToken) {
        // Timeout de 10 segundos para verificar token
        final response = await _api.verifyToken().timeout(
              const Duration(seconds: 10),
              onTimeout: () => ApiResponse<Map<String, dynamic>>.error(
                  'Timeout al verificar sesión'),
            );
        if (response.success) {
          state = state.copyWith(
            isAuthenticated: true,
            isLoading: false,
            user: response.data?['user'],
          );
          return;
        }
      }
      state = state.copyWith(isAuthenticated: false, isLoading: false);
    } catch (e) {
      // En caso de error, mostrar login
      state = state.copyWith(isAuthenticated: false, isLoading: false);
    }
  }

  Future<bool> login(String username, String password) async {
    state = state.copyWith(isLoading: true, error: null);

    final response = await _api.login(
      username: username,
      password: password,
      appType: 'admin',
    );

    if (response.success) {
      state = state.copyWith(
        isAuthenticated: true,
        isLoading: false,
        user: response.data?['user'],
      );
      return true;
    } else {
      state = state.copyWith(
        isAuthenticated: false,
        isLoading: false,
        error: response.error,
      );
      return false;
    }
  }

  Future<void> logout() async {
    await _api.logout();
    state = AuthState(isAuthenticated: false, isLoading: false);
  }

  /// Forzar fin de carga (para timeout de seguridad)
  void forceStopLoading() {
    if (state.isLoading) {
      state = state.copyWith(isAuthenticated: false, isLoading: false);
    }
  }
}

/// Provider para verificar si hay servidor configurado
final serverConfiguredProvider = FutureProvider<bool>((ref) async {
  final serverUrl = await ServerConfig.getServerUrl();
  return serverUrl.isNotEmpty;
});

/// App de administración
class ChatIAAdminApp extends ConsumerWidget {
  const ChatIAAdminApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final serverConfigured = ref.watch(serverConfiguredProvider);
    final syncState = ref.watch(syncProvider);

    // Usar nombre del sitio desde sync si está disponible
    final appTitle = syncState.siteName ?? AppConfig.adminAppName;

    return MaterialApp(
      title: appTitle,
      debugShowCheckedModeBanner: AppConfig.isDebug,
      locale: const Locale('es', 'ES'),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: _buildTheme(syncState),
      darkTheme: _buildDarkTheme(syncState),
      themeMode: ThemeMode.system,
      home: serverConfigured.when(
        data: (isConfigured) {
          if (!isConfigured) {
            // Si no hay servidor configurado, mostrar pantalla de setup
            return const AdminSetupScreen();
          }
          // Si hay servidor, seguir flujo normal
          return authState.isLoading
              ? const SplashScreen()
              : authState.isAuthenticated
                  ? const AdminHomeScreen()
                  : const AdminLoginScreen();
        },
        loading: () => const SplashScreen(),
        error: (_, __) => const AdminSetupScreen(),
      ),
    );
  }

  ThemeData _buildTheme(SyncState syncState) {
    // Si hay tema sincronizado, usarlo
    if (syncState.theme != null) {
      final baseTheme = syncState.getThemeData(brightness: Brightness.light);
      return baseTheme.copyWith(
        appBarTheme: const AppBarTheme(
          centerTitle: true,
          elevation: 0,
        ),
        cardTheme: CardTheme(
          elevation: 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        ),
      );
    }

    // Tema por defecto
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: Color(AppColors.secondaryValue),
        brightness: Brightness.light,
      ),
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
      ),
      cardTheme: CardTheme(
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }

  ThemeData _buildDarkTheme(SyncState syncState) {
    // Si hay tema sincronizado, usarlo
    if (syncState.theme != null) {
      final baseTheme = syncState.getThemeData(brightness: Brightness.dark);
      return baseTheme.copyWith(
        appBarTheme: const AppBarTheme(
          centerTitle: true,
          elevation: 0,
        ),
        cardTheme: CardTheme(
          elevation: 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      );
    }

    // Tema por defecto
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: Color(AppColors.secondaryValue),
        brightness: Brightness.dark,
      ),
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
      ),
      cardTheme: CardTheme(
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }
}
