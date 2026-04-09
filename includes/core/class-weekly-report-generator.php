<?php
/**
 * Generador de Resumen Ejecutivo Semanal
 *
 * Genera automáticamente un resumen semanal con:
 * - Métricas clave de la semana
 * - Comparativas con semana anterior
 * - Análisis IA de tendencias
 * - Recomendaciones automáticas
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Weekly_Report_Generator {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     */
    private $engine = null;

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
        add_action('wp_ajax_flavor_generate_weekly_report', [$this, 'ajax_generate_report']);
        add_action('wp_ajax_flavor_get_report_preview', [$this, 'ajax_get_preview']);
        add_action('flavor_weekly_report_cron', [$this, 'generate_scheduled_report']);

        // Programar cron si no existe
        if (!wp_next_scheduled('flavor_weekly_report_cron')) {
            wp_schedule_event(strtotime('next monday 9:00'), 'weekly', 'flavor_weekly_report_cron');
        }
    }

    /**
     * Obtiene el motor de IA
     */
    private function get_engine() {
        if ($this->engine === null && class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $this->engine = $manager->get_active_engine();
        }
        return $this->engine;
    }

    /**
     * Handler AJAX para generar reporte
     */
    public function ajax_generate_report() {
        check_ajax_referer('flavor_weekly_report', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $week_start = sanitize_text_field($_POST['week_start'] ?? '');
        $modules = isset($_POST['modules']) ? array_map('sanitize_text_field', (array) $_POST['modules']) : [];

        $report = $this->generate_report($week_start, $modules);

        if ($report['success']) {
            wp_send_json_success($report);
        } else {
            wp_send_json_error($report);
        }
    }

    /**
     * Handler AJAX para preview
     */
    public function ajax_get_preview() {
        check_ajax_referer('flavor_weekly_report', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $metrics = $this->collect_metrics();
        wp_send_json_success(['metrics' => $metrics]);
    }

    /**
     * Genera reporte programado
     */
    public function generate_scheduled_report() {
        $report = $this->generate_report();

        if ($report['success']) {
            $this->save_report($report);
            $this->send_report_notification($report);
        }
    }

    /**
     * Genera el reporte semanal completo
     */
    public function generate_report($week_start = '', $modules = []) {
        // Calcular fechas
        if (empty($week_start)) {
            $week_start = date('Y-m-d', strtotime('last monday'));
        }
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        $prev_week_start = date('Y-m-d', strtotime($week_start . ' -7 days'));
        $prev_week_end = date('Y-m-d', strtotime($week_start . ' -1 day'));

        // Recolectar métricas
        $current_metrics = $this->collect_metrics($week_start, $week_end, $modules);
        $previous_metrics = $this->collect_metrics($prev_week_start, $prev_week_end, $modules);

        // Calcular variaciones
        $variations = $this->calculate_variations($current_metrics, $previous_metrics);

        // Generar análisis IA
        $ai_analysis = $this->generate_ai_analysis($current_metrics, $variations);

        // Generar recomendaciones
        $recommendations = $this->generate_recommendations($current_metrics, $variations);

        return [
            'success' => true,
            'period' => [
                'start' => $week_start,
                'end' => $week_end,
            ],
            'metrics' => $current_metrics,
            'previous_metrics' => $previous_metrics,
            'variations' => $variations,
            'analysis' => $ai_analysis,
            'recommendations' => $recommendations,
            'generated_at' => current_time('mysql'),
        ];
    }

    /**
     * Recolecta métricas de la semana
     */
    private function collect_metrics($start_date = '', $end_date = '', $modules = []) {
        global $wpdb;

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-7 days'));
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }

        $metrics = [
            'socios' => [],
            'eventos' => [],
            'reservas' => [],
            'incidencias' => [],
            'grupos_consumo' => [],
            'engagement' => [],
        ];

        $prefix = $wpdb->prefix . 'flavor_';

        // SOCIOS
        $tabla_socios = $prefix . 'socios';
        if ($this->table_exists($tabla_socios)) {
            $metrics['socios'] = [
                'nuevos' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_socios} WHERE fecha_alta BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'"),
                'bajas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_socios} WHERE fecha_baja BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
            ];
        }

        // EVENTOS
        $tabla_eventos = $prefix . 'eventos';
        if ($this->table_exists($tabla_eventos)) {
            $metrics['eventos'] = [
                'celebrados' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_eventos} WHERE fecha_evento BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'inscritos' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(inscritos), 0) FROM {$tabla_eventos} WHERE fecha_evento BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'proximos' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_eventos} WHERE fecha_evento > %s AND fecha_evento <= DATE_ADD(%s, INTERVAL 7 DAY)",
                    $end_date, $end_date
                )),
            ];
        }

        // RESERVAS
        $tabla_reservas = $prefix . 'reservas';
        if ($this->table_exists($tabla_reservas)) {
            $metrics['reservas'] = [
                'realizadas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_reserva BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'confirmadas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_reserva BETWEEN %s AND %s AND estado = 'confirmada'",
                    $start_date, $end_date
                )),
                'canceladas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_reserva BETWEEN %s AND %s AND estado = 'cancelada'",
                    $start_date, $end_date
                )),
            ];
        }

        // INCIDENCIAS
        $tabla_incidencias = $prefix . 'incidencias';
        if ($this->table_exists($tabla_incidencias)) {
            $metrics['incidencias'] = [
                'abiertas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_incidencias} WHERE fecha_creacion BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'resueltas' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_incidencias} WHERE fecha_resolucion BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'pendientes' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$tabla_incidencias} WHERE estado NOT IN ('cerrada', 'resuelta')"
                ),
                'tiempo_medio_resolucion' => $this->calculate_avg_resolution_time($start_date, $end_date),
            ];
        }

        // GRUPOS DE CONSUMO
        $tabla_gc_pedidos = $prefix . 'gc_pedidos';
        if ($this->table_exists($tabla_gc_pedidos)) {
            $metrics['grupos_consumo'] = [
                'pedidos' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_gc_pedidos} WHERE fecha_pedido BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
                'volumen_total' => (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(total), 0) FROM {$tabla_gc_pedidos} WHERE fecha_pedido BETWEEN %s AND %s",
                    $start_date, $end_date
                )),
            ];
        }

        // ENGAGEMENT (visitas, usuarios activos)
        $metrics['engagement'] = [
            'usuarios_activos' => $this->get_active_users_count($start_date, $end_date),
            'nuevos_usuarios' => $this->get_new_users_count($start_date, $end_date),
        ];

        return $metrics;
    }

    /**
     * Calcula variaciones entre periodos
     */
    private function calculate_variations($current, $previous) {
        $variations = [];

        foreach ($current as $category => $values) {
            if (!is_array($values)) continue;

            $variations[$category] = [];
            foreach ($values as $metric => $value) {
                $prev_value = $previous[$category][$metric] ?? 0;

                if ($prev_value > 0) {
                    $change = (($value - $prev_value) / $prev_value) * 100;
                } elseif ($value > 0) {
                    $change = 100;
                } else {
                    $change = 0;
                }

                $variations[$category][$metric] = [
                    'current' => $value,
                    'previous' => $prev_value,
                    'change' => round($change, 1),
                    'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
                ];
            }
        }

        return $variations;
    }

    /**
     * Genera análisis con IA
     */
    private function generate_ai_analysis($metrics, $variations) {
        $engine = $this->get_engine();
        if (!$engine || !$engine->is_configured()) {
            return $this->generate_basic_analysis($metrics, $variations);
        }

        $system_prompt = "Eres un analista de datos para una organización comunitaria. Genera un resumen ejecutivo breve y claro de la actividad semanal. Destaca los puntos más relevantes, tendencias positivas y áreas de mejora. Usa un tono profesional pero cercano. Máximo 200 palabras.";

        $data_summary = $this->format_metrics_for_ai($metrics, $variations);
        $user_prompt = "Analiza estos datos de la semana y genera un resumen ejecutivo:\n\n" . $data_summary;

        try {
            $response = $engine->send_message(
                [['role' => 'user', 'content' => $user_prompt]],
                $system_prompt,
                []
            );

            if ($response['success']) {
                return $response['response'];
            }
        } catch (Exception $e) {
            error_log('Flavor Weekly Report AI Error: ' . $e->getMessage());
        }

        return $this->generate_basic_analysis($metrics, $variations);
    }

    /**
     * Genera análisis básico sin IA
     */
    private function generate_basic_analysis($metrics, $variations) {
        $highlights = [];

        // Socios
        if (!empty($metrics['socios']['nuevos'])) {
            $trend = $variations['socios']['nuevos']['trend'] ?? 'stable';
            $highlights[] = sprintf(
                __('%d nuevos socios esta semana (%s respecto a la anterior)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $metrics['socios']['nuevos'],
                $this->trend_text($trend, $variations['socios']['nuevos']['change'] ?? 0)
            );
        }

        // Eventos
        if (!empty($metrics['eventos']['celebrados'])) {
            $highlights[] = sprintf(
                __('%d eventos celebrados con %d participantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $metrics['eventos']['celebrados'],
                $metrics['eventos']['inscritos']
            );
        }

        // Incidencias
        if (!empty($metrics['incidencias']['pendientes'])) {
            $highlights[] = sprintf(
                __('%d incidencias pendientes de resolver', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $metrics['incidencias']['pendientes']
            );
        }

        return implode("\n\n", $highlights);
    }

    /**
     * Genera recomendaciones basadas en datos
     */
    private function generate_recommendations($metrics, $variations) {
        $recommendations = [];

        // Recomendaciones automáticas basadas en patrones
        if (isset($variations['socios']['nuevos']) && $variations['socios']['nuevos']['trend'] === 'down') {
            $recommendations[] = [
                'type' => 'warning',
                'title' => __('Captación de socios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'text' => __('La captación de nuevos socios ha bajado. Considera lanzar una campaña de captación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if (!empty($metrics['incidencias']['pendientes']) && $metrics['incidencias']['pendientes'] > 10) {
            $recommendations[] = [
                'type' => 'alert',
                'title' => __('Incidencias acumuladas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'text' => sprintf(
                    __('Hay %d incidencias pendientes. Prioriza la resolución para mantener la satisfacción.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $metrics['incidencias']['pendientes']
                ),
            ];
        }

        if (isset($metrics['reservas']['canceladas']) && $metrics['reservas']['realizadas'] > 0) {
            $cancel_rate = ($metrics['reservas']['canceladas'] / $metrics['reservas']['realizadas']) * 100;
            if ($cancel_rate > 20) {
                $recommendations[] = [
                    'type' => 'warning',
                    'title' => __('Alta tasa de cancelaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'text' => sprintf(
                        __('El %.0f%% de las reservas se cancelaron. Revisa las políticas de reserva.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $cancel_rate
                    ),
                ];
            }
        }

        if (!empty($metrics['eventos']['proximos']) && $metrics['eventos']['proximos'] > 0) {
            $recommendations[] = [
                'type' => 'info',
                'title' => __('Próximos eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'text' => sprintf(
                    __('Hay %d eventos programados para la próxima semana. Recuerda enviar recordatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $metrics['eventos']['proximos']
                ),
            ];
        }

        return $recommendations;
    }

    /**
     * Formatea métricas para prompt de IA
     */
    private function format_metrics_for_ai($metrics, $variations) {
        $lines = [];

        foreach ($metrics as $category => $values) {
            if (empty($values) || !is_array($values)) continue;

            $category_name = ucfirst(str_replace('_', ' ', $category));
            $lines[] = "## {$category_name}";

            foreach ($values as $metric => $value) {
                $variation = $variations[$category][$metric] ?? null;
                $change_text = '';
                if ($variation) {
                    $change_text = " ({$variation['change']}% vs semana anterior)";
                }
                $lines[] = "- " . ucfirst(str_replace('_', ' ', $metric)) . ": {$value}{$change_text}";
            }

            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * Texto de tendencia
     */
    private function trend_text($trend, $change) {
        $abs_change = abs($change);
        switch ($trend) {
            case 'up':
                return sprintf('+%.0f%%', $abs_change);
            case 'down':
                return sprintf('-%.0f%%', $abs_change);
            default:
                return __('sin cambios', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
    }

    /**
     * Calcula tiempo medio de resolución de incidencias
     */
    private function calculate_avg_resolution_time($start_date, $end_date) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if (!$this->table_exists($tabla)) {
            return 0;
        }

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion))
             FROM {$tabla}
             WHERE fecha_resolucion BETWEEN %s AND %s
             AND fecha_creacion IS NOT NULL",
            $start_date, $end_date
        ));

        return round((float) $result, 1);
    }

    /**
     * Obtiene usuarios activos en periodo
     */
    private function get_active_users_count($start_date, $end_date) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
             WHERE meta_key = 'last_activity'
             AND meta_value BETWEEN %s AND %s",
            strtotime($start_date), strtotime($end_date)
        ));
    }

    /**
     * Obtiene nuevos usuarios en periodo
     */
    private function get_new_users_count($start_date, $end_date) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users}
             WHERE user_registered BETWEEN %s AND %s",
            $start_date . ' 00:00:00', $end_date . ' 23:59:59'
        ));
    }

    /**
     * Verifica si tabla existe
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }

    /**
     * Guarda reporte en historial
     */
    private function save_report($report) {
        $reports = get_option('flavor_weekly_reports', []);

        $reports[] = [
            'period' => $report['period'],
            'generated_at' => $report['generated_at'],
            'summary' => substr($report['analysis'], 0, 500),
        ];

        // Mantener últimos 52 reportes (1 año)
        if (count($reports) > 52) {
            $reports = array_slice($reports, -52);
        }

        update_option('flavor_weekly_reports', $reports);
    }

    /**
     * Envía notificación del reporte
     */
    private function send_report_notification($report) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(__('[%s] Resumen Semanal - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $site_name,
            $report['period']['start']
        );

        $message = $this->format_report_email($report);

        wp_mail($admin_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    /**
     * Formatea reporte para email
     */
    private function format_report_email($report) {
        $html = '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<h1 style="color: #333;">📊 ' . __('Resumen Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
        $html .= '<p style="color: #666;">' . sprintf(
            __('Periodo: %s - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $report['period']['start'],
            $report['period']['end']
        ) . '</p>';

        $html .= '<h2>' . __('Análisis', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>';
        $html .= '<p>' . nl2br(esc_html($report['analysis'])) . '</p>';

        if (!empty($report['recommendations'])) {
            $html .= '<h2>' . __('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>';
            $html .= '<ul>';
            foreach ($report['recommendations'] as $rec) {
                $html .= '<li><strong>' . esc_html($rec['title']) . '</strong>: ' . esc_html($rec['text']) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</body></html>';

        return $html;
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Weekly_Report_Generator::get_instance();
});
