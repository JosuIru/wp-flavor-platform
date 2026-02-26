<?php
/**
 * Modulo de Carpooling para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Carpooling - Sistema de viajes compartidos
 */
class Flavor_Chat_Carpooling_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Nombre tabla viajes
     */
    private $tabla_viajes;

    /**
     * Nombre tabla reservas
     */
    private $tabla_reservas;

    /**
     * Nombre tabla rutas recurrentes
     */
    private $tabla_rutas_recurrentes;

    /**
     * Nombre tabla valoraciones
     */
    private $tabla_valoraciones;

    /**
     * Nombre tabla vehiculos
     */
    private $tabla_vehiculos;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->id = 'carpooling';
        $this->name = 'Carpooling'; // Translation loaded on init
        $this->description = 'Sistema de viajes compartidos entre vecinos para reducir costes y emisiones.'; // Translation loaded on init

        $this->tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
        $this->tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
        $this->tabla_rutas_recurrentes = $wpdb->prefix . 'flavor_carpooling_rutas_recurrentes';
        $this->tabla_valoraciones = $wpdb->prefix . 'flavor_carpooling_valoraciones';
        $this->tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_viajes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Carpooling no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
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
    public function get_table_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        return [
            $this->tabla_viajes => "CREATE TABLE {$this->tabla_viajes} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                conductor_id bigint(20) UNSIGNED NOT NULL,
                vehiculo_id bigint(20) UNSIGNED DEFAULT NULL,
                origen varchar(255) NOT NULL,
                destino varchar(255) NOT NULL,
                origen_lat decimal(10,8) DEFAULT NULL,
                origen_lng decimal(11,8) DEFAULT NULL,
                destino_lat decimal(10,8) DEFAULT NULL,
                destino_lng decimal(11,8) DEFAULT NULL,
                fecha_salida datetime NOT NULL,
                plazas_disponibles int(11) NOT NULL,
                plazas_ocupadas int(11) NOT NULL DEFAULT 0,
                precio_por_plaza decimal(10,2) DEFAULT NULL,
                descripcion text,
                permite_fumar tinyint(1) NOT NULL DEFAULT 0,
                permite_mascotas tinyint(1) NOT NULL DEFAULT 0,
                permite_equipaje_grande tinyint(1) NOT NULL DEFAULT 0,
                estado enum('activo','completo','cancelado','finalizado') NOT NULL DEFAULT 'activo',
                es_recurrente tinyint(1) NOT NULL DEFAULT 0,
                ruta_recurrente_id bigint(20) UNSIGNED DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY conductor_id (conductor_id),
                KEY vehiculo_id (vehiculo_id),
                KEY estado (estado),
                KEY fecha_salida (fecha_salida),
                KEY ruta_recurrente_id (ruta_recurrente_id)
            ) $charset_collate;",

            $this->tabla_reservas => "CREATE TABLE {$this->tabla_reservas} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                viaje_id bigint(20) UNSIGNED NOT NULL,
                pasajero_id bigint(20) UNSIGNED NOT NULL,
                numero_plazas int(11) NOT NULL DEFAULT 1,
                estado enum('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
                precio_total decimal(10,2) DEFAULT NULL,
                punto_recogida varchar(255) DEFAULT NULL,
                punto_bajada varchar(255) DEFAULT NULL,
                notas text,
                fecha_reserva datetime NOT NULL,
                fecha_confirmacion datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY viaje_id (viaje_id),
                KEY pasajero_id (pasajero_id),
                KEY estado (estado)
            ) $charset_collate;",

            $this->tabla_rutas_recurrentes => "CREATE TABLE {$this->tabla_rutas_recurrentes} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                conductor_id bigint(20) UNSIGNED NOT NULL,
                nombre varchar(255) NOT NULL,
                origen varchar(255) NOT NULL,
                destino varchar(255) NOT NULL,
                dias_semana varchar(50) NOT NULL,
                hora_salida time NOT NULL,
                plazas_disponibles int(11) NOT NULL,
                precio_por_plaza decimal(10,2) DEFAULT NULL,
                activa tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY conductor_id (conductor_id)
            ) $charset_collate;",

            $this->tabla_valoraciones => "CREATE TABLE {$this->tabla_valoraciones} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                viaje_id bigint(20) UNSIGNED NOT NULL,
                valorador_id bigint(20) UNSIGNED NOT NULL,
                valorado_id bigint(20) UNSIGNED NOT NULL,
                tipo_valoracion enum('conductor','pasajero') NOT NULL,
                puntuacion int(11) NOT NULL,
                comentario text,
                fecha_valoracion datetime NOT NULL,
                PRIMARY KEY (id),
                KEY viaje_id (viaje_id),
                KEY valorador_id (valorador_id),
                KEY valorado_id (valorado_id)
            ) $charset_collate;",

            $this->tabla_vehiculos => "CREATE TABLE {$this->tabla_vehiculos} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                propietario_id bigint(20) UNSIGNED NOT NULL,
                marca varchar(100) NOT NULL,
                modelo varchar(100) NOT NULL,
                color varchar(50) DEFAULT NULL,
                matricula varchar(20) NOT NULL,
                ano int(11) DEFAULT NULL,
                plazas_totales int(11) NOT NULL,
                foto_url varchar(500) DEFAULT NULL,
                activo tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY propietario_id (propietario_id),
                UNIQUE KEY matricula (matricula)
            ) $charset_collate;"
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_verificacion_conductor' => true,
            'permite_valoraciones' => true,
            'dias_anticipacion_maxima' => 30,
            'max_pasajeros_por_viaje' => 4,
            'permite_mascotas' => true,
            'permite_equipaje_grande' => true,
            'radio_busqueda_km' => 5,
            'calculo_coste_automatico' => true,
            'precio_por_km' => 0.15,
            'comision_plataforma_porcentaje' => 0,
            'notificaciones_email' => true,
            'tiempo_minimo_cancelacion_horas' => 24,
            'puntuacion_minima_conductor' => 3.0,
            'max_cancelaciones_mes' => 3,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // AJAX handlers publicos
        add_action('wp_ajax_carpooling_buscar_viajes', [$this, 'ajax_buscar_viajes']);
        add_action('wp_ajax_nopriv_carpooling_buscar_viajes', [$this, 'ajax_buscar_viajes']);
        add_action('wp_ajax_carpooling_publicar_viaje', [$this, 'ajax_publicar_viaje']);
        add_action('wp_ajax_carpooling_reservar_plaza', [$this, 'ajax_reservar_plaza']);
        add_action('wp_ajax_carpooling_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_carpooling_confirmar_reserva', [$this, 'ajax_confirmar_reserva']);
        add_action('wp_ajax_carpooling_valorar_viaje', [$this, 'ajax_valorar_viaje']);
        add_action('wp_ajax_carpooling_cancelar_viaje', [$this, 'ajax_cancelar_viaje']);
        add_action('wp_ajax_carpooling_mis_viajes', [$this, 'ajax_mis_viajes']);
        add_action('wp_ajax_carpooling_mis_reservas', [$this, 'ajax_mis_reservas']);
        add_action('wp_ajax_carpooling_guardar_vehiculo', [$this, 'ajax_guardar_vehiculo']);
        add_action('wp_ajax_carpooling_obtener_vehiculos', [$this, 'ajax_obtener_vehiculos']);
        add_action('wp_ajax_carpooling_crear_ruta_recurrente', [$this, 'ajax_crear_ruta_recurrente']);
        add_action('wp_ajax_carpooling_autocompletar_lugar', [$this, 'ajax_autocompletar_lugar']);
        add_action('wp_ajax_nopriv_carpooling_autocompletar_lugar', [$this, 'ajax_autocompletar_lugar']);
        add_action('wp_ajax_carpooling_detalle_viaje', [$this, 'ajax_detalle_viaje']);
        add_action('wp_ajax_nopriv_carpooling_detalle_viaje', [$this, 'ajax_detalle_viaje']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para viajes recurrentes
        add_action('carpooling_generar_viajes_recurrentes', [$this, 'generar_viajes_recurrentes']);
        if (!wp_next_scheduled('carpooling_generar_viajes_recurrentes')) {
            wp_schedule_event(time(), 'daily', 'carpooling_generar_viajes_recurrentes');
        }

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-carpooling-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Carpooling_Frontend_Controller::get_instance();
        }
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('carpooling_buscar_viaje', [$this, 'shortcode_buscar_viaje']);
        add_shortcode('carpooling_publicar_viaje', [$this, 'shortcode_publicar_viaje']);
        add_shortcode('carpooling_mis_viajes', [$this, 'shortcode_mis_viajes']);
        add_shortcode('carpooling_mis_reservas', [$this, 'shortcode_mis_reservas']);
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets() {
        $modulo_url = plugin_dir_url(__FILE__);

        wp_register_style(
            'carpooling-styles',
            $modulo_url . 'assets/css/carpooling.css',
            [],
            '1.0.0'
        );

        wp_register_script(
            'carpooling-scripts',
            $modulo_url . 'assets/js/carpooling.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('carpooling-scripts', 'carpoolingData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('carpooling_nonce'),
            'restUrl' => rest_url('carpooling/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings' => [
                'confirmar_reserva' => __('Confirmar reserva?', 'flavor-chat-ia'),
                'cancelar_reserva' => __('Cancelar reserva?', 'flavor-chat-ia'),
                'error_generico' => __('Ha ocurrido un error. Intentalo de nuevo.', 'flavor-chat-ia'),
                'reserva_exitosa' => __('Reserva realizada con exito!', 'flavor-chat-ia'),
                'viaje_publicado' => __('Viaje publicado correctamente!', 'flavor-chat-ia'),
                'selecciona_origen' => __('Selecciona punto de origen', 'flavor-chat-ia'),
                'selecciona_destino' => __('Selecciona punto de destino', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sin_resultados' => __('No se encontraron viajes', 'flavor-chat-ia'),
            ],
            'settings' => [
                'radio_busqueda_km' => $this->settings['radio_busqueda_km'],
                'precio_por_km' => $this->settings['precio_por_km'],
                'max_pasajeros' => $this->settings['max_pasajeros_por_viaje'],
            ],
        ]);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_viajes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
        /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_viajes' => [
                'description' => 'Buscar viajes disponibles',
                'params' => ['origen', 'destino', 'fecha', 'radio_km', 'plazas'],
            ],
            'publicar_viaje' => [
                'description' => 'Publicar nuevo viaje',
                'params' => ['origen', 'destino', 'fecha_hora', 'plazas', 'precio', 'vehiculo_id'],
            ],
            'reservar_plaza' => [
                'description' => 'Reservar plaza en viaje',
                'params' => ['viaje_id', 'plazas', 'mensaje'],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar una reserva',
                'params' => ['reserva_id', 'motivo'],
            ],
            'mis_viajes_conductor' => [
                'description' => 'Mis viajes como conductor',
                'params' => ['estado', 'limite'],
            ],
            'mis_viajes_pasajero' => [
                'description' => 'Mis viajes como pasajero',
                'params' => ['estado', 'limite'],
            ],
            'confirmar_reserva' => [
                'description' => 'Confirmar o rechazar reserva (conductor)',
                'params' => ['reserva_id', 'accion', 'motivo'],
            ],
            'valorar_viaje' => [
                'description' => 'Valorar conductor o pasajero',
                'params' => ['reserva_id', 'puntuacion', 'comentario', 'aspectos'],
            ],
            'obtener_valoraciones' => [
                'description' => 'Obtener valoraciones de un usuario',
                'params' => ['usuario_id', 'tipo'],
            ],
            'historial_viajes' => [
                'description' => 'Historial de viajes realizados',
                'params' => ['limite', 'pagina'],
            ],
            'gestionar_vehiculo' => [
                'description' => 'Agregar o editar vehiculo',
                'params' => ['vehiculo_id', 'datos'],
            ],
            'crear_ruta_recurrente' => [
                'description' => 'Crear ruta recurrente',
                'params' => ['origen', 'destino', 'hora', 'dias', 'plazas', 'precio'],
            ],
            'estadisticas_carpooling' => [
                'description' => 'Estadisticas generales (admin)',
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
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Buscar viajes
     */
    private function action_buscar_viajes($params) {
        global $wpdb;

        $origen_lat = floatval($params['origen_lat'] ?? 0);
        $origen_lng = floatval($params['origen_lng'] ?? 0);
        $destino_lat = floatval($params['destino_lat'] ?? 0);
        $destino_lng = floatval($params['destino_lng'] ?? 0);
        $fecha = sanitize_text_field($params['fecha'] ?? date('Y-m-d'));
        $radio_km = absint($params['radio_km'] ?? $this->settings['radio_busqueda_km']);
        $plazas_requeridas = absint($params['plazas'] ?? 1);

        $sql = "SELECT v.*,
                (6371 * acos(cos(radians(%f)) * cos(radians(origen_lat)) * cos(radians(origen_lng) - radians(%f)) + sin(radians(%f)) * sin(radians(origen_lat)))) AS distancia_origen,
                (6371 * acos(cos(radians(%f)) * cos(radians(destino_lat)) * cos(radians(destino_lng) - radians(%f)) + sin(radians(%f)) * sin(radians(destino_lat)))) AS distancia_destino
                FROM {$this->tabla_viajes} v
                WHERE v.estado = 'publicado'
                AND v.plazas_disponibles >= %d
                AND DATE(v.fecha_hora) = %s
                AND v.fecha_hora > NOW()
                HAVING distancia_origen <= %d AND distancia_destino <= %d
                ORDER BY v.fecha_hora ASC
                LIMIT 50";

        $viajes = $wpdb->get_results($wpdb->prepare(
            $sql,
            $origen_lat, $origen_lng, $origen_lat,
            $destino_lat, $destino_lng, $destino_lat,
            $plazas_requeridas, $fecha, $radio_km, $radio_km
        ));

        $viajes_formateados = [];
        foreach ($viajes as $viaje) {
            $viajes_formateados[] = $this->formatear_viaje_para_respuesta($viaje);
        }

        return [
            'success' => true,
            'total' => count($viajes_formateados),
            'viajes' => $viajes_formateados,
            'filtros' => [
                'fecha' => $fecha,
                'radio_km' => $radio_km,
                'plazas' => $plazas_requeridas,
            ],
        ];
    }

    /**
     * Accion: Publicar viaje
     */
    private function action_publicar_viaje($params) {
        global $wpdb;

        $conductor_id = get_current_user_id();
        if (!$conductor_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion para publicar un viaje.', 'flavor-chat-ia')];
        }

        // Validar datos requeridos
        $campos_requeridos = ['origen', 'origen_lat', 'origen_lng', 'destino', 'destino_lat', 'destino_lng', 'fecha_hora', 'plazas'];
        foreach ($campos_requeridos as $campo) {
            if (empty($params[$campo])) {
                return ['success' => false, 'error' => sprintf(__('El campo %s es obligatorio.', 'flavor-chat-ia'), $campo)];
            }
        }

        // Calcular precio si es automatico
        $precio_por_plaza = floatval($params['precio'] ?? 0);
        $precio_calculado_auto = false;

        if ($precio_por_plaza <= 0 && $this->settings['calculo_coste_automatico']) {
            $distancia_km = $this->calcular_distancia_km(
                floatval($params['origen_lat']),
                floatval($params['origen_lng']),
                floatval($params['destino_lat']),
                floatval($params['destino_lng'])
            );
            $precio_por_plaza = round($distancia_km * $this->settings['precio_por_km'], 2);
            $precio_calculado_auto = true;
        }

        $plazas = min(absint($params['plazas']), $this->settings['max_pasajeros_por_viaje']);

        $datos_viaje = [
            'conductor_id' => $conductor_id,
            'vehiculo_id' => absint($params['vehiculo_id'] ?? 0) ?: null,
            'origen' => sanitize_text_field($params['origen']),
            'origen_lat' => floatval($params['origen_lat']),
            'origen_lng' => floatval($params['origen_lng']),
            'origen_place_id' => sanitize_text_field($params['origen_place_id'] ?? ''),
            'destino' => sanitize_text_field($params['destino']),
            'destino_lat' => floatval($params['destino_lat']),
            'destino_lng' => floatval($params['destino_lng']),
            'destino_place_id' => sanitize_text_field($params['destino_place_id'] ?? ''),
            'fecha_hora' => sanitize_text_field($params['fecha_hora']),
            'plazas_disponibles' => $plazas,
            'plazas_totales' => $plazas,
            'precio_por_plaza' => $precio_por_plaza,
            'precio_calculado_auto' => $precio_calculado_auto ? 1 : 0,
            'permite_fumar' => !empty($params['permite_fumar']) ? 1 : 0,
            'permite_mascotas' => !empty($params['permite_mascotas']) ? 1 : 0,
            'permite_equipaje_grande' => !empty($params['permite_equipaje_grande']) ? 1 : 0,
            'solo_mujeres' => !empty($params['solo_mujeres']) ? 1 : 0,
            'notas' => sanitize_textarea_field($params['notas'] ?? ''),
            'estado' => 'publicado',
        ];

        if (!empty($params['paradas_intermedias'])) {
            $datos_viaje['paradas_intermedias'] = wp_json_encode($params['paradas_intermedias']);
        }

        if (!empty($params['preferencias'])) {
            $datos_viaje['preferencias'] = wp_json_encode($params['preferencias']);
        }

        $resultado = $wpdb->insert($this->tabla_viajes, $datos_viaje);

        if ($resultado === false) {
            return ['success' => false, 'error' => __('Error al publicar el viaje.', 'flavor-chat-ia')];
        }

        $viaje_id = $wpdb->insert_id;

        return [
            'success' => true,
            'message' => __('Viaje publicado correctamente.', 'flavor-chat-ia'),
            'viaje_id' => $viaje_id,
            'viaje' => $this->obtener_viaje($viaje_id),
        ];
    }

    /**
     * Accion: Reservar plaza
     */
    private function action_reservar_plaza($params) {
        global $wpdb;

        $pasajero_id = get_current_user_id();
        if (!$pasajero_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion para reservar.', 'flavor-chat-ia')];
        }

        $viaje_id = absint($params['viaje_id'] ?? 0);
        if (!$viaje_id) {
            return ['success' => false, 'error' => __('ID de viaje invalido.', 'flavor-chat-ia')];
        }

        $viaje = $this->obtener_viaje($viaje_id);
        if (!$viaje) {
            return ['success' => false, 'error' => __('Viaje no encontrado.', 'flavor-chat-ia')];
        }

        // Validaciones
        if ($viaje->conductor_id == $pasajero_id) {
            return ['success' => false, 'error' => __('No puedes reservar en tu propio viaje.', 'flavor-chat-ia')];
        }

        if ($viaje->estado !== 'publicado') {
            return ['success' => false, 'error' => __('Este viaje ya no esta disponible.', 'flavor-chat-ia')];
        }

        $plazas_solicitadas = absint($params['plazas'] ?? 1);
        if ($plazas_solicitadas > $viaje->plazas_disponibles) {
            return ['success' => false, 'error' => __('No hay suficientes plazas disponibles.', 'flavor-chat-ia')];
        }

        // Verificar si ya tiene reserva en este viaje
        $reserva_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_reservas} WHERE viaje_id = %d AND pasajero_id = %d AND estado NOT IN ('cancelada', 'rechazada')",
            $viaje_id, $pasajero_id
        ));

        if ($reserva_existente) {
            return ['success' => false, 'error' => __('Ya tienes una reserva en este viaje.', 'flavor-chat-ia')];
        }

        $coste_total = $viaje->precio_por_plaza * $plazas_solicitadas;

        $datos_reserva = [
            'viaje_id' => $viaje_id,
            'pasajero_id' => $pasajero_id,
            'plazas_reservadas' => $plazas_solicitadas,
            'coste_total' => $coste_total,
            'mensaje_pasajero' => sanitize_textarea_field($params['mensaje'] ?? ''),
            'telefono_contacto' => sanitize_text_field($params['telefono'] ?? ''),
            'estado' => 'solicitada',
        ];

        if (!empty($params['punto_recogida'])) {
            $datos_reserva['punto_recogida'] = sanitize_text_field($params['punto_recogida']);
            $datos_reserva['punto_recogida_lat'] = floatval($params['punto_recogida_lat'] ?? 0);
            $datos_reserva['punto_recogida_lng'] = floatval($params['punto_recogida_lng'] ?? 0);
        }

        $resultado = $wpdb->insert($this->tabla_reservas, $datos_reserva);

        if ($resultado === false) {
            return ['success' => false, 'error' => __('Error al crear la reserva.', 'flavor-chat-ia')];
        }

        $reserva_id = $wpdb->insert_id;

        // Notificar al conductor
        $this->enviar_notificacion_nueva_reserva($reserva_id);

        return [
            'success' => true,
            'message' => __('Reserva solicitada. El conductor debe confirmarla.', 'flavor-chat-ia'),
            'reserva_id' => $reserva_id,
            'coste_total' => $coste_total,
        ];
    }

    /**
     * Accion: Confirmar o rechazar reserva
     */
    private function action_confirmar_reserva($params) {
        global $wpdb;

        $conductor_id = get_current_user_id();
        $reserva_id = absint($params['reserva_id'] ?? 0);
        $accion = sanitize_text_field($params['accion'] ?? '');

        if (!in_array($accion, ['confirmar', 'rechazar'])) {
            return ['success' => false, 'error' => __('Accion no valida.', 'flavor-chat-ia')];
        }

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.plazas_disponibles
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva || $reserva->conductor_id != $conductor_id) {
            return ['success' => false, 'error' => __('No tienes permiso para gestionar esta reserva.', 'flavor-chat-ia')];
        }

        if ($reserva->estado !== 'solicitada') {
            return ['success' => false, 'error' => __('Esta reserva ya fue procesada.', 'flavor-chat-ia')];
        }

        if ($accion === 'confirmar') {
            if ($reserva->plazas_reservadas > $reserva->plazas_disponibles) {
                return ['success' => false, 'error' => __('No hay suficientes plazas disponibles.', 'flavor-chat-ia')];
            }

            $wpdb->update(
                $this->tabla_reservas,
                ['estado' => 'confirmada', 'fecha_confirmacion' => current_time('mysql')],
                ['id' => $reserva_id]
            );

            // Actualizar plazas disponibles
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_viajes} SET plazas_disponibles = plazas_disponibles - %d WHERE id = %d",
                $reserva->plazas_reservadas, $reserva->viaje_id
            ));

            // Verificar si el viaje esta completo
            $plazas_restantes = $wpdb->get_var($wpdb->prepare(
                "SELECT plazas_disponibles FROM {$this->tabla_viajes} WHERE id = %d",
                $reserva->viaje_id
            ));

            if ($plazas_restantes <= 0) {
                $wpdb->update($this->tabla_viajes, ['estado' => 'completo'], ['id' => $reserva->viaje_id]);
            }

            $this->enviar_notificacion_reserva_confirmada($reserva_id);
            $mensaje = __('Reserva confirmada.', 'flavor-chat-ia');
        } else {
            $wpdb->update(
                $this->tabla_reservas,
                [
                    'estado' => 'rechazada',
                    'motivo_rechazo' => sanitize_textarea_field($params['motivo'] ?? ''),
                ],
                ['id' => $reserva_id]
            );

            $this->enviar_notificacion_reserva_rechazada($reserva_id);
            $mensaje = __('Reserva rechazada.', 'flavor-chat-ia');
        }

        return ['success' => true, 'message' => $mensaje];
    }

    /**
     * Accion: Cancelar reserva
     */
    private function action_cancelar_reserva($params) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $reserva_id = absint($params['reserva_id'] ?? 0);

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return ['success' => false, 'error' => __('Reserva no encontrada.', 'flavor-chat-ia')];
        }

        $es_pasajero = $reserva->pasajero_id == $usuario_id;
        $es_conductor = $reserva->conductor_id == $usuario_id;

        if (!$es_pasajero && !$es_conductor) {
            return ['success' => false, 'error' => __('No tienes permiso para cancelar esta reserva.', 'flavor-chat-ia')];
        }

        if (!in_array($reserva->estado, ['solicitada', 'confirmada'])) {
            return ['success' => false, 'error' => __('Esta reserva no puede ser cancelada.', 'flavor-chat-ia')];
        }

        // Verificar tiempo minimo de cancelacion
        $horas_hasta_viaje = (strtotime($reserva->fecha_hora) - time()) / 3600;
        if ($horas_hasta_viaje < $this->settings['tiempo_minimo_cancelacion_horas'] && $reserva->estado === 'confirmada') {
            return [
                'success' => false,
                'error' => sprintf(
                    __('No puedes cancelar con menos de %d horas de antelacion.', 'flavor-chat-ia'),
                    $this->settings['tiempo_minimo_cancelacion_horas']
                ),
            ];
        }

        // Si estaba confirmada, devolver plazas
        if ($reserva->estado === 'confirmada') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_viajes} SET plazas_disponibles = plazas_disponibles + %d, estado = 'publicado' WHERE id = %d",
                $reserva->plazas_reservadas, $reserva->viaje_id
            ));
        }

        $wpdb->update(
            $this->tabla_reservas,
            [
                'estado' => 'cancelada',
                'motivo_cancelacion' => sanitize_textarea_field($params['motivo'] ?? ''),
                'cancelado_por' => $es_pasajero ? 'pasajero' : 'conductor',
                'fecha_cancelacion' => current_time('mysql'),
            ],
            ['id' => $reserva_id]
        );

        // Notificar a la otra parte
        if ($es_pasajero) {
            $this->enviar_notificacion_cancelacion_conductor($reserva_id);
        } else {
            $this->enviar_notificacion_cancelacion_pasajero($reserva_id);
        }

        return ['success' => true, 'message' => __('Reserva cancelada correctamente.', 'flavor-chat-ia')];
    }

    /**
     * Accion: Valorar viaje
     */
    private function action_valorar_viaje($params) {
        global $wpdb;

        $valorador_id = get_current_user_id();
        $reserva_id = absint($params['reserva_id'] ?? 0);
        $puntuacion = absint($params['puntuacion'] ?? 0);

        if ($puntuacion < 1 || $puntuacion > 5) {
            return ['success' => false, 'error' => __('La puntuacion debe estar entre 1 y 5.', 'flavor-chat-ia')];
        }

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d AND r.estado = 'completada'",
            $reserva_id
        ));

        if (!$reserva) {
            return ['success' => false, 'error' => __('Solo puedes valorar viajes completados.', 'flavor-chat-ia')];
        }

        $es_pasajero = $reserva->pasajero_id == $valorador_id;
        $es_conductor = $reserva->conductor_id == $valorador_id;

        if (!$es_pasajero && !$es_conductor) {
            return ['success' => false, 'error' => __('No participaste en este viaje.', 'flavor-chat-ia')];
        }

        // Verificar si ya valoro
        $valoracion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_valoraciones} WHERE reserva_id = %d AND valorador_id = %d",
            $reserva_id, $valorador_id
        ));

        if ($valoracion_existente) {
            return ['success' => false, 'error' => __('Ya has valorado este viaje.', 'flavor-chat-ia')];
        }

        $tipo = $es_pasajero ? 'conductor' : 'pasajero';
        $valorado_id = $es_pasajero ? $reserva->conductor_id : $reserva->pasajero_id;

        $datos_valoracion = [
            'viaje_id' => $reserva->viaje_id,
            'reserva_id' => $reserva_id,
            'valorador_id' => $valorador_id,
            'valorado_id' => $valorado_id,
            'tipo' => $tipo,
            'puntuacion' => $puntuacion,
            'comentario' => sanitize_textarea_field($params['comentario'] ?? ''),
        ];

        // Aspectos detallados
        if (!empty($params['puntualidad'])) {
            $datos_valoracion['puntualidad'] = absint($params['puntualidad']);
        }
        if (!empty($params['amabilidad'])) {
            $datos_valoracion['amabilidad'] = absint($params['amabilidad']);
        }
        if (!empty($params['limpieza'])) {
            $datos_valoracion['limpieza'] = absint($params['limpieza']);
        }
        if (!empty($params['conduccion'])) {
            $datos_valoracion['conduccion'] = absint($params['conduccion']);
        }
        if (!empty($params['comunicacion'])) {
            $datos_valoracion['comunicacion'] = absint($params['comunicacion']);
        }

        $resultado = $wpdb->insert($this->tabla_valoraciones, $datos_valoracion);

        if ($resultado === false) {
            return ['success' => false, 'error' => __('Error al guardar la valoracion.', 'flavor-chat-ia')];
        }

        // Marcar reserva como valorada
        $wpdb->update($this->tabla_reservas, ['valoracion_realizada' => 1], ['id' => $reserva_id]);

        return ['success' => true, 'message' => __('Valoracion guardada correctamente.', 'flavor-chat-ia')];
    }

    /**
     * Accion: Obtener valoraciones de usuario
     */
    private function action_obtener_valoraciones($params) {
        global $wpdb;

        $usuario_id = absint($params['usuario_id'] ?? 0);
        $tipo = sanitize_text_field($params['tipo'] ?? '');

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Usuario no especificado.', 'flavor-chat-ia')];
        }

        $where_tipo = '';
        if (in_array($tipo, ['conductor', 'pasajero'])) {
            $where_tipo = $wpdb->prepare(" AND tipo = %s", $tipo);
        }

        $valoraciones = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name as nombre_valorador
             FROM {$this->tabla_valoraciones} v
             JOIN {$wpdb->users} u ON v.valorador_id = u.ID
             WHERE v.valorado_id = %d AND v.visible = 1 {$where_tipo}
             ORDER BY v.fecha_creacion DESC
             LIMIT 50",
            $usuario_id
        ));

        // Calcular estadisticas
        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_valoraciones,
                AVG(puntuacion) as promedio_general,
                AVG(puntualidad) as promedio_puntualidad,
                AVG(amabilidad) as promedio_amabilidad,
                AVG(limpieza) as promedio_limpieza,
                AVG(conduccion) as promedio_conduccion,
                AVG(comunicacion) as promedio_comunicacion
             FROM {$this->tabla_valoraciones}
             WHERE valorado_id = %d AND visible = 1 {$where_tipo}",
            $usuario_id
        ));

        return [
            'success' => true,
            'valoraciones' => $valoraciones,
            'estadisticas' => [
                'total' => intval($estadisticas->total_valoraciones),
                'promedio' => round(floatval($estadisticas->promedio_general), 1),
                'puntualidad' => round(floatval($estadisticas->promedio_puntualidad), 1),
                'amabilidad' => round(floatval($estadisticas->promedio_amabilidad), 1),
                'limpieza' => round(floatval($estadisticas->promedio_limpieza), 1),
                'conduccion' => round(floatval($estadisticas->promedio_conduccion), 1),
                'comunicacion' => round(floatval($estadisticas->promedio_comunicacion), 1),
            ],
        ];
    }

    /**
     * Accion: Mis viajes como conductor
     */
    private function action_mis_viajes_conductor($params) {
        global $wpdb;

        $conductor_id = get_current_user_id();
        if (!$conductor_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $estado = sanitize_text_field($params['estado'] ?? '');
        $limite = absint($params['limite'] ?? 20);

        $where_estado = '';
        if (!empty($estado) && in_array($estado, ['publicado', 'completo', 'en_curso', 'finalizado', 'cancelado'])) {
            $where_estado = $wpdb->prepare(" AND estado = %s", $estado);
        }

        $viajes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_viajes}
             WHERE conductor_id = %d {$where_estado}
             ORDER BY fecha_hora DESC
             LIMIT %d",
            $conductor_id, $limite
        ));

        $viajes_formateados = [];
        foreach ($viajes as $viaje) {
            $viaje_data = $this->formatear_viaje_para_respuesta($viaje);

            // Agregar reservas
            $viaje_data['reservas'] = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, u.display_name as nombre_pasajero
                 FROM {$this->tabla_reservas} r
                 JOIN {$wpdb->users} u ON r.pasajero_id = u.ID
                 WHERE r.viaje_id = %d
                 ORDER BY r.fecha_solicitud DESC",
                $viaje->id
            ));

            $viajes_formateados[] = $viaje_data;
        }

        return [
            'success' => true,
            'total' => count($viajes_formateados),
            'viajes' => $viajes_formateados,
        ];
    }

    /**
     * Accion: Mis viajes como pasajero
     */
    private function action_mis_viajes_pasajero($params) {
        global $wpdb;

        $pasajero_id = get_current_user_id();
        if (!$pasajero_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $estado = sanitize_text_field($params['estado'] ?? '');
        $limite = absint($params['limite'] ?? 20);

        $where_estado = '';
        if (!empty($estado) && in_array($estado, ['solicitada', 'confirmada', 'rechazada', 'cancelada', 'completada'])) {
            $where_estado = $wpdb->prepare(" AND r.estado = %s", $estado);
        }

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, v.*, r.id as reserva_id, r.estado as estado_reserva,
                    u.display_name as nombre_conductor
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             JOIN {$wpdb->users} u ON v.conductor_id = u.ID
             WHERE r.pasajero_id = %d {$where_estado}
             ORDER BY v.fecha_hora DESC
             LIMIT %d",
            $pasajero_id, $limite
        ));

        return [
            'success' => true,
            'total' => count($reservas),
            'reservas' => $reservas,
        ];
    }

    /**
     * Accion: Gestionar vehiculo
     */
    private function action_gestionar_vehiculo($params) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $vehiculo_id = absint($params['vehiculo_id'] ?? 0);
        $datos = $params['datos'] ?? [];

        $datos_vehiculo = [
            'usuario_id' => $usuario_id,
            'marca' => sanitize_text_field($datos['marca'] ?? ''),
            'modelo' => sanitize_text_field($datos['modelo'] ?? ''),
            'anio' => absint($datos['anio'] ?? 0) ?: null,
            'color' => sanitize_text_field($datos['color'] ?? ''),
            'matricula' => strtoupper(sanitize_text_field($datos['matricula'] ?? '')),
            'tipo' => in_array($datos['tipo'] ?? '', ['coche', 'moto', 'furgoneta']) ? $datos['tipo'] : 'coche',
            'plazas_disponibles' => absint($datos['plazas'] ?? 4),
            'tiene_aire_acondicionado' => !empty($datos['aire_acondicionado']) ? 1 : 0,
            'tiene_maletero_grande' => !empty($datos['maletero_grande']) ? 1 : 0,
            'es_predeterminado' => !empty($datos['predeterminado']) ? 1 : 0,
        ];

        if (!empty($datos['foto_url'])) {
            $datos_vehiculo['foto_url'] = esc_url_raw($datos['foto_url']);
        }

        if ($vehiculo_id) {
            // Verificar propiedad
            $propietario = $wpdb->get_var($wpdb->prepare(
                "SELECT usuario_id FROM {$this->tabla_vehiculos} WHERE id = %d",
                $vehiculo_id
            ));

            if ($propietario != $usuario_id) {
                return ['success' => false, 'error' => __('No tienes permiso para editar este vehiculo.', 'flavor-chat-ia')];
            }

            $wpdb->update($this->tabla_vehiculos, $datos_vehiculo, ['id' => $vehiculo_id]);
            $mensaje = __('Vehiculo actualizado correctamente.', 'flavor-chat-ia');
        } else {
            $wpdb->insert($this->tabla_vehiculos, $datos_vehiculo);
            $vehiculo_id = $wpdb->insert_id;
            $mensaje = __('Vehiculo agregado correctamente.', 'flavor-chat-ia');
        }

        // Si es predeterminado, quitar de otros
        if (!empty($datos['predeterminado'])) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_vehiculos} SET es_predeterminado = 0 WHERE usuario_id = %d AND id != %d",
                $usuario_id, $vehiculo_id
            ));
        }

        return [
            'success' => true,
            'message' => $mensaje,
            'vehiculo_id' => $vehiculo_id,
        ];
    }

    /**
     * Accion: Crear ruta recurrente
     */
    private function action_crear_ruta_recurrente($params) {
        global $wpdb;

        $conductor_id = get_current_user_id();
        if (!$conductor_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $campos_requeridos = ['origen', 'origen_lat', 'origen_lng', 'destino', 'destino_lat', 'destino_lng', 'hora', 'dias', 'plazas', 'precio'];
        foreach ($campos_requeridos as $campo) {
            if (empty($params[$campo])) {
                return ['success' => false, 'error' => sprintf(__('El campo %s es obligatorio.', 'flavor-chat-ia'), $campo)];
            }
        }

        $dias_semana = is_array($params['dias']) ? $params['dias'] : json_decode($params['dias'], true);
        if (empty($dias_semana)) {
            return ['success' => false, 'error' => __('Debes seleccionar al menos un dia.', 'flavor-chat-ia')];
        }

        $datos_ruta = [
            'conductor_id' => $conductor_id,
            'vehiculo_id' => absint($params['vehiculo_id'] ?? 0) ?: null,
            'nombre' => sanitize_text_field($params['nombre'] ?? ''),
            'origen' => sanitize_text_field($params['origen']),
            'origen_lat' => floatval($params['origen_lat']),
            'origen_lng' => floatval($params['origen_lng']),
            'destino' => sanitize_text_field($params['destino']),
            'destino_lat' => floatval($params['destino_lat']),
            'destino_lng' => floatval($params['destino_lng']),
            'hora_salida' => sanitize_text_field($params['hora']),
            'dias_semana' => wp_json_encode(array_map('absint', $dias_semana)),
            'plazas' => absint($params['plazas']),
            'precio_por_plaza' => floatval($params['precio']),
            'activa' => 1,
            'fecha_inicio' => !empty($params['fecha_inicio']) ? sanitize_text_field($params['fecha_inicio']) : null,
            'fecha_fin' => !empty($params['fecha_fin']) ? sanitize_text_field($params['fecha_fin']) : null,
        ];

        if (!empty($params['preferencias'])) {
            $datos_ruta['preferencias'] = wp_json_encode($params['preferencias']);
        }

        $resultado = $wpdb->insert($this->tabla_rutas_recurrentes, $datos_ruta);

        if ($resultado === false) {
            return ['success' => false, 'error' => __('Error al crear la ruta recurrente.', 'flavor-chat-ia')];
        }

        $ruta_id = $wpdb->insert_id;

        // Generar viajes para los proximos 7 dias
        $this->generar_viajes_para_ruta($ruta_id, 7);

        return [
            'success' => true,
            'message' => __('Ruta recurrente creada. Se han generado viajes para los proximos dias.', 'flavor-chat-ia'),
            'ruta_id' => $ruta_id,
        ];
    }

    /**
     * Accion: Estadisticas carpooling (admin)
     */
    private function action_estadisticas_carpooling($params) {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('Sin permisos.', 'flavor-chat-ia')];
        }

        $periodo = sanitize_text_field($params['periodo'] ?? '30');
        $fecha_inicio = date('Y-m-d', strtotime("-{$periodo} days"));

        $estadisticas = [
            'viajes_publicados' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE fecha_creacion >= %s",
                $fecha_inicio
            )),
            'viajes_completados' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE estado = 'finalizado' AND fecha_creacion >= %s",
                $fecha_inicio
            )),
            'reservas_totales' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE fecha_solicitud >= %s",
                $fecha_inicio
            )),
            'reservas_confirmadas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE estado = 'confirmada' AND fecha_solicitud >= %s",
                $fecha_inicio
            )),
            'conductores_activos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT conductor_id) FROM {$this->tabla_viajes} WHERE fecha_creacion >= %s",
                $fecha_inicio
            )),
            'pasajeros_activos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pasajero_id) FROM {$this->tabla_reservas} WHERE fecha_solicitud >= %s",
                $fecha_inicio
            )),
            'valoracion_promedio' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(puntuacion) FROM {$this->tabla_valoraciones} WHERE fecha_creacion >= %s",
                $fecha_inicio
            )),
            'rutas_populares' => $wpdb->get_results($wpdb->prepare(
                "SELECT origen, destino, COUNT(*) as total
                 FROM {$this->tabla_viajes}
                 WHERE fecha_creacion >= %s
                 GROUP BY origen, destino
                 ORDER BY total DESC
                 LIMIT 10",
                $fecha_inicio
            )),
        ];

        return [
            'success' => true,
            'periodo_dias' => $periodo,
            'estadisticas' => $estadisticas,
        ];
    }

    // ========================================
    // AJAX HANDLERS
    // ========================================

    /**
     * AJAX: Buscar viajes
     */
    public function ajax_buscar_viajes() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_buscar_viajes($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Publicar viaje
     */
    public function ajax_publicar_viaje() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_publicar_viaje($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Reservar plaza
     */
    public function ajax_reservar_plaza() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_reservar_plaza($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_cancelar_reserva($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Confirmar reserva
     */
    public function ajax_confirmar_reserva() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_confirmar_reserva($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Valorar viaje
     */
    public function ajax_valorar_viaje() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_valorar_viaje($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Cancelar viaje (conductor)
     */
    public function ajax_cancelar_viaje() {
        check_ajax_referer('carpooling_nonce', 'nonce');
        global $wpdb;

        $conductor_id = get_current_user_id();
        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        $viaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_viajes} WHERE id = %d AND conductor_id = %d",
            $viaje_id, $conductor_id
        ));

        if (!$viaje) {
            wp_send_json(['success' => false, 'error' => __('Viaje no encontrado.', 'flavor-chat-ia')]);
        }

        if (!in_array($viaje->estado, ['publicado', 'completo'])) {
            wp_send_json(['success' => false, 'error' => __('Este viaje no puede cancelarse.', 'flavor-chat-ia')]);
        }

        // Cancelar viaje
        $wpdb->update(
            $this->tabla_viajes,
            ['estado' => 'cancelado', 'motivo_cancelacion' => $motivo],
            ['id' => $viaje_id]
        );

        // Cancelar todas las reservas pendientes/confirmadas
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, pasajero_id FROM {$this->tabla_reservas} WHERE viaje_id = %d AND estado IN ('solicitada', 'confirmada')",
            $viaje_id
        ));

        foreach ($reservas as $reserva) {
            $wpdb->update(
                $this->tabla_reservas,
                [
                    'estado' => 'cancelada',
                    'motivo_cancelacion' => __('Viaje cancelado por el conductor.', 'flavor-chat-ia'),
                    'cancelado_por' => 'conductor',
                    'fecha_cancelacion' => current_time('mysql'),
                ],
                ['id' => $reserva->id]
            );
            $this->enviar_notificacion_cancelacion_pasajero($reserva->id);
        }

        wp_send_json(['success' => true, 'message' => __('Viaje cancelado. Se ha notificado a los pasajeros.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Mis viajes
     */
    public function ajax_mis_viajes() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_mis_viajes_conductor($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Mis reservas
     */
    public function ajax_mis_reservas() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_mis_viajes_pasajero($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Guardar vehiculo
     */
    public function ajax_guardar_vehiculo() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_gestionar_vehiculo([
            'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
            'datos' => $_POST,
        ]);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener vehiculos del usuario
     */
    public function ajax_obtener_vehiculos() {
        check_ajax_referer('carpooling_nonce', 'nonce');
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        $vehiculos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_vehiculos} WHERE usuario_id = %d AND activo = 1 ORDER BY es_predeterminado DESC, fecha_creacion DESC",
            $usuario_id
        ));

        wp_send_json(['success' => true, 'vehiculos' => $vehiculos]);
    }

    /**
     * AJAX: Crear ruta recurrente
     */
    public function ajax_crear_ruta_recurrente() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $resultado = $this->action_crear_ruta_recurrente($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX: Autocompletar lugar
     */
    public function ajax_autocompletar_lugar() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $termino = sanitize_text_field($_POST['termino'] ?? '');
        if (strlen($termino) < 3) {
            wp_send_json(['success' => true, 'lugares' => []]);
        }

        // Usar Nominatim de OpenStreetMap para autocompletado gratuito
        $url = add_query_arg([
            'q' => $termino,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 5,
            'countrycodes' => 'es',
        ], 'https://nominatim.openstreetmap.org/search');

        $respuesta = wp_remote_get($url, [
            'headers' => ['User-Agent' => 'FlavorChatIA/1.0'],
            'timeout' => 5,
        ]);

        if (is_wp_error($respuesta)) {
            wp_send_json(['success' => false, 'error' => __('Error de conexion', 'flavor-chat-ia')]);
        }

        $lugares_raw = json_decode(wp_remote_retrieve_body($respuesta), true);
        $lugares = [];

        if (is_array($lugares_raw)) {
            foreach ($lugares_raw as $lugar) {
                $lugares[] = [
                    'nombre' => $lugar['display_name'],
                    'lat' => floatval($lugar['lat']),
                    'lng' => floatval($lugar['lon']),
                    'place_id' => $lugar['place_id'] ?? '',
                    'tipo' => $lugar['type'] ?? '',
                ];
            }
        }

        wp_send_json(['success' => true, 'lugares' => $lugares]);
    }

    /**
     * AJAX: Detalle de viaje
     */
    public function ajax_detalle_viaje() {
        check_ajax_referer('carpooling_nonce', 'nonce');

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $viaje = $this->obtener_viaje($viaje_id);

        if (!$viaje) {
            wp_send_json(['success' => false, 'error' => __('Viaje no encontrado.', 'flavor-chat-ia')]);
        }

        // Incrementar visualizaciones
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_viajes} SET visualizaciones = visualizaciones + 1 WHERE id = %d",
            $viaje_id
        ));

        $viaje_data = $this->formatear_viaje_para_respuesta($viaje, true);

        wp_send_json(['success' => true, 'viaje' => $viaje_data]);
    }

    // ========================================
    // REST API
    // ========================================

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('carpooling/v1', '/viajes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_viajes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('carpooling/v1', '/viajes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_viaje'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('carpooling/v1', '/viajes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_viaje'],
            'permission_callback' => [$this, 'rest_usuario_autenticado'],
        ]);

        register_rest_route('carpooling/v1', '/reservas', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_reserva'],
            'permission_callback' => [$this, 'rest_usuario_autenticado'],
        ]);

        register_rest_route('carpooling/v1', '/usuario/(?P<id>\d+)/valoraciones', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_valoraciones_usuario'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Permiso: Usuario autenticado
     */
    public function rest_usuario_autenticado() {
        return is_user_logged_in();
    }

    /**
     * REST: Obtener viajes
     */
    public function rest_obtener_viajes($request) {
        $params = [
            'origen_lat' => $request->get_param('origen_lat'),
            'origen_lng' => $request->get_param('origen_lng'),
            'destino_lat' => $request->get_param('destino_lat'),
            'destino_lng' => $request->get_param('destino_lng'),
            'fecha' => $request->get_param('fecha'),
            'radio_km' => $request->get_param('radio'),
            'plazas' => $request->get_param('plazas'),
        ];

        $resultado = $this->action_buscar_viajes($params);
        return new WP_REST_Response($this->sanitize_public_carpooling_response($resultado), $resultado['success'] ? 200 : 400);
    }

    /**
     * REST: Obtener viaje individual
     */
    public function rest_obtener_viaje($request) {
        $viaje_id = absint($request['id']);
        $viaje = $this->obtener_viaje($viaje_id);

        if (!$viaje) {
            return new WP_REST_Response(['success' => false, 'error' => __('Viaje no encontrado', 'flavor-chat-ia')], 404);
        }

        $respuesta = [
            'success' => true,
            'viaje' => $this->formatear_viaje_para_respuesta($viaje, true),
        ];

        return new WP_REST_Response($this->sanitize_public_carpooling_response($respuesta), 200);
    }

    /**
     * REST: Crear viaje
     */
    public function rest_crear_viaje($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_publicar_viaje($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 201 : 400);
    }

    /**
     * REST: Crear reserva
     */
    public function rest_crear_reserva($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_reservar_plaza($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 201 : 400);
    }

    /**
     * REST: Obtener valoraciones de usuario
     */
    public function rest_obtener_valoraciones_usuario($request) {
        $resultado = $this->action_obtener_valoraciones([
            'usuario_id' => absint($request['id']),
            'tipo' => $request->get_param('tipo'),
        ]);
        return new WP_REST_Response($this->sanitize_public_carpooling_response($resultado), 200);
    }

    private function sanitize_public_carpooling_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['viajes']) && is_array($respuesta['viajes'])) {
            $respuesta['viajes'] = array_map([$this, 'sanitize_public_viaje'], $respuesta['viajes']);
        }

        if (!empty($respuesta['viaje']) && is_array($respuesta['viaje'])) {
            $respuesta['viaje'] = $this->sanitize_public_viaje($respuesta['viaje']);
        }

        if (!empty($respuesta['valoraciones']) && is_array($respuesta['valoraciones'])) {
            $respuesta['valoraciones'] = array_map(function($valoracion) {
                if (!is_object($valoracion) && !is_array($valoracion)) {
                    return $valoracion;
                }

                if (is_object($valoracion)) {
                    unset($valoracion->valorador_id);
                    return $valoracion;
                }

                unset($valoracion['valorador_id']);
                return $valoracion;
            }, $respuesta['valoraciones']);
        }

        return $respuesta;
    }

    private function sanitize_public_viaje($viaje) {
        if (!is_array($viaje)) {
            return $viaje;
        }

        if (!empty($viaje['conductor']) && is_array($viaje['conductor'])) {
            unset($viaje['conductor']['id']);
            $viaje['conductor']['avatar'] = '';
        }

        return $viaje;
    }

    // ========================================
    // SHORTCODES
    // ========================================

    /**
     * Shortcode: Buscar viaje
     */
    public function shortcode_buscar_viaje($atts) {
        wp_enqueue_style('carpooling-styles');
        wp_enqueue_script('carpooling-scripts');

        $atts = shortcode_atts([
            'mostrar_mapa' => 'true',
            'radio_defecto' => $this->settings['radio_busqueda_km'],
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/views/buscar-viaje.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Publicar viaje
     */
    public function shortcode_publicar_viaje($atts) {
        if (!is_user_logged_in()) {
            return '<div class="carpooling-login-required">' .
                   __('Debes iniciar sesion para publicar un viaje.', 'flavor-chat-ia') .
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a></div>';
        }

        wp_enqueue_style('carpooling-styles');
        wp_enqueue_script('carpooling-scripts');

        $atts = shortcode_atts([
            'mostrar_vehiculos' => 'true',
            'permite_recurrente' => 'true',
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/views/publicar-viaje.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis viajes
     */
    public function shortcode_mis_viajes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="carpooling-login-required">' .
                   __('Debes iniciar sesion para ver tus viajes.', 'flavor-chat-ia') .
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a></div>';
        }

        wp_enqueue_style('carpooling-styles');
        wp_enqueue_script('carpooling-scripts');

        ob_start();
        include dirname(__FILE__) . '/views/mis-viajes.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas
     */
    public function shortcode_mis_reservas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="carpooling-login-required">' .
                   __('Debes iniciar sesion para ver tus reservas.', 'flavor-chat-ia') .
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a></div>';
        }

        wp_enqueue_style('carpooling-styles');
        wp_enqueue_script('carpooling-scripts');

        ob_start();
        include dirname(__FILE__) . '/views/mis-reservas.php';
        return ob_get_clean();
    }

    // ========================================
    // METODOS AUXILIARES
    // ========================================

    /**
     * Obtener viaje por ID
     */
    private function obtener_viaje($viaje_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_viajes} WHERE id = %d",
            $viaje_id
        ));
    }

    /**
     * Formatear viaje para respuesta
     */
    private function formatear_viaje_para_respuesta($viaje, $incluir_detalles = false) {
        global $wpdb;

        $conductor = get_userdata($viaje->conductor_id);
        $valoraciones_conductor = $this->obtener_estadisticas_valoraciones($viaje->conductor_id, 'conductor');

        $datos = [
            'id' => intval($viaje->id),
            'conductor' => [
                'id' => intval($viaje->conductor_id),
                'nombre' => $conductor ? $conductor->display_name : __('Usuario', 'flavor-chat-ia'),
                'avatar' => get_avatar_url($viaje->conductor_id, ['size' => 80]),
                'valoracion' => $valoraciones_conductor['promedio'],
                'total_viajes' => $valoraciones_conductor['total'],
            ],
            'origen' => $viaje->origen,
            'origen_lat' => floatval($viaje->origen_lat),
            'origen_lng' => floatval($viaje->origen_lng),
            'destino' => $viaje->destino,
            'destino_lat' => floatval($viaje->destino_lat),
            'destino_lng' => floatval($viaje->destino_lng),
            'fecha_hora' => $viaje->fecha_hora,
            'fecha_formateada' => date_i18n('l, j M Y', strtotime($viaje->fecha_hora)),
            'hora_formateada' => date_i18n('H:i', strtotime($viaje->fecha_hora)),
            'plazas_disponibles' => intval($viaje->plazas_disponibles),
            'plazas_totales' => intval($viaje->plazas_totales),
            'precio_por_plaza' => floatval($viaje->precio_por_plaza),
            'estado' => $viaje->estado,
            'permite_fumar' => (bool) $viaje->permite_fumar,
            'permite_mascotas' => (bool) $viaje->permite_mascotas,
            'permite_equipaje_grande' => (bool) $viaje->permite_equipaje_grande,
            'solo_mujeres' => (bool) $viaje->solo_mujeres,
        ];

        if (isset($viaje->distancia_origen)) {
            $datos['distancia_origen_km'] = round($viaje->distancia_origen, 1);
        }
        if (isset($viaje->distancia_destino)) {
            $datos['distancia_destino_km'] = round($viaje->distancia_destino, 1);
        }

        if ($incluir_detalles) {
            $datos['notas'] = $viaje->notas;
            $datos['visualizaciones'] = intval($viaje->visualizaciones);
            $datos['paradas_intermedias'] = $viaje->paradas_intermedias ? json_decode($viaje->paradas_intermedias, true) : [];
            $datos['preferencias'] = $viaje->preferencias ? json_decode($viaje->preferencias, true) : [];

            // Vehiculo
            if ($viaje->vehiculo_id) {
                $vehiculo = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$this->tabla_vehiculos} WHERE id = %d",
                    $viaje->vehiculo_id
                ));
                if ($vehiculo) {
                    $datos['vehiculo'] = [
                        'marca' => $vehiculo->marca,
                        'modelo' => $vehiculo->modelo,
                        'color' => $vehiculo->color,
                        'anio' => $vehiculo->anio,
                        'foto_url' => $vehiculo->foto_url,
                        'verificado' => (bool) $vehiculo->verificado,
                    ];
                }
            }
        }

        return $datos;
    }

    /**
     * Obtener estadisticas de valoraciones de usuario
     */
    private function obtener_estadisticas_valoraciones($usuario_id, $tipo = null) {
        global $wpdb;

        $where_tipo = '';
        if ($tipo) {
            $where_tipo = $wpdb->prepare(" AND tipo = %s", $tipo);
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, AVG(puntuacion) as promedio
             FROM {$this->tabla_valoraciones}
             WHERE valorado_id = %d AND visible = 1 {$where_tipo}",
            $usuario_id
        ));

        return [
            'total' => intval($stats->total),
            'promedio' => $stats->promedio ? round(floatval($stats->promedio), 1) : 0,
        ];
    }

    /**
     * Calcular distancia en km usando formula Haversine
     */
    private function calcular_distancia_km($lat1, $lng1, $lat2, $lng2) {
        $radio_tierra_km = 6371;

        $lat1_rad = deg2rad($lat1);
        $lat2_rad = deg2rad($lat2);
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lng = deg2rad($lng2 - $lng1);

        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lng / 2) * sin($delta_lng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radio_tierra_km * $c;
    }

    /**
     * Generar viajes para ruta recurrente
     */
    private function generar_viajes_para_ruta($ruta_id, $dias_adelante = 7) {
        global $wpdb;

        $ruta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_rutas_recurrentes} WHERE id = %d AND activa = 1",
            $ruta_id
        ));

        if (!$ruta) {
            return;
        }

        $dias_semana = json_decode($ruta->dias_semana, true);
        $fecha_inicio = new DateTime();
        $fecha_fin = new DateTime("+{$dias_adelante} days");

        // Si la ruta tiene fecha inicio/fin, respetarlas
        if ($ruta->fecha_inicio && strtotime($ruta->fecha_inicio) > time()) {
            $fecha_inicio = new DateTime($ruta->fecha_inicio);
        }
        if ($ruta->fecha_fin) {
            $fecha_limite = new DateTime($ruta->fecha_fin);
            if ($fecha_fin > $fecha_limite) {
                $fecha_fin = $fecha_limite;
            }
        }

        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($fecha_inicio, $intervalo, $fecha_fin);

        foreach ($periodo as $fecha) {
            $dia_semana = (int) $fecha->format('N'); // 1=Lunes, 7=Domingo

            if (!in_array($dia_semana, $dias_semana)) {
                continue;
            }

            $fecha_hora_viaje = $fecha->format('Y-m-d') . ' ' . $ruta->hora_salida;

            // Verificar que no exista ya
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->tabla_viajes} WHERE ruta_recurrente_id = %d AND fecha_hora = %s",
                $ruta_id, $fecha_hora_viaje
            ));

            if ($existe) {
                continue;
            }

            // Crear viaje
            $preferencias = $ruta->preferencias ? json_decode($ruta->preferencias, true) : [];

            $wpdb->insert($this->tabla_viajes, [
                'conductor_id' => $ruta->conductor_id,
                'vehiculo_id' => $ruta->vehiculo_id,
                'origen' => $ruta->origen,
                'origen_lat' => $ruta->origen_lat,
                'origen_lng' => $ruta->origen_lng,
                'destino' => $ruta->destino,
                'destino_lat' => $ruta->destino_lat,
                'destino_lng' => $ruta->destino_lng,
                'fecha_hora' => $fecha_hora_viaje,
                'plazas_disponibles' => $ruta->plazas,
                'plazas_totales' => $ruta->plazas,
                'precio_por_plaza' => $ruta->precio_por_plaza,
                'permite_fumar' => $preferencias['permite_fumar'] ?? 0,
                'permite_mascotas' => $preferencias['permite_mascotas'] ?? 0,
                'permite_equipaje_grande' => $preferencias['permite_equipaje_grande'] ?? 0,
                'es_recurrente' => 1,
                'ruta_recurrente_id' => $ruta_id,
                'estado' => 'publicado',
            ]);
        }

        // Actualizar fecha ultima generacion
        $wpdb->update(
            $this->tabla_rutas_recurrentes,
            ['ultima_generacion' => current_time('Y-m-d')],
            ['id' => $ruta_id]
        );
    }

    /**
     * Cron: Generar viajes recurrentes
     */
    public function generar_viajes_recurrentes() {
        global $wpdb;

        $rutas_activas = $wpdb->get_results(
            "SELECT id FROM {$this->tabla_rutas_recurrentes}
             WHERE activa = 1
             AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())"
        );

        foreach ($rutas_activas as $ruta) {
            $this->generar_viajes_para_ruta($ruta->id, 14);
        }
    }

    // ========================================
    // NOTIFICACIONES
    // ========================================

    /**
     * Enviar notificacion de nueva reserva al conductor
     */
    private function enviar_notificacion_nueva_reserva($reserva_id) {
        if (!$this->settings['notificaciones_email']) {
            return;
        }

        global $wpdb;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.origen, v.destino, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return;
        }

        $conductor = get_userdata($reserva->conductor_id);
        $pasajero = get_userdata($reserva->pasajero_id);

        if (!$conductor || !$conductor->user_email) {
            return;
        }

        $asunto = sprintf(__('[Carpooling] Nueva solicitud de reserva para tu viaje', 'flavor-chat-ia'));

        $mensaje = sprintf(
            __("Hola %s,\n\n%s ha solicitado reservar %d plaza(s) en tu viaje:\n\nRuta: %s -> %s\nFecha: %s\n\nAccede a tu panel para confirmar o rechazar la reserva.\n\nSaludos,\nEl equipo de Carpooling", 'flavor-chat-ia'),
            $conductor->display_name,
            $pasajero ? $pasajero->display_name : __('Un usuario', 'flavor-chat-ia'),
            $reserva->plazas_reservadas,
            $reserva->origen,
            $reserva->destino,
            date_i18n('l j F Y, H:i', strtotime($reserva->fecha_hora))
        );

        wp_mail($conductor->user_email, $asunto, $mensaje);
    }

    /**
     * Enviar notificacion de reserva confirmada
     */
    private function enviar_notificacion_reserva_confirmada($reserva_id) {
        if (!$this->settings['notificaciones_email']) {
            return;
        }

        global $wpdb;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.origen, v.destino, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return;
        }

        $pasajero = get_userdata($reserva->pasajero_id);
        $conductor = get_userdata($reserva->conductor_id);

        if (!$pasajero || !$pasajero->user_email) {
            return;
        }

        $asunto = sprintf(__('[Carpooling] Tu reserva ha sido confirmada!', 'flavor-chat-ia'));

        $mensaje = sprintf(
            __("Hola %s,\n\nTu reserva ha sido confirmada!\n\nRuta: %s -> %s\nFecha: %s\nConductor: %s\nPlazas: %d\nCoste total: %.2f EUR\n\nNo olvides estar puntual en el punto de recogida.\n\nBuen viaje!", 'flavor-chat-ia'),
            $pasajero->display_name,
            $reserva->origen,
            $reserva->destino,
            date_i18n('l j F Y, H:i', strtotime($reserva->fecha_hora)),
            $conductor ? $conductor->display_name : '-',
            $reserva->plazas_reservadas,
            $reserva->coste_total
        );

        wp_mail($pasajero->user_email, $asunto, $mensaje);
    }

    /**
     * Enviar notificacion de reserva rechazada
     */
    private function enviar_notificacion_reserva_rechazada($reserva_id) {
        if (!$this->settings['notificaciones_email']) {
            return;
        }

        global $wpdb;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.origen, v.destino, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return;
        }

        $pasajero = get_userdata($reserva->pasajero_id);

        if (!$pasajero || !$pasajero->user_email) {
            return;
        }

        $asunto = sprintf(__('[Carpooling] Tu solicitud de reserva no ha sido aceptada', 'flavor-chat-ia'));

        $mensaje = sprintf(
            __("Hola %s,\n\nLamentamos informarte que tu solicitud de reserva no ha sido aceptada.\n\nRuta: %s -> %s\nFecha: %s\n\nMotivo: %s\n\nTe animamos a buscar otros viajes disponibles.\n\nSaludos,\nEl equipo de Carpooling", 'flavor-chat-ia'),
            $pasajero->display_name,
            $reserva->origen,
            $reserva->destino,
            date_i18n('l j F Y, H:i', strtotime($reserva->fecha_hora)),
            $reserva->motivo_rechazo ?: __('No especificado', 'flavor-chat-ia')
        );

        wp_mail($pasajero->user_email, $asunto, $mensaje);
    }

    /**
     * Enviar notificacion de cancelacion al conductor
     */
    private function enviar_notificacion_cancelacion_conductor($reserva_id) {
        if (!$this->settings['notificaciones_email']) {
            return;
        }

        global $wpdb;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.conductor_id, v.origen, v.destino, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return;
        }

        $conductor = get_userdata($reserva->conductor_id);
        $pasajero = get_userdata($reserva->pasajero_id);

        if (!$conductor || !$conductor->user_email) {
            return;
        }

        $asunto = sprintf(__('[Carpooling] Un pasajero ha cancelado su reserva', 'flavor-chat-ia'));

        $mensaje = sprintf(
            __("Hola %s,\n\n%s ha cancelado su reserva en tu viaje:\n\nRuta: %s -> %s\nFecha: %s\nPlazas liberadas: %d\n\nMotivo: %s\n\nLas plazas han vuelto a estar disponibles.\n\nSaludos,\nEl equipo de Carpooling", 'flavor-chat-ia'),
            $conductor->display_name,
            $pasajero ? $pasajero->display_name : __('Un pasajero', 'flavor-chat-ia'),
            $reserva->origen,
            $reserva->destino,
            date_i18n('l j F Y, H:i', strtotime($reserva->fecha_hora)),
            $reserva->plazas_reservadas,
            $reserva->motivo_cancelacion ?: __('No especificado', 'flavor-chat-ia')
        );

        wp_mail($conductor->user_email, $asunto, $mensaje);
    }

    /**
     * Enviar notificacion de cancelacion al pasajero
     */
    private function enviar_notificacion_cancelacion_pasajero($reserva_id) {
        if (!$this->settings['notificaciones_email']) {
            return;
        }

        global $wpdb;

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, v.origen, v.destino, v.fecha_hora
             FROM {$this->tabla_reservas} r
             JOIN {$this->tabla_viajes} v ON r.viaje_id = v.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            return;
        }

        $pasajero = get_userdata($reserva->pasajero_id);

        if (!$pasajero || !$pasajero->user_email) {
            return;
        }

        $asunto = sprintf(__('[Carpooling] Tu viaje ha sido cancelado', 'flavor-chat-ia'));

        $mensaje = sprintf(
            __("Hola %s,\n\nLamentamos informarte que el viaje en el que tenias reserva ha sido cancelado por el conductor.\n\nRuta: %s -> %s\nFecha: %s\n\nMotivo: %s\n\nTe animamos a buscar otros viajes disponibles.\n\nSaludos,\nEl equipo de Carpooling", 'flavor-chat-ia'),
            $pasajero->display_name,
            $reserva->origen,
            $reserva->destino,
            date_i18n('l j F Y, H:i', strtotime($reserva->fecha_hora)),
            $reserva->motivo_cancelacion ?: __('No especificado', 'flavor-chat-ia')
        );

        wp_mail($pasajero->user_email, $asunto, $mensaje);
    }

    // ========================================
    // TOOL DEFINITIONS Y WEB COMPONENTS
    // ========================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'carpooling_buscar',
                'description' => 'Buscar viajes compartidos disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'origen' => ['type' => 'string', 'description' => 'Ciudad o direccion de origen'],
                        'origen_lat' => ['type' => 'number', 'description' => 'Latitud origen'],
                        'origen_lng' => ['type' => 'number', 'description' => 'Longitud origen'],
                        'destino' => ['type' => 'string', 'description' => 'Ciudad o direccion de destino'],
                        'destino_lat' => ['type' => 'number', 'description' => 'Latitud destino'],
                        'destino_lng' => ['type' => 'number', 'description' => 'Longitud destino'],
                        'fecha' => ['type' => 'string', 'description' => 'Fecha del viaje YYYY-MM-DD'],
                        'plazas' => ['type' => 'integer', 'description' => 'Numero de plazas necesarias'],
                    ],
                    'required' => ['origen_lat', 'origen_lng', 'destino_lat', 'destino_lng'],
                ],
            ],
            [
                'name' => 'carpooling_publicar',
                'description' => 'Publicar un nuevo viaje compartido',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'origen' => ['type' => 'string', 'description' => 'Direccion de origen'],
                        'origen_lat' => ['type' => 'number'],
                        'origen_lng' => ['type' => 'number'],
                        'destino' => ['type' => 'string', 'description' => 'Direccion de destino'],
                        'destino_lat' => ['type' => 'number'],
                        'destino_lng' => ['type' => 'number'],
                        'fecha_hora' => ['type' => 'string', 'description' => 'Fecha y hora YYYY-MM-DD HH:MM'],
                        'plazas' => ['type' => 'integer', 'description' => 'Plazas disponibles'],
                        'precio' => ['type' => 'number', 'description' => 'Precio por plaza en EUR'],
                    ],
                    'required' => ['origen', 'destino', 'fecha_hora', 'plazas'],
                ],
            ],
            [
                'name' => 'carpooling_reservar',
                'description' => 'Reservar plaza en un viaje',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'viaje_id' => ['type' => 'integer', 'description' => 'ID del viaje'],
                        'plazas' => ['type' => 'integer', 'description' => 'Numero de plazas'],
                        'mensaje' => ['type' => 'string', 'description' => 'Mensaje para el conductor'],
                    ],
                    'required' => ['viaje_id'],
                ],
            ],
            [
                'name' => 'carpooling_mis_viajes',
                'description' => 'Ver mis viajes como conductor o pasajero',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'enum' => ['conductor', 'pasajero']],
                        'estado' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Carpooling', 'flavor-chat-ia'),
                'description' => __('Seccion hero principal con buscador de viajes', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-car',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Comparte tu viaje, ahorra dinero', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('Viaja de forma economica y sostenible con tus vecinos', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'carpooling/hero',
            ],
            'viajes_grid' => [
                'label' => __('Grid de Viajes', 'flavor-chat-ia'),
                'description' => __('Listado de viajes disponibles en formato tarjetas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo de seccion', 'flavor-chat-ia'),
                        'default' => __('Viajes Disponibles', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'filtro_categoria' => [
                        'type' => 'select',
                        'label' => __('Filtrar por', 'flavor-chat-ia'),
                        'options' => ['todos', 'proximos', 'populares'],
                        'default' => 'proximos',
                    ],
                    'mostrar_avatares' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar avatares conductores', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'carpooling/viajes-grid',
            ],
            'como_funciona' => [
                'label' => __('Como Funciona', 'flavor-chat-ia'),
                'description' => __('Pasos explicativos del proceso', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Como funciona?', 'flavor-chat-ia'),
                    ],
                    'paso1_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 1 - Titulo', 'flavor-chat-ia'),
                        'default' => __('Busca tu viaje', 'flavor-chat-ia'),
                    ],
                    'paso1_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 1 - Texto', 'flavor-chat-ia'),
                        'default' => __('Introduce origen, destino y fecha', 'flavor-chat-ia'),
                    ],
                    'paso2_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 2 - Titulo', 'flavor-chat-ia'),
                        'default' => __('Reserva tu plaza', 'flavor-chat-ia'),
                    ],
                    'paso2_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 2 - Texto', 'flavor-chat-ia'),
                        'default' => __('Selecciona el viaje que mejor se ajuste', 'flavor-chat-ia'),
                    ],
                    'paso3_titulo' => [
                        'type' => 'text',
                        'label' => __('Paso 3 - Titulo', 'flavor-chat-ia'),
                        'default' => __('Viaja!', 'flavor-chat-ia'),
                    ],
                    'paso3_texto' => [
                        'type' => 'textarea',
                        'label' => __('Paso 3 - Texto', 'flavor-chat-ia'),
                        'default' => __('Comparte tu viaje y ahorra', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'carpooling/como-funciona',
            ],
            'cta_conductor' => [
                'label' => __('CTA Conductor', 'flavor-chat-ia'),
                'description' => __('Llamada a la accion para publicar viajes', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Tienes coche? Comparte tus viajes', 'flavor-chat-ia'),
                    ],
                    'texto' => [
                        'type' => 'textarea',
                        'label' => __('Texto', 'flavor-chat-ia'),
                        'default' => __('Recupera parte del coste de tus desplazamientos habituales', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del boton', 'flavor-chat-ia'),
                        'default' => __('Publicar Viaje', 'flavor-chat-ia'),
                    ],
                    'boton_url' => [
                        'type' => 'url',
                        'label' => __('URL del boton', 'flavor-chat-ia'),
                        'default' => '#',
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#3b82f6',
                    ],
                ],
                'template' => 'carpooling/cta-conductor',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Carpooling Comunitario**

Sistema de viajes compartidos entre vecinos para reducir costes y emisiones.

**Caracteristicas:**
- Publica o busca viajes
- Filtro por origen, destino y fecha
- Calculo automatico de costes
- Sistema de valoraciones
- Viajes recurrentes (trabajo, universidad)
- Gestion de vehiculos

**Para conductores:**
- Publica tus viajes facilmente
- Configura rutas recurrentes
- Gestiona tus vehiculos
- Recibe valoraciones positivas
- Acepta o rechaza reservas

**Para pasajeros:**
- Busca viajes por ruta y fecha
- Reserva plazas al instante
- Contacta con el conductor
- Valora tu experiencia

**Seguridad:**
- Verificacion de conductores
- Valoraciones y reputacion
- Informacion del vehiculo
- Puntos de recogida seguros

**Beneficios:**
- Ahorro en combustible y peajes
- Reduce trafico y emisiones
- Conoce a tus vecinos
- Viajes mas sostenibles
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como se calcula el precio?',
                'respuesta' => 'El conductor puede establecer el precio o usar nuestro calculo automatico basado en distancia y gastos reales.',
            ],
            [
                'pregunta' => 'Que pasa si cancelo?',
                'respuesta' => 'Las cancelaciones afectan tu reputacion. Intenta avisar con al menos 24h de antelacion.',
            ],
            [
                'pregunta' => 'Como funcionan las valoraciones?',
                'respuesta' => 'Despues de cada viaje completado, tanto conductor como pasajero pueden valorarse mutuamente del 1 al 5.',
            ],
            [
                'pregunta' => 'Puedo crear viajes recurrentes?',
                'respuesta' => 'Si, puedes configurar rutas recurrentes para tus desplazamientos habituales y se generaran automaticamente.',
            ],
            [
                'pregunta' => 'Es seguro?',
                'respuesta' => 'Todos los usuarios estan registrados y tienen valoraciones publicas. Ademas verificamos la informacion de los conductores.',
            ],
        ];
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'carpooling',
            'label' => __('Carpooling', 'flavor-chat-ia'),
            'icon' => 'dashicons-car',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'carpooling-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'carpooling-viajes',
                    'titulo' => __('Viajes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_viajes'],
                    'badge' => [$this, 'contar_viajes_activos'],
                ],
                [
                    'slug' => 'carpooling-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        include dirname(__FILE__) . '/views/dashboard.php';
    }

    /**
     * Renderiza la página de viajes
     */
    public function render_admin_viajes() {
        include dirname(__FILE__) . '/views/viajes.php';
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_config() {
        $configuracion_actual = $this->get_settings();

        echo '<div class="wrap flavor-modulo-page">';
        echo '<h1>' . esc_html__('Configuración de Carpooling', 'flavor-chat-ia') . '</h1>';

        echo '<form method="post" action="">';
        wp_nonce_field('guardar_config_carpooling', 'carpooling_config_nonce');
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="max_pasajeros_por_viaje">' . esc_html__('Máximo pasajeros por viaje', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="max_pasajeros_por_viaje" id="max_pasajeros_por_viaje" value="' . esc_attr($configuracion_actual['max_pasajeros_por_viaje']) . '" min="1" max="8" class="small-text" />';
        echo '<p class="description">' . esc_html__('Número máximo de pasajeros permitidos por viaje.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="precio_por_km">' . esc_html__('Precio por kilómetro', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="precio_por_km" id="precio_por_km" value="' . esc_attr($configuracion_actual['precio_por_km']) . '" min="0" step="0.01" class="small-text" /> EUR';
        echo '<p class="description">' . esc_html__('Precio sugerido por kilómetro recorrido.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="radio_busqueda_km">' . esc_html__('Radio de búsqueda', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="radio_busqueda_km" id="radio_busqueda_km" value="' . esc_attr($configuracion_actual['radio_busqueda_km']) . '" min="1" class="small-text" /> km';
        echo '<p class="description">' . esc_html__('Radio por defecto para buscar viajes cercanos.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="dias_anticipacion_maxima">' . esc_html__('Días de anticipación máxima', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="dias_anticipacion_maxima" id="dias_anticipacion_maxima" value="' . esc_attr($configuracion_actual['dias_anticipacion_maxima']) . '" min="1" max="90" class="small-text" />';
        echo '<p class="description">' . esc_html__('Días máximos para publicar un viaje con antelación.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="tiempo_minimo_cancelacion_horas">' . esc_html__('Tiempo mínimo de cancelación', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="tiempo_minimo_cancelacion_horas" id="tiempo_minimo_cancelacion_horas" value="' . esc_attr($configuracion_actual['tiempo_minimo_cancelacion_horas']) . '" min="0" class="small-text" /> horas';
        echo '<p class="description">' . esc_html__('Horas mínimas antes del viaje para permitir cancelaciones.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="requiere_verificacion_conductor">' . esc_html__('Verificación de conductores', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="requiere_verificacion_conductor" id="requiere_verificacion_conductor" ' . checked($configuracion_actual['requiere_verificacion_conductor'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Requerir verificación de identidad para conductores.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permite_valoraciones">' . esc_html__('Sistema de valoraciones', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permite_valoraciones" id="permite_valoraciones" ' . checked($configuracion_actual['permite_valoraciones'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Permitir valoraciones entre usuarios después de viajes.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permite_mascotas">' . esc_html__('Permitir mascotas', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permite_mascotas" id="permite_mascotas" ' . checked($configuracion_actual['permite_mascotas'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Permitir opción de viajes con mascotas.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="notificaciones_email">' . esc_html__('Notificaciones por email', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="notificaciones_email" id="notificaciones_email" ' . checked($configuracion_actual['notificaciones_email'], true, false) . ' /></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . esc_attr__('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Cuenta viajes activos para el badge
     *
     * @return int
     */
    public function contar_viajes_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_viajes)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE estado = 'activo' AND fecha_salida >= NOW()"
        );
    }

    /**
     * Obtiene estadísticas para el dashboard del panel unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;

        $estadisticas = [
            'viajes_activos' => 0,
            'reservas_pendientes' => 0,
            'conductores_activos' => 0,
            'viajes_completados_mes' => 0,
        ];

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_viajes)) {
            return $estadisticas;
        }

        $estadisticas['viajes_activos'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE estado = 'activo' AND fecha_salida >= NOW()"
        );

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_reservas)) {
            $estadisticas['reservas_pendientes'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE estado = 'pendiente'"
            );
        }

        $estadisticas['conductores_activos'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT conductor_id) FROM {$this->tabla_viajes} WHERE estado = 'activo'"
        );

        $estadisticas['viajes_completados_mes'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_viajes} WHERE estado = 'completado' AND fecha_salida >= %s",
                date('Y-m-01')
            )
        );

        return $estadisticas;
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
            Flavor_Page_Creator::refresh_module_pages('carpooling');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('carpooling');
        if (!$pagina && !get_option('flavor_carpooling_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['carpooling']);
            update_option('flavor_carpooling_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Carpooling', 'flavor-chat-ia'),
                'slug' => 'carpooling',
                'content' => '<h1>' . __('Carpooling Comunitario', 'flavor-chat-ia') . '</h1>
<p>' . __('Comparte coche y reduce tu huella de carbono', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="carpooling" action="listar_viajes" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Publicar Viaje', 'flavor-chat-ia'),
                'slug' => 'publicar',
                'content' => '<h1>' . __('Publicar Viaje', 'flavor-chat-ia') . '</h1>
<p>' . __('Ofrece plazas en tu coche', 'flavor-chat-ia') . '</p>

[flavor_module_form module="carpooling" action="publicar_viaje"]',
                'parent' => 'carpooling',
            ],
            [
                'title' => __('Buscar Viaje', 'flavor-chat-ia'),
                'slug' => 'buscar',
                'content' => '<h1>' . __('Buscar Viaje', 'flavor-chat-ia') . '</h1>

[flavor_module_form module="carpooling" action="buscar_viaje"]',
                'parent' => 'carpooling',
            ],
            [
                'title' => __('Mis Viajes', 'flavor-chat-ia'),
                'slug' => 'mis-viajes',
                'content' => '<h1>' . __('Mis Viajes', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="carpooling"]',
                'parent' => 'carpooling',
            ],
        ];
    }
}
