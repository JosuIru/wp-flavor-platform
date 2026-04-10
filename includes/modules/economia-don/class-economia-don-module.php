<?php
/**
 * Módulo Economía del Don
 *
 * Sistema de donaciones y regalos sin expectativa de retorno.
 * Facilita ofrecer y recibir sin contabilidad ni intercambio.
 *
 * Valoración de Conciencia: 94/100
 * - conciencia_fundamental: 0.25 (Dar por el placer de dar)
 * - abundancia_organizable: 0.30 (Lo que sobra para quien lo necesita)
 * - interdependencia_radical: 0.20 (Red de apoyo incondicional)
 * - madurez_ciclica: 0.10 (Flujo natural de dar/recibir)
 * - valor_intrinseco: 0.15 (El valor está en el acto de dar)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Economia_Don_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Categorías de dones
     */
    const CATEGORIAS_DON = [
        'objetos' => [
            'nombre' => 'Objetos y cosas',
            'icono' => 'dashicons-archive',
            'color' => '#3498db',
            'descripcion' => 'Ropa, muebles, electrodomésticos, juguetes...',
        ],
        'alimentos' => [
            'nombre' => 'Alimentos',
            'icono' => 'dashicons-carrot',
            'color' => '#27ae60',
            'descripcion' => 'Comida casera, excedentes de huerta, conservas...',
        ],
        'servicios' => [
            'nombre' => 'Servicios y habilidades',
            'icono' => 'dashicons-admin-tools',
            'color' => '#9b59b6',
            'descripcion' => 'Clases, reparaciones, traducciones, diseño...',
        ],
        'tiempo' => [
            'nombre' => 'Tiempo y compañía',
            'icono' => 'dashicons-clock',
            'color' => '#e74c3c',
            'descripcion' => 'Acompañamiento, escucha, paseos, cuidados...',
        ],
        'conocimiento' => [
            'nombre' => 'Conocimiento',
            'icono' => 'dashicons-book',
            'color' => '#f39c12',
            'descripcion' => 'Tutorías, mentorías, consejos, experiencia...',
        ],
        'espacios' => [
            'nombre' => 'Espacios',
            'icono' => 'dashicons-admin-home',
            'color' => '#1abc9c',
            'descripcion' => 'Uso temporal de espacios, alojamiento, local...',
        ],
    ];

    /**
     * Estados del don
     */
    const ESTADOS_DON = [
        'disponible' => ['nombre' => 'Disponible', 'color' => '#27ae60'],
        'reservado' => ['nombre' => 'Reservado', 'color' => '#f39c12'],
        'entregado' => ['nombre' => 'Entregado', 'color' => '#3498db'],
        'recibido' => ['nombre' => 'Recibido con gratitud', 'color' => '#9b59b6'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'economia_don';
        $this->name = __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Dar y recibir sin esperar nada a cambio.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->icon = 'dashicons-heart';
        $this->color = '#e74c3c';

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['economia_local', 'cuidados'];
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
            'categorias_habilitadas' => array_keys(self::CATEGORIAS_DON),
            'permitir_anonimato' => true,
            'notificar_nuevos_dones' => true,
            'mostrar_mapa' => true,
            'radio_busqueda_km' => 10,
            'mostrar_en_dashboard' => true,
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia'];
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
                'table'   => $wpdb->prefix . 'flavor_economia_don_ofertas',
                'context' => 'side',
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
        add_action('save_post_ed_don', [$this, 'guardar_meta_don']);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX
        add_action('wp_ajax_ed_solicitar_don', [$this, 'ajax_solicitar_don']);
        add_action('wp_ajax_ed_confirmar_entrega', [$this, 'ajax_confirmar_entrega']);
        add_action('wp_ajax_ed_agradecer', [$this, 'ajax_agradecer']);
        add_action('wp_ajax_ed_publicar_don', [$this, 'ajax_publicar_don']);

        // Dashboard widget
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // API REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Panel Unificado
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
        $tab_file = dirname(__FILE__) . '/class-economia-don-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Economia_Don_Dashboard_Tab')) {
                Flavor_Economia_Don_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar dones disponibles
        register_rest_route($namespace, '/economia-don/dones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_dones'],
            'permission_callback' => [$this, 'public_read_permission'],
            'args' => [
                'categoria' => ['type' => 'string'],
                'limite' => ['type' => 'integer', 'default' => 20],
            ],
        ]);

        // Obtener don por ID
        register_rest_route($namespace, '/economia-don/dones/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_don'],
            'permission_callback' => [$this, 'can_read_don'],
        ]);

        // Publicar nuevo don
        register_rest_route($namespace, '/economia-don/dones', [
            'methods' => 'POST',
            'callback' => [$this, 'api_publicar_don'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Solicitar un don
        register_rest_route($namespace, '/economia-don/dones/(?P<id>\d+)/solicitar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_solicitar_don'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Confirmar entrega
        register_rest_route($namespace, '/economia-don/dones/(?P<id>\d+)/entregar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_confirmar_entrega'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Mis dones
        register_rest_route($namespace, '/economia-don/mis-dones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_dones'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Muro de gratitud
        register_rest_route($namespace, '/economia-don/gratitudes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_muro_gratitud'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);
    }

    /**
     * Permite lecturas públicas explícitas.
     */
    public function public_read_permission() {
        return true;
    }

    /**
     * Permite leer un don solo si es público o si pertenece al usuario actual.
     */
    public function can_read_don($request) {
        $don_id = absint($request->get_param('id'));
        $don = get_post($don_id);

        if (!$don || $don->post_type !== 'ed_don') {
            return false;
        }

        if ($don->post_status === 'publish' && get_post_meta($don_id, '_ed_estado', true) === 'disponible') {
            return true;
        }

        $user_id = get_current_user_id();
        return $user_id > 0 && ($user_id === (int) $don->post_author || current_user_can('manage_options'));
    }

    /**
     * API: Listar dones disponibles
     */
    public function api_listar_dones($request) {
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite') ?: 20;

        $args = [
            'post_type' => 'ed_don',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'meta_query' => [
                ['key' => '_ed_estado', 'value' => 'disponible'],
            ],
        ];

        if ($categoria && isset(self::CATEGORIAS_DON[$categoria])) {
            $args['meta_query'][] = ['key' => '_ed_categoria', 'value' => $categoria];
        }

        $dones = get_posts($args);
        $resultado = [];

        foreach ($dones as $don) {
            $cat_key = get_post_meta($don->ID, '_ed_categoria', true);
            $anonimo = get_post_meta($don->ID, '_ed_anonimo', true);
            $resultado[] = [
                'id' => $don->ID,
                'titulo' => $don->post_title,
                'descripcion' => wp_trim_words($don->post_content, 30),
                'categoria' => $cat_key,
                'categoria_nombre' => self::CATEGORIAS_DON[$cat_key]['nombre'] ?? $cat_key,
                'ubicacion' => get_post_meta($don->ID, '_ed_ubicacion', true),
                'disponibilidad' => get_post_meta($don->ID, '_ed_disponibilidad', true),
                'autor' => $anonimo ? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN) : get_the_author_meta('display_name', $don->post_author),
                'imagen' => get_the_post_thumbnail_url($don->ID, 'medium'),
                'fecha' => get_the_date('Y-m-d', $don),
            ];
        }

        return new \WP_REST_Response([
            'success' => true,
            'total' => count($resultado),
            'dones' => $resultado,
            'categorias' => array_keys(self::CATEGORIAS_DON),
        ], 200);
    }

    /**
     * API: Obtener don por ID
     */
    public function api_obtener_don($request) {
        $id = $request->get_param('id');
        $don = get_post($id);

        if (!$don || $don->post_type !== 'ed_don') {
            return new \WP_REST_Response(['success' => false, 'error' => 'Don no encontrado'], 404);
        }

        $estado = get_post_meta($don->ID, '_ed_estado', true);
        if (($don->post_status ?? '') !== 'publish' || $estado !== 'disponible') {
            $user_id = get_current_user_id();
            if ($user_id <= 0 || ($user_id !== (int) $don->post_author && !current_user_can('manage_options'))) {
                return new \WP_REST_Response(['success' => false, 'error' => 'Don no disponible'], 404);
            }
        }

        $cat_key = get_post_meta($don->ID, '_ed_categoria', true);
        $anonimo = get_post_meta($don->ID, '_ed_anonimo', true);

        return new \WP_REST_Response([
            'success' => true,
            'don' => [
                'id' => $don->ID,
                'titulo' => $don->post_title,
                'contenido' => $don->post_content,
                'categoria' => $cat_key,
                'categoria_nombre' => self::CATEGORIAS_DON[$cat_key]['nombre'] ?? $cat_key,
                'estado' => $estado,
                'ubicacion' => get_post_meta($don->ID, '_ed_ubicacion', true),
                'disponibilidad' => get_post_meta($don->ID, '_ed_disponibilidad', true),
                'condiciones' => get_post_meta($don->ID, '_ed_condiciones', true),
                'autor' => $anonimo ? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN) : get_the_author_meta('display_name', $don->post_author),
                'imagen' => get_the_post_thumbnail_url($don->ID, 'large'),
            ],
        ], 200);
    }

    /**
     * API: Publicar nuevo don
     */
    public function api_publicar_don($request) {
        $titulo = sanitize_text_field($request->get_param('titulo'));
        $descripcion = sanitize_textarea_field($request->get_param('descripcion'));
        $categoria = sanitize_text_field($request->get_param('categoria') ?: 'objetos');
        $ubicacion = sanitize_text_field($request->get_param('ubicacion'));
        $disponibilidad = sanitize_text_field($request->get_param('disponibilidad'));
        $anonimo = $request->get_param('anonimo');

        if (empty($titulo)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Título requerido'], 400);
        }

        $don_id = wp_insert_post([
            'post_type' => 'ed_don',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($don_id) || empty($don_id)) {
            $error = is_wp_error($don_id) ? $don_id->get_error_message() : __('No se pudo crear el don.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            return new \WP_REST_Response(['success' => false, 'error' => $error], 500);
        }

        update_post_meta($don_id, '_ed_categoria', $categoria);
        update_post_meta($don_id, '_ed_estado', 'disponible');
        update_post_meta($don_id, '_ed_ubicacion', $ubicacion);
        update_post_meta($don_id, '_ed_disponibilidad', $disponibilidad);
        update_post_meta($don_id, '_ed_anonimo', $anonimo ? '1' : '0');

        return new \WP_REST_Response([
            'success' => true,
            'mensaje' => __('¡Don publicado! Gracias por tu generosidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'id' => $don_id,
        ], 201);
    }

    /**
     * API: Solicitar un don
     */
    public function api_solicitar_don($request) {
        $don_id = $request->get_param('id');
        $mensaje = sanitize_textarea_field($request->get_param('mensaje'));
        $user_id = get_current_user_id();

        $estado = get_post_meta($don_id, '_ed_estado', true);
        if ($estado !== 'disponible') {
            return new \WP_REST_Response(['success' => false, 'error' => 'Este don ya no está disponible'], 400);
        }

        $solicitud_id = wp_insert_post([
            'post_type' => 'ed_solicitud',
            'post_title' => sprintf(__('Solicitud de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), get_userdata($user_id)->display_name),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ], true);

        if (is_wp_error($solicitud_id) || empty($solicitud_id)) {
            $error = is_wp_error($solicitud_id) ? $solicitud_id->get_error_message() : __('No se pudo crear la solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            return new \WP_REST_Response(['success' => false, 'error' => $error], 500);
        }

        update_post_meta($solicitud_id, '_ed_don_id', $don_id);
        update_post_meta($solicitud_id, '_ed_mensaje', $mensaje);
        update_post_meta($solicitud_id, '_ed_estado', 'pendiente');
        update_post_meta($don_id, '_ed_estado', 'reservado');
        update_post_meta($don_id, '_ed_receptor_id', $user_id);

        return new \WP_REST_Response([
            'success' => true,
            'mensaje' => __('¡Solicitud enviada! El donante se pondrá en contacto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * API: Confirmar entrega
     */
    public function api_confirmar_entrega($request) {
        $don_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $donante_id = get_post_field('post_author', $don_id);
        if ($donante_id != $user_id && !current_user_can('manage_options')) {
            return new \WP_REST_Response(['success' => false, 'error' => 'No tienes permiso'], 403);
        }

        update_post_meta($don_id, '_ed_estado', 'entregado');
        update_post_meta($don_id, '_ed_fecha_entrega', current_time('mysql'));

        $dones_dados = absint(get_user_meta($user_id, '_ed_dones_dados', true));
        update_user_meta($user_id, '_ed_dones_dados', $dones_dados + 1);

        return new \WP_REST_Response([
            'success' => true,
            'mensaje' => __('¡Entrega confirmada! Gracias por tu generosidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * API: Mis dones
     */
    public function api_mis_dones($request) {
        $user_id = get_current_user_id();
        $estadisticas = $this->get_estadisticas_usuario($user_id);

        $dones = get_posts([
            'post_type' => 'ed_don',
            'author' => $user_id,
            'posts_per_page' => 20,
            'post_status' => 'publish',
        ]);

        $mis_dones = array_map(function($don) {
            return [
                'id' => $don->ID,
                'titulo' => $don->post_title,
                'estado' => get_post_meta($don->ID, '_ed_estado', true),
                'categoria' => get_post_meta($don->ID, '_ed_categoria', true),
            ];
        }, $dones);

        return new \WP_REST_Response([
            'success' => true,
            'estadisticas' => $estadisticas,
            'dones' => $mis_dones,
        ], 200);
    }

    /**
     * API: Muro de gratitud
     */
    public function api_muro_gratitud($request) {
        $limite = $request->get_param('limite') ?: 20;

        $gratitudes = get_posts([
            'post_type' => 'ed_gratitud',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
        ]);

        $resultado = array_map(function($gratitud) {
            $don_id = get_post_meta($gratitud->ID, '_ed_don_id', true);
            return [
                'id' => $gratitud->ID,
                'titulo' => $gratitud->post_title,
                'mensaje' => $gratitud->post_content,
                'autor' => get_the_author_meta('display_name', $gratitud->post_author),
                'don_titulo' => $don_id ? get_the_title($don_id) : '',
                'fecha' => get_the_date('Y-m-d', $gratitud),
            ];
        }, $gratitudes);

        return new \WP_REST_Response(['success' => true, 'gratitudes' => $resultado], 200);
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     */
    protected function get_admin_config() {
        return [
            'id' => 'economia-don',
            'label' => __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-heart',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'economia-don-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'economia-don-listado',
                    'titulo' => __('Dones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug' => 'economia-don-solicitudes',
                    'titulo' => __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_solicitudes'],
                    'badge' => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug' => 'economia-don-gratitudes',
                    'titulo' => __('Gratitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_gratitudes'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta solicitudes pendientes
     */
    public function contar_solicitudes_pendientes() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'ed_solicitud'
               AND p.post_status = 'publish'
               AND pm.meta_key = '_ed_estado'
               AND pm.meta_value = 'pendiente'"
        );
    }

    /**
     * Estadísticas para dashboard unificado
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $resultado = [];

        $dones_disponibles = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'ed_don' AND p.post_status = 'publish'
               AND pm.meta_key = '_ed_estado' AND pm.meta_value = 'disponible'"
        );

        $resultado[] = [
            'icon' => 'dashicons-heart',
            'valor' => $dones_disponibles,
            'label' => __('Dones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'green',
            'enlace' => admin_url('admin.php?page=economia-don-listado'),
        ];

        $gratitudes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ed_gratitud' AND post_status = 'publish'"
        );

        $resultado[] = [
            'icon' => 'dashicons-smiley',
            'valor' => $gratitudes,
            'label' => __('Gratitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'blue',
            'enlace' => admin_url('admin.php?page=economia-don-gratitudes'),
        ];

        return $resultado;
    }

    /**
     * Renderiza dashboard admin
     */
    public function render_admin_dashboard() {
        $rutaVista = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($rutaVista)) {
            include $rutaVista;
        } else {
            // Fallback inline si no existe la vista
            echo '<div class="wrap flavor-modulo-page">';
            $this->render_page_header(__('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN));

            global $wpdb;
            $disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'ed_don' AND p.post_status = 'publish' AND pm.meta_key = '_ed_estado' AND pm.meta_value = 'disponible'");
            $entregados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'ed_don' AND pm.meta_key = '_ed_estado' AND pm.meta_value IN ('entregado', 'recibido')");
            $gratitudes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ed_gratitud' AND post_status = 'publish'");

            echo '<div class="flavor-stats-grid">';
            echo '<div class="flavor-stat-card"><span class="dashicons dashicons-heart"></span><div class="stat-content"><span class="stat-number">' . esc_html($disponibles) . '</span><span class="stat-label">' . esc_html__('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div></div>';
            echo '<div class="flavor-stat-card"><span class="dashicons dashicons-yes"></span><div class="stat-content"><span class="stat-number">' . esc_html($entregados) . '</span><span class="stat-label">' . esc_html__('Entregados', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div></div>';
            echo '<div class="flavor-stat-card"><span class="dashicons dashicons-smiley"></span><div class="stat-content"><span class="stat-number">' . esc_html($gratitudes) . '</span><span class="stat-label">' . esc_html__('Gratitudes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div></div>';
            echo '</div></div>';
        }
    }

    /**
     * Renderiza listado de dones
     */
    public function render_admin_listado() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dones', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nuevo Don', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('post-new.php?post_type=ed_don'), 'class' => 'button-primary'],
        ]);

        $dones = get_posts(['post_type' => 'ed_don', 'posts_per_page' => 50, 'post_status' => ['publish', 'draft']]);

        if (empty($dones)) {
            echo '<p>' . esc_html__('No hay dones publicados.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__('Título', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th></tr></thead><tbody>';
            foreach ($dones as $don) {
                $cat_key = get_post_meta($don->ID, '_ed_categoria', true);
                $estado = get_post_meta($don->ID, '_ed_estado', true) ?: 'disponible';
                echo '<tr><td>' . esc_html($don->ID) . '</td><td>' . esc_html($don->post_title) . '</td><td>' . esc_html(self::CATEGORIAS_DON[$cat_key]['nombre'] ?? $cat_key) . '</td><td>' . esc_html(self::ESTADOS_DON[$estado]['nombre'] ?? $estado) . '</td><td><a href="' . esc_url(get_edit_post_link($don->ID)) . '" class="button button-small">' . esc_html__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    /**
     * Renderiza solicitudes
     */
    public function render_admin_solicitudes() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Solicitudes de Dones', FLAVOR_PLATFORM_TEXT_DOMAIN));

        $solicitudes = get_posts(['post_type' => 'ed_solicitud', 'posts_per_page' => 50]);

        if (empty($solicitudes)) {
            echo '<p>' . esc_html__('No hay solicitudes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Don', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th></tr></thead><tbody>';
            foreach ($solicitudes as $sol) {
                $don_id = get_post_meta($sol->ID, '_ed_don_id', true);
                $estado = get_post_meta($sol->ID, '_ed_estado', true);
                echo '<tr><td>' . esc_html($sol->ID) . '</td><td>' . esc_html(get_the_author_meta('display_name', $sol->post_author)) . '</td><td>' . esc_html($don_id ? get_the_title($don_id) : '-') . '</td><td>' . esc_html(ucfirst($estado)) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    /**
     * Renderiza gratitudes
     */
    public function render_admin_gratitudes() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Muro de Gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN));

        $gratitudes = get_posts(['post_type' => 'ed_gratitud', 'posts_per_page' => 50, 'post_status' => 'publish']);

        if (empty($gratitudes)) {
            echo '<p>' . esc_html__('No hay gratitudes publicadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            echo '<div class="flavor-gratitudes-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">';
            foreach ($gratitudes as $g) {
                echo '<div style="background:#f9f9f9;padding:16px;border-radius:8px;border-left:4px solid #e74c3c;">';
                echo '<strong>' . esc_html($g->post_title) . '</strong>';
                echo '<p>' . esc_html(wp_trim_words($g->post_content, 30)) . '</p>';
                echo '<small>' . esc_html(get_the_author_meta('display_name', $g->post_author)) . ' - ' . esc_html(get_the_date('d/m/Y', $g)) . '</small>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        // Shortcodes principales y aliases sin sobrescribir registros previos.
        $shortcodes = [
            'economia_don' => 'shortcode_listado',
            'mis_dones' => 'shortcode_mis_dones',
            'ofrecer_don' => 'shortcode_ofrecer',
            'muro_gratitud' => 'shortcode_muro_gratitud',
            // Aliases con prefijo flavor_ para compatibilidad con dynamic-pages
            'flavor_don_listado' => 'shortcode_listado',
            'flavor_don_mis_dones' => 'shortcode_mis_dones',
            'flavor_don_ofrecer' => 'shortcode_ofrecer',
            'flavor_don_muro_gratitud' => 'shortcode_muro_gratitud',
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
        // CPT: Dones (objetos/servicios ofrecidos)
        register_post_type('ed_don', [
            'labels' => [
                'name' => __('Dones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new' => __('Ofrecer Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'add_new_item' => __('Ofrecer Nuevo Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'edit_item' => __('Editar Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-heart',
            'show_in_menu' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'economia-don'],
        ]);

        // CPT: Solicitudes de don
        register_post_type('ed_solicitud', [
            'labels' => [
                'name' => __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title'],
        ]);

        // CPT: Gratitudes
        register_post_type('ed_gratitud', [
            'labels' => [
                'name' => __('Gratitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-smiley',
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'muro-gratitud'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        register_taxonomy('ed_categoria', 'ed_don', [
            'labels' => [
                'name' => __('Categorías de Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'singular_name' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'ed_don_datos',
            __('Datos del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_meta_box_don'],
            'ed_don',
            'normal',
            'high'
        );
    }

    /**
     * Renderiza meta box del don
     */
    public function render_meta_box_don($post) {
        wp_nonce_field('ed_don_nonce', 'ed_don_nonce_field');

        $categoria = get_post_meta($post->ID, '_ed_categoria', true);
        $estado = get_post_meta($post->ID, '_ed_estado', true) ?: 'disponible';
        $ubicacion = get_post_meta($post->ID, '_ed_ubicacion', true);
        $anonimo = get_post_meta($post->ID, '_ed_anonimo', true);
        $disponibilidad = get_post_meta($post->ID, '_ed_disponibilidad', true);
        $condiciones = get_post_meta($post->ID, '_ed_condiciones', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ed_categoria"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="ed_categoria" id="ed_categoria" class="regular-text">
                        <?php foreach (self::CATEGORIAS_DON as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($categoria, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ed_estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="ed_estado" id="ed_estado">
                        <?php foreach (self::ESTADOS_DON as $id => $data) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($estado, $id); ?>>
                            <?php echo esc_html($data['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ed_ubicacion"><?php esc_html_e('Ubicación/Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="ed_ubicacion" id="ed_ubicacion"
                           value="<?php echo esc_attr($ubicacion); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: Centro, Barrio Norte...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ed_disponibilidad"><?php esc_html_e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="ed_disponibilidad" id="ed_disponibilidad"
                           value="<?php echo esc_attr($disponibilidad); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: Tardes de 17-20h', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ed_condiciones"><?php esc_html_e('Condiciones (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <textarea name="ed_condiciones" id="ed_condiciones" rows="2" class="large-text"
                              placeholder="<?php esc_attr_e('Ej: Recoger en mi domicilio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php echo esc_textarea($condiciones); ?></textarea>
                    <p class="description"><?php esc_html_e('Requisitos para recibir el don (no monetarios)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ed_anonimo"><?php esc_html_e('Donación anónima', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="ed_anonimo" id="ed_anonimo" value="1"
                               <?php checked($anonimo, '1'); ?>>
                        <?php esc_html_e('No mostrar mi nombre públicamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guarda meta del don
     */
    public function guardar_meta_don($post_id) {
        if (!isset($_POST['ed_don_nonce_field']) ||
            !wp_verify_nonce($_POST['ed_don_nonce_field'], 'ed_don_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos = ['categoria', 'estado', 'ubicacion', 'disponibilidad', 'condiciones'];
        foreach ($campos as $campo) {
            if (isset($_POST['ed_' . $campo])) {
                update_post_meta($post_id, '_ed_' . $campo, sanitize_text_field($_POST['ed_' . $campo]));
            }
        }

        update_post_meta($post_id, '_ed_anonimo', isset($_POST['ed_anonimo']) ? '1' : '0');
    }

    /**
     * AJAX: Solicitar un don
     */
    public function ajax_solicitar_don() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que el don está disponible
        $estado = get_post_meta($don_id, '_ed_estado', true);
        if ($estado !== 'disponible') {
            wp_send_json_error(['message' => __('Este don ya no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Crear solicitud
        $solicitud_id = wp_insert_post([
            'post_type' => 'ed_solicitud',
            'post_title' => sprintf(
                __('Solicitud de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                get_userdata($user_id)->display_name
            ),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ], true);

        if (!is_wp_error($solicitud_id) && !empty($solicitud_id)) {
            update_post_meta($solicitud_id, '_ed_don_id', $don_id);
            update_post_meta($solicitud_id, '_ed_mensaje', $mensaje);
            update_post_meta($solicitud_id, '_ed_estado', 'pendiente');

            // Notificar al donante
            $donante_id = get_post_field('post_author', $don_id);
            $this->notificar_donante($donante_id, $don_id, $user_id);

            // Marcar don como reservado
            update_post_meta($don_id, '_ed_estado', 'reservado');
            update_post_meta($don_id, '_ed_receptor_id', $user_id);

            wp_send_json_success([
                'message' => __('¡Solicitud enviada! El donante se pondrá en contacto contigo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        $error_message = is_wp_error($solicitud_id)
            ? $solicitud_id->get_error_message()
            : __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN);
        wp_send_json_error(['message' => $error_message]);
    }

    /**
     * AJAX: Confirmar entrega
     */
    public function ajax_confirmar_entrega() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que es el donante
        $donante_id = get_post_field('post_author', $don_id);
        if ($donante_id != $user_id) {
            wp_send_json_error(['message' => __('No tienes permiso', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        update_post_meta($don_id, '_ed_estado', 'entregado');
        update_post_meta($don_id, '_ed_fecha_entrega', current_time('mysql'));

        // Actualizar estadísticas del donante
        $dones_dados = absint(get_user_meta($user_id, '_ed_dones_dados', true));
        update_user_meta($user_id, '_ed_dones_dados', $dones_dados + 1);

        // Notificar al receptor para que agradezca
        $receptor_id = get_post_meta($don_id, '_ed_receptor_id', true);
        if ($receptor_id) {
            $this->notificar_receptor_entrega($receptor_id, $don_id);
        }

        wp_send_json_success([
            'message' => __('¡Entrega confirmada! Gracias por tu generosidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Agradecer un don
     */
    public function ajax_agradecer() {
        check_ajax_referer('ed_nonce', 'nonce');

        $don_id = absint($_POST['don_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $user_id = get_current_user_id();

        if (!$don_id || !$user_id || !$mensaje) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que es el receptor
        $receptor_id = get_post_meta($don_id, '_ed_receptor_id', true);
        if ($receptor_id != $user_id) {
            wp_send_json_error(['message' => __('No tienes permiso', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Crear gratitud
        $gratitud_id = wp_insert_post([
            'post_type' => 'ed_gratitud',
            'post_title' => sprintf(__('Gratitud por "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), get_the_title($don_id)),
            'post_content' => $mensaje,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ], true);

        if (!is_wp_error($gratitud_id) && !empty($gratitud_id)) {
            update_post_meta($gratitud_id, '_ed_don_id', $don_id);
            update_post_meta($don_id, '_ed_estado', 'recibido');
            update_post_meta($don_id, '_ed_gratitud_id', $gratitud_id);

            // Actualizar estadísticas del receptor
            $dones_recibidos = absint(get_user_meta($user_id, '_ed_dones_recibidos', true));
            update_user_meta($user_id, '_ed_dones_recibidos', $dones_recibidos + 1);

            wp_send_json_success([
                'message' => __('¡Gracias por tu gratitud! Se ha publicado en el muro.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        $error_message = is_wp_error($gratitud_id)
            ? $gratitud_id->get_error_message()
            : __('Error al publicar gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN);
        wp_send_json_error(['message' => $error_message]);
    }

    /**
     * AJAX: Publicar nuevo don
     */
    public function ajax_publicar_don() {
        check_ajax_referer('ed_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? 'objetos');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $disponibilidad = sanitize_text_field($_POST['disponibilidad'] ?? '');
        $anonimo = isset($_POST['anonimo']);
        $user_id = get_current_user_id();

        if (!$titulo) {
            wp_send_json_error(['message' => __('El título es obligatorio', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $don_id = wp_insert_post([
            'post_type' => 'ed_don',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ], true);

        if (!is_wp_error($don_id) && !empty($don_id)) {
            update_post_meta($don_id, '_ed_categoria', $categoria);
            update_post_meta($don_id, '_ed_estado', 'disponible');
            update_post_meta($don_id, '_ed_ubicacion', $ubicacion);
            update_post_meta($don_id, '_ed_disponibilidad', $disponibilidad);
            update_post_meta($don_id, '_ed_anonimo', $anonimo ? '1' : '0');

            wp_send_json_success([
                'message' => __('¡Don publicado! Gracias por tu generosidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'don_id' => $don_id,
                'url' => get_permalink($don_id),
            ]);
        }

        $error_message = is_wp_error($don_id)
            ? $don_id->get_error_message()
            : __('Error al publicar el don', FLAVOR_PLATFORM_TEXT_DOMAIN);
        wp_send_json_error(['message' => $error_message]);
    }

    /**
     * Notifica al donante de una solicitud
     */
    private function notificar_donante($donante_id, $don_id, $solicitante_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $solicitante = get_userdata($solicitante_id);
        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $donante_id,
            __('Alguien quiere recibir tu don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(
                __('%s ha solicitado "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $solicitante->display_name,
                get_the_title($don_id)
            ),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => get_permalink($don_id),
            ]
        );
    }

    /**
     * Notifica al receptor que se ha entregado
     */
    private function notificar_receptor_entrega($receptor_id, $don_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $receptor_id,
            __('¡Has recibido un don!', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(
                __('El donante ha confirmado la entrega de "%s". ¡No olvides agradecer!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                get_the_title($don_id)
            ),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => get_permalink($don_id),
            ]
        );
    }

    /**
     * Shortcode: Listado de dones
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/listado-dones.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis dones
     */
    public function shortcode_mis_dones($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tus dones.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mis-dones.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Ofrecer don
     */
    public function shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ofrecer un don.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        include dirname(__FILE__) . '/templates/ofrecer-don.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Muro de gratitud
     */
    public function shortcode_muro_gratitud($atts) {
        $atts = shortcode_atts([
            'limite' => 20,
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/templates/muro-gratitud.php';
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

        $widget_path = dirname(__FILE__) . '/class-economia-don-widget.php';
        if (!class_exists('Flavor_Economia_Don_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Economia_Don_Widget')) {
            $registry->register(new Flavor_Economia_Don_Widget($this));
        }
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        // Cargar en páginas del CPT
        if (is_singular('ed_don') || is_post_type_archive('ed_don')) {
            return true;
        }

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'economia_don',
            'mis_dones',
            'ofrecer_don',
            'muro_gratitud',
            'flavor_don_listado',
            'flavor_don_mis_dones',
            'flavor_don_ofrecer',
            'flavor_don_muro_gratitud',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-economia-don',
            FLAVOR_PLATFORM_URL . 'includes/modules/economia-don/assets/css/economia-don.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-economia-don',
            FLAVOR_PLATFORM_URL . 'includes/modules/economia-don/assets/js/economia-don.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-economia-don', 'edData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ed_nonce'),
            'i18n' => [
                'confirmSolicitar' => __('¿Deseas solicitar este don?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmEntrega' => __('¿Confirmas que has entregado este don?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'gracias' => __('¡Gracias por tu generosidad!', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

        $dones_dados = absint(get_user_meta($user_id, '_ed_dones_dados', true));
        $dones_recibidos = absint(get_user_meta($user_id, '_ed_dones_recibidos', true));

        // Dones activos
        $dones_activos = get_posts([
            'post_type' => 'ed_don',
            'author' => $user_id,
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => '_ed_estado', 'value' => 'disponible'],
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        return [
            'dones_dados' => $dones_dados,
            'dones_recibidos' => $dones_recibidos,
            'dones_activos' => count($dones_activos),
        ];
    }

    /**
     * Valoración para el Sello de Conciencia
     */
    public function get_consciousness_valuation() {
        return [
            'nombre' => 'Economía del Don',
            'puntuacion' => 94,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'conciencia_fundamental' => 0.25,
                'interdependencia_radical' => 0.20,
                'valor_intrinseco' => 0.15,
                'madurez_ciclica' => 0.10,
            ],
            'descripcion_contribucion' => 'Facilita el flujo de recursos sin contabilidad ni intercambio, reconociendo que dar es un acto de abundancia y no de pérdida.',
            'categoria' => 'economia_alternativa',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Economía del Don - Guía de Uso**

La Economía del Don es un sistema donde se ofrece y se recibe sin esperar nada a cambio.

**Principios:**
- Dar por el placer de dar, no para recibir
- Lo que me sobra puede serle útil a otra persona
- No hay contabilidad ni puntos ni saldo
- La gratitud es el único retorno esperado

**Cómo funciona:**
1. Ofrece lo que te sobra o puedes compartir
2. Alguien lo solicita si lo necesita
3. Coordináis la entrega
4. El receptor expresa su gratitud en el muro

**Categorías de dones:**
- Objetos y cosas
- Alimentos
- Servicios y habilidades
- Tiempo y compañía
- Conocimiento
- Espacios

**Valores:**
- La abundancia se crea compartiendo
- Todos tenemos algo que ofrecer
- Recibir también es un acto generoso
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_dones' => [
                'description' => 'Ver dones disponibles en la comunidad',
                'params' => ['categoria'],
            ],
            'mis_dones' => [
                'description' => 'Ver mis dones ofrecidos y recibidos',
                'params' => [],
            ],
            'ofrecer_don' => [
                'description' => 'Ofrecer un don a la comunidad',
                'params' => ['tipo', 'descripcion'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'ofertas_disponibles',
            'listado' => 'ofertas_disponibles',
            'buscar' => 'ofertas_disponibles',
            'mis_items' => 'mis_intercambios',
            'mis-dones' => 'mis_intercambios',
            'crear' => 'ofrecer_don',
            'nuevo' => 'ofrecer_don',
            'foro' => 'foro_don',
            'chat' => 'chat_don',
            'multimedia' => 'multimedia_don',
            'red-social' => 'red_social_don',
            'red_social' => 'red_social_don',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'error' => __('Acción no implementada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    private function action_ofertas_disponibles($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[economia_don]'),
        ];
    }

    private function action_mis_intercambios($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[mis_dones]'),
        ];
    }

    private function action_ofrecer_don($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[ofrecer_don]'),
        ];
    }

    private function resolve_contextual_don($params = []) {
        global $wpdb;

        $don_id = absint(
            $params['don_id']
            ?? $params['id']
            ?? $_GET['don_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$don_id) {
            return null;
        }

        $tabla = $wpdb->prefix . 'flavor_economia_dones';
        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return null;
        }

        $don = $wpdb->get_row($wpdb->prepare(
            "SELECT id, titulo, descripcion FROM {$tabla} WHERE id = %d",
            $don_id
        ), ARRAY_A);

        if (!$don) {
            return null;
        }

        return $don;
    }

    private function action_foro_don($params) {
        $don = $this->resolve_contextual_don($params);
        if (!$don) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un don para ver su foro.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del don', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>'
            . '<p>' . esc_html($don['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="economia_don" entidad_id="' . absint($don['id']) . '"]')
            . '</div>';
    }

    private function action_chat_don($params) {
        $don = $this->resolve_contextual_don($params);
        if (!$don) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un don para ver su chat.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este don.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del don', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($don['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?don_id=' . absint($don['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="economia_don" entidad_id="' . absint($don['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_don($params) {
        $don = $this->resolve_contextual_don($params);
        if (!$don) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un don para ver sus archivos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del don', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($don['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?don_id=' . absint($don['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="economia_don" entidad_id="' . absint($don['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_don($params) {
        $don = $this->resolve_contextual_don($params);
        if (!$don) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un don para ver su actividad social.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este don.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del don', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($don['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?don_id=' . absint($don['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="economia_don" entidad_id="' . absint($don['id']) . '"]')
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'economia-don',
            'title'    => __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Dar y recibir sin esperar retorno directo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '🎁',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_economia_don',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Qué ofreces', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => ['objetos', 'alimentos', 'servicios', 'tiempo', 'conocimiento', 'espacios']],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'ubicacion'   => ['type' => 'text', 'label' => __('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'disponibilidad' => ['type' => 'text', 'label' => __('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'imagen'      => ['type' => 'image', 'label' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],

            'estados' => [
                'disponible' => ['label' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '🟢'],
                'reservado'  => ['label' => __('Reservado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '🟡'],
                'entregado'  => ['label' => __('Entregado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'blue', 'icon' => '✅'],
                'expirado'   => ['label' => __('Expirado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'gray', 'icon' => '⏰'],
            ],

            'stats' => [
                'dones_activos'  => ['label' => __('Dones activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🎁', 'color' => 'amber'],
                'dones_dados'    => ['label' => __('Dones dados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '💝', 'color' => 'rose'],
                'dones_recibidos' => ['label' => __('Dones recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🤲', 'color' => 'blue'],
                'participantes'  => ['label' => __('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👥', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'don-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['ubicacion', 'disponibilidad'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'dones' => [
                    'label'   => __('Dones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-heart',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'ofrecer' => [
                    'label'      => __('Ofrecer don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:economia_don_ofrecer',
                    'requires_login' => true,
                ],
                'mis-dones' => [
                    'label'      => __('Mis dones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:economia_don_mis_dones',
                    'requires_login' => true,
                ],
                'recibidos' => [
                    'label'      => __('Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-download',
                    'content'    => 'shortcode:economia_don_recibidos',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'fecha_publicacion',
                'order'      => 'DESC',
                'filterable' => ['categoria', 'zona'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'dones_recientes', 'mis_ofrecimientos', 'solicitudes_pendientes'],
                'actions' => [
                    'ofrecer'  => ['label' => __('Ofrecer don', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🎁', 'color' => 'amber'],
                    'explorar' => ['label' => __('Explorar dones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔍', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'matching'       => true,
                'chat'           => true,
                'valoraciones'   => true,
                'notificaciones' => true,
                'karma'          => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-economia-don-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Economia_Don_Dashboard_Tab')) {
                Flavor_Economia_Don_Dashboard_Tab::get_instance();
            }
        }
    }
}

if (!class_exists('Flavor_Chat_Economia_Don_Module', false)) {
    class_alias('Flavor_Platform_Economia_Don_Module', 'Flavor_Chat_Economia_Don_Module');
}
