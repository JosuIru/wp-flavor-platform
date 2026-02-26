# Auditoria v5 - Dashboards cliente web (pantallas faltantes y enlaces sin destino)
Fecha: 2026-02-16
Entorno base auditado: `http://localhost:10028` (evidencia runtime previa)

## Estado de verificacion en esta corrida
- `localhost:10028` y `localhost:10003` no respondieron en esta sesión (connection refused).
- Este informe consolida la captura profunda más reciente (`auditoria_web_mi_portal_links_deep_10028_2026-02-16.csv`) y rutas definidas de dashboard.

## Resumen ejecutivo
- Hallazgos fuente-destino: 141
- Severidad: {'CRITICO': 73, 'ALTO': 68}
- Tipología: {'global_link_rot': 73, 'ruta_legacy_sin_prefijo': 54, 'accion_dashboard_sin_pantalla': 14}

## Fallos críticos/recurrentes
- `http://localhost:10028/orders/` -> 38 ocurrencias
- `http://localhost:10028/cookies` -> 35 ocurrencias

## Dashboards cliente más afectados
- `/mi-portal/eventos/` -> 15 enlaces fallando
- `/mi-portal/woocommerce/` -> 10 enlaces fallando
- `/mi-portal/avisos-municipales/` -> 9 enlaces fallando
- `/mi-portal/socios/` -> 7 enlaces fallando
- `/mi-portal/carpooling/` -> 4 enlaces fallando
- `/mi-portal/colectivos/` -> 4 enlaces fallando
- `/mi-portal/comunidades/` -> 4 enlaces fallando
- `/mi-portal/fichaje-empleados/` -> 4 enlaces fallando
- `/mi-portal/ayuda-vecinal/` -> 3 enlaces fallando
- `/mi-portal/banco-tiempo/` -> 3 enlaces fallando
- `/mi-portal/bares/` -> 3 enlaces fallando
- `/mi-portal/biblioteca/` -> 3 enlaces fallando
- `/mi-portal/bicicletas-compartidas/` -> 3 enlaces fallando
- `/mi-portal/chat-grupos/` -> 3 enlaces fallando
- `/mi-portal/chat-interno/` -> 3 enlaces fallando

## Brechas funcionales detectadas (pantallas faltantes)
- Acciones rápidas del dashboard principal apuntan a rutas no publicadas (`/eventos/crear/`, `/talleres/crear/`, `/ayuda-vecinal/solicitar/`, `/banco-tiempo/ofrecer/`, `/grupos-consumo/productos/`, `/incidencias/crear/`, `/servicios/`).
- En dashboards por módulo se usan rutas legacy sin prefijo portal (`/colectivos/crear/`, `/crear-comunidad/`, `/publicar-viaje/`, `/fichaje/solicitar-correccion/`, `/eventos/inscribirse/?evento_id=*`).
- Enlaces transversales repetidos (`/orders/`, `/cookies`) generan ruido y bloquean navegación en múltiples pantallas cliente.

## Evidencia
- `reports/auditoria_web_dashboards_cliente_pantallas_faltantes_v5_2026-02-16.csv`
- `reports/auditoria_web_mi_portal_links_deep_10028_2026-02-16.csv`
- `reports/auditoria_web_client_dashboard_defined_routes_10028_2026-02-16.csv`
