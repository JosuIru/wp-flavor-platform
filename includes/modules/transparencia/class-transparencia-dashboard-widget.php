<?php
/**
 * Widget de Dashboard para Transparencia
 *
 * @package FlavorChatIA
 * @subpackage Modules\Transparencia
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Transparencia_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'transparencia';
    protected $icon = 'dashicons-visibility';
    protected $size = 'medium';
    protected $category = 'gestion';
    protected $priority = 45;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_transparencia_';
        $this->title = __('Transparencia', 'flavor-chat-ia');
        $this->description = __('Portal de transparencia y datos abiertos', 'flavor-chat-ia');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 600,
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

        $tabla_documentos = $this->prefix_tabla . 'documentos';
        $tabla_categorias = $this->prefix_tabla . 'categorias';
        $es_admin = is_admin() && !wp_doing_ajax();

        $total_documentos = 0;
        $total_categorias = 0;

        if ($this->table_exists($tabla_documentos)) {
            $total_documentos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_documentos} WHERE estado = 'publicado'"
            );
        }

        if ($this->table_exists($tabla_categorias)) {
            $total_categorias = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_categorias} WHERE estado = 'activa'"
            );
        }

        $stats = [
            [
                'icon' => 'dashicons-media-document',
                'valor' => $total_documentos,
                'label' => __('Documentos', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=transparencia') : Flavor_Chat_Helpers::get_action_url('transparencia', ''),
            ],
            [
                'icon' => 'dashicons-category',
                'valor' => $total_categorias,
                'label' => __('Categorías', 'flavor-chat-ia'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=transparencia&tab=categorias') : Flavor_Chat_Helpers::get_action_url('transparencia', 'categorias'),
            ],
        ];

        $items = $this->get_documentos_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay documentos publicados', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Portal transparencia', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=transparencia') : Flavor_Chat_Helpers::get_action_url('transparencia', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_documentos_recientes(int $limite): array {
        global $wpdb;
        $tabla_documentos = $this->prefix_tabla . 'documentos';

        if (!$this->table_exists($tabla_documentos)) {
            return [];
        }

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, categoria, fecha_publicacion
             FROM {$tabla_documentos}
             WHERE estado = 'publicado'
             ORDER BY fecha_publicacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($documentos as $documento) {
            $items[] = [
                'icon' => 'dashicons-media-document',
                'title' => wp_trim_words($documento->titulo, 4, '...'),
                'meta' => $documento->categoria ?: date_i18n('j M Y', strtotime($documento->fecha_publicacion)),
                'url' => $es_admin ? admin_url('admin.php?page=transparencia&documento=' . $documento->id) : Flavor_Chat_Helpers::get_action_url('transparencia', 'documento') . '/' . $documento->id . '/',
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
