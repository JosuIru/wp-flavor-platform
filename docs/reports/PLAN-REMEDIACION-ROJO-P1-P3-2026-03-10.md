# Plan de remediación módulos ROJO (P1/P2/P3)

Fecha: 2026-03-10
Base: `reports/ESTADO-REAL-PLUGIN-2026-03-10.md`

## Objetivo
Pasar los 14 módulos en ROJO a estado operativo controlado sin romper módulos VERDE.

## Nota de precisión
El semáforo estructural marca ROJO cuando falta `views/dashboard.php` o `frontend/`, pero varios módulos sí tienen lógica funcional vía `class-*-dashboard-tab.php` y shortcodes.
Por eso este plan separa:
- ROJO bloqueante real (impacta carga/UX/rutas)
- ROJO estructural (deuda de estandarización)

## P1 (bloqueante, ejecutar primero)

### 1) Congelar rutas/estado de módulos “sistema” no funcionales
- Módulo: `assets` (pseudo-módulo técnico, no de negocio).
- Acción: excluirlo del catálogo funcional y del semáforo de módulos de producto.
- Archivos:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/reports/ESTADO-REAL-PLUGIN-2026-03-10.md` (criterio)
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/assets/*` (dejar como soporte)

### 2) Blindar dashboards que hoy pueden quedar en loading
- Módulos afectados por rutas/flujo: `advertising`, `chat-grupos`, `chat-interno`, `facturas`, `empresarial`.
- Acción: forzar fallback si faltan assets/view y evitar spinner infinito.
- Archivos:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/admin/class-admin-menu-manager.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/admin/class-dashboard.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/admin/views/dashboard.php`

### 3) Definir estado “experimental” para módulos de trading/cripto
- Módulos: `dex-solana`, `trading-ia`, `themacle`.
- Acción: feature flag explícita (OFF por defecto en local/prod), mensaje de módulo experimental, sin intentar inicialización pesada si faltan tablas/config.
- Archivos:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/dex-solana/class-dex-solana-module.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/trading-ia/class-trading-ia-module.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/themacle/class-themacle-module.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-app-profiles.php`

### 4) Resolver colisiones de shortcodes en módulos ROJO/adyacentes
- Síntoma: tags duplicados y sobrescritura por orden de carga.
- Acción: namespacing único + alias legacy con deprecación.
- Archivos base:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-module-shortcodes.php`
  - módulos con duplicados detectados (chat, facturas, marketplace, incidencias, reservas, etc.)

## P2 (normalización funcional por módulo)

### 5) Crear frontend-controller mínimo donde falta carpeta `frontend/`
Objetivo: URL estable y puente a shortcodes existentes.
- `chat-grupos`:
  - crear: `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/chat-grupos/frontend/class-chat-grupos-frontend-controller.php`
  - reutilizar shortcodes ya existentes en `class-chat-grupos-module.php`
- `chat-interno`:
  - crear: `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/chat-interno/frontend/class-chat-interno-frontend-controller.php`
- `facturas`:
  - crear frontend controlador para `mis_facturas`, `detalle`, `pagar`
- `empresarial`:
  - frontend controlador para landing/módulo corporativo

### 6) Homologar dashboard view para módulos con dashboard-tab pero sin `views/dashboard.php`
Módulos: `bares`, `encuestas`, `huella-ecologica`, `sello-conciencia`, `trading-ia`, `dex-solana`, `themacle`, `chat-grupos`, `chat-interno`, `fichaje-empleados`.
- Acción: crear `views/dashboard.php` canónico que use renderizador/tab actual.
- Beneficio: shell/admin uniforme + menor deuda en auditorías futuras.

### 7) Estandarizar módulos admin-only
Módulos: `advertising`, `encuestas` (si procede), `facturas` (si procede), `empresarial` (según perfil).
- Acción: marcar en metadata `admin_only` para que no se exijan rutas frontend en semáforo.
- Archivos objetivo:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-module-access-control.php`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/class-app-profiles.php`

## P3 (calidad y release)

### 8) Rehacer métrica de auditoría (estado real, no solo estructura)
- Nuevo criterio sugerido por módulo:
  - `init` y clase cargable
  - tablas/config válidas
  - al menos una vía de render (dashboard tab o view o shortcode frontend)
  - health endpoint básico OK
- Entregable:
  - script auditor y salida JSON/MD reproducible.

### 9) Móvil/APK: consolidación release
- Estado actual detectado: APK debug.
- Acción:
  - definir entrypoint estable (`main_selector.dart` o política clara admin/client)
  - generar build release firmado
  - comprobar `server_config.dart` por entorno
- Archivos:
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/mobile-apps/lib/main_selector.dart`
  - `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/mobile-apps/lib/core/config/server_config.dart`
  - scripts `build-release.sh` / `build_app.sh`

## Priorización de ejecución (orden recomendado)
1. P1.1 + P1.2 (estabilidad dashboard y carga)
2. P1.3 (feature flags trading/dex/themacle)
3. P1.4 (shortcodes duplicados críticos)
4. P2.5 + P2.6 (normalización frontend/dashboard de módulos ROJO)
5. P2.7 (admin-only metadata)
6. P3.8 + P3.9 (auditoría reproducible y release móvil)

## Resultado esperado
- ROJO bloqueante real: **0**
- ROJO estructural: **<= 3** (solo módulos explícitamente experimentales)
- Dashboard admin sin loading infinito
- Inventario de shortcodes sin colisiones no controladas
- Build móvil release trazable
