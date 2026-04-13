# Backlog Tecnico de Refactorizacion Admin

**Fecha:** 2026-03-22
**Objetivo:** Convertir el plan de refactorizacion admin en una lista operativa de trabajo.

---

## 1. Fase 1: Estructura canonica

- [x] Redefinir el arbol admin a 4 secciones en `admin/class-admin-menu-manager.php`
- [x] Crear registro central de paginas admin con:
  - `section`
  - `group`
  - `slug`
  - `label`
  - `capability`
  - `priority`
  - `legacy_aliases`
- [x] Hacer que el menu clasico y el shell lean del mismo registro
- [x] Mantener redirects o aliases para slugs antiguos

---

## 2. Fase 2: Shell y navegacion

- [x] Quitar inferencia por prefijos en `admin/class-admin-shell.php` cuando exista entrada en el registro
- [x] Rehacer `admin/views/shell-sidebar.php` con secciones:
  - `Inicio`
  - `Ecosistema`
  - `Configuracion`
  - `Sistema`
- [ ] Ajustar favoritos, recientes y busqueda para usar el registro central
- [x] Ocultar en shell paginas legacy o tecnicas no prioritarias

---

## 3. Fase 3: Inicio

- [~] Fusionar `flavor-dashboard` y `flavor-unified-dashboard`
- [x] Convertir `admin/views/dashboard.php` en home admin canonica
- [~] Integrar widgets utiles de `includes/dashboard/class-unified-dashboard.php`
- [ ] Sacar el indice de dashboards de la primera capa

---

## 4. Fase 4: Ecosistema

- [x] Convertir `admin/class-unified-modules-view.php` en `Ecosistema > Catalogo`
- [x] Integrar `admin/class-addon-admin.php` como tab `Addons`
- [x] Integrar marketplace de addons si aplica como tab secundaria
- [x] Mover `admin/class-module-relations-admin.php` a `Ecosistema > Relaciones`
- [~] Mover `admin/class-module-dashboards-page.php` a `Ecosistema > Dashboards`
- [x] Crear `Ecosistema > Bundles`

---

## 5. Fase 5: Configuracion

- [~] Fusionar `app-composer`, `layouts`, `pages` y `landings`
- [x] Usar `admin/class-pages-admin-v2.php` como base canonica
- [x] Reubicar:
  - `Perfil y App`
  - `Experiencia`
  - `Paginas y Landings`
  - `Permisos`
  - `Ajustes`
- [x] Mover `design settings`, `chat settings` y `apps config` a `Configuracion`

---

## 6. Fase 6: Sistema

- [~] Fusionar `health`, `export/import`, `activity log` y `feature flags` en `Sistema > Mantenimiento`
- [~] Fusionar `docs`, `tours` y `wizard` en `Sistema > Ayuda / Onboarding`
- [x] Ocultar estas pantallas de la primera capa

---

## 7. Fase 7: UI comun admin

- [~] Definir componentes admin reutilizables:
  - cards
  - filtros
  - tablas
  - tabs
  - badges
  - empty states
- [~] Reducir CSS y JS especificos y estilos inline
  - Ya aplicado en `Bundles`, `Systems Panel`, `Apps Móviles`, `Menú App`, `Deep Links` y `Red de Nodos` para callouts, acciones y bloques superiores.
  - El selector de vista `admin / gestor` ya usa `admin/css/admin-menu-manager.css` y `admin/js/admin-menu-manager.js` en vez de CSS/JS embebidos.
  - `Inicio Rápido` del dashboard y el bloque de dashboards activos del configurador de vistas ya no dependen de estilos inline directos.
  - `Addons` ya usa clases propias para estadísticas, badges y bloque informativo, reduciendo presentación embebida en `Ecosistema`.
  - `Marketplace` ya no inyecta HTML decorativo inline en el label del menú y `unified-modules` mueve color dinámico de iconos a CSS basado en variables.
  - `Relaciones` ya no mantiene `display:none` inline en su aviso principal; el estado visual queda delegado a CSS.
  - `Analytics` y `API Docs` ya reducen presentation embebida en KPIs, iconos y tabs ocultas iniciales.
  - `Activity Log` ya concentra buena parte de su presentación en clases locales en vez de múltiples `style=` dispersos en resumen, filtros y tabla.
  - `Health Check` ya mueve resumen, badges, estados muteds, píldoras activas y bloque resumen de API a clases locales reutilizables, reduciendo presentación inline en la pantalla diagnóstica.
  - `Tours` ya compacta acciones, mensaje de felicitación, bloques de categorías, tarjetas de vídeos y recursos adicionales con clases locales, reduciendo decoración embebida en `Sistema > Ayuda`.
  - `Setup Wizard` ya mueve wrapper de breadcrumbs, CTA de tema, resultado/progreso de importación, botones de navegación y loader a clases/utilidades del CSS propio del asistente, reduciendo presentación embebida en `Sistema > Onboarding`.
  - `Configuración de vistas` ya salió también de CSS/JS embebido y usa `admin/css/admin-menu-manager.css` + `admin/js/admin-menu-manager.js` para layout y comportamiento.
  - `Pages V2` ya dejó de definir CSS/JS dentro del render y usa assets reales: `admin/css/pages-admin.css` y `admin/js/pages-admin.js`.
  - `Permisos` ya dejó de definir CSS/JS dentro del render y usa assets reales: `admin/css/permissions.css` y `admin/js/permissions.js`.
  - Las pestañas `Roles`, `Capabilities`, `Usuarios` y `Módulos` dentro de `Permisos` ya salieron de `style=`, `<style>` y `<script>` embebidos en su capa principal.
  - `Deep Links` ya salió de una situación peor que el inline: apuntaba a assets fuera de ruta y con clases desalineadas. Ahora usa assets propios (`includes/app-integration/assets/deep-links-admin.css/js`) y la vista actual queda alineada con ellos.
  - `Apps Móviles > Módulos` ya mueve a `includes/app-integration/assets/apps-config.js` la lógica de modal de documentación, búsqueda, filtros, recomendaciones y sincronización; además saca de la vista el inline más visible de esa subpestaña.
  - `Flavor_Chat_Helpers::get_portal_url()` ya protege el acceso temprano durante bootstrap y evita fatales al intentar resolver permalinks de `mi-portal` antes de `init`.
- [~] Unificar visualmente:
  - `admin/css/admin-shell.css`
  - `admin/css/unified-dashboard.css`
  - `admin/css/unified-modules.css`

---

## 8. Fase 8: Limpieza

- [x] Marcar paginas legacy con alias y redireccion
- [~] Eliminar entradas duplicadas del submenu clasico
- [x] Ocultar modulos o paginas nicho del primer nivel
- [~] Revisar capacidades y vistas `admin` vs `gestor`
  - La vista `gestor` ya no rebaja menús a `read`; ahora usa la capacidad propia `flavor_ver_dashboard`, reduciendo exposición accidental de páginas admin a usuarios con acceso genérico al backend.
  - La configuración de `gestor` ya no se apoya en todo el registro visible del admin: ahora usa un subconjunto canónico de menús operativos permitidos para esa vista.

---

## 9. Orden Minimo Viable

1. registro central
2. shell nuevo
3. home admin unificada
4. catalogo unificado modulos/addons
5. configuracion unificada
6. sistema unificado
7. limpieza

---

## 10. Resultado Esperado

Si este backlog se ejecuta bien, la administracion del plugin deberia:

- reducir entradas de primer nivel
- compactar menus y pantallas
- eliminar duplicidad mental
- hacer que shell, menu y busqueda usen una sola estructura
- mejorar mantenimiento de CSS/JS admin
- parecer un producto mas solido y menos acumulativo

---

## 11. Estado actual de ejecucion

### Hecho

- Registro central de navegacion admin operativo
- Menu clasico y shell alineados con el registro
- Chrome admin reutilizable con breadcrumbs y navegacion compacta
- `Inicio`, `Ecosistema`, `Configuracion` y `Sistema` ya visibles como capas mentales coherentes
- Canonicidad aplicada en `Pages`, `Documentation`, `Systems Panel`, `Dashboard Manager`
- Varias pantallas pasadas a modo auxiliar u oculto en navegacion principal

### En curso

- Compactacion de `Sistema`
- Compactacion de `Permisos`
- Reduccion de pantallas auxiliares en primera capa
- Consolidacion real entre `Apps Móviles`, `Menú App`, `Deep Links` y `Red`
- Consolidacion de CSS admin comun para avisos, acciones y wrappers ligeros
- Ajuste fino de la vista `gestor` para que no herede pantallas auxiliares como `Widgets`
- Extraccion progresiva de CSS/JS embebido en `Inicio`, con `Widgets` ya pasado a assets dedicados
- `Inicio` ya mueve su script embebido a `admin/js/dashboard-charts.js`
- `Inicio` ya vació por completo el bloque `<style>` embebido de `admin/views/dashboard.php` hacia `admin/css/dashboard.css`
- `Permisos` ya vació por completo los bloques `<style>` y `<script>` embebidos de `admin/views/permissions.php` hacia `admin/css/permissions.css` y `admin/js/permissions.js`
- `Permisos` queda bastante saneado en la capa visible; el trabajo pendiente allí ya sería más de simplificación funcional que de presentación embebida
- `Deep Links` queda saneado en capa visible y de interacción básica; el trabajo pendiente allí ya sería más de refinado funcional o UX avanzada que de canonicidad/admin chrome
- `Apps Móviles` sigue siendo la bolsa grande pendiente, pero `Módulos` ya quedó sensiblemente más canónico; lo que queda ahí es seguir por `Navegación`, `Branding` y utilidades avanzadas
- `Apps Móviles > Navegación` ya quedó bastante más limpio en preview y acciones base; el siguiente paso allí sería seguir por `Info`, `Branding` y bloques utilitarios todavía embebidos
- `Apps Móviles > Branding` y presets de `Navegación` ya quedaron bastante más canónicos; el siguiente bloque fuerte en `Apps Móviles` pasa a ser `Tools`, `Analytics/Diagnóstico` y restos menores de `Info`
- `Apps Móviles > Tools` ya quedó saneado en su capa principal; el siguiente bloque fuerte en `Apps Móviles` pasa a ser `Analytics/Diagnóstico`, `Deep Links` embebido residual y restos menores de `Info`
- `Apps Móviles` ya quedó muy saneado a nivel de assets y canonicidad; el siguiente bloque fuerte pasa a ser limpieza de `style=` residuales, simplificación funcional y revisión de tabs auxiliares menos usadas

### Pendiente fuerte

- Home admin realmente unificada con menos duplicidad funcional
- Revisar si `dashboard-charts.js` debe absorber definitivamente la logica residual del dashboard principal
- Consolidacion de CSS/JS admin compartido
- Revisión fina de capacidades y vista `gestor`
