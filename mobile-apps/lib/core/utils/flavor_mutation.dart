import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../widgets/flavor_snackbar.dart';

class FlavorMutation {
  const FlavorMutation._();

  static Future<bool> runApiResponse(
    BuildContext context, {
    required Future<ApiResponse<Map<String, dynamic>>> Function() request,
    required String successMessage,
    String? fallbackErrorMessage,
    Future<void> Function()? onSuccess,
  }) async {
    try {
      final response = await request();
      if (!context.mounted) return response.success;

      if (response.success) {
        FlavorSnackbar.showSuccess(context, successMessage);
        await onSuccess?.call();
        return true;
      }

      FlavorSnackbar.showError(
        context,
        response.error ?? fallbackErrorMessage ?? 'No se pudo completar la acción.',
      );
      return false;
    } catch (e) {
      if (context.mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
      return false;
    }
  }
}
