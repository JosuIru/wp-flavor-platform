import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:vibration/vibration.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Tipos de feedback háptico
enum HapticFeedbackType {
  light,
  medium,
  heavy,
  selection,
  success,
  warning,
  error,
}

/// Tipos de transición
enum TransitionType {
  fade,
  slide,
  scale,
  slideUp,
  slideDown,
  none,
}

/// Configuración de UX
class UxConfig {
  final bool hapticEnabled;
  final bool animationsEnabled;
  final double animationSpeed;
  final bool reducedMotion;
  final bool highContrast;
  final double fontSize;

  const UxConfig({
    this.hapticEnabled = true,
    this.animationsEnabled = true,
    this.animationSpeed = 1.0,
    this.reducedMotion = false,
    this.highContrast = false,
    this.fontSize = 1.0,
  });

  UxConfig copyWith({
    bool? hapticEnabled,
    bool? animationsEnabled,
    double? animationSpeed,
    bool? reducedMotion,
    bool? highContrast,
    double? fontSize,
  }) {
    return UxConfig(
      hapticEnabled: hapticEnabled ?? this.hapticEnabled,
      animationsEnabled: animationsEnabled ?? this.animationsEnabled,
      animationSpeed: animationSpeed ?? this.animationSpeed,
      reducedMotion: reducedMotion ?? this.reducedMotion,
      highContrast: highContrast ?? this.highContrast,
      fontSize: fontSize ?? this.fontSize,
    );
  }

  Map<String, dynamic> toJson() => {
    'haptic_enabled': hapticEnabled,
    'animations_enabled': animationsEnabled,
    'animation_speed': animationSpeed,
    'reduced_motion': reducedMotion,
    'high_contrast': highContrast,
    'font_size': fontSize,
  };

  factory UxConfig.fromJson(Map<String, dynamic> json) {
    return UxConfig(
      hapticEnabled: json['haptic_enabled'] as bool? ?? true,
      animationsEnabled: json['animations_enabled'] as bool? ?? true,
      animationSpeed: (json['animation_speed'] as num?)?.toDouble() ?? 1.0,
      reducedMotion: json['reduced_motion'] as bool? ?? false,
      highContrast: json['high_contrast'] as bool? ?? false,
      fontSize: (json['font_size'] as num?)?.toDouble() ?? 1.0,
    );
  }
}

/// Servicio de UX para mejoras de experiencia de usuario
class UxService extends ChangeNotifier {
  static const String _prefsKey = 'flavor_ux_config';

  /// Configuración actual
  UxConfig _config = const UxConfig();

  /// Si la vibración está soportada
  bool? _hasVibrator;

  /// Singleton
  static UxService? _instance;

  UxService._();

  static UxService get instance {
    _instance ??= UxService._();
    return _instance!;
  }

  /// Configuración actual
  UxConfig get config => _config;

  /// Inicializa el servicio
  Future<void> initialize() async {
    await _loadConfig();
    await _checkVibrationSupport();
  }

  /// Carga configuración guardada
  Future<void> _loadConfig() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final configJson = prefs.getString(_prefsKey);
      
      if (configJson != null) {
        final Map<String, dynamic> json = {};
        // Parse manual para evitar dependencia de dart:convert
        _config = UxConfig(
          hapticEnabled: prefs.getBool('ux_haptic_enabled') ?? true,
          animationsEnabled: prefs.getBool('ux_animations_enabled') ?? true,
          animationSpeed: prefs.getDouble('ux_animation_speed') ?? 1.0,
          reducedMotion: prefs.getBool('ux_reduced_motion') ?? false,
          highContrast: prefs.getBool('ux_high_contrast') ?? false,
          fontSize: prefs.getDouble('ux_font_size') ?? 1.0,
        );
      }
    } catch (e) {
      debugPrint('[UxService] Error loading config: $e');
    }
  }

  /// Guarda configuración
  Future<void> _saveConfig() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('ux_haptic_enabled', _config.hapticEnabled);
      await prefs.setBool('ux_animations_enabled', _config.animationsEnabled);
      await prefs.setDouble('ux_animation_speed', _config.animationSpeed);
      await prefs.setBool('ux_reduced_motion', _config.reducedMotion);
      await prefs.setBool('ux_high_contrast', _config.highContrast);
      await prefs.setDouble('ux_font_size', _config.fontSize);
    } catch (e) {
      debugPrint('[UxService] Error saving config: $e');
    }
  }

  /// Verifica soporte de vibración
  Future<void> _checkVibrationSupport() async {
    try {
      _hasVibrator = await Vibration.hasVibrator() ?? false;
    } catch (e) {
      _hasVibrator = false;
    }
  }

  // =========================================================================
  // CONFIGURACIÓN
  // =========================================================================

  /// Actualiza configuración
  Future<void> updateConfig(UxConfig newConfig) async {
    _config = newConfig;
    await _saveConfig();
    notifyListeners();
  }

  /// Habilita/deshabilita háptico
  Future<void> setHapticEnabled(bool enabled) async {
    _config = _config.copyWith(hapticEnabled: enabled);
    await _saveConfig();
    notifyListeners();
  }

  /// Habilita/deshabilita animaciones
  Future<void> setAnimationsEnabled(bool enabled) async {
    _config = _config.copyWith(animationsEnabled: enabled);
    await _saveConfig();
    notifyListeners();
  }

  /// Ajusta velocidad de animación
  Future<void> setAnimationSpeed(double speed) async {
    _config = _config.copyWith(animationSpeed: speed.clamp(0.5, 2.0));
    await _saveConfig();
    notifyListeners();
  }

  /// Habilita/deshabilita movimiento reducido
  Future<void> setReducedMotion(bool reduced) async {
    _config = _config.copyWith(reducedMotion: reduced);
    await _saveConfig();
    notifyListeners();
  }

  /// Ajusta tamaño de fuente
  Future<void> setFontSize(double size) async {
    _config = _config.copyWith(fontSize: size.clamp(0.8, 1.5));
    await _saveConfig();
    notifyListeners();
  }

  // =========================================================================
  // FEEDBACK HÁPTICO
  // =========================================================================

  /// Ejecuta feedback háptico
  Future<void> haptic(HapticFeedbackType type) async {
    if (!_config.hapticEnabled || _hasVibrator != true) return;

    try {
      switch (type) {
        case HapticFeedbackType.light:
          await HapticFeedback.lightImpact();
          break;
        case HapticFeedbackType.medium:
          await HapticFeedback.mediumImpact();
          break;
        case HapticFeedbackType.heavy:
          await HapticFeedback.heavyImpact();
          break;
        case HapticFeedbackType.selection:
          await HapticFeedback.selectionClick();
          break;
        case HapticFeedbackType.success:
          await Vibration.vibrate(duration: 50);
          await Future.delayed(const Duration(milliseconds: 100));
          await Vibration.vibrate(duration: 50);
          break;
        case HapticFeedbackType.warning:
          await Vibration.vibrate(duration: 100);
          break;
        case HapticFeedbackType.error:
          await Vibration.vibrate(duration: 200);
          await Future.delayed(const Duration(milliseconds: 100));
          await Vibration.vibrate(duration: 200);
          break;
      }
    } catch (e) {
      debugPrint('[UxService] Haptic error: $e');
    }
  }

  /// Feedback para tap
  Future<void> hapticTap() => haptic(HapticFeedbackType.light);

  /// Feedback para selección
  Future<void> hapticSelection() => haptic(HapticFeedbackType.selection);

  /// Feedback para éxito
  Future<void> hapticSuccess() => haptic(HapticFeedbackType.success);

  /// Feedback para error
  Future<void> hapticError() => haptic(HapticFeedbackType.error);

  // =========================================================================
  // ANIMACIONES
  // =========================================================================

  /// Obtiene duración de animación ajustada
  Duration getAnimationDuration(Duration baseDuration) {
    if (!_config.animationsEnabled || _config.reducedMotion) {
      return Duration.zero;
    }
    return Duration(
      milliseconds: (baseDuration.inMilliseconds / _config.animationSpeed).round(),
    );
  }

  /// Duración corta
  Duration get shortAnimation => getAnimationDuration(const Duration(milliseconds: 150));

  /// Duración media
  Duration get mediumAnimation => getAnimationDuration(const Duration(milliseconds: 300));

  /// Duración larga
  Duration get longAnimation => getAnimationDuration(const Duration(milliseconds: 500));

  /// Obtiene curva de animación
  Curve get animationCurve {
    if (_config.reducedMotion) return Curves.linear;
    return Curves.easeInOut;
  }

  /// Construye transición de página
  Widget buildPageTransition({
    required Widget child,
    required Animation<double> animation,
    TransitionType type = TransitionType.fade,
  }) {
    if (!_config.animationsEnabled || _config.reducedMotion) {
      return child;
    }

    switch (type) {
      case TransitionType.fade:
        return FadeTransition(opacity: animation, child: child);
      
      case TransitionType.slide:
        return SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(1.0, 0.0),
            end: Offset.zero,
          ).animate(CurvedAnimation(
            parent: animation,
            curve: animationCurve,
          )),
          child: child,
        );
      
      case TransitionType.scale:
        return ScaleTransition(
          scale: Tween<double>(begin: 0.9, end: 1.0).animate(
            CurvedAnimation(parent: animation, curve: animationCurve),
          ),
          child: FadeTransition(opacity: animation, child: child),
        );
      
      case TransitionType.slideUp:
        return SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(0.0, 1.0),
            end: Offset.zero,
          ).animate(CurvedAnimation(
            parent: animation,
            curve: animationCurve,
          )),
          child: child,
        );
      
      case TransitionType.slideDown:
        return SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(0.0, -1.0),
            end: Offset.zero,
          ).animate(CurvedAnimation(
            parent: animation,
            curve: animationCurve,
          )),
          child: child,
        );
      
      case TransitionType.none:
        return child;
    }
  }

  // =========================================================================
  // ESTADOS DE CARGA
  // =========================================================================

  /// Widget de carga con shimmer
  Widget buildLoadingShimmer({
    double? width,
    double? height,
    BorderRadius? borderRadius,
  }) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.grey[300],
        borderRadius: borderRadius ?? BorderRadius.circular(4),
      ),
    );
  }

  /// Widget de error con retry
  Widget buildErrorState({
    required String message,
    VoidCallback? onRetry,
    IconData icon = Icons.error_outline,
  }) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 64, color: Colors.red[300]),
          const SizedBox(height: 16),
          Text(
            message,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 16 * _config.fontSize,
              color: Colors.grey[700],
            ),
          ),
          if (onRetry != null) ...[
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                hapticTap();
                onRetry();
              },
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ],
      ),
    );
  }

  /// Widget de estado vacío
  Widget buildEmptyState({
    required String message,
    String? actionLabel,
    VoidCallback? onAction,
    IconData icon = Icons.inbox,
  }) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            message,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 16 * _config.fontSize,
              color: Colors.grey[600],
            ),
          ),
          if (onAction != null && actionLabel != null) ...[
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () {
                hapticTap();
                onAction();
              },
              child: Text(actionLabel),
            ),
          ],
        ],
      ),
    );
  }

  // =========================================================================
  // TIPOGRAFÍA
  // =========================================================================

  /// Obtiene estilo de texto escalado
  TextStyle getScaledTextStyle(TextStyle baseStyle) {
    return baseStyle.copyWith(
      fontSize: (baseStyle.fontSize ?? 14) * _config.fontSize,
    );
  }

  /// Tamaño de fuente pequeño
  double get fontSizeSmall => 12 * _config.fontSize;

  /// Tamaño de fuente normal
  double get fontSizeNormal => 14 * _config.fontSize;

  /// Tamaño de fuente medio
  double get fontSizeMedium => 16 * _config.fontSize;

  /// Tamaño de fuente grande
  double get fontSizeLarge => 20 * _config.fontSize;

  /// Tamaño de fuente título
  double get fontSizeTitle => 24 * _config.fontSize;

  // =========================================================================
  // ACCESIBILIDAD
  // =========================================================================

  /// Obtiene configuración de contraste
  Color getContrastColor(Color background) {
    if (_config.highContrast) {
      return background.computeLuminance() > 0.5 ? Colors.black : Colors.white;
    }
    return background.computeLuminance() > 0.5 ? Colors.black87 : Colors.white;
  }

  /// Verifica si debe usar animaciones
  bool get shouldAnimate => _config.animationsEnabled && !_config.reducedMotion;
}

// =========================================================================
// WIDGETS HELPER
// =========================================================================

/// Widget animado con configuración de UX
class UxAnimatedWidget extends StatefulWidget {
  final Widget child;
  final Duration duration;
  final Curve curve;
  final bool animate;

  const UxAnimatedWidget({
    super.key,
    required this.child,
    this.duration = const Duration(milliseconds: 300),
    this.curve = Curves.easeInOut,
    this.animate = true,
  });

  @override
  State<UxAnimatedWidget> createState() => _UxAnimatedWidgetState();
}

class _UxAnimatedWidgetState extends State<UxAnimatedWidget>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    final ux = UxService.instance;
    
    _controller = AnimationController(
      duration: ux.getAnimationDuration(widget.duration),
      vsync: this,
    );
    
    _animation = CurvedAnimation(
      parent: _controller,
      curve: ux.animationCurve,
    );

    if (widget.animate && ux.shouldAnimate) {
      _controller.forward();
    } else {
      _controller.value = 1.0;
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _animation,
      child: widget.child,
    );
  }
}

/// Botón con feedback háptico
class UxButton extends StatelessWidget {
  final VoidCallback? onPressed;
  final Widget child;
  final HapticFeedbackType hapticType;
  final ButtonStyle? style;

  const UxButton({
    super.key,
    required this.onPressed,
    required this.child,
    this.hapticType = HapticFeedbackType.light,
    this.style,
  });

  @override
  Widget build(BuildContext context) {
    return ElevatedButton(
      onPressed: onPressed != null
          ? () {
              UxService.instance.haptic(hapticType);
              onPressed!();
            }
          : null,
      style: style,
      child: child,
    );
  }
}

/// GestureDetector con feedback háptico
class UxTapDetector extends StatelessWidget {
  final VoidCallback? onTap;
  final Widget child;
  final HapticFeedbackType hapticType;

  const UxTapDetector({
    super.key,
    required this.onTap,
    required this.child,
    this.hapticType = HapticFeedbackType.light,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap != null
          ? () {
              UxService.instance.haptic(hapticType);
              onTap!();
            }
          : null,
      child: child,
    );
  }
}
