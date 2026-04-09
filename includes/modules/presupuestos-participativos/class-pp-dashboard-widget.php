<?php
/**
 * Widget de Dashboard para Presupuestos Participativos
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_PP_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'presupuestos-participativos';
    protected $icon = 'dashicons-chart-pie';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 18;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_pp_';
        $this->title = __('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Decide cómo se invierte en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN);

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 300,
            'description' => $this->description,
        ]);
    }

    public function get_widget_data(): array {
        $user_id = get_current_user_id();
        return $this->get_cached_data(function() use ($user_id) {
            return $this->fetch_widget_data($user_id);
        });
    }

    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $tabla_presupuestos = $this->prefix_tabla . 'presupuestos';
        $tabla_proyectos = $this->prefix_tabla . 'proyectos';
        $tabla_votos = $this->prefix_tabla . 'votos';
        $es_admin = is_admin() && !wp_doing_ajax();

        // Presupuesto activo
        $presupuesto_activo = null;
        if ($this->table_exists($tabla_presupuestos)) {
            $presupuesto_activo = $wpdb->get_row(
                "SELECT id, titulo, importe_total, fase
                 FROM {$tabla_presupuestos}
                 WHERE estado = 'activo'
                 ORDER BY fecha_inicio DESC LIMIT 1"
            );
        }

        $proyectos_en_votacion = 0;
        if ($presupuesto_activo && $this->table_exists($tabla_proyectos)) {
            $proyectos_en_votacion = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_proyectos}
                 WHERE presupuesto_id = %d AND estado = 'en_votacion'",
                $presupuesto_activo->id
            ));
        }

        $mis_votos = 0;
        if ($user_id && $presupuesto_activo && $this->table_exists($tabla_votos)) {
            $mis_votos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_votos} v
                 INNER JOIN {$tabla_proyectos} p ON v.proyecto_id = p.id
                 WHERE p.presupuesto_id = %d AND v.usuario_id = %d",
                $presupuesto_activo->id,
                $user_id
            ));
        }

        $stats = [];

        if ($presupuesto_activo) {
            $stats[] = [
                'icon' => 'dashicons-chart-pie',
                'valor' => number_format($presupuesto_activo->importe_total, 0, ',', '.') . ' €',
                'label' => __('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=presupuestos-participativos') : Flavor_Chat_Helpers::get_action_url('presupuestos_participativos', ''),
            ];

            $fase_texto = [
                'propuestas' => __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'votacion' => __('Votación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ejecucion' => __('Ejecución', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cerrado' => __('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

            $stats[] = [
                'icon' => 'dashicons-flag',
                'valor' => $fase_texto[$presupuesto_activo->fase] ?? $presupuesto_activo->fase,
                'label' => __('Fase actual', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $presupuesto_activo->fase === 'votacion' ? 'warning' : 'info',
            ];

            if ($proyectos_en_votacion > 0) {
                $stats[] = [
                    'icon' => 'dashicons-lightbulb',
                    'valor' => $proyectos_en_votacion,
                    'label' => __('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'success',
                    'url' => $es_admin ? admin_url('admin.php?page=presupuestos-participativos&tab=proyectos') : Flavor_Chat_Helpers::get_action_url('presupuestos_participativos', 'proyectos'),
                ];
            }
        } else {
            $stats[] = [
                'icon' => 'dashicons-info',
                'valor' => __('Sin proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'label' => __('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'gray',
            ];
        }

        $items = $presupuesto_activo ? $this->get_proyectos_destacados($presupuesto_activo->id, 4) : [];

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay proceso de presupuestos activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=presupuestos-participativos') : Flavor_Chat_Helpers::get_action_url('presupuestos_participativos', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_proyectos_destacados(int $presupuesto_id, int $limite): array {
        global $wpdb;
        $tabla_proyectos = $this->prefix_tabla . 'proyectos';

        if (!$this->table_exists($tabla_proyectos)) {
            return [];
        }

        $proyectos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, coste_estimado, total_votos
             FROM {$tabla_proyectos}
             WHERE presupuesto_id = %d AND estado = 'en_votacion'
             ORDER BY total_votos DESC, fecha_creacion DESC LIMIT %d",
            $presupuesto_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($proyectos as $proyecto) {
            $items[] = [
                'icon' => 'dashicons-lightbulb',
                'title' => wp_trim_words($proyecto->titulo, 5, '...'),
                'meta' => number_format($proyecto->coste_estimado, 0, ',', '.') . ' €',
                'url' => $es_admin ? admin_url('admin.php?page=presupuestos-participativos&proyecto=' . $proyecto->id) : Flavor_Chat_Helpers::get_action_url('presupuestos_participativos', 'proyecto') . '/' . $proyecto->id . '/',
                'badge' => $proyecto->total_votos > 0 ? $proyecto->total_votos . ' votos' : null,
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

    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
