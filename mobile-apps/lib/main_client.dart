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
import 'features/client/client_dashboard_screen.dart';
import 'package:url_launcher/url_launcher.dart';
import 'features/setup/setup_screen.dart';
import 'features/client/camps/camps_screen.dart';
import 'features/layouts/layout_config.dart';
import 'features/modules/module_hub_screen.dart';
import 'features/modules/grupos_consumo/grupos_consumo_screen.dart';
import 'features/modules/banco_tiempo/banco_tiempo_screen.dart';
import 'features/modules/marketplace/marketplace_screen.dart';
import 'features/modules/eventos/eventos_screen.dart';
import 'features/modules/socios/socios_screen.dart';
import 'features/modules/facturas/facturas_screen.dart';
import 'features/modules/chat_grupos/chat_grupos_screen.dart';
import 'features/modules/chat_interno/chat_interno_screen.dart';
import 'core/modules/module_screen_registry.dart';
import 'core/modules/lazy/module_lazy_loader.dart';
import 'features/network/network_discovery_screen.dart';
import 'features/network/add_community_screen.dart';
import 'features/auth/login_screen.dart';

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
    final i18n = AppLocalizations.of(context)!;
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

/// Pantalla principal de la app cliente con configuración dinámica
class ClientHomeScreen extends ConsumerStatefulWidget {
  const ClientHomeScreen({super.key});

  @override
  ConsumerState<ClientHomeScreen> createState() => _ClientHomeScreenState();
}

class _ClientHomeScreenState extends ConsumerState<ClientHomeScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  int _currentIndex = 0;
  List<_TabConfig> _tabs = [];
  bool _configLoaded = false;
  String _navigationType = 'hybrid';
  bool _showAppBar = true;
  String? _lastLayoutSignature;

  @override
  void initState() {
    super.initState();
    // Programar carga después del primer frame para tener acceso a context
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadConfig();
    });
  }

  Future<void> _handleBusinessChanged() async {
    final serverUrl = await ServerConfig.getServerUrl();
    final apiUrl = await ServerConfig.getFullApiUrl();

    ref.read(apiClientProvider).updateBaseUrl(apiUrl);
    ref.invalidate(siteInfoProvider);
    ref.invalidate(clientAppConfigProvider);
    await _loadConfig();

    Logger.i(
      'Negocio cambiado: $serverUrl ($apiUrl)',
      tag: 'ClientHomeScreen',
    );
  }

  String _buildLayoutSignature(LayoutConfig config) {
    final tabsSig = config.clientTabs
        .map(
            (t) => '${t.id}:${t.enabled ? 1 : 0}:${t.order}:${t.type}:${t.url}')
        .join('|');
    final navSig = config.navigationItems
        .map((n) => '${n.title}:${n.url}:${n.order}')
        .join('|');
    return [
      config.menu.mobileBehavior,
      config.defaultTab,
      tabsSig,
      navSig,
    ].join('||');
  }

  Future<void> _loadConfig() async {
    // Primero intentar usar LayoutService si está cargado
    final layoutService = LayoutService();
    if (layoutService.isLoaded) {
      _lastLayoutSignature = _buildLayoutSignature(layoutService.config);
      _applyLayoutConfig(layoutService.config);
      return;
    }

    // Usar DynamicConfig si está disponible
    final dynamicConfig = DynamicConfig();

    // Si la configuración dinámica ya está cargada, usarla
    if (dynamicConfig.isLoaded) {
      _applyDynamicConfig(dynamicConfig);
      return;
    }

    // Intentar cargar desde el provider original como fallback
    final configAsync = ref.read(clientAppConfigProvider.future);
    final config = await configAsync;

    if (config != null && mounted) {
      final tabsData = config['tabs'] as List? ?? [];
      final defaultTab = config['default_tab'] as String? ?? 'info';
      final navigationType =
          (config['navigation_type'] as String? ?? 'hybrid').toLowerCase();
      final showAppBar = config['show_appbar'] as bool? ?? true;

      final tabs = <_TabConfig>[];
      for (final tab in tabsData) {
        if (tab['enabled'] == true) {
          tabs.add(_TabConfig.fromJson(tab as Map<String, dynamic>));
        }
      }

      // Ordenar por orden
      tabs.sort((a, b) => a.order.compareTo(b.order));

      // Encontrar índice de la pestaña por defecto
      int defaultIndex = tabs.indexWhere((t) => t.id == defaultTab);
      if (defaultIndex < 0) defaultIndex = tabs.length - 1;

      setState(() {
        _tabs = tabs;
        _currentIndex = defaultIndex;
        _navigationType = _normalizeNavigationType(navigationType);
        _showAppBar = showAppBar;
        _configLoaded = true;
      });
    } else if (mounted) {
      // Config por defecto si falla
      _applyDefaultConfig();
    }
  }

  /// Aplica la configuración desde LayoutService
  void _applyLayoutConfig(LayoutConfig layoutConfig) {
    if (!mounted) return;
    final i18n = AppLocalizations.of(context)!;

    // Usar clientTabs si están disponibles (compatibilidad con calendario)
    if (layoutConfig.clientTabs.isNotEmpty) {
      final tabs = <_TabConfig>[];
      for (final clientTab in layoutConfig.enabledTabs) {
        final label = clientTab.label.isNotEmpty
            ? clientTab.label
            : _fallbackTabLabel(i18n, clientTab.id, clientTab.type);
        tabs.add(_TabConfig(
          id: clientTab.id,
          label: label,
          icon: _iconFromString(clientTab.icon),
          type: clientTab.type,
          url: clientTab.url,
          order: clientTab.order,
        ));
      }

      if (tabs.isNotEmpty) {
        final navigationType = _navigationTypeFromLayout(layoutConfig);
        setState(() {
          _tabs = tabs;
          _currentIndex = layoutConfig.defaultTabIndex;
          _navigationType = navigationType;
          _showAppBar = layoutConfig.hybridShowAppBar;
          _configLoaded = true;
        });
        Logger.d('Layout config aplicado con ${tabs.length} clientTabs',
            tag: 'ClientHomeScreen');
        return;
      }
    }

    // Fallback: usar navigationItems si no hay clientTabs
    final navigationItems = layoutConfig.navigationItems;
    if (navigationItems.isNotEmpty) {
      final tabs = <_TabConfig>[];
      for (int i = 0; i < navigationItems.length && i < 5; i++) {
        final item = navigationItems[i];
        final tabId = _urlToTabId(item.url);
        if (tabId != null) {
          tabs.add(_TabConfig(
            id: tabId,
            label: item.title,
            icon: item.iconData,
            order: i,
          ));
        }
      }

      if (tabs.isNotEmpty) {
        final navigationType = _navigationTypeFromLayout(layoutConfig);
        setState(() {
          _tabs = tabs;
          _currentIndex = 0;
          _navigationType = navigationType;
          _showAppBar = layoutConfig.hybridShowAppBar;
          _configLoaded = true;
        });
        Logger.d('Layout config aplicado con ${tabs.length} navigationItems',
            tag: 'ClientHomeScreen');
        return;
      }
    }

    // Si no hay configuración válida, usar default
    _applyDefaultConfig();
  }

  /// Convierte URL a ID de tab conocido
  String? _urlToTabId(String url) {
    final lowerUrl = url.toLowerCase();
    if (lowerUrl.contains('chat')) return 'chat';
    if (lowerUrl.contains('reserv') || lowerUrl.contains('book'))
      return 'reservations';
    if (lowerUrl.contains('ticket') || lowerUrl.contains('mis-'))
      return 'my_tickets';
    if (lowerUrl.contains('info') || lowerUrl.contains('about')) return 'info';
    if (lowerUrl.contains('camp')) return 'camps';
    if (lowerUrl == '/' ||
        lowerUrl.contains('home') ||
        lowerUrl.contains('inicio')) return 'info';
    return null;
  }

  /// Convierte nombre de icono a IconData
  IconData _iconFromString(String iconName) {
    switch (iconName) {
      case 'chat_bubble':
        return Icons.chat_bubble;
      case 'calendar_today':
        return Icons.calendar_today;
      case 'confirmation_number':
        return Icons.confirmation_number;
      case 'info':
        return Icons.info;
      case 'home':
        return Icons.home;
      case 'settings':
        return Icons.settings;
      case 'person':
        return Icons.person;
      case 'qr_code':
        return Icons.qr_code;
      case 'search':
        return Icons.search;
      // Nuevos iconos para pestañas adicionales
      case 'local_activity':
        return Icons.local_activity;
      case 'cabin':
        return Icons.cabin;
      case 'forest':
        return Icons.forest;
      case 'nature_people':
        return Icons.nature_people;
      case 'event':
        return Icons.event;
      case 'star':
        return Icons.star;
      case 'favorite':
        return Icons.favorite;
      case 'shopping_cart':
        return Icons.shopping_cart;
      case 'store':
        return Icons.store;
      case 'public':
        return Icons.public;
      case 'link':
        return Icons.link;
      case 'groups':
      case 'groups_2':
        return Icons.groups;
      case 'handyman':
      case 'build':
        return Icons.handyman;
      default:
        return Icons.help;
    }
  }

  /// Aplica la configuración dinámica
  void _applyDynamicConfig(DynamicConfig config) {
    if (!mounted) return;

    final tabsEnabled = config.tabsEnabled;
    final tabsData = config.tabs;
    final defaultTab = config.defaultTab;
    final i18n = AppLocalizations.of(context)!;

    final tabs = <_TabConfig>[];
    for (final tab in tabsData) {
      final tabId = tab['id'] as String? ?? '';
      if (tabsEnabled.contains(tabId)) {
        final tabConfig = _TabConfig.fromJson(tab);
        final label = tabConfig.label.isNotEmpty
            ? tabConfig.label
            : _fallbackTabLabel(i18n, tabConfig.id, tabConfig.type);
        tabs.add(tabConfig.copyWith(label: label));
      }
    }

    // Ordenar por orden
    tabs.sort((a, b) => a.order.compareTo(b.order));

    // Encontrar índice de la pestaña por defecto
    int defaultIndex = tabs.indexWhere((t) => t.id == defaultTab);
    if (defaultIndex < 0) defaultIndex = tabs.length - 1;

    setState(() {
      _tabs = tabs;
      _currentIndex = defaultIndex;
      _configLoaded = true;
    });
  }

  /// Aplica configuración por defecto
  void _applyDefaultConfig() {
    if (!mounted) return;
    setState(() {
      _tabs = [
        _TabConfig(
            id: 'chat',
            label: i18n.tabChatLabel,
            icon: Icons.chat_bubble,
            order: 0),
        _TabConfig(
            id: 'reservations',
            label: i18n.tabReservationsLabel,
            icon: Icons.calendar_today,
            order: 1),
        _TabConfig(
            id: 'my_tickets',
            label: i18n.tabMyTicketsLabel,
            icon: Icons.confirmation_number,
            order: 2),
        _TabConfig(
            id: 'info', label: i18n.tabInfoLabel, icon: Icons.info, order: 3),
      ];
      _currentIndex = 3; // Info por defecto
      _navigationType = 'hybrid';
      _showAppBar = true;
      _configLoaded = true;
    });
  }

  String _normalizeNavigationType(String raw) {
    switch (raw) {
      case 'tabs_only':
      case 'drawer_only':
      case 'hybrid':
        return raw;
      default:
        return 'hybrid';
    }
  }

  String _navigationTypeFromLayout(LayoutConfig layoutConfig) {
    final style = (layoutConfig.navigationStyle).toLowerCase();
    switch (style) {
      case 'bottom':
        return 'tabs_only';
      case 'hamburger':
        return 'drawer_only';
      case 'hybrid':
      case 'auto':
      default:
        return 'hybrid';
    }
  }

  String _fallbackTabLabel(
      AppLocalizations i18n, String tabId, String tabType) {
    if (tabType == 'web') {
      return i18n.tabWebLabel;
    }
    switch (tabId) {
      case 'modules':
        return i18n.tabModulesLabel;
      case 'chat':
        return i18n.tabChatLabel;
      case 'reservations':
      case 'experiences':
        return i18n.tabReservationsLabel;
      case 'my_tickets':
        return i18n.tabMyTicketsLabel;
      case 'info':
        return i18n.tabInfoLabel;
      case 'camps':
        return i18n.campamentosB769f1;
      case 'grupos_consumo':
        return i18n.gruposConsumoTitle;
      case 'banco_tiempo':
        return i18n.bancoTiempoTitle;
      case 'marketplace':
        return i18n.marketplaceTitle;
      default:
        if (tabId.startsWith('web_')) {
          return i18n.tabWebLabel;
        }
        return tabId;
    }
  }

  /// Navegar a una pestaña específica por ID
  void _navigateToTab(int index) {
    if (index >= 0 && index < _tabs.length) {
      setState(() => _currentIndex = index);
    }
  }

  /// Navegar a pestaña por ID
  void _navigateToTabById(String tabId) {
    final index = _tabs.indexWhere((t) => t.id == tabId);
    if (index >= 0) {
      setState(() => _currentIndex = index);
    }
  }

  bool _hasTab(String id) {
    return _tabs.any((t) => t.id == id);
  }

  Future<void> _openExternalUrl(BuildContext context, String url) async {
    if (url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo abrir el enlace')),
      );
    }
  }

  Widget _buildScreen(_TabConfig tab) {
    // Primero intentar cargar desde el registro de módulos
    final loader = ModuleLazyLoader();

    // Intentar con el ID exacto
    var builder = loader.getScreenBuilder(tab.id);

    // Intentar con guiones convertidos a guiones bajos
    if (builder == null) {
      builder = loader.getScreenBuilder(tab.id.replaceAll('-', '_'));
    }

    // Intentar con guiones bajos convertidos a guiones
    if (builder == null) {
      builder = loader.getScreenBuilder(tab.id.replaceAll('_', '-'));
    }

    // Si encontramos un builder registrado, usarlo
    if (builder != null) {
      Logger.d('Usando pantalla registrada para: ${tab.id}',
          tag: 'ClientHomeScreen');
      return builder(context);
    }

    // Fallback a pantallas hardcodeadas básicas
    switch (tab.id) {
      case 'home':
      case 'inicio':
        return _hasTab('modules')
            ? const ModuleHubScreen()
            : InfoScreen(
                onNavigateToTab: _navigateToTab,
                onBusinessChanged: () {
                  _handleBusinessChanged();
                },
              );
      case 'perfil':
      case 'profile':
        return _hasTab('socios')
            ? const SociosScreen()
            : InfoScreen(
                onNavigateToTab: _navigateToTab,
                onBusinessChanged: () {
                  _handleBusinessChanged();
                },
              );
      case 'modules':
        return const ModuleHubScreen();
      case 'chat':
        return const ChatScreen();
      case 'reservations':
      case 'experiences': // Experiencias usa la misma pantalla de reservas
        return const ReservationsScreen();
      case 'my_tickets':
        return const MyReservationsScreen();
      case 'info':
        return InfoScreen(
          onNavigateToTab: _navigateToTab,
          onBusinessChanged: () {
            _handleBusinessChanged();
          },
        );
      case 'camps':
        return const CampsScreen();
      default:
        // Para pestañas desconocidas, mostrar placeholder
        Logger.w('No se encontró pantalla para: ${tab.id}',
            tag: 'ClientHomeScreen');
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.construction, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Text(
                i18n.clientUnknownSection(tab.id),
                style: TextStyle(color: Colors.grey[600], fontSize: 16),
              ),
              const SizedBox(height: 8),
              Text(
                i18n.clientComingSoon,
                style: TextStyle(color: Colors.grey[400], fontSize: 14),
              ),
            ],
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final authState = ref.watch(clientAuthProvider);

    // Escuchar cambios del layoutConfigProvider
    ref.listen<LayoutConfig>(layoutConfigProvider, (previous, next) {
      final signature = _buildLayoutSignature(next);
      if (signature == _lastLayoutSignature) {
        return;
      }
      _lastLayoutSignature = signature;
      _applyLayoutConfig(next);
    });

    // Escuchar cambios del syncProvider para recargar configuración
    ref.listen(syncProvider, (previous, next) {
      if (next.layoutConfig != null && previous?.layoutConfig == null) {
        // Se cargó una nueva configuración de layout
        _applyLayoutConfig(next.layoutConfig!);
      }
    });

    if (!_configLoaded) {
      return Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_tabs.isEmpty) {
      return Scaffold(
        body: Center(
          child: Text(i18n.clientNoTabs),
        ),
      );
    }

    // Obtener configuración de navegación del layout
    final layoutConfig = ref.watch(layoutConfigProvider);
    final drawerItems = layoutConfig.drawerItems;

    final maxBottomTabs = _tabs.length > 5 ? 5 : _tabs.length;
    final useBottomNav = _navigationType != 'drawer_only' && maxBottomTabs >= 2;

    // Dividir tabs en bottom (primeras 5) y drawer (si aplica)
    final bottomTabs =
        useBottomNav ? _tabs.take(maxBottomTabs).toList() : <_TabConfig>[];
    final bodyIndex =
        _currentIndex >= 0 && _currentIndex < _tabs.length ? _currentIndex : 0;
    final bottomSelectedIndex = bodyIndex < bottomTabs.length ? bodyIndex : 0;

    final showDrawer = _navigationType != 'tabs_only';

    return Scaffold(
      appBar: _showAppBar
          ? AppBar(
              title:
                  Text(ref.watch(syncProvider).siteName ?? i18n.defaultAppName),
            )
          : null,
      body: IndexedStack(
        index: bodyIndex,
        children: _tabs.map((tab) => _buildScreen(tab)).toList(),
      ),
      // Botón flotante para ir a la app admin (solo si es admin)
      floatingActionButton: authState.isAdmin
          ? FloatingActionButton.extended(
              onPressed: () async {
                final success = await AppConfig.openAdminApp();
                if (!success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(i18n.clientAdminAppNotInstalled),
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                }
              },
              icon: const Icon(Icons.admin_panel_settings),
              label: Text(i18n.commonAdmin),
              backgroundColor: Theme.of(context).colorScheme.secondary,
            )
          : null,
      bottomNavigationBar: useBottomNav && bottomTabs.length >= 2
          ? NavigationBar(
              selectedIndex: bottomSelectedIndex,
              onDestinationSelected: (index) {
                Haptics.selection();
                setState(() => _currentIndex = index);
              },
              destinations: bottomTabs
                  .map((tab) => NavigationDestination(
                        icon: Icon(tab.icon, fill: 0),
                        selectedIcon: Icon(tab.icon),
                        label: tab.label,
                      ))
                  .toList(),
            )
          : null,
      drawer: showDrawer ? _buildDrawer(context, drawerItems) : null,
    );
  }

  /// Construye el drawer cuando no se usa bottom navigation
  Widget _buildDrawer(BuildContext context, List<DrawerItem> drawerItems) {
    final syncState = ref.watch(syncProvider);
    final authState = ref.watch(clientAuthProvider);
    final theme = Theme.of(context);

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            // Header del drawer - muestra info de usuario si está logueado
            Container(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: authState.isAuthenticated
                        ? theme.colorScheme.primary
                        : theme.colorScheme.surfaceContainerHighest,
                    child: Icon(
                      authState.isAuthenticated
                          ? Icons.person
                          : Icons.person_outline,
                      color: authState.isAuthenticated
                          ? Colors.white
                          : theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          authState.isAuthenticated
                              ? (authState.user?['name'] ?? i18n.commonLogin)
                              : syncState.siteName ?? i18n.defaultAppName,
                          style: theme.textTheme.titleMedium,
                        ),
                        if (authState.isAuthenticated &&
                            authState.user?['email'] != null)
                          Text(
                            authState.user!['email'],
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Divider(),
            // Items de navegación
            Expanded(
              child: ListView.builder(
                itemCount:
                    drawerItems.isNotEmpty ? drawerItems.length : _tabs.length,
                itemBuilder: (context, index) {
                  if (drawerItems.isNotEmpty) {
                    final item = drawerItems[index];
                    return ListTile(
                      contentPadding: EdgeInsets.only(
                        left: 16 + (item.depth * 16).toDouble(),
                        right: 16,
                      ),
                      leading: Icon(_iconFromString(item.icon)),
                      title: Text(item.title),
                      onTap: () {
                        Navigator.pop(context);
                        if (item.type == 'native' && item.target.isNotEmpty) {
                          final tabIndex =
                              _tabs.indexWhere((t) => t.id == item.target);
                          if (tabIndex >= 0) {
                            setState(() => _currentIndex = tabIndex);
                            return;
                          }
                        }
                        if (item.url.isNotEmpty) {
                          _openExternalUrl(context, item.url);
                        }
                      },
                    );
                  } else {
                    final tab = _tabs[index];
                    return ListTile(
                      leading: Icon(tab.icon),
                      title: Text(tab.label),
                      selected: _currentIndex == index,
                      onTap: () {
                        Navigator.pop(context);
                        setState(() => _currentIndex = index);
                      },
                    );
                  }
                },
              ),
            ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.search),
              title: Text(i18n.directorioDf7b92),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const DirectoryScreen(),
                  ),
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.swap_horiz),
              title: Text(i18n.infoChangeBusinessTitle),
              subtitle: Text(i18n.infoConnectOtherSiteSubtitle),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ChangeBusinessScreen(
                      onComplete: () {
                        Navigator.pop(context);
                        _handleBusinessChanged();
                      },
                    ),
                  ),
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.explore),
              title: Text(i18n.networkExploreTitle),
              subtitle: Text(i18n.networkExploreSubtitle),
              onTap: () {
                Navigator.pop(context);
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const NetworkDiscoveryScreen(),
                  ),
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.add_home_work_outlined),
              title: Text(i18n.networkAddCommunityTitle),
              onTap: () async {
                Navigator.pop(context);
                final result = await Navigator.of(context).push<bool>(
                  MaterialPageRoute(
                    builder: (_) => const AddCommunityScreen(),
                  ),
                );
                if (result == true && context.mounted) {
                  // Si se añadió una comunidad, refrescar
                  ref.invalidate(clientAppConfigProvider);
                }
              },
            ),
            const Divider(),
            // Botón de Login/Logout
            if (authState.isAuthenticated)
              ListTile(
                leading: Icon(Icons.logout, color: theme.colorScheme.error),
                title: Text(
                  i18n.loginLogout,
                  style: TextStyle(color: theme.colorScheme.error),
                ),
                onTap: () async {
                  Navigator.pop(context);
                  final confirm = await showDialog<bool>(
                    context: context,
                    builder: (ctx) => AlertDialog(
                      title: Text(i18n.loginLogout),
                      content: Text(i18n.loginLogoutConfirm),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.of(ctx).pop(false),
                          child: Text(i18n.commonCancel),
                        ),
                        TextButton(
                          onPressed: () => Navigator.of(ctx).pop(true),
                          child: Text(i18n.loginLogout),
                        ),
                      ],
                    ),
                  );
                  if (confirm == true) {
                    await ref.read(clientAuthProvider.notifier).logout();
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(i18n.loginLogoutSuccess)),
                      );
                    }
                  }
                },
              )
            else
              ListTile(
                leading: Icon(Icons.login, color: theme.colorScheme.primary),
                title: Text(
                  i18n.commonLogin,
                  style: TextStyle(color: theme.colorScheme.primary),
                ),
                subtitle: Text(i18n.loginSubtitle),
                onTap: () async {
                  Navigator.pop(context);
                  final result = await Navigator.of(context).push<bool>(
                    MaterialPageRoute(
                      builder: (_) => LoginScreen(
                        onLoginSuccess: () {
                          ref.invalidate(clientAuthProvider);
                        },
                      ),
                    ),
                  );
                  if (result == true && context.mounted) {
                    // Refrescar el estado de autenticación
                    ref.invalidate(clientAuthProvider);
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text(i18n.loginSuccess)),
                    );
                  }
                },
              ),
          ],
        ),
      ),
    );
  }
}

/// Configuración de una pestaña
class _TabConfig {
  final String id;
  final String label;
  final IconData icon;
  final String type;
  final String? url;
  final int order;

  _TabConfig({
    required this.id,
    required this.label,
    required this.icon,
    this.type = 'native',
    this.url,
    required this.order,
  });

  _TabConfig copyWith({
    String? id,
    String? label,
    IconData? icon,
    String? type,
    String? url,
    int? order,
  }) {
    return _TabConfig(
      id: id ?? this.id,
      label: label ?? this.label,
      icon: icon ?? this.icon,
      type: type ?? this.type,
      url: url ?? this.url,
      order: order ?? this.order,
    );
  }

  factory _TabConfig.fromJson(Map<String, dynamic> json) {
    return _TabConfig(
      id: json['id'] as String? ?? '',
      label: json['label'] as String? ?? '',
      icon: _iconFromString(json['icon'] as String? ?? 'help'),
      // Soporta 'type' (Flutter) y 'content_type' (WordPress)
      type: json['type'] as String? ??
          json['content_type'] as String? ??
          'native',
      // Soporta 'url' (Flutter) y 'content_ref' (WordPress)
      url: json['url'] as String? ?? json['content_ref'] as String?,
      order: json['order'] as int? ?? 0,
    );
  }

  static IconData _iconFromString(String iconName) {
    switch (iconName) {
      case 'chat_bubble':
        return Icons.chat_bubble;
      case 'calendar_today':
        return Icons.calendar_today;
      case 'confirmation_number':
        return Icons.confirmation_number;
      case 'info':
        return Icons.info;
      case 'home':
        return Icons.home;
      case 'settings':
        return Icons.settings;
      case 'person':
        return Icons.person;
      case 'qr_code':
        return Icons.qr_code;
      case 'public':
        return Icons.public;
      case 'link':
        return Icons.link;
      case 'groups':
      case 'groups_2':
        return Icons.groups;
      case 'handyman':
      case 'build':
        return Icons.handyman;
      default:
        return Icons.help;
    }
  }
}
