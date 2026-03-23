import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'app_config.dart';
import '../theme/app_colors.dart';

/// Configuración dinámica cargada desde el servidor WordPress
///
/// Esta clase gestiona toda la configuración personalizable de la app,
/// incluyendo colores, branding, secciones visibles y textos.
/// La configuración se cachea localmente y se actualiza periódicamente.
class DynamicConfig {
  // Singleton
  static final DynamicConfig _instance = DynamicConfig._internal();
  factory DynamicConfig() => _instance;
  DynamicConfig._internal();

  // Estado interno
  Map<String, dynamic>? _config;
  bool _isLoaded = false;
  DateTime? _lastLoadTime;

  // Cache keys
  static const String _cacheKey = 'dynamic_config_cache';
  static const String _cacheTimeKey = 'dynamic_config_cache_time';
  static const int _cacheDuration = 3600000; // 1 hora en milisegundos

  /// Indica si la configuración ha sido cargada
  bool get isLoaded => _isLoaded;

  /// Obtiene la configuración raw
  Map<String, dynamic>? get rawConfig => _config;

  // ==========================================
  // COLORES
  // ==========================================

  /// Color primario de la app
  Color get primaryColor => _parseColor(
        _config?['colors']?['primary'],
        Color(AppColors.primary.value),
      );

  /// Color secundario
  Color get secondaryColor => _parseColor(
        _config?['colors']?['secondary'],
        Color(AppColors.secondary.value),
      );

  /// Color de acento
  Color get accentColor => _parseColor(
        _config?['colors']?['accent'],
        Color(AppColors.secondary.value),
      );

  /// Color de fondo principal
  Color get backgroundColor => _parseColor(
        _config?['colors']?['background'],
        Color(AppColors.background.value),
      );

  /// Color de superficie (tarjetas, etc.)
  Color get surfaceColor => _parseColor(
        _config?['colors']?['surface'],
        Color(AppColors.surface.value),
      );

  /// Color de texto principal
  Color get textPrimaryColor => _parseColor(
        _config?['colors']?['text_primary'],
        const Color(0xFF212121),
      );

  /// Color de texto secundario
  Color get textSecondaryColor => _parseColor(
        _config?['colors']?['text_secondary'],
        const Color(0xFF757575),
      );

  /// Color de error
  Color get errorColor => _parseColor(
        _config?['colors']?['error'],
        Color(AppColors.error.value),
      );

  /// Color de éxito
  Color get successColor => _parseColor(
        _config?['colors']?['success'],
        Color(AppColors.onPrimary.value),
      );

  // ==========================================
  // BRANDING
  // ==========================================

  /// URL del logo (modo claro)
  String? get logoUrl => _config?['branding']?['logo_url'] as String?;

  /// URL del logo (modo oscuro)
  String? get logoDarkUrl => _config?['branding']?['logo_dark_url'] as String?;

  /// Nombre del negocio
  String get businessName =>
      _config?['branding']?['business_name'] as String? ?? '';

  /// Nombre de la app
  String get appName =>
      _config?['branding']?['app_name'] as String? ??
      _config?['branding']?['business_name'] as String? ??
      '';

  /// Mensaje de bienvenida (del branding)
  String get welcomeMessage =>
      _config?['branding']?['welcome_message'] as String? ?? '';

  // ==========================================
  // SECCIONES DE INFO
  // ==========================================

  /// Muestra información del negocio
  bool get showBusinessInfo =>
      _config?['info_sections']?['business_info'] as bool? ?? true;

  /// Muestra últimas noticias/posts
  bool get showLatestPosts =>
      _config?['info_sections']?['latest_posts'] as bool? ?? true;

  /// Muestra actualizaciones del sitio
  bool get showSiteUpdates =>
      _config?['info_sections']?['site_updates'] as bool? ?? true;

  /// Muestra información de contacto
  bool get showContact =>
      _config?['info_sections']?['contact'] as bool? ?? true;

  /// Muestra enlaces de redes sociales
  bool get showSocialLinks =>
      _config?['info_sections']?['social_links'] as bool? ?? true;

  /// Muestra servicios/experiencias
  bool get showServices =>
      _config?['info_sections']?['services'] as bool? ?? true;

  /// Muestra galería de imágenes
  bool get showGallery =>
      _config?['info_sections']?['gallery'] as bool? ?? true;

  /// Muestra horarios
  bool get showSchedule =>
      _config?['info_sections']?['schedule'] as bool? ?? true;

  /// Muestra ubicación
  bool get showLocation =>
      _config?['info_sections']?['location'] as bool? ?? true;

  // ==========================================
  // TEXTOS PERSONALIZADOS
  // ==========================================

  /// Texto de bienvenida (de texts)
  String get welcomeText =>
      _config?['texts']?['welcome'] as String? ?? '';

  /// Título de la sección Info
  String get infoTitle =>
      _config?['texts']?['info_title'] as String? ?? '';

  /// Placeholder del chat
  String get chatPlaceholder =>
      _config?['texts']?['chat_placeholder'] as String? ?? '';

  /// Título de reservas
  String get reservationsTitle =>
      _config?['texts']?['reservations_title'] as String? ?? '';

  /// Mensaje sin reservas
  String get noReservationsText =>
      _config?['texts']?['no_reservations'] as String? ?? '';

  /// Mensaje sin tickets
  String get noTicketsText =>
      _config?['texts']?['no_tickets'] as String? ?? '';

  // ==========================================
  // TABS / NAVEGACIÓN
  // ==========================================

  /// Orden de las pestañas
  List<String> get tabOrder {
    final order = _config?['tab_order'];
    if (order is List) {
      return List<String>.from(order);
    }
    return ['info', 'chat', 'reservations', 'my_tickets'];
  }

  /// Pestañas habilitadas
  List<String> get tabsEnabled {
    final enabled = _config?['tabs_enabled'];
    if (enabled is List) {
      return List<String>.from(enabled);
    }
    return ['info', 'chat', 'reservations', 'my_tickets'];
  }

  /// Pestaña por defecto
  String get defaultTab =>
      _config?['default_tab'] as String? ?? 'info';

  /// Configuración completa de tabs
  List<Map<String, dynamic>> get tabs {
    final tabsData = _config?['tabs'];
    if (tabsData is List) {
      return List<Map<String, dynamic>>.from(
        tabsData.map((t) => Map<String, dynamic>.from(t)),
      );
    }
    return [
      {'id': 'chat', 'label': '', 'icon': 'chat_bubble', 'enabled': true, 'order': 0},
      {'id': 'reservations', 'label': '', 'icon': 'calendar_today', 'enabled': true, 'order': 1},
      {'id': 'my_tickets', 'label': '', 'icon': 'confirmation_number', 'enabled': true, 'order': 2},
      {'id': 'info', 'label': '', 'icon': 'info', 'enabled': true, 'order': 3},
    ];
  }

  // ==========================================
  // FEATURES
  // ==========================================

  /// Chat habilitado
  bool get chatEnabled =>
      _config?['features']?['chat_enabled'] as bool? ?? true;

  /// Reservas habilitadas
  bool get reservationsEnabled =>
      _config?['features']?['reservations_enabled'] as bool? ?? true;

  /// Mis tickets habilitado
  bool get myTicketsEnabled =>
      _config?['features']?['my_tickets_enabled'] as bool? ?? true;

  /// Tickets offline habilitados
  bool get offlineTicketsEnabled =>
      _config?['features']?['offline_tickets'] as bool? ?? true;

  // ==========================================
  // MÉTODOS
  // ==========================================

  /// Carga la configuración desde el caché local
  Future<bool> loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedConfig = prefs.getString(_cacheKey);
      final cacheTime = prefs.getInt(_cacheTimeKey) ?? 0;
      final now = DateTime.now().millisecondsSinceEpoch;

      // Si el caché tiene menos de 1 hora, usarlo
      if (cachedConfig != null && (now - cacheTime) < _cacheDuration) {
        _config = json.decode(cachedConfig) as Map<String, dynamic>;
        _isLoaded = true;
        _lastLoadTime = DateTime.fromMillisecondsSinceEpoch(cacheTime);
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Error loading config from cache: $e');
      return false;
    }
  }

  /// Actualiza la configuración con nuevos datos del servidor
  Future<void> updateConfig(Map<String, dynamic> config) async {
    _config = config;
    _isLoaded = true;
    _lastLoadTime = DateTime.now();

    // Guardar en caché
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_cacheKey, json.encode(config));
      await prefs.setInt(_cacheTimeKey, DateTime.now().millisecondsSinceEpoch);
    } catch (e) {
      debugPrint('Error saving config to cache: $e');
    }
  }

  /// Limpia el caché de configuración
  Future<void> clearCache() async {
    _config = null;
    _isLoaded = false;
    _lastLoadTime = null;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_cacheKey);
      await prefs.remove(_cacheTimeKey);
    } catch (e) {
      debugPrint('Error clearing config cache: $e');
    }
  }

  /// Indica si el caché necesita actualizarse
  bool get needsRefresh {
    if (!_isLoaded || _lastLoadTime == null) return true;
    final elapsed = DateTime.now().difference(_lastLoadTime!).inMilliseconds;
    return elapsed >= _cacheDuration;
  }

  /// Convierte un color hexadecimal a Color de Flutter
  Color _parseColor(String? hex, Color fallback) {
    if (hex == null || hex.isEmpty) return fallback;

    try {
      // Eliminar # si existe
      String hexColor = hex.replaceAll('#', '');

      // Si tiene 6 caracteres, añadir FF para alpha
      if (hexColor.length == 6) {
        hexColor = 'FF$hexColor';
      }

      return Color(int.parse(hexColor, radix: 16));
    } catch (e) {
      return fallback;
    }
  }

  /// Obtiene el logo apropiado según el modo de tema
  String? getLogoForBrightness(Brightness brightness) {
    if (brightness == Brightness.dark && logoDarkUrl != null && logoDarkUrl!.isNotEmpty) {
      return logoDarkUrl;
    }
    return logoUrl;
  }

  /// Genera un ColorScheme dinámico basado en la configuración
  ColorScheme buildColorScheme({Brightness brightness = Brightness.light}) {
    return ColorScheme(
      brightness: brightness,
      primary: primaryColor,
      onPrimary: _contrastColor(primaryColor),
      secondary: secondaryColor,
      onSecondary: _contrastColor(secondaryColor),
      error: errorColor,
      onError: Colors.white,
      surface: brightness == Brightness.light ? surfaceColor : const Color(0xFF1E1E1E),
      onSurface: brightness == Brightness.light ? textPrimaryColor : Colors.white,
    );
  }

  /// Calcula el color de contraste (blanco o negro)
  Color _contrastColor(Color color) {
    // Usar luminancia para determinar si el texto debe ser blanco o negro
    final luminance = color.computeLuminance();
    return luminance > 0.5 ? Colors.black : Colors.white;
  }

  // ==========================================
  // CONFIGURACIÓN DE MÓDULOS
  // ==========================================

  /// Lista de módulos activos
  List<String> get activeModules {
    final modules = _config?['modules']?['active'];
    if (modules is List) {
      return List<String>.from(modules);
    }
    return [];
  }

  /// Lista de módulos deshabilitados
  List<String> get disabledModules {
    final modules = _config?['modules']?['disabled'];
    if (modules is List) {
      return List<String>.from(modules);
    }
    return [];
  }

  /// Orden de visualización de módulos
  List<String> get modulesOrder {
    final order = _config?['modules']?['order'];
    if (order is List) {
      return List<String>.from(order);
    }
    return [];
  }

  /// Configuración de módulos favoritos
  List<String> get favoriteModules {
    final favorites = _config?['modules']?['favorites'];
    if (favorites is List) {
      return List<String>.from(favorites);
    }
    return [];
  }

  /// Módulos visibles en el dashboard admin
  List<String> get adminDashboardModules {
    final modules = _config?['modules']?['admin_dashboard'];
    if (modules is List) {
      return List<String>.from(modules);
    }
    return activeModules; // Fallback a todos los activos
  }

  /// Configuración específica de un módulo
  Map<String, dynamic> getModuleConfig(String moduleId) {
    final modulesConfig = _config?['modules']?['config'];
    if (modulesConfig is Map) {
      final moduleConfig = modulesConfig[moduleId];
      if (moduleConfig is Map<String, dynamic>) {
        return moduleConfig;
      }
    }
    return {};
  }

  /// Verifica si un módulo está activo
  bool isModuleActive(String moduleId) {
    return activeModules.contains(moduleId) &&
           !disabledModules.contains(moduleId);
  }

  /// Permisos del usuario para módulos
  Map<String, List<String>> get modulePermissions {
    final permissions = _config?['modules']?['permissions'];
    if (permissions is Map) {
      return permissions.map(
        (key, value) => MapEntry(
          key.toString(),
          value is List ? List<String>.from(value) : <String>[],
        ),
      );
    }
    return {};
  }

  /// Verifica si el usuario tiene permiso para un módulo
  bool hasModulePermission(String moduleId, String permission) {
    final perms = modulePermissions[moduleId];
    if (perms == null) return true; // Sin restricciones = permitido
    return perms.contains(permission) || perms.contains('*');
  }

  @override
  String toString() {
    return 'DynamicConfig(isLoaded: $_isLoaded, businessName: $businessName, primaryColor: $primaryColor, activeModules: ${activeModules.length})';
  }
}
