import 'package:flutter/services.dart';

/// Servicio centralizado de feedback haptico para la aplicacion.
/// Proporciona metodos estaticos para diferentes tipos de vibraciones.
class Haptics {
  /// Vibracion ligera - para interacciones sutiles como selecciones
  static void light() => HapticFeedback.lightImpact();

  /// Vibracion media - para confirmaciones y acciones completadas
  static void medium() => HapticFeedback.mediumImpact();

  /// Vibracion fuerte - para acciones importantes o alertas
  static void heavy() => HapticFeedback.heavyImpact();

  /// Click de seleccion - para cambios de pestana, toggles, etc.
  static void selection() => HapticFeedback.selectionClick();

  /// Feedback de exito - vibracion media para acciones exitosas
  static void success() => HapticFeedback.mediumImpact();

  /// Feedback de error - vibracion fuerte para errores o advertencias
  static void error() => HapticFeedback.heavyImpact();

  /// Vibracion personalizada (solo Android) - usa el vibrator del sistema
  static void vibrate() => HapticFeedback.vibrate();
}
