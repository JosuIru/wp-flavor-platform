<?php
/**
 * Widget de Dashboard para Radio
 *
 * @package FlavorPlatform
 * @subpackage Modules\Radio
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Radio_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'radio';
    protected $icon = 'dashicons-controls-volumeon';
    protected $size = 'medium';
    protected $category = 'comunicacion';
    protected $priority = 38;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_radio_';
        $this->title = __('Radio', 'flavor-platform');
        $this->description = __('Radio comunitaria en vivo', 'flavor-platform');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 60,
            'description' => $this->description,
        ]);
    }

    public function get_widget_data(): array {
        return $this->get_cached_data(function() {
            return $this->fetch_widget_data();
        });
    }

    private function fetch_widget_data(): array {
        global $wpdb;

        $tabla_programas = $this->prefix_tabla . 'programas';
        $tabla_emisiones = $this->prefix_tabla . 'emisiones';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_programas = 0;
        if ($this->table_exists($tabla_programas)) {
            $total_programas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_programas} WHERE estado = 'activo'"
            );
        }

        $emision_actual = null;
        if ($this->table_exists($tabla_emisiones)) {
            $emision_actual = $wpdb->get_row(
                "SELECT e.*, p.titulo as programa_titulo
                 FROM {$tabla_emisiones} e
                 LEFT JOIN {$tabla_programas} p ON e.programa_id = p.id
                 WHERE e.estado = 'en_vivo'
                 ORDER BY e.fecha_inicio DESC LIMIT 1"
            );
        }

        $stats = [];

        if ($emision_actual) {
            $stats[] = [
                'icon' => 'dashicons-controls-play',
                'valor' => __('EN VIVO', 'flavor-platform'),
                'label' => wp_trim_words($emision_actual->programa_titulo, 2, '...'),
                'color' => 'success',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-radio-dashboard') : Flavor_Platform_Helpers::get_action_url('radio', ''),
            ];
        } else {
            $stats[] = [
                'icon' => 'dashicons-controls-volumeon',
                'valor' => __('Sin emisión', 'flavor-platform'),
                'label' => __('Radio', 'flavor-platform'),
                'color' => 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-radio-dashboard') : Flavor_Platform_Helpers::get_action_url('radio', ''),
            ];
        }

        $stats[] = [
            'icon' => 'dashicons-playlist-audio',
            'valor' => $total_programas,
            'label' => __('Programas', 'flavor-platform'),
            'color' => $total_programas > 0 ? 'info' : 'gray',
            'url' => $es_admin ? admin_url('admin.php?page=flavor-radio-programas') : Flavor_Platform_Helpers::get_action_url('radio', 'programas'),
        ];

        $items = $this->get_programas_destacados(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay programas de radio disponibles', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Escuchar radio', 'flavor-platform'),
                    'url' => $es_admin ? admin_url('admin.php?page=flavor-radio-dashboard') : Flavor_Platform_Helpers::get_action_url('radio', ''),
                    'icon' => 'dashicons-controls-play',
                ],
            ],
        ];
    }

    private function get_programas_destacados(int $limite): array {
        global $wpdb;
        $tabla_programas = $this->prefix_tabla . 'programas';

        if (!$this->table_exists($tabla_programas)) {
            return [];
        }

        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, descripcion, horario
             FROM {$tabla_programas}
             WHERE estado = 'activo'
             ORDER BY destacado DESC, titulo ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($programas as $programa) {
            $items[] = [
                'icon' => 'dashicons-microphone',
                'title' => wp_trim_words($programa->titulo, 4, '...'),
                'meta' => $programa->horario ?: '',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-radio-programas') : Flavor_Platform_Helpers::get_action_url('radio', 'programa') . '/' . $programa->id . '/',
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
