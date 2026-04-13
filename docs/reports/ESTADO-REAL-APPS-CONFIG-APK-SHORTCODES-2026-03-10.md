# Estado Real Plugin - Apps Config / APK / Shortcodes (2026-03-10)

## Resumen ejecutivo

Estado global actual: **operativo y validado end-to-end**.  
No se observan bloqueos críticos activos en dashboard/API/APK.

## Semáforo por área

### 1) Dashboard admin `/wp-admin/admin.php?page=flavor-dashboard`
- Estado: **VERDE**
- Evidencia:
  - Smoke previo con sesión: dashboard `HTTP 200`.
  - Assets críticos CSS del dashboard resolviendo `HTTP 200`.
  - Sin errores 404 actuales en assets principales.

### 2) Página apps config `/wp-admin/admin.php?page=flavor-apps-config`
- Estado: **VERDE**
- Hecho:
  - Arquitectura de formulario corregida para evitar forms anidados (Push fuera de `options.php`).
  - Validación de pestaña activa con fallback seguro a `general`.
  - Organización de tabs principal + dropdown avanzada consolidada.
- Validado autenticado (2026-03-10):
  - Todas las pestañas devuelven `HTTP 200`.
  - Markers de contenido detectados en: general, navegación, branding, módulos, push, seguridad, stats, deeplinks, idiomas, directorio, diagnóstico y tools.
  - Estructura de formularios OK:
    - General usa `options.php`.
    - Push usa formulario dedicado.
  - Nota operativa:
    - Si la vista activa del usuario es `gestor_grupos`, `flavor-apps-config` puede dar 403 aunque el usuario sea admin.
    - El smoke ahora fuerza cambio automático a vista `admin` cuando detecta ese estado.

### 3) API mobile `chat-ia-mobile/v1/client-app-config`
- Estado: **VERDE**
- Evidencia en vivo:
  - `HTTP 200`, `success=true`.
  - Respuesta incluye:
    - `navigation_type`
    - `show_appbar`
    - `tabs` y `drawer_items`
    - `quick_actions`
    - `home_widgets`
  - Conteos observados en entorno local:
    - `tabs_count=5`
    - `drawer_count=19`
    - `quick_actions_count=10`
    - `home_widgets_count=6`

### 4) APKs y distribución
- Estado: **VERDE**
- Evidencia en vivo:
  - `/app-downloads/flavour-app-admin.apk` -> `HTTP 200`
  - `/app-downloads/flavour-app-cliente.apk` -> `HTTP 200`

### 5) Integración APK cliente con configuración del panel
- Estado: **VERDE**
- Hecho:
  - Cliente Flutter alineado para respetar `navigation_type`/`show_appbar`.
  - Home cliente consume `quick_actions` y `home_widgets` del backend con fallback.
- Validado técnico:
  - `navigation_type/show_appbar` consumidos por cliente.
  - `quick_actions/home_widgets` consumidos con fallback seguro.

### 6) Shortcodes
- Estado: **VERDE OPERATIVO**
- Hecho:
  - Endurecimiento de múltiples módulos con guardas `shortcode_exists` para evitar colisiones y dobles registros.
  - Limpieza de duplicidades relevantes (incluyendo grupos de consumo).
  - Caso `marketplace_formulario` dejado compatible y controlado (coexistencia intencional).
- Observación:
  - Recomendable mantener un barrido funcional periódico de shortcodes en páginas reales al añadir módulos nuevos.

## Cambios relevantes aplicados hoy

- `includes/app-integration/class-app-config-admin.php`
  - Validación `allowed_tabs`.
  - Consolidación de tabs.
  - Separación segura de formularios (`options.php` vs Push).
- `includes/api/class-mobile-api.php`
  - Alineación de config móvil con `flavor_apps_config`.
  - Soporte `quick_actions` y `home_widgets` adaptativos.
- `mobile-apps/lib/main_client.dart`
  - Navegación dinámica real según config backend.
- `mobile-apps/lib/features/client/client_dashboard_screen.dart`
  - Consumo de `quick_actions` y `home_widgets` en home.
- `dev-scripts/smoke-apps-config.sh`
  - Nuevo smoke unificado para apps-config + API + APK.

## Smoke test unificado disponible

```bash
BASE_URL="http://sitio-prueba.local" bash dev-scripts/smoke-apps-config.sh
```

Con login para validar contenido interno de pestañas:

```bash
WP_USER="tu_usuario" WP_PASS="tu_password" BASE_URL="http://sitio-prueba.local" bash dev-scripts/smoke-apps-config.sh
```

## Conclusión

El plugin queda en estado **completo operativo** para dashboard + apps-config + API mobile + distribución APK, con validación autenticada de pestañas y smoke unificado actualizado.
