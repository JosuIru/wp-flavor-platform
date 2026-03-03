<?php
/**
 * Widget de Dashboard para Foros
 *
 * Muestra estadísticas del módulo de foros en el dashboard unificado:
 * - Temas recientes
 * - Mis participaciones
 * - Respuestas sin leer
 *
 * @package FlavorChatIA
 * @subpackage Modules\Foros
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Foros
 *
 * @since 4.1.0
 */
class Flavor_Foros_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'foros';

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
    protected $icon = 'dashicons-format-chat';

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
    protected $category = 'comunicacion';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 18;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_foros_';
        $this->title = __('Foros', 'flavor-chat-ia');
        $this->description = __('Debates y discusiones de la comunidad', 'flavor-chat-ia');

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

        $tabla_hilos = $this->prefix_tabla . 'hilos';
        $tabla_respuestas = $this->prefix_tabla . 'respuestas';
        $tabla_suscripciones = $this->prefix_tabla . 'suscripciones';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Total temas activos
        $total_temas = 0;
        if ($this->table_exists($tabla_hilos)) {
            $total_temas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_hilos}
                 WHERE estado = 'abierto'"
            );
        }

        // Mis temas
        $mis_temas = 0;
        if ($user_id && $this->table_exists($tabla_hilos)) {
            $mis_temas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_hilos}
                 WHERE autor_id = %d",
                $user_id
            ));
        }

        // Mis respuestas
        $mis_respuestas = 0;
        if ($user_id && $this->table_exists($tabla_respuestas)) {
            $mis_respuestas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_respuestas}
                 WHERE autor_id = %d",
                $user_id
            ));
        }

        // Respuestas nuevas en temas suscritos
        $respuestas_nuevas = 0;
        if ($user_id && $this->table_exists($tabla_suscripciones) && $this->table_exists($tabla_respuestas)) {
            $respuestas_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_respuestas} r
                 INNER JOIN {$tabla_suscripciones} s ON r.hilo_id = s.hilo_id
                 WHERE s.usuario_id = %d
                 AND r.created_at > s.ultima_lectura
                 AND r.autor_id != %d",
                $user_id,
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Temas activos
        $stats[] = [
            'icon' => 'dashicons-admin-comments',
            'valor' => $total_temas,
            'label' => __('Temas activos', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=foros') : home_url('/mi-portal/foros/'),
        ];

        // Stat 2: Respuestas nuevas
        if ($user_id && $respuestas_nuevas > 0) {
            $stats[] = [
                'icon' => 'dashicons-bell',
                'valor' => $respuestas_nuevas,
                'label' => __('Nuevas respuestas', 'flavor-chat-ia'),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=foros&filter=suscritos') : home_url('/mi-portal/foros/suscritos/'),
            ];
        }

        // Stat 3: Mis participaciones
        if ($user_id) {
            $participaciones = $mis_temas + $mis_respuestas;
            $stats[] = [
                'icon' => 'dashicons-edit',
                'valor' => $participaciones,
                'label' => __('Participaciones', 'flavor-chat-ia'),
                'color' => $participaciones > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=foros&filter=mios') : home_url('/mi-portal/foros/mis-temas/'),
            ];
        }

        // Stat 4: Crear tema
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-plus-alt2',
                'valor' => __('Nuevo', 'flavor-chat-ia'),
                'label' => __('Crear tema', 'flavor-chat-ia'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=foros&action=nuevo') : home_url('/mi-portal/foros/nuevo/'),
            ];
        }

        // Items: temas recientes
        $items = $this->get_temas_recientes(5);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay temas de discusión abiertos', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver todos los temas', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=foros') : home_url('/mi-portal/foros/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene los temas más recientes
     *
     * @param int $limite Número máximo de temas
     * @return array
     */
    private function get_temas_recientes(int $limite = 5): array {
        global $wpdb;

        $tabla_hilos = $this->prefix_tabla . 'hilos';
        $tabla_respuestas = $this->prefix_tabla . 'respuestas';

        if (!$this->table_exists($tabla_hilos)) {
            return [];
        }

        // Obtener temas con actividad reciente
        $temas = $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.titulo, t.autor_id, t.created_at, t.respuestas_count,
                    u.display_name as nombre_autor,
                    (SELECT MAX(created_at) FROM {$tabla_respuestas} WHERE hilo_id = t.id AND estado != 'eliminado') as ultima_actividad
             FROM {$tabla_hilos} t
             LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
             WHERE t.estado = 'abierto'
             ORDER BY COALESCE(ultima_actividad, t.ultima_actividad, t.created_at) DESC
             LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($temas as $tema) {
            $total_respuestas = (int) ($tema->respuestas_count ?? 0);

            $items[] = [
                'icon' => 'dashicons-format-chat',
                'title' => wp_trim_words($tema->titulo, 6, '...'),
                'meta' => $tema->nombre_autor ?: __('Anónimo', 'flavor-chat-ia'),
                'url' => $es_admin ? admin_url('admin.php?page=foros&tema=' . $tema->id) : home_url('/mi-portal/foros/tema/' . $tema->id . '/'),
                'badge' => $total_respuestas > 0 ? $total_respuestas : null,
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
