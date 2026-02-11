<?php
/**
 * Clase de administracion de permisos
 *
 * Registra la pagina de menu y gestiona las acciones AJAX
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Permissions_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Permissions_Admin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'registrar_menu']);
        add_action('wp_ajax_flavor_get_user_permissions', [$this, 'ajax_get_user_permissions']);
        add_action('wp_ajax_flavor_assign_module_role', [$this, 'ajax_assign_module_role']);
        add_action('wp_ajax_flavor_revoke_module_role', [$this, 'ajax_revoke_module_role']);
        add_action('wp_ajax_flavor_update_role_capabilities', [$this, 'ajax_update_role_capabilities']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Registra la pagina de menu
     */
    public function registrar_menu() {
        add_submenu_page(
            'flavor-chat-ia', // Parent slug
            __('Gestion de Permisos', 'flavor-chat-ia'),
            __('Permisos', 'flavor-chat-ia'),
            'flavor_manage_permissions',
            'flavor-permissions',
            [$this, 'render_pagina']
        );
    }

    /**
     * Renderiza la pagina de permisos
     */
    public function render_pagina() {
        // Cargar las clases necesarias
        if (!class_exists('Flavor_Role_Manager')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-role-manager.php';
        }
        if (!class_exists('Flavor_Permission_Helper')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-permission-helper.php';
        }

        include FLAVOR_CHAT_IA_PATH . 'admin/views/permissions.php';
    }

    /**
     * Encola scripts y estilos
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'flavor-permissions') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
    }

    /**
     * AJAX: Obtiene permisos de un usuario
     */
    public function ajax_get_user_permissions() {
        check_ajax_referer('flavor_permissions_nonce', 'nonce');

        if (!current_user_can('flavor_manage_permissions')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')], 403);
        }

        $user_id = intval($_POST['user_id']);

        if (!$user_id) {
            wp_send_json_error(['message' => __('ID de usuario invalido', 'flavor-chat-ia')]);
        }

        $resumen = Flavor_Permission_Helper::get_permissions_summary($user_id);
        $module_roles = Flavor_Permission_Helper::get_all_module_roles($user_id);

        wp_send_json_success([
            'user_id' => $user_id,
            'permissions_summary' => $resumen,
            'module_roles' => $module_roles,
        ]);
    }

    /**
     * AJAX: Asigna rol de modulo a usuario
     */
    public function ajax_assign_module_role() {
        check_ajax_referer('flavor_permissions_nonce', 'nonce');

        if (!current_user_can('flavor_manage_permissions')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')], 403);
        }

        $user_id = intval($_POST['user_id']);
        $module = sanitize_key($_POST['module']);
        $role = sanitize_key($_POST['role']);

        if (!$user_id || !$module || !$role) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = Flavor_Permission_Helper::assign_module_role($user_id, $module, $role);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Rol asignado correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('No se pudo asignar el rol', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Revoca rol de modulo de usuario
     */
    public function ajax_revoke_module_role() {
        check_ajax_referer('flavor_permissions_nonce', 'nonce');

        if (!current_user_can('flavor_manage_permissions')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')], 403);
        }

        $user_id = intval($_POST['user_id']);
        $module = sanitize_key($_POST['module']);

        if (!$user_id || !$module) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = Flavor_Permission_Helper::revoke_module_role($user_id, $module);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Rol revocado correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('No se pudo revocar el rol', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Actualiza capabilities de un rol
     */
    public function ajax_update_role_capabilities() {
        check_ajax_referer('flavor_permissions_nonce', 'nonce');

        if (!current_user_can('flavor_manage_roles')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')], 403);
        }

        $role_slug = sanitize_key($_POST['role']);
        $capabilities = isset($_POST['capabilities']) ? array_map('sanitize_key', $_POST['capabilities']) : [];

        if (!$role_slug) {
            wp_send_json_error(['message' => __('Rol invalido', 'flavor-chat-ia')]);
        }

        $role_manager = Flavor_Role_Manager::get_instance();
        $resultado = $role_manager->actualizar_capabilities_rol($role_slug, $capabilities);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Capabilities actualizadas correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('No se pudieron actualizar las capabilities', 'flavor-chat-ia')]);
        }
    }

    /**
     * Obtiene estadisticas de permisos
     *
     * @return array
     */
    public function obtener_estadisticas() {
        global $wpdb;

        $role_manager = Flavor_Role_Manager::get_instance();

        // Contar usuarios por rol de Flavor
        $stats = [
            'total_capabilities' => count($role_manager->obtener_todas_las_capabilities()),
            'total_modules' => count($role_manager->obtener_modulos_con_capabilities()),
            'total_roles' => count($role_manager->obtener_roles()),
            'custom_roles' => count($role_manager->obtener_roles_personalizados()),
            'users_by_flavor_role' => [],
            'users_with_module_roles' => 0,
        ];

        // Usuarios por rol de Flavor
        foreach (array_keys($role_manager->obtener_roles()) as $rol) {
            $count = count(get_users(['role' => $rol]));
            if ($count > 0) {
                $stats['users_by_flavor_role'][$rol] = $count;
            }
        }

        // Usuarios con roles de modulo
        $stats['users_with_module_roles'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
            WHERE meta_key = '_flavor_module_roles'"
        );

        return $stats;
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_Permissions_Admin::get_instance();
    }
});
