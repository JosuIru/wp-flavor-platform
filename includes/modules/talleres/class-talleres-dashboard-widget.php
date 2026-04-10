<?php
/**
 * Widget de Dashboard para Talleres
 *
 * @package FlavorPlatform
 * @subpackage Modules\Talleres
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Talleres_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'talleres';
    protected $icon = 'dashicons-hammer';
    protected $size = 'medium';
    protected $category = 'actividades';
    protected $priority = 25;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_talleres_';
        $this->title = __('Talleres', 'flavor-platform');
        $this->description = __('Talleres prácticos de la comunidad', 'flavor-platform');

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

        $tabla_talleres = $this->prefix_tabla . 'talleres';
        $es_admin = is_admin() && !wp_doing_ajax();

        $talleres_proximos = 0;
        if ($this->table_exists($tabla_talleres)) {
            $talleres_proximos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_talleres}
                 WHERE estado = 'programado' AND fecha >= CURDATE()"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-hammer',
                'valor' => $talleres_proximos,
                'label' => __('Talleres próximos', 'flavor-platform'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=talleres') : Flavor_Platform_Helpers::get_action_url('talleres', ''),
            ],
        ];

        $items = $this->get_talleres_proximos(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay talleres programados', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Ver talleres', 'flavor-platform'),
                    'url' => $es_admin ? admin_url('admin.php?page=talleres') : Flavor_Platform_Helpers::get_action_url('talleres', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_talleres_proximos(int $limite): array {
        global $wpdb;
        $tabla_talleres = $this->prefix_tabla . 'talleres';

        if (!$this->table_exists($tabla_talleres)) {
            return [];
        }

        $talleres = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha, lugar FROM {$tabla_talleres}
             WHERE estado = 'programado' AND fecha >= CURDATE()
             ORDER BY fecha ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($talleres as $taller) {
            $items[] = [
                'icon' => 'dashicons-hammer',
                'title' => wp_trim_words($taller->titulo, 5, '...'),
                'meta' => date_i18n('j M, H:i', strtotime($taller->fecha)),
                'url' => $es_admin ? admin_url('admin.php?page=talleres&id=' . $taller->id) : Flavor_Platform_Helpers::get_action_url('talleres', 'ver') . '/' . $taller->id . '/',
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
