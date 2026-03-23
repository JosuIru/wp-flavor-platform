# Modulo: Colectivos y Asociaciones

> Gestion de colectivos, asociaciones, cooperativas, ONGs y plataformas ciudadanas

## Descripcion

Sistema completo para crear y gestionar organizaciones colectivas. Permite a los usuarios crear colectivos de distintos tipos (asociaciones, cooperativas, ONGs, colectivos informales, plataformas), gestionar membresias con roles jerarquicos, organizar proyectos colaborativos y convocar asambleas. Ideal para comunidades que buscan facilitar la organizacion ciudadana y el trabajo en equipo.

## Archivos Principales

```
includes/modules/colectivos/
├── class-colectivos-module.php              # Clase principal del modulo
├── class-colectivos-dashboard-tab.php       # Tab para dashboard del cliente
├── class-colectivos-dashboard-widget.php    # Widget de dashboard
├── frontend/
│   └── class-colectivos-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                        # Vista dashboard admin
│   ├── listado-colectivos.php               # Listado de colectivos
│   ├── detalle-colectivo.php                # Detalle de colectivo
│   ├── crear-colectivo.php                  # Formulario de creacion
│   ├── mis-colectivos.php                   # Colectivos del usuario
│   ├── proyectos.php                        # Proyectos del colectivo
│   ├── asambleas.php                        # Asambleas del colectivo
│   ├── miembros.php                         # Gestion de miembros
│   ├── solicitudes.php                      # Solicitudes de union
│   └── config.php                           # Configuracion del modulo
└── assets/
    ├── css/colectivos.css
    ├── css/colectivos-frontend.css
    └── js/colectivos-frontend.js
```

## Tablas de Base de Datos

### wp_flavor_colectivos
Tabla principal de colectivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| nombre | varchar(200) | Nombre del colectivo |
| descripcion | text | Descripcion completa |
| tipo | enum | asociacion/cooperativa/ong/colectivo/plataforma |
| imagen | varchar(255) | URL de imagen/logo |
| email_contacto | varchar(200) | Email de contacto |
| telefono | varchar(50) | Telefono de contacto |
| direccion | text | Direccion fisica |
| web | varchar(255) | Sitio web |
| redes_sociales | text | JSON con enlaces a redes |
| sector | varchar(100) | Sector de actividad |
| miembros_count | int(11) | Contador de miembros |
| proyectos_count | int(11) | Contador de proyectos |
| creador_id | bigint(20) unsigned | FK usuario creador |
| estado | enum | activo/inactivo/en_formacion |
| created_at | datetime | Fecha de creacion |
| updated_at | datetime | Fecha de actualizacion |

**Indices:** creador_id, tipo, estado, sector

### wp_flavor_colectivos_miembros
Membresias de usuarios en colectivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| colectivo_id | bigint(20) unsigned | FK colectivo |
| user_id | bigint(20) unsigned | FK usuario WP |
| rol | enum | presidente/secretario/tesorero/vocal/miembro |
| estado | enum | activo/pendiente/baja |
| fecha_alta | datetime | Fecha de alta |
| fecha_baja | datetime | Fecha de baja (si aplica) |

**Indices:** colectivo_id, user_id, estado
**Unique:** colectivo_id + user_id

### wp_flavor_colectivos_proyectos
Proyectos de los colectivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| colectivo_id | bigint(20) unsigned | FK colectivo |
| titulo | varchar(255) | Titulo del proyecto |
| descripcion | text | Descripcion del proyecto |
| estado | enum | planificado/en_curso/completado/cancelado |
| presupuesto | decimal(10,2) | Presupuesto asignado |
| fecha_inicio | date | Fecha de inicio |
| fecha_fin | date | Fecha prevista de fin |
| responsable_id | bigint(20) unsigned | FK usuario responsable |
| participantes | text | JSON con IDs de participantes |
| progreso | int(11) | Porcentaje de progreso (0-100) |
| created_at | datetime | Fecha de creacion |
| updated_at | datetime | Fecha de actualizacion |

**Indices:** colectivo_id, estado, responsable_id

### wp_flavor_colectivos_asambleas
Asambleas convocadas por los colectivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) unsigned | ID unico |
| colectivo_id | bigint(20) unsigned | FK colectivo |
| titulo | varchar(255) | Titulo de la asamblea |
| descripcion | text | Descripcion/objetivo |
| tipo | enum | ordinaria/extraordinaria |
| fecha | datetime | Fecha y hora |
| lugar | varchar(255) | Lugar de celebracion |
| orden_del_dia | text | Orden del dia |
| acta | text | Acta de la reunion (post-asamblea) |
| asistentes | text | JSON con IDs de asistentes confirmados |
| estado | enum | convocada/en_curso/finalizada/cancelada |
| created_at | datetime | Fecha de creacion |

**Indices:** colectivo_id, fecha, estado

## Shortcodes

### Shortcodes Legacy (class-colectivos-module.php)

```php
[colectivos_listar]
// Listado de colectivos con filtros
// - tipo: asociacion|cooperativa|ong|colectivo|plataforma
// - sector: slug del sector
// - columnas: 2|3|4 (default: 3)
// - limite: numero (default: 12)

[colectivos_crear]
// Formulario para crear nuevo colectivo
// Requiere usuario autenticado

[colectivos_detalle]
// Detalle de un colectivo
// - id: ID del colectivo (o auto desde URL)

[colectivos_mis_colectivos]
// Colectivos del usuario actual
// - columnas: 2|3 (default: 2)

[colectivos_proyectos]
// Proyectos de un colectivo
// - colectivo_id: ID (o auto desde URL)
// - estado: planificado|en_curso|completado|cancelado
// - limite: numero (default: 10)

[colectivos_asambleas]
// Asambleas de un colectivo
// - colectivo_id: ID (o auto desde URL)
// - estado: convocada|en_curso|finalizada
// - limite: numero (default: 10)

[colectivos_mi_actividad]
// Resumen de actividad del usuario en sus colectivos
// - limite: numero (default: 5)
```

### Shortcodes Frontend Controller (class-colectivos-frontend-controller.php)

```php
[flavor_colectivos_listado]
// Directorio de colectivos con paginacion
// - tipo: filtrar por tipo
// - por_pagina: numero (default: 12)
// - vista: grid|lista (default: grid)

[flavor_colectivos_detalle]
// Pagina completa de detalle
// - colectivo_id: ID (o auto desde URL)

[flavor_colectivos_crear]
// Formulario de creacion completo
// Requiere autenticacion

[flavor_colectivos_mis_colectivos]
// Colectivos del usuario actual

[flavor_colectivos_proyectos]
// Proyectos con filtros
// - colectivo_id: ID

[flavor_colectivos_asambleas]
// Asambleas con calendario
// - colectivo_id: ID
// - solo_futuras: si|no (default: si)

[flavor_colectivos_miembros]
// Lista de miembros del colectivo
// - colectivo_id: ID

[flavor_colectivos_mapa]
// Mapa geografico de colectivos (Leaflet)
```

## Dashboard Tab

**Clase:** `Flavor_Colectivos_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Panel principal con KPIs
- `mis-colectivos` - Colectivos del usuario
- `explorar` - Directorio de colectivos
- `crear` - Formulario de creacion
- `proyectos` - Proyectos en curso
- `asambleas` - Proximas asambleas
- `foro` - Foro integrado del colectivo
- `chat` - Chat grupal del colectivo
- `multimedia` - Documentos y archivos

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/colectivos/` | listar | Directorio de colectivos |
| `/colectivos/crear/` | crear | Formulario creacion |
| `/colectivos/mis-colectivos/` | mis_colectivos | Mis colectivos |
| `/mi-portal/colectivos/` | dashboard | Dashboard del modulo |
| `/mi-portal/colectivos/proyectos/` | proyectos | Proyectos |
| `/mi-portal/colectivos/asambleas/` | asambleas | Asambleas |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| foros | Integracion | Foros por colectivo |
| chat-grupos | Integracion | Chat grupal del colectivo |
| multimedia | Integracion | Documentos compartidos |
| red-social | Integracion | Feed de actividad |
| eventos | Contenedor | Eventos del colectivo |
| comunidades | Relacion | Colectivos dentro de comunidades |

## Hooks y Filtros

### Actions

```php
// Colectivo creado
do_action('flavor_colectivo_creado', $colectivo_id, $creador_id);

// Usuario se une a colectivo
do_action('flavor_colectivo_miembro_unido', $colectivo_id, $usuario_id, $rol);

// Usuario abandona colectivo
do_action('flavor_colectivo_miembro_salido', $colectivo_id, $usuario_id);

// Proyecto creado
do_action('flavor_colectivo_proyecto_creado', $proyecto_id, $colectivo_id);

// Asamblea convocada
do_action('flavor_colectivo_asamblea_convocada', $asamblea_id, $colectivo_id);

// Asistencia confirmada
do_action('flavor_colectivo_asistencia_confirmada', $asamblea_id, $usuario_id);
```

### Filters

```php
// Filtrar tipos permitidos
apply_filters('flavor_colectivos_tipos_permitidos', $tipos);

// Personalizar roles de miembro
apply_filters('flavor_colectivos_roles_miembro', $roles);

// Modificar limite de colectivos por usuario
apply_filters('flavor_colectivos_max_por_usuario', $limite, $usuario_id);

// Filtrar sectores disponibles
apply_filters('flavor_colectivos_sectores', $sectores);

// Personalizar estados de proyecto
apply_filters('flavor_colectivos_estados_proyecto', $estados);
```

## API REST

### Endpoints

```
GET    /wp-json/flavor/v1/colectivos
       Parametros: tipo, sector, busqueda, limite

GET    /wp-json/flavor/v1/colectivos/{id}
       Retorna detalle completo del colectivo

POST   /wp-json/flavor/v1/colectivos/{id}/unirse
       Solicita membresia (autenticado)

GET    /wp-json/flavor/v1/colectivos/{id}/miembros
       Lista miembros del colectivo

GET    /wp-json/flavor/v1/colectivos/mis-colectivos
       Colectivos del usuario autenticado
       Parametros: rol, estado
```

## AJAX Handlers

### Con autenticacion (wp_ajax_)

```php
colectivos_crear              // Crear nuevo colectivo
colectivos_unirse             // Solicitar membresia
colectivos_abandonar          // Abandonar colectivo
colectivos_crear_proyecto     // Crear proyecto
colectivos_actualizar_proyecto // Actualizar proyecto
colectivos_convocar_asamblea  // Convocar asamblea
colectivos_confirmar_asistencia // Confirmar asistencia
colectivos_aprobar_miembro    // Aprobar solicitud de membresia
```

### Publicos (wp_ajax_nopriv_)

```php
colectivos_obtener            // Obtener datos de colectivo
colectivos_listar             // Listar colectivos
```

## Configuracion

```php
'colectivos' => [
    'enabled' => true,
    'requiere_aprobacion' => false,           // Aprobacion admin para crear
    'maximo_colectivos_por_usuario' => 5,     // Limite de creacion
    'permitir_proyectos' => true,
    'permitir_asambleas' => true,
    'tipos_permitidos' => [
        'asociacion',
        'cooperativa',
        'ong',
        'colectivo',
        'plataforma'
    ],
    'roles_miembro' => [
        'presidente' => 'Presidente/a',
        'secretario' => 'Secretario/a',
        'tesorero'   => 'Tesorero/a',
        'vocal'      => 'Vocal',
        'miembro'    => 'Miembro',
    ],
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `colectivo_crear` | Crear colectivos |
| `colectivo_gestionar` | Administrar colectivo propio |
| `colectivo_convocar` | Convocar asambleas (presidente/secretario) |
| `colectivo_aprobar` | Aprobar solicitudes de membresia |
| `colectivos_admin` | Administrar todos los colectivos |

## Roles de Miembro

| Rol | Permisos |
|-----|----------|
| presidente | Gestion completa, convocar asambleas, aprobar miembros, no puede abandonar |
| secretario | Convocar asambleas, aprobar miembros, crear proyectos |
| tesorero | Gestionar presupuestos de proyectos |
| vocal | Participar en decisiones, crear proyectos |
| miembro | Participar en asambleas y proyectos |

## Sectores Disponibles

```php
$sectores = [
    'cultura'         => 'Cultura y Arte',
    'medioambiente'   => 'Medio Ambiente',
    'educacion'       => 'Educacion',
    'salud'           => 'Salud',
    'derechos'        => 'Derechos Humanos',
    'economia_social' => 'Economia Social',
    'tecnologia'      => 'Tecnologia',
    'deportes'        => 'Deportes',
    'vecinal'         => 'Vecinal',
    'otro'            => 'Otro',
];
```

## Principios Gailu

Este modulo implementa los principios:
- **gobernanza**: Facilita la organizacion democratica y participativa
- **cuidados**: Promueve el apoyo mutuo entre miembros

Contribuye a:
- **cohesion**: Fortalece los lazos comunitarios
- **autonomia**: Permite autoorganizacion de grupos

## AI Tools

El modulo proporciona definiciones de herramientas para Claude:

```php
colectivos_listar    // Lista colectivos con filtros
colectivos_buscar    // Busca por termino
colectivos_crear     // Crea nuevo colectivo (autenticado)
```

## Knowledge Base

Comandos de voz/chat disponibles:
- "ver colectivos" - Lista todos los colectivos activos
- "buscar colectivo [nombre]" - Busca por nombre o sector
- "crear colectivo" - Inicia proceso de creacion
- "mis colectivos" - Muestra tus colectivos
- "proyectos de [colectivo]" - Lista proyectos
- "asambleas de [colectivo]" - Lista asambleas
- "estadisticas de [colectivo]" - Muestra estadisticas

## Componentes Web (Visual Builder)

| Componente | Descripcion |
|------------|-------------|
| `colectivos_hero` | Seccion hero para pagina de colectivos |
| `colectivos_grid` | Listado en tarjetas con filtros |
| `colectivos_proyectos` | Proyectos activos con progreso |

## Integraciones

### Providers aceptados
- multimedia
- articulos_social
- eventos
- podcast

### Targets de integracion
```php
[
    'type'    => 'table',
    'table'   => 'wp_flavor_colectivos',
    'context' => 'normal',
]
```

## Contextos de Dashboard

- **Cliente:** colectivos, asociacion, gobernanza, comunidad
- **Admin:** colectivos, gobernanza, admin
