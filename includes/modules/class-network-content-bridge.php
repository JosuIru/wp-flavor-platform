<?php
/**
 * Puente entre Sistema de Integraciones y Red de Nodos
 *
 * Permite compartir contenido de módulos polivalentes entre nodos de la red.
 * Los providers pueden publicar su contenido para que sea visible
 * en otros nodos federados.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que conecta las integraciones de módulos con la red federada
 */
class Flavor_Network_Content_Bridge {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tipos de contenido compartibles en red
     */
    const NETWORK_SHAREABLE_TYPES = [
        'recetas' => [
            'label'       => 'Recetas',
            'description' => 'Recetas culinarias compartidas en la red',
            'icon'        => 'dashicons-carrot',
            'category'    => 'saberes',
        ],
        'multimedia' => [
            'label'       => 'Multimedia',
            'description' => 'Fotos y videos compartidos',
            'icon'        => 'dashicons-format-gallery',
            'category'    => 'contenido',
        ],
        'podcast' => [
            'label'       => 'Podcast',
            'description' => 'Episodios de podcast compartidos',
            'icon'        => 'dashicons-microphone',
            'category'    => 'contenido',
        ],
        'biblioteca' => [
            'label'       => 'Biblioteca',
            'description' => 'Recursos educativos y documentos',
            'icon'        => 'dashicons-book',
            'category'    => 'saberes',
        ],
        'eventos' => [
            'label'       => 'Eventos',
            'description' => 'Eventos abiertos a la red',
            'icon'        => 'dashicons-calendar-alt',
            'category'    => 'actividades',
        ],
        'cursos' => [
            'label'       => 'Cursos',
            'description' => 'Formaciones disponibles para la red',
            'icon'        => 'dashicons-welcome-learn-more',
            'category'    => 'saberes',
        ],
        'talleres' => [
            'label'       => 'Talleres',
            'description' => 'Talleres abiertos a participantes de la red',
            'icon'        => 'dashicons-hammer',
            'category'    => 'actividades',
        ],
        'productos' => [
            'label'       => 'Productos',
            'description' => 'Catálogo de productos disponibles',
            'icon'        => 'dashicons-cart',
            'category'    => 'catalogo',
        ],
        'servicios' => [
            'label'       => 'Servicios',
            'description' => 'Servicios ofrecidos a la red',
            'icon'        => 'dashicons-admin-tools',
            'category'    => 'catalogo',
        ],
        'grupos_consumo' => [
            'label'       => 'Grupos de Consumo',
            'description' => 'Grupos de consumo abiertos a nuevos miembros',
            'icon'        => 'dashicons-cart',
            'category'    => 'comunidades',
        ],
        'banco_tiempo' => [
            'label'       => 'Bancos de Tiempo',
            'description' => 'Bancos de tiempo abiertos a la red',
            'icon'        => 'dashicons-clock',
            'category'    => 'comunidades',
        ],
        'comunidades' => [
            'label'       => 'Comunidades',
            'description' => 'Comunidades abiertas a nuevos miembros',
            'icon'        => 'dashicons-groups',
            'category'    => 'comunidades',
        ],
    ];

    /**
     * Niveles de visibilidad en red
     */
    const VISIBILITY_LEVELS = [
        'privado'   => 'Solo este nodo',
        'conectado' => 'Nodos conectados',
        'federado'  => 'Nodos federados',
        'publico'   => 'Toda la red',
    ];

    /**
     * Configuración de caché para contenido federado
     */
    const CACHE_CONFIG = [
        'directory'     => 3600,      // Directorio de nodos: 1 hora
        'node_profile'  => 1800,      // Perfil de nodo: 30 min
        'shared_content' => 900,      // Contenido compartido: 15 min
        'events'        => 600,       // Eventos: 10 min
        'communities'   => 1200,      // Comunidades: 20 min
    ];

    /**
     * Niveles de confianza de nodos
     */
    const NODE_TRUST_LEVELS = [
        'no_verificado' => [
            'label' => 'No verificado',
            'icon'  => '⚪',
            'color' => '#9ca3af',
            'min_score' => 0,
        ],
        'basico' => [
            'label' => 'Verificado básico',
            'icon'  => '🔵',
            'color' => '#3b82f6',
            'min_score' => 25,
        ],
        'completo' => [
            'label' => 'Verificado completo',
            'icon'  => '🟢',
            'color' => '#10b981',
            'min_score' => 60,
        ],
        'referencia' => [
            'label' => 'Nodo de referencia',
            'icon'  => '⭐',
            'color' => '#f59e0b',
            'min_score' => 90,
        ],
    ];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Añadir campo de visibilidad en red a los metaboxes de providers
        add_action('flavor_integration_provider_metabox_after', [$this, 'render_network_visibility_field'], 10, 2);
        add_action('flavor_integration_save_provider_meta', [$this, 'save_network_visibility'], 10, 2);

        // Sincronizar contenido compartido con la red
        add_action('save_post', [$this, 'sync_content_to_network'], 100, 2);
        add_action('before_delete_post', [$this, 'remove_content_from_network']);

        // REST API endpoints para contenido de red
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Filtros para obtener contenido de la red
        add_filter('flavor_get_network_content', [$this, 'get_federated_content'], 10, 3);

        // Añadir columna de visibilidad en listados de admin
        add_action('admin_init', [$this, 'add_network_columns']);

        // Widget de contenido de red
        add_action('wp_dashboard_setup', [$this, 'add_network_content_widget']);

        // Shortcodes para mostrar contenido de red
        add_shortcode('flavor_red_contenido', [$this, 'shortcode_network_content']);
        add_shortcode('flavor_red_recetas', [$this, 'shortcode_network_recipes']);
        add_shortcode('flavor_red_eventos', [$this, 'shortcode_network_events']);
        add_shortcode('flavor_red_comunidades', [$this, 'shortcode_network_communities']);

        // Sistema de caché federado
        add_filter('flavor_get_network_content', [$this, 'get_cached_content'], 5, 3);
        add_action('flavor_network_content_shared', [$this, 'invalidate_content_cache'], 10, 3);
        add_action('flavor_network_sync_completed', [$this, 'refresh_node_cache']);

        // Sistema de reputación de nodos
        add_action('flavor_network_node_response', [$this, 'update_node_metrics'], 10, 3);
        add_action('flavor_network_content_reported', [$this, 'handle_content_report'], 10, 2);
        add_filter('flavor_network_filter_by_trust', [$this, 'filter_content_by_trust'], 10, 2);

        // Cron para actualizar métricas de nodos
        add_action('flavor_network_daily_metrics', [$this, 'calculate_daily_node_metrics']);
        if (!wp_next_scheduled('flavor_network_daily_metrics')) {
            wp_schedule_event(time(), 'daily', 'flavor_network_daily_metrics');
        }
    }

    /**
     * Renderiza campo de visibilidad en red
     */
    public function render_network_visibility_field($post_id, $content_type) {
        // Solo mostrar si el sistema de red está activo
        if (!class_exists('Flavor_Network_Manager')) {
            return;
        }

        $visibility = get_post_meta($post_id, '_flavor_network_visibility', true);
        if (empty($visibility)) {
            $visibility = 'privado';
        }

        $share_with_relations = get_post_meta($post_id, '_flavor_share_with_relations', true);
        ?>
        <div class="flavor-network-visibility" style="margin-top: 15px; padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1;">
            <h4 style="margin: 0 0 10px;">
                <span class="dashicons dashicons-networking" style="color: #2271b1;"></span>
                <?php _e('Compartir en la Red', 'flavor-chat-ia'); ?>
            </h4>

            <p>
                <label for="flavor_network_visibility">
                    <?php _e('Visibilidad:', 'flavor-chat-ia'); ?>
                </label>
                <select id="flavor_network_visibility" name="flavor_network_visibility" style="width: 100%;">
                    <?php foreach (self::VISIBILITY_LEVELS as $level => $label): ?>
                        <option value="<?php echo esc_attr($level); ?>" <?php selected($visibility, $level); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label>
                    <input type="checkbox" name="flavor_share_with_relations" value="1"
                           <?php checked($share_with_relations, '1'); ?> />
                    <?php _e('Incluir contenido relacionado (integraciones)', 'flavor-chat-ia'); ?>
                </label>
            </p>

            <p class="description" style="font-size: 11px; color: #666;">
                <?php _e('Al compartir en la red, este contenido será visible para otros nodos según el nivel seleccionado.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Guarda la visibilidad en red
     */
    public function save_network_visibility($post_id, $content_type) {
        if (isset($_POST['flavor_network_visibility'])) {
            $visibility = sanitize_text_field($_POST['flavor_network_visibility']);
            update_post_meta($post_id, '_flavor_network_visibility', $visibility);
        }

        $share_relations = isset($_POST['flavor_share_with_relations']) ? '1' : '0';
        update_post_meta($post_id, '_flavor_share_with_relations', $share_relations);
    }

    /**
     * Sincroniza contenido con la red cuando se guarda
     */
    public function sync_content_to_network($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Verificar si es un tipo de contenido compartible
        $shareable_post_types = $this->get_shareable_post_types();
        if (!in_array($post->post_type, $shareable_post_types)) {
            return;
        }

        $visibility = get_post_meta($post_id, '_flavor_network_visibility', true);

        if ($visibility && $visibility !== 'privado' && $post->post_status === 'publish') {
            $this->publish_to_network($post_id, $post, $visibility);
        } else {
            $this->unpublish_from_network($post_id);
        }
    }

    /**
     * Obtiene los post types que pueden compartirse
     */
    private function get_shareable_post_types() {
        $post_types = [
            'flavor_receta',
            'flavor_evento',
            'flavor_curso',
            'flavor_taller',
            'gc_producto',
            'gc_grupo',        // Grupos de consumo
            'bt_banco',        // Bancos de tiempo
            'flavor_comunidad', // Comunidades
            'product',         // WooCommerce
        ];

        return apply_filters('flavor_network_shareable_post_types', $post_types);
    }

    /**
     * Publica contenido en la red
     */
    private function publish_to_network($post_id, $post, $visibility) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_network_shared_content';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return;
        }

        // Obtener nodo local
        $nodo_local = $this->get_local_node_id();
        if (!$nodo_local) {
            return;
        }

        // Determinar tipo de contenido
        $tipo_contenido = $this->map_post_type_to_content_type($post->post_type);

        // Preparar datos
        $datos = [
            'nodo_id'        => $nodo_local,
            'tipo_contenido' => $tipo_contenido,
            'titulo'         => $post->post_title,
            'descripcion'    => wp_trim_words($post->post_content, 50),
            'url_externa'    => get_permalink($post_id),
            'imagen_url'     => get_the_post_thumbnail_url($post_id, 'medium'),
            'visible_red'    => ($visibility !== 'privado') ? 1 : 0,
            'nivel_visibilidad' => $visibility,
            'referencia_local'  => $post_id,
            'metadata'       => json_encode($this->get_content_metadata($post_id, $post)),
            'estado'         => 'activo',
            'fecha_actualizacion' => current_time('mysql'),
        ];

        // Verificar si ya existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE nodo_id = %d AND referencia_local = %d",
            $nodo_local,
            $post_id
        ));

        if ($existe) {
            $wpdb->update($tabla, $datos, ['id' => $existe]);
        } else {
            $datos['fecha_creacion'] = current_time('mysql');
            $wpdb->insert($tabla, $datos);
        }

        // Log de actividad
        do_action('flavor_network_content_shared', $post_id, $tipo_contenido, $visibility);
    }

    /**
     * Quita contenido de la red
     */
    private function unpublish_from_network($post_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_network_shared_content';
        $nodo_local = $this->get_local_node_id();

        if (!$nodo_local) {
            return;
        }

        $wpdb->update(
            $tabla,
            ['visible_red' => 0, 'estado' => 'oculto'],
            ['nodo_id' => $nodo_local, 'referencia_local' => $post_id]
        );
    }

    /**
     * Elimina contenido de la red cuando se borra el post
     */
    public function remove_content_from_network($post_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_network_shared_content';
        $nodo_local = $this->get_local_node_id();

        if (!$nodo_local) {
            return;
        }

        $wpdb->delete($tabla, [
            'nodo_id' => $nodo_local,
            'referencia_local' => $post_id
        ]);
    }

    /**
     * Mapea post_type a tipo de contenido de red
     */
    private function map_post_type_to_content_type($post_type) {
        $mapping = [
            'flavor_receta'    => 'recetas',
            'flavor_evento'    => 'eventos',
            'flavor_curso'     => 'cursos',
            'flavor_taller'    => 'talleres',
            'gc_producto'      => 'productos',
            'product'          => 'productos',
            'flavor_video'     => 'multimedia',
            'gc_grupo'         => 'grupos_consumo',
            'bt_banco'         => 'banco_tiempo',
            'flavor_comunidad' => 'comunidades',
        ];

        return isset($mapping[$post_type]) ? $mapping[$post_type] : 'contenido';
    }

    /**
     * Obtiene metadatos adicionales del contenido
     */
    private function get_content_metadata($post_id, $post) {
        $metadata = [
            'post_type' => $post->post_type,
            'author'    => get_the_author_meta('display_name', $post->post_author),
        ];

        // Añadir integraciones relacionadas si está habilitado
        $share_relations = get_post_meta($post_id, '_flavor_share_with_relations', true);
        if ($share_relations === '1') {
            $metadata['integraciones'] = $this->get_post_integrations($post_id);
        }

        // Datos específicos por tipo
        switch ($post->post_type) {
            case 'flavor_receta':
                $metadata['tiempo_preparacion'] = get_post_meta($post_id, '_receta_tiempo_preparacion', true);
                $metadata['dificultad'] = get_post_meta($post_id, '_receta_dificultad', true);
                $metadata['porciones'] = get_post_meta($post_id, '_receta_porciones', true);
                break;

            case 'flavor_evento':
                $metadata['fecha_inicio'] = get_post_meta($post_id, '_evento_fecha_inicio', true);
                $metadata['fecha_fin'] = get_post_meta($post_id, '_evento_fecha_fin', true);
                $metadata['ubicacion'] = get_post_meta($post_id, '_evento_ubicacion', true);
                break;

            case 'flavor_curso':
            case 'flavor_taller':
                $metadata['duracion'] = get_post_meta($post_id, '_duracion', true);
                $metadata['modalidad'] = get_post_meta($post_id, '_modalidad', true);
                $metadata['precio'] = get_post_meta($post_id, '_precio', true);
                break;
        }

        return apply_filters('flavor_network_content_metadata', $metadata, $post_id, $post);
    }

    /**
     * Obtiene las integraciones de un post
     */
    private function get_post_integrations($post_id) {
        $integraciones = [];

        // Obtener todos los providers registrados
        $providers = apply_filters('flavor_integration_providers', []);

        foreach ($providers as $provider_id => $provider) {
            $meta_key = '_flavor_integrated_' . $provider_id;
            $relacionados = get_post_meta($post_id, $meta_key, true);

            if (!empty($relacionados) && is_array($relacionados)) {
                $integraciones[$provider_id] = [];

                foreach ($relacionados as $relacionado_id) {
                    $relacionado = get_post($relacionado_id);
                    if ($relacionado) {
                        $integraciones[$provider_id][] = [
                            'id'     => $relacionado_id,
                            'titulo' => $relacionado->post_title,
                            'url'    => get_permalink($relacionado_id),
                            'imagen' => get_the_post_thumbnail_url($relacionado_id, 'thumbnail'),
                        ];
                    }
                }
            }
        }

        return $integraciones;
    }

    /**
     * Obtiene el ID del nodo local
     */
    private function get_local_node_id() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return null;
        }

        return $wpdb->get_var("SELECT id FROM $tabla WHERE es_nodo_local = 1 LIMIT 1");
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor-integration/v1', '/network-content', [
            'methods'  => 'GET',
            'callback' => [$this, 'api_get_network_content'],
            'permission_callback' => '__return_true',
            'args' => [
                'tipo' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'categoria' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'nodo' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
                'pagina' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
                'por_pagina' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        register_rest_route('flavor-integration/v1', '/network-content/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'api_get_single_content'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor-integration/v1', '/network-stats', [
            'methods'  => 'GET',
            'callback' => [$this, 'api_get_network_stats'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * API: Obtener contenido de la red
     */
    public function api_get_network_content($request) {
        global $wpdb;

        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $nodo = $request->get_param('nodo');
        $pagina = max(1, $request->get_param('pagina'));
        $por_pagina = min(100, max(1, $request->get_param('por_pagina')));
        $offset = ($pagina - 1) * $por_pagina;

        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';

        $where = ['c.visible_red = 1', "c.estado = 'activo'"];
        $params = [];

        if (!empty($tipo)) {
            $where[] = 'c.tipo_contenido = %s';
            $params[] = $tipo;
        }

        if (!empty($nodo)) {
            $where[] = 'c.nodo_id = %d';
            $params[] = $nodo;
        }

        $where_sql = implode(' AND ', $where);

        $query = "
            SELECT c.*, n.nombre as nodo_nombre, n.logo_url as nodo_logo, n.slug as nodo_slug
            FROM $tabla_contenido c
            LEFT JOIN $tabla_nodos n ON c.nodo_id = n.id
            WHERE $where_sql
            ORDER BY c.fecha_creacion DESC
            LIMIT %d OFFSET %d
        ";

        $params[] = $por_pagina;
        $params[] = $offset;

        $resultados = $wpdb->get_results($wpdb->prepare($query, $params));

        // Contar total
        $total_query = "SELECT COUNT(*) FROM $tabla_contenido c WHERE $where_sql";
        $total = $wpdb->get_var($wpdb->prepare($total_query, array_slice($params, 0, -2)));

        return rest_ensure_response([
            'contenido' => $resultados,
            'total'     => (int) $total,
            'paginas'   => ceil($total / $por_pagina),
            'pagina'    => $pagina,
        ]);
    }

    /**
     * API: Obtener contenido individual
     */
    public function api_get_single_content($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $tabla = $wpdb->prefix . 'flavor_network_shared_content';
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';

        $contenido = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, n.nombre as nodo_nombre, n.logo_url as nodo_logo,
                   n.slug as nodo_slug, n.site_url as nodo_url
            FROM $tabla c
            LEFT JOIN $tabla_nodos n ON c.nodo_id = n.id
            WHERE c.id = %d AND c.visible_red = 1
        ", $id));

        if (!$contenido) {
            return new WP_Error('not_found', 'Contenido no encontrado', ['status' => 404]);
        }

        // Decodificar metadata
        $contenido->metadata = json_decode($contenido->metadata, true);

        return rest_ensure_response($contenido);
    }

    /**
     * API: Estadísticas de la red
     */
    public function api_get_network_stats($request) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_network_shared_content';

        $stats = $wpdb->get_results("
            SELECT tipo_contenido, COUNT(*) as cantidad
            FROM $tabla
            WHERE visible_red = 1 AND estado = 'activo'
            GROUP BY tipo_contenido
        ");

        $por_tipo = [];
        $total = 0;

        foreach ($stats as $stat) {
            $por_tipo[$stat->tipo_contenido] = (int) $stat->cantidad;
            $total += $stat->cantidad;
        }

        return rest_ensure_response([
            'total'    => $total,
            'por_tipo' => $por_tipo,
            'tipos_disponibles' => array_keys(self::NETWORK_SHAREABLE_TYPES),
        ]);
    }

    /**
     * Obtiene contenido federado (filter)
     */
    public function get_federated_content($content, $tipo, $args) {
        global $wpdb;

        $defaults = [
            'limite'  => 20,
            'offset'  => 0,
            'nodo_id' => null,
            'excluir_local' => false,
        ];

        $args = wp_parse_args($args, $defaults);
        $tabla = $wpdb->prefix . 'flavor_network_shared_content';

        $where = ["visible_red = 1", "estado = 'activo'"];
        $params = [];

        if (!empty($tipo)) {
            $where[] = 'tipo_contenido = %s';
            $params[] = $tipo;
        }

        if ($args['excluir_local']) {
            $nodo_local = $this->get_local_node_id();
            if ($nodo_local) {
                $where[] = 'nodo_id != %d';
                $params[] = $nodo_local;
            }
        }

        if ($args['nodo_id']) {
            $where[] = 'nodo_id = %d';
            $params[] = $args['nodo_id'];
        }

        $where_sql = implode(' AND ', $where);
        $params[] = $args['limite'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $tabla
            WHERE $where_sql
            ORDER BY fecha_creacion DESC
            LIMIT %d OFFSET %d
        ", $params));
    }

    /**
     * Añade columnas de red en admin
     */
    public function add_network_columns() {
        $post_types = $this->get_shareable_post_types();

        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'add_network_column']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_network_column'], 10, 2);
        }
    }

    /**
     * Añade columna
     */
    public function add_network_column($columns) {
        $columns['network_visibility'] = '<span class="dashicons dashicons-networking" title="' .
            esc_attr__('Visibilidad en Red', 'flavor-chat-ia') . '"></span>';
        return $columns;
    }

    /**
     * Renderiza columna
     */
    public function render_network_column($column, $post_id) {
        if ($column !== 'network_visibility') {
            return;
        }

        $visibility = get_post_meta($post_id, '_flavor_network_visibility', true);

        $icons = [
            'privado'   => '🔒',
            'conectado' => '🔗',
            'federado'  => '🌐',
            'publico'   => '🌍',
        ];

        $labels = self::VISIBILITY_LEVELS;

        $icon = isset($icons[$visibility]) ? $icons[$visibility] : '🔒';
        $label = isset($labels[$visibility]) ? $labels[$visibility] : $labels['privado'];

        echo '<span title="' . esc_attr($label) . '">' . $icon . '</span>';
    }

    /**
     * Widget de dashboard
     */
    public function add_network_content_widget() {
        if (!class_exists('Flavor_Network_Manager')) {
            return;
        }

        wp_add_dashboard_widget(
            'flavor_network_content_widget',
            __('Contenido de la Red', 'flavor-chat-ia'),
            [$this, 'render_network_widget']
        );
    }

    /**
     * Renderiza widget
     */
    public function render_network_widget() {
        $contenido = apply_filters('flavor_get_network_content', [], '', [
            'limite' => 5,
            'excluir_local' => true,
        ]);

        if (empty($contenido)) {
            echo '<p>' . __('No hay contenido nuevo de la red.', 'flavor-chat-ia') . '</p>';
            return;
        }

        echo '<ul style="margin: 0;">';
        foreach ($contenido as $item) {
            $tipo_info = isset(self::NETWORK_SHAREABLE_TYPES[$item->tipo_contenido])
                ? self::NETWORK_SHAREABLE_TYPES[$item->tipo_contenido]
                : ['icon' => 'dashicons-admin-post', 'label' => $item->tipo_contenido];

            printf(
                '<li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span class="dashicons %s" style="color: #2271b1;"></span>
                    <a href="%s" target="_blank">%s</a>
                    <br><small style="color: #666;">%s · %s</small>
                </li>',
                esc_attr($tipo_info['icon']),
                esc_url($item->url_externa),
                esc_html($item->titulo),
                esc_html($tipo_info['label']),
                esc_html(human_time_diff(strtotime($item->fecha_creacion)))
            );
        }
        echo '</ul>';

        echo '<p><a href="' . admin_url('admin.php?page=flavor-network') . '">' .
             __('Ver todo el contenido de la red', 'flavor-chat-ia') . ' →</a></p>';
    }

    /**
     * Shortcode: Contenido de red genérico
     */
    public function shortcode_network_content($atts) {
        $atts = shortcode_atts([
            'tipo'    => '',
            'limite'  => 10,
            'columnas' => 3,
            'mostrar_nodo' => 'true',
        ], $atts);

        $contenido = apply_filters('flavor_get_network_content', [], $atts['tipo'], [
            'limite' => intval($atts['limite']),
        ]);

        if (empty($contenido)) {
            return '<p class="flavor-network-empty">' . __('No hay contenido disponible.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-network-grid" style="display: grid; grid-template-columns: repeat(<?php echo intval($atts['columnas']); ?>, 1fr); gap: 20px;">
            <?php foreach ($contenido as $item):
                $metadata = json_decode($item->metadata, true);
            ?>
            <div class="flavor-network-item" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                <?php if ($item->imagen_url): ?>
                    <img src="<?php echo esc_url($item->imagen_url); ?>" alt="" style="width: 100%; height: 150px; object-fit: cover;">
                <?php endif; ?>

                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 10px;">
                        <a href="<?php echo esc_url($item->url_externa); ?>" target="_blank">
                            <?php echo esc_html($item->titulo); ?>
                        </a>
                    </h4>

                    <?php if ($item->descripcion): ?>
                        <p style="font-size: 14px; color: #666; margin: 0 0 10px;">
                            <?php echo esc_html($item->descripcion); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($atts['mostrar_nodo'] === 'true' && isset($item->nodo_nombre)): ?>
                        <div style="font-size: 12px; color: #999;">
                            <?php if ($item->nodo_logo): ?>
                                <img src="<?php echo esc_url($item->nodo_logo); ?>" alt=""
                                     style="width: 16px; height: 16px; border-radius: 50%; vertical-align: middle;">
                            <?php endif; ?>
                            <?php echo esc_html($item->nodo_nombre); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Recetas de la red
     */
    public function shortcode_network_recipes($atts) {
        $atts['tipo'] = 'recetas';
        return $this->shortcode_network_content($atts);
    }

    /**
     * Shortcode: Eventos de la red
     */
    public function shortcode_network_events($atts) {
        $atts['tipo'] = 'eventos';
        return $this->shortcode_network_content($atts);
    }

    /**
     * Shortcode: Comunidades de la red (grupos de consumo, bancos de tiempo, comunidades)
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_network_communities($atts) {
        $atts = shortcode_atts([
            'tipos'        => 'grupos_consumo,banco_tiempo,comunidades', // Tipos a mostrar
            'limite'       => 12,
            'columnas'     => 3,
            'mostrar_nodo' => 'true',
            'busqueda'     => 'true', // Mostrar barra de búsqueda
        ], $atts);

        $tipos_array = array_map('trim', explode(',', $atts['tipos']));
        $contenido = [];

        // Obtener contenido de cada tipo
        foreach ($tipos_array as $tipo) {
            $items = apply_filters('flavor_get_network_content', [], $tipo, [
                'limite'        => $atts['limite'],
                'excluir_local' => true,
            ]);
            $contenido = array_merge($contenido, $items);
        }

        // Ordenar por fecha
        usort($contenido, function($a, $b) {
            return strtotime($b->fecha_creacion ?? 0) - strtotime($a->fecha_creacion ?? 0);
        });

        // Limitar resultados
        $contenido = array_slice($contenido, 0, $atts['limite']);

        ob_start();
        ?>
        <div class="flavor-red-comunidades">
            <?php if ($atts['busqueda'] === 'true'): ?>
            <div class="flavor-red-busqueda">
                <input type="text"
                       class="flavor-red-busqueda-input"
                       placeholder="<?php esc_attr_e('Buscar grupos, bancos de tiempo o comunidades...', 'flavor-chat-ia'); ?>"
                       data-search-target=".flavor-red-comunidades-grid">
                <div class="flavor-red-filtros">
                    <label class="flavor-red-filtro">
                        <input type="checkbox" value="grupos_consumo" checked>
                        <span><?php esc_html_e('Grupos de Consumo', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flavor-red-filtro">
                        <input type="checkbox" value="banco_tiempo" checked>
                        <span><?php esc_html_e('Bancos de Tiempo', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flavor-red-filtro">
                        <input type="checkbox" value="comunidades" checked>
                        <span><?php esc_html_e('Comunidades', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($contenido)): ?>
                <div class="flavor-red-vacio">
                    <span class="dashicons dashicons-networking"></span>
                    <p><?php esc_html_e('No hay comunidades disponibles en la red en este momento.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-red-vacio-sub"><?php esc_html_e('Las comunidades aparecerán aquí cuando otros nodos compartan sus grupos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-red-comunidades-grid" style="--columnas: <?php echo esc_attr($atts['columnas']); ?>">
                    <?php foreach ($contenido as $item):
                        $tipo_label = self::NETWORK_SHAREABLE_TYPES[$item->tipo_contenido]['label'] ?? $item->tipo_contenido;
                        $tipo_icon = self::NETWORK_SHAREABLE_TYPES[$item->tipo_contenido]['icon'] ?? 'dashicons-groups';
                        $metadata = is_string($item->metadata) ? json_decode($item->metadata, true) : $item->metadata;
                    ?>
                    <div class="flavor-red-comunidad-card" data-tipo="<?php echo esc_attr($item->tipo_contenido); ?>">
                        <?php if ($item->imagen_url): ?>
                            <div class="flavor-red-comunidad-imagen">
                                <img src="<?php echo esc_url($item->imagen_url); ?>" alt="<?php echo esc_attr($item->titulo); ?>">
                            </div>
                        <?php else: ?>
                            <div class="flavor-red-comunidad-imagen flavor-red-comunidad-imagen-default">
                                <span class="<?php echo esc_attr($tipo_icon); ?>"></span>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-red-comunidad-contenido">
                            <span class="flavor-red-comunidad-tipo">
                                <span class="<?php echo esc_attr($tipo_icon); ?>"></span>
                                <?php echo esc_html($tipo_label); ?>
                            </span>

                            <h4 class="flavor-red-comunidad-titulo">
                                <?php echo esc_html($item->titulo); ?>
                            </h4>

                            <?php if ($item->descripcion): ?>
                                <p class="flavor-red-comunidad-descripcion">
                                    <?php echo esc_html(wp_trim_words($item->descripcion, 15)); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($atts['mostrar_nodo'] === 'true' && isset($item->nodo_nombre)): ?>
                                <div class="flavor-red-comunidad-nodo">
                                    <?php if ($item->nodo_logo): ?>
                                        <img src="<?php echo esc_url($item->nodo_logo); ?>" alt="">
                                    <?php endif; ?>
                                    <span><?php echo esc_html($item->nodo_nombre); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flavor-red-comunidad-acciones">
                            <a href="<?php echo esc_url($item->url_externa); ?>"
                               target="_blank"
                               rel="noopener"
                               class="flavor-red-btn">
                                <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-red-comunidades {
            max-width: 1200px;
            margin: 0 auto;
        }
        .flavor-red-busqueda {
            margin-bottom: 24px;
        }
        .flavor-red-busqueda-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gc-gray-300);
            border-radius: var(--gc-button-radius);
            font-size: 1em;
            font-family: var(--gc-font-family);
            margin-bottom: 12px;
        }
        .flavor-red-busqueda-input:focus {
            outline: none;
            border-color: var(--gc-primary);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        .flavor-red-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .flavor-red-filtro {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .flavor-red-filtro input {
            accent-color: var(--gc-primary);
        }
        .flavor-red-vacio {
            text-align: center;
            padding: 60px 20px;
            background: var(--gc-gray-100);
            border-radius: var(--gc-border-radius);
        }
        .flavor-red-vacio .dashicons {
            font-size: 64px;
            width: 64px;
            height: 64px;
            color: var(--gc-gray-500);
            margin-bottom: 16px;
        }
        .flavor-red-vacio p {
            margin: 0 0 8px;
            color: var(--gc-gray-900);
        }
        .flavor-red-vacio-sub {
            font-size: 0.9em;
            color: var(--gc-gray-500) !important;
        }
        .flavor-red-comunidades-grid {
            display: grid;
            grid-template-columns: repeat(var(--columnas, 3), 1fr);
            gap: 24px;
        }
        .flavor-red-comunidad-card {
            background: #fff;
            border: 1px solid var(--gc-gray-300);
            border-radius: var(--gc-border-radius);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .flavor-red-comunidad-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--gc-shadow-lg);
        }
        .flavor-red-comunidad-card.oculto {
            display: none;
        }
        .flavor-red-comunidad-imagen {
            height: 140px;
            overflow: hidden;
        }
        .flavor-red-comunidad-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .flavor-red-comunidad-imagen-default {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gc-primary), var(--gc-primary-dark));
        }
        .flavor-red-comunidad-imagen-default .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: rgba(255,255,255,0.9);
        }
        .flavor-red-comunidad-contenido {
            padding: 16px;
        }
        .flavor-red-comunidad-tipo {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gc-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .flavor-red-comunidad-tipo .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        .flavor-red-comunidad-titulo {
            margin: 0 0 8px;
            font-size: 1.1em;
            color: var(--gc-gray-900);
            font-family: var(--gc-font-headings);
        }
        .flavor-red-comunidad-descripcion {
            font-size: 0.9em;
            color: var(--gc-gray-500);
            margin: 0 0 12px;
            line-height: 1.5;
        }
        .flavor-red-comunidad-nodo {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8em;
            color: var(--gc-gray-500);
        }
        .flavor-red-comunidad-nodo img {
            width: 18px;
            height: 18px;
            border-radius: 50%;
        }
        .flavor-red-comunidad-acciones {
            padding: 12px 16px;
            border-top: 1px solid var(--gc-gray-200);
        }
        .flavor-red-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--gc-primary);
            color: #fff;
            border-radius: var(--gc-button-radius);
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .flavor-red-btn:hover {
            background: var(--gc-primary-dark);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: var(--gc-shadow);
        }
        .flavor-red-btn .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }

        @media (max-width: 900px) {
            .flavor-red-comunidades-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .flavor-red-comunidades-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Búsqueda
                var searchInput = document.querySelector('.flavor-red-busqueda-input');
                var cards = document.querySelectorAll('.flavor-red-comunidad-card');
                var checkboxes = document.querySelectorAll('.flavor-red-filtro input');

                function filtrarCards() {
                    var query = searchInput ? searchInput.value.toLowerCase() : '';
                    var tiposActivos = [];
                    checkboxes.forEach(function(cb) {
                        if (cb.checked) tiposActivos.push(cb.value);
                    });

                    cards.forEach(function(card) {
                        var titulo = card.querySelector('.flavor-red-comunidad-titulo').textContent.toLowerCase();
                        var tipo = card.getAttribute('data-tipo');
                        var coincideTexto = titulo.indexOf(query) !== -1;
                        var coincideTipo = tiposActivos.indexOf(tipo) !== -1;

                        if (coincideTexto && coincideTipo) {
                            card.classList.remove('oculto');
                        } else {
                            card.classList.add('oculto');
                        }
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', filtrarCards);
                }
                checkboxes.forEach(function(cb) {
                    cb.addEventListener('change', filtrarCards);
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // SISTEMA DE CACHÉ FEDERADO
    // =========================================================================

    /**
     * Obtiene contenido cacheado de la red
     *
     * @param array  $content Contenido actual
     * @param string $tipo    Tipo de contenido
     * @param array  $args    Argumentos
     * @return array
     */
    public function get_cached_content($content, $tipo, $args) {
        $cache_key = $this->generate_cache_key('network_content', $tipo, $args);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        return $content;
    }

    /**
     * Guarda contenido en caché
     *
     * @param string $tipo      Tipo de contenido
     * @param array  $content   Contenido a cachear
     * @param array  $args      Argumentos de la consulta
     */
    public function set_content_cache($tipo, $content, $args = []) {
        $cache_key = $this->generate_cache_key('network_content', $tipo, $args);
        $ttl = $this->get_cache_ttl($tipo);

        set_transient($cache_key, $content, $ttl);
    }

    /**
     * Genera clave de caché única
     *
     * @param string $prefix Prefijo
     * @param string $tipo   Tipo
     * @param array  $args   Argumentos
     * @return string
     */
    private function generate_cache_key($prefix, $tipo, $args = []) {
        $key_parts = [$prefix, $tipo];

        if (!empty($args['nodo_id'])) {
            $key_parts[] = 'n' . $args['nodo_id'];
        }
        if (!empty($args['limite'])) {
            $key_parts[] = 'l' . $args['limite'];
        }

        return 'flavor_' . md5(implode('_', $key_parts));
    }

    /**
     * Obtiene TTL para tipo de contenido
     *
     * @param string $tipo Tipo de contenido
     * @return int TTL en segundos
     */
    private function get_cache_ttl($tipo) {
        $mapping = [
            'eventos'       => self::CACHE_CONFIG['events'],
            'comunidades'   => self::CACHE_CONFIG['communities'],
            'grupos_consumo' => self::CACHE_CONFIG['communities'],
            'banco_tiempo'  => self::CACHE_CONFIG['communities'],
        ];

        return $mapping[$tipo] ?? self::CACHE_CONFIG['shared_content'];
    }

    /**
     * Invalida caché cuando se comparte contenido
     *
     * @param int    $post_id    ID del post
     * @param string $tipo       Tipo de contenido
     * @param string $visibility Visibilidad
     */
    public function invalidate_content_cache($post_id, $tipo, $visibility) {
        global $wpdb;

        // Eliminar todos los transients relacionados con este tipo
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             OR option_name LIKE %s",
            '_transient_flavor_%' . $tipo . '%',
            '_transient_timeout_flavor_%' . $tipo . '%'
        ));

        // Limpiar caché de nodos
        delete_transient('flavor_network_nodes_cache');
    }

    /**
     * Refresca caché de nodos
     */
    public function refresh_node_cache() {
        delete_transient('flavor_network_nodes_cache');
        delete_transient('flavor_network_directory');
    }

    /**
     * Obtiene directorio de nodos cacheado
     *
     * @return array
     */
    public function get_cached_node_directory() {
        $cached = get_transient('flavor_network_directory');

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $nodos = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE estado = 'activo' ORDER BY reputacion_score DESC"
        );

        set_transient('flavor_network_directory', $nodos, self::CACHE_CONFIG['directory']);

        return $nodos;
    }

    // =========================================================================
    // SISTEMA DE REPUTACIÓN DE NODOS
    // =========================================================================

    /**
     * Actualiza métricas de un nodo basado en respuesta
     *
     * @param int   $nodo_id        ID del nodo
     * @param float $response_time  Tiempo de respuesta en ms
     * @param bool  $success        Si la respuesta fue exitosa
     */
    public function update_node_metrics($nodo_id, $response_time, $success) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        // Obtener métricas actuales
        $nodo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $nodo_id
        ));

        if (!$nodo) {
            return;
        }

        // Calcular nuevo tiempo de respuesta promedio (media móvil)
        $avg_response = isset($nodo->avg_response_time) ? $nodo->avg_response_time : 0;
        $new_avg = ($avg_response * 0.9) + ($response_time * 0.1);

        // Actualizar uptime
        $total_checks = isset($nodo->total_checks) ? $nodo->total_checks + 1 : 1;
        $successful_checks = isset($nodo->successful_checks) ? $nodo->successful_checks : 0;
        if ($success) {
            $successful_checks++;
        }
        $uptime = ($total_checks > 0) ? ($successful_checks / $total_checks) * 100 : 100;

        // Actualizar en base de datos
        $wpdb->update(
            $tabla,
            [
                'avg_response_time' => round($new_avg),
                'uptime_percent'    => round($uptime, 2),
                'total_checks'      => $total_checks,
                'successful_checks' => $successful_checks,
                'ultima_verificacion' => current_time('mysql'),
            ],
            ['id' => $nodo_id],
            ['%d', '%f', '%d', '%d', '%s'],
            ['%d']
        );

        // Recalcular score de reputación
        $this->recalculate_node_reputation($nodo_id);
    }

    /**
     * Maneja reporte de contenido problemático de un nodo
     *
     * @param int    $contenido_id ID del contenido
     * @param string $motivo       Motivo del reporte
     */
    public function handle_content_report($contenido_id, $motivo) {
        global $wpdb;
        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';

        // Obtener nodo del contenido
        $nodo_id = $wpdb->get_var($wpdb->prepare(
            "SELECT nodo_id FROM $tabla_contenido WHERE id = %d",
            $contenido_id
        ));

        if (!$nodo_id) {
            return;
        }

        // Incrementar contador de reportes
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_nodos SET reportes_count = reportes_count + 1 WHERE id = %d",
            $nodo_id
        ));

        // Recalcular reputación
        $this->recalculate_node_reputation($nodo_id);

        // Registrar reporte
        $this->log_node_report($nodo_id, $contenido_id, $motivo);
    }

    /**
     * Recalcula la reputación de un nodo
     *
     * @param int $nodo_id ID del nodo
     */
    public function recalculate_node_reputation($nodo_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        $nodo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $nodo_id
        ));

        if (!$nodo) {
            return;
        }

        // Factores de reputación (ponderados de 0 a 100)
        $score = 0;

        // 1. Uptime (40% del score)
        $uptime = isset($nodo->uptime_percent) ? $nodo->uptime_percent : 100;
        $score += ($uptime / 100) * 40;

        // 2. Tiempo de respuesta (20% del score) - menos es mejor
        $avg_response = isset($nodo->avg_response_time) ? $nodo->avg_response_time : 500;
        if ($avg_response <= 200) {
            $response_score = 20;
        } elseif ($avg_response <= 500) {
            $response_score = 15;
        } elseif ($avg_response <= 1000) {
            $response_score = 10;
        } elseif ($avg_response <= 2000) {
            $response_score = 5;
        } else {
            $response_score = 0;
        }
        $score += $response_score;

        // 3. Reportes (20% del score) - penalización
        $reportes = isset($nodo->reportes_count) ? $nodo->reportes_count : 0;
        $reportes_penalty = min($reportes * 2, 20);
        $score += (20 - $reportes_penalty);

        // 4. Verificación manual (10% del score)
        $verificado = isset($nodo->verificado_manualmente) && $nodo->verificado_manualmente;
        $score += $verificado ? 10 : 0;

        // 5. Antigüedad (10% del score)
        $fecha_registro = isset($nodo->created_at) ? strtotime($nodo->created_at) : time();
        $dias_activo = (time() - $fecha_registro) / DAY_IN_SECONDS;
        if ($dias_activo >= 365) {
            $antiguedad_score = 10;
        } elseif ($dias_activo >= 180) {
            $antiguedad_score = 7;
        } elseif ($dias_activo >= 90) {
            $antiguedad_score = 5;
        } elseif ($dias_activo >= 30) {
            $antiguedad_score = 3;
        } else {
            $antiguedad_score = 1;
        }
        $score += $antiguedad_score;

        // Determinar nivel de confianza
        $nivel = 'no_verificado';
        foreach (array_reverse(self::NODE_TRUST_LEVELS) as $key => $config) {
            if ($score >= $config['min_score']) {
                $nivel = $key;
                break;
            }
        }

        // Actualizar en base de datos
        $wpdb->update(
            $tabla,
            [
                'reputacion_score' => round($score, 2),
                'nivel_confianza'  => $nivel,
            ],
            ['id' => $nodo_id],
            ['%f', '%s'],
            ['%d']
        );

        // Limpiar caché
        delete_transient('flavor_network_directory');
    }

    /**
     * Filtra contenido por nivel de confianza del nodo
     *
     * @param array  $contenido       Contenido a filtrar
     * @param string $nivel_minimo    Nivel mínimo requerido
     * @return array
     */
    public function filter_content_by_trust($contenido, $nivel_minimo = 'basico') {
        $niveles = array_keys(self::NODE_TRUST_LEVELS);
        $indice_minimo = array_search($nivel_minimo, $niveles);

        if ($indice_minimo === false) {
            return $contenido;
        }

        return array_filter($contenido, function($item) use ($niveles, $indice_minimo) {
            $nivel_nodo = $item->nivel_confianza ?? 'no_verificado';
            $indice_nodo = array_search($nivel_nodo, $niveles);
            return $indice_nodo !== false && $indice_nodo >= $indice_minimo;
        });
    }

    /**
     * Calcula métricas diarias de nodos (cron)
     */
    public function calculate_daily_node_metrics() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return;
        }

        $nodos = $wpdb->get_results("SELECT id FROM $tabla WHERE estado = 'activo'");

        foreach ($nodos as $nodo) {
            $this->recalculate_node_reputation($nodo->id);
        }

        // Limpiar caché
        $this->refresh_node_cache();
    }

    /**
     * Registra reporte de nodo
     *
     * @param int    $nodo_id      ID del nodo
     * @param int    $contenido_id ID del contenido
     * @param string $motivo       Motivo del reporte
     */
    private function log_node_report($nodo_id, $contenido_id, $motivo) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_reports';

        // Crear tabla si no existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            $charset_collate = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE $tabla (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                nodo_id bigint(20) unsigned NOT NULL,
                contenido_id bigint(20) unsigned DEFAULT NULL,
                reportador_id bigint(20) unsigned DEFAULT NULL,
                motivo varchar(100) NOT NULL,
                descripcion text,
                estado enum('pendiente','revisado','confirmado','descartado') DEFAULT 'pendiente',
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                fecha_resolucion datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY nodo_id (nodo_id),
                KEY estado (estado)
            ) $charset_collate");
        }

        $wpdb->insert($tabla, [
            'nodo_id'      => $nodo_id,
            'contenido_id' => $contenido_id,
            'reportador_id' => get_current_user_id(),
            'motivo'       => sanitize_text_field($motivo),
            'fecha_creacion' => current_time('mysql'),
        ]);
    }

    /**
     * Obtiene estadísticas de reputación de nodos
     *
     * @return array
     */
    public function get_node_reputation_stats() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $stats = [
            'total_nodos'     => 0,
            'por_nivel'       => [],
            'score_promedio'  => 0,
            'uptime_promedio' => 0,
        ];

        // Contar por nivel
        $por_nivel = $wpdb->get_results(
            "SELECT nivel_confianza, COUNT(*) as cantidad
             FROM $tabla
             WHERE estado = 'activo'
             GROUP BY nivel_confianza"
        );

        foreach ($por_nivel as $row) {
            $stats['por_nivel'][$row->nivel_confianza ?? 'no_verificado'] = (int) $row->cantidad;
            $stats['total_nodos'] += $row->cantidad;
        }

        // Promedios
        $promedios = $wpdb->get_row(
            "SELECT AVG(reputacion_score) as score, AVG(uptime_percent) as uptime
             FROM $tabla WHERE estado = 'activo'"
        );

        if ($promedios) {
            $stats['score_promedio'] = round($promedios->score ?? 0, 2);
            $stats['uptime_promedio'] = round($promedios->uptime ?? 0, 2);
        }

        return $stats;
    }

    /**
     * Obtiene nodos ordenados por reputación
     *
     * @param int    $limite    Límite de resultados
     * @param string $nivel_min Nivel mínimo
     * @return array
     */
    public function get_nodes_by_reputation($limite = 20, $nivel_min = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $where = ["estado = 'activo'"];

        if ($nivel_min) {
            $niveles = array_keys(self::NODE_TRUST_LEVELS);
            $indice = array_search($nivel_min, $niveles);
            if ($indice !== false) {
                $niveles_validos = array_slice($niveles, $indice);
                $placeholders = implode(',', array_fill(0, count($niveles_validos), '%s'));
                $where[] = $wpdb->prepare("nivel_confianza IN ($placeholders)", $niveles_validos);
            }
        }

        $where_sql = implode(' AND ', $where);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE $where_sql ORDER BY reputacion_score DESC LIMIT %d",
            $limite
        ));
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Network_Content_Bridge::get_instance();
}, 20);
