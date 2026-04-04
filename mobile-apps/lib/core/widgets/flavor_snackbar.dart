import 'package:flutter/material.dart';

class FlavorSnackbar {
  const FlavorSnackbar._();

  static void showInfo(BuildContext context, String message) {
    _show(
      context,
      message: message,
    );
  }

  static void showSuccess(BuildContext context, String message) {
    _show(
      context,
      message: message,
      backgroundColor: Colors.green.shade700,
      foregroundColor: Colors.white,
    );
  }

  static void showError(BuildContext context, String message) {
    _show(
      context,
      message: message,
      backgroundColor: Theme.of(context).colorScheme.error,
      foregroundColor: Theme.of(context).colorScheme.onError,
    );
  }

  static void _show(
    BuildContext context, {
    required String message,
    Color? backgroundColor,
    Color? foregroundColor,
  }) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: backgroundColor,
        behavior: SnackBarBehavior.floating,
        showCloseIcon: true,
        closeIconColor: foregroundColor,
      ),
    );
  }
}
