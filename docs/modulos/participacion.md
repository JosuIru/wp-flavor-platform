# Módulo: Participación

Sistema integral de participación ciudadana y democracia directa.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `participacion` |
| Versión | 2.0.0 |
| Categoría | Gobernanza |
| Rol | Transversal |
| Principios Gailu | Gobernanza |

## Descripción

El módulo PARTICIPACION permite crear propuestas ciudadanas, votaciones, encuestas, peticiones y debates. Implementa procesos participativos completos con fases definidas, desde la recogida de propuestas hasta la implementación.

## Tablas de Base de Datos

### `wp_flavor_propuestas`
Propuestas ciudadanas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| titulo | varchar(255) | Título |
| descripcion | longtext | Descripción |
| categoria | varchar | urbanismo, movilidad, ambiente, cultura, servicios, etc. |
| proponente_id | bigint | Usuario autor |
| estado | enum | borrador, pendiente_validacion, activa, en_estudio, aprobada, rechazada, implementada |
| tipo | enum | propuesta, consulta, iniciativa, presupuesto |
| votos_favor | int | Votos a favor |
| votos_contra | int | Votos en contra |
| total_apoyos | int | Total apoyos |
| presupuesto_estimado | decimal | Presupuesto EUR |
| ubicacion_lat/lng | decimal | Coordenadas |
| respuesta_oficial | text | Respuesta gobierno |

### `wp_flavor_votaciones`
Votaciones y consultas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| titulo | varchar | Título |
| tipo | enum | referendum, consulta, encuesta, presupuesto |
| estado | enum | programada, activa, finalizada, cancelada |
| fecha_inicio | datetime | Inicio votación |
| fecha_fin | datetime | Fin votación |
| opciones | longtext | JSON de opciones |
| es_anonima | tinyint | Votación anónima |
| quorum_minimo | int | Quórum requerido |

### `wp_flavor_votos`
Registro de votos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| votacion_id | bigint | FK a votación |
| propuesta_id | bigint | FK a propuesta |
| usuario_id | bigint | Votante |
| voto | varchar | Opción seleccionada |
| hash_verificacion | varchar | Hash de verificación |

### `wp_flavor_apoyos`
Apoyos/firmas a propuestas.

### `wp_flavor_comentarios_propuesta`
Comentarios y debates.

### `wp_flavor_fases_participacion`
Fases del proceso participativo.

## Endpoints API REST

**Namespace:** `flavor-chat/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/participacion/propuestas` | Listar propuestas |
| GET | `/participacion/propuestas/{id}` | Detalle propuesta |
| GET | `/participacion/votaciones` | Listar votaciones |
| GET | `/participacion/estadisticas` | Estadísticas |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[propuestas_activas]` | Lista de propuestas |
| `[crear_propuesta]` | Formulario crear propuesta |
| `[votacion_activa]` | Widget de votación |
| `[resultados_participacion]` | Resultados |
| `[fases_participacion]` | Timeline de fases |
| `[presupuesto_participativo]` | Vista presupuestos |
| `[detalle_propuesta]` | Detalle propuesta |
| `[participacion_encuestas]` | Encuestas |
| `[participacion_peticiones]` | Peticiones |
| `[participacion_debates]` | Debates |

## Fases del Proceso

| Fase | Duración Default | Descripción |
|------|------------------|-------------|
| Recogida | 30 días | Recogida de propuestas |
| Debate | 15 días | Debate ciudadano |
| Votación | 15 días | Votación |
| Evaluación | 30 días | Evaluación técnica |
| Implementación | 90 días | Implementación |

## Configuración

```php
[
    'requiere_verificacion' => true,
    'votos_necesarios_propuesta' => 10,
    'permite_propuestas_ciudadanas' => true,
    'moderacion_propuestas' => true,
    'duracion_votacion_dias' => 7,
    'quorum_minimo' => 0,
    'max_propuestas_usuario_mes' => 5,
    'permitir_comentarios' => true,
    'presupuesto_participativo_activo' => false,
    'presupuesto_total_anual' => 100000,
    'max_presupuesto_propuesta' => 50000,
]
```

## Categorías de Propuestas

- urbanismo
- movilidad
- medio_ambiente
- cultura
- servicios
- seguridad
- educacion
- deportes
- social
- economia
- tecnologia
- otros

## Integraciones

El módulo gobierna:
- `energia_comunitaria`
- `comunidades`
- `presupuestos_participativos`

Acepta integraciones de:
- `multimedia`
- `articulos_social`

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver propuestas | Público |
| Ver votaciones | Público |
| Crear propuesta | Usuario autenticado |
| Votar | Usuario autenticado |
| Comentar | Usuario autenticado |
| Administrar | `manage_options` |

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_participacion_actualizar_estados` | Cada hora | Actualiza estados |

## AJAX Actions

- `participacion_crear_propuesta`
- `participacion_apoyar_propuesta`
- `participacion_votar`
- `participacion_comentar`
- `participacion_filtrar_propuestas`
