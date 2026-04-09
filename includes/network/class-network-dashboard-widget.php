<?php
/**
 * Widget de Dashboard para Red de Comunidades
 *
 * Muestra estadísticas de la red de nodos federados:
 * - Nodos conectados activos
 * - Eventos compartidos de la red
 * - Contenido compartido
 * - Alertas solidarias activas
 *
 * @package FlavorChatIA
 * @subpackage Network
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Asegurar que la clase base exista
if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Red de Comunidades
 *
 * @since 4.1.0
 */
class Flavor_Network_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'red-nodos';

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
    protected $icon = 'dashicons-networking';

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
    protected $category = 'red';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 20;

    /**
     * Prefijo de tablas de red
     *
     * @var string
     */
    private $prefix_tabla;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_network_';
        $this->title = __('Red de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Conexiones y contenido compartido con otras comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN);

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

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        return $this->get_cached_data(function() {
            return $this->fetch_widget_data();
        });
    }

    /**
     * Obtiene los datos frescos del widget
     *
     * @return array
     */
    private function fetch_widget_data(): array {
        global $wpdb;

        // Verificar que las tablas de red existan
        $tabla_nodos = $this->prefix_tabla . 'nodes';
        if (!$this->table_exists($tabla_nodos)) {
            return [
                'stats' => [],
                'items' => [],
                'empty_state' => __('Red no configurada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'footer' => [
                    [
                        'label' => __('Configurar red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'url' => admin_url('admin.php?page=flavor-platform-network'),
                        'icon' => 'dashicons-admin-settings',
                    ],
                ],
            ];
        }

        // Nodos conectados activos
        $nodos_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_nodos}
             WHERE estado = 'activo'"
        );

        // Eventos de la red (próximos 7 días)
        $tabla_eventos = $this->prefix_tabla . 'events';
        $eventos_red = 0;
        if ($this->table_exists($tabla_eventos)) {
            $eventos_red = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_eventos}
                 WHERE visible_red = 1
                   AND fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
            );
        }

        // Contenido compartido activo
        $tabla_contenido = $this->prefix_tabla . 'shared_content';
        $contenido_compartido = 0;
        if ($this->table_exists($tabla_contenido)) {
            $contenido_compartido = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_contenido}
                 WHERE visible_red = 1 AND estado = 'activo'"
            );
        }

        // Alertas solidarias activas
        $tabla_alertas = $this->prefix_tabla . 'alerts';
        $alertas_activas = 0;
        if ($this->table_exists($tabla_alertas)) {
            $alertas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_alertas}
                 WHERE estado = 'activa'
                   AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())"
            );
        }

        // Construir estadísticas
        $stats = [
            [
                'icon' => 'dashicons-groups',
                'valor' => $nodos_activos,
                'label' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'primary',
                'url' => home_url('/red/directorio/'),
            ],
            [
                'icon' => 'dashicons-calendar',
                'valor' => $eventos_red,
                'label' => __('Eventos red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $eventos_red > 0 ? 'success' : 'gray',
                'url' => home_url('/red/eventos/'),
            ],
            [
                'icon' => 'dashicons-share',
                'valor' => $contenido_compartido,
                'label' => __('Compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'info',
                'url' => home_url('/red/catalogo/'),
            ],
            [
                'icon' => 'dashicons-warning',
                'valor' => $alertas_activas,
                'label' => __('Alertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $alertas_activas > 0 ? 'danger' : 'gray',
                'url' => home_url('/red/alertas/'),
            ],
        ];

        // Obtener eventos próximos de otros nodos
        $items = $this->get_eventos_remotos(5);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay eventos próximos en la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Explorar red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => home_url('/red/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
            'extra' => [
                'nodos_activos' => $nodos_activos,
                'tiene_alertas' => $alertas_activas > 0,
            ],
        ];
    }

    /**
     * Obtiene eventos de otros nodos de la red
     *
     * @param int $limite Número máximo de eventos
     * @return array
     */
    private function get_eventos_remotos(int $limite = 5): array {
        global $wpdb;

        $tabla_eventos = $this->prefix_tabla . 'events';
        $tabla_nodos = $this->prefix_tabla . 'nodes';

        if (!$this->table_exists($tabla_eventos) || !$this->table_exists($tabla_nodos)) {
            return [];
        }

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, n.nombre AS nodo_nombre, n.logo_url
             FROM {$tabla_eventos} e
             LEFT JOIN {$tabla_nodos} n ON e.nodo_id = n.id
             WHERE e.visible_red = 1
               AND e.fecha_inicio > NOW()
             ORDER BY e.fecha_inicio ASC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($eventos as $evento) {
            $fecha_formateada = date_i18n('j M', strtotime($evento->fecha_inicio));
            $tipo_icono = $this->get_tipo_evento_icono($evento->tipo ?? '');

            $badge = null;
            if (!empty($evento->tipo) && $evento->tipo === 'online') {
                $badge = __('Online', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            $items[] = [
                'icon' => $tipo_icono,
                'title' => $evento->titulo,
                'meta' => sprintf('%s - %s', $evento->nodo_nombre ?: __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN), $fecha_formateada),
                'url' => home_url('/red/eventos/' . $evento->id . '/'),
                'badge' => $badge,
            ];
        }

        return $items;
    }

    /**
     * Obtiene el icono según el tipo de evento
     *
     * @param string $tipo Tipo de evento
     * @return string
     */
    private function get_tipo_evento_icono(string $tipo): string {
        $iconos = [
            'presencial' => 'dashicons-location',
            'online' => 'dashicons-video-alt2',
            'hibrido' => 'dashicons-admin-multisite',
            'taller' => 'dashicons-hammer',
            'formacion' => 'dashicons-welcome-learn-more',
            'asamblea' => 'dashicons-megaphone',
            'mercado' => 'dashicons-store',
            'fiesta' => 'dashicons-admin-customizer',
        ];

        return $iconos[$tipo] ?? 'dashicons-calendar-alt';
    }

    /**
     * Verifica si una tabla existe
     *
     * @param string $tabla_nombre Nombre de la tabla
     * @return bool
     */
    private function table_exists(string $tabla_nombre): bool {
        global $wpdb;
        static $cache = [];

        if (isset($cache[$tabla_nombre])) {
            return $cache[$tabla_nombre];
        }

        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_nombre
        ));

        $cache[$tabla_nombre] = ($resultado === $tabla_nombre);
        return $cache[$tabla_nombre];
    }

    /**
     * Comprueba si el widget puede mostrarse
     *
     * @return bool
     */
    public function can_display(): bool {
        // Verificar que la red esté habilitada
        $tabla_nodos = $this->prefix_tabla . 'nodes';
        return $this->table_exists($tabla_nodos);
    }

    /**
     * Renderiza el contenido del widget con alertas destacadas
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();

        // Si hay alertas activas, mostrar indicador especial
        if (!empty($data['extra']['tiene_alertas'])) {
            echo '<div class="fud-widget-alert-indicator" aria-live="polite">';
            echo '<span class="dashicons dashicons-warning"></span> ';
            echo esc_html__('Hay alertas solidarias activas', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</div>';
        }

        $this->render_widget_content($data);
    }

    /**
     * Obtiene la URL del módulo para el footer
     *
     * @return string
     */
    protected function get_module_url(): string {
        return home_url('/red/');
    }
}
