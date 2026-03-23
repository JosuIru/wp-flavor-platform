# Módulo: Reservas

Gestión genérica de reservas para múltiples tipos de negocio.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `reservas` |
| Versión | 1.1.0 |
| Categoría | Servicios |
| Principios Gailu | Economía local |

## Descripción

El módulo RESERVAS proporciona un sistema genérico de reservas adaptable a distintos negocios: mesas de restaurante, espacios coworking, clases deportivas, citas, etc. Incluye calendario de disponibilidad, confirmación automática y herramientas de gestión.

## Tablas de Base de Datos

### `wp_flavor_reservas`
Reservas realizadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| recurso_id | bigint | FK a recursos |
| usuario_id | bigint | Usuario WP |
| tipo_servicio | varchar | mesa_restaurante, espacio_coworking, clase_deportiva |
| nombre_cliente | varchar | Nombre |
| email_cliente | varchar | Email |
| telefono_cliente | varchar | Teléfono |
| fecha_reserva | date | Fecha |
| hora_inicio | time | Hora inicio |
| hora_fin | time | Hora fin |
| num_personas | int | Personas |
| estado | varchar | pendiente, confirmada, cancelada, completada |
| notas | text | Notas |

### `wp_flavor_reservas_recursos`
Recursos disponibles para reservar.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar | Nombre |
| tipo | varchar | Tipo de recurso |
| categoria | varchar | Categoría |
| descripcion | text | Descripción |
| ubicacion | varchar | Ubicación |
| capacidad | int | Capacidad máxima |
| imagen | varchar | URL imagen |
| estado | varchar | activo/inactivo |

## Endpoints API REST

**Namespace:** `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/reservas` | Listar reservas |
| GET | `/reservas/{id}` | Obtener reserva |
| POST | `/reservas` | Crear reserva |
| PUT | `/reservas/{id}` | Modificar reserva |
| POST | `/reservas/{id}/cancelar` | Cancelar |
| GET | `/reservas/disponibilidad` | Consultar disponibilidad |
| GET | `/reservas/config` | Configuración |

### Parámetros POST `/reservas`

```json
{
  "tipo_servicio": "mesa_restaurante",
  "nombre_cliente": "Juan García",
  "email_cliente": "juan@email.com",
  "telefono_cliente": "600123456",
  "fecha_reserva": "2026-04-15",
  "hora_inicio": "20:00",
  "hora_fin": "22:00",
  "num_personas": 4,
  "notas": "Mesa junto a ventana"
}
```

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[reservas_recursos]` | Lista de recursos |
| `[reservas_calendario]` | Calendario disponibilidad |
| `[reservas_formulario]` | Formulario crear reserva |
| `[reservas_mis_reservas]` | Mis reservas |
| `[reservas_cancelar]` | Formulario cancelar |
| `[reservas_disponibilidad]` | Checker disponibilidad |

## Configuración

```php
[
    'hora_apertura' => '09:00',
    'hora_cierre' => '22:00',
    'duracion_por_defecto' => 60,  // minutos
    'capacidad_maxima' => 50,
    'dias_antelacion' => 30,
    'tipos_servicio' => [
        'mesa_restaurante' => 'Mesa de Restaurante',
        'espacio_coworking' => 'Espacio Coworking',
        'clase_deportiva' => 'Clase Deportiva',
    ],
    'estados_reserva' => [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'completada' => 'Completada',
    ],
]
```

## Tipos de Servicio

| Tipo | Descripción |
|------|-------------|
| mesa_restaurante | Mesas de restaurante |
| espacio_coworking | Espacios de trabajo |
| clase_deportiva | Clases y actividades |

## Estados de Reserva

| Estado | Descripción |
|--------|-------------|
| pendiente | Esperando confirmación |
| confirmada | Reserva confirmada |
| cancelada | Cancelada |
| completada | Completada |

## Dashboard

El dashboard muestra:
- Total de reservas
- Reservas de hoy
- Reservas activas/pendientes
- Confirmadas del mes
- Canceladas del mes
- Completadas del mes
- Pendientes vencidas

## Herramientas Chat IA

| Herramienta | Descripción |
|-------------|-------------|
| `reservas_crear_reserva` | Crear nueva reserva |
| `reservas_cancelar_reserva` | Cancelar reserva |
| `reservas_mis_reservas` | Listar reservas |
| `reservas_disponibilidad` | Verificar disponibilidad |
| `reservas_modificar_reserva` | Modificar reserva |

## Dashboard Tabs Usuario

- Reservas activas
- Próximas reservas
- Historial de reservas

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver recursos | Público |
| Ver disponibilidad | Público |
| Crear reserva | Público (con email) |
| Cancelar reserva | Propietario |
| Gestionar | `manage_options` |

## AJAX Actions

- `reservas_crear`
- `reservas_cancelar`
- `reservas_disponibilidad`
