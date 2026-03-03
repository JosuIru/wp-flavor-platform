# Matriz De Funcionalidades Pendientes

Fecha: 2026-03-02

## Criterio

Esta matriz esta basada en revision estatica del codigo actual, no en validacion runtime completa del portal.

Estados usados:

- `Operativo`: el modulo tiene contrato moderno razonablemente alineado y no muestra una carencia funcional clara en esta pasada.
- `Parcial`: el modulo funciona en parte, pero aun conserva deuda funcional, legacy o rutas no cubiertas.
- `Pendiente real`: hay funcionalidades visibles anunciadas pero todavia sin implementar o con TODO/placeholder funcional claro.
- `Riesgo estructural`: ademas de deuda funcional, mantiene desajustes relevantes de esquema, contratos o integracion.

## Matriz

| Modulo | Estado | Falta real principal | Evidencia base |
| --- | --- | --- | --- |
| advertising | Parcial | validar formularios/runtime del dashboard y contratos secundarios | `class-advertising-module.php`, `class-advertising-dashboard-tab.php` |
| avisos-municipales | Parcial | revisar runtime de tabs y filtros; contrato mejorado pero no cerrado en vivo | `class-avisos-municipales-module.php` |
| ayuda-vecinal | Parcial | responder, aceptar, cancelar y completar ya existen; falta validar en vivo retirar/desactivar y cerrar UX general | `class-ayuda-vecinal-module.php`, `assets/js/ayuda-vecinal-frontend.js` |
| banco-tiempo | Riesgo estructural | sigue siendo el modulo con mas deuda visible: mezcla historica admin/frontend, consultas antiguas y textos legacy residuales | `views/servicios.php`, `views/intercambios.php`, `templates/mi-reputacion.php`, `assets/js/banco-tiempo.js` |
| bares | Parcial | validar detalle, reservas y reseñas con rutas modernas | `class-bares-module.php`, `class-bares-dashboard-tab.php` |
| biblioteca | Parcial | revisar runtime de prestamos, catalogo y shortcodes historicos | `class-biblioteca-module.php` |
| bicicletas-compartidas | Pendiente real | shortcodes historicos marcados como placeholder/TODO | `reports/auditoria-shortcodes-2026-02-26.md`, `class-bicicletas-compartidas-module.php` |
| biodiversidad-local | Pendiente real | shortcode catalogo sigue marcado como pendiente; validar flujo frontend completo | `frontend/class-biodiversidad-local-frontend-controller.php` |
| campanias | Parcial | falta pasada especifica de flujos reales y widgets | `class-campanias-module.php` |
| carpooling | Parcial | revisar flujos reales de publicar, reservar y valoraciones | `class-carpooling-module.php` |
| chat-estados | Parcial | estructura saneada, falta validacion runtime del flujo de estados | `class-chat-estados-module.php`, `class-chat-estados-dashboard-tab.php` |
| chat-grupos | Parcial | contratos mejorados, falta validacion real de grupos, mensajes y alta | `class-chat-grupos-module.php`, `class-chat-grupos-dashboard-tab.php` |
| chat-interno | Parcial | schema drift mitigado con migraciones y portal mejor alineado; sigue faltando validar carga real de conversaciones, archivados y envios en vivo | `class-chat-interno-module.php`, `assets/js/chat-interno.js` |
| circulos-cuidados | Parcial | flujo principal existe, falta cierre runtime y segunda pasada de UX | `class-circulos-cuidados-module.php` |
| clientes | Parcial | dashboard y acciones basicas alineadas, pero no cerrado end-to-end | `class-clientes-module.php`, `class-clientes-dashboard-tab.php` |
| colectivos | Pendiente real | shortcodes `listado` y `detalle` siguen historicamente pendientes | `frontend/class-colectivos-frontend-controller.php` |
| compostaje | Parcial | solicitud real y puente moderno del portal ya existen; falta cerrar operativa/admin de solicitudes y validar turnos/balance en vivo | `class-compostaje-module.php`, `frontend/class-compostaje-frontend-controller.php` |
| comunidades | Parcial | base muy saneada, falta validacion runtime amplia de tabs e integraciones | `class-comunidades-module.php`, `assets/js/comunidades.js` |
| cursos | Parcial | revisar calendario/shortcodes historicos y mensajes tecnicos | `class-cursos-module.php`, `frontend/class-cursos-frontend-controller.php` |
| dex-solana | Parcial | contratos del portal alineados, pero requiere validacion funcional profunda | `class-dex-solana-module.php` |
| documentacion-legal | Operativo | sin bloqueo funcional claro detectado en esta pasada | `class-documentacion-legal-module.php` |
| economia-don | Pendiente real | shortcode/listado historico aun marcado como placeholder | `frontend/class-economia-don-frontend-controller.php` |
| economia-suficiencia | Parcial | notificacion ya implementada, falta cierre runtime del modulo completo | `class-economia-suficiencia-module.php` |
| email-marketing | Parcial | contratos mejorados y fallback admin util, falta validacion funcional real | `class-email-marketing-module.php` |
| empresarial | Parcial | aun devuelve acciones no implementadas fuera del puente principal; modulo grande sin cierre runtime | `class-empresarial-module.php` |
| encuestas | Operativo | no aparece un bloqueo funcional claro en esta pasada | `class-encuestas-module.php` |
| espacios-comunes | Parcial | conflictos historicos corregidos, falta prueba funcional completa | `class-espacios-comunes-module.php`, `assets/js/espacios-frontend.js` |
| eventos | Parcial | integracion con comunidades mejorada; falta validar ciclo completo por comunidad | `class-eventos-module.php`, `frontend/class-eventos-frontend-controller.php` |
| facturas | Parcial | dashboard alineado, pero shortcodes historicos siguen marcados como pendientes y falta runtime | `class-facturas-module.php`, `class-facturas-dashboard-tab.php` |
| fichaje-empleados | Parcial | dashboard y tabs saneados, falta runtime completo y acciones menos usadas | `class-fichaje-empleados-module.php`, `class-fichaje-empleados-dashboard-tab.php` |
| foros | Parcial | frontend principal ya alineado, falta rematar dashboards legacy y validar mapeo entidad->foro en vivo | `class-foros-module.php`, `frontend/class-foros-frontend-controller.php` |
| grupos-consumo | Parcial | suscripciones y AJAX mucho mejor, falta validacion completa de ciclos y pedidos | `class-grupos-consumo-module.php`, `class-gc-subscriptions.php` |
| huella-ecologica | Parcial | puente de acciones mejorado; falta validar producto real | `class-huella-ecologica-module.php` |
| huertos-urbanos | Pendiente real | shortcode historico de mapa sigue figurando como pendiente y falta pasada runtime | `class-huertos-urbanos-module.php` |
| incidencias | Parcial | acciones del portal alineadas, falta validacion funcional completa | `class-incidencias-module.php` |
| justicia-restaurativa | Pendiente real | shortcode de inicio sigue historicamente pendiente y falta cierre funcional | `frontend/class-justicia-restaurativa-frontend-controller.php` |
| mapa-actores | Pendiente real | directorio/shortcode historico pendiente y grafo aun con placeholders parciales | `frontend/class-mapa-actores-frontend-controller.php`, `class-mapa-actores-module.php` |
| marketplace | Parcial | wrappers reales añadidos, falta validar publicar/contactar/mis anuncios en vivo | `class-marketplace-module.php`, `frontend/class-marketplace-frontend-controller.php` |
| multimedia | Pendiente real | mantiene placeholder/funcion de edicion en desarrollo y shortcodes historicos pendientes | `class-multimedia-module.php`, `assets/js/multimedia-frontend.js` |
| parkings | Parcial | tabs y shortcodes ya alineados, falta validacion runtime de reservas | `class-parkings-module.php` |
| participacion | Parcial | frontend/JS saneado, falta validar propuestas, votaciones y resultados reales | `class-participacion-module.php`, `assets/js/participacion.js` |
| podcast | Parcial | crear serie y subir episodio ya existen; quedan alerts en templates/vistas auxiliares y falta validacion runtime completa | `assets/js/podcast-frontend.js`, `templates/suscribirse.php`, `views/suscriptores.php` |
| presupuestos-participativos | Parcial | tablas e integracion mejoradas, falta cerrar shortcode de votacion y runtime | `class-presupuestos-participativos-module.php`, `frontend/class-presupuestos-participativos-frontend-controller.php` |
| radio | Pendiente real | shortcode `radio_podcasts` sigue historicamente pendiente; validar programacion y mis programas | `class-radio-module.php` |
| recetas | Parcial | flujo comunitario moderado ya coherente, falta limpieza documental y validacion runtime | `class-recetas-module.php`, `frontend/class-recetas-frontend-controller.php` |
| reciclaje | Parcial | tablas y JS alineados, falta pasada runtime de mapa, puntos e impacto | `class-reciclaje-module.php`, `assets/js/reciclaje.js` |
| red-social | Parcial | tabs y acciones mas coherentes, falta validacion runtime de feed y relaciones | `class-red-social-module.php`, `class-red-social-dashboard-tab.php` |
| reservas | Parcial | backend y frontend moderno alineados, falta validacion funcional completa de calendario y cancelacion | `class-reservas-module.php`, `frontend/class-reservas-frontend-controller.php` |
| saberes-ancestrales | Parcial | puente de acciones mejorado, falta runtime y posibles vistas menos usadas | `class-saberes-ancestrales-module.php` |
| seguimiento-denuncias | Pendiente real | buscador/shortcode historico sigue figurando como pendiente | `frontend/class-seguimiento-denuncias-frontend-controller.php` |
| sello-conciencia | Parcial | dashboard funcional, falta runtime estable y completar flujos de solicitud reales | `class-sello-conciencia-module.php`, `class-sello-conciencia-dashboard-tab.php` |
| socios | Parcial | rutas modernas y tabs ya alineadas, falta validacion real de cuotas y pasarela | `class-socios-module.php`, `frontend/class-socios-frontend-controller.php` |
| talleres | Pendiente real | `mis_inscripciones` sigue historicamente pendiente; falta validacion funcional | `frontend/class-talleres-frontend-controller.php` |
| themacle | Parcial | ya no esta vacio, pero sigue de baja madurez funcional | `class-themacle-module.php` |
| trabajo-digno | Parcial | puente de acciones mejorado; falta cierre runtime de CV, postulaciones y publicar | `class-trabajo-digno-module.php` |
| trading-ia | Pendiente real | shortcodes dashboard/panel control siguen historicamente pendientes; requiere validacion profunda | `class-trading-ia-module.php` |
| tramites | Parcial | contratos y JS saneados, falta validar catalogo, detalle y seguimiento en vivo | `class-tramites-module.php`, `class-tramites-dashboard-tab.php` |
| transparencia | Parcial | aliases y shortcodes mejorados, falta validar solicitudes y portal real | `class-transparencia-module.php` |
| woocommerce | Parcial | aliases y fallbacks admin utiles, pero varias vistas siguen siendo placeholder administrativo | `class-woocommerce-module.php` |

## Prioridad actual

### Tanda 1: funcionalidad visible ya rota o con mayor deuda funcional real

- `banco-tiempo`
- `ayuda-vecinal`
- `chat-interno`
- `compostaje`
- `facturas`
- `transparencia`

### Tanda 2: contratos parciales que requieren cierre runtime

- `grupos-consumo`
- `reservas`
- `marketplace`
- `tramites`
- `participacion`
- `espacios-comunes`
- `red-social`
- `comunidades`

### Tanda 3: modulos con placeholder historico, alerts legacy o baja madurez

- `bicicletas-compartidas`
- `biodiversidad-local`
- `colectivos`
- `economia-don`
- `huertos-urbanos`
- `justicia-restaurativa`
- `mapa-actores`
- `podcast`
- `radio`
- `seguimiento-denuncias`
- `talleres`
- `trading-ia`

## Notas

- Los estados `Parcial` no implican que el modulo este roto; implican que todavia no esta cerrado funcionalmente en runtime.
- Parte de los placeholders historicos vienen de [auditoria-shortcodes-2026-02-26.md](./auditoria-shortcodes-2026-02-26.md), que ya no es canonica, pero sigue siendo util para localizar deuda residual.
- El entorno Local ha sido intermitente en la validacion HTTP por CLI, asi que la parte decisiva que queda es una pasada runtime estable sobre las rutas del portal.
