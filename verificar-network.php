<?php
/**
 * Script de verificación y limpieza de Network
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/verificar-network.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Verificación del Sistema de Red de Comunidades</h1>';

// 1. Limpiar caché
wp_cache_flush();
if (function_exists('opcache_reset')) {
    opcache_reset();
}
echo '<p>✓ Caché limpiado</p>';

// 2. Verificar tablas
global $wpdb;
$prefix = $wpdb->prefix . 'flavor_network_';
$tablas_requeridas = [
    'nodes',
    'connections',
    'messages',
    'favorites',
    'recommendations',
    'board',
    'shared_content',
    'events',
    'collaborations',
];

echo '<h2>Verificación de Tablas:</h2>';
foreach ($tablas_requeridas as $tabla) {
    $tabla_completa = $prefix . $tabla;
    $existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_completa'") === $tabla_completa;

    if ($existe) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_completa");
        echo "<p>✓ <strong>{$tabla}</strong>: {$count} registros</p>";
    } else {
        echo "<p>✗ <strong>{$tabla}</strong>: NO EXISTE</p>";
    }
}

// 3. Verificar clases cargadas
echo '<h2>Clases Cargadas:</h2>';
$clases = [
    'Flavor_Network_Manager',
    'Flavor_Network_API',
    'Flavor_Network_Admin',
    'Flavor_Network_Installer',
];

foreach ($clases as $clase) {
    $existe = class_exists($clase);
    echo $existe ? "<p>✓ {$clase}</p>" : "<p>✗ {$clase} NO ENCONTRADA</p>";
}

// 4. Verificar endpoints REST
echo '<h2>Endpoints REST:</h2>';
$rest_server = rest_get_server();
$namespaces = $rest_server->get_namespaces();

if (in_array('flavor-network/v1', $namespaces)) {
    echo '<p>✓ Namespace flavor-network/v1 registrado</p>';

    $routes = $rest_server->get_routes();
    $network_routes = array_filter(array_keys($routes), function($route) {
        return strpos($route, '/flavor-network/v1/') === 0;
    });

    echo '<p>Rutas registradas: ' . count($network_routes) . '</p>';
    echo '<ul>';
    foreach (array_slice($network_routes, 0, 10) as $route) {
        echo '<li>' . esc_html($route) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>✗ Namespace flavor-network/v1 NO REGISTRADO</p>';
    echo '<p>Namespaces disponibles: ' . implode(', ', $namespaces) . '</p>';
}

// 5. Verificar datos demo
echo '<h2>Datos Demo:</h2>';
$demo_manager = Flavor_Demo_Data_Manager::get_instance();
$tiene_datos = $demo_manager->has_demo_data('network');
$count = $demo_manager->get_demo_data_count('network');

echo $tiene_datos ? "<p>✓ Datos demo: {$count} registros</p>" : "<p>✗ No hay datos demo</p>";

echo '<hr>';
echo '<p><strong>URL de la página de Network:</strong> <a href="' . admin_url('admin.php?page=flavor-network') . '">Ir a Network</a></p>';
echo '<p><strong>URL del API:</strong> <code>' . rest_url('flavor-network/v1/') . '</code></p>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">Volver al Compositor</a></p>';
