# Módulo: Espacios Comunes

Sistema de reserva y gestión de espacios comunitarios.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `espacios_comunes` |
| Versión | 1.0.0 |
| Categoría | Servicios |
| Principios Gailu | Economía local, Cuidados |

## Descripción

El módulo ESPACIOS COMUNES permite gestionar la reserva de espacios compartidos (salas, auditorios, cocinas, terrazas, etc.). Incluye sistema de aprobación, gestión de equipamiento, seguimiento de disponibilidad y características avanzadas de sostenibilidad (Sello de Conciencia).

## Tablas de Base de Datos

### `wp_flavor_espacios_comunes`
Espacios disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar | Nombre del espacio |
| tipo | varchar | salon_eventos, sala_reuniones, cocina, taller, terraza, jardin, gimnasio, ludoteca, piscina, parking |
| capacidad_maxima | int | Personas máximo |
| superficie_m2 | decimal | Metros cuadrados |
| equipamiento | text | JSON de equipos |
| horario_apertura/cierre | time | Horarios |
| precio_hora | decimal | Tarifa |
| requiere_aprobacion | tinyint | Necesita aprobación |
| requiere_deposito | tinyint | Requiere fianza |
| estado | enum | activo, inactivo, mantenimiento |

### `wp_flavor_espacios_reservas`
Reservas de espacios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| espacio_id | bigint | FK a espacio |
| usuario_id | bigint | Solicitante |
| fecha | date | Fecha reserva |
| hora_inicio/fin | time | Horarios |
| estado | enum | pendiente, aprobada, rechazada, cancelada, completada |
| num_asistentes | int | Personas |
| codigo_acceso | varchar | Código único |

### `wp_flavor_espacios_bloqueos`
Bloqueos de disponibilidad.

### `wp_flavor_ec_cesiones`
Cesiones solidarias de reservas (Sello Conciencia).

### `wp_flavor_ec_lista_espera`
Lista de espera para espacios.

### `wp_flavor_ec_consumos`
Registro de consumo energético.

### `wp_flavor_ec_voluntariado`
Tareas de mantenimiento voluntario.

### `wp_flavor_ec_participaciones`
Participaciones en voluntariado.

## Endpoints API REST

**Namespace:** `flavor-chat-ia/v1` y `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/espacios-comunes/tipos` | Tipos de espacios |
| GET | `/espacios` | Listar espacios |
| GET | `/espacios/{id}` | Detalle espacio |
| GET | `/espacios/{id}/disponibilidad` | Slots libres |
| POST | `/espacios/reservas` | Crear reserva |
| GET | `/espacios/reservas` | Mis reservas |
| DELETE | `/espacios/reservas/{id}` | Cancelar reserva |
| POST | `/espacios/reservas/{id}/valorar` | Valorar |
| POST | `/espacios/incidencias` | Reportar problema |
| GET | `/espacios/equipamiento` | Catálogo equipos |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[espacios_listado]` | Grid de espacios |
| `[espacios_detalle]` | Detalle de espacio |
| `[espacios_reservar]` | Formulario reserva |
| `[espacios_mis_reservas]` | Mis reservas |
| `[espacios_calendario]` | Calendario disponibilidad |
| `[espacios_equipamiento]` | Catálogo equipos |
| `[espacios_proxima_reserva]` | Widget próxima reserva |

### Shortcodes Sello Conciencia

| Shortcode | Descripción |
|-----------|-------------|
| `[ec_cesiones_disponibles]` | Reservas cedidas |
| `[ec_huella_espacio]` | Consumo energético |
| `[ec_voluntariado]` | Tareas voluntariado |
| `[ec_dashboard_sostenibilidad]` | Métricas globales |
| `[ec_mi_impacto]` | Mi aporte comunitario |

## Configuración

```php
[
    'requiere_fianza' => true,
    'importe_fianza_predeterminado' => 50,
    'horas_anticipacion_minima' => 24,
    'dias_anticipacion_maxima' => 90,
    'horas_anticipacion_cancelacion' => 24,
    'permite_reservas_recurrentes' => true,
    'duracion_maxima_horas' => 8,
    'notificar_administrador' => true,
    'auto_confirmar_reservas' => false,
]
```

## Tipos de Espacios

- salon_eventos
- sala_reuniones
- cocina
- taller
- terraza
- jardin
- gimnasio
- ludoteca
- piscina
- parking
- otro

## Sello de Conciencia (+5 puntos)

### Uso Solidario
- Cesión de reservas a fondo solidario
- Lista espera prioritaria para vulnerables
- Puntos: 20 (solidaria), 10 (normal)

### Huella de Uso
- Registro de consumos (electricidad, agua, gas)
- Cálculo de CO2 estimado
- Comparativa mensual

### Cuidado Comunitario
- Tareas de mantenimiento/limpieza
- Sistema de puntos por participación
- Niveles de urgencia

## Notificaciones

| Evento | Tipo |
|--------|------|
| Reserva creada | info |
| Reserva aprobada | success |
| Reserva rechazada | warning |
| Recordatorio | info |
| Reserva cancelada | warning |
| Alto consumo | warning |

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver espacios | Público |
| Ver disponibilidad | Público |
| Crear reserva | Usuario autenticado |
| Cancelar reserva | Propietario |
| Aprobar reservas | `manage_options` |
| Registrar consumo | `manage_options` |

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_espacios_actualizar_estados` | Cada hora | Actualizar estados |
| `flavor_espacios_recordatorios` | Diaria | Enviar recordatorios |
| `flavor_ec_check_consumos` | Diaria | Alertas consumo |
