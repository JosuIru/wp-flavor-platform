<?php
/**
 * Módulo: Huella Ecológica Comunitaria
 *
 * Sistema de medición y reducción del impacto ambiental colectivo.
 * Permite calcular, registrar y compensar la huella ecológica.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Huella Ecológica Comunitaria
 */
class Flavor_Platform_Huella_Ecologica_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Categorías de huella ecológica
     */
    const CATEGORIAS_HUELLA = [
        'transporte' => [
            'nombre' => 'Transporte',
            'icono' => 'dashicons-car',
            'color' => '#e74c3c',
            'unidad' => 'kg CO2',
        ],
        'energia' => [
            'nombre' => 'Energía',
            'icono' => 'dashicons-lightbulb',
            'color' => '#f39c12',
            'unidad' => 'kg CO2',
        ],
        'alimentacion' => [
            'nombre' => 'Alimentación',
            'icono' => 'dashicons-carrot',
            'color' => '#27ae60',
            'unidad' => 'kg CO2',
        ],
        'consumo' => [
            'nombre' => 'Consumo',
            'icono' => 'dashicons-cart',
            'color' => '#3498db',
            'unidad' => 'kg CO2',
        ],
        'residuos' => [
            'nombre' => 'Residuos',
            'icono' => 'dashicons-trash',
            'color' => '#9b59b6',
            'unidad' => 'kg CO2',
        ],
        'agua' => [
            'nombre' => 'Agua',
            'icono' => 'dashicons-admin-site-alt3',
            'color' => '#1abc9c',
            'unidad' => 'litros',
        ],
    ];

    /**
     * Tipos de acciones reductoras
     */
    const TIPOS_ACCION = [
        'movilidad_sostenible' => [
            'nombre' => 'Movilidad sostenible',
            'categoria' => 'transporte',
            'reduccion_estimada' => 2.5,
            'descripcion' => 'Usar bici, caminar o transporte público',
        ],
        'energia_renovable' => [
            'nombre' => 'Energía renovable',
            'categoria' => 'energia',
            'reduccion_estimada' => 5.0,
            'descripcion' => 'Usar energía de fuentes renovables',
        ],
        'dieta_local' => [
            'nombre' => 'Alimentación local',
            'categoria' => 'alimentacion',
            'reduccion_estimada' => 1.5,
            'descripcion' => 'Consumir productos de proximidad',
        ],
        'reducir_carne' => [
            'nombre' => 'Reducir carne',
            'categoria' => 'alimentacion',
            'reduccion_estimada' => 3.0,
            'descripcion' => 'Días sin carne o dieta vegetal',
        ],
        'reparar_reutilizar' => [
            'nombre' => 'Reparar y reutilizar',
            'categoria' => 'consumo',
            'reduccion_estimada' => 2.0,
            'descripcion' => 'Alargar vida útil de objetos',
        ],
        'compostaje' => [
            'nombre' => 'Compostaje',
            'categoria' => 'residuos',
            'reduccion_estimada' => 1.0,
            'descripcion' => 'Compostar residuos orgánicos',
        ],
        'ahorro_agua' => [
            'nombre' => 'Ahorro de agua',
            'categoria' => 'agua',
            'reduccion_estimada' => 0.5,
            'descripcion' => 'Reducir consumo de agua',
        ],
        'autoconsumo' => [
            'nombre' => 'Autoconsumo',
            'categoria' => 'alimentacion',
            'reduccion_estimada' => 2.0,
            'descripcion' => 'Cultivar tus propios alimentos',
        ],
    ];

    /**
     * Estados de proyectos de compensación
     */
    const ESTADOS_PROYECTO = [
        'propuesto' => ['nombre' => 'Propuesto', 'color' => '#f39c12'],
        'aprobado' => ['nombre' => 'Aprobado', 'color' => '#3498db'],
        'en_curso' => ['nombre' => 'En curso', 'color' => '#27ae60'],
        'completado' => ['nombre' => 'Completado', 'color' => '#2ecc71'],
        'cancelado' => ['nombre' => 'Cancelado', 'color' => '#95a5a6'],
    ];

    /**
     * Logros/badges disponibles
     */
    const LOGROS = [
        'primera_medicion' => [
            'nombre' => 'Primera medición',
            'descripcion' => 'Calculaste tu huella por primera vez',
            'icono' => '🌱',
            'puntos' => 10,
        ],
        'semana_verde' => [
            'nombre' => 'Semana verde',
            'descripcion' => '7 días registrando acciones',
            'icono' => '🌿',
            'puntos' => 25,
        ],
        'huella_cero' => [
            'nombre' => 'Día huella cero',
            'descripcion' => 'Un día con huella neta cero',
            'icono' => '🌍',
            'puntos' => 50,
        ],
        'embajador' => [
            'nombre' => 'Embajador verde',
            'descripcion' => 'Propusiste un proyecto comunitario',
            'icono' => '🌳',
            'puntos' => 100,
        ],
        'compensador' => [
            'nombre' => 'Compensador',
            'descripcion' => 'Participaste en proyecto de compensación',
            'icono' => '💚',
            'puntos' => 75,
        ],
        'mentor_eco' => [
            'nombre' => 'Mentor ecológico',
            'descripcion' => 'Ayudaste a 5 personas a reducir su huella',
            'icono' => '🎓',
            'puntos' => 150,
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->module_id = 'huella_ecologica';
        $this->module_name = __('Huella Ecológica Comunitaria', 'flavor-platform');
        $this->module_description = __('Sistema de medición y reducción del impacto ambiental colectivo', 'flavor-platform');
        $this->module_icon = 'dashicons-palmtree';
        $this->module_color = '#27ae60';
        $this->module_role = 'transversal';
        $this->ecosystem_measures_modules = ['energia_comunitaria', 'grupos_consumo', 'compostaje', 'reciclaje', 'carpooling'];
        $this->dashboard_transversal_priority = 5;
        $this->dashboard_client_contexts = ['impacto', 'sostenibilidad', 'energia', 'consumo'];
        $this->dashboard_admin_contexts = ['impacto', 'sostenibilidad', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['regeneracion'];
        $this->gailu_contribuye_a = ['impacto', 'autonomia'];

        parent::__construct();
    }

    /**
     * Inicializa el módulo
     */
    public function init(): void {
        // Registrar post types en el hook 'init' de WordPress, no directamente
        add_action('init', [$this, 'register_post_types'], 5);

        $this->register_ajax_handlers();
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

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
    private function init_dashboard_tabs(): void {
        $tab_file = dirname(__FILE__) . '/class-huella-ecologica-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Huella_Ecologica_Dashboard_Tab')) {
                Flavor_Huella_Ecologica_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Estadísticas del usuario
        register_rest_route($namespace, '/huella-ecologica/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Estadísticas de la comunidad
        register_rest_route($namespace, '/huella-ecologica/comunidad', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_estadisticas_comunidad'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);

        // Proyectos de compensación
        register_rest_route($namespace, '/huella-ecologica/proyectos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_proyectos'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);

        // Logros del usuario
        register_rest_route($namespace, '/huella-ecologica/logros', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_logros'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Calculadora
        register_rest_route($namespace, '/huella-ecologica/calcular', [
            'methods' => 'POST',
            'callback' => [$this, 'api_calcular_huella'],
            'permission_callback' => [$this, 'public_read_permission'],
        ]);
    }

    /**
     * Verifica si el usuario está logueado
     */
    public function check_user_logged_in(): bool {
        return is_user_logged_in();
    }

    /**
     * Permite lecturas públicas explícitas.
     */
    public function public_read_permission(): bool {
        return true;
    }

    /**
     * API: Obtener estadísticas del usuario
     */
    public function api_get_estadisticas(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        $periodo = $request->get_param('periodo') ?: 'mes';

        return new \WP_REST_Response($this->get_estadisticas_usuario($user_id, $periodo));
    }

    /**
     * API: Obtener estadísticas de la comunidad
     */
    public function api_get_estadisticas_comunidad(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response($this->get_estadisticas_comunidad());
    }

    /**
     * API: Obtener proyectos
     */
    public function api_get_proyectos(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'he_proyecto',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'meta_query' => [['key' => '_he_estado', 'value' => ['aprobado', 'en_curso'], 'compare' => 'IN']],
        ];

        $query = new \WP_Query($args);
        $proyectos = [];

        foreach ($query->posts as $post) {
            $participantes = get_post_meta($post->ID, '_he_participantes', true) ?: [];
            $proyectos[] = [
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'descripcion' => wp_trim_words($post->post_content, 30),
                'meta_co2' => get_post_meta($post->ID, '_he_meta_co2', true),
                'co2_actual' => get_post_meta($post->ID, '_he_co2_actual', true),
                'participantes' => count($participantes),
                'estado' => get_post_meta($post->ID, '_he_estado', true),
            ];
        }

        return new \WP_REST_Response(['proyectos' => $proyectos, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener logros
     */
    public function api_get_logros(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();
        return new \WP_REST_Response(['logros' => $this->get_logros_usuario($user_id)]);
    }

    /**
     * API: Calcular huella
     */
    public function api_calcular_huella(\WP_REST_Request $request): \WP_REST_Response {
        $datos = $request->get_json_params();
        $huella_total = 0;
        $desglose = [];

        if (!empty($datos['km_coche'])) {
            $co2_coche = floatval($datos['km_coche']) * 0.21;
            $huella_total += $co2_coche;
            $desglose['transporte_coche'] = $co2_coche;
        }
        if (!empty($datos['km_avion'])) {
            $co2_avion = floatval($datos['km_avion']) * 0.255;
            $huella_total += $co2_avion;
            $desglose['transporte_avion'] = $co2_avion;
        }
        if (!empty($datos['kwh_mes'])) {
            $co2_energia = floatval($datos['kwh_mes']) * 0.38 / 30;
            $huella_total += $co2_energia;
            $desglose['energia'] = $co2_energia;
        }
        if (!empty($datos['tipo_dieta'])) {
            $co2_dieta = ['omnivora' => 7.2, 'flexitariana' => 4.7, 'vegetariana' => 3.8, 'vegana' => 2.9];
            $dieta_co2 = $co2_dieta[$datos['tipo_dieta']] ?? 5.0;
            $huella_total += $dieta_co2;
            $desglose['alimentacion'] = $dieta_co2;
        }

        return new \WP_REST_Response([
            'huella_diaria' => round($huella_total, 2),
            'huella_mensual' => round($huella_total * 30, 2),
            'huella_anual' => round($huella_total * 365, 2),
            'desglose' => $desglose,
        ]);
    }

    /**
     * Registra los tipos de post personalizados
     */
    public function register_post_types(): void {
        // Registros de huella individual
        register_post_type('he_registro', [
            'labels' => [
                'name' => __('Registros de Huella', 'flavor-platform'),
                'singular_name' => __('Registro de Huella', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);

        // Acciones reductoras
        register_post_type('he_accion', [
            'labels' => [
                'name' => __('Acciones Reductoras', 'flavor-platform'),
                'singular_name' => __('Acción Reductora', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);

        // Proyectos de compensación comunitarios
        register_post_type('he_proyecto', [
            'labels' => [
                'name' => __('Proyectos de Compensación', 'flavor-platform'),
                'singular_name' => __('Proyecto de Compensación', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail'],
            'capability_type' => 'post',
        ]);

        // Logros obtenidos
        register_post_type('he_logro', [
            'labels' => [
                'name' => __('Logros Ecológicos', 'flavor-platform'),
                'singular_name' => __('Logro Ecológico', 'flavor-platform'),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Registra los manejadores AJAX
     */
    private function register_ajax_handlers(): void {
        add_action('wp_ajax_he_registrar_huella', [$this, 'ajax_registrar_huella']);
        add_action('wp_ajax_he_registrar_accion', [$this, 'ajax_registrar_accion']);
        add_action('wp_ajax_he_proponer_proyecto', [$this, 'ajax_proponer_proyecto']);
        add_action('wp_ajax_he_unirse_proyecto', [$this, 'ajax_unirse_proyecto']);
        add_action('wp_ajax_he_obtener_estadisticas', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_he_calcular_huella', [$this, 'ajax_calcular_huella']);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes(): void {
        // Shortcodes originales (legacy)
        add_shortcode('huella_ecologica_calculadora', [$this, 'shortcode_calculadora']);
        add_shortcode('huella_ecologica_mis_registros', [$this, 'shortcode_mis_registros']);
        add_shortcode('huella_ecologica_comunidad', [$this, 'shortcode_comunidad']);
        add_shortcode('huella_ecologica_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('huella_ecologica_logros', [$this, 'shortcode_logros']);

        // Shortcodes con prefijo flavor_ (nuevos)
        add_shortcode('flavor_huella_calculadora', [$this, 'shortcode_calculadora']);
        add_shortcode('flavor_huella_mis_registros', [$this, 'shortcode_mis_registros']);
        add_shortcode('flavor_huella_logros', [$this, 'shortcode_logros']);
        add_shortcode('flavor_huella_comunidad', [$this, 'shortcode_comunidad']);
        add_shortcode('flavor_huella_proyectos', [$this, 'shortcode_proyectos']);
    }

    /**
     * Encola los assets del módulo
     */
    public function enqueue_assets(): void {
        if (!$this->is_module_page()) {
            return;
        }

        wp_enqueue_style(
            'flavor-huella-ecologica',
            $this->get_module_url() . 'assets/css/huella-ecologica.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-huella-ecologica',
            $this->get_module_url() . 'assets/js/huella-ecologica.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-huella-ecologica', 'flavorHuellaEcologica', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huella_ecologica_nonce'),
            'categorias' => self::CATEGORIAS_HUELLA,
            'acciones' => self::TIPOS_ACCION,
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'success' => __('Registro guardado', 'flavor-platform'),
                'confirmar' => __('¿Estás seguro?', 'flavor-platform'),
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
        return has_shortcode($post->post_content, 'huella_ecologica_calculadora')
            || has_shortcode($post->post_content, 'huella_ecologica_mis_registros')
            || has_shortcode($post->post_content, 'huella_ecologica_comunidad')
            || has_shortcode($post->post_content, 'huella_ecologica_proyectos')
            || has_shortcode($post->post_content, 'huella_ecologica_logros')
            || has_shortcode($post->post_content, 'flavor_huella_calculadora')
            || has_shortcode($post->post_content, 'flavor_huella_mis_registros')
            || has_shortcode($post->post_content, 'flavor_huella_logros')
            || has_shortcode($post->post_content, 'flavor_huella_comunidad')
            || has_shortcode($post->post_content, 'flavor_huella_proyectos')
            || strpos($_SERVER['REQUEST_URI'], '/huella-ecologica') !== false;
    }

    /**
     * AJAX: Registrar huella diaria
     */
    public function ajax_registrar_huella(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $valor = floatval($_POST['valor'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (!isset(self::CATEGORIAS_HUELLA[$categoria])) {
            wp_send_json_error(['message' => __('Categoría no válida', 'flavor-platform')]);
        }

        if ($valor <= 0) {
            wp_send_json_error(['message' => __('El valor debe ser positivo', 'flavor-platform')]);
        }

        $registro_id = wp_insert_post([
            'post_type' => 'he_registro',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf('%s - %s', self::CATEGORIAS_HUELLA[$categoria]['nombre'], $fecha),
        ], true);

        if (is_wp_error($registro_id) || empty($registro_id)) {
            $error = is_wp_error($registro_id) ? $registro_id->get_error_message() : __('No se pudo registrar la huella.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($registro_id, '_he_fecha', $fecha);
        update_post_meta($registro_id, '_he_categoria', $categoria);
        update_post_meta($registro_id, '_he_valor', $valor);
        update_post_meta($registro_id, '_he_descripcion', $descripcion);

        // Verificar logros
        $this->verificar_logros($user_id);

        wp_send_json_success([
            'message' => __('Registro guardado correctamente', 'flavor-platform'),
            'registro_id' => $registro_id,
        ]);
    }

    /**
     * AJAX: Registrar acción reductora
     */
    public function ajax_registrar_accion(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $cantidad = intval($_POST['cantidad'] ?? 1);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        if (!isset(self::TIPOS_ACCION[$tipo])) {
            wp_send_json_error(['message' => __('Tipo de acción no válido', 'flavor-platform')]);
        }

        $accion_data = self::TIPOS_ACCION[$tipo];
        $reduccion_total = $accion_data['reduccion_estimada'] * $cantidad;

        $accion_id = wp_insert_post([
            'post_type' => 'he_accion',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $accion_data['nombre'],
        ], true);

        if (is_wp_error($accion_id) || empty($accion_id)) {
            $error = is_wp_error($accion_id) ? $accion_id->get_error_message() : __('No se pudo registrar la acción.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($accion_id, '_he_tipo', $tipo);
        update_post_meta($accion_id, '_he_fecha', $fecha);
        update_post_meta($accion_id, '_he_cantidad', $cantidad);
        update_post_meta($accion_id, '_he_reduccion', $reduccion_total);
        update_post_meta($accion_id, '_he_notas', $notas);

        // Verificar logros
        $this->verificar_logros($user_id);

        wp_send_json_success([
            'message' => sprintf(
                __('¡Genial! Has compensado %.1f kg de CO2', 'flavor-platform'),
                $reduccion_total
            ),
            'accion_id' => $accion_id,
            'reduccion' => $reduccion_total,
        ]);
    }

    /**
     * AJAX: Proponer proyecto de compensación
     */
    public function ajax_proponer_proyecto(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $meta_co2 = floatval($_POST['meta_co2'] ?? 0);
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $tipo_proyecto = sanitize_key($_POST['tipo_proyecto'] ?? 'reforestacion');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-platform')]);
        }

        $proyecto_id = wp_insert_post([
            'post_type' => 'he_proyecto',
            'post_status' => 'private',
            'post_author' => $user_id,
            'post_title' => $titulo,
            'post_content' => $descripcion,
        ], true);

        if (is_wp_error($proyecto_id) || empty($proyecto_id)) {
            $error = is_wp_error($proyecto_id) ? $proyecto_id->get_error_message() : __('No se pudo crear el proyecto.', 'flavor-platform');
            wp_send_json_error(['message' => $error]);
        }

        update_post_meta($proyecto_id, '_he_estado', 'propuesto');
        update_post_meta($proyecto_id, '_he_meta_co2', $meta_co2);
        update_post_meta($proyecto_id, '_he_co2_actual', 0);
        update_post_meta($proyecto_id, '_he_ubicacion', $ubicacion);
        update_post_meta($proyecto_id, '_he_tipo_proyecto', $tipo_proyecto);
        update_post_meta($proyecto_id, '_he_participantes', [$user_id]);
        update_post_meta($proyecto_id, '_he_fecha_propuesta', current_time('mysql'));

        // Otorgar logro de embajador
        $this->otorgar_logro($user_id, 'embajador');

        wp_send_json_success([
            'message' => __('Proyecto propuesto correctamente. Será revisado pronto.', 'flavor-platform'),
            'proyecto_id' => $proyecto_id,
        ]);
    }

    /**
     * AJAX: Unirse a proyecto de compensación
     */
    public function ajax_unirse_proyecto(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $proyecto_id = intval($_POST['proyecto_id'] ?? 0);

        $proyecto = get_post($proyecto_id);
        if (!$proyecto || $proyecto->post_type !== 'he_proyecto') {
            wp_send_json_error(['message' => __('Proyecto no encontrado', 'flavor-platform')]);
        }

        $estado = get_post_meta($proyecto_id, '_he_estado', true);
        if (!in_array($estado, ['aprobado', 'en_curso'])) {
            wp_send_json_error(['message' => __('Este proyecto no está abierto a participantes', 'flavor-platform')]);
        }

        $participantes = get_post_meta($proyecto_id, '_he_participantes', true) ?: [];
        if (in_array($user_id, $participantes)) {
            wp_send_json_error(['message' => __('Ya participas en este proyecto', 'flavor-platform')]);
        }

        $participantes[] = $user_id;
        update_post_meta($proyecto_id, '_he_participantes', $participantes);

        // Otorgar logro de compensador
        $this->otorgar_logro($user_id, 'compensador');

        wp_send_json_success([
            'message' => __('Te has unido al proyecto', 'flavor-platform'),
            'participantes' => count($participantes),
        ]);
    }

    /**
     * AJAX: Obtener estadísticas del usuario
     */
    public function ajax_obtener_estadisticas(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $user_id = get_current_user_id();
        $periodo = sanitize_key($_POST['periodo'] ?? 'mes');

        $stats = $this->get_estadisticas_usuario($user_id, $periodo);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Calcular huella estimada
     */
    public function ajax_calcular_huella(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        $datos = $_POST['datos'] ?? [];
        $huella_total = 0;
        $desglose = [];

        // Transporte
        if (!empty($datos['km_coche'])) {
            $co2_coche = floatval($datos['km_coche']) * 0.21; // kg CO2/km promedio
            $huella_total += $co2_coche;
            $desglose['transporte_coche'] = $co2_coche;
        }
        if (!empty($datos['km_avion'])) {
            $co2_avion = floatval($datos['km_avion']) * 0.255;
            $huella_total += $co2_avion;
            $desglose['transporte_avion'] = $co2_avion;
        }

        // Energía
        if (!empty($datos['kwh_mes'])) {
            $co2_energia = floatval($datos['kwh_mes']) * 0.38 / 30; // por día
            $huella_total += $co2_energia;
            $desglose['energia'] = $co2_energia;
        }

        // Alimentación
        if (!empty($datos['tipo_dieta'])) {
            $co2_dieta = [
                'omnivora' => 7.2,
                'flexitariana' => 4.7,
                'vegetariana' => 3.8,
                'vegana' => 2.9,
            ];
            $dieta_co2 = $co2_dieta[$datos['tipo_dieta']] ?? 5.0;
            $huella_total += $dieta_co2;
            $desglose['alimentacion'] = $dieta_co2;
        }

        // Consumo
        if (!empty($datos['compras_nuevas'])) {
            $co2_consumo = floatval($datos['compras_nuevas']) * 0.5;
            $huella_total += $co2_consumo;
            $desglose['consumo'] = $co2_consumo;
        }

        wp_send_json_success([
            'huella_diaria' => round($huella_total, 2),
            'huella_mensual' => round($huella_total * 30, 2),
            'huella_anual' => round($huella_total * 365, 2),
            'desglose' => $desglose,
            'comparativa' => [
                'media_espana' => 7.5,
                'objetivo_2030' => 4.0,
                'objetivo_2050' => 2.0,
            ],
        ]);
    }

    /**
     * Obtiene las estadísticas de un usuario
     */
    public function get_estadisticas_usuario(int $user_id, string $periodo = 'mes'): array {
        global $wpdb;

        $fecha_inicio = $this->get_fecha_inicio_periodo($periodo);

        // Huella total del período
        $huella_total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(pm.meta_value), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_valor'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_registro'
               AND p.post_author = %d
               AND pm2.meta_value >= %s",
            $user_id, $fecha_inicio
        ));

        // Reducción total del período
        $reduccion_total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(pm.meta_value), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_reduccion'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_accion'
               AND p.post_author = %d
               AND pm2.meta_value >= %s",
            $user_id, $fecha_inicio
        ));

        // Huella por categoría
        $huella_por_categoria = $wpdb->get_results($wpdb->prepare(
            "SELECT pm_cat.meta_value as categoria, SUM(pm_val.meta_value) as total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_he_categoria'
             INNER JOIN {$wpdb->postmeta} pm_val ON p.ID = pm_val.post_id AND pm_val.meta_key = '_he_valor'
             INNER JOIN {$wpdb->postmeta} pm_fecha ON p.ID = pm_fecha.post_id AND pm_fecha.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_registro'
               AND p.post_author = %d
               AND pm_fecha.meta_value >= %s
             GROUP BY pm_cat.meta_value",
            $user_id, $fecha_inicio
        ), ARRAY_A);

        // Acciones por tipo
        $acciones_por_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT pm_tipo.meta_value as tipo, COUNT(*) as cantidad, SUM(pm_red.meta_value) as reduccion
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = '_he_tipo'
             INNER JOIN {$wpdb->postmeta} pm_red ON p.ID = pm_red.post_id AND pm_red.meta_key = '_he_reduccion'
             INNER JOIN {$wpdb->postmeta} pm_fecha ON p.ID = pm_fecha.post_id AND pm_fecha.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_accion'
               AND p.post_author = %d
               AND pm_fecha.meta_value >= %s
             GROUP BY pm_tipo.meta_value",
            $user_id, $fecha_inicio
        ), ARRAY_A);

        // Logros del usuario
        $logros = $this->get_logros_usuario($user_id);

        // Proyectos donde participa
        $proyectos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_participantes'
             WHERE p.post_type = 'he_proyecto'
               AND pm.meta_value LIKE %s",
            '%"' . $user_id . '"%'
        ));

        return [
            'huella_total' => round(floatval($huella_total), 2),
            'reduccion_total' => round(floatval($reduccion_total), 2),
            'huella_neta' => round(floatval($huella_total) - floatval($reduccion_total), 2),
            'huella_por_categoria' => $huella_por_categoria,
            'acciones_por_tipo' => $acciones_por_tipo,
            'logros' => $logros,
            'proyectos' => intval($proyectos),
            'periodo' => $periodo,
        ];
    }

    /**
     * Obtiene la fecha de inicio según el período
     */
    private function get_fecha_inicio_periodo(string $periodo): string {
        switch ($periodo) {
            case 'semana':
                return date('Y-m-d', strtotime('-7 days'));
            case 'mes':
                return date('Y-m-01');
            case 'anio':
                return date('Y-01-01');
            case 'total':
                return '1970-01-01';
            default:
                return date('Y-m-01');
        }
    }

    /**
     * Obtiene los logros de un usuario
     */
    public function get_logros_usuario(int $user_id): array {
        $logros_obtenidos = get_user_meta($user_id, '_he_logros', true) ?: [];
        $resultado = [];

        foreach (self::LOGROS as $logro_id => $logro_data) {
            $resultado[] = [
                'id' => $logro_id,
                'nombre' => $logro_data['nombre'],
                'descripcion' => $logro_data['descripcion'],
                'icono' => $logro_data['icono'],
                'puntos' => $logro_data['puntos'],
                'obtenido' => isset($logros_obtenidos[$logro_id]),
                'fecha_obtenido' => $logros_obtenidos[$logro_id] ?? null,
            ];
        }

        return $resultado;
    }

    /**
     * Verifica y otorga logros
     */
    private function verificar_logros(int $user_id): void {
        global $wpdb;

        $logros_obtenidos = get_user_meta($user_id, '_he_logros', true) ?: [];

        // Primera medición
        if (!isset($logros_obtenidos['primera_medicion'])) {
            $tiene_registro = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_type = 'he_registro' AND post_author = %d",
                $user_id
            ));
            if ($tiene_registro > 0) {
                $this->otorgar_logro($user_id, 'primera_medicion');
            }
        }

        // Semana verde
        if (!isset($logros_obtenidos['semana_verde'])) {
            $dias_consecutivos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.meta_value)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_fecha'
                 WHERE p.post_type = 'he_accion'
                   AND p.post_author = %d
                   AND pm.meta_value >= %s",
                $user_id, date('Y-m-d', strtotime('-7 days'))
            ));
            if ($dias_consecutivos >= 7) {
                $this->otorgar_logro($user_id, 'semana_verde');
            }
        }
    }

    /**
     * Otorga un logro a un usuario
     */
    private function otorgar_logro(int $user_id, string $logro_id): void {
        if (!isset(self::LOGROS[$logro_id])) {
            return;
        }

        $logros_obtenidos = get_user_meta($user_id, '_he_logros', true) ?: [];

        if (isset($logros_obtenidos[$logro_id])) {
            return;
        }

        $logros_obtenidos[$logro_id] = current_time('mysql');
        update_user_meta($user_id, '_he_logros', $logros_obtenidos);

        // Registrar en post type para historial
        wp_insert_post([
            'post_type' => 'he_logro',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => self::LOGROS[$logro_id]['nombre'],
            'meta_input' => [
                '_he_logro_id' => $logro_id,
                '_he_puntos' => self::LOGROS[$logro_id]['puntos'],
            ],
        ]);
    }

    /**
     * Obtiene estadísticas de la comunidad
     */
    public function get_estadisticas_comunidad(): array {
        global $wpdb;

        $mes_actual = date('Y-m-01');

        // Total huella comunidad este mes
        $huella_comunidad = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(pm.meta_value), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_valor'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_registro'
               AND pm2.meta_value >= %s",
            $mes_actual
        ));

        // Total reducción comunidad este mes
        $reduccion_comunidad = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(pm.meta_value), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_reduccion'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_accion'
               AND pm2.meta_value >= %s",
            $mes_actual
        ));

        // Usuarios participando
        $usuarios_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.post_author)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_fecha'
             WHERE p.post_type IN ('he_registro', 'he_accion')
               AND pm.meta_value >= %s",
            $mes_actual
        ));

        // Proyectos activos
        $proyectos_activos = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'he_proyecto'
               AND pm.meta_key = '_he_estado'
               AND pm.meta_value IN ('aprobado', 'en_curso')"
        );

        // Top 5 contribuyentes
        $top_contribuyentes = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_author, SUM(pm.meta_value) as reduccion_total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_he_reduccion'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type = 'he_accion'
               AND pm2.meta_value >= %s
             GROUP BY p.post_author
             ORDER BY reduccion_total DESC
             LIMIT 5",
            $mes_actual
        ));

        return [
            'huella_comunidad' => round(floatval($huella_comunidad), 1),
            'reduccion_comunidad' => round(floatval($reduccion_comunidad), 1),
            'huella_neta' => round(floatval($huella_comunidad) - floatval($reduccion_comunidad), 1),
            'usuarios_activos' => intval($usuarios_activos),
            'proyectos_activos' => intval($proyectos_activos),
            'top_contribuyentes' => $top_contribuyentes,
        ];
    }

    /**
     * Shortcode: Calculadora de huella
     */
    public function shortcode_calculadora($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/calculadora.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis registros
     */
    public function shortcode_mis_registros($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="he-login-required">' . __('Inicia sesión para ver tus registros', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/mis-registros.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas de comunidad
     */
    public function shortcode_comunidad($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/comunidad.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos de compensación
     */
    public function shortcode_proyectos($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/proyectos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Logros
     */
    public function shortcode_logros($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="he-login-required">' . __('Inicia sesión para ver tus logros', 'flavor-platform') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/logros.php';
        return ob_get_clean();
    }

    /**
     * Registra el widget de dashboard
     */
    public function register_dashboard_widget($registry): void {
        $widget_file = $this->get_module_path() . 'class-huella-ecologica-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('Flavor_Huella_Ecologica_Widget')) {
                $registry->register(new Flavor_Huella_Ecologica_Widget($this));
            }
        }
    }

    /**
     * Configuración del módulo para admin
     */
    public function get_admin_config(): array {
        return [
            'id' => 'huella_ecologica',
            'label' => __('Huella Ecológica', 'flavor-platform'),
            'icon' => 'dashicons-palmtree',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'huella-ecologica',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'he-usuarios',
                    'titulo' => __('Usuarios', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_usuarios'],
                ],
                [
                    'slug' => 'he-proyectos',
                    'titulo' => __('Proyectos', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_proyectos'],
                    'badge' => [$this, 'contar_proyectos_pendientes'],
                ],
            ],
            'estadisticas' => [$this, 'get_admin_estadisticas'],
            'settings' => [
                'he_meta_reduccion_anual' => [
                    'label' => __('Meta de reducción anual (kg CO2)', 'flavor-platform'),
                    'type' => 'number',
                    'default' => 10000,
                ],
                'he_factores_emision_personalizados' => [
                    'label' => __('Usar factores de emisión personalizados', 'flavor-platform'),
                    'type' => 'checkbox',
                    'default' => false,
                ],
            ],
        ];
    }

    /**
     * Obtiene estadísticas para el panel de admin
     *
     * @return array
     */
    public function get_admin_estadisticas(): array {
        $stats = $this->get_estadisticas_comunidad();
        return [
            'usuarios_activos' => $stats['usuarios_activos'],
            'huella_total' => $stats['huella_comunidad'] . ' kg CO2',
            'reduccion_total' => $stats['reduccion_comunidad'] . ' kg CO2',
            'proyectos_activos' => $stats['proyectos_activos'],
        ];
    }

    /**
     * Cuenta proyectos pendientes de aprobación
     *
     * @return int
     */
    public function contar_proyectos_pendientes(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'he_proyecto'
               AND pm.meta_key = '_he_estado'
               AND pm.meta_value = 'propuesto'"
        );
    }

    /**
     * Renderiza el dashboard de administración del módulo
     */
    public function render_admin_dashboard(): void {
        global $wpdb;

        $stats = $this->get_estadisticas_comunidad();
        $meta_anual = get_option('he_meta_reduccion_anual', 10000);
        $porcentaje_meta = $meta_anual > 0 ? min(100, round(($stats['reduccion_comunidad'] / $meta_anual) * 100, 1)) : 0;

        // Datos de las últimas semanas
        $datos_semanas = $wpdb->get_results(
            "SELECT
                YEARWEEK(pm2.meta_value, 1) as semana,
                SUM(CASE WHEN p.post_type = 'he_registro' THEN pm.meta_value ELSE 0 END) as huella,
                SUM(CASE WHEN p.post_type = 'he_accion' THEN pm.meta_value ELSE 0 END) as reduccion
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_he_valor', '_he_reduccion')
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type IN ('he_registro', 'he_accion')
               AND p.post_status = 'publish'
               AND pm2.meta_value >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
             GROUP BY YEARWEEK(pm2.meta_value, 1)
             ORDER BY semana DESC
             LIMIT 8",
            ARRAY_A
        );

        // Categorías más impactantes
        $huella_categorias = $wpdb->get_results(
            "SELECT pm_cat.meta_value as categoria, SUM(pm_val.meta_value) as total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_he_categoria'
             INNER JOIN {$wpdb->postmeta} pm_val ON p.ID = pm_val.post_id AND pm_val.meta_key = '_he_valor'
             WHERE p.post_type = 'he_registro' AND p.post_status = 'publish'
             GROUP BY pm_cat.meta_value
             ORDER BY total DESC",
            ARRAY_A
        );

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Huella Ecológica - Dashboard', 'flavor-platform'), [
                ['label' => __('Exportar Informe', 'flavor-platform'), 'url' => '#', 'class' => 'button'],
            ]); ?>

            <!-- KPIs principales -->
            <div class="flavor-kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-groups" style="font-size: 32px; color: #3498db;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo esc_html($stats['usuarios_activos']); ?></div>
                            <div style="color: #646970; font-size: 13px;"><?php _e('Usuarios participando', 'flavor-platform'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-chart-area" style="font-size: 32px; color: #e74c3c;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo esc_html(number_format($stats['huella_comunidad'], 1)); ?></div>
                            <div style="color: #646970; font-size: 13px;"><?php _e('Huella total (kg CO2)', 'flavor-platform'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #27ae60;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: bold; color: #27ae60;"><?php echo esc_html(number_format($stats['reduccion_comunidad'], 1)); ?></div>
                            <div style="color: #646970; font-size: 13px;"><?php _e('Reducción lograda (kg CO2)', 'flavor-platform'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-portfolio" style="font-size: 32px; color: #9b59b6;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo esc_html($stats['proyectos_activos']); ?></div>
                            <div style="color: #646970; font-size: 13px;"><?php _e('Proyectos activos', 'flavor-platform'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progreso hacia meta anual -->
            <div class="flavor-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin-top: 0;"><?php _e('Progreso hacia la meta anual', 'flavor-platform'); ?></h3>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="flex: 1;">
                        <div style="background: #f0f0f0; border-radius: 10px; height: 20px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, #27ae60, #2ecc71); height: 100%; width: <?php echo esc_attr($porcentaje_meta); ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <div style="min-width: 150px; text-align: right;">
                        <strong><?php echo esc_html($porcentaje_meta); ?>%</strong>
                        <span style="color: #646970;"> (<?php echo esc_html(number_format($stats['reduccion_comunidad'], 0)); ?> / <?php echo esc_html(number_format($meta_anual, 0)); ?> kg CO2)</span>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Tabla de categorías -->
                <div class="flavor-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0;"><?php _e('Huella por categoría', 'flavor-platform'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Categoría', 'flavor-platform'); ?></th>
                                <th style="text-align: right;"><?php _e('Total (kg CO2)', 'flavor-platform'); ?></th>
                                <th style="text-align: right;"><?php _e('% del total', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_huella = array_sum(array_column($huella_categorias, 'total'));
                            foreach ($huella_categorias as $categoria_data):
                                $categoria_info = self::CATEGORIAS_HUELLA[$categoria_data['categoria']] ?? ['nombre' => $categoria_data['categoria'], 'color' => '#666', 'icono' => 'dashicons-marker'];
                                $porcentaje_categoria = $total_huella > 0 ? round(($categoria_data['total'] / $total_huella) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="dashicons <?php echo esc_attr($categoria_info['icono']); ?>" style="color: <?php echo esc_attr($categoria_info['color']); ?>;"></span>
                                    <?php echo esc_html($categoria_info['nombre']); ?>
                                </td>
                                <td style="text-align: right;"><?php echo esc_html(number_format($categoria_data['total'], 1)); ?></td>
                                <td style="text-align: right;">
                                    <span style="display: inline-block; width: 50px; background: #f0f0f0; border-radius: 3px; margin-right: 5px;">
                                        <span style="display: block; width: <?php echo esc_attr($porcentaje_categoria); ?>%; background: <?php echo esc_attr($categoria_info['color']); ?>; height: 8px; border-radius: 3px;"></span>
                                    </span>
                                    <?php echo esc_html($porcentaje_categoria); ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($huella_categorias)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #646970; padding: 20px;">
                                    <?php _e('No hay datos de huella registrados aún', 'flavor-platform'); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Top contribuyentes -->
                <div class="flavor-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0;"><?php _e('Top contribuyentes', 'flavor-platform'); ?></h3>
                    <?php if (!empty($stats['top_contribuyentes'])): ?>
                    <ol style="margin: 0; padding-left: 20px;">
                        <?php foreach ($stats['top_contribuyentes'] as $index => $contribuyente):
                            $usuario = get_userdata($contribuyente->post_author);
                            $nombre_usuario = $usuario ? $usuario->display_name : __('Usuario', 'flavor-platform');
                            $medallas = ['🥇', '🥈', '🥉', '4.', '5.'];
                        ?>
                        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                <span style="font-size: 18px;"><?php echo $medallas[$index] ?? ($index + 1) . '.'; ?></span>
                                <?php echo esc_html($nombre_usuario); ?>
                            </span>
                            <strong style="color: #27ae60;"><?php echo esc_html(number_format($contribuyente->reduccion_total, 1)); ?> kg</strong>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else: ?>
                    <p style="color: #646970; text-align: center; padding: 20px;"><?php _e('No hay contribuyentes aún', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la vista de usuarios con sus métricas
     */
    public function render_admin_usuarios(): void {
        global $wpdb;

        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $por_pagina = 20;
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Usuarios con actividad
        $usuarios_query = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.post_author as usuario_id,
                COUNT(DISTINCT CASE WHEN p.post_type = 'he_registro' THEN p.ID END) as total_registros,
                COUNT(DISTINCT CASE WHEN p.post_type = 'he_accion' THEN p.ID END) as total_acciones,
                COALESCE(SUM(CASE WHEN p.post_type = 'he_registro' THEN pm.meta_value ELSE 0 END), 0) as huella_total,
                COALESCE(SUM(CASE WHEN p.post_type = 'he_accion' THEN pm.meta_value ELSE 0 END), 0) as reduccion_total,
                MAX(pm2.meta_value) as ultima_actividad
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_he_valor', '_he_reduccion')
             LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_he_fecha'
             WHERE p.post_type IN ('he_registro', 'he_accion')
               AND p.post_status = 'publish'
             GROUP BY p.post_author
             ORDER BY reduccion_total DESC
             LIMIT %d OFFSET %d",
            $por_pagina,
            $offset
        ), ARRAY_A);

        $total_usuarios = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author)
             FROM {$wpdb->posts}
             WHERE post_type IN ('he_registro', 'he_accion') AND post_status = 'publish'"
        );
        $total_paginas = ceil($total_usuarios / $por_pagina);

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Usuarios - Huella Ecológica', 'flavor-platform'), [
                ['label' => __('Exportar CSV', 'flavor-platform'), 'url' => '#', 'class' => 'button'],
            ]); ?>

            <div class="flavor-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                            <th style="text-align: center;"><?php _e('Registros', 'flavor-platform'); ?></th>
                            <th style="text-align: center;"><?php _e('Acciones', 'flavor-platform'); ?></th>
                            <th style="text-align: right;"><?php _e('Huella (kg CO2)', 'flavor-platform'); ?></th>
                            <th style="text-align: right;"><?php _e('Reducción (kg CO2)', 'flavor-platform'); ?></th>
                            <th style="text-align: right;"><?php _e('Balance neto', 'flavor-platform'); ?></th>
                            <th><?php _e('Última actividad', 'flavor-platform'); ?></th>
                            <th><?php _e('Logros', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_query as $usuario_data):
                            $usuario = get_userdata($usuario_data['usuario_id']);
                            $nombre_usuario = $usuario ? $usuario->display_name : __('Usuario #', 'flavor-platform') . $usuario_data['usuario_id'];
                            $email_usuario = $usuario ? $usuario->user_email : '';
                            $balance = floatval($usuario_data['huella_total']) - floatval($usuario_data['reduccion_total']);
                            $logros_usuario = $this->get_logros_usuario($usuario_data['usuario_id']);
                            $logros_obtenidos = array_filter($logros_usuario, function($logro) { return $logro['obtenido']; });
                        ?>
                        <tr>
                            <td>
                                <?php echo get_avatar($usuario_data['usuario_id'], 32, '', '', ['style' => 'vertical-align: middle; margin-right: 8px; border-radius: 50%;']); ?>
                                <strong><?php echo esc_html($nombre_usuario); ?></strong>
                                <?php if ($email_usuario): ?>
                                <br><small style="color: #646970;"><?php echo esc_html($email_usuario); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;"><?php echo esc_html($usuario_data['total_registros']); ?></td>
                            <td style="text-align: center;"><?php echo esc_html($usuario_data['total_acciones']); ?></td>
                            <td style="text-align: right; color: #e74c3c;"><?php echo esc_html(number_format($usuario_data['huella_total'], 1)); ?></td>
                            <td style="text-align: right; color: #27ae60;"><?php echo esc_html(number_format($usuario_data['reduccion_total'], 1)); ?></td>
                            <td style="text-align: right;">
                                <span style="color: <?php echo $balance > 0 ? '#e74c3c' : '#27ae60'; ?>; font-weight: bold;">
                                    <?php echo $balance > 0 ? '+' : ''; ?><?php echo esc_html(number_format($balance, 1)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usuario_data['ultima_actividad']): ?>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($usuario_data['ultima_actividad']))); ?>
                                <?php else: ?>
                                    <span style="color: #646970;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $total_logros_obtenidos = count($logros_obtenidos);
                                foreach (array_slice($logros_obtenidos, 0, 3) as $logro): ?>
                                    <span title="<?php echo esc_attr($logro['nombre']); ?>" style="font-size: 16px;"><?php echo esc_html($logro['icono']); ?></span>
                                <?php endforeach; ?>
                                <?php if ($total_logros_obtenidos > 3): ?>
                                    <span style="color: #646970; font-size: 12px;">+<?php echo $total_logros_obtenidos - 3; ?></span>
                                <?php endif; ?>
                                <?php if ($total_logros_obtenidos === 0): ?>
                                    <span style="color: #646970;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios_query)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #646970; padding: 40px;">
                                <?php _e('No hay usuarios con actividad registrada', 'flavor-platform'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(__('%d usuarios', 'flavor-platform'), $total_usuarios); ?></span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $pagina_actual,
                                'total' => $total_paginas,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            ]);
                            ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la vista de proyectos de compensación
     */
    public function render_admin_proyectos(): void {
        global $wpdb;

        // Procesar cambio de estado si se envió
        if (isset($_POST['he_proyecto_action']) && isset($_POST['he_proyecto_nonce'])) {
            if (wp_verify_nonce($_POST['he_proyecto_nonce'], 'he_proyecto_action')) {
                $proyecto_id = intval($_POST['proyecto_id']);
                $nuevo_estado = sanitize_key($_POST['nuevo_estado']);
                if (isset(self::ESTADOS_PROYECTO[$nuevo_estado])) {
                    update_post_meta($proyecto_id, '_he_estado', $nuevo_estado);
                    if ($nuevo_estado === 'aprobado') {
                        wp_update_post(['ID' => $proyecto_id, 'post_status' => 'publish']);
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Estado del proyecto actualizado.', 'flavor-platform') . '</p></div>';
                }
            }
        }

        // Filtro por estado
        $filtro_estado = isset($_GET['estado']) ? sanitize_key($_GET['estado']) : '';

        // Obtener proyectos
        $args_query = [
            'post_type' => 'he_proyecto',
            'post_status' => ['publish', 'private'],
            'posts_per_page' => 20,
            'paged' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
        ];

        if ($filtro_estado && isset(self::ESTADOS_PROYECTO[$filtro_estado])) {
            $args_query['meta_query'] = [
                ['key' => '_he_estado', 'value' => $filtro_estado],
            ];
        }

        $proyectos_query = new WP_Query($args_query);

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Proyectos de Compensación', 'flavor-platform'), [
                ['label' => __('Añadir proyecto', 'flavor-platform'), 'url' => admin_url('post-new.php?post_type=he_proyecto'), 'class' => 'button button-primary'],
            ]); ?>

            <!-- Filtros -->
            <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo esc_url(remove_query_arg('estado')); ?>"
                   class="button <?php echo empty($filtro_estado) ? 'button-primary' : ''; ?>">
                    <?php _e('Todos', 'flavor-platform'); ?>
                </a>
                <?php foreach (self::ESTADOS_PROYECTO as $estado_key => $estado_info):
                    $count_estado = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts} p
                         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                         WHERE p.post_type = 'he_proyecto' AND pm.meta_key = '_he_estado' AND pm.meta_value = %s",
                        $estado_key
                    ));
                ?>
                    <a href="<?php echo esc_url(add_query_arg('estado', $estado_key)); ?>"
                       class="button <?php echo $filtro_estado === $estado_key ? 'button-primary' : ''; ?>"
                       style="border-left: 3px solid <?php echo esc_attr($estado_info['color']); ?>;">
                        <?php echo esc_html($estado_info['nombre']); ?> (<?php echo $count_estado; ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="flavor-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Proyecto', 'flavor-platform'); ?></th>
                            <th><?php _e('Proponente', 'flavor-platform'); ?></th>
                            <th style="text-align: center;"><?php _e('Estado', 'flavor-platform'); ?></th>
                            <th style="text-align: right;"><?php _e('Meta CO2', 'flavor-platform'); ?></th>
                            <th style="text-align: right;"><?php _e('Logrado', 'flavor-platform'); ?></th>
                            <th style="text-align: center;"><?php _e('Participantes', 'flavor-platform'); ?></th>
                            <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                            <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($proyectos_query->have_posts()): $proyectos_query->the_post();
                            $proyecto_id = get_the_ID();
                            $estado_actual = get_post_meta($proyecto_id, '_he_estado', true) ?: 'propuesto';
                            $estado_info = self::ESTADOS_PROYECTO[$estado_actual] ?? self::ESTADOS_PROYECTO['propuesto'];
                            $meta_co2 = get_post_meta($proyecto_id, '_he_meta_co2', true) ?: 0;
                            $co2_logrado = get_post_meta($proyecto_id, '_he_co2_actual', true) ?: 0;
                            $participantes = get_post_meta($proyecto_id, '_he_participantes', true) ?: [];
                            $ubicacion = get_post_meta($proyecto_id, '_he_ubicacion', true);
                            $tipo_proyecto = get_post_meta($proyecto_id, '_he_tipo_proyecto', true);
                            $progreso = $meta_co2 > 0 ? min(100, round(($co2_logrado / $meta_co2) * 100, 1)) : 0;
                            $autor = get_userdata(get_the_author_meta('ID'));
                        ?>
                        <tr>
                            <td>
                                <strong><?php the_title(); ?></strong>
                                <?php if ($ubicacion): ?>
                                <br><small style="color: #646970;"><span class="dashicons dashicons-location" style="font-size: 14px;"></span> <?php echo esc_html($ubicacion); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo get_avatar(get_the_author_meta('ID'), 24, '', '', ['style' => 'vertical-align: middle; margin-right: 5px; border-radius: 50%;']); ?>
                                <?php echo esc_html($autor ? $autor->display_name : __('Usuario', 'flavor-platform')); ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; background: <?php echo esc_attr($estado_info['color']); ?>; color: #fff; font-size: 12px;">
                                    <?php echo esc_html($estado_info['nombre']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;"><?php echo esc_html(number_format($meta_co2, 0)); ?> kg</td>
                            <td style="text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <div style="width: 60px; background: #f0f0f0; border-radius: 3px; height: 8px;">
                                        <div style="width: <?php echo esc_attr($progreso); ?>%; background: #27ae60; height: 100%; border-radius: 3px;"></div>
                                    </div>
                                    <span><?php echo esc_html(number_format($co2_logrado, 0)); ?> kg</span>
                                </div>
                            </td>
                            <td style="text-align: center;"><?php echo count($participantes); ?></td>
                            <td><?php echo get_the_date(); ?></td>
                            <td>
                                <form method="post" style="display: inline-flex; gap: 5px; align-items: center;">
                                    <?php wp_nonce_field('he_proyecto_action', 'he_proyecto_nonce'); ?>
                                    <input type="hidden" name="he_proyecto_action" value="1">
                                    <input type="hidden" name="proyecto_id" value="<?php echo esc_attr($proyecto_id); ?>">
                                    <select name="nuevo_estado" style="min-width: 100px;">
                                        <?php foreach (self::ESTADOS_PROYECTO as $key_estado => $info_estado): ?>
                                            <option value="<?php echo esc_attr($key_estado); ?>" <?php selected($estado_actual, $key_estado); ?>>
                                                <?php echo esc_html($info_estado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button button-small"><?php _e('Cambiar', 'flavor-platform'); ?></button>
                                </form>
                                <a href="<?php echo get_edit_post_link($proyecto_id); ?>" class="button button-small" title="<?php _e('Editar', 'flavor-platform'); ?>">
                                    <span class="dashicons dashicons-edit" style="line-height: 1.3;"></span>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                        <?php if (!$proyectos_query->have_posts()): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #646970; padding: 40px;">
                                <?php _e('No hay proyectos que coincidan con los filtros', 'flavor-platform'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($proyectos_query->max_num_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(__('%d proyectos', 'flavor-platform'), $proyectos_query->found_posts); ?></span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => max(1, get_query_var('paged')),
                                'total' => $proyectos_query->max_num_pages,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            ]);
                            ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
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
            'nombre' => 'Huella Ecológica Comunitaria',
            'puntuacion' => 90,
            'premisas' => [
                'interdependencia_radical' => 0.30, // Conexión con ecosistema
                'madurez_ciclica' => 0.25, // Ciclos naturales
                'valor_intrinseco' => 0.20, // Valor de la naturaleza
                'conciencia_fundamental' => 0.15, // Conciencia ecológica
                'abundancia_organizable' => 0.10, // Organizar recursos
            ],
            'descripcion_contribucion' => 'Este módulo promueve la conciencia ecológica colectiva, ' .
                'reconociendo nuestra interdependencia con los ecosistemas y el valor intrínseco ' .
                'de la naturaleza más allá del uso humano.',
            'categoria' => 'ecologia',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'calcular_huella' => [
                'description' => 'Calcular mi huella ecológica',
                'params' => ['categoria'],
            ],
            'registrar_accion' => [
                'description' => 'Registrar acción reductora de huella',
                'params' => ['tipo', 'descripcion'],
            ],
            'ver_estadisticas' => [
                'description' => 'Ver estadísticas de mi huella',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'calcular_huella',
            'calculadora' => 'calcular_huella',
            'mi_huella' => 'mis_registros',
            'mis_items' => 'mis_registros',
            'mis-registros' => 'mis_registros',
            'compensar' => 'proyectos_compensacion',
            'ranking' => 'ver_logros',
            'logros' => 'ver_logros',
            'comunidad' => 'ver_comunidad',
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

    private function action_calcular_huella($params) {
        return ['success' => true, 'html' => do_shortcode('[huella_ecologica_calculadora]')];
    }

    private function action_mis_registros($params) {
        return ['success' => true, 'html' => do_shortcode('[huella_ecologica_mis_registros]')];
    }

    private function action_ver_comunidad($params) {
        return ['success' => true, 'html' => do_shortcode('[huella_ecologica_comunidad]')];
    }

    private function action_proyectos_compensacion($params) {
        return ['success' => true, 'html' => do_shortcode('[huella_ecologica_proyectos]')];
    }

    private function action_ver_logros($params) {
        return ['success' => true, 'html' => do_shortcode('[huella_ecologica_logros]')];
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
        return __('La Huella Ecológica mide el impacto ambiental personal y comunitario, permitiendo calcular, registrar y compensar emisiones de CO2.', 'flavor-platform');
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'huella-ecologica',
            'title'    => __('Huella Ecológica', 'flavor-platform'),
            'subtitle' => __('Mide y reduce tu impacto ambiental', 'flavor-platform'),
            'icon'     => '🌍',
            'color'    => 'info', // Usa variable CSS --flavor-info del tema

            'database' => [
                'table'       => 'flavor_huella_ecologica',
                'primary_key' => 'id',
            ],

            'fields' => [
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', 'flavor-platform'), 'options' => ['transporte', 'energia', 'alimentacion', 'consumo', 'residuos']],
                'tipo'        => ['type' => 'select', 'label' => __('Tipo', 'flavor-platform'), 'options' => ['emision', 'compensacion']],
                'cantidad'    => ['type' => 'number', 'label' => __('Cantidad CO₂ (kg)', 'flavor-platform'), 'step' => '0.1'],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'fecha'       => ['type' => 'date', 'label' => __('Fecha', 'flavor-platform')],
            ],

            'estados' => [
                'registrado' => ['label' => __('Registrado', 'flavor-platform'), 'color' => 'blue', 'icon' => '📝'],
                'verificado' => ['label' => __('Verificado', 'flavor-platform'), 'color' => 'green', 'icon' => '✅'],
                'compensado' => ['label' => __('Compensado', 'flavor-platform'), 'color' => 'teal', 'icon' => '🌱'],
            ],

            'stats' => [
                'huella_total'    => ['label' => __('Mi huella (kg CO₂)', 'flavor-platform'), 'icon' => '🌍', 'color' => 'teal'],
                'compensado'      => ['label' => __('Compensado', 'flavor-platform'), 'icon' => '🌱', 'color' => 'green'],
                'balance'         => ['label' => __('Balance neto', 'flavor-platform'), 'icon' => '⚖️', 'color' => 'blue'],
                'ranking_comunidad' => ['label' => __('Posición ranking', 'flavor-platform'), 'icon' => '🏆', 'color' => 'amber'],
            ],

            'card' => [
                'template'     => 'huella-card',
                'title_field'  => 'categoria',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['cantidad', 'fecha'],
                'show_estado'  => true,
            ],

            'tabs' => [
                'calculadora' => [
                    'label'   => __('Calculadora', 'flavor-platform'),
                    'icon'    => 'dashicons-calculator',
                    'content' => 'shortcode:huella_ecologica_calculadora',
                    'public'  => true,
                ],
                'mi-huella' => [
                    'label'      => __('Mi huella', 'flavor-platform'),
                    'icon'       => 'dashicons-chart-pie',
                    'content'    => 'shortcode:huella_ecologica_mis_registros',
                    'requires_login' => true,
                ],
                'registrar' => [
                    'label'      => __('Registrar', 'flavor-platform'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:huella_ecologica_calculadora',
                    'requires_login' => true,
                ],
                'compensar' => [
                    'label'      => __('Compensar', 'flavor-platform'),
                    'icon'       => 'dashicons-palmtree',
                    'content'    => 'shortcode:huella_ecologica_proyectos',
                    'requires_login' => true,
                ],
                'ranking' => [
                    'label'   => __('Ranking', 'flavor-platform'),
                    'icon'    => 'dashicons-awards',
                    'content' => 'shortcode:huella_ecologica_logros',
                    'public'  => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 20,
                'order_by'   => 'fecha',
                'order'      => 'DESC',
                'filterable' => ['categoria', 'tipo'],
            ],

            'dashboard' => [
                'widgets' => ['grafico_huella', 'balance_mensual', 'consejos', 'comparativa_comunidad'],
                'actions' => [
                    'calcular'  => ['label' => __('Calcular huella', 'flavor-platform'), 'icon' => '📊', 'color' => 'teal'],
                    'compensar' => ['label' => __('Compensar', 'flavor-platform'), 'icon' => '🌱', 'color' => 'green'],
                ],
            ],

            'features' => [
                'calculadora'    => true,
                'graficos'       => true,
                'gamificacion'   => true,
                'consejos'       => true,
                'comparativas'   => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-huella-ecologica-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Huella_Ecologica_Dashboard_Tab')) {
                Flavor_Huella_Ecologica_Dashboard_Tab::get_instance();
            }
        }
    }
}

// Legacy alias for backward compatibility
if (!class_exists('Flavor_Chat_Huella_Ecologica_Module', false)) {
    class_alias('Flavor_Platform_Huella_Ecologica_Module', 'Flavor_Chat_Huella_Ecologica_Module');
}
