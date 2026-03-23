# Modulo: Economia del Don

> Sistema de donaciones y regalos sin expectativa de retorno

## Descripcion

Plataforma para dar y recibir sin esperar nada a cambio. Facilita ofrecer objetos, servicios, tiempo, conocimiento y espacios a quien los necesite, fomentando una economia basada en la generosidad y la abundancia compartida. El unico "retorno" esperado es la gratitud, que se publica en un muro comunitario.

**Valoracion de Conciencia:** 94/100

| Principio | Peso | Descripcion |
|-----------|------|-------------|
| abundancia_organizable | 0.30 | Lo que sobra para quien lo necesita |
| conciencia_fundamental | 0.25 | Dar por el placer de dar |
| interdependencia_radical | 0.20 | Red de apoyo incondicional |
| valor_intrinseco | 0.15 | El valor esta en el acto de dar |
| madurez_ciclica | 0.10 | Flujo natural de dar/recibir |

## Archivos Principales

```
includes/modules/economia-don/
├── class-economia-don-module.php           # Clase principal del modulo
├── class-economia-don-dashboard-tab.php    # Tab para dashboard de usuario
├── class-economia-don-widget.php           # Widget de dashboard
├── install.php                             # Instalacion de tablas BD
├── frontend/
│   └── class-economia-don-frontend-controller.php  # Controlador frontend
├── templates/
│   ├── listado-dones.php                   # Listado de dones disponibles
│   ├── mis-dones.php                       # Mis dones ofrecidos
│   ├── muro-gratitud.php                   # Muro de gratitudes
│   └── ofrecer-don.php                     # Formulario para ofrecer
├── views/
│   └── dashboard.php                       # Vista dashboard admin
└── assets/
    ├── css/
    │   └── economia-don.css
    │   └── economia-don-frontend.css
    └── js/
        └── economia-don.js
        └── economia-don-frontend.js
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripcion |
|-----|------|-------------|
| Don | `ed_don` | Dones ofrecidos por usuarios |
| Solicitud | `ed_solicitud` | Solicitudes de dones |
| Gratitud | `ed_gratitud` | Mensajes de agradecimiento |

## Taxonomias

| Taxonomia | Slug | Asociada a |
|-----------|------|------------|
| Categoria de Don | `ed_categoria` | `ed_don` |

## Tablas de Base de Datos

### wp_flavor_economia_dones
Dones ofrecidos por usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| usuario_id | bigint(20) UNSIGNED | FK usuario donante |
| titulo | varchar(255) | Nombre del don |
| descripcion | text | Descripcion detallada |
| categoria | varchar(100) | objetos/alimentos/servicios/tiempo/conocimiento/espacios |
| condiciones | text | Condiciones de entrega (opcional) |
| ubicacion | varchar(255) | Zona de recogida |
| imagen | varchar(500) | URL imagen |
| estado | enum | disponible/reservado/entregado/recibido |
| fecha_creacion | datetime | Fecha publicacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** usuario_id, estado, categoria, fecha_creacion

### wp_flavor_economia_solicitudes
Solicitudes de dones por parte de usuarios interesados.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| don_id | bigint(20) UNSIGNED | FK don solicitado |
| usuario_id | bigint(20) UNSIGNED | FK usuario solicitante |
| mensaje | text | Mensaje opcional |
| estado | enum | pendiente/aceptada/rechazada |
| fecha | datetime | Fecha solicitud |

**Indices:** don_id, usuario_id, estado

### wp_flavor_economia_entregas
Entregas confirmadas de dones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| don_id | bigint(20) UNSIGNED | FK don entregado |
| donante_id | bigint(20) UNSIGNED | FK usuario donante |
| receptor_id | bigint(20) UNSIGNED | FK usuario receptor |
| fecha_entrega | datetime | Fecha de entrega |
| notas | text | Notas adicionales |
| gratitud_enviada | tinyint(1) | Si receptor envio gratitud |

**Indices:** don_id, donante_id, receptor_id

### wp_flavor_economia_gratitudes
Mensajes de agradecimiento para el muro de gratitud.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| don_id | bigint(20) UNSIGNED | FK don agradecido |
| usuario_id | bigint(20) UNSIGNED | FK usuario que agradece |
| mensaje | text | Mensaje de gratitud |
| publico | tinyint(1) | Visible en muro publico |
| fecha | datetime | Fecha de publicacion |

**Indices:** don_id, usuario_id, publico, fecha

## Categorias de Dones

| ID | Nombre | Icono | Color |
|----|--------|-------|-------|
| objetos | Objetos y cosas | dashicons-archive | #3498db |
| alimentos | Alimentos | dashicons-carrot | #27ae60 |
| servicios | Servicios y habilidades | dashicons-admin-tools | #9b59b6 |
| tiempo | Tiempo y compania | dashicons-clock | #e74c3c |
| conocimiento | Conocimiento | dashicons-book | #f39c12 |
| espacios | Espacios | dashicons-admin-home | #1abc9c |

## Estados del Don

| Estado | Nombre | Color |
|--------|--------|-------|
| disponible | Disponible | #27ae60 |
| reservado | Reservado | #f39c12 |
| entregado | Entregado | #3498db |
| recibido | Recibido con gratitud | #9b59b6 |

## Shortcodes

### Listado y Catalogo

```php
[economia_don]
// Listado de dones disponibles
// Alias: [flavor_don_listado]
// Atributos:
// - categoria: filtrar por categoria
// - limite: numero de resultados (default: 12)

[flavor_don_detalle]
// Detalle de un don especifico
// - don_id: ID del don
```

### Gestion Personal

```php
[mis_dones]
// Mis dones ofrecidos
// Alias: [flavor_don_mis_dones]

[flavor_don_mis_recepciones]
// Dones que he recibido

[ofrecer_don]
// Formulario para publicar un don
// Alias: [flavor_don_ofrecer]
```

### Comunidad

```php
[muro_gratitud]
// Muro de mensajes de gratitud
// Alias: [flavor_don_muro_gratitud]
// - limite: numero de gratitudes (default: 20)

[flavor_don_estadisticas]
// Estadisticas globales del sistema
```

## Dashboard Tab

**Clase:** `Flavor_Economia_Don_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Panel principal con estadisticas
- `mis-dones` - Dones que he ofrecido
- `recibidos` - Dones que he recibido
- `ofrecer` - Publicar nuevo don

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/economia-don/` | index | Dashboard principal |
| `/mi-portal/economia-don/dones/` | dones | Catalogo de dones |
| `/mi-portal/economia-don/ofrecer/` | ofrecer | Publicar don |
| `/mi-portal/economia-don/mis-dones/` | mis-dones | Mis dones |
| `/mi-portal/economia-don/recibidos/` | recibidos | Dones recibidos |
| `/mi-portal/economia-don/muro-gratitud/` | muro | Muro de gratitud |

## API REST

### Endpoints Publicos

```
GET /wp-json/flavor/v1/economia-don/dones
// Listar dones disponibles
// Params: categoria, limite

GET /wp-json/flavor/v1/economia-don/dones/{id}
// Obtener don por ID

GET /wp-json/flavor/v1/economia-don/gratitudes
// Muro de gratitud
// Params: limite
```

### Endpoints Autenticados

```
POST /wp-json/flavor/v1/economia-don/dones
// Publicar nuevo don
// Body: titulo, descripcion, categoria, ubicacion, disponibilidad, anonimo

POST /wp-json/flavor/v1/economia-don/dones/{id}/solicitar
// Solicitar un don
// Body: mensaje

POST /wp-json/flavor/v1/economia-don/dones/{id}/entregar
// Confirmar entrega

GET /wp-json/flavor/v1/economia-don/mis-dones
// Mis dones y estadisticas
```

## AJAX Handlers

| Action | Metodo | Descripcion |
|--------|--------|-------------|
| `ed_solicitar_don` | POST | Solicitar un don |
| `ed_confirmar_entrega` | POST | Confirmar entrega |
| `ed_agradecer` | POST | Enviar gratitud |
| `ed_publicar_don` | POST | Publicar nuevo don |
| `flavor_don_ofrecer` | POST | Ofrecer don (frontend) |
| `flavor_don_solicitar` | POST | Solicitar don (frontend) |
| `flavor_don_confirmar_entrega` | POST | Confirmar (frontend) |
| `flavor_don_agradecer` | POST | Agradecer (frontend) |
| `flavor_don_obtener` | GET | Obtener dones (publico) |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Dones por comunidad |
| banco-tiempo | Complemento | Alternativa con intercambio |
| red-social | Feed | Actividad en timeline |
| multimedia | Integracion | Imagenes de dones |
| foros | Discusion | Foro por don |
| chat-grupos | Comunicacion | Chat por don |
| notificaciones | Alertas | Notificaciones de solicitudes |

## Hooks y Filtros

### Actions

```php
// Don publicado
do_action('flavor_ed_don_publicado', $don_id, $usuario_id);

// Don solicitado
do_action('flavor_ed_don_solicitado', $don_id, $solicitante_id);

// Entrega confirmada
do_action('flavor_ed_entrega_confirmada', $don_id, $donante_id, $receptor_id);

// Gratitud enviada
do_action('flavor_ed_gratitud_enviada', $gratitud_id, $don_id);
```

### Filters

```php
// Categorias habilitadas
apply_filters('flavor_ed_categorias', $categorias);

// Validar publicacion
apply_filters('flavor_ed_validar_don', $valido, $datos);

// Mensaje de confirmacion
apply_filters('flavor_ed_mensaje_confirmacion', $mensaje, $tipo);
```

## Configuracion

```php
'economia_don' => [
    'enabled' => true,
    'categorias_habilitadas' => ['objetos', 'alimentos', 'servicios', 'tiempo', 'conocimiento', 'espacios'],
    'permitir_anonimato' => true,
    'notificar_nuevos_dones' => true,
    'mostrar_mapa' => true,
    'radio_busqueda_km' => 10,
    'mostrar_en_dashboard' => true,
]
```

## Principios del Don

El modulo implementa los principios fundamentales de la economia del don:

1. **Dar sin esperar retorno**: El acto de dar es valioso en si mismo
2. **Abundancia compartida**: Lo que me sobra puede serle util a otra persona
3. **Sin contabilidad**: No hay puntos, saldo ni intercambio obligado
4. **Gratitud como unico "pago"**: El receptor expresa su gratitud publicamente
5. **Anonimato opcional**: Posibilidad de donar de forma anonima

## Flujo de Uso

```
1. Usuario publica don
   └── Estado: disponible
       └── Se muestra en catalogo

2. Otro usuario lo solicita
   └── Estado: reservado
       └── Notificacion al donante

3. Donante confirma entrega
   └── Estado: entregado
       └── Notificacion al receptor

4. Receptor agradece
   └── Estado: recibido
       └── Gratitud en muro publico
```

## Estadisticas de Usuario

El modulo registra estadisticas por usuario:

| Metadato | Clave | Descripcion |
|----------|-------|-------------|
| Dones dados | `_ed_dones_dados` | Total de dones entregados |
| Dones recibidos | `_ed_dones_recibidos` | Total de dones recibidos |
| Dones activos | (calculado) | Dones en estado disponible |

## Widget de Dashboard

**Clase:** `Flavor_Economia_Don_Widget`

Muestra en el dashboard:
- Dones disponibles actualmente
- Ultimos dones publicados
- Acceso rapido a ofrecer don

## Panel de Administracion

Paginas en el panel unificado de gestion:

| Slug | Titulo | Descripcion |
|------|--------|-------------|
| economia-don-dashboard | Dashboard | Estadisticas generales |
| economia-don-listado | Dones | Listado y gestion de dones |
| economia-don-solicitudes | Solicitudes | Solicitudes pendientes (con badge) |
| economia-don-gratitudes | Gratitudes | Muro de gratitud |
