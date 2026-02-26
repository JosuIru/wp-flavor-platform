# Auditoria web cliente v4 (dashboards y CTAs)
Fecha: 2026-02-16
Alcance: validacion runtime de enlaces/botones desde dashboards cliente (`/mi-portal/*`), sin correcciones.

## Resumen por entorno
- http://localhost:10028: login_ok=False, paginas=1, enlaces=1, fallos=1, ver_mas=0, ver_mas_fallos=0
- http://localhost:10003: login_ok=False, paginas=1, enlaces=1, fallos=1, ver_mas=0, ver_mas_fallos=0

## Hallazgos clave
-  -> 2 fallos

## Observaciones
- Se detectan rutas legacy sin prefijo `/mi-portal` invocadas desde pantallas de modulo.
- Se mantienen enlaces transversales rotos recurrentes (`/orders/`, `/cookies`) en multiples dashboards.
- En 10003, si no hay acceso de usuario para dashboard, el barrido refleja solo alcance accesible.

## Evidencia
- `reports/auditoria_web_client_dashboards_cta_runtime_v4_2026-02-16.csv`
- `reports/matriz_enlaces_rotos_dashboards_cliente_v4_2026-02-16.csv`
