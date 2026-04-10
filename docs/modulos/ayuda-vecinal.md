# Modulo: Ayuda Vecinal

> Red de ayuda mutua entre vecinos - solicita y ofrece ayuda en tu comunidad

## Descripcion

Plataforma que conecta vecinos que necesitan ayuda con voluntarios dispuestos a colaborar. El sistema permite publicar solicitudes de ayuda, ofrecer servicios voluntarios, coordinar la asistencia y valorar las interacciones. Implementa un sistema de puntos de solidaridad para reconocer a los voluntarios mas activos.

## Principios Gailu

- **Cuidados**: Modulo centrado en el apoyo mutuo y la solidaridad vecinal
- **Cohesion**: Contribuye a fortalecer los vinculos comunitarios
- **Resiliencia**: Desarrolla redes de apoyo para situaciones de necesidad

## Archivos Principales

```
includes/modules/ayuda-vecinal/
├── class-ayuda-vecinal-module.php              # Clase principal del modulo
├── class-ayuda-vecinal-api.php                 # API REST para movil
├── class-ayuda-vecinal-dashboard-widget.php    # Widget de dashboard
├── frontend/
│   └── class-ayuda-vecinal-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                           # Vista dashboard admin
│   ├── solicitudes.php                         # Gestion de solicitudes
│   ├── voluntarios.php                         # Gestion de voluntarios
│   ├── matches.php                             # Emparejamientos
│   └── estadisticas.php                        # Estadisticas del modulo
└── assets/
    ├── css/
    │   └── ayuda-vecinal.css
    └── js/
        └── ayuda-vecinal.js
```

## Tablas de Base de Datos

### wp_flavor_ayuda_solicitudes
Solicitudes de ayuda publicadas por usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| solicitante_id | bigint(20) | FK usuario que solicita |
| categoria | varchar(100) | Categoria de ayuda |
| titulo | varchar(255) | Titulo de la solicitud |
| descripcion | text | Descripcion detallada |
| urgencia | enum | baja/media/alta/urgente |
| ubicacion | varchar(500) | Direccion o zona |
| ubicacion_lat | decimal(10,7) | Latitud |
| ubicacion_lng | decimal(10,7) | Longitud |
| fecha_necesaria | datetime | Cuando se necesita la ayuda |
| duracion_estimada_minutos | int | Tiempo estimado |
| necesita_desplazamiento | tinyint(1) | Requiere ir al lugar |
| requiere_habilidad_especifica | tinyint(1) | Necesita habilidades |
| habilidades_requeridas | text | Habilidades necesarias |
| num_personas_necesarias | int | Numero de voluntarios |
| compensacion | text | Compensacion ofrecida (opcional) |
| estado | enum | abierta/asignada/en_curso/completada/cancelada/expirada |
| ayudante_id | bigint(20) | FK voluntario asignado |
| fecha_solicitud | datetime | Fecha de creacion |
| fecha_asignacion | datetime | Fecha de asignacion |
| fecha_completado | datetime | Fecha de finalizacion |

**Indices:** solicitante_id, ayudante_id, categoria, urgencia, estado, fecha_necesaria, ubicacion (lat, lng)

### wp_flavor_ayuda_ofertas
Ofertas de ayuda permanentes de voluntarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK voluntario |
| categoria | varchar(100) | Categoria de ayuda |
| titulo | varchar(255) | Titulo de la oferta |
| descripcion | text | Descripcion del servicio |
| habilidades | text JSON | Habilidades del voluntario |
| disponibilidad | text JSON | Dias y horarios disponibles |
| radio_km | int | Radio de accion en km |
| tiene_vehiculo | tinyint(1) | Dispone de vehiculo |
| estado | varchar(20) | activa/pausada/inactiva |
| fecha_creacion | datetime | Fecha de creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** usuario_id, categoria, estado

### wp_flavor_ayuda_respuestas
Respuestas de voluntarios a solicitudes.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| solicitud_id | bigint(20) | FK solicitud |
| ayudante_id | bigint(20) | FK voluntario |
| mensaje | text | Mensaje del voluntario |
| disponibilidad_propuesta | datetime | Fecha propuesta |
| estado | enum | pendiente/aceptada/rechazada/retirada |
| fecha_respuesta | datetime | Fecha de respuesta |

**Indices:** solicitud_id, ayudante_id, estado

### wp_flavor_ayuda_valoraciones
Valoraciones entre solicitantes y voluntarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| solicitud_id | bigint(20) | FK solicitud |
| valorador_id | bigint(20) | Quien valora |
| valorado_id | bigint(20) | Quien es valorado |
| tipo | enum | ayudante/solicitante |
| puntuacion | int | Puntuacion general (1-5) |
| aspectos | text JSON | puntualidad, amabilidad, calidad |
| comentario | text | Comentario opcional |
| puntos_solidaridad_otorgados | int | Puntos ganados |
| fecha_valoracion | datetime | Fecha de valoracion |

**Indices:** solicitud_id + valorador_id (UNIQUE), valorado_id

## Shortcodes

### Listado y Busqueda

```php
[ayuda_vecinal_solicitudes]
// Listado de solicitudes de ayuda
// - categoria: filtrar por categoria
// - urgencia: filtrar por urgencia
// - limite: numero de resultados (default: 12)
// - mostrar_filtros: true|false (default: true)

[ayuda_vecinal_mapa]
// Mapa de solicitudes cercanas
// - radio_km: radio de busqueda (default: 5)
// - limite: numero de marcadores

[ayuda_vecinal_cercana]
// Widget compacto de solicitudes cercanas
// - limite: numero de resultados (default: 3)
// - radio_km: radio de busqueda (default: 5)
```

### Formularios

```php
[ayuda_vecinal_solicitar]
// Formulario para solicitar ayuda
// Requiere usuario autenticado

[ayuda_vecinal_ofrecer]
// Formulario para publicar oferta de ayuda
// Requiere usuario autenticado
```

### Perfil y Seguimiento

```php
[ayuda_vecinal_mis_ayudas]
// Mis ayudas ofrecidas
// - estado: filtrar por estado
// - limite: numero de resultados (default: 20)

[ayuda_vecinal_mis_solicitudes]
// Mis solicitudes de ayuda
// - estado: filtrar por estado
// - limite: numero de resultados (default: 20)

[ayuda_vecinal_estadisticas]
// Estadisticas de la comunidad
// Muestra: ayudas completadas, voluntarios activos, horas donadas
```

## Dashboard Tab

**Clase:** `Flavor_Ayuda_Vecinal_Dashboard_Widget`

**Estadisticas mostradas:**
- Solicitudes activas (necesitan ayuda)
- Ofertas activas (ofrecen ayuda)
- Mis solicitudes pendientes

**Widget features:**
- Tamano: medium
- Categoria: servicios
- Refrescable: si
- Cache: 120 segundos

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/ayuda-vecinal/` | index | Dashboard principal |
| `/mi-portal/ayuda-vecinal/solicitudes/` | solicitudes | Ver solicitudes activas |
| `/mi-portal/ayuda-vecinal/ofertas/` | ofertas | Ver ofertas de ayuda |
| `/mi-portal/ayuda-vecinal/solicitar/` | solicitar | Crear solicitud |
| `/mi-portal/ayuda-vecinal/ofrecer/` | ofrecer | Crear oferta |
| `/mi-portal/ayuda-vecinal/mis-solicitudes/` | mis-solicitudes | Mis solicitudes |
| `/mi-portal/ayuda-vecinal/solicitud/{id}/` | detalle | Detalle solicitud |

## REST API

### Endpoints Publicos

```
GET /flavor/v1/ayuda-vecinal
// Listar solicitudes activas
// Params: categoria, urgencia, estado, per_page, page

GET /flavor/v1/ayuda-vecinal/{id}
// Obtener detalle de solicitud
```

### Endpoints Autenticados

```
POST /flavor/v1/ayuda-vecinal
// Crear solicitud de ayuda
// Body: categoria, titulo, descripcion, urgencia, ubicacion, fecha_necesaria...

POST /flavor/v1/ayuda-vecinal/{id}/responder
// Ofrecer ayuda en una solicitud
// Body: mensaje, disponibilidad_propuesta

GET /flavor/v1/ayuda-vecinal/mis-solicitudes
// Mis solicitudes (creadas y respondidas)
// Params: tipo, estado, per_page, page

PUT /flavor/v1/ayuda-vecinal/{id}
// Actualizar solicitud (solo propietario)
// Body: estado, campos editables...
```

### API Movil (Namespace: flavor-platform/v1)

```
GET /ayuda-vecinal
// Sistema completo: solicitudes, voluntarios, categorias

POST /ayuda-vecinal/solicitudes
// Crear solicitud desde app

POST /ayuda-vecinal/solicitudes/{id}/ofrecer
// Ofrecer ayuda desde app

DELETE /ayuda-vecinal/solicitudes/{id}
// Cancelar solicitud propia
```

## Acciones del Modulo (Chat IA)

| Accion | Descripcion | Parametros |
|--------|-------------|------------|
| listar_solicitudes | Listar solicitudes | estado, categoria, limit, offset |
| solicitudes_activas | Ver solicitudes abiertas | categoria, urgencia |
| solicitudes_cercanas | Solicitudes por ubicacion | lat, lng, radio_km |
| crear_solicitud | Crear nueva solicitud | categoria, titulo, descripcion, urgencia |
| mis_solicitudes | Mis solicitudes | - |
| ofrecer_ayuda | Responder a solicitud | solicitud_id, mensaje |
| aceptar_ayudante | Aceptar voluntario | respuesta_id |
| publicar_oferta | Crear oferta permanente | categoria, titulo, descripcion |
| mis_ayudas_realizadas | Ayudas completadas | - |
| marcar_completada | Finalizar ayuda | solicitud_id |
| valorar_ayuda | Valorar interaccion | solicitud_id, puntuacion, comentario |
| mis_puntos_solidaridad | Ver mis puntos | - |
| estadisticas_ayuda | Stats (admin) | periodo |

## Categorias de Ayuda

| ID | Nombre | Icono |
|----|--------|-------|
| compras | Compras y recados | shopping_cart |
| cuidado_mayores | Cuidado de mayores | elderly |
| cuidado_ninos | Cuidado de ninos | child_care |
| mascotas | Cuidado de mascotas | pets |
| transporte | Transporte | directions_car |
| tecnologia | Ayuda con tecnologia | computer |
| tramites | Tramites y gestiones | description |
| reparaciones | Reparaciones menores | build |
| compania | Compania y conversacion | accessibility |
| otro | Otras ayudas | help |

## Niveles de Urgencia

| Nivel | Descripcion | Color |
|-------|-------------|-------|
| urgente | Necesidad inmediata | rojo |
| alta | En 24 horas | naranja |
| media | En pocos dias | amarillo |
| baja | Cuando sea posible | verde |

## Estados de Solicitud

| Estado | Descripcion |
|--------|-------------|
| abierta | Esperando voluntarios |
| asignada | Voluntario aceptado |
| en_curso | Ayuda en progreso |
| completada | Ayuda finalizada |
| cancelada | Cancelada por usuario |
| expirada | Tiempo limite alcanzado |

## Sistema de Puntos de Solidaridad

El modulo implementa un sistema de reconocimiento basado en puntos:

- **Puntos por ayuda completada:** Configurable (default: 10 puntos)
- **Valoraciones:** Impactan en la visibilidad del voluntario
- **Ranking:** Voluntarios mas activos destacados

```php
'sistema_puntos_solidaridad' => true,
'puntos_por_ayuda' => 10,
```

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Red de ayuda por comunidad |
| eventos | Complemento | Eventos de voluntariado |
| banco_tiempo | Alternativa | Intercambio de servicios |
| multimedia | Integracion | Imagenes en solicitudes |

## Hooks y Filtros

### Actions

```php
// Solicitud creada
do_action('flavor_ayuda_solicitud_creada', $solicitud_id, $usuario_id);

// Oferta de ayuda enviada
do_action('flavor_ayuda_oferta_enviada', $solicitud_id, $ayudante_id);

// Ayudante aceptado
do_action('flavor_ayuda_ayudante_aceptado', $solicitud_id, $ayudante_id);

// Ayuda completada
do_action('flavor_ayuda_completada', $solicitud_id, $ayudante_id);

// Valoracion enviada
do_action('flavor_ayuda_valoracion_enviada', $valoracion_id, $solicitud_id);
```

### Filters

```php
// Categorias disponibles
apply_filters('flavor_ayuda_categorias', $categorias);

// Puntos por ayuda
apply_filters('flavor_ayuda_puntos_por_ayuda', $puntos, $solicitud_id);

// Validar solicitud antes de crear
apply_filters('flavor_ayuda_validar_solicitud', $valido, $datos);
```

## Notificaciones

El modulo envia notificaciones por email:

- **Al solicitante:** Cuando un voluntario se ofrece
- **Al voluntario:** Cuando es aceptado
- **A voluntarios suscritos:** Cuando hay nueva solicitud en su categoria

## Configuracion

```php
'ayuda_vecinal' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'requiere_verificacion_usuarios' => true,
    'permite_valoraciones' => true,
    'sistema_puntos_solidaridad' => true,
    'puntos_por_ayuda' => 10,
    'categorias_ayuda' => [
        'compras',
        'cuidado_mayores',
        'cuidado_ninos',
        'mascotas',
        'transporte',
        'tecnologia',
        'tramites',
        'reparaciones',
        'compania',
        'otro',
    ],
]
```

## Componentes Web (Visual Builder)

| Componente | Categoria | Descripcion |
|------------|-----------|-------------|
| hero_ayuda | hero | Hero con estadisticas |
| solicitudes_grid | listings | Grid de solicitudes activas |
| categorias_ayuda | features | Categorias de ayuda disponibles |
| cta_voluntario | cta | Llamada a la accion para voluntarios |

## Knowledge Base (IA)

El modulo proporciona contexto a la IA sobre:

- Tipos de ayuda disponibles
- Flujo para solicitar ayuda
- Flujo para ofrecer ayuda
- Sistema de puntos
- Niveles de urgencia
- Principios de la red de ayuda

## FAQs Integradas

| Pregunta | Respuesta |
|----------|-----------|
| Tengo que pagar por la ayuda? | No, es ayuda gratuita entre vecinos |
| Y si nadie me ayuda? | Reformula tu solicitud o contacta con el coordinador |
| Estoy obligado a ayudar? | No, la ayuda debe ser siempre voluntaria |

## Administracion

### Paginas Admin

- **Dashboard:** Vision general de la red de ayuda
- **Solicitudes:** Gestion de solicitudes activas
- **Voluntarios:** Listado de voluntarios y sus estadisticas

### Permisos

- `manage_options`: Acceso completo
- `flavor_ver_dashboard`: Solo lectura (gestores de grupo)
