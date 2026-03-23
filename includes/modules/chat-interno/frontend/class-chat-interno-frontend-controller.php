<?php
/**
 * Frontend Controller para Chat Interno.
 *
 * Expone shortcodes canónicos y los redirige al módulo principal
 * sin duplicar la lógica de negocio.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Interno_Frontend_Controller {

    /**
     * Instancia singleton.
     *
     * @var Flavor_Chat_Interno_Frontend_Controller|null
     */
    private static $instance = null;

    /**
     * Devuelve instancia singleton.
     *
     * @return Flavor_Chat_Interno_Frontend_Controller
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor privado.
     */
    private function __construct() {
        add_action('init', [$this, 'register_shortcodes'], 20);
    }

    /**
     * Registra shortcodes canónicos sin sobrescribir existentes.
     *
     * @return void
     */
    public function register_shortcodes() {
        $this->register_alias('chat_interno_inbox', 'shortcode_inbox');
        $this->register_alias('chat_interno_conversacion', 'shortcode_conversacion');
        $this->register_alias('chat_interno_iniciar', 'shortcode_iniciar_chat');
    }

    /**
     * Registra alias de shortcode con proxy al módulo.
     *
     * @param string $tag Tag del shortcode.
     * @param string $module_method Método del módulo destino.
     * @return void
     */
    private function register_alias($tag, $module_method) {
        if (shortcode_exists($tag)) {
            return;
        }

        add_shortcode($tag, function ($atts = []) use ($module_method) {
            $module = $this->get_module();
            if (!$module || !method_exists($module, $module_method)) {
                return '';
            }

            return call_user_func([$module, $module_method], (array) $atts);
        });
    }

    /**
     * Obtiene instancia del módulo chat_interno.
     *
     * @return object|null
     */
    private function get_module() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return null;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        if (!$loader || !method_exists($loader, 'get_module')) {
            return null;
        }

        return $loader->get_module('chat_interno');
    }
}

Flavor_Chat_Interno_Frontend_Controller::get_instance();

