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
        $this->module_name = __('Economía de Suficiencia', 'flavor-chat-ia');
        $this->module_description = __('Promueve un modelo basado en "suficiente" vs "máximo"', 'flavor-chat-ia');
        $this->module_icon = 'dashicons-editor-expand';
        $this->module_color = '#27ae60';

        parent::__construct();
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
            'permission_callback' => '__return_true',
        ]);

        // Biblioteca de recursos
        register_rest_route($namespace, '/economia-suficiencia/biblioteca', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_biblioteca'],
            'permission_callback' => '__return_true',
        ]);
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
                'name' => __('Reflexiones de Suficiencia', 'flavor-chat-ia'),
                'singular_name' => __('Reflexión', 'flavor-chat-ia'),
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
                'name' => __('Compromisos de Suficiencia', 'flavor-chat-ia'),
                'singular_name' => __('Compromiso', 'flavor-chat-ia'),
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
                'name' => __('Prácticas de Suficiencia', 'flavor-chat-ia'),
                'singular_name' => __('Práctica', 'flavor-chat-ia'),
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
                'name' => __('Biblioteca de Objetos', 'flavor-chat-ia'),
                'singular_name' => __('Objeto', 'flavor-chat-ia'),
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
        if (!$this->is_module_page()) {
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
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'guardado' => __('Guardado correctamente', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si estamos en una página del módulo
     */
    private function is_module_page(): bool {
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
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $pregunta = sanitize_text_field($_POST['pregunta'] ?? '');
        $respuesta = sanitize_textarea_field($_POST['respuesta'] ?? '');

        if (empty($respuesta)) {
            wp_send_json_error(['message' => __('La reflexión no puede estar vacía', 'flavor-chat-ia')]);
        }

        $reflexion_id = wp_insert_post([
            'post_type' => 'es_reflexion',
            'post_status' => 'private',
            'post_author' => $user_id,
            'post_title' => $pregunta ?: __('Reflexión personal', 'flavor-chat-ia'),
            'post_content' => $respuesta,
        ]);

        if (is_wp_error($reflexion_id)) {
            wp_send_json_error(['message' => $reflexion_id->get_error_message()]);
        }

        update_post_meta($reflexion_id, '_es_categoria', $categoria);
        update_post_meta($reflexion_id, '_es_fecha', current_time('mysql'));

        // Sumar puntos
        $this->sumar_puntos($user_id, 5, 'reflexion');

        wp_send_json_success([
            'message' => __('Reflexión guardada', 'flavor-chat-ia'),
            'reflexion_id' => $reflexion_id,
        ]);
    }

    /**
     * AJAX: Hacer compromiso de suficiencia
     */
    public function ajax_hacer_compromiso(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $duracion = sanitize_text_field($_POST['duracion'] ?? '30');

        if (!isset(self::TIPOS_COMPROMISO[$tipo])) {
            wp_send_json_error(['message' => __('Tipo de compromiso no válido', 'flavor-chat-ia')]);
        }

        $compromiso_data = self::TIPOS_COMPROMISO[$tipo];

        $compromiso_id = wp_insert_post([
            'post_type' => 'es_compromiso',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $compromiso_data['nombre'],
        ]);

        if (is_wp_error($compromiso_id)) {
            wp_send_json_error(['message' => $compromiso_id->get_error_message()]);
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
            'message' => __('¡Compromiso adquirido!', 'flavor-chat-ia'),
            'compromiso_id' => $compromiso_id,
        ]);
    }

    /**
     * AJAX: Registrar práctica de suficiencia
     */
    public function ajax_registrar_practica(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $compromiso_id = intval($_POST['compromiso_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        // Verificar que el compromiso existe y pertenece al usuario
        $compromiso = get_post($compromiso_id);
        if (!$compromiso || (int) $compromiso->post_author !== $user_id) {
            wp_send_json_error(['message' => __('Compromiso no encontrado', 'flavor-chat-ia')]);
        }

        $practica_id = wp_insert_post([
            'post_type' => 'es_practica',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf(__('Práctica: %s', 'flavor-chat-ia'), $compromiso->post_title),
        ]);

        update_post_meta($practica_id, '_es_compromiso_id', $compromiso_id);
        update_post_meta($practica_id, '_es_fecha', current_time('mysql'));
        update_post_meta($practica_id, '_es_notas', $notas);

        // Actualizar contador del compromiso
        $dias_cumplidos = intval(get_post_meta($compromiso_id, '_es_dias_cumplidos', true));
        update_post_meta($compromiso_id, '_es_dias_cumplidos', $dias_cumplidos + 1);

        // Sumar puntos
        $this->sumar_puntos($user_id, 3, 'practica');

        wp_send_json_success([
            'message' => __('¡Práctica registrada!', 'flavor-chat-ia'),
            'dias_cumplidos' => $dias_cumplidos + 1,
        ]);
    }

    /**
     * AJAX: Compartir recurso en biblioteca
     */
    public function ajax_compartir_recurso(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $condiciones = sanitize_textarea_field($_POST['condiciones'] ?? '');

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es requerido', 'flavor-chat-ia')]);
        }

        $recurso_id = wp_insert_post([
            'post_type' => 'es_recurso',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $nombre,
            'post_content' => $descripcion,
        ]);

        if (is_wp_error($recurso_id)) {
            wp_send_json_error(['message' => $recurso_id->get_error_message()]);
        }

        update_post_meta($recurso_id, '_es_categoria', $categoria);
        update_post_meta($recurso_id, '_es_condiciones', $condiciones);
        update_post_meta($recurso_id, '_es_estado', 'disponible');
        update_post_meta($recurso_id, '_es_prestamos', 0);

        // Sumar puntos
        $this->sumar_puntos($user_id, 15, 'compartir_recurso');

        wp_send_json_success([
            'message' => __('¡Recurso añadido a la biblioteca!', 'flavor-chat-ia'),
            'recurso_id' => $recurso_id,
        ]);
    }

    /**
     * AJAX: Solicitar préstamo
     */
    public function ajax_solicitar_prestamo(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $recurso_id = intval($_POST['recurso_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $dias = intval($_POST['dias'] ?? 7);

        $recurso = get_post($recurso_id);
        if (!$recurso || $recurso->post_type !== 'es_recurso') {
            wp_send_json_error(['message' => __('Recurso no encontrado', 'flavor-chat-ia')]);
        }

        $estado = get_post_meta($recurso_id, '_es_estado', true);
        if ($estado !== 'disponible') {
            wp_send_json_error(['message' => __('Este recurso no está disponible', 'flavor-chat-ia')]);
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
        // TODO: Enviar notificación

        wp_send_json_success([
            'message' => __('Préstamo solicitado. El propietario ha sido notificado.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Evaluar necesidades personales
     */
    public function ajax_evaluar_necesidades(): void {
        check_ajax_referer('suficiencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $evaluaciones = $_POST['evaluaciones'] ?? [];

        if (empty($evaluaciones) || !is_array($evaluaciones)) {
            wp_send_json_error(['message' => __('Datos de evaluación no válidos', 'flavor-chat-ia')]);
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
            'message' => __('Evaluación guardada', 'flavor-chat-ia'),
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
            return '<p class="es-login-required">' . __('Inicia sesión para evaluar tus necesidades', 'flavor-chat-ia') . '</p>';
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
            return '<p class="es-login-required">' . __('Inicia sesión para hacer compromisos', 'flavor-chat-ia') . '</p>';
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
            return '<p class="es-login-required">' . __('Inicia sesión para ver tu camino', 'flavor-chat-ia') . '</p>';
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
        return ['status' => 'not_implemented', 'message' => __('Acción no implementada', 'flavor-chat-ia')];
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
        return __('La Economía de Suficiencia promueve un modelo basado en "suficiente" vs "máximo", enfocándose en necesidades reales y bienestar colectivo.', 'flavor-chat-ia');
    }
}
