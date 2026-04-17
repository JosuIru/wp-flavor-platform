<?php
/**
 * Trait para verificaciones de permisos
 *
 * Proporciona métodos comunes para verificar permisos
 * en endpoints REST y AJAX.
 *
 * @package FlavorPlatform
 * @subpackage Traits
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para verificación de permisos
 */
trait Flavor_Permission_Checks_Trait {

    /**
     * Verifica si el usuario actual es administrador
     *
     * @return bool
     */
    protected function is_admin_user() {
        return current_user_can('manage_options');
    }

    /**
     * Verifica si el usuario actual puede editar contenido
     *
     * @return bool
     */
    protected function can_edit_content() {
        return current_user_can('edit_posts');
    }

    /**
     * Verifica si el usuario actual está logueado
     *
     * @return bool
     */
    protected function is_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Verifica si el usuario puede gestionar un módulo específico
     *
     * @param string $module Nombre del módulo.
     * @return bool
     */
    protected function can_manage_module($module) {
        $capability = 'flavor_manage_' . sanitize_key($module);

        // Admins siempre pueden
        if ($this->is_admin_user()) {
            return true;
        }

        // Verificar capability específica
        if (current_user_can($capability)) {
            return true;
        }

        // Verificar si está en el array de gestores del módulo
        $gestores = get_option('flavor_module_managers_' . $module, []);
        return in_array(get_current_user_id(), (array) $gestores, true);
    }

    /**
     * Verifica si el usuario es socio activo
     *
     * @return bool
     */
    protected function is_active_member() {
        if (!$this->is_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $socio_id = get_user_meta($user_id, 'flavor_socio_id', true);

        if (empty($socio_id)) {
            return false;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';
        $estado = $wpdb->get_var($wpdb->prepare(
            "SELECT estado FROM {$tabla} WHERE id = %d",
            $socio_id
        ));

        return $estado === 'activo';
    }

    /**
     * Verifica si el usuario es propietario de un recurso
     *
     * @param string $table      Tabla del recurso.
     * @param int    $resource_id ID del recurso.
     * @param string $user_field Campo que contiene el user_id.
     * @return bool
     */
    protected function is_resource_owner($table, $resource_id, $user_field = 'user_id') {
        if (!$this->is_logged_in()) {
            return false;
        }

        global $wpdb;
        $full_table = $wpdb->prefix . $table;
        $owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT {$user_field} FROM {$full_table} WHERE id = %d",
            $resource_id
        ));

        return (int) $owner_id === get_current_user_id();
    }

    /**
     * Verifica si el usuario puede acceder a un recurso (propietario o admin)
     *
     * @param string $table      Tabla del recurso.
     * @param int    $resource_id ID del recurso.
     * @param string $user_field Campo que contiene el user_id.
     * @return bool
     */
    protected function can_access_resource($table, $resource_id, $user_field = 'user_id') {
        if ($this->is_admin_user()) {
            return true;
        }
        return $this->is_resource_owner($table, $resource_id, $user_field);
    }

    /**
     * Verifica nonce de REST API
     *
     * @param WP_REST_Request $request Request a verificar.
     * @return bool
     */
    protected function verify_rest_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        if (empty($nonce)) {
            return false;
        }
        return wp_verify_nonce($nonce, 'wp_rest');
    }

    /**
     * Verifica nonce de AJAX
     *
     * @param string $action   Acción del nonce.
     * @param string $param    Nombre del parámetro (default 'nonce').
     * @return bool
     */
    protected function verify_ajax_nonce($action, $param = 'nonce') {
        $nonce = isset($_REQUEST[$param]) ? sanitize_text_field($_REQUEST[$param]) : '';
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Permission callback: requiere login
     *
     * @return bool|WP_Error
     */
    public function permission_logged_in() {
        if (!$this->is_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('Debes iniciar sesión para acceder a este recurso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * Permission callback: requiere admin
     *
     * @return bool|WP_Error
     */
    public function permission_admin() {
        if (!$this->is_admin_user()) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos de administrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * Permission callback: requiere socio activo
     *
     * @return bool|WP_Error
     */
    public function permission_active_member() {
        if (!$this->is_active_member()) {
            return new WP_Error(
                'rest_forbidden',
                __('Debes ser un socio activo para acceder a este recurso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * Permission callback: público (siempre permite)
     *
     * @return bool
     */
    public function permission_public() {
        return true;
    }
}
