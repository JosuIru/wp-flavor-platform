<?php
/**
 * Sistema de Cache para Analytics
 *
 * Proporciona datos pre-calculados y cacheados para dashboards y consultas frecuentes
 * Reduce el consumo de tokens de IA al tener respuestas listas
 *
 * @package ChatIAAddon
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe
if (class_exists('Chat_IA_Analytics_Cache')) {
    return;
}

class Chat_IA_Analytics_Cache {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * TTL por defecto en segundos (1 hora)
     */
    const DEFAULT_TTL = 3600;

    /**
     * Prefijo para transients
     */
    const CACHE_PREFIX = 'chat_ia_analytics_';

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
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Programar cron para actualizar cache
        add_action('init', [$this, 'schedule_cache_refresh']);
        add_action('chat_ia_refresh_analytics_cache', [$this, 'refresh_all_cache']);

        // Invalidar cache cuando hay cambios relevantes
        add_action('calendario_experiencias_dias_actualizados', [$this, 'invalidate_calendar_cache']);
        add_action('woocommerce_order_status_completed', [$this, 'invalidate_sales_cache']);
        add_action('woocommerce_new_order', [$this, 'invalidate_sales_cache']);
    }

    /**
     * Programa el cron para refrescar cache
     */
    public function schedule_cache_refresh() {
        if (!wp_next_scheduled('chat_ia_refresh_analytics_cache')) {
            wp_schedule_event(time(), 'hourly', 'chat_ia_refresh_analytics_cache');
        }
    }

    /**
     * Refresca todo el cache
     */
    public function refresh_all_cache() {
        $this->compute_and_cache_dashboard();
        $this->compute_and_cache_comparisons();
        $this->compute_and_cache_trends();
        $this->compute_and_cache_alerts();
    }

    /**
     * Obtiene el dashboard cacheado
     *
     * @return array
     */
    public function get_cached_dashboard() {
        $cached = get_transient(self::CACHE_PREFIX . 'dashboard');

        if ($cached !== false) {
            $cached['_from_cache'] = true;
            return $cached;
        }

        return $this->compute_and_cache_dashboard();
    }

    /**
     * Calcula y cachea el dashboard
     *
     * @return array
     */
    public function compute_and_cache_dashboard() {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';

        $hoy = date('Y-m-d');
        $inicio_semana = date('Y-m-d', strtotime('monday this week'));
        $fin_semana = date('Y-m-d', strtotime('sunday this week'));
        $inicio_mes = date('Y-m-01');
        $fin_mes = date('Y-m-t');

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // KPIs de hoy
        $reservas_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_tickets}
             WHERE fecha = %s AND estado != 'cancelado'",
            $hoy
        ));

        // KPIs de semana
        $reservas_semana = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_tickets}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'",
            $inicio_semana, $fin_semana
        ));

        // KPIs de mes
        $reservas_mes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_tickets}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'",
            $inicio_mes, $fin_mes
        ));

        // Calcular ingresos estimados
        $ingresos_hoy = $this->calcular_ingresos_periodo($hoy, $hoy, $tipos);
        $ingresos_semana = $this->calcular_ingresos_periodo($inicio_semana, $fin_semana, $tipos);
        $ingresos_mes = $this->calcular_ingresos_periodo($inicio_mes, $fin_mes, $tipos);

        // Check-ins de hoy
        $checkins_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_tickets}
             WHERE fecha = %s AND checkin IS NOT NULL",
            $hoy
        ));

        // Disponibilidad actual (para hoy)
        $disponibilidad_hoy = $this->calcular_disponibilidad($hoy, $tipos);

        // Top tickets de la semana
        $top_tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla_tickets}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug
             ORDER BY cantidad DESC
             LIMIT 5",
            $inicio_semana, $fin_semana
        ), ARRAY_A);

        foreach ($top_tickets as &$ticket) {
            $slug = $ticket['ticket_slug'];
            $ticket['nombre'] = $tipos[$slug]['name'] ?? $slug;
        }

        $data = [
            'fecha_calculo' => current_time('mysql'),
            'kpis' => [
                'hoy' => [
                    'reservas' => intval($reservas_hoy),
                    'ingresos' => round($ingresos_hoy, 2),
                    'checkins' => intval($checkins_hoy),
                    'pendientes' => intval($reservas_hoy) - intval($checkins_hoy),
                ],
                'semana' => [
                    'reservas' => intval($reservas_semana),
                    'ingresos' => round($ingresos_semana, 2),
                ],
                'mes' => [
                    'reservas' => intval($reservas_mes),
                    'ingresos' => round($ingresos_mes, 2),
                ],
            ],
            'disponibilidad_hoy' => $disponibilidad_hoy,
            'top_tickets_semana' => $top_tickets,
            'ocupacion_media_hoy' => $this->calcular_ocupacion_media($disponibilidad_hoy),
        ];

        set_transient(self::CACHE_PREFIX . 'dashboard', $data, self::DEFAULT_TTL);

        return $data;
    }

    /**
     * Calcula ingresos para un periodo
     */
    private function calcular_ingresos_periodo($fecha_inicio, $fecha_fin, $tipos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug",
            $fecha_inicio, $fecha_fin
        ), ARRAY_A);

        $total = 0;
        foreach ($resultados as $row) {
            $slug = $row['ticket_slug'];
            $precio = floatval($tipos[$slug]['precio'] ?? 0);
            $total += $row['cantidad'] * $precio;
        }

        return $total;
    }

    /**
     * Calcula disponibilidad para una fecha
     */
    private function calcular_disponibilidad($fecha, $tipos) {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_limites = $wpdb->prefix . 'reservas_limites';

        $disponibilidad = [];

        foreach ($tipos as $slug => $tipo) {
            $plazas_base = intval($tipo['plazas'] ?? 0);

            // Verificar limite especial
            $limite_especial = $wpdb->get_var($wpdb->prepare(
                "SELECT plazas FROM {$tabla_limites} WHERE ticket_slug = %s AND fecha = %s",
                $slug, $fecha
            ));

            $plazas_totales = $limite_especial !== null ? intval($limite_especial) : $plazas_base;

            // Contar vendidas
            $vendidas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_tickets}
                 WHERE ticket_slug = %s AND fecha = %s AND estado != 'cancelado'",
                $slug, $fecha
            ));

            $libres = max(0, $plazas_totales - intval($vendidas));

            $disponibilidad[] = [
                'slug' => $slug,
                'nombre' => $tipo['name'] ?? $slug,
                'plazas_totales' => $plazas_totales,
                'vendidas' => intval($vendidas),
                'libres' => $libres,
                'porcentaje_ocupacion' => $plazas_totales > 0
                    ? round((intval($vendidas) / $plazas_totales) * 100, 1)
                    : 0,
            ];
        }

        return $disponibilidad;
    }

    /**
     * Calcula ocupacion media
     */
    private function calcular_ocupacion_media($disponibilidad) {
        $total_ocupacion = 0;
        $tipos_con_plazas = 0;

        foreach ($disponibilidad as $disp) {
            if ($disp['plazas_totales'] > 0) {
                $total_ocupacion += $disp['porcentaje_ocupacion'];
                $tipos_con_plazas++;
            }
        }

        return $tipos_con_plazas > 0 ? round($total_ocupacion / $tipos_con_plazas, 1) : 0;
    }

    /**
     * Obtiene comparativas cacheadas
     *
     * @return array
     */
    public function get_cached_comparisons() {
        $cached = get_transient(self::CACHE_PREFIX . 'comparisons');

        if ($cached !== false) {
            $cached['_from_cache'] = true;
            return $cached;
        }

        return $this->compute_and_cache_comparisons();
    }

    /**
     * Calcula y cachea comparativas
     *
     * @return array
     */
    public function compute_and_cache_comparisons() {
        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));

        $inicio_semana = date('Y-m-d', strtotime('monday this week'));
        $fin_semana = date('Y-m-d', strtotime('sunday this week'));
        $inicio_semana_ant = date('Y-m-d', strtotime('monday last week'));
        $fin_semana_ant = date('Y-m-d', strtotime('sunday last week'));

        $inicio_mes = date('Y-m-01');
        $fin_mes = date('Y-m-t');
        $inicio_mes_ant = date('Y-m-01', strtotime('first day of last month'));
        $fin_mes_ant = date('Y-m-t', strtotime('last day of last month'));

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Hoy vs Ayer
        $reservas_hoy = $this->contar_reservas_periodo($hoy, $hoy);
        $reservas_ayer = $this->contar_reservas_periodo($ayer, $ayer);
        $ingresos_hoy = $this->calcular_ingresos_periodo($hoy, $hoy, $tipos);
        $ingresos_ayer = $this->calcular_ingresos_periodo($ayer, $ayer, $tipos);

        // Semana vs Semana anterior
        $reservas_semana = $this->contar_reservas_periodo($inicio_semana, $fin_semana);
        $reservas_semana_ant = $this->contar_reservas_periodo($inicio_semana_ant, $fin_semana_ant);
        $ingresos_semana = $this->calcular_ingresos_periodo($inicio_semana, $fin_semana, $tipos);
        $ingresos_semana_ant = $this->calcular_ingresos_periodo($inicio_semana_ant, $fin_semana_ant, $tipos);

        // Mes vs Mes anterior
        $reservas_mes = $this->contar_reservas_periodo($inicio_mes, $fin_mes);
        $reservas_mes_ant = $this->contar_reservas_periodo($inicio_mes_ant, $fin_mes_ant);
        $ingresos_mes = $this->calcular_ingresos_periodo($inicio_mes, $fin_mes, $tipos);
        $ingresos_mes_ant = $this->calcular_ingresos_periodo($inicio_mes_ant, $fin_mes_ant, $tipos);

        $data = [
            'fecha_calculo' => current_time('mysql'),
            'hoy_vs_ayer' => $this->calcular_diferencias(
                $reservas_ayer, $reservas_hoy, $ingresos_ayer, $ingresos_hoy
            ),
            'semana_vs_anterior' => $this->calcular_diferencias(
                $reservas_semana_ant, $reservas_semana, $ingresos_semana_ant, $ingresos_semana
            ),
            'mes_vs_anterior' => $this->calcular_diferencias(
                $reservas_mes_ant, $reservas_mes, $ingresos_mes_ant, $ingresos_mes
            ),
        ];

        set_transient(self::CACHE_PREFIX . 'comparisons', $data, self::DEFAULT_TTL);

        return $data;
    }

    /**
     * Cuenta reservas para un periodo
     */
    private function contar_reservas_periodo($fecha_inicio, $fecha_fin) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'",
            $fecha_inicio, $fecha_fin
        )));
    }

    /**
     * Calcula diferencias entre periodos
     */
    private function calcular_diferencias($reservas_ant, $reservas_act, $ingresos_ant, $ingresos_act) {
        $diff_reservas = $reservas_act - $reservas_ant;
        $diff_ingresos = $ingresos_act - $ingresos_ant;

        return [
            'anterior' => [
                'reservas' => $reservas_ant,
                'ingresos' => round($ingresos_ant, 2),
            ],
            'actual' => [
                'reservas' => $reservas_act,
                'ingresos' => round($ingresos_act, 2),
            ],
            'diferencias' => [
                'reservas' => $diff_reservas,
                'reservas_porcentaje' => $reservas_ant > 0
                    ? round(($diff_reservas / $reservas_ant) * 100, 1)
                    : ($diff_reservas > 0 ? 100 : 0),
                'ingresos' => round($diff_ingresos, 2),
                'ingresos_porcentaje' => $ingresos_ant > 0
                    ? round(($diff_ingresos / $ingresos_ant) * 100, 1)
                    : ($diff_ingresos > 0 ? 100 : 0),
            ],
        ];
    }

    /**
     * Obtiene tendencias cacheadas
     *
     * @return array
     */
    public function get_cached_trends() {
        $cached = get_transient(self::CACHE_PREFIX . 'trends');

        if ($cached !== false) {
            $cached['_from_cache'] = true;
            return $cached;
        }

        return $this->compute_and_cache_trends();
    }

    /**
     * Calcula y cachea tendencias
     *
     * @return array
     */
    public function compute_and_cache_trends() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        // Ultimos 30 dias
        $inicio_30_dias = date('Y-m-d', strtotime('-30 days'));
        $hoy = date('Y-m-d');

        // Reservas por dia (ultimos 30 dias)
        $reservas_diarias = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY fecha
             ORDER BY fecha ASC",
            $inicio_30_dias, $hoy
        ), ARRAY_A);

        // Dia de la semana con mas reservas
        $dia_mas_reservas = $wpdb->get_row($wpdb->prepare(
            "SELECT DAYNAME(fecha) as dia_semana, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY DAYNAME(fecha)
             ORDER BY cantidad DESC
             LIMIT 1",
            $inicio_30_dias, $hoy
        ), ARRAY_A);

        // Ticket mas vendido
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $ticket_mas_vendido = $wpdb->get_row($wpdb->prepare(
            "SELECT ticket_slug, COUNT(*) as cantidad
             FROM {$tabla}
             WHERE fecha BETWEEN %s AND %s AND estado != 'cancelado'
             GROUP BY ticket_slug
             ORDER BY cantidad DESC
             LIMIT 1",
            $inicio_30_dias, $hoy
        ), ARRAY_A);

        if ($ticket_mas_vendido) {
            $slug = $ticket_mas_vendido['ticket_slug'];
            $ticket_mas_vendido['nombre'] = $tipos[$slug]['name'] ?? $slug;
        }

        // Calcular media diaria
        $total_dias = count($reservas_diarias);
        $total_reservas = array_sum(array_column($reservas_diarias, 'cantidad'));
        $media_diaria = $total_dias > 0 ? round($total_reservas / $total_dias, 1) : 0;

        $data = [
            'fecha_calculo' => current_time('mysql'),
            'periodo_analizado' => ['inicio' => $inicio_30_dias, 'fin' => $hoy],
            'reservas_diarias' => $reservas_diarias,
            'dia_mas_activo' => $dia_mas_reservas,
            'ticket_mas_vendido' => $ticket_mas_vendido,
            'media_diaria' => $media_diaria,
            'total_periodo' => $total_reservas,
        ];

        set_transient(self::CACHE_PREFIX . 'trends', $data, self::DEFAULT_TTL);

        return $data;
    }

    /**
     * Obtiene alertas cacheadas
     *
     * @return array
     */
    public function get_cached_alerts() {
        $cached = get_transient(self::CACHE_PREFIX . 'alerts');

        if ($cached !== false) {
            $cached['_from_cache'] = true;
            return $cached;
        }

        return $this->compute_and_cache_alerts();
    }

    /**
     * Calcula y cachea alertas
     *
     * @return array
     */
    public function compute_and_cache_alerts() {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';

        $alertas = [];
        $hoy = date('Y-m-d');
        $proximos_7_dias = date('Y-m-d', strtotime('+7 days'));

        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Dias casi llenos (>80% ocupacion)
        foreach ($tipos as $slug => $tipo) {
            $plazas = intval($tipo['plazas'] ?? 0);
            if ($plazas === 0) continue;

            $ocupacion = $wpdb->get_results($wpdb->prepare(
                "SELECT fecha, COUNT(*) as cantidad
                 FROM {$tabla_tickets}
                 WHERE ticket_slug = %s AND fecha BETWEEN %s AND %s AND estado != 'cancelado'
                 GROUP BY fecha
                 HAVING cantidad >= %d",
                $slug, $hoy, $proximos_7_dias, floor($plazas * 0.8)
            ), ARRAY_A);

            foreach ($ocupacion as $dia) {
                $porcentaje = round(($dia['cantidad'] / $plazas) * 100);
                $alertas[] = [
                    'tipo' => 'ocupacion_alta',
                    'nivel' => $porcentaje >= 100 ? 'critico' : 'advertencia',
                    'mensaje' => sprintf(
                        '%s al %d%% el %s',
                        $tipo['name'] ?? $slug,
                        $porcentaje,
                        $dia['fecha']
                    ),
                    'fecha' => $dia['fecha'],
                    'ticket' => $slug,
                ];
            }
        }

        // Tickets bloqueados
        $bloqueados = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_tickets} WHERE blocked = 1"
        );
        if ($bloqueados > 0) {
            $alertas[] = [
                'tipo' => 'tickets_bloqueados',
                'nivel' => 'info',
                'mensaje' => sprintf('%d ticket(s) bloqueado(s)', $bloqueados),
            ];
        }

        // Dias sin configurar (proximos 7 dias)
        $dias_config = get_option('calendario_experiencias_dias', []);
        $dias_sin_config = 0;
        $fecha_check = new DateTime($hoy);

        for ($i = 0; $i < 7; $i++) {
            $fecha_str = $fecha_check->format('Y-m-d');
            if (!isset($dias_config[$fecha_str])) {
                $dias_sin_config++;
            }
            $fecha_check->modify('+1 day');
        }

        if ($dias_sin_config > 0) {
            $alertas[] = [
                'tipo' => 'calendario',
                'nivel' => 'info',
                'mensaje' => sprintf('%d dia(s) sin estado configurado en los proximos 7 dias', $dias_sin_config),
            ];
        }

        $data = [
            'fecha_calculo' => current_time('mysql'),
            'total_alertas' => count($alertas),
            'alertas' => $alertas,
        ];

        // Cache mas corto para alertas (15 minutos)
        set_transient(self::CACHE_PREFIX . 'alerts', $data, 900);

        return $data;
    }

    /**
     * Invalida cache del calendario
     */
    public function invalidate_calendar_cache() {
        delete_transient(self::CACHE_PREFIX . 'dashboard');
        delete_transient(self::CACHE_PREFIX . 'alerts');
    }

    /**
     * Invalida cache de ventas
     */
    public function invalidate_sales_cache() {
        delete_transient(self::CACHE_PREFIX . 'dashboard');
        delete_transient(self::CACHE_PREFIX . 'comparisons');
        delete_transient(self::CACHE_PREFIX . 'trends');
    }

    /**
     * Invalida todo el cache
     */
    public function invalidate_all() {
        delete_transient(self::CACHE_PREFIX . 'dashboard');
        delete_transient(self::CACHE_PREFIX . 'comparisons');
        delete_transient(self::CACHE_PREFIX . 'trends');
        delete_transient(self::CACHE_PREFIX . 'alerts');
    }

    /**
     * Obtiene resumen compacto para shortcuts
     *
     * @param string $tipo Tipo de resumen: 'hoy', 'semana', 'mes'
     * @return array
     */
    public function get_quick_summary($tipo = 'hoy') {
        $dashboard = $this->get_cached_dashboard();

        switch ($tipo) {
            case 'semana':
                $kpis = $dashboard['kpis']['semana'] ?? [];
                $label = 'Esta semana';
                break;
            case 'mes':
                $kpis = $dashboard['kpis']['mes'] ?? [];
                $label = 'Este mes';
                break;
            default:
                $kpis = $dashboard['kpis']['hoy'] ?? [];
                $label = 'Hoy';
        }

        return [
            'success' => true,
            'label' => $label,
            'reservas' => $kpis['reservas'] ?? 0,
            'ingresos' => $kpis['ingresos'] ?? 0,
            'kpi_line' => sprintf(
                '%s: %d reservas | %.2f€',
                $label,
                $kpis['reservas'] ?? 0,
                $kpis['ingresos'] ?? 0
            ),
        ];
    }

    /**
     * Obtiene comparativa rapida para shortcuts
     *
     * @param string $tipo Tipo: 'hoy_vs_ayer', 'semana_vs_anterior', 'mes_vs_anterior'
     * @return array
     */
    public function get_quick_comparison($tipo) {
        $comparisons = $this->get_cached_comparisons();

        $data = $comparisons[$tipo] ?? null;

        if (!$data) {
            return ['success' => false, 'error' => __('Tipo de comparativa no valido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $labels = [
            'hoy_vs_ayer' => ['Ayer', 'Hoy'],
            'semana_vs_anterior' => ['Semana pasada', 'Esta semana'],
            'mes_vs_anterior' => ['Mes pasado', 'Este mes'],
        ];

        list($label_ant, $label_act) = $labels[$tipo] ?? ['Anterior', 'Actual'];

        $diff = $data['diferencias'];
        $signo = $diff['reservas'] >= 0 ? '+' : '';

        return [
            'success' => true,
            'tipo' => $tipo,
            'labels' => ['anterior' => $label_ant, 'actual' => $label_act],
            'data' => $data,
            'resumen' => sprintf(
                '%s vs %s: %s%d reservas (%s%.1f%%)',
                $label_act,
                $label_ant,
                $signo,
                $diff['reservas'],
                $signo,
                $diff['reservas_porcentaje']
            ),
        ];
    }
}
