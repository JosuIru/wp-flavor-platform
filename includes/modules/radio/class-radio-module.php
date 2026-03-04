<?php
/**
 * Módulo de Radio Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Radio - Emisora de radio comunitaria en streaming
 */
class Flavor_Chat_Radio_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'radio';
        $this->name = 'Radio Comunitaria'; // Translation loaded on init
        $this->description = 'Emisora de radio comunitaria en streaming con programación y participación ciudadana.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        return Flavor_Chat_Helpers::tabla_existe($tabla_programas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Radio no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'url_stream' => '',
            'url_stream_hd' => '',
            'frecuencia_fm' => '',
            'nombre_radio' => __('Radio Comunitaria', 'flavor-chat-ia'),
            'slogan' => __('La voz de tu barrio', 'flavor-chat-ia'),
            'permite_locutores_comunidad' => true,
            'duracion_maxima_programa' => 120,
            'duracion_minima_programa' => 30,
            'requiere_aprobacion_programas' => true,
            'permite_dedicatorias' => true,
            'max_dedicatorias_dia' => 3,
            'chat_en_vivo' => true,
            'grabacion_automatica' => true,
            'url_grabaciones' => '',
            'permite_podcasts' => true,
            'oyentes_contador_publico' => true,
            'color_marca' => '#8b5cf6',
            'logo_url' => '',
            'puntos_escuchar_programa' => 2,
            'puntos_enviar_dedicatoria' => 5,
            'puntos_proponer_programa' => 20,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en panel de administración unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        add_action('init', [$this, 'maybe_create_tables']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_radio_stream', [$this, 'ajax_get_stream']);
        add_action('wp_ajax_nopriv_flavor_radio_stream', [$this, 'ajax_get_stream']);
        add_action('wp_ajax_flavor_radio_programa_actual', [$this, 'ajax_programa_actual']);
        add_action('wp_ajax_nopriv_flavor_radio_programa_actual', [$this, 'ajax_programa_actual']);
        add_action('wp_ajax_flavor_radio_programacion', [$this, 'ajax_programacion']);
        add_action('wp_ajax_nopriv_flavor_radio_programacion', [$this, 'ajax_programacion']);
        add_action('wp_ajax_flavor_radio_enviar_dedicatoria', [$this, 'ajax_enviar_dedicatoria']);
        add_action('wp_ajax_flavor_radio_chat_mensaje', [$this, 'ajax_chat_mensaje']);
        add_action('wp_ajax_flavor_radio_chat_mensajes', [$this, 'ajax_chat_mensajes']);
        add_action('wp_ajax_nopriv_flavor_radio_chat_mensajes', [$this, 'ajax_chat_mensajes']);
        add_action('wp_ajax_flavor_radio_proponer_programa', [$this, 'ajax_proponer_programa']);
        add_action('wp_ajax_flavor_radio_reportar_oyente', [$this, 'ajax_reportar_oyente']);
        add_action('wp_ajax_nopriv_flavor_radio_reportar_oyente', [$this, 'ajax_reportar_oyente']);
        add_action('wp_ajax_flavor_radio_mis_dedicatorias', [$this, 'ajax_mis_dedicatorias']);
        add_action('wp_ajax_flavor_radio_podcasts', [$this, 'ajax_podcasts']);
        add_action('wp_ajax_nopriv_flavor_radio_podcasts', [$this, 'ajax_podcasts']);

        // Admin AJAX
        add_action('wp_ajax_flavor_radio_admin_aprobar_dedicatoria', [$this, 'ajax_admin_aprobar_dedicatoria']);
        add_action('wp_ajax_flavor_radio_admin_emitir_dedicatoria', [$this, 'ajax_admin_emitir_dedicatoria']);
        add_action('wp_ajax_flavor_radio_admin_aprobar_programa', [$this, 'ajax_admin_aprobar_programa']);
        add_action('wp_ajax_flavor_radio_admin_crear_emision', [$this, 'ajax_admin_crear_emision']);
        add_action('wp_ajax_flavor_radio_admin_iniciar_emision', [$this, 'ajax_admin_iniciar_emision']);
        add_action('wp_ajax_flavor_radio_admin_finalizar_emision', [$this, 'ajax_admin_finalizar_emision']);
        add_action('wp_ajax_flavor_radio_admin_stats', [$this, 'ajax_admin_stats']);

        // Shortcodes
        $this->register_shortcodes();

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Dashboard tab (básico)
        add_filter('flavor_user_dashboard_tabs', [$this, 'add_dashboard_tab']);

        // Dashboard tabs extendidos para el usuario
        $this->init_dashboard_tabs();

        // Cron para actualizar oyentes
        add_action('flavor_radio_actualizar_oyentes', [$this, 'cron_actualizar_oyentes']);
        if (!wp_next_scheduled('flavor_radio_actualizar_oyentes')) {
            wp_schedule_event(time(), 'every_minute', 'flavor_radio_actualizar_oyentes');
        }

        // Registrar intervalo de un minuto
        add_filter('cron_schedules', function($schedules) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display' => __('Cada minuto', 'flavor-chat-ia')
            ];
            return $schedules;
        });
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('flavor_radio_player', [$this, 'shortcode_player']);
        add_shortcode('flavor_radio_programacion', [$this, 'shortcode_programacion']);
        add_shortcode('flavor_radio_dedicatorias', [$this, 'shortcode_dedicatorias']);
        add_shortcode('flavor_radio_chat', [$this, 'shortcode_chat']);
        add_shortcode('flavor_radio_proponer', [$this, 'shortcode_proponer_programa']);
        add_shortcode('flavor_radio_podcasts', [$this, 'shortcode_podcasts']);
        add_shortcode('radio_en_vivo', [$this, 'shortcode_player']);
        add_shortcode('radio_programacion', [$this, 'shortcode_programacion']);
        add_shortcode('radio_dedicatorias', [$this, 'shortcode_dedicatorias']);
        add_shortcode('radio_chat', [$this, 'shortcode_chat']);
        add_shortcode('radio_proponer', [$this, 'shortcode_proponer_programa']);
        add_shortcode('radio_podcasts', [$this, 'shortcode_podcasts']);
        add_shortcode('radio_mis_programas', [$this, 'shortcode_mis_programas']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';
        $tabla_chat = $wpdb->prefix . 'flavor_radio_chat';
        $tabla_oyentes = $wpdb->prefix . 'flavor_radio_oyentes';
        $tabla_propuestas = $wpdb->prefix . 'flavor_radio_propuestas';
        $tabla_podcasts = $wpdb->prefix . 'flavor_radio_podcasts';

        $sql_programas = "CREATE TABLE IF NOT EXISTS $tabla_programas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            locutor_id bigint(20) unsigned NOT NULL,
            co_locutores JSON DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            genero_musical varchar(100) DEFAULT NULL,
            frecuencia enum('diario','semanal','quincenal','mensual','especial') DEFAULT 'semanal',
            dias_semana JSON DEFAULT NULL COMMENT 'Array de días: [1,3,5] = Lun, Mie, Vie',
            hora_inicio time DEFAULT NULL,
            duracion_minutos int(11) DEFAULT 60,
            estado enum('pendiente','activo','pausado','finalizado') DEFAULT 'pendiente',
            oyentes_promedio int(11) DEFAULT 0,
            total_episodios int(11) DEFAULT 0,
            redes_sociales JSON DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY locutor_id (locutor_id),
            KEY estado (estado),
            KEY slug (slug)
        ) $charset_collate;";

        $sql_emision = "CREATE TABLE IF NOT EXISTS $tabla_emision (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('programa','musica','noticia','anuncio','especial') DEFAULT 'programa',
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha_hora_inicio datetime NOT NULL,
            fecha_hora_fin datetime NOT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            en_vivo tinyint(1) DEFAULT 0,
            oyentes_pico int(11) DEFAULT 0,
            oyentes_total int(11) DEFAULT 0,
            chat_activo tinyint(1) DEFAULT 1,
            estado enum('programado','en_emision','finalizado','cancelado') DEFAULT 'programado',
            notas_locutor text DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY fecha_hora_inicio (fecha_hora_inicio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_dedicatorias = "CREATE TABLE IF NOT EXISTS $tabla_dedicatorias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            de_nombre varchar(100) NOT NULL,
            para_nombre varchar(100) NOT NULL,
            mensaje text NOT NULL,
            cancion_titulo varchar(255) DEFAULT NULL,
            cancion_artista varchar(255) DEFAULT NULL,
            cancion_url varchar(500) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada','emitida') DEFAULT 'pendiente',
            emision_id bigint(20) unsigned DEFAULT NULL,
            motivo_rechazo varchar(255) DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_emision datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY emision_id (emision_id)
        ) $charset_collate;";

        $sql_chat = "CREATE TABLE IF NOT EXISTS $tabla_chat (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            emision_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            tipo enum('mensaje','mencion','alerta') DEFAULT 'mensaje',
            destacado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY emision_id (emision_id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        $sql_oyentes = "CREATE TABLE IF NOT EXISTS $tabla_oyentes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            emision_id bigint(20) unsigned DEFAULT NULL,
            dispositivo varchar(50) DEFAULT NULL,
            inicio datetime DEFAULT CURRENT_TIMESTAMP,
            ultima_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            duracion_segundos int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY usuario_id (usuario_id),
            KEY emision_id (emision_id),
            KEY activo (activo)
        ) $charset_collate;";

        $sql_propuestas = "CREATE TABLE IF NOT EXISTS $tabla_propuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            nombre_programa varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            frecuencia_deseada varchar(50) DEFAULT NULL,
            horario_preferido varchar(100) DEFAULT NULL,
            experiencia text DEFAULT NULL,
            demo_url varchar(500) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
            notas_admin text DEFAULT NULL,
            programa_id bigint(20) unsigned DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_podcasts = "CREATE TABLE IF NOT EXISTS $tabla_podcasts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) unsigned NOT NULL,
            emision_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            archivo_url varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT 0,
            tamano_bytes bigint(20) DEFAULT 0,
            imagen_url varchar(500) DEFAULT NULL,
            reproducciones int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            publicado tinyint(1) DEFAULT 1,
            fecha_emision datetime DEFAULT NULL,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY emision_id (emision_id),
            KEY publicado (publicado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_programas);
        dbDelta($sql_emision);
        dbDelta($sql_dedicatorias);
        dbDelta($sql_chat);
        dbDelta($sql_oyentes);
        dbDelta($sql_propuestas);
        dbDelta($sql_podcasts);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Stream info
        register_rest_route($namespace, '/radio/stream', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_stream'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Programa actual
        register_rest_route($namespace, '/radio/ahora', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_programa_actual'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Programación
        register_rest_route($namespace, '/radio/programacion', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_programacion'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Programas
        register_rest_route($namespace, '/radio/programas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_programas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Programa detalle
        register_rest_route($namespace, '/radio/programa/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_programa_detalle'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Enviar dedicatoria
        register_rest_route($namespace, '/radio/dedicatoria', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_enviar_dedicatoria'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mis dedicatorias
        register_rest_route($namespace, '/radio/mis-dedicatorias', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_dedicatorias'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Chat mensajes
        register_rest_route($namespace, '/radio/chat/(?P<emision_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_chat_mensajes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Enviar mensaje chat
        register_rest_route($namespace, '/radio/chat/(?P<emision_id>\d+)/mensaje', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_chat_enviar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Proponer programa
        register_rest_route($namespace, '/radio/proponer', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_proponer_programa'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Podcasts
        register_rest_route($namespace, '/radio/podcasts', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_podcasts'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Podcast detalle
        register_rest_route($namespace, '/radio/podcast/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_podcast_detalle'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Reportar oyente (heartbeat)
        register_rest_route($namespace, '/radio/oyente', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reportar_oyente'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Contador de oyentes
        register_rest_route($namespace, '/radio/oyentes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_contar_oyentes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    // =========================================================================
    // REST Endpoints
    // =========================================================================

    /**
     * REST: Obtener info del stream
     */
    public function rest_get_stream($request) {
        $settings = $this->get_settings();

        return new WP_REST_Response([
            'success' => true,
            'stream' => [
                'url' => $settings['url_stream'],
                'url_hd' => $settings['url_stream_hd'],
                'frecuencia_fm' => $settings['frecuencia_fm'],
                'nombre' => $settings['nombre_radio'],
                'slogan' => $settings['slogan'],
                'logo' => $settings['logo_url'],
                'color' => $settings['color_marca'],
            ],
        ], 200);
    }

    /**
     * REST: Programa actual en emisión
     */
    public function rest_programa_actual($request) {
        global $wpdb;
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $ahora = current_time('mysql');

        // Buscar emisión actual
        $emision = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, p.nombre as programa_nombre, p.imagen_url as programa_imagen,
                    p.locutor_id, u.display_name as locutor_nombre
             FROM $tabla_emision e
             LEFT JOIN $tabla_programas p ON e.programa_id = p.id
             LEFT JOIN {$wpdb->users} u ON p.locutor_id = u.ID
             WHERE e.estado = 'en_emision'
             OR (e.estado = 'programado' AND e.fecha_hora_inicio <= %s AND e.fecha_hora_fin >= %s)
             ORDER BY e.fecha_hora_inicio DESC
             LIMIT 1",
            $ahora, $ahora
        ));

        if (!$emision) {
            // Sin emisión actual, buscar siguiente
            $siguiente = $wpdb->get_row($wpdb->prepare(
                "SELECT e.*, p.nombre as programa_nombre, p.imagen_url as programa_imagen
                 FROM $tabla_emision e
                 LEFT JOIN $tabla_programas p ON e.programa_id = p.id
                 WHERE e.estado = 'programado' AND e.fecha_hora_inicio > %s
                 ORDER BY e.fecha_hora_inicio ASC
                 LIMIT 1",
                $ahora
            ));

            return new WP_REST_Response([
                'success' => true,
                'en_vivo' => false,
                'siguiente' => $siguiente ? [
                    'titulo' => $siguiente->titulo,
                    'programa' => $siguiente->programa_nombre,
                    'imagen' => $siguiente->programa_imagen,
                    'hora_inicio' => date('H:i', strtotime($siguiente->fecha_hora_inicio)),
                    'en' => human_time_diff(current_time('timestamp'), strtotime($siguiente->fecha_hora_inicio)),
                ] : null,
                'oyentes' => $this->get_oyentes_actuales(),
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'en_vivo' => true,
            'emision' => [
                'id' => $emision->id,
                'titulo' => $emision->titulo,
                'descripcion' => $emision->descripcion,
                'tipo' => $emision->tipo,
                'programa' => $emision->programa_nombre,
                'programa_id' => $emision->programa_id,
                'imagen' => $emision->programa_imagen,
                'locutor' => $emision->locutor_nombre,
                'hora_inicio' => date('H:i', strtotime($emision->fecha_hora_inicio)),
                'hora_fin' => date('H:i', strtotime($emision->fecha_hora_fin)),
                'progreso' => $this->calcular_progreso($emision),
                'chat_activo' => (bool) $emision->chat_activo,
            ],
            'oyentes' => $this->get_oyentes_actuales(),
        ], 200);
    }

    /**
     * REST: Programación
     */
    public function rest_programacion($request) {
        global $wpdb;
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $fecha = sanitize_text_field($request->get_param('fecha') ?: date('Y-m-d'));
        $dias = absint($request->get_param('dias') ?: 7);

        $fecha_fin = date('Y-m-d', strtotime($fecha . " +$dias days"));

        $emisiones = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, p.nombre as programa_nombre, p.imagen_url as programa_imagen,
                    p.categoria, u.display_name as locutor_nombre
             FROM $tabla_emision e
             LEFT JOIN $tabla_programas p ON e.programa_id = p.id
             LEFT JOIN {$wpdb->users} u ON p.locutor_id = u.ID
             WHERE DATE(e.fecha_hora_inicio) >= %s AND DATE(e.fecha_hora_inicio) < %s
             AND e.estado != 'cancelado'
             ORDER BY e.fecha_hora_inicio ASC",
            $fecha, $fecha_fin
        ));

        // Agrupar por día
        $programacion = [];
        foreach ($emisiones as $emision) {
            $dia = date('Y-m-d', strtotime($emision->fecha_hora_inicio));
            if (!isset($programacion[$dia])) {
                $programacion[$dia] = [
                    'fecha' => $dia,
                    'dia_nombre' => date_i18n('l', strtotime($dia)),
                    'emisiones' => [],
                ];
            }

            $programacion[$dia]['emisiones'][] = [
                'id' => $emision->id,
                'titulo' => $emision->titulo,
                'descripcion' => $emision->descripcion,
                'tipo' => $emision->tipo,
                'programa' => $emision->programa_nombre,
                'programa_id' => $emision->programa_id,
                'imagen' => $emision->programa_imagen,
                'categoria' => $emision->categoria,
                'locutor' => $emision->locutor_nombre,
                'hora_inicio' => date('H:i', strtotime($emision->fecha_hora_inicio)),
                'hora_fin' => date('H:i', strtotime($emision->fecha_hora_fin)),
                'estado' => $emision->estado,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'programacion' => array_values($programacion),
        ], 200);
    }

    /**
     * REST: Listar programas
     */
    public function rest_programas($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programas';

        $categoria = sanitize_text_field($request->get_param('categoria') ?: '');
        $limite = absint($request->get_param('limite') ?: 20);

        $where = ["p.estado = 'activo'"];
        $params = [];

        if ($categoria) {
            $where[] = 'p.categoria = %s';
            $params[] = $categoria;
        }

        $params[] = $limite;

        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as locutor_nombre
             FROM $tabla p
             LEFT JOIN {$wpdb->users} u ON p.locutor_id = u.ID
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.oyentes_promedio DESC, p.nombre ASC
             LIMIT %d",
            ...$params
        ));

        $data = [];
        foreach ($programas as $prog) {
            $dias = json_decode($prog->dias_semana, true) ?: [];
            $dias_nombres = array_map(function($d) {
                $nombres = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                return $nombres[$d] ?? '';
            }, $dias);

            $data[] = [
                'id' => $prog->id,
                'nombre' => $prog->nombre,
                'slug' => $prog->slug,
                'descripcion' => $prog->descripcion,
                'imagen' => $prog->imagen_url,
                'categoria' => $prog->categoria,
                'locutor' => [
                    'id' => $prog->locutor_id,
                    'nombre' => $prog->locutor_nombre,
                ],
                'horario' => [
                    'frecuencia' => $prog->frecuencia,
                    'dias' => $dias_nombres,
                    'hora' => $prog->hora_inicio ? date('H:i', strtotime($prog->hora_inicio)) : null,
                    'duracion' => $prog->duracion_minutos,
                ],
                'oyentes_promedio' => $prog->oyentes_promedio,
                'total_episodios' => $prog->total_episodios,
            ];
        }

        // Categorías disponibles
        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

        $respuesta = [
            'success' => true,
            'programas' => $data,
            'categorias' => $categorias,
        ];

        return new WP_REST_Response($this->sanitize_public_radio_response($respuesta), 200);
    }

    /**
     * REST: Enviar dedicatoria
     */
    public function rest_enviar_dedicatoria($request) {
        $settings = $this->get_settings();

        if (!$settings['permite_dedicatorias']) {
            return new WP_REST_Response(['error' => __('Las dedicatorias están deshabilitadas', 'flavor-chat-ia')], 403);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';
        $usuario_id = get_current_user_id();

        // Verificar límite diario
        $hoy = date('Y-m-d');
        $enviadas_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND DATE(fecha_solicitud) = %s",
            $usuario_id, $hoy
        ));

        if ($enviadas_hoy >= $settings['max_dedicatorias_dia']) {
            return new WP_REST_Response([
                'error' => sprintf('Has alcanzado el límite de %d dedicatorias por día', $settings['max_dedicatorias_dia'])
            ], 400);
        }

        $de = sanitize_text_field($request->get_param('de'));
        $para = sanitize_text_field($request->get_param('para'));
        $mensaje = sanitize_textarea_field($request->get_param('mensaje'));
        $cancion_titulo = sanitize_text_field($request->get_param('cancion_titulo') ?: '');
        $cancion_artista = sanitize_text_field($request->get_param('cancion_artista') ?: '');

        if (empty($de) || empty($para) || empty($mensaje)) {
            return new WP_REST_Response(['error' => __('Completa todos los campos obligatorios', 'flavor-chat-ia')], 400);
        }

        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'de_nombre' => $de,
            'para_nombre' => $para,
            'mensaje' => $mensaje,
            'cancion_titulo' => $cancion_titulo,
            'cancion_artista' => $cancion_artista,
        ], ['%d', '%s', '%s', '%s', '%s', '%s']);

        // Gamificación
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $settings['puntos_enviar_dedicatoria'], 'enviar_dedicatoria');

        // Notificar a admins
        do_action('flavor_notificacion_enviar', 0, 'radio_nueva_dedicatoria', [
            'de' => $de,
            'para' => $para,
            'usuario' => wp_get_current_user()->display_name,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Tu dedicatoria ha sido enviada y será revisada por nuestro equipo', 'flavor-chat-ia'),
            'dedicatoria_id' => $wpdb->insert_id,
        ], 201);
    }

    /**
     * REST: Mis dedicatorias
     */
    public function rest_mis_dedicatorias($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';
        $usuario_id = get_current_user_id();

        $dedicatorias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY fecha_solicitud DESC LIMIT 20",
            $usuario_id
        ));

        $data = [];
        foreach ($dedicatorias as $d) {
            $data[] = [
                'id' => $d->id,
                'de' => $d->de_nombre,
                'para' => $d->para_nombre,
                'mensaje' => $d->mensaje,
                'cancion' => $d->cancion_titulo ? ($d->cancion_titulo . ' - ' . $d->cancion_artista) : null,
                'estado' => $d->estado,
                'fecha_solicitud' => $d->fecha_solicitud,
                'fecha_emision' => $d->fecha_emision,
            ];
        }

        return new WP_REST_Response(['success' => true, 'dedicatorias' => $data], 200);
    }

    /**
     * REST: Chat mensajes
     */
    public function rest_chat_mensajes($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_chat';

        $emision_id = absint($request->get_param('emision_id'));
        $desde = absint($request->get_param('desde') ?: 0);
        $limite = absint($request->get_param('limite') ?: 50);

        $where = ['c.emision_id = %d', 'c.eliminado = 0'];
        $params = [$emision_id];

        if ($desde > 0) {
            $where[] = 'c.id > %d';
            $params[] = $desde;
        }

        $params[] = $limite;

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as autor_nombre
             FROM $tabla c
             LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.fecha ASC
             LIMIT %d",
            ...$params
        ));

        $data = [];
        foreach ($mensajes as $m) {
            $data[] = [
                'id' => $m->id,
                'mensaje' => esc_html($m->mensaje),
                'tipo' => $m->tipo,
                'destacado' => (bool) $m->destacado,
                'autor' => [
                    'id' => $m->usuario_id,
                    'nombre' => $m->autor_nombre,
                    'avatar' => get_avatar_url($m->usuario_id, ['size' => 32]),
                ],
                'fecha' => $m->fecha,
            ];
        }

        $respuesta = ['success' => true, 'mensajes' => $data];

        return new WP_REST_Response($this->sanitize_public_radio_response($respuesta), 200);
    }

    private function sanitize_public_radio_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['programas']) && is_array($respuesta['programas'])) {
            $respuesta['programas'] = array_map(function($programa) {
                if (!is_array($programa)) {
                    return $programa;
                }

                if (!empty($programa['locutor']) && is_array($programa['locutor'])) {
                    unset($programa['locutor']['id']);
                }

                return $programa;
            }, $respuesta['programas']);
        }

        if (!empty($respuesta['mensajes']) && is_array($respuesta['mensajes'])) {
            $respuesta['mensajes'] = array_map(function($mensaje) {
                if (!is_array($mensaje)) {
                    return $mensaje;
                }

                if (!empty($mensaje['autor']) && is_array($mensaje['autor'])) {
                    unset($mensaje['autor']['id']);
                    $mensaje['autor']['avatar'] = '';
                }

                return $mensaje;
            }, $respuesta['mensajes']);
        }

        return $respuesta;
    }

    /**
     * REST: Enviar mensaje chat
     */
    public function rest_chat_enviar($request) {
        $settings = $this->get_settings();

        if (!$settings['chat_en_vivo']) {
            return new WP_REST_Response(['error' => __('El chat está deshabilitado', 'flavor-chat-ia')], 403);
        }

        global $wpdb;
        $tabla_chat = $wpdb->prefix . 'flavor_radio_chat';
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';

        $emision_id = absint($request->get_param('emision_id'));
        $mensaje = sanitize_textarea_field($request->get_param('mensaje'));
        $usuario_id = get_current_user_id();

        if (empty($mensaje)) {
            return new WP_REST_Response(['error' => __('El mensaje no puede estar vacío', 'flavor-chat-ia')], 400);
        }

        // Verificar que la emisión tiene chat activo
        $emision = $wpdb->get_row($wpdb->prepare(
            "SELECT chat_activo, estado FROM $tabla_emision WHERE id = %d",
            $emision_id
        ));

        if (!$emision || !$emision->chat_activo || $emision->estado !== 'en_emision') {
            return new WP_REST_Response(['error' => __('El chat no está disponible para esta emisión', 'flavor-chat-ia')], 400);
        }

        // Rate limiting simple
        $ultimo_mensaje = $wpdb->get_var($wpdb->prepare(
            "SELECT fecha FROM $tabla_chat WHERE usuario_id = %d ORDER BY fecha DESC LIMIT 1",
            $usuario_id
        ));

        if ($ultimo_mensaje && (strtotime('now') - strtotime($ultimo_mensaje)) < 3) {
            return new WP_REST_Response(['error' => __('Espera unos segundos entre mensajes', 'flavor-chat-ia')], 429);
        }

        $tipo = 'mensaje';
        if (strpos($mensaje, '@') === 0) {
            $tipo = 'mencion';
        }

        $wpdb->insert($tabla_chat, [
            'emision_id' => $emision_id,
            'usuario_id' => $usuario_id,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
        ], ['%d', '%d', '%s', '%s']);

        $user = wp_get_current_user();

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => [
                'id' => $wpdb->insert_id,
                'mensaje' => esc_html($mensaje),
                'tipo' => $tipo,
                'destacado' => false,
                'autor' => [
                    'id' => $usuario_id,
                    'nombre' => $user->display_name,
                    'avatar' => get_avatar_url($usuario_id, ['size' => 32]),
                ],
                'fecha' => current_time('mysql'),
            ],
        ], 201);
    }

    /**
     * REST: Proponer programa
     */
    public function rest_proponer_programa($request) {
        $settings = $this->get_settings();

        if (!$settings['permite_locutores_comunidad']) {
            return new WP_REST_Response(['error' => __('Las propuestas de programas están cerradas', 'flavor-chat-ia')], 403);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_propuestas';
        $usuario_id = get_current_user_id();

        // Verificar si ya tiene propuesta pendiente
        $pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        if ($pendiente) {
            return new WP_REST_Response(['error' => __('Ya tienes una propuesta pendiente de revisión', 'flavor-chat-ia')], 400);
        }

        $nombre = sanitize_text_field($request->get_param('nombre'));
        $descripcion = sanitize_textarea_field($request->get_param('descripcion'));
        $categoria = sanitize_text_field($request->get_param('categoria') ?: '');
        $frecuencia = sanitize_text_field($request->get_param('frecuencia') ?: '');
        $horario = sanitize_text_field($request->get_param('horario') ?: '');
        $experiencia = sanitize_textarea_field($request->get_param('experiencia') ?: '');
        $demo_url = esc_url_raw($request->get_param('demo_url') ?: '');

        if (empty($nombre) || empty($descripcion)) {
            return new WP_REST_Response(['error' => __('Nombre y descripción son obligatorios', 'flavor-chat-ia')], 400);
        }

        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'nombre_programa' => $nombre,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'frecuencia_deseada' => $frecuencia,
            'horario_preferido' => $horario,
            'experiencia' => $experiencia,
            'demo_url' => $demo_url,
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        // Gamificación
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $settings['puntos_proponer_programa'], 'proponer_programa_radio');

        // Notificar
        do_action('flavor_notificacion_enviar', 0, 'radio_nueva_propuesta', [
            'nombre' => $nombre,
            'usuario' => wp_get_current_user()->display_name,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('limite', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * REST: Podcasts
     */
    public function rest_podcasts($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_podcasts';
        $tabla_prog = $wpdb->prefix . 'flavor_radio_programas';

        $programa_id = absint($request->get_param('programa_id') ?: 0);
        $limite = absint($request->get_param('limite') ?: 20);
        $pagina = absint($request->get_param('pagina') ?: 1);

        $where = ['pc.publicado = 1'];
        $params = [];

        if ($programa_id > 0) {
            $where[] = 'pc.programa_id = %d';
            $params[] = $programa_id;
        }

        $offset = ($pagina - 1) * $limite;

        // Total
        $total = $wpdb->get_var($params
            ? $wpdb->prepare("SELECT COUNT(*) FROM $tabla pc WHERE " . implode(' AND ', $where), ...$params)
            : "SELECT COUNT(*) FROM $tabla pc WHERE " . implode(' AND ', $where)
        );

        $params[] = $limite;
        $params[] = $offset;

        $podcasts = $wpdb->get_results($wpdb->prepare(
            "SELECT pc.*, p.nombre as programa_nombre, p.imagen_url as programa_imagen
             FROM $tabla pc
             LEFT JOIN $tabla_prog p ON pc.programa_id = p.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY pc.fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            ...$params
        ));

        $data = [];
        foreach ($podcasts as $pod) {
            $data[] = [
                'id' => $pod->id,
                'titulo' => $pod->titulo,
                'descripcion' => $pod->descripcion,
                'archivo' => $pod->archivo_url,
                'duracion' => $this->format_duration($pod->duracion_segundos),
                'imagen' => $pod->imagen_url ?: $pod->programa_imagen,
                'programa' => [
                    'id' => $pod->programa_id,
                    'nombre' => $pod->programa_nombre,
                ],
                'reproducciones' => $pod->reproducciones,
                'fecha' => $pod->fecha_publicacion,
                'fecha_humana' => human_time_diff(strtotime($pod->fecha_publicacion)) . ' ago',
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'podcasts' => $data,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
        ], 200);
    }

    /**
     * REST: Reportar oyente (heartbeat)
     */
    public function rest_reportar_oyente($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_oyentes';

        $session_id = sanitize_text_field($request->get_param('session_id'));
        $emision_id = absint($request->get_param('emision_id') ?: 0);
        $dispositivo = sanitize_text_field($request->get_param('dispositivo') ?: 'web');

        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }

        $usuario_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Insertar o actualizar
        $existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE session_id = %s",
            $session_id
        ));

        if ($existente) {
            $duracion = strtotime('now') - strtotime($existente->inicio);
            $wpdb->update($tabla, [
                'ultima_actividad' => current_time('mysql'),
                'duracion_segundos' => $duracion,
                'emision_id' => $emision_id ?: $existente->emision_id,
                'activo' => 1,
            ], ['id' => $existente->id], ['%s', '%d', '%d', '%d'], ['%d']);
        } else {
            $wpdb->insert($tabla, [
                'session_id' => $session_id,
                'usuario_id' => $usuario_id ?: null,
                'ip_address' => $ip,
                'emision_id' => $emision_id ?: null,
                'dispositivo' => $dispositivo,
            ], ['%s', '%d', '%s', '%d', '%s']);
        }

        return new WP_REST_Response([
            'success' => true,
            'session_id' => $session_id,
            'oyentes' => $this->get_oyentes_actuales(),
        ], 200);
    }

    /**
     * REST: Contar oyentes
     */
    public function rest_contar_oyentes($request) {
        return new WP_REST_Response([
            'success' => true,
            'oyentes' => $this->get_oyentes_actuales(),
        ], 200);
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    public function ajax_get_stream() {
        $request = new WP_REST_Request('GET');
        $response = $this->rest_get_stream($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_programa_actual() {
        $request = new WP_REST_Request('GET');
        $response = $this->rest_programa_actual($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_programacion() {
        $request = new WP_REST_Request('GET');
        $request->set_param('fecha', sanitize_text_field($_GET['fecha'] ?? date('Y-m-d')));
        $request->set_param('dias', absint($_GET['dias'] ?? 7));
        $response = $this->rest_programacion($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_enviar_dedicatoria() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('de', sanitize_text_field($_POST['de']));
        $request->set_param('para', sanitize_text_field($_POST['para']));
        $request->set_param('mensaje', sanitize_textarea_field($_POST['mensaje']));
        $request->set_param('cancion_titulo', sanitize_text_field($_POST['cancion_titulo'] ?? ''));
        $request->set_param('cancion_artista', sanitize_text_field($_POST['cancion_artista'] ?? ''));

        $response = $this->rest_enviar_dedicatoria($request);
        $data = $response->get_data();

        if ($response->get_status() === 201) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error', 'flavor-chat-ia'));
        }
    }

    public function ajax_chat_mensaje() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('emision_id', absint($_POST['emision_id']));
        $request->set_param('mensaje', sanitize_textarea_field($_POST['mensaje']));

        $response = $this->rest_chat_enviar($request);
        $data = $response->get_data();

        if ($response->get_status() === 201) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error', 'flavor-chat-ia'));
        }
    }

    public function ajax_chat_mensajes() {
        $request = new WP_REST_Request('GET');
        $request->set_param('emision_id', absint($_GET['emision_id']));
        $request->set_param('desde', absint($_GET['desde'] ?? 0));
        $response = $this->rest_chat_mensajes($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_proponer_programa() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('nombre', sanitize_text_field($_POST['nombre']));
        $request->set_param('descripcion', sanitize_textarea_field($_POST['descripcion']));
        $request->set_param('categoria', sanitize_text_field($_POST['categoria'] ?? ''));
        $request->set_param('frecuencia', sanitize_text_field($_POST['frecuencia'] ?? ''));
        $request->set_param('horario', sanitize_text_field($_POST['horario'] ?? ''));
        $request->set_param('experiencia', sanitize_textarea_field($_POST['experiencia'] ?? ''));
        $request->set_param('demo_url', esc_url_raw($_POST['demo_url'] ?? ''));

        $response = $this->rest_proponer_programa($request);
        $data = $response->get_data();

        if ($response->get_status() === 201) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error', 'flavor-chat-ia'));
        }
    }

    public function ajax_reportar_oyente() {
        $request = new WP_REST_Request('POST');
        $request->set_param('session_id', sanitize_text_field($_POST['session_id'] ?? ''));
        $request->set_param('emision_id', absint($_POST['emision_id'] ?? 0));
        $request->set_param('dispositivo', sanitize_text_field($_POST['dispositivo'] ?? 'web'));

        $response = $this->rest_reportar_oyente($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_mis_dedicatorias() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('GET');
        $response = $this->rest_mis_dedicatorias($request);
        wp_send_json_success($response->get_data());
    }

    public function ajax_podcasts() {
        $request = new WP_REST_Request('GET');
        $request->set_param('programa_id', absint($_GET['programa_id'] ?? 0));
        $request->set_param('limite', absint($_GET['limite'] ?? 20));
        $request->set_param('pagina', absint($_GET['pagina'] ?? 1));

        $response = $this->rest_podcasts($request);
        wp_send_json_success($response->get_data());
    }

    // =========================================================================
    // Admin AJAX
    // =========================================================================

    public function ajax_admin_aprobar_dedicatoria() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';

        $dedicatoria_id = absint($_POST['dedicatoria_id']);
        $accion = sanitize_text_field($_POST['accion']); // aprobar, rechazar

        $nuevo_estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';

        $wpdb->update($tabla, [
            'estado' => $nuevo_estado,
            'motivo_rechazo' => $accion === 'rechazar' ? sanitize_text_field($_POST['motivo'] ?? '') : null,
        ], ['id' => $dedicatoria_id], ['%s', '%s'], ['%d']);

        // Notificar al usuario
        $dedicatoria = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $dedicatoria_id));
        if ($dedicatoria) {
            do_action('flavor_notificacion_enviar', $dedicatoria->usuario_id, 'radio_dedicatoria_' . $nuevo_estado, [
                'de' => $dedicatoria->de_nombre,
                'para' => $dedicatoria->para_nombre,
            ]);
        }

        $estado_dedicatoria = $accion === 'aprobar' ? __('aprobada', 'flavor-chat-ia') : __('rechazada', 'flavor-chat-ia');
        wp_send_json_success(['mensaje' => sprintf(__('Dedicatoria %s', 'flavor-chat-ia'), $estado_dedicatoria)]);
    }

    public function ajax_admin_emitir_dedicatoria() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';

        $dedicatoria_id = absint($_POST['dedicatoria_id']);

        $wpdb->update($tabla, [
            'estado' => 'emitida',
            'fecha_emision' => current_time('mysql'),
        ], ['id' => $dedicatoria_id], ['%s', '%s'], ['%d']);

        wp_send_json_success(['mensaje' => __('Marcada como emitida', 'flavor-chat-ia')]);
    }

    public function ajax_admin_aprobar_programa() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_radio_propuestas';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $propuesta_id = absint($_POST['propuesta_id']);
        $accion = sanitize_text_field($_POST['accion']); // aprobar, rechazar

        $propuesta = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_propuestas WHERE id = %d", $propuesta_id));

        if (!$propuesta) {
            wp_send_json_error(__('Propuesta no encontrada', 'flavor-chat-ia'));
        }

        if ($accion === 'aprobar') {
            // Crear programa
            $slug = sanitize_title($propuesta->nombre_programa);
            $slug_base = $slug;
            $counter = 1;
            while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tabla_programas WHERE slug = %s", $slug))) {
                $slug = $slug_base . '-' . $counter++;
            }

            $wpdb->insert($tabla_programas, [
                'nombre' => $propuesta->nombre_programa,
                'slug' => $slug,
                'descripcion' => $propuesta->descripcion,
                'locutor_id' => $propuesta->usuario_id,
                'categoria' => $propuesta->categoria,
                'estado' => 'activo',
            ], ['%s', '%s', '%s', '%d', '%s', '%s']);

            $programa_id = $wpdb->insert_id;

            $wpdb->update($tabla_propuestas, [
                'estado' => 'aprobada',
                'programa_id' => $programa_id,
                'fecha_respuesta' => current_time('mysql'),
                'notas_admin' => sanitize_textarea_field($_POST['notas'] ?? ''),
            ], ['id' => $propuesta_id], ['%s', '%d', '%s', '%s'], ['%d']);
        } else {
            $wpdb->update($tabla_propuestas, [
                'estado' => 'rechazada',
                'fecha_respuesta' => current_time('mysql'),
                'notas_admin' => sanitize_textarea_field($_POST['notas'] ?? ''),
            ], ['id' => $propuesta_id], ['%s', '%s', '%s'], ['%d']);
        }

        // Notificar
        do_action('flavor_notificacion_enviar', $propuesta->usuario_id, 'radio_propuesta_' . ($accion === 'aprobar' ? 'aprobada' : 'rechazada'), [
            'nombre' => $propuesta->nombre_programa,
        ]);

        $estado_propuesta = $accion === 'aprobar' ? __('aprobada', 'flavor-chat-ia') : __('rechazada', 'flavor-chat-ia');
        wp_send_json_success(['mensaje' => sprintf(__('Propuesta %s', 'flavor-chat-ia'), $estado_propuesta)]);
    }

    public function ajax_admin_crear_emision() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programacion';

        $titulo = sanitize_text_field($_POST['titulo']);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $programa_id = absint($_POST['programa_id'] ?? 0);
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'programa');
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio']);
        $fecha_fin = sanitize_text_field($_POST['fecha_fin']);

        if (empty($titulo) || empty($fecha_inicio) || empty($fecha_fin)) {
            wp_send_json_error(__('Completa los campos obligatorios', 'flavor-chat-ia'));
        }

        $wpdb->insert($tabla, [
            'programa_id' => $programa_id ?: null,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'fecha_hora_inicio' => $fecha_inicio,
            'fecha_hora_fin' => $fecha_fin,
            'estado' => 'programado',
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        wp_send_json_success([
            'mensaje' => __('Emisión programada', 'flavor-chat-ia'),
            'emision_id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_admin_iniciar_emision() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programacion';

        $emision_id = absint($_POST['emision_id']);

        // Finalizar cualquier emisión activa
        $wpdb->update($tabla, ['estado' => 'finalizado'], ['estado' => 'en_emision'], ['%s'], ['%s']);

        // Iniciar la nueva
        $wpdb->update($tabla, [
            'estado' => 'en_emision',
            'en_vivo' => 1,
        ], ['id' => $emision_id], ['%s', '%d'], ['%d']);

        wp_send_json_success(['mensaje' => __('Emisión iniciada', 'flavor-chat-ia')]);
    }

    public function ajax_admin_finalizar_emision() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_oyentes = $wpdb->prefix . 'flavor_radio_oyentes';

        $emision_id = absint($_POST['emision_id']);

        // Calcular oyentes totales
        $oyentes_total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $tabla_oyentes WHERE emision_id = %d",
            $emision_id
        ));

        $wpdb->update($tabla_emision, [
            'estado' => 'finalizado',
            'en_vivo' => 0,
            'oyentes_total' => $oyentes_total,
        ], ['id' => $emision_id], ['%s', '%d', '%d'], ['%d']);

        // Actualizar promedio del programa
        $emision = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_emision WHERE id = %d", $emision_id));
        if ($emision && $emision->programa_id) {
            $this->actualizar_promedio_programa($emision->programa_id);
        }

        wp_send_json_success(['mensaje' => __('Emisión finalizada', 'flavor-chat-ia')]);
    }

    public function ajax_admin_stats() {
        check_ajax_referer('flavor_radio_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;

        $stats = [
            'programas_activos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_radio_programas WHERE estado = 'activo'"),
            'emisiones_mes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_radio_programacion WHERE MONTH(fecha_hora_inicio) = MONTH(NOW()) AND YEAR(fecha_hora_inicio) = YEAR(NOW())"),
            'dedicatorias_pendientes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_radio_dedicatorias WHERE estado = 'pendiente'"),
            'propuestas_pendientes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_radio_propuestas WHERE estado = 'pendiente'"),
            'oyentes_actuales' => $this->get_oyentes_actuales(),
            'oyentes_hoy' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}flavor_radio_oyentes WHERE DATE(inicio) = CURDATE()"),
            'podcasts_total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flavor_radio_podcasts WHERE publicado = 1"),
            'reproducciones_podcast' => (int) $wpdb->get_var("SELECT SUM(reproducciones) FROM {$wpdb->prefix}flavor_radio_podcasts"),
        ];

        // Top programas
        $stats['top_programas'] = $wpdb->get_results(
            "SELECT id, nombre, oyentes_promedio FROM {$wpdb->prefix}flavor_radio_programas WHERE estado = 'activo' ORDER BY oyentes_promedio DESC LIMIT 5"
        );

        // Oyentes por día (última semana)
        $stats['oyentes_semana'] = $wpdb->get_results(
            "SELECT DATE(inicio) as fecha, COUNT(DISTINCT session_id) as oyentes
             FROM {$wpdb->prefix}flavor_radio_oyentes
             WHERE inicio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(inicio)
             ORDER BY fecha"
        );

        wp_send_json_success($stats);
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Shortcode: Reproductor de radio
     */
    public function shortcode_player($atts) {
        $atts = shortcode_atts([
            'estilo' => 'completo', // compacto, completo
            'autoplay' => 'false',
            'mostrar_programa' => 'true',
            'mostrar_oyentes' => 'true',
        ], $atts);

        $settings = $this->get_settings();

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-player <?php echo esc_attr('estilo-' . $atts['estilo']); ?>"
             data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
             data-stream="<?php echo esc_attr($settings['url_stream']); ?>"
             data-stream-hd="<?php echo esc_attr($settings['url_stream_hd']); ?>">

            <div class="radio-player-visual">
                <?php if ($settings['logo_url']): ?>
                    <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="<?php echo esc_attr($settings['nombre_radio']); ?>" class="radio-logo">
                <?php endif; ?>
                <div class="radio-visualizer">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>
            </div>

            <div class="radio-player-info">
                <h3 class="radio-nombre"><?php echo esc_html($settings['nombre_radio']); ?></h3>
                <p class="radio-slogan"><?php echo esc_html($settings['slogan']); ?></p>
                <?php if ($settings['frecuencia_fm']): ?>
                    <span class="radio-fm"><?php echo esc_html($settings['frecuencia_fm']); ?> FM</span>
                <?php endif; ?>

                <?php if ($atts['mostrar_programa'] === 'true'): ?>
                <div class="radio-programa-actual">
                    <span class="radio-badge-vivo"><?php _e('En Vivo', 'flavor-chat-ia'); ?></span>
                    <span class="radio-programa-nombre"><?php _e('Cargando...', 'flavor-chat-ia'); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="radio-player-controls">
                <button class="radio-btn-play" aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
                <div class="radio-volume">
                    <button class="radio-btn-mute" aria-label="<?php esc_attr_e('Silenciar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-volumeon"></span>
                    </button>
                    <input type="range" class="radio-volume-slider" min="0" max="100" value="80">
                </div>
                <?php if ($settings['url_stream_hd']): ?>
                <button class="radio-btn-hd" title="<?php esc_attr_e('Alta Calidad', 'flavor-chat-ia'); ?>"><?php echo esc_html__('HD', 'flavor-chat-ia'); ?></button>
                <?php endif; ?>
            </div>

            <?php if ($atts['mostrar_oyentes'] === 'true' && $settings['oyentes_contador_publico']): ?>
            <div class="radio-oyentes">
                <span class="dashicons dashicons-groups"></span>
                <span class="radio-oyentes-count">0</span> <?php _e('escuchando', 'flavor-chat-ia'); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Programación
     */
    public function shortcode_programacion($atts) {
        $atts = shortcode_atts([
            'vista' => 'semana', // dia, semana
            'dias' => 7,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-programacion"
             data-vista="<?php echo esc_attr($atts['vista']); ?>"
             data-dias="<?php echo esc_attr($atts['dias']); ?>">

            <div class="programacion-nav">
                <button class="programacion-nav-prev"><?php echo esc_html__('&lsaquo;', 'flavor-chat-ia'); ?></button>
                <span class="programacion-nav-titulo"></span>
                <button class="programacion-nav-next"><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></button>
            </div>

            <div class="programacion-grid">
                <div class="mm-loading"><?php _e('Cargando programación...', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de dedicatorias
     */
    public function shortcode_dedicatorias($atts) {
        $settings = $this->get_settings();

        if (!$settings['permite_dedicatorias']) {
            return '<p class="radio-aviso">' . __('Las dedicatorias no están disponibles en este momento.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="radio-aviso">' . __('Debes iniciar sesión para enviar dedicatorias.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-dedicatorias">
            <h3><?php _e('Enviar Dedicatoria', 'flavor-chat-ia'); ?></h3>
            <p class="dedicatoria-info"><?php printf(__('Puedes enviar hasta %d dedicatorias por día.', 'flavor-chat-ia'), $settings['max_dedicatorias_dia']); ?></p>

            <form id="radio-form-dedicatoria">
                <?php wp_nonce_field('flavor_radio_nonce', 'radio_nonce'); ?>

                <div class="form-grupo">
                    <label><?php _e('De', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="de" required placeholder="<?php esc_attr_e('Tu nombre', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Para', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="para" required placeholder="<?php esc_attr_e('A quién va dedicada', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Mensaje', 'flavor-chat-ia'); ?></label>
                    <textarea name="mensaje" required rows="4" placeholder="<?php esc_attr_e('Escribe tu mensaje de dedicatoria...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="form-grupo form-grupo-inline">
                    <div class="form-grupo-half">
                        <label><?php _e('Canción (opcional)', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="cancion_titulo" placeholder="<?php esc_attr_e('Título de la canción', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="form-grupo-half">
                        <label><?php echo esc_html__('&nbsp;', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="cancion_artista" placeholder="<?php esc_attr_e('Artista', 'flavor-chat-ia'); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Enviar Dedicatoria', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <div class="mis-dedicatorias" style="margin-top: 2rem;">
                <h4><?php _e('Mis Dedicatorias', 'flavor-chat-ia'); ?></h4>
                <div class="mis-dedicatorias-lista"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Chat en vivo
     */
    public function shortcode_chat($atts) {
        $settings = $this->get_settings();

        if (!$settings['chat_en_vivo']) {
            return '';
        }

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-chat">
            <div class="radio-chat-header">
                <h4><?php _e('Chat en Vivo', 'flavor-chat-ia'); ?></h4>
                <span class="radio-chat-status"><?php _e('Conectando...', 'flavor-chat-ia'); ?></span>
            </div>

            <div class="radio-chat-mensajes"></div>

            <?php if (is_user_logged_in()): ?>
            <form class="radio-chat-form">
                <?php wp_nonce_field('flavor_radio_nonce', 'radio_nonce'); ?>
                <input type="text" name="mensaje" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" autocomplete="off">
                <button type="submit"><span class="dashicons dashicons-arrow-right-alt"></span></button>
            </form>
            <?php else: ?>
            <p class="radio-chat-login"><?php _e('Inicia sesión para participar en el chat', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Proponer programa
     */
    public function shortcode_proponer_programa($atts) {
        $settings = $this->get_settings();

        if (!$settings['permite_locutores_comunidad']) {
            return '<p class="radio-aviso">' . __('Las propuestas de programas están cerradas temporalmente.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="radio-aviso">' . __('Debes iniciar sesión para proponer un programa.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-proponer">
            <h3><?php _e('Proponer un Programa', 'flavor-chat-ia'); ?></h3>
            <p class="proponer-info"><?php _e('¿Tienes una idea para un programa de radio? Cuéntanos y podrías tener tu propio espacio en nuestra emisora.', 'flavor-chat-ia'); ?></p>

            <form id="radio-form-proponer">
                <?php wp_nonce_field('flavor_radio_nonce', 'radio_nonce'); ?>

                <div class="form-grupo">
                    <label><?php _e('Nombre del programa', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Descripción', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="descripcion" required rows="4" placeholder="<?php esc_attr_e('Describe de qué tratará tu programa, el formato, secciones, público objetivo...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select name="categoria">
                        <option value=""><?php _e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('musica', 'flavor-chat-ia'); ?>"><?php _e('Música', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('noticias', 'flavor-chat-ia'); ?>"><?php _e('Noticias', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('entretenimiento', 'flavor-chat-ia'); ?>"><?php _e('Entretenimiento', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('deportes', 'flavor-chat-ia'); ?>"><?php _e('Deportes', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="form-grupo form-grupo-inline">
                    <div class="form-grupo-half">
                        <label><?php _e('Frecuencia deseada', 'flavor-chat-ia'); ?></label>
                        <select name="frecuencia">
                            <option value=""><?php _e('Selecciona...', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('diario', 'flavor-chat-ia'); ?>"><?php _e('Diario', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('semanal', 'flavor-chat-ia'); ?>"><?php _e('Semanal', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('quincenal', 'flavor-chat-ia'); ?>"><?php _e('Quincenal', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('mensual', 'flavor-chat-ia'); ?>"><?php _e('Mensual', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <div class="form-grupo-half">
                        <label><?php _e('Horario preferido', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="horario" placeholder="<?php esc_attr_e('Ej: Viernes 18:00', 'flavor-chat-ia'); ?>">
                    </div>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Experiencia previa', 'flavor-chat-ia'); ?></label>
                    <textarea name="experiencia" rows="3" placeholder="<?php esc_attr_e('Cuéntanos si tienes experiencia en radio, podcasting, o similar...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Demo o muestra (URL)', 'flavor-chat-ia'); ?></label>
                    <input type="url" name="demo_url" placeholder="<?php esc_attr_e('Link a un audio de muestra si lo tienes', 'flavor-chat-ia'); ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-microphone"></span>
                    <?php _e('Enviar Propuesta', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Podcasts
     */
    public function shortcode_podcasts($atts) {
        $atts = shortcode_atts([
            'programa_id' => 0,
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-radio-podcasts"
             data-programa="<?php echo esc_attr($atts['programa_id']); ?>"
             data-limite="<?php echo esc_attr($atts['limite']); ?>">

            <div class="podcasts-filtros">
                <select class="podcasts-filtro-programa">
                    <option value=""><?php _e('Todos los programas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="podcasts-lista">
                <div class="mm-loading"><?php _e('Cargando podcasts...', 'flavor-chat-ia'); ?></div>
            </div>

            <div class="podcasts-paginacion"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis programas.
     */
    public function shortcode_mis_programas($atts = []) {
        if (!is_user_logged_in()) {
            return '<p class="radio-aviso">' . __('Debes iniciar sesión para ver tus programas.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_programas';
        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, categoria, estado, hora_inicio
             FROM {$tabla}
             WHERE locutor_id = %d
             ORDER BY estado = 'activo' DESC, nombre ASC",
            get_current_user_id()
        ));

        ob_start();
        ?>
        <div class="flavor-radio-mis-programas">
            <?php if (empty($programas)) : ?>
                <p><?php esc_html_e('Todavía no tienes programas asignados.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <ul class="radio-programas-lista">
                    <?php foreach ($programas as $programa) : ?>
                        <li>
                            <strong><?php echo esc_html($programa->nombre ?: __('Programa', 'flavor-chat-ia')); ?></strong>
                            <?php if (!empty($programa->categoria)) : ?>
                                <span><?php echo esc_html($programa->categoria); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($programa->hora_inicio)) : ?>
                                <span><?php echo esc_html(date_i18n('H:i', strtotime($programa->hora_inicio))); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // Actions del módulo
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'en_vivo' => [
                'description' => 'Obtener stream y programa actual',
                'params' => [],
            ],
            'programacion' => [
                'description' => 'Ver programación de la semana',
                'params' => ['fecha', 'dias'],
            ],
            'programas' => [
                'description' => 'Listar programas de radio',
                'params' => ['categoria', 'limite'],
            ],
            'enviar_dedicatoria' => [
                'description' => 'Enviar dedicatoria musical',
                'params' => ['de', 'para', 'mensaje', 'cancion_titulo', 'cancion_artista'],
            ],
            'mis_dedicatorias' => [
                'description' => 'Ver mis dedicatorias enviadas',
                'params' => [],
            ],
            'chat_mensajes' => [
                'description' => 'Ver mensajes del chat en vivo',
                'params' => ['emision_id', 'desde'],
            ],
            'enviar_chat' => [
                'description' => 'Enviar mensaje al chat',
                'params' => ['emision_id', 'mensaje'],
            ],
            'proponer_programa' => [
                'description' => 'Proponer un programa de radio',
                'params' => ['nombre', 'descripcion', 'categoria'],
            ],
            'podcasts' => [
                'description' => 'Ver podcasts disponibles',
                'params' => ['programa_id', 'limite'],
            ],
            'oyentes' => [
                'description' => 'Ver cantidad de oyentes',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'programas',
            'listado' => 'programas',
            'explorar' => 'programas',
            'buscar' => 'programas',
            'programacion' => 'programacion',
            'directo' => 'en_vivo',
            'stream' => 'en_vivo',
            'crear' => 'proponer_programa',
            'nuevo' => 'proponer_programa',
            'mis_items' => 'mis_dedicatorias',
            'mis-programas' => 'programas',
            'mensajes' => 'chat_mensajes',
            'enviar_mensaje' => 'enviar_chat',
            'dedicatorias' => 'mis_dedicatorias',
            'podcast' => 'podcasts',
            'stats' => 'oyentes',
            'foro' => 'foro_programa',
            'chat' => 'chat_programa',
            'multimedia' => 'multimedia_programa',
            'red-social' => 'red_social_programa',
            'red_social' => 'red_social_programa',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    private function action_en_vivo($params) {
        $request = new WP_REST_Request('GET');
        $response = $this->rest_programa_actual($request);
        $data = $response->get_data();

        $settings = $this->get_settings();
        $data['stream_url'] = $settings['url_stream'];
        $data['frecuencia_fm'] = $settings['frecuencia_fm'];

        return $data;
    }

    private function action_programacion($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('fecha', $params['fecha'] ?? date('Y-m-d'));
        $request->set_param('dias', $params['dias'] ?? 7);
        return $this->rest_programacion($request)->get_data();
    }

    private function action_programas($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('categoria', $params['categoria'] ?? '');
        $request->set_param('limite', $params['limite'] ?? 20);
        return $this->rest_programas($request)->get_data();
    }

    private function action_enviar_dedicatoria($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('de', $params['de'] ?? '');
        $request->set_param('para', $params['para'] ?? '');
        $request->set_param('mensaje', $params['mensaje'] ?? '');
        $request->set_param('cancion_titulo', $params['cancion_titulo'] ?? '');
        $request->set_param('cancion_artista', $params['cancion_artista'] ?? '');

        return $this->rest_enviar_dedicatoria($request)->get_data();
    }

    private function action_mis_dedicatorias($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }
        return $this->rest_mis_dedicatorias(new WP_REST_Request('GET'))->get_data();
    }

    private function action_chat_mensajes($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('emision_id', $params['emision_id'] ?? 0);
        $request->set_param('desde', $params['desde'] ?? 0);
        return $this->rest_chat_mensajes($request)->get_data();
    }

    private function action_enviar_chat($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('emision_id', $params['emision_id'] ?? 0);
        $request->set_param('mensaje', $params['mensaje'] ?? '');

        return $this->rest_chat_enviar($request)->get_data();
    }

    private function action_proponer_programa($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('nombre', $params['nombre'] ?? '');
        $request->set_param('descripcion', $params['descripcion'] ?? '');
        $request->set_param('categoria', $params['categoria'] ?? '');

        return $this->rest_proponer_programa($request)->get_data();
    }

    private function action_podcasts($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('programa_id', $params['programa_id'] ?? 0);
        $request->set_param('limite', $params['limite'] ?? 20);
        return $this->rest_podcasts($request)->get_data();
    }

    private function action_oyentes($params) {
        return [
            'success' => true,
            'oyentes' => $this->get_oyentes_actuales(),
        ];
    }

    private function resolve_contextual_programa($params = []) {
        global $wpdb;

        $programa_id = absint(
            $params['programa_id']
            ?? $params['id']
            ?? $_GET['programa_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$programa_id) {
            return null;
        }

        $tabla = $wpdb->prefix . 'flavor_radio_programas';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        $programa = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion FROM {$tabla} WHERE id = %d",
            $programa_id
        ), ARRAY_A);

        if (!$programa) {
            return null;
        }

        return [
            'id' => (int) $programa['id'],
            'titulo' => (string) $programa['nombre'],
            'descripcion' => (string) ($programa['descripcion'] ?? ''),
        ];
    }

    private function action_foro_programa($params) {
        $programa = $this->resolve_contextual_programa($params);
        if (!$programa) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un programa para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del programa', 'flavor-chat-ia') . '</h2>'
            . '<p>' . esc_html($programa['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="radio_programa" entidad_id="' . absint($programa['id']) . '"]')
            . '</div>';
    }

    private function action_chat_programa($params) {
        $programa = $this->resolve_contextual_programa($params);
        if (!$programa) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un programa para ver su chat.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este programa.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del programa', 'flavor-chat-ia') . '</h2><p>' . esc_html($programa['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?programa_id=' . absint($programa['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="radio_programa" entidad_id="' . absint($programa['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_programa($params) {
        $programa = $this->resolve_contextual_programa($params);
        if (!$programa) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un programa para ver sus archivos.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Multimedia del programa', 'flavor-chat-ia') . '</h2><p>' . esc_html($programa['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?programa_id=' . absint($programa['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="radio_programa" entidad_id="' . absint($programa['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_programa($params) {
        $programa = $this->resolve_contextual_programa($params);
        if (!$programa) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un programa para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este programa.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del programa', 'flavor-chat-ia') . '</h2><p>' . esc_html($programa['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?programa_id=' . absint($programa['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="radio_programa" entidad_id="' . absint($programa['id']) . '"]')
            . '</div>';
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Obtener oyentes actuales
     */
    private function get_oyentes_actuales() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_oyentes';

        // Oyentes con actividad en últimos 2 minutos
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla WHERE activo = 1 AND ultima_actividad > DATE_SUB(NOW(), INTERVAL 2 MINUTE)"
        );
    }

    /**
     * Calcular progreso de emisión
     */
    private function calcular_progreso($emision) {
        $inicio = strtotime($emision->fecha_hora_inicio);
        $fin = strtotime($emision->fecha_hora_fin);
        $ahora = current_time('timestamp');

        $duracion = $fin - $inicio;
        $transcurrido = $ahora - $inicio;

        if ($duracion <= 0) return 0;
        return min(100, max(0, round(($transcurrido / $duracion) * 100)));
    }

    /**
     * Formatear duración
     */
    private function format_duration($segundos) {
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;

        if ($horas > 0) {
            return sprintf('%d:%02d:%02d', $horas, $minutos, $segs);
        }
        return sprintf('%d:%02d', $minutos, $segs);
    }

    /**
     * Actualizar promedio de oyentes del programa
     */
    private function actualizar_promedio_programa($programa_id) {
        global $wpdb;
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $promedio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(oyentes_pico) FROM $tabla_emision WHERE programa_id = %d AND estado = 'finalizado'",
            $programa_id
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_emision WHERE programa_id = %d AND estado = 'finalizado'",
            $programa_id
        ));

        $wpdb->update($tabla_programas, [
            'oyentes_promedio' => round($promedio),
            'total_episodios' => $total,
        ], ['id' => $programa_id], ['%d', '%d'], ['%d']);
    }

    /**
     * Cron: Actualizar oyentes
     */
    public function cron_actualizar_oyentes() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_oyentes';

        // Marcar como inactivos los que no han reportado en 2 minutos
        $wpdb->query(
            "UPDATE $tabla SET activo = 0 WHERE activo = 1 AND ultima_actividad < DATE_SUB(NOW(), INTERVAL 2 MINUTE)"
        );

        // Actualizar pico de oyentes en emisión actual
        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $oyentes_actuales = $this->get_oyentes_actuales();

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_emision SET oyentes_pico = GREATEST(oyentes_pico, %d) WHERE estado = 'en_emision'",
            $oyentes_actuales
        ));
    }

    // =========================================================================
    // Admin & Assets
    // =========================================================================

    /**
     * Admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Radio', 'flavor-chat-ia'),
            __('Radio', 'flavor-chat-ia'),
            'manage_options',
            'flavor-radio',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $template = FLAVOR_CHAT_IA_PATH . 'includes/modules/radio/views/admin-dashboard.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Radio Comunitaria', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . __('Panel de administración de la radio.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Configuración del módulo para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'radio',
            'label' => __('Radio Comunitaria', 'flavor-chat-ia'),
            'icon' => 'dashicons-microphone',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'flavor-radio-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'flavor-radio-programas',
                    'titulo' => __('Programas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_programas'],
                ],
                [
                    'slug' => 'flavor-radio-emisiones',
                    'titulo' => __('Emisiones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_emisiones'],
                    'badge' => [$this, 'contar_emisiones_pendientes'],
                ],
            ],
        ];
    }

    /**
     * Renderiza la página de dashboard del panel unificado
     */
    public function render_pagina_dashboard() {
        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/radio/views/dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Renderiza la página de programas del panel unificado
     */
    public function render_pagina_programas() {
        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/radio/views/programas.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Renderiza la página de emisiones del panel unificado
     */
    public function render_pagina_emisiones() {
        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/radio/views/emisiones.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Cuenta las emisiones pendientes para mostrar badge
     *
     * @return int
     */
    public function contar_emisiones_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_emisiones = $wpdb->prefix . 'flavor_radio_emisiones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_emisiones)) {
            return 0;
        }

        $cantidad_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_emisiones WHERE estado = 'programado'"
        );

        return (int) $cantidad_pendientes;
    }

    /**
     * Verifica si se deben cargar los assets del modulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'flavor_radio_player',
            'flavor_radio_programacion',
            'flavor_radio_dedicatorias',
            'flavor_radio_chat',
            'flavor_radio_proponer',
            'flavor_radio_podcasts',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        if (!$this->can_activate()) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'flavor-radio-css',
            $base_url . 'css/radio-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-radio-js',
            $base_url . 'js/radio-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-radio-js', 'flavorRadio', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/radio/'),
            'nonce' => wp_create_nonce('flavor_radio_nonce'),
            'user_id' => get_current_user_id(),
            'strings' => [
                'play' => __('Reproducir', 'flavor-chat-ia'),
                'pause' => __('Pausar', 'flavor-chat-ia'),
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error de conexión', 'flavor-chat-ia'),
                'sin_emision' => __('Sin emisión', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-radio') === false) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('flavor-radio-admin-css', $base_url . 'css/radio-admin.css', [], $version);
        wp_enqueue_script('flavor-radio-admin-js', $base_url . 'js/radio-admin.js', ['jquery'], $version, true);

        wp_localize_script('flavor-radio-admin-js', 'flavorRadioAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_radio_admin'),
        ]);
    }

    /**
     * Dashboard tab
     */
    public function add_dashboard_tab($tabs) {
        $tabs['radio'] = [
            'label' => __('Radio', 'flavor-chat-ia'),
            'icon' => 'microphone',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 40,
        ];
        return $tabs;
    }

    /**
     * Render dashboard tab
     */
    public function render_dashboard_tab() {
        echo do_shortcode('[flavor_radio_player estilo="compacto"]');
        echo do_shortcode('[flavor_radio_dedicatorias]');
    }

    // =========================================================================
    // Web Components & Tools
    // =========================================================================

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_radio' => [
                'label' => __('Hero Radio', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Radio Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('La voz de tu barrio', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_reproductor' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'radio/hero',
            ],
            'reproductor_radio' => [
                'label' => __('Reproductor de Radio', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-controls-play',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('En Directo', 'flavor-chat-ia')],
                    'mostrar_programa_actual' => ['type' => 'toggle', 'default' => true],
                    'mostrar_oyentes' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['compacto', 'completo'], 'default' => 'completo'],
                ],
                'template' => 'radio/reproductor',
            ],
            'programacion' => [
                'label' => __('Parrilla de Programación', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Programación', 'flavor-chat-ia')],
                    'vista' => ['type' => 'select', 'options' => ['dia', 'semana'], 'default' => 'semana'],
                    'mostrar_descripcion' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'radio/programacion',
            ],
            'cta_locutor' => [
                'label' => __('CTA Ser Locutor', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Quieres Tener tu Programa?', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Comparte tu voz y contenido en nuestra radio comunitaria', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Proponer Programa', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#8b5cf6'],
                ],
                'template' => 'radio/cta-locutor',
            ],
            'podcasts_lista' => [
                'label' => __('Lista de Podcasts', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-playlist-audio',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Podcasts', 'flavor-chat-ia')],
                    'programa_id' => ['type' => 'number', 'default' => 0],
                    'limite' => ['type' => 'number', 'default' => 10],
                ],
                'template' => 'radio/podcasts',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'radio_en_vivo',
                'description' => 'Escuchar radio comunitaria en vivo y ver qué programa está al aire',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'radio_programacion',
                'description' => 'Ver la programación de la radio para los próximos días',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'dias' => ['type' => 'integer', 'description' => 'Número de días a mostrar'],
                    ],
                ],
            ],
            [
                'name' => 'radio_dedicatoria',
                'description' => 'Enviar dedicatoria musical a la radio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'de' => ['type' => 'string', 'description' => 'Tu nombre'],
                        'para' => ['type' => 'string', 'description' => 'Destinatario'],
                        'mensaje' => ['type' => 'string', 'description' => 'Mensaje de dedicatoria'],
                        'cancion' => ['type' => 'string', 'description' => 'Canción solicitada (opcional)'],
                    ],
                    'required' => ['de', 'para', 'mensaje'],
                ],
            ],
            [
                'name' => 'radio_podcasts',
                'description' => 'Buscar podcasts de programas de radio anteriores',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'programa' => ['type' => 'string', 'description' => 'Nombre del programa'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        $settings = $this->get_settings();

        return <<<KNOWLEDGE
**{$settings['nombre_radio']}**

{$settings['slogan']}

Emisora de radio del barrio gestionada por y para la comunidad.
{$settings['frecuencia_fm']}

**Funcionalidades:**
- Escucha en vivo desde la app, web o FM
- Programación variada: noticias, música, debates, cultura
- Envía dedicatorias musicales para tus seres queridos
- Chat en vivo con locutores durante las emisiones
- Participa como locutor con tu propio programa
- Escucha podcasts de programas anteriores

**Programación:**
- Programas en vivo con locutores comunitarios
- Bloques musicales de diferentes géneros
- Noticias y actualidad local
- Programas especiales de fin de semana

**Cómo participar:**
- Escucha en vivo y comenta en el chat
- Envía dedicatorias (máximo {$settings['max_dedicatorias_dia']} por día)
- Propón tu propio programa de radio
- Llama o escribe durante las emisiones
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo puedo tener mi programa?',
                'respuesta' => 'Envía tu propuesta desde la sección "Proponer Programa" con el nombre, descripción, horario preferido y temática. Nuestro equipo lo revisará y te contactará.',
            ],
            [
                'pregunta' => '¿Las dedicatorias son gratuitas?',
                'respuesta' => 'Sí, enviar dedicatorias es completamente gratuito para todos los vecinos registrados.',
            ],
            [
                'pregunta' => '¿Cuántas dedicatorias puedo enviar?',
                'respuesta' => 'Puedes enviar hasta 3 dedicatorias por día. Las dedicatorias son revisadas antes de emitirse.',
            ],
            [
                'pregunta' => '¿Puedo escuchar programas anteriores?',
                'respuesta' => 'Sí, los programas se guardan como podcasts. Puedes escucharlos en la sección de Podcasts.',
            ],
            [
                'pregunta' => '¿Cómo funciona el chat en vivo?',
                'respuesta' => 'Durante las emisiones puedes participar en el chat enviando mensajes que el locutor puede leer al aire.',
            ],
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('radio');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('radio');
        if (!$pagina && !get_option('flavor_radio_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['radio']);
            update_option('flavor_radio_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $tabla_programacion = $wpdb->prefix . 'flavor_radio_programacion';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
            return $estadisticas;
        }

        // Total de programas
        $total_programas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_programas} WHERE estado = 'activo'"
        );

        $estadisticas['programas'] = [
            'icon' => 'dashicons-controls-volumeon',
            'valor' => $total_programas,
            'label' => __('Programas', 'flavor-chat-ia'),
            'color' => 'purple',
        ];

        // Programa en vivo (si existe programación)
        if (Flavor_Chat_Helpers::tabla_existe($tabla_programacion)) {
            $en_vivo = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_programacion}
                 WHERE estado = 'en_emision'
                 OR (estado = 'programado' AND fecha_hora_inicio <= NOW() AND fecha_hora_fin >= NOW())"
            );

            if ($en_vivo > 0) {
                $estadisticas['en_vivo'] = [
                    'icon' => 'dashicons-microphone',
                    'valor' => __('EN VIVO', 'flavor-chat-ia'),
                    'label' => __('Ahora', 'flavor-chat-ia'),
                    'color' => 'red',
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Radio Comunitaria', 'flavor-chat-ia'),
                'slug' => 'radio',
                'content' => '<h1>' . __('Radio Comunitaria', 'flavor-chat-ia') . '</h1>
<p>' . __('Sintoniza nuestra radio comunitaria en vivo. Disfruta de programas variados, música, noticias locales y contenido creado por vecinos para vecinos.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="radio" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Programación', 'flavor-chat-ia'),
                'slug' => 'programacion',
                'content' => '<h1>' . __('Programación', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta la programación completa de nuestra radio. Conoce los horarios de emisión de todos los programas y no te pierdas tus favoritos.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="radio" action="programacion"]',
                'parent' => 'radio',
            ],
            [
                'title' => __('Mis Programas', 'flavor-chat-ia'),
                'slug' => 'mis-programas',
                'content' => '<h1>' . __('Mis Programas', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus programas de radio, revisa las estadísticas de audiencia y administra tus emisiones.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="radio" action="mis_items"]',
                'parent' => 'radio',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'radio',
            'title'    => __('Radio Comunitaria', 'flavor-chat-ia'),
            'subtitle' => __('Emisiones en vivo y podcasts de tu comunidad', 'flavor-chat-ia'),
            'icon'     => '📻',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_radio_programas',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'       => ['type' => 'text', 'label' => __('Nombre programa', 'flavor-chat-ia'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'categoria'    => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['musica', 'noticias', 'cultura', 'deportes', 'entretenimiento', 'educacion']],
                'horario'      => ['type' => 'text', 'label' => __('Horario', 'flavor-chat-ia')],
                'dia_emision'  => ['type' => 'select', 'label' => __('Día de emisión', 'flavor-chat-ia')],
                'conductor_id' => ['type' => 'select', 'label' => __('Conductor', 'flavor-chat-ia')],
                'imagen'       => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
            ],

            'estados' => [
                'en_vivo'     => ['label' => __('En vivo', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '🔴'],
                'programado'  => ['label' => __('Programado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '📅'],
                'pausado'     => ['label' => __('Pausado', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏸️'],
                'archivado'   => ['label' => __('Archivado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '🗄️'],
            ],

            'stats' => [
                'programas_activos' => ['label' => __('Programas', 'flavor-chat-ia'), 'icon' => '📻', 'color' => 'red'],
                'oyentes_actuales'  => ['label' => __('Oyentes ahora', 'flavor-chat-ia'), 'icon' => '🎧', 'color' => 'green'],
                'horas_emision'     => ['label' => __('Horas emitidas', 'flavor-chat-ia'), 'icon' => '⏱️', 'color' => 'blue'],
                'conductores'       => ['label' => __('Conductores', 'flavor-chat-ia'), 'icon' => '🎙️', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'programa-card',
                'title_field'  => 'nombre',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['horario', 'dia_emision'],
                'show_imagen'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'en-vivo' => [
                    'label'   => __('En vivo', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-controls-volumeon',
                    'content' => 'shortcode:radio_en_vivo',
                    'public'  => true,
                ],
                'programacion' => [
                    'label'   => __('Programación', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-calendar-alt',
                    'content' => 'shortcode:radio_programacion',
                    'public'  => true,
                ],
                'programas' => [
                    'label'   => __('Programas', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-playlist-audio',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'mis-programas' => [
                    'label'      => __('Mis programas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:radio_mis_programas',
                    'requires_login' => true,
                ],
                'foro' => [
                    'label'      => __('Foro', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-comments',
                    'content'    => 'action:foro_programa',
                    'hidden_nav' => true,
                ],
                'chat' => [
                    'label'      => __('Chat', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-format-chat',
                    'content'    => 'action:chat_programa',
                    'hidden_nav' => true,
                ],
                'multimedia' => [
                    'label'      => __('Multimedia', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-format-gallery',
                    'content'    => 'action:multimedia_programa',
                    'hidden_nav' => true,
                ],
                'red-social' => [
                    'label'      => __('Red social', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-share',
                    'content'    => 'action:red_social_programa',
                    'hidden_nav' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'nombre',
                'order'      => 'ASC',
                'filterable' => ['categoria', 'dia_emision'],
            ],

            'dashboard' => [
                'widgets' => ['player_en_vivo', 'programacion_hoy', 'programas_populares', 'stats'],
                'actions' => [
                    'escuchar' => ['label' => __('Escuchar en vivo', 'flavor-chat-ia'), 'icon' => '🔴', 'color' => 'red'],
                    'ver'      => ['label' => __('Ver programación', 'flavor-chat-ia'), 'icon' => '📅', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'streaming'     => true,
                'programacion'  => true,
                'podcast'       => true,
                'chat_en_vivo'  => true,
                'estadisticas'  => true,
            ],
        ];
    }

    /**
     * Inicializa los tabs extendidos del dashboard del usuario
     *
     * Carga la clase Flavor_Radio_Dashboard_Tab que proporciona tabs adicionales:
     * - radio-mis-programas: Programas favoritos del usuario
     * - radio-mis-dedicatorias: Dedicatorias enviadas por el usuario
     * - radio-mis-propuestas: Propuestas de contenido enviadas
     *
     * @return void
     */
    private function init_dashboard_tabs() {
        $dashboard_tab_file = FLAVOR_CHAT_IA_PATH . 'includes/modules/radio/class-radio-dashboard-tab.php';

        if (file_exists($dashboard_tab_file)) {
            require_once $dashboard_tab_file;

            if (class_exists('Flavor_Radio_Dashboard_Tab')) {
                Flavor_Radio_Dashboard_Tab::get_instance();
            }
        }
    }


    /**
     * Registra las páginas de administración del módulo
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(null, __('Emisiones', 'flavor-chat-ia'), __('Emisiones', 'flavor-chat-ia'), $capability, 'flavor-radio-emisiones', [$this, 'render_pagina_emisiones']);
        add_submenu_page(null, __('Programas', 'flavor-chat-ia'), __('Programas', 'flavor-chat-ia'), $capability, 'flavor-radio-programas', [$this, 'render_pagina_programas']);
    }

    public function render_pagina_emisiones() {
        $views_path = dirname(__FILE__) . '/views/emisiones.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Emisiones', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_programas() {
        $views_path = dirname(__FILE__) . '/views/programas.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Programas', 'flavor-chat-ia') . '</h1></div>'; }
    }

    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-radio-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Radio_Dashboard_Tab')) {
                Flavor_Radio_Dashboard_Tab::get_instance();
            }
        }
    }
}
