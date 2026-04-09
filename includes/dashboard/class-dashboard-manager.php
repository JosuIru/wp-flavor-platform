<?php
/**
 * Dashboard Manager - Sistema de Dashboard con Widgets
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dashboard_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Widgets registrados
     */
    private $widgets = [];

    /**
     * Obtener instancia
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
        $this->init_default_widgets();
        $this->init_hooks();
    }

    /**
     * Inicializar widgets por defecto
     */
    private function init_default_widgets() {
        $this->widgets = [
            'chat_stats' => [
                'title' => 'Estadísticas del Chat',
                'description' => 'Resumen de conversaciones y mensajes',
                'icon' => 'dashicons-format-chat',
                'category' => 'analytics',
                'size' => 'medium',
                'callback' => [$this, 'render_chat_stats_widget'],
            ],
            'recent_conversations' => [
                'title' => 'Conversaciones Recientes',
                'description' => 'Últimas conversaciones del chat',
                'icon' => 'dashicons-admin-comments',
                'category' => 'activity',
                'size' => 'large',
                'callback' => [$this, 'render_recent_conversations_widget'],
            ],
            'page_builder_stats' => [
                'title' => 'Page Builder',
                'description' => 'Páginas creadas con el builder',
                'icon' => 'dashicons-layout',
                'category' => 'content',
                'size' => 'small',
                'callback' => [$this, 'render_page_builder_widget'],
            ],
            'module_status' => [
                'title' => 'Estado de Módulos',
                'description' => 'Módulos activos e inactivos',
                'icon' => 'dashicons-admin-plugins',
                'category' => 'system',
                'size' => 'medium',
                'callback' => [$this, 'render_module_status_widget'],
            ],
            'api_usage' => [
                'title' => 'Uso de API',
                'description' => 'Consumo de tokens y llamadas API',
                'icon' => 'dashicons-cloud',
                'category' => 'analytics',
                'size' => 'medium',
                'callback' => [$this, 'render_api_usage_widget'],
            ],
            'quick_actions' => [
                'title' => 'Acciones Rápidas',
                'description' => 'Accesos directos a funciones comunes',
                'icon' => 'dashicons-admin-tools',
                'category' => 'tools',
                'size' => 'small',
                'callback' => [$this, 'render_quick_actions_widget'],
            ],
            'notifications' => [
                'title' => 'Notificaciones',
                'description' => 'Notificaciones pendientes',
                'icon' => 'dashicons-bell',
                'category' => 'activity',
                'size' => 'medium',
                'callback' => [$this, 'render_notifications_widget'],
            ],
            'activity_chart' => [
                'title' => 'Gráfico de Actividad',
                'description' => 'Actividad de los últimos 7 días',
                'icon' => 'dashicons-chart-area',
                'category' => 'analytics',
                'size' => 'large',
                'callback' => [$this, 'render_activity_chart_widget'],
            ],
        ];

        $this->widgets = apply_filters('flavor_dashboard_widgets', $this->widgets);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // NOTA: El menú principal se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_dashboard_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_flavor_save_dashboard_layout', [$this, 'ajax_save_dashboard_layout']);
        add_action('wp_ajax_flavor_get_widget_data', [$this, 'ajax_get_widget_data']);
    }

    /**
     * Añadir página de dashboard
     */
    public function add_dashboard_page() {
        add_menu_page(
            'Flavor Platform',
            'Flavor Platform',
            'edit_posts',
            'flavor-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-superhero',
            3
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_flavor-dashboard') {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . "assets/css/layouts/dashboard{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . "assets/js/dashboard{$sufijo_asset}.js",
            ['jquery', 'wp-util'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_localize_script('flavor-dashboard-widgets', 'flavorDashboard', [
            'nonce' => wp_create_nonce('flavor_dashboard_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'widgets' => $this->get_widgets_info(),
            'userLayout' => $this->get_user_layout(),
        ]);
    }

    /**
     * Renderizar página de dashboard
     */
    public function render_dashboard_page() {
        $target_url = admin_url('admin.php?page=flavor-dashboard');

        if (class_exists('Flavor_Chat_Helpers')) {
            Flavor_Chat_Helpers::safe_redirect($target_url);
            exit;
        }

        if (!headers_sent()) {
            wp_safe_redirect($target_url);
            exit;
        }

        ?>
        <div class="wrap flavor-dashboard-wrap">
            <h1><?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <div class="notice notice-info">
                <p><?php esc_html_e('Esta pantalla legacy de dashboard ha sido absorbida por la portada principal del admin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p>
                    <a href="<?php echo esc_url($target_url); ?>" class="button button-primary">
                        <?php esc_html_e('Ir a Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener información de widgets
     */
    public function get_widgets_info() {
        $info = [];
        foreach ($this->widgets as $widget_id => $widget) {
            $info[$widget_id] = [
                'id' => $widget_id,
                'title' => $widget['title'],
                'description' => $widget['description'],
                'icon' => $widget['icon'],
                'category' => $widget['category'],
                'size' => $widget['size'],
            ];
        }
        return $info;
    }

    /**
     * Obtener layout del usuario
     */
    public function get_user_layout() {
        $user_id = get_current_user_id();
        $layout = get_user_meta($user_id, 'flavor_dashboard_layout', true);

        if (empty($layout)) {
            // Layout por defecto
            $layout = [
                'chat_stats',
                'activity_chart',
                'recent_conversations',
                'quick_actions',
                'module_status',
                'api_usage',
            ];
        }

        return $layout;
    }

    /**
     * AJAX: Obtener datos del dashboard
     * Incluye caché transient para evitar re-renderizar widgets frecuentemente.
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('flavor_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $user_id = get_current_user_id();
        $layout = $this->get_user_layout();
        $force_refresh = !empty($_POST['refresh']);

        // Generar clave de caché única por usuario y layout
        $cache_key = 'flavor_dash_' . $user_id . '_' . md5(wp_json_encode($layout));

        // Intentar obtener del caché (si no se fuerza refresh)
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                wp_send_json_success($cached);
                return;
            }
        }

        // Renderizar widgets (operación costosa)
        $widgets_data = [];
        foreach ($layout as $widget_id) {
            if (isset($this->widgets[$widget_id])) {
                ob_start();
                call_user_func($this->widgets[$widget_id]['callback']);
                $widgets_data[$widget_id] = [
                    'id' => $widget_id,
                    'title' => $this->widgets[$widget_id]['title'],
                    'icon' => $this->widgets[$widget_id]['icon'],
                    'size' => $this->widgets[$widget_id]['size'],
                    'html' => ob_get_clean(),
                ];
            }
        }

        $response_data = [
            'layout' => $layout,
            'widgets' => $widgets_data,
            'cached_at' => current_time('mysql'),
        ];

        // Cachear por 5 minutos
        set_transient($cache_key, $response_data, 5 * MINUTE_IN_SECONDS);

        wp_send_json_success($response_data);
    }

    /**
     * Invalida el caché del dashboard para todos los usuarios.
     * Llamar cuando cambien datos que afecten los widgets.
     */
    public static function invalidate_dashboard_cache() {
        global $wpdb;

        // Eliminar todos los transients de dashboard
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_flavor_dash_%'
            OR option_name LIKE '_transient_timeout_flavor_dash_%'"
        );
    }

    /**
     * AJAX: Guardar layout del dashboard
     */
    public function ajax_save_dashboard_layout() {
        check_ajax_referer('flavor_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $layout = array_map('sanitize_text_field', $_POST['layout'] ?? []);

        // Validar que todos los widgets existen
        $valid_layout = [];
        foreach ($layout as $widget_id) {
            if (isset($this->widgets[$widget_id])) {
                $valid_layout[] = $widget_id;
            }
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'flavor_dashboard_layout', $valid_layout);

        // Invalidar caché del usuario actual
        $cache_key = 'flavor_dash_' . $user_id . '_' . md5(wp_json_encode($valid_layout));
        delete_transient($cache_key);

        wp_send_json_success(['message' => __('Layout guardado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener datos de un widget específico
     */
    public function ajax_get_widget_data() {
        check_ajax_referer('flavor_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');

        if (!isset($this->widgets[$widget_id])) {
            wp_send_json_error(['message' => __('Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        ob_start();
        call_user_func($this->widgets[$widget_id]['callback']);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Widget: Estadísticas del Chat
     */
    public function render_chat_stats_widget() {
        global $wpdb;

        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';
        $table_messages = $wpdb->prefix . 'flavor_chat_messages';

        // Obtener todas las estadísticas en una sola consulta optimizada
        $today = current_time('Y-m-d');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(CASE WHEN DATE(c.started_at) = %s THEN 1 ELSE 0 END) as today_conversations,
                COUNT(*) as total_conversations,
                SUM(CASE WHEN c.escalated = 1 THEN 1 ELSE 0 END) as total_escalations,
                (SELECT COUNT(*) FROM {$table_messages} m
                 JOIN {$table_conversations} c2 ON m.conversation_id = c2.id
                 WHERE DATE(c2.started_at) = %s) as today_messages
            FROM {$table_conversations} c",
            $today,
            $today
        ));

        $today_conversations = $stats->today_conversations ?? 0;
        $today_messages = $stats->today_messages ?? 0;
        $total_conversations = $stats->total_conversations ?? 0;
        $total_escalations = $stats->total_escalations ?? 0;

        ?>
        <div class="flavor-widget-stats-grid">
            <div class="flavor-stat-item">
                <span class="flavor-stat-icon dashicons dashicons-format-chat"></span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-value"><?php echo intval($today_conversations); ?></span>
                    <span class="flavor-stat-label">Conversaciones Hoy</span>
                </div>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-icon dashicons dashicons-admin-comments"></span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-value"><?php echo intval($today_messages); ?></span>
                    <span class="flavor-stat-label">Mensajes Hoy</span>
                </div>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-icon dashicons dashicons-chart-bar"></span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-value"><?php echo intval($total_conversations); ?></span>
                    <span class="flavor-stat-label">Total Conversaciones</span>
                </div>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-icon dashicons dashicons-businessman"></span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-value"><?php echo intval($total_escalations); ?></span>
                    <span class="flavor-stat-label">Escalaciones</span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Widget: Conversaciones Recientes
     */
    public function render_recent_conversations_widget() {
        global $wpdb;

        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';

        $recent = $wpdb->get_results(
            "SELECT * FROM {$table_conversations}
            ORDER BY started_at DESC
            LIMIT 5"
        );

        ?>
        <div class="flavor-recent-list">
            <?php if (empty($recent)) : ?>
                <p class="flavor-empty">No hay conversaciones recientes</p>
            <?php else : ?>
                <?php foreach ($recent as $conv) : ?>
                    <div class="flavor-recent-item">
                        <div class="flavor-recent-icon">
                            <span class="dashicons dashicons-format-chat"></span>
                        </div>
                        <div class="flavor-recent-content">
                            <strong>Sesión: <?php echo esc_html(substr($conv->session_id, 0, 8)); ?>...</strong>
                            <span class="flavor-recent-meta">
                                <?php echo intval($conv->message_count); ?> mensajes
                                <?php if ($conv->escalated) : ?>
                                    <span class="flavor-badge flavor-badge-warning">Escalado</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flavor-recent-time">
                            <?php echo human_time_diff(strtotime($conv->started_at)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Widget: Page Builder Stats
     */
    public function render_page_builder_widget() {
        $pages_with_builder = get_posts([
            'post_type' => 'page',
            'meta_key' => '_flavor_page_layout',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        $count = count($pages_with_builder);
        ?>
        <div class="flavor-widget-center">
            <div class="flavor-big-number"><?php echo intval($count); ?></div>
            <p>Páginas creadas con Page Builder</p>
            <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button">
                Ver páginas
            </a>
        </div>
        <?php
    }

    /**
     * Widget: Estado de Módulos
     */
    public function render_module_status_widget() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $settings['active_modules'] ?? [];

        $available_modules = [
            'woocommerce' => 'WooCommerce',
            'carpooling' => 'Carpooling',
            'banco-tiempo' => 'Banco de Tiempo',
            'grupos-consumo' => 'Grupos de Consumo',
            'eventos' => 'Eventos',
            'directorio' => 'Directorio',
        ];

        ?>
        <div class="flavor-module-list">
            <?php foreach ($available_modules as $module_id => $module_name) :
                $is_active = in_array($module_id, $active_modules);
            ?>
                <div class="flavor-module-item">
                    <span class="flavor-module-status <?php echo $is_active ? 'active' : 'inactive'; ?>"></span>
                    <span class="flavor-module-name"><?php echo esc_html($module_name); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo admin_url('admin.php?page=flavor-module-dashboards'); ?>" class="flavor-link-small">
            Gestionar módulos
        </a>
        <?php
    }

    /**
     * Widget: Uso de API
     */
    public function render_api_usage_widget() {
        global $wpdb;

        $table_messages = $wpdb->prefix . 'flavor_chat_messages';

        // Obtener tokens y llamadas API en una sola consulta
        $month_start = current_time('Y-m-01');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(tokens_used), 0) as tokens_used,
                SUM(CASE WHEN role = 'assistant' THEN 1 ELSE 0 END) as api_calls
            FROM {$table_messages}
            WHERE created_at >= %s",
            $month_start
        ));

        $tokens_used = $stats->tokens_used ?? 0;
        $api_calls = $stats->api_calls ?? 0;

        ?>
        <div class="flavor-api-stats">
            <div class="flavor-api-stat">
                <span class="flavor-api-value"><?php echo number_format(intval($tokens_used)); ?></span>
                <span class="flavor-api-label">Tokens este mes</span>
            </div>
            <div class="flavor-api-stat">
                <span class="flavor-api-value"><?php echo number_format(intval($api_calls)); ?></span>
                <span class="flavor-api-label">Llamadas API</span>
            </div>
        </div>
        <?php $settings = get_option('flavor_chat_ia_settings', []); ?>
        <div class="flavor-api-provider">
            <small>Proveedor activo: <?php echo esc_html(ucfirst($settings['active_provider'] ?? 'Claude')); ?></small>
        </div>
        <?php
    }

    /**
     * Widget: Acciones Rápidas
     */
    public function render_quick_actions_widget() {
        ?>
        <div class="flavor-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=flavor-platform-settings'); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-admin-settings"></span>
                <span>Configuración</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=flavor-platform-settings'); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-chart-area"></span>
                <span>Analíticas</span>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-plus"></span>
                <span>Nueva Página</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=flavor-platform-escalations'); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-phone"></span>
                <span>Escalaciones</span>
            </a>
        </div>
        <?php
    }

    /**
     * Widget: Notificaciones
     */
    public function render_notifications_widget() {
        if (!class_exists('Flavor_Notification_Manager')) {
            echo '<p class="flavor-empty">Sistema de notificaciones no disponible</p>';
            return;
        }

        $notifications = Flavor_Notification_Manager::get_instance()->get_user_notifications(
            get_current_user_id(),
            ['unread_only' => true, 'limit' => 5]
        );

        ?>
        <div class="flavor-notification-list">
            <?php if (empty($notifications)) : ?>
                <p class="flavor-empty">No hay notificaciones nuevas</p>
            <?php else : ?>
                <?php foreach ($notifications as $notif) : ?>
                    <div class="flavor-notification-item">
                        <strong><?php echo esc_html($notif->title); ?></strong>
                        <p><?php echo esc_html(wp_trim_words($notif->message, 10)); ?></p>
                        <small><?php echo human_time_diff(strtotime($notif->created_at)); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Widget: Gráfico de Actividad
     */
    public function render_activity_chart_widget() {
        global $wpdb;

        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';

        // Obtener datos de los últimos 7 días en una sola consulta
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $end_date = current_time('Y-m-d');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(started_at) as conv_date, COUNT(*) as conv_count
            FROM {$table_conversations}
            WHERE DATE(started_at) BETWEEN %s AND %s
            GROUP BY DATE(started_at)
            ORDER BY conv_date ASC",
            $start_date,
            $end_date
        ));

        // Indexar resultados por fecha para acceso rápido
        $counts_by_date = [];
        foreach ($results as $row) {
            $counts_by_date[$row->conv_date] = intval($row->conv_count);
        }

        // Generar array de 7 días (incluyendo días sin actividad)
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $chart_data[] = [
                'date' => date_i18n('D j', strtotime($date)),
                'count' => $counts_by_date[$date] ?? 0,
            ];
        }

        ?>
        <div class="flavor-chart-container">
            <canvas id="flavor-activity-chart" data-chart='<?php echo wp_json_encode($chart_data); ?>'></canvas>
        </div>
        <?php
    }

    /**
     * Registrar un widget personalizado
     *
     * @param string $widget_id
     * @param array $args
     */
    public function register_widget($widget_id, $args) {
        $defaults = [
            'title' => 'Widget',
            'description' => '',
            'icon' => 'dashicons-admin-generic',
            'category' => 'tools',
            'size' => 'medium',
            'callback' => null,
        ];

        $this->widgets[$widget_id] = wp_parse_args($args, $defaults);
    }
}
