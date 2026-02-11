<?php
/**
 * Sistema de Monitoreo de Tokens
 *
 * Rastrea el consumo de tokens de IA vs atajos directos
 * para medir el ahorro real del sistema de shortcuts
 *
 * @package ChatIAAddon
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Token_Monitor {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Opción donde se guardan las estadísticas
     */
    const STATS_OPTION = 'chat_ia_token_stats';

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
        // AJAX para obtener estadísticas
        add_action('wp_ajax_chat_ia_get_token_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_chat_ia_reset_token_stats', [$this, 'ajax_reset_stats']);
    }

    /**
     * Registra uso de tokens de IA
     *
     * @param int $input_tokens Tokens de entrada
     * @param int $output_tokens Tokens de salida
     * @param string $context Contexto (admin_assistant, frontend_chat, etc)
     */
    public function log_ai_usage($input_tokens, $output_tokens, $context = 'general') {
        $stats = $this->get_stats();
        $today = date('Y-m-d');

        // Inicializar día si no existe
        if (!isset($stats['daily'][$today])) {
            $stats['daily'][$today] = $this->get_empty_day_stats();
        }

        // Incrementar contadores
        $stats['daily'][$today]['ai_calls']++;
        $stats['daily'][$today]['input_tokens'] += $input_tokens;
        $stats['daily'][$today]['output_tokens'] += $output_tokens;
        $stats['daily'][$today]['total_tokens'] += ($input_tokens + $output_tokens);

        // Por contexto
        if (!isset($stats['daily'][$today]['by_context'][$context])) {
            $stats['daily'][$today]['by_context'][$context] = [
                'calls' => 0,
                'tokens' => 0,
            ];
        }
        $stats['daily'][$today]['by_context'][$context]['calls']++;
        $stats['daily'][$today]['by_context'][$context]['tokens'] += ($input_tokens + $output_tokens);

        // Totales
        $stats['totals']['ai_calls']++;
        $stats['totals']['input_tokens'] += $input_tokens;
        $stats['totals']['output_tokens'] += $output_tokens;
        $stats['totals']['total_tokens'] += ($input_tokens + $output_tokens);

        $this->save_stats($stats);
    }

    /**
     * Registra uso de atajo directo (sin IA)
     *
     * @param string $shortcut_id ID del atajo
     * @param int $estimated_tokens Tokens estimados que se habrían usado con IA
     */
    public function log_shortcut_usage($shortcut_id, $estimated_tokens = 400) {
        $stats = $this->get_stats();
        $today = date('Y-m-d');

        // Inicializar día si no existe
        if (!isset($stats['daily'][$today])) {
            $stats['daily'][$today] = $this->get_empty_day_stats();
        }

        // Incrementar contadores
        $stats['daily'][$today]['shortcut_calls']++;
        $stats['daily'][$today]['tokens_saved'] += $estimated_tokens;

        // Por shortcut
        if (!isset($stats['daily'][$today]['by_shortcut'][$shortcut_id])) {
            $stats['daily'][$today]['by_shortcut'][$shortcut_id] = 0;
        }
        $stats['daily'][$today]['by_shortcut'][$shortcut_id]++;

        // Totales
        $stats['totals']['shortcut_calls']++;
        $stats['totals']['tokens_saved'] += $estimated_tokens;

        $this->save_stats($stats);
    }

    /**
     * Registra uso de caché (sin IA)
     *
     * @param string $cache_key Clave del caché usado
     * @param int $estimated_tokens Tokens estimados que se habrían usado
     */
    public function log_cache_hit($cache_key, $estimated_tokens = 300) {
        $stats = $this->get_stats();
        $today = date('Y-m-d');

        // Inicializar día si no existe
        if (!isset($stats['daily'][$today])) {
            $stats['daily'][$today] = $this->get_empty_day_stats();
        }

        // Incrementar contadores
        $stats['daily'][$today]['cache_hits']++;
        $stats['daily'][$today]['tokens_saved'] += $estimated_tokens;

        // Totales
        $stats['totals']['cache_hits']++;
        $stats['totals']['tokens_saved'] += $estimated_tokens;

        $this->save_stats($stats);
    }

    /**
     * Obtiene estadísticas
     */
    public function get_stats() {
        $stats = get_option(self::STATS_OPTION, null);

        if ($stats === null) {
            $stats = $this->get_empty_stats();
            $this->save_stats($stats);
        }

        // Limpiar datos antiguos (más de 30 días)
        $stats = $this->cleanup_old_data($stats);

        return $stats;
    }

    /**
     * Guarda estadísticas
     */
    private function save_stats($stats) {
        $stats['last_updated'] = current_time('mysql');
        update_option(self::STATS_OPTION, $stats);
    }

    /**
     * Estructura vacía de estadísticas
     */
    private function get_empty_stats() {
        return [
            'started_at' => current_time('mysql'),
            'last_updated' => current_time('mysql'),
            'totals' => [
                'ai_calls' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'shortcut_calls' => 0,
                'cache_hits' => 0,
                'tokens_saved' => 0,
            ],
            'daily' => [],
        ];
    }

    /**
     * Estructura vacía para un día
     */
    private function get_empty_day_stats() {
        return [
            'ai_calls' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'shortcut_calls' => 0,
            'cache_hits' => 0,
            'tokens_saved' => 0,
            'by_context' => [],
            'by_shortcut' => [],
        ];
    }

    /**
     * Limpia datos de más de 30 días
     */
    private function cleanup_old_data($stats) {
        $cutoff = date('Y-m-d', strtotime('-30 days'));

        foreach ($stats['daily'] as $date => $data) {
            if ($date < $cutoff) {
                unset($stats['daily'][$date]);
            }
        }

        return $stats;
    }

    /**
     * Obtiene resumen para mostrar
     */
    public function get_summary() {
        $stats = $this->get_stats();
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));

        $summary = [
            'totals' => $stats['totals'],
            'today' => $stats['daily'][$today] ?? $this->get_empty_day_stats(),
            'week' => $this->get_period_stats($stats, $week_start, $today),
            'efficiency' => $this->calculate_efficiency($stats['totals']),
            'cost_estimate' => $this->estimate_cost($stats['totals']),
        ];

        return $summary;
    }

    /**
     * Obtiene estadísticas para un período
     */
    private function get_period_stats($stats, $start, $end) {
        $period_stats = $this->get_empty_day_stats();

        $current = new DateTime($start);
        $end_date = new DateTime($end);

        while ($current <= $end_date) {
            $date = $current->format('Y-m-d');
            if (isset($stats['daily'][$date])) {
                $day = $stats['daily'][$date];
                $period_stats['ai_calls'] += $day['ai_calls'];
                $period_stats['input_tokens'] += $day['input_tokens'];
                $period_stats['output_tokens'] += $day['output_tokens'];
                $period_stats['total_tokens'] += $day['total_tokens'];
                $period_stats['shortcut_calls'] += $day['shortcut_calls'];
                $period_stats['cache_hits'] += $day['cache_hits'];
                $period_stats['tokens_saved'] += $day['tokens_saved'];
            }
            $current->modify('+1 day');
        }

        return $period_stats;
    }

    /**
     * Calcula eficiencia del sistema
     */
    private function calculate_efficiency($totals) {
        $total_actions = $totals['ai_calls'] + $totals['shortcut_calls'] + $totals['cache_hits'];

        if ($total_actions === 0) {
            return [
                'shortcut_ratio' => 0,
                'cache_ratio' => 0,
                'ai_ratio' => 0,
                'tokens_saved_pct' => 0,
            ];
        }

        $potential_tokens = $totals['total_tokens'] + $totals['tokens_saved'];
        $saved_pct = $potential_tokens > 0 ? round(($totals['tokens_saved'] / $potential_tokens) * 100, 1) : 0;

        return [
            'shortcut_ratio' => round(($totals['shortcut_calls'] / $total_actions) * 100, 1),
            'cache_ratio' => round(($totals['cache_hits'] / $total_actions) * 100, 1),
            'ai_ratio' => round(($totals['ai_calls'] / $total_actions) * 100, 1),
            'tokens_saved_pct' => $saved_pct,
        ];
    }

    /**
     * Estima costo basado en precios de Claude
     * Precios aproximados para Claude 3.5 Sonnet
     */
    private function estimate_cost($totals) {
        // Precios Claude 3.5 Sonnet (USD por millón de tokens)
        $input_price = 3.00;  // $3 per 1M input tokens
        $output_price = 15.00; // $15 per 1M output tokens

        $input_cost = ($totals['input_tokens'] / 1000000) * $input_price;
        $output_cost = ($totals['output_tokens'] / 1000000) * $output_price;
        $total_cost = $input_cost + $output_cost;

        // Costo ahorrado (estimado como promedio input/output)
        $avg_price = ($input_price + $output_price) / 2;
        $saved_cost = ($totals['tokens_saved'] / 1000000) * $avg_price;

        return [
            'spent_usd' => round($total_cost, 4),
            'saved_usd' => round($saved_cost, 4),
            'potential_usd' => round($total_cost + $saved_cost, 4),
        ];
    }

    /**
     * AJAX: Obtiene estadísticas
     */
    public function ajax_get_stats() {
        check_ajax_referer('chat_ia_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        wp_send_json_success($this->get_summary());
    }

    /**
     * AJAX: Resetea estadísticas
     */
    public function ajax_reset_stats() {
        check_ajax_referer('chat_ia_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        delete_option(self::STATS_OPTION);
        wp_send_json_success(['message' => __('Estadísticas reseteadas', 'chat-ia-addon')]);
    }

    /**
     * Genera HTML para el panel de estadísticas
     */
    public function render_stats_panel() {
        $summary = $this->get_summary();
        $efficiency = $summary['efficiency'];
        $cost = $summary['cost_estimate'];

        ob_start();
        ?>
        <div class="token-monitor-panel">
            <h3><?php _e('📊 Monitoreo de Tokens', 'chat-ia-addon'); ?></h3>

            <div class="token-stats-grid">
                <!-- Hoy -->
                <div class="stat-card">
                    <h4><?php _e('Hoy', 'chat-ia-addon'); ?></h4>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Llamadas IA:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value"><?php echo number_format($summary['today']['ai_calls']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Atajos directos:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success"><?php echo number_format($summary['today']['shortcut_calls']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Cache hits:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success"><?php echo number_format($summary['today']['cache_hits']); ?></span>
                    </div>
                    <div class="stat-row highlight">
                        <span class="stat-label"><?php _e('Tokens ahorrados:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success"><?php echo number_format($summary['today']['tokens_saved']); ?></span>
                    </div>
                </div>

                <!-- Eficiencia -->
                <div class="stat-card">
                    <h4><?php _e('Eficiencia', 'chat-ia-addon'); ?></h4>
                    <div class="efficiency-bar">
                        <div class="bar-segment ai" style="width: <?php echo $efficiency['ai_ratio']; ?>%"
                             title="<?php echo esc_attr(sprintf(__('IA: %s%%', 'chat-ia-addon'), $efficiency['ai_ratio'])); ?>">
                        </div>
                        <div class="bar-segment shortcuts" style="width: <?php echo $efficiency['shortcut_ratio']; ?>%"
                             title="<?php echo esc_attr(sprintf(__('Atajos: %s%%', 'chat-ia-addon'), $efficiency['shortcut_ratio'])); ?>">
                        </div>
                        <div class="bar-segment cache" style="width: <?php echo $efficiency['cache_ratio']; ?>%"
                             title="<?php echo esc_attr(sprintf(__('Cache: %s%%', 'chat-ia-addon'), $efficiency['cache_ratio'])); ?>">
                        </div>
                    </div>
                    <div class="efficiency-legend">
                        <span class="legend-item"><span class="dot ai"></span> IA (<?php echo $efficiency['ai_ratio']; ?>%)</span>
                        <span class="legend-item"><span class="dot shortcuts"></span> Atajos (<?php echo $efficiency['shortcut_ratio']; ?>%)</span>
                        <span class="legend-item"><span class="dot cache"></span> Cache (<?php echo $efficiency['cache_ratio']; ?>%)</span>
                    </div>
                    <div class="stat-row highlight">
                        <span class="stat-label"><?php _e('Tokens ahorrados:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success"><?php echo $efficiency['tokens_saved_pct']; ?>%</span>
                    </div>
                </div>

                <!-- Costos -->
                <div class="stat-card">
                    <h4><?php _e('Costo Estimado (USD)', 'chat-ia-addon'); ?></h4>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Gastado:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value">$<?php echo number_format($cost['spent_usd'], 4); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Ahorrado:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success">$<?php echo number_format($cost['saved_usd'], 4); ?></span>
                    </div>
                    <div class="stat-row highlight">
                        <span class="stat-label"><?php _e('Sin optimización:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value">$<?php echo number_format($cost['potential_usd'], 4); ?></span>
                    </div>
                </div>

                <!-- Totales -->
                <div class="stat-card">
                    <h4><?php _e('Totales (30 días)', 'chat-ia-addon'); ?></h4>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Tokens usados:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value"><?php echo number_format($summary['totals']['total_tokens']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Tokens ahorrados:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value text-success"><?php echo number_format($summary['totals']['tokens_saved']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><?php _e('Total acciones:', 'chat-ia-addon'); ?></span>
                        <span class="stat-value"><?php
                            echo number_format(
                                $summary['totals']['ai_calls'] +
                                $summary['totals']['shortcut_calls'] +
                                $summary['totals']['cache_hits']
                            );
                        ?></span>
                    </div>
                </div>
            </div>

            <div class="token-monitor-footer">
                <small><?php _e('Última actualización:', 'chat-ia-addon'); ?> <?php echo esc_html($this->get_stats()['last_updated']); ?></small>
                <button type="button" class="button button-small" id="reset-token-stats">
                    <?php _e('Resetear', 'chat-ia-addon'); ?>
                </button>
            </div>
        </div>

        <style>
        .token-monitor-panel {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .token-monitor-panel h3 {
            margin: 0 0 15px;
            font-size: 14px;
        }
        .token-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
        }
        .stat-card h4 {
            margin: 0 0 10px;
            font-size: 13px;
            color: #1e3a5f;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
        }
        .stat-row.highlight {
            border-top: 1px solid #e0e0e0;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: 600;
        }
        .stat-value { font-weight: 500; }
        .text-success { color: #28a745; }
        .efficiency-bar {
            display: flex;
            height: 20px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        .bar-segment {
            transition: width 0.3s;
        }
        .bar-segment.ai { background: #dc3545; }
        .bar-segment.shortcuts { background: #28a745; }
        .bar-segment.cache { background: #17a2b8; }
        .efficiency-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .legend-item { display: flex; align-items: center; gap: 4px; }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .dot.ai { background: #dc3545; }
        .dot.shortcuts { background: #28a745; }
        .dot.cache { background: #17a2b8; }
        .token-monitor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
