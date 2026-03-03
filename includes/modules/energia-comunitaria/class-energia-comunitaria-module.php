<?php
/**
 * Modulo de Energia Comunitaria para Chat IA
 *
 * Gestion comunitaria de generacion, consumo, reparto y mantenimiento
 * de infraestructuras energeticas locales.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Energia_Comunitaria_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    public function __construct() {
        add_action('wp_ajax_energia_comunitaria_crear_instalacion', [$this, 'ajax_crear_instalacion']);
        add_action('wp_ajax_energia_comunitaria_crear_comunidad', [$this, 'ajax_crear_comunidad_energetica']);
        add_action('wp_ajax_energia_comunitaria_crear_participante', [$this, 'ajax_crear_participante']);
        add_action('wp_ajax_energia_comunitaria_cerrar_reparto', [$this, 'ajax_cerrar_reparto']);
        add_action('wp_ajax_energia_comunitaria_registrar_lectura', [$this, 'ajax_registrar_lectura']);
        add_action('wp_ajax_energia_comunitaria_reportar_incidencia', [$this, 'ajax_reportar_incidencia']);
        add_action('wp_ajax_energia_comunitaria_actualizar_liquidacion', [$this, 'ajax_actualizar_liquidacion']);
        add_action('admin_post_energia_comunitaria_exportar_liquidaciones', [$this, 'handle_exportar_liquidaciones_csv']);
        add_action('admin_post_energia_comunitaria_exportar_liquidacion', [$this, 'handle_exportar_liquidacion_csv']);

        $this->id = 'energia_comunitaria';
        $this->name = 'Energia Comunitaria';
        $this->description = 'Gestion comunitaria de instalaciones, produccion, consumo y soberania energetica local.';
        $this->icon = 'dashicons-lightbulb';
        $this->color = '#f59e0b';
        $this->category = 'sostenibilidad';

        parent::__construct();
    }

    public function can_activate() {
        global $wpdb;

        return Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_energia_comunidades');
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Energia Comunitaria no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }

        return '';
    }

    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_reparto_excedentes' => true,
            'permite_compra_colectiva' => true,
            'permite_autolecuras' => true,
            'factor_co2_kwh' => 0.23,
            'precio_referencia_kwh' => 0.18,
            'moneda' => 'EUR',
            'requiere_validacion_lecturas' => false,
            'capacidad_gestion' => 'edit_posts',
            'capacidad_reportes' => 'read',
            'capacidad_lecturas' => 'read',
        ];
    }

    protected function get_accepted_integrations() {
        return ['comunidades', 'eventos', 'biblioteca', 'multimedia', 'presupuestos_participativos', 'huella_ecologica'];
    }

    protected function get_integration_targets() {
        global $wpdb;

        return [
            [
                'type' => 'table',
                'table' => $wpdb->prefix . 'flavor_energia_comunidades',
                'context' => 'normal',
                'label' => __('Comunidad energetica', 'flavor-chat-ia'),
            ],
            [
                'type' => 'table',
                'table' => $wpdb->prefix . 'flavor_energia_instalaciones',
                'context' => 'side',
                'label' => __('Instalacion energetica', 'flavor-chat-ia'),
            ],
        ];
    }

    public function init() {
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        $this->registrar_en_panel_unificado();
        $this->inicializar_dashboard_tab();
    }

    public static function get_renderer_config() {
        return [
            'module' => 'energia-comunitaria',
            'title' => __('Energia Comunitaria', 'flavor-chat-ia'),
            'subtitle' => __('Produccion, consumo, reparto y soberania energetica local', 'flavor-chat-ia'),
            'icon' => '⚡',
            'color' => 'amber',
            'database' => [
                'table' => 'flavor_energia_comunidades',
                'primary_key' => 'id',
                'status_field' => 'estado',
                'order_by' => 'created_at DESC',
                'filter_fields' => ['modelo_reparto', 'tipo_instalacion_principal'],
            ],
            'fields' => [
                'titulo' => 'nombre',
                'descripcion' => 'descripcion',
                'estado' => 'estado',
                'categoria' => 'tipo_instalacion_principal',
            ],
            'stats' => [
                [
                    'label' => __('Comunidades energeticas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-groups',
                    'color' => '#f59e0b',
                    'count_where' => "estado = 'activa'",
                ],
                [
                    'label' => __('Instalaciones activas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-tools',
                    'color' => '#10b981',
                    'query' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}flavor_energia_instalaciones WHERE estado = 'activa'",
                ],
                [
                    'label' => __('Lecturas este mes', 'flavor-chat-ia'),
                    'icon' => 'dashicons-chart-area',
                    'color' => '#0ea5e9',
                    'query' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}flavor_energia_lecturas WHERE DATE_FORMAT(fecha_lectura, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
                ],
                [
                    'label' => __('Incidencias abiertas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-warning',
                    'color' => '#ef4444',
                    'query' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}flavor_energia_incidencias WHERE estado IN ('abierta', 'en_progreso')",
                ],
            ],
            'tabs' => [
                'panel' => [
                    'label' => __('Panel', 'flavor-chat-ia'),
                    'icon' => 'dashicons-chart-pie',
                    'content' => '[flavor_energia_dashboard]',
                ],
                'instalaciones' => [
                    'label' => __('Instalaciones', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-tools',
                    'content' => '[flavor_energia_instalaciones]',
                ],
                'reparto' => [
                    'label' => __('Reparto', 'flavor-chat-ia'),
                    'icon' => 'dashicons-randomize',
                    'content' => '[flavor_energia_balance]',
                    'requires_login' => true,
                ],
                'cierres' => [
                    'label' => __('Cierres', 'flavor-chat-ia'),
                    'icon' => 'dashicons-archive',
                    'content' => '[flavor_energia_cierres]',
                    'requires_login' => true,
                ],
                'liquidaciones' => [
                    'label' => __('Liquidaciones', 'flavor-chat-ia'),
                    'icon' => 'dashicons-media-spreadsheet',
                    'content' => '[flavor_energia_liquidaciones]',
                    'requires_login' => true,
                ],
                'participantes' => [
                    'label' => __('Participantes', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-users',
                    'content' => '[flavor_energia_participantes]',
                    'requires_login' => true,
                ],
                'mantenimiento' => [
                    'label' => __('Mantenimiento', 'flavor-chat-ia'),
                    'icon' => 'dashicons-hammer',
                    'content' => '[flavor_energia_mantenimiento]',
                    'requires_login' => true,
                ],
                'nueva-comunidad' => [
                    'label' => __('Nueva comunidad energetica', 'flavor-chat-ia'),
                    'icon' => 'dashicons-groups',
                    'content' => '[flavor_energia_form_comunidad]',
                    'requires_login' => true,
                ],
                'nueva-instalacion' => [
                    'label' => __('Nueva instalacion', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus-alt',
                    'content' => '[flavor_energia_form_instalacion]',
                    'requires_login' => true,
                ],
                'registrar-lectura' => [
                    'label' => __('Registrar lectura', 'flavor-chat-ia'),
                    'icon' => 'dashicons-chart-line',
                    'content' => '[flavor_energia_form_lectura]',
                    'requires_login' => true,
                ],
                'nuevo-participante' => [
                    'label' => __('Nuevo participante', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus-alt2',
                    'content' => '[flavor_energia_form_participante]',
                    'requires_login' => true,
                ],
                'cerrar-reparto' => [
                    'label' => __('Cerrar reparto', 'flavor-chat-ia'),
                    'icon' => 'dashicons-saved',
                    'content' => '[flavor_energia_form_cierre]',
                    'requires_login' => true,
                ],
                'proyectos' => [
                    'label' => __('Proyectos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-portfolio',
                    'content' => '[flavor_energia_proyectos]',
                ],
                'comunidad' => [
                    'label' => __('Comunidad', 'flavor-chat-ia'),
                    'icon' => 'dashicons-groups',
                    'content' => '[comunidades_listado categoria="medioambiente" limite="6"]',
                ],
            ],
            'archive' => [
                'columns' => 3,
                'per_page' => 12,
                'show_filters' => true,
                'show_search' => true,
            ],
            'dashboard' => [
                'show_stats' => true,
                'show_actions' => true,
                'actions' => [
                    'registrar_lectura' => ['label' => __('Registrar lectura', 'flavor-chat-ia'), 'icon' => '📈', 'color' => 'amber'],
                    'reportar_incidencia' => ['label' => __('Reportar incidencia', 'flavor-chat-ia'), 'icon' => '🛠️', 'color' => 'red'],
                ],
            ],
        ];
    }

    public function maybe_create_tables() {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_energia_comunidades')) {
            $this->create_tables();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_energia_participantes')) {
            $this->create_tables();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_energia_repartos_cierre')) {
            $this->create_tables();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_energia_liquidaciones')) {
            $this->create_tables();
            return;
        }

        $tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';
        $tiene_fecha_notificacion = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_liquidaciones} LIKE 'fecha_notificacion'");
        $tiene_fecha_aceptacion = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_liquidaciones} LIKE 'fecha_aceptacion'");

        if (!$tiene_fecha_notificacion || !$tiene_fecha_aceptacion) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';
        $tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
        $tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';
        $tabla_participantes = $wpdb->prefix . 'flavor_energia_participantes';
        $tabla_cierres = $wpdb->prefix . 'flavor_energia_repartos_cierre';
        $tabla_cierres_detalle = $wpdb->prefix . 'flavor_energia_repartos_detalle';
        $tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';

        dbDelta("CREATE TABLE {$tabla_comunidades} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            creador_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(190) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo_instalacion_principal varchar(50) DEFAULT 'solar',
            modelo_reparto varchar(50) DEFAULT 'proporcional',
            potencia_kw decimal(10,2) DEFAULT 0,
            bateria_kwh decimal(10,2) DEFAULT 0,
            estado varchar(30) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_instalaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            energia_comunidad_id bigint(20) unsigned NOT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(190) NOT NULL,
            tipo varchar(50) DEFAULT 'solar',
            potencia_kw decimal(10,2) DEFAULT 0,
            bateria_kwh decimal(10,2) DEFAULT 0,
            ubicacion varchar(190) DEFAULT NULL,
            estado varchar(30) DEFAULT 'activa',
            ultima_revision datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY energia_comunidad_id (energia_comunidad_id)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_lecturas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instalacion_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            energia_generada_kwh decimal(10,2) DEFAULT 0,
            energia_consumida_kwh decimal(10,2) DEFAULT 0,
            excedente_kwh decimal(10,2) DEFAULT 0,
            bateria_porcentaje decimal(5,2) DEFAULT 0,
            fecha_lectura datetime NOT NULL,
            origen varchar(30) DEFAULT 'manual',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY instalacion_id (instalacion_id),
            KEY fecha_lectura (fecha_lectura)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_incidencias} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instalacion_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(190) NOT NULL,
            descripcion text DEFAULT NULL,
            prioridad varchar(20) DEFAULT 'media',
            estado varchar(30) DEFAULT 'abierta',
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY instalacion_id (instalacion_id),
            KEY estado (estado)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_participantes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            energia_comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(190) NOT NULL,
            rol varchar(50) DEFAULT 'hogar',
            coeficiente_reparto decimal(8,4) DEFAULT 1.0000,
            consumo_base_kwh decimal(10,2) DEFAULT 0,
            estado varchar(30) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY energia_comunidad_id (energia_comunidad_id),
            KEY user_id (user_id),
            KEY estado (estado)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_cierres} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            energia_comunidad_id bigint(20) unsigned NOT NULL,
            periodo varchar(7) NOT NULL,
            periodo_inicio date NOT NULL,
            periodo_fin date NOT NULL,
            kwh_generados decimal(10,2) DEFAULT 0,
            kwh_consumidos decimal(10,2) DEFAULT 0,
            excedente_kwh decimal(10,2) DEFAULT 0,
            autosuficiencia_pct decimal(8,2) DEFAULT 0,
            ahorro_estimado_eur decimal(10,2) DEFAULT 0,
            creado_por bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_periodo (energia_comunidad_id, periodo),
            KEY energia_comunidad_id (energia_comunidad_id),
            KEY periodo (periodo)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_cierres_detalle} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cierre_id bigint(20) unsigned NOT NULL,
            participante_id bigint(20) unsigned NOT NULL,
            participante_nombre varchar(190) NOT NULL,
            coeficiente_reparto decimal(8,4) DEFAULT 0,
            ratio_reparto_pct decimal(8,2) DEFAULT 0,
            kwh_asignados decimal(10,2) DEFAULT 0,
            ahorro_estimado_eur decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY cierre_id (cierre_id),
            KEY participante_id (participante_id)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$tabla_liquidaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cierre_id bigint(20) unsigned NOT NULL,
            detalle_id bigint(20) unsigned NOT NULL,
            energia_comunidad_id bigint(20) unsigned NOT NULL,
            participante_id bigint(20) unsigned NOT NULL,
            participante_nombre varchar(190) NOT NULL,
            periodo varchar(7) NOT NULL,
            referencia varchar(60) NOT NULL,
            kwh_liquidados decimal(10,2) DEFAULT 0,
            precio_kwh decimal(10,4) DEFAULT 0,
            importe_ahorro_eur decimal(10,2) DEFAULT 0,
            estado varchar(30) DEFAULT 'generada',
            fecha_notificacion datetime DEFAULT NULL,
            fecha_aceptacion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY detalle_id (detalle_id),
            KEY cierre_id (cierre_id),
            KEY energia_comunidad_id (energia_comunidad_id),
            KEY participante_id (participante_id),
            KEY periodo (periodo)
        ) {$charset_collate};");
    }

    private function can_manage_module() {
        return current_user_can($this->get_setting('capacidad_gestion', 'edit_posts')) || current_user_can('manage_options');
    }

    private function can_submit_reports() {
        return current_user_can($this->get_setting('capacidad_reportes', 'read')) || current_user_can('manage_options');
    }

    private function can_submit_readings() {
        return current_user_can($this->get_setting('capacidad_lecturas', 'read')) || current_user_can('manage_options');
    }

    private function get_energia_comunidad($energia_comunidad_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_comunidades';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $energia_comunidad_id));
    }

    private function get_instalacion($instalacion_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $instalacion_id));
    }

    private function user_can_manage_energia_comunidad($energia_comunidad_id) {
        if ($this->can_manage_module()) {
            return true;
        }

        $comunidad = $this->get_energia_comunidad($energia_comunidad_id);
        if (!$comunidad) {
            return false;
        }

        return (int) $comunidad->creador_id === get_current_user_id();
    }

    private function user_can_manage_instalacion($instalacion_id) {
        if ($this->can_manage_module()) {
            return true;
        }

        $instalacion = $this->get_instalacion($instalacion_id);
        if (!$instalacion) {
            return false;
        }

        if ((int) $instalacion->responsable_id === get_current_user_id()) {
            return true;
        }

        return $this->user_can_manage_energia_comunidad((int) $instalacion->energia_comunidad_id);
    }

    public function register_shortcodes() {
        add_shortcode('flavor_energia_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('flavor_energia_instalaciones', [$this, 'shortcode_instalaciones']);
        add_shortcode('flavor_energia_balance', [$this, 'shortcode_balance']);
        add_shortcode('flavor_energia_mantenimiento', [$this, 'shortcode_mantenimiento']);
        add_shortcode('flavor_energia_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('flavor_energia_form_comunidad', [$this, 'shortcode_form_comunidad']);
        add_shortcode('flavor_energia_form_instalacion', [$this, 'shortcode_form_instalacion']);
        add_shortcode('flavor_energia_participantes', [$this, 'shortcode_participantes']);
        add_shortcode('flavor_energia_form_participante', [$this, 'shortcode_form_participante']);
        add_shortcode('flavor_energia_cierres', [$this, 'shortcode_cierres']);
        add_shortcode('flavor_energia_liquidaciones', [$this, 'shortcode_liquidaciones']);
        add_shortcode('flavor_energia_form_cierre', [$this, 'shortcode_form_cierre']);
        add_shortcode('flavor_energia_form_lectura', [$this, 'shortcode_form_lectura']);
        add_shortcode('flavor_energia_form_incidencia', [$this, 'shortcode_form_incidencia']);
    }

    public function enqueue_assets() {
        $base_url = FLAVOR_CHAT_IA_URL . 'includes/modules/energia-comunitaria/assets/';
        $base_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/energia-comunitaria/assets/';
        $version = FLAVOR_CHAT_IA_VERSION;

        if (file_exists($base_path . 'css/energia-comunitaria.css')) {
            wp_register_style('flavor-energia-comunitaria', $base_url . 'css/energia-comunitaria.css', [], $version);
            wp_enqueue_style('flavor-energia-comunitaria');
        }

        if (file_exists($base_path . 'js/energia-comunitaria.js')) {
            wp_register_script('flavor-energia-comunitaria', $base_url . 'js/energia-comunitaria.js', ['jquery'], $version, true);
            wp_localize_script('flavor-energia-comunitaria', 'flavorEnergiaComunitaria', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor/v1/energia-comunitaria/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'formNonce' => wp_create_nonce('energia_comunitaria_nonce'),
            ]);
            wp_enqueue_script('flavor-energia-comunitaria');
        }
    }

    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/energia-comunitaria/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_dashboard'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_comunidades'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/instalaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_instalaciones'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/incidencias', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_post_incidencia'],
            'permission_callback' => function () {
                return is_user_logged_in() && $this->can_submit_reports();
            },
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/comunidades', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_comunidades'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/balance/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_balance_comunidad'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/participantes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_participantes_comunidad'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/cierres/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_cierres_comunidad'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/liquidaciones/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_liquidaciones_comunidad'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/liquidacion/(?P<id>\d+)/estado', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_post_liquidacion_estado'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);

        register_rest_route('flavor/v1', '/energia-comunitaria/liquidacion/(?P<id>\d+)/export', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_liquidacion_export'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);
    }

    public function rest_get_dashboard() {
        return rest_ensure_response($this->get_dashboard_data());
    }

    public function rest_get_instalaciones() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $items = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $items = $wpdb->get_results("SELECT * FROM {$tabla} ORDER BY created_at DESC LIMIT 20", ARRAY_A);
        }

        return rest_ensure_response([
            'items' => $items,
        ]);
    }

    public function rest_post_incidencia($request) {
        global $wpdb;

        $instalacion_id = absint($request->get_param('instalacion_id'));
        $titulo = sanitize_text_field((string) $request->get_param('titulo'));
        $descripcion = sanitize_textarea_field((string) $request->get_param('descripcion'));
        $prioridad = sanitize_text_field((string) $request->get_param('prioridad'));

        if ($instalacion_id <= 0 || $titulo === '') {
            return new WP_Error('energia_invalid_request', __('Faltan datos obligatorios.', 'flavor-chat-ia'), ['status' => 400]);
        }

        if (!$this->user_can_manage_instalacion($instalacion_id) && !$this->can_submit_reports()) {
            return new WP_Error('energia_forbidden', __('No tienes permisos para reportar sobre esta instalacion.', 'flavor-chat-ia'), ['status' => 403]);
        }

        $wpdb->insert(
            $wpdb->prefix . 'flavor_energia_incidencias',
            [
                'instalacion_id' => $instalacion_id,
                'user_id' => get_current_user_id(),
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'prioridad' => $prioridad ?: 'media',
                'estado' => 'abierta',
                'fecha_reporte' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        return rest_ensure_response([
            'success' => true,
            'message' => __('Incidencia registrada correctamente.', 'flavor-chat-ia'),
        ]);
    }

    public function rest_get_comunidades() {
        return rest_ensure_response([
            'items' => $this->get_comunidades_energeticas(),
        ]);
    }

    public function rest_get_balance_comunidad($request) {
        $comunidad_id = absint($request['id']);

        return rest_ensure_response($this->get_balance_comunidad_detallado($comunidad_id));
    }

    public function rest_get_participantes_comunidad($request) {
        $comunidad_id = absint($request['id']);

        return rest_ensure_response([
            'items' => $this->get_participantes_comunidad($comunidad_id),
        ]);
    }

    public function rest_get_cierres_comunidad($request) {
        $comunidad_id = absint($request['id']);

        return rest_ensure_response([
            'items' => $this->get_cierres_comunidad($comunidad_id),
        ]);
    }

    public function rest_get_liquidaciones_comunidad($request) {
        $comunidad_id = absint($request['id']);
        $filters = [
            'periodo' => sanitize_text_field((string) $request->get_param('periodo')),
            'estado' => sanitize_text_field((string) $request->get_param('estado')),
            'fecha_desde' => sanitize_text_field((string) $request->get_param('fecha_desde')),
            'fecha_hasta' => sanitize_text_field((string) $request->get_param('fecha_hasta')),
        ];
        $page = max(1, absint($request->get_param('page') ?: 1));
        $per_page = max(1, min(50, absint($request->get_param('per_page') ?: 20)));
        $result = $this->get_liquidaciones_comunidad_result($comunidad_id, $filters, $page, $per_page);

        return rest_ensure_response([
            'items' => $result['items'],
            'pagination' => [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
                'has_more' => $result['has_more'],
            ],
        ]);
    }

    public function rest_post_liquidacion_estado($request) {
        $liquidacion_id = absint($request['id']);
        $estado = sanitize_text_field((string) $request->get_param('estado'));

        if ($liquidacion_id <= 0 || $estado === '') {
            return new WP_Error('energia_invalid_request', __('Faltan datos obligatorios.', 'flavor-chat-ia'), ['status' => 400]);
        }

        $resultado = $this->actualizar_estado_liquidacion($liquidacion_id, $estado);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response($resultado);
    }

    public function rest_get_liquidacion_export($request) {
        $liquidacion_id = absint($request['id']);
        $liquidacion = $this->get_liquidacion($liquidacion_id);

        if (!$liquidacion) {
            return new WP_Error('energia_liquidacion_not_found', __('La liquidacion indicada no existe.', 'flavor-chat-ia'), ['status' => 404]);
        }

        if (!$this->user_can_manage_energia_comunidad((int) $liquidacion->energia_comunidad_id)) {
            return new WP_Error('energia_forbidden', __('No puedes exportar esta liquidacion.', 'flavor-chat-ia'), ['status' => 403]);
        }

        return rest_ensure_response([
            'success' => true,
            'filename' => $this->build_liquidacion_export_filename($liquidacion),
            'csv' => $this->build_liquidacion_csv_string($liquidacion),
        ]);
    }

    private function get_dashboard_data() {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';
        $tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
        $tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';

        $data = [
            'comunidades_activas' => 0,
            'instalaciones_activas' => 0,
            'kwh_generados_mes' => 0,
            'kwh_consumidos_mes' => 0,
            'incidencias_abiertas' => 0,
            'autosuficiencia_pct' => 0,
            'comunidades_vinculadas' => 0,
            'ahorro_estimado_mes' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            $data['comunidades_activas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comunidades} WHERE estado = 'activa'");
            $data['comunidades_vinculadas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comunidades} WHERE comunidad_id IS NOT NULL");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_instalaciones)) {
            $data['instalaciones_activas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_instalaciones} WHERE estado = 'activa'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_lecturas)) {
            $totales = $wpdb->get_row(
                "SELECT
                    COALESCE(SUM(energia_generada_kwh), 0) AS generada,
                    COALESCE(SUM(energia_consumida_kwh), 0) AS consumida
                 FROM {$tabla_lecturas}
                 WHERE DATE_FORMAT(fecha_lectura, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
            );

            $data['kwh_generados_mes'] = (float) ($totales->generada ?? 0);
            $data['kwh_consumidos_mes'] = (float) ($totales->consumida ?? 0);

            if ($data['kwh_consumidos_mes'] > 0) {
                $data['autosuficiencia_pct'] = round(($data['kwh_generados_mes'] / $data['kwh_consumidos_mes']) * 100, 1);
            }

            $data['ahorro_estimado_mes'] = round(
                min($data['kwh_generados_mes'], $data['kwh_consumidos_mes']) * (float) $this->get_setting('precio_referencia_kwh', 0.18),
                2
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            $data['incidencias_abiertas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_incidencias} WHERE estado IN ('abierta', 'en_progreso')");
        }

        return $data;
    }

    private function get_balance_comunidad_detallado($energia_comunidad_id) {
        global $wpdb;

        $tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
        $participantes = $this->get_participantes_comunidad($energia_comunidad_id);

        $data = [
            'energia_comunidad_id' => $energia_comunidad_id,
            'kwh_generados_mes' => 0,
            'kwh_consumidos_mes' => 0,
            'excedente_kwh_mes' => 0,
            'autosuficiencia_pct' => 0,
            'ahorro_estimado_eur' => 0,
            'participantes' => [],
        ];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_instalaciones) || !Flavor_Chat_Helpers::tabla_existe($tabla_lecturas)) {
            return $data;
        }

        $totales = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(l.energia_generada_kwh), 0) AS generada,
                COALESCE(SUM(l.energia_consumida_kwh), 0) AS consumida,
                COALESCE(SUM(l.excedente_kwh), 0) AS excedente
             FROM {$tabla_lecturas} l
             INNER JOIN {$tabla_instalaciones} i ON i.id = l.instalacion_id
             WHERE i.energia_comunidad_id = %d
               AND DATE_FORMAT(l.fecha_lectura, '%%Y-%%m') = DATE_FORMAT(CURDATE(), '%%Y-%%m')",
            $energia_comunidad_id
        ));

        $data['kwh_generados_mes'] = (float) ($totales->generada ?? 0);
        $data['kwh_consumidos_mes'] = (float) ($totales->consumida ?? 0);
        $data['excedente_kwh_mes'] = (float) ($totales->excedente ?? 0);

        if ($data['kwh_consumidos_mes'] > 0) {
            $data['autosuficiencia_pct'] = round(($data['kwh_generados_mes'] / $data['kwh_consumidos_mes']) * 100, 1);
        }

        $data['ahorro_estimado_eur'] = round(
            min($data['kwh_generados_mes'], $data['kwh_consumidos_mes']) * (float) $this->get_setting('precio_referencia_kwh', 0.18),
            2
        );

        $coeficiente_total = array_sum(array_map(function ($participante) {
            return (float) ($participante['coeficiente_reparto'] ?? 0);
        }, $participantes));

        foreach ($participantes as $participante) {
            $coeficiente = (float) ($participante['coeficiente_reparto'] ?? 0);
            $ratio = $coeficiente_total > 0 ? ($coeficiente / $coeficiente_total) : 0;
            $kwh_asignados = round($data['excedente_kwh_mes'] * $ratio, 2);

            $participante['ratio_reparto'] = round($ratio * 100, 2);
            $participante['kwh_asignados'] = $kwh_asignados;
            $participante['ahorro_estimado_eur'] = round($kwh_asignados * (float) $this->get_setting('precio_referencia_kwh', 0.18), 2);
            $data['participantes'][] = $participante;
        }

        return $data;
    }

    private function get_comunidades_energeticas() {
        global $wpdb;

        $tabla_energia = $wpdb->prefix . 'flavor_energia_comunidades';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_energia)) {
            return [];
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            return $wpdb->get_results(
                "SELECT ec.*, c.nombre AS comunidad_nombre
                 FROM {$tabla_energia} ec
                 LEFT JOIN {$tabla_comunidades} c ON c.id = ec.comunidad_id
                 ORDER BY ec.created_at DESC
                 LIMIT 20",
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            "SELECT * FROM {$tabla_energia} ORDER BY created_at DESC LIMIT 20",
            ARRAY_A
        );
    }

    private function get_comunidades_wp_options() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_comunidades';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT id, nombre, categoria, tipo
             FROM {$tabla}
             WHERE estado = 'activa'
             ORDER BY nombre ASC
             LIMIT 100"
        );
    }

    private function get_comunidades_energeticas_options() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_comunidades';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT id, nombre, modelo_reparto
             FROM {$tabla}
             WHERE estado = 'activa'
             ORDER BY nombre ASC
             LIMIT 100"
        );
    }

    private function get_participantes_comunidad($energia_comunidad_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_participantes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $energia_comunidad_id <= 0) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$tabla}
             WHERE energia_comunidad_id = %d
               AND estado = 'activo'
             ORDER BY coeficiente_reparto DESC, nombre ASC",
            $energia_comunidad_id
        ), ARRAY_A);
    }

    private function get_cierres_comunidad($energia_comunidad_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_repartos_cierre';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $energia_comunidad_id <= 0) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$tabla}
             WHERE energia_comunidad_id = %d
             ORDER BY periodo DESC, created_at DESC
             LIMIT 24",
            $energia_comunidad_id
        ), ARRAY_A);
    }

    private function get_cierre_detalle($cierre_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_repartos_detalle';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $cierre_id <= 0) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$tabla}
             WHERE cierre_id = %d
             ORDER BY ahorro_estimado_eur DESC, kwh_asignados DESC",
            $cierre_id
        ), ARRAY_A);
    }

    private function get_liquidaciones_comunidad($energia_comunidad_id, $filters = []) {
        $result = $this->get_liquidaciones_comunidad_result($energia_comunidad_id, $filters, 1, 100);

        return $result['items'];
    }

    private function get_liquidaciones_comunidad_result($energia_comunidad_id, $filters = [], $page = 1, $per_page = 100) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_liquidaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $energia_comunidad_id <= 0) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $per_page,
                'total_pages' => 0,
                'has_more' => false,
            ];
        }

        $where = ['energia_comunidad_id = %d'];
        $values = [$energia_comunidad_id];

        if (!empty($filters['periodo']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['periodo'])) {
            $where[] = 'periodo = %s';
            $values[] = $filters['periodo'];
        }

        if (!empty($filters['estado']) && in_array($filters['estado'], $this->get_allowed_liquidacion_statuses(), true)) {
            $where[] = 'estado = %s';
            $values[] = $filters['estado'];
        }

        if (!empty($filters['fecha_desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['fecha_desde'])) {
            $where[] = 'DATE(created_at) >= %s';
            $values[] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['fecha_hasta'])) {
            $where[] = 'DATE(created_at) <= %s';
            $values[] = $filters['fecha_hasta'];
        }

        $where_sql = implode(' AND ', $where);
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$tabla}
             WHERE {$where_sql}",
            ...$values
        ));

        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset = ($page - 1) * $per_page;
        $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;

        $sql = "SELECT *
            FROM {$tabla}
            WHERE {$where_sql}
            ORDER BY periodo DESC, created_at DESC, importe_ahorro_eur DESC
            LIMIT %d OFFSET %d";

        $items = $wpdb->get_results($wpdb->prepare($sql, ...array_merge($values, [$per_page, $offset])), ARRAY_A);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'has_more' => $page < $total_pages,
        ];
    }

    private function get_liquidacion($liquidacion_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_liquidaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $liquidacion_id <= 0) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $liquidacion_id
        ));
    }

    private function get_liquidaciones_cierre($cierre_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_liquidaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla) || $cierre_id <= 0) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$tabla}
             WHERE cierre_id = %d
             ORDER BY importe_ahorro_eur DESC, kwh_liquidados DESC",
            $cierre_id
        ), ARRAY_A);
    }

    private function build_liquidacion_reference($cierre_id, $participante_id, $periodo) {
        return sprintf(
            'EC-%s-C%04d-P%04d',
            str_replace('-', '', $periodo),
            $cierre_id,
            $participante_id
        );
    }

    private function get_allowed_liquidacion_statuses() {
        return ['generada', 'notificada', 'aceptada'];
    }

    private function build_liquidacion_export_filename($liquidacion) {
        $comunidad = $this->get_energia_comunidad((int) $liquidacion->energia_comunidad_id);
        $slug_comunidad = $comunidad ? sanitize_title($comunidad->nombre) : 'comunidad';

        return sprintf(
            'energia-liquidacion-%s-%s.csv',
            $slug_comunidad,
            sanitize_title($liquidacion->referencia)
        );
    }

    private function build_liquidacion_csv_string($liquidacion) {
        $stream = fopen('php://temp', 'r+');

        if (!$stream) {
            return '';
        }

        fputcsv($stream, ['Campo', 'Valor'], ';');
        fputcsv($stream, ['Periodo', $liquidacion->periodo], ';');
        fputcsv($stream, ['Referencia', $liquidacion->referencia], ';');
        fputcsv($stream, ['Participante', $liquidacion->participante_nombre], ';');
        fputcsv($stream, ['kWh liquidados', (float) $liquidacion->kwh_liquidados], ';');
        fputcsv($stream, ['Precio kWh', (float) $liquidacion->precio_kwh], ';');
        fputcsv($stream, ['Ahorro EUR', (float) $liquidacion->importe_ahorro_eur], ';');
        fputcsv($stream, ['Estado', $liquidacion->estado], ';');
        fputcsv($stream, ['Fecha notificacion', $liquidacion->fecha_notificacion ?: ''], ';');
        fputcsv($stream, ['Fecha aceptacion', $liquidacion->fecha_aceptacion ?: ''], ';');

        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }

    private function stream_liquidaciones_csv($energia_comunidad_id) {
        $liquidaciones = $this->get_liquidaciones_comunidad($energia_comunidad_id);

        if (empty($liquidaciones)) {
            return new WP_Error('energia_empty_export', __('No hay liquidaciones para exportar.', 'flavor-chat-ia'));
        }

        $comunidad = $this->get_energia_comunidad($energia_comunidad_id);
        $slug_comunidad = $comunidad ? sanitize_title($comunidad->nombre) : 'comunidad';
        $filename = sprintf('energia-liquidaciones-%s-%s.csv', $slug_comunidad, date('Ymd-His'));

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        if (!$output) {
            return new WP_Error('energia_export_failed', __('No se pudo abrir la salida de exportación.', 'flavor-chat-ia'));
        }

        fputcsv($output, ['Periodo', 'Referencia', 'Participante', 'kWh liquidados', 'Precio kWh', 'Ahorro EUR', 'Estado', 'Fecha notificacion', 'Fecha aceptacion'], ';');

        foreach ($liquidaciones as $liquidacion) {
            fputcsv($output, [
                $liquidacion['periodo'],
                $liquidacion['referencia'],
                $liquidacion['participante_nombre'],
                (float) $liquidacion['kwh_liquidados'],
                (float) $liquidacion['precio_kwh'],
                (float) $liquidacion['importe_ahorro_eur'],
                $liquidacion['estado'],
                $liquidacion['fecha_notificacion'] ?? '',
                $liquidacion['fecha_aceptacion'] ?? '',
            ], ';');
        }

        fclose($output);

        return true;
    }

    private function stream_liquidacion_csv($liquidacion_id) {
        $liquidacion = $this->get_liquidacion($liquidacion_id);

        if (!$liquidacion) {
            return new WP_Error('energia_liquidacion_not_found', __('La liquidacion indicada no existe.', 'flavor-chat-ia'));
        }

        $filename = $this->build_liquidacion_export_filename($liquidacion);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $this->build_liquidacion_csv_string($liquidacion);

        return true;
    }

    private function actualizar_estado_liquidacion($liquidacion_id, $estado) {
        global $wpdb;

        $liquidacion = $this->get_liquidacion($liquidacion_id);

        if (!$liquidacion) {
            return new WP_Error('energia_liquidacion_not_found', __('La liquidacion indicada no existe.', 'flavor-chat-ia'), ['status' => 404]);
        }

        if (!$this->user_can_manage_energia_comunidad((int) $liquidacion->energia_comunidad_id)) {
            return new WP_Error('energia_forbidden', __('No puedes gestionar esta liquidacion.', 'flavor-chat-ia'), ['status' => 403]);
        }

        if (!in_array($estado, $this->get_allowed_liquidacion_statuses(), true)) {
            return new WP_Error('energia_invalid_status', __('Estado de liquidacion no valido.', 'flavor-chat-ia'), ['status' => 400]);
        }

        $tabla = $wpdb->prefix . 'flavor_energia_liquidaciones';
        $data = ['estado' => $estado];
        $format = ['%s'];
        $now = current_time('mysql');

        if ($estado === 'generada') {
            $data['fecha_notificacion'] = null;
            $data['fecha_aceptacion'] = null;
            $format[] = '%s';
            $format[] = '%s';
        } elseif ($estado === 'notificada') {
            $data['fecha_notificacion'] = $liquidacion->fecha_notificacion ?: $now;
            $data['fecha_aceptacion'] = null;
            $format[] = '%s';
            $format[] = '%s';
        } elseif ($estado === 'aceptada') {
            $data['fecha_notificacion'] = $liquidacion->fecha_notificacion ?: $now;
            $data['fecha_aceptacion'] = $liquidacion->fecha_aceptacion ?: $now;
            $format[] = '%s';
            $format[] = '%s';
        }

        $actualizado = $wpdb->update(
            $tabla,
            $data,
            ['id' => $liquidacion_id],
            $format,
            ['%d']
        );

        if ($actualizado === false) {
            return new WP_Error('energia_liquidacion_update_failed', __('No se pudo actualizar la liquidacion.', 'flavor-chat-ia'), ['status' => 500]);
        }

        return [
            'success' => true,
            'message' => __('Estado de liquidacion actualizado correctamente.', 'flavor-chat-ia'),
            'liquidacion_id' => $liquidacion_id,
            'estado' => $estado,
        ];
    }

    private function cerrar_reparto_mensual($energia_comunidad_id, $periodo) {
        global $wpdb;

        if (!$this->user_can_manage_energia_comunidad($energia_comunidad_id)) {
            return new WP_Error('energia_forbidden', __('No puedes cerrar repartos en esta comunidad energética.', 'flavor-chat-ia'));
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return new WP_Error('energia_invalid_period', __('El periodo debe tener formato YYYY-MM.', 'flavor-chat-ia'));
        }

        $tabla_cierres = $wpdb->prefix . 'flavor_energia_repartos_cierre';
        $tabla_detalle = $wpdb->prefix . 'flavor_energia_repartos_detalle';
        $tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';

        if (
            !Flavor_Chat_Helpers::tabla_existe($tabla_cierres) ||
            !Flavor_Chat_Helpers::tabla_existe($tabla_detalle) ||
            !Flavor_Chat_Helpers::tabla_existe($tabla_liquidaciones)
        ) {
            return new WP_Error('energia_missing_tables', __('No existen las tablas de cierres energéticos.', 'flavor-chat-ia'));
        }

        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_cierres} WHERE energia_comunidad_id = %d AND periodo = %s",
            $energia_comunidad_id,
            $periodo
        ));

        if ($existente) {
            return new WP_Error('energia_duplicate_period', __('Ya existe un cierre para ese periodo.', 'flavor-chat-ia'));
        }

        $balance = $this->get_balance_comunidad_detallado($energia_comunidad_id);
        $periodo_inicio = $periodo . '-01';
        $periodo_fin = date('Y-m-t', strtotime($periodo_inicio));

        $insertado = $wpdb->insert(
            $tabla_cierres,
            [
                'energia_comunidad_id' => $energia_comunidad_id,
                'periodo' => $periodo,
                'periodo_inicio' => $periodo_inicio,
                'periodo_fin' => $periodo_fin,
                'kwh_generados' => $balance['kwh_generados_mes'],
                'kwh_consumidos' => $balance['kwh_consumidos_mes'],
                'excedente_kwh' => $balance['excedente_kwh_mes'],
                'autosuficiencia_pct' => $balance['autosuficiencia_pct'],
                'ahorro_estimado_eur' => $balance['ahorro_estimado_eur'],
                'creado_por' => get_current_user_id(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s']
        );

        if (!$insertado) {
            return new WP_Error('energia_insert_failed', __('No se pudo registrar el cierre mensual.', 'flavor-chat-ia'));
        }

        $cierre_id = (int) $wpdb->insert_id;

        $precio_referencia = (float) $this->get_setting('precio_referencia_kwh', 0.18);

        foreach ($balance['participantes'] as $participante) {
            $detalle_insertado = $wpdb->insert(
                $tabla_detalle,
                [
                    'cierre_id' => $cierre_id,
                    'participante_id' => (int) ($participante['id'] ?? 0),
                    'participante_nombre' => (string) ($participante['nombre'] ?? ''),
                    'coeficiente_reparto' => (float) ($participante['coeficiente_reparto'] ?? 0),
                    'ratio_reparto_pct' => (float) ($participante['ratio_reparto'] ?? 0),
                    'kwh_asignados' => (float) ($participante['kwh_asignados'] ?? 0),
                    'ahorro_estimado_eur' => (float) ($participante['ahorro_estimado_eur'] ?? 0),
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%f', '%f', '%f', '%f', '%s']
            );

            if (!$detalle_insertado) {
                continue;
            }

            $detalle_id = (int) $wpdb->insert_id;
            $participante_id = (int) ($participante['id'] ?? 0);

            $wpdb->insert(
                $tabla_liquidaciones,
                [
                    'cierre_id' => $cierre_id,
                    'detalle_id' => $detalle_id,
                    'energia_comunidad_id' => $energia_comunidad_id,
                    'participante_id' => $participante_id,
                    'participante_nombre' => (string) ($participante['nombre'] ?? ''),
                    'periodo' => $periodo,
                    'referencia' => $this->build_liquidacion_reference($cierre_id, $participante_id, $periodo),
                    'kwh_liquidados' => (float) ($participante['kwh_asignados'] ?? 0),
                    'precio_kwh' => $precio_referencia,
                    'importe_ahorro_eur' => (float) ($participante['ahorro_estimado_eur'] ?? 0),
                    'estado' => 'generada',
                    'fecha_notificacion' => null,
                    'fecha_aceptacion' => null,
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s']
            );
        }

        return [
            'success' => true,
            'cierre_id' => $cierre_id,
            'periodo' => $periodo,
            'balance' => $balance,
        ];
    }

    public function shortcode_dashboard() {
        $data = $this->get_dashboard_data();

        ob_start();
        ?>
        <div class="flavor-energia-panel">
            <h3><?php esc_html_e('Panel energetico comunitario', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-energia-kpis">
                <div><strong><?php echo esc_html(number_format_i18n($data['comunidades_activas'])); ?></strong><span><?php esc_html_e(' comunidades', 'flavor-chat-ia'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($data['instalaciones_activas'])); ?></strong><span><?php esc_html_e(' instalaciones', 'flavor-chat-ia'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($data['kwh_generados_mes'], 1)); ?></strong><span><?php esc_html_e(' kWh generados/mes', 'flavor-chat-ia'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($data['autosuficiencia_pct'], 1)); ?>%</strong><span><?php esc_html_e(' autosuficiencia', 'flavor-chat-ia'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($data['comunidades_vinculadas'])); ?></strong><span><?php esc_html_e(' comunidades vinculadas', 'flavor-chat-ia'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($data['ahorro_estimado_mes'], 2)); ?> €</strong><span><?php esc_html_e(' ahorro estimado/mes', 'flavor-chat-ia'); ?></span></div>
            </div>
            <p><?php esc_html_e('Este panel es la base para mostrar produccion, consumo, excedentes, ahorro y capacidad de bateria por comunidad energetica.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_instalaciones() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_energia = $wpdb->prefix . 'flavor_energia_comunidades';
        $instalaciones = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $instalaciones = $wpdb->get_results(
                "SELECT i.*, ec.nombre AS comunidad_energetica
                 FROM {$tabla} i
                 LEFT JOIN {$tabla_energia} ec ON ec.id = i.energia_comunidad_id
                 ORDER BY i.created_at DESC
                 LIMIT 12"
            );
        }

        ob_start();
        ?>
        <div class="flavor-energia-listado">
            <h3><?php esc_html_e('Instalaciones', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($instalaciones)) : ?>
                <p><?php esc_html_e('Todavia no hay instalaciones registradas.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <ul>
                    <?php foreach ($instalaciones as $instalacion) : ?>
                        <li>
                            <strong><?php echo esc_html($instalacion->nombre); ?></strong>
                            <span><?php echo esc_html($instalacion->tipo); ?></span>
                            <span><?php echo esc_html(number_format_i18n((float) $instalacion->potencia_kw, 2)); ?> kW</span>
                            <?php if (!empty($instalacion->comunidad_energetica)) : ?>
                                <span><?php echo esc_html($instalacion->comunidad_energetica); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_balance() {
        $comunidades = $this->get_comunidades_energeticas_options();
        $comunidad_id = absint($_GET['energia_comunidad_id'] ?? ($comunidades[0]->id ?? 0));
        $data = $this->get_balance_comunidad_detallado($comunidad_id);
        $excedente = $data['excedente_kwh_mes'];

        ob_start();
        ?>
        <div class="flavor-energia-balance">
            <h3><?php esc_html_e('Balance y reparto', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Base para reparto proporcional, por aportacion o por necesidades esenciales.', 'flavor-chat-ia'); ?></p>
            <?php if (!empty($comunidades)) : ?>
                <form method="get" class="flavor-form" style="margin:0 0 16px;">
                    <input type="hidden" name="energia_comunidad_id" value="">
                    <label for="energia-balance-comunidad"><?php esc_html_e('Comunidad energética', 'flavor-chat-ia'); ?></label>
                    <select id="energia-balance-comunidad" onchange="window.location.search='?energia_comunidad_id='+this.value;">
                        <?php foreach ($comunidades as $comunidad) : ?>
                            <option value="<?php echo esc_attr($comunidad->id); ?>" <?php selected((int) $comunidad_id, (int) $comunidad->id); ?>>
                                <?php echo esc_html($comunidad->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <p>
                <strong><?php esc_html_e('Generacion neta del mes:', 'flavor-chat-ia'); ?></strong>
                <?php echo esc_html(number_format_i18n($excedente, 1)); ?> kWh
            </p>
            <p>
                <strong><?php esc_html_e('Ahorro estimado:', 'flavor-chat-ia'); ?></strong>
                <?php echo esc_html(number_format_i18n($data['ahorro_estimado_eur'], 2)); ?> €
            </p>
            <?php if (!empty($data['participantes'])) : ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Participante', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Coeficiente', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('% reparto', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('kWh asignados', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Ahorro', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['participantes'] as $participante) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($participante['nombre'] ?? ''); ?></strong></td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($participante['coeficiente_reparto'] ?? 0), 2)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($participante['ratio_reparto'] ?? 0), 2)); ?>%</td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($participante['kwh_asignados'] ?? 0), 2)); ?> kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($participante['ahorro_estimado_eur'] ?? 0), 2)); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_mantenimiento() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_incidencias';
        $incidencias = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $incidencias = $wpdb->get_results("SELECT * FROM {$tabla} ORDER BY fecha_reporte DESC LIMIT 10");
        }

        ob_start();
        ?>
        <div class="flavor-energia-mantenimiento">
            <h3><?php esc_html_e('Mantenimiento e incidencias', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($incidencias)) : ?>
                <p><?php esc_html_e('No hay incidencias abiertas.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <ul>
                    <?php foreach ($incidencias as $incidencia) : ?>
                        <li>
                            <strong><?php echo esc_html($incidencia->titulo); ?></strong>
                            <span><?php echo esc_html($incidencia->prioridad); ?></span>
                            <span><?php echo esc_html($incidencia->estado); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_proyectos() {
        return '<div class="flavor-energia-proyectos"><h3>' . esc_html__('Proyectos energeticos', 'flavor-chat-ia') . '</h3><p>' . esc_html__('Espacio previsto para ampliaciones, compras colectivas, subvenciones y votaciones comunitarias.', 'flavor-chat-ia') . '</p></div>';
    }

    public function shortcode_participantes() {
        $comunidades = $this->get_comunidades_energeticas_options();
        $comunidad_id = absint($_GET['energia_comunidad_id'] ?? ($comunidades[0]->id ?? 0));
        $participantes = $this->get_participantes_comunidad($comunidad_id);

        ob_start();
        ?>
        <div class="flavor-energia-participantes">
            <h3><?php esc_html_e('Participantes y coeficientes', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($participantes)) : ?>
                <p><?php esc_html_e('Todavía no hay participantes registrados en esta comunidad energética.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <ul class="flavor-energia-participantes-list">
                    <?php foreach ($participantes as $participante) : ?>
                        <li>
                            <strong><?php echo esc_html($participante['nombre']); ?></strong>
                            <span><?php echo esc_html($participante['rol']); ?></span>
                            <span><?php echo esc_html(number_format_i18n((float) $participante['coeficiente_reparto'], 2)); ?></span>
                            <span><?php echo esc_html(number_format_i18n((float) $participante['consumo_base_kwh'], 1)); ?> kWh</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_cierres() {
        $comunidades = $this->get_comunidades_energeticas_options();
        $comunidad_id = absint($_GET['energia_comunidad_id'] ?? ($comunidades[0]->id ?? 0));
        $cierres = $this->get_cierres_comunidad($comunidad_id);
        $cierre_abierto_id = absint($_GET['cierre_id'] ?? ($cierres[0]['id'] ?? 0));
        $detalle = $cierre_abierto_id ? $this->get_cierre_detalle($cierre_abierto_id) : [];
        $liquidaciones = $cierre_abierto_id ? $this->get_liquidaciones_cierre($cierre_abierto_id) : [];

        ob_start();
        ?>
        <div class="flavor-energia-cierres">
            <h3><?php esc_html_e('Histórico de cierres mensuales', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($cierres)) : ?>
                <p><?php esc_html_e('Todavía no hay cierres mensuales guardados.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Generados', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Consumidos', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Excedente', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Ahorro', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cierres as $cierre) : ?>
                                <tr>
                                    <td><a href="<?php echo esc_url(add_query_arg(['energia_comunidad_id' => $comunidad_id, 'cierre_id' => $cierre['id']])); ?>"><?php echo esc_html($cierre['periodo']); ?></a></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $cierre['kwh_generados'], 2)); ?> kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) $cierre['kwh_consumidos'], 2)); ?> kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) $cierre['excedente_kwh'], 2)); ?> kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) $cierre['ahorro_estimado_eur'], 2)); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($detalle)) : ?>
                    <h4><?php esc_html_e('Detalle del cierre', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-table-responsive">
                        <table class="flavor-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Participante', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Coeficiente', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('% reparto', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('kWh', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Ahorro', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalle as $fila) : ?>
                                    <tr>
                                        <td><?php echo esc_html($fila['participante_nombre']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $fila['coeficiente_reparto'], 2)); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $fila['ratio_reparto_pct'], 2)); ?>%</td>
                                        <td><?php echo esc_html(number_format_i18n((float) $fila['kwh_asignados'], 2)); ?> kWh</td>
                                        <td><?php echo esc_html(number_format_i18n((float) $fila['ahorro_estimado_eur'], 2)); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php if (!empty($liquidaciones)) : ?>
                    <h4><?php esc_html_e('Liquidaciones generadas', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-table-responsive">
                        <table class="flavor-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Referencia', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Participante', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('kWh liquidados', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Importe ahorro', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liquidaciones as $liquidacion) : ?>
                                    <tr>
                                        <td><?php echo esc_html($liquidacion['referencia']); ?></td>
                                        <td><?php echo esc_html($liquidacion['participante_nombre']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $liquidacion['kwh_liquidados'], 2)); ?> kWh</td>
                                        <td><?php echo esc_html(number_format_i18n((float) $liquidacion['precio_kwh'], 4)); ?> €/kWh</td>
                                        <td><?php echo esc_html(number_format_i18n((float) $liquidacion['importe_ahorro_eur'], 2)); ?> €</td>
                                        <td><?php echo esc_html($liquidacion['estado']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_liquidaciones() {
        $comunidades = $this->get_comunidades_energeticas_options();
        $comunidad_id = absint($_GET['energia_comunidad_id'] ?? ($comunidades[0]->id ?? 0));
        $can_manage = is_user_logged_in() && ($comunidad_id > 0 ? $this->user_can_manage_energia_comunidad($comunidad_id) : $this->can_manage_module());
        $estados = $this->get_allowed_liquidacion_statuses();
        $filters = [
            'periodo' => sanitize_text_field($_GET['periodo'] ?? ''),
            'estado' => sanitize_text_field($_GET['estado'] ?? ''),
            'fecha_desde' => sanitize_text_field($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => sanitize_text_field($_GET['fecha_hasta'] ?? ''),
        ];
        $liquidaciones = $this->get_liquidaciones_comunidad($comunidad_id, $filters);
        $export_url = '';

        if ($can_manage && $comunidad_id > 0) {
            $export_url = wp_nonce_url(
                admin_url('admin-post.php?action=energia_comunitaria_exportar_liquidaciones&energia_comunidad_id=' . $comunidad_id),
                'energia_comunitaria_exportar_liquidaciones_' . $comunidad_id
            );
        }

        ob_start();
        ?>
        <div class="flavor-energia-liquidaciones">
            <h3><?php esc_html_e('Liquidaciones por participante', 'flavor-chat-ia'); ?></h3>
            <?php if ($export_url) : ?>
                <p><a class="flavor-btn flavor-btn-secondary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Exportar CSV', 'flavor-chat-ia'); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($comunidades)) : ?>
                <form method="get" class="flavor-form flavor-energia-filters" style="margin:0 0 16px;">
                    <div class="flavor-form-group">
                        <label for="energia-liquidaciones-comunidad"><?php esc_html_e('Comunidad energética', 'flavor-chat-ia'); ?></label>
                        <select id="energia-liquidaciones-comunidad" name="energia_comunidad_id">
                            <?php foreach ($comunidades as $comunidad) : ?>
                                <option value="<?php echo esc_attr($comunidad->id); ?>" <?php selected((int) $comunidad_id, (int) $comunidad->id); ?>>
                                    <?php echo esc_html($comunidad->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-group">
                        <label for="energia-liquidaciones-periodo"><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></label>
                        <input id="energia-liquidaciones-periodo" type="month" name="periodo" value="<?php echo esc_attr($filters['periodo']); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="energia-liquidaciones-estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                        <select id="energia-liquidaciones-estado" name="estado">
                            <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($estados as $estado) : ?>
                                <option value="<?php echo esc_attr($estado); ?>" <?php selected($filters['estado'], $estado); ?>>
                                    <?php echo esc_html($estado); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flavor-form-group">
                        <label for="energia-liquidaciones-fecha-desde"><?php esc_html_e('Desde', 'flavor-chat-ia'); ?></label>
                        <input id="energia-liquidaciones-fecha-desde" type="date" name="fecha_desde" value="<?php echo esc_attr($filters['fecha_desde']); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <label for="energia-liquidaciones-fecha-hasta"><?php esc_html_e('Hasta', 'flavor-chat-ia'); ?></label>
                        <input id="energia-liquidaciones-fecha-hasta" type="date" name="fecha_hasta" value="<?php echo esc_attr($filters['fecha_hasta']); ?>">
                    </div>
                    <div class="flavor-energia-filters-actions">
                        <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
                        <a class="flavor-btn flavor-btn-secondary" href="<?php echo esc_url(remove_query_arg(['periodo', 'estado', 'fecha_desde', 'fecha_hasta'])); ?>"><?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?></a>
                    </div>
                </form>
            <?php endif; ?>
            <?php if (empty($liquidaciones)) : ?>
                <p><?php esc_html_e('Todavía no hay liquidaciones generadas para esta comunidad energética.', 'flavor-chat-ia'); ?></p>
            <?php else : ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Referencia', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Participante', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('kWh', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Ahorro', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Notificada', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Aceptada', 'flavor-chat-ia'); ?></th>
                                <?php if ($can_manage) : ?>
                                    <th><?php esc_html_e('Exportar', 'flavor-chat-ia'); ?></th>
                                <?php endif; ?>
                                <?php if ($can_manage) : ?>
                                    <th><?php esc_html_e('Accion', 'flavor-chat-ia'); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liquidaciones as $liquidacion) : ?>
                                <tr>
                                    <td><?php echo esc_html($liquidacion['periodo']); ?></td>
                                    <td><?php echo esc_html($liquidacion['referencia']); ?></td>
                                    <td><?php echo esc_html($liquidacion['participante_nombre']); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $liquidacion['kwh_liquidados'], 2)); ?> kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) $liquidacion['precio_kwh'], 4)); ?> €/kWh</td>
                                    <td><?php echo esc_html(number_format_i18n((float) $liquidacion['importe_ahorro_eur'], 2)); ?> €</td>
                                    <td><span class="flavor-energia-status flavor-energia-status-<?php echo esc_attr(sanitize_html_class($liquidacion['estado'])); ?>"><?php echo esc_html($liquidacion['estado']); ?></span></td>
                                    <td><?php echo esc_html($liquidacion['fecha_notificacion'] ? mysql2date('d/m/Y H:i', $liquidacion['fecha_notificacion']) : ''); ?></td>
                                    <td><?php echo esc_html($liquidacion['fecha_aceptacion'] ? mysql2date('d/m/Y H:i', $liquidacion['fecha_aceptacion']) : ''); ?></td>
                                    <?php if ($can_manage) : ?>
                                        <?php $export_item_url = wp_nonce_url(admin_url('admin-post.php?action=energia_comunitaria_exportar_liquidacion&liquidacion_id=' . (int) $liquidacion['id']), 'energia_comunitaria_exportar_liquidacion_' . (int) $liquidacion['id']); ?>
                                        <td><a class="flavor-btn flavor-btn-secondary" href="<?php echo esc_url($export_item_url); ?>"><?php esc_html_e('CSV', 'flavor-chat-ia'); ?></a></td>
                                    <?php endif; ?>
                                    <?php if ($can_manage) : ?>
                                        <td>
                                            <form method="post" class="flavor-energia-inline-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                                                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                                                <input type="hidden" name="action" value="energia_comunitaria_actualizar_liquidacion">
                                                <input type="hidden" name="liquidacion_id" value="<?php echo esc_attr($liquidacion['id']); ?>">
                                                <select name="estado">
                                                    <?php foreach ($estados as $estado) : ?>
                                                        <option value="<?php echo esc_attr($estado); ?>" <?php selected($liquidacion['estado'], $estado); ?>>
                                                            <?php echo esc_html($estado); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="flavor-btn flavor-btn-secondary"><?php esc_html_e('Actualizar', 'flavor-chat-ia'); ?></button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_comunidad() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para crear comunidades energeticas.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_manage_module()) {
            return '<p>' . esc_html__('No tienes permisos para crear comunidades energeticas.', 'flavor-chat-ia') . '</p>';
        }

        $comunidades = $this->get_comunidades_wp_options();

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Crear comunidad energetica', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_crear_comunidad">

                <div class="flavor-form-group">
                    <label for="energia-comunidad-nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></label>
                    <input id="energia-comunidad-nombre" type="text" name="nombre" required>
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-descripcion"><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></label>
                    <textarea id="energia-comunidad-descripcion" name="descripcion" rows="4"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-vinculada"><?php esc_html_e('Comunidad social vinculada', 'flavor-chat-ia'); ?></label>
                    <select id="energia-comunidad-vinculada" name="comunidad_id">
                        <option value=""><?php esc_html_e('Sin vincular por ahora', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($comunidades as $comunidad) : ?>
                            <option value="<?php echo esc_attr($comunidad->id); ?>"><?php echo esc_html($comunidad->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-tipo"><?php esc_html_e('Tecnologia principal', 'flavor-chat-ia'); ?></label>
                    <select id="energia-comunidad-tipo" name="tipo_instalacion_principal">
                        <option value="solar"><?php esc_html_e('Solar', 'flavor-chat-ia'); ?></option>
                        <option value="eolica"><?php esc_html_e('Mini eolica', 'flavor-chat-ia'); ?></option>
                        <option value="mixta"><?php esc_html_e('Mixta', 'flavor-chat-ia'); ?></option>
                        <option value="bateria"><?php esc_html_e('Bateria compartida', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-reparto"><?php esc_html_e('Modelo de reparto', 'flavor-chat-ia'); ?></label>
                    <select id="energia-comunidad-reparto" name="modelo_reparto">
                        <option value="proporcional"><?php esc_html_e('Proporcional', 'flavor-chat-ia'); ?></option>
                        <option value="necesidades"><?php esc_html_e('Por necesidades', 'flavor-chat-ia'); ?></option>
                        <option value="mixto"><?php esc_html_e('Mixto', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-potencia"><?php esc_html_e('Potencia prevista kW', 'flavor-chat-ia'); ?></label>
                    <input id="energia-comunidad-potencia" type="number" name="potencia_kw" min="0" step="0.01">
                </div>

                <div class="flavor-form-group">
                    <label for="energia-comunidad-bateria"><?php esc_html_e('Bateria prevista kWh', 'flavor-chat-ia'); ?></label>
                    <input id="energia-comunidad-bateria" type="number" name="bateria_kwh" min="0" step="0.01">
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Crear comunidad energetica', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_instalacion() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para registrar instalaciones.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_manage_module()) {
            return '<p>' . esc_html__('No tienes permisos para registrar instalaciones.', 'flavor-chat-ia') . '</p>';
        }

        $comunidades = $this->get_comunidades_wp_options();
        $comunidades_energeticas = $this->get_comunidades_energeticas();

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Registrar instalacion energetica', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_crear_instalacion">

                <div class="flavor-form-group">
                    <label for="energia-comunidad-id"><?php esc_html_e('Comunidad energetica', 'flavor-chat-ia'); ?></label>
                    <select id="energia-comunidad-id" name="energia_comunidad_id" required>
                        <option value=""><?php esc_html_e('Selecciona una comunidad energetica', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($comunidades_energeticas as $item) : ?>
                            <option value="<?php echo esc_attr($item['id']); ?>">
                                <?php echo esc_html($item['nombre'] . (!empty($item['comunidad_nombre']) ? ' - ' . $item['comunidad_nombre'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="instalacion-nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></label>
                    <input id="instalacion-nombre" type="text" name="nombre" required>
                </div>

                <div class="flavor-form-group">
                    <label for="instalacion-tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label>
                    <select id="instalacion-tipo" name="tipo">
                        <option value="solar"><?php esc_html_e('Solar', 'flavor-chat-ia'); ?></option>
                        <option value="bateria"><?php esc_html_e('Bateria', 'flavor-chat-ia'); ?></option>
                        <option value="microred"><?php esc_html_e('Microred', 'flavor-chat-ia'); ?></option>
                        <option value="eolica"><?php esc_html_e('Mini eolica', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="instalacion-potencia"><?php esc_html_e('Potencia kW', 'flavor-chat-ia'); ?></label>
                    <input id="instalacion-potencia" type="number" name="potencia_kw" min="0" step="0.01">
                </div>

                <div class="flavor-form-group">
                    <label for="instalacion-bateria"><?php esc_html_e('Bateria kWh', 'flavor-chat-ia'); ?></label>
                    <input id="instalacion-bateria" type="number" name="bateria_kwh" min="0" step="0.01">
                </div>

                <div class="flavor-form-group">
                    <label for="instalacion-ubicacion"><?php esc_html_e('Ubicacion', 'flavor-chat-ia'); ?></label>
                    <input id="instalacion-ubicacion" type="text" name="ubicacion">
                </div>

                <?php if (!empty($comunidades)) : ?>
                    <p class="flavor-form-help"><?php esc_html_e('Las comunidades sociales existentes pueden vincularse al crear o editar la comunidad energetica principal.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Guardar instalacion', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_participante() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para registrar participantes.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_manage_module()) {
            return '<p>' . esc_html__('No tienes permisos para registrar participantes.', 'flavor-chat-ia') . '</p>';
        }

        $comunidades = $this->get_comunidades_energeticas_options();

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Registrar participante', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_crear_participante">

                <div class="flavor-form-group">
                    <label for="participante-comunidad-id"><?php esc_html_e('Comunidad energética', 'flavor-chat-ia'); ?></label>
                    <select id="participante-comunidad-id" name="energia_comunidad_id" required>
                        <option value=""><?php esc_html_e('Selecciona una comunidad', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($comunidades as $comunidad) : ?>
                            <option value="<?php echo esc_attr($comunidad->id); ?>"><?php echo esc_html($comunidad->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="participante-nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></label>
                    <input id="participante-nombre" type="text" name="nombre" required>
                </div>

                <div class="flavor-form-group">
                    <label for="participante-rol"><?php esc_html_e('Rol', 'flavor-chat-ia'); ?></label>
                    <select id="participante-rol" name="rol">
                        <option value="hogar"><?php esc_html_e('Hogar', 'flavor-chat-ia'); ?></option>
                        <option value="comercio"><?php esc_html_e('Comercio', 'flavor-chat-ia'); ?></option>
                        <option value="espacio_comun"><?php esc_html_e('Espacio común', 'flavor-chat-ia'); ?></option>
                        <option value="equipamiento"><?php esc_html_e('Equipamiento', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="participante-coeficiente"><?php esc_html_e('Coeficiente de reparto', 'flavor-chat-ia'); ?></label>
                    <input id="participante-coeficiente" type="number" name="coeficiente_reparto" min="0.01" step="0.01" value="1">
                </div>

                <div class="flavor-form-group">
                    <label for="participante-consumo"><?php esc_html_e('Consumo base kWh', 'flavor-chat-ia'); ?></label>
                    <input id="participante-consumo" type="number" name="consumo_base_kwh" min="0" step="0.01" value="0">
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Guardar participante', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_cierre() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para cerrar repartos.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_manage_module()) {
            return '<p>' . esc_html__('No tienes permisos para cerrar repartos.', 'flavor-chat-ia') . '</p>';
        }

        $comunidades = $this->get_comunidades_energeticas_options();
        $periodo_actual = date('Y-m');

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Cerrar reparto mensual', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_cerrar_reparto">

                <div class="flavor-form-group">
                    <label for="cierre-comunidad-id"><?php esc_html_e('Comunidad energética', 'flavor-chat-ia'); ?></label>
                    <select id="cierre-comunidad-id" name="energia_comunidad_id" required>
                        <option value=""><?php esc_html_e('Selecciona una comunidad', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($comunidades as $comunidad) : ?>
                            <option value="<?php echo esc_attr($comunidad->id); ?>"><?php echo esc_html($comunidad->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="cierre-periodo"><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></label>
                    <input id="cierre-periodo" type="month" name="periodo" value="<?php echo esc_attr($periodo_actual); ?>" required>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Cerrar y guardar reparto', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_lectura() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para registrar lecturas.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_submit_readings()) {
            return '<p>' . esc_html__('No tienes permisos para registrar lecturas.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $instalaciones = Flavor_Chat_Helpers::tabla_existe($tabla)
            ? $wpdb->get_results("SELECT id, nombre FROM {$tabla} WHERE estado = 'activa' ORDER BY nombre ASC LIMIT 100")
            : [];

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Registrar lectura energetica', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_registrar_lectura">

                <div class="flavor-form-group">
                    <label for="lectura-instalacion-id"><?php esc_html_e('Instalacion', 'flavor-chat-ia'); ?></label>
                    <select id="lectura-instalacion-id" name="instalacion_id" required>
                        <option value=""><?php esc_html_e('Selecciona una instalacion', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($instalaciones as $instalacion) : ?>
                            <option value="<?php echo esc_attr($instalacion->id); ?>"><?php echo esc_html($instalacion->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="lectura-generada"><?php esc_html_e('Energia generada kWh', 'flavor-chat-ia'); ?></label>
                    <input id="lectura-generada" type="number" name="energia_generada_kwh" min="0" step="0.01" required>
                </div>

                <div class="flavor-form-group">
                    <label for="lectura-consumida"><?php esc_html_e('Energia consumida kWh', 'flavor-chat-ia'); ?></label>
                    <input id="lectura-consumida" type="number" name="energia_consumida_kwh" min="0" step="0.01" required>
                </div>

                <div class="flavor-form-group">
                    <label for="lectura-bateria"><?php esc_html_e('Bateria %', 'flavor-chat-ia'); ?></label>
                    <input id="lectura-bateria" type="number" name="bateria_porcentaje" min="0" max="100" step="0.01">
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Guardar lectura', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function shortcode_form_incidencia() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesion para reportar incidencias.', 'flavor-chat-ia') . '</p>';
        }

        if (!$this->can_submit_reports()) {
            return '<p>' . esc_html__('No tienes permisos para reportar incidencias.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $instalaciones = Flavor_Chat_Helpers::tabla_existe($tabla)
            ? $wpdb->get_results("SELECT id, nombre FROM {$tabla} ORDER BY nombre ASC LIMIT 100")
            : [];

        ob_start();
        ?>
        <div class="flavor-energia-form-wrapper">
            <h3><?php esc_html_e('Reportar incidencia tecnica', 'flavor-chat-ia'); ?></h3>
            <form method="post" class="flavor-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php wp_nonce_field('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field'); ?>
                <input type="hidden" name="action" value="energia_comunitaria_reportar_incidencia">

                <div class="flavor-form-group">
                    <label for="incidencia-instalacion-id"><?php esc_html_e('Instalacion', 'flavor-chat-ia'); ?></label>
                    <select id="incidencia-instalacion-id" name="instalacion_id" required>
                        <option value=""><?php esc_html_e('Selecciona una instalacion', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($instalaciones as $instalacion) : ?>
                            <option value="<?php echo esc_attr($instalacion->id); ?>"><?php echo esc_html($instalacion->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="incidencia-titulo"><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></label>
                    <input id="incidencia-titulo" type="text" name="titulo" required>
                </div>

                <div class="flavor-form-group">
                    <label for="incidencia-descripcion"><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></label>
                    <textarea id="incidencia-descripcion" name="descripcion" rows="4"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="incidencia-prioridad"><?php esc_html_e('Prioridad', 'flavor-chat-ia'); ?></label>
                    <select id="incidencia-prioridad" name="prioridad">
                        <option value="baja"><?php esc_html_e('Baja', 'flavor-chat-ia'); ?></option>
                        <option value="media"><?php esc_html_e('Media', 'flavor-chat-ia'); ?></option>
                        <option value="alta"><?php esc_html_e('Alta', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary"><?php esc_html_e('Enviar incidencia', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public function ajax_crear_instalacion() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_manage_module()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $energia_comunidad_id = absint($_POST['energia_comunidad_id'] ?? 0);

        if ($energia_comunidad_id <= 0 || !$this->user_can_manage_energia_comunidad($energia_comunidad_id)) {
            wp_send_json_error(['message' => __('No puedes crear instalaciones en esa comunidad energetica.', 'flavor-chat-ia')]);
        }

        $wpdb->insert(
            $tabla,
            [
                'energia_comunidad_id' => $energia_comunidad_id,
                'responsable_id' => get_current_user_id(),
                'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                'tipo' => sanitize_text_field($_POST['tipo'] ?? 'solar'),
                'potencia_kw' => floatval($_POST['potencia_kw'] ?? 0),
                'bateria_kwh' => floatval($_POST['bateria_kwh'] ?? 0),
                'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
                'estado' => 'activa',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => __('Instalacion creada correctamente.', 'flavor-chat-ia'),
            'id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_crear_comunidad_energetica() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_manage_module()) {
            wp_send_json_error(['message' => __('No tienes permisos para crear comunidades energeticas.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_comunidades';

        $wpdb->insert(
            $tabla,
            [
                'comunidad_id' => absint($_POST['comunidad_id'] ?? 0) ?: null,
                'creador_id' => get_current_user_id(),
                'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
                'tipo_instalacion_principal' => sanitize_text_field($_POST['tipo_instalacion_principal'] ?? 'solar'),
                'modelo_reparto' => sanitize_text_field($_POST['modelo_reparto'] ?? 'proporcional'),
                'potencia_kw' => floatval($_POST['potencia_kw'] ?? 0),
                'bateria_kwh' => floatval($_POST['bateria_kwh'] ?? 0),
                'estado' => 'activa',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => __('Comunidad energetica creada correctamente.', 'flavor-chat-ia'),
            'id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_registrar_lectura() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_submit_readings()) {
            wp_send_json_error(['message' => __('No tienes permisos para registrar lecturas.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_lecturas';
        $instalacion_id = absint($_POST['instalacion_id'] ?? 0);
        $generada = floatval($_POST['energia_generada_kwh'] ?? 0);
        $consumida = floatval($_POST['energia_consumida_kwh'] ?? 0);

        if ($instalacion_id <= 0 || !$this->user_can_manage_instalacion($instalacion_id)) {
            wp_send_json_error(['message' => __('No puedes registrar lecturas en esa instalacion.', 'flavor-chat-ia')]);
        }

        $wpdb->insert(
            $tabla,
            [
                'instalacion_id' => $instalacion_id,
                'user_id' => get_current_user_id(),
                'energia_generada_kwh' => $generada,
                'energia_consumida_kwh' => $consumida,
                'excedente_kwh' => max(0, $generada - $consumida),
                'bateria_porcentaje' => floatval($_POST['bateria_porcentaje'] ?? 0),
                'fecha_lectura' => current_time('mysql'),
                'origen' => 'manual',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => __('Lectura registrada correctamente.', 'flavor-chat-ia'),
            'id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_crear_participante() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_manage_module()) {
            wp_send_json_error(['message' => __('No tienes permisos para registrar participantes.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_energia_participantes';
        $energia_comunidad_id = absint($_POST['energia_comunidad_id'] ?? 0);

        if ($energia_comunidad_id <= 0 || !$this->user_can_manage_energia_comunidad($energia_comunidad_id)) {
            wp_send_json_error(['message' => __('No puedes registrar participantes en esa comunidad energética.', 'flavor-chat-ia')]);
        }

        $wpdb->insert(
            $tabla,
            [
                'energia_comunidad_id' => $energia_comunidad_id,
                'user_id' => absint($_POST['user_id'] ?? 0) ?: null,
                'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                'rol' => sanitize_text_field($_POST['rol'] ?? 'hogar'),
                'coeficiente_reparto' => max(0.01, (float) ($_POST['coeficiente_reparto'] ?? 1)),
                'consumo_base_kwh' => max(0, (float) ($_POST['consumo_base_kwh'] ?? 0)),
                'estado' => 'activo',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s']
        );

        wp_send_json_success([
            'message' => __('Participante registrado correctamente.', 'flavor-chat-ia'),
            'id' => $wpdb->insert_id,
        ]);
    }

    public function ajax_cerrar_reparto() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_manage_module()) {
            wp_send_json_error(['message' => __('No tienes permisos para cerrar repartos.', 'flavor-chat-ia')]);
        }

        $resultado = $this->cerrar_reparto_mensual(
            absint($_POST['energia_comunidad_id'] ?? 0),
            sanitize_text_field($_POST['periodo'] ?? '')
        );

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Cierre mensual registrado correctamente.', 'flavor-chat-ia'),
            'cierre_id' => $resultado['cierre_id'],
            'periodo' => $resultado['periodo'],
        ]);
    }

    public function ajax_reportar_incidencia() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in() || !$this->can_submit_reports()) {
            wp_send_json_error(['message' => __('No tienes permisos para reportar incidencias.', 'flavor-chat-ia')]);
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('instalacion_id', absint($_POST['instalacion_id'] ?? 0));
        $request->set_param('titulo', sanitize_text_field($_POST['titulo'] ?? ''));
        $request->set_param('descripcion', sanitize_textarea_field($_POST['descripcion'] ?? ''));
        $request->set_param('prioridad', sanitize_text_field($_POST['prioridad'] ?? 'media'));

        $response = $this->rest_post_incidencia($request);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success($response->get_data());
    }

    public function ajax_actualizar_liquidacion() {
        check_ajax_referer('energia_comunitaria_nonce', 'energia_comunitaria_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No tienes permisos para gestionar liquidaciones.', 'flavor-chat-ia')]);
        }

        $resultado = $this->actualizar_estado_liquidacion(
            absint($_POST['liquidacion_id'] ?? 0),
            sanitize_text_field($_POST['estado'] ?? '')
        );

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success($resultado);
    }

    public function handle_exportar_liquidaciones_csv() {
        if (!is_user_logged_in()) {
            wp_die(__('Debes iniciar sesion para exportar liquidaciones.', 'flavor-chat-ia'));
        }

        $energia_comunidad_id = absint($_GET['energia_comunidad_id'] ?? 0);

        check_admin_referer('energia_comunitaria_exportar_liquidaciones_' . $energia_comunidad_id);

        if ($energia_comunidad_id <= 0 || !$this->user_can_manage_energia_comunidad($energia_comunidad_id)) {
            wp_die(__('No tienes permisos para exportar estas liquidaciones.', 'flavor-chat-ia'));
        }

        $resultado = $this->stream_liquidaciones_csv($energia_comunidad_id);

        if (is_wp_error($resultado)) {
            wp_die($resultado->get_error_message());
        }

        exit;
    }

    public function handle_exportar_liquidacion_csv() {
        if (!is_user_logged_in()) {
            wp_die(__('Debes iniciar sesion para exportar la liquidacion.', 'flavor-chat-ia'));
        }

        $liquidacion_id = absint($_GET['liquidacion_id'] ?? 0);

        check_admin_referer('energia_comunitaria_exportar_liquidacion_' . $liquidacion_id);

        $liquidacion = $this->get_liquidacion($liquidacion_id);
        if (!$liquidacion || !$this->user_can_manage_energia_comunidad((int) $liquidacion->energia_comunidad_id)) {
            wp_die(__('No tienes permisos para exportar esta liquidacion.', 'flavor-chat-ia'));
        }

        $resultado = $this->stream_liquidacion_csv($liquidacion_id);

        if (is_wp_error($resultado)) {
            wp_die($resultado->get_error_message());
        }

        exit;
    }

    public function get_actions() {
        return [
            'listar_instalaciones' => [
                'description' => 'Lista instalaciones energeticas registradas',
                'params' => ['estado'],
            ],
            'consultar_balance' => [
                'description' => 'Obtiene el balance energetico agregado del mes',
                'params' => [],
            ],
            'reportar_incidencia' => [
                'description' => 'Crea una incidencia tecnica en una instalacion',
                'params' => ['instalacion_id', 'titulo', 'descripcion', 'prioridad'],
            ],
        ];
    }

    public function execute_action($action_name, $params) {
        switch ($action_name) {
            case 'listar_instalaciones':
                return $this->action_listar_instalaciones($params);
            case 'consultar_balance':
                return [
                    'success' => true,
                    'data' => $this->get_dashboard_data(),
                ];
            case 'reportar_incidencia':
                return $this->action_reportar_incidencia($params);
        }

        return [
            'success' => false,
            'error' => __('Accion no encontrada.', 'flavor-chat-ia'),
        ];
    }

    private function action_listar_instalaciones($params) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_energia_instalaciones';
        $estado = sanitize_text_field($params['estado'] ?? '');
        $items = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            if ($estado !== '') {
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla} WHERE estado = %s ORDER BY created_at DESC LIMIT 20",
                    $estado
                ), ARRAY_A);
            } else {
                $items = $wpdb->get_results("SELECT * FROM {$tabla} ORDER BY created_at DESC LIMIT 20", ARRAY_A);
            }
        }

        return [
            'success' => true,
            'data' => $items,
        ];
    }

    private function action_reportar_incidencia($params) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para reportar incidencias.', 'flavor-chat-ia'),
            ];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('instalacion_id', absint($params['instalacion_id'] ?? 0));
        $request->set_param('titulo', $params['titulo'] ?? '');
        $request->set_param('descripcion', $params['descripcion'] ?? '');
        $request->set_param('prioridad', $params['prioridad'] ?? 'media');

        $response = $this->rest_post_incidencia($request);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'data' => $response->get_data(),
        ];
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'energia_listar_instalaciones',
                'description' => 'Lista instalaciones de una comunidad energetica',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado de la instalacion',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'energia_consultar_balance',
                'description' => 'Consulta el balance energetico agregado del mes',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new stdClass(),
                ],
            ],
            [
                'name' => 'energia_reportar_incidencia',
                'description' => 'Registra una incidencia tecnica en una instalacion energetica',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'instalacion_id' => ['type' => 'integer'],
                        'titulo' => ['type' => 'string'],
                        'descripcion' => ['type' => 'string'],
                        'prioridad' => ['type' => 'string'],
                    ],
                    'required' => ['instalacion_id', 'titulo'],
                ],
            ],
        ];
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Energia Comunitaria - Guia Base**

Este modulo organiza comunidades que buscan autosuficiencia energetica mediante infraestructuras compartidas.

Elementos principales:
- Comunidades energeticas
- Instalaciones solares, baterias y otras infraestructuras
- Lecturas de generacion, consumo y excedentes
- Incidencias y mantenimiento
- Proyectos de ampliacion o compra colectiva

Indicadores clave:
- kWh generados
- kWh consumidos
- porcentaje de autosuficiencia
- excedentes disponibles
- incidencias abiertas
- ahorro economico y CO2 evitado

Relaciones naturales:
- Comunidades: gobernanza y miembros
- Presupuestos participativos: nuevas inversiones
- Eventos: talleres y jornadas tecnicas
- Huella ecologica: impacto evitado
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            [
                'pregunta' => 'Que gestiona este modulo?',
                'respuesta' => 'Gestiona instalaciones, lecturas, balance energetico, incidencias y proyectos de una comunidad energetica local.',
            ],
            [
                'pregunta' => 'Sustituye al modulo de comunidades?',
                'respuesta' => 'No. Lo complementa. Comunidades gestiona la capa social y este modulo la capa operativa energetica.',
            ],
            [
                'pregunta' => 'Como se conecta con una comunidad existente?',
                'respuesta' => 'La comunidad energetica puede vincularse con una comunidad social ya creada para reutilizar miembros, conversacion y gobernanza.',
            ],
            [
                'pregunta' => 'Puede conectarse con otras iniciativas sostenibles?',
                'respuesta' => 'Si. Encaja especialmente con huella ecologica, eventos, presupuestos participativos y economia de suficiencia.',
            ],
        ];
    }

    public function get_pages_definition() {
        return [
            [
                'title' => __('Energia Comunitaria', 'flavor-chat-ia'),
                'slug' => 'energia-comunitaria',
                'content' => '<h1>' . __('Energia Comunitaria', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona comunidades energeticas, instalaciones, lecturas y mantenimiento para avanzar hacia la autosuficiencia local.', 'flavor-chat-ia') . '</p>

[flavor_energia_dashboard]

[flavor module="energia-comunitaria" view="listado"]',
                'parent' => 0,
            ],
            [
                'title' => __('Comunidades Energéticas', 'flavor-chat-ia'),
                'slug' => 'comunidades',
                'content' => '<h1>' . __('Comunidades Energéticas', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta las comunidades energéticas registradas y su estado general.', 'flavor-chat-ia') . '</p>

[flavor module="energia-comunitaria" view="listado"]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Nueva Comunidad Energética', 'flavor-chat-ia'),
                'slug' => 'nueva-comunidad',
                'content' => '<h1>' . __('Nueva Comunidad Energética', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea una comunidad energética y vincúlala con una comunidad social existente si lo necesitas.', 'flavor-chat-ia') . '</p>

[flavor_energia_form_comunidad]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Instalaciones', 'flavor-chat-ia'),
                'slug' => 'instalaciones',
                'content' => '<h1>' . __('Instalaciones Energéticas', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta instalaciones solares, baterías, microredes y otros activos energéticos de la comunidad.', 'flavor-chat-ia') . '</p>

[flavor_energia_instalaciones]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Participantes', 'flavor-chat-ia'),
                'slug' => 'participantes',
                'content' => '<h1>' . __('Participantes', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona coeficientes de reparto y consumos base de los hogares o espacios participantes.', 'flavor-chat-ia') . '</p>

[flavor_energia_participantes]

[flavor_energia_form_participante]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Nueva Instalación', 'flavor-chat-ia'),
                'slug' => 'nueva-instalacion',
                'content' => '<h1>' . __('Registrar Instalación', 'flavor-chat-ia') . '</h1>
<p>' . __('Añade una nueva instalación energética a una comunidad ya creada.', 'flavor-chat-ia') . '</p>

[flavor_energia_form_instalacion]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Registrar Lectura', 'flavor-chat-ia'),
                'slug' => 'registrar-lectura',
                'content' => '<h1>' . __('Registrar Lectura Energética', 'flavor-chat-ia') . '</h1>
<p>' . __('Registra producción, consumo y estado de batería de una instalación.', 'flavor-chat-ia') . '</p>

[flavor_energia_form_lectura]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Mantenimiento', 'flavor-chat-ia'),
                'slug' => 'mantenimiento',
                'content' => '<h1>' . __('Mantenimiento e Incidencias', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta incidencias abiertas y registra nuevas acciones de mantenimiento.', 'flavor-chat-ia') . '</p>

[flavor_energia_mantenimiento]

[flavor_energia_form_incidencia]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Balance Energético', 'flavor-chat-ia'),
                'slug' => 'balance',
                'content' => '<h1>' . __('Balance y Reparto Energético', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta la generación neta y la base de reparto comunitario de energía.', 'flavor-chat-ia') . '</p>

[flavor_energia_balance]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Cierres Mensuales', 'flavor-chat-ia'),
                'slug' => 'cierres',
                'content' => '<h1>' . __('Cierres Mensuales', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta el histórico auditable de repartos cerrados y registra nuevos cierres mensuales.', 'flavor-chat-ia') . '</p>

[flavor_energia_cierres]

[flavor_energia_form_cierre]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Liquidaciones', 'flavor-chat-ia'),
                'slug' => 'liquidaciones',
                'content' => '<h1>' . __('Liquidaciones Energéticas', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta el resumen económico por participante generado a partir de cada cierre mensual.', 'flavor-chat-ia') . '</p>

[flavor_energia_liquidaciones]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Proyectos Energéticos', 'flavor-chat-ia'),
                'slug' => 'proyectos',
                'content' => '<h1>' . __('Proyectos Energéticos', 'flavor-chat-ia') . '</h1>
<p>' . __('Visualiza ampliaciones, compras colectivas y futuras inversiones energéticas de la comunidad.', 'flavor-chat-ia') . '</p>

[flavor_energia_proyectos]',
                'parent' => 'energia-comunitaria',
            ],
            [
                'title' => __('Comunidad', 'flavor-chat-ia'),
                'slug' => 'comunidad',
                'content' => '<h1>' . __('Comunidad y Gobernanza', 'flavor-chat-ia') . '</h1>
<p>' . __('Accede a la comunidad social vinculada para anuncios, coordinación y toma de decisiones.', 'flavor-chat-ia') . '</p>

[comunidades_listado categoria="medioambiente" limite="6"]',
                'parent' => 'energia-comunitaria',
            ],
        ];
    }

    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('energia_comunitaria');
            return;
        }

        $pagina = get_page_by_path('energia-comunitaria');
        if (!$pagina && !get_option('flavor_energia_comunitaria_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['energia_comunitaria']);
            update_option('flavor_energia_comunitaria_pages_created', 1, false);
        }
    }

    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-energia-comunitaria-dashboard-tab.php';

        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Energia_Comunitaria_Dashboard_Tab')) {
                Flavor_Energia_Comunitaria_Dashboard_Tab::get_instance();
            }
        }
    }
}
