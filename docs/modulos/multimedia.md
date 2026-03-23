# Modulo: Multimedia

> Galeria de fotos, videos y contenidos audiovisuales comunitarios

## Descripcion

Sistema completo de gestion multimedia que permite a los usuarios subir, organizar y compartir fotos, videos y audios con la comunidad. Incluye albumes, sistema de likes, comentarios, tags, geolocalizacion, moderacion de contenido, estadisticas de vistas y descargas. Se integra con otros modulos para proporcionar galerias contextuales (eventos, comunidades, grupos, etc.).

## Archivos Principales

```
includes/modules/multimedia/
├── class-multimedia-module.php              # Clase principal del modulo
├── class-multimedia-dashboard-tab.php       # Tabs del dashboard de usuario
├── class-multimedia-dashboard-widget.php    # Widget del dashboard
├── frontend/
│   └── class-multimedia-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                        # Dashboard admin
│   ├── admin-galeria.php                    # Galeria de administracion
│   ├── admin-configuracion.php              # Configuracion del modulo
│   ├── albumes.php                          # Gestion de albumes
│   ├── categorias.php                       # Gestion de categorias
│   ├── galeria.php                          # Vista galeria publica
│   └── moderacion.php                       # Cola de moderacion
└── assets/
    ├── css/
    │   └── multimedia-frontend.css
    └── js/
        └── multimedia-frontend.js
```

## Tablas de Base de Datos

### wp_flavor_multimedia
Archivos multimedia subidos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario que subio |
| album_id | bigint(20) | FK album (null si sin album) |
| titulo | varchar(255) | Titulo del archivo |
| descripcion | text | Descripcion |
| tipo | enum | imagen/video/audio |
| archivo_url | varchar(500) | URL del archivo |
| archivo_path | varchar(500) | Ruta fisica del archivo |
| thumbnail_url | varchar(500) | URL del thumbnail |
| mime_type | varchar(100) | Tipo MIME del archivo |
| tamano_bytes | bigint(20) | Tamano en bytes |
| ancho | int | Ancho en pixeles (imagenes) |
| alto | int | Alto en pixeles (imagenes) |
| duracion_segundos | int | Duracion (videos/audios) |
| ubicacion_lat | decimal(10,7) | Latitud geolocalizacion |
| ubicacion_lng | decimal(10,7) | Longitud geolocalizacion |
| ubicacion_nombre | varchar(255) | Nombre de ubicacion |
| vistas | int | Contador de vistas |
| me_gusta | int | Contador de likes |
| comentarios_count | int | Contador de comentarios |
| descargas | int | Contador de descargas |
| estado | enum | pendiente/publico/privado/comunidad/rechazado |
| destacado | tinyint(1) | Contenido destacado |
| permite_descargas | tinyint(1) | Permite descargas |
| permite_comentarios | tinyint(1) | Permite comentarios |
| entidad_tipo | varchar(50) | Tipo de entidad vinculada |
| entidad_id | bigint(20) | ID de entidad vinculada |
| metadata | JSON | Datos adicionales |
| fecha_creacion | datetime | Fecha de subida |
| fecha_modificacion | datetime | Ultima modificacion |

**Indices:** usuario_id, album_id, tipo, estado, destacado, fecha_creacion, entidad_tipo+entidad_id

### wp_flavor_multimedia_albumes
Albumes para organizar contenido.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario creador |
| nombre | varchar(255) | Nombre del album |
| slug | varchar(255) | URL amigable |
| descripcion | text | Descripcion del album |
| portada_id | bigint(20) | FK archivo de portada |
| privacidad | enum | publico/privado/comunidad |
| archivos_count | int | Contador de archivos |
| orden | int | Orden de visualizacion |
| fecha_creacion | datetime | Fecha creacion |
| fecha_modificacion | datetime | Ultima modificacion |

**Indices:** usuario_id, slug, privacidad

### wp_flavor_multimedia_likes
Registro de me gusta.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| archivo_id | bigint(20) | FK archivo |
| usuario_id | bigint(20) | FK usuario |
| fecha | datetime | Fecha del like |

**Indices:** archivo_usuario (UNIQUE), usuario_id

### wp_flavor_multimedia_comentarios
Comentarios en archivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| archivo_id | bigint(20) | FK archivo |
| usuario_id | bigint(20) | FK usuario |
| comentario | text | Contenido del comentario |
| parent_id | bigint(20) | FK comentario padre (respuestas) |
| estado | enum | visible/oculto/eliminado |
| fecha | datetime | Fecha del comentario |

**Indices:** archivo_id, usuario_id, parent_id

### wp_flavor_multimedia_tags
Etiquetas de archivos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| archivo_id | bigint(20) | FK archivo |
| tag | varchar(100) | Etiqueta |

**Indices:** archivo_id, tag

### wp_flavor_multimedia_reportes
Reportes de contenido inapropiado.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| archivo_id | bigint(20) | FK archivo |
| usuario_id | bigint(20) | FK usuario que reporta |
| motivo | varchar(100) | Motivo del reporte |
| descripcion | text | Descripcion adicional |
| estado | enum | pendiente/revisado/resuelto |
| fecha | datetime | Fecha del reporte |

**Indices:** archivo_id, estado

## Shortcodes

### Galeria Publica

```php
[flavor_galeria]
// Galeria general
// - categoria: filtrar por categoria
// - tipo: imagen|video|audio
// - limite: numero de items (default: 24)
// - columnas: 2-6 (default: 4)

[flavor_carousel]
// Carrusel de imagenes destacadas
// - limite: numero de items
```

### Albumes

```php
[flavor_albumes]
// Listado de albumes
// - autor: current|ID de usuario
// - limite: numero de albumes (default: 12)

[flavor_multimedia_album id="123"]
// Contenido de un album especifico
```

### Gestion Personal

```php
[flavor_mi_galeria]
// Mis archivos subidos
// Permite editar y eliminar

[flavor_subir_multimedia]
// Formulario de subida
// - tipo: filtrar tipo permitido
// - entidad: vincular a entidad
// - entidad_id: ID de entidad

[flavor_multimedia_dashboard]
// Dashboard completo de multimedia
```

### Integracion con Otros Modulos

```php
[flavor_multimedia_galeria entidad="evento" entidad_id="123"]
// Galeria vinculada a una entidad especifica
// Usado en tabs de otros modulos

[flavor_multimedia_visor]
// Lightbox/visor de imagenes
```

## Dashboard Tab

**Clase:** `Flavor_Multimedia_Dashboard_Tab`

**Tabs disponibles:**
- `multimedia-mis-fotos` - Galeria de fotos del usuario
- `multimedia-mis-albumes` - Albumes del usuario
- `multimedia-favoritos` - Contenido con likes del usuario
- `multimedia-estadisticas` - Metricas de vistas y likes

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/multimedia/` | index | Dashboard multimedia |
| `/mi-portal/multimedia/galeria/` | galeria | Galeria publica |
| `/mi-portal/multimedia/subir/` | subir | Formulario de subida |
| `/mi-portal/multimedia/mi-galeria/` | mi-galeria | Mis archivos |
| `/mi-portal/multimedia/mi-galeria/?tab=albumes` | albumes | Mis albumes |
| `/mi-portal/multimedia/favoritos/` | favoritos | Contenido con likes |

## REST API Endpoints

### Publicos

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/multimedia/galeria` | GET | Listado de archivos (params: tipo, album_id, tag, busqueda, limite, pagina, orden) |
| `/flavor/v1/multimedia/archivo/{id}` | GET | Detalle de archivo |
| `/flavor/v1/multimedia/archivo/{id}/comentarios` | GET | Comentarios de un archivo |
| `/flavor/v1/multimedia/albumes` | GET | Listado de albumes |
| `/flavor/v1/multimedia/album/{id}` | GET | Detalle de album |
| `/flavor/v1/multimedia/archivo/{id}/descargar` | GET | Descargar archivo |
| `/flavor/v1/multimedia/tags` | GET | Tags populares |

### Autenticados

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/flavor/v1/multimedia/subir` | POST | Subir archivo |
| `/flavor/v1/multimedia/archivo/{id}` | PUT | Editar archivo |
| `/flavor/v1/multimedia/archivo/{id}` | DELETE | Eliminar archivo |
| `/flavor/v1/multimedia/archivo/{id}/like` | POST | Toggle me gusta |
| `/flavor/v1/multimedia/archivo/{id}/comentar` | POST | Agregar comentario |
| `/flavor/v1/multimedia/archivo/{id}/reportar` | POST | Reportar contenido |
| `/flavor/v1/multimedia/album` | POST | Crear album |
| `/flavor/v1/multimedia/mis-archivos` | GET | Mis archivos |
| `/flavor/v1/multimedia/mis-albumes` | GET | Mis albumes |

## Hooks y Filtros

### Actions

```php
// Archivo subido
do_action('flavor_multimedia_archivo_subido', $archivo_id, $usuario_id, $tipo);

// Archivo eliminado
do_action('flavor_multimedia_archivo_eliminado', $archivo_id, $usuario_id);

// Nuevo like
do_action('flavor_multimedia_like', $archivo_id, $usuario_id);

// Nuevo comentario
do_action('flavor_multimedia_comentario', $comentario_id, $archivo_id, $usuario_id);

// Archivo moderado
do_action('flavor_multimedia_moderado', $archivo_id, $estado, $moderador_id);

// Gamificacion: puntos por subir
do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $puntos, 'subir_multimedia');

// Gamificacion: puntos por recibir like
do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $puntos, 'recibir_like_mm');
```

### Filters

```php
// Formatos de imagen permitidos
apply_filters('flavor_multimedia_formatos_imagen', $formatos);

// Formatos de video permitidos
apply_filters('flavor_multimedia_formatos_video', $formatos);

// Formatos de audio permitidos
apply_filters('flavor_multimedia_formatos_audio', $formatos);

// Tamano maximo por tipo
apply_filters('flavor_multimedia_max_size', $bytes, $tipo);

// Validar archivo antes de subir
apply_filters('flavor_multimedia_validar_subida', $valido, $file, $usuario_id);

// Configuracion de thumbnails
apply_filters('flavor_multimedia_thumbnail_config', ['width' => 400, 'height' => 300]);

// Estados disponibles
apply_filters('flavor_multimedia_estados', $estados);
```

## Configuracion

```php
'multimedia' => [
    'enabled' => true,
    'disponible_app' => 'ambas', // web, mobile, ambas

    // Subida
    'permite_subir' => true,
    'requiere_moderacion' => false,

    // Formatos permitidos
    'formatos_imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'formatos_video' => ['mp4', 'mov', 'avi', 'webm'],
    'formatos_audio' => ['mp3', 'wav', 'ogg', 'm4a'],

    // Limites de tamano (MB)
    'max_tamano_imagen_mb' => 10,
    'max_tamano_video_mb' => 100,
    'max_tamano_audio_mb' => 50,

    // Thumbnails
    'genera_thumbnails' => true,
    'thumbnail_width' => 400,
    'thumbnail_height' => 300,
    'calidad_jpeg' => 85,

    // Funcionalidades
    'permite_albumes' => true,
    'permite_geolocalizacion' => true,
    'permite_comentarios' => true,
    'permite_descargas' => true,
    'max_archivos_album' => 500,

    // Marca de agua
    'marca_agua' => false,

    // Gamificacion
    'puntos_subir_foto' => 5,
    'puntos_subir_video' => 10,
    'puntos_recibir_like' => 2,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `multimedia_ver` | Ver galeria publica |
| `multimedia_subir` | Subir archivos |
| `multimedia_editar_propio` | Editar archivos propios |
| `multimedia_eliminar_propio` | Eliminar archivos propios |
| `multimedia_comentar` | Comentar en archivos |
| `multimedia_crear_album` | Crear albumes |
| `multimedia_moderar` | Moderar contenido pendiente |
| `multimedia_gestionar` | Administrar todo el modulo |

## Integracion con Otros Modulos

El modulo multimedia proporciona tabs de galeria automaticamente a otros modulos cuando esta activo:

| Modulo | Tab ID | Descripcion |
|--------|--------|-------------|
| grupos_consumo | galeria-grupo | Fotos del grupo |
| eventos | galeria-evento | Fotos del evento |
| comunidades | galeria-comunidad | Multimedia de la comunidad |
| cursos | recursos-curso | Recursos del curso |
| talleres | recursos-taller | Recursos del taller |
| huertos_urbanos | fotos-huerto | Fotos del huerto |
| colectivos | galeria-colectivo | Galeria del colectivo |
| banco_tiempo | galeria-servicio | Fotos del servicio |
| incidencias | galeria-incidencia | Evidencias de incidencia |
| participacion | multimedia-propuesta | Multimedia de propuesta |
| presupuestos_participativos | galeria-pp-proyecto | Multimedia de proyecto |
| transparencia | galeria-documento | Documentos multimedia |
| radio | galeria-radio-programa | Recursos del programa |

## Ejemplos de Uso

### Mostrar galeria filtrada

```php
// Galeria de imagenes de eventos
echo do_shortcode('[flavor_galeria categoria="eventos" tipo="imagen" limite="12"]');

// Galeria de videos destacados
echo do_shortcode('[flavor_galeria tipo="video" destacados="true"]');

// Galeria de una comunidad especifica
echo do_shortcode('[flavor_multimedia_galeria entidad="comunidad" entidad_id="5"]');
```

### Obtener archivos via PHP

```php
$modulo_multimedia = Flavor_Chat_Modules::get_instance()->get_module('multimedia');
if ($modulo_multimedia) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_multimedia';

    // Ultimas 10 fotos publicas
    $fotos = $wpdb->get_results(
        "SELECT * FROM {$tabla}
         WHERE tipo = 'imagen' AND estado IN ('publico', 'comunidad')
         ORDER BY fecha_creacion DESC
         LIMIT 10"
    );
}
```

### Subir archivo programaticamente

```php
global $wpdb;
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

$wpdb->insert($tabla_multimedia, [
    'usuario_id' => get_current_user_id(),
    'titulo' => 'Mi foto',
    'descripcion' => 'Descripcion de la foto',
    'tipo' => 'imagen',
    'archivo_url' => $url_archivo,
    'thumbnail_url' => $url_thumbnail,
    'mime_type' => 'image/jpeg',
    'tamano_bytes' => $tamano,
    'estado' => 'comunidad',
    'fecha_creacion' => current_time('mysql'),
]);

$archivo_id = $wpdb->insert_id;
```

### Escuchar evento de nuevo like

```php
add_action('flavor_multimedia_like', function($archivo_id, $usuario_id) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_multimedia';

    $archivo = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla WHERE id = %d",
        $archivo_id
    ));

    if ($archivo && (int) $archivo->usuario_id !== $usuario_id) {
        // Notificar al autor que recibio un like
        do_action('flavor_notificacion_enviar', $archivo->usuario_id, 'mm_nuevo_like', [
            'archivo_id' => $archivo_id,
            'archivo_titulo' => $archivo->titulo,
            'like_por' => get_user_by('id', $usuario_id)->display_name,
        ]);
    }
}, 10, 2);
```

### Integracion con Widget de Dashboard

```php
// El modulo registra automaticamente un widget en el dashboard del usuario
// que muestra:
// - Total de fotos, videos y albumes
// - Likes recibidos
// - Ultimos archivos subidos

// Para personalizar el widget:
add_filter('flavor_multimedia_dashboard_widget_config', function($config) {
    $config['mostrar_estadisticas'] = true;
    $config['mostrar_recientes'] = true;
    $config['limite_recientes'] = 8;
    return $config;
});
```

## Notas Tecnicas

### Generacion de Thumbnails

El modulo genera automaticamente thumbnails para imagenes utilizando las funciones de WordPress. Los thumbnails se almacenan en el directorio de uploads con sufijo `-thumb`.

```php
// Configuracion por defecto
$thumbnail_width = 400;
$thumbnail_height = 300;
$calidad_jpeg = 85;
```

### Limpieza Automatica

Se ejecuta un cron diario para limpiar archivos huerfanos y actualizar estadisticas:

```php
// El cron se registra automaticamente
wp_schedule_event(time(), 'daily', 'flavor_multimedia_cleanup');
```

### Formatos Soportados

**Imagenes:** JPG, JPEG, PNG, GIF, WEBP

**Videos:** MP4, MOV, AVI, WEBM

**Audio:** MP3, WAV, OGG, M4A

### Geolocalizacion

El modulo soporta almacenar coordenadas GPS extraidas de los metadatos EXIF de las imagenes o proporcionadas manualmente por el usuario.

### Sistema de Moderacion

Cuando `requiere_moderacion` esta activo:
1. Los archivos nuevos se guardan con estado `pendiente`
2. Los administradores reciben notificacion
3. Desde el panel de moderacion pueden aprobar o rechazar
4. El usuario recibe notificacion del resultado
