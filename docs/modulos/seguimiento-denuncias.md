# Módulo: Seguimiento de Denuncias

> Sistema de tracking de denuncias formales ante administraciones

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `seguimiento_denuncias` |
| **Versión** | 1.0.0+ |
| **Categoría** | Administración / Participación |

### Principios Gailu

- `transparencia` - Seguimiento público de procesos
- `participacion` - Incidencia ciudadana
- `justicia` - Defensa de derechos colectivos

---

## Descripción

Sistema de tracking de denuncias formales ante administraciones públicas. Permite registrar denuncias, quejas, recursos y solicitudes, hacer seguimiento de estados y plazos, y documentar todo el proceso con timeline de eventos.

### Características Principales

- **Tipos**: Denuncias, quejas, recursos, solicitudes, peticiones
- **Ámbitos**: Municipal, provincial, autonómico, estatal, europeo
- **Timeline**: Historial completo de eventos y actualizaciones
- **Plazos**: Control automático de plazos de respuesta
- **Participantes**: Denunciante, colaboradores, seguidores, afectados
- **Plantillas**: Modelos de denuncia reutilizables
- **Notificaciones**: Alertas de cambios y vencimientos

---

## Tablas de Base de Datos

### `{prefix}_flavor_seguimiento_denuncias`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `titulo` | varchar(255) | Título de la denuncia |
| `descripcion` | longtext | Descripción completa |
| `tipo` | enum | `denuncia`, `queja`, `recurso`, `solicitud`, `peticion` |
| `categoria` | varchar(100) | Categoría temática |
| `ambito` | enum | `municipal`, `provincial`, `autonomico`, `estatal`, `europeo` |
| `organismo_destino` | varchar(255) | Organismo destinatario |
| `organismo_email` | varchar(200) | Email del organismo |
| `organismo_direccion` | text | Dirección del organismo |
| `numero_registro` | varchar(100) | Número de registro oficial |
| `fecha_presentacion` | date | Fecha de presentación |
| `fecha_limite_respuesta` | date | Plazo de respuesta |
| `estado` | enum | Ver estados |
| `prioridad` | enum | `baja`, `media`, `alta`, `urgente` |
| `denunciante_id` | bigint | Usuario denunciante |
| `denunciante_nombre` | varchar(200) | Nombre del denunciante |
| `denunciante_tipo` | enum | `individual`, `colectivo`, `anonimo` |
| `colectivo_id` | bigint | Colectivo asociado |
| `campania_id` | bigint | Campaña relacionada |
| `incidencia_id` | bigint | Incidencia origen |
| `documentos_adjuntos` | text | URLs de documentos |
| `etiquetas` | text | Tags |
| `visibilidad` | enum | `publica`, `miembros`, `privada` |
| `notificar_cambios` | tinyint | Enviar notificaciones |

### `{prefix}_flavor_seguimiento_denuncias_eventos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `denuncia_id` | bigint | Denuncia padre |
| `tipo` | enum | Ver tipos de evento |
| `titulo` | varchar(255) | Título del evento |
| `descripcion` | text | Descripción |
| `estado_anterior` | varchar(50) | Estado previo |
| `estado_nuevo` | varchar(50) | Estado nuevo |
| `documento_adjunto` | varchar(255) | Documento asociado |
| `autor_id` | bigint | Usuario que registra |
| `automatico` | tinyint | Generado automáticamente |
| `created_at` | datetime | Fecha del evento |

### `{prefix}_flavor_seguimiento_denuncias_participantes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `denuncia_id` | bigint | Denuncia |
| `user_id` | bigint | Usuario participante |
| `rol` | enum | `denunciante`, `colaborador`, `seguidor`, `afectado` |
| `notificaciones` | tinyint | Recibe notificaciones |

**Unique Key**: `denuncia_id`, `user_id`

### `{prefix}_flavor_seguimiento_denuncias_plantillas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(255) | Nombre de plantilla |
| `descripcion` | text | Descripción |
| `tipo` | varchar(50) | Tipo de denuncia |
| `categoria` | varchar(100) | Categoría |
| `contenido_plantilla` | longtext | Texto modelo |
| `campos_requeridos` | text | Campos a rellenar |
| `organismo_sugerido` | varchar(255) | Organismo por defecto |
| `plazo_respuesta_dias` | int | Plazo típico |
| `activa` | tinyint | Plantilla activa |
| `usos` | int | Contador de usos |

---

## Estados de Denuncia

| Estado | Descripción |
|--------|-------------|
| `presentada` | Denuncia registrada y presentada |
| `en_tramite` | En proceso de tramitación |
| `requerimiento` | Se ha recibido requerimiento |
| `silencio` | Silencio administrativo |
| `resuelta_favorable` | Resolución favorable |
| `resuelta_desfavorable` | Resolución desfavorable |
| `archivada` | Denuncia archivada |
| `recurrida` | Se ha presentado recurso |

## Tipos de Evento

| Tipo | Descripción |
|------|-------------|
| `creacion` | Denuncia creada |
| `cambio_estado` | Cambio de estado |
| `documento_recibido` | Documento recibido |
| `documento_enviado` | Documento enviado |
| `respuesta` | Respuesta del organismo |
| `requerimiento` | Requerimiento recibido |
| `recurso` | Recurso presentado |
| `nota` | Nota interna |
| `plazo_vencido` | Vencimiento de plazo |
| `otro` | Otro tipo de evento |

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[denuncias_listar]` | Listado de denuncias | `estado`, `tipo`, `limite` |
| `[denuncias_detalle]` | Detalle de denuncia | `id` |
| `[denuncias_crear]` | Formulario de creación | - |
| `[denuncias_mis_denuncias]` | Mis denuncias | - |
| `[denuncias_timeline]` | Timeline de eventos | `id` |
| `[denuncias_estadisticas]` | Estadísticas | - |

### Ejemplo Listado

```
[denuncias_listar estado="en_tramite" tipo="denuncia" limite="12"]
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `plazo_respuesta_defecto` | int | 30 | Días plazo por defecto |
| `notificar_plazos` | bool | true | Notificar vencimientos |
| `dias_aviso_plazo` | int | 5 | Días antes para avisar |
| `permitir_denuncias_anonimas` | bool | false | Permitir anónimos |
| `requiere_aprobacion` | bool | false | Requiere aprobación admin |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `denuncias_crear` | Crear denuncia | Usuario |
| `denuncias_actualizar_estado` | Cambiar estado | Usuario |
| `denuncias_agregar_evento` | Añadir evento timeline | Usuario |
| `denuncias_seguir` | Seguir denuncia | Usuario |
| `denuncias_dejar_seguir` | Dejar de seguir | Usuario |
| `denuncias_listar` | Listar denuncias | Público |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_verificar_plazos_denuncias` | Diario | Verificar plazos y notificar |

---

## Vistas Frontend

| Vista | Archivo | Descripción |
|-------|---------|-------------|
| Listado | `views/listado.php` | Lista de denuncias |
| Detalle | `views/detalle.php` | Ficha completa |
| Crear | `views/crear.php` | Formulario creación |
| Mis Denuncias | `views/mis-denuncias.php` | Denuncias propias |
| Timeline | `views/timeline.php` | Línea temporal |
| Estadísticas | `views/estadisticas.php` | Datos agregados |

---

## Permisos y Visibilidad

### Niveles de Visibilidad

| Visibilidad | Quién puede ver |
|-------------|-----------------|
| `publica` | Cualquier visitante |
| `miembros` | Usuarios registrados |
| `privada` | Solo denunciante y participantes |

### Roles de Participante

| Rol | Permisos |
|-----|----------|
| `denunciante` | Control total, edición, cierre |
| `colaborador` | Añadir eventos, documentos |
| `seguidor` | Solo lectura y notificaciones |
| `afectado` | Lectura, puede testimoniar |

---

## Integraciones

### Con Módulo Campañas

- Vincular denuncias a campañas de incidencia
- Denuncias colectivas desde campaña

### Con Módulo Mapa de Actores

- Organismos destino como actores
- Historial de interacciones

### Con Módulo Incidencias

- Denuncias que nacen de incidencias
- Escalado de incidencias a denuncias formales

### Con Módulo Colectivos

- Denuncias en nombre de colectivo
- Firma colectiva

---

## Flujo de Trabajo Típico

```
1. CREAR → Estado: presentada
   ↓
2. PRESENTAR → Obtener número registro
   ↓
3. ESPERAR → Estado: en_tramite
   ↓
4a. RESPUESTA → Estado: resuelta_favorable/desfavorable
4b. SILENCIO → Estado: silencio (tras vencer plazo)
4c. REQUERIMIENTO → Estado: requerimiento → responder
   ↓
5. RECURRIR (si desfavorable) → Estado: recurrida
   ↓
6. ARCHIVAR → Estado: archivada
```

---

## Notificaciones

El sistema notifica automáticamente:

- Cambios de estado a participantes
- Plazos próximos a vencer
- Nuevos eventos en denuncias seguidas
- Resoluciones

---

## Notas de Implementación

- Cada cambio de estado genera evento automático en timeline
- Los plazos se calculan desde fecha_presentacion
- El sistema detecta silencio administrativo automáticamente
- Las plantillas facilitan crear denuncias tipo
- Los documentos se almacenan como adjuntos de WordPress
- Soporta denuncias individuales, colectivas y anónimas
