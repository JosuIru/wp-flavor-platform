# Documentación Completa - Apps Móviles Chat IA

## Resumen Ejecutivo

Las apps móviles son **genéricas y multi-tenant**. Están diseñadas para funcionar con **cualquier sitio WordPress** que tenga el plugin instalado. La configuración del servidor se realiza en el primer uso.

---

## Personalización Rápida (White-Label)

### Opción 1: Autoconfiguración con IA (Recomendada)

El sistema analiza automáticamente el sitio y genera la configuración óptima:

```bash
cd mobile-apps
./personalizar-app.sh --from-url https://tu-sitio.com
flutter pub get
flutter build apk --flavor client -t lib/main_client.dart --release
```

La IA:
- Detecta el nombre del negocio
- Extrae colores del tema/logo
- Sugiere nombre de app optimizado
- Genera ID único para Google Play
- Configura deep links automáticamente

### Opción 2: Configuración Manual

1. Copiar `config.example.json` a `config.json`
2. Editar los valores
3. Ejecutar `./personalizar-app.sh`

```json
{
  "business_name": "Zoo de Mallorca",
  "app_id": "com.zoodemallorca.app",
  "client_app_name": "Zoo Mallorca",
  "colors": {
    "primary": "#4CAF50"
  }
}
```

### Endpoints de Autoconfiguración

| Endpoint | Descripción |
|----------|-------------|
| `GET /app-config/generate` | Genera config.json automáticamente con IA |
| `GET /app-config/preview` | Vista previa (solo admin) |
| `GET /site-content` | Contenido inteligente para pantalla Info |

---

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                    APPS FLUTTER                              │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐         ┌─────────────────┐           │
│  │   APP ADMIN     │         │   APP CLIENTE   │           │
│  │  (main_admin)   │  ←───→  │  (main_client)  │           │
│  │                 │  Deep   │                 │           │
│  │  - Dashboard    │  Links  │  - Chat IA      │           │
│  │  - Reservas     │         │  - Reservar     │           │
│  │  - Estadísticas │         │  - Mis Tickets  │           │
│  │  - Clientes     │         │  - Info         │           │
│  │  - QR Scanner   │         │                 │           │
│  └────────┬────────┘         └────────┬────────┘           │
│           │                           │                     │
│           └───────────┬───────────────┘                     │
│                       ▼                                     │
│              ┌─────────────────┐                           │
│              │   API CLIENT    │                           │
│              │  (Dio + Auth)   │                           │
│              └────────┬────────┘                           │
└───────────────────────┼─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    SERVIDOR WORDPRESS                        │
│                                                             │
│  /wp-json/chat-ia-mobile/v1/                               │
│  ├── auth/*          (login, verify)                       │
│  ├── chat/*          (sesiones, mensajes)                  │
│  ├── public/*        (info, disponibilidad, tickets)       │
│  ├── reservations/*  (check, prepare, add-to-cart)         │
│  ├── admin/*         (dashboard, reservas, clientes)       │
│  └── client/*        (mis reservas, config)                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Estado de Implementación de Features

### APP ADMIN

| Feature | Estado | Descripción |
|---------|--------|-------------|
| **Login** | ✅ Completo | Usuario/contraseña con JWT |
| **Dashboard** | ✅ Completo | KPIs, ingresos, reservas hoy |
| **Gestión Reservas** | ✅ Completo | Filtros, búsqueda, detalle |
| **Check-in QR** | ✅ Completo | Escaneo y validación |
| **Estadísticas** | ✅ Completo | Gráficos con fl_chart |
| **Vista Calendario** | ✅ Completo | Calendario mensual |
| **Clientes WooCommerce** | ✅ Completo | Listado y búsqueda |
| **Clientes Manuales** | ✅ Completo | CRUD completo |
| **Clientes Unificados** | ✅ Completo | WooCommerce + Manuales |
| **Notas de Cliente** | ✅ Completo | Por fecha |
| **Exportación CSV** | ✅ Completo | Reservas y clientes |
| **Chat IA Admin** | ✅ Completo | Chat con contexto admin |
| **Configuración Idioma** | ✅ Completo | Multi-idioma |
| **Configuración Servidor** | ✅ Completo | Cambiar URL servidor |
| **Deep Link a Cliente** | ✅ Completo | Abrir app cliente |
| **Push Notifications** | ⚠️ Preparado | Endpoint listo, falta Firebase |

### APP CLIENTE

| Feature | Estado | Descripción |
|---------|--------|-------------|
| **Setup Inicial** | ✅ Completo | URL manual o QR |
| **Chat IA** | ✅ Completo | Con sugerencias rápidas |
| **Calendario Reservas** | ✅ Completo | Selección de fecha |
| **Selector Tickets** | ✅ Completo | Por estado/día |
| **Carrito** | ✅ Completo | Añadir productos |
| **Checkout WebView** | ✅ Completo | WooCommerce checkout |
| **Mis Tickets (Billetera)** | ✅ Completo | Por email verificado |
| **Verificación Email** | ✅ Completo | Código de 6 dígitos por email |
| **QR en Tickets** | ✅ Completo | Código QR para check-in |
| **Info Screen** | ✅ Completo | Autoconfiguración desde servidor |
| **Servicios/Experiencias** | ✅ Completo | Carrusel horizontal |
| **Secciones de Contenido** | ✅ Completo | Páginas importantes |
| **Galería** | ✅ Completo | Imágenes del sitio |
| **Noticias/Blog** | ✅ Completo | Últimas publicaciones |
| **Horarios** | ✅ Completo | Texto de horarios |
| **Ubicación + Mapas** | ✅ Completo | Con direcciones |
| **Contacto** | ✅ Completo | Teléfono, WhatsApp, Email |
| **Redes Sociales** | ✅ Completo | Facebook, Instagram, etc. |
| **Tabs Dinámicos** | ✅ Completo | Configurables desde servidor |
| **Deep Link a Admin** | ✅ Completo | Si el usuario es admin |
| **Offline Tickets** | ⚠️ Parcial | Caché pero sin sync |
| **Push Notifications** | ⚠️ Preparado | Endpoint listo, falta Firebase |

---

## Archivos de Configuración para Personalización

### 1. build.gradle (Android)

**Ubicación:** `android/app/build.gradle`

```gradle
defaultConfig {
    applicationId = "com.TUDOMINIO.TUAPP"  // ← CAMBIAR
    minSdk = 21
    targetSdk = 34
}

productFlavors {
    admin {
        dimension "app"
        applicationIdSuffix ".admin"
        resValue "string", "app_name", "TU NOMBRE Admin"  // ← CAMBIAR
    }
    client {
        dimension "app"
        applicationIdSuffix ".client"
        resValue "string", "app_name", "TU NOMBRE"  // ← CAMBIAR
    }
}
```

### 2. app_config.dart

**Ubicación:** `lib/core/config/app_config.dart`

```dart
class AppConfig {
  // PERSONALIZAR ESTOS VALORES:
  static const String businessName = 'Zoo de Mallorca';
  static const String clientAppName = 'Zoo Mallorca';
  static const String adminAppName = 'Zoo Admin';

  static const String developerName = 'Zoo de Mallorca S.L.';
  static const String developerEmail = 'info@zoodemallorca.com';
  static const String developerPhone = '+34 971 123 456';

  static const String adminPackageName = 'com.zoodemallorca.app.admin';
  static const String clientPackageName = 'com.zoodemallorca.app.client';

  static const String deepLinkScheme = 'zoodemallorca';
}

class AppColors {
  // COLORES PERSONALIZADOS:
  static const int primaryValue = 0xFF4CAF50;     // Verde
  static const int secondaryValue = 0xFF8BC34A;   // Verde claro
  static const int accentValue = 0xFFFF9800;      // Naranja
}
```

### 3. AndroidManifest.xml (Deep Links)

**Ubicación:** `android/app/src/admin/AndroidManifest.xml` y `client/`

```xml
<intent-filter>
    <data android:scheme="zoodemallorca" android:host="admin"/>
    <!-- o host="client" para la app cliente -->
</intent-filter>

<queries>
    <package android:name="com.zoodemallorca.app.client"/>
    <!-- o .admin para la app cliente -->
</queries>
```

### 4. Assets (Logo e Iconos)

**Ubicación:** `assets/images/`

- `logo.svg` - Logo principal de la app

**Iconos de la app (Android):**
- `android/app/src/main/res/mipmap-*/ic_launcher.png`

Puedes generar iconos con: https://romannurik.github.io/AndroidAssetStudio/icons-launcher.html

---

## Resumen: Qué Cambiar para Personalizar

| Archivo | Qué cambiar |
|---------|-------------|
| `build.gradle` | applicationId, app_name |
| `app_config.dart` | businessName, colores, developerInfo, packageNames |
| `AndroidManifest.xml` | scheme en deep links, package en queries |
| `assets/images/logo.svg` | Logo de la empresa |
| `android/.../ic_launcher.png` | Iconos de la app |

**Total: 4-5 archivos, ~20 líneas de código**

---

## Compilación

```bash
# Instalar dependencias
flutter pub get

# Compilar Admin Release
flutter build apk --flavor admin -t lib/main_admin.dart --release

# Compilar Client Release
flutter build apk --flavor client -t lib/main_client.dart --release

# Para Google Play (AAB):
flutter build appbundle --flavor admin -t lib/main_admin.dart --release
flutter build appbundle --flavor client -t lib/main_client.dart --release
```

**Ubicación de APKs:**
- `build/app/outputs/flutter-apk/app-admin-release.apk`
- `build/app/outputs/flutter-apk/app-client-release.apk`

---

## Endpoints de la API

### Públicos (sin autenticación)
- `GET /site-info` - Nombre, logo, config
- `GET /site-content` - Contenido inteligente para Info screen
- `GET /public/business-info` - Teléfono, email, redes
- `GET /public/availability` - Disponibilidad
- `GET /public/tickets` - Tipos de ticket
- `GET /public/experiences` - Experiencias
- `GET /public/posts` - Blog
- `GET /public/updates` - Novedades

### Reservas
- `POST /reservations/check` - Verificar disponibilidad
- `POST /reservations/prepare` - Preparar reserva
- `POST /reservations/add-to-cart` - Añadir a carrito
- `GET /reservations/cart-url` - URL del carrito

### Cliente autenticado
- `GET /client/my-reservations` - Billetera de tickets (requiere email verificado)
- `GET /client/reservation/{code}` - Detalle por código
- `GET /client/config` - Config de tabs

### Verificación de Email
- `POST /client/email/send-code` - Enviar código de 6 dígitos al email
- `POST /client/email/verify-code` - Verificar código ingresado
- `GET /client/email/status` - Estado de verificación del dispositivo

### Admin
- `POST /auth/login` - Login
- `GET /auth/verify` - Verificar token
- `GET /admin/dashboard` - Dashboard
- `GET /admin/reservations` - Listado
- `POST /admin/reservations/{id}/checkin` - Check-in
- `GET /admin/stats` - Estadísticas
- `GET /admin/customers` - Clientes
- `GET /admin/manual-customers` - Clientes manuales
- `POST /admin/export/csv` - Exportar

---

## Dependencias Principales

| Paquete | Versión | Uso |
|---------|---------|-----|
| flutter_riverpod | ^2.4.9 | State management |
| dio | ^5.4.0 | HTTP client |
| flutter_secure_storage | ^9.0.0 | Tokens seguros |
| mobile_scanner | ^4.0.1 | Scanner QR |
| table_calendar | ^3.0.9 | Calendario |
| fl_chart | ^0.66.0 | Gráficos admin |
| webview_flutter | ^4.4.4 | Checkout |
| cached_network_image | ^3.3.1 | Caché imágenes |
| qr_flutter | ^4.1.0 | Generar QR |
| url_launcher | ^6.2.2 | Abrir URLs |
| map_launcher | ^3.1.0 | Abrir mapas |
| share_plus | ^7.2.1 | Compartir |
| uuid | ^4.2.1 | ID único de dispositivo |

---

## Verificación de Email (Billetera)

La billetera de tickets requiere verificación de email para vincular el dispositivo con las reservas del cliente. Esto previene que cualquier persona pueda ver las reservas de otro usuario simplemente ingresando su email.

### Flujo de Verificación

1. Usuario ingresa su email en la app
2. El servidor envía un código de 6 dígitos al email
3. Usuario ingresa el código en la app
4. Si el código es correcto, el dispositivo queda vinculado al email por 30 días
5. Las reservas del email se muestran en la billetera

### Seguridad

- Códigos válidos por 15 minutos
- Máximo 5 intentos por código
- Dispositivo identificado por UUID único
- Verificación almacenada en transients de WordPress

---

## Notas Importantes

1. **La app NO tiene URL hardcodeada** - Se configura en el primer uso
2. **El servidor devuelve la configuración de tabs** - Se puede personalizar qué pestañas mostrar
3. **Los colores del tema se pueden cambiar** - En `app_config.dart`
4. **El logo viene del servidor** - Endpoint `/site-info`
5. **Push notifications preparadas** - Falta integrar Firebase
6. **Deep links funcionan** - Entre apps admin y cliente
