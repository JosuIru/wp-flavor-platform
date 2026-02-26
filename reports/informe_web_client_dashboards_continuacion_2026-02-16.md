# Continuación auditoría web cliente (dashboards y enlaces)
Fecha: 2026-02-16
Entorno: `http://localhost:10028`

## Confirmaciones
- CTA runtime en `/mi-portal/`: **38** validados, **0** rotos en mismo host.
- Resultado: los botones `Ver más` del dashboard principal funcionan hacia `/mi-portal/{modulo}/`.

## Incidencias encontradas
- Rutas de dashboard definidas en `class-portal-shortcodes` con fallo: **7**
- `/ayuda-vecinal/solicitar/` -> 200 `NOT_FOUND` (Ayuda vecinal - sitio prueba)
- `/banco-tiempo/ofrecer/` -> 200 `NOT_FOUND` (Banco tiempo - sitio prueba)
- `/eventos/crear/` -> 200 `NOT_FOUND` (Eventos - sitio prueba)
- `/grupos-consumo/productos/` -> 200 `NOT_FOUND` (Grupos consumo - sitio prueba)
- `/incidencias/crear/` -> 200 `NOT_FOUND` (Incidencias - sitio prueba)
- `/servicios/` -> 404 `NOT_FOUND` (Page not found &#8211; sitio prueba)
- `/talleres/crear/` -> 200 `NOT_FOUND` (Talleres - sitio prueba)

- Placeholders/anchors rotos en templates cliente: **126** casos ALTO.
Top archivos:
- `includes/templates/components/biblioteca/generos-nav.php`: 18
- `templates/frontend/bicicletas-compartidas/archive.php`: 6
- `templates/frontend/compostaje/single.php`: 6
- `templates/frontend/clientes/archive.php`: 6
- `templates/frontend/empresarial/archive.php`: 6
- `templates/frontend/multimedia/single.php`: 5
- `templates/frontend/socios/archive.php`: 5
- `templates/frontend/bicicletas-compartidas/single.php`: 5
- `templates/frontend/red-social/single.php`: 5
- `includes/templates/components/talleres/talleres-grid.php`: 5
- `templates/frontend/empresarial/single.php`: 4
- `templates/frontend/colectivos/single.php`: 3
- `templates/frontend/bicicletas/single.php`: 3
- `templates/frontend/radio/single.php`: 3
- `templates/frontend/biblioteca/single.php`: 3

## Lectura operativa
- El problema de “Ver más no lleva a ningún lado” no está en el dashboard base `/mi-portal/`; está en rutas legacy sin prefijo `/mi-portal` y en plantillas frontend con `href="#"`.
- Riesgo UX alto en páginas de archivo/single que muestran navegación o paginación simulada sin destino.

## Evidencia
- `reports/auditoria_web_client_dashboard_defined_routes_10028_2026-02-16.csv`
- `reports/auditoria_web_mi_portal_cta_links_10028_2026-02-16.csv`
- `reports/auditoria_web_client_anchor_links_2026-02-16.csv`
- `reports/auditoria_web_client_dashboards_continuacion_2026-02-16.csv`
