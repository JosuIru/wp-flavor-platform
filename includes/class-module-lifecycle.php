<?php
/**
 * Module Lifecycle Hooks
 *
 * Sincroniza eventos de activación/desactivación de módulos
 * y crea páginas automáticamente cuando corresponda.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Lifecycle {

    public function __construct() {
        add_action('update_option_flavor_chat_ia_settings', [$this, 'on_settings_update'], 10, 3);
        add_action('flavor_module_activated', [$this, 'handle_module_activated']);
    }

    /**
     * Detecta cambios en módulos activos.
     *
     * @param array $old_value
     * @param array $value
     * @param string $option
     */
    public function on_settings_update($old_value, $value, $option) {
        $old_modules = $this->normalize_modules($old_value['active_modules'] ?? []);
        $new_modules = $this->normalize_modules($value['active_modules'] ?? []);

        $activated = array_values(array_diff($new_modules, $old_modules));
        $deactivated = array_values(array_diff($old_modules, $new_modules));

        foreach ($activated as $module_id) {
            do_action('flavor_module_activated', $module_id);
            do_action('flavor_module_activated_' . $module_id, $module_id);
        }

        foreach ($deactivated as $module_id) {
            do_action('flavor_module_deactivated', $module_id);
            do_action('flavor_module_deactivated_' . $module_id, $module_id);
        }
    }

    /**
     * Al activar un módulo, crear páginas web correspondientes.
     *
     * @param string $module_id
     */
    public function handle_module_activated($module_id) {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // Asegurar que $wp_rewrite está disponible (necesario para wp_insert_post)
        if (!did_action('init')) {
            // Diferir la creación de páginas hasta después de init
            add_action('init', function() use ($module_id) {
                if (class_exists('Flavor_Page_Creator')) {
                    Flavor_Page_Creator::create_pages_for_modules([$module_id]);
                }
            }, 99);
            return;
        }

        Flavor_Page_Creator::create_pages_for_modules([$module_id]);
    }

    /**
     * Normaliza lista de módulos.
     *
     * @param array $modules
     * @return array
     */
    private function normalize_modules($modules) {
        $modules = is_array($modules) ? $modules : [];
        $normalized = [];
        foreach ($modules as $module_id) {
            if (!is_string($module_id)) {
                continue;
            }
            $module_id = trim(str_replace('-', '_', $module_id));
            if ($module_id !== '') {
                $normalized[] = $module_id;
            }
        }
        return array_values(array_unique($normalized));
    }
}

new Flavor_Module_Lifecycle();
