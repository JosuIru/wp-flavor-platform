import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/config/app_config.dart';
import 'core/utils/logger.dart';
import 'core/utils/haptics.dart';
import 'core/config/server_config.dart';
import 'core/api/api_client.dart';
import 'core/providers/providers.dart'; // Importar providers compartidos
import 'core/providers/sync_provider.dart';
import 'core/services/app_sync_service.dart';
import 'core/providers/admin_modules_provider.dart';
import 'core/modules/lazy/module_lazy_loader.dart';
import 'core/modules/module_screen_registry.dart';
import 'features/layouts/layout_config.dart';
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

/// Splash screen mientras carga
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  @override
  void initState() {
    super.initState();
    // Timeout de seguridad: si después de 15 segundos sigue cargando, forzar login
    Future.delayed(const Duration(seconds: 15), () {
      if (mounted) {
        final authState = ref.read(authStateProvider);
        if (authState.isLoading) {
          ref.read(authStateProvider.notifier).forceStopLoading();
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.admin_panel_settings,
              size: 80,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 24),
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            Text(
              i18n.loadingConnecting,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withOpacity(0.6),
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Pantalla de setup inicial (cuando no hay servidor configurado)
class AdminSetupScreen extends ConsumerStatefulWidget {
  const AdminSetupScreen({super.key});

  @override
  ConsumerState<AdminSetupScreen> createState() => _AdminSetupScreenState();
}

class _AdminSetupScreenState extends ConsumerState<AdminSetupScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _urlController = TextEditingController();
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _scanQR() async {
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (context) => const _QRScannerSetupScreen(),
      ),
    );

    if (result != null && result.isNotEmpty && mounted) {
      // Mostrar lo que se escaneó para debug
      final shouldProceed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(i18n.adminSetupQrScannedTitle),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  i18n.adminSetupQrContentLabel,
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: SelectableText(
                    result,
                    style:
                        const TextStyle(fontFamily: 'monospace', fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(i18n.adminSetupConnect),
            ),
          ],
        ),
      );

      if (shouldProceed == true) {
        await _processServerUrl(result);
      }
    }
  }

  Future<void> _processServerUrl(String url) async {
    String serverUrl = url.trim();
    String? adminToken;

    Logger.d('QR escaneado: $serverUrl', tag: 'AdminSetup');

    // Si es JSON, extraer URL y validar token admin
    if (serverUrl.contains('{') && serverUrl.contains('}')) {
      try {
        final jsonData = json.decode(serverUrl) as Map<String, dynamic>;

        // Extraer tipo y token
        final qrType = jsonData['type'] as String? ?? 'client';
        adminToken = jsonData['token'] as String?;

        // Validar que sea QR de admin
        if (qrType != 'admin') {
          setState(() {
            _error = i18n.adminSetupQrClientOnly;
          });
          return;
        }

        // Validar que tenga token
        if (adminToken == null || adminToken.isEmpty) {
          setState(() {
            _error = i18n.adminSetupQrInvalid;
          });
          return;
        }

        // Extraer URL
        serverUrl = (jsonData['url'] as String? ?? '').replaceAll(r'\/', '/');
        Logger.d(
            'URL extraída del JSON: $serverUrl, token presente: ${adminToken.isNotEmpty}',
            tag: 'AdminSetup');
      } catch (e) {
        // Fallback: intentar extraer URL con regex
        Logger.e('Error parseando JSON: $e', tag: 'AdminSetup', error: e);
        var urlMatch = RegExp(r'"url"\s*:\s*"([^"]+)"').firstMatch(serverUrl);
        if (urlMatch != null) {
          serverUrl = urlMatch.group(1)!.replaceAll(r'\/', '/');
        }
      }
    } else {
      // URL directa sin JSON - no permitir para admin
      setState(() {
        _error = i18n.adminSetupRequiresSecureQr;
      });
      return;
    }

    // Limpiar URL
    serverUrl = serverUrl.trim();
    if (serverUrl.endsWith('/')) {
      serverUrl = serverUrl.substring(0, serverUrl.length - 1);
    }

    // Quitar /wp-json/... si está incluido
    final wpJsonIndex = serverUrl.indexOf('/wp-json');
    if (wpJsonIndex > 0) {
      serverUrl = serverUrl.substring(0, wpJsonIndex);
    }

    // Si no tiene protocolo, añadir https://
    if (!serverUrl.startsWith('http://') && !serverUrl.startsWith('https://')) {
      // Si parece un dominio válido, añadir https
      if (serverUrl.contains('.') && !serverUrl.contains(' ')) {
        serverUrl = 'https://$serverUrl';
      } else {
        setState(() {
          _error = i18n.adminSetupQrNotRecognized(url);
        });
        return;
      }
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // Detectar API/namespace del sitio
      final discoveryResponse = await ApiClient.discoverSiteAt(serverUrl);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        setState(() {
          _error = i18n.adminSetupServerError(
            discoveryResponse.error ?? i18n.commonInvalidResponse,
            '$serverUrl/wp-json/app-discovery/v1/info',
          );
        });
        return;
      }

      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        serverUrl,
        discoveryData: discoveryResponse.data,
      );
      final fullUrl = '$serverUrl$apiNamespace';
      Logger.d('Conectando a: $fullUrl', tag: 'AdminSetup');

      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getBusinessInfo().timeout(
            const Duration(seconds: 15),
            onTimeout: () => ApiResponse<Map<String, dynamic>>.error(
              i18n.adminSetupTimeoutServer,
            ),
          );

      Logger.d(
          'Respuesta: success=${response.success}, error=${response.error}',
          tag: 'AdminSetup');

      if (response.success) {
        // Validar token de seguridad admin antes de guardar
        if (adminToken != null && adminToken.isNotEmpty) {
          Logger.d('Validando token de admin...', tag: 'AdminSetup');
          final tokenResponse =
              await testClient.validateAdminSiteToken(adminToken).timeout(
                    const Duration(seconds: 10),
                    onTimeout: () => ApiResponse<Map<String, dynamic>>.error(
                      i18n.adminSetupTimeoutToken,
                    ),
                  );

          if (!tokenResponse.success) {
            setState(() {
              _isLoading = false;
              _error = i18n.adminSetupInvalidToken;
            });
            return;
          }
          Logger.i('Token de admin validado correctamente', tag: 'AdminSetup');
        }

        // Guardar configuración
        await ServerConfig.setServerUrl(serverUrl);
        await ServerConfig.setApiNamespace(apiNamespace);

        // IMPORTANTE: Actualizar la URL del apiClient existente
        ref.read(apiClientProvider).updateBaseUrl(fullUrl);
        Logger.d('ApiClient actualizado con URL: $fullUrl', tag: 'AdminSetup');

        // Sincronizar layouts y tema con el servidor
        Logger.i('Sincronizando layouts y tema con servidor: $serverUrl',
            tag: 'AdminApp');
        final syncResult =
            await ref.read(syncProvider.notifier).syncWithSite(serverUrl);
        if (!syncResult.success) {
          Logger.w(
            'Sincronizacion inicial incompleta: ${syncResult.error}',
            tag: 'AdminApp',
          );
        }

        // Forzar recarga de providers
        ref.invalidate(serverConfiguredProvider);
        ref.invalidate(authStateProvider);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(i18n.adminSetupServerConfiguredSuccess),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _error = i18n.adminSetupServerError(
            response.error ?? i18n.commonInvalidResponse,
            fullUrl,
          );
        });
      }
    } catch (e, stack) {
      Logger.e('Error de conexión: $e', tag: 'AdminSetup', error: e);
      Logger.e('Stack: $stack', tag: 'AdminSetup');
      setState(() {
        _error = i18n.adminSetupConnectionError(e.toString(), serverUrl);
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Logo
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.admin_panel_settings,
                  size: 64,
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
              const SizedBox(height: 32),

              // Título
              Text(
                i18n.adminSetupTitle,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                i18n.adminSetupSubtitle,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 48),

              // Botón de escanear QR
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _isLoading ? null : _scanQR,
                  icon: const Icon(Icons.qr_code_scanner, size: 28),
                  label: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      i18n.adminSetupScanQrButton,
                      style: const TextStyle(fontSize: 16),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                i18n.adminSetupFindQrHint,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: 32),

              // Divider
              Row(
                children: [
                  const Expanded(child: Divider()),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      i18n.adminSetupOrEnterUrl,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.outline,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Expanded(child: Divider()),
                ],
              ),

              const SizedBox(height: 24),

              // Campo de URL manual
              TextField(
                controller: _urlController,
                decoration: InputDecoration(
                  labelText: i18n.adminSetupServerUrlLabel,
                  hintText: i18n.adminSetupServerUrlHint,
                  prefixIcon: const Icon(Icons.language),
                  errorText: _error,
                  suffixIcon: _isLoading
                      ? const Padding(
                          padding: EdgeInsets.all(12),
                          child: SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        )
                      : IconButton(
                          icon: const Icon(Icons.arrow_forward),
                          onPressed: () {
                            if (_urlController.text.isNotEmpty) {
                              _processServerUrl(_urlController.text);
                            }
                          },
                        ),
                ),
                keyboardType: TextInputType.url,
                onSubmitted: (value) {
                  if (value.isNotEmpty) {
                    _processServerUrl(value);
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Pantalla de escaneo QR para setup
class _QRScannerSetupScreen extends StatefulWidget {
  const _QRScannerSetupScreen();

  @override
  State<_QRScannerSetupScreen> createState() => _QRScannerSetupScreenState();
}

class _QRScannerSetupScreenState extends State<_QRScannerSetupScreen> {
  final MobileScannerController _controller = MobileScannerController();
  bool _hasScanned = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_hasScanned) return;

    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final String? code = barcode.rawValue;
      if (code != null && code.isNotEmpty) {
        _hasScanned = true;
        Navigator.pop(context, code);
        return;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocalizations.of(context)!.adminSetupScanQrTitle),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
          ),
          // Marco de escaneo
          Center(
            child: Container(
              width: 280,
              height: 280,
              decoration: BoxDecoration(
                border: Border.all(
                  color: Colors.white.withOpacity(0.5),
                  width: 3,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),
          // Instrucciones
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black.withOpacity(0.9),
                  ],
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    AppLocalizations.of(context)!.adminSetupScanQrInstruction,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    AppLocalizations.of(context)!.adminSetupScanQrSubtitle,
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Pantalla de login admin
class AdminLoginScreen extends ConsumerStatefulWidget {
  const AdminLoginScreen({super.key});

  @override
  ConsumerState<AdminLoginScreen> createState() => _AdminLoginScreenState();
}

class _AdminLoginScreenState extends ConsumerState<AdminLoginScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  final _storage = const FlutterSecureStorage();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _rememberCredentials = true;

  static const _usernameKey = 'saved_username';
  static const _passwordKey = 'saved_password';
  static const _rememberKey = 'remember_credentials';

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();
  }

  Future<void> _loadSavedCredentials() async {
    final remember = await _storage.read(key: _rememberKey);
    if (remember == 'true') {
      final username = await _storage.read(key: _usernameKey);
      final password = await _storage.read(key: _passwordKey);
      if (username != null && password != null) {
        setState(() {
          _usernameController.text = username;
          _passwordController.text = password;
          _rememberCredentials = true;
        });
      }
    }
  }

  Future<void> _saveCredentials() async {
    if (_rememberCredentials) {
      await _storage.write(
          key: _usernameKey, value: _usernameController.text.trim());
      await _storage.write(key: _passwordKey, value: _passwordController.text);
      await _storage.write(key: _rememberKey, value: 'true');
    } else {
      await _storage.delete(key: _usernameKey);
      await _storage.delete(key: _passwordKey);
      await _storage.write(key: _rememberKey, value: 'false');
    }
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final success = await ref.read(authStateProvider.notifier).login(
          _usernameController.text.trim(),
          _passwordController.text,
        );

    if (success) {
      await _saveCredentials();
    }

    if (mounted) {
      setState(() => _isLoading = false);
    }

    if (!success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content:
              Text(ref.read(authStateProvider).error ?? i18n.adminLoginError),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Widget _buildLogo() {
    final siteInfo = ref.watch(siteInfoProvider);

    return siteInfo.when(
      data: (info) {
        final logoUrl = info?['logo_url'] as String?;
        if (logoUrl != null && logoUrl.isNotEmpty) {
          return ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: Image.network(
              logoUrl,
              height: 80,
              width: 80,
              fit: BoxFit.contain,
              errorBuilder: (context, error, stackTrace) => Icon(
                Icons.admin_panel_settings,
                size: 80,
                color: Theme.of(context).colorScheme.primary,
              ),
              loadingBuilder: (context, child, loadingProgress) {
                if (loadingProgress == null) return child;
                return SizedBox(
                  height: 80,
                  width: 80,
                  child: Center(
                    child: CircularProgressIndicator(
                      value: loadingProgress.expectedTotalBytes != null
                          ? loadingProgress.cumulativeBytesLoaded /
                              loadingProgress.expectedTotalBytes!
                          : null,
                      strokeWidth: 2,
                    ),
                  ),
                );
              },
            ),
          );
        }
        return Icon(
          Icons.admin_panel_settings,
          size: 80,
          color: Theme.of(context).colorScheme.primary,
        );
      },
      loading: () => SizedBox(
        height: 80,
        width: 80,
        child: Center(
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
      ),
      error: (_, __) => Icon(
        Icons.admin_panel_settings,
        size: 80,
        color: Theme.of(context).colorScheme.primary,
      ),
    );
  }

  Widget _buildSiteName() {
    final siteInfo = ref.watch(siteInfoProvider);
    final i18n = AppLocalizations.of(context)!;

    return siteInfo.when(
      data: (info) {
        final siteName = info?['name'] as String?;
        return Text(
          siteName ?? i18n.adminAppTitle,
          style: Theme.of(context).textTheme.headlineMedium,
          textAlign: TextAlign.center,
        );
      },
      loading: () => Text(
        i18n.adminAppTitle,
        style: Theme.of(context).textTheme.headlineMedium,
        textAlign: TextAlign.center,
      ),
      error: (_, __) => Text(
        i18n.adminAppTitle,
        style: Theme.of(context).textTheme.headlineMedium,
        textAlign: TextAlign.center,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.dns),
            tooltip: i18n.adminLoginConfigServerTooltip,
            onPressed: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ServerConfigScreen()),
              );
              // Recargar la app después de configurar el servidor
              if (mounted) {
                ref.invalidate(siteInfoProvider);
              }
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildLogo(),
                  const SizedBox(height: 16),
                  _buildSiteName(),
                  const SizedBox(height: 8),
                  Text(
                    i18n.adminLoginSubtitle,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withOpacity(0.7),
                        ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  // Mostrar URL del servidor configurado
                  FutureBuilder<String>(
                    future: ServerConfig.getServerUrl(),
                    builder: (context, snapshot) {
                      final serverUrl = snapshot.data ?? '';
                      final isConfigured = serverUrl.isNotEmpty;
                      return Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        decoration: BoxDecoration(
                          color: isConfigured
                              ? Colors.green.withOpacity(0.1)
                              : Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isConfigured
                                ? Colors.green.withOpacity(0.3)
                                : Colors.orange.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              isConfigured ? Icons.check_circle : Icons.warning,
                              size: 20,
                              color:
                                  isConfigured ? Colors.green : Colors.orange,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    isConfigured
                                        ? i18n.adminServerConfiguredStatus
                                        : i18n.adminServerNotConfiguredStatus,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 12,
                                      color: isConfigured
                                          ? Colors.green
                                          : Colors.orange,
                                    ),
                                  ),
                                  if (isConfigured)
                                    Text(
                                      serverUrl,
                                      style: const TextStyle(fontSize: 11),
                                      overflow: TextOverflow.ellipsis,
                                    )
                                  else
                                    Text(
                                      i18n.adminServerConfigureHint,
                                      style: const TextStyle(fontSize: 11),
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 32),
                  AutofillGroup(
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _usernameController,
                          decoration: InputDecoration(
                            labelText: i18n.adminLoginUsernameLabel,
                            prefixIcon: const Icon(Icons.person),
                          ),
                          autofillHints: const [AutofillHints.username],
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return i18n.adminLoginUsernameRequired;
                            }
                            return null;
                          },
                          textInputAction: TextInputAction.next,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          decoration: InputDecoration(
                            labelText: i18n.adminLoginPasswordLabel,
                            prefixIcon: const Icon(Icons.lock),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                              ),
                              onPressed: () {
                                setState(
                                    () => _obscurePassword = !_obscurePassword);
                              },
                            ),
                          ),
                          autofillHints: const [AutofillHints.password],
                          obscureText: _obscurePassword,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return i18n.adminLoginPasswordRequired;
                            }
                            return null;
                          },
                          onFieldSubmitted: (_) => _handleLogin(),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Checkbox(
                        value: _rememberCredentials,
                        onChanged: (value) {
                          setState(() => _rememberCredentials = value ?? false);
                        },
                      ),
                      GestureDetector(
                        onTap: () {
                          setState(() =>
                              _rememberCredentials = !_rememberCredentials);
                        },
                        child: Text(i18n.adminLoginRememberCredentials),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _isLoading ? null : _handleLogin,
                    child: _isLoading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text(i18n.adminLoginButton),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Pantalla principal admin
class AdminHomeScreen extends ConsumerStatefulWidget {
  const AdminHomeScreen({super.key});

  @override
  ConsumerState<AdminHomeScreen> createState() => _AdminHomeScreenState();
}

class _AdminHomeScreenState extends ConsumerState<AdminHomeScreen> {
  int _currentIndex = 0;

  void _navigateToTab(int index) {
    setState(() => _currentIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    // Obtener módulos activos
    final modulesAsync = ref.watch(adminModulesProvider);

    return modulesAsync.when(
      loading: () => const Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 16),
              Text('Cargando...'),
            ],
          ),
        ),
      ),
      error: (error, stack) {
        Logger.e('Error al cargar módulos: $error',
            tag: 'AdminHome', error: error);
        return Scaffold(
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 48, color: Colors.red),
                const SizedBox(height: 16),
                Text('Error: ${error.toString()}'),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => ref.invalidate(adminModulesProvider),
                  child: const Text('Reintentar'),
                ),
              ],
            ),
          ),
        );
      },
      data: (modules) => _buildAdminScaffold(context, i18n, modules),
    );
  }

  Widget _buildAdminScaffold(
    BuildContext context,
    AppLocalizations i18n,
    List<String> modules,
  ) {
    // Construir tabs dinámicamente basados en módulos activos
    final tabs = <AdminTab>[];

    // 1. Dashboard (siempre presente)
    tabs.add(AdminTab(
      id: 'dashboard',
      label: i18n.adminBottomNavDashboard,
      icon: Icons.dashboard_outlined,
      selectedIcon: Icons.dashboard,
      screen: DashboardScreenV2(
        onNavigateToReservations: () {},
        onNavigateToChat: () {},
      ),
    ));

    // 2. Reservas (si está activo)
    final hasReservations = modules.any((m) =>
        m == 'reservas' ||
        m == 'reservations' ||
        m == 'experiences' ||
        m == 'eventos' ||
        m == 'calendar');

    if (hasReservations) {
      tabs.add(AdminTab(
        id: 'reservations',
        label: i18n.adminBottomNavReservations,
        icon: Icons.calendar_today_outlined,
        selectedIcon: Icons.calendar_today,
        screen: const AdminReservationsScreen(),
      ));
    }

    // 3. Chat (si está activo)
    final hasChat = modules.any((m) => m.contains('chat'));

    if (hasChat) {
      tabs.add(AdminTab(
        id: 'chat',
        label: i18n.adminBottomNavChat,
        icon: Icons.smart_toy_outlined,
        selectedIcon: Icons.smart_toy,
        screen: const AdminChatScreen(),
      ));
    }

    // 4. Módulos (si hay módulos con pantallas implementadas)
    final loader = ModuleLazyLoader();
    final hasModulesWithScreens = modules.any((m) =>
        loader.getScreenBuilder(m) != null ||
        loader.getScreenBuilder(m.replaceAll('_', '-')) != null ||
        loader.getScreenBuilder(m.replaceAll('-', '_')) != null);

    if (hasModulesWithScreens) {
      tabs.add(AdminTab(
        id: 'modules',
        label: i18n.adminModulesDashboardTitle,
        icon: Icons.extension_outlined,
        selectedIcon: Icons.extension,
        screen: const ModulesAdminScreenDynamic(),
      ));
    }

    // 5. Settings (siempre presente)
    tabs.add(AdminTab(
      id: 'settings',
      label: i18n.adminBottomNavSettings,
      icon: Icons.settings_outlined,
      selectedIcon: Icons.settings,
      screen: const AdminSettingsScreen(),
    ));

    final bottomTabs = tabs.take(4).toList();
    final extraTabs = tabs.skip(4).toList();

    // Construir drawer items
    final drawerItems = <AdminDrawerItem>[
      for (final tab in tabs)
        AdminDrawerItem(
          label: tab.label,
          icon: tab.icon,
          screen: tab.screen,
        ),
      AdminDrawerItem(
        label: i18n.adminDrawerStats,
        icon: Icons.query_stats_outlined,
        screen: const StatsScreen(),
      ),
      AdminDrawerItem(
        label: i18n.adminDrawerCalendar,
        icon: Icons.calendar_month_outlined,
        screen: const CalendarViewScreen(),
      ),
      AdminDrawerItem(
        label: i18n.adminDrawerCustomers,
        icon: Icons.people_outline,
        screen: const CustomersScreen(),
      ),
      AdminDrawerItem(
        label: i18n.adminDrawerManualCustomers,
        icon: Icons.person_add_alt_1_outlined,
        screen: const ManualCustomersScreen(),
      ),
      AdminDrawerItem(
        label: i18n.adminDrawerCamps,
        icon: Icons.terrain_outlined,
        screen: const CampsManagementScreen(),
      ),
    ];

    final hasDrawer = extraTabs.isNotEmpty || drawerItems.isNotEmpty;

    return Scaffold(
      appBar: hasDrawer
          ? AppBar(
              title: Text(i18n.adminAppTitle),
            )
          : null,
      drawer: hasDrawer
          ? _AdminDrawer(
              tabs: tabs,
              currentIndex: _currentIndex,
              onTabSelected: _navigateToTab,
            )
          : null,
      body: IndexedStack(
        index: _currentIndex,
        children: bottomTabs.map((tab) => tab.screen).toList(),
      ),
      bottomNavigationBar: bottomTabs.length >= 2
          ? NavigationBar(
              selectedIndex: _currentIndex,
              onDestinationSelected: (index) {
                Haptics.selection();
                setState(() => _currentIndex = index);
              },
              destinations: bottomTabs
                  .map((tab) => NavigationDestination(
                        icon: Icon(tab.icon),
                        selectedIcon: Icon(tab.selectedIcon),
                        label: tab.label,
                      ))
                  .toList(),
            )
          : null,
    );
  }
}

/// Drawer del admin con secciones colapsables
class _AdminDrawer extends ConsumerStatefulWidget {
  final List<AdminTab> tabs;
  final int currentIndex;
  final void Function(int) onTabSelected;

  const _AdminDrawer({
    required this.tabs,
    required this.currentIndex,
    required this.onTabSelected,
  });

  @override
  ConsumerState<_AdminDrawer> createState() => _AdminDrawerState();
}

class _AdminDrawerState extends ConsumerState<_AdminDrawer> {
  // Estado de expansión de cada sección (guardado localmente)
  final Map<String, bool> _expandedSections = {
    'navigation': false,
    'management': true,
    'modules': false,
  };

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final theme = Theme.of(context);
    final syncState = ref.watch(syncProvider);
    final siteName = syncState.siteName ?? i18n.adminAppTitle;

    return Drawer(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          // Header con logo del sitio
          DrawerHeader(
            decoration: BoxDecoration(
              color: theme.colorScheme.primaryContainer,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                Icon(
                  Icons.admin_panel_settings,
                  size: 48,
                  color: theme.colorScheme.primary,
                ),
                const SizedBox(height: 12),
                Text(
                  siteName,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),

          // === SECCIÓN: NAVEGACIÓN RÁPIDA (tabs actuales) ===
          _buildSection(
            context: context,
            key: 'navigation',
            icon: Icons.dashboard_outlined,
            title: i18n.adminDrawerSectionNavigation,
            initiallyExpanded: _expandedSections['navigation'] ?? false,
            children: widget.tabs.asMap().entries.map((entry) {
              final index = entry.key;
              final tab = entry.value;
              final isSelected = index == widget.currentIndex;
              return ListTile(
                leading: Icon(
                  isSelected ? tab.selectedIcon : tab.icon,
                  color: isSelected ? theme.colorScheme.primary : null,
                ),
                title: Text(
                  tab.label,
                  style: isSelected
                      ? TextStyle(
                          color: theme.colorScheme.primary,
                          fontWeight: FontWeight.bold,
                        )
                      : null,
                ),
                selected: isSelected,
                onTap: () {
                  Navigator.pop(context);
                  widget.onTabSelected(index);
                },
              );
            }).toList(),
          ),

          const Divider(height: 1),

          // === SECCIÓN: GESTIÓN ===
          _buildSection(
            context: context,
            key: 'management',
            icon: Icons.business_center_outlined,
            title: i18n.adminDrawerSectionManagement,
            initiallyExpanded: _expandedSections['management'] ?? true,
            children: [
              _buildDrawerTile(
                context: context,
                icon: Icons.query_stats_outlined,
                title: i18n.dashboardStats,
                screen: const StatsScreen(),
              ),
              _buildDrawerTile(
                context: context,
                icon: Icons.calendar_month_outlined,
                title: i18n.dashboardCalendar,
                screen: const CalendarViewScreen(),
              ),
              _buildDrawerTile(
                context: context,
                icon: Icons.people_outline,
                title: i18n.dashboardCustomers,
                screen: const CustomersScreen(),
              ),
              _buildDrawerTile(
                context: context,
                icon: Icons.person_add_alt_1_outlined,
                title: i18n.adminDrawerManualCustomers,
                screen: const ManualCustomersScreen(),
              ),
              _buildDrawerTile(
                context: context,
                icon: Icons.terrain_outlined,
                title: i18n.dashboardCamps,
                screen: const CampsManagementScreen(),
              ),
            ],
          ),

          const Divider(height: 1),

          // === SECCIÓN: MÓDULOS ===
          _buildSection(
            context: context,
            key: 'modules',
            icon: Icons.extension_outlined,
            title: i18n.adminSettingsModulesTitle,
            initiallyExpanded: _expandedSections['modules'] ?? false,
            children: [
              _buildDrawerTile(
                context: context,
                icon: Icons.apps_outlined,
                title: i18n.adminSettingsModulesSubtitle,
                screen: const ModuleHubScreen(isAdmin: true),
              ),
              _buildDrawerTile(
                context: context,
                icon: Icons.dashboard_customize_outlined,
                title: i18n.adminModulesDashboardTitle,
                screen: const ModulesAdminScreenDynamic(),
              ),
            ],
          ),

          const Divider(height: 1),

          // === AJUSTES (siempre visible, no colapsable) ===
          ListTile(
            leading: const Icon(Icons.settings_outlined),
            title: Text(i18n.adminBottomNavSettings),
            onTap: () {
              Navigator.pop(context);
              // Buscar el índice de settings en tabs
              final settingsIndex =
                  widget.tabs.indexWhere((t) => t.id == 'settings');
              if (settingsIndex >= 0) {
                widget.onTabSelected(settingsIndex);
              } else {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                      builder: (_) => const AdminSettingsScreen()),
                );
              }
            },
          ),
        ],
      ),
    );
  }

  Widget _buildSection({
    required BuildContext context,
    required String key,
    required IconData icon,
    required String title,
    required bool initiallyExpanded,
    required List<Widget> children,
  }) {
    return ExpansionTile(
      leading: Icon(icon),
      title: Text(
        title,
        style: const TextStyle(fontWeight: FontWeight.w500),
      ),
      initiallyExpanded: initiallyExpanded,
      onExpansionChanged: (expanded) {
        setState(() {
          _expandedSections[key] = expanded;
        });
      },
      children: children,
    );
  }

  Widget _buildDrawerTile({
    required BuildContext context,
    required IconData icon,
    required String title,
    required Widget screen,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.only(left: 56, right: 16),
      leading: Icon(icon, size: 22),
      title: Text(title),
      dense: true,
      onTap: () {
        Navigator.pop(context);
        Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => screen),
        );
      },
    );
  }
}

/// Modelo para un tab del admin
class AdminTab {
  final String id;
  final String label;
  final IconData icon;
  final IconData selectedIcon;
  final Widget screen;

  const AdminTab({
    required this.id,
    required this.label,
    required this.icon,
    required this.selectedIcon,
    required this.screen,
  });
}

/// Modelo para un item del drawer
class AdminDrawerItem {
  final String label;
  final IconData icon;
  final Widget screen;

  const AdminDrawerItem({
    required this.label,
    required this.icon,
    required this.screen,
  });
}

/// Pantalla de ajustes
class AdminSettingsScreen extends ConsumerWidget {
  const AdminSettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final user = ref.watch(authStateProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.adminSettingsTitle),
      ),
      body: ListView(
        children: [
          // Info del usuario
          Container(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 40,
                  backgroundColor:
                      Theme.of(context).colorScheme.primaryContainer,
                  child: Icon(
                    Icons.person,
                    size: 40,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  user?['name'] ?? i18n.adminSettingsUserFallback,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                if (user?['email'] != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    user!['email'],
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withOpacity(0.7),
                        ),
                  ),
                ],
              ],
            ),
          ),
          const Divider(),

          // Configuración
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              i18n.adminSettingsSectionConfig,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                  ),
            ),
          ),
          Consumer(
            builder: (context, ref, _) {
              final language = ref.watch(languageProvider);
              final languageNames = {
                'es': i18n.languageNameEs,
                'eu': i18n.languageNameEu,
                'en': i18n.languageNameEn,
                'fr': i18n.languageNameFr,
                'ca': i18n.languageNameCa,
              };
              return ListTile(
                leading: const Icon(Icons.language),
                title: Text(i18n.adminSettingsLanguageTitle),
                subtitle: Text(languageNames[language] ?? i18n.languageNameEs),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const LanguageScreen()),
                  );
                },
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.notifications),
            title: Text(i18n.adminSettingsNotificationsTitle),
            subtitle: Text(i18n.adminSettingsNotificationsSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.extension),
            title: Text(i18n.adminSettingsModulesTitle),
            subtitle: Text(i18n.adminSettingsModulesSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ModuleHubScreen(isAdmin: true),
                ),
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.dashboard_customize),
            title: Text(i18n.adminModulesDashboardTitle),
            subtitle: Text(i18n.adminModulesDashboardSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ModulesAdminScreenDynamic(),
                ),
              );
            },
          ),
          const Divider(),

          // Configuracion del servidor
          ListTile(
            leading: Icon(
              Icons.dns,
              color: Theme.of(context).colorScheme.secondary,
            ),
            title: Text(i18n.adminSettingsServerTitle),
            subtitle: FutureBuilder<String>(
              future: ServerConfig.getServerUrl(),
              builder: (context, snapshot) {
                return Text(snapshot.data ?? i18n.adminSettingsServerLoading);
              },
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ServerConfigScreen()),
              );
            },
          ),
          ListTile(
            leading: Icon(
              Icons.sync,
              color: Theme.of(context).colorScheme.secondary,
            ),
            title: Text(i18n.commonResyncTitle),
            subtitle: Text(i18n.commonResyncSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () async {
              final messenger = ScaffoldMessenger.of(context);
              messenger.showSnackBar(
                SnackBar(
                  content: Text(i18n.commonResyncInProgress),
                  behavior: SnackBarBehavior.floating,
                ),
              );
              final result = await ref.read(syncProvider.notifier).refresh();
              if (!context.mounted) return;
              if (result.success) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(i18n.commonResyncSuccess),
                    backgroundColor: Colors.green,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              } else {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(i18n.commonResyncError),
                    backgroundColor: Colors.red,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            },
          ),
          const Divider(),

          // App Cliente
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              i18n.adminSettingsAppsSection,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                  ),
            ),
          ),
          ListTile(
            leading: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primaryContainer,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                Icons.phone_android,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
            title: Text(i18n.adminSettingsClientAppTitle),
            subtitle: Text(i18n.adminSettingsClientAppSubtitle),
            trailing: const Icon(Icons.open_in_new),
            onTap: () async {
              final success = await AppConfig.openClientApp();
              if (!success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(i18n.adminSettingsClientAppMissing),
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            },
          ),
          const Divider(),

          // Soporte
          ListTile(
            leading: Icon(
              Icons.support_agent,
              color: Theme.of(context).colorScheme.primary,
            ),
            title: Text(i18n.adminSettingsSupportTitle),
            subtitle: Text(i18n.adminSettingsSupportSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const SupportScreen()),
              );
            },
          ),
          const Divider(),

          // Info de la app
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: Text(i18n.adminSettingsVersionTitle),
            subtitle: Text(AppConfig.appVersion),
          ),
          ListTile(
            leading: const Icon(Icons.code),
            title: Text(i18n.adminSettingsDevelopedByTitle),
            subtitle: Text(AppConfig.developerName),
          ),
          const Divider(),

          // Cerrar sesión
          ListTile(
            leading: Icon(
              Icons.logout,
              color: Theme.of(context).colorScheme.error,
            ),
            title: Text(
              i18n.adminSettingsLogoutTitle,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            onTap: () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: Text(i18n.adminSettingsLogoutTitle),
                  content: Text(i18n.adminSettingsLogoutConfirm),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: Text(i18n.commonCancel),
                    ),
                    FilledButton(
                      onPressed: () {
                        Navigator.pop(context);
                        ref.read(authStateProvider.notifier).logout();
                      },
                      style: FilledButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.error,
                      ),
                      child: Text(i18n.adminSettingsLogoutAction),
                    ),
                  ],
                ),
              );
            },
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }
}
