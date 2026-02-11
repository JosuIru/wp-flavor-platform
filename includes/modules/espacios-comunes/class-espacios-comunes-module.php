<?php
/**
 * Módulo de Espacios Comunes para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Espacios Comunes - Reserva de espacios comunitarios
 */
class Flavor_Chat_Espacios_Comunes_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'espacios_comunes';
        $this->name = __('Espacios Comunes', 'flavor-chat-ia');
        $this->description = __('Sistema de reserva y gestión de espacios comunes y equipamientos de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_espacios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Espacios Comunes no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_fianza' => true,
            'importe_fianza_predeterminado' => 50,
            'horas_anticipacion_minima' => 24,
            'dias_anticipacion_maxima' => 90,
            'horas_anticipacion_cancelacion' => 24,
            'permite_reservas_recurrentes' => true,
            'duracion_maxima_horas' => 8,
            'notificar_administrador' => true,
            'auto_confirmar_reservas' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_espacios_crear_reserva', [$this, 'ajax_crear_reserva']);
        add_action('wp_ajax_espacios_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_espacios_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_espacios_disponibilidad', [$this, 'ajax_disponibilidad']);
        add_action('wp_ajax_nopriv_espacios_disponibilidad', [$this, 'ajax_disponibilidad']);
        add_action('wp_ajax_espacios_reportar_incidencia', [$this, 'ajax_reportar_incidencia']);

        // Admin AJAX
        add_action('wp_ajax_espacios_confirmar_reserva', [$this, 'ajax_confirmar_reserva']);
        add_action('wp_ajax_espacios_rechazar_reserva', [$this, 'ajax_rechazar_reserva']);
        add_action('wp_ajax_espacios_devolver_fianza', [$this, 'ajax_devolver_fianza']);

        // WP Cron para actualizar estado de reservas
        add_action('flavor_espacios_actualizar_estados', [$this, 'actualizar_estados_reservas']);
        if (!wp_next_scheduled('flavor_espacios_actualizar_estados')) {
            wp_schedule_event(time(), 'hourly', 'flavor_espacios_actualizar_estados');
        }

        // WP Cron para recordatorios
        add_action('flavor_espacios_recordatorios', [$this, 'enviar_recordatorios']);
        if (!wp_next_scheduled('flavor_espacios_recordatorios')) {
            wp_schedule_event(time(), 'daily', 'flavor_espacios_recordatorios');
        }
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('espacios_listado', [$this, 'shortcode_listado']);
        add_shortcode('espacios_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('espacios_mis_reservas', [$this, 'shortcode_mis_reservas']);
        add_shortcode('espacios_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('espacios_equipamiento', [$this, 'shortcode_equipamiento']);
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/espacios', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_espacios'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/espacios/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_detalle_espacio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/espacios/(?P<id>\d+)/disponibilidad', [
            'methods' => 'GET',
            'callback' => [$this, 'api_disponibilidad'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/espacios/reservas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_reserva'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/espacios/reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_reservas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/espacios/reservas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'api_cancelar_reserva'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/espacios/reservas/(?P<id>\d+)/valorar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_valorar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/espacios/equipamiento', [
            'methods' => 'GET',
            'callback' => [$this, 'api_equipamiento'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/espacios/incidencias', [
            'methods' => 'POST',
            'callback' => [$this, 'api_reportar_incidencia'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/espacios/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route('flavor/v1', '/espacios/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_tipos_espacios'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Check user logged in
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Check admin
     */
    public function check_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';
        $tabla_incidencias = $wpdb->prefix . 'flavor_espacios_incidencias';

        $sql_espacios = "CREATE TABLE IF NOT EXISTS $tabla_espacios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo enum('salon_eventos','sala_reuniones','cocina','taller','terraza','jardin','gimnasio','ludoteca','piscina','parking','otro') DEFAULT 'salon_eventos',
            ubicacion varchar(500) NOT NULL,
            latitud decimal(10,7) DEFAULT NULL,
            longitud decimal(10,7) DEFAULT NULL,
            capacidad_personas int(11) NOT NULL,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            equipamiento text DEFAULT NULL COMMENT 'JSON',
            normas_uso text DEFAULT NULL,
            precio_hora decimal(10,2) DEFAULT 0,
            precio_dia decimal(10,2) DEFAULT 0,
            requiere_fianza tinyint(1) DEFAULT 1,
            importe_fianza decimal(10,2) DEFAULT 50,
            horario_apertura time DEFAULT '08:00:00',
            horario_cierre time DEFAULT '22:00:00',
            dias_disponibles varchar(50) DEFAULT 'L,M,X,J,V,S,D',
            fotos text DEFAULT NULL COMMENT 'JSON array URLs',
            responsable_id bigint(20) unsigned DEFAULT NULL,
            instrucciones_acceso text DEFAULT NULL,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            estado enum('disponible','mantenimiento','inactivo') DEFAULT 'disponible',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            num_asistentes int(11) DEFAULT NULL,
            motivo varchar(500) DEFAULT NULL,
            tipo_evento varchar(100) DEFAULT NULL,
            equipamiento_adicional text DEFAULT NULL COMMENT 'JSON',
            precio_total decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            instrucciones_especiales text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            estado enum('solicitada','confirmada','en_curso','finalizada','cancelada','rechazada') DEFAULT 'solicitada',
            motivo_rechazo text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_cancelacion datetime DEFAULT NULL,
            recordatorio_enviado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY fecha_inicio (fecha_inicio),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_equipamiento = "CREATE TABLE IF NOT EXISTS $tabla_equipamiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria enum('mobiliario','audiovisual','cocina','deportivo','herramientas','juegos','limpieza','otro') DEFAULT 'otro',
            cantidad int(11) DEFAULT 1,
            ubicacion_predeterminada bigint(20) unsigned DEFAULT NULL COMMENT 'espacio_id',
            requiere_reserva tinyint(1) DEFAULT 0,
            precio_reserva decimal(10,2) DEFAULT 0,
            instrucciones_uso text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('disponible','reservado','mantenimiento','fuera_servicio') DEFAULT 'disponible',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY ubicacion_predeterminada (ubicacion_predeterminada),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_incidencias = "CREATE TABLE IF NOT EXISTS $tabla_incidencias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            reserva_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo enum('dano','limpieza','equipamiento','ruido','otro') DEFAULT 'otro',
            urgencia enum('baja','media','alta') DEFAULT 'media',
            fotos text DEFAULT NULL COMMENT 'JSON array URLs',
            estado enum('abierta','en_proceso','resuelta','cerrada') DEFAULT 'abierta',
            respuesta_admin text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_espacios);
        dbDelta($sql_reservas);
        dbDelta($sql_equipamiento);
        dbDelta($sql_incidencias);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_espacios' => [
                'description' => 'Listar espacios disponibles',
                'params' => ['tipo', 'fecha'],
            ],
            'detalle_espacio' => [
                'description' => 'Ver detalles del espacio',
                'params' => ['espacio_id'],
            ],
            'disponibilidad' => [
                'description' => 'Ver disponibilidad de espacio',
                'params' => ['espacio_id', 'fecha_desde', 'fecha_hasta'],
            ],
            'crear_reserva' => [
                'description' => 'Crear reserva',
                'params' => ['espacio_id', 'fecha_inicio', 'fecha_fin', 'motivo'],
            ],
            'mis_reservas' => [
                'description' => 'Mis reservas activas',
                'params' => [],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar reserva',
                'params' => ['reserva_id'],
            ],
            'valorar_espacio' => [
                'description' => 'Valorar espacio usado',
                'params' => ['reserva_id', 'valoracion', 'comentario'],
            ],
            'equipamiento_disponible' => [
                'description' => 'Ver equipamiento disponible',
                'params' => ['categoria'],
            ],
            'reportar_incidencia' => [
                'description' => 'Reportar problema en espacio',
                'params' => ['espacio_id', 'descripcion'],
            ],
            'estadisticas_espacios' => [
                'description' => 'Estadísticas de uso (admin)',
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

    // =========================================================================
    // ACCIONES DEL MÓDULO
    // =========================================================================

    /**
     * Acción: Listar espacios
     */
    private function action_listar_espacios($params) {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $where = ["estado = 'disponible'"];
        $prepare_values = [];

        if (!empty($params['tipo'])) {
            $where[] = 'tipo = %s';
            $prepare_values[] = sanitize_text_field($params['tipo']);
        }

        $sql = "SELECT * FROM $tabla_espacios WHERE " . implode(' AND ', $where) . " ORDER BY nombre";

        if (!empty($prepare_values)) {
            $espacios = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $espacios = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'espacios' => array_map([$this, 'formatear_espacio'], $espacios),
        ];
    }

    /**
     * Acción: Detalle de espacio
     */
    private function action_detalle_espacio($params) {
        if (empty($params['espacio_id'])) {
            return ['success' => false, 'error' => 'ID de espacio requerido'];
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            intval($params['espacio_id'])
        ));

        if (!$espacio) {
            return ['success' => false, 'error' => 'Espacio no encontrado'];
        }

        $espacio_formateado = $this->formatear_espacio($espacio, true);

        return [
            'success' => true,
            'espacio' => $espacio_formateado,
        ];
    }

    /**
     * Acción: Ver disponibilidad
     */
    private function action_disponibilidad($params) {
        if (empty($params['espacio_id'])) {
            return ['success' => false, 'error' => 'ID de espacio requerido'];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio_id = intval($params['espacio_id']);
        $fecha_desde = isset($params['fecha_desde']) ? sanitize_text_field($params['fecha_desde']) : date('Y-m-d');
        $fecha_hasta = isset($params['fecha_hasta']) ? sanitize_text_field($params['fecha_hasta']) : date('Y-m-d', strtotime('+30 days'));

        // Obtener espacio
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT horario_apertura, horario_cierre, dias_disponibles FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ));

        if (!$espacio) {
            return ['success' => false, 'error' => 'Espacio no encontrado'];
        }

        // Obtener reservas en el rango
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha_inicio, fecha_fin, estado
             FROM $tabla_reservas
             WHERE espacio_id = %d
             AND fecha_inicio >= %s
             AND fecha_fin <= %s
             AND estado IN ('solicitada', 'confirmada', 'en_curso')",
            $espacio_id,
            $fecha_desde . ' 00:00:00',
            $fecha_hasta . ' 23:59:59'
        ));

        $slots_ocupados = [];
        foreach ($reservas as $reserva) {
            $slots_ocupados[] = [
                'inicio' => $reserva->fecha_inicio,
                'fin' => $reserva->fecha_fin,
                'estado' => $reserva->estado,
            ];
        }

        return [
            'success' => true,
            'espacio_id' => $espacio_id,
            'horario' => [
                'apertura' => $espacio->horario_apertura,
                'cierre' => $espacio->horario_cierre,
                'dias' => explode(',', $espacio->dias_disponibles),
            ],
            'reservas' => $slots_ocupados,
        ];
    }

    /**
     * Acción: Crear reserva
     */
    private function action_crear_reserva($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['espacio_id']) || empty($params['fecha_inicio']) || empty($params['fecha_fin'])) {
            return ['success' => false, 'error' => 'Espacio, fecha inicio y fecha fin son requeridos'];
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $espacio_id = intval($params['espacio_id']);
        $usuario_id = get_current_user_id();
        $fecha_inicio = sanitize_text_field($params['fecha_inicio']);
        $fecha_fin = sanitize_text_field($params['fecha_fin']);

        // Verificar espacio existe y está disponible
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d AND estado = 'disponible'",
            $espacio_id
        ));

        if (!$espacio) {
            return ['success' => false, 'error' => 'Espacio no disponible'];
        }

        // Validar fechas
        $settings = $this->get_settings();
        $ahora = current_time('timestamp');
        $inicio_timestamp = strtotime($fecha_inicio);
        $fin_timestamp = strtotime($fecha_fin);

        if ($inicio_timestamp <= $ahora) {
            return ['success' => false, 'error' => 'La fecha de inicio debe ser futura'];
        }

        if ($fin_timestamp <= $inicio_timestamp) {
            return ['success' => false, 'error' => 'La fecha fin debe ser posterior al inicio'];
        }

        // Validar anticipación mínima
        $horas_anticipacion = ($inicio_timestamp - $ahora) / 3600;
        if ($horas_anticipacion < $settings['horas_anticipacion_minima']) {
            return ['success' => false, 'error' => sprintf(__('Debes reservar con al menos %d horas de anticipación', 'flavor-chat-ia'), $settings['horas_anticipacion_minima'])];
        }

        // Validar duración máxima
        $duracion_horas = ($fin_timestamp - $inicio_timestamp) / 3600;
        if ($duracion_horas > $settings['duracion_maxima_horas']) {
            return ['success' => false, 'error' => sprintf(__('La duración máxima es %d horas', 'flavor-chat-ia'), $settings['duracion_maxima_horas'])];
        }

        // Verificar disponibilidad (no solapamiento)
        $solapamiento = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reservas
             WHERE espacio_id = %d
             AND estado IN ('solicitada', 'confirmada', 'en_curso')
             AND (
                 (fecha_inicio <= %s AND fecha_fin > %s)
                 OR (fecha_inicio < %s AND fecha_fin >= %s)
                 OR (fecha_inicio >= %s AND fecha_fin <= %s)
             )",
            $espacio_id,
            $fecha_inicio, $fecha_inicio,
            $fecha_fin, $fecha_fin,
            $fecha_inicio, $fecha_fin
        ));

        if ($solapamiento) {
            return ['success' => false, 'error' => 'El espacio ya está reservado en ese horario'];
        }

        // Calcular precio
        $precio_total = 0;
        if ($espacio->precio_hora > 0) {
            $precio_total = $duracion_horas * floatval($espacio->precio_hora);
        } elseif ($espacio->precio_dia > 0 && $duracion_horas >= 8) {
            $precio_total = floatval($espacio->precio_dia);
        }

        // Crear reserva
        $estado_inicial = $settings['auto_confirmar_reservas'] ? 'confirmada' : 'solicitada';

        $resultado = $wpdb->insert($tabla_reservas, [
            'espacio_id' => $espacio_id,
            'usuario_id' => $usuario_id,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'num_asistentes' => isset($params['num_asistentes']) ? intval($params['num_asistentes']) : null,
            'motivo' => isset($params['motivo']) ? sanitize_textarea_field($params['motivo']) : null,
            'tipo_evento' => isset($params['tipo_evento']) ? sanitize_text_field($params['tipo_evento']) : null,
            'equipamiento_adicional' => isset($params['equipamiento']) ? wp_json_encode($params['equipamiento']) : null,
            'precio_total' => $precio_total,
            'fianza' => $espacio->requiere_fianza ? floatval($espacio->importe_fianza) : null,
            'instrucciones_especiales' => isset($params['instrucciones']) ? sanitize_textarea_field($params['instrucciones']) : null,
            'estado' => $estado_inicial,
            'fecha_solicitud' => current_time('mysql'),
            'fecha_confirmacion' => $estado_inicial === 'confirmada' ? current_time('mysql') : null,
        ]);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al crear la reserva'];
        }

        $reserva_id = $wpdb->insert_id;

        // Notificaciones
        if ($estado_inicial === 'solicitada') {
            $this->notificar_nueva_reserva($reserva_id, $espacio, $usuario_id);
        }

        return [
            'success' => true,
            'mensaje' => $estado_inicial === 'confirmada'
                ? __('Reserva confirmada', 'flavor-chat-ia')
                : __('Solicitud de reserva enviada. Recibirás confirmación pronto.', 'flavor-chat-ia'),
            'reserva_id' => $reserva_id,
            'estado' => $estado_inicial,
            'precio_total' => $precio_total,
            'fianza' => $espacio->requiere_fianza ? floatval($espacio->importe_fianza) : 0,
        ];
    }

    /**
     * Acción: Mis reservas
     */
    private function action_mis_reservas($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $usuario_id = get_current_user_id();

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo, e.ubicacion,
                    (SELECT url FROM (SELECT JSON_UNQUOTE(JSON_EXTRACT(e.fotos, '$[0]')) as url) t) as foto
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.usuario_id = %d
             ORDER BY r.fecha_inicio DESC",
            $usuario_id
        ));

        $activas = [];
        $pasadas = [];

        foreach ($reservas as $reserva) {
            $formateada = $this->formatear_reserva($reserva);
            if (in_array($reserva->estado, ['solicitada', 'confirmada', 'en_curso'])) {
                $activas[] = $formateada;
            } else {
                $pasadas[] = $formateada;
            }
        }

        return [
            'success' => true,
            'reservas_activas' => $activas,
            'reservas_pasadas' => $pasadas,
        ];
    }

    /**
     * Acción: Cancelar reserva
     */
    private function action_cancelar_reserva($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['reserva_id'])) {
            return ['success' => false, 'error' => 'ID de reserva requerido'];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reserva_id = intval($params['reserva_id']);
        $usuario_id = get_current_user_id();

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND usuario_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            return ['success' => false, 'error' => 'Reserva no encontrada'];
        }

        if (!in_array($reserva->estado, ['solicitada', 'confirmada'])) {
            return ['success' => false, 'error' => 'Esta reserva no puede ser cancelada'];
        }

        // Verificar anticipación mínima para cancelación
        $settings = $this->get_settings();
        $horas_hasta_reserva = (strtotime($reserva->fecha_inicio) - current_time('timestamp')) / 3600;
        $pierde_fianza = $horas_hasta_reserva < $settings['horas_anticipacion_cancelacion'];

        $wpdb->update($tabla_reservas, [
            'estado' => 'cancelada',
            'fecha_cancelacion' => current_time('mysql'),
            'fianza_devuelta' => $pierde_fianza ? 0 : 1,
        ], ['id' => $reserva_id]);

        return [
            'success' => true,
            'mensaje' => $pierde_fianza
                ? __('Reserva cancelada. La fianza no será devuelta por cancelación tardía.', 'flavor-chat-ia')
                : __('Reserva cancelada correctamente.', 'flavor-chat-ia'),
            'fianza_devuelta' => !$pierde_fianza,
        ];
    }

    /**
     * Acción: Valorar espacio
     */
    private function action_valorar_espacio($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['reserva_id']) || empty($params['valoracion'])) {
            return ['success' => false, 'error' => 'Reserva y valoración son requeridos'];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reserva_id = intval($params['reserva_id']);
        $usuario_id = get_current_user_id();
        $valoracion = max(1, min(5, intval($params['valoracion'])));
        $comentario = isset($params['comentario']) ? sanitize_textarea_field($params['comentario']) : null;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND usuario_id = %d AND estado = 'finalizada'",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            return ['success' => false, 'error' => 'Solo puedes valorar reservas finalizadas'];
        }

        if ($reserva->valoracion) {
            return ['success' => false, 'error' => 'Ya has valorado esta reserva'];
        }

        // Guardar valoración
        $wpdb->update($tabla_reservas, [
            'valoracion' => $valoracion,
            'comentario_valoracion' => $comentario,
        ], ['id' => $reserva_id]);

        // Actualizar media del espacio
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(valoracion) as media, COUNT(*) as total
             FROM $tabla_reservas
             WHERE espacio_id = %d AND valoracion IS NOT NULL",
            $reserva->espacio_id
        ));

        $wpdb->update($tabla_espacios, [
            'valoracion_media' => $stats->media,
            'numero_valoraciones' => $stats->total,
        ], ['id' => $reserva->espacio_id]);

        return [
            'success' => true,
            'mensaje' => __('Gracias por tu valoración', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Equipamiento disponible
     */
    private function action_equipamiento_disponible($params) {
        global $wpdb;
        $tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';

        $where = ["estado = 'disponible'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        $sql = "SELECT * FROM $tabla_equipamiento WHERE " . implode(' AND ', $where) . " ORDER BY categoria, nombre";

        if (!empty($prepare_values)) {
            $equipamiento = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $equipamiento = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'equipamiento' => array_map(function($e) {
                return [
                    'id' => intval($e->id),
                    'nombre' => $e->nombre,
                    'descripcion' => $e->descripcion,
                    'categoria' => $e->categoria,
                    'cantidad' => intval($e->cantidad),
                    'requiere_reserva' => (bool)$e->requiere_reserva,
                    'precio' => floatval($e->precio_reserva),
                    'foto' => $e->foto_url,
                    'instrucciones' => $e->instrucciones_uso,
                ];
            }, $equipamiento),
        ];
    }

    /**
     * Acción: Reportar incidencia
     */
    private function action_reportar_incidencia($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['espacio_id']) || empty($params['descripcion'])) {
            return ['success' => false, 'error' => 'Espacio y descripción son requeridos'];
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_espacios_incidencias';

        $resultado = $wpdb->insert($tabla_incidencias, [
            'espacio_id' => intval($params['espacio_id']),
            'usuario_id' => get_current_user_id(),
            'reserva_id' => isset($params['reserva_id']) ? intval($params['reserva_id']) : null,
            'titulo' => isset($params['titulo']) ? sanitize_text_field($params['titulo']) : __('Incidencia', 'flavor-chat-ia'),
            'descripcion' => sanitize_textarea_field($params['descripcion']),
            'tipo' => isset($params['tipo']) ? sanitize_text_field($params['tipo']) : 'otro',
            'urgencia' => isset($params['urgencia']) ? sanitize_text_field($params['urgencia']) : 'media',
            'fotos' => isset($params['fotos']) ? wp_json_encode($params['fotos']) : null,
            'estado' => 'abierta',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al reportar incidencia'];
        }

        // Notificar administrador
        $this->notificar_incidencia($wpdb->insert_id);

        return [
            'success' => true,
            'mensaje' => __('Incidencia reportada. La revisaremos pronto.', 'flavor-chat-ia'),
            'incidencia_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Acción: Estadísticas (admin)
     */
    private function action_estadisticas_espacios($params) {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $periodo = isset($params['periodo']) ? sanitize_text_field($params['periodo']) : 'mes';
        $fecha_inicio = $this->calcular_fecha_inicio($periodo);

        $total_espacios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_espacios WHERE estado = 'disponible'");

        $total_reservas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE fecha_solicitud >= %s",
            $fecha_inicio
        ));

        $reservas_confirmadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE fecha_solicitud >= %s AND estado IN ('confirmada', 'en_curso', 'finalizada')",
            $fecha_inicio
        ));

        $ingresos = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(precio_total) FROM $tabla_reservas WHERE fecha_solicitud >= %s AND estado IN ('confirmada', 'en_curso', 'finalizada')",
            $fecha_inicio
        ));

        $espacios_populares = $wpdb->get_results($wpdb->prepare(
            "SELECT e.nombre, COUNT(r.id) as total_reservas
             FROM $tabla_espacios e
             LEFT JOIN $tabla_reservas r ON e.id = r.espacio_id AND r.fecha_solicitud >= %s
             GROUP BY e.id
             ORDER BY total_reservas DESC
             LIMIT 5",
            $fecha_inicio
        ));

        return [
            'success' => true,
            'estadisticas' => [
                'total_espacios' => intval($total_espacios),
                'total_reservas' => intval($total_reservas),
                'reservas_confirmadas' => intval($reservas_confirmadas),
                'tasa_confirmacion' => $total_reservas > 0 ? round(($reservas_confirmadas / $total_reservas) * 100, 1) : 0,
                'ingresos' => floatval($ingresos ?: 0),
                'espacios_populares' => $espacios_populares,
            ],
        ];
    }

    // =========================================================================
    // REST API ENDPOINTS
    // =========================================================================

    public function api_listar_espacios($request) {
        $params = ['tipo' => $request->get_param('tipo')];
        $resultado = $this->action_listar_espacios($params);
        return new WP_REST_Response($this->sanitize_public_espacios_response($resultado), 200);
    }

    public function api_detalle_espacio($request) {
        $resultado = $this->action_detalle_espacio(['espacio_id' => $request->get_param('id')]);
        return new WP_REST_Response($this->sanitize_public_espacios_response($resultado), $resultado['success'] ? 200 : 404);
    }

    public function api_disponibilidad($request) {
        $params = [
            'espacio_id' => $request->get_param('id'),
            'fecha_desde' => $request->get_param('desde'),
            'fecha_hasta' => $request->get_param('hasta'),
        ];
        $resultado = $this->action_disponibilidad($params);
        return new WP_REST_Response($this->sanitize_public_espacios_response($resultado), 200);
    }

    public function api_crear_reserva($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_crear_reserva($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 201 : 400);
    }

    public function api_mis_reservas($request) {
        $resultado = $this->action_mis_reservas([]);
        return new WP_REST_Response($resultado, 200);
    }

    public function api_cancelar_reserva($request) {
        $resultado = $this->action_cancelar_reserva(['reserva_id' => $request->get_param('id')]);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    public function api_valorar($request) {
        $params = array_merge(
            $request->get_json_params(),
            ['reserva_id' => $request->get_param('id')]
        );
        $resultado = $this->action_valorar_espacio($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    public function api_equipamiento($request) {
        $params = ['categoria' => $request->get_param('categoria')];
        $resultado = $this->action_equipamiento_disponible($params);
        return new WP_REST_Response($this->sanitize_public_espacios_response($resultado), 200);
    }

    public function api_reportar_incidencia($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_reportar_incidencia($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 201 : 400);
    }

    public function api_estadisticas($request) {
        $params = ['periodo' => $request->get_param('periodo')];
        $resultado = $this->action_estadisticas_espacios($params);
        return new WP_REST_Response($resultado, 200);
    }

    public function api_tipos_espacios($request) {
        $tipos = [
            'salon_eventos' => __('Salón de eventos', 'flavor-chat-ia'),
            'sala_reuniones' => __('Sala de reuniones', 'flavor-chat-ia'),
            'cocina' => __('Cocina comunitaria', 'flavor-chat-ia'),
            'taller' => __('Taller', 'flavor-chat-ia'),
            'terraza' => __('Terraza', 'flavor-chat-ia'),
            'jardin' => __('Jardín', 'flavor-chat-ia'),
            'gimnasio' => __('Gimnasio', 'flavor-chat-ia'),
            'ludoteca' => __('Ludoteca', 'flavor-chat-ia'),
            'piscina' => __('Piscina', 'flavor-chat-ia'),
            'parking' => __('Parking', 'flavor-chat-ia'),
            'otro' => __('Otro', 'flavor-chat-ia'),
        ];

        return new WP_REST_Response(['success' => true, 'tipos' => $tipos], 200);
    }

    private function sanitize_public_espacios_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['espacios']) && is_array($respuesta['espacios'])) {
            $respuesta['espacios'] = array_map([$this, 'sanitize_public_espacio'], $respuesta['espacios']);
        }

        if (!empty($respuesta['espacio']) && is_array($respuesta['espacio'])) {
            $respuesta['espacio'] = $this->sanitize_public_espacio($respuesta['espacio']);
        }

        return $respuesta;
    }

    private function sanitize_public_espacio($espacio) {
        if (!is_array($espacio)) {
            return $espacio;
        }

        if (array_key_exists('responsable', $espacio)) {
            unset($espacio['responsable']);
        }

        return $espacio;
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    public function ajax_crear_reserva() {
        check_ajax_referer('espacios_nonce', 'nonce');

        $resultado = $this->action_crear_reserva([
            'espacio_id' => isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0,
            'fecha_inicio' => isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : '',
            'fecha_fin' => isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : '',
            'num_asistentes' => isset($_POST['num_asistentes']) ? intval($_POST['num_asistentes']) : null,
            'motivo' => isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '',
            'tipo_evento' => isset($_POST['tipo_evento']) ? sanitize_text_field($_POST['tipo_evento']) : '',
        ]);

        wp_send_json($resultado);
    }

    public function ajax_cancelar_reserva() {
        check_ajax_referer('espacios_nonce', 'nonce');

        $resultado = $this->action_cancelar_reserva([
            'reserva_id' => isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    public function ajax_valorar() {
        check_ajax_referer('espacios_nonce', 'nonce');

        $resultado = $this->action_valorar_espacio([
            'reserva_id' => isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0,
            'valoracion' => isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0,
            'comentario' => isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '',
        ]);

        wp_send_json($resultado);
    }

    public function ajax_disponibilidad() {
        $resultado = $this->action_disponibilidad([
            'espacio_id' => isset($_GET['espacio_id']) ? intval($_GET['espacio_id']) : 0,
            'fecha_desde' => isset($_GET['desde']) ? sanitize_text_field($_GET['desde']) : date('Y-m-d'),
            'fecha_hasta' => isset($_GET['hasta']) ? sanitize_text_field($_GET['hasta']) : date('Y-m-d', strtotime('+30 days')),
        ]);

        wp_send_json($resultado);
    }

    public function ajax_reportar_incidencia() {
        check_ajax_referer('espacios_nonce', 'nonce');

        $resultado = $this->action_reportar_incidencia([
            'espacio_id' => isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0,
            'titulo' => isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '',
            'descripcion' => isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '',
            'tipo' => isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'otro',
            'urgencia' => isset($_POST['urgencia']) ? sanitize_text_field($_POST['urgencia']) : 'media',
        ]);

        wp_send_json($resultado);
    }

    public function ajax_confirmar_reserva() {
        check_ajax_referer('espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reserva_id = isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0;

        $wpdb->update($tabla_reservas, [
            'estado' => 'confirmada',
            'fecha_confirmacion' => current_time('mysql'),
        ], ['id' => $reserva_id]);

        $this->notificar_reserva_confirmada($reserva_id);

        wp_send_json(['success' => true, 'mensaje' => 'Reserva confirmada']);
    }

    public function ajax_rechazar_reserva() {
        check_ajax_referer('espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reserva_id = isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0;
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';

        $wpdb->update($tabla_reservas, [
            'estado' => 'rechazada',
            'motivo_rechazo' => $motivo,
        ], ['id' => $reserva_id]);

        $this->notificar_reserva_rechazada($reserva_id, $motivo);

        wp_send_json(['success' => true, 'mensaje' => 'Reserva rechazada']);
    }

    public function ajax_devolver_fianza() {
        check_ajax_referer('espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reserva_id = isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0;

        $wpdb->update($tabla_reservas, ['fianza_devuelta' => 1], ['id' => $reserva_id]);

        wp_send_json(['success' => true, 'mensaje' => 'Fianza marcada como devuelta']);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_listado($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        $atts = shortcode_atts([
            'tipo' => '',
            'columnas' => 3,
        ], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('espacios_nonce'),
            'usuario_logueado' => is_user_logged_in(),
        ]);

        ob_start();
        include FLAVOR_CHAT_PATH . 'includes/modules/espacios-comunes/templates/listado.php';
        return ob_get_clean();
    }

    public function shortcode_detalle($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['id' => 0], $atts);
        $espacio_id = $atts['id'] ?: (isset($_GET['espacio_id']) ? intval($_GET['espacio_id']) : 0);

        if (!$espacio_id) {
            return '<p>' . __('Espacio no especificado.', 'flavor-chat-ia') . '</p>';
        }

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('espacios_nonce'),
            'usuario_logueado' => is_user_logged_in(),
            'espacio_id' => $espacio_id,
        ]);

        ob_start();
        include FLAVOR_CHAT_PATH . 'includes/modules/espacios-comunes/templates/detalle.php';
        return ob_get_clean();
    }

    public function shortcode_mis_reservas($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
        }

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('espacios_nonce'),
            'usuario_logueado' => true,
        ]);

        ob_start();
        include FLAVOR_CHAT_PATH . 'includes/modules/espacios-comunes/templates/mis-reservas.php';
        return ob_get_clean();
    }

    public function shortcode_calendario($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['espacio_id' => 0], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('espacios_nonce'),
            'espacio_id' => intval($atts['espacio_id']),
        ]);

        ob_start();
        include FLAVOR_CHAT_PATH . 'includes/modules/espacios-comunes/templates/calendario.php';
        return ob_get_clean();
    }

    public function shortcode_equipamiento($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['categoria' => ''], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);

        ob_start();
        include FLAVOR_CHAT_PATH . 'includes/modules/espacios-comunes/templates/equipamiento.php';
        return ob_get_clean();
    }

    // =========================================================================
    // WP CRON
    // =========================================================================

    public function actualizar_estados_reservas() {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $ahora = current_time('mysql');

        // Pasar a "en_curso" las confirmadas que ya empezaron
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_reservas
             SET estado = 'en_curso'
             WHERE estado = 'confirmada'
             AND fecha_inicio <= %s
             AND fecha_fin > %s",
            $ahora, $ahora
        ));

        // Pasar a "finalizada" las que ya terminaron
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_reservas
             SET estado = 'finalizada'
             WHERE estado = 'en_curso'
             AND fecha_fin <= %s",
            $ahora
        ));
    }

    public function enviar_recordatorios() {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $manana = date('Y-m-d', strtotime('+1 day'));

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.estado = 'confirmada'
             AND DATE(r.fecha_inicio) = %s
             AND r.recordatorio_enviado = 0",
            $manana
        ));

        foreach ($reservas as $reserva) {
            $this->notificar_recordatorio($reserva);
            $wpdb->update($tabla_reservas, ['recordatorio_enviado' => 1], ['id' => $reserva->id]);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function formatear_espacio($espacio, $completo = false) {
        $fotos = json_decode($espacio->fotos, true) ?: [];
        $equipamiento = json_decode($espacio->equipamiento, true) ?: [];

        $data = [
            'id' => intval($espacio->id),
            'nombre' => $espacio->nombre,
            'descripcion' => $completo ? $espacio->descripcion : wp_trim_words($espacio->descripcion, 30),
            'tipo' => $espacio->tipo,
            'tipo_label' => $this->get_tipo_label($espacio->tipo),
            'ubicacion' => $espacio->ubicacion,
            'capacidad' => intval($espacio->capacidad_personas),
            'superficie_m2' => floatval($espacio->superficie_m2),
            'precio_hora' => floatval($espacio->precio_hora),
            'precio_dia' => floatval($espacio->precio_dia),
            'requiere_fianza' => (bool)$espacio->requiere_fianza,
            'importe_fianza' => floatval($espacio->importe_fianza),
            'equipamiento' => $equipamiento,
            'foto_principal' => !empty($fotos) ? $fotos[0] : null,
            'valoracion' => floatval($espacio->valoracion_media),
            'num_valoraciones' => intval($espacio->numero_valoraciones),
            'estado' => $espacio->estado,
        ];

        if ($completo) {
            $data['fotos'] = $fotos;
            $data['normas_uso'] = $espacio->normas_uso;
            $data['horario_apertura'] = $espacio->horario_apertura;
            $data['horario_cierre'] = $espacio->horario_cierre;
            $data['dias_disponibles'] = explode(',', $espacio->dias_disponibles);
            $data['instrucciones_acceso'] = $espacio->instrucciones_acceso;

            if ($espacio->responsable_id) {
                $responsable = get_userdata($espacio->responsable_id);
                $data['responsable'] = $responsable ? $responsable->display_name : null;
            }
        }

        return $data;
    }

    private function formatear_reserva($reserva) {
        return [
            'id' => intval($reserva->id),
            'espacio_id' => intval($reserva->espacio_id),
            'espacio_nombre' => $reserva->espacio_nombre ?? '',
            'espacio_tipo' => $reserva->espacio_tipo ?? '',
            'ubicacion' => $reserva->ubicacion ?? '',
            'foto' => $reserva->foto ?? null,
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'fecha_inicio_formatted' => date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_inicio)),
            'fecha_fin_formatted' => date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_fin)),
            'num_asistentes' => $reserva->num_asistentes,
            'motivo' => $reserva->motivo,
            'tipo_evento' => $reserva->tipo_evento,
            'precio_total' => floatval($reserva->precio_total),
            'fianza' => floatval($reserva->fianza ?: 0),
            'fianza_devuelta' => (bool)$reserva->fianza_devuelta,
            'estado' => $reserva->estado,
            'estado_label' => $this->get_estado_label($reserva->estado),
            'valoracion' => $reserva->valoracion,
            'puede_cancelar' => in_array($reserva->estado, ['solicitada', 'confirmada']),
            'puede_valorar' => $reserva->estado === 'finalizada' && !$reserva->valoracion,
        ];
    }

    private function get_tipo_label($tipo) {
        $tipos = [
            'salon_eventos' => __('Salón de eventos', 'flavor-chat-ia'),
            'sala_reuniones' => __('Sala de reuniones', 'flavor-chat-ia'),
            'cocina' => __('Cocina comunitaria', 'flavor-chat-ia'),
            'taller' => __('Taller', 'flavor-chat-ia'),
            'terraza' => __('Terraza', 'flavor-chat-ia'),
            'jardin' => __('Jardín', 'flavor-chat-ia'),
            'gimnasio' => __('Gimnasio', 'flavor-chat-ia'),
            'ludoteca' => __('Ludoteca', 'flavor-chat-ia'),
            'piscina' => __('Piscina', 'flavor-chat-ia'),
            'parking' => __('Parking', 'flavor-chat-ia'),
            'otro' => __('Otro', 'flavor-chat-ia'),
        ];
        return $tipos[$tipo] ?? $tipo;
    }

    private function get_estado_label($estado) {
        $estados = [
            'solicitada' => __('Pendiente de confirmación', 'flavor-chat-ia'),
            'confirmada' => __('Confirmada', 'flavor-chat-ia'),
            'en_curso' => __('En curso', 'flavor-chat-ia'),
            'finalizada' => __('Finalizada', 'flavor-chat-ia'),
            'cancelada' => __('Cancelada', 'flavor-chat-ia'),
            'rechazada' => __('Rechazada', 'flavor-chat-ia'),
        ];
        return $estados[$estado] ?? $estado;
    }

    private function calcular_fecha_inicio($periodo) {
        switch ($periodo) {
            case 'semana': return date('Y-m-d', strtotime('-1 week'));
            case 'mes': return date('Y-m-d', strtotime('-1 month'));
            case 'trimestre': return date('Y-m-d', strtotime('-3 months'));
            case 'ano': return date('Y-m-d', strtotime('-1 year'));
            default: return date('Y-m-d', strtotime('-1 month'));
        }
    }

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    private function notificar_nueva_reserva($reserva_id, $espacio, $usuario_id) {
        $usuario = get_userdata($usuario_id);
        $asunto = sprintf(__('Nueva solicitud de reserva: %s', 'flavor-chat-ia'), $espacio->nombre);
        $mensaje = sprintf(
            __('%s ha solicitado reservar el espacio "%s".', 'flavor-chat-ia'),
            $usuario->display_name,
            $espacio->nombre
        );

        // Notificar administradores
        $admins = get_users(['role' => 'administrator']);
        foreach ($admins as $admin) {
            do_action('flavor_notificacion_enviar', $admin->ID, $asunto, $mensaje, 'espacios_solicitud');
        }
    }

    private function notificar_reserva_confirmada($reserva_id) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) return;

        $asunto = sprintf(__('Reserva confirmada: %s', 'flavor-chat-ia'), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Tu reserva de "%s" para el %s ha sido confirmada.', 'flavor-chat-ia'),
            $reserva->espacio_nombre,
            date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_inicio))
        );

        do_action('flavor_notificacion_enviar', $reserva->usuario_id, $asunto, $mensaje, 'espacios_confirmada');
    }

    private function notificar_reserva_rechazada($reserva_id, $motivo) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) return;

        $asunto = sprintf(__('Reserva no disponible: %s', 'flavor-chat-ia'), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Tu solicitud de reserva para "%s" no ha podido ser aprobada. %s', 'flavor-chat-ia'),
            $reserva->espacio_nombre,
            $motivo ? __('Motivo: ', 'flavor-chat-ia') . $motivo : ''
        );

        do_action('flavor_notificacion_enviar', $reserva->usuario_id, $asunto, $mensaje, 'espacios_rechazada');
    }

    private function notificar_recordatorio($reserva) {
        $asunto = sprintf(__('Recordatorio: Reserva mañana - %s', 'flavor-chat-ia'), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Recuerda que mañana tienes reservado "%s" a las %s.', 'flavor-chat-ia'),
            $reserva->espacio_nombre,
            date_i18n('H:i', strtotime($reserva->fecha_inicio))
        );

        do_action('flavor_notificacion_enviar', $reserva->usuario_id, $asunto, $mensaje, 'espacios_recordatorio');
    }

    private function notificar_incidencia($incidencia_id) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_espacios_incidencias';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, e.nombre as espacio_nombre
             FROM $tabla_incidencias i
             INNER JOIN $tabla_espacios e ON i.espacio_id = e.id
             WHERE i.id = %d",
            $incidencia_id
        ));

        if (!$incidencia) return;

        $usuario = get_userdata($incidencia->usuario_id);
        $asunto = sprintf(__('Nueva incidencia: %s', 'flavor-chat-ia'), $incidencia->espacio_nombre);
        $mensaje = sprintf(
            __('%s ha reportado una incidencia en "%s": %s', 'flavor-chat-ia'),
            $usuario->display_name,
            $incidencia->espacio_nombre,
            wp_trim_words($incidencia->descripcion, 20)
        );

        $admins = get_users(['role' => 'administrator']);
        foreach ($admins as $admin) {
            do_action('flavor_notificacion_enviar', $admin->ID, $asunto, $mensaje, 'espacios_incidencia');
        }
    }

    // =========================================================================
    // WEB COMPONENTS Y KNOWLEDGE BASE
    // =========================================================================

    public function get_web_components() {
        return [
            'hero_espacios' => [
                'label' => __('Hero Espacios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Espacios Comunes', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Reserva espacios para tus eventos y actividades', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'espacios/hero',
            ],
            'espacios_grid' => [
                'label' => __('Grid de Espacios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestros Espacios', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'tipo_filtro' => ['type' => 'text', 'default' => ''],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'espacios/grid',
            ],
            'calendario_disponibilidad' => [
                'label' => __('Calendario Disponibilidad', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'espacio_id' => ['type' => 'number', 'default' => 0],
                    'vista_defecto' => ['type' => 'select', 'options' => ['mes', 'semana'], 'default' => 'semana'],
                ],
                'template' => 'espacios/calendario',
            ],
            'proceso_reserva' => [
                'label' => __('Proceso de Reserva', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-yes',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Cómo Reservar', 'flavor-chat-ia')],
                ],
                'template' => 'espacios/proceso',
            ],
        ];
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'espacios_listar',
                'description' => 'Ver espacios comunes disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'description' => 'Tipo de espacio'],
                    ],
                ],
            ],
            [
                'name' => 'espacios_reservar',
                'description' => 'Reservar un espacio común',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'espacio_id' => ['type' => 'integer'],
                        'fecha_inicio' => ['type' => 'string'],
                        'fecha_fin' => ['type' => 'string'],
                        'motivo' => ['type' => 'string'],
                    ],
                    'required' => ['espacio_id', 'fecha_inicio', 'fecha_fin'],
                ],
            ],
        ];
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Espacios Comunes de la Comunidad**

Reserva espacios compartidos para tus eventos y actividades.

**Tipos de espacios:**
- Salón de eventos (fiestas, reuniones grandes)
- Salas de reuniones (juntas, talleres)
- Cocina comunitaria (cenas, talleres cocina)
- Taller (bricolaje, reparaciones)
- Terraza/Jardín (eventos al aire libre)
- Gimnasio (deportes, yoga)
- Ludoteca (cumpleaños infantiles)
- Piscina (reserva de carriles/horarios)

**Equipamiento disponible:**
- Mesas y sillas
- Sistema de sonido
- Proyector y pantalla
- Cocina equipada
- Vajilla y cubiertos
- Juegos y juguetes
- Material deportivo

**Cómo reservar:**
1. Consulta disponibilidad
2. Solicita tu reserva
3. Espera confirmación
4. Paga fianza si requerido
5. Recoge llaves día del evento
6. Disfruta el espacio
7. Deja todo limpio y ordenado
8. Recupera tu fianza

**Normas generales:**
- Respeta horarios
- Deja el espacio limpio
- No excedas capacidad
- No hacer ruido excesivo
- Respeta vecinos
- Avisa si hay daños

**Precios:**
- Gratuitos o precio simbólico
- Fianza reembolsable
- Descuentos por reservas largas
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cuánto cuesta reservar un espacio?',
                'respuesta' => 'Depende del espacio. Muchos son gratuitos con fianza reembolsable, otros tienen precio simbólico.',
            ],
            [
                'pregunta' => '¿Puedo cancelar mi reserva?',
                'respuesta' => 'Sí, con al menos 24h de antelación sin penalización. Cancelaciones tardías pierden la fianza.',
            ],
            [
                'pregunta' => '¿Qué pasa si rompo algo?',
                'respuesta' => 'Se descuenta de la fianza. Si el daño supera la fianza, debes pagar la diferencia.',
            ],
            [
                'pregunta' => '¿Cómo recojo las llaves?',
                'respuesta' => 'Se te indicará en la confirmación. Normalmente en conserjería o buzón con código.',
            ],
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('espacios_comunes');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('espacios-comunes');
        if (!$pagina && !get_option('flavor_espacios_comunes_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['espacios_comunes']);
            update_option('flavor_espacios_comunes_pages_created', 1, false);
        }
    }

}
