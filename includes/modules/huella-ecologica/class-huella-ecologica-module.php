<?php
/**
 * Módulo: Huella Ecológica Comunitaria
 *
 * Sistema de medición y reducción del impacto ambiental colectivo.
 * Permite calcular, registrar y compensar la huella ecológica.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Huella Ecológica Comunitaria
 */
class Flavor_Chat_Huella_Ecologica_Module extends Flavor_Chat_Module_Base {

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
        $this->module_name = __('Huella Ecológica Comunitaria', 'flavor-chat-ia');
        $this->module_description = __('Sistema de medición y reducción del impacto ambiental colectivo', 'flavor-chat-ia');
        $this->module_icon = 'dashicons-palmtree';
        $this->module_color = '#27ae60';

        parent::__construct();
    }

    /**
     * Inicializa el módulo
     */
    public function init(): void {
        $this->register_post_types();
        $this->register_ajax_handlers();
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
    }

    /**
     * Registra los tipos de post personalizados
     */
    private function register_post_types(): void {
        // Registros de huella individual
        register_post_type('he_registro', [
            'labels' => [
                'name' => __('Registros de Huella', 'flavor-chat-ia'),
                'singular_name' => __('Registro de Huella', 'flavor-chat-ia'),
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
                'name' => __('Acciones Reductoras', 'flavor-chat-ia'),
                'singular_name' => __('Acción Reductora', 'flavor-chat-ia'),
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
                'name' => __('Proyectos de Compensación', 'flavor-chat-ia'),
                'singular_name' => __('Proyecto de Compensación', 'flavor-chat-ia'),
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
                'name' => __('Logros Ecológicos', 'flavor-chat-ia'),
                'singular_name' => __('Logro Ecológico', 'flavor-chat-ia'),
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
    private function register_shortcodes(): void {
        add_shortcode('huella_ecologica_calculadora', [$this, 'shortcode_calculadora']);
        add_shortcode('huella_ecologica_mis_registros', [$this, 'shortcode_mis_registros']);
        add_shortcode('huella_ecologica_comunidad', [$this, 'shortcode_comunidad']);
        add_shortcode('huella_ecologica_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('huella_ecologica_logros', [$this, 'shortcode_logros']);
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
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-huella-ecologica',
            $this->get_module_url() . 'assets/js/huella-ecologica.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-huella-ecologica', 'flavorHuellaEcologica', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huella_ecologica_nonce'),
            'categorias' => self::CATEGORIAS_HUELLA,
            'acciones' => self::TIPOS_ACCION,
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'success' => __('Registro guardado', 'flavor-chat-ia'),
                'confirmar' => __('¿Estás seguro?', 'flavor-chat-ia'),
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
            || strpos($_SERVER['REQUEST_URI'], '/huella-ecologica') !== false;
    }

    /**
     * AJAX: Registrar huella diaria
     */
    public function ajax_registrar_huella(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $valor = floatval($_POST['valor'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (!isset(self::CATEGORIAS_HUELLA[$categoria])) {
            wp_send_json_error(['message' => __('Categoría no válida', 'flavor-chat-ia')]);
        }

        if ($valor <= 0) {
            wp_send_json_error(['message' => __('El valor debe ser positivo', 'flavor-chat-ia')]);
        }

        $registro_id = wp_insert_post([
            'post_type' => 'he_registro',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf('%s - %s', self::CATEGORIAS_HUELLA[$categoria]['nombre'], $fecha),
        ]);

        if (is_wp_error($registro_id)) {
            wp_send_json_error(['message' => $registro_id->get_error_message()]);
        }

        update_post_meta($registro_id, '_he_fecha', $fecha);
        update_post_meta($registro_id, '_he_categoria', $categoria);
        update_post_meta($registro_id, '_he_valor', $valor);
        update_post_meta($registro_id, '_he_descripcion', $descripcion);

        // Verificar logros
        $this->verificar_logros($user_id);

        wp_send_json_success([
            'message' => __('Registro guardado correctamente', 'flavor-chat-ia'),
            'registro_id' => $registro_id,
        ]);
    }

    /**
     * AJAX: Registrar acción reductora
     */
    public function ajax_registrar_accion(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $cantidad = intval($_POST['cantidad'] ?? 1);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        if (!isset(self::TIPOS_ACCION[$tipo])) {
            wp_send_json_error(['message' => __('Tipo de acción no válido', 'flavor-chat-ia')]);
        }

        $accion_data = self::TIPOS_ACCION[$tipo];
        $reduccion_total = $accion_data['reduccion_estimada'] * $cantidad;

        $accion_id = wp_insert_post([
            'post_type' => 'he_accion',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => $accion_data['nombre'],
        ]);

        if (is_wp_error($accion_id)) {
            wp_send_json_error(['message' => $accion_id->get_error_message()]);
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
                __('¡Genial! Has compensado %.1f kg de CO2', 'flavor-chat-ia'),
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
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $meta_co2 = floatval($_POST['meta_co2'] ?? 0);
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $tipo_proyecto = sanitize_key($_POST['tipo_proyecto'] ?? 'reforestacion');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-chat-ia')]);
        }

        $proyecto_id = wp_insert_post([
            'post_type' => 'he_proyecto',
            'post_status' => 'private',
            'post_author' => $user_id,
            'post_title' => $titulo,
            'post_content' => $descripcion,
        ]);

        if (is_wp_error($proyecto_id)) {
            wp_send_json_error(['message' => $proyecto_id->get_error_message()]);
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
            'message' => __('Proyecto propuesto correctamente. Será revisado pronto.', 'flavor-chat-ia'),
            'proyecto_id' => $proyecto_id,
        ]);
    }

    /**
     * AJAX: Unirse a proyecto de compensación
     */
    public function ajax_unirse_proyecto(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $proyecto_id = intval($_POST['proyecto_id'] ?? 0);

        $proyecto = get_post($proyecto_id);
        if (!$proyecto || $proyecto->post_type !== 'he_proyecto') {
            wp_send_json_error(['message' => __('Proyecto no encontrado', 'flavor-chat-ia')]);
        }

        $estado = get_post_meta($proyecto_id, '_he_estado', true);
        if (!in_array($estado, ['aprobado', 'en_curso'])) {
            wp_send_json_error(['message' => __('Este proyecto no está abierto a participantes', 'flavor-chat-ia')]);
        }

        $participantes = get_post_meta($proyecto_id, '_he_participantes', true) ?: [];
        if (in_array($user_id, $participantes)) {
            wp_send_json_error(['message' => __('Ya participas en este proyecto', 'flavor-chat-ia')]);
        }

        $participantes[] = $user_id;
        update_post_meta($proyecto_id, '_he_participantes', $participantes);

        // Otorgar logro de compensador
        $this->otorgar_logro($user_id, 'compensador');

        wp_send_json_success([
            'message' => __('Te has unido al proyecto', 'flavor-chat-ia'),
            'participantes' => count($participantes),
        ]);
    }

    /**
     * AJAX: Obtener estadísticas del usuario
     */
    public function ajax_obtener_estadisticas(): void {
        check_ajax_referer('huella_ecologica_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
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
            return '<p class="he-login-required">' . __('Inicia sesión para ver tus registros', 'flavor-chat-ia') . '</p>';
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
            return '<p class="he-login-required">' . __('Inicia sesión para ver tus logros', 'flavor-chat-ia') . '</p>';
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
            'paginas' => [
                [
                    'slug' => 'huella-ecologica',
                    'titulo' => __('Huella Ecológica', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'he-proyectos',
                    'titulo' => __('Proyectos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_proyectos'],
                ],
            ],
            'settings' => [
                'he_meta_reduccion_anual' => [
                    'label' => __('Meta de reducción anual (kg CO2)', 'flavor-chat-ia'),
                    'type' => 'number',
                    'default' => 10000,
                ],
                'he_factores_emision_personalizados' => [
                    'label' => __('Usar factores de emisión personalizados', 'flavor-chat-ia'),
                    'type' => 'checkbox',
                    'default' => false,
                ],
            ],
        ];
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
}
