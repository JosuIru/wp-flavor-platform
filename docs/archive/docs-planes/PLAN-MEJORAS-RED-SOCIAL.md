# Plan de Mejoras - Red Social Federada

> Documento de plan y cierre parcial de una iniciativa especifica.
> Los porcentajes de completitud aqui reflejan esa fase de trabajo, no una garantia global de estado real vigente.
> Para lectura canonica del estado actual, usa `ESTADO-REAL-PLUGIN.md` y `../reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`.

## Diagramas de Arquitectura

### Arquitectura General de Red
**FigJam:** [Arquitectura Red Social Federada](https://www.figma.com/online-whiteboard/create-diagram/3086ca8d-12ff-4acf-8275-c1fb4b4017bd)

### Capas Internas de Nodo
**FigJam:** [Capas Internas de Nodo](https://www.figma.com/online-whiteboard/create-diagram/48a78bfa-5169-426b-831d-a9fcf9f936a0)

### Flujo de Sincronización Federada
**FigJam:** [Flujo Sincronización Federada](https://www.figma.com/online-whiteboard/create-diagram/c7320312-9339-4825-9190-b35260293fdf)

---

## Estado Actual

**Última actualización:** 23/02/2026

| Componente | Estado | Completitud |
|------------|--------|-------------|
| Red Social Interna | ✅ Completo | 100% |
| Comunidades | ✅ Completo | 100% |
| Federación Inter-nodos | ✅ Completo | 100% |
| Content Bridge | ✅ Completo | 100% |
| Algoritmo de Feed | ✅ Completo | 100% |
| Analytics | ✅ Completo | 100% |
| Moderación Avanzada | ✅ Completo | 100% |
| Sistema Reputación | ✅ Completo | 100% |
| Búsqueda Avanzada | ✅ Completo | 100% |
| Exportación Datos | ✅ Completo | 100% |
| RGPD | ✅ Completo | 100% |
| Caché Federado | ✅ Completo | 100% |
| Reputación Nodos | ✅ Completo | 100% |
| Interconexión Comunidades | ✅ Completo | 100% |
| **Notificaciones Cross-Comunidad** | ✅ Completo | 100% |
| **Búsqueda Federada** | ✅ Completo | 100% |
| **Tablón de Anuncios** | ✅ Completo | 100% |
| **Métricas de Colaboración** | ✅ Completo | 100% |

---

## Funcionalidades de Interconexión Implementadas

### Shortcodes Disponibles

| Shortcode | Descripción |
|-----------|-------------|
| `[comunidades_feed_unificado]` | Feed de actividad de todas las comunidades del usuario (locales + red federada) |
| `[comunidades_calendario]` | Calendario coordinado de eventos de comunidades locales + federadas |
| `[comunidades_recursos_compartidos]` | Recursos compartidos (recetas, biblioteca, multimedia) de la red |
| `[comunidades_centro_notificaciones]` | Centro de notificaciones cross-comunidad con preferencias |
| `[comunidades_busqueda_federada]` | Búsqueda avanzada en contenido local y de red federada |
| `[comunidades_tablon_anuncios]` | Tablón de anuncios colaborativo entre comunidades |
| `[comunidades_metricas_colaboracion]` | Dashboard de métricas de colaboración inter-comunidades |

### Parámetros de Shortcodes

#### `[comunidades_feed_unificado]`
- `limite="30"` - Número de publicaciones a mostrar
- `mostrar_origen="true"` - Mostrar indicador de origen (local/federado)
- `incluir_red="true"` - Incluir contenido de la red federada

#### `[comunidades_calendario]`
- `vista="mes"` - Vista del calendario (mes, semana)
- `incluir_red="true"` - Incluir eventos de nodos federados

#### `[comunidades_recursos_compartidos]`
- `tipos="recetas,biblioteca,multimedia,podcast"` - Tipos de recursos a mostrar
- `limite="12"` - Número de recursos
- `columnas="4"` - Columnas en el grid
- `incluir_red="true"` - Incluir recursos de nodos federados

#### `[comunidades_centro_notificaciones]`
- `limite="50"` - Número de notificaciones a mostrar
- `mostrar_preferencias="true"` - Mostrar panel de preferencias
- `agrupar_por="comunidad"` - Agrupar por comunidad o fecha

#### `[comunidades_busqueda_federada]`
- `incluir_red="true"` - Incluir resultados de la red federada
- `tipos="publicaciones,eventos,recursos,miembros"` - Tipos de contenido a buscar
- `limite="20"` - Resultados por página

#### `[comunidades_tablon_anuncios]`
- `comunidad_id=""` - ID de comunidad específica (vacío = todas)
- `limite="15"` - Número de anuncios
- `permitir_crear="true"` - Permitir crear nuevos anuncios
- `incluir_red="true"` - Incluir anuncios de comunidades federadas

#### `[comunidades_metricas_colaboracion]`
- `periodo="30"` - Días a analizar
- `comunidad_id=""` - ID de comunidad específica (vacío = todas del usuario)
- `mostrar_graficos="true"` - Mostrar gráficos visuales

### Funcionalidades AJAX

| Acción AJAX | Descripción |
|-------------|-------------|
| `comunidades_feed_unificado` | Cargar más publicaciones del feed |
| `comunidades_compartir_publicacion` | Cross-posting entre comunidades |
| `comunidades_calendario_eventos` | Obtener eventos por mes |
| `comunidades_recursos_compartidos` | Obtener recursos filtrados |
| `comunidades_marcar_todas_leidas` | Marcar todas las notificaciones como leídas |
| `comunidades_eliminar_notificacion` | Eliminar una notificación específica |
| `comunidades_guardar_preferencias_notificaciones` | Guardar preferencias de notificaciones |
| `comunidades_busqueda_federada` | Realizar búsqueda en contenido local y federado |
| `comunidades_crear_anuncio` | Crear nuevo anuncio en tablón |
| `comunidades_votar_anuncio` | Votar relevancia de anuncio |
| `comunidades_cargar_metricas` | Cargar métricas de colaboración |

### Integración con Red Federada

El sistema utiliza `Flavor_Network_Content_Bridge` para:
- Sincronizar contenido entre nodos
- Mostrar comunidades de otros nodos
- Compartir recursos (recetas, eventos, multimedia)
- Mantener visibilidad por niveles (privado, conectado, federado, público)

---

## Sistema de Caché Federado

### Configuración de TTL

| Tipo de contenido | TTL (segundos) | Descripción |
|-------------------|----------------|-------------|
| `directory` | 3600 (1h) | Directorio de nodos |
| `node_profile` | 1800 (30m) | Perfil de nodo |
| `shared_content` | 900 (15m) | Contenido general |
| `events` | 600 (10m) | Eventos |
| `communities` | 1200 (20m) | Comunidades, grupos, bancos |

### Métodos Disponibles

```php
// Obtener directorio de nodos cacheado
$nodos = $bridge->get_cached_node_directory();

// Invalidar caché de tipo específico
do_action('flavor_network_content_shared', $post_id, $tipo, $visibility);

// Refrescar caché de nodos
do_action('flavor_network_sync_completed');
```

---

## Sistema de Reputación de Nodos

### Niveles de Confianza

| Nivel | Score mínimo | Icono | Descripción |
|-------|--------------|-------|-------------|
| `no_verificado` | 0 | ⚪ | Sin verificación |
| `basico` | 25 | 🔵 | Verificado básico |
| `completo` | 60 | 🟢 | Verificado completo |
| `referencia` | 90 | ⭐ | Nodo de referencia |

### Factores de Reputación

| Factor | Peso | Descripción |
|--------|------|-------------|
| Uptime | 40% | Porcentaje de disponibilidad |
| Tiempo respuesta | 20% | Velocidad de respuesta API |
| Reportes | 20% | Penalización por reportes |
| Verificación | 10% | Verificación manual |
| Antigüedad | 10% | Tiempo activo en la red |

### Métodos Disponibles

```php
// Actualizar métricas tras respuesta
do_action('flavor_network_node_response', $nodo_id, $response_time_ms, $success);

// Reportar contenido problemático
do_action('flavor_network_content_reported', $contenido_id, $motivo);

// Filtrar contenido por nivel de confianza
$contenido = apply_filters('flavor_network_filter_by_trust', $contenido, 'basico');

// Obtener estadísticas de reputación
$stats = Flavor_Network_Content_Bridge::get_instance()->get_node_reputation_stats();

// Obtener nodos por reputación
$nodos = Flavor_Network_Content_Bridge::get_instance()->get_nodes_by_reputation(20, 'basico');
```

### Cron Automático

El sistema ejecuta diariamente:
- Recálculo de reputación de todos los nodos activos
- Limpieza de caché de directorio

---

## Plan de Mejoras por Prioridad

### Fase 1: Experiencia de Usuario (Alta Prioridad)

#### 1.1 Algoritmo de Feed Inteligente
**Estado actual:** Solo cronológico
**Objetivo:** Feed personalizado basado en engagement

**Tareas:**
- [ ] Crear tabla `flavor_social_engagement` para tracking
- [ ] Implementar cálculo de score por publicación
- [ ] Factores a considerar:
  - Interacciones del usuario con autor
  - Antigüedad de publicación (decay)
  - Número de likes/comentarios
  - Tipo de contenido preferido
  - Hashtags seguidos
- [ ] Añadir opción de cambiar entre cronológico/inteligente
- [ ] Cache de feed personalizado (Redis/transients)

**Archivos a modificar:**
```
includes/modules/red-social/class-red-social-module.php
  - Método: ajax_cargar_feed()
  - Nuevo método: calcular_score_publicacion()
  - Nuevo método: obtener_feed_personalizado()
```

**Schema nueva tabla:**
```sql
CREATE TABLE {prefix}flavor_social_engagement (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo_interaccion ENUM('view', 'like', 'comment', 'share', 'save', 'follow') NOT NULL,
    referencia_id BIGINT UNSIGNED NOT NULL,
    referencia_tipo ENUM('publicacion', 'usuario', 'hashtag', 'comunidad') NOT NULL,
    peso DECIMAL(5,2) DEFAULT 1.00,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_referencia (referencia_tipo, referencia_id),
    INDEX idx_fecha (fecha)
);
```

**Estimación:** Complejidad media

---

#### 1.2 Búsqueda Avanzada con Filtros
**Estado actual:** Índices FULLTEXT sin UI
**Objetivo:** Interfaz de búsqueda completa

**Tareas:**
- [ ] Crear componente de búsqueda unificada
- [ ] Filtros por:
  - Tipo de contenido (publicaciones, usuarios, comunidades, hashtags)
  - Fecha (hoy, semana, mes, año)
  - Autor
  - Comunidad
  - Hashtags
  - Ubicación (radio geográfico)
- [ ] Autocompletado de hashtags y menciones
- [ ] Búsqueda en contenido de red federada
- [ ] Historial de búsquedas del usuario

**Nuevo shortcode:**
```
[rs_busqueda_avanzada]
```

**Nuevo endpoint REST:**
```
POST /flavor-app/v1/red-social/buscar
  - query: string
  - tipo: array
  - filtros: object
  - pagina: int
```

**Estimación:** Complejidad media

---

### Fase 2: Administración y Moderación (Alta Prioridad)

#### 2.1 Panel de Moderación Avanzada
**Estado actual:** Estados existen pero UI limitada
**Objetivo:** Dashboard completo de moderación

**Tareas:**
- [ ] Crear página admin: "Flavor > Moderación"
- [ ] Cola de contenido reportado
- [ ] Filtros por tipo de reporte:
  - Spam
  - Contenido inapropiado
  - Acoso
  - Información falsa
  - Otro
- [ ] Acciones masivas (aprobar, rechazar, ocultar)
- [ ] Sistema de warnings a usuarios
- [ ] Historial de acciones de moderación
- [ ] Bloqueo temporal/permanente de usuarios
- [ ] Dashboard con métricas de moderación

**Nueva tabla:**
```sql
CREATE TABLE {prefix}flavor_social_moderacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    moderador_id BIGINT UNSIGNED NOT NULL,
    accion ENUM('aprobar', 'rechazar', 'ocultar', 'warning', 'ban_temporal', 'ban_permanente') NOT NULL,
    tipo_contenido ENUM('publicacion', 'comentario', 'usuario', 'comunidad') NOT NULL,
    contenido_id BIGINT UNSIGNED NOT NULL,
    motivo TEXT,
    duracion_ban INT UNSIGNED DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_moderador (moderador_id),
    INDEX idx_contenido (tipo_contenido, contenido_id)
);
```

**Nuevo archivo:**
```
includes/admin/class-social-moderation-admin.php
```

**Estimación:** Complejidad alta

---

#### 2.2 Analytics y Dashboard de Métricas
**Estado actual:** No existe
**Objetivo:** Dashboard con estadísticas de uso

**Tareas:**
- [ ] Crear página admin: "Flavor > Analytics Social"
- [ ] Métricas generales:
  - Usuarios activos (diarios, semanales, mensuales)
  - Publicaciones creadas (gráfico temporal)
  - Engagement rate (likes + comentarios / publicaciones)
  - Trending hashtags
  - Top publicaciones
  - Top usuarios (por seguidores, por engagement)
- [ ] Métricas de comunidades:
  - Comunidades más activas
  - Crecimiento de membresías
  - Ratio de retención
- [ ] Métricas de red federada:
  - Nodos activos
  - Contenido compartido
  - Colaboraciones activas
- [ ] Exportación de reportes (CSV, PDF)
- [ ] Gráficos con Chart.js

**Nuevos archivos:**
```
includes/admin/class-social-analytics-admin.php
includes/class-social-analytics.php
admin/js/social-analytics.js
admin/css/social-analytics.css
```

**Estimación:** Complejidad alta

---

### Fase 3: Gamificación y Engagement (Media Prioridad)

#### 3.1 Sistema de Reputación/Karma
**Estado actual:** No existe
**Objetivo:** Puntuación de usuarios basada en contribuciones

**Tareas:**
- [ ] Crear campo `reputacion` en tabla perfiles
- [ ] Definir sistema de puntos:
  - Publicación: +5 puntos
  - Like recibido: +1 punto
  - Comentario recibido: +2 puntos
  - Compartido recibido: +3 puntos
  - Seguidor nuevo: +2 puntos
  - Publicación reportada (confirmada): -10 puntos
  - Warning de moderación: -20 puntos
- [ ] Niveles de reputación:
  - Nuevo (0-50)
  - Activo (51-200)
  - Contribuidor (201-500)
  - Experto (501-1000)
  - Líder (1001-5000)
  - Embajador (5001+)
- [ ] Badges/logros
- [ ] Mostrar nivel en perfil público
- [ ] Permisos basados en reputación (ej: crear comunidades requiere nivel "Activo")

**Modificaciones:**
```sql
ALTER TABLE {prefix}flavor_social_perfiles
ADD COLUMN reputacion INT UNSIGNED DEFAULT 0,
ADD COLUMN nivel_reputacion ENUM('nuevo', 'activo', 'contribuidor', 'experto', 'lider', 'embajador') DEFAULT 'nuevo';
```

**Nueva tabla badges:**
```sql
CREATE TABLE {prefix}flavor_social_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono_url VARCHAR(255),
    condicion_tipo ENUM('reputacion', 'publicaciones', 'seguidores', 'antiguedad', 'especial') NOT NULL,
    condicion_valor INT UNSIGNED NOT NULL,
    activo TINYINT(1) DEFAULT 1
);

CREATE TABLE {prefix}flavor_social_user_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    fecha_obtenido DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_badge (usuario_id, badge_id)
);
```

**Estimación:** Complejidad media

---

### Fase 4: Privacidad y Cumplimiento (Alta Prioridad Legal)

#### 4.1 Cumplimiento RGPD Completo
**Estado actual:** Parcialmente implementado
**Objetivo:** Cumplimiento total RGPD

**Tareas:**
- [ ] Panel de privacidad para usuario:
  - Ver todos sus datos almacenados
  - Descargar datos (formato JSON/ZIP)
  - Solicitar eliminación de cuenta
  - Gestionar consentimientos
- [ ] Registro de consentimientos con timestamp
- [ ] Derecho al olvido (eliminación completa)
- [ ] Anonimización de datos en lugar de borrado (para estadísticas)
- [ ] Política de retención de datos
- [ ] Notificación de brechas de seguridad
- [ ] Registro de actividades de tratamiento

**Nuevo shortcode:**
```
[rs_privacidad_usuario]
```

**Nuevos endpoints:**
```
GET  /flavor-app/v1/privacidad/mis-datos
POST /flavor-app/v1/privacidad/exportar
POST /flavor-app/v1/privacidad/eliminar-cuenta
GET  /flavor-app/v1/privacidad/consentimientos
POST /flavor-app/v1/privacidad/consentimientos
```

**Nueva tabla:**
```sql
CREATE TABLE {prefix}flavor_privacy_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo_consentimiento VARCHAR(100) NOT NULL,
    consentido TINYINT(1) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo_consentimiento)
);

CREATE TABLE {prefix}flavor_privacy_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('exportar', 'eliminar', 'rectificar') NOT NULL,
    estado ENUM('pendiente', 'procesando', 'completado', 'rechazado') DEFAULT 'pendiente',
    motivo_rechazo TEXT,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_procesado DATETIME,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado)
);
```

**Estimación:** Complejidad alta

---

#### 4.2 Exportación de Datos Personales
**Estado actual:** No existe
**Objetivo:** Permitir descarga de todos los datos del usuario

**Tareas:**
- [ ] Generar archivo ZIP con:
  - Perfil (JSON)
  - Publicaciones (JSON + imágenes)
  - Comentarios (JSON)
  - Likes dados (JSON)
  - Seguidores/seguidos (JSON)
  - Comunidades (JSON)
  - Mensajes privados (JSON)
  - Notificaciones (JSON)
  - Historias (JSON + media)
- [ ] Generación asíncrona (background job)
- [ ] Notificación por email cuando esté listo
- [ ] Enlace de descarga temporal (24h)
- [ ] Límite de exportaciones (1 por semana)

**Nuevo archivo:**
```
includes/class-data-exporter.php
```

**Estimación:** Complejidad media

---

### Fase 5: Optimización de Red Federada (Media Prioridad)

#### 5.1 Caché de Contenido Federado
**Estado actual:** Sincronización cada hora sin caché
**Objetivo:** Reducir latencia y carga de red

**Tareas:**
- [ ] Implementar caché de contenido de nodos externos
- [ ] TTL configurable por tipo de contenido
- [ ] Invalidación de caché en eventos de sincronización
- [ ] Caché de directorio de nodos
- [ ] Caché de perfiles de nodos
- [ ] Compresión de respuestas API

**Configuración:**
```php
[
    'cache_ttl_directory' => 3600,      // 1 hora
    'cache_ttl_node_profile' => 1800,   // 30 min
    'cache_ttl_shared_content' => 900,  // 15 min
    'cache_ttl_events' => 600,          // 10 min
    'enable_compression' => true
]
```

**Estimación:** Complejidad media

---

#### 5.2 Sistema de Reputación de Nodos
**Estado actual:** No existe
**Objetivo:** Valorar confiabilidad de nodos de la red

**Tareas:**
- [ ] Métricas de nodo:
  - Tiempo de actividad (uptime)
  - Tiempo de respuesta API
  - Calidad de contenido compartido
  - Reportes recibidos
  - Verificación manual
- [ ] Niveles de confianza:
  - No verificado
  - Verificado básico
  - Verificado completo
  - Nodo de referencia
- [ ] Filtrado de contenido por nivel de confianza
- [ ] Alertas de nodos problemáticos

**Modificación tabla nodos:**
```sql
ALTER TABLE {prefix}flavor_network_nodes
ADD COLUMN reputacion_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN nivel_confianza ENUM('no_verificado', 'basico', 'completo', 'referencia') DEFAULT 'no_verificado',
ADD COLUMN uptime_percent DECIMAL(5,2) DEFAULT 100.00,
ADD COLUMN avg_response_time INT UNSIGNED DEFAULT 0,
ADD COLUMN reportes_count INT UNSIGNED DEFAULT 0;
```

**Estimación:** Complejidad alta

---

## Cronograma Sugerido

```
┌─────────────────────────────────────────────────────────────────────┐
│                        TIMELINE DE DESARROLLO                        │
├─────────────────────────────────────────────────────────────────────┤
│ FASE 1: Experiencia Usuario                                         │
│ ├── 1.1 Algoritmo Feed          [████████░░░░░░░░]                  │
│ └── 1.2 Búsqueda Avanzada       [░░░░████████░░░░]                  │
├─────────────────────────────────────────────────────────────────────┤
│ FASE 2: Administración                                               │
│ ├── 2.1 Moderación              [░░░░░░░░████████]                  │
│ └── 2.2 Analytics               [░░░░░░░░░░░░████]                  │
├─────────────────────────────────────────────────────────────────────┤
│ FASE 3: Gamificación                                                 │
│ └── 3.1 Reputación              [░░░░░░░░░░░░░░██]                  │
├─────────────────────────────────────────────────────────────────────┤
│ FASE 4: Privacidad (PARALELO)                                        │
│ ├── 4.1 RGPD                    [████████████░░░░]                  │
│ └── 4.2 Exportación             [░░░░████████░░░░]                  │
├─────────────────────────────────────────────────────────────────────┤
│ FASE 5: Optimización Red                                             │
│ ├── 5.1 Caché                   [░░░░░░░░░░░░████]                  │
│ └── 5.2 Reputación Nodos        [░░░░░░░░░░░░░░██]                  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Priorización Recomendada

### Inmediato (Sprint 1-2)
1. **RGPD Completo** - Obligatorio legalmente
2. **Panel Moderación** - Crítico para gestión

### Corto Plazo (Sprint 3-4)
3. **Analytics Dashboard** - Necesario para toma de decisiones
4. **Búsqueda Avanzada** - Mejora UX significativa

### Medio Plazo (Sprint 5-6)
5. **Algoritmo Feed** - Mejora engagement
6. **Exportación Datos** - Complementa RGPD

### Largo Plazo (Sprint 7-8)
7. **Sistema Reputación** - Gamificación
8. **Caché Federado** - Optimización
9. **Reputación Nodos** - Escalabilidad red

---

## Recursos Necesarios

### Desarrollo
- Desarrollador PHP senior (módulos core)
- Desarrollador frontend (UI/UX)
- DevOps (caché, optimización)

### Testing
- QA para flujos de moderación
- Testing de carga para federación
- Auditoría RGPD externa

### Documentación
- Guía de administración
- API docs actualizada
- Manual de moderación

---

## Funcionalidades Adicionales Implementadas (Sprint 9)

### Notificaciones Cross-Comunidad

Sistema completo de notificaciones entre comunidades:

| Tipo | Descripción |
|------|-------------|
| `nueva_publicacion` | Cuando alguien publica en tus comunidades |
| `nuevo_evento` | Cuando se crea un evento |
| `nuevo_miembro` | Cuando alguien se une (para admins) |
| `recurso_compartido` | Cuando se comparte contenido entre comunidades |
| `mencion` | Cuando te mencionan |
| `contenido_federado` | Contenido relevante de la red federada |
| `crosspost` | Cuando se comparte contenido entre comunidades |
| `comunidad_relacionada` | Actividad en comunidades de la misma categoría |

**Shortcode:** `[comunidades_notificaciones]`

### Búsqueda Federada Unificada

Búsqueda en contenido local y federado simultáneamente:

**Shortcode:** `[comunidades_busqueda]`

**Tipos buscables:**
- Comunidades
- Publicaciones
- Eventos
- Recetas
- Biblioteca
- Multimedia

**Filtros:**
- Por tipo de contenido
- Por origen (local/federado/todos)

### Tablón de Anuncios Inter-Comunidades

Sistema de anuncios entre comunidades con categorías:

| Categoría | Icono | Uso |
|-----------|-------|-----|
| `general` | 📢 | Anuncios generales |
| `urgente` | 🚨 | Anuncios urgentes |
| `evento` | 📅 | Promoción de eventos |
| `convocatoria` | 📋 | Convocatorias |
| `recurso` | 📦 | Compartir recursos |
| `colaboracion` | 🤝 | Propuestas de colaboración |

**Shortcode:** `[comunidades_tablon limite="20" incluir_red="true"]`

**AJAX handlers:**
- `comunidades_obtener_anuncios` - Listar anuncios
- `comunidades_crear_anuncio` - Crear nuevo anuncio
- `comunidades_mis_comunidades_admin` - Comunidades donde puede publicar

### Métricas de Colaboración

Dashboard con estadísticas de actividad y colaboración:

**Shortcode:** `[comunidades_metricas]`

**Métricas mostradas:**
- Comunidades activas
- Total de colaboraciones
- Publicaciones del período
- Contenido federado
- Top comunidades más activas
- Tipos de colaboración (gráfico)
- Actividad reciente
- Recursos más compartidos
- Conexiones entre comunidades
- Métricas de la red federada

**Filtro por período:** 7, 30, 90 o 365 días

---

## Resumen de Shortcodes Disponibles

| Shortcode | Descripción |
|-----------|-------------|
| `[comunidades_listar]` | Listado de comunidades públicas |
| `[comunidades_crear]` | Formulario para crear comunidad |
| `[comunidades_detalle]` | Detalle de una comunidad |
| `[comunidades_mis_comunidades]` | Comunidades del usuario |
| `[comunidades_feed_unificado]` | Feed unificado local + federado |
| `[comunidades_calendario]` | Calendario de eventos coordinado |
| `[comunidades_recursos_compartidos]` | Grid de recursos compartidos |
| `[comunidades_notificaciones]` | Centro de notificaciones |
| `[comunidades_busqueda]` | Búsqueda federada unificada |
| `[comunidades_tablon]` | Tablón de anuncios |
| `[comunidades_metricas]` | Dashboard de métricas |

---

## Métricas de Éxito

| Mejora | KPI | Objetivo |
|--------|-----|----------|
| Algoritmo Feed | Tiempo en feed | +30% |
| Búsqueda | Búsquedas exitosas | >80% |
| Moderación | Tiempo respuesta reporte | <24h |
| Analytics | Decisiones data-driven | 100% |
| RGPD | Cumplimiento legal | 100% |
| Reputación | Usuarios con nivel >1 | >50% |
| Caché | Tiempo respuesta API | -50% |
| Notificaciones | Engagement rate | +25% |
| Colaboración | Cross-posts entre comunidades | +40% |

---

*Documento generado: 2026-02-23*
*Última actualización: 2026-02-23*
*Versión: 1.1*
