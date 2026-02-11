import 'package:url_launcher/url_launcher.dart';
import 'server_config.dart';

/// Configuración de la aplicación
///
/// PERSONALIZACIÓN:
/// Para crear apps personalizadas para tu negocio, modifica estos valores:
/// - businessName: Nombre que aparece en la app
/// - clientAppName/adminAppName: Nombres de las apps
/// - developerName/Email/Phone: Tu información de contacto
///
/// También debes cambiar los package names en android/app/build.gradle
class AppConfig {
  /// Nombre del negocio por defecto (se sobrescribe con datos del servidor)
  static const String businessName = 'Mi Negocio';

  /// Namespace de la API
  static const String apiNamespace = '/wp-json/chat-ia-mobile/v1';

  /// URL completa de la API (se obtiene dinámicamente)
  static Future<String> getApiUrl() async {
    return await ServerConfig.getFullApiUrl();
  }

  /// Timeout para peticiones HTTP (en segundos)
  static const int httpTimeout = 30;

  /// Nombre de la app cliente
  static const String clientAppName = 'Reservas';

  /// Nombre de la app admin
  static const String adminAppName = 'Admin';

  /// Versión de la app
  static const String appVersion = '1.0.0';

  /// ¿Modo debug? (false para producción)
  static const bool isDebug = false;

  /// Información del desarrollador (personalizar antes de distribuir)
  static const String developerName = 'gailu Wz';
  static const String developerEmail = 'info@gailu.net';
  static const String developerPhone = '+34 600 000 000';

  /// Package names de las apps (cambiar en build.gradle para producción)
  /// Estos valores son solo de referencia
  static const String adminPackageName = 'com.tudominio.app.admin';
  static const String clientPackageName = 'com.tudominio.app.client';

  /// Deep link scheme (cambiar en AndroidManifest.xml para producción)
  /// Por defecto usa "chatiaapp" que es genérico
  static const String deepLinkScheme = 'chatiaapp';

  /// Intenta abrir la app de admin
  static Future<bool> openAdminApp() async {
    // Intentar abrir con deep link
    final Uri adminUri = Uri.parse('$deepLinkScheme://admin');
    if (await canLaunchUrl(adminUri)) {
      return await launchUrl(adminUri);
    }
    // Fallback: intentar abrir el paquete directamente
    final Uri packageUri = Uri.parse('android-app://$adminPackageName');
    if (await canLaunchUrl(packageUri)) {
      return await launchUrl(packageUri);
    }
    return false;
  }

  /// Intenta abrir la app de cliente
  static Future<bool> openClientApp() async {
    // Intentar abrir con deep link
    final Uri clientUri = Uri.parse('$deepLinkScheme://client');
    if (await canLaunchUrl(clientUri)) {
      return await launchUrl(clientUri);
    }
    // Fallback: intentar abrir el paquete directamente
    final Uri packageUri = Uri.parse('android-app://$clientPackageName');
    if (await canLaunchUrl(packageUri)) {
      return await launchUrl(packageUri);
    }
    return false;
  }

  /// Obtiene la URL de la API dinámicamente (legacy support)
  static String get apiUrl => '';
}

/// Configuración de colores de la app
class AppColors {
  // Colores principales
  static const int primaryValue = 0xFF2196F3;
  static const int secondaryValue = 0xFF4CAF50;
  static const int accentValue = 0xFFFF9800;

  // Colores de estado
  static const int successValue = 0xFF4CAF50;
  static const int warningValue = 0xFFFF9800;
  static const int errorValue = 0xFFF44336;
  static const int infoValue = 0xFF2196F3;

  // Fondos
  static const int backgroundValue = 0xFFF5F5F5;
  static const int surfaceValue = 0xFFFFFFFF;
  static const int cardValue = 0xFFFFFFFF;
}

/// Configuración de textos
class AppStrings {
  // General
  static const String appName = 'Chat IA';
  static const String loading = 'Cargando...';
  static const String error = 'Error';
  static const String retry = 'Reintentar';
  static const String cancel = 'Cancelar';
  static const String confirm = 'Confirmar';
  static const String save = 'Guardar';
  static const String delete = 'Eliminar';
  static const String edit = 'Editar';
  static const String close = 'Cerrar';
  static const String search = 'Buscar';
  static const String noResults = 'Sin resultados';

  // Auth
  static const String login = 'Iniciar sesión';
  static const String logout = 'Cerrar sesión';
  static const String username = 'Usuario';
  static const String password = 'Contraseña';
  static const String loginError = 'Error al iniciar sesión';

  // Chat
  static const String chatTitle = 'Asistente';
  static const String typeMessage = 'Escribe un mensaje...';
  static const String sendMessage = 'Enviar';

  // Reservations
  static const String reservations = 'Reservas';
  static const String availability = 'Disponibilidad';
  static const String tickets = 'Entradas';
  static const String checkout = 'Finalizar compra';
  static const String addToCart = 'Añadir al carrito';
}
