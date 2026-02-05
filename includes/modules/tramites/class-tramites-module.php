<?php
/**
 * Modulo de Tramites para Chat IA - Sistema Completo
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Tramites - Gestion completa de tramites administrativos
 */
class Flavor_Chat_Tramites_Module extends Flavor_Chat_Module_Base {

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var array Nombres de tablas */
    private $tabla_tipos_tramite;
    private $tabla_expedientes;
    private $tabla_documentos;
    private $tabla_estados;
    private $tabla_campos_formulario;
    private $tabla_historial;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->id = 'tramites';
        $this->name = __('Tramites y Gestiones', 'flavor-chat-ia');
        $this->description = __('Sistema completo de gestion de tramites administrativos online.', 'flavor-chat-ia');

        $this->tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
        $this->tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $this->tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';
        $this->tabla_estados = $wpdb->prefix . 'flavor_estados_tramite';
        $this->tabla_campos_formulario = $wpdb->prefix . 'flavor_campos_formulario';
        $this->tabla_historial = $wpdb->prefix . 'flavor_historial_expediente';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_tipos_tramite);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Tramites no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_aprobacion' => true,
            'permite_tramites_online' => true,
            'permite_tramites_presencial' => true,
            'plazo_resolucion_maximo_dias' => 30,
            'notificar_cambio_estado' => true,
            'notificar_por_email' => true,
            'permite_cancelacion' => true,
            'dias_limite_cancelacion' => 5,
            'tamanio_maximo_archivo_mb' => 10,
            'tipos_archivo_permitidos' => 'pdf,jpg,jpeg,png,doc,docx',
            'max_archivos_por_expediente' => 20,
            'mostrar_timeline_publico' => true,
            'auto_asignar_numero_expediente' => true,
            'prefijo_expediente' => 'EXP',
            'requiere_login' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_tramites_action', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_flavor_tramites_action', [$this, 'handle_ajax_request']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('catalogo_tramites', [$this, 'shortcode_catalogo_tramites']);
        add_shortcode('iniciar_tramite', [$this, 'shortcode_iniciar_tramite']);
        add_shortcode('mis_expedientes', [$this, 'shortcode_mis_expedientes']);
        add_shortcode('estado_expediente', [$this, 'shortcode_estado_expediente']);
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets() {
        if ($this->should_load_assets()) {
            $modulo_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'flavor-tramites-css',
                $modulo_url . 'assets/css/tramites.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'flavor-tramites-js',
                $modulo_url . 'assets/js/tramites.js',
                ['jquery'],
                self::VERSION,
                true
            );

            wp_localize_script('flavor-tramites-js', 'flavorTramitesConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor-tramites/v1/'),
                'nonce' => wp_create_nonce('flavor_tramites_nonce'),
                'maxFileSize' => $this->get_setting('tamanio_maximo_archivo_mb') * 1024 * 1024,
                'allowedTypes' => explode(',', $this->get_setting('tipos_archivo_permitidos')),
                'maxFiles' => $this->get_setting('max_archivos_por_expediente'),
                'i18n' => [
                    'uploading' => __('Subiendo...', 'flavor-chat-ia'),
                    'uploadError' => __('Error al subir el archivo', 'flavor-chat-ia'),
                    'fileTooBig' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                    'invalidType' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
                    'confirmDelete' => __('¿Estas seguro de eliminar este documento?', 'flavor-chat-ia'),
                    'confirmSubmit' => __('¿Deseas enviar el tramite?', 'flavor-chat-ia'),
                    'required' => __('Este campo es obligatorio', 'flavor-chat-ia'),
                    'invalidEmail' => __('Email no valido', 'flavor-chat-ia'),
                    'invalidPhone' => __('Telefono no valido', 'flavor-chat-ia'),
                    'success' => __('Operacion completada', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                ],
            ]);
        }
    }

    /**
     * Determinar si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes_modulo = ['catalogo_tramites', 'iniciar_tramite', 'mis_expedientes', 'estado_expediente'];
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crear tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_tipos_tramite)) {
            $this->create_tables();
        }
    }

    /**
     * Crear todas las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla tipos_tramite
        $sql_tipos_tramite = "CREATE TABLE IF NOT EXISTS {$this->tabla_tipos_tramite} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text,
            descripcion_corta varchar(500),
            categoria varchar(100),
            subcategoria varchar(100),
            icono varchar(100) DEFAULT 'dashicons-clipboard',
            color varchar(20) DEFAULT '#0073aa',
            requisitos longtext,
            documentos_requeridos longtext,
            plazo_resolucion_dias int(11) DEFAULT 30,
            tasa decimal(10,2) DEFAULT 0.00,
            tasa_urgente decimal(10,2) DEFAULT 0.00,
            permite_urgente tinyint(1) DEFAULT 0,
            requiere_cita tinyint(1) DEFAULT 0,
            requiere_firma_digital tinyint(1) DEFAULT 0,
            permite_representante tinyint(1) DEFAULT 1,
            departamento_responsable varchar(255),
            email_notificacion varchar(255),
            plantilla_email_inicio text,
            plantilla_email_resolucion text,
            orden int(11) DEFAULT 0,
            estado enum('activo','inactivo','borrador') DEFAULT 'activo',
            visibilidad enum('publico','registrados','privado') DEFAULT 'publico',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            creado_por bigint(20) unsigned,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY orden (orden)
        ) $charset_collate;";
        dbDelta($sql_tipos_tramite);

        // Tabla expedientes
        $sql_expedientes = "CREATE TABLE IF NOT EXISTS {$this->tabla_expedientes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_expediente varchar(50) NOT NULL,
            tipo_tramite_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned,
            session_id varchar(100),
            nombre_solicitante varchar(255) NOT NULL,
            email_solicitante varchar(255) NOT NULL,
            telefono_solicitante varchar(50),
            dni_solicitante varchar(20),
            direccion_solicitante text,
            es_representante tinyint(1) DEFAULT 0,
            nombre_representado varchar(255),
            dni_representado varchar(20),
            datos_formulario longtext,
            notas_solicitante text,
            notas_internas text,
            estado_actual varchar(50) DEFAULT 'borrador',
            prioridad enum('baja','normal','alta','urgente') DEFAULT 'normal',
            canal_entrada enum('online','presencial','telefono','email') DEFAULT 'online',
            asignado_a bigint(20) unsigned,
            departamento varchar(255),
            tasa_pagada decimal(10,2) DEFAULT 0.00,
            referencia_pago varchar(100),
            fecha_pago datetime,
            fecha_inicio datetime,
            fecha_limite datetime,
            fecha_resolucion datetime,
            resolucion enum('pendiente','favorable','desfavorable','desistimiento','caducidad') DEFAULT 'pendiente',
            motivo_resolucion text,
            puntuacion_satisfaccion int(11),
            comentario_satisfaccion text,
            ip_creacion varchar(45),
            user_agent_creacion text,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_expediente (numero_expediente),
            KEY tipo_tramite_id (tipo_tramite_id),
            KEY user_id (user_id),
            KEY estado_actual (estado_actual),
            KEY prioridad (prioridad),
            KEY fecha_creacion (fecha_creacion),
            KEY asignado_a (asignado_a)
        ) $charset_collate;";
        dbDelta($sql_expedientes);

        // Tabla documentos_expediente
        $sql_documentos = "CREATE TABLE IF NOT EXISTS {$this->tabla_documentos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            expediente_id bigint(20) unsigned NOT NULL,
            tipo_documento varchar(100),
            nombre_original varchar(255) NOT NULL,
            nombre_archivo varchar(255) NOT NULL,
            ruta_archivo varchar(500) NOT NULL,
            mime_type varchar(100),
            tamanio_bytes bigint(20) unsigned DEFAULT 0,
            hash_archivo varchar(64),
            es_obligatorio tinyint(1) DEFAULT 0,
            validado tinyint(1) DEFAULT 0,
            validado_por bigint(20) unsigned,
            fecha_validacion datetime,
            notas_validacion text,
            origen enum('solicitante','administracion','generado') DEFAULT 'solicitante',
            visible_solicitante tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            fecha_subida datetime DEFAULT CURRENT_TIMESTAMP,
            subido_por bigint(20) unsigned,
            PRIMARY KEY (id),
            KEY expediente_id (expediente_id),
            KEY tipo_documento (tipo_documento),
            KEY validado (validado)
        ) $charset_collate;";
        dbDelta($sql_documentos);

        // Tabla estados_tramite
        $sql_estados = "CREATE TABLE IF NOT EXISTS {$this->tabla_estados} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text,
            color varchar(20) DEFAULT '#666666',
            icono varchar(100) DEFAULT 'dashicons-marker',
            es_inicial tinyint(1) DEFAULT 0,
            es_final tinyint(1) DEFAULT 0,
            permite_edicion tinyint(1) DEFAULT 0,
            permite_documentos tinyint(1) DEFAULT 1,
            notifica_solicitante tinyint(1) DEFAULT 1,
            plantilla_notificacion text,
            orden int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY es_inicial (es_inicial),
            KEY es_final (es_final),
            KEY orden (orden)
        ) $charset_collate;";
        dbDelta($sql_estados);

        // Tabla campos_formulario
        $sql_campos = "CREATE TABLE IF NOT EXISTS {$this->tabla_campos_formulario} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo_tramite_id bigint(20) unsigned NOT NULL,
            nombre_campo varchar(100) NOT NULL,
            etiqueta varchar(255) NOT NULL,
            tipo_campo enum('text','textarea','email','tel','number','date','datetime','select','radio','checkbox','file','hidden','dni','iban','cp') DEFAULT 'text',
            placeholder varchar(255),
            valor_defecto text,
            opciones longtext,
            validacion varchar(255),
            patron_validacion varchar(255),
            mensaje_error varchar(255),
            es_obligatorio tinyint(1) DEFAULT 0,
            es_readonly tinyint(1) DEFAULT 0,
            mostrar_en_resumen tinyint(1) DEFAULT 1,
            grupo varchar(100),
            ancho enum('full','half','third','quarter') DEFAULT 'full',
            clase_css varchar(255),
            ayuda text,
            condicion_visible text,
            orden int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo_tramite_id (tipo_tramite_id),
            KEY nombre_campo (nombre_campo),
            KEY grupo (grupo),
            KEY orden (orden)
        ) $charset_collate;";
        dbDelta($sql_campos);

        // Tabla historial_expediente
        $sql_historial = "CREATE TABLE IF NOT EXISTS {$this->tabla_historial} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            expediente_id bigint(20) unsigned NOT NULL,
            tipo_evento enum('creacion','cambio_estado','documento_subido','documento_validado','documento_rechazado','nota_agregada','asignacion','resolucion','notificacion','otro') DEFAULT 'otro',
            estado_anterior varchar(50),
            estado_nuevo varchar(50),
            descripcion text NOT NULL,
            datos_adicionales longtext,
            es_publico tinyint(1) DEFAULT 1,
            usuario_id bigint(20) unsigned,
            nombre_usuario varchar(255),
            ip_origen varchar(45),
            fecha_evento datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY expediente_id (expediente_id),
            KEY tipo_evento (tipo_evento),
            KEY fecha_evento (fecha_evento),
            KEY es_publico (es_publico)
        ) $charset_collate;";
        dbDelta($sql_historial);

        $this->insert_default_data();
    }

    /**
     * Insertar datos por defecto
     */
    private function insert_default_data() {
        global $wpdb;

        // Estados por defecto
        $estados_existentes = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_estados}");
        if ($estados_existentes == 0) {
            $estados_defecto = [
                ['borrador', 'Borrador', 'Expediente en preparacion', '#9e9e9e', 'dashicons-edit', 1, 0, 1, 1, 0, 1],
                ['pendiente', 'Pendiente', 'Pendiente de revision', '#ff9800', 'dashicons-clock', 0, 0, 0, 1, 1, 2],
                ['en_revision', 'En Revision', 'Siendo revisado por el departamento', '#2196f3', 'dashicons-visibility', 0, 0, 0, 1, 1, 3],
                ['subsanacion', 'Pendiente Subsanacion', 'Requiere documentacion adicional', '#f44336', 'dashicons-warning', 0, 0, 1, 1, 1, 4],
                ['en_tramite', 'En Tramite', 'Tramitandose', '#673ab7', 'dashicons-admin-generic', 0, 0, 0, 0, 1, 5],
                ['resuelto_favorable', 'Resuelto Favorable', 'Tramite aprobado', '#4caf50', 'dashicons-yes-alt', 0, 1, 0, 0, 1, 6],
                ['resuelto_desfavorable', 'Resuelto Desfavorable', 'Tramite denegado', '#f44336', 'dashicons-dismiss', 0, 1, 0, 0, 1, 7],
                ['archivado', 'Archivado', 'Expediente archivado', '#607d8b', 'dashicons-archive', 0, 1, 0, 0, 0, 8],
            ];

            foreach ($estados_defecto as $estado) {
                $wpdb->insert($this->tabla_estados, [
                    'codigo' => $estado[0],
                    'nombre' => $estado[1],
                    'descripcion' => $estado[2],
                    'color' => $estado[3],
                    'icono' => $estado[4],
                    'es_inicial' => $estado[5],
                    'es_final' => $estado[6],
                    'permite_edicion' => $estado[7],
                    'permite_documentos' => $estado[8],
                    'notifica_solicitante' => $estado[9],
                    'orden' => $estado[10],
                ]);
            }
        }
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-tramites/v1';

        register_rest_route($namespace, '/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tipos_tramite'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/tipos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tipo_tramite'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/expedientes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_expedientes'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        register_rest_route($namespace, '/expedientes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_expediente'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_expediente'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'rest_update_expediente'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)/documentos', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_upload_documento'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/(?P<id>\d+)/historial', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_historial'],
            'permission_callback' => [$this, 'check_expediente_permission'],
        ]);

        register_rest_route($namespace, '/expedientes/consulta/(?P<numero>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_consulta_expediente'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/estados', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estados'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Verificar permiso de usuario
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }

    /**
     * Verificar permiso sobre expediente
     */
    public function check_expediente_permission($request) {
        $expediente_id = $request->get_param('id');
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $usuario_actual_id = get_current_user_id();
        if ($usuario_actual_id && $expediente->user_id == $usuario_actual_id) {
            return true;
        }

        $session_id = isset($_COOKIE['flavor_tramites_session']) ? sanitize_text_field($_COOKIE['flavor_tramites_session']) : '';
        if ($session_id && $expediente->session_id === $session_id) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // REST API HANDLERS
    // =========================================================================

    /**
     * REST: Obtener tipos de tramite
     */
    public function rest_get_tipos_tramite($request) {
        global $wpdb;

        $categoria = $request->get_param('categoria');
        $busqueda = $request->get_param('busqueda');

        $where = ["estado = 'activo'"];
        $valores_preparar = [];

        if (!is_user_logged_in()) {
            $where[] = "visibilidad = 'publico'";
        } elseif (!current_user_can('manage_options')) {
            $where[] = "visibilidad IN ('publico', 'registrados')";
        }

        if ($categoria) {
            $where[] = "categoria = %s";
            $valores_preparar[] = sanitize_text_field($categoria);
        }

        if ($busqueda) {
            $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $termino_busqueda = '%' . $wpdb->esc_like(sanitize_text_field($busqueda)) . '%';
            $valores_preparar[] = $termino_busqueda;
            $valores_preparar[] = $termino_busqueda;
        }

        $sql = "SELECT id, codigo, nombre, descripcion_corta, categoria, subcategoria, icono, color, plazo_resolucion_dias, tasa, permite_urgente, requiere_cita
                FROM {$this->tabla_tipos_tramite}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY orden ASC, nombre ASC";

        if (!empty($valores_preparar)) {
            $tipos_tramite = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparar));
        } else {
            $tipos_tramite = $wpdb->get_results($sql);
        }

        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$this->tabla_tipos_tramite} WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

        return rest_ensure_response([
            'success' => true,
            'tipos' => $tipos_tramite,
            'categorias' => $categorias,
            'total' => count($tipos_tramite),
        ]);
    }

    /**
     * REST: Obtener tipo de tramite individual
     */
    public function rest_get_tipo_tramite($request) {
        global $wpdb;

        $tipo_id = absint($request->get_param('id'));

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_tipos_tramite} WHERE id = %d AND estado = 'activo'",
            $tipo_id
        ));

        if (!$tipo) {
            return new WP_Error('not_found', 'Tipo de tramite no encontrado', ['status' => 404]);
        }

        $campos_formulario = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_campos_formulario} WHERE tipo_tramite_id = %d AND activo = 1 ORDER BY orden ASC",
            $tipo_id
        ));

        foreach ($campos_formulario as &$campo) {
            if ($campo->opciones) {
                $campo->opciones = json_decode($campo->opciones, true);
            }
            if ($campo->condicion_visible) {
                $campo->condicion_visible = json_decode($campo->condicion_visible, true);
            }
        }

        $tipo->requisitos = $tipo->requisitos ? json_decode($tipo->requisitos, true) : [];
        $tipo->documentos_requeridos = $tipo->documentos_requeridos ? json_decode($tipo->documentos_requeridos, true) : [];
        $tipo->campos_formulario = $campos_formulario;

        return rest_ensure_response([
            'success' => true,
            'tipo' => $tipo,
        ]);
    }

    /**
     * REST: Obtener expedientes del usuario
     */
    public function rest_get_expedientes($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado');
        $pagina = max(1, intval($request->get_param('pagina') ?: 1));
        $por_pagina = min(50, max(1, intval($request->get_param('por_pagina') ?: 10)));
        $offset = ($pagina - 1) * $por_pagina;

        $where = ['e.user_id = %d'];
        $valores = [$usuario_id];

        if ($estado) {
            $where[] = 'e.estado_actual = %s';
            $valores[] = sanitize_text_field($estado);
        }

        $sql_total = "SELECT COUNT(*) FROM {$this->tabla_expedientes} e WHERE " . implode(' AND ', $where);
        $total = $wpdb->get_var($wpdb->prepare($sql_total, ...$valores));

        $sql = "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                       es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono
                FROM {$this->tabla_expedientes} e
                LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
                LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.fecha_creacion DESC
                LIMIT %d OFFSET %d";

        $valores[] = $por_pagina;
        $valores[] = $offset;

        $expedientes = $wpdb->get_results($wpdb->prepare($sql, ...$valores));

        return rest_ensure_response([
            'success' => true,
            'expedientes' => $expedientes,
            'total' => intval($total),
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * REST: Crear expediente
     */
    public function rest_create_expediente($request) {
        global $wpdb;

        $tipo_tramite_id = absint($request->get_param('tipo_tramite_id'));
        $datos_formulario = $request->get_param('datos_formulario');
        $es_borrador = $request->get_param('borrador') === true;

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_tipos_tramite} WHERE id = %d AND estado = 'activo'",
            $tipo_tramite_id
        ));

        if (!$tipo) {
            return new WP_Error('invalid_type', 'Tipo de tramite no valido', ['status' => 400]);
        }

        if ($this->get_setting('requiere_login') && !is_user_logged_in()) {
            return new WP_Error('auth_required', 'Debes iniciar sesion', ['status' => 401]);
        }

        $nombre_solicitante = sanitize_text_field($request->get_param('nombre_solicitante'));
        $email_solicitante = sanitize_email($request->get_param('email_solicitante'));
        $telefono_solicitante = sanitize_text_field($request->get_param('telefono_solicitante'));
        $dni_solicitante = sanitize_text_field($request->get_param('dni_solicitante'));
        $direccion_solicitante = sanitize_textarea_field($request->get_param('direccion_solicitante'));

        if (!$es_borrador) {
            if (empty($nombre_solicitante) || empty($email_solicitante)) {
                return new WP_Error('missing_data', 'Nombre y email son obligatorios', ['status' => 400]);
            }

            if (!is_email($email_solicitante)) {
                return new WP_Error('invalid_email', 'Email no valido', ['status' => 400]);
            }

            $errores_validacion = $this->validar_campos_formulario($tipo_tramite_id, $datos_formulario);
            if (!empty($errores_validacion)) {
                return new WP_Error('validation_error', 'Errores de validacion', ['status' => 400, 'errors' => $errores_validacion]);
            }
        }

        $numero_expediente = $this->generar_numero_expediente();
        $usuario_id = get_current_user_id();
        $session_id = $this->get_or_create_session_id();

        $fecha_limite = null;
        if ($tipo->plazo_resolucion_dias) {
            $fecha_limite = date('Y-m-d H:i:s', strtotime('+' . $tipo->plazo_resolucion_dias . ' days'));
        }

        $datos_insercion = [
            'numero_expediente' => $numero_expediente,
            'tipo_tramite_id' => $tipo_tramite_id,
            'user_id' => $usuario_id ?: null,
            'session_id' => $session_id,
            'nombre_solicitante' => $nombre_solicitante,
            'email_solicitante' => $email_solicitante,
            'telefono_solicitante' => $telefono_solicitante,
            'dni_solicitante' => $dni_solicitante,
            'direccion_solicitante' => $direccion_solicitante,
            'datos_formulario' => $datos_formulario ? wp_json_encode($datos_formulario) : null,
            'notas_solicitante' => sanitize_textarea_field($request->get_param('notas')),
            'estado_actual' => $es_borrador ? 'borrador' : 'pendiente',
            'prioridad' => $request->get_param('urgente') ? 'urgente' : 'normal',
            'canal_entrada' => 'online',
            'departamento' => $tipo->departamento_responsable,
            'fecha_inicio' => $es_borrador ? null : current_time('mysql'),
            'fecha_limite' => $es_borrador ? null : $fecha_limite,
            'ip_creacion' => $this->get_client_ip(),
            'user_agent_creacion' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'fecha_creacion' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($this->tabla_expedientes, $datos_insercion);

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al crear expediente', ['status' => 500]);
        }

        $expediente_id = $wpdb->insert_id;

        $this->registrar_historial($expediente_id, 'creacion', null, $es_borrador ? 'borrador' : 'pendiente', 'Expediente creado');

        if (!$es_borrador && $this->get_setting('notificar_por_email')) {
            $this->enviar_notificacion_inicio($expediente_id);
        }

        return rest_ensure_response([
            'success' => true,
            'mensaje' => $es_borrador ? 'Borrador guardado' : 'Expediente creado correctamente',
            'expediente' => [
                'id' => $expediente_id,
                'numero_expediente' => $numero_expediente,
                'estado' => $es_borrador ? 'borrador' : 'pendiente',
            ],
        ]);
    }

    /**
     * REST: Obtener expediente
     */
    public function rest_get_expediente($request) {
        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente_completo($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'expediente' => $expediente,
        ]);
    }

    /**
     * REST: Actualizar expediente
     */
    public function rest_update_expediente($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $estado_info = $this->get_estado($expediente->estado_actual);
        if (!$estado_info || !$estado_info->permite_edicion) {
            return new WP_Error('not_editable', 'El expediente no se puede editar en su estado actual', ['status' => 403]);
        }

        $datos_actualizacion = [];
        $campos_permitidos = ['nombre_solicitante', 'email_solicitante', 'telefono_solicitante', 'dni_solicitante', 'direccion_solicitante', 'notas_solicitante'];

        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos_actualizacion[$campo] = sanitize_text_field($valor);
            }
        }

        $datos_formulario = $request->get_param('datos_formulario');
        if ($datos_formulario !== null) {
            $datos_actualizacion['datos_formulario'] = wp_json_encode($datos_formulario);
        }

        $enviar = $request->get_param('enviar');
        if ($enviar && $expediente->estado_actual === 'borrador') {
            $errores = $this->validar_campos_formulario($expediente->tipo_tramite_id, $datos_formulario ?: json_decode($expediente->datos_formulario, true));
            if (!empty($errores)) {
                return new WP_Error('validation_error', 'Errores de validacion', ['status' => 400, 'errors' => $errores]);
            }

            $datos_actualizacion['estado_actual'] = 'pendiente';
            $datos_actualizacion['fecha_inicio'] = current_time('mysql');

            $tipo = $this->get_tipo_tramite($expediente->tipo_tramite_id);
            if ($tipo && $tipo->plazo_resolucion_dias) {
                $datos_actualizacion['fecha_limite'] = date('Y-m-d H:i:s', strtotime('+' . $tipo->plazo_resolucion_dias . ' days'));
            }
        }

        if (!empty($datos_actualizacion)) {
            $datos_actualizacion['fecha_modificacion'] = current_time('mysql');

            $wpdb->update($this->tabla_expedientes, $datos_actualizacion, ['id' => $expediente_id]);

            if (isset($datos_actualizacion['estado_actual'])) {
                $this->registrar_historial($expediente_id, 'cambio_estado', $expediente->estado_actual, $datos_actualizacion['estado_actual'], 'Expediente enviado');

                if ($this->get_setting('notificar_por_email')) {
                    $this->enviar_notificacion_inicio($expediente_id);
                }
            } else {
                $this->registrar_historial($expediente_id, 'otro', null, null, 'Datos actualizados');
            }
        }

        return rest_ensure_response([
            'success' => true,
            'mensaje' => 'Expediente actualizado',
        ]);
    }

    /**
     * REST: Subir documento
     */
    public function rest_upload_documento($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));
        $expediente = $this->get_expediente($expediente_id);

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $estado_info = $this->get_estado($expediente->estado_actual);
        if (!$estado_info || !$estado_info->permite_documentos) {
            return new WP_Error('not_allowed', 'No se pueden subir documentos en este estado', ['status' => 403]);
        }

        $documentos_actuales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_documentos} WHERE expediente_id = %d",
            $expediente_id
        ));

        $max_archivos = $this->get_setting('max_archivos_por_expediente');
        if ($documentos_actuales >= $max_archivos) {
            return new WP_Error('max_files', "Maximo de {$max_archivos} archivos alcanzado", ['status' => 400]);
        }

        if (empty($_FILES['documento'])) {
            return new WP_Error('no_file', 'No se recibio ningun archivo', ['status' => 400]);
        }

        $archivo = $_FILES['documento'];
        $tipos_permitidos = array_map('trim', explode(',', $this->get_setting('tipos_archivo_permitidos')));
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $tipos_permitidos)) {
            return new WP_Error('invalid_type', 'Tipo de archivo no permitido', ['status' => 400]);
        }

        $tamanio_maximo = $this->get_setting('tamanio_maximo_archivo_mb') * 1024 * 1024;
        if ($archivo['size'] > $tamanio_maximo) {
            return new WP_Error('file_too_big', 'El archivo excede el tamanio maximo', ['status' => 400]);
        }

        $directorio_upload = wp_upload_dir();
        $directorio_expedientes = $directorio_upload['basedir'] . '/expedientes/' . $expediente->numero_expediente;

        if (!file_exists($directorio_expedientes)) {
            wp_mkdir_p($directorio_expedientes);
        }

        $nombre_archivo = wp_unique_filename($directorio_expedientes, sanitize_file_name($archivo['name']));
        $ruta_destino = $directorio_expedientes . '/' . $nombre_archivo;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            return new WP_Error('upload_failed', 'Error al subir el archivo', ['status' => 500]);
        }

        $hash_archivo = hash_file('sha256', $ruta_destino);
        $tipo_documento = sanitize_text_field($request->get_param('tipo_documento'));

        $wpdb->insert($this->tabla_documentos, [
            'expediente_id' => $expediente_id,
            'tipo_documento' => $tipo_documento,
            'nombre_original' => $archivo['name'],
            'nombre_archivo' => $nombre_archivo,
            'ruta_archivo' => str_replace($directorio_upload['basedir'], '', $ruta_destino),
            'mime_type' => $archivo['type'],
            'tamanio_bytes' => $archivo['size'],
            'hash_archivo' => $hash_archivo,
            'origen' => 'solicitante',
            'fecha_subida' => current_time('mysql'),
            'subido_por' => get_current_user_id() ?: null,
        ]);

        $documento_id = $wpdb->insert_id;

        $this->registrar_historial($expediente_id, 'documento_subido', null, null, "Documento subido: {$archivo['name']}");

        return rest_ensure_response([
            'success' => true,
            'mensaje' => 'Documento subido correctamente',
            'documento' => [
                'id' => $documento_id,
                'nombre' => $archivo['name'],
                'tamanio' => $archivo['size'],
            ],
        ]);
    }

    /**
     * REST: Obtener historial
     */
    public function rest_get_historial($request) {
        global $wpdb;

        $expediente_id = absint($request->get_param('id'));

        $es_admin = current_user_can('manage_options');
        $where_publico = $es_admin ? '' : 'AND es_publico = 1';

        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_historial}
             WHERE expediente_id = %d {$where_publico}
             ORDER BY fecha_evento DESC",
            $expediente_id
        ));

        return rest_ensure_response([
            'success' => true,
            'historial' => $historial,
        ]);
    }

    /**
     * REST: Consulta publica de expediente
     */
    public function rest_consulta_expediente($request) {
        global $wpdb;

        $numero = sanitize_text_field($request->get_param('numero'));

        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT e.numero_expediente, e.estado_actual, e.fecha_creacion, e.fecha_limite,
                    t.nombre as tipo_nombre, es.nombre as estado_nombre, es.color as estado_color
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.numero_expediente = %s",
            $numero
        ));

        if (!$expediente) {
            return new WP_Error('not_found', 'Expediente no encontrado', ['status' => 404]);
        }

        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_evento, descripcion, fecha_evento
             FROM {$this->tabla_historial}
             WHERE expediente_id = (SELECT id FROM {$this->tabla_expedientes} WHERE numero_expediente = %s)
             AND es_publico = 1
             ORDER BY fecha_evento DESC
             LIMIT 10",
            $numero
        ));

        return rest_ensure_response([
            'success' => true,
            'expediente' => $expediente,
            'historial' => $historial,
        ]);
    }

    /**
     * REST: Obtener estados
     */
    public function rest_get_estados($request) {
        global $wpdb;

        $estados = $wpdb->get_results(
            "SELECT codigo, nombre, descripcion, color, icono, es_inicial, es_final
             FROM {$this->tabla_estados}
             WHERE activo = 1
             ORDER BY orden ASC"
        );

        return rest_ensure_response([
            'success' => true,
            'estados' => $estados,
        ]);
    }

    // =========================================================================
    // AJAX HANDLER
    // =========================================================================

    /**
     * Manejar peticiones AJAX
     */
    public function handle_ajax_request() {
        check_ajax_referer('flavor_tramites_nonce', 'nonce');

        $accion = isset($_POST['accion_tramites']) ? sanitize_text_field($_POST['accion_tramites']) : '';

        $acciones_permitidas = [
            'get_tipo_tramite',
            'crear_expediente',
            'actualizar_expediente',
            'subir_documento',
            'eliminar_documento',
            'obtener_expediente',
            'listar_expedientes',
            'consultar_estado',
            'cancelar_expediente',
        ];

        if (!in_array($accion, $acciones_permitidas)) {
            wp_send_json_error(['mensaje' => 'Accion no permitida']);
        }

        $metodo = 'ajax_' . $accion;
        if (method_exists($this, $metodo)) {
            $resultado = $this->$metodo();
            if (isset($resultado['success']) && $resultado['success']) {
                wp_send_json_success($resultado);
            } else {
                wp_send_json_error($resultado);
            }
        } else {
            wp_send_json_error(['mensaje' => 'Metodo no implementado']);
        }
    }

    /**
     * AJAX: Obtener tipo de tramite
     */
    private function ajax_get_tipo_tramite() {
        $tipo_id = isset($_POST['tipo_id']) ? absint($_POST['tipo_id']) : 0;

        if (!$tipo_id) {
            return ['success' => false, 'mensaje' => 'ID de tipo requerido'];
        }

        $tipo = $this->get_tipo_tramite_completo($tipo_id);

        if (!$tipo) {
            return ['success' => false, 'mensaje' => 'Tipo no encontrado'];
        }

        return ['success' => true, 'tipo' => $tipo];
    }

    /**
     * AJAX: Crear expediente
     */
    private function ajax_crear_expediente() {
        $request = new WP_REST_Request('POST');
        $request->set_param('tipo_tramite_id', isset($_POST['tipo_tramite_id']) ? absint($_POST['tipo_tramite_id']) : 0);
        $request->set_param('nombre_solicitante', isset($_POST['nombre_solicitante']) ? sanitize_text_field($_POST['nombre_solicitante']) : '');
        $request->set_param('email_solicitante', isset($_POST['email_solicitante']) ? sanitize_email($_POST['email_solicitante']) : '');
        $request->set_param('telefono_solicitante', isset($_POST['telefono_solicitante']) ? sanitize_text_field($_POST['telefono_solicitante']) : '');
        $request->set_param('dni_solicitante', isset($_POST['dni_solicitante']) ? sanitize_text_field($_POST['dni_solicitante']) : '');
        $request->set_param('direccion_solicitante', isset($_POST['direccion_solicitante']) ? sanitize_textarea_field($_POST['direccion_solicitante']) : '');
        $request->set_param('datos_formulario', isset($_POST['datos_formulario']) ? json_decode(stripslashes($_POST['datos_formulario']), true) : []);
        $request->set_param('notas', isset($_POST['notas']) ? sanitize_textarea_field($_POST['notas']) : '');
        $request->set_param('borrador', isset($_POST['borrador']) && $_POST['borrador'] === 'true');
        $request->set_param('urgente', isset($_POST['urgente']) && $_POST['urgente'] === 'true');

        $response = $this->rest_create_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'mensaje' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    /**
     * AJAX: Listar expedientes
     */
    private function ajax_listar_expedientes() {
        if (!is_user_logged_in()) {
            return ['success' => false, 'mensaje' => 'Debes iniciar sesion'];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('estado', isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '');
        $request->set_param('pagina', isset($_POST['pagina']) ? absint($_POST['pagina']) : 1);
        $request->set_param('por_pagina', isset($_POST['por_pagina']) ? absint($_POST['por_pagina']) : 10);

        $response = $this->rest_get_expedientes($request);

        return $response->get_data();
    }

    /**
     * AJAX: Consultar estado
     */
    private function ajax_consultar_estado() {
        $numero = isset($_POST['numero_expediente']) ? sanitize_text_field($_POST['numero_expediente']) : '';

        if (empty($numero)) {
            return ['success' => false, 'mensaje' => 'Numero de expediente requerido'];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('numero', $numero);

        $response = $this->rest_consulta_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'mensaje' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Catalogo de tramites
     */
    public function shortcode_catalogo_tramites($atts) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'columnas' => 3,
            'mostrar_filtros' => 'true',
            'mostrar_buscador' => 'true',
            'limite' => 12,
        ], $atts);

        global $wpdb;

        $where = ["estado = 'activo'"];
        $valores = [];

        if (!empty($atributos['categoria'])) {
            $where[] = "categoria = %s";
            $valores[] = sanitize_text_field($atributos['categoria']);
        }

        if (!is_user_logged_in()) {
            $where[] = "visibilidad = 'publico'";
        }

        $sql = "SELECT * FROM {$this->tabla_tipos_tramite} WHERE " . implode(' AND ', $where) . " ORDER BY orden ASC, nombre ASC LIMIT %d";
        $valores[] = absint($atributos['limite']);

        $tipos = $wpdb->get_results($wpdb->prepare($sql, ...$valores));

        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$this->tabla_tipos_tramite} WHERE estado = 'activo' AND categoria IS NOT NULL ORDER BY categoria");

        ob_start();
        ?>
        <div class="flavor-tramites-catalogo" data-columnas="<?php echo esc_attr($atributos['columnas']); ?>">
            <?php if ($atributos['mostrar_buscador'] === 'true' || $atributos['mostrar_filtros'] === 'true'): ?>
            <div class="flavor-tramites-filtros">
                <?php if ($atributos['mostrar_buscador'] === 'true'): ?>
                <div class="flavor-tramites-buscador">
                    <input type="text" id="flavor-buscar-tramite" placeholder="<?php esc_attr_e('Buscar tramite...', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <?php endif; ?>

                <?php if ($atributos['mostrar_filtros'] === 'true' && !empty($categorias)): ?>
                <div class="flavor-tramites-categorias">
                    <button class="flavor-categoria-btn active" data-categoria=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></button>
                    <?php foreach ($categorias as $categoria): ?>
                    <button class="flavor-categoria-btn" data-categoria="<?php echo esc_attr($categoria); ?>"><?php echo esc_html($categoria); ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flavor-tramites-grid">
                <?php if (empty($tipos)): ?>
                <p class="flavor-tramites-vacio"><?php esc_html_e('No hay tramites disponibles.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach ($tipos as $tipo): ?>
                    <div class="flavor-tramite-card" data-categoria="<?php echo esc_attr($tipo->categoria); ?>" data-id="<?php echo esc_attr($tipo->id); ?>">
                        <div class="flavor-tramite-icono" style="background-color: <?php echo esc_attr($tipo->color); ?>">
                            <span class="dashicons <?php echo esc_attr($tipo->icono); ?>"></span>
                        </div>
                        <div class="flavor-tramite-contenido">
                            <h3 class="flavor-tramite-titulo"><?php echo esc_html($tipo->nombre); ?></h3>
                            <p class="flavor-tramite-descripcion"><?php echo esc_html($tipo->descripcion_corta); ?></p>
                            <div class="flavor-tramite-meta">
                                <?php if ($tipo->plazo_resolucion_dias): ?>
                                <span class="flavor-tramite-plazo">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($tipo->plazo_resolucion_dias); ?> <?php esc_html_e('dias', 'flavor-chat-ia'); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($tipo->tasa > 0): ?>
                                <span class="flavor-tramite-tasa">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <?php echo number_format($tipo->tasa, 2); ?> EUR
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('tramite', $tipo->id, get_permalink())); ?>" class="flavor-tramite-enlace">
                            <?php esc_html_e('Iniciar tramite', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Iniciar tramite
     */
    public function shortcode_iniciar_tramite($atts) {
        $atributos = shortcode_atts([
            'id' => 0,
        ], $atts);

        $tipo_id = !empty($atributos['id']) ? absint($atributos['id']) : (isset($_GET['tramite']) ? absint($_GET['tramite']) : 0);

        if (!$tipo_id) {
            return '<p class="flavor-tramites-error">' . esc_html__('No se ha especificado el tipo de tramite.', 'flavor-chat-ia') . '</p>';
        }

        $tipo = $this->get_tipo_tramite_completo($tipo_id);

        if (!$tipo) {
            return '<p class="flavor-tramites-error">' . esc_html__('Tramite no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        if ($this->get_setting('requiere_login') && !is_user_logged_in()) {
            return '<div class="flavor-tramites-login-required">
                <p>' . esc_html__('Debes iniciar sesion para realizar este tramite.', 'flavor-chat-ia') . '</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesion', 'flavor-chat-ia') . '</a>
            </div>';
        }

        $usuario_actual = wp_get_current_user();

        ob_start();
        ?>
        <div class="flavor-tramites-formulario" data-tipo-id="<?php echo esc_attr($tipo_id); ?>">
            <div class="flavor-tramite-cabecera">
                <div class="flavor-tramite-icono-grande" style="background-color: <?php echo esc_attr($tipo->color); ?>">
                    <span class="dashicons <?php echo esc_attr($tipo->icono); ?>"></span>
                </div>
                <div class="flavor-tramite-info">
                    <h2><?php echo esc_html($tipo->nombre); ?></h2>
                    <p><?php echo esc_html($tipo->descripcion); ?></p>
                </div>
            </div>

            <?php if (!empty($tipo->requisitos)): ?>
            <div class="flavor-tramite-requisitos">
                <h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('Requisitos', 'flavor-chat-ia'); ?></h3>
                <ul>
                    <?php foreach ($tipo->requisitos as $requisito): ?>
                    <li><?php echo esc_html($requisito); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form id="flavor-form-tramite" class="flavor-form" enctype="multipart/form-data">
                <input type="hidden" name="tipo_tramite_id" value="<?php echo esc_attr($tipo_id); ?>">

                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Datos del solicitante', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-form-row">
                        <div class="flavor-form-field flavor-field-half">
                            <label for="nombre_solicitante"><?php esc_html_e('Nombre completo', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" id="nombre_solicitante" name="nombre_solicitante" required value="<?php echo esc_attr($usuario_actual->display_name); ?>">
                        </div>
                        <div class="flavor-form-field flavor-field-half">
                            <label for="dni_solicitante"><?php esc_html_e('DNI/NIE/CIF', 'flavor-chat-ia'); ?></label>
                            <input type="text" id="dni_solicitante" name="dni_solicitante" pattern="[0-9A-Za-z]{8,12}">
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-field flavor-field-half">
                            <label for="email_solicitante"><?php esc_html_e('Email', 'flavor-chat-ia'); ?> *</label>
                            <input type="email" id="email_solicitante" name="email_solicitante" required value="<?php echo esc_attr($usuario_actual->user_email); ?>">
                        </div>
                        <div class="flavor-form-field flavor-field-half">
                            <label for="telefono_solicitante"><?php esc_html_e('Telefono', 'flavor-chat-ia'); ?></label>
                            <input type="tel" id="telefono_solicitante" name="telefono_solicitante">
                        </div>
                    </div>

                    <div class="flavor-form-field">
                        <label for="direccion_solicitante"><?php esc_html_e('Direccion', 'flavor-chat-ia'); ?></label>
                        <textarea id="direccion_solicitante" name="direccion_solicitante" rows="2"></textarea>
                    </div>
                </div>

                <?php if (!empty($tipo->campos_formulario)): ?>
                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Datos del tramite', 'flavor-chat-ia'); ?></h3>
                    <?php echo $this->renderizar_campos_formulario($tipo->campos_formulario); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($tipo->documentos_requeridos)): ?>
                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Documentacion', 'flavor-chat-ia'); ?></h3>
                    <p class="flavor-form-ayuda"><?php esc_html_e('Formatos permitidos:', 'flavor-chat-ia'); ?> <?php echo esc_html($this->get_setting('tipos_archivo_permitidos')); ?></p>

                    <div class="flavor-documentos-requeridos">
                        <?php foreach ($tipo->documentos_requeridos as $doc): ?>
                        <div class="flavor-documento-item" data-tipo="<?php echo esc_attr($doc['codigo']); ?>">
                            <div class="flavor-documento-info">
                                <span class="flavor-documento-nombre">
                                    <?php echo esc_html($doc['nombre']); ?>
                                    <?php if (!empty($doc['obligatorio'])): ?>
                                    <span class="flavor-obligatorio">*</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($doc['descripcion'])): ?>
                                <span class="flavor-documento-desc"><?php echo esc_html($doc['descripcion']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-documento-upload">
                                <input type="file" name="documentos[<?php echo esc_attr($doc['codigo']); ?>]" class="flavor-file-input" <?php echo !empty($doc['obligatorio']) ? 'required' : ''; ?>>
                                <label class="flavor-file-label">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Seleccionar archivo', 'flavor-chat-ia'); ?>
                                </label>
                                <span class="flavor-file-name"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flavor-form-seccion">
                    <h3><?php esc_html_e('Observaciones', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-form-field">
                        <textarea id="notas_solicitante" name="notas" rows="3" placeholder="<?php esc_attr_e('Informacion adicional que quieras aportar...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>
                </div>

                <?php if ($tipo->tasa > 0): ?>
                <div class="flavor-tramite-tasa-info">
                    <span class="dashicons dashicons-info"></span>
                    <?php printf(esc_html__('Este tramite tiene una tasa de %s EUR', 'flavor-chat-ia'), number_format($tipo->tasa, 2)); ?>
                </div>
                <?php endif; ?>

                <div class="flavor-form-acciones">
                    <button type="button" class="flavor-btn flavor-btn-secondary" id="flavor-guardar-borrador">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e('Guardar borrador', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Enviar solicitud', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>

            <div class="flavor-tramite-resultado" style="display: none;">
                <div class="flavor-resultado-icono">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <h3><?php esc_html_e('Solicitud enviada correctamente', 'flavor-chat-ia'); ?></h3>
                <p class="flavor-resultado-numero"></p>
                <p class="flavor-resultado-mensaje"><?php esc_html_e('Recibiras un email de confirmacion con los detalles.', 'flavor-chat-ia'); ?></p>
                <div class="flavor-resultado-acciones">
                    <a href="#" class="flavor-btn flavor-btn-secondary flavor-ver-expediente"><?php esc_html_e('Ver mi expediente', 'flavor-chat-ia'); ?></a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis expedientes
     */
    public function shortcode_mis_expedientes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-tramites-login-required">
                <p>' . esc_html__('Debes iniciar sesion para ver tus expedientes.', 'flavor-chat-ia') . '</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesion', 'flavor-chat-ia') . '</a>
            </div>';
        }

        global $wpdb;

        $usuario_id = get_current_user_id();

        $expedientes = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                    es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.user_id = %d
             ORDER BY e.fecha_creacion DESC
             LIMIT 50",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-mis-expedientes">
            <div class="flavor-expedientes-cabecera">
                <h2><?php esc_html_e('Mis expedientes', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-expedientes-filtros">
                    <select id="flavor-filtro-estado">
                        <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                        <option value="borrador"><?php esc_html_e('Borrador', 'flavor-chat-ia'); ?></option>
                        <option value="pendiente"><?php esc_html_e('Pendiente', 'flavor-chat-ia'); ?></option>
                        <option value="en_revision"><?php esc_html_e('En revision', 'flavor-chat-ia'); ?></option>
                        <option value="en_tramite"><?php esc_html_e('En tramite', 'flavor-chat-ia'); ?></option>
                        <option value="resuelto_favorable"><?php esc_html_e('Resuelto favorable', 'flavor-chat-ia'); ?></option>
                        <option value="resuelto_desfavorable"><?php esc_html_e('Resuelto desfavorable', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>

            <?php if (empty($expedientes)): ?>
            <div class="flavor-expedientes-vacio">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('No tienes expedientes registrados.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php else: ?>
            <div class="flavor-expedientes-lista">
                <?php foreach ($expedientes as $expediente): ?>
                <div class="flavor-expediente-item" data-estado="<?php echo esc_attr($expediente->estado_actual); ?>">
                    <div class="flavor-expediente-icono" style="background-color: <?php echo esc_attr($expediente->tipo_color ?: '#0073aa'); ?>">
                        <span class="dashicons <?php echo esc_attr($expediente->tipo_icono ?: 'dashicons-clipboard'); ?>"></span>
                    </div>
                    <div class="flavor-expediente-info">
                        <div class="flavor-expediente-numero"><?php echo esc_html($expediente->numero_expediente); ?></div>
                        <div class="flavor-expediente-tipo"><?php echo esc_html($expediente->tipo_nombre); ?></div>
                        <div class="flavor-expediente-fecha">
                            <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($expediente->fecha_creacion))); ?>
                        </div>
                    </div>
                    <div class="flavor-expediente-estado">
                        <span class="flavor-estado-badge" style="background-color: <?php echo esc_attr($expediente->estado_color ?: '#666'); ?>">
                            <span class="dashicons <?php echo esc_attr($expediente->estado_icono ?: 'dashicons-marker'); ?>"></span>
                            <?php echo esc_html($expediente->estado_nombre); ?>
                        </span>
                    </div>
                    <div class="flavor-expediente-acciones">
                        <a href="<?php echo esc_url(add_query_arg('expediente', $expediente->numero_expediente, get_permalink())); ?>" class="flavor-btn flavor-btn-small">
                            <?php esc_html_e('Ver detalle', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estado de expediente
     */
    public function shortcode_estado_expediente($atts) {
        $numero = isset($_GET['expediente']) ? sanitize_text_field($_GET['expediente']) : '';

        ob_start();
        ?>
        <div class="flavor-estado-expediente">
            <div class="flavor-consulta-estado">
                <h2><?php esc_html_e('Consultar estado de expediente', 'flavor-chat-ia'); ?></h2>
                <form id="flavor-form-consulta" class="flavor-form-inline">
                    <input type="text" id="flavor-numero-expediente" name="numero" placeholder="<?php esc_attr_e('Numero de expediente (ej: EXP-2024-00001)', 'flavor-chat-ia'); ?>" value="<?php echo esc_attr($numero); ?>">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Consultar', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>

            <div class="flavor-expediente-detalle" style="display: none;">
                <div class="flavor-detalle-cabecera">
                    <div class="flavor-detalle-numero"></div>
                    <div class="flavor-detalle-estado"></div>
                </div>

                <div class="flavor-detalle-info">
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Tipo de tramite', 'flavor-chat-ia'); ?></span>
                        <span class="flavor-info-valor flavor-detalle-tipo"></span>
                    </div>
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Fecha de inicio', 'flavor-chat-ia'); ?></span>
                        <span class="flavor-info-valor flavor-detalle-fecha"></span>
                    </div>
                    <div class="flavor-info-item">
                        <span class="flavor-info-label"><?php esc_html_e('Fecha limite', 'flavor-chat-ia'); ?></span>
                        <span class="flavor-info-valor flavor-detalle-limite"></span>
                    </div>
                </div>

                <div class="flavor-timeline-container">
                    <h3><?php esc_html_e('Historico del expediente', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-timeline"></div>
                </div>
            </div>

            <div class="flavor-expediente-no-encontrado" style="display: none;">
                <span class="dashicons dashicons-warning"></span>
                <p><?php esc_html_e('No se ha encontrado ningun expediente con ese numero.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // METODOS AUXILIARES
    // =========================================================================

    /**
     * Obtener tipo de tramite
     */
    private function get_tipo_tramite($tipo_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_tipos_tramite} WHERE id = %d",
            $tipo_id
        ));
    }

    /**
     * Obtener tipo de tramite completo con campos
     */
    private function get_tipo_tramite_completo($tipo_id) {
        global $wpdb;

        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_tipos_tramite} WHERE id = %d AND estado = 'activo'",
            $tipo_id
        ));

        if (!$tipo) {
            return null;
        }

        $campos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_campos_formulario} WHERE tipo_tramite_id = %d AND activo = 1 ORDER BY orden ASC",
            $tipo_id
        ));

        foreach ($campos as &$campo) {
            if ($campo->opciones) {
                $campo->opciones = json_decode($campo->opciones, true);
            }
            if ($campo->condicion_visible) {
                $campo->condicion_visible = json_decode($campo->condicion_visible, true);
            }
        }

        $tipo->requisitos = $tipo->requisitos ? json_decode($tipo->requisitos, true) : [];
        $tipo->documentos_requeridos = $tipo->documentos_requeridos ? json_decode($tipo->documentos_requeridos, true) : [];
        $tipo->campos_formulario = $campos;

        return $tipo;
    }

    /**
     * Obtener expediente
     */
    private function get_expediente($expediente_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_expedientes} WHERE id = %d",
            $expediente_id
        ));
    }

    /**
     * Obtener expediente completo
     */
    private function get_expediente_completo($expediente_id) {
        global $wpdb;

        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono, t.color as tipo_color,
                    es.nombre as estado_nombre, es.color as estado_color, es.icono as estado_icono,
                    es.permite_edicion, es.permite_documentos
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             LEFT JOIN {$this->tabla_estados} es ON e.estado_actual = es.codigo
             WHERE e.id = %d",
            $expediente_id
        ));

        if (!$expediente) {
            return null;
        }

        $expediente->datos_formulario = $expediente->datos_formulario ? json_decode($expediente->datos_formulario, true) : [];

        $expediente->documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_documentos} WHERE expediente_id = %d AND visible_solicitante = 1 ORDER BY fecha_subida ASC",
            $expediente_id
        ));

        $expediente->historial = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_historial} WHERE expediente_id = %d AND es_publico = 1 ORDER BY fecha_evento DESC",
            $expediente_id
        ));

        return $expediente;
    }

    /**
     * Obtener estado
     */
    private function get_estado($codigo) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_estados} WHERE codigo = %s",
            $codigo
        ));
    }

    /**
     * Generar numero de expediente
     */
    private function generar_numero_expediente() {
        global $wpdb;

        $prefijo = $this->get_setting('prefijo_expediente');
        $anio = date('Y');

        $ultimo_numero = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(numero_expediente, '-', -1) AS UNSIGNED))
             FROM {$this->tabla_expedientes}
             WHERE numero_expediente LIKE %s",
            $prefijo . '-' . $anio . '-%'
        ));

        $siguiente_numero = ($ultimo_numero ?: 0) + 1;

        return sprintf('%s-%s-%05d', $prefijo, $anio, $siguiente_numero);
    }

    /**
     * Obtener o crear ID de sesion
     */
    private function get_or_create_session_id() {
        if (isset($_COOKIE['flavor_tramites_session'])) {
            return sanitize_text_field($_COOKIE['flavor_tramites_session']);
        }

        $session_id = wp_generate_uuid4();
        setcookie('flavor_tramites_session', $session_id, time() + (86400 * 30), '/');

        return $session_id;
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $claves_ip = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($claves_ip as $clave) {
            if (!empty($_SERVER[$clave])) {
                $ip = sanitize_text_field($_SERVER[$clave]);
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validar campos del formulario
     */
    private function validar_campos_formulario($tipo_id, $datos) {
        global $wpdb;

        $campos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_campos_formulario} WHERE tipo_tramite_id = %d AND activo = 1",
            $tipo_id
        ));

        $errores = [];

        foreach ($campos as $campo) {
            $valor = isset($datos[$campo->nombre_campo]) ? $datos[$campo->nombre_campo] : null;

            if ($campo->es_obligatorio && (empty($valor) && $valor !== '0')) {
                $errores[$campo->nombre_campo] = sprintf(__('El campo %s es obligatorio', 'flavor-chat-ia'), $campo->etiqueta);
                continue;
            }

            if (!empty($valor) && !empty($campo->patron_validacion)) {
                if (!preg_match('/' . $campo->patron_validacion . '/', $valor)) {
                    $errores[$campo->nombre_campo] = $campo->mensaje_error ?: sprintf(__('El campo %s no tiene un formato valido', 'flavor-chat-ia'), $campo->etiqueta);
                }
            }

            if (!empty($valor)) {
                switch ($campo->tipo_campo) {
                    case 'email':
                        if (!is_email($valor)) {
                            $errores[$campo->nombre_campo] = __('Email no valido', 'flavor-chat-ia');
                        }
                        break;
                    case 'dni':
                        if (!$this->validar_dni_nie($valor)) {
                            $errores[$campo->nombre_campo] = __('DNI/NIE no valido', 'flavor-chat-ia');
                        }
                        break;
                    case 'iban':
                        if (!$this->validar_iban($valor)) {
                            $errores[$campo->nombre_campo] = __('IBAN no valido', 'flavor-chat-ia');
                        }
                        break;
                }
            }
        }

        return $errores;
    }

    /**
     * Validar DNI/NIE
     */
    private function validar_dni_nie($documento) {
        $documento = strtoupper(trim($documento));

        if (preg_match('/^[0-9]{8}[A-Z]$/', $documento)) {
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            $numero = intval(substr($documento, 0, 8));
            return $documento[8] === $letras[$numero % 23];
        }

        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $documento)) {
            $reemplazos = ['X' => '0', 'Y' => '1', 'Z' => '2'];
            $numero = $reemplazos[$documento[0]] . substr($documento, 1, 7);
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            return $documento[8] === $letras[intval($numero) % 23];
        }

        return false;
    }

    /**
     * Validar IBAN
     */
    private function validar_iban($iban) {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));

        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/', $iban)) {
            return false;
        }

        $iban_reorganizado = substr($iban, 4) . substr($iban, 0, 4);
        $iban_numerico = '';

        for ($i = 0; $i < strlen($iban_reorganizado); $i++) {
            $caracter = $iban_reorganizado[$i];
            if (ctype_alpha($caracter)) {
                $iban_numerico .= (ord($caracter) - 55);
            } else {
                $iban_numerico .= $caracter;
            }
        }

        return bcmod($iban_numerico, '97') === '1';
    }

    /**
     * Renderizar campos del formulario
     */
    private function renderizar_campos_formulario($campos) {
        $html = '';
        $grupo_actual = '';

        foreach ($campos as $campo) {
            if ($campo->grupo && $campo->grupo !== $grupo_actual) {
                if ($grupo_actual !== '') {
                    $html .= '</div>';
                }
                $html .= '<div class="flavor-form-grupo" data-grupo="' . esc_attr($campo->grupo) . '">';
                $html .= '<h4>' . esc_html($campo->grupo) . '</h4>';
                $grupo_actual = $campo->grupo;
            }

            $clase_ancho = 'flavor-field-' . ($campo->ancho ?: 'full');
            $atributos_condicion = '';

            if ($campo->condicion_visible) {
                $atributos_condicion = ' data-condicion="' . esc_attr(wp_json_encode($campo->condicion_visible)) . '"';
            }

            $html .= '<div class="flavor-form-field ' . esc_attr($clase_ancho) . ' ' . esc_attr($campo->clase_css) . '"' . $atributos_condicion . '>';
            $html .= '<label for="campo_' . esc_attr($campo->nombre_campo) . '">';
            $html .= esc_html($campo->etiqueta);
            if ($campo->es_obligatorio) {
                $html .= ' <span class="flavor-obligatorio">*</span>';
            }
            $html .= '</label>';

            $atributos_campo = 'id="campo_' . esc_attr($campo->nombre_campo) . '" name="datos_formulario[' . esc_attr($campo->nombre_campo) . ']"';

            if ($campo->es_obligatorio) {
                $atributos_campo .= ' required';
            }
            if ($campo->es_readonly) {
                $atributos_campo .= ' readonly';
            }
            if ($campo->placeholder) {
                $atributos_campo .= ' placeholder="' . esc_attr($campo->placeholder) . '"';
            }
            if ($campo->patron_validacion) {
                $atributos_campo .= ' pattern="' . esc_attr($campo->patron_validacion) . '"';
            }

            switch ($campo->tipo_campo) {
                case 'textarea':
                    $html .= '<textarea ' . $atributos_campo . ' rows="4">' . esc_textarea($campo->valor_defecto) . '</textarea>';
                    break;

                case 'select':
                    $html .= '<select ' . $atributos_campo . '>';
                    $html .= '<option value="">' . esc_html__('Seleccionar...', 'flavor-chat-ia') . '</option>';
                    if (is_array($campo->opciones)) {
                        foreach ($campo->opciones as $opcion) {
                            $valor_opcion = is_array($opcion) ? $opcion['valor'] : $opcion;
                            $texto_opcion = is_array($opcion) ? $opcion['texto'] : $opcion;
                            $seleccionado = $campo->valor_defecto === $valor_opcion ? ' selected' : '';
                            $html .= '<option value="' . esc_attr($valor_opcion) . '"' . $seleccionado . '>' . esc_html($texto_opcion) . '</option>';
                        }
                    }
                    $html .= '</select>';
                    break;

                case 'radio':
                    $html .= '<div class="flavor-radio-group">';
                    if (is_array($campo->opciones)) {
                        foreach ($campo->opciones as $indice => $opcion) {
                            $valor_opcion = is_array($opcion) ? $opcion['valor'] : $opcion;
                            $texto_opcion = is_array($opcion) ? $opcion['texto'] : $opcion;
                            $marcado = $campo->valor_defecto === $valor_opcion ? ' checked' : '';
                            $html .= '<label class="flavor-radio-label">';
                            $html .= '<input type="radio" name="datos_formulario[' . esc_attr($campo->nombre_campo) . ']" value="' . esc_attr($valor_opcion) . '"' . $marcado . '>';
                            $html .= ' ' . esc_html($texto_opcion);
                            $html .= '</label>';
                        }
                    }
                    $html .= '</div>';
                    break;

                case 'checkbox':
                    $marcado = $campo->valor_defecto ? ' checked' : '';
                    $html .= '<label class="flavor-checkbox-label">';
                    $html .= '<input type="checkbox" ' . $atributos_campo . ' value="1"' . $marcado . '>';
                    $html .= ' ' . esc_html($campo->etiqueta);
                    $html .= '</label>';
                    break;

                case 'date':
                    $html .= '<input type="date" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'datetime':
                    $html .= '<input type="datetime-local" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'number':
                    $html .= '<input type="number" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                case 'file':
                    $html .= '<input type="file" ' . $atributos_campo . ' class="flavor-file-input">';
                    break;

                case 'hidden':
                    $html .= '<input type="hidden" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
                    break;

                default:
                    $tipo_input = in_array($campo->tipo_campo, ['email', 'tel', 'url']) ? $campo->tipo_campo : 'text';
                    $html .= '<input type="' . esc_attr($tipo_input) . '" ' . $atributos_campo . ' value="' . esc_attr($campo->valor_defecto) . '">';
            }

            if ($campo->ayuda) {
                $html .= '<span class="flavor-field-ayuda">' . esc_html($campo->ayuda) . '</span>';
            }

            $html .= '</div>';
        }

        if ($grupo_actual !== '') {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Registrar evento en historial
     */
    private function registrar_historial($expediente_id, $tipo_evento, $estado_anterior, $estado_nuevo, $descripcion, $datos_adicionales = null, $es_publico = true) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $nombre_usuario = $usuario_id ? wp_get_current_user()->display_name : __('Sistema', 'flavor-chat-ia');

        $wpdb->insert($this->tabla_historial, [
            'expediente_id' => $expediente_id,
            'tipo_evento' => $tipo_evento,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado_nuevo,
            'descripcion' => $descripcion,
            'datos_adicionales' => $datos_adicionales ? wp_json_encode($datos_adicionales) : null,
            'es_publico' => $es_publico ? 1 : 0,
            'usuario_id' => $usuario_id ?: null,
            'nombre_usuario' => $nombre_usuario,
            'ip_origen' => $this->get_client_ip(),
            'fecha_evento' => current_time('mysql'),
        ]);
    }

    /**
     * Enviar notificacion de inicio
     */
    private function enviar_notificacion_inicio($expediente_id) {
        $expediente = $this->get_expediente_completo($expediente_id);

        if (!$expediente || empty($expediente->email_solicitante)) {
            return false;
        }

        $asunto = sprintf(__('Confirmacion de tramite - %s', 'flavor-chat-ia'), $expediente->numero_expediente);

        $mensaje = sprintf(
            __("Estimado/a %s,\n\nSu solicitud ha sido registrada correctamente.\n\nNumero de expediente: %s\nTipo de tramite: %s\nFecha de registro: %s\n\nPuede consultar el estado de su expediente en cualquier momento.\n\nSaludos.", 'flavor-chat-ia'),
            $expediente->nombre_solicitante,
            $expediente->numero_expediente,
            $expediente->tipo_nombre,
            date_i18n('d/m/Y H:i', strtotime($expediente->fecha_creacion))
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($expediente->email_solicitante, $asunto, $mensaje, $headers);
    }

    // =========================================================================
    // ACCIONES DEL MODULO (COMPATIBILIDAD)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_tramites' => [
                'description' => 'Listar tipos de tramites disponibles',
                'params' => ['categoria', 'busqueda'],
            ],
            'detalle_tramite' => [
                'description' => 'Ver detalles de un tipo de tramite',
                'params' => ['tramite_id'],
            ],
            'crear_expediente' => [
                'description' => 'Crear un nuevo expediente',
                'params' => ['tipo_tramite_id', 'datos_formulario'],
            ],
            'mis_expedientes' => [
                'description' => 'Ver mis expedientes',
                'params' => ['estado'],
            ],
            'estado_expediente' => [
                'description' => 'Consultar estado de un expediente',
                'params' => ['numero_expediente'],
            ],
            'cancelar_expediente' => [
                'description' => 'Cancelar un expediente en borrador',
                'params' => ['expediente_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo = 'action_' . $action_name;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return [
            'success' => false,
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Listar tramites
     */
    private function action_listar_tramites($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('categoria', isset($params['categoria']) ? $params['categoria'] : '');
        $request->set_param('busqueda', isset($params['busqueda']) ? $params['busqueda'] : '');

        $response = $this->rest_get_tipos_tramite($request);
        return $response->get_data();
    }

    /**
     * Accion: Detalle tramite
     */
    private function action_detalle_tramite($params) {
        if (empty($params['tramite_id'])) {
            return ['success' => false, 'error' => 'Se requiere el ID del tramite'];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('id', $params['tramite_id']);

        $response = $this->rest_get_tipo_tramite($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    /**
     * Accion: Estado expediente
     */
    private function action_estado_expediente($params) {
        if (empty($params['numero_expediente'])) {
            return ['success' => false, 'error' => 'Se requiere el numero de expediente'];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('numero', $params['numero_expediente']);

        $response = $this->rest_consulta_expediente($request);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        return $response->get_data();
    }

    // =========================================================================
    // COMPONENTES WEB Y CONOCIMIENTO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'catalogo_tramites' => [
                'label' => __('Catalogo de Tramites', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Tramites Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_filtros' => ['type' => 'toggle', 'default' => true],
                ],
                'shortcode' => 'catalogo_tramites',
            ],
            'formulario_tramite' => [
                'label' => __('Formulario de Tramite', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-welcome-write-blog',
                'fields' => [
                    'tramite_id' => ['type' => 'number', 'default' => 0],
                ],
                'shortcode' => 'iniciar_tramite',
            ],
            'mis_expedientes' => [
                'label' => __('Mis Expedientes', 'flavor-chat-ia'),
                'category' => 'user',
                'icon' => 'dashicons-portfolio',
                'shortcode' => 'mis_expedientes',
            ],
            'consulta_estado' => [
                'label' => __('Consultar Estado', 'flavor-chat-ia'),
                'category' => 'widgets',
                'icon' => 'dashicons-search',
                'shortcode' => 'estado_expediente',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'listar_tramites',
                'description' => 'Ver tipos de tramites disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Filtrar por categoria'],
                        'busqueda' => ['type' => 'string', 'description' => 'Termino de busqueda'],
                    ],
                ],
            ],
            [
                'name' => 'detalle_tramite',
                'description' => 'Ver detalles y requisitos de un tramite',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tramite_id' => ['type' => 'integer', 'description' => 'ID del tipo de tramite'],
                    ],
                    'required' => ['tramite_id'],
                ],
            ],
            [
                'name' => 'estado_expediente',
                'description' => 'Consultar estado de un expediente',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'numero_expediente' => ['type' => 'string', 'description' => 'Numero de expediente'],
                    ],
                    'required' => ['numero_expediente'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Tramites Online**

Plataforma completa para la gestion de tramites administrativos de forma electronica.

**Funcionalidades principales:**
- Catalogo de tramites con buscador y filtros
- Formularios dinamicos configurables por tipo de tramite
- Subida de documentacion con validacion de formatos
- Seguimiento de expedientes con timeline de estados
- Notificaciones automaticas por email
- Consulta publica de estado por numero de expediente

**Proceso de tramitacion:**
1. Consultar el catalogo de tramites disponibles
2. Seleccionar el tramite y revisar requisitos
3. Cumplimentar el formulario con los datos requeridos
4. Adjuntar la documentacion necesaria
5. Enviar la solicitud y obtener numero de expediente
6. Seguir el estado del expediente hasta su resolucion

**Estados de un expediente:**
- Borrador: Solicitud guardada sin enviar
- Pendiente: Enviada, en espera de revision
- En revision: Siendo evaluada por el departamento
- Subsanacion: Requiere documentacion adicional
- En tramite: Tramitandose activamente
- Resuelto favorable: Aprobado
- Resuelto desfavorable: Denegado
- Archivado: Expediente cerrado

**Tipos de documentos aceptados:**
- PDF, JPG, JPEG, PNG para documentos escaneados
- DOC, DOCX para formularios editables

**Informacion importante:**
- Los expedientes tienen plazos de resolucion segun el tipo de tramite
- Puede haber tasas asociadas a determinados tramites
- Es posible guardar borradores y continuar mas tarde
- Las notificaciones de cambio de estado se envian por email
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo iniciar un tramite online?',
                'respuesta' => 'Accede al catalogo de tramites, selecciona el que necesitas, revisa los requisitos, rellena el formulario y adjunta la documentacion requerida.',
            ],
            [
                'pregunta' => 'Donde puedo consultar el estado de mi expediente?',
                'respuesta' => 'Puedes consultar el estado introduciendo tu numero de expediente en el buscador de estado, o accediendo a "Mis expedientes" si estas registrado.',
            ],
            [
                'pregunta' => 'Que documentos puedo adjuntar?',
                'respuesta' => 'Se aceptan archivos PDF, imagenes (JPG, PNG) y documentos Word (DOC, DOCX). El tamanio maximo por archivo es de 10MB.',
            ],
            [
                'pregunta' => 'Puedo guardar un tramite y continuarlo despues?',
                'respuesta' => 'Si, puedes guardar un borrador y retomarlo mas tarde desde "Mis expedientes".',
            ],
            [
                'pregunta' => 'Como recibo las notificaciones?',
                'respuesta' => 'Las notificaciones se envian automaticamente al email que proporcionaste al crear el expediente cuando hay cambios de estado.',
            ],
        ];
    }
}
