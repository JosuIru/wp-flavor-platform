import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/semantics.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Servicio para gestionar accesibilidad y soporte RTL
class AccessibilityService extends ChangeNotifier {
  static AccessibilityService? _instance;

  static const String _prefKeyFontScale = 'a11y_font_scale';
  static const String _prefKeyHighContrast = 'a11y_high_contrast';
  static const String _prefKeyReduceMotion = 'a11y_reduce_motion';
  static const String _prefKeyBoldText = 'a11y_bold_text';
  static const String _prefKeyForceLTR = 'a11y_force_ltr';

  // Estado de accesibilidad
  double _fontScale = 1.0;
  bool _highContrast = false;
  bool _reduceMotion = false;
  bool _boldText = false;
  bool _forceLTR = false;
  bool _isRTL = false;

  // Configuración del sistema
  bool _systemHighContrast = false;
  bool _systemReduceMotion = false;
  bool _systemBoldText = false;

  AccessibilityService._();

  factory AccessibilityService() {
    _instance ??= AccessibilityService._();
    return _instance!;
  }

  // Getters
  double get fontScale => _fontScale;
  bool get highContrast => _highContrast || _systemHighContrast;
  bool get reduceMotion => _reduceMotion || _systemReduceMotion;
  bool get boldText => _boldText || _systemBoldText;
  bool get forceLTR => _forceLTR;
  bool get isRTL => _isRTL && !_forceLTR;

  /// Inicializar servicio
  Future<void> initialize() async {
    await _loadPreferences();
    _detectSystemSettings();
    _detectTextDirection();
    notifyListeners();
    debugPrint('[Accessibility] Servicio inicializado: RTL=$_isRTL, fontScale=$_fontScale');
  }

  /// Cargar preferencias guardadas
  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _fontScale = prefs.getDouble(_prefKeyFontScale) ?? 1.0;
    _highContrast = prefs.getBool(_prefKeyHighContrast) ?? false;
    _reduceMotion = prefs.getBool(_prefKeyReduceMotion) ?? false;
    _boldText = prefs.getBool(_prefKeyBoldText) ?? false;
    _forceLTR = prefs.getBool(_prefKeyForceLTR) ?? false;
  }

  /// Detectar configuraciones del sistema
  void _detectSystemSettings() {
    final window = ui.PlatformDispatcher.instance;

    // Alto contraste del sistema
    _systemHighContrast = window.accessibilityFeatures.highContrast;

    // Reducir movimiento del sistema
    _systemReduceMotion = window.accessibilityFeatures.reduceMotion;

    // Texto en negrita del sistema
    _systemBoldText = window.accessibilityFeatures.boldText;
  }

  /// Detectar dirección del texto según locale
  void _detectTextDirection() {
    final locale = ui.PlatformDispatcher.instance.locale;
    _isRTL = _isRTLLocale(locale.languageCode);
  }

  /// Verificar si un idioma es RTL
  bool _isRTLLocale(String languageCode) {
    const rtlLanguages = ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'yi'];
    return rtlLanguages.contains(languageCode);
  }

  /// Actualizar dirección del texto para un locale específico
  void updateTextDirection(String languageCode) {
    _isRTL = _isRTLLocale(languageCode);
    notifyListeners();
  }

  /// Establecer escala de fuente
  Future<void> setFontScale(double scale) async {
    if (scale < 0.8 || scale > 2.0) return;

    _fontScale = scale;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setDouble(_prefKeyFontScale, scale);
    notifyListeners();
  }

  /// Establecer alto contraste
  Future<void> setHighContrast(bool enabled) async {
    _highContrast = enabled;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefKeyHighContrast, enabled);
    notifyListeners();
  }

  /// Establecer reducir movimiento
  Future<void> setReduceMotion(bool enabled) async {
    _reduceMotion = enabled;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefKeyReduceMotion, enabled);
    notifyListeners();
  }

  /// Establecer texto en negrita
  Future<void> setBoldText(bool enabled) async {
    _boldText = enabled;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefKeyBoldText, enabled);
    notifyListeners();
  }

  /// Forzar dirección LTR
  Future<void> setForceLTR(bool enabled) async {
    _forceLTR = enabled;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefKeyForceLTR, enabled);
    notifyListeners();
  }

  /// Restablecer a valores por defecto
  Future<void> resetToDefaults() async {
    _fontScale = 1.0;
    _highContrast = false;
    _reduceMotion = false;
    _boldText = false;
    _forceLTR = false;

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefKeyFontScale);
    await prefs.remove(_prefKeyHighContrast);
    await prefs.remove(_prefKeyReduceMotion);
    await prefs.remove(_prefKeyBoldText);
    await prefs.remove(_prefKeyForceLTR);

    notifyListeners();
  }

  /// Obtener TextDirection actual
  TextDirection get textDirection =>
      isRTL ? TextDirection.rtl : TextDirection.ltr;

  /// Obtener duración de animación ajustada
  Duration getAnimationDuration(Duration baseDuration) {
    if (reduceMotion) {
      return Duration.zero;
    }
    return baseDuration;
  }

  /// Obtener curva de animación ajustada
  Curve getAnimationCurve(Curve baseCurve) {
    if (reduceMotion) {
      return Curves.linear;
    }
    return baseCurve;
  }
}

/// Widget para aplicar configuración de accesibilidad
class AccessibilityWrapper extends StatelessWidget {
  final Widget child;
  final AccessibilityService service;

  const AccessibilityWrapper({
    super.key,
    required this.child,
    required this.service,
  });

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: service,
      builder: (context, _) {
        return Directionality(
          textDirection: service.textDirection,
          child: MediaQuery(
            data: MediaQuery.of(context).copyWith(
              textScaler: TextScaler.linear(service.fontScale),
              boldText: service.boldText,
              highContrast: service.highContrast,
              disableAnimations: service.reduceMotion,
            ),
            child: child,
          ),
        );
      },
    );
  }
}

/// Extensión para widgets con soporte RTL
extension RTLWidget on Widget {
  /// Aplicar transformación para RTL
  Widget withRTLSupport(BuildContext context) {
    final isRTL = Directionality.of(context) == TextDirection.rtl;
    if (!isRTL) return this;

    return Transform.scale(
      scaleX: -1,
      child: this,
    );
  }

  /// Aplicar padding con soporte RTL
  Widget withRTLPadding(
    BuildContext context, {
    double start = 0,
    double end = 0,
    double top = 0,
    double bottom = 0,
  }) {
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: start,
        end: end,
        top: top,
        bottom: bottom,
      ),
      child: this,
    );
  }
}

/// Utilidades de accesibilidad
class AccessibilityUtils {
  /// Obtener color con contraste adecuado
  static Color getContrastColor(Color backgroundColor) {
    final luminance = backgroundColor.computeLuminance();
    return luminance > 0.5 ? Colors.black : Colors.white;
  }

  /// Verificar si el contraste es suficiente (WCAG AA)
  static bool hasAdequateContrast(Color foreground, Color background) {
    final ratio = _calculateContrastRatio(foreground, background);
    return ratio >= 4.5; // WCAG AA para texto normal
  }

  /// Verificar si el contraste es suficiente para texto grande (WCAG AA)
  static bool hasAdequateContrastLargeText(Color foreground, Color background) {
    final ratio = _calculateContrastRatio(foreground, background);
    return ratio >= 3.0; // WCAG AA para texto grande
  }

  /// Calcular ratio de contraste
  static double _calculateContrastRatio(Color foreground, Color background) {
    final foregroundLuminance = foreground.computeLuminance();
    final backgroundLuminance = background.computeLuminance();

    final lighter = foregroundLuminance > backgroundLuminance
        ? foregroundLuminance
        : backgroundLuminance;
    final darker = foregroundLuminance < backgroundLuminance
        ? foregroundLuminance
        : backgroundLuminance;

    return (lighter + 0.05) / (darker + 0.05);
  }

  /// Obtener tamaño de fuente accesible
  static double getAccessibleFontSize(double baseSize, double fontScale) {
    return baseSize * fontScale;
  }

  /// Obtener tamaño mínimo de touch target (48dp según guidelines)
  static double get minTouchTargetSize => 48.0;

  /// Verificar si un tamaño cumple con guidelines de touch target
  static bool isAdequateTouchTarget(double size) {
    return size >= minTouchTargetSize;
  }
}

/// Semántica personalizada para lectores de pantalla
class FlavorSemantics extends StatelessWidget {
  final Widget child;
  final String? label;
  final String? hint;
  final String? value;
  final bool? button;
  final bool? link;
  final bool? header;
  final bool? image;
  final VoidCallback? onTap;
  final VoidCallback? onLongPress;
  final bool excludeSemantics;

  const FlavorSemantics({
    super.key,
    required this.child,
    this.label,
    this.hint,
    this.value,
    this.button,
    this.link,
    this.header,
    this.image,
    this.onTap,
    this.onLongPress,
    this.excludeSemantics = false,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: label,
      hint: hint,
      value: value,
      button: button,
      link: link,
      header: header,
      image: image,
      onTap: onTap,
      onLongPress: onLongPress,
      excludeSemantics: excludeSemantics,
      child: child,
    );
  }
}

/// Widget para anuncios de lector de pantalla
class ScreenReaderAnnouncement extends StatelessWidget {
  final String message;
  final Widget child;
  final bool announceOnBuild;

  const ScreenReaderAnnouncement({
    super.key,
    required this.message,
    required this.child,
    this.announceOnBuild = false,
  });

  @override
  Widget build(BuildContext context) {
    if (announceOnBuild) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        SemanticsService.announce(message, TextDirection.ltr);
      });
    }
    return child;
  }

  /// Anunciar mensaje manualmente
  static void announce(String message, {TextDirection direction = TextDirection.ltr}) {
    SemanticsService.announce(message, direction);
  }
}

/// Configuración de accesibilidad por módulo
class ModuleAccessibilityConfig {
  final String moduleId;
  final String screenReaderLabel;
  final String? screenReaderHint;
  final bool supportedInRTL;
  final List<String> requiredSemantics;

  const ModuleAccessibilityConfig({
    required this.moduleId,
    required this.screenReaderLabel,
    this.screenReaderHint,
    this.supportedInRTL = true,
    this.requiredSemantics = const [],
  });

  /// Configuraciones predefinidas para módulos
  static const Map<String, ModuleAccessibilityConfig> moduleConfigs = {
    'eventos': ModuleAccessibilityConfig(
      moduleId: 'eventos',
      screenReaderLabel: 'Eventos de la comunidad',
      screenReaderHint: 'Muestra los próximos eventos',
      requiredSemantics: ['date', 'title', 'location'],
    ),
    'marketplace': ModuleAccessibilityConfig(
      moduleId: 'marketplace',
      screenReaderLabel: 'Tienda',
      screenReaderHint: 'Productos disponibles para comprar',
      requiredSemantics: ['product_name', 'price', 'add_to_cart'],
    ),
    'reservas': ModuleAccessibilityConfig(
      moduleId: 'reservas',
      screenReaderLabel: 'Reservas',
      screenReaderHint: 'Gestiona tus reservas de espacios',
      requiredSemantics: ['resource', 'date', 'time', 'status'],
    ),
  };
}
