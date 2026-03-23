<?php
/**
 * Módulo: Biodiversidad Local
 *
 * Catalogación comunitaria de especies, proyectos de conservación,
 * avistamientos y protección de ecosistemas locales.
 *
 * @package FlavorChatIA
 * @subpackage Modules\BiodiversidadLocal
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Biodiversidad_Local_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Categorías de especies
     */
    const CATEGORIAS_ESPECIES = [
        'flora' => [
            'nombre' => 'Flora',
            'icono' => 'dashicons-palmtree',
            'color' => '#22c55e',
            'subcategorias' => ['arboles', 'arbustos', 'plantas_herbaceas', 'hongos', 'liquenes', 'algas'],
        ],
        'fauna_vertebrados' => [
            'nombre' => 'Fauna Vertebrada',
            'icono' => 'dashicons-pets',
            'color' => '#f97316',
            'subcategorias' => ['aves', 'mamiferos', 'reptiles', 'anfibios', 'peces'],
        ],
        'fauna_invertebrados' => [
            'nombre' => 'Invertebrados',
            'icono' => 'dashicons-admin-site-alt',
            'color' => '#a855f7',
            'subcategorias' => ['insectos', 'aracnidos', 'moluscos', 'crustaceos', 'otros'],
        ],
    ];

    /**
     * Estados de conservación (basado en IUCN)
     */
    const ESTADOS_CONSERVACION = [
        'no_evaluada' => ['nombre' => 'No Evaluada', 'color' => '#6b7280', 'icono' => 'NE'],
        'preocupacion_menor' => ['nombre' => 'Preocupación Menor', 'color' => '#22c55e', 'icono' => 'LC'],
        'casi_amenazada' => ['nombre' => 'Casi Amenazada', 'color' => '#84cc16', 'icono' => 'NT'],
        'vulnerable' => ['nombre' => 'Vulnerable', 'color' => '#eab308', 'icono' => 'VU'],
        'en_peligro' => ['nombre' => 'En Peligro', 'color' => '#f97316', 'icono' => 'EN'],
        'en_peligro_critico' => ['nombre' => 'En Peligro Crítico', 'color' => '#ef4444', 'icono' => 'CR'],
        'extinta_silvestre' => ['nombre' => 'Extinta en Estado Silvestre', 'color' => '#1f2937', 'icono' => 'EW'],
        'extinta' => ['nombre' => 'Extinta', 'color' => '#000000', 'icono' => 'EX'],
    ];

    /**
     * Tipos de hábitat
     */
    const TIPOS_HABITAT = [
        'bosque' => ['nombre' => 'Bosque', 'icono' => 'dashicons-palmtree'],
        'pradera' => ['nombre' => 'Pradera/Pastizal', 'icono' => 'dashicons-welcome-view-site'],
        'humedal' => ['nombre' => 'Humedal', 'icono' => 'dashicons-format-audio'],
        'rio' => ['nombre' => 'Río/Arroyo', 'icono' => 'dashicons-chart-line'],
        'montaña' => ['nombre' => 'Montaña', 'icono' => 'dashicons-image-filter'],
        'costa' => ['nombre' => 'Costa/Litoral', 'icono' => 'dashicons-waves'],
        'urbano' => ['nombre' => 'Urbano/Periurbano', 'icono' => 'dashicons-building'],
        'agricola' => ['nombre' => 'Agrícola', 'icono' => 'dashicons-carrot'],
    ];

    /**
     * Tipos de proyectos de conservación
     */
    const TIPOS_PROYECTO = [
        'reforestacion' => ['nombre' => 'Reforestación', 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
        'limpieza' => ['nombre' => 'Limpieza de Espacios', 'icono' => 'dashicons-trash', 'color' => '#3b82f6'],
        'censo' => ['nombre' => 'Censo de Especies', 'icono' => 'dashicons-clipboard', 'color' => '#8b5cf6'],
        'proteccion' => ['nombre' => 'Protección de Hábitat', 'icono' => 'dashicons-shield', 'color' => '#f59e0b'],
        'educacion' => ['nombre' => 'Educación Ambiental', 'icono' => 'dashicons-book-alt', 'color' => '#06b6d4'],
        'polinizadores' => ['nombre' => 'Apoyo a Polinizadores', 'icono' => 'dashicons-admin-site-alt', 'color' => '#f97316'],
        'fauna_silvestre' => ['nombre' => 'Refugio Fauna Silvestre', 'icono' => 'dashicons-pets', 'color' => '#a855f7'],
        'semillas' => ['nombre' => 'Banco de Semillas', 'icono' => 'dashicons-marker', 'color' => '#84cc16'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'biodiversidad_local';
        $this->name = __('Biodiversidad Local', 'flavor-chat-ia');
        $this->description = __('Catálogo comunitario de especies locales, proyectos de conservación y ciencia ciudadana.', 'flavor-chat-ia');
        $this->icon = 'dashicons-admin-site-alt3';
        $this->category = 'medioambiente';
        $this->visibility = 'registered';
        $this->version = '1.0.0';

        parent::__construct();
    }

    /**
     * Inicialización del módulo
     * Se ejecuta cuando el módulo está activo
     */
    public function init() {
        // El módulo utiliza funcionalidad heredada de la clase base
        // Hooks adicionales pueden añadirse aquí
    }

    /**
     * Obtiene la valoración de conciencia del módulo
     *
     * @return array
     */
    public function get_consciousness_valuation(): array {
        return [
            'puntuacion_total' => 87,
            'premisas' => [
                'conciencia_fundamental' => [
                    'puntuacion' => 19,
                    'descripcion' => __('Reconoce el valor intrínseco de cada especie y su derecho a existir, más allá de su utilidad para los humanos.', 'flavor-chat-ia'),
                ],
                'abundancia_organizable' => [
                    'puntuacion' => 18,
                    'descripcion' => __('Cataloga y organiza el conocimiento colectivo sobre la riqueza natural del territorio como patrimonio común.', 'flavor-chat-ia'),
                ],
                'interdependencia_radical' => [
                    'puntuacion' => 20,
                    'descripcion' => __('Visualiza las conexiones ecosistémicas y cómo cada especie depende de otras en la trama de la vida.', 'flavor-chat-ia'),
                ],
                'madurez_ciclica' => [
                    'puntuacion' => 15,
                    'descripcion' => __('Respeta los ciclos naturales y las temporadas de reproducción, migración y descanso de las especies.', 'flavor-chat-ia'),
                ],
                'valor_intrinseco' => [
                    'puntuacion' => 15,
                    'descripcion' => __('Documenta especies sin criterios de utilidad, valorando por igual a las consideradas "humildes" o "insignificantes".', 'flavor-chat-ia'),
                ],
            ],
            'fortalezas' => [
                __('Excelente reconocimiento de la interdependencia ecosistémica', 'flavor-chat-ia'),
                __('Fuerte valoración de la conciencia en todas las formas de vida', 'flavor-chat-ia'),
                __('Promueve la ciencia ciudadana como forma de conexión con la naturaleza', 'flavor-chat-ia'),
            ],
            'areas_mejora' => [
                __('Podría incorporar más elementos de sabiduría indígena sobre biodiversidad', 'flavor-chat-ia'),
                __('Integrar perspectivas de derechos de la naturaleza', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'recetas', 'biblioteca'];
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
                'table'   => $wpdb->prefix . 'flavor_especies',
                'context' => 'normal',
            ],
        ];
    }

    /**
     * Configura el módulo
     */
    protected function setup_module() {
        $this->register_as_integration_consumer();

        // Registrar CPT en el hook 'init' de WordPress
        add_action('init', [$this, 'register_all_cpts'], 5);

        $this->register_ajax_handlers();

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Panel Unificado Admin
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();

        // Encolar assets del frontend
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets_frontend']);
    }

    /**
     * Registra y encola assets del frontend
     */
    public function registrar_assets_frontend() {
        // Registrar CSS
        wp_register_style(
            'flavor-biodiversidad-local',
            FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-local.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_style(
            'flavor-biodiversidad-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-frontend.css',
            ['flavor-biodiversidad-local'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Encolar en páginas del módulo
        if ($this->is_module_page()) {
            wp_enqueue_style('flavor-biodiversidad-frontend');
        }
    }

    /**
     * Verifica si estamos en una página del módulo
     *
     * @return bool
     */
    private function is_module_page() {
        global $post;

        // Verificar por shortcode en el contenido
        if ($post && is_a($post, 'WP_Post')) {
            $shortcodes = ['flavor_biodiversidad', 'flavor_biodiversidad_catalogo', 'flavor_biodiversidad_proyectos'];
            foreach ($shortcodes as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    return true;
                }
            }
        }

        // Verificar por parámetro de módulo en la URL
        if (isset($_GET['flavor_module']) && $_GET['flavor_module'] === 'biodiversidad-local') {
            return true;
        }

        // Verificar por acción del módulo
        if (isset($_GET['action']) && isset($_GET['module']) && $_GET['module'] === 'biodiversidad-local') {
            return true;
        }

        return false;
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $tab_file = dirname(__FILE__) . '/class-biodiversidad-local-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Biodiversidad_Local_Dashboard_Tab')) {
                Flavor_Biodiversidad_Local_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra el widget de dashboard
     */
    public function register_dashboard_widget($registry) {
        $settings = $this->get_settings();
        if (empty($settings['mostrar_en_dashboard'])) {
            return;
        }

        $widget_path = dirname(__FILE__) . '/class-biodiversidad-local-widget.php';
        if (!class_exists('Flavor_Biodiversidad_Local_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Biodiversidad_Local_Widget')) {
            $registry->register(new Flavor_Biodiversidad_Local_Widget($this));
        }
    }

    /**
     * Registra todos los CPTs y taxonomías
     */
    public function register_all_cpts() {
        $this->register_cpt_especie();
        $this->register_cpt_avistamiento();
        $this->register_cpt_proyecto_conservacion();
        $this->register_taxonomies();
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Listar especies
        register_rest_route($namespace, '/biodiversidad/especies', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_especies'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener especie
        register_rest_route($namespace, '/biodiversidad/especies/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_especie'],
            'permission_callback' => '__return_true',
        ]);

        // Listar avistamientos
        register_rest_route($namespace, '/biodiversidad/avistamientos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_avistamientos'],
            'permission_callback' => '__return_true',
        ]);

        // Mis avistamientos
        register_rest_route($namespace, '/biodiversidad/mis-avistamientos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mis_avistamientos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Listar proyectos
        register_rest_route($namespace, '/biodiversidad/proyectos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_proyectos'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Verifica si el usuario está logueado
     */
    public function check_user_logged_in(): bool {
        return is_user_logged_in();
    }

    /**
     * API: Obtener especies
     */
    public function api_get_especies(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'bl_especie',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
        ];

        if ($categoria = $request->get_param('categoria')) {
            $args['tax_query'] = [['taxonomy' => 'bl_categoria', 'field' => 'slug', 'terms' => $categoria]];
        }

        $query = new \WP_Query($args);
        $especies = [];

        foreach ($query->posts as $post) {
            $especies[] = [
                'id' => $post->ID,
                'nombre' => $post->post_title,
                'nombre_cientifico' => get_post_meta($post->ID, '_bl_nombre_cientifico', true),
                'estado_conservacion' => get_post_meta($post->ID, '_bl_estado_conservacion', true),
                'imagen' => get_the_post_thumbnail_url($post->ID, 'medium'),
            ];
        }

        return new \WP_REST_Response([
            'especies' => $especies,
            'total' => $query->found_posts,
        ]);
    }

    /**
     * API: Obtener especie individual
     */
    public function api_get_especie(\WP_REST_Request $request): \WP_REST_Response {
        $especie_id = $request->get_param('id');
        $especie = get_post($especie_id);

        if (!$especie || $especie->post_type !== 'bl_especie') {
            return new \WP_REST_Response(['error' => 'Especie no encontrada'], 404);
        }

        return new \WP_REST_Response([
            'id' => $especie->ID,
            'nombre' => $especie->post_title,
            'descripcion' => $especie->post_content,
            'nombre_cientifico' => get_post_meta($especie->ID, '_bl_nombre_cientifico', true),
            'estado_conservacion' => get_post_meta($especie->ID, '_bl_estado_conservacion', true),
            'imagen' => get_the_post_thumbnail_url($especie->ID, 'large'),
        ]);
    }

    /**
     * API: Obtener avistamientos
     */
    public function api_get_avistamientos(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'bl_avistamiento',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
        ];

        $query = new \WP_Query($args);
        $avistamientos = [];

        foreach ($query->posts as $post) {
            $avistamientos[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'especie_id' => get_post_meta($post->ID, '_bl_especie_id', true),
                'latitud' => get_post_meta($post->ID, '_bl_latitud', true),
                'longitud' => get_post_meta($post->ID, '_bl_longitud', true),
                'fecha' => get_post_meta($post->ID, '_bl_fecha', true),
            ];
        }

        return new \WP_REST_Response([
            'avistamientos' => $avistamientos,
            'total' => $query->found_posts,
        ]);
    }

    /**
     * API: Obtener mis avistamientos
     */
    public function api_get_mis_avistamientos(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();

        $query = new \WP_Query([
            'post_type' => 'bl_avistamiento',
            'post_status' => ['publish', 'pending'],
            'author' => $user_id,
            'posts_per_page' => -1,
        ]);

        $avistamientos = [];
        foreach ($query->posts as $post) {
            $avistamientos[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'estado' => $post->post_status,
                'fecha' => get_post_meta($post->ID, '_bl_fecha', true),
            ];
        }

        return new \WP_REST_Response(['avistamientos' => $avistamientos]);
    }

    /**
     * API: Obtener proyectos
     */
    public function api_get_proyectos(\WP_REST_Request $request): \WP_REST_Response {
        $query = new \WP_Query([
            'post_type' => 'bl_proyecto',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
        ]);

        $proyectos = [];
        foreach ($query->posts as $post) {
            $participantes = get_post_meta($post->ID, '_bl_participantes', true) ?: [];
            $proyectos[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'tipo' => get_post_meta($post->ID, '_bl_tipo', true),
                'participantes' => count($participantes),
            ];
        }

        return new \WP_REST_Response(['proyectos' => $proyectos, 'total' => $query->found_posts]);
    }

    /**
     * Configuración del admin para el panel unificado
     */
    public function get_admin_config(): array {
        return [
            'id' => 'biodiversidad_local',
            'label' => __('Biodiversidad Local', 'flavor-chat-ia'),
            'icon' => 'dashicons-admin-site-alt3',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'biodiversidad',
                    'titulo' => __('Biodiversidad', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'biodiversidad-especies',
                    'titulo' => __('Especies', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_especies'],
                ],
                [
                    'slug' => 'biodiversidad-avistamientos',
                    'titulo' => __('Avistamientos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_avistamientos'],
                ],
                [
                    'slug' => 'biodiversidad-proyectos',
                    'titulo' => __('Proyectos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_proyectos'],
                ],
            ],
        ];
    }

    /**
     * Render: Dashboard admin
     */
    public function render_admin_dashboard(): void {
        $stats = $this->get_estadisticas();
        ?>
        <div class="wrap flavor-admin-biodiversidad">
            <h1><?php esc_html_e('Biodiversidad Local', 'flavor-chat-ia'); ?></h1>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <h3><?php echo esc_html($stats['especies_catalogadas']); ?></h3>
                    <p><?php esc_html_e('Especies catalogadas', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-visibility"></span>
                    <h3><?php echo esc_html($stats['avistamientos_total']); ?></h3>
                    <p><?php esc_html_e('Avistamientos', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-clock"></span>
                    <h3><?php echo esc_html($stats['avistamientos_pendientes']); ?></h3>
                    <p><?php esc_html_e('Pendientes validar', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php echo esc_html($stats['proyectos_activos']); ?></h3>
                    <p><?php esc_html_e('Proyectos activos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render: Listado de especies
     */
    public function render_admin_especies(): void {
        $especies = get_posts(['post_type' => 'bl_especie', 'posts_per_page' => 50, 'post_status' => ['publish', 'pending']]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Especies', 'flavor-chat-ia'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=bl_especie'); ?>" class="page-title-action"><?php esc_html_e('Añadir', 'flavor-chat-ia'); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Nombre científico', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado conservación', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($especies as $especie): $estado = get_post_meta($especie->ID, '_bl_estado_conservacion', true); ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($especie->ID); ?>"><?php echo esc_html($especie->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($especie->ID, '_bl_nombre_cientifico', true)); ?></td>
                        <td><?php echo esc_html(self::ESTADOS_CONSERVACION[$estado]['nombre'] ?? $estado); ?></td>
                        <td><?php echo esc_html($especie->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de avistamientos
     */
    public function render_admin_avistamientos(): void {
        $avistamientos = get_posts(['post_type' => 'bl_avistamiento', 'posts_per_page' => 50, 'post_status' => ['publish', 'pending']]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Avistamientos', 'flavor-chat-ia'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($avistamientos as $avist): ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($avist->ID); ?>"><?php echo esc_html($avist->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($avist->ID, '_bl_fecha', true)); ?></td>
                        <td><?php echo esc_html(get_the_author_meta('display_name', $avist->post_author)); ?></td>
                        <td><?php echo esc_html($avist->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de proyectos
     */
    public function render_admin_proyectos(): void {
        $proyectos = get_posts(['post_type' => 'bl_proyecto', 'posts_per_page' => 50, 'post_status' => ['publish', 'pending']]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Proyectos de Conservación', 'flavor-chat-ia'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=bl_proyecto'); ?>" class="page-title-action"><?php esc_html_e('Añadir', 'flavor-chat-ia'); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></th><th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($proyectos as $proy): $tipo = get_post_meta($proy->ID, '_bl_tipo', true); $participantes = get_post_meta($proy->ID, '_bl_participantes', true) ?: []; ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($proy->ID); ?>"><?php echo esc_html($proy->post_title); ?></a></td>
                        <td><?php echo esc_html(self::TIPOS_PROYECTO[$tipo]['nombre'] ?? $tipo); ?></td>
                        <td><?php echo count($participantes); ?></td>
                        <td><?php echo esc_html($proy->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Registra CPT: Especie Local
     */
    private function register_cpt_especie() {
        register_post_type('bl_especie', [
            'labels' => [
                'name' => __('Especies', 'flavor-chat-ia'),
                'singular_name' => __('Especie', 'flavor-chat-ia'),
                'add_new' => __('Añadir Especie', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Especie', 'flavor-chat-ia'),
                'edit_item' => __('Editar Especie', 'flavor-chat-ia'),
                'new_item' => __('Nueva Especie', 'flavor-chat-ia'),
                'view_item' => __('Ver Especie', 'flavor-chat-ia'),
                'search_items' => __('Buscar Especies', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/especies'],
            'menu_icon' => 'dashicons-admin-site-alt3',
        ]);
    }

    /**
     * Registra CPT: Avistamiento
     */
    private function register_cpt_avistamiento() {
        register_post_type('bl_avistamiento', [
            'labels' => [
                'name' => __('Avistamientos', 'flavor-chat-ia'),
                'singular_name' => __('Avistamiento', 'flavor-chat-ia'),
                'add_new' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'add_new_item' => __('Registrar Nuevo Avistamiento', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/avistamientos'],
        ]);
    }

    /**
     * Registra CPT: Proyecto de Conservación
     */
    private function register_cpt_proyecto_conservacion() {
        register_post_type('bl_proyecto', [
            'labels' => [
                'name' => __('Proyectos Conservación', 'flavor-chat-ia'),
                'singular_name' => __('Proyecto', 'flavor-chat-ia'),
                'add_new' => __('Crear Proyecto', 'flavor-chat-ia'),
                'add_new_item' => __('Crear Nuevo Proyecto', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/proyectos'],
            // Permite propuestas frontend de usuarios autenticados (quedan en pending).
            'map_meta_cap' => true,
            'capabilities' => [
                'create_posts' => 'read',
            ],
        ]);
    }

    /**
     * Registra taxonomías
     */
    private function register_taxonomies() {
        // Categoría de especie
        register_taxonomy('bl_categoria', 'bl_especie', [
            'labels' => [
                'name' => __('Categorías', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'biodiversidad/categoria'],
        ]);

        // Hábitat
        register_taxonomy('bl_habitat', ['bl_especie', 'bl_avistamiento'], [
            'labels' => [
                'name' => __('Hábitats', 'flavor-chat-ia'),
                'singular_name' => __('Hábitat', 'flavor-chat-ia'),
            ],
            'hierarchical' => false,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'biodiversidad/habitat'],
        ]);
    }

    /**
     * Registra manejadores AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_bl_registrar_avistamiento', [$this, 'ajax_registrar_avistamiento']);
        add_action('wp_ajax_bl_registrar_especie', [$this, 'ajax_registrar_especie']);
        add_action('wp_ajax_bl_crear_proyecto', [$this, 'ajax_crear_proyecto']);
        add_action('wp_ajax_bl_participar_proyecto', [$this, 'ajax_participar_proyecto']);
        add_action('wp_ajax_bl_validar_avistamiento', [$this, 'ajax_validar_avistamiento']);
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('biodiversidad_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('biodiversidad_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('biodiversidad_registrar', [$this, 'shortcode_registrar']);
        add_shortcode('biodiversidad_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('biodiversidad_mis_avistamientos', [$this, 'shortcode_mis_avistamientos']);
    }

    /**
     * Encola scripts y estilos
     */
    public function enqueue_assets() {
        $base_url = FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/';

        wp_enqueue_style(
            'flavor-biodiversidad',
            $base_url . 'css/biodiversidad-local.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'flavor-biodiversidad',
            $base_url . 'js/biodiversidad-local.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('flavor-biodiversidad', 'flavorBiodiversidad', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biodiversidad_nonce'),
            'categorias' => self::CATEGORIAS_ESPECIES,
            'estados' => self::ESTADOS_CONSERVACION,
            'habitats' => self::TIPOS_HABITAT,
            'i18n' => [
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'success' => __('Operación completada', 'flavor-chat-ia'),
                'confirm_avistamiento' => __('¿Registrar este avistamiento?', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Shortcode: Catálogo de especies
     */
    public function shortcode_catalogo($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de avistamientos
     */
    public function shortcode_mapa($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/mapa.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Registrar avistamiento
     */
    public function shortcode_registrar($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/registrar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos de conservación
     */
    public function shortcode_proyectos($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/proyectos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis avistamientos
     */
    public function shortcode_mis_avistamientos($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/mis-avistamientos.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Registrar avistamiento
     */
    public function ajax_registrar_avistamiento() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $especie_id = intval($_POST['especie_id'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $latitud = floatval($_POST['latitud'] ?? 0);
        $longitud = floatval($_POST['longitud'] ?? 0);
        $cantidad = intval($_POST['cantidad'] ?? 1);
        $habitat = sanitize_text_field($_POST['habitat'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? current_time('Y-m-d'));

        $especie = get_post($especie_id);
        $titulo = sprintf(
            __('Avistamiento: %s - %s', 'flavor-chat-ia'),
            $especie ? $especie->post_title : __('Especie desconocida', 'flavor-chat-ia'),
            date_i18n('j M Y', strtotime($fecha))
        );

        $avistamiento_id = wp_insert_post([
            'post_type' => 'bl_avistamiento',
            'post_status' => 'pending',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($avistamiento_id)) {
            wp_send_json_error(['message' => $avistamiento_id->get_error_message()]);
        }

        update_post_meta($avistamiento_id, '_bl_especie_id', $especie_id);
        update_post_meta($avistamiento_id, '_bl_latitud', $latitud);
        update_post_meta($avistamiento_id, '_bl_longitud', $longitud);
        update_post_meta($avistamiento_id, '_bl_cantidad', $cantidad);
        update_post_meta($avistamiento_id, '_bl_fecha', $fecha);
        update_post_meta($avistamiento_id, '_bl_validaciones', []);

        if ($habitat) {
            wp_set_object_terms($avistamiento_id, $habitat, 'bl_habitat');
        }

        wp_send_json_success([
            'message' => __('Avistamiento registrado. Será revisado por la comunidad.', 'flavor-chat-ia'),
            'avistamiento_id' => $avistamiento_id,
        ]);
    }

    /**
     * AJAX: Registrar nueva especie
     */
    public function ajax_registrar_especie() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $nombre_comun = sanitize_text_field($_POST['nombre_comun'] ?? '');
        $nombre_cientifico = sanitize_text_field($_POST['nombre_cientifico'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $estado = sanitize_text_field($_POST['estado_conservacion'] ?? 'no_evaluada');
        $habitats = array_map('sanitize_text_field', $_POST['habitats'] ?? []);

        if (empty($nombre_comun)) {
            wp_send_json_error(['message' => __('El nombre común es requerido', 'flavor-chat-ia')]);
        }

        $especie_id = wp_insert_post([
            'post_type' => 'bl_especie',
            'post_status' => 'pending',
            'post_title' => $nombre_comun,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($especie_id)) {
            wp_send_json_error(['message' => $especie_id->get_error_message()]);
        }

        update_post_meta($especie_id, '_bl_nombre_cientifico', $nombre_cientifico);
        update_post_meta($especie_id, '_bl_estado_conservacion', $estado);

        if ($categoria) {
            wp_set_object_terms($especie_id, $categoria, 'bl_categoria');
        }

        if (!empty($habitats)) {
            wp_set_object_terms($especie_id, $habitats, 'bl_habitat');
        }

        wp_send_json_success([
            'message' => __('Especie propuesta. Será revisada por la comunidad.', 'flavor-chat-ia'),
            'especie_id' => $especie_id,
        ]);
    }

    /**
     * AJAX: Crear proyecto de conservación
     */
    public function ajax_crear_proyecto() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio'] ?? '');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $participantes_max = intval($_POST['participantes_max'] ?? 0);

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-chat-ia')]);
        }

        $proyecto_id = wp_insert_post([
            'post_type' => 'bl_proyecto',
            'post_status' => 'pending',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($proyecto_id) || empty($proyecto_id)) {
            $error_message = is_wp_error($proyecto_id)
                ? $proyecto_id->get_error_message()
                : __('No se pudo crear el proyecto.', 'flavor-chat-ia');
            wp_send_json_error(['message' => $error_message]);
        }

        update_post_meta($proyecto_id, '_bl_tipo', $tipo);
        update_post_meta($proyecto_id, '_bl_fecha_inicio', $fecha_inicio);
        update_post_meta($proyecto_id, '_bl_ubicacion', $ubicacion);
        update_post_meta($proyecto_id, '_bl_participantes_max', $participantes_max);
        update_post_meta($proyecto_id, '_bl_participantes', [get_current_user_id()]);

        wp_send_json_success([
            'message' => __('Proyecto creado. Será revisado para su publicación.', 'flavor-chat-ia'),
            'proyecto_id' => $proyecto_id,
        ]);
    }

    /**
     * AJAX: Participar en proyecto
     */
    public function ajax_participar_proyecto() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
        $user_id = get_current_user_id();

        $participantes = get_post_meta($proyecto_id, '_bl_participantes', true) ?: [];
        $max_participantes = intval(get_post_meta($proyecto_id, '_bl_participantes_max', true));

        if (in_array($user_id, $participantes)) {
            wp_send_json_error(['message' => __('Ya estás participando en este proyecto', 'flavor-chat-ia')]);
        }

        if ($max_participantes > 0 && count($participantes) >= $max_participantes) {
            wp_send_json_error(['message' => __('El proyecto ha alcanzado el máximo de participantes', 'flavor-chat-ia')]);
        }

        $participantes[] = $user_id;
        update_post_meta($proyecto_id, '_bl_participantes', $participantes);

        wp_send_json_success([
            'message' => __('Te has unido al proyecto de conservación', 'flavor-chat-ia'),
            'participantes' => count($participantes),
        ]);
    }

    /**
     * AJAX: Validar avistamiento
     */
    public function ajax_validar_avistamiento() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $avistamiento_id = intval($_POST['avistamiento_id'] ?? 0);
        $es_valido = filter_var($_POST['es_valido'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $user_id = get_current_user_id();

        $validaciones = get_post_meta($avistamiento_id, '_bl_validaciones', true) ?: [];

        // Evitar doble validación
        foreach ($validaciones as $val) {
            if ($val['user_id'] === $user_id) {
                wp_send_json_error(['message' => __('Ya has validado este avistamiento', 'flavor-chat-ia')]);
            }
        }

        $validaciones[] = [
            'user_id' => $user_id,
            'es_valido' => $es_valido,
            'fecha' => current_time('mysql'),
        ];

        update_post_meta($avistamiento_id, '_bl_validaciones', $validaciones);

        // Auto-publicar si tiene 3+ validaciones positivas
        $positivas = array_filter($validaciones, fn($v) => $v['es_valido']);
        if (count($positivas) >= 3) {
            wp_update_post([
                'ID' => $avistamiento_id,
                'post_status' => 'publish',
            ]);
        }

        wp_send_json_success([
            'message' => __('Gracias por tu validación', 'flavor-chat-ia'),
            'validaciones_positivas' => count($positivas),
            'validaciones_total' => count($validaciones),
        ]);
    }

    /**
     * Obtiene estadísticas del módulo
     *
     * @return array
     */
    public function get_estadisticas(): array {
        $especies = wp_count_posts('bl_especie');
        $avistamientos = wp_count_posts('bl_avistamiento');
        $proyectos = wp_count_posts('bl_proyecto');

        $user_id = get_current_user_id();
        $mis_avistamientos = new WP_Query([
            'post_type' => 'bl_avistamiento',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        return [
            'especies_catalogadas' => $especies->publish ?? 0,
            'avistamientos_total' => $avistamientos->publish ?? 0,
            'avistamientos_pendientes' => $avistamientos->pending ?? 0,
            'proyectos_activos' => $proyectos->publish ?? 0,
            'mis_avistamientos' => $mis_avistamientos->found_posts,
        ];
    }

    /**
     * Obtiene páginas del frontend
     *
     * @return array
     */
    public function get_frontend_pages(): array {
        return [
            'catalogo' => [
                'titulo' => __('Catálogo de Especies', 'flavor-chat-ia'),
                'slug' => 'biodiversidad',
                'shortcode' => '[biodiversidad_catalogo]',
                'icono' => 'dashicons-admin-site-alt3',
            ],
            'mapa' => [
                'titulo' => __('Mapa de Avistamientos', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/mapa',
                'shortcode' => '[biodiversidad_mapa]',
                'icono' => 'dashicons-location-alt',
            ],
            'registrar' => [
                'titulo' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/registrar',
                'shortcode' => '[biodiversidad_registrar]',
                'icono' => 'dashicons-camera',
            ],
            'proyectos' => [
                'titulo' => __('Proyectos de Conservación', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/proyectos',
                'shortcode' => '[biodiversidad_proyectos]',
                'icono' => 'dashicons-groups',
            ],
            'mis_avistamientos' => [
                'titulo' => __('Mis Avistamientos', 'flavor-chat-ia'),
                'slug' => 'mi-portal/biodiversidad',
                'shortcode' => '[biodiversidad_mis_avistamientos]',
                'icono' => 'dashicons-portfolio',
            ],
        ];
    }

    /**
     * Obtiene acciones del módulo
     *
     * @return array
     */
    public function get_actions(): array {
        return [
            'registrar_avistamiento' => [
                'name' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'description' => __('Registra un avistamiento de fauna o flora', 'flavor-chat-ia'),
                'callback' => [$this, 'action_registrar_avistamiento'],
            ],
            'buscar_especie' => [
                'name' => __('Buscar Especie', 'flavor-chat-ia'),
                'description' => __('Busca información sobre una especie local', 'flavor-chat-ia'),
                'callback' => [$this, 'action_buscar_especie'],
            ],
            'listar_proyectos' => [
                'name' => __('Ver Proyectos', 'flavor-chat-ia'),
                'description' => __('Lista los proyectos de conservación activos', 'flavor-chat-ia'),
                'callback' => [$this, 'action_listar_proyectos'],
            ],
        ];
    }

    /**
     * Acción: Registrar avistamiento
     */
    public function action_registrar_avistamiento($params) {
        return [
            'success' => true,
            'message' => __('Para registrar un avistamiento, visita la sección de Biodiversidad.', 'flavor-chat-ia'),
            'url' => home_url('/biodiversidad/registrar/'),
        ];
    }

    /**
     * Acción: Buscar especie
     */
    public function action_buscar_especie($params) {
        $termino = $params['especie'] ?? '';

        if (empty($termino)) {
            return [
                'success' => false,
                'message' => __('Indica el nombre de la especie que buscas', 'flavor-chat-ia'),
            ];
        }

        $especies = new WP_Query([
            'post_type' => 'bl_especie',
            'post_status' => 'publish',
            's' => $termino,
            'posts_per_page' => 5,
        ]);

        if (!$especies->have_posts()) {
            return [
                'success' => true,
                'message' => sprintf(__('No encontré especies con el nombre "%s"', 'flavor-chat-ia'), $termino),
                'especies' => [],
            ];
        }

        $resultados = [];
        foreach ($especies->posts as $especie) {
            $resultados[] = [
                'nombre' => $especie->post_title,
                'cientifico' => get_post_meta($especie->ID, '_bl_nombre_cientifico', true),
                'estado' => get_post_meta($especie->ID, '_bl_estado_conservacion', true),
                'url' => get_permalink($especie->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Encontré %d especie(s)', 'flavor-chat-ia'), count($resultados)),
            'especies' => $resultados,
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    public function action_listar_proyectos($params) {
        $proyectos = new WP_Query([
            'post_type' => 'bl_proyecto',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $lista = [];
        foreach ($proyectos->posts as $proyecto) {
            $tipo = get_post_meta($proyecto->ID, '_bl_tipo', true);
            $tipo_data = self::TIPOS_PROYECTO[$tipo] ?? ['nombre' => $tipo];
            $participantes = get_post_meta($proyecto->ID, '_bl_participantes', true) ?: [];

            $lista[] = [
                'titulo' => $proyecto->post_title,
                'tipo' => $tipo_data['nombre'],
                'participantes' => count($participantes),
                'url' => get_permalink($proyecto->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Hay %d proyecto(s) de conservación activos', 'flavor-chat-ia'), count($lista)),
            'proyectos' => $lista,
        ];
    }

    /**
     * Obtiene base de conocimiento
     *
     * @return string
     */
    public function get_knowledge_base(): string {
        $stats = $this->get_estadisticas();

        return sprintf(
            __("Módulo de Biodiversidad Local:\n" .
            "- %d especies catalogadas\n" .
            "- %d avistamientos registrados por la comunidad\n" .
            "- %d proyectos de conservación activos\n\n" .
            "Funcionalidades:\n" .
            "- Catálogo colaborativo de especies locales\n" .
            "- Mapa interactivo de avistamientos\n" .
            "- Sistema de ciencia ciudadana con validación comunitaria\n" .
            "- Proyectos de conservación y voluntariado\n" .
            "- Estados de conservación basados en IUCN", 'flavor-chat-ia'),
            $stats['especies_catalogadas'],
            $stats['avistamientos_total'],
            $stats['proyectos_activos']
        );
    }

    /**
     * Obtiene FAQs
     *
     * @return array
     */
    public function get_faqs(): array {
        return [
            [
                'pregunta' => __('¿Cómo registro un avistamiento de fauna o flora?', 'flavor-chat-ia'),
                'respuesta' => __('Ve a Biodiversidad > Registrar Avistamiento. Necesitas indicar la especie, ubicación y fecha. Puedes añadir fotos y descripción.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Cómo se validan los avistamientos?', 'flavor-chat-ia'),
                'respuesta' => __('Los avistamientos son validados por la comunidad. Con 3 validaciones positivas, se publican automáticamente.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Puedo proponer una especie que no está en el catálogo?', 'flavor-chat-ia'),
                'respuesta' => __('Sí, puedes proponer nuevas especies desde la sección de registro. Será revisada antes de añadirse al catálogo.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Cómo puedo participar en proyectos de conservación?', 'flavor-chat-ia'),
                'respuesta' => __('Explora los proyectos activos en Biodiversidad > Proyectos y únete a los que te interesen.', 'flavor-chat-ia'),
            ],
        ];
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
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'catalogo_especies',
            'listado' => 'catalogo_especies',
            'catalogo' => 'catalogo_especies',
            'explorar' => 'catalogo_especies',
            'buscar' => 'catalogo_especies',
            'mapa' => 'ver_mapa',
            'crear' => 'registrar_avistamiento',
            'registrar' => 'registrar_avistamiento',
            'mis_items' => 'mis_avistamientos',
            'avistamientos' => 'mis_avistamientos',
            'proyectos' => 'ver_proyectos',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'message' => __('Acción no implementada', 'flavor-chat-ia'),
        ];
    }

    private function action_catalogo_especies($params) {
        return ['success' => true, 'html' => do_shortcode('[biodiversidad_catalogo]')];
    }

    private function action_ver_mapa($params) {
        return ['success' => true, 'html' => do_shortcode('[biodiversidad_mapa]')];
    }

    private function action_mis_avistamientos($params) {
        return ['success' => true, 'html' => do_shortcode('[biodiversidad_mis_avistamientos]')];
    }

    private function action_ver_proyectos($params) {
        return ['success' => true, 'html' => do_shortcode('[biodiversidad_proyectos]')];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'biodiversidad-local',
            'title'    => __('Biodiversidad Local', 'flavor-chat-ia'),
            'subtitle' => __('Catálogo de fauna y flora de tu comunidad', 'flavor-chat-ia'),
            'icon'     => '🦋',
            'color'    => 'info', // Usa variable CSS --flavor-info del tema

            'database' => [
                'table'       => 'flavor_biodiversidad_especies',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre_comun'     => ['type' => 'text', 'label' => __('Nombre común', 'flavor-chat-ia'), 'required' => true],
                'nombre_cientifico' => ['type' => 'text', 'label' => __('Nombre científico', 'flavor-chat-ia')],
                'tipo'             => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['fauna', 'flora', 'hongo']],
                'estado_conservacion' => ['type' => 'select', 'label' => __('Estado conservación', 'flavor-chat-ia')],
                'habitat'          => ['type' => 'text', 'label' => __('Hábitat', 'flavor-chat-ia')],
                'descripcion'      => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'imagen'           => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
            ],

            'estados' => [
                'borrador'   => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '📝'],
                'pendiente'  => ['label' => __('Pendiente validación', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏳'],
                'validado'   => ['label' => __('Validado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'rechazado'  => ['label' => __('Rechazado', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                'especies_catalogadas' => ['label' => __('Especies', 'flavor-chat-ia'), 'icon' => '🦋', 'color' => 'lime'],
                'avistamientos_total'  => ['label' => __('Avistamientos', 'flavor-chat-ia'), 'icon' => '👁️', 'color' => 'blue'],
                'proyectos_activos'    => ['label' => __('Proyectos', 'flavor-chat-ia'), 'icon' => '🌱', 'color' => 'green'],
                'contribuidores'       => ['label' => __('Contribuidores', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'especie-card',
                'title_field'  => 'nombre_comun',
                'subtitle_field' => 'nombre_cientifico',
                'meta_fields'  => ['tipo', 'estado_conservacion'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'catalogo' => [
                    'label'   => __('Catálogo', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-portfolio',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'mapa' => [
                    'label'   => __('Mapa', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-location',
                    'content' => 'shortcode:biodiversidad_mapa',
                    'public'  => true,
                ],
                'avistamientos' => [
                    'label'   => __('Avistamientos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-visibility',
                    'content' => 'shortcode:biodiversidad_mis_avistamientos',
                    'public'  => true,
                ],
                'registrar' => [
                    'label'      => __('Registrar', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:biodiversidad_registrar',
                    'requires_login' => true,
                ],
                'proyectos' => [
                    'label'   => __('Proyectos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-clipboard',
                    'content' => 'shortcode:biodiversidad_proyectos',
                    'public'  => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'nombre_comun',
                'order'      => 'ASC',
                'filterable' => ['tipo', 'estado_conservacion', 'habitat'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'especies_recientes', 'avistamientos_mapa', 'proyectos_activos'],
                'actions' => [
                    'avistamiento' => ['label' => __('Registrar avistamiento', 'flavor-chat-ia'), 'icon' => '👁️', 'color' => 'lime'],
                    'especie'      => ['label' => __('Proponer especie', 'flavor-chat-ia'), 'icon' => '🦋', 'color' => 'green'],
                ],
            ],

            'features' => [
                'validacion_comunitaria' => true,
                'geolocalizacion'        => true,
                'galeria_fotos'          => true,
                'gamificacion'           => true,
                'exportar_datos'         => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-biodiversidad-local-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Biodiversidad_Local_Dashboard_Tab')) {
                Flavor_Biodiversidad_Local_Dashboard_Tab::get_instance();
            }
        }
    }
}
