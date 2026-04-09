<?php
/**
 * Utilidades comunes de severidad para dashboards y herramientas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dashboard_Severity {

    /**
     * Traduce un slug de severidad a etiqueta visible.
     *
     * @param string $severity
     * @return string
     */
    public static function get_label($severity) {
        $labels = [
            'attention' => __('Atención', 'flavor-platform'),
            'followup' => __('Seguimiento', 'flavor-platform'),
            'stable' => __('Estable', 'flavor-platform'),
        ];

        $severity = sanitize_key((string) $severity);
        return $labels[$severity] ?? $labels['stable'];
    }

    /**
     * Devuelve slug y etiqueta normalizados.
     *
     * @param string $severity
     * @return array{slug:string,label:string}
     */
    public static function get_payload($severity) {
        $severity = sanitize_key((string) $severity);
        if (!in_array($severity, ['attention', 'followup', 'stable'], true)) {
            $severity = 'stable';
        }

        return [
            'slug' => $severity,
            'label' => self::get_label($severity),
        ];
    }

    /**
     * Calcula severidad a partir de una herramienta del portal.
     *
     * @param array $tool
     * @return string
     */
    public static function from_tool(array $tool) {
        $kind = (string) ($tool['kind'] ?? '');
        $score = (int) ($tool['score'] ?? 0);

        if ($kind === 'operar' || $score >= 88) {
            return 'attention';
        }

        if ($kind === 'coordinar' || $score >= 76) {
            return 'followup';
        }

        return 'stable';
    }

    /**
     * Calcula severidad a partir del tipo de notificacion.
     *
     * @param string $type
     * @return string
     */
    public static function from_notification_type($type) {
        $type = (string) $type;

        if (in_array($type, ['error', 'warning'], true)) {
            return 'attention';
        }

        if ($type === 'info') {
            return 'followup';
        }

        return 'stable';
    }

    /**
     * Calcula severidad por cercania temporal.
     *
     * @param string $date
     * @param string $fallback
     * @return string
     */
    public static function from_date($date, $fallback = 'followup') {
        $timestamp = $date ? strtotime((string) $date) : 0;

        if (!$timestamp) {
            return sanitize_key((string) $fallback) ?: 'followup';
        }

        $diff = $timestamp - current_time('timestamp');

        if ($diff <= DAY_IN_SECONDS) {
            return 'attention';
        }

        if ($diff <= (3 * DAY_IN_SECONDS)) {
            return 'followup';
        }

        return 'stable';
    }

    /**
     * Calcula severidad para acciones ejecutivas del dashboard admin.
     *
     * @param string $action_kind
     * @return string
     */
    public static function from_admin_action($action_kind) {
        $action_kind = sanitize_key((string) $action_kind);

        if (in_array($action_kind, ['focus', 'complete'], true)) {
            return 'attention';
        }

        if ($action_kind === 'review') {
            return 'followup';
        }

        return 'stable';
    }
}
