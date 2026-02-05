# Chat IA - Apps Móviles Flutter

Apps móviles nativas para el sistema Chat IA desarrolladas con Flutter.

## Estructura del Proyecto

```
mobile-apps/
├── lib/
│   ├── core/                           # Código compartido
│   │   ├── api/
│   │   │   └── api_client.dart         # Cliente HTTP para la API
│   │   ├── config/
│   │   │   └── app_config.dart         # Configuración de la app
│   │   ├── models/
│   │   │   └── models.dart             # Modelos de datos
│   │   ├── providers/
│   │   │   └── providers.dart          # Providers Riverpod
│   │   └── widgets/
│   │       ├── widgets.dart            # Barrel file
│   │       ├── chat_widgets.dart       # Widgets de chat
│   │       ├── calendar_widgets.dart   # Widgets de calendario
│   │       ├── ticket_widgets.dart     # Widgets de tickets
│   │       └── common_widgets.dart     # Widgets comunes
│   ├── features/
│   │   ├── chat/
│   │   │   └── chat_screen.dart        # Pantalla de chat cliente
│   │   ├── reservations/
│   │   │   ├── reservations_screen.dart  # Selección de tickets
│   │   │   └── checkout_webview.dart     # WebView WooCommerce
│   │   ├── info/
│   │   │   └── info_screen.dart        # Info del negocio
│   │   └── admin/
│   │       ├── dashboard_screen.dart   # Dashboard admin
│   │       ├── admin_reservations_screen.dart  # Gestión reservas
│   │       └── admin_chat_screen.dart  # Chat IA admin
│   ├── client/
│   │   └── app_client.dart             # Configuración cliente
│   ├── main_client.dart                # Entry point app cliente
│   └── main_admin.dart                 # Entry point app admin
├── assets/
│   ├── images/
│   ├── icons/
│   └── fonts/
├── android/                            # Configuración Android
├── ios/                                # Configuración iOS
├── pubspec.yaml                        # Dependencias
└── README.md
```

## Requisitos

- Flutter SDK >= 3.0.0
- Dart >= 3.0.0
- Android Studio / Xcode
- Cuenta de Firebase (opcional, para push notifications)

## Configuración Inicial

### 1. Instalar dependencias

```bash
cd mobile-apps
flutter pub get
```

### 2. Configurar URL de la API

Edita `lib/core/config/app_config.dart`:

```dart
static const String baseUrl = 'https://TU-SITIO.com';
```

### 3. Configurar Firebase (Push Notifications - Opcional)

1. Crea un proyecto en [Firebase Console](https://console.firebase.google.com)
2. Añade apps Android e iOS
3. Descarga los archivos de configuración:
   - Android: `google-services.json` → `android/app/`
   - iOS: `GoogleService-Info.plist` → `ios/Runner/`

## Ejecutar en Desarrollo

### App Cliente (Reservas)

```bash
flutter run -t lib/main_client.dart
```

### App Admin

```bash
flutter run -t lib/main_admin.dart
```

## Compilar APK

### App Cliente

```bash
flutter build apk -t lib/main_client.dart --release
```

El APK se genera en: `build/app/outputs/flutter-apk/app-release.apk`

### App Admin

```bash
flutter build apk -t lib/main_admin.dart --release
```

## Compilar para iOS

### App Cliente

```bash
flutter build ios -t lib/main_client.dart --release
```

### App Admin

```bash
flutter build ios -t lib/main_admin.dart --release
```

## Características

### App Cliente
- **Chat IA**: Asistente virtual para consultas y reservas
- **Calendario**: Visualización de disponibilidad con estados
- **Selector de tickets**: Elegir tipo y cantidad de entradas
- **Checkout WebView**: Pago integrado con WooCommerce
- **Información del negocio**: Datos de contacto, horarios, ubicación

### App Admin
- **Dashboard**: Estadísticas del día, semana y mes
- **Gestión de reservas**: Búsqueda, filtrado y detalles
- **Chat IA Admin**: Consultas sobre datos, exportación CSV
- **Check-in**: Validación de entradas (próximamente: QR)
- **Configuración**: Ajustes y cierre de sesión

## API REST Endpoints

La app se comunica con WordPress a través de estos endpoints (namespace `chat-ia-mobile/v1`):

### Públicos (sin auth)
- `POST /chat/session` - Crear sesión de chat
- `POST /chat/send` - Enviar mensaje
- `GET /public/business-info` - Info del negocio
- `GET /public/availability` - Disponibilidad
- `GET /public/tickets` - Tipos de tickets
- `POST /reservations/check` - Verificar disponibilidad
- `POST /reservations/add-to-cart` - Añadir al carrito

### Admin (requiere auth)
- `POST /auth/login` - Login
- `GET /auth/verify` - Verificar token
- `GET /admin/dashboard` - Dashboard
- `GET /admin/reservations` - Lista de reservas
- `GET /admin/reservations/find?code=XXX` - Buscar por código QR
- `POST /admin/reservations/{id}/checkin` - Realizar check-in
- `POST /admin/reservations/{id}/cancel` - Cancelar reserva
- `POST /admin/chat/send` - Chat IA admin
- `GET /admin/customers` - Lista de clientes
- `POST /admin/export/csv` - Exportar datos

## Flujo de Checkout

El checkout se maneja abriendo WooCommerce en un WebView:

1. Usuario selecciona fecha y tickets en la app
2. Se añade al carrito vía API
3. Se abre WebView con la URL del carrito
4. Usuario completa pago en WooCommerce
5. WebView detecta URL de confirmación (`/order-received/`) y cierra
6. App muestra confirmación y limpia el carrito

## Personalización

### Colores

Edita `lib/core/config/app_config.dart`:

```dart
class AppColors {
  static const int primaryValue = 0xFF2196F3;    // Azul
  static const int secondaryValue = 0xFF4CAF50;  // Verde
  // ...
}
```

### Nombres de la App

```dart
class AppConfig {
  static const String clientAppName = 'Reservas';
  static const String adminAppName = 'Admin Reservas';
}
```

### Tema Oscuro

Las apps soportan tema oscuro automáticamente según la configuración del sistema.

## Troubleshooting

### Error de conexión API

1. Verifica que la URL base sea correcta en `app_config.dart`
2. Asegúrate de que el plugin Chat IA esté activo en WordPress
3. Comprueba que los endpoints REST estén accesibles: `https://tu-sitio.com/wp-json/chat-ia-mobile/v1/public/business-info`

### Error de autenticación (Admin)

1. Verifica credenciales de WordPress
2. El usuario debe tener rol de administrador
3. Comprueba que el token no haya expirado (tokens válidos por 24h)

### WebView no carga

1. Verifica que tu sitio tenga SSL (HTTPS)
2. En Android, asegúrate de tener permisos de internet en `AndroidManifest.xml`
3. Comprueba la URL de checkout en la respuesta de `add-to-cart`

### Push notifications no funcionan

1. Verifica configuración de Firebase
2. Comprueba que los archivos de configuración estén en su lugar
3. En iOS, asegúrate de tener los certificados APNS configurados

## Arquitectura

### Estado (Riverpod)
- `apiClientProvider`: Cliente HTTP singleton
- `chatProvider`: Estado del chat cliente
- `adminChatProvider`: Estado del chat admin
- `cartProvider`: Carrito de compras
- `authStateProvider`: Autenticación admin

### Navegación
- **Cliente**: Bottom navigation con 3 tabs (Chat, Reservar, Info)
- **Admin**: Bottom navigation con 4 tabs (Dashboard, Reservas, Chat IA, Ajustes)

### Autenticación
- Cliente: Sin autenticación (acceso público)
- Admin: Login con usuario/contraseña de WordPress, token JWT

## Debugging

### Habilitar modo debug

En `lib/core/config/app_config.dart`:

```dart
static const bool isDebug = true;  // Muestra banner de debug
```

### Probar en emulador Android

1. Para usar con Local by Flywheel:
   ```dart
   // En app_config.dart, cambiar baseUrl:
   static const String baseUrl = 'http://10.0.2.2:10014';  // IP especial del emulador
   ```

2. O usando la IP local de tu máquina:
   ```dart
   static const String baseUrl = 'http://192.168.1.XX:10014';
   ```

### Probar en dispositivo físico

1. Encuentra la IP local de tu máquina: `ip addr` o `hostname -I`
2. Configura la URL:
   ```dart
   static const String baseUrl = 'http://192.168.1.XX:10014';
   ```
3. Asegúrate que tu firewall permite conexiones en el puerto

### Ver logs de debug

```bash
# En terminal, mientras ejecutas la app:
flutter run -t lib/main_client.dart --verbose

# Ver solo errores de la API:
flutter logs | grep -i "api\|error\|exception"
```

### Verificar endpoints desde navegador

```
# Info del negocio (público):
https://tu-sitio.com/wp-json/chat-ia-mobile/v1/public/business-info

# Disponibilidad (público):
https://tu-sitio.com/wp-json/chat-ia-mobile/v1/public/availability?from=2025-01-01&to=2025-01-31

# Tickets (público):
https://tu-sitio.com/wp-json/chat-ia-mobile/v1/public/tickets
```

### Problemas comunes

| Error | Solución |
|-------|----------|
| `CERTIFICATE_VERIFY_FAILED` | Usar HTTPS o configurar `android:usesCleartextTraffic="true"` |
| `Connection refused` | Verificar URL y puerto, firewall |
| `401 Unauthorized` | Token expirado, hacer login de nuevo |
| `404 Not Found` | Plugin no activo o endpoint incorrecto |
| `Timeout` | Aumentar timeout en app_config.dart |

## Soporte

Desarrollado por **Gailu WZ Microcoop**
- Email: info@gailu.net
- Tel: +34 690 390 018
