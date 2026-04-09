<?php
/**
 * Módulo: Economía de Suficiencia
 *
 * Promueve un modelo económico basado en "suficiente" vs "máximo".
 * Enfocado en necesidades reales, bienestar y límites conscientes.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Economía de Suficiencia
 */
class Flavor_Chat_Economia_Suficiencia_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Categorías de necesidades (basadas en Max-Neef)
     */
    const CATEGORIAS_NECESIDADES = [
        'subsistencia' => [
            'nombre' => 'Subsistencia',
            'descripcion' => 'Alimentación, salud, vivienda, trabajo',
            'icono' => 'dashicons-heart',
            'color' => '#e74c3c',
        ],
        'proteccion' => [
            'nombre' => 'Protección',
            'descripcion' => 'Seguridad, cuidado, prevención, derechos',
            'icono' => 'dashicons-shield',
            'color' => '#3498db',
        ],
        'afecto' => [
            'nombre' => 'Afecto',
            'descripcion' => 'Amor, familia, amistades, intimidad',
            'icono' => 'dashicons-heart',
            'color' => '#e91e63',
        ],
        'entendimiento' => [
            'nombre' => 'Entendimiento',
            'descripcion' => 'Educación, conocimiento, curiosidad',
            'icono' => 'dashicons-book-alt',
            'color' => '#9c27b0',
        ],
        'participacion' => [
            'nombre' => 'Participación',
            'descripcion' => 'Comunidad, decisiones colectivas',
            'icono' => 'dashicons-groups',
            'color' => '#ff9800',
        ],
        'ocio' => [
            'nombre' => 'Ocio',
            'descripcion' => 'Descanso, tiempo libre, juego',
            'icono' => 'dashicons-admin-customizer',
            'color' => '#4caf50',
        ],
        'creacion' => [
            'nombre' => 'Creación',
            'descripcion' => 'Creatividad, trabajo significativo',
            'icono' => 'dashicons-art',
            'color' => '#00bcd4',
        ],
        'identidad' => [
            'nombre' => 'Identidad',
            'descripcion' => 'Pertenencia, autoestima, coherencia',
            'icono' => 'dashicons-admin-users',
            'color' => '#795548',
        ],
        'libertad' => [
            'nombre' => 'Libertad',
            'descripcion' => 'Autonomía, rebeldía, diferencia',
            'icono' => 'dashicons-flag',
            'color' => '#607d8b',
        ],
    ];

    /**
     * Tipos de compromisos de suficiencia
     */
    const TIPOS_COMPROMISO = [
        'reducir_consumo' => [
            'nombre' => 'Reducir consumo',
            'descripcion' => 'Comprar menos, usar lo que tengo',
            'icono' => 'dashicons-minus',
        ],
        'compartir' => [
            'nombre' => 'Compartir recursos',
            'descripcion' => 'Prestar, compartir, donar',
            'icono' => 'dashicons-share',
        ],
        'reparar' => [
            'nombre' => 'Reparar',
            'descripcion' => 'Arreglar en vez de comprar nuevo',
            'icono' => 'dashicons-admin-tools',
        ],
        'local' => [
            'nombre' => 'Consumir local',
            'descripcion' => 'Priorizar comercio de proximidad',
            'icono' => 'dashicons-location',
        ],
        'etico' => [
            'nombre' => 'Consumo ético',
            'descripcion' => 'Productos justos y sostenibles',
            'icono' => 'dashicons-yes-alt',
        ],
        'tiempo' => [
            'nombre' => 'Más tiempo, menos dinero',
            'descripcion' => 'Priorizar tiempo libre sobre ingresos',
            'icono' => 'dashicons-clock',
        ],
        'autoconsumo' => [
            'nombre' => 'Autoconsumo',
            'descripcion' => 'Producir lo que necesito',
            'icono' => 'dashicons-carrot',
        ],
        'desconectar' => [
            'nombre' => 'Desconectar',
            'descripcion' => 'Reducir dependencia tecnológica',
            'icono' => 'dashicons-smartphone',
        ],
    ];

    /**
     * Niveles de suficiencia
     */
    const NIVELES_SUFICIENCIA = [
        'explorando' => [
            'nombre' => 'Explorando',
            'descripcion' => 'Comenzando a cuestionar el consumismo',
            'puntos_min' => 0,
            'color' => '#95a5a6',
        ],
        'consciente' => [
            'nombre' => 'Consciente',
            'descripcion' => 'Identificando mis necesidades reales',
            'puntos_min' => 50,
            'color' => '#3498db',
        ],
        'practicante' => [
            'nombre' => 'Practicante',
            'descripcion' => 'Aplicando suficiencia activamente',
            'puntos_min' => 150,
            'color' => '#27ae60',
        ],
        'mentor' => [
            'nombre' => 'Mentor',
            'descripcion' => 'Inspirando a otros en suficiencia',
            'puntos_min' => 300,
            'color' => '#9b59b6',
        ],
        'sabio' => [
            'nombre' => 'Sabio/a',
            'descripcion' => 'Viviendo la suficiencia plenamente',
            'puntos_min' => 500,
            'color' => '#f39c12',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->module_id = 'economia_suficiencia';
        $this->module_name = __('Economía de Suficiencia', 'flavor-platform');
        $this->module_description = __('Promueve un modelo basado en "suficiente" vs "máximo"', 'flavor-platform');
        $this->module_icon = 'dashicons-editor-expand';
        $this->module_color = '#27ae60';
        $this->module_role = 'transversal';
        $this->ecosystem_teaches_modules = ['grupos_consumo', 'marketplace', 'comunidades'];
        $this->ecosystem_supports_modules = ['grupos_consumo', 'marketplace', 'comunidades'];
        $this->dashboard_transversal_priority = 30;
        $this->dashboard_client_contexts = ['consumo', 'suficiencia', 'aprendizaje', 'comunidad'];
        $this->dashboard_admin_contexts = ['consumo', 'aprendizaje', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['aprendizaje', 'economia_local'];
        $this->gailu_contribuye_a = ['resiliencia', 'autonomia'];

        parent::__construct();

        // Registrar en el Panel de Administración Unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
    }

    /**
     * Inicializa el módulo
     */
    public function init(): void {
        // Registrar post types en el hook 'init' de WordPress
        add_action('init', [$this, 'register_post_types'], 5);

        $this->register_ajax_handlers();
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs(): void {
        $tab_file = dirname(__FILE__) . '/class-economia-suficiencia-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Economia_Suficiencia_Dashboard_Tab')) {
                Flavor_Economia_Suficiencia_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Obtener reflexiones del usuario
        register_rest_route($namespace, '/economia-suficiencia/reflexiones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_reflexiones'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Obtener compromisos del usuario
        register_rest_route($namespace, '/economia-suficiencia/compromisos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_compromisos'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // Obtener estadísticas de la comunidad
        register_rest_route($namespace, '/economia-suficiencia/comunidad', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_estadisticas_comunidad'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);

        // Biblioteca de recursos
        register_rest_route($namespace, '/economia-suficiencia/biblioteca', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_biblioteca'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);
    }

    /**
     * Permite lecturas públicas explícitas.
     */
    public function public_read_permission(): bool {
        return true;
    }

    /**
     * API: Obtener reflexiones del usuario
     */
    public function api_get_reflexiones(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();

        $reflexiones = get_posts([
            'post_type' => 'es_reflexion',
            'author' => $user_id,
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $data = array_map(function($post) {
            return [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'contenido' => $post->post_content,
                'fecha' => $post->post_date,
            ];
        }, $reflexiones);

        return new \WP_REST_Response($data, 200);
    }

    /**
     * API: Obtener compromisos del usuario
     */
    public function api_get_compromisos(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();

        $compromisos = get_posts([
            'post_type' => 'es_compromiso',
            'author' => $user_id,
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $data = array_map(function($post) {
            return [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'descripcion' => $post->post_content,
                'estado' => get_post_meta($post->ID, '_es_estado', true) ?: 'activo',
                'progreso' => (int) get_post_meta($post->ID, '_es_progreso', true),
                'fecha_inicio' => $post->post_date,
            ];
        }, $compromisos);

        return new \WP_REST_Response($data, 200);
    }

    /**
     * API: Obtener estadísticas de la comunidad
     */
    public function api_get_estadisticas_comunidad(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;

        $total_reflexiones = wp_count_posts('es_reflexion')->publish ?? 0;
        $total_compromisos = wp_count_posts('es_compromiso')->publish ?? 0;
        $total_recursos = wp_count_posts('es_recurso')->publish ?? 0;
        $total_participantes = $wpdb->get_var("SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} WHERE post_type IN ('es_reflexion', 'es_compromiso') AND post_status = 'publish'");

        return new \WP_REST_Response([
            'reflexiones' => (int) $total_reflexiones,
            'compromisos' => (int) $total_compromisos,
            'recursos_compartidos' => (int) $total_recursos,
            'participantes' => (int) $total_participantes,
        ], 200);
    }

    /**
     * API: Obtener biblioteca de recursos
     */
    public function api_get_biblioteca(\WP_REST_Request $request): \WP_REST_Response {
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite') ?: 20;

        $args = [
            'post_type' => 'es_recurso',
            'posts_per_page' => $limite,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($categoria) {
            $args['meta_query'] = [
                [
                    'key' => '_es_categoria',
                    'value' => $categoria,
                ],
            ];
        }

        $recursos = get_posts($args);

        $data = array_map(function($post) {
            return [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'descripcion' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
                'categoria' => get_post_meta($post->ID, '_es_categoria', true),
                'tipo' => get_post_meta($post->ID, '_es_tipo', true),
                'autor' => get_the_author_meta('display_name', $post->post_author),
                'fecha' => $post->post_date,
            ];
        }, $recursos);

        return new \WP_REST_Response($data, 200);
    }

    /**
     * Registra los tipos de post personalizados
     */
    public function register_post_types(): void {
        // Reflexiones personales
        register_post_type('es_reflexion', [
            'labels' => [
                'name' => __('Reflexiones de Suficiencia', 'flavor-platform'),
                'singular_name' => __('Reflexión', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author'],
            'capability_type' => 'post',
        ]);

        // Compromisos de suficiencia
        register_post_type('es_compromiso', [
            'labels' => [
                'name' => __('Compromisos de Suficiencia', 'flavor-platform'),
                'singular_name' => __('Compromiso', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);

        // Registro de prácticas
        register_post_type('es_practica', [
            'labels' => [
                'name' => __('Prácticas de Suficiencia', 'flavor-platform'),
                'singular_name' => __('Práctica', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);

        // Recursos compartidos (biblioteca de objetos)
        register_post_type('es_recurso', [
            'labels' => [
                'name' => __('Biblioteca de Objetos', 'flavor-platform'),
                'singular_name' => __('Objeto', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Registra los manejadores AJAX
     */
    private function register_ajax_handlers(): void {
        add_action('wp_ajax_es_guardar_reflexion', [$this, 'ajax_guardar_reflexion']);
        add_action('wp_ajax_es_hacer_compromiso', [$this, 'ajax_hacer_compromiso']);
        add_action('wp_ajax_es_registrar_practica', [$this, 'ajax_registrar_practica']);
        add_action('wp_ajax_es_compartir_recurso', [$this, 'ajax_compartir_recurso']);
        add_action('wp_ajax_es_solicitar_prestamo', [$this, 'ajax_solicitar_prestamo']);
        add_action('wp_ajax_es_evaluar_necesidades', [$this, 'ajax_evaluar_necesidades']);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes(): void {
        // Shortcodes principales
        add_shortcode('suficiencia_intro', [$this, 'shortcode_intro']);
        add_shortcode('suficiencia_evaluacion', [$this, 'shortcode_evaluacion']);
        add_shortcode('suficiencia_compromisos', [$this, 'shortcode_compromisos']);
        add_shortcode('suficiencia_biblioteca', [$this, 'shortcode_biblioteca']);
        add_shortcode('suficiencia_mi_camino', [$this, 'shortcode_mi_camino']);

        // Aliases con prefijo flavor_ para compatibilidad con dynamic-pages.php
        add_shortcode('flavor_suficiencia_intro', [$this, 'shortcode_intro']);
        add_shortcode('flavor_suficiencia_evaluacion', [$this, 'shortcode_evaluacion']);
        add_shortcode('flavor_suficiencia_compromisos', [$this, 'shortcode_compromisos']);
        add_shortcode('flavor_suficiencia_biblioteca', [$this, 'shortcode_biblioteca']);
        add_shortcode('flavor_suficiencia_mi_camino', [$this, 'shortcode_mi_camino']);
    }

    /**
     * Encola los assets del módulo
     */
    public function enqueue_assets(): void {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-economia-suficiencia',
            $this->get_module_url() . 'assets/css/economia-suficiencia.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-economia-suficiencia',
            $this->get_module_url() . 'assets/js/economia-suficiencia.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-economia-suficiencia', 'flavorSuficiencia', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('suficiencia_nonce'),
            'categorias' => self::CATEGORIAS_NECESIDADES,
            'compromisos' => self::TIPOS_COMPROMISO,
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

        $post_content = $post->post_content ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Shortcodes originales
        $shortcodes_principales = [
            'suficiencia_intro',
            'suficiencia_evaluacion',
            'suficiencia_compromisos',
            'suficiencia_biblioteca',
            'suficiencia_mi_camino',
        ];

        // Verificar shortcodes principales
        foreach ($shortcodes_principales as $shortcode) {
            if (has_shortcode($post_content, $shortcode)) {
                return true;
            }
        }

        // Verificar aliases con prefijo flavor_
        foreach ($shortcodes_principales as $shortcode) {
            if (has_shortcode($post_content, 'flavor_' . $shortcode)) {
                return true;
            }
        }

        // Verificar por URL
        return strpos($request_uri, '/economia-suficiencia') !== false;
    }

    /**
     * AJAX: Guardar reflexión
     */
    public function ajax_guardar_reflexion(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $pregunta = sanitize_text_field($_POST['pregunta'] ?? '');
        $respuesta = sanitize_textarea_field($_POST['respuesta'] ?? '');

        if (empty($respuesta)) {
            wp_send_json_error(['message' => __('La reflexión no puede estar vacía', 'flavor-platform')]);
        }

        $reflexion_id = wp_insert_post([
            'post_type' => 'es_reflexion',
            'post_status' => 'private',
            'post_author' => $user_id,
            'post_title' => $pregunta ?: __('Reflexión personal', 'flavor-platform'),
            'post_content' => $respuesta,
        ], true);

        if (is_wp_error($reflexion_id) || empty($reflexion_id)) {
            $error = is_wp_error($reflexion_id) ? $reflexion_id->get_error_message() : __('No se pudo guardar la reflexión.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($reflexion_id, '_es_categoria', $categoria);
        update_post_meta($reflexion_id, '_es_fecha', current_time('mysql'));

        // Sumar puntos
        $this->sumar_puntos($user_id, 5, 'reflexion');

        wp_send_json_success([
            'message' => __('Reflexión guardada', 'flavor-platform'),
            'reflexion_id' => $reflexion_id,
        ]);
    }

    /**
     * AJAX: Hacer compromiso de suficiencia
     */
    public function ajax_hacer_compromiso(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $duracion = sanitize_text_field($_POST['duracion'] ?? '30');

        if (!isset(self::TIPOS_COMPROMISO[$tipo])) {
            wp_send_json_error(['message' => __('Tipo de compromiso no válido', 'flavor-platform')]);
        }

        $compromiso_data = self::TIPOS_COMPROMISO[$tipo];

        $compromiso_id = wp_insert_post([
            'post_type' => 'es_compromiso',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $compromiso_data['nombre'],
        ], true);

        if (is_wp_error($compromiso_id) || empty($compromiso_id)) {
            $error = is_wp_error($compromiso_id) ? $compromiso_id->get_error_message() : __('No se pudo crear el compromiso.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($compromiso_id, '_es_tipo', $tipo);
        update_post_meta($compromiso_id, '_es_descripcion', $descripcion);
        update_post_meta($compromiso_id, '_es_duracion_dias', intval($duracion));
        update_post_meta($compromiso_id, '_es_fecha_inicio', current_time('mysql'));
        update_post_meta($compromiso_id, '_es_fecha_fin', date('Y-m-d', strtotime("+{$duracion} days")));
        update_post_meta($compromiso_id, '_es_estado', 'activo');
        update_post_meta($compromiso_id, '_es_dias_cumplidos', 0);

        // Sumar puntos
        $this->sumar_puntos($user_id, 10, 'compromiso');

        wp_send_json_success([
            'message' => __('¡Compromiso adquirido!', 'flavor-platform'),
            'compromiso_id' => $compromiso_id,
        ]);
    }

    /**
     * AJAX: Registrar práctica de suficiencia
     */
    public function ajax_registrar_practica(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $compromiso_id = intval($_POST['compromiso_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        // Verificar que el compromiso existe y pertenece al usuario
        $compromiso = get_post($compromiso_id);
        if (!$compromiso || (int) $compromiso->post_author !== $user_id) {
            wp_send_json_error(['message' => __('Compromiso no encontrado', 'flavor-platform')]);
        }

        $practica_id = wp_insert_post([
            'post_type' => 'es_practica',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf(__('Práctica: %s', 'flavor-platform'), $compromiso->post_title),
        ], true);

        if (is_wp_error($practica_id) || empty($practica_id)) {
            $error = is_wp_error($practica_id) ? $practica_id->get_error_message() : __('No se pudo registrar la práctica.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($practica_id, '_es_compromiso_id', $compromiso_id);
        update_post_meta($practica_id, '_es_fecha', current_time('mysql'));
        update_post_meta($practica_id, '_es_notas', $notas);

        // Actualizar contador del compromiso
        $dias_cumplidos = intval(get_post_meta($compromiso_id, '_es_dias_cumplidos', true));
        update_post_meta($compromiso_id, '_es_dias_cumplidos', $dias_cumplidos + 1);

        // Sumar puntos
        $this->sumar_puntos($user_id, 3, 'practica');

        wp_send_json_success([
            'message' => __('¡Práctica registrada!', 'flavor-platform'),
            'dias_cumplidos' => $dias_cumplidos + 1,
        ]);
    }

    /**
     * AJAX: Compartir recurso en biblioteca
     */
    public function ajax_compartir_recurso(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $condiciones = sanitize_textarea_field($_POST['condiciones'] ?? '');

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es requerido', 'flavor-platform')]);
        }

        $recurso_id = wp_insert_post([
            'post_type' => 'es_recurso',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $nombre,
            'post_content' => $descripcion,
        ], true);

        if (is_wp_error($recurso_id) || empty($recurso_id)) {
            $error = is_wp_error($recurso_id) ? $recurso_id->get_error_message() : __('No se pudo crear el recurso.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($recurso_id, '_es_categoria', $categoria);
        update_post_meta($recurso_id, '_es_condiciones', $condiciones);
        update_post_meta($recurso_id, '_es_estado', 'disponible');
        update_post_meta($recurso_id, '_es_prestamos', 0);

        // Sumar puntos
        $this->sumar_puntos($user_id, 15, 'compartir_recurso');

        wp_send_json_success([
            'message' => __('¡Recurso añadido a la biblioteca!', 'flavor-platform'),
            'recurso_id' => $recurso_id,
        ]);
    }

    /**
     * AJAX: Solicitar préstamo
     */
    public function ajax_solicitar_prestamo(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $recurso_id = intval($_POST['recurso_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $dias = intval($_POST['dias'] ?? 7);

        $recurso = get_post($recurso_id);
        if (!$recurso || $recurso->post_type !== 'es_recurso') {
            wp_send_json_error(['message' => __('Recurso no encontrado', 'flavor-platform')]);
        }

        $estado = get_post_meta($recurso_id, '_es_estado', true);
        if ($estado !== 'disponible') {
            wp_send_json_error(['message' => __('Este recurso no está disponible', 'flavor-platform')]);
        }

        // Actualizar estado
        update_post_meta($recurso_id, '_es_estado', 'prestado');
        update_post_meta($recurso_id, '_es_prestado_a', $user_id);
        update_post_meta($recurso_id, '_es_fecha_prestamo', current_time('mysql'));
        update_post_meta($recurso_id, '_es_fecha_devolucion', date('Y-m-d', strtotime("+{$dias} days")));

        // Incrementar contador
        $prestamos = intval(get_post_meta($recurso_id, '_es_prestamos', true));
        update_post_meta($recurso_id, '_es_prestamos', $prestamos + 1);

        // Notificar al propietario
        $propietario_id = $recurso->post_author;
        $this->notificar_prestamo_recurso($propietario_id, $user_id, $recurso, $dias, $mensaje);

        wp_send_json_success([
            'message' => __('Préstamo solicitado. El propietario ha sido notificado.', 'flavor-platform'),
        ]);
    }

    /**
     * Notifica al propietario cuando un recurso ha sido prestado.
     *
     * @param int     $propietario_id
     * @param int     $solicitante_id
     * @param WP_Post $recurso
     * @param int     $dias
     * @param string  $mensaje
     * @return void
     */
    private function notificar_prestamo_recurso($propietario_id, $solicitante_id, $recurso, $dias, $mensaje = ''): void {
        $propietario_id = absint($propietario_id);
        $solicitante_id = absint($solicitante_id);

        if (!$propietario_id || !$solicitante_id || $propietario_id === $solicitante_id) {
            return;
        }

        $propietario = get_userdata($propietario_id);
        $solicitante = get_userdata($solicitante_id);

        if (!$propietario || empty($propietario->user_email) || !$solicitante) {
            return;
        }

        $subject = sprintf(
            __('Nuevo préstamo solicitado para "%s"', 'flavor-platform'),
            $recurso->post_title
        );

        $body_lines = [
            sprintf(__('Hola %s,', 'flavor-platform'), $propietario->display_name ?: __('propietario', 'flavor-platform')),
            '',
            sprintf(
                __('%s ha solicitado el recurso "%s" durante %d días.', 'flavor-platform'),
                $solicitante->display_name ?: __('Un usuario', 'flavor-platform'),
                $recurso->post_title,
                max(1, $dias)
            ),
        ];

        if ($mensaje !== '') {
            $body_lines[] = '';
            $body_lines[] = __('Mensaje del solicitante:', 'flavor-platform');
            $body_lines[] = $mensaje;
        }

        $body_lines[] = '';
        $body_lines[] = __('Puedes revisar el recurso desde tu panel de economía de suficiencia.', 'flavor-platform');

        wp_mail($propietario->user_email, $subject, implode("\n", $body_lines));

        do_action('flavor_es_recurso_prestado_notificado', $propietario_id, $solicitante_id, $recurso->ID, [
            'dias' => max(1, $dias),
            'mensaje' => $mensaje,
        ]);
    }

    /**
     * AJAX: Evaluar necesidades personales
     */
    public function ajax_evaluar_necesidades(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $evaluaciones = $_POST['evaluaciones'] ?? [];

        if (empty($evaluaciones) || !is_array($evaluaciones)) {
            wp_send_json_error(['message' => __('Datos de evaluación no válidos', 'flavor-platform')]);
        }

        // Guardar evaluación
        $evaluacion_actual = [];
        foreach ($evaluaciones as $categoria => $valor) {
            $categoria = sanitize_key($categoria);
            $valor = intval($valor);
            if (isset(self::CATEGORIAS_NECESIDADES[$categoria]) && $valor >= 1 && $valor <= 5) {
                $evaluacion_actual[$categoria] = $valor;
            }
        }

        update_user_meta($user_id, '_es_evaluacion_necesidades', $evaluacion_actual);
        update_user_meta($user_id, '_es_fecha_evaluacion', current_time('mysql'));

        // Sumar puntos
        $this->sumar_puntos($user_id, 10, 'evaluacion');

        // Calcular áreas a mejorar
        $areas_mejorar = [];
        foreach ($evaluacion_actual as $cat => $valor) {
            if ($valor < 3) {
                $areas_mejorar[] = self::CATEGORIAS_NECESIDADES[$cat]['nombre'];
            }
        }

        wp_send_json_success([
            'message' => __('Evaluación guardada', 'flavor-platform'),
            'areas_mejorar' => $areas_mejorar,
        ]);
    }

    /**
     * Suma puntos de suficiencia al usuario
     */
    private function sumar_puntos(int $user_id, int $puntos, string $tipo): void {
        $puntos_actuales = intval(get_user_meta($user_id, '_es_puntos_suficiencia', true));
        update_user_meta($user_id, '_es_puntos_suficiencia', $puntos_actuales + $puntos);

        // Registrar historial
        $historial = get_user_meta($user_id, '_es_historial_puntos', true) ?: [];
        $historial[] = [
            'puntos' => $puntos,
            'tipo' => $tipo,
            'fecha' => current_time('mysql'),
        ];
        update_user_meta($user_id, '_es_historial_puntos', $historial);
    }

    /**
     * Obtiene el nivel de suficiencia del usuario
     */
    public function get_nivel_usuario(int $user_id): array {
        $puntos = intval(get_user_meta($user_id, '_es_puntos_suficiencia', true));

        $nivel_actual = self::NIVELES_SUFICIENCIA['explorando'];
        foreach (self::NIVELES_SUFICIENCIA as $nivel_id => $nivel_data) {
            if ($puntos >= $nivel_data['puntos_min']) {
                $nivel_actual = $nivel_data;
                $nivel_actual['id'] = $nivel_id;
            }
        }

        // Calcular siguiente nivel
        $siguiente_nivel = null;
        $encontrado_actual = false;
        foreach (self::NIVELES_SUFICIENCIA as $nivel_id => $nivel_data) {
            if ($encontrado_actual) {
                $siguiente_nivel = $nivel_data;
                $siguiente_nivel['id'] = $nivel_id;
                break;
            }
            if ($nivel_data['puntos_min'] === $nivel_actual['puntos_min']) {
                $encontrado_actual = true;
            }
        }

        return [
            'puntos' => $puntos,
            'nivel' => $nivel_actual,
            'siguiente_nivel' => $siguiente_nivel,
            'progreso' => $siguiente_nivel
                ? (($puntos - $nivel_actual['puntos_min']) / ($siguiente_nivel['puntos_min'] - $nivel_actual['puntos_min'])) * 100
                : 100,
        ];
    }

    /**
     * Obtiene estadísticas del usuario
     */
    public function get_estadisticas_usuario(int $user_id): array {
        global $wpdb;

        // Compromisos activos
        $compromisos_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_compromiso'
               AND p.post_author = %d
               AND pm.meta_key = '_es_estado'
               AND pm.meta_value = 'activo'",
            $user_id
        ));

        // Prácticas este mes
        $practicas_mes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_practica'
               AND p.post_author = %d
               AND pm.meta_key = '_es_fecha'
               AND pm.meta_value >= %s",
            $user_id, date('Y-m-01')
        ));

        // Recursos compartidos
        $recursos_compartidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'es_recurso' AND post_author = %d",
            $user_id
        ));

        // Reflexiones
        $reflexiones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'es_reflexion' AND post_author = %d",
            $user_id
        ));

        // Evaluación de necesidades
        $evaluacion = get_user_meta($user_id, '_es_evaluacion_necesidades', true) ?: [];

        return [
            'compromisos_activos' => intval($compromisos_activos),
            'practicas_mes' => intval($practicas_mes),
            'recursos_compartidos' => intval($recursos_compartidos),
            'reflexiones' => intval($reflexiones),
            'evaluacion_necesidades' => $evaluacion,
            'nivel' => $this->get_nivel_usuario($user_id),
        ];
    }

    /**
     * Shortcode: Introducción a la suficiencia
     */
    public function shortcode_intro($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/intro.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Evaluación de necesidades
     */
    public function shortcode_evaluacion($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="es-login-required">' . __('Inicia sesión para evaluar tus necesidades', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/evaluacion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Compromisos
     */
    public function shortcode_compromisos($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="es-login-required">' . __('Inicia sesión para hacer compromisos', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/compromisos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Biblioteca de objetos
     */
    public function shortcode_biblioteca($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/biblioteca.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi camino
     */
    public function shortcode_mi_camino($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="es-login-required">' . __('Inicia sesión para ver tu camino', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/mi-camino.php';
        return ob_get_clean();
    }

    /**
     * Registra el widget de dashboard
     */
    public function register_dashboard_widget($registry): void {
        $widget_file = $this->get_module_path() . 'class-economia-suficiencia-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('Flavor_Economia_Suficiencia_Widget')) {
                $registry->register(new Flavor_Economia_Suficiencia_Widget($this));
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
            'nombre' => 'Economía de Suficiencia',
            'puntuacion' => 89,
            'premisas' => [
                'conciencia_fundamental' => 0.30, // Cuestionar el "más es mejor"
                'abundancia_organizable' => 0.25, // Lo suficiente es abundancia
                'valor_intrinseco' => 0.20, // Bienestar vs posesión
                'madurez_ciclica' => 0.15, // Ciclos de necesidades
                'interdependencia_radical' => 0.10, // Compartir recursos
            ],
            'descripcion_contribucion' => 'Este módulo cuestiona la lógica del crecimiento infinito, ' .
                'promoviendo una economía basada en necesidades reales y bienestar colectivo. ' .
                'Inspira la reflexión sobre qué es "suficiente" para vivir bien.',
            'categoria' => 'economia_consciente',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'mis_compromisos' => [
                'description' => 'Ver mis compromisos de suficiencia',
                'params' => [],
            ],
            'registrar_practica' => [
                'description' => 'Registrar una práctica de suficiencia',
                'params' => ['tipo', 'descripcion'],
            ],
            'ver_nivel' => [
                'description' => 'Ver mi nivel de suficiencia',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'mis_compromisos',
            'listado' => 'mis_compromisos',
            'mis_items' => 'mis_compromisos',
            'mis-compromisos' => 'mis_compromisos',
            'crear' => 'registrar_practica',
            'nuevo' => 'registrar_practica',
            'nivel' => 'ver_nivel',
            'mi_camino' => 'ver_nivel',
            'foro' => 'foro_recurso',
            'chat' => 'chat_recurso',
            'multimedia' => 'multimedia_recurso',
            'red-social' => 'red_social_recurso',
            'red_social' => 'red_social_recurso',
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

    /**
     * Acción: ver compromisos.
     */
    private function action_mis_compromisos($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_suficiencia_compromisos]'),
        ];
    }

    /**
     * Acción: registrar práctica o mostrar evaluación.
     */
    private function action_registrar_practica($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_suficiencia_evaluacion]'),
        ];
    }

    /**
     * Acción: ver nivel/camino.
     */
    private function action_ver_nivel($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_suficiencia_mi_camino]'),
        ];
    }

    /**
     * Resolver recurso contextual para tabs satélite.
     *
     * @param array $params
     * @return WP_Post|null
     */
    private function resolve_contextual_recurso($params = []) {
        $recurso_id = absint(
            $params['recurso_id']
            ?? $params['id']
            ?? $_GET['recurso_id']
            ?? 0
        );

        if (!$recurso_id) {
            return null;
        }

        $recurso = get_post($recurso_id);
        if (!$recurso || $recurso->post_type !== 'es_recurso') {
            return null;
        }

        return $recurso;
    }

    private function action_foro_recurso($params) {
        $recurso = $this->resolve_contextual_recurso($params);
        if (!$recurso) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado el recurso contextual.', 'flavor-platform'),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Foro del recurso', 'flavor-platform') . '</h3>';
        $html .= '<p>' . esc_html($recurso->post_title) . '</p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_foros_integrado entidad="es_recurso" entidad_id="' . absint($recurso->ID) . '"]');

        return $html;
    }

    private function action_chat_recurso($params) {
        if (!is_user_logged_in()) {
            return '<div class="notice notice-info"><p>' . esc_html__('Debes iniciar sesión para acceder al chat del recurso.', 'flavor-platform') . '</p></div>';
        }

        $recurso = $this->resolve_contextual_recurso($params);
        if (!$recurso) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado el recurso contextual.', 'flavor-platform'),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Chat del recurso', 'flavor-platform') . '</h3>';
        $html .= '<p>' . esc_html($recurso->post_title) . '</p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_chat_grupo_integrado entidad="es_recurso" entidad_id="' . absint($recurso->ID) . '"]');

        return $html;
    }

    private function action_multimedia_recurso($params) {
        $recurso = $this->resolve_contextual_recurso($params);
        if (!$recurso) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado el recurso contextual.', 'flavor-platform'),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Multimedia del recurso', 'flavor-platform') . '</h3>';
        $html .= '<p>' . esc_html($recurso->post_title) . '</p>';
        $html .= '<p><a class="button button-primary" href="' . esc_url(home_url('/mi-portal/multimedia/subir/?recurso_id=' . absint($recurso->ID))) . '">' . esc_html__('Subir archivo', 'flavor-platform') . '</a></p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_multimedia_galeria entidad="es_recurso" entidad_id="' . absint($recurso->ID) . '"]');

        return $html;
    }

    private function action_red_social_recurso($params) {
        if (!is_user_logged_in()) {
            return '<div class="notice notice-info"><p>' . esc_html__('Debes iniciar sesión para ver la actividad social del recurso.', 'flavor-platform') . '</p></div>';
        }

        $recurso = $this->resolve_contextual_recurso($params);
        if (!$recurso) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado el recurso contextual.', 'flavor-platform'),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Actividad social del recurso', 'flavor-platform') . '</h3>';
        $html .= '<p>' . esc_html($recurso->post_title) . '</p>';
        $html .= '<p><a class="button button-primary" href="' . esc_url(home_url('/mi-portal/red-social/crear/?recurso_id=' . absint($recurso->ID))) . '">' . esc_html__('Publicar', 'flavor-platform') . '</a></p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_social_feed entidad="es_recurso" entidad_id="' . absint($recurso->ID) . '"]');

        return $html;
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
        return __('La Economía de Suficiencia promueve un modelo basado en "suficiente" vs "máximo", enfocándose en necesidades reales y bienestar colectivo.', 'flavor-platform');
    }

    // =========================================================================
    // PANEL DE ADMINISTRACIÓN UNIFICADO
    // =========================================================================

    /**
     * Configuración del módulo para el Panel de Administración Unificado
     *
     * Define las páginas de admin, categoría y callbacks de renderizado.
     * El mapping 'economia_suficiencia' => 'suficiencia-dashboard' se establece
     * automáticamente al usar el slug de la primera página.
     *
     * @return array Configuración completa del módulo para admin
     */
    public function get_admin_config(): array {
        return [
            'id'         => 'economia_suficiencia',
            'label'      => __('Economía de Suficiencia', 'flavor-platform'),
            'icon'       => 'dashicons-editor-expand',
            'capability' => 'manage_options',
            'categoria'  => 'sostenibilidad',
            'paginas'    => [
                [
                    'slug'     => 'suficiencia-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                    'badge'    => [$this, 'contar_compromisos_pendientes'],
                ],
                [
                    'slug'     => 'suficiencia-usuarios',
                    'titulo'   => __('Usuarios', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_usuarios'],
                ],
                [
                    'slug'     => 'suficiencia-compromisos',
                    'titulo'   => __('Compromisos', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_compromisos'],
                ],
                [
                    'slug'     => 'suficiencia-biblioteca',
                    'titulo'   => __('Biblioteca', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_biblioteca'],
                ],
                [
                    'slug'     => 'suficiencia-config',
                    'titulo'   => __('Configuración', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas'     => [$this, 'get_estadisticas_admin'],
            'dashboard_widget' => [$this, 'render_admin_widget'],
        ];
    }

    /**
     * Obtiene estadísticas para el dashboard unificado
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_admin(): array {
        global $wpdb;

        $total_usuarios_participando = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author)
             FROM {$wpdb->posts}
             WHERE post_type IN ('es_reflexion', 'es_compromiso', 'es_practica')
             AND post_status = 'publish'"
        );

        $total_compromisos_activos = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_compromiso'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_es_estado'
             AND pm.meta_value = 'activo'"
        );

        $total_recursos_compartidos = wp_count_posts('es_recurso')->publish ?? 0;

        return [
            [
                'icon'   => 'dashicons-groups',
                'valor'  => intval($total_usuarios_participando),
                'label'  => __('Participantes', 'flavor-platform'),
                'color'  => 'green',
                'enlace' => admin_url('admin.php?page=suficiencia-usuarios'),
            ],
            [
                'icon'   => 'dashicons-heart',
                'valor'  => intval($total_compromisos_activos),
                'label'  => __('Compromisos activos', 'flavor-platform'),
                'color'  => 'blue',
                'enlace' => admin_url('admin.php?page=suficiencia-compromisos'),
            ],
            [
                'icon'   => 'dashicons-share',
                'valor'  => intval($total_recursos_compartidos),
                'label'  => __('Recursos compartidos', 'flavor-platform'),
                'color'  => 'purple',
                'enlace' => admin_url('admin.php?page=suficiencia-biblioteca'),
            ],
        ];
    }

    /**
     * Cuenta compromisos pendientes de revisión (para badge)
     *
     * @return int Número de compromisos pendientes
     */
    public function contar_compromisos_pendientes(): int {
        global $wpdb;

        $pendientes = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_compromiso'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_es_estado'
             AND pm.meta_value = 'pendiente_revision'"
        );

        return intval($pendientes);
    }

    /**
     * Renderiza el widget del dashboard unificado
     */
    public function render_admin_widget(): void {
        global $wpdb;

        $estadisticas_recientes = $wpdb->get_row(
            "SELECT
                (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'es_practica' AND post_status = 'publish' AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as practicas_semana,
                (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'es_compromiso' AND post_status = 'publish' AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as compromisos_semana
            "
        );
        ?>
        <div class="suficiencia-widget-resumen">
            <p>
                <strong><?php echo intval($estadisticas_recientes->practicas_semana ?? 0); ?></strong>
                <?php _e('prácticas registradas esta semana', 'flavor-platform'); ?>
            </p>
            <p>
                <strong><?php echo intval($estadisticas_recientes->compromisos_semana ?? 0); ?></strong>
                <?php _e('nuevos compromisos esta semana', 'flavor-platform'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Dashboard de administración
     */
    public function render_admin_dashboard(): void {
        global $wpdb;

        // Obtener estadísticas generales
        $total_usuarios = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author)
             FROM {$wpdb->posts}
             WHERE post_type IN ('es_reflexion', 'es_compromiso', 'es_practica')
             AND post_status = 'publish'"
        );

        $total_compromisos_activos = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_compromiso'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_es_estado'
             AND pm.meta_value = 'activo'"
        );

        $total_practicas = wp_count_posts('es_practica')->publish ?? 0;
        $total_recursos = wp_count_posts('es_recurso')->publish ?? 0;
        $total_reflexiones = wp_count_posts('es_reflexion')->publish ?? 0;

        // Calcular progreso comunitario (promedio de puntos por usuario)
        $promedio_puntos = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS UNSIGNED))
             FROM {$wpdb->usermeta}
             WHERE meta_key = '_es_puntos_suficiencia'"
        );

        // Usuarios más activos
        $usuarios_activos = $wpdb->get_results(
            "SELECT u.ID, u.display_name,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = '_es_puntos_suficiencia') as puntos,
                    COUNT(p.ID) as actividad
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->posts} p ON p.post_author = u.ID
             WHERE p.post_type IN ('es_reflexion', 'es_compromiso', 'es_practica')
             AND p.post_status = 'publish'
             GROUP BY u.ID
             ORDER BY actividad DESC
             LIMIT 5"
        );

        // Compromisos más populares
        $compromisos_populares = $wpdb->get_results(
            "SELECT pm.meta_value as tipo, COUNT(*) as total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'es_compromiso'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_es_tipo'
             GROUP BY pm.meta_value
             ORDER BY total DESC
             LIMIT 5"
        );

        // Actividad reciente
        $actividad_reciente = $wpdb->get_results(
            "SELECT p.ID, p.post_type, p.post_title, p.post_date, u.display_name
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
             WHERE p.post_type IN ('es_reflexion', 'es_compromiso', 'es_practica', 'es_recurso')
             AND p.post_status = 'publish'
             ORDER BY p.post_date DESC
             LIMIT 10"
        );
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Dashboard - Economía de Suficiencia', 'flavor-platform')); ?>

            <!-- KPIs principales -->
            <div class="flavor-admin-kpis" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-groups" style="font-size: 32px; color: #27ae60;"></span>
                    <h3 style="margin: 10px 0 5px; font-size: 28px;"><?php echo intval($total_usuarios); ?></h3>
                    <p style="color: #666; margin: 0;"><?php _e('Usuarios participando', 'flavor-platform'); ?></p>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-heart" style="font-size: 32px; color: #e74c3c;"></span>
                    <h3 style="margin: 10px 0 5px; font-size: 28px;"><?php echo intval($total_compromisos_activos); ?></h3>
                    <p style="color: #666; margin: 0;"><?php _e('Compromisos activos', 'flavor-platform'); ?></p>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-chart-bar" style="font-size: 32px; color: #3498db;"></span>
                    <h3 style="margin: 10px 0 5px; font-size: 28px;"><?php echo number_format(floatval($promedio_puntos), 1); ?></h3>
                    <p style="color: #666; margin: 0;"><?php _e('Progreso comunitario (promedio)', 'flavor-platform'); ?></p>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-share" style="font-size: 32px; color: #9b59b6;"></span>
                    <h3 style="margin: 10px 0 5px; font-size: 28px;"><?php echo intval($total_recursos); ?></h3>
                    <p style="color: #666; margin: 0;"><?php _e('Recursos compartidos', 'flavor-platform'); ?></p>
                </div>
            </div>

            <div class="flavor-admin-columns" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Usuarios más activos -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2><?php _e('Usuarios más activos', 'flavor-platform'); ?></h2>
                    <?php if ($usuarios_activos): ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                                    <th><?php _e('Puntos', 'flavor-platform'); ?></th>
                                    <th><?php _e('Actividad', 'flavor-platform'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_activos as $usuario): ?>
                                    <tr>
                                        <td>
                                            <?php echo get_avatar($usuario->ID, 24); ?>
                                            <?php echo esc_html($usuario->display_name); ?>
                                        </td>
                                        <td><strong><?php echo intval($usuario->puntos); ?></strong></td>
                                        <td><?php echo intval($usuario->actividad); ?> <?php _e('acciones', 'flavor-platform'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="description"><?php _e('Aún no hay usuarios participando.', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Compromisos más populares -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2><?php _e('Compromisos más populares', 'flavor-platform'); ?></h2>
                    <?php if ($compromisos_populares): ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($compromisos_populares as $compromiso):
                                $tipo_info = self::TIPOS_COMPROMISO[$compromiso->tipo] ?? ['nombre' => $compromiso->tipo, 'icono' => 'dashicons-marker'];
                            ?>
                                <li style="padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                    <span>
                                        <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                                        <?php echo esc_html($tipo_info['nombre']); ?>
                                    </span>
                                    <strong><?php echo intval($compromiso->total); ?> <?php _e('personas', 'flavor-platform'); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="description"><?php _e('Aún no hay compromisos registrados.', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
                <h2><?php _e('Actividad reciente', 'flavor-platform'); ?></h2>
                <?php if ($actividad_reciente): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                                <th><?php _e('Tipo', 'flavor-platform'); ?></th>
                                <th><?php _e('Descripción', 'flavor-platform'); ?></th>
                                <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividad_reciente as $actividad):
                                $tipo_labels = [
                                    'es_reflexion' => __('Reflexión', 'flavor-platform'),
                                    'es_compromiso' => __('Compromiso', 'flavor-platform'),
                                    'es_practica' => __('Práctica', 'flavor-platform'),
                                    'es_recurso' => __('Recurso', 'flavor-platform'),
                                ];
                            ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n('j M Y, H:i', strtotime($actividad->post_date))); ?></td>
                                    <td><span class="flavor-badge"><?php echo esc_html($tipo_labels[$actividad->post_type] ?? $actividad->post_type); ?></span></td>
                                    <td><?php echo esc_html($actividad->post_title); ?></td>
                                    <td><?php echo esc_html($actividad->display_name); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="description"><?php _e('No hay actividad reciente.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de gestión de usuarios
     */
    public function render_admin_usuarios(): void {
        global $wpdb;

        // Paginación
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $items_por_pagina = 20;
        $offset = ($pagina_actual - 1) * $items_por_pagina;

        // Filtros
        $filtro_nivel = isset($_GET['nivel']) ? sanitize_key($_GET['nivel']) : '';
        $buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Construir query
        $query_where = "WHERE p.post_type IN ('es_reflexion', 'es_compromiso', 'es_practica') AND p.post_status = 'publish'";
        $query_params = [];

        if ($buscar) {
            $query_where .= " AND u.display_name LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($buscar) . '%';
        }

        // Obtener usuarios con su actividad
        $total_usuarios = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.post_author)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
                 {$query_where}",
                ...$query_params
            )
        );

        $usuarios = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.ID, u.display_name, u.user_email, u.user_registered,
                        COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = '_es_puntos_suficiencia'), 0) as puntos,
                        COUNT(p.ID) as total_actividad,
                        SUM(CASE WHEN p.post_type = 'es_compromiso' THEN 1 ELSE 0 END) as total_compromisos,
                        SUM(CASE WHEN p.post_type = 'es_practica' THEN 1 ELSE 0 END) as total_practicas,
                        SUM(CASE WHEN p.post_type = 'es_reflexion' THEN 1 ELSE 0 END) as total_reflexiones
                 FROM {$wpdb->users} u
                 INNER JOIN {$wpdb->posts} p ON p.post_author = u.ID
                 {$query_where}
                 GROUP BY u.ID
                 ORDER BY puntos DESC
                 LIMIT %d OFFSET %d",
                ...array_merge($query_params, [$items_por_pagina, $offset])
            )
        );

        $total_paginas = ceil($total_usuarios / $items_por_pagina);
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Usuarios - Economía de Suficiencia', 'flavor-platform')); ?>

            <!-- Filtros -->
            <div class="tablenav top">
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="suficiencia-usuarios">
                    <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar usuario...', 'flavor-platform'); ?>">
                    <select name="nivel">
                        <option value=""><?php _e('Todos los niveles', 'flavor-platform'); ?></option>
                        <?php foreach (self::NIVELES_SUFICIENCIA as $nivel_id => $nivel_data): ?>
                            <option value="<?php echo esc_attr($nivel_id); ?>" <?php selected($filtro_nivel, $nivel_id); ?>>
                                <?php echo esc_html($nivel_data['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>
                </form>
            </div>

            <!-- Tabla de usuarios -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                        <th><?php _e('Email', 'flavor-platform'); ?></th>
                        <th><?php _e('Nivel', 'flavor-platform'); ?></th>
                        <th><?php _e('Puntos', 'flavor-platform'); ?></th>
                        <th><?php _e('Compromisos', 'flavor-platform'); ?></th>
                        <th><?php _e('Prácticas', 'flavor-platform'); ?></th>
                        <th><?php _e('Reflexiones', 'flavor-platform'); ?></th>
                        <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios): ?>
                        <?php foreach ($usuarios as $usuario):
                            $nivel_data = $this->get_nivel_usuario($usuario->ID);
                        ?>
                            <tr>
                                <td><?php echo get_avatar($usuario->ID, 32); ?></td>
                                <td>
                                    <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                    <br><small><?php _e('Desde:', 'flavor-platform'); ?> <?php echo esc_html(date_i18n('j M Y', strtotime($usuario->user_registered))); ?></small>
                                </td>
                                <td><?php echo esc_html($usuario->user_email); ?></td>
                                <td>
                                    <span class="flavor-badge" style="background-color: <?php echo esc_attr($nivel_data['nivel']['color']); ?>; color: #fff;">
                                        <?php echo esc_html($nivel_data['nivel']['nombre']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo intval($usuario->puntos); ?></strong></td>
                                <td><?php echo intval($usuario->total_compromisos); ?></td>
                                <td><?php echo intval($usuario->total_practicas); ?></td>
                                <td><?php echo intval($usuario->total_reflexiones); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_user_link($usuario->ID)); ?>" class="button button-small">
                                        <?php _e('Ver perfil', 'flavor-platform'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9"><?php _e('No se encontraron usuarios participando en el módulo.', 'flavor-platform'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(__('%d usuarios', 'flavor-platform'), $total_usuarios); ?>
                        </span>
                        <?php
                        echo paginate_links([
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $pagina_actual,
                            'total'   => $total_paginas,
                            'type'    => 'plain',
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de gestión de compromisos
     */
    public function render_admin_compromisos(): void {
        global $wpdb;

        // Procesar acciones
        if (isset($_POST['accion_compromiso']) && wp_verify_nonce($_POST['_wpnonce_suficiencia'], 'suficiencia_admin_compromisos')) {
            $compromiso_id = intval($_POST['compromiso_id']);
            $accion = sanitize_key($_POST['accion_compromiso']);

            switch ($accion) {
                case 'aprobar':
                    update_post_meta($compromiso_id, '_es_estado', 'activo');
                    echo '<div class="notice notice-success"><p>' . __('Compromiso aprobado.', 'flavor-platform') . '</p></div>';
                    break;
                case 'rechazar':
                    update_post_meta($compromiso_id, '_es_estado', 'rechazado');
                    echo '<div class="notice notice-warning"><p>' . __('Compromiso rechazado.', 'flavor-platform') . '</p></div>';
                    break;
                case 'completar':
                    update_post_meta($compromiso_id, '_es_estado', 'completado');
                    echo '<div class="notice notice-success"><p>' . __('Compromiso marcado como completado.', 'flavor-platform') . '</p></div>';
                    break;
            }
        }

        // Paginación y filtros
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $items_por_pagina = 20;
        $offset = ($pagina_actual - 1) * $items_por_pagina;
        $filtro_estado = isset($_GET['estado']) ? sanitize_key($_GET['estado']) : '';
        $filtro_tipo = isset($_GET['tipo']) ? sanitize_key($_GET['tipo']) : '';

        // Construir query
        $query_where = "WHERE p.post_type = 'es_compromiso' AND p.post_status = 'publish'";
        $query_params = [];

        if ($filtro_estado) {
            $query_where .= " AND pm_estado.meta_value = %s";
            $query_params[] = $filtro_estado;
        }

        if ($filtro_tipo) {
            $query_where .= " AND pm_tipo.meta_value = %s";
            $query_params[] = $filtro_tipo;
        }

        // Total compromisos
        $count_query = "SELECT COUNT(DISTINCT p.ID)
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_es_estado'
                        LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_es_tipo'
                        {$query_where}";
        $total_compromisos = $wpdb->get_var($query_params ? $wpdb->prepare($count_query, ...$query_params) : $count_query);

        // Obtener compromisos
        $select_query = "SELECT p.ID, p.post_title, p.post_date, p.post_author,
                                u.display_name,
                                pm_estado.meta_value as estado,
                                pm_tipo.meta_value as tipo,
                                pm_duracion.meta_value as duracion,
                                pm_dias.meta_value as dias_cumplidos,
                                pm_desc.meta_value as descripcion
                         FROM {$wpdb->posts} p
                         INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
                         LEFT JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_es_estado'
                         LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_es_tipo'
                         LEFT JOIN {$wpdb->postmeta} pm_duracion ON p.ID = pm_duracion.post_id AND pm_duracion.meta_key = '_es_duracion_dias'
                         LEFT JOIN {$wpdb->postmeta} pm_dias ON p.ID = pm_dias.post_id AND pm_dias.meta_key = '_es_dias_cumplidos'
                         LEFT JOIN {$wpdb->postmeta} pm_desc ON p.ID = pm_desc.post_id AND pm_desc.meta_key = '_es_descripcion'
                         {$query_where}
                         ORDER BY p.post_date DESC
                         LIMIT %d OFFSET %d";

        $query_final_params = array_merge($query_params, [$items_por_pagina, $offset]);
        $compromisos = $wpdb->get_results($wpdb->prepare($select_query, ...$query_final_params));

        $total_paginas = ceil($total_compromisos / $items_por_pagina);

        $estados_compromisos = [
            'activo' => ['label' => __('Activo', 'flavor-platform'), 'color' => '#27ae60'],
            'pendiente_revision' => ['label' => __('Pendiente', 'flavor-platform'), 'color' => '#f39c12'],
            'completado' => ['label' => __('Completado', 'flavor-platform'), 'color' => '#3498db'],
            'abandonado' => ['label' => __('Abandonado', 'flavor-platform'), 'color' => '#95a5a6'],
            'rechazado' => ['label' => __('Rechazado', 'flavor-platform'), 'color' => '#e74c3c'],
        ];
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Compromisos - Economía de Suficiencia', 'flavor-platform')); ?>

            <!-- Resumen de tipos de compromiso disponibles -->
            <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3><?php _e('Tipos de compromiso disponibles', 'flavor-platform'); ?></h3>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <?php foreach (self::TIPOS_COMPROMISO as $tipo_id => $tipo_data): ?>
                        <div style="padding: 10px 15px; background: #f0f0f1; border-radius: 6px; display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                            <span><strong><?php echo esc_html($tipo_data['nombre']); ?></strong></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filtros -->
            <div class="tablenav top">
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="suficiencia-compromisos">
                    <select name="estado">
                        <option value=""><?php _e('Todos los estados', 'flavor-platform'); ?></option>
                        <?php foreach ($estados_compromisos as $estado_id => $estado_data): ?>
                            <option value="<?php echo esc_attr($estado_id); ?>" <?php selected($filtro_estado, $estado_id); ?>>
                                <?php echo esc_html($estado_data['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tipo">
                        <option value=""><?php _e('Todos los tipos', 'flavor-platform'); ?></option>
                        <?php foreach (self::TIPOS_COMPROMISO as $tipo_id => $tipo_data): ?>
                            <option value="<?php echo esc_attr($tipo_id); ?>" <?php selected($filtro_tipo, $tipo_id); ?>>
                                <?php echo esc_html($tipo_data['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>
                </form>
            </div>

            <!-- Tabla de compromisos -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Tipo', 'flavor-platform'); ?></th>
                        <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                        <th><?php _e('Descripción', 'flavor-platform'); ?></th>
                        <th><?php _e('Estado', 'flavor-platform'); ?></th>
                        <th><?php _e('Progreso', 'flavor-platform'); ?></th>
                        <th><?php _e('Fecha inicio', 'flavor-platform'); ?></th>
                        <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($compromisos): ?>
                        <?php foreach ($compromisos as $compromiso):
                            $tipo_info = self::TIPOS_COMPROMISO[$compromiso->tipo] ?? ['nombre' => $compromiso->tipo, 'icono' => 'dashicons-marker'];
                            $estado_info = $estados_compromisos[$compromiso->estado] ?? ['label' => $compromiso->estado, 'color' => '#999'];
                            $duracion = intval($compromiso->duracion);
                            $dias_cumplidos = intval($compromiso->dias_cumplidos);
                            $progreso = $duracion > 0 ? min(100, round(($dias_cumplidos / $duracion) * 100)) : 0;
                        ?>
                            <tr>
                                <td>
                                    <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                                    <?php echo esc_html($tipo_info['nombre']); ?>
                                </td>
                                <td>
                                    <?php echo get_avatar($compromiso->post_author, 24); ?>
                                    <?php echo esc_html($compromiso->display_name); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($compromiso->descripcion ? wp_trim_words($compromiso->descripcion, 10) : '-'); ?>
                                </td>
                                <td>
                                    <span class="flavor-badge" style="background-color: <?php echo esc_attr($estado_info['color']); ?>; color: #fff;">
                                        <?php echo esc_html($estado_info['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; background: #e0e0e0; border-radius: 4px; height: 8px;">
                                            <div style="width: <?php echo $progreso; ?>%; background: #27ae60; border-radius: 4px; height: 100%;"></div>
                                        </div>
                                        <span><?php echo $dias_cumplidos; ?>/<?php echo $duracion; ?> <?php _e('días', 'flavor-platform'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html(date_i18n('j M Y', strtotime($compromiso->post_date))); ?></td>
                                <td>
                                    <?php if ($compromiso->estado === 'pendiente_revision'): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('suficiencia_admin_compromisos', '_wpnonce_suficiencia'); ?>
                                            <input type="hidden" name="compromiso_id" value="<?php echo $compromiso->ID; ?>">
                                            <button type="submit" name="accion_compromiso" value="aprobar" class="button button-small button-primary">
                                                <?php _e('Aprobar', 'flavor-platform'); ?>
                                            </button>
                                            <button type="submit" name="accion_compromiso" value="rechazar" class="button button-small">
                                                <?php _e('Rechazar', 'flavor-platform'); ?>
                                            </button>
                                        </form>
                                    <?php elseif ($compromiso->estado === 'activo'): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('suficiencia_admin_compromisos', '_wpnonce_suficiencia'); ?>
                                            <input type="hidden" name="compromiso_id" value="<?php echo $compromiso->ID; ?>">
                                            <button type="submit" name="accion_compromiso" value="completar" class="button button-small">
                                                <?php _e('Marcar completado', 'flavor-platform'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7"><?php _e('No se encontraron compromisos.', 'flavor-platform'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(__('%d compromisos', 'flavor-platform'), $total_compromisos); ?>
                        </span>
                        <?php
                        echo paginate_links([
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $pagina_actual,
                            'total'   => $total_paginas,
                            'type'    => 'plain',
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de biblioteca de recursos educativos
     */
    public function render_admin_biblioteca(): void {
        global $wpdb;

        // Procesar acciones de agregar/editar recurso
        if (isset($_POST['guardar_recurso']) && wp_verify_nonce($_POST['_wpnonce_suficiencia'], 'suficiencia_admin_biblioteca')) {
            $recurso_id = isset($_POST['recurso_id']) ? intval($_POST['recurso_id']) : 0;
            $titulo = sanitize_text_field($_POST['titulo']);
            $contenido = wp_kses_post($_POST['contenido']);
            $categoria_recurso = sanitize_key($_POST['categoria_recurso']);
            $tipo_recurso = sanitize_key($_POST['tipo_recurso']);

            $post_data = [
                'post_title'   => $titulo,
                'post_content' => $contenido,
                'post_type'    => 'es_recurso',
                'post_status'  => 'publish',
            ];

            if ($recurso_id) {
                $post_data['ID'] = $recurso_id;
                wp_update_post($post_data);
                $mensaje = __('Recurso actualizado correctamente.', 'flavor-platform');
            } else {
                $recurso_id = wp_insert_post($post_data, true);
                $mensaje = __('Recurso creado correctamente.', 'flavor-platform');
            }

            if (!is_wp_error($recurso_id) && !empty($recurso_id)) {
                update_post_meta($recurso_id, '_es_categoria', $categoria_recurso);
                update_post_meta($recurso_id, '_es_tipo', $tipo_recurso);
                echo '<div class="notice notice-success"><p>' . esc_html($mensaje) . '</p></div>';
            } elseif (is_wp_error($recurso_id)) {
                echo '<div class="notice notice-error"><p>' . esc_html($recurso_id->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('No se pudo guardar el recurso.', 'flavor-platform') . '</p></div>';
            }
        }

        // Eliminar recurso
        if (isset($_GET['eliminar']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'eliminar_recurso')) {
            $recurso_id = intval($_GET['eliminar']);
            wp_delete_post($recurso_id, true);
            echo '<div class="notice notice-success"><p>' . __('Recurso eliminado.', 'flavor-platform') . '</p></div>';
        }

        // Paginación
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $items_por_pagina = 20;
        $offset = ($pagina_actual - 1) * $items_por_pagina;

        // Filtros
        $filtro_categoria = isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '';
        $filtro_tipo = isset($_GET['tipo']) ? sanitize_key($_GET['tipo']) : '';

        $categorias_recursos = [
            'libros' => __('Libros', 'flavor-platform'),
            'articulos' => __('Artículos', 'flavor-platform'),
            'videos' => __('Videos', 'flavor-platform'),
            'podcasts' => __('Podcasts', 'flavor-platform'),
            'cursos' => __('Cursos', 'flavor-platform'),
            'herramientas' => __('Herramientas', 'flavor-platform'),
            'comunidades' => __('Comunidades', 'flavor-platform'),
        ];

        $tipos_recursos = [
            'enlace' => __('Enlace externo', 'flavor-platform'),
            'documento' => __('Documento descargable', 'flavor-platform'),
            'texto' => __('Contenido propio', 'flavor-platform'),
        ];

        // Construir query
        $query_where = "WHERE p.post_type = 'es_recurso' AND p.post_status = 'publish'";
        $query_params = [];

        if ($filtro_categoria) {
            $query_where .= " AND pm_cat.meta_value = %s";
            $query_params[] = $filtro_categoria;
        }

        if ($filtro_tipo) {
            $query_where .= " AND pm_tipo.meta_value = %s";
            $query_params[] = $filtro_tipo;
        }

        $count_query = "SELECT COUNT(DISTINCT p.ID)
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_es_categoria'
                        LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_es_tipo'
                        {$query_where}";
        $total_recursos = $wpdb->get_var($query_params ? $wpdb->prepare($count_query, ...$query_params) : $count_query);

        $select_query = "SELECT p.ID, p.post_title, p.post_content, p.post_date, p.post_author,
                                u.display_name,
                                pm_cat.meta_value as categoria,
                                pm_tipo.meta_value as tipo
                         FROM {$wpdb->posts} p
                         INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
                         LEFT JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_es_categoria'
                         LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_es_tipo'
                         {$query_where}
                         ORDER BY p.post_date DESC
                         LIMIT %d OFFSET %d";

        $query_final_params = array_merge($query_params, [$items_por_pagina, $offset]);
        $recursos = $wpdb->get_results($wpdb->prepare($select_query, ...$query_final_params));

        $total_paginas = ceil($total_recursos / $items_por_pagina);

        // Verificar si estamos editando
        $editando_recurso = null;
        if (isset($_GET['editar'])) {
            $editando_recurso = get_post(intval($_GET['editar']));
        }
        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $acciones_header = [
                ['label' => __('Añadir recurso', 'flavor-platform'), 'url' => '#formulario-recurso', 'class' => 'button-primary'],
            ];
            $this->render_page_header(__('Biblioteca - Economía de Suficiencia', 'flavor-platform'), $acciones_header);
            ?>

            <!-- Formulario para añadir/editar recurso -->
            <div id="formulario-recurso" class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3><?php echo $editando_recurso ? __('Editar recurso', 'flavor-platform') : __('Añadir nuevo recurso educativo', 'flavor-platform'); ?></h3>
                <form method="post">
                    <?php wp_nonce_field('suficiencia_admin_biblioteca', '_wpnonce_suficiencia'); ?>
                    <?php if ($editando_recurso): ?>
                        <input type="hidden" name="recurso_id" value="<?php echo $editando_recurso->ID; ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="titulo"><?php _e('Título', 'flavor-platform'); ?></label></th>
                            <td>
                                <input type="text" name="titulo" id="titulo" class="regular-text" required
                                       value="<?php echo $editando_recurso ? esc_attr($editando_recurso->post_title) : ''; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="categoria_recurso"><?php _e('Categoría', 'flavor-platform'); ?></label></th>
                            <td>
                                <select name="categoria_recurso" id="categoria_recurso">
                                    <?php
                                    $cat_actual = $editando_recurso ? get_post_meta($editando_recurso->ID, '_es_categoria', true) : '';
                                    foreach ($categorias_recursos as $cat_id => $cat_label):
                                    ?>
                                        <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($cat_actual, $cat_id); ?>>
                                            <?php echo esc_html($cat_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="tipo_recurso"><?php _e('Tipo', 'flavor-platform'); ?></label></th>
                            <td>
                                <select name="tipo_recurso" id="tipo_recurso">
                                    <?php
                                    $tipo_actual = $editando_recurso ? get_post_meta($editando_recurso->ID, '_es_tipo', true) : '';
                                    foreach ($tipos_recursos as $tipo_id => $tipo_label):
                                    ?>
                                        <option value="<?php echo esc_attr($tipo_id); ?>" <?php selected($tipo_actual, $tipo_id); ?>>
                                            <?php echo esc_html($tipo_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="contenido"><?php _e('Contenido/Descripción', 'flavor-platform'); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    $editando_recurso ? $editando_recurso->post_content : '',
                                    'contenido',
                                    ['textarea_rows' => 8, 'media_buttons' => true]
                                );
                                ?>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="guardar_recurso" class="button button-primary">
                            <?php echo $editando_recurso ? __('Actualizar recurso', 'flavor-platform') : __('Añadir recurso', 'flavor-platform'); ?>
                        </button>
                        <?php if ($editando_recurso): ?>
                            <a href="<?php echo admin_url('admin.php?page=suficiencia-biblioteca'); ?>" class="button">
                                <?php _e('Cancelar', 'flavor-platform'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <!-- Filtros -->
            <div class="tablenav top">
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="suficiencia-biblioteca">
                    <select name="categoria">
                        <option value=""><?php _e('Todas las categorías', 'flavor-platform'); ?></option>
                        <?php foreach ($categorias_recursos as $cat_id => $cat_label): ?>
                            <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($filtro_categoria, $cat_id); ?>>
                                <?php echo esc_html($cat_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tipo">
                        <option value=""><?php _e('Todos los tipos', 'flavor-platform'); ?></option>
                        <?php foreach ($tipos_recursos as $tipo_id => $tipo_label): ?>
                            <option value="<?php echo esc_attr($tipo_id); ?>" <?php selected($filtro_tipo, $tipo_id); ?>>
                                <?php echo esc_html($tipo_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>
                </form>
            </div>

            <!-- Tabla de recursos -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Título', 'flavor-platform'); ?></th>
                        <th><?php _e('Categoría', 'flavor-platform'); ?></th>
                        <th><?php _e('Tipo', 'flavor-platform'); ?></th>
                        <th><?php _e('Autor', 'flavor-platform'); ?></th>
                        <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                        <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recursos): ?>
                        <?php foreach ($recursos as $recurso): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($recurso->post_title); ?></strong>
                                    <br><small><?php echo esc_html(wp_trim_words(strip_tags($recurso->post_content), 15)); ?></small>
                                </td>
                                <td><?php echo esc_html($categorias_recursos[$recurso->categoria] ?? $recurso->categoria); ?></td>
                                <td><?php echo esc_html($tipos_recursos[$recurso->tipo] ?? $recurso->tipo); ?></td>
                                <td><?php echo esc_html($recurso->display_name); ?></td>
                                <td><?php echo esc_html(date_i18n('j M Y', strtotime($recurso->post_date))); ?></td>
                                <td>
                                    <a href="<?php echo add_query_arg('editar', $recurso->ID); ?>" class="button button-small">
                                        <?php _e('Editar', 'flavor-platform'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(add_query_arg('eliminar', $recurso->ID), 'eliminar_recurso'); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php esc_attr_e('¿Estás seguro de eliminar este recurso?', 'flavor-platform'); ?>');">
                                        <?php _e('Eliminar', 'flavor-platform'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('No hay recursos en la biblioteca.', 'flavor-platform'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(__('%d recursos', 'flavor-platform'), $total_recursos); ?>
                        </span>
                        <?php
                        echo paginate_links([
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $pagina_actual,
                            'total'   => $total_paginas,
                            'type'    => 'plain',
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de configuración del módulo
     */
    public function render_admin_config(): void {
        // Guardar configuración
        if (isset($_POST['guardar_config']) && wp_verify_nonce($_POST['_wpnonce_suficiencia'], 'suficiencia_admin_config')) {
            $opciones = [
                'es_habilitar_biblioteca' => isset($_POST['es_habilitar_biblioteca']) ? 1 : 0,
                'es_habilitar_gamificacion' => isset($_POST['es_habilitar_gamificacion']) ? 1 : 0,
                'es_puntos_reflexion' => max(1, intval($_POST['es_puntos_reflexion'])),
                'es_puntos_compromiso' => max(1, intval($_POST['es_puntos_compromiso'])),
                'es_puntos_practica' => max(1, intval($_POST['es_puntos_practica'])),
                'es_puntos_compartir' => max(1, intval($_POST['es_puntos_compartir'])),
                'es_duracion_minima_compromiso' => max(1, intval($_POST['es_duracion_minima_compromiso'])),
                'es_duracion_maxima_compromiso' => max(1, intval($_POST['es_duracion_maxima_compromiso'])),
                'es_moderacion_compromisos' => isset($_POST['es_moderacion_compromisos']) ? 1 : 0,
                'es_mostrar_ranking' => isset($_POST['es_mostrar_ranking']) ? 1 : 0,
                'es_notificar_nuevos_compromisos' => isset($_POST['es_notificar_nuevos_compromisos']) ? 1 : 0,
                'es_email_notificaciones' => sanitize_email($_POST['es_email_notificaciones']),
            ];

            foreach ($opciones as $opcion_key => $opcion_valor) {
                update_option($opcion_key, $opcion_valor);
            }

            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'flavor-platform') . '</p></div>';
        }

        // Obtener valores actuales
        $config = [
            'es_habilitar_biblioteca' => get_option('es_habilitar_biblioteca', 1),
            'es_habilitar_gamificacion' => get_option('es_habilitar_gamificacion', 1),
            'es_puntos_reflexion' => get_option('es_puntos_reflexion', 5),
            'es_puntos_compromiso' => get_option('es_puntos_compromiso', 10),
            'es_puntos_practica' => get_option('es_puntos_practica', 3),
            'es_puntos_compartir' => get_option('es_puntos_compartir', 15),
            'es_duracion_minima_compromiso' => get_option('es_duracion_minima_compromiso', 7),
            'es_duracion_maxima_compromiso' => get_option('es_duracion_maxima_compromiso', 365),
            'es_moderacion_compromisos' => get_option('es_moderacion_compromisos', 0),
            'es_mostrar_ranking' => get_option('es_mostrar_ranking', 1),
            'es_notificar_nuevos_compromisos' => get_option('es_notificar_nuevos_compromisos', 1),
            'es_email_notificaciones' => get_option('es_email_notificaciones', get_option('admin_email')),
        ];
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Configuración - Economía de Suficiencia', 'flavor-platform')); ?>

            <form method="post">
                <?php wp_nonce_field('suficiencia_admin_config', '_wpnonce_suficiencia'); ?>

                <!-- Configuración General -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2><?php _e('Configuración General', 'flavor-platform'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Biblioteca de recursos', 'flavor-platform'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="es_habilitar_biblioteca" value="1" <?php checked($config['es_habilitar_biblioteca'], 1); ?>>
                                    <?php _e('Habilitar biblioteca de objetos compartidos', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php _e('Permite a los usuarios compartir y solicitar préstamos de objetos.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Moderación de compromisos', 'flavor-platform'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="es_moderacion_compromisos" value="1" <?php checked($config['es_moderacion_compromisos'], 1); ?>>
                                    <?php _e('Requerir aprobación para nuevos compromisos', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php _e('Los compromisos quedarán en estado pendiente hasta ser aprobados por un administrador.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sistema de Gamificación -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2><?php _e('Sistema de Gamificación', 'flavor-platform'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Habilitar gamificación', 'flavor-platform'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="es_habilitar_gamificacion" value="1" <?php checked($config['es_habilitar_gamificacion'], 1); ?>>
                                    <?php _e('Activar sistema de puntos y niveles', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Mostrar ranking público', 'flavor-platform'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="es_mostrar_ranking" value="1" <?php checked($config['es_mostrar_ranking'], 1); ?>>
                                    <?php _e('Mostrar clasificación de usuarios por puntos', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Puntos por acción', 'flavor-platform'); ?></th>
                            <td>
                                <table style="border-collapse: separate; border-spacing: 10px 5px;">
                                    <tr>
                                        <td><label><?php _e('Reflexión:', 'flavor-platform'); ?></label></td>
                                        <td><input type="number" name="es_puntos_reflexion" value="<?php echo esc_attr($config['es_puntos_reflexion']); ?>" min="1" max="100" style="width: 60px;"> <?php _e('puntos', 'flavor-platform'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><label><?php _e('Nuevo compromiso:', 'flavor-platform'); ?></label></td>
                                        <td><input type="number" name="es_puntos_compromiso" value="<?php echo esc_attr($config['es_puntos_compromiso']); ?>" min="1" max="100" style="width: 60px;"> <?php _e('puntos', 'flavor-platform'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><label><?php _e('Práctica diaria:', 'flavor-platform'); ?></label></td>
                                        <td><input type="number" name="es_puntos_practica" value="<?php echo esc_attr($config['es_puntos_practica']); ?>" min="1" max="100" style="width: 60px;"> <?php _e('puntos', 'flavor-platform'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><label><?php _e('Compartir recurso:', 'flavor-platform'); ?></label></td>
                                        <td><input type="number" name="es_puntos_compartir" value="<?php echo esc_attr($config['es_puntos_compartir']); ?>" min="1" max="100" style="width: 60px;"> <?php _e('puntos', 'flavor-platform'); ?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Compromisos -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2><?php _e('Configuración de Compromisos', 'flavor-platform'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Duración mínima', 'flavor-platform'); ?></th>
                            <td>
                                <input type="number" name="es_duracion_minima_compromiso" value="<?php echo esc_attr($config['es_duracion_minima_compromiso']); ?>" min="1" max="365" style="width: 80px;"> <?php _e('días', 'flavor-platform'); ?>
                                <p class="description"><?php _e('Duración mínima que debe tener un compromiso.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Duración máxima', 'flavor-platform'); ?></th>
                            <td>
                                <input type="number" name="es_duracion_maxima_compromiso" value="<?php echo esc_attr($config['es_duracion_maxima_compromiso']); ?>" min="1" max="365" style="width: 80px;"> <?php _e('días', 'flavor-platform'); ?>
                                <p class="description"><?php _e('Duración máxima permitida para un compromiso.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Notificaciones -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2><?php _e('Notificaciones', 'flavor-platform'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Notificar nuevos compromisos', 'flavor-platform'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="es_notificar_nuevos_compromisos" value="1" <?php checked($config['es_notificar_nuevos_compromisos'], 1); ?>>
                                    <?php _e('Enviar email cuando se registre un nuevo compromiso', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Email de notificaciones', 'flavor-platform'); ?></th>
                            <td>
                                <input type="email" name="es_email_notificaciones" value="<?php echo esc_attr($config['es_email_notificaciones']); ?>" class="regular-text">
                                <p class="description"><?php _e('Dirección de email donde se enviarán las notificaciones de administración.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Niveles de suficiencia (solo lectura) -->
                <div class="flavor-admin-box" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h2><?php _e('Niveles de Suficiencia', 'flavor-platform'); ?></h2>
                    <p class="description"><?php _e('Los niveles están predefinidos y se asignan automáticamente según los puntos acumulados.', 'flavor-platform'); ?></p>
                    <table class="widefat striped" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th><?php _e('Nivel', 'flavor-platform'); ?></th>
                                <th><?php _e('Puntos mínimos', 'flavor-platform'); ?></th>
                                <th><?php _e('Descripción', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (self::NIVELES_SUFICIENCIA as $nivel_id => $nivel_data): ?>
                                <tr>
                                    <td>
                                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo esc_attr($nivel_data['color']); ?>; margin-right: 8px;"></span>
                                        <strong><?php echo esc_html($nivel_data['nombre']); ?></strong>
                                    </td>
                                    <td><?php echo intval($nivel_data['puntos_min']); ?></td>
                                    <td><?php echo esc_html($nivel_data['descripcion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="guardar_config" class="button button-primary button-large">
                        <?php _e('Guardar configuración', 'flavor-platform'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'economia-suficiencia',
            'title'    => __('Economía de Suficiencia', 'flavor-platform'),
            'subtitle' => __('Vivir con lo suficiente para el bienestar colectivo', 'flavor-platform'),
            'icon'     => '🌿',
            'color'    => 'secondary', // Usa variable CSS --flavor-secondary del tema

            'database' => [
                'table'       => 'flavor_economia_suficiencia',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Práctica', 'flavor-platform'), 'required' => true],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', 'flavor-platform'), 'options' => ['consumo', 'energia', 'transporte', 'alimentacion', 'vivienda']],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'impacto'     => ['type' => 'number', 'label' => __('Impacto estimado', 'flavor-platform')],
                'dificultad'  => ['type' => 'select', 'label' => __('Dificultad', 'flavor-platform'), 'options' => ['facil', 'media', 'dificil']],
            ],

            'estados' => [
                'propuesta'  => ['label' => __('Propuesta', 'flavor-platform'), 'color' => 'gray', 'icon' => '💡'],
                'en_practica' => ['label' => __('En práctica', 'flavor-platform'), 'color' => 'green', 'icon' => '🌱'],
                'consolidada' => ['label' => __('Consolidada', 'flavor-platform'), 'color' => 'emerald', 'icon' => '🌿'],
                'compartida' => ['label' => __('Compartida', 'flavor-platform'), 'color' => 'blue', 'icon' => '🤝'],
            ],

            'stats' => [
                'practicas_activas' => ['label' => __('Prácticas activas', 'flavor-platform'), 'icon' => '🌿', 'color' => 'emerald'],
                'participantes'     => ['label' => __('Participantes', 'flavor-platform'), 'icon' => '👥', 'color' => 'blue'],
                'ahorro_recursos'   => ['label' => __('Recursos ahorrados', 'flavor-platform'), 'icon' => '♻️', 'color' => 'green'],
                'impacto_comunidad' => ['label' => __('Impacto comunidad', 'flavor-platform'), 'icon' => '🌍', 'color' => 'teal'],
            ],

            'card' => [
                'template'     => 'practica-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['dificultad', 'impacto'],
                'show_estado'  => true,
            ],

            'tabs' => [
                'practicas' => [
                    'label'   => __('Prácticas', 'flavor-platform'),
                    'icon'    => 'dashicons-portfolio',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'biblioteca' => [
                    'label'   => __('Biblioteca', 'flavor-platform'),
                    'icon'    => 'dashicons-book',
                    'content' => 'shortcode:suficiencia_biblioteca',
                    'public'  => true,
                ],
                'mi-compromiso' => [
                    'label'      => __('Mi compromiso', 'flavor-platform'),
                    'icon'       => 'dashicons-heart',
                    'content'    => 'shortcode:suficiencia_mi_compromiso',
                    'requires_login' => true,
                ],
                'registrar' => [
                    'label'      => __('Registrar práctica', 'flavor-platform'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:suficiencia_registrar',
                    'requires_login' => true,
                ],
                'foro' => [
                    'label'      => __('Foro', 'flavor-platform'),
                    'icon'       => 'dashicons-format-chat',
                    'content'    => 'callback:action_foro_recurso',
                    'requires_login' => false,
                    'hidden_nav' => true,
                ],
                'chat' => [
                    'label'      => __('Chat', 'flavor-platform'),
                    'icon'       => 'dashicons-format-status',
                    'content'    => 'callback:action_chat_recurso',
                    'requires_login' => true,
                    'hidden_nav' => true,
                ],
                'multimedia' => [
                    'label'      => __('Multimedia', 'flavor-platform'),
                    'icon'       => 'dashicons-format-gallery',
                    'content'    => 'callback:action_multimedia_recurso',
                    'requires_login' => false,
                    'hidden_nav' => true,
                ],
                'red-social' => [
                    'label'      => __('Red social', 'flavor-platform'),
                    'icon'       => 'dashicons-share',
                    'content'    => 'callback:action_red_social_recurso',
                    'requires_login' => true,
                    'hidden_nav' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'titulo',
                'order'      => 'ASC',
                'filterable' => ['categoria', 'dificultad'],
            ],

            'dashboard' => [
                'widgets' => ['mi_nivel', 'practicas_sugeridas', 'comunidad', 'recursos'],
                'actions' => [
                    'compromiso' => ['label' => __('Nuevo compromiso', 'flavor-platform'), 'icon' => '🌱', 'color' => 'emerald'],
                    'explorar'   => ['label' => __('Explorar prácticas', 'flavor-platform'), 'icon' => '🔍', 'color' => 'green'],
                ],
            ],

            'features' => [
                'compromisos'    => true,
                'seguimiento'    => true,
                'gamificacion'   => true,
                'comunidad'      => true,
                'recursos'       => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-economia-suficiencia-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Economia_Suficiencia_Dashboard_Tab')) {
                Flavor_Economia_Suficiencia_Dashboard_Tab::get_instance();
            }
        }
    }
}
