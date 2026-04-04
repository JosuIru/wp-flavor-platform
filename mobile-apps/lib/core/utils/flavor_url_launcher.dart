import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../widgets/flavor_snackbar.dart';

class FlavorUrlLauncher {
  static Future<bool> openExternalRaw(
    String rawUrl, {
    bool normalizeHttpScheme = false,
  }) async {
    if (rawUrl.trim().isEmpty) {
      return false;
    }

    final normalizedUrl = normalizeHttpScheme &&
            !rawUrl.trim().startsWith('http://') &&
            !rawUrl.trim().startsWith('https://')
        ? 'https://${rawUrl.trim()}'
        : rawUrl.trim();

    final uri = Uri.tryParse(normalizedUrl);
    if (uri == null) {
      return false;
    }

    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  static Future<bool> openExternalUriRaw(Uri uri) {
    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  static Future<bool> openExternal(
    BuildContext context,
    String rawUrl, {
    String emptyMessage = 'No hay enlace disponible.',
    String errorMessage = 'No se pudo abrir el enlace.',
    bool normalizeHttpScheme = false,
  }) async {
    if (rawUrl.trim().isEmpty) {
      FlavorSnackbar.showInfo(context, emptyMessage);
      return false;
    }

    final normalizedUrl = normalizeHttpScheme &&
            !rawUrl.trim().startsWith('http://') &&
            !rawUrl.trim().startsWith('https://')
        ? 'https://${rawUrl.trim()}'
        : rawUrl.trim();

    final uri = Uri.tryParse(normalizedUrl);
    if (uri == null) {
      FlavorSnackbar.showError(context, errorMessage);
      return false;
    }

    final launched = await openExternalUriRaw(uri);

    if (!context.mounted) return launched;
    if (!launched) {
      FlavorSnackbar.showError(context, errorMessage);
    }
    return launched;
  }

  static Future<bool> openExternalUri(
    BuildContext context,
    Uri uri, {
    String errorMessage = 'No se pudo abrir el enlace.',
  }) async {
    final launched = await openExternalUriRaw(uri);

    if (!context.mounted) return launched;
    if (!launched) {
      FlavorSnackbar.showError(context, errorMessage);
    }
    return launched;
  }
}
