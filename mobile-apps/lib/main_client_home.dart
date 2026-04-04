part of 'main_client.dart';

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
    final layoutService = LayoutService();
    if (layoutService.isLoaded) {
      _lastLayoutSignature = _buildLayoutSignature(layoutService.config);
      _applyLayoutConfig(layoutService.config);
      return;
    }

    final dynamicConfig = DynamicConfig();
    if (dynamicConfig.isLoaded) {
      _applyDynamicConfig(dynamicConfig);
      return;
    }

    final config = await ref.read(clientAppConfigProvider.future);

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

      tabs.sort((a, b) => a.order.compareTo(b.order));

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
      _applyDefaultConfig();
    }
  }

  void _applyLayoutConfig(LayoutConfig layoutConfig) {
    if (!mounted) return;
    final i18n = AppLocalizations.of(context)!;

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

    _applyDefaultConfig();
  }

  String? _urlToTabId(String url) {
    final lowerUrl = url.toLowerCase();
    if (lowerUrl.contains('chat')) return 'chat';
    if (lowerUrl.contains('reserv') || lowerUrl.contains('book')) {
      return 'reservations';
    }
    if (lowerUrl.contains('ticket') || lowerUrl.contains('mis-')) {
      return 'my_tickets';
    }
    if (lowerUrl.contains('info') || lowerUrl.contains('about')) return 'info';
    if (lowerUrl.contains('camp')) return 'camps';
    if (lowerUrl == '/' ||
        lowerUrl.contains('home') ||
        lowerUrl.contains('inicio')) {
      return 'info';
    }
    return null;
  }

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

    tabs.sort((a, b) => a.order.compareTo(b.order));

    int defaultIndex = tabs.indexWhere((t) => t.id == defaultTab);
    if (defaultIndex < 0) defaultIndex = tabs.length - 1;

    setState(() {
      _tabs = tabs;
      _currentIndex = defaultIndex;
      _configLoaded = true;
    });
  }

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
      _currentIndex = 3;
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
    final style = layoutConfig.navigationStyle.toLowerCase();
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

  void _navigateToTab(int index) {
    if (index >= 0 && index < _tabs.length) {
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
    final loader = ModuleLazyLoader();
    var builder = loader.getScreenBuilder(tab.id);

    if (builder == null) {
      builder = loader.getScreenBuilder(tab.id.replaceAll('-', '_'));
    }
    if (builder == null) {
      builder = loader.getScreenBuilder(tab.id.replaceAll('_', '-'));
    }

    if (builder != null) {
      Logger.d('Usando pantalla registrada para: ${tab.id}',
          tag: 'ClientHomeScreen');
      return builder(context);
    }

    switch (tab.id) {
      case 'home':
      case 'inicio':
        return _hasTab('modules')
            ? const ModuleHubScreen()
            : InfoScreen(
                onNavigateToTab: _navigateToTab,
                onBusinessChanged: _handleBusinessChanged,
              );
      case 'perfil':
      case 'profile':
        return _hasTab('socios')
            ? const SociosScreen()
            : InfoScreen(
                onNavigateToTab: _navigateToTab,
                onBusinessChanged: _handleBusinessChanged,
              );
      case 'modules':
        return const ModuleHubScreen();
      case 'chat':
        return const ChatScreen();
      case 'reservations':
      case 'experiences':
        return const ReservationsScreen();
      case 'my_tickets':
        return const MyReservationsScreen();
      case 'info':
        return InfoScreen(
          onNavigateToTab: _navigateToTab,
          onBusinessChanged: _handleBusinessChanged,
        );
      case 'camps':
        return const CampsScreen();
      default:
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

    ref.listen<LayoutConfig>(layoutConfigProvider, (previous, next) {
      final signature = _buildLayoutSignature(next);
      if (signature == _lastLayoutSignature) {
        return;
      }
      _lastLayoutSignature = signature;
      _applyLayoutConfig(next);
    });

    ref.listen(syncProvider, (previous, next) {
      if (next.layoutConfig != null && previous?.layoutConfig == null) {
        _applyLayoutConfig(next.layoutConfig!);
      }
    });

    if (!_configLoaded) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_tabs.isEmpty) {
      return Scaffold(
        body: Center(
          child: Text(i18n.clientNoTabs),
        ),
      );
    }

    final layoutConfig = ref.watch(layoutConfigProvider);
    final drawerItems = layoutConfig.drawerItems;
    final maxBottomTabs = _tabs.length > 5 ? 5 : _tabs.length;
    final useBottomNav = _navigationType != 'drawer_only' && maxBottomTabs >= 2;
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

  Widget _buildDrawer(BuildContext context, List<DrawerItem> drawerItems) {
    final syncState = ref.watch(syncProvider);
    final authState = ref.watch(clientAuthProvider);
    final theme = Theme.of(context);

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
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
                  }

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
                  ref.invalidate(clientAppConfigProvider);
                }
              },
            ),
            const Divider(),
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
      type: json['type'] as String? ??
          json['content_type'] as String? ??
          'native',
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
