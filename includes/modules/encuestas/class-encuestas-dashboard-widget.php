<?php
/**
 * Widget de Dashboard para Encuestas
 *
 * @package FlavorPlatform
 * @subpackage Modules\Encuestas
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Encuestas_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'encuestas';
    protected $icon = 'dashicons-forms';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 22;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_encuestas_';
        $this->title = __('Encuestas', 'flavor-platform');
        $this->description = __('Participa en las encuestas de la comunidad', 'flavor-platform');

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

    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $tabla_encuestas = $this->prefix_tabla . 'encuestas';
        $tabla_respuestas = $this->prefix_tabla . 'respuestas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $encuestas_activas = 0;
        if ($this->table_exists($tabla_encuestas)) {
            $encuestas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_encuestas}
                 WHERE estado = 'activa' AND (fecha_fin IS NULL OR fecha_fin >= NOW())"
            );
        }

        $mis_respuestas = 0;
        if ($user_id && $this->table_exists($tabla_respuestas)) {
            $mis_respuestas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT encuesta_id) FROM {$tabla_respuestas}
                 WHERE usuario_id = %d",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-forms',
                'valor' => $encuestas_activas,
                'label' => __('Activas', 'flavor-platform'),
                'color' => $encuestas_activas > 0 ? 'warning' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=encuestas') : Flavor_Platform_Helpers::get_action_url('encuestas', ''),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-yes-alt',
                'valor' => $mis_respuestas,
                'label' => __('Respondidas', 'flavor-platform'),
                'color' => $mis_respuestas > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=encuestas&tab=mis-respuestas') : Flavor_Platform_Helpers::get_action_url('encuestas', 'mis-respuestas'),
            ];
        }

        $items = $this->get_encuestas_activas(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay encuestas activas', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Ver encuestas', 'flavor-platform'),
                    'url' => $es_admin ? admin_url('admin.php?page=encuestas') : Flavor_Platform_Helpers::get_action_url('encuestas', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_encuestas_activas(int $limite): array {
        global $wpdb;
        $tabla_encuestas = $this->prefix_tabla . 'encuestas';

        if (!$this->table_exists($tabla_encuestas)) {
            return [];
        }

        $encuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_fin, total_respuestas
             FROM {$tabla_encuestas}
             WHERE estado = 'activa' AND (fecha_fin IS NULL OR fecha_fin >= NOW())
             ORDER BY fecha_creacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($encuestas as $encuesta) {
            $meta = '';
            if (!empty($encuesta->fecha_fin)) {
                $meta = sprintf(__('Cierra: %s', 'flavor-platform'), date_i18n('j M', strtotime($encuesta->fecha_fin)));
            }

            $items[] = [
                'icon' => 'dashicons-clipboard',
                'title' => wp_trim_words($encuesta->titulo, 5, '...'),
                'meta' => $meta,
                'url' => $es_admin ? admin_url('admin.php?page=encuestas&encuesta=' . $encuesta->id) : Flavor_Platform_Helpers::get_action_url('encuestas', 'responder') . '/' . $encuesta->id . '/',
                'badge' => isset($encuesta->total_respuestas) && $encuesta->total_respuestas > 0 ? $encuesta->total_respuestas . ' resp.' : null,
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
