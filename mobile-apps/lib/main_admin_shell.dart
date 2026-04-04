part of 'main_admin.dart';

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
    final tabs = <AdminTab>[
      AdminTab(
        id: 'dashboard',
        label: i18n.adminBottomNavDashboard,
        icon: Icons.dashboard_outlined,
        selectedIcon: Icons.dashboard,
        screen: DashboardScreenV2(
          onNavigateToReservations: () {},
          onNavigateToChat: () {},
        ),
      ),
    ];

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

    tabs.add(AdminTab(
      id: 'settings',
      label: i18n.adminBottomNavSettings,
      icon: Icons.settings_outlined,
      selectedIcon: Icons.settings,
      screen: const AdminSettingsScreen(),
    ));

    final bottomTabs = tabs.take(4).toList();
    final extraTabs = tabs.skip(4).toList();
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
      appBar: hasDrawer ? AppBar(title: Text(i18n.adminAppTitle)) : null,
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
          ListTile(
            leading: const Icon(Icons.settings_outlined),
            title: Text(i18n.adminBottomNavSettings),
            onTap: () {
              Navigator.pop(context);
              final settingsIndex =
                  widget.tabs.indexWhere((t) => t.id == 'settings');
              if (settingsIndex >= 0) {
                widget.onTabSelected(settingsIndex);
              } else {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const AdminSettingsScreen(),
                  ),
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
