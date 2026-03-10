<?php
/**
 * Widget de Dashboard para Participación
 *
 * @package FlavorChatIA
 * @subpackage Modules\Participacion
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Participacion_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'participacion';
    protected $icon = 'dashicons-megaphone';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 15;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_participacion_';
        $this->title = __('Participación', 'flavor-chat-ia');
        $this->description = __('Votaciones y debates de la comunidad', 'flavor-chat-ia');

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
        $user_id = get_current_user_id();
        return $this->get_cached_data(function() use ($user_id) {
            return $this->fetch_widget_data($user_id);
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

    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $tabla_votaciones = $this->prefix_tabla . 'votaciones';
        $tabla_votos = $this->prefix_tabla . 'votos';
        $tabla_propuestas = $this->prefix_tabla . 'propuestas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $votaciones_activas = 0;
        if ($this->table_exists($tabla_votaciones)) {
            $votaciones_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_votaciones}
                 WHERE estado = 'activa' AND fecha_fin >= NOW()"
            );
        }

        $mis_votos = 0;
        if ($user_id && $this->table_exists($tabla_votos)) {
            $mis_votos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_votos}
                 WHERE usuario_id = %d AND MONTH(fecha_voto) = MONTH(NOW())",
                $user_id
            ));
        }

        $propuestas_abiertas = 0;
        if ($this->table_exists($tabla_propuestas)) {
            $propuestas_abiertas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_propuestas}
                 WHERE estado = 'abierta'"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-chart-bar',
                'valor' => $votaciones_activas,
                'label' => __('Votaciones', 'flavor-chat-ia'),
                'color' => $votaciones_activas > 0 ? 'warning' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=participacion') : home_url('/mi-portal/participacion/votaciones/'),
            ],
            [
                'icon' => 'dashicons-lightbulb',
                'valor' => $propuestas_abiertas,
                'label' => __('Propuestas', 'flavor-chat-ia'),
                'color' => $propuestas_abiertas > 0 ? 'info' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=participacion&tab=propuestas') : home_url('/mi-portal/participacion/propuestas/'),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-thumbs-up',
                'valor' => $mis_votos,
                'label' => __('Mis votos', 'flavor-chat-ia'),
                'color' => $mis_votos > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=participacion&tab=mis-votos') : home_url('/mi-portal/participacion/votaciones/'),
            ];
        }

        $items = $this->get_votaciones_activas(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'summary' => [
                'votaciones_activas' => $votaciones_activas,
                'propuestas_abiertas' => $propuestas_abiertas,
                'mis_votos' => $mis_votos,
            ],
            'empty_state' => __('No hay votaciones activas en este momento', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Participar', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=participacion') : home_url('/mi-portal/participacion/votaciones/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_votaciones_activas(int $limite): array {
        global $wpdb;
        $tabla_votaciones = $this->prefix_tabla . 'votaciones';

        if (!$this->table_exists($tabla_votaciones)) {
            return [];
        }

        $votaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_fin, total_votos
             FROM {$tabla_votaciones}
             WHERE estado = 'activa' AND fecha_fin >= NOW()
             ORDER BY fecha_fin ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($votaciones as $votacion) {
            $tiempo_restante = human_time_diff(current_time('timestamp'), strtotime($votacion->fecha_fin));

            $items[] = [
                'icon' => 'dashicons-chart-bar',
                'title' => wp_trim_words($votacion->titulo, 5, '...'),
                'meta' => sprintf(__('Cierra en %s', 'flavor-chat-ia'), $tiempo_restante),
                'url' => $es_admin ? admin_url('admin.php?page=participacion&votacion=' . $votacion->id) : add_query_arg('encuesta_id', $votacion->id, home_url('/mi-portal/participacion/encuesta/')),
                'badge' => $votacion->total_votos > 0 ? $votacion->total_votos . ' votos' : null,
            ];
        }

        return $items;
    }

    private function table_exists(string $nombre_tabla): bool {
        global $wpdb;
        static $cache = [];
        if (isset($cache[$nombre_tabla])) {
            return $cache[$nombre_tabla];
        }
        $resultado = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $nombre_tabla));
        $cache[$nombre_tabla] = ($resultado === $nombre_tabla);
        return $cache[$nombre_tabla];
    }

    /**
     * Devuelve severidad nativa del widget segun actividad participativa real.
     *
     * @param array $data
     * @return array{slug:string,label:string,reason:string}
     */
    private function get_native_severity_payload(array $data): array {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $votaciones_activas = (int) ($summary['votaciones_activas'] ?? 0);
        $propuestas_abiertas = (int) ($summary['propuestas_abiertas'] ?? 0);
        $mis_votos = (int) ($summary['mis_votos'] ?? 0);

        if ($votaciones_activas > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('attention');
            $severity['reason'] = __('Hay votaciones activas abiertas y conviene atenderlas antes de que cierren.', 'flavor-chat-ia');
            return $severity;
        }

        if ($propuestas_abiertas > 0 || $mis_votos > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('followup');
            $severity['reason'] = __('Hay propuestas o actividad participativa reciente que conviene revisar.', 'flavor-chat-ia');
            return $severity;
        }

        $severity = Flavor_Dashboard_Severity::get_payload('stable');
        $severity['reason'] = __('No hay procesos participativos urgentes abiertos en este momento.', 'flavor-chat-ia');
        return $severity;
    }

    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
