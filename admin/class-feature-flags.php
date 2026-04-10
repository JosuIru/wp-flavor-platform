<?php
/**
 * Feature Flags Admin - Sistema de flags para apps
 *
 * Permite activar/desactivar funcionalidades en las apps desde el admin.
 *
 * @package Flavor_Platform
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar Feature Flags
 */
class Flavor_Feature_Flags {

    /**
     * Instancia singleton
     *
     * @var Flavor_Feature_Flags|null
     */
    private static $instance = null;

    /**
     * Nombre de la opción para flags
     */
    const OPTION_NAME = 'flavor_feature_flags';

    /**
     * Flags por defecto
     */
    private $default_flags = [
        'new_ui_enabled' => [
            'name' => 'Nueva interfaz de usuario',
            'description' => 'Habilita el nuevo diseño de la app',
            'enabled' => false,
            'rollout_percentage' => 0,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'dark_mode' => [
            'name' => 'Modo oscuro',
            'description' => 'Permite activar el modo oscuro en la app',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'biometric_login' => [
            'name' => 'Login biométrico',
            'description' => 'Permite login con huella/Face ID',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => '1.2.0',
        ],
        'offline_mode' => [
            'name' => 'Modo offline',
            'description' => 'Permite usar la app sin conexión',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'push_notifications' => [
            'name' => 'Notificaciones push',
            'description' => 'Habilita las notificaciones push',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'analytics_enabled' => [
            'name' => 'Analytics',
            'description' => 'Envía datos de uso anónimos',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'crash_reporting' => [
            'name' => 'Reporte de crashes',
            'description' => 'Envía reportes de errores automáticamente',
            'enabled' => true,
            'rollout_percentage' => 100,
            'platforms' => ['android', 'ios'],
            'min_version' => null,
        ],
        'in_app_review' => [
            'name' => 'Solicitar valoración',
            'description' => 'Muestra prompt para valorar la app',
            'enabled' => true,
            'rollout_percentage' => 50,
            'platforms' => ['android', 'ios'],
            'min_version' => '1.1.0',
        ],
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Feature_Flags
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
        add_action('admin_menu', [$this, 'add_admin_menu'], 30);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_feature_flags_save', [$this, 'ajax_save_flags']);
        add_action('wp_ajax_flavor_feature_flags_create', [$this, 'ajax_create_flag']);
        add_action('wp_ajax_flavor_feature_flags_delete', [$this, 'ajax_delete_flag']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Feature Flags', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Feature Flags', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-feature-flags',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'flavor-chat-ia_page_flavor-feature-flags') {
            return;
        }

        wp_enqueue_style(
            'flavor-feature-flags',
            FLAVOR_PLATFORM_URL . 'admin/css/feature-flags.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-feature-flags',
            FLAVOR_PLATFORM_URL . 'admin/js/feature-flags.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-feature-flags', 'flavorFeatureFlags', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_feature_flags'),
            'flags' => $this->get_all_flags(),
        ]);
    }

    /**
     * Renderizar página de administración
     */
    public function render_admin_page() {
        $flags = $this->get_all_flags();
        ?>
        <div class="wrap flavor-feature-flags-wrap">
            <h1>
                <span class="dashicons dashicons-flag"></span>
                <?php _e('Feature Flags', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <button type="button" class="page-title-action" id="add-flag-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Nuevo Flag', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </h1>

            <p class="description">
                <?php _e('Controla qué funcionalidades están disponibles en las aplicaciones móviles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flags-grid" id="flags-grid">
                <?php foreach ($flags as $key => $flag): ?>
                <div class="flag-card <?php echo $flag['enabled'] ? 'enabled' : 'disabled'; ?>" data-flag="<?php echo esc_attr($key); ?>">
                    <div class="flag-header">
                        <div class="flag-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" <?php checked($flag['enabled']); ?> data-flag="<?php echo esc_attr($key); ?>">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="flag-info">
                            <h3><?php echo esc_html($flag['name']); ?></h3>
                            <code><?php echo esc_html($key); ?></code>
                        </div>
                        <div class="flag-actions">
                            <button type="button" class="button-link edit-flag" data-flag="<?php echo esc_attr($key); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <?php if (!isset($this->default_flags[$key])): ?>
                            <button type="button" class="button-link delete-flag" data-flag="<?php echo esc_attr($key); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flag-body">
                        <p><?php echo esc_html($flag['description']); ?></p>

                        <div class="flag-meta">
                            <div class="meta-item">
                                <span class="label"><?php _e('Rollout:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="value"><?php echo esc_html($flag['rollout_percentage']); ?>%</span>
                            </div>
                            <div class="meta-item">
                                <span class="label"><?php _e('Plataformas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="value">
                                    <?php
                                    $platforms = $flag['platforms'] ?? ['android', 'ios'];
                                    echo implode(', ', array_map('strtoupper', $platforms));
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($flag['min_version'])): ?>
                            <div class="meta-item">
                                <span class="label"><?php _e('Versión mín:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="value">v<?php echo esc_html($flag['min_version']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flag-rollout">
                        <input type="range" min="0" max="100" value="<?php echo esc_attr($flag['rollout_percentage']); ?>"
                               class="rollout-slider" data-flag="<?php echo esc_attr($key); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modal para crear/editar flag -->
        <div id="flag-modal" class="flavor-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title"><?php _e('Nuevo Feature Flag', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <form id="flag-form">
                    <div class="modal-body">
                        <div class="form-row">
                            <label for="flag_key"><?php _e('Identificador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="flag_key" name="key" required
                                   pattern="[a-z_]+" placeholder="mi_feature_flag">
                            <p class="description"><?php _e('Solo letras minúsculas y guiones bajos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>

                        <div class="form-row">
                            <label for="flag_name"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="flag_name" name="name" required>
                        </div>

                        <div class="form-row">
                            <label for="flag_description"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <textarea id="flag_description" name="description" rows="2"></textarea>
                        </div>

                        <div class="form-row two-cols">
                            <div>
                                <label for="flag_rollout"><?php _e('Porcentaje de rollout', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="number" id="flag_rollout" name="rollout_percentage"
                                       min="0" max="100" value="0">
                            </div>
                            <div>
                                <label for="flag_min_version"><?php _e('Versión mínima', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="flag_min_version" name="min_version"
                                       placeholder="1.0.0">
                            </div>
                        </div>

                        <div class="form-row">
                            <label><?php _e('Plataformas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="platforms[]" value="android" checked>
                                    Android
                                </label>
                                <label>
                                    <input type="checkbox" name="platforms[]" value="ios" checked>
                                    iOS
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" onclick="closeModal()">
                            <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-app/v2', '/feature-flags', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_flags'],
            'permission_callback' => [$this, 'check_app_read_access'],
        ]);
    }

    /**
     * Verificar acceso de lectura para apps y admins.
     *
     * Acepta sesión WordPress, token Bearer móvil válido,
     * token de app registrado o secreto admin del sitio.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_app_read_access($request) {
        if (is_user_logged_in()) {
            return true;
        }

        if (class_exists('Chat_IA_Mobile_API')) {
            $mobile_api = Chat_IA_Mobile_API::get_instance();
            if ($mobile_api && $mobile_api->check_auth_token($request)) {
                return true;
            }
        }

        $app_token = $request->get_header('X-Flavor-Token');
        if (is_string($app_token) && '' !== $app_token) {
            $valid_tokens = get_option('flavor_apps_tokens', []);
            foreach ($valid_tokens as $token_data) {
                if (isset($token_data['token']) && hash_equals((string) $token_data['token'], $app_token)) {
                    return true;
                }
            }

            if (class_exists('Flavor_App_Config_Admin')) {
                $admin_secret = Flavor_App_Config_Admin::get_admin_site_secret();
                if (is_string($admin_secret) && '' !== $admin_secret && hash_equals($admin_secret, $app_token)) {
                    return true;
                }
            }
        }

        return new WP_Error(
            'rest_forbidden',
            __('Autenticación de app requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['status' => 401]
        );
    }

    /**
     * REST: Obtener flags para las apps
     */
    public function rest_get_flags($request) {
        $platform = $request->get_param('platform') ?? 'android';
        $app_version = $request->get_param('app_version') ?? '1.0.0';
        $user_id = $request->get_param('user_id');

        $flags = $this->get_all_flags();
        $result = [];

        foreach ($flags as $key => $flag) {
            // Verificar plataforma
            $platforms = $flag['platforms'] ?? ['android', 'ios'];
            if (!in_array($platform, $platforms)) {
                continue;
            }

            // Verificar versión mínima
            if (!empty($flag['min_version'])) {
                if (version_compare($app_version, $flag['min_version'], '<')) {
                    continue;
                }
            }

            // Calcular si está habilitado para este usuario
            $is_enabled = $flag['enabled'];

            if ($is_enabled && $flag['rollout_percentage'] < 100) {
                // Rollout gradual basado en user_id
                $hash = $user_id ? crc32($user_id . $key) : mt_rand(0, 99);
                $bucket = abs($hash) % 100;
                $is_enabled = $bucket < $flag['rollout_percentage'];
            }

            $result[$key] = $is_enabled;
        }

        return rest_ensure_response([
            'flags' => $result,
            'timestamp' => current_time('c'),
        ]);
    }

    /**
     * Obtener todos los flags
     *
     * @return array
     */
    public function get_all_flags() {
        $saved = get_option(self::OPTION_NAME, []);
        return array_merge($this->default_flags, $saved);
    }

    /**
     * Obtener un flag específico
     *
     * @param string $key
     * @return array|null
     */
    public function get_flag($key) {
        $flags = $this->get_all_flags();
        return $flags[$key] ?? null;
    }

    /**
     * Verificar si un flag está habilitado
     *
     * @param string $key
     * @param array $context Contexto (platform, version, user_id)
     * @return bool
     */
    public function is_enabled($key, $context = []) {
        $flag = $this->get_flag($key);

        if (!$flag || !$flag['enabled']) {
            return false;
        }

        // Verificar plataforma
        if (!empty($context['platform'])) {
            $platforms = $flag['platforms'] ?? ['android', 'ios'];
            if (!in_array($context['platform'], $platforms)) {
                return false;
            }
        }

        // Verificar versión
        if (!empty($context['version']) && !empty($flag['min_version'])) {
            if (version_compare($context['version'], $flag['min_version'], '<')) {
                return false;
            }
        }

        // Rollout gradual
        if ($flag['rollout_percentage'] < 100 && !empty($context['user_id'])) {
            $hash = crc32($context['user_id'] . $key);
            $bucket = abs($hash) % 100;
            return $bucket < $flag['rollout_percentage'];
        }

        return $flag['rollout_percentage'] > 0;
    }

    /**
     * AJAX: Guardar flags
     */
    public function ajax_save_flags() {
        check_ajax_referer('flavor_feature_flags', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $key = sanitize_key($_POST['flag_key'] ?? '');
        $data = [
            'enabled' => isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false,
            'rollout_percentage' => absint($_POST['rollout_percentage'] ?? 0),
        ];

        $flags = get_option(self::OPTION_NAME, []);

        // Si es un flag por defecto, solo guardar cambios
        if (isset($this->default_flags[$key])) {
            $flags[$key] = array_merge($this->default_flags[$key], $data);
        } elseif (isset($flags[$key])) {
            $flags[$key] = array_merge($flags[$key], $data);
        }

        update_option(self::OPTION_NAME, $flags);

        wp_send_json_success(['flag' => $key, 'data' => $flags[$key] ?? $data]);
    }

    /**
     * AJAX: Crear flag
     */
    public function ajax_create_flag() {
        check_ajax_referer('flavor_feature_flags', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $key = sanitize_key($_POST['key'] ?? '');

        if (empty($key)) {
            wp_send_json_error(__('Identificador requerido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $flags = get_option(self::OPTION_NAME, []);

        if (isset($flags[$key]) || isset($this->default_flags[$key])) {
            wp_send_json_error(__('Ya existe un flag con ese identificador', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $platforms = isset($_POST['platforms']) ? (array) $_POST['platforms'] : ['android', 'ios'];

        $flags[$key] = [
            'name' => sanitize_text_field($_POST['name'] ?? $key),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'enabled' => false,
            'rollout_percentage' => absint($_POST['rollout_percentage'] ?? 0),
            'platforms' => array_map('sanitize_key', $platforms),
            'min_version' => sanitize_text_field($_POST['min_version'] ?? ''),
        ];

        update_option(self::OPTION_NAME, $flags);

        wp_send_json_success(['flag' => $key, 'data' => $flags[$key]]);
    }

    /**
     * AJAX: Eliminar flag
     */
    public function ajax_delete_flag() {
        check_ajax_referer('flavor_feature_flags', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $key = sanitize_key($_POST['flag_key'] ?? '');

        // No permitir eliminar flags por defecto
        if (isset($this->default_flags[$key])) {
            wp_send_json_error(__('No se puede eliminar un flag del sistema', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $flags = get_option(self::OPTION_NAME, []);
        unset($flags[$key]);
        update_option(self::OPTION_NAME, $flags);

        wp_send_json_success();
    }
}

// Inicializar
Flavor_Feature_Flags::get_instance();
