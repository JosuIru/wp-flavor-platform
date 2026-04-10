<?php
/**
 * Analytics del chat
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Analytics {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Platform_Analytics
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
    private function __construct() {}

    /**
     * Obtiene estadísticas generales
     *
     * @param string $period 'today', 'week', 'month', 'all'
     * @return array
     */
    public function get_stats($period = 'week') {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_chat_conversations';

        $date_filter = $this->get_date_filter($period);

        // Total conversaciones
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE 1=1 {$date_filter}"
        );

        // Escaladas
        $escalated = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE escalated = 1 {$date_filter}"
        );

        // Conversiones
        $conversions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE conversion_type IS NOT NULL {$date_filter}"
        );

        // Valor de conversiones
        $conversion_value = $wpdb->get_var(
            "SELECT SUM(conversion_value) FROM {$table} WHERE conversion_value > 0 {$date_filter}"
        );

        // Promedio de mensajes
        $avg_messages = $wpdb->get_var(
            "SELECT AVG(message_count) FROM {$table} WHERE message_count > 0 {$date_filter}"
        );

        return [
            'total_conversations' => intval($total),
            'escalated' => intval($escalated),
            'escalation_rate' => $total > 0 ? round(($escalated / $total) * 100, 1) : 0,
            'conversions' => intval($conversions),
            'conversion_rate' => $total > 0 ? round(($conversions / $total) * 100, 1) : 0,
            'conversion_value' => floatval($conversion_value),
            'avg_messages' => round(floatval($avg_messages), 1),
        ];
    }

    /**
     * Obtiene filtro de fecha SQL
     *
     * @param string $period
     * @return string
     */
    private function get_date_filter($period) {
        switch ($period) {
            case 'today':
                return "AND DATE(started_at) = CURDATE()";
            case 'week':
                return "AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'month':
                return "AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            default:
                return "";
        }
    }

    /**
     * Obtiene conversaciones por día
     *
     * @param int $days
     * @return array
     */
    public function get_conversations_by_day($days = 7) {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_chat_conversations';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(started_at) as date, COUNT(*) as count
             FROM {$table}
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(started_at)
             ORDER BY date ASC",
            $days
        ), ARRAY_A);
    }

    /**
     * Obtiene las preguntas más frecuentes
     *
     * @param int $limit
     * @return array
     */
    public function get_top_questions($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_chat_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT content, COUNT(*) as count
             FROM {$table}
             WHERE role = 'user'
             AND LENGTH(content) > 10
             AND LENGTH(content) < 200
             GROUP BY content
             ORDER BY count DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
    }
}

if (!class_exists('Flavor_Chat_Analytics', false)) {
    class_alias('Flavor_Platform_Analytics', 'Flavor_Chat_Analytics');
}
