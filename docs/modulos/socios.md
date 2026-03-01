# Módulo: Socios

> Gestión completa de socios y membresías

## Descripción

Sistema de gestión de socios que incluye altas, bajas, cuotas, carnet digital, directorio de miembros y beneficios. Integra con otros módulos para ofrecer descuentos y acceso exclusivo.

## Archivos Principales

```
includes/modules/socios/
├── class-socios-module.php           # Clase principal
├── class-socios-dashboard-tab.php    # Tab dashboard
├── install.php                       # Instalación BD
├── frontend/
│   └── class-socios-frontend-controller.php
├── views/
│   ├── dashboard.php
│   ├── cuotas.php
│   └── socios.php
└── assets/
    └── css/socios-frontend.css
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Socio | `fc_socio` | Ficha de socio |

## Tablas de Base de Datos

### wp_flavor_socios
Datos principales de socios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| numero_socio | varchar(20) UNIQUE | Número de socio |
| usuario_id | bigint(20) | FK usuario WP |
| nombre | varchar(100) | Nombre |
| apellidos | varchar(150) | Apellidos |
| email | varchar(100) UNIQUE | Email |
| telefono | varchar(20) | Teléfono |
| dni_nif | varchar(20) | DNI/NIF |
| fecha_nacimiento | date | Fecha nacimiento |
| genero | enum | masculino/femenino/otro/no_especifica |
| direccion | varchar(255) | Dirección |
| codigo_postal | varchar(10) | CP |
| ciudad | varchar(100) | Ciudad |
| provincia | varchar(100) | Provincia |
| imagen_perfil | varchar(500) | URL foto |
| tipo_socio | varchar(50) | FK tipo |
| categoria | varchar(50) | Subcategoría |
| fecha_alta | date | Fecha alta |
| fecha_baja | date | Fecha baja |
| estado | enum | pendiente/activo/suspendido/baja/moroso |
| cuota_tipo | varchar(50) | Tipo cuota |
| cuota_importe | decimal(10,2) | Importe cuota |
| cuota_reducida | tinyint(1) | Tiene reducción |
| forma_pago | enum | efectivo/transferencia/domiciliacion/tarjeta |
| iban | varchar(34) | Cuenta bancaria |
| mandato_sepa | varchar(50) | Referencia SEPA |
| comunicaciones_email | tinyint(1) | Acepta email |
| comunicaciones_sms | tinyint(1) | Acepta SMS |
| comunicaciones_postal | tinyint(1) | Acepta correo |
| intereses | text | Intereses/temas |
| notas | text | Observaciones |
| referido_por | bigint(20) | Socio que refirió |
| carnet_emitido | tinyint(1) | Carnet generado |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha registro |
| updated_at | datetime | Última actualización |

**Índices:** numero_socio, usuario_id, email, estado, tipo_socio

### wp_flavor_socios_cuotas
Cuotas y pagos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| socio_id | bigint(20) | FK socio |
| concepto | varchar(255) | Concepto |
| tipo | enum | inscripcion/mensual/anual/extraordinaria |
| periodo | varchar(20) | Período (202401, 2024, etc.) |
| fecha_emision | date | Fecha emisión |
| fecha_vencimiento | date | Fecha vencimiento |
| fecha_pago | date | Fecha pago efectivo |
| importe | decimal(10,2) | Importe cuota |
| estado | enum | pendiente/pagado/vencido/anulado |
| metodo_pago | varchar(50) | Método usado |
| referencia_pago | varchar(100) | Ref. transferencia/recibo |
| factura_id | varchar(50) | ID factura |
| factura_url | varchar(500) | URL factura PDF |
| remesa_id | bigint(20) | FK remesa SEPA |
| intentos_cobro | tinyint | Nº intentos |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha creación |

**Índices:** socio_id, estado, periodo, fecha_vencimiento

### wp_flavor_socios_tipos
Tipos de socios configurables.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(100) | Nombre tipo |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripción |
| cuota_mensual | decimal(10,2) | Cuota mensual |
| cuota_anual | decimal(10,2) | Cuota anual |
| cuota_inscripcion | decimal(10,2) | Cuota alta |
| beneficios | text | Lista beneficios |
| color | varchar(7) | Color distintivo |
| icono | varchar(50) | Icono |
| es_gratuito | tinyint(1) | Sin cuota |
| requiere_aprobacion | tinyint(1) | Requiere validar |
| visible | tinyint(1) | Visible en web |
| orden | int | Orden listado |
| activo | tinyint(1) | Está activo |

### wp_flavor_socios_historial
Historial de cambios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| socio_id | bigint(20) | FK socio |
| accion | varchar(50) | Tipo acción |
| descripcion | text | Descripción |
| datos_anteriores | longtext JSON | Estado previo |
| datos_nuevos | longtext JSON | Estado nuevo |
| realizado_por | bigint(20) | Usuario que hizo |
| ip_address | varchar(45) | IP origen |
| created_at | datetime | Fecha acción |

### wp_flavor_socios_remesas
Remesas SEPA para domiciliaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| referencia | varchar(50) UNIQUE | Ref. remesa |
| fecha_generacion | datetime | Fecha creación |
| fecha_cobro | date | Fecha cargo |
| total_recibos | int | Nº recibos |
| importe_total | decimal(12,2) | Total remesa |
| estado | enum | generada/enviada/procesada/rechazada |
| archivo_xml | varchar(500) | Ruta XML SEPA |
| notas | text | Observaciones |

## Shortcodes

### Perfil y Datos

```php
[socios_mi_perfil]
// Perfil completo del socio
// - editable: true|false
// - secciones: datos,contacto,preferencias

[socios_carnet]
// Carnet digital
// - formato: tarjeta|qr|completo
// - descargar: true|false

[socios_cuotas]
// Estado de cuotas
// - mostrar_historial: true|false
// - pagar_online: true|false
```

### Alta y Gestión

```php
[socios_alta]
// Formulario de alta
// - tipo_default: slug tipo
// - mostrar_tipos: true|false
// - campos_requeridos: nombre,email,telefono

[socios_baja]
// Formulario de baja
// - confirmar: true|false

[socios_actualizar_datos]
// Actualizar información
```

### Directorio

```php
[socios_directorio]
// Directorio de socios
// - tipo: slug tipo
// - buscar: true|false
// - mostrar_foto: true|false
// - mostrar_contacto: true|false (según privacidad)
// - limite: número

[socios_beneficios]
// Lista de beneficios
// - tipo: slug tipo (o todos)
// - formato: lista|grid|acordeon
```

## Dashboard Tab

**Clase:** `Flavor_Socios_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Resumen estado
- `perfil` - Mi perfil
- `cuotas` - Mis cuotas
- `carnet` - Carnet digital
- `beneficios` - Mis beneficios
- `directorio` - Otros socios
- `configuracion` - Preferencias

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/socios/` | index | Dashboard |
| `/mi-portal/socios/perfil/` | perfil | Mi perfil |
| `/mi-portal/socios/cuotas/` | cuotas | Mis cuotas |
| `/mi-portal/socios/carnet/` | carnet | Carnet digital |
| `/mi-portal/socios/beneficios/` | beneficios | Beneficios |
| `/mi-portal/socios/directorio/` | directorio | Directorio |
| `/mi-portal/socios/alta/` | alta | Hacerse socio |
| `/mi-portal/socios/baja/` | baja | Darse de baja |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| comunidades | Membresía | Comunidades de socios |
| colectivos | Membresía | Colectivos de socios |
| grupos-consumo | Descuento | Descuento en pedidos |
| eventos | Descuento | Precio socio en eventos |
| cursos | Descuento | Precio socio en cursos |
| talleres | Descuento | Precio socio en talleres |
| espacios-comunes | Acceso | Reserva prioritaria |
| biblioteca | Acceso | Préstamos extendidos |

## Hooks y Filtros

### Actions

```php
// Nuevo socio dado de alta
do_action('flavor_socio_alta', $socio_id, $datos);

// Socio dado de baja
do_action('flavor_socio_baja', $socio_id, $motivo);

// Cuota pagada
do_action('flavor_socio_cuota_pagada', $cuota_id, $socio_id);

// Cuota vencida
do_action('flavor_socio_cuota_vencida', $cuota_id, $socio_id);

// Estado cambiado
do_action('flavor_socio_estado_cambiado', $socio_id, $estado_nuevo, $estado_anterior);
```

### Filters

```php
// Descuento de socio en precio
apply_filters('flavor_socio_descuento', $descuento, $tipo_socio, $contexto);

// Campos del formulario de alta
apply_filters('flavor_socio_campos_alta', $campos);

// Validar datos socio
apply_filters('flavor_socio_validar_datos', $valido, $datos);

// Generar número de socio
apply_filters('flavor_socio_generar_numero', $numero, $tipo_socio);
```

## Configuración

```php
'socios' => [
    'enabled' => true,
    'prefijo_numero' => 'SOC-',
    'formato_numero' => '%s%05d', // SOC-00001
    'cuota_por_defecto' => 'mensual',
    'dias_gracia' => 15,
    'dias_aviso_vencimiento' => 7,
    'permitir_pago_online' => true,
    'metodos_pago' => ['transferencia', 'domiciliacion', 'tarjeta'],
    'requiere_aprobacion' => false,
    'carnet_digital' => true,
    'directorio_publico' => false,
    'campos_obligatorios' => ['nombre', 'apellidos', 'email', 'telefono'],
    'notificaciones' => [
        'bienvenida' => true,
        'cuota_proxima' => true,
        'cuota_vencida' => true,
        'renovacion' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `socios_ver_propio` | Ver su ficha |
| `socios_editar_propio` | Editar sus datos |
| `socios_ver_directorio` | Ver otros socios |
| `socios_gestionar` | Admin socios |
| `socios_emitir_cuotas` | Generar cuotas |
| `socios_ver_informes` | Ver estadísticas |

## Integración Descuentos

```php
// En otros módulos, aplicar descuento de socio:
$precio_final = $precio_base;
if (function_exists('flavor_socio_get_descuento')) {
    $descuento = flavor_socio_get_descuento(get_current_user_id(), 'eventos');
    $precio_final = $precio_base * (1 - $descuento / 100);
}
```

## Carnet Digital

El carnet digital incluye:
- Foto del socio
- Número de socio
- Nombre completo
- Tipo de socio
- Fecha de alta
- Fecha de validez
- Código QR verificable
- Código de barras

Formatos de exportación:
- Vista web responsiva
- Imagen PNG
- PDF para imprimir
- Apple Wallet (pkpass)
- Google Wallet
