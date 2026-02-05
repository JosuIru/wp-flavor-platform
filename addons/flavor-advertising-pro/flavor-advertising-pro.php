<?php
/**
 * Addon Name: Flavor Advertising Pro
 * Description: Sistema completo de publicidad ética con gestión de anuncios, anunciantes, tracking, pagos y red global de anuncios. GDPR compliant.
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
if (!defined('FLAVOR_ADVERTISING_VERSION')) {
    define('FLAVOR_ADVERTISING_VERSION', '1.0.0');
}
if (!defined('FLAVOR_ADVERTISING_PATH')) {
    define('FLAVOR_ADVERTISING_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_ADVERTISING_URL')) {
    define('FLAVOR_ADVERTISING_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_advertising_pro_register');

function flavor_advertising_pro_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('advertising-pro', [
        'name' => __('Advertising Pro', 'flavor-advertising-pro'),
        'version' => FLAVOR_ADVERTISING_VERSION,
        'description' => __('Sistema completo de publicidad ética con tracking, pagos y red global. GDPR compliant.', 'flavor-advertising-pro'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-megaphone',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.4',
                'wordpress' => '5.8',
            ],
            'optional' => [
                'addon:network-communities' => [
                    'name' => 'Network Communities',
                    'feature' => 'Red global de anuncios compartidos'
                ],
            ]
        ],
        'init_callback' => 'flavor_advertising_pro_init',
        'settings_page' => 'admin.php?page=flavor-advertising',
        'documentation_url' => 'https://gailu.net/docs/advertising-pro',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 */
function flavor_advertising_pro_init() {
    // Cargar clase principal del sistema de publicidad
    require_once FLAVOR_ADVERTISING_PATH . 'includes/class-advertising-system.php';

    // Cargar módulo de publicidad (integración con IA)
    if (class_exists('Flavor_Chat_Module_Loader')) {
        require_once FLAVOR_ADVERTISING_PATH . 'includes/class-advertising-module.php';

        add_filter('flavor_register_modules', function($modules) {
            if (class_exists('Flavor_Chat_Module_Advertising')) {
                $modules['advertising'] = new Flavor_Chat_Module_Advertising();
            }
            return $modules;
        });
    }

    // Inicializar sistema de publicidad
    if (class_exists('Flavor_Advertising_System')) {
        Flavor_Advertising_System::get_instance();
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG) {
        error_log('[Flavor Advertising Pro] Addon inicializado correctamente v' . FLAVOR_ADVERTISING_VERSION);
    }
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-advertising-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
