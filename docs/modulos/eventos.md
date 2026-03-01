# Módulo: Eventos

> Gestión completa de eventos y actividades

## Descripción

Sistema de gestión de eventos que incluye calendario, inscripciones, diferentes tipos de eventos (presenciales, online, híbridos), integración con otros módulos y sistema de notificaciones.

## Archivos Principales

```
includes/modules/eventos/
├── class-eventos-module.php           # Clase principal
├── class-eventos-dashboard-tab.php    # Tab dashboard
├── install.php                        # Instalación BD
├── frontend/
│   └── class-eventos-frontend-controller.php
├── views/
│   └── dashboard.php
└── assets/
    ├── css/
    └── js/
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Evento | `fc_evento` | Eventos y actividades |

## Tablas de Base de Datos

### wp_flavor_eventos
Datos de eventos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| titulo | varchar(255) | Título evento |
| slug | varchar(255) UNIQUE | URL amigable |
| descripcion | text | Descripción corta |
| contenido | longtext | Contenido completo |
| extracto | text | Extracto |
| imagen | varchar(500) | URL imagen principal |
| galeria | longtext JSON | Galería imágenes |
| tipo | varchar(50) | Tipo evento |
| categoria | varchar(100) | Categoría |
| etiquetas | varchar(255) | Tags |
| fecha_inicio | datetime | Inicio evento |
| fecha_fin | datetime | Fin evento |
| hora_inicio | time | Hora inicio |
| hora_fin | time | Hora fin |
| es_todo_el_dia | tinyint(1) | Evento de día completo |
| es_recurrente | tinyint(1) | Se repite |
| recurrencia_tipo | varchar(20) | diario/semanal/mensual/anual |
| recurrencia_config | longtext JSON | Config recurrencia |
| ubicacion_tipo | enum | presencial/online/hibrido |
| ubicacion_nombre | varchar(255) | Nombre lugar |
| ubicacion_direccion | varchar(255) | Dirección |
| ubicacion_latitud | decimal(10,8) | Coordenada |
| ubicacion_longitud | decimal(11,8) | Coordenada |
| url_online | varchar(500) | Link reunión |
| plataforma_online | varchar(50) | Zoom/Meet/Teams/etc |
| organizador_id | bigint(20) | FK usuario |
| organizador_nombre | varchar(100) | Nombre organizador |
| organizador_email | varchar(100) | Email contacto |
| organizador_telefono | varchar(20) | Teléfono contacto |
| aforo_maximo | int | Plazas totales |
| inscritos_count | int | Inscritos actuales |
| lista_espera_count | int | En lista espera |
| requiere_inscripcion | tinyint(1) | Necesita inscribirse |
| inscripcion_abierta | tinyint(1) | Acepta inscripciones |
| fecha_limite_inscripcion | datetime | Límite inscripción |
| precio | decimal(10,2) | Precio general |
| precio_socios | decimal(10,2) | Precio socios |
| es_gratuito | tinyint(1) | Es gratis |
| estado | enum | borrador/publicado/cancelado/finalizado |
| visibilidad | enum | publico/privado/miembros |
| es_destacado | tinyint(1) | Destacado en home |
| visualizaciones | int | Contador vistas |
| comunidad_id | bigint(20) | FK comunidad si aplica |
| colectivo_id | bigint(20) | FK colectivo si aplica |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** slug, fecha_inicio, estado, categoria, organizador_id

### wp_flavor_eventos_inscripciones
Inscripciones a eventos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| evento_id | bigint(20) | FK evento |
| usuario_id | bigint(20) | FK usuario (puede ser 0) |
| nombre | varchar(100) | Nombre inscrito |
| email | varchar(100) | Email |
| telefono | varchar(20) | Teléfono |
| num_asistentes | int | Nº personas |
| estado | enum | pendiente/confirmada/cancelada/asistio/no_asistio |
| tipo_entrada | varchar(50) | Tipo entrada |
| precio_pagado | decimal(10,2) | Precio pagado |
| metodo_pago | varchar(50) | Método pago |
| referencia_pago | varchar(100) | Referencia |
| codigo_confirmacion | varchar(20) | Código único |
| qr_code | varchar(500) | URL QR |
| check_in_at | datetime | Fecha check-in |
| notas | text | Notas inscrito |
| notas_admin | text | Notas internas |
| metadata | longtext JSON | Datos extra |
| created_at | datetime | Fecha inscripción |
| updated_at | datetime | Última actualización |

**Índices:** evento_id, usuario_id, email, estado, codigo_confirmacion

### wp_flavor_eventos_categorias
Categorías de eventos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(100) | Nombre |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripción |
| icono | varchar(50) | Icono |
| color | varchar(7) | Color |
| imagen | varchar(500) | Imagen |
| padre_id | bigint(20) | Categoría padre |
| orden | int | Orden listado |
| activa | tinyint(1) | Está activa |

### wp_flavor_eventos_lista_espera
Lista de espera cuando está lleno.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| evento_id | bigint(20) | FK evento |
| usuario_id | bigint(20) | FK usuario |
| email | varchar(100) | Email |
| nombre | varchar(100) | Nombre |
| posicion | int | Posición en cola |
| notificado | tinyint(1) | Ya fue notificado |
| fecha_notificacion | datetime | Fecha notificación |
| created_at | datetime | Fecha registro |

## Shortcodes

### Listados y Calendario

```php
[eventos_calendario]
// Calendario visual
// - vista: mes|semana|lista|agenda
// - categoria: slug
// - comunidad: ID
// - mostrar_filtros: true|false

[eventos_proximos]
// Lista próximos eventos
// - limite: número (default: 6)
// - categoria: slug
// - columnas: 1|2|3|4
// - mostrar_precio: true|false
// - mostrar_aforo: true|false

[eventos_pasados]
// Histórico de eventos
// - limite: número
// - año: 2024
```

### Detalle y Acciones

```php
[eventos_detalle]
// Página completa del evento
// - id: ID evento (o auto desde URL)

[eventos_inscribirse]
// Formulario inscripción
// - id: ID evento
// - campos: nombre,email,telefono,notas

[eventos_mis_eventos]
// Eventos del usuario
// - tipo: inscritos|organizados|todos
// - estado: proximos|pasados|todos
```

### Gestión

```php
[eventos_crear]
// Formulario crear evento
// - tipos: todos|presencial|online
// - categorias: slugs permitidas

[eventos_buscar]
// Buscador de eventos
// - placeholder: texto
// - con_mapa: true|false
// - filtros: fecha,categoria,ubicacion,precio
```

## Dashboard Tab

**Clase:** `Flavor_Eventos_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Calendario personal
- `proximos` - Próximos eventos
- `mis-inscripciones` - Mis inscripciones
- `organizados` - Eventos que organizo
- `crear` - Crear evento
- `historial` - Eventos pasados
- `favoritos` - Guardados

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/eventos/` | index | Calendario |
| `/mi-portal/eventos/proximos/` | proximos | Próximos |
| `/mi-portal/eventos/mis-inscripciones/` | mis-inscripciones | Inscritos |
| `/mi-portal/eventos/crear/` | crear | Nuevo evento |
| `/mi-portal/eventos/organizados/` | organizados | Como organizador |
| `/mi-portal/eventos/{id}/` | ver | Detalle |
| `/mi-portal/eventos/{id}/inscribirse/` | inscribirse | Inscripción |
| `/mi-portal/eventos/{id}/gestionar/` | gestionar | Admin evento |
| `/mi-portal/eventos/{id}/asistentes/` | asistentes | Lista asistentes |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| comunidades | Contenedor | Eventos de comunidad |
| colectivos | Contenedor | Eventos de colectivo |
| espacios-comunes | Reserva | Reservar espacio para evento |
| socios | Descuento | Precio socio |
| cursos | Relación | Eventos de cursos |
| talleres | Relación | Sesiones de talleres |

## Hooks y Filtros

### Actions

```php
// Nueva inscripción
do_action('flavor_evento_inscripcion', $inscripcion_id, $evento_id, $usuario_id);

// Inscripción cancelada
do_action('flavor_evento_cancelacion', $inscripcion_id, $evento_id);

// Check-in realizado
do_action('flavor_evento_checkin', $inscripcion_id, $evento_id);

// Evento publicado
do_action('flavor_evento_publicado', $evento_id);

// Evento cancelado
do_action('flavor_evento_cancelado', $evento_id, $motivo);

// Plaza liberada (notificar lista espera)
do_action('flavor_evento_plaza_liberada', $evento_id);
```

### Filters

```php
// Precio final del evento
apply_filters('flavor_evento_precio', $precio, $evento_id, $usuario_id);

// Campos del formulario inscripción
apply_filters('flavor_evento_campos_inscripcion', $campos, $evento_id);

// Validar inscripción
apply_filters('flavor_evento_validar_inscripcion', $valido, $datos, $evento_id);

// Categorías disponibles para crear
apply_filters('flavor_evento_categorias_crear', $categorias, $usuario_id);
```

## Configuración

```php
'eventos' => [
    'enabled' => true,
    'tipos_permitidos' => ['presencial', 'online', 'hibrido'],
    'requiere_aprobacion_crear' => false,
    'max_asistentes_default' => 0, // 0 = sin límite
    'dias_antelacion_crear' => 1,
    'permitir_lista_espera' => true,
    'dias_cancelacion_gratuita' => 2,
    'plataformas_online' => ['zoom', 'meet', 'teams', 'otra'],
    'integracion_calendario' => true, // Google Calendar, iCal
    'notificaciones' => [
        'inscripcion_confirmada' => true,
        'recordatorio_24h' => true,
        'recordatorio_1h' => true,
        'evento_cancelado' => true,
        'plaza_disponible' => true,
    ],
]
```

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `eventos_ver` | Ver eventos públicos |
| `eventos_inscribirse` | Inscribirse a eventos |
| `eventos_crear` | Crear eventos |
| `eventos_editar_propio` | Editar propios |
| `eventos_gestionar` | Admin todos |
| `eventos_checkin` | Hacer check-in |

## Integraciones

### Google Calendar
```php
// Exportar a Google Calendar
$gcal_url = flavor_evento_get_gcal_url($evento_id);

// Sincronizar con calendario
flavor_evento_sync_gcal($evento_id, $calendar_id);
```

### iCal
```php
// Descargar archivo .ics
$ical_url = flavor_evento_get_ical_url($evento_id);
```

### Códigos QR
El sistema genera códigos QR para:
- Check-in de asistentes
- Compartir evento
- Añadir a calendario
