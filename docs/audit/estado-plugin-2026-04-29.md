# Estado del plugin Flavor Platform — auditoría completa

**Fecha**: 2026-04-29
**Alcance**: VBP, 90 módulos, sistema APK, rendimiento, red de nodos / mesh, admin/UX
**Método**: 6 auditorías paralelas por dominio (subagentes Explore) + síntesis priorizada

---

## TL;DR — top hallazgos (acciónables ya)

| # | Dominio | Hallazgo | Severidad |
|---|---------|----------|-----------|
| 1 | Admin | `Flavor_Notification_Center` y `Flavor_Notifications_System` registran los mismos `wp_ajax_*` y crean la misma tabla. Conflicto activo de handlers. | **Crítica** |
| 2 | APKs | 30+ TODOs reales en `chat_service.dart`, `media_upload_service.dart` mientras `mobile-apps/AUDITORIA_COMPLETA_APPS.md` declara "0 TODOs". Doc miente. | **Alta** |
| 3 | APKs | APKs binarios (130–261 MB) commiteados en `mobile-apps/build/`. Repo crece exponencialmente. | **Alta** |
| 4 | Rendimiento | N+1 queries con `get_post_meta` en loops de 6+ módulos (circulos-cuidados, biodiversidad-local, justicia-restaurativa, etc.). +100ms/listado. | **Alta** |
| 5 | Rendimiento | 4 crons custom con intervalo `every_minute` (network-webhooks, radio, email-marketing, notifications). 5–10K queries/día evitables. | **Alta** |
| 6 | Red | Webhooks federación: ventana de replay 5 min sin nonce. Replay attack viable. | **Alta** |
| 7 | Red | Mesh: `/peers/list` y `/peers/{id}` públicos sin rate limiting. Sybil/flooding trivial. | **Alta** |
| 8 | VBP | `FLAVOR_VBP_ALLOW_LEGACY_KEY` + key hardcoded `flavor-vbp-2024`. Si la constante se enciende en prod = backdoor. | **Alta** |
| 9 | VBP | `Flavor_VBP_REST_API::ejecutar_shortcode_html()` (`class-vbp-rest-api.php:1122`) hace `echo do_shortcode()` sin sanitizar el output como respuesta REST. Riesgo XSS. | **Alta** |
| 10 | Módulos | `crowdfunding` y `marketplace` registran shortcodes 2 veces (en módulo y en frontend controller). | **Alta** |

---

## 1. Visual Builder Pro (VBP)

VBP está en buen estado funcional, pero arrastra varias cosas heredadas del crecimiento rápido.

### Bugs activos
- `includes/visual-builder/class-visual-builder.php:949-950, 958-959` — `ajax_undo()` / `ajax_redo()` retornan éxito pero el historial nunca se mantiene. La UI miente al usuario.
- `addons/flavor-visual-builder-pro/class-vbp-rest-api.php:1122` — `echo do_shortcode($html)` dentro de un endpoint REST, sin escapar output. **Vector XSS si un shortcode genera HTML controlado por el atacante.**
- `addons/flavor-visual-builder-pro/class-vbp-rest-api.php:1690-1750` — `exportar_html()` arma doctype + estructura HTML inline en PHP en lugar de delegar en un template. Mantenibilidad pobre.

### A completar
- `class-vbp-editor.php:542` — emoji-picker se carga desde jsdelivr CDN; TODO de mover local o lazy.
- `class-vbp-rest-api.php:1152-1159` — `obtener_bloques()` hace `class_exists` pero no instancia; falla silenciosa devolviendo `[]`.
- `class-vbp-symbols.php:389, 418, 871, 907, 1219, 1888` — múltiples retornos `null` sin `WP_Error`. El cliente no puede distinguir "no existe" de "falló la consulta".

### A eliminar
- Posible duplicidad `Flavor_VB_All_Components` (core) vs `Flavor_VBP_Component_Library` (Pro). Validar si ambas registran el mismo catálogo.

### A revisar
- `FLAVOR_VBP_ALLOW_LEGACY_KEY` con key estática `flavor-vbp-2024` (`class-vbp-rest-api.php:2382-2389`). Solo loguea aviso. Eliminar la rama legacy o requerir flag por entorno.
- `class-vbp-unsplash.php:382-399` — sin caché en transient antes del `wp_remote_get` a Unsplash. Cada búsqueda = HTTP hit.
- `VBP_Figma_Tokens` sin prefijo `Flavor_` — anomalía de naming.

---

## 2. Módulos (90)

El sistema de módulos funciona, pero hay metadata declarativa al 1% y duplicación funcional preocupante.

### Bugs activos
- **Shortcodes registrados dos veces** en `crowdfunding` y `marketplace` (clase del módulo + frontend controller). La función se ejecuta doble.
- `crowdfunding`: `add_action('init', [$this, 'register_shortcodes'])` también desde el frontend controller.
- `class-module-loader.php:~470` — TODO en `get_module_dependencies()`. Aún hardcoded en lugar de leer de schema.

### A completar
- **69 de 70 módulos sin `module-schema.json`**. El loader no puede leer dependencias declarativas; categorización y visibilidad dependen de heurísticas.
- 15+ módulos sin carpeta `templates/` aunque su clase los referencia: `woocommerce`, `documentacion-legal`, `multimedia`, `ayuda-vecinal`, `dex-solana`, `chat-grupos`, `socios`, `huertos-urbanos`, `reservas`, `chat-interno`.
- 97 endpoints REST sin schema OpenAPI.
- `modules/PLANTILLA_MODULO.php` (528 líneas) sin documentación de uso.

### A eliminar
- **`economia-don` vs `economia-suficiencia`** — clases casi idénticas. Decidir cuál sobrevive.
- **`chat-grupos` vs `chat-interno` vs `chat-estados`** — 3 módulos de chat con post_types y shortcodes solapados. Necesita consolidación o roles claros.
- **`bares` vs `empresas` vs `empresarial`** — verificar overlap de CPTs.

### A revisar
- `dex-solana` (114 KB) y `trading-ia` — desconectados del ecosistema comunitario. ¿Experimentos vivos o muertos?
- `kulturaka` no implementa `get_tool_definitions()` (interface chat).
- `class-module-loader.php::get_modules_to_load()` — resuelve por `$_GET['page']` sin caché. O(n) por request, n=70.

---

## 3. APKs / mobile-apps

El subsistema con más drift entre lo documentado y lo real.

### Bugs activos
- `mobile-apps/lib/core/services/chat_service.dart:482-531` — `getConversations()`, `getMessages()`, `sendMessage()` son **stubs**. El chat real no funciona contra backend. **Crítico si se cree que está terminado.**
- `mobile-apps/lib/core/config/app_config.dart:14-16` — `serverUrl=https://sitio-prueba.local` con cert autofirmado. SSL handshake falla en dev.
- `mobile-apps/lib/core/config/app_config.dart:28` — `apiKey = ''` hardcoded vacío. Si el backend la exige = 401 silencioso.
- `mobile-apps/lib/core/config/app_config.dart:42-45` — `pinnedCertificates = []`. Sin pinning en producción.
- **APKs binarios (130–261 MB) en `mobile-apps/build/app/outputs/flutter-apk/`** dentro del repo git.
- `admin/class-apk-builder.php:572-596` — `flavor_apps_config` se lee pero la opción no se inicializa nunca explícitamente; depende de defaults silenciosos.

### A completar
- `media_upload_service.dart` — TODO "implementar subida real con http/dio".
- `qr_setup_service.dart` — pantalla incompleta.
- `class-apk-builder.php:1482-1550` — `ajax_start_build()` genera config JSON pero **no compila** ningún APK realmente. Solo es preview.
- `mobile-apps/AUDITORIA_COMPLETA_APPS.md` declara "0 TODOs" cuando hay 30+. Doc desactualizada.

### A eliminar
- `mobile-apps/build_app.sh` (v1) vs `build_app_v2.sh` vs `build-custom-apk.sh` vs `build-release.sh` — **4 scripts de build solapados**. Elegir 1 canónico.
- `mobile-apps/personalizar-app.sh` no se invoca desde wp-admin (admin tiene su propio flow). Probable abandonado.
- `*.iml`, `.idea/`, `.dart_tool/` deberían estar en `.gitignore`.

### A revisar
- Autenticación incoherente: JWT + refresh en `TokenManager.dart`, pero `personalizar-app.sh` llama a `/app-config/generate` sin auth. ¿Endpoint público?
- Sincronización tabs admin (`flavor_apps_config['tabs']`) ↔ `lib/main_client_home.dart`. Sin fuente única, drift garantizado.
- 34 clases REST en `includes/api/` — verificar que todas las que la app llama están realmente registradas.
- Tamaño bundle: APK release 130 MB (Flutter típico 50–80 MB). Sospecha de pantallas/deps no usadas.

---

## 4. Rendimiento

### Bugs (lentitud activa)
- **N+1 queries en 6+ módulos** (`circulos-cuidados`, `biodiversidad-local`, `justicia-restaurativa`, etc.) — `get_post_meta` dentro de `foreach($posts)` sin precarga. ~100 ms por listado de 10. **Crítico**.
- **4 crons `every_minute`**: `class-network-webhooks.php:81`, `modules/radio/:180`, `modules/email-marketing/:332`, `notifications/class-notification-manager.php:94`. 5760 ejecuciones/día solo de estos 4. Reducir a 5–15 min.
- `includes/class-chat-assets.php:59` — Alpine.js (52 KB) + flavor-modules.js encolados en TODAS las páginas frontend, antes de saber si hay shortcode.
- `flavor-platform.php:381, 386, 454, 459` — `get_option()` legacy + new en paralelo sin caché en variable. 2–3 queries duplicadas en cada request.

### A completar (caché incompleto)
- Dos cache managers paralelos (`class-cache-manager.php` + `class-performance-cache.php`) con convenciones distintas. REST web-vitals NO cachea.
- Transients de `activity-log.php:264, 345` — `DELETE` masivos en `invalidate_*`, pero los `SET` correspondientes son inciertos.
- `class-frontend-assets.php:200-207` — cachea presencia de shortcodes en post_meta, pero los 569 `wp_create_nonce()` + `wp_localize_script()` se rehacen cada request.

### A eliminar
- **`assets/node_modules/`** (~500 MB con jQuery, terser, @babel/parser) en repo. Nunca se enquena.
- `includes/network/class-network-admin.php:73-74` — Leaflet (140 KB) en `wp_enqueue_scripts` global; solo lo usan red-social y biodiversidad.
- 15+ `error_log()` sin guarda `WP_DEBUG` en producción.

### A revisar
- `flavor_active_modules` se respeta en boot, pero el loader carga 71 módulos en `plugins_loaded:10` antes del filtro. Falta early-exit.
- 20+ queries `SELECT *` sin `LIMIT` en `activity-log`, `network-node`, `carpooling`, `multimedia`, `documentacion-legal`.
- `assets/vbp/vendor/three/` (~53 MB) sin tree-shake.
- `assets/js/chat-widget.js` (43 KB) sin lazy.

---

## 5. Red de nodos / mesh / federation

Es el subsistema con más superficie de seguridad sin endurecer.

### Bugs activos
- **Endpoints `/directory`, `/map`, `/board`, etc.** (`includes/network/class-network-api.php:44-177`) — solo rate-limit por IP. No verifican identidad Ed25519 del peer remoto. En una malla, lectura no autenticada = exfil libre.
- **Webhooks**: ventana ±300 s para timestamp pero **sin nonce de un solo uso**. Replay attack durante 5 min con un solo paquete capturado (`class-network-webhooks.php:119-146`).
- **Mesh `/peers/list` y `/peers/{id}`** públicos sin rate limiter. Sybil/flooding triviales (`class-mesh-api.php:50-172`).
- **Cron mesh sin intervalos custom registrados**: `flavor_mesh_gossip_batch`, `flavor_mesh_heartbeat`, `flavor_mesh_cleanup_expired` se programan pero los intervalos `every_minute`/`every_5_minutes` no se añaden a `cron_schedules`. Silenciosamente nunca corren (`class-gossip-protocol.php:90-98`).
- **CRDT merge sin convergencia garantizada**: vector clocks presentes pero sin nonce global. LWW Register puede divergir (`class-gossip-protocol.php:104-173`).

### A completar
- Peer discovery sin DHT — solo bootstrap nodes estáticos.
- Gossip sin batching real, sin TTL decremento entre hops.
- Marketplace federado: `imagen_url` apunta a host remoto, sin proxy/caché. Si el nodo remoto cae = imágenes rotas.
- Webhooks sin reintento exponencial ni dead-letter queue.
- Inconsistencia firma: mesh usa Ed25519 (sodium), core federación usa HMAC-SHA256. Sin mapeo entre identidades.

### A eliminar
- `includes/api/class-federation-api.php` — referenciado en docs antiguas, no instanciado tras migración a mesh v1.5.0.
- Tablas `flavor_network_favorites`, `flavor_network_recommendations`, `flavor_network_matches` creadas pero ningún endpoint las lee/escribe.
- Templates `network-favorites.php`, `network-recommendations.php` — shortcodes registrados, nunca renderizados.

### A revisar
- **`Flavor_Network_API` vs `Flavor_Mesh_API`** exponen `/directory`, eventos, colaboraciones. ¿Cuál es el canónico? Definir roles: core = node management, mesh = P2P puro.
- `check_admin_permission()` core requiere `is_user_logged_in()`. En P2P no hay sesión WP — escritura de peer remoto = 403 siempre.
- `tools/test-mesh-system.php` solo verifica clases/tablas, no cubre Sybil, replay, regresión de vector clock.

---

## 6. Admin / UX / usabilidad

### Bugs UX
- **`Flavor_Notification_Center` y `Flavor_Notifications_System` ambas registran `wp_ajax_flavor_mark_notification_read` y `wp_ajax_flavor_delete_notification`, ambas crean `wp_flavor_notifications`**. Conflicto activo. Una tiene que ganar.
- `class-chat-settings.php:762-763` — `wp_nonce_field()` duplicado en el mismo form.
- `class-apk-builder.php:937` — string "Próximamente" sin `__()` (mock_items kombucha).
- `class-flavor-systems-admin-panel.php:90-101` — depende de jquery-ui-tabs/dialog, deprecated en WP moderno.
- `admin/assets/css/unified-admin-panel.css:227` — único media query `782px`. Sin breakpoint <480px → móvil roto.

### A completar
- `class-contextual-help.php:197-216` — solo `flavor-dashboard` y `flavor-modules` tienen help_tabs. Las otras ~48 páginas, vacías.
- `class-accessibility.php:58-67` — declara skip_links/focus_indicators pero los CSS/JS asociados (`dashboard-a11y.css`, `vbp-accessibility.js`) no se enquean en admin.
- `frontend-shortcuts.php:179, 200` — algunos handlers AJAX validan nonce solo cliente; falta server-side.
- `class-app-profile-admin.php:2014-2297` — sección Demo Data sin `wp_verify_nonce()` server-side claro.

### A eliminar
- `class-admin-menu-manager.php:39-66` — 14 constantes `@deprecated` con callbacks `*_legacy()` que solo redirigen.
- `class-license-admin.php:80, 171` — `redirect_legacy_page()` huérfano.
- `includes/class-chat-ajax.php.backup` — archivo `.backup` huérfano (también flagged por VBP).

### A revisar
- 50+ páginas admin sin patrón visual unificado: unas Alpine.js, otras jQuery. Decidir framework canónico.
- Menú admin plano sin agrupación. Mobile UX pobre.
- Validación AJAX inconsistente: `check_ajax_referer` vs `wp_verify_nonce` custom. Patrón único pendiente.
- Modales `app-menu-configurator` (`class-app-menu-configurator.php:679`) sin viewport correcto en iPad.

---

## Plan de acción sugerido (priorizado)

### P0 — bugs críticos / seguridad (≤ 1 sprint)
1. **Eliminar duplicación `Notification_Center` ↔ `Notifications_System`**: decidir cuál sobrevive y desregistrar la otra.
2. **Sanitizar `do_shortcode` en VBP REST** (`class-vbp-rest-api.php:1122`): `wp_kses_post` o template controlado.
3. **Quitar `FLAVOR_VBP_ALLOW_LEGACY_KEY`** y la rama de la API key estática.
4. **Webhooks federación: añadir nonce de un uso** + lookup de `nonce_seen` con TTL 5 min.
5. **Mesh `/peers/*`: aplicar rate limiter** y `verify_peer_signature()` en endpoints de listado.
6. **Cron mesh: registrar intervalos custom** en `cron_schedules` (`every_minute`, `every_5_minutes`).
7. **Quitar APKs binarios del repo** + añadir `mobile-apps/build/`, `*.iml`, `.idea/`, `.dart_tool/` a `.gitignore`. Considerar `git filter-repo` para purgar historia.
8. **Eliminar duplicación shortcodes** crowdfunding y marketplace.

### P1 — performance & deuda visible (1–2 sprints)
9. Reducir crons `every_minute` a `every_5_minutes` o `every_15_minutes` (evaluar caso por caso).
10. N+1: precargar metas en los 6 módulos con `update_postmeta_cache` o queries batch.
11. Mover Alpine + flavor-modules a enqueue condicional por shortcode/CPT.
12. Caché transient en `class-vbp-unsplash.php`.
13. Borrar `assets/node_modules/`, mover dev deps fuera del plugin.
14. Decidir y consolidar 4 scripts `build_app*.sh` en uno + documentar.

### P2 — limpieza estructural (continuo, low-risk)
15. Resolver duplicados `economia-don/economia-suficiencia`, módulos chat-*, módulos empresariales (`bares`/`empresas`/`empresarial`).
16. Eliminar tablas `flavor_network_favorites|recommendations|matches` o implementar el feature.
17. Borrar `class-federation-api.php`, templates `network-favorites/recommendations`.
18. Decidir destino de `dex-solana`, `trading-ia`: scope o eliminar.
19. Consolidar `Flavor_Network_API` ↔ `Flavor_Mesh_API` con roles claros.
20. Help_tabs y accesibilidad sistemática en admin.

### P3 — productización (cuando haya margen)
21. Generar `module-schema.json` automáticamente para los 70 módulos sin él.
22. Tests de Sybil / replay / vector-clock regression para mesh.
23. Implementar `chat_service.dart` real (o documentar el subsistema como POC).
24. Reescribir `mobile-apps/AUDITORIA_COMPLETA_APPS.md` con datos reales.
25. Unificar framework JS admin (Alpine vs jQuery).

---

## Métricas globales

- **Hallazgos totales**: ~89 distribuidos en 6 dominios.
- **Bugs activos**: ~22 (de los cuales 6–8 con severidad alta o crítica).
- **A completar**: ~28 features parciales o stubs.
- **A eliminar**: ~22 elementos muertos / duplicados.
- **A revisar**: ~17 sospechas que requieren validación de uso real.

Los dominios más críticos son **APKs** (drift entre doc y realidad, repo bloated) y **red/mesh** (varios vectores de seguridad), seguidos de **rendimiento** (N+1 + crons cada minuto). VBP y módulos están funcionalmente bien pero arrastran duplicaciones acumuladas. Admin/UX necesita una pasada de consolidación más que cirugía.
