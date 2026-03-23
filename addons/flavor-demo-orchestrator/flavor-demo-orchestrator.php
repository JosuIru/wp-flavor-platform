<?php
/**
 * Addon Name: Flavor Demo Orchestrator
 * Description: Orquesta la carga/limpieza de datos demo desde Apps Móviles y muestra historial de ejecuciones.
 * Version: 1.0.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Chat IA 3.0.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('FLAVOR_DEMO_ORCHESTRATOR_VERSION')) {
    define('FLAVOR_DEMO_ORCHESTRATOR_VERSION', '1.0.0');
}
if (!defined('FLAVOR_DEMO_ORCHESTRATOR_PATH')) {
    define('FLAVOR_DEMO_ORCHESTRATOR_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_DEMO_ORCHESTRATOR_URL')) {
    define('FLAVOR_DEMO_ORCHESTRATOR_URL', plugin_dir_url(__FILE__));
}

add_action('flavor_register_addons', 'flavor_demo_orchestrator_register');

function flavor_demo_orchestrator_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('demo-orchestrator', [
        'name' => __('Demo Orchestrator', 'flavor-demo-orchestrator'),
        'version' => FLAVOR_DEMO_ORCHESTRATOR_VERSION,
        'description' => __('Gestión centralizada de datos demo con acciones rápidas e historial.', 'flavor-demo-orchestrator'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-database-import',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.4',
                'wordpress' => '5.8',
            ],
        ],
        'init_callback' => 'flavor_demo_orchestrator_init',
        'settings_page' => 'admin.php?page=flavor-apps-config&tab=tools',
        'documentation_url' => '',
        'is_premium' => false,
    ]);
}

function flavor_demo_orchestrator_init() {
    if (!is_admin()) {
        return;
    }

    require_once FLAVOR_DEMO_ORCHESTRATOR_PATH . 'includes/class-demo-orchestrator-admin.php';

    if (class_exists('Flavor_Demo_Orchestrator_Admin')) {
        Flavor_Demo_Orchestrator_Admin::get_instance();
    }
}

add_action('init', function() {
    load_plugin_textdomain(
        'flavor-demo-orchestrator',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
