<?php
/**
 * Addon Name: Flavor Admin Assistant
 * Description: Asistente con IA integrado en el panel de administración. Gestiona WordPress con comandos de voz, atajos de teclado y herramientas inteligentes. Control de acceso por roles incluido.
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
if (!defined('FLAVOR_ADMIN_ASSISTANT_VERSION')) {
    define('FLAVOR_ADMIN_ASSISTANT_VERSION', '1.0.0');
}
if (!defined('FLAVOR_ADMIN_ASSISTANT_PATH')) {
    define('FLAVOR_ADMIN_ASSISTANT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_ADMIN_ASSISTANT_URL')) {
    define('FLAVOR_ADMIN_ASSISTANT_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_admin_assistant_register');

function flavor_admin_assistant_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('admin-assistant', [
        'name' => __('Admin Assistant', 'flavor-admin-assistant'),
        'version' => FLAVOR_ADMIN_ASSISTANT_VERSION,
        'description' => __('Asistente IA para el panel de administración con atajos de teclado y herramientas inteligentes.', 'flavor-admin-assistant'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-admin-users',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.4',
                'wordpress' => '5.8',
            ],
            'optional' => [
                'module:chat-core' => [
                    'name' => 'Chat Core',
                    'feature' => 'Conversación con el asistente IA'
                ],
            ]
        ],
        'init_callback' => 'flavor_admin_assistant_init',
        'settings_page' => 'admin.php?page=flavor-admin-assistant',
        'documentation_url' => 'https://gailu.net/docs/admin-assistant',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 */
function flavor_admin_assistant_init() {
    // Solo cargar en admin
    if (!is_admin()) {
        return;
    }

    // Cargar clases del admin assistant
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-analytics-cache.php';
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-admin-backup.php';
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-admin-assistant-tools.php';
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-admin-shortcuts.php';
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-admin-role-access.php';
    require_once FLAVOR_ADMIN_ASSISTANT_PATH . 'includes/class-admin-assistant.php';

    // Inicializar asistente
    if (class_exists('Chat_IA_Admin_Assistant')) {
        Chat_IA_Admin_Assistant::get_instance();
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG) {
        flavor_log_debug( 'Addon inicializado correctamente v' . FLAVOR_ADMIN_ASSISTANT_VERSION, 'AdminAssistant' );
    }
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-admin-assistant',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
