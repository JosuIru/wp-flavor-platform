import 'package:flutter/material.dart';

import 'flavor_url_launcher.dart';

class FlavorContactLauncher {
  static Future<bool> call(
    BuildContext context,
    String phone, {
    String errorMessage = 'No se puede realizar la llamada',
  }) {
    return FlavorUrlLauncher.openExternal(
      context,
      'tel:$phone',
      emptyMessage: 'No hay telefono disponible.',
      errorMessage: errorMessage,
    );
  }

  static Future<bool> email(
    BuildContext context,
    String email, {
    String? subject,
    String? body,
    String errorMessage = 'No se puede abrir el email',
  }) {
    final query = <String, String>{
      if ((subject ?? '').isNotEmpty) 'subject': subject!,
      if ((body ?? '').isNotEmpty) 'body': body!,
    };
    final uri = Uri(
      scheme: 'mailto',
      path: email,
      queryParameters: query.isEmpty ? null : query,
    );
    return FlavorUrlLauncher.openExternalUri(
      context,
      uri,
      errorMessage: errorMessage,
    );
  }

  static Future<bool> sms(
    BuildContext context,
    String phone, {
    String? body,
    String errorMessage = 'No se puede abrir SMS',
  }) {
    final uri = Uri(
      scheme: 'sms',
      path: phone,
      queryParameters: (body ?? '').isNotEmpty ? {'body': body!} : null,
    );
    return FlavorUrlLauncher.openExternalUri(
      context,
      uri,
      errorMessage: errorMessage,
    );
  }
}
