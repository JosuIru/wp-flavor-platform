# API de Manifiesto de Apps v2.0

## Descripción

La API de Manifiesto (`/flavor-app/v2/`) es un endpoint unificado que consolida toda la configuración necesaria para las apps móviles Flutter. Reemplaza múltiples llamadas a diferentes endpoints con una sola que incluye:

- Versionado de configuración con checksum
- Validación de módulos con dependencias
- Sincronización bidireccional
- Confirmación de aplicación de cambios

## Endpoints

### GET /flavor-app/v2/manifest

Obtiene el manifiesto completo de la app.

**Parámetros:**
- `refresh` (opcional): Si es `1`, ignora la caché

**Respuesta:**
```json
{
  "version": "v2.0-20260321-abc12345",
  "schema_version": "2.0",
  "checksum": "sha256:...",
  "generated_at": "2026-03-21T10:00:00+00:00",
  "site": {
    "url": "https://mi-sitio.com",
    "name": "Mi Comunidad",
    "description": "Plataforma comunitaria",
    "language": "es_ES",
    "timezone": "Europe/Madrid"
  },
  "branding": {
    "app_name": "Mi App",
    "app_id": "com.micomunidad.app",
    "logo_url": "https://...",
    "developer": { "name": "...", "email": "..." },
    "legal": { "privacy_url": "...", "terms_url": "..." }
  },
  "theme": {
    "mode": "system",
    "light": { "primary": "#6366f1", ... },
    "dark": { "primary": "#818cf8", ... }
  },
  "modules": {
    "active": [
      { "id": "eventos", "name": "Eventos", "icon": "calendar_today", ... }
    ],
    "total": 5,
    "available": 40
  },
  "navigation": {
    "style": "default",
    "bottom_tabs": [...],
    "drawer": [...],
    "show_home": true
  },
  "features": {
    "push_notifications": false,
    "offline_mode": true,
    "dark_mode": true,
    "multi_language": false
  },
  "build": {
    "version_name": "1.0.0",
    "version_code": "1",
    "android": { "min_sdk": "21", ... },
    "ios": { "min_version": "12.0", ... }
  },
  "api_endpoints": {
    "manifest": "https://.../wp-json/flavor-app/v2/manifest",
    "sync": "https://.../wp-json/flavor-app/v2/sync/confirm",
    "chat": "https://.../wp-json/unified-api/v1/chat"
  }
}
```

### GET /flavor-app/v2/manifest/check

Verificación rápida de versión sin descargar todo el manifiesto.

**Parámetros:**
- `version`: Versión actual del cliente
- `checksum`: Checksum actual del cliente

**Respuesta:**
```json
{
  "needs_update": true,
  "reason": "version_mismatch",
  "current_version": "v2.0-20260321-abc12345",
  "client_version": "v2.0-20260320-def67890"
}
```

### POST /flavor-app/v2/sync/confirm

La app confirma que aplicó una configuración.

**Body:**
```json
{
  "device_id": "device_123456789",
  "config_version": "v2.0-20260321-abc12345",
  "platform": "flutter_mobile",
  "app_version": "1.0.0"
}
```

**Respuesta:**
```json
{
  "success": true,
  "device_id": "device_123456789",
  "confirmed_at": "2026-03-21T10:00:00+00:00"
}
```

### GET /flavor-app/v2/sync/status (Protegido)

Estado de sincronización de todos los dispositivos.

**Requiere:** Header `X-VBP-Key`

**Respuesta:**
```json
{
  "current_version": "v2.0-20260321-abc12345",
  "stats": {
    "total_devices": 15,
    "up_to_date": 12,
    "needs_update": 3,
    "by_platform": { "flutter_mobile": 10, "flutter_web": 5 },
    "last_sync": "2026-03-21T09:55:00+00:00"
  },
  "devices": [
    {
      "device_id": "device_123",
      "platform": "flutter_mobile",
      "config_version": "v2.0-20260321-abc12345",
      "is_current": true,
      "synced_at": "2026-03-21T09:55:00+00:00"
    }
  ]
}
```

## Validación de Módulos

### POST /flavor-app/v2/modules/validate (Protegido)

Valida una lista de módulos incluyendo dependencias y conflictos.

**Body:**
```json
{
  "modules": ["grupos-consumo", "eventos", "marketplace"]
}
```

**Respuesta:**
```json
{
  "valid": false,
  "modules": ["grupos-consumo", "eventos", "marketplace"],
  "errors": [
    {
      "type": "missing_dependency",
      "module": "grupos-consumo",
      "dependency": "socios",
      "message": "Módulo 'grupos-consumo' requiere 'socios'"
    }
  ],
  "warnings": [
    {
      "type": "too_many_modules",
      "count": 18,
      "max": 15,
      "message": "Se recomienda no activar más de 15 módulos"
    }
  ]
}
```

### GET /flavor-app/v2/modules/dependencies (Protegido)

Obtiene el mapa completo de dependencias.

**Respuesta:**
```json
{
  "dependencies": {
    "grupos-consumo": ["socios"],
    "crowdfunding": ["socios"],
    "cursos": ["socios"],
    "presupuestos-participativos": ["socios", "transparencia"]
  },
  "conflicts": {
    "chat_interno": ["chat_grupos"]
  },
  "max_recommended": 15
}
```

### POST /flavor-app/v2/modules/resolve (Protegido)

Resuelve dependencias automáticamente.

**Body:**
```json
{
  "modules": ["grupos-consumo", "cursos"]
}
```

**Respuesta:**
```json
{
  "original_modules": ["grupos-consumo", "cursos"],
  "resolved_modules": ["grupos-consumo", "cursos", "socios"],
  "added_dependencies": ["socios"],
  "total": 3
}
```

## Configuración Rápida

### POST /flavor-app/v2/setup (Protegido)

Configura la app en una sola llamada.

**Body:**
```json
{
  "modules": ["eventos", "socios", "marketplace"],
  "auto_resolve_dependencies": true,
  "theme_preset": "emerald-green",
  "branding": {
    "app_name": "Mi Cooperativa",
    "app_description": "Plataforma de consumo responsable"
  }
}
```

**Respuesta:**
```json
{
  "success": true,
  "new_version": "v2.0-20260321-xyz98765",
  "results": {
    "modules": ["eventos", "socios", "marketplace"],
    "modules_resolved": [],
    "theme": { "preset": "emerald-green" },
    "branding": { "app_name": "Mi Cooperativa", ... }
  },
  "errors": {}
}
```

## Dependencias de Módulos

| Módulo | Requiere |
|--------|----------|
| `grupos-consumo` | `socios` |
| `crowdfunding` | `socios` |
| `biblioteca` | `socios` |
| `cursos` | `socios` |
| `talleres` | `socios` |
| `presupuestos-participativos` | `socios`, `transparencia` |
| `banco-tiempo` | `socios` |
| `energia-comunitaria` | `socios`, `transparencia` |
| `huertos-urbanos` | `socios`, `reservas` |
| `carpooling` | `socios` |
| `bicicletas-compartidas` | `reservas` |
| `espacios-comunes` | `reservas` |
| `circulos-cuidados` | `socios`, `comunidades` |
| `colectivos` | `socios`, `foros` |
| `economia-don` | `socios` |

## Presets de Tema

| Preset | Descripción |
|--------|-------------|
| `modern-blue` | Azul moderno |
| `emerald-green` | Verde esmeralda (cooperativas, ecología) |
| `purple-violet` | Violeta (cultura, creatividad) |
| `warm-orange` | Naranja cálido (comunidad) |
| `corporate` | Corporativo (instituciones) |
| `nature` | Naturaleza (grupos de consumo) |
| `minimal` | Minimalista |
| `rose` | Rosa |

## Flujo de Sincronización

```
┌─────────────┐                      ┌─────────────┐
│  App Flutter │                      │  WordPress  │
└──────┬──────┘                      └──────┬──────┘
       │                                     │
       │  1. GET /manifest/check             │
       │     (version, checksum)             │
       │────────────────────────────────────>│
       │                                     │
       │  2. { needs_update: true/false }    │
       │<────────────────────────────────────│
       │                                     │
       │  3. Si needs_update:                │
       │     GET /manifest                   │
       │────────────────────────────────────>│
       │                                     │
       │  4. { manifest completo }           │
       │<────────────────────────────────────│
       │                                     │
       │  5. Aplicar configuración           │
       │     localmente                      │
       │                                     │
       │  6. POST /sync/confirm              │
       │     (device_id, version)            │
       │────────────────────────────────────>│
       │                                     │
       │  7. { success: true }               │
       │<────────────────────────────────────│
       │                                     │
```

## Uso desde Flutter

```dart
import 'package:flavor_app/core/services/app_manifest_service.dart';

// Inicializar
final manifestService = AppManifestService();
await manifestService.initialize();

// Verificar si hay actualizaciones
final checkResult = await manifestService.checkForUpdates();
if (checkResult.needsUpdate) {
  print('Nueva versión disponible: ${checkResult.currentVersion}');
}

// Sincronizar con sitio
final result = await manifestService.syncWithSite('https://mi-sitio.com');
if (result.success) {
  print('Sincronizado: ${result.siteName} v${result.version}');
}

// Obtener módulos activos
final modules = manifestService.getActiveModules();

// Obtener ThemeData
final theme = manifestService.getThemeData(brightness: Brightness.light);
```

## QR Code Setup Rápido

### POST /flavor-app/v2/qr/generate (Protegido)

Genera un código QR para configurar la app rápidamente.

**Body:**
```json
{
  "expiration": 24,
  "include_modules": true
}
```

**Respuesta:**
```json
{
  "success": true,
  "code": "abc123xyz456",
  "payload": "eyJ2IjoxLCJjb2RlIjoiYWJjMTIzeHl6NDU2IiwidXJsIjoiaHR0cHM6Ly...",
  "qr_url": "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=...",
  "expires_at": "2026-03-22T10:00:00+00:00",
  "site": {
    "name": "Mi Comunidad",
    "url": "https://mi-sitio.com"
  },
  "instructions": {
    "es": "Escanea este código QR con la app Flavor para conectar automáticamente.",
    "en": "Scan this QR code with the Flavor app to connect automatically.",
    "eu": "Eskaneatu QR kode hau Flavor aplikazioarekin automatikoki konektatzeko."
  }
}
```

### GET /flavor-app/v2/qr/data (Protegido)

Obtiene datos de un QR existente o genera uno nuevo.

**Parámetros:**
- `code` (opcional): Código del QR a consultar

**Respuesta:**
```json
{
  "success": true,
  "code": "abc123xyz456",
  "payload": "eyJ2IjoxLC...",
  "expires_at": "2026-03-22T10:00:00+00:00",
  "qr_content": "https://site.com/wp-json/flavor-app/v2/qr/validate?action=flavor_app_setup&payload=..."
}
```

### POST /flavor-app/v2/qr/validate (Público)

La app valida un código QR escaneado.

**Body:**
```json
{
  "payload": "eyJ2IjoxLCJjb2RlIjoiYWJjMTIzeHl6NDU2IiwidXJsIjoiaHR0cHM6Ly...",
  "device_id": "flutter_1234567890"
}
```

**Respuesta (válido):**
```json
{
  "valid": true,
  "site": {
    "url": "https://mi-sitio.com",
    "name": "Mi Comunidad",
    "description": "Plataforma comunitaria",
    "api_base": "https://mi-sitio.com/wp-json/flavor-app/v2"
  },
  "manifest_url": "https://mi-sitio.com/wp-json/flavor-app/v2/manifest",
  "modules": ["eventos", "socios", "marketplace"],
  "branding": { ... },
  "theme": { ... },
  "auto_connect": true,
  "device_registered": true
}
```

**Respuesta (inválido):**
```json
{
  "valid": false,
  "error": "QR code has expired"
}
```

## Flujo de Setup con QR

```
┌─────────────┐                      ┌─────────────┐
│  Admin WP   │                      │  App Flutter │
└──────┬──────┘                      └──────┬──────┘
       │                                     │
       │  1. POST /qr/generate               │
       │     (expiration: 24)                │
       │<────────────────────────────────────│
       │                                     │
       │  2. { qr_url, code, payload }       │
       │────────────────────────────────────>│
       │                                     │
       │  3. Mostrar QR en pantalla          │
       │     (o descargar imagen)            │
       │                                     │
       │                                     │
       │         [Usuario escanea QR]        │
       │                                     │
       │                                     │
       │  4. POST /qr/validate               │
       │     (payload, device_id)            │
       │<────────────────────────────────────│
       │                                     │
       │  5. { valid: true, site, modules }  │
       │────────────────────────────────────>│
       │                                     │
       │  6. App configura automáticamente   │
       │     y descarga manifest completo    │
       │                                     │
```

## Uso desde Flutter

```dart
import 'package:flavor_app/core/services/qr_setup_service.dart';

// Procesar QR escaneado
final result = await QrSetupService.processQrCode(
  scannedContent,
  deviceId: QrSetupService.generateDeviceId(),
);

if (result.isValid) {
  print('Conectado a: ${result.siteName}');
  print('URL: ${result.siteUrl}');
  print('Módulos: ${result.modules}');

  // Configurar la app con los datos recibidos
  await configureApp(result);
} else {
  print('Error: ${result.error}');
}
```

## Webhooks

El sistema de webhooks permite notificar a sistemas externos cuando cambia la configuración de la app.

### GET /flavor-app/v2/webhooks (Protegido)

Lista los webhooks configurados.

**Respuesta:**
```json
{
  "webhooks": [
    {
      "id": "wh_abc123xyz456",
      "name": "Notificación Slack",
      "url": "https://hooks.slack.com/...",
      "events": ["config.updated", "modules.changed"],
      "secret": "***",
      "active": true,
      "created_at": "2026-03-21T10:00:00+00:00",
      "last_called": "2026-03-21T12:00:00+00:00",
      "last_status": "success"
    }
  ],
  "total": 1,
  "events": {
    "config.updated": "Configuración de app actualizada",
    "modules.changed": "Módulos activos cambiaron",
    "theme.changed": "Tema/colores cambiaron",
    "branding.changed": "Branding actualizado",
    "manifest.updated": "Manifiesto regenerado",
    "device.connected": "Nuevo dispositivo conectado",
    "device.synced": "Dispositivo sincronizado",
    "qr.generated": "Código QR generado",
    "qr.used": "Código QR utilizado",
    "test": "Evento de prueba",
    "*": "Todos los eventos"
  }
}
```

### POST /flavor-app/v2/webhooks (Protegido)

Registra un nuevo webhook.

**Body:**
```json
{
  "name": "Notificación Slack",
  "url": "https://hooks.slack.com/services/xxx/yyy/zzz",
  "events": ["config.updated", "modules.changed"],
  "secret": "mi_secret_opcional"
}
```

**Respuesta:**
```json
{
  "success": true,
  "id": "wh_abc123xyz456",
  "secret": "generated_secret_if_not_provided",
  "message": "Webhook registrado correctamente"
}
```

### DELETE /flavor-app/v2/webhooks/{id} (Protegido)

Elimina un webhook.

**Respuesta:**
```json
{
  "success": true,
  "message": "Webhook eliminado correctamente"
}
```

### POST /flavor-app/v2/webhooks/{id}/test (Protegido)

Envía un evento de prueba al webhook.

**Respuesta:**
```json
{
  "success": true,
  "status_code": 200,
  "response": "OK"
}
```

### GET /flavor-app/v2/webhooks/history (Protegido)

Obtiene el historial de envíos de webhooks.

**Parámetros:**
- `limit` (opcional): Número máximo de registros (default: 50, max: 200)

**Respuesta:**
```json
{
  "history": [
    {
      "webhook_name": "Notificación Slack",
      "webhook_url": "https://hooks.slack.com/...",
      "event": "config.updated",
      "success": true,
      "status_code": 200,
      "timestamp": "2026-03-21T12:00:00+00:00"
    }
  ],
  "total": 1
}
```

### Formato del Payload de Webhook

Cada webhook recibe un POST con el siguiente formato:

```json
{
  "event": "config.updated",
  "site_url": "https://mi-sitio.com",
  "site_name": "Mi Comunidad",
  "timestamp": "2026-03-21T12:00:00+00:00",
  "data": {
    "changes": {
      "modules": {
        "added": ["eventos"],
        "removed": []
      }
    },
    "version": "v2.0-20260321-abc12345"
  }
}
```

### Headers de Seguridad

```
Content-Type: application/json
X-Flavor-Event: config.updated
X-Flavor-Signature: hmac_sha256_signature
X-Flavor-Timestamp: 1711015200
User-Agent: Flavor-Webhook/1.0
```

Para verificar la firma en tu servidor:
```python
import hmac
import hashlib

def verify_signature(payload, signature, secret):
    expected = hmac.new(
        secret.encode(),
        payload.encode(),
        hashlib.sha256
    ).hexdigest()
    return hmac.compare_digest(expected, signature)
```

## Push Notifications (Firebase Cloud Messaging)

### GET /flavor-app/v2/push/status (Protegido)

Obtiene el estado de configuración de push notifications.

**Respuesta:**
```json
{
  "enabled": true,
  "configured": true,
  "provider": "firebase",
  "devices": {
    "total": 25,
    "android": 18,
    "ios": 7
  },
  "topics": {
    "all": "Todos los usuarios",
    "news": "Noticias y novedades",
    "events": "Eventos",
    "updates": "Actualizaciones de la app",
    "marketplace": "Ofertas del marketplace",
    "community": "Actividad de la comunidad"
  },
  "config": {
    "project_id": "my-firebase-project"
  }
}
```

### POST /flavor-app/v2/push/config (Protegido)

Configura las credenciales de Firebase.

**Body:**
```json
{
  "server_key": "AAAA...:APA91b...",
  "project_id": "my-firebase-project"
}
```

### POST /flavor-app/v2/push/register (Público)

Registra un dispositivo para recibir notificaciones.

**Body:**
```json
{
  "token": "fcm_token_here",
  "device_id": "device_unique_id",
  "platform": "android",
  "app_version": "1.0.0",
  "user_id": 123
}
```

**Respuesta:**
```json
{
  "success": true,
  "device_key": "md5_hash_of_token",
  "message": "Dispositivo registrado para notificaciones"
}
```

### POST /flavor-app/v2/push/send (Protegido)

Envía notificación a dispositivos específicos.

**Body:**
```json
{
  "tokens": ["token1", "token2"],
  "title": "Nueva actualización",
  "body": "Hay nuevas funciones disponibles",
  "data": {"action": "open_updates"},
  "image": "https://site.com/image.png"
}
```

### POST /flavor-app/v2/push/send/topic (Protegido)

Envía notificación a un topic.

**Body:**
```json
{
  "topic": "events",
  "title": "Nuevo evento",
  "body": "Se ha programado un nuevo evento para mañana"
}
```

### POST /flavor-app/v2/push/send/broadcast (Protegido)

Envía notificación a todos los dispositivos.

**Body:**
```json
{
  "title": "Mantenimiento programado",
  "body": "La app estará en mantenimiento de 2:00 a 4:00",
  "platform": "android"
}
```

### POST /flavor-app/v2/push/topics/subscribe (Público)

Suscribe dispositivo a un topic.

**Body:**
```json
{
  "token": "fcm_token",
  "topic": "events"
}
```

### GET /flavor-app/v2/push/history (Protegido)

Obtiene historial de notificaciones enviadas.

**Respuesta:**
```json
{
  "history": [
    {
      "target": "broadcast",
      "title": "Nueva actualización",
      "body": "Hay nuevas funciones...",
      "devices": 25,
      "success": true,
      "timestamp": "2026-03-21T12:00:00+00:00"
    }
  ],
  "total": 50
}
```

## Uso desde Flutter - Push Notifications

```dart
import 'package:flavor_app/core/services/push_notification_service.dart';

// Crear servicio
final pushService = PushNotificationService(apiClient: apiClient);
await pushService.initialize();

// Registrar token FCM (llamar cuando Firebase devuelve el token)
final result = await pushService.updateToken(fcmToken);
if (result.success) {
  print('Dispositivo registrado: ${result.deviceKey}');
}

// Suscribirse a topics
await pushService.subscribeToTopic('events');
await pushService.subscribeToTopic('marketplace');

// Listener para notificaciones
pushService.addNotificationListener((notification) {
  print('Notificación: ${notification.title}');
  // Mostrar notificación local o navegar
});

// Verificar estado
print('Topics suscritos: ${pushService.subscribedTopics}');
```

## Migración desde API v1

La API v2 es compatible con los endpoints existentes. La app puede:

1. Intentar `/flavor-app/v2/manifest` primero
2. Si falla, hacer fallback a `/app-discovery/v1/info`

Los datos son compatibles, solo cambia la estructura de algunos campos.

---

## API de Crash Reporting

Endpoints para reportar y gestionar crashes de la app móvil.

### POST /flavor-app/v2/crashes (Público)

Reporta un crash individual.

**Body:**
```json
{
  "error_type": "crash",
  "message": "NullPointerException in HomeScreen.build",
  "stack_trace": "package:app/screens/home.dart:42...",
  "device_id": "device_123",
  "app_version": "1.2.0",
  "platform": "android",
  "os_version": "Android 13",
  "device_model": "Samsung Galaxy S21",
  "context": {
    "current_screen": "HomeScreen",
    "user_id": "user_456"
  }
}
```

**Respuesta:**
```json
{
  "success": true,
  "crash_id": "crash_abc123",
  "message": "Crash reportado correctamente"
}
```

### POST /flavor-app/v2/crashes/batch (Público)

Reporta múltiples crashes (para envío offline).

**Body:**
```json
{
  "crashes": [
    {
      "error_type": "exception",
      "message": "Error parsing JSON",
      "device_id": "device_123",
      "timestamp": "2026-03-21T10:00:00Z"
    }
  ]
}
```

### GET /flavor-app/v2/crashes (Protegido)

Lista crashes con filtros y paginación.

**Parámetros:**
- `page` (default: 1)
- `per_page` (default: 50)
- `error_type`: crash, exception, anr, networkError
- `status`: open, resolved, ignored
- `platform`: android, ios
- `from_date`, `to_date`: filtro de fechas

**Respuesta:**
```json
{
  "success": true,
  "crashes": [...],
  "pagination": {
    "total": 150,
    "page": 1,
    "per_page": 50,
    "total_pages": 3
  }
}
```

### GET /flavor-app/v2/crashes/{id} (Protegido)

Obtiene detalle de un crash específico.

**Respuesta:**
```json
{
  "success": true,
  "crash": {
    "id": "crash_abc123",
    "error_type": "crash",
    "message": "...",
    "stack_trace": "...",
    "device_id": "...",
    "status": "open",
    "occurred_at": "2026-03-21T10:00:00Z"
  },
  "similar_count": 5,
  "similar_crashes": [...]
}
```

### POST /flavor-app/v2/crashes/{id}/resolve (Protegido)

Marca un crash como resuelto.

**Body:**
```json
{
  "resolution_notes": "Fixed in v1.2.1 - Issue #42"
}
```

### POST /flavor-app/v2/crashes/{id}/ignore (Protegido)

Marca un crash como ignorado.

### DELETE /flavor-app/v2/crashes/{id} (Protegido)

Elimina un crash.

### GET /flavor-app/v2/crashes/stats (Protegido)

Obtiene estadísticas de crashes.

**Parámetros:**
- `period`: 24h, 7d, 30d, 90d (default: 7d)

**Respuesta:**
```json
{
  "success": true,
  "period": "7d",
  "stats": {
    "total_crashes": 150,
    "open_crashes": 45,
    "resolved_crashes": 100,
    "ignored_crashes": 5,
    "by_type": {"crash": 50, "exception": 80, "anr": 20},
    "by_platform": {"android": 100, "ios": 50},
    "by_version": {"1.2.0": 80, "1.1.0": 70},
    "by_day": {"2026-03-21": 15, "2026-03-20": 22},
    "unique_devices": 75,
    "top_errors": {...}
  }
}
```

### GET /flavor-app/v2/crashes/groups (Protegido)

Agrupa crashes similares por fingerprint.

**Respuesta:**
```json
{
  "success": true,
  "groups": [
    {
      "fingerprint": "abc123",
      "message": "NullPointerException in HomeScreen",
      "error_type": "crash",
      "count": 25,
      "first_seen": "2026-03-15T10:00:00Z",
      "last_seen": "2026-03-21T15:30:00Z",
      "status": "open",
      "affected_versions": ["1.2.0", "1.1.0"],
      "affected_devices_count": 20
    }
  ],
  "total_groups": 15
}
```

### GET /flavor-app/v2/crashes/export (Protegido)

Exporta crashes en JSON o CSV.

**Parámetros:**
- `format`: json, csv (default: json)
- `from_date`, `to_date`: filtros opcionales

## Uso desde Flutter - Crash Reporting

```dart
import 'package:flavor_app/core/services/crash_reporting_service.dart';

// Crear servicio
final crashService = CrashReportingService(
  apiClient: apiClient,
  deviceId: deviceId,
);

// Inicializar (configura handlers globales)
await crashService.initialize(
  appVersion: '1.2.0',
  platform: 'android',
  osVersion: Platform.operatingSystemVersion,
  deviceModel: 'Samsung Galaxy S21',
);

// Establecer usuario
crashService.setUserId('user_456');

// Registrar pantalla actual (para contexto)
crashService.setCurrentScreen('HomeScreen');

// Añadir breadcrumbs (migas de pan para debugging)
crashService.addBreadcrumb(
  'user_action',
  'Button clicked',
  data: {'button': 'submit'},
);

// Reportar error manualmente
try {
  await riskyOperation();
} catch (e, stack) {
  await crashService.reportError(e, stack, context: {
    'operation': 'riskyOperation',
  });
}

// Reportar ANR (Application Not Responding)
await crashService.reportANR(
  duration: Duration(seconds: 5),
  lastAction: 'Loading products',
);

// Reportar error de red
await crashService.reportNetworkError(
  '/api/products',
  500,
  'Internal server error',
);

// Wrapper para operaciones
final result = await captureErrors(
  crashService,
  () => fetchProducts(),
  operationName: 'fetchProducts',
);

// Enviar crashes pendientes (después de recuperar conexión)
final sentCount = await crashService.flushPendingCrashes();
print('Enviados $sentCount crashes pendientes');

// Ver crashes pendientes
print('Pendientes: ${crashService.pendingCrashesCount}');
```

### Breadcrumbs

Los breadcrumbs son un historial de acciones que ayudan a entender qué hizo el usuario antes del crash:

```dart
// Categorías predefinidas
crashService.addBreadcrumb(BreadcrumbCategories.navigation, 'Opened HomeScreen');
crashService.addBreadcrumb(BreadcrumbCategories.userAction, 'Tapped Submit');
crashService.addBreadcrumb(BreadcrumbCategories.network, 'API call to /products');
crashService.addBreadcrumb(BreadcrumbCategories.state, 'Cart updated', data: {'items': 5});
```

### Tipos de Error

```dart
enum ErrorType {
  crash,        // Fatal - app termina
  exception,    // No fatal - capturado
  anr,          // Application Not Responding
  networkError, // Errores de red
  assertion,    // Assertion failures
  widgetError,  // Errores de widgets Flutter
  platformError,// Errores de plataforma nativa
  unknown,      // No clasificado
}
```

### Configuración Avanzada

```dart
// Deshabilitar temporalmente
crashService.setEnabled(false);

// Añadir contexto global (disponible en todos los crashes)
crashService.setGlobalContext('environment', 'production');
crashService.setGlobalContext('feature_flags', {'dark_mode': true});

// Limpiar datos
await crashService.clearBreadcrumbs();
await crashService.clearPendingCrashes();
```
