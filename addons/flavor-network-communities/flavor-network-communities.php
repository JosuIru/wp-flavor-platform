<?php
/**
 * Addon Name: Flavor Network Communities
 * Description: Sistema de red multi-sitio para conectar comunidades, compartir contenido, eventos, colaboraciones y catálogo global. Ideal para redes de municipios, franquicias o federaciones.
 * Version: 1.0.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Chat IA 3.0.0+
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del addon
if (!defined('FLAVOR_NETWORK_VERSION')) {
    define('FLAVOR_NETWORK_VERSION', '1.0.0');
}
if (!defined('FLAVOR_NETWORK_PATH')) {
    define('FLAVOR_NETWORK_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_NETWORK_URL')) {
    define('FLAVOR_NETWORK_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_network_communities_register');

function flavor_network_communities_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('network-communities', [
        'name' => __('Network Communities', 'flavor-network-communities'),
        'version' => FLAVOR_NETWORK_VERSION,
        'description' => __('Sistema de red multi-sitio para conectar comunidades y compartir recursos globales.', 'flavor-network-communities'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-networking',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.4',
                'wordpress' => '5.8',
                'php_extension:curl' => true,
                'php_extension:json' => true,
            ],
            'optional' => [
                'module:eventos' => [
                    'name' => 'Módulo de Eventos',
                    'feature' => 'Eventos compartidos en la red'
                ],
                'module:marketplace' => [
                    'name' => 'Módulo de Marketplace',
                    'feature' => 'Catálogo global de productos'
                ],
            ]
        ],
        'init_callback' => 'flavor_network_communities_init',
        'settings_page' => 'admin.php?page=flavor-network',
        'documentation_url' => 'https://gailu.net/docs/network-communities',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 */
function flavor_network_communities_init() {
    // Cargar clases del sistema de red
    require_once FLAVOR_NETWORK_PATH . 'includes/class-network-installer.php';
    require_once FLAVOR_NETWORK_PATH . 'includes/class-network-node.php';
    require_once FLAVOR_NETWORK_PATH . 'includes/class-network-api.php';
    require_once FLAVOR_NETWORK_PATH . 'includes/class-network-manager.php';

    // Cargar admin solo en backend
    if (is_admin()) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-admin.php';
    }

    // Inicializar componentes principales
    if (class_exists('Flavor_Network_Manager')) {
        Flavor_Network_Manager::get_instance();
    }

    if (is_admin() && class_exists('Flavor_Network_Admin')) {
        Flavor_Network_Admin::get_instance();
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG) {
        error_log('[Flavor Network Communities] Addon inicializado correctamente v' . FLAVOR_NETWORK_VERSION);
    }
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-network-communities',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
