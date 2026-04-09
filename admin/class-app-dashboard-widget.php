<?php
/**
 * Widget de Estadísticas de Apps para el Dashboard de WordPress
 *
 * Muestra métricas clave de las aplicaciones móviles en el dashboard principal.
 *
 * @package Flavor_Chat_IA
 * @since 3.4.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del Widget de Dashboard para Apps
 */
class Flavor_App_Dashboard_Widget {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_Dashboard_Widget|null
     */
    private static $instance = null;

    /**
     * Nombre del widget
     */
    const WIDGET_ID = 'flavor_app_stats_widget';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_Dashboard_Widget
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
        add_action('wp_dashboard_setup', [$this, 'register_widget']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_app_widget_refresh', [$this, 'ajax_refresh_stats']);
    }

    /**
     * Registrar el widget
     */
    public function register_widget() {
        wp_add_dashboard_widget(
            self::WIDGET_ID,
            '<span class="dashicons dashicons-smartphone"></span> ' . __('Apps Móviles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_widget'],
            [$this, 'configure_widget']
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(
            'flavor-app-widget',
            FLAVOR_CHAT_IA_URL . 'admin/css/app-dashboard-widget.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-app-widget',
            FLAVOR_CHAT_IA_URL . 'admin/js/app-dashboard-widget.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-app-widget', 'flavorAppWidget', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_app_widget'),
            'refreshInterval' => 60000, // 1 minuto
        ]);
    }

    /**
     * Renderizar el widget
     */
    public function render_widget() {
        $stats = $this->get_stats();
        ?>
        <div class="flavor-app-widget" id="flavor-app-widget">
            <!-- Métricas principales -->
            <div class="app-widget-metrics">
                <div class="metric-card">
                    <span class="metric-icon dashicons dashicons-groups"></span>
                    <div class="metric-content">
                        <span class="metric-value" data-metric="active_users">
                            <?php echo esc_html($stats['active_users']); ?>
                        </span>
                        <span class="metric-label"><?php _e('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <span class="metric-trend <?php echo $stats['users_trend'] >= 0 ? 'up' : 'down'; ?>">
                        <?php echo ($stats['users_trend'] >= 0 ? '+' : '') . $stats['users_trend']; ?>%
                    </span>
                </div>

                <div class="metric-card">
                    <span class="metric-icon dashicons dashicons-chart-line"></span>
                    <div class="metric-content">
                        <span class="metric-value" data-metric="sessions_today">
                            <?php echo esc_html($stats['sessions_today']); ?>
                        </span>
                        <span class="metric-label"><?php _e('Sesiones hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="metric-card">
                    <span class="metric-icon dashicons dashicons-download"></span>
                    <div class="metric-content">
                        <span class="metric-value" data-metric="total_downloads">
                            <?php echo esc_html($stats['total_downloads']); ?>
                        </span>
                        <span class="metric-label"><?php _e('Descargas totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gráfico mini -->
            <div class="app-widget-chart">
                <canvas id="app-widget-mini-chart" height="80"></canvas>
            </div>

            <!-- Versión actual -->
            <div class="app-widget-version">
                <div class="version-info">
                    <span class="version-platform">
                        <span class="dashicons dashicons-smartphone"></span>
                        Android
                    </span>
                    <span class="version-number"><?php echo esc_html($stats['android_version'] ?: 'N/A'); ?></span>
                </div>
                <div class="version-info">
                    <span class="version-platform">
                        <span class="dashicons dashicons-apple"></span>
                        iOS
                    </span>
                    <span class="version-number"><?php echo esc_html($stats['ios_version'] ?: 'N/A'); ?></span>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (!empty($stats['alerts'])): ?>
            <div class="app-widget-alerts">
                <?php foreach ($stats['alerts'] as $alert): ?>
                <div class="widget-alert <?php echo esc_attr($alert['type']); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($alert['icon']); ?>"></span>
                    <?php echo esc_html($alert['message']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Acciones rápidas -->
            <div class="app-widget-actions">
                <a href="<?php echo admin_url('admin.php?page=flavor-app-analytics'); ?>" class="button">
                    <?php _e('Ver Analytics', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=flavor-app-releases'); ?>" class="button">
                    <?php _e('Releases', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=flavor-apk-builder'); ?>" class="button button-primary">
                    <?php _e('Compilar APK', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <!-- Timestamp -->
            <div class="app-widget-footer">
                <span class="last-updated">
                    <?php _e('Actualizado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span id="widget-timestamp"><?php echo date_i18n('H:i'); ?></span>
                </span>
                <button type="button" class="refresh-btn" id="refresh-app-widget" title="<?php _e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Configurar el widget
     */
    public function configure_widget() {
        $options = get_option('flavor_app_widget_options', []);

        if (isset($_POST['flavor_app_widget_submit'])) {
            $options['show_chart'] = isset($_POST['show_chart']);
            $options['show_alerts'] = isset($_POST['show_alerts']);
            $options['auto_refresh'] = isset($_POST['auto_refresh']);
            update_option('flavor_app_widget_options', $options);
        }

        $show_chart = isset($options['show_chart']) ? $options['show_chart'] : true;
        $show_alerts = isset($options['show_alerts']) ? $options['show_alerts'] : true;
        $auto_refresh = isset($options['auto_refresh']) ? $options['auto_refresh'] : true;
        ?>
        <p>
            <label>
                <input type="checkbox" name="show_chart" <?php checked($show_chart); ?>>
                <?php _e('Mostrar gráfico de actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="show_alerts" <?php checked($show_alerts); ?>>
                <?php _e('Mostrar alertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="auto_refresh" <?php checked($auto_refresh); ?>>
                <?php _e('Auto-actualizar cada minuto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
        </p>
        <input type="hidden" name="flavor_app_widget_submit" value="1">
        <?php
    }

    /**
     * Obtener estadísticas
     *
     * @return array
     */
    private function get_stats() {
        global $wpdb;

        $stats = [
            'active_users' => 0,
            'users_trend' => 0,
            'sessions_today' => 0,
            'total_downloads' => 0,
            'android_version' => '',
            'ios_version' => '',
            'alerts' => [],
            'chart_data' => [],
        ];

        // Verificar si existen las tablas
        $analytics_table = $wpdb->prefix . 'flavor_app_analytics';
        $sessions_table = $wpdb->prefix . 'flavor_app_sessions';
        $releases_table = $wpdb->prefix . 'flavor_app_releases';

        // Usuarios activos (últimas 24h)
        if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table) {
            $stats['active_users'] = (int) $wpdb->get_var("
                SELECT COUNT(DISTINCT user_id)
                FROM $sessions_table
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");

            // Tendencia comparada con ayer
            $yesterday_users = (int) $wpdb->get_var("
                SELECT COUNT(DISTINCT user_id)
                FROM $sessions_table
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");

            if ($yesterday_users > 0) {
                $stats['users_trend'] = round((($stats['active_users'] - $yesterday_users) / $yesterday_users) * 100);
            }

            // Sesiones hoy
            $stats['sessions_today'] = (int) $wpdb->get_var("
                SELECT COUNT(*)
                FROM $sessions_table
                WHERE DATE(created_at) = CURDATE()
            ");

            // Datos para gráfico (últimos 7 días)
            $chart_data = $wpdb->get_results("
                SELECT DATE(created_at) as date, COUNT(DISTINCT user_id) as users
                FROM $sessions_table
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", ARRAY_A);

            $stats['chart_data'] = array_map(function($row) {
                return [
                    'date' => $row['date'],
                    'users' => (int) $row['users'],
                ];
            }, $chart_data);
        }

        // Descargas totales y versiones
        if ($wpdb->get_var("SHOW TABLES LIKE '$releases_table'") === $releases_table) {
            $stats['total_downloads'] = (int) $wpdb->get_var("
                SELECT SUM(downloads) FROM $releases_table
            ");

            // Última versión Android
            $stats['android_version'] = $wpdb->get_var("
                SELECT version FROM $releases_table
                WHERE platform = 'android' AND is_published = 1
                ORDER BY created_at DESC LIMIT 1
            ");

            // Última versión iOS
            $stats['ios_version'] = $wpdb->get_var("
                SELECT version FROM $releases_table
                WHERE platform = 'ios' AND is_published = 1
                ORDER BY created_at DESC LIMIT 1
            ");
        }

        // Generar alertas
        $stats['alerts'] = $this->generate_alerts($stats);

        return $stats;
    }

    /**
     * Generar alertas basadas en estadísticas
     *
     * @param array $stats
     * @return array
     */
    private function generate_alerts($stats) {
        $alerts = [];

        // Alerta si hay caída significativa de usuarios
        if ($stats['users_trend'] < -20) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'warning',
                'message' => sprintf(
                    __('Caída del %d%% en usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    abs($stats['users_trend'])
                ),
            ];
        }

        // Alerta si no hay versión publicada
        if (empty($stats['android_version']) && empty($stats['ios_version'])) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'info-outline',
                'message' => __('No hay releases publicados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar crashes recientes
        global $wpdb;
        $crashes_table = $wpdb->prefix . 'flavor_app_crashes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$crashes_table'") === $crashes_table) {
            $recent_crashes = (int) $wpdb->get_var("
                SELECT COUNT(*) FROM $crashes_table
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");

            if ($recent_crashes > 10) {
                $alerts[] = [
                    'type' => 'error',
                    'icon' => 'dismiss',
                    'message' => sprintf(
                        __('%d crashes en las últimas 24h', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $recent_crashes
                    ),
                ];
            }
        }

        return $alerts;
    }

    /**
     * AJAX: Refrescar estadísticas
     */
    public function ajax_refresh_stats() {
        check_ajax_referer('flavor_app_widget', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No autorizado');
        }

        $stats = $this->get_stats();

        wp_send_json_success([
            'stats' => $stats,
            'timestamp' => date_i18n('H:i'),
        ]);
    }
}

// Inicializar
Flavor_App_Dashboard_Widget::get_instance();
