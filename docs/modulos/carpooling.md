# Modulo: Carpooling

> Sistema de viajes compartidos entre vecinos para reducir costes y emisiones

## Descripcion

Sistema completo de carpooling (viajes compartidos) que permite a los usuarios publicar viajes como conductores o reservar plazas como pasajeros. Incluye busqueda por geolocalizacion, sistema de valoraciones, gestion de vehiculos, rutas recurrentes, notificaciones por email y sincronizacion con red federada para compartir viajes entre nodos.

## Archivos Principales

```
includes/modules/carpooling/
├── class-carpooling-module.php              # Clase principal del modulo
├── class-carpooling-dashboard-tab.php       # Tabs del dashboard de usuario
├── class-carpooling-dashboard-widget.php    # Widget para dashboard
├── frontend/
│   └── class-carpooling-frontend-controller.php  # Controlador frontend
├── views/
│   ├── buscar-viaje.php                     # Vista busqueda
│   ├── publicar-viaje.php                   # Formulario publicacion
│   ├── mis-viajes.php                       # Viajes del conductor
│   ├── mis-reservas.php                     # Reservas del pasajero
│   ├── viajes.php                           # Listado viajes
│   ├── reservas.php                         # Gestion reservas
│   ├── conductores.php                      # Lista conductores
│   └── dashboard.php                        # Dashboard admin
├── templates/
└── assets/
    ├── css/
    └── js/
```

## Tablas de Base de Datos

### wp_flavor_carpooling_viajes
Viajes publicados por conductores.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| conductor_id | bigint(20) UNSIGNED | FK usuario conductor |
| vehiculo_id | bigint(20) UNSIGNED | FK vehiculo (opcional) |
| origen | varchar(255) | Direccion origen |
| destino | varchar(255) | Direccion destino |
| origen_lat | decimal(10,8) | Latitud origen |
| origen_lng | decimal(11,8) | Longitud origen |
| destino_lat | decimal(10,8) | Latitud destino |
| destino_lng | decimal(11,8) | Longitud destino |
| fecha_salida | datetime | Fecha y hora de salida |
| plazas_disponibles | int(11) | Plazas libres |
| plazas_ocupadas | int(11) | Plazas reservadas |
| precio_por_plaza | decimal(10,2) | Precio por pasajero |
| descripcion | text | Notas del viaje |
| permite_fumar | tinyint(1) | Permite fumar |
| permite_mascotas | tinyint(1) | Permite mascotas |
| permite_equipaje_grande | tinyint(1) | Permite equipaje grande |
| estado | enum | activo/completo/cancelado/finalizado |
| es_recurrente | tinyint(1) | Es viaje recurrente |
| ruta_recurrente_id | bigint(20) UNSIGNED | FK ruta recurrente |
| created_at | datetime | Fecha creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** conductor_id, vehiculo_id, estado, fecha_salida, ruta_recurrente_id

### wp_flavor_carpooling_reservas
Reservas de plazas por pasajeros.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| viaje_id | bigint(20) UNSIGNED | FK viaje |
| pasajero_id | bigint(20) UNSIGNED | FK usuario pasajero |
| numero_plazas | int(11) | Plazas reservadas |
| estado | enum | pendiente/confirmada/cancelada/completada |
| precio_total | decimal(10,2) | Coste total reserva |
| punto_recogida | varchar(255) | Punto de recogida |
| punto_bajada | varchar(255) | Punto de bajada |
| notas | text | Notas del pasajero |
| fecha_reserva | datetime | Fecha de solicitud |
| fecha_confirmacion | datetime | Fecha confirmacion |

**Indices:** viaje_id, pasajero_id, estado

### wp_flavor_carpooling_rutas_recurrentes
Rutas programadas para generar viajes automaticamente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| conductor_id | bigint(20) UNSIGNED | FK usuario |
| nombre | varchar(255) | Nombre de la ruta |
| origen | varchar(255) | Direccion origen |
| destino | varchar(255) | Direccion destino |
| dias_semana | varchar(50) | JSON con dias (1=Lunes...7=Domingo) |
| hora_salida | time | Hora de salida |
| plazas_disponibles | int(11) | Plazas ofertadas |
| precio_por_plaza | decimal(10,2) | Precio |
| activa | tinyint(1) | Ruta activa |
| created_at | datetime | Fecha creacion |

**Indices:** conductor_id

### wp_flavor_carpooling_valoraciones
Sistema de reputacion para conductores y pasajeros.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| viaje_id | bigint(20) UNSIGNED | FK viaje |
| valorador_id | bigint(20) UNSIGNED | FK usuario que valora |
| valorado_id | bigint(20) UNSIGNED | FK usuario valorado |
| tipo_valoracion | enum | conductor/pasajero |
| puntuacion | int(11) | Puntuacion 1-5 |
| comentario | text | Comentario |
| fecha_valoracion | datetime | Fecha valoracion |

**Indices:** viaje_id, valorador_id, valorado_id

### wp_flavor_carpooling_vehiculos
Vehiculos registrados por conductores.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| propietario_id | bigint(20) UNSIGNED | FK usuario |
| marca | varchar(100) | Marca vehiculo |
| modelo | varchar(100) | Modelo |
| color | varchar(50) | Color |
| matricula | varchar(20) UNIQUE | Matricula |
| ano | int(11) | Ano fabricacion |
| plazas_totales | int(11) | Plazas disponibles |
| foto_url | varchar(500) | URL imagen vehiculo |
| activo | tinyint(1) | Vehiculo activo |
| created_at | datetime | Fecha registro |

**Indices:** propietario_id, matricula (UNIQUE)

## Shortcodes

### Busqueda y Listados

```php
[carpooling_buscar_viaje]
// Formulario de busqueda de viajes
// - mostrar_mapa: true|false (default: true)
// - radio_defecto: km (default: 5)

[carpooling_viajes]
// Listado de viajes disponibles
// - limite: numero (default: 12)
// - mostrar_filtros: si|no
// - origen: filtrar por origen
// - destino: filtrar por destino
// - esquema_color: default|custom (VBP)
// - estilo_tarjeta: elevated|flat|outlined

[carpooling_buscar]
// Buscador avanzado con filtros
// Soporta parametros GET: origen, destino, fecha, plazas

[carpooling_busqueda_rapida]
// Formulario compacto de busqueda rapida
```

### Gestion de Viajes

```php
[carpooling_publicar_viaje]
// Formulario para publicar nuevo viaje
// - mostrar_vehiculos: true|false
// - permite_recurrente: true|false
// Requiere login

[carpooling_mis_viajes]
// Lista de viajes publicados como conductor
// Requiere login

[carpooling_mis_reservas]
// Lista de reservas como pasajero
// Requiere login
```

### Widgets

```php
[carpooling_proximo_viaje]
// Widget con el proximo viaje del usuario
// (como conductor o pasajero)
// Requiere login
```

## Dashboard Tab

**Clase:** `Flavor_Carpooling_Dashboard_Tab`

**Tabs disponibles:**
- `carpooling-mis-viajes` - Viajes como conductor
- `carpooling-mis-reservas` - Reservas como pasajero
- `carpooling-estadisticas` - Impacto ambiental y estadisticas

**Funcionalidades del dashboard:**
- Ver y gestionar viajes publicados
- Confirmar o rechazar reservas
- Cancelar viajes con notificacion a pasajeros
- Ver historial de reservas
- Valorar conductores/pasajeros tras viaje completado
- Estadisticas de CO2 ahorrado y km compartidos

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/carpooling/` | index | Buscar viajes |
| `/mi-portal/carpooling/buscar/` | buscar | Busqueda avanzada |
| `/mi-portal/carpooling/publicar/` | publicar | Publicar viaje |
| `/mi-portal/carpooling/mis-viajes/` | mis-viajes | Viajes del conductor |
| `/mi-portal/carpooling/mis-reservas/` | mis-reservas | Reservas del pasajero |
| `/mi-portal/carpooling/viaje/{id}/` | ver | Detalle viaje |
| `/mi-portal/carpooling/viaje/{id}/reservar/` | reservar | Reservar plaza |

## REST API

### Endpoints Publicos

```
GET  /carpooling/v1/viajes
     ?origen_lat=&origen_lng=&destino_lat=&destino_lng=&fecha=&radio=&plazas=
     Buscar viajes disponibles

GET  /carpooling/v1/viajes/{id}
     Obtener detalle de viaje

GET  /carpooling/v1/usuario/{id}/valoraciones
     ?tipo=conductor|pasajero
     Obtener valoraciones de usuario
```

### Endpoints Autenticados

```
POST /carpooling/v1/viajes
     Publicar nuevo viaje

POST /carpooling/v1/reservas
     Crear reserva de plaza
```

## AJAX Handlers

### Busqueda y Viajes
- `carpooling_buscar_viajes` - Buscar viajes (publico)
- `carpooling_publicar_viaje` - Publicar viaje
- `carpooling_detalle_viaje` - Obtener detalle (publico)
- `carpooling_cancelar_viaje` - Cancelar viaje como conductor
- `carpooling_mis_viajes` - Mis viajes como conductor
- `carpooling_autocompletar_lugar` - Autocompletado de lugares (Nominatim)

### Reservas
- `carpooling_reservar_plaza` - Reservar plaza
- `carpooling_cancelar_reserva` - Cancelar reserva
- `carpooling_confirmar_reserva` - Confirmar/rechazar reserva
- `carpooling_mis_reservas` - Mis reservas como pasajero

### Valoraciones y Vehiculos
- `carpooling_valorar_viaje` - Valorar conductor o pasajero
- `carpooling_guardar_vehiculo` - Agregar/editar vehiculo
- `carpooling_obtener_vehiculos` - Listar vehiculos del usuario
- `carpooling_crear_ruta_recurrente` - Crear ruta recurrente
- `carpooling_contactar_conductor` - Enviar mensaje al conductor

## Hooks y Filtros

### Actions

```php
// Nueva reserva solicitada
do_action('flavor_carpooling_reserva_solicitada', $reserva_id, $viaje_id, $pasajero_id);

// Reserva confirmada
do_action('flavor_carpooling_reserva_confirmada', $reserva_id, $viaje_id);

// Reserva cancelada
do_action('flavor_carpooling_reserva_cancelada', $reserva_id, $motivo, $cancelado_por);

// Viaje publicado
do_action('flavor_carpooling_viaje_publicado', $viaje_id, $conductor_id);

// Viaje cancelado
do_action('flavor_carpooling_viaje_cancelado', $viaje_id, $motivo);

// Viaje completado
do_action('flavor_carpooling_viaje_completado', $viaje_id);

// Nueva valoracion
do_action('flavor_carpooling_valoracion', $valoracion_id, $valorado_id, $puntuacion);
```

### Filters

```php
// Precio final del viaje
apply_filters('flavor_carpooling_precio', $precio, $viaje_id, $pasajero_id);

// Radio de busqueda
apply_filters('flavor_carpooling_radio_busqueda', $radio_km, $usuario_id);

// Maximo pasajeros por viaje
apply_filters('flavor_carpooling_max_pasajeros', $max, $vehiculo_id);

// Validar reserva antes de confirmar
apply_filters('flavor_carpooling_validar_reserva', $valido, $reserva_data, $viaje_id);

// Dias de anticipacion para cancelar
apply_filters('flavor_carpooling_tiempo_cancelacion', $horas, $viaje_id);
```

## Configuracion

```php
'carpooling' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'requiere_verificacion_conductor' => true,
    'permite_valoraciones' => true,
    'dias_anticipacion_maxima' => 30,
    'max_pasajeros_por_viaje' => 4,
    'permite_mascotas' => true,
    'permite_equipaje_grande' => true,
    'radio_busqueda_km' => 5,
    'calculo_coste_automatico' => true,
    'precio_por_km' => 0.15,
    'comision_plataforma_porcentaje' => 0,
    'notificaciones_email' => true,
    'tiempo_minimo_cancelacion_horas' => 24,
    'puntuacion_minima_conductor' => 3.0,
    'max_cancelaciones_mes' => 3,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `carpooling_ver` | Ver viajes publicos |
| `carpooling_buscar` | Buscar viajes |
| `carpooling_reservar` | Reservar plazas |
| `carpooling_publicar` | Publicar viajes |
| `carpooling_gestionar` | Administrar todos los viajes |
| `carpooling_valorar` | Valorar conductores/pasajeros |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Carpooling comunitario |
| socios | Descuento | Precio especial socios |
| eventos | Integracion | Compartir viaje a eventos |
| network | Federacion | Sincronizar viajes entre nodos |

## Integraciones

### Geolocalizacion (Nominatim/OpenStreetMap)
```php
// Autocompletado de lugares gratuito
// Usa Nominatim de OpenStreetMap
carpooling_autocompletar_lugar($termino);
```

### Calculo de Distancia (Haversine)
```php
// Calcula distancia entre coordenadas
$distancia_km = $this->calcular_distancia_km($lat1, $lng1, $lat2, $lng2);
```

### Red Federada
```php
// Sincroniza viajes con tabla de red
$this->sincronizar_viaje_con_red($viaje_id);

// Elimina viaje de la red
$this->eliminar_viaje_de_red($viaje_id);

// Sincroniza todos los viajes activos
$this->sincronizar_todos_viajes_con_red();
```

### Mapas (Leaflet)
El modulo utiliza Leaflet para visualizacion de mapas en el dashboard y formularios de busqueda.

## Cron Jobs

```php
// Genera viajes automaticos para rutas recurrentes
// Se ejecuta diariamente
add_action('carpooling_generar_viajes_recurrentes', [$this, 'generar_viajes_recurrentes']);
wp_schedule_event(time(), 'daily', 'carpooling_generar_viajes_recurrentes');
```

## Notificaciones por Email

El modulo envia notificaciones automaticas para:
- Nueva solicitud de reserva (al conductor)
- Reserva confirmada (al pasajero)
- Reserva rechazada (al pasajero)
- Viaje cancelado por conductor (a todos los pasajeros)
- Cancelacion de reserva (al conductor)

## Principios Gailu

El modulo implementa los siguientes principios:
- **economia_local**: Fomenta la economia colaborativa y reduce costes de transporte
- **regeneracion**: Reduce emisiones de CO2 al compartir vehiculos

Contribuye a:
- **autonomia**: Los usuarios gestionan sus propios viajes
- **impacto**: Estadisticas de ahorro ambiental visible

## Ejemplos de Uso

### Publicar un viaje

```php
// Via AJAX o REST API
$datos_viaje = [
    'origen' => 'Bilbao',
    'origen_lat' => 43.2630,
    'origen_lng' => -2.9350,
    'destino' => 'San Sebastian',
    'destino_lat' => 43.3183,
    'destino_lng' => -1.9812,
    'fecha_hora' => '2024-03-20 08:00:00',
    'plazas' => 3,
    'precio' => 5.00,
    'permite_mascotas' => true,
];

$resultado = $modulo->action_publicar_viaje($datos_viaje);
```

### Buscar viajes cercanos

```php
$viajes = $modulo->action_buscar_viajes([
    'origen_lat' => 43.2630,
    'origen_lng' => -2.9350,
    'destino_lat' => 43.3183,
    'destino_lng' => -1.9812,
    'fecha' => '2024-03-20',
    'radio_km' => 5,
    'plazas' => 2,
]);
```

### Crear ruta recurrente

```php
$ruta = $modulo->action_crear_ruta_recurrente([
    'nombre' => 'Trabajo Bilbao-Donostia',
    'origen' => 'Bilbao Centro',
    'origen_lat' => 43.2630,
    'origen_lng' => -2.9350,
    'destino' => 'Donostia Centro',
    'destino_lat' => 43.3183,
    'destino_lng' => -1.9812,
    'hora' => '07:30',
    'dias' => [1, 2, 3, 4, 5], // Lunes a viernes
    'plazas' => 3,
    'precio' => 5.00,
]);
```
