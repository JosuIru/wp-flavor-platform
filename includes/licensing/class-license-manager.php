<?php
/**
 * Gestor de Licencias de Flavor Platform
 *
 * Maneja la activación, verificación y gestión de licencias del plugin
 *
 * @package FlavorChatIA
 * @subpackage Licensing
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase gestora de licencias
 *
 * @since 3.2.0
 */
class Flavor_License_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_License_Manager
     */
    private static $instance = null;

    /**
     * URL del servidor de licencias
     *
     * @var string
     */
    private $license_server = 'https://api.gailu.net/v1/platform-licenses';

    /**
     * Opción donde se guarda la licencia
     *
     * @var string
     */
    private const LICENSE_OPTION = 'flavor_platform_license';

    /**
     * Opción para caché de verificación
     *
     * @var string
     */
    private const LICENSE_CACHE_OPTION = 'flavor_platform_license_cache';

    /**
     * TTL del caché de verificación (24 horas)
     *
     * @var int
     */
    private const CACHE_TTL = DAY_IN_SECONDS;

    /**
     * Datos de licencia actual
     *
     * @var array|null
     */
    private $license_data = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_License_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_license();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Verificación diaria
        add_action('flavor_daily_license_verify', [$this, 'verify_license_remote']);
        if (!wp_next_scheduled('flavor_daily_license_verify')) {
            wp_schedule_event(time(), 'daily', 'flavor_daily_license_verify');
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_platform_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_flavor_platform_deactivate_license', [$this, 'ajax_deactivate_license']);
        add_action('wp_ajax_flavor_platform_verify_license', [$this, 'ajax_verify_license']);

        // Avisos de admin
        add_action('admin_notices', [$this, 'show_license_notices']);

        // Filtro para control de acceso a módulos
        add_filter('flavor_module_access_allowed', [$this, 'filter_module_access'], 10, 2);
    }

    /**
     * Carga la licencia guardada
     *
     * @return void
     */
    private function load_license() {
        $this->license_data = get_option(self::LICENSE_OPTION, null);
    }

    /**
     * Guarda la licencia
     *
     * @return bool
     */
    private function save_license() {
        return update_option(self::LICENSE_OPTION, $this->license_data);
    }

    /**
     * Activa una licencia
     *
     * @param string $license_key Clave de licencia
     * @return array|WP_Error Datos de licencia o error
     */
    public function activate_license($license_key) {
        // Validar formato
        $license_key = $this->normalize_license_key($license_key);

        if (!$this->is_valid_license_format($license_key)) {
            return new WP_Error(
                'invalid_format',
                __('Formato de licencia inválido. Debe ser XXXX-XXXX-XXXX-XXXX', 'flavor-chat-ia')
            );
        }

        // Preparar datos
        $site_data = [
            'license_key' => $license_key,
            'site_url'    => $this->get_site_url(),
            'site_name'   => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'wp_version'  => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => FLAVOR_CHAT_IA_VERSION,
        ];

        // Verificar con servidor remoto
        $response = $this->make_api_request('activate', $site_data);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar licencia
        $this->license_data = [
            'key'            => $license_key,
            'status'         => 'active',
            'plan'           => $response['plan'] ?? 'starter',
            'activated_at'   => current_time('mysql'),
            'expires_at'     => $response['expires_at'] ?? null,
            'sites_allowed'  => $response['sites_allowed'] ?? 1,
            'sites_active'   => $response['sites_active'] ?? 1,
            'customer_email' => $response['customer_email'] ?? '',
            'customer_name'  => $response['customer_name'] ?? '',
            'last_verified'  => current_time('mysql'),
        ];

        $this->save_license();
        $this->clear_cache();

        // Log
        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("Licencia activada: Plan {$this->license_data['plan']}");
        }

        do_action('flavor_platform_license_activated', $this->license_data);

        return $this->license_data;
    }

    /**
     * Desactiva la licencia
     *
     * @return bool|WP_Error
     */
    public function deactivate_license() {
        if (!$this->has_license()) {
            return new WP_Error('no_license', __('No hay licencia activa para desactivar.', 'flavor-chat-ia'));
        }

        $license_key = $this->license_data['key'];

        // Notificar al servidor
        $response = $this->make_api_request('deactivate', [
            'license_key' => $license_key,
            'site_url'    => $this->get_site_url(),
        ]);

        // Incluso si falla el servidor, eliminar localmente
        if (is_wp_error($response)) {
            if (function_exists('flavor_chat_ia_log')) {
                flavor_chat_ia_log("Error desactivando licencia en servidor: " . $response->get_error_message(), 'warning');
            }
        }

        // Eliminar licencia local
        $old_plan = $this->license_data['plan'];
        $this->license_data = null;
        delete_option(self::LICENSE_OPTION);
        $this->clear_cache();

        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("Licencia desactivada (era plan: {$old_plan})");
        }

        do_action('flavor_platform_license_deactivated');

        return true;
    }

    /**
     * Verifica la licencia con el servidor remoto
     *
     * @param bool $force Forzar verificación ignorando caché
     * @return bool|WP_Error
     */
    public function verify_license_remote($force = false) {
        if (!$this->has_license()) {
            return false;
        }

        // Verificar caché
        if (!$force) {
            $cached = $this->get_cached_verification();
            if ($cached !== false) {
                return $cached['status'] === 'active';
            }
        }

        $response = $this->make_api_request('verify', [
            'license_key' => $this->license_data['key'],
            'site_url'    => $this->get_site_url(),
        ]);

        if (is_wp_error($response)) {
            // En caso de error de red, usar datos locales
            return $this->license_data['status'] === 'active';
        }

        // Actualizar datos locales
        $this->license_data['status'] = $response['status'];
        $this->license_data['plan'] = $response['plan'] ?? $this->license_data['plan'];
        $this->license_data['expires_at'] = $response['expires_at'] ?? $this->license_data['expires_at'];
        $this->license_data['sites_active'] = $response['sites_active'] ?? $this->license_data['sites_active'];
        $this->license_data['last_verified'] = current_time('mysql');

        $this->save_license();

        // Guardar en caché
        $this->set_verification_cache([
            'status'   => $response['status'],
            'verified' => current_time('timestamp'),
        ]);

        return $response['status'] === 'active';
    }

    /**
     * Hace una petición al API de licencias
     *
     * @param string $action Acción (activate, deactivate, verify)
     * @param array $data Datos a enviar
     * @return array|WP_Error
     */
    private function make_api_request($action, $data) {
        $url = trailingslashit($this->license_server) . $action;

        $response = wp_remote_post($url, [
            'body'    => wp_json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'FlavorPlatform/' . FLAVOR_CHAT_IA_VERSION,
                'X-Flavor-Site' => $this->get_site_url(),
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($code !== 200) {
            return new WP_Error(
                'license_error',
                $result['message'] ?? __('Error del servidor de licencias', 'flavor-chat-ia'),
                ['status' => $code]
            );
        }

        return $result;
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA
    // =========================================================================

    /**
     * Verifica si hay una licencia configurada
     *
     * @return bool
     */
    public function has_license() {
        return !empty($this->license_data) && !empty($this->license_data['key']);
    }

    /**
     * Verifica si la licencia está activa
     *
     * @return bool
     */
    public function is_license_active() {
        if (!$this->has_license()) {
            return false;
        }

        // Verificar estado
        if ($this->license_data['status'] !== 'active') {
            return false;
        }

        // Verificar expiración
        if (!empty($this->license_data['expires_at'])) {
            $expires = strtotime($this->license_data['expires_at']);
            if ($expires && $expires < time()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene el plan actual
     *
     * @return string Slug del plan (free si no hay licencia)
     */
    public function get_current_plan() {
        if (!$this->is_license_active()) {
            return 'free';
        }

        return $this->license_data['plan'] ?? 'free';
    }

    /**
     * Obtiene los datos de la licencia
     *
     * @return array|null
     */
    public function get_license_data() {
        return $this->license_data;
    }

    /**
     * Obtiene días restantes de la licencia
     *
     * @return int|null Días restantes o null si es permanente/no hay licencia
     */
    public function get_days_remaining() {
        if (!$this->has_license() || empty($this->license_data['expires_at'])) {
            return null;
        }

        $expires = strtotime($this->license_data['expires_at']);
        if (!$expires) {
            return null;
        }

        $diff = $expires - time();
        if ($diff <= 0) {
            return 0;
        }

        return ceil($diff / DAY_IN_SECONDS);
    }

    /**
     * Verifica si el usuario puede usar un módulo según su licencia
     *
     * @param string $module_slug Slug del módulo
     * @return bool
     */
    public function can_use_module($module_slug) {
        $current_plan = $this->get_current_plan();
        $plans = Flavor_License_Plans::get_instance();

        return $plans->is_module_in_plan($module_slug, $current_plan);
    }

    /**
     * Filtro para control de acceso a módulos
     *
     * @param bool $allowed Si el acceso está permitido
     * @param string $module_slug Slug del módulo
     * @return bool
     */
    public function filter_module_access($allowed, $module_slug) {
        // Si ya está denegado por otra razón, mantener
        if (!$allowed) {
            return false;
        }

        // Verificar licencia
        return $this->can_use_module($module_slug);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Activar licencia
     *
     * @return void
     */
    public function ajax_activate_license() {
        check_ajax_referer('flavor_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        $license_key = sanitize_text_field($_POST['license_key'] ?? '');

        if (empty($license_key)) {
            wp_send_json_error(__('Introduce una clave de licencia', 'flavor-chat-ia'));
        }

        $result = $this->activate_license($license_key);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Licencia activada correctamente', 'flavor-chat-ia'),
            'license' => $result,
            'plan'    => $result['plan'],
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
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        $result = $this->deactivate_license();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Licencia desactivada', 'flavor-chat-ia'),
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
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        $result = $this->verify_license_remote(true);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'active'  => $result,
            'license' => $this->get_license_data(),
        ]);
    }

    // =========================================================================
    // AVISOS DE ADMIN
    // =========================================================================

    /**
     * Muestra avisos sobre la licencia
     *
     * @return void
     */
    public function show_license_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'flavor') === false) {
            return;
        }

        // Sin licencia
        if (!$this->has_license()) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>' . esc_html__('Flavor Platform:', 'flavor-chat-ia') . '</strong> ';
            echo esc_html__('Activa tu licencia para desbloquear todos los módulos.', 'flavor-chat-ia');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=flavor-license')) . '">';
            echo esc_html__('Activar licencia', 'flavor-chat-ia') . '</a>';
            echo '</p></div>';
            return;
        }

        // Licencia expirada
        $days = $this->get_days_remaining();
        if ($days !== null && $days <= 0) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . esc_html__('Licencia expirada:', 'flavor-chat-ia') . '</strong> ';
            echo esc_html__('Tu licencia de Flavor Platform ha expirado. Renuévala para mantener el acceso a los módulos premium.', 'flavor-chat-ia');
            echo '</p></div>';
            return;
        }

        // Licencia por expirar (30 días)
        if ($days !== null && $days <= 30) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Licencia por expirar:', 'flavor-chat-ia') . '</strong> ';
            echo sprintf(
                esc_html__('Tu licencia expira en %d días. Renuévala para mantener el acceso.', 'flavor-chat-ia'),
                $days
            );
            echo '</p></div>';
        }
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Normaliza una clave de licencia
     *
     * @param string $key Clave a normalizar
     * @return string
     */
    private function normalize_license_key($key) {
        $key = strtoupper(trim($key));
        $key = preg_replace('/[^A-Z0-9]/', '', $key);

        // Añadir guiones si no los tiene
        if (strlen($key) === 16) {
            $key = implode('-', str_split($key, 4));
        }

        return $key;
    }

    /**
     * Valida el formato de una clave de licencia
     *
     * @param string $key Clave a validar
     * @return bool
     */
    private function is_valid_license_format($key) {
        return (bool) preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key);
    }

    /**
     * Obtiene la URL del sitio normalizada
     *
     * @return string
     */
    private function get_site_url() {
        $url = get_site_url();
        $url = preg_replace('#^https?://#', '', $url);
        $url = untrailingslashit($url);
        return strtolower($url);
    }

    /**
     * Obtiene verificación desde caché
     *
     * @return array|false
     */
    private function get_cached_verification() {
        $cache = get_transient(self::LICENSE_CACHE_OPTION);

        if (!$cache) {
            return false;
        }

        // Verificar TTL
        if (isset($cache['verified']) && (time() - $cache['verified']) > self::CACHE_TTL) {
            $this->clear_cache();
            return false;
        }

        return $cache;
    }

    /**
     * Guarda verificación en caché
     *
     * @param array $data Datos a cachear
     * @return void
     */
    private function set_verification_cache($data) {
        set_transient(self::LICENSE_CACHE_OPTION, $data, self::CACHE_TTL);
    }

    /**
     * Limpia el caché de verificación
     *
     * @return void
     */
    private function clear_cache() {
        delete_transient(self::LICENSE_CACHE_OPTION);
    }

    /**
     * Configura URL del servidor de licencias
     *
     * @param string $url Nueva URL
     * @return void
     */
    public function set_license_server($url) {
        $this->license_server = trailingslashit($url);
    }
}

// =========================================================================
// FUNCIONES HELPER GLOBALES
// =========================================================================

/**
 * Obtiene el gestor de licencias
 *
 * @return Flavor_License_Manager
 */
function flavor_license_manager() {
    return Flavor_License_Manager::get_instance();
}

/**
 * Verifica si la licencia está activa
 *
 * @return bool
 */
function flavor_has_active_license() {
    return Flavor_License_Manager::get_instance()->is_license_active();
}

/**
 * Obtiene el plan actual
 *
 * @return string
 */
function flavor_get_current_plan() {
    return Flavor_License_Manager::get_instance()->get_current_plan();
}

/**
 * Verifica si un módulo está disponible según la licencia
 *
 * @param string $module_slug Slug del módulo
 * @return bool
 */
function flavor_can_use_module($module_slug) {
    return Flavor_License_Manager::get_instance()->can_use_module($module_slug);
}
