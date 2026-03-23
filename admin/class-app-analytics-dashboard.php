<?php
/**
 * Dashboard de Analytics para Apps Móviles
 *
 * Panel de administración que muestra métricas de uso de las apps:
 * - Descargas y usuarios activos
 * - Módulos más utilizados
 * - Eventos y sesiones
 * - Crashes y errores
 * - Tendencias temporales
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_App_Analytics_Dashboard {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = 'flavor-app-analytics';

    /**
     * Tabla de analytics
     */
    private $table_name;

    /**
     * Tabla de sesiones
     */
    private $sessions_table;

    /**
     * Tabla de eventos
     */
    private $events_table;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_app_analytics';
        $this->sessions_table = $wpdb->prefix . 'flavor_app_sessions';
        $this->events_table = $wpdb->prefix . 'flavor_app_events';

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_get_app_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_flavor_export_app_analytics', array($this, 'ajax_export_analytics'));
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Agregar página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Analytics Apps', 'flavor-chat-ia'),
            __('Analytics Apps', 'flavor-chat-ia'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_dashboard')
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        // CSS del dashboard
        wp_enqueue_style(
            'flavor-app-analytics',
            FLAVOR_CHAT_IA_URL . 'admin/css/app-analytics.css',
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        // JS del dashboard
        wp_enqueue_script(
            'flavor-app-analytics',
            FLAVOR_CHAT_IA_URL . 'admin/js/app-analytics.js',
            array('jquery', 'chartjs'),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-app-analytics', 'flavorAppAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_app_analytics'),
            'i18n' => array(
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al cargar datos', 'flavor-chat-ia'),
                'noData' => __('Sin datos disponibles', 'flavor-chat-ia'),
                'users' => __('Usuarios', 'flavor-chat-ia'),
                'sessions' => __('Sesiones', 'flavor-chat-ia'),
                'events' => __('Eventos', 'flavor-chat-ia'),
                'crashes' => __('Crashes', 'flavor-chat-ia'),
            )
        ));
    }

    /**
     * Renderizar dashboard
     */
    public function render_dashboard() {
        $this->ensure_tables_exist();
        ?>
        <div class="wrap flavor-app-analytics-wrap">
            <h1>
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('Analytics de Apps Móviles', 'flavor-chat-ia'); ?>
            </h1>

            <!-- Filtros de período -->
            <div class="analytics-filters">
                <div class="filter-group">
                    <label><?php _e('Período:', 'flavor-chat-ia'); ?></label>
                    <select id="analytics-period">
                        <option value="7d"><?php _e('Últimos 7 días', 'flavor-chat-ia'); ?></option>
                        <option value="30d" selected><?php _e('Últimos 30 días', 'flavor-chat-ia'); ?></option>
                        <option value="90d"><?php _e('Últimos 90 días', 'flavor-chat-ia'); ?></option>
                        <option value="365d"><?php _e('Último año', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php _e('App:', 'flavor-chat-ia'); ?></label>
                    <select id="analytics-app">
                        <option value="all"><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                        <option value="client"><?php _e('App Cliente', 'flavor-chat-ia'); ?></option>
                        <option value="admin"><?php _e('App Admin', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php _e('Plataforma:', 'flavor-chat-ia'); ?></label>
                    <select id="analytics-platform">
                        <option value="all"><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                        <option value="android"><?php _e('Android', 'flavor-chat-ia'); ?></option>
                        <option value="ios"><?php _e('iOS', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <button type="button" id="refresh-analytics" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Actualizar', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" id="export-analytics" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Exportar', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="analytics-summary-cards">
                <div class="summary-card" data-metric="total_users">
                    <div class="card-icon users">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Usuarios Totales', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="total-users">-</div>
                        <div class="card-trend" id="total-users-trend"></div>
                    </div>
                </div>

                <div class="summary-card" data-metric="active_users">
                    <div class="card-icon active">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="active-users">-</div>
                        <div class="card-trend" id="active-users-trend"></div>
                    </div>
                </div>

                <div class="summary-card" data-metric="sessions">
                    <div class="card-icon sessions">
                        <span class="dashicons dashicons-smartphone"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Sesiones', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="total-sessions">-</div>
                        <div class="card-trend" id="sessions-trend"></div>
                    </div>
                </div>

                <div class="summary-card" data-metric="avg_duration">
                    <div class="card-icon duration">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Duración Media', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="avg-duration">-</div>
                        <div class="card-trend" id="duration-trend"></div>
                    </div>
                </div>

                <div class="summary-card" data-metric="events">
                    <div class="card-icon events">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Eventos', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="total-events">-</div>
                        <div class="card-trend" id="events-trend"></div>
                    </div>
                </div>

                <div class="summary-card warning" data-metric="crashes">
                    <div class="card-icon crashes">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="card-content">
                        <h3><?php _e('Crashes', 'flavor-chat-ia'); ?></h3>
                        <div class="card-value" id="total-crashes">-</div>
                        <div class="card-trend" id="crashes-trend"></div>
                    </div>
                </div>
            </div>

            <!-- Gráficos principales -->
            <div class="analytics-charts-row">
                <div class="chart-container large">
                    <h3><?php _e('Usuarios y Sesiones', 'flavor-chat-ia'); ?></h3>
                    <canvas id="users-sessions-chart"></canvas>
                </div>
            </div>

            <div class="analytics-charts-row two-cols">
                <div class="chart-container">
                    <h3><?php _e('Módulos Más Utilizados', 'flavor-chat-ia'); ?></h3>
                    <canvas id="modules-chart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('Distribución por Plataforma', 'flavor-chat-ia'); ?></h3>
                    <canvas id="platform-chart"></canvas>
                </div>
            </div>

            <div class="analytics-charts-row two-cols">
                <div class="chart-container">
                    <h3><?php _e('Eventos por Tipo', 'flavor-chat-ia'); ?></h3>
                    <canvas id="events-chart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('Versiones de App', 'flavor-chat-ia'); ?></h3>
                    <canvas id="versions-chart"></canvas>
                </div>
            </div>

            <!-- Tablas de detalle -->
            <div class="analytics-tables-row">
                <div class="table-container">
                    <h3><?php _e('Top Pantallas Visitadas', 'flavor-chat-ia'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" id="top-screens-table">
                        <thead>
                            <tr>
                                <th><?php _e('Pantalla', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Visitas', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Usuarios', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Tiempo Medio', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-container">
                    <h3><?php _e('Crashes Recientes', 'flavor-chat-ia'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" id="recent-crashes-table">
                        <thead>
                            <tr>
                                <th><?php _e('Error', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Versión', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Dispositivo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dispositivos y ubicaciones -->
            <div class="analytics-tables-row">
                <div class="table-container">
                    <h3><?php _e('Top Dispositivos', 'flavor-chat-ia'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" id="top-devices-table">
                        <thead>
                            <tr>
                                <th><?php _e('Modelo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Versión OS', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Usuarios', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('%', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-container">
                    <h3><?php _e('Retención de Usuarios', 'flavor-chat-ia'); ?></h3>
                    <div id="retention-heatmap" class="retention-grid">
                        <div class="loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Real-time -->
            <div class="analytics-realtime-section">
                <h3>
                    <span class="realtime-indicator"></span>
                    <?php _e('Actividad en Tiempo Real', 'flavor-chat-ia'); ?>
                </h3>
                <div class="realtime-metrics">
                    <div class="realtime-metric">
                        <span class="metric-value" id="realtime-users">0</span>
                        <span class="metric-label"><?php _e('Usuarios ahora', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="realtime-metric">
                        <span class="metric-value" id="realtime-events">0</span>
                        <span class="metric-label"><?php _e('Eventos/min', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="realtime-activity" id="realtime-feed">
                        <!-- Feed de actividad en tiempo real -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Asegurar que las tablas existen
     */
    private function ensure_tables_exist() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla principal de analytics
        $sql_analytics = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_id varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            app_type varchar(20) NOT NULL DEFAULT 'client',
            app_version varchar(20) NOT NULL,
            platform varchar(20) NOT NULL,
            os_version varchar(20) DEFAULT NULL,
            device_model varchar(100) DEFAULT NULL,
            screen_resolution varchar(20) DEFAULT NULL,
            language varchar(10) DEFAULT NULL,
            first_seen datetime NOT NULL,
            last_seen datetime NOT NULL,
            total_sessions int(11) DEFAULT 0,
            total_events int(11) DEFAULT 0,
            total_duration int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY device_id (device_id),
            KEY user_id (user_id),
            KEY app_type (app_type),
            KEY platform (platform),
            KEY last_seen (last_seen)
        ) $charset_collate;";

        // Tabla de sesiones
        $sql_sessions = "CREATE TABLE IF NOT EXISTS {$this->sessions_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            device_id varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            app_type varchar(20) NOT NULL DEFAULT 'client',
            started_at datetime NOT NULL,
            ended_at datetime DEFAULT NULL,
            duration int(11) DEFAULT 0,
            screens_viewed int(11) DEFAULT 0,
            events_count int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY device_id (device_id),
            KEY user_id (user_id),
            KEY started_at (started_at),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Tabla de eventos
        $sql_events = "CREATE TABLE IF NOT EXISTS {$this->events_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id varchar(64) NOT NULL,
            session_id varchar(64) DEFAULT NULL,
            device_id varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            event_type varchar(50) NOT NULL,
            event_name varchar(100) NOT NULL,
            screen_name varchar(100) DEFAULT NULL,
            module varchar(50) DEFAULT NULL,
            properties longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY session_id (session_id),
            KEY device_id (device_id),
            KEY event_type (event_type),
            KEY module (module),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_analytics);
        dbDelta($sql_sessions);
        dbDelta($sql_events);
    }

    /**
     * AJAX: Obtener analytics
     */
    public function ajax_get_analytics() {
        check_ajax_referer('flavor_app_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $period = sanitize_text_field($_POST['period'] ?? '30d');
        $app_type = sanitize_text_field($_POST['app_type'] ?? 'all');
        $platform = sanitize_text_field($_POST['platform'] ?? 'all');

        $data = array(
            'summary' => $this->get_summary_metrics($period, $app_type, $platform),
            'timeline' => $this->get_timeline_data($period, $app_type, $platform),
            'modules' => $this->get_modules_usage($period, $app_type, $platform),
            'platforms' => $this->get_platform_distribution($period, $app_type),
            'events' => $this->get_events_by_type($period, $app_type, $platform),
            'versions' => $this->get_version_distribution($period, $app_type, $platform),
            'screens' => $this->get_top_screens($period, $app_type, $platform),
            'crashes' => $this->get_recent_crashes($period, $app_type, $platform),
            'devices' => $this->get_top_devices($period, $app_type, $platform),
            'retention' => $this->get_retention_data($period, $app_type, $platform),
            'realtime' => $this->get_realtime_data(),
        );

        wp_send_json_success($data);
    }

    /**
     * Obtener métricas de resumen
     */
    private function get_summary_metrics($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $prev_date_from = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));

        $where_current = $this->build_where_clause($date_from, null, $app_type, $platform);
        $where_previous = $this->build_where_clause($prev_date_from, $date_from, $app_type, $platform);

        // Usuarios totales
        $total_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT device_id) FROM {$this->table_name} WHERE 1=1 {$where_current}"
        );
        $prev_total_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT device_id) FROM {$this->table_name} WHERE last_seen >= '{$prev_date_from}' AND last_seen < '{$date_from}'"
        );

        // Usuarios activos (última semana)
        $active_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        $active_users = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE last_seen >= '{$active_date}'"
        );

        // Sesiones
        $total_sessions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->sessions_table} WHERE started_at >= '{$date_from}'"
        );
        $prev_sessions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->sessions_table} WHERE started_at >= '{$prev_date_from}' AND started_at < '{$date_from}'"
        );

        // Duración media
        $avg_duration = $wpdb->get_var(
            "SELECT AVG(duration) FROM {$this->sessions_table} WHERE started_at >= '{$date_from}' AND duration > 0"
        );

        // Eventos
        $total_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->events_table} WHERE created_at >= '{$date_from}'"
        );
        $prev_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->events_table} WHERE created_at >= '{$prev_date_from}' AND created_at < '{$date_from}'"
        );

        // Crashes
        $total_crashes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->events_table} WHERE event_type = 'crash' AND created_at >= '{$date_from}'"
        );
        $prev_crashes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->events_table} WHERE event_type = 'crash' AND created_at >= '{$prev_date_from}' AND created_at < '{$date_from}'"
        );

        return array(
            'total_users' => (int) $total_users ?: 0,
            'total_users_trend' => $this->calculate_trend($total_users, $prev_total_users),
            'active_users' => (int) $active_users ?: 0,
            'active_users_trend' => 0,
            'total_sessions' => (int) $total_sessions ?: 0,
            'sessions_trend' => $this->calculate_trend($total_sessions, $prev_sessions),
            'avg_duration' => round($avg_duration / 60, 1) ?: 0, // en minutos
            'duration_trend' => 0,
            'total_events' => (int) $total_events ?: 0,
            'events_trend' => $this->calculate_trend($total_events, $prev_events),
            'total_crashes' => (int) $total_crashes ?: 0,
            'crashes_trend' => $this->calculate_trend($total_crashes, $prev_crashes),
        );
    }

    /**
     * Obtener datos de línea temporal
     */
    private function get_timeline_data($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $data = array(
            'labels' => array(),
            'users' => array(),
            'sessions' => array(),
        );

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('d/m', strtotime($date));

            // Usuarios únicos ese día
            $users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT device_id) FROM {$this->sessions_table}
                 WHERE DATE(started_at) = %s",
                $date
            ));
            $data['users'][] = (int) $users ?: 0;

            // Sesiones ese día
            $sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->sessions_table}
                 WHERE DATE(started_at) = %s",
                $date
            ));
            $data['sessions'][] = (int) $sessions ?: 0;
        }

        return $data;
    }

    /**
     * Obtener uso de módulos
     */
    private function get_modules_usage($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT module, COUNT(*) as count
             FROM {$this->events_table}
             WHERE created_at >= %s AND module IS NOT NULL AND module != ''
             GROUP BY module
             ORDER BY count DESC
             LIMIT 10",
            $date_from
        ), ARRAY_A);

        $labels = array();
        $values = array();

        foreach ($results as $row) {
            $labels[] = ucfirst(str_replace('_', ' ', $row['module']));
            $values[] = (int) $row['count'];
        }

        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }

    /**
     * Obtener distribución por plataforma
     */
    private function get_platform_distribution($period, $app_type) {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT platform, COUNT(*) as count
             FROM {$this->table_name}
             GROUP BY platform",
            ARRAY_A
        );

        $data = array(
            'android' => 0,
            'ios' => 0,
        );

        foreach ($results as $row) {
            $platform = strtolower($row['platform']);
            if (isset($data[$platform])) {
                $data[$platform] = (int) $row['count'];
            }
        }

        return array(
            'labels' => array('Android', 'iOS'),
            'values' => array($data['android'], $data['ios']),
        );
    }

    /**
     * Obtener eventos por tipo
     */
    private function get_events_by_type($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count
             FROM {$this->events_table}
             WHERE created_at >= %s
             GROUP BY event_type
             ORDER BY count DESC
             LIMIT 8",
            $date_from
        ), ARRAY_A);

        $labels = array();
        $values = array();

        foreach ($results as $row) {
            $labels[] = ucfirst($row['event_type']);
            $values[] = (int) $row['count'];
        }

        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }

    /**
     * Obtener distribución de versiones
     */
    private function get_version_distribution($period, $app_type, $platform) {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT app_version, COUNT(*) as count
             FROM {$this->table_name}
             GROUP BY app_version
             ORDER BY count DESC
             LIMIT 5",
            ARRAY_A
        );

        $labels = array();
        $values = array();

        foreach ($results as $row) {
            $labels[] = 'v' . $row['app_version'];
            $values[] = (int) $row['count'];
        }

        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }

    /**
     * Obtener top pantallas
     */
    private function get_top_screens($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                screen_name as screen,
                COUNT(*) as visits,
                COUNT(DISTINCT device_id) as users,
                AVG(TIMESTAMPDIFF(SECOND, created_at, NOW())) as avg_time
             FROM {$this->events_table}
             WHERE created_at >= %s AND event_type = 'screen_view' AND screen_name IS NOT NULL
             GROUP BY screen_name
             ORDER BY visits DESC
             LIMIT 10",
            $date_from
        ), ARRAY_A);
    }

    /**
     * Obtener crashes recientes
     */
    private function get_recent_crashes($period, $app_type, $platform) {
        global $wpdb;

        $days = $this->period_to_days($period);
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                e.event_name as error,
                a.app_version as version,
                a.device_model as device,
                e.created_at as date
             FROM {$this->events_table} e
             LEFT JOIN {$this->table_name} a ON e.device_id = a.device_id
             WHERE e.created_at >= %s AND e.event_type = 'crash'
             ORDER BY e.created_at DESC
             LIMIT 10",
            $date_from
        ), ARRAY_A);
    }

    /**
     * Obtener top dispositivos
     */
    private function get_top_devices($period, $app_type, $platform) {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                device_model as model,
                os_version,
                COUNT(*) as users,
                ROUND(COUNT(*) * 100.0 / %d, 1) as percentage
             FROM {$this->table_name}
             WHERE device_model IS NOT NULL
             GROUP BY device_model, os_version
             ORDER BY users DESC
             LIMIT 10",
            max($total, 1)
        ), ARRAY_A);
    }

    /**
     * Obtener datos de retención
     */
    private function get_retention_data($period, $app_type, $platform) {
        global $wpdb;

        // Matriz de retención simplificada (7 días)
        $retention = array();

        for ($cohort = 6; $cohort >= 0; $cohort--) {
            $cohort_date = date('Y-m-d', strtotime("-{$cohort} days"));
            $row = array(
                'cohort' => date('d/m', strtotime($cohort_date)),
                'days' => array(),
            );

            // Usuarios que se registraron ese día
            $cohort_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT device_id) FROM {$this->table_name}
                 WHERE DATE(first_seen) = %s",
                $cohort_date
            ));

            for ($day = 0; $day <= 6 - $cohort; $day++) {
                $check_date = date('Y-m-d', strtotime($cohort_date . " +{$day} days"));

                if ($cohort_users > 0) {
                    $returned = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(DISTINCT s.device_id)
                         FROM {$this->sessions_table} s
                         JOIN {$this->table_name} a ON s.device_id = a.device_id
                         WHERE DATE(a.first_seen) = %s AND DATE(s.started_at) = %s",
                        $cohort_date,
                        $check_date
                    ));
                    $row['days'][] = round(($returned / $cohort_users) * 100);
                } else {
                    $row['days'][] = 0;
                }
            }

            $retention[] = $row;
        }

        return $retention;
    }

    /**
     * Obtener datos en tiempo real
     */
    private function get_realtime_data() {
        global $wpdb;

        $five_min_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $one_min_ago = date('Y-m-d H:i:s', strtotime('-1 minute'));

        // Usuarios activos ahora
        $active_now = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT device_id) FROM {$this->sessions_table}
             WHERE is_active = 1 AND started_at >= %s",
            $five_min_ago
        ));

        // Eventos por minuto
        $events_per_min = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->events_table}
             WHERE created_at >= %s",
            $one_min_ago
        ));

        // Últimos eventos
        $recent_events = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, event_name, screen_name, created_at
             FROM {$this->events_table}
             WHERE created_at >= %s
             ORDER BY created_at DESC
             LIMIT 10",
            $five_min_ago
        ), ARRAY_A);

        return array(
            'active_users' => (int) $active_now ?: 0,
            'events_per_min' => (int) $events_per_min ?: 0,
            'recent_events' => $recent_events,
        );
    }

    /**
     * Convertir período a días
     */
    private function period_to_days($period) {
        switch ($period) {
            case '7d': return 7;
            case '30d': return 30;
            case '90d': return 90;
            case '365d': return 365;
            default: return 30;
        }
    }

    /**
     * Construir cláusula WHERE
     */
    private function build_where_clause($date_from, $date_to = null, $app_type = 'all', $platform = 'all') {
        $where = "";

        if ($date_from) {
            $where .= " AND last_seen >= '{$date_from}'";
        }
        if ($date_to) {
            $where .= " AND last_seen < '{$date_to}'";
        }
        if ($app_type !== 'all') {
            $where .= " AND app_type = '{$app_type}'";
        }
        if ($platform !== 'all') {
            $where .= " AND platform = '{$platform}'";
        }

        return $where;
    }

    /**
     * Calcular tendencia
     */
    private function calculate_trend($current, $previous) {
        if (!$previous || $previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * AJAX: Exportar analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('flavor_app_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $period = sanitize_text_field($_POST['period'] ?? '30d');

        $data = array(
            'exported_at' => current_time('mysql'),
            'period' => $period,
            'summary' => $this->get_summary_metrics($period, 'all', 'all'),
            'modules' => $this->get_modules_usage($period, 'all', 'all'),
            'screens' => $this->get_top_screens($period, 'all', 'all'),
            'devices' => $this->get_top_devices($period, 'all', 'all'),
        );

        wp_send_json_success(array(
            'filename' => 'app-analytics-' . date('Y-m-d') . '.json',
            'content' => json_encode($data, JSON_PRETTY_PRINT),
        ));
    }
}

// Inicializar
Flavor_App_Analytics_Dashboard::get_instance();
