import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'core/config/app_config.dart';
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
import 'features/setup/setup_screen.dart';
import 'features/client/camps/camps_screen.dart';
import 'features/layouts/layout_config.dart';

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
final clientAppConfigProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
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
    } catch (_) {}
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
      {'id': 'chat', 'label': 'Chat', 'icon': 'chat_bubble', 'enabled': true, 'order': 0},
      {'id': 'reservations', 'label': 'Reservar', 'icon': 'calendar_today', 'enabled': true, 'order': 1},
      {'id': 'my_tickets', 'label': 'Mis Tickets', 'icon': 'confirmation_number', 'enabled': true, 'order': 2},
      {'id': 'info', 'label': 'Info', 'icon': 'info', 'enabled': true, 'order': 3},
    ],
    'default_tab': 'info',
    'features': {
      'chat_enabled': true,
      'reservations_enabled': true,
      'my_tickets_enabled': true,
      'offline_tickets': true,
    },
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
    state = state.copyWith(isAuthenticated: false, isAdmin: false, isLoading: false);
  }

  Future<void> logout() async {
    await _api.logout();
    state = ClientAuthState(isAuthenticated: false, isAdmin: false, isLoading: false);
  }
}

final clientAuthProvider = StateNotifierProvider<ClientAuthNotifier, ClientAuthState>((ref) {
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

  debugPrint('Client app starting with API URL: $apiUrl');

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
final dynamicConfigProvider = StateNotifierProvider<DynamicConfigNotifier, DynamicConfigState>((ref) {
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
    debugPrint('[DynamicConfig] Cargando configuración del servidor...');

    try {
      final response = await _api.getClientAppConfig();
      debugPrint('[DynamicConfig] Respuesta: success=${response.success}, hasData=${response.data != null}');

      if (response.success && response.data != null) {
        debugPrint('[DynamicConfig] Data keys: ${response.data!.keys.toList()}');
        final configData = response.data!['config'] as Map<String, dynamic>?;

        if (configData != null) {
          debugPrint('[DynamicConfig] Config keys: ${configData.keys.toList()}');
          debugPrint('[DynamicConfig] Colors: ${configData['colors']}');
          debugPrint('[DynamicConfig] Branding: ${configData['branding']}');

          final config = DynamicConfig();
          await config.updateConfig(configData);

          debugPrint('[DynamicConfig] Config actualizado: isLoaded=${config.isLoaded}');
          debugPrint('[DynamicConfig] Primary color: ${config.primaryColor}');
          debugPrint('[DynamicConfig] Logo URL: ${config.logoUrl}');

          state = state.copyWith(
            isLoading: false,
            isLoaded: true,
            config: config,
          );
          return;
        } else {
          debugPrint('[DynamicConfig] ERROR: configData es null');
        }
      }

      debugPrint('[DynamicConfig] Error del servidor: ${response.error}');
      state = state.copyWith(
        isLoading: false,
        error: response.error ?? 'Error al cargar configuración',
      );
    } catch (e, stack) {
      debugPrint('[DynamicConfig] Excepción: $e');
      debugPrint('[DynamicConfig] Stack: $stack');
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
    final hasLayoutTheme = syncState.layoutConfig != null && syncState.theme != null;

    return MaterialApp(
      title: syncState.siteName ?? (config.isLoaded ? config.appName : AppConfig.clientAppName),
      debugShowCheckedModeBanner: AppConfig.isDebug,
      theme: hasLayoutTheme
          ? syncState.getThemeData(brightness: Brightness.light)
          : DynamicThemeBuilder.buildLightTheme(config.isLoaded ? config : null),
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
      debugPrint('[ClientApp] Sincronizando con servidor: $serverUrl');
      ref.read(syncProvider.notifier).syncWithSite(serverUrl);
    }

    setState(() {
      _isConfigured = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
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
  int _currentIndex = 0;
  List<_TabConfig> _tabs = [];
  bool _configLoaded = false;

  @override
  void initState() {
    super.initState();
    _loadConfig();
  }

  Future<void> _loadConfig() async {
    // Primero intentar usar LayoutService si está cargado
    final layoutService = LayoutService();
    if (layoutService.isLoaded) {
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

    // Usar clientTabs si están disponibles (compatibilidad con calendario)
    if (layoutConfig.clientTabs.isNotEmpty) {
      final tabs = <_TabConfig>[];
      for (final clientTab in layoutConfig.enabledTabs) {
        tabs.add(_TabConfig(
          id: clientTab.id,
          label: clientTab.label,
          icon: _iconFromString(clientTab.icon),
          order: clientTab.order,
        ));
      }

      if (tabs.isNotEmpty) {
        setState(() {
          _tabs = tabs;
          _currentIndex = layoutConfig.defaultTabIndex;
          _configLoaded = true;
        });
        debugPrint('[ClientHomeScreen] Layout config aplicado con ${tabs.length} clientTabs');
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
        setState(() {
          _tabs = tabs;
          _currentIndex = 0;
          _configLoaded = true;
        });
        debugPrint('[ClientHomeScreen] Layout config aplicado con ${tabs.length} navigationItems');
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
    if (lowerUrl.contains('reserv') || lowerUrl.contains('book')) return 'reservations';
    if (lowerUrl.contains('ticket') || lowerUrl.contains('mis-')) return 'my_tickets';
    if (lowerUrl.contains('info') || lowerUrl.contains('about')) return 'info';
    if (lowerUrl.contains('camp')) return 'camps';
    if (lowerUrl == '/' || lowerUrl.contains('home') || lowerUrl.contains('inicio')) return 'info';
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

    final tabs = <_TabConfig>[];
    for (final tab in tabsData) {
      final tabId = tab['id'] as String? ?? '';
      if (tabsEnabled.contains(tabId)) {
        tabs.add(_TabConfig.fromJson(tab));
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
        _TabConfig(id: 'chat', label: 'Chat', icon: Icons.chat_bubble, order: 0),
        _TabConfig(id: 'reservations', label: 'Reservar', icon: Icons.calendar_today, order: 1),
        _TabConfig(id: 'my_tickets', label: 'Mis Tickets', icon: Icons.confirmation_number, order: 2),
        _TabConfig(id: 'info', label: 'Info', icon: Icons.info, order: 3),
      ];
      _currentIndex = 3; // Info por defecto
      _configLoaded = true;
    });
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

  Widget _buildScreen(String tabId) {
    switch (tabId) {
      case 'chat':
        return const ChatScreen();
      case 'reservations':
      case 'experiences': // Experiencias usa la misma pantalla de reservas
        return const ReservationsScreen();
      case 'my_tickets':
        return const MyReservationsScreen();
      case 'info':
        return InfoScreen(onNavigateToTab: _navigateToTab);
      case 'camps':
        return const CampsScreen();
      default:
        // Para pestañas desconocidas, mostrar placeholder
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.construction, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Text(
                'Sección "$tabId"',
                style: TextStyle(color: Colors.grey[600], fontSize: 16),
              ),
              const SizedBox(height: 8),
              Text(
                'Próximamente',
                style: TextStyle(color: Colors.grey[400], fontSize: 14),
              ),
            ],
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(clientAuthProvider);

    // Escuchar cambios del syncProvider para recargar configuración
    ref.listen(syncProvider, (previous, next) {
      if (next.layoutConfig != null && previous?.layoutConfig == null) {
        // Se cargó una nueva configuración de layout
        _applyLayoutConfig(next.layoutConfig!);
      }
    });

    if (!_configLoaded) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_tabs.isEmpty) {
      return const Scaffold(
        body: Center(
          child: Text('No hay pestañas configuradas'),
        ),
      );
    }

    // Obtener configuración de navegación del layout
    final layoutConfig = ref.watch(layoutConfigProvider);
    final useBottomNav = layoutConfig.menu.useBottomNavigation;

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _tabs.map((tab) => _buildScreen(tab.id)).toList(),
      ),
      // Botón flotante para ir a la app admin (solo si es admin)
      floatingActionButton: authState.isAdmin
          ? FloatingActionButton.extended(
              onPressed: () async {
                final success = await AppConfig.openAdminApp();
                if (!success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('La app de administración no está instalada'),
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                }
              },
              icon: const Icon(Icons.admin_panel_settings),
              label: const Text('Admin'),
              backgroundColor: Theme.of(context).colorScheme.secondary,
            )
          : null,
      bottomNavigationBar: useBottomNav
          ? NavigationBar(
              selectedIndex: _currentIndex,
              onDestinationSelected: (index) {
                setState(() => _currentIndex = index);
              },
              destinations: _tabs.map((tab) => NavigationDestination(
                icon: Icon(tab.icon, fill: 0),
                selectedIcon: Icon(tab.icon),
                label: tab.label,
              )).toList(),
            )
          : null,
      drawer: !useBottomNav ? _buildDrawer(context) : null,
    );
  }

  /// Construye el drawer cuando no se usa bottom navigation
  Widget _buildDrawer(BuildContext context) {
    final syncState = ref.watch(syncProvider);

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            // Header del drawer
            Container(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    child: const Icon(Icons.person, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      syncState.siteName ?? 'Mi App',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                ],
              ),
            ),
            const Divider(),
            // Items de navegación
            Expanded(
              child: ListView.builder(
                itemCount: _tabs.length,
                itemBuilder: (context, index) {
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
                },
              ),
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
  final int order;

  _TabConfig({
    required this.id,
    required this.label,
    required this.icon,
    required this.order,
  });

  factory _TabConfig.fromJson(Map<String, dynamic> json) {
    return _TabConfig(
      id: json['id'] as String? ?? '',
      label: json['label'] as String? ?? '',
      icon: _iconFromString(json['icon'] as String? ?? 'help'),
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
      default:
        return Icons.help;
    }
  }
}
