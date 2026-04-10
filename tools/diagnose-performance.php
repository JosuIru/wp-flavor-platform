<?php
/**
 * Herramienta de diagnóstico de rendimiento para Flavor Platform
 *
 * Ejecutar: wp eval-file tools/diagnose-performance.php
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    // Si se ejecuta desde CLI
    require_once dirname(__DIR__, 5) . '/wp-load.php';
}

echo "\n=== DIAGNÓSTICO DE RENDIMIENTO - FLAVOR PLATFORM ===\n\n";

// 1. Módulos activos
$active_modules = get_option('flavor_active_modules', []);
$main_settings = get_option('flavor_chat_ia_settings', []);
$active_from_settings = $main_settings['active_modules'] ?? [];
$all_active = array_unique(array_merge($active_modules, $active_from_settings));

echo "1. MÓDULOS ACTIVOS: " . count($all_active) . "\n";
if (count($all_active) > 15) {
    echo "   ⚠️  ADVERTENCIA: Más de 15 módulos activos puede afectar el rendimiento\n";
}

// 2. Transients de dashboard
global $wpdb;
$dashboard_transients = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_flavor_dash_%'"
);
echo "\n2. TRANSIENTS DE DASHBOARD: {$dashboard_transients}\n";
if ($dashboard_transients == 0) {
    echo "   ⚠️  No hay cachés de dashboard. Primera carga será lenta.\n";
    echo "   💡 Solución: Los dashboards se cachearán automáticamente tras visitarlos.\n";
}

// 3. Autoload de opciones
$autoload_options = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->options}
     WHERE autoload = 'yes' AND option_name LIKE 'flavor_%'"
);
$autoload_size = $wpdb->get_var(
    "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options}
     WHERE autoload = 'yes' AND option_name LIKE 'flavor_%'"
);
echo "\n3. OPCIONES AUTOLOAD FLAVOR: {$autoload_options} (" . number_format($autoload_size / 1024, 2) . " KB)\n";
if ($autoload_size > 100000) {
    echo "   ⚠️  Muchos datos en autoload. Considerar desactivar autoload en opciones grandes.\n";
}

// 4. Object cache
$object_cache_enabled = wp_using_ext_object_cache();
echo "\n4. OBJECT CACHE: " . ($object_cache_enabled ? '✅ Activo' : '❌ No activo') . "\n";
if (!$object_cache_enabled) {
    echo "   💡 Recomendación: Instalar Redis o Memcached para mejor rendimiento.\n";
}

// 5. Tablas de módulos
$tables_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name LIKE '{$wpdb->prefix}flavor_%'"
);
echo "\n5. TABLAS DE MÓDULOS: {$tables_count}\n";

// 6. Índices faltantes (verificar tablas grandes)
$tables_without_index = [];
$flavor_tables = $wpdb->get_col(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name LIKE '{$wpdb->prefix}flavor_%'"
);

foreach ($flavor_tables as $table) {
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}");
    if (count($indexes) <= 1) { // Solo tiene PRIMARY
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($row_count > 100) {
            $tables_without_index[] = "{$table} ({$row_count} filas)";
        }
    }
}

if (!empty($tables_without_index)) {
    echo "\n6. TABLAS SIN ÍNDICES ADICIONALES (>100 filas):\n";
    foreach ($tables_without_index as $t) {
        echo "   - {$t}\n";
    }
    echo "   💡 Considerar añadir índices en columnas usadas en WHERE/ORDER BY.\n";
} else {
    echo "\n6. ÍNDICES: ✅ Tablas principales tienen índices\n";
}

// 7. Queries lentas recientes (si hay log)
echo "\n7. RECOMENDACIONES GENERALES:\n";
echo "   - Activar WP_DEBUG=false en producción (reduce logging)\n";
echo "   - Usar un plugin de caché de página (WP Super Cache, W3 Total Cache)\n";
echo "   - Activar OPcache en PHP\n";
echo "   - Considerar CDN para assets estáticos\n";

// 8. Estado de caché de metadatos de módulos
$metadata_cache = get_transient('flavor_modules_metadata_cache');
echo "\n8. CACHÉ DE METADATOS: " . ($metadata_cache ? '✅ Activa' : '❌ No existe') . "\n";
if (!$metadata_cache) {
    echo "   💡 Se regenerará en la próxima carga.\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n\n";

// Limpiar cachés si se pide
if (isset($argv[1]) && $argv[1] === '--clean') {
    echo "Limpiando cachés de dashboard...\n";
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_flavor_dash_%'
            OR option_name LIKE '_transient_timeout_flavor_dash_%'"
    );
    echo "Eliminados: " . ($deleted / 2) . " transients\n";
}
