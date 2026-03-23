# Módulo: Chat de Grupos

> Grupos de conversación temáticos para la comunidad

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `chat_grupos` |
| **Versión** | 1.0.0+ |
| **Categoría** | Comunicación / Social |
| **Disponible en App** | Ambas (cliente y admin) |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`
- `Flavor_WhatsApp_Features`
- `Flavor_Encuestas_Features`

---

## Descripción

Sistema de chat grupal con canales y temas organizados. Permite crear grupos de conversación temáticos para la comunidad, con integración automática en otros módulos.

### Características Principales

- **Grupos Temáticos**: Conversaciones organizadas por tema
- **Canales**: Subcanales dentro de grupos
- **Archivos**: Compartir imágenes, documentos
- **Encuestas**: Crear encuestas en el chat
- **Hilos**: Respuestas en hilo
- **Reacciones**: Emojis de reacción
- **Integración**: Chat automático en otros módulos
- **Push**: Notificaciones push

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `disponible_app` | string | `ambas` | Disponibilidad en app |
| `max_miembros_por_grupo` | int | 500 | Máximo miembros |
| `permite_crear_grupos` | bool | true | Usuarios pueden crear |
| `requiere_aprobacion_grupos` | bool | false | Aprobación admin |
| `permite_grupos_privados` | bool | true | Grupos privados |
| `permite_archivos` | bool | true | Compartir archivos |
| `max_archivo_mb` | int | 10 | Tamaño máximo MB |
| `permite_videollamadas` | bool | false | Videollamadas |
| `permite_encuestas` | bool | true | Encuestas en chat |
| `historial_mensajes_dias` | int | 365 | Días de historial |
| `notificaciones_push` | bool | true | Push notifications |
| `mensajes_por_pagina` | int | 50 | Paginación |
| `permite_reacciones` | bool | true | Reacciones emoji |
| `permite_hilos` | bool | true | Respuestas en hilo |

---

## Tablas de Base de Datos

### `{prefix}_flavor_chat_grupos`

Tabla principal de grupos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar | Nombre del grupo |
| `descripcion` | text | Descripción |
| `tipo` | enum | Tipo de grupo |
| `privacidad` | enum | Público/privado |
| `imagen` | varchar | URL de imagen |
| `admin_id` | bigint | Administrador |
| `entidad_tipo` | varchar | Tipo de entidad asociada |
| `entidad_id` | bigint | ID de entidad asociada |
| `created_at` | datetime | Fecha de creación |

### `{prefix}_flavor_chat_grupos_mensajes`

Mensajes de los grupos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `grupo_id` | bigint | Grupo |
| `usuario_id` | bigint | Autor |
| `mensaje` | text | Contenido |
| `tipo` | enum | texto/imagen/archivo/sistema |
| `adjunto_url` | varchar | URL de adjunto |
| `hilo_id` | bigint | Mensaje padre (hilo) |
| `created_at` | datetime | Fecha |

### `{prefix}_flavor_chat_grupos_miembros`

Miembros de cada grupo.

### `{prefix}_flavor_chat_grupos_leidos`

Control de mensajes leídos por usuario.

---

## Integraciones con Módulos

El módulo inyecta automáticamente un tab de "Chat" en los dashboards de otros módulos:

| Módulo | Entidad | Descripción |
|--------|---------|-------------|
| `grupos_consumo` | grupo_consumo | Chat del grupo |
| `eventos` | evento | Chat del evento |
| `comunidades` | comunidad | Chat de la comunidad |
| `incidencias` | incidencia | Chat de la incidencia |
| `documentacion_legal` | documento_legal | Chat del documento |
| `presupuestos_participativos` | pp_proyecto | Chat del proyecto |
| `saberes_ancestrales` | saber_ancestral | Chat del saber |
| `transparencia` | documento_transparencia | Chat del documento |
| `avisos_municipales` | aviso_municipal | Chat del aviso |
| `economia_don` | economia_don | Chat del intercambio |
| `advertising` | advertising_ad | Chat del anuncio |
| `radio` | radio_programa | Chat del programa |
| `energia_comunitaria` | energia_comunidad | Chat de la comunidad |
| `cursos` | curso | Chat del curso |
| `talleres` | taller | Chat del taller |
| `colectivos` | colectivo | Chat del colectivo |
| `circulos_cuidados` | circulo | Chat del círculo |
| `banco_tiempo` | servicio_bt | Chat del servicio |
| `trabajo_digno` | trabajo_digno_oferta | Chat de la oferta |
| `huertos_urbanos` | huerto | Chat del huerto |
| `participacion` | participacion_propuesta | Chat de la propuesta |
| `economia_suficiencia` | es_recurso | Chat del recurso |
| `justicia_restaurativa` | jr_proceso | Chat del proceso |

### Shortcode de Integración

```php
[flavor_chat_grupo_integrado entidad="evento" entidad_id="123"]
```

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_chat_grupos]` | Lista de grupos |
| `[flavor_chat_grupo id="X"]` | Chat de un grupo específico |
| `[flavor_chat_crear_grupo]` | Formulario crear grupo |
| `[flavor_chat_mis_grupos]` | Mis grupos |
| `[flavor_chat_grupo_integrado]` | Chat integrado en entidad |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `chat_grupos_crear` | Crear grupo | Usuario |
| `chat_grupos_enviar` | Enviar mensaje | Usuario |
| `chat_grupos_obtener` | Obtener mensajes | Usuario |
| `chat_grupos_marcar_leido` | Marcar como leído | Usuario |
| `chat_grupos_unirse` | Unirse a grupo | Usuario |
| `chat_grupos_salir` | Salir del grupo | Usuario |
| `chat_grupos_invitar` | Invitar miembro | Usuario |
| `chat_grupos_expulsar` | Expulsar miembro | Admin grupo |
| `chat_grupos_editar` | Editar grupo | Admin grupo |
| `chat_grupos_eliminar` | Eliminar grupo | Admin grupo |
| `chat_grupos_reaccionar` | Añadir reacción | Usuario |
| `chat_grupos_upload` | Subir archivo | Usuario |

---

## Método: Contar Mensajes No Leídos

```php
$no_leidos = $modulo->contar_mensajes_no_leidos_entidad(
    $tipo_entidad,  // 'evento', 'curso', etc.
    $entidad_id,    // ID de la entidad
    $user_id        // Usuario
);
```

Este método se usa para mostrar badges en los tabs de integración.

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `chat-grupos-dashboard` | Panel principal |

---

## Flujo de Integración

```
1. MÓDULO ACTIVA
   Otro módulo (ej: eventos) está activo
   ↓
2. CREAR ENTIDAD
   Se crea un evento con ID 123
   ↓
3. PRIMER ACCESO AL TAB
   Usuario entra al tab "Chat" del evento
   ↓
4. CREAR GRUPO AUTOMÁTICO
   Se crea grupo con entidad_tipo="evento" entidad_id=123
   ↓
5. CHAT DISPONIBLE
   Usuarios del evento pueden chatear
   ↓
6. BADGE
   Se muestra contador de no leídos en el tab
```

---

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver grupos públicos | Público |
| Enviar mensajes | Miembro del grupo |
| Crear grupos | Usuario (si configurado) |
| Administrar grupo | Admin del grupo |
| Moderar | Administrador del sitio |

---

## Notas de Implementación

- Los grupos pueden ser independientes o vinculados a entidades
- Cuando se vincula a una entidad, el acceso depende del acceso a la entidad
- Los badges de no leídos se calculan dinámicamente
- Soporta el trait WhatsApp_Features para funcionalidades avanzadas
- Soporta el trait Encuestas_Features para encuestas en chat
- Las integraciones se configuran en `get_tab_integrations()`
- El chat integrado usa shortcode con parámetros dinámicos

