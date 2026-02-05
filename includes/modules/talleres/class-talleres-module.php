<?php
/**
 * Módulo de Talleres para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Talleres - Talleres prácticos comunitarios
 */
class Flavor_Chat_Talleres_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'talleres';
        $this->name = __('Talleres Prácticos', 'flavor-chat-ia');
        $this->description = __('Talleres prácticos y workshops organizados por y para la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        return Flavor_Chat_Helpers::tabla_existe($tabla_talleres);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Talleres no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion_organizadores' => false,
            'permite_talleres_gratuitos' => true,
            'permite_talleres_pago' => true,
            'comision_talleres_pago' => 10,
            'max_participantes_por_taller' => 20,
            'min_participantes_para_confirmar' => 5,
            'permite_lista_espera' => true,
            'dias_anticipacion_cancelacion' => 2,
            'categorias' => [
                'artesania' => 'Artesanía y manualidades',
                'cocina' => 'Cocina y conservas',
                'huerto' => 'Huerto urbano y jardinería',
                'tecnologia' => 'Tecnología y digital',
                'costura' => 'Costura y textil',
                'carpinteria' => 'Carpintería básica',
                'reparaciones' => 'Reparaciones domésticas',
                'reciclaje' => 'Reciclaje creativo',
                'idiomas' => 'Idiomas',
                'musica' => 'Música',
                'otros' => 'Otros',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers publicos
        add_action('wp_ajax_talleres_inscribirse', [$this, 'ajax_inscribirse']);
        add_action('wp_ajax_talleres_cancelar_inscripcion', [$this, 'ajax_cancelar_inscripcion']);
        add_action('wp_ajax_talleres_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_talleres_descargar_material', [$this, 'ajax_descargar_material']);
        add_action('wp_ajax_talleres_proponer', [$this, 'ajax_proponer_taller']);

        // AJAX handlers organizador
        add_action('wp_ajax_talleres_marcar_asistencia', [$this, 'ajax_marcar_asistencia']);
        add_action('wp_ajax_talleres_generar_certificado', [$this, 'ajax_generar_certificado']);
        add_action('wp_ajax_talleres_subir_material', [$this, 'ajax_subir_material']);

        // AJAX Admin
        add_action('wp_ajax_talleres_admin_guardar', [$this, 'ajax_admin_guardar_taller']);
        add_action('wp_ajax_talleres_admin_cambiar_estado', [$this, 'ajax_admin_cambiar_estado']);
        add_action('wp_ajax_talleres_admin_exportar', [$this, 'ajax_admin_exportar']);

        // WP Cron para recordatorios
        if (!wp_next_scheduled('talleres_enviar_recordatorios')) {
            wp_schedule_event(time(), 'daily', 'talleres_enviar_recordatorios');
        }
        add_action('talleres_enviar_recordatorios', [$this, 'enviar_recordatorios']);

        // Cron para confirmar talleres con minimo de participantes
        if (!wp_next_scheduled('talleres_confirmar_automatico')) {
            wp_schedule_event(time(), 'twicedaily', 'talleres_confirmar_automatico');
        }
        add_action('talleres_confirmar_automatico', [$this, 'confirmar_talleres_automatico']);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('proximos_talleres', [$this, 'shortcode_proximos_talleres']);
        add_shortcode('detalle_taller', [$this, 'shortcode_detalle_taller']);
        add_shortcode('mis_inscripciones_talleres', [$this, 'shortcode_mis_inscripciones']);
        add_shortcode('proponer_taller', [$this, 'shortcode_proponer_taller']);
        add_shortcode('calendario_talleres', [$this, 'shortcode_calendario']);
        add_shortcode('mis_talleres_organizador', [$this, 'shortcode_mis_talleres_organizador']);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Publicos
        register_rest_route($namespace, '/talleres', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_talleres'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_detalle_taller'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/talleres/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_categorias'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/talleres/calendario', [
            'methods' => 'GET',
            'callback' => [$this, 'api_calendario'],
            'permission_callback' => '__return_true',
        ]);

        // Requieren autenticacion
        register_rest_route($namespace, '/talleres/inscribirse', [
            'methods' => 'POST',
            'callback' => [$this, 'api_inscribirse'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/cancelar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_cancelar_inscripcion'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/mis-inscripciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_inscripciones'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)/valorar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_valorar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)/materiales', [
            'methods' => 'GET',
            'callback' => [$this, 'api_materiales'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)/certificado', [
            'methods' => 'GET',
            'callback' => [$this, 'api_certificado'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/proponer', [
            'methods' => 'POST',
            'callback' => [$this, 'api_proponer_taller'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Organizador
        register_rest_route($namespace, '/talleres/organizador/mis-talleres', [
            'methods' => 'GET',
            'callback' => [$this, 'api_organizador_mis_talleres'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)/asistencia', [
            'methods' => 'POST',
            'callback' => [$this, 'api_marcar_asistencia'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/talleres/(?P<id>\d+)/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    /**
     * Check si usuario esta logueado
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_asistencias = $wpdb->prefix . 'flavor_talleres_asistencias';
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';

        $sql_talleres = "CREATE TABLE IF NOT EXISTS $tabla_talleres (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organizador_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            descripcion_corta varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            tipo enum('puntual','serie') DEFAULT 'puntual',
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            duracion_horas decimal(5,2) NOT NULL,
            numero_sesiones int(11) DEFAULT 1,
            max_participantes int(11) DEFAULT 20,
            min_participantes int(11) DEFAULT 5,
            precio decimal(10,2) DEFAULT 0,
            es_gratuito tinyint(1) DEFAULT 1,
            materiales_incluidos tinyint(1) DEFAULT 0,
            materiales_necesarios text DEFAULT NULL,
            que_aprenderas text DEFAULT NULL,
            requisitos text DEFAULT NULL,
            imagen_portada varchar(500) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_detalle text DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            inscritos_actuales int(11) DEFAULT 0,
            lista_espera_count int(11) DEFAULT 0,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            destacado tinyint(1) DEFAULT 0,
            permite_certificado tinyint(1) DEFAULT 1,
            porcentaje_asistencia_certificado int(11) DEFAULT 80,
            estado enum('borrador','pendiente','publicado','confirmado','en_curso','finalizado','cancelado') DEFAULT 'borrador',
            motivo_cancelacion text DEFAULT NULL,
            fecha_limite_inscripcion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY organizador_id (organizador_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY destacado (destacado)
        ) $charset_collate;";

        $sql_sesiones = "CREATE TABLE IF NOT EXISTS $tabla_sesiones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            numero_sesion int(11) DEFAULT 1,
            titulo varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            fecha_hora datetime NOT NULL,
            duracion_minutos int(11) NOT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            notas_organizador text DEFAULT NULL,
            asistentes_confirmados int(11) DEFAULT 0,
            estado enum('programada','en_curso','finalizada','cancelada') DEFAULT 'programada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY taller_id (taller_id),
            KEY fecha_hora (fecha_hora),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            participante_id bigint(20) unsigned NOT NULL,
            nombre_completo varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            notas_participante text DEFAULT NULL,
            precio_pagado decimal(10,2) DEFAULT 0,
            metodo_pago varchar(50) DEFAULT NULL,
            transaccion_id varchar(100) DEFAULT NULL,
            estado_pago enum('pendiente','pagado','reembolsado') DEFAULT 'pendiente',
            lista_espera tinyint(1) DEFAULT 0,
            posicion_espera int(11) DEFAULT NULL,
            sesiones_asistidas int(11) DEFAULT 0,
            sesiones_totales int(11) DEFAULT 0,
            porcentaje_asistencia decimal(5,2) DEFAULT 0,
            certificado_emitido tinyint(1) DEFAULT 0,
            certificado_codigo varchar(100) DEFAULT NULL,
            certificado_fecha datetime DEFAULT NULL,
            estado enum('pendiente','confirmada','cancelada','completada','no_presentado') DEFAULT 'pendiente',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_cancelacion datetime DEFAULT NULL,
            motivo_cancelacion text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY taller_participante (taller_id, participante_id),
            KEY participante_id (participante_id),
            KEY estado (estado),
            KEY lista_espera (lista_espera)
        ) $charset_collate;";

        $sql_asistencias = "CREATE TABLE IF NOT EXISTS $tabla_asistencias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            inscripcion_id bigint(20) unsigned NOT NULL,
            sesion_id bigint(20) unsigned NOT NULL,
            asistio tinyint(1) DEFAULT 0,
            hora_llegada time DEFAULT NULL,
            notas text DEFAULT NULL,
            marcado_por bigint(20) unsigned DEFAULT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_sesion (inscripcion_id, sesion_id),
            KEY sesion_id (sesion_id)
        ) $charset_collate;";

        $sql_materiales = "CREATE TABLE IF NOT EXISTS $tabla_materiales (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            sesion_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('documento','imagen','video','enlace','otro') DEFAULT 'documento',
            archivo_url varchar(500) DEFAULT NULL,
            archivo_nombre varchar(255) DEFAULT NULL,
            archivo_tamano int(11) DEFAULT NULL,
            enlace_externo varchar(500) DEFAULT NULL,
            solo_inscritos tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            fecha_subida datetime DEFAULT CURRENT_TIMESTAMP,
            subido_por bigint(20) unsigned NOT NULL,
            PRIMARY KEY (id),
            KEY taller_id (taller_id),
            KEY sesion_id (sesion_id)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            inscripcion_id bigint(20) unsigned NOT NULL,
            participante_id bigint(20) unsigned NOT NULL,
            puntuacion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            aspectos_positivos text DEFAULT NULL,
            aspectos_mejorar text DEFAULT NULL,
            recomendaria tinyint(1) DEFAULT 1,
            visible tinyint(1) DEFAULT 1,
            respuesta_organizador text DEFAULT NULL,
            fecha_respuesta datetime DEFAULT NULL,
            fecha_valoracion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_id (inscripcion_id),
            KEY taller_id (taller_id),
            KEY participante_id (participante_id),
            KEY puntuacion (puntuacion)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_talleres);
        dbDelta($sql_sesiones);
        dbDelta($sql_inscripciones);
        dbDelta($sql_asistencias);
        dbDelta($sql_materiales);
        dbDelta($sql_valoraciones);
    }

    // =========================================================================
    // ACCIONES DEL MÓDULO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'talleres_disponibles' => [
                'description' => 'Listar talleres disponibles',
                'params' => ['categoria', 'fecha_desde', 'nivel'],
            ],
            'detalle_taller' => [
                'description' => 'Ver detalles del taller',
                'params' => ['taller_id'],
            ],
            'inscribirse' => [
                'description' => 'Inscribirse en taller',
                'params' => ['taller_id'],
            ],
            'cancelar_inscripcion' => [
                'description' => 'Cancelar inscripción',
                'params' => ['inscripcion_id', 'motivo'],
            ],
            'mis_talleres_inscritos' => [
                'description' => 'Talleres en los que estoy inscrito',
                'params' => ['estado'],
            ],
            'mis_talleres_organizador' => [
                'description' => 'Talleres que organizo',
                'params' => [],
            ],
            'marcar_asistencia' => [
                'description' => 'Marcar asistencia a sesión (organizador)',
                'params' => ['sesion_id', 'participante_id', 'asistio'],
            ],
            'valorar_taller' => [
                'description' => 'Valorar taller completado',
                'params' => ['taller_id', 'puntuacion', 'comentario'],
            ],
            'descargar_certificado' => [
                'description' => 'Descargar certificado de asistencia',
                'params' => ['taller_id'],
            ],
            'proponer_taller' => [
                'description' => 'Proponer nuevo taller',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'calendario_talleres' => [
                'description' => 'Ver calendario de talleres',
                'params' => ['mes', 'anio'],
            ],
            'estadisticas_taller' => [
                'description' => 'Estadísticas del taller (organizador)',
                'params' => ['taller_id'],
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
     * Acción: Talleres disponibles
     */
    private function action_talleres_disponibles($params) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';

        $where = ["t.estado IN ('publicado', 'confirmado')"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 't.categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['nivel'])) {
            $where[] = 't.nivel = %s';
            $prepare_values[] = sanitize_text_field($params['nivel']);
        }

        if (!empty($params['fecha_desde'])) {
            $where[] = 's.fecha_hora >= %s';
            $prepare_values[] = sanitize_text_field($params['fecha_desde']);
        } else {
            $where[] = 's.fecha_hora >= NOW()';
        }

        $sql = "SELECT t.*, MIN(s.fecha_hora) as proxima_sesion
                FROM $tabla_talleres t
                INNER JOIN $tabla_sesiones s ON t.id = s.taller_id
                WHERE " . implode(' AND ', $where) . "
                AND s.estado = 'programada'
                GROUP BY t.id
                ORDER BY proxima_sesion ASC
                LIMIT 50";

        if (!empty($prepare_values)) {
            $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $talleres = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'talleres' => array_map([$this, 'formatear_taller_lista'], $talleres),
        ];
    }

    /**
     * Acción: Detalle taller
     */
    private function action_detalle_taller($params) {
        if (empty($params['taller_id'])) {
            return ['success' => false, 'error' => 'ID de taller requerido'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d",
            intval($params['taller_id'])
        ));

        if (!$taller) {
            return ['success' => false, 'error' => 'Taller no encontrado'];
        }

        // Obtener sesiones
        $sesiones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_sesiones WHERE taller_id = %d ORDER BY numero_sesion ASC",
            $taller->id
        ));

        // Obtener valoraciones visibles
        $valoraciones = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name as autor_nombre
             FROM $tabla_valoraciones v
             INNER JOIN {$wpdb->users} u ON v.participante_id = u.ID
             WHERE v.taller_id = %d AND v.visible = 1
             ORDER BY v.fecha_valoracion DESC
             LIMIT 10",
            $taller->id
        ));

        // Verificar inscripcion del usuario actual
        $inscripcion = null;
        if (is_user_logged_in()) {
            $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
            $inscripcion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_inscripciones WHERE taller_id = %d AND participante_id = %d",
                $taller->id,
                get_current_user_id()
            ));
        }

        $organizador = get_userdata($taller->organizador_id);

        return [
            'success' => true,
            'taller' => [
                'id' => $taller->id,
                'titulo' => $taller->titulo,
                'descripcion' => $taller->descripcion,
                'descripcion_corta' => $taller->descripcion_corta,
                'organizador' => [
                    'id' => $taller->organizador_id,
                    'nombre' => $organizador ? $organizador->display_name : 'Organizador',
                    'avatar' => get_avatar_url($taller->organizador_id),
                ],
                'categoria' => $taller->categoria,
                'tipo' => $taller->tipo,
                'nivel' => $taller->nivel,
                'duracion_horas' => floatval($taller->duracion_horas),
                'numero_sesiones' => $taller->numero_sesiones,
                'max_participantes' => $taller->max_participantes,
                'min_participantes' => $taller->min_participantes,
                'precio' => floatval($taller->precio),
                'es_gratuito' => (bool)$taller->es_gratuito,
                'materiales_incluidos' => (bool)$taller->materiales_incluidos,
                'materiales_necesarios' => $taller->materiales_necesarios,
                'que_aprenderas' => $taller->que_aprenderas ? json_decode($taller->que_aprenderas, true) : [],
                'requisitos' => $taller->requisitos,
                'imagen' => $taller->imagen_portada,
                'ubicacion' => $taller->ubicacion,
                'ubicacion_detalle' => $taller->ubicacion_detalle,
                'coordenadas' => $taller->ubicacion_lat ? [
                    'lat' => floatval($taller->ubicacion_lat),
                    'lng' => floatval($taller->ubicacion_lng),
                ] : null,
                'inscritos' => $taller->inscritos_actuales,
                'plazas_disponibles' => max(0, $taller->max_participantes - $taller->inscritos_actuales),
                'lista_espera' => $taller->lista_espera_count,
                'valoracion' => floatval($taller->valoracion_media),
                'num_valoraciones' => $taller->numero_valoraciones,
                'permite_certificado' => (bool)$taller->permite_certificado,
                'estado' => $taller->estado,
                'fecha_limite_inscripcion' => $taller->fecha_limite_inscripcion,
            ],
            'sesiones' => array_map(function($s) {
                return [
                    'id' => $s->id,
                    'numero' => $s->numero_sesion,
                    'titulo' => $s->titulo,
                    'descripcion' => $s->descripcion,
                    'fecha_hora' => $s->fecha_hora,
                    'fecha_formateada' => date_i18n('l, j F Y - H:i', strtotime($s->fecha_hora)),
                    'duracion_minutos' => $s->duracion_minutos,
                    'ubicacion' => $s->ubicacion,
                    'estado' => $s->estado,
                ];
            }, $sesiones),
            'valoraciones' => array_map(function($v) {
                return [
                    'autor' => $v->autor_nombre,
                    'puntuacion' => $v->puntuacion,
                    'comentario' => $v->comentario,
                    'fecha' => date_i18n('j F Y', strtotime($v->fecha_valoracion)),
                    'respuesta' => $v->respuesta_organizador,
                ];
            }, $valoraciones),
            'inscripcion' => $inscripcion ? [
                'id' => $inscripcion->id,
                'estado' => $inscripcion->estado,
                'lista_espera' => (bool)$inscripcion->lista_espera,
                'posicion_espera' => $inscripcion->posicion_espera,
                'sesiones_asistidas' => $inscripcion->sesiones_asistidas,
                'porcentaje_asistencia' => floatval($inscripcion->porcentaje_asistencia),
                'certificado' => (bool)$inscripcion->certificado_emitido,
                'fecha' => $inscripcion->fecha_inscripcion,
            ] : null,
        ];
    }

    /**
     * Acción: Inscribirse
     */
    private function action_inscribirse($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['taller_id'])) {
            return ['success' => false, 'error' => 'ID de taller requerido'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $usuario_id = get_current_user_id();
        $taller_id = intval($params['taller_id']);

        // Verificar que el taller existe y esta abierto
        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d AND estado IN ('publicado', 'confirmado')",
            $taller_id
        ));

        if (!$taller) {
            return ['success' => false, 'error' => 'Taller no disponible'];
        }

        // Verificar fecha limite de inscripcion
        if ($taller->fecha_limite_inscripcion && strtotime($taller->fecha_limite_inscripcion) < time()) {
            return ['success' => false, 'error' => 'El plazo de inscripción ha finalizado'];
        }

        // Verificar que no este ya inscrito
        $ya_inscrito = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE taller_id = %d AND participante_id = %d AND estado != 'cancelada'",
            $taller_id,
            $usuario_id
        ));

        if ($ya_inscrito) {
            return ['success' => false, 'error' => 'Ya estás inscrito en este taller'];
        }

        // Verificar plazas
        $lista_espera = false;
        $posicion_espera = null;
        $settings = $this->get_settings();

        if ($taller->inscritos_actuales >= $taller->max_participantes) {
            if ($settings['permite_lista_espera']) {
                $lista_espera = true;
                $posicion_espera = $taller->lista_espera_count + 1;
            } else {
                return ['success' => false, 'error' => 'No hay plazas disponibles'];
            }
        }

        // Contar sesiones del taller
        $total_sesiones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_sesiones WHERE taller_id = %d",
            $taller_id
        ));

        // Datos del usuario
        $usuario = get_userdata($usuario_id);

        // Determinar estado
        $estado_inscripcion = 'confirmada';
        if (!$taller->es_gratuito && $taller->precio > 0) {
            $estado_inscripcion = 'pendiente';
        }
        if ($lista_espera) {
            $estado_inscripcion = 'pendiente';
        }

        // Crear inscripcion
        $resultado = $wpdb->insert($tabla_inscripciones, [
            'taller_id' => $taller_id,
            'participante_id' => $usuario_id,
            'nombre_completo' => isset($params['nombre']) ? sanitize_text_field($params['nombre']) : $usuario->display_name,
            'email' => isset($params['email']) ? sanitize_email($params['email']) : $usuario->user_email,
            'telefono' => isset($params['telefono']) ? sanitize_text_field($params['telefono']) : '',
            'notas_participante' => isset($params['notas']) ? sanitize_textarea_field($params['notas']) : '',
            'precio_pagado' => $taller->es_gratuito ? 0 : $taller->precio,
            'estado_pago' => $taller->es_gratuito ? 'pagado' : 'pendiente',
            'lista_espera' => $lista_espera ? 1 : 0,
            'posicion_espera' => $posicion_espera,
            'sesiones_totales' => $total_sesiones,
            'estado' => $estado_inscripcion,
            'fecha_inscripcion' => current_time('mysql'),
            'fecha_confirmacion' => $estado_inscripcion === 'confirmada' ? current_time('mysql') : null,
        ]);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al inscribirse'];
        }

        // Actualizar contadores
        if ($lista_espera) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_talleres SET lista_espera_count = lista_espera_count + 1 WHERE id = %d",
                $taller_id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_talleres SET inscritos_actuales = inscritos_actuales + 1 WHERE id = %d",
                $taller_id
            ));
        }

        // Enviar notificacion
        $this->enviar_notificacion_inscripcion($usuario_id, $taller, $lista_espera);

        $mensaje = $lista_espera
            ? sprintf('Te has apuntado a la lista de espera (posición %d)', $posicion_espera)
            : '¡Te has inscrito correctamente!';

        if ($estado_inscripcion === 'pendiente' && !$lista_espera) {
            $mensaje .= ' Pendiente de pago.';
        }

        return [
            'success' => true,
            'mensaje' => $mensaje,
            'inscripcion_id' => $wpdb->insert_id,
            'lista_espera' => $lista_espera,
            'posicion_espera' => $posicion_espera,
        ];
    }

    /**
     * Acción: Cancelar inscripcion
     */
    private function action_cancelar_inscripcion($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['inscripcion_id'])) {
            return ['success' => false, 'error' => 'ID de inscripción requerido'];
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $usuario_id = get_current_user_id();
        $inscripcion_id = intval($params['inscripcion_id']);

        // Verificar inscripcion
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, t.titulo, t.organizador_id
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_talleres t ON i.taller_id = t.id
             WHERE i.id = %d AND i.participante_id = %d AND i.estado NOT IN ('cancelada', 'completada')",
            $inscripcion_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => 'Inscripción no encontrada o no cancelable'];
        }

        // Verificar anticipacion de cancelacion
        $settings = $this->get_settings();
        $primera_sesion = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(fecha_hora) FROM $tabla_sesiones WHERE taller_id = %d AND estado = 'programada'",
            $inscripcion->taller_id
        ));

        if ($primera_sesion) {
            $dias_hasta_sesion = (strtotime($primera_sesion) - time()) / (60 * 60 * 24);
            if ($dias_hasta_sesion < $settings['dias_anticipacion_cancelacion']) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'No puedes cancelar con menos de %d días de anticipación',
                        $settings['dias_anticipacion_cancelacion']
                    ),
                ];
            }
        }

        // Cancelar inscripcion
        $wpdb->update(
            $tabla_inscripciones,
            [
                'estado' => 'cancelada',
                'fecha_cancelacion' => current_time('mysql'),
                'motivo_cancelacion' => isset($params['motivo']) ? sanitize_textarea_field($params['motivo']) : null,
            ],
            ['id' => $inscripcion_id]
        );

        // Actualizar contadores
        if ($inscripcion->lista_espera) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_talleres SET lista_espera_count = GREATEST(0, lista_espera_count - 1) WHERE id = %d",
                $inscripcion->taller_id
            ));
            // Reordenar posiciones de lista de espera
            $this->reordenar_lista_espera($inscripcion->taller_id);
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_talleres SET inscritos_actuales = GREATEST(0, inscritos_actuales - 1) WHERE id = %d",
                $inscripcion->taller_id
            ));
            // Promover de lista de espera si hay
            $this->promover_lista_espera($inscripcion->taller_id);
        }

        return [
            'success' => true,
            'mensaje' => 'Inscripción cancelada correctamente',
        ];
    }

    /**
     * Acción: Mis talleres inscritos
     */
    private function action_mis_talleres_inscritos($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $usuario_id = get_current_user_id();

        $where_estado = '';
        if (!empty($params['estado'])) {
            $where_estado = $wpdb->prepare(" AND i.estado = %s", sanitize_text_field($params['estado']));
        }

        $inscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, t.titulo, t.imagen_portada, t.categoria, t.ubicacion,
                    t.organizador_id, t.estado as estado_taller,
                    (SELECT MIN(fecha_hora) FROM $tabla_sesiones WHERE taller_id = t.id AND estado = 'programada') as proxima_sesion
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_talleres t ON i.taller_id = t.id
             WHERE i.participante_id = %d $where_estado
             ORDER BY i.fecha_inscripcion DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'inscripciones' => array_map(function($i) {
                $organizador = get_userdata($i->organizador_id);
                return [
                    'id' => $i->id,
                    'taller_id' => $i->taller_id,
                    'titulo' => $i->titulo,
                    'imagen' => $i->imagen_portada,
                    'categoria' => $i->categoria,
                    'ubicacion' => $i->ubicacion,
                    'organizador' => $organizador ? $organizador->display_name : 'Organizador',
                    'estado_inscripcion' => $i->estado,
                    'estado_taller' => $i->estado_taller,
                    'lista_espera' => (bool)$i->lista_espera,
                    'posicion_espera' => $i->posicion_espera,
                    'sesiones_asistidas' => $i->sesiones_asistidas,
                    'sesiones_totales' => $i->sesiones_totales,
                    'porcentaje_asistencia' => floatval($i->porcentaje_asistencia),
                    'certificado' => (bool)$i->certificado_emitido,
                    'proxima_sesion' => $i->proxima_sesion ? date_i18n('j M Y H:i', strtotime($i->proxima_sesion)) : null,
                    'fecha_inscripcion' => $i->fecha_inscripcion,
                ];
            }, $inscripciones),
        ];
    }

    /**
     * Acción: Valorar taller
     */
    private function action_valorar_taller($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['taller_id']) || !isset($params['puntuacion'])) {
            return ['success' => false, 'error' => 'Datos incompletos'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';
        $usuario_id = get_current_user_id();
        $taller_id = intval($params['taller_id']);
        $puntuacion = max(1, min(5, intval($params['puntuacion'])));

        // Verificar inscripcion completada
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE taller_id = %d AND participante_id = %d AND estado = 'completada'",
            $taller_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => 'Debes completar el taller para valorarlo'];
        }

        // Verificar si ya valoro
        $ya_valoro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_valoraciones WHERE inscripcion_id = %d",
            $inscripcion->id
        ));

        if ($ya_valoro) {
            return ['success' => false, 'error' => 'Ya has valorado este taller'];
        }

        // Guardar valoracion
        $wpdb->insert($tabla_valoraciones, [
            'taller_id' => $taller_id,
            'inscripcion_id' => $inscripcion->id,
            'participante_id' => $usuario_id,
            'puntuacion' => $puntuacion,
            'comentario' => isset($params['comentario']) ? sanitize_textarea_field($params['comentario']) : '',
            'aspectos_positivos' => isset($params['positivos']) ? sanitize_textarea_field($params['positivos']) : '',
            'aspectos_mejorar' => isset($params['mejorar']) ? sanitize_textarea_field($params['mejorar']) : '',
            'recomendaria' => isset($params['recomendaria']) ? intval($params['recomendaria']) : 1,
            'fecha_valoracion' => current_time('mysql'),
        ]);

        // Recalcular media
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(puntuacion) as media, COUNT(*) as total
             FROM $tabla_valoraciones WHERE taller_id = %d",
            $taller_id
        ));

        $wpdb->update(
            $tabla_talleres,
            [
                'valoracion_media' => $stats->media,
                'numero_valoraciones' => $stats->total,
            ],
            ['id' => $taller_id]
        );

        return [
            'success' => true,
            'mensaje' => '¡Gracias por tu valoración!',
        ];
    }

    /**
     * Acción: Marcar asistencia
     */
    private function action_marcar_asistencia($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['sesion_id']) || empty($params['participante_id'])) {
            return ['success' => false, 'error' => 'Datos incompletos'];
        }

        global $wpdb;
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_asistencias = $wpdb->prefix . 'flavor_talleres_asistencias';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();
        $sesion_id = intval($params['sesion_id']);
        $participante_id = intval($params['participante_id']);
        $asistio = isset($params['asistio']) ? intval($params['asistio']) : 1;

        // Verificar que es el organizador
        $sesion = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, t.organizador_id
             FROM $tabla_sesiones s
             INNER JOIN $tabla_talleres t ON s.taller_id = t.id
             WHERE s.id = %d",
            $sesion_id
        ));

        if (!$sesion || ($sesion->organizador_id != $usuario_id && !current_user_can('manage_options'))) {
            return ['success' => false, 'error' => 'No tienes permiso para marcar asistencia'];
        }

        // Obtener inscripcion
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE taller_id = %d AND participante_id = %d AND estado = 'confirmada'",
            $sesion->taller_id,
            $participante_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => 'Participante no inscrito'];
        }

        // Insertar o actualizar asistencia
        $asistencia_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_asistencias WHERE inscripcion_id = %d AND sesion_id = %d",
            $inscripcion->id,
            $sesion_id
        ));

        if ($asistencia_existente) {
            $wpdb->update(
                $tabla_asistencias,
                [
                    'asistio' => $asistio,
                    'marcado_por' => $usuario_id,
                    'fecha_registro' => current_time('mysql'),
                ],
                ['id' => $asistencia_existente]
            );
        } else {
            $wpdb->insert($tabla_asistencias, [
                'inscripcion_id' => $inscripcion->id,
                'sesion_id' => $sesion_id,
                'asistio' => $asistio,
                'hora_llegada' => $asistio ? current_time('H:i:s') : null,
                'marcado_por' => $usuario_id,
                'fecha_registro' => current_time('mysql'),
            ]);
        }

        // Actualizar contadores de asistencia
        $this->actualizar_asistencia_inscripcion($inscripcion->id);

        // Actualizar contador de sesion
        $asistentes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_asistencias WHERE sesion_id = %d AND asistio = 1",
            $sesion_id
        ));

        $wpdb->update($tabla_sesiones, ['asistentes_confirmados' => $asistentes], ['id' => $sesion_id]);

        return [
            'success' => true,
            'mensaje' => $asistio ? 'Asistencia confirmada' : 'Ausencia registrada',
        ];
    }

    /**
     * Acción: Calendario
     */
    private function action_calendario_talleres($params) {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';

        $mes = isset($params['mes']) ? intval($params['mes']) : intval(date('m'));
        $anio = isset($params['anio']) ? intval($params['anio']) : intval(date('Y'));

        $fecha_inicio = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $fecha_fin = date('Y-m-t 23:59:59', strtotime($fecha_inicio));

        $sesiones = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.titulo, t.categoria, t.imagen_portada, t.ubicacion, t.estado as estado_taller
             FROM $tabla_sesiones s
             INNER JOIN $tabla_talleres t ON s.taller_id = t.id
             WHERE s.fecha_hora BETWEEN %s AND %s
             AND t.estado IN ('publicado', 'confirmado', 'en_curso')
             ORDER BY s.fecha_hora ASC",
            $fecha_inicio,
            $fecha_fin
        ));

        $eventos = [];
        foreach ($sesiones as $sesion) {
            $fecha = date('Y-m-d', strtotime($sesion->fecha_hora));
            if (!isset($eventos[$fecha])) {
                $eventos[$fecha] = [];
            }
            $eventos[$fecha][] = [
                'sesion_id' => $sesion->id,
                'taller_id' => $sesion->taller_id,
                'titulo' => $sesion->titulo ?: $sesion->titulo,
                'hora' => date('H:i', strtotime($sesion->fecha_hora)),
                'duracion' => $sesion->duracion_minutos,
                'categoria' => $sesion->categoria,
                'ubicacion' => $sesion->ubicacion,
                'estado' => $sesion->estado,
            ];
        }

        return [
            'success' => true,
            'mes' => $mes,
            'anio' => $anio,
            'eventos' => $eventos,
        ];
    }

    /**
     * Acción: Proponer taller
     */
    private function action_proponer_taller($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['titulo']) || empty($params['descripcion'])) {
            return ['success' => false, 'error' => 'Título y descripción son requeridos'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        $titulo = sanitize_text_field($params['titulo']);
        $slug = sanitize_title($titulo) . '-' . time();

        $datos = [
            'organizador_id' => $usuario_id,
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => wp_kses_post($params['descripcion']),
            'descripcion_corta' => isset($params['descripcion_corta']) ? sanitize_textarea_field($params['descripcion_corta']) : '',
            'categoria' => isset($params['categoria']) ? sanitize_text_field($params['categoria']) : 'otros',
            'tipo' => isset($params['tipo']) ? sanitize_text_field($params['tipo']) : 'puntual',
            'nivel' => isset($params['nivel']) ? sanitize_text_field($params['nivel']) : 'todos',
            'duracion_horas' => isset($params['duracion_horas']) ? floatval($params['duracion_horas']) : 2,
            'numero_sesiones' => isset($params['numero_sesiones']) ? intval($params['numero_sesiones']) : 1,
            'max_participantes' => isset($params['max_participantes']) ? intval($params['max_participantes']) : 15,
            'min_participantes' => isset($params['min_participantes']) ? intval($params['min_participantes']) : 5,
            'precio' => isset($params['precio']) ? floatval($params['precio']) : 0,
            'es_gratuito' => isset($params['precio']) && floatval($params['precio']) > 0 ? 0 : 1,
            'materiales_incluidos' => isset($params['materiales_incluidos']) ? intval($params['materiales_incluidos']) : 0,
            'materiales_necesarios' => isset($params['materiales_necesarios']) ? sanitize_textarea_field($params['materiales_necesarios']) : '',
            'ubicacion' => isset($params['ubicacion']) ? sanitize_text_field($params['ubicacion']) : '',
            'estado' => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($tabla_talleres, $datos);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al crear la propuesta'];
        }

        $taller_id = $wpdb->insert_id;

        // Notificar a administradores
        $this->notificar_nueva_propuesta($taller_id, $titulo);

        return [
            'success' => true,
            'mensaje' => 'Propuesta enviada correctamente. Será revisada por los coordinadores.',
            'taller_id' => $taller_id,
        ];
    }

    /**
     * Acción: Descargar certificado
     */
    private function action_descargar_certificado($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['taller_id'])) {
            return ['success' => false, 'error' => 'ID de taller requerido'];
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();
        $taller_id = intval($params['taller_id']);

        // Verificar inscripcion y certificado
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, t.titulo, t.duracion_horas, t.permite_certificado, t.porcentaje_asistencia_certificado
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_talleres t ON i.taller_id = t.id
             WHERE i.taller_id = %d AND i.participante_id = %d AND i.estado = 'completada'",
            $taller_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => 'Debes completar el taller para obtener el certificado'];
        }

        if (!$inscripcion->permite_certificado) {
            return ['success' => false, 'error' => 'Este taller no emite certificados'];
        }

        if ($inscripcion->porcentaje_asistencia < $inscripcion->porcentaje_asistencia_certificado) {
            return [
                'success' => false,
                'error' => sprintf(
                    'Necesitas al menos %d%% de asistencia. Tu asistencia: %d%%',
                    $inscripcion->porcentaje_asistencia_certificado,
                    $inscripcion->porcentaje_asistencia
                ),
            ];
        }

        // Generar certificado si no existe
        if (!$inscripcion->certificado_emitido) {
            $codigo = 'CERT-TAL-' . strtoupper(wp_generate_password(8, false));
            $wpdb->update(
                $tabla_inscripciones,
                [
                    'certificado_emitido' => 1,
                    'certificado_codigo' => $codigo,
                    'certificado_fecha' => current_time('mysql'),
                ],
                ['id' => $inscripcion->id]
            );
            $inscripcion->certificado_codigo = $codigo;
            $inscripcion->certificado_fecha = current_time('mysql');
        }

        $usuario = get_userdata($usuario_id);

        return [
            'success' => true,
            'certificado' => [
                'codigo' => $inscripcion->certificado_codigo,
                'titulo_taller' => $inscripcion->titulo,
                'participante' => $usuario->display_name,
                'duracion_horas' => floatval($inscripcion->duracion_horas),
                'asistencia' => floatval($inscripcion->porcentaje_asistencia),
                'fecha_emision' => $inscripcion->certificado_fecha,
            ],
        ];
    }

    /**
     * Acción: Estadisticas del taller
     */
    private function action_estadisticas_taller($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['taller_id'])) {
            return ['success' => false, 'error' => 'ID de taller requerido'];
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';
        $usuario_id = get_current_user_id();
        $taller_id = intval($params['taller_id']);

        // Verificar que es el organizador
        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d AND organizador_id = %d",
            $taller_id,
            $usuario_id
        ));

        if (!$taller && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => 'No tienes permiso para ver estas estadísticas'];
        }

        if (!$taller) {
            $taller = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_talleres WHERE id = %d", $taller_id));
        }

        // Estadisticas de inscripciones
        $stats_inscripciones = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN lista_espera = 1 THEN 1 ELSE 0 END) as lista_espera,
                AVG(porcentaje_asistencia) as asistencia_media
             FROM $tabla_inscripciones WHERE taller_id = %d",
            $taller_id
        ));

        // Estadisticas de valoraciones
        $stats_valoraciones = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                AVG(puntuacion) as media,
                SUM(CASE WHEN recomendaria = 1 THEN 1 ELSE 0 END) as recomendarian
             FROM $tabla_valoraciones WHERE taller_id = %d",
            $taller_id
        ));

        // Sesiones
        $sesiones = $wpdb->get_results($wpdb->prepare(
            "SELECT numero_sesion, fecha_hora, asistentes_confirmados, estado
             FROM $tabla_sesiones WHERE taller_id = %d ORDER BY numero_sesion",
            $taller_id
        ));

        return [
            'success' => true,
            'estadisticas' => [
                'inscritos_totales' => intval($stats_inscripciones->total),
                'confirmados' => intval($stats_inscripciones->confirmadas),
                'completados' => intval($stats_inscripciones->completadas),
                'cancelados' => intval($stats_inscripciones->canceladas),
                'lista_espera' => intval($stats_inscripciones->lista_espera),
                'asistencia_media' => round(floatval($stats_inscripciones->asistencia_media), 1),
                'ocupacion' => $taller->max_participantes > 0
                    ? round(($taller->inscritos_actuales / $taller->max_participantes) * 100, 1)
                    : 0,
                'valoraciones_total' => intval($stats_valoraciones->total),
                'valoracion_media' => round(floatval($stats_valoraciones->media), 2),
                'porcentaje_recomendacion' => $stats_valoraciones->total > 0
                    ? round(($stats_valoraciones->recomendarian / $stats_valoraciones->total) * 100, 1)
                    : 0,
                'ingresos' => $taller->es_gratuito ? 0 : ($stats_inscripciones->confirmadas * $taller->precio),
            ],
            'sesiones' => array_map(function($s) {
                return [
                    'numero' => $s->numero_sesion,
                    'fecha' => date_i18n('j M Y H:i', strtotime($s->fecha_hora)),
                    'asistentes' => $s->asistentes_confirmados,
                    'estado' => $s->estado,
                ];
            }, $sesiones),
        ];
    }

    // =========================================================================
    // API REST
    // =========================================================================

    public function api_listar_talleres($request) {
        return rest_ensure_response($this->action_talleres_disponibles([
            'categoria' => $request->get_param('categoria'),
            'nivel' => $request->get_param('nivel'),
            'fecha_desde' => $request->get_param('fecha_desde'),
        ]));
    }

    public function api_detalle_taller($request) {
        return rest_ensure_response($this->action_detalle_taller([
            'taller_id' => $request->get_param('id'),
        ]));
    }

    public function api_categorias($request) {
        $settings = $this->get_settings();
        return rest_ensure_response([
            'success' => true,
            'categorias' => $settings['categorias'] ?? [],
        ]);
    }

    public function api_calendario($request) {
        return rest_ensure_response($this->action_calendario_talleres([
            'mes' => $request->get_param('mes'),
            'anio' => $request->get_param('anio'),
        ]));
    }

    public function api_inscribirse($request) {
        return rest_ensure_response($this->action_inscribirse([
            'taller_id' => $request->get_param('taller_id'),
            'nombre' => $request->get_param('nombre'),
            'email' => $request->get_param('email'),
            'telefono' => $request->get_param('telefono'),
            'notas' => $request->get_param('notas'),
        ]));
    }

    public function api_cancelar_inscripcion($request) {
        return rest_ensure_response($this->action_cancelar_inscripcion([
            'inscripcion_id' => $request->get_param('inscripcion_id'),
            'motivo' => $request->get_param('motivo'),
        ]));
    }

    public function api_mis_inscripciones($request) {
        return rest_ensure_response($this->action_mis_talleres_inscritos([
            'estado' => $request->get_param('estado'),
        ]));
    }

    public function api_valorar($request) {
        return rest_ensure_response($this->action_valorar_taller([
            'taller_id' => $request->get_param('id'),
            'puntuacion' => $request->get_param('puntuacion'),
            'comentario' => $request->get_param('comentario'),
            'positivos' => $request->get_param('positivos'),
            'mejorar' => $request->get_param('mejorar'),
            'recomendaria' => $request->get_param('recomendaria'),
        ]));
    }

    public function api_materiales($request) {
        if (!is_user_logged_in()) {
            return rest_ensure_response(['success' => false, 'error' => 'Debes iniciar sesión']);
        }

        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $taller_id = intval($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Verificar inscripcion
        $inscrito = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones
             WHERE taller_id = %d AND participante_id = %d AND estado IN ('confirmada', 'completada')",
            $taller_id,
            $usuario_id
        ));

        $where_solo_inscritos = $inscrito ? '' : 'AND solo_inscritos = 0';

        $materiales = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_materiales WHERE taller_id = %d $where_solo_inscritos ORDER BY orden, fecha_subida",
            $taller_id
        ));

        return rest_ensure_response([
            'success' => true,
            'materiales' => array_map(function($m) {
                return [
                    'id' => $m->id,
                    'titulo' => $m->titulo,
                    'descripcion' => $m->descripcion,
                    'tipo' => $m->tipo,
                    'archivo_url' => $m->archivo_url,
                    'archivo_nombre' => $m->archivo_nombre,
                    'tamano' => $m->archivo_tamano ? size_format($m->archivo_tamano) : null,
                    'enlace' => $m->enlace_externo,
                    'descargas' => $m->descargas,
                ];
            }, $materiales),
        ]);
    }

    public function api_certificado($request) {
        return rest_ensure_response($this->action_descargar_certificado([
            'taller_id' => $request->get_param('id'),
        ]));
    }

    public function api_proponer_taller($request) {
        return rest_ensure_response($this->action_proponer_taller($request->get_params()));
    }

    public function api_organizador_mis_talleres($request) {
        if (!is_user_logged_in()) {
            return rest_ensure_response(['success' => false, 'error' => 'Debes iniciar sesión']);
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $usuario_id = get_current_user_id();

        $talleres = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE organizador_id = %d ORDER BY fecha_creacion DESC",
            $usuario_id
        ));

        return rest_ensure_response([
            'success' => true,
            'talleres' => array_map(function($t) {
                return [
                    'id' => $t->id,
                    'titulo' => $t->titulo,
                    'categoria' => $t->categoria,
                    'estado' => $t->estado,
                    'inscritos' => $t->inscritos_actuales,
                    'max_participantes' => $t->max_participantes,
                    'valoracion' => floatval($t->valoracion_media),
                    'fecha_creacion' => $t->fecha_creacion,
                ];
            }, $talleres),
        ]);
    }

    public function api_marcar_asistencia($request) {
        return rest_ensure_response($this->action_marcar_asistencia([
            'sesion_id' => $request->get_param('sesion_id'),
            'participante_id' => $request->get_param('participante_id'),
            'asistio' => $request->get_param('asistio'),
        ]));
    }

    public function api_estadisticas($request) {
        return rest_ensure_response($this->action_estadisticas_taller([
            'taller_id' => $request->get_param('id'),
        ]));
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    public function ajax_inscribirse() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_inscribirse([
            'taller_id' => isset($_POST['taller_id']) ? intval($_POST['taller_id']) : 0,
            'nombre' => isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'telefono' => isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '',
            'notas' => isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '',
        ]));
    }

    public function ajax_cancelar_inscripcion() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_cancelar_inscripcion([
            'inscripcion_id' => isset($_POST['inscripcion_id']) ? intval($_POST['inscripcion_id']) : 0,
            'motivo' => isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '',
        ]));
    }

    public function ajax_valorar() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_valorar_taller([
            'taller_id' => isset($_POST['taller_id']) ? intval($_POST['taller_id']) : 0,
            'puntuacion' => isset($_POST['puntuacion']) ? intval($_POST['puntuacion']) : 0,
            'comentario' => isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '',
        ]));
    }

    public function ajax_descargar_material() {
        check_ajax_referer('talleres_nonce', 'nonce');

        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;

        if (!$material_id) {
            wp_send_json(['success' => false, 'error' => 'Material no especificado']);
        }

        // Incrementar contador de descargas
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_materiales SET descargas = descargas + 1 WHERE id = %d",
            $material_id
        ));

        $material = $wpdb->get_row($wpdb->prepare(
            "SELECT archivo_url FROM $tabla_materiales WHERE id = %d",
            $material_id
        ));

        wp_send_json([
            'success' => true,
            'url' => $material ? $material->archivo_url : '',
        ]);
    }

    public function ajax_proponer_taller() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_proponer_taller($_POST));
    }

    public function ajax_marcar_asistencia() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_marcar_asistencia([
            'sesion_id' => isset($_POST['sesion_id']) ? intval($_POST['sesion_id']) : 0,
            'participante_id' => isset($_POST['participante_id']) ? intval($_POST['participante_id']) : 0,
            'asistio' => isset($_POST['asistio']) ? intval($_POST['asistio']) : 1,
        ]));
    }

    public function ajax_generar_certificado() {
        check_ajax_referer('talleres_nonce', 'nonce');
        wp_send_json($this->action_descargar_certificado([
            'taller_id' => isset($_POST['taller_id']) ? intval($_POST['taller_id']) : 0,
        ]));
    }

    public function ajax_subir_material() {
        check_ajax_referer('talleres_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'error' => 'Debes iniciar sesión']);
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $usuario_id = get_current_user_id();
        $taller_id = isset($_POST['taller_id']) ? intval($_POST['taller_id']) : 0;

        // Verificar que es organizador
        $es_organizador = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_talleres WHERE id = %d AND organizador_id = %d",
            $taller_id,
            $usuario_id
        ));

        if (!$es_organizador && !current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'No tienes permiso']);
        }

        // Manejar subida de archivo
        if (!empty($_FILES['archivo'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $archivo_id = media_handle_upload('archivo', 0);
            if (is_wp_error($archivo_id)) {
                wp_send_json(['success' => false, 'error' => $archivo_id->get_error_message()]);
            }

            $archivo_url = wp_get_attachment_url($archivo_id);
            $archivo_path = get_attached_file($archivo_id);
            $archivo_nombre = basename($archivo_path);
            $archivo_tamano = filesize($archivo_path);
        }

        $wpdb->insert($tabla_materiales, [
            'taller_id' => $taller_id,
            'sesion_id' => isset($_POST['sesion_id']) ? intval($_POST['sesion_id']) : null,
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'documento'),
            'archivo_url' => $archivo_url ?? null,
            'archivo_nombre' => $archivo_nombre ?? null,
            'archivo_tamano' => $archivo_tamano ?? null,
            'enlace_externo' => isset($_POST['enlace']) ? esc_url_raw($_POST['enlace']) : null,
            'solo_inscritos' => isset($_POST['solo_inscritos']) ? intval($_POST['solo_inscritos']) : 1,
            'subido_por' => $usuario_id,
        ]);

        wp_send_json([
            'success' => true,
            'mensaje' => 'Material subido correctamente',
            'material_id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_admin_guardar_taller() {
        check_ajax_referer('talleres_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $taller_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');

        if (empty($titulo)) {
            wp_send_json(['success' => false, 'error' => 'El título es requerido']);
        }

        $datos = [
            'titulo' => $titulo,
            'slug' => sanitize_title($titulo) . ($taller_id ? '' : '-' . time()),
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'descripcion_corta' => sanitize_textarea_field($_POST['descripcion_corta'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'puntual'),
            'nivel' => sanitize_text_field($_POST['nivel'] ?? 'todos'),
            'duracion_horas' => floatval($_POST['duracion_horas'] ?? 2),
            'numero_sesiones' => intval($_POST['numero_sesiones'] ?? 1),
            'max_participantes' => intval($_POST['max_participantes'] ?? 20),
            'min_participantes' => intval($_POST['min_participantes'] ?? 5),
            'precio' => floatval($_POST['precio'] ?? 0),
            'es_gratuito' => isset($_POST['es_gratuito']) ? 1 : (floatval($_POST['precio'] ?? 0) <= 0 ? 1 : 0),
            'materiales_incluidos' => isset($_POST['materiales_incluidos']) ? 1 : 0,
            'materiales_necesarios' => sanitize_textarea_field($_POST['materiales_necesarios'] ?? ''),
            'imagen_portada' => esc_url_raw($_POST['imagen_portada'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'ubicacion_detalle' => sanitize_textarea_field($_POST['ubicacion_detalle'] ?? ''),
        ];

        if ($taller_id > 0) {
            $wpdb->update($tabla_talleres, $datos, ['id' => $taller_id]);
            $mensaje = 'Taller actualizado';
        } else {
            $datos['organizador_id'] = get_current_user_id();
            $datos['estado'] = 'borrador';
            $wpdb->insert($tabla_talleres, $datos);
            $taller_id = $wpdb->insert_id;
            $mensaje = 'Taller creado';
        }

        wp_send_json([
            'success' => true,
            'mensaje' => $mensaje,
            'taller_id' => $taller_id,
        ]);
    }

    public function ajax_admin_cambiar_estado() {
        check_ajax_referer('talleres_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $taller_id = intval($_POST['taller_id'] ?? 0);
        $estado = sanitize_text_field($_POST['estado'] ?? '');

        $estados_validos = ['borrador', 'pendiente', 'publicado', 'confirmado', 'en_curso', 'finalizado', 'cancelado'];

        if (!in_array($estado, $estados_validos)) {
            wp_send_json(['success' => false, 'error' => 'Estado inválido']);
        }

        $wpdb->update($tabla_talleres, ['estado' => $estado], ['id' => $taller_id]);

        wp_send_json([
            'success' => true,
            'mensaje' => 'Estado actualizado',
        ]);
    }

    public function ajax_admin_exportar() {
        check_ajax_referer('talleres_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $taller_id = intval($_POST['taller_id'] ?? 0);

        $inscritos = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, u.user_email, u.display_name
             FROM $tabla_inscripciones i
             INNER JOIN {$wpdb->users} u ON i.participante_id = u.ID
             WHERE i.taller_id = %d
             ORDER BY i.fecha_inscripcion DESC",
            $taller_id
        ));

        $csv_data = "Nombre,Email,Teléfono,Estado,Asistencia,Lista Espera,Certificado,Fecha Inscripción\n";
        foreach ($inscritos as $inscrito) {
            $csv_data .= sprintf(
                '"%s","%s","%s","%s","%s%%","%s","%s","%s"' . "\n",
                $inscrito->nombre_completo ?: $inscrito->display_name,
                $inscrito->email ?: $inscrito->user_email,
                $inscrito->telefono,
                $inscrito->estado,
                $inscrito->porcentaje_asistencia,
                $inscrito->lista_espera ? 'Sí' : 'No',
                $inscrito->certificado_emitido ? 'Sí' : 'No',
                $inscrito->fecha_inscripcion
            );
        }

        wp_send_json([
            'success' => true,
            'csv' => $csv_data,
            'filename' => 'participantes-taller-' . $taller_id . '.csv',
        ]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_proximos_talleres($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'nivel' => '',
            'limite' => 12,
            'columnas' => 3,
        ], $atts);

        $this->enqueue_frontend_assets();

        $resultado = $this->action_talleres_disponibles([
            'categoria' => $atts['categoria'],
            'nivel' => $atts['nivel'],
        ]);

        $talleres = $resultado['success'] ? array_slice($resultado['talleres'], 0, $atts['limite']) : [];

        ob_start();
        include $this->get_module_path() . 'templates/proximos-talleres.php';
        return ob_get_clean();
    }

    public function shortcode_detalle_taller($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);

        $taller_id = $atts['id'] ?: (isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0);

        if (!$taller_id) {
            return '<p class="talleres-error">Taller no especificado</p>';
        }

        $this->enqueue_frontend_assets();

        $resultado = $this->action_detalle_taller(['taller_id' => $taller_id]);

        if (!$resultado['success']) {
            return '<p class="talleres-error">' . esc_html($resultado['error']) . '</p>';
        }

        $taller = $resultado['taller'];
        $sesiones = $resultado['sesiones'];
        $valoraciones = $resultado['valoraciones'];
        $inscripcion = $resultado['inscripcion'];

        ob_start();
        include $this->get_module_path() . 'templates/detalle-taller.php';
        return ob_get_clean();
    }

    public function shortcode_mis_inscripciones($atts) {
        if (!is_user_logged_in()) {
            return '<p class="talleres-login-required">Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para ver tus inscripciones.</p>';
        }

        $this->enqueue_frontend_assets();

        $resultado = $this->action_mis_talleres_inscritos([]);

        ob_start();
        include $this->get_module_path() . 'templates/mis-inscripciones.php';
        return ob_get_clean();
    }

    public function shortcode_proponer_taller($atts) {
        if (!is_user_logged_in()) {
            return '<p class="talleres-login-required">Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para proponer un taller.</p>';
        }

        $this->enqueue_frontend_assets();
        $settings = $this->get_settings();

        ob_start();
        include $this->get_module_path() . 'templates/proponer-taller.php';
        return ob_get_clean();
    }

    public function shortcode_calendario($atts) {
        $atts = shortcode_atts([
            'mes' => date('m'),
            'anio' => date('Y'),
        ], $atts);

        $this->enqueue_frontend_assets();

        ob_start();
        include $this->get_module_path() . 'templates/calendario.php';
        return ob_get_clean();
    }

    public function shortcode_mis_talleres_organizador($atts) {
        if (!is_user_logged_in()) {
            return '<p class="talleres-login-required">Debes iniciar sesión para acceder.</p>';
        }

        $this->enqueue_frontend_assets();

        ob_start();
        include $this->get_module_path() . 'templates/organizador-panel.php';
        return ob_get_clean();
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    private function enqueue_frontend_assets() {
        wp_enqueue_style('talleres-frontend', $this->get_module_url() . 'assets/css/talleres.css', [], '1.0.0');
        wp_enqueue_script('talleres-frontend', $this->get_module_url() . 'assets/js/talleres.js', ['jquery'], '1.0.0', true);
        wp_localize_script('talleres-frontend', 'talleresData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('talleres_nonce'),
            'rest_url' => rest_url('flavor/v1/talleres'),
            'i18n' => [
                'confirmar_inscripcion' => __('¿Confirmar inscripción?', 'flavor-chat-ia'),
                'confirmar_cancelacion' => __('¿Estás seguro de cancelar tu inscripción?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
            ],
        ]);
    }

    private function formatear_taller_lista($taller) {
        $organizador = get_userdata($taller->organizador_id);
        return [
            'id' => $taller->id,
            'titulo' => $taller->titulo,
            'descripcion' => wp_trim_words($taller->descripcion, 25),
            'organizador' => $organizador ? $organizador->display_name : 'Organizador',
            'categoria' => $taller->categoria,
            'nivel' => $taller->nivel,
            'duracion_horas' => floatval($taller->duracion_horas),
            'precio' => floatval($taller->precio),
            'es_gratuito' => (bool)$taller->es_gratuito,
            'plazas_disponibles' => max(0, $taller->max_participantes - $taller->inscritos_actuales),
            'inscritos' => $taller->inscritos_actuales,
            'max_participantes' => $taller->max_participantes,
            'proxima_sesion' => isset($taller->proxima_sesion)
                ? date_i18n('j M Y H:i', strtotime($taller->proxima_sesion))
                : null,
            'ubicacion' => $taller->ubicacion,
            'valoracion' => floatval($taller->valoracion_media),
            'imagen' => $taller->imagen_portada,
        ];
    }

    private function actualizar_asistencia_inscripcion($inscripcion_id) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_asistencias = $wpdb->prefix . 'flavor_talleres_asistencias';

        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT sesiones_totales FROM $tabla_inscripciones WHERE id = %d",
            $inscripcion_id
        ));

        $asistencias = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_asistencias WHERE inscripcion_id = %d AND asistio = 1",
            $inscripcion_id
        ));

        $porcentaje = $inscripcion->sesiones_totales > 0
            ? ($asistencias / $inscripcion->sesiones_totales) * 100
            : 0;

        $wpdb->update(
            $tabla_inscripciones,
            [
                'sesiones_asistidas' => $asistencias,
                'porcentaje_asistencia' => $porcentaje,
            ],
            ['id' => $inscripcion_id]
        );
    }

    private function reordenar_lista_espera($taller_id) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $lista = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones
             WHERE taller_id = %d AND lista_espera = 1 AND estado != 'cancelada'
             ORDER BY fecha_inscripcion ASC",
            $taller_id
        ));

        $posicion = 1;
        foreach ($lista as $inscripcion) {
            $wpdb->update(
                $tabla_inscripciones,
                ['posicion_espera' => $posicion],
                ['id' => $inscripcion->id]
            );
            $posicion++;
        }
    }

    private function promover_lista_espera($taller_id) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d",
            $taller_id
        ));

        if ($taller->inscritos_actuales >= $taller->max_participantes) {
            return;
        }

        $siguiente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE taller_id = %d AND lista_espera = 1 AND estado = 'pendiente'
             ORDER BY posicion_espera ASC LIMIT 1",
            $taller_id
        ));

        if ($siguiente) {
            $wpdb->update(
                $tabla_inscripciones,
                [
                    'lista_espera' => 0,
                    'posicion_espera' => null,
                    'estado' => 'confirmada',
                    'fecha_confirmacion' => current_time('mysql'),
                ],
                ['id' => $siguiente->id]
            );

            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_talleres
                 SET inscritos_actuales = inscritos_actuales + 1,
                     lista_espera_count = GREATEST(0, lista_espera_count - 1)
                 WHERE id = %d",
                $taller_id
            ));

            $this->reordenar_lista_espera($taller_id);
            $this->notificar_promocion_lista_espera($siguiente->participante_id, $taller);
        }
    }

    private function enviar_notificacion_inscripcion($usuario_id, $taller, $lista_espera = false) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) return;

        if ($lista_espera) {
            $asunto = sprintf(__('Te has apuntado a la lista de espera - %s', 'flavor-chat-ia'), $taller->titulo);
            $mensaje = sprintf(
                __('Hola %s,

Te has apuntado a la lista de espera del taller "%s".

Te notificaremos si se libera alguna plaza.

¡Gracias por tu interés!', 'flavor-chat-ia'),
                $usuario->display_name,
                $taller->titulo
            );
        } else {
            $asunto = sprintf(__('¡Inscripción confirmada! - %s', 'flavor-chat-ia'), $taller->titulo);
            $mensaje = sprintf(
                __('Hola %s,

Tu inscripción al taller "%s" ha sido confirmada.

Ubicación: %s

Consulta los detalles del taller en tu panel de usuario.

¡Te esperamos!', 'flavor-chat-ia'),
                $usuario->display_name,
                $taller->titulo,
                $taller->ubicacion
            );
        }

        wp_mail($usuario->user_email, $asunto, $mensaje);
    }

    private function notificar_promocion_lista_espera($usuario_id, $taller) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) return;

        $asunto = sprintf(__('¡Plaza disponible! - %s', 'flavor-chat-ia'), $taller->titulo);
        $mensaje = sprintf(
            __('Hola %s,

¡Buenas noticias! Se ha liberado una plaza en el taller "%s" y tu inscripción ha sido confirmada.

Consulta los detalles en tu panel de usuario.

¡Te esperamos!', 'flavor-chat-ia'),
            $usuario->display_name,
            $taller->titulo
        );

        wp_mail($usuario->user_email, $asunto, $mensaje);
    }

    private function notificar_nueva_propuesta($taller_id, $titulo) {
        $admin_email = get_option('admin_email');
        $asunto = sprintf(__('Nueva propuesta de taller: %s', 'flavor-chat-ia'), $titulo);
        $mensaje = sprintf(
            __('Se ha recibido una nueva propuesta de taller:

Título: %s
ID: %d

Revísala en el panel de administración.', 'flavor-chat-ia'),
            $titulo,
            $taller_id
        );

        wp_mail($admin_email, $asunto, $mensaje);
    }

    public function enviar_recordatorios() {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        // Recordar sesiones de mañana
        $manana_inicio = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $manana_fin = date('Y-m-d 23:59:59', strtotime('+1 day'));

        $sesiones_manana = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.titulo, t.ubicacion
             FROM $tabla_sesiones s
             INNER JOIN $tabla_talleres t ON s.taller_id = t.id
             WHERE s.fecha_hora BETWEEN %s AND %s
             AND s.estado = 'programada'",
            $manana_inicio,
            $manana_fin
        ));

        foreach ($sesiones_manana as $sesion) {
            $inscritos = $wpdb->get_results($wpdb->prepare(
                "SELECT i.participante_id, u.user_email, u.display_name
                 FROM $tabla_inscripciones i
                 INNER JOIN {$wpdb->users} u ON i.participante_id = u.ID
                 WHERE i.taller_id = %d AND i.estado = 'confirmada'",
                $sesion->taller_id
            ));

            foreach ($inscritos as $inscrito) {
                $asunto = sprintf(__('Recordatorio: Mañana tienes taller - %s', 'flavor-chat-ia'), $sesion->titulo);
                $mensaje = sprintf(
                    __('Hola %s,

Te recordamos que mañana tienes el taller "%s".

Fecha y hora: %s
Ubicación: %s

¡Te esperamos!', 'flavor-chat-ia'),
                    $inscrito->display_name,
                    $sesion->titulo,
                    date_i18n('l j F Y - H:i', strtotime($sesion->fecha_hora)),
                    $sesion->ubicacion
                );

                wp_mail($inscrito->user_email, $asunto, $mensaje);
            }
        }
    }

    public function confirmar_talleres_automatico() {
        global $wpdb;
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        // Confirmar talleres publicados que han alcanzado el mínimo de participantes
        $wpdb->query(
            "UPDATE $tabla_talleres
             SET estado = 'confirmado'
             WHERE estado = 'publicado'
             AND inscritos_actuales >= min_participantes"
        );
    }

    private function get_module_url() {
        return plugin_dir_url(__FILE__);
    }

    private function get_module_path() {
        return plugin_dir_path(__FILE__);
    }

    // =========================================================================
    // FORM CONFIG
    // =========================================================================

    public function get_form_config($action_name) {
        $configs = [
            'inscribirse' => [
                'title' => __('Inscribirse en Taller', 'flavor-chat-ia'),
                'description' => __('Completa el formulario para inscribirte', 'flavor-chat-ia'),
                'fields' => [
                    'taller_id' => ['type' => 'hidden', 'required' => true],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                    ],
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Notas o requisitos especiales', 'flavor-chat-ia'),
                        'rows' => 3,
                    ],
                ],
                'submit_text' => __('Confirmar Inscripción', 'flavor-chat-ia'),
            ],
            'proponer_taller' => [
                'title' => __('Proponer Taller', 'flavor-chat-ia'),
                'description' => __('Comparte tu conocimiento organizando un taller', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del taller', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => $this->get_settings()['categorias'],
                    ],
                    'nivel' => [
                        'type' => 'select',
                        'label' => __('Nivel', 'flavor-chat-ia'),
                        'options' => [
                            'todos' => __('Todos los niveles', 'flavor-chat-ia'),
                            'principiante' => __('Principiante', 'flavor-chat-ia'),
                            'intermedio' => __('Intermedio', 'flavor-chat-ia'),
                            'avanzado' => __('Avanzado', 'flavor-chat-ia'),
                        ],
                    ],
                    'duracion_horas' => [
                        'type' => 'number',
                        'label' => __('Duración (horas)', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                        'max' => 8,
                        'default' => 2,
                    ],
                    'max_participantes' => [
                        'type' => 'number',
                        'label' => __('Máximo participantes', 'flavor-chat-ia'),
                        'min' => 3,
                        'max' => 50,
                        'default' => 15,
                    ],
                    'precio' => [
                        'type' => 'number',
                        'label' => __('Precio (€)', 'flavor-chat-ia'),
                        'step' => '0.01',
                        'min' => 0,
                        'default' => 0,
                        'description' => __('0 para gratuito', 'flavor-chat-ia'),
                    ],
                    'ubicacion' => [
                        'type' => 'text',
                        'label' => __('Ubicación', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                ],
                'submit_text' => __('Enviar Propuesta', 'flavor-chat-ia'),
            ],
            'valorar_taller' => [
                'title' => __('Valorar Taller', 'flavor-chat-ia'),
                'fields' => [
                    'taller_id' => ['type' => 'hidden', 'required' => true],
                    'puntuacion' => [
                        'type' => 'number',
                        'label' => __('Puntuación', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                        'max' => 5,
                    ],
                    'comentario' => [
                        'type' => 'textarea',
                        'label' => __('Comentario', 'flavor-chat-ia'),
                        'rows' => 4,
                    ],
                ],
                'submit_text' => __('Enviar Valoración', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    // =========================================================================
    // WEB COMPONENTS
    // =========================================================================

    public function get_web_components() {
        return [
            'hero_talleres' => [
                'label' => __('Hero Talleres', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Talleres Prácticos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Aprende nuevas habilidades con tu comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/hero',
            ],
            'talleres_grid' => [
                'label' => __('Grid de Talleres', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Próximos Talleres', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_instructor' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/grid',
            ],
            'categorias_talleres' => [
                'label' => __('Categorías de Talleres', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Explora por Categoría', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'carrusel'], 'default' => 'grid'],
                    'mostrar_contador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'talleres/categorias',
            ],
            'calendario_talleres' => [
                'label' => __('Calendario de Talleres', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Calendario', 'flavor-chat-ia')],
                    'vista_inicial' => ['type' => 'select', 'options' => ['mes', 'semana', 'lista'], 'default' => 'mes'],
                ],
                'template' => 'talleres/calendario',
            ],
            'cta_organizador' => [
                'label' => __('CTA Ser Organizador', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Comparte tu Conocimiento', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Organiza tu propio taller', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Proponer Taller', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#3b82f6'],
                ],
                'template' => 'talleres/cta-organizador',
            ],
        ];
    }

    // =========================================================================
    // TOOL DEFINITIONS & KNOWLEDGE
    // =========================================================================

    public function get_tool_definitions() {
        return [
            [
                'name' => 'talleres_disponibles',
                'description' => 'Ver talleres disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría del taller'],
                        'nivel' => ['type' => 'string', 'description' => 'Nivel del taller'],
                    ],
                ],
            ],
            [
                'name' => 'talleres_detalle',
                'description' => 'Ver detalles de un taller',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'taller_id' => ['type' => 'integer', 'description' => 'ID del taller'],
                    ],
                    'required' => ['taller_id'],
                ],
            ],
            [
                'name' => 'talleres_inscribirse',
                'description' => 'Inscribirse en un taller',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'taller_id' => ['type' => 'integer', 'description' => 'ID del taller'],
                    ],
                    'required' => ['taller_id'],
                ],
            ],
        ];
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Talleres Prácticos Comunitarios**

Aprende haciendo en talleres organizados por vecinos expertos.

**Categorías de talleres:**
- Artesanía y manualidades
- Cocina y conservas
- Huerto urbano y jardinería
- Tecnología y digital
- Costura y textil
- Carpintería básica
- Reparaciones domésticas
- Reciclaje creativo

**Tipos de talleres:**
- Puntuales: Una sesión única
- Serie: Varias sesiones consecutivas

**Qué incluyen:**
- Instrucción práctica
- Materiales (según taller)
- Espacio comunitario
- Grupo reducido
- Certificado de asistencia

**Control de asistencia:**
- Se registra asistencia por sesión
- Certificado requiere mínimo 80% asistencia
- Lista de espera automática

**Organiza tu taller:**
1. Propón tu tema y fecha
2. Define materiales y precio
3. Espera aprobación
4. ¡Comparte tu conocimiento!

**Ventajas:**
- Aprendizaje práctico
- Grupos pequeños
- Precios accesibles
- Conoce a tus vecinos
- Desarrolla habilidades útiles
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si el taller se cancela?',
                'respuesta' => 'Se cancela si no hay mínimo de participantes. Se reembolsa el 100%.',
            ],
            [
                'pregunta' => '¿Están incluidos los materiales?',
                'respuesta' => 'Depende del taller. En la descripción se indica qué está incluido.',
            ],
            [
                'pregunta' => '¿Puedo organizar un taller?',
                'respuesta' => 'Sí, cualquier vecino puede proponer un taller. Se revisa antes de publicar.',
            ],
            [
                'pregunta' => '¿Cómo funciona la lista de espera?',
                'respuesta' => 'Si no hay plazas, te apuntas a la lista. Si alguien cancela, pasas automáticamente.',
            ],
            [
                'pregunta' => '¿Puedo cancelar mi inscripción?',
                'respuesta' => 'Sí, con al menos 2 días de anticipación antes de la primera sesión.',
            ],
            [
                'pregunta' => '¿Cómo obtengo el certificado?',
                'respuesta' => 'Al completar el taller con al menos 80% de asistencia, puedes descargarlo.',
            ],
        ];
    }
}
