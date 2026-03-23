# Modulo: Podcast

> Plataforma de podcasting comunitario - crea, publica y escucha episodios de audio

## Descripcion

Sistema completo de podcasting que permite crear series de podcast, subir episodios de audio, gestionar suscripciones y reproducir contenido. Incluye feed RSS compatible con Apple Podcasts y Spotify, sistema de transcripciones, estadisticas de reproduccion y un reproductor de audio personalizable.

## Archivos Principales

```
includes/modules/podcast/
├── class-podcast-module.php                 # Clase principal del modulo
├── class-podcast-dashboard-tab.php          # Tabs del dashboard de usuario
├── class-podcast-dashboard-widget.php       # Widget para dashboard
├── frontend/
│   └── class-podcast-frontend-controller.php # Controlador frontend
├── views/
│   ├── dashboard.php                        # Vista admin dashboard
│   ├── series.php                           # Gestion de series
│   ├── episodios.php                        # Gestion de episodios
│   ├── estadisticas.php                     # Vista de estadisticas
│   ├── suscriptores.php                     # Gestion de suscriptores
│   └── config.php                           # Configuracion del modulo
├── assets/
│   ├── css/
│   └── js/
└── templates/
```

## Tablas de Base de Datos

### wp_flavor_podcast_series
Series de podcast.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| titulo | varchar(255) | Titulo de la serie |
| slug | varchar(255) UNIQUE | URL amigable |
| descripcion | text | Descripcion completa |
| descripcion_corta | varchar(500) | Extracto |
| autor_id | bigint(20) | FK usuario creador |
| imagen_url | varchar(500) | Portada de la serie |
| imagen_banner_url | varchar(500) | Banner |
| categoria | varchar(100) | Categoria principal |
| subcategoria | varchar(100) | Subcategoria |
| idioma | varchar(10) | Codigo idioma (es, en) |
| pais | varchar(50) | Pais de origen |
| sitio_web | varchar(500) | URL externa |
| email_contacto | varchar(255) | Email de contacto |
| estado | enum | borrador/publicado/pausado/archivado |
| tipo | enum | episodico/serial |
| explicito | tinyint(1) | Contenido explicito |
| copyright | varchar(255) | Texto copyright |
| suscriptores | int(11) | Total suscriptores |
| total_episodios | int(11) | Contador episodios |
| total_reproducciones | bigint(20) | Reproducciones totales |
| duracion_total_segundos | bigint(20) | Duracion acumulada |
| valoracion_promedio | decimal(3,2) | Rating promedio |
| total_valoraciones | int(11) | Numero de valoraciones |
| fecha_ultimo_episodio | datetime | Fecha ultimo episodio |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |
| meta_datos | json | Datos adicionales |

**Indices:** slug, autor_id, estado, categoria, fecha_ultimo_episodio, FULLTEXT(titulo, descripcion)

### wp_flavor_podcast_episodios
Episodios de podcast.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| serie_id | bigint(20) | FK serie |
| temporada | int(11) | Numero de temporada |
| numero_episodio | int(11) | Numero del episodio |
| guid | varchar(255) UNIQUE | Identificador unico RSS |
| titulo | varchar(255) | Titulo del episodio |
| slug | varchar(255) | URL amigable |
| descripcion | text | Descripcion completa |
| descripcion_corta | varchar(500) | Extracto |
| notas_episodio | text | Notas adicionales |
| archivo_url | varchar(500) | URL del archivo de audio |
| archivo_url_alternativo | varchar(500) | URL alternativa |
| tipo_archivo | varchar(50) | MIME type (audio/mpeg) |
| duracion_segundos | int(11) | Duracion en segundos |
| tamano_bytes | bigint(20) | Tamano del archivo |
| bitrate | int(11) | Bitrate del audio |
| imagen_url | varchar(500) | Imagen del episodio |
| estado | enum | borrador/publicado/programado/archivado |
| explicito | tinyint(1) | Contenido explicito |
| tipo_episodio | enum | completo/trailer/bonus |
| reproducciones | int(11) | Contador reproducciones |
| reproducciones_unicas | int(11) | Reproducciones unicas |
| descargas | int(11) | Contador descargas |
| me_gusta | int(11) | Contador likes |
| comentarios_count | int(11) | Contador comentarios |
| tiempo_escucha_total | bigint(20) | Tiempo total escuchado |
| porcentaje_completado_promedio | decimal(5,2) | Promedio completado |
| fecha_publicacion | datetime | Fecha publicacion |
| fecha_programacion | datetime | Fecha programada |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |
| meta_datos | json | Datos adicionales |

**Indices:** guid, serie_id, estado, fecha_publicacion, temporada_episodio, FULLTEXT(titulo, descripcion)

### wp_flavor_podcast_suscripciones
Suscripciones de usuarios a series.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| serie_id | bigint(20) | FK serie |
| usuario_id | bigint(20) | FK usuario |
| notificaciones_email | tinyint(1) | Notificar por email |
| notificaciones_push | tinyint(1) | Notificar push |
| ultimo_episodio_visto | bigint(20) | FK ultimo episodio visto |
| episodios_pendientes | int(11) | Episodios sin ver |
| fecha_suscripcion | datetime | Fecha suscripcion |
| fecha_ultima_actividad | datetime | Ultima actividad |

**Indices:** serie_usuario (UNIQUE), usuario_id, fecha_suscripcion

### wp_flavor_podcast_reproducciones
Historial y estadisticas de reproducciones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| episodio_id | bigint(20) | FK episodio |
| usuario_id | bigint(20) | FK usuario (puede ser null) |
| sesion_id | varchar(100) | ID de sesion |
| ip_address | varchar(45) | Direccion IP |
| user_agent | varchar(500) | User agent |
| dispositivo | varchar(50) | Tipo dispositivo |
| plataforma | varchar(50) | Sistema operativo |
| navegador | varchar(50) | Navegador |
| posicion_actual | int(11) | Posicion actual en segundos |
| duracion_escuchada | int(11) | Segundos escuchados |
| porcentaje_completado | decimal(5,2) | Porcentaje escuchado |
| completado | tinyint(1) | Episodio completado |
| velocidad_reproduccion | decimal(3,2) | Velocidad (1x, 1.5x, etc) |
| fuente | varchar(50) | Origen (web, app, rss) |
| pais | varchar(50) | Pais del oyente |
| ciudad | varchar(100) | Ciudad |
| fecha_inicio | datetime | Inicio reproduccion |
| fecha_ultima_actualizacion | datetime | Ultima actualizacion |
| fecha_fin | datetime | Fin reproduccion |

**Indices:** episodio_id, usuario_id, sesion_id, fecha_inicio, episodio_usuario

### wp_flavor_podcast_transcripciones
Transcripciones de episodios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| episodio_id | bigint(20) | FK episodio |
| idioma | varchar(10) | Codigo idioma |
| tipo | enum | auto/manual/ia |
| estado | enum | pendiente/procesando/completado/error |
| contenido_texto | longtext | Texto plano |
| contenido_srt | longtext | Formato SRT |
| contenido_vtt | longtext | Formato WebVTT |
| contenido_json | json | Con timestamps |
| palabras_count | int(11) | Contador palabras |
| duracion_procesamiento | int(11) | Tiempo de proceso |
| confianza_promedio | decimal(5,4) | Confianza del proceso |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** episodio_idioma (UNIQUE), estado

### wp_flavor_podcast_favoritos
Episodios marcados como favoritos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| episodio_id | bigint(20) | FK episodio |
| fecha_agregado | datetime | Fecha |

**Indices:** usuario_episodio (UNIQUE), episodio_id, fecha_agregado

### wp_flavor_podcast_descargas
Registro de descargas offline.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| episodio_id | bigint(20) | FK episodio |
| dispositivo | varchar(100) | Dispositivo |
| fecha_descarga | datetime | Fecha descarga |
| estado | enum | descargado/eliminado |

**Indices:** usuario_episodio (UNIQUE), episodio_id, fecha_descarga

## Shortcodes

### Reproductor y Player

```php
[podcast_player]
// Reproductor completo de podcast
// - episodio_id: ID del episodio a reproducir
// - serie_id: ID de la serie (muestra ultimo episodio)
// - autoplay: true|false
// - mostrar_lista: true|false - lista de episodios
// - mostrar_descripcion: true|false
// - mostrar_transcripcion: true|false
// - estilo: completo|compacto
// - color: color primario personalizado

[flavor_podcast_player]
// Player embebido alternativo
// - episodio_id: ID del episodio
// - serie_id: ID de la serie
// - estilo: compacto|completo
```

### Listados

```php
[podcast_lista_episodios]
// Lista de episodios de una serie
// - serie_id: ID de la serie (requerido)
// - limite: numero (default: 10)
// - orden: recientes|antiguos|populares
// - temporada: numero de temporada (0 = todas)
// - mostrar_player_mini: true|false
// - paginacion: true|false
// - columnas: 1|2|3

[podcast_series]
// Grid de series de podcast
// - limite: numero (default: 12)
// - categoria: slug de categoria
// - orden: recientes|populares
// - columnas: 2|3|4
// - mostrar_suscriptores: true|false
// - mostrar_episodios: true|false

[flavor_podcast_catalogo]
// Catalogo completo con filtros
// - categoria: slug
// - limite: numero
// - orden: recientes|populares
```

### Gestion de Usuario

```php
[podcast_suscribirse]
// Boton de suscripcion a serie
// - serie_id: ID de la serie
// - estilo: boton|minimal
// - mostrar_contador: true|false

[podcast_estadisticas]
// Panel de estadisticas (solo autor)
// - serie_id: ID de la serie
// - periodo: dias (default: 30)

[flavor_podcast_mis_suscripciones]
// Lista de suscripciones del usuario

[flavor_podcast_crear_serie]
// Formulario para crear nueva serie

[flavor_podcast_subir_episodio]
// Formulario para subir episodio
```

### Busqueda

```php
[podcast_buscar]
// Buscador de podcasts y episodios
// - placeholder: texto del input
// - mostrar_filtros: true|false
// - limite: resultados por tipo
```

### Detalle

```php
[flavor_podcast_serie]
// Pagina detalle de serie
// - id: ID de la serie (o auto desde URL)

[flavor_podcast_episodio]
// Pagina detalle de episodio
// - id: ID del episodio (o auto desde URL)
```

## Dashboard Tab

**Clase:** `Flavor_Podcast_Dashboard_Tab`

**Tabs disponibles:**
- `podcast` - Dashboard general del usuario
- `podcast-suscripciones` - Series suscritas
- `podcast-historial` - Historial de reproducciones
- `podcast-favoritos` - Episodios favoritos
- `podcast-descargas` - Descargas offline

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/podcast/` | index | Dashboard de podcast |
| `/mi-portal/podcast/suscripciones/` | suscripciones | Mis suscripciones |
| `/mi-portal/podcast/historial/` | historial | Historial |
| `/mi-portal/podcast/favoritos/` | favoritos | Favoritos |
| `/mi-portal/podcast/mis-series/` | mis-series | Series que creo |
| `/mi-portal/podcast/crear-serie/` | crear-serie | Nueva serie |
| `/mi-portal/podcast/subir-episodio/` | subir-episodio | Subir episodio |

## Categorias Disponibles

- `noticias` - Noticias Locales
- `entrevistas` - Entrevistas
- `historias` - Historias del Barrio
- `debates` - Debates Comunitarios
- `cultura` - Cultura y Arte
- `educacion` - Educacion
- `tecnologia` - Tecnologia
- `deportes` - Deportes
- `musica` - Musica
- `comedia` - Comedia

## REST API

### Endpoints Publicos

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/flavor-podcast/v1/series` | Lista de series |
| GET | `/flavor-podcast/v1/series/{id}` | Detalle de serie |
| GET | `/flavor-podcast/v1/series/{id}/episodios` | Episodios de serie |
| GET | `/flavor-podcast/v1/episodios/{id}` | Detalle de episodio |
| GET | `/flavor-podcast/v1/buscar` | Buscar contenido |

### Endpoints Autenticados

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | `/flavor-podcast/v1/series` | Crear serie |
| POST | `/flavor-podcast/v1/episodios` | Crear episodio |
| GET | `/flavor-podcast/v1/estadisticas/serie/{id}` | Estadisticas (autor) |

### Parametros de Lista

- `limite` - Resultados por pagina
- `pagina` - Numero de pagina
- `categoria` - Filtrar por categoria
- `orden` - recientes|populares|alfabetico

## Hooks y Filtros

### Actions

```php
// Nueva suscripcion
do_action('flavor_podcast_suscripcion', $serie_id, $usuario_id);

// Suscripcion cancelada
do_action('flavor_podcast_desuscripcion', $serie_id, $usuario_id);

// Nuevo episodio publicado
do_action('flavor_podcast_episodio_publicado', $episodio_id, $serie_id);

// Reproduccion iniciada
do_action('flavor_podcast_reproduccion_iniciada', $episodio_id, $usuario_id);

// Reproduccion completada
do_action('flavor_podcast_reproduccion_completada', $episodio_id, $usuario_id);

// Episodio marcado como favorito
do_action('flavor_podcast_favorito_agregado', $episodio_id, $usuario_id);

// Descarga iniciada
do_action('flavor_podcast_descarga_iniciada', $episodio_id, $usuario_id);
```

### Filters

```php
// Filtrar series en listados
apply_filters('flavor_podcast_series_query', $args);

// Filtrar episodios en listados
apply_filters('flavor_podcast_episodios_query', $args, $serie_id);

// Modificar datos del feed RSS
apply_filters('flavor_podcast_rss_serie', $datos_serie, $serie_id);
apply_filters('flavor_podcast_rss_episodio', $datos_episodio, $episodio);

// Validar subida de episodio
apply_filters('flavor_podcast_validar_episodio', $valido, $datos, $serie_id);

// Categorias disponibles
apply_filters('flavor_podcast_categorias', $categorias);

// Limite de series por usuario
apply_filters('flavor_podcast_limite_series', $limite, $usuario_id);
```

## Configuracion

```php
'podcast' => [
    'enabled' => true,
    'permite_subir_episodios' => true,
    'requiere_moderacion' => false,
    'duracion_maxima_minutos' => 120,
    'tamano_maximo_mb' => 100,
    'formatos_permitidos' => ['mp3', 'mp4', 'ogg', 'm4a', 'wav'],
    'permite_comentarios' => true,
    'genera_rss' => true,
    'transcripcion_automatica' => false,
    'autoplay_siguiente' => true,
    'mostrar_estadisticas' => true,
    'limite_series_por_usuario' => 5,
    'episodios_por_pagina' => 10,
    'calidad_audio_defecto' => 'alta',
    'rss_items_limite' => 50,
    'imagen_serie_defecto' => '',
    'color_player_primario' => '#6366f1',
    'color_player_secundario' => '#818cf8',
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `podcast_ver` | Ver podcasts publicos |
| `podcast_escuchar` | Reproducir episodios |
| `podcast_suscribirse` | Suscribirse a series |
| `podcast_crear_serie` | Crear series de podcast |
| `podcast_subir_episodio` | Subir episodios |
| `podcast_editar_propio` | Editar contenido propio |
| `podcast_gestionar` | Administrar todo el contenido |
| `podcast_ver_estadisticas` | Ver estadisticas |

## Feed RSS

El modulo genera feeds RSS compatibles con las principales plataformas de podcast:

### URL del Feed

```
https://tudominio.com/podcast/feed/{serie_id}/
```

### Compatibilidad

- Apple Podcasts (iTunes)
- Spotify
- Google Podcasts
- Pocket Casts
- Overcast
- Cualquier lector RSS

### Namespaces Soportados

- `itunes` - Apple Podcasts
- `googleplay` - Google Podcasts
- `atom` - Atom syndication
- `content` - RSS content module
- `podcast` - Podcast Index

## Integraciones

### Reproductor de Audio

El modulo incluye un reproductor HTML5 personalizado con:
- Control de velocidad (0.5x - 2x)
- Control de volumen
- Barra de progreso interactiva
- Botones de adelantar/retroceder (15s/30s)
- Modo lista de reproduccion
- Descarga de episodios
- Compartir episodio

### Estadisticas

El sistema registra automaticamente:
- Reproducciones totales y unicas
- Tiempo de escucha
- Porcentaje de completado
- Dispositivo y ubicacion
- Velocidad de reproduccion preferida
- Historial de escucha por usuario

### Notificaciones

Notificaciones automaticas para:
- Nuevo episodio en serie suscrita
- Recordatorio de episodios pendientes
- Confirmacion de suscripcion

## Ejemplos de Uso

### Mostrar player de un episodio

```php
[podcast_player episodio_id="123" autoplay="false" mostrar_transcripcion="true"]
```

### Grid de series populares

```php
[podcast_series limite="6" orden="populares" columnas="3"]
```

### Catalogo con filtros

```php
[flavor_podcast_catalogo categoria="cultura" limite="12"]
```

### Boton de suscripcion

```php
[podcast_suscribirse serie_id="5" mostrar_contador="true"]
```

### Buscador completo

```php
[podcast_buscar placeholder="Buscar podcasts..." mostrar_filtros="true"]
```

### Lista de episodios de una serie

```php
[podcast_lista_episodios serie_id="5" limite="20" orden="recientes" paginacion="true"]
```
