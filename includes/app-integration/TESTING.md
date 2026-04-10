# 🧪 Guía de Testing - Integración APKs

## Verificación Rápida

### 1. Verificar que el Plugin se Carga Correctamente

**Comprobar en WordPress Admin**:
1. Ve a **Plugins** → Verifica que "Flavor Chat IA" esté activo
2. No debe haber errores fatales
3. Verifica en los logs de PHP (si WP_DEBUG está activo)

**Desde terminal**:
```bash
# Ver últimos errores en los logs
tail -f /ruta/a/tu/wordpress/wp-content/debug.log
```

### 2. Verificar Endpoints de Descubrimiento

#### Test 1: Información del Sistema
```bash
curl -X GET "http://basaberenueva.local/wp-json/app-discovery/v1/info" | jq
```

**Resultado esperado**: JSON con información del sitio, sistemas activos, tema, etc.

**Verificaciones**:
- ✅ Status code: 200
- ✅ Campo `active_systems` presente
- ✅ Campo `wordpress_url` con URL del sitio
- ✅ Campo `theme` con colores

**Ejemplo de respuesta exitosa**:
```json
{
  "wordpress_url": "http://basaberenueva.local",
  "site_name": "Basabere Nueva",
  "site_description": "...",
  "active_systems": [
    {
      "id": "flavor-chat-ia",
      "name": "Flavor Chat IA",
      "active": true,
      "version": "1.5.0",
      "api_namespace": "flavor-platform/v1",
      "profile": "personalizado",
      "modules": ["grupos_consumo"],
      "features": ["chat", "pedidos_colectivos", "productores", "repartos"],
      "endpoints": {
        "discovery": "/wp-json/app-discovery/v1/info",
        "modules": "/wp-json/app-discovery/v1/modules",
        "theme": "/wp-json/app-discovery/v1/theme",
        "grupos_consumo": {
          "pedidos": "/wp-json/flavor-platform/v1/pedidos",
          "mis_pedidos": "/wp-json/flavor-platform/v1/mis-pedidos"
        }
      }
    }
  ],
  "unified_api": true,
  "api_version": "1.0",
  "theme": {
    "primary_color": "#4CAF50",
    "secondary_color": "#8BC34A",
    "accent_color": "#FF9800",
    "logo_url": "",
    "favicon_url": "",
    "background_color": "#FFFFFF",
    "text_color": "#000000"
  },
  "timezone": "Europe/Madrid",
  "language": "es_ES"
}
```

#### Test 2: Módulos Disponibles
```bash
curl -X GET "http://basaberenueva.local/wp-json/app-discovery/v1/modules" | jq
```

**Resultado esperado**: Lista de módulos con configuración

**Verificaciones**:
- ✅ Status code: 200
- ✅ Campo `modules` es array
- ✅ Cada módulo tiene `id`, `name`, `icon`, `color`
- ✅ Campo `total` coincide con número de módulos

**Ejemplo de respuesta exitosa**:
```json
{
  "success": true,
  "modules": [
    {
      "id": "grupos_consumo",
      "name": "Grupos de Consumo",
      "description": "Sistema de pedidos colectivos y grupos de consumo",
      "system": "flavor-chat-ia",
      "api_namespace": "flavor-platform/v1",
      "icon": "shopping_basket",
      "color": "#46b450",
      "show_in_navigation": true,
      "config": {
        "allow_orders_from_app": true,
        "show_progress_bar": true,
        "enable_notifications": true,
        "cache_duration": 3600
      }
    }
  ],
  "total": 1
}
```

#### Test 3: Configuración de Tema
```bash
curl -X GET "http://basaberenueva.local/wp-json/app-discovery/v1/theme" | jq
```

**Resultado esperado**: Colores y assets del tema

**Verificaciones**:
- ✅ Status code: 200
- ✅ Campos `primary_color`, `secondary_color` presentes
- ✅ Colores en formato hexadecimal (#RRGGBB)

### 3. Verificar Endpoints Unificados

#### Test 4: Chat Unificado
```bash
curl -X POST "http://basaberenueva.local/wp-json/unified-api/v1/chat" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hola, ¿qué pedidos hay disponibles?",
    "session_id": "test-session-123"
  }' | jq
```

**Resultado esperado**: Respuesta del chat con sugerencias y acciones

**Verificaciones**:
- ✅ Status code: 200
- ✅ Campo `success` es true
- ✅ Campo `response` tiene texto
- ✅ Campo `system` indica qué sistema procesó (flavor-chat-ia o calendario-experiencias)

#### Test 5: Información del Sitio Unificada
```bash
curl -X GET "http://basaberenueva.local/wp-json/unified-api/v1/site-info" | jq
```

**Resultado esperado**: Info consolidada del sitio

**Verificaciones**:
- ✅ Status code: 200
- ✅ Campo `site_name` presente
- ✅ Campo `systems_active` lista los sistemas activos

## Tests Desde Navegador

### Test Visual 1: Info del Sistema
Abre en tu navegador:
```
http://basaberenueva.local/wp-json/app-discovery/v1/info
```

Deberías ver un JSON formateado con toda la información del sistema.

### Test Visual 2: Módulos
```
http://basaberenueva.local/wp-json/app-discovery/v1/modules
```

### Test Visual 3: Tema
```
http://basaberenueva.local/wp-json/app-discovery/v1/theme
```

### Test Visual 4: Sitio Unificado
```
http://basaberenueva.local/wp-json/unified-api/v1/site-info
```

## Verificación de Integración

### Escenario 1: Solo Flavor Chat IA Activo

**Setup**:
- Flavor Chat IA: ✅ Activo
- wp-calendario-experiencias: ❌ Desactivado

**Comando**:
```bash
curl "http://basaberenueva.local/wp-json/app-discovery/v1/info" | jq '.active_systems[].id'
```

**Resultado esperado**:
```
"flavor-chat-ia"
```

### Escenario 2: Ambos Plugins Activos

**Setup**:
- Flavor Chat IA: ✅ Activo
- wp-calendario-experiencias: ✅ Activo
- Chat IA Addon de calendario: ❌ DESACTIVADO (importante para evitar conflictos)

**Comando**:
```bash
curl "http://basaberenueva.local/wp-json/app-discovery/v1/info" | jq '.active_systems[].id'
```

**Resultado esperado**:
```
"calendario-experiencias"
"flavor-chat-ia"
```

### Escenario 3: Detección de Módulos Activos

**Setup**:
- Activa el módulo "Grupos de Consumo" en Flavor Chat IA

**Comando**:
```bash
curl "http://basaberenueva.local/wp-json/app-discovery/v1/modules" | jq '.modules[] | {id, name, system}'
```

**Resultado esperado**:
```json
{
  "id": "grupos_consumo",
  "name": "Grupos de Consumo",
  "system": "flavor-chat-ia"
}
```

## Debugging

### Error: 404 Not Found

**Problema**: El endpoint no existe o no se registró

**Solución**:
```bash
# 1. Recargar permalinks desde WP Admin
# Ve a: Ajustes → Enlaces permanentes → Guardar cambios

# 2. O desde WP-CLI
wp rewrite flush

# 3. Verifica que la clase se carga
wp eval "echo class_exists('Flavor_App_Integration') ? 'OK' : 'FAIL';"
```

### Error: 500 Internal Server Error

**Problema**: Error en el código PHP

**Solución**:
```bash
# Ver logs de error
tail -100 /ruta/a/debug.log | grep "Flavor"

# Activar debug en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Error: Empty Response o {}

**Problema**: El plugin no se está cargando correctamente

**Verificación**:
```bash
# Verificar que las clases existen
wp eval "
  echo 'Flavor_App_Integration: ' . (class_exists('Flavor_App_Integration') ? 'OK' : 'FAIL') . PHP_EOL;
  echo 'Flavor_Plugin_Detector: ' . (class_exists('Flavor_Plugin_Detector') ? 'OK' : 'FAIL') . PHP_EOL;
  echo 'Flavor_API_Adapter: ' . (class_exists('Flavor_API_Adapter') ? 'OK' : 'FAIL') . PHP_EOL;
"
```

### Verificar Prioridad de Hooks

```bash
# Ver qué hooks están registrados
wp eval "
  global \$wp_filter;
  if (isset(\$wp_filter['rest_api_init'])) {
    foreach (\$wp_filter['rest_api_init']->callbacks as \$priority => \$callbacks) {
      foreach (\$callbacks as \$callback) {
        if (is_array(\$callback['function'])) {
          \$class = is_object(\$callback['function'][0]) ? get_class(\$callback['function'][0]) : \$callback['function'][0];
          if (strpos(\$class, 'Flavor') !== false) {
            echo \"Priority \$priority: \$class\" . PHP_EOL;
          }
        }
      }
    }
  }
"
```

## Checklist de Verificación

Antes de considerar que la integración funciona correctamente:

- [ ] Plugin activa sin errores
- [ ] Endpoint `/app-discovery/v1/info` devuelve 200
- [ ] Endpoint `/app-discovery/v1/modules` devuelve módulos
- [ ] Endpoint `/app-discovery/v1/theme` devuelve tema
- [ ] Endpoint `/unified-api/v1/chat` acepta mensajes
- [ ] Endpoint `/unified-api/v1/site-info` devuelve info
- [ ] Detección de wp-calendario-experiencias funciona (si está activo)
- [ ] Detección de Flavor Chat IA funciona
- [ ] Módulos activos aparecen en la lista
- [ ] Colores del tema se detectan correctamente
- [ ] API namespace correcto para cada sistema
- [ ] Endpoints específicos de módulos aparecen
- [ ] No hay conflictos de clases con otros plugins
- [ ] Logs de PHP sin errores fatales

## Tests Automatizados (Futuro)

Para implementar en el futuro:

```php
// tests/test-app-integration.php
class Test_App_Integration extends WP_UnitTestCase {
    public function test_discovery_endpoint_returns_system_info() {
        $request = new WP_REST_Request('GET', '/app-discovery/v1/info');
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('active_systems', $response->get_data());
    }

    public function test_modules_endpoint_returns_active_modules() {
        $request = new WP_REST_Request('GET', '/app-discovery/v1/modules');
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['modules']);
    }
}
```

## Próximos Pasos

Una vez verificado que todo funciona:

1. ✅ Endpoints funcionando
2. ⏳ Integrar en Flutter app existente
3. ⏳ Crear módulos Flutter para nuevas funcionalidades
4. ⏳ Testing end-to-end con app móvil
5. ⏳ Documentación para desarrolladores Flutter

---

**¡Usa esta guía para verificar que la integración funciona correctamente!** 🧪
