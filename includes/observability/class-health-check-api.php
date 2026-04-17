<?php
/**
 * API de Health Check consolidado
 *
 * Endpoint único que reúne verificaciones de salud del sistema
 * para monitoreo externo y alertas.
 *
 * @package FlavorPlatform
 * @subpackage Observability
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API REST para health checks
 */
class Flavor_Health_Check_API {

    /**
     * Namespace de la API
     */
    private const NAMESPACE = 'flavor/v1';

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Umbrales de rendimiento (ms)
     */
    private const PERFORMANCE_THRESHOLDS = [
        'database' => 100,    // Query simple < 100ms
        'rest_api' => 500,    // Request REST < 500ms
        'cache'    => 50,     // Hit de caché < 50ms
    ];

    /**
     * Tablas críticas del sistema
     */
    private const CRITICAL_TABLES = [
        'flavor_activity_log',
        'flavor_eventos',
        'flavor_socios',
        'flavor_gc_pedidos',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa la API
     *
     * @return void
     */
    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas REST
     *
     * @return void
     */
    public function register_routes() {
        // Health check público (solo status básico)
        register_rest_route(self::NAMESPACE, '/health', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_health_status'],
            'permission_callback' => '__return_true',
        ]);

        // Health check detallado (requiere admin)
        register_rest_route(self::NAMESPACE, '/health/detailed', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_detailed_health'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Métricas de rendimiento (requiere admin)
        register_rest_route(self::NAMESPACE, '/health/metrics', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_performance_metrics'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Verifica permiso de administrador
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Health check básico (público)
     *
     * @return WP_REST_Response
     */
    public function get_health_status() {
        $start = microtime(true);

        $checks = [
            'database' => $this->check_database(),
            'cache' => $this->check_cache(),
        ];

        $all_healthy = !in_array(false, array_column($checks, 'healthy'), true);
        $response_time = round((microtime(true) - $start) * 1000, 2);

        $status_code = $all_healthy ? 200 : 503;

        return new WP_REST_Response([
            'status' => $all_healthy ? 'healthy' : 'unhealthy',
            'timestamp' => gmdate('c'),
            'response_time_ms' => $response_time,
            'version' => defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : 'unknown',
        ], $status_code);
    }

    /**
     * Health check detallado (admin)
     *
     * @return WP_REST_Response
     */
    public function get_detailed_health() {
        $start = microtime(true);

        $checks = [
            'database' => $this->check_database(),
            'tables' => $this->check_critical_tables(),
            'cache' => $this->check_cache(),
            'modules' => $this->check_modules(),
            'memory' => $this->check_memory(),
            'disk' => $this->check_disk(),
        ];

        $all_healthy = true;
        $warnings = [];

        foreach ($checks as $name => $check) {
            if (!$check['healthy']) {
                $all_healthy = false;
            }
            if (!empty($check['warning'])) {
                $warnings[] = $name . ': ' . $check['warning'];
            }
        }

        $response_time = round((microtime(true) - $start) * 1000, 2);

        return new WP_REST_Response([
            'status' => $all_healthy ? 'healthy' : 'unhealthy',
            'timestamp' => gmdate('c'),
            'response_time_ms' => $response_time,
            'checks' => $checks,
            'warnings' => $warnings,
            'system' => [
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : 'unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ], $all_healthy ? 200 : 503);
    }

    /**
     * Métricas de rendimiento
     *
     * @return WP_REST_Response
     */
    public function get_performance_metrics() {
        $metrics_file = dirname(__FILE__) . '/class-performance-metrics.php';
        if (!file_exists($metrics_file)) {
            return new WP_REST_Response([
                'error' => 'Performance metrics not available',
            ], 404);
        }

        require_once $metrics_file;
        $metrics = Flavor_Performance_Metrics::get_instance();

        return new WP_REST_Response([
            'timestamp' => gmdate('c'),
            'metrics' => $metrics->get_all_stats(),
            'thresholds' => self::PERFORMANCE_THRESHOLDS,
        ], 200);
    }

    /**
     * Verifica conexión a base de datos
     *
     * @return array
     */
    private function check_database() {
        global $wpdb;

        $start = microtime(true);
        $result = $wpdb->get_var('SELECT 1');
        $duration = (microtime(true) - $start) * 1000;

        $healthy = $result === '1';
        $warning = null;

        if ($duration > self::PERFORMANCE_THRESHOLDS['database']) {
            $warning = sprintf('Query took %.2fms (threshold: %dms)', $duration, self::PERFORMANCE_THRESHOLDS['database']);
        }

        return [
            'healthy' => $healthy,
            'duration_ms' => round($duration, 2),
            'warning' => $warning,
        ];
    }

    /**
     * Verifica tablas críticas
     *
     * @return array
     */
    private function check_critical_tables() {
        global $wpdb;

        $missing = [];
        $counts = [];

        foreach (self::CRITICAL_TABLES as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $full_table)) === $full_table;

            if (!$exists) {
                $missing[] = $table;
            } else {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}");
                $counts[$table] = (int) $count;
            }
        }

        return [
            'healthy' => empty($missing),
            'missing_tables' => $missing,
            'row_counts' => $counts,
            'warning' => !empty($missing) ? 'Missing tables: ' . implode(', ', $missing) : null,
        ];
    }

    /**
     * Verifica sistema de caché
     *
     * @return array
     */
    private function check_cache() {
        $test_key = 'flavor_health_check_' . time();
        $test_value = 'test_' . wp_generate_password(8, false);

        $start = microtime(true);
        set_transient($test_key, $test_value, 60);
        $retrieved = get_transient($test_key);
        delete_transient($test_key);
        $duration = (microtime(true) - $start) * 1000;

        $healthy = $retrieved === $test_value;
        $warning = null;

        if ($duration > self::PERFORMANCE_THRESHOLDS['cache']) {
            $warning = sprintf('Cache operation took %.2fms (threshold: %dms)', $duration, self::PERFORMANCE_THRESHOLDS['cache']);
        }

        return [
            'healthy' => $healthy,
            'duration_ms' => round($duration, 2),
            'type' => wp_using_ext_object_cache() ? 'external' : 'transients',
            'warning' => $warning,
        ];
    }

    /**
     * Verifica módulos activos
     *
     * @return array
     */
    private function check_modules() {
        $active_modules = get_option('flavor_active_modules', []);
        $count = is_array($active_modules) ? count($active_modules) : 0;

        $warning = null;
        if ($count > 20) {
            $warning = "High number of active modules ({$count}) may impact performance";
        }

        return [
            'healthy' => true,
            'active_count' => $count,
            'warning' => $warning,
        ];
    }

    /**
     * Verifica uso de memoria
     *
     * @return array
     */
    private function check_memory() {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parse_memory_limit(ini_get('memory_limit'));

        $usage_percent = $limit > 0 ? ($current / $limit) * 100 : 0;
        $peak_percent = $limit > 0 ? ($peak / $limit) * 100 : 0;

        $warning = null;
        if ($usage_percent > 80) {
            $warning = sprintf('Memory usage at %.1f%% of limit', $usage_percent);
        }

        return [
            'healthy' => $usage_percent < 90,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit_mb' => round($limit / 1024 / 1024, 2),
            'usage_percent' => round($usage_percent, 1),
            'warning' => $warning,
        ];
    }

    /**
     * Verifica espacio en disco
     *
     * @return array
     */
    private function check_disk() {
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'];

        if (!is_dir($path)) {
            return [
                'healthy' => false,
                'warning' => 'Upload directory not accessible',
            ];
        }

        $free = disk_free_space($path);
        $total = disk_total_space($path);

        if ($free === false || $total === false) {
            return [
                'healthy' => true,
                'warning' => 'Unable to determine disk space',
            ];
        }

        $free_percent = ($free / $total) * 100;
        $warning = null;

        if ($free_percent < 10) {
            $warning = sprintf('Low disk space: %.1f%% free', $free_percent);
        }

        return [
            'healthy' => $free_percent > 5,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'free_percent' => round($free_percent, 1),
            'warning' => $warning,
        ];
    }

    /**
     * Parsea límite de memoria de PHP
     *
     * @param string $limit Límite (ej: '256M').
     * @return int Bytes.
     */
    private function parse_memory_limit($limit) {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $limit = strtoupper(trim($limit));
        $value = (int) $limit;

        if (strpos($limit, 'G') !== false) {
            return $value * 1024 * 1024 * 1024;
        }
        if (strpos($limit, 'M') !== false) {
            return $value * 1024 * 1024;
        }
        if (strpos($limit, 'K') !== false) {
            return $value * 1024;
        }

        return $value;
    }
}

// Inicializar
add_action('init', function () {
    Flavor_Health_Check_API::get_instance()->init();
});
