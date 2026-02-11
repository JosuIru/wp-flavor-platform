<?php
/**
 * Script temporal para limpiar caché de menús admin
 * Ejecutar vía: wp eval-file limpiar-cache-admin.php
 */

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache limpiado\n";
}

// Limpiar transients relacionados con menú
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%' AND option_name LIKE '%menu%'");
echo "✓ Transients de menú eliminados\n";

// Limpiar opciones de capabilities cacheadas
delete_option('fresh_site');
wp_cache_flush();
echo "✓ Caché de WordPress limpiado\n";

// Limpiar user meta cache
$current_user_id = get_current_user_id();
if ($current_user_id) {
    clean_user_cache($current_user_id);
    echo "✓ Caché de usuario limpiado\n";
}

echo "\n=== Caché limpiado correctamente ===\n";
echo "Por favor, recarga la página en el navegador (Ctrl+Shift+R para forzar recarga)\n";
