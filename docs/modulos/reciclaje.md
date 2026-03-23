# Modulo: Reciclaje

> Sistema de gestion de reciclaje comunitario con recompensas

## Descripcion

Plataforma integral para gestionar el reciclaje comunitario, incluyendo puntos de reciclaje, registro de depositos, sistema de puntos canjeables, calendario de recogidas, economia circular, huella de carbono y retos comunitarios. Implementa los principios Gailu de regeneracion, impacto y autonomia.

## Archivos Principales

```
includes/modules/reciclaje/
├── class-reciclaje-module.php
├── class-reciclaje-conciencia-features.php
├── class-reciclaje-dashboard-tab.php
├── class-reciclaje-dashboard-widget.php
├── class-reciclaje-api.php
├── frontend/
│   └── class-reciclaje-frontend-controller.php
├── views/
│   ├── dashboard.php
│   ├── puntos.php
│   ├── campanas.php
│   ├── estadisticas.php
│   ├── calendario.php
│   └── materiales.php
├── templates/
│   ├── economia-circular.php
│   ├── mi-huella-reciclaje.php
│   ├── retos-reciclaje.php
│   └── red-reparadores.php
└── assets/
    ├── css/
    └── js/
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripcion |
|-----|------|-------------|
| Recompensa | `recompensa_reciclaje` | Recompensas canjeables por puntos |
| Guia | `guia_reciclaje` | Guias de reciclaje por material |

## Taxonomias

| Taxonomia | Slug | Asociada a | Descripcion |
|-----------|------|------------|-------------|
| Tipo de Material | `tipo_material` | guia_reciclaje | Clasifica guias por tipo de material |
| Categoria de Recompensa | `categoria_recompensa` | recompensa_reciclaje | Categorias de recompensas |

## Tablas de Base de Datos

### wp_flavor_reciclaje_puntos
Puntos de reciclaje (contenedores, puntos limpios, centros de acopio).

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| nombre | varchar(255) | Nombre del punto |
| tipo | enum | punto_limpio/contenedor_comunitario/centro_acopio/movil |
| direccion | varchar(500) | Direccion fisica |
| latitud | decimal(10,7) | Coordenada latitud |
| longitud | decimal(10,7) | Coordenada longitud |
| materiales_aceptados | text JSON | Array de materiales que acepta |
| horario | text | Horario de atencion |
| contacto | varchar(255) | Telefono/email |
| instrucciones | text | Instrucciones de uso |
| foto_url | varchar(500) | Imagen del punto |
| estado | enum | activo/lleno/mantenimiento/inactivo |
| fecha_creacion | datetime | Fecha de creacion |

**Indices:** ubicacion (latitud, longitud), tipo, estado

### wp_flavor_reciclaje_depositos
Registros de depositos de material por usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| usuario_id | bigint(20) unsigned | FK usuario |
| punto_reciclaje_id | bigint(20) unsigned | FK punto de reciclaje |
| tipo_material | varchar(50) | Tipo de material depositado |
| cantidad_kg | decimal(10,2) | Cantidad en kilogramos |
| puntos_ganados | int(11) | Puntos otorgados |
| foto_url | varchar(500) | Foto del deposito |
| verificado | tinyint(1) | Si esta verificado |
| verificado_por | bigint(20) unsigned | FK usuario verificador |
| fecha_deposito | datetime | Fecha del deposito |

**Indices:** usuario_id, punto_reciclaje_id, tipo_material, fecha_deposito

### wp_flavor_reciclaje_recogidas
Calendario de recogidas programadas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| tipo_recogida | enum | programada/a_demanda/urgente |
| zona | varchar(255) | Zona de recogida |
| tipos_residuos | text JSON | Tipos de residuos a recoger |
| fecha_programada | datetime | Fecha y hora programada |
| hora_inicio | time | Hora de inicio |
| hora_fin | time | Hora de fin |
| ruta | text JSON | Coordenadas de la ruta |
| notas | text | Notas adicionales |
| estado | enum | programada/en_curso/completada/cancelada |
| fecha_creacion | datetime | Fecha de creacion |

**Indices:** fecha_programada, estado

### wp_flavor_reciclaje_contenedores
Contenedores individuales en puntos de reciclaje.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| punto_reciclaje_id | bigint(20) unsigned | FK punto de reciclaje |
| tipo_residuo | varchar(50) | Tipo de residuo que recibe |
| capacidad_litros | int(11) | Capacidad en litros |
| nivel_llenado | int(11) | Porcentaje 0-100 |
| necesita_vaciado | tinyint(1) | Si necesita ser vaciado |
| ultima_recogida | datetime | Ultima fecha de vaciado |
| reportes_problema | int(11) | Numero de reportes |
| estado | enum | operativo/lleno/danado/fuera_servicio |
| fecha_instalacion | datetime | Fecha de instalacion |

**Indices:** punto_reciclaje_id, tipo_residuo, necesita_vaciado

### wp_flavor_reciclaje_campanas
Campanas de reciclaje comunitario.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| titulo | varchar(255) | Titulo de la campana |
| descripcion | text | Descripcion |
| fecha_inicio | datetime | Fecha de inicio |
| fecha_fin | datetime | Fecha de fin |
| objetivo_kg | decimal(10,2) | Objetivo en kilogramos |
| objetivo_participantes | int(11) | Objetivo de participantes |
| puntos_bonus | int(11) | Puntos bonus por participar |
| estado | enum | borrador/programada/activa/finalizada/cancelada |
| ambito | enum | comunidad/barrio/municipio/escolar/empresa |
| materiales | text JSON | Materiales objetivo |
| ubicacion | varchar(255) | Ubicacion |
| created_by | bigint(20) unsigned | FK creador |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Fecha actualizacion |

**Indices:** estado, fecha_inicio, fecha_fin

### Tablas del Sello de Conciencia

#### wp_flavor_rec_reutilizaciones
Intercambio de materiales (economia circular).

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| usuario_id | bigint(20) unsigned | FK usuario oferente |
| material_tipo | varchar(50) | Tipo de material |
| descripcion | text | Descripcion del material |
| cantidad | decimal(8,2) | Cantidad |
| unidad | varchar(20) | Unidad de medida |
| estado | enum | disponible/reservado/entregado |
| receptor_id | bigint(20) unsigned | FK usuario receptor |
| ubicacion | varchar(255) | Ubicacion |
| foto_url | varchar(500) | Foto |
| co2_ahorrado | decimal(8,2) | CO2 ahorrado estimado |
| fecha_creacion | datetime | Fecha |
| fecha_entrega | datetime | Fecha de entrega |

#### wp_flavor_rec_huella_carbono
Huella de carbono personal por periodo.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| usuario_id | bigint(20) unsigned | FK usuario |
| periodo | varchar(7) | Periodo (YYYY-MM) |
| co2_reciclaje | decimal(10,2) | CO2 ahorrado por reciclaje |
| co2_reutilizacion | decimal(10,2) | CO2 ahorrado por reutilizacion |
| co2_reparacion | decimal(10,2) | CO2 ahorrado por reparacion |
| co2_total_ahorrado | decimal(10,2) | Total CO2 ahorrado |
| kg_reciclados | decimal(10,2) | Kg reciclados |
| items_reutilizados | int(11) | Items reutilizados |
| items_reparados | int(11) | Items reparados |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Unique:** usuario_id + periodo

#### wp_flavor_rec_retos
Retos comunitarios de reciclaje.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| titulo | varchar(255) | Titulo del reto |
| descripcion | text | Descripcion |
| tipo | enum | reciclaje/reutilizacion/reparacion/reduccion |
| material_objetivo | varchar(50) | Material especifico |
| meta_cantidad | decimal(10,2) | Meta a alcanzar |
| unidad | varchar(20) | Unidad (kg, items, etc) |
| progreso_actual | decimal(10,2) | Progreso actual |
| fecha_inicio | date | Fecha inicio |
| fecha_fin | date | Fecha fin |
| estado | enum | activo/completado/expirado |
| puntos_recompensa | int(11) | Puntos al completar |
| participantes | int(11) | Total participantes |
| creado_por | bigint(20) unsigned | FK creador |
| fecha_creacion | datetime | Fecha creacion |

#### wp_flavor_rec_reto_participaciones
Participaciones en retos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| reto_id | bigint(20) unsigned | FK reto |
| usuario_id | bigint(20) unsigned | FK usuario |
| contribucion | decimal(10,2) | Contribucion del usuario |
| puntos_ganados | int(11) | Puntos ganados |
| fecha_inscripcion | datetime | Fecha inscripcion |
| fecha_ultima_contribucion | datetime | Ultima contribucion |

**Unique:** reto_id + usuario_id

#### wp_flavor_rec_reparadores
Red de reparadores comunitarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| usuario_id | bigint(20) unsigned | FK usuario |
| especialidades | text JSON | Especialidades |
| descripcion | text | Descripcion |
| disponibilidad | varchar(100) | Horarios disponibles |
| ubicacion | varchar(255) | Ubicacion |
| valoracion_media | decimal(3,2) | Valoracion promedio |
| reparaciones_completadas | int(11) | Total reparaciones |
| verificado | tinyint(1) | Si esta verificado |
| activo | tinyint(1) | Si esta activo |
| fecha_registro | datetime | Fecha registro |

#### wp_flavor_rec_reparaciones
Solicitudes de reparacion.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| solicitante_id | bigint(20) unsigned | FK solicitante |
| reparador_id | bigint(20) unsigned | FK reparador asignado |
| categoria | varchar(50) | Categoria del objeto |
| descripcion | text | Descripcion del problema |
| fotos | text JSON | Fotos del objeto |
| estado | enum | abierta/asignada/en_proceso/completada/cancelada |
| co2_ahorrado | decimal(8,2) | CO2 ahorrado |
| valoracion | tinyint(1) | Valoracion 1-5 |
| comentario_valoracion | text | Comentario |
| fecha_creacion | datetime | Fecha creacion |
| fecha_completado | datetime | Fecha completado |

#### wp_flavor_rec_metricas
Metricas comunitarias agregadas por periodo.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| periodo | varchar(7) UNIQUE | Periodo (YYYY-MM) |
| kg_reciclados | decimal(12,2) | Kg reciclados |
| items_reutilizados | int(11) | Items reutilizados |
| reparaciones_completadas | int(11) | Reparaciones completadas |
| co2_total_ahorrado | decimal(12,2) | CO2 ahorrado |
| usuarios_activos | int(11) | Usuarios activos |
| retos_completados | int(11) | Retos completados |
| material_top | varchar(50) | Material mas reciclado |
| fecha_calculo | datetime | Fecha de calculo |

## Shortcodes

### Modulo Principal

```php
[reciclaje_puntos_cercanos]
// Mapa con puntos de reciclaje cercanos
// - altura: alto en px (default: 500)
// - zoom: nivel de zoom inicial (default: 14)

[reciclaje_calendario]
// Calendario de recogidas
// - zona: filtrar por zona

[reciclaje_mis_puntos]
// Puntos acumulados del usuario actual

[reciclaje_ranking]
// Ranking de recicladores
// - limite: numero de usuarios (default: 10)

[reciclaje_guia]
// Muestra guias de reciclaje (CPT guia_reciclaje)

[reciclaje_recompensas]
// Catalogo de recompensas canjeables
```

### Frontend Controller

```php
[flavor_reciclaje_mapa]
// Mapa interactivo con Leaflet

[flavor_reciclaje_puntos]
// Lista de puntos de reciclaje

[flavor_reciclaje_registrar]
// Formulario para registrar deposito

[flavor_reciclaje_mis_registros]
// Historial de depositos del usuario

[flavor_reciclaje_canjear]
// Interfaz para canjear puntos

[flavor_reciclaje_guia]
// Guia visual de clasificacion

[flavor_reciclaje_estadisticas]
// Estadisticas personales

[flavor_reciclaje_reportar]
// Reportar problema con contenedor
```

### Sello de Conciencia

```php
[rec_economia_circular]
// Intercambio de materiales (dar y recibir)

[rec_mi_huella_reciclaje]
// Dashboard de huella de carbono personal

[rec_retos_activos]
// Lista de retos comunitarios activos

[rec_red_reparadores]
// Red de reparadores y solicitudes

[rec_dashboard_impacto]
// Dashboard de impacto ambiental comunitario
```

## Dashboard Tab

**Clase:** `Flavor_Reciclaje_Dashboard_Tab`

**Tabs disponibles:**
- `reciclaje-mis-aportes` - Historial de depositos
- `reciclaje-mis-puntos` - Puntos y ranking
- `reciclaje-recompensas` - Catalogo y canje
- `reciclaje-estadisticas` - Impacto ambiental personal

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/reciclaje/` | index | Dashboard |
| `/mi-portal/reciclaje/mapa/` | mapa | Mapa de puntos |
| `/mi-portal/reciclaje/registrar/` | registrar | Registrar deposito |
| `/mi-portal/reciclaje/mis-puntos/` | mis-puntos | Mis puntos |
| `/mi-portal/reciclaje/recompensas/` | recompensas | Canjear |
| `/mi-portal/reciclaje/calendario/` | calendario | Recogidas |
| `/mi-portal/reciclaje/guia/` | guia | Guia reciclaje |
| `/mi-portal/reciclaje/retos/` | retos | Retos activos |
| `/mi-portal/reciclaje/economia-circular/` | economia | Intercambio |
| `/mi-portal/reciclaje/reparadores/` | reparadores | Red reparadores |
| `/mi-portal/reciclaje/huella/` | huella | Mi huella CO2 |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Ambito | Reciclaje por comunidad |
| gamificacion | Recompensas | Puntos y badges |
| notificaciones | Alertas | Recordatorios recogidas |
| multimedia | Media | Fotos de depositos |

## Categorias de Materiales

| Material | Color | Icono | Factor CO2 (kg/kg) |
|----------|-------|-------|-------------------|
| papel | #3b82f6 | media-default | 0.7 |
| plastico | #eab308 | star-filled | 1.5 |
| vidrio | #22c55e | visibility | 0.3 |
| organico | #84cc16 | carrot | 0.2 |
| electronico | #f97316 | laptop | 2.0 |
| ropa | #8b5cf6 | businessman | 3.0 |
| aceite | #f59e0b | admin-appearance | 2.5 |
| pilas | #ef4444 | warning | 5.0 |
| metal | - | - | 4.0 |

## Sistema de Puntos

- **Puntos por Kg:** Configurable (default: 10 pts/kg)
- **Verificacion:** Los depositos requieren verificacion para acreditar puntos
- **Canje:** Puntos canjeables por recompensas (CPT recompensa_reciclaje)
- **Ranking:** Clasificacion por puntos totales acumulados

## REST API

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/reciclaje/puntos` | GET | Obtener puntos cercanos |
| `/flavor/v1/reciclaje/deposito` | POST | Registrar deposito |
| `/flavor/v1/reciclaje/calendario` | GET | Obtener recogidas |
| `/flavor/v1/reciclaje/stats` | GET | Estadisticas del usuario |

## Hooks y Filtros

### Actions

```php
// Deposito registrado
do_action('reciclaje_deposito_registrado', $deposito_id, $usuario_id, $datos);

// Recogida notificada
do_action('reciclaje_notificar_recogidas');

// Contenedores verificados
do_action('reciclaje_verificar_contenedores');

// Reto unido
do_action('flavor_rec_reto_unido', $reto_id, $usuario_id);

// Reparacion completada
do_action('flavor_rec_reparacion_completada', $reparacion_id);
```

### Filters

```php
// Categorias de reciclaje
apply_filters('flavor_reciclaje_categorias', $categorias);

// Puntos por kg
apply_filters('flavor_reciclaje_puntos_por_kg', $puntos, $tipo_material);

// Factor CO2 por material
apply_filters('flavor_rec_factor_co2', $factor, $material);
```

## Configuracion

```php
'reciclaje' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'puntos_por_kg' => 10,
    'permite_canje_puntos' => true,
    'notificar_recogidas' => true,
    'permite_reportar_contenedores' => true,
    'categorias_reciclaje' => [
        'papel',
        'plastico',
        'vidrio',
        'organico',
        'electronico',
        'ropa',
        'aceite',
        'pilas'
    ],
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `reciclaje_registrar` | Registrar depositos |
| `reciclaje_ver_puntos` | Ver puntos de reciclaje |
| `reciclaje_canjear` | Canjear puntos |
| `reciclaje_gestionar` | Administrar modulo |
| `reciclaje_verificar` | Verificar depositos |
| `reciclaje_estadisticas` | Ver estadisticas completas |

## WP Cron

El modulo registra dos tareas programadas:

- **reciclaje_notificar_recogidas** (diario): Notifica a usuarios sobre recogidas del dia siguiente
- **reciclaje_verificar_contenedores** (dos veces al dia): Verifica nivel de llenado y marca contenedores que necesitan vaciado

## Integracion con Mapas

- Leaflet para mapas interactivos
- Geolocalizacion del usuario
- Busqueda por proximidad (haversine)
- Marcadores por tipo de punto
- Filtros por material aceptado

## Calculo de Impacto Ambiental

El modulo calcula automaticamente:
- **CO2 evitado:** kg reciclados * factor_material
- **Arboles equivalentes:** kg papel / 17
- **Agua ahorrada:** kg * 5 litros (estimacion)

## Componentes Web (Visual Builder)

| Componente | Descripcion |
|------------|-------------|
| hero_reciclaje | Banner principal con stats |
| puntos_reciclaje | Mapa de puntos con filtros |
| calendario_recogidas | Calendario visual |
| guia_reciclaje | Guia de clasificacion |

## Notificaciones

El sistema notifica automaticamente:
1. Confirmacion de deposito registrado
2. Puntos ganados al verificar deposito
3. Recordatorio de recogidas proximas
4. Contenedores llenos (a admins)
5. Nuevo reto disponible
6. Reto completado

## Funcionalidades del Sello de Conciencia (+13 pts)

### Economia Circular
- Ofrecer materiales para reutilizar
- Solicitar materiales de otros usuarios
- Estados: disponible/reservado/entregado

### Huella de Carbono Personal
- Tracking mensual de CO2 ahorrado
- Desglose por actividad (reciclaje/reutilizacion/reparacion)
- Historico de 6 meses

### Retos Comunitarios
- Tipos: reciclaje/reutilizacion/reparacion/reduccion
- Progreso comunitario en tiempo real
- Puntos bonus al completar

### Red de Reparadores
- Registro de reparadores voluntarios
- Especialidades y valoraciones
- Solicitudes de reparacion
- Tracking de CO2 ahorrado

### Dashboard de Impacto
- Metricas comunitarias agregadas
- Comparativas mensuales
- Material mas reciclado
