# Módulo: Email Marketing

> Sistema de newsletters y campañas de email

## Descripción

Plataforma completa de email marketing que incluye gestión de listas, creación de campañas, automatizaciones, plantillas y tracking de métricas.

## Archivos Principales

```
includes/modules/email-marketing/
├── class-email-marketing-module.php
├── class-em-dashboard-tab.php
├── install.php
├── views/
│   └── configuracion.php
└── assets/
    ├── css/em-dashboard.css
    └── js/em-dashboard.js
```

## Tablas de Base de Datos

### wp_flavor_em_listas
Listas de suscriptores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(200) | Nombre lista |
| slug | varchar(200) UNIQUE | Identificador |
| descripcion | text | Descripción |
| tipo | enum | general/segmento/automatica |
| doble_optin | tinyint(1) | Requiere confirmación |
| mensaje_confirmacion | text | Email confirmación |
| mensaje_bienvenida | text | Email bienvenida |
| total_suscriptores | int | Contador |
| activa | tinyint(1) | Está activa |
| metadata | longtext JSON | Datos extra |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

### wp_flavor_em_suscriptores
Base de suscriptores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| email | varchar(100) UNIQUE | Email |
| nombre | varchar(100) | Nombre |
| apellidos | varchar(100) | Apellidos |
| usuario_id | bigint(20) | FK usuario WP |
| estado | enum | pendiente/confirmado/baja/rebotado/spam |
| origen | varchar(100) | Fuente suscripción |
| ip_registro | varchar(45) | IP registro |
| fecha_registro | datetime | Fecha alta |
| fecha_confirmacion | datetime | Fecha confirmación |
| fecha_baja | datetime | Fecha baja |
| motivo_baja | text | Razón baja |
| tags | longtext JSON | Etiquetas |
| campos_personalizados | longtext JSON | Campos extra |
| puntuacion | int | Lead scoring |
| emails_enviados | int | Total enviados |
| emails_abiertos | int | Total abiertos |
| emails_clicks | int | Total clicks |
| ultimo_email | datetime | Último email recibido |
| ultima_apertura | datetime | Última apertura |
| ultimo_click | datetime | Último click |
| metadata | longtext JSON | Datos extra |

**Índices:** email, estado, usuario_id

### wp_flavor_em_suscriptor_lista
Relación suscriptor-lista (N:M).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| suscriptor_id | bigint(20) | FK suscriptor |
| lista_id | bigint(20) | FK lista |
| fecha_suscripcion | datetime | Fecha unión |
| estado | enum | activo/baja |
| fuente | varchar(100) | Cómo se añadió |

**Unique:** suscriptor_id + lista_id

### wp_flavor_em_campanias
Campañas de email.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(255) | Nombre interno |
| asunto | varchar(255) | Asunto email |
| asunto_alternativo | varchar(255) | Asunto test A/B |
| preview_text | varchar(255) | Texto preview |
| contenido_html | longtext | HTML email |
| contenido_texto | longtext | Versión texto |
| plantilla_id | bigint(20) | FK plantilla |
| tipo | enum | regular/ab_test/automatizada/transaccional |
| estado | enum | borrador/programada/enviando/enviada/pausada/cancelada |
| listas_ids | longtext JSON | Listas destino |
| segmentos | longtext JSON | Filtros segmento |
| excluir_listas | longtext JSON | Listas excluidas |
| remitente_nombre | varchar(100) | Nombre "De" |
| remitente_email | varchar(100) | Email "De" |
| responder_a | varchar(100) | Reply-To |
| fecha_programada | datetime | Fecha envío programado |
| fecha_inicio | datetime | Inicio envío real |
| fecha_fin | datetime | Fin envío |
| total_destinatarios | int | Nº destinatarios |
| total_enviados | int | Enviados |
| total_entregados | int | Entregados |
| total_abiertos | int | Abiertos únicos |
| total_clicks | int | Clicks únicos |
| total_bajas | int | Bajas |
| total_rebotes | int | Rebotes |
| total_spam | int | Marcados spam |
| ab_variante_ganadora | varchar(10) | A o B |
| ab_criterio | varchar(50) | Criterio selección |
| metadata | longtext JSON | Datos extra |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** estado, tipo, fecha_programada

### wp_flavor_em_automatizaciones
Flujos automatizados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(255) | Nombre |
| descripcion | text | Descripción |
| trigger_tipo | varchar(50) | Tipo disparador |
| trigger_config | longtext JSON | Configuración trigger |
| estado | enum | activa/pausada/borrador |
| pasos | longtext JSON | Secuencia de pasos |
| total_inscritos | int | Usuarios en flujo |
| total_completados | int | Han terminado |
| total_salidos | int | Han salido |
| fecha_inicio | datetime | Activación |
| metadata | longtext JSON | Datos extra |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

### wp_flavor_em_auto_suscriptores
Suscriptores en automatizaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| automatizacion_id | bigint(20) | FK automatización |
| suscriptor_id | bigint(20) | FK suscriptor |
| paso_actual | int | Paso donde está |
| estado | enum | activo/completado/salido/pausado |
| fecha_entrada | datetime | Entrada al flujo |
| fecha_ultimo_paso | datetime | Último paso ejecutado |
| proxima_ejecucion | datetime | Próximo paso |
| datos | longtext JSON | Datos del recorrido |

### wp_flavor_em_plantillas
Plantillas de email.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(200) | Nombre |
| descripcion | text | Descripción |
| categoria | varchar(100) | Categoría |
| contenido_html | longtext | HTML plantilla |
| thumbnail | varchar(500) | URL preview |
| variables | longtext JSON | Variables disponibles |
| es_publica | tinyint(1) | Visible para todos |
| es_sistema | tinyint(1) | Plantilla del sistema |
| created_by | bigint(20) | Creador |
| created_at | datetime | Fecha creación |

### wp_flavor_em_tracking
Tracking de emails.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| campania_id | bigint(20) | FK campaña |
| automatizacion_id | bigint(20) | FK automatización |
| suscriptor_id | bigint(20) | FK suscriptor |
| email_hash | varchar(64) | Hash único email |
| tipo | enum | envio/entrega/apertura/click/baja/rebote/spam |
| url_clickeada | varchar(500) | URL si click |
| ip | varchar(45) | IP evento |
| user_agent | varchar(500) | User agent |
| pais | varchar(2) | País |
| ciudad | varchar(100) | Ciudad |
| dispositivo | varchar(50) | desktop/mobile/tablet |
| cliente_email | varchar(100) | Cliente email detectado |
| created_at | datetime | Fecha evento |

**Índices:** campania_id, suscriptor_id, tipo, created_at

### wp_flavor_em_cola
Cola de envíos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| campania_id | bigint(20) | FK campaña |
| suscriptor_id | bigint(20) | FK suscriptor |
| email | varchar(100) | Email destino |
| prioridad | tinyint | Prioridad cola |
| estado | enum | pendiente/procesando/enviado/fallido |
| intentos | tinyint | Intentos envío |
| ultimo_intento | datetime | Último intento |
| error | text | Error si falló |
| created_at | datetime | Añadido a cola |
| sent_at | datetime | Fecha envío |

## Shortcodes

### Suscripción

```php
[em_suscripcion]
// Formulario suscripción
// - lista: slug lista
// - campos: email,nombre,apellidos
// - doble_optin: true|false
// - mensaje_exito: texto
// - estilo: inline|modal|flotante

[em_preferencias]
// Centro de preferencias
// - mostrar_listas: true|false
// - permitir_baja: true|false
```

### Archivo

```php
[em_archivo]
// Archivo de newsletters
// - lista: slug lista
// - limite: número
// - columnas: 1|2|3

[em_campania]
// Ver campaña en navegador
// - id: ID campaña
```

### Gestión (admin)

```php
[em_estadisticas]
// Estadísticas globales
// - periodo: mes|trimestre|año

[em_listas]
// Gestión de listas

[em_crear]
// Editor de campaña

[em_programar]
// Programar envío

[em_plantillas]
// Galería de plantillas

[em_contactos]
// Gestión de contactos
```

## Dashboard Tab

**Clase:** `Flavor_EM_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Resumen
- `campanias` - Lista campañas
- `crear` - Nueva campaña
- `automatizaciones` - Flujos
- `listas` - Listas
- `suscriptores` - Contactos
- `plantillas` - Plantillas
- `estadisticas` - Métricas

## Tipos de Automatización

| Trigger | Descripción |
|---------|-------------|
| suscripcion | Al suscribirse a lista |
| cumpleanos | En fecha de cumpleaños |
| fecha_especifica | En fecha configurada |
| inactividad | Sin actividad X días |
| click_enlace | Al hacer click en enlace |
| etiqueta | Al añadir etiqueta |
| compra | Al realizar compra (WC) |
| formulario | Al enviar formulario |

## Métricas

| Métrica | Descripción |
|---------|-------------|
| Tasa entrega | Entregados / Enviados |
| Tasa apertura | Abiertos / Entregados |
| Tasa click | Clicks / Abiertos |
| Tasa baja | Bajas / Entregados |
| Tasa rebote | Rebotes / Enviados |
| Tasa spam | Spam / Entregados |

## Configuración

```php
'email_marketing' => [
    'enabled' => true,
    'proveedor' => 'smtp', // smtp|sendgrid|mailgun|ses
    'smtp' => [
        'host' => '',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'encryption' => 'tls',
    ],
    'remitente_default' => [
        'nombre' => 'Mi Organización',
        'email' => 'info@example.com',
    ],
    'limite_envio_hora' => 500,
    'doble_optin_default' => true,
    'tracking' => [
        'aperturas' => true,
        'clicks' => true,
        'geolocalizacion' => true,
    ],
    'limpieza_automatica' => [
        'rebotes_duros' => true,
        'inactivos_dias' => 365,
    ],
]
```

## Integración con Otros Módulos

| Módulo | Integración |
|--------|-------------|
| socios | Sincronizar suscriptores |
| comunidades | Newsletters por comunidad |
| eventos | Emails de eventos |
| grupos-consumo | Notificaciones ciclos |
| encuestas | Distribución encuestas |
