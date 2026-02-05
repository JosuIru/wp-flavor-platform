# Solución de Error Fatal - Flavor Chat IA

## 🔧 Diagnóstico del Problema

He realizado las siguientes correcciones al módulo Grupos de Consumo:

### Problemas Encontrados y Corregidos

1. **✅ Método `get_setting()` faltante** en la clase base
   - Añadido `get_setting()` y `update_setting()` a `Flavor_Chat_Module_Base`

2. **✅ Auto-inicialización de API**
   - Eliminada la línea `Flavor_Grupos_Consumo_API::get_instance();` del final del archivo API
   - Ahora se inicializa correctamente en el hook `rest_api_init`

3. **✅ Método `get_activation_error()` faltante**
   - Añadido al módulo de Grupos de Consumo

---

## 🧪 Cómo Probar si el Problema está Resuelto

### Opción 1: Test de Sintaxis PHP (Línea de Comandos)

Ejecuta este comando en tu terminal:

```bash
cd "/home/josu/Local Sites/basaberenueva/app/public/wp-content/plugins/flavor-chat-ia"
php test-module.php
```

**Si funciona**, verás:
```
=== TODAS LAS PRUEBAS PASARON ✓ ===
```

**Si falla**, verás el error específico de PHP.

### Opción 2: Habilitar Debug de WordPress

1. Edita `wp-config.php` en la raíz de WordPress
2. Añade o modifica estas líneas:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

3. Intenta activar el plugin
4. Revisa el archivo de errores:
   ```
   /home/josu/Local Sites/basaberenueva/app/public/wp-content/debug.log
   ```

### Opción 3: Ver Error en Pantalla

1. Temporalmente, habilita la visualización de errores en `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
```

2. Intenta activar el plugin
3. El error se mostrará en la pantalla

---

## 🔍 Posibles Causas del Error Fatal

Si después de mis correcciones aún hay error, puede ser por:

### 1. Conflicto con Otros Plugins

**Síntoma**: Error al cargar una clase o función

**Solución**:
- Desactiva TODOS los otros plugins
- Intenta activar Flavor Chat IA
- Si funciona, reactiva los otros plugins uno por uno para encontrar el conflicto

### 2. Versión de PHP Incompatible

**Requisito**: PHP 7.4 o superior

**Verificar versión**:
```bash
php -v
```

**Si es inferior a 7.4**, actualiza PHP.

### 3. Memoria PHP Insuficiente

**Síntoma**: "Allowed memory size exhausted"

**Solución** en `wp-config.php`:
```php
define('WP_MEMORY_LIMIT', '256M');
```

### 4. WordPress Desactualizado

**Requisito**: WordPress 5.8 o superior

**Verificar**: Panel de WordPress > Escritorio > Actualizaciones

### 5. Base de Datos con Problemas

**Síntoma**: Error al crear tablas

**Solución**:
```sql
-- Verificar si la tabla ya existe con nombre diferente
SHOW TABLES LIKE '%flavor%';

-- Si existe wp_flavor_gc_pedidos antigua, elimínala:
DROP TABLE IF EXISTS wp_flavor_gc_pedidos;
```

---

## 🚑 Solución Rápida: Desactivar Módulo Grupos de Consumo

Si el error persiste y es urgente activar el plugin, puedes desactivar temporalmente el módulo:

### Método 1: Renombrar el Módulo

```bash
cd "/home/josu/Local Sites/basaberenueva/app/public/wp-content/plugins/flavor-chat-ia/includes/modules"
mv grupos-consumo grupos-consumo.DISABLED
```

### Método 2: Modificar el Loader

Edita `includes/modules/class-module-loader.php` y comenta la línea del módulo:

```php
$builtin_modules = [
    'woocommerce' => [...],
    'banco_tiempo' => [...],
    'marketplace' => [...],
    // 'grupos_consumo' => [
    //     'file' => $modules_path . 'grupos-consumo/class-grupos-consumo-module.php',
    //     'class' => 'Flavor_Chat_Grupos_Consumo_Module',
    // ],
];
```

---

## 🐛 Errores Comunes y Sus Soluciones

### Error: "Class 'Flavor_Chat_Module_Base' not found"

**Causa**: La interfaz no se cargó antes del módulo

**Solución**: Verificar que en `flavor-chat-ia.php` la línea siguiente existe:
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/interface-chat-module.php';
```

**Debe estar ANTES de**:
```php
require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-module-loader.php';
```

### Error: "Call to undefined method get_setting()"

**Causa**: El método no existía en la clase base

**Solución**: ✅ Ya corregido. Verifica que `interface-chat-module.php` tenga el método `get_setting()`.

### Error: "Cannot redeclare class Flavor_Grupos_Consumo_API"

**Causa**: La clase API se está cargando dos veces

**Solución**: ✅ Ya corregido. Verificar que al final de `class-grupos-consumo-api.php` NO exista:
```php
Flavor_Grupos_Consumo_API::get_instance(); // ← Esta línea debe estar ELIMINADA
```

### Error: "Call to undefined function WP_Query()"

**Causa**: WordPress no está completamente cargado cuando se ejecuta el código

**Solución**: Asegurarse de que el código se ejecuta dentro de un hook de WordPress, nunca directamente al cargar el archivo.

---

## 📞 Cómo Reportar el Error

Si ninguna solución funciona, proporciona esta información:

1. **Versión de PHP**: `php -v`
2. **Versión de WordPress**: Panel > Escritorio > Actualizaciones
3. **Contenido del debug.log**: Últimas 50 líneas
4. **Plugins activos**: Lista de todos los plugins
5. **Tema activo**: Nombre y versión
6. **Resultado del test**: Salida de `php test-module.php`

### Copiar Últimas Líneas del Log

```bash
tail -50 "/home/josu/Local Sites/basaberenueva/app/public/wp-content/debug.log"
```

### Listar Plugins Activos

En MySQL/phpMyAdmin:
```sql
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';
```

---

## ✅ Verificación Final

Después de aplicar las correcciones, sigue estos pasos:

1. **Desactiva el plugin** si está activo
2. **Limpia la caché** de WordPress y del servidor
3. **Verifica los permisos** de archivos (644 para archivos, 755 para directorios)
4. **Activa el plugin** desde el panel de WordPress
5. **Verifica** que no haya errores en la pantalla
6. **Revisa** el `debug.log` para confirmar que no hay warnings

---

## 🎯 Estado Actual de las Correcciones

| Archivo | Corrección | Estado |
|---------|-----------|--------|
| `interface-chat-module.php` | Añadido `get_setting()` y `update_setting()` | ✅ Corregido |
| `class-grupos-consumo-api.php` | Eliminada auto-inicialización | ✅ Corregido |
| `class-grupos-consumo-module.php` | Añadido `get_activation_error()` | ✅ Corregido |
| `class-grupos-consumo-module.php` | Inicialización API en hook correcto | ✅ Corregido |

---

## 📚 Recursos Adicionales

- [Documentación completa del módulo](./includes/modules/grupos-consumo/GUIA_COMPLETA.md)
- [Documentación técnica](./includes/modules/grupos-consumo/IMPLEMENTACION_COMPLETA.md)
- [API REST para móviles](./includes/modules/grupos-consumo/API_MOBILE.md)

---

**Fecha de correcciones**: 27 de enero de 2026
**Versión del plugin**: 1.5.0
