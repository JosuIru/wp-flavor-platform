<?php
/**
 * Widget de Dashboard para Huertos Urbanos
 *
 * @package FlavorChatIA
 * @subpackage Modules\HuertosUrbanos
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Huertos_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'huertos-urbanos';
    protected $icon = 'dashicons-carrot';
    protected $size = 'medium';
    protected $category = 'sostenibilidad';
    protected $priority = 10;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_huertos_';
        $this->title = __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Gestión de parcelas y huertos comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN);

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

        $tabla_parcelas = $this->prefix_tabla . 'parcelas';
        $tabla_asignaciones = $this->prefix_tabla . 'asignaciones';
        $es_admin = is_admin() && !wp_doing_ajax();

        $mis_parcelas = 0;
        if ($user_id && $this->table_exists($tabla_asignaciones)) {
            $mis_parcelas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_asignaciones}
                 WHERE usuario_id = %d AND estado = 'activa'",
                $user_id
            ));
        }

        $parcelas_disponibles = 0;
        if ($this->table_exists($tabla_parcelas)) {
            $parcelas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_parcelas}
                 WHERE estado = 'disponible'"
            );
        }

        $stats = [];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-carrot',
                'valor' => $mis_parcelas,
                'label' => __('Mis parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $mis_parcelas > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=huertos-urbanos') : Flavor_Chat_Helpers::get_action_url('huertos_urbanos', 'mis-parcelas'),
            ];
        }

        $stats[] = [
            'icon' => 'dashicons-location',
            'valor' => $parcelas_disponibles,
            'label' => __('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $parcelas_disponibles > 0 ? 'info' : 'gray',
            'url' => $es_admin ? admin_url('admin.php?page=huertos-urbanos&tab=disponibles') : Flavor_Chat_Helpers::get_action_url('huertos_urbanos', 'disponibles'),
        ];

        $items = $user_id ? $this->get_mis_parcelas($user_id, 3) : [];

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('Solicita una parcela para empezar a cultivar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=huertos-urbanos') : Flavor_Chat_Helpers::get_action_url('huertos_urbanos', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_mis_parcelas(int $user_id, int $limite): array {
        global $wpdb;
        $tabla_parcelas = $this->prefix_tabla . 'parcelas';
        $tabla_asignaciones = $this->prefix_tabla . 'asignaciones';

        if (!$this->table_exists($tabla_asignaciones)) {
            return [];
        }

        $parcelas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.nombre, p.ubicacion, a.fecha_asignacion
             FROM {$tabla_asignaciones} a
             LEFT JOIN {$tabla_parcelas} p ON a.parcela_id = p.id
             WHERE a.usuario_id = %d AND a.estado = 'activa'
             ORDER BY a.fecha_asignacion DESC LIMIT %d",
            $user_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($parcelas as $parcela) {
            $items[] = [
                'icon' => 'dashicons-carrot',
                'title' => $parcela->nombre ?: __('Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'meta' => $parcela->ubicacion ?: '',
                'url' => $es_admin ? admin_url('admin.php?page=huertos-urbanos&parcela=' . $parcela->id) : Flavor_Chat_Helpers::get_action_url('huertos_urbanos', 'parcela') . '/' . $parcela->id . '/',
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
