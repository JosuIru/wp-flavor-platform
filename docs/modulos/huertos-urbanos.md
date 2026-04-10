# Modulo: Huertos Urbanos

> Gestion completa de huertos urbanos comunitarios

## Descripcion

Sistema integral de gestion de huertos urbanos que permite administrar parcelas, cultivos, tareas comunitarias, intercambios de productos y turnos de riego. Pensado para comunidades, cooperativas y organizaciones que gestionan espacios de cultivo compartidos con enfoque ecologico y participativo.

## Archivos Principales

```
includes/modules/huertos-urbanos/
├── class-huertos-urbanos-module.php              # Clase principal del modulo
├── class-huertos-urbanos-dashboard-tab.php       # Tabs del dashboard usuario
├── class-huertos-urbanos-api.php                 # API REST para movil
├── class-huertos-dashboard-widget.php            # Widget para dashboard
├── install.php                                   # Instalacion de BD
├── frontend/
│   └── class-huertos-urbanos-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                             # Vista dashboard admin
│   ├── parcelas.php                              # Gestion de parcelas
│   ├── huertanos.php                             # Gestion de hortelanos
│   ├── cosechas.php                              # Registro de cosechas
│   ├── recursos.php                              # Recursos compartidos
│   └── config.php                                # Configuracion
└── assets/
    ├── css/huertos.css
    └── js/huertos.js
```

## Tablas de Base de Datos

### wp_flavor_huertos
Tabla principal de huertos urbanos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre del huerto |
| slug | varchar(255) | URL amigable |
| descripcion | longtext | Descripcion del huerto |
| imagen_destacada | varchar(500) | URL imagen principal |
| galeria | json | Galeria de imagenes |
| direccion | varchar(500) | Direccion completa |
| latitud | decimal(10,8) | Coordenada GPS |
| longitud | decimal(11,8) | Coordenada GPS |
| superficie_total | decimal(10,2) | Superficie en m2 |
| total_parcelas | int | Numero de parcelas |
| parcelas_disponibles | int | Parcelas libres |
| tipo_riego | varchar(100) | Sistema de riego |
| acceso_agua | tinyint(1) | Tiene punto de agua |
| herramientas_comunes | tinyint(1) | Herramientas compartidas |
| compostaje_comunitario | tinyint(1) | Tiene compostadora |
| horario_apertura | time | Hora apertura |
| horario_cierre | time | Hora cierre |
| dias_apertura | varchar(100) | Dias de acceso |
| normas | text | Normativa del huerto |
| contacto_nombre | varchar(255) | Nombre contacto |
| contacto_email | varchar(255) | Email contacto |
| contacto_telefono | varchar(50) | Telefono contacto |
| cuota_mensual | decimal(10,2) | Precio mensual |
| deposito | decimal(10,2) | Fianza |
| estado | enum | activo/inactivo/en_construccion |
| metadata | json | Datos adicionales |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** slug, estado, coordenadas

### wp_flavor_huertos_parcelas
Parcelas individuales dentro de cada huerto.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| huerto_id | bigint(20) | FK huerto |
| numero | varchar(50) | Numero/codigo parcela |
| superficie | decimal(8,2) | Tamano en m2 |
| ubicacion_descripcion | varchar(255) | Descripcion ubicacion |
| posicion_x | int | Coordenada en mapa |
| posicion_y | int | Coordenada en mapa |
| tipo | enum | individual/compartida/comunitaria |
| estado | enum | disponible/ocupada/reservada/mantenimiento |
| acceso_agua_directo | tinyint(1) | Riego directo |
| sombra | varchar(100) | Nivel de sombra |
| orientacion | enum | norte/sur/este/oeste |
| tiene_riego | tinyint(1) | Sistema de riego |
| observaciones | text | Notas adicionales |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** huerto_id, estado, numero

### wp_flavor_huertos_asignaciones
Asignaciones de parcelas a usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| parcela_id | bigint(20) | FK parcela |
| usuario_id | bigint(20) | FK usuario WordPress |
| fecha_asignacion | date | Inicio asignacion |
| fecha_fin | date | Fin asignacion |
| estado | enum | activa/finalizada/cancelada/suspendida |
| motivo_fin | text | Razon de finalizacion |
| deposito_pagado | decimal(10,2) | Fianza pagada |
| deposito_devuelto | tinyint(1) | Fianza devuelta |
| created_at | datetime | Fecha creacion |

**Indices:** parcela_id, usuario_id, estado

### wp_flavor_huertos_solicitudes
Solicitudes de nuevas parcelas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| huerto_id | bigint(20) | FK huerto |
| usuario_id | bigint(20) | FK usuario |
| parcela_preferida_id | bigint(20) | FK parcela preferida |
| tipo_parcela_preferido | varchar(50) | Tipo preferido |
| experiencia_previa | text | Experiencia cultivo |
| motivacion | text | Motivacion solicitud |
| disponibilidad_horaria | text | Horarios disponibles |
| acepta_normas | tinyint(1) | Acepto normativa |
| estado | enum | pendiente/aprobada/rechazada/lista_espera |
| posicion_lista_espera | int | Posicion en cola |
| notas_admin | text | Notas internas |
| fecha_respuesta | datetime | Fecha respuesta |
| admin_id | bigint(20) | Admin que proceso |
| created_at | datetime | Fecha solicitud |

### wp_flavor_huertos_cultivos
Registro de cultivos en parcelas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| parcela_id | bigint(20) | FK parcela |
| nombre | varchar(255) | Nombre cultivo |
| variedad | varchar(255) | Variedad especifica |
| fecha_siembra | date | Fecha siembra |
| fecha_cosecha_estimada | date | Estimacion cosecha |
| fecha_cosecha_real | date | Cosecha real |
| estado | enum | sembrado/creciendo/listo/cosechado/fallido |
| cantidad_sembrada | varchar(100) | Cantidad inicial |
| cantidad_cosechada | varchar(100) | Cantidad obtenida |
| notas | text | Observaciones |
| created_at | datetime | Fecha registro |

### wp_flavor_huertos_actividades
Diario de actividades en parcelas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| parcela_id | bigint(20) | FK parcela |
| usuario_id | bigint(20) | FK usuario |
| tipo | enum | siembra/riego/cosecha/tratamiento/mantenimiento/otro |
| titulo | varchar(255) | Titulo actividad |
| descripcion | text | Descripcion detallada |
| fecha_actividad | date | Fecha realizada |
| cultivo | varchar(255) | Cultivo relacionado |
| cantidad | varchar(100) | Cantidad si aplica |
| fotos | json | Galeria fotos |
| clima | varchar(100) | Condiciones clima |
| created_at | datetime | Fecha registro |

### wp_flavor_huertos_tareas
Tareas comunitarias del huerto.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| huerto_id | bigint(20) | FK huerto |
| creador_id | bigint(20) | FK usuario creador |
| titulo | varchar(255) | Titulo tarea |
| descripcion | text | Descripcion |
| tipo | enum | riego/limpieza/mantenimiento/taller/reunion/siembra_comunitaria/cosecha_comunitaria/compostaje/otro |
| fecha | date | Fecha programada |
| hora_inicio | time | Hora inicio |
| hora_fin | time | Hora fin |
| max_participantes | int | Aforo maximo |
| ubicacion_especifica | varchar(255) | Lugar exacto |
| materiales_necesarios | text | Lista materiales |
| es_obligatoria | tinyint(1) | Es obligatoria |
| puntos_participacion | int | Puntos por asistir |
| estado | enum | programada/en_curso/completada/cancelada |
| notas_completado | text | Notas finalizacion |
| created_at | datetime | Fecha creacion |

### wp_flavor_huertos_intercambios
Sistema de intercambio de productos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| huerto_id | bigint(20) | FK huerto (opcional) |
| tipo | enum | semillas/cosecha/plantulas/herramientas/conocimiento |
| titulo | varchar(255) | Titulo oferta |
| descripcion | text | Descripcion |
| cantidad | varchar(100) | Cantidad disponible |
| foto_url | varchar(500) | Foto producto |
| busca_a_cambio | text | Que busca |
| disponible_hasta | date | Fecha limite |
| contacto_preferido | enum | mensaje/email/telefono/presencial |
| estado | enum | disponible/reservado/intercambiado/cancelado/expirado |
| veces_visto | int | Contador visitas |
| created_at | datetime | Fecha publicacion |

### wp_flavor_huertos_turnos_riego
Gestion de turnos de riego.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| huerto_id | bigint(20) | FK huerto |
| usuario_id | bigint(20) | FK usuario asignado |
| fecha_turno | date | Fecha del turno |
| hora_inicio | time | Hora inicio |
| hora_fin | time | Hora fin |
| zona_riego | varchar(100) | Zona asignada |
| completado | tinyint(1) | Turno completado |
| fecha_completado | datetime | Fecha completado |
| sustituido_por | bigint(20) | Usuario sustituto |
| notas | text | Observaciones |

### wp_flavor_huertos_pagos
Pagos de cuotas y depositos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| asignacion_id | bigint(20) | FK asignacion |
| usuario_id | bigint(20) | FK usuario |
| concepto | varchar(255) | Concepto pago |
| importe | decimal(10,2) | Cantidad |
| periodo | varchar(50) | Periodo cubierto |
| fecha_vencimiento | date | Fecha limite |
| fecha_pago | datetime | Fecha pago real |
| estado | enum | pendiente/pagado/vencido/cancelado |
| metodo_pago | varchar(100) | Metodo usado |
| referencia_pago | varchar(255) | Referencia |
| created_at | datetime | Fecha creacion |

## Shortcodes

### Shortcodes del Modulo Principal

```php
[mapa_huertos]
// Mapa interactivo con todos los huertos
// Usa Leaflet para visualizacion

[lista_huertos]
// Listado de huertos disponibles
// - estado: activo|todos
// - limite: numero

[mi_parcela]
// Informacion de la parcela del usuario
// Muestra cultivos activos y actividad reciente

[calendario_cultivos]
// Calendario con fechas de siembra y cosecha

[intercambios_huertos]
// Listado de intercambios disponibles
// - tipo: semillas|cosecha|plantulas|herramientas|conocimiento

[tareas_huerto]
// Proximas tareas comunitarias
// - huerto_id: ID especifico (opcional)
```

### Shortcodes del Frontend Controller

```php
[huertos_listado]
// Listado de huertos con filtros
// - estado: activo (default)
// - limite: 12 (default)
// - mostrar_mapa: true|false

[huertos_mapa]
// Mapa interactivo de huertos
// - altura: 500px (default)
// - zoom: 13 (default)

[huertos_detalle]
// Detalle de un huerto especifico
// - id: ID del huerto
// - slug: slug del huerto

[huertos_solicitar]
// Formulario de solicitud de parcela
// - huerto_id: preseleccionar huerto

[huertos_mi_parcela]
// Panel de mi parcela asignada
// Acceso a diario y cultivos

[huertos_diario]
// Diario de actividades del usuario
// Registro de siembra, riego, cosecha...

[huertos_cultivos]
// Listado de mis cultivos activos
// Registro y seguimiento
```

## Dashboard Tabs (Area Usuario)

**Clase:** `Flavor_Huertos_Urbanos_Dashboard_Tab`

**Tabs disponibles en Mi Portal:**
- `huertos` - Listado de huertos disponibles
- `mi-parcela` - Mi parcela asignada con cultivos y actividad
- `calendario-huertos` - Calendario de tareas, turnos de riego y cosechas
- `mapa-huertos` - Mapa interactivo de ubicaciones

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/huertos/` | index | Listado de huertos |
| `/huertos/{slug}/` | detalle | Detalle de huerto |
| `/huertos/{slug}/solicitar/` | solicitar | Formulario solicitud |
| `/mi-portal/huertos/` | dashboard | Dashboard usuario |
| `/mi-portal/huertos/mi-parcela/` | mi-parcela | Mi parcela |
| `/mi-portal/huertos/diario/` | diario | Diario actividades |
| `/mi-portal/huertos/cultivos/` | cultivos | Mis cultivos |
| `/mi-portal/huertos/registrar-cultivo/` | nuevo-cultivo | Registrar cultivo |
| `/mi-portal/huertos/registrar-actividad/` | nueva-actividad | Nueva actividad |

## API REST

### Endpoints Disponibles

```
GET  /wp-json/flavor-platform/v1/huertos-urbanos/dashboard
     Obtiene dashboard completo del usuario

POST /wp-json/flavor-platform/v1/huertos-urbanos/solicitar-parcela
     Envia solicitud de parcela
     Params: tamanio (required), observaciones

POST /wp-json/flavor-platform/v1/huertos-urbanos/tareas/{id}/completar
     Marca tarea como completada

POST /wp-json/flavor-platform/v1/huertos-urbanos/intercambios/{id}/contactar
     Contacta con usuario de intercambio
     Params: mensaje (required)
```

## Panel de Administracion

**Paginas admin disponibles:**

| Pagina | Descripcion |
|--------|-------------|
| `huertos-dashboard` | Dashboard con estadisticas |
| `huertos-parcelas` | Gestion de parcelas |
| `huertos-hortelanos` | Gestion de hortelanos activos |
| `huertos-lista-espera` | Solicitudes pendientes |
| `huertos-recursos` | Recursos compartidos |
| `huertos-config` | Configuracion del modulo |

## Configuracion

```php
'huertos_urbanos' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'permite_solicitar_parcela' => true,
    'precio_parcela_anual' => 0,              // 0 = gratuito
    'requiere_compromiso_asistencia' => true,
    'horas_minimas_mes' => 4,                 // Horas trabajo comunitario
    'permite_intercambio_cosechas' => true,
    'sistema_turnos_riego' => true,
    'max_parcelas_por_usuario' => 1,
    'dias_espera_lista' => 30,
    'notificaciones_email' => true,
    'mostrar_mapa_publico' => true,
    'coordenadas_centro_lat' => 40.4168,
    'coordenadas_centro_lng' => -3.7038,
    'zoom_mapa_default' => 12,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `huertos_ver` | Ver huertos publicos |
| `huertos_solicitar` | Solicitar parcela |
| `huertos_gestionar_parcela` | Gestionar parcela propia |
| `huertos_registrar_actividad` | Registrar actividades |
| `huertos_crear_intercambio` | Publicar intercambios |
| `huertos_apuntarse_tareas` | Inscribirse en tareas |
| `huertos_gestionar` | Admin completo huertos |
| `huertos_aprobar_solicitudes` | Aprobar/rechazar solicitudes |

## Acciones AJAX

```php
// Listar huertos cercanos
wp_ajax_flavor_huertos_listar

// Detalle de huerto
wp_ajax_flavor_huertos_detalle

// Mi parcela
wp_ajax_flavor_huertos_mi_parcela

// Cultivos de parcela
wp_ajax_flavor_huertos_cultivos_parcela

// Tareas proximas
wp_ajax_flavor_huertos_tareas

// Intercambios disponibles
wp_ajax_flavor_huertos_intercambios

// Solicitar parcela
wp_ajax_flavor_huertos_solicitar_parcela

// Registrar cultivo
wp_ajax_flavor_huertos_registrar_cultivo

// Apuntarse a tarea
wp_ajax_flavor_huertos_apuntarse_tarea

// Publicar intercambio
wp_ajax_flavor_huertos_publicar_intercambio

// Completar tarea
wp_ajax_flavor_huertos_completar_tarea

// Registrar actividad
wp_ajax_flavor_huertos_registrar_actividad

// Calendario de riego
wp_ajax_flavor_huertos_calendario_riego

// Marcar riego completado
wp_ajax_flavor_huertos_marcar_riego

// Estadisticas
wp_ajax_flavor_huertos_estadisticas
```

## Tipos de Actividades

| Tipo | Descripcion |
|------|-------------|
| `siembra` | Siembra de semillas o plantulas |
| `riego` | Riego de la parcela |
| `cosecha` | Recoleccion de productos |
| `tratamiento` | Aplicacion de tratamientos |
| `mantenimiento` | Mantenimiento general |
| `poda` | Poda de plantas |
| `abonado` | Aplicacion de abono |
| `limpieza` | Limpieza de parcela |
| `transplante` | Transplante de plantas |
| `observacion` | Observacion y seguimiento |
| `otro` | Otras actividades |

## Estados de Cultivo

| Estado | Descripcion |
|--------|-------------|
| `planificado` | Planificado para sembrar |
| `sembrado` | Recien sembrado |
| `germinando` | En germinacion |
| `crecimiento` | En fase de crecimiento |
| `floracion` | En floracion |
| `maduracion` | Madurando frutos |
| `cosecha` | Listo para cosechar |
| `finalizado` | Cultivo finalizado |
| `fallido` | Cultivo fallido |

## Tipos de Intercambio

| Tipo | Descripcion |
|------|-------------|
| `semillas` | Semillas para siembra |
| `cosecha` | Productos cosechados |
| `plantulas` | Plantulas para transplante |
| `herramientas` | Herramientas de cultivo |
| `conocimiento` | Conocimientos y consejos |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Huertos de comunidad |
| socios | Membresia | Descuentos para socios |
| eventos | Relacion | Eventos en el huerto |
| recetas | Integracion | Recetas con productos |
| multimedia | Integracion | Galeria de fotos |

## Cron Jobs

```php
// Notificaciones programadas (diario)
wp_schedule_event(time(), 'daily', 'flavor_huertos_cron_notificaciones');
```

## Ejemplo de Uso

### Mostrar listado de huertos
```php
[huertos_listado estado="activo" limite="6" mostrar_mapa="true"]
```

### Crear pagina de detalle de huerto
```php
[huertos_detalle slug="huerto-comunitario-centro"]
```

### Dashboard personal del hortelano
```php
[huertos_mi_parcela]
[huertos_cultivos]
```

### Sistema de intercambio
```php
[intercambios_huertos tipo="semillas"]
```

## Principios Gailu

Este modulo implementa los principios:
- **Regeneracion**: Promueve cultivo ecologico y sostenible
- **Economia local**: Fomenta intercambio local de productos

Contribuye a:
- **Autonomia**: Autoproduccion de alimentos
- **Impacto**: Reduccion huella ecologica
