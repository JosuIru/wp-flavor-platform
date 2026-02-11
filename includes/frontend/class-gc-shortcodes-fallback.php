<?php
/**
 * Fallback para registrar shortcodes de Grupos de Consumo
 * cuando el módulo no está cargado pero la página los usa.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_GC_Shortcodes_Fallback {
    private static $instance = null;
    private $module_instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_shortcodes'], 9);
    }

    public function register_shortcodes() {
        if (shortcode_exists('gc_nav') || shortcode_exists('gc_formulario_union')) {
            return;
        }

        $this->ensure_gc_module_loaded();
        $this->ensure_gc_membership_loaded();
    }

    private function ensure_gc_module_loaded() {
        if (class_exists('Flavor_Chat_IA')) {
            $plugin = Flavor_Chat_IA::get_instance();
            if (method_exists($plugin, 'get_modules')) {
                $modules = $plugin->get_modules();
                if (isset($modules['grupos_consumo'])) {
                    $this->module_instance = $modules['grupos_consumo'];
                    return;
                }
            }
        }

        if (!class_exists('Flavor_Chat_Grupos_Consumo_Module')) {
            $file = FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/class-grupos-consumo-module.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (class_exists('Flavor_Chat_Grupos_Consumo_Module') && $this->module_instance === null) {
            $this->module_instance = new Flavor_Chat_Grupos_Consumo_Module();
        }
    }

    private function ensure_gc_membership_loaded() {
        if (class_exists('Flavor_GC_Membership')) {
            Flavor_GC_Membership::get_instance();
            return;
        }

        $file = FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/class-gc-membership.php';
        if (file_exists($file)) {
            require_once $file;
        }

        if (class_exists('Flavor_GC_Membership')) {
            Flavor_GC_Membership::get_instance();
        }
    }
}

Flavor_GC_Shortcodes_Fallback::get_instance();
