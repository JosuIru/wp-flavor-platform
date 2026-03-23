# Modulo: Campanias y Acciones Colectivas

> Sistema de coordinacion de campanias ciudadanas, protestas, recogida de firmas y acciones colectivas

## Descripcion

Plataforma completa para coordinar campanias ciudadanas incluyendo protestas, recogidas de firmas, concentraciones, boicots, denuncias publicas, sensibilizacion y acciones legales. Permite a los usuarios crear campanias, recoger firmas, programar acciones y coordinar participantes con diferentes roles.

## Archivos Principales

```
includes/modules/campanias/
├── class-campanias-module.php          # Modulo principal
├── class-campanias-dashboard-tab.php   # Tabs del dashboard de usuario
├── frontend/
│   └── class-campanias-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                   # Vista dashboard
│   ├── listado.php                     # Listado de campanias
│   ├── detalle.php                     # Detalle de campania
│   ├── crear.php                       # Formulario creacion
│   ├── firmar.php                      # Formulario de firma
│   ├── mis-campanias.php               # Campanias del usuario
│   ├── mapa.php                        # Mapa de acciones
│   ├── calendario.php                  # Calendario de acciones
│   └── estadisticas.php                # Estadisticas
└── assets/
    ├── css/
    │   ├── campanias.css
    │   └── campanias-dashboard.css
    └── js/
        ├── campanias.js
        └── campanias-dashboard.js
```

## Tablas de Base de Datos

### wp_flavor_campanias
Tabla principal de campanias ciudadanas.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| titulo | varchar(255) | Titulo de la campania |
| descripcion | longtext | Descripcion detallada |
| tipo | enum | protesta/recogida_firmas/concentracion/boicot/denuncia_publica/sensibilizacion/accion_legal/otra |
| estado | enum | planificada/activa/pausada/completada/cancelada |
| objetivo_descripcion | text | Descripcion del objetivo |
| objetivo_firmas | int | Meta de firmas a conseguir |
| firmas_actuales | int | Firmas recogidas |
| fecha_inicio | date | Fecha de inicio |
| fecha_fin | date | Fecha de fin |
| ubicacion | varchar(255) | Ubicacion textual |
| latitud | decimal(10,8) | Coordenada geografica |
| longitud | decimal(11,8) | Coordenada geografica |
| imagen | varchar(255) | URL imagen principal |
| documentos | text | Documentos adjuntos (JSON) |
| enlaces_externos | text | Enlaces a recursos externos |
| hashtags | varchar(255) | Hashtags para redes sociales |
| colectivo_id | bigint(20) | FK al colectivo organizador |
| comunidad_id | bigint(20) | FK a la comunidad |
| creador_id | bigint(20) | FK al usuario creador |
| visibilidad | enum | publica/miembros/privada |
| destacada | tinyint(1) | Campania destacada |
| created_at | datetime | Fecha de creacion |
| updated_at | datetime | Ultima actualizacion |

**Indices:** creador_id, tipo, estado, colectivo_id, comunidad_id, fecha_inicio

### wp_flavor_campanias_participantes
Participantes en campanias con roles.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| campania_id | bigint(20) | FK a campania |
| user_id | bigint(20) | FK a usuario |
| rol | enum | organizador/coordinador/colaborador/firmante/asistente |
| estado | enum | confirmado/pendiente/cancelado |
| tareas_asignadas | text | Tareas del participante |
| notas | text | Notas adicionales |
| fecha_union | datetime | Fecha de union |

**Indices:** campania_id, user_id, rol
**Unique:** campania_id + user_id

### wp_flavor_campanias_acciones
Acciones y eventos de las campanias.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| campania_id | bigint(20) | FK a campania |
| titulo | varchar(255) | Titulo de la accion |
| descripcion | text | Descripcion |
| tipo | enum | concentracion/manifestacion/charla/taller/difusion/reunion/entrega_firmas/rueda_prensa/otra |
| fecha | datetime | Fecha y hora |
| ubicacion | varchar(255) | Ubicacion |
| latitud | decimal(10,8) | Coordenada |
| longitud | decimal(11,8) | Coordenada |
| punto_encuentro | varchar(255) | Punto de encuentro |
| materiales_necesarios | text | Lista de materiales |
| responsable_id | bigint(20) | FK al responsable |
| asistentes_esperados | int | Asistentes esperados |
| asistentes_confirmados | int | Asistentes confirmados |
| estado | enum | programada/en_curso/completada/cancelada |
| resultado | text | Resultado de la accion |
| created_at | datetime | Fecha de creacion |

**Indices:** campania_id, fecha, estado

### wp_flavor_campanias_actualizaciones
Actualizaciones y noticias de las campanias.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| campania_id | bigint(20) | FK a campania |
| titulo | varchar(255) | Titulo |
| contenido | longtext | Contenido de la actualizacion |
| tipo | enum | noticia/logro/problema/llamamiento/media |
| imagen | varchar(255) | Imagen adjunta |
| autor_id | bigint(20) | FK al autor |
| destacada | tinyint(1) | Es destacada |
| created_at | datetime | Fecha de creacion |

**Indices:** campania_id, autor_id, created_at

### wp_flavor_campanias_firmas
Firmas recogidas para campanias.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| campania_id | bigint(20) | FK a campania |
| user_id | bigint(20) | FK a usuario (opcional) |
| nombre | varchar(200) | Nombre del firmante |
| email | varchar(200) | Email del firmante |
| dni_hash | varchar(64) | Hash del DNI (verificacion) |
| localidad | varchar(100) | Localidad del firmante |
| comentario | text | Comentario de apoyo |
| visible | tinyint(1) | Firma visible publicamente |
| verificada | tinyint(1) | Firma verificada |
| ip_hash | varchar(64) | Hash de IP |
| created_at | datetime | Fecha de firma |

**Indices:** campania_id, user_id, created_at

## Tipos de Campania

| Tipo | Descripcion | Color |
|------|-------------|-------|
| `protesta` | Protesta / Manifestacion | Rojo |
| `recogida_firmas` | Recogida de Firmas | Azul |
| `concentracion` | Concentracion | Morado |
| `boicot` | Boicot | Naranja |
| `denuncia_publica` | Denuncia Publica | Amarillo |
| `sensibilizacion` | Sensibilizacion | Verde |
| `accion_legal` | Accion Legal | Indigo |
| `otra` | Otra | Gris |

## Estados de Campania

| Estado | Descripcion | Color |
|--------|-------------|-------|
| `planificada` | Campania en preparacion | Gris |
| `activa` | Campania en curso | Verde |
| `pausada` | Temporalmente detenida | Amarillo |
| `completada` | Finalizada con exito | Azul |
| `cancelada` | Cancelada | Rojo |

## Shortcodes

### Listados y Navegacion

```php
[campanias_listar]
// Lista campanias activas
// - tipo: Filtrar por tipo
// - estado: activa (default)
// - limite: 12
// - columnas: 3
// - comunidad: ID comunidad
// - colectivo: ID colectivo

[campanias_detalle id="123"]
// Muestra detalle de una campania
// - id: ID de campania (o via GET)

[campanias_mis_campanias]
// Campanias del usuario actual

[campanias_mapa]
// Mapa de acciones geolocalizadas

[campanias_calendario]
// Calendario de acciones programadas
```

### Interaccion

```php
[campanias_crear]
// Formulario para crear nueva campania
// Requiere usuario logueado

[campanias_firmar id="123"]
// Formulario de firma
// - id: ID de campania (o via GET)
```

### Dashboard

```php
[flavor_campanias_dashboard]
// Panel completo para el dashboard

[flavor_campanias_destacadas]
// Campanias destacadas
```

## Dashboard Tab

**Clase:** `Flavor_Campanias_Dashboard_Tab`

**Tabs disponibles:**
- `campanias-mis-firmas` - Campanias firmadas
- `campanias-mis-campanias` - Campanias creadas
- `campanias-siguiendo` - Campanias como colaborador

**Frontend Controller:** `Flavor_Campanias_Frontend_Controller`

**KPIs mostrados:**
- Campanias participando
- Total firmas realizadas
- Campanias creadas
- Campanias completadas

## Paginas Dinamicas

| Ruta | Accion | Descripcion |
|------|--------|-------------|
| `/mi-portal/campanias/` | index | Dashboard |
| `/mi-portal/campanias/listado/` | listado | Lista campanias |
| `/mi-portal/campanias/crear/` | crear | Nueva campania |
| `/mi-portal/campanias/mis-campanias/` | mis-campanias | Propias |
| `/mi-portal/campanias/firmar/` | firmar | Firmar campania |
| `/mi-portal/campanias/mapa/` | mapa | Mapa acciones |
| `/mi-portal/campanias/calendario/` | calendario | Calendario |
| `/mi-portal/campanias/detalle/?campania_id=X` | detalle | Ver campania |

## API REST

### Endpoints Publicos

```
GET  /wp-json/flavor/v1/campanias
     ?tipo=recogida_firmas
     ?estado=activa
     ?limite=12

GET  /wp-json/flavor/v1/campanias/{id}

POST /wp-json/flavor/v1/campanias/{id}/firmar
     Body: { nombre, email, localidad?, comentario? }
```

### Endpoints Autenticados

```
POST /wp-json/flavor/v1/campanias/{id}/participar
     // Unirse como colaborador
```

## AJAX Actions

### Publicas (nopriv)

| Action | Descripcion |
|--------|-------------|
| `campanias_firmar` | Registrar firma |
| `campanias_listar` | Listar campanias |
| `campanias_obtener` | Obtener detalle |

### Autenticadas

| Action | Descripcion |
|--------|-------------|
| `campanias_crear` | Crear campania |
| `campanias_actualizar` | Actualizar campania |
| `campanias_participar` | Unirse a campania |
| `campanias_abandonar` | Dejar campania |
| `campanias_crear_accion` | Crear accion programada |
| `campanias_publicar_actualizacion` | Publicar novedad |
| `campanias_confirmar_asistencia` | Confirmar asistencia accion |
| `campanias_dashboard_retirar_firma` | Retirar firma |
| `campanias_dashboard_dejar_seguir` | Dejar de seguir |
| `campanias_dashboard_cambiar_estado` | Cambiar estado |

## Roles de Participantes

| Rol | Permisos |
|-----|----------|
| `organizador` | Control total de la campania |
| `coordinador` | Gestionar acciones y participantes |
| `colaborador` | Participar activamente |
| `firmante` | Solo firma de apoyo |
| `asistente` | Asistir a acciones |

## Tipos de Acciones

| Tipo | Descripcion |
|------|-------------|
| `concentracion` | Reunion en lugar publico |
| `manifestacion` | Marcha o desfile |
| `charla` | Charla informativa |
| `taller` | Taller practico |
| `difusion` | Accion de difusion |
| `reunion` | Reunion organizativa |
| `entrega_firmas` | Entrega de firmas |
| `rueda_prensa` | Rueda de prensa |
| `otra` | Otra accion |

## Configuracion

```php
'campanias' => [
    'enabled' => true,
    'requiere_aprobacion' => true,
    'tipos_permitidos' => [
        'protesta', 'recogida_firmas', 'concentracion',
        'boicot', 'denuncia_publica', 'sensibilizacion',
        'accion_legal', 'otra'
    ],
    'permitir_firmas_anonimas' => false,
    'verificar_email_firmas' => true,
    'max_campanias_por_usuario' => 10,
    'notificar_nuevas_campanias' => true,
    'mostrar_mapa_acciones' => true,
]
```

## Hooks y Filtros

### Actions

```php
// Campania creada
do_action('flavor_campania_creada', $campania_id, $creador_id);

// Campania actualizada
do_action('flavor_campania_actualizada', $campania_id, $datos);

// Nueva firma registrada
do_action('flavor_campania_firma_registrada', $campania_id, $firma_id, $user_id);

// Participante agregado
do_action('flavor_campania_participante_agregado', $campania_id, $user_id, $rol);

// Accion programada
do_action('flavor_campania_accion_creada', $accion_id, $campania_id);

// Estado cambiado
do_action('flavor_campania_estado_cambiado', $campania_id, $nuevo_estado, $anterior);

// Objetivo de firmas alcanzado
do_action('flavor_campania_objetivo_alcanzado', $campania_id, $total_firmas);
```

### Filters

```php
// Tipos de campania disponibles
apply_filters('flavor_campanias_tipos', $tipos);

// Validar datos de campania
apply_filters('flavor_campanias_validar_datos', $valido, $datos);

// Maximo campanias por usuario
apply_filters('flavor_campanias_max_por_usuario', $max, $user_id);

// Campos del formulario de firma
apply_filters('flavor_campanias_campos_firma', $campos, $campania_id);

// Datos antes de guardar firma
apply_filters('flavor_campanias_pre_firma', $datos, $campania_id);
```

## Integracion IA

### Acciones disponibles

```php
$acciones = [
    'listar_campanias' => [
        'descripcion' => 'Lista las campanias activas',
        'parametros' => ['tipo', 'estado', 'limite'],
    ],
    'ver_campania' => [
        'descripcion' => 'Muestra detalle de una campania',
        'parametros' => ['campania_id'],
    ],
    'crear_campania' => [
        'descripcion' => 'Crea una nueva campania',
        'parametros' => ['titulo', 'descripcion', 'tipo', 'objetivo_firmas'],
    ],
    'firmar_campania' => [
        'descripcion' => 'Firma una campania',
        'parametros' => ['campania_id', 'nombre', 'email'],
    ],
    'participar_campania' => [
        'descripcion' => 'Unirse como participante',
        'parametros' => ['campania_id'],
    ],
];
```

### Tool Definitions

```php
[
    'name' => 'campanias_listar',
    'description' => 'Lista las campanias ciudadanas activas',
],
[
    'name' => 'campanias_firmar',
    'description' => 'Firma una campania de recogida de firmas',
]
```

## Panel de Administracion

### Paginas Admin

| Slug | Titulo | Descripcion |
|------|--------|-------------|
| `campanias-dashboard` | Dashboard | Resumen general |
| `campanias-listado` | Listado | Gestion de campanias |
| `campanias-firmas` | Firmas | Gestion de firmas |
| `campanias-estadisticas` | Estadisticas | Metricas y graficos |
| `campanias-config` | Configuracion | Ajustes del modulo |

### Estadisticas Admin

- Total campanias
- Campanias activas
- Total firmas
- Campanias exitosas (alcanzaron objetivo)
- Firmas hoy / esta semana
- Top campanias por firmas
- Tendencia de firmas (7 dias)
- Proximas acciones programadas

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| colectivos | Organizador | Campanias de colectivos |
| comunidades | Ambito | Campanias comunitarias |
| participacion | Complemento | Participacion ciudadana |
| transparencia | Publicacion | Resultados publicos |
| eventos | Acciones | Sincronizar acciones como eventos |
| red-social | Difusion | Compartir campanias |

## FAQs

**Como puedo crear una campania?**
Ve a la seccion de campanias y usa el formulario de creacion. Necesitas estar registrado como usuario.

**Como firmo una campania?**
En la pagina de detalle de la campania encontraras el formulario de firma. Solo necesitas tu nombre y email.

**Puedo organizar acciones dentro de una campania?**
Si, como organizador o coordinador puedes programar concentraciones, charlas, entregas de firmas y otras acciones.

**Como se verifican las firmas?**
Por defecto se verifica el email del firmante. Se puede habilitar verificacion adicional via DNI o censo.

**Puedo retirar mi firma?**
Si, desde tu dashboard en la seccion "Mis Firmas" puedes retirar firmas que hayas dado.

## Principios Gailu

Este modulo implementa los principios de:

- **Participacion**: Herramientas para la movilizacion ciudadana
- **Democracia directa**: Los ciudadanos proponen y apoyan causas
- **Accion colectiva**: Coordinacion de acciones y eventos
- **Transparencia**: Seguimiento publico del progreso de campanias
- **Empoderamiento**: Cualquier usuario puede iniciar una campania
