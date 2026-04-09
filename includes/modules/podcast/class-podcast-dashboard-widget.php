<?php
/**
 * Widget de Dashboard para Podcast
 *
 * @package FlavorChatIA
 * @subpackage Modules\Podcast
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Podcast_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'podcast';
    protected $icon = 'dashicons-microphone';
    protected $size = 'medium';
    protected $category = 'comunicacion';
    protected $priority = 30;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_podcast_';
        $this->title = __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Escucha los episodios de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN);

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
        return $this->get_cached_data(function() {
            return $this->fetch_widget_data();
        });
    }

    private function fetch_widget_data(): array {
        global $wpdb;

        $tabla_episodios = $this->prefix_tabla . 'episodios';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_episodios = 0;
        $ultimo_episodio = null;

        if ($this->table_exists($tabla_episodios)) {
            $total_episodios = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_episodios} WHERE estado = 'publicado'"
            );

            $ultimo_episodio = $wpdb->get_row(
                "SELECT id, titulo, duracion, fecha_publicacion
                 FROM {$tabla_episodios}
                 WHERE estado = 'publicado'
                 ORDER BY fecha_publicacion DESC LIMIT 1"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-microphone',
                'valor' => $total_episodios,
                'label' => __('Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=podcast') : Flavor_Chat_Helpers::get_action_url('podcast', ''),
            ],
        ];

        if ($ultimo_episodio) {
            $stats[] = [
                'icon' => 'dashicons-controls-play',
                'valor' => __('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'label' => wp_trim_words($ultimo_episodio->titulo, 3, '...'),
                'color' => 'success',
                'url' => $es_admin ? admin_url('admin.php?page=podcast&id=' . $ultimo_episodio->id) : Flavor_Chat_Helpers::get_action_url('podcast', 'episodio') . '/' . $ultimo_episodio->id . '/',
            ];
        }

        $items = $this->get_episodios_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay episodios publicados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=podcast') : Flavor_Chat_Helpers::get_action_url('podcast', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_episodios_recientes(int $limite): array {
        global $wpdb;
        $tabla_episodios = $this->prefix_tabla . 'episodios';

        if (!$this->table_exists($tabla_episodios)) {
            return [];
        }

        $episodios = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, duracion, fecha_publicacion
             FROM {$tabla_episodios}
             WHERE estado = 'publicado'
             ORDER BY fecha_publicacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($episodios as $episodio) {
            $duracion = '';
            if ($episodio->duracion) {
                $minutos = floor($episodio->duracion / 60);
                $duracion = $minutos . ' min';
            }

            $items[] = [
                'icon' => 'dashicons-controls-play',
                'title' => wp_trim_words($episodio->titulo, 5, '...'),
                'meta' => $duracion ?: date_i18n('j M', strtotime($episodio->fecha_publicacion)),
                'url' => $es_admin ? admin_url('admin.php?page=podcast&id=' . $episodio->id) : Flavor_Chat_Helpers::get_action_url('podcast', 'episodio') . '/' . $episodio->id . '/',
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
