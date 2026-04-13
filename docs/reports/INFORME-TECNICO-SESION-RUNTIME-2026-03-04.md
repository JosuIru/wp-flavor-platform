# Informe Tecnico de Sesion Runtime 2026-03-04

## Alcance real de esta sesion

Este informe resume el trabajo realizado en la sesion centrada en:

- coherencia de rutas frontend del portal
- correccion de redirects de login
- sustitucion de shortcodes legacy rotos
- alineacion de tabs y vistas runtime en modulos con mezcla de logica legacy y frontend nuevo
- orden documental para separar fuentes canonicas de historicas

No debe leerse como resumen completo del `git diff` del repositorio, porque el arbol ya contenia muchos cambios previos no originados en esta sesion.

## Resultado ejecutivo

Se redujo de forma material la deuda runtime visible en el portal.

Quedaron corregidos problemas de estas familias:

- `redirect_to` roto hacia `/hello-world/`
- shortcodes legacy mostrados en texto literal
- rutas del portal que salian a slugs legacy fuera de `/mi-portal/`
- tabs que incrustaban vistas admin o caian en renderers genericos incorrectos
- widgets con acciones hacia rutas no respaldadas por el modulo real

La mejora afecta sobre todo a:

- `marketplace`
- `tramites`
- `colectivos`
- `foros`
- `reservas`

## Cambios transversales

### 1. Redirects de login corregidos

Se elimino el patron estructural `wp_login_url(get_permalink())` en la capa principal del portal y se sustituyo por la URL real de la request.

Archivos base relevantes:

- [includes/class-helpers.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-helpers.php)
- [includes/layouts/class-layout-renderer.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/layouts/class-layout-renderer.php)
- [includes/frontend/class-dynamic-pages.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-pages.php)
- [includes/frontend/class-dynamic-crud.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-crud.php)
- [includes/frontend/class-client-dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-client-dashboard.php)
- [includes/dashboard/class-unified-dashboard.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/dashboard/class-unified-dashboard.php)
- [includes/class-portal-shortcodes.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-portal-shortcodes.php)
- [includes/class-adaptive-menu.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-adaptive-menu.php)
- [includes/class-module-shortcodes.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-module-shortcodes.php)

Resultado:

- el portal deja de mandar a `redirect_to=/hello-world/`
- los enlaces de acceso vuelven a la pantalla real del usuario
- el problema deja de ser estructural en la base del plugin

### 2. Render de acciones dinamicas mas coherente

Se ajusto la capa de paginas dinamicas para que, cuando una accion este respaldada por un tab del modulo, use el renderer del modulo en lugar de caer en CRUD/listing generico.

Archivo clave:

- [includes/frontend/class-dynamic-pages.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-pages.php)

## Modulos corregidos

### Marketplace

Problemas tratados:

- shortcode legacy `marketplace_destacados` roto
- mezcla entre fuente legacy basada en tabla propia y frontend nuevo
- categorias y publicar con navegacion inconsistente
- redirects de login incorrectos

Archivos principales:

- [includes/modules/marketplace/class-marketplace-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/class-marketplace-module.php)
- [includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php)
- [templates/frontend/marketplace/formulario.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/templates/frontend/marketplace/formulario.php)
- [includes/frontend/class-dynamic-pages.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-pages.php)

Resultado:

- desaparece el shortcode literal roto en la portada de Marketplace
- `anuncios` y `mis-anuncios` pasan por frontend controller nuevo
- `publicar` y `categorias` quedan mejor alineados con el portal

### Tramites

Problemas tratados:

- frontend controller no cargado desde el modulo
- tabs secundarios con login mudo o fallback ambiguo

Archivos:

- [includes/modules/tramites/class-tramites-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/tramites/class-tramites-module.php)
- [includes/modules/tramites/frontend/class-tramites-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/tramites/frontend/class-tramites-frontend-controller.php)

Resultado:

- el modulo carga explicitamente su frontend controller
- los tabs secundarios muestran login util con redirect correcto

### Colectivos

Problemas tratados:

- enlaces frontend que sacaban al usuario a `/colectivos/...`
- inconsistencias entre detalle por query y rutas legacy
- redirects JS hacia slugs antiguos

Archivos:

- [includes/modules/colectivos/class-colectivos-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/class-colectivos-module.php)
- [includes/modules/colectivos/class-colectivos-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/class-colectivos-dashboard-tab.php)
- [includes/modules/colectivos/class-colectivos-dashboard-widget.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/class-colectivos-dashboard-widget.php)
- [includes/modules/colectivos/views/listado-colectivos.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/views/listado-colectivos.php)
- [includes/modules/colectivos/views/mis-colectivos.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/views/mis-colectivos.php)
- [includes/modules/colectivos/assets/js/colectivos.js](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/assets/js/colectivos.js)

Resultado:

- los enlaces visibles principales ya no expulsan al usuario del portal
- el detalle se normaliza a `/mi-portal/colectivos/?colectivo=<id>`

### Foros

Problemas tratados:

- el tab `hilos` incrustaba una vista admin
- widget de actividad reciente usando referencia incorrecta

Archivo:

- [includes/modules/foros/class-foros-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/foros/class-foros-module.php)

Resultado:

- `hilos` deja de usar render admin dentro del portal
- el widget pasa a un shortcode frontend valido

### Reservas

Problemas tratados:

- dashboard y widgets enlazaban a rutas no respaldadas por el esquema real del modulo
- CTAs que salian a `/reservas/` o `/mis-reservas/`
- filtros de pendientes sin soporte en la vista frontend

Archivos:

- [includes/modules/reservas/class-reservas-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/reservas/class-reservas-dashboard-tab.php)
- [includes/modules/reservas/class-reservas-dashboard-widget.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/reservas/class-reservas-dashboard-widget.php)
- [includes/modules/reservas/frontend/class-reservas-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/reservas/frontend/class-reservas-frontend-controller.php)
- [includes/modules/reservas/views/formulario.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/reservas/views/formulario.php)

Resultado:

- navegacion reencauzada a tabs validos del portal
- `mis-reservas` soporta `?estado=pendiente`
- los CTAs dejan de depender de rutas huérfanas

## Documentacion

Se ordeno la capa documental para separar fuentes canonicas de historicas y evitar que documentos antiguos se lean como estado real actual.

Archivos relevantes:

- [docs/ESTADO-DOCUMENTACION.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/ESTADO-DOCUMENTACION.md)
- [docs/ESTADO-REAL-PLUGIN.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/ESTADO-REAL-PLUGIN.md)
- [docs/INDICE-DOCUMENTACION.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/INDICE-DOCUMENTACION.md)
- [docs/PLUGIN-COMPLETO.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/PLUGIN-COMPLETO.md)
- [docs/GUIA_MODULOS.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/GUIA_MODULOS.md)
- [docs/CATALOGO-MODULOS.md](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/docs/CATALOGO-MODULOS.md)

Archivo eliminado por deprecacion explicita:

- `docs/MODULOS_ESTADO_PLAN.md`

## Validacion realizada

Se hizo validacion tecnica por:

- `php -l` en los archivos tocados
- inspeccion de codigo y rutas construidas
- validacion runtime real en varias fases anteriores de la sesion mientras `localhost:10028` estaba disponible

## Limites de validacion

Al final de la sesion `http://localhost:10028` dejo de responder, por lo que la ultima pasada en `colectivos` y `reservas` quedo validada por codigo y lint, no por navegacion real final.

Por tanto, este informe permite afirmar:

- que se corrigieron incoherencias estructurales claras en codigo
- que se redujo la probabilidad de rutas rotas, widgets inconsistentes y redirects defectuosos

Pero no permite afirmar automaticamente:

- que todos los flujos finales se hayan revalidado en runtime despues del ultimo cambio
- que los modulos revisados hayan quedado completamente cerrados a nivel UX o datos

## Lectura operativa final

La deuda visible mas grave del portal ha bajado.

Lo que queda pendiente es mas acotado y suele caer en una de estas categorias:

- estados vacios poco trabajados
- flujos secundarios aun no unificados
- validacion runtime final cuando la instancia local vuelva a responder

La conclusion tecnica correcta es:

- la sesion no deja el plugin "cerrado"
- si deja menos deuda estructural en runtime
- y mejora de forma concreta la coherencia navegable del portal en varios modulos clave
