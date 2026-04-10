<?php
/**
 * Gestor de Estadísticas para Email Marketing
 *
 * Maneja todos los cálculos y consultas de estadísticas.
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Stats_Manager {

    /**
     * Prefijo de tablas
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Instancia singleton
     * @var self|null
     */
    private static $instance = null;

    /**
     * Cache de estadísticas
     * @var array
     */
    private $cache = [];

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
     * Obtener resumen general de estadísticas
     *
     * @param string $period Período (today, week, month, year, all)
     * @return array
     */
    public function get_summary($period = 'month') {
        $cache_key = "summary_{$period}";

        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        global $wpdb;

        $date_filter = $this->get_date_filter($period);

        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Estadísticas de suscriptores
        $subscribers = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'baja' THEN 1 ELSE 0 END) as bajas
             FROM {$prefix}suscriptores",
            ARRAY_A
        );

        // Nuevos suscriptores en el período
        $new_subscribers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}suscriptores WHERE created_at >= %s",
            $date_filter
        ));

        // Estadísticas de campañas
        $campaigns = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'enviada' THEN 1 ELSE 0 END) as enviadas
             FROM {$prefix}campanias WHERE created_at >= %s",
            $date_filter
        ), ARRAY_A);

        // Estadísticas de envíos
        $sends = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos,
                SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as fallidos
             FROM {$prefix}envios WHERE created_at >= %s",
            $date_filter
        ), ARRAY_A);

        // Estadísticas de eventos (aperturas, clicks)
        $events = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(CASE WHEN tipo = 'apertura' THEN 1 ELSE 0 END) as aperturas,
                SUM(CASE WHEN tipo = 'click' THEN 1 ELSE 0 END) as clicks,
                SUM(CASE WHEN tipo = 'baja' THEN 1 ELSE 0 END) as bajas
             FROM {$prefix}eventos WHERE created_at >= %s",
            $date_filter
        ), ARRAY_A);

        $total_enviados = (int) ($sends['exitosos'] ?? 0);
        $aperturas = (int) ($events['aperturas'] ?? 0);
        $clicks = (int) ($events['clicks'] ?? 0);

        $summary = [
            'subscribers' => [
                'total' => (int) ($subscribers['total'] ?? 0),
                'active' => (int) ($subscribers['activos'] ?? 0),
                'unsubscribed' => (int) ($subscribers['bajas'] ?? 0),
                'new_in_period' => (int) $new_subscribers,
            ],
            'campaigns' => [
                'total' => (int) ($campaigns['total'] ?? 0),
                'sent' => (int) ($campaigns['enviadas'] ?? 0),
            ],
            'emails' => [
                'sent' => $total_enviados,
                'failed' => (int) ($sends['fallidos'] ?? 0),
                'opens' => $aperturas,
                'clicks' => $clicks,
                'unsubscribes' => (int) ($events['bajas'] ?? 0),
            ],
            'rates' => [
                'open_rate' => $total_enviados > 0 ? round(($aperturas / $total_enviados) * 100, 2) : 0,
                'click_rate' => $total_enviados > 0 ? round(($clicks / $total_enviados) * 100, 2) : 0,
                'unsubscribe_rate' => $total_enviados > 0 ? round(((int) ($events['bajas'] ?? 0) / $total_enviados) * 100, 2) : 0,
            ],
            'period' => $period,
            'generated_at' => current_time('mysql'),
        ];

        $this->cache[$cache_key] = $summary;

        return $summary;
    }

    /**
     * Obtener estadísticas por día
     *
     * @param string $period Período
     * @param string $metric Métrica (sends, opens, clicks, subscribers)
     * @return array
     */
    public function get_daily_stats($period = 'month', $metric = 'sends') {
        global $wpdb;

        $date_filter = $this->get_date_filter($period);
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        switch ($metric) {
            case 'opens':
                $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                        FROM {$prefix}eventos
                        WHERE tipo = 'apertura' AND created_at >= %s
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                break;

            case 'clicks':
                $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                        FROM {$prefix}eventos
                        WHERE tipo = 'click' AND created_at >= %s
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                break;

            case 'subscribers':
                $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                        FROM {$prefix}suscriptores
                        WHERE created_at >= %s
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                break;

            case 'unsubscribes':
                $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                        FROM {$prefix}eventos
                        WHERE tipo = 'baja' AND created_at >= %s
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                break;

            case 'sends':
            default:
                $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                        FROM {$prefix}envios
                        WHERE estado = 'enviado' AND created_at >= %s
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                break;
        }

        $results = $wpdb->get_results($wpdb->prepare($sql, $date_filter), ARRAY_A);

        // Rellenar días sin datos
        $filled = $this->fill_date_gaps($results, $period);

        return [
            'metric' => $metric,
            'period' => $period,
            'data' => $filled,
        ];
    }

    /**
     * Obtener mejores campañas
     *
     * @param int    $limit Límite de resultados
     * @param string $order_by Ordenar por (open_rate, click_rate, total_sent)
     * @return array
     */
    public function get_top_campaigns($limit = 10, $order_by = 'open_rate') {
        global $wpdb;

        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        $campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT
                c.id,
                c.nombre,
                c.asunto,
                c.enviada_at,
                COUNT(DISTINCT e.id) as total_sent,
                COUNT(DISTINCT CASE WHEN ev.tipo = 'apertura' THEN ev.id END) as opens,
                COUNT(DISTINCT CASE WHEN ev.tipo = 'click' THEN ev.id END) as clicks
             FROM {$prefix}campanias c
             LEFT JOIN {$prefix}envios e ON c.id = e.campania_id AND e.estado = 'enviado'
             LEFT JOIN {$prefix}eventos ev ON c.id = ev.campania_id
             WHERE c.estado = 'enviada'
             GROUP BY c.id
             HAVING total_sent > 0
             ORDER BY
                CASE WHEN %s = 'open_rate' THEN (opens / total_sent) END DESC,
                CASE WHEN %s = 'click_rate' THEN (clicks / total_sent) END DESC,
                CASE WHEN %s = 'total_sent' THEN total_sent END DESC
             LIMIT %d",
            $order_by,
            $order_by,
            $order_by,
            $limit
        ), ARRAY_A);

        foreach ($campaigns as &$campaign) {
            $total = (int) $campaign['total_sent'];
            $campaign['open_rate'] = $total > 0 ? round(($campaign['opens'] / $total) * 100, 2) : 0;
            $campaign['click_rate'] = $total > 0 ? round(($campaign['clicks'] / $total) * 100, 2) : 0;
        }

        return $campaigns;
    }

    /**
     * Obtener estadísticas de listas
     *
     * @return array
     */
    public function get_lists_stats() {
        global $wpdb;

        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        return $wpdb->get_results(
            "SELECT
                l.id,
                l.nombre,
                COUNT(DISTINCT sl.suscriptor_id) as total,
                SUM(CASE WHEN s.estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN sl.fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as nuevos_semana
             FROM {$prefix}listas l
             LEFT JOIN {$prefix}suscriptor_lista sl ON l.id = sl.lista_id
             LEFT JOIN {$prefix}suscriptores s ON sl.suscriptor_id = s.id
             GROUP BY l.id
             ORDER BY total DESC",
            ARRAY_A
        );
    }

    /**
     * Obtener tasa de crecimiento de suscriptores
     *
     * @param string $period Período actual
     * @return float Porcentaje de crecimiento
     */
    public function get_subscriber_growth_rate($period = 'month') {
        global $wpdb;

        $prefix = $wpdb->prefix . self::TABLE_PREFIX;
        $current_date = $this->get_date_filter($period);
        $previous_date = $this->get_date_filter($period, true);

        // Suscriptores en período actual
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}suscriptores WHERE created_at >= %s",
            $current_date
        ));

        // Suscriptores en período anterior
        $previous = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}suscriptores WHERE created_at >= %s AND created_at < %s",
            $previous_date,
            $current_date
        ));

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Obtener filtro de fecha según período
     *
     * @param string $period   Período
     * @param bool   $previous Si obtener el período anterior
     * @return string
     */
    private function get_date_filter($period, $previous = false) {
        $multiplier = $previous ? 2 : 1;

        switch ($period) {
            case 'today':
                $interval = $multiplier . ' DAY';
                break;
            case 'week':
                $interval = ($multiplier * 7) . ' DAY';
                break;
            case 'month':
                $interval = $multiplier . ' MONTH';
                break;
            case 'quarter':
                $interval = ($multiplier * 3) . ' MONTH';
                break;
            case 'year':
                $interval = $multiplier . ' YEAR';
                break;
            case 'all':
            default:
                return '1970-01-01 00:00:00';
        }

        return date('Y-m-d H:i:s', strtotime("-{$interval}"));
    }

    /**
     * Rellenar huecos en datos diarios
     *
     * @param array  $data   Datos originales
     * @param string $period Período
     * @return array
     */
    private function fill_date_gaps($data, $period) {
        $start_date = $this->get_date_filter($period);
        $end_date = current_time('Y-m-d');

        // Convertir datos a array asociativo por fecha
        $data_by_date = [];
        foreach ($data as $row) {
            $data_by_date[$row['date']] = (int) $row['count'];
        }

        // Generar todas las fechas del rango
        $filled = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($current <= $end) {
            $date_str = $current->format('Y-m-d');
            $filled[] = [
                'date' => $date_str,
                'count' => $data_by_date[$date_str] ?? 0,
            ];
            $current->modify('+1 day');
        }

        return $filled;
    }

    /**
     * Limpiar cache
     */
    public function clear_cache() {
        $this->cache = [];
    }

    /**
     * Obtener comparativa con período anterior
     *
     * @param string $period Período
     * @return array
     */
    public function get_comparison($period = 'month') {
        $current = $this->get_summary($period);

        // Calcular métricas del período anterior
        global $wpdb;

        $current_start = $this->get_date_filter($period);
        $previous_start = $this->get_date_filter($period, true);
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Envíos período anterior
        $previous_sends = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}envios
             WHERE estado = 'enviado' AND created_at >= %s AND created_at < %s",
            $previous_start,
            $current_start
        ));

        // Aperturas período anterior
        $previous_opens = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}eventos
             WHERE tipo = 'apertura' AND created_at >= %s AND created_at < %s",
            $previous_start,
            $current_start
        ));

        $current_sends = $current['emails']['sent'];
        $current_opens = $current['emails']['opens'];

        return [
            'current' => $current,
            'changes' => [
                'sends' => $this->calculate_change($current_sends, $previous_sends),
                'opens' => $this->calculate_change($current_opens, $previous_opens),
                'subscribers' => $this->get_subscriber_growth_rate($period),
            ],
        ];
    }

    /**
     * Calcular cambio porcentual
     *
     * @param int $current  Valor actual
     * @param int $previous Valor anterior
     * @return float
     */
    private function calculate_change($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
