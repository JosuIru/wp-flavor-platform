<?php
/**
 * Widget de Dashboard para Bicicletas Compartidas
 *
 * @package FlavorPlatform
 * @subpackage Modules\BicicletasCompartidas
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Bicicletas_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'bicicletas-compartidas';
    protected $icon = 'dashicons-image-rotate';
    protected $size = 'small';
    protected $category = 'sostenibilidad';
    protected $priority = 25;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_bicicletas_';
        $this->title = __('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Sistema de bicicletas compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN);

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 60,
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

        $tabla_bicicletas = $this->prefix_tabla . 'bicicletas';
        $tabla_prestamos = $this->prefix_tabla . 'prestamos';
        $es_admin = is_admin() && !wp_doing_ajax();

        $bicicletas_disponibles = 0;
        if ($this->table_exists($tabla_bicicletas)) {
            $bicicletas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'disponible'"
            );
        }

        $prestamo_activo = null;
        if ($user_id && $this->table_exists($tabla_prestamos)) {
            $prestamo_activo = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, b.nombre as bicicleta_nombre
                 FROM {$tabla_prestamos} p
                 LEFT JOIN {$tabla_bicicletas} b ON p.bicicleta_id = b.id
                 WHERE p.usuario_id = %d AND p.estado = 'activo'
                 ORDER BY p.fecha_inicio DESC LIMIT 1",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-image-rotate',
                'valor' => $bicicletas_disponibles,
                'label' => __('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $bicicletas_disponibles > 0 ? 'success' : 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-bicicletas-prestamos') : Flavor_Platform_Helpers::get_action_url('bicicletas_compartidas', ''),
            ],
        ];

        if ($prestamo_activo) {
            $tiempo = human_time_diff(strtotime($prestamo_activo->fecha_inicio));
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $tiempo,
                'label' => $prestamo_activo->bicicleta_nombre ?: __('En uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-bicicletas-prestamos&id=' . $prestamo_activo->id) : Flavor_Platform_Helpers::get_action_url('bicicletas_compartidas', 'devolver'),
            ];
        }

        return [
            'stats' => $stats,
            'items' => [],
            'empty_state' => __('Reserva una bicicleta para moverte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => $prestamo_activo ? __('Devolver', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=flavor-bicicletas-prestamos') : Flavor_Platform_Helpers::get_action_url('bicicletas_compartidas', ''),
                    'icon' => $prestamo_activo ? 'dashicons-undo' : 'dashicons-plus-alt2',
                ],
            ],
        ];
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
