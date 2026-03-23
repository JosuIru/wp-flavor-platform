<?php
/**
 * Widget de Dashboard para Trámites
 *
 * @package FlavorChatIA
 * @subpackage Modules\Tramites
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Tramites_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'tramites';
    protected $icon = 'dashicons-clipboard';
    protected $size = 'medium';
    protected $category = 'gestion';
    protected $priority = 12;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_tramites_';
        $this->title = __('Trámites', 'flavor-chat-ia');
        $this->description = __('Gestión de trámites y solicitudes', 'flavor-chat-ia');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 180,
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

        $tabla_tramites = $this->prefix_tabla . 'tramites';
        $tabla_solicitudes = $this->prefix_tabla . 'solicitudes';
        $es_admin = is_admin() && !wp_doing_ajax();

        $tramites_disponibles = 0;
        if ($this->table_exists($tabla_tramites)) {
            $tramites_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_tramites} WHERE estado = 'activo'"
            );
        }

        $mis_solicitudes = 0;
        $solicitudes_pendientes = 0;
        if ($user_id && $this->table_exists($tabla_solicitudes)) {
            $mis_solicitudes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE usuario_id = %d",
                $user_id
            ));
            $solicitudes_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_solicitudes}
                 WHERE usuario_id = %d AND estado IN ('pendiente', 'en_proceso')",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-clipboard',
                'valor' => $tramites_disponibles,
                'label' => __('Trámites', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=tramites-dashboard') : Flavor_Chat_Helpers::get_action_url('tramites', ''),
            ],
        ];

        if ($user_id) {
            if ($solicitudes_pendientes > 0) {
                $stats[] = [
                    'icon' => 'dashicons-clock',
                    'valor' => $solicitudes_pendientes,
                    'label' => __('En proceso', 'flavor-chat-ia'),
                    'color' => 'warning',
                    'url' => $es_admin ? admin_url('admin.php?page=tramites-pendientes') : Flavor_Chat_Helpers::get_action_url('tramites', 'mis-tramites'),
                ];
            }

            if ($mis_solicitudes > 0) {
                $stats[] = [
                    'icon' => 'dashicons-list-view',
                    'valor' => $mis_solicitudes,
                    'label' => __('Mis trámites', 'flavor-chat-ia'),
                    'color' => 'info',
                    'url' => $es_admin ? admin_url('admin.php?page=tramites-historial') : Flavor_Chat_Helpers::get_action_url('tramites', 'mis-tramites'),
                ];
            }
        }

        $items = $this->get_tramites_destacados(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay trámites disponibles', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Iniciar trámite', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=tramites-dashboard') : Flavor_Chat_Helpers::get_action_url('tramites', ''),
                    'icon' => 'dashicons-plus-alt2',
                ],
            ],
        ];
    }

    private function get_tramites_destacados(int $limite): array {
        global $wpdb;
        $tabla_tramites = $this->prefix_tabla . 'tramites';

        if (!$this->table_exists($tabla_tramites)) {
            return [];
        }

        $tramites = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, categoria, tiempo_estimado
             FROM {$tabla_tramites}
             WHERE estado = 'activo'
             ORDER BY destacado DESC, titulo ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($tramites as $tramite) {
            $meta = $tramite->categoria ?: '';
            if (!empty($tramite->tiempo_estimado)) {
                $meta = sprintf(__('%s días', 'flavor-chat-ia'), $tramite->tiempo_estimado);
            }

            $items[] = [
                'icon' => 'dashicons-media-text',
                'title' => wp_trim_words($tramite->titulo, 4, '...'),
                'meta' => $meta,
                'url' => $es_admin ? admin_url('admin.php?page=tramites-tipos') : add_query_arg('tramite_id', $tramite->id, Flavor_Chat_Helpers::get_action_url('tramites', 'iniciar')),
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
