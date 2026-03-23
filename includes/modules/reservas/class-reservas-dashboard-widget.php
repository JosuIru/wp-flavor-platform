<?php
/**
 * Widget de Dashboard para Reservas
 *
 * Muestra estadísticas del módulo de reservas en el dashboard unificado:
 * - Próximas reservas
 * - Historial
 * - Recursos disponibles
 *
 * @package FlavorChatIA
 * @subpackage Modules\Reservas
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Reservas
 *
 * @since 4.1.0
 */
class Flavor_Reservas_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'reservas';

    /**
     * Título del widget
     *
     * @var string
     */
    protected $title;

    /**
     * Icono del widget
     *
     * @var string
     */
    protected $icon = 'dashicons-calendar';

    /**
     * Tamaño del widget
     *
     * @var string
     */
    protected $size = 'medium';

    /**
     * Categoría del widget
     *
     * @var string
     */
    protected $category = 'gestion';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 12;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    private $prefix_tabla;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_reservas_';
        $this->title = __('Reservas', 'flavor-chat-ia');
        $this->description = __('Reserva espacios y recursos comunitarios', 'flavor-chat-ia');

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

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        return $this->get_cached_data(function() use ($user_id) {
            return $this->fetch_widget_data($user_id);
        });
    }

    /**
     * Obtiene los datos frescos del widget
     *
     * @param int $user_id ID del usuario
     * @return array
     */
    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $tabla_reservas = $this->prefix_tabla . 'reservas';
        $tabla_recursos = $this->prefix_tabla . 'recursos';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Próximas reservas del usuario
        $proximas_reservas = 0;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $proximas_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE usuario_id = %d
                 AND fecha_inicio >= CURDATE()
                 AND estado IN ('confirmada', 'pendiente')",
                $user_id
            ));
        }

        // Próxima reserva del usuario
        $proxima_reserva = null;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $proxima_reserva = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, rc.nombre as recurso_nombre
                 FROM {$tabla_reservas} r
                 LEFT JOIN {$tabla_recursos} rc ON r.recurso_id = rc.id
                 WHERE r.usuario_id = %d
                 AND r.fecha_inicio >= CURDATE()
                 AND r.estado IN ('confirmada', 'pendiente')
                 ORDER BY r.fecha_inicio ASC
                 LIMIT 1",
                $user_id
            ));
        }

        // Reservas pendientes de confirmación
        $pendientes_confirmacion = 0;
        if ($user_id && $this->table_exists($tabla_reservas)) {
            $pendientes_confirmacion = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE usuario_id = %d
                 AND estado = 'pendiente'",
                $user_id
            ));
        }

        // Total recursos disponibles
        $total_recursos = 0;
        if ($this->table_exists($tabla_recursos)) {
            $total_recursos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_recursos}
                 WHERE estado = 'activo'"
            );
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Próximas reservas
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $proximas_reservas,
                'label' => __('Próximas reservas', 'flavor-chat-ia'),
                'color' => $proximas_reservas > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=reservas') : add_query_arg('tab', 'mis-reservas', Flavor_Chat_Helpers::get_action_url('reservas', '')),
            ];
        }

        // Stat 2: Próxima reserva con fecha
        if ($proxima_reserva) {
            $fecha_formateada = date_i18n('j M, H:i', strtotime($proxima_reserva->fecha_inicio));
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $fecha_formateada,
                'label' => wp_trim_words($proxima_reserva->recurso_nombre, 2, '...'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=reservas&id=' . $proxima_reserva->id) : add_query_arg(['tab' => 'mis-reservas', 'reserva_id' => $proxima_reserva->id], Flavor_Chat_Helpers::get_action_url('reservas', '')),
            ];
        }

        // Stat 3: Pendientes de confirmación
        if ($user_id && $pendientes_confirmacion > 0) {
            $stats[] = [
                'icon' => 'dashicons-warning',
                'valor' => $pendientes_confirmacion,
                'label' => __('Pendientes', 'flavor-chat-ia'),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=reservas&filter=pendientes') : add_query_arg(['tab' => 'mis-reservas', 'estado' => 'pendiente'], Flavor_Chat_Helpers::get_action_url('reservas', '')),
            ];
        }

        // Stat 4: Recursos disponibles
        $stats[] = [
            'icon' => 'dashicons-building',
            'valor' => $total_recursos,
            'label' => __('Espacios', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=reservas&tab=recursos') : add_query_arg('tab', 'recursos', Flavor_Chat_Helpers::get_action_url('reservas', '')),
        ];

        // Items: mis próximas reservas
        $items = $user_id ? $this->get_proximas_reservas($user_id, 4) : $this->get_recursos_destacados(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => $user_id ? __('No tienes reservas próximas', 'flavor-chat-ia') : __('Inicia sesión para ver tus reservas', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Nueva reserva', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=reservas&action=nueva') : add_query_arg('tab', 'nueva-reserva', Flavor_Chat_Helpers::get_action_url('reservas', '')),
                    'icon' => 'dashicons-plus-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene las próximas reservas del usuario
     *
     * @param int $user_id ID del usuario
     * @param int $limite Número máximo de reservas
     * @return array
     */
    private function get_proximas_reservas(int $user_id, int $limite = 4): array {
        global $wpdb;

        $tabla_reservas = $this->prefix_tabla . 'reservas';
        $tabla_recursos = $this->prefix_tabla . 'recursos';

        if (!$this->table_exists($tabla_reservas)) {
            return [];
        }

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.estado, rc.nombre as recurso_nombre
             FROM {$tabla_reservas} r
             LEFT JOIN {$tabla_recursos} rc ON r.recurso_id = rc.id
             WHERE r.usuario_id = %d
             AND r.fecha_inicio >= CURDATE()
             AND r.estado IN ('confirmada', 'pendiente')
             ORDER BY r.fecha_inicio ASC
             LIMIT %d",
            $user_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($reservas as $reserva) {
            $fecha = date_i18n('j M', strtotime($reserva->fecha_inicio));
            $hora = date_i18n('H:i', strtotime($reserva->fecha_inicio));

            $icono = 'dashicons-calendar';
            if ($reserva->estado === 'pendiente') {
                $icono = 'dashicons-clock';
            }

            $items[] = [
                'icon' => $icono,
                'title' => $reserva->recurso_nombre ?: __('Recurso', 'flavor-chat-ia'),
                'meta' => $fecha . ' · ' . $hora,
                'url' => $es_admin ? admin_url('admin.php?page=reservas&id=' . $reserva->id) : add_query_arg(['tab' => 'mis-reservas', 'reserva_id' => $reserva->id], Flavor_Chat_Helpers::get_action_url('reservas', '')),
                'badge' => $reserva->estado === 'pendiente' ? __('Pendiente', 'flavor-chat-ia') : null,
            ];
        }

        return $items;
    }

    /**
     * Obtiene los recursos destacados
     *
     * @param int $limite Número máximo de recursos
     * @return array
     */
    private function get_recursos_destacados(int $limite = 4): array {
        global $wpdb;

        $tabla_recursos = $this->prefix_tabla . 'recursos';

        if (!$this->table_exists($tabla_recursos)) {
            return [];
        }

        $recursos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, tipo, capacidad
             FROM {$tabla_recursos}
             WHERE estado = 'activo'
             ORDER BY nombre ASC
             LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($recursos as $recurso) {
            // Icono según tipo
            $icono = 'dashicons-building';
            if ($recurso->tipo === 'sala') {
                $icono = 'dashicons-admin-multisite';
            } elseif ($recurso->tipo === 'vehiculo') {
                $icono = 'dashicons-car';
            } elseif ($recurso->tipo === 'herramienta') {
                $icono = 'dashicons-hammer';
            } elseif ($recurso->tipo === 'equipo') {
                $icono = 'dashicons-laptop';
            }

            $capacidad_texto = '';
            if ($recurso->capacidad > 0) {
                $capacidad_texto = sprintf(__('%d personas', 'flavor-chat-ia'), $recurso->capacidad);
            }

            $items[] = [
                'icon' => $icono,
                'title' => $recurso->nombre,
                'meta' => $capacidad_texto ?: ucfirst($recurso->tipo),
                'url' => $es_admin ? admin_url('admin.php?page=reservas&recurso=' . $recurso->id) : add_query_arg(['tab' => 'nueva-reserva', 'recurso_id' => $recurso->id], Flavor_Chat_Helpers::get_action_url('reservas', '')),
            ];
        }

        return $items;
    }

    /**
     * Verifica si una tabla existe
     *
     * @param string $nombre_tabla Nombre de la tabla
     * @return bool
     */
    private function table_exists(string $nombre_tabla): bool {
        global $wpdb;
        static $cache = [];

        if (isset($cache[$nombre_tabla])) {
            return $cache[$nombre_tabla];
        }

        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $nombre_tabla
        ));

        $cache[$nombre_tabla] = ($resultado === $nombre_tabla);
        return $cache[$nombre_tabla];
    }

    /**
     * Renderiza el contenido del widget
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
