<?php
/**
 * Widget de Dashboard para Grupos de Consumo
 *
 * Muestra estadísticas del módulo de grupos de consumo en el dashboard unificado:
 * - Ciclo activo y tiempo restante
 * - Gasto del mes actual
 * - Items en cesta
 * - Pedidos del usuario
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo
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
 * Widget de Dashboard para Grupos de Consumo
 *
 * @since 4.1.0
 */
class Flavor_GC_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'grupos-consumo';

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
    protected $icon = 'dashicons-store';

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
    protected $priority = 30;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_gc_';
        $this->title = __('Grupos de Consumo', 'flavor-chat-ia');
        $this->description = __('Gestiona tus pedidos y compras colaborativas', 'flavor-chat-ia');

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

        if (!$user_id) {
            return [
                'stats' => [],
                'items' => [],
                'empty_state' => __('Inicia sesión para ver tus datos', 'flavor-chat-ia'),
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

        // Obtener ciclo activo
        $ciclo_activo = $this->get_ciclo_activo();

        // Estadísticas del usuario
        $tabla_pedidos = $this->prefix_tabla . 'pedidos';
        $tabla_lista = $this->prefix_tabla . 'lista_compra';
        $tabla_pagos_gc = $this->prefix_tabla . 'pagos';

        // Gasto del mes
        $gasto_mes = 0.0;
        if ($this->table_exists($tabla_pedidos)) {
            $gasto_mes = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(cantidad * precio_unitario), 0)
                 FROM {$tabla_pedidos}
                 WHERE usuario_id = %d AND fecha_pedido >= %s",
                $user_id,
                gmdate('Y-m-01')
            ));
        }

        // Pedidos del ciclo actual
        $pedidos_ciclo = 0;
        if ($ciclo_activo && $this->table_exists($tabla_pedidos)) {
            $pedidos_ciclo = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_pedidos}
                 WHERE usuario_id = %d AND ciclo_id = %d",
                $user_id,
                $ciclo_activo['id']
            ));
        }

        // Items en cesta
        $items_cesta = 0;
        if ($this->table_exists($tabla_lista)) {
            $items_cesta = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_lista}
                 WHERE usuario_id = %d",
                $user_id
            ));
        }

        // Pagos pendientes
        $pagos_pendientes = 0;
        if ($this->table_exists($tabla_pagos_gc)) {
            $pagos_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_pagos_gc}
                 WHERE usuario_id = %d AND estado IN ('pendiente', 'procesando')",
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        // Detectar contexto admin/frontend
        $es_admin = is_admin() && !wp_doing_ajax();

        // Stat 1: Ciclo activo o estado
        if ($ciclo_activo) {
            $stats[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $ciclo_activo['titulo'],
                'label' => __('Ciclo activo', 'flavor-chat-ia'),
                'color' => 'success',
                'url' => $es_admin ? admin_url('admin.php?page=grupos-consumo') : Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos'),
            ];

            // Stat 2: Tiempo restante
            $tiempo_restante = $this->calcular_tiempo_restante($ciclo_activo['fecha_cierre']);
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $tiempo_restante,
                'label' => __('Cierra en', 'flavor-chat-ia'),
                'color' => $this->get_urgencia_color($ciclo_activo['fecha_cierre']),
            ];
        } else {
            $stats[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => __('Sin ciclo', 'flavor-chat-ia'),
                'label' => __('Ciclo activo', 'flavor-chat-ia'),
                'color' => 'gray',
            ];
        }

        // Stat 3: Gasto del mes
        $stats[] = [
            'icon' => 'dashicons-chart-line',
            'valor' => number_format($gasto_mes, 2, ',', '.') . ' €',
            'label' => __('Este mes', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=gc-pedidos') : Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos'),
        ];

        // Stat 4: Items en cesta
        $stats[] = [
            'icon' => 'dashicons-cart',
            'valor' => $items_cesta,
            'label' => __('En cesta', 'flavor-chat-ia'),
            'color' => $items_cesta > 0 ? 'warning' : 'gray',
            'url' => $es_admin ? admin_url('admin.php?page=gc-pedidos') : Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido'),
        ];

        // Items: últimos productos añadidos a cesta
        $items = $this->get_ultimos_items_cesta($user_id, 3);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No tienes productos en tu cesta', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $es_admin ? __('Ver panel', 'flavor-chat-ia') : __('Ver productos', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=grupos-consumo') : Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
            'extra' => [
                'pedidos_ciclo' => $pedidos_ciclo,
                'pagos_pendientes' => $pagos_pendientes,
                'ciclo_activo' => $ciclo_activo !== null,
            ],
        ];
    }

    /**
     * Obtiene el ciclo activo
     *
     * @return array|null
     */
    private function get_ciclo_activo(): ?array {
        // Intentar primero con CPT
        $args = [
            'post_type' => 'gc_ciclo',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_gc_estado',
                    'value' => 'abierto',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_gc_fecha_cierre',
            'order' => 'ASC',
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $ciclo_post = $query->posts[0];
            return [
                'id' => $ciclo_post->ID,
                'titulo' => $ciclo_post->post_title,
                'fecha_cierre' => get_post_meta($ciclo_post->ID, '_gc_fecha_cierre', true),
                'fecha_entrega' => get_post_meta($ciclo_post->ID, '_gc_fecha_entrega', true),
            ];
        }

        // Fallback: buscar en tabla personalizada
        global $wpdb;
        $tabla_ciclos = $this->prefix_tabla . 'ciclos';

        if (!$this->table_exists($tabla_ciclos)) {
            return null;
        }

        $ciclo = $wpdb->get_row(
            "SELECT id, titulo, fecha_cierre, fecha_entrega
             FROM {$tabla_ciclos}
             WHERE estado = 'abierto' AND fecha_cierre > NOW()
             ORDER BY fecha_cierre ASC
             LIMIT 1"
        );

        if ($ciclo) {
            return [
                'id' => (int) $ciclo->id,
                'titulo' => $ciclo->titulo,
                'fecha_cierre' => $ciclo->fecha_cierre,
                'fecha_entrega' => $ciclo->fecha_entrega,
            ];
        }

        return null;
    }

    /**
     * Calcula el tiempo restante hasta una fecha
     *
     * @param string $fecha_cierre Fecha de cierre
     * @return string
     */
    private function calcular_tiempo_restante(string $fecha_cierre): string {
        $timestamp_cierre = strtotime($fecha_cierre);
        $timestamp_actual = current_time('timestamp');
        $diferencia = $timestamp_cierre - $timestamp_actual;

        if ($diferencia <= 0) {
            return __('Cerrado', 'flavor-chat-ia');
        }

        if ($diferencia < 3600) {
            // Menos de 1 hora
            $minutos = ceil($diferencia / 60);
            return sprintf(__('%d min', 'flavor-chat-ia'), $minutos);
        }

        if ($diferencia < 86400) {
            // Menos de 24 horas
            $horas = ceil($diferencia / 3600);
            return sprintf(__('%d h', 'flavor-chat-ia'), $horas);
        }

        // Días
        $dias = ceil($diferencia / 86400);
        return sprintf(_n('%d día', '%d días', $dias, 'flavor-chat-ia'), $dias);
    }

    /**
     * Obtiene el color de urgencia basado en el tiempo restante
     *
     * @param string $fecha_cierre Fecha de cierre
     * @return string
     */
    private function get_urgencia_color(string $fecha_cierre): string {
        $timestamp_cierre = strtotime($fecha_cierre);
        $timestamp_actual = current_time('timestamp');
        $diferencia = $timestamp_cierre - $timestamp_actual;

        if ($diferencia <= 0) {
            return 'gray';
        }

        if ($diferencia < 3600) {
            // Menos de 1 hora: rojo urgente
            return 'danger';
        }

        if ($diferencia < 86400) {
            // Menos de 24 horas: naranja
            return 'warning';
        }

        if ($diferencia < 259200) {
            // Menos de 3 días: azul
            return 'info';
        }

        // Más de 3 días: verde
        return 'success';
    }

    /**
     * Obtiene los últimos items añadidos a la cesta
     *
     * @param int $user_id ID del usuario
     * @param int $limite Número máximo de items
     * @return array
     */
    private function get_ultimos_items_cesta(int $user_id, int $limite = 3): array {
        global $wpdb;
        $tabla_lista = $this->prefix_tabla . 'lista_compra';

        if (!$this->table_exists($tabla_lista)) {
            return [];
        }

        $items_db = $wpdb->get_results($wpdb->prepare(
            "SELECT lc.*, p.post_title as nombre_producto
             FROM {$tabla_lista} lc
             LEFT JOIN {$wpdb->posts} p ON lc.producto_id = p.ID
             WHERE lc.usuario_id = %d
             ORDER BY lc.fecha_agregado DESC
             LIMIT %d",
            $user_id,
            $limite
        ));

        $es_admin_items = is_admin() && !wp_doing_ajax();
        $url_cesta = $es_admin_items ? admin_url('admin.php?page=gc-pedidos') : Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido');

        $items = [];
        foreach ($items_db as $item) {
            $items[] = [
                'icon' => 'dashicons-carrot',
                'title' => $item->nombre_producto ?: __('Producto', 'flavor-chat-ia'),
                'meta' => sprintf(__('Cantidad: %d', 'flavor-chat-ia'), $item->cantidad),
                'url' => $url_cesta,
            ];
        }

        return $items;
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
     * Renderiza el contenido del widget
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
