<?php
/**
 * Script para limpiar caché de WordPress
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/admin/limpiar-cache.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'flavor-chat-ia'));
}

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>✓ OPcache limpiado</p>";
}

// Limpiar transients
global $wpdb;
$deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
echo "<p>✓ Transients eliminados: {$deleted}</p>";

// Limpiar caché de WordPress
wp_cache_flush();
echo "<p>✓ Caché de WordPress limpiado</p>";

// Limpiar caché de usuario actual
$current_user_id = get_current_user_id();
if ($current_user_id) {
    clean_user_cache($current_user_id);
    wp_cache_delete($current_user_id, 'users');
    wp_cache_delete($current_user_id, 'user_meta');
    echo "<p>✓ Caché de usuario limpiado (ID: {$current_user_id})</p>";
}

// Forzar recarga de capabilities
$user = wp_get_current_user();
$user->get_role_caps();
echo "<p>✓ Capabilities recargadas</p>";

echo "<hr>";
echo "<h2>✓ Caché limpiado correctamente</h2>";
echo "<p><strong>Ahora puedes:</strong></p>";
echo "<ul>";
echo "<li><a href='" . admin_url('admin.php?page=flavor-gestion') . "'>Ir al Panel de Gestión</a></li>";
echo "<li><a href='" . admin_url('admin.php?page=gc-dashboard') . "'>Ir al Dashboard de Grupos de Consumo</a></li>";
echo "</ul>";
echo "<p><small>Tip: Presiona Ctrl+Shift+R en el navegador para forzar recarga sin caché</small></p>";
