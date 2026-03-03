<?php
/**
 * Widget de Dashboard para Comunidades
 *
 * Muestra estadísticas del módulo de comunidades en el dashboard unificado:
 * - Mis comunidades
 * - Actividad reciente
 * - Comunidades sugeridas
 *
 * @package FlavorChatIA
 * @subpackage Modules\Comunidades
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Comunidades
 *
 * @since 4.1.0
 */
class Flavor_Comunidades_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'comunidades';

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
    protected $icon = 'dashicons-groups';

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
    protected $priority = 10;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_comunidades_';
        $this->title = __('Comunidades', 'flavor-chat-ia');
        $this->description = __('Grupos y comunidades a las que perteneces', 'flavor-chat-ia');

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

        $tabla_comunidades = $this->prefix_tabla . 'comunidades';
        $tabla_miembros = $this->prefix_tabla . 'miembros';
        $tabla_publicaciones = $this->prefix_tabla . 'publicaciones';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Mis comunidades
        $mis_comunidades = 0;
        if ($user_id && $this->table_exists($tabla_miembros)) {
            $mis_comunidades = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        // Comunidades donde soy admin/moderador
        $soy_admin = 0;
        if ($user_id && $this->table_exists($tabla_miembros)) {
            $soy_admin = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros}
                 WHERE usuario_id = %d AND rol IN ('admin', 'moderador') AND estado = 'activo'",
                $user_id
            ));
        }

        // Total comunidades públicas
        $total_comunidades = 0;
        if ($this->table_exists($tabla_comunidades)) {
            $total_comunidades = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_comunidades}
                 WHERE estado = 'activo' AND visibilidad IN ('publica', 'visible')"
            );
        }

        // Publicaciones sin leer en mis comunidades
        $publicaciones_nuevas = 0;
        if ($user_id && $this->table_exists($tabla_publicaciones) && $this->table_exists($tabla_miembros)) {
            $publicaciones_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones} p
                 INNER JOIN {$tabla_miembros} m ON p.comunidad_id = m.comunidad_id
                 WHERE m.usuario_id = %d
                 AND m.estado = 'activo'
                 AND p.fecha_creacion > m.ultima_visita",
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Mis comunidades
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-groups',
                'valor' => $mis_comunidades,
                'label' => __('Mis comunidades', 'flavor-chat-ia'),
                'color' => $mis_comunidades > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=comunidades') : home_url('/mi-portal/comunidades/mis-comunidades/'),
            ];
        }

        // Stat 2: Publicaciones nuevas
        if ($user_id && $publicaciones_nuevas > 0) {
            $stats[] = [
                'icon' => 'dashicons-format-chat',
                'valor' => $publicaciones_nuevas,
                'label' => __('Nuevas publicaciones', 'flavor-chat-ia'),
                'color' => 'warning',
                'url' => $es_admin ? admin_url('admin.php?page=comunidades') : home_url('/mi-portal/comunidades/actividad/'),
            ];
        }

        // Stat 3: Administro
        if ($user_id && $soy_admin > 0) {
            $stats[] = [
                'icon' => 'dashicons-shield-alt',
                'valor' => $soy_admin,
                'label' => __('Administro', 'flavor-chat-ia'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=comunidades&rol=admin') : home_url('/mi-portal/comunidades/mis-comunidades/'),
            ];
        }

        // Stat 4: Explorar comunidades
        $stats[] = [
            'icon' => 'dashicons-search',
            'valor' => $total_comunidades,
            'label' => __('Comunidades', 'flavor-chat-ia'),
            'color' => 'info',
            'url' => $es_admin ? admin_url('admin.php?page=comunidades') : home_url('/mi-portal/comunidades/explorar/'),
        ];

        // Items: mis comunidades recientes
        $items = $this->get_mis_comunidades($user_id, 5);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => $user_id ? __('Aún no perteneces a ninguna comunidad', 'flavor-chat-ia') : __('Inicia sesión para ver tus comunidades', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $user_id ? __('Explorar comunidades', 'flavor-chat-ia') : __('Ver comunidades', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=comunidades') : home_url('/mi-portal/comunidades/explorar/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene las comunidades del usuario
     *
     * @param int $user_id ID del usuario
     * @param int $limite Número máximo de comunidades
     * @return array
     */
    private function get_mis_comunidades(int $user_id, int $limite = 5): array {
        global $wpdb;

        if (!$user_id) {
            return [];
        }

        $tabla_comunidades = $this->prefix_tabla . 'comunidades';
        $tabla_miembros = $this->prefix_tabla . 'miembros';

        if (!$this->table_exists($tabla_comunidades) || !$this->table_exists($tabla_miembros)) {
            return [];
        }

        $comunidades = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.nombre, c.icono, m.rol, m.fecha_union
             FROM {$tabla_comunidades} c
             INNER JOIN {$tabla_miembros} m ON c.id = m.comunidad_id
             WHERE m.usuario_id = %d AND m.estado = 'activo'
             ORDER BY m.fecha_union DESC
             LIMIT %d",
            $user_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($comunidades as $comunidad) {
            $rol_texto = '';
            if ($comunidad->rol === 'admin') {
                $rol_texto = __('Admin', 'flavor-chat-ia');
            } elseif ($comunidad->rol === 'moderador') {
                $rol_texto = __('Mod', 'flavor-chat-ia');
            }

            $items[] = [
                'icon' => $comunidad->icono ?: 'dashicons-groups',
                'title' => $comunidad->nombre,
                'meta' => $rol_texto ?: __('Miembro', 'flavor-chat-ia'),
                'url' => $es_admin ? admin_url('admin.php?page=comunidades&id=' . $comunidad->id) : home_url('/mi-portal/comunidades/' . $comunidad->id . '/'),
                'badge' => $rol_texto,
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
