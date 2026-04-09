<?php
/**
 * Módulo: Saberes Ancestrales
 *
 * Preservación y transmisión del conocimiento tradicional comunitario.
 * Conecta generaciones y honra la sabiduría de los mayores.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Saberes Ancestrales
 */
class Flavor_Chat_Saberes_Ancestrales_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Categorías de saberes
     */
    const CATEGORIAS_SABER = [
        'agricultura' => [
            'nombre' => 'Agricultura tradicional',
            'descripcion' => 'Cultivos, ciclos lunares, semillas antiguas',
            'icono' => 'dashicons-carrot',
            'color' => '#8B4513',
        ],
        'artesania' => [
            'nombre' => 'Artesanía',
            'descripcion' => 'Oficios manuales, tejidos, cerámica',
            'icono' => 'dashicons-art',
            'color' => '#D2691E',
        ],
        'medicina' => [
            'nombre' => 'Medicina natural',
            'descripcion' => 'Plantas medicinales, remedios caseros',
            'icono' => 'dashicons-heart',
            'color' => '#228B22',
        ],
        'gastronomia' => [
            'nombre' => 'Gastronomía tradicional',
            'descripcion' => 'Recetas, conservas, fermentos',
            'icono' => 'dashicons-food',
            'color' => '#FF6347',
        ],
        'tradiciones' => [
            'nombre' => 'Tradiciones y rituales',
            'descripcion' => 'Fiestas, ceremonias, costumbres',
            'icono' => 'dashicons-groups',
            'color' => '#9932CC',
        ],
        'construccion' => [
            'nombre' => 'Construcción tradicional',
            'descripcion' => 'Técnicas constructivas ancestrales',
            'icono' => 'dashicons-admin-home',
            'color' => '#CD853F',
        ],
        'musica' => [
            'nombre' => 'Música y danza',
            'descripcion' => 'Canciones, instrumentos, bailes',
            'icono' => 'dashicons-format-audio',
            'color' => '#4169E1',
        ],
        'narracion' => [
            'nombre' => 'Narración oral',
            'descripcion' => 'Cuentos, leyendas, refranes',
            'icono' => 'dashicons-format-quote',
            'color' => '#708090',
        ],
        'oficios' => [
            'nombre' => 'Oficios perdidos',
            'descripcion' => 'Herrería, carpintería, cestería...',
            'icono' => 'dashicons-hammer',
            'color' => '#696969',
        ],
    ];

    /**
     * Tipos de transmisión
     */
    const TIPOS_TRANSMISION = [
        'documentacion' => [
            'nombre' => 'Documentación',
            'descripcion' => 'Registro escrito, fotos, vídeos',
        ],
        'taller' => [
            'nombre' => 'Taller práctico',
            'descripcion' => 'Aprendizaje presencial guiado',
        ],
        'mentoria' => [
            'nombre' => 'Mentoría',
            'descripcion' => 'Acompañamiento uno a uno',
        ],
        'circulo' => [
            'nombre' => 'Círculo de saberes',
            'descripcion' => 'Encuentro grupal de intercambio',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->module_id = 'saberes_ancestrales';
        $this->module_name = __('Saberes Ancestrales', 'flavor-platform');
        $this->module_description = __('Preserva y transmite el conocimiento tradicional de la comunidad', 'flavor-platform');
        $this->module_icon = 'dashicons-book';
        $this->module_color = '#8B4513';
        $this->module_role = 'transversal';
        $this->ecosystem_teaches_modules = ['comunidades', 'talleres', 'cursos'];
        $this->ecosystem_supports_modules = ['talleres', 'cursos', 'comunidades'];
        $this->dashboard_transversal_priority = 40;
        $this->dashboard_client_contexts = ['aprendizaje', 'comunidad', 'cultura', 'saberes'];
        $this->dashboard_admin_contexts = ['aprendizaje', 'cultura', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['aprendizaje', 'cuidados'];
        $this->gailu_contribuye_a = ['cohesion', 'resiliencia'];

        parent::__construct();
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['recetas', 'biblioteca', 'multimedia', 'podcast', 'videos'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_saberes',
                'context' => 'normal',
            ],
        ];
    }

    /**
     * Inicializa el módulo
     */
    public function init(): void {
        $this->register_as_integration_consumer();

        // Registrar CPT y taxonomías en el hook 'init' de WordPress
        add_action('init', [$this, 'register_all_cpts'], 5);

        $this->register_ajax_handlers();
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs(): void {
        $tab_file = dirname(__FILE__) . '/class-saberes-ancestrales-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Saberes_Ancestrales_Dashboard_Tab')) {
                Flavor_Saberes_Ancestrales_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra todos los CPTs y taxonomías
     */
    public function register_all_cpts(): void {
        $this->register_post_types();
        $this->register_taxonomies();
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Listar saberes
        register_rest_route($namespace, '/saberes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_saberes'],
            'permission_callback' => [$this, 'public_read_permission'],
            'args' => [
                'categoria' => ['type' => 'string'],
                'limite' => ['type' => 'integer', 'default' => 20],
            ],
        ]);

        // Obtener saber por ID
        register_rest_route($namespace, '/saberes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_saber'],
            'permission_callback' => [$this, 'can_read_saber'],
        ]);

        // Registrar nuevo saber
        register_rest_route($namespace, '/saberes', [
            'methods' => 'POST',
            'callback' => [$this, 'api_registrar_saber'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Listar talleres
        register_rest_route($namespace, '/saberes/talleres', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_talleres'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);

        // Inscribirse en taller
        register_rest_route($namespace, '/saberes/talleres/(?P<id>\d+)/inscribirse', [
            'methods' => 'POST',
            'callback' => [$this, 'api_inscribirse_taller'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Mis aprendizajes
        register_rest_route($namespace, '/saberes/mis-aprendizajes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_aprendizajes'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    /**
     * Permisos de lectura pública para recursos publicados del módulo.
     *
     * @return bool
     */
    public function public_read_permission(): bool {
        return true;
    }

    /**
     * Verifica que un saber concreto sea públicamente visible.
     *
     * @param WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function can_read_saber($request) {
        $id = absint($request->get_param('id'));
        $saber = get_post($id);

        if (!$saber || $saber->post_type !== 'sa_saber') {
            return new \WP_Error('not_found', __('Saber no encontrado', 'flavor-platform'), ['status' => 404]);
        }

        if ($saber->post_status === 'publish') {
            return true;
        }

        if (is_user_logged_in() && current_user_can('edit_post', $id)) {
            return true;
        }

        return new \WP_Error('rest_forbidden', __('No tienes permiso para ver este saber.', 'flavor-platform'), ['status' => 403]);
    }

    /**
     * API: Listar saberes
     */
    public function api_listar_saberes($request): \WP_REST_Response {
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite') ?: 20;

        $args = [
            'post_type' => 'sa_saber',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
        ];

        if ($categoria) {
            $args['tax_query'] = [[
                'taxonomy' => 'sa_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        $saberes = get_posts($args);
        $resultado = [];

        foreach ($saberes as $saber) {
            $categoria_term = wp_get_post_terms($saber->ID, 'sa_categoria', ['fields' => 'slugs']);
            $resultado[] = [
                'id' => $saber->ID,
                'titulo' => $saber->post_title,
                'descripcion' => wp_trim_words($saber->post_content, 30),
                'categoria' => !empty($categoria_term) ? $categoria_term[0] : '',
                'portador' => get_post_meta($saber->ID, '_sa_portador', true),
                'origen' => get_post_meta($saber->ID, '_sa_origen', true),
                'agradecimientos' => (int) get_post_meta($saber->ID, '_sa_agradecimientos', true),
                'fecha' => get_the_date('Y-m-d', $saber),
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'total' => count($resultado),
            'saberes' => $resultado,
            'categorias' => array_keys(self::CATEGORIAS_SABER),
        ], 200);
    }

    /**
     * API: Obtener saber por ID
     */
    public function api_obtener_saber($request): \WP_REST_Response {
        $id = $request->get_param('id');
        $saber = get_post($id);

        if (!$saber || $saber->post_type !== 'sa_saber') {
            return new \WP_REST_Response(['success' => false, 'error' => 'Saber no encontrado'], 404);
        }

        $categoria_term = wp_get_post_terms($saber->ID, 'sa_categoria', ['fields' => 'slugs']);

        return new \WP_REST_Response([
            'success' => true,
            'saber' => [
                'id' => $saber->ID,
                'titulo' => $saber->post_title,
                'contenido' => $saber->post_content,
                'categoria' => !empty($categoria_term) ? $categoria_term[0] : '',
                'portador' => get_post_meta($saber->ID, '_sa_portador', true),
                'origen' => get_post_meta($saber->ID, '_sa_origen', true),
                'agradecimientos' => (int) get_post_meta($saber->ID, '_sa_agradecimientos', true),
                'imagen' => get_the_post_thumbnail_url($saber->ID, 'large'),
                'autor' => get_the_author_meta('display_name', $saber->post_author),
                'fecha' => get_the_date('Y-m-d', $saber),
            ],
        ], 200);
    }

    /**
     * API: Registrar saber
     */
    public function api_registrar_saber($request): \WP_REST_Response {
        $titulo = sanitize_text_field($request->get_param('titulo'));
        $descripcion = sanitize_textarea_field($request->get_param('descripcion'));
        $categoria = sanitize_key($request->get_param('categoria'));
        $portador = sanitize_text_field($request->get_param('portador'));
        $origen = sanitize_text_field($request->get_param('origen'));

        if (empty($titulo) || empty($descripcion)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Título y descripción requeridos'], 400);
        }

        $saber_id = wp_insert_post([
            'post_type' => 'sa_saber',
            'post_status' => 'pending',
            'post_author' => get_current_user_id(),
            'post_title' => $titulo,
            'post_content' => $descripcion,
        ], true);

        if (is_wp_error($saber_id) || empty($saber_id)) {
            $error = is_wp_error($saber_id) ? $saber_id->get_error_message() : __('No se pudo crear el saber.', 'flavor-platform');
            return new \WP_REST_Response(['success' => false, 'error' => $error], 500);
        }

        if ($categoria && isset(self::CATEGORIAS_SABER[$categoria])) {
            wp_set_object_terms($saber_id, $categoria, 'sa_categoria');
        }

        update_post_meta($saber_id, '_sa_origen', $origen);
        update_post_meta($saber_id, '_sa_portador', $portador);
        update_post_meta($saber_id, '_sa_documentado_por', get_current_user_id());
        update_post_meta($saber_id, '_sa_fecha_documentacion', current_time('mysql'));

        return new \WP_REST_Response([
            'success' => true,
            'mensaje' => __('Saber documentado. Será revisado antes de publicarse.', 'flavor-platform'),
            'id' => $saber_id,
        ], 201);
    }

    /**
     * API: Listar talleres
     */
    public function api_listar_talleres($request): \WP_REST_Response {
        $talleres = get_posts([
            'post_type' => 'sa_taller',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_key' => '_sa_fecha',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [[
                'key' => '_sa_fecha',
                'value' => current_time('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            ]],
        ]);

        $resultado = [];
        foreach ($talleres as $taller) {
            $inscritos = get_post_meta($taller->ID, '_sa_inscritos', true) ?: [];
            $plazas = (int) get_post_meta($taller->ID, '_sa_plazas', true) ?: 20;
            $resultado[] = [
                'id' => $taller->ID,
                'titulo' => $taller->post_title,
                'descripcion' => wp_trim_words($taller->post_content, 30),
                'fecha' => get_post_meta($taller->ID, '_sa_fecha', true),
                'plazas' => $plazas,
                'inscritos' => count($inscritos),
                'plazas_libres' => $plazas - count($inscritos),
            ];
        }

        return new \WP_REST_Response(['success' => true, 'talleres' => $resultado], 200);
    }

    /**
     * API: Inscribirse en taller
     */
    public function api_inscribirse_taller($request): \WP_REST_Response {
        $taller_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $taller = get_post($taller_id);
        if (!$taller || $taller->post_type !== 'sa_taller') {
            return new \WP_REST_Response(['success' => false, 'error' => 'Taller no encontrado'], 404);
        }

        $inscritos = get_post_meta($taller_id, '_sa_inscritos', true) ?: [];
        $plazas = (int) get_post_meta($taller_id, '_sa_plazas', true) ?: 20;

        if (in_array($user_id, $inscritos)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Ya estás inscrito'], 400);
        }

        if (count($inscritos) >= $plazas) {
            return new \WP_REST_Response(['success' => false, 'error' => 'No quedan plazas'], 400);
        }

        $inscritos[] = $user_id;
        update_post_meta($taller_id, '_sa_inscritos', $inscritos);

        return new \WP_REST_Response([
            'success' => true,
            'mensaje' => __('Inscripción completada', 'flavor-platform'),
            'plazas_restantes' => $plazas - count($inscritos),
        ], 200);
    }

    /**
     * API: Mis aprendizajes
     */
    public function api_mis_aprendizajes($request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $estadisticas = $this->get_estadisticas_usuario($user_id);

        // Saberes documentados por el usuario
        $saberes = get_posts([
            'post_type' => 'sa_saber',
            'author' => $user_id,
            'posts_per_page' => 10,
        ]);

        $mis_saberes = array_map(function($saber) {
            return [
                'id' => $saber->ID,
                'titulo' => $saber->post_title,
                'estado' => $saber->post_status,
            ];
        }, $saberes);

        return new \WP_REST_Response([
            'success' => true,
            'estadisticas' => $estadisticas,
            'saberes_documentados' => $mis_saberes,
        ], 200);
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     */
    protected function get_admin_config(): array {
        return [
            'id' => 'saberes-ancestrales',
            'label' => __('Saberes Ancestrales', 'flavor-platform'),
            'icon' => 'dashicons-book',
            'capability' => 'manage_options',
            'categoria' => 'cultura',
            'paginas' => [
                [
                    'slug' => 'saberes-dashboard',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'saberes-listado',
                    'titulo' => __('Saberes', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge' => [$this, 'contar_saberes_pendientes'],
                ],
                [
                    'slug' => 'saberes-talleres',
                    'titulo' => __('Talleres', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_talleres'],
                ],
                [
                    'slug' => 'saberes-portadores',
                    'titulo' => __('Portadores', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_portadores'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta saberes pendientes de revisión
     */
    public function contar_saberes_pendientes(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_saber' AND post_status = 'pending'"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     */
    public function get_estadisticas_dashboard(): array {
        $stats = $this->get_estadisticas();
        $resultado = [];

        $resultado[] = [
            'icon' => 'dashicons-book',
            'valor' => $stats['saberes_total'],
            'label' => __('Saberes documentados', 'flavor-platform'),
            'color' => 'green',
            'enlace' => admin_url('admin.php?page=saberes-listado'),
        ];

        $resultado[] = [
            'icon' => 'dashicons-groups',
            'valor' => $stats['portadores'],
            'label' => __('Portadores', 'flavor-platform'),
            'color' => 'blue',
            'enlace' => admin_url('admin.php?page=saberes-portadores'),
        ];

        if ($stats['talleres_proximos'] > 0) {
            $resultado[] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $stats['talleres_proximos'],
                'label' => __('Talleres próximos', 'flavor-platform'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=saberes-talleres'),
            ];
        }

        return $resultado;
    }

    /**
     * Renderiza el dashboard admin
     */
    public function render_admin_dashboard(): void {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Saberes Ancestrales', 'flavor-platform'));

        $stats = $this->get_estadisticas();

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-book"></span>';
        echo '<div class="stat-content"><span class="stat-number">' . esc_html($stats['saberes_total']) . '</span>';
        echo '<span class="stat-label">' . esc_html__('Saberes', 'flavor-platform') . '</span></div></div>';

        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-groups"></span>';
        echo '<div class="stat-content"><span class="stat-number">' . esc_html($stats['portadores']) . '</span>';
        echo '<span class="stat-label">' . esc_html__('Portadores', 'flavor-platform') . '</span></div></div>';

        echo '<div class="flavor-stat-card"><span class="dashicons dashicons-calendar-alt"></span>';
        echo '<div class="stat-content"><span class="stat-number">' . esc_html($stats['talleres_proximos']) . '</span>';
        echo '<span class="stat-label">' . esc_html__('Talleres próximos', 'flavor-platform') . '</span></div></div>';
        echo '</div>';

        if (!empty($stats['saberes_por_categoria'])) {
            echo '<h3>' . esc_html__('Por categoría', 'flavor-platform') . '</h3>';
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Categoría', 'flavor-platform') . '</th><th>' . esc_html__('Total', 'flavor-platform') . '</th></tr></thead><tbody>';
            foreach ($stats['saberes_por_categoria'] as $cat) {
                $categoria_nombre = self::CATEGORIAS_SABER[$cat['categoria']]['nombre'] ?? $cat['categoria'];
                echo '<tr><td>' . esc_html($categoria_nombre) . '</td><td>' . esc_html($cat['total']) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza listado de saberes
     */
    public function render_admin_listado(): void {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Saberes Documentados', 'flavor-platform'));

        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $categoria = isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '';

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="saberes-listado">';
        echo '<select name="estado"><option value="">' . esc_html__('Todos los estados', 'flavor-platform') . '</option>';
        foreach (['publish' => 'Publicado', 'pending' => 'Pendiente', 'draft' => 'Borrador'] as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($estado, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> ';
        echo '<select name="categoria"><option value="">' . esc_html__('Todas las categorías', 'flavor-platform') . '</option>';
        foreach (self::CATEGORIAS_SABER as $key => $cat) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($categoria, $key, false) . '>' . esc_html($cat['nombre']) . '</option>';
        }
        echo '</select> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-platform') . '</button>';
        echo '</form>';

        $args = ['post_type' => 'sa_saber', 'posts_per_page' => 50];
        if ($estado) {
            $args['post_status'] = $estado;
        } else {
            $args['post_status'] = ['publish', 'pending', 'draft'];
        }
        if ($categoria) {
            $args['tax_query'] = [['taxonomy' => 'sa_categoria', 'field' => 'slug', 'terms' => $categoria]];
        }

        $saberes = get_posts($args);

        if (empty($saberes)) {
            echo '<p>' . esc_html__('No hay saberes con esos filtros.', 'flavor-platform') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>' . esc_html__('Título', 'flavor-platform') . '</th><th>' . esc_html__('Categoría', 'flavor-platform') . '</th>';
            echo '<th>' . esc_html__('Estado', 'flavor-platform') . '</th><th>' . esc_html__('Autor', 'flavor-platform') . '</th>';
            echo '<th>' . esc_html__('Acciones', 'flavor-platform') . '</th></tr></thead><tbody>';

            foreach ($saberes as $saber) {
                $cat_terms = wp_get_post_terms($saber->ID, 'sa_categoria', ['fields' => 'names']);
                echo '<tr>';
                echo '<td>' . esc_html($saber->ID) . '</td>';
                echo '<td>' . esc_html($saber->post_title) . '</td>';
                echo '<td>' . esc_html(implode(', ', $cat_terms)) . '</td>';
                echo '<td>' . esc_html(ucfirst($saber->post_status)) . '</td>';
                echo '<td>' . esc_html(get_the_author_meta('display_name', $saber->post_author)) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($saber->ID)) . '" class="button button-small">' . esc_html__('Editar', 'flavor-platform') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza listado de talleres
     */
    public function render_admin_talleres(): void {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Talleres de Saberes', 'flavor-platform'), [
            ['label' => __('Nuevo Taller', 'flavor-platform'), 'url' => admin_url('post-new.php?post_type=sa_taller'), 'class' => 'button-primary'],
        ]);

        $talleres = get_posts(['post_type' => 'sa_taller', 'posts_per_page' => 50, 'post_status' => ['publish', 'draft']]);

        if (empty($talleres)) {
            echo '<p>' . esc_html__('No hay talleres programados.', 'flavor-platform') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>' . esc_html__('Título', 'flavor-platform') . '</th><th>' . esc_html__('Fecha', 'flavor-platform') . '</th>';
            echo '<th>' . esc_html__('Plazas', 'flavor-platform') . '</th><th>' . esc_html__('Inscritos', 'flavor-platform') . '</th>';
            echo '<th>' . esc_html__('Acciones', 'flavor-platform') . '</th></tr></thead><tbody>';

            foreach ($talleres as $taller) {
                $inscritos = get_post_meta($taller->ID, '_sa_inscritos', true) ?: [];
                $plazas = (int) get_post_meta($taller->ID, '_sa_plazas', true) ?: 20;
                $fecha = get_post_meta($taller->ID, '_sa_fecha', true);
                echo '<tr>';
                echo '<td>' . esc_html($taller->ID) . '</td>';
                echo '<td>' . esc_html($taller->post_title) . '</td>';
                echo '<td>' . esc_html($fecha ?: '-') . '</td>';
                echo '<td>' . esc_html($plazas) . '</td>';
                echo '<td>' . esc_html(count($inscritos)) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($taller->ID)) . '" class="button button-small">' . esc_html__('Editar', 'flavor-platform') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza listado de portadores
     */
    public function render_admin_portadores(): void {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Portadores de Saberes', 'flavor-platform'), [
            ['label' => __('Nuevo Portador', 'flavor-platform'), 'url' => admin_url('post-new.php?post_type=sa_portador'), 'class' => 'button-primary'],
        ]);

        $portadores = get_posts(['post_type' => 'sa_portador', 'posts_per_page' => 50, 'post_status' => ['publish', 'draft']]);

        if (empty($portadores)) {
            echo '<p>' . esc_html__('No hay portadores registrados.', 'flavor-platform') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>' . esc_html__('Nombre', 'flavor-platform') . '</th>';
            echo '<th>' . esc_html__('Acciones', 'flavor-platform') . '</th></tr></thead><tbody>';

            foreach ($portadores as $portador) {
                echo '<tr>';
                echo '<td>' . esc_html($portador->ID) . '</td>';
                echo '<td>' . esc_html($portador->post_title) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($portador->ID)) . '" class="button button-small">' . esc_html__('Editar', 'flavor-platform') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Registra los tipos de post personalizados
     */
    private function register_post_types(): void {
        // Saberes documentados
        register_post_type('sa_saber', [
            'labels' => [
                'name' => __('Saberes', 'flavor-platform'),
                'singular_name' => __('Saber', 'flavor-platform'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt'],
            'capability_type' => 'post',
            'has_archive' => true,
            'rewrite' => ['slug' => 'saberes'],
        ]);

        // Portadores de saberes (personas mayores/sabias)
        register_post_type('sa_portador', [
            'labels' => [
                'name' => __('Portadores de Saberes', 'flavor-platform'),
                'singular_name' => __('Portador de Saber', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
        ]);

        // Talleres de transmisión
        register_post_type('sa_taller', [
            'labels' => [
                'name' => __('Talleres de Saberes', 'flavor-platform'),
                'singular_name' => __('Taller', 'flavor-platform'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail'],
            'capability_type' => 'post',
        ]);

        // Solicitudes de aprendizaje
        register_post_type('sa_solicitud', [
            'labels' => [
                'name' => __('Solicitudes de Aprendizaje', 'flavor-platform'),
                'singular_name' => __('Solicitud', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Registra taxonomías
     */
    private function register_taxonomies(): void {
        register_taxonomy('sa_categoria', ['sa_saber', 'sa_taller'], [
            'labels' => [
                'name' => __('Categorías de Saber', 'flavor-platform'),
                'singular_name' => __('Categoría', 'flavor-platform'),
            ],
            'public' => true,
            'hierarchical' => true,
        ]);

        register_taxonomy('sa_origen', 'sa_saber', [
            'labels' => [
                'name' => __('Origen', 'flavor-platform'),
                'singular_name' => __('Origen', 'flavor-platform'),
            ],
            'public' => true,
            'hierarchical' => false,
        ]);
    }

    /**
     * Registra los manejadores AJAX
     */
    private function register_ajax_handlers(): void {
        add_action('wp_ajax_sa_registrar_saber', [$this, 'ajax_registrar_saber']);
        add_action('wp_ajax_sa_solicitar_aprendizaje', [$this, 'ajax_solicitar_aprendizaje']);
        add_action('wp_ajax_sa_inscribirse_taller', [$this, 'ajax_inscribirse_taller']);
        add_action('wp_ajax_sa_proponer_taller', [$this, 'ajax_proponer_taller']);
        add_action('wp_ajax_sa_agradecer_saber', [$this, 'ajax_agradecer_saber']);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes(): void {
        // Shortcodes principales
        add_shortcode('saberes_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('saberes_portadores', [$this, 'shortcode_portadores']);
        add_shortcode('saberes_talleres', [$this, 'shortcode_talleres']);
        add_shortcode('saberes_compartir', [$this, 'shortcode_compartir']);
        add_shortcode('saberes_mis_aprendizajes', [$this, 'shortcode_mis_aprendizajes']);

        // Aliases con prefijo flavor_ para compatibilidad con dynamic-pages
        add_shortcode('flavor_saberes_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('flavor_saberes_compartir', [$this, 'shortcode_compartir']);
        add_shortcode('flavor_saberes_talleres', [$this, 'shortcode_talleres']);
    }

    /**
     * Encola los assets del módulo
     */
    public function enqueue_assets(): void {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-saberes-ancestrales',
            $this->get_module_url() . 'assets/css/saberes-ancestrales.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-saberes-ancestrales',
            $this->get_module_url() . 'assets/js/saberes-ancestrales.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-saberes-ancestrales', 'flavorSaberes', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('saberes_nonce'),
            'categorias' => self::CATEGORIAS_SABER,
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'guardado' => __('Guardado correctamente', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets(): bool {
        global $post;

        if (!$post) {
            return false;
        }

        // Verificar CPTs del módulo
        if (is_singular(['sa_saber', 'sa_taller', 'sa_portador'])) {
            return true;
        }

        if (is_post_type_archive(['sa_saber', 'sa_taller'])) {
            return true;
        }

        $shortcodes_modulo = [
            'saberes_catalogo',
            'saberes_portadores',
            'saberes_talleres',
            'saberes_compartir',
            'saberes_mis_aprendizajes',
            'flavor_saberes_catalogo',
            'flavor_saberes_compartir',
            'flavor_saberes_talleres',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * AJAX: Registrar un saber
     */
    public function ajax_registrar_saber(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $origen = sanitize_text_field($_POST['origen'] ?? '');
        $portador = sanitize_text_field($_POST['portador'] ?? '');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-platform')]);
        }

        $saber_id = wp_insert_post([
            'post_type' => 'sa_saber',
            'post_status' => 'pending', // Revisión antes de publicar
            'post_author' => $user_id,
            'post_title' => $titulo,
            'post_content' => $descripcion,
        ], true);

        if (is_wp_error($saber_id) || empty($saber_id)) {
            $error = is_wp_error($saber_id) ? $saber_id->get_error_message() : __('No se pudo crear el saber.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        // Asignar categoría
        if ($categoria && isset(self::CATEGORIAS_SABER[$categoria])) {
            wp_set_object_terms($saber_id, $categoria, 'sa_categoria');
        }

        // Guardar metadatos
        update_post_meta($saber_id, '_sa_origen', $origen);
        update_post_meta($saber_id, '_sa_portador', $portador);
        update_post_meta($saber_id, '_sa_documentado_por', $user_id);
        update_post_meta($saber_id, '_sa_fecha_documentacion', current_time('mysql'));
        update_post_meta($saber_id, '_sa_agradecimientos', 0);

        wp_send_json_success([
            'message' => __('Saber documentado. Será revisado antes de publicarse.', 'flavor-platform'),
            'saber_id' => $saber_id,
        ]);
    }

    /**
     * AJAX: Solicitar aprendizaje de un saber
     */
    public function ajax_solicitar_aprendizaje(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $saber_id = intval($_POST['saber_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        $saber = get_post($saber_id);
        if (!$saber || $saber->post_type !== 'sa_saber') {
            wp_send_json_error(['message' => __('Saber no encontrado', 'flavor-platform')]);
        }

        // Verificar si ya solicitó
        global $wpdb;
        $ya_solicito = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_solicitud'
               AND p.post_author = %d
               AND pm.meta_key = '_sa_saber_id'
               AND pm.meta_value = %d",
            $user_id, $saber_id
        ));

        if ($ya_solicito > 0) {
            wp_send_json_error(['message' => __('Ya has solicitado aprender este saber', 'flavor-platform')]);
        }

        $solicitud_id = wp_insert_post([
            'post_type' => 'sa_solicitud',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf(__('Solicitud: %s', 'flavor-platform'), $saber->post_title),
        ], true);

        if (is_wp_error($solicitud_id) || empty($solicitud_id)) {
            $error = is_wp_error($solicitud_id) ? $solicitud_id->get_error_message() : __('No se pudo crear la solicitud.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($solicitud_id, '_sa_saber_id', $saber_id);
        update_post_meta($solicitud_id, '_sa_mensaje', $mensaje);
        update_post_meta($solicitud_id, '_sa_estado', 'pendiente');
        update_post_meta($solicitud_id, '_sa_fecha', current_time('mysql'));

        wp_send_json_success([
            'message' => __('Solicitud enviada. Te contactaremos cuando haya oportunidad de aprender.', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Inscribirse en taller
     */
    public function ajax_inscribirse_taller(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $taller_id = intval($_POST['taller_id'] ?? 0);

        $taller = get_post($taller_id);
        if (!$taller || $taller->post_type !== 'sa_taller') {
            wp_send_json_error(['message' => __('Taller no encontrado', 'flavor-platform')]);
        }

        $inscritos = get_post_meta($taller_id, '_sa_inscritos', true) ?: [];
        $plazas = intval(get_post_meta($taller_id, '_sa_plazas', true)) ?: 20;

        if (in_array($user_id, $inscritos)) {
            wp_send_json_error(['message' => __('Ya estás inscrito', 'flavor-platform')]);
        }

        if (count($inscritos) >= $plazas) {
            wp_send_json_error(['message' => __('No quedan plazas disponibles', 'flavor-platform')]);
        }

        $inscritos[] = $user_id;
        update_post_meta($taller_id, '_sa_inscritos', $inscritos);

        wp_send_json_success([
            'message' => __('Inscripción completada', 'flavor-platform'),
            'plazas_restantes' => $plazas - count($inscritos),
        ]);
    }

    /**
     * AJAX: Agradecer un saber
     */
    public function ajax_agradecer_saber(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $saber_id = intval($_POST['saber_id'] ?? 0);

        $agradecimientos = intval(get_post_meta($saber_id, '_sa_agradecimientos', true));
        update_post_meta($saber_id, '_sa_agradecimientos', $agradecimientos + 1);

        wp_send_json_success([
            'agradecimientos' => $agradecimientos + 1,
        ]);
    }

    /**
     * Obtiene estadísticas del módulo
     */
    public function get_estadisticas(): array {
        global $wpdb;

        $saberes_total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_saber' AND post_status = 'publish'"
        );

        $portadores = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_portador' AND post_status = 'publish'"
        );

        $talleres_proximos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_taller'
               AND p.post_status = 'publish'
               AND pm.meta_key = '_sa_fecha'
               AND pm.meta_value >= %s",
            current_time('mysql')
        ));

        $saberes_por_categoria = $wpdb->get_results(
            "SELECT t.slug as categoria, COUNT(*) as total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE p.post_type = 'sa_saber'
               AND p.post_status = 'publish'
               AND tt.taxonomy = 'sa_categoria'
             GROUP BY t.slug"
        , ARRAY_A);

        return [
            'saberes_total' => intval($saberes_total),
            'portadores' => intval($portadores),
            'talleres_proximos' => intval($talleres_proximos),
            'saberes_por_categoria' => $saberes_por_categoria,
        ];
    }

    /**
     * Obtiene estadísticas del usuario
     */
    public function get_estadisticas_usuario(int $user_id): array {
        global $wpdb;

        $saberes_documentados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'sa_saber' AND post_author = %d",
            $user_id
        ));

        $talleres_inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_taller'
               AND pm.meta_key = '_sa_inscritos'
               AND pm.meta_value LIKE %s",
            '%"' . $user_id . '"%'
        ));

        $solicitudes_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_solicitud'
               AND p.post_author = %d
               AND pm.meta_key = '_sa_estado'
               AND pm.meta_value = 'pendiente'",
            $user_id
        ));

        return [
            'saberes_documentados' => intval($saberes_documentados),
            'talleres_inscritos' => intval($talleres_inscritos),
            'solicitudes_pendientes' => intval($solicitudes_pendientes),
        ];
    }

    /**
     * Shortcode: Catálogo de saberes
     */
    public function shortcode_catalogo($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Portadores de saberes
     */
    public function shortcode_portadores($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/portadores.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Talleres
     */
    public function shortcode_talleres($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/talleres.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Compartir saber
     */
    public function shortcode_compartir($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="sa-login-required">' . __('Inicia sesión para compartir saberes', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/compartir.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis aprendizajes
     */
    public function shortcode_mis_aprendizajes($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="sa-login-required">' . __('Inicia sesión para ver tus aprendizajes', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/mis-aprendizajes.php';
        return ob_get_clean();
    }

    /**
     * Registra el widget de dashboard
     */
    public function register_dashboard_widget($registry): void {
        $widget_file = $this->get_module_path() . 'class-saberes-ancestrales-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('Flavor_Saberes_Ancestrales_Widget')) {
                $registry->register(new Flavor_Saberes_Ancestrales_Widget($this));
            }
        }
    }

    /**
     * Obtiene la ruta del módulo
     */
    public function get_module_path(): string {
        return plugin_dir_path(__FILE__);
    }

    /**
     * Obtiene la URL del módulo
     */
    public function get_module_url(): string {
        return plugin_dir_url(__FILE__);
    }

    /**
     * Valoración de conciencia del módulo
     */
    public function get_consciousness_valuation(): array {
        return [
            'nombre' => 'Saberes Ancestrales',
            'puntuacion' => 88,
            'premisas' => [
                'madurez_ciclica' => 0.30, // Ciclos generacionales de conocimiento
                'conciencia_fundamental' => 0.25, // Sabiduría acumulada
                'interdependencia_radical' => 0.20, // Conexión intergeneracional
                'valor_intrinseco' => 0.15, // Valor del conocimiento tradicional
                'abundancia_organizable' => 0.10, // Organizar y transmitir saberes
            ],
            'descripcion_contribucion' => 'Este módulo honra la sabiduría de los mayores y el conocimiento ' .
                'acumulado por generaciones. Reconoce que la madurez colectiva viene de integrar ' .
                'el pasado con el presente, y que cada saber ancestral contiene conciencia cristalizada.',
            'categoria' => 'cultura',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_saberes' => [
                'description' => 'Buscar saberes ancestrales por categoría',
                'params' => ['categoria'],
            ],
            'ver_talleres' => [
                'description' => 'Ver talleres de transmisión de saberes',
                'params' => [],
            ],
            'guardianes_saber' => [
                'description' => 'Ver guardianes de saberes de la comunidad',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'catalogo_saberes',
            'listado' => 'catalogo_saberes',
            'catalogo' => 'catalogo_saberes',
            'talleres' => 'talleres_transmision',
            'guardianes' => 'guardianes_saber',
            'portadores' => 'guardianes_saber',
            'crear' => 'compartir_saber',
            'documentar' => 'compartir_saber',
            'mis_items' => 'mis_aprendizajes',
            'aprender' => 'mis_aprendizajes',
            'foro' => 'foro_saber',
            'chat' => 'chat_saber',
            'multimedia' => 'multimedia_saber',
            'red-social' => 'red_social_saber',
            'red_social' => 'red_social_saber',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'error' => __('Acción no implementada', 'flavor-platform'),
        ];
    }

    private function action_catalogo_saberes($params) {
        return ['success' => true, 'html' => do_shortcode('[saberes_catalogo]')];
    }

    private function action_talleres_transmision($params) {
        return ['success' => true, 'html' => do_shortcode('[saberes_talleres]')];
    }

    private function action_guardianes_saber($params) {
        return ['success' => true, 'html' => do_shortcode('[saberes_portadores]')];
    }

    private function action_compartir_saber($params) {
        return ['success' => true, 'html' => do_shortcode('[saberes_compartir]')];
    }

    private function action_mis_aprendizajes($params) {
        return ['success' => true, 'html' => do_shortcode('[saberes_mis_aprendizajes]')];
    }

    private function resolve_contextual_saber($params = []) {
        $saber_id = absint(
            $params['saber_id']
            ?? $params['id']
            ?? $_GET['saber_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$saber_id) {
            return null;
        }

        $saber = get_post($saber_id);
        if (!$saber || $saber->post_type !== 'sa_saber') {
            return null;
        }

        return [
            'id' => (int) $saber->ID,
            'titulo' => (string) $saber->post_title,
            'descripcion' => (string) $saber->post_content,
        ];
    }

    private function action_foro_saber($params) {
        $saber = $this->resolve_contextual_saber($params);
        if (!$saber) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un saber para ver su foro.', 'flavor-platform') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del saber', 'flavor-platform') . '</h2>'
            . '<p>' . esc_html($saber['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="saber_ancestral" entidad_id="' . absint($saber['id']) . '"]')
            . '</div>';
    }

    private function action_chat_saber($params) {
        $saber = $this->resolve_contextual_saber($params);
        if (!$saber) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un saber para ver su chat.', 'flavor-platform') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este saber.', 'flavor-platform') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del saber', 'flavor-platform') . '</h2><p>' . esc_html($saber['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?saber_id=' . absint($saber['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', 'flavor-platform')
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="saber_ancestral" entidad_id="' . absint($saber['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_saber($params) {
        $saber = $this->resolve_contextual_saber($params);
        if (!$saber) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un saber para ver sus archivos.', 'flavor-platform') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del saber', 'flavor-platform') . '</h2><p>' . esc_html($saber['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?saber_id=' . absint($saber['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', 'flavor-platform')
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="saber_ancestral" entidad_id="' . absint($saber['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_saber($params) {
        $saber = $this->resolve_contextual_saber($params);
        if (!$saber) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un saber para ver su actividad social.', 'flavor-platform') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este saber.', 'flavor-platform') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del saber', 'flavor-platform') . '</h2><p>' . esc_html($saber['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?saber_id=' . absint($saber['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', 'flavor-platform')
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="saber_ancestral" entidad_id="' . absint($saber['id']) . '"]')
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return __('Saberes Ancestrales preserva y transmite el conocimiento tradicional de la comunidad, conectando generaciones y honrando la sabiduría de los mayores.', 'flavor-platform');
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'saberes-ancestrales',
            'title'    => __('Saberes Ancestrales', 'flavor-platform'),
            'subtitle' => __('Preservación y transmisión del conocimiento tradicional', 'flavor-platform'),
            'icon'     => '📜',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_saberes_ancestrales',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'required' => true],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', 'flavor-platform'), 'options' => ['artesania', 'cocina', 'agricultura', 'medicina', 'musica', 'oficios', 'tradiciones']],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'guardian'    => ['type' => 'text', 'label' => __('Guardián del saber', 'flavor-platform')],
                'origen'      => ['type' => 'text', 'label' => __('Origen/Tradición', 'flavor-platform')],
                'multimedia'  => ['type' => 'file', 'label' => __('Material multimedia', 'flavor-platform'), 'multiple' => true],
            ],

            'estados' => [
                'documentado' => ['label' => __('Documentado', 'flavor-platform'), 'color' => 'blue', 'icon' => '📝'],
                'en_transmision' => ['label' => __('En transmisión', 'flavor-platform'), 'color' => 'green', 'icon' => '🔄'],
                'preservado'  => ['label' => __('Preservado', 'flavor-platform'), 'color' => 'amber', 'icon' => '🏆'],
                'en_riesgo'   => ['label' => __('En riesgo', 'flavor-platform'), 'color' => 'red', 'icon' => '⚠️'],
            ],

            'stats' => [
                'saberes_catalogados' => ['label' => __('Saberes catalogados', 'flavor-platform'), 'icon' => '📜', 'color' => 'yellow'],
                'guardianes'          => ['label' => __('Guardianes', 'flavor-platform'), 'icon' => '👴', 'color' => 'amber'],
                'talleres_activos'    => ['label' => __('Talleres activos', 'flavor-platform'), 'icon' => '🎓', 'color' => 'green'],
                'aprendices'          => ['label' => __('Aprendices', 'flavor-platform'), 'icon' => '👥', 'color' => 'blue'],
            ],

            'card' => [
                'template'     => 'saber-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['guardian', 'origen'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'catalogo' => [
                    'label'   => __('Catálogo', 'flavor-platform'),
                    'icon'    => 'dashicons-portfolio',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'guardianes' => [
                    'label'   => __('Guardianes', 'flavor-platform'),
                    'icon'    => 'dashicons-admin-users',
                    'content' => 'shortcode:saberes_portadores',
                    'public'  => true,
                ],
                'talleres' => [
                    'label'   => __('Talleres', 'flavor-platform'),
                    'icon'    => 'dashicons-welcome-learn-more',
                    'content' => 'shortcode:saberes_talleres',
                    'public'  => true,
                ],
                'documentar' => [
                    'label'      => __('Documentar', 'flavor-platform'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:saberes_compartir',
                    'requires_login' => true,
                ],
                'aprender' => [
                    'label'      => __('Aprender', 'flavor-platform'),
                    'icon'       => 'dashicons-book',
                    'content'    => 'shortcode:saberes_mis_aprendizajes',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'titulo',
                'order'      => 'ASC',
                'filterable' => ['categoria', 'origen'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'saberes_destacados', 'proximos_talleres', 'guardianes_activos'],
                'actions' => [
                    'documentar' => ['label' => __('Documentar saber', 'flavor-platform'), 'icon' => '📝', 'color' => 'yellow'],
                    'aprender'   => ['label' => __('Aprender saber', 'flavor-platform'), 'icon' => '📚', 'color' => 'green'],
                ],
            ],

            'features' => [
                'multimedia'       => true,
                'transmision'      => true,
                'talleres'         => true,
                'reconocimiento'   => true,
                'arbol_genealogico' => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-saberes-ancestrales-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Saberes_Ancestrales_Dashboard_Tab')) {
                Flavor_Saberes_Ancestrales_Dashboard_Tab::get_instance();
            }
        }
    }
}
