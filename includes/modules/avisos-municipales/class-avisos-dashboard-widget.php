<?php
/**
 * Widget de Dashboard para Avisos Municipales
 *
 * @package FlavorChatIA
 * @subpackage Modules\AvisosMunicipales
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Avisos_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'avisos-municipales';
    protected $icon = 'dashicons-megaphone';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 5;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_avisos_';
        $this->title = __('Avisos', 'flavor-chat-ia');
        $this->description = __('Avisos y comunicados municipales', 'flavor-chat-ia');

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
        return $this->get_cached_data(function() {
            return $this->fetch_widget_data();
        });
    }

    private function fetch_widget_data(): array {
        global $wpdb;

        $tabla_avisos = $this->prefix_tabla . 'avisos';
        $es_admin = is_admin() && !wp_doing_ajax();

        $avisos_activos = 0;
        $avisos_urgentes = 0;

        if ($this->table_exists($tabla_avisos)) {
            $avisos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_avisos}
                 WHERE estado = 'publicado' AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())"
            );
            $avisos_urgentes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_avisos}
                 WHERE estado = 'publicado' AND urgente = 1
                 AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-megaphone',
                'valor' => $avisos_activos,
                'label' => __('Avisos activos', 'flavor-chat-ia'),
                'color' => $avisos_activos > 0 ? 'info' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=avisos-municipales') : home_url('/mi-portal/avisos/'),
            ],
        ];

        if ($avisos_urgentes > 0) {
            $stats[] = [
                'icon' => 'dashicons-warning',
                'valor' => $avisos_urgentes,
                'label' => __('Urgentes', 'flavor-chat-ia'),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=avisos-municipales&urgente=1') : home_url('/mi-portal/avisos/?urgente=1'),
            ];
        }

        $items = $this->get_avisos_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay avisos activos', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver todos', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=avisos-municipales') : home_url('/mi-portal/avisos/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_avisos_recientes(int $limite): array {
        global $wpdb;
        $tabla_avisos = $this->prefix_tabla . 'avisos';

        if (!$this->table_exists($tabla_avisos)) {
            return [];
        }

        $avisos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, urgente, fecha_publicacion
             FROM {$tabla_avisos}
             WHERE estado = 'publicado' AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             ORDER BY urgente DESC, fecha_publicacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($avisos as $aviso) {
            $items[] = [
                'icon' => $aviso->urgente ? 'dashicons-warning' : 'dashicons-megaphone',
                'title' => wp_trim_words($aviso->titulo, 5, '...'),
                'meta' => human_time_diff(strtotime($aviso->fecha_publicacion)),
                'url' => $es_admin ? admin_url('admin.php?page=avisos-municipales&aviso=' . $aviso->id) : home_url('/mi-portal/avisos/aviso/' . $aviso->id . '/'),
                'badge' => $aviso->urgente ? __('Urgente', 'flavor-chat-ia') : null,
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
