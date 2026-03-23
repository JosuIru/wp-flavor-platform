/// Punto de entrada por defecto de Flutter
///
/// Este archivo existe para compatibilidad con builds que no especifican
/// un target (-t). Redirige a main_admin.dart por defecto.
///
/// Para builds específicos usar:
/// - flutter build apk --flavor admin -t lib/main_admin.dart
/// - flutter build apk --flavor client -t lib/main_client.dart
///
/// O usar el script: ./build_app.sh admin|client [debug|release]

import 'main_admin.dart' as admin_app;

void main() {
  admin_app.main();
}
