<?php
/**
 * Widget de Dashboard para Carpooling
 *
 * @package FlavorChatIA
 * @subpackage Modules\Carpooling
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Carpooling_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'carpooling';
    protected $icon = 'dashicons-car';
    protected $size = 'medium';
    protected $category = 'sostenibilidad';
    protected $priority = 20;
    private $prefix_tabla;

    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_carpooling_';
        $this->title = __('Carpooling', 'flavor-chat-ia');
        $this->description = __('Comparte viajes y reduce tu huella', 'flavor-chat-ia');

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

        $tabla_viajes = $this->prefix_tabla . 'viajes';
        $tabla_reservas = $this->prefix_tabla . 'reservas';
        $es_admin = is_admin() && !wp_doing_ajax();

        $viajes_disponibles = 0;
        if ($this->table_exists($tabla_viajes)) {
            $viajes_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_viajes}
                 WHERE estado = 'activo' AND fecha_salida >= NOW()"
            );
        }

        $mis_viajes = 0;
        if ($user_id && $this->table_exists($tabla_viajes)) {
            $mis_viajes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_viajes}
                 WHERE conductor_id = %d AND fecha_salida >= NOW()",
                $user_id
            ));
        }

        $mis_reservas = 0;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $mis_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas} r
                 INNER JOIN {$tabla_viajes} v ON r.viaje_id = v.id
                 WHERE r.pasajero_id = %d AND v.fecha_salida >= NOW() AND r.estado = 'confirmada'",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-car',
                'valor' => $viajes_disponibles,
                'label' => __('Viajes disponibles', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-carpooling-viajes') : home_url('/mi-portal/carpooling/'),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $mis_viajes,
                'label' => __('Ofrezco', 'flavor-chat-ia'),
                'color' => $mis_viajes > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-carpooling-viajes&tab=mis-viajes') : home_url('/mi-portal/carpooling/mis-viajes/'),
            ];

            $stats[] = [
                'icon' => 'dashicons-tickets-alt',
                'valor' => $mis_reservas,
                'label' => __('Reservas', 'flavor-chat-ia'),
                'color' => $mis_reservas > 0 ? 'info' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=flavor-carpooling-viajes&tab=reservas') : home_url('/mi-portal/carpooling/reservas/'),
            ];
        }

        $items = $this->get_viajes_proximos(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay viajes disponibles próximamente', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Buscar viajes', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=flavor-carpooling-viajes') : home_url('/mi-portal/carpooling/buscar/'),
                    'icon' => 'dashicons-search',
                ],
            ],
        ];
    }

    private function get_viajes_proximos(int $limite): array {
        global $wpdb;
        $tabla_viajes = $this->prefix_tabla . 'viajes';

        if (!$this->table_exists($tabla_viajes)) {
            return [];
        }

        $viajes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, origen, destino, fecha_salida, plazas_disponibles
             FROM {$tabla_viajes}
             WHERE estado = 'activo' AND fecha_salida >= NOW()
             ORDER BY fecha_salida ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($viajes as $viaje) {
            $ruta = wp_trim_words($viaje->origen, 2, '') . ' → ' . wp_trim_words($viaje->destino, 2, '');
            $items[] = [
                'icon' => 'dashicons-car',
                'title' => $ruta,
                'meta' => date_i18n('j M, H:i', strtotime($viaje->fecha_salida)),
                'url' => $es_admin ? admin_url('admin.php?page=flavor-carpooling-viajes&viaje=' . $viaje->id) : home_url('/mi-portal/carpooling/viaje/' . $viaje->id . '/'),
                'badge' => $viaje->plazas_disponibles > 0 ? $viaje->plazas_disponibles . ' plazas' : null,
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
