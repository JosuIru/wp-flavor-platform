<?php
/**
 * Módulo de Cursos y Formación para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Cursos - Plataforma de formación comunitaria
 */
class Flavor_Chat_Cursos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;
    use Flavor_Encuestas_Features;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_cursos_admin_guardar_curso', [$this, 'ajax_admin_guardar_curso']);
        add_action('wp_ajax_nopriv_cursos_admin_guardar_curso', [$this, 'ajax_admin_guardar_curso']);

        $this->id = 'cursos';
        $this->name = 'Cursos y Formación'; // Translation loaded on init
        $this->description = 'Plataforma de cursos y formación comunitaria - aprende y enseña en tu comunidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_cursos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Cursos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'disponible_app' => 'cliente',
            'requiere_aprobacion_instructores' => true,
            'permite_cursos_gratuitos' => true,
            'permite_cursos_pago' => true,
            'comision_cursos_pago' => 15,
            'max_alumnos_por_curso' => 30,
            'permite_certificados' => true,
            'requiere_evaluacion' => true,
            'permite_cursos_online' => true,
            'permite_cursos_presenciales' => true,
            'categorias' => [
                'tecnologia' => 'Tecnología y Programación',
                'idiomas' => 'Idiomas',
                'cocina' => 'Cocina y Gastronomía',
                'artesania' => 'Artesanía y Manualidades',
                'jardineria' => 'Jardinería Urbana',
                'reparaciones' => 'Reparaciones Domésticas',
                'musica' => 'Música y Arte',
                'salud' => 'Salud y Bienestar',
                'finanzas' => 'Finanzas Personales',
                'otros' => 'Otros',
            ],
        ];
    }

    /**
     * Tipos de contenido que este módulo acepta como integraciones
     *
     * @return array Lista de IDs de tipos de contenido aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'biblioteca', 'podcast'];
    }

    /**
     * Targets donde se pueden vincular integraciones
     *
     * @return array Configuración de targets
     */
    protected function get_integration_targets() {
        return [
            [
                'type' => 'custom_table',
                'table' => 'flavor_cursos',
                'module_id' => $this->id,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar en el panel de administración unificado
        $this->registrar_en_panel_unificado();

        // Registrar como consumidor de integraciones
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // AJAX handlers
        add_action('wp_ajax_cursos_inscribirse', [$this, 'ajax_inscribirse']);
        add_action('wp_ajax_cursos_marcar_leccion', [$this, 'ajax_marcar_leccion']);
        add_action('wp_ajax_cursos_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_cursos_solicitar_certificado', [$this, 'ajax_solicitar_certificado']);

        // Admin AJAX
        add_action('wp_ajax_cursos_admin_guardar', [$this, 'ajax_admin_guardar_curso']);
        add_action('wp_ajax_cursos_admin_guardar_leccion', [$this, 'ajax_admin_guardar_leccion']);
        add_action('wp_ajax_cursos_admin_cambiar_estado', [$this, 'ajax_admin_cambiar_estado']);
        add_action('wp_ajax_cursos_admin_exportar', [$this, 'ajax_admin_exportar']);

        // WP Cron para recordatorios
        if (!wp_next_scheduled('cursos_enviar_recordatorios')) {
            wp_schedule_event(time(), 'daily', 'cursos_enviar_recordatorios');
        }
        add_action('cursos_enviar_recordatorios', [$this, 'enviar_recordatorios']);

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();

        // Integrar funcionalidades de encuestas
        $this->init_encuestas_features('curso');
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-cursos-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Cursos_Frontend_Controller::get_instance();
        }
    }

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'cursos',
            'label' => __('Cursos', 'flavor-chat-ia'),
            'icon' => 'dashicons-welcome-learn-more',
            'capability' => 'manage_options',
            'categoria' => 'actividades',
            'paginas' => [
                [
                    'slug' => 'cursos-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'cursos-listado',
                    'titulo' => __('Cursos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_cursos'],
                    'badge' => [$this, 'contar_cursos_activos'],
                ],
                [
                    'slug' => 'cursos-inscripciones',
                    'titulo' => __('Inscripciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_inscripciones'],
                    'badge' => [$this, 'contar_inscripciones_pendientes'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta los cursos activos
     *
     * @return int
     */
    public function contar_cursos_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_cursos WHERE estado IN ('publicado', 'en_curso')"
        );
    }

    /**
     * Cuenta las inscripciones pendientes
     *
     * @return int
     */
    public function contar_inscripciones_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado = 'pendiente'"
        );
    }

    /**
     * Estadísticas para el dashboard del panel unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        $estadisticas = [
            'cursos_activos' => 0,
            'total_alumnos' => 0,
            'inscripciones_mes' => 0,
            'cursos_completados' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            $estadisticas['cursos_activos'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_cursos WHERE estado IN ('publicado', 'en_curso')"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            $estadisticas['total_alumnos'] = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT alumno_id) FROM $tabla_inscripciones WHERE estado IN ('activa', 'completada')"
            );

            $estadisticas['inscripciones_mes'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_inscripciones
                 WHERE fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );

            $estadisticas['cursos_completados'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado = 'completada'"
            );
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        $estadisticas = $this->get_estadisticas_dashboard();
        include $this->get_module_path() . 'templates/admin/dashboard.php';
    }

    /**
     * Renderiza el listado de cursos en admin
     */
    public function render_admin_cursos() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $cursos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            $cursos = $wpdb->get_results(
                "SELECT * FROM $tabla_cursos ORDER BY fecha_creacion DESC"
            );
        }

        include $this->get_module_path() . 'templates/admin/cursos.php';
    }

    /**
     * Renderiza el listado de inscripciones en admin
     */
    public function render_admin_inscripciones() {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $inscripciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            $inscripciones = $wpdb->get_results(
                "SELECT i.*, c.titulo as curso_titulo, u.display_name as alumno_nombre, u.user_email as alumno_email
                 FROM $tabla_inscripciones i
                 INNER JOIN $tabla_cursos c ON i.curso_id = c.id
                 INNER JOIN {$wpdb->users} u ON i.alumno_id = u.ID
                 ORDER BY i.fecha_inscripcion DESC
                 LIMIT 100"
            );
        }

        include $this->get_module_path() . 'templates/admin/inscripciones.php';
    }

    /**
     * Registrar shortcodes
     * Solo registra si no existen ya (el Frontend Controller puede haberlos registrado antes)
     */
    public function register_shortcodes() {
        // Shortcodes que pueden estar en el Frontend Controller - registrar solo como fallback
        if (!shortcode_exists('cursos_catalogo')) {
            add_shortcode('cursos_catalogo', [$this, 'shortcode_catalogo']);
        }
        if (!shortcode_exists('cursos_mis_cursos')) {
            add_shortcode('cursos_mis_cursos', [$this, 'shortcode_mis_cursos']);
        }
        if (!shortcode_exists('cursos_aula')) {
            add_shortcode('cursos_aula', [$this, 'shortcode_aula']);
        }

        // Shortcodes exclusivos del módulo principal
        add_shortcode('cursos_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('cursos_instructor', [$this, 'shortcode_instructor']);
        add_shortcode('cursos_certificado', [$this, 'shortcode_certificado']);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Públicos
        register_rest_route($namespace, '/cursos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_cursos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/cursos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_detalle_curso'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/cursos/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Requieren autenticación
        register_rest_route($namespace, '/cursos/inscribirse', [
            'methods' => 'POST',
            'callback' => [$this, 'api_inscribirse'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/mis-cursos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_cursos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/(?P<id>\d+)/lecciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_lecciones'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/leccion/(?P<id>\d+)/completar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_completar_leccion'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/(?P<id>\d+)/valorar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_valorar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/(?P<id>\d+)/certificado', [
            'methods' => 'POST',
            'callback' => [$this, 'api_solicitar_certificado'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Instructor
        register_rest_route($namespace, '/cursos/instructor/mis-cursos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_instructor_mis_cursos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/cursos/instructor/estadisticas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_instructor_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    /**
     * Check si usuario está logueado
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            $this->create_tables();
        }
    }

    /**
     * Migra las tablas a la versión actual si es necesario
     */
    public function maybe_migrate_tables() {
        $version_actual = get_option('flavor_cursos_db_version', '1.0.0');

        if (version_compare($version_actual, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('flavor_cursos_db_version', '1.1.0');
        }
    }

    /**
     * Migración a versión 1.1.0 - Agrega columna destacado
     */
    private function migrate_to_1_1_0() {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
            return;
        }

        // Verificar y agregar columna destacado
        $columna_existe = $wpdb->get_results(
            $wpdb->prepare("SHOW COLUMNS FROM $tabla_cursos LIKE %s", 'destacado')
        );

        if (empty($columna_existe)) {
            $wpdb->query("ALTER TABLE $tabla_cursos ADD COLUMN destacado tinyint(1) DEFAULT 0 AFTER numero_valoraciones");
            $wpdb->query("ALTER TABLE $tabla_cursos ADD KEY destacado (destacado)");
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';

        $sql_cursos = "CREATE TABLE IF NOT EXISTS $tabla_cursos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instructor_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            descripcion_corta varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            modalidad enum('online','presencial','mixto') DEFAULT 'online',
            duracion_horas int(11) NOT NULL,
            max_alumnos int(11) DEFAULT 30,
            precio decimal(10,2) DEFAULT 0,
            es_gratuito tinyint(1) DEFAULT 1,
            requisitos text DEFAULT NULL,
            que_aprenderas text DEFAULT NULL,
            imagen_portada varchar(500) DEFAULT NULL,
            video_presentacion varchar(500) DEFAULT NULL,
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            horario varchar(255) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            alumnos_inscritos int(11) DEFAULT 0,
            valoracion_media decimal(3,2) DEFAULT 0,
            numero_valoraciones int(11) DEFAULT 0,
            destacado tinyint(1) DEFAULT 0,
            estado enum('borrador','pendiente','publicado','en_curso','finalizado','cancelado') DEFAULT 'borrador',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY instructor_id (instructor_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio),
            KEY destacado (destacado)
        ) $charset_collate;";

        $sql_lecciones = "CREATE TABLE IF NOT EXISTS $tabla_lecciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) unsigned NOT NULL,
            numero_orden int(11) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('video','texto','quiz','archivo','enlace','live') DEFAULT 'texto',
            contenido longtext DEFAULT NULL,
            video_url varchar(500) DEFAULT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            es_gratuita tinyint(1) DEFAULT 0,
            es_obligatoria tinyint(1) DEFAULT 1,
            puntos int(11) DEFAULT 10,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY curso_id (curso_id),
            KEY numero_orden (numero_orden)
        ) $charset_collate;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) unsigned NOT NULL,
            alumno_id bigint(20) unsigned NOT NULL,
            precio_pagado decimal(10,2) DEFAULT 0,
            metodo_pago varchar(50) DEFAULT NULL,
            transaccion_id varchar(100) DEFAULT NULL,
            progreso_porcentaje decimal(5,2) DEFAULT 0,
            lecciones_completadas int(11) DEFAULT 0,
            puntos_obtenidos int(11) DEFAULT 0,
            tiempo_total_minutos int(11) DEFAULT 0,
            fecha_ultima_actividad datetime DEFAULT NULL,
            estado enum('pendiente','activa','completada','abandonada','suspendida','reembolsada') DEFAULT 'pendiente',
            certificado_emitido tinyint(1) DEFAULT 0,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY curso_alumno (curso_id, alumno_id),
            KEY alumno_id (alumno_id),
            KEY estado (estado),
            KEY fecha_inscripcion (fecha_inscripcion)
        ) $charset_collate;";

        $sql_progreso = "CREATE TABLE IF NOT EXISTS $tabla_progreso (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            inscripcion_id bigint(20) unsigned NOT NULL,
            leccion_id bigint(20) unsigned NOT NULL,
            completada tinyint(1) DEFAULT 0,
            tiempo_dedicado_minutos int(11) DEFAULT 0,
            puntuacion decimal(5,2) DEFAULT NULL,
            intentos int(11) DEFAULT 0,
            respuestas_quiz longtext DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_leccion (inscripcion_id, leccion_id),
            KEY leccion_id (leccion_id)
        ) $charset_collate;";

        $sql_certificados = "CREATE TABLE IF NOT EXISTS $tabla_certificados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            inscripcion_id bigint(20) unsigned NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            alumno_id bigint(20) unsigned NOT NULL,
            codigo_verificacion varchar(100) NOT NULL,
            nota_final decimal(5,2) DEFAULT NULL,
            horas_completadas int(11) DEFAULT NULL,
            fecha_emision datetime DEFAULT CURRENT_TIMESTAMP,
            pdf_url varchar(500) DEFAULT NULL,
            estado enum('generando','emitido','revocado') DEFAULT 'generando',
            PRIMARY KEY (id),
            UNIQUE KEY inscripcion_id (inscripcion_id),
            UNIQUE KEY codigo_verificacion (codigo_verificacion),
            KEY alumno_id (alumno_id),
            KEY curso_id (curso_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_cursos);
        dbDelta($sql_lecciones);
        dbDelta($sql_inscripciones);
        dbDelta($sql_progreso);
        dbDelta($sql_certificados);
    }

    // =========================================================================
    // ACCIONES DEL MÓDULO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'catalogo_cursos' => [
                'description' => 'Ver catálogo de cursos',
                'params' => ['categoria', 'nivel', 'modalidad', 'busqueda'],
            ],
            'detalle_curso' => [
                'description' => 'Ver detalles del curso',
                'params' => ['curso_id'],
            ],
            'inscribirse' => [
                'description' => 'Inscribirse en curso',
                'params' => ['curso_id'],
            ],
            'mis_cursos' => [
                'description' => 'Mis cursos inscritos',
                'params' => ['estado'],
            ],
            'mis_cursos_instructor' => [
                'description' => 'Cursos que imparto',
                'params' => [],
            ],
            'ver_leccion' => [
                'description' => 'Ver lección del curso',
                'params' => ['leccion_id'],
            ],
            'marcar_completada' => [
                'description' => 'Marcar lección completada',
                'params' => ['leccion_id', 'tiempo_minutos'],
            ],
            'valorar_curso' => [
                'description' => 'Valorar curso completado',
                'params' => ['curso_id', 'valoracion', 'comentario'],
            ],
            'solicitar_certificado' => [
                'description' => 'Solicitar certificado',
                'params' => ['curso_id'],
            ],
            'crear_curso' => [
                'description' => 'Crear nuevo curso (instructor)',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'estadisticas_curso' => [
                'description' => 'Estadísticas del curso (admin)',
                'params' => ['curso_id'],
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
     * Acción: Ver catálogo
     */
    public function action_catalogo_cursos($params) {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $where = ["estado IN ('publicado', 'en_curso')"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['nivel'])) {
            $where[] = 'nivel = %s';
            $prepare_values[] = sanitize_text_field($params['nivel']);
        }

        if (!empty($params['modalidad'])) {
            $where[] = 'modalidad = %s';
            $prepare_values[] = sanitize_text_field($params['modalidad']);
        }

        if (!empty($params['busqueda'])) {
            $where[] = '(titulo LIKE %s OR descripcion LIKE %s)';
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($params['busqueda'])) . '%';
            $prepare_values[] = $busqueda;
            $prepare_values[] = $busqueda;
        }

        $sql = "SELECT * FROM $tabla_cursos WHERE " . implode(' AND ', $where) . " ORDER BY destacado DESC, fecha_inicio DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $cursos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $cursos = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'cursos' => array_map([$this, 'formatear_curso_lista'], $cursos),
        ];
    }

    /**
     * Acción: Detalle curso
     */
    private function action_detalle_curso($params) {
        if (empty($params['curso_id'])) {
            return ['success' => false, 'error' => __('flavor_cursos', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';

        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cursos WHERE id = %d",
            intval($params['curso_id'])
        ));

        if (!$curso) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Obtener lecciones (solo títulos públicamente)
        $lecciones = $wpdb->get_results($wpdb->prepare(
            "SELECT id, numero_orden, titulo, tipo, duracion_minutos, es_gratuita
             FROM $tabla_lecciones
             WHERE curso_id = %d
             ORDER BY numero_orden ASC",
            $curso->id
        ));

        // Verificar si el usuario está inscrito
        $inscripcion = null;
        if (is_user_logged_in()) {
            $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
            $inscripcion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_inscripciones WHERE curso_id = %d AND alumno_id = %d",
                $curso->id,
                get_current_user_id()
            ));
        }

        $instructor = get_userdata($curso->instructor_id);

        return [
            'success' => true,
            'curso' => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'descripcion' => $curso->descripcion,
                'descripcion_corta' => $curso->descripcion_corta,
                'instructor' => [
                    'id' => $curso->instructor_id,
                    'nombre' => $instructor ? $instructor->display_name : 'Instructor',
                    'avatar' => get_avatar_url($curso->instructor_id),
                ],
                'categoria' => $curso->categoria,
                'nivel' => $curso->nivel,
                'modalidad' => $curso->modalidad,
                'duracion_horas' => $curso->duracion_horas,
                'max_alumnos' => $curso->max_alumnos,
                'precio' => floatval($curso->precio),
                'es_gratuito' => (bool)$curso->es_gratuito,
                'requisitos' => $curso->requisitos ? json_decode($curso->requisitos, true) : [],
                'que_aprenderas' => $curso->que_aprenderas ? json_decode($curso->que_aprenderas, true) : [],
                'imagen' => $curso->imagen_portada,
                'video' => $curso->video_presentacion,
                'fecha_inicio' => $curso->fecha_inicio,
                'fecha_fin' => $curso->fecha_fin,
                'horario' => $curso->horario,
                'ubicacion' => $curso->ubicacion,
                'alumnos' => $curso->alumnos_inscritos,
                'valoracion' => floatval($curso->valoracion_media),
                'num_valoraciones' => $curso->numero_valoraciones,
                'estado' => $curso->estado,
                'plazas_disponibles' => max(0, $curso->max_alumnos - $curso->alumnos_inscritos),
            ],
            'lecciones' => array_map(function($l) {
                return [
                    'id' => $l->id,
                    'orden' => $l->numero_orden,
                    'titulo' => $l->titulo,
                    'tipo' => $l->tipo,
                    'duracion' => $l->duracion_minutos,
                    'es_gratuita' => (bool)$l->es_gratuita,
                ];
            }, $lecciones),
            'inscripcion' => $inscripcion ? [
                'estado' => $inscripcion->estado,
                'progreso' => floatval($inscripcion->progreso_porcentaje),
                'fecha' => $inscripcion->fecha_inscripcion,
                'certificado' => (bool)$inscripcion->certificado_emitido,
            ] : null,
        ];
    }

    /**
     * Acción: Inscribirse
     */
    private function action_inscribirse($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('fecha_inicio', 'flavor-chat-ia')];
        }

        if (empty($params['curso_id'])) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $usuario_id = get_current_user_id();
        $curso_id = intval($params['curso_id']);

        // Verificar que el curso existe y está abierto
        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cursos WHERE id = %d AND estado IN ('publicado', 'en_curso')",
            $curso_id
        ));

        if (!$curso) {
            return ['success' => false, 'error' => __('progreso', 'flavor-chat-ia')];
        }

        // Verificar plazas
        if ($curso->alumnos_inscritos >= $curso->max_alumnos) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Verificar que no esté ya inscrito
        $ya_inscrito = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE curso_id = %d AND alumno_id = %d",
            $curso_id,
            $usuario_id
        ));

        if ($ya_inscrito) {
            return ['success' => false, 'error' => __('leccion_id', 'flavor-chat-ia')];
        }

        // Si es de pago, verificar pago (simplificado - integrar con pasarela)
        $estado_inscripcion = 'activa';
        if (!$curso->es_gratuito && $curso->precio > 0) {
            // Aquí iría la lógica de pago
            // Por ahora, marcamos como pendiente
            $estado_inscripcion = 'pendiente';
        }

        // Crear inscripción
        $resultado = $wpdb->insert($tabla_inscripciones, [
            'curso_id' => $curso_id,
            'alumno_id' => $usuario_id,
            'precio_pagado' => $curso->es_gratuito ? 0 : $curso->precio,
            'estado' => $estado_inscripcion,
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        if (!$resultado) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_cursos SET alumnos_inscritos = alumnos_inscritos + 1 WHERE id = %d",
            $curso_id
        ));

        // Notificar al usuario
        $this->enviar_notificacion_inscripcion($usuario_id, $curso);

        return [
            'success' => true,
            'mensaje' => $estado_inscripcion === 'activa'
                ? '¡Te has inscrito correctamente!'
                : 'Inscripción pendiente de pago',
            'inscripcion_id' => $wpdb->insert_id,
            'estado' => $estado_inscripcion,
        ];
    }

    /**
     * Acción: Mis cursos
     */
    private function action_mis_cursos($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('fecha_completado', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $usuario_id = get_current_user_id();

        $where_estado = '';
        if (!empty($params['estado'])) {
            $where_estado = $wpdb->prepare(" AND i.estado = %s", sanitize_text_field($params['estado']));
        }

        $cursos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, i.estado as estado_inscripcion, i.progreso_porcentaje,
                    i.lecciones_completadas, i.fecha_inscripcion, i.certificado_emitido
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_cursos c ON i.curso_id = c.id
             WHERE i.alumno_id = %d $where_estado
             ORDER BY i.fecha_ultima_actividad DESC, i.fecha_inscripcion DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'cursos' => array_map(function($c) {
                $instructor = get_userdata($c->instructor_id);
                return [
                    'id' => $c->id,
                    'titulo' => $c->titulo,
                    'imagen' => $c->imagen_portada,
                    'instructor' => $instructor ? $instructor->display_name : 'Instructor',
                    'categoria' => $c->categoria,
                    'duracion_horas' => $c->duracion_horas,
                    'estado_inscripcion' => $c->estado_inscripcion,
                    'progreso' => floatval($c->progreso_porcentaje),
                    'lecciones_completadas' => $c->lecciones_completadas,
                    'fecha_inscripcion' => $c->fecha_inscripcion,
                    'certificado' => (bool)$c->certificado_emitido,
                ];
            }, $cursos),
        ];
    }

    /**
     * Acción: Ver lección
     */
    private function action_ver_leccion($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        if (empty($params['leccion_id'])) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $usuario_id = get_current_user_id();
        $leccion_id = intval($params['leccion_id']);

        // Obtener lección
        $leccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_lecciones WHERE id = %d",
            $leccion_id
        ));

        if (!$leccion) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Verificar inscripción (a menos que sea gratuita)
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE curso_id = %d AND alumno_id = %d AND estado = 'activa'",
            $leccion->curso_id,
            $usuario_id
        ));

        if (!$inscripcion && !$leccion->es_gratuita) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Obtener o crear progreso
        $progreso = null;
        if ($inscripcion) {
            $progreso = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_progreso
                 WHERE inscripcion_id = %d AND leccion_id = %d",
                $inscripcion->id,
                $leccion_id
            ));

            if (!$progreso) {
                $wpdb->insert($tabla_progreso, [
                    'inscripcion_id' => $inscripcion->id,
                    'leccion_id' => $leccion_id,
                    'fecha_inicio' => current_time('mysql'),
                ]);
            }
        }

        return [
            'success' => true,
            'leccion' => [
                'id' => $leccion->id,
                'curso_id' => $leccion->curso_id,
                'orden' => $leccion->numero_orden,
                'titulo' => $leccion->titulo,
                'descripcion' => $leccion->descripcion,
                'tipo' => $leccion->tipo,
                'contenido' => $leccion->contenido,
                'video_url' => $leccion->video_url,
                'archivo_url' => $leccion->archivo_url,
                'duracion' => $leccion->duracion_minutos,
                'puntos' => $leccion->puntos,
            ],
            'progreso' => $progreso ? [
                'completada' => (bool)$progreso->completada,
                'tiempo_dedicado' => $progreso->tiempo_dedicado_minutos,
                'puntuacion' => $progreso->puntuacion,
            ] : null,
        ];
    }

    /**
     * Acción: Marcar lección completada
     */
    private function action_marcar_completada($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        if (empty($params['leccion_id'])) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $usuario_id = get_current_user_id();
        $leccion_id = intval($params['leccion_id']);
        $tiempo = isset($params['tiempo_minutos']) ? intval($params['tiempo_minutos']) : 0;

        // Obtener lección
        $leccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_lecciones WHERE id = %d",
            $leccion_id
        ));

        if (!$leccion) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        // Verificar inscripción
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE curso_id = %d AND alumno_id = %d AND estado = 'activa'",
            $leccion->curso_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Actualizar o crear progreso
        $progreso_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_progreso WHERE inscripcion_id = %d AND leccion_id = %d",
            $inscripcion->id,
            $leccion_id
        ));

        if ($progreso_existente) {
            if (!$progreso_existente->completada) {
                $wpdb->update(
                    $tabla_progreso,
                    [
                        'completada' => 1,
                        'tiempo_dedicado_minutos' => $progreso_existente->tiempo_dedicado_minutos + $tiempo,
                        'fecha_completado' => current_time('mysql'),
                    ],
                    ['id' => $progreso_existente->id]
                );
            }
        } else {
            $wpdb->insert($tabla_progreso, [
                'inscripcion_id' => $inscripcion->id,
                'leccion_id' => $leccion_id,
                'completada' => 1,
                'tiempo_dedicado_minutos' => $tiempo,
                'fecha_inicio' => current_time('mysql'),
                'fecha_completado' => current_time('mysql'),
            ]);
        }

        // Actualizar progreso general del curso
        $this->actualizar_progreso_curso($inscripcion->id, $leccion->curso_id);

        return [
            'success' => true,
            'mensaje' => __('¡Lección completada!', 'flavor-chat-ia'),
            'puntos' => $leccion->puntos,
        ];
    }

    /**
     * Actualizar progreso del curso
     */
    private function actualizar_progreso_curso($inscripcion_id, $curso_id) {
        global $wpdb;
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

        // Contar lecciones totales y completadas
        $total_lecciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_lecciones WHERE curso_id = %d AND es_obligatoria = 1",
            $curso_id
        ));

        $lecciones_completadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_progreso p
             INNER JOIN $tabla_lecciones l ON p.leccion_id = l.id
             WHERE p.inscripcion_id = %d AND p.completada = 1 AND l.es_obligatoria = 1",
            $inscripcion_id
        ));

        // Calcular porcentaje
        $porcentaje = $total_lecciones > 0 ? ($lecciones_completadas / $total_lecciones) * 100 : 0;

        // Sumar puntos
        $puntos = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(l.puntos), 0) FROM $tabla_progreso p
             INNER JOIN $tabla_lecciones l ON p.leccion_id = l.id
             WHERE p.inscripcion_id = %d AND p.completada = 1",
            $inscripcion_id
        ));

        // Sumar tiempo
        $tiempo = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(tiempo_dedicado_minutos), 0) FROM $tabla_progreso WHERE inscripcion_id = %d",
            $inscripcion_id
        ));

        // Actualizar inscripción
        $estado = $porcentaje >= 100 ? 'completada' : 'activa';
        $fecha_completado = $porcentaje >= 100 ? current_time('mysql') : null;

        $wpdb->update(
            $tabla_inscripciones,
            [
                'progreso_porcentaje' => $porcentaje,
                'lecciones_completadas' => $lecciones_completadas,
                'puntos_obtenidos' => $puntos,
                'tiempo_total_minutos' => $tiempo,
                'fecha_ultima_actividad' => current_time('mysql'),
                'estado' => $estado,
                'fecha_completado' => $fecha_completado,
            ],
            ['id' => $inscripcion_id]
        );
    }

    /**
     * Acción: Valorar curso
     */
    private function action_valorar_curso($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('flavor_cursos_inscripciones', 'flavor-chat-ia')];
        }

        if (empty($params['curso_id']) || !isset($params['valoracion'])) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $usuario_id = get_current_user_id();
        $curso_id = intval($params['curso_id']);
        $valoracion = max(1, min(5, intval($params['valoracion'])));
        $comentario = isset($params['comentario']) ? sanitize_textarea_field($params['comentario']) : '';

        // Verificar inscripción completada
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE curso_id = %d AND alumno_id = %d AND estado = 'completada'",
            $curso_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Guardar valoración
        $wpdb->update(
            $tabla_inscripciones,
            [
                'valoracion' => $valoracion,
                'comentario_valoracion' => $comentario,
            ],
            ['id' => $inscripcion->id]
        );

        // Recalcular media del curso
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(valoracion) as media, COUNT(*) as total
             FROM $tabla_inscripciones
             WHERE curso_id = %d AND valoracion IS NOT NULL",
            $curso_id
        ));

        $wpdb->update(
            $tabla_cursos,
            [
                'valoracion_media' => $stats->media,
                'numero_valoraciones' => $stats->total,
            ],
            ['id' => $curso_id]
        );

        return [
            'success' => true,
            'mensaje' => __('¡Gracias por tu valoración!', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Solicitar certificado
     */
    private function action_solicitar_certificado($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        if (empty($params['curso_id'])) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';
        $usuario_id = get_current_user_id();
        $curso_id = intval($params['curso_id']);

        // Verificar inscripción completada
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE curso_id = %d AND alumno_id = %d AND estado = 'completada'",
            $curso_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Verificar si ya tiene certificado
        $certificado_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_certificados WHERE inscripcion_id = %d",
            $inscripcion->id
        ));

        if ($certificado_existente) {
            return [
                'success' => true,
                'certificado' => [
                    'codigo' => $certificado_existente->codigo_verificacion,
                    'fecha' => $certificado_existente->fecha_emision,
                    'pdf_url' => $certificado_existente->pdf_url,
                ],
            ];
        }

        // Generar certificado
        $codigo = 'CERT-' . strtoupper(wp_generate_password(8, false));

        $wpdb->insert($tabla_certificados, [
            'inscripcion_id' => $inscripcion->id,
            'curso_id' => $curso_id,
            'alumno_id' => $usuario_id,
            'codigo_verificacion' => $codigo,
            'nota_final' => $inscripcion->puntos_obtenidos,
            'horas_completadas' => ceil($inscripcion->tiempo_total_minutos / 60),
            'estado' => 'emitido',
        ]);

        // Marcar en inscripción
        $wpdb->update(
            $tabla_inscripciones,
            ['certificado_emitido' => 1],
            ['id' => $inscripcion->id]
        );

        return [
            'success' => true,
            'mensaje' => __('¡Certificado generado!', 'flavor-chat-ia'),
            'certificado' => [
                'codigo' => $codigo,
                'fecha' => current_time('mysql'),
            ],
        ];
    }

    /**
     * Acción: Cursos del instructor
     */
    private function action_mis_cursos_instructor($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $usuario_id = get_current_user_id();

        $cursos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_cursos WHERE instructor_id = %d ORDER BY fecha_creacion DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'cursos' => array_map(function($c) {
                return [
                    'id' => $c->id,
                    'titulo' => $c->titulo,
                    'estado' => $c->estado,
                    'alumnos' => $c->alumnos_inscritos,
                    'valoracion' => floatval($c->valoracion_media),
                    'fecha_creacion' => $c->fecha_creacion,
                ];
            }, $cursos),
        ];
    }

    /**
     * Acción: Estadísticas del curso
     */
    private function action_estadisticas_curso($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        if (empty($params['curso_id'])) {
            return ['success' => false, 'error' => __('curso_id', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $usuario_id = get_current_user_id();
        $curso_id = intval($params['curso_id']);

        // Verificar que es el instructor
        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cursos WHERE id = %d AND instructor_id = %d",
            $curso_id,
            $usuario_id
        ));

        if (!$curso && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('ID de curso requerido', 'flavor-chat-ia')];
        }

        // Estadísticas
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_inscritos,
                SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'abandonada' THEN 1 ELSE 0 END) as abandonados,
                AVG(progreso_porcentaje) as progreso_medio,
                AVG(tiempo_total_minutos) as tiempo_medio
             FROM $tabla_inscripciones WHERE curso_id = %d",
            $curso_id
        ));

        return [
            'success' => true,
            'estadisticas' => [
                'total_inscritos' => intval($stats->total_inscritos),
                'activos' => intval($stats->activos),
                'completados' => intval($stats->completados),
                'abandonados' => intval($stats->abandonados),
                'tasa_completado' => $stats->total_inscritos > 0
                    ? round(($stats->completados / $stats->total_inscritos) * 100, 1)
                    : 0,
                'progreso_medio' => round(floatval($stats->progreso_medio), 1),
                'tiempo_medio_horas' => round($stats->tiempo_medio / 60, 1),
                'valoracion' => floatval($curso->valoracion_media),
                'ingresos' => $curso->es_gratuito ? 0 : ($stats->total_inscritos * $curso->precio),
            ],
        ];
    }

    // =========================================================================
    // API REST
    // =========================================================================

    /**
     * API: Listar cursos
     */
    public function api_listar_cursos($request) {
        $params = [
            'categoria' => $request->get_param('categoria'),
            'nivel' => $request->get_param('nivel'),
            'modalidad' => $request->get_param('modalidad'),
            'busqueda' => $request->get_param('q'),
        ];
        return rest_ensure_response($this->action_catalogo_cursos($params));
    }

    /**
     * API: Detalle curso
     */
    public function api_detalle_curso($request) {
        return rest_ensure_response($this->action_detalle_curso([
            'curso_id' => $request->get_param('id'),
        ]));
    }

    /**
     * API: Categorías
     */
    public function api_categorias($request) {
        $settings = $this->get_settings();
        $categorias = $settings['categorias'] ?? [];

        return rest_ensure_response([
            'success' => true,
            'categorias' => $categorias,
        ]);
    }

    /**
     * API: Inscribirse
     */
    public function api_inscribirse($request) {
        return rest_ensure_response($this->action_inscribirse([
            'curso_id' => $request->get_param('curso_id'),
        ]));
    }

    /**
     * API: Mis cursos
     */
    public function api_mis_cursos($request) {
        return rest_ensure_response($this->action_mis_cursos([
            'estado' => $request->get_param('estado'),
        ]));
    }

    /**
     * API: Lecciones del curso
     */
    public function api_lecciones($request) {
        global $wpdb;
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

        $curso_id = intval($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Verificar inscripción
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones
             WHERE curso_id = %d AND alumno_id = %d AND estado IN ('activa', 'completada')",
            $curso_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return rest_ensure_response([
                'success' => false,
                'error' => __('No estás inscrito en este curso.', 'flavor-chat-ia'),
            ]);
        }

        // Obtener lecciones con progreso
        $lecciones = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.completada, p.tiempo_dedicado_minutos, p.puntuacion
             FROM $tabla_lecciones l
             LEFT JOIN $tabla_progreso p ON l.id = p.leccion_id AND p.inscripcion_id = %d
             WHERE l.curso_id = %d
             ORDER BY l.numero_orden ASC",
            $inscripcion->id,
            $curso_id
        ));

        return rest_ensure_response([
            'success' => true,
            'lecciones' => array_map(function($l) {
                return [
                    'id' => $l->id,
                    'orden' => $l->numero_orden,
                    'titulo' => $l->titulo,
                    'descripcion' => $l->descripcion,
                    'tipo' => $l->tipo,
                    'duracion' => $l->duracion_minutos,
                    'completada' => (bool)$l->completada,
                    'tiempo_dedicado' => $l->tiempo_dedicado_minutos,
                    'puntuacion' => $l->puntuacion,
                ];
            }, $lecciones),
            'progreso' => [
                'porcentaje' => floatval($inscripcion->progreso_porcentaje),
                'completadas' => $inscripcion->lecciones_completadas,
            ],
        ]);
    }

    /**
     * API: Completar lección
     */
    public function api_completar_leccion($request) {
        return rest_ensure_response($this->action_marcar_completada([
            'leccion_id' => $request->get_param('id'),
            'tiempo_minutos' => $request->get_param('tiempo'),
        ]));
    }

    /**
     * API: Valorar curso
     */
    public function api_valorar($request) {
        return rest_ensure_response($this->action_valorar_curso([
            'curso_id' => $request->get_param('id'),
            'valoracion' => $request->get_param('valoracion'),
            'comentario' => $request->get_param('comentario'),
        ]));
    }

    /**
     * API: Solicitar certificado
     */
    public function api_solicitar_certificado($request) {
        return rest_ensure_response($this->action_solicitar_certificado([
            'curso_id' => $request->get_param('id'),
        ]));
    }

    /**
     * API: Cursos del instructor
     */
    public function api_instructor_mis_cursos($request) {
        return rest_ensure_response($this->action_mis_cursos_instructor([]));
    }

    /**
     * API: Estadísticas del instructor
     */
    public function api_instructor_estadisticas($request) {
        return rest_ensure_response($this->action_estadisticas_curso([
            'curso_id' => $request->get_param('id'),
        ]));
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Inscribirse
     */
    public function ajax_inscribirse() {
        check_ajax_referer('cursos_nonce', 'nonce');

        $resultado = $this->action_inscribirse([
            'curso_id' => isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Marcar lección
     */
    public function ajax_marcar_leccion() {
        check_ajax_referer('cursos_nonce', 'nonce');

        $resultado = $this->action_marcar_completada([
            'leccion_id' => isset($_POST['leccion_id']) ? intval($_POST['leccion_id']) : 0,
            'tiempo_minutos' => isset($_POST['tiempo']) ? intval($_POST['tiempo']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Valorar curso
     */
    public function ajax_valorar() {
        check_ajax_referer('cursos_nonce', 'nonce');

        $resultado = $this->action_valorar_curso([
            'curso_id' => isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0,
            'valoracion' => isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0,
            'comentario' => isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '',
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Solicitar certificado
     */
    public function ajax_solicitar_certificado() {
        check_ajax_referer('cursos_nonce', 'nonce');

        $resultado = $this->action_solicitar_certificado([
            'curso_id' => isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX Admin: Guardar curso
     */
    public function ajax_admin_guardar_curso() {
        check_ajax_referer('cursos_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $curso_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');

        if (empty($titulo)) {
            wp_send_json(['success' => false, 'error' => __('modalidad', 'flavor-chat-ia')]);
        }

        $datos = [
            'titulo' => $titulo,
            'slug' => sanitize_title($titulo),
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'descripcion_corta' => sanitize_textarea_field($_POST['descripcion_corta'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'nivel' => sanitize_text_field($_POST['nivel'] ?? 'todos'),
            'modalidad' => sanitize_text_field($_POST['modalidad'] ?? 'online'),
            'duracion_horas' => intval($_POST['duracion_horas'] ?? 1),
            'max_alumnos' => intval($_POST['max_alumnos'] ?? 30),
            'precio' => floatval($_POST['precio'] ?? 0),
            'es_gratuito' => isset($_POST['es_gratuito']) ? 1 : 0,
            'requisitos' => isset($_POST['requisitos']) ? wp_json_encode($_POST['requisitos']) : null,
            'que_aprenderas' => isset($_POST['que_aprenderas']) ? wp_json_encode($_POST['que_aprenderas']) : null,
            'imagen_portada' => esc_url_raw($_POST['imagen_portada'] ?? ''),
            'video_presentacion' => esc_url_raw($_POST['video_presentacion'] ?? ''),
            'fecha_inicio' => !empty($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : null,
            'fecha_fin' => !empty($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : null,
            'horario' => sanitize_text_field($_POST['horario'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
        ];

        if ($curso_id > 0) {
            // Actualizar
            $wpdb->update($tabla_cursos, $datos, ['id' => $curso_id]);
            $mensaje = 'Curso actualizado';
        } else {
            // Crear
            $datos['instructor_id'] = get_current_user_id();
            $datos['estado'] = 'borrador';
            $wpdb->insert($tabla_cursos, $datos);
            $curso_id = $wpdb->insert_id;
            $mensaje = 'Curso creado';
        }

        wp_send_json([
            'success' => true,
            'mensaje' => $mensaje,
            'curso_id' => $curso_id,
        ]);
    }

    /**
     * AJAX Admin: Guardar lección
     */
    public function ajax_admin_guardar_leccion() {
        check_ajax_referer('cursos_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';

        $leccion_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $curso_id = intval($_POST['curso_id'] ?? 0);
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');

        if (empty($curso_id) || empty($titulo)) {
            wp_send_json(['success' => false, 'error' => __('assets/js/cursos-frontend.js', 'flavor-chat-ia')]);
        }

        $datos = [
            'curso_id' => $curso_id,
            'titulo' => $titulo,
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'texto'),
            'contenido' => wp_kses_post($_POST['contenido'] ?? ''),
            'video_url' => esc_url_raw($_POST['video_url'] ?? ''),
            'archivo_url' => esc_url_raw($_POST['archivo_url'] ?? ''),
            'duracion_minutos' => intval($_POST['duracion_minutos'] ?? 0),
            'es_gratuita' => isset($_POST['es_gratuita']) ? 1 : 0,
            'es_obligatoria' => isset($_POST['es_obligatoria']) ? 1 : 1,
            'puntos' => intval($_POST['puntos'] ?? 10),
        ];

        if ($leccion_id > 0) {
            $wpdb->update($tabla_lecciones, $datos, ['id' => $leccion_id]);
        } else {
            // Obtener siguiente orden
            $max_orden = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(numero_orden) FROM $tabla_lecciones WHERE curso_id = %d",
                $curso_id
            ));
            $datos['numero_orden'] = ($max_orden ?? 0) + 1;
            $wpdb->insert($tabla_lecciones, $datos);
            $leccion_id = $wpdb->insert_id;
        }

        wp_send_json([
            'success' => true,
            'mensaje' => __('Lección guardada', 'flavor-chat-ia'),
            'leccion_id' => $leccion_id,
        ]);
    }

    /**
     * AJAX Admin: Cambiar estado
     */
    public function ajax_admin_cambiar_estado() {
        check_ajax_referer('cursos_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $curso_id = intval($_POST['curso_id'] ?? 0);
        $estado = sanitize_text_field($_POST['estado'] ?? '');

        $estados_validos = ['borrador', 'pendiente', 'publicado', 'en_curso', 'finalizado', 'cancelado'];

        if (!in_array($estado, $estados_validos)) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $wpdb->update($tabla_cursos, ['estado' => $estado], ['id' => $curso_id]);

        wp_send_json([
            'success' => true,
            'mensaje' => __('Estado actualizado', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX Admin: Exportar alumnos
     */
    public function ajax_admin_exportar() {
        check_ajax_referer('cursos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $curso_id = intval($_POST['curso_id'] ?? 0);

        $inscritos = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, u.user_email, u.display_name
             FROM $tabla_inscripciones i
             INNER JOIN {$wpdb->users} u ON i.alumno_id = u.ID
             WHERE i.curso_id = %d
             ORDER BY i.fecha_inscripcion DESC",
            $curso_id
        ));

        $csv_data = "Nombre,Email,Estado,Progreso,Fecha Inscripción\n";
        foreach ($inscritos as $inscrito) {
            $csv_data .= sprintf(
                '"%s","%s","%s","%s%%","%s"' . "\n",
                $inscrito->display_name,
                $inscrito->user_email,
                $inscrito->estado,
                $inscrito->progreso_porcentaje,
                $inscrito->fecha_inscripcion
            );
        }

        wp_send_json([
            'success' => true,
            'csv' => $csv_data,
            'filename' => 'alumnos-curso-' . $curso_id . '.csv',
        ]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Catálogo
     */
    public function shortcode_catalogo($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'nivel' => '',
            'modalidad' => '',
            'limite' => 12,
            'columnas' => 3,
        ], $atts);

        wp_enqueue_style('cursos-frontend', $this->get_module_url() . 'assets/css/cursos-frontend.css', [], '1.0.0');
        wp_enqueue_script('cursos-frontend', $this->get_module_url() . 'assets/js/cursos-frontend.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cursos-frontend', 'cursosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cursos_nonce'),
            'rest_url' => rest_url('flavor/v1/cursos'),
        ]);

        ob_start();
        include $this->get_module_path() . 'templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $curso_id = $atts['id'] ?: (isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0);

        if (!$curso_id) {
            return '<p class="cursos-error">Curso no especificado</p>';
        }

        $resultado = $this->action_detalle_curso(['curso_id' => $curso_id]);

        if (!$resultado['success']) {
            return '<p class="cursos-error">' . esc_html($resultado['error']) . '</p>';
        }

        wp_enqueue_style('cursos-frontend', $this->get_module_url() . 'assets/css/cursos-frontend.css', [], '1.0.0');
        wp_enqueue_script('cursos-frontend', $this->get_module_url() . 'assets/js/cursos-frontend.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cursos-frontend', 'cursosData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cursos_nonce'),
        ]);

        $curso = $resultado['curso'];
        $lecciones = $resultado['lecciones'];
        $inscripcion = $resultado['inscripcion'];

        ob_start();
        include $this->get_module_path() . 'templates/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis cursos
     */
    public function shortcode_mis_cursos($atts) {
        if (!is_user_logged_in()) {
            return '<p class="cursos-login-required">Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para ver tus cursos.</p>';
        }

        wp_enqueue_style('cursos-frontend', $this->get_module_url() . 'assets/css/cursos-frontend.css', [], '1.0.0');
        wp_enqueue_script('cursos-frontend', $this->get_module_url() . 'assets/js/cursos-frontend.js', ['jquery'], '1.0.0', true);

        $resultado = $this->action_mis_cursos([]);

        ob_start();
        include $this->get_module_path() . 'templates/mis-cursos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Aula virtual
     */
    public function shortcode_aula($atts) {
        if (!is_user_logged_in()) {
            return '<p class="cursos-login-required">Debes <a href="' . wp_login_url(get_permalink()) . '">iniciar sesión</a> para acceder al aula.</p>';
        }

        $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
        $leccion_id = isset($_GET['leccion_id']) ? intval($_GET['leccion_id']) : 0;

        if (!$curso_id) {
            return '<p class="cursos-error">Curso no especificado</p>';
        }

        wp_enqueue_style('cursos-aula', $this->get_module_url() . 'assets/css/cursos-aula.css', [], '1.0.0');
        wp_enqueue_script('cursos-aula', $this->get_module_url() . 'assets/js/cursos-aula.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cursos-aula', 'cursosAulaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cursos_nonce'),
            'curso_id' => $curso_id,
            'leccion_id' => $leccion_id,
        ]);

        ob_start();
        include $this->get_module_path() . 'templates/aula.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Panel instructor
     */
    public function shortcode_instructor($atts) {
        if (!is_user_logged_in()) {
            return '<p class="cursos-login-required">Debes iniciar sesión para acceder.</p>';
        }

        wp_enqueue_style('cursos-instructor', $this->get_module_url() . 'assets/css/cursos-instructor.css', [], '1.0.0');
        wp_enqueue_script('cursos-instructor', $this->get_module_url() . 'assets/js/cursos-instructor.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cursos-instructor', 'cursosInstructorData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cursos_admin_nonce'),
        ]);

        ob_start();
        include $this->get_module_path() . 'templates/instructor.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Verificar certificado
     */
    public function shortcode_certificado($atts) {
        $codigo = isset($_GET['codigo']) ? sanitize_text_field($_GET['codigo']) : '';

        if (empty($codigo)) {
            ob_start();
            include $this->get_module_path() . 'templates/verificar-certificado.php';
            return ob_get_clean();
        }

        global $wpdb;
        $tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $certificado = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cu.titulo as curso_titulo, cu.duracion_horas
             FROM $tabla_certificados c
             INNER JOIN $tabla_cursos cu ON c.curso_id = cu.id
             WHERE c.codigo_verificacion = %s AND c.estado = 'emitido'",
            $codigo
        ));

        ob_start();
        include $this->get_module_path() . 'templates/certificado.php';
        return ob_get_clean();
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Formatear curso para lista
     */
    private function formatear_curso_lista($curso) {
        $instructor = get_userdata($curso->instructor_id);
        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'slug' => $curso->slug,
            'descripcion' => wp_trim_words($curso->descripcion, 30),
            'instructor' => $instructor ? $instructor->display_name : 'Instructor',
            'categoria' => $curso->categoria,
            'nivel' => $curso->nivel,
            'modalidad' => $curso->modalidad,
            'duracion_horas' => $curso->duracion_horas,
            'precio' => floatval($curso->precio),
            'es_gratuito' => (bool)$curso->es_gratuito,
            'alumnos' => $curso->alumnos_inscritos,
            'valoracion' => floatval($curso->valoracion_media),
            'imagen' => $curso->imagen_portada,
            'destacado' => (bool)$curso->destacado,
            'fecha_inicio' => $curso->fecha_inicio,
        ];
    }

    /**
     * Enviar notificación de inscripción
     */
    private function enviar_notificacion_inscripcion($usuario_id, $curso) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) return;

        $asunto = sprintf(__('¡Bienvenido al curso "%s"!', 'flavor-chat-ia'), $curso->titulo);
        $mensaje = sprintf(
            __('Hola %s,

Te has inscrito correctamente en el curso "%s".

Puedes acceder al contenido del curso desde tu panel de usuario.

¡Mucho éxito en tu aprendizaje!', 'flavor-chat-ia'),
            $usuario->display_name,
            $curso->titulo
        );

        wp_mail($usuario->user_email, $asunto, $mensaje);
    }

    /**
     * Enviar recordatorios (WP Cron)
     */
    public function enviar_recordatorios() {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        // Recordar a alumnos inactivos hace 7 días
        $inactivos = $wpdb->get_results(
            "SELECT i.*, c.titulo, u.user_email, u.display_name
             FROM $tabla_inscripciones i
             INNER JOIN $tabla_cursos c ON i.curso_id = c.id
             INNER JOIN {$wpdb->users} u ON i.alumno_id = u.ID
             WHERE i.estado = 'activa'
             AND i.progreso_porcentaje < 100
             AND (i.fecha_ultima_actividad IS NULL OR i.fecha_ultima_actividad < DATE_SUB(NOW(), INTERVAL 7 DAY))
             LIMIT 50"
        );

        foreach ($inactivos as $inscripcion) {
            $asunto = sprintf(__('¿Continúas con "%s"?', 'flavor-chat-ia'), $inscripcion->titulo);
            $mensaje = sprintf(
                __('Hola %s,

Hace tiempo que no te vemos por el curso "%s".

Tu progreso actual es del %d%%. ¡Ánimo, puedes completarlo!

Accede ahora y continúa aprendiendo.', 'flavor-chat-ia'),
                $inscripcion->display_name,
                $inscripcion->titulo,
                $inscripcion->progreso_porcentaje
            );

            wp_mail($inscripcion->user_email, $asunto, $mensaje);
        }
    }

    /**
     * Obtener URL del módulo
     */
    private function get_module_url() {
        return plugin_dir_url(__FILE__);
    }

    /**
     * Obtener path del módulo
     */
    private function get_module_path() {
        return plugin_dir_path(__FILE__);
    }

    // =========================================================================
    // WEB COMPONENTS
    // =========================================================================

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_cursos' => [
                'label' => __('Hero Cursos', 'flavor-chat-ia'),
                'description' => __('Sección hero para plataforma de cursos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Aprende con Tu Comunidad', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Cursos impartidos por vecinos expertos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'label' => __('Mostrar buscador', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'label' => __('Mostrar estadísticas', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'cursos/hero',
            ],
            'cursos_grid' => [
                'label' => __('Grid de Cursos', 'flavor-chat-ia'),
                'description' => __('Catálogo de cursos disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Cursos Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Cantidad', 'flavor-chat-ia'), 'default' => 9],
                    'filtro_categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia'), 'default' => ''],
                    'filtro_nivel' => ['type' => 'select', 'label' => __('Nivel', 'flavor-chat-ia'), 'options' => ['', 'principiante', 'intermedio', 'avanzado'], 'default' => ''],
                    'mostrar_filtros' => ['type' => 'toggle', 'label' => __('Mostrar filtros', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'cursos/grid',
            ],
            'categorias_cursos' => [
                'label' => __('Categorías de Cursos', 'flavor-chat-ia'),
                'description' => __('Navegación por categorías', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Explora por Categoría', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['tarjetas', 'iconos', 'lista'], 'default' => 'tarjetas'],
                    'mostrar_contador' => ['type' => 'toggle', 'label' => __('Mostrar contador', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'cursos/categorias',
            ],
            'cta_instructor' => [
                'label' => __('CTA Instructor', 'flavor-chat-ia'),
                'description' => __('Llamada a la acción para instructores', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-welcome-learn-more',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Comparte tu Conocimiento', 'flavor-chat-ia')],
                    'texto' => ['type' => 'textarea', 'label' => __('Texto', 'flavor-chat-ia'), 'default' => __('Conviértete en instructor y enseña a tu comunidad', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'label' => __('Texto botón', 'flavor-chat-ia'), 'default' => __('Crear Curso', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'label' => __('URL botón', 'flavor-chat-ia'), 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'label' => __('Color de fondo', 'flavor-chat-ia'), 'default' => '#10b981'],
                ],
                'template' => 'cursos/cta-instructor',
            ],
            'curso_destacado' => [
                'label' => __('Curso Destacado', 'flavor-chat-ia'),
                'description' => __('Mostrar un curso destacado', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-star-filled',
                'fields' => [
                    'curso_id' => ['type' => 'number', 'label' => __('ID del curso', 'flavor-chat-ia'), 'default' => 0],
                    'mostrar_video' => ['type' => 'toggle', 'label' => __('Mostrar video', 'flavor-chat-ia'), 'default' => true],
                    'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['horizontal', 'vertical'], 'default' => 'horizontal'],
                ],
                'template' => 'cursos/destacado',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'cursos_catalogo',
                'description' => 'Ver catálogo de cursos disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Filtrar por categoría'],
                        'nivel' => ['type' => 'string', 'description' => 'Filtrar por nivel'],
                        'busqueda' => ['type' => 'string', 'description' => 'Buscar por texto'],
                    ],
                ],
            ],
            [
                'name' => 'cursos_detalle',
                'description' => 'Ver detalles de un curso',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'curso_id' => ['type' => 'integer', 'description' => 'ID del curso'],
                    ],
                    'required' => ['curso_id'],
                ],
            ],
            [
                'name' => 'cursos_inscribirse',
                'description' => 'Inscribirse en un curso',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'curso_id' => ['type' => 'integer', 'description' => 'ID del curso'],
                    ],
                    'required' => ['curso_id'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Plataforma de Cursos Comunitarios**

Aprende nuevas habilidades o comparte tu conocimiento con la comunidad.

**Categorías disponibles:**
- Tecnología y programación
- Idiomas
- Cocina y gastronomía
- Artesanía y manualidades
- Jardinería urbana
- Reparaciones domésticas
- Música y arte
- Salud y bienestar
- Finanzas personales

**Modalidades:**
- Online: Aprende a tu ritmo con videos y materiales
- Presencial: Clases en espacios comunitarios
- Mixto: Combina ambas modalidades

**Niveles:**
- Principiante: Sin conocimientos previos
- Intermedio: Requiere conocimientos básicos
- Avanzado: Para expertos que quieren profundizar

**Ventajas:**
- Cursos impartidos por vecinos expertos
- Precios accesibles o gratuitos
- Certificados de finalización verificables
- Comunidad de apoyo
- Aprende a tu ritmo
- Seguimiento de progreso

**Cómo funciona:**
1. Explora el catálogo de cursos
2. Inscríbete (gratis o de pago)
3. Accede al contenido desde tu panel
4. Completa las lecciones
5. Obtén tu certificado

**Conviértete en instructor:**
1. Propón tu curso
2. Crea el contenido (videos, textos, ejercicios)
3. Establece fechas, horarios y precio
4. Comparte tu conocimiento
5. Gana reconocimiento y compensación
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Obtengo un certificado?',
                'respuesta' => 'Sí, al completar el curso y todas las lecciones obligatorias recibes un certificado digital con código de verificación único.',
            ],
            [
                'pregunta' => '¿Puedo enseñar un curso?',
                'respuesta' => 'Sí, cualquier vecino con conocimientos puede proponer un curso. Envía tu propuesta y será revisada antes de publicarse.',
            ],
            [
                'pregunta' => '¿Qué pasa si no puedo asistir a todas las clases?',
                'respuesta' => 'Los cursos online quedan grabados y puedes verlos cuando quieras. En presenciales, consulta con el instructor las opciones de recuperación.',
            ],
            [
                'pregunta' => '¿Puedo cancelar mi inscripción?',
                'respuesta' => 'En cursos gratuitos puedes abandonar cuando quieras. En cursos de pago, consulta la política de reembolsos de cada curso.',
            ],
            [
                'pregunta' => '¿Los certificados son válidos?',
                'respuesta' => 'Los certificados son emitidos por la comunidad y pueden verificarse con el código único. No son títulos oficiales pero acreditan tu formación.',
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
            Flavor_Page_Creator::refresh_module_pages('cursos');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('cursos');
        if (!$pagina && !get_option('flavor_cursos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['cursos']);
            update_option('flavor_cursos_pages_created', 1, false);
        }
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
                'title' => __('Cursos', 'flavor-chat-ia'),
                'slug' => 'cursos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Cursos y Formación', 'flavor-chat-ia'),
                    'subtitle' => __('Programas educativos para todos los niveles', 'flavor-chat-ia'),
                    'background' => 'gradient',
                    'module' => 'cursos',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="cursos" action="cursos_disponibles" columnas="3" limite="12"]',
                ]),
                'parent' => 0,
            ],

            // Categorías
            [
                'title' => __('Categorías', 'flavor-chat-ia'),
                'slug' => 'categorias',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Categorías de Cursos', 'flavor-chat-ia'),
                    'subtitle' => __('Explora cursos por categoría', 'flavor-chat-ia'),
                    'module' => 'cursos',
                    'current' => 'categorias',
                    'content_after' => '[flavor_module_listing module="cursos" action="categorias"]',
                ]),
                'parent' => 'cursos',
            ],

            // Mis cursos
            [
                'title' => __('Mis Cursos', 'flavor-chat-ia'),
                'slug' => 'mis-cursos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Cursos', 'flavor-chat-ia'),
                    'subtitle' => __('Cursos en los que estás matriculado', 'flavor-chat-ia'),
                    'module' => 'cursos',
                    'current' => 'mis_cursos',
                    'content_after' => '[flavor_module_listing module="cursos" action="mis_cursos" user_specific="yes"]',
                ]),
                'parent' => 'cursos',
            ],

            // Crear curso
            [
                'title' => __('Crear Curso', 'flavor-chat-ia'),
                'slug' => 'crear',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Crear un Curso', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte tu conocimiento como instructor', 'flavor-chat-ia'),
                    'module' => 'cursos',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="cursos" action="crear_curso"]',
                ]),
                'parent' => 'cursos',
            ],

            // Matricularse
            [
                'title' => __('Matricularse', 'flavor-chat-ia'),
                'slug' => 'matricularse',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Matricularse en Curso', 'flavor-chat-ia'),
                    'subtitle' => __('Completa tu inscripción', 'flavor-chat-ia'),
                    'module' => 'cursos',
                    'current' => 'matricularse',
                    'content_after' => '[flavor_module_form module="cursos" action="matricularse"]',
                ]),
                'parent' => 'cursos',
            ],

            // Instructores
            [
                'title' => __('Instructores', 'flavor-chat-ia'),
                'slug' => 'instructores',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Nuestros Instructores', 'flavor-chat-ia'),
                    'subtitle' => __('Conoce a los profesionales que imparten los cursos', 'flavor-chat-ia'),
                    'module' => 'cursos',
                    'current' => 'instructores',
                    'content_after' => '[flavor_module_listing module="cursos" action="instructores" columnas="4"]',
                ]),
                'parent' => 'cursos',
            ],
        ];
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas del sidebar (primer parámetro null)
        add_submenu_page(
            null,
            __('Cursos - Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'cursos',
            [$this, 'render_pagina_dashboard']
        );

        add_submenu_page(
            null,
            __('Cursos', 'flavor-chat-ia'),
            __('Cursos', 'flavor-chat-ia'),
            $capability,
            'cursos-listado',
            [$this, 'render_pagina_cursos']
        );

        add_submenu_page(
            null,
            __('Alumnos', 'flavor-chat-ia'),
            __('Alumnos', 'flavor-chat-ia'),
            $capability,
            'cursos-alumnos',
            [$this, 'render_pagina_alumnos']
        );

        add_submenu_page(
            null,
            __('Instructores', 'flavor-chat-ia'),
            __('Instructores', 'flavor-chat-ia'),
            $capability,
            'cursos-instructores',
            [$this, 'render_pagina_instructores']
        );

        add_submenu_page(
            null,
            __('Matrículas', 'flavor-chat-ia'),
            __('Matrículas', 'flavor-chat-ia'),
            $capability,
            'cursos-matriculas',
            [$this, 'render_pagina_matriculas']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Cursos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de cursos
     */
    public function render_pagina_cursos() {
        $views_path = dirname(__FILE__) . '/views/cursos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Cursos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de alumnos
     */
    public function render_pagina_alumnos() {
        $views_path = dirname(__FILE__) . '/views/alumnos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Alumnos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de instructores
     */
    public function render_pagina_instructores() {
        $views_path = dirname(__FILE__) . '/views/instructores.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Instructores', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de matrículas
     */
    public function render_pagina_matriculas() {
        $views_path = dirname(__FILE__) . '/views/matriculas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Matrículas', 'flavor-chat-ia') . '</h1></div>';
        }
    }
}
