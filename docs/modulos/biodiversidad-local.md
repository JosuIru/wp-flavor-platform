# Modulo: Biodiversidad Local

> Catalogacion comunitaria de especies, avistamientos y proyectos de conservacion

## Descripcion

Sistema de ciencia ciudadana para la catalogacion colaborativa de la biodiversidad local. Permite registrar especies de flora y fauna, documentar avistamientos con geolocalizacion, organizar proyectos de conservacion y validar contribuciones de forma comunitaria. Incluye estados de conservacion basados en la clasificacion IUCN.

## Archivos Principales

```
includes/modules/biodiversidad-local/
├── class-biodiversidad-local-module.php           # Clase principal
├── class-biodiversidad-local-dashboard-tab.php    # Tab del dashboard
├── class-biodiversidad-local-widget.php           # Widget resumen
├── frontend/
│   └── class-biodiversidad-local-frontend-controller.php  # Controlador frontend
├── views/
│   └── dashboard.php                              # Vista admin dashboard
├── templates/
│   ├── catalogo.php                               # Catalogo de especies
│   ├── mapa.php                                   # Mapa de avistamientos
│   ├── mis-avistamientos.php                      # Avistamientos del usuario
│   ├── proyectos.php                              # Proyectos de conservacion
│   └── registrar.php                              # Formulario registrar avistamiento
└── assets/
    ├── css/
    │   ├── biodiversidad-local.css
    │   └── biodiversidad-frontend.css
    └── js/
        └── biodiversidad-frontend.js
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripcion |
|-----|------|-------------|
| Especie | `bl_especie` | Especies del catalogo local |
| Avistamiento | `bl_avistamiento` | Registros de avistamientos |
| Proyecto | `bl_proyecto` | Proyectos de conservacion |

## Taxonomias

| Taxonomia | Slug | Aplicada a |
|-----------|------|-----------|
| Categoria | `bl_categoria` | bl_especie |
| Habitat | `bl_habitat` | bl_especie, bl_avistamiento |

## Tablas de Base de Datos

### wp_flavor_biodiversidad_especies
Catalogo de especies locales.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre_comun | varchar(255) | Nombre popular |
| nombre_cientifico | varchar(255) | Nombre cientifico |
| familia | varchar(100) | Familia taxonomica |
| categoria | varchar(50) | flora/fauna_vertebrados/fauna_invertebrados |
| estado_conservacion | varchar(50) | Estado IUCN |
| descripcion | longtext | Descripcion detallada |
| habitat | text | Habitat natural |
| comportamiento | text | Comportamiento observado |
| temporada | varchar(100) | Epoca del ano |
| amenazas | text | Amenazas conocidas |
| curiosidades | text | Datos curiosos |
| imagen | varchar(500) | URL imagen principal |
| verificada | tinyint(1) | Especie verificada por expertos |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** nombre_comun, nombre_cientifico, categoria, estado_conservacion

### wp_flavor_biodiversidad_avistamientos
Registros de avistamientos de usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| especie_id | bigint(20) | FK especie |
| usuario_id | bigint(20) | FK usuario |
| fecha | date | Fecha del avistamiento |
| hora | time | Hora aproximada |
| cantidad | int | Numero de individuos |
| latitud | decimal(10,8) | Coordenada GPS |
| longitud | decimal(11,8) | Coordenada GPS |
| ubicacion_nombre | varchar(255) | Descripcion del lugar |
| notas | text | Observaciones adicionales |
| imagen | varchar(500) | Foto del avistamiento |
| estado | enum | pendiente/validado/rechazado |
| validador_id | bigint(20) | FK usuario validador |
| fecha_validacion | datetime | Fecha de validacion |
| fecha_registro | datetime | Fecha de registro |

**Indices:** especie_id, usuario_id, fecha, estado, latitud, longitud

### wp_flavor_biodiversidad_proyectos
Proyectos de conservacion.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre del proyecto |
| descripcion | longtext | Descripcion completa |
| objetivos | longtext | Objetivos del proyecto |
| tipo | varchar(50) | Tipo de proyecto |
| imagen | varchar(500) | Imagen destacada |
| ubicacion | varchar(255) | Zona del proyecto |
| fecha_inicio | date | Fecha de inicio |
| fecha_fin | date | Fecha estimada fin |
| estado | enum | activo/completado/pausado |
| organizador_id | bigint(20) | FK usuario creador |
| created_at | datetime | Fecha creacion |

**Indices:** tipo, estado, fecha_inicio

### wp_flavor_biodiversidad_participantes
Participantes en proyectos de conservacion.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| proyecto_id | bigint(20) | FK proyecto |
| usuario_id | bigint(20) | FK usuario |
| estado | enum | activo/inactivo |
| fecha_union | datetime | Fecha de union |

**Indices:** proyecto_id, usuario_id, estado

## Categorias de Especies

```php
const CATEGORIAS_ESPECIES = [
    'flora' => [
        'nombre' => 'Flora',
        'icono' => 'dashicons-palmtree',
        'color' => '#22c55e',
        'subcategorias' => ['arboles', 'arbustos', 'plantas_herbaceas', 'hongos', 'liquenes', 'algas'],
    ],
    'fauna_vertebrados' => [
        'nombre' => 'Fauna Vertebrada',
        'icono' => 'dashicons-pets',
        'color' => '#f97316',
        'subcategorias' => ['aves', 'mamiferos', 'reptiles', 'anfibios', 'peces'],
    ],
    'fauna_invertebrados' => [
        'nombre' => 'Invertebrados',
        'icono' => 'dashicons-admin-site-alt',
        'color' => '#a855f7',
        'subcategorias' => ['insectos', 'aracnidos', 'moluscos', 'crustaceos', 'otros'],
    ],
];
```

## Estados de Conservacion (IUCN)

| Codigo | Estado | Color |
|--------|--------|-------|
| NE | No Evaluada | #6b7280 |
| LC | Preocupacion Menor | #22c55e |
| NT | Casi Amenazada | #84cc16 |
| VU | Vulnerable | #eab308 |
| EN | En Peligro | #f97316 |
| CR | En Peligro Critico | #ef4444 |
| EW | Extinta en Estado Silvestre | #1f2937 |
| EX | Extinta | #000000 |

## Tipos de Proyecto de Conservacion

| Tipo | Descripcion |
|------|-------------|
| reforestacion | Reforestacion |
| limpieza | Limpieza de Espacios |
| censo | Censo de Especies |
| proteccion | Proteccion de Habitat |
| educacion | Educacion Ambiental |
| polinizadores | Apoyo a Polinizadores |
| fauna_silvestre | Refugio Fauna Silvestre |
| semillas | Banco de Semillas |

## Tipos de Habitat

| Habitat | Descripcion |
|---------|-------------|
| bosque | Bosque |
| pradera | Pradera/Pastizal |
| humedal | Humedal |
| rio | Rio/Arroyo |
| montana | Montana |
| costa | Costa/Litoral |
| urbano | Urbano/Periurbano |
| agricola | Agricola |

## Shortcodes

### Catalogo y Especies

```php
[biodiversidad_catalogo]
// Catalogo de especies con filtros
// - categoria: slug categoria (opcional)
// - por_pagina: numero (default: 12)

[flavor_biodiversidad_catalogo]
// Alias con funcionalidad extendida
// - categoria: slug
// - por_pagina: numero

[flavor_biodiversidad_especie]
// Detalle de una especie
// - especie_id: ID (o desde URL)
```

### Mapa y Avistamientos

```php
[biodiversidad_mapa]
// Mapa interactivo de avistamientos
// - especie_id: filtrar por especie
// - altura: altura CSS (default: 500px)

[flavor_biodiversidad_mapa]
// Alias del mapa

[biodiversidad_registrar]
// Formulario para registrar avistamiento

[flavor_biodiversidad_reportar]
// Alias del formulario de registro

[biodiversidad_mis_avistamientos]
// Lista de avistamientos del usuario

[flavor_biodiversidad_mis_avistamientos]
// Alias
```

### Proyectos

```php
[biodiversidad_proyectos]
// Lista de proyectos de conservacion

[flavor_biodiversidad_proyectos]
// Alias con vista extendida

[flavor_biodiversidad_proyecto]
// Detalle de un proyecto
// - proyecto_id: ID (o desde URL)
```

### Estadisticas

```php
[flavor_biodiversidad_estadisticas]
// Dashboard de estadisticas del modulo
```

## Dashboard Tab

**Clase:** `Flavor_Biodiversidad_Local_Dashboard_Tab`

**Tabs disponibles:**
- `biodiversidad-resumen` - Panel principal con KPIs
- `biodiversidad-avistamientos` - Mis avistamientos

## Widget Dashboard

**Clase:** `Flavor_Biodiversidad_Local_Widget`

Muestra:
- Total de especies catalogadas
- Avistamientos totales
- Proyectos activos
- Avistamientos propios del usuario
- Avistamientos pendientes de validar
- Acceso rapido a catalogo y registro

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/biodiversidad/` | catalogo | Catalogo de especies |
| `/biodiversidad/mapa/` | mapa | Mapa de avistamientos |
| `/biodiversidad/registrar/` | registrar | Nuevo avistamiento |
| `/biodiversidad/proyectos/` | proyectos | Lista proyectos |
| `/biodiversidad/especies/` | archive | Archivo de especies (CPT) |
| `/biodiversidad/avistamientos/` | archive | Archivo de avistamientos |
| `/mi-portal/biodiversidad/` | mis-avistamientos | Avistamientos propios |
| `/mi-portal/biodiversidad/catalogo/` | catalogo | Catalogo desde mi portal |
| `/mi-portal/biodiversidad/nuevo-avistamiento/` | registrar | Registrar desde mi portal |
| `/mi-portal/biodiversidad/proyectos/` | proyectos | Proyectos desde mi portal |

## API REST

### Endpoints

```
GET  /wp-json/flavor/v1/biodiversidad/especies
     Lista especies con paginacion
     Params: per_page, page, categoria

GET  /wp-json/flavor/v1/biodiversidad/especies/{id}
     Detalle de especie

GET  /wp-json/flavor/v1/biodiversidad/avistamientos
     Lista avistamientos validados
     Params: per_page, page

GET  /wp-json/flavor/v1/biodiversidad/mis-avistamientos
     Avistamientos del usuario autenticado
     Auth: requerida

GET  /wp-json/flavor/v1/biodiversidad/proyectos
     Lista proyectos activos
     Params: per_page
```

## AJAX Handlers

| Accion | Callback | Autenticacion |
|--------|----------|---------------|
| `bl_registrar_avistamiento` | Registrar nuevo avistamiento | Si |
| `bl_registrar_especie` | Proponer nueva especie | Si |
| `bl_crear_proyecto` | Crear proyecto de conservacion | Si |
| `bl_participar_proyecto` | Unirse a proyecto | Si |
| `bl_validar_avistamiento` | Validar avistamiento (moderadores) | Si |
| `flavor_biodiversidad_reportar` | Reportar avistamiento | Si |
| `flavor_biodiversidad_validar` | Validar avistamiento | Si |
| `flavor_biodiversidad_buscar_especies` | Autocompletado especies | No |
| `flavor_biodiversidad_unirse_proyecto` | Unirse a proyecto | Si |
| `flavor_biodiversidad_obtener_avistamientos` | Obtener para mapa | No |

## Validacion Comunitaria

El sistema implementa validacion colaborativa de avistamientos:

1. Usuario registra avistamiento (estado: `pendiente`)
2. Otros usuarios validan o rechazan
3. Con 3 validaciones positivas, se publica automaticamente
4. Los moderadores pueden validar directamente

```php
// Estructura de validaciones
$validaciones = [
    [
        'user_id' => 123,
        'es_valido' => true,
        'fecha' => '2024-01-15 10:30:00',
    ],
    // ...
];
```

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| multimedia | Integracion | Galerias de fotos de especies |
| recetas | Integracion | Recetas con plantas comestibles |
| biblioteca | Integracion | Guias de identificacion |
| comunidades | Contenedor | Biodiversidad por comunidad |
| huella-ecologica | Datos | Aporte a indicadores ecologicos |

## Hooks y Filtros

### Actions

```php
// Nuevo avistamiento registrado
do_action('flavor_biodiversidad_avistamiento_registrado', $avistamiento_id, $especie_id, $usuario_id);

// Avistamiento validado
do_action('flavor_biodiversidad_avistamiento_validado', $avistamiento_id);

// Nueva especie propuesta
do_action('flavor_biodiversidad_especie_propuesta', $especie_id, $usuario_id);

// Usuario unido a proyecto
do_action('flavor_biodiversidad_participante_unido', $proyecto_id, $usuario_id);

// Proyecto creado
do_action('flavor_biodiversidad_proyecto_creado', $proyecto_id, $usuario_id);
```

### Filters

```php
// Modificar categorias de especies
apply_filters('flavor_biodiversidad_categorias', $categorias);

// Modificar estados de conservacion
apply_filters('flavor_biodiversidad_estados_conservacion', $estados);

// Validar datos de avistamiento
apply_filters('flavor_biodiversidad_validar_avistamiento', $valido, $datos);

// Numero de validaciones para auto-publicar
apply_filters('flavor_biodiversidad_validaciones_requeridas', 3, $avistamiento_id);
```

## Configuracion

```php
'biodiversidad_local' => [
    'enabled' => true,
    'mostrar_en_dashboard' => true,
    'validaciones_auto_publicar' => 3,
    'permitir_proponer_especies' => true,
    'requiere_foto' => false,
    'requiere_ubicacion' => true,
    'moderar_avistamientos' => true,
    'notificaciones' => [
        'nuevo_avistamiento' => true,
        'avistamiento_validado' => true,
        'proyecto_nuevo' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `bl_ver_catalogo` | Ver catalogo de especies |
| `bl_registrar_avistamiento` | Registrar avistamientos |
| `bl_proponer_especie` | Proponer nuevas especies |
| `bl_validar_avistamiento` | Validar avistamientos de otros |
| `bl_gestionar_proyectos` | Crear y gestionar proyectos |
| `bl_moderar` | Acceso completo de moderacion |

## Valoracion de Conciencia

El modulo incluye una valoracion de alineamiento con principios de conciencia:

| Premisa | Puntuacion | Descripcion |
|---------|------------|-------------|
| Conciencia Fundamental | 19/20 | Reconoce el valor intrinseco de cada especie |
| Abundancia Organizable | 18/20 | Cataloga el conocimiento colectivo como patrimonio |
| Interdependencia Radical | 20/20 | Visualiza conexiones ecosistemicas |
| Madurez Ciclica | 15/20 | Respeta ciclos naturales y temporadas |
| Valor Intrinseco | 15/20 | Documenta sin criterios de utilidad |
| **Total** | **87/100** | |

## Notas de Desarrollo

- Los CPTs se registran con `show_in_menu => false` para integrarlos en el panel unificado
- El mapa usa Leaflet con OpenStreetMap
- Los avistamientos requieren geolocalizacion obligatoria
- Las imagenes se suben a la biblioteca de medios de WordPress
- El sistema de validacion comunitaria implementa gamificacion basica
- Las estadisticas se calculan en tiempo real con cache opcional
- Soporta exportacion de datos para proyectos de ciencia ciudadana
