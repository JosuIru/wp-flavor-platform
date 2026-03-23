<?php
/**
 * Widget de Dashboard para Marketplace
 *
 * Muestra estadísticas del módulo de marketplace en el dashboard unificado:
 * - Mis anuncios activos
 * - Mensajes pendientes
 * - Anuncios destacados
 *
 * @package FlavorChatIA
 * @subpackage Modules\Marketplace
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Marketplace
 *
 * @since 4.1.0
 */
class Flavor_Marketplace_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'marketplace';

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
    protected $icon = 'dashicons-cart';

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
    protected $priority = 20;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_mk_';
        $this->title = __('Marketplace', 'flavor-chat-ia');
        $this->description = __('Compra, vende e intercambia en tu comunidad', 'flavor-chat-ia');

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

        $tabla_anuncios = $this->prefix_tabla . 'anuncios';
        $tabla_mensajes = $this->prefix_tabla . 'mensajes';
        $tabla_favoritos = $this->prefix_tabla . 'favoritos';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Mis anuncios activos
        $mis_anuncios = 0;
        if ($user_id && $this->table_exists($tabla_anuncios)) {
            $mis_anuncios = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_anuncios}
                 WHERE autor_id = %d AND estado = 'activo'",
                $user_id
            ));
        }

        // Mensajes sin leer
        $mensajes_sin_leer = 0;
        if ($user_id && $this->table_exists($tabla_mensajes)) {
            $mensajes_sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes}
                 WHERE destinatario_id = %d AND leido = 0",
                $user_id
            ));
        }

        // Mis favoritos
        $mis_favoritos = 0;
        if ($user_id && $this->table_exists($tabla_favoritos)) {
            $mis_favoritos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_favoritos}
                 WHERE usuario_id = %d",
                $user_id
            ));
        }

        // Total anuncios activos en plataforma
        $total_anuncios = 0;
        if ($this->table_exists($tabla_anuncios)) {
            $total_anuncios = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_anuncios} WHERE estado = 'activo'"
            );
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Mis anuncios
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-megaphone',
                'valor' => $mis_anuncios,
                'label' => __('Mis anuncios', 'flavor-chat-ia'),
                'color' => $mis_anuncios > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=marketplace-anuncios') : Flavor_Chat_Helpers::get_action_url('marketplace', '') . '?tab=mis-anuncios',
            ];
        }

        // Stat 2: Mensajes
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-email',
                'valor' => $mensajes_sin_leer,
                'label' => __('Mensajes nuevos', 'flavor-chat-ia'),
                'color' => $mensajes_sin_leer > 0 ? 'warning' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=marketplace-moderacion') : Flavor_Chat_Helpers::get_action_url('marketplace', ''),
            ];
        }

        // Stat 3: Total en plataforma
        $stats[] = [
            'icon' => 'dashicons-tag',
            'valor' => $total_anuncios,
            'label' => __('Anuncios activos', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=marketplace-dashboard') : Flavor_Chat_Helpers::get_action_url('marketplace', ''),
        ];

        // Stat 4: Favoritos
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-heart',
                'valor' => $mis_favoritos,
                'label' => __('Favoritos', 'flavor-chat-ia'),
                'color' => $mis_favoritos > 0 ? 'danger' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=marketplace-anuncios') : Flavor_Chat_Helpers::get_action_url('marketplace', ''),
            ];
        }

        // Items: últimos anuncios
        $items = $this->get_ultimos_anuncios(5);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay anuncios disponibles', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $user_id ? __('Publicar anuncio', 'flavor-chat-ia') : __('Ver anuncios', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('post-new.php?post_type=marketplace_item') : Flavor_Chat_Helpers::get_action_url('marketplace', $user_id ? 'publicar' : ''),
                    'icon' => $user_id ? 'dashicons-plus-alt' : 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene los últimos anuncios
     *
     * @param int $limite Número máximo de anuncios
     * @return array
     */
    private function get_ultimos_anuncios(int $limite = 5): array {
        global $wpdb;

        $tabla_anuncios = $this->prefix_tabla . 'anuncios';

        if (!$this->table_exists($tabla_anuncios)) {
            return [];
        }

        $anuncios = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, precio, tipo, fecha_creacion
             FROM {$tabla_anuncios}
             WHERE estado = 'activo'
             ORDER BY fecha_creacion DESC
             LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($anuncios as $anuncio) {
            $precio_texto = '';
            if ($anuncio->precio > 0) {
                $precio_texto = number_format($anuncio->precio, 2, ',', '.') . ' €';
            } elseif ($anuncio->tipo === 'regalo') {
                $precio_texto = __('Gratis', 'flavor-chat-ia');
            } elseif ($anuncio->tipo === 'intercambio') {
                $precio_texto = __('Intercambio', 'flavor-chat-ia');
            }

            $icono = 'dashicons-tag';
            if ($anuncio->tipo === 'regalo') {
                $icono = 'dashicons-heart';
            } elseif ($anuncio->tipo === 'intercambio') {
                $icono = 'dashicons-update';
            }

            $items[] = [
                'icon' => $icono,
                'title' => wp_trim_words($anuncio->titulo, 6, '...'),
                'meta' => $precio_texto,
                'url' => $es_admin ? admin_url('edit.php?post_type=marketplace_item') : Flavor_Chat_Helpers::get_action_url('marketplace', 'detalle') . '?anuncio_id=' . $anuncio->id,
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
