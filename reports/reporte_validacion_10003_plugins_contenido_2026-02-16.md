# Validación en entorno con contenido
Fecha: 2026-02-16
URL: `http://localhost:10003`

## Resumen
- Estado general: **OPERATIVO** para los 2 plugins funcionales (campamentos + calendario/reservas).
- Resultado: **PASS en endpoints críticos de contenido y reserva**.

## Evidencia por plugin

### Basabere Campamentos (`camps/v1`)
- `GET /wp-json/camps/v1/camps` => `success:true`, `total:4` (contenido real).
- `GET /wp-json/camps/v1/camps/1172` => detalle completo con campos de campamento y descripción.

### Calendario/Reservas móvil (`chat-ia-mobile/v1`)
- `GET /wp-json/chat-ia-mobile/v1/public/availability?from=2026-03-01&to=2026-05-31`
  - devuelve múltiples días con estados (`abierto`, `semana-santa`) y colores.
- `GET /wp-json/chat-ia-mobile/v1/public/tickets`
  - devuelve catálogo amplio de tickets (tipos normal/rango/grupo, dependencias y precios).
- Flujo reserva:
  - `POST /reservations/check` => `available:true` para fecha válida (`2026-04-04`).
  - `POST /reservations/prepare` => calcula items/total correctamente.
  - `POST /reservations/add-to-cart` => carrito y checkout URL válidos (`/carrito/`, `/finalizar-compra/`).

## Observación técnica
- `GET /wp-json/app-discovery/v1/info` devuelve `404 rest_no_route` en este entorno.
- Esto no bloquea la operación de los 2 plugins validados aquí porque sus namespaces sí están registrados en `GET /wp-json/`:
  - `camps/v1`
  - `chat-ia-mobile/v1`

## Veredicto
- En `http://localhost:10003`, los dos plugins que mencionaste **funcionan con contenido real** y el flujo de reserva por API está **operativo**.
