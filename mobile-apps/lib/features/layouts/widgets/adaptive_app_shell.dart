/// Adaptive App Shell
///
/// Widget que adapta la estructura de la app según la configuración
/// del sitio WordPress sincronizado.
///
/// Usa el layout config para:
/// - Mostrar bottom navigation o hamburger menu
/// - Aplicar el tema del sitio
/// - Configurar los items de navegación

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../layouts/layout_config.dart';
import '../../../core/providers/sync_provider.dart';
import '../../../core/widgets/common_widgets.dart';

/// Shell adaptativo que cambia según la configuración del sitio
class AdaptiveAppShell extends ConsumerStatefulWidget {
  /// Páginas que se mostrarán en la navegación
  final List<Widget> pages;

  /// Índice inicial
  final int initialIndex;

  /// Callback cuando cambia la página
  final ValueChanged<int>? onPageChanged;

  /// Título de la app bar (opcional, se usa el del sitio si no se especifica)
  final String? title;

  /// Acciones adicionales para el app bar
  final List<Widget>? actions;

  /// Widget para el drawer (si el menú usa hamburger)
  final Widget? drawer;

  /// Floating action button
  final Widget? floatingActionButton;

  const AdaptiveAppShell({
    super.key,
    required this.pages,
    this.initialIndex = 0,
    this.onPageChanged,
    this.title,
    this.actions,
    this.drawer,
    this.floatingActionButton,
  });

  @override
  ConsumerState<AdaptiveAppShell> createState() => _AdaptiveAppShellState();
}

class _AdaptiveAppShellState extends ConsumerState<AdaptiveAppShell> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  late int _currentIndex;
  late PageController _pageController;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: widget.initialIndex);
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _onPageChanged(int index) {
    setState(() => _currentIndex = index);
    widget.onPageChanged?.call(index);
  }

  void _onNavItemTapped(int index) {
    _pageController.animateToPage(
      index,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final syncState = ref.watch(syncProvider);
    final layoutConfig = ref.watch(layoutConfigProvider);
    final useBottomNav = ref.watch(useBottomNavigationProvider);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        final shouldExit = await showExitConfirmation(context);
        if (shouldExit && context.mounted) {
          // Cerrar la aplicación
          SystemNavigator.pop();
        }
      },
      child: Scaffold(
        appBar: _buildAppBar(syncState, layoutConfig),
        drawer: useBottomNav ? null : _buildDrawer(layoutConfig),
        body: PageView(
          controller: _pageController,
          onPageChanged: _onPageChanged,
          physics: useBottomNav
              ? const NeverScrollableScrollPhysics()
              : const BouncingScrollPhysics(),
          children: widget.pages,
        ),
        bottomNavigationBar: useBottomNav
            ? _buildBottomNavigationBar(layoutConfig)
            : null,
        floatingActionButton: widget.floatingActionButton,
      ),
    );
  }

  PreferredSizeWidget? _buildAppBar(SyncState syncState, LayoutConfig config) {
    final title =
        widget.title ?? syncState.siteName ?? i18n.defaultAppName;
    final useBottomNav = config.menu.useBottomNavigation;

    return AppBar(
      title: Text(title),
      centerTitle: config.menu.type == MenuType.centered,
      elevation: config.menu.sticky ? 0 : 2,
      actions: [
        if (config.showSearch)
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () => _showSearch(context),
          ),
        ...?widget.actions,
        if (!useBottomNav)
          IconButton(
            icon: const Icon(Icons.menu),
            onPressed: () => Scaffold.of(context).openDrawer(),
          ),
      ],
    );
  }

  Widget? _buildDrawer(LayoutConfig config) {
    if (widget.drawer != null) return widget.drawer;

    // Drawer por defecto basado en navegación
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
                      ref.read(syncProvider).siteName ?? i18n.defaultAppName,
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
                itemCount: config.navigationItems.length,
                itemBuilder: (context, index) {
                  final item = config.navigationItems[index];
                  return ListTile(
                    leading: Icon(item.iconData),
                    title: Text(_localizeNavTitle(i18n, item.title)),
                    selected: _currentIndex == index,
                    onTap: () {
                      Navigator.pop(context);
                      _onNavItemTapped(index);
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

  String _localizeNavTitle(AppLocalizations i18n, String title) {
    switch (title.toLowerCase()) {
      case 'inicio':
      case 'home':
        return i18n.navHome;
      case 'explorar':
      case 'explore':
        return i18n.navExplore;
      case 'chat':
        return i18n.navChat;
      case 'perfil':
      case 'profile':
        return i18n.navProfile;
      default:
        return title;
    }
  }

  Widget _buildBottomNavigationBar(LayoutConfig config) {
    final items = config.navigationItems.take(5).toList();

    return BottomNavigationBar(
      currentIndex: _currentIndex.clamp(0, items.length - 1),
      onTap: _onNavItemTapped,
      type: BottomNavigationBarType.fixed,
      items: items.map((item) => BottomNavigationBarItem(
        icon: Icon(item.iconData),
        label: item.title,
      )).toList(),
    );
  }

  void _showSearch(BuildContext context) {
    showSearch(
      context: context,
      delegate: _AppSearchDelegate(),
    );
  }
}

/// Search delegate básico
class _AppSearchDelegate extends SearchDelegate<String> {
  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () => query = '',
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () => close(context, ''),
    );
  }

  @override
  Widget buildResults(BuildContext context) {
    return Center(
      child: Text(i18n.commonResultsFor(query)),
    );
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    return const Center(
      child: Text(i18n.escribeParaBuscar633b34),
    );
  }
}

/// Bottom navigation flotante (estilo iOS)
class FloatingBottomNavigation extends ConsumerWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;

  const FloatingBottomNavigation({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final layoutConfig = ref.watch(layoutConfigProvider);
    final items = layoutConfig.navigationItems.take(5).toList();
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 20,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: items.asMap().entries.map((entry) {
            final index = entry.key;
            final item = entry.value;
            final isSelected = index == currentIndex;

            return GestureDetector(
              onTap: () => onTap(index),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: EdgeInsets.symmetric(
                  horizontal: isSelected ? 16 : 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: isSelected
                      ? theme.colorScheme.primary.withOpacity(0.1)
                      : Colors.transparent,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      item.iconData,
                      color: isSelected
                          ? theme.colorScheme.primary
                          : theme.colorScheme.onSurface.withOpacity(0.6),
                      size: 24,
                    ),
                    if (isSelected) ...[
                      const SizedBox(width: 8),
                      Text(
                        item.title,
                        style: TextStyle(
                          color: theme.colorScheme.primary,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}
