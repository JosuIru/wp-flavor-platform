<?php
/**
 * Widget de Dashboard para Eventos
 *
 * Muestra estadísticas del módulo de eventos en el dashboard unificado:
 * - Próximos eventos
 * - Mis inscripciones
 * - Eventos populares
 *
 * @package FlavorChatIA
 * @subpackage Modules\Eventos
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Eventos
 *
 * @since 4.1.0
 */
class Flavor_Eventos_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'eventos';

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
    protected $icon = 'dashicons-calendar-alt';

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
    protected $category = 'comunidad';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 15;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_';
        $this->title = __('Eventos', 'flavor-chat-ia');
        $this->description = __('Próximos eventos y actividades', 'flavor-chat-ia');

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

        $tabla_eventos = $this->prefix_tabla . 'eventos';
        $tabla_inscripciones = $this->prefix_tabla . 'eventos_inscripciones';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Contar eventos próximos (próximos 30 días)
        $total_proximos = 0;
        if ($this->table_exists($tabla_eventos)) {
            $total_proximos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_eventos}
                 WHERE estado = 'publicado'
                 AND fecha_inicio >= CURDATE()
                 AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
            );
        }

        // Mis inscripciones activas
        $mis_inscripciones = 0;
        if ($user_id && $this->table_exists($tabla_inscripciones)) {
            $mis_inscripciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_inscripciones} ei
                 INNER JOIN {$tabla_eventos} e ON ei.evento_id = e.id
                 WHERE ei.usuario_id = %d
                 AND ei.estado = 'confirmado'
                 AND e.fecha_inicio >= CURDATE()",
                $user_id
            ));
        }

        // Evento más próximo
        $evento_proximo = null;
        if ($this->table_exists($tabla_eventos)) {
            $evento_proximo = $wpdb->get_row(
                "SELECT id, titulo, fecha_inicio, lugar
                 FROM {$tabla_eventos}
                 WHERE estado = 'publicado'
                 AND fecha_inicio >= CURDATE()
                 ORDER BY fecha_inicio ASC
                 LIMIT 1"
            );
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Próximos eventos
        $stats[] = [
            'icon' => 'dashicons-calendar',
            'valor' => $total_proximos,
            'label' => __('Próximos eventos', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=eventos') : home_url('/mi-portal/eventos/'),
        ];

        // Stat 2: Mis inscripciones
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-yes-alt',
                'valor' => $mis_inscripciones,
                'label' => __('Mis inscripciones', 'flavor-chat-ia'),
                'color' => $mis_inscripciones > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=eventos') : home_url('/mi-portal/eventos/mis-inscripciones/'),
            ];
        }

        // Stat 3: Próximo evento
        if ($evento_proximo) {
            $fecha_formateada = date_i18n('j M', strtotime($evento_proximo->fecha_inicio));
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $fecha_formateada,
                'label' => wp_trim_words($evento_proximo->titulo, 3, '...'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=eventos&evento=' . $evento_proximo->id) : home_url('/mi-portal/eventos/ver/' . $evento_proximo->id . '/'),
            ];
        }

        // Items: próximos eventos
        $items = $this->get_proximos_eventos(5);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay eventos próximos programados', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $es_admin ? __('Gestionar eventos', 'flavor-chat-ia') : __('Ver calendario', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=eventos') : home_url('/mi-portal/eventos/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene los próximos eventos
     *
     * @param int $limite Número máximo de eventos
     * @return array
     */
    private function get_proximos_eventos(int $limite = 5): array {
        global $wpdb;

        $tabla_eventos = $this->prefix_tabla . 'eventos';

        if (!$this->table_exists($tabla_eventos)) {
            return [];
        }

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_inicio, lugar, aforo_maximo, aforo_actual
             FROM {$tabla_eventos}
             WHERE estado = 'publicado'
             AND fecha_inicio >= CURDATE()
             ORDER BY fecha_inicio ASC
             LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($eventos as $evento) {
            $fecha = date_i18n('j M, H:i', strtotime($evento->fecha_inicio));
            $plazas = '';
            if ($evento->aforo_maximo > 0) {
                $disponibles = $evento->aforo_maximo - ($evento->aforo_actual ?? 0);
                $plazas = sprintf(__('%d plazas', 'flavor-chat-ia'), $disponibles);
            }

            $items[] = [
                'icon' => 'dashicons-calendar-alt',
                'title' => $evento->titulo,
                'meta' => $fecha . ($evento->lugar ? ' · ' . $evento->lugar : ''),
                'url' => $es_admin ? admin_url('admin.php?page=eventos&evento=' . $evento->id) : home_url('/mi-portal/eventos/ver/' . $evento->id . '/'),
                'badge' => $plazas,
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
