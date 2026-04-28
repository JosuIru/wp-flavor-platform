<?php
/**
 * Módulo de Espacios Comunes para Chat IA
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Espacios Comunes - Reserva de espacios comunitarios
 */
class Flavor_Platform_Espacios_Comunes_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'espacios_comunes';
        $this->name = __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Sistema de reserva y gestión de espacios comunes y equipamientos de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['economia_local', 'cuidados'];
        $this->gailu_contribuye_a = ['cohesion', 'autonomia'];

        parent::__construct();

        // Integrar sistema de notificaciones
        $this->init_notifications();

        // Cargar funcionalidades del Sello de Conciencia (+5 pts)
        $this->cargar_funcionalidades_conciencia();
    }

    /**
     * Carga las funcionalidades del Sello de Conciencia
     * Uso Solidario, Huella de Uso, Cuidado Comunitario y Dashboard de Sostenibilidad
     */
    private function cargar_funcionalidades_conciencia() {
        $archivo_conciencia = dirname(__FILE__) . '/class-ec-conciencia-features.php';

        if (file_exists($archivo_conciencia)) {
            require_once $archivo_conciencia;

            if (class_exists('Flavor_EC_Conciencia_Features')) {
                Flavor_EC_Conciencia_Features::get_instance();
            }
        }
    }

    /**
     * Inicializar sistema de notificaciones
     */
    private function init_notifications() {
        add_action('ec_reservation_created', [$this, 'notify_reservation_created'], 10, 2);
        add_action('ec_reservation_approved', [$this, 'notify_reservation_approved'], 10, 2);
        add_action('ec_reservation_rejected', [$this, 'notify_reservation_rejected'], 10, 3);
        add_action('ec_reservation_reminder', [$this, 'notify_reservation_reminder'], 10, 2);
        add_action('ec_reservation_cancelled', [$this, 'notify_reservation_cancelled'], 10, 2);
    }

    /**
     * Red de seguridad para el registro en panel unificado.
     *
     * El trait ya aporta este método, pero se deja una implementación
     * explícita aquí para evitar fatales si en runtime existe alguna
     * desalineación de carga de traits o clases cacheadas.
     */
    protected function registrar_en_panel_unificado() {
        add_filter('flavor_admin_panel_modules', [$this, 'registrar_modulo_admin']);
    }

    /**
     * Notificación: Reserva creada
     */
    public function notify_reservation_created($reservation_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Reserva solicitada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Tu solicitud de reserva #%d ha sido enviada. Recibirás una notificación cuando sea revisada.', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservation_id),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => home_url("/espacios-comunes/mis-reservas?reservation={$reservation_id}"),
                'metadata' => [
                    'reservation_id' => $reservation_id,
                    'action' => 'reservation_created'
                ]
            ]
        );
    }

    /**
     * Notificación: Reserva aprobada
     */
    public function notify_reservation_approved($reservation_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Reserva aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('¡Tu reserva #%d ha sido aprobada! Ya puedes usar el espacio en la fecha solicitada.', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservation_id),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => home_url("/espacios-comunes/mis-reservas?reservation={$reservation_id}"),
                'metadata' => [
                    'reservation_id' => $reservation_id,
                    'action' => 'reservation_approved'
                ]
            ]
        );
    }

    /**
     * Notificación: Reserva rechazada
     */
    public function notify_reservation_rejected($reservation_id, $user_id, $reason = '') {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $message = sprintf(__('Tu reserva #%d no ha sido aprobada.', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservation_id);
        if (!empty($reason)) {
            $message .= ' ' . sprintf(__('Motivo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $reason);
        }

        $nc->send(
            $user_id,
            __('Reserva no aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $message,
            [
                'module_id' => $this->id,
                'type' => 'warning',
                'link' => home_url("/espacios-comunes/mis-reservas?reservation={$reservation_id}"),
                'metadata' => [
                    'reservation_id' => $reservation_id,
                    'action' => 'reservation_rejected',
                    'reason' => $reason
                ]
            ]
        );
    }

    /**
     * Notificación: Recordatorio de reserva
     */
    public function notify_reservation_reminder($reservation_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Recordatorio de reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Tu reserva #%d es mañana. No olvides recoger las llaves en recepción.', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservation_id),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => home_url("/espacios-comunes/mis-reservas?reservation={$reservation_id}"),
                'metadata' => [
                    'reservation_id' => $reservation_id,
                    'action' => 'reservation_reminder'
                ]
            ]
        );
    }

    /**
     * Notificación: Reserva cancelada
     */
    public function notify_reservation_cancelled($reservation_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Tu reserva #%d ha sido cancelada.', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservation_id),
            [
                'module_id' => $this->id,
                'type' => 'warning',
                'link' => home_url("/espacios-comunes/mis-reservas"),
                'metadata' => [
                    'reservation_id' => $reservation_id,
                    'action' => 'reservation_cancelled'
                ]
            ]
        );
    }

    /**
     * El gatekeeping legacy basado en tabla_existe() era chicken-and-egg.
     * Ahora el loader llama ensure_database_schema() antes, garantizando
     * que las tablas estén sincronizadas. Devolvemos true por defecto.
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Espacios Comunes no están creadas. Se crearán automáticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
        add_action('init', [$this, 'ensure_database_schema']);
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

        // Admin AJAX - Gestión de espacios y reservas
        add_action('wp_ajax_espacios_comunes_guardar_espacio', [$this, 'ajax_guardar_espacio']);
        add_action('wp_ajax_espacios_comunes_guardar_reserva', [$this, 'ajax_guardar_reserva']);
        add_action('wp_ajax_espacios_comunes_listar_espacios', [$this, 'ajax_listar_espacios']);
        add_action('wp_ajax_espacios_comunes_listar_reservas', [$this, 'ajax_listar_reservas']);

        // Admin AJAX - Operaciones CRUD adicionales
        add_action('wp_ajax_espacios_comunes_obtener_espacio', [$this, 'ajax_obtener_espacio']);
        add_action('wp_ajax_espacios_comunes_eliminar_espacio', [$this, 'ajax_eliminar_espacio']);
        add_action('wp_ajax_espacios_comunes_obtener_reserva', [$this, 'ajax_obtener_reserva']);
        add_action('wp_ajax_espacios_comunes_eliminar_reserva', [$this, 'ajax_eliminar_reserva']);

        // Admin AJAX - Normas y políticas
        add_action('wp_ajax_espacios_comunes_listar_normas', [$this, 'ajax_listar_normas']);
        add_action('wp_ajax_espacios_comunes_obtener_norma', [$this, 'ajax_obtener_norma']);
        add_action('wp_ajax_espacios_comunes_guardar_norma', [$this, 'ajax_guardar_norma']);
        add_action('wp_ajax_espacios_comunes_eliminar_norma', [$this, 'ajax_eliminar_norma']);
        add_action('wp_ajax_espacios_comunes_obtener_politicas_cancelacion', [$this, 'ajax_obtener_politicas_cancelacion']);
        add_action('wp_ajax_espacios_comunes_guardar_politicas_cancelacion', [$this, 'ajax_guardar_politicas_cancelacion']);
        add_action('wp_ajax_espacios_comunes_obtener_restricciones', [$this, 'ajax_obtener_restricciones']);
        add_action('wp_ajax_espacios_comunes_guardar_restricciones', [$this, 'ajax_guardar_restricciones']);
        add_action('wp_ajax_espacios_comunes_obtener_config_notificaciones', [$this, 'ajax_obtener_config_notificaciones']);
        add_action('wp_ajax_espacios_comunes_guardar_notificaciones', [$this, 'ajax_guardar_notificaciones']);

        // Admin AJAX - Calendario
        add_action('wp_ajax_espacios_comunes_obtener_calendario', [$this, 'ajax_obtener_calendario']);
        add_action('wp_ajax_espacios_comunes_obtener_eventos_calendario', [$this, 'ajax_obtener_eventos_calendario']);
        add_action('wp_ajax_espacios_comunes_obtener_estadisticas_calendario', [$this, 'ajax_obtener_estadisticas_calendario']);
        add_action('wp_ajax_espacios_comunes_exportar_calendario', [$this, 'ajax_exportar_calendario']);

        // Admin AJAX - Usuarios
        add_action('wp_ajax_espacios_comunes_listar_usuarios', [$this, 'ajax_listar_usuarios']);

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

        // Registrar en panel unificado
        $this->registrar_en_panel_unificado();

        // Cargar Dashboard Widget
        $this->inicializar_dashboard_widget();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Inicializa el dashboard widget del módulo
     */
    private function inicializar_dashboard_widget() {
        $archivo_widget = dirname(__FILE__) . '/class-espacios-dashboard-widget.php';
        if (file_exists($archivo_widget)) {
            require_once $archivo_widget;
            if (class_exists('Flavor_Espacios_Dashboard_Widget')) {
                new Flavor_Espacios_Dashboard_Widget();
            }
        }
    }

    /**
     * Configuración del módulo para el panel unificado de administración.
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'espacios_comunes',
            'label' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-admin-home',
            'capability' => 'manage_options',
            'categoria' => 'servicios',
            'paginas' => [
                [
                    'slug' => 'espacios-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'espacios-listado',
                    'titulo' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_espacios'],
                ],
                [
                    'slug' => 'espacios-reservas',
                    'titulo' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_reservas'],
                ],
                [
                    'slug' => 'espacios-calendario',
                    'titulo' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_calendario'],
                ],
                [
                    'slug' => 'espacios-normas',
                    'titulo' => __('Normas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_normas'],
                ],
            ],
        ];
    }

    /**
     * Renderiza la página dashboard.
     */
    public function render_pagina_dashboard() {
        $this->render_admin_view('dashboard.php', __('Espacios Comunes - Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Renderiza la página de espacios.
     */
    public function render_pagina_espacios() {
        $this->render_admin_view('espacios.php', __('Gestión de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Renderiza la página de reservas.
     */
    public function render_pagina_reservas() {
        $this->render_admin_view('reservas.php', __('Gestión de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Renderiza la página de calendario.
     */
    public function render_pagina_calendario() {
        $this->render_admin_view('calendario.php', __('Calendario de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Renderiza la página de normas.
     */
    public function render_pagina_normas() {
        $this->render_admin_view('normas.php', __('Normas y Políticas', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Helper para renderizar vistas admin del módulo.
     *
     * @param string $view_file
     * @param string $fallback_title
     * @return void
     */
    private function render_admin_view($view_file, $fallback_title) {
        $view_path = dirname(__FILE__) . '/views/' . ltrim($view_file, '/');
        if (file_exists($view_path)) {
            include $view_path;
            return;
        }

        echo '<div class="wrap"><h1>' . esc_html($fallback_title) . '</h1></div>';
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
        $aliases = [
            'listar' => 'listar_espacios',
            'listado' => 'listar_espacios',
            'buscar' => 'listar_espacios',
            'detalle' => 'detalle_espacio',
            'ver' => 'detalle_espacio',
            'calendario' => 'disponibilidad',
            'disponibilidad' => 'disponibilidad',
            'crear' => 'crear_reserva',
            'reservar' => 'crear_reserva',
            'mis_items' => 'mis_reservas',
            'mis-reservas' => 'mis_reservas',
            'cancelar' => 'cancelar_reserva',
            'valorar' => 'valorar_espacio',
            'equipamiento' => 'equipamiento_disponible',
            'reportar' => 'reportar_incidencia',
            'stats' => 'estadisticas_espacios',
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
            return ['success' => false, 'error' => __('ID de espacio requerido.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            intval($params['espacio_id'])
        ));

        if (!$espacio) {
            return ['success' => false, 'error' => __('Espacio no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
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
            return ['success' => false, 'error' => __('ID de espacio requerido.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
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
            return ['success' => false, 'error' => __('Espacio no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
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
            return ['success' => false, 'error' => __('Debes iniciar sesión para reservar.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if (empty($params['espacio_id']) || empty($params['fecha_inicio']) || empty($params['fecha_fin'])) {
            return ['success' => false, 'error' => __('Espacio, fecha de inicio y fecha de fin son requeridos.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
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
            return ['success' => false, 'error' => __('Espacio no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Validar fechas
        $settings = $this->get_settings();
        $ahora = current_time('timestamp');
        $inicio_timestamp = strtotime($fecha_inicio);
        $fin_timestamp = strtotime($fecha_fin);

        if ($inicio_timestamp <= $ahora) {
            return ['success' => false, 'error' => __('La fecha de inicio debe ser futura.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if ($fin_timestamp <= $inicio_timestamp) {
            return ['success' => false, 'error' => __('La fecha de fin debe ser posterior al inicio.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Validar anticipación mínima
        $horas_anticipacion = ($inicio_timestamp - $ahora) / 3600;
        if ($horas_anticipacion < $settings['horas_anticipacion_minima']) {
            return ['success' => false, 'error' => sprintf(__('Debes reservar con al menos %d horas de anticipación', FLAVOR_PLATFORM_TEXT_DOMAIN), $settings['horas_anticipacion_minima'])];
        }

        // Validar duración máxima
        $duracion_horas = ($fin_timestamp - $inicio_timestamp) / 3600;
        if ($duracion_horas > $settings['duracion_maxima_horas']) {
            return ['success' => false, 'error' => sprintf(__('La duración máxima es %d horas', FLAVOR_PLATFORM_TEXT_DOMAIN), $settings['duracion_maxima_horas'])];
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
                ? __('Reserva confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Solicitud de reserva enviada. Recibirás confirmación pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                ? __('Reserva cancelada. La fianza no será devuelta por cancelación tardía.', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Reserva cancelada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'mensaje' => __('Gracias por tu valoración', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'titulo' => isset($params['titulo']) ? sanitize_text_field($params['titulo']) : __('Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'mensaje' => __('Incidencia reportada. La revisaremos pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'salon_eventos' => __('Salón de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sala_reuniones' => __('Sala de reuniones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cocina' => __('Cocina comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'taller' => __('Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'terraza' => __('Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'jardin' => __('Jardín', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gimnasio' => __('Gimnasio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'ludoteca' => __('Ludoteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'piscina' => __('Piscina', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'parking' => __('Parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        $resultado = $this->action_cancelar_reserva([
            'reserva_id' => isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    public function ajax_valorar() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

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
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reserva_id = isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0;

        $wpdb->update($tabla_reservas, ['fianza_devuelta' => 1], ['id' => $reserva_id]);

        wp_send_json(['success' => true, 'mensaje' => 'Fianza marcada como devuelta']);
    }

    /**
     * AJAX: Guardar espacio (crear o actualizar)
     */
    public function ajax_guardar_espacio() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $datos_espacio = [
            'nombre' => isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '',
            'descripcion' => isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '',
            'tipo' => isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'salon_eventos',
            'ubicacion' => isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '',
            'latitud' => isset($_POST['latitud']) ? floatval($_POST['latitud']) : null,
            'longitud' => isset($_POST['longitud']) ? floatval($_POST['longitud']) : null,
            'capacidad_personas' => isset($_POST['capacidad_personas']) ? intval($_POST['capacidad_personas']) : 0,
            'superficie_m2' => isset($_POST['superficie_m2']) ? floatval($_POST['superficie_m2']) : null,
            'equipamiento' => isset($_POST['equipamiento']) ? wp_json_encode($_POST['equipamiento']) : null,
            'normas_uso' => isset($_POST['normas_uso']) ? sanitize_textarea_field($_POST['normas_uso']) : '',
            'precio_hora' => isset($_POST['precio_hora']) ? floatval($_POST['precio_hora']) : 0,
            'precio_dia' => isset($_POST['precio_dia']) ? floatval($_POST['precio_dia']) : 0,
            'requiere_fianza' => isset($_POST['requiere_fianza']) ? intval($_POST['requiere_fianza']) : 1,
            'importe_fianza' => isset($_POST['importe_fianza']) ? floatval($_POST['importe_fianza']) : 50,
            'horario_apertura' => isset($_POST['horario_apertura']) ? sanitize_text_field($_POST['horario_apertura']) : '08:00:00',
            'horario_cierre' => isset($_POST['horario_cierre']) ? sanitize_text_field($_POST['horario_cierre']) : '22:00:00',
            'dias_disponibles' => isset($_POST['dias_disponibles']) ? sanitize_text_field($_POST['dias_disponibles']) : 'L,M,X,J,V,S,D',
            'fotos' => isset($_POST['fotos']) ? wp_json_encode($_POST['fotos']) : null,
            'responsable_id' => isset($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null,
            'instrucciones_acceso' => isset($_POST['instrucciones_acceso']) ? sanitize_textarea_field($_POST['instrucciones_acceso']) : '',
            'estado' => isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'disponible',
        ];

        // Validar campos requeridos
        if (empty($datos_espacio['nombre'])) {
            wp_send_json_error(['message' => __('El nombre del espacio es requerido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (empty($datos_espacio['ubicacion'])) {
            wp_send_json_error(['message' => __('La ubicación del espacio es requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($datos_espacio['capacidad_personas'] <= 0) {
            wp_send_json_error(['message' => __('La capacidad debe ser mayor a 0.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($espacio_id > 0) {
            // Actualizar espacio existente
            $resultado = $wpdb->update($tabla_espacios, $datos_espacio, ['id' => $espacio_id]);

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al actualizar el espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'message' => __('Espacio actualizado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'espacio_id' => $espacio_id,
            ]);
        } else {
            // Crear nuevo espacio
            $datos_espacio['fecha_creacion'] = current_time('mysql');
            $resultado = $wpdb->insert($tabla_espacios, $datos_espacio);

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al crear el espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'message' => __('Espacio creado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'espacio_id' => $wpdb->insert_id,
            ]);
        }
    }

    /**
     * AJAX: Guardar reserva (crear o actualizar desde admin)
     */
    public function ajax_guardar_reserva() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reserva_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $espacio_id = isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0;
        $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : '';

        // Validar campos requeridos
        if ($espacio_id <= 0) {
            wp_send_json_error(['message' => __('Debe seleccionar un espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($usuario_id <= 0) {
            wp_send_json_error(['message' => __('Debe seleccionar un usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (empty($fecha_inicio) || empty($fecha_fin)) {
            wp_send_json_error(['message' => __('Las fechas de inicio y fin son requeridas.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que el espacio existe
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ));

        if (!$espacio) {
            wp_send_json_error(['message' => __('El espacio seleccionado no existe.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que el usuario existe
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            wp_send_json_error(['message' => __('El usuario seleccionado no existe.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar disponibilidad (excepto para la misma reserva si es actualización)
        $condicion_excluir = $reserva_id > 0 ? $wpdb->prepare(" AND id != %d", $reserva_id) : '';
        $solapamiento = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reservas
             WHERE espacio_id = %d
             AND estado IN ('solicitada', 'confirmada', 'en_curso')
             AND (
                 (fecha_inicio <= %s AND fecha_fin > %s)
                 OR (fecha_inicio < %s AND fecha_fin >= %s)
                 OR (fecha_inicio >= %s AND fecha_fin <= %s)
             )
             $condicion_excluir",
            $espacio_id,
            $fecha_inicio, $fecha_inicio,
            $fecha_fin, $fecha_fin,
            $fecha_inicio, $fecha_fin
        ));

        if ($solapamiento) {
            wp_send_json_error(['message' => __('Ya existe una reserva en ese horario.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $datos_reserva = [
            'espacio_id' => $espacio_id,
            'usuario_id' => $usuario_id,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'num_asistentes' => isset($_POST['num_asistentes']) ? intval($_POST['num_asistentes']) : null,
            'motivo' => isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '',
            'tipo_evento' => isset($_POST['tipo_evento']) ? sanitize_text_field($_POST['tipo_evento']) : '',
            'equipamiento_adicional' => isset($_POST['equipamiento_adicional']) ? wp_json_encode($_POST['equipamiento_adicional']) : null,
            'precio_total' => isset($_POST['precio_total']) ? floatval($_POST['precio_total']) : 0,
            'fianza' => isset($_POST['fianza']) ? floatval($_POST['fianza']) : ($espacio->requiere_fianza ? $espacio->importe_fianza : 0),
            'instrucciones_especiales' => isset($_POST['instrucciones_especiales']) ? sanitize_textarea_field($_POST['instrucciones_especiales']) : '',
            'estado' => isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'solicitada',
        ];

        if ($reserva_id > 0) {
            // Actualizar reserva existente
            $resultado = $wpdb->update($tabla_reservas, $datos_reserva, ['id' => $reserva_id]);

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al actualizar la reserva.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'message' => __('Reserva actualizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reserva_id' => $reserva_id,
            ]);
        } else {
            // Crear nueva reserva
            $datos_reserva['fecha_solicitud'] = current_time('mysql');
            if ($datos_reserva['estado'] === 'confirmada') {
                $datos_reserva['fecha_confirmacion'] = current_time('mysql');
            }

            $resultado = $wpdb->insert($tabla_reservas, $datos_reserva);

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al crear la reserva.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            $nueva_reserva_id = $wpdb->insert_id;

            // Notificar al usuario si la reserva se creó confirmada
            if ($datos_reserva['estado'] === 'confirmada') {
                $this->notificar_reserva_confirmada($nueva_reserva_id);
            }

            wp_send_json_success([
                'message' => __('Reserva creada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reserva_id' => $nueva_reserva_id,
            ]);
        }
    }

    /**
     * AJAX: Listar espacios (para admin)
     */
    public function ajax_listar_espacios() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';
        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $por_pagina = isset($_POST['por_pagina']) ? min(100, max(1, intval($_POST['por_pagina']))) : 20;

        $where = ['1=1'];
        $valores_prepare = [];

        if (!empty($tipo)) {
            $where[] = 'tipo = %s';
            $valores_prepare[] = $tipo;
        }

        if (!empty($estado)) {
            $where[] = 'estado = %s';
            $valores_prepare[] = $estado;
        }

        if (!empty($busqueda)) {
            $where[] = '(nombre LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)';
            $patron_busqueda = '%' . $wpdb->esc_like($busqueda) . '%';
            $valores_prepare[] = $patron_busqueda;
            $valores_prepare[] = $patron_busqueda;
            $valores_prepare[] = $patron_busqueda;
        }

        $condicion_where = implode(' AND ', $where);
        $offset = ($pagina - 1) * $por_pagina;

        // Obtener total
        $sql_total = "SELECT COUNT(*) FROM $tabla_espacios WHERE $condicion_where";
        if (!empty($valores_prepare)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_total, $valores_prepare));
        } else {
            $total = $wpdb->get_var($sql_total);
        }

        // Obtener espacios
        $sql_espacios = "SELECT * FROM $tabla_espacios WHERE $condicion_where ORDER BY nombre ASC LIMIT %d OFFSET %d";
        $valores_prepare[] = $por_pagina;
        $valores_prepare[] = $offset;

        $espacios = $wpdb->get_results($wpdb->prepare($sql_espacios, $valores_prepare));

        $espacios_formateados = array_map([$this, 'formatear_espacio'], $espacios);

        wp_send_json_success([
            'espacios' => $espacios_formateados,
            'total' => intval($total),
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Listar reservas (para admin)
     */
    public function ajax_listar_reservas() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio_id = isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0;
        $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $fecha_desde = isset($_POST['fecha_desde']) ? sanitize_text_field($_POST['fecha_desde']) : '';
        $fecha_hasta = isset($_POST['fecha_hasta']) ? sanitize_text_field($_POST['fecha_hasta']) : '';
        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $por_pagina = isset($_POST['por_pagina']) ? min(100, max(1, intval($_POST['por_pagina']))) : 20;

        $where = ['1=1'];
        $valores_prepare = [];

        if ($espacio_id > 0) {
            $where[] = 'r.espacio_id = %d';
            $valores_prepare[] = $espacio_id;
        }

        if ($usuario_id > 0) {
            $where[] = 'r.usuario_id = %d';
            $valores_prepare[] = $usuario_id;
        }

        if (!empty($estado)) {
            $where[] = 'r.estado = %s';
            $valores_prepare[] = $estado;
        }

        if (!empty($fecha_desde)) {
            $where[] = 'r.fecha_inicio >= %s';
            $valores_prepare[] = $fecha_desde . ' 00:00:00';
        }

        if (!empty($fecha_hasta)) {
            $where[] = 'r.fecha_fin <= %s';
            $valores_prepare[] = $fecha_hasta . ' 23:59:59';
        }

        $condicion_where = implode(' AND ', $where);
        $offset = ($pagina - 1) * $por_pagina;

        // Obtener total
        $sql_total = "SELECT COUNT(*) FROM $tabla_reservas r WHERE $condicion_where";
        if (!empty($valores_prepare)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_total, $valores_prepare));
        } else {
            $total = $wpdb->get_var($sql_total);
        }

        // Obtener reservas con datos del espacio
        $sql_reservas = "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo, e.ubicacion,
                         SUBSTRING(e.fotos, 1, 200) as foto_raw
                         FROM $tabla_reservas r
                         INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
                         WHERE $condicion_where
                         ORDER BY r.fecha_inicio DESC
                         LIMIT %d OFFSET %d";
        $valores_prepare[] = $por_pagina;
        $valores_prepare[] = $offset;

        $reservas = $wpdb->get_results($wpdb->prepare($sql_reservas, $valores_prepare));

        // Formatear reservas con datos de usuario
        $reservas_formateadas = [];
        foreach ($reservas as $reserva) {
            // Extraer primera foto
            $fotos = json_decode($reserva->foto_raw, true);
            $reserva->foto = !empty($fotos) && is_array($fotos) ? $fotos[0] : null;
            unset($reserva->foto_raw);

            // Obtener datos del usuario
            $usuario = get_userdata($reserva->usuario_id);
            $reserva_formateada = $this->formatear_reserva($reserva);
            $reserva_formateada['usuario_nombre'] = $usuario ? $usuario->display_name : __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $reserva_formateada['usuario_email'] = $usuario ? $usuario->user_email : '';

            $reservas_formateadas[] = $reserva_formateada;
        }

        wp_send_json_success([
            'reservas' => $reservas_formateadas,
            'total' => intval($total),
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Obtener espacio individual
     */
    public function ajax_obtener_espacio() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $espacio_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($espacio_id <= 0) {
            wp_send_json_error(['message' => __('ID de espacio no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ));

        if (!$espacio) {
            wp_send_json_error(['message' => __('Espacio no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success($this->formatear_espacio($espacio, true));
    }

    /**
     * AJAX: Eliminar espacio
     */
    public function ajax_eliminar_espacio() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $espacio_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($espacio_id <= 0) {
            wp_send_json_error(['message' => __('ID de espacio no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Verificar que no tenga reservas activas
        $reservas_activas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE espacio_id = %d
             AND estado IN ('solicitada', 'confirmada', 'en_curso')",
            $espacio_id
        ));

        if ($reservas_activas > 0) {
            wp_send_json_error([
                'message' => sprintf(
                    __('No se puede eliminar el espacio porque tiene %d reserva(s) activa(s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $reservas_activas
                )
            ]);
        }

        $resultado = $wpdb->delete($tabla_espacios, ['id' => $espacio_id], ['%d']);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al eliminar el espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Eliminar de la red federada si existe
        $this->eliminar_espacio_de_red($espacio_id);

        wp_send_json_success(['message' => __('Espacio eliminado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener reserva individual
     */
    public function ajax_obtener_reserva() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $reserva_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($reserva_id <= 0) {
            wp_send_json_error(['message' => __('ID de reserva no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo, e.ubicacion
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $reserva_formateada = $this->formatear_reserva($reserva);

        // Añadir datos del usuario
        $usuario = get_userdata($reserva->usuario_id);
        $reserva_formateada['usuario_id'] = intval($reserva->usuario_id);
        $reserva_formateada['usuario_nombre'] = $usuario ? $usuario->display_name : __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $reserva_formateada['usuario_email'] = $usuario ? $usuario->user_email : '';

        wp_send_json_success($reserva_formateada);
    }

    /**
     * AJAX: Eliminar reserva
     */
    public function ajax_eliminar_reserva() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $reserva_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($reserva_id <= 0) {
            wp_send_json_error(['message' => __('ID de reserva no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Obtener la reserva antes de eliminar para notificar
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d",
            $reserva_id
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultado = $wpdb->delete($tabla_reservas, ['id' => $reserva_id], ['%d']);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al eliminar la reserva.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Notificar al usuario si la reserva estaba activa
        if (in_array($reserva->estado, ['solicitada', 'confirmada'])) {
            do_action('ec_reservation_cancelled', $reserva_id, $reserva->usuario_id);
        }

        wp_send_json_success(['message' => __('Reserva eliminada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Listar normas
     */
    public function ajax_listar_normas() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';

        $normas = get_option('flavor_espacios_normas', []);

        if (!empty($tipo) && is_array($normas)) {
            $normas = array_filter($normas, function($norma) use ($tipo) {
                return isset($norma['tipo']) && $norma['tipo'] === $tipo;
            });
            $normas = array_values($normas);
        }

        // Añadir nombres de espacios a las normas específicas
        if (!empty($normas)) {
            global $wpdb;
            $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

            foreach ($normas as &$norma) {
                if (!empty($norma['espacio_id'])) {
                    $espacio_nombre = $wpdb->get_var($wpdb->prepare(
                        "SELECT nombre FROM $tabla_espacios WHERE id = %d",
                        intval($norma['espacio_id'])
                    ));
                    $norma['espacio_nombre'] = $espacio_nombre ?: '';
                }
            }
        }

        wp_send_json_success($normas);
    }

    /**
     * AJAX: Obtener norma individual
     */
    public function ajax_obtener_norma() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $norma_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($norma_id <= 0) {
            wp_send_json_error(['message' => __('ID de norma no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $normas = get_option('flavor_espacios_normas', []);

        $norma_encontrada = null;
        foreach ($normas as $norma) {
            if (isset($norma['id']) && intval($norma['id']) === $norma_id) {
                $norma_encontrada = $norma;
                break;
            }
        }

        if (!$norma_encontrada) {
            wp_send_json_error(['message' => __('Norma no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success($norma_encontrada);
    }

    /**
     * AJAX: Guardar norma
     */
    public function ajax_guardar_norma() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $norma_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'general';
        $espacio_id = isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0;
        $prioridad = isset($_POST['prioridad']) ? sanitize_text_field($_POST['prioridad']) : 'media';
        $activa = isset($_POST['activa']) ? 1 : 0;

        if (empty($titulo)) {
            wp_send_json_error(['message' => __('El título es requerido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (empty($descripcion)) {
            wp_send_json_error(['message' => __('La descripción es requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $normas = get_option('flavor_espacios_normas', []);
        if (!is_array($normas)) {
            $normas = [];
        }

        $nueva_norma = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'espacio_id' => $espacio_id,
            'prioridad' => $prioridad,
            'activa' => $activa,
            'fecha_modificacion' => current_time('mysql'),
        ];

        if ($norma_id > 0) {
            // Actualizar norma existente
            $encontrada = false;
            foreach ($normas as $indice => $norma) {
                if (isset($norma['id']) && intval($norma['id']) === $norma_id) {
                    $nueva_norma['id'] = $norma_id;
                    $nueva_norma['fecha_creacion'] = $norma['fecha_creacion'] ?? current_time('mysql');
                    $normas[$indice] = $nueva_norma;
                    $encontrada = true;
                    break;
                }
            }

            if (!$encontrada) {
                wp_send_json_error(['message' => __('Norma no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }
        } else {
            // Crear nueva norma
            $nuevo_id = 1;
            foreach ($normas as $norma) {
                if (isset($norma['id']) && intval($norma['id']) >= $nuevo_id) {
                    $nuevo_id = intval($norma['id']) + 1;
                }
            }
            $nueva_norma['id'] = $nuevo_id;
            $nueva_norma['fecha_creacion'] = current_time('mysql');
            $normas[] = $nueva_norma;
        }

        update_option('flavor_espacios_normas', $normas);

        wp_send_json_success([
            'message' => __('Norma guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'norma' => $nueva_norma,
        ]);
    }

    /**
     * AJAX: Eliminar norma
     */
    public function ajax_eliminar_norma() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $norma_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($norma_id <= 0) {
            wp_send_json_error(['message' => __('ID de norma no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $normas = get_option('flavor_espacios_normas', []);

        $normas_filtradas = array_filter($normas, function($norma) use ($norma_id) {
            return !isset($norma['id']) || intval($norma['id']) !== $norma_id;
        });

        if (count($normas_filtradas) === count($normas)) {
            wp_send_json_error(['message' => __('Norma no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        update_option('flavor_espacios_normas', array_values($normas_filtradas));

        wp_send_json_success(['message' => __('Norma eliminada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener políticas de cancelación
     */
    public function ajax_obtener_politicas_cancelacion() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $politicas = get_option('flavor_espacios_politicas_cancelacion', [
            'plazo_cancelacion' => 24,
            'cancelaciones_permitidas' => 3,
            'penalizacion_activa' => 0,
            'penalizacion_duracion' => 7,
        ]);

        wp_send_json_success($politicas);
    }

    /**
     * AJAX: Guardar políticas de cancelación
     */
    public function ajax_guardar_politicas_cancelacion() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $politicas = [
            'plazo_cancelacion' => isset($_POST['plazo_cancelacion']) ? intval($_POST['plazo_cancelacion']) : 24,
            'cancelaciones_permitidas' => isset($_POST['cancelaciones_permitidas']) ? intval($_POST['cancelaciones_permitidas']) : 3,
            'penalizacion_activa' => isset($_POST['penalizacion_activa']) ? 1 : 0,
            'penalizacion_duracion' => isset($_POST['penalizacion_duracion']) ? intval($_POST['penalizacion_duracion']) : 7,
        ];

        update_option('flavor_espacios_politicas_cancelacion', $politicas);

        // Actualizar también los settings del módulo
        $settings = $this->get_settings();
        $settings['horas_anticipacion_cancelacion'] = $politicas['plazo_cancelacion'];
        $this->update_settings($settings);

        wp_send_json_success(['message' => __('Políticas guardadas correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener restricciones de uso
     */
    public function ajax_obtener_restricciones() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $restricciones = get_option('flavor_espacios_restricciones', [
            'max_horas_mes' => 0,
            'max_reservas_simultaneas' => 3,
            'restriccion_repeticion' => 0,
        ]);

        wp_send_json_success($restricciones);
    }

    /**
     * AJAX: Guardar restricciones de uso
     */
    public function ajax_guardar_restricciones() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $restricciones = [
            'max_horas_mes' => isset($_POST['max_horas_mes']) ? intval($_POST['max_horas_mes']) : 0,
            'max_reservas_simultaneas' => isset($_POST['max_reservas_simultaneas']) ? max(1, intval($_POST['max_reservas_simultaneas'])) : 3,
            'restriccion_repeticion' => isset($_POST['restriccion_repeticion']) ? 1 : 0,
        ];

        update_option('flavor_espacios_restricciones', $restricciones);

        wp_send_json_success(['message' => __('Restricciones guardadas correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener configuración de notificaciones
     */
    public function ajax_obtener_config_notificaciones() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $configuracion = get_option('flavor_espacios_notificaciones', [
            'notif_confirmacion' => 1,
            'notif_recordatorio' => 1,
            'recordatorio_horas' => 24,
            'notif_cancelacion' => 1,
        ]);

        wp_send_json_success($configuracion);
    }

    /**
     * AJAX: Guardar configuración de notificaciones
     */
    public function ajax_guardar_notificaciones() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $configuracion = [
            'notif_confirmacion' => isset($_POST['notif_confirmacion']) ? 1 : 0,
            'notif_recordatorio' => isset($_POST['notif_recordatorio']) ? 1 : 0,
            'recordatorio_horas' => isset($_POST['recordatorio_horas']) ? max(1, intval($_POST['recordatorio_horas'])) : 24,
            'notif_cancelacion' => isset($_POST['notif_cancelacion']) ? 1 : 0,
        ];

        update_option('flavor_espacios_notificaciones', $configuracion);

        wp_send_json_success(['message' => __('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Obtener calendario de disponibilidad
     */
    public function ajax_obtener_calendario() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $espacio_id = isset($_POST['espacio_id']) ? intval($_POST['espacio_id']) : 0;
        $mes = isset($_POST['mes']) ? intval($_POST['mes']) : intval(date('m'));
        $anio = isset($_POST['anio']) ? intval($_POST['anio']) : intval(date('Y'));

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $fecha_inicio = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $fecha_fin = date('Y-m-t 23:59:59', strtotime($fecha_inicio));

        $condicion_espacio = '';
        $valores_prepare = [$fecha_inicio, $fecha_fin];

        if ($espacio_id > 0) {
            $condicion_espacio = 'AND r.espacio_id = %d';
            $valores_prepare[] = $espacio_id;
        }

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.fecha_inicio >= %s AND r.fecha_inicio <= %s
             AND r.estado NOT IN ('cancelada', 'rechazada')
             $condicion_espacio
             ORDER BY r.fecha_inicio ASC",
            ...$valores_prepare
        ));

        $eventos = [];
        foreach ($reservas as $reserva) {
            $usuario = get_userdata($reserva->usuario_id);
            $eventos[] = [
                'id' => intval($reserva->id),
                'title' => $reserva->espacio_nombre,
                'start' => $reserva->fecha_inicio,
                'end' => $reserva->fecha_fin,
                'estado' => $reserva->estado,
                'espacio_id' => intval($reserva->espacio_id),
                'espacio_tipo' => $reserva->espacio_tipo,
                'usuario' => $usuario ? $usuario->display_name : __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'motivo' => $reserva->motivo,
            ];
        }

        wp_send_json_success([
            'eventos' => $eventos,
            'mes' => $mes,
            'anio' => $anio,
        ]);
    }

    /**
     * AJAX: Obtener eventos del calendario (para vista de calendario)
     */
    public function ajax_obtener_eventos_calendario() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : date('Y-m-d');
        $vista = isset($_POST['vista']) ? sanitize_text_field($_POST['vista']) : 'month';
        $espacios_filtro = isset($_POST['espacios']) ? sanitize_text_field($_POST['espacios']) : '';
        $estados_filtro = isset($_POST['estados']) ? sanitize_text_field($_POST['estados']) : '';
        $hora_desde = isset($_POST['hora_desde']) ? sanitize_text_field($_POST['hora_desde']) : '00:00';
        $hora_hasta = isset($_POST['hora_hasta']) ? sanitize_text_field($_POST['hora_hasta']) : '23:59';

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        // Calcular rango de fechas según la vista
        $fecha_timestamp = strtotime($fecha);
        switch ($vista) {
            case 'day':
                $fecha_inicio = date('Y-m-d', $fecha_timestamp) . ' ' . $hora_desde . ':00';
                $fecha_fin = date('Y-m-d', $fecha_timestamp) . ' ' . $hora_hasta . ':59';
                break;
            case 'week':
                $inicio_semana = date('Y-m-d', strtotime('monday this week', $fecha_timestamp));
                $fin_semana = date('Y-m-d', strtotime('sunday this week', $fecha_timestamp));
                $fecha_inicio = $inicio_semana . ' ' . $hora_desde . ':00';
                $fecha_fin = $fin_semana . ' ' . $hora_hasta . ':59';
                break;
            case 'agenda':
                $fecha_inicio = date('Y-m-d', $fecha_timestamp) . ' 00:00:00';
                $fecha_fin = date('Y-m-d', strtotime('+30 days', $fecha_timestamp)) . ' 23:59:59';
                break;
            case 'month':
            default:
                $fecha_inicio = date('Y-m-01', $fecha_timestamp) . ' 00:00:00';
                $fecha_fin = date('Y-m-t', $fecha_timestamp) . ' 23:59:59';
                break;
        }

        $condiciones = [];
        $valores_prepare = [$fecha_inicio, $fecha_fin];

        // Filtro de espacios
        if (!empty($espacios_filtro)) {
            $espacios_array = array_map('intval', explode(',', $espacios_filtro));
            $placeholders = implode(',', array_fill(0, count($espacios_array), '%d'));
            $condiciones[] = "r.espacio_id IN ($placeholders)";
            $valores_prepare = array_merge($valores_prepare, $espacios_array);
        }

        // Filtro de estados
        if (!empty($estados_filtro)) {
            $estados_array = array_map('sanitize_text_field', explode(',', $estados_filtro));
            $placeholders = implode(',', array_fill(0, count($estados_array), '%s'));
            $condiciones[] = "r.estado IN ($placeholders)";
            $valores_prepare = array_merge($valores_prepare, $estados_array);
        }

        $condicion_extra = !empty($condiciones) ? ' AND ' . implode(' AND ', $condiciones) : '';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.tipo as espacio_tipo
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.fecha_inicio >= %s AND r.fecha_fin <= %s
             $condicion_extra
             ORDER BY r.fecha_inicio ASC",
            ...$valores_prepare
        ));

        $eventos = [];
        foreach ($reservas as $reserva) {
            $usuario = get_userdata($reserva->usuario_id);
            $eventos[] = [
                'id' => intval($reserva->id),
                'title' => $reserva->espacio_nombre . ($reserva->motivo ? ' - ' . wp_trim_words($reserva->motivo, 5) : ''),
                'start' => $reserva->fecha_inicio,
                'end' => $reserva->fecha_fin,
                'estado' => $reserva->estado,
                'espacio_id' => intval($reserva->espacio_id),
                'espacio_nombre' => $reserva->espacio_nombre,
                'espacio_tipo' => $reserva->espacio_tipo,
                'usuario' => $usuario ? $usuario->display_name : __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'usuario_id' => intval($reserva->usuario_id),
                'motivo' => $reserva->motivo,
                'color' => $this->get_color_estado($reserva->estado),
            ];
        }

        wp_send_json_success($eventos);
    }

    /**
     * Obtiene el color asociado a un estado de reserva
     */
    private function get_color_estado($estado) {
        $colores = [
            'solicitada' => '#f59e0b',  // Amber
            'confirmada' => '#10b981',  // Green
            'en_curso' => '#3b82f6',    // Blue
            'finalizada' => '#6b7280',  // Gray
            'cancelada' => '#ef4444',   // Red
            'rechazada' => '#dc2626',   // Dark Red
        ];
        return $colores[$estado] ?? '#6b7280';
    }

    /**
     * AJAX: Obtener estadísticas del calendario
     */
    public function ajax_obtener_estadisticas_calendario() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $hoy = date('Y-m-d');
        $inicio_semana = date('Y-m-d', strtotime('monday this week'));
        $fin_semana = date('Y-m-d', strtotime('sunday this week'));
        $inicio_mes = date('Y-m-01');
        $fin_mes = date('Y-m-t');

        // Reservas de hoy
        $reservas_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE DATE(fecha_inicio) = %s
             AND estado IN ('confirmada', 'en_curso')",
            $hoy
        ));

        // Reservas de esta semana
        $reservas_semana = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s
             AND estado IN ('confirmada', 'en_curso', 'finalizada')",
            $inicio_semana,
            $fin_semana
        ));

        // Reservas de este mes
        $reservas_mes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s
             AND estado IN ('confirmada', 'en_curso', 'finalizada')",
            $inicio_mes,
            $fin_mes
        ));

        // Calcular tasa de ocupación (simplificado)
        $total_espacios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_espacios WHERE estado = 'disponible'");
        $dias_mes = intval(date('t'));
        $horas_disponibles = $total_espacios * $dias_mes * 14; // Asumiendo 14 horas por día

        $horas_reservadas = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin))
             FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s
             AND estado IN ('confirmada', 'en_curso', 'finalizada')",
            $inicio_mes,
            $fin_mes
        ));

        $tasa_ocupacion = $horas_disponibles > 0
            ? round(($horas_reservadas / $horas_disponibles) * 100, 1)
            : 0;

        wp_send_json_success([
            'hoy' => intval($reservas_hoy),
            'semana' => intval($reservas_semana),
            'mes' => intval($reservas_mes),
            'ocupacion' => min(100, $tasa_ocupacion),
        ]);
    }

    /**
     * AJAX: Exportar calendario a ICS
     */
    public function ajax_exportar_calendario() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'flavor_espacios_calendario_nonce')) {
            wp_die(__('Verificación de seguridad fallida.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $fecha_inicio = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $fecha_fin = date('Y-m-t 23:59:59', strtotime($fecha_inicio));

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion
             FROM $tabla_reservas r
             INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
             WHERE r.fecha_inicio >= %s AND r.fecha_inicio <= %s
             AND r.estado IN ('confirmada', 'en_curso')
             ORDER BY r.fecha_inicio ASC",
            $fecha_inicio,
            $fecha_fin
        ));

        // Generar ICS
        $ics_content = "BEGIN:VCALENDAR\r\n";
        $ics_content .= "VERSION:2.0\r\n";
        $ics_content .= "PRODID:-//Flavor Platform//Espacios Comunes//ES\r\n";
        $ics_content .= "CALSCALE:GREGORIAN\r\n";
        $ics_content .= "METHOD:PUBLISH\r\n";
        $ics_content .= "X-WR-CALNAME:Espacios Comunes\r\n";

        foreach ($reservas as $reserva) {
            $usuario = get_userdata($reserva->usuario_id);
            $uid = md5($reserva->id . $reserva->fecha_inicio);
            $dtstart = gmdate('Ymd\THis\Z', strtotime($reserva->fecha_inicio));
            $dtend = gmdate('Ymd\THis\Z', strtotime($reserva->fecha_fin));
            $dtstamp = gmdate('Ymd\THis\Z');

            $ics_content .= "BEGIN:VEVENT\r\n";
            $ics_content .= "UID:{$uid}@flavor-platform\r\n";
            $ics_content .= "DTSTAMP:{$dtstamp}\r\n";
            $ics_content .= "DTSTART:{$dtstart}\r\n";
            $ics_content .= "DTEND:{$dtend}\r\n";
            $ics_content .= "SUMMARY:" . $this->ics_escape($reserva->espacio_nombre) . "\r\n";
            $ics_content .= "LOCATION:" . $this->ics_escape($reserva->ubicacion) . "\r\n";
            $ics_content .= "DESCRIPTION:" . $this->ics_escape(
                ($reserva->motivo ? $reserva->motivo . ' - ' : '') .
                __('Reservado por: ', FLAVOR_PLATFORM_TEXT_DOMAIN) . ($usuario ? $usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN))
            ) . "\r\n";
            $ics_content .= "STATUS:CONFIRMED\r\n";
            $ics_content .= "END:VEVENT\r\n";
        }

        $ics_content .= "END:VCALENDAR\r\n";

        // Headers para descarga
        $nombre_archivo = sprintf('espacios-comunes-%04d-%02d.ics', $anio, $mes);
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . strlen($ics_content));

        echo $ics_content;
        exit;
    }

    /**
     * Escapa texto para formato ICS
     */
    private function ics_escape($texto) {
        $texto = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $texto);
        return $texto;
    }

    /**
     * AJAX: Listar usuarios (para select en formularios)
     */
    public function ajax_listar_usuarios() {
        check_ajax_referer('flavor_espacios_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';
        $limite = isset($_POST['limite']) ? min(100, max(1, intval($_POST['limite']))) : 50;

        $argumentos_usuario = [
            'number' => $limite,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];

        if (!empty($busqueda)) {
            $argumentos_usuario['search'] = '*' . $busqueda . '*';
            $argumentos_usuario['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $usuarios = get_users($argumentos_usuario);

        $lista_usuarios = [];
        foreach ($usuarios as $usuario) {
            $lista_usuarios[] = [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name,
                'email' => $usuario->user_email,
                'avatar' => get_avatar_url($usuario->ID, ['size' => 32]),
            ];
        }

        wp_send_json_success($lista_usuarios);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function shortcode_listado($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        $atts = shortcode_atts([
            'tipo' => '',
            'columnas' => 3,
        ], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_espacios_nonce'),
            'usuario_logueado' => is_user_logged_in(),
        ]);

        ob_start();
        include FLAVOR_PLATFORM_PATH . 'includes/modules/espacios-comunes/templates/listado.php';
        return ob_get_clean();
    }

    public function shortcode_detalle($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['id' => 0], $atts);
        $espacio_id = $atts['id'] ?: (isset($_GET['espacio_id']) ? intval($_GET['espacio_id']) : 0);

        if (!$espacio_id) {
            return '<p>' . __('Espacio no especificado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_espacios_nonce'),
            'usuario_logueado' => is_user_logged_in(),
            'espacio_id' => $espacio_id,
        ]);

        ob_start();
        include FLAVOR_PLATFORM_PATH . 'includes/modules/espacios-comunes/templates/detalle.php';
        return ob_get_clean();
    }

    public function shortcode_mis_reservas($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_espacios_nonce'),
            'usuario_logueado' => true,
        ]);

        ob_start();
        include FLAVOR_PLATFORM_PATH . 'includes/modules/espacios-comunes/templates/mis-reservas.php';
        return ob_get_clean();
    }

    public function shortcode_calendario($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['espacio_id' => 0], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);
        wp_enqueue_script('espacios-frontend', $base_url . 'js/espacios-frontend.js', ['jquery'], $version, true);

        wp_localize_script('espacios-frontend', 'espaciosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_espacios_nonce'),
            'espacio_id' => intval($atts['espacio_id']),
        ]);

        ob_start();
        include FLAVOR_PLATFORM_PATH . 'includes/modules/espacios-comunes/templates/calendario.php';
        return ob_get_clean();
    }

    public function shortcode_equipamiento($atts) {
        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        $atts = shortcode_atts(['categoria' => ''], $atts);

        wp_enqueue_style('espacios-frontend', $base_url . 'css/espacios-frontend.css', [], $version);

        ob_start();
        include FLAVOR_PLATFORM_PATH . 'includes/modules/espacios-comunes/templates/equipamiento.php';
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
            'salon_eventos' => __('Salón de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sala_reuniones' => __('Sala de reuniones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cocina' => __('Cocina comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'taller' => __('Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'terraza' => __('Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'jardin' => __('Jardín', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gimnasio' => __('Gimnasio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'ludoteca' => __('Ludoteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'piscina' => __('Piscina', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'parking' => __('Parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $tipos[$tipo] ?? $tipo;
    }

    private function get_estado_label($estado) {
        $estados = [
            'solicitada' => __('Pendiente de confirmación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'en_curso' => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'finalizada' => __('Finalizada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'rechazada' => __('Rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        $asunto = sprintf(__('Nueva solicitud de reserva: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $espacio->nombre);
        $mensaje = sprintf(
            __('%s ha solicitado reservar el espacio "%s".', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

        $asunto = sprintf(__('Reserva confirmada: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Tu reserva de "%s" para el %s ha sido confirmada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

        $asunto = sprintf(__('Reserva no disponible: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Tu solicitud de reserva para "%s" no ha podido ser aprobada. %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $reserva->espacio_nombre,
            $motivo ? __('Motivo: ', FLAVOR_PLATFORM_TEXT_DOMAIN) . $motivo : ''
        );

        do_action('flavor_notificacion_enviar', $reserva->usuario_id, $asunto, $mensaje, 'espacios_rechazada');
    }

    private function notificar_recordatorio($reserva) {
        $asunto = sprintf(__('Recordatorio: Reserva mañana - %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $reserva->espacio_nombre);
        $mensaje = sprintf(
            __('Recuerda que mañana tienes reservado "%s" a las %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        $asunto = sprintf(__('Nueva incidencia: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $incidencia->espacio_nombre);
        $mensaje = sprintf(
            __('%s ha reportado una incidencia en "%s": %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                'label' => __('Hero Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Reserva espacios para tus eventos y actividades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'espacios/hero',
            ],
            'espacios_grid' => [
                'label' => __('Grid de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestros Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'tipo_filtro' => ['type' => 'text', 'default' => ''],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'espacios/grid',
            ],
            'calendario_disponibilidad' => [
                'label' => __('Calendario Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'espacio_id' => ['type' => 'number', 'default' => 0],
                    'vista_defecto' => ['type' => 'select', 'options' => ['mes', 'semana'], 'default' => 'semana'],
                ],
                'template' => 'espacios/calendario',
            ],
            'proceso_reserva' => [
                'label' => __('Proceso de Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'content',
                'icon' => 'dashicons-yes',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Cómo Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN)],
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

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Verificar que las tablas existan
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_espacios)) {
            return $estadisticas;
        }

        // Total de espacios disponibles
        $total_espacios = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_espacios} WHERE estado = 'disponible'"
        );

        $estadisticas['espacios_disponibles'] = [
            'icon' => 'dashicons-building',
            'valor' => $total_espacios,
            'label' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'blue',
        ];

        // Reservas del usuario actual
        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Platform_Helpers::tabla_existe($tabla_reservas)) {
            // Reservas activas (confirmadas o en curso)
            $reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE usuario_id = %d
                 AND estado IN ('confirmada', 'en_curso')
                 AND fecha_fin >= NOW()",
                $usuario_id
            ));

            $estadisticas['mis_reservas'] = [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $reservas_activas,
                'label' => __('Reservas activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $reservas_activas > 0 ? 'green' : 'gray',
            ];

            // Próxima reserva
            $proxima_reserva = $wpdb->get_row($wpdb->prepare(
                "SELECT r.fecha_inicio, e.nombre as espacio_nombre
                 FROM {$tabla_reservas} r
                 INNER JOIN {$tabla_espacios} e ON r.espacio_id = e.id
                 WHERE r.usuario_id = %d
                 AND r.estado IN ('confirmada', 'en_curso')
                 AND r.fecha_inicio >= NOW()
                 ORDER BY r.fecha_inicio ASC
                 LIMIT 1",
                $usuario_id
            ));

            if ($proxima_reserva) {
                $fecha_formateada = date_i18n('d M H:i', strtotime($proxima_reserva->fecha_inicio));
                $estadisticas['proxima_reserva'] = [
                    'icon' => 'dashicons-clock',
                    'valor' => $fecha_formateada,
                    'label' => $proxima_reserva->espacio_nombre,
                    'color' => 'purple',
                ];
            }

            // Reservas pendientes de confirmación
            $reservas_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE usuario_id = %d
                 AND estado = 'solicitada'
                 AND fecha_inicio >= NOW()",
                $usuario_id
            ));

            if ($reservas_pendientes > 0) {
                $estadisticas['pendientes'] = [
                    'icon' => 'dashicons-hourglass',
                    'valor' => $reservas_pendientes,
                    'label' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'orange',
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
        if (!class_exists('Flavor_Page_Creator_V3')) {
            return [];
        }

        return [
            // Página principal
            [
                'title' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'espacios-comunes',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Reserva de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'subtitle' => __('Salas de reuniones, zonas deportivas y más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'background' => 'white',
                    'module' => 'espacios_comunes',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="espacios_comunes" action="espacios_disponibles" columnas="3"]',
                ]),
                'parent' => 0,
            ],

            // Hacer reserva
            [
                'title' => __('Hacer Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'reservar',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Reservar Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'subtitle' => __('Completa el formulario para solicitar tu reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'module' => 'espacios_comunes',
                    'current' => 'reservar',
                    'content_after' => '[flavor_module_form module="espacios_comunes" action="crear_reserva"]',
                ]),
                'parent' => 'espacios-comunes',
            ],

            // Mis reservas
            [
                'title' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mis-reservas',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'subtitle' => __('Consulta y gestiona tus reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'module' => 'espacios_comunes',
                    'current' => 'mis_reservas',
                    'content_after' => '[flavor_module_listing module="espacios_comunes" action="mis_reservas" user_specific="yes"]',
                ]),
                'parent' => 'espacios-comunes',
            ],

            // Calendario
            [
                'title' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'calendario',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Calendario de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'subtitle' => __('Consulta la disponibilidad de los espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'module' => 'espacios_comunes',
                    'current' => 'calendario',
                    'content_after' => '[flavor_module_calendar module="espacios_comunes"]',
                ]),
                'parent' => 'espacios-comunes',
            ],

            // Normas de uso
            [
                'title' => __('Normas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'normas',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Normas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'subtitle' => __('Reglas para el uso responsable de los espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'module' => 'espacios_comunes',
                    'current' => 'normas',
                    'content_after' => '[flavor_module_content module="espacios_comunes" action="mostrar_normas"]',
                ]),
                'parent' => 'espacios-comunes',
            ],
        ];
    }

    /**
     * Registra las paginas de administracion del modulo
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        add_submenu_page(
            null,
            __('Calendario Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'espacios-calendario',
            [$this, 'render_espacios_calendario']
        );

        add_submenu_page(
            null,
            __('Reservas de Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'espacios-comunes-reservas',
            [$this, 'render_espacios_comunes_reservas']
        );

        add_submenu_page(
            null,
            __('Listado de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'espacios-listado',
            [$this, 'render_espacios_listado']
        );

        add_submenu_page(
            null,
            __('Normas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Normas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'espacios-normas',
            [$this, 'render_espacios_normas']
        );

        add_submenu_page(
            null,
            __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'espacios-reservas',
            [$this, 'render_espacios_reservas']
        );
    }

    /**
     * Render: Calendario
     */
    public function render_espacios_calendario() {
        $vista = dirname(__FILE__) . '/views/calendario.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Calendario Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Render: Reservas de espacios comunes
     */
    public function render_espacios_comunes_reservas() {
        $vista = dirname(__FILE__) . '/views/reservas.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Reservas de Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Render: Listado de espacios
     */
    public function render_espacios_listado() {
        $vista = dirname(__FILE__) . '/views/listado.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Listado de Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Render: Normas de uso
     */
    public function render_espacios_normas() {
        $vista = dirname(__FILE__) . '/views/normas.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Normas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Render: Reservas
     */
    public function render_espacios_reservas() {
        $vista = dirname(__FILE__) . '/views/reservas-admin.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    // ─── Sincronización con Red ──────────────────────────────

    /**
     * Sincroniza un espacio con la tabla de red federada
     */
    public function sincronizar_espacio_con_red($espacio_id) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_red = $wpdb->prefix . 'flavor_network_spaces';

        // Verificar que la tabla de red existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_red'") !== $tabla_red) {
            return false;
        }

        // Obtener datos del espacio
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ));

        if (!$espacio || $espacio->estado !== 'disponible') {
            return false;
        }

        $nodo_id = get_option('flavor_network_node_id', '');
        if (empty($nodo_id)) {
            $nodo_id = wp_generate_uuid4();
            update_option('flavor_network_node_id', $nodo_id);
        }

        // Obtener foto principal del JSON
        $fotos = json_decode($espacio->fotos, true);
        $foto_principal = is_array($fotos) && !empty($fotos) ? $fotos[0] : '';

        $datos_espacio = [
            'nodo_id'            => $nodo_id,
            'espacio_id'         => $espacio_id,
            'nombre'             => $espacio->nombre,
            'descripcion'        => wp_trim_words($espacio->descripcion, 50),
            'tipo'               => $espacio->tipo,
            'ubicacion'          => $espacio->ubicacion,
            'latitud'            => $espacio->latitud,
            'longitud'           => $espacio->longitud,
            'capacidad_personas' => $espacio->capacidad_personas,
            'superficie_m2'      => $espacio->superficie_m2,
            'precio_hora'        => $espacio->precio_hora,
            'precio_dia'         => $espacio->precio_dia,
            'horario_apertura'   => $espacio->horario_apertura,
            'horario_cierre'     => $espacio->horario_cierre,
            'dias_disponibles'   => $espacio->dias_disponibles,
            'foto_principal'     => $foto_principal,
            'estado'             => $espacio->estado,
            'compartir_en_red'   => 1,
            'visible_en_red'     => 1,
            'actualizado_en'     => current_time('mysql'),
        ];

        // Verificar si ya existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_red WHERE nodo_id = %s AND espacio_id = %d",
            $nodo_id,
            $espacio_id
        ));

        if ($existe) {
            $wpdb->update($tabla_red, $datos_espacio, [
                'nodo_id' => $nodo_id,
                'espacio_id' => $espacio_id
            ]);
        } else {
            $datos_espacio['creado_en'] = current_time('mysql');
            $wpdb->insert($tabla_red, $datos_espacio);
        }

        return true;
    }

    /**
     * Elimina un espacio de la tabla de red
     */
    public function eliminar_espacio_de_red($espacio_id) {
        global $wpdb;

        $tabla_red = $wpdb->prefix . 'flavor_network_spaces';
        $nodo_id = get_option('flavor_network_node_id', '');

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_red'") === $tabla_red) {
            $wpdb->delete($tabla_red, [
                'nodo_id' => $nodo_id,
                'espacio_id' => $espacio_id
            ]);
        }
    }

    /**
     * Sincroniza todos los espacios disponibles con la red
     */
    public function sincronizar_todos_espacios_con_red() {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $espacios = $wpdb->get_results(
            "SELECT id FROM $tabla_espacios WHERE estado = 'disponible'"
        );

        foreach ($espacios as $espacio) {
            $this->sincronizar_espacio_con_red($espacio->id);
        }
    }
}

// Legacy alias for backward compatibility
if (!class_exists('Flavor_Chat_Espacios_Comunes_Module', false)) {
    class_alias('Flavor_Platform_Espacios_Comunes_Module', 'Flavor_Chat_Espacios_Comunes_Module');
}
