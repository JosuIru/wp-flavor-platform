<?php
/**
 * Registro Central de Widgets del Dashboard Unificado
 *
 * Singleton que gestiona el registro, obtencion y filtrado de widgets
 * provenientes de los distintos modulos del sistema.
 *
 * @package FlavorChatIA
 * @subpackage Dashboard
 * @since 4.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Widget Registry
 *
 * @since 4.0.0
 */
class Flavor_Widget_Registry {

    /**
     * Instancia singleton
     *
     * @var Flavor_Widget_Registry|null
     */
    private static $instance = null;

    /**
     * Widgets registrados
     *
     * @var array<string, Flavor_Dashboard_Widget_Interface>
     */
    private $widgets = [];

    /**
     * Categorias disponibles
     *
     * @var array
     */
    private $categories = [];

    /**
     * Flag de inicializacion
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Widget_Registry
     */
    public static function get_instance(): Flavor_Widget_Registry {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_categories();
        $this->init_hooks();
    }

    /**
     * Inicializa las categorias de widgets
     *
     * Las 9 categorias principales + sistema agrupan todos los widgets del dashboard.
     * Cada categoria tiene un color asociado definido en design-tokens.css (--fl-cat-*)
     *
     * @return void
     * @since 4.1.0 Ampliado a 9 categorias
     */
    private function init_categories(): void {
        $this->categories = [
            // Categoria: Personas - Usuarios, empleados, socios
            'personas' => [
                'label'       => __('Personas', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-users',
                'order'       => 10,
                'description' => __('Gestion de usuarios, empleados y socios', 'flavor-chat-ia'),
                'color'       => '#ec4899', // Rosa
            ],

            // Categoria: Economia - Finanzas, transacciones, marketplace
            'economia' => [
                'label'       => __('Economia', 'flavor-chat-ia'),
                'icon'        => 'dashicons-chart-line',
                'order'       => 20,
                'description' => __('Finanzas, transacciones y comercio', 'flavor-chat-ia'),
                'color'       => '#10b981', // Verde
            ],

            // Categoria: Operaciones - Reservas, fichaje, incidencias
            'operaciones' => [
                'label'       => __('Operaciones', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-tools',
                'order'       => 30,
                'description' => __('Operaciones diarias y gestion', 'flavor-chat-ia'),
                'color'       => '#f97316', // Naranja
            ],

            // Categoria: Recursos - Espacios, equipamiento, biblioteca
            'recursos' => [
                'label'       => __('Recursos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-archive',
                'order'       => 40,
                'description' => __('Espacios, equipamiento y recursos compartidos', 'flavor-chat-ia'),
                'color'       => '#14b8a6', // Teal
            ],

            // Categoria: Comunicacion - Chat, foros, avisos
            'comunicacion' => [
                'label'       => __('Comunicacion', 'flavor-chat-ia'),
                'icon'        => 'dashicons-megaphone',
                'order'       => 50,
                'description' => __('Canales de comunicacion y mensajeria', 'flavor-chat-ia'),
                'color'       => '#8b5cf6', // Violeta
            ],

            // Categoria: Actividades - Eventos, cursos, talleres
            'actividades' => [
                'label'       => __('Actividades', 'flavor-chat-ia'),
                'icon'        => 'dashicons-calendar-alt',
                'order'       => 60,
                'description' => __('Eventos, cursos y formacion', 'flavor-chat-ia'),
                'color'       => '#a855f7', // Purple
            ],

            // Categoria: Servicios - Tramites, ayuda, soporte
            'servicios' => [
                'label'       => __('Servicios', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-site',
                'order'       => 70,
                'description' => __('Tramites, servicios y soporte', 'flavor-chat-ia'),
                'color'       => '#0ea5e9', // Sky
            ],

            // Categoria: Comunidad - Red social, participacion
            'comunidad' => [
                'label'       => __('Comunidad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-groups',
                'order'       => 80,
                'description' => __('Vida comunitaria y participacion', 'flavor-chat-ia'),
                'color'       => '#f59e0b', // Amarillo
            ],

            // Categoria: Sostenibilidad - Huertos, reciclaje, movilidad
            'sostenibilidad' => [
                'label'       => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-palmtree',
                'order'       => 90,
                'description' => __('Medio ambiente y sostenibilidad', 'flavor-chat-ia'),
                'color'       => '#84cc16', // Lima
            ],

            // Categoria legacy: Gestion (alias de operaciones)
            'gestion' => [
                'label'       => __('Gestion', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clipboard',
                'order'       => 35,
                'description' => __('Gestion general', 'flavor-chat-ia'),
                'color'       => '#3b82f6', // Azul
            ],

            // Categoria: Red de Nodos - Federacion
            'red' => [
                'label'       => __('Red de Nodos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-networking',
                'order'       => 95,
                'description' => __('Red federada de comunidades', 'flavor-chat-ia'),
                'color'       => '#06b6d4', // Cyan
            ],

            // Categoria: Sistema - Configuracion, estado
            'sistema' => [
                'label'       => __('Sistema', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-generic',
                'order'       => 100,
                'description' => __('Configuracion y estado del sistema', 'flavor-chat-ia'),
                'color'       => '#6b7280', // Gris
            ],
        ];

        /**
         * Filtro para modificar las categorias de widgets
         *
         * @param array $categories Categorias existentes
         */
        $this->categories = apply_filters('flavor_dashboard_widget_categories', $this->categories);
    }

    /**
     * Inicializa los hooks de WordPress
     *
     * @return void
     */
    private function init_hooks(): void {
        // Inicializar widgets en 'init' con prioridad 20 (despues de que los modulos se carguen)
        add_action('init', [$this, 'initialize_widgets'], 20);

        // AJAX para actualizar widget
        add_action('wp_ajax_fud_refresh_widget', [$this, 'ajax_refresh_widget']);

        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Inicializa los widgets disparando el hook de registro
     *
     * @return void
     */
    public function initialize_widgets(): void {
        if ($this->initialized) {
            return;
        }

        /**
         * Hook para que los modulos registren sus widgets
         *
         * Ejemplo de uso:
         *   add_action('flavor_register_dashboard_widgets', function($registry) {
         *       $registry->register(new Mi_Widget());
         *   });
         *
         * @param Flavor_Widget_Registry $registry Instancia del registro
         */
        do_action('flavor_register_dashboard_widgets', $this);

        $this->initialized = true;
    }

    /**
     * Registra un widget en el sistema
     *
     * @param Flavor_Dashboard_Widget_Interface $widget Widget a registrar
     * @return bool True si se registro correctamente
     */
    public function register(Flavor_Dashboard_Widget_Interface $widget): bool {
        $widget_id = $widget->get_widget_id();

        if (empty($widget_id)) {
            return false;
        }

        // Verificar si ya existe
        if (isset($this->widgets[$widget_id])) {
            // Permitir sobrescribir con filtro
            if (!apply_filters('flavor_dashboard_allow_widget_override', false, $widget_id)) {
                return false;
            }
        }

        $this->widgets[$widget_id] = $widget;

        /**
         * Accion cuando se registra un widget
         *
         * @param string $widget_id ID del widget
         * @param Flavor_Dashboard_Widget_Interface $widget Widget registrado
         */
        do_action('flavor_dashboard_widget_registered', $widget_id, $widget);

        return true;
    }

    /**
     * Desregistra un widget
     *
     * @param string $widget_id ID del widget
     * @return bool True si se elimino
     */
    public function unregister(string $widget_id): bool {
        if (!isset($this->widgets[$widget_id])) {
            return false;
        }

        unset($this->widgets[$widget_id]);
        return true;
    }

    /**
     * Obtiene un widget por su ID
     *
     * @param string $widget_id ID del widget
     * @return Flavor_Dashboard_Widget_Interface|null
     */
    public function get_widget(string $widget_id): ?Flavor_Dashboard_Widget_Interface {
        return $this->widgets[$widget_id] ?? null;
    }

    /**
     * Obtiene todos los widgets registrados
     *
     * @param bool $include_config Si incluir la configuracion de cada widget
     * @return array
     */
    public function get_all(bool $include_config = false): array {
        if (!$include_config) {
            return $this->widgets;
        }

        $widgets_con_config = [];
        foreach ($this->widgets as $widget_id => $widget) {
            $widgets_con_config[$widget_id] = [
                'widget' => $widget,
                'config' => $widget->get_widget_config(),
            ];
        }

        return $widgets_con_config;
    }

    /**
     * Obtiene widgets filtrados por categoria
     *
     * @param string $category Categoria a filtrar
     * @return array
     */
    public function get_by_category(string $category): array {
        $filtered = [];

        foreach ($this->widgets as $widget_id => $widget) {
            $config = $widget->get_widget_config();
            if (($config['category'] ?? 'sistema') === $category) {
                $filtered[$widget_id] = $widget;
            }
        }

        return $filtered;
    }

    /**
     * Obtiene widgets ordenados por prioridad
     *
     * @return array
     */
    public function get_sorted(): array {
        $widgets_con_prioridad = [];

        foreach ($this->widgets as $widget_id => $widget) {
            $config = $widget->get_widget_config();
            $widgets_con_prioridad[$widget_id] = [
                'widget'   => $widget,
                'priority' => $config['priority'] ?? 50,
                'category' => $config['category'] ?? 'sistema',
            ];
        }

        // Ordenar por categoria primero, luego por prioridad
        uasort($widgets_con_prioridad, function ($widget_a, $widget_b) {
            $orden_cat_a = $this->categories[$widget_a['category']]['order'] ?? 999;
            $orden_cat_b = $this->categories[$widget_b['category']]['order'] ?? 999;

            if ($orden_cat_a !== $orden_cat_b) {
                return $orden_cat_a - $orden_cat_b;
            }

            return $widget_a['priority'] - $widget_b['priority'];
        });

        // Devolver solo los widgets
        return array_map(function ($item) {
            return $item['widget'];
        }, $widgets_con_prioridad);
    }

    /**
     * Obtiene las categorias disponibles
     *
     * @return array
     */
    public function get_categories(): array {
        return $this->categories;
    }

    /**
     * Obtiene categorias con conteo de widgets
     *
     * @return array
     */
    public function get_categories_with_count(): array {
        $categorias_con_conteo = [];

        foreach ($this->categories as $categoria_id => $categoria_info) {
            $widgets_en_categoria = $this->get_by_category($categoria_id);
            $categorias_con_conteo[$categoria_id] = array_merge($categoria_info, [
                'count' => count($widgets_en_categoria),
            ]);
        }

        // Ordenar por order
        uasort($categorias_con_conteo, function ($categoria_a, $categoria_b) {
            return ($categoria_a['order'] ?? 999) - ($categoria_b['order'] ?? 999);
        });

        return $categorias_con_conteo;
    }

    /**
     * Obtiene widgets agrupados por categoria
     *
     * Devuelve un array asociativo donde cada clave es una categoria
     * y el valor contiene la informacion de la categoria y sus widgets.
     *
     * @return array {
     *     @type array $categoria_id {
     *         @type array  $info      Informacion de la categoria (label, icon, order)
     *         @type array  $widgets   Widgets de esta categoria
     *         @type bool   $collapsed Si la categoria esta colapsada por el usuario
     *         @type int    $count     Numero de widgets
     *     }
     * }
     * @since 4.1.0
     */
    public function get_grouped_by_category(): array {
        $agrupados = [];
        $categorias = $this->get_categories();
        $categorias_colapsadas = $this->get_user_collapsed_categories();

        // Ordenar categorias por su orden
        uasort($categorias, function ($categoria_a, $categoria_b) {
            return ($categoria_a['order'] ?? 999) - ($categoria_b['order'] ?? 999);
        });

        foreach ($categorias as $categoria_id => $categoria_info) {
            $widgets_categoria = $this->get_by_category($categoria_id);

            // Solo incluir categorias con widgets
            if (empty($widgets_categoria)) {
                continue;
            }

            // Ordenar widgets de la categoria por prioridad
            uasort($widgets_categoria, function ($widget_a, $widget_b) {
                $config_a = $widget_a->get_widget_config();
                $config_b = $widget_b->get_widget_config();
                return ($config_a['priority'] ?? 50) - ($config_b['priority'] ?? 50);
            });

            $agrupados[$categoria_id] = [
                'info'      => $categoria_info,
                'widgets'   => $widgets_categoria,
                'collapsed' => in_array($categoria_id, $categorias_colapsadas, true),
                'count'     => count($widgets_categoria),
            ];
        }

        /**
         * Filtro para modificar los widgets agrupados por categoria
         *
         * @param array $agrupados Widgets agrupados
         */
        return apply_filters('flavor_dashboard_grouped_widgets', $agrupados);
    }

    /**
     * Obtiene las categorias colapsadas por el usuario
     *
     * @return array Lista de IDs de categorias colapsadas
     * @since 4.1.0
     */
    public function get_user_collapsed_categories(): array {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return [];
        }

        $colapsadas = get_user_meta($usuario_id, 'fud_collapsed_categories', true);
        return is_array($colapsadas) ? $colapsadas : [];
    }

    /**
     * Guarda las categorias colapsadas del usuario
     *
     * @param array $categoria_ids IDs de categorias colapsadas
     * @return bool
     * @since 4.1.0
     */
    public function save_user_collapsed_categories(array $categoria_ids): bool {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return false;
        }

        $ids_limpios = array_map('sanitize_key', $categoria_ids);
        return (bool) update_user_meta($usuario_id, 'fud_collapsed_categories', $ids_limpios);
    }

    /**
     * Alterna el estado colapsado de una categoria
     *
     * @param string $categoria_id ID de la categoria
     * @return bool Nuevo estado (true = colapsado)
     * @since 4.1.0
     */
    public function toggle_category_collapsed(string $categoria_id): bool {
        $colapsadas = $this->get_user_collapsed_categories();
        $categoria_id = sanitize_key($categoria_id);

        if (in_array($categoria_id, $colapsadas, true)) {
            // Quitar de colapsadas
            $colapsadas = array_diff($colapsadas, [$categoria_id]);
            $nuevo_estado = false;
        } else {
            // Agregar a colapsadas
            $colapsadas[] = $categoria_id;
            $nuevo_estado = true;
        }

        $this->save_user_collapsed_categories($colapsadas);
        return $nuevo_estado;
    }

    /**
     * Obtiene widgets de una categoria especifica con su estado
     *
     * @param string $categoria_id ID de la categoria
     * @return array|null Null si la categoria no existe
     * @since 4.1.0
     */
    public function get_category_with_widgets(string $categoria_id): ?array {
        if (!isset($this->categories[$categoria_id])) {
            return null;
        }

        $widgets = $this->get_by_category($categoria_id);
        $colapsadas = $this->get_user_collapsed_categories();

        return [
            'info'      => $this->categories[$categoria_id],
            'widgets'   => $widgets,
            'collapsed' => in_array($categoria_id, $colapsadas, true),
            'count'     => count($widgets),
        ];
    }

    /**
     * Verifica si un widget esta registrado
     *
     * @param string $widget_id ID del widget
     * @return bool
     */
    public function has_widget(string $widget_id): bool {
        return isset($this->widgets[$widget_id]);
    }

    /**
     * Obtiene el conteo total de widgets
     *
     * @return int
     */
    public function count(): int {
        return count($this->widgets);
    }

    /**
     * Obtiene IDs de widgets visibles para el usuario actual
     *
     * @return array
     */
    public function get_visible_widget_ids(): array {
        $usuario_id = get_current_user_id();
        $preferencias_guardadas = get_user_meta($usuario_id, 'fud_widget_visibility', true);

        if (!empty($preferencias_guardadas) && is_array($preferencias_guardadas)) {
            // Solo devolver widgets que existan
            return array_intersect($preferencias_guardadas, array_keys($this->widgets));
        }

        // Por defecto, todos visibles
        return array_keys($this->widgets);
    }

    /**
     * Obtiene el orden de widgets para el usuario actual
     *
     * @return array
     */
    public function get_user_widget_order(): array {
        $usuario_id = get_current_user_id();
        $orden_guardado = get_user_meta($usuario_id, 'fud_widget_order', true);

        if (!empty($orden_guardado) && is_array($orden_guardado)) {
            // Validar que los widgets existan
            $orden_valido = array_intersect($orden_guardado, array_keys($this->widgets));

            // Agregar widgets nuevos que no esten en el orden guardado
            $widgets_faltantes = array_diff(array_keys($this->widgets), $orden_valido);
            return array_merge($orden_valido, $widgets_faltantes);
        }

        // Devolver orden por prioridad
        return array_keys($this->get_sorted());
    }

    /**
     * Guarda el orden de widgets del usuario
     *
     * @param array $order Orden de IDs de widgets
     * @return bool
     */
    public function save_user_widget_order(array $order): bool {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return false;
        }

        $orden_limpio = array_map('sanitize_key', $order);
        return (bool) update_user_meta($usuario_id, 'fud_widget_order', $orden_limpio);
    }

    /**
     * Guarda la visibilidad de widgets del usuario
     *
     * @param array $visible_ids IDs de widgets visibles
     * @return bool
     */
    public function save_user_widget_visibility(array $visible_ids): bool {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return false;
        }

        $ids_limpios = array_map('sanitize_key', $visible_ids);
        return (bool) update_user_meta($usuario_id, 'fud_widget_visibility', $ids_limpios);
    }

    /**
     * Handler AJAX para refrescar un widget
     *
     * @return void
     */
    public function ajax_refresh_widget(): void {
        check_ajax_referer('fud_dashboard_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $widget_id = sanitize_key($_POST['widget_id'] ?? '');

        if (empty($widget_id) || !$this->has_widget($widget_id)) {
            wp_send_json_error(['message' => __('Widget no encontrado', 'flavor-chat-ia')]);
        }

        $widget = $this->get_widget($widget_id);

        // Limpiar cache si es posible
        if (method_exists($widget, 'clear_cache')) {
            $widget->clear_cache();
        }

        // Obtener HTML renderizado
        ob_start();
        $widget->render_widget();
        $html = ob_get_clean();

        wp_send_json_success([
            'html'      => $html,
            'widget_id' => $widget_id,
            'data'      => $widget->get_widget_data(),
            'timestamp' => current_time('c'),
        ]);
    }

    /**
     * Registra rutas REST API
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('flavor/v1', '/dashboard/widgets', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_widgets'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);

        register_rest_route('flavor/v1', '/dashboard/widgets/(?P<id>[a-z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_single_widget'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
            'args' => [
                'id' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);

        register_rest_route('flavor/v1', '/dashboard/widgets/order', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_save_order'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);

        register_rest_route('flavor/v1', '/dashboard/widgets/visibility', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_save_visibility'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);
    }

    /**
     * REST callback: Obtener todos los widgets
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_widgets(WP_REST_Request $request): WP_REST_Response {
        $categoria_filtro = $request->get_param('category');
        $incluir_html = $request->get_param('include_html') === 'true';

        $widgets = $categoria_filtro
            ? $this->get_by_category($categoria_filtro)
            : $this->get_sorted();

        $resultado = [];
        foreach ($widgets as $widget_id => $widget) {
            $config = $widget->get_widget_config();
            $item = [
                'id'     => $widget_id,
                'config' => $config,
                'data'   => $widget->get_widget_data(),
            ];

            if ($incluir_html) {
                ob_start();
                $widget->render_widget();
                $item['html'] = ob_get_clean();
            }

            $resultado[] = $item;
        }

        return new WP_REST_Response([
            'success'    => true,
            'widgets'    => $resultado,
            'categories' => $this->get_categories_with_count(),
            'user_order' => $this->get_user_widget_order(),
        ], 200);
    }

    /**
     * REST callback: Obtener un widget
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_single_widget(WP_REST_Request $request): WP_REST_Response {
        $widget_id = $request->get_param('id');

        if (!$this->has_widget($widget_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Widget no encontrado', 'flavor-chat-ia'),
            ], 404);
        }

        $widget = $this->get_widget($widget_id);
        $config = $widget->get_widget_config();

        ob_start();
        $widget->render_widget();
        $html = ob_get_clean();

        return new WP_REST_Response([
            'success' => true,
            'widget'  => [
                'id'     => $widget_id,
                'config' => $config,
                'data'   => $widget->get_widget_data(),
                'html'   => $html,
            ],
        ], 200);
    }

    /**
     * REST callback: Guardar orden de widgets
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_save_order(WP_REST_Request $request): WP_REST_Response {
        $order = $request->get_param('order');

        if (!is_array($order)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Formato de orden invalido', 'flavor-chat-ia'),
            ], 400);
        }

        $saved = $this->save_user_widget_order($order);

        return new WP_REST_Response([
            'success' => $saved,
            'message' => $saved
                ? __('Orden guardado correctamente', 'flavor-chat-ia')
                : __('Error al guardar el orden', 'flavor-chat-ia'),
        ], $saved ? 200 : 500);
    }

    /**
     * REST callback: Guardar visibilidad de widgets
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_save_visibility(WP_REST_Request $request): WP_REST_Response {
        $visible = $request->get_param('visible');

        if (!is_array($visible)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Formato de visibilidad invalido', 'flavor-chat-ia'),
            ], 400);
        }

        $saved = $this->save_user_widget_visibility($visible);

        return new WP_REST_Response([
            'success' => $saved,
            'message' => $saved
                ? __('Visibilidad guardada correctamente', 'flavor-chat-ia')
                : __('Error al guardar la visibilidad', 'flavor-chat-ia'),
        ], $saved ? 200 : 500);
    }
}

/**
 * Funcion helper para obtener la instancia del registro
 *
 * @return Flavor_Widget_Registry
 */
function flavor_widget_registry(): Flavor_Widget_Registry {
    return Flavor_Widget_Registry::get_instance();
}
