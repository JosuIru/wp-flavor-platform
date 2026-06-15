# Ahorro Rotativo — API móvil

Namespace REST: `flavor-platform/v1`. Base: `/wp-json/flavor-platform/v1/ahorro-rotativo`.
Autenticación: sesión WordPress / JWT de la plataforma (`is_user_logged_in`).
La plataforma **no mueve dinero**: registra y coordina.

## Endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET  | `/circulos` | sí | Círculos del usuario (creados o donde es miembro). |
| POST | `/circulos` | sí | Crea un círculo en borrador. Body: `nombre`, `importe_aportacion`, `num_plazas`, `periodo_dias?`, `modo_orden?` (`fijo`\|`sorteo`). |
| GET  | `/circulos/{id}` | sí (miembro) | Detalle: `circulo`, `miembros` (con `posicion_turno` y `reputacion_porcentaje`), `ronda_actual` (con `aportaciones`). |
| POST | `/circulos/{id}/activar` | sí (creador) | Asigna turnos y genera las rondas. |
| POST | `/circulos/{id}/miembros` | sí (creador) | Invita/añade miembro. Body: `nombre_visible`, `usuario_id?`. Devuelve `codigo_invitacion`. |
| POST | `/unirse` | sí | Acepta una invitación. Body: `codigo_invitacion`. |
| POST | `/aportaciones/{id}/pagar` | sí | El miembro marca su aportación como pagada. |
| POST | `/aportaciones/{id}/confirmar` | sí | El beneficiario confirma la recepción. |

## Reputación
`reputacion_porcentaje` se deriva en el servidor: % de aportaciones confirmadas a tiempo
(antes de `periodo_fin + dias_gracia`). No se almacena; es inmanipulable.

## Estados
- Aportación: `pendiente` → `pagada` → `confirmada`. "Retrasada" se deriva del plazo.
- Círculo: `borrador` → `activo` → `completado` / `cancelado`.
