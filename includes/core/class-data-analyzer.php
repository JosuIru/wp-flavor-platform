<?php
/**
 * Analizador de Datos con IA
 *
 * Proporciona análisis inteligente de datos:
 * - Detección de tendencias
 * - Predicciones básicas
 * - Anomalías y alertas
 * - Insights automáticos
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Data_Analyzer {

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
        add_action('wp_ajax_flavor_analyze_data', [$this, 'ajax_analyze']);
        add_action('wp_ajax_flavor_get_insights', [$this, 'ajax_get_insights']);
        add_action('wp_ajax_flavor_detect_anomalies', [$this, 'ajax_detect_anomalies']);
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
     * Handler AJAX para análisis
     */
    public function ajax_analyze() {
        check_ajax_referer('flavor_data_analyzer', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $analysis_type = sanitize_text_field($_POST['type'] ?? 'general');
        $module = sanitize_text_field($_POST['module'] ?? '');
        $period = sanitize_text_field($_POST['period'] ?? '30');

        $result = $this->analyze($analysis_type, $module, $period);
        wp_send_json_success($result);
    }

    /**
     * Handler AJAX para insights
     */
    public function ajax_get_insights() {
        check_ajax_referer('flavor_data_analyzer', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $module = sanitize_text_field($_POST['module'] ?? '');
        $insights = $this->get_module_insights($module);

        wp_send_json_success(['insights' => $insights]);
    }

    /**
     * Handler AJAX para detección de anomalías
     */
    public function ajax_detect_anomalies() {
        check_ajax_referer('flavor_data_analyzer', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $anomalies = $this->detect_all_anomalies();
        wp_send_json_success(['anomalies' => $anomalies]);
    }

    /**
     * Realiza análisis de datos
     */
    public function analyze($type, $module = '', $period = 30) {
        $data = $this->collect_data($module, $period);

        switch ($type) {
            case 'trends':
                return $this->analyze_trends($data, $module);
            case 'predictions':
                return $this->generate_predictions($data, $module);
            case 'correlations':
                return $this->find_correlations($data);
            case 'segments':
                return $this->analyze_segments($data, $module);
            default:
                return $this->general_analysis($data, $module);
        }
    }

    /**
     * Recolecta datos para análisis
     */
    private function collect_data($module, $period) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $date_from = date('Y-m-d', strtotime("-{$period} days"));

        $data = [
            'period' => $period,
            'date_from' => $date_from,
            'date_to' => date('Y-m-d'),
        ];

        // Datos según módulo
        if (empty($module) || $module === 'socios') {
            $data['socios'] = $this->collect_socios_data($prefix, $date_from);
        }

        if (empty($module) || $module === 'eventos') {
            $data['eventos'] = $this->collect_eventos_data($prefix, $date_from);
        }

        if (empty($module) || $module === 'reservas') {
            $data['reservas'] = $this->collect_reservas_data($prefix, $date_from);
        }

        if (empty($module) || $module === 'incidencias') {
            $data['incidencias'] = $this->collect_incidencias_data($prefix, $date_from);
        }

        if (empty($module) || $module === 'grupos_consumo') {
            $data['grupos_consumo'] = $this->collect_gc_data($prefix, $date_from);
        }

        return $data;
    }

    /**
     * Recolecta datos de socios
     */
    private function collect_socios_data($prefix, $date_from) {
        global $wpdb;
        $tabla = $prefix . 'socios';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'activo'"),
            'nuevos_periodo' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE fecha_alta >= %s", $date_from
            )),
            'bajas_periodo' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE fecha_baja >= %s", $date_from
            )),
            'por_tipo' => $wpdb->get_results(
                "SELECT tipo_socio, COUNT(*) as cantidad FROM {$tabla} WHERE estado = 'activo' GROUP BY tipo_socio",
                ARRAY_A
            ),
            'tendencia_diaria' => $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(fecha_alta) as fecha, COUNT(*) as nuevos
                 FROM {$tabla}
                 WHERE fecha_alta >= %s
                 GROUP BY DATE(fecha_alta)
                 ORDER BY fecha",
                $date_from
            ), ARRAY_A),
        ];
    }

    /**
     * Recolecta datos de eventos
     */
    private function collect_eventos_data($prefix, $date_from) {
        global $wpdb;
        $tabla = $prefix . 'eventos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return [
            'total_periodo' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE fecha_evento >= %s", $date_from
            )),
            'total_inscritos' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(inscritos), 0) FROM {$tabla} WHERE fecha_evento >= %s", $date_from
            )),
            'por_categoria' => $wpdb->get_results($wpdb->prepare(
                "SELECT categoria, COUNT(*) as cantidad, SUM(inscritos) as total_inscritos
                 FROM {$tabla}
                 WHERE fecha_evento >= %s
                 GROUP BY categoria",
                $date_from
            ), ARRAY_A),
            'ocupacion_media' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(inscritos / NULLIF(capacidad, 0) * 100)
                 FROM {$tabla}
                 WHERE fecha_evento >= %s AND capacidad > 0",
                $date_from
            )),
        ];
    }

    /**
     * Recolecta datos de reservas
     */
    private function collect_reservas_data($prefix, $date_from) {
        global $wpdb;
        $tabla = $prefix . 'reservas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return [
            'total_periodo' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE fecha_reserva >= %s", $date_from
            )),
            'por_estado' => $wpdb->get_results($wpdb->prepare(
                "SELECT estado, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_reserva >= %s
                 GROUP BY estado",
                $date_from
            ), ARRAY_A),
            'por_recurso' => $wpdb->get_results($wpdb->prepare(
                "SELECT recurso_id, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_reserva >= %s
                 GROUP BY recurso_id
                 ORDER BY cantidad DESC
                 LIMIT 10",
                $date_from
            ), ARRAY_A),
            'por_dia_semana' => $wpdb->get_results($wpdb->prepare(
                "SELECT DAYOFWEEK(fecha_reserva) as dia, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_reserva >= %s
                 GROUP BY DAYOFWEEK(fecha_reserva)",
                $date_from
            ), ARRAY_A),
        ];
    }

    /**
     * Recolecta datos de incidencias
     */
    private function collect_incidencias_data($prefix, $date_from) {
        global $wpdb;
        $tabla = $prefix . 'incidencias';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return [
            'total_periodo' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE fecha_creacion >= %s", $date_from
            )),
            'por_estado' => $wpdb->get_results($wpdb->prepare(
                "SELECT estado, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_creacion >= %s
                 GROUP BY estado",
                $date_from
            ), ARRAY_A),
            'por_prioridad' => $wpdb->get_results($wpdb->prepare(
                "SELECT prioridad, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_creacion >= %s
                 GROUP BY prioridad",
                $date_from
            ), ARRAY_A),
            'tiempo_resolucion_medio' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion))
                 FROM {$tabla}
                 WHERE fecha_resolucion >= %s",
                $date_from
            )),
            'por_categoria' => $wpdb->get_results($wpdb->prepare(
                "SELECT categoria, COUNT(*) as cantidad
                 FROM {$tabla}
                 WHERE fecha_creacion >= %s
                 GROUP BY categoria
                 ORDER BY cantidad DESC",
                $date_from
            ), ARRAY_A),
        ];
    }

    /**
     * Recolecta datos de grupos de consumo
     */
    private function collect_gc_data($prefix, $date_from) {
        global $wpdb;
        $tabla_pedidos = $prefix . 'gc_pedidos';

        if (!$this->table_exists($tabla_pedidos)) {
            return [];
        }

        return [
            'total_pedidos' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE fecha_pedido >= %s", $date_from
            )),
            'volumen_total' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$tabla_pedidos} WHERE fecha_pedido >= %s", $date_from
            )),
            'pedido_medio' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(total) FROM {$tabla_pedidos} WHERE fecha_pedido >= %s", $date_from
            )),
            'compradores_unicos' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_pedidos} WHERE fecha_pedido >= %s", $date_from
            )),
        ];
    }

    /**
     * Analiza tendencias
     */
    private function analyze_trends($data, $module) {
        $trends = [];

        // Tendencia de socios
        if (!empty($data['socios']['tendencia_diaria'])) {
            $trend = $this->calculate_trend($data['socios']['tendencia_diaria'], 'nuevos');
            $trends['socios'] = [
                'direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable'),
                'slope' => round($trend, 2),
                'description' => $this->describe_trend('socios', $trend),
            ];
        }

        // Tendencia de incidencias
        if (!empty($data['incidencias']['tiempo_resolucion_medio'])) {
            $tiempo = (float) $data['incidencias']['tiempo_resolucion_medio'];
            $trends['incidencias'] = [
                'avg_resolution_hours' => round($tiempo, 1),
                'description' => $tiempo < 24
                    ? __('Excelente tiempo de resolución (menos de 24h)', 'flavor-chat-ia')
                    : ($tiempo < 72
                        ? __('Tiempo de resolución aceptable', 'flavor-chat-ia')
                        : __('El tiempo de resolución necesita mejorar', 'flavor-chat-ia')
                    ),
            ];
        }

        return [
            'type' => 'trends',
            'period' => $data['period'],
            'trends' => $trends,
        ];
    }

    /**
     * Calcula tendencia lineal simple
     */
    private function calculate_trend($data, $value_key) {
        if (count($data) < 2) {
            return 0;
        }

        $n = count($data);
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_xx = 0;

        foreach ($data as $i => $row) {
            $x = $i;
            $y = (float) ($row[$value_key] ?? 0);
            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_xx += $x * $x;
        }

        $denominator = ($n * $sum_xx - $sum_x * $sum_x);
        if ($denominator == 0) {
            return 0;
        }

        return ($n * $sum_xy - $sum_x * $sum_y) / $denominator;
    }

    /**
     * Describe tendencia
     */
    private function describe_trend($metric, $slope) {
        if ($slope > 0.5) {
            return sprintf(__('Fuerte crecimiento en %s', 'flavor-chat-ia'), $metric);
        } elseif ($slope > 0) {
            return sprintf(__('Crecimiento moderado en %s', 'flavor-chat-ia'), $metric);
        } elseif ($slope < -0.5) {
            return sprintf(__('Fuerte descenso en %s', 'flavor-chat-ia'), $metric);
        } elseif ($slope < 0) {
            return sprintf(__('Descenso moderado en %s', 'flavor-chat-ia'), $metric);
        } else {
            return sprintf(__('%s estable', 'flavor-chat-ia'), ucfirst($metric));
        }
    }

    /**
     * Genera predicciones básicas
     */
    private function generate_predictions($data, $module) {
        $predictions = [];

        // Predicción de socios
        if (!empty($data['socios'])) {
            $rate = $data['socios']['nuevos_periodo'] / max(1, $data['period']);
            $predicted_monthly = round($rate * 30);
            $predictions['socios'] = [
                'next_month_new' => $predicted_monthly,
                'growth_rate_daily' => round($rate, 2),
                'confidence' => 'medium',
            ];
        }

        // Predicción de reservas
        if (!empty($data['reservas']['por_dia_semana'])) {
            $busiest_day = null;
            $max_reservations = 0;
            $day_names = ['', 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

            foreach ($data['reservas']['por_dia_semana'] as $row) {
                if ($row['cantidad'] > $max_reservations) {
                    $max_reservations = $row['cantidad'];
                    $busiest_day = $day_names[$row['dia']] ?? '';
                }
            }

            if ($busiest_day) {
                $predictions['reservas'] = [
                    'busiest_day' => $busiest_day,
                    'peak_reservations' => $max_reservations,
                    'recommendation' => sprintf(
                        __('Mayor demanda los %s. Considera aumentar disponibilidad.', 'flavor-chat-ia'),
                        $busiest_day
                    ),
                ];
            }
        }

        return [
            'type' => 'predictions',
            'period' => $data['period'],
            'predictions' => $predictions,
            'disclaimer' => __('Las predicciones son estimaciones basadas en datos históricos.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Encuentra correlaciones
     */
    private function find_correlations($data) {
        $correlations = [];

        // Correlación eventos-socios
        if (!empty($data['eventos']) && !empty($data['socios'])) {
            $correlations[] = [
                'metrics' => ['eventos', 'socios_nuevos'],
                'description' => __('Analizar si más eventos generan más altas de socios', 'flavor-chat-ia'),
            ];
        }

        return [
            'type' => 'correlations',
            'correlations' => $correlations,
        ];
    }

    /**
     * Analiza segmentos
     */
    private function analyze_segments($data, $module) {
        $segments = [];

        // Segmentación de socios
        if (!empty($data['socios']['por_tipo'])) {
            $segments['socios'] = [
                'by_type' => $data['socios']['por_tipo'],
                'total' => $data['socios']['total'],
            ];
        }

        // Segmentación de incidencias
        if (!empty($data['incidencias']['por_prioridad'])) {
            $segments['incidencias'] = [
                'by_priority' => $data['incidencias']['por_prioridad'],
                'by_status' => $data['incidencias']['por_estado'] ?? [],
            ];
        }

        return [
            'type' => 'segments',
            'segments' => $segments,
        ];
    }

    /**
     * Análisis general con IA
     */
    private function general_analysis($data, $module) {
        $engine = $this->get_engine();

        $summary = $this->format_data_for_analysis($data);
        $basic_insights = $this->generate_basic_insights($data);

        // Análisis IA si está disponible
        $ai_analysis = '';
        if ($engine && $engine->is_configured()) {
            $ai_analysis = $this->get_ai_analysis($summary);
        }

        return [
            'type' => 'general',
            'period' => $data['period'] . ' días',
            'summary' => $summary,
            'insights' => $basic_insights,
            'ai_analysis' => $ai_analysis,
        ];
    }

    /**
     * Formatea datos para análisis
     */
    private function format_data_for_analysis($data) {
        $lines = [];

        if (!empty($data['socios'])) {
            $lines[] = sprintf('Socios: %d total, %d nuevos, %d bajas',
                $data['socios']['total'] ?? 0,
                $data['socios']['nuevos_periodo'] ?? 0,
                $data['socios']['bajas_periodo'] ?? 0
            );
        }

        if (!empty($data['eventos'])) {
            $lines[] = sprintf('Eventos: %d realizados, %d participantes, %.0f%% ocupación media',
                $data['eventos']['total_periodo'] ?? 0,
                $data['eventos']['total_inscritos'] ?? 0,
                $data['eventos']['ocupacion_media'] ?? 0
            );
        }

        if (!empty($data['reservas'])) {
            $lines[] = sprintf('Reservas: %d en el periodo',
                $data['reservas']['total_periodo'] ?? 0
            );
        }

        if (!empty($data['incidencias'])) {
            $lines[] = sprintf('Incidencias: %d nuevas, %.1fh tiempo medio resolución',
                $data['incidencias']['total_periodo'] ?? 0,
                $data['incidencias']['tiempo_resolucion_medio'] ?? 0
            );
        }

        if (!empty($data['grupos_consumo'])) {
            $lines[] = sprintf('Grupos de consumo: %d pedidos, %.2f€ volumen, %.2f€ pedido medio',
                $data['grupos_consumo']['total_pedidos'] ?? 0,
                $data['grupos_consumo']['volumen_total'] ?? 0,
                $data['grupos_consumo']['pedido_medio'] ?? 0
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Genera insights básicos
     */
    private function generate_basic_insights($data) {
        $insights = [];

        // Insight de crecimiento de socios
        if (!empty($data['socios'])) {
            $net_growth = ($data['socios']['nuevos_periodo'] ?? 0) - ($data['socios']['bajas_periodo'] ?? 0);
            if ($net_growth > 0) {
                $insights[] = [
                    'type' => 'positive',
                    'icon' => '📈',
                    'text' => sprintf(__('Crecimiento neto de %d socios', 'flavor-chat-ia'), $net_growth),
                ];
            } elseif ($net_growth < 0) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => '📉',
                    'text' => sprintf(__('Pérdida neta de %d socios', 'flavor-chat-ia'), abs($net_growth)),
                ];
            }
        }

        // Insight de eventos
        if (!empty($data['eventos']) && ($data['eventos']['ocupacion_media'] ?? 0) < 50) {
            $insights[] = [
                'type' => 'suggestion',
                'icon' => '💡',
                'text' => __('La ocupación media de eventos es baja. Considera promocionar más.', 'flavor-chat-ia'),
            ];
        }

        // Insight de incidencias
        if (!empty($data['incidencias']) && ($data['incidencias']['tiempo_resolucion_medio'] ?? 0) > 72) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'text' => __('El tiempo de resolución de incidencias supera las 72h', 'flavor-chat-ia'),
            ];
        }

        return $insights;
    }

    /**
     * Obtiene análisis IA
     */
    private function get_ai_analysis($summary) {
        $engine = $this->get_engine();
        if (!$engine || !$engine->is_configured()) {
            return '';
        }

        $system_prompt = "Eres un analista de datos experto. Analiza los datos proporcionados y ofrece 2-3 insights accionables. Sé conciso y directo.";
        $user_prompt = "Analiza estos datos y proporciona insights clave:\n\n" . $summary;

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
            error_log('Flavor Data Analyzer AI Error: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Obtiene insights de un módulo
     */
    public function get_module_insights($module) {
        $data = $this->collect_data($module, 30);
        return $this->generate_basic_insights($data);
    }

    /**
     * Detecta anomalías en todos los datos
     */
    public function detect_all_anomalies() {
        $anomalies = [];
        $data = $this->collect_data('', 30);

        // Anomalía: demasiadas bajas de socios
        if (!empty($data['socios'])) {
            $nuevos = $data['socios']['nuevos_periodo'] ?? 0;
            $bajas = $data['socios']['bajas_periodo'] ?? 0;
            if ($bajas > $nuevos * 2 && $bajas > 5) {
                $anomalies[] = [
                    'severity' => 'high',
                    'module' => 'socios',
                    'description' => sprintf(
                        __('Anomalía: %d bajas vs %d altas en el periodo', 'flavor-chat-ia'),
                        $bajas, $nuevos
                    ),
                ];
            }
        }

        // Anomalía: incidencias sin resolver acumuladas
        if (!empty($data['incidencias']['por_estado'])) {
            $pendientes = 0;
            foreach ($data['incidencias']['por_estado'] as $estado) {
                if (!in_array($estado['estado'], ['cerrada', 'resuelta'])) {
                    $pendientes += $estado['cantidad'];
                }
            }
            if ($pendientes > 20) {
                $anomalies[] = [
                    'severity' => 'medium',
                    'module' => 'incidencias',
                    'description' => sprintf(
                        __('Anomalía: %d incidencias pendientes acumuladas', 'flavor-chat-ia'),
                        $pendientes
                    ),
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Verifica si tabla existe
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Data_Analyzer::get_instance();
});
