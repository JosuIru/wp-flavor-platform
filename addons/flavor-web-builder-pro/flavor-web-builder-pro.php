<?php
/**
 * Addon Name: Flavor Web Builder Pro
 * Description: Constructor visual drag & drop de páginas y landing pages con IA integrada. Incluye 170+ componentes, 17 templates predefinidos y asistente con IA para generar diseños automáticamente.
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
if (!defined('FLAVOR_WEB_BUILDER_VERSION')) {
    define('FLAVOR_WEB_BUILDER_VERSION', '1.2.0');
}
if (!defined('FLAVOR_WEB_BUILDER_PATH')) {
    define('FLAVOR_WEB_BUILDER_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_WEB_BUILDER_URL')) {
    define('FLAVOR_WEB_BUILDER_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_web_builder_pro_register');

function flavor_web_builder_pro_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('web-builder-pro', [
        'name' => __('Web Builder Pro', 'flavor-web-builder-pro'),
        'version' => FLAVOR_WEB_BUILDER_VERSION,
        'description' => __('Constructor visual drag & drop con 170+ componentes, templates predefinidos y asistente IA.', 'flavor-web-builder-pro'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-layout',
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
                    'feature' => 'Asistente IA para generar templates'
                ],
            ]
        ],
        'init_callback' => 'flavor_web_builder_pro_init',
        'settings_page' => 'edit.php?post_type=flavor_landing',
        'documentation_url' => 'https://gailu.net/docs/web-builder-pro',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 */
function flavor_web_builder_pro_init() {
    // Cargar clases del web builder
    require_once FLAVOR_WEB_BUILDER_PATH . 'includes/class-component-registry.php';
    require_once FLAVOR_WEB_BUILDER_PATH . 'includes/class-component-renderer.php';
    require_once FLAVOR_WEB_BUILDER_PATH . 'includes/class-preview-handler.php';
    require_once FLAVOR_WEB_BUILDER_PATH . 'includes/class-page-builder.php';
    require_once FLAVOR_WEB_BUILDER_PATH . 'includes/class-ai-template-assistant.php';

    // Inicializar componentes principales
    if (class_exists('Flavor_Page_Builder')) {
        Flavor_Page_Builder::get_instance();
    }

    if (class_exists('Flavor_Component_Renderer')) {
        Flavor_Component_Renderer::get_instance();
    }

    if (class_exists('Flavor_Preview_Handler')) {
        Flavor_Preview_Handler::get_instance();
    }

    if (class_exists('Flavor_AI_Template_Assistant')) {
        Flavor_AI_Template_Assistant::get_instance();
    }

    if (function_exists('flavor_component_registry')) {
        flavor_component_registry();
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG) {
        flavor_log_debug( 'Addon inicializado correctamente v' . FLAVOR_WEB_BUILDER_VERSION, 'WebBuilderPro' );
    }
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-web-builder-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
