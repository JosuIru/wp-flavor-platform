# Modulo: Crowdfunding

> Financiacion colectiva para proyectos culturales, sociales y comunitarios

## Descripcion

Sistema completo de crowdfunding/micromecenazgo que permite crear campanas de financiacion colectiva. Soporta multiples monedas (EUR, SEMILLA, HOURS), diferentes modalidades de financiacion, tiers/recompensas y distribucion transparente de fondos. Disenado para integrarse con otros modulos como Kulturaka, eventos, colectivos y comunidades.

## Archivos Principales

```
includes/modules/crowdfunding/
├── class-crowdfunding-module.php           # Clase principal
├── install.php                             # Instalacion BD
├── frontend/
│   └── class-crowdfunding-frontend-controller.php
├── views/
│   └── dashboard.php                       # Panel admin
├── templates/
│   └── proyecto-single.php                 # Vista de proyecto
└── assets/
    ├── css/
    └── js/
```

## Tablas de Base de Datos

### wp_flavor_crowdfunding_proyectos
Tabla principal de proyectos/campanas de crowdfunding.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| titulo | varchar(255) | Titulo del proyecto |
| slug | varchar(255) UNIQUE | URL amigable |
| descripcion | text | Descripcion corta |
| contenido | longtext | Contenido completo |
| extracto | varchar(500) | Extracto |
| modulo_origen | varchar(50) | Modulo que origino el proyecto |
| entidad_tipo | varchar(50) | Tipo de entidad relacionada |
| entidad_id | bigint(20) | ID de entidad relacionada |
| creador_id | bigint(20) | FK usuario creador |
| colectivo_id | bigint(20) | FK colectivo si aplica |
| comunidad_id | bigint(20) | FK comunidad si aplica |
| tipo | enum | album/tour/produccion/equipamiento/espacio/evento/social/emergencia/otro |
| categoria | varchar(100) | Categoria del proyecto |
| objetivo_eur | decimal(12,2) | Objetivo en euros |
| objetivo_semilla | decimal(12,2) | Objetivo en SEMILLA |
| objetivo_hours | decimal(10,2) | Objetivo en HOURS |
| recaudado_eur | decimal(12,2) | Recaudado en euros |
| recaudado_semilla | decimal(12,2) | Recaudado en SEMILLA |
| recaudado_hours | decimal(10,2) | Recaudado en HOURS |
| moneda_principal | enum | eur/semilla/hours/mixta |
| acepta_eur | tinyint(1) | Acepta euros |
| acepta_semilla | tinyint(1) | Acepta SEMILLA |
| acepta_hours | tinyint(1) | Acepta HOURS |
| minimo_aportacion | decimal(10,2) | Aportacion minima |
| permite_aportacion_libre | tinyint(1) | Permite aportacion libre |
| modalidad | enum | todo_o_nada/flexible/donacion |
| fecha_inicio | datetime | Inicio de la campana |
| fecha_fin | datetime | Fin de la campana |
| dias_duracion | int | Duracion en dias |
| porcentaje_creador | decimal(5,2) | % para el creador |
| porcentaje_espacio | decimal(5,2) | % para el espacio |
| porcentaje_comunidad | decimal(5,2) | % para fondo comunitario |
| porcentaje_plataforma | decimal(5,2) | % para la plataforma |
| porcentaje_emergencia | decimal(5,2) | % para fondo de emergencia |
| desglose_presupuesto | json | Desglose detallado |
| imagen_principal | varchar(500) | URL imagen principal |
| video_principal | varchar(500) | URL video |
| galeria | json | Galeria de imagenes |
| aportantes_count | int | Numero de mecenas |
| visualizaciones | int | Contador de vistas |
| estado | enum | borrador/revision/activo/pausado/exitoso/fallido/cancelado |
| destacado | tinyint(1) | Es destacado |
| verificado | tinyint(1) | Proyecto verificado |
| visibilidad | enum | publico/comunidad/privado |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** slug, creador_id, colectivo_id, comunidad_id, tipo, estado, modalidad, fecha_inicio, fecha_fin, entidad, destacado, busqueda (FULLTEXT)

### wp_flavor_crowdfunding_tiers
Niveles de recompensa.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| proyecto_id | bigint(20) | FK proyecto |
| nombre | varchar(150) | Nombre del tier |
| descripcion | text | Descripcion |
| importe_eur | decimal(10,2) | Importe en euros |
| importe_semilla | decimal(10,2) | Importe en SEMILLA |
| importe_hours | decimal(10,2) | Importe en HOURS |
| recompensas | json | Lista de recompensas |
| incluye_tiers_anteriores | tinyint(1) | Acumula recompensas |
| cantidad_limitada | int | Limite de unidades |
| cantidad_vendida | int | Unidades vendidas |
| disponible | tinyint(1) | Esta disponible |
| fecha_entrega_estimada | date | Fecha estimada entrega |
| requiere_envio | tinyint(1) | Necesita envio |
| coste_envio | decimal(10,2) | Coste de envio |
| imagen | varchar(500) | Imagen del tier |
| destacado | tinyint(1) | Es destacado |
| orden | int | Orden de visualizacion |
| activo | tinyint(1) | Esta activo |

### wp_flavor_crowdfunding_aportaciones
Aportaciones/contribuciones de mecenas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| proyecto_id | bigint(20) | FK proyecto |
| tier_id | bigint(20) | FK tier (opcional) |
| usuario_id | bigint(20) | FK usuario (opcional) |
| nombre | varchar(200) | Nombre del aportante |
| email | varchar(200) | Email |
| telefono | varchar(50) | Telefono |
| importe | decimal(12,2) | Importe aportado |
| moneda | enum | eur/semilla/hours |
| importe_eur_equivalente | decimal(12,2) | Equivalente en EUR |
| estado | enum | pendiente/procesando/completada/fallida/reembolsada/cancelada |
| metodo_pago | varchar(50) | Metodo de pago |
| referencia_pago | varchar(100) | Referencia |
| transaccion_id | varchar(100) | ID transaccion |
| fecha_pago | datetime | Fecha del pago |
| recompensas_seleccionadas | json | Recompensas elegidas |
| recompensas_entregadas | tinyint(1) | Entregadas |
| anonimo | tinyint(1) | Es anonimo |
| mensaje_publico | text | Mensaje visible |
| mensaje_privado | text | Mensaje al creador |
| es_recurrente | tinyint(1) | Aportacion recurrente |
| frecuencia_recurrencia | enum | mensual/trimestral/anual |
| comision_plataforma | decimal(10,2) | Comision plataforma |
| comision_pasarela | decimal(10,2) | Comision pasarela |
| importe_neto | decimal(12,2) | Importe neto |
| ip_address | varchar(45) | IP del aportante |

### wp_flavor_crowdfunding_actualizaciones
Actualizaciones publicadas por el creador.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| proyecto_id | bigint(20) | FK proyecto |
| autor_id | bigint(20) | FK autor |
| titulo | varchar(255) | Titulo |
| contenido | longtext | Contenido |
| tipo | enum | novedad/hito/agradecimiento/problema/entrega/otro |
| imagen | varchar(500) | Imagen |
| video | varchar(500) | Video |
| solo_aportantes | tinyint(1) | Solo visible para mecenas |
| destacada | tinyint(1) | Es destacada |
| notificacion_enviada | tinyint(1) | Notificacion enviada |

### wp_flavor_crowdfunding_comentarios
Comentarios y preguntas en proyectos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| proyecto_id | bigint(20) | FK proyecto |
| usuario_id | bigint(20) | FK usuario |
| aportacion_id | bigint(20) | FK aportacion si aplica |
| padre_id | bigint(20) | FK comentario padre |
| contenido | text | Contenido |
| tipo | enum | comentario/pregunta/agradecimiento/respuesta |
| estado | enum | pendiente/aprobado/rechazado/spam |
| es_del_creador | tinyint(1) | Es del creador |
| destacado | tinyint(1) | Destacado |

### wp_flavor_crowdfunding_categorias
Categorias de proyectos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(100) | Nombre |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripcion |
| icono | varchar(50) | Icono dashicons |
| color | varchar(7) | Color hex |
| imagen | varchar(500) | Imagen |
| padre_id | bigint(20) | Categoria padre |
| activa | tinyint(1) | Esta activa |
| orden | int | Orden |

## Shortcodes

### Listados

```php
[crowdfunding_listado]
// Lista de proyectos activos
// - limite: numero (default: 12)
// - tipo: album|tour|produccion|evento|social|etc
// - categoria: slug
// - columnas: 1|2|3|4

[crowdfunding_destacados]
// Proyectos destacados
// - limite: numero (default: 4)
```

### Detalle

```php
[crowdfunding_proyecto]
// Pagina de un proyecto individual
// - id: ID del proyecto
// - slug: slug del proyecto
```

### Gestion

```php
[crowdfunding_crear]
// Formulario para crear nuevo proyecto

[crowdfunding_mis_proyectos]
// Proyectos creados por el usuario actual
```

## Dashboard Tab

**Clase:** `Flavor_Chat_Crowdfunding_Module`

**Estadisticas mostradas:**
- Total de proyectos
- Proyectos activos
- Total recaudado (EUR y SEMILLA)
- Numero de mecenas
- Tasa de exito
- Grafico de recaudacion por tipo
- Ultimas aportaciones
- Proyectos en curso con progreso

**Accesos rapidos:**
- Todos los proyectos
- Crear proyecto
- Proyectos activos
- Mis aportaciones
- Mis proyectos
- Estadisticas
- Portal publico

## Modalidades de Financiacion

| Modalidad | Descripcion |
|-----------|-------------|
| `todo_o_nada` | Solo se entrega si se alcanza el objetivo |
| `flexible` | Se entrega lo recaudado aunque no se alcance |
| `donacion` | Sin objetivo minimo, siempre exitoso |

## Tipos de Proyecto

| Tipo | Label |
|------|-------|
| `album` | Album/Grabacion |
| `tour` | Gira/Tour |
| `produccion` | Produccion |
| `equipamiento` | Equipamiento |
| `espacio` | Espacio |
| `evento` | Evento |
| `social` | Proyecto Social |
| `emergencia` | Emergencia |
| `otro` | Otro |

## Monedas Soportadas

| Moneda | Descripcion | Tasa Default |
|--------|-------------|--------------|
| `eur` | Euros | 1.00 |
| `semilla` | Moneda social SEMILLA | 0.10 EUR |
| `hours` | Banco de tiempo HOURS | 10.00 EUR |

## Hooks y Filtros

### Actions

```php
// Proyecto creado
do_action('flavor_crowdfunding_proyecto_creado', $proyecto_id, $datos);

// Aportacion registrada (antes del pago)
do_action('flavor_crowdfunding_aportacion_registrada', $aportacion_id, $proyecto_id, $datos);

// Aportacion completada (pago exitoso)
do_action('flavor_crowdfunding_aportacion_completada', $aportacion_id, $proyecto_id);

// Hito de recaudacion alcanzado (25%, 50%, 75%, 100%)
do_action('flavor_crowdfunding_hito_alcanzado', $proyecto_id, $hito, $porcentaje);

// Proyecto finalizado
do_action('flavor_crowdfunding_proyecto_finalizado', $proyecto_id, $nuevo_estado);

// Crear proyecto para entidad de otro modulo
do_action('flavor_crowdfunding_crear_para_entidad', $modulo, $entidad_tipo, $entidad_id, $datos);
```

### Filters

```php
// Obtener proyectos de una entidad
apply_filters('flavor_crowdfunding_proyectos_entidad', $proyectos, $modulo, $entidad_id);
```

## Configuracion

```php
'crowdfunding' => [
    'enabled' => true,

    // Comisiones
    'comision_plataforma' => 5.0,        // % para la plataforma
    'comision_pasarela' => 2.5,          // % para pasarela de pago

    // Distribucion por defecto (estilo Kulturaka)
    'distribucion_default' => [
        'creador' => 70,                 // % para el creador
        'espacio' => 10,                 // % para el espacio
        'comunidad' => 10,               // % para fondo comunitario
        'plataforma' => 5,               // % para infraestructura
        'emergencia' => 5,               // % para fondo de emergencia
    ],

    // Limites
    'minimo_objetivo' => 100,            // Objetivo minimo EUR
    'maximo_objetivo' => 100000,         // Objetivo maximo EUR
    'minimo_aportacion' => 1,            // Aportacion minima
    'maximo_duracion_dias' => 90,        // Duracion maxima campana

    // Monedas aceptadas
    'acepta_eur' => true,
    'acepta_semilla' => true,
    'acepta_hours' => true,
    'tasa_semilla_eur' => 0.10,          // 1 SEMILLA = 0.10 EUR
    'tasa_hours_eur' => 10.00,           // 1 HOUR = 10 EUR

    // Verificacion
    'requiere_verificacion' => false,
    'auto_aprobar' => true,

    // Notificaciones de hitos
    'notificar_hitos' => [25, 50, 75, 100],
]
```

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| kulturaka | Integracion | Crowdfunding para artistas |
| eventos | Integracion | Financiar eventos |
| colectivos | Contenedor | Proyectos de colectivo |
| comunidades | Contenedor | Proyectos comunitarios |
| campanias | Relacion | Campanias con crowdfunding |
| socios | Relacion | Descuentos para socios |

## API REST

```php
// Endpoints registrados via register_rest_routes()
// Base: /wp-json/flavor/v1/crowdfunding/

GET    /proyectos                    // Listar proyectos
GET    /proyectos/{id}               // Obtener proyecto
POST   /proyectos                    // Crear proyecto
PUT    /proyectos/{id}               // Actualizar proyecto
DELETE /proyectos/{id}               // Eliminar proyecto

POST   /proyectos/{id}/aportacion    // Registrar aportacion
POST   /aportaciones/{id}/confirmar  // Confirmar pago

GET    /proyectos/{id}/tiers         // Listar tiers
POST   /proyectos/{id}/tiers         // Crear tier

GET    /proyectos/{id}/actualizaciones   // Listar actualizaciones
POST   /proyectos/{id}/actualizaciones   // Crear actualizacion
```

## Cron Jobs

```php
// Verificar proyectos finalizados (cada hora)
add_action('flavor_crowdfunding_check_finalizados', [$this, 'procesar_proyectos_finalizados']);

// Procesa proyectos activos cuya fecha_fin ha pasado
// - Si alcanzo objetivo: estado = 'exitoso'
// - Si es flexible y recaudo algo: estado = 'exitoso'
// - Si es donacion: estado = 'exitoso'
// - En otro caso: estado = 'fallido'
```

## Ejemplos de Uso

### Crear un proyecto programaticamente

```php
$modulo = Flavor_Chat_Module_Loader::get_instance()->get_module('crowdfunding');

$proyecto_id = $modulo->crear_proyecto([
    'titulo' => 'Grabacion de mi primer album',
    'descripcion' => 'Necesito financiacion para grabar mi primer disco...',
    'objetivo_eur' => 5000,
    'tipo' => 'album',
    'modalidad' => 'flexible',
    'dias_duracion' => 45,
    'acepta_semilla' => true,
    'tiers' => [
        [
            'nombre' => 'Mecenas',
            'importe_eur' => 10,
            'descripcion' => 'Tu nombre en los creditos',
            'recompensas' => ['Nombre en creditos', 'Acceso anticipado'],
        ],
        [
            'nombre' => 'Colaborador',
            'importe_eur' => 25,
            'descripcion' => 'Disco firmado + extras',
            'recompensas' => ['Disco firmado', 'Camiseta', 'Todo lo anterior'],
        ],
    ],
]);
```

### Registrar una aportacion

```php
$aportacion_id = $modulo->registrar_aportacion([
    'proyecto_id' => 123,
    'tier_id' => 5,
    'nombre' => 'Juan Garcia',
    'email' => 'juan@example.com',
    'importe' => 25,
    'moneda' => 'eur',
    'mensaje_publico' => 'Mucha suerte con el proyecto!',
]);

// Confirmar despues del pago exitoso
$modulo->confirmar_aportacion($aportacion_id, [
    'referencia' => 'PAY-123456',
    'transaccion_id' => 'TXN-789',
]);
```

### Crear proyecto para otra entidad

```php
// Desde el modulo de eventos
do_action('flavor_crowdfunding_crear_para_entidad', 'eventos', 'evento', 456, [
    'titulo' => 'Financiacion Festival de Verano 2024',
    'objetivo_eur' => 15000,
    'tipo' => 'evento',
]);
```

### Obtener proyectos de una entidad

```php
$resultado = apply_filters('flavor_crowdfunding_proyectos_entidad', [], 'colectivos', 789);
$proyectos = $resultado['proyectos'];
```

## Categorias por Defecto

El sistema incluye las siguientes categorias predefinidas:

| Categoria | Slug | Icono | Color |
|-----------|------|-------|-------|
| Musica | musica | dashicons-format-audio | #ec4899 |
| Teatro y Artes Escenicas | teatro | dashicons-tickets-alt | #ef4444 |
| Audiovisual | audiovisual | dashicons-video-alt3 | #8b5cf6 |
| Artes Visuales | artes-visuales | dashicons-art | #3b82f6 |
| Literatura | literatura | dashicons-book | #10b981 |
| Espacios Culturales | espacios | dashicons-building | #f59e0b |
| Eventos y Festivales | eventos | dashicons-calendar-alt | #06b6d4 |
| Proyectos Sociales | social | dashicons-groups | #84cc16 |
| Educacion | educacion | dashicons-welcome-learn-more | #6366f1 |
| Medio Ambiente | medioambiente | dashicons-palmtree | #22c55e |
| Emergencias | emergencias | dashicons-sos | #dc2626 |
| Otros | otros | dashicons-plus-alt | #6b7280 |

## Principios Gailu

Este modulo se alinea con los principios del ecosistema Gailu:

- **Economia Solidaria**: Financiacion colectiva como alternativa a la banca tradicional
- **Cooperacion**: Los proyectos benefician a creadores, espacios y comunidad
- **Redistribucion**: Porcentaje de lo recaudado va a fondos comunitarios
- **Cultura**: Apoyo a proyectos culturales y artisticos locales
