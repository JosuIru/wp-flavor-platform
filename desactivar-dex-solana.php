<?php
/**
 * Script para desactivar el módulo dex-solana
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/desactivar-dex-solana.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Desactivar módulo dex-solana</h1>';

$settings = get_option('flavor_chat_ia_settings', []);
$modulos_activos = $settings['active_modules'] ?? [];

echo '<h2>Módulos activos antes (' . count($modulos_activos) . '):</h2>';
echo '<ul>';
foreach ($modulos_activos as $modulo) {
    $destacado = $modulo === 'dex_solana' ? ' style="color: red; font-weight: bold;"' : '';
    echo "<li{$destacado}>{$modulo}</li>";
}
echo '</ul>';

// Eliminar dex_solana de la lista
$modulos_activos = array_filter($modulos_activos, function($modulo) {
    return $modulo !== 'dex_solana' && $modulo !== 'dex-solana';
});

// Actualizar configuración
$settings['active_modules'] = array_values($modulos_activos);
update_option('flavor_chat_ia_settings', $settings);

echo '<hr>';
echo '<h2 style="color: green;">✓ Módulo dex-solana desactivado</h2>';

echo '<h2>Módulos activos después (' . count($modulos_activos) . '):</h2>';
echo '<ul>';
foreach ($modulos_activos as $modulo) {
    echo "<li>{$modulo}</li>";
}
echo '</ul>';

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">← Volver al Compositor</a></p>';
