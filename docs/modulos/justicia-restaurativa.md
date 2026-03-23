# Módulo: Justicia Restaurativa

> Sistema de resolución de conflictos comunitaria basada en reparación y diálogo

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `justicia_restaurativa` |
| **Versión** | 1.0.0+ |
| **Categoría** | Gobernanza / Comunidad |
| **Disponible en App** | Cliente |

### Principios Gailu

- `conciencia_fundamental` (0.30) - Conflicto como oportunidad de crecimiento
- `interdependencia_radical` (0.25) - Comunidad como garante
- `madurez_ciclica` (0.20) - Proceso de sanación tiene su tiempo
- `valor_intrinseco` (0.15) - Dignidad de todas las partes
- `abundancia_organizable` (0.10) - Recursos comunitarios de mediación

### Valoración de Conciencia: 92/100

---

## Descripción

Transforma el conflicto en oportunidad de crecimiento, reconoce la dignidad de todas las partes y promueve la reparación sobre el castigo. Sistema de resolución de conflictos comunitaria que prioriza el diálogo, la mediación y la reconciliación.

### Características Principales

- **Procesos Restaurativos**: Mediación, círculos de diálogo, conferencias, círculos de paz
- **Confidencialidad**: Todo lo dicho es confidencial
- **Mediadores Voluntarios**: Red de mediadores de la comunidad
- **Seguimiento de Acuerdos**: Control de cumplimiento
- **Notificaciones**: Alertas a todas las partes involucradas

---

## Tipos de Proceso

| Tipo | Nombre | Descripción | Duración |
|------|--------|-------------|----------|
| `mediacion` | Mediación | Diálogo facilitado entre las partes | 1-3 sesiones |
| `circulo_dialogo` | Círculo de diálogo | Conversación comunitaria ampliada | 1-2 sesiones |
| `conferencia` | Conferencia restaurativa | Reunión con todas las partes afectadas | 2-4 sesiones |
| `circulo_paz` | Círculo de paz | Proceso comunitario de sanación | 3-6 sesiones |

---

## Estados del Proceso

| Estado | Descripción | Color |
|--------|-------------|-------|
| `solicitado` | Proceso solicitado | #f39c12 |
| `aceptado` | Otra parte ha aceptado | #3498db |
| `en_curso` | Sesiones en marcha | #9b59b6 |
| `acuerdo` | Acuerdo alcanzado | #27ae60 |
| `cumplido` | Acuerdo cumplido | #2ecc71 |
| `no_acuerdo` | Sin acuerdo | #95a5a6 |
| `cancelado` | Proceso cancelado | #e74c3c |

---

## Custom Post Types

### `jr_proceso` - Procesos Restaurativos

**Meta Fields:**
- `_jr_tipo` - Tipo de proceso
- `_jr_estado` - Estado actual
- `_jr_descripcion` - Descripción (confidencial)
- `_jr_solicitante_id` - Usuario solicitante
- `_jr_otra_parte_id` - Otra parte involucrada
- `_jr_mediador_id` - Mediador asignado
- `_jr_fecha_solicitud` - Fecha de solicitud
- `_jr_fecha_inicio` - Fecha de inicio
- `_jr_fecha_aceptacion` - Fecha de aceptación

### `jr_sesion` - Sesiones

Post type para registrar cada sesión del proceso.

### `jr_acuerdo` - Acuerdos

Post type para documentar acuerdos alcanzados.

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/`

| Endpoint | Método | Descripción | Auth |
|----------|--------|-------------|------|
| `/justicia-restaurativa/tipos` | GET | Tipos de proceso disponibles | Público |
| `/justicia-restaurativa/mis-procesos` | GET | Mis procesos | Usuario |
| `/justicia-restaurativa/proceso/{id}` | GET | Obtener proceso específico | Usuario |
| `/justicia-restaurativa/estadisticas` | GET | Estadísticas personales | Usuario |
| `/justicia-restaurativa/mediadores` | GET | Lista de mediadores | Público |

### GET `/justicia-restaurativa/tipos`

**Respuesta:**
```json
{
  "tipos": {
    "mediacion": {
      "nombre": "Mediación",
      "icono": "dashicons-groups",
      "color": "#3498db",
      "descripcion": "Diálogo facilitado entre las partes",
      "duracion_estimada": "1-3 sesiones"
    }
  }
}
```

### GET `/justicia-restaurativa/mis-procesos`

**Respuesta:**
```json
{
  "procesos": [
    {
      "id": 123,
      "titulo": "Proceso Mediación - 15/03/2026",
      "tipo": "Mediación",
      "estado": "En curso",
      "color_estado": "#9b59b6"
    }
  ]
}
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `tipos_habilitados` | array | Todos | Tipos de proceso activos |
| `confidencialidad_estricta` | bool | true | Confidencialidad total |
| `permitir_mediadores_voluntarios` | bool | true | Mediadores voluntarios |
| `notificar_partes` | bool | true | Notificar a las partes |
| `dias_respuesta_maximos` | int | 7 | Días máximos para responder |
| `mostrar_en_dashboard` | bool | true | Widget en dashboard |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `jr_solicitar_proceso` | Solicitar nuevo proceso | Usuario |
| `jr_responder_solicitud` | Responder a invitación | Usuario |
| `jr_registrar_sesion` | Registrar sesión | Usuario |
| `jr_registrar_acuerdo` | Registrar acuerdo | Usuario |
| `jr_ser_mediador` | Solicitar ser mediador | Usuario |

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[justicia_restaurativa]` | Información general sobre JR |
| `[solicitar_mediacion]` | Formulario para solicitar proceso |
| `[mis_procesos]` | Lista de mis procesos |
| `[mediadores]` | Lista de mediadores disponibles |
| `[flavor_justicia_info]` | Alias con prefijo |
| `[flavor_justicia_solicitar]` | Alias con prefijo |
| `[flavor_justicia_mis_procesos]` | Alias con prefijo |
| `[flavor_justicia_mediadores]` | Alias con prefijo |

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Información | Qué es la JR | No |
| Mediadores | Lista de mediadores | No |
| Solicitar | Formulario de solicitud | Sí |
| Mis procesos | Procesos propios | Sí |
| Foro | Foro contextual | No |
| Chat | Chat del proceso | Sí |
| Multimedia | Archivos del proceso | No |
| Red social | Actividad social | Sí |

---

## Permisos y Confidencialidad

### Quién puede ver un proceso

- Administradores (`manage_options`)
- Solicitante del proceso
- Otra parte involucrada
- Mediador asignado

### Acceso denegado

Si un usuario intenta ver un proceso sin permiso, se muestra un mensaje de error indicando que la información es confidencial.

---

## Mediadores

### Cómo ser mediador

1. Usuario solicita ser mediador vía AJAX `jr_ser_mediador`
2. Proporciona formación y motivación
3. Admin revisa solicitud
4. Se marca `_jr_es_mediador = 1` en user_meta

### Obtener mediadores

```php
$mediadores = get_users([
    'meta_key' => '_jr_es_mediador',
    'meta_value' => '1',
]);
```

---

## Notificaciones

El sistema envía notificaciones automáticas para:

- Invitación a proceso (a otra parte)
- Nueva solicitud (a mediadores)
- Aceptación de proceso (a solicitante)
- Solicitud de mediador (a admins)

Usa `Flavor_Notification_Center` si está disponible.

---

## Flujo de Trabajo

```
1. SOLICITAR
   Usuario solicita proceso restaurativo
   ↓
2. INVITAR
   Se notifica a la otra parte
   ↓
3. ACEPTAR/RECHAZAR
   Otra parte responde
   ↓
4. ASIGNAR MEDIADOR
   Se asigna mediador disponible
   ↓
5. SESIONES
   Se realizan las sesiones necesarias
   ↓
6. ACUERDO
   Se alcanza (o no) un acuerdo
   ↓
7. SEGUIMIENTO
   Se hace seguimiento del cumplimiento
   ↓
8. CIERRE
   Proceso completado
```

---

## Integraciones

### Módulos Satélite

- `foros` - Foro contextual del proceso
- `chat-grupos` - Chat privado del proceso
- `multimedia` - Archivos y documentos
- `red-social` - Actividad social

---

## Páginas de Administración

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `justicia-restaurativa` | Panel principal |
| Procesos | `jr-procesos` | Listado de procesos |
| Mediadores | `jr-mediadores` | Gestión de mediadores |

---

## Estadísticas de Usuario

```json
{
  "procesos": 3,
  "acuerdos": 2,
  "es_mediador": false
}
```

---

## Notas de Implementación

- Los procesos usan Custom Post Types con estado `private`
- La confidencialidad se aplica a nivel de template_redirect
- Los mediadores se identifican por user_meta
- El sistema soporta procesos individuales y colectivos
- Integración completa con el dashboard unificado
- Los procesos cancelados no se eliminan, se marcan
- La otra parte puede rechazar participar

