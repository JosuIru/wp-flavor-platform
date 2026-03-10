<?php
/**
 * Widget de Dashboard para Colectivos
 *
 * @package FlavorChatIA
 * @subpackage Modules\Colectivos
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Colectivos_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'colectivos';
    protected $icon = 'dashicons-groups';
    protected $size = 'medium';
    protected $category = 'comunidad';
    protected $priority = 20;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_colectivos_';
        $this->title = __('Colectivos', 'flavor-chat-ia');
        $this->description = __('Colectivos y asociaciones locales', 'flavor-chat-ia');

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

        $tabla_colectivos = $this->prefix_tabla . 'colectivos';
        $tabla_miembros = $this->prefix_tabla . 'miembros';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_colectivos = 0;
        if ($this->table_exists($tabla_colectivos)) {
            $total_colectivos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_colectivos} WHERE estado = 'activo'"
            );
        }

        $mis_colectivos = 0;
        if ($user_id && $this->table_exists($tabla_miembros)) {
            $mis_colectivos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-groups',
                'valor' => $total_colectivos,
                'label' => __('Colectivos', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=colectivos') : home_url('/mi-portal/colectivos/'),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $mis_colectivos,
                'label' => __('Mis colectivos', 'flavor-chat-ia'),
                'color' => $mis_colectivos > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=colectivos&tab=mis-colectivos') : home_url('/mi-portal/colectivos/mis-colectivos/'),
            ];
        }

        $items = $this->get_colectivos_destacados(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay colectivos registrados', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Explorar colectivos', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=colectivos') : home_url('/mi-portal/colectivos/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_colectivos_destacados(int $limite): array {
        global $wpdb;
        $tabla_colectivos = $this->prefix_tabla . 'colectivos';

        if (!$this->table_exists($tabla_colectivos)) {
            return [];
        }

        $colectivos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, categoria, total_miembros
             FROM {$tabla_colectivos}
             WHERE estado = 'activo'
             ORDER BY destacado DESC, total_miembros DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($colectivos as $colectivo) {
            $miembros_texto = '';
            if (isset($colectivo->total_miembros) && $colectivo->total_miembros > 0) {
                $miembros_texto = sprintf(_n('%d miembro', '%d miembros', $colectivo->total_miembros, 'flavor-chat-ia'), $colectivo->total_miembros);
            }

            $items[] = [
                'icon' => 'dashicons-groups',
                'title' => wp_trim_words($colectivo->nombre, 4, '...'),
                'meta' => $colectivo->categoria ?: $miembros_texto,
                'url' => $es_admin ? admin_url('admin.php?page=colectivos&colectivo=' . $colectivo->id) : home_url('/mi-portal/colectivos/?colectivo=' . $colectivo->id),
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
