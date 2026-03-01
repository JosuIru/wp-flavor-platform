# Módulo: Grupos de Consumo

> Gestión completa de grupos de consumo agroecológico

## Descripción

Sistema integral para la gestión de grupos de consumo que incluye catálogo de productos, ciclos de pedido, gestión de productores, carrito de compra, sistema de pagos y seguimiento de impacto ecológico.

## Archivos Principales

```
includes/modules/grupos-consumo/
├── class-grupos-consumo-module.php      # Clase principal
├── class-gc-dashboard-tab.php           # Tab del dashboard
├── class-gc-dashboard-widget.php        # Widget resumen
├── class-gc-membership.php              # Gestión membresías
├── class-gc-notification-channels.php   # Notificaciones
├── install.php                          # Instalación BD
├── frontend/
│   ├── class-gc-frontend-controller.php # Controlador frontend
│   └── class-gc-ajax-handlers.php       # Handlers AJAX
├── views/
│   ├── dashboard.php
│   ├── catalogo.php
│   ├── carrito.php
│   └── ...
├── templates/
│   ├── carrito-completo.php
│   └── carrito-flotante.php
└── assets/
    ├── css/
    └── js/gc-frontend.js
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Grupo | `gc_grupo` | Grupos de consumo |
| Producto | `gc_producto` | Productos del catálogo |
| Pedido | `gc_pedido` | Pedidos de usuarios |
| Ciclo | `gc_ciclo` | Ciclos de pedido |
| Productor | `gc_productor` | Productores/proveedores |

## Taxonomías

| Taxonomía | Slug | Aplicada a |
|-----------|------|-----------|
| Categoría Producto | `categoria_producto` | gc_producto |
| Tipo Producto | `tipo_producto` | gc_producto |
| Productor | `productor` | gc_producto |
| Certificación | `certificacion` | gc_producto, gc_productor |

## Tablas de Base de Datos

### wp_flavor_gc_pedidos
Pedidos de productos por usuario y ciclo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| ciclo_id | bigint(20) | FK ciclo |
| usuario_id | bigint(20) | FK usuario |
| producto_id | bigint(20) | FK producto |
| cantidad | decimal(10,2) | Cantidad pedida |
| precio_unitario | decimal(10,2) | Precio al momento |
| subtotal | decimal(10,2) | Subtotal línea |
| estado | enum | pendiente/confirmado/cancelado |
| notas | text | Notas del pedido |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** ciclo_id, usuario_id, producto_id, estado

### wp_flavor_gc_entregas
Consolidado de entregas por usuario y ciclo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| ciclo_id | bigint(20) | FK ciclo |
| usuario_id | bigint(20) | FK usuario |
| total_pedido | decimal(10,2) | Total productos |
| gastos_gestion | decimal(10,2) | Gastos añadidos |
| total_final | decimal(10,2) | Total a pagar |
| estado_pago | enum | pendiente/pagado/parcial |
| fecha_pago | datetime | Fecha de pago |
| metodo_pago | varchar(50) | Método usado |
| estado_recogida | enum | pendiente/recogido/incidencia |
| fecha_recogida | datetime | Fecha recogida |
| recogido_por | bigint(20) | Usuario que recogió |
| notas | text | Observaciones |

### wp_flavor_gc_consumidores
Miembros de grupos de consumo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) | FK usuario WP |
| grupo_id | bigint(20) | FK grupo |
| rol | enum | miembro/coordinador/admin |
| estado | enum | activo/pendiente/suspendido/baja |
| fecha_alta | datetime | Fecha incorporación |
| fecha_baja | datetime | Fecha baja si aplica |
| preferencias_alimentarias | text | Preferencias |
| alergias | text | Alergias/intolerancias |
| saldo_pendiente | decimal(10,2) | Saldo cuenta |
| notas | text | Observaciones |

**Índices:** usuario_id, grupo_id, estado

### wp_flavor_gc_suscripciones
Suscripciones recurrentes a cestas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) | FK usuario |
| cesta_tipo_id | bigint(20) | FK tipo cesta |
| frecuencia | enum | semanal/quincenal/mensual |
| estado | enum | activa/pausada/cancelada |
| fecha_inicio | date | Inicio suscripción |
| fecha_fin | date | Fin si temporal |
| fecha_proximo_cargo | date | Próximo cargo |
| metodo_pago | varchar(50) | Método preferido |
| notas_entrega | text | Instrucciones |

### wp_flavor_gc_cestas_tipo
Tipos de cestas predefinidas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(200) | Nombre cesta |
| descripcion | text | Descripción |
| precio | decimal(10,2) | Precio base |
| productos_incluidos | longtext JSON | Productos fijos |
| productos_opcionales | longtext JSON | Opciones extra |
| imagen | varchar(500) | URL imagen |
| activa | tinyint(1) | Está activa |

### wp_flavor_gc_pagos
Sistema de pagos y transacciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) | FK usuario |
| entrega_id | bigint(20) | FK entrega |
| concepto | varchar(255) | Concepto pago |
| importe | decimal(10,2) | Cantidad |
| metodo | enum | efectivo/transferencia/tarjeta/bizum |
| estado | enum | pendiente/completado/fallido/reembolsado |
| referencia | varchar(100) | Referencia externa |
| fecha_pago | datetime | Fecha efectiva |
| comprobante_url | varchar(500) | URL comprobante |

### wp_flavor_gc_excedentes
Gestión de productos sobrantes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| ciclo_id | bigint(20) | FK ciclo |
| producto_id | bigint(20) | FK producto |
| cantidad_sobrante | decimal(10,2) | Cantidad sin reclamar |
| cantidad_reclamada | decimal(10,2) | Reclamada por usuarios |
| cantidad_donada | decimal(10,2) | Donada |
| estado | enum | disponible/agotado/donado |
| destino_donacion | varchar(255) | Organización destino |
| precio_solidario | decimal(10,2) | Precio reducido |

### wp_flavor_gc_huella_ciclo
Impacto ecológico por ciclo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| ciclo_id | bigint(20) UNIQUE | FK ciclo |
| km_evitados | decimal(10,2) | Km transporte evitados |
| co2_evitado | decimal(10,2) | Kg CO2 no emitidos |
| plastico_evitado | decimal(10,2) | Kg plástico evitado |
| agua_ahorrada | decimal(10,2) | Litros agua |
| productores_locales | int | Nº productores locales |
| productos_eco_porcentaje | decimal(5,2) | % productos eco |
| puntuacion_sostenibilidad | tinyint | Score 0-100 |

### wp_flavor_gc_precio_desglose
Transparencia en precios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| producto_id | bigint(20) | FK producto |
| ciclo_id | bigint(20) | FK ciclo |
| precio_productor | decimal(10,2) | Precio al productor |
| costes_transporte | decimal(10,2) | Coste transporte |
| costes_gestion | decimal(10,2) | Coste gestión |
| costes_mermas | decimal(10,2) | Mermas estimadas |
| fondo_social | decimal(10,2) | Aporte fondo social |
| iva | decimal(10,2) | IVA aplicado |
| precio_final | decimal(10,2) | PVP final |
| origen_km | int | Km desde origen |
| certificaciones | varchar(255) | Certificaciones |

### wp_flavor_gc_trueque
Sistema de intercambio entre miembros.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_ofrece_id | bigint(20) | Quien ofrece |
| producto_ofrece | varchar(255) | Qué ofrece |
| cantidad_ofrece | decimal(10,2) | Cantidad |
| busca | text | Qué busca a cambio |
| estado | enum | abierto/en_negociacion/completado/cancelado |
| usuario_intercambia_id | bigint(20) | Con quién intercambia |
| fecha_intercambio | datetime | Fecha realización |

## Shortcodes

### Catálogo y Productos

```php
[gc_catalogo]
// Parámetros:
// - categoria: slug categoría (opcional)
// - productor: ID productor (opcional)
// - orden: nombre|precio|nuevo (default: nombre)
// - limite: número (default: 20)
// - mostrar_agotados: true|false (default: false)

[gc_productos]
// Alias de gc_catalogo con vista grid

[gc_productores]
// Lista de productores
// - mostrar_productos: true|false
// - con_mapa: true|false

[gc_productores_cercanos]
// Productores por proximidad
// - radio: km (default: 50)
// - limite: número
```

### Carrito y Pedidos

```php
[gc_carrito]
// Carrito de compra completo
// - mostrar_resumen: true|false
// - mostrar_huella: true|false

[gc_mi_cesta]
// Vista reducida del carrito

[gc_mi_pedido]
// Pedido del ciclo actual

[gc_mis_pedidos]
// Historial de pedidos
// - limite: número
// - estado: todos|pendiente|completado
```

### Ciclos y Grupos

```php
[gc_ciclo_actual]
// Información del ciclo activo
// - mostrar_countdown: true|false
// - mostrar_progreso: true|false

[gc_ciclos]
// Lista de ciclos
// - estado: activo|cerrado|todos
// - limite: número

[gc_grupos_lista]
// Directorio de grupos
// - zona: código zona
// - abiertos: true|false (solo abiertos a nuevos)
```

### Panel y Navegación

```php
[gc_panel]
// Panel completo del usuario

[gc_nav]
// Navegación del módulo

[gc_suscripciones]
// Gestión de suscripciones

[gc_historial]
// Historial completo
// - tipo: pedidos|pagos|todo
// - desde: fecha
// - hasta: fecha

[gc_calendario]
// Calendario de ciclos y entregas
```

## Dashboard Tab

**Clase:** `Flavor_GC_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Panel principal con resumen
- `catalogo` - Catálogo de productos
- `carrito` - Carrito actual
- `mis-pedidos` - Historial pedidos
- `mi-grupo` - Gestión del grupo
- `productores` - Directorio productores
- `suscripciones` - Mis suscripciones
- `estadisticas` - Métricas personales
- `configuracion` - Preferencias

## Widget Dashboard

**Clase:** `Flavor_GC_Dashboard_Widget`

Muestra:
- Estado del ciclo actual
- Resumen del carrito
- Próxima fecha de entrega
- Saldo pendiente
- Acceso rápido al catálogo

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/grupos-consumo/` | index | Dashboard GC |
| `/mi-portal/grupos-consumo/catalogo/` | catalogo | Catálogo productos |
| `/mi-portal/grupos-consumo/carrito/` | carrito | Carrito compra |
| `/mi-portal/grupos-consumo/mis-pedidos/` | mis-pedidos | Historial |
| `/mi-portal/grupos-consumo/ciclo-actual/` | ciclo-actual | Ciclo activo |
| `/mi-portal/grupos-consumo/productores/` | productores | Directorio |
| `/mi-portal/grupos-consumo/suscripciones/` | suscripciones | Suscripciones |
| `/mi-portal/grupos-consumo/mi-grupo/` | mi-grupo | Gestión grupo |
| `/mi-portal/grupos-consumo/mi-grupo/turnos/` | turnos | Voluntariado |
| `/mi-portal/grupos-consumo/crear-grupo/` | crear-grupo | Nuevo grupo |
| `/mi-portal/grupos-consumo/unirse/` | unirse | Solicitud unión |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| comunidades | Integración | Grupos vinculados a comunidades |
| huella-ecologica | Datos | Aporta datos de impacto |
| socios | Membresía | Descuentos para socios |
| economia-don | Complemento | Intercambio de excedentes |
| recetas | Contenido | Recetas con productos |

## Hooks y Filtros

### Actions

```php
// Cuando se añade producto al carrito
do_action('flavor_gc_producto_añadido', $producto_id, $cantidad, $usuario_id);

// Cuando se confirma un pedido
do_action('flavor_gc_pedido_confirmado', $pedido_id, $ciclo_id, $usuario_id);

// Cuando se cierra un ciclo
do_action('flavor_gc_ciclo_cerrado', $ciclo_id, $stats);

// Cuando se completa una entrega
do_action('flavor_gc_entrega_completada', $entrega_id, $usuario_id);
```

### Filters

```php
// Modificar precio de producto
apply_filters('flavor_gc_precio_producto', $precio, $producto_id, $usuario_id);

// Modificar gastos de gestión
apply_filters('flavor_gc_gastos_gestion', $gastos, $total_pedido, $grupo_id);

// Validar cantidad máxima
apply_filters('flavor_gc_cantidad_maxima', $max, $producto_id, $stock);
```

## API REST

### Endpoints

```
GET  /wp-json/flavor/v1/gc/productos
GET  /wp-json/flavor/v1/gc/productos/{id}
GET  /wp-json/flavor/v1/gc/ciclo-actual
POST /wp-json/flavor/v1/gc/carrito/añadir
PUT  /wp-json/flavor/v1/gc/carrito/actualizar
DELETE /wp-json/flavor/v1/gc/carrito/eliminar/{id}
GET  /wp-json/flavor/v1/gc/mis-pedidos
POST /wp-json/flavor/v1/gc/pedido/confirmar
```

## Configuración

### Opciones del Módulo

```php
// En flavor_chat_ia_settings
'grupos_consumo' => [
    'enabled' => true,
    'gastos_gestion_porcentaje' => 5,
    'minimo_pedido' => 15.00,
    'dias_antelacion_cierre' => 2,
    'permitir_excedentes' => true,
    'mostrar_huella' => true,
    'metodos_pago' => ['efectivo', 'transferencia', 'bizum'],
    'notificaciones' => [
        'apertura_ciclo' => true,
        'cierre_ciclo' => true,
        'recordatorio_recogida' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `gc_ver_catalogo` | Ver productos |
| `gc_hacer_pedido` | Realizar pedidos |
| `gc_gestionar_grupo` | Administrar grupo |
| `gc_gestionar_ciclos` | Crear/cerrar ciclos |
| `gc_ver_estadisticas` | Ver métricas |
| `gc_gestionar_productores` | Admin productores |

## Notas de Desarrollo

- El carrito se almacena en sesión y en BD para persistencia
- Los ciclos se cierran automáticamente via WP-Cron
- El cálculo de huella usa factores configurables
- Los excedentes se liberan 24h después del cierre
- Soporta múltiples grupos por usuario
