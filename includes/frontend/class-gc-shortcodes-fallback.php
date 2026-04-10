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
        if (class_exists('Flavor_Platform')) {
            $plugin = Flavor_Platform::get_instance();
            if (method_exists($plugin, 'get_modules')) {
                $modules = $plugin->get_modules();
                if (isset($modules['grupos_consumo'])) {
                    $this->module_instance = $modules['grupos_consumo'];
                    return;
                }
            }
        }

        $grupos_consumo_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Grupos_Consumo_Module')
            : 'Flavor_Chat_Grupos_Consumo_Module';
        if (!class_exists($grupos_consumo_module_class)) {
            $file = FLAVOR_PLATFORM_PATH . 'includes/modules/grupos-consumo/class-grupos-consumo-module.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (class_exists($grupos_consumo_module_class) && $this->module_instance === null) {
            $this->module_instance = new $grupos_consumo_module_class();
        }
    }

    private function ensure_gc_membership_loaded() {
        if (class_exists('Flavor_GC_Membership')) {
            Flavor_GC_Membership::get_instance();
            return;
        }

        $file = FLAVOR_PLATFORM_PATH . 'includes/modules/grupos-consumo/class-gc-membership.php';
        if (file_exists($file)) {
            require_once $file;
        }

        if (class_exists('Flavor_GC_Membership')) {
            Flavor_GC_Membership::get_instance();
        }
    }
}

Flavor_GC_Shortcodes_Fallback::get_instance();
