# Modulo: Tramites

> Sistema completo de gestion de tramites administrativos online

## Descripcion

Plataforma integral para la gestion de tramites administrativos que permite a los ciudadanos iniciar, seguir y completar procedimientos administrativos de forma online o presencial. Incluye catalogo de tramites, formularios dinamicos, gestion documental, seguimiento de expedientes y notificaciones automaticas.

## Archivos Principales

```
includes/modules/tramites/
├── class-tramites-module.php           # Modulo principal
├── class-tramites-api.php              # API REST movil
├── class-tramites-dashboard-tab.php    # Tabs dashboard usuario
├── class-tramites-dashboard-widget.php # Widget dashboard admin
├── frontend/
│   └── class-tramites-frontend-controller.php  # Controlador frontend
├── templates/
│   └── [plantillas de vistas]
├── views/
│   ├── dashboard.php
│   ├── solicitudes.php
│   ├── aprobaciones.php
│   ├── documentos.php
│   └── plantillas.php
└── assets/
    ├── css/
    │   ├── tramites.css
    │   └── tramites-dashboard.css
    └── js/
        └── tramites.js
```

## Tablas de Base de Datos

### wp_flavor_tipos_tramite
Catalogo de tipos de tramites disponibles.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre del tramite |
| descripcion | text | Descripcion completa |
| descripcion_corta | text | Resumen breve |
| categoria | varchar(100) | Categoria principal |
| subcategoria | varchar(100) | Subcategoria |
| icono | varchar(50) | Icono dashicon |
| color | varchar(20) | Color identificativo |
| plazo_resolucion_dias | int | Dias para resolver |
| requiere_cita | tinyint(1) | Requiere cita previa |
| permite_online | tinyint(1) | Disponible online |
| permite_presencial | tinyint(1) | Disponible presencial |
| precio | decimal(10,2) | Tasa del tramite |
| tasa | decimal(10,2) | Tasa administrativa |
| visibilidad | varchar(20) | publico/registrados/admin |
| requisitos | longtext JSON | Lista de requisitos |
| documentos_requeridos | longtext JSON | Docs necesarios |
| departamento_responsable | varchar(100) | Departamento |
| estado | enum | activo/inactivo |
| orden | int | Orden en listado |
| created_at | datetime | Fecha creacion |

**Indices:** categoria, estado, orden

### wp_flavor_expedientes
Expedientes/solicitudes de tramites.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| numero_expediente | varchar(50) UNIQUE | Codigo referencia (EXP-YYYY-NNNNN) |
| tipo_tramite_id | bigint(20) | FK tipo de tramite |
| user_id | bigint(20) | FK usuario WordPress |
| solicitante_id | bigint(20) | FK solicitante |
| session_id | varchar(50) | ID sesion anonima |
| nombre_solicitante | varchar(255) | Nombre completo |
| email_solicitante | varchar(100) | Email contacto |
| telefono_solicitante | varchar(20) | Telefono |
| dni_solicitante | varchar(20) | DNI/NIE/CIF |
| direccion_solicitante | text | Direccion postal |
| estado_actual | varchar(50) | Estado del expediente |
| via_tramitacion | enum | online/presencial |
| datos_formulario | longtext JSON | Datos del formulario |
| notas_solicitante | text | Observaciones |
| observaciones | text | Notas internas |
| prioridad | enum | baja/media/alta/urgente |
| departamento | varchar(100) | Departamento asignado |
| asignado_a | bigint(20) | FK usuario asignado |
| canal_entrada | varchar(50) | online/presencial/telefono |
| fecha_inicio | datetime | Inicio tramitacion |
| fecha_solicitud | datetime | Fecha solicitud |
| fecha_creacion | datetime | Fecha creacion |
| fecha_modificacion | datetime | Ultima modificacion |
| fecha_resolucion | datetime | Fecha resolucion |
| fecha_limite | datetime | Fecha limite |
| ip_creacion | varchar(45) | IP del solicitante |
| user_agent_creacion | varchar(500) | User agent |
| created_at | datetime | Timestamp creacion |
| updated_at | datetime | Timestamp actualizacion |

**Indices:** numero_expediente, tipo_tramite_id, user_id, estado_actual, asignado_a

### wp_flavor_documentos_expediente
Documentos adjuntos a expedientes.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| expediente_id | bigint(20) | FK expediente |
| tipo_documento | varchar(100) | Tipo de documento |
| nombre_original | varchar(255) | Nombre original |
| nombre_archivo | varchar(255) | Nombre almacenado |
| ruta_archivo | varchar(500) | Ruta en servidor |
| url_archivo | varchar(500) | URL publica |
| mime_type | varchar(100) | Tipo MIME |
| tamano_bytes | int | Tamano en bytes |
| hash_archivo | varchar(64) | Hash SHA256 |
| origen | varchar(50) | solicitante/admin/sistema |
| estado | varchar(50) | Estado revision |
| subido_por | bigint(20) | FK usuario |
| visible_solicitante | tinyint(1) | Visible al ciudadano |
| fecha_subida | datetime | Fecha subida |

**Indices:** expediente_id, tipo_documento

### wp_flavor_estados_tramite
Configuracion de estados disponibles.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| codigo | varchar(50) PK | Codigo unico |
| nombre | varchar(100) | Nombre visible |
| descripcion | text | Descripcion |
| color | varchar(20) | Color hexadecimal |
| icono | varchar(50) | Icono dashicon |
| es_inicial | tinyint(1) | Es estado inicial |
| es_final | tinyint(1) | Es estado final |
| permite_edicion | tinyint(1) | Permite editar datos |
| permite_documentos | tinyint(1) | Permite subir docs |
| notifica_solicitante | tinyint(1) | Notifica al ciudadano |
| orden | int | Orden en flujo |
| activo | tinyint(1) | Esta activo |

### wp_flavor_historial_estados_expediente
Historial de cambios de estado.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| expediente_id | bigint(20) | FK expediente |
| estado_anterior | varchar(50) | Estado previo |
| estado_nuevo | varchar(50) | Nuevo estado |
| usuario_id | bigint(20) | FK usuario que cambio |
| comentario | text | Comentario del cambio |
| fecha_cambio | datetime | Fecha del cambio |

**Indices:** expediente_id

### wp_flavor_campos_formulario
Campos dinamicos por tipo de tramite.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| tipo_tramite_id | bigint(20) | FK tipo tramite |
| nombre_campo | varchar(100) | Nombre interno |
| etiqueta | varchar(255) | Label visible |
| tipo_campo | varchar(50) | text/email/select/file/etc |
| opciones | longtext JSON | Opciones para select |
| es_obligatorio | tinyint(1) | Campo requerido |
| requerido | tinyint(1) | Alias de obligatorio |
| placeholder | varchar(255) | Placeholder |
| ayuda | text | Texto de ayuda |
| patron_validacion | varchar(255) | Regex validacion |
| mensaje_error | varchar(255) | Mensaje error custom |
| condicion_visible | longtext JSON | Mostrar condicionalmente |
| orden | int | Orden en formulario |
| activo | tinyint(1) | Campo activo |

**Indices:** tipo_tramite_id, orden

### wp_flavor_historial_expediente
Historial completo de acciones.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| expediente_id | bigint(20) | FK expediente |
| usuario_id | bigint(20) | FK usuario |
| tipo_evento | varchar(100) | Tipo de evento |
| accion | varchar(100) | Accion realizada |
| descripcion | text | Descripcion detallada |
| metadata | longtext JSON | Datos adicionales |
| es_publico | tinyint(1) | Visible al ciudadano |
| fecha | datetime | Fecha evento |
| fecha_evento | datetime | Alias fecha |

**Indices:** expediente_id, usuario_id

## Shortcodes

### Modulo Principal

```php
[catalogo_tramites]
// Catalogo completo de tramites disponibles
// - categoria: filtrar por categoria
// - columnas: 2|3|4 (por defecto 3)
// - mostrar_filtros: true|false
// - mostrar_buscador: true|false
// - limite: numero maximo

[iniciar_tramite]
// Formulario para iniciar un tramite
// - id: ID del tipo de tramite (o desde ?tramite=ID)

[mis_expedientes]
// Lista de expedientes del usuario logueado
// Requiere login

[estado_expediente]
// Consulta publica de estado por numero
// - (usa ?expediente=EXP-YYYY-NNNNN)
```

### Frontend Controller

```php
[flavor_tramites_catalogo]
// Catalogo con diseno moderno
// - categoria: slug categoria
// - limite: numero
// - mostrar_filtros: si|no

[flavor_tramites_detalle]
// Detalle de un tipo de tramite
// - id: ID del tramite (o desde URL)

[flavor_tramites_solicitar]
// Formulario multi-paso para solicitar
// - id: ID del tramite

[flavor_tramites_mis_solicitudes]
// Panel de mis expedientes
// Requiere login

[flavor_tramites_seguimiento]
// Consultar estado por numero
// Publico

[flavor_tramites_citas]
// Gestionar citas de tramites
// Requiere login

[flavor_tramites_documentos]
// Gestion de documentos de expediente
// Requiere login

[flavor_tramites_buscar]
// Buscador de tramites
// Publico
```

## Dashboard Tabs

**Clase:** `Flavor_Tramites_Dashboard_Tab`

**Tabs disponibles:**
- `tramites-mis-expedientes` - Panel principal con KPIs y expedientes activos
- `tramites-pendientes` - Expedientes que requieren accion del usuario
- `tramites-historial` - Historial de expedientes completados

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/tramites/` | index | Dashboard principal |
| `/mi-portal/tramites/catalogo/` | catalogo | Catalogo de tramites |
| `/mi-portal/tramites/solicitar/` | solicitar | Iniciar tramite |
| `/mi-portal/tramites/mis-solicitudes/` | mis-solicitudes | Mis expedientes |
| `/mi-portal/tramites/seguimiento/` | seguimiento | Consultar estado |
| `/mi-portal/tramites/citas/` | citas | Mis citas |
| `/mi-portal/tramites/documentos/` | documentos | Gestion documentos |
| `/tramites/` | publico | Catalogo publico |

## REST API

### Namespace: `flavor-tramites/v1`

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/tipos` | Listar tipos de tramite |
| GET | `/tipos/{id}` | Detalle de tipo con campos |
| GET | `/expedientes` | Expedientes del usuario |
| POST | `/expedientes` | Crear nuevo expediente |
| GET | `/expedientes/{id}` | Detalle de expediente |
| PUT | `/expedientes/{id}` | Actualizar expediente |
| POST | `/expedientes/{id}/documentos` | Subir documento |
| GET | `/expedientes/{id}/historial` | Historial del expediente |
| GET | `/expedientes/consulta/{numero}` | Consulta publica |
| GET | `/estados` | Listar estados disponibles |

### Namespace: `flavor-platform/v1` (API Movil)

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/tramites/dashboard` | Dashboard con estadisticas |
| GET | `/tramites/tipos` | Tipos de tramite |
| GET | `/tramites/mis-expedientes` | Mis expedientes |
| GET | `/tramites/expedientes/{id}` | Detalle expediente |
| POST | `/tramites/iniciar` | Iniciar nuevo tramite |
| POST | `/tramites/expedientes/{id}/documentos` | Subir documento |
| DELETE | `/tramites/expedientes/{id}` | Cancelar expediente |

## Estados y Flujo

```
BORRADOR → PENDIENTE → EN_REVISION → EN_TRAMITE → RESUELTO_FAVORABLE
                ↓           ↓              ↓              ↓
           SUBSANACION   ARCHIVADO   RESUELTO_DESFAVORABLE
```

| Estado | Descripcion | Permite Editar | Permite Docs |
|--------|-------------|----------------|--------------|
| borrador | Expediente en preparacion | Si | Si |
| pendiente | Pendiente de revision | No | Si |
| en_revision | Siendo revisado | No | Si |
| subsanacion | Requiere documentacion adicional | Si | Si |
| en_tramite | Tramitandose | No | No |
| resuelto_favorable | Tramite aprobado | No | No |
| resuelto_desfavorable | Tramite denegado | No | No |
| archivado | Expediente archivado | No | No |

## Panel de Administracion

### Paginas Admin (Panel Unificado)

| Slug | Titulo | Descripcion |
|------|--------|-------------|
| `tramites-dashboard` | Dashboard | Vista general con estadisticas |
| `tramites-pendientes` | Pendientes | Expedientes por resolver |
| `tramites-historial` | Historial | Expedientes resueltos |
| `tramites-tipos` | Tipos de Tramite | Gestion del catalogo |
| `tramites-config` | Configuracion | Ajustes del modulo |

## Configuracion

```php
'tramites' => [
    'enabled' => true,
    'disponible_app' => 'cliente', // cliente|admin|ambas
    'requiere_aprobacion' => true,
    'permite_tramites_online' => true,
    'permite_tramites_presencial' => true,
    'plazo_resolucion_maximo_dias' => 30,
    'notificar_cambio_estado' => true,
    'notificar_por_email' => true,
    'permite_cancelacion' => true,
    'dias_limite_cancelacion' => 5,
    'tamanio_maximo_archivo_mb' => 10,
    'tipos_archivo_permitidos' => 'pdf,jpg,jpeg,png,doc,docx',
    'max_archivos_por_expediente' => 20,
    'mostrar_timeline_publico' => true,
    'auto_asignar_numero_expediente' => true,
    'prefijo_expediente' => 'EXP',
    'requiere_login' => true,
]
```

## Permisos y Capabilities

| Capability | Descripcion |
|------------|-------------|
| `tramites_ver_catalogo` | Ver catalogo de tramites |
| `tramites_iniciar` | Iniciar nuevos tramites |
| `tramites_ver_propios` | Ver sus propios expedientes |
| `tramites_gestionar` | Administrar todos los expedientes |
| `tramites_resolver` | Resolver expedientes |
| `tramites_configurar` | Configurar modulo |
| `manage_options` | Acceso completo admin |
| `flavor_ver_dashboard` | Vista resumida de gestor |

## Validaciones

El modulo incluye validaciones especiales para:

- **DNI/NIE**: Validacion con letra de control
- **IBAN**: Validacion de formato bancario
- **Email**: Validacion de formato
- **Telefono**: Validacion de formato

## Notificaciones

El sistema envia notificaciones automaticas:

1. **Confirmacion de inicio**: Al crear un expediente
2. **Cambio de estado**: Cuando el estado cambia
3. **Solicitud de documentacion**: En estado subsanacion
4. **Resolucion**: Al resolver favorable o desfavorablemente

Las notificaciones pueden enviarse por:
- Email
- Notificaciones internas del sistema

## Integraciones

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| multimedia | Contenido | Adjuntar archivos multimedia |
| biblioteca | Contenido | Vincular documentos de biblioteca |
| comunidades | Ambito | Tramites por comunidad |
| socios | Usuario | Tramites para socios |
| reservas | Funcionalidad | Citas para tramites presenciales |

## Caracteristicas Destacadas

### Formularios Dinamicos
- Campos configurables por tipo de tramite
- Tipos: texto, email, select, checkbox, file, fecha, DNI, IBAN
- Validaciones personalizadas con regex
- Campos condicionales (mostrar segun valor de otro campo)

### Gestion Documental
- Upload seguro con hash SHA256
- Organizacion por expediente
- Control de tipos MIME permitidos
- Limite de tamano configurable
- Visibilidad configurable (admin/solicitante)

### Timeline/Historial
- Registro automatico de todas las acciones
- Timeline visual para el ciudadano
- Historial interno para administradores
- Filtro de eventos publicos/privados

### Consulta Publica
- Consulta por numero de expediente sin login
- Vista simplificada del estado
- Timeline de eventos publicos

### Multicanal
- Tramitacion online completa
- Soporte para tramites presenciales
- Citas previas integradas
- Registro de canal de entrada
