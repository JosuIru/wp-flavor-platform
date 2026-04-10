<?php
/**
 * Sistema de Licenciamiento de Addons
 *
 * Gestiona activación, validación y renovación de licencias
 * para addons premium de Flavor Platform
 *
 * @package FlavorPlatform
 * @subpackage Addons
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de licencias
 *
 * @since 3.0.0
 */
class Flavor_Addon_License {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_License
     */
    private static $instancia = null;

    /**
     * URL del servidor de licencias
     *
     * @var string
     */
    private $servidor_licencias = 'https://api.gailu.net/v1/licenses';

    /**
     * Licencias activas
     *
     * @var array
     */
    private $licencias_activas = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_License
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_licenses();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Verificar licencias diariamente
        add_action('flavor_daily_license_check', [$this, 'verify_all_licenses']);
        if (!wp_next_scheduled('flavor_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'flavor_daily_license_check');
        }

        // AJAX para gestión de licencias
        add_action('wp_ajax_flavor_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_flavor_deactivate_license', [$this, 'ajax_deactivate_license']);
        add_action('wp_ajax_flavor_verify_license', [$this, 'ajax_verify_license']);

        // Mostrar avisos de licencias
        add_action('admin_notices', [$this, 'show_license_notices']);
    }

    /**
     * Carga licencias guardadas
     *
     * @return void
     */
    private function load_licenses() {
        $this->licencias_activas = get_option('flavor_addon_licenses', []);
    }

    /**
     * Guarda licencias
     *
     * @return bool
     */
    private function save_licenses() {
        return update_option('flavor_addon_licenses', $this->licencias_activas);
    }

    /**
     * Activa una licencia
     *
     * @param string $addon_slug Slug del addon
     * @param string $license_key Clave de licencia
     * @return bool|WP_Error
     */
    public function activate_license($addon_slug, $license_key) {
        // Validar formato de licencia
        if (!$this->is_valid_license_format($license_key)) {
            return new WP_Error('invalid_format', __('Formato de licencia inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Hacer request al servidor
        $response = $this->make_license_request('activate', [
            'license' => $license_key,
            'addon' => $addon_slug,
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar licencia
        $this->licencias_activas[$addon_slug] = [
            'key' => $license_key,
            'status' => 'active',
            'activated_at' => current_time('mysql'),
            'expires_at' => $response['expires_at'] ?? null,
            'license_type' => $response['license_type'] ?? 'regular',
            'activations_left' => $response['activations_left'] ?? 0,
            'last_check' => current_time('mysql'),
        ];

        $this->save_licenses();

        flavor_platform_log("Licencia activada para addon: {$addon_slug}");

        do_action('flavor_license_activated', $addon_slug, $license_key);

        return true;
    }

    /**
     * Desactiva una licencia
     *
     * @param string $addon_slug Slug del addon
     * @return bool|WP_Error
     */
    public function deactivate_license($addon_slug) {
        if (!isset($this->licencias_activas[$addon_slug])) {
            return new WP_Error('no_license', __('No hay licencia activa para este addon.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $license_key = $this->licencias_activas[$addon_slug]['key'];

        // Hacer request al servidor
        $response = $this->make_license_request('deactivate', [
            'license' => $license_key,
            'addon' => $addon_slug,
            'site_url' => get_site_url(),
        ]);

        if (is_wp_error($response)) {
            // Aún así eliminar localmente
            flavor_platform_log("Error desactivando licencia en servidor: " . $response->get_error_message(), 'warning');
        }

        // Eliminar licencia local
        unset($this->licencias_activas[$addon_slug]);
        $this->save_licenses();

        flavor_platform_log("Licencia desactivada para addon: {$addon_slug}");

        do_action('flavor_license_deactivated', $addon_slug);

        return true;
    }

    /**
     * Verifica una licencia con el servidor
     *
     * @param string $addon_slug Slug del addon
     * @return bool|WP_Error
     */
    public function verify_license($addon_slug) {
        if (!isset($this->licencias_activas[$addon_slug])) {
            return new WP_Error('no_license', __('No hay licencia para verificar.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $license_key = $this->licencias_activas[$addon_slug]['key'];

        // Hacer request al servidor
        $response = $this->make_license_request('verify', [
            'license' => $license_key,
            'addon' => $addon_slug,
            'site_url' => get_site_url(),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        // Actualizar información local
        $this->licencias_activas[$addon_slug]['status'] = $response['status'];
        $this->licencias_activas[$addon_slug]['expires_at'] = $response['expires_at'] ?? null;
        $this->licencias_activas[$addon_slug]['last_check'] = current_time('mysql');

        $this->save_licenses();

        return $response['status'] === 'active';
    }

    /**
     * Verifica todas las licencias
     *
     * @return void
     */
    public function verify_all_licenses() {
        foreach (array_keys($this->licencias_activas) as $addon_slug) {
            $this->verify_license($addon_slug);
        }

        flavor_platform_log('Verificación de licencias completada');
    }

    /**
     * Hace un request al servidor de licencias
     *
     * @param string $action Acción (activate, deactivate, verify)
     * @param array $data Datos adicionales
     * @return array|WP_Error
     */
    private function make_license_request($action, $data) {
        $url = add_query_arg('action', $action, $this->servidor_licencias);

        $response = wp_remote_post($url, [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'FlavorPlatform/' . FLAVOR_PLATFORM_VERSION,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            flavor_platform_log('Error en request de licencia: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($code !== 200) {
            return new WP_Error(
                'license_error',
                $result['message'] ?? __('Error en servidor de licencias.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        return $result;
    }

    /**
     * Verifica si una licencia está activa
     *
     * @param string $addon_slug Slug del addon
     * @return bool
     */
    public function is_license_active($addon_slug) {
        if (!isset($this->licencias_activas[$addon_slug])) {
            return false;
        }

        $license = $this->licencias_activas[$addon_slug];

        // Verificar estado
        if ($license['status'] !== 'active') {
            return false;
        }

        // Verificar expiración
        if (!empty($license['expires_at'])) {
            $expires = strtotime($license['expires_at']);
            if ($expires < time()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene información de una licencia
     *
     * @param string $addon_slug Slug del addon
     * @return array|null
     */
    public function get_license_info($addon_slug) {
        return $this->licencias_activas[$addon_slug] ?? null;
    }

    /**
     * Obtiene días restantes de una licencia
     *
     * @param string $addon_slug Slug del addon
     * @return int|null Días restantes o null si es permanente
     */
    public function get_license_days_remaining($addon_slug) {
        if (!isset($this->licencias_activas[$addon_slug])) {
            return null;
        }

        $license = $this->licencias_activas[$addon_slug];

        if (empty($license['expires_at'])) {
            return null; // Licencia permanente
        }

        $expires = strtotime($license['expires_at']);
        $now = time();

        if ($expires < $now) {
            return 0;
        }

        return ceil(($expires - $now) / DAY_IN_SECONDS);
    }

    /**
     * Valida formato de clave de licencia
     *
     * @param string $license_key Clave a validar
     * @return bool
     */
    private function is_valid_license_format($license_key) {
        // Formato: XXXX-XXXX-XXXX-XXXX (4 grupos de 4 caracteres)
        return (bool) preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license_key);
    }

    /**
     * AJAX: Activar licencia
     *
     * @return void
     */
    public function ajax_activate_license() {
        check_ajax_referer('flavor_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $addon_slug = sanitize_text_field($_POST['addon_slug'] ?? '');
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');

        if (empty($addon_slug) || empty($license_key)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $result = $this->activate_license($addon_slug, $license_key);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Licencia activada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'license' => $this->get_license_info($addon_slug),
        ]);
    }

    /**
     * AJAX: Desactivar licencia
     *
     * @return void
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('flavor_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $addon_slug = sanitize_text_field($_POST['addon_slug'] ?? '');

        if (empty($addon_slug)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $result = $this->deactivate_license($addon_slug);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Licencia desactivada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Verificar licencia
     *
     * @return void
     */
    public function ajax_verify_license() {
        check_ajax_referer('flavor_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $addon_slug = sanitize_text_field($_POST['addon_slug'] ?? '');

        if (empty($addon_slug)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $result = $this->verify_license($addon_slug);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'active' => $result,
            'license' => $this->get_license_info($addon_slug),
        ]);
    }

    /**
     * Muestra avisos sobre licencias
     *
     * @return void
     */
    public function show_license_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        if (!in_array($screen->id, ['flavor-platform_page_flavor-addons', 'toplevel_page_flavor-dashboard'])) {
            return;
        }

        foreach ($this->licencias_activas as $addon_slug => $license) {
            // Licencias por expirar (menos de 30 días)
            $days_remaining = $this->get_license_days_remaining($addon_slug);
            if ($days_remaining !== null && $days_remaining <= 30 && $days_remaining > 0) {
                $addon_info = Flavor_Addon_Manager::get_addon_info($addon_slug);
                $addon_name = $addon_info['name'] ?? $addon_slug;

                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__('Licencia por expirar:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ';
                echo sprintf(
                    esc_html__('La licencia de "%s" expira en %d días. Renuévala para seguir recibiendo actualizaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($addon_name),
                    $days_remaining
                );
                echo '</p></div>';
            }

            // Licencias expiradas
            if ($days_remaining !== null && $days_remaining <= 0) {
                $addon_info = Flavor_Addon_Manager::get_addon_info($addon_slug);
                $addon_name = $addon_info['name'] ?? $addon_slug;

                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Licencia expirada:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ';
                echo sprintf(
                    esc_html__('La licencia de "%s" ha expirado. Renuévala para seguir recibiendo actualizaciones y soporte.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    esc_html($addon_name)
                );
                echo '</p></div>';
            }
        }
    }

    /**
     * Obtiene todas las licencias activas
     *
     * @return array
     */
    public function get_all_licenses() {
        return $this->licencias_activas;
    }

    /**
     * Configura servidor de licencias personalizado
     *
     * @param string $url URL del servidor
     * @return void
     */
    public function set_license_server($url) {
        $this->servidor_licencias = trailingslashit($url);
    }
}

/**
 * Función helper para verificar licencia
 *
 * @param string $addon_slug Slug del addon
 * @return bool
 */
function flavor_is_licensed($addon_slug) {
    return Flavor_Addon_License::get_instance()->is_license_active($addon_slug);
}

/**
 * Función helper para obtener info de licencia
 *
 * @param string $addon_slug Slug del addon
 * @return array|null
 */
function flavor_get_license_info($addon_slug) {
    return Flavor_Addon_License::get_instance()->get_license_info($addon_slug);
}
