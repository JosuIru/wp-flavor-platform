<?php
/**
 * Widget de Dashboard para Espacios Comunes
 *
 * @package FlavorPlatform
 * @subpackage Modules\EspaciosComunes
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Espacios_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'espacios-comunes';
    protected $icon = 'dashicons-building';
    protected $size = 'medium';
    protected $category = 'recursos';
    protected $priority = 10;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_espacios_';
        $this->title = __('Espacios', 'flavor-platform');
        $this->description = __('Reserva de espacios comunitarios', 'flavor-platform');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 120,
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

        $tabla_espacios = $this->prefix_tabla . 'espacios';
        $tabla_reservas = $this->prefix_tabla . 'reservas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $espacios_disponibles = 0;
        if ($this->table_exists($tabla_espacios)) {
            $espacios_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_espacios} WHERE estado = 'activo'"
            );
        }

        $mis_reservas = 0;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $mis_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE usuario_id = %d AND fecha_inicio >= CURDATE()
                 AND estado IN ('confirmada', 'pendiente')",
                $user_id
            ));
        }

        $proxima_reserva = null;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $proxima_reserva = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, e.nombre as espacio_nombre
                 FROM {$tabla_reservas} r
                 LEFT JOIN {$tabla_espacios} e ON r.espacio_id = e.id
                 WHERE r.usuario_id = %d AND r.fecha_inicio >= CURDATE()
                 AND r.estado = 'confirmada'
                 ORDER BY r.fecha_inicio ASC LIMIT 1",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-building',
                'valor' => $espacios_disponibles,
                'label' => __('Espacios', 'flavor-platform'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=espacios-comunes') : Flavor_Platform_Helpers::get_action_url('espacios_comunes', ''),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $mis_reservas,
                'label' => __('Mis reservas', 'flavor-platform'),
                'color' => $mis_reservas > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=espacios-comunes&tab=mis-reservas') : Flavor_Platform_Helpers::get_action_url('espacios_comunes', 'mis-reservas'),
            ];
        }

        if ($proxima_reserva) {
            $fecha = date_i18n('j M', strtotime($proxima_reserva->fecha_inicio));
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $fecha,
                'label' => wp_trim_words($proxima_reserva->espacio_nombre, 2, '...'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=espacios-comunes&reserva=' . $proxima_reserva->id) : Flavor_Platform_Helpers::get_action_url('espacios_comunes', 'reserva') . '/' . $proxima_reserva->id . '/',
            ];
        }

        $items = $this->get_espacios_disponibles(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay espacios disponibles', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Reservar espacio', 'flavor-platform'),
                    'url' => $es_admin ? admin_url('admin.php?page=espacios-comunes&action=reservar') : Flavor_Platform_Helpers::get_action_url('espacios_comunes', 'reservar'),
                    'icon' => 'dashicons-plus-alt2',
                ],
            ],
        ];
    }

    private function get_espacios_disponibles(int $limite): array {
        global $wpdb;
        $tabla_espacios = $this->prefix_tabla . 'espacios';

        if (!$this->table_exists($tabla_espacios)) {
            return [];
        }

        $espacios = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, capacidad, ubicacion
             FROM {$tabla_espacios}
             WHERE estado = 'activo'
             ORDER BY nombre ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($espacios as $espacio) {
            $capacidad = $espacio->capacidad > 0 ? sprintf(__('%d pers.', 'flavor-platform'), $espacio->capacidad) : '';

            $items[] = [
                'icon' => 'dashicons-building',
                'title' => $espacio->nombre,
                'meta' => $capacidad ?: $espacio->ubicacion,
                'url' => $es_admin ? admin_url('admin.php?page=espacios-comunes&espacio=' . $espacio->id) : Flavor_Platform_Helpers::get_action_url('espacios_comunes', 'espacio') . '/' . $espacio->id . '/',
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
