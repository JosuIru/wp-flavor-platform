<?php
/**
 * Widget de Dashboard para Ayuda Vecinal
 *
 * @package FlavorChatIA
 * @subpackage Modules\AyudaVecinal
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Ayuda_Vecinal_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    private static $instance = null;
    protected $widget_id = 'ayuda-vecinal';
    protected $icon = 'dashicons-heart';
    protected $size = 'medium';
    protected $category = 'servicios';
    protected $priority = 10;
    private $prefix_tabla;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_ayuda_';
        $this->title = __('Ayuda Vecinal', 'flavor-chat-ia');
        $this->description = __('Solicita o ofrece ayuda a tus vecinos', 'flavor-chat-ia');

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

        $tabla_solicitudes = $this->prefix_tabla . 'solicitudes';
        $tabla_ofertas = $this->prefix_tabla . 'ofertas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $solicitudes_activas = 0;
        if ($this->table_exists($tabla_solicitudes)) {
            $solicitudes_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_solicitudes}
                 WHERE estado = 'activa'"
            );
        }

        $ofertas_activas = 0;
        if ($this->table_exists($tabla_ofertas)) {
            $ofertas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_ofertas}
                 WHERE estado = 'activa'"
            );
        }

        $mis_solicitudes = 0;
        if ($user_id && $this->table_exists($tabla_solicitudes)) {
            $mis_solicitudes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_solicitudes}
                 WHERE usuario_id = %d AND estado = 'activa'",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-sos',
                'valor' => $solicitudes_activas,
                'label' => __('Necesitan ayuda', 'flavor-chat-ia'),
                'color' => $solicitudes_activas > 0 ? 'warning' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=ayuda-vecinal') : Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', 'solicitudes'),
            ],
            [
                'icon' => 'dashicons-heart',
                'valor' => $ofertas_activas,
                'label' => __('Ofrecen ayuda', 'flavor-chat-ia'),
                'color' => $ofertas_activas > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=ayuda-vecinal&tab=ofertas') : Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', 'ofertas'),
            ],
        ];

        if ($user_id && $mis_solicitudes > 0) {
            $stats[] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $mis_solicitudes,
                'label' => __('Mis solicitudes', 'flavor-chat-ia'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=ayuda-vecinal&tab=mis-solicitudes') : Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', 'mis-solicitudes'),
            ];
        }

        $items = $this->get_solicitudes_recientes(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay solicitudes de ayuda activas', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ofrecer ayuda', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=ayuda-vecinal&action=ofrecer') : Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', 'ofrecer'),
                    'icon' => 'dashicons-heart',
                ],
            ],
        ];
    }

    private function get_solicitudes_recientes(int $limite): array {
        global $wpdb;
        $tabla_solicitudes = $this->prefix_tabla . 'solicitudes';

        if (!$this->table_exists($tabla_solicitudes)) {
            return [];
        }

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, categoria, urgencia, fecha_creacion
             FROM {$tabla_solicitudes}
             WHERE estado = 'activa'
             ORDER BY urgencia DESC, fecha_creacion DESC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($solicitudes as $solicitud) {
            $icono = 'dashicons-sos';
            if ($solicitud->urgencia === 'alta') {
                $icono = 'dashicons-warning';
            }

            $items[] = [
                'icon' => $icono,
                'title' => wp_trim_words($solicitud->titulo, 5, '...'),
                'meta' => $solicitud->categoria ?: human_time_diff(strtotime($solicitud->fecha_creacion)),
                'url' => $es_admin ? admin_url('admin.php?page=ayuda-vecinal&solicitud=' . $solicitud->id) : Flavor_Chat_Helpers::get_action_url('ayuda_vecinal', 'solicitud') . '/' . $solicitud->id . '/',
                'badge' => $solicitud->urgencia === 'alta' ? __('Urgente', 'flavor-chat-ia') : null,
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
