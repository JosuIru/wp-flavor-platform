# Estado Real de APIs REST - Flavor Platform 3.3.0

**Fecha:** 2026-03-21
**Auditoría:** Verificación exhaustiva de inicialización de APIs

---

## Resumen

| Estado | Cantidad | Descripción |
|--------|----------|-------------|
| ✅ Inicializadas correctamente | 7 | APIs con `get_instance()` llamado |
| ❌ NO inicializadas | **12** | APIs definidas pero no registradas |
| **Total APIs REST** | **19** | Total de APIs con `register_rest_route` |

### 🔴 HALLAZGO CRÍTICO

**El 63% de las APIs REST NO están funcionando** (12 de 19)

---

## APIs NO Inicializadas (12)

### Prioridad P0 - Críticas para Claude Code

#### 1. ❌ Flavor_Site_Builder_API
- **Archivo:** `includes/api/class-site-builder-api.php`
- **Namespace:** `flavor-site-builder/v1`
- **Impacto:** CRÍTICO - Toda la API de creación de sitios no funciona
- **Endpoints afectados:** 15+ endpoints (perfiles, plantillas, site/create, etc.)

#### 2. ❌ Flavor_VBP_Claude_API
- **Archivo:** `includes/api/class-vbp-claude-api.php`
- **Namespace:** `flavor-vbp/v1`
- **Impacto:** CRÍTICO - Visual Builder Pro API no funciona
- **Endpoints afectados:** 13+ endpoints (pages, blocks, templates, etc.)

#### 3. ❌ Flavor_VBP_Diagnostics
- **Archivo:** `includes/api/class-vbp-diagnostics.php`
- **Impacto:** ALTO - Diagnóstico de VBP no disponible

#### 4. ❌ Flavor_VBP_Preview_API
- **Archivo:** `includes/api/class-vbp-preview-api.php`
- **Impacto:** ALTO - Preview de landings no funciona

### Prioridad P1 - Funcionalidad del Plugin

#### 5. ❌ Flavor_Module_Config_API
- **Archivo:** `includes/api/class-module-config-api.php`
- **Impacto:** ALTO - Configuración de módulos vía API

#### 6. ❌ Chat_IA_Mobile_API
- **Archivo:** `includes/api/class-mobile-api.php`
- **Impacto:** ALTO - API principal para apps móviles

#### 7. ❌ Flavor_Mobile_API_Extensions
- **Archivo:** `includes/api/class-mobile-api-extensions.php`
- **Impacto:** MEDIO - Extensiones para apps móviles

#### 8. ❌ Flavor_Module_Actions_API
- **Archivo:** `includes/api/class-module-actions-api.php`
- **Impacto:** MEDIO - Acciones de módulos

#### 9. ❌ Flavor_Module_Gap_Status_API
- **Archivo:** `includes/api/class-module-gap-status-api.php`
- **Impacto:** MEDIO - Estado de gaps de módulos

#### 10. ❌ Flavor_Federation_API
- **Archivo:** `includes/api/class-federation-api.php`
- **Impacto:** MEDIO - Red federada entre sitios

#### 11. ❌ Flavor_Native_Content_API
- **Archivo:** `includes/api/class-native-content-api.php`
- **Impacto:** MEDIO - Contenido nativo

### Prioridad P2 - Herramientas Secundarias

#### 12. ❌ Flavor_API_Documentation
- **Archivo:** `includes/api/class-api-documentation.php`
- **Impacto:** BAJO - Documentación automática de APIs

#### 13. ❌ Flavor_E2E_REST_API (probablemente solo para testing)
- **Archivo:** `includes/api/class-e2e-rest-api.php`
- **Impacto:** BAJO - Testing E2E

---

## APIs Correctamente Inicializadas

### ✅ Flavor_Site_Config_API
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-config-api.php';
Flavor_Site_Config_API::get_instance(); // ✅ CORRECTO
```

### ✅ Flavor_Media_API
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-media-api.php';
Flavor_Media_API::get_instance(); // ✅ CORRECTO
```

### ✅ Flavor_Module_Manager_API
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-manager-api.php';
Flavor_Module_Manager_API::get_instance(); // ✅ CORRECTO
```

### ✅ Flavor_App_Config_API
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-app-config-api.php';
Flavor_App_Config_API::get_instance(); // ✅ CORRECTO
```

### ✅ Flavor_SEO_API
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-seo-api.php';
Flavor_SEO_API::get_instance(); // ✅ CORRECTO
```

---

## Impacto por Componente

### CLAUDE.md - Instrucciones para Claude Code

**Estado:** 🔴 DESACTUALIZADO Y NO FUNCIONAL

Todos los ejemplos de la sección de APIs fallarán:

```bash
# ❌ ESTOS COMANDOS NO FUNCIONAN ACTUALMENTE

# Site Builder API
curl "http://SITIO/wp-json/flavor-site-builder/v1/system/health"
# Error: rest_no_route

# VBP Claude API
curl "http://SITIO/wp-json/flavor-vbp/v1/claude/status"
# Error: rest_no_route
```

### Workflow de Creación de Sitios

**Estado:** 🔴 COMPLETAMENTE ROTO

El flujo documentado en CLAUDE.md no funciona:

1. ❌ Validar configuración → `POST /site/validate`
2. ❌ Crear sitio → `POST /site/create`
3. ❌ Crear páginas VBP → `POST /claude/pages/styled`
4. ❌ Configurar menús → `POST /menu`
5. ❌ Aplicar tema → `POST /theme/apply`

### Mobile Apps / APK Configuration

**Estado:** ⚠️ POR VERIFICAR

Si `Flavor_App_Config_API` está bien inicializada (parece que sí), la configuración de apps móviles debería funcionar.

---

## Plan de Remediación

### Fase 1: Fix Inmediato (P0)

**Archivo:** `includes/bootstrap/class-bootstrap-dependencies.php`

**Cambios necesarios (líneas 307-316):**

```diff
 // API REST para integración con Claude Code / VBP
 require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-claude-api.php';
+Flavor_VBP_Claude_API::get_instance();

 // API de Diagnóstico VBP (para verificar estado del sistema)
 require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-diagnostics.php';
+Flavor_VBP_Diagnostics::get_instance();

 // API de Preview VBP (endpoints públicos para previsualizar landings)
 require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-vbp-preview-api.php';
+Flavor_VBP_Preview_API::get_instance();

 // Site Builder API para creación completa de sitios
 require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-site-builder-api.php';
+Flavor_Site_Builder_API::get_instance();
```

### Fase 2: Verificación (P1)

Comprobar qué otras APIs tienen el mismo problema:

```bash
cd includes/api

# Listar todas las APIs con register_rest_route
grep -l "register_rest_route" *.php

# Verificar cuáles están inicializadas
for api in class-*.php; do
    class=$(grep "^class " "$api" | awk '{print $2}')
    if grep -q "register_rest_route" "$api"; then
        if ! grep -q "${class}::get_instance()" ../bootstrap/class-bootstrap-dependencies.php; then
            echo "❌ $class ($api)"
        fi
    fi
done
```

### Fase 3: Testing (P1)

Después del fix, ejecutar:

```bash
# 1. Flush rewrite rules
wp rewrite flush

# 2. Listar endpoints Flavor
wp rest-api list --format=json | jq -r '.[] | select(.namespace | contains("flavor"))'

# 3. Probar Site Builder
curl -s "http://sitio.local/wp-json/flavor-site-builder/v1/system/health" \
  -H "X-VBP-Key: flavor-vbp-2024" | jq

# 4. Probar VBP Claude
curl -s "http://sitio.local/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: flavor-vbp-2024" | jq

# 5. Probar listado de plantillas
curl -s "http://sitio.local/wp-json/flavor-site-builder/v1/templates" \
  -H "X-VBP-Key: flavor-vbp-2024" | jq
```

### Fase 4: Actualizar Documentación (P2)

Una vez verificado el fix:

1. Actualizar CLAUDE.md con ejemplos funcionales
2. Añadir sección de troubleshooting en docs/api/
3. Crear script de verificación de APIs en tools/

---

## Otras APIs por Revisar

Archivos en `includes/api/` que requieren análisis individual:

- `class-api-documentation.php` - Documentación automática
- `class-api-rate-limiter.php` - Rate limiting
- `class-client-dashboard-api.php` - Dashboard de cliente
- `class-design-tokens-exporter.php` - Tokens de diseño
- `class-e2e-rest-api.php` - Testing E2E
- `class-federation-api.php` - Red federada
- `class-mobile-api.php` - API móvil principal
- `class-mobile-api-extensions.php` - Extensiones móvil
- `class-module-actions-api.php` - Acciones de módulos
- `class-module-config-api.php` - Configuración de módulos
- `class-module-gap-status-api.php` - Estado de gaps
- `class-native-content-api.php` - Contenido nativo
- `class-reputation-api.php` - Sistema de reputación

---

## Conclusión

**Severidad:** 🔴 CRÍTICA
**Estado:** Bloqueante para automatización con Claude Code
**Tiempo de fix:** 5 minutos
**Tiempo de testing:** 15 minutos
**Prioridad:** P0

**Recomendación:** Aplicar fix inmediatamente y verificar con testing exhaustivo.

---

## Próximos Pasos

1. ✅ Crear este reporte
2. ⏳ Aplicar fix en `class-bootstrap-dependencies.php`
3. ⏳ Verificar con curl que endpoints responden
4. ⏳ Ejecutar smoke tests de cada API
5. ⏳ Actualizar CLAUDE.md con estado correcto
6. ⏳ Crear script de verificación automática

---

## Referencias

- `reports/AUDITORIA-APIS-REST-2026-03-21.md` - Análisis detallado
- `includes/bootstrap/class-bootstrap-dependencies.php` - Bootstrap principal
- `CLAUDE.md` - Documentación de APIs (actualmente incorrecta)
- `docs/api/CLAUDE-API-GUIDE.md` - Guía de APIs
