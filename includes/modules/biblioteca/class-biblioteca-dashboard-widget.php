<?php
/**
 * Widget de Dashboard para Biblioteca
 *
 * @package FlavorChatIA
 * @subpackage Modules\Biblioteca
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Biblioteca_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'biblioteca';
    protected $icon = 'dashicons-book';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 25;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_biblioteca_';
        $this->title = __('Biblioteca', 'flavor-chat-ia');
        $this->description = __('Biblioteca comunitaria y préstamos', 'flavor-chat-ia');

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

        $tabla_libros = $this->prefix_tabla . 'libros';
        $tabla_prestamos = $this->prefix_tabla . 'prestamos';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_libros = 0;
        $libros_disponibles = 0;

        if ($this->table_exists($tabla_libros)) {
            $total_libros = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_libros} WHERE disponibilidad != 'no_disponible'"
            );
            $libros_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_libros} WHERE disponibilidad = 'disponible'"
            );
        }

        $mis_prestamos = 0;
        if ($user_id && $this->table_exists($tabla_prestamos)) {
            $mis_prestamos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_prestamos}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-book',
                'valor' => $total_libros,
                'label' => __('Libros', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=biblioteca') : home_url('/mi-portal/biblioteca/'),
            ],
            [
                'icon' => 'dashicons-yes',
                'valor' => $libros_disponibles,
                'label' => __('Disponibles', 'flavor-chat-ia'),
                'color' => $libros_disponibles > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=biblioteca&estado=disponible') : home_url('/mi-portal/biblioteca/?estado=disponible'),
            ],
        ];

        if ($user_id && $mis_prestamos > 0) {
            $stats[] = [
                'icon' => 'dashicons-book-alt',
                'valor' => $mis_prestamos,
                'label' => __('Mis préstamos', 'flavor-chat-ia'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=biblioteca&tab=mis-prestamos') : home_url('/mi-portal/biblioteca/mis-prestamos/'),
            ];
        }

        $items = $this->get_libros_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay libros disponibles', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver catálogo', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=biblioteca') : home_url('/mi-portal/biblioteca/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_libros_recientes(int $limite): array {
        global $wpdb;
        $tabla_libros = $this->prefix_tabla . 'libros';

        if (!$this->table_exists($tabla_libros)) {
            return [];
        }

        $libros = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, autor, disponibilidad
             FROM {$tabla_libros}
             WHERE disponibilidad != 'no_disponible'
             ORDER BY fecha_agregado DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($libros as $libro) {
            $items[] = [
                'icon' => 'dashicons-book',
                'title' => wp_trim_words($libro->titulo, 4, '...'),
                'meta' => $libro->autor ?: '',
                'url' => $es_admin ? admin_url('admin.php?page=biblioteca&libro=' . $libro->id) : home_url('/mi-portal/biblioteca/libro/' . $libro->id . '/'),
                'badge' => $libro->disponibilidad === 'disponible' ? __('Disponible', 'flavor-chat-ia') : null,
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
