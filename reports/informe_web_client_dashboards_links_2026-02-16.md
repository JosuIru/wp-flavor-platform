# Auditoría Web Cliente: dashboards, enlaces y botones
Fecha: 2026-02-16
Entorno validado: `http://localhost:10028`
Alcance: vistas cliente (mi-portal + templates frontend/componentes)

## Resultado ejecutivo
- `/mi-portal/` CTA/`Ver más` validados: **38**
- `/mi-portal/` CTA rotos (mismo host): **0**
- Rutas cliente no-mi-portal evaluadas: **45**
- Rutas no-mi-portal con fallo/404: **43**
- Enlaces anchor `#` detectados en templates cliente: **142**
- Enlaces anchor severidad ALTO: **126**

## Hallazgos clave
1. Los botones `Ver más` del dashboard principal `/mi-portal/` funcionan en general.
- No se detectaron fallos de destino en CTA de `/mi-portal/` (mismo host).
2. Existen rutas legacy/no-prefijo que fallan fuera de `/mi-portal/` (ej. `/servicios/`, `/eventos/crear/`, etc.).
- `/servicios/` -> 404 `NOT_FOUND`
- `/eventos/crear/` -> 200 `NOT_FOUND`
- `/talleres/crear/` -> 200 `NOT_FOUND`
- `/ayuda-vecinal/solicitar/` -> 200 `NOT_FOUND`
- `/banco-tiempo/ofrecer/` -> 200 `NOT_FOUND`
- `/grupos-consumo/productos/` -> 200 `NOT_FOUND`
- `/incidencias/crear/` -> 200 `NOT_FOUND`
- `/talleres/` -> 200 `NOT_FOUND`
- `/eventos/` -> 200 `NOT_FOUND`
- `/ayuda-vecinal/` -> 200 `NOT_FOUND`
- `/banco-tiempo/` -> 200 `NOT_FOUND`
- `/grupos-consumo/` -> 200 `NOT_FOUND`
- `/incidencias/` -> 200 `NOT_FOUND`
- `/woocommerce/` -> 200 `NOT_FOUND`
- `/fichaje-empleados/` -> 200 `NOT_FOUND`
- `/socios/` -> 200 `NOT_FOUND`
- `/participacion/` -> 200 `NOT_FOUND`
- `/presupuestos-participativos/` -> 200 `NOT_FOUND`
- `/avisos-municipales/` -> 200 `NOT_FOUND`
- `/biblioteca/` -> 200 `NOT_FOUND`
- `/bicicletas-compartidas/` -> 200 `NOT_FOUND`
- `/carpooling/` -> 200 `NOT_FOUND`
- `/chat-grupos/` -> 200 `NOT_FOUND`
- `/chat-interno/` -> 200 `NOT_FOUND`
- `/compostaje/` -> 200 `NOT_FOUND`
- ... y 18 rutas adicionales con fallo.
3. Hay un volumen alto de botones/enlaces placeholder en plantillas (`href="#"`) sin destino real.
Top archivos con más enlaces ALTO:
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

## Evidencia
- `reports/auditoria_web_mi_portal_cta_links_10028_2026-02-16.csv`
- `reports/auditoria_web_client_dashboard_routes_10028_2026-02-16.csv`
- `reports/auditoria_web_client_anchor_links_2026-02-16.csv`
