# Modulo: Avisos Municipales

> Sistema de comunicados oficiales y notificaciones del ayuntamiento

## Descripcion

Plataforma para la gestion y difusion de avisos y comunicados oficiales del ayuntamiento hacia la ciudadania. Permite publicar avisos con diferentes niveles de prioridad, organizarlos por categorias y zonas geograficas, y gestionar suscripciones de usuarios para recibir notificaciones por email o push.

## Archivos Principales

```
includes/modules/avisos-municipales/
├── class-avisos-municipales-module.php      # Modulo principal
├── class-avisos-municipales-api.php         # API REST movil
├── class-avisos-dashboard-widget.php        # Widget dashboard
├── frontend/
│   └── class-avisos-municipales-frontend-controller.php  # Controlador frontend
├── templates/
│   ├── activos.php                          # Lista avisos activos
│   ├── historial.php                        # Historial de avisos
│   ├── suscribirse.php                      # Formulario suscripcion
│   └── urgentes.php                         # Avisos urgentes
├── views/
│   ├── dashboard.php                        # Dashboard admin
│   └── archivo.php                          # Archivo historico
└── assets/
    ├── css/
    │   ├── avisos.css                       # Estilos principales
    │   └── avisos-frontend.css              # Estilos frontend
    └── js/
        ├── avisos.js                        # JavaScript principal
        └── avisos-frontend.js               # JavaScript frontend
```

## Tablas de Base de Datos

### wp_flavor_avisos_municipales
Avisos publicados.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| titulo | varchar(255) | Titulo del aviso |
| contenido | text | Contenido completo |
| extracto | text | Resumen del aviso |
| prioridad | enum | urgente/alta/media/baja |
| categoria | varchar(100) | Categoria (legacy) |
| categoria_id | bigint(20) | FK categoria |
| zona_id | bigint(20) | FK zona geografica |
| estado | enum | borrador/publicado/archivado |
| publicado | tinyint(1) | Esta publicado |
| destacado | tinyint(1) | Aviso destacado |
| autor_id | bigint(20) | FK usuario autor |
| departamento | varchar(100) | Departamento emisor |
| fecha_publicacion | datetime | Fecha publicacion |
| fecha_inicio | datetime | Inicio vigencia |
| fecha_fin | datetime | Fin vigencia (expiracion) |
| fecha_expiracion | datetime | Fecha expiracion (legacy) |
| ubicacion_especifica | varchar(255) | Ubicacion afectada |
| requiere_confirmacion | tinyint(1) | Requiere confirmacion lectura |
| enlace_externo | varchar(500) | URL mas informacion |
| contacto_email | varchar(100) | Email contacto |
| contacto_telefono | varchar(20) | Telefono contacto |
| adjuntos | longtext JSON | Documentos adjuntos |
| tiene_adjuntos | tinyint(1) | Tiene archivos adjuntos |
| total_visualizaciones | int | Contador vistas |
| visualizaciones | int | Vistas (alias) |
| total_confirmaciones | int | Confirmaciones recibidas |
| confirmaciones_count | int | Contador confirmaciones |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** autor_id, estado, prioridad, categoria, fecha_publicacion

### wp_flavor_avisos_categorias
Categorias de avisos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(100) | Nombre categoria |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripcion |
| icono | varchar(50) | Icono (dashicons) |
| color | varchar(20) | Color hexadecimal |
| orden | int | Orden en listados |
| activa | tinyint(1) | Esta activa |
| created_at | datetime | Fecha creacion |

**Categorias por defecto:**
- Corte de agua (`corte-agua`)
- Corte de luz (`corte-luz`)
- Obras publicas (`obras`)
- Eventos (`eventos`)
- Trafico (`trafico`)
- Medio ambiente (`medio-ambiente`)
- Seguridad (`seguridad`)
- Cultura (`cultura`)
- Convocatorias (`convocatorias`)
- Otros (`otros`)

### wp_flavor_avisos_zonas
Zonas geograficas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(100) | Nombre zona |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripcion |
| tipo | enum | municipio/barrio/distrito/calle |
| geometria | text | Datos geograficos |
| activa | tinyint(1) | Esta activa |
| created_at | datetime | Fecha creacion |

### wp_flavor_avisos_suscripciones
Suscripciones de usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| email | varchar(100) | Email (si no usuario) |
| nombre | varchar(100) | Nombre suscriptor |
| categoria_id | bigint(20) | FK categoria suscrita |
| zona_id | bigint(20) | FK zona suscrita |
| categorias_ids | longtext JSON | IDs categorias |
| zonas_ids | longtext JSON | IDs zonas |
| canal | enum | email/push/sms |
| notificar_email | tinyint(1) | Recibir por email |
| notificar_push | tinyint(1) | Recibir push |
| prioridad_minima | varchar(20) | Prioridad minima a notificar |
| activa | tinyint(1) | Suscripcion activa |
| confirmada | tinyint(1) | Email confirmado |
| token_confirmacion | varchar(64) | Token confirmacion |
| created_at | datetime | Fecha suscripcion |

### wp_flavor_avisos_lecturas
Registro de lecturas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| aviso_id | bigint(20) | FK aviso |
| usuario_id | bigint(20) | FK usuario |
| ip_address | varchar(45) | IP del lector |
| user_agent | varchar(255) | Navegador |
| fecha_lectura | datetime | Fecha lectura |

**Unique:** aviso_id + usuario_id

### wp_flavor_avisos_confirmaciones
Confirmaciones de lectura.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| aviso_id | bigint(20) | FK aviso |
| usuario_id | bigint(20) | FK usuario |
| nombre_completo | varchar(100) | Nombre confirmante |
| ip_address | varchar(45) | IP |
| comentario | text | Comentario opcional |
| fecha_confirmacion | datetime | Fecha confirmacion |

**Unique:** aviso_id + usuario_id

### wp_flavor_avisos_push_subscriptions
Suscripciones push del navegador.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| suscripcion_id | bigint(20) | FK suscripcion |
| endpoint | text | URL endpoint push |
| public_key | varchar(255) | Clave publica |
| auth_token | varchar(255) | Token autenticacion |
| user_agent | text | Navegador |
| activa | tinyint(1) | Suscripcion activa |
| created_at | datetime | Fecha creacion |

## Shortcodes

### Modulo Principal (class-avisos-municipales-module.php)

```php
[avisos_activos]
// Lista de avisos activos
// - categoria: slug categoria
// - prioridad: urgente|alta|media|baja
// - limite: numero (default 10)
// - columnas: 1|2|3 (default 1)
// - mostrar_filtros: true|false (default true)

[avisos_zona]
// Avisos filtrados por zona
// - zona: slug zona
// - limite: numero (default 10)

[suscribirse_avisos]
// Formulario de suscripcion
// - titulo: texto personalizado
// - descripcion: texto personalizado

[historial_avisos]
// Historial de avisos pasados
// - limite: numero (default 20)
// - desde: fecha inicio (Y-m-d)
// - hasta: fecha fin (Y-m-d)

[aviso_detalle]
// Detalle de un aviso especifico
// - id: ID del aviso (o auto desde URL ?aviso=)

[avisos_urgentes]
// Solo avisos urgentes
// - limite: numero (default 5)
```

### Frontend Controller (class-avisos-municipales-frontend-controller.php)

```php
[flavor_avisos_listado]
// Listado completo con paginacion
// - limite: numero (default 10)
// - categoria: slug
// - prioridad: urgente|alta|media|baja
// - paginacion: true|false (default true)

[flavor_avisos_urgentes]
// Banner de avisos urgentes
// - limite: numero (default 5)

[flavor_avisos_detalle]
// Detalle de aviso
// - id: ID aviso (o auto desde URL ?aviso_id=)

[flavor_avisos_suscripciones]
// Gestion de suscripciones (requiere login)

[flavor_avisos_buscador]
// Buscador de avisos

[flavor_avisos_categorias]
// Grid de categorias con iconos

[flavor_avisos_banner]
// Banner marquee de avisos urgentes

[flavor_avisos_dashboard]
// Dashboard completo del usuario (requiere login)
```

## Dashboard Tab

**Clase:** `Flavor_Avisos_Municipales_Frontend_Controller`

El modulo registra una tab en el dashboard del usuario mediante el filtro `flavor_user_dashboard_tabs`:

**Contenido del dashboard:**
- KPIs: Avisos urgentes, Sin leer, Confirmados, Suscripciones
- Panel de avisos urgentes
- Lista de avisos recientes
- Resumen de suscripciones activas

## Widget de Dashboard

**Clase:** `Flavor_Avisos_Dashboard_Widget`

Widget para dashboards admin y frontend que muestra:
- Contador de avisos activos
- Contador de avisos urgentes (si existen)
- Lista de avisos recientes con prioridad
- Enlaces a administracion

**Configuracion:**
- `widget_id`: avisos-municipales
- `icon`: dashicons-megaphone
- `size`: medium
- `category`: comunidad
- `cache_time`: 120 segundos

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/avisos/` | index | Dashboard avisos |
| `/mi-portal/avisos/?aviso={id}` | detalle | Ver aviso completo |
| `/mi-portal/avisos/?seccion=todos` | listado | Todos los avisos |
| `/mi-portal/avisos/?seccion=detalle&aviso_id={id}` | detalle | Detalle aviso |
| `/mi-portal/avisos/?seccion=suscripciones` | suscripciones | Gestionar suscripciones |
| `/mi-portal/avisos-municipales/` | portal | Portal publico |

## API REST

### Endpoints Movil (class-avisos-municipales-api.php)

```
GET  /wp-json/flavor-chat-ia/v1/avisos-municipales
     ?categoria={slug}
     Obtiene lista de avisos (requiere autenticacion)
     Respuesta: { success, avisos[], categorias[], mis_suscripciones[] }

POST /wp-json/flavor-chat-ia/v1/avisos-municipales/{id}/leer
     Marca aviso como leido (requiere autenticacion)

POST /wp-json/flavor-chat-ia/v1/avisos-municipales/suscripciones
     Body: { categorias: [] }
     Actualiza suscripciones del usuario
```

### Endpoints Principales (class-avisos-municipales-module.php)

```
GET  /wp-json/flavor-chat/v1/avisos
     ?categoria={id}&zona={id}&prioridad={str}&busqueda={str}
     &pagina={n}&por_pagina={n}&orden={str}&direccion={ASC|DESC}
     Lista publica de avisos

GET  /wp-json/flavor-chat/v1/avisos/{id}
     Detalle de un aviso

POST /wp-json/flavor-chat/v1/avisos
     Crear aviso (admin)

PUT  /wp-json/flavor-chat/v1/avisos/{id}
     Actualizar aviso (admin)

DELETE /wp-json/flavor-chat/v1/avisos/{id}
     Eliminar aviso (admin)

GET  /wp-json/flavor-chat/v1/avisos/categorias
     Lista de categorias

GET  /wp-json/flavor-chat/v1/avisos/zonas
     Lista de zonas

POST /wp-json/flavor-chat/v1/avisos/suscribir
     Suscribirse a avisos

POST /wp-json/flavor-chat/v1/avisos/{id}/confirmar
     Confirmar lectura (requiere login)

GET  /wp-json/flavor-chat/v1/avisos/estadisticas
     Estadisticas (admin)
```

## AJAX Handlers

### Publicos

```php
// Listar avisos
wp_ajax_flavor_avisos_listar
wp_ajax_nopriv_flavor_avisos_listar

// Ver aviso individual
wp_ajax_flavor_avisos_ver
wp_ajax_nopriv_flavor_avisos_ver

// Suscribirse
wp_ajax_flavor_avisos_suscribir
wp_ajax_nopriv_flavor_avisos_suscribir

// Registrar visualizacion
wp_ajax_flavor_avisos_registrar_visualizacion
wp_ajax_nopriv_flavor_avisos_registrar_visualizacion

// Obtener avisos (frontend controller)
wp_ajax_flavor_avisos_obtener
wp_ajax_nopriv_flavor_avisos_obtener
```

### Autenticados

```php
// Marcar como leido
wp_ajax_flavor_avisos_marcar_leido

// Confirmar lectura
wp_ajax_flavor_avisos_confirmar_lectura
wp_ajax_flavor_avisos_confirmar

// Registrar push subscription
wp_ajax_flavor_avisos_registrar_push

// Desuscribirse
wp_ajax_flavor_avisos_desuscribir
```

### Admin

```php
// CRUD de avisos
wp_ajax_flavor_avisos_crear
wp_ajax_flavor_avisos_actualizar
wp_ajax_flavor_avisos_eliminar

// Estadisticas
wp_ajax_flavor_avisos_estadisticas

// Enviar notificaciones
wp_ajax_flavor_avisos_enviar_notificaciones
```

## Hooks y Filtros

### Actions

```php
// Aviso publicado
do_action('flavor_aviso_publicado', $aviso_id, $datos);

// Aviso actualizado
do_action('flavor_aviso_actualizado', $aviso_id, $datos);

// Aviso eliminado
do_action('flavor_aviso_eliminado', $aviso_id);

// Aviso leido
do_action('flavor_aviso_leido', $aviso_id, $usuario_id);

// Lectura confirmada
do_action('flavor_aviso_confirmado', $aviso_id, $usuario_id);

// Nueva suscripcion
do_action('flavor_avisos_suscripcion', $suscripcion_id, $datos);

// Notificaciones enviadas
do_action('flavor_avisos_notificaciones_enviadas', $aviso_id, $count);

// Cron: enviar avisos programados
do_action('flavor_avisos_enviar_programados');
```

### Filters

```php
// Categorias disponibles
apply_filters('flavor_avisos_categorias', $categorias);

// Zonas disponibles
apply_filters('flavor_avisos_zonas', $zonas);

// Prioridades configuracion
apply_filters('flavor_avisos_prioridades', $prioridades);

// Validar datos de aviso
apply_filters('flavor_aviso_validar', $valido, $datos);

// Query de avisos
apply_filters('flavor_avisos_query', $sql, $args);

// Datos de aviso para mostrar
apply_filters('flavor_aviso_datos', $datos, $aviso);

// Template de notificacion email
apply_filters('flavor_avisos_email_template', $template, $aviso);

// Destinatarios de notificacion
apply_filters('flavor_avisos_destinatarios', $destinatarios, $aviso);
```

## Configuracion

```php
'avisos_municipales' => [
    'enabled' => true,
    'enviar_push_notifications' => true,
    'enviar_email_notifications' => true,
    'requiere_confirmacion_lectura' => false,
    'dias_expiracion_default' => 30,
    'avisos_por_pagina' => 10,
    'mostrar_visualizaciones' => true,
    'permitir_adjuntos' => true,
    'max_adjuntos' => 5,
    'vapid_public_key' => '',
    'vapid_private_key' => '',
    'prioridades' => [
        'urgente' => ['label' => 'Urgente', 'color' => '#dc2626', 'icon' => 'warning'],
        'alta'    => ['label' => 'Alta', 'color' => '#f97316', 'icon' => 'flag'],
        'media'   => ['label' => 'Media', 'color' => '#eab308', 'icon' => 'info'],
        'baja'    => ['label' => 'Baja', 'color' => '#22c55e', 'icon' => 'check'],
    ],
    'categorias_default' => [
        'corte_agua'      => 'Corte de agua',
        'corte_luz'       => 'Corte de luz',
        'obras'           => 'Obras publicas',
        'eventos'         => 'Eventos',
        'trafico'         => 'Trafico y movilidad',
        'medio_ambiente'  => 'Medio ambiente',
        'seguridad'       => 'Seguridad ciudadana',
        'cultura'         => 'Cultura y deportes',
        'convocatorias'   => 'Convocatorias',
        'otros'           => 'Otros',
    ],
    'notificaciones' => [
        'nuevo_aviso' => true,
        'aviso_urgente' => true,
        'recordatorio_confirmacion' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `manage_options` | Gestionar avisos (crear, editar, eliminar) |
| `avisos_ver` | Ver avisos (implicito para usuarios logueados) |
| `avisos_confirmar` | Confirmar lectura de avisos |
| `avisos_suscribirse` | Gestionar suscripciones |

## Prioridades

| Prioridad | Color | Icono | Descripcion |
|-----------|-------|-------|-------------|
| urgente | #dc2626 (rojo) | warning | Avisos criticos que requieren atencion inmediata |
| alta | #f97316 (naranja) | flag | Avisos importantes |
| media | #eab308 (amarillo) | info | Avisos informativos normales |
| baja | #22c55e (verde) | check | Avisos de baja prioridad |

## Sistema de Notificaciones

### Canales

1. **Email**: Notificaciones por correo electronico
   - Confirmacion de suscripcion
   - Nuevos avisos segun suscripciones
   - Avisos urgentes

2. **Push (Navegador)**: Notificaciones push
   - Requiere VAPID keys configuradas
   - Suscripcion desde el navegador

### Flujo de Notificaciones

1. Al publicar un aviso:
   - Se buscan suscriptores por categoria/zona
   - Se filtran por prioridad minima configurada
   - Se envian emails a suscriptores con `notificar_email = 1`
   - Se envian push a suscriptores con `notificar_push = 1`

2. Cron programado (hourly):
   - Procesa avisos programados para publicacion
   - Envia notificaciones pendientes

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Ambito | Avisos por comunidad |
| incidencias | Informacion | Notificar sobre incidencias |
| presupuestos-participativos | Comunicacion | Avisos sobre presupuestos |
| transparencia | Datos | Publicacion oficial |
| eventos | Complementario | Anunciar eventos |

## Ejemplos de Uso

### Mostrar avisos activos en una pagina

```php
// En template o shortcode
echo do_shortcode('[avisos_activos limite="5" mostrar_filtros="false"]');
```

### Obtener avisos programaticamente

```php
$modulo = Flavor_Module_Loader::get_module('avisos_municipales');
$avisos = $modulo->obtener_avisos_activos([
    'categoria_id' => 2,
    'prioridad' => 'urgente',
    'limite' => 10
]);
```

### Crear un aviso desde codigo

```php
$modulo = Flavor_Module_Loader::get_module('avisos_municipales');
$aviso_id = $modulo->crear_aviso([
    'titulo' => 'Corte de agua programado',
    'contenido' => 'Se informa a los vecinos...',
    'categoria_id' => 1,
    'prioridad' => 'alta',
    'zona_id' => 1,
    'fecha_inicio' => date('Y-m-d H:i:s'),
    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+7 days')),
    'publicado' => 1,
]);
```

### Suscribir usuario a categorias

```php
// Via AJAX handler o REST API
$resultado = $modulo->procesar_suscripcion([
    'email' => 'usuario@ejemplo.com',
    'nombre' => 'Juan Perez',
    'categorias' => [1, 2, 3],
    'zonas' => [1],
    'push' => 0,
]);
```

## Migraciones

### Version 1.1.0
- Agregar columnas: `publicado`, `destacado`, `fecha_inicio`, `fecha_fin`, `categoria_id`, `zona_id`
- Sincronizar `publicado` desde `estado = 'publicado'`
- Copiar `fecha_publicacion` a `fecha_inicio`
- Copiar `fecha_expiracion` a `fecha_fin`

## Notas Tecnicas

- El modulo usa el patron Singleton para el frontend controller
- Las tablas se crean automaticamente al activar el modulo
- Los assets se cargan condicionalmente (solo en paginas con shortcodes)
- El widget de dashboard implementa cache de 2 minutos
- Las notificaciones push requieren configuracion de VAPID keys
