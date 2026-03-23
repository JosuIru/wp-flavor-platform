import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';

/// Configuración de tema dinámico desde el servidor
class DynamicThemeConfig {
  final String id;
  final String name;
  final Color primaryColor;
  final Color secondaryColor;
  final Color accentColor;
  final Color backgroundColor;
  final Color surfaceColor;
  final Color errorColor;
  final Color onPrimary;
  final Color onSecondary;
  final Color onBackground;
  final Color onSurface;
  final Color onError;
  final Brightness brightness;
  final String? fontFamily;
  final double? borderRadius;
  final Map<String, dynamic> customColors;

  const DynamicThemeConfig({
    required this.id,
    required this.name,
    required this.primaryColor,
    required this.secondaryColor,
    required this.accentColor,
    required this.backgroundColor,
    required this.surfaceColor,
    required this.errorColor,
    required this.onPrimary,
    required this.onSecondary,
    required this.onBackground,
    required this.onSurface,
    required this.onError,
    this.brightness = Brightness.light,
    this.fontFamily,
    this.borderRadius,
    this.customColors = const {},
  });

  factory DynamicThemeConfig.fromJson(Map<String, dynamic> json) {
    return DynamicThemeConfig(
      id: json['id'] ?? 'default',
      name: json['name'] ?? 'Default Theme',
      primaryColor: _parseColor(json['primary_color'], Colors.blue),
      secondaryColor: _parseColor(json['secondary_color'], Colors.blueAccent),
      accentColor: _parseColor(json['accent_color'], Colors.amber),
      backgroundColor: _parseColor(json['background_color'], Colors.white),
      surfaceColor: _parseColor(json['surface_color'], Colors.white),
      errorColor: _parseColor(json['error_color'], Colors.red),
      onPrimary: _parseColor(json['on_primary'], Colors.white),
      onSecondary: _parseColor(json['on_secondary'], Colors.white),
      onBackground: _parseColor(json['on_background'], Colors.black87),
      onSurface: _parseColor(json['on_surface'], Colors.black87),
      onError: _parseColor(json['on_error'], Colors.white),
      brightness: json['brightness'] == 'dark' ? Brightness.dark : Brightness.light,
      fontFamily: json['font_family'],
      borderRadius: json['border_radius']?.toDouble(),
      customColors: Map<String, dynamic>.from(json['custom_colors'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'primary_color': _colorToHex(primaryColor),
    'secondary_color': _colorToHex(secondaryColor),
    'accent_color': _colorToHex(accentColor),
    'background_color': _colorToHex(backgroundColor),
    'surface_color': _colorToHex(surfaceColor),
    'error_color': _colorToHex(errorColor),
    'on_primary': _colorToHex(onPrimary),
    'on_secondary': _colorToHex(onSecondary),
    'on_background': _colorToHex(onBackground),
    'on_surface': _colorToHex(onSurface),
    'on_error': _colorToHex(onError),
    'brightness': brightness == Brightness.dark ? 'dark' : 'light',
    'font_family': fontFamily,
    'border_radius': borderRadius,
    'custom_colors': customColors,
  };

  static Color _parseColor(dynamic value, Color defaultColor) {
    if (value == null) return defaultColor;
    if (value is String) {
      final hex = value.replaceAll('#', '');
      if (hex.length == 6) {
        return Color(int.parse('FF$hex', radix: 16));
      } else if (hex.length == 8) {
        return Color(int.parse(hex, radix: 16));
      }
    }
    return defaultColor;
  }

  static String _colorToHex(Color color) {
    return '#${color.value.toRadixString(16).substring(2).toUpperCase()}';
  }

  /// Convertir a ThemeData de Flutter
  ThemeData toThemeData() {
    final colorScheme = ColorScheme(
      brightness: brightness,
      primary: primaryColor,
      onPrimary: onPrimary,
      secondary: secondaryColor,
      onSecondary: onSecondary,
      error: errorColor,
      onError: onError,
      surface: surfaceColor,
      onSurface: onSurface,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: backgroundColor,
      fontFamily: fontFamily,
      appBarTheme: AppBarTheme(
        backgroundColor: primaryColor,
        foregroundColor: onPrimary,
        elevation: 0,
      ),
      cardTheme: CardTheme(
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(borderRadius ?? 12),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: onPrimary,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(borderRadius ?? 8),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(borderRadius ?? 8),
        ),
        filled: true,
        fillColor: surfaceColor,
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: accentColor,
        foregroundColor: onPrimary,
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: surfaceColor,
        selectedItemColor: primaryColor,
        unselectedItemColor: onSurface.withOpacity(0.6),
      ),
    );
  }

  /// Obtener color personalizado por clave
  Color? getCustomColor(String key) {
    final value = customColors[key];
    if (value != null) {
      return _parseColor(value, Colors.transparent);
    }
    return null;
  }

  /// Crear copia con modificaciones
  DynamicThemeConfig copyWith({
    String? id,
    String? name,
    Color? primaryColor,
    Color? secondaryColor,
    Color? accentColor,
    Color? backgroundColor,
    Color? surfaceColor,
    Color? errorColor,
    Color? onPrimary,
    Color? onSecondary,
    Color? onBackground,
    Color? onSurface,
    Color? onError,
    Brightness? brightness,
    String? fontFamily,
    double? borderRadius,
    Map<String, dynamic>? customColors,
  }) {
    return DynamicThemeConfig(
      id: id ?? this.id,
      name: name ?? this.name,
      primaryColor: primaryColor ?? this.primaryColor,
      secondaryColor: secondaryColor ?? this.secondaryColor,
      accentColor: accentColor ?? this.accentColor,
      backgroundColor: backgroundColor ?? this.backgroundColor,
      surfaceColor: surfaceColor ?? this.surfaceColor,
      errorColor: errorColor ?? this.errorColor,
      onPrimary: onPrimary ?? this.onPrimary,
      onSecondary: onSecondary ?? this.onSecondary,
      onBackground: onBackground ?? this.onBackground,
      onSurface: onSurface ?? this.onSurface,
      onError: onError ?? this.onError,
      brightness: brightness ?? this.brightness,
      fontFamily: fontFamily ?? this.fontFamily,
      borderRadius: borderRadius ?? this.borderRadius,
      customColors: customColors ?? this.customColors,
    );
  }
}

/// Servicio de temas dinámicos
class DynamicThemeService extends ChangeNotifier {
  static DynamicThemeService? _instance;

  final ApiClient _apiClient;
  static const String _prefsKeyTheme = 'dynamic_theme';
  static const String _prefsKeyThemeId = 'selected_theme_id';

  DynamicThemeConfig? _currentTheme;
  List<DynamicThemeConfig> _availableThemes = [];
  bool _isLoading = false;

  DynamicThemeService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory DynamicThemeService({required ApiClient apiClient}) {
    _instance ??= DynamicThemeService._(apiClient: apiClient);
    return _instance!;
  }

  /// Tema actual
  DynamicThemeConfig? get currentTheme => _currentTheme;

  /// ThemeData actual
  ThemeData get themeData => _currentTheme?.toThemeData() ?? _defaultTheme;

  /// Temas disponibles
  List<DynamicThemeConfig> get availableThemes => List.unmodifiable(_availableThemes);

  /// Estado de carga
  bool get isLoading => _isLoading;

  /// Tema por defecto
  static final ThemeData _defaultTheme = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
  );

  /// Inicializar servicio
  Future<void> initialize() async {
    await _loadFromCache();
    await refresh();
  }

  /// Cargar tema desde cache
  Future<void> _loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final themeJson = prefs.getString(_prefsKeyTheme);

      if (themeJson != null) {
        final data = jsonDecode(themeJson);
        _currentTheme = DynamicThemeConfig.fromJson(data);
        debugPrint('[DynamicTheme] Cargado desde cache: ${_currentTheme?.name}');
        notifyListeners();
      }
    } catch (e) {
      debugPrint('[DynamicTheme] Error cargando cache: $e');
    }
  }

  /// Guardar tema en cache
  Future<void> _saveToCache() async {
    if (_currentTheme == null) return;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_prefsKeyTheme, jsonEncode(_currentTheme!.toJson()));
      await prefs.setString(_prefsKeyThemeId, _currentTheme!.id);
    } catch (e) {
      debugPrint('[DynamicTheme] Error guardando cache: $e');
    }
  }

  /// Refrescar temas desde servidor
  Future<bool> refresh() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiClient.get('/flavor-app/v2/themes');

      final responseData = response.data;
      if (responseData?['themes'] != null) {
        _availableThemes = (responseData?['themes'] as List)
            .map((t) => DynamicThemeConfig.fromJson(t))
            .toList();

        debugPrint('[DynamicTheme] ${_availableThemes.length} temas disponibles');
      }

      // Si hay un tema activo del servidor, usarlo
      if (responseData?['active_theme'] != null) {
        _currentTheme = DynamicThemeConfig.fromJson(responseData?['active_theme']);
        await _saveToCache();
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      debugPrint('[DynamicTheme] Error refrescando: $e');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// Seleccionar tema por ID
  Future<bool> selectTheme(String themeId) async {
    final theme = _availableThemes.firstWhere(
      (t) => t.id == themeId,
      orElse: () => _availableThemes.first,
    );

    _currentTheme = theme;
    await _saveToCache();
    notifyListeners();

    // Notificar al servidor
    try {
      await _apiClient.post('/flavor-app/v2/themes/select', data: {
        'theme_id': themeId,
        'user_id': AppConfig.userId,
      });
      return true;
    } catch (e) {
      debugPrint('[DynamicTheme] Error seleccionando tema: $e');
      return false;
    }
  }

  /// Aplicar tema personalizado
  void applyCustomTheme(DynamicThemeConfig theme) {
    _currentTheme = theme;
    _saveToCache();
    notifyListeners();
  }

  /// Cambiar brillo (claro/oscuro)
  void toggleBrightness() {
    if (_currentTheme == null) return;

    _currentTheme = _currentTheme!.copyWith(
      brightness: _currentTheme!.brightness == Brightness.light
          ? Brightness.dark
          : Brightness.light,
    );

    _saveToCache();
    notifyListeners();
  }

  /// Restablecer tema por defecto
  Future<void> resetToDefault() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyTheme);
    await prefs.remove(_prefsKeyThemeId);

    _currentTheme = null;
    notifyListeners();
  }
}

/// Widget para aplicar tema dinámico
class DynamicThemeBuilder extends StatelessWidget {
  final Widget Function(BuildContext context, ThemeData theme) builder;
  final DynamicThemeService themeService;

  const DynamicThemeBuilder({
    super.key,
    required this.builder,
    required this.themeService,
  });

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: themeService,
      builder: (context, _) {
        return builder(context, themeService.themeData);
      },
    );
  }
}

/// Extensión para acceder al servicio de tema desde el contexto
extension DynamicThemeExtension on BuildContext {
  DynamicThemeService get dynamicTheme {
    // En producción, usar Provider o GetIt
    throw UnimplementedError('Usar Provider.of<DynamicThemeService>(context)');
  }
}
