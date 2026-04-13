# Auditoría de APIs REST - Flavor Platform 3.3.0

**Fecha:** 2026-03-21
**Versión:** 3.3.0
**Alcance:** Verificación de endpoints REST API y su inicialización

---

## Resumen Ejecutivo

### 🔴 PROBLEMA CRÍTICO DETECTADO

**Las APIs principales NO están inicializadas en el bootstrap del plugin.**

- ❌ `Flavor_Site_Builder_API` - Cargada pero NO inicializada
- ❌ `Flavor_VBP_Claude_API` - Cargada pero NO inicializada

Esto significa que los endpoints REST definidos en CLAUDE.md **NO están disponibles** actualmente.

---

## Análisis Detallado

### 1. APIs Definidas

El sistema tiene 23 archivos de API en `includes/api/`:

| API | Archivo | Namespace | Inicializada |
|-----|---------|-----------|--------------|
| App Config API | class-app-config-api.php | - | ✅ Sí |
| Client Dashboard API | class-client-dashboard-api.php | - | ❓ Revisar |
| Media API | class-media-api.php | flavor-media/v1 | ✅ Sí |
| Module Manager API | class-module-manager-api.php | flavor-modules/v1 | ✅ Sí |
| SEO API | class-seo-api.php | flavor-seo/v1 | ✅ Sí |
| Site Builder API | class-site-builder-api.php | flavor-site-builder/v1 | ❌ **NO** |
| Site Config API | class-site-config-api.php | - | ✅ Sí |
| VBP Claude API | class-vbp-claude-api.php | flavor-vbp/v1 | ❌ **NO** |
| VBP Diagnostics | class-vbp-diagnostics.php | - | ❓ Revisar |
| VBP Preview API | class-vbp-preview-api.php | - | ❓ Revisar |
| Mobile API | class-mobile-api.php | - | ❓ Revisar |
| Federation API | class-federation-api.php | - | ❓ Revisar |

### 2. Problema en Bootstrap

**Ubicación:** `includes/bootstrap/class-bootstrap-dependencies.php:307-316`

```php
// API REST para integración con Claude Code / VBP
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-claude-api.php';
// ❌ FALTA: Flavor_VBP_Claude_API::get_instance();

// Site Builder API para creación completa de sitios
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-builder-api.php';
// ❌ FALTA: Flavor_Site_Builder_API::get_instance();
```

**Comparación con APIs que SÍ funcionan:**

```php
// API de Configuración de Sitio
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-config-api.php';
Flavor_Site_Config_API::get_instance(); // ✅ CORRECTO

// API de Media
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-media-api.php';
Flavor_Media_API::get_instance(); // ✅ CORRECTO
```

### 3. Endpoints Afectados

#### Site Builder API (`flavor-site-builder/v1`)

Endpoints documentados en CLAUDE.md que **NO están disponibles**:

- `GET /profiles` - Listar perfiles
- `GET /templates` - Listar plantillas
- `GET /themes` - Listar temas
- `GET /modules` - Listar módulos
- `POST /site/validate` - Validar configuración
- `POST /site/create` - Crear sitio completo
- `GET /site/status` - Estado del sitio
- `POST /modules/activate` - Activar módulos
- `POST /pages/create-for-modules` - Crear páginas
- `POST /menu` - Crear/asignar menú
- `POST /profile/set` - Establecer perfil
- `POST /theme/apply` - Aplicar tema
- `GET /design/options` - Opciones de diseño
- `POST /demo-data/import` - Importar datos demo

#### VBP Claude API (`flavor-vbp/v1/claude`)

Endpoints documentados en CLAUDE.md que **NO están disponibles**:

- `GET /claude/status` - Estado de VBP
- `GET /claude/capabilities` - Capacidades
- `GET /claude/blocks` - Listar bloques
- `GET /claude/schema` - Esquema completo
- `GET /claude/pages` - Listar páginas VBP
- `POST /claude/pages` - Crear página
- `PUT /claude/pages/{id}` - Actualizar página
- `POST /claude/pages/{id}/publish` - Publicar página
- `POST /claude/pages/styled` - Crear página con estilos
- `GET /claude/templates` - Plantillas VBP
- `GET /claude/section-types` - Tipos de sección
- `GET /claude/design-presets` - Presets de diseño
- `GET /claude/widgets` - Widgets disponibles

---

## Impacto

### 🚨 Funcionalidades Bloqueadas

1. **Creación automatizada de sitios desde Claude Code**
   - Todo el flujo documentado en CLAUDE.md está roto
   - No se pueden crear sitios con plantillas predefinidas
   - No se pueden activar módulos vía API

2. **Visual Builder Pro desde Claude Code**
   - No se pueden crear páginas con VBP vía API
   - No se pueden obtener plantillas ni presets
   - No se puede usar el flujo de diseño automatizado

3. **Configuración de Apps móviles**
   - Los endpoints de configuración de APKs podrían no estar disponibles

### ⚠️ Documentación Inconsistente

- `CLAUDE.md` documenta endpoints que no existen en runtime
- Los ejemplos de curl en la documentación fallarán todos
- Instrucciones para Claude Code no funcionarán

---

## Solución Propuesta

### Opción 1: Inicialización Inmediata (Recomendada)

Modificar `includes/bootstrap/class-bootstrap-dependencies.php` líneas 307-316:

```php
// API REST para integración con Claude Code / VBP
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-claude-api.php';
Flavor_VBP_Claude_API::get_instance(); // AÑADIR ESTA LÍNEA

// API de Diagnóstico VBP
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-diagnostics.php';
Flavor_VBP_Diagnostics::get_instance(); // AÑADIR ESTA LÍNEA

// API de Preview VBP
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-preview-api.php';
Flavor_VBP_Preview_API::get_instance(); // AÑADIR ESTA LÍNEA

// Site Builder API
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-builder-api.php';
Flavor_Site_Builder_API::get_instance(); // AÑADIR ESTA LÍNEA
```

### Opción 2: Verificar otras APIs

Revisar si hay más APIs que también necesitan inicialización:

```bash
cd includes/api
grep -l "register_rest_route" *.php | while read file; do
    class=$(grep "^class " "$file" | head -1 | awk '{print $2}')
    init=$(grep -l "${class}::get_instance()" ../bootstrap/class-bootstrap-dependencies.php 2>/dev/null)
    if [ -z "$init" ]; then
        echo "❌ $file ($class) - NO inicializada"
    else
        echo "✅ $file ($class) - Inicializada"
    fi
done
```

---

## Verificación Post-Fix

Una vez aplicada la solución, verificar con:

```bash
cd /ruta/wordpress

# 1. Verificar que las APIs se registran
wp rest-api list --format=json | jq -r '.[] | select(.namespace | contains("flavor"))'

# 2. Probar endpoint de salud
curl -s "http://sitio.local/wp-json/flavor-site-builder/v1/system/health" \
  -H "X-VBP-Key: flavor-vbp-2024"

# 3. Probar endpoint VBP
curl -s "http://sitio.local/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: flavor-vbp-2024"
```

---

## Conclusiones

1. **Severidad:** CRÍTICA
2. **Componentes afectados:** Site Builder API, VBP Claude API
3. **Usuarios afectados:** Cualquier automatización con Claude Code
4. **Tiempo estimado de fix:** 5-10 minutos
5. **Prioridad:** P0 - Bloqueante para automatización

---

## Historial

- **2026-03-21:** Detección del problema durante auditoría de APIs
- **Pendiente:** Aplicar fix y verificar

---

## Referencias

- `includes/api/class-site-builder-api.php`
- `includes/api/class-vbp-claude-api.php`
- `includes/bootstrap/class-bootstrap-dependencies.php`
- `CLAUDE.md` - Documentación de APIs
- `docs/api/CLAUDE-API-GUIDE.md`
