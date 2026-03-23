# Modulo: Red Social

> Red social comunitaria alternativa sin publicidad, centrada en la comunidad

## Descripcion

Plataforma social completa que ofrece una alternativa a las redes sociales comerciales. Sin publicidad, sin algoritmos ocultos de manipulacion, y con total control sobre la privacidad. Incluye publicaciones, comentarios, reacciones, sistema de seguidores, historias temporales, hashtags, menciones, notificaciones y un sistema completo de reputacion/karma con badges.

## Archivos Principales

```
includes/modules/red-social/
|-- class-red-social-module.php           # Clase principal del modulo
|-- class-red-social-dashboard-tab.php    # Tab del dashboard de usuario
|-- templates/
|   |-- amigos.php                        # Vista de conexiones
|   |-- explorar.php                      # Pagina de exploracion
|   |-- feed.php                          # Feed de publicaciones
|   |-- historias.php                     # Carrusel de historias
|   |-- mi-actividad.php                  # Actividad del usuario
|-- views/
|   |-- dashboard.php                     # Dashboard admin
|   |-- estadisticas.php                  # Estadisticas admin
|   |-- moderacion.php                    # Panel de moderacion
|   |-- publicaciones.php                 # Gestion de publicaciones
|   |-- usuarios.php                      # Gestion de usuarios
|-- assets/
    |-- css/
    |-- js/
```

## Tablas de Base de Datos

### wp_flavor_social_publicaciones
Almacena las publicaciones de los usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| autor_id | bigint(20) | FK usuario autor |
| contenido | text | Contenido de la publicacion |
| tipo | enum | texto/imagen/video/enlace/evento/compartido |
| adjuntos | longtext JSON | URLs de archivos adjuntos |
| visibilidad | enum | publica/comunidad/seguidores/privada |
| ubicacion | varchar(255) | Ubicacion opcional |
| estado | enum | borrador/publicado/moderacion/oculto/eliminado |
| publicacion_original_id | bigint(20) | Para compartidos |
| es_fijado | tinyint(1) | Publicacion fijada |
| me_gusta | int | Contador de likes |
| comentarios | int | Contador de comentarios |
| compartidos | int | Contador de compartidos |
| vistas | int | Contador de visualizaciones |
| fecha_publicacion | datetime | Fecha de publicacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** autor_id, estado, fecha_publicacion, visibilidad, FULLTEXT(contenido)

### wp_flavor_social_comentarios
Comentarios en publicaciones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| publicacion_id | bigint(20) | FK publicacion |
| autor_id | bigint(20) | FK usuario |
| comentario_padre_id | bigint(20) | Para respuestas anidadas |
| contenido | text | Texto del comentario |
| me_gusta | int | Contador likes |
| estado | enum | publicado/moderacion/oculto/eliminado |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

### wp_flavor_social_reacciones
Reacciones a publicaciones y comentarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| publicacion_id | bigint(20) | FK publicacion (nullable) |
| comentario_id | bigint(20) | FK comentario (nullable) |
| usuario_id | bigint(20) | FK usuario |
| tipo | enum | me_gusta/me_encanta/me_divierte/me_entristece/me_enfada |
| fecha_creacion | datetime | Fecha reaccion |

**Indices:** UNIQUE(publicacion_id, usuario_id), UNIQUE(comentario_id, usuario_id)

### wp_flavor_social_seguimientos
Relaciones de seguimiento entre usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| seguidor_id | bigint(20) | FK usuario que sigue |
| seguido_id | bigint(20) | FK usuario seguido |
| notificaciones_activas | tinyint(1) | Recibir notificaciones |
| fecha_seguimiento | datetime | Fecha del seguimiento |

**Indices:** UNIQUE(seguidor_id, seguido_id)

### wp_flavor_social_hashtags
Hashtags utilizados.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| hashtag | varchar(100) | Texto del hashtag |
| total_usos | int | Contador de uso |
| fecha_creacion | datetime | Primera vez usado |
| fecha_ultimo_uso | datetime | Ultimo uso |

### wp_flavor_social_hashtags_posts
Relacion hashtags-publicaciones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| hashtag_id | bigint(20) | FK hashtag |
| publicacion_id | bigint(20) | FK publicacion |
| fecha_creacion | datetime | Fecha asociacion |

### wp_flavor_social_historias
Historias temporales (24h).

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| autor_id | bigint(20) | FK usuario |
| tipo | enum | imagen/video/texto |
| contenido_url | varchar(500) | URL del contenido |
| texto | text | Texto de la historia |
| color_fondo | varchar(20) | Color de fondo |
| vistas | int | Contador visualizaciones |
| fecha_creacion | datetime | Fecha creacion |
| fecha_expiracion | datetime | Fecha de expiracion |

### wp_flavor_social_notificaciones
Notificaciones del sistema.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario destinatario |
| actor_id | bigint(20) | FK usuario que genera |
| tipo | enum | like/comentario/seguidor/mencion/compartido/historia |
| referencia_id | bigint(20) | ID del elemento |
| referencia_tipo | varchar(50) | Tipo de referencia |
| mensaje | text | Mensaje personalizado |
| leida | tinyint(1) | Notificacion leida |
| fecha_creacion | datetime | Fecha creacion |

### wp_flavor_social_guardados
Publicaciones guardadas/favoritas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| publicacion_id | bigint(20) | FK publicacion |
| fecha_guardado | datetime | Fecha guardado |

### wp_flavor_social_perfiles
Perfiles extendidos de usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario WP |
| nombre_completo | varchar(255) | Nombre completo |
| bio | text | Biografia |
| ubicacion | varchar(255) | Ubicacion |
| sitio_web | varchar(255) | URL sitio web |
| fecha_nacimiento | date | Fecha nacimiento |
| cover_url | varchar(500) | Imagen de portada |
| es_verificado | tinyint(1) | Usuario verificado |
| es_privado | tinyint(1) | Perfil privado |
| total_publicaciones | int | Contador publicaciones |
| total_seguidores | int | Contador seguidores |
| total_siguiendo | int | Contador siguiendo |
| fecha_creacion | datetime | Fecha registro |
| fecha_actualizacion | datetime | Ultima actualizacion |

### wp_flavor_social_engagement
Datos para algoritmo de feed inteligente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| autor_id | bigint(20) | FK autor del contenido |
| tipo_interaccion | enum | like/comentario/compartido/guardado/clic/tiempo_lectura |
| contenido_id | bigint(20) | FK contenido |
| peso | decimal(5,2) | Peso de la interaccion |
| fecha_interaccion | datetime | Fecha interaccion |

### wp_flavor_social_reputacion
Sistema de puntos/karma.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| puntos_totales | int | Puntos acumulados |
| nivel | varchar(50) | Nivel actual |
| puntos_semana | int | Puntos esta semana |
| puntos_mes | int | Puntos este mes |
| racha_dias | int | Dias consecutivos activo |
| ultima_actividad | datetime | Ultima actividad |
| fecha_actualizacion | datetime | Ultima actualizacion |

### wp_flavor_social_badges
Definicion de insignias.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(100) | Nombre badge |
| slug | varchar(100) | Identificador |
| descripcion | text | Descripcion |
| icono | varchar(255) | URL o emoji del icono |
| color | varchar(20) | Color del badge |
| categoria | enum | participacion/creacion/comunidad/especial/temporal |
| puntos_requeridos | int | Puntos minimos |
| condicion_especial | text JSON | Condiciones adicionales |
| es_unico | tinyint(1) | Solo se puede obtener una vez |
| activo | tinyint(1) | Badge activo |
| orden | int | Orden de visualizacion |
| fecha_creacion | datetime | Fecha creacion |

### wp_flavor_social_usuario_badges
Badges obtenidos por usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| badge_id | bigint(20) | FK badge |
| fecha_obtenido | datetime | Fecha obtencion |
| destacado | tinyint(1) | Mostrar en perfil |

### wp_flavor_social_historial_puntos
Historial de puntos de reputacion.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| puntos | int | Puntos otorgados |
| tipo_accion | varchar(50) | Tipo de accion |
| descripcion | varchar(255) | Descripcion |
| referencia_id | bigint(20) | ID referencia |
| referencia_tipo | varchar(50) | Tipo referencia |
| fecha_creacion | datetime | Fecha registro |

## Shortcodes

### Feed y Timeline

```php
[rs_feed]
// Feed principal de publicaciones
// - tipo: timeline|comunidad|trending
// - limite: numero (default: 10)
// - mostrar_crear: true|false (formulario de crear publicacion)

[rs_historias]
// Carrusel de historias de usuarios seguidos
// - limite: numero (default: 10)
```

### Perfil y Usuarios

```php
[rs_perfil]
// Muestra el perfil de un usuario
// - usuario_id: ID (default: usuario actual)
// Acepta parametro GET: ?usuario_id=X o ?rs_perfil=X

[rs_explorar]
// Pagina de exploracion con publicaciones con media
// - limite: numero (default: 30)
// Acepta filtro por hashtag: ?hashtag=X o ?rs_hashtag=X
```

### Crear Contenido

```php
[rs_crear_publicacion]
// Formulario para crear nueva publicacion
// Requiere usuario autenticado
```

### Notificaciones

```php
[rs_notificaciones]
// Lista de notificaciones del usuario
// Requiere usuario autenticado
```

### Sistema de Reputacion

```php
[rs_reputacion]
// Muestra la reputacion del usuario
// - usuario_id: ID (default: usuario actual)

[rs_ranking]
// Ranking de usuarios por puntos
// - limite: numero (default: 10)

[rs_badges]
// Muestra badges/insignias disponibles y obtenidas

[rs_mi_actividad]
// Historial de actividad del usuario
```

### Integracion con otros modulos

```php
[flavor_social_feed]
// Feed integrado para tabs de otros modulos
// - entidad: tipo de entidad (grupo_consumo, evento, comunidad, etc.)
// - entidad_id: ID de la entidad
```

## Dashboard Tab

**Clase:** `Flavor_Red_Social_Dashboard_Tab`

**Subtabs disponibles:**
- `feed` - Feed principal de publicaciones
- `perfil` - Mi perfil social
- `amigos` - Conexiones y descubrir usuarios
- `actividad` - Mi actividad reciente
- `reputacion` - Puntos, nivel y badges

## Paginas Dinamicas

| Ruta | Descripcion |
|------|-------------|
| `/red-social/` | Feed principal |
| `/red-social/mi-perfil/` | Perfil del usuario actual |
| `/red-social/amigos/` | Conexiones y sugerencias |
| `/red-social/mensajes/` | Mensajes privados |

## Sistema de Reputacion

### Niveles

| Nivel | Puntos Minimos | Etiqueta | Icono |
|-------|----------------|----------|-------|
| nuevo | 0 | Nuevo | ![nuevo](🌱) |
| activo | 50 | Activo | ![activo](⭐) |
| contribuidor | 200 | Contribuidor | ![contribuidor](🌟) |
| experto | 500 | Experto | ![experto](💫) |
| lider | 1000 | Lider | ![lider](🏆) |
| embajador | 2500 | Embajador | ![embajador](👑) |
| leyenda | 5000 | Leyenda | ![leyenda](🔥) |

### Puntos por Accion

| Accion | Puntos |
|--------|--------|
| publicacion | 10 |
| comentario | 5 |
| like_recibido | 2 |
| like_dado | 1 |
| compartido | 8 |
| seguidor_ganado | 3 |
| seguir_usuario | 1 |
| historia | 5 |
| mencion_recibida | 2 |
| login_diario | 2 |
| primera_publicacion | 25 |
| verificacion_perfil | 50 |
| invitar_usuario | 15 |
| badge_obtenido | 10 |

## Tipos de Reaccion

- `me_gusta` - Me gusta clasico
- `me_encanta` - Me encanta
- `me_divierte` - Me divierte
- `me_entristece` - Me entristece
- `me_enfada` - Me enfada

## Tipos de Notificacion

- `like` - Alguien dio like a tu publicacion
- `comentario` - Alguien comento en tu publicacion
- `seguidor` - Alguien empezo a seguirte
- `mencion` - Te mencionaron en una publicacion
- `compartido` - Compartieron tu publicacion
- `historia` - Alguien publico una historia

## Algoritmo de Feed

El modulo soporta dos tipos de feed:

### Cronologico (default)
Publicaciones ordenadas por fecha, mas recientes primero.

### Inteligente
Algoritmo basado en:
- **Afinidad con el autor:** Interacciones previas (likes, comentarios, tiempo de lectura)
- **Engagement de la publicacion:** (likes + comentarios*2 + compartidos*3) / LOG(edad)
- **Recencia:** Decadencia temporal logaritmica
- **Diversidad:** Maximo 3 publicaciones consecutivas del mismo autor
- **Bonus:** +50 si es de alguien que sigues, +10 si tiene adjuntos

## REST API

### Endpoints

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/wp-json/flavor-chat/v1/red-social/feed` | Obtener feed |
| POST | `/wp-json/flavor-chat/v1/red-social/publicacion` | Crear publicacion |
| GET | `/wp-json/flavor-chat/v1/red-social/publicacion/{id}` | Obtener publicacion |
| GET | `/wp-json/flavor-chat/v1/red-social/perfil/{id}` | Obtener perfil |
| GET | `/wp-json/flavor-chat/v1/red-social/trending` | Obtener hashtags trending |

### Parametros GET /feed

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| tipo | string | timeline, comunidad, trending |
| desde | int | ID desde donde paginar |
| limite | int | Numero de resultados |

## Configuracion

```php
'red_social' => [
    'disponible_app' => 'cliente',              // admin|cliente|ambos
    'publicaciones_publicas' => true,           // Permitir publicaciones publicas
    'requiere_moderacion' => false,             // Revisar antes de publicar
    'max_caracteres_publicacion' => 5000,       // Limite de caracteres
    'permite_imagenes' => true,                 // Permitir subir imagenes
    'permite_videos' => true,                   // Permitir subir videos
    'max_imagenes_por_post' => 10,              // Max imagenes por publicacion
    'permite_hashtags' => true,                 // Activar hashtags
    'permite_menciones' => true,                // Activar menciones @usuario
    'permite_compartir' => true,                // Permitir compartir posts
    'permite_historias' => true,                // Activar historias
    'duracion_historia_horas' => 24,            // Duracion de historias
    'timeline_algoritmo' => 'cronologico',      // cronologico|inteligente
    'notificaciones_email' => true,             // Enviar emails
    'max_seguidores_sugeridos' => 10,           // Sugerencias a mostrar
]
```

## Integraciones con Otros Modulos

El modulo Red Social puede inyectar tabs de publicaciones en otros modulos:

| Modulo | Tab Inyectado |
|--------|---------------|
| grupos_consumo | Publicaciones del grupo |
| eventos | Publicaciones del evento |
| comunidades | Feed de la comunidad |
| colectivos | Publicaciones del colectivo |
| talleres | Publicaciones del taller |
| huertos_urbanos | Actividad del huerto |
| banco_tiempo | Publicaciones de servicios |
| circulos_cuidados | Actividad del circulo |
| incidencias | Actividad de incidencia |
| participacion | Actividad de propuesta |
| transparencia | Actividad de documento |
| trabajo_digno | Publicaciones de ofertas |

## AJAX Actions

| Action | Descripcion | Parametros |
|--------|-------------|------------|
| `rs_crear_publicacion` | Crear nueva publicacion | contenido, visibilidad, adjuntos |
| `rs_toggle_like` | Dar/quitar like | publicacion_id, tipo |
| `rs_crear_comentario` | Crear comentario | publicacion_id, contenido, padre_id |
| `rs_obtener_comentarios` | Obtener comentarios | publicacion_id, limite, offset |
| `rs_like_comentario` | Like en comentario | comentario_id |
| `rs_toggle_seguir` | Seguir/dejar de seguir | usuario_id |
| `rs_cargar_feed` | Cargar mas publicaciones | desde, tipo |
| `rs_buscar_usuarios` | Buscar usuarios | query, limite |
| `rs_obtener_historias` | Obtener historias usuario | usuario_id |
| `rs_crear_historia` | Crear historia | tipo, texto, color, archivo |
| `rs_guardar_post` | Guardar publicacion | publicacion_id |
| `rs_obtener_notificaciones` | Obtener notificaciones | limite |
| `rs_marcar_notificacion_leida` | Marcar leida | notificacion_id, todas |
| `rs_obtener_perfil` | Obtener perfil completo | usuario_id |
| `rs_actualizar_perfil` | Actualizar perfil | bio, ubicacion, sitio_web |
| `rs_eliminar_publicacion` | Eliminar publicacion | publicacion_id |
| `rs_reportar_contenido` | Reportar contenido | tipo, contenido_id, motivo |

## Hooks y Filtros

### Actions

```php
// Usuario subio de nivel
do_action('flavor_social_nivel_subido', $usuario_id, $nuevo_nivel, $info_nivel);
```

## Componentes Web (Visual Builder)

| Componente | Descripcion |
|------------|-------------|
| `hero_social` | Hero de presentacion de la red social |
| `timeline_feed` | Feed de publicaciones configurable |
| `stats_comunidad` | Estadisticas de la comunidad |
| `sugerencias_usuarios` | Widget de sugerencias de conexion |
| `historias_carousel` | Carrusel de historias |

## Cron Jobs

| Evento | Frecuencia | Descripcion |
|--------|------------|-------------|
| `rs_limpiar_historias_expiradas` | Cada hora | Elimina historias expiradas |

## Permisos y Visibilidad

### Visibilidad de Publicaciones

| Tipo | Descripcion |
|------|-------------|
| publica | Visible para todos, incluso no registrados |
| comunidad | Solo usuarios registrados |
| seguidores | Solo seguidores del autor |
| privada | Solo el autor |

### Perfiles Privados

Cuando un perfil es privado:
- Solo los seguidores aprobados pueden ver sus publicaciones
- Las solicitudes de seguimiento requieren aprobacion

## Moderacion

El modulo incluye panel de moderacion para:
- Revisar publicaciones pendientes de aprobacion
- Gestionar reportes de contenido
- Ocultar/eliminar publicaciones
- Gestionar usuarios

## Diferencias con Redes Comerciales

- Sin publicidad ni contenido patrocinado
- Sin algoritmos ocultos de manipulacion
- Sin venta de datos personales
- Sin rastreo publicitario
- Control total sobre la privacidad
- Datos alojados localmente
- Propiedad y control comunitario
- Transparencia total en el funcionamiento
