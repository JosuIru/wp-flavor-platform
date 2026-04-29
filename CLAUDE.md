# Flavor Platform - Instrucciones para Claude Code

## REGLAS CRITICAS

### PROHIBIDO
1. **NUNCA crear paginas con HTML/Gutenberg** - Usar Visual Builder Pro API
2. **NUNCA activar +20 modulos** sin justificacion
3. **NUNCA configurar tema no instalado**
4. **NUNCA crear menus sin asignar ubicaciones**
5. **NUNCA compilar APKs sin `build_app.sh`** (hay 2 APKs: client y admin)

### OBLIGATORIO
1. Ejecutar validacion primero
2. Usar VBP para paginas con diseno
3. Verificar tema existe antes de activar
4. Asignar menus a ubicaciones (primary, footer, mobile)
5. Configurar footer con widgets

---

## Validacion Pre-vuelo

```bash
cd /ruta/wordpress
wp plugin is-active flavor-platform || echo "ERROR: Plugin no activo"
[ -d "wp-content/themes/flavor-starter" ] || echo "ERROR: Tema no instalado"
[ "$(wp option get template)" = "flavor-starter" ] || echo "ERROR: Tema no activo"

API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/system/health" -H "X-VBP-Key: $API_KEY"
```

---

## Discovery (ANTES de componer paginas)

```bash
# Inventario completo
bash tools/vbp-inventory.sh "http://SITIO"

# O consultas especificas:
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/blocks" -H "X-VBP-Key: $API_KEY"
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/design-presets" -H "X-VBP-Key: $API_KEY"
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/modules" -H "X-VBP-Key: $API_KEY" | jq '.[] | select(.active==true)'
```

**REGLAS:**
- NUNCA usar bloques que no aparezcan en `/claude/blocks`
- NUNCA referenciar modulos no ACTIVOS
- Si algo no existe, PREGUNTAR

---

## API Key

```bash
# Obtener
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")

# Usar en requests
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/status" -H "X-VBP-Key: $API_KEY"

# Regenerar (produccion)
wp eval "echo flavor_regenerate_vbp_api_key();"
```

---

## Crear Paginas (SIEMPRE VBP)

```bash
# Pagina con preset
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{"title": "Mi Landing", "preset": "modern", "sections": ["hero", "features", "cta"], "status": "publish"}'

# Pagina funcional (shortcode)
wp post create --post_type=page --post_title="Portal" --post_content='[flavor_unified_dashboard]' --post_status=publish
```

---

## Crear Sitio Completo

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "name": "Mi Cooperativa",
    "modules": ["grupos_consumo", "socios", "eventos"],
    "create_pages": true,
    "create_menus": true,
    "theme": "light"
  }'
```

---

## Menus

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/menu" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "location": "primary",
    "items": [
      {"title": "Inicio", "url": "/"},
      {"title": "Productos", "url": "/productos"}
    ]
  }'
```

---

## Apps Moviles

Ver `CLAUDE-APK.md` para documentacion completa.

```bash
# Verificar soporte de modulos (3 niveles: WordPress + Flutter + API)
bash tools/apk-inventory.sh "http://SITIO" "mobile-apps"

# Compilar (OBLIGATORIO usar script)
cd mobile-apps/
./build_app.sh client release  # APK cliente
./build_app.sh admin release   # APK admin
```

---

## Checklist Final

```bash
[ "$(wp option get template)" = "flavor-starter" ] && echo "OK Tema"
[ $(wp option get flavor_active_modules --format=json | jq length) -lt 20 ] && echo "OK Modulos"
[ $(wp option get page_on_front) -gt 0 ] && echo "OK Homepage"
wp menu location list | grep -q primary && echo "OK Menus"
```

---

## Patrones Arquitectonicos (importantes al tocar core)

### 1. Migraciones de schema por checksum

`Flavor_Platform_Module_Base::ensure_database_schema()` es la unica via canonica
para sincronizar tablas de un modulo con `dbDelta()`. NO llames directamente a
`maybe_create_tables()` ni a `create_tables()` desde hooks.

- Calcula `md5_file()` del archivo de la clase y lo compara con la opcion
  `flavor_module_db_checksum_{id}`.
- Si difiere, invoca `create_tables()` (o cae a `maybe_create_tables()` por
  Reflection) y guarda el nuevo checksum.
- Hook: el module loader lo llama antes de `can_activate()`, asi que las
  tablas siempre existen al chequear capacidad.
- **Forzar re-ejecucion**: `wp eval "delete_option('flavor_module_db_checksum_<id>');"`
- **NO uses** `add_action('init', [..., 'maybe_create_tables'])`. Si lo ves en
  un PR, sustituyelo por `'ensure_database_schema'`.

### 2. Mesh Installer separado del Network Installer del core

Hay dos clases con responsabilidades distintas — no confundirlas:

- `Flavor_Network_Installer` (core, `includes/network/`): 24+ tablas globales
  de la red (eventos, marketplace, federation_log, etc.). Owner del schema base.
- `Flavor_Network_Mesh_Installer` (addon, `addons/flavor-network-communities/`):
  ciclo de vida de peers + keypair (Ed25519, sodium). 11 metodos:
  `get_local_peer`, `decrypt_private_key`, `generate_peer_keypair`, etc.
  Su option es `flavor_network_mesh_db_version`.

Si añades funcionalidad mesh (gossip, CRDT, peer discovery), usa
`Flavor_Network_Mesh_Installer::*`. Si tocas tablas de eventos/marketplace
federados, usa `Flavor_Network_Installer::*`.

### 3. Cache canónico: Flavor_Cache_Manager

Hay dos clases de caché en el plugin:

- **`Flavor_Cache_Manager`** (`includes/class-cache-manager.php`) — canónico.
  Métodos estáticos: `get`, `set`, `remember($key, $callback, $ttl)`, `period_key`.
  Usado en analytics, VBP REST, admin. Total: 4 archivos consumidores.
- **`Flavor_Performance_Cache`** (`includes/class-performance-cache.php`) —
  marcado `@deprecated` desde 3.6.0. Singleton con métodos de instancia.
  Usado solo en `class-client-dashboard-api.php` y `class-system-initializer.php`
  (3 callers). Conserva métodos extra (`precarga`, `mostrar_estadisticas`,
  `limpiar_cache_modulos`) que no tienen equivalente en Cache_Manager.

Para código nuevo: **siempre `Flavor_Cache_Manager::remember(...)`**. Si
necesitas estadísticas o limpieza por grupo, añade el método al
Cache_Manager antes de seguir usando Performance_Cache (evita ampliar
la deuda).

### 4. APK Builder: preview interactivo

El previsualizador de APK en `admin/class-apk-builder.php` debe permanecer
fiel al flujo Flutter real (`mobile-apps/lib/main_client_home.dart`):

- **Tabs nativas**: leer desde la opcion `flavor_apps_config['tabs']` via el
  endpoint AJAX `flavor_apk_get_tabs_config`. JS rebuilds la barra inferior
  llamando a `loadRealTabsConfig()` en init.
- **Datos por modulo**: `ajax_module_preview_data()` valida con `DESCRIBE`
  (cacheado en transient 5 min) que las columnas referenciadas existan antes
  de ejecutar el SELECT. Si fallan, devuelve `source='mock'` con
  `reason='schema_mismatch'`.
- **Heuristica de columnas**: `extract_columns_from_expression()` ignora
  keywords SQL (CONCAT, IFNULL, COALESCE, DATE_FORMAT, NOW, AS, ...) y strings.
- **modulesMeta**: cada modulo expone `material_icon`, `color` (hex),
  `description` y `mock_items` via `wp_localize_script`. Material Icons
  Outlined se carga desde Google Fonts CDN.
- **NO añadir queries** sin pasar por `get_module_preview_queries()` ni
  saltarse la validacion DESCRIBE.

---

## Documentacion Detallada

| Archivo | Contenido |
|---------|-----------|
| `docs/api/ENDPOINTS-REFERENCE.md` | Referencia completa de endpoints |
| `docs/api/EJEMPLOS-USO.md` | Ejemplos de curl |
| `docs/PLANTILLAS.md` | Plantillas, presets y secciones |
| `CLAUDE-APK.md` | Configuracion de apps moviles |

---

## Plantillas Rapidas

| Template | Modulos |
|----------|---------|
| `grupos_consumo` | grupos-consumo, socios, eventos, marketplace |
| `comunidad` | comunidades, participacion, eventos, foros |
| `asociacion` | socios, eventos, transparencia, foros |
| `cooperativa` | socios, transparencia, presupuestos-participativos |

## Presets de Diseno

`modern` | `community` | `cooperative` | `eco` | `fundraising` | `nature`
