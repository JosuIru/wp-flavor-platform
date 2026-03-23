/// Layout Configuration for Flavor Apps
///
/// Este archivo define las configuraciones de layouts que se sincronizan
/// con el plugin WordPress Flavor Chat IA.
///
/// Los layouts se cargan dinámicamente desde cualquier sitio WordPress
/// que tenga el plugin instalado, permitiendo que una única APK se
/// transforme en la app del sitio conectado.

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Tipos de menú disponibles
enum MenuType {
  classic,
  centered,
  sidebar,
  bottomNav,
  megaMenu,
  minimal,
  split,
  transparentHero,
  tabs,
  doubleHeader,
  searchFocused,
}

/// Tipos de footer disponibles
enum FooterType {
  multiColumn,
  compact,
  newsletter,
  contact,
  appDownload,
  wave,
  minimalDark,
  big,
  cta,
  social,
}

/// Configuración del tema importada desde WordPress
class LayoutTheme {
  final Color primaryColor;
  final Color secondaryColor;
  final Color accentColor;
  final Color backgroundColor;
  final Color textColor;
  final String fontFamily;

  const LayoutTheme({
    this.primaryColor = const Color(0xFF3B82F6),
    this.secondaryColor = const Color(0xFF8B5CF6),
    this.accentColor = const Color(0xFFF59E0B),
    this.backgroundColor = const Color(0xFFFFFFFF),
    this.textColor = const Color(0xFF1F2937),
    this.fontFamily = 'Inter',
  });

  factory LayoutTheme.fromJson(Map<String, dynamic> json) {
    return LayoutTheme(
      primaryColor: _parseColor(json['primary_color']),
      secondaryColor: _parseColor(json['secondary_color']),
      accentColor: _parseColor(json['accent_color']),
      backgroundColor: _parseColor(json['background_color']),
      textColor: _parseColor(json['text_color']),
      fontFamily: json['font_family'] ?? 'Inter',
    );
  }

  static Color _parseColor(String? hex) {
    if (hex == null || hex.isEmpty) return const Color(0xFF3B82F6);
    hex = hex.replaceFirst('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }

  Map<String, dynamic> toJson() => {
    'primary_color': '#${primaryColor.value.toRadixString(16).substring(2)}',
    'secondary_color': '#${secondaryColor.value.toRadixString(16).substring(2)}',
    'accent_color': '#${accentColor.value.toRadixString(16).substring(2)}',
    'background_color': '#${backgroundColor.value.toRadixString(16).substring(2)}',
    'text_color': '#${textColor.value.toRadixString(16).substring(2)}',
    'font_family': fontFamily,
  };

  ThemeData toThemeData({Brightness brightness = Brightness.light}) {
    final isDark = brightness == Brightness.dark;

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: ColorScheme(
        brightness: brightness,
        primary: primaryColor,
        onPrimary: _contrastColor(primaryColor),
        secondary: secondaryColor,
        onSecondary: _contrastColor(secondaryColor),
        tertiary: accentColor,
        onTertiary: _contrastColor(accentColor),
        error: const Color(0xFFEF4444),
        onError: Colors.white,
        surface: isDark ? const Color(0xFF1E1E1E) : backgroundColor,
        onSurface: isDark ? Colors.white : textColor,
      ),
      fontFamily: fontFamily,
      appBarTheme: AppBarTheme(
        backgroundColor: isDark ? const Color(0xFF1E1E1E) : primaryColor,
        foregroundColor: isDark ? Colors.white : _contrastColor(primaryColor),
        elevation: 0,
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: isDark ? const Color(0xFF1E1E1E) : backgroundColor,
        selectedItemColor: primaryColor,
        unselectedItemColor: isDark ? Colors.grey[400] : Colors.grey[600],
      ),
    );
  }

  Color _contrastColor(Color color) {
    final luminance = color.computeLuminance();
    return luminance > 0.5 ? Colors.black : Colors.white;
  }
}

/// Item de navegación para el menú
class NavigationItem {
  final int id;
  final String title;
  final String url;
  final String icon;
  final int? parentId;
  final int order;
  final List<NavigationItem> children;

  const NavigationItem({
    required this.id,
    required this.title,
    required this.url,
    this.icon = 'home',
    this.parentId,
    this.order = 0,
    this.children = const [],
  });

  factory NavigationItem.fromJson(Map<String, dynamic> json) {
    return NavigationItem(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      url: json['url'] ?? '',
      icon: json['icon'] ?? 'home',
      parentId: json['parent'] != null && json['parent'] != 0
          ? int.tryParse(json['parent'].toString())
          : null,
      order: json['order'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'title': title,
    'url': url,
    'icon': icon,
    'parent': parentId ?? 0,
    'order': order,
  };

  IconData get iconData {
    final iconMap = {
      'home': Icons.home_outlined,
      'admin-home': Icons.home_outlined,
      'search': Icons.search_outlined,
      'admin-users': Icons.person_outline,
      'email': Icons.email_outlined,
      'cart': Icons.shopping_cart_outlined,
      'marker': Icons.place_outlined,
      'calendar': Icons.calendar_today_outlined,
      'clock': Icons.access_time_outlined,
      'heart': Icons.favorite_outline,
      'star-filled': Icons.star_outline,
      'menu': Icons.menu,
      'plus-alt': Icons.add_circle_outline,
      'dashboard': Icons.dashboard_outlined,
      'admin-settings': Icons.settings_outlined,
      'visibility': Icons.visibility_outlined,
      'info': Icons.info_outline,
      'phone': Icons.phone_outlined,
      'location': Icons.location_on_outlined,
      'link': Icons.link_outlined,
      'chat': Icons.chat_bubble_outline,
      'shopping_basket': Icons.shopping_basket_outlined,
      'access_time': Icons.access_time_outlined,
      'account_balance': Icons.account_balance_outlined,
      'groups': Icons.groups_outlined,
      'meeting_room': Icons.meeting_room_outlined,
      'volunteer_activism': Icons.volunteer_activism_outlined,
      'grass': Icons.grass_outlined,
      'local_library': Icons.local_library_outlined,
      'school': Icons.school_outlined,
      'podcasts': Icons.podcasts_outlined,
      'radio': Icons.radio_outlined,
      'pedal_bike': Icons.pedal_bike_outlined,
      'recycling': Icons.recycling_outlined,
      'storefront': Icons.storefront_outlined,
      'report_problem': Icons.report_problem_outlined,
    };
    return iconMap[icon] ?? Icons.circle_outlined;
  }

  NavigationItem copyWith({List<NavigationItem>? children}) {
    return NavigationItem(
      id: id,
      title: title,
      url: url,
      icon: icon,
      parentId: parentId,
      order: order,
      children: children ?? this.children,
    );
  }
}

/// Configuración del menú
class MenuConfig {
  final String id;
  final String name;
  final MenuType type;
  final String mobileBehavior;
  final Map<String, dynamic> settings;

  const MenuConfig({
    this.id = 'bottomNav',
    this.name = 'Bottom Navigation',
    this.type = MenuType.bottomNav,
    this.mobileBehavior = 'bottom_tabs',  // Por defecto bottom tabs para móviles
    this.settings = const {},
  });

  factory MenuConfig.fromJson(Map<String, dynamic> json) {
    return MenuConfig(
      id: json['type'] ?? 'classic',
      name: json['name'] ?? 'Classic Menu',
      type: _parseMenuType(json['type']),
      mobileBehavior: json['mobile_behavior'] ?? 'hamburger',
      settings: json['config'] ?? {},
    );
  }

  static MenuType _parseMenuType(String? type) {
    switch (type) {
      case 'classic': return MenuType.classic;
      case 'centered': return MenuType.centered;
      case 'sidebar': return MenuType.sidebar;
      case 'bottom-nav': return MenuType.bottomNav;
      case 'mega-menu': return MenuType.megaMenu;
      case 'minimal': return MenuType.minimal;
      case 'split': return MenuType.split;
      case 'transparent-hero': return MenuType.transparentHero;
      case 'tabs': return MenuType.tabs;
      case 'double-header': return MenuType.doubleHeader;
      case 'search-focused': return MenuType.searchFocused;
      default: return MenuType.classic;
    }
  }

  Map<String, dynamic> toJson() => {
    'type': id,
    'name': name,
    'mobile_behavior': mobileBehavior,
    'config': settings,
  };

  bool get useBottomNavigation =>
      type == MenuType.bottomNav ||
      mobileBehavior == 'bottom-nav' ||
      mobileBehavior == 'bottom_tabs' ||
      (settings['use_bottom_navigation'] ?? false);

  bool get sticky => settings['sticky'] ?? true;
  bool get transparentOnHero => settings['transparent_on_hero'] ?? false;
  bool get showSearch => settings['show_search'] ?? true;
}

/// Configuración del footer
class FooterConfig {
  final String id;
  final String name;
  final FooterType type;
  final Map<String, dynamic> settings;

  const FooterConfig({
    this.id = 'compact',
    this.name = 'Compact Footer',
    this.type = FooterType.compact,
    this.settings = const {},
  });

  factory FooterConfig.fromJson(Map<String, dynamic> json) {
    return FooterConfig(
      id: json['type'] ?? 'compact',
      name: json['name'] ?? 'Compact Footer',
      type: _parseFooterType(json['type']),
      settings: json['config'] ?? {},
    );
  }

  static FooterType _parseFooterType(String? type) {
    switch (type) {
      case 'multi-column': return FooterType.multiColumn;
      case 'compact': return FooterType.compact;
      case 'newsletter': return FooterType.newsletter;
      case 'contact': return FooterType.contact;
      case 'app-download': return FooterType.appDownload;
      case 'wave': return FooterType.wave;
      case 'minimal-dark': return FooterType.minimalDark;
      case 'big': return FooterType.big;
      case 'cta': return FooterType.cta;
      case 'social': return FooterType.social;
      default: return FooterType.compact;
    }
  }

  Map<String, dynamic> toJson() => {
    'type': id,
    'name': name,
    'config': settings,
  };
}

/// Configuración de componentes extras
class ComponentsConfig {
  final bool darkModeEnabled;
  final bool darkModeAuto;
  final bool backToTopEnabled;
  final bool cookieBannerEnabled;
  final bool announcementBarEnabled;

  const ComponentsConfig({
    this.darkModeEnabled = false,
    this.darkModeAuto = true,
    this.backToTopEnabled = true,
    this.cookieBannerEnabled = false,
    this.announcementBarEnabled = false,
  });

  factory ComponentsConfig.fromJson(Map<String, dynamic> json) {
    final darkMode = json['dark_mode'] ?? {};
    return ComponentsConfig(
      darkModeEnabled: darkMode['enabled'] ?? false,
      darkModeAuto: darkMode['auto'] ?? true,
      backToTopEnabled: json['back_to_top'] ?? true,
      cookieBannerEnabled: json['cookie_banner'] ?? false,
      announcementBarEnabled: json['announcement_bar'] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
    'dark_mode': {
      'enabled': darkModeEnabled,
      'auto': darkModeAuto,
    },
    'back_to_top': backToTopEnabled,
    'cookie_banner': cookieBannerEnabled,
    'announcement_bar': announcementBarEnabled,
  };
}

/// Configuración de pestaña del cliente (compatible con calendario)
class ClientTab {
  final String id;
  final String label;
  final String icon;
  final String type;
  final String? url;
  final bool enabled;
  final int order;

  const ClientTab({
    required this.id,
    required this.label,
    this.icon = 'help',
    this.type = 'native',
    this.url,
    this.enabled = true,
    this.order = 0,
  });

  factory ClientTab.fromJson(Map<String, dynamic> json) {
    return ClientTab(
      id: json['id'] ?? '',
      label: json['label'] ?? '',
      icon: json['icon'] ?? 'help',
      // Soporta tanto 'type' como 'content_type' de WordPress
      type: json['type'] ?? json['content_type'] ?? 'native',
      // Soporta tanto 'url' como 'content_ref' de WordPress
      url: json['url'] ?? json['content_ref'],
      enabled: json['enabled'] ?? true,
      order: json['order'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'label': label,
    'icon': icon,
    'type': type,
    'url': url,
    'enabled': enabled,
    'order': order,
  };
}

/// Configuración de funcionalidades del cliente
class ClientFeatures {
  final bool chatEnabled;
  final bool reservationsEnabled;
  final bool myTicketsEnabled;
  final bool offlineTickets;
  final bool pushNotifications;
  final bool biometricAuth;

  const ClientFeatures({
    this.chatEnabled = true,
    this.reservationsEnabled = true,
    this.myTicketsEnabled = true,
    this.offlineTickets = true,
    this.pushNotifications = false,
    this.biometricAuth = false,
  });

  factory ClientFeatures.fromJson(Map<String, dynamic> json) {
    return ClientFeatures(
      chatEnabled: json['chat_enabled'] ?? true,
      reservationsEnabled: json['reservations_enabled'] ?? true,
      myTicketsEnabled: json['my_tickets_enabled'] ?? true,
      offlineTickets: json['offline_tickets'] ?? true,
      pushNotifications: json['push_notifications'] ?? false,
      biometricAuth: json['biometric_auth'] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
    'chat_enabled': chatEnabled,
    'reservations_enabled': reservationsEnabled,
    'my_tickets_enabled': myTicketsEnabled,
    'offline_tickets': offlineTickets,
    'push_notifications': pushNotifications,
    'biometric_auth': biometricAuth,
  };
}

/// Configuración completa de layout
class LayoutConfig {
  final String version;
  final bool available;
  final MenuConfig menu;
  final FooterConfig footer;
  final List<NavigationItem> navigationItems;
  final ComponentsConfig components;
  final Map<String, dynamic> settings;
  // Configuración de pestañas del cliente (compatible con calendario)
  final List<ClientTab> clientTabs;
  final String defaultTab;
  final ClientFeatures features;

  const LayoutConfig({
    this.version = '1.0',
    this.available = true,
    required this.menu,
    required this.footer,
    this.navigationItems = const [],
    this.components = const ComponentsConfig(),
    this.settings = const {},
    this.clientTabs = const [],
    this.defaultTab = 'info',
    this.features = const ClientFeatures(),
  });

  factory LayoutConfig.fromJson(Map<String, dynamic> json) {
    // Parse navigation items
    final navItemsJson = json['navigation'] as List<dynamic>? ?? [];
    final allNavItems = navItemsJson
        .map((item) => NavigationItem.fromJson(item as Map<String, dynamic>))
        .toList();

    // Organizar jerárquicamente
    final topLevelItems = allNavItems.where((item) => item.parentId == null).toList();
    final organizedItems = topLevelItems.map((parent) {
      final children = allNavItems.where((item) => item.parentId == parent.id).toList();
      return parent.copyWith(children: children);
    }).toList();

    // Ordenar por order
    organizedItems.sort((a, b) => a.order.compareTo(b.order));

    // Parse client tabs (soporta tanto 'client_tabs' como 'tabs' de WordPress)
    final clientTabsJson = json['client_tabs'] as List<dynamic>?
        ?? json['tabs'] as List<dynamic>?
        ?? [];
    final clientTabs = clientTabsJson
        .map((tab) => ClientTab.fromJson(tab as Map<String, dynamic>))
        .where((tab) => tab.enabled)
        .toList();
    clientTabs.sort((a, b) => a.order.compareTo(b.order));

    return LayoutConfig(
      version: json['version'] ?? '1.0',
      available: json['available'] ?? true,
      menu: MenuConfig.fromJson(json['menu'] ?? {}),
      footer: FooterConfig.fromJson(json['footer'] ?? {}),
      navigationItems: organizedItems,
      components: ComponentsConfig.fromJson(json['components'] ?? {}),
      settings: json['settings'] ?? {},
      clientTabs: clientTabs,
      defaultTab: json['default_tab'] ?? 'info',
      features: ClientFeatures.fromJson(json['features'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() => {
    'version': version,
    'available': available,
    'menu': menu.toJson(),
    'footer': footer.toJson(),
    'navigation': navigationItems.map((item) => item.toJson()).toList(),
    'components': components.toJson(),
    'settings': settings,
    'client_tabs': clientTabs.map((tab) => tab.toJson()).toList(),
    'default_tab': defaultTab,
    'features': features.toJson(),
  };

  factory LayoutConfig.defaults() {
    return LayoutConfig(
      menu: const MenuConfig(),
      footer: const FooterConfig(),
      navigationItems: const [
        NavigationItem(id: 1, title: 'Inicio', url: '/', icon: 'home', order: 0),
        NavigationItem(id: 2, title: 'Explorar', url: '/explorar', icon: 'search', order: 1),
        NavigationItem(id: 3, title: 'Chat', url: '/chat', icon: 'chat', order: 2),
        NavigationItem(id: 4, title: 'Perfil', url: '/perfil', icon: 'admin-users', order: 3),
      ],
      // Pestañas por defecto compatibles con calendario
      clientTabs: const [
        ClientTab(id: 'chat', label: 'Chat', icon: 'chat_bubble', order: 0),
        ClientTab(id: 'reservations', label: 'Reservar', icon: 'calendar_today', order: 1),
        ClientTab(id: 'my_tickets', label: 'Mis Tickets', icon: 'confirmation_number', order: 2),
        ClientTab(id: 'info', label: 'Info', icon: 'info', order: 3),
      ],
      defaultTab: 'info',
    );
  }

  bool get stickyHeader => settings['sticky_header'] ?? true;
  bool get showSearch => settings['show_search'] ?? true;
  bool get showCart => settings['show_cart'] ?? false;
  String get navigationStyle => settings['navigation_style'] ?? 'auto';
  bool get hybridShowAppBar => settings['hybrid_show_appbar'] ?? true;
  String get mapProvider => settings['map_provider'] ?? 'osm';
  String get googleMapsApiKey => settings['google_maps_api_key'] ?? '';
  List<DrawerItem> get drawerItems {
    final items = settings['drawer_items'];
    if (items is List) {
      final list = items
          .map((item) => DrawerItem.fromJson(item as Map<String, dynamic>))
          .toList();
      list.sort((a, b) => a.order.compareTo(b.order));
      return list;
    }
    return [];
  }

  /// Obtiene el índice de la pestaña por defecto
  int get defaultTabIndex {
    final index = clientTabs.indexWhere((tab) => tab.id == defaultTab);
    return index >= 0 ? index : (clientTabs.isNotEmpty ? clientTabs.length - 1 : 0);
  }

  /// Obtiene pestañas habilitadas
  List<ClientTab> get enabledTabs => clientTabs.where((tab) => tab.enabled).toList();
}

/// Item del menú hamburguesa (modo híbrido)
class DrawerItem {
  final String title;
  final String url;
  final String icon;
  final String type;
  final String target;
  final int order;
  final int depth;

  const DrawerItem({
    required this.title,
    required this.url,
    this.icon = 'public',
    this.type = 'web',
    this.target = '',
    this.order = 0,
    this.depth = 0,
  });

  factory DrawerItem.fromJson(Map<String, dynamic> json) {
    return DrawerItem(
      title: json['title'] ?? '',
      url: json['url'] ?? '',
      icon: json['icon'] ?? 'public',
      type: json['type'] ?? 'web',
      target: json['target'] ?? '',
      order: json['order'] ?? 0,
      depth: json['depth'] ?? 0,
    );
  }
}

/// Servicio para cargar y gestionar layouts
class LayoutService {
  // Singleton
  static final LayoutService _instance = LayoutService._internal();
  factory LayoutService() => _instance;
  LayoutService._internal();

  // Estado interno
  LayoutConfig? _cachedConfig;
  LayoutTheme? _cachedTheme;
  bool _isLoaded = false;
  DateTime? _lastLoadTime;

  // Cache keys
  static const String _layoutCacheKey = 'layout_config_cache';
  static const String _themeCacheKey = 'layout_theme_cache';
  static const String _cacheTimeKey = 'layout_cache_time';
  static const int _cacheDuration = 3600000; // 1 hora en ms

  /// Indica si la configuración ha sido cargada
  bool get isLoaded => _isLoaded;

  /// Obtiene la configuración actual
  LayoutConfig get config => _cachedConfig ?? LayoutConfig.defaults();

  /// Obtiene el tema actual
  LayoutTheme get theme => _cachedTheme ?? const LayoutTheme();

  /// Indica si el caché necesita actualizarse
  bool get needsRefresh {
    if (!_isLoaded || _lastLoadTime == null) return true;
    final elapsed = DateTime.now().difference(_lastLoadTime!).inMilliseconds;
    return elapsed >= _cacheDuration;
  }

  /// Carga la configuración desde el caché local
  Future<bool> loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedLayout = prefs.getString(_layoutCacheKey);
      final cachedTheme = prefs.getString(_themeCacheKey);
      final cacheTime = prefs.getInt(_cacheTimeKey) ?? 0;
      final now = DateTime.now().millisecondsSinceEpoch;

      // Si el caché tiene menos de 1 hora, usarlo
      if (cachedLayout != null && (now - cacheTime) < _cacheDuration) {
        _cachedConfig = LayoutConfig.fromJson(
          json.decode(cachedLayout) as Map<String, dynamic>,
        );

        if (cachedTheme != null) {
          _cachedTheme = LayoutTheme.fromJson(
            json.decode(cachedTheme) as Map<String, dynamic>,
          );
        }

        _isLoaded = true;
        _lastLoadTime = DateTime.fromMillisecondsSinceEpoch(cacheTime);
        debugPrint('LayoutService: Loaded from cache');
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('LayoutService: Error loading from cache: $e');
      return false;
    }
  }

  /// Actualiza la configuración con nuevos datos del servidor
  Future<void> updateConfig(Map<String, dynamic> layoutData, {Map<String, dynamic>? themeData}) async {
    try {
      _cachedConfig = LayoutConfig.fromJson(layoutData);

      if (themeData != null) {
        _cachedTheme = LayoutTheme.fromJson(themeData);
      }

      _isLoaded = true;
      _lastLoadTime = DateTime.now();

      // Guardar en caché
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_layoutCacheKey, json.encode(_cachedConfig!.toJson()));

      if (_cachedTheme != null) {
        await prefs.setString(_themeCacheKey, json.encode(_cachedTheme!.toJson()));
      }

      await prefs.setInt(_cacheTimeKey, DateTime.now().millisecondsSinceEpoch);

      debugPrint('LayoutService: Config updated and cached');
    } catch (e) {
      debugPrint('LayoutService: Error updating config: $e');
    }
  }

  /// Limpia el caché de configuración
  Future<void> clearCache() async {
    _cachedConfig = null;
    _cachedTheme = null;
    _isLoaded = false;
    _lastLoadTime = null;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_layoutCacheKey);
      await prefs.remove(_themeCacheKey);
      await prefs.remove(_cacheTimeKey);
      debugPrint('LayoutService: Cache cleared');
    } catch (e) {
      debugPrint('LayoutService: Error clearing cache: $e');
    }
  }

  /// Genera un ThemeData basado en la configuración
  ThemeData buildThemeData({Brightness brightness = Brightness.light}) {
    return theme.toThemeData(brightness: brightness);
  }

  /// Determina si usar Bottom Navigation
  bool get useBottomNavigation => config.menu.useBottomNavigation;

  /// Obtiene los items de navegación para bottom nav
  List<BottomNavigationBarItem> get bottomNavItems {
    final items = config.navigationItems.take(5).toList();
    return items.map((item) => BottomNavigationBarItem(
      icon: Icon(item.iconData),
      label: item.title,
    )).toList();
  }
}
