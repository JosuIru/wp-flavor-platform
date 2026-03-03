<?php
/**
 * Módulo de Biblioteca Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Biblioteca - Sistema de préstamo de libros entre vecinos
 */
class Flavor_Chat_Biblioteca_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Provider;

    /**
     * {@inheritdoc}
     */
    public function get_integration_content_type() {
        return [
            'id' => 'biblioteca',
            'label' => __('Biblioteca', 'flavor-chat-ia'),
            'icon' => 'dashicons-book',
            'table' => 'flavor_biblioteca_libros',
        ];
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'biblioteca';
        $this->name = __('Biblioteca Comunitaria', 'flavor-chat-ia');
        $this->description = __('Sistema de préstamo e intercambio de libros entre vecinos de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        return Flavor_Chat_Helpers::tabla_existe($tabla_libros);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Biblioteca no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_donaciones' => true,
            'permite_intercambios' => true,
            'permite_prestamos' => true,
            'duracion_prestamo_dias' => 30,
            'renovaciones_maximas' => 2,
            'permite_reservas' => true,
            'sistema_puntos' => true,
            'puntos_por_prestamo' => 1,
            'puntos_por_devolucion' => 2,
            'requiere_verificacion_isbn' => false,
            'notificar_vencimientos' => true,
            'dias_antes_notificar' => 3,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar como proveedor de integración
        $this->register_as_integration_provider();

        // Registrar páginas de admin en el panel unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_ajax_biblioteca_solicitar_prestamo', [$this, 'ajax_solicitar_prestamo']);
        add_action('wp_ajax_biblioteca_devolver_libro', [$this, 'ajax_devolver_libro']);
        add_action('wp_ajax_biblioteca_renovar_prestamo', [$this, 'ajax_renovar_prestamo']);
        add_action('wp_ajax_biblioteca_reservar_libro', [$this, 'ajax_reservar_libro']);
        add_action('wp_ajax_biblioteca_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_biblioteca_valorar_libro', [$this, 'ajax_valorar_libro']);
        add_action('wp_ajax_biblioteca_agregar_libro', [$this, 'ajax_agregar_libro']);
        add_action('wp_ajax_biblioteca_editar_libro', [$this, 'ajax_editar_libro']);
        add_action('wp_ajax_biblioteca_eliminar_libro', [$this, 'ajax_eliminar_libro']);
        add_action('wp_ajax_biblioteca_aprobar_prestamo', [$this, 'ajax_aprobar_prestamo']);
        add_action('wp_ajax_biblioteca_rechazar_prestamo', [$this, 'ajax_rechazar_prestamo']);
        add_action('wp_ajax_biblioteca_buscar_isbn', [$this, 'ajax_buscar_isbn']);

        // WP Cron para recordatorios de devolución
        add_action('flavor_biblioteca_recordatorios', [$this, 'enviar_recordatorios_devolucion']);
        if (!wp_next_scheduled('flavor_biblioteca_recordatorios')) {
            wp_schedule_event(time(), 'daily', 'flavor_biblioteca_recordatorios');
        }

        // WP Cron para procesar reservas expiradas
        add_action('flavor_biblioteca_procesar_reservas', [$this, 'procesar_reservas_expiradas']);
        if (!wp_next_scheduled('flavor_biblioteca_procesar_reservas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_biblioteca_procesar_reservas');
        }

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();

        // Cargar Dashboard Tab
        $this->cargar_dashboard_tab();

        // Enqueue assets frontend
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-biblioteca-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Biblioteca_Frontend_Controller::get_instance();
        }
    }

    /**
     * Carga el Dashboard Tab para el panel de usuario
     */
    private function cargar_dashboard_tab() {
        $archivo_dashboard_tab = dirname(__FILE__) . '/class-biblioteca-dashboard-tab.php';
        if (file_exists($archivo_dashboard_tab)) {
            require_once $archivo_dashboard_tab;
            Flavor_Biblioteca_Dashboard_Tab::get_instance();
        }
    }

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('biblioteca_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('biblioteca_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('biblioteca_mis_libros', [$this, 'shortcode_mis_libros']);
        add_shortcode('biblioteca_mis_prestamos', [$this, 'shortcode_mis_prestamos']);
        add_shortcode('biblioteca_agregar', [$this, 'shortcode_agregar']);
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'biblioteca_catalogo',
            'biblioteca_detalle',
            'biblioteca_mis_libros',
            'biblioteca_mis_prestamos',
            'biblioteca_agregar',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets frontend solo cuando se usan shortcodes
     */
    public function maybe_enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $assets_url = plugin_dir_url(__FILE__) . 'assets/';
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'biblioteca-frontend',
            $assets_url . 'css/biblioteca-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'biblioteca-frontend',
            $assets_url . 'js/biblioteca-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_frontend'),
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'strings' => [
                'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'flavor-chat-ia'),
                'confirmar_prestamo' => __('¿Deseas solicitar el préstamo de este libro?', 'flavor-chat-ia'),
                'prestamo_solicitado' => __('Tu solicitud de préstamo ha sido enviada.', 'flavor-chat-ia'),
                'libro_devuelto' => __('El libro ha sido devuelto correctamente.', 'flavor-chat-ia'),
                'login_requerido' => __('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'),
            ]
        ]);
    }

    /**
     * Registra las rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/biblioteca/libros', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_libros'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/libros/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_detalle_libro'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/libros', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/libros/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_actualizar_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/libros/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'api_eliminar_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/mis-libros', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_libros'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/prestamos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_prestamos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/prestamos/solicitar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_solicitar_prestamo'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/prestamos/(?P<id>\d+)/devolver', [
            'methods' => 'POST',
            'callback' => [$this, 'api_devolver_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/prestamos/(?P<id>\d+)/renovar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_renovar_prestamo'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/reservas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_reservar_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/reservas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'api_cancelar_reserva'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/resenas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_valorar_libro'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/generos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_generos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/recomendaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_recomendaciones'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/isbn/(?P<isbn>[0-9X-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_buscar_isbn'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route('flavor/v1', '/biblioteca/solicitudes-pendientes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_solicitudes_pendientes'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    /**
     * Verificar usuario logueado
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            $this->create_tables();
        }
    }

    /**
     * Migra las tablas si es necesario
     */
    public function maybe_migrate_tables() {
        $version_actual = get_option('flavor_biblioteca_db_version', '1.0.0');

        if (version_compare($version_actual, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('flavor_biblioteca_db_version', '1.1.0');
        }

        if (version_compare($version_actual, '1.2.0', '<')) {
            $this->migrate_to_1_2_0();
            update_option('flavor_biblioteca_db_version', '1.2.0');
        }
    }

    /**
     * Migración a versión 1.1.0 - Agregar columnas faltantes
     */
    private function migrate_to_1_1_0() {
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        // Verificar y agregar portada_url si no existe
        $columna_existe = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM $tabla_libros LIKE %s",
                'portada_url'
            )
        );

        if (empty($columna_existe)) {
            $wpdb->query("ALTER TABLE $tabla_libros ADD COLUMN portada_url varchar(500) DEFAULT NULL AFTER descripcion");
        }
    }

    /**
     * Migración a versión 1.2.0 - Agregar columnas en prestamos y reservas
     */
    private function migrate_to_1_2_0() {
        global $wpdb;

        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        // Columnas para prestamos
        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $columnas_prestamos = [
                'prestatario_id' => "bigint(20) unsigned NOT NULL AFTER libro_id",
                'prestamista_id' => "bigint(20) unsigned NOT NULL AFTER prestatario_id",
                'fecha_solicitud' => "datetime DEFAULT CURRENT_TIMESTAMP AFTER prestamista_id",
            ];

            foreach ($columnas_prestamos as $columna => $definicion) {
                $existe = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $tabla_prestamos LIKE %s", $columna));
                if (empty($existe)) {
                    $wpdb->query("ALTER TABLE $tabla_prestamos ADD COLUMN $columna $definicion");
                    if ($columna === 'prestatario_id' || $columna === 'prestamista_id') {
                        $wpdb->query("ALTER TABLE $tabla_prestamos ADD KEY $columna ($columna)");
                    }
                }
            }
        }

        // Columnas para reservas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
            $existe = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $tabla_reservas LIKE %s", 'fecha_solicitud'));
            if (empty($existe)) {
                $wpdb->query("ALTER TABLE $tabla_reservas ADD COLUMN fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP AFTER usuario_id");
            }
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        $sql_libros = "CREATE TABLE IF NOT EXISTS $tabla_libros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propietario_id bigint(20) unsigned NOT NULL,
            isbn varchar(20) DEFAULT NULL,
            titulo varchar(500) NOT NULL,
            autor varchar(255) NOT NULL,
            editorial varchar(255) DEFAULT NULL,
            ano_publicacion int(11) DEFAULT NULL,
            idioma varchar(50) DEFAULT 'Español',
            genero varchar(100) DEFAULT NULL,
            num_paginas int(11) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            portada_url varchar(500) DEFAULT NULL,
            estado_fisico enum('excelente','bueno','aceptable','desgastado') DEFAULT 'bueno',
            disponibilidad enum('disponible','prestado','reservado','no_disponible') DEFAULT 'disponible',
            tipo enum('donado','prestamo','intercambio') DEFAULT 'prestamo',
            ubicacion varchar(255) DEFAULT NULL COMMENT 'Casa del propietario o punto recogida',
            valoracion_media decimal(3,2) DEFAULT 0,
            veces_prestado int(11) DEFAULT 0,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propietario_id (propietario_id),
            KEY isbn (isbn),
            KEY disponibilidad (disponibilidad),
            KEY genero (genero),
            FULLTEXT KEY busqueda (titulo, autor, descripcion)
        ) $charset_collate;";

        $sql_prestamos = "CREATE TABLE IF NOT EXISTS $tabla_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            prestamista_id bigint(20) unsigned NOT NULL,
            prestatario_id bigint(20) unsigned NOT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_prestamo datetime DEFAULT NULL,
            fecha_devolucion_prevista datetime DEFAULT NULL,
            fecha_devolucion_real datetime DEFAULT NULL,
            renovaciones int(11) DEFAULT 0,
            estado enum('pendiente','activo','devuelto','retrasado','perdido','rechazado') DEFAULT 'pendiente',
            notas_prestamista text DEFAULT NULL,
            notas_prestatario text DEFAULT NULL,
            valoracion_libro int(11) DEFAULT NULL,
            valoracion_prestatario int(11) DEFAULT NULL,
            punto_entrega varchar(255) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY libro_id (libro_id),
            KEY prestatario_id (prestatario_id),
            KEY prestamista_id (prestamista_id),
            KEY estado (estado),
            KEY fecha_devolucion_prevista (fecha_devolucion_prevista)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime NOT NULL,
            estado enum('pendiente','confirmada','cancelada','expirada','convertida') DEFAULT 'pendiente',
            notificado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY libro_id (libro_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_resenas = "CREATE TABLE IF NOT EXISTS $tabla_resenas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            valoracion int(11) NOT NULL,
            resena text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY libro_usuario (libro_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_libros);
        dbDelta($sql_prestamos);
        dbDelta($sql_reservas);
        dbDelta($sql_resenas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_libros' => [
                'description' => 'Buscar libros disponibles',
                'params' => ['query', 'genero', 'autor'],
            ],
            'detalle_libro' => [
                'description' => 'Ver detalles del libro',
                'params' => ['libro_id'],
            ],
            'solicitar_prestamo' => [
                'description' => 'Solicitar préstamo',
                'params' => ['libro_id'],
            ],
            'mis_libros' => [
                'description' => 'Mis libros en la biblioteca',
                'params' => [],
            ],
            'mis_prestamos' => [
                'description' => 'Libros que tengo prestados',
                'params' => [],
            ],
            'agregar_libro' => [
                'description' => 'Agregar libro a la biblioteca',
                'params' => ['titulo', 'autor', 'isbn', 'tipo'],
            ],
            'devolver_libro' => [
                'description' => 'Marcar libro como devuelto',
                'params' => ['prestamo_id'],
            ],
            'renovar_prestamo' => [
                'description' => 'Renovar préstamo',
                'params' => ['prestamo_id'],
            ],
            'reservar_libro' => [
                'description' => 'Reservar libro prestado',
                'params' => ['libro_id'],
            ],
            'valorar_libro' => [
                'description' => 'Valorar y reseñar libro',
                'params' => ['libro_id', 'valoracion', 'resena'],
            ],
            'recomendaciones' => [
                'description' => 'Libros recomendados',
                'params' => [],
            ],
            'estadisticas_biblioteca' => [
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
            'listar' => 'buscar_libros',
            'listado' => 'buscar_libros',
            'buscar' => 'buscar_libros',
            'explorar' => 'buscar_libros',
            'detalle' => 'detalle_libro',
            'ver' => 'detalle_libro',
            'crear' => 'agregar_libro',
            'nuevo' => 'agregar_libro',
            'mis_items' => 'mis_libros',
            'mis-libros' => 'mis_libros',
            'mis-prestamos' => 'mis_prestamos',
            'prestamo' => 'solicitar_prestamo',
            'reservar' => 'reservar_libro',
            'devolver' => 'devolver_libro',
            'renovar' => 'renovar_prestamo',
            'valorar' => 'valorar_libro',
            'recomendaciones' => 'recomendaciones',
            'stats' => 'estadisticas_biblioteca',
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
     * Acción: Buscar libros
     */
    private function action_buscar_libros($params) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $where = ["disponibilidad IN ('disponible', 'prestado', 'reservado')"];
        $prepare_values = [];

        if (!empty($params['query'])) {
            $busqueda = sanitize_text_field($params['query']);
            $where[] = "(titulo LIKE %s OR autor LIKE %s OR descripcion LIKE %s)";
            $like_busqueda = '%' . $wpdb->esc_like($busqueda) . '%';
            $prepare_values[] = $like_busqueda;
            $prepare_values[] = $like_busqueda;
            $prepare_values[] = $like_busqueda;
        }

        if (!empty($params['genero'])) {
            $where[] = 'genero = %s';
            $prepare_values[] = sanitize_text_field($params['genero']);
        }

        if (!empty($params['autor'])) {
            $where[] = 'autor LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like(sanitize_text_field($params['autor'])) . '%';
        }

        $sql = "SELECT * FROM $tabla_libros WHERE " . implode(' AND ', $where) . " ORDER BY fecha_agregado DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $libros = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $libros = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'libros' => array_map([$this, 'formatear_libro'], $libros),
        ];
    }

    /**
     * Acción: Detalle de libro
     */
    private function action_detalle_libro($params) {
        if (empty($params['libro_id'])) {
            return ['success' => false, 'error' => 'ID de libro requerido'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            intval($params['libro_id'])
        ));

        if (!$libro) {
            return ['success' => false, 'error' => 'Libro no encontrado'];
        }

        // Obtener reseñas
        $resenas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as usuario_nombre
             FROM $tabla_resenas r
             LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.libro_id = %d
             ORDER BY r.fecha_creacion DESC
             LIMIT 10",
            $libro->id
        ));

        $libro_formateado = $this->formatear_libro($libro);
        $libro_formateado['descripcion_completa'] = $libro->descripcion;
        $libro_formateado['resenas'] = array_map(function($r) {
            return [
                'id' => $r->id,
                'usuario' => $r->usuario_nombre,
                'valoracion' => intval($r->valoracion),
                'resena' => $r->resena,
                'fecha' => date_i18n(get_option('date_format'), strtotime($r->fecha_creacion)),
            ];
        }, $resenas);

        return [
            'success' => true,
            'libro' => $libro_formateado,
        ];
    }

    /**
     * Acción: Solicitar préstamo
     */
    private function action_solicitar_prestamo($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['libro_id'])) {
            return ['success' => false, 'error' => 'ID de libro requerido'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        $usuario_id = get_current_user_id();
        $libro_id = intval($params['libro_id']);

        // Verificar libro
        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $libro_id
        ));

        if (!$libro) {
            return ['success' => false, 'error' => 'Libro no encontrado'];
        }

        if ($libro->propietario_id == $usuario_id) {
            return ['success' => false, 'error' => 'No puedes solicitar tu propio libro'];
        }

        if ($libro->disponibilidad !== 'disponible') {
            return ['success' => false, 'error' => 'El libro no está disponible actualmente'];
        }

        // Verificar si ya tiene una solicitud pendiente
        $solicitud_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_prestamos
             WHERE libro_id = %d AND prestatario_id = %d AND estado IN ('pendiente', 'activo')",
            $libro_id,
            $usuario_id
        ));

        if ($solicitud_existente) {
            return ['success' => false, 'error' => 'Ya tienes una solicitud activa para este libro'];
        }

        // Crear solicitud de préstamo
        $resultado = $wpdb->insert($tabla_prestamos, [
            'libro_id' => $libro_id,
            'prestamista_id' => $libro->propietario_id,
            'prestatario_id' => $usuario_id,
            'estado' => 'pendiente',
            'notas_prestatario' => isset($params['notas']) ? sanitize_textarea_field($params['notas']) : null,
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al crear la solicitud'];
        }

        $prestamo_id = $wpdb->insert_id;

        // Notificar al propietario
        $this->notificar_propietario_solicitud($libro, $usuario_id, $prestamo_id);

        return [
            'success' => true,
            'mensaje' => 'Solicitud de préstamo enviada. El propietario te contactará pronto.',
            'prestamo_id' => $prestamo_id,
        ];
    }

    /**
     * Acción: Mis libros
     */
    private function action_mis_libros($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $usuario_id = get_current_user_id();

        $libros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE propietario_id = %d ORDER BY fecha_agregado DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'libros' => array_map([$this, 'formatear_libro'], $libros),
        ];
    }

    /**
     * Acción: Mis préstamos
     */
    private function action_mis_prestamos($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $usuario_id = get_current_user_id();

        // Libros que tengo prestados (soy prestatario)
        $prestamos_recibidos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor, l.portada_url, l.propietario_id,
                    u.display_name as propietario_nombre
             FROM $tabla_prestamos p
             INNER JOIN $tabla_libros l ON p.libro_id = l.id
             LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
             WHERE p.prestatario_id = %d AND p.estado IN ('pendiente', 'activo', 'retrasado')
             ORDER BY p.fecha_solicitud DESC",
            $usuario_id
        ));

        // Libros que he prestado (soy prestamista)
        $prestamos_realizados = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor, l.portada_url,
                    u.display_name as prestatario_nombre
             FROM $tabla_prestamos p
             INNER JOIN $tabla_libros l ON p.libro_id = l.id
             LEFT JOIN {$wpdb->users} u ON p.prestatario_id = u.ID
             WHERE p.prestamista_id = %d AND p.estado IN ('pendiente', 'activo', 'retrasado')
             ORDER BY p.fecha_solicitud DESC",
            $usuario_id
        ));

        return [
            'success' => true,
            'prestamos_recibidos' => array_map([$this, 'formatear_prestamo'], $prestamos_recibidos),
            'prestamos_realizados' => array_map([$this, 'formatear_prestamo'], $prestamos_realizados),
        ];
    }

    /**
     * Acción: Agregar libro
     */
    private function action_agregar_libro($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['titulo']) || empty($params['autor'])) {
            return ['success' => false, 'error' => 'Título y autor son obligatorios'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $usuario_id = get_current_user_id();

        $datos_libro = [
            'propietario_id' => $usuario_id,
            'titulo' => sanitize_text_field($params['titulo']),
            'autor' => sanitize_text_field($params['autor']),
            'isbn' => isset($params['isbn']) ? sanitize_text_field($params['isbn']) : null,
            'editorial' => isset($params['editorial']) ? sanitize_text_field($params['editorial']) : null,
            'ano_publicacion' => isset($params['ano']) ? intval($params['ano']) : null,
            'idioma' => isset($params['idioma']) ? sanitize_text_field($params['idioma']) : 'Español',
            'genero' => isset($params['genero']) ? sanitize_text_field($params['genero']) : null,
            'num_paginas' => isset($params['paginas']) ? intval($params['paginas']) : null,
            'descripcion' => isset($params['descripcion']) ? sanitize_textarea_field($params['descripcion']) : null,
            'portada_url' => isset($params['portada_url']) ? esc_url_raw($params['portada_url']) : null,
            'estado_fisico' => isset($params['estado_fisico']) ? sanitize_text_field($params['estado_fisico']) : 'bueno',
            'tipo' => isset($params['tipo']) ? sanitize_text_field($params['tipo']) : 'prestamo',
            'ubicacion' => isset($params['ubicacion']) ? sanitize_text_field($params['ubicacion']) : null,
            'disponibilidad' => 'disponible',
            'fecha_agregado' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($tabla_libros, $datos_libro);

        if (!$resultado) {
            return ['success' => false, 'error' => 'Error al agregar el libro'];
        }

        // Dar puntos al usuario por agregar libro
        $settings = $this->get_settings();
        if (!empty($settings['sistema_puntos'])) {
            do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $settings['puntos_por_prestamo'], 'biblioteca_agregar_libro');
        }

        return [
            'success' => true,
            'mensaje' => 'Libro agregado correctamente a la biblioteca',
            'libro_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Acción: Devolver libro
     */
    private function action_devolver_libro($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['prestamo_id'])) {
            return ['success' => false, 'error' => 'ID de préstamo requerido'];
        }

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $prestamo_id = intval($params['prestamo_id']);
        $usuario_id = get_current_user_id();

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d",
            $prestamo_id
        ));

        if (!$prestamo) {
            return ['success' => false, 'error' => 'Préstamo no encontrado'];
        }

        // Solo el prestamista o prestatario pueden marcar como devuelto
        if ($prestamo->prestamista_id != $usuario_id && $prestamo->prestatario_id != $usuario_id) {
            return ['success' => false, 'error' => 'No tienes permiso para esta acción'];
        }

        // Actualizar préstamo
        $wpdb->update($tabla_prestamos, [
            'estado' => 'devuelto',
            'fecha_devolucion_real' => current_time('mysql'),
        ], ['id' => $prestamo_id]);

        // Verificar si hay reservas pendientes
        $reserva_pendiente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas
             WHERE libro_id = %d AND estado = 'pendiente'
             ORDER BY fecha_solicitud ASC LIMIT 1",
            $prestamo->libro_id
        ));

        if ($reserva_pendiente) {
            // Actualizar libro a reservado
            $wpdb->update($tabla_libros, ['disponibilidad' => 'reservado'], ['id' => $prestamo->libro_id]);

            // Notificar al usuario que reservó
            $this->notificar_reserva_disponible($reserva_pendiente);

            // Actualizar reserva
            $wpdb->update($tabla_reservas, [
                'estado' => 'confirmada',
                'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+48 hours')),
            ], ['id' => $reserva_pendiente->id]);
        } else {
            // Libro disponible
            $wpdb->update($tabla_libros, ['disponibilidad' => 'disponible'], ['id' => $prestamo->libro_id]);
        }

        // Dar puntos por devolución
        $settings = $this->get_settings();
        if (!empty($settings['sistema_puntos'])) {
            do_action('flavor_gamificacion_agregar_puntos', $prestamo->prestatario_id, $settings['puntos_por_devolucion'], 'biblioteca_devolucion');
        }

        return [
            'success' => true,
            'mensaje' => 'Libro marcado como devuelto correctamente',
        ];
    }

    /**
     * Acción: Renovar préstamo
     */
    private function action_renovar_prestamo($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['prestamo_id'])) {
            return ['success' => false, 'error' => 'ID de préstamo requerido'];
        }

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $prestamo_id = intval($params['prestamo_id']);
        $usuario_id = get_current_user_id();

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d AND prestatario_id = %d AND estado = 'activo'",
            $prestamo_id,
            $usuario_id
        ));

        if (!$prestamo) {
            return ['success' => false, 'error' => 'Préstamo no encontrado o no activo'];
        }

        $settings = $this->get_settings();
        $max_renovaciones = $settings['renovaciones_maximas'] ?? 2;

        if ($prestamo->renovaciones >= $max_renovaciones) {
            return ['success' => false, 'error' => 'Has alcanzado el máximo de renovaciones permitidas'];
        }

        // Verificar si hay reservas
        $hay_reservas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE libro_id = %d AND estado = 'pendiente'",
            $prestamo->libro_id
        ));

        if ($hay_reservas > 0) {
            return ['success' => false, 'error' => 'No puedes renovar porque hay reservas pendientes'];
        }

        // Renovar
        $dias_prestamo = $settings['duracion_prestamo_dias'] ?? 30;
        $nueva_fecha = date('Y-m-d H:i:s', strtotime("+{$dias_prestamo} days"));

        $wpdb->update($tabla_prestamos, [
            'fecha_devolucion_prevista' => $nueva_fecha,
            'renovaciones' => $prestamo->renovaciones + 1,
        ], ['id' => $prestamo_id]);

        return [
            'success' => true,
            'mensaje' => 'Préstamo renovado correctamente',
            'nueva_fecha_devolucion' => date_i18n(get_option('date_format'), strtotime($nueva_fecha)),
        ];
    }

    /**
     * Acción: Reservar libro
     */
    private function action_reservar_libro($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['libro_id'])) {
            return ['success' => false, 'error' => 'ID de libro requerido'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $libro_id = intval($params['libro_id']);
        $usuario_id = get_current_user_id();

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $libro_id
        ));

        if (!$libro) {
            return ['success' => false, 'error' => 'Libro no encontrado'];
        }

        if ($libro->disponibilidad === 'disponible') {
            return ['success' => false, 'error' => 'El libro está disponible, puedes solicitarlo directamente'];
        }

        if ($libro->propietario_id == $usuario_id) {
            return ['success' => false, 'error' => 'No puedes reservar tu propio libro'];
        }

        // Verificar si ya tiene reserva
        $reserva_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reservas
             WHERE libro_id = %d AND usuario_id = %d AND estado = 'pendiente'",
            $libro_id,
            $usuario_id
        ));

        if ($reserva_existente) {
            return ['success' => false, 'error' => 'Ya tienes una reserva para este libro'];
        }

        // Crear reserva
        $wpdb->insert($tabla_reservas, [
            'libro_id' => $libro_id,
            'usuario_id' => $usuario_id,
            'fecha_solicitud' => current_time('mysql'),
            'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'estado' => 'pendiente',
        ]);

        return [
            'success' => true,
            'mensaje' => 'Reserva creada. Te notificaremos cuando el libro esté disponible.',
            'reserva_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Acción: Valorar libro
     */
    private function action_valorar_libro($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        if (empty($params['libro_id']) || empty($params['valoracion'])) {
            return ['success' => false, 'error' => 'Libro y valoración son requeridos'];
        }

        global $wpdb;
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro_id = intval($params['libro_id']);
        $usuario_id = get_current_user_id();
        $valoracion = max(1, min(5, intval($params['valoracion'])));
        $resena = isset($params['resena']) ? sanitize_textarea_field($params['resena']) : null;

        // Insertar o actualizar reseña
        $resena_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_resenas WHERE libro_id = %d AND usuario_id = %d",
            $libro_id,
            $usuario_id
        ));

        if ($resena_existente) {
            $wpdb->update($tabla_resenas, [
                'valoracion' => $valoracion,
                'resena' => $resena,
            ], ['id' => $resena_existente]);
        } else {
            $wpdb->insert($tabla_resenas, [
                'libro_id' => $libro_id,
                'usuario_id' => $usuario_id,
                'valoracion' => $valoracion,
                'resena' => $resena,
                'fecha_creacion' => current_time('mysql'),
            ]);
        }

        // Actualizar valoración media del libro
        $media = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(valoracion) FROM $tabla_resenas WHERE libro_id = %d",
            $libro_id
        ));

        $wpdb->update($tabla_libros, ['valoracion_media' => $media], ['id' => $libro_id]);

        return [
            'success' => true,
            'mensaje' => 'Valoración guardada correctamente',
        ];
    }

    /**
     * Acción: Recomendaciones
     */
    private function action_recomendaciones($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesión'];
        }

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $usuario_id = get_current_user_id();

        // Obtener géneros favoritos del usuario basado en préstamos
        $generos_favoritos = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT l.genero
             FROM $tabla_prestamos p
             INNER JOIN $tabla_libros l ON p.libro_id = l.id
             WHERE p.prestatario_id = %d AND l.genero IS NOT NULL
             ORDER BY p.fecha_solicitud DESC
             LIMIT 5",
            $usuario_id
        ));

        $recomendados = [];

        if (!empty($generos_favoritos)) {
            $placeholders = implode(',', array_fill(0, count($generos_favoritos), '%s'));
            $params_query = array_merge($generos_favoritos, [$usuario_id]);

            $recomendados = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_libros
                 WHERE genero IN ($placeholders)
                 AND disponibilidad = 'disponible'
                 AND propietario_id != %d
                 ORDER BY valoracion_media DESC, veces_prestado DESC
                 LIMIT 10",
                ...$params_query
            ));
        }

        // Si no hay suficientes, agregar los más populares
        if (count($recomendados) < 10) {
            $limite_adicional = 10 - count($recomendados);
            $ids_existentes = array_column($recomendados, 'id');

            $where_extra = '';
            if (!empty($ids_existentes)) {
                $ids_string = implode(',', array_map('intval', $ids_existentes));
                $where_extra = "AND id NOT IN ($ids_string)";
            }

            $populares = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_libros
                 WHERE disponibilidad = 'disponible'
                 AND propietario_id != %d
                 $where_extra
                 ORDER BY valoracion_media DESC, veces_prestado DESC
                 LIMIT %d",
                $usuario_id,
                $limite_adicional
            ));

            $recomendados = array_merge($recomendados, $populares);
        }

        return [
            'success' => true,
            'recomendaciones' => array_map([$this, 'formatear_libro'], $recomendados),
        ];
    }

    /**
     * Acción: Estadísticas biblioteca (admin)
     */
    private function action_estadisticas_biblioteca($params) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        $periodo = isset($params['periodo']) ? sanitize_text_field($params['periodo']) : 'mes';
        $fecha_inicio = $this->calcular_fecha_inicio($periodo);

        $total_libros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros");
        $libros_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE disponibilidad = 'disponible'");
        $total_prestamos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE fecha_solicitud >= %s",
            $fecha_inicio
        ));
        $prestamos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'");
        $lectores_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT prestatario_id) FROM $tabla_prestamos WHERE fecha_solicitud >= %s",
            $fecha_inicio
        ));
        $total_resenas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_resenas");

        // Top géneros
        $generos_populares = $wpdb->get_results(
            "SELECT genero, COUNT(*) as cantidad
             FROM $tabla_libros
             WHERE genero IS NOT NULL
             GROUP BY genero
             ORDER BY cantidad DESC
             LIMIT 5"
        );

        // Libros más prestados
        $libros_populares = $wpdb->get_results(
            "SELECT titulo, autor, veces_prestado
             FROM $tabla_libros
             ORDER BY veces_prestado DESC
             LIMIT 5"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_libros' => intval($total_libros),
                'libros_disponibles' => intval($libros_disponibles),
                'total_prestamos' => intval($total_prestamos),
                'prestamos_activos' => intval($prestamos_activos),
                'lectores_activos' => intval($lectores_activos),
                'total_resenas' => intval($total_resenas),
                'generos_populares' => $generos_populares,
                'libros_populares' => $libros_populares,
            ],
        ];
    }

    // =========================================================================
    // REST API ENDPOINTS
    // =========================================================================

    /**
     * API: Listar libros
     */
    public function api_listar_libros($request) {
        $params = [
            'query' => $request->get_param('busqueda'),
            'genero' => $request->get_param('genero'),
            'autor' => $request->get_param('autor'),
        ];

        $resultado = $this->action_buscar_libros($params);
        return new WP_REST_Response($this->sanitize_public_biblioteca_response($resultado), 200);
    }

    /**
     * API: Detalle libro
     */
    public function api_detalle_libro($request) {
        $resultado = $this->action_detalle_libro(['libro_id' => $request->get_param('id')]);
        $status = $resultado['success'] ? 200 : 404;
        return new WP_REST_Response($this->sanitize_public_biblioteca_response($resultado), $status);
    }

    /**
     * API: Crear libro
     */
    public function api_crear_libro($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_agregar_libro($params);
        $status = $resultado['success'] ? 201 : 400;
        return new WP_REST_Response($resultado, $status);
    }

    /**
     * API: Actualizar libro
     */
    public function api_actualizar_libro($request) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro_id = intval($request->get_param('id'));
        $usuario_id = get_current_user_id();

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d AND propietario_id = %d",
            $libro_id,
            $usuario_id
        ));

        if (!$libro) {
            return new WP_REST_Response(['success' => false, 'error' => 'Libro no encontrado o sin permisos'], 404);
        }

        $params = $request->get_json_params();
        $datos_actualizados = [];

        $campos_permitidos = ['titulo', 'autor', 'isbn', 'editorial', 'ano_publicacion', 'idioma', 'genero', 'num_paginas', 'descripcion', 'portada_url', 'estado_fisico', 'tipo', 'ubicacion', 'disponibilidad'];

        foreach ($campos_permitidos as $campo) {
            if (isset($params[$campo])) {
                $datos_actualizados[$campo] = sanitize_text_field($params[$campo]);
            }
        }

        if (!empty($datos_actualizados)) {
            $wpdb->update($tabla_libros, $datos_actualizados, ['id' => $libro_id]);
        }

        return new WP_REST_Response(['success' => true, 'mensaje' => 'Libro actualizado'], 200);
    }

    /**
     * API: Eliminar libro
     */
    public function api_eliminar_libro($request) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        $libro_id = intval($request->get_param('id'));
        $usuario_id = get_current_user_id();

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d AND propietario_id = %d",
            $libro_id,
            $usuario_id
        ));

        if (!$libro) {
            return new WP_REST_Response(['success' => false, 'error' => 'Libro no encontrado o sin permisos'], 404);
        }

        // Verificar si tiene préstamos activos
        $prestamos_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE libro_id = %d AND estado IN ('pendiente', 'activo')",
            $libro_id
        ));

        if ($prestamos_activos > 0) {
            return new WP_REST_Response(['success' => false, 'error' => 'No puedes eliminar un libro con préstamos activos'], 400);
        }

        $wpdb->delete($tabla_libros, ['id' => $libro_id]);

        return new WP_REST_Response(['success' => true, 'mensaje' => 'Libro eliminado'], 200);
    }

    /**
     * API: Mis libros
     */
    public function api_mis_libros($request) {
        $resultado = $this->action_mis_libros([]);
        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Mis préstamos
     */
    public function api_mis_prestamos($request) {
        $resultado = $this->action_mis_prestamos([]);
        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Solicitar préstamo
     */
    public function api_solicitar_prestamo($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_solicitar_prestamo($params);
        $status = $resultado['success'] ? 201 : 400;
        return new WP_REST_Response($resultado, $status);
    }

    /**
     * API: Devolver libro
     */
    public function api_devolver_libro($request) {
        $resultado = $this->action_devolver_libro(['prestamo_id' => $request->get_param('id')]);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * API: Renovar préstamo
     */
    public function api_renovar_prestamo($request) {
        $resultado = $this->action_renovar_prestamo(['prestamo_id' => $request->get_param('id')]);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * API: Reservar libro
     */
    public function api_reservar_libro($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_reservar_libro($params);
        $status = $resultado['success'] ? 201 : 400;
        return new WP_REST_Response($resultado, $status);
    }

    /**
     * API: Cancelar reserva
     */
    public function api_cancelar_reserva($request) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $reserva_id = intval($request->get_param('id'));
        $usuario_id = get_current_user_id();

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND usuario_id = %d AND estado = 'pendiente'",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            return new WP_REST_Response(['success' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        $wpdb->update($tabla_reservas, ['estado' => 'cancelada'], ['id' => $reserva_id]);

        return new WP_REST_Response(['success' => true, 'mensaje' => 'Reserva cancelada'], 200);
    }

    /**
     * API: Valorar libro
     */
    public function api_valorar_libro($request) {
        $params = $request->get_json_params();
        $resultado = $this->action_valorar_libro($params);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * API: Listar géneros
     */
    public function api_listar_generos($request) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $generos = $wpdb->get_results(
            "SELECT genero, COUNT(*) as cantidad
             FROM $tabla_libros
             WHERE genero IS NOT NULL AND genero != ''
             GROUP BY genero
             ORDER BY cantidad DESC"
        );

        return new WP_REST_Response([
            'success' => true,
            'generos' => $generos,
        ], 200);
    }

    /**
     * API: Estadísticas
     */
    public function api_estadisticas($request) {
        $resultado = $this->action_estadisticas_biblioteca(['periodo' => $request->get_param('periodo')]);
        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Recomendaciones
     */
    public function api_recomendaciones($request) {
        $resultado = $this->action_recomendaciones([]);
        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Buscar ISBN
     */
    public function api_buscar_isbn($request) {
        $isbn = sanitize_text_field($request->get_param('isbn'));
        $resultado = $this->buscar_datos_isbn($isbn);
        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 404);
    }

    /**
     * API: Solicitudes pendientes
     */
    public function api_solicitudes_pendientes($request) {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $usuario_id = get_current_user_id();

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor, l.portada_url,
                    u.display_name as prestatario_nombre, u.user_email as prestatario_email
             FROM $tabla_prestamos p
             INNER JOIN $tabla_libros l ON p.libro_id = l.id
             LEFT JOIN {$wpdb->users} u ON p.prestatario_id = u.ID
             WHERE p.prestamista_id = %d AND p.estado = 'pendiente'
             ORDER BY p.fecha_solicitud DESC",
            $usuario_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'solicitudes' => array_map([$this, 'formatear_prestamo'], $solicitudes),
        ], 200);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Solicitar préstamo
     */
    public function ajax_solicitar_prestamo() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_solicitar_prestamo([
            'libro_id' => isset($_POST['libro_id']) ? intval($_POST['libro_id']) : 0,
            'notas' => isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '',
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Devolver libro
     */
    public function ajax_devolver_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_devolver_libro([
            'prestamo_id' => isset($_POST['prestamo_id']) ? intval($_POST['prestamo_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Renovar préstamo
     */
    public function ajax_renovar_prestamo() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_renovar_prestamo([
            'prestamo_id' => isset($_POST['prestamo_id']) ? intval($_POST['prestamo_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Reservar libro
     */
    public function ajax_reservar_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_reservar_libro([
            'libro_id' => isset($_POST['libro_id']) ? intval($_POST['libro_id']) : 0,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $reserva_id = isset($_POST['reserva_id']) ? intval($_POST['reserva_id']) : 0;
        $usuario_id = get_current_user_id();

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND usuario_id = %d AND estado = 'pendiente'",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            wp_send_json(['success' => false, 'error' => 'Reserva no encontrada']);
        }

        $wpdb->update($tabla_reservas, ['estado' => 'cancelada'], ['id' => $reserva_id]);

        wp_send_json(['success' => true, 'mensaje' => 'Reserva cancelada']);
    }

    /**
     * AJAX: Valorar libro
     */
    public function ajax_valorar_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_valorar_libro([
            'libro_id' => isset($_POST['libro_id']) ? intval($_POST['libro_id']) : 0,
            'valoracion' => isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0,
            'resena' => isset($_POST['resena']) ? sanitize_textarea_field($_POST['resena']) : '',
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Agregar libro
     */
    public function ajax_agregar_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $resultado = $this->action_agregar_libro([
            'titulo' => isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '',
            'autor' => isset($_POST['autor']) ? sanitize_text_field($_POST['autor']) : '',
            'isbn' => isset($_POST['isbn']) ? sanitize_text_field($_POST['isbn']) : '',
            'editorial' => isset($_POST['editorial']) ? sanitize_text_field($_POST['editorial']) : '',
            'ano' => isset($_POST['ano']) ? intval($_POST['ano']) : null,
            'idioma' => isset($_POST['idioma']) ? sanitize_text_field($_POST['idioma']) : 'Español',
            'genero' => isset($_POST['genero']) ? sanitize_text_field($_POST['genero']) : '',
            'paginas' => isset($_POST['paginas']) ? intval($_POST['paginas']) : null,
            'descripcion' => isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '',
            'portada_url' => isset($_POST['portada_url']) ? esc_url_raw($_POST['portada_url']) : '',
            'estado_fisico' => isset($_POST['estado_fisico']) ? sanitize_text_field($_POST['estado_fisico']) : 'bueno',
            'tipo' => isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'prestamo',
            'ubicacion' => isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '',
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Editar libro
     */
    public function ajax_editar_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro_id = isset($_POST['libro_id']) ? intval($_POST['libro_id']) : 0;
        $usuario_id = get_current_user_id();

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d AND propietario_id = %d",
            $libro_id,
            $usuario_id
        ));

        if (!$libro) {
            wp_send_json(['success' => false, 'error' => 'Libro no encontrado o sin permisos']);
        }

        $datos_actualizados = [];
        $campos = ['titulo', 'autor', 'isbn', 'editorial', 'idioma', 'genero', 'descripcion', 'portada_url', 'estado_fisico', 'tipo', 'ubicacion'];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $datos_actualizados[$campo] = sanitize_text_field($_POST[$campo]);
            }
        }

        if (isset($_POST['ano'])) {
            $datos_actualizados['ano_publicacion'] = intval($_POST['ano']);
        }

        if (isset($_POST['paginas'])) {
            $datos_actualizados['num_paginas'] = intval($_POST['paginas']);
        }

        if (!empty($datos_actualizados)) {
            $wpdb->update($tabla_libros, $datos_actualizados, ['id' => $libro_id]);
        }

        wp_send_json(['success' => true, 'mensaje' => 'Libro actualizado']);
    }

    /**
     * AJAX: Eliminar libro
     */
    public function ajax_eliminar_libro() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        $libro_id = isset($_POST['libro_id']) ? intval($_POST['libro_id']) : 0;
        $usuario_id = get_current_user_id();

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d AND propietario_id = %d",
            $libro_id,
            $usuario_id
        ));

        if (!$libro) {
            wp_send_json(['success' => false, 'error' => 'Libro no encontrado o sin permisos']);
        }

        $prestamos_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE libro_id = %d AND estado IN ('pendiente', 'activo')",
            $libro_id
        ));

        if ($prestamos_activos > 0) {
            wp_send_json(['success' => false, 'error' => 'No puedes eliminar un libro con préstamos activos']);
        }

        $wpdb->delete($tabla_libros, ['id' => $libro_id]);

        wp_send_json(['success' => true, 'mensaje' => 'Libro eliminado']);
    }

    /**
     * AJAX: Aprobar préstamo
     */
    public function ajax_aprobar_prestamo() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $prestamo_id = isset($_POST['prestamo_id']) ? intval($_POST['prestamo_id']) : 0;
        $usuario_id = get_current_user_id();
        $punto_entrega = isset($_POST['punto_entrega']) ? sanitize_text_field($_POST['punto_entrega']) : '';

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d AND prestamista_id = %d AND estado = 'pendiente'",
            $prestamo_id,
            $usuario_id
        ));

        if (!$prestamo) {
            wp_send_json(['success' => false, 'error' => 'Préstamo no encontrado']);
        }

        $settings = $this->get_settings();
        $dias_prestamo = $settings['duracion_prestamo_dias'] ?? 30;
        $fecha_devolucion = date('Y-m-d H:i:s', strtotime("+{$dias_prestamo} days"));

        $wpdb->update($tabla_prestamos, [
            'estado' => 'activo',
            'fecha_prestamo' => current_time('mysql'),
            'fecha_devolucion_prevista' => $fecha_devolucion,
            'punto_entrega' => $punto_entrega,
        ], ['id' => $prestamo_id]);

        // Actualizar disponibilidad del libro
        $wpdb->update($tabla_libros, ['disponibilidad' => 'prestado'], ['id' => $prestamo->libro_id]);

        // Incrementar contador de préstamos
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_libros SET veces_prestado = veces_prestado + 1 WHERE id = %d",
            $prestamo->libro_id
        ));

        // Notificar al prestatario
        $this->notificar_prestamo_aprobado($prestamo, $punto_entrega, $fecha_devolucion);

        wp_send_json([
            'success' => true,
            'mensaje' => 'Préstamo aprobado',
            'fecha_devolucion' => date_i18n(get_option('date_format'), strtotime($fecha_devolucion)),
        ]);
    }

    /**
     * AJAX: Rechazar préstamo
     */
    public function ajax_rechazar_prestamo() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        $prestamo_id = isset($_POST['prestamo_id']) ? intval($_POST['prestamo_id']) : 0;
        $usuario_id = get_current_user_id();
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';

        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE id = %d AND prestamista_id = %d AND estado = 'pendiente'",
            $prestamo_id,
            $usuario_id
        ));

        if (!$prestamo) {
            wp_send_json(['success' => false, 'error' => 'Préstamo no encontrado']);
        }

        $wpdb->update($tabla_prestamos, [
            'estado' => 'rechazado',
            'notas_prestamista' => $motivo,
        ], ['id' => $prestamo_id]);

        // Notificar al prestatario
        $this->notificar_prestamo_rechazado($prestamo, $motivo);

        wp_send_json(['success' => true, 'mensaje' => 'Préstamo rechazado']);
    }

    /**
     * AJAX: Buscar ISBN
     */
    public function ajax_buscar_isbn() {
        check_ajax_referer('biblioteca_nonce', 'nonce');

        $isbn = isset($_POST['isbn']) ? sanitize_text_field($_POST['isbn']) : '';
        $resultado = $this->buscar_datos_isbn($isbn);

        wp_send_json($resultado);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Catálogo de libros
     */
    public function shortcode_catalogo($atts) {
        $atts = shortcode_atts([
            'genero' => '',
            'limite' => 12,
            'columnas' => 4,
        ], $atts);

        $base_url = plugins_url('assets/', __FILE__);
        $version_modulo = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('biblioteca-frontend', $base_url . 'css/biblioteca-frontend.css', [], $version_modulo);
        wp_enqueue_script('biblioteca-frontend', $base_url . 'js/biblioteca-frontend.js', ['jquery'], $version_modulo, true);

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_nonce'),
            'usuario_logueado' => is_user_logged_in(),
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de libro
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $libro_id = $atts['id'] ?: (isset($_GET['libro_id']) ? intval($_GET['libro_id']) : 0);

        if (!$libro_id) {
            return '<p>' . __('Libro no especificado.', 'flavor-chat-ia') . '</p>';
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version_modulo = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('biblioteca-frontend', $base_url . 'css/biblioteca-frontend.css', [], $version_modulo);
        wp_enqueue_script('biblioteca-frontend', $base_url . 'js/biblioteca-frontend.js', ['jquery'], $version_modulo, true);

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_nonce'),
            'usuario_logueado' => is_user_logged_in(),
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis libros
     */
    public function shortcode_mis_libros($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus libros.', 'flavor-chat-ia') . '</p>';
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version_modulo = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('biblioteca-frontend', $base_url . 'css/biblioteca-frontend.css', [], $version_modulo);
        wp_enqueue_script('biblioteca-frontend', $base_url . 'js/biblioteca-frontend.js', ['jquery'], $version_modulo, true);

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_nonce'),
            'usuario_logueado' => true,
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/mis-libros.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis préstamos
     */
    public function shortcode_mis_prestamos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus préstamos.', 'flavor-chat-ia') . '</p>';
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version_modulo = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('biblioteca-frontend', $base_url . 'css/biblioteca-frontend.css', [], $version_modulo);
        wp_enqueue_script('biblioteca-frontend', $base_url . 'js/biblioteca-frontend.js', ['jquery'], $version_modulo, true);

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_nonce'),
            'usuario_logueado' => true,
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/mis-prestamos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Agregar libro
     */
    public function shortcode_agregar($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para agregar libros.', 'flavor-chat-ia') . '</p>';
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version_modulo = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style('biblioteca-frontend', $base_url . 'css/biblioteca-frontend.css', [], $version_modulo);
        wp_enqueue_script('biblioteca-frontend', $base_url . 'js/biblioteca-frontend.js', ['jquery'], $version_modulo, true);
        wp_enqueue_media();

        wp_localize_script('biblioteca-frontend', 'bibliotecaData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_nonce'),
            'usuario_logueado' => true,
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/agregar-libro.php';
        return ob_get_clean();
    }

    // =========================================================================
    // WP CRON
    // =========================================================================

    /**
     * Enviar recordatorios de devolución
     */
    public function enviar_recordatorios_devolucion() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $settings = $this->get_settings();
        $dias_antes = $settings['dias_antes_notificar'] ?? 3;

        $fecha_limite = date('Y-m-d', strtotime("+{$dias_antes} days"));

        // Préstamos próximos a vencer
        $prestamos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor
             FROM $tabla_prestamos p
             INNER JOIN $tabla_libros l ON p.libro_id = l.id
             WHERE p.estado = 'activo'
             AND DATE(p.fecha_devolucion_prevista) = %s",
            $fecha_limite
        ));

        foreach ($prestamos as $prestamo) {
            $this->notificar_recordatorio_devolucion($prestamo);
        }

        // Marcar préstamos retrasados
        $wpdb->query(
            "UPDATE $tabla_prestamos
             SET estado = 'retrasado'
             WHERE estado = 'activo'
             AND fecha_devolucion_prevista < NOW()"
        );
    }

    /**
     * Procesar reservas expiradas
     */
    public function procesar_reservas_expiradas() {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        // Expirar reservas confirmadas que no se recogieron
        $reservas_expiradas = $wpdb->get_results(
            "SELECT r.*, l.titulo
             FROM $tabla_reservas r
             INNER JOIN $tabla_libros l ON r.libro_id = l.id
             WHERE r.estado = 'confirmada'
             AND r.fecha_expiracion < NOW()"
        );

        foreach ($reservas_expiradas as $reserva) {
            $wpdb->update($tabla_reservas, ['estado' => 'expirada'], ['id' => $reserva->id]);

            // Verificar siguiente reserva
            $siguiente_reserva = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_reservas
                 WHERE libro_id = %d AND estado = 'pendiente'
                 ORDER BY fecha_solicitud ASC LIMIT 1",
                $reserva->libro_id
            ));

            if ($siguiente_reserva) {
                $wpdb->update($tabla_reservas, [
                    'estado' => 'confirmada',
                    'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+48 hours')),
                ], ['id' => $siguiente_reserva->id]);

                $this->notificar_reserva_disponible($siguiente_reserva);
            } else {
                $wpdb->update($tabla_libros, ['disponibilidad' => 'disponible'], ['id' => $reserva->libro_id]);
            }
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Formatear datos del libro
     */
    private function formatear_libro($libro) {
        $propietario = get_userdata($libro->propietario_id);
        $base_url = plugins_url('assets/', __FILE__);
        $imagen_placeholder = $base_url . 'img/libro-placeholder.png';

        return [
            'id' => intval($libro->id),
            'titulo' => $libro->titulo,
            'autor' => $libro->autor,
            'isbn' => $libro->isbn,
            'editorial' => $libro->editorial,
            'ano' => $libro->ano_publicacion,
            'idioma' => $libro->idioma,
            'genero' => $libro->genero,
            'paginas' => $libro->num_paginas,
            'descripcion' => wp_trim_words($libro->descripcion, 30),
            'portada' => $libro->portada_url ?: $imagen_placeholder,
            'estado_fisico' => $libro->estado_fisico,
            'disponibilidad' => $libro->disponibilidad,
            'tipo' => $libro->tipo,
            'ubicacion' => $libro->ubicacion,
            'valoracion' => floatval($libro->valoracion_media),
            'veces_prestado' => intval($libro->veces_prestado),
            'propietario_id' => intval($libro->propietario_id),
            'propietario' => $propietario ? $propietario->display_name : __('Vecino', 'flavor-chat-ia'),
            'fecha_agregado' => date_i18n(get_option('date_format'), strtotime($libro->fecha_agregado)),
        ];
    }

    /**
     * Formatear datos del préstamo
     */
    private function formatear_prestamo($prestamo) {
        $data = [
            'id' => intval($prestamo->id),
            'libro_id' => intval($prestamo->libro_id),
            'titulo' => $prestamo->titulo ?? '',
            'autor' => $prestamo->autor ?? '',
            'portada' => $prestamo->portada_url ?? '',
            'estado' => $prestamo->estado,
            'renovaciones' => intval($prestamo->renovaciones),
            'fecha_solicitud' => date_i18n(get_option('date_format'), strtotime($prestamo->fecha_solicitud)),
            'notas_prestatario' => $prestamo->notas_prestatario,
            'punto_entrega' => $prestamo->punto_entrega,
        ];

        if (!empty($prestamo->fecha_prestamo)) {
            $data['fecha_prestamo'] = date_i18n(get_option('date_format'), strtotime($prestamo->fecha_prestamo));
        }

        if (!empty($prestamo->fecha_devolucion_prevista)) {
            $data['fecha_devolucion_prevista'] = date_i18n(get_option('date_format'), strtotime($prestamo->fecha_devolucion_prevista));
            $data['dias_restantes'] = max(0, floor((strtotime($prestamo->fecha_devolucion_prevista) - time()) / 86400));
        }

        if (isset($prestamo->propietario_nombre)) {
            $data['propietario'] = $prestamo->propietario_nombre;
        }

        if (isset($prestamo->prestatario_nombre)) {
            $data['prestatario'] = $prestamo->prestatario_nombre;
        }

        if (isset($prestamo->prestatario_email)) {
            $data['prestatario_email'] = $prestamo->prestatario_email;
        }

        return $data;
    }

    private function sanitize_public_biblioteca_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['libros']) && is_array($respuesta['libros'])) {
            $respuesta['libros'] = array_map([$this, 'sanitize_public_libro'], $respuesta['libros']);
        }

        if (!empty($respuesta['libro']) && is_array($respuesta['libro'])) {
            $respuesta['libro'] = $this->sanitize_public_libro($respuesta['libro']);
        }

        return $respuesta;
    }

    private function sanitize_public_libro($libro) {
        if (!is_array($libro)) {
            return $libro;
        }

        unset($libro['propietario_id'], $libro['ubicacion']);
        $libro['propietario'] = __('Vecino', 'flavor-chat-ia');

        return $libro;
    }

    /**
     * Calcular fecha inicio según periodo
     */
    private function calcular_fecha_inicio($periodo) {
        switch ($periodo) {
            case 'semana':
                return date('Y-m-d', strtotime('-1 week'));
            case 'mes':
                return date('Y-m-d', strtotime('-1 month'));
            case 'trimestre':
                return date('Y-m-d', strtotime('-3 months'));
            case 'ano':
                return date('Y-m-d', strtotime('-1 year'));
            default:
                return date('Y-m-d', strtotime('-1 month'));
        }
    }

    /**
     * Buscar datos de libro por ISBN
     */
    private function buscar_datos_isbn($isbn) {
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
            return ['success' => false, 'error' => 'ISBN inválido'];
        }

        // Buscar en Open Library API
        $response = wp_remote_get("https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data");

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => 'Error al buscar ISBN'];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body["ISBN:{$isbn}"])) {
            return ['success' => false, 'error' => 'Libro no encontrado'];
        }

        $datos = $body["ISBN:{$isbn}"];

        return [
            'success' => true,
            'libro' => [
                'titulo' => $datos['title'] ?? '',
                'autor' => isset($datos['authors'][0]['name']) ? $datos['authors'][0]['name'] : '',
                'editorial' => isset($datos['publishers'][0]['name']) ? $datos['publishers'][0]['name'] : '',
                'ano' => isset($datos['publish_date']) ? intval($datos['publish_date']) : null,
                'paginas' => $datos['number_of_pages'] ?? null,
                'portada' => isset($datos['cover']['medium']) ? $datos['cover']['medium'] : '',
            ],
        ];
    }

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    /**
     * Notificar al propietario sobre nueva solicitud
     */
    private function notificar_propietario_solicitud($libro, $solicitante_id, $prestamo_id) {
        $propietario = get_userdata($libro->propietario_id);
        $solicitante = get_userdata($solicitante_id);

        if (!$propietario || !$solicitante) {
            return;
        }

        $asunto = sprintf(__('Nueva solicitud de préstamo: %s', 'flavor-chat-ia'), $libro->titulo);
        $mensaje = sprintf(
            __('%s ha solicitado prestado tu libro "%s". Revisa la solicitud en tu panel de biblioteca.', 'flavor-chat-ia'),
            $solicitante->display_name,
            $libro->titulo
        );

        do_action('flavor_notificacion_enviar', $libro->propietario_id, $asunto, $mensaje, 'biblioteca_solicitud');
    }

    /**
     * Notificar al prestatario que el préstamo fue aprobado
     */
    private function notificar_prestamo_aprobado($prestamo, $punto_entrega, $fecha_devolucion) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $prestamo->libro_id
        ));

        $prestatario = get_userdata($prestamo->prestatario_id);

        if (!$prestatario || !$libro) {
            return;
        }

        $asunto = sprintf(__('Préstamo aprobado: %s', 'flavor-chat-ia'), $libro->titulo);
        $mensaje = sprintf(
            __('Tu solicitud de préstamo para "%s" ha sido aprobada. Punto de entrega: %s. Fecha de devolución: %s.', 'flavor-chat-ia'),
            $libro->titulo,
            $punto_entrega ?: __('A acordar', 'flavor-chat-ia'),
            date_i18n(get_option('date_format'), strtotime($fecha_devolucion))
        );

        do_action('flavor_notificacion_enviar', $prestamo->prestatario_id, $asunto, $mensaje, 'biblioteca_aprobado');
    }

    /**
     * Notificar al prestatario que el préstamo fue rechazado
     */
    private function notificar_prestamo_rechazado($prestamo, $motivo) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $prestamo->libro_id
        ));

        $prestatario = get_userdata($prestamo->prestatario_id);

        if (!$prestatario || !$libro) {
            return;
        }

        $asunto = sprintf(__('Préstamo no disponible: %s', 'flavor-chat-ia'), $libro->titulo);
        $mensaje = sprintf(
            __('Tu solicitud de préstamo para "%s" no ha podido ser atendida. %s', 'flavor-chat-ia'),
            $libro->titulo,
            $motivo ? __('Motivo: ', 'flavor-chat-ia') . $motivo : ''
        );

        do_action('flavor_notificacion_enviar', $prestamo->prestatario_id, $asunto, $mensaje, 'biblioteca_rechazado');
    }

    /**
     * Notificar recordatorio de devolución
     */
    private function notificar_recordatorio_devolucion($prestamo) {
        $prestatario = get_userdata($prestamo->prestatario_id);

        if (!$prestatario) {
            return;
        }

        $asunto = sprintf(__('Recordatorio: Devolución de "%s"', 'flavor-chat-ia'), $prestamo->titulo);
        $mensaje = sprintf(
            __('Recuerda que el libro "%s" debe ser devuelto el %s. Si necesitas más tiempo, puedes solicitar una renovación.', 'flavor-chat-ia'),
            $prestamo->titulo,
            date_i18n(get_option('date_format'), strtotime($prestamo->fecha_devolucion_prevista))
        );

        do_action('flavor_notificacion_enviar', $prestamo->prestatario_id, $asunto, $mensaje, 'biblioteca_recordatorio');
    }

    /**
     * Notificar que la reserva está disponible
     */
    private function notificar_reserva_disponible($reserva) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $reserva->libro_id
        ));

        $usuario = get_userdata($reserva->usuario_id);

        if (!$usuario || !$libro) {
            return;
        }

        $asunto = sprintf(__('Libro disponible: %s', 'flavor-chat-ia'), $libro->titulo);
        $mensaje = sprintf(
            __('¡Buenas noticias! El libro "%s" que reservaste ya está disponible. Tienes 48 horas para recogerlo.', 'flavor-chat-ia'),
            $libro->titulo
        );

        do_action('flavor_notificacion_enviar', $reserva->usuario_id, $asunto, $mensaje, 'biblioteca_reserva_disponible');
    }

    // =========================================================================
    // TOOL DEFINITIONS Y KNOWLEDGE BASE
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'biblioteca_buscar',
                'description' => 'Buscar libros en la biblioteca comunitaria',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Título, autor o tema'],
                        'genero' => ['type' => 'string', 'description' => 'Género literario'],
                    ],
                ],
            ],
            [
                'name' => 'biblioteca_solicitar',
                'description' => 'Solicitar préstamo de un libro',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'libro_id' => ['type' => 'integer', 'description' => 'ID del libro'],
                    ],
                    'required' => ['libro_id'],
                ],
            ],
            [
                'name' => 'biblioteca_mis_prestamos',
                'description' => 'Ver mis préstamos activos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_biblioteca' => [
                'label' => __('Hero Biblioteca', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Biblioteca Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Miles de libros compartidos entre vecinos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'biblioteca/hero',
            ],
            'libros_grid' => [
                'label' => __('Grid de Libros', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Libros Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [3, 4, 5, 6], 'default' => 5],
                    'limite' => ['type' => 'number', 'default' => 12],
                    'genero' => ['type' => 'text', 'default' => ''],
                    'mostrar_propietario' => ['type' => 'toggle', 'default' => false],
                ],
                'template' => 'biblioteca/grid',
            ],
            'generos_nav' => [
                'label' => __('Navegación por Géneros', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Explora por Género', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'carrusel'], 'default' => 'grid'],
                ],
                'template' => 'biblioteca/generos',
            ],
            'stats_biblioteca' => [
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'mostrar_total_libros' => ['type' => 'toggle', 'default' => true],
                    'mostrar_prestamos' => ['type' => 'toggle', 'default' => true],
                    'mostrar_lectores' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'biblioteca/stats',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Biblioteca Comunitaria**

Comparte, presta e intercambia libros con tus vecinos.

**Cómo funciona:**
1. Agrega tus libros que quieras compartir
2. Busca libros que te interesen
3. Solicita préstamo al propietario
4. Acuerda punto de entrega
5. Lee y devuelve en el plazo acordado

**Tipos de libros:**
- Donados: Son de la comunidad, gratis
- Préstamo: Debes devolverlos
- Intercambio: Cambio por otro libro tuyo

**Géneros disponibles:**
- Novela, Ensayo, Poesía
- Ciencia ficción, Fantasía
- Historia, Biografía
- Técnico, Académico
- Infantil, Juvenil
- Y muchos más...

**Sistema de puntos:**
- Gana puntos prestando libros
- Usa puntos para solicitar préstamos
- Fomenta la reciprocidad

**Ventajas:**
- Acceso gratis a miles de libros
- Conoce gustos de tus vecinos
- Reduce consumo, reutiliza
- Crea comunidad lectora
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cuánto tiempo puedo tener un libro?',
                'respuesta' => 'Normalmente 30 días, pero puedes renovar hasta 2 veces si nadie lo ha reservado.',
            ],
            [
                'pregunta' => '¿Qué pasa si pierdo un libro?',
                'respuesta' => 'Debes reponerlo o acordar compensación con el propietario.',
            ],
            [
                'pregunta' => '¿Puedo donar libros?',
                'respuesta' => 'Sí, los libros donados pasan a ser de la comunidad y cualquiera puede tomarlos.',
            ],
            [
                'pregunta' => '¿Cómo busco por ISBN?',
                'respuesta' => 'Usa el escáner de código de barras o introduce el ISBN manualmente para autocompletar los datos del libro.',
            ],
            [
                'pregunta' => '¿Qué pasa si reservo un libro?',
                'respuesta' => 'Recibirás una notificación cuando el libro esté disponible y tendrás 48 horas para recogerlo.',
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
            Flavor_Page_Creator::refresh_module_pages('biblioteca');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('biblioteca');
        if (!$pagina && !get_option('flavor_biblioteca_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['biblioteca']);
            update_option('flavor_biblioteca_pages_created', 1, false);
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

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            return $estadisticas;
        }

        // Total de libros disponibles
        $libros_disponibles = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_libros} WHERE estado = 'disponible'"
        );

        $estadisticas['libros_disponibles'] = [
            'icon' => 'dashicons-book',
            'valor' => $libros_disponibles,
            'label' => __('Libros disponibles', 'flavor-chat-ia'),
            'color' => 'blue',
        ];

        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            // Préstamos activos del usuario
            $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_prestamos}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $usuario_id
            ));

            $estadisticas['mis_prestamos'] = [
                'icon' => 'dashicons-book-alt',
                'valor' => $prestamos_activos,
                'label' => __('Mis préstamos', 'flavor-chat-ia'),
                'color' => $prestamos_activos > 0 ? 'green' : 'gray',
            ];

            // Préstamos por devolver (próximos a vencer)
            $por_devolver = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_prestamos}
                 WHERE usuario_id = %d AND estado = 'activo'
                 AND fecha_devolucion <= DATE_ADD(NOW(), INTERVAL 3 DAY)",
                $usuario_id
            ));

            if ($por_devolver > 0) {
                $estadisticas['por_devolver'] = [
                    'icon' => 'dashicons-warning',
                    'valor' => $por_devolver,
                    'label' => __('Por devolver', 'flavor-chat-ia'),
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
                'title' => __('Biblioteca', 'flavor-chat-ia'),
                'slug' => 'biblioteca',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Biblioteca Comunitaria', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte y toma prestados libros de la comunidad', 'flavor-chat-ia'),
                    'background' => 'gradient',
                    'module' => 'biblioteca',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="biblioteca" action="libros_disponibles" columnas="4" limite="16"]',
                ]),
                'parent' => 0,
            ],

            // Catálogo
            [
                'title' => __('Catálogo', 'flavor-chat-ia'),
                'slug' => 'catalogo',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Catálogo de Libros', 'flavor-chat-ia'),
                    'subtitle' => __('Explora todos los libros disponibles', 'flavor-chat-ia'),
                    'module' => 'biblioteca',
                    'current' => 'catalogo',
                    'content_after' => '[flavor_module_listing module="biblioteca" action="catalogo_completo" columnas="4"]',
                ]),
                'parent' => 'biblioteca',
            ],

            // Mis préstamos
            [
                'title' => __('Mis Préstamos', 'flavor-chat-ia'),
                'slug' => 'mis-prestamos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Préstamos', 'flavor-chat-ia'),
                    'subtitle' => __('Libros que tienes en préstamo', 'flavor-chat-ia'),
                    'module' => 'biblioteca',
                    'current' => 'prestamos',
                    'content_after' => '[flavor_module_listing module="biblioteca" action="mis_prestamos" user_specific="yes"]',
                ]),
                'parent' => 'biblioteca',
            ],

            // Añadir libro
            [
                'title' => __('Añadir Libro', 'flavor-chat-ia'),
                'slug' => 'anadir',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Añadir un Libro', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte tus libros con la comunidad', 'flavor-chat-ia'),
                    'module' => 'biblioteca',
                    'current' => 'anadir',
                    'content_after' => '[flavor_module_form module="biblioteca" action="anadir_libro"]',
                ]),
                'parent' => 'biblioteca',
            ],

            // Reservas
            [
                'title' => __('Reservas', 'flavor-chat-ia'),
                'slug' => 'reservas',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Reservas', 'flavor-chat-ia'),
                    'subtitle' => __('Libros que has reservado', 'flavor-chat-ia'),
                    'module' => 'biblioteca',
                    'current' => 'reservas',
                    'content_after' => '[flavor_module_listing module="biblioteca" action="mis_reservas" user_specific="yes"]',
                ]),
                'parent' => 'biblioteca',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'biblioteca',
            'title'    => __('Biblioteca Comunitaria', 'flavor-chat-ia'),
            'subtitle' => __('Comparte y descubre libros con tus vecinos', 'flavor-chat-ia'),
            'icon'     => '📚',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'          => 'flavor_biblioteca',
                'status_field'   => 'estado',
                'exclude_status' => 'eliminado',
                'order_by'       => 'titulo ASC',
                'filter_fields'  => ['estado', 'categoria', 'genero'],
            ],

            'fields' => [
                'titulo'      => 'titulo',
                'descripcion' => 'sinopsis',
                'imagen'      => 'portada',
                'estado'      => 'estado',
                'autor'       => 'autor',
                'categoria'   => 'genero',
                'isbn'        => 'isbn',
                'user_id'     => 'propietario_id',
            ],

            'estados' => [
                'disponible' => ['label' => __('Disponible', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'prestado'   => ['label' => __('Prestado', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '🟡'],
                'reservado'  => ['label' => __('Reservado', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🔵'],
            ],

            'stats' => [
                ['label' => __('Libros', 'flavor-chat-ia'), 'icon' => '📚', 'color' => 'sky', 'count_where' => "1=1"],
                ['label' => __('Disponibles', 'flavor-chat-ia'), 'icon' => '🟢', 'color' => 'green', 'count_where' => "estado = 'disponible'"],
                ['label' => __('Prestados', 'flavor-chat-ia'), 'icon' => '📖', 'color' => 'yellow', 'count_where' => "estado = 'prestado'"],
                ['label' => __('Lectores', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'blue', 'query' => "SELECT COUNT(DISTINCT usuario_id) FROM {table}_prestamos"],
            ],

            'card' => [
                'color' => 'sky', 'icon' => '📚',
                'meta' => [['icon' => '✍️', 'field' => 'autor'], ['icon' => '📁', 'field' => 'categoria']],
            ],

            'tabs' => [
                'catalogo' => ['label' => __('Catálogo', 'flavor-chat-ia'), 'icon' => 'dashicons-book-alt', 'content' => 'template:_archive.php'],
                'mis-prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'content' => 'template:mis-prestamos.php'],
                'novedades' => ['label' => __('Novedades', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled'],
                'resenas' => ['label' => __('Reseñas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments', 'is_integration' => true, 'source_module' => 'foros'],
                'clubes' => ['label' => __('Clubes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'is_integration' => true, 'content' => '[biblioteca_clubes]'],
            ],

            'archive' => [
                'columns' => 4, 'per_page' => 16,
                'cta_text' => __('Añadir libro', 'flavor-chat-ia'), 'cta_icon' => '➕',
                'empty_state' => ['icon' => '📚', 'title' => __('No hay libros', 'flavor-chat-ia')],
            ],

            'dashboard' => [
                'show_header' => true,
                'quick_actions' => [
                    ['title' => __('Catálogo', 'flavor-chat-ia'), 'icon' => '📚', 'color' => 'sky', 'url' => home_url('/mi-portal/biblioteca/')],
                    ['title' => __('Préstamos', 'flavor-chat-ia'), 'icon' => '📖', 'color' => 'blue', 'url' => home_url('/mi-portal/biblioteca/?tab=mis-prestamos')],
                    ['title' => __('Añadir', 'flavor-chat-ia'), 'icon' => '➕', 'color' => 'green', 'url' => home_url('/mi-portal/biblioteca/anadir/')],
                    ['title' => __('Clubes', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'purple', 'url' => home_url('/mi-portal/biblioteca/?tab=clubes')],
                ],
            ],
        ];
    }

    // =========================================================================
    // ADMINISTRACIÓN - PANEL UNIFICADO
    // =========================================================================

    /**
     * Configuración de páginas de administración para el Panel Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => 'biblioteca',
            'label'      => __('Biblioteca', 'flavor-chat-ia'),
            'icon'       => 'dashicons-book',
            'capability' => 'manage_options',
            'categoria'  => 'comunidad',
            'paginas'    => [
                [
                    'slug'     => 'biblioteca-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug'     => 'biblioteca-catalogo',
                    'titulo'   => __('Catálogo', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_catalogo'],
                ],
                [
                    'slug'     => 'biblioteca-prestamos',
                    'titulo'   => __('Préstamos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_prestamos'],
                    'badge'    => [$this, 'contar_prestamos_pendientes'],
                ],
                [
                    'slug'     => 'biblioteca-usuarios',
                    'titulo'   => __('Usuarios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_usuarios'],
                ],
                [
                    'slug'     => 'biblioteca-config',
                    'titulo'   => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
        ];
    }

    /**
     * Contar préstamos pendientes de aprobación
     *
     * @return int
     */
    public function contar_prestamos_pendientes() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'pendiente'"
        );
    }

    /**
     * Renderiza el Dashboard de administración
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        // Obtener KPIs
        $total_libros = 0;
        $libros_disponibles = 0;
        $prestamos_activos = 0;
        $prestamos_pendientes = 0;
        $reservas_pendientes = 0;
        $usuarios_activos = 0;
        $prestamos_retrasados = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            $total_libros = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_libros}");
            $libros_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_libros} WHERE disponibilidad = 'disponible'"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'activo'"
            );
            $prestamos_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'pendiente'"
            );
            $prestamos_retrasados = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'retrasado'"
            );
            $usuarios_activos = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT prestatario_id) FROM {$tabla_prestamos}
                 WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
            $reservas_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'"
            );
        }

        // Últimos préstamos
        $ultimos_prestamos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $ultimos_prestamos = $wpdb->get_results(
                "SELECT p.*, l.titulo, l.autor,
                        prestatario.display_name as prestatario_nombre,
                        prestamista.display_name as prestamista_nombre
                 FROM {$tabla_prestamos} p
                 LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
                 LEFT JOIN {$wpdb->users} prestatario ON p.prestatario_id = prestatario.ID
                 LEFT JOIN {$wpdb->users} prestamista ON p.prestamista_id = prestamista.ID
                 ORDER BY p.fecha_solicitud DESC
                 LIMIT 10"
            );
        }

        // Libros más prestados
        $libros_populares = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            $libros_populares = $wpdb->get_results(
                "SELECT titulo, autor, veces_prestado, valoracion_media
                 FROM {$tabla_libros}
                 WHERE veces_prestado > 0
                 ORDER BY veces_prestado DESC
                 LIMIT 5"
            );
        }

        // Géneros más populares
        $generos_populares = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            $generos_populares = $wpdb->get_results(
                "SELECT genero, COUNT(*) as cantidad
                 FROM {$tabla_libros}
                 WHERE genero IS NOT NULL AND genero != ''
                 GROUP BY genero
                 ORDER BY cantidad DESC
                 LIMIT 5"
            );
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Biblioteca - Dashboard', 'flavor-chat-ia'), [
                ['label' => __('Añadir Libro', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=biblioteca-catalogo&action=nuevo'), 'class' => 'button-primary'],
            ]); ?>

            <!-- KPIs -->
            <div class="flavor-kpis" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-book" style="font-size: 32px; color: #2271b1;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($total_libros); ?></div>
                    <div style="color: #646970;"><?php _e('Total Libros', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #00a32a;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($libros_disponibles); ?></div>
                    <div style="color: #646970;"><?php _e('Disponibles', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-book-alt" style="font-size: 32px; color: #dba617;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($prestamos_activos); ?></div>
                    <div style="color: #646970;"><?php _e('Préstamos Activos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-clock" style="font-size: 32px; color: #d63638;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($prestamos_pendientes); ?></div>
                    <div style="color: #646970;"><?php _e('Pendientes Aprobar', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-calendar" style="font-size: 32px; color: #8c6c10;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($reservas_pendientes); ?></div>
                    <div style="color: #646970;"><?php _e('Reservas', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <span class="dashicons dashicons-groups" style="font-size: 32px; color: #3858e9;"></span>
                    <div style="font-size: 28px; font-weight: 600; margin: 10px 0;"><?php echo number_format_i18n($usuarios_activos); ?></div>
                    <div style="color: #646970;"><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <?php if ($prestamos_retrasados > 0): ?>
            <div class="notice notice-warning" style="margin: 20px 0;">
                <p>
                    <strong><?php _e('Atención:', 'flavor-chat-ia'); ?></strong>
                    <?php printf(
                        _n(
                            'Hay %d préstamo retrasado que requiere seguimiento.',
                            'Hay %d préstamos retrasados que requieren seguimiento.',
                            $prestamos_retrasados,
                            'flavor-chat-ia'
                        ),
                        $prestamos_retrasados
                    ); ?>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos&estado=retrasado'); ?>"><?php _e('Ver préstamos retrasados', 'flavor-chat-ia'); ?></a>
                </p>
            </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Últimos préstamos -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0;">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Últimos Préstamos', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($ultimos_prestamos)): ?>
                    <table class="widefat striped" style="border: none;">
                        <thead>
                            <tr>
                                <th><?php _e('Libro', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Prestatario', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_prestamos as $prestamo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($prestamo->titulo); ?></strong>
                                    <br><small><?php echo esc_html($prestamo->autor); ?></small>
                                </td>
                                <td><?php echo esc_html($prestamo->prestatario_nombre ?: __('Usuario', 'flavor-chat-ia')); ?></td>
                                <td>
                                    <?php
                                    $estados_colores = [
                                        'pendiente' => '#dba617',
                                        'activo' => '#00a32a',
                                        'devuelto' => '#646970',
                                        'retrasado' => '#d63638',
                                        'rechazado' => '#8c8f94',
                                    ];
                                    $color_estado = $estados_colores[$prestamo->estado] ?? '#646970';
                                    ?>
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; background: <?php echo $color_estado; ?>; color: #fff; font-size: 12px;">
                                        <?php echo esc_html(ucfirst($prestamo->estado)); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_solicitud)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-bottom: 0; text-align: right;">
                        <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos'); ?>" class="button">
                            <?php _e('Ver todos los préstamos', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                    <?php else: ?>
                    <p style="color: #646970;"><?php _e('No hay préstamos registrados aún.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Libros populares -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Libros Populares', 'flavor-chat-ia'); ?>
                        </h3>
                        <?php if (!empty($libros_populares)): ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($libros_populares as $libro): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
                                <strong><?php echo esc_html($libro->titulo); ?></strong>
                                <br><small style="color: #646970;">
                                    <?php echo esc_html($libro->autor); ?> |
                                    <?php printf(__('%d préstamos', 'flavor-chat-ia'), $libro->veces_prestado); ?>
                                    <?php if ($libro->valoracion_media > 0): ?>
                                    | <span style="color: #dba617;">★</span> <?php echo number_format($libro->valoracion_media, 1); ?>
                                    <?php endif; ?>
                                </small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p style="color: #646970;"><?php _e('Aún no hay datos suficientes.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Géneros -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-category"></span>
                            <?php _e('Géneros Populares', 'flavor-chat-ia'); ?>
                        </h3>
                        <?php if (!empty($generos_populares)): ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($generos_populares as $genero): ?>
                            <li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
                                <span><?php echo esc_html($genero->genero); ?></span>
                                <span class="count" style="background: #f0f0f1; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                    <?php echo number_format_i18n($genero->cantidad); ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p style="color: #646970;"><?php _e('No hay géneros definidos.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Catálogo de libros
     */
    public function render_admin_catalogo() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        // Paginación
        $items_per_page = 20;
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($pagina_actual - 1) * $items_per_page;

        // Filtros
        $filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $filtro_disponibilidad = isset($_GET['disponibilidad']) ? sanitize_text_field($_GET['disponibilidad']) : '';
        $filtro_genero = isset($_GET['genero']) ? sanitize_text_field($_GET['genero']) : '';

        $where_clauses = ['1=1'];
        $prepare_values = [];

        if (!empty($filtro_busqueda)) {
            $where_clauses[] = "(titulo LIKE %s OR autor LIKE %s OR isbn LIKE %s)";
            $like_busqueda = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
            $prepare_values[] = $like_busqueda;
            $prepare_values[] = $like_busqueda;
            $prepare_values[] = $like_busqueda;
        }

        if (!empty($filtro_disponibilidad)) {
            $where_clauses[] = "disponibilidad = %s";
            $prepare_values[] = $filtro_disponibilidad;
        }

        if (!empty($filtro_genero)) {
            $where_clauses[] = "genero = %s";
            $prepare_values[] = $filtro_genero;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Contar total
        $total_query = "SELECT COUNT(*) FROM {$tabla_libros} WHERE {$where_sql}";
        if (!empty($prepare_values)) {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($total_query, ...$prepare_values));
        } else {
            $total_items = (int) $wpdb->get_var($total_query);
        }
        $total_paginas = ceil($total_items / $items_per_page);

        // Obtener libros
        $query = "SELECT l.*, u.display_name as propietario_nombre
                  FROM {$tabla_libros} l
                  LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
                  WHERE {$where_sql}
                  ORDER BY l.fecha_agregado DESC
                  LIMIT {$items_per_page} OFFSET {$offset}";

        if (!empty($prepare_values)) {
            $libros = $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));
        } else {
            $libros = $wpdb->get_results($query);
        }

        // Obtener géneros para filtro
        $generos = $wpdb->get_col(
            "SELECT DISTINCT genero FROM {$tabla_libros} WHERE genero IS NOT NULL AND genero != '' ORDER BY genero"
        );

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Catálogo de Libros', 'flavor-chat-ia'), [
                ['label' => __('Añadir Libro', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=biblioteca-catalogo&action=nuevo'), 'class' => 'button-primary'],
                ['label' => __('Exportar CSV', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=biblioteca-catalogo&action=exportar'), 'class' => ''],
            ]); ?>

            <!-- Filtros -->
            <form method="get" style="margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="page" value="biblioteca-catalogo">
                <input type="search" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>"
                       placeholder="<?php esc_attr_e('Buscar por título, autor o ISBN...', 'flavor-chat-ia'); ?>"
                       style="min-width: 250px;">
                <select name="disponibilidad">
                    <option value=""><?php _e('Todas las disponibilidades', 'flavor-chat-ia'); ?></option>
                    <option value="disponible" <?php selected($filtro_disponibilidad, 'disponible'); ?>><?php _e('Disponible', 'flavor-chat-ia'); ?></option>
                    <option value="prestado" <?php selected($filtro_disponibilidad, 'prestado'); ?>><?php _e('Prestado', 'flavor-chat-ia'); ?></option>
                    <option value="reservado" <?php selected($filtro_disponibilidad, 'reservado'); ?>><?php _e('Reservado', 'flavor-chat-ia'); ?></option>
                    <option value="no_disponible" <?php selected($filtro_disponibilidad, 'no_disponible'); ?>><?php _e('No disponible', 'flavor-chat-ia'); ?></option>
                </select>
                <select name="genero">
                    <option value=""><?php _e('Todos los géneros', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($generos as $genero): ?>
                    <option value="<?php echo esc_attr($genero); ?>" <?php selected($filtro_genero, $genero); ?>><?php echo esc_html($genero); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
                <?php if ($filtro_busqueda || $filtro_disponibilidad || $filtro_genero): ?>
                <a href="<?php echo admin_url('admin.php?page=biblioteca-catalogo'); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>
            </form>

            <p class="description">
                <?php printf(__('Mostrando %d de %d libros', 'flavor-chat-ia'), count($libros), $total_items); ?>
            </p>

            <!-- Tabla de libros -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php _e('Portada', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Título / Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('ISBN', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Género', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Propietario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Disponibilidad', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Préstamos', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($libros)): ?>
                        <?php foreach ($libros as $libro): ?>
                        <tr>
                            <td>
                                <?php if ($libro->portada_url): ?>
                                <img src="<?php echo esc_url($libro->portada_url); ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                <span class="dashicons dashicons-book" style="font-size: 40px; color: #8c8f94;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($libro->titulo); ?></strong>
                                <br><span style="color: #646970;"><?php echo esc_html($libro->autor); ?></span>
                            </td>
                            <td><?php echo esc_html($libro->isbn ?: '—'); ?></td>
                            <td><?php echo esc_html($libro->genero ?: '—'); ?></td>
                            <td><?php echo esc_html($libro->propietario_nombre ?: __('Desconocido', 'flavor-chat-ia')); ?></td>
                            <td>
                                <?php
                                $disponibilidad_colores = [
                                    'disponible' => '#00a32a',
                                    'prestado' => '#dba617',
                                    'reservado' => '#2271b1',
                                    'no_disponible' => '#8c8f94',
                                ];
                                $color_disp = $disponibilidad_colores[$libro->disponibilidad] ?? '#646970';
                                ?>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; background: <?php echo $color_disp; ?>; color: #fff; font-size: 12px;">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $libro->disponibilidad))); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo number_format_i18n($libro->veces_prestado); ?>
                                <?php if ($libro->valoracion_media > 0): ?>
                                <br><small style="color: #dba617;">★ <?php echo number_format($libro->valoracion_media, 1); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=biblioteca-catalogo&action=editar&libro_id=' . $libro->id); ?>" class="button button-small">
                                    <?php _e('Editar', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <?php _e('No se encontraron libros con los filtros seleccionados.', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(__('%d elementos', 'flavor-chat-ia'), $total_items); ?></span>
                    <span class="pagination-links">
                        <?php
                        $paginas_enlaces = paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_paginas,
                            'current' => $pagina_actual,
                        ]);
                        echo $paginas_enlaces;
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Préstamos
     */
    public function render_admin_prestamos() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        // Procesar acciones
        if (isset($_POST['accion_prestamo']) && wp_verify_nonce($_POST['_wpnonce'], 'biblioteca_admin_prestamo')) {
            $accion = sanitize_text_field($_POST['accion_prestamo']);
            $prestamo_id = intval($_POST['prestamo_id']);

            if ($accion === 'aprobar' && $prestamo_id > 0) {
                $prestamo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_prestamos} WHERE id = %d", $prestamo_id));
                if ($prestamo && $prestamo->estado === 'pendiente') {
                    $settings = $this->get_settings();
                    $dias_prestamo = $settings['duracion_prestamo_dias'] ?? 30;
                    $fecha_devolucion = date('Y-m-d H:i:s', strtotime("+{$dias_prestamo} days"));

                    $wpdb->update($tabla_prestamos, [
                        'estado' => 'activo',
                        'fecha_prestamo' => current_time('mysql'),
                        'fecha_devolucion_prevista' => $fecha_devolucion,
                    ], ['id' => $prestamo_id]);

                    $wpdb->update($tabla_libros, ['disponibilidad' => 'prestado'], ['id' => $prestamo->libro_id]);
                    $wpdb->query($wpdb->prepare("UPDATE {$tabla_libros} SET veces_prestado = veces_prestado + 1 WHERE id = %d", $prestamo->libro_id));

                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Préstamo aprobado correctamente.', 'flavor-chat-ia') . '</p></div>';
                }
            } elseif ($accion === 'rechazar' && $prestamo_id > 0) {
                $wpdb->update($tabla_prestamos, ['estado' => 'rechazado'], ['id' => $prestamo_id]);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Préstamo rechazado.', 'flavor-chat-ia') . '</p></div>';
            } elseif ($accion === 'devolver' && $prestamo_id > 0) {
                $prestamo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_prestamos} WHERE id = %d", $prestamo_id));
                if ($prestamo) {
                    $wpdb->update($tabla_prestamos, [
                        'estado' => 'devuelto',
                        'fecha_devolucion_real' => current_time('mysql'),
                    ], ['id' => $prestamo_id]);
                    $wpdb->update($tabla_libros, ['disponibilidad' => 'disponible'], ['id' => $prestamo->libro_id]);
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Libro marcado como devuelto.', 'flavor-chat-ia') . '</p></div>';
                }
            }
        }

        // Filtros
        $filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

        $where_estado = '';
        if (!empty($filtro_estado)) {
            $where_estado = $wpdb->prepare(" AND p.estado = %s", $filtro_estado);
        }

        // Obtener préstamos
        $prestamos = $wpdb->get_results(
            "SELECT p.*, l.titulo, l.autor, l.portada_url,
                    prestatario.display_name as prestatario_nombre, prestatario.user_email as prestatario_email,
                    prestamista.display_name as prestamista_nombre
             FROM {$tabla_prestamos} p
             LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
             LEFT JOIN {$wpdb->users} prestatario ON p.prestatario_id = prestatario.ID
             LEFT JOIN {$wpdb->users} prestamista ON p.prestamista_id = prestamista.ID
             WHERE 1=1 {$where_estado}
             ORDER BY
                CASE p.estado
                    WHEN 'pendiente' THEN 1
                    WHEN 'retrasado' THEN 2
                    WHEN 'activo' THEN 3
                    ELSE 4
                END,
                p.fecha_solicitud DESC
             LIMIT 100"
        );

        // Contadores por estado
        $conteos = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad FROM {$tabla_prestamos} GROUP BY estado",
            OBJECT_K
        );

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Gestión de Préstamos', 'flavor-chat-ia')); ?>

            <!-- Tabs de estados -->
            <ul class="subsubsub" style="margin: 15px 0;">
                <li>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos'); ?>" class="<?php echo empty($filtro_estado) ? 'current' : ''; ?>">
                        <?php _e('Todos', 'flavor-chat-ia'); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos&estado=pendiente'); ?>" class="<?php echo $filtro_estado === 'pendiente' ? 'current' : ''; ?>">
                        <?php _e('Pendientes', 'flavor-chat-ia'); ?>
                        <?php if (!empty($conteos['pendiente'])): ?>
                        <span class="count">(<?php echo $conteos['pendiente']->cantidad; ?>)</span>
                        <?php endif; ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos&estado=activo'); ?>" class="<?php echo $filtro_estado === 'activo' ? 'current' : ''; ?>">
                        <?php _e('Activos', 'flavor-chat-ia'); ?>
                        <?php if (!empty($conteos['activo'])): ?>
                        <span class="count">(<?php echo $conteos['activo']->cantidad; ?>)</span>
                        <?php endif; ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos&estado=retrasado'); ?>" class="<?php echo $filtro_estado === 'retrasado' ? 'current' : ''; ?>">
                        <?php _e('Retrasados', 'flavor-chat-ia'); ?>
                        <?php if (!empty($conteos['retrasado'])): ?>
                        <span class="count">(<?php echo $conteos['retrasado']->cantidad; ?>)</span>
                        <?php endif; ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=biblioteca-prestamos&estado=devuelto'); ?>" class="<?php echo $filtro_estado === 'devuelto' ? 'current' : ''; ?>">
                        <?php _e('Devueltos', 'flavor-chat-ia'); ?>
                    </a>
                </li>
            </ul>

            <!-- Tabla de préstamos -->
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php _e('Libro', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Título / Autor', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Prestatario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Propietario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Fechas', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prestamos)): ?>
                        <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td>
                                <?php if ($prestamo->portada_url): ?>
                                <img src="<?php echo esc_url($prestamo->portada_url); ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                <span class="dashicons dashicons-book" style="font-size: 40px; color: #8c8f94;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($prestamo->titulo); ?></strong>
                                <br><span style="color: #646970;"><?php echo esc_html($prestamo->autor); ?></span>
                            </td>
                            <td>
                                <?php echo esc_html($prestamo->prestatario_nombre ?: __('Usuario', 'flavor-chat-ia')); ?>
                                <br><small style="color: #646970;"><?php echo esc_html($prestamo->prestatario_email); ?></small>
                            </td>
                            <td><?php echo esc_html($prestamo->prestamista_nombre ?: __('Desconocido', 'flavor-chat-ia')); ?></td>
                            <td>
                                <?php
                                $estados_colores = [
                                    'pendiente' => '#dba617',
                                    'activo' => '#00a32a',
                                    'devuelto' => '#646970',
                                    'retrasado' => '#d63638',
                                    'rechazado' => '#8c8f94',
                                ];
                                $color_estado = $estados_colores[$prestamo->estado] ?? '#646970';
                                ?>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; background: <?php echo $color_estado; ?>; color: #fff; font-size: 12px;">
                                    <?php echo esc_html(ucfirst($prestamo->estado)); ?>
                                </span>
                                <?php if ($prestamo->renovaciones > 0): ?>
                                <br><small><?php printf(__('%d renovaciones', 'flavor-chat-ia'), $prestamo->renovaciones); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?php _e('Solicitado:', 'flavor-chat-ia'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_solicitud)); ?>
                                    <?php if ($prestamo->fecha_devolucion_prevista): ?>
                                    <br><?php _e('Devolver:', 'flavor-chat-ia'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_devolucion_prevista)); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('biblioteca_admin_prestamo'); ?>
                                    <input type="hidden" name="prestamo_id" value="<?php echo $prestamo->id; ?>">

                                    <?php if ($prestamo->estado === 'pendiente'): ?>
                                    <button type="submit" name="accion_prestamo" value="aprobar" class="button button-small button-primary">
                                        <?php _e('Aprobar', 'flavor-chat-ia'); ?>
                                    </button>
                                    <button type="submit" name="accion_prestamo" value="rechazar" class="button button-small" onclick="return confirm('<?php esc_attr_e('¿Rechazar este préstamo?', 'flavor-chat-ia'); ?>')">
                                        <?php _e('Rechazar', 'flavor-chat-ia'); ?>
                                    </button>
                                    <?php elseif ($prestamo->estado === 'activo' || $prestamo->estado === 'retrasado'): ?>
                                    <button type="submit" name="accion_prestamo" value="devolver" class="button button-small">
                                        <?php _e('Marcar Devuelto', 'flavor-chat-ia'); ?>
                                    </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <?php _e('No hay préstamos que mostrar.', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Usuarios
     */
    public function render_admin_usuarios() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        // Obtener usuarios con actividad en biblioteca
        $usuarios = $wpdb->get_results(
            "SELECT
                u.ID,
                u.display_name,
                u.user_email,
                (SELECT COUNT(*) FROM {$tabla_libros} WHERE propietario_id = u.ID) as libros_aportados,
                (SELECT COUNT(*) FROM {$tabla_prestamos} WHERE prestatario_id = u.ID) as prestamos_solicitados,
                (SELECT COUNT(*) FROM {$tabla_prestamos} WHERE prestatario_id = u.ID AND estado = 'activo') as prestamos_activos,
                (SELECT COUNT(*) FROM {$tabla_prestamos} WHERE prestatario_id = u.ID AND estado = 'retrasado') as prestamos_retrasados,
                (SELECT MAX(fecha_solicitud) FROM {$tabla_prestamos} WHERE prestatario_id = u.ID) as ultima_actividad
             FROM {$wpdb->users} u
             WHERE u.ID IN (
                SELECT propietario_id FROM {$tabla_libros}
                UNION
                SELECT prestatario_id FROM {$tabla_prestamos}
             )
             ORDER BY ultima_actividad DESC
             LIMIT 100"
        );

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Usuarios de la Biblioteca', 'flavor-chat-ia')); ?>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Libros Aportados', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Préstamos Solicitados', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Activos Ahora', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Retrasados', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Última Actividad', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td>
                                <?php echo get_avatar($usuario->ID, 32); ?>
                                <strong style="margin-left: 8px;"><?php echo esc_html($usuario->display_name); ?></strong>
                                <br><small style="color: #646970; margin-left: 40px;"><?php echo esc_html($usuario->user_email); ?></small>
                            </td>
                            <td>
                                <span style="font-size: 18px; font-weight: 600;"><?php echo number_format_i18n($usuario->libros_aportados); ?></span>
                            </td>
                            <td><?php echo number_format_i18n($usuario->prestamos_solicitados); ?></td>
                            <td>
                                <?php if ($usuario->prestamos_activos > 0): ?>
                                <span style="color: #00a32a; font-weight: 600;"><?php echo number_format_i18n($usuario->prestamos_activos); ?></span>
                                <?php else: ?>
                                <span style="color: #646970;">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($usuario->prestamos_retrasados > 0): ?>
                                <span style="color: #d63638; font-weight: 600;"><?php echo number_format_i18n($usuario->prestamos_retrasados); ?></span>
                                <?php else: ?>
                                <span style="color: #646970;">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($usuario->ultima_actividad) {
                                    echo date_i18n(get_option('date_format'), strtotime($usuario->ultima_actividad));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <?php _e('No hay usuarios con actividad en la biblioteca.', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Configuración
     */
    public function render_admin_config() {
        // Guardar configuración
        if (isset($_POST['guardar_config']) && wp_verify_nonce($_POST['_wpnonce'], 'biblioteca_guardar_config')) {
            $opciones = [
                'duracion_prestamo_dias' => intval($_POST['duracion_prestamo_dias']),
                'renovaciones_maximas' => intval($_POST['renovaciones_maximas']),
                'permite_reservas' => isset($_POST['permite_reservas']) ? true : false,
                'sistema_puntos' => isset($_POST['sistema_puntos']) ? true : false,
                'puntos_por_prestamo' => intval($_POST['puntos_por_prestamo']),
                'puntos_por_devolucion' => intval($_POST['puntos_por_devolucion']),
                'notificar_vencimientos' => isset($_POST['notificar_vencimientos']) ? true : false,
                'dias_antes_notificar' => intval($_POST['dias_antes_notificar']),
                'permite_donaciones' => isset($_POST['permite_donaciones']) ? true : false,
                'permite_intercambios' => isset($_POST['permite_intercambios']) ? true : false,
                'requiere_verificacion_isbn' => isset($_POST['requiere_verificacion_isbn']) ? true : false,
            ];

            // Guardar cada opción
            foreach ($opciones as $clave => $valor) {
                update_option('flavor_biblioteca_' . $clave, $valor);
            }

            // También actualizar las settings del módulo (opción centralizada)
            $current_settings = $this->get_settings();
            $new_settings = array_merge($current_settings, $opciones);
            update_option('flavor_chat_ia_module_' . $this->id, $new_settings);

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
        }

        // Obtener configuración actual
        $settings = $this->get_settings();

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Configuración de Biblioteca', 'flavor-chat-ia')); ?>

            <form method="post" style="max-width: 800px;">
                <?php wp_nonce_field('biblioteca_guardar_config'); ?>

                <!-- Sección: Préstamos -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px;">
                        <span class="dashicons dashicons-book-alt"></span>
                        <?php _e('Configuración de Préstamos', 'flavor-chat-ia'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="duracion_prestamo_dias"><?php _e('Duración del préstamo', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="duracion_prestamo_dias" id="duracion_prestamo_dias"
                                       value="<?php echo esc_attr($settings['duracion_prestamo_dias'] ?? 30); ?>"
                                       min="1" max="365" class="small-text">
                                <span><?php _e('días', 'flavor-chat-ia'); ?></span>
                                <p class="description"><?php _e('Tiempo predeterminado que un usuario puede tener un libro.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="renovaciones_maximas"><?php _e('Renovaciones máximas', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="renovaciones_maximas" id="renovaciones_maximas"
                                       value="<?php echo esc_attr($settings['renovaciones_maximas'] ?? 2); ?>"
                                       min="0" max="10" class="small-text">
                                <p class="description"><?php _e('Número de veces que se puede renovar un préstamo.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Permitir reservas', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="permite_reservas" value="1"
                                           <?php checked($settings['permite_reservas'] ?? true); ?>>
                                    <?php _e('Los usuarios pueden reservar libros que están prestados.', 'flavor-chat-ia'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sección: Tipos de transacción -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Tipos de Transacción', 'flavor-chat-ia'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Permitir donaciones', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="permite_donaciones" value="1"
                                           <?php checked($settings['permite_donaciones'] ?? true); ?>>
                                    <?php _e('Los usuarios pueden donar libros a la comunidad.', 'flavor-chat-ia'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Permitir intercambios', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="permite_intercambios" value="1"
                                           <?php checked($settings['permite_intercambios'] ?? true); ?>>
                                    <?php _e('Los usuarios pueden intercambiar libros entre sí.', 'flavor-chat-ia'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sección: Gamificación -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px;">
                        <span class="dashicons dashicons-awards"></span>
                        <?php _e('Gamificación', 'flavor-chat-ia'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Sistema de puntos', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sistema_puntos" value="1"
                                           <?php checked($settings['sistema_puntos'] ?? true); ?>>
                                    <?php _e('Activar sistema de puntos por actividad en la biblioteca.', 'flavor-chat-ia'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="puntos_por_prestamo"><?php _e('Puntos por aportar libro', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="puntos_por_prestamo" id="puntos_por_prestamo"
                                       value="<?php echo esc_attr($settings['puntos_por_prestamo'] ?? 1); ?>"
                                       min="0" max="100" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="puntos_por_devolucion"><?php _e('Puntos por devolución', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="puntos_por_devolucion" id="puntos_por_devolucion"
                                       value="<?php echo esc_attr($settings['puntos_por_devolucion'] ?? 2); ?>"
                                       min="0" max="100" class="small-text">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sección: Notificaciones -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px;">
                        <span class="dashicons dashicons-bell"></span>
                        <?php _e('Notificaciones', 'flavor-chat-ia'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Notificar vencimientos', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notificar_vencimientos" value="1"
                                           <?php checked($settings['notificar_vencimientos'] ?? true); ?>>
                                    <?php _e('Enviar recordatorios antes de la fecha de devolución.', 'flavor-chat-ia'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dias_antes_notificar"><?php _e('Días de anticipación', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="dias_antes_notificar" id="dias_antes_notificar"
                                       value="<?php echo esc_attr($settings['dias_antes_notificar'] ?? 3); ?>"
                                       min="1" max="14" class="small-text">
                                <span><?php _e('días antes', 'flavor-chat-ia'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sección: Verificación -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #f0f0f1; padding-bottom: 10px;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Verificación', 'flavor-chat-ia'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Verificar ISBN', 'flavor-chat-ia'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="requiere_verificacion_isbn" value="1"
                                           <?php checked($settings['requiere_verificacion_isbn'] ?? false); ?>>
                                    <?php _e('Requerir ISBN válido al agregar libros.', 'flavor-chat-ia'); ?>
                                </label>
                                <p class="description"><?php _e('Si está activo, se validará el ISBN contra la base de datos de Open Library.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="guardar_config" class="button button-primary button-large">
                        <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-biblioteca-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Biblioteca_Dashboard_Tab')) {
                Flavor_Biblioteca_Dashboard_Tab::get_instance();
            }
        }
    }
}
