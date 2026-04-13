# Progreso de Canonicidad Admin

Fecha: 2026-03-22

## Objetivo

Dejar claro qué pantallas del admin ya usan una implementación canónica alineada con la nueva arquitectura (`Inicio`, `Ecosistema`, `Configuración`, `Sistema`) y cuáles siguen dependiendo de capas legacy o fallback.

## Cambios aplicados

### Infraestructura común

- Se añadió un registro central de navegación admin en `admin/class-admin-navigation-registry.php`.
- Se reorganizó `admin/class-admin-menu-manager.php` para consumir ese registro.
- Se añadió `admin/class-admin-page-chrome.php` y `admin/css/admin-page-chrome.css` para navegación compacta y breadcrumbs.
- El registro ya distingue entre páginas `primary`, `auxiliary` y `legacy_bridge`.
- La consolidación visual ya empezó a salir de cabeceras ad hoc: `Apps Móviles`, `Menú App`, `Deep Links`, `Red de Nodos`, `Bundles` y bridges de sistema ya usan utilidades CSS comunes del `page chrome` para avisos, acciones y tarjetas simples.

### Navegación y cromado canónico

Pantallas ya alineadas con `page chrome` y breadcrumbs:

- `flavor-dashboard`
- `flavor-unified-dashboard`
- `flavor-module-dashboards`
- `flavor-app-composer`
- `flavor-addons`
- `flavor-marketplace`
- `flavor-design-settings`
- `flavor-layouts`
- `flavor-create-pages`
- `flavor-permissions`
- `flavor-chat-config`
- `flavor-chat-ia-escalations`
- `flavor-apps-config`
- `flavor-app-menu`
- `flavor-deep-links`
- `flavor-network`
- `flavor-health-check`
- `flavor-export-import`
- `flavor-activity-log`
- `flavor-analytics`
- `flavor-api-docs`
- `flavor-documentation`
- `flavor-tours`
- `flavor-setup-wizard`

## Estado por pantalla

### Canónicas activas

- `flavor-dashboard`
  Implementación visible canónica: `admin/views/dashboard.php`
- `flavor-module-dashboards`
  Implementación visible canónica: `admin/class-unified-modules-view.php`
- `flavor-design-settings`
  Implementación visible canónica: `admin/class-design-settings.php`
- `flavor-layouts`
  Implementación visible canónica: `admin/class-layout-admin.php`
- `flavor-create-pages`
  Implementación visible canónica: `admin/class-pages-admin-v2.php`
- `flavor-apps-config`
  Implementación visible canónica: `includes/app-integration/class-app-config-admin.php`
- `flavor-app-menu`
  Implementación visible canónica: `admin/class-app-menu-configurator.php`
- `flavor-deep-links`
  Implementación visible canónica: `includes/app-integration/class-deep-link-manager.php`
- `flavor-network`
  Implementación visible canónica: `includes/network/class-network-admin.php`
- `flavor-module-relations`
  Implementación visible canónica: `admin/class-module-relations-admin.php`
- `flavor-bundles`
  Implementación visible canónica: `admin/class-bundles-admin.php`
- `flavor-permissions`
  Implementación visible canónica: `admin/class-permissions-admin.php` + `admin/views/permissions.php`
- `flavor-chat-config`
  Implementación visible canónica: `admin/class-chat-settings.php`
- `flavor-chat-ia-escalations`
  Implementación visible canónica: `admin/class-chat-settings.php`
- `flavor-health-check`
  Implementación visible canónica: `admin/class-health-check.php`
- `flavor-export-import`
  Implementación visible canónica: `admin/class-export-import.php`
- `flavor-activity-log`
  Implementación visible canónica: `admin/class-activity-log-page.php`
- `flavor-analytics`
  Implementación visible canónica: `admin/class-analytics-dashboard.php`
- `flavor-api-docs`
  Implementación visible canónica: `admin/class-api-docs.php`
- `flavor-documentation`
  Implementación visible canónica: `admin/class-documentation-admin.php`
- `flavor-tours`
  Implementación visible canónica: `admin/class-guided-tours.php` + `admin/views/tours-panel.php`

### Canónicas con fallback legacy

- `flavor-documentation`
  Canónica: `admin/class-documentation-admin.php`
  Fallback: `includes/admin/class-documentation-page.php`
  Estado: el menú admin y bootstrap ya priorizan la canónica nueva; la legacy queda solo como respaldo.

- `flavor-create-pages`
  Canónica: `admin/class-pages-admin-v2.php`
  Fallback: `admin/class-pages-admin.php`
  Estado: `callback_pages()` ya prioriza V2 y cae a legacy solo si V2 no está disponible.

- `flavor-systems-panel`
  Canónica de entrada: bridge desde `admin/class-admin-menu-manager.php` y `admin/class-flavor-systems-admin-panel.php`
  Legacy: panel V3 completo con `legacy=1`
  Estado: ya no debería competir como pantalla principal.

### Auxiliares u ocultas en navegación principal

- `flavor-systems-panel`
- `flavor-setup-wizard`
- `flavor-landing-editor`
- `flavor-api-docs`
- `flavor-newsletter`
- `flavor-analytics`
- `flavor-unified-dashboard`

### Ajustes recientes de vista reducida

- La vista `gestor` ya no fuerza `flavor-unified-dashboard` como menú base obligatorio.
- `Widgets` deja de arrastrarse por defecto en la configuración compacta del menú clásico para `gestor`.
- La vista `gestor` ya no usa `read` como capacidad de acceso para los menús rebajados; ahora se apoya en `flavor_ver_dashboard`, endureciendo el acceso al panel simplificado.
- La configuración de menús de `gestor` ya se filtra contra un subconjunto canónico de slugs operativos permitidos, evitando que la vista simplificada herede páginas técnicas solo por estar visibles en el registro general.
- El selector de vista `admin / gestor` ya no imprime CSS/JS inline: usa `admin/css/admin-menu-manager.css` y `admin/js/admin-menu-manager.js`.
- `Configuración de vistas` ya mueve su layout y su comportamiento AJAX a esos mismos assets comunes, dejando de ser una pantalla excepcional con bloques CSS/JS embebidos.
- `Pages V2` ya usa assets propios para layout y tabs (`admin/css/pages-admin.css` y `admin/js/pages-admin.js`) en vez de inyectar CSS/JS en el render.
- `Inicio Rápido` y el panel de dashboards activos del configurador de vistas ya usan clases CSS dedicadas en vez de presentación embebida.
- `Addons` ya salió parcialmente de los bloques inline en estadísticas y contenido auxiliar de `Ecosistema`.
- `Marketplace` ya no lleva decoración HTML inline en el título del submenú y `unified-modules` ya delega el color visual de iconos en CSS con variables.
- `Relaciones` ya deja el estado oculto de su aviso principal en CSS, no en markup embebido.
- `Analytics` ya delega colores de KPIs y módulos en CSS con variables, y `API Docs` ya no define tabs iniciales ocultas con `display:none` inline.
- `Activity Log` ya compacta resumen, filtros y tabla con clases locales en vez de presentación embebida repetida.
- `Health Check` ya compacta resumen, badges de estado, estados vacíos/atenuados, píldoras activas y bloque resumen de API con clases locales, reduciendo `style=` repetidos en su capa principal.
- `Tours` ya compacta acciones globales, felicitación, bloques de categorías, vídeos tutoriales y recursos adicionales con clases locales en vez de `style=` repetidos.
- `Setup Wizard` ya compacta breadcrumb wrapper, CTA de tema, estados ocultos de importación, navegación del footer y loader con utilidades del CSS propio del asistente en vez de `style=` repetidos.
- `Widgets` (`flavor-unified-dashboard`) ya mueve su interacción principal al asset `admin/js/unified-dashboard.js`: modal de personalización, filtros de categoría, colapso de grupos, refresco y guardado de layout dejan de vivir en un `<script>` embebido dentro de la vista.
- `Widgets` también deja estados visibles/ocultos básicos en `admin/css/unified-dashboard.css` (`dropdown`, modal oculto y `spinning`) en vez de inyectarlos desde markup o JS.
- En la pasada sobre `Widgets` se corrigieron además varios errores de sintaxis preexistentes en `includes/dashboard/class-unified-dashboard.php` dentro de la capa social/admin, que impedían validar el archivo completo con `php -l`.
- `Inicio` (`flavor-dashboard`) ya no incrusta su script para persistencia de paneles colapsables y gráficos: esa capa pasa a `admin/js/dashboard-charts.js`, apoyada en `panelStateNonce` desde `class-dashboard.php`.
- `Inicio` ya empezó a vaciar también su gran bloque `<style>` embebido: la base visual de cabecera, alertas, cards, grids, listas, tareas, módulos y accesos rápidos ya fue movida a `admin/css/dashboard.css`.
- `Permisos` ya tiene assets propios (`admin/css/permissions.css` y `admin/js/permissions.js`) en vez de `<style>` y `<script>` embebidos en `admin/views/permissions.php`.
- `Permisos` también corrige el notice roto de feedback y deja la pestaña `Roles` sin `style=` residuales en su capa principal.
- `Capabilities` dentro de `Permisos` ya no mantiene `<style>` propio ni `style=` embebidos en su layout principal; esa capa visual pasa al CSS común de permisos.
- `Usuarios` y `Módulos` dentro de `Permisos` ya salieron también de sus `style=` de maquetación y acciones principales; las cuatro subpestañas de permisos quedan alineadas con el asset `admin/css/permissions.css`.
- `Deep Links` ya no depende de assets apuntando a rutas erróneas ni de una vista con `style=` residual: ahora carga assets propios desde `includes/app-integration/assets/deep-links-admin.css` y `includes/app-integration/assets/deep-links-admin.js`, alineados con la vista actual.
- `Deep Links` también deja de depender de modales ocultos por `style="display:none"` y de muestras de color con `background-color` embebido; esa capa pasa a clases y a un bridge JS específico contra la REST API existente.
- `Apps Móviles > Módulos` ya no mantiene el gran `<script>` embebido del modal de documentación, filtros, recomendaciones y sincronización: esa capa pasa a `includes/app-integration/assets/apps-config.js`.
- En ese mismo tramo, `Apps Móviles > Módulos` ya saca de la vista los `display:none` y estilos inline más visibles de header, aviso, panel de recomendaciones, modal de documentación y estado de sincronización, apoyándose en `includes/app-integration/assets/apps-config.css`.
- Se añadió además una protección en `Flavor_Chat_Helpers::get_portal_url()` para evitar fatales al resolver enlaces de portal demasiado pronto durante `plugins_loaded`, devolviendo fallback seguro hasta que WordPress tenga lista la capa de permalinks.

### Legacy bridge explícito

- `flavor-systems-panel`

### Sin duplicidad relevante detectada en esta pasada

- `flavor-permissions`
  No se detectó una segunda implementación equivalente; hay una sola clase admin que carga la vista, y su base ya quedó externalizada a assets.

## Riesgos todavía abiertos

- `class-bootstrap-dependencies.php` y `class-system-initializer.php` siguen cargando muchas clases admin en paralelo; aunque ya se redujo duplicación en documentación, aún no se ha hecho una pasada global de canonicidad.
- `class-pages-admin.php` sigue existiendo como implementación funcional legacy; conviene convertirlo más adelante en wrapper explícito o retirarlo de la ruta principal.
- Varias pantallas aún mezclan lógica nueva con contenido legacy en el mismo render.

## Recomendación de siguiente fase

1. Revisar pantallas con doble capa visible o conceptual:
   - `Dashboard principal`
   - `Pages`
   - `Addons / Marketplace`
   - `Systems Panel`
2. Convertir legacies supervivientes en:
   - wrapper
   - alias oculto
   - fallback explícito
3. Mantener el criterio:
   - no tocar frontend
   - no mover slugs aún
   - priorizar canonicidad antes de refactor profundo

## Resumen ejecutivo

La capa admin ya tiene una arquitectura visible más coherente y varias pantallas principales ya apuntan a su implementación canónica. Las duplicidades más claras reducidas en esta sesión han sido:

- Documentación
- Páginas
- Navegación compacta
- Breadcrumbs
- Callouts y acciones superiores compartidas en `Configuración`
- `Widgets` admin con JS/CSS extraído a assets
- `Inicio` con JS embebido retirado de la vista principal
- `Inicio` con primera fase del CSS embebido ya extraída a asset
- `Inicio` sin bloques `<style>` ni `<script>` embebidos en `dashboard.php`
- `Permisos` sin bloques `<style>` ni `<script>` embebidos en `admin/views/permissions.php`
- Subpestañas de `Permisos` (`Roles`, `Capabilities`, `Usuarios`, `Módulos`) sin `style=`, `<style>` o `<script>` embebidos en su capa principal
- `Deep Links` sin `style=`, `<style>` o `<script>` embebidos en `includes/app-integration/views/deep-links-admin.php`
- `Apps Móviles > Módulos` sin `<script>` embebido ni `style=` residual en el tramo `render_modules_tab()`
- `Apps Móviles > Navegación` con preview (`App Bar`, tabs activas, drawer y logo) ya apoyada en clases/CSS variables y sin estilos embebidos en ese tramo principal
- `Apps Móviles > Branding` ya sin bloques `<style>` ni `<script>` embebidos; presets y export/import de tema pasan a `apps-config.css` y `apps-config.js`
- `Apps Móviles > Navegación` ya sin bloque `<style>/<script>` propio para presets rápidos; la lógica y la presentación quedan en assets compartidos
- `Apps Móviles > Seguridad` ya sin `display:none` ni estilos embebidos en el bloque principal de token generado
- `Apps Móviles > Tools` ya sin su bloque `<style>/<script>` embebido; acciones de export/import/reset/cache pasan a `apps-config.css` y `apps-config.js`
- `Apps Móviles > Estadísticas`, `Push test`, `Diagnóstico` y `Deep Links` embebido ya sin bloques `<style>/<script>` dentro de `class-app-config-admin.php`
- `Apps Móviles > Idiomas` ya sin bloque `<style>` embebido
- `class-app-config-admin.php` queda sin bloques `<style>` ni `<script>` embebidos

El siguiente trabajo no debería ser añadir más UI, sino seguir reduciendo implementaciones paralelas y dejando explícito qué pantalla manda y cuál queda como respaldo.
