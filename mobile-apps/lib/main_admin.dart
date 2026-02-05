import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/config/app_config.dart';
import 'core/config/server_config.dart';
import 'core/api/api_client.dart';
import 'core/providers/providers.dart'; // Importar providers compartidos
import 'core/providers/sync_provider.dart';
import 'core/services/app_sync_service.dart';
import 'features/layouts/layout_config.dart';
import 'features/admin/dashboard_screen.dart';
import 'features/admin/admin_reservations_screen.dart';
import 'features/admin/admin_chat_screen.dart';
import 'features/admin/stats_screen.dart';
import 'features/admin/calendar_view_screen.dart';
import 'features/admin/customers_screen.dart';
import 'features/admin/manual_customers_screen.dart';
import 'features/admin/settings/language_screen.dart' show LanguageScreen, languageProvider;
import 'features/admin/settings/notifications_screen.dart';
import 'features/admin/settings/support_screen.dart';
import 'features/admin/settings/server_config_screen.dart';
import 'features/admin/camps/camps_management_screen.dart';

/// Punto de entrada para la app de ADMIN
void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  debugPrint('[ADMIN MAIN] Iniciando app...');

  // Cargar la URL del servidor antes de iniciar la app
  final serverUrl = await ServerConfig.getServerUrl();
  debugPrint('[ADMIN MAIN] serverUrl obtenida: $serverUrl');

  final apiUrl = serverUrl.isNotEmpty
      ? await ServerConfig.getFullApiUrl()
      : 'https://placeholder.local/wp-json/chat-ia-mobile/v1'; // URL temporal hasta que se configure

  debugPrint('[ADMIN MAIN] apiUrl final: $apiUrl');

  final apiClient = ApiClient(baseUrl: apiUrl);
  debugPrint('[ADMIN MAIN] ApiClient creado con baseUrl: ${apiClient.currentBaseUrl}');

  runApp(
    ProviderScope(
      overrides: [
        // Inicializar el apiClient con la URL guardada
        apiClientProvider.overrideWithValue(apiClient),
      ],
      child: const ChatIAAdminApp(),
    ),
  );

  debugPrint('[ADMIN MAIN] App iniciada con override de apiClientProvider');
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
          onTimeout: () => ApiResponse<Map<String, dynamic>>.error('Timeout al verificar sesión'),
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
      // Localización en español
      locale: const Locale('es', 'ES'),
      supportedLocales: const [
        Locale('es', 'ES'),
        Locale('en', 'US'),
      ],
      localizationsDelegates: const [
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
              'Conectando...',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurface.withOpacity(0.6),
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
          title: const Text('QR Escaneado'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Contenido del QR:', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: SelectableText(
                    result,
                    style: const TextStyle(fontFamily: 'monospace', fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Conectar'),
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

    debugPrint('QR escaneado: $serverUrl');

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
            _error = '⚠️ Este QR es para la app de clientes.\n\nUsa el QR de Admin (amarillo) desde el panel de WordPress.';
          });
          return;
        }

        // Validar que tenga token
        if (adminToken == null || adminToken.isEmpty) {
          setState(() {
            _error = '❌ QR de Admin inválido.\n\nEl código QR no incluye token de seguridad. Genera uno nuevo desde WordPress.';
          });
          return;
        }

        // Extraer URL
        serverUrl = (jsonData['url'] as String? ?? '').replaceAll(r'\/', '/');
        debugPrint('URL extraída del JSON: $serverUrl, token presente: ${adminToken.isNotEmpty}');
      } catch (e) {
        // Fallback: intentar extraer URL con regex
        debugPrint('Error parseando JSON: $e');
        var urlMatch = RegExp(r'"url"\s*:\s*"([^"]+)"').firstMatch(serverUrl);
        if (urlMatch != null) {
          serverUrl = urlMatch.group(1)!.replaceAll(r'\/', '/');
        }
      }
    } else {
      // URL directa sin JSON - no permitir para admin
      setState(() {
        _error = '⚠️ La app Admin requiere escanear el QR seguro.\n\nEncontrarás el QR de Admin (amarillo) en WordPress > Chat IA > Apps Móviles.';
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
          _error = 'QR no reconocido: $url\n\nDebe contener una URL válida';
        });
        return;
      }
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // Probar conexión
      final fullUrl = '$serverUrl/wp-json/chat-ia-mobile/v1';
      debugPrint('Conectando a: $fullUrl');

      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getBusinessInfo().timeout(
        const Duration(seconds: 15),
        onTimeout: () => ApiResponse<Map<String, dynamic>>.error('Timeout - el servidor tardó más de 15 segundos'),
      );

      debugPrint('Respuesta: success=${response.success}, error=${response.error}');

      if (response.success) {
        // Validar token de seguridad admin antes de guardar
        if (adminToken != null && adminToken.isNotEmpty) {
          debugPrint('Validando token de admin...');
          final tokenResponse = await testClient.validateAdminSiteToken(adminToken).timeout(
            const Duration(seconds: 10),
            onTimeout: () => ApiResponse<Map<String, dynamic>>.error('Timeout validando token'),
          );

          if (!tokenResponse.success) {
            setState(() {
              _isLoading = false;
              _error = '❌ Token de seguridad inválido.\n\nEl QR puede estar obsoleto. Genera uno nuevo desde WordPress > Chat IA > Apps Móviles.';
            });
            return;
          }
          debugPrint('Token de admin validado correctamente');
        }

        // Guardar configuración
        await ServerConfig.setServerUrl(serverUrl);
        await ServerConfig.setApiNamespace('/wp-json/chat-ia-mobile/v1');

        // IMPORTANTE: Actualizar la URL del apiClient existente
        ref.read(apiClientProvider).updateBaseUrl(fullUrl);
        debugPrint('ApiClient actualizado con URL: $fullUrl');

        // Sincronizar layouts y tema con el servidor
        debugPrint('[AdminApp] Sincronizando layouts y tema con servidor: $serverUrl');
        ref.read(syncProvider.notifier).syncWithSite(serverUrl);

        // Forzar recarga de providers
        ref.invalidate(serverConfiguredProvider);
        ref.invalidate(authStateProvider);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✓ Servidor configurado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _error = 'Error del servidor: ${response.error ?? "Respuesta inválida"}\n\nURL: $fullUrl';
        });
      }
    } catch (e, stack) {
      debugPrint('Error de conexión: $e');
      debugPrint('Stack: $stack');
      setState(() {
        _error = 'Error de conexión:\n$e\n\nURL intentada: $serverUrl';
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
                'Configurar Servidor',
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                'Conecta la app con tu servidor WordPress',
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
                  label: const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      'Escanear código QR',
                      style: TextStyle(fontSize: 16),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Encuentra el QR en WordPress:\nChat IA → Configuración → Apps Móviles',
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
                      'o ingresa la URL',
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
                  labelText: 'URL del servidor',
                  hintText: 'https://tu-sitio.com',
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
        title: const Text('Escanear QR'),
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
              child: const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Apunta al código QR',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Lo encontrarás en el panel de WordPress',
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
      await _storage.write(key: _usernameKey, value: _usernameController.text.trim());
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
          content: Text(ref.read(authStateProvider).error ?? 'Error de login'),
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

    return siteInfo.when(
      data: (info) {
        final siteName = info?['name'] as String?;
        return Text(
          siteName ?? AppConfig.adminAppName,
          style: Theme.of(context).textTheme.headlineMedium,
          textAlign: TextAlign.center,
        );
      },
      loading: () => Text(
        AppConfig.adminAppName,
        style: Theme.of(context).textTheme.headlineMedium,
        textAlign: TextAlign.center,
      ),
      error: (_, __) => Text(
        AppConfig.adminAppName,
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
            tooltip: 'Configurar servidor',
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
                    'Inicia sesión con tu cuenta de WordPress',
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
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
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
                              color: isConfigured ? Colors.green : Colors.orange,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    isConfigured ? 'Servidor configurado' : 'Servidor no configurado',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 12,
                                      color: isConfigured ? Colors.green : Colors.orange,
                                    ),
                                  ),
                                  if (isConfigured)
                                    Text(
                                      serverUrl,
                                      style: const TextStyle(fontSize: 11),
                                      overflow: TextOverflow.ellipsis,
                                    )
                                  else
                                    const Text(
                                      'Toca el icono ⚙️ arriba para configurar',
                                      style: TextStyle(fontSize: 11),
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
                          decoration: const InputDecoration(
                            labelText: 'Usuario',
                            prefixIcon: Icon(Icons.person),
                          ),
                          autofillHints: const [AutofillHints.username],
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Introduce tu usuario';
                            }
                            return null;
                          },
                          textInputAction: TextInputAction.next,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          decoration: InputDecoration(
                            labelText: 'Contraseña',
                            prefixIcon: const Icon(Icons.lock),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                              ),
                              onPressed: () {
                                setState(() => _obscurePassword = !_obscurePassword);
                              },
                            ),
                          ),
                          autofillHints: const [AutofillHints.password],
                          obscureText: _obscurePassword,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Introduce tu contraseña';
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
                          setState(() => _rememberCredentials = !_rememberCredentials);
                        },
                        child: const Text('Recordar credenciales'),
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
                        : const Text('Iniciar sesión'),
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
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: [
          // Dashboard
          DashboardScreen(
            onNavigateToReservations: () => _navigateToTab(1),
            onNavigateToChat: () => _navigateToTab(2),
          ),
          // Reservas
          const AdminReservationsScreen(),
          // Chat IA
          const AdminChatScreen(),
          // Configuración
          const AdminSettingsScreen(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() => _currentIndex = index);
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          NavigationDestination(
            icon: Icon(Icons.calendar_today_outlined),
            selectedIcon: Icon(Icons.calendar_today),
            label: 'Reservas',
          ),
          NavigationDestination(
            icon: Icon(Icons.smart_toy_outlined),
            selectedIcon: Icon(Icons.smart_toy),
            label: 'Chat IA',
          ),
          NavigationDestination(
            icon: Icon(Icons.settings_outlined),
            selectedIcon: Icon(Icons.settings),
            label: 'Ajustes',
          ),
        ],
      ),
    );
  }
}

/// Pantalla de ajustes
class AdminSettingsScreen extends ConsumerWidget {
  const AdminSettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authStateProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Ajustes'),
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
                  backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                  child: Icon(
                    Icons.person,
                    size: 40,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  user?['name'] ?? 'Usuario',
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
              'Configuración',
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
                'es': 'Espanol',
                'eu': 'Euskera',
                'en': 'English',
                'fr': 'Francais',
                'ca': 'Catala',
              };
              return ListTile(
                leading: const Icon(Icons.language),
                title: const Text('Idioma'),
                subtitle: Text(languageNames[language] ?? 'Espanol'),
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
            title: const Text('Notificaciones'),
            subtitle: const Text('Configurar alertas'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
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
            title: const Text('Servidor'),
            subtitle: FutureBuilder<String>(
              future: ServerConfig.getServerUrl(),
              builder: (context, snapshot) {
                return Text(snapshot.data ?? 'Cargando...');
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
          const Divider(),

          // App Cliente
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              'Aplicaciones',
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
            title: const Text('App de Cliente'),
            subtitle: const Text('Abrir app para clientes'),
            trailing: const Icon(Icons.open_in_new),
            onTap: () async {
              final success = await AppConfig.openClientApp();
              if (!success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('La app de cliente no está instalada'),
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
            title: const Text('Soporte y ayuda'),
            subtitle: const Text('Contactar con el equipo de desarrollo'),
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
            title: const Text('Version'),
            subtitle: Text(AppConfig.appVersion),
          ),
          ListTile(
            leading: const Icon(Icons.code),
            title: const Text('Desarrollado por'),
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
              'Cerrar sesión',
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            onTap: () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Cerrar sesión'),
                  content: const Text('¿Seguro que quieres cerrar sesión?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Cancelar'),
                    ),
                    FilledButton(
                      onPressed: () {
                        Navigator.pop(context);
                        ref.read(authStateProvider.notifier).logout();
                      },
                      style: FilledButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.error,
                      ),
                      child: const Text('Cerrar sesión'),
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
