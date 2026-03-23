# Modulo: Compostaje Comunitario

> Sistema completo de gestion de compostaje comunitario con gamificacion

## Descripcion

Sistema integral de gestion de puntos de compostaje comunitario que permite administrar composteras, registrar aportaciones de material organico, gestionar turnos de mantenimiento y gamificar la participacion ciudadana. Incluye estadisticas de impacto ambiental (CO2 evitado), ranking de participantes y sistema de niveles para fomentar la participacion.

## Archivos Principales

```
includes/modules/compostaje/
├── class-compostaje-module.php              # Clase principal del modulo
├── class-compostaje-dashboard-tab.php       # Tabs del dashboard usuario
├── frontend/
│   └── class-compostaje-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php                        # Vista dashboard admin
│   ├── composteras.php                      # Gestion de composteras
│   ├── participantes.php                    # Gestion de participantes
│   ├── mantenimiento.php                    # Turnos de mantenimiento
│   └── produccion.php                       # Produccion de compost
├── templates/
│   ├── estadisticas.php                     # Vista estadisticas publicas
│   ├── mapa.php                             # Mapa de composteras
│   ├── mis-aportaciones.php                 # Historial del usuario
│   ├── ranking.php                          # Ranking de participantes
│   └── registrar.php                        # Formulario de aportacion
└── assets/
    ├── css/compostaje.css
    └── js/compostaje.js
```

## Tablas de Base de Datos

### wp_flavor_puntos_compostaje
Tabla principal de puntos/ubicaciones de compostaje.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| nombre | varchar(255) | Nombre del punto |
| descripcion | text | Descripcion del punto |
| direccion | varchar(500) | Direccion completa |
| latitud | decimal(10,7) | Coordenada GPS |
| longitud | decimal(10,7) | Coordenada GPS |
| tipo | enum | comunitario/vecinal/escolar/municipal/privado |
| capacidad_litros | int | Capacidad total en litros |
| num_composteras | int | Numero de composteras |
| nivel_llenado_pct | int | Porcentaje de llenado |
| temperatura_actual | decimal(5,2) | Temperatura medida |
| humedad_actual | int | Humedad medida |
| fase_actual | enum | recepcion/activo/maduracion/listo/mantenimiento |
| fecha_ultima_medicion | datetime | Ultima medicion |
| fecha_inicio_ciclo | datetime | Inicio del ciclo actual |
| fecha_estimada_listo | datetime | Fecha estimada compost listo |
| responsable_id | bigint(20) | FK usuario responsable |
| telefono_contacto | varchar(20) | Telefono contacto |
| email_contacto | varchar(100) | Email contacto |
| horario_apertura | varchar(255) | Horarios de acceso |
| instrucciones_acceso | text | Como acceder |
| materiales_permitidos | text | Lista de materiales |
| foto_url | varchar(500) | Foto del punto |
| estado | enum | activo/inactivo/mantenimiento/cerrado |
| verificado | tinyint(1) | Punto verificado |
| fecha_creacion | datetime | Fecha creacion |
| fecha_actualizacion | datetime | Ultima actualizacion |

**Indices:** ubicacion (lat,lng), estado, tipo, fase_actual, responsable

### wp_flavor_aportaciones_compost
Registro de aportaciones de material organico.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| punto_id | bigint(20) | FK punto de compostaje |
| usuario_id | bigint(20) | FK usuario WordPress |
| tipo_material | enum | Codigo del material |
| categoria_material | enum | verde/marron/especial |
| cantidad_kg | decimal(10,3) | Peso en kg |
| puntos_obtenidos | int | Puntos gamificacion |
| bonus_nivel | int | Bonus por nivel usuario |
| foto_url | varchar(500) | Foto de la aportacion |
| notas | text | Notas adicionales |
| validado | tinyint(1) | Aportacion validada |
| validado_por | bigint(20) | Admin que valido |
| fecha_validacion | datetime | Fecha validacion |
| co2_evitado_kg | decimal(10,3) | CO2 evitado calculado |
| fecha_aportacion | datetime | Fecha de aportacion |

**Indices:** punto_id, usuario_id, fecha_aportacion, tipo_material, validado

### wp_flavor_turnos_compostaje
Turnos de mantenimiento de composteras.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| punto_id | bigint(20) | FK punto |
| tipo_tarea | enum | volteo/riego/medicion/tamizado/limpieza/revision/otro |
| descripcion | text | Descripcion de la tarea |
| fecha_turno | date | Fecha del turno |
| hora_inicio | time | Hora inicio |
| hora_fin | time | Hora fin |
| plazas_disponibles | int | Plazas totales |
| plazas_ocupadas | int | Plazas ocupadas |
| puntos_recompensa | int | Puntos por participar |
| estado | enum | abierto/completo/en_curso/completado/cancelado |
| notas_organizador | text | Notas del organizador |
| creado_por | bigint(20) | FK usuario creador |
| fecha_creacion | datetime | Fecha creacion |

**Indices:** punto_id, fecha_turno, estado, tipo_tarea

### wp_flavor_inscripciones_turno
Inscripciones de usuarios a turnos.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| turno_id | bigint(20) | FK turno |
| usuario_id | bigint(20) | FK usuario |
| estado | enum | inscrito/confirmado/asistio/no_asistio/cancelado |
| puntos_obtenidos | int | Puntos obtenidos |
| notas_usuario | text | Notas del voluntario |
| notas_admin | text | Notas admin |
| fecha_inscripcion | datetime | Fecha inscripcion |
| fecha_confirmacion | datetime | Fecha confirmacion |

**Indices:** turno_id+usuario_id (unique), usuario_id, estado

### wp_flavor_materiales_compostables
Catalogo de materiales compostables.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| codigo | varchar(50) | Codigo unico (slug) |
| nombre | varchar(100) | Nombre material |
| descripcion | text | Descripcion |
| categoria | enum | verde/marron/especial/no_compostable |
| ratio_carbono_nitrogeno | int | Ratio C/N |
| puntos_por_kg | int | Puntos por kg |
| icono | varchar(50) | Icono dashicon |
| consejos | text | Consejos de uso |
| activo | tinyint(1) | Material activo |
| orden | int | Orden de visualizacion |

**Indices:** codigo (unique), categoria, activo

### wp_flavor_estadisticas_compost
Estadisticas agregadas por periodo.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| punto_id | bigint(20) | FK punto (null = global) |
| periodo | enum | diario/semanal/mensual/anual |
| fecha_periodo | date | Fecha del periodo |
| total_kg_aportados | decimal(10,2) | Kg totales |
| total_aportaciones | int | Num aportaciones |
| usuarios_activos | int | Usuarios unicos |
| kg_verdes | decimal(10,2) | Kg verdes |
| kg_marrones | decimal(10,2) | Kg marrones |
| turnos_completados | int | Turnos completados |
| co2_evitado_kg | decimal(10,2) | CO2 evitado |
| puntos_otorgados | int | Puntos totales |
| compost_producido_kg | decimal(10,2) | Compost generado |
| fecha_calculo | datetime | Fecha calculo |

**Indices:** punto_id+periodo+fecha_periodo (unique), fecha_periodo, periodo

### wp_flavor_logros_compostaje
Sistema de logros/insignias de usuarios.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| usuario_id | bigint(20) | FK usuario |
| tipo_logro | varchar(50) | Tipo de logro |
| nivel | int | Nivel del logro |
| descripcion | varchar(255) | Descripcion |
| fecha_obtencion | datetime | Fecha obtencion |

**Indices:** usuario_id+tipo_logro+nivel (unique), usuario_id, tipo_logro

### wp_flavor_solicitudes_compost
Solicitudes de compost maduro.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | bigint(20) | ID unico |
| punto_id | bigint(20) | FK punto |
| usuario_id | bigint(20) | FK usuario |
| cantidad_kg | decimal(10,3) | Cantidad solicitada |
| estado | enum | pendiente/aprobada/entregada/rechazada/cancelada |
| notas_usuario | text | Notas del solicitante |
| notas_admin | text | Notas admin |
| fecha_solicitud | datetime | Fecha solicitud |
| fecha_resolucion | datetime | Fecha resolucion |

**Indices:** punto_id, usuario_id, estado, fecha_solicitud

## Shortcodes

### Shortcodes del Modulo Principal

```php
[mapa_composteras]
// Mapa interactivo con todos los puntos de compostaje
// - altura: altura en px (default: 500)
// - tipo: filtrar por tipo
// - mostrar_filtros: true|false

[registrar_aportacion]
// Formulario para registrar aportaciones
// - punto_id: preseleccionar punto (opcional)

[mis_aportaciones]
// Historial de aportaciones del usuario
// Incluye nivel, puntos y estadisticas

[guia_compostaje]
// Guia visual de que se puede compostar
// - estilo: tarjetas|lista

[ranking_compostaje]
// Ranking de usuarios por kg aportados
// - limite: numero de entradas (default: 10)
// - periodo: total|mes|semana|ano

[estadisticas_compostaje]
// Estadisticas globales de impacto
// Muestra kg totales, CO2 evitado, participantes

[turnos_compostaje]
// Lista de turnos disponibles para inscripcion
// - punto_id: filtrar por punto

[compostaje_cercana]
// Widget: Compostera mas cercana al usuario

[compostaje_mi_balance]
[flavor_compostaje_mi_balance]
// Widget: Balance personal (kg, aportaciones)
```

### Shortcodes del Frontend Controller

```php
[flavor_compostaje_mapa]
// Mapa interactivo con Leaflet

[flavor_compostaje_puntos]
// Lista de puntos de compostaje

[flavor_compostaje_registrar]
// Formulario de registro de aportacion

[flavor_compostaje_mis_aportaciones]
// Historial personal

[flavor_compostaje_turnos]
// Turnos disponibles

[flavor_compostaje_guia]
// Guia de compostaje

[flavor_compostaje_ranking]
// Ranking de participantes

[flavor_compostaje_dashboard]
// Dashboard completo del usuario
```

## Dashboard Tabs (Area Usuario)

**Clase:** `Flavor_Compostaje_Dashboard_Tab`

**Tabs disponibles en Mi Portal:**
- `compostaje-mis-aportes` - Historial de aportaciones del usuario
- `compostaje-mi-balance` - Puntos acumulados y kg compostados
- `compostaje-turnos` - Turnos de mantenimiento asignados
- `compostaje-ranking` - Posicion en el ranking comunitario

## Sistema de Gamificacion

### Niveles de Usuario

| Nivel | Nombre | Kg Minimos | Bonus Puntos | Icono |
|-------|--------|------------|--------------|-------|
| 1 | Semilla | 0 | 0 | seedling |
| 2 | Brote | 10 | +5 pts/kg | leaf |
| 3 | Planta | 50 | +10 pts/kg | plant |
| 4 | Arbol | 150 | +15 pts/kg | tree |
| 5 | Bosque | 500 | +25 pts/kg | forest |
| 6 | Ecosistema | 1000 | +50 pts/kg | globe |

### Tipos de Logros

| Logro | Nivel | Condicion |
|-------|-------|-----------|
| primera_aportacion | 1 | 1 aportacion |
| aportaciones_10 | 1 | 10 aportaciones |
| aportaciones_50 | 2 | 50 aportaciones |
| aportaciones_100 | 3 | 100 aportaciones |
| kg_10 | 1 | 10 kg aportados |
| kg_50 | 2 | 50 kg aportados |
| kg_100 | 3 | 100 kg aportados |
| kg_500 | 4 | 500 kg aportados |

### Materiales y Puntos

**Materiales Verdes (ricos en nitrogeno):**
| Material | Puntos/kg | Ratio C/N |
|----------|-----------|-----------|
| Frutas y verduras | 5 | 25:1 |
| Posos de cafe | 6 | 20:1 |
| Cesped fresco | 4 | 20:1 |
| Restos de cocina | 5 | 25:1 |
| Plantas verdes | 4 | 25:1 |

**Materiales Marrones (ricos en carbono):**
| Material | Puntos/kg | Ratio C/N |
|----------|-----------|-----------|
| Hojas secas | 6 | 60:1 |
| Papel y carton | 7 | 170:1 |
| Ramas y poda | 5 | 100:1 |
| Serrin | 4 | 400:1 |
| Paja | 5 | 75:1 |

**Materiales Especiales:**
| Material | Puntos/kg |
|----------|-----------|
| Cascaras de huevo | 8 |
| Bolsas de te | 6 |

## API REST

### Endpoints Disponibles

```
GET  /wp-json/flavor-compostaje/v1/puntos
     Obtiene puntos de compostaje
     Params: lat, lng, radio, tipo, estado

GET  /wp-json/flavor-compostaje/v1/puntos/{id}
     Detalle de un punto especifico

POST /wp-json/flavor-compostaje/v1/aportacion
     Registra una aportacion (auth required)
     Params: punto_id, tipo_material, cantidad_kg, notas

GET  /wp-json/flavor-compostaje/v1/usuario/estadisticas
     Estadisticas del usuario actual (auth required)

GET  /wp-json/flavor-compostaje/v1/turnos
     Lista turnos disponibles
     Params: punto_id, desde, hasta

POST /wp-json/flavor-compostaje/v1/turno/inscribir
     Inscribirse en un turno (auth required)
     Params: turno_id

GET  /wp-json/flavor-compostaje/v1/estadisticas/globales
     Estadisticas globales de compostaje

GET  /wp-json/flavor-compostaje/v1/ranking
     Ranking de usuarios
     Params: limite, periodo (total|mes|semana|ano)

GET  /wp-json/flavor-compostaje/v1/materiales
     Catalogo de materiales compostables
```

## Panel de Administracion

**Paginas admin disponibles:**

| Pagina | Descripcion |
|--------|-------------|
| `compostaje-dashboard` | Dashboard con estadisticas globales |
| `compostaje-composteras` | Gestion de puntos de compostaje |
| `compostaje-participantes` | Usuarios activos y sus aportaciones |
| `compostaje-mantenimiento` | Turnos de mantenimiento |
| `compostaje-produccion` | Produccion y recogida de compost |

## Configuracion

```php
'compostaje' => [
    'enabled' => true,
    'disponible_app' => 'cliente',
    'permite_recoger_compost' => true,
    'kg_minimos_recogida' => 5,
    'puntos_por_kg_depositado' => 5,
    'puntos_por_turno_mantenimiento' => 50,
    'sistema_turnos_volteo' => true,
    'notificar_compost_listo' => true,
    'notificar_turno_asignado' => true,
    'dias_aviso_turno' => 2,
    'max_kg_por_deposito' => 10,
    'permitir_fotos_deposito' => true,
    'validacion_admin_requerida' => false,
    'mostrar_ranking' => true,
    'co2_por_kg_organico' => 0.5,
]
```

## Acciones AJAX

```php
// Registrar aportacion
wp_ajax_compostaje_registrar_aportacion

// Apuntarse a turno
wp_ajax_compostaje_apuntarse_turno

// Consultar estado punto
wp_ajax_compostaje_consultar_estado
wp_ajax_nopriv_compostaje_consultar_estado

// Obtener puntos usuario
wp_ajax_compostaje_obtener_puntos

// Mis aportaciones paginadas
wp_ajax_compostaje_mis_aportaciones

// Cancelar inscripcion turno
wp_ajax_compostaje_cancelar_turno

// Completar turno (admin)
wp_ajax_compostaje_completar_turno

// Frontend Controller AJAX
wp_ajax_flavor_compostaje_dashboard
wp_ajax_flavor_compostaje_registrar
wp_ajax_flavor_compostaje_inscribir_turno
wp_ajax_flavor_compostaje_cancelar_inscripcion
wp_ajax_flavor_compostaje_buscar_puntos
wp_ajax_nopriv_flavor_compostaje_buscar_puntos
wp_ajax_flavor_compostaje_solicitar_compost
```

## Tipos de Puntos de Compostaje

| Tipo | Descripcion |
|------|-------------|
| `comunitario` | Gestionado por la comunidad |
| `vecinal` | De una comunidad de vecinos |
| `escolar` | En centros educativos |
| `municipal` | Gestionado por el ayuntamiento |
| `privado` | Gestion privada |

## Fases del Compostaje

| Fase | Descripcion |
|------|-------------|
| `recepcion` | Aceptando aportaciones |
| `activo` | En proceso de descomposicion activa |
| `maduracion` | Periodo de maduracion |
| `listo` | Compost listo para recoger |
| `mantenimiento` | En mantenimiento, no acepta aportaciones |

## Tipos de Tareas de Mantenimiento

| Tarea | Descripcion | Puntos |
|-------|-------------|--------|
| `volteo` | Voltear el material | 50 |
| `riego` | Humedecer si es necesario | 30 |
| `medicion` | Medir temperatura/humedad | 25 |
| `tamizado` | Tamizar compost maduro | 60 |
| `limpieza` | Limpieza general | 40 |
| `revision` | Revision del estado | 20 |
| `otro` | Otras tareas | Variable |

## Cron Jobs

```php
// Notificaciones de turnos proximos (diario)
wp_schedule_event(time(), 'daily', 'flavor_compostaje_notificaciones_diarias');
```

## Calculo de Impacto Ambiental

- **CO2 Evitado:** `cantidad_kg * 0.5` kg de CO2 por kg de material organico compostado
- **Compost Producido:** Aproximadamente 30% del material aportado

## Vinculaciones con Otros Modulos

| Modulo | Tipo | Descripcion |
|--------|------|-------------|
| comunidades | Contenedor | Composteras de comunidad |
| huertos-urbanos | Relacion | Compost para huertos |
| socios | Membresia | Descuentos para socios |
| eventos | Integracion | Talleres de compostaje |

## Ejemplo de Uso

### Mostrar mapa de composteras
```php
[mapa_composteras altura="600" mostrar_filtros="true"]
```

### Formulario de registro
```php
[registrar_aportacion]
```

### Dashboard del usuario
```php
[flavor_compostaje_dashboard]
```

### Ranking mensual
```php
[ranking_compostaje limite="20" periodo="mes"]
```

### Guia interactiva
```php
[guia_compostaje estilo="tarjetas"]
```

## Principios Gailu

Este modulo implementa los principios:
- **Regeneracion**: Transforma residuos organicos en recurso valioso

Contribuye a:
- **Autonomia**: Reduce dependencia de fertilizantes externos
- **Impacto**: Mide y comunica el impacto ambiental positivo
