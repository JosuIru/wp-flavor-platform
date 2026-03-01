# Módulo: Incidencias

> Sistema de reporte de incidencias urbanas

## Descripción

Plataforma para que los ciudadanos reporten incidencias en el espacio público (baches, farolas fundidas, mobiliario dañado, etc.), con seguimiento del estado de resolución y comunicación bidireccional.

## Archivos Principales

```
includes/modules/incidencias/
├── class-incidencias-module.php
├── class-incidencias-dashboard-tab.php
├── install.php
├── frontend/
│   └── class-incidencias-frontend-controller.php
├── views/
│   └── dashboard.php
└── assets/
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Incidencia | `fc_incidencia` | Incidencias reportadas |

## Tablas de Base de Datos

### wp_flavor_incidencias
Incidencias reportadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| codigo | varchar(20) UNIQUE | Código referencia |
| titulo | varchar(255) | Título descriptivo |
| descripcion | text | Descripción detallada |
| categoria | varchar(100) | Categoría principal |
| subcategoria | varchar(100) | Subcategoría |
| prioridad | enum | baja/media/alta/urgente |
| estado | enum | nueva/recibida/asignada/en_proceso/pendiente_info/resuelta/cerrada/rechazada |
| ubicacion_texto | varchar(255) | Dirección |
| ubicacion_latitud | decimal(10,8) | Coordenada |
| ubicacion_longitud | decimal(11,8) | Coordenada |
| codigo_postal | varchar(10) | CP |
| barrio | varchar(100) | Barrio/distrito |
| imagen | varchar(500) | Foto principal |
| imagenes_adicionales | longtext JSON | Más fotos |
| usuario_id | bigint(20) | FK usuario (0 si anónimo) |
| usuario_nombre | varchar(100) | Nombre reportante |
| usuario_email | varchar(100) | Email contacto |
| usuario_telefono | varchar(20) | Teléfono |
| es_anonimo | tinyint(1) | Reporte anónimo |
| asignado_a | bigint(20) | FK técnico asignado |
| departamento | varchar(100) | Departamento responsable |
| fecha_limite | datetime | Fecha límite resolución |
| fecha_resolucion | datetime | Fecha resuelta |
| resolucion_descripcion | text | Descripción solución |
| votos_apoyo | int | Ciudadanos que apoyan |
| visualizaciones | int | Vistas |
| es_publica | tinyint(1) | Visible públicamente |
| origen | varchar(50) | web/app/telefono/presencial |
| comunidad_id | bigint(20) | FK comunidad si aplica |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** codigo, categoria, estado, prioridad, ubicacion

### wp_flavor_incidencias_comentarios
Seguimiento y comunicaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| incidencia_id | bigint(20) | FK incidencia |
| usuario_id | bigint(20) | FK usuario |
| usuario_nombre | varchar(100) | Nombre |
| tipo | enum | comentario/actualizacion/solicitud_info/respuesta/resolucion |
| contenido | text | Mensaje |
| es_interno | tinyint(1) | Solo visible internamente |
| es_publico | tinyint(1) | Visible ciudadano |
| adjuntos | longtext JSON | Archivos adjuntos |
| estado_anterior | varchar(50) | Estado antes |
| estado_nuevo | varchar(50) | Estado después |
| created_at | datetime | Fecha |

**Índices:** incidencia_id, tipo

### wp_flavor_incidencias_votos
Votos de apoyo a incidencias.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| incidencia_id | bigint(20) | FK incidencia |
| usuario_id | bigint(20) | FK usuario |
| created_at | datetime | Fecha voto |

**Unique:** incidencia_id + usuario_id

### wp_flavor_incidencias_categorias
Categorías configurables.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(100) | Nombre |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripción |
| icono | varchar(50) | Icono |
| color | varchar(7) | Color |
| padre_id | bigint(20) | Categoría padre |
| departamento_default | varchar(100) | Departamento por defecto |
| prioridad_default | varchar(20) | Prioridad por defecto |
| dias_resolucion | int | Días estimados |
| activa | tinyint(1) | Está activa |
| orden | int | Orden listado |

### wp_flavor_incidencias_asignaciones
Historial de asignaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| incidencia_id | bigint(20) | FK incidencia |
| asignado_por | bigint(20) | Quien asignó |
| asignado_a | bigint(20) | A quien se asignó |
| departamento | varchar(100) | Departamento |
| motivo | text | Motivo asignación |
| created_at | datetime | Fecha |

## Shortcodes

### Reportar y Ver

```php
[incidencias_reportar]
// Formulario nuevo reporte
// - categorias: slugs permitidas
// - mapa: true|false
// - anonimo: true|false (permitir anónimo)

[incidencias_listar]
// Lista de incidencias
// - categoria: slug
// - estado: nueva|en_proceso|resuelta|todas
// - zona: código zona
// - orden: recientes|votos|prioridad
// - limite: número

[incidencias_detalle]
// Detalle de incidencia
// - id: ID o código (o auto desde URL)

[incidencias_mapa]
// Mapa de incidencias
// - categoria: slug
// - estado: activas|todas
// - zoom: nivel
// - centro: lat,lng
```

### Usuario

```php
[incidencias_mis_reportes]
// Incidencias del usuario
// - estado: todas|abiertas|resueltas
// - limite: número

[incidencias_seguimiento]
// Seguir estado por código
// - codigo: código incidencia

[incidencias_estadisticas]
// Estadísticas públicas
// - periodo: mes|trimestre|año
// - zona: código
```

## Dashboard Tab

**Clase:** `Flavor_Incidencias_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Resumen
- `reportar` - Nueva incidencia
- `mis-reportes` - Mis incidencias
- `mapa` - Mapa incidencias
- `seguimiento` - Consultar estado
- `estadisticas` - Métricas
- `historial` - Resueltas

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/incidencias/` | index | Dashboard |
| `/mi-portal/incidencias/reportar/` | reportar | Nueva |
| `/mi-portal/incidencias/mis-reportes/` | mis-reportes | Propias |
| `/mi-portal/incidencias/mapa/` | mapa | Mapa |
| `/mi-portal/incidencias/seguimiento/` | seguimiento | Por código |
| `/mi-portal/incidencias/{codigo}/` | ver | Detalle |
| `/mi-portal/incidencias/estadisticas/` | estadisticas | Métricas |
| `/mi-portal/incidencias/historial/` | historial | Resueltas |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| comunidades | Ámbito | Incidencias por comunidad |
| avisos-municipales | Información | Avisos sobre incidencias |
| transparencia | Datos | Estadísticas públicas |
| presupuestos-participativos | Origen | Propuestas desde incidencias |

## Estados y Flujo

```
NUEVA → RECIBIDA → ASIGNADA → EN_PROCESO → RESUELTA → CERRADA
                      ↓              ↓
              PENDIENTE_INFO    RECHAZADA
```

| Estado | Descripción |
|--------|-------------|
| nueva | Recién creada |
| recibida | Confirmada recepción |
| asignada | Asignada a técnico/departamento |
| en_proceso | Trabajando en ella |
| pendiente_info | Requiere más información |
| resuelta | Solucionada |
| cerrada | Verificada y cerrada |
| rechazada | No procede |

## Hooks y Filtros

### Actions

```php
// Incidencia reportada
do_action('flavor_incidencia_reportada', $incidencia_id, $datos);

// Estado cambiado
do_action('flavor_incidencia_estado_cambiado', $incidencia_id, $estado_nuevo, $estado_anterior);

// Incidencia asignada
do_action('flavor_incidencia_asignada', $incidencia_id, $asignado_a, $departamento);

// Comentario añadido
do_action('flavor_incidencia_comentario', $incidencia_id, $comentario_id);

// Incidencia resuelta
do_action('flavor_incidencia_resuelta', $incidencia_id, $resolucion);

// Voto de apoyo
do_action('flavor_incidencia_voto', $incidencia_id, $usuario_id);
```

### Filters

```php
// Categorías disponibles
apply_filters('flavor_incidencias_categorias', $categorias);

// Validar reporte
apply_filters('flavor_incidencia_validar', $valido, $datos);

// Prioridad automática
apply_filters('flavor_incidencia_prioridad_auto', $prioridad, $categoria, $datos);

// Departamento automático
apply_filters('flavor_incidencia_departamento_auto', $departamento, $categoria);

// Días límite resolución
apply_filters('flavor_incidencia_dias_limite', $dias, $categoria, $prioridad);
```

## Configuración

```php
'incidencias' => [
    'enabled' => true,
    'permitir_anonimo' => true,
    'requiere_foto' => false,
    'requiere_ubicacion' => true,
    'validacion_automatica' => false,
    'notificar_cercanos' => true,
    'radio_notificacion' => 500, // metros
    'dias_cierre_automatico' => 30, // días tras resolver
    'votos_para_priorizar' => 10,
    'categorias' => [
        'via_publica' => ['nombre' => 'Vía pública', 'dias' => 15],
        'alumbrado' => ['nombre' => 'Alumbrado', 'dias' => 7],
        'mobiliario' => ['nombre' => 'Mobiliario urbano', 'dias' => 20],
        'limpieza' => ['nombre' => 'Limpieza', 'dias' => 3],
        'zonas_verdes' => ['nombre' => 'Zonas verdes', 'dias' => 10],
        'trafico' => ['nombre' => 'Tráfico', 'dias' => 5],
        'ruidos' => ['nombre' => 'Ruidos', 'dias' => 7],
        'otros' => ['nombre' => 'Otros', 'dias' => 30],
    ],
    'notificaciones' => [
        'confirmacion_reporte' => true,
        'cambio_estado' => true,
        'solicitud_info' => true,
        'resolucion' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `incidencias_reportar` | Crear incidencias |
| `incidencias_ver_propias` | Ver sus incidencias |
| `incidencias_ver_publicas` | Ver todas públicas |
| `incidencias_gestionar` | Administrar incidencias |
| `incidencias_asignar` | Asignar a técnicos |
| `incidencias_resolver` | Marcar como resuelta |
| `incidencias_ver_internas` | Ver notas internas |

## Integración con Mapas

- Geolocalización automática al reportar
- Mapa interactivo de incidencias
- Clustering por densidad
- Filtros por categoría/estado
- Rutas para técnicos
- Exportación a KML/GeoJSON

## Notificaciones Ciudadanas

El sistema notifica al reportante:
1. Confirmación de recepción
2. Asignación a departamento
3. Solicitudes de información
4. Cambios de estado
5. Resolución final
6. Cierre de incidencia

También notifica a vecinos cercanos sobre nuevas incidencias en su zona.
