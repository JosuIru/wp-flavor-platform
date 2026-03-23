# Modulo: Radio Comunitaria

> Emisora de radio comunitaria en streaming con programacion y participacion ciudadana

## Descripcion

Sistema completo de radio comunitaria que permite gestionar una emisora de radio en streaming con programacion, locutores, dedicatorias, chat en vivo, podcasts y participacion de la comunidad. Incluye reproductor multimedia, sistema de programacion semanal, gestion de locutores y herramientas de interaccion con los oyentes.

## Archivos Principales

```
includes/modules/radio/
├── class-radio-module.php                    # Clase principal del modulo
├── class-radio-dashboard-tab.php             # Tabs del dashboard de usuario
├── class-radio-dashboard-widget.php          # Widget del dashboard
├── class-radio-media-manager.php             # Gestor de medios/audio
├── frontend/
│   └── class-radio-frontend-controller.php   # Controlador frontend
├── views/
│   ├── dashboard.php                         # Dashboard admin
│   ├── programas.php                         # Gestion de programas
│   ├── programacion.php                      # Parrilla de programacion
│   ├── emisiones.php                         # Control de emisiones
│   ├── locutores.php                         # Gestion de locutores
│   ├── locutor-panel.php                     # Panel del locutor
│   ├── media-manager.php                     # Gestor de archivos de audio
│   └── config.php                            # Configuracion del modulo
└── assets/
    ├── css/
    │   └── radio-frontend.css
    └── js/
        └── radio-frontend.js
```

## Tablas de Base de Datos

### wp_flavor_radio_programas
Programas de radio registrados.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre del programa |
| slug | varchar(255) | URL amigable |
| descripcion | text | Descripcion del programa |
| locutor_id | bigint(20) | FK usuario locutor principal |
| co_locutores | JSON | Array de IDs de co-locutores |
| imagen_url | varchar(500) | Imagen del programa |
| categoria | varchar(100) | Categoria (musica, noticias, etc.) |
| genero_musical | varchar(100) | Genero musical si aplica |
| frecuencia | enum | diario/semanal/quincenal/mensual/especial |
| dias_semana | JSON | Array de dias [1,3,5] = Lun, Mie, Vie |
| hora_inicio | time | Hora de inicio |
| duracion_minutos | int | Duracion en minutos |
| estado | enum | pendiente/activo/pausado/finalizado |
| oyentes_promedio | int | Media de oyentes |
| total_episodios | int | Contador de episodios |
| redes_sociales | JSON | URLs de redes del programa |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** locutor_id, estado, slug

### wp_flavor_radio_programacion
Emisiones programadas y en curso.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| programa_id | bigint(20) | FK programa |
| tipo | enum | programa/musica/noticia/anuncio/especial |
| titulo | varchar(255) | Titulo de la emision |
| descripcion | text | Descripcion |
| fecha_hora_inicio | datetime | Inicio programado |
| fecha_hora_fin | datetime | Fin programado |
| archivo_url | varchar(500) | URL archivo pregrabado |
| en_vivo | tinyint(1) | Es emision en vivo |
| oyentes_pico | int | Maximo de oyentes |
| oyentes_total | int | Total acumulado |
| chat_activo | tinyint(1) | Chat habilitado |
| estado | enum | programado/en_emision/finalizado/cancelado |
| notas_locutor | text | Notas internas |
| metadata | JSON | Datos adicionales |
| fecha_creacion | datetime | Fecha creacion |

**Indices:** programa_id, fecha_hora_inicio, estado

### wp_flavor_radio_dedicatorias
Dedicatorias de los oyentes.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| de_nombre | varchar(100) | Nombre del remitente |
| para_nombre | varchar(100) | Nombre del destinatario |
| mensaje | text | Mensaje de la dedicatoria |
| cancion_titulo | varchar(255) | Titulo de cancion solicitada |
| cancion_artista | varchar(255) | Artista solicitado |
| cancion_url | varchar(500) | URL de la cancion |
| estado | enum | pendiente/aprobada/rechazada/emitida |
| emision_id | bigint(20) | FK emision donde se emitio |
| motivo_rechazo | varchar(255) | Motivo si fue rechazada |
| fecha_solicitud | datetime | Fecha de envio |
| fecha_emision | datetime | Fecha cuando se emitio |

**Indices:** usuario_id, estado, emision_id

### wp_flavor_radio_chat
Mensajes del chat en vivo.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| emision_id | bigint(20) | FK emision |
| usuario_id | bigint(20) | FK usuario |
| mensaje | text | Contenido del mensaje |
| tipo | enum | mensaje/mencion/alerta |
| destacado | tinyint(1) | Mensaje destacado |
| eliminado | tinyint(1) | Mensaje eliminado |
| fecha | datetime | Fecha del mensaje |

**Indices:** emision_id, usuario_id, fecha

### wp_flavor_radio_oyentes
Registro de oyentes conectados.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| session_id | varchar(64) | ID de sesion unico |
| usuario_id | bigint(20) | FK usuario (null si anonimo) |
| ip_address | varchar(45) | Direccion IP |
| emision_id | bigint(20) | FK emision actual |
| dispositivo | varchar(50) | Tipo de dispositivo |
| inicio | datetime | Inicio de escucha |
| ultima_actividad | datetime | Ultimo heartbeat |
| duracion_segundos | int | Tiempo total escuchando |
| activo | tinyint(1) | Actualmente conectado |

**Indices:** session_id (UNIQUE), usuario_id, emision_id, activo

### wp_flavor_radio_propuestas
Propuestas de programas de la comunidad.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| nombre_programa | varchar(255) | Nombre propuesto |
| descripcion | text | Descripcion del programa |
| categoria | varchar(100) | Categoria propuesta |
| frecuencia_deseada | varchar(50) | Frecuencia deseada |
| horario_preferido | varchar(100) | Horario preferido |
| experiencia | text | Experiencia del proponente |
| demo_url | varchar(500) | URL demo/piloto |
| estado | enum | pendiente/aprobada/rechazada |
| notas_admin | text | Notas del administrador |
| programa_id | bigint(20) | FK programa creado si se aprobo |
| fecha_solicitud | datetime | Fecha de propuesta |
| fecha_respuesta | datetime | Fecha de respuesta |

**Indices:** usuario_id, estado

### wp_flavor_radio_podcasts
Grabaciones y podcasts.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| programa_id | bigint(20) | FK programa |
| emision_id | bigint(20) | FK emision original |
| titulo | varchar(255) | Titulo del episodio |
| descripcion | text | Descripcion |
| archivo_url | varchar(500) | URL del archivo de audio |
| duracion_segundos | int | Duracion en segundos |
| tamano_bytes | bigint | Tamano del archivo |
| imagen_url | varchar(500) | Imagen del episodio |
| reproducciones | int | Contador de reproducciones |
| descargas | int | Contador de descargas |
| publicado | tinyint(1) | Esta publicado |
| fecha_emision | datetime | Fecha de la emision original |
| fecha_publicacion | datetime | Fecha de publicacion |

**Indices:** programa_id, emision_id, publicado

## Shortcodes

### Reproductor de Radio

```php
[flavor_radio_player]
// Reproductor de streaming
// - estilo: completo|compacto|mini
// - autoplay: true|false

[radio_en_vivo]
// Alias del reproductor
```

### Programacion

```php
[flavor_radio_programacion]
// Parrilla de programacion semanal
// - dia: filtrar por dia especifico
// - limite: numero maximo de programas

[radio_programacion]
// Alias de programacion

[flavor_radio_programa_actual]
// Muestra el programa actual y el siguiente
// - mostrar_siguiente: true|false
```

### Dedicatorias

```php
[flavor_radio_dedicatorias]
// Formulario para enviar dedicatorias
// Requiere usuario logueado

[radio_dedicatorias]
// Alias de dedicatorias
```

### Chat en Vivo

```php
[flavor_radio_chat]
// Chat en tiempo real durante emisiones
// Requiere chat_en_vivo habilitado

[radio_chat]
// Alias de chat
```

### Propuestas y Participacion

```php
[flavor_radio_proponer]
// Formulario para proponer un programa
// Requiere usuario logueado

[radio_proponer]
// Alias de proponer
```

### Podcasts

```php
[flavor_radio_podcasts]
// Listado de grabaciones/podcasts
// - limite: numero de items (default: 12)

[radio_podcasts]
// Alias de podcasts
```

### Gestion Personal

```php
[radio_mis_programas]
// Programas donde el usuario es locutor

[flavor_radio_estadisticas]
// Estadisticas publicas de la radio

[flavor_radio_locutor id="123"]
// Perfil de un locutor especifico

[flavor_radio_calendario]
// Calendario de eventos y emisiones especiales

[flavor_radio_favoritos]
// Programas favoritos del usuario

[flavor_radio_canales]
// Selector de canales (si hay multiples)
```

## Dashboard Tab

**Clase:** `Flavor_Radio_Dashboard_Tab`

**Tabs disponibles:**
- `radio` - Dashboard principal de radio
- `radio-mis-programas` - Programas favoritos del usuario
- `radio-mis-dedicatorias` - Historial de dedicatorias enviadas
- `radio-mis-propuestas` - Propuestas de programas del usuario

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/radio/` | index | Player y programacion |
| `/mi-portal/radio/programacion/` | programacion | Parrilla semanal |
| `/mi-portal/radio/dedicatorias/` | dedicatorias | Enviar dedicatoria |
| `/mi-portal/radio/podcasts/` | podcasts | Grabaciones |
| `/mi-portal/radio/programas/` | programas | Catalogo de programas |
| `/mi-portal/radio/programa/{slug}/` | programa | Detalle de programa |
| `/mi-portal/radio/mis-programas/` | mis-programas | Como locutor |
| `/mi-portal/radio/proponer/` | proponer | Proponer programa |

## REST API Endpoints

### Publicos

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/radio/stream` | GET | Info del stream |
| `/flavor/v1/radio/ahora` | GET | Programa actual |
| `/flavor/v1/radio/programacion` | GET | Programacion (params: fecha, dias) |
| `/flavor/v1/radio/programas` | GET | Lista de programas |
| `/flavor/v1/radio/programa/{id}` | GET | Detalle de programa |
| `/flavor/v1/radio/podcasts` | GET | Lista de podcasts |
| `/flavor/v1/radio/podcast/{id}` | GET | Detalle de podcast |
| `/flavor/v1/radio/oyentes` | GET | Contador de oyentes |
| `/flavor/v1/radio/metadata` | GET | Metadatos del stream (cancion actual) |
| `/flavor/v1/radio/canales` | GET | Canales disponibles |
| `/flavor/v1/radio/calendario` | GET | Calendario de eventos |
| `/flavor/v1/radio/locutor/{id}` | GET | Perfil de locutor |
| `/flavor/v1/radio/chat/{emision_id}` | GET | Mensajes del chat |

### Autenticados

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/radio/dedicatoria` | POST | Enviar dedicatoria |
| `/flavor/v1/radio/mis-dedicatorias` | GET | Mis dedicatorias |
| `/flavor/v1/radio/chat/{emision_id}/mensaje` | POST | Enviar mensaje chat |
| `/flavor/v1/radio/proponer` | POST | Proponer programa |
| `/flavor/v1/radio/favoritos` | GET | Mis favoritos |
| `/flavor/v1/radio/favorito/{programa_id}` | POST | Toggle favorito |
| `/flavor/v1/radio/oyente` | POST | Reportar actividad (heartbeat) |

### Administracion

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/radio/analytics` | GET | Estadisticas completas |

## Hooks y Filtros

### Actions

```php
// Nueva dedicatoria enviada
do_action('flavor_radio_dedicatoria_enviada', $dedicatoria_id, $usuario_id);

// Dedicatoria emitida
do_action('flavor_radio_dedicatoria_emitida', $dedicatoria_id, $emision_id);

// Emision iniciada
do_action('flavor_radio_emision_iniciada', $emision_id, $programa_id);

// Emision finalizada
do_action('flavor_radio_emision_finalizada', $emision_id, $stats);

// Nuevo mensaje de chat
do_action('flavor_radio_chat_mensaje', $mensaje_id, $emision_id, $usuario_id);

// Programa aprobado
do_action('flavor_radio_programa_aprobado', $programa_id, $propuesta_id);

// Oyente conectado
do_action('flavor_radio_oyente_conectado', $session_id, $usuario_id);

// Gamificacion: puntos por escuchar
do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $puntos, 'escuchar_programa');
```

### Filters

```php
// Limite de dedicatorias por dia
apply_filters('flavor_radio_max_dedicatorias_dia', $limite, $usuario_id);

// Duracion maxima/minima de programas
apply_filters('flavor_radio_duracion_programa', $duracion, $tipo);

// Validar dedicatoria antes de guardar
apply_filters('flavor_radio_validar_dedicatoria', $valido, $datos, $usuario_id);

// Categorias de programas disponibles
apply_filters('flavor_radio_categorias', $categorias);

// URL del stream por calidad
apply_filters('flavor_radio_stream_url', $url, $calidad);
```

## Configuracion

```php
'radio' => [
    'enabled' => true,
    'disponible_app' => 'ambas', // web, mobile, ambas
    'url_stream' => '',           // URL del streaming
    'url_stream_hd' => '',        // URL streaming HD
    'frecuencia_fm' => '',        // Ej: "107.9 FM"
    'nombre_radio' => 'Radio Comunitaria',
    'slogan' => 'La voz de tu barrio',
    'logo_url' => '',
    'color_marca' => '#8b5cf6',

    // Programas y locutores
    'permite_locutores_comunidad' => true,
    'duracion_maxima_programa' => 120, // minutos
    'duracion_minima_programa' => 30,  // minutos
    'requiere_aprobacion_programas' => true,

    // Dedicatorias
    'permite_dedicatorias' => true,
    'max_dedicatorias_dia' => 3,

    // Chat y participacion
    'chat_en_vivo' => true,
    'oyentes_contador_publico' => true,

    // Grabaciones
    'grabacion_automatica' => true,
    'url_grabaciones' => '',
    'permite_podcasts' => true,

    // Gamificacion
    'puntos_escuchar_programa' => 2,
    'puntos_enviar_dedicatoria' => 5,
    'puntos_proponer_programa' => 20,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `radio_escuchar` | Escuchar la radio (publico) |
| `radio_dedicatoria` | Enviar dedicatorias |
| `radio_chat` | Participar en el chat |
| `radio_proponer_programa` | Proponer nuevos programas |
| `radio_locutor` | Acceso al panel de locutor |
| `radio_gestionar_programa` | Gestionar programa propio |
| `radio_aprobar_dedicatorias` | Aprobar/rechazar dedicatorias |
| `radio_gestionar` | Administrar todo el modulo |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Radio de comunidad especifica |
| eventos | Integracion | Eventos especiales de radio |
| podcast | Relacion | Exportar emisiones a podcast |
| gamificacion | Puntos | Puntos por participar |
| notificaciones | Alertas | Avisos de programas favoritos |

## Ejemplos de Uso

### Insertar reproductor en pagina

```php
// Reproductor completo
echo do_shortcode('[flavor_radio_player estilo="completo"]');

// Reproductor compacto para sidebar
echo do_shortcode('[flavor_radio_player estilo="compacto"]');

// Mini reproductor flotante
echo do_shortcode('[flavor_radio_player estilo="mini"]');
```

### Obtener programa actual via PHP

```php
$radio_module = Flavor_Chat_Modules::get_instance()->get_module('radio');
if ($radio_module) {
    $programa_actual = $radio_module->get_programa_actual();
    if ($programa_actual) {
        echo 'Ahora suena: ' . esc_html($programa_actual->titulo);
    }
}
```

### Enviar dedicatoria programaticamente

```php
global $wpdb;
$tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';

$wpdb->insert($tabla_dedicatorias, [
    'usuario_id' => get_current_user_id(),
    'de_nombre' => 'Juan',
    'para_nombre' => 'Maria',
    'mensaje' => 'Feliz cumpleanos!',
    'cancion_titulo' => 'Las Mananitas',
    'cancion_artista' => 'Tradicional',
    'estado' => 'pendiente',
    'fecha_solicitud' => current_time('mysql'),
]);
```

### Escuchar evento de emision iniciada

```php
add_action('flavor_radio_emision_iniciada', function($emision_id, $programa_id) {
    // Notificar a usuarios con programa en favoritos
    global $wpdb;
    $tabla_favoritos = $wpdb->prefix . 'flavor_radio_favoritos';

    $usuarios_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT usuario_id FROM $tabla_favoritos WHERE programa_id = %d AND notificaciones = 1",
        $programa_id
    ));

    foreach ($usuarios_ids as $usuario_id) {
        // Enviar notificacion push
        do_action('flavor_notificacion_enviar', $usuario_id, 'radio_programa_en_vivo', [
            'programa_id' => $programa_id,
            'emision_id' => $emision_id,
        ]);
    }
}, 10, 2);
```

### Integracion con Widget de Dashboard

```php
// El modulo registra automaticamente un widget en el dashboard del usuario
// que muestra:
// - Reproductor mini
// - Programa actual
// - Ultimas dedicatorias del usuario
// - Programas favoritos

// Para personalizar el widget:
add_filter('flavor_radio_dashboard_widget_config', function($config) {
    $config['mostrar_chat'] = true;
    $config['mostrar_oyentes'] = true;
    return $config;
});
```

## Notas Tecnicas

### Streaming de Audio

El modulo soporta URLs de streaming en formato:
- Icecast/Shoutcast (audio/mpeg)
- HLS (m3u8)
- DASH (mpd)

### Heartbeat de Oyentes

Los oyentes activos se actualizan cada 60 segundos mediante un cron job que marca como inactivos a los oyentes sin actividad reciente.

```php
// El cron se registra automaticamente
wp_schedule_event(time(), 'every_minute', 'flavor_radio_actualizar_oyentes');
```

### Metadatos del Stream

El modulo puede obtener metadatos del stream (cancion actual, artista) si el servidor de streaming lo soporta. Los formatos compatibles incluyen:
- Icecast JSON stats
- Shoutcast v1/v2
- Azuracast API
