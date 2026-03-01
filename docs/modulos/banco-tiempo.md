# Módulo: Banco de Tiempo

> Sistema de intercambio de servicios usando tiempo como moneda

## Descripción

Plataforma para el intercambio de servicios entre usuarios donde la moneda es el tiempo. Un usuario ofrece horas de un servicio y recibe horas de otro servicio a cambio, fomentando la economía colaborativa y el apoyo mutuo.

## Archivos Principales

```
includes/modules/banco-tiempo/
├── class-banco-tiempo-module.php        # Clase principal
├── class-banco-tiempo-dashboard-tab.php # Tab dashboard
├── install.php                          # Instalación BD
├── views/
│   └── dashboard.php
└── assets/
```

## CPTs (Custom Post Types)

| CPT | Slug | Descripción |
|-----|------|-------------|
| Servicio | `fc_servicio_bt` | Servicios ofrecidos |

## Tablas de Base de Datos

### wp_flavor_banco_tiempo_servicios
Servicios ofrecidos por usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) | FK usuario |
| titulo | varchar(255) | Nombre servicio |
| descripcion | text | Descripción detallada |
| categoria | varchar(100) | Categoría |
| subcategoria | varchar(100) | Subcategoría |
| horas_estimadas | decimal(5,2) | Horas por sesión |
| modalidad | enum | presencial/online/ambas |
| ubicacion | varchar(255) | Zona de servicio |
| disponibilidad | longtext JSON | Horarios disponibles |
| requisitos | text | Requisitos previos |
| materiales | text | Materiales necesarios |
| imagen | varchar(500) | URL imagen |
| estado | enum | activo/pausado/completado |
| visualizaciones | int | Contador vistas |
| solicitudes_count | int | Nº solicitudes |
| valoracion_media | decimal(3,2) | Valoración promedio |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** usuario_id, categoria, estado

### wp_flavor_banco_tiempo_transacciones
Intercambios de horas entre usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| servicio_id | bigint(20) | FK servicio |
| usuario_solicitante_id | bigint(20) | Quien solicita |
| usuario_prestador_id | bigint(20) | Quien presta |
| horas | decimal(5,2) | Horas intercambiadas |
| descripcion | text | Descripción intercambio |
| estado | enum | pendiente/aceptada/rechazada/completada/cancelada/disputa |
| fecha_solicitud | datetime | Fecha solicitud |
| fecha_aceptacion | datetime | Fecha aceptación |
| fecha_realizacion | datetime | Fecha acordada |
| fecha_completado | datetime | Fecha completado |
| valoracion_solicitante | tinyint | Valoración (1-5) |
| valoracion_prestador | tinyint | Valoración (1-5) |
| comentario_solicitante | text | Comentario |
| comentario_prestador | text | Comentario |
| motivo_rechazo | text | Si rechazado |
| motivo_cancelacion | text | Si cancelado |
| metadata | longtext JSON | Datos adicionales |

**Índices:** servicio_id, usuario_solicitante_id, usuario_prestador_id, estado

### wp_flavor_banco_tiempo_reputacion
Reputación y estadísticas de usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) UNIQUE | FK usuario |
| total_intercambios | int | Nº intercambios |
| horas_dadas | decimal(10,2) | Horas prestadas |
| horas_recibidas | decimal(10,2) | Horas recibidas |
| saldo_horas | decimal(10,2) | Balance actual |
| rating_promedio | decimal(3,2) | Valoración media |
| rating_puntualidad | decimal(3,2) | Puntualidad |
| rating_calidad | decimal(3,2) | Calidad servicio |
| rating_comunicacion | decimal(3,2) | Comunicación |
| estado_verificacion | enum | pendiente/verificado/destacado |
| badges | longtext JSON | Insignias obtenidas |
| nivel | tinyint | Nivel usuario |
| puntos_confianza | int | Puntos acumulados |
| ultima_actividad | datetime | Última actividad |
| created_at | datetime | Fecha registro |
| updated_at | datetime | Última actualización |

### wp_flavor_banco_tiempo_donaciones
Donaciones solidarias de horas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| donante_id | bigint(20) | Quien dona |
| beneficiario_id | bigint(20) | Quien recibe (0=fondo común) |
| tipo | enum | directa/fondo_comun/emergencia |
| horas | decimal(5,2) | Horas donadas |
| motivo | text | Motivo donación |
| estado | enum | pendiente/aceptada/rechazada |
| fecha_donacion | datetime | Fecha |
| fecha_uso | datetime | Cuando se usó |

### wp_flavor_banco_tiempo_valoraciones
Valoraciones detalladas de intercambios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| transaccion_id | bigint(20) | FK transacción |
| valorador_id | bigint(20) | Quien valora |
| valorado_id | bigint(20) | Valorado |
| rol_valorador | enum | solicitante/prestador |
| rating_general | tinyint | Valoración general |
| rating_puntualidad | tinyint | Puntualidad |
| rating_calidad | tinyint | Calidad |
| rating_comunicacion | tinyint | Comunicación |
| comentario | text | Comentario |
| es_publica | tinyint(1) | Visible públicamente |
| created_at | datetime | Fecha |

### wp_flavor_banco_tiempo_limites
Límites y alertas por usuario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| usuario_id | bigint(20) UNIQUE | FK usuario |
| saldo_actual | decimal(10,2) | Saldo horas |
| limite_deuda | decimal(5,2) | Máximo negativo permitido |
| limite_acumulacion | decimal(5,2) | Máximo positivo permitido |
| alerta_activa | tinyint(1) | Tiene alerta |
| tipo_alerta | varchar(50) | Tipo alerta |
| plan_equilibrio | longtext JSON | Plan para equilibrar |
| fecha_revision | datetime | Próxima revisión |

### wp_flavor_banco_tiempo_metricas
Métricas globales del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| periodo | varchar(20) | Identificador período |
| fecha_inicio | date | Inicio período |
| fecha_fin | date | Fin período |
| tipo_periodo | enum | diario/semanal/mensual/anual |
| total_usuarios | int | Usuarios activos |
| total_servicios | int | Servicios publicados |
| total_intercambios | int | Intercambios realizados |
| horas_intercambiadas | decimal(10,2) | Total horas |
| nuevos_usuarios | int | Nuevos registros |
| indice_equidad | decimal(5,2) | Índice distribución |
| categorias_top | longtext JSON | Categorías más activas |
| alertas | longtext JSON | Alertas del período |
| puntuacion_sostenibilidad | tinyint | Score salud sistema |

## Shortcodes

### Catálogo y Servicios

```php
[banco_tiempo_servicios]
// Catálogo de servicios
// - categoria: slug
// - modalidad: presencial|online|ambas
// - orden: recientes|valoracion|popularidad
// - limite: número
// - columnas: 2|3|4

[banco_tiempo_ofrecer]
// Formulario publicar servicio
// - categorias: slugs permitidas

[banco_tiempo_solicitar]
// Solicitar un servicio existente
// - servicio_id: ID
```

### Balance y Perfil

```php
[banco_tiempo_mi_balance]
// Balance de horas del usuario
// - mostrar_historial: true|false
// - mostrar_grafico: true|false

[banco_tiempo_perfil]
// Perfil público del usuario
// - id: ID usuario (o actual)
// - mostrar_servicios: true|false
// - mostrar_valoraciones: true|false

[banco_tiempo_intercambios]
// Historial de intercambios
// - estado: todos|pendientes|completados
// - rol: todos|solicitante|prestador
// - limite: número
```

### Comunidad

```php
[banco_tiempo_ranking]
// Ranking de usuarios
// - tipo: horas_dadas|valoracion|intercambios
// - limite: número

[banco_tiempo_buscar]
// Buscador de servicios
// - placeholder: texto
// - filtros: categoria,modalidad,zona
// - mapa: true|false
```

## Dashboard Tab

**Clase:** `Flavor_Banco_Tiempo_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Panel principal
- `mis-servicios` - Servicios que ofrezco
- `mis-intercambios` - Intercambios activos
- `mi-balance` - Balance de horas
- `publicar` - Nuevo servicio
- `buscar` - Buscar servicios
- `comunidad` - Ranking y estadísticas
- `historial` - Historial completo

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/banco-tiempo/` | index | Dashboard |
| `/mi-portal/banco-tiempo/servicios/` | servicios | Catálogo |
| `/mi-portal/banco-tiempo/ofrecer/` | ofrecer | Publicar servicio |
| `/mi-portal/banco-tiempo/solicitar/` | solicitar | Solicitar servicio |
| `/mi-portal/banco-tiempo/mis-intercambios/` | mis-intercambios | Activos |
| `/mi-portal/banco-tiempo/mi-balance/` | mi-balance | Balance |
| `/mi-portal/banco-tiempo/comunidad/` | comunidad | Ranking |
| `/mi-portal/banco-tiempo/perfil/{id}/` | perfil | Perfil usuario |
| `/mi-portal/banco-tiempo/historial/` | historial | Histórico |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| socios | Membresía | Límites especiales para socios |
| comunidades | Contenedor | BT por comunidad |
| economia-don | Complemento | Alternativa al intercambio |
| huella-ecologica | Datos | Impacto del compartir |
| red-social | Feed | Actividad en timeline |

## Hooks y Filtros

### Actions

```php
// Servicio publicado
do_action('flavor_bt_servicio_publicado', $servicio_id, $usuario_id);

// Intercambio solicitado
do_action('flavor_bt_intercambio_solicitado', $transaccion_id);

// Intercambio completado
do_action('flavor_bt_intercambio_completado', $transaccion_id, $horas);

// Valoración enviada
do_action('flavor_bt_valoracion_enviada', $valoracion_id, $transaccion_id);

// Saldo actualizado
do_action('flavor_bt_saldo_actualizado', $usuario_id, $saldo_nuevo, $saldo_anterior);

// Alerta de límite
do_action('flavor_bt_alerta_limite', $usuario_id, $tipo_alerta);
```

### Filters

```php
// Horas por intercambio
apply_filters('flavor_bt_horas_intercambio', $horas, $servicio_id);

// Límite de deuda
apply_filters('flavor_bt_limite_deuda', $limite, $usuario_id);

// Categorías disponibles
apply_filters('flavor_bt_categorias', $categorias);

// Validar intercambio
apply_filters('flavor_bt_validar_intercambio', $valido, $datos);
```

## Configuración

```php
'banco_tiempo' => [
    'enabled' => true,
    'horas_bienvenida' => 2, // Horas de regalo al registrarse
    'limite_deuda_default' => -10, // Máximo negativo
    'limite_acumulacion_default' => 50, // Máximo positivo
    'horas_minimas_intercambio' => 0.5,
    'horas_maximas_intercambio' => 8,
    'dias_cancelacion_gratuita' => 2,
    'requiere_verificacion' => false,
    'permitir_donaciones' => true,
    'fondo_comun' => true,
    'categorias' => [
        'hogar' => 'Tareas del hogar',
        'tecnologia' => 'Tecnología',
        'idiomas' => 'Idiomas',
        'transporte' => 'Transporte',
        'cuidados' => 'Cuidados',
        'educacion' => 'Educación',
        'arte' => 'Arte y cultura',
        'otros' => 'Otros',
    ],
    'notificaciones' => [
        'solicitud_recibida' => true,
        'solicitud_aceptada' => true,
        'recordatorio_intercambio' => true,
        'valoracion_pendiente' => true,
        'alerta_saldo' => true,
    ],
]
```

## Sistema de Equilibrio

El banco de tiempo implementa mecanismos para mantener el equilibrio:

1. **Límites de saldo**: Evita acumulación excesiva
2. **Alertas automáticas**: Notifica desequilibrios
3. **Planes de equilibrio**: Sugiere acciones
4. **Fondo común**: Para casos especiales
5. **Donaciones solidarias**: Apoyo mutuo

## Insignias (Badges)

| Badge | Requisito |
|-------|-----------|
| Novato | Primera hora intercambiada |
| Activo | 10 intercambios |
| Veterano | 50 intercambios |
| Generoso | 20 horas donadas |
| 5 Estrellas | Media 5.0 con 10+ valoraciones |
| Puntual | 95% puntualidad |
| Embajador | Trae 5 nuevos usuarios |
