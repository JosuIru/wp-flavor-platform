# Módulo: Comunidades

> Gestión de comunidades virtuales temáticas o geográficas

## Descripción

Sistema central para crear y gestionar comunidades virtuales. Las comunidades pueden ser temáticas, geográficas o de interés común. Funcionan como contenedor para otros módulos permitiendo que eventos, foros, recursos, etc. se vinculen a comunidades específicas.

## Archivos Principales

```
includes/modules/comunidades/
├── class-comunidades-module.php         # Clase principal
├── class-comunidades-dashboard-tab.php  # Tab dashboard
├── frontend/
│   └── class-comunidades-frontend-controller.php
├── views/
│   ├── dashboard.php
│   ├── mis-comunidades.php
│   ├── explorar.php
│   ├── detalle-comunidad.php
│   └── crear.php
└── assets/
    ├── css/comunidades-frontend.css
    └── js/comunidades-frontend.js
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Comunidad | `fc_comunidad` | Comunidades virtuales |

### Campos del CPT fc_comunidad

| Meta Key | Tipo | Descripción |
|----------|------|-------------|
| `_fc_comunidad_tipo` | string | vecinal/tematica/proyecto/organizacion |
| `_fc_comunidad_categoria` | string | Categoría principal |
| `_fc_comunidad_descripcion_corta` | string | Extracto |
| `_fc_comunidad_imagen_portada` | int | ID imagen portada |
| `_fc_comunidad_imagen_banner` | int | ID imagen banner |
| `_fc_comunidad_ubicacion` | string | Ubicación si aplica |
| `_fc_comunidad_latitud` | float | Coordenada |
| `_fc_comunidad_longitud` | float | Coordenada |
| `_fc_comunidad_privacidad` | string | publica/privada/oculta |
| `_fc_comunidad_requiere_aprobacion` | bool | Requiere aprobar miembros |
| `_fc_comunidad_fundador_id` | int | ID usuario fundador |
| `_fc_comunidad_admins` | array | IDs administradores |
| `_fc_comunidad_moderadores` | array | IDs moderadores |
| `_fc_comunidad_total_miembros` | int | Contador miembros |
| `_fc_comunidad_modulos_activos` | array | Módulos habilitados |
| `_fc_comunidad_reglas` | text | Normas comunidad |
| `_fc_comunidad_enlaces` | array | Enlaces externos |
| `_fc_comunidad_color_tema` | string | Color principal |

## Taxonomías

| Taxonomía | Slug | Aplicada a |
|-----------|------|-----------|
| Tipo Comunidad | `tipo_comunidad` | fc_comunidad |
| Categoría Comunidad | `categoria_comunidad` | fc_comunidad |
| Etiqueta Comunidad | `etiqueta_comunidad` | fc_comunidad |

## Tablas de Base de Datos

### wp_flavor_comunidades_miembros
Membresías de usuarios en comunidades.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| comunidad_id | bigint(20) | FK comunidad (post_id) |
| usuario_id | bigint(20) | FK usuario WP |
| rol | enum | fundador/admin/moderador/miembro |
| estado | enum | activo/pendiente/suspendido/baneado |
| fecha_solicitud | datetime | Fecha solicitud |
| fecha_aprobacion | datetime | Fecha aprobación |
| aprobado_por | bigint(20) | Admin que aprobó |
| mensaje_solicitud | text | Mensaje al solicitar |
| notificaciones | tinyint(1) | Recibe notificaciones |
| visible_en_directorio | tinyint(1) | Aparece en lista |
| cargo | varchar(100) | Cargo/título en comunidad |
| puntos_participacion | int | Puntos acumulados |
| ultima_actividad | datetime | Última actividad |
| notas_admin | text | Notas internas |

**Índices:** comunidad_id, usuario_id, rol, estado
**Unique:** comunidad_id + usuario_id

### wp_flavor_comunidades_invitaciones
Invitaciones a unirse.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| comunidad_id | bigint(20) | FK comunidad |
| invitador_id | bigint(20) | Quien invita |
| email_invitado | varchar(100) | Email invitado |
| usuario_invitado_id | bigint(20) | FK si ya existe |
| codigo | varchar(50) | Código único |
| mensaje | text | Mensaje personalizado |
| estado | enum | pendiente/aceptada/rechazada/expirada |
| fecha_envio | datetime | Fecha envío |
| fecha_respuesta | datetime | Fecha respuesta |
| fecha_expiracion | datetime | Fecha límite |

### wp_flavor_comunidades_actividad
Feed de actividad de la comunidad.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| comunidad_id | bigint(20) | FK comunidad |
| usuario_id | bigint(20) | FK usuario |
| tipo | enum | publicacion/evento/recurso/miembro/anuncio |
| accion | varchar(50) | Acción específica |
| objeto_tipo | varchar(50) | Tipo objeto relacionado |
| objeto_id | bigint(20) | ID objeto |
| titulo | varchar(255) | Título actividad |
| contenido | text | Contenido/descripción |
| imagen | varchar(500) | URL imagen |
| visibilidad | enum | todos/miembros/admins |
| likes_count | int | Contador likes |
| comentarios_count | int | Contador comentarios |
| es_destacada | tinyint(1) | Fijada arriba |
| created_at | datetime | Fecha creación |

### wp_flavor_comunidades_anuncios
Tablón de anuncios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| comunidad_id | bigint(20) | FK comunidad |
| autor_id | bigint(20) | FK usuario |
| titulo | varchar(255) | Título anuncio |
| contenido | text | Contenido |
| tipo | enum | informativo/urgente/evento/recurso |
| imagen | varchar(500) | URL imagen |
| adjuntos | longtext JSON | Archivos adjuntos |
| fecha_publicacion | datetime | Fecha publicación |
| fecha_expiracion | datetime | Fecha fin visibilidad |
| es_fijado | tinyint(1) | Fijado arriba |
| estado | enum | borrador/publicado/archivado |
| visualizaciones | int | Contador vistas |
| notificar_miembros | tinyint(1) | Envió notificación |

### wp_flavor_comunidades_recursos
Recursos compartidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| comunidad_id | bigint(20) | FK comunidad |
| subidor_id | bigint(20) | FK usuario |
| titulo | varchar(255) | Nombre recurso |
| descripcion | text | Descripción |
| tipo | enum | documento/enlace/imagen/video/otro |
| url | varchar(500) | URL o ruta archivo |
| mime_type | varchar(100) | Tipo MIME |
| tamano | bigint(20) | Tamaño bytes |
| carpeta | varchar(255) | Carpeta/categoría |
| etiquetas | varchar(255) | Tags |
| descargas | int | Contador descargas |
| es_publico | tinyint(1) | Visible fuera comunidad |
| created_at | datetime | Fecha subida |

## Shortcodes

### Listados y Exploración

```php
[comunidades_listar]
// Directorio de comunidades
// - tipo: vecinal|tematica|proyecto|todos
// - categoria: slug categoría
// - orden: recientes|populares|alfabetico
// - limite: número (default: 12)
// - columnas: 2|3|4 (default: 3)
// - mostrar_privadas: true|false (default: false)

[comunidades_busqueda]
// Buscador de comunidades
// - placeholder: texto
// - con_filtros: true|false
// - mostrar_mapa: true|false

[comunidades_mis_comunidades]
// Comunidades del usuario actual
// - limite: número
// - compacto: true|false
// - mostrar_rol: true|false
```

### Detalle y Contenido

```php
[comunidades_detalle]
// Página completa de comunidad
// - id: ID comunidad (o auto desde URL)
// - tabs: actividad,anuncios,miembros,recursos,eventos

[comunidades_feed_unificado]
// Feed de actividad
// - comunidad_id: ID (o todas las del usuario)
// - limite: número
// - tipos: publicacion,evento,recurso,anuncio

[comunidades_tablon]
// Tablón de anuncios
// - comunidad_id: ID
// - limite: número
// - incluir_red: true|false (incluir red de comunidades)

[comunidades_calendario]
// Calendario de eventos de la comunidad
// - comunidad_id: ID
// - vista: mes|semana|lista
```

### Acciones

```php
[comunidades_crear]
// Formulario crear comunidad
// - tipos_permitidos: vecinal,tematica,proyecto

[comunidades_recursos_compartidos]
// Gestor de recursos
// - comunidad_id: ID
// - modo: galeria|lista
// - subir: true|false

[comunidades_metricas]
// Estadísticas de comunidad
// - comunidad_id: ID

[comunidades_actividad]
// Timeline de actividad

[comunidades_notificaciones]
// Centro de notificaciones del usuario
```

## Dashboard Tab

**Clase:** `Flavor_Comunidades_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Panel principal
- `mis-comunidades` - Mis comunidades
- `explorar` - Directorio
- `crear` - Crear nueva
- `invitaciones` - Invitaciones pendientes
- `notificaciones` - Centro notificaciones

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/comunidades/` | index | Dashboard |
| `/mi-portal/comunidades/explorar/` | explorar | Directorio |
| `/mi-portal/comunidades/mis-comunidades/` | mis-comunidades | Mis comunidades |
| `/mi-portal/comunidades/crear/` | crear | Nueva comunidad |
| `/mi-portal/comunidades/{id}/` | ver | Detalle comunidad |
| `/mi-portal/comunidades/{id}/miembros/` | miembros | Lista miembros |
| `/mi-portal/comunidades/{id}/anuncios/` | anuncios | Tablón |
| `/mi-portal/comunidades/{id}/recursos/` | recursos | Archivos |
| `/mi-portal/comunidades/{id}/eventos/` | eventos | Calendario |
| `/mi-portal/comunidades/{id}/foro/` | foro | Discusiones |
| `/mi-portal/comunidades/{id}/configuracion/` | configuracion | Ajustes |
| `/mi-portal/comunidades/{id}/estadisticas/` | estadisticas | Métricas |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| eventos | Contenedor | Eventos de la comunidad |
| foros | Contenedor | Foros por comunidad |
| grupos-consumo | Contenedor | GC vinculados |
| colectivos | Relación | Colectivos miembros |
| socios | Membresía | Comunidades de socios |
| marketplace | Contenedor | Mercadillo comunidad |
| biblioteca | Contenedor | Biblioteca compartida |
| ayuda-vecinal | Contenedor | Ayudas del barrio |
| encuestas | Contenedor | Encuestas comunidad |

## Hooks y Filtros

### Actions

```php
// Usuario se une a comunidad
do_action('flavor_comunidad_miembro_unido', $comunidad_id, $usuario_id, $rol);

// Usuario abandona comunidad
do_action('flavor_comunidad_miembro_salido', $comunidad_id, $usuario_id);

// Nueva actividad en comunidad
do_action('flavor_comunidad_nueva_actividad', $comunidad_id, $actividad);

// Comunidad creada
do_action('flavor_comunidad_creada', $comunidad_id, $fundador_id);

// Anuncio publicado
do_action('flavor_comunidad_anuncio_publicado', $anuncio_id, $comunidad_id);
```

### Filters

```php
// Filtrar comunidades visibles
apply_filters('flavor_comunidades_visibles', $comunidades, $usuario_id);

// Modificar roles disponibles
apply_filters('flavor_comunidad_roles', $roles);

// Personalizar tabs de comunidad
apply_filters('flavor_comunidad_tabs', $tabs, $comunidad_id, $usuario_id);

// Módulos disponibles para comunidad
apply_filters('flavor_comunidad_modulos_disponibles', $modulos);
```

## API REST

### Endpoints

```
GET    /wp-json/flavor/v1/comunidades
GET    /wp-json/flavor/v1/comunidades/{id}
POST   /wp-json/flavor/v1/comunidades
PUT    /wp-json/flavor/v1/comunidades/{id}
DELETE /wp-json/flavor/v1/comunidades/{id}

GET    /wp-json/flavor/v1/comunidades/{id}/miembros
POST   /wp-json/flavor/v1/comunidades/{id}/unirse
DELETE /wp-json/flavor/v1/comunidades/{id}/abandonar

GET    /wp-json/flavor/v1/comunidades/{id}/actividad
POST   /wp-json/flavor/v1/comunidades/{id}/publicar

GET    /wp-json/flavor/v1/comunidades/{id}/anuncios
POST   /wp-json/flavor/v1/comunidades/{id}/anuncios
```

## Configuración

```php
'comunidades' => [
    'enabled' => true,
    'tipos_permitidos' => ['vecinal', 'tematica', 'proyecto', 'organizacion'],
    'max_comunidades_por_usuario' => 10,
    'max_miembros_comunidad' => 0, // 0 = sin límite
    'requiere_aprobacion_crear' => false,
    'modulos_habilitados' => ['foros', 'eventos', 'recursos', 'anuncios'],
    'notificaciones' => [
        'nuevo_miembro' => true,
        'nuevo_anuncio' => true,
        'actividad_diaria' => false,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `comunidad_crear` | Crear comunidades |
| `comunidad_gestionar` | Administrar propia |
| `comunidad_moderar` | Moderar contenido |
| `comunidad_eliminar` | Eliminar comunidad |
| `comunidades_admin` | Admin todas |
