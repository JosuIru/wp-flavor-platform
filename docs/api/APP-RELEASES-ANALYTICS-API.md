# API de Releases y Analytics de Apps

Documentación de los endpoints REST para gestión de releases de aplicaciones móviles y analytics.

## Base URL

```
/wp-json/flavor-app/v2/
```

## Autenticación

Todos los endpoints requieren autenticación JWT o el header `X-VBP-Key`.

```bash
Authorization: Bearer <jwt_token>
# o
X-VBP-Key: <tu-api-key>
```

---

## Releases API

### Listar Releases

```http
GET /releases
```

**Query Parameters:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| app_type | string | `admin` o `client` |
| platform | string | `android` o `ios` |
| channel | string | `alpha`, `beta`, `production` |
| status | string | `draft` o `published` |
| page | int | Página (default: 1) |
| per_page | int | Items por página (default: 20) |

**Response:**

```json
{
  "items": [
    {
      "id": 1,
      "version": "1.2.0",
      "build_number": 45,
      "app_type": "client",
      "platform": "android",
      "channel": "production",
      "changelog": "- Nueva funcionalidad X\n- Corrección de bugs",
      "file_name": "app-release-1.2.0.apk",
      "file_size": 45678901,
      "file_size_formatted": "43.5 MB",
      "file_hash": "sha256:abc123...",
      "download_url": "https://site.com/releases/app-1.2.0.apk",
      "min_os_version": "21",
      "is_mandatory": false,
      "is_published": true,
      "downloads": 1250,
      "created_at": "2024-03-20T10:30:00Z",
      "created_at_formatted": "20 Mar 2024",
      "published_at": "2024-03-21T09:00:00Z"
    }
  ],
  "total": 15,
  "page": 1,
  "per_page": 20
}
```

### Obtener Release

```http
GET /releases/{id}
```

### Crear Release

```http
POST /releases
```

**Request Body:**

```json
{
  "version": "1.2.0",
  "build_number": 45,
  "app_type": "client",
  "platform": "android",
  "channel": "beta",
  "changelog": "Cambios de esta versión...",
  "min_os_version": "21",
  "is_mandatory": false,
  "file_name": "app-release.apk",
  "file_size": 45678901,
  "file_hash": "sha256:..."
}
```

### Subir Archivo APK/IPA

```http
POST /releases/upload
Content-Type: multipart/form-data
```

**Form Data:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| apk_file | file | Archivo .apk, .aab o .ipa |

**Response:**

```json
{
  "success": true,
  "data": {
    "file_name": "app-release-1.2.0.apk",
    "file_size": 45678901,
    "file_hash": "sha256:abc123def456...",
    "file_url": "/uploads/releases/app-release-1.2.0.apk"
  }
}
```

### Publicar Release

```http
POST /releases/{id}/publish
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "version": "1.2.0",
    "published_at": "2024-03-21T09:00:00Z"
  }
}
```

### Despublicar Release

```http
POST /releases/{id}/unpublish
```

### Eliminar Release

```http
DELETE /releases/{id}
```

### Verificar Actualización (Público)

Endpoint público para que las apps verifiquen actualizaciones.

```http
GET /releases/check-update
```

**Query Parameters:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| app_type | string | `admin` o `client` |
| platform | string | `android` o `ios` |
| current_version | string | Versión actual instalada |
| current_build | int | Build number actual |

**Response:**

```json
{
  "update_available": true,
  "latest_version": "1.3.0",
  "latest_build": 50,
  "download_url": "https://site.com/releases/app-1.3.0.apk",
  "changelog": "- Nueva funcionalidad\n- Mejoras de rendimiento",
  "is_mandatory": true,
  "file_size": 48000000,
  "min_os_version": "21"
}
```

---

## Analytics API

### Obtener Resumen

```http
GET /analytics/summary
```

**Query Parameters:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| period | string | `today`, `week`, `month`, `year` |
| app_type | string | Filtrar por tipo de app |

**Response:**

```json
{
  "active_users": 1250,
  "active_users_change": 12.5,
  "total_sessions": 8450,
  "sessions_change": 8.2,
  "avg_session_duration": 342,
  "crashes": 23,
  "crash_rate": 0.27,
  "retention_7d": 45.2,
  "retention_30d": 28.7
}
```

### Obtener Timeline

```http
GET /analytics/timeline
```

**Query Parameters:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| metric | string | `users`, `sessions`, `events` |
| period | string | `7d`, `30d`, `90d` |
| granularity | string | `hour`, `day`, `week` |

**Response:**

```json
{
  "data": [
    {"date": "2024-03-14", "value": 1200},
    {"date": "2024-03-15", "value": 1350},
    {"date": "2024-03-16", "value": 1180}
  ],
  "total": 15240,
  "average": 1270
}
```

### Obtener Uso de Módulos

```http
GET /analytics/modules
```

**Response:**

```json
{
  "modules": [
    {"module": "eventos", "sessions": 3500, "users": 890, "percentage": 28.5},
    {"module": "marketplace", "sessions": 2800, "users": 720, "percentage": 22.8},
    {"module": "socios", "sessions": 2100, "users": 650, "percentage": 17.1}
  ]
}
```

### Registrar Evento

```http
POST /analytics/event
```

**Request Body:**

```json
{
  "event_type": "screen_view",
  "event_name": "home_screen",
  "module": "general",
  "properties": {
    "source": "deep_link",
    "campaign": "spring_promo"
  },
  "session_id": "sess_abc123",
  "timestamp": "2024-03-20T10:30:00Z"
}
```

### Registrar Sesión

```http
POST /analytics/session
```

**Request Body:**

```json
{
  "action": "start",
  "session_id": "sess_abc123",
  "device_info": {
    "platform": "android",
    "os_version": "14",
    "app_version": "1.2.0",
    "device_model": "Pixel 7",
    "screen_size": "1080x2400"
  }
}
```

### Obtener Dispositivos

```http
GET /analytics/devices
```

**Response:**

```json
{
  "platforms": [
    {"platform": "android", "users": 8500, "percentage": 68},
    {"platform": "ios", "users": 4000, "percentage": 32}
  ],
  "versions": [
    {"version": "1.2.0", "users": 6500, "percentage": 52},
    {"version": "1.1.0", "users": 4500, "percentage": 36},
    {"version": "1.0.0", "users": 1500, "percentage": 12}
  ],
  "os_versions": [
    {"os": "Android 14", "users": 4500},
    {"os": "Android 13", "users": 3200},
    {"os": "iOS 17", "users": 3000}
  ]
}
```

---

## Push Notifications API

### Registrar Dispositivo

```http
POST /push/register
```

**Request Body:**

```json
{
  "token": "fcm_token_abc123...",
  "platform": "android",
  "device_id": "device_xyz",
  "app_version": "1.2.0"
}
```

### Suscribir a Tópico

```http
POST /push/subscribe
```

**Request Body:**

```json
{
  "token": "fcm_token_abc123...",
  "topic": "module_eventos"
}
```

### Desuscribir de Tópico

```http
POST /push/unsubscribe
```

### Obtener Notificaciones Pendientes

```http
GET /push/pending
```

**Response:**

```json
{
  "items": [
    {
      "id": "notif_123",
      "title": "Nuevo evento",
      "body": "Se ha publicado un nuevo evento...",
      "module": "eventos",
      "action": "view",
      "target_id": "456",
      "timestamp": "2024-03-20T10:30:00Z",
      "is_read": false
    }
  ],
  "unread_count": 5
}
```

### Marcar como Leída

```http
POST /push/{id}/read
```

### Marcar Todas como Leídas

```http
POST /push/read-all
```

---

## Crash Reporting API

### Reportar Crash

```http
POST /crashes
```

**Request Body:**

```json
{
  "error_type": "NullPointerException",
  "error_message": "Attempt to invoke method on null object",
  "stack_trace": "at com.example.app.MainActivity...",
  "app_version": "1.2.0",
  "platform": "android",
  "os_version": "14",
  "device_model": "Pixel 7",
  "timestamp": "2024-03-20T10:30:00Z",
  "user_id": "user_123",
  "breadcrumbs": [
    {"event": "app_start", "timestamp": "..."},
    {"event": "screen_view", "screen": "home", "timestamp": "..."}
  ]
}
```

### Listar Crashes

```http
GET /crashes
```

**Query Parameters:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| period | string | `24h`, `7d`, `30d` |
| status | string | `new`, `investigating`, `resolved` |
| app_version | string | Filtrar por versión |

---

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request - Parámetros inválidos |
| 401 | Unauthorized - Token inválido o expirado |
| 403 | Forbidden - Sin permisos |
| 404 | Not Found - Recurso no encontrado |
| 409 | Conflict - Versión ya existe |
| 413 | Payload Too Large - Archivo muy grande |
| 422 | Unprocessable Entity - Validación fallida |
| 429 | Too Many Requests - Rate limit excedido |
| 500 | Internal Server Error |

---

## Rate Limits

| Endpoint | Límite |
|----------|--------|
| GET /releases | 100/min |
| POST /releases | 10/min |
| POST /analytics/event | 1000/min |
| POST /crashes | 100/min |

---

## Webhooks

Se pueden configurar webhooks para eventos de releases:

- `release.created` - Nueva release creada
- `release.published` - Release publicada
- `release.downloaded` - Release descargada

Configurar en: **Admin > Apps > Configuración > Webhooks**
