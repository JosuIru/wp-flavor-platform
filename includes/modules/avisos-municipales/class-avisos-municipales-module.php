<?php
/**
 * Modulo de Avisos Municipales para Chat IA
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Avisos Municipales - Comunicados oficiales y notificaciones
 */
class Flavor_Platform_Avisos_Municipales_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var array Nombres de tablas */
    private $tablas = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'avisos_municipales';
        $this->name = 'Avisos Municipales'; // Translation loaded on init
        $this->description = 'Comunicados oficiales, cortes de servicio, eventos y notificaciones del ayuntamiento.'; // Translation loaded on init

        global $wpdb;
        $this->tablas = [
            'avisos'          => $wpdb->prefix . 'flavor_avisos_municipales',
            'categorias'      => $wpdb->prefix . 'flavor_avisos_categorias',
            'zonas'           => $wpdb->prefix . 'flavor_avisos_zonas',
            'suscripciones'   => $wpdb->prefix . 'flavor_avisos_suscripciones',
            'lecturas'        => $wpdb->prefix . 'flavor_avisos_lecturas',
            'confirmaciones'  => $wpdb->prefix . 'flavor_avisos_confirmaciones',
            'push'            => $wpdb->prefix . 'flavor_avisos_push_subscriptions',
        ];

        parent::__construct();
        $this->cargar_frontend_controller();

    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Platform_Helpers::tabla_existe($this->tablas['avisos']);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Avisos Municipales no estan creadas. Activa el modulo para crearlas automaticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
            $this->tablas['avisos'] => "CREATE TABLE {$this->tablas['avisos']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                titulo varchar(255) NOT NULL,
                contenido text NOT NULL,
                prioridad enum('urgente','alta','media','baja') NOT NULL DEFAULT 'media',
                categoria varchar(100) DEFAULT NULL,
                estado enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
                autor_id bigint(20) UNSIGNED NOT NULL,
                fecha_publicacion datetime DEFAULT NULL,
                fecha_expiracion datetime DEFAULT NULL,
                tiene_adjuntos tinyint(1) NOT NULL DEFAULT 0,
                total_visualizaciones int(11) NOT NULL DEFAULT 0,
                total_confirmaciones int(11) NOT NULL DEFAULT 0,
                requiere_confirmacion tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY autor_id (autor_id),
                KEY estado (estado),
                KEY prioridad (prioridad),
                KEY categoria (categoria),
                KEY fecha_publicacion (fecha_publicacion)
            ) $charset_collate;",

            $this->tablas['adjuntos'] => "CREATE TABLE {$this->tablas['adjuntos']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                aviso_id bigint(20) UNSIGNED NOT NULL,
                nombre_archivo varchar(255) NOT NULL,
                ruta_archivo varchar(500) NOT NULL,
                tipo_mime varchar(100) DEFAULT NULL,
                tamano_bytes int(11) DEFAULT NULL,
                orden int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY aviso_id (aviso_id)
            ) $charset_collate;",

            $this->tablas['visualizaciones'] => "CREATE TABLE {$this->tablas['visualizaciones']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                aviso_id bigint(20) UNSIGNED NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                fecha_visualizacion datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY aviso_usuario (aviso_id, usuario_id)
            ) $charset_collate;",

            $this->tablas['confirmaciones'] => "CREATE TABLE {$this->tablas['confirmaciones']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                aviso_id bigint(20) UNSIGNED NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                fecha_confirmacion datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY aviso_usuario (aviso_id, usuario_id)
            ) $charset_collate;",

            $this->tablas['push'] => "CREATE TABLE {$this->tablas['push']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                endpoint text NOT NULL,
                public_key varchar(255) DEFAULT NULL,
                auth_token varchar(255) DEFAULT NULL,
                user_agent text,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id)
            ) $charset_collate;",

            $this->tablas['categorias'] => "CREATE TABLE {$this->tablas['categorias']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                nombre varchar(100) NOT NULL,
                slug varchar(100) NOT NULL,
                descripcion text DEFAULT NULL,
                icono varchar(50) DEFAULT 'info',
                color varchar(20) DEFAULT '#6b7280',
                orden int(11) DEFAULT 0,
                activa tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug),
                KEY activa (activa),
                KEY orden (orden)
            ) $charset_collate;",

            $this->tablas['zonas'] => "CREATE TABLE {$this->tablas['zonas']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                nombre varchar(100) NOT NULL,
                slug varchar(100) NOT NULL,
                descripcion text DEFAULT NULL,
                tipo enum('municipio','barrio','distrito','calle') DEFAULT 'barrio',
                geometria text DEFAULT NULL,
                activa tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug),
                KEY tipo (tipo),
                KEY activa (activa)
            ) $charset_collate;",

            $this->tablas['suscripciones'] => "CREATE TABLE {$this->tablas['suscripciones']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                categoria_id bigint(20) UNSIGNED DEFAULT NULL,
                zona_id bigint(20) UNSIGNED DEFAULT NULL,
                canal enum('email','push','sms') DEFAULT 'email',
                activa tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY categoria_id (categoria_id),
                KEY zona_id (zona_id)
            ) $charset_collate;",

            $this->tablas['lecturas'] => "CREATE TABLE {$this->tablas['lecturas']} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                aviso_id bigint(20) UNSIGNED NOT NULL,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                fecha_lectura datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY aviso_usuario (aviso_id, usuario_id),
                KEY aviso_id (aviso_id),
                KEY usuario_id (usuario_id)
            ) $charset_collate;"
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'enviar_push_notifications'    => true,
            'enviar_email_notifications'   => true,
            'requiere_confirmacion_lectura'=> false,
            'dias_expiracion_default'      => 30,
            'avisos_por_pagina'            => 10,
            'mostrar_visualizaciones'      => true,
            'permitir_adjuntos'            => true,
            'max_adjuntos'                 => 5,
            'vapid_public_key'             => '',
            'vapid_private_key'            => '',
            'prioridades' => [
                'urgente' => ['label' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#dc2626', 'icon' => 'warning'],
                'alta'    => ['label' => __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#f97316', 'icon' => 'flag'],
                'media'   => ['label' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#eab308', 'icon' => 'info'],
                'baja'    => ['label' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#22c55e', 'icon' => 'check'],
            ],
            'categorias_default' => [
                'corte_agua'      => __('Corte de agua', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'corte_luz'       => __('Corte de luz', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'obras'           => __('Obras publicas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eventos'         => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'trafico'         => __('Trafico y movilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'medio_ambiente'  => __('Medio ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'seguridad'       => __('Seguridad ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cultura'         => __('Cultura y deportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'convocatorias'   => __('Convocatorias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'otros'           => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers publicos
        add_action('wp_ajax_flavor_avisos_listar', [$this, 'ajax_listar_avisos']);
        add_action('wp_ajax_nopriv_flavor_avisos_listar', [$this, 'ajax_listar_avisos']);
        add_action('wp_ajax_flavor_avisos_ver', [$this, 'ajax_ver_aviso']);
        add_action('wp_ajax_nopriv_flavor_avisos_ver', [$this, 'ajax_ver_aviso']);
        add_action('wp_ajax_flavor_avisos_marcar_leido', [$this, 'ajax_marcar_leido']);
        add_action('wp_ajax_flavor_avisos_confirmar_lectura', [$this, 'ajax_confirmar_lectura']);
        add_action('wp_ajax_flavor_avisos_suscribir', [$this, 'ajax_suscribir']);
        add_action('wp_ajax_nopriv_flavor_avisos_suscribir', [$this, 'ajax_suscribir']);
        add_action('wp_ajax_flavor_avisos_registrar_push', [$this, 'ajax_registrar_push']);
        add_action('wp_ajax_flavor_avisos_registrar_visualizacion', [$this, 'ajax_registrar_visualizacion']);
        add_action('wp_ajax_nopriv_flavor_avisos_registrar_visualizacion', [$this, 'ajax_registrar_visualizacion']);

        // AJAX handlers admin
        add_action('wp_ajax_flavor_avisos_crear', [$this, 'ajax_crear_aviso']);
        add_action('wp_ajax_flavor_avisos_actualizar', [$this, 'ajax_actualizar_aviso']);
        add_action('wp_ajax_flavor_avisos_eliminar', [$this, 'ajax_eliminar_aviso']);
        add_action('wp_ajax_flavor_avisos_estadisticas', [$this, 'ajax_estadisticas']);
        add_action('wp_ajax_flavor_avisos_enviar_notificaciones', [$this, 'ajax_enviar_notificaciones']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para notificaciones programadas
        add_action('flavor_avisos_enviar_programados', [$this, 'enviar_avisos_programados']);
        if (!wp_next_scheduled('flavor_avisos_enviar_programados')) {
            wp_schedule_event(time(), 'hourly', 'flavor_avisos_enviar_programados');
        }

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        $db_version = get_option('flavor_avisos_db_version', '0');
        $current_version = '2.1.0'; // Incrementado para forzar recreación

        if (version_compare($db_version, $current_version, '<')) {
            $this->create_tables();
            $this->insertar_datos_iniciales();
            update_option('flavor_avisos_db_version', $current_version);
        }
    }

    /**
     * Verifica y aplica migraciones de base de datos
     */
    public function maybe_migrate_tables() {
        $version_migracion = get_option('flavor_avisos_migration_version', '1.0.0');

        if (version_compare($version_migracion, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('flavor_avisos_migration_version', '1.1.0');
        }
    }

    /**
     * Migración a versión 1.1.0 - Agregar columnas para compatibilidad
     */
    private function migrate_to_1_1_0() {
        global $wpdb;

        $tabla_avisos = $this->tablas['avisos'];

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_avisos)) {
            return;
        }

        // Columnas a agregar
        $columnas = [
            'publicado' => "tinyint(1) DEFAULT 0 AFTER estado",
            'destacado' => "tinyint(1) DEFAULT 0 AFTER publicado",
            'fecha_inicio' => "datetime DEFAULT NULL AFTER fecha_publicacion",
            'fecha_fin' => "datetime DEFAULT NULL AFTER fecha_inicio",
            'categoria_id' => "bigint(20) UNSIGNED DEFAULT NULL AFTER categoria",
            'zona_id' => "bigint(20) UNSIGNED DEFAULT NULL AFTER categoria_id",
        ];

        foreach ($columnas as $columna => $definicion) {
            $existe = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $tabla_avisos LIKE %s", $columna));
            if (empty($existe)) {
                $wpdb->query("ALTER TABLE $tabla_avisos ADD COLUMN $columna $definicion");
            }
        }

        // Actualizar publicado basado en estado
        $wpdb->query("UPDATE $tabla_avisos SET publicado = 1 WHERE estado = 'publicado'");

        // Copiar fecha_publicacion a fecha_inicio si no tiene valor
        $wpdb->query("UPDATE $tabla_avisos SET fecha_inicio = fecha_publicacion WHERE fecha_inicio IS NULL AND fecha_publicacion IS NOT NULL");

        // Copiar fecha_expiracion a fecha_fin
        $wpdb->query("UPDATE $tabla_avisos SET fecha_fin = fecha_expiracion WHERE fecha_fin IS NULL");
    }

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
     * Inserta datos iniciales
     */
    private function insertar_datos_iniciales() {
        global $wpdb;

        $categorias_default = [
            ['nombre' => 'Corte de agua', 'slug' => 'corte-agua', 'icono' => 'water', 'color' => '#3b82f6'],
            ['nombre' => 'Corte de luz', 'slug' => 'corte-luz', 'icono' => 'bolt', 'color' => '#f59e0b'],
            ['nombre' => 'Obras publicas', 'slug' => 'obras', 'icono' => 'construction', 'color' => '#f97316'],
            ['nombre' => 'Eventos', 'slug' => 'eventos', 'icono' => 'calendar', 'color' => '#8b5cf6'],
            ['nombre' => 'Trafico', 'slug' => 'trafico', 'icono' => 'car', 'color' => '#ef4444'],
            ['nombre' => 'Medio ambiente', 'slug' => 'medio-ambiente', 'icono' => 'leaf', 'color' => '#22c55e'],
            ['nombre' => 'Seguridad', 'slug' => 'seguridad', 'icono' => 'shield', 'color' => '#dc2626'],
            ['nombre' => 'Cultura', 'slug' => 'cultura', 'icono' => 'theater', 'color' => '#ec4899'],
            ['nombre' => 'Convocatorias', 'slug' => 'convocatorias', 'icono' => 'document', 'color' => '#6366f1'],
            ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'info', 'color' => '#6b7280'],
        ];

        foreach ($categorias_default as $index => $categoria) {
            $wpdb->insert(
                $this->tablas['categorias'],
                array_merge($categoria, ['orden' => $index]),
                ['%s', '%s', '%s', '%s', '%d']
            );
        }

        $wpdb->insert(
            $this->tablas['zonas'],
            [
                'nombre' => 'Todo el municipio',
                'slug'   => 'todo-municipio',
                'tipo'   => 'municipio',
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('avisos_activos', [$this, 'shortcode_avisos_activos']);
        add_shortcode('avisos_zona', [$this, 'shortcode_avisos_zona']);
        add_shortcode('suscribirse_avisos', [$this, 'shortcode_suscribirse']);
        add_shortcode('historial_avisos', [$this, 'shortcode_historial']);
        add_shortcode('aviso_detalle', [$this, 'shortcode_aviso_detalle']);
        add_shortcode('avisos_urgentes', [$this, 'shortcode_avisos_urgentes']);
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
            'avisos_activos',
            'avisos_zona',
            'suscribirse_avisos',
            'historial_avisos',
            'aviso_detalle',
            'avisos_urgentes',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets frontend
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $module_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'flavor-avisos-municipales',
            $module_url . 'assets/css/avisos.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-avisos-municipales',
            $module_url . 'assets/js/avisos.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('flavor-avisos-municipales', 'flavorAvisosConfig', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('flavor_avisos_nonce'),
            'vapidKey' => '',
            'i18n'     => [
                'cargando'        => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'           => __('Error al cargar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados'  => __('No hay avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar'       => __('Confirmar lectura', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmado'      => __('Lectura confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encola assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Verificar si estamos en una página del módulo de avisos
        $pagina_actual = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $es_pagina_avisos = strpos($pagina_actual, 'avisos') !== false;

        if (!$es_pagina_avisos && strpos($hook, 'flavor-chat') === false) {
            return;
        }

        // Cargar estilos base del frontend
        $this->enqueue_assets();

        // Cargar estilos del dashboard admin (componentes dm-*)
        if ($es_pagina_avisos) {
            wp_enqueue_style(
                'flavor-dashboard-module-components',
                FLAVOR_PLATFORM_URL . 'assets/css/layouts/dashboard-module-components.css',
                [],
                FLAVOR_PLATFORM_VERSION
            );
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-chat/v1';

        register_rest_route($namespace, '/avisos', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_avisos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => $this->get_avisos_args(),
        ]);

        register_rest_route($namespace, '/avisos/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_aviso'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/avisos', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_create_aviso'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($namespace, '/avisos/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'rest_update_aviso'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($namespace, '/avisos/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'rest_delete_aviso'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($namespace, '/avisos/categorias', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/avisos/zonas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_zonas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/avisos/suscribir', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_suscribir'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/avisos/(?P<id>\d+)/confirmar', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_confirmar_lectura'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/avisos/estadisticas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_estadisticas'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Argumentos para endpoint de avisos
     */
    private function get_avisos_args() {
        return [
            'categoria'  => ['type' => 'integer', 'default' => 0],
            'zona'       => ['type' => 'integer', 'default' => 0],
            'prioridad'  => ['type' => 'string', 'default' => ''],
            'busqueda'   => ['type' => 'string', 'default' => ''],
            'pagina'     => ['type' => 'integer', 'default' => 1],
            'por_pagina' => ['type' => 'integer', 'default' => 10],
            'orden'      => ['type' => 'string', 'default' => 'fecha_inicio'],
            'direccion'  => ['type' => 'string', 'default' => 'DESC'],
        ];
    }

    /**
     * Verifica permiso admin
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Verifica usuario logueado
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Avisos activos
     */
    public function shortcode_avisos_activos($atts) {
        $atts = shortcode_atts([
            'categoria'  => '',
            'prioridad'  => '',
            'limite'     => 10,
            'columnas'   => 1,
            'mostrar_filtros' => 'true',
        ], $atts);

        $avisos = $this->obtener_avisos_activos([
            'categoria_slug' => $atts['categoria'],
            'prioridad'      => $atts['prioridad'],
            'limite'         => intval($atts['limite']),
        ]);

        $categorias = $this->obtener_categorias();
        $zonas = $this->obtener_zonas();

        ob_start();
        ?>
        <div class="avisos-municipales-container" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <div class="avisos-municipales-header">
                <div>
                    <h2 class="avisos-municipales-title"><?php _e('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="avisos-municipales-subtitle"><?php _e('Informacion oficial del ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <?php if ($atts['mostrar_filtros'] === 'true'): ?>
            <div class="avisos-filtros">
                <div class="avisos-filtro-grupo">
                    <label class="avisos-filtro-label"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="avisos-filtro-categoria" class="avisos-filtro-select">
                        <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo esc_attr($categoria->id); ?>"><?php echo esc_html($categoria->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="avisos-filtro-grupo">
                    <label class="avisos-filtro-label"><?php _e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="avisos-filtro-prioridad" class="avisos-filtro-select">
                        <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="avisos-filtro-grupo">
                    <label class="avisos-filtro-label"><?php _e('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="avisos-filtro-zona" class="avisos-filtro-select">
                        <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($zonas as $zona): ?>
                        <option value="<?php echo esc_attr($zona->id); ?>"><?php echo esc_html($zona->nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="avisos-btn avisos-btn-primary avisos-btn-filtrar">
                    <?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
            <?php endif; ?>

            <div class="avisos-lista">
                <?php if (empty($avisos)): ?>
                <div class="avisos-empty">
                    <div class="avisos-empty-icon">📢</div>
                    <p><?php _e('No hay avisos activos en este momento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($avisos as $aviso): ?>
                    <?php echo $this->render_aviso_card($aviso); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="avisos-btn avisos-btn-secondary avisos-btn-cargar-mas" style="display:none;">
                <?php _e('Cargar mas avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Avisos por zona
     */
    public function shortcode_avisos_zona($atts) {
        $atts = shortcode_atts([
            'zona'   => '',
            'limite' => 10,
        ], $atts);

        $avisos = $this->obtener_avisos_activos([
            'zona_slug' => $atts['zona'],
            'limite'    => intval($atts['limite']),
        ]);

        ob_start();
        ?>
        <div class="avisos-municipales-container avisos-zona-container">
            <div class="avisos-lista">
                <?php if (empty($avisos)): ?>
                <div class="avisos-empty">
                    <p><?php _e('No hay avisos para esta zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($avisos as $aviso): ?>
                    <?php echo $this->render_aviso_card($aviso); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de suscripcion
     */
    public function shortcode_suscribirse($atts) {
        $atts = shortcode_atts([
            'titulo'      => __('Suscribete a los avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Recibe notificaciones de los avisos que te interesan', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], $atts);

        $categorias = $this->obtener_categorias();
        $zonas = $this->obtener_zonas();
        $usuario_actual = wp_get_current_user();

        ob_start();
        ?>
        <div class="avisos-suscripcion">
            <h3 class="avisos-suscripcion-titulo"><?php echo esc_html($atts['titulo']); ?></h3>
            <p class="avisos-suscripcion-descripcion"><?php echo esc_html($atts['descripcion']); ?></p>

            <form class="avisos-suscripcion-form">
                <div class="avisos-suscripcion-row">
                    <input type="text" name="nombre" class="avisos-suscripcion-input"
                           placeholder="<?php _e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           value="<?php echo esc_attr($usuario_actual->display_name ?? ''); ?>">
                    <input type="email" name="email" class="avisos-suscripcion-input" required
                           placeholder="<?php _e('Tu email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           value="<?php echo esc_attr($usuario_actual->user_email ?? ''); ?>">
                </div>

                <div>
                    <label class="avisos-filtro-label" style="color:#fff;margin-bottom:8px;display:block;">
                        <?php _e('Categorias de interes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <div class="avisos-suscripcion-categorias">
                        <?php foreach ($categorias as $categoria): ?>
                        <label class="avisos-suscripcion-categoria">
                            <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($categoria->id); ?>" checked>
                            <?php echo esc_html($categoria->nombre); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="avisos-filtro-label" style="color:#fff;margin-bottom:8px;display:block;">
                        <?php _e('Zonas de interes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <div class="avisos-suscripcion-categorias">
                        <?php foreach ($zonas as $zona): ?>
                        <label class="avisos-suscripcion-categoria">
                            <input type="checkbox" name="zonas[]" value="<?php echo esc_attr($zona->id); ?>" checked>
                            <?php echo esc_html($zona->nombre); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="avisos-suscripcion-categoria">
                        <input type="checkbox" name="push" value="1">
                        <?php _e('Recibir notificaciones push en el navegador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </div>

                <button type="submit" class="avisos-suscripcion-submit">
                    <?php _e('Suscribirme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de avisos
     */
    public function shortcode_historial($atts) {
        $atts = shortcode_atts([
            'limite' => 20,
            'desde'  => '',
            'hasta'  => '',
        ], $atts);

        $avisos = $this->obtener_historial_avisos([
            'limite' => intval($atts['limite']),
            'desde'  => $atts['desde'],
            'hasta'  => $atts['hasta'],
        ]);

        $avisos_por_fecha = [];
        foreach ($avisos as $aviso) {
            $fecha = date('Y-m-d', strtotime($aviso->fecha_inicio));
            if (!isset($avisos_por_fecha[$fecha])) {
                $avisos_por_fecha[$fecha] = [];
            }
            $avisos_por_fecha[$fecha][] = $aviso;
        }

        ob_start();
        ?>
        <div class="avisos-municipales-container avisos-historial">
            <h3 class="avisos-historial-titulo"><?php _e('Historial de Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="avisos-historial-timeline">
                <?php foreach ($avisos_por_fecha as $fecha => $avisos_del_dia): ?>
                <div class="avisos-historial-item">
                    <div class="avisos-historial-fecha">
                        <?php echo date_i18n('j F Y', strtotime($fecha)); ?>
                    </div>
                    <?php foreach ($avisos_del_dia as $aviso): ?>
                    <div class="avisos-historial-contenido">
                        <span class="aviso-badge aviso-badge-<?php echo esc_attr($aviso->prioridad); ?>">
                            <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                        </span>
                        <strong><?php echo esc_html($aviso->titulo); ?></strong>
                        <p><?php echo esc_html(wp_trim_words($aviso->contenido, 20)); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de aviso
     */
    public function shortcode_aviso_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $aviso_id = intval($atts['id']) ?: (isset($_GET['aviso']) ? intval($_GET['aviso']) : 0);

        if (!$aviso_id) {
            return '<p>' . __('Aviso no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $aviso = $this->obtener_aviso($aviso_id);

        if (!$aviso) {
            return '<p>' . __('Aviso no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->registrar_visualizacion($aviso_id);

        $usuario_id = get_current_user_id();
        $ya_confirmado = false;
        if ($usuario_id && $aviso->requiere_confirmacion) {
            $ya_confirmado = $this->usuario_confirmo_aviso($aviso_id, $usuario_id);
        }

        ob_start();
        ?>
        <div class="avisos-municipales-container">
            <div class="aviso-detalle">
                <div class="aviso-detalle-header <?php echo $aviso->prioridad === 'urgente' ? 'urgente' : ''; ?>">
                    <div class="aviso-badges" style="margin-bottom:12px;">
                        <span class="aviso-badge aviso-badge-<?php echo esc_attr($aviso->prioridad); ?>">
                            <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                        </span>
                        <?php if ($aviso->categoria_nombre): ?>
                        <span class="aviso-badge aviso-badge-categoria">
                            <?php echo esc_html($aviso->categoria_nombre); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="aviso-detalle-titulo"><?php echo esc_html($aviso->titulo); ?></h1>

                    <div class="aviso-detalle-meta">
                        <span><?php echo date_i18n('j F Y, H:i', strtotime($aviso->fecha_inicio)); ?></span>
                        <?php if ($aviso->fecha_fin): ?>
                        <span><?php _e('Hasta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo date_i18n('j F Y, H:i', strtotime($aviso->fecha_fin)); ?></span>
                        <?php endif; ?>
                        <?php if ($aviso->departamento): ?>
                        <span><?php echo esc_html($aviso->departamento); ?></span>
                        <?php endif; ?>
                        <span><?php echo number_format($aviso->visualizaciones); ?> <?php _e('visualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="aviso-detalle-body">
                    <div class="aviso-detalle-contenido">
                        <?php echo wp_kses_post(wpautop($aviso->contenido)); ?>
                    </div>

                    <?php if ($aviso->ubicacion_especifica): ?>
                    <div style="margin-top:20px;padding:15px;background:#f3f4f6;border-radius:8px;">
                        <strong><?php _e('Ubicacion:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <?php echo esc_html($aviso->ubicacion_especifica); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($aviso->enlace_externo): ?>
                    <div style="margin-top:20px;">
                        <a href="<?php echo esc_url($aviso->enlace_externo); ?>" target="_blank" class="avisos-btn avisos-btn-primary">
                            <?php _e('Mas informacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($aviso->contacto_email || $aviso->contacto_telefono): ?>
                    <div style="margin-top:20px;padding:15px;background:#f3f4f6;border-radius:8px;">
                        <strong><?php _e('Contacto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong><br>
                        <?php if ($aviso->contacto_email): ?>
                        <a href="mailto:<?php echo esc_attr($aviso->contacto_email); ?>"><?php echo esc_html($aviso->contacto_email); ?></a><br>
                        <?php endif; ?>
                        <?php if ($aviso->contacto_telefono): ?>
                        <a href="tel:<?php echo esc_attr($aviso->contacto_telefono); ?>"><?php echo esc_html($aviso->contacto_telefono); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    $adjuntos = json_decode($aviso->adjuntos, true);
                    if (!empty($adjuntos)):
                    ?>
                    <div class="aviso-detalle-adjuntos">
                        <h4 class="aviso-detalle-adjuntos-titulo"><?php _e('Documentos adjuntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="aviso-adjunto-lista">
                            <?php foreach ($adjuntos as $adjunto): ?>
                            <a href="<?php echo esc_url($adjunto['url']); ?>" class="aviso-adjunto-item" target="_blank">
                                📎 <?php echo esc_html($adjunto['nombre']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($aviso->requiere_confirmacion && $usuario_id): ?>
                    <div class="aviso-confirmacion">
                        <?php if ($ya_confirmado): ?>
                        <div class="aviso-confirmado">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php _e('Has confirmado la lectura de este aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <?php else: ?>
                        <p class="aviso-confirmacion-texto">
                            <?php _e('Este aviso requiere confirmacion de lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <button type="button" class="avisos-btn avisos-btn-primary avisos-btn-confirmar" data-aviso-id="<?php echo esc_attr($aviso_id); ?>">
                            <?php _e('Confirmar lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Avisos urgentes
     */
    public function shortcode_avisos_urgentes($atts) {
        $atts = shortcode_atts([
            'limite' => 5,
        ], $atts);

        $avisos = $this->obtener_avisos_activos([
            'prioridad' => 'urgente',
            'limite'    => intval($atts['limite']),
        ]);

        if (empty($avisos)) {
            return '';
        }

        ob_start();
        ?>
        <div class="avisos-municipales-container avisos-urgentes-container">
            <div class="avisos-lista">
                <?php foreach ($avisos as $aviso): ?>
                <?php echo $this->render_aviso_card($aviso); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tarjeta de aviso
     */
    private function render_aviso_card($aviso) {
        $usuario_id = get_current_user_id();
        $leido = $usuario_id ? $this->usuario_leyo_aviso($aviso->id, $usuario_id) : true;
        $clase_no_leido = $leido ? '' : 'no-leido';

        ob_start();
        ?>
        <article class="aviso-card prioridad-<?php echo esc_attr($aviso->prioridad); ?> <?php echo $clase_no_leido; ?>" data-aviso-id="<?php echo esc_attr($aviso->id); ?>">
            <div class="aviso-card-header">
                <div>
                    <h3 class="aviso-titulo">
                        <a href="?aviso=<?php echo esc_attr($aviso->id); ?>"><?php echo esc_html($aviso->titulo); ?></a>
                    </h3>
                </div>
                <div class="aviso-badges">
                    <?php if (!$leido): ?>
                    <span class="aviso-badge aviso-badge-nuevo"><?php _e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                    <span class="aviso-badge aviso-badge-<?php echo esc_attr($aviso->prioridad); ?>">
                        <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                    </span>
                    <?php if (!empty($aviso->categoria_nombre)): ?>
                    <span class="aviso-badge aviso-badge-categoria"><?php echo esc_html($aviso->categoria_nombre); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aviso-card-body">
                <p class="aviso-extracto">
                    <?php echo esc_html($aviso->extracto ?: wp_trim_words($aviso->contenido, 30)); ?>
                </p>
            </div>
            <div class="aviso-card-footer">
                <div class="aviso-meta">
                    <span class="aviso-meta-item">
                        <?php echo date_i18n('j M Y, H:i', strtotime($aviso->fecha_inicio)); ?>
                    </span>
                    <?php if (!empty($aviso->zona_nombre)): ?>
                    <span class="aviso-meta-item"><?php echo esc_html($aviso->zona_nombre); ?></span>
                    <?php endif; ?>
                </div>
                <a href="?aviso=<?php echo esc_attr($aviso->id); ?>" class="avisos-btn avisos-btn-secondary">
                    <?php _e('Ver mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // METODOS DE DATOS
    // =========================================================================

    /**
     * Obtiene avisos activos
     */
    public function obtener_avisos_activos($args = []) {
        global $wpdb;

        $defaults = [
            'categoria_id'   => 0,
            'categoria_slug' => '',
            'zona_id'        => 0,
            'zona_slug'      => '',
            'prioridad'      => '',
            'busqueda'       => '',
            'limite'         => 10,
            'offset'         => 0,
            'orden'          => 'fecha_inicio',
            'direccion'      => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['a.publicado = 1', 'a.fecha_inicio <= NOW()', '(a.fecha_fin IS NULL OR a.fecha_fin >= NOW())'];
        $valores_preparados = [];

        if ($args['categoria_id']) {
            $where[] = 'a.categoria_id = %d';
            $valores_preparados[] = intval($args['categoria_id']);
        }

        if ($args['categoria_slug']) {
            $where[] = 'c.slug = %s';
            $valores_preparados[] = sanitize_text_field($args['categoria_slug']);
        }

        if ($args['zona_id']) {
            $where[] = '(a.zona_id = %d OR a.zona_id IS NULL)';
            $valores_preparados[] = intval($args['zona_id']);
        }

        if ($args['zona_slug']) {
            $where[] = '(z.slug = %s OR a.zona_id IS NULL)';
            $valores_preparados[] = sanitize_text_field($args['zona_slug']);
        }

        if ($args['prioridad']) {
            $where[] = 'a.prioridad = %s';
            $valores_preparados[] = sanitize_text_field($args['prioridad']);
        }

        if ($args['busqueda']) {
            $where[] = '(a.titulo LIKE %s OR a.contenido LIKE %s)';
            $busqueda_like = '%' . $wpdb->esc_like($args['busqueda']) . '%';
            $valores_preparados[] = $busqueda_like;
            $valores_preparados[] = $busqueda_like;
        }

        $orden_seguro = in_array($args['orden'], ['fecha_inicio', 'prioridad', 'visualizaciones', 'titulo']) ? $args['orden'] : 'fecha_inicio';
        $direccion_segura = strtoupper($args['direccion']) === 'ASC' ? 'ASC' : 'DESC';

        $sql_where = implode(' AND ', $where);

        $sql = "SELECT a.id, a.titulo, a.prioridad, a.extracto, a.contenido, a.fecha_inicio, a.categoria_id, a.zona_id,
                       c.nombre as categoria_nombre, c.slug as categoria_slug, c.icono as categoria_icono, c.color as categoria_color,
                       z.nombre as zona_nombre, z.slug as zona_slug
                FROM {$this->tablas['avisos']} a
                LEFT JOIN {$this->tablas['categorias']} c ON a.categoria_id = c.id
                LEFT JOIN {$this->tablas['zonas']} z ON a.zona_id = z.id
                WHERE $sql_where
                ORDER BY a.destacado DESC, a.prioridad DESC, a.$orden_seguro $direccion_segura
                LIMIT %d OFFSET %d";

        $valores_preparados[] = intval($args['limite']);
        $valores_preparados[] = intval($args['offset']);

        return $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparados));
    }

    /**
     * Obtiene un aviso por ID
     */
    public function obtener_aviso($aviso_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, c.nombre as categoria_nombre, c.slug as categoria_slug, z.nombre as zona_nombre
             FROM {$this->tablas['avisos']} a
             LEFT JOIN {$this->tablas['categorias']} c ON a.categoria_id = c.id
             LEFT JOIN {$this->tablas['zonas']} z ON a.zona_id = z.id
             WHERE a.id = %d AND a.publicado = 1",
            intval($aviso_id)
        ));
    }

    /**
     * Obtiene historial de avisos
     */
    public function obtener_historial_avisos($args = []) {
        global $wpdb;

        $defaults = [
            'limite' => 20,
            'desde'  => '',
            'hasta'  => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['publicado = 1'];
        $valores_preparados = [];

        if ($args['desde']) {
            $where[] = 'fecha_inicio >= %s';
            $valores_preparados[] = sanitize_text_field($args['desde']);
        }

        if ($args['hasta']) {
            $where[] = 'fecha_inicio <= %s';
            $valores_preparados[] = sanitize_text_field($args['hasta']);
        }

        $sql_where = implode(' AND ', $where);

        $sql = "SELECT id, titulo, contenido, prioridad, fecha_inicio
                FROM {$this->tablas['avisos']}
                WHERE $sql_where
                ORDER BY fecha_inicio DESC
                LIMIT %d";

        $valores_preparados[] = intval($args['limite']);

        return $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparados));
    }

    /**
     * Obtiene categorias activas
     */
    public function obtener_categorias() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, nombre FROM {$this->tablas['categorias']} WHERE activa = 1 ORDER BY orden ASC, nombre ASC"
        );
    }

    /**
     * Obtiene zonas activas
     */
    public function obtener_zonas() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, nombre FROM {$this->tablas['zonas']} WHERE activa = 1 ORDER BY tipo ASC, nombre ASC"
        );
    }

    /**
     * Crea un nuevo aviso
     */
    public function crear_aviso($datos) {
        global $wpdb;

        $datos_insertar = [
            'titulo'               => sanitize_text_field($datos['titulo']),
            'contenido'            => wp_kses_post($datos['contenido']),
            'extracto'             => isset($datos['extracto']) ? sanitize_text_field($datos['extracto']) : '',
            'categoria_id'         => intval($datos['categoria_id'] ?? 0) ?: null,
            'prioridad'            => in_array($datos['prioridad'] ?? '', ['baja', 'media', 'alta', 'urgente']) ? $datos['prioridad'] : 'media',
            'zona_id'              => intval($datos['zona_id'] ?? 0) ?: null,
            'ubicacion_especifica' => sanitize_text_field($datos['ubicacion_especifica'] ?? ''),
            'fecha_inicio'         => sanitize_text_field($datos['fecha_inicio'] ?? current_time('mysql')),
            'fecha_fin'            => !empty($datos['fecha_fin']) ? sanitize_text_field($datos['fecha_fin']) : null,
            'publicado'            => intval($datos['publicado'] ?? 1),
            'destacado'            => intval($datos['destacado'] ?? 0),
            'requiere_confirmacion'=> intval($datos['requiere_confirmacion'] ?? 0),
            'enlace_externo'       => esc_url_raw($datos['enlace_externo'] ?? ''),
            'autor_id'             => get_current_user_id(),
            'departamento'         => sanitize_text_field($datos['departamento'] ?? ''),
            'contacto_email'       => sanitize_email($datos['contacto_email'] ?? ''),
            'contacto_telefono'    => sanitize_text_field($datos['contacto_telefono'] ?? ''),
            'adjuntos'             => isset($datos['adjuntos']) ? wp_json_encode($datos['adjuntos']) : null,
        ];

        $resultado = $wpdb->insert($this->tablas['avisos'], $datos_insertar);

        if ($resultado) {
            $aviso_id = $wpdb->insert_id;

            if ($datos_insertar['publicado'] && $this->get_setting('enviar_push_notifications')) {
                $this->programar_notificaciones($aviso_id);
            }

            return $aviso_id;
        }

        return false;
    }

    /**
     * Actualiza un aviso
     */
    public function actualizar_aviso($aviso_id, $datos) {
        global $wpdb;

        $datos_actualizar = [];

        if (isset($datos['titulo'])) {
            $datos_actualizar['titulo'] = sanitize_text_field($datos['titulo']);
        }
        if (isset($datos['contenido'])) {
            $datos_actualizar['contenido'] = wp_kses_post($datos['contenido']);
        }
        if (isset($datos['extracto'])) {
            $datos_actualizar['extracto'] = sanitize_text_field($datos['extracto']);
        }
        if (isset($datos['categoria_id'])) {
            $datos_actualizar['categoria_id'] = intval($datos['categoria_id']) ?: null;
        }
        if (isset($datos['prioridad'])) {
            $datos_actualizar['prioridad'] = in_array($datos['prioridad'], ['baja', 'media', 'alta', 'urgente']) ? $datos['prioridad'] : 'media';
        }
        if (isset($datos['zona_id'])) {
            $datos_actualizar['zona_id'] = intval($datos['zona_id']) ?: null;
        }
        if (isset($datos['fecha_inicio'])) {
            $datos_actualizar['fecha_inicio'] = sanitize_text_field($datos['fecha_inicio']);
        }
        if (isset($datos['fecha_fin'])) {
            $datos_actualizar['fecha_fin'] = !empty($datos['fecha_fin']) ? sanitize_text_field($datos['fecha_fin']) : null;
        }
        if (isset($datos['publicado'])) {
            $datos_actualizar['publicado'] = intval($datos['publicado']);
        }
        if (isset($datos['destacado'])) {
            $datos_actualizar['destacado'] = intval($datos['destacado']);
        }

        return $wpdb->update(
            $this->tablas['avisos'],
            $datos_actualizar,
            ['id' => intval($aviso_id)]
        );
    }

    /**
     * Elimina un aviso
     */
    public function eliminar_aviso($aviso_id) {
        global $wpdb;

        $wpdb->delete($this->tablas['lecturas'], ['aviso_id' => intval($aviso_id)]);
        $wpdb->delete($this->tablas['confirmaciones'], ['aviso_id' => intval($aviso_id)]);

        return $wpdb->delete($this->tablas['avisos'], ['id' => intval($aviso_id)]);
    }

    /**
     * Registra visualizacion
     */
    private function registrar_visualizacion($aviso_id) {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tablas['avisos']} SET visualizaciones = visualizaciones + 1 WHERE id = %d",
            intval($aviso_id)
        ));

        $usuario_id = get_current_user_id();
        if ($usuario_id) {
            $this->marcar_como_leido($aviso_id, $usuario_id);
        }
    }

    /**
     * Marca aviso como leido
     */
    private function marcar_como_leido($aviso_id, $usuario_id) {
        global $wpdb;

        $wpdb->replace(
            $this->tablas['lecturas'],
            [
                'aviso_id'   => intval($aviso_id),
                'usuario_id' => intval($usuario_id),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    /**
     * Verifica si usuario leyo aviso
     */
    private function usuario_leyo_aviso($aviso_id, $usuario_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['lecturas']} WHERE aviso_id = %d AND usuario_id = %d",
            intval($aviso_id),
            intval($usuario_id)
        ));
    }

    /**
     * Confirma lectura de aviso
     */
    private function confirmar_lectura_aviso($aviso_id, $usuario_id, $datos = []) {
        global $wpdb;

        $resultado = $wpdb->insert(
            $this->tablas['confirmaciones'],
            [
                'aviso_id'       => intval($aviso_id),
                'usuario_id'     => intval($usuario_id),
                'nombre_completo'=> sanitize_text_field($datos['nombre'] ?? ''),
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '',
                'comentario'     => sanitize_textarea_field($datos['comentario'] ?? ''),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        if ($resultado) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['avisos']} SET confirmaciones_count = confirmaciones_count + 1 WHERE id = %d",
                intval($aviso_id)
            ));
        }

        return $resultado;
    }

    /**
     * Verifica si usuario confirmo aviso
     */
    private function usuario_confirmo_aviso($aviso_id, $usuario_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['confirmaciones']} WHERE aviso_id = %d AND usuario_id = %d",
            intval($aviso_id),
            intval($usuario_id)
        ));
    }

    /**
     * Procesa suscripcion
     */
    private function procesar_suscripcion($datos) {
        global $wpdb;

        $email = sanitize_email($datos['email']);

        if (!is_email($email)) {
            return ['success' => false, 'message' => __('Email no valido', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['suscripciones']} WHERE email = %s",
            $email
        ));

        $datos_suscripcion = [
            'email'           => $email,
            'nombre'          => sanitize_text_field($datos['nombre'] ?? ''),
            'usuario_id'      => get_current_user_id() ?: null,
            'categorias_ids'  => isset($datos['categorias']) ? wp_json_encode(array_map('intval', $datos['categorias'])) : null,
            'zonas_ids'       => isset($datos['zonas']) ? wp_json_encode(array_map('intval', $datos['zonas'])) : null,
            'notificar_push'  => intval($datos['push'] ?? 0),
            'token_confirmacion' => wp_generate_password(32, false),
            'confirmada'      => get_current_user_id() ? 1 : 0,
        ];

        if ($existe) {
            $wpdb->update($this->tablas['suscripciones'], $datos_suscripcion, ['id' => $existe]);
            $suscripcion_id = $existe;
        } else {
            $wpdb->insert($this->tablas['suscripciones'], $datos_suscripcion);
            $suscripcion_id = $wpdb->insert_id;
        }

        if (!$datos_suscripcion['confirmada']) {
            $this->enviar_email_confirmacion($email, $datos_suscripcion['token_confirmacion']);
        }

        return [
            'success'        => true,
            'message'        => __('Suscripcion procesada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'suscripcion_id' => $suscripcion_id,
            'solicitar_push' => (bool) $datos_suscripcion['notificar_push'],
        ];
    }

    /**
     * Envia email de confirmacion
     */
    private function enviar_email_confirmacion($email, $token) {
        $enlace_confirmacion = add_query_arg([
            'action' => 'confirmar_suscripcion_avisos',
            'token'  => $token,
        ], home_url());

        $asunto = __('Confirma tu suscripcion a Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $mensaje = sprintf(
            __("Hola,\n\nPara confirmar tu suscripcion a los avisos municipales, haz clic en el siguiente enlace:\n\n%s\n\nSi no solicitaste esta suscripcion, ignora este mensaje.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $enlace_confirmacion
        );

        wp_mail($email, $asunto, $mensaje);
    }

    /**
     * Programa notificaciones para un aviso
     */
    private function programar_notificaciones($aviso_id) {
        $aviso = $this->obtener_aviso($aviso_id);
        if (!$aviso) {
            return;
        }

        $this->enviar_notificaciones_email($aviso);
        $this->enviar_notificaciones_push($aviso);
    }

    /**
     * Envia notificaciones por email
     */
    private function enviar_notificaciones_email($aviso) {
        global $wpdb;

        $where_categoria = '';
        $where_zona = '';

        if ($aviso->categoria_id) {
            $where_categoria = $wpdb->prepare(
                " AND (categorias_ids IS NULL OR categorias_ids LIKE %s)",
                '%' . $aviso->categoria_id . '%'
            );
        }

        if ($aviso->zona_id) {
            $where_zona = $wpdb->prepare(
                " AND (zonas_ids IS NULL OR zonas_ids LIKE %s)",
                '%' . $aviso->zona_id . '%'
            );
        }

        $prioridad_orden = ['baja' => 1, 'media' => 2, 'alta' => 3, 'urgente' => 4];
        $prioridad_aviso = $prioridad_orden[$aviso->prioridad] ?? 2;

        $sql = "SELECT email, nombre FROM {$this->tablas['suscripciones']}
                WHERE activa = 1 AND confirmada = 1 AND notificar_email = 1
                AND FIELD(prioridad_minima, 'urgente', 'alta', 'media', 'baja') >= %d
                $where_categoria $where_zona";

        $suscriptores = $wpdb->get_results($wpdb->prepare($sql, $prioridad_aviso));

        foreach ($suscriptores as $suscriptor) {
            $this->enviar_email_aviso($suscriptor, $aviso);
        }
    }

    /**
     * Envia email de aviso individual
     */
    private function enviar_email_aviso($suscriptor, $aviso) {
        $asunto = sprintf('[%s] %s', strtoupper($aviso->prioridad), $aviso->titulo);

        $mensaje = sprintf(
            "%s\n\n%s\n\nVer aviso completo: %s",
            $aviso->titulo,
            wp_strip_all_tags($aviso->contenido),
            add_query_arg('aviso', $aviso->id, home_url())
        );

        wp_mail($suscriptor->email, $asunto, $mensaje);
    }

    /**
     * Envia notificaciones push
     */
    private function enviar_notificaciones_push($aviso) {
        global $wpdb;

        $suscripciones_push = $wpdb->get_results(
            "SELECT p.* FROM {$this->tablas['push']} p
             INNER JOIN {$this->tablas['suscripciones']} s ON p.suscripcion_id = s.id
             WHERE p.activa = 1 AND s.activa = 1 AND s.notificar_push = 1"
        );

        $payload = wp_json_encode([
            'title' => $aviso->titulo,
            'body'  => wp_trim_words(wp_strip_all_tags($aviso->contenido), 20),
            'icon'  => '/wp-content/plugins/flavor-chat-ia/assets/images/icon-aviso.png',
            'badge' => '/wp-content/plugins/flavor-chat-ia/assets/images/badge.png',
            'data'  => [
                'aviso_id'  => $aviso->id,
                'prioridad' => $aviso->prioridad,
                'url'       => add_query_arg('aviso', $aviso->id, home_url()),
            ],
        ]);

        foreach ($suscripciones_push as $suscripcion) {
            $this->enviar_push_individual($suscripcion, $payload);
        }
    }

    /**
     * Envia push individual
     */
    private function enviar_push_individual($suscripcion, $payload) {
        // Implementacion basica - requiere libreria web-push
        // Para produccion usar: https://github.com/web-push-libs/web-push-php
    }

    /**
     * Envia avisos programados (cron)
     */
    public function enviar_avisos_programados() {
        global $wpdb;

        // Obtener avisos publicados recientes que no han sido notificados (via meta)
        $avisos_pendientes = $wpdb->get_results(
            "SELECT a.* FROM {$this->tablas['avisos']} a
             LEFT JOIN {$wpdb->postmeta} pm ON a.id = pm.post_id AND pm.meta_key = '_notificaciones_enviadas'
             WHERE a.estado = 'publicado'
             AND (pm.meta_value IS NULL OR pm.meta_value = '0')
             AND a.fecha_publicacion IS NOT NULL AND a.fecha_publicacion <= NOW()"
        );

        foreach ($avisos_pendientes as $aviso) {
            $this->programar_notificaciones($aviso->id);

            // Guardar flag de notificación en meta
            update_post_meta($aviso->id, '_notificaciones_enviadas', '1');
        }
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Listar avisos
     */
    public function ajax_listar_avisos() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $avisos = $this->obtener_avisos_activos([
            'categoria_id' => intval($_POST['categoria'] ?? 0),
            'zona_id'      => intval($_POST['zona'] ?? 0),
            'prioridad'    => sanitize_text_field($_POST['prioridad'] ?? ''),
            'limite'       => 10,
            'offset'       => (intval($_POST['pagina'] ?? 1) - 1) * 10,
        ]);

        $total = $this->contar_avisos_activos($_POST);

        wp_send_json_success([
            'avisos'  => array_map([$this, 'formatear_aviso_para_json'], $avisos),
            'total'   => $total,
            'hay_mas' => count($avisos) === 10,
        ]);
    }

    /**
     * Cuenta avisos activos
     */
    private function contar_avisos_activos($filtros) {
        global $wpdb;

        $where = ['publicado = 1', 'fecha_inicio <= NOW()', '(fecha_fin IS NULL OR fecha_fin >= NOW())'];

        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria_id = %d', intval($filtros['categoria']));
        }

        if (!empty($filtros['prioridad'])) {
            $where[] = $wpdb->prepare('prioridad = %s', sanitize_text_field($filtros['prioridad']));
        }

        $sql_where = implode(' AND ', $where);

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['avisos']} WHERE $sql_where");
    }

    /**
     * Formatea aviso para JSON
     */
    private function formatear_aviso_para_json($aviso) {
        $usuario_id = get_current_user_id();

        return [
            'id'              => $aviso->id,
            'titulo'          => $aviso->titulo,
            'extracto'        => $aviso->extracto ?: wp_trim_words($aviso->contenido, 30),
            'prioridad'       => $aviso->prioridad,
            'categoria_nombre'=> $aviso->categoria_nombre ?? '',
            'zona_nombre'     => $aviso->zona_nombre ?? '',
            'fecha'           => date_i18n('j M Y, H:i', strtotime($aviso->fecha_inicio)),
            'leido'           => $usuario_id ? $this->usuario_leyo_aviso($aviso->id, $usuario_id) : true,
            'visualizaciones' => $aviso->visualizaciones,
        ];
    }

    /**
     * AJAX: Ver aviso
     */
    public function ajax_ver_aviso() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        $aviso = $this->obtener_aviso($aviso_id);

        if (!$aviso) {
            wp_send_json_error(['message' => __('Aviso no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->registrar_visualizacion($aviso_id);

        wp_send_json_success(['aviso' => $this->formatear_aviso_para_json($aviso)]);
    }

    /**
     * AJAX: Marcar leido
     */
    public function ajax_marcar_leido() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        $this->marcar_como_leido($aviso_id, $usuario_id);

        wp_send_json_success();
    }

    /**
     * AJAX: Confirmar lectura
     */
    public function ajax_confirmar_lectura() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);

        if ($this->usuario_confirmo_aviso($aviso_id, $usuario_id)) {
            wp_send_json_error(['message' => __('Ya has confirmado este aviso', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultado = $this->confirmar_lectura_aviso($aviso_id, $usuario_id);

        if ($resultado) {
            wp_send_json_success(['message' => __('Lectura confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Suscribir
     */
    public function ajax_suscribir() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $resultado = $this->procesar_suscripcion($_POST);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Registrar push
     */
    public function ajax_registrar_push() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        $subscription = json_decode(stripslashes($_POST['subscription']), true);

        if (!$subscription || !isset($subscription['endpoint'])) {
            wp_send_json_error(['message' => __('Datos de suscripcion invalidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $wpdb->insert(
            $this->tablas['push'],
            [
                'usuario_id' => get_current_user_id() ?: null,
                'endpoint'   => esc_url_raw($subscription['endpoint']),
                'p256dh'     => sanitize_text_field($subscription['keys']['p256dh'] ?? ''),
                'auth'       => sanitize_text_field($subscription['keys']['auth'] ?? ''),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        wp_send_json_success();
    }

    /**
     * AJAX: Registrar visualizacion
     */
    public function ajax_registrar_visualizacion() {
        $avisos = isset($_POST['avisos']) ? array_map('intval', (array) $_POST['avisos']) : [];

        foreach ($avisos as $aviso_id) {
            if ($aviso_id > 0) {
                $this->registrar_visualizacion($aviso_id);
            }
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Crear aviso (admin)
     */
    public function ajax_crear_aviso() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = $this->crear_aviso($_POST);

        if ($aviso_id) {
            wp_send_json_success(['aviso_id' => $aviso_id, 'message' => __('Aviso creado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al crear aviso', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Actualizar aviso (admin)
     */
    public function ajax_actualizar_aviso() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        $resultado = $this->actualizar_aviso($aviso_id, $_POST);

        if ($resultado !== false) {
            wp_send_json_success(['message' => __('Aviso actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Eliminar aviso (admin)
     */
    public function ajax_eliminar_aviso() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        $resultado = $this->eliminar_aviso($aviso_id);

        if ($resultado) {
            wp_send_json_success(['message' => __('Aviso eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Estadisticas (admin)
     */
    public function ajax_estadisticas() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success($this->obtener_estadisticas());
    }

    /**
     * AJAX: Enviar notificaciones (admin)
     */
    public function ajax_enviar_notificaciones() {
        check_ajax_referer('flavor_avisos_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $aviso_id = intval($_POST['aviso_id'] ?? 0);
        $aviso = $this->obtener_aviso($aviso_id);

        if (!$aviso) {
            wp_send_json_error(['message' => __('Aviso no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->programar_notificaciones($aviso_id);

        wp_send_json_success(['message' => __('Notificaciones enviadas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    // =========================================================================
    // REST API CALLBACKS
    // =========================================================================

    /**
     * REST: Obtener avisos
     */
    public function rest_get_avisos($request) {
        $avisos = $this->obtener_avisos_activos([
            'categoria_id' => $request->get_param('categoria'),
            'zona_id'      => $request->get_param('zona'),
            'prioridad'    => $request->get_param('prioridad'),
            'busqueda'     => $request->get_param('busqueda'),
            'limite'       => $request->get_param('por_pagina'),
            'offset'       => ($request->get_param('pagina') - 1) * $request->get_param('por_pagina'),
        ]);

        return rest_ensure_response([
            'avisos' => array_map([$this, 'formatear_aviso_para_json'], $avisos),
            'total'  => count($avisos),
        ]);
    }

    /**
     * REST: Obtener aviso individual
     */
    public function rest_get_aviso($request) {
        $aviso = $this->obtener_aviso($request->get_param('id'));

        if (!$aviso) {
            return new WP_Error('not_found', __('Aviso no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $this->registrar_visualizacion($aviso->id);

        return rest_ensure_response($this->formatear_aviso_para_json($aviso));
    }

    /**
     * REST: Crear aviso
     */
    public function rest_create_aviso($request) {
        $aviso_id = $this->crear_aviso($request->get_params());

        if ($aviso_id) {
            return rest_ensure_response(['id' => $aviso_id, 'message' => __('Aviso creado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        return new WP_Error('create_failed', __('Error al crear aviso', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * REST: Actualizar aviso
     */
    public function rest_update_aviso($request) {
        $resultado = $this->actualizar_aviso($request->get_param('id'), $request->get_params());

        if ($resultado !== false) {
            return rest_ensure_response(['message' => __('Aviso actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        return new WP_Error('update_failed', __('Error al actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * REST: Eliminar aviso
     */
    public function rest_delete_aviso($request) {
        $resultado = $this->eliminar_aviso($request->get_param('id'));

        if ($resultado) {
            return rest_ensure_response(['message' => __('Aviso eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        return new WP_Error('delete_failed', __('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * REST: Obtener categorias
     */
    public function rest_get_categorias() {
        return rest_ensure_response($this->obtener_categorias());
    }

    /**
     * REST: Obtener zonas
     */
    public function rest_get_zonas() {
        return rest_ensure_response($this->obtener_zonas());
    }

    /**
     * REST: Suscribir
     */
    public function rest_suscribir($request) {
        $resultado = $this->procesar_suscripcion($request->get_params());

        if ($resultado['success']) {
            return rest_ensure_response($resultado);
        }

        return new WP_Error('subscription_failed', $resultado['message'], ['status' => 400]);
    }

    /**
     * REST: Confirmar lectura
     */
    public function rest_confirmar_lectura($request) {
        $usuario_id = get_current_user_id();
        $aviso_id = $request->get_param('id');

        $resultado = $this->confirmar_lectura_aviso($aviso_id, $usuario_id);

        if ($resultado) {
            return rest_ensure_response(['message' => __('Lectura confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        return new WP_Error('confirm_failed', __('Error al confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * REST: Estadisticas
     */
    public function rest_get_estadisticas() {
        return rest_ensure_response($this->obtener_estadisticas());
    }

    /**
     * Obtiene estadisticas generales
     */
    private function obtener_estadisticas() {
        global $wpdb;

        return [
            'total_avisos'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['avisos']}"),
            'avisos_activos'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['avisos']} WHERE publicado = 1 AND fecha_inicio <= NOW() AND (fecha_fin IS NULL OR fecha_fin >= NOW())"),
            'total_suscriptores'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['suscripciones']} WHERE activa = 1"),
            'total_visualizaciones' => (int) $wpdb->get_var("SELECT SUM(visualizaciones) FROM {$this->tablas['avisos']}"),
            'total_confirmaciones'=> (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['confirmaciones']}"),
            'por_prioridad'       => $wpdb->get_results("SELECT prioridad, COUNT(*) as total FROM {$this->tablas['avisos']} WHERE publicado = 1 GROUP BY prioridad"),
            'por_categoria'       => $wpdb->get_results("SELECT c.nombre, COUNT(a.id) as total FROM {$this->tablas['categorias']} c LEFT JOIN {$this->tablas['avisos']} a ON c.id = a.categoria_id AND a.publicado = 1 GROUP BY c.id ORDER BY total DESC"),
        ];
    }

    // =========================================================================
    // METODOS HEREDADOS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_avisos'       => ['description' => 'Listar avisos municipales activos', 'params' => ['categoria', 'prioridad', 'zona', 'limite']],
            'ver_aviso'           => ['description' => 'Ver detalles de un aviso', 'params' => ['aviso_id']],
            'marcar_leido'        => ['description' => 'Marcar un aviso como leido', 'params' => ['aviso_id']],
            'confirmar_lectura'   => ['description' => 'Confirmar lectura de aviso importante', 'params' => ['aviso_id']],
            'avisos_no_leidos'    => ['description' => 'Ver avisos que no he leido', 'params' => []],
            'avisos_urgentes'     => ['description' => 'Ver avisos urgentes activos', 'params' => []],
            'suscribirse'         => ['description' => 'Suscribirse a avisos', 'params' => ['email', 'categorias', 'zonas']],
            'crear_aviso'         => ['description' => 'Crear nuevo aviso municipal (solo admin)', 'params' => ['titulo', 'contenido', 'categoria', 'prioridad']],
            'estadisticas_aviso'  => ['description' => 'Ver estadisticas de un aviso (solo admin)', 'params' => ['aviso_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_avisos',
            'listado' => 'listar_avisos',
            'explorar' => 'listar_avisos',
            'buscar' => 'listar_avisos',
            'detalle' => 'ver_aviso',
            'ver' => 'ver_aviso',
            'no-leidos' => 'avisos_no_leidos',
            'urgentes' => 'avisos_urgentes',
            'foro' => 'foro_aviso',
            'chat' => 'chat_aviso',
            'multimedia' => 'multimedia_aviso',
            'red-social' => 'red_social_aviso',
            'red_social' => 'red_social_aviso',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return ['success' => false, 'error' => "Accion no implementada: {$action_name}"];
    }

    /**
     * Accion: Listar avisos
     */
    private function action_listar_avisos($params) {
        $avisos = $this->obtener_avisos_activos([
            'categoria_slug' => $params['categoria'] ?? '',
            'prioridad'      => $params['prioridad'] ?? '',
            'zona_slug'      => $params['zona'] ?? '',
            'limite'         => intval($params['limite'] ?? 20),
        ]);

        return [
            'success' => true,
            'total'   => count($avisos),
            'avisos'  => array_map([$this, 'formatear_aviso_para_json'], $avisos),
        ];
    }

    /**
     * Accion: Ver aviso
     */
    private function action_ver_aviso($params) {
        $aviso_id = intval($params['aviso_id'] ?? 0);

        if (!$aviso_id) {
            return ['success' => false, 'error' => __('Se requiere el ID del aviso', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $aviso = $this->obtener_aviso($aviso_id);

        if (!$aviso) {
            return ['success' => false, 'error' => __('Aviso no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $this->registrar_visualizacion($aviso_id);

        return ['success' => true, 'aviso' => $this->formatear_aviso_para_json($aviso)];
    }

    /**
     * Accion: Avisos no leidos
     */
    private function action_avisos_no_leidos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión para ver avisos no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;

        $avisos = $wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM {$this->tablas['avisos']} a
             LEFT JOIN {$this->tablas['lecturas']} l ON a.id = l.aviso_id AND l.usuario_id = %d
             WHERE a.publicado = 1 AND a.fecha_inicio <= NOW() AND (a.fecha_fin IS NULL OR a.fecha_fin >= NOW()) AND l.id IS NULL
             ORDER BY a.prioridad DESC, a.fecha_inicio DESC LIMIT 20",
            $usuario_id
        ));

        return [
            'success'        => true,
            'total_no_leidos'=> count($avisos),
            'avisos'         => array_map([$this, 'formatear_aviso_para_json'], $avisos),
        ];
    }

    /**
     * Accion: Avisos urgentes
     */
    private function action_avisos_urgentes($params) {
        $avisos = $this->obtener_avisos_activos(['prioridad' => 'urgente', 'limite' => 10]);

        return [
            'success' => true,
            'total'   => count($avisos),
            'avisos'  => array_map([$this, 'formatear_aviso_para_json'], $avisos),
        ];
    }

    private function resolve_contextual_aviso($params = []) {
        $aviso_id = intval(
            $params['aviso_id']
            ?? $params['id']
            ?? $_GET['aviso_id']
            ?? $_GET['id']
            ?? 0
        );

        if ($aviso_id <= 0) {
            return null;
        }

        $aviso = $this->obtener_aviso($aviso_id);
        if (!$aviso) {
            return null;
        }

        return [
            'id' => (int) $aviso->id,
            'titulo' => (string) $aviso->titulo,
            'descripcion' => (string) ($aviso->contenido ?? ''),
        ];
    }

    private function action_foro_aviso($params) {
        $aviso = $this->resolve_contextual_aviso($params);
        if (!$aviso) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un aviso para ver su foro.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>'
            . '<p>' . esc_html($aviso['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="aviso_municipal" entidad_id="' . absint($aviso['id']) . '"]')
            . '</div>';
    }

    private function action_chat_aviso($params) {
        $aviso = $this->resolve_contextual_aviso($params);
        if (!$aviso) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un aviso para ver su chat.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($aviso['titulo']) . '</p></div>'
            . '<a href="' . esc_url(Flavor_Platform_Helpers::get_action_url('chat_grupos', 'mensajes') . '?aviso_id=' . absint($aviso['id'])) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="aviso_municipal" entidad_id="' . absint($aviso['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_aviso($params) {
        $aviso = $this->resolve_contextual_aviso($params);
        if (!$aviso) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un aviso para ver sus archivos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($aviso['titulo']) . '</p></div>'
            . '<a href="' . esc_url(Flavor_Platform_Helpers::get_action_url('multimedia', 'subir') . '?aviso_id=' . absint($aviso['id'])) . '" class="button button-primary">'
            . esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="aviso_municipal" entidad_id="' . absint($aviso['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_aviso($params) {
        $aviso = $this->resolve_contextual_aviso($params);
        if (!$aviso) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un aviso para ver su actividad social.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2><p>' . esc_html($aviso['titulo']) . '</p></div>'
            . '<a href="' . esc_url(Flavor_Platform_Helpers::get_action_url('red_social', 'crear') . '?aviso_id=' . absint($aviso['id'])) . '" class="button button-primary">'
            . esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN)
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="aviso_municipal" entidad_id="' . absint($aviso['id']) . '"]')
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'avisos_listar',
                'description'  => 'Ver avisos municipales activos',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Filtrar por categoria'],
                        'prioridad' => ['type' => 'string', 'description' => 'Filtrar por prioridad', 'enum' => ['baja', 'media', 'alta', 'urgente']],
                        'zona'      => ['type' => 'string', 'description' => 'Filtrar por zona'],
                    ],
                ],
            ],
            [
                'name'         => 'avisos_ver',
                'description'  => 'Ver detalle de un aviso',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'aviso_id' => ['type' => 'integer', 'description' => 'ID del aviso'],
                    ],
                    'required'   => ['aviso_id'],
                ],
            ],
            [
                'name'         => 'avisos_no_leidos',
                'description'  => 'Ver avisos que no he leido',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
            [
                'name'         => 'avisos_urgentes',
                'description'  => 'Ver avisos urgentes activos',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Avisos Municipales**

Canal oficial de comunicacion del ayuntamiento con los vecinos.

**Tipos de avisos por categoria:**
- Corte de agua: Interrupciones del suministro
- Corte de luz: Cortes electricos programados
- Obras publicas: Trabajos en via publica
- Eventos: Actividades municipales, fiestas
- Trafico: Cortes de calle, desvios
- Medio ambiente: Alertas ambientales
- Seguridad: Avisos de seguridad ciudadana
- Cultura: Eventos culturales y deportivos
- Convocatorias: Plenos, asambleas, consultas

**Prioridades:**
- Urgente: Rojo, requiere accion inmediata, notificacion push
- Alta: Naranja, importante
- Media: Amarillo, informativo
- Baja: Verde, opcional

**Funcionalidades:**
- Suscripcion por zona y categoria
- Notificaciones push y email
- Confirmacion de lectura para avisos importantes
- Historial de avisos pasados
- Filtros por prioridad, categoria y zona
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            ['pregunta' => 'Como me suscribo a los avisos?', 'respuesta' => 'Usa el formulario de suscripcion para elegir las categorias y zonas que te interesan. Recibiras avisos por email y/o notificaciones push.'],
            ['pregunta' => 'Como se si hay avisos nuevos?', 'respuesta' => 'Recibiras notificaciones push si las tienes activadas. Los avisos no leidos aparecen marcados como "Nuevo".'],
            ['pregunta' => 'Puedo ver avisos antiguos?', 'respuesta' => 'Si, puedes ver el historial de avisos desde la seccion correspondiente, filtrando por fecha o categoria.'],
            ['pregunta' => 'Que significa confirmar lectura?', 'respuesta' => 'Algunos avisos importantes requieren que confirmes que los has leido. Esto queda registrado para el ayuntamiento.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label'       => __('Hero Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Seccion hero con avisos destacados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'hero',
                'icon'        => 'dashicons-megaphone',
                'fields'      => [
                    'titulo'          => ['type' => 'text', 'label' => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'subtitulo'       => ['type' => 'textarea', 'label' => __('Subtitulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Mantente informado de las novedades de tu municipio', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mostrar_urgentes'=> ['type' => 'toggle', 'label' => __('Destacar avisos urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => true],
                ],
                'template'    => 'avisos-municipales/hero',
            ],
            'avisos_lista' => [
                'label'       => __('Lista de Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado cronologico de avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'listings',
                'icon'        => 'dashicons-list-view',
                'fields'      => [
                    'titulo'       => ['type' => 'text', 'label' => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Ultimos Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'mostrar_fecha'=> ['type' => 'toggle', 'label' => __('Mostrar fecha', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => true],
                    'limite'       => ['type' => 'number', 'label' => __('Numero maximo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => 10],
                ],
                'template'    => 'avisos-municipales/avisos-lista',
            ],
            'suscripcion' => [
                'label'       => __('Suscripcion a Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Formulario para recibir notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'forms',
                'icon'        => 'dashicons-email',
                'fields'      => [
                    'titulo'     => ['type' => 'text', 'label' => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Recibe Avisos en tu Email', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    'descripcion'=> ['type' => 'textarea', 'label' => __('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN), 'default' => __('Suscribete y no te pierdas ninguna novedad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'template'    => 'avisos-municipales/suscripcion',
            ],
        ];
    }

    // =========================================================================
    // PANEL UNIFICADO DE GESTIÓN
    // =========================================================================

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'avisos_municipales',
            'label' => __('Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-megaphone',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'admin_quick_links_auto' => true,
            'paginas' => [
                [
                    'slug' => 'avisos-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'avisos-activos',
                    'titulo' => __('Avisos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_activos'],
                    'badge' => [$this, 'contar_avisos_publicados'],
                ],
                [
                    'slug' => 'avisos-nuevo',
                    'titulo' => __('Nuevo Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_nuevo'],
                ],
                [
                    'slug' => 'avisos-archivo',
                    'titulo' => __('Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_archivo'],
                ],
                [
                    'slug' => 'avisos-config',
                    'titulo' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta avisos publicados y no expirados para el badge del admin
     *
     * @return int
     */
    public function contar_avisos_publicados() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_avisos)) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_avisos WHERE estado = 'publicado' AND (fecha_expiracion IS NULL OR fecha_expiracion > %s)",
            current_time('mysql')
        ));
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];
        $estadisticas = [];

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_avisos)) {
            return $estadisticas;
        }

        // Avisos activos
        $avisos_activos = $this->contar_avisos_publicados();
        $estadisticas[] = [
            'icon' => 'dashicons-megaphone',
            'valor' => $avisos_activos,
            'label' => __('Avisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $avisos_activos > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=avisos-activos'),
        ];

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de avisos municipales
     */
    public function render_admin_dashboard() {
        // Renderizar el dashboard completo desde el archivo de vista
        $dashboard_view_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($dashboard_view_path)) {
            include $dashboard_view_path;
        } else {
            echo '<div class="wrap flavor-modulo-page">';
            $this->render_page_header(__('Dashboard de Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN));
            echo '<p>' . __('Panel de control del módulo de avisos municipales.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            echo '</div>';
        }
    }
    public function render_admin_activos() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Avisos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nuevo Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=avisos-nuevo'), 'class' => 'button-primary'],
        ]);

        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];
        $avisos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, prioridad, categoria, created_at
             FROM $tabla_avisos WHERE estado = 'publicado' AND (fecha_expiracion IS NULL OR fecha_expiracion > %s) ORDER BY created_at DESC LIMIT 20",
            current_time('mysql')
        ), ARRAY_A);

        if (!empty($avisos)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($avisos as $aviso) {
                $clase_prioridad = 'priority-' . esc_attr($aviso['prioridad'] ?? 'media');
                echo '<tr>';
                echo '<td><strong>' . esc_html($aviso['titulo']) . '</strong></td>';
                echo '<td><span class="' . esc_attr($clase_prioridad) . '">' . esc_html(ucfirst($aviso['prioridad'] ?? 'media')) . '</span></td>';
                echo '<td>' . esc_html($aviso['categoria'] ?? '-') . '</td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($aviso['created_at']))) . '</td>';
                echo '<td><a href="#" class="button button-small am-ver-aviso" data-id="' . esc_attr($aviso['id']) . '">' . __('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a> <a href="' . esc_url(admin_url('admin.php?page=avisos-nuevo&editar=' . $aviso['id'])) . '" class="button button-small">' . __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay avisos activos en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Modal ver aviso
        echo '<div id="modal-ver-aviso" style="display:none;">
            <div class="modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;">
                <div class="modal-content" style="position:relative;max-width:700px;margin:50px auto;background:#fff;padding:20px;border-radius:4px;">
                    <h2>' . __('Detalle del Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
                    <div id="contenido-aviso"></div>
                    <p><button type="button" class="button" id="cerrar-modal-aviso">' . __('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</button></p>
                </div>
            </div>
        </div>';

        echo '<script>
        jQuery(document).ready(function($) {
            $(".am-ver-aviso").on("click", function(e) {
                e.preventDefault();
                var $row = $(this).closest("tr");
                var titulo = $row.find("td:eq(0)").text();
                var prioridad = $row.find("td:eq(1)").text();
                var categoria = $row.find("td:eq(2)").text();
                var fecha = $row.find("td:eq(3)").text();

                var html = "<table class=\"form-table\">";
                html += "<tr><th>Título:</th><td><strong>" + titulo + "</strong></td></tr>";
                html += "<tr><th>Prioridad:</th><td>" + prioridad + "</td></tr>";
                html += "<tr><th>Categoría:</th><td>" + categoria + "</td></tr>";
                html += "<tr><th>Fecha:</th><td>" + fecha + "</td></tr>";
                html += "</table>";
                $("#contenido-aviso").html(html);
                $("#modal-ver-aviso").fadeIn();
            });
            $("#cerrar-modal-aviso, .modal-overlay").on("click", function(e) {
                if (e.target === this) $("#modal-ver-aviso").fadeOut();
            });
        });
        </script>';

        echo '</div>';
    }

    /**
     * Renderiza la página de nuevo/editar aviso
     */
    public function render_admin_nuevo() {
        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];

        // Verificar si estamos editando
        $aviso_id = isset($_GET['editar']) ? absint($_GET['editar']) : 0;
        $republicar_id = isset($_GET['republicar']) ? absint($_GET['republicar']) : 0;
        $prioridad_default = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : 'media';

        $aviso = null;
        $es_edicion = false;
        $es_republicar = false;

        if ($aviso_id > 0) {
            $aviso = $wpdb->get_row($wpdb->prepare(
                "SELECT id, titulo, contenido, prioridad, categoria, fecha_expiracion
                 FROM $tabla_avisos WHERE id = %d",
                $aviso_id
            ));
            $es_edicion = true;
        } elseif ($republicar_id > 0) {
            $aviso = $wpdb->get_row($wpdb->prepare(
                "SELECT id, titulo, contenido, prioridad, categoria, fecha_expiracion
                 FROM $tabla_avisos WHERE id = %d",
                $republicar_id
            ));
            $es_republicar = true;
        }

        // Valores del formulario
        $titulo = $aviso ? $aviso->titulo : '';
        $contenido = $aviso ? $aviso->contenido : '';
        $prioridad = $aviso ? $aviso->prioridad : $prioridad_default;
        $categoria = $aviso ? $aviso->categoria : '';
        $fecha_exp = $aviso && $aviso->fecha_expiracion ? date('Y-m-d\TH:i', strtotime($aviso->fecha_expiracion)) : '';

        // Título de la página
        $titulo_pagina = $es_edicion ? __('Editar Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) : ($es_republicar ? __('Republicar Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Nuevo Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN));

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header($titulo_pagina, [
            ['label' => __('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=avisos-activos'), 'class' => 'button'],
        ]);

        // Procesar formulario
        if (isset($_POST['publicar_aviso']) || isset($_POST['guardar_borrador'])) {
            $this->procesar_formulario_aviso($es_edicion ? $aviso_id : 0);
        }

        echo '<form method="post" action="" class="flavor-form">';
        wp_nonce_field('flavor_crear_aviso', 'flavor_aviso_nonce');

        if ($es_edicion) {
            echo '<input type="hidden" name="aviso_id" value="' . esc_attr($aviso_id) . '" />';
        }

        echo '<table class="form-table">';

        // Título
        echo '<tr><th scope="row"><label for="titulo">' . __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' <span class="required">*</span></label></th>';
        echo '<td><input type="text" name="titulo" id="titulo" class="regular-text" value="' . esc_attr($titulo) . '" required /></td></tr>';

        // Contenido
        echo '<tr><th scope="row"><label for="contenido">' . __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><textarea name="contenido" id="contenido" rows="8" class="large-text">' . esc_textarea($contenido) . '</textarea></td></tr>';

        // Prioridad
        echo '<tr><th scope="row"><label for="prioridad">' . __('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><select name="prioridad" id="prioridad">';
        $prioridades = ['baja' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'media' => __('Media', FLAVOR_PLATFORM_TEXT_DOMAIN), 'alta' => __('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'urgente' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        foreach ($prioridades as $valor => $etiqueta) {
            $selected = ($prioridad === $valor) ? ' selected' : '';
            echo '<option value="' . esc_attr($valor) . '"' . $selected . '>' . esc_html($etiqueta) . '</option>';
        }
        echo '</select></td></tr>';

        // Categoría
        $tabla_categorias = $this->tablas['categorias'];
        $categorias_lista = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_categorias)) {
            $categorias_lista = $wpdb->get_results("SELECT id, nombre FROM $tabla_categorias ORDER BY orden ASC");
        }
        if (!empty($categorias_lista)) {
            echo '<tr><th scope="row"><label for="categoria">' . __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
            echo '<td><select name="categoria" id="categoria">';
            echo '<option value="">' . __('-- Seleccionar --', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</option>';
            foreach ($categorias_lista as $cat) {
                $selected = ($categoria === $cat->nombre) ? ' selected' : '';
                echo '<option value="' . esc_attr($cat->nombre) . '"' . $selected . '>' . esc_html($cat->nombre) . '</option>';
            }
            echo '</select></td></tr>';
        }

        // Fecha de expiración
        echo '<tr><th scope="row"><label for="fecha_expiracion">' . __('Fecha de expiración', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="datetime-local" name="fecha_expiracion" id="fecha_expiracion" value="' . esc_attr($fecha_exp) . '" />';
        echo '<p class="description">' . __('Dejar vacío para aviso sin fecha de expiración.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></td></tr>';

        echo '</table>';

        echo '<p class="submit">';
        $boton_publicar = $es_edicion ? __('Actualizar Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Publicar Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN);
        echo '<input type="submit" name="publicar_aviso" class="button-primary" value="' . esc_attr($boton_publicar) . '" />';
        if (!$es_edicion) {
            echo ' <input type="submit" name="guardar_borrador" class="button" value="' . __('Guardar Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN) . '" />';
        }
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Procesa el formulario de nuevo/editar aviso
     *
     * @param int $aviso_id ID del aviso a editar (0 para nuevo)
     */
    private function procesar_formulario_aviso($aviso_id = 0) {
        if (!wp_verify_nonce($_POST['flavor_aviso_nonce'] ?? '', 'flavor_crear_aviso')) {
            echo '<div class="notice notice-error"><p>' . __('Error de seguridad. Recarga la página.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];

        $datos = [
            'titulo'           => sanitize_text_field($_POST['titulo'] ?? ''),
            'contenido'        => wp_kses_post($_POST['contenido'] ?? ''),
            'prioridad'        => sanitize_text_field($_POST['prioridad'] ?? 'media'),
            'categoria'        => sanitize_text_field($_POST['categoria'] ?? ''),
            'estado'           => isset($_POST['publicar_aviso']) ? 'publicado' : 'borrador',
            'fecha_publicacion'=> isset($_POST['publicar_aviso']) ? current_time('mysql') : null,
            'fecha_expiracion' => !empty($_POST['fecha_expiracion']) ? sanitize_text_field($_POST['fecha_expiracion']) : null,
            'updated_at'       => current_time('mysql'),
        ];

        if (empty($datos['titulo'])) {
            echo '<div class="notice notice-error"><p>' . __('El título es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        if ($aviso_id > 0) {
            // Actualizar
            $resultado = $wpdb->update($tabla_avisos, $datos, ['id' => $aviso_id]);
            if ($resultado !== false) {
                echo '<div class="notice notice-success"><p>' . __('Aviso actualizado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error al actualizar el aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            }
        } else {
            // Insertar
            $datos['autor_id'] = get_current_user_id();
            $datos['created_at'] = current_time('mysql');

            $resultado = $wpdb->insert($tabla_avisos, $datos);
            if ($resultado) {
                $nuevo_id = $wpdb->insert_id;
                echo '<div class="notice notice-success"><p>' . sprintf(__('Aviso creado correctamente. <a href="%s">Ver avisos activos</a>', FLAVOR_PLATFORM_TEXT_DOMAIN), admin_url('admin.php?page=avisos-activos')) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Error al crear el aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            }
        }
    }

    /**
     * Renderiza la página de archivo de avisos
     */
    public function render_admin_archivo() {
        global $wpdb;
        $tabla_avisos = $this->tablas['avisos'];
        $tabla_categorias = $this->tablas['categorias'];
        $ahora = current_time('mysql');
        $inicio_mes = gmdate('Y-m-01 00:00:00');

        // Filtros
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '',
            'anio'      => isset($_GET['anio']) ? absint($_GET['anio']) : '',
            'buscar'    => isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '',
        ];

        // Paginación
        $por_pagina = 20;
        $pagina = isset($_GET['pag']) ? max(1, absint($_GET['pag'])) : 1;
        $offset = ($pagina - 1) * $por_pagina;

        // Construir query
        $where = "(estado = 'archivado' OR estado = 'expirado' OR (fecha_expiracion IS NOT NULL AND fecha_expiracion <= %s))";
        $params = [$ahora];

        if (!empty($filtros['categoria'])) {
            $where .= " AND categoria = %s";
            $params[] = $filtros['categoria'];
        }

        if (!empty($filtros['anio'])) {
            $where .= " AND YEAR(created_at) = %d";
            $params[] = $filtros['anio'];
        }

        if (!empty($filtros['buscar'])) {
            $where .= " AND titulo LIKE %s";
            $params[] = '%' . $wpdb->esc_like($filtros['buscar']) . '%';
        }

        // Total para paginación
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_avisos WHERE $where",
            ...$params
        ));

        // Avisos
        $avisos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, contenido, categoria, prioridad, fecha_publicacion, fecha_expiracion, total_visualizaciones, created_at
             FROM $tabla_avisos WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            ...array_merge($params, [$por_pagina, $offset])
        ));

        // Estadísticas
        $stats = [
            'total_archivados' => $total,
            'este_mes' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_avisos WHERE (estado = 'archivado' OR (fecha_expiracion IS NOT NULL AND fecha_expiracion <= %s)) AND fecha_expiracion >= %s",
                $ahora,
                $inicio_mes
            )),
            'total_visualizaciones' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_visualizaciones) FROM $tabla_avisos WHERE $where",
                ...$params
            )),
            'total_confirmaciones' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_confirmaciones) FROM $tabla_avisos WHERE $where",
                ...$params
            )),
        ];

        // Categorías para filtro
        $categorias = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_categorias)) {
            $categorias = $wpdb->get_results("SELECT id, nombre FROM $tabla_categorias ORDER BY orden ASC");
        }

        // Paginación
        $paginacion = [
            'total'         => $total,
            'por_pagina'    => $por_pagina,
            'pagina'        => $pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ];

        // Cargar vista
        $vista_path = dirname(__FILE__) . '/views/archivo.php';
        if (file_exists($vista_path)) {
            include $vista_path;
        }
    }

    /**
     * Renderiza la página de configuración de avisos
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN));

        $configuracion_actual = $this->get_default_settings();

        echo '<form method="post" action="">';
        wp_nonce_field('flavor_config_avisos', 'flavor_config_nonce');
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="enviar_push_notifications">' . __('Notificaciones Push', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="checkbox" name="enviar_push_notifications" id="enviar_push_notifications" ' . checked($configuracion_actual['enviar_push_notifications'], true, false) . ' />';
        echo '<p class="description">' . __('Enviar notificaciones push a los suscriptores.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="enviar_email_notifications">' . __('Notificaciones Email', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="checkbox" name="enviar_email_notifications" id="enviar_email_notifications" ' . checked($configuracion_actual['enviar_email_notifications'], true, false) . ' /></td></tr>';

        echo '<tr><th scope="row"><label for="dias_expiracion_default">' . __('Días de expiración por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="number" name="dias_expiracion_default" id="dias_expiracion_default" value="' . esc_attr($configuracion_actual['dias_expiracion_default']) . '" min="0" class="small-text" />';
        echo '<p class="description">' . __('0 = sin expiración automática.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="avisos_por_pagina">' . __('Avisos por página', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="number" name="avisos_por_pagina" id="avisos_por_pagina" value="' . esc_attr($configuracion_actual['avisos_por_pagina']) . '" min="1" max="100" class="small-text" /></td></tr>';

        echo '<tr><th scope="row"><label for="requiere_confirmacion_lectura">' . __('Requiere confirmación de lectura', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="checkbox" name="requiere_confirmacion_lectura" id="requiere_confirmacion_lectura" ' . checked($configuracion_actual['requiere_confirmacion_lectura'], true, false) . ' />';
        echo '<p class="description">' . __('Los usuarios deben confirmar que han leído el aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN) . '" /></p>';
        echo '</form>';
        echo '</div>';
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
            Flavor_Page_Creator::refresh_module_pages('avisos_municipales');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('avisos-municipales');
        if (!$pagina && !get_option('flavor_avisos_municipales_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['avisos_municipales']);
            update_option('flavor_avisos_municipales_pages_created', 1, false);
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
                'title' => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'avisos-municipales',
                'content' => '<h1>' . __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Mantente informado de los avisos y comunicados oficiales del ayuntamiento y tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="avisos_municipales" action="listar" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Mis Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'avisos-municipales/mis-avisos',
                'content' => '<h1>' . __('Mis Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Revisa los avisos que te afectan directamente y confirma su lectura.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="avisos_municipales" action="mis_avisos" columnas="1" limite="20"]',
                'parent' => 'avisos-municipales',
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
            'module'   => 'avisos_municipales',
            'title'    => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Comunicados oficiales del ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '📢',
            'color'    => 'warning', // Usa variable CSS --flavor-warning del tema

            'database' => [
                'table'       => 'flavor_avisos_municipales',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['label' => __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'text', 'required' => true],
                'contenido'   => ['label' => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'wysiwyg', 'required' => true],
                'prioridad'   => ['label' => __('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select', 'options' => ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'urgente' => 'Urgente']],
                'categoria'   => ['label' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select'],
                'zona'        => ['label' => __('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'select'],
                'fecha_inicio'=> ['label' => __('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'datetime'],
                'fecha_fin'   => ['label' => __('Fecha fin', FLAVOR_PLATFORM_TEXT_DOMAIN), 'type' => 'datetime'],
            ],

            'estados' => [
                'publicado' => ['label' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '✅'],
                'borrador'  => ['label' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'gray', 'icon' => '📝'],
                'expirado'  => ['label' => __('Expirado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'orange', 'icon' => '⏰'],
                'archivado' => ['label' => __('Archivado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '📦'],
            ],

            'stats' => [
                'avisos_activos' => ['label' => __('Avisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📢', 'color' => 'orange'],
                'urgentes'       => ['label' => __('Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🚨', 'color' => 'red'],
                'no_leidos'      => ['label' => __('Sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📬', 'color' => 'blue'],
                'suscriptores'   => ['label' => __('Suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👥', 'color' => 'green'],
            ],

            'card' => [
                'title_field'    => 'titulo',
                'subtitle_field' => 'extracto',
                'badge_field'    => 'prioridad',
                'meta_fields'    => ['categoria', 'fecha_inicio', 'visualizaciones'],
            ],

            'tabs' => [
                'todos' => [
                    'label'   => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-megaphone',
                    'content' => 'template:_archive.php',
                ],
                'urgentes' => [
                    'label'   => __('Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-warning',
                    'content' => 'template:urgentes.php',
                ],
                'no-leidos' => [
                    'label'   => __('Sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-email',
                    'content' => 'template:no-leidos.php',
                ],
                'suscripcion' => [
                    'label'   => __('Suscripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-bell',
                    'content' => 'template:suscripcion.php',
                ],
            ],

            'archive' => [
                'columns'      => 2,
                'per_page'     => 12,
                'show_filters' => true,
                'show_search'  => true,
            ],

            'dashboard' => [
                'show_stats'   => true,
                'show_actions' => true,
                'actions'      => [
                    'suscribirse' => ['label' => __('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔔', 'color' => 'orange'],
                    'ver_todos'   => ['label' => __('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📢', 'color' => 'blue'],
                ],
            ],
        ];
    }

    /**
     * Cargar frontend controller
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-avisos-municipales-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Avisos_Municipales_Frontend_Controller::get_instance();
        }
    }
}

if (!class_exists('Flavor_Chat_Avisos_Municipales_Module', false)) {
    class_alias('Flavor_Platform_Avisos_Municipales_Module', 'Flavor_Chat_Avisos_Municipales_Module');
}
