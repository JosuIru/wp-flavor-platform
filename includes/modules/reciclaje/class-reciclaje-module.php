<?php
/**
 * Módulo de Reciclaje para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Reciclaje - Gestión de reciclaje comunitario
 */
class Flavor_Chat_Reciclaje_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'reciclaje';
        $this->name = __('Reciclaje Comunitario', 'flavor-chat-ia');
        $this->description = __('Sistema de gestión de reciclaje, puntos limpios y economía circular en la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Reciclaje no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'puntos_por_kg' => 10,
            'permite_canje_puntos' => true,
            'notificar_recogidas' => true,
            'permite_reportar_contenedores' => true,
            'categorias_reciclaje' => ['papel', 'plastico', 'vidrio', 'organico', 'electronico', 'ropa', 'aceite', 'pilas'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'register_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_reciclaje_registrar_deposito', [$this, 'ajax_registrar_deposito']);
        add_action('wp_ajax_reciclaje_obtener_puntos', [$this, 'ajax_obtener_puntos']);
        add_action('wp_ajax_reciclaje_reportar_contenedor', [$this, 'ajax_reportar_contenedor']);
        add_action('wp_ajax_reciclaje_calendario', [$this, 'ajax_calendario_recogidas']);
        add_action('wp_ajax_reciclaje_mis_puntos', [$this, 'ajax_mis_puntos']);
        add_action('wp_ajax_reciclaje_canjear_puntos', [$this, 'ajax_canjear_puntos']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WP Cron
        add_action('reciclaje_notificar_recogidas', [$this, 'notificar_recogidas_proximas']);
        add_action('reciclaje_verificar_contenedores', [$this, 'verificar_estado_contenedores']);

        if (!wp_next_scheduled('reciclaje_notificar_recogidas')) {
            wp_schedule_event(time(), 'daily', 'reciclaje_notificar_recogidas');
        }
        if (!wp_next_scheduled('reciclaje_verificar_contenedores')) {
            wp_schedule_event(time(), 'twicedaily', 'reciclaje_verificar_contenedores');
        }

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje)) {
            $this->create_tables();
        }
    }

    /**
     * Registra los post types
     */
    public function register_post_types() {
        // Post type para recompensas canjeables
        register_post_type('recompensa_reciclaje', [
            'labels' => [
                'name' => __('Recompensas', 'flavor-chat-ia'),
                'singular_name' => __('Recompensa', 'flavor-chat-ia'),
                'add_new' => __('Añadir Recompensa', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Recompensa', 'flavor-chat-ia'),
                'edit_item' => __('Editar Recompensa', 'flavor-chat-ia'),
                'new_item' => __('Nueva Recompensa', 'flavor-chat-ia'),
                'view_item' => __('Ver Recompensa', 'flavor-chat-ia'),
                'search_items' => __('Buscar Recompensas', 'flavor-chat-ia'),
                'not_found' => __('No se encontraron recompensas', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'recompensas-reciclaje'],
            'show_in_rest' => true,
        ]);

        // Post type para guías de reciclaje
        register_post_type('guia_reciclaje', [
            'labels' => [
                'name' => __('Guías', 'flavor-chat-ia'),
                'singular_name' => __('Guía', 'flavor-chat-ia'),
                'add_new' => __('Añadir Guía', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite' => ['slug' => 'guias-reciclaje'],
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra las taxonomías
     */
    public function register_taxonomies() {
        register_taxonomy('tipo_material', ['guia_reciclaje'], [
            'labels' => [
                'name' => __('Tipos de Material', 'flavor-chat-ia'),
                'singular_name' => __('Tipo de Material', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'tipo-material'],
        ]);

        register_taxonomy('categoria_recompensa', ['recompensa_reciclaje'], [
            'labels' => [
                'name' => __('Categorías de Recompensa', 'flavor-chat-ia'),
                'singular_name' => __('Categoría de Recompensa', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'categoria-recompensa'],
        ]);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('reciclaje_puntos_cercanos', [$this, 'shortcode_puntos_cercanos']);
        add_shortcode('reciclaje_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('reciclaje_mis_puntos', [$this, 'shortcode_mis_puntos']);
        add_shortcode('reciclaje_ranking', [$this, 'shortcode_ranking']);
        add_shortcode('reciclaje_guia', [$this, 'shortcode_guia']);
        add_shortcode('reciclaje_recompensas', [$this, 'shortcode_recompensas']);
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        $sql_puntos = "CREATE TABLE IF NOT EXISTS $tabla_puntos_reciclaje (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            tipo enum('punto_limpio','contenedor_comunitario','centro_acopio','movil') DEFAULT 'contenedor_comunitario',
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            materiales_aceptados text NOT NULL COMMENT 'JSON array',
            horario text DEFAULT NULL,
            contacto varchar(255) DEFAULT NULL,
            instrucciones text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('activo','lleno','mantenimiento','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ubicacion (latitud, longitud),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_depositos = "CREATE TABLE IF NOT EXISTS $tabla_depositos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_material varchar(50) NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            puntos_ganados int(11) DEFAULT 0,
            foto_url varchar(500) DEFAULT NULL,
            verificado tinyint(1) DEFAULT 0,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            fecha_deposito datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_material (tipo_material),
            KEY fecha_deposito (fecha_deposito)
        ) $charset_collate;";

        $sql_recogidas = "CREATE TABLE IF NOT EXISTS $tabla_recogidas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo_recogida enum('programada','a_demanda','urgente') DEFAULT 'programada',
            zona varchar(255) NOT NULL,
            tipos_residuos text NOT NULL COMMENT 'JSON',
            fecha_programada datetime NOT NULL,
            hora_inicio time DEFAULT NULL,
            hora_fin time DEFAULT NULL,
            ruta text DEFAULT NULL COMMENT 'JSON de coordenadas',
            notas text DEFAULT NULL,
            estado enum('programada','en_curso','completada','cancelada') DEFAULT 'programada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fecha_programada (fecha_programada),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_contenedores = "CREATE TABLE IF NOT EXISTS $tabla_contenedores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            nivel_llenado int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
            necesita_vaciado tinyint(1) DEFAULT 0,
            ultima_recogida datetime DEFAULT NULL,
            reportes_problema int(11) DEFAULT 0,
            estado enum('operativo','lleno','danado','fuera_servicio') DEFAULT 'operativo',
            fecha_instalacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_residuo (tipo_residuo),
            KEY necesita_vaciado (necesita_vaciado)
        ) $charset_collate;";

        $tabla_canjes = $wpdb->prefix . 'flavor_reciclaje_canjes';
        $sql_canjes = "CREATE TABLE IF NOT EXISTS $tabla_canjes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            recompensa_id bigint(20) unsigned NOT NULL,
            puntos_canjeados int(11) NOT NULL,
            fecha_canje datetime DEFAULT CURRENT_TIMESTAMP,
            estado enum('pendiente','aprobado','entregado','cancelado') DEFAULT 'pendiente',
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY recompensa_id (recompensa_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_puntos);
        dbDelta($sql_depositos);
        dbDelta($sql_recogidas);
        dbDelta($sql_contenedores);
        dbDelta($sql_canjes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'puntos_cercanos' => [
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'params' => ['lat', 'lng', 'tipo_material'],
            ],
            'calendario_recogidas' => [
                'description' => 'Ver calendario de recogidas',
                'params' => ['zona', 'tipo_residuo'],
            ],
            'registrar_deposito' => [
                'description' => 'Registrar depósito de material',
                'params' => ['punto_id', 'tipo_material', 'cantidad_kg'],
            ],
            'mis_puntos_reciclaje' => [
                'description' => 'Ver mis puntos acumulados',
                'params' => [],
            ],
            'canje_puntos' => [
                'description' => 'Canjear puntos por recompensas',
                'params' => ['recompensa_id'],
            ],
            'reportar_contenedor' => [
                'description' => 'Reportar problema con contenedor',
                'params' => ['contenedor_id', 'problema'],
            ],
            'guia_reciclaje' => [
                'description' => 'Guía de qué reciclar y cómo',
                'params' => ['tipo_material'],
            ],
            // Admin actions
            'estadisticas_reciclaje' => [
                'description' => 'Estadísticas de reciclaje (admin)',
                'params' => ['periodo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Puntos cercanos
     */
    private function action_puntos_cercanos($params) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $tipo_material = sanitize_text_field($params['tipo_material'] ?? '');

        $where = "estado = 'activo'";
        if (!empty($tipo_material)) {
            $where .= $wpdb->prepare(" AND materiales_aceptados LIKE %s", '%' . $wpdb->esc_like($tipo_material) . '%');
        }

        if ($lat != 0 && $lng != 0) {
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_puntos
                    WHERE $where
                    ORDER BY distancia ASC
                    LIMIT 20";

            $puntos = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat));
        } else {
            $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos WHERE $where ORDER BY nombre LIMIT 20");
        }

        return [
            'success' => true,
            'puntos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'tipo' => $p->tipo,
                    'direccion' => $p->direccion,
                    'lat' => floatval($p->latitud),
                    'lng' => floatval($p->longitud),
                    'materiales' => json_decode($p->materiales_aceptados, true),
                    'horario' => $p->horario,
                    'distancia_km' => isset($p->distancia) ? round($p->distancia, 2) : null,
                ];
            }, $puntos),
        ];
    }

    /**
     * Acción: Calendario de recogidas
     */
    private function action_calendario_recogidas($params) {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        $zona = sanitize_text_field($params['zona'] ?? '');
        $tipo_residuo = sanitize_text_field($params['tipo_residuo'] ?? '');

        $where = "estado != 'cancelada'";
        if (!empty($zona)) {
            $where .= $wpdb->prepare(" AND zona = %s", $zona);
        }
        if (!empty($tipo_residuo)) {
            $where .= $wpdb->prepare(" AND tipos_residuos LIKE %s", '%' . $wpdb->esc_like($tipo_residuo) . '%');
        }

        $recogidas = $wpdb->get_results(
            "SELECT * FROM $tabla_recogidas
            WHERE $where
            AND fecha_programada >= NOW()
            ORDER BY fecha_programada ASC
            LIMIT 30"
        );

        return [
            'success' => true,
            'recogidas' => array_map(function($r) {
                return [
                    'id' => $r->id,
                    'tipo' => $r->tipo_recogida,
                    'zona' => $r->zona,
                    'tipos_residuos' => json_decode($r->tipos_residuos, true),
                    'fecha' => $r->fecha_programada,
                    'hora_inicio' => $r->hora_inicio,
                    'hora_fin' => $r->hora_fin,
                    'estado' => $r->estado,
                ];
            }, $recogidas),
        ];
    }

    /**
     * Acción: Registrar depósito
     */
    private function action_registrar_deposito($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Usuario no autenticado'];
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $settings = $this->get_settings();

        $punto_id = intval($params['punto_id'] ?? 0);
        $tipo_material = sanitize_text_field($params['tipo_material'] ?? '');
        $cantidad_kg = floatval($params['cantidad_kg'] ?? 0);

        if ($punto_id <= 0 || $cantidad_kg <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos'];
        }

        // Calcular puntos ganados
        $puntos_por_kg = intval($settings['puntos_por_kg'] ?? 10);
        $puntos_ganados = round($cantidad_kg * $puntos_por_kg);

        $resultado = $wpdb->insert(
            $tabla_depositos,
            [
                'usuario_id' => get_current_user_id(),
                'punto_reciclaje_id' => $punto_id,
                'tipo_material' => $tipo_material,
                'cantidad_kg' => $cantidad_kg,
                'puntos_ganados' => $puntos_ganados,
                'verificado' => 0,
                'fecha_deposito' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%d', '%d', '%s']
        );

        if ($resultado) {
            // Integración con gamificación
            do_action('flavor_gamification_award_points', get_current_user_id(), $puntos_ganados, 'reciclaje', 'Depósito de reciclaje');

            // Notificación
            do_action('flavor_notification_send', get_current_user_id(), 'reciclaje_deposito', [
                'title' => '¡Depósito registrado!',
                'message' => sprintf('Has ganado %d puntos por reciclar %.2f kg de %s', $puntos_ganados, $cantidad_kg, $tipo_material),
                'icon' => 'dashicons-awards',
            ]);

            return [
                'success' => true,
                'deposito_id' => $wpdb->insert_id,
                'puntos_ganados' => $puntos_ganados,
            ];
        }

        return ['success' => false, 'error' => 'Error al registrar depósito'];
    }

    /**
     * Acción: Mis puntos de reciclaje
     */
    private function action_mis_puntos_reciclaje($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Usuario no autenticado'];
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        $total_puntos = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(puntos_ganados) FROM $tabla_depositos WHERE usuario_id = %d",
            $usuario_id
        ));

        $total_kg_reciclados = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cantidad_kg) FROM $tabla_depositos WHERE usuario_id = %d",
            $usuario_id
        ));

        $depositos_por_material = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_material, SUM(cantidad_kg) as total_kg, SUM(puntos_ganados) as total_puntos
            FROM $tabla_depositos
            WHERE usuario_id = %d
            GROUP BY tipo_material",
            $usuario_id
        ));

        return [
            'success' => true,
            'total_puntos' => intval($total_puntos),
            'total_kg_reciclados' => floatval($total_kg_reciclados),
            'por_material' => array_map(function($m) {
                return [
                    'material' => $m->tipo_material,
                    'kg' => floatval($m->total_kg),
                    'puntos' => intval($m->total_puntos),
                ];
            }, $depositos_por_material),
        ];
    }

    /**
     * Acción: Canje de puntos
     */
    private function action_canje_puntos($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Usuario no autenticado'];
        }

        $recompensa_id = intval($params['recompensa_id'] ?? 0);
        if ($recompensa_id <= 0) {
            return ['success' => false, 'error' => 'Recompensa inválida'];
        }

        $puntos_necesarios = intval(get_post_meta($recompensa_id, '_puntos_necesarios', true));
        $resultado_mis_puntos = $this->action_mis_puntos_reciclaje([]);

        if (!$resultado_mis_puntos['success']) {
            return ['success' => false, 'error' => 'Error al obtener puntos'];
        }

        $puntos_actuales = $resultado_mis_puntos['total_puntos'];

        if ($puntos_actuales < $puntos_necesarios) {
            return [
                'success' => false,
                'error' => 'Puntos insuficientes',
                'puntos_actuales' => $puntos_actuales,
                'puntos_necesarios' => $puntos_necesarios,
            ];
        }

        // Registrar el canje
        global $wpdb;
        $tabla_canjes = $wpdb->prefix . 'flavor_reciclaje_canjes';

        $wpdb->insert(
            $tabla_canjes,
            [
                'usuario_id' => get_current_user_id(),
                'recompensa_id' => $recompensa_id,
                'puntos_canjeados' => $puntos_necesarios,
                'fecha_canje' => current_time('mysql'),
                'estado' => 'pendiente',
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );

        // Notificación
        do_action('flavor_notification_send', get_current_user_id(), 'reciclaje_canje', [
            'title' => '¡Recompensa canjeada!',
            'message' => sprintf('Has canjeado %d puntos por: %s', $puntos_necesarios, get_the_title($recompensa_id)),
            'icon' => 'dashicons-awards',
        ]);

        return [
            'success' => true,
            'canje_id' => $wpdb->insert_id,
            'puntos_restantes' => $puntos_actuales - $puntos_necesarios,
        ];
    }

    /**
     * Acción: Reportar contenedor
     */
    private function action_reportar_contenedor($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Usuario no autenticado'];
        }

        global $wpdb;
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        $contenedor_id = intval($params['contenedor_id'] ?? 0);
        $problema = sanitize_text_field($params['problema'] ?? '');

        if ($contenedor_id <= 0) {
            return ['success' => false, 'error' => 'Contenedor inválido'];
        }

        // Incrementar contador de reportes
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_contenedores SET reportes_problema = reportes_problema + 1 WHERE id = %d",
            $contenedor_id
        ));

        // Si hay muchos reportes, marcar como necesita atención
        $reportes = $wpdb->get_var($wpdb->prepare(
            "SELECT reportes_problema FROM $tabla_contenedores WHERE id = %d",
            $contenedor_id
        ));

        if ($reportes >= 3) {
            $wpdb->update(
                $tabla_contenedores,
                ['estado' => 'danado'],
                ['id' => $contenedor_id],
                ['%s'],
                ['%d']
            );

            // Notificar al admin
            do_action('flavor_notification_send_admin', 'reciclaje_contenedor_danado', [
                'title' => 'Contenedor requiere atención',
                'message' => sprintf('El contenedor #%d ha recibido %d reportes de problema', $contenedor_id, $reportes),
            ]);
        }

        return [
            'success' => true,
            'reportes_totales' => intval($reportes),
        ];
    }

    /**
     * Acción: Guía de reciclaje
     */
    private function action_guia_reciclaje($params) {
        $tipo_material = sanitize_text_field($params['tipo_material'] ?? '');

        $guias = get_posts([
            'post_type' => 'guia_reciclaje',
            'posts_per_page' => -1,
            'tax_query' => !empty($tipo_material) ? [[
                'taxonomy' => 'tipo_material',
                'field' => 'slug',
                'terms' => $tipo_material,
            ]] : [],
        ]);

        return [
            'success' => true,
            'guias' => array_map(function($g) {
                return [
                    'id' => $g->ID,
                    'titulo' => $g->post_title,
                    'contenido' => $g->post_content,
                    'resumen' => $g->post_excerpt,
                    'imagen' => get_the_post_thumbnail_url($g->ID, 'medium'),
                ];
            }, $guias),
        ];
    }

    /**
     * Acción: Estadísticas de reciclaje (admin)
     */
    private function action_estadisticas_reciclaje($params) {
        if (!current_user_can('manage_options')) {
            return ['success' => false, 'error' => 'Permisos insuficientes'];
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $periodo = sanitize_text_field($params['periodo'] ?? 'mes');

        $fecha_desde = match($periodo) {
            'semana' => date('Y-m-d', strtotime('-7 days')),
            'mes' => date('Y-m-d', strtotime('-30 days')),
            'trimestre' => date('Y-m-d', strtotime('-90 days')),
            'año' => date('Y-m-d', strtotime('-365 days')),
            default => date('Y-m-d', strtotime('-30 days')),
        };

        $stats = [
            'total_kg' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(cantidad_kg) FROM $tabla_depositos WHERE fecha_deposito >= %s",
                $fecha_desde
            )),
            'total_depositos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_depositos WHERE fecha_deposito >= %s",
                $fecha_desde
            )),
            'usuarios_activos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_depositos WHERE fecha_deposito >= %s",
                $fecha_desde
            )),
            'por_material' => $wpdb->get_results($wpdb->prepare(
                "SELECT tipo_material, SUM(cantidad_kg) as total_kg, COUNT(*) as num_depositos
                FROM $tabla_depositos
                WHERE fecha_deposito >= %s
                GROUP BY tipo_material
                ORDER BY total_kg DESC",
                $fecha_desde
            )),
        ];

        return [
            'success' => true,
            'periodo' => $periodo,
            'estadisticas' => $stats,
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Reconocimiento de materiales por foto (clasificación automática)
     * - Rutas optimizadas a puntos de reciclaje cercanos
     * - Sugerencias de reciclaje según historial del usuario
     * - Chatbot para dudas de reciclaje
     */
    public function get_web_components() {
        return [
            'hero_reciclaje' => [
                'label' => __('Hero Reciclaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Reciclaje Comunitario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Recicla, gana puntos y cuida el planeta', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_puntos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/hero',
            ],
            'puntos_reciclaje' => [
                'label' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Punto de Reciclaje', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_materiales' => ['type' => 'toggle', 'default' => true],
                    'filtrar_por_tipo' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/puntos',
            ],
            'calendario_recogidas' => [
                'label' => __('Calendario de Recogidas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Calendario de Recogidas', 'flavor-chat-ia')],
                    'vista' => ['type' => 'select', 'options' => ['mensual', 'semanal'], 'default' => 'mensual'],
                    'mostrar_zona' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/calendario',
            ],
            'guia_reciclaje' => [
                'label' => __('Guía de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué va en cada Contenedor', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['tarjetas', 'acordeon'], 'default' => 'tarjetas'],
                    'mostrar_colores' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/guia',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'reciclaje_puntos_cercanos',
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'tipo_material' => ['type' => 'string', 'description' => 'Tipo de material a reciclar'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Reciclaje Comunitario**

Sistema integral de gestión de reciclaje con recompensas por participar.

**Tipos de reciclaje:**
- Papel y cartón
- Plástico y envases
- Vidrio
- Orgánico
- Electrónico (RAEE)
- Ropa y textil
- Aceite usado
- Pilas y baterías

**Puntos de reciclaje:**
- Puntos limpios municipales
- Contenedores comunitarios
- Centros de acopio especializados
- Recogida móvil

**Sistema de puntos:**
- Gana puntos por reciclar
- Canjea por descuentos locales
- Premios comunitarios
- Rankings de reciclaje

**Calendario de recogidas:**
- Recogidas programadas por zona
- Alertas personalizadas
- Recogida de voluminosos
- Residuos especiales

**Guías de reciclaje:**
- Qué va en cada contenedor
- Cómo preparar los residuos
- Qué NO reciclar
- Alternativas de reutilización
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Dónde reciclo aparatos electrónicos?',
                'respuesta' => 'En los puntos limpios municipales o en recogidas especiales de RAEE.',
            ],
            [
                'pregunta' => '¿Cómo funcionan los puntos?',
                'respuesta' => 'Ganas puntos por cada kg de material reciclado. Pueden canjearse por descuentos en comercios locales.',
            ],
            [
                'pregunta' => '¿Qué hago con el aceite usado?',
                'respuesta' => 'Nunca por el fregadero. Guárdalo en botellas y llévalo a puntos de recogida de aceite.',
            ],
        ];
    }

    // ========================================
    // MÉTODOS AJAX
    // ========================================

    /**
     * AJAX: Registrar depósito
     */
    public function ajax_registrar_deposito() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_registrar_deposito($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener puntos cercanos
     */
    public function ajax_obtener_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_puntos_cercanos($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Reportar contenedor
     */
    public function ajax_reportar_contenedor() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_reportar_contenedor($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Calendario de recogidas
     */
    public function ajax_calendario_recogidas() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_calendario_recogidas($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Mis puntos
     */
    public function ajax_mis_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_mis_puntos_reciclaje($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Canjear puntos
     */
    public function ajax_canjear_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $resultado = $this->action_canje_puntos($_POST);
        wp_send_json($resultado);
    }

    // ========================================
    // REST API
    // ========================================

    /**
     * Registra las rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/reciclaje/puntos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_puntos'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/depositos', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_registrar_deposito'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/mis-puntos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_puntos'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/calendario', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_calendario'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/recompensas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_recompensas'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/canjear', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_canjear'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/ranking', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_ranking'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/reciclaje/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST: Obtener puntos de reciclaje
     */
    public function rest_obtener_puntos($request) {
        $params = [
            'lat' => $request->get_param('lat'),
            'lng' => $request->get_param('lng'),
            'tipo_material' => $request->get_param('tipo_material'),
        ];

        return $this->action_puntos_cercanos($params);
    }

    /**
     * REST: Registrar depósito
     */
    public function rest_registrar_deposito($request) {
        $params = [
            'punto_id' => $request->get_param('punto_id'),
            'tipo_material' => $request->get_param('tipo_material'),
            'cantidad_kg' => $request->get_param('cantidad_kg'),
        ];

        return $this->action_registrar_deposito($params);
    }

    /**
     * REST: Mis puntos
     */
    public function rest_mis_puntos($request) {
        return $this->action_mis_puntos_reciclaje([]);
    }

    /**
     * REST: Calendario
     */
    public function rest_calendario($request) {
        $params = [
            'zona' => $request->get_param('zona'),
            'tipo_residuo' => $request->get_param('tipo_residuo'),
        ];

        return $this->action_calendario_recogidas($params);
    }

    /**
     * REST: Recompensas disponibles
     */
    public function rest_recompensas($request) {
        $recompensas = get_posts([
            'post_type' => 'recompensa_reciclaje',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        return [
            'success' => true,
            'recompensas' => array_map(function($r) {
                return [
                    'id' => $r->ID,
                    'titulo' => $r->post_title,
                    'descripcion' => $r->post_content,
                    'puntos_necesarios' => intval(get_post_meta($r->ID, '_puntos_necesarios', true)),
                    'imagen' => get_the_post_thumbnail_url($r->ID, 'medium'),
                    'stock' => intval(get_post_meta($r->ID, '_stock', true)),
                ];
            }, $recompensas),
        ];
    }

    /**
     * REST: Canjear puntos
     */
    public function rest_canjear($request) {
        $params = [
            'recompensa_id' => $request->get_param('recompensa_id'),
        ];

        return $this->action_canje_puntos($params);
    }

    /**
     * REST: Ranking de usuarios
     */
    public function rest_ranking($request) {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $limite = intval($request->get_param('limite') ?? 10);

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id,
                    SUM(cantidad_kg) as total_kg,
                    SUM(puntos_ganados) as total_puntos
            FROM $tabla_depositos
            GROUP BY usuario_id
            ORDER BY total_puntos DESC
            LIMIT %d",
            $limite
        ));

        return [
            'success' => true,
            'ranking' => array_map(function($u) {
                $user_data = get_userdata($u->usuario_id);
                return [
                    'usuario_id' => $u->usuario_id,
                    'nombre' => $user_data ? $user_data->display_name : 'Usuario',
                    'total_kg' => floatval($u->total_kg),
                    'total_puntos' => intval($u->total_puntos),
                ];
            }, $ranking),
        ];
    }

    /**
     * REST: Estadísticas
     */
    public function rest_estadisticas($request) {
        $params = [
            'periodo' => $request->get_param('periodo') ?? 'mes',
        ];

        return $this->action_estadisticas_reciclaje($params);
    }

    // ========================================
    // SHORTCODES
    // ========================================

    /**
     * Shortcode: Puntos cercanos
     */
    public function shortcode_puntos_cercanos($atts) {
        $atts = shortcode_atts([
            'altura' => '500px',
            'tipo_material' => '',
        ], $atts);

        ob_start();
        ?>
        <div class="reciclaje-puntos-map" data-tipo="<?php echo esc_attr($atts['tipo_material']); ?>" style="height: <?php echo esc_attr($atts['altura']); ?>">
            <div id="mapa-reciclaje"></div>
            <div class="puntos-lista"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario
     */
    public function shortcode_calendario($atts) {
        $atts = shortcode_atts([
            'zona' => '',
            'vista' => 'mensual',
        ], $atts);

        ob_start();
        ?>
        <div class="reciclaje-calendario" data-zona="<?php echo esc_attr($atts['zona']); ?>" data-vista="<?php echo esc_attr($atts['vista']); ?>">
            <div class="calendario-header">
                <h3><?php _e('Calendario de Recogidas', 'flavor-chat-ia'); ?></h3>
                <select id="zona-selector">
                    <option value=""><?php _e('Todas las zonas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div id="calendario-recogidas"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis puntos
     */
    public function shortcode_mis_puntos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus puntos.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->action_mis_puntos_reciclaje([]);

        if (!$resultado['success']) {
            return '<p>' . __('Error al cargar puntos.', 'flavor-chat-ia') . '</p>';
        }

        $datos = $resultado;

        ob_start();
        ?>
        <div class="reciclaje-mis-puntos">
            <div class="puntos-resumen">
                <div class="stat-card">
                    <h4><?php _e('Total Puntos', 'flavor-chat-ia'); ?></h4>
                    <p class="stat-valor"><?php echo number_format($datos['total_puntos']); ?></p>
                </div>
                <div class="stat-card">
                    <h4><?php _e('Total Reciclado', 'flavor-chat-ia'); ?></h4>
                    <p class="stat-valor"><?php echo number_format($datos['total_kg_reciclados'], 2); ?> kg</p>
                </div>
            </div>
            <div class="puntos-desglose">
                <h4><?php _e('Por Material', 'flavor-chat-ia'); ?></h4>
                <?php foreach ($datos['por_material'] as $material): ?>
                    <div class="material-item">
                        <span class="material-nombre"><?php echo esc_html($material['material']); ?></span>
                        <span class="material-kg"><?php echo number_format($material['kg'], 2); ?> kg</span>
                        <span class="material-puntos"><?php echo number_format($material['puntos']); ?> pts</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking
     */
    public function shortcode_ranking($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id,
                    SUM(cantidad_kg) as total_kg,
                    SUM(puntos_ganados) as total_puntos
            FROM $tabla_depositos
            GROUP BY usuario_id
            ORDER BY total_puntos DESC
            LIMIT %d",
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="reciclaje-ranking">
            <h3><?php _e('Ranking de Recicladores', 'flavor-chat-ia'); ?></h3>
            <table class="ranking-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('KG Reciclados', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Puntos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $pos => $usuario):
                        $user_data = get_userdata($usuario->usuario_id);
                        $nombre = $user_data ? $user_data->display_name : 'Usuario';
                    ?>
                        <tr>
                            <td><?php echo $pos + 1; ?></td>
                            <td><?php echo esc_html($nombre); ?></td>
                            <td><?php echo number_format($usuario->total_kg, 2); ?></td>
                            <td><?php echo number_format($usuario->total_puntos); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Guía de reciclaje
     */
    public function shortcode_guia($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
        ], $atts);

        $resultado = $this->action_guia_reciclaje(['tipo_material' => $atts['tipo']]);

        if (!$resultado['success']) {
            return '<p>' . __('Error al cargar guías.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="reciclaje-guia">
            <?php foreach ($resultado['guias'] as $guia): ?>
                <div class="guia-item">
                    <?php if ($guia['imagen']): ?>
                        <img src="<?php echo esc_url($guia['imagen']); ?>" alt="<?php echo esc_attr($guia['titulo']); ?>">
                    <?php endif; ?>
                    <h4><?php echo esc_html($guia['titulo']); ?></h4>
                    <?php if ($guia['resumen']): ?>
                        <p class="guia-resumen"><?php echo esc_html($guia['resumen']); ?></p>
                    <?php endif; ?>
                    <div class="guia-contenido"><?php echo wp_kses_post($guia['contenido']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Recompensas
     */
    public function shortcode_recompensas($atts) {
        $recompensas = get_posts([
            'post_type' => 'recompensa_reciclaje',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        ob_start();
        ?>
        <div class="reciclaje-recompensas">
            <?php foreach ($recompensas as $recompensa):
                $puntos = intval(get_post_meta($recompensa->ID, '_puntos_necesarios', true));
                $stock = intval(get_post_meta($recompensa->ID, '_stock', true));
            ?>
                <div class="recompensa-item">
                    <?php if (has_post_thumbnail($recompensa->ID)): ?>
                        <?php echo get_the_post_thumbnail($recompensa->ID, 'medium'); ?>
                    <?php endif; ?>
                    <h4><?php echo esc_html($recompensa->post_title); ?></h4>
                    <p><?php echo esc_html($recompensa->post_excerpt); ?></p>
                    <div class="recompensa-info">
                        <span class="puntos-necesarios"><?php echo number_format($puntos); ?> puntos</span>
                        <?php if ($stock > 0): ?>
                            <span class="stock"><?php printf(__('Stock: %d', 'flavor-chat-ia'), $stock); ?></span>
                        <?php else: ?>
                            <span class="sin-stock"><?php _e('Sin stock', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (is_user_logged_in() && $stock > 0): ?>
                        <button class="btn-canjear" data-recompensa-id="<?php echo $recompensa->ID; ?>">
                            <?php _e('Canjear', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ========================================
    // WP CRON
    // ========================================

    /**
     * Notifica recogidas próximas
     */
    public function notificar_recogidas_proximas() {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        // Buscar recogidas en las próximas 24 horas
        $recogidas = $wpdb->get_results(
            "SELECT * FROM $tabla_recogidas
            WHERE estado = 'programada'
            AND fecha_programada BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)"
        );

        foreach ($recogidas as $recogida) {
            // Notificar a usuarios de la zona
            $usuarios = get_users(['meta_key' => 'zona_recogida', 'meta_value' => $recogida->zona]);

            foreach ($usuarios as $usuario) {
                do_action('flavor_notification_send', $usuario->ID, 'reciclaje_recogida', [
                    'title' => __('Recogida programada mañana', 'flavor-chat-ia'),
                    'message' => sprintf(
                        __('Mañana hay recogida de %s en tu zona (%s)', 'flavor-chat-ia'),
                        implode(', ', json_decode($recogida->tipos_residuos, true)),
                        $recogida->zona
                    ),
                    'icon' => 'dashicons-calendar-alt',
                    'color' => '#10b981',
                ]);
            }
        }
    }

    /**
     * Verifica estado de contenedores
     */
    public function verificar_estado_contenedores() {
        global $wpdb;
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        // Buscar contenedores que necesitan vaciado
        $contenedores_llenos = $wpdb->get_results(
            "SELECT * FROM $tabla_contenedores
            WHERE nivel_llenado >= 80
            AND necesita_vaciado = 0"
        );

        foreach ($contenedores_llenos as $contenedor) {
            // Marcar como necesita vaciado
            $wpdb->update(
                $tabla_contenedores,
                ['necesita_vaciado' => 1],
                ['id' => $contenedor->id],
                ['%d'],
                ['%d']
            );

            // Notificar al admin
            do_action('flavor_notification_send_admin', 'reciclaje_contenedor_lleno', [
                'title' => __('Contenedor lleno', 'flavor-chat-ia'),
                'message' => sprintf(
                    __('El contenedor #%d en el punto %d está al %d%% de capacidad', 'flavor-chat-ia'),
                    $contenedor->id,
                    $contenedor->punto_reciclaje_id,
                    $contenedor->nivel_llenado
                ),
            ]);
        }
    }

    // ========================================
    // ASSETS
    // ========================================

    /**
     * Encola assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_active()) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_enqueue_style(
            'flavor-reciclaje-frontend',
            $base_url . 'reciclaje-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-reciclaje-frontend',
            $base_url . 'reciclaje-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-reciclaje-frontend', 'reciclajeData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reciclaje_nonce'),
            'restUrl' => rest_url('flavor/v1/reciclaje'),
            'i18n' => [
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'exito' => __('Operación exitosa', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola assets admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'reciclaje') === false) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_enqueue_style(
            'flavor-reciclaje-admin',
            $base_url . 'reciclaje-admin.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-reciclaje-admin',
            $base_url . 'reciclaje-admin.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-reciclaje-admin', 'reciclajeAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reciclaje_admin_nonce'),
            'restUrl' => rest_url('flavor/v1/reciclaje'),
        ]);
    }

    /**
     * Registra menú admin
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Reciclaje', 'flavor-chat-ia'),
            __('Reciclaje', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje',
            [$this, 'render_admin_dashboard'],
            'dashicons-admin-site',
            30
        );

        add_submenu_page(
            'flavor-reciclaje',
            __('Puntos de Reciclaje', 'flavor-chat-ia'),
            __('Puntos', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje-puntos',
            [$this, 'render_admin_puntos']
        );

        add_submenu_page(
            'flavor-reciclaje',
            __('Depósitos', 'flavor-chat-ia'),
            __('Depósitos', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje-depositos',
            [$this, 'render_admin_depositos']
        );

        add_submenu_page(
            'flavor-reciclaje',
            __('Recogidas', 'flavor-chat-ia'),
            __('Recogidas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje-recogidas',
            [$this, 'render_admin_recogidas']
        );

        add_submenu_page(
            'flavor-reciclaje',
            __('Estadísticas', 'flavor-chat-ia'),
            __('Estadísticas', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje-stats',
            [$this, 'render_admin_stats']
        );
    }

    /**
     * Renderiza dashboard admin
     */
    public function render_admin_dashboard() {
        $stats = $this->action_estadisticas_reciclaje(['periodo' => 'mes']);
        ?>
        <div class="wrap">
            <h1><?php _e('Panel de Reciclaje', 'flavor-chat-ia'); ?></h1>

            <?php if ($stats['success']): ?>
                <div class="reciclaje-stats-grid">
                    <div class="stat-box">
                        <h3><?php _e('Total Reciclado', 'flavor-chat-ia'); ?></h3>
                        <p class="stat-number"><?php echo number_format($stats['estadisticas']['total_kg'], 2); ?> kg</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php _e('Depósitos', 'flavor-chat-ia'); ?></h3>
                        <p class="stat-number"><?php echo number_format($stats['estadisticas']['total_depositos']); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></h3>
                        <p class="stat-number"><?php echo number_format($stats['estadisticas']['usuarios_activos']); ?></p>
                    </div>
                </div>

                <h2><?php _e('Por Material', 'flavor-chat-ia'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Material', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('KG Reciclados', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Nº Depósitos', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['estadisticas']['por_material'] as $material): ?>
                            <tr>
                                <td><?php echo esc_html($material->tipo_material); ?></td>
                                <td><?php echo number_format($material->total_kg, 2); ?></td>
                                <td><?php echo number_format($material->num_depositos); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza admin puntos
     */
    public function render_admin_puntos() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos ORDER BY nombre");
        ?>
        <div class="wrap">
            <h1><?php _e('Puntos de Reciclaje', 'flavor-chat-ia'); ?></h1>
            <a href="#" class="button button-primary"><?php _e('Añadir Punto', 'flavor-chat-ia'); ?></a>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Dirección', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($puntos as $punto): ?>
                        <tr>
                            <td><?php echo $punto->id; ?></td>
                            <td><?php echo esc_html($punto->nombre); ?></td>
                            <td><?php echo esc_html($punto->tipo); ?></td>
                            <td><?php echo esc_html($punto->direccion); ?></td>
                            <td><?php echo esc_html($punto->estado); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza admin depósitos
     */
    public function render_admin_depositos() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $depositos = $wpdb->get_results("SELECT * FROM $tabla_depositos ORDER BY fecha_deposito DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1><?php _e('Depósitos Recientes', 'flavor-chat-ia'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Material', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Cantidad (kg)', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Puntos', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Verificado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($depositos as $deposito):
                        $user = get_userdata($deposito->usuario_id);
                    ?>
                        <tr>
                            <td><?php echo $deposito->id; ?></td>
                            <td><?php echo $user ? esc_html($user->display_name) : 'Usuario #' . $deposito->usuario_id; ?></td>
                            <td><?php echo esc_html($deposito->tipo_material); ?></td>
                            <td><?php echo number_format($deposito->cantidad_kg, 2); ?></td>
                            <td><?php echo number_format($deposito->puntos_ganados); ?></td>
                            <td><?php echo esc_html($deposito->fecha_deposito); ?></td>
                            <td><?php echo $deposito->verificado ? '✓' : '✗'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza admin recogidas
     */
    public function render_admin_recogidas() {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';
        $recogidas = $wpdb->get_results("SELECT * FROM $tabla_recogidas ORDER BY fecha_programada DESC");
        ?>
        <div class="wrap">
            <h1><?php _e('Recogidas Programadas', 'flavor-chat-ia'); ?></h1>
            <a href="#" class="button button-primary"><?php _e('Nueva Recogida', 'flavor-chat-ia'); ?></a>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Zona', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recogidas as $recogida): ?>
                        <tr>
                            <td><?php echo $recogida->id; ?></td>
                            <td><?php echo esc_html($recogida->tipo_recogida); ?></td>
                            <td><?php echo esc_html($recogida->zona); ?></td>
                            <td><?php echo esc_html($recogida->fecha_programada); ?></td>
                            <td><?php echo esc_html($recogida->estado); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza admin estadísticas
     */
    public function render_admin_stats() {
        $stats_mes = $this->action_estadisticas_reciclaje(['periodo' => 'mes']);
        $stats_año = $this->action_estadisticas_reciclaje(['periodo' => 'año']);
        ?>
        <div class="wrap">
            <h1><?php _e('Estadísticas de Reciclaje', 'flavor-chat-ia'); ?></h1>

            <h2><?php _e('Último Mes', 'flavor-chat-ia'); ?></h2>
            <?php if ($stats_mes['success']): ?>
                <p><?php printf(__('Total reciclado: %s kg en %s depósitos', 'flavor-chat-ia'),
                    number_format($stats_mes['estadisticas']['total_kg'], 2),
                    number_format($stats_mes['estadisticas']['total_depositos'])
                ); ?></p>
            <?php endif; ?>

            <h2><?php _e('Último Año', 'flavor-chat-ia'); ?></h2>
            <?php if ($stats_año['success']): ?>
                <p><?php printf(__('Total reciclado: %s kg en %s depósitos', 'flavor-chat-ia'),
                    number_format($stats_año['estadisticas']['total_kg'], 2),
                    number_format($stats_año['estadisticas']['total_depositos'])
                ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
