# Análisis de Patrones de Inicialización de APIs REST

**Fecha:** 2026-03-21
**Objetivo:** Determinar el patrón correcto de inicialización para cada API

---

## Resumen Ejecutivo

De 19 APIs REST analizadas:

- ✅ **15 APIs usan Singleton completo** - Usar `::get_instance()`
- ⚠️ **3 APIs usan Singleton con constructor público** - Verificar manualmente
- ❌ **2 APIs NO usan Singleton** - Usar `new ClassName()`

---

## APIs con Singleton Completo (15)

Estas APIs tienen:
- `private static $instance`
- `public static function get_instance()`
- `private function __construct()`

**Inicialización:** `ClassName::get_instance();`

### Lista de APIs Singleton

1. ✅ **Flavor_API_Documentation**
   - Archivo: `class-api-documentation.php`
   - Patrón: Singleton completo

2. ✅ **Flavor_App_Config_API**
   - Archivo: `class-app-config-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** NO cargada en bootstrap ❌

3. ✅ **Flavor_Federation_API**
   - Archivo: `class-federation-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** Cargada pero NO inicializada ⚠️

4. ✅ **Flavor_Media_API**
   - Archivo: `class-media-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** NO cargada en bootstrap ❌

5. ✅ **Flavor_Mobile_API_Extensions**
   - Archivo: `class-mobile-api-extensions.php`
   - Patrón: Singleton completo
   - **Estado actual:** Cargada pero NO inicializada ⚠️

6. ✅ **Chat_IA_Mobile_API**
   - Archivo: `class-mobile-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** Cargada pero NO inicializada ⚠️

7. ✅ **Flavor_Module_Actions_API**
   - Archivo: `class-module-actions-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** Cargada pero NO inicializada ⚠️

8. ✅ **Flavor_Module_Gap_Status_API**
   - Archivo: `class-module-gap-status-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** Cargada pero NO inicializada ⚠️

9. ✅ **Flavor_Module_Manager_API**
   - Archivo: `class-module-manager-api.php`
   - Patrón: Singleton completo
   - **Estado actual:** NO cargada en bootstrap ❌

10. ✅ **Flavor_Native_Content_API**
    - Archivo: `class-native-content-api.php`
    - Patrón: Singleton completo
    - **Estado actual:** Cargada pero NO inicializada ⚠️

11. ✅ **Flavor_Reputation_API**
    - Archivo: `class-reputation-api.php`
    - Patrón: Singleton completo
    - **Estado actual:** Cargada en otro lugar (reputation system) ✅

12. ✅ **Flavor_SEO_API**
    - Archivo: `class-seo-api.php`
    - Patrón: Singleton completo
    - **Estado actual:** NO cargada en bootstrap ❌

13. ✅ **Flavor_Site_Builder_API**
    - Archivo: `class-site-builder-api.php`
    - Patrón: Singleton completo
    - **Estado actual:** NO cargada en bootstrap ❌

14. ✅ **Flavor_VBP_Diagnostics**
    - Archivo: `class-vbp-diagnostics.php`
    - Patrón: Singleton completo
    - **Estado actual:** NO cargada en bootstrap ❌

15. ✅ **Flavor_VBP_Preview_API**
    - Archivo: `class-vbp-preview-api.php`
    - Patrón: Singleton completo
    - **Estado actual:** NO cargada en bootstrap ❌

---

## APIs con Singleton Parcial (3)

Estas APIs tienen `get_instance()` pero necesitan verificación del constructor.

### 1. ⚠️ Flavor_Client_Dashboard_API

- **Archivo:** `class-client-dashboard-api.php`
- **Estado actual:** Cargada y SÍ inicializada ✅
- **Verificar:** Constructor público o privado

### 2. ⚠️ Flavor_Site_Config_API

- **Archivo:** `class-site-config-api.php`
- **Estado actual:** NO cargada en bootstrap ❌
- **Verificar:** Constructor público o privado

### 3. ⚠️ Flavor_VBP_Claude_API

- **Archivo:** `class-vbp-claude-api.php`
- **Estado actual:** NO cargada en bootstrap ❌
- **Verificar:** Constructor público o privado
- **Nota:** Variable de instancia se llama `$instancia` (español)

---

## APIs SIN Singleton (2)

Estas APIs usan constructor público y deben instanciarse con `new`.

### 1. ❌ Flavor_Module_Config_API

- **Archivo:** `class-module-config-api.php`
- **Patrón:** Constructor público
- **Inicialización:** `new Flavor_Module_Config_API();`
- **Estado actual:** Cargada pero NO inicializada ⚠️

### 2. ❌ Flavor_E2E_REST_API

- **Archivo:** `class-e2e-rest-api.php`
- **Patrón:** Constructor público
- **Inicialización:** `new Flavor_E2E_REST_API();`
- **Estado actual:** Cargada pero NO inicializada ⚠️
- **Nota:** Probablemente solo para testing, no prioritaria

---

## Matriz Completa

| API | Archivo | Singleton | Cargada | Inicializada | Acción Requerida |
|-----|---------|-----------|---------|--------------|------------------|
| API_Documentation | class-api-documentation.php | ✅ | ⚠️ | ❌ | Cargar + Inicializar |
| App_Config_API | class-app-config-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| Client_Dashboard_API | class-client-dashboard-api.php | ⚠️ | ✅ | ✅ | Verificar patrón |
| E2E_REST_API | class-e2e-rest-api.php | ❌ | ⚠️ | ❌ | Instanciar con new |
| Federation_API | class-federation-api.php | ✅ | ✅ | ❌ | Inicializar |
| Media_API | class-media-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| Mobile_API | class-mobile-api.php | ✅ | ✅ | ❌ | Inicializar |
| Mobile_API_Extensions | class-mobile-api-extensions.php | ✅ | ✅ | ❌ | Inicializar |
| Module_Actions_API | class-module-actions-api.php | ✅ | ✅ | ❌ | Inicializar |
| Module_Config_API | class-module-config-api.php | ❌ | ✅ | ❌ | Instanciar con new |
| Module_Gap_Status_API | class-module-gap-status-api.php | ✅ | ✅ | ❌ | Inicializar |
| Module_Manager_API | class-module-manager-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| Native_Content_API | class-native-content-api.php | ✅ | ✅ | ❌ | Inicializar |
| Reputation_API | class-reputation-api.php | ✅ | ✅ | ✅ | OK |
| SEO_API | class-seo-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| Site_Builder_API | class-site-builder-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| Site_Config_API | class-site-config-api.php | ⚠️ | ❌ | ❌ | **Cargar + Verificar** |
| VBP_Claude_API | class-vbp-claude-api.php | ⚠️ | ❌ | ❌ | **Cargar + Verificar** |
| VBP_Diagnostics | class-vbp-diagnostics.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |
| VBP_Preview_API | class-vbp-preview-api.php | ✅ | ❌ | ❌ | **Cargar + Inicializar** |

---

## Prioridad de Acción

### P0 - Críticas para Claude Code (URGENTE)

Estas 4 APIs son esenciales para la automatización documentada en CLAUDE.md:

1. **Flavor_VBP_Claude_API** ⚠️
   - Cargar en bootstrap
   - Verificar constructor
   - Inicializar (probablemente `::get_instance()`)

2. **Flavor_Site_Builder_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

3. **Flavor_VBP_Diagnostics** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

4. **Flavor_VBP_Preview_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

### P1 - Importantes para Funcionalidad General

5. **Flavor_Site_Config_API** ⚠️
   - Cargar en bootstrap
   - Verificar constructor
   - Inicializar

6. **Flavor_Media_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

7. **Flavor_Module_Manager_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

8. **Flavor_App_Config_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

9. **Flavor_SEO_API** ✅
   - Cargar en bootstrap
   - Inicializar con `::get_instance()`

### P2 - Ya Cargadas, Solo Inicializar

10. **Flavor_Federation_API** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

11. **Chat_IA_Mobile_API** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

12. **Flavor_Mobile_API_Extensions** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

13. **Flavor_Module_Actions_API** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

14. **Flavor_Module_Gap_Status_API** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

15. **Flavor_Native_Content_API** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

16. **Flavor_API_Documentation** ✅
    - Ya cargada
    - Solo inicializar con `::get_instance()`

### P3 - APIs sin Singleton

17. **Flavor_Module_Config_API** ❌
    - Ya cargada
    - Instanciar con `new Flavor_Module_Config_API();`

18. **Flavor_E2E_REST_API** ❌
    - Ya cargada
    - Instanciar con `new Flavor_E2E_REST_API();`
    - Nota: Solo testing, baja prioridad

---

## Plan de Implementación

### Fase 1: Verificar APIs con ⚠️

```bash
# Verificar constructores
grep -A 3 "function __construct" \
  includes/api/class-client-dashboard-api.php \
  includes/api/class-site-config-api.php \
  includes/api/class-vbp-claude-api.php
```

### Fase 2: Añadir al Bootstrap (método load_api_system)

Añadir después de la línea 265 (después de `class-module-actions-api.php`):

```php
// API REST para integración con Claude Code / VBP
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-claude-api.php';
Flavor_VBP_Claude_API::get_instance();

// API de Diagnóstico VBP (para verificar estado del sistema)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-diagnostics.php';
Flavor_VBP_Diagnostics::get_instance();

// API de Preview VBP (endpoints públicos para previsualizar landings)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-preview-api.php';
Flavor_VBP_Preview_API::get_instance();

// Site Builder API para creación completa de sitios
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-builder-api.php';
Flavor_Site_Builder_API::get_instance();

// API de Configuración de Sitio (layouts, menús, settings)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-config-api.php';
Flavor_Site_Config_API::get_instance(); // Verificar antes

// API de Media (imágenes, iconos, placeholders, fuentes, gradientes)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-media-api.php';
Flavor_Media_API::get_instance();

// API de Gestión de Módulos (activar/desactivar, configurar, demo data)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-manager-api.php';
Flavor_Module_Manager_API::get_instance();

// API de Configuración de Apps/APKs (branding, temas, permisos, build)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-app-config-api.php';
Flavor_App_Config_API::get_instance();

// API de SEO (meta tags, Open Graph, Twitter Cards, Schema.org, sitemap)
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-seo-api.php';
Flavor_SEO_API::get_instance();
```

### Fase 3: Inicializar APIs Ya Cargadas

Reemplazar líneas existentes:

```php
// ANTES (línea 242):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-federation-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-federation-api.php';
Flavor_Federation_API::get_instance();

// ANTES (línea 245):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-gap-status-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-gap-status-api.php';
Flavor_Module_Gap_Status_API::get_instance();

// ANTES (línea 248):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api.php';
Chat_IA_Mobile_API::get_instance();

// ANTES (línea 249):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api-extensions.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api-extensions.php';
Flavor_Mobile_API_Extensions::get_instance();

// ANTES (línea 252):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-native-content-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-native-content-api.php';
Flavor_Native_Content_API::get_instance();

// ANTES (línea 259):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-e2e-rest-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-e2e-rest-api.php';
new Flavor_E2E_REST_API(); // NO usa singleton

// ANTES (línea 262):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-api-documentation.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-api-documentation.php';
Flavor_API_Documentation::get_instance();

// ANTES (línea 265):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-actions-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-actions-api.php';
Flavor_Module_Actions_API::get_instance();

// ANTES (línea 239):
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-config-api.php';

// DESPUÉS:
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-config-api.php';
new Flavor_Module_Config_API(); // NO usa singleton
```

---

## Verificación Post-Implementación

```bash
# 1. Sintaxis PHP
php -l includes/bootstrap/class-bootstrap-dependencies.php

# 2. WordPress carga
wp eval 'echo "OK\n";'

# 3. Listar endpoints
wp rest-api list --format=json | jq -r '.[] | select(.namespace | contains("flavor"))'

# 4. Probar endpoints críticos
curl -s "http://sitio.local/wp-json/flavor-site-builder/v1/system/health" \
  -H "X-VBP-Key: flavor-vbp-2024"

curl -s "http://sitio.local/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: flavor-vbp-2024"
```

---

## Conclusiones

- **15 APIs usan Singleton estándar** - Listas para `::get_instance()`
- **3 APIs necesitan verificación manual** del constructor
- **2 APIs necesitan `new ClassName()`** en lugar de singleton
- **9 APIs críticas NO están cargadas** en el bootstrap actual
- **7 APIs están cargadas pero NO inicializadas**

**Próximo paso:** Verificar las 3 APIs con ⚠️ y luego aplicar el fix completo.
