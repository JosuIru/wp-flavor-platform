<?php
/**
 * Sistema de Analytics para App Móvil
 *
 * Rastrea eventos, conexiones y uso de módulos desde la app móvil.
 *
 * @package FlavorPlatform
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de analytics de la app
 */
class Flavor_App_Analytics {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de las tablas
     */
    private $table_events;
    private $table_daily_stats;

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
        global $wpdb;
        $this->table_events = $wpdb->prefix . 'flavor_app_events';
        $this->table_daily_stats = $wpdb->prefix . 'flavor_app_daily_stats';

        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Crea las tablas necesarias
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_events = $wpdb->prefix . 'flavor_app_events';
        $table_stats = $wpdb->prefix . 'flavor_app_daily_stats';

        $sql_events = "CREATE TABLE $table_events (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            device_id varchar(100) DEFAULT NULL,
            platform varchar(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY device_id (device_id),
            KEY platform (platform),
            KEY created_at (created_at)
        ) $charset_collate;";

        $sql_stats = "CREATE TABLE $table_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            connections int(11) DEFAULT 0,
            unique_devices int(11) DEFAULT 0,
            module_accesses int(11) DEFAULT 0,
            active_users int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY stat_date (stat_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_events);
        dbDelta($sql_stats);
    }

    /**
     * Registra los endpoints REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-app-analytics/v1', '/track', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_track_event'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor-app-analytics/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_stats'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Maneja el tracking de eventos desde la app
     */
    public function handle_track_event($request) {
        $event_type = sanitize_text_field($request->get_param('event_type'));
        $event_data = $request->get_param('data');
        $device_id = sanitize_text_field($request->get_param('device_id'));
        $platform = sanitize_text_field($request->get_param('platform'));
        $user_id = get_current_user_id();

        if (empty($event_type)) {
            return new WP_Error('missing_event_type', 'Event type is required', ['status' => 400]);
        }

        $logged = $this->log_event($event_type, [
            'data' => $event_data,
            'device_id' => $device_id,
            'platform' => $platform,
            'user_id' => $user_id,
        ]);

        // Actualizar estadísticas diarias
        $this->update_daily_stats($event_type);

        return rest_ensure_response([
            'success' => $logged,
            'event_type' => $event_type,
        ]);
    }

    /**
     * Registra un evento
     */
    public function log_event($event_type, $data = []) {
        global $wpdb;

        $inserted = $wpdb->insert(
            $this->table_events,
            [
                'event_type' => $event_type,
                'event_data' => is_array($data['data']) ? wp_json_encode($data['data']) : ($data['data'] ?? null),
                'user_id' => $data['user_id'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'platform' => $data['platform'] ?? null,
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        return $inserted !== false;
    }

    /**
     * Actualiza las estadísticas diarias
     */
    private function update_daily_stats($event_type) {
        global $wpdb;

        $today = current_time('Y-m-d');

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_daily_stats} WHERE stat_date = %s",
            $today
        ));

        if ($existing) {
            $update_field = '';
            if ($event_type === 'app_connection' || $event_type === 'connection') {
                $update_field = 'connections = connections + 1';
            } elseif ($event_type === 'module_access') {
                $update_field = 'module_accesses = module_accesses + 1';
            }

            if ($update_field) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->table_daily_stats} SET $update_field WHERE stat_date = %s",
                    $today
                ));
            }
        } else {
            $wpdb->insert(
                $this->table_daily_stats,
                [
                    'stat_date' => $today,
                    'connections' => $event_type === 'app_connection' ? 1 : 0,
                    'module_accesses' => $event_type === 'module_access' ? 1 : 0,
                    'unique_devices' => 0,
                    'active_users' => 0,
                ],
                ['%s', '%d', '%d', '%d', '%d']
            );
        }
    }

    /**
     * Maneja la solicitud de estadísticas
     */
    public function handle_get_stats($request) {
        $period = absint($request->get_param('period')) ?: 30;

        return rest_ensure_response([
            'summary' => $this->get_summary_stats($period),
            'timeline' => $this->get_timeline_stats($period),
            'modules' => $this->get_module_stats($period),
            'devices' => $this->get_device_stats($period),
            'tokens' => $this->get_token_stats(),
        ]);
    }

    /**
     * Obtiene resumen de estadísticas
     */
    public function get_summary_stats($period = 30) {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$period} days"));

        $total_connections = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(connections) FROM {$this->table_daily_stats} WHERE stat_date >= %s",
            $date_from
        )) ?: 0;

        $unique_devices = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT device_id) FROM {$this->table_events}
             WHERE created_at >= %s AND device_id IS NOT NULL",
            $date_from . ' 00:00:00'
        )) ?: 0;

        $module_accesses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_events}
             WHERE event_type = 'module_access' AND created_at >= %s",
            $date_from . ' 00:00:00'
        )) ?: 0;

        $table_tokens = $wpdb->prefix . 'flavor_push_tokens';
        $active_tokens = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_tokens'") === $table_tokens) {
            $active_tokens = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_tokens WHERE last_used >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ) ?: 0;
        }

        return [
            'total_connections' => (int) $total_connections,
            'unique_devices' => (int) $unique_devices,
            'module_accesses' => (int) $module_accesses,
            'active_tokens' => (int) $active_tokens,
        ];
    }

    /**
     * Obtiene estadísticas de timeline
     */
    public function get_timeline_stats($period = 30) {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$period} days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT stat_date as date, connections, module_accesses
             FROM {$this->table_daily_stats}
             WHERE stat_date >= %s
             ORDER BY stat_date ASC",
            $date_from
        ), ARRAY_A);

        $filled_results = [];
        $current = new DateTime($date_from);
        $end = new DateTime();

        while ($current <= $end) {
            $date_str = $current->format('Y-m-d');
            $found = false;

            foreach ($results as $row) {
                if ($row['date'] === $date_str) {
                    $filled_results[] = [
                        'date' => $date_str,
                        'connections' => (int) $row['connections'],
                        'module_accesses' => (int) $row['module_accesses'],
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $filled_results[] = [
                    'date' => $date_str,
                    'connections' => 0,
                    'module_accesses' => 0,
                ];
            }

            $current->modify('+1 day');
        }

        return $filled_results;
    }

    /**
     * Obtiene estadísticas por módulo
     */
    public function get_module_stats($period = 30) {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$period} days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.module_id')) as module_id,
                COUNT(*) as access_count
             FROM {$this->table_events}
             WHERE event_type = 'module_access'
               AND created_at >= %s
               AND event_data IS NOT NULL
             GROUP BY module_id
             ORDER BY access_count DESC
             LIMIT 20",
            $date_from . ' 00:00:00'
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Obtiene estadísticas por dispositivo/plataforma
     */
    public function get_device_stats($period = 30) {
        global $wpdb;

        $date_from = date('Y-m-d', strtotime("-{$period} days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT platform, COUNT(DISTINCT device_id) as count
             FROM {$this->table_events}
             WHERE created_at >= %s AND platform IS NOT NULL
             GROUP BY platform
             ORDER BY count DESC",
            $date_from . ' 00:00:00'
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Obtiene estadísticas de tokens
     */
    public function get_token_stats() {
        global $wpdb;

        $table_tokens = $wpdb->prefix . 'flavor_push_tokens';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_tokens'") !== $table_tokens) {
            return [];
        }

        $results = $wpdb->get_results(
            "SELECT
                id,
                CONCAT(LEFT(token, 20), '...') as token_partial,
                platform,
                user_id,
                last_used as last_active
             FROM $table_tokens
             ORDER BY last_used DESC
             LIMIT 10",
            ARRAY_A
        );

        foreach ($results as &$row) {
            if ($row['user_id']) {
                $user = get_userdata($row['user_id']);
                $row['user_name'] = $user ? $user->display_name : 'Usuario #' . $row['user_id'];
            } else {
                $row['user_name'] = null;
            }
        }

        return $results ?: [];
    }

    /**
     * Registra una conexión de app
     */
    public function log_connection($device_id = null, $platform = null) {
        return $this->log_event('app_connection', [
            'device_id' => $device_id,
            'platform' => $platform,
            'user_id' => get_current_user_id(),
        ]);
    }

    /**
     * Registra acceso a módulo
     */
    public function log_module_access($module_id, $device_id = null) {
        return $this->log_event('module_access', [
            'data' => ['module_id' => $module_id],
            'device_id' => $device_id,
            'user_id' => get_current_user_id(),
        ]);
    }

    /**
     * Obtiene la IP del cliente
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }
}

// Inicializar
Flavor_App_Analytics::get_instance();
