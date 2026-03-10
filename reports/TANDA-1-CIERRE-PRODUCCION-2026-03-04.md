# Tanda 1 de Cierre a Produccion 2026-03-04

## Alcance

Primera ejecucion del plan maestro de cierre a produccion sobre:

- estabilidad del entorno runtime
- capa comun del portal dinamico
- modulos criticos de la ola 2 con hallazgos visibles en runtime

## Verificacion de entorno

Se confirmo que la instancia local responde en runtime cuando la comprobacion HTTP se ejecuta fuera del sandbox.

Rutas verificadas con `HTTP 200`:

- `/`
- `/mi-portal/`
- `/mi-portal/marketplace/`
- `/mi-portal/tramites/`
- `/mi-portal/reservas/`
- `/mi-portal/foros/`
- `/mi-portal/colectivos/`

Conclusion:

- el bloqueo no era del sitio, sino de acceso HTTP desde el sandbox
- la validacion runtime vuelve a ser util para las siguientes tandas

## Hallazgos principales de la tanda

### 1. Metadatos heredados de `hello-world` en paginas dinamicas

Hallazgo:

Las rutas del portal emitian `canonical`, `oEmbed`, `shortlink` y `feed` heredados del post base de WordPress (`hello-world`).

Impacto:

- contaminacion SEO
- metadatos incorrectos al compartir
- incoherencia estructural en toda la capa dinamica

Correccion aplicada:

- se ajusto `wp_head` en paginas dinamicas para eliminar esos enlaces heredados
- se emite `canonical` y `og:url` correctos de la request actual

Archivo:

- [includes/frontend/class-dynamic-pages.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-pages.php)

Validacion:

- `/mi-portal/marketplace/` ya emite `canonical` correcto a `/mi-portal/marketplace/`
- dejaron de aparecer en `head` los enlaces heredados a `/hello-world/`

### 2. `tramites` seguia enlazando fuera del portal

Hallazgo:

El catalogo de tramites llevaba a rutas como:

- `/tramites/detalle/?tramite_id=...`
- `/tramites/seguimiento/`

Impacto:

- salto fuera del esquema del portal
- inconsistencia con el sistema de paginas dinamicas

Correccion aplicada:

- se añadieron acciones ocultas `detalle` y `citas` al renderer del modulo
- se reencauzaron URLs de detalle, iniciar, seguimiento, citas y catalogo a `/mi-portal/tramites/...`

Archivos:

- [includes/modules/tramites/class-tramites-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/tramites/class-tramites-module.php)
- [includes/modules/tramites/frontend/class-tramites-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/tramites/frontend/class-tramites-frontend-controller.php)

Validacion:

- `/mi-portal/tramites/` ya emite enlaces a `/mi-portal/tramites/detalle/?tramite_id=...`
- `seguimiento` ya apunta a `/mi-portal/tramites/seguimiento/`

### 3. `participacion` mostraba enlaces malformados de `foros`

Hallazgo:

En runtime se detecto un enlace roto de foros dentro de `participacion` con forma `/foros//`.

Origen:

- renderer legacy de listado de foros construido por slug directo

Correccion aplicada:

- el listado legacy de `foros` ahora enlaza a `/mi-portal/foros/?foro_id=<id>`

Archivo:

- [includes/modules/foros/class-foros-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/foros/class-foros-module.php)

Validacion:

- `/mi-portal/participacion/` ya emite:
  - `/mi-portal/foros/?foro_id=1`
  - `/mi-portal/foros/?foro_id=2`
  - `/mi-portal/foros/?foro_id=3`
- deja de aparecer el enlace roto `/foros//`

## Ampliacion de la tanda

### 4. `eventos` reencauzado al portal

Hallazgo:

`eventos` seguia mezclando enlaces a rutas legacy como `/eventos/?evento_id=...`, `/eventos/` y algunas variantes `ver/<id>`.

Correccion aplicada:

- se añadió una accion oculta `detalle` al renderer del modulo
- se reencauzaron enlaces principales a `/mi-portal/eventos/detalle/?evento_id=...`
- se alinearon dashboard tab, widget, frontend controller y vistas auxiliares del modulo

Archivos:

- [includes/modules/eventos/class-eventos-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/class-eventos-module.php)
- [includes/modules/eventos/class-eventos-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/class-eventos-dashboard-tab.php)
- [includes/modules/eventos/class-eventos-dashboard-widget.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/class-eventos-dashboard-widget.php)
- [includes/modules/eventos/frontend/class-eventos-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/frontend/class-eventos-frontend-controller.php)

Validacion:

- `/mi-portal/eventos/detalle/?evento_id=1` responde `HTTP 200`
- `/mi-portal/eventos/mis-inscripciones/` responde `HTTP 200`

### 5. `socios` normalizado al naming canonico del portal

Hallazgo:

`Socios` seguia mezclando rutas visibles como `datos`, `cuotas` y `registro`, mientras el contrato principal del modulo ya usa `mi-perfil`, `mis-cuotas` y `unirse`.

Correccion aplicada:

- se actualizaron CTAs visibles del dashboard y widget a:
  - `mi-perfil`
  - `mis-cuotas`
  - `unirse`

Archivos:

- [includes/modules/socios/class-socios-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/socios/class-socios-dashboard-tab.php)
- [includes/modules/socios/class-socios-dashboard-widget.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/socios/class-socios-dashboard-widget.php)

Validacion:

- `/mi-portal/socios/` ya muestra rutas visibles a `/mi-portal/socios/unirse/`
- no reaparecen en ese bloque visible las rutas antiguas `datos/`, `cuotas/` o `registro/`

### 6. `colectivos` y `marketplace` cerrados en bootstrap y detalle portal

Hallazgos:

- `colectivos` podia dejar el shortcode `flavor_colectivos_listado` sin registrar segun el orden de carga del modulo.
- `marketplace` seguia mezclando enlaces visibles a singles publicos del CPT y rutas legacy `anuncio/` o `ver/`.
- ademas, el frontend controller registraba `marketplace_detalle` sin implementar el callback real.

Correccion aplicada:

- `colectivos`
  - se carga explicitamente el frontend controller desde el modulo para asegurar los shortcodes modernos
- `marketplace`
  - se añadió un helper canonico de detalle: `/mi-portal/marketplace/detalle/?anuncio_id=...`
  - se implementó `shortcode_detalle()` en el frontend controller
  - se añadió el tab oculto `detalle` al renderer del modulo
  - se reencauzaron los enlaces visibles del catalogo, mis anuncios, dashboard tab y dashboard widget al detalle dentro del portal

Archivos:

- [includes/modules/colectivos/class-colectivos-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/colectivos/class-colectivos-module.php)
- [includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php)
- [includes/modules/marketplace/class-marketplace-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/class-marketplace-module.php)
- [includes/modules/marketplace/class-marketplace-dashboard-widget.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/class-marketplace-dashboard-widget.php)
- [includes/modules/marketplace/class-marketplace-dashboard-tab.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/marketplace/class-marketplace-dashboard-tab.php)
- [templates/frontend/marketplace/mis-anuncios.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/templates/frontend/marketplace/mis-anuncios.php)

Validacion:

- `/mi-portal/colectivos/` ya no muestra el shortcode literal `[flavor_colectivos_listado ...]`
- `/mi-portal/marketplace/` ya enlaza detalles a `/mi-portal/marketplace/detalle/?anuncio_id=...`
- `/mi-portal/marketplace/detalle/?anuncio_id=2202` renderiza el detalle real de `Patines en línea talla 40`
- el login de detalle usa `redirect_to` correcto a la ruta del portal

### 7. Mitigacion del widget externo de chat que inyectaba `hello-world`

Hallazgo:

- el ultimo enlace visible a `http://localhost:10028/hello-world/` no salia de `flavor-chat-ia`
- el origen real era el plugin externo `wp-calendario-experiencias/addons/chat-ia-addon`, que insertaba un widget flotante en `wp_footer()` con:
  - bloque `chat-ia-posts-list`
  - item `Hello world!`
  - enlaces utiles del sitio

Correccion aplicada:

- se intercepta `wp_footer()` en las paginas dinamicas del portal
- se filtra especificamente el bloque del widget externo antes de imprimir el footer final
- no se toca el plugin externo; la mitigacion queda encapsulada en el portal dinamico

Archivo:

- [includes/frontend/class-dynamic-pages.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/frontend/class-dynamic-pages.php)

Validacion:

- `/mi-portal/marketplace/` ya no contiene `chat-ia-featured-content`
- `/mi-portal/marketplace/` ya no contiene `chat-ia-posts-list`
- `/mi-portal/marketplace/` ya no contiene `hello-world`
- `/mi-portal/colectivos/` y `/mi-portal/tramites/` tampoco muestran ya ese widget externo en el HTML

## Estado de la ola 2 tras esta tanda

### Modulos con mejora confirmada en runtime

- `marketplace`
- `tramites`
- `foros`
- `colectivos`
- `eventos`
- `socios`
- `reservas`
- `participacion`

### Residual abierto

Todavia quedan cuestiones no cerradas del todo:

- estados vacios ligados a falta de datos reales
- validacion dedicada pendiente para algunos flujos secundarios de `socios` y `eventos`

### Avance adicional en flujos secundarios de `socios` y `eventos`

Correcciones aplicadas:

- `socios` carga ya su frontend controller de forma explicita desde el modulo
- `socios` incorpora ruta oculta `carnet` dentro del contrato del portal con shortcode real `[socios_mi_carnet]`
- `socios` mejora estados de login en `mi-perfil`, `mis-cuotas` y `carnet` con enlace de acceso y `redirect_to` correcto
- `eventos` mejora el estado de login de `mis-inscripciones`
- `eventos` mejora el estado de error de `detalle` cuando falta `evento_id`
- `eventos` elimina un residual de `get_permalink()` en `mis-inscripciones` y reencauza el CTA a `/mi-portal/eventos/detalle/?evento_id=...`

Archivos:

- [includes/modules/socios/class-socios-module.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/socios/class-socios-module.php)
- [includes/modules/socios/frontend/class-socios-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/socios/frontend/class-socios-frontend-controller.php)
- [includes/modules/eventos/frontend/class-eventos-frontend-controller.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/frontend/class-eventos-frontend-controller.php)
- [includes/modules/eventos/templates/mis-inscripciones.php](/home/josu/Local%20Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/eventos/templates/mis-inscripciones.php)

Validacion:

- `php -l` sin errores en los cuatro archivos
- la validacion runtime quedo pendiente porque `http://localhost:10028` no estaba respondiendo en este tramo de trabajo

## Decision operativa

La tanda 1 permite afirmar que:

- el entorno runtime vuelve a ser validable
- se ha corregido deuda transversal de `wp_head`
- se han cerrado al menos dos incoherencias P1 reproducibles en modulos criticos

Pero no permite afirmar todavia que la ola 2 este cerrada.

## Siguiente paso recomendado

Ejecutar `Tanda 2` sobre los modulos criticos restantes con foco en:

- `socios`
- `eventos`
- validacion final cruzada de `marketplace`, `tramites`, `reservas`, `foros`, `colectivos`, `participacion`
