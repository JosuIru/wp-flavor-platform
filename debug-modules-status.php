<?php
/**
 * Script temporal de debug - ELIMINAR DESPUÉS DE USAR
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG: Estado de Módulos ===\n\n";

// 1. Leer configuración
$config = get_option('flavor_chat_ia_settings', []);
$active_modules = $config['active_modules'] ?? [];

echo "1. Active Modules en configuración:\n";
echo "   Total: " . count($active_modules) . "\n";
if (!empty($active_modules)) {
    foreach ($active_modules as $mod) {
        echo "   - $mod\n";
    }
} else {
    echo "   (Ninguno)\n";
}

// 2. App Profile
$app_profile = get_option('app_profile', null);
echo "\n2. App Profile: " . ($app_profile ?: '(no definido)') . "\n";

// 3. Módulos cargados
if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $loaded = $loader->get_loaded_modules();
    
    echo "\n3. Módulos cargados en memoria:\n";
    echo "   Total: " . count($loaded) . "\n";
    if (!empty($loaded)) {
        foreach ($loaded as $id => $instance) {
            echo "   - $id (" . get_class($instance) . ")\n";
        }
    } else {
        echo "   (Ninguno)\n";
    }
} else {
    echo "\n3. Module Loader no disponible\n";
}

// 4. Verificar BD directamente
global $wpdb;
$db_value = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'flavor_chat_ia_settings'");
if ($db_value) {
    $db_config = maybe_unserialize($db_value);
    $db_active = $db_config['active_modules'] ?? [];
    echo "\n4. Active Modules en BD (lectura directa):\n";
    echo "   Total: " . count($db_active) . "\n";
    if (!empty($db_active)) {
        foreach ($db_active as $mod) {
            echo "   - $mod\n";
        }
    } else {
        echo "   (Ninguno)\n";
    }
} else {
    echo "\n4. Opción flavor_chat_ia_settings no existe en BD\n";
}

echo "\n=== FIN DEBUG ===\n";
