import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'core/config/app_config.dart';
import 'core/utils/logger.dart';
import 'core/utils/haptics.dart';
import 'core/config/server_config.dart';
import 'core/config/dynamic_config.dart';
import 'core/api/api_client.dart';
import 'core/providers/providers.dart' show apiClientProvider;
import 'core/providers/sync_provider.dart';
import 'core/theme/dynamic_theme.dart';
import 'features/chat/chat_screen.dart';
import 'features/reservations/reservations_screen.dart';
import 'features/reservations/my_reservations_screen.dart';
import 'features/info/info_screen.dart';
import 'features/info/directory_screen.dart';
import 'package:url_launcher/url_launcher.dart';
import 'features/setup/setup_screen.dart';
import 'features/client/camps/camps_screen.dart';
import 'features/layouts/layout_config.dart';
import 'features/modules/module_hub_screen.dart';
import 'features/modules/socios/socios_screen.dart';
import 'core/modules/module_screen_registry.dart';
import 'core/modules/lazy/module_lazy_loader.dart';
import 'features/network/network_discovery_screen.dart';
import 'features/network/add_community_screen.dart';
import 'features/auth/login_screen.dart';

part 'main_client_home.dart';

/// Provider para información del sitio (logo, nombre, etc.)
final siteInfoProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getSiteInfo();
  if (response.success && response.data != null) {
    return response.data;
  }
  return null;
});

/// Provider para configuración de la app cliente (pestañas, features, etc.)
final clientAppConfigProvider =
    FutureProvider<Map<String, dynamic>?>((ref) async {
  final api = ref.read(apiClientProvider);

  // Intentar cargar desde caché primero
  final prefs = await SharedPreferences.getInstance();
  final cachedConfig = prefs.getString('client_app_config');
  final cacheTime = prefs.getInt('client_app_config_time') ?? 0;
  final now = DateTime.now().millisecondsSinceEpoch;

  // Si el caché tiene menos de 1 hora, usarlo
  if (cachedConfig != null && (now - cacheTime) < 3600000) {
    try {
      return json.decode(cachedConfig) as Map<String, dynamic>;
    } catch (e) {
      debugPrint('Error parseando config cacheada: $e');
    }
  }

  // Obtener del servidor
  final response = await api.getClientAppConfig();
  if (response.success && response.data != null) {
    final config = response.data!['config'] as Map<String, dynamic>?;
    if (config != null) {
      // Guardar en caché
      await prefs.setString('client_app_config', json.encode(config));
      await prefs.setInt('client_app_config_time', now);
      return config;
    }
  }

  // Si falla, devolver config por defecto
  return {
    'tabs': [
      {
        'id': 'chat',
        'label': 'Chat',
        'icon': 'chat_bubble',
        'enabled': true,
        'order': 0
      },
      {
        'id': 'reservations',
        'label': 'Reservar',
        'icon': 'calendar_today',
        'enabled': true,
        'order': 1
      },
      {
        'id': 'my_tickets',
        'label': 'Mis Tickets',
        'icon': 'confirmation_number',
        'enabled': true,
        'order': 2
      },
      {
        'id': 'info',
        'label': 'Info',
        'icon': 'info',
        'enabled': true,
        'order': 3
      },
    ],
    'default_tab': 'info',
    'features': {
      'chat_enabled': true,
      'reservations_enabled': true,
      'my_tickets_enabled': true,
      'offline_tickets': true,
    },
    'info_sections': [
      {
        'id': 'quick_links',
        'label': 'Enlaces Rápidos',
        'icon': 'link',
        'enabled': true,
        'order': 1
      },
      {
        'id': 'services',
        'label': 'Servicios',
        'icon': 'star',
        'enabled': true,
        'order': 2
      },
      {
        'id': 'content',
        'label': 'Contenido',
        'icon': 'article',
        'enabled': true,
        'order': 3
      },
      {
        'id': 'gallery',
        'label': 'Galería',
        'icon': 'photo_library',
        'enabled': true,
        'order': 4
      },
      {
        'id': 'news',
        'label': 'Noticias',
        'icon': 'newspaper',
        'enabled': true,
        'order': 5
      },
      {
        'id': 'schedule',
        'label': 'Horarios',
        'icon': 'schedule',
        'enabled': true,
        'order': 6
      },
      {
        'id': 'location',
        'label': 'Ubicación',
        'icon': 'location_on',
        'enabled': true,
        'order': 7
      },
      {
        'id': 'contact',
        'label': 'Contacto',
        'icon': 'phone',
        'enabled': true,
        'order': 8
      },
      {
        'id': 'social',
        'label': 'Redes Sociales',
        'icon': 'share',
        'enabled': true,
        'order': 9
      },
    ],
  };
});

/// Provider para el email del cliente (para billetera)
final clientEmailProvider = StateProvider<String?>((ref) => null);

/// Estado de autenticación del cliente (opcional)
class ClientAuthState {
  final bool isAuthenticated;
  final bool isAdmin;
  final bool isLoading;
  final Map<String, dynamic>? user;

  ClientAuthState({
    this.isAuthenticated = false,
    this.isAdmin = false,
    this.isLoading = true,
    this.user,
  });

  ClientAuthState copyWith({
    bool? isAuthenticated,
    bool? isAdmin,
    bool? isLoading,
    Map<String, dynamic>? user,
  }) {
    return ClientAuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isAdmin: isAdmin ?? this.isAdmin,
      isLoading: isLoading ?? this.isLoading,
      user: user ?? this.user,
    );
  }
}

/// Notifier de autenticación del cliente
class ClientAuthNotifier extends StateNotifier<ClientAuthState> {
  final ApiClient _api;

  ClientAuthNotifier(this._api) : super(ClientAuthState()) {
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final hasToken = await _api.hasToken();
    if (hasToken) {
      final response = await _api.verifyToken();
      if (response.success && response.data != null) {
        final user = response.data!['user'] as Map<String, dynamic>?;
        final isAdmin = user?['is_admin'] == true;
        state = state.copyWith(
          isAuthenticated: true,
          isAdmin: isAdmin,
          isLoading: false,
          user: user,
        );
        return;
      }
    }
    state = state.copyWith(
        isAuthenticated: false, isAdmin: false, isLoading: false);
  }

  Future<void> logout() async {
    await _api.logout();
    state = ClientAuthState(
        isAuthenticated: false, isAdmin: false, isLoading: false);
  }
}

final clientAuthProvider =
    StateNotifierProvider<ClientAuthNotifier, ClientAuthState>((ref) {
  return ClientAuthNotifier(ref.read(apiClientProvider));
});

/// Provider para el estado de configuración
final isConfiguredProvider = FutureProvider<bool>((ref) async {
  return await ServerConfig.isConfigured();
});

/// Punto de entrada para la app de CLIENTES
void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Cargar la URL del servidor antes de iniciar la app
  final serverUrl = await ServerConfig.getServerUrl();
  final apiUrl = serverUrl.isNotEmpty
      ? await ServerConfig.getFullApiUrl()
      : 'https://placeholder.local/wp-json/chat-ia-mobile/v1';

  Logger.i('Client app starting with API URL: $apiUrl', tag: 'ClientMain');

  // Registrar todas las pantallas de módulos
  final registry = ModuleScreenRegistry();
  registry.registerAllScreens();
  Logger.d('Pantallas de módulos registradas', tag: 'ClientMain');

  runApp(
    ProviderScope(
      overrides: [
        // Inicializar el apiClient con la URL guardada
        apiClientProvider.overrideWithValue(ApiClient(baseUrl: apiUrl)),
      ],
      child: const ChatIAClientApp(),
    ),
  );
}

/// Provider para la configuración dinámica
final dynamicConfigProvider =
    StateNotifierProvider<DynamicConfigNotifier, DynamicConfigState>((ref) {
  return DynamicConfigNotifier(ref.read(apiClientProvider));
});

/// Estado de la configuración dinámica
class DynamicConfigState {
  final bool isLoading;
  final bool isLoaded;
  final String? error;
  final DynamicConfig config;

  DynamicConfigState({
    this.isLoading = true,
    this.isLoaded = false,
    this.error,
    DynamicConfig? config,
  }) : config = config ?? DynamicConfig();

  DynamicConfigState copyWith({
    bool? isLoading,
    bool? isLoaded,
    String? error,
    DynamicConfig? config,
  }) {
    return DynamicConfigState(
      isLoading: isLoading ?? this.isLoading,
      isLoaded: isLoaded ?? this.isLoaded,
      error: error,
      config: config ?? this.config,
    );
  }
}

/// Notifier para gestionar la configuración dinámica
class DynamicConfigNotifier extends StateNotifier<DynamicConfigState> {
  final ApiClient _api;

  DynamicConfigNotifier(this._api) : super(DynamicConfigState()) {
    _loadConfig();
  }

  Future<void> _loadConfig() async {
    final config = DynamicConfig();

    // Intentar cargar desde caché primero
    final cachedLoaded = await config.loadFromCache();
    if (cachedLoaded) {
      state = state.copyWith(
        isLoading: false,
        isLoaded: true,
        config: config,
      );
    }

    // Cargar del servidor si es necesario
    if (!cachedLoaded || config.needsRefresh) {
      await refreshConfig();
    }
  }

  Future<void> refreshConfig() async {
    state = state.copyWith(isLoading: true);
    Logger.d('Cargando configuración del servidor...', tag: 'DynamicConfig');

    try {
      final response = await _api.getClientAppConfig();
      Logger.d(
          'Respuesta: success=${response.success}, hasData=${response.data != null}',
          tag: 'DynamicConfig');

      if (response.success && response.data != null) {
        Logger.d('Data keys: ${response.data!.keys.toList()}',
            tag: 'DynamicConfig');
        final configData = response.data!['config'] as Map<String, dynamic>?;

        if (configData != null) {
          Logger.d('Config keys: ${configData.keys.toList()}',
              tag: 'DynamicConfig');
          Logger.d('Colors: ${configData['colors']}', tag: 'DynamicConfig');
          Logger.d('Branding: ${configData['branding']}', tag: 'DynamicConfig');

          final config = DynamicConfig();
          await config.updateConfig(configData);

          Logger.d('Config actualizado: isLoaded=${config.isLoaded}',
              tag: 'DynamicConfig');
          Logger.d('Primary color: ${config.primaryColor}',
              tag: 'DynamicConfig');
          Logger.d('Logo URL: ${config.logoUrl}', tag: 'DynamicConfig');

          state = state.copyWith(
            isLoading: false,
            isLoaded: true,
            config: config,
          );
          return;
        } else {
          Logger.e('configData es null', tag: 'DynamicConfig');
        }
      }

      Logger.e('Error del servidor: ${response.error}', tag: 'DynamicConfig');
      state = state.copyWith(
        isLoading: false,
        error: response.error ?? 'Error al cargar configuración',
      );
    } catch (e, stack) {
      Logger.e('Excepción: $e', tag: 'DynamicConfig', error: e);
      Logger.e('Stack: $stack', tag: 'DynamicConfig');
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }
}

/// App de clientes para reservas
class ChatIAClientApp extends ConsumerStatefulWidget {
  const ChatIAClientApp({super.key});

  @override
  ConsumerState<ChatIAClientApp> createState() => _ChatIAClientAppState();
}

class _ChatIAClientAppState extends ConsumerState<ChatIAClientApp> {
  @override
  Widget build(BuildContext context) {
    final configState = ref.watch(dynamicConfigProvider);
    final config = configState.config;

    // Usar tema del sync si está disponible, sino del DynamicConfig
    final syncState = ref.watch(syncProvider);
    final hasLayoutTheme =
        syncState.layoutConfig != null && syncState.theme != null;

    return MaterialApp(
      title: syncState.siteName ??
          (config.isLoaded && config.appName.isNotEmpty
              ? config.appName
              : AppConfig.clientAppName),
      debugShowCheckedModeBanner: AppConfig.isDebug,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: hasLayoutTheme
          ? syncState.getThemeData(brightness: Brightness.light)
          : DynamicThemeBuilder.buildLightTheme(
              config.isLoaded ? config : null),
      darkTheme: hasLayoutTheme
          ? syncState.getThemeData(brightness: Brightness.dark)
          : DynamicThemeBuilder.buildDarkTheme(config.isLoaded ? config : null),
      themeMode: ThemeMode.system,
      home: const _AppStartup(),
    );
  }
}

/// Widget de inicio que verifica la configuración
class _AppStartup extends ConsumerStatefulWidget {
  const _AppStartup();

  @override
  ConsumerState<_AppStartup> createState() => _AppStartupState();
}

class _AppStartupState extends ConsumerState<_AppStartup> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  bool? _isConfigured;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _checkConfiguration();
  }

  Future<void> _checkConfiguration() async {
    final isConfigured = await ServerConfig.isConfigured();
    if (mounted) {
      setState(() {
        _isConfigured = isConfigured;
        _isLoading = false;
      });
    }
  }

  void _onSetupComplete() async {
    // Recargar la URL del servidor
    final serverUrl = await ServerConfig.getServerUrl();
    final apiUrl = await ServerConfig.getFullApiUrl();

    // Actualizar el provider del API client
    ref.read(apiClientProvider).updateBaseUrl(apiUrl);

    // Refrescar los providers que dependen del API
    ref.invalidate(siteInfoProvider);

    // Sincronizar layouts y tema con el nuevo servidor
    if (serverUrl.isNotEmpty) {
      Logger.i('Sincronizando con servidor: $serverUrl', tag: 'ClientApp');
      final syncResult =
          await ref.read(syncProvider.notifier).syncWithSite(serverUrl);
      if (!syncResult.success) {
        Logger.w(
          'Sincronizacion inicial incompleta: ${syncResult.error}',
          tag: 'ClientApp',
        );
      }
    }

    setState(() {
      _isConfigured = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_isConfigured != true) {
      return SetupScreen(onSetupComplete: _onSetupComplete);
    }

    return const ClientHomeScreen();
  }
}
