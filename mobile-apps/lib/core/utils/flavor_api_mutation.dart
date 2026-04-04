import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../widgets/flavor_snackbar.dart';

class FlavorApiMutation {
  const FlavorApiMutation._();

  static Future<bool> run(
    BuildContext context, {
    required Future<ApiResponse> Function() request,
    required String successMessage,
    required String errorMessage,
    Future<void> Function()? onSuccess,
  }) async {
    final response = await request();
    if (!context.mounted) return response.success;

    if (response.success) {
      FlavorSnackbar.showSuccess(context, successMessage);
      if (onSuccess != null) {
        await onSuccess();
      }
      return true;
    }

    FlavorSnackbar.showError(context, response.error ?? errorMessage);
    return false;
  }
}
