<?php
/**
 * Script de prueba para popular red social
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/test-populate-red-social.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Test: Popular Red Social</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

// Cargar Demo Data Manager
require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-manager.php';

if (!class_exists('Flavor_Demo_Data_Manager')) {
    echo '<p style="color: red;">✗ Clase Flavor_Demo_Data_Manager no encontrada</p>';
    echo '</div>';
    exit;
}

echo '<p>✓ Clase Flavor_Demo_Data_Manager encontrada</p>';

// Obtener instancia singleton
$manager = Flavor_Demo_Data_Manager::get_instance();

echo '<p>✓ Instancia obtenida</p>';

// Verificar que el método existe
if (!method_exists($manager, 'populate_module')) {
    echo '<p style="color: red;">✗ Método populate_module no existe</p>';
    echo '</div>';
    exit;
}

echo '<p>✓ Método populate_module existe</p>';

// Intentar popular
echo '<h3>Intentando popular red_social...</h3>';

try {
    $resultado = $manager->populate_module('red_social');

    echo '<pre>';
    print_r($resultado);
    echo '</pre>';

    if (isset($resultado['success']) && $resultado['success']) {
        echo '<p style="color: green;">✓ Éxito!</p>';
    } else {
        echo '<p style="color: red;">✗ Falló</p>';
        if (isset($resultado['error'])) {
            echo '<p style="color: red;">Error: ' . esc_html($resultado['error']) . '</p>';
        }
    }
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Excepción: ' . esc_html($e->getMessage()) . '</p>';
    echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
}

echo '</div>';

// Verificar tablas
echo '<h2>Verificar Tablas</h2>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

global $wpdb;
$tablas = [
    'flavor_social_perfiles',
    'flavor_social_publicaciones',
    'flavor_social_comentarios'
];

foreach ($tablas as $tabla) {
    $tabla_completa = $wpdb->prefix . $tabla;
    $existe = Flavor_Chat_Helpers::tabla_existe($tabla_completa);

    if ($existe) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `$tabla_completa`");
        echo "<p style='color: green;'>✓ {$tabla}: existe, {$count} registros</p>";
    } else {
        echo "<p style='color: red;'>✗ {$tabla}: no existe</p>";
    }
}

echo '</div>';

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '" class="button">Volver al Compositor</a></p>';
