<?php
/**
 * Widget de Dashboard para Banco de Tiempo
 *
 * Muestra estadísticas del módulo de banco de tiempo en el dashboard unificado:
 * - Saldo de horas
 * - Servicios ofrecidos
 * - Intercambios recientes
 *
 * @package FlavorChatIA
 * @subpackage Modules\BancoTiempo
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Banco de Tiempo
 *
 * @since 4.1.0
 */
class Flavor_Banco_Tiempo_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'banco-tiempo';

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
    protected $icon = 'dashicons-clock';

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
    protected $category = 'economia';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 25;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_bt_';
        $this->title = __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Intercambia servicios con tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN);

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

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return [
                'stats' => [],
                'items' => [],
                'empty_state' => __('Inicia sesión para acceder al banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'footer' => [],
            ];
        }

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

        $tabla_servicios = $this->prefix_tabla . 'servicios';
        $tabla_transacciones = $this->prefix_tabla . 'transacciones';
        $tabla_metricas = $this->prefix_tabla . 'metricas';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Saldo de horas del usuario
        $saldo_horas = 0.0;
        if ($this->table_exists($tabla_metricas)) {
            $saldo_horas = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(saldo_actual, 0) FROM {$tabla_metricas}
                 WHERE usuario_id = %d
                 ORDER BY fecha DESC
                 LIMIT 1",
                $user_id
            ));
        }

        // Si no hay métricas, calcular de transacciones
        if ($saldo_horas === 0.0 && $this->table_exists($tabla_transacciones)) {
            $horas_recibidas = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(horas), 0) FROM {$tabla_transacciones}
                 WHERE usuario_solicitante_id = %d AND estado = 'completado'",
                $user_id
            ));

            $horas_dadas = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(horas), 0) FROM {$tabla_transacciones}
                 WHERE usuario_receptor_id = %d AND estado = 'completado'",
                $user_id
            ));

            $saldo_horas = $horas_recibidas - $horas_dadas;
        }

        // Mis servicios ofrecidos
        $mis_servicios = 0;
        if ($this->table_exists($tabla_servicios)) {
            $mis_servicios = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_servicios}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        // Intercambios completados
        $intercambios_completados = 0;
        if ($this->table_exists($tabla_transacciones)) {
            $intercambios_completados = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_transacciones}
                 WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
                 AND estado = 'completado'",
                $user_id,
                $user_id
            ));
        }

        // Intercambios pendientes
        $intercambios_pendientes = 0;
        if ($this->table_exists($tabla_transacciones)) {
            $intercambios_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_transacciones}
                 WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
                 AND estado IN ('pendiente', 'aceptada', 'en_progreso')",
                $user_id,
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Saldo de horas
        $color_saldo = 'gray';
        if ($saldo_horas > 0) {
            $color_saldo = 'success';
        } elseif ($saldo_horas < 0) {
            $color_saldo = 'danger';
        }

        $stats[] = [
            'icon' => 'dashicons-clock',
            'valor' => number_format(abs($saldo_horas), 1, ',', '.') . 'h',
            'label' => $saldo_horas >= 0 ? __('Saldo positivo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Saldo negativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $color_saldo,
            'url' => $es_admin ? admin_url('admin.php?page=banco-tiempo') : Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'mi-saldo'),
        ];

        // Stat 2: Mis servicios
        $stats[] = [
            'icon' => 'dashicons-hammer',
            'valor' => $mis_servicios,
            'label' => __('Mis servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $mis_servicios > 0 ? 'primary' : 'gray',
            'url' => $es_admin ? admin_url('admin.php?page=bt-servicios') : Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios'),
        ];

        // Stat 3: Intercambios pendientes
        if ($intercambios_pendientes > 0) {
            $stats[] = [
                'icon' => 'dashicons-update',
                'valor' => $intercambios_pendientes,
                'label' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=bt-intercambios') : Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'intercambios'),
            ];
        }

        // Stat 4: Total completados
        $stats[] = [
            'icon' => 'dashicons-yes-alt',
            'valor' => $intercambios_completados,
            'label' => __('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'info',
            'url' => $es_admin ? admin_url('admin.php?page=bt-intercambios') : Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'intercambios'),
        ];

        // Items: servicios disponibles
        $items = $this->get_servicios_disponibles($user_id, 4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('Explora servicios disponibles en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver todos los servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $es_admin ? admin_url('admin.php?page=bt-servicios') : Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene servicios disponibles
     *
     * @param int $user_id ID del usuario actual (para excluir sus propios servicios)
     * @param int $limite Número máximo de servicios
     * @return array
     */
    private function get_servicios_disponibles(int $user_id, int $limite = 4): array {
        global $wpdb;

        $tabla_servicios = $this->prefix_tabla . 'servicios';

        if (!$this->table_exists($tabla_servicios)) {
            return [];
        }

        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.titulo, s.categoria, s.usuario_id, u.display_name as nombre_usuario
             FROM {$tabla_servicios} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE s.estado = 'activo'
             AND s.usuario_id != %d
             ORDER BY s.fecha_publicacion DESC
             LIMIT %d",
            $user_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($servicios as $servicio) {
            $items[] = [
                'icon' => 'dashicons-hammer',
                'title' => wp_trim_words($servicio->titulo, 5, '...'),
                'meta' => $servicio->nombre_usuario ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => $es_admin ? admin_url('admin.php?page=bt-servicios') : add_query_arg('servicio_id', $servicio->id, Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios')),
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
