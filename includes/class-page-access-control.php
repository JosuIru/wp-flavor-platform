<?php
/**
 * Control de Acceso a Páginas
 *
 * Middleware que verifica permisos antes de mostrar páginas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Page_Access_Control {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
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
        add_action('template_redirect', [$this, 'check_page_access'], 5);
        add_filter('login_redirect', [$this, 'redirect_after_login'], 10, 3);
    }

    /**
     * Verifica el acceso a la página actual
     */
    public function check_page_access() {
        if (!is_page()) {
            return;
        }

        $page_id = get_the_ID();

        // Verificar si requiere login
        if ($this->requires_login($page_id)) {
            if (!is_user_logged_in()) {
                // Guardar URL de retorno
                $return_url = get_permalink($page_id);
                auth_redirect();
                exit;
            }
        }

        // Verificar permisos de módulo
        $this->check_module_access($page_id);
    }

    /**
     * Verifica si la página requiere login
     */
    private function requires_login($page_id) {
        // Meta explícito
        $requires_login = get_post_meta($page_id, '_flavor_requires_login', true);
        if ($requires_login === '1') {
            return true;
        }

        // Páginas auto-creadas por Flavor requieren login por defecto
        // excepto las landings principales
        $is_auto_page = get_post_meta($page_id, '_flavor_auto_page', true);
        if ($is_auto_page) {
            $slug = get_post_field('post_name', $page_id);
            $parent_id = wp_get_post_parent_id($page_id);

            // Páginas de nivel superior (sin padre) son públicas
            if ($parent_id === 0) {
                return false;
            }

            // Páginas "mis-", "crear", "editar" requieren login
            if (preg_match('/^(mis-|crear|editar|nuevo)/', $slug)) {
                return true;
            }

            // Formularios requieren login
            $content = get_post_field('post_content', $page_id);
            if (strpos($content, '[flavor_module_form') !== false) {
                return true;
            }
        }

        // Mi Portal siempre requiere login
        $slug = get_post_field('post_name', $page_id);
        if ($slug === 'mi-portal' || $slug === 'mi-cuenta') {
            return true;
        }

        return false;
    }

    /**
     * Verifica acceso específico del módulo
     */
    private function check_module_access($page_id) {
        // Obtener módulo asociado a la página
        $modules_meta = get_post_meta($page_id, '_flavor_auto_page_modules', true);
        if (empty($modules_meta)) {
            return;
        }

        $modules = explode(',', $modules_meta);
        if (empty($modules)) {
            return;
        }

        // Verificar acceso al primer módulo (normalmente solo hay uno)
        $module_id = trim($modules[0]);

        if (class_exists('Flavor_Module_Access_Control') && class_exists('Flavor_User_Messages')) {
            $control = Flavor_Module_Access_Control::get_instance();
            if (!$control->user_can_access($module_id)) {
                // Usar mensaje de error mejorado
                $module_name = ucfirst(str_replace(['_', '-'], ' ', $module_id));
                $reason = __('Este módulo requiere permisos específicos que actualmente no tienes asignados.', 'flavor-chat-ia');
                Flavor_User_Messages::access_denied($module_name, $reason);
            }
        }
    }

    /**
     * Redirige al dashboard después del login
     */
    public function redirect_after_login($redirect_to, $request, $user) {
        // Si hay URL de retorno específica, usarla
        if (!empty($request) && $request !== admin_url()) {
            return $request;
        }

        // Si es admin, dejar el redirect por defecto
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('administrator', $user->roles)) {
                return admin_url();
            }
        }

        // Para usuarios normales, ir a Mi Portal
        $portal_page = get_page_by_path('mi-portal');
        if ($portal_page) {
            return get_permalink($portal_page);
        }

        return home_url('/');
    }

    /**
     * Marca una página como que requiere login
     */
    public static function mark_page_requires_login($page_id) {
        update_post_meta($page_id, '_flavor_requires_login', '1');
    }

    /**
     * Marca una página como pública
     */
    public static function mark_page_public($page_id) {
        update_post_meta($page_id, '_flavor_requires_login', '0');
    }
}
