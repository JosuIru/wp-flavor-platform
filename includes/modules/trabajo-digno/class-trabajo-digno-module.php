<?php
/**
 * Módulo: Trabajo Digno
 *
 * Bolsa de empleo ético, emprendimiento local, formación profesional
 * y promoción de condiciones laborales justas.
 *
 * @package FlavorPlatform
 * @subpackage Modules\TrabajoDigno
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Trabajo_Digno_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Tipos de oferta laboral
     */
    const TIPOS_OFERTA = [
        'empleo' => [
            'nombre' => 'Empleo',
            'icono' => 'dashicons-businessman',
            'color' => '#3b82f6',
        ],
        'cooperativa' => [
            'nombre' => 'Cooperativa',
            'icono' => 'dashicons-groups',
            'color' => '#22c55e',
        ],
        'autonomo' => [
            'nombre' => 'Autónomo/Freelance',
            'icono' => 'dashicons-portfolio',
            'color' => '#f59e0b',
        ],
        'practicas' => [
            'nombre' => 'Prácticas',
            'icono' => 'dashicons-welcome-learn-more',
            'color' => '#8b5cf6',
        ],
        'voluntariado' => [
            'nombre' => 'Voluntariado',
            'icono' => 'dashicons-heart',
            'color' => '#ec4899',
        ],
    ];

    /**
     * Sectores de actividad
     */
    const SECTORES = [
        'agroecologia' => ['nombre' => 'Agroecología', 'icono' => 'dashicons-carrot'],
        'artesania' => ['nombre' => 'Artesanía', 'icono' => 'dashicons-hammer'],
        'comercio_justo' => ['nombre' => 'Comercio Justo', 'icono' => 'dashicons-cart'],
        'construccion_sostenible' => ['nombre' => 'Construcción Sostenible', 'icono' => 'dashicons-building'],
        'cuidados' => ['nombre' => 'Cuidados', 'icono' => 'dashicons-heart'],
        'educacion' => ['nombre' => 'Educación', 'icono' => 'dashicons-welcome-learn-more'],
        'energia_renovable' => ['nombre' => 'Energías Renovables', 'icono' => 'dashicons-lightbulb'],
        'hosteleria' => ['nombre' => 'Hostelería', 'icono' => 'dashicons-food'],
        'tecnologia' => ['nombre' => 'Tecnología', 'icono' => 'dashicons-laptop'],
        'cultura' => ['nombre' => 'Cultura y Arte', 'icono' => 'dashicons-art'],
        'salud' => ['nombre' => 'Salud', 'icono' => 'dashicons-plus-alt'],
        'transporte' => ['nombre' => 'Transporte Sostenible', 'icono' => 'dashicons-car'],
        'reciclaje' => ['nombre' => 'Reciclaje/Economía Circular', 'icono' => 'dashicons-update'],
        'servicios' => ['nombre' => 'Servicios Locales', 'icono' => 'dashicons-admin-tools'],
    ];

    /**
     * Jornadas laborales
     */
    const JORNADAS = [
        'completa' => 'Jornada completa',
        'parcial' => 'Media jornada',
        'flexible' => 'Horario flexible',
        'remoto' => 'Trabajo remoto',
        'hibrido' => 'Híbrido',
        'temporal' => 'Temporal/Por proyecto',
    ];

    /**
     * Criterios de trabajo digno (OIT + economía solidaria)
     */
    const CRITERIOS_DIGNIDAD = [
        'salario_justo' => [
            'nombre' => 'Salario Justo',
            'descripcion' => 'Remuneración suficiente para una vida digna',
            'icono' => 'dashicons-money-alt',
        ],
        'seguridad_social' => [
            'nombre' => 'Seguridad Social',
            'descripcion' => 'Cobertura de protección social',
            'icono' => 'dashicons-shield',
        ],
        'conciliacion' => [
            'nombre' => 'Conciliación',
            'descripcion' => 'Equilibrio vida laboral y personal',
            'icono' => 'dashicons-clock',
        ],
        'igualdad' => [
            'nombre' => 'Igualdad',
            'descripcion' => 'Sin discriminación de ningún tipo',
            'icono' => 'dashicons-groups',
        ],
        'formacion' => [
            'nombre' => 'Formación Continua',
            'descripcion' => 'Desarrollo profesional permanente',
            'icono' => 'dashicons-welcome-learn-more',
        ],
        'participacion' => [
            'nombre' => 'Participación',
            'descripcion' => 'Voz en las decisiones de la organización',
            'icono' => 'dashicons-megaphone',
        ],
        'sostenibilidad' => [
            'nombre' => 'Sostenibilidad',
            'descripcion' => 'Impacto ambiental responsable',
            'icono' => 'dashicons-admin-site-alt3',
        ],
        'impacto_local' => [
            'nombre' => 'Impacto Local',
            'descripcion' => 'Contribución a la comunidad',
            'icono' => 'dashicons-location-alt',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'trabajo_digno';
        $this->name = __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Bolsa de empleo ético, emprendimiento local y promoción del trabajo digno.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->icon = 'dashicons-businessman';
        $this->category = 'economia';
        $this->visibility = 'registered';
        $this->version = '1.0.0';

        parent::__construct();
    }

    /**
     * Inicialización del módulo
     * Se ejecuta cuando el módulo está activo
     */
    public function init() {
        $this->setup_module();
    }

    /**
     * Obtiene la valoración de conciencia del módulo
     *
     * @return array
     */
    public function get_consciousness_valuation(): array {
        return [
            'puntuacion_total' => 85,
            'premisas' => [
                'conciencia_fundamental' => [
                    'puntuacion' => 18,
                    'descripcion' => __('Reconoce la dignidad inherente del trabajador y su derecho a condiciones justas y respetuosas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'abundancia_organizable' => [
                    'puntuacion' => 17,
                    'descripcion' => __('Organiza las oportunidades laborales locales como recurso comunitario compartido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'interdependencia_radical' => [
                    'puntuacion' => 17,
                    'descripcion' => __('Conecta empleadores y trabajadores en relaciones de mutuo beneficio y corresponsabilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'madurez_ciclica' => [
                    'puntuacion' => 16,
                    'descripcion' => __('Respeta los ritmos de vida, la conciliación familiar y el derecho al descanso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'valor_intrinseco' => [
                    'puntuacion' => 17,
                    'descripcion' => __('Valora el trabajo por su aporte social y no solo por su productividad económica.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            'fortalezas' => [
                __('Criterios explícitos de trabajo digno basados en OIT', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Promoción de economía cooperativa y solidaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Conexión empleo-formación-emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'areas_mejora' => [
                __('Incorporar métricas de impacto social del empleo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Desarrollar sistema de verificación de condiciones laborales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * Configura el módulo
     */
    protected function setup_module() {
        // Registrar CPT y taxonomías en el hook 'init' de WordPress
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
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $tab_file = dirname(__FILE__) . '/class-trabajo-digno-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Trabajo_Digno_Dashboard_Tab')) {
                Flavor_Trabajo_Digno_Dashboard_Tab::get_instance();
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

        $widget_path = dirname(__FILE__) . '/class-trabajo-digno-widget.php';
        if (!class_exists('Flavor_Trabajo_Digno_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Trabajo_Digno_Widget')) {
            $registry->register(new Flavor_Trabajo_Digno_Widget($this));
        }
    }

    /**
     * Registra todos los CPTs y taxonomías
     */
    public function register_all_cpts() {
        $this->register_cpt_oferta();
        $this->register_cpt_perfil();
        $this->register_cpt_formacion();
        $this->register_cpt_emprendimiento();
        $this->register_taxonomies();
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Listar ofertas
        register_rest_route($namespace, '/trabajo-digno/ofertas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_ofertas'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener oferta
        register_rest_route($namespace, '/trabajo-digno/ofertas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_oferta'],
            'permission_callback' => '__return_true',
        ]);

        // Listar formaciones
        register_rest_route($namespace, '/trabajo-digno/formacion', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_formaciones'],
            'permission_callback' => '__return_true',
        ]);

        // Listar emprendimientos
        register_rest_route($namespace, '/trabajo-digno/emprendimientos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_emprendimientos'],
            'permission_callback' => '__return_true',
        ]);

        // Mi perfil
        register_rest_route($namespace, '/trabajo-digno/mi-perfil', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mi_perfil'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mis postulaciones
        register_rest_route($namespace, '/trabajo-digno/mis-postulaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mis_postulaciones'],
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
     * API: Obtener ofertas
     */
    public function api_get_ofertas(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'td_oferta',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
        ];

        if ($tipo = $request->get_param('tipo')) {
            $args['meta_query'][] = ['key' => '_td_tipo', 'value' => $tipo];
        }
        if ($sector = $request->get_param('sector')) {
            $args['tax_query'] = [['taxonomy' => 'td_sector', 'field' => 'slug', 'terms' => $sector]];
        }

        $query = new \WP_Query($args);
        $ofertas = [];

        foreach ($query->posts as $post) {
            $tipo = get_post_meta($post->ID, '_td_tipo', true);
            $ofertas[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'tipo' => self::TIPOS_OFERTA[$tipo]['nombre'] ?? $tipo,
                'jornada' => self::JORNADAS[get_post_meta($post->ID, '_td_jornada', true)] ?? '',
                'ubicacion' => get_post_meta($post->ID, '_td_ubicacion', true),
                'indice_dignidad' => $this->calcular_indice_dignidad($post->ID),
            ];
        }

        return new \WP_REST_Response(['ofertas' => $ofertas, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener oferta
     */
    public function api_get_oferta(\WP_REST_Request $request): \WP_REST_Response {
        $oferta_id = $request->get_param('id');
        $oferta = get_post($oferta_id);

        if (!$oferta || $oferta->post_type !== 'td_oferta') {
            return new \WP_REST_Response(['error' => 'Oferta no encontrada'], 404);
        }

        $tipo = get_post_meta($oferta_id, '_td_tipo', true);
        $criterios = get_post_meta($oferta_id, '_td_criterios_dignidad', true) ?: [];

        return new \WP_REST_Response([
            'id' => $oferta->ID,
            'titulo' => $oferta->post_title,
            'descripcion' => $oferta->post_content,
            'tipo' => self::TIPOS_OFERTA[$tipo] ?? [],
            'jornada' => get_post_meta($oferta_id, '_td_jornada', true),
            'ubicacion' => get_post_meta($oferta_id, '_td_ubicacion', true),
            'salario' => get_post_meta($oferta_id, '_td_salario', true),
            'criterios_dignidad' => $criterios,
            'indice_dignidad' => $this->calcular_indice_dignidad($oferta_id),
        ]);
    }

    /**
     * API: Obtener formaciones
     */
    public function api_get_formaciones(\WP_REST_Request $request): \WP_REST_Response {
        $query = new \WP_Query([
            'post_type' => 'td_formacion',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
        ]);

        $formaciones = [];
        foreach ($query->posts as $post) {
            $inscritos = get_post_meta($post->ID, '_td_inscritos', true) ?: [];
            $formaciones[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'plazas' => get_post_meta($post->ID, '_td_plazas', true),
                'inscritos' => count($inscritos),
                'imagen' => get_the_post_thumbnail_url($post->ID, 'medium'),
            ];
        }

        return new \WP_REST_Response(['formaciones' => $formaciones, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener emprendimientos
     */
    public function api_get_emprendimientos(\WP_REST_Request $request): \WP_REST_Response {
        $query = new \WP_Query([
            'post_type' => 'td_emprendimiento',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
        ]);

        $emprendimientos = [];
        foreach ($query->posts as $post) {
            $emprendimientos[] = [
                'id' => $post->ID,
                'nombre' => $post->post_title,
                'tipo_organizacion' => get_post_meta($post->ID, '_td_tipo_organizacion', true),
                'imagen' => get_the_post_thumbnail_url($post->ID, 'medium'),
            ];
        }

        return new \WP_REST_Response(['emprendimientos' => $emprendimientos, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener mi perfil
     */
    public function api_get_mi_perfil(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $perfil = get_posts([
            'post_type' => 'td_perfil',
            'author' => $user_id,
            'posts_per_page' => 1,
        ]);

        if (empty($perfil)) {
            return new \WP_REST_Response(['perfil' => null, 'message' => 'No tienes perfil profesional']);
        }

        $perfil = $perfil[0];
        return new \WP_REST_Response([
            'id' => $perfil->ID,
            'titulo' => $perfil->post_title,
            'descripcion' => $perfil->post_content,
            'experiencia' => get_post_meta($perfil->ID, '_td_experiencia', true),
            'formacion' => get_post_meta($perfil->ID, '_td_formacion', true),
            'disponibilidad' => get_post_meta($perfil->ID, '_td_disponibilidad', true),
        ]);
    }

    /**
     * API: Obtener mis postulaciones
     */
    public function api_get_mis_postulaciones(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $ofertas = get_posts([
            'post_type' => 'td_oferta',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [['key' => '_td_postulaciones', 'compare' => 'EXISTS']],
        ]);

        $mis_postulaciones = [];
        foreach ($ofertas as $oferta) {
            $postulaciones = get_post_meta($oferta->ID, '_td_postulaciones', true) ?: [];
            foreach ($postulaciones as $p) {
                if ($p['user_id'] == $user_id) {
                    $mis_postulaciones[] = [
                        'oferta_id' => $oferta->ID,
                        'oferta_titulo' => $oferta->post_title,
                        'fecha' => $p['fecha'],
                        'estado' => $p['estado'],
                    ];
                    break;
                }
            }
        }

        return new \WP_REST_Response(['postulaciones' => $mis_postulaciones]);
    }

    /**
     * Configuración del admin para el panel unificado
     */
    public function get_admin_config(): array {
        return [
            'id' => 'trabajo_digno',
            'label' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-businessman',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'trabajo-digno',
                    'titulo' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'td-ofertas',
                    'titulo' => __('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_ofertas'],
                ],
                [
                    'slug' => 'td-formacion',
                    'titulo' => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_formacion'],
                ],
                [
                    'slug' => 'td-emprendimientos',
                    'titulo' => __('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_emprendimientos'],
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
        <div class="wrap flavor-admin-trabajo">
            <h1><?php esc_html_e('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-businessman"></span>
                    <h3><?php echo esc_html($stats['ofertas_activas']); ?></h3>
                    <p><?php esc_html_e('Ofertas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <h3><?php echo esc_html($stats['formaciones_disponibles']); ?></h3>
                    <p><?php esc_html_e('Formaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-store"></span>
                    <h3><?php echo esc_html($stats['emprendimientos_locales']); ?></h3>
                    <p><?php esc_html_e('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render: Listado de ofertas
     */
    public function render_admin_ofertas(): void {
        $ofertas = get_posts(['post_type' => 'td_oferta', 'posts_per_page' => 50, 'post_status' => 'any']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ofertas de Trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=td_oferta'); ?>" class="page-title-action"><?php esc_html_e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Dignidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th></tr></thead>
                <tbody>
                <?php foreach ($ofertas as $oferta): $tipo = get_post_meta($oferta->ID, '_td_tipo', true); ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($oferta->ID); ?>"><?php echo esc_html($oferta->post_title); ?></a></td>
                        <td><?php echo esc_html(self::TIPOS_OFERTA[$tipo]['nombre'] ?? $tipo); ?></td>
                        <td><?php echo esc_html($this->calcular_indice_dignidad($oferta->ID)); ?>%</td>
                        <td><?php echo esc_html($oferta->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de formación
     */
    public function render_admin_formacion(): void {
        $formaciones = get_posts(['post_type' => 'td_formacion', 'posts_per_page' => 50, 'post_status' => 'any']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=td_formacion'); ?>" class="page-title-action"><?php esc_html_e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th></tr></thead>
                <tbody>
                <?php foreach ($formaciones as $form): $inscritos = get_post_meta($form->ID, '_td_inscritos', true) ?: []; ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($form->ID); ?>"><?php echo esc_html($form->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($form->ID, '_td_plazas', true) ?: '∞'); ?></td>
                        <td><?php echo count($inscritos); ?></td>
                        <td><?php echo esc_html($form->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render: Listado de emprendimientos
     */
    public function render_admin_emprendimientos(): void {
        $emprendimientos = get_posts(['post_type' => 'td_emprendimiento', 'posts_per_page' => 50, 'post_status' => 'any']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=td_emprendimiento'); ?>" class="page-title-action"><?php esc_html_e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Sector', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th></tr></thead>
                <tbody>
                <?php foreach ($emprendimientos as $emp): $sectores = wp_get_object_terms($emp->ID, 'td_sector', ['fields' => 'names']); ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($emp->ID); ?>"><?php echo esc_html($emp->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($emp->ID, '_td_tipo_organizacion', true)); ?></td>
                        <td><?php echo esc_html(implode(', ', $sectores)); ?></td>
                        <td><?php echo esc_html($emp->post_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Registra CPT: Oferta de Trabajo
     */
    private function register_cpt_oferta() {
        register_post_type('td_oferta', [
            'labels' => [
                'name' => __('Ofertas de Trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Publicar Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new_item' => __('Publicar Nueva Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'edit_item' => __('Editar Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'custom-fields'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'trabajo-digno/ofertas'],
            'menu_icon' => 'dashicons-businessman',
        ]);
    }

    /**
     * Registra CPT: Perfil Profesional
     */
    private function register_cpt_perfil() {
        register_post_type('td_perfil', [
            'labels' => [
                'name' => __('Perfiles Profesionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author'],
        ]);
    }

    /**
     * Registra CPT: Formación
     */
    private function register_cpt_formacion() {
        register_post_type('td_formacion', [
            'labels' => [
                'name' => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Curso/Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Añadir Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'trabajo-digno/formacion'],
        ]);
    }

    /**
     * Registra CPT: Emprendimiento
     */
    private function register_cpt_emprendimiento() {
        register_post_type('td_emprendimiento', [
            'labels' => [
                'name' => __('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Registrar Emprendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'trabajo-digno/emprendimientos'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    private function register_taxonomies() {
        register_taxonomy('td_sector', ['td_oferta', 'td_emprendimiento', 'td_formacion'], [
            'labels' => [
                'name' => __('Sectores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Sector', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'trabajo-digno/sector'],
        ]);

        register_taxonomy('td_habilidad', ['td_oferta', 'td_perfil'], [
            'labels' => [
                'name' => __('Habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Habilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'hierarchical' => false,
            'show_admin_column' => true,
        ]);
    }

    /**
     * Registra manejadores AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_td_publicar_oferta', [$this, 'ajax_publicar_oferta']);
        add_action('wp_ajax_td_postular', [$this, 'ajax_postular']);
        add_action('wp_ajax_td_guardar_perfil', [$this, 'ajax_guardar_perfil']);
        add_action('wp_ajax_td_registrar_emprendimiento', [$this, 'ajax_registrar_emprendimiento']);
        add_action('wp_ajax_td_inscribir_formacion', [$this, 'ajax_inscribir_formacion']);
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('trabajo_digno_ofertas', [$this, 'shortcode_ofertas']);
        add_shortcode('trabajo_digno_formacion', [$this, 'shortcode_formacion']);
        add_shortcode('trabajo_digno_emprendimientos', [$this, 'shortcode_emprendimientos']);
        add_shortcode('trabajo_digno_mi_perfil', [$this, 'shortcode_mi_perfil']);
        add_shortcode('trabajo_digno_publicar', [$this, 'shortcode_publicar']);
    }

    /**
     * Encola scripts y estilos
     */
    public function enqueue_assets() {
        $base_url = FLAVOR_PLATFORM_URL . 'includes/modules/trabajo-digno/assets/';

        wp_enqueue_style(
            'flavor-trabajo-digno',
            $base_url . 'css/trabajo-digno.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'flavor-trabajo-digno',
            $base_url . 'js/trabajo-digno.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('flavor-trabajo-digno', 'flavorTrabajoDigno', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trabajo_digno_nonce'),
            'tipos' => self::TIPOS_OFERTA,
            'sectores' => self::SECTORES,
            'criterios' => self::CRITERIOS_DIGNIDAD,
            'i18n' => [
                'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'success' => __('Operación completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirm_postular' => __('¿Confirmas tu postulación?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Shortcode: Ofertas de trabajo
     */
    public function shortcode_ofertas($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/ofertas.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Formación
     */
    public function shortcode_formacion($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/formacion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Emprendimientos
     */
    public function shortcode_emprendimientos($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/emprendimientos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi perfil profesional
     */
    public function shortcode_mi_perfil($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/mi-perfil.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Publicar oferta
     */
    public function shortcode_publicar($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/publicar.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Publicar oferta
     */
    public function ajax_publicar_oferta() {
        check_ajax_referer('trabajo_digno_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $sector = sanitize_text_field($_POST['sector'] ?? '');
        $jornada = sanitize_text_field($_POST['jornada'] ?? '');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $salario = sanitize_text_field($_POST['salario'] ?? '');
        $criterios = array_map('sanitize_text_field', $_POST['criterios'] ?? []);

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $oferta_id = wp_insert_post([
            'post_type' => 'td_oferta',
            'post_status' => 'pending',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($oferta_id) || empty($oferta_id)) {
            $error = is_wp_error($oferta_id) ? $oferta_id->get_error_message() : __('No se pudo crear la oferta.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($oferta_id, '_td_tipo', $tipo);
        update_post_meta($oferta_id, '_td_jornada', $jornada);
        update_post_meta($oferta_id, '_td_ubicacion', $ubicacion);
        update_post_meta($oferta_id, '_td_salario', $salario);
        update_post_meta($oferta_id, '_td_criterios_dignidad', $criterios);
        update_post_meta($oferta_id, '_td_postulaciones', []);

        if ($sector) {
            wp_set_object_terms($oferta_id, $sector, 'td_sector');
        }

        wp_send_json_success([
            'message' => __('Oferta publicada. Será revisada antes de su publicación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'oferta_id' => $oferta_id,
        ]);
    }

    /**
     * AJAX: Postular a oferta
     */
    public function ajax_postular() {
        check_ajax_referer('trabajo_digno_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $oferta_id = intval($_POST['oferta_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        $postulaciones = get_post_meta($oferta_id, '_td_postulaciones', true) ?: [];

        foreach ($postulaciones as $p) {
            if ($p['user_id'] === $user_id) {
                wp_send_json_error(['message' => __('Ya has postulado a esta oferta', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }
        }

        $postulaciones[] = [
            'user_id' => $user_id,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'estado' => 'pendiente',
        ];

        update_post_meta($oferta_id, '_td_postulaciones', $postulaciones);

        // Notificar al autor de la oferta
        $oferta = get_post($oferta_id);
        $autor_email = get_the_author_meta('email', $oferta->post_author);
        $candidato = wp_get_current_user();

        wp_mail(
            $autor_email,
            sprintf(__('Nueva postulación: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $oferta->post_title),
            sprintf(
                __("%s ha postulado a tu oferta '%s'.\n\nMensaje: %s\n\nAccede al panel para revisar la postulación.", FLAVOR_PLATFORM_TEXT_DOMAIN),
                $candidato->display_name,
                $oferta->post_title,
                $mensaje ?: __('Sin mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN)
            )
        );

        wp_send_json_success([
            'message' => __('Postulación enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Guardar perfil profesional
     */
    public function ajax_guardar_perfil() {
        check_ajax_referer('trabajo_digno_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $experiencia = sanitize_textarea_field($_POST['experiencia'] ?? '');
        $formacion = sanitize_textarea_field($_POST['formacion'] ?? '');
        $habilidades = array_map('sanitize_text_field', $_POST['habilidades'] ?? []);
        $sectores = array_map('sanitize_text_field', $_POST['sectores'] ?? []);
        $disponibilidad = sanitize_text_field($_POST['disponibilidad'] ?? '');

        // Buscar perfil existente o crear nuevo
        $perfil_existente = get_posts([
            'post_type' => 'td_perfil',
            'author' => $user_id,
            'posts_per_page' => 1,
        ]);

        $perfil_data = [
            'post_type' => 'td_perfil',
            'post_status' => 'publish',
            'post_title' => $titulo ?: get_user_meta($user_id, 'display_name', true),
            'post_content' => $descripcion,
            'post_author' => $user_id,
        ];

        if (!empty($perfil_existente)) {
            $perfil_data['ID'] = $perfil_existente[0]->ID;
            $perfil_id = wp_update_post($perfil_data);
        } else {
            $perfil_id = wp_insert_post($perfil_data, true);
        }

        if (is_wp_error($perfil_id) || empty($perfil_id)) {
            $error = is_wp_error($perfil_id) ? $perfil_id->get_error_message() : __('No se pudo guardar el perfil.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($perfil_id, '_td_experiencia', $experiencia);
        update_post_meta($perfil_id, '_td_formacion', $formacion);
        update_post_meta($perfil_id, '_td_disponibilidad', $disponibilidad);

        if (!empty($habilidades)) {
            wp_set_object_terms($perfil_id, $habilidades, 'td_habilidad');
        }
        if (!empty($sectores)) {
            wp_set_object_terms($perfil_id, $sectores, 'td_sector');
        }

        wp_send_json_success([
            'message' => __('Perfil actualizado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'perfil_id' => $perfil_id,
        ]);
    }

    /**
     * AJAX: Registrar emprendimiento
     */
    public function ajax_registrar_emprendimiento() {
        check_ajax_referer('trabajo_digno_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $sector = sanitize_text_field($_POST['sector'] ?? '');
        $tipo_organizacion = sanitize_text_field($_POST['tipo_organizacion'] ?? '');
        $web = esc_url_raw($_POST['web'] ?? '');
        $contacto = sanitize_email($_POST['contacto'] ?? '');

        if (empty($nombre) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Nombre y descripción son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $emprendimiento_id = wp_insert_post([
            'post_type' => 'td_emprendimiento',
            'post_status' => 'pending',
            'post_title' => $nombre,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($emprendimiento_id) || empty($emprendimiento_id)) {
            $error = is_wp_error($emprendimiento_id) ? $emprendimiento_id->get_error_message() : __('No se pudo crear el emprendimiento.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($emprendimiento_id, '_td_tipo_organizacion', $tipo_organizacion);
        update_post_meta($emprendimiento_id, '_td_web', $web);
        update_post_meta($emprendimiento_id, '_td_contacto', $contacto);

        if ($sector) {
            wp_set_object_terms($emprendimiento_id, $sector, 'td_sector');
        }

        wp_send_json_success([
            'message' => __('Emprendimiento registrado. Será revisado antes de su publicación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'emprendimiento_id' => $emprendimiento_id,
        ]);
    }

    /**
     * AJAX: Inscribir a formación
     */
    public function ajax_inscribir_formacion() {
        check_ajax_referer('trabajo_digno_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $formacion_id = intval($_POST['formacion_id'] ?? 0);
        $user_id = get_current_user_id();

        $inscritos = get_post_meta($formacion_id, '_td_inscritos', true) ?: [];
        $plazas_max = intval(get_post_meta($formacion_id, '_td_plazas', true));

        if (in_array($user_id, $inscritos)) {
            wp_send_json_error(['message' => __('Ya estás inscrito/a', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($plazas_max > 0 && count($inscritos) >= $plazas_max) {
            wp_send_json_error(['message' => __('No quedan plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $inscritos[] = $user_id;
        update_post_meta($formacion_id, '_td_inscritos', $inscritos);

        wp_send_json_success([
            'message' => __('Inscripción completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'inscritos' => count($inscritos),
        ]);
    }

    /**
     * Obtiene estadísticas del módulo
     *
     * @return array
     */
    public function get_estadisticas(): array {
        $ofertas = wp_count_posts('td_oferta');
        $formaciones = wp_count_posts('td_formacion');
        $emprendimientos = wp_count_posts('td_emprendimiento');

        $user_id = get_current_user_id();

        // Mis postulaciones
        global $wpdb;
        $mis_postulaciones = 0;
        if ($user_id) {
            $ofertas_con_postulaciones = get_posts([
                'post_type' => 'td_oferta',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => '_td_postulaciones', 'compare' => 'EXISTS']
                ],
            ]);

            foreach ($ofertas_con_postulaciones as $oferta) {
                $postulaciones = get_post_meta($oferta->ID, '_td_postulaciones', true) ?: [];
                foreach ($postulaciones as $p) {
                    if ($p['user_id'] == $user_id) {
                        $mis_postulaciones++;
                        break;
                    }
                }
            }
        }

        return [
            'ofertas_activas' => $ofertas->publish ?? 0,
            'formaciones_disponibles' => $formaciones->publish ?? 0,
            'emprendimientos_locales' => $emprendimientos->publish ?? 0,
            'mis_postulaciones' => $mis_postulaciones,
        ];
    }

    /**
     * Calcula el índice de dignidad de una oferta
     *
     * @param int $oferta_id
     * @return int Porcentaje 0-100
     */
    public function calcular_indice_dignidad($oferta_id): int {
        $criterios_oferta = get_post_meta($oferta_id, '_td_criterios_dignidad', true) ?: [];
        $total_criterios = count(self::CRITERIOS_DIGNIDAD);

        if ($total_criterios === 0) return 0;

        return intval((count($criterios_oferta) / $total_criterios) * 100);
    }

    /**
     * Obtiene páginas del frontend
     *
     * @return array
     */
    public function get_frontend_pages(): array {
        return [
            'ofertas' => [
                'titulo' => __('Bolsa de Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'trabajo-digno',
                'shortcode' => '[trabajo_digno_ofertas]',
                'icono' => 'dashicons-businessman',
            ],
            'formacion' => [
                'titulo' => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'trabajo-digno/formacion',
                'shortcode' => '[trabajo_digno_formacion]',
                'icono' => 'dashicons-welcome-learn-more',
            ],
            'emprendimientos' => [
                'titulo' => __('Emprendimientos Locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'trabajo-digno/emprendimientos',
                'shortcode' => '[trabajo_digno_emprendimientos]',
                'icono' => 'dashicons-store',
            ],
            'mi_perfil' => [
                'titulo' => __('Mi Perfil Profesional', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mi-portal/trabajo-digno',
                'shortcode' => '[trabajo_digno_mi_perfil]',
                'icono' => 'dashicons-id-alt',
            ],
            'publicar' => [
                'titulo' => __('Publicar Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'trabajo-digno/publicar',
                'shortcode' => '[trabajo_digno_publicar]',
                'icono' => 'dashicons-plus-alt',
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
            'buscar_empleo' => [
                'name' => __('Buscar Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Busca ofertas de trabajo digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'callback' => [$this, 'action_buscar_empleo'],
            ],
            'ver_formacion' => [
                'name' => __('Ver Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Consulta cursos y talleres disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'callback' => [$this, 'action_ver_formacion'],
            ],
        ];
    }

    /**
     * Acción: Buscar empleo
     */
    public function action_buscar_empleo($params) {
        $sector = $params['sector'] ?? '';
        $tipo = $params['tipo'] ?? '';

        $args = [
            'post_type' => 'td_oferta',
            'post_status' => 'publish',
            'posts_per_page' => 5,
        ];

        if ($sector) {
            $args['tax_query'] = [
                ['taxonomy' => 'td_sector', 'field' => 'slug', 'terms' => $sector]
            ];
        }

        if ($tipo) {
            $args['meta_query'] = [
                ['key' => '_td_tipo', 'value' => $tipo]
            ];
        }

        $ofertas = new WP_Query($args);

        $lista = [];
        foreach ($ofertas->posts as $oferta) {
            $tipo_data = self::TIPOS_OFERTA[get_post_meta($oferta->ID, '_td_tipo', true)] ?? ['nombre' => ''];
            $lista[] = [
                'titulo' => $oferta->post_title,
                'tipo' => $tipo_data['nombre'],
                'url' => get_permalink($oferta->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Encontré %d oferta(s) de trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN), count($lista)),
            'ofertas' => $lista,
            'url_completa' => home_url('/trabajo-digno/'),
        ];
    }

    /**
     * Acción: Ver formación
     */
    public function action_ver_formacion($params) {
        $formaciones = new WP_Query([
            'post_type' => 'td_formacion',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $lista = [];
        foreach ($formaciones->posts as $formacion) {
            $lista[] = [
                'titulo' => $formacion->post_title,
                'url' => get_permalink($formacion->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Hay %d formación(es) disponible(s)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($lista)),
            'formaciones' => $lista,
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
            __("Módulo de Trabajo Digno:\n" .
            "- %d ofertas de empleo activas\n" .
            "- %d formaciones disponibles\n" .
            "- %d emprendimientos locales\n\n" .
            "Funcionalidades:\n" .
            "- Bolsa de empleo con criterios éticos (OIT)\n" .
            "- Índice de dignidad para cada oferta\n" .
            "- Formación profesional y capacitación\n" .
            "- Directorio de emprendimientos locales\n" .
            "- Perfiles profesionales y postulaciones", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $stats['ofertas_activas'],
            $stats['formaciones_disponibles'],
            $stats['emprendimientos_locales']
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
                'pregunta' => __('¿Qué es el índice de dignidad?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'respuesta' => __('Es un indicador que muestra cuántos criterios de trabajo digno cumple una oferta (salario justo, conciliación, igualdad, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            [
                'pregunta' => __('¿Cómo publico una oferta de empleo?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'respuesta' => __('Ve a Trabajo Digno > Publicar Oferta. Completa el formulario incluyendo los criterios de dignidad que ofreces.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            [
                'pregunta' => __('¿Cómo postulo a una oferta?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'respuesta' => __('Desde la ficha de la oferta, haz clic en "Postular". Puedes adjuntar un mensaje de presentación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            [
                'pregunta' => __('¿Puedo registrar mi emprendimiento?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'respuesta' => __('Sí, en la sección de Emprendimientos puedes registrar tu proyecto o empresa local.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'listar' => 'ver_ofertas',
            'listado' => 'ver_ofertas',
            'ofertas' => 'ver_ofertas',
            'emprendimientos' => 'ver_emprendimientos',
            'crear' => 'publicar_oferta',
            'nuevo' => 'publicar_oferta',
            'mis_items' => 'ver_mi_perfil',
            'mi-cv' => 'ver_mi_perfil',
            'mis-postulaciones' => 'ver_mis_postulaciones',
            'foro' => 'foro_oferta',
            'chat' => 'chat_oferta',
            'multimedia' => 'multimedia_oferta',
            'red-social' => 'red_social_oferta',
            'red_social' => 'red_social_oferta',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'message' => __('Acción no implementada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    private function action_ver_ofertas($params) {
        return ['success' => true, 'html' => do_shortcode('[trabajo_digno_ofertas]')];
    }

    private function action_ver_emprendimientos($params) {
        return ['success' => true, 'html' => do_shortcode('[trabajo_digno_emprendimientos]')];
    }

    private function action_publicar_oferta($params) {
        return ['success' => true, 'html' => do_shortcode('[trabajo_digno_publicar]')];
    }

    private function action_ver_mi_perfil($params) {
        return ['success' => true, 'html' => do_shortcode('[trabajo_digno_mi_perfil]')];
    }

    private function action_ver_mis_postulaciones($params) {
        return ['success' => true, 'data' => $this->api_get_mis_postulaciones(new \WP_REST_Request('GET'))->get_data()];
    }

    private function resolve_contextual_oferta(array $params = []): ?\WP_Post {
        $oferta_id = intval(
            $params['oferta_id']
            ?? $params['entity_id']
            ?? ($_GET['oferta_id'] ?? 0)
        );

        if ($oferta_id <= 0) {
            return null;
        }

        $oferta = get_post($oferta_id);
        if (!$oferta || $oferta->post_type !== 'td_oferta') {
            return null;
        }

        return $oferta;
    }

    private function action_foro_oferta($params) {
        $oferta = $this->resolve_contextual_oferta((array) $params);
        if (!$oferta) {
            return ['success' => false, 'message' => __('Selecciona una oferta para abrir su foro.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        ob_start();
        ?>
        <div class="td-contexto td-contexto-foro">
            <div class="td-contexto-header">
                <h3><?php echo esc_html__('Foro de la oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php echo esc_html($oferta->post_title); ?></p>
            </div>
            <?php echo do_shortcode('[flavor_foros_integrado entidad="trabajo_digno_oferta" entidad_id="' . intval($oferta->ID) . '"]'); ?>
        </div>
        <?php
        return ['success' => true, 'html' => ob_get_clean()];
    }

    private function action_chat_oferta($params) {
        $oferta = $this->resolve_contextual_oferta((array) $params);
        if (!$oferta) {
            return ['success' => false, 'message' => __('Selecciona una oferta para abrir su chat.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        ob_start();
        ?>
        <div class="td-contexto td-contexto-chat">
            <div class="td-contexto-header">
                <h3><?php echo esc_html__('Chat de la oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php echo esc_html($oferta->post_title); ?></p>
            </div>
            <p><a class="button button-secondary" href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/')); ?>"><?php echo esc_html__('Abrir chat completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></p>
            <?php echo do_shortcode('[flavor_chat_grupo_integrado entidad="trabajo_digno_oferta" entidad_id="' . intval($oferta->ID) . '"]'); ?>
        </div>
        <?php
        return ['success' => true, 'html' => ob_get_clean()];
    }

    private function action_multimedia_oferta($params) {
        $oferta = $this->resolve_contextual_oferta((array) $params);
        if (!$oferta) {
            return ['success' => false, 'message' => __('Selecciona una oferta para ver sus recursos multimedia.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        ob_start();
        ?>
        <div class="td-contexto td-contexto-multimedia">
            <div class="td-contexto-header">
                <h3><?php echo esc_html__('Recursos de la oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php echo esc_html($oferta->post_title); ?></p>
            </div>
            <p><a class="button button-secondary" href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/?trabajo_digno_oferta_id=' . intval($oferta->ID))); ?>"><?php echo esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></p>
            <?php echo do_shortcode('[flavor_multimedia_galeria entidad="trabajo_digno_oferta" entidad_id="' . intval($oferta->ID) . '"]'); ?>
        </div>
        <?php
        return ['success' => true, 'html' => ob_get_clean()];
    }

    private function action_red_social_oferta($params) {
        $oferta = $this->resolve_contextual_oferta((array) $params);
        if (!$oferta) {
            return ['success' => false, 'message' => __('Selecciona una oferta para ver su actividad social.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        ob_start();
        ?>
        <div class="td-contexto td-contexto-red-social">
            <div class="td-contexto-header">
                <h3><?php echo esc_html__('Actividad social de la oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php echo esc_html($oferta->post_title); ?></p>
            </div>
            <p><a class="button button-secondary" href="<?php echo esc_url(home_url('/mi-portal/red-social/crear/?trabajo_digno_oferta_id=' . intval($oferta->ID))); ?>"><?php echo esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a></p>
            <?php echo do_shortcode('[flavor_social_feed entidad="trabajo_digno_oferta" entidad_id="' . intval($oferta->ID) . '"]'); ?>
        </div>
        <?php
        return ['success' => true, 'html' => ob_get_clean()];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'trabajo-digno',
            'title'    => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Ofertas de empleo con criterios de dignidad laboral', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '💼',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_trabajo_digno',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'        => ['type' => 'text', 'label' => __('Puesto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'empresa'       => ['type' => 'text', 'label' => __('Empresa/Organización', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'descripcion'   => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'tipo_contrato' => ['type' => 'select', 'label' => __('Tipo de contrato', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'jornada'       => ['type' => 'select', 'label' => __('Jornada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => ['completa', 'parcial', 'flexible']],
                'salario'       => ['type' => 'text', 'label' => __('Salario', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'ubicacion'     => ['type' => 'text', 'label' => __('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'indice_dignidad' => ['type' => 'number', 'label' => __('Índice dignidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'min' => 0, 'max' => 100],
            ],

            'estados' => [
                'activa'    => ['label' => __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '🟢'],
                'pausada'   => ['label' => __('Pausada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '⏸️'],
                'cubierta'  => ['label' => __('Cubierta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'blue', 'icon' => '✅'],
                'cancelada' => ['label' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                'ofertas_activas'  => ['label' => __('Ofertas activas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '💼', 'color' => 'sky'],
                'empresas'         => ['label' => __('Empresas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🏢', 'color' => 'blue'],
                'emprendimientos'  => ['label' => __('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🚀', 'color' => 'indigo'],
                'contrataciones'   => ['label' => __('Contrataciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🤝', 'color' => 'green'],
            ],

            'card' => [
                'template'     => 'oferta-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'empresa',
                'meta_fields'  => ['jornada', 'ubicacion', 'indice_dignidad'],
                'show_badge'   => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'ofertas' => [
                    'label'   => __('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-clipboard',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'emprendimientos' => [
                    'label'   => __('Emprendimientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-lightbulb',
                    'content' => 'shortcode:trabajo_digno_emprendimientos',
                    'public'  => true,
                ],
                'publicar' => [
                    'label'      => __('Publicar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:trabajo_digno_publicar',
                    'requires_login' => true,
                ],
                'mis-postulaciones' => [
                    'label'      => __('Mis postulaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'callback:render_tab_mis_postulaciones',
                    'requires_login' => true,
                ],
                'mi-cv' => [
                    'label'      => __('Mi CV', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-id',
                    'content'    => 'shortcode:trabajo_digno_mi_perfil',
                    'requires_login' => true,
                ],
                'foro' => [
                    'label'      => __('Foro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-format-chat',
                    'content'    => 'callback:render_tab_foro',
                    'hidden_nav' => true,
                ],
                'chat' => [
                    'label'      => __('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-format-status',
                    'content'    => 'callback:render_tab_chat',
                    'hidden_nav' => true,
                ],
                'multimedia' => [
                    'label'      => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-format-gallery',
                    'content'    => 'callback:render_tab_multimedia',
                    'hidden_nav' => true,
                ],
                'red-social' => [
                    'label'      => __('Red social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-share',
                    'content'    => 'callback:render_tab_red_social',
                    'hidden_nav' => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 12,
                'order_by'   => 'fecha_publicacion',
                'order'      => 'DESC',
                'filterable' => ['tipo_contrato', 'jornada', 'zona'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'ofertas_recientes', 'mis_postulaciones', 'emprendimientos_locales'],
                'actions' => [
                    'publicar'  => ['label' => __('Publicar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📝', 'color' => 'sky'],
                    'buscar'    => ['label' => __('Buscar empleo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔍', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'indice_dignidad'  => true,
                'postulaciones'    => true,
                'cv_online'        => true,
                'emprendimientos'  => true,
                'alertas'          => true,
            ],
        ];
    }

    public function render_tab_mis_postulaciones(): string {
        $response = $this->api_get_mis_postulaciones(new \WP_REST_Request('GET'))->get_data();
        $postulaciones = $response['postulaciones'] ?? [];

        ob_start();
        ?>
        <div class="td-mis-postulaciones">
            <?php if (empty($postulaciones)) : ?>
                <p><?php esc_html_e('No tienes postulaciones registradas todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else : ?>
                <ul class="td-postulaciones-lista">
                    <?php foreach ($postulaciones as $postulacion) : ?>
                        <li>
                            <strong><?php echo esc_html($postulacion['oferta_titulo'] ?? __('Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                            <span><?php echo esc_html($postulacion['fecha'] ?? ''); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_tab_foro(): string {
        $result = $this->action_foro_oferta(['oferta_id' => $_GET['oferta_id'] ?? 0]);
        return $result['html'] ?? '<p>' . esc_html($result['message'] ?? '') . '</p>';
    }

    public function render_tab_chat(): string {
        $result = $this->action_chat_oferta(['oferta_id' => $_GET['oferta_id'] ?? 0]);
        return $result['html'] ?? '<p>' . esc_html($result['message'] ?? '') . '</p>';
    }

    public function render_tab_multimedia(): string {
        $result = $this->action_multimedia_oferta(['oferta_id' => $_GET['oferta_id'] ?? 0]);
        return $result['html'] ?? '<p>' . esc_html($result['message'] ?? '') . '</p>';
    }

    public function render_tab_red_social(): string {
        $result = $this->action_red_social_oferta(['oferta_id' => $_GET['oferta_id'] ?? 0]);
        return $result['html'] ?? '<p>' . esc_html($result['message'] ?? '') . '</p>';
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-trabajo-digno-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Trabajo_Digno_Dashboard_Tab')) {
                Flavor_Trabajo_Digno_Dashboard_Tab::get_instance();
            }
        }
    }
}

if (!class_exists('Flavor_Chat_Trabajo_Digno_Module', false)) {
    class_alias('Flavor_Platform_Trabajo_Digno_Module', 'Flavor_Chat_Trabajo_Digno_Module');
}
