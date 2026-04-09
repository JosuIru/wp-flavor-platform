<?php
/**
 * Módulo Círculos de Cuidados
 *
 * Organiza redes de apoyo mutuo para situaciones vitales:
 * - Acompañamiento a personas mayores
 * - Cuidado compartido de infancia
 * - Apoyo en enfermedad/duelo
 * - Bancos de horas de cuidado
 *
 * @package FlavorChatIA
 * @subpackage Modules\CirculosCuidados
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo Círculos de Cuidados
 */
class Flavor_Chat_Circulos_Cuidados_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Tipos de círculos de cuidado
     */
    const TIPOS_CIRCULO = [
        'mayores' => [
            'nombre' => 'Acompañamiento Mayores',
            'descripcion' => 'Visitas, compañía, ayuda con gestiones',
            'icono' => 'dashicons-groups',
            'color' => '#9b59b6',
        ],
        'infancia' => [
            'nombre' => 'Cuidado Infancia',
            'descripcion' => 'Cuidado compartido, recogidas escolares, actividades',
            'icono' => 'dashicons-heart',
            'color' => '#e91e63',
        ],
        'enfermedad' => [
            'nombre' => 'Apoyo Enfermedad',
            'descripcion' => 'Acompañamiento médico, comidas, ayuda doméstica',
            'icono' => 'dashicons-plus-alt',
            'color' => '#00bcd4',
        ],
        'duelo' => [
            'nombre' => 'Acompañamiento Duelo',
            'descripcion' => 'Presencia, escucha, apoyo emocional',
            'icono' => 'dashicons-admin-users',
            'color' => '#607d8b',
        ],
        'maternidad' => [
            'nombre' => 'Red de Maternidad',
            'descripcion' => 'Apoyo embarazo, postparto, crianza',
            'icono' => 'dashicons-admin-home',
            'color' => '#ff9800',
        ],
        'diversidad' => [
            'nombre' => 'Diversidad Funcional',
            'descripcion' => 'Apoyo a personas con necesidades especiales',
            'icono' => 'dashicons-universal-access',
            'color' => '#4caf50',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'circulos_cuidados';
        $this->name = __('Círculos de Cuidados', 'flavor-platform');
        $this->description = __('Organiza redes de apoyo mutuo para situaciones vitales.', 'flavor-platform');

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['cuidados', 'aprendizaje'];
        $this->gailu_contribuye_a = ['cohesion', 'resiliencia'];

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'tipos_habilitados' => array_keys(self::TIPOS_CIRCULO),
            'horas_minimas_compromiso' => 2,
            'notificar_necesidades_urgentes' => true,
            'permitir_anonimato' => true,
            'mostrar_en_dashboard' => true,
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
                'table'   => $wpdb->prefix . 'flavor_circulos_cuidados',
                'context' => 'normal',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();
        // Custom Post Types
        add_action('init', [$this, 'registrar_post_types']);
        add_action('init', [$this, 'registrar_taxonomias']);

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_cc_circulo', [$this, 'guardar_meta_circulo']);
        add_action('save_post_cc_necesidad', [$this, 'guardar_meta_necesidad']);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX
        add_action('wp_ajax_cc_unirse_circulo', [$this, 'ajax_unirse_circulo']);
        add_action('wp_ajax_cc_ofrecer_ayuda', [$this, 'ajax_ofrecer_ayuda']);
        add_action('wp_ajax_cc_registrar_horas', [$this, 'ajax_registrar_horas']);
        add_action('wp_ajax_cc_crear_necesidad', [$this, 'ajax_crear_necesidad']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Notificaciones
        add_action('cc_necesidad_urgente', [$this, 'notificar_necesidad_urgente'], 10, 2);

        // Cron para recordatorios
        add_action('cc_recordatorio_cuidados', [$this, 'enviar_recordatorios']);
        if (!wp_next_scheduled('cc_recordatorio_cuidados')) {
            wp_schedule_event(time(), 'daily', 'cc_recordatorio_cuidados');
        }

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Panel Unificado Admin
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $tab_file = dirname(__FILE__) . '/class-circulos-cuidados-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Circulos_Cuidados_Dashboard_Tab')) {
                Flavor_Circulos_Cuidados_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Callback de cron para recordatorios.
     *
     * Se deja como implementación segura mínima para evitar fatales
     * cuando el evento programado se ejecuta antes de definirse una
     * lógica de recordatorios más completa para el módulo.
     */
    public function enviar_recordatorios() {
        do_action('flavor_log', 'circulos_cuidados_recordatorios_placeholder');
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Listar círculos
        register_rest_route($namespace, '/circulos-cuidados', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_circulos'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener círculo
        register_rest_route($namespace, '/circulos-cuidados/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_circulo'],
            'permission_callback' => '__return_true',
        ]);

        // Necesidades abiertas
        register_rest_route($namespace, '/circulos-cuidados/necesidades', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_necesidades'],
            'permission_callback' => '__return_true',
        ]);

        // Mis cuidados
        register_rest_route($namespace, '/circulos-cuidados/mis-cuidados', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mis_cuidados'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    /**
     * Verifica si el usuario está logueado
     */
    public function check_user_logged_in(): bool {
        return is_user_logged_in();
    }

    /**
     * API: Obtener círculos
     */
    public function api_get_circulos(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'cc_circulo',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
        ];

        if ($tipo = $request->get_param('tipo')) {
            $args['meta_query'] = [['key' => '_cc_tipo', 'value' => $tipo]];
        }

        $query = new \WP_Query($args);
        $circulos = [];

        foreach ($query->posts as $post) {
            $miembros = get_post_meta($post->ID, '_cc_miembros', true) ?: [];
            $circulos[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'tipo' => get_post_meta($post->ID, '_cc_tipo', true),
                'zona' => get_post_meta($post->ID, '_cc_zona', true),
                'miembros' => count($miembros),
            ];
        }

        return new \WP_REST_Response(['circulos' => $circulos, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener círculo
     */
    public function api_get_circulo(\WP_REST_Request $request): \WP_REST_Response {
        $circulo_id = $request->get_param('id');
        $circulo = get_post($circulo_id);

        if (!$circulo || $circulo->post_type !== 'cc_circulo') {
            return new \WP_REST_Response(['error' => 'Círculo no encontrado'], 404);
        }

        $miembros = get_post_meta($circulo_id, '_cc_miembros', true) ?: [];

        return new \WP_REST_Response([
            'id' => $circulo->ID,
            'titulo' => $circulo->post_title,
            'descripcion' => $circulo->post_content,
            'tipo' => get_post_meta($circulo_id, '_cc_tipo', true),
            'zona' => get_post_meta($circulo_id, '_cc_zona', true),
            'miembros' => count($miembros),
            'max_miembros' => get_post_meta($circulo_id, '_cc_max_miembros', true),
        ]);
    }

    /**
     * API: Obtener necesidades
     */
    public function api_get_necesidades(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'cc_necesidad',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'meta_query' => [['key' => '_cc_estado', 'value' => ['abierta', 'en_proceso'], 'compare' => 'IN']],
        ];

        $query = new \WP_Query($args);
        $necesidades = [];

        foreach ($query->posts as $post) {
            $necesidades[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'urgencia' => get_post_meta($post->ID, '_cc_urgencia', true),
                'estado' => get_post_meta($post->ID, '_cc_estado', true),
                'horas_necesarias' => get_post_meta($post->ID, '_cc_horas_necesarias', true),
            ];
        }

        return new \WP_REST_Response(['necesidades' => $necesidades, 'total' => $query->found_posts]);
    }

    /**
     * API: Mis cuidados
     */
    public function api_get_mis_cuidados(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $stats = $this->get_estadisticas_usuario($user_id);

        return new \WP_REST_Response($stats);
    }

    /**
     * Configuración del admin para el panel unificado
     */
    public function get_admin_config(): array {
        return [
            'id' => 'circulos_cuidados',
            'label' => __('Círculos de Cuidados', 'flavor-platform'),
            'icon' => 'dashicons-heart',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'circulos-cuidados',
                    'titulo' => __('Círculos de Cuidados', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'cc-circulos',
                    'titulo' => __('Círculos', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_circulos'],
                ],
                [
                    'slug' => 'cc-necesidades',
                    'titulo' => __('Necesidades', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_necesidades'],
                ],
            ],
        ];
    }

    /**
     * Render: Dashboard admin
     */
    public function render_admin_dashboard(): void {
        $circulos = wp_count_posts('cc_circulo');
        $necesidades = wp_count_posts('cc_necesidad');
        ?>
        <div class="wrap flavor-admin-circulos">
            <h1><?php esc_html_e('Círculos de Cuidados', 'flavor-platform'); ?></h1>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-heart"></span>
                    <h3><?php echo esc_html($circulos->publish ?? 0); ?></h3>
                    <p><?php esc_html_e('Círculos activos', 'flavor-platform'); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-sos"></span>
                    <h3><?php echo esc_html($necesidades->publish ?? 0); ?></h3>
                    <p><?php esc_html_e('Necesidades abiertas', 'flavor-platform'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render: Listado de círculos
     */
    public function render_admin_circulos(): void {
        $circulos = get_posts(['post_type' => 'cc_circulo', 'posts_per_page' => 50, 'post_status' => 'any']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Círculos', 'flavor-platform'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=cc_circulo'); ?>" class="page-title-action"><?php esc_html_e('Añadir', 'flavor-platform'); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Nombre', 'flavor-platform'); ?></th><th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th><th><?php esc_html_e('Zona', 'flavor-platform'); ?></th><th><?php esc_html_e('Miembros', 'flavor-platform'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($circulos as $circulo): $tipo = get_post_meta($circulo->ID, '_cc_tipo', true); $miembros = get_post_meta($circulo->ID, '_cc_miembros', true) ?: []; ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($circulo->ID); ?>"><?php echo esc_html($circulo->post_title); ?></a></td>
                        <td><?php echo esc_html(self::TIPOS_CIRCULO[$tipo]['nombre'] ?? $tipo); ?></td>
                        <td><?php echo esc_html(get_post_meta($circulo->ID, '_cc_zona', true)); ?></td>
                        <td><?php echo count($miembros); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de necesidades
     */
    public function render_admin_necesidades(): void {
        $necesidades = get_posts(['post_type' => 'cc_necesidad', 'posts_per_page' => 50, 'post_status' => 'any']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Necesidades de Cuidado', 'flavor-platform'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', 'flavor-platform'); ?></th><th><?php esc_html_e('Urgencia', 'flavor-platform'); ?></th><th><?php esc_html_e('Estado', 'flavor-platform'); ?></th><th><?php esc_html_e('Horas', 'flavor-platform'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($necesidades as $nec): ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($nec->ID); ?>"><?php echo esc_html($nec->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($nec->ID, '_cc_urgencia', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($nec->ID, '_cc_estado', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($nec->ID, '_cc_horas_necesarias', true)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        $shortcodes = [
            // Shortcodes originales
            'circulos_cuidados' => 'shortcode_listado',
            'mis_cuidados' => 'shortcode_mis_cuidados',
            'necesidades_cuidados' => 'shortcode_necesidades',
            // Aliases con prefijo flavor_
            'flavor_circulos_listado' => 'shortcode_listado',
            'flavor_circulos_mis_cuidados' => 'shortcode_mis_cuidados',
            'flavor_circulos_necesidades' => 'shortcode_necesidades',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Registra Custom Post Types
     */
    public function registrar_post_types() {
        // CPT: Círculos de Cuidado
        register_post_type('cc_circulo', [
            'labels' => [
                'name' => __('Círculos de Cuidado', 'flavor-platform'),
                'singular_name' => __('Círculo', 'flavor-platform'),
                'add_new' => __('Crear Círculo', 'flavor-platform'),
                'add_new_item' => __('Crear Nuevo Círculo', 'flavor-platform'),
                'edit_item' => __('Editar Círculo', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-heart',
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'circulos-cuidados'],
        ]);

        // CPT: Necesidades de Cuidado
        register_post_type('cc_necesidad', [
            'labels' => [
                'name' => __('Necesidades de Cuidado', 'flavor-platform'),
                'singular_name' => __('Necesidad', 'flavor-platform'),
                'add_new' => __('Solicitar Ayuda', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-sos',
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'necesidades-cuidado'],
        ]);

        // CPT: Registro de Horas de Cuidado
        register_post_type('cc_registro_horas', [
            'labels' => [
                'name' => __('Registro de Horas', 'flavor-platform'),
                'singular_name' => __('Registro', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-clock',
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        register_taxonomy('cc_tipo_circulo', ['cc_circulo', 'cc_necesidad'], [
            'labels' => [
                'name' => __('Tipos de Círculo', 'flavor-platform'),
                'singular_name' => __('Tipo', 'flavor-platform'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('cc_tipo_ayuda', 'cc_necesidad', [
            'labels' => [
                'name' => __('Tipos de Ayuda', 'flavor-platform'),
                'singular_name' => __('Tipo de Ayuda', 'flavor-platform'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'cc_circulo_datos',
            __('Datos del Círculo', 'flavor-platform'),
            [$this, 'render_meta_box_circulo'],
            'cc_circulo',
            'normal',
            'high'
        );

        add_meta_box(
            'cc_necesidad_datos',
            __('Datos de la Necesidad', 'flavor-platform'),
            [$this, 'render_meta_box_necesidad'],
            'cc_necesidad',
            'normal',
            'high'
        );

        add_meta_box(
            'cc_circulo_miembros',
            __('Miembros del Círculo', 'flavor-platform'),
            [$this, 'render_meta_box_miembros'],
            'cc_circulo',
            'side',
            'default'
        );
    }

    /**
     * Renderiza meta box del círculo
     */
    public function render_meta_box_circulo($post) {
        wp_nonce_field('cc_circulo_nonce', 'cc_circulo_nonce_field');

        $tipo = get_post_meta($post->ID, '_cc_tipo', true);
        $coordinador = get_post_meta($post->ID, '_cc_coordinador', true);
        $max_miembros = get_post_meta($post->ID, '_cc_max_miembros', true) ?: 15;
        $zona = get_post_meta($post->ID, '_cc_zona', true);
        $privacidad = get_post_meta($post->ID, '_cc_privacidad', true) ?: 'publico';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cc_tipo"><?php esc_html_e('Tipo de Círculo', 'flavor-platform'); ?></label></th>
                <td>
                    <select name="cc_tipo" id="cc_tipo" class="regular-text">
                        <?php foreach (self::TIPOS_CIRCULO as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($tipo, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cc_zona"><?php esc_html_e('Zona/Barrio', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="text" name="cc_zona" id="cc_zona"
                           value="<?php echo esc_attr($zona); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="cc_max_miembros"><?php esc_html_e('Máximo miembros', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="number" name="cc_max_miembros" id="cc_max_miembros"
                           value="<?php echo esc_attr($max_miembros); ?>" min="3" max="50">
                </td>
            </tr>
            <tr>
                <th><label for="cc_privacidad"><?php esc_html_e('Privacidad', 'flavor-platform'); ?></label></th>
                <td>
                    <select name="cc_privacidad" id="cc_privacidad">
                        <option value="publico" <?php selected($privacidad, 'publico'); ?>>
                            <?php esc_html_e('Público - Cualquiera puede unirse', 'flavor-platform'); ?>
                        </option>
                        <option value="solicitud" <?php selected($privacidad, 'solicitud'); ?>>
                            <?php esc_html_e('Por solicitud - Requiere aprobación', 'flavor-platform'); ?>
                        </option>
                        <option value="invitacion" <?php selected($privacidad, 'invitacion'); ?>>
                            <?php esc_html_e('Solo invitación', 'flavor-platform'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box de necesidad
     */
    public function render_meta_box_necesidad($post) {
        wp_nonce_field('cc_necesidad_nonce', 'cc_necesidad_nonce_field');

        $urgencia = get_post_meta($post->ID, '_cc_urgencia', true) ?: 'normal';
        $fecha_inicio = get_post_meta($post->ID, '_cc_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_cc_fecha_fin', true);
        $horas_necesarias = get_post_meta($post->ID, '_cc_horas_necesarias', true);
        $anonimo = get_post_meta($post->ID, '_cc_anonimo', true);
        $estado = get_post_meta($post->ID, '_cc_estado', true) ?: 'abierta';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cc_urgencia"><?php esc_html_e('Urgencia', 'flavor-platform'); ?></label></th>
                <td>
                    <select name="cc_urgencia" id="cc_urgencia">
                        <option value="baja" <?php selected($urgencia, 'baja'); ?>>
                            <?php esc_html_e('Baja - Puede esperar', 'flavor-platform'); ?>
                        </option>
                        <option value="normal" <?php selected($urgencia, 'normal'); ?>>
                            <?php esc_html_e('Normal', 'flavor-platform'); ?>
                        </option>
                        <option value="alta" <?php selected($urgencia, 'alta'); ?>>
                            <?php esc_html_e('Alta - Próximos días', 'flavor-platform'); ?>
                        </option>
                        <option value="urgente" <?php selected($urgencia, 'urgente'); ?>>
                            <?php esc_html_e('Urgente - Hoy/Mañana', 'flavor-platform'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cc_fecha_inicio"><?php esc_html_e('Fecha inicio', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="datetime-local" name="cc_fecha_inicio" id="cc_fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="cc_fecha_fin"><?php esc_html_e('Fecha fin', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="datetime-local" name="cc_fecha_fin" id="cc_fecha_fin"
                           value="<?php echo esc_attr($fecha_fin); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="cc_horas"><?php esc_html_e('Horas necesarias', 'flavor-platform'); ?></label></th>
                <td>
                    <input type="number" name="cc_horas_necesarias" id="cc_horas"
                           value="<?php echo esc_attr($horas_necesarias); ?>" min="0.5" step="0.5">
                </td>
            </tr>
            <tr>
                <th><label for="cc_anonimo"><?php esc_html_e('Solicitud anónima', 'flavor-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="cc_anonimo" id="cc_anonimo" value="1"
                               <?php checked($anonimo, '1'); ?>>
                        <?php esc_html_e('No mostrar mi nombre públicamente', 'flavor-platform'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="cc_estado"><?php esc_html_e('Estado', 'flavor-platform'); ?></label></th>
                <td>
                    <select name="cc_estado" id="cc_estado">
                        <option value="abierta" <?php selected($estado, 'abierta'); ?>>
                            <?php esc_html_e('Abierta', 'flavor-platform'); ?>
                        </option>
                        <option value="en_proceso" <?php selected($estado, 'en_proceso'); ?>>
                            <?php esc_html_e('En proceso', 'flavor-platform'); ?>
                        </option>
                        <option value="cubierta" <?php selected($estado, 'cubierta'); ?>>
                            <?php esc_html_e('Cubierta', 'flavor-platform'); ?>
                        </option>
                        <option value="cerrada" <?php selected($estado, 'cerrada'); ?>>
                            <?php esc_html_e('Cerrada', 'flavor-platform'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box de miembros
     */
    public function render_meta_box_miembros($post) {
        $miembros = get_post_meta($post->ID, '_cc_miembros', true) ?: [];
        $coordinador = get_post_meta($post->ID, '_cc_coordinador', true);

        if (empty($miembros)) {
            echo '<p>' . esc_html__('No hay miembros aún.', 'flavor-platform') . '</p>';
            return;
        }

        echo '<ul class="cc-miembros-lista">';
        foreach ($miembros as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;

            $es_coordinador = ((int) $user_id === (int) $coordinador);
            $rol = $es_coordinador ? __('Coordinador', 'flavor-platform') : __('Miembro', 'flavor-platform');

            echo '<li>';
            echo esc_html($user->display_name);
            echo ' <small>(' . esc_html($rol) . ')</small>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p><strong>' . count($miembros) . '</strong> ' . esc_html__('miembros', 'flavor-platform') . '</p>';
    }

    /**
     * Guarda meta del círculo
     */
    public function guardar_meta_circulo($post_id) {
        if (!isset($_POST['cc_circulo_nonce_field']) ||
            !wp_verify_nonce($_POST['cc_circulo_nonce_field'], 'cc_circulo_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['tipo', 'zona', 'max_miembros', 'privacidad'];
        foreach ($campos as $campo) {
            if (isset($_POST['cc_' . $campo])) {
                update_post_meta($post_id, '_cc_' . $campo, sanitize_text_field($_POST['cc_' . $campo]));
            }
        }

        // Si es nuevo, asignar al creador como coordinador y primer miembro
        if (get_post_meta($post_id, '_cc_coordinador', true) === '') {
            $user_id = get_current_user_id();
            update_post_meta($post_id, '_cc_coordinador', $user_id);
            update_post_meta($post_id, '_cc_miembros', [$user_id]);
        }
    }

    /**
     * Guarda meta de necesidad
     */
    public function guardar_meta_necesidad($post_id) {
        if (!isset($_POST['cc_necesidad_nonce_field']) ||
            !wp_verify_nonce($_POST['cc_necesidad_nonce_field'], 'cc_necesidad_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['urgencia', 'fecha_inicio', 'fecha_fin', 'horas_necesarias', 'estado'];
        foreach ($campos as $campo) {
            if (isset($_POST['cc_' . $campo])) {
                update_post_meta($post_id, '_cc_' . $campo, sanitize_text_field($_POST['cc_' . $campo]));
            }
        }

        update_post_meta($post_id, '_cc_anonimo', isset($_POST['cc_anonimo']) ? '1' : '0');

        // Disparar acción si es urgente
        $urgencia = $_POST['cc_urgencia'] ?? 'normal';
        if ($urgencia === 'urgente') {
            do_action('cc_necesidad_urgente', $post_id, get_current_user_id());
        }
    }

    /**
     * AJAX: Unirse a un círculo
     */
    public function ajax_unirse_circulo() {
        check_ajax_referer('cc_nonce', 'nonce');

        $circulo_id = absint($_POST['circulo_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$circulo_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-platform')]);
        }

        $miembros = get_post_meta($circulo_id, '_cc_miembros', true) ?: [];
        $max_miembros = get_post_meta($circulo_id, '_cc_max_miembros', true) ?: 15;

        if (in_array($user_id, $miembros)) {
            wp_send_json_error(['message' => __('Ya eres miembro de este círculo', 'flavor-platform')]);
        }

        if (count($miembros) >= $max_miembros) {
            wp_send_json_error(['message' => __('El círculo está lleno', 'flavor-platform')]);
        }

        $miembros[] = $user_id;
        update_post_meta($circulo_id, '_cc_miembros', $miembros);

        wp_send_json_success([
            'message' => __('Te has unido al círculo', 'flavor-platform'),
            'miembros' => count($miembros),
        ]);
    }

    /**
     * AJAX: Ofrecer ayuda a una necesidad
     */
    public function ajax_ofrecer_ayuda() {
        check_ajax_referer('cc_nonce', 'nonce');

        $necesidad_id = absint($_POST['necesidad_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$necesidad_id || !$user_id || $horas <= 0) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-platform')]);
        }

        $ayudantes = get_post_meta($necesidad_id, '_cc_ayudantes', true) ?: [];
        $ayudantes[] = [
            'user_id' => $user_id,
            'horas' => $horas,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'estado' => 'pendiente',
        ];
        update_post_meta($necesidad_id, '_cc_ayudantes', $ayudantes);

        // Actualizar estado si hay suficiente ayuda
        $horas_necesarias = floatval(get_post_meta($necesidad_id, '_cc_horas_necesarias', true));
        $horas_ofrecidas = array_sum(array_column($ayudantes, 'horas'));

        if ($horas_ofrecidas >= $horas_necesarias) {
            update_post_meta($necesidad_id, '_cc_estado', 'cubierta');
        } else {
            update_post_meta($necesidad_id, '_cc_estado', 'en_proceso');
        }

        // Notificar al solicitante
        $solicitante_id = get_post_field('post_author', $necesidad_id);
        $this->notificar_oferta_ayuda($necesidad_id, $user_id, $solicitante_id);

        wp_send_json_success([
            'message' => __('Gracias por ofrecer tu ayuda', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Registrar horas de cuidado realizadas
     */
    public function ajax_registrar_horas() {
        check_ajax_referer('cc_nonce', 'nonce');

        $necesidad_id = absint($_POST['necesidad_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $user_id = get_current_user_id();

        if (!$necesidad_id || !$user_id || $horas <= 0) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-platform')]);
        }

        // Crear registro de horas
        $registro_id = wp_insert_post([
            'post_type' => 'cc_registro_horas',
            'post_title' => sprintf(
                __('%s - %s horas', 'flavor-platform'),
                get_userdata($user_id)->display_name,
                $horas
            ),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ], true);

        if (is_wp_error($registro_id) || empty($registro_id)) {
            $error = is_wp_error($registro_id)
                ? $registro_id->get_error_message()
                : __('No se pudo registrar las horas.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($registro_id, '_cc_necesidad_id', $necesidad_id);
        update_post_meta($registro_id, '_cc_horas', $horas);
        update_post_meta($registro_id, '_cc_descripcion', $descripcion);
        update_post_meta($registro_id, '_cc_fecha', current_time('mysql'));

        // Actualizar total de horas del usuario
        $horas_totales = floatval(get_user_meta($user_id, '_cc_horas_totales', true));
        update_user_meta($user_id, '_cc_horas_totales', $horas_totales + $horas);

        wp_send_json_success([
            'message' => __('Horas registradas correctamente', 'flavor-platform'),
            'horas_totales' => $horas_totales + $horas,
        ]);
    }

    /**
     * Notifica necesidad urgente
     */
    public function notificar_necesidad_urgente($necesidad_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $necesidad = get_post($necesidad_id);
        $nc = Flavor_Notification_Center::get_instance();

        // Obtener miembros del círculo relacionado
        $circulo_id = get_post_meta($necesidad_id, '_cc_circulo_id', true);
        if ($circulo_id) {
            $miembros = get_post_meta($circulo_id, '_cc_miembros', true) ?: [];

            foreach ($miembros as $miembro_id) {
                if ((int) $miembro_id === $user_id) continue;

                $nc->send(
                    $miembro_id,
                    __('Necesidad urgente de cuidados', 'flavor-platform'),
                    sprintf(__('Se necesita ayuda urgente: %s', 'flavor-platform'), $necesidad->post_title),
                    [
                        'module_id' => $this->id,
                        'type' => 'warning',
                        'link' => get_permalink($necesidad_id),
                    ]
                );
            }
        }
    }

    /**
     * Notifica oferta de ayuda
     */
    private function notificar_oferta_ayuda($necesidad_id, $ayudante_id, $solicitante_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $ayudante = get_userdata($ayudante_id);
        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $solicitante_id,
            __('Alguien quiere ayudarte', 'flavor-platform'),
            sprintf(__('%s se ha ofrecido a ayudarte', 'flavor-platform'), $ayudante->display_name),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => get_permalink($necesidad_id),
            ]
        );
    }

    /**
     * Shortcode: Listado de círculos
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/listado-circulos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis cuidados
     */
    public function shortcode_mis_cuidados($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tus cuidados.', 'flavor-platform') . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mis-cuidados.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Necesidades de cuidados
     */
    public function shortcode_necesidades($atts) {
        $atts = shortcode_atts([
            'estado' => 'abierta',
            'urgencia' => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/necesidades.php';
        return ob_get_clean();
    }

    /**
     * Registra widget de dashboard
     */
    public function register_dashboard_widget($registry) {
        $settings = $this->get_settings();
        if (empty($settings['mostrar_en_dashboard'])) {
            return;
        }

        $widget_path = dirname(__FILE__) . '/class-circulos-cuidados-widget.php';
        if (!class_exists('Flavor_Circulos_Cuidados_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Circulos_Cuidados_Widget')) {
            $registry->register(new Flavor_Circulos_Cuidados_Widget($this));
        }
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        if (is_singular(['cc_circulo', 'cc_necesidad'])) {
            return true;
        }

        global $post;
        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'circulos_cuidados',
            'mis_cuidados',
            'necesidades_cuidados',
            'flavor_circulos_listado',
            'flavor_circulos_mis_cuidados',
            'flavor_circulos_necesidades',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-circulos-cuidados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/css/circulos-cuidados.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-circulos-cuidados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/circulos-cuidados/assets/js/circulos-cuidados.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-circulos-cuidados', 'ccData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cc_nonce'),
            'i18n' => [
                'confirmUnirse' => __('¿Quieres unirte a este círculo?', 'flavor-platform'),
                'gracias' => __('¡Gracias por cuidar!', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Obtiene estadísticas del usuario
     */
    public function get_estadisticas_usuario($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        // Círculos donde participa
        $circulos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_cc_miembros'
               AND pm.meta_value LIKE %s
               AND p.post_type = 'cc_circulo'
               AND p.post_status = 'publish'",
            '%"' . $user_id . '"%'
        ));

        // Horas totales de cuidado
        $horas = floatval(get_user_meta($user_id, '_cc_horas_totales', true));

        // Necesidades ayudadas
        $ayudadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_cc_ayudantes'
               AND pm.meta_value LIKE %s
               AND p.post_type = 'cc_necesidad'",
            '%user_id";i:' . $user_id . '%'
        ));

        return [
            'circulos' => (int) $circulos,
            'horas_cuidado' => $horas,
            'necesidades_ayudadas' => (int) $ayudadas,
        ];
    }

    /**
     * Valoración para el Sello de Conciencia
     */
    public function get_consciousness_valuation() {
        return [
            'nombre' => 'Círculos de Cuidados',
            'puntuacion' => 96,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Reconoce el valor del trabajo de cuidados, organiza el apoyo mutuo, distribuye la responsabilidad colectivamente y dignifica tanto a quien cuida como a quien es cuidado.',
            'categoria' => 'cuidados',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_circulos' => [
                'description' => 'Ver círculos de cuidados disponibles',
                'params' => ['tipo'],
            ],
            'ver_necesidades' => [
                'description' => 'Ver necesidades de cuidado abiertas',
                'params' => ['urgencia'],
            ],
            'mis_cuidados' => [
                'description' => 'Ver mis estadísticas de cuidados',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Círculos de Cuidados - Guía de Uso**

Los Círculos de Cuidados son redes de apoyo mutuo para situaciones vitales.

**Tipos de círculos:**
- Acompañamiento a mayores
- Cuidado compartido de infancia
- Apoyo en enfermedad
- Acompañamiento en duelo
- Red de maternidad
- Diversidad funcional

**Cómo funciona:**
1. Únete a un círculo de tu zona o interés
2. Cuando necesites ayuda, publica una necesidad
3. Otros miembros se ofrecerán a ayudar
4. Registra las horas de cuidado que das
5. Cuando otros necesiten, ayuda tú también

**Valores:**
- El cuidado es responsabilidad colectiva
- Todas las horas de cuidado valen igual
- La ayuda se da sin esperar retorno inmediato
- Se respeta la dignidad de quien cuida y quien es cuidado
KNOWLEDGE;
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
            'listar' => 'ver_circulos',
            'listado' => 'ver_circulos',
            'circulos' => 'ver_circulos',
            'necesidades' => 'ver_necesidades',
            'crear' => 'unirse_circulo',
            'unirse' => 'unirse_circulo',
            'mis_items' => 'ver_mis_cuidados',
            'mis-circulos' => 'ver_mis_cuidados',
            'registrar' => 'registrar_cuidado',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'message' => __('Acción no implementada', 'flavor-platform'),
        ];
    }

    private function action_ver_circulos($params) {
        return ['success' => true, 'html' => do_shortcode('[circulos_cuidados]')];
    }

    private function action_ver_necesidades($params) {
        return ['success' => true, 'html' => do_shortcode('[necesidades_cuidados]')];
    }

    private function action_unirse_circulo($params) {
        return ['success' => true, 'html' => do_shortcode('[circulos_cuidados]')];
    }

    private function action_ver_mis_cuidados($params) {
        return ['success' => true, 'html' => do_shortcode('[mis_cuidados]')];
    }

    private function action_registrar_cuidado($params) {
        return ['success' => true, 'html' => do_shortcode('[mis_cuidados]')];
    }

    private function resolve_contextual_circulo(): ?array {
        $circulo_id = absint($_GET['circulo_id'] ?? $_GET['circulo'] ?? $_GET['id'] ?? 0);
        if (!$circulo_id) {
            return null;
        }

        $circulo = get_post($circulo_id);
        if (!$circulo || $circulo->post_type !== 'cc_circulo') {
            return null;
        }

        return [
            'id'     => (int) $circulo->ID,
            'nombre' => get_the_title($circulo),
        ];
    }

    public function render_tab_foro($usuario_id): string {
        $circulo = $this->resolve_contextual_circulo();
        if (!$circulo) {
            return '<p>' . esc_html__('Selecciona un círculo para ver su foro.', 'flavor-platform') . '</p>';
        }

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Foro del círculo', 'flavor-platform') . '</h3><p>' . esc_html($circulo['nombre']) . '</p></div>'
            . do_shortcode('[flavor_foros_integrado entidad="circulo" entidad_id="' . absint($circulo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_chat($usuario_id): string {
        if (!$usuario_id) {
            return '<p>' . esc_html__('Inicia sesión para acceder al chat del círculo.', 'flavor-platform') . '</p>';
        }

        $circulo = $this->resolve_contextual_circulo();
        if (!$circulo) {
            return '<p>' . esc_html__('Selecciona un círculo para ver su chat.', 'flavor-platform') . '</p>';
        }

        $cta = home_url('/mi-portal/chat-grupos/mensajes/?circulo_id=' . absint($circulo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Chat del círculo', 'flavor-platform') . '</h3><p>' . esc_html($circulo['nombre']) . '</p>'
            . '<p><a class="button button-primary" href="' . esc_url($cta) . '">' . esc_html__('Abrir chat completo', 'flavor-platform') . '</a></p></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="circulo" entidad_id="' . absint($circulo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_multimedia($usuario_id): string {
        $circulo = $this->resolve_contextual_circulo();
        if (!$circulo) {
            return '<p>' . esc_html__('Selecciona un círculo para ver sus archivos.', 'flavor-platform') . '</p>';
        }

        $cta = home_url('/mi-portal/multimedia/subir/?circulo_id=' . absint($circulo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Archivos del círculo', 'flavor-platform') . '</h3><p>' . esc_html($circulo['nombre']) . '</p>'
            . '<p><a class="button" href="' . esc_url($cta) . '">' . esc_html__('Subir archivo', 'flavor-platform') . '</a></p></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="circulo" entidad_id="' . absint($circulo['id']) . '"]')
            . '</div>';
    }

    public function render_tab_red_social($usuario_id): string {
        if (!$usuario_id) {
            return '<p>' . esc_html__('Inicia sesión para ver la actividad del círculo.', 'flavor-platform') . '</p>';
        }

        $circulo = $this->resolve_contextual_circulo();
        if (!$circulo) {
            return '<p>' . esc_html__('Selecciona un círculo para ver su actividad social.', 'flavor-platform') . '</p>';
        }

        $cta = home_url('/mi-portal/red-social/crear/?circulo_id=' . absint($circulo['id']));

        return '<div class="flavor-contextual-block">'
            . '<div class="flavor-contextual-header"><h3>' . esc_html__('Actividad del círculo', 'flavor-platform') . '</h3><p>' . esc_html($circulo['nombre']) . '</p>'
            . '<p><a class="button" href="' . esc_url($cta) . '">' . esc_html__('Publicar', 'flavor-platform') . '</a></p></div>'
            . do_shortcode('[flavor_social_feed entidad="circulo" entidad_id="' . absint($circulo['id']) . '"]')
            . '</div>';
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'circulos-cuidados',
            'title'    => __('Círculos de Cuidados', 'flavor-platform'),
            'subtitle' => __('Redes de apoyo mutuo para el cuidado colectivo', 'flavor-platform'),
            'icon'     => '💜',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_circulos_cuidados',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'       => ['type' => 'text', 'label' => __('Nombre del círculo', 'flavor-platform'), 'required' => true],
                'tipo'         => ['type' => 'select', 'label' => __('Tipo', 'flavor-platform'), 'options' => ['mayores', 'infancia', 'enfermedad', 'duelo', 'maternidad', 'diversidad_funcional']],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'zona'         => ['type' => 'text', 'label' => __('Zona', 'flavor-platform')],
                'max_miembros' => ['type' => 'number', 'label' => __('Máximo miembros', 'flavor-platform')],
            ],

            'estados' => [
                'activo'   => ['label' => __('Activo', 'flavor-platform'), 'color' => 'green', 'icon' => '🟢'],
                'pausado'  => ['label' => __('Pausado', 'flavor-platform'), 'color' => 'yellow', 'icon' => '⏸️'],
                'cerrado'  => ['label' => __('Cerrado', 'flavor-platform'), 'color' => 'gray', 'icon' => '🔒'],
            ],

            'stats' => [
                'circulos_activos' => ['label' => __('Círculos activos', 'flavor-platform'), 'icon' => '💜', 'color' => 'fuchsia'],
                'cuidadores'       => ['label' => __('Cuidadores', 'flavor-platform'), 'icon' => '👥', 'color' => 'blue'],
                'horas_cuidado'    => ['label' => __('Horas de cuidado', 'flavor-platform'), 'icon' => '⏱️', 'color' => 'purple'],
                'necesidades_mes'  => ['label' => __('Necesidades/mes', 'flavor-platform'), 'icon' => '❤️', 'color' => 'rose'],
            ],

            'card' => [
                'template'     => 'circulo-card',
                'title_field'  => 'nombre',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['zona', 'miembros_count'],
                'show_estado'  => true,
            ],

            'tabs' => [
                'circulos' => [
                    'label'   => __('Círculos', 'flavor-platform'),
                    'icon'    => 'dashicons-heart',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'necesidades' => [
                    'label'   => __('Necesidades', 'flavor-platform'),
                    'icon'    => 'dashicons-sos',
                    'content' => 'shortcode:necesidades_cuidados',
                    'public'  => true,
                ],
                'unirse' => [
                    'label'      => __('Unirse', 'flavor-platform'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:circulos_cuidados',
                    'requires_login' => true,
                ],
                'mis-circulos' => [
                    'label'      => __('Mis círculos', 'flavor-platform'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:mis_cuidados',
                    'requires_login' => true,
                ],
                'foro' => [
                    'label'          => __('Foro', 'flavor-platform'),
                    'icon'           => 'dashicons-format-chat',
                    'content'        => 'callback:render_tab_foro',
                    'requires_login' => true,
                ],
                'chat' => [
                    'label'          => __('Chat', 'flavor-platform'),
                    'icon'           => 'dashicons-format-status',
                    'content'        => 'callback:render_tab_chat',
                    'requires_login' => true,
                ],
                'multimedia' => [
                    'label'   => __('Multimedia', 'flavor-platform'),
                    'icon'    => 'dashicons-format-gallery',
                    'content' => 'callback:render_tab_multimedia',
                ],
                'red-social' => [
                    'label'          => __('Red social', 'flavor-platform'),
                    'icon'           => 'dashicons-share',
                    'content'        => 'callback:render_tab_red_social',
                    'requires_login' => true,
                ],
                'registrar-cuidado' => [
                    'label'      => __('Registrar cuidado', 'flavor-platform'),
                    'icon'       => 'dashicons-edit',
                    'content'    => 'shortcode:mis_cuidados',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 9,
                'order_by'   => 'nombre',
                'order'      => 'ASC',
                'filterable' => ['tipo', 'zona'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'necesidades_urgentes', 'mis_compromisos', 'calendario_cuidados'],
                'actions' => [
                    'necesidad' => ['label' => __('Publicar necesidad', 'flavor-platform'), 'icon' => '❤️', 'color' => 'fuchsia'],
                    'ofrecer'   => ['label' => __('Ofrecer cuidado', 'flavor-platform'), 'icon' => '🤗', 'color' => 'purple'],
                ],
            ],

            'features' => [
                'matching'      => true,
                'calendario'    => true,
                'chat_circulo'  => true,
                'banco_horas'   => true,
                'notificaciones' => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-circulos-cuidados-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Circulos_Cuidados_Dashboard_Tab')) {
                Flavor_Circulos_Cuidados_Dashboard_Tab::get_instance();
            }
        }
    }
}
