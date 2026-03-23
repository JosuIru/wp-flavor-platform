import 'package:flutter/material.dart';
import '../config/dynamic_config.dart';
import '../config/app_config.dart';
import 'app_colors.dart';

/// Constructor de temas dinámicos basados en la configuración del servidor
class DynamicThemeBuilder {
  /// Construye un tema claro dinámico
  static ThemeData buildLightTheme([DynamicConfig? config]) {
    config ??= DynamicConfig();

    final primaryColor = config.isLoaded
        ? config.primaryColor
        : Color(AppColors.primary.value);

    final colorScheme = config.isLoaded
        ? config.buildColorScheme(brightness: Brightness.light)
        : ColorScheme.fromSeed(
            seedColor: primaryColor,
            brightness: Brightness.light,
          );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: config.isLoaded
          ? config.backgroundColor
          : Colors.white,
      appBarTheme: AppBarTheme(
        centerTitle: true,
        elevation: 0,
        backgroundColor: primaryColor,
        foregroundColor: _contrastColor(primaryColor),
      ),
      cardTheme: CardTheme(
        elevation: 2,
        color: config.isLoaded ? config.surfaceColor : Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: config.isLoaded
            ? config.surfaceColor
            : Colors.grey.shade100,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: primaryColor, width: 2),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: _contrastColor(primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: _contrastColor(primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryColor,
          side: BorderSide(color: primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryColor,
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: config.isLoaded
            ? config.secondaryColor
            : AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: primaryColor.withOpacity(0.2),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: primaryColor,
            );
          }
          return TextStyle(
            fontSize: 12,
            color: config!.isLoaded
                ? config.textSecondaryColor
                : Colors.grey.shade600,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return IconThemeData(color: primaryColor);
          }
          return IconThemeData(
            color: config!.isLoaded
                ? config.textSecondaryColor
                : Colors.grey.shade600,
          );
        }),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: config.isLoaded
            ? config.surfaceColor
            : Colors.grey.shade100,
        selectedColor: primaryColor.withOpacity(0.2),
        labelStyle: TextStyle(
          color: config.isLoaded
              ? config.textPrimaryColor
              : Colors.black87,
        ),
      ),
      dividerTheme: DividerThemeData(
        color: Colors.grey.shade300,
        thickness: 1,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
        ),
      ),
    );
  }

  /// Construye un tema oscuro dinámico
  static ThemeData buildDarkTheme([DynamicConfig? config]) {
    config ??= DynamicConfig();

    final primaryColor = config.isLoaded
        ? config.primaryColor
        : Color(AppColors.primary.value);

    final colorScheme = config.isLoaded
        ? config.buildColorScheme(brightness: Brightness.dark)
        : ColorScheme.fromSeed(
            seedColor: primaryColor,
            brightness: Brightness.dark,
          );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: const Color(0xFF121212),
      appBarTheme: AppBarTheme(
        centerTitle: true,
        elevation: 0,
        backgroundColor: const Color(0xFF1E1E1E),
        foregroundColor: Colors.white,
      ),
      cardTheme: CardTheme(
        elevation: 2,
        color: const Color(0xFF1E1E1E),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFF2C2C2C),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: primaryColor, width: 2),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: _contrastColor(primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: _contrastColor(primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryColor,
          side: BorderSide(color: primaryColor),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryColor,
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: config.isLoaded
            ? config.secondaryColor
            : AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: const Color(0xFF1E1E1E),
        indicatorColor: primaryColor.withOpacity(0.2),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: primaryColor,
            );
          }
          return TextStyle(
            fontSize: 12,
            color: Colors.grey.shade400,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return IconThemeData(color: primaryColor);
          }
          return IconThemeData(color: Colors.grey.shade400);
        }),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: const Color(0xFF2C2C2C),
        selectedColor: primaryColor.withOpacity(0.3),
        labelStyle: const TextStyle(color: Colors.white70),
      ),
      dividerTheme: DividerThemeData(
        color: Colors.grey.shade800,
        thickness: 1,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: const Color(0xFF2C2C2C),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
        ),
      ),
    );
  }

  /// Calcula el color de contraste óptimo (blanco o negro)
  static Color _contrastColor(Color color) {
    final luminance = color.computeLuminance();
    return luminance > 0.5 ? Colors.black : Colors.white;
  }
}

/// Extension para facilitar el acceso a colores dinámicos
extension DynamicColorsExtension on BuildContext {
  /// Obtiene la configuración dinámica
  DynamicConfig get dynamicConfig => DynamicConfig();

  /// Color primario dinámico
  Color get primaryColor => dynamicConfig.isLoaded
      ? dynamicConfig.primaryColor
      : Theme.of(this).colorScheme.primary;

  /// Color secundario dinámico
  Color get secondaryColor => dynamicConfig.isLoaded
      ? dynamicConfig.secondaryColor
      : Theme.of(this).colorScheme.secondary;

  /// Color de error dinámico
  Color get errorColor => dynamicConfig.isLoaded
      ? dynamicConfig.errorColor
      : Theme.of(this).colorScheme.error;

  /// Color de éxito dinámico
  Color get successColor => dynamicConfig.isLoaded
      ? dynamicConfig.successColor
      : const Color(0xFF22C55E); // Green success color
}
