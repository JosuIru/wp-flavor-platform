import 'package:flutter/foundation.dart';
import 'package:in_app_review/in_app_review.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'feature_flags_service.dart';

/// Condiciones para mostrar el prompt de review
class ReviewConditions {
  final int minSessionCount;
  final int minDaysSinceInstall;
  final int minDaysSinceLastPrompt;
  final int minPositiveActions;
  final int maxPromptsTotal;

  const ReviewConditions({
    this.minSessionCount = 5,
    this.minDaysSinceInstall = 7,
    this.minDaysSinceLastPrompt = 30,
    this.minPositiveActions = 3,
    this.maxPromptsTotal = 3,
  });
}

/// Servicio para solicitar valoraciones de la app
class AppReviewService {
  static AppReviewService? _instance;

  final InAppReview _inAppReview;
  final FeatureFlagsService? _featureFlags;

  static const String _prefsKeyInstallDate = 'app_install_date';
  static const String _prefsKeySessionCount = 'app_session_count';
  static const String _prefsKeyLastPromptDate = 'app_last_review_prompt';
  static const String _prefsKeyPromptCount = 'app_review_prompt_count';
  static const String _prefsKeyPositiveActions = 'app_positive_actions';
  static const String _prefsKeyReviewCompleted = 'app_review_completed';

  ReviewConditions _conditions = const ReviewConditions();
  bool _isAvailable = false;

  AppReviewService._({FeatureFlagsService? featureFlags})
      : _inAppReview = InAppReview.instance,
        _featureFlags = featureFlags;

  factory AppReviewService({FeatureFlagsService? featureFlags}) {
    _instance ??= AppReviewService._(featureFlags: featureFlags);
    return _instance!;
  }

  /// Configurar condiciones personalizadas
  void configure(ReviewConditions conditions) {
    _conditions = conditions;
  }

  /// Inicializar servicio
  Future<void> initialize() async {
    _isAvailable = await _inAppReview.isAvailable();
    await _ensureInstallDate();
    await _incrementSessionCount();

    debugPrint('[AppReview] Available: $_isAvailable');
  }

  /// Asegurar que existe fecha de instalación
  Future<void> _ensureInstallDate() async {
    final prefs = await SharedPreferences.getInstance();
    if (!prefs.containsKey(_prefsKeyInstallDate)) {
      await prefs.setString(_prefsKeyInstallDate, DateTime.now().toIso8601String());
    }
  }

  /// Incrementar contador de sesiones
  Future<void> _incrementSessionCount() async {
    final prefs = await SharedPreferences.getInstance();
    final count = prefs.getInt(_prefsKeySessionCount) ?? 0;
    await prefs.setInt(_prefsKeySessionCount, count + 1);
  }

  /// Registrar acción positiva (completar compra, logro, etc.)
  Future<void> recordPositiveAction() async {
    final prefs = await SharedPreferences.getInstance();
    final count = prefs.getInt(_prefsKeyPositiveActions) ?? 0;
    await prefs.setInt(_prefsKeyPositiveActions, count + 1);

    debugPrint('[AppReview] Acción positiva registrada: ${count + 1}');

    // Verificar si es momento de pedir review
    await tryRequestReview();
  }

  /// Intentar solicitar review si se cumplen las condiciones
  Future<bool> tryRequestReview() async {
    if (!_isAvailable) {
      debugPrint('[AppReview] In-app review no disponible');
      return false;
    }

    // Verificar feature flag
    if (_featureFlags != null && !_featureFlags!.isEnabled(CommonFlags.inAppReview, defaultValue: true)) {
      debugPrint('[AppReview] Deshabilitado por feature flag');
      return false;
    }

    final canShow = await _checkConditions();
    if (!canShow) {
      return false;
    }

    try {
      await _inAppReview.requestReview();
      await _recordPromptShown();

      debugPrint('[AppReview] Review solicitado');
      return true;
    } catch (e) {
      debugPrint('[AppReview] Error solicitando review: $e');
      return false;
    }
  }

  /// Verificar si se cumplen las condiciones
  Future<bool> _checkConditions() async {
    final prefs = await SharedPreferences.getInstance();

    // ¿Ya completó el review?
    if (prefs.getBool(_prefsKeyReviewCompleted) == true) {
      debugPrint('[AppReview] Ya completó el review');
      return false;
    }

    // Verificar número de prompts
    final promptCount = prefs.getInt(_prefsKeyPromptCount) ?? 0;
    if (promptCount >= _conditions.maxPromptsTotal) {
      debugPrint('[AppReview] Máximo de prompts alcanzado');
      return false;
    }

    // Verificar sesiones
    final sessionCount = prefs.getInt(_prefsKeySessionCount) ?? 0;
    if (sessionCount < _conditions.minSessionCount) {
      debugPrint('[AppReview] Sesiones insuficientes: $sessionCount < ${_conditions.minSessionCount}');
      return false;
    }

    // Verificar días desde instalación
    final installDateStr = prefs.getString(_prefsKeyInstallDate);
    if (installDateStr != null) {
      final installDate = DateTime.tryParse(installDateStr);
      if (installDate != null) {
        final daysSinceInstall = DateTime.now().difference(installDate).inDays;
        if (daysSinceInstall < _conditions.minDaysSinceInstall) {
          debugPrint('[AppReview] Días desde instalación insuficientes: $daysSinceInstall < ${_conditions.minDaysSinceInstall}');
          return false;
        }
      }
    }

    // Verificar días desde último prompt
    final lastPromptStr = prefs.getString(_prefsKeyLastPromptDate);
    if (lastPromptStr != null) {
      final lastPrompt = DateTime.tryParse(lastPromptStr);
      if (lastPrompt != null) {
        final daysSincePrompt = DateTime.now().difference(lastPrompt).inDays;
        if (daysSincePrompt < _conditions.minDaysSinceLastPrompt) {
          debugPrint('[AppReview] Días desde último prompt insuficientes: $daysSincePrompt < ${_conditions.minDaysSinceLastPrompt}');
          return false;
        }
      }
    }

    // Verificar acciones positivas
    final positiveActions = prefs.getInt(_prefsKeyPositiveActions) ?? 0;
    if (positiveActions < _conditions.minPositiveActions) {
      debugPrint('[AppReview] Acciones positivas insuficientes: $positiveActions < ${_conditions.minPositiveActions}');
      return false;
    }

    debugPrint('[AppReview] Todas las condiciones cumplidas');
    return true;
  }

  /// Registrar que se mostró el prompt
  Future<void> _recordPromptShown() async {
    final prefs = await SharedPreferences.getInstance();
    final count = prefs.getInt(_prefsKeyPromptCount) ?? 0;
    await prefs.setInt(_prefsKeyPromptCount, count + 1);
    await prefs.setString(_prefsKeyLastPromptDate, DateTime.now().toIso8601String());

    // Resetear acciones positivas
    await prefs.setInt(_prefsKeyPositiveActions, 0);
  }

  /// Marcar review como completado (llamar después de que el usuario valore)
  Future<void> markReviewCompleted() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefsKeyReviewCompleted, true);
  }

  /// Abrir página de la app en la tienda
  Future<bool> openStoreListing() async {
    try {
      await _inAppReview.openStoreListing(
        appStoreId: '123456789', // Reemplazar con ID real
      );
      return true;
    } catch (e) {
      debugPrint('[AppReview] Error abriendo store: $e');
      return false;
    }
  }

  /// Resetear todo (para testing)
  Future<void> reset() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyInstallDate);
    await prefs.remove(_prefsKeySessionCount);
    await prefs.remove(_prefsKeyLastPromptDate);
    await prefs.remove(_prefsKeyPromptCount);
    await prefs.remove(_prefsKeyPositiveActions);
    await prefs.remove(_prefsKeyReviewCompleted);

    await _ensureInstallDate();
    debugPrint('[AppReview] Reset completado');
  }

  /// Obtener estadísticas actuales
  Future<Map<String, dynamic>> getStats() async {
    final prefs = await SharedPreferences.getInstance();

    return {
      'is_available': _isAvailable,
      'session_count': prefs.getInt(_prefsKeySessionCount) ?? 0,
      'positive_actions': prefs.getInt(_prefsKeyPositiveActions) ?? 0,
      'prompt_count': prefs.getInt(_prefsKeyPromptCount) ?? 0,
      'review_completed': prefs.getBool(_prefsKeyReviewCompleted) ?? false,
      'install_date': prefs.getString(_prefsKeyInstallDate),
      'last_prompt_date': prefs.getString(_prefsKeyLastPromptDate),
    };
  }
}
