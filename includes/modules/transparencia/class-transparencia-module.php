<?php
/**
 * Modulo de Transparencia para Chat IA
 * Portal de transparencia y rendicion de cuentas
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Transparencia - Portal completo de transparencia y rendicion de cuentas
 */
class Flavor_Platform_Transparencia_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Version del modulo
     */
    const VERSION = '2.0.0';

    /**
     * Prefijo de tablas
     */
    private $prefijo_tabla;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->id = 'transparencia';
        $this->name = 'Portal de Transparencia'; // Translation loaded on init
        $this->description = 'Portal de transparencia con datos publicos, presupuestos, contratos, actas y rendicion de cuentas.'; // Translation loaded on init
        $this->module_role = 'transversal';
        $this->ecosystem_governs_modules = ['grupos_consumo', 'energia_comunitaria', 'participacion'];
        $this->dashboard_transversal_priority = 20;
        $this->dashboard_client_contexts = ['transparencia', 'gobernanza', 'rendicion_cuentas'];
        $this->dashboard_admin_contexts = ['transparencia', 'gobernanza', 'admin'];
        $this->prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['gobernanza'];
        $this->gailu_contribuye_a = ['autonomia', 'cohesion'];

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';
        return Flavor_Platform_Helpers::tabla_existe($tabla_documentos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Transparencia no estan creadas. Se crearan automaticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
            'permite_solicitudes_anonimas' => false,
            'dias_plazo_respuesta' => 30,
            'publicacion_automatica' => false,
            'requiere_aprobacion_publicacion' => true,
            'notificar_nuevas_solicitudes' => true,
            'email_notificaciones' => get_option('admin_email'),
            'categorias_habilitadas' => [
                'presupuestos', 'contratos', 'subvenciones', 'normativa',
                'actas', 'personal', 'indicadores', 'patrimonio'
            ],
            'mostrar_graficos' => true,
            'limite_documentos_por_pagina' => 12,
            'formatos_permitidos' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'],
            'tamano_maximo_archivo' => 10485760, // 10MB
        ];
    }

    /**
     * Configuracion para el panel de administracion unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => 'transparencia',
            'label'      => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'       => 'dashicons-visibility',
            'capability' => 'manage_options',
            'categoria'  => 'economia',
            'paginas'    => [
                [
                    'slug'     => 'transparencia-dashboard',
                    'titulo'   => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_dashboard'],
                    'badge'    => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug'     => 'transparencia-datos',
                    'titulo'   => __('Datos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_datos'],
                ],
                [
                    'slug'     => 'transparencia-solicitudes',
                    'titulo'   => __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_solicitudes'],
                    'badge'    => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug'     => 'transparencia-publicar',
                    'titulo'   => __('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_publicar'],
                ],
                [
                    'slug'     => 'transparencia-informes',
                    'titulo'   => __('Informes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_informes'],
                ],
                [
                    'slug'     => 'transparencia-configuracion',
                    'titulo'   => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas'     => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en panel de administracion unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Registrar shortcodes
        $this->register_shortcodes();

        // Registrar AJAX handlers
        $this->register_ajax_handlers();

        // Registrar REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Hooks adicionales
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        add_action('transparencia_check_plazos', [$this, 'verificar_plazos_solicitudes']);

        // Programar cron si no existe
        if (!wp_next_scheduled('transparencia_check_plazos')) {
            wp_schedule_event(time(), 'daily', 'transparencia_check_plazos');
        }

        // Cargar Dashboard Tab para el frontend del usuario
        $this->cargar_dashboard_tab();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Carga el Dashboard Tab del modulo
     */
    private function cargar_dashboard_tab() {
        $ruta_dashboard_tab = dirname(__FILE__) . '/class-transparencia-dashboard-tab.php';
        if (file_exists($ruta_dashboard_tab)) {
            require_once $ruta_dashboard_tab;
            if (class_exists('Flavor_Transparencia_Dashboard_Tab')) {
                Flavor_Transparencia_Dashboard_Tab::get_instance();
            }
        }

        // Controlador frontend (shortcodes y paneles de usuario en /mi-portal/).
        $ruta_frontend_controller = dirname(__FILE__) . '/frontend/class-transparencia-frontend-controller.php';
        if (file_exists($ruta_frontend_controller)) {
            require_once $ruta_frontend_controller;
            if (class_exists('Flavor_Transparencia_Frontend_Controller')) {
                Flavor_Transparencia_Frontend_Controller::get_instance();
            }
        }
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('transparencia_portal', [$this, 'shortcode_portal']);
        add_shortcode('transparencia_presupuesto_actual', [$this, 'shortcode_presupuesto_actual']);
        add_shortcode('transparencia_presupuestos', [$this, 'shortcode_presupuestos']);
        add_shortcode('transparencia_ultimos_gastos', [$this, 'shortcode_ultimos_gastos']);
        add_shortcode('transparencia_buscador_docs', [$this, 'shortcode_buscador_docs']);
        add_shortcode('transparencia_solicitar_info', [$this, 'shortcode_solicitar_info']);
        add_shortcode('transparencia_solicitar', [$this, 'shortcode_solicitar_info']);
        add_shortcode('transparencia_actas', [$this, 'shortcode_actas']);
        add_shortcode('transparencia_contratos', [$this, 'shortcode_contratos']);
        add_shortcode('transparencia_grafico_presupuesto', [$this, 'shortcode_grafico_presupuesto']);
        add_shortcode('transparencia_indicadores', [$this, 'shortcode_indicadores']);
        add_shortcode('transparencia_presupuesto_resumen', [$this, 'shortcode_presupuesto_resumen']);
        add_shortcode('transparencia_mis_solicitudes', [$this, 'shortcode_mis_solicitudes_alias']);
    }

    /**
     * Wrapper para reutilizar el shortcode frontend de mis solicitudes.
     *
     * @param array $atributos
     * @return string
     */
    public function shortcode_mis_solicitudes_alias($atributos = []) {
        if (shortcode_exists('flavor_transparencia_mis_solicitudes')) {
            return do_shortcode('[flavor_transparencia_mis_solicitudes]');
        }

        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                __('Debes iniciar sesión para ver tus solicitudes.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                '</div>';
        }

        return '<div class="flavor-alert flavor-alert-info">' .
            __('El panel de seguimiento de solicitudes no está disponible en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            '</div>';
    }

    /**
     * Render para tab de mis solicitudes en rutas dinámicas.
     *
     * @param int $user_id
     * @return string
     */
    public function render_tab_mis_solicitudes($user_id = 0) {
        return $this->shortcode_mis_solicitudes_alias([]);
    }

    /**
     * Registrar handlers AJAX
     */
    private function register_ajax_handlers() {
        $acciones_ajax = [
            'transparencia_buscar_documentos',
            'transparencia_ver_documento',
            'transparencia_descargar_documento',
            'transparencia_enviar_solicitud',
            'transparencia_obtener_presupuesto',
            'transparencia_filtrar_gastos',
            'transparencia_obtener_actas',
            'transparencia_estadisticas',
        ];

        foreach ($acciones_ajax as $accion) {
            add_action("wp_ajax_{$accion}", [$this, $accion]);
            add_action("wp_ajax_nopriv_{$accion}", [$this, $accion]);
        }
    }

    /**
     * Encolar assets frontend
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_base = plugin_dir_url(__FILE__) . 'assets/';
        $version_assets = self::VERSION;
        $css_file = plugin_dir_path(__FILE__) . 'assets/css/transparencia.css';
        $css_version = file_exists($css_file) ? (string) filemtime($css_file) : $version_assets;

        wp_enqueue_style(
            'flavor-transparencia',
            $ruta_base . 'css/transparencia.css',
            [],
            $css_version
        );

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_enqueue_script(
            'flavor-transparencia',
            $ruta_base . 'js/transparencia.js',
            ['jquery', 'chartjs'],
            $version_assets,
            true
        );

        wp_localize_script('flavor-transparencia', 'flavorTransparencia', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('transparencia_nonce'),
            'strings' => [
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinResultados' => __('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarDescarga' => __('Descargar documento?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Verificar si se deben cargar los assets
     */
    private function should_load_assets() {
        global $post;

        // Rutas dinámicas del portal /mi-portal/transparencia/*
        $modulo_actual = sanitize_key((string) get_query_var('flavor_module', ''));
        if ($modulo_actual === 'transparencia') {
            return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
        if ($request_uri !== '' && strpos($request_uri, '/mi-portal/transparencia') !== false) {
            return true;
        }

        if (!$post) {
            return false;
        }

        $shortcodes_transparencia = [
            'transparencia_portal', 'transparencia_presupuesto_actual',
            'transparencia_ultimos_gastos', 'transparencia_buscador_docs',
            'transparencia_solicitar_info', 'transparencia_actas',
            'transparencia_grafico_presupuesto', 'transparencia_indicadores',
            'transparencia_presupuestos', 'transparencia_contratos',
            'transparencia_presupuesto_resumen'
        ];

        foreach ($shortcodes_transparencia as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encolar assets admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'transparencia') === false) {
            return;
        }

        $css_file = plugin_dir_path(__FILE__) . 'assets/css/transparencia.css';
        $css_version = file_exists($css_file) ? (string) filemtime($css_file) : self::VERSION;
        wp_enqueue_style('flavor-transparencia-admin', plugin_dir_url(__FILE__) . 'assets/css/transparencia.css', [], $css_version);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_documentos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla de documentos publicos
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';
        $sql_documentos = "CREATE TABLE IF NOT EXISTS $tabla_documentos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            categoria varchar(50) NOT NULL,
            subcategoria varchar(100) DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            contenido longtext DEFAULT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            archivo_nombre varchar(255) DEFAULT NULL,
            archivo_tipo varchar(50) DEFAULT NULL,
            archivo_tamano bigint(20) unsigned DEFAULT NULL,
            importe decimal(15,2) DEFAULT NULL,
            periodo varchar(100) DEFAULT NULL,
            fecha_documento date DEFAULT NULL,
            entidad varchar(255) DEFAULT NULL,
            departamento varchar(255) DEFAULT NULL,
            etiquetas json DEFAULT NULL,
            metadatos json DEFAULT NULL,
            estado enum('borrador','pendiente','publicado','archivado') DEFAULT 'borrador',
            visitas int(11) unsigned DEFAULT 0,
            descargas int(11) unsigned DEFAULT 0,
            autor_id bigint(20) unsigned DEFAULT NULL,
            aprobado_por bigint(20) unsigned DEFAULT NULL,
            fecha_publicacion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY subcategoria (subcategoria),
            KEY estado (estado),
            KEY periodo (periodo),
            KEY fecha_publicacion (fecha_publicacion),
            KEY entidad (entidad),
            FULLTEXT KEY busqueda (titulo, descripcion, contenido)
        ) $charset_collate;";
        dbDelta($sql_documentos);

        // Tabla de presupuestos
        $tabla_presupuestos = $this->prefijo_tabla . 'presupuestos';
        $sql_presupuestos = "CREATE TABLE IF NOT EXISTS $tabla_presupuestos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ejercicio year NOT NULL,
            tipo enum('ingresos','gastos') NOT NULL,
            capitulo varchar(10) NOT NULL,
            articulo varchar(10) DEFAULT NULL,
            concepto varchar(10) DEFAULT NULL,
            subconcepto varchar(10) DEFAULT NULL,
            denominacion varchar(500) NOT NULL,
            credito_inicial decimal(15,2) DEFAULT 0,
            modificaciones decimal(15,2) DEFAULT 0,
            credito_definitivo decimal(15,2) DEFAULT 0,
            obligaciones_reconocidas decimal(15,2) DEFAULT 0,
            pagos_realizados decimal(15,2) DEFAULT 0,
            pendiente_pago decimal(15,2) DEFAULT 0,
            porcentaje_ejecucion decimal(5,2) DEFAULT 0,
            entidad varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY presupuesto_unico (ejercicio, tipo, capitulo, articulo, concepto, subconcepto, entidad),
            KEY ejercicio (ejercicio),
            KEY tipo (tipo),
            KEY capitulo (capitulo),
            KEY entidad (entidad)
        ) $charset_collate;";
        dbDelta($sql_presupuestos);

        // Tabla de gastos
        $tabla_gastos = $this->prefijo_tabla . 'gastos';
        $sql_gastos = "CREATE TABLE IF NOT EXISTS $tabla_gastos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ejercicio year NOT NULL,
            numero_operacion varchar(50) DEFAULT NULL,
            fecha_operacion date NOT NULL,
            concepto text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            subcategoria varchar(100) DEFAULT NULL,
            proveedor varchar(255) DEFAULT NULL,
            proveedor_nif varchar(20) DEFAULT NULL,
            importe decimal(15,2) NOT NULL,
            iva decimal(15,2) DEFAULT 0,
            importe_total decimal(15,2) NOT NULL,
            capitulo_presupuestario varchar(10) DEFAULT NULL,
            partida_presupuestaria varchar(50) DEFAULT NULL,
            centro_coste varchar(100) DEFAULT NULL,
            proyecto varchar(100) DEFAULT NULL,
            contrato_id bigint(20) unsigned DEFAULT NULL,
            factura_numero varchar(100) DEFAULT NULL,
            factura_fecha date DEFAULT NULL,
            estado_pago enum('pendiente','pagado','anulado') DEFAULT 'pendiente',
            fecha_pago date DEFAULT NULL,
            observaciones text DEFAULT NULL,
            documento_url varchar(500) DEFAULT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ejercicio (ejercicio),
            KEY fecha_operacion (fecha_operacion),
            KEY categoria (categoria),
            KEY proveedor (proveedor),
            KEY estado_pago (estado_pago),
            KEY capitulo_presupuestario (capitulo_presupuestario)
        ) $charset_collate;";
        dbDelta($sql_gastos);

        // Tabla de solicitudes de informacion
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';
        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_expediente varchar(50) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            nombre_solicitante varchar(255) DEFAULT NULL,
            email_solicitante varchar(255) NOT NULL,
            telefono_solicitante varchar(20) DEFAULT NULL,
            direccion_solicitante text DEFAULT NULL,
            tipo_solicitante enum('persona_fisica','persona_juridica','anonimo') DEFAULT 'persona_fisica',
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            motivo text DEFAULT NULL,
            formato_preferido enum('electronico','papel','ambos') DEFAULT 'electronico',
            estado enum('recibida','admitida','en_tramite','ampliacion','resuelta','denegada','desistida','archivada') DEFAULT 'recibida',
            prioridad enum('baja','normal','alta','urgente') DEFAULT 'normal',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            respuesta longtext DEFAULT NULL,
            motivo_denegacion text DEFAULT NULL,
            documentos_adjuntos json DEFAULT NULL,
            documentos_respuesta json DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_admision datetime DEFAULT NULL,
            fecha_limite datetime DEFAULT NULL,
            fecha_resolucion datetime DEFAULT NULL,
            dias_tramitacion int(11) DEFAULT NULL,
            ip_solicitante varchar(45) DEFAULT NULL,
            notas_internas text DEFAULT NULL,
            historial_estados json DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY numero_expediente (numero_expediente),
            KEY user_id (user_id),
            KEY estado (estado),
            KEY categoria (categoria),
            KEY asignado_a (asignado_a),
            KEY fecha_solicitud (fecha_solicitud),
            KEY fecha_limite (fecha_limite)
        ) $charset_collate;";
        dbDelta($sql_solicitudes);

        // Tabla de actas
        $tabla_actas = $this->prefijo_tabla . 'actas';
        $sql_actas = "CREATE TABLE IF NOT EXISTS $tabla_actas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo_organo enum('pleno','junta_gobierno','comision','consejo','otros') NOT NULL,
            nombre_organo varchar(255) NOT NULL,
            numero_sesion varchar(50) DEFAULT NULL,
            tipo_sesion enum('ordinaria','extraordinaria','urgente') DEFAULT 'ordinaria',
            fecha_sesion datetime NOT NULL,
            lugar varchar(255) DEFAULT NULL,
            convocatoria_url varchar(500) DEFAULT NULL,
            orden_del_dia longtext DEFAULT NULL,
            acta_contenido longtext DEFAULT NULL,
            acta_url varchar(500) DEFAULT NULL,
            video_url varchar(500) DEFAULT NULL,
            audio_url varchar(500) DEFAULT NULL,
            asistentes json DEFAULT NULL,
            ausentes json DEFAULT NULL,
            invitados json DEFAULT NULL,
            acuerdos json DEFAULT NULL,
            votos json DEFAULT NULL,
            estado enum('convocada','celebrada','aprobada','publicada') DEFAULT 'convocada',
            fecha_aprobacion date DEFAULT NULL,
            observaciones text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo_organo (tipo_organo),
            KEY fecha_sesion (fecha_sesion),
            KEY estado (estado),
            KEY tipo_sesion (tipo_sesion)
        ) $charset_collate;";
        dbDelta($sql_actas);

        // Actualizar version de DB
        update_option('flavor_transparencia_db_version', self::VERSION);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-chat/v1';

        // Documentos publicos
        register_rest_route($namespace, '/transparencia/documentos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_documentos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => $this->get_documentos_args(),
        ]);

        register_rest_route($namespace, '/transparencia/documentos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_documento'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Presupuestos
        register_rest_route($namespace, '/transparencia/presupuestos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_presupuestos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/transparencia/presupuestos/resumen', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_resumen_presupuesto'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Gastos
        register_rest_route($namespace, '/transparencia/gastos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_gastos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/transparencia/gastos/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estadisticas_gastos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Actas
        register_rest_route($namespace, '/transparencia/actas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_actas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Solicitudes
        register_rest_route($namespace, '/transparencia/solicitudes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_solicitud'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/transparencia/solicitudes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_solicitud'],
            'permission_callback' => [$this, 'check_solicitud_permission'],
        ]);

        // Estadisticas generales
        register_rest_route($namespace, '/transparencia/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estadisticas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Argumentos para endpoint de documentos
     */
    private function get_documentos_args() {
        return [
            'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'periodo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'termino' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'pagina' => ['type' => 'integer', 'default' => 1],
            'por_pagina' => ['type' => 'integer', 'default' => 12],
            'orden' => ['type' => 'string', 'default' => 'fecha_publicacion'],
            'direccion' => ['type' => 'string', 'default' => 'DESC'],
        ];
    }

    /**
     * REST: Obtener documentos
     */
    public function rest_get_documentos($request) {
        $parametros = [
            'categoria' => $request->get_param('categoria'),
            'periodo' => $request->get_param('periodo'),
            'termino' => $request->get_param('termino'),
            'pagina' => $request->get_param('pagina'),
            'por_pagina' => $request->get_param('por_pagina'),
            'orden' => $request->get_param('orden'),
            'direccion' => $request->get_param('direccion'),
        ];

        $resultado = $this->obtener_documentos_publicos($parametros);

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * REST: Obtener documento individual
     */
    public function rest_get_documento($request) {
        $documento_id = (int) $request->get_param('id');
        $documento = $this->obtener_documento_por_id($documento_id);

        if (!$documento) {
            return new WP_Error('not_found', __('Documento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        // Incrementar visitas
        $this->incrementar_visitas($documento_id);

        return new WP_REST_Response($documento, 200);
    }

    /**
     * REST: Obtener presupuestos
     */
    public function rest_get_presupuestos($request) {
        $ejercicio = $request->get_param('ejercicio') ?: date('Y');
        $tipo = $request->get_param('tipo');

        $presupuestos = $this->obtener_presupuestos($ejercicio, $tipo);

        return new WP_REST_Response($presupuestos, 200);
    }

    /**
     * REST: Obtener resumen de presupuesto
     */
    public function rest_get_resumen_presupuesto($request) {
        $ejercicio = $request->get_param('ejercicio') ?: date('Y');

        $resumen = $this->calcular_resumen_presupuesto($ejercicio);

        return new WP_REST_Response($resumen, 200);
    }

    /**
     * REST: Obtener gastos
     */
    public function rest_get_gastos($request) {
        $parametros = [
            'ejercicio' => $request->get_param('ejercicio') ?: date('Y'),
            'categoria' => $request->get_param('categoria'),
            'desde' => $request->get_param('desde'),
            'hasta' => $request->get_param('hasta'),
            'limite' => $request->get_param('limite') ?: 50,
        ];

        $gastos = $this->obtener_gastos($parametros);

        return new WP_REST_Response($gastos, 200);
    }

    /**
     * REST: Obtener estadisticas de gastos
     */
    public function rest_get_estadisticas_gastos($request) {
        $ejercicio = $request->get_param('ejercicio') ?: date('Y');

        $estadisticas = $this->calcular_estadisticas_gastos($ejercicio);

        return new WP_REST_Response($estadisticas, 200);
    }

    /**
     * REST: Obtener actas
     */
    public function rest_get_actas($request) {
        $parametros = [
            'tipo_organo' => $request->get_param('tipo_organo'),
            'desde' => $request->get_param('desde'),
            'hasta' => $request->get_param('hasta'),
            'estado' => $request->get_param('estado') ?: 'publicada',
            'limite' => $request->get_param('limite') ?: 20,
        ];

        $actas = $this->obtener_actas($parametros);

        return new WP_REST_Response($actas, 200);
    }

    /**
     * REST: Crear solicitud de informacion
     */
    public function rest_crear_solicitud($request) {
        $datos = [
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'categoria' => $request->get_param('categoria'),
            'nombre' => $request->get_param('nombre'),
            'email' => $request->get_param('email'),
            'telefono' => $request->get_param('telefono'),
        ];

        $resultado = $this->crear_solicitud_informacion($datos);

        if ($resultado['success']) {
            return new WP_REST_Response($resultado, 201);
        }

        return new WP_Error('error', $resultado['error'], ['status' => 400]);
    }

    /**
     * REST: Obtener solicitud
     */
    public function rest_get_solicitud($request) {
        $solicitud_id = (int) $request->get_param('id');
        $solicitud = $this->obtener_solicitud_por_id($solicitud_id);

        if (!$solicitud) {
            return new WP_Error('not_found', __('Solicitud no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        return new WP_REST_Response($solicitud, 200);
    }

    /**
     * REST: Obtener estadisticas generales
     */
    public function rest_get_estadisticas($request) {
        $estadisticas = $this->calcular_estadisticas_generales();

        return new WP_REST_Response($estadisticas, 200);
    }

    /**
     * Verificar permisos para ver solicitud
     */
    public function check_solicitud_permission($request) {
        $solicitud_id = (int) $request->get_param('id');
        $usuario_actual_id = get_current_user_id();

        if (current_user_can('manage_options')) {
            return true;
        }

        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';
        $propietario_solicitud = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        return $usuario_actual_id && $propietario_solicitud == $usuario_actual_id;
    }

    /**
     * Obtener documentos publicos
     */
    private function obtener_documentos_publicos($parametros) {
        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        $where = ["estado = 'publicado'"];
        $valores_prepare = [];

        if (!empty($parametros['categoria'])) {
            $where[] = 'categoria = %s';
            $valores_prepare[] = sanitize_text_field($parametros['categoria']);
        }

        if (!empty($parametros['periodo'])) {
            $where[] = 'periodo = %s';
            $valores_prepare[] = sanitize_text_field($parametros['periodo']);
        }

        if (!empty($parametros['termino'])) {
            $termino_busqueda = '%' . $wpdb->esc_like(sanitize_text_field($parametros['termino'])) . '%';
            $where[] = '(titulo LIKE %s OR descripcion LIKE %s OR contenido LIKE %s)';
            $valores_prepare[] = $termino_busqueda;
            $valores_prepare[] = $termino_busqueda;
            $valores_prepare[] = $termino_busqueda;
        }

        $pagina = max(1, (int) ($parametros['pagina'] ?? 1));
        $por_pagina = min(50, max(1, (int) ($parametros['por_pagina'] ?? 12)));
        $offset = ($pagina - 1) * $por_pagina;

        $orden = in_array($parametros['orden'] ?? '', ['titulo', 'fecha_publicacion', 'importe', 'categoria'])
            ? $parametros['orden'] : 'fecha_publicacion';
        $direccion = strtoupper($parametros['direccion'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_documentos WHERE " . implode(' AND ', $where);
        if (!empty($valores_prepare)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_count, ...$valores_prepare));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        // Obtener documentos
        $sql = "SELECT id, categoria, subcategoria, titulo, descripcion, importe, periodo,
                       fecha_documento, entidad, archivo_url, archivo_nombre, fecha_publicacion, visitas
                FROM $tabla_documentos
                WHERE " . implode(' AND ', $where) . "
                ORDER BY $orden $direccion
                LIMIT %d OFFSET %d";

        $valores_prepare[] = $por_pagina;
        $valores_prepare[] = $offset;

        $documentos = $wpdb->get_results($wpdb->prepare($sql, ...$valores_prepare));

        return [
            'documentos' => array_map([$this, 'formatear_documento'], $documentos),
            'paginacion' => [
                'pagina_actual' => $pagina,
                'por_pagina' => $por_pagina,
                'total' => (int) $total,
                'total_paginas' => ceil($total / $por_pagina),
            ],
        ];
    }

    /**
     * Formatear documento para respuesta
     */
    private function formatear_documento($documento) {
        return [
            'id' => (int) $documento->id,
            'categoria' => $documento->categoria,
            'subcategoria' => $documento->subcategoria,
            'titulo' => $documento->titulo,
            'descripcion' => wp_trim_words($documento->descripcion ?? '', 30),
            'importe' => $documento->importe ? (float) $documento->importe : null,
            'periodo' => $documento->periodo,
            'fecha_documento' => $documento->fecha_documento,
            'entidad' => $documento->entidad,
            'tiene_archivo' => !empty($documento->archivo_url),
            'fecha_publicacion' => $documento->fecha_publicacion
                ? date_i18n('d/m/Y', strtotime($documento->fecha_publicacion)) : null,
            'visitas' => (int) $documento->visitas,
        ];
    }

    /**
     * Obtener documento por ID
     */
    private function obtener_documento_por_id($documento_id) {
        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_documentos WHERE id = %d AND estado = 'publicado'",
            $documento_id
        ));

        if (!$documento) {
            return null;
        }

        return [
            'id' => (int) $documento->id,
            'categoria' => $documento->categoria,
            'subcategoria' => $documento->subcategoria,
            'titulo' => $documento->titulo,
            'descripcion' => $documento->descripcion,
            'contenido' => $documento->contenido,
            'importe' => $documento->importe ? (float) $documento->importe : null,
            'periodo' => $documento->periodo,
            'fecha_documento' => $documento->fecha_documento,
            'entidad' => $documento->entidad,
            'departamento' => $documento->departamento,
            'tiene_archivo' => !empty($documento->archivo_url),
            'archivo_nombre' => $documento->archivo_nombre,
            'archivo_tipo' => $documento->archivo_tipo,
            'fecha_publicacion' => $documento->fecha_publicacion
                ? date_i18n('d/m/Y H:i', strtotime($documento->fecha_publicacion)) : null,
            'etiquetas' => json_decode($documento->etiquetas ?: '[]', true),
            'metadatos' => json_decode($documento->metadatos ?: '{}', true),
        ];
    }

    /**
     * Incrementar visitas de documento
     */
    private function incrementar_visitas($documento_id) {
        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_documentos SET visitas = visitas + 1 WHERE id = %d",
            $documento_id
        ));
    }

    /**
     * Obtener presupuestos
     */
    private function obtener_presupuestos($ejercicio, $tipo = null) {
        global $wpdb;
        $tabla_presupuestos = $this->prefijo_tabla . 'presupuestos';

        $where = ['ejercicio = %d'];
        $valores = [$ejercicio];

        if ($tipo) {
            $where[] = 'tipo = %s';
            $valores[] = $tipo;
        }

        $presupuestos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_presupuestos
             WHERE " . implode(' AND ', $where) . "
             ORDER BY tipo, capitulo, articulo, concepto",
            ...$valores
        ));

        return array_map(function($presupuesto) {
            return [
                'id' => (int) $presupuesto->id,
                'tipo' => $presupuesto->tipo,
                'capitulo' => $presupuesto->capitulo,
                'articulo' => $presupuesto->articulo,
                'concepto' => $presupuesto->concepto,
                'denominacion' => $presupuesto->denominacion,
                'credito_inicial' => (float) $presupuesto->credito_inicial,
                'modificaciones' => (float) $presupuesto->modificaciones,
                'credito_definitivo' => (float) $presupuesto->credito_definitivo,
                'obligaciones_reconocidas' => (float) $presupuesto->obligaciones_reconocidas,
                'pagos_realizados' => (float) $presupuesto->pagos_realizados,
                'porcentaje_ejecucion' => (float) $presupuesto->porcentaje_ejecucion,
            ];
        }, $presupuestos);
    }

    /**
     * Calcular resumen de presupuesto
     */
    private function calcular_resumen_presupuesto($ejercicio) {
        global $wpdb;
        $tabla_presupuestos = $this->prefijo_tabla . 'presupuestos';

        $resumen_ingresos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(credito_inicial) as inicial,
                SUM(credito_definitivo) as definitivo,
                SUM(obligaciones_reconocidas) as reconocido
             FROM $tabla_presupuestos
             WHERE ejercicio = %d AND tipo = 'ingresos'",
            $ejercicio
        ));

        $resumen_gastos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(credito_inicial) as inicial,
                SUM(credito_definitivo) as definitivo,
                SUM(obligaciones_reconocidas) as reconocido,
                SUM(pagos_realizados) as pagado
             FROM $tabla_presupuestos
             WHERE ejercicio = %d AND tipo = 'gastos'",
            $ejercicio
        ));

        $por_capitulo_ingresos = $wpdb->get_results($wpdb->prepare(
            "SELECT capitulo, denominacion, SUM(credito_definitivo) as total
             FROM $tabla_presupuestos
             WHERE ejercicio = %d AND tipo = 'ingresos'
             GROUP BY capitulo
             ORDER BY capitulo",
            $ejercicio
        ));

        $por_capitulo_gastos = $wpdb->get_results($wpdb->prepare(
            "SELECT capitulo, denominacion, SUM(credito_definitivo) as total
             FROM $tabla_presupuestos
             WHERE ejercicio = %d AND tipo = 'gastos'
             GROUP BY capitulo
             ORDER BY capitulo",
            $ejercicio
        ));

        return [
            'ejercicio' => $ejercicio,
            'ingresos' => [
                'inicial' => (float) ($resumen_ingresos->inicial ?? 0),
                'definitivo' => (float) ($resumen_ingresos->definitivo ?? 0),
                'reconocido' => (float) ($resumen_ingresos->reconocido ?? 0),
            ],
            'gastos' => [
                'inicial' => (float) ($resumen_gastos->inicial ?? 0),
                'definitivo' => (float) ($resumen_gastos->definitivo ?? 0),
                'reconocido' => (float) ($resumen_gastos->reconocido ?? 0),
                'pagado' => (float) ($resumen_gastos->pagado ?? 0),
            ],
            'saldo' => (float) (($resumen_ingresos->definitivo ?? 0) - ($resumen_gastos->definitivo ?? 0)),
            'por_capitulo' => [
                'ingresos' => array_map(function($capitulo) {
                    return [
                        'capitulo' => $capitulo->capitulo,
                        'denominacion' => $capitulo->denominacion,
                        'total' => (float) $capitulo->total,
                    ];
                }, $por_capitulo_ingresos),
                'gastos' => array_map(function($capitulo) {
                    return [
                        'capitulo' => $capitulo->capitulo,
                        'denominacion' => $capitulo->denominacion,
                        'total' => (float) $capitulo->total,
                    ];
                }, $por_capitulo_gastos),
            ],
        ];
    }

    /**
     * Obtener gastos
     */
    private function obtener_gastos($parametros) {
        global $wpdb;
        $tabla_gastos = $this->prefijo_tabla . 'gastos';

        $where = ['1=1'];
        $valores = [];

        if (!empty($parametros['ejercicio'])) {
            $where[] = 'ejercicio = %d';
            $valores[] = (int) $parametros['ejercicio'];
        }

        if (!empty($parametros['categoria'])) {
            $where[] = 'categoria = %s';
            $valores[] = sanitize_text_field($parametros['categoria']);
        }

        if (!empty($parametros['desde'])) {
            $where[] = 'fecha_operacion >= %s';
            $valores[] = sanitize_text_field($parametros['desde']);
        }

        if (!empty($parametros['hasta'])) {
            $where[] = 'fecha_operacion <= %s';
            $valores[] = sanitize_text_field($parametros['hasta']);
        }

        $limite = min(100, max(1, (int) ($parametros['limite'] ?? 50)));
        $valores[] = $limite;

        $gastos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, fecha_operacion, concepto, categoria, proveedor, importe_total,
                    capitulo_presupuestario, estado_pago
             FROM $tabla_gastos
             WHERE " . implode(' AND ', $where) . "
             ORDER BY fecha_operacion DESC
             LIMIT %d",
            ...$valores
        ));

        return array_map(function($gasto) {
            return [
                'id' => (int) $gasto->id,
                'fecha' => date_i18n('d/m/Y', strtotime($gasto->fecha_operacion)),
                'concepto' => $gasto->concepto,
                'categoria' => $gasto->categoria,
                'proveedor' => $gasto->proveedor,
                'importe' => (float) $gasto->importe_total,
                'capitulo' => $gasto->capitulo_presupuestario,
                'estado_pago' => $gasto->estado_pago,
            ];
        }, $gastos);
    }

    /**
     * Calcular estadisticas de gastos
     */
    private function calcular_estadisticas_gastos($ejercicio) {
        global $wpdb;
        $tabla_gastos = $this->prefijo_tabla . 'gastos';

        $total_gastos = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(importe_total) FROM $tabla_gastos WHERE ejercicio = %d",
            $ejercicio
        ));

        $por_categoria = $wpdb->get_results($wpdb->prepare(
            "SELECT categoria, COUNT(*) as cantidad, SUM(importe_total) as total
             FROM $tabla_gastos
             WHERE ejercicio = %d AND categoria IS NOT NULL
             GROUP BY categoria
             ORDER BY total DESC",
            $ejercicio
        ));

        $por_mes = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(fecha_operacion) as mes, SUM(importe_total) as total
             FROM $tabla_gastos
             WHERE ejercicio = %d
             GROUP BY MONTH(fecha_operacion)
             ORDER BY mes",
            $ejercicio
        ));

        $top_proveedores = $wpdb->get_results($wpdb->prepare(
            "SELECT proveedor, COUNT(*) as operaciones, SUM(importe_total) as total
             FROM $tabla_gastos
             WHERE ejercicio = %d AND proveedor IS NOT NULL
             GROUP BY proveedor
             ORDER BY total DESC
             LIMIT 10",
            $ejercicio
        ));

        return [
            'ejercicio' => $ejercicio,
            'total' => (float) ($total_gastos ?? 0),
            'por_categoria' => array_map(function($categoria) {
                return [
                    'categoria' => $categoria->categoria,
                    'cantidad' => (int) $categoria->cantidad,
                    'total' => (float) $categoria->total,
                ];
            }, $por_categoria),
            'por_mes' => array_map(function($mes) {
                return [
                    'mes' => (int) $mes->mes,
                    'total' => (float) $mes->total,
                ];
            }, $por_mes),
            'top_proveedores' => array_map(function($proveedor) {
                return [
                    'proveedor' => $proveedor->proveedor,
                    'operaciones' => (int) $proveedor->operaciones,
                    'total' => (float) $proveedor->total,
                ];
            }, $top_proveedores),
        ];
    }

    /**
     * Obtener actas
     */
    private function obtener_actas($parametros) {
        global $wpdb;
        $tabla_actas = $this->prefijo_tabla . 'actas';

        $where = ['1=1'];
        $valores = [];

        if (!empty($parametros['tipo_organo'])) {
            $where[] = 'tipo_organo = %s';
            $valores[] = sanitize_text_field($parametros['tipo_organo']);
        }

        if (!empty($parametros['estado'])) {
            $where[] = 'estado = %s';
            $valores[] = sanitize_text_field($parametros['estado']);
        }

        if (!empty($parametros['desde'])) {
            $where[] = 'fecha_sesion >= %s';
            $valores[] = sanitize_text_field($parametros['desde']);
        }

        if (!empty($parametros['hasta'])) {
            $where[] = 'fecha_sesion <= %s';
            $valores[] = sanitize_text_field($parametros['hasta']);
        }

        $limite = min(50, max(1, (int) ($parametros['limite'] ?? 20)));
        $valores[] = $limite;

        $actas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo_organo, nombre_organo, numero_sesion, tipo_sesion,
                    fecha_sesion, lugar, acta_url, video_url, estado
             FROM $tabla_actas
             WHERE " . implode(' AND ', $where) . "
             ORDER BY fecha_sesion DESC
             LIMIT %d",
            ...$valores
        ));

        return array_map(function($acta) {
            return [
                'id' => (int) $acta->id,
                'tipo_organo' => $acta->tipo_organo,
                'nombre_organo' => $acta->nombre_organo,
                'numero_sesion' => $acta->numero_sesion,
                'tipo_sesion' => $acta->tipo_sesion,
                'fecha_sesion' => date_i18n('d/m/Y H:i', strtotime($acta->fecha_sesion)),
                'lugar' => $acta->lugar,
                'tiene_acta' => !empty($acta->acta_url),
                'tiene_video' => !empty($acta->video_url),
                'estado' => $acta->estado,
            ];
        }, $actas);
    }

    /**
     * Crear solicitud de informacion
     */
    private function crear_solicitud_informacion($datos) {
        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';
        $configuracion = $this->get_settings();

        // Validaciones
        if (empty($datos['titulo']) || empty($datos['descripcion'])) {
            return ['success' => false, 'error' => __('El titulo y la descripcion son obligatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if (empty($datos['email']) || !is_email($datos['email'])) {
            return ['success' => false, 'error' => __('Debes indicar un correo electrónico válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id && !$configuracion['permite_solicitudes_anonimas']) {
            return ['success' => false, 'error' => __('Debes iniciar sesión para solicitar información.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Generar numero de expediente
        $anio_actual = date('Y');
        $ultima_solicitud = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(numero_expediente, '/', 1) AS UNSIGNED))
             FROM $tabla_solicitudes
             WHERE numero_expediente LIKE %s",
            '%/' . $anio_actual
        ));
        $numero_siguiente = ($ultima_solicitud ?? 0) + 1;
        $numero_expediente = sprintf('%04d/%s', $numero_siguiente, $anio_actual);

        // Calcular fecha limite
        $dias_plazo = (int) $configuracion['dias_plazo_respuesta'];
        $fecha_limite = date('Y-m-d H:i:s', strtotime("+{$dias_plazo} weekdays"));

        $resultado_insercion = $wpdb->insert($tabla_solicitudes, [
            'numero_expediente' => $numero_expediente,
            'user_id' => $usuario_actual_id ?: null,
            'nombre_solicitante' => sanitize_text_field($datos['nombre'] ?? ''),
            'email_solicitante' => sanitize_email($datos['email']),
            'telefono_solicitante' => sanitize_text_field($datos['telefono'] ?? ''),
            'tipo_solicitante' => $usuario_actual_id ? 'persona_fisica' : 'anonimo',
            'titulo' => sanitize_text_field($datos['titulo']),
            'descripcion' => sanitize_textarea_field($datos['descripcion']),
            'categoria' => !empty($datos['categoria']) ? sanitize_text_field($datos['categoria']) : null,
            'estado' => 'recibida',
            'fecha_solicitud' => current_time('mysql'),
            'fecha_limite' => $fecha_limite,
            'ip_solicitante' => $this->obtener_ip_cliente(),
            'historial_estados' => json_encode([[
                'estado' => 'recibida',
                'fecha' => current_time('mysql'),
                'usuario' => null,
                'observaciones' => 'Solicitud registrada automaticamente',
            ]]),
        ]);

        if ($resultado_insercion === false) {
            return ['success' => false, 'error' => __('No se pudo registrar la solicitud de información.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $solicitud_id = $wpdb->insert_id;

        // Enviar notificaciones
        $this->enviar_notificacion_nueva_solicitud($solicitud_id, $numero_expediente);
        $this->enviar_confirmacion_solicitante($datos['email'], $numero_expediente);

        return [
            'success' => true,
            'mensaje' => __('Solicitud registrada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'solicitud_id' => $solicitud_id,
            'numero_expediente' => $numero_expediente,
            'fecha_limite' => date_i18n('d/m/Y', strtotime($fecha_limite)),
        ];
    }

    /**
     * Obtener solicitud por ID
     */
    private function obtener_solicitud_por_id($solicitud_id) {
        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        if (!$solicitud) {
            return null;
        }

        return [
            'id' => (int) $solicitud->id,
            'numero_expediente' => $solicitud->numero_expediente,
            'titulo' => $solicitud->titulo,
            'descripcion' => $solicitud->descripcion,
            'categoria' => $solicitud->categoria,
            'estado' => $solicitud->estado,
            'fecha_solicitud' => date_i18n('d/m/Y H:i', strtotime($solicitud->fecha_solicitud)),
            'fecha_limite' => $solicitud->fecha_limite
                ? date_i18n('d/m/Y', strtotime($solicitud->fecha_limite)) : null,
            'fecha_resolucion' => $solicitud->fecha_resolucion
                ? date_i18n('d/m/Y H:i', strtotime($solicitud->fecha_resolucion)) : null,
            'respuesta' => $solicitud->respuesta,
            'motivo_denegacion' => $solicitud->motivo_denegacion,
            'dias_tramitacion' => $solicitud->dias_tramitacion,
        ];
    }

    /**
     * Calcular estadisticas generales
     */
    private function calcular_estadisticas_generales() {
        global $wpdb;

        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';
        $tabla_actas = $this->prefijo_tabla . 'actas';

        $estadisticas_documentos = $wpdb->get_row(
            "SELECT COUNT(*) as total, SUM(visitas) as visitas_totales, SUM(descargas) as descargas_totales
             FROM $tabla_documentos WHERE estado = 'publicado'"
        );

        $documentos_por_categoria = $wpdb->get_results(
            "SELECT categoria, COUNT(*) as cantidad
             FROM $tabla_documentos WHERE estado = 'publicado'
             GROUP BY categoria"
        );

        $estadisticas_solicitudes = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'recibida' THEN 1 ELSE 0 END) as recibidas,
                SUM(CASE WHEN estado = 'en_tramite' THEN 1 ELSE 0 END) as en_tramite,
                SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas,
                SUM(CASE WHEN estado = 'denegada' THEN 1 ELSE 0 END) as denegadas,
                AVG(dias_tramitacion) as promedio_dias
             FROM $tabla_solicitudes"
        );

        $total_actas = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_actas WHERE estado = 'publicada'"
        );

        return [
            'documentos' => [
                'total' => (int) ($estadisticas_documentos->total ?? 0),
                'visitas' => (int) ($estadisticas_documentos->visitas_totales ?? 0),
                'descargas' => (int) ($estadisticas_documentos->descargas_totales ?? 0),
                'por_categoria' => array_map(function($categoria) {
                    return [
                        'categoria' => $categoria->categoria,
                        'cantidad' => (int) $categoria->cantidad,
                    ];
                }, $documentos_por_categoria),
            ],
            'solicitudes' => [
                'total' => (int) ($estadisticas_solicitudes->total ?? 0),
                'recibidas' => (int) ($estadisticas_solicitudes->recibidas ?? 0),
                'en_tramite' => (int) ($estadisticas_solicitudes->en_tramite ?? 0),
                'resueltas' => (int) ($estadisticas_solicitudes->resueltas ?? 0),
                'denegadas' => (int) ($estadisticas_solicitudes->denegadas ?? 0),
                'promedio_dias_tramitacion' => round((float) ($estadisticas_solicitudes->promedio_dias ?? 0), 1),
            ],
            'actas' => [
                'total' => (int) $total_actas,
            ],
        ];
    }

    /**
     * Enviar notificacion de nueva solicitud
     */
    private function enviar_notificacion_nueva_solicitud($solicitud_id, $numero_expediente) {
        $configuracion = $this->get_settings();

        if (!$configuracion['notificar_nuevas_solicitudes']) {
            return;
        }

        $email_destino = $configuracion['email_notificaciones'];
        $asunto = sprintf(__('[Transparencia] Nueva solicitud de informacion: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $numero_expediente);
        $mensaje = sprintf(
            __("Se ha recibido una nueva solicitud de acceso a informacion publica.\n\nNumero de expediente: %s\n\nAcceda al panel de administracion para gestionarla.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $numero_expediente
        );

        wp_mail($email_destino, $asunto, $mensaje);
    }

    /**
     * Enviar confirmacion al solicitante
     */
    private function enviar_confirmacion_solicitante($email, $numero_expediente) {
        $asunto = sprintf(__('Confirmacion de solicitud de informacion: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $numero_expediente);
        $configuracion = $this->get_settings();
        $dias_plazo = $configuracion['dias_plazo_respuesta'];

        $mensaje = sprintf(
            __("Su solicitud de acceso a informacion publica ha sido registrada correctamente.\n\nNumero de expediente: %s\n\nPlazo maximo de respuesta: %d dias habiles.\n\nGuarde este numero de expediente para futuras consultas.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $numero_expediente,
            $dias_plazo
        );

        wp_mail($email, $asunto, $mensaje);
    }

    /**
     * Obtener IP del cliente
     */
    private function obtener_ip_cliente() {
        $claves_ip = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($claves_ip as $clave) {
            if (!empty($_SERVER[$clave])) {
                $ip = $_SERVER[$clave];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return sanitize_text_field(trim($ip));
            }
        }

        return '0.0.0.0';
    }

    /**
     * Verificar plazos de solicitudes (cron)
     */
    public function verificar_plazos_solicitudes() {
        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        $solicitudes_proximas = $wpdb->get_results(
            "SELECT id, numero_expediente, email_solicitante, fecha_limite
             FROM $tabla_solicitudes
             WHERE estado IN ('recibida', 'admitida', 'en_tramite')
             AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 5 DAY)
             AND fecha_limite > NOW()"
        );

        foreach ($solicitudes_proximas as $solicitud) {
            $this->notificar_plazo_proximo($solicitud);
        }
    }

    /**
     * Notificar plazo proximo a vencer
     */
    private function notificar_plazo_proximo($solicitud) {
        $configuracion = $this->get_settings();
        $email_destino = $configuracion['email_notificaciones'];

        $asunto = sprintf(__('[URGENTE] Plazo proximo a vencer: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $solicitud->numero_expediente);
        $mensaje = sprintf(
            __("La solicitud %s tiene el plazo de respuesta proximo a vencer.\n\nFecha limite: %s\n\nPor favor, resuelva la solicitud a la mayor brevedad.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $solicitud->numero_expediente,
            date_i18n('d/m/Y', strtotime($solicitud->fecha_limite))
        );

        wp_mail($email_destino, $asunto, $mensaje);
    }

    // ========================================================================
    // AJAX HANDLERS
    // ========================================================================

    /**
     * AJAX: Buscar documentos
     */
    public function transparencia_buscar_documentos() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $parametros = [
            'termino' => sanitize_text_field($_POST['termino'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'pagina' => (int) ($_POST['pagina'] ?? 1),
        ];

        $resultado = $this->obtener_documentos_publicos($parametros);

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Ver documento
     */
    public function transparencia_ver_documento() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $documento_id = (int) ($_POST['documento_id'] ?? 0);

        if (!$documento_id) {
            wp_send_json_error(['mensaje' => __('ID de documento invalido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $documento = $this->obtener_documento_por_id($documento_id);

        if (!$documento) {
            wp_send_json_error(['mensaje' => __('Documento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->incrementar_visitas($documento_id);

        wp_send_json_success($documento);
    }

    /**
     * AJAX: Descargar documento
     */
    public function transparencia_descargar_documento() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'transparencia_nonce')) {
            wp_die(__('Acceso no autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $documento_id = (int) ($_GET['documento_id'] ?? 0);
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT archivo_url, archivo_nombre, archivo_tipo FROM $tabla_documentos WHERE id = %d AND estado = 'publicado'",
            $documento_id
        ));

        if (!$documento || empty($documento->archivo_url)) {
            wp_die(__(__('Documento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Incrementar descargas
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_documentos SET descargas = descargas + 1 WHERE id = %d",
            $documento_id
        ));

        // Redirigir al archivo
        wp_redirect($documento->archivo_url);
        exit;
    }

    /**
     * AJAX: Enviar solicitud
     */
    public function transparencia_enviar_solicitud() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $datos = [
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
        ];

        $resultado = $this->crear_solicitud_informacion($datos);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Obtener presupuesto
     */
    public function transparencia_obtener_presupuesto() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $periodo = sanitize_text_field($_POST['periodo'] ?? date('Y'));

        $resumen = $this->calcular_resumen_presupuesto($periodo);

        $datos_grafico = [
            'etiquetas' => array_column($resumen['por_capitulo']['gastos'], 'denominacion'),
            'valores' => array_column($resumen['por_capitulo']['gastos'], 'total'),
            'resumen' => [
                'total' => $resumen['gastos']['definitivo'],
                'ingresos' => $resumen['ingresos']['definitivo'],
                'gastos' => $resumen['gastos']['definitivo'],
                'saldo' => $resumen['saldo'],
            ],
        ];

        wp_send_json_success($datos_grafico);
    }

    /**
     * AJAX: Filtrar gastos
     */
    public function transparencia_filtrar_gastos() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $parametros = [
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'ejercicio' => (int) ($_POST['ejercicio'] ?? date('Y')),
            'limite' => 20,
        ];

        $gastos = $this->obtener_gastos($parametros);

        wp_send_json_success(['gastos' => $gastos]);
    }

    /**
     * AJAX: Obtener actas
     */
    public function transparencia_obtener_actas() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $parametros = [
            'tipo_organo' => sanitize_text_field($_POST['tipo_organo'] ?? ''),
            'estado' => 'publicada',
            'limite' => 20,
        ];

        $actas = $this->obtener_actas($parametros);

        wp_send_json_success(['actas' => $actas]);
    }

    /**
     * AJAX: Estadisticas
     */
    public function transparencia_estadisticas() {
        check_ajax_referer('transparencia_nonce', 'nonce');

        $estadisticas = $this->calcular_estadisticas_generales();

        wp_send_json_success($estadisticas);
    }

    // ========================================================================
    // SHORTCODES
    // ========================================================================

    /**
     * Shortcode: Portal completo de transparencia
     */
    public function shortcode_portal($atributos) {
        $atributos = shortcode_atts([
            'mostrar_buscador' => 'true',
            'mostrar_categorias' => 'true',
            'categoria' => '',
        ], $atributos);

        ob_start();
        ?>
        <div class="transparencia-portal">
            <div class="transparencia-header">
                <h1><?php _e('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
                <p><?php _e('Acceso a la informacion publica y rendicion de cuentas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <?php if ($atributos['mostrar_categorias'] === 'true'): ?>
            <nav class="transparencia-nav">
                <a href="#" class="transparencia-nav-item active" data-categoria="">
                    <span class="dashicons dashicons-category"></span>
                    <?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#" class="transparencia-nav-item" data-categoria="presupuestos">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php _e('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#" class="transparencia-nav-item" data-categoria="contratos">
                    <span class="dashicons dashicons-media-text"></span>
                    <?php _e('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#" class="transparencia-nav-item" data-categoria="subvenciones">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php _e('Subvenciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#" class="transparencia-nav-item" data-categoria="actas">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php _e('Actas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="#" class="transparencia-nav-item" data-categoria="normativa">
                    <span class="dashicons dashicons-book"></span>
                    <?php _e('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </nav>
            <?php endif; ?>

            <?php if ($atributos['mostrar_buscador'] === 'true'): ?>
            <div class="transparencia-buscador">
                <h2 class="transparencia-buscador-titulo"><?php _e('Buscar documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <form class="transparencia-buscador-form">
                    <div class="transparencia-campo">
                        <label for="transparencia-termino"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="transparencia-termino" name="termino" class="transparencia-buscar-input"
                               placeholder="<?php _e('Escriba su busqueda...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="transparencia-campo">
                        <label for="transparencia-categoria"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="transparencia-categoria" name="categoria">
                            <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('contratos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('subvenciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Subvenciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('actas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Actas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('normativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div class="transparencia-campo">
                        <label for="transparencia-periodo"><?php _e('Periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="transparencia-periodo" name="periodo">
                            <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php for ($anio = date('Y'); $anio >= date('Y') - 5; $anio--): ?>
                            <option value="<?php echo $anio; ?>"><?php echo $anio; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="transparencia-btn transparencia-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="transparencia-mensajes"></div>
            <div class="transparencia-documentos-grid"></div>
            <div class="transparencia-paginacion"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Presupuesto actual
     */
    public function shortcode_presupuesto_actual($atributos) {
        $atributos = shortcode_atts([
            'ejercicio' => date('Y'),
            'mostrar_grafico' => 'true',
        ], $atributos);

        $resumen = $this->calcular_resumen_presupuesto($atributos['ejercicio']);

        ob_start();
        ?>
        <div class="transparencia-presupuesto">
            <div class="transparencia-presupuesto-header">
                <h2 class="transparencia-presupuesto-titulo"><?php _e('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <span class="transparencia-presupuesto-periodo"><?php echo esc_html($atributos['ejercicio']); ?></span>
            </div>

            <div class="transparencia-presupuesto-resumen">
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Presupuesto Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor total">
                        <?php echo number_format($resumen['gastos']['definitivo'], 2, ',', '.'); ?> &euro;
                    </div>
                </div>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Ingresos Previstos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor ingresos">
                        <?php echo number_format($resumen['ingresos']['definitivo'], 2, ',', '.'); ?> &euro;
                    </div>
                </div>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Gastos Previstos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor gastos">
                        <?php echo number_format($resumen['gastos']['definitivo'], 2, ',', '.'); ?> &euro;
                    </div>
                </div>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Saldo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor">
                        <?php echo number_format($resumen['saldo'], 2, ',', '.'); ?> &euro;
                    </div>
                </div>
            </div>

            <?php if ($atributos['mostrar_grafico'] === 'true'): ?>
            <div class="transparencia-presupuesto-chart">
                <canvas id="transparencia-chart-presupuesto"></canvas>
            </div>
            <div id="transparencia-datos-presupuesto" data-valores='<?php echo json_encode([
                'etiquetas' => array_column($resumen['por_capitulo']['gastos'], 'denominacion'),
                'valores' => array_column($resumen['por_capitulo']['gastos'], 'total'),
            ]); ?>'></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Presupuestos completos
     */
    public function shortcode_presupuestos($atributos) {
        $atts = shortcode_atts([
            'limite' => 12,
        ], $atributos);

        $template_path = dirname(__FILE__) . '/templates/presupuestos.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return $this->shortcode_presupuesto_actual($atributos);
    }

    /**
     * Shortcode: Ultimos gastos
     */
    public function shortcode_ultimos_gastos($atributos) {
        $atributos = shortcode_atts([
            'limite' => 10,
            'categoria' => '',
        ], $atributos);

        $parametros = [
            'limite' => (int) $atributos['limite'],
            'categoria' => $atributos['categoria'],
        ];

        $gastos = $this->obtener_gastos($parametros);

        ob_start();
        ?>
        <div class="transparencia-gastos">
            <div class="transparencia-gastos-header">
                <h3 class="transparencia-gastos-titulo"><?php _e('Ultimos Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="transparencia-gastos-lista">
                <?php if (empty($gastos)): ?>
                    <div class="transparencia-vacio">
                        <?php _e('No hay gastos para mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($gastos as $gasto): ?>
                    <div class="transparencia-gasto-item">
                        <div class="transparencia-gasto-info">
                            <div class="transparencia-gasto-concepto"><?php echo esc_html($gasto['concepto']); ?></div>
                            <div class="transparencia-gasto-fecha"><?php echo esc_html($gasto['fecha']); ?></div>
                        </div>
                        <div class="transparencia-gasto-importe">
                            <?php echo number_format($gasto['importe'], 2, ',', '.'); ?> &euro;
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Buscador de documentos
     */
    public function shortcode_buscador_docs($atributos) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'placeholder' => __('Buscar documentos publicos...', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], $atributos);

        ob_start();
        ?>
        <div class="transparencia-buscador">
            <form class="transparencia-buscador-form">
                <div class="transparencia-campo" style="flex: 1;">
                    <input type="text" name="termino" class="transparencia-buscar-input"
                           placeholder="<?php echo esc_attr($atributos['placeholder']); ?>">
                    <?php if ($atributos['categoria']): ?>
                    <input type="hidden" name="categoria" value="<?php echo esc_attr($atributos['categoria']); ?>">
                    <?php endif; ?>
                </div>
                <button type="submit" class="transparencia-btn transparencia-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>
            <div class="transparencia-documentos-grid" style="margin-top: 1.5rem;"></div>
            <div class="transparencia-paginacion"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Solicitar informacion
     */
    public function shortcode_solicitar_info($atributos) {
        $atributos = shortcode_atts([
            'mostrar_info' => 'true',
        ], $atributos);

        $configuracion = $this->get_settings();
        $usuario_logueado = is_user_logged_in();
        $usuario_actual = wp_get_current_user();
        $estado_envio = null;
        $valores_formulario = [
            'nombre' => '',
            'email' => '',
            'telefono' => '',
            'titulo' => '',
            'categoria' => '',
            'descripcion' => '',
        ];

        if ($usuario_logueado) {
            $valores_formulario['nombre'] = (string) $usuario_actual->display_name;
            $valores_formulario['email'] = (string) $usuario_actual->user_email;
        }

        // Fallback sin JS: procesar envio por POST del propio formulario.
        if (
            isset($_POST['flavor_transparencia_form'])
            && sanitize_text_field(wp_unslash((string) $_POST['flavor_transparencia_form'])) === 'solicitud_info'
        ) {
            $nonce = sanitize_text_field(wp_unslash((string) ($_POST['flavor_transparencia_form_nonce'] ?? '')));
            if (!wp_verify_nonce($nonce, 'flavor_transparencia_form_solicitud')) {
                $estado_envio = [
                    'success' => false,
                    'error' => __('La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            } else {
                $datos_post = [
                    'nombre' => sanitize_text_field(wp_unslash((string) ($_POST['nombre'] ?? ''))),
                    'email' => sanitize_email(wp_unslash((string) ($_POST['email'] ?? ''))),
                    'telefono' => sanitize_text_field(wp_unslash((string) ($_POST['telefono'] ?? ''))),
                    'titulo' => sanitize_text_field(wp_unslash((string) ($_POST['titulo'] ?? ''))),
                    'categoria' => sanitize_text_field(wp_unslash((string) ($_POST['categoria'] ?? ''))),
                    'descripcion' => sanitize_textarea_field(wp_unslash((string) ($_POST['descripcion'] ?? ''))),
                ];

                $valores_formulario = array_merge($valores_formulario, $datos_post);
                $estado_envio = $this->crear_solicitud_informacion($datos_post);
            }
        }

        ob_start();
        ?>
        <div class="transparencia-solicitud">
            <div class="transparencia-solicitud-header">
                <h2 class="transparencia-solicitud-titulo"><?php _e('Solicitud de Acceso a Informacion Publica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="transparencia-solicitud-descripcion">
                    <?php _e('Complete el formulario para solicitar informacion publica. Recibira respuesta en un plazo maximo de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <?php echo $configuracion['dias_plazo_respuesta']; ?> <?php _e('dias habiles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if ($atributos['mostrar_info'] === 'true'): ?>
            <div class="transparencia-solicitud-info">
                <strong><?php _e('Derecho de acceso:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <?php _e('Toda persona tiene derecho a acceder a la informacion publica, sin necesidad de motivar su solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <?php endif; ?>

            <?php if (is_array($estado_envio)) : ?>
            <div class="transparencia-mensajes">
                <?php if (!empty($estado_envio['success'])) : ?>
                <div class="transparencia-mensaje transparencia-mensaje-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span>
                        <?php
                        printf(
                            esc_html__('Solicitud enviada correctamente. Expediente: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            esc_html((string) ($estado_envio['numero_expediente'] ?? '-'))
                        );
                        ?>
                    </span>
                </div>
                <?php else : ?>
                <div class="transparencia-mensaje transparencia-mensaje-error">
                    <span class="dashicons dashicons-warning"></span>
                    <span><?php echo esc_html((string) ($estado_envio['error'] ?? __('No se pudo enviar la solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN))); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form class="transparencia-solicitud-form" method="post" action="">
                <input type="hidden" name="flavor_transparencia_form" value="solicitud_info">
                <input type="hidden" name="flavor_transparencia_form_nonce" value="<?php echo esc_attr(wp_create_nonce('flavor_transparencia_form_solicitud')); ?>">
                <?php if (!$usuario_logueado): ?>
                <div class="transparencia-campo">
                    <label for="solicitud-nombre"><?php _e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" id="solicitud-nombre" name="nombre" value="<?php echo esc_attr($valores_formulario['nombre']); ?>" required>
                </div>
                <div class="transparencia-campo">
                    <label for="solicitud-email"><?php _e('Correo electronico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="email" id="solicitud-email" name="email" value="<?php echo esc_attr($valores_formulario['email']); ?>" required>
                </div>
                <div class="transparencia-campo">
                    <label for="solicitud-telefono"><?php _e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="tel" id="solicitud-telefono" name="telefono" value="<?php echo esc_attr($valores_formulario['telefono']); ?>">
                </div>
                <?php else: ?>
                <input type="hidden" name="nombre" value="<?php echo esc_attr($valores_formulario['nombre']); ?>">
                <input type="hidden" name="email" value="<?php echo esc_attr($valores_formulario['email']); ?>">
                <?php endif; ?>

                <div class="transparencia-campo">
                    <label for="solicitud-titulo"><?php _e('Titulo de la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" id="solicitud-titulo" name="titulo" required
                           value="<?php echo esc_attr($valores_formulario['titulo']); ?>"
                           placeholder="<?php _e('Describa brevemente la informacion que solicita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="transparencia-campo">
                    <label for="solicitud-categoria"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="solicitud-categoria" name="categoria">
                        <option value=""><?php _e('Seleccione una categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'presupuestos'); ?>><?php _e('Presupuestos y cuentas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('contratos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'contratos'); ?>><?php _e('Contratos y licitaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('subvenciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'subvenciones'); ?>><?php _e('Subvenciones y ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('personal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'personal'); ?>><?php _e('Personal y retribuciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('normativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'normativa'); ?>><?php _e('Normativa y acuerdos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($valores_formulario['categoria'], 'otros'); ?>><?php _e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="transparencia-campo">
                    <label for="solicitud-descripcion"><?php _e('Descripcion detallada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea id="solicitud-descripcion" name="descripcion" required
                              placeholder="<?php _e('Describa con detalle la informacion que necesita, incluyendo periodos, entidades o cualquier dato que ayude a localizar la informacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php echo esc_textarea($valores_formulario['descripcion']); ?></textarea>
                </div>

                <div class="transparencia-solicitud-acciones">
                    <button type="reset" class="transparencia-btn transparencia-btn-secondary">
                        <?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="transparencia-btn transparencia-btn-success">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Actas
     */
    public function shortcode_actas($atributos) {
        $atributos = shortcode_atts([
            'tipo_organo' => '',
            'limite' => 10,
        ], $atributos);

        $parametros = [
            'tipo_organo' => $atributos['tipo_organo'],
            'estado' => 'publicada',
            'limite' => (int) $atributos['limite'],
        ];

        $actas = $this->obtener_actas($parametros);

        ob_start();
        ?>
        <div class="transparencia-actas">
            <div class="transparencia-actas-header">
                <h3 class="transparencia-actas-titulo"><?php _e('Actas y Sesiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <?php if (empty($actas)): ?>
                <div class="transparencia-vacio">
                    <?php _e('No hay actas publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php else: ?>
                <?php foreach ($actas as $acta): ?>
                <div class="transparencia-acta-item">
                    <div class="transparencia-acta-icono">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="transparencia-acta-contenido">
                        <div class="transparencia-acta-titulo-item">
                            <?php echo esc_html($acta['nombre_organo']); ?>
                            <?php if ($acta['numero_sesion']): ?>
                                - <?php echo esc_html($acta['numero_sesion']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="transparencia-acta-meta">
                            <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($acta['fecha_sesion']); ?></span>
                            <span class="transparencia-estado transparencia-estado-<?php echo esc_attr($acta['tipo_sesion']); ?>">
                                <?php echo esc_html(ucfirst($acta['tipo_sesion'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="transparencia-acta-acciones">
                        <?php if ($acta['tiene_acta']): ?>
                        <button class="transparencia-btn transparencia-btn-primary transparencia-btn-sm">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <?php endif; ?>
                        <?php if ($acta['tiene_video']): ?>
                        <button class="transparencia-btn transparencia-btn-secondary transparencia-btn-sm">
                            <span class="dashicons dashicons-video-alt3"></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Contratos públicos
     */
    public function shortcode_contratos($atributos) {
        $atts = shortcode_atts([
            'limite' => 20,
        ], $atributos);

        $template_path = dirname(__FILE__) . '/templates/contratos.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return '<div class="transparencia-vacio">' . esc_html__('No hay vista de contratos disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    }

    /**
     * Shortcode: Grafico de presupuesto
     */
    public function shortcode_grafico_presupuesto($atributos) {
        $atributos = shortcode_atts([
            'ejercicio' => date('Y'),
            'tipo' => 'doughnut',
            'altura' => '350',
        ], $atributos);

        $resumen = $this->calcular_resumen_presupuesto($atributos['ejercicio']);

        ob_start();
        ?>
        <div class="transparencia-presupuesto-chart" style="height: <?php echo esc_attr($atributos['altura']); ?>px;">
            <canvas id="transparencia-chart-presupuesto"></canvas>
        </div>
        <div id="transparencia-datos-presupuesto" data-valores='<?php echo json_encode([
            'etiquetas' => array_column($resumen['por_capitulo']['gastos'], 'denominacion'),
            'valores' => array_column($resumen['por_capitulo']['gastos'], 'total'),
        ]); ?>'></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Resumen de presupuesto
     */
    public function shortcode_presupuesto_resumen($atributos) {
        $atts = shortcode_atts([
            'ejercicio' => date('Y'),
            'estilo' => 'default',
            'mostrar_enlace' => true,
        ], $atributos);

        $template_path = dirname(__FILE__) . '/templates/presupuesto-resumen.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return $this->shortcode_presupuesto_actual([
            'ejercicio' => $atts['ejercicio'],
            'mostrar_grafico' => 'false',
        ]);
    }

    /**
     * Shortcode: Indicadores
     */
    public function shortcode_indicadores($atributos) {
        $atributos = shortcode_atts([
            'mostrar_documentos' => 'true',
            'mostrar_solicitudes' => 'true',
        ], $atributos);

        $estadisticas = $this->calcular_estadisticas_generales();

        ob_start();
        ?>
        <div class="transparencia-presupuesto">
            <div class="transparencia-presupuesto-header">
                <h2 class="transparencia-presupuesto-titulo"><?php _e('Indicadores de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <div class="transparencia-presupuesto-resumen">
                <?php if ($atributos['mostrar_documentos'] === 'true'): ?>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Documentos Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor">
                        <?php echo number_format($estadisticas['documentos']['total']); ?>
                    </div>
                </div>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Visitas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor">
                        <?php echo number_format($estadisticas['documentos']['visitas']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($atributos['mostrar_solicitudes'] === 'true'): ?>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Solicitudes Recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor">
                        <?php echo number_format($estadisticas['solicitudes']['total']); ?>
                    </div>
                </div>
                <div class="transparencia-presupuesto-item">
                    <div class="transparencia-presupuesto-item-label"><?php _e('Tiempo Medio Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="transparencia-presupuesto-item-valor">
                        <?php echo $estadisticas['solicitudes']['promedio_dias_tramitacion']; ?> <?php _e('dias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ========================================================================
    // DASHBOARD WIDGETS
    // ========================================================================

    /**
     * Agregar widgets al dashboard
     */
    public function add_dashboard_widgets() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'transparencia_solicitudes_widget',
            __('Solicitudes de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'render_dashboard_widget']
        );
    }

    /**
     * Renderizar widget del dashboard
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        $solicitudes_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'admitida', 'en_tramite')"
        );

        $solicitudes_proximas_vencer = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_solicitudes
             WHERE estado IN ('recibida', 'admitida', 'en_tramite')
             AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 5 DAY)"
        );

        echo '<p><strong>' . __('Solicitudes pendientes:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . $solicitudes_pendientes . '</p>';
        if ($solicitudes_proximas_vencer > 0) {
            echo '<p style="color: #d63638;"><strong>' . __('Proximas a vencer:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . $solicitudes_proximas_vencer . '</p>';
        }
    }

    /**
     * Agregar intervalos de cron personalizados
     */
    public function add_cron_schedules($schedules) {
        $schedules['twice_daily'] = [
            'interval' => 43200,
            'display' => __('Dos veces al dia', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $schedules;
    }

    // ========================================================================
    // ACCIONES DEL MODULO
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'consultar_datos' => [
                'description' => 'Consultar datos publicos de transparencia',
                'params' => ['categoria', 'periodo', 'entidad', 'termino'],
            ],
            'solicitar_informacion' => [
                'description' => 'Solicitar informacion publica',
                'params' => ['titulo', 'descripcion', 'categoria', 'email'],
            ],
            'presupuestos' => [
                'description' => 'Consultar presupuestos publicados',
                'params' => ['ejercicio', 'tipo'],
            ],
            'gastos' => [
                'description' => 'Consultar gastos y ejecucion presupuestaria',
                'params' => ['ejercicio', 'categoria', 'desde', 'hasta'],
            ],
            'actas' => [
                'description' => 'Consultar actas de sesiones',
                'params' => ['tipo_organo', 'desde', 'hasta'],
            ],
            'ver_indicadores' => [
                'description' => 'Ver indicadores de gestion y estadisticas',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'consultar_datos',
            'documentos' => 'consultar_datos',
            'buscar' => 'consultar_datos',
            'crear' => 'solicitar_informacion',
            'solicitar' => 'solicitar_informacion',
            'mis_items' => 'consultar_datos',
            'mis-solicitudes' => 'consultar_datos',
            'indicadores' => 'ver_indicadores',
            'foro' => 'foro_documento',
            'chat' => 'chat_documento',
            'multimedia' => 'multimedia_documento',
            'red-social' => 'red_social_documento',
            'red_social' => 'red_social_documento',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error' => __('La vista solicitada no está disponible en Transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Accion: Consultar datos publicos
     */
    private function action_consultar_datos($parametros) {
        $resultado = $this->obtener_documentos_publicos($parametros);
        return ['success' => true, 'datos' => $resultado['documentos']];
    }

    private function resolve_contextual_documento($parametros = []) {
        $documento_id = absint(
            $parametros['documento_id']
            ?? $parametros['id']
            ?? $_GET['documento_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$documento_id) {
            return null;
        }

        $documento = $this->obtener_documento_por_id($documento_id);
        if (!$documento) {
            return null;
        }

        return [
            'id' => (int) $documento['id'],
            'titulo' => (string) $documento['titulo'],
            'descripcion' => (string) ($documento['descripcion'] ?? ''),
        ];
    }

    private function action_foro_documento($parametros) {
        $documento = $this->resolve_contextual_documento($parametros);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su foro.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del documento', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>'
            . '<p>' . esc_html($documento['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="documento_transparencia" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_chat_documento($parametros) {
        $documento = $this->resolve_contextual_documento($parametros);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su chat.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este documento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del documento', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?documento_id=' . absint($documento['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="documento_transparencia" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_documento($parametros) {
        $documento = $this->resolve_contextual_documento($parametros);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver sus archivos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del documento', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?documento_id=' . absint($documento['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="documento_transparencia" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_documento($parametros) {
        $documento = $this->resolve_contextual_documento($parametros);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su actividad social.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este documento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del documento', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?documento_id=' . absint($documento['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="documento_transparencia" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    /**
     * Accion: Solicitar informacion
     */
    private function action_solicitar_informacion($parametros) {
        return $this->crear_solicitud_informacion($parametros);
    }

    /**
     * Accion: Presupuestos
     */
    private function action_presupuestos($parametros) {
        $ejercicio = $parametros['ejercicio'] ?? date('Y');
        $tipo = $parametros['tipo'] ?? null;

        $presupuestos = $this->obtener_presupuestos($ejercicio, $tipo);
        $resumen = $this->calcular_resumen_presupuesto($ejercicio);

        return ['success' => true, 'presupuestos' => $presupuestos, 'resumen' => $resumen];
    }

    /**
     * Accion: Gastos
     */
    private function action_gastos($parametros) {
        $gastos = $this->obtener_gastos($parametros);
        return ['success' => true, 'gastos' => $gastos];
    }

    /**
     * Accion: Actas
     */
    private function action_actas($parametros) {
        $actas = $this->obtener_actas($parametros);
        return ['success' => true, 'actas' => $actas];
    }

    /**
     * Accion: Ver indicadores
     */
    private function action_ver_indicadores($parametros) {
        $estadisticas = $this->calcular_estadisticas_generales();
        return ['success' => true, 'indicadores' => $estadisticas];
    }

    // ========================================================================
    // COMPONENTES WEB
    // ========================================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero_transparencia' => [
                'label' => __('Hero Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-visibility',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Acceso a la informacion publica', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/hero',
            ],
            'datos_publicos_grid' => [
                'label' => __('Grid de Datos Publicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-media-spreadsheet',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Datos Publicos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                    'categoria' => ['type' => 'text', 'default' => ''],
                ],
                'template' => 'transparencia/grid',
            ],
            'indicadores_widget' => [
                'label' => __('Widget de Indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'widgets',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mostrar_graficos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/indicadores',
            ],
            'presupuesto_widget' => [
                'label' => __('Widget de Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'widgets',
                'icon' => 'dashicons-chart-pie',
                'fields' => [
                    'ejercicio' => ['type' => 'text', 'default' => date('Y')],
                    'mostrar_grafico' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/presupuesto',
            ],
            'solicitud_form' => [
                'label' => __('Formulario de Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'forms',
                'icon' => 'dashicons-feedback',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Solicitar Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mostrar_info' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'transparencia/solicitud',
            ],
        ];
    }

    // ========================================================================
    // TOOL DEFINITIONS
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'consultar_transparencia',
                'description' => 'Consultar datos publicos del portal de transparencia',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoria (presupuestos, contratos, subvenciones, normativa, actas, personal, indicadores)'],
                        'periodo' => ['type' => 'string', 'description' => 'Periodo o ejercicio'],
                        'termino' => ['type' => 'string', 'description' => 'Termino de busqueda'],
                    ],
                ],
            ],
            [
                'name' => 'solicitar_informacion_publica',
                'description' => 'Crear solicitud de acceso a informacion publica',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Titulo de la solicitud'],
                        'descripcion' => ['type' => 'string', 'description' => 'Descripcion detallada'],
                        'email' => ['type' => 'string', 'description' => 'Email para respuesta'],
                    ],
                    'required' => ['titulo', 'descripcion', 'email'],
                ],
            ],
            [
                'name' => 'ver_presupuesto',
                'description' => 'Consultar presupuesto y ejecucion presupuestaria',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejercicio' => ['type' => 'string', 'description' => 'Ejercicio presupuestario (ano)'],
                    ],
                ],
            ],
            [
                'name' => 'ver_gastos',
                'description' => 'Consultar gastos publicos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'ejercicio' => ['type' => 'string', 'description' => 'Ejercicio'],
                        'categoria' => ['type' => 'string', 'description' => 'Categoria de gasto'],
                    ],
                ],
            ],
        ];
    }

    // ========================================================================
    // KNOWLEDGE BASE
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Portal de Transparencia**

Sistema completo de acceso a informacion publica y rendicion de cuentas.

**Categorias de informacion publica:**
- **Presupuestos**: Presupuestos anuales, ejecucion presupuestaria, liquidaciones, modificaciones de credito
- **Gastos**: Detalle de gastos, pagos a proveedores, facturas
- **Contratos**: Contratos publicos, licitaciones, adjudicaciones, menores
- **Subvenciones**: Subvenciones concedidas y recibidas, convenios
- **Normativa**: Ordenanzas, reglamentos, bandos, acuerdos
- **Actas**: Actas de pleno, juntas de gobierno, comisiones informativas
- **Personal**: Plantilla, retribuciones, RPT, oferta de empleo
- **Indicadores**: Indicadores de gestion, calidad de servicios

**Derecho de acceso a la informacion:**
- Regulado por Ley 19/2013 de Transparencia
- Toda persona puede solicitar informacion sin motivar
- Plazo maximo de respuesta: 30 dias habiles (prorrogable)
- La denegacion debe ser motivada
- Silencio administrativo: negativo

**Proceso de solicitud:**
1. Identificar la informacion necesaria
2. Cumplimentar formulario de solicitud
3. Recibir numero de expediente
4. Seguimiento del estado de la solicitud
5. Recepcion de respuesta o resolucion

**Limites al derecho de acceso:**
- Seguridad nacional y defensa
- Prevencion e investigacion de delitos
- Proteccion de datos personales
- Secreto profesional y propiedad intelectual
- Procesos de decision en curso

**Publicidad activa:**
- Informacion institucional y organizativa
- Informacion de relevancia juridica
- Informacion economica, presupuestaria y estadistica
KNOWLEDGE;
    }

    // ========================================================================
    // PANEL ADMINISTRACION UNIFICADO
    // ========================================================================

    /**
     * Contar solicitudes pendientes para badge en menu
     *
     * @return int
     */
    public function contar_solicitudes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_solicitudes)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'admitida', 'en_tramite')"
        );
    }

    /**
     * Renderizar dashboard de administracion
     */
    public function render_admin_dashboard() {
        $estadisticas = $this->calcular_estadisticas_generales();
        $solicitudes_pendientes = $this->contar_solicitudes_pendientes();
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Dashboard de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN), [
                [
                    'label' => __('Nuevo Documento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => $this->admin_page_url('transparencia-documentos') . '&action=nuevo',
                    'class' => 'button-primary',
                ],
            ]); ?>

            <?php if (method_exists($this, 'render_admin_module_hub')) : ?>
                <?php $this->render_admin_module_hub([
                    'description' => __('Acceso visible a documentos, configuración y al bloque principal de métricas y actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'stats_anchor' => '#transparencia-stats',
                    'extra_items' => [
                        [
                            'label' => __('Portal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'url' => home_url('/mi-portal/transparencia/'),
                            'icon' => 'dashicons-external',
                        ],
                    ],
                ]); ?>
            <?php endif; ?>

            <div id="transparencia-stats" class="flavor-dashboard-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-media-document"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($estadisticas['documentos']['total'] ?? 0); ?></span>
                        <span class="stat-label"><?php _e('Documentos Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card <?php echo $solicitudes_pendientes > 0 ? 'has-badge' : ''; ?>">
                    <span class="dashicons dashicons-format-status"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($solicitudes_pendientes); ?></span>
                        <span class="stat-label"><?php _e('Solicitudes Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-visibility"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($estadisticas['documentos']['visitas'] ?? 0); ?></span>
                        <span class="stat-label"><?php _e('Visitas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $estadisticas['solicitudes']['promedio_dias_tramitacion'] ?? 0; ?></span>
                        <span class="stat-label"><?php _e('Dias Promedio Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-admin-section">
                <h2><?php _e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <?php $this->render_dashboard_widget(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página dashboard con vista completa
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            $this->render_admin_dashboard();
        }
    }

    /**
     * Renderizar pagina de documentos
     */
    public function render_admin_documentos() {
        $accion = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listado';
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Documentos de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN), [
                [
                    'label' => __('Nuevo Documento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => $this->admin_page_url('transparencia-documentos') . '&action=nuevo',
                    'class' => 'button-primary',
                ],
            ]); ?>

            <?php
            $tabs_navegacion = [
                ['slug' => 'listado', 'label' => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['slug' => 'presupuestos', 'label' => __('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['slug' => 'contratos', 'label' => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['slug' => 'actas', 'label' => __('Actas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ['slug' => 'normativa', 'label' => __('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ];
            $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'listado';
            $this->render_page_tabs($tabs_navegacion, $tab_actual);
            ?>

            <div class="flavor-admin-content">
                <?php
                $parametros_documentos = ['limite' => 20, 'pagina' => 1];
                if ($tab_actual !== 'listado') {
                    $parametros_documentos['categoria'] = $tab_actual;
                }
                $documentos = $this->obtener_documentos_publicos($parametros_documentos);
                ?>

                <?php if (empty($documentos['documentos'])): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-media-document"></span>
                        <p><?php _e('No hay documentos en esta categoria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Visitas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos['documentos'] as $documento): ?>
                            <tr>
                                <td><strong><?php echo esc_html($documento['titulo']); ?></strong></td>
                                <td><?php echo esc_html($documento['categoria']); ?></td>
                                <td><?php echo esc_html($documento['fecha_publicacion']); ?></td>
                                <td><?php echo number_format($documento['visitas']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia&action=editar&id=' . $documento['id'])); ?>" class="button button-small"><?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                    <a href="<?php echo esc_url($documento['url']); ?>" target="_blank" class="button button-small"><?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar pagina de configuracion
     */
    public function render_admin_configuracion() {
        $configuracion_actual = $this->get_settings();
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Configuracion de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>

            <form method="post" action="">
                <?php wp_nonce_field('transparencia_config', 'transparencia_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Plazo respuesta (dias)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="number" name="dias_plazo_respuesta"
                                   value="<?php echo esc_attr($configuracion_actual['dias_plazo_respuesta']); ?>"
                                   min="1" max="90" class="small-text">
                            <p class="description"><?php _e('Dias habiles para responder solicitudes de informacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Solicitudes anonimas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="permite_solicitudes_anonimas" value="1"
                                       <?php checked($configuracion_actual['permite_solicitudes_anonimas']); ?>>
                                <?php _e('Permitir solicitudes sin identificacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="email" name="email_notificaciones"
                                   value="<?php echo esc_attr($configuracion_actual['email_notificaciones']); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Mostrar graficos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mostrar_graficos" value="1"
                                       <?php checked($configuracion_actual['mostrar_graficos']); ?>>
                                <?php _e('Habilitar visualizacion grafica de datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Documentos por pagina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="number" name="limite_documentos_por_pagina"
                                   value="<?php echo esc_attr($configuracion_actual['limite_documentos_por_pagina']); ?>"
                                   min="6" max="48" step="6" class="small-text">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Guardar Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Obtener estadisticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        $estadisticas = $this->calcular_estadisticas_generales();

        return [
            'documentos_publicados' => $estadisticas['documentos']['total'] ?? 0,
            'solicitudes_pendientes' => $this->contar_solicitudes_pendientes(),
            'visitas_mes' => $estadisticas['documentos']['visitas'] ?? 0,
            'tasa_respuesta' => round($estadisticas['solicitudes']['tasa_resolucion'] ?? 0, 1) . '%',
        ];
    }

    // ========================================================================
    // FAQS
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo acceder a los presupuestos?',
                'respuesta' => 'Los presupuestos estan disponibles en la seccion de presupuestos del portal. Puede consultar el presupuesto por ejercicio, ver el desglose por capitulos y la ejecucion presupuestaria.',
            ],
            [
                'pregunta' => 'Como solicito informacion que no esta publicada?',
                'respuesta' => 'Puede realizar una solicitud de acceso a informacion publica a traves del formulario. Recibira un numero de expediente y respuesta en un plazo maximo de 30 dias habiles.',
            ],
            [
                'pregunta' => 'Donde puedo ver los contratos publicos?',
                'respuesta' => 'Los contratos estan en la categoria de contratos, con detalle de objeto, adjudicatario, importe y duracion. Tambien puede acceder al perfil del contratante.',
            ],
            [
                'pregunta' => 'Puedo descargar las actas de las sesiones?',
                'respuesta' => 'Si, las actas aprobadas y publicadas estan disponibles para descarga. En algunos casos tambien hay grabaciones en video o audio de las sesiones.',
            ],
            [
                'pregunta' => 'Que plazo hay para responder mi solicitud?',
                'respuesta' => 'El plazo maximo es de 30 dias habiles desde la recepcion de la solicitud. En casos complejos puede prorrogarse otros 30 dias, previa notificacion.',
            ],
            [
                'pregunta' => 'Necesito identificarme para solicitar informacion?',
                'respuesta' => 'Depende de la configuracion. En general se requiere al menos un email para recibir la respuesta. Algunas solicitudes pueden requerir identificacion completa.',
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
            Flavor_Page_Creator::refresh_module_pages('transparencia');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('transparencia');
        if (!$pagina && !get_option('flavor_transparencia_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['transparencia']);
            update_option('flavor_transparencia_pages_created', 1, false);
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

        $tabla_documentos = $wpdb->prefix . 'flavor_transparencia_documentos';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_documentos)) {
            return $estadisticas;
        }

        // Documentos públicos
        $total_documentos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_documentos} WHERE estado = 'publicado'"
        );

        $estadisticas['documentos'] = [
            'icon' => 'dashicons-media-document',
            'valor' => $total_documentos,
            'label' => __('Documentos públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => 'blue',
        ];

        // Documentos recientes (último mes)
        $docs_recientes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_documentos}
             WHERE estado = 'publicado'
             AND fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        if ($docs_recientes > 0) {
            $estadisticas['docs_recientes'] = [
                'icon' => 'dashicons-calendar',
                'valor' => $docs_recientes,
                'label' => __('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'green',
            ];
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
                'title' => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'transparencia',
                'content' => '<h1>' . __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Accede a la información pública de nuestra organización. Consulta presupuestos, contratos, actas y toda la documentación disponible para los ciudadanos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="transparencia" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Solicitar Información', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'solicitar',
                'content' => '<h1>' . __('Solicitar Información', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Ejerce tu derecho de acceso a la información pública. Realiza una solicitud formal y recibe respuesta en el plazo establecido por la ley.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="transparencia" action="crear"]',
                'parent' => 'transparencia',
            ],
            [
                'title' => __('Mis Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mis-solicitudes',
                'content' => '<h1>' . __('Mis Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Consulta el estado de tus solicitudes de acceso a información pública y descarga las respuestas recibidas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="transparencia" action="mis_items"]',
                'parent' => 'transparencia',
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
            'module'   => 'transparencia',
            'title'    => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Acceso a información pública y rendición de cuentas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '🔍',
            'color'    => 'secondary', // Usa variable CSS --flavor-secondary del tema

            'database' => [
                'table'       => 'flavor_transparencia',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'required' => true],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN), 'options' => ['presupuestos', 'contratos', 'subvenciones', 'personal', 'actas', 'normativa']],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'documento'   => ['type' => 'file', 'label' => __('Documento', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'año'         => ['type' => 'number', 'label' => __('Año', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                'fecha_publicacion' => ['type' => 'date', 'label' => __('Fecha publicación', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ],

            'estados' => [
                'publicado'  => ['label' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '✅'],
                'borrador'   => ['label' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'gray', 'icon' => '📝'],
                'pendiente'  => ['label' => __('Pendiente revisión', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '⏳'],
                'archivado'  => ['label' => __('Archivado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'slate', 'icon' => '🗄️'],
            ],

            'stats' => [
                'documentos_publicados' => ['label' => __('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📄', 'color' => 'cyan'],
                'solicitudes_atendidas' => ['label' => __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📋', 'color' => 'blue'],
                'descargas_total'       => ['label' => __('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📥', 'color' => 'green'],
                'tiempo_respuesta'      => ['label' => __('Tiempo medio respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '⏱️', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'documento-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['año', 'fecha_publicacion'],
                'show_descarga' => true,
            ],

            'tabs' => [
                'documentos' => [
                    'label'   => __('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-media-document',
                    'content' => 'shortcode:transparencia_buscador_docs',
                    'public'  => true,
                ],
                'presupuestos' => [
                    'label'   => __('Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-chart-pie',
                    'content' => 'shortcode:transparencia_presupuestos',
                    'public'  => true,
                ],
                'gastos' => [
                    'label'   => __('Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-money-alt',
                    'content' => 'template:ultimos-gastos.php',
                    'public'  => true,
                ],
                'contratos' => [
                    'label'   => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-media-document',
                    'content' => 'shortcode:transparencia_contratos',
                    'public'  => true,
                ],
                'actas' => [
                    'label'   => __('Actas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-text-page',
                    'content' => 'template:actas.php',
                    'public'  => true,
                ],
                'indicadores' => [
                    'label'   => __('Indicadores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-chart-bar',
                    'content' => 'template:indicadores.php',
                    'public'  => true,
                ],
                'solicitar' => [
                    'label'      => __('Solicitar información', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:transparencia_solicitar',
                    'requires_login' => true,
                ],
                'mis-solicitudes' => [
                    'label'      => __('Mis solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'callback:render_tab_mis_solicitudes',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'fecha_publicacion',
                'order'      => 'DESC',
                'filterable' => ['categoria', 'año'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'documentos_recientes', 'graficos_presupuesto', 'solicitudes'],
                'actions' => [
                    'solicitar' => ['label' => __('Solicitar información', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📝', 'color' => 'cyan'],
                    'buscar'    => ['label' => __('Buscar documento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔍', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'buscador'         => true,
                'descargas'        => true,
                'solicitudes'      => true,
                'graficos'         => true,
                'historial'        => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-transparencia-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Transparencia_Dashboard_Tab')) {
                Flavor_Transparencia_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {

        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $capability = 'manage_options';

        // Evitar doble registro del slug cuando el registrador automático
        // de dashboards de módulos ya expone views/configuracion.php
        if (!class_exists('Flavor_Module_Dashboards_Registrar')) {
            add_submenu_page(
                null,
                __('Transparencia - Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $capability,
                'transparencia-configuracion',
                [$this, 'render_pagina_configuracion']
            );
        }

        add_submenu_page(
            null,
            __('Transparencia - Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'transparencia-datos',
            [$this, 'render_pagina_datos']
        );

        add_submenu_page(
            null,
            __('Transparencia - Informes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Informes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'transparencia-informes',
            [$this, 'render_pagina_informes']
        );

        add_submenu_page(
            null,
            __('Transparencia - Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'transparencia-publicar',
            [$this, 'render_pagina_publicar']
        );

        add_submenu_page(
            null,
            __('Transparencia - Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'transparencia-solicitudes',
            [$this, 'render_pagina_solicitudes']
        );
    }

    /**
     * Renderizar página de configuración
     */
    public function render_pagina_configuracion() {
        $views_path = dirname(__FILE__) . '/views/configuracion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Configuración del portal de transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de datos públicos
     */
    public function render_pagina_datos() {
        $views_path = dirname(__FILE__) . '/views/datos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Datos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Gestión de documentos y datos publicados en el portal de transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de informes
     */
    public function render_pagina_informes() {
        $views_path = dirname(__FILE__) . '/views/informes.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Informes de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Generación y gestión de informes de transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de publicar
     */
    public function render_pagina_publicar() {
        $views_path = dirname(__FILE__) . '/views/publicar.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Publicar Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Publicación de documentos en el portal de transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderizar página de solicitudes
     */
    public function render_pagina_solicitudes() {
        $views_path = dirname(__FILE__) . '/views/solicitudes.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Solicitudes de Información', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Gestión de solicitudes de acceso a información pública.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }
}

if (!class_exists('Flavor_Chat_Transparencia_Module', false)) {
    class_alias('Flavor_Platform_Transparencia_Module', 'Flavor_Chat_Transparencia_Module');
}
