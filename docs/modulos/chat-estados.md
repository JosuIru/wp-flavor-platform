# Módulo: Chat Estados

> Sistema de estados/stories efímeros tipo WhatsApp

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `chat_estados` |
| **Versión** | 1.5.0+ |
| **Categoría** | Comunicación / Social |
| **Disponible en App** | Ambas (cliente y admin) |

---

## Descripción

Sistema de publicaciones efímeras similar a WhatsApp Status o Instagram Stories. Los usuarios pueden publicar estados (texto, imagen, video) que desaparecen automáticamente después de 24 horas.

### Características Principales

- **Estados Efímeros**: Desaparecen tras 24 horas
- **Tipos de Contenido**: Texto, imagen, video, audio, ubicación
- **Privacidad**: Todos, excepto algunos, o solo algunos contactos
- **Visualizaciones**: Ver quién ha visto cada estado
- **Reacciones**: Emojis de reacción
- **Respuestas**: Mensajes directos como respuesta
- **Silenciar**: Silenciar estados de usuarios específicos

---

## Tipos de Estado

| Tipo | Descripción |
|------|-------------|
| `texto` | Texto con color de fondo personalizado |
| `imagen` | Imagen con texto opcional |
| `video` | Video corto (máx. 30 segundos) |
| `audio` | Nota de voz |
| `ubicacion` | Ubicación geográfica |

## Privacidades

| Privacidad | Descripción |
|------------|-------------|
| `todos` | Todos mis contactos |
| `contactos_excepto` | Mis contactos excepto los seleccionados |
| `solo_compartir` | Solo los contactos seleccionados |

---

## Tablas de Base de Datos

### `{prefix}_flavor_chat_estados`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Autor del estado |
| `tipo` | enum | Tipo de estado |
| `contenido` | text | Texto del estado |
| `media_url` | varchar(500) | URL del archivo multimedia |
| `media_thumbnail` | varchar(500) | URL del thumbnail |
| `duracion_media` | int | Duración en segundos (video/audio) |
| `color_fondo` | varchar(20) | Color de fondo (para texto) |
| `color_texto` | varchar(20) | Color del texto |
| `fuente` | varchar(50) | Tipografía |
| `ubicacion_lat` | decimal(10,8) | Latitud |
| `ubicacion_lng` | decimal(11,8) | Longitud |
| `ubicacion_nombre` | varchar(255) | Nombre del lugar |
| `privacidad` | enum | Nivel de privacidad |
| `usuarios_excluidos` | longtext | JSON de IDs excluidos |
| `usuarios_incluidos` | longtext | JSON de IDs incluidos |
| `visualizaciones_count` | int | Contador de vistas |
| `reacciones_count` | int | Contador de reacciones |
| `respuestas_count` | int | Contador de respuestas |
| `activo` | tinyint | Estado activo |
| `fecha_creacion` | datetime | Fecha de creación |
| `fecha_expiracion` | datetime | Fecha de expiración |

### `{prefix}_flavor_chat_estados_vistas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `estado_id` | bigint | Estado visualizado |
| `usuario_id` | bigint | Usuario que vio |
| `fecha_vista` | datetime | Fecha de visualización |

**Unique Key:** `estado_id`, `usuario_id`

### `{prefix}_flavor_chat_estados_reacciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `estado_id` | bigint | Estado |
| `usuario_id` | bigint | Usuario que reaccionó |
| `emoji` | varchar(50) | Emoji de reacción |
| `fecha` | datetime | Fecha de reacción |

**Unique Key:** `estado_id`, `usuario_id`

### `{prefix}_flavor_chat_estados_respuestas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `estado_id` | bigint | Estado |
| `usuario_id` | bigint | Usuario que responde |
| `mensaje` | text | Mensaje de respuesta |
| `leido` | tinyint | Respuesta leída |
| `fecha` | datetime | Fecha de respuesta |

### `{prefix}_flavor_chat_estados_silenciados`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario que silencia |
| `silenciado_id` | bigint | Usuario silenciado |
| `fecha` | datetime | Fecha |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `disponible_app` | string | `ambas` | Disponibilidad en app |
| `max_estados_dia` | int | 30 | Máximo estados por día |
| `max_duracion_video` | int | 30 | Segundos máx. de video |
| `max_tamano_imagen` | int | 5242880 | Bytes máx. imagen (5MB) |
| `max_tamano_video` | int | 16777216 | Bytes máx. video (16MB) |
| `permitir_respuestas` | bool | true | Permitir respuestas |
| `permitir_reacciones` | bool | true | Permitir reacciones |
| `mostrar_visualizaciones` | bool | true | Mostrar quién vio |
| `duracion_horas` | int | 24 | Horas de duración |
| `colores_fondo` | array | [...] | Paleta de colores |

### Colores de Fondo Predefinidos

```json
["#128C7E", "#25D366", "#075E54", "#34B7F1", "#E91E63", "#9C27B0", "#673AB7", "#3F51B5"]
```

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_estados]` | Visor de estados (carrusel) |
| `[flavor_estados_crear]` | Formulario para crear estado |
| `[flavor_estados_mis_estados]` | Mis estados activos |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `chat_estados_crear_estado` | Crear nuevo estado | Usuario |
| `chat_estados_obtener_estados` | Obtener estados de contactos | Usuario |
| `chat_estados_marcar_visto` | Marcar como visto | Usuario |
| `chat_estados_eliminar_estado` | Eliminar mi estado | Usuario |
| `chat_estados_silenciar_usuario` | Silenciar usuario | Usuario |
| `chat_estados_upload_media` | Subir archivo multimedia | Usuario |
| `chat_estados_obtener_visualizaciones` | Ver quién vio | Usuario |
| `chat_estados_reportar_estado` | Reportar estado | Usuario |
| `flavor_estados_reaccionar` | Añadir reacción | Usuario |
| `flavor_estados_responder` | Responder a estado | Usuario |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_limpiar_estados_expirados` | Cada hora | Eliminar estados expirados |

---

## REST API

El módulo registra endpoints REST para integración con apps móviles.

---

## Dashboard Widget

El módulo registra un widget que muestra:
- Mis estados activos
- Estados de contactos
- Estados pendientes de ver

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Estados | Ver estados de contactos | Sí |
| Crear | Crear nuevo estado | Sí |
| Mis Estados | Gestionar mis estados | Sí |

---

## Flujo de Uso

```
1. CREAR ESTADO
   Usuario crea estado con contenido
   ↓
2. PUBLICAR
   Estado visible para contactos según privacidad
   fecha_expiracion = NOW() + 24 horas
   ↓
3. VISUALIZAR
   Contactos ven el estado
   Se registra en tabla vistas
   ↓
4. REACCIONAR/RESPONDER
   Contactos pueden reaccionar o enviar mensaje
   ↓
5. EXPIRAR
   Tras 24 horas, cron marca como inactivo
   ↓
6. LIMPIAR
   Los estados expirados se eliminan
```

---

## Integraciones

- Integración con módulo de moderación de contenido
- Dashboard widget compatible con sistema moderno y legacy
- Notificaciones para respuestas y reacciones

---

## Notas de Implementación

- Los estados usan `activo = 0` cuando expiran (no se borran inmediatamente)
- El cron de limpieza elimina estados inactivos periódicamente
- Los usuarios silenciados no aparecen en el feed
- El visor es tipo carrusel (similar a WhatsApp)
- Soporta múltiples estados por usuario
- Los estados se agrupan por usuario en el visor
- Las respuestas son mensajes directos privados

