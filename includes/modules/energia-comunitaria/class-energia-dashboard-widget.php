<?php
/**
 * Widget de Dashboard para Energia Comunitaria
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Energia_Comunitaria_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'energia-comunitaria';
    protected $title;
    protected $icon = 'dashicons-lightbulb';
    protected $size = 'medium';
    protected $category = 'sostenibilidad';
    protected $priority = 36;

    public function __construct() {
        $this->title = __('Energia Comunitaria', 'flavor-chat-ia');
        $this->description = __('Produccion, consumo, incidencias y comunidades energeticas activas.', 'flavor-chat-ia');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 180,
            'description' => $this->description,
        ]);
    }

    public function get_widget_data(): array {
        return $this->get_cached_data(function () {
            return $this->fetch_widget_data();
        });
    }

    public function get_widget_config(): array {
        $config = parent::get_widget_config();
        $severity = $this->get_native_severity_payload($this->get_widget_data());

        if (!empty($severity['slug'])) {
            $config['severity_slug'] = $severity['slug'];
            $config['severity_label'] = $severity['label'];
            $config['severity_reason'] = $severity['reason'];
        }

        return $config;
    }

    private function fetch_widget_data(): array {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';
        $tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
        $tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';

        $comunidades_activas = 0;
        $instalaciones_activas = 0;
        $autosuficiencia = 0;
        $incidencias_abiertas = 0;
        $items = [];

        if ($this->table_exists($tabla_comunidades)) {
            $comunidades_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comunidades} WHERE estado = 'activa'");

            $items = $wpdb->get_results(
                "SELECT nombre, tipo_instalacion_principal, potencia_kw, modelo_reparto
                 FROM {$tabla_comunidades}
                 ORDER BY created_at DESC
                 LIMIT 5",
                ARRAY_A
            );
        }

        if ($this->table_exists($tabla_instalaciones)) {
            $instalaciones_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_instalaciones} WHERE estado = 'activa'");
        }

        if ($this->table_exists($tabla_incidencias)) {
            $incidencias_abiertas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_incidencias} WHERE estado IN ('abierta', 'en_progreso')");
        }

        if ($this->table_exists($tabla_lecturas)) {
            $totales = $wpdb->get_row(
                "SELECT
                    COALESCE(SUM(energia_generada_kwh), 0) AS generada,
                    COALESCE(SUM(energia_consumida_kwh), 0) AS consumida
                 FROM {$tabla_lecturas}
                 WHERE DATE_FORMAT(fecha_lectura, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
            );

            $generada = (float) ($totales->generada ?? 0);
            $consumida = (float) ($totales->consumida ?? 0);

            if ($consumida > 0) {
                $autosuficiencia = round(($generada / $consumida) * 100, 1);
            }
        }

        $stats = [
            [
                'icon' => 'dashicons-groups',
                'valor' => $comunidades_activas,
                'label' => __('Comunidades', 'flavor-chat-ia'),
                'color' => $comunidades_activas > 0 ? 'success' : 'gray',
                'url' => $this->get_context_url('/mi-portal/energia-comunitaria/comunidades/', 'energia-comunitaria'),
            ],
            [
                'icon' => 'dashicons-admin-tools',
                'valor' => $instalaciones_activas,
                'label' => __('Instalaciones', 'flavor-chat-ia'),
                'color' => $instalaciones_activas > 0 ? 'primary' : 'gray',
                'url' => $this->get_context_url('/mi-portal/energia-comunitaria/instalaciones/', 'energia-comunitaria'),
            ],
            [
                'icon' => 'dashicons-chart-pie',
                'valor' => $autosuficiencia . '%',
                'label' => __('Autosuficiencia', 'flavor-chat-ia'),
                'color' => $autosuficiencia >= 70 ? 'success' : 'warning',
                'url' => $this->get_context_url('/mi-portal/energia-comunitaria/balance/', 'energia-comunitaria'),
            ],
            [
                'icon' => 'dashicons-warning',
                'valor' => $incidencias_abiertas,
                'label' => __('Incidencias', 'flavor-chat-ia'),
                'color' => $incidencias_abiertas > 0 ? 'danger' : 'success',
                'url' => $this->get_context_url('/mi-portal/energia-comunitaria/mantenimiento/', 'energia-comunitaria'),
            ],
        ];

        $items_formateados = array_map(function (array $item): array {
            return [
                'icon' => 'dashicons-lightbulb',
                'title' => $item['nombre'] ?? '',
                'meta' => trim(($item['tipo_instalacion_principal'] ?? '') . ' · ' . number_format_i18n((float) ($item['potencia_kw'] ?? 0), 1) . ' kW'),
                'badge' => $item['modelo_reparto'] ?? '',
                'url' => $this->get_context_url('/mi-portal/energia-comunitaria/comunidades/', 'energia-comunitaria'),
            ];
        }, $items);

        return [
            'stats' => $stats,
            'items' => $items_formateados,
            'summary' => [
                'comunidades_activas' => $comunidades_activas,
                'instalaciones_activas' => $instalaciones_activas,
                'autosuficiencia' => $autosuficiencia,
                'incidencias_abiertas' => $incidencias_abiertas,
            ],
            'empty_state' => __('Todavia no hay comunidades energeticas registradas', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Abrir energia comunitaria', 'flavor-chat-ia'),
                    'url' => $this->get_context_url('/mi-portal/energia-comunitaria/', 'energia-comunitaria'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Devuelve severidad nativa del widget segun el estado real del modulo.
     *
     * @param array $data
     * @return array{slug:string,label:string,reason:string}
     */
    private function get_native_severity_payload(array $data): array {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $incidencias_abiertas = (int) ($summary['incidencias_abiertas'] ?? 0);
        $autosuficiencia = (float) ($summary['autosuficiencia'] ?? 0);
        $instalaciones_activas = (int) ($summary['instalaciones_activas'] ?? 0);
        $comunidades_activas = (int) ($summary['comunidades_activas'] ?? 0);

        if ($incidencias_abiertas > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('attention');
            $severity['reason'] = __('Hay incidencias energéticas abiertas que requieren seguimiento operativo.', 'flavor-chat-ia');
            return $severity;
        }

        if ($instalaciones_activas > 0 && $autosuficiencia > 0 && $autosuficiencia < 60) {
            $severity = Flavor_Dashboard_Severity::get_payload('followup');
            $severity['reason'] = __('La autosuficiencia del periodo actual está por debajo del nivel deseable.', 'flavor-chat-ia');
            return $severity;
        }

        if ($comunidades_activas > 0 || $instalaciones_activas > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('stable');
            $severity['reason'] = __('El ecosistema energético está activo y sin alertas operativas inmediatas.', 'flavor-chat-ia');
            return $severity;
        }

        $severity = Flavor_Dashboard_Severity::get_payload('stable');
        $severity['reason'] = __('Todavía no hay actividad suficiente para elevar prioridad en este módulo.', 'flavor-chat-ia');
        return $severity;
    }
}
