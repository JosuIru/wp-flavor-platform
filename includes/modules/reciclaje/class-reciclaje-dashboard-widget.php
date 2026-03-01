<?php
/**
 * Widget de Dashboard para Reciclaje
 *
 * @package FlavorChatIA
 * @subpackage Modules\Reciclaje
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Reciclaje_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'reciclaje';
    protected $icon = 'dashicons-update';
    protected $size = 'medium';
    protected $category = 'sostenibilidad';
    protected $priority = 42;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_reciclaje_';
        $this->title = __('Reciclaje', 'flavor-chat-ia');
        $this->description = __('Puntos de reciclaje y recogidas', 'flavor-chat-ia');

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

        $tabla_puntos = $this->prefix_tabla . 'puntos';
        $tabla_recogidas = $this->prefix_tabla . 'recogidas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_puntos = 0;
        if ($this->table_exists($tabla_puntos)) {
            $total_puntos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_puntos} WHERE estado = 'activo'"
            );
        }

        $proxima_recogida = null;
        if ($this->table_exists($tabla_recogidas)) {
            $proxima_recogida = $wpdb->get_row(
                "SELECT fecha, tipo, zona
                 FROM {$tabla_recogidas}
                 WHERE fecha >= CURDATE() AND estado = 'programada'
                 ORDER BY fecha ASC LIMIT 1"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-location-alt',
                'valor' => $total_puntos,
                'label' => __('Puntos limpios', 'flavor-chat-ia'),
                'color' => $total_puntos > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=reciclaje') : home_url('/mi-portal/reciclaje/puntos/'),
            ],
        ];

        if ($proxima_recogida) {
            $fecha_formateada = date_i18n('j M', strtotime($proxima_recogida->fecha));
            $stats[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $fecha_formateada,
                'label' => $proxima_recogida->tipo ?: __('Próx. recogida', 'flavor-chat-ia'),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=reciclaje&tab=recogidas') : home_url('/mi-portal/reciclaje/calendario/'),
            ];
        }

        $items = $this->get_puntos_cercanos(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay puntos de reciclaje registrados', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver mapa', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=reciclaje') : home_url('/mi-portal/reciclaje/'),
                    'icon' => 'dashicons-location',
                ],
            ],
        ];
    }

    private function get_puntos_cercanos(int $limite): array {
        global $wpdb;
        $tabla_puntos = $this->prefix_tabla . 'puntos';

        if (!$this->table_exists($tabla_puntos)) {
            return [];
        }

        $puntos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, direccion, tipo
             FROM {$tabla_puntos}
             WHERE estado = 'activo'
             ORDER BY nombre ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($puntos as $punto) {
            $items[] = [
                'icon' => 'dashicons-location-alt',
                'title' => wp_trim_words($punto->nombre, 4, '...'),
                'meta' => $punto->tipo ?: $punto->direccion,
                'url' => $es_admin ? admin_url('admin.php?page=reciclaje&punto=' . $punto->id) : home_url('/mi-portal/reciclaje/punto/' . $punto->id . '/'),
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
