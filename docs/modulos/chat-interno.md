# Módulo: Chat Interno

> Sistema de mensajería privada uno a uno

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `chat_interno` |
| **Versión** | 1.0.0+ |
| **Categoría** | Comunicación / Social |
| **Disponible en App** | Ambas (cliente y admin) |

### Principios Gailu

- `comunicacion` - Comunicación directa entre miembros
- `comunidad` - Fortalecer lazos comunitarios
- `privacidad` - Mensajería privada y segura

---

## Descripción

Sistema completo de mensajería privada uno a uno entre miembros de la comunidad. Incluye soporte para archivos adjuntos, notas de voz, indicadores de escritura, estados de conexión, edición/eliminación de mensajes y opcionalmente cifrado E2E.

### Características Principales

- **Conversaciones 1:1**: Chat privado entre dos usuarios
- **Adjuntos**: Imágenes, archivos, audio
- **Estados**: Online, ausente, ocupado, offline
- **Typing Indicator**: Ver cuando el otro escribe
- **Edición**: Editar mensajes enviados (tiempo limitado)
- **Eliminación**: Eliminar mensajes para uno o ambos
- **Bloqueo**: Bloquear usuarios
- **Búsqueda**: Búsqueda fulltext en mensajes
- **Cifrado E2E**: Opcionalmente con encriptación extremo a extremo

---

## Tablas de Base de Datos

### `{prefix}_flavor_chat_conversaciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `tipo` | enum | `individual`, `soporte` |
| `estado` | enum | `activa`, `archivada`, `bloqueada` |
| `ultimo_mensaje_id` | bigint | ID del último mensaje |
| `fecha_creacion` | datetime | Fecha de creación |
| `fecha_actualizacion` | datetime | Última actualización |

### `{prefix}_flavor_chat_mensajes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `conversacion_id` | bigint | Conversación padre |
| `remitente_id` | bigint | Usuario que envía |
| `mensaje` | text | Contenido del mensaje |
| `mensaje_html` | text | Versión HTML |
| `tipo` | enum | `texto`, `imagen`, `archivo`, `audio`, `ubicacion`, `sistema` |
| `adjunto_url` | varchar(500) | URL del adjunto |
| `adjunto_nombre` | varchar(255) | Nombre del archivo |
| `adjunto_tamano` | bigint | Tamaño en bytes |
| `adjunto_tipo` | varchar(100) | MIME type |
| `responde_a` | bigint | ID mensaje al que responde |
| `leido` | tinyint | Marcado como leído |
| `fecha_lectura` | datetime | Fecha de lectura |
| `editado` | tinyint | Mensaje editado |
| `fecha_edicion` | datetime | Fecha de edición |
| `eliminado` | tinyint | Mensaje eliminado |
| `eliminado_para` | text | JSON de user_ids |
| `cifrado` | tinyint | Mensaje cifrado E2E |
| `ciphertext` | text | Texto cifrado |
| `e2e_version` | int | Versión del protocolo |
| `fecha_creacion` | datetime | Fecha de envío |

**Índice FULLTEXT**: `mensaje`

### `{prefix}_flavor_chat_participantes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `conversacion_id` | bigint | Conversación |
| `usuario_id` | bigint | Usuario participante |
| `rol` | enum | `participante`, `soporte`, `admin` |
| `silenciado` | tinyint | Notificaciones silenciadas |
| `archivado` | tinyint | Conversación archivada |
| `ultimo_mensaje_leido` | bigint | Último mensaje leído |
| `escribiendo` | tinyint | Está escribiendo |
| `escribiendo_timestamp` | datetime | Timestamp de typing |
| `notificaciones` | enum | `todas`, `menciones`, `ninguna` |
| `fecha_ingreso` | datetime | Fecha de unión |

**Unique Key**: `conversacion_id`, `usuario_id`

### `{prefix}_flavor_chat_bloqueados`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario que bloquea |
| `bloqueado_id` | bigint | Usuario bloqueado |
| `motivo` | varchar(255) | Motivo del bloqueo |
| `fecha_creacion` | datetime | Fecha de bloqueo |

### `{prefix}_flavor_chat_estados_usuario`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario |
| `estado` | enum | `online`, `ausente`, `ocupado`, `offline` |
| `mensaje_estado` | varchar(255) | Mensaje de estado personalizado |
| `ultima_actividad` | datetime | Última actividad |

---

## Estados

### Estados de Usuario

| Estado | Descripción |
|--------|-------------|
| `online` | Usuario conectado |
| `ausente` | Ausente temporalmente |
| `ocupado` | No molestar |
| `offline` | Desconectado |

### Tipos de Mensaje

| Tipo | Descripción |
|------|-------------|
| `texto` | Mensaje de texto |
| `imagen` | Imagen adjunta |
| `archivo` | Archivo adjunto |
| `audio` | Nota de voz |
| `ubicacion` | Ubicación compartida |
| `sistema` | Mensaje del sistema |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `disponible_app` | string | `ambas` | Disponibilidad en app |
| `permite_chat_no_contactos` | bool | true | Chat con no-contactos |
| `requiere_verificacion_usuario` | bool | false | Requiere verificación |
| `permite_archivos` | bool | true | Permitir adjuntos |
| `permite_notas_voz` | bool | true | Permitir audio |
| `permite_videollamadas` | bool | false | Permitir videollamadas |
| `max_tamano_archivo_mb` | int | 25 | Tamaño máximo MB |
| `eliminar_mensajes_antiguos_dias` | int | 0 | Auto-eliminar (0=nunca) |
| `encriptacion_e2e` | bool | false | Cifrado E2E |
| `notificaciones_push` | bool | true | Push notifications |
| `mensajes_por_pagina` | int | 50 | Paginación |
| `permite_editar_mensajes` | bool | true | Permitir edición |
| `tiempo_edicion_minutos` | int | 15 | Tiempo límite edición |
| `permite_eliminar_mensajes` | bool | true | Permitir eliminación |
| `mostrar_estado_conexion` | bool | true | Mostrar estado online |
| `mostrar_typing_indicator` | bool | true | Mostrar "escribiendo..." |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `flavor_chat_interno_conversaciones` | Obtener conversaciones | Usuario |
| `flavor_chat_interno_iniciar` | Iniciar conversación | Usuario |
| `flavor_chat_interno_mensajes` | Obtener mensajes | Usuario |
| `flavor_chat_interno_enviar` | Enviar mensaje | Usuario |
| `flavor_chat_interno_marcar_leido` | Marcar como leído | Usuario |
| `flavor_chat_interno_typing` | Indicador escribiendo | Usuario |
| `flavor_chat_interno_buscar` | Buscar mensajes | Usuario |
| `flavor_chat_interno_archivar` | Archivar conversación | Usuario |
| `flavor_chat_interno_silenciar` | Silenciar conversación | Usuario |
| `flavor_chat_interno_eliminar_msg` | Eliminar mensaje | Usuario |
| `flavor_chat_interno_editar_msg` | Editar mensaje | Usuario |
| `flavor_chat_interno_bloquear` | Bloquear usuario | Usuario |
| `flavor_chat_interno_desbloquear` | Desbloquear usuario | Usuario |
| `flavor_chat_interno_upload` | Subir archivo | Usuario |
| `flavor_chat_interno_usuarios` | Buscar usuarios | Usuario |
| `flavor_chat_interno_poll` | Poll nuevos mensajes | Usuario |
| `flavor_chat_interno_estado` | Actualizar estado | Usuario |
| `flavor_chat_interno_info_usuario` | Info de usuario | Usuario |

---

## Shortcodes

El módulo expone shortcodes a través del Frontend Controller:

| Shortcode | Descripción |
|-----------|-------------|
| `[chat_interno]` | Widget de chat completo |
| `[chat_interno_lista]` | Lista de conversaciones |
| `[chat_interno_conversacion]` | Una conversación específica |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_chat_interno_limpieza` | Diario | Limpieza de mensajes antiguos |

---

## Hooks

### Acciones de Login/Logout

```php
// Marcar usuario online al hacer login
add_action('wp_login', [$this, 'marcar_usuario_online'], 10, 2);

// Marcar offline al cerrar sesión
add_action('wp_logout', [$this, 'marcar_usuario_offline']);
```

### Dashboard Integration

```php
// Añadir tab en dashboard de usuario
add_filter('flavor_user_dashboard_tabs', [$this, 'add_dashboard_tab']);
```

---

## Cifrado E2E (Opcional)

Cuando `encriptacion_e2e` está activo:

- Los mensajes se cifran en el cliente antes de enviar
- Solo el destinatario puede descifrar
- Tablas adicionales para claves públicas
- Protocolo Double Ratchet compatible
- Mensajes legacy marcados como `legacy_plaintext`

---

## Frontend JavaScript

```javascript
// Localización disponible
flavorChatInterno = {
    ajaxUrl: 'admin-ajax.php',
    nonce: 'xxx',
    userId: 123,
    strings: {
        // Traducciones
    }
}
```

---

## Permisos

| Capability | Descripción |
|------------|-------------|
| Usuario logueado | Usar chat, enviar mensajes |
| Cualquier usuario | Puede ser contactado (configurable) |

---

## Notas de Implementación

- Usa polling para actualizaciones en tiempo real (configurable)
- Los mensajes eliminados se marcan, no se borran físicamente
- La edición tiene límite de tiempo configurable
- El bloqueo es bidireccional (ninguno puede escribir al otro)
- Los estados se actualizan automáticamente con la actividad
- Soporte para responder a mensajes específicos (threads)
- Búsqueda fulltext en contenido de mensajes
