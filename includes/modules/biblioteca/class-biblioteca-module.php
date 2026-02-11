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
        add_action('init', [$this, 'maybe_create_tables']);
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

}
