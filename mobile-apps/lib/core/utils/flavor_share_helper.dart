import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';

import '../widgets/flavor_snackbar.dart';

class FlavorShareHelper {
  static Future<void> copyText(
    BuildContext context,
    String text, {
    String successMessage = 'Enlace copiado.',
  }) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!context.mounted) return;
    FlavorSnackbar.showSuccess(context, successMessage);
  }

  static Future<void> shareText(
    String text, {
    String? subject,
  }) {
    return Share.share(text, subject: subject);
  }
}
