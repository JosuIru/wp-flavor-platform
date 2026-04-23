# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Pendiente
- Sistema de plugins de terceros para VBP
- Exportacion a Figma nativa
- Modo presentacion para clientes

---

## [3.5.9] - 2026-04-23

### Anadido
- **CPT `flavor_product` + shortcode `[flavor_ecosystem]`** para vitrina extensible del ecosistema. Registra un post type con campos meta (url, repo, docs, version, status, icon, badge_color, order) expuestos en REST. El shortcode renderiza una rejilla de cards con los productos publicados, filtrable por `status` y configurable en `columns` y `limit`. Cada nuevo plugin/app del ecosistema se incorpora publicando un post del CPT, sin tocar la pagina de destino.
- **Landing del ecosistema** (`/landing/ecosistema-flavor/`) generada con VBP preset `modern` con secciones hero + shortcode (grid dinamico del CPT) + stats + features + como_funciona + testimonials + faq + cta.

### Corregido
- **API VBP `/claude/*` devolvia 401 sistematico** por scope `vbp_claude` ausente de `flavor_get_vbp_automation_scopes()`. Se anade a la lista por defecto.
- **API VBP `/claude/blocks` devolvia 500** por llamada a metodo inexistente `Flavor_VBP_Block_Library::get_all_blocks()`; el metodo real es `get_bloques()`.

---

## [3.5.8] - 2026-04-23

### Corregido
- **Updater del plugin principal nunca se registraba**. `Flavor_Plugin_Updater` se autoinstanciaba con un `add_action('plugins_loaded', ...)` al final de `class-plugin-updater.php`, pero el autoloader solo carga ese archivo cuando alguien referencia la clase — y nadie lo hacia. Resultado: el hook `pre_set_site_transient_update_plugins` nunca se enganchaba y el plugin nunca consultaba GitHub para detectar actualizaciones. Se anade un `add_action('plugins_loaded', ..., 15)` en `Flavor_Platform::init_hooks()` que fuerza la carga e instanciacion del updater. Los sitios afectados deben actualizar manualmente a 3.5.8 una vez (subiendo el ZIP desde GitHub); a partir de ahi el updater detecta las nuevas versiones automaticamente.

---

## [3.5.7] - 2026-04-23

### Corregido
- **Redirect loop infinito en modo URL directory**. Con `url_mode=directory` el filtro `redirect_canonical` de WordPress intentaba redirigir `/es/`, `/en/`, etc. a URLs sin prefijo porque no conoce ese esquema, lo que creaba un bucle infinito en el navegador. Nuevo metodo `prevent_language_canonical_redirect()` en `Flavor_Multilingual_Core` que intercepta el filtro y devuelve `false` cuando la URL solicitada empieza por un prefijo de idioma activo, cancelando la redireccion espuria. Solo se activa cuando `url_mode=directory`.

---

## [3.5.6] - 2026-04-23

### Anadido
- **Configuracion del selector de idiomas desde el panel multilingue**. Nueva seccion "Selector de Idiomas" en `admin.php?page=flavor-multilingual` (pestana Configuracion) con seis campos: ubicaciones de menu (checkboxes por cada nav_menu registrado), estilo del selector (dropdown/horizontal/vertical/flags-only/minimal/globe/select), mostrar banderas, mostrar nombres, usar nombres nativos y ocultar idioma actual. Los valores se persisten en `flavor_multilingual_settings` y el frontend controller ya los leia — no habia codigo de logica que anadir, solo los campos de UI que faltaban.
- **Marketplace: URL dinamica segun servidor de licencias**. `Flavor_Addon_Marketplace::configure_marketplace_url()` ajusta `$this->marketplace_url` usando `flavor_license_server_url` (opcion BD) o la constante `FLAVOR_LICENSE_SERVER_URL`. Si el endpoint del servidor de licencias ya incluye `/fls/v1/`, se reutiliza esa base en lugar de la URL hardcoded.
- **Marketplace: soporte de flujo de checkout**. Si la respuesta de instalacion de addon devuelve `checkout_url`, el navegador redirige al TPV en lugar de mostrar "Instalado". El boton restaura su etiqueta original (`cta_label` o "Instalar") en caso de error.
- **License Manager: campos adicionales en la respuesta de licencia**. `store_license_data()` y `refresh_license()` persisten ahora `plan_modules`, `addon_modules`, `addons` y `features` que el servidor puede devolver junto a `modules`.

### Corregido
- **Marketplace: etiqueta del boton de instalacion no se restauraba en error**. Antes del fix, un error de red dejaba el boton con texto "Instalando..." indefinidamente. Ahora se captura `data-label` al inicio de la llamada AJAX y se restaura en los callbacks `error` y `success` (cuando no hay redireccion).

---

## [3.5.5] - 2026-04-22

### Corregido
- **Aviso "modo degradado" persistia tras reparar el despliegue**. Cuando `flavor_platform_require_bootstrap_file()` no encontraba un archivo, lo registraba en la opcion `flavor_platform_missing_bootstrap_files` y mostraba el aviso. Al subir los archivos despues por FTP, el aviso seguia apareciendo porque la opcion nunca se limpiaba. Ahora, cuando un archivo se carga correctamente, se retira de la lista de faltantes; si la lista queda vacia, se borra la opcion.

---

## [3.5.4] - 2026-04-22

### Corregido
- **`Flavor_Plugin_Updater` ignoraba `?force-check=1`**. El updater mantiene un cache propio (`flavor_plugin_update_check`) para evitar martillar la API de GitHub. `?force-check=1` de WordPress solo invalida el transient de WP, no ese cache propio: cuando se publicaba una release nueva mientras el cache decia "no hay update", el usuario tenia que esperar hasta 12h para detectarla. Ahora el cache se salta cuando:
  - La URL contiene `?force-check=1` (boton "Buscar actualizaciones").
  - Se ejecuta el AJAX `flavor_check_updates` del enlace "Verificar actualizaciones" del propio plugin.
- **TTL del cache reducido** de 12h a 1h: menor impacto si alguien no fuerza.

---

## [3.5.3] - 2026-04-22

### Eliminado
- **Pantalla `flavor-create-pages`** (Mi App > Paginas): era una herramienta puntual de migracion V1 -> V2 que ya cumplio su funcion. Eliminados los dos archivos de clase (`class-pages-admin.php`, `class-pages-admin-v2.php`) y todas las referencias en shell, menu-manager, bootstrap, settings-hub, dashboard, page-chrome, menu-organizer, navigation-registry y views. Para crear landings ahora se usa la Vista Unificada de Modulos con el boton "Crear landing" en cada tarjeta.

### Corregido
- **Workflow de Release: `composer install --no-dev` reventaba** por el hook `post-install-cmd` que intentaba ejecutar `vendor/bin/phpcs` (dev dep no instalado en produccion). Ahora el script es tolerante: si no existe `vendor/bin/phpcs` imprime "skipped" y sale con exit 0.
- **`package-lock.json` resincronizado** con `package.json` (faltaban 10 transitivas). `npm ci` en el workflow dejo de fallar con "Missing: ... from lock file".

### Nota
Funcionalmente equivalente a 3.5.2 mas los cambios listados arriba. El bump a 3.5.3 es para que instalaciones con 3.5.2 temprana (subida manual antes de consolidar el empaquetado) detecten la actualizacion y reciban estos extras.

---

## [3.5.2] - 2026-04-22

### Anadido
- **Page header de seccion en el Admin Shell**. Breadcrumb + titulo del apartado actual + chips con las subpaginas hermanas, renderizado encima del contenido en todas las paginas del shell (salvo dashboard principal). Hace visible donde esta el usuario y que mas cuelga del apartado sin depender del sidebar.
- **Modal de confirmacion al activar modulos con dependencias**. Antes de activar un modulo que requiere otros no activos, se muestra la cadena completa de modulos que se activaran en cascada. Nuevo endpoint AJAX `flavor_preview_toggle_modulo` y metodo `Flavor_Module_Dependency_Resolver::get_activation_chain()`.
- **Label visible en botones de landing** en la vista de Modulos Unificados. El `+` de "Crear landing" y el `edit` de "Editar landing" ahora llevan texto visible junto al icono.

### Corregido
- **Service Worker de VBP no pre-cacheaba assets**. Los paths se resolvian contra el scope del SW (`assets/vbp/js/`) en lugar de la raiz del plugin, devolviendo 404. Ahora `pluginRoot` se calcula correctamente. Bump de cache a `vbp-v2`.
- **Crash en dashboard al recibir respuesta AJAX sin estadisticas**. `updateMetrics()` hacia `stats.usuarios_activos_30d` con `stats` undefined. Ahora valida la forma del payload antes de leer propiedades.
- **Doble carga de Alpine.js en `flavor-app-composer`** (handles `alpine` del shell + `alpinejs` del CDN). Provocaba bucle infinito en `_x_toggleAndCascadeWithTransitions`. Unificado en el handle `alpine` desde vendor local. Dependencia de `unified-modules` actualizada a `alpine`.

---

## [3.5.1] - 2026-04-22

### Cambiado
- **Actualizaciones automaticas via GitHub Releases** (coherencia open source)
  - El updater del core (`Flavor_Plugin_Updater`) ya no consulta `licencias.gailu.net`: lee directamente las releases publicas de `JosuIru/wp-flavor-platform`.
  - Eliminado el envio de `license_key`, `site_url` y telemetria al verificar updates.
  - Nuevo helper `Flavor_GitHub_Release_API` (GET anonimo a `api.github.com`, con token opcional via `FLAVOR_GH_TOKEN` para rate limit).
  - Repo configurable via constante `FLAVOR_PLATFORM_GH_REPO`, opcion o filtro `flavor_platform_github_repo`.
  - Filtro `flavor_platform_accept_prereleases` para opt-in a canales beta.
- **Addon updater unificado al mismo patron**
  - `Flavor_Addon_Updater::register_addon()` ahora requiere `github_repo` en el config; eliminados `update_url` y `license_key`.
  - Cada addon consulta su propio repositorio GitHub; el sistema de licencias premium (`Flavor_Addon_License`) se mantiene intacto para addons que lo necesiten.

### Corregido
- **Workflow de release** (`.github/workflows/release.yml`)
  - Corregida la ruta de `CHANGELOG.md`: ahora se copia desde `docs/CHANGELOG.md`.

---

## [3.5.0] - 2026-04-01

### Anadido
- **Sistema de Discovery** - Scripts obligatorios antes de componer paginas/APKs
  - `tools/full-inventory.sh` - Inventario completo 3 fases
  - `tools/vbp-inventory.sh` - Inventario Visual Builder Pro
  - `tools/apk-inventory.sh` - Inventario APK 3 niveles
- **API de Compatibilidad de Modulos** (`/flavor-platform/v1/modules/compatibility`)
- **Pre-commit hook** para validar modulos antes de commit
- **Addon Multilingual v1.4.0** completo
  - 94 tests unitarios
  - Object cache con wp_cache
  - Documentacion OpenAPI
  - Assets minificados (45% reduccion)
  - Editor side-by-side con atajos de teclado
  - Sistema de comentarios de traduccion

### Mejorado
- **CLAUDE.md** con reglas de discovery obligatorio
- **CLAUDE-APK.md** nueva documentacion para apps moviles
- **README.md** simplificado a quickstart

### Limpieza
- 34 archivos .md historicos movidos a `archive/docs-historicos/`
- Estructura de documentacion consolidada

---

## [3.4.0] - 2026-03-23

### Anadido
- **Sistema de Versionado de Modulos** con `module.json`
- **Visual Builder Pro mejorado** - Split-screen preview
- **Modularizacion de keyboard shortcuts** en VBP

### Arquitectura
- Bootstrap modular refactorizado
- Sistema de migrations con WP-CLI

---

## [3.3.0] - 2026-03-10

### Anadido
- **Federacion completa** - Sincronizacion entre nodos WordPress
- **Webhooks** con firma HMAC-SHA256
- **8 shortcodes de red** para contenido federado
- **Apps moviles extendidas** - 55 templates Flutter

### Seguridad
- Correccion de vulnerabilidades en endpoints A/B testing
- Mejoras en endpoints publicos de blog

---

## [3.2.0] - 2026-02-23

### Anadido
- **Addon Multilingual** - Sistema de traduccion multiidioma
- **Templates dinamicos** para CPTs en VBP
- **REST API para Claude Code** (`/flavor-vbp/v1/claude/`)

### Rendimiento
- Lazy loading de widgets en dashboard
- Consolidacion de queries N+1

---

## [3.1.1] - 2026-02-12

### Seguridad
- **API Keys encriptadas** con AES-256-GCM en base de datos
- **HTTPS forzado** en aplicaciones Android (usesCleartextTraffic=false)
- **Network Security Config** para Android con cert pinning preparado
- **Autenticacion biometrica** (Face ID / huella dactilar) en apps moviles
- **Validacion de uploads mejorada** con verificacion MIME y limite 5MB
- **Headers de seguridad** en exports (X-Content-Type-Options, SHA256)

### Rendimiento
- **Lazy loading de modulos** - Reduccion 40% tiempo de carga
- **Cache de metadatos** de modulos (transient 24h)
- **Assets minificados** - 15+ archivos CSS/JS optimizados
- **Limpieza de archivos** - Eliminados node_modules, builds, scripts debug

### Arquitectura
- **Migracion V3 completa** - 43/43 modulos con `get_pages_definition()`
- **Trait de notificaciones** implementado en 41/43 modulos
- **Refactorizacion email-marketing** - 5 clases especializadas extraidas
- **Logger centralizado** en Flutter - Reemplazo de 153 debugPrint

### UX/UI
- **Sistema de validacion de formularios** con feedback en tiempo real
- **Loading states AJAX** con spinners, overlays y toasts
- **Tooltips accesibles** sin dependencias externas
- **Modo oscuro** con deteccion de preferencia del sistema
- **Breakpoints responsive** formalizados (sm/md/lg/xl/2xl)
- **Modales de confirmacion** reemplazando confirm() nativo
- **Haptic feedback** centralizado en apps Flutter
- **Semantics widgets** para accesibilidad en Flutter
- **PopScope navigation** para control de boton atras
- **Accesibilidad en formularios** - aria-labels, fieldsets, legends

### Documentacion
- **OpenAPI 3.0 completa** - 45+ endpoints documentados
- **Internacionalizacion** - Plantilla POT + traduccion EN_US
- **Reportes de auditoria** actualizados

### Testing
- **Suite PHPUnit** - 4 tests unitarios basicos
- **Suite Flutter** - 5 tests de seguridad y utilidades
- **Bootstrap de tests** con mocks de WordPress

### Correcciones
- Eliminados 23 scripts de debug del directorio raiz
- Eliminados 21 archivos MD temporales
- Corregidos 72+ usos de debugPrint por Logger

---

## [3.1.0] - 2026-02-01

### Anadido
- 43 modulos funcionales completos
- Sistema de chat con IA (Claude, OpenAI, DeepSeek, Mistral)
- Aplicaciones moviles Flutter (Android/iOS)
- Sistema de reservas y tickets
- Multi-idioma (ES, EN, EU)

### Arquitectura
- Plugin modular con autoloader PSR-4
- Sistema de hooks extensible
- APIs REST y AJAX completas
- Sistema de roles granular

---

## [3.0.0] - 2026-01-15

### Anadido
- Reescritura completa del sistema
- Nueva arquitectura modular V3
- Soporte multisite WordPress
- Sistema de addons

---

## [2.4.0] - 2025-12-20 - Visual Builder Pro

### Anadido
- **Modo Prototipado (Prototype Mode)**
  - Definir interacciones: click, hover, scroll
  - Acciones: navegar a frame, mostrar overlay, animar
  - Conectar frames con flechas
  - Preview interactivo en tiempo real
  - Exportar como HTML interactivo
  - Smart Animate entre estados

- **AI Layout Assistant mejorado**
  - Generar layouts desde descripcion natural
  - Auto-spacing inteligente
  - Sugerencias de colores complementarios
  - Generacion de variantes de diseno
  - Integracion con API de IA existente

### Mejorado
- Rendimiento del canvas en documentos grandes
- Mejor soporte para elementos SVG

---

## [2.3.0] - 2025-11-15 - Visual Builder Pro

### Anadido
- **Colaboracion en Tiempo Real (Real-time Collaboration)**
  - Servidor WebSocket para sincronizacion instantanea
  - Cursores de otros usuarios visibles en canvas
  - Locks optimistas por elemento
  - Sincronizacion de cambios con CRDT
  - Indicador visual de quien edita cada elemento
  - Fallback a WordPress Heartbeat si WebSocket falla

- **Variantes Responsive (Responsive Variants)**
  - Breakpoints: desktop (1280px+), laptop (1024px), tablet (768px), mobile (375px)
  - Guardar variante de layout por breakpoint
  - Override de propiedades por breakpoint
  - Visualizacion de diferencias entre breakpoints
  - Copiar layout de un breakpoint a otro

- **Constructor de Animaciones (Animation Builder)**
  - Panel de timeline visual con keyframes
  - Propiedades animables: transform, opacity, colors, dimensions
  - Curvas de easing predefinidas + editor bezier custom
  - Preview en canvas antes de guardar
  - Exportar como CSS @keyframes
  - Triggers: scroll-into-view, hover, click, page-load

- **Gestor de Assets (Asset Manager)**
  - Panel lateral con libreria centralizada de medios
  - Tabs: Imagenes, SVGs, Videos, Iconos, Documentos
  - Drag & drop para subir multiples archivos
  - Busqueda y filtros por tipo/fecha/tamano
  - Integracion con Unsplash (busqueda directa)
  - Sistema de favoritos y carpetas
  - Optimizacion automatica de imagenes

### Mejorado
- Panel de capas con mejor rendimiento
- Undo/redo mas rapido con snapshots incrementales

---

## [2.2.0] - 2025-10-01 - Visual Builder Pro

### Anadido
- **Sistema de Simbolos (Symbols)**
  - Crear simbolos desde seleccion (Ctrl+Shift+Y)
  - Insertar instancias vinculadas (Ctrl+Alt+O)
  - Overrides por instancia (texto, imagenes, enlaces, colores)
  - Simbolos anidados (simbolo dentro de simbolo)
  - Sistema de variantes (ej: boton primario/secundario/ghost)
  - Swap entre simbolos similares
  - Import/Export de simbolos como JSON
  - Panel de simbolos (F8) con busqueda y categorias
  - Desvincular instancia (Ctrl+Alt+U)
  - Ir al master (Ctrl+Shift+G)

- **Smart Guides**
  - Guias de alineacion automaticas al arrastrar
  - Lineas de centro (rojas) y bordes (azules)
  - Snap automatico a grid de 8px
  - Snap a bordes/centros de otros elementos
  - Configuracion de sensibilidad del snap

- **Indicadores de Espaciado (Spacing Indicators)**
  - Mostrar distancia en px entre elementos
  - Flechas bidireccionales con valor numerico
  - Activar con Alt+hover sobre cualquier elemento
  - Aparecen automaticamente al arrastrar
  - Color naranja para facil identificacion

- **Copiar/Pegar Estilos (Copy/Paste Styles)**
  - Ctrl+Alt+C para copiar estilos del elemento seleccionado
  - Ctrl+Alt+V para pegar estilos a seleccion actual
  - Incluye: colores, tipografia, espaciado, bordes, sombras
  - Notificacion toast de confirmacion
  - Funciona con seleccion multiple

- **Zoom a Seleccion (Zoom to Selection)**
  - Atajo: Ctrl+2 o doble-click en minimap
  - Calcula bounding box automaticamente
  - Aplica zoom y pan para centrar
  - Animacion suave de transicion (300ms)
  - Funciona con elementos individuales o seleccion multiple

- **Edicion Masiva (Bulk Property Edit)**
  - Seleccion multiple + inspector muestra campos comunes
  - Campos con valores mixtos muestran placeholder "Mixed"
  - Editar aplica cambios a todos los seleccionados
  - Checkbox para "aplicar solo a seleccionados"
  - Soporte para propiedades numericas, colores y texto

### Mejorado
- Rendimiento general del canvas (60fps constante)
- Mejor feedback visual durante drag & drop

---

## [2.1.0] - 2025-08-15 - Visual Builder Pro

### Anadido
- **Sistema de Branching/Versiones**
  - Crear ramas de diseno independientes
  - Merge entre branches con resolucion de conflictos
  - Diff visual entre ramas (lado a lado)
  - Nombrar y describir branches
  - Restaurar desde cualquier punto en el historial
  - Colaboracion aislada por branch

- **Constraints/Pinning (tipo Figma)**
  - Panel en inspector: Top, Right, Bottom, Left, Center H/V
  - Checkboxes visuales para anclar a cada borde
  - Al redimensionar padre, hijos anclados mantienen distancia
  - Presets rapidos: Fill, Center, Top-Left, Top-Right, etc.
  - Visualizacion en canvas de constraints activos
  - Indicadores de linea punteada mostrando anclajes

- **Estilos Globales (Global Styles)**
  - Definir clases CSS reutilizables (.heading-1, .button-primary, etc.)
  - Aplicar a elementos desde dropdown en inspector
  - Editar estilo global actualiza todas las instancias
  - Panel de gestion de estilos (crear, editar, eliminar)
  - Exportar como archivo CSS
  - Importar estilos desde CSS existente
  - Variables CSS para colores y tipografia

### Mejorado
- UI del inspector mas compacta
- Mejor organizacion de paneles laterales

---

## [2.0.0] - 2025-06-01 - Visual Builder Pro

> **RELEASE MAYOR** - Ver [Release Notes completas](docs/releases/v2.0.0.md)

### Anadido
- **Editor Visual Fullscreen** tipo Figma/Photoshop
  - Layout con toolbar superior, paneles laterales, canvas central
  - Miniaturas de pagina en panel izquierdo
  - Inspector de propiedades en panel derecho
  - Barra de estado con informacion contextual

- **Canvas con Drag & Drop**
  - Powered by SortableJS
  - Arrastrar elementos desde panel de bloques
  - Reordenar elementos arrastrando
  - Seleccion multiple (Ctrl+click, Shift+click)
  - Zoom (Ctrl+rueda, pinch en trackpad)
  - Pan (Space+arrastrar)

- **Panel de Bloques**
  - 50+ bloques predefinidos por categoria
  - Busqueda instantanea
  - Favoritos del usuario
  - Drag & drop al canvas

- **Panel de Capas**
  - Vista jerarquica de elementos
  - Drag & drop para reordenar
  - Ocultar/mostrar capas
  - Bloquear capas
  - Renombrar elementos

- **Inspector de Propiedades**
  - Tabs: Layout, Estilos, Avanzado
  - Controles visuales para espaciado, colores, tipografia
  - Preview en tiempo real

- **Historial de Versiones (Version History)**
  - Guardar snapshots manualmente
  - Auto-save cada 5 minutos
  - Restaurar versiones anteriores
  - Diff visual entre versiones
  - Limite configurable de versiones guardadas

- **Atajos de Teclado Completos**
  - Ver referencia en `docs/vbp/keyboard-shortcuts.md`
  - Paleta de comandos (Ctrl+K)
  - Todos los atajos personalizables

- **Sistema de Plugins VBP**
  - API para extensiones de terceros
  - Hooks disponibles: `vbp:init`, `vbp:save`, `vbp:load`, etc.
  - Panel de gestion de plugins
  - Documentacion para desarrolladores

- **Temas del Editor**
  - Light (predeterminado)
  - Dark
  - Midnight (OLED)
  - Forest (verde oscuro)
  - High Contrast (accesibilidad)
  - Temas personalizados via CSS variables

- **Accesibilidad (a11y)**
  - ARIA completo en todos los controles
  - Navegacion por teclado de todos los paneles
  - Screen reader support (testeado con NVDA, VoiceOver)
  - Respeta prefers-reduced-motion
  - Contraste minimo WCAG AA

- **Modo Offline**
  - Service Worker para cache de assets
  - IndexedDB para almacenar cambios
  - Indicador de estado de conexion
  - Sincronizacion automatica al reconectar
  - Cola de cambios pendientes

- **Monitor de Rendimiento**
  - Metricas en tiempo real (FPS, memoria)
  - Alertas automaticas si rendimiento baja
  - Sugerencias de optimizacion
  - Historial de metricas

- **Design Tokens**
  - Definir tokens de diseno (colores, espaciado, tipografia)
  - Sincronizacion con Figma (import/export)
  - Export a multiples formatos: CSS, SCSS, JSON, Tailwind
  - Panel de gestion de tokens

- **API de Claude mejorada**
  - Endpoints para todas las features VBP
  - Operaciones batch (crear/actualizar multiples elementos)
  - Documentacion contextual en cada endpoint
  - Ejemplos de uso en documentacion

### Cambiado
- Migrado de jQuery UI a SortableJS
- Nuevo sistema de estados con Alpine.js
- Arquitectura de componentes modular

### Seguridad
- Rate limiting en API (100 requests/minuto)
- Headers de seguridad HTTP (X-Content-Type-Options, X-Frame-Options)
- Comparacion timing-safe de API keys con hash_equals()
- Logging de intentos de acceso fallidos

### Obsoleto
- Editor clasico de VBP 1.x (mantener solo para migracion)

### Eliminado
- Dependencia de jQuery UI
- Panel de widgets legacy

---

## [1.x.x] - Versiones Legacy

Las versiones 1.x fueron la primera iteracion del Visual Builder, con funcionalidad limitada. Se recomienda migrar a 2.0+.

### [1.2.0] - 2025-03-01
- Ultimo release de la rama 1.x
- Modo de compatibilidad para migracion a 2.0

### [1.1.0] - 2025-01-15
- Mejoras de estabilidad
- Correccion de bugs criticos

### [1.0.0] - 2024-11-01
- Release inicial del Visual Builder
- Funcionalidad basica de arrastrar y soltar

---

## Enlaces

[Unreleased]: https://github.com/flavor/flavor-platform/compare/v3.5.0...HEAD
[3.5.0]: https://github.com/flavor/flavor-platform/compare/v3.4.0...v3.5.0
[3.4.0]: https://github.com/flavor/flavor-platform/compare/v3.3.0...v3.4.0
[3.3.0]: https://github.com/flavor/flavor-platform/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/flavor/flavor-platform/compare/v3.1.1...v3.2.0
[3.1.1]: https://github.com/flavor/flavor-platform/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/flavor/flavor-platform/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/flavor/flavor-platform/compare/v2.4.0...v3.0.0
[2.4.0]: https://github.com/flavor/flavor-platform/compare/v2.3.0...v2.4.0
[2.3.0]: https://github.com/flavor/flavor-platform/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/flavor/flavor-platform/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/flavor/flavor-platform/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/flavor/flavor-platform/releases/tag/v2.0.0
