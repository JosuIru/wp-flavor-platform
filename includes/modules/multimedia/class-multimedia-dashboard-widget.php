<?php
/**
 * Widget de Dashboard para Multimedia
 *
 * @package FlavorChatIA
 * @subpackage Modules\Multimedia
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Multimedia_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'multimedia';
    protected $icon = 'dashicons-format-video';
    protected $size = 'medium';
    protected $category = 'comunicacion';
    protected $priority = 35;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_multimedia_';
        $this->title = __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Galería de fotos y vídeos', FLAVOR_PLATFORM_TEXT_DOMAIN);

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

        $tabla_archivos = $this->prefix_tabla . 'archivos';
        $tabla_albumes = $this->prefix_tabla . 'albumes';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_archivos = 0;
        $total_albumes = 0;

        if ($this->table_exists($tabla_archivos)) {
            $total_archivos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_archivos} WHERE estado = 'publicado'"
            );
        }

        if ($this->table_exists($tabla_albumes)) {
            $total_albumes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_albumes} WHERE estado = 'publicado'"
            );
        }

        $mis_archivos = 0;
        if ($user_id && $this->table_exists($tabla_archivos)) {
            $mis_archivos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_archivos} WHERE usuario_id = %d",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-format-gallery',
                'valor' => $total_albumes,
                'label' => __('Álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=multimedia-albumes') : Flavor_Chat_Helpers::get_action_url('multimedia', 'galeria'),
            ],
            [
                'icon' => 'dashicons-images-alt2',
                'valor' => $total_archivos,
                'label' => __('Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=multimedia-galeria') : Flavor_Chat_Helpers::get_action_url('multimedia', 'galeria'),
            ],
        ];

        if ($user_id && $mis_archivos > 0) {
            $stats[] = [
                'icon' => 'dashicons-upload',
                'valor' => $mis_archivos,
                'label' => __('Mis archivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'success',
                'url' => $es_admin ? admin_url('admin.php?page=multimedia-galeria&autor=' . $user_id) : Flavor_Chat_Helpers::get_action_url('multimedia', 'mi-galeria'),
            ];
        }

        $items = $this->get_albumes_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay contenido multimedia disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver galería', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=multimedia-galeria') : Flavor_Chat_Helpers::get_action_url('multimedia', 'galeria'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_albumes_recientes(int $limite): array {
        global $wpdb;
        $tabla_albumes = $this->prefix_tabla . 'albumes';

        if (!$this->table_exists($tabla_albumes)) {
            return [];
        }

        $albumes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_creacion, total_archivos
             FROM {$tabla_albumes}
             WHERE estado = 'publicado'
             ORDER BY fecha_creacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($albumes as $album) {
            $items[] = [
                'icon' => 'dashicons-format-gallery',
                'title' => wp_trim_words($album->titulo, 4, '...'),
                'meta' => sprintf(_n('%d archivo', '%d archivos', $album->total_archivos ?? 0, FLAVOR_PLATFORM_TEXT_DOMAIN), $album->total_archivos ?? 0),
                'url' => $es_admin
                    ? admin_url('admin.php?page=multimedia-albumes&album=' . $album->id)
                    : add_query_arg('album_id', $album->id, Flavor_Chat_Helpers::get_action_url('multimedia', 'mi-galeria')),
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
