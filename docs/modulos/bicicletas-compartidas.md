# Modulo: Bicicletas Compartidas

> Sistema de bici-sharing comunitario para movilidad sostenible

## Descripcion

Sistema completo de bicicletas compartidas gestionado por la comunidad. Permite a los usuarios localizar estaciones, reservar bicicletas, realizar prestamos y devoluciones en cualquier estacion. Incluye gestion de flota con diferentes tipos de bicicletas, sistema de fianza configurable, estadisticas de uso e impacto ambiental (CO2 ahorrado, calorias quemadas), gestion de mantenimiento y visualizacion en mapa interactivo con Leaflet.

## Archivos Principales

```
includes/modules/bicicletas-compartidas/
├── class-bicicletas-compartidas-module.php      # Clase principal del modulo
├── class-bicicletas-compartidas-api.php         # API REST para aplicaciones moviles
├── class-bicicletas-dashboard-tab.php           # Tabs del dashboard de usuario
├── class-bicicletas-dashboard-widget.php        # Widget para dashboard admin
├── frontend/
│   └── class-bicicletas-compartidas-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                            # Dashboard admin completo
│   ├── bicicletas.php                           # Gestion de flota
│   ├── estaciones.php                           # Gestion de estaciones
│   ├── mantenimiento.php                        # Gestion de mantenimiento
│   ├── uso.php                                  # Reportes de uso
│   └── mapa.php                                 # Vista de mapa
└── assets/
    ├── css/
    │   └── bicicletas-frontend.css              # Estilos frontend
    └── js/
        └── bicicletas-frontend.js               # JavaScript frontend
```

## Tablas de Base de Datos

### wp_flavor_bicicletas
Bicicletas registradas en el sistema.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| codigo | varchar(50) UNIQUE | Codigo identificador de la bicicleta |
| tipo | varchar(20) | urbana/montana/electrica/infantil/carga |
| marca | varchar(100) | Marca del fabricante |
| modelo | varchar(100) | Modelo |
| color | varchar(50) | Color |
| talla | varchar(5) | Talla (XS/S/M/L/XL) |
| estacion_actual_id | bigint(20) UNSIGNED | FK estacion donde se encuentra |
| estado | varchar(20) | disponible/en_uso/mantenimiento/reservada |
| kilometros_acumulados | int(11) | Km totales recorridos |
| ultima_revision | datetime | Fecha ultima revision |
| proximo_mantenimiento_km | int(11) | Km para proximo mantenimiento |
| foto_url | varchar(500) | URL imagen de la bicicleta |
| equipamiento | text | JSON con equipamiento incluido |
| fecha_alta | datetime | Fecha de registro |

**Indices:** codigo (UNIQUE), estacion_actual_id, estado, tipo

### wp_flavor_bicicletas_prestamos
Historial de prestamos de bicicletas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| bicicleta_id | bigint(20) UNSIGNED | FK bicicleta |
| usuario_id | bigint(20) UNSIGNED | FK usuario |
| estacion_salida_id | bigint(20) UNSIGNED | FK estacion de recogida |
| estacion_llegada_id | bigint(20) UNSIGNED | FK estacion de devolucion |
| fecha_inicio | datetime | Fecha/hora inicio prestamo |
| fecha_fin | datetime | Fecha/hora fin prestamo |
| duracion_minutos | int(11) | Duracion en minutos |
| kilometros_recorridos | decimal(10,2) | Km recorridos |
| coste_total | decimal(10,2) | Coste del prestamo |
| fianza | decimal(10,2) | Importe fianza |
| fianza_devuelta | tinyint(1) | Fianza devuelta (0/1) |
| incidencias | text | Problemas reportados |
| valoracion | int(11) | Valoracion del servicio (1-5) |
| estado | varchar(20) | activo/finalizado |
| fecha_creacion | datetime | Fecha creacion registro |

**Indices:** bicicleta_id, usuario_id, estado, fecha_inicio

### wp_flavor_bicicletas_estaciones
Estaciones/puntos de recogida y devolucion.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| nombre | varchar(255) | Nombre de la estacion |
| direccion | varchar(500) | Direccion completa |
| latitud | decimal(10,7) | Coordenada latitud |
| longitud | decimal(10,7) | Coordenada longitud |
| capacidad_total | int(11) | Numero maximo de bicicletas |
| bicicletas_disponibles | int(11) | Bicicletas disponibles actualmente |
| tipo | varchar(20) | publica/privada/mixta |
| horario_apertura | time | Hora de apertura |
| horario_cierre | time | Hora de cierre |
| servicios | text | JSON con servicios (herramientas, bomba, etc) |
| foto_url | varchar(500) | URL imagen de la estacion |
| estado | varchar(20) | activa/inactiva/mantenimiento |
| fecha_creacion | datetime | Fecha creacion |

**Indices:** latitud, estado

### wp_flavor_bicicletas_mantenimiento
Registro de mantenimientos y reparaciones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) UNSIGNED | ID unico |
| bicicleta_id | bigint(20) UNSIGNED | FK bicicleta |
| tipo | varchar(20) | revision/reparacion/preventivo |
| descripcion | text | Descripcion del trabajo |
| reportado_por | bigint(20) UNSIGNED | FK usuario que reporto |
| tecnico_asignado | bigint(20) UNSIGNED | FK tecnico encargado |
| fecha_reporte | datetime | Fecha del reporte |
| fecha_inicio | datetime | Inicio del trabajo |
| fecha_fin | datetime | Fin del trabajo |
| coste | decimal(10,2) | Coste de la reparacion |
| piezas_cambiadas | text | JSON con piezas reemplazadas |
| estado | varchar(20) | pendiente/en_proceso/completado |

**Indices:** bicicleta_id, estado, fecha_reporte

## Shortcodes

### Mapas y Estaciones

```php
[bicicletas_mapa]
// Mapa interactivo con estaciones y disponibilidad
// - altura: px (default: 500)
// - zoom: nivel zoom (default: 14)
// - lat: latitud centro (auto si no se especifica)
// - lng: longitud centro (auto si no se especifica)
// - mostrar_leyenda: yes|no (default: yes)

[bicicletas_estaciones]
// Lista de estaciones con disponibilidad
// - limite: numero (default: 12)
// - columnas: 1-4 (default: 3)
// - ordenar: nombre|disponibles|distancia
// - mostrar_vacia: yes|no (default: no)
```

### Reservas y Prestamos

```php
[bicicletas_reservar]
// Formulario para reservar bicicleta
// - estacion_id: ID estacion (opcional, tambien via GET)
// Requiere login

[bicicletas_mis_viajes]
// Historial de viajes del usuario
// - limite: numero (default: 20)
// - mostrar_activo: yes|no (default: yes)
// Requiere login
```

### Estadisticas y Tarifas

```php
[bicicletas_estadisticas]
// Estadisticas personales del usuario
// - Viajes totales, km recorridos, CO2 ahorrado
// - Calorias quemadas, dinero ahorrado
// Requiere login

[bicicletas_tarifas]
// Muestra planes y tarifas disponibles
// - Precio por hora, dia y mes
// - Informacion de fianza
// - Condiciones del servicio
```

### Shortcodes adicionales (Frontend Controller)

```php
[flavor_bicicletas_mapa]
[flavor_bicicletas_estaciones]
[flavor_bicicletas_disponibles]
[flavor_bicicletas_detalle]
[flavor_bicicletas_reservar]
[flavor_bicicletas_mis_prestamos]
[flavor_bicicletas_prestamo_activo]
[flavor_bicicletas_estadisticas]
```

## Dashboard Tab

**Clase:** `Flavor_Bicicletas_Dashboard_Tab`

**Tabs disponibles:**
- `bicicletas-mis-viajes` - Historial de viajes
- `bicicletas-mi-cuenta` - Saldo, plan y preferencias
- `bicicletas-estadisticas` - Impacto ambiental y estadisticas

**Funcionalidades del dashboard:**
- Ver prestamo activo con tiempo transcurrido
- Historial completo de viajes con paginacion
- Estadisticas de km, CO2 ahorrado, calorias
- Gestion de saldo y suscripciones
- Valorar viajes completados

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/bicicletas/` | index | Mapa y estaciones |
| `/mi-portal/bicicletas/estaciones/` | estaciones | Lista de estaciones |
| `/mi-portal/bicicletas/reservar/` | reservar | Reservar bicicleta |
| `/mi-portal/bicicletas/mis-viajes/` | mis-viajes | Historial de viajes |
| `/mi-portal/bicicletas/estadisticas/` | estadisticas | Estadisticas personales |
| `/mi-portal/bicicletas/tarifas/` | tarifas | Planes y precios |

## REST API

### Endpoints Publicos

```
GET  /flavor/v1/bicicletas
     ?estacion_id=&tipo=&estado=
     Listar bicicletas (default: disponibles)

GET  /flavor/v1/bicicletas/estaciones
     ?lat=&lng=&radio_km=
     Listar estaciones (con distancia si hay coordenadas)

GET  /flavor/v1/bicicletas/{id}
     Obtener detalle de bicicleta
```

### Endpoints Autenticados

```
POST /flavor/v1/bicicletas/{id}/reservar
     Iniciar prestamo de bicicleta

POST /flavor/v1/bicicletas/{id}/devolver
     ?estacion_id=&kilometros=&incidencias=&valoracion=
     Finalizar prestamo

GET  /flavor/v1/bicicletas/mis-reservas
     ?estado=activo|finalizado|todos&limite=
     Obtener prestamos del usuario
```

### API Movil Adicional (class-bicicletas-compartidas-api.php)

```
GET  /flavor-chat-ia/v1/bicicletas-compartidas
     Vista completa: estaciones, alquiler activo, historial

POST /flavor-chat-ia/v1/bicicletas-compartidas/alquilar
     ?estacion_id=
     Alquilar bicicleta

POST /flavor-chat-ia/v1/bicicletas-compartidas/finalizar
     ?estacion_id=
     Finalizar alquiler
```

## AJAX Handlers

### Frontend Controller
- `flavor_bicicletas_reservar` - Reservar bicicleta
- `flavor_bicicletas_devolver` - Devolver bicicleta
- `flavor_bicicletas_reportar_problema` - Reportar incidencia
- `flavor_bicicletas_cancelar_reserva` - Cancelar reserva
- `flavor_bicicletas_valorar` - Valorar prestamo
- `flavor_bicicletas_buscar_estaciones` - Buscar estaciones cercanas

### Modulo Principal
- `bicicletas_reservar` - Reservar via shortcode
- `flavor_bicicletas_cargar_mas_viajes` - Cargar mas viajes (paginacion)

## Hooks y Filtros

### Actions

```php
// Prestamo iniciado
do_action('flavor_bicicletas_prestamo_iniciado', $prestamo_id, $bicicleta_id, $usuario_id);

// Prestamo finalizado
do_action('flavor_bicicletas_prestamo_finalizado', $prestamo_id, $datos_resumen);

// Incidencia reportada
do_action('flavor_bicicletas_incidencia_reportada', $bicicleta_id, $descripcion, $usuario_id);

// Bicicleta enviada a mantenimiento
do_action('flavor_bicicletas_mantenimiento_iniciado', $bicicleta_id, $mantenimiento_id);
```

### Filters

```php
// Calcular coste del prestamo
apply_filters('flavor_bicicletas_calcular_coste', $coste, $minutos, $usuario_id);

// Validar reserva
apply_filters('flavor_bicicletas_validar_reserva', $valido, $bicicleta_id, $usuario_id);

// Fianza requerida
apply_filters('flavor_bicicletas_fianza_requerida', $importe, $usuario_id);

// Duracion maxima permitida
apply_filters('flavor_bicicletas_duracion_maxima', $dias, $tipo_bicicleta);
```

## Configuracion

```php
'bicicletas_compartidas' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'requiere_fianza' => true,
    'importe_fianza' => 50,
    'precio_hora' => 0,
    'precio_dia' => 0,
    'precio_mes' => 10,
    'duracion_maxima_prestamo_dias' => 7,
    'permite_reservas' => true,
    'horas_anticipacion_reserva' => 2,
    'requiere_verificacion_usuario' => true,
    'notificar_mantenimiento' => true,
    'permite_reportar_problemas' => true,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `bicicletas_ver` | Ver estaciones y bicicletas |
| `bicicletas_reservar` | Reservar/prestar bicicletas |
| `bicicletas_reportar` | Reportar problemas |
| `bicicletas_gestionar` | Administrar flota y estaciones |
| `bicicletas_mantenimiento` | Gestionar mantenimiento |

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Flota de bicicletas comunitaria |
| socios | Descuento | Tarifas especiales para socios |
| eventos | Integracion | Sugerir bicicleta para ir a eventos |
| huella-ecologica | Datos | Aportar km en bici al calculo |

## Integraciones

### Geolocalizacion (Navegador)
```php
// Obtener ubicacion del usuario para estaciones cercanas
navigator.geolocation.getCurrentPosition(function(pos) {
    // Centrar mapa y buscar estaciones
});
```

### Calculo de Distancia (Haversine)
```php
// SQL para calcular distancia a estaciones
(6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
```

### Mapas (Leaflet)
El modulo utiliza Leaflet para visualizacion de mapas. Muestra:
- Marcadores de estaciones con color segun disponibilidad
- Pop-ups con informacion de bicicletas disponibles
- Geolocalizacion del usuario
- Leyenda de disponibilidad

## Estadisticas e Impacto Ambiental

El modulo calcula automaticamente:
- **CO2 ahorrado:** 120g por km vs coche
- **Calorias quemadas:** 25 por km
- **Dinero ahorrado:** 0.15 EUR por km vs transporte publico
- **Arboles equivalentes:** CO2 ahorrado / 22kg (absorcion anual por arbol)

## Principios Gailu

El modulo implementa los siguientes principios:
- **regeneracion**: Reduce emisiones de CO2 y promueve movilidad sostenible
- **economia_local**: Servicio gestionado por la comunidad

Contribuye a:
- **impacto**: Estadisticas visibles de ahorro ambiental
- **autonomia**: Los usuarios gestionan sus propios viajes

## Ejemplos de Uso

### Reservar una bicicleta

```php
// Via REST API
$request = new WP_REST_Request('POST');
$request->set_param('id', $bicicleta_id);

$resultado = $modulo->api_reservar_bicicleta($request);

// Respuesta exitosa
[
    'success' => true,
    'mensaje' => 'Bicicleta BICI-001 reservada correctamente',
    'prestamo' => [
        'id' => 123,
        'bicicleta_id' => 45,
        'bicicleta_codigo' => 'BICI-001',
        'fecha_inicio' => '2024-03-20 08:00:00',
        'fianza' => 50.00,
        'estado' => 'activo'
    ]
]
```

### Buscar estaciones cercanas

```php
$estaciones = $modulo->action_estaciones([
    'lat' => 43.2630,
    'lng' => -2.9350,
    'radio_km' => 3,
]);

// Respuesta
[
    'success' => true,
    'estaciones' => [
        [
            'id' => 1,
            'nombre' => 'Plaza Mayor',
            'direccion' => 'Plaza Mayor, 1',
            'lat' => 43.2635,
            'lng' => -2.9345,
            'bicicletas_disponibles' => 8,
            'capacidad_total' => 12,
            'distancia_km' => 0.15
        ],
        // ...
    ]
]
```

### Devolver bicicleta

```php
$request = new WP_REST_Request('POST');
$request->set_param('id', $bicicleta_id);
$request->set_param('estacion_id', 5);
$request->set_param('kilometros', 12.5);
$request->set_param('valoracion', 5);

$resultado = $modulo->api_devolver_bicicleta($request);

// Respuesta
[
    'success' => true,
    'mensaje' => 'Bicicleta devuelta correctamente en Estacion Centro',
    'resumen' => [
        'prestamo_id' => 123,
        'duracion' => '45 minutos',
        'duracion_minutos' => 45,
        'kilometros' => 12.5,
        'coste' => 0,
        'fianza_devuelta' => 50.00,
        'estacion_devolucion' => 'Estacion Centro'
    ]
]
```

## Tipos de Bicicletas

| Tipo | Descripcion |
|------|-------------|
| urbana | Bicicleta estandar para ciudad |
| montana | Bicicleta de montana (MTB) |
| electrica | Bicicleta con asistencia electrica |
| infantil | Bicicleta para ninos |
| carga | Bicicleta con cesta/portaequipajes grande |

## Tarifas por Defecto

- **Primeros 30 minutos:** Gratis
- **Cada 30 minutos adicionales:** 0.50 EUR
- **Abono mensual:** 10 EUR (viajes ilimitados)
- **Fianza:** 50 EUR (reembolsable)

## Panel de Administracion

### Paginas disponibles
- **Dashboard:** Estadisticas generales y accesos rapidos
- **Flota:** Gestion de bicicletas (anadir, editar, estados)
- **Estaciones:** Gestion de estaciones (ubicacion, capacidad)
- **Prestamos Activos:** Seguimiento de bicicletas en uso
- **Configuracion:** Tarifas, fianza y opciones del modulo

### Estadisticas del Dashboard
- Total de bicicletas
- Bicicletas disponibles
- Bicicletas en uso
- Bicicletas en mantenimiento
- Estaciones activas
- Prestamos del dia
