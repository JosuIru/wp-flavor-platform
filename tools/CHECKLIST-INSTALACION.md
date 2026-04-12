# Checklist de Instalación - Flavor Platform

## Verificación Rápida (WP-CLI)

```bash
cd /ruta/wordpress

# 1. Plugin activo
wp plugin is-active flavor-platform && echo "✅ Plugin activo"

# 2. Verificación completa
wp eval "
echo '=== CHECKLIST ===' . PHP_EOL;
echo '1. Módulos: ' . count(get_option('flavor_active_modules', [])) . PHP_EOL;
echo '2. VBP: ' . (class_exists('Flavor_VBP_Editor') ? '✅' : '❌') . PHP_EOL;
echo '3. API: ' . (class_exists('Flavor_VBP_Claude_API') ? '✅' : '❌') . PHP_EOL;
echo '4. Tema: ' . wp_get_theme()->get('Name') . PHP_EOL;
global \\\$wpdb;
echo '5. Tablas: ' . count(\\\$wpdb->get_results(\\\"SHOW TABLES LIKE '%flavor%'\\\", ARRAY_N)) . PHP_EOL;
echo '6. Homepage: ' . (get_option('page_on_front') ?: 'No') . PHP_EOL;
echo '7. Menus: ' . count(wp_get_nav_menus()) . PHP_EOL;
"
```

## Criterios de Aceptación

| Componente | Mínimo | Ideal |
|------------|--------|-------|
| Módulos activos | 1+ | 5-15 |
| VBP Editor | ✅ | ✅ |
| API Claude | ✅ | ✅ |
| Tema | flavor-starter | flavor-starter |
| Tablas | 10+ | 50+ |
| Homepage | Configurada | Página VBP |
| Menús | 1+ | 3+ (primary, footer, mobile) |

## Verificación Manual (Navegador)

1. **Admin Dashboard**: `/wp-admin/admin.php?page=flavor-platform`
   - [ ] Carga sin errores
   - [ ] Muestra módulos activos

2. **VBP Editor**: `/wp-admin/admin.php?page=flavor-vbp`
   - [ ] Canvas carga correctamente
   - [ ] Bloques disponibles en sidebar
   - [ ] Guardar funciona

3. **API Health**: `/wp-json/flavor-site-builder/v1/system/health`
   - [ ] Responde `{"status":"ok"}`

4. **Frontend**: Visitar homepage
   - [ ] Renderiza sin errores PHP
   - [ ] Estilos cargados

## Verificación de APKs

```bash
cd mobile-apps/

# Compilar cliente
./build_app.sh client release

# Compilar admin
./build_app.sh admin release

# Verificar que son diferentes
ls -la build/app/outputs/flutter-apk/
```

## Si Algo Falla

1. Revisar `/wp-content/debug.log`
2. Ejecutar `bash tools/validate-site.sh "http://SITIO" "/ruta/wp"`
3. Verificar permisos de carpetas
4. Regenerar API key: `wp eval "echo flavor_regenerate_vbp_api_key();"`
