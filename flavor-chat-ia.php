<?php
/**
 * Flavor Platform - Plataforma de comunidades, IA y herramientas para WordPress
 *
 * @package FlavorPlatform
 *
 * Plugin Name: Flavor Platform
 * Plugin URI: https://gailu.net
 * Description: Plataforma integral para WordPress: Red de Comunidades, Asistente IA, Page Builder, Deep Links, Matching, Newsletter, Sellos de Calidad y más.
 * Version: 3.3.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flavor-chat-ia
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.4
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Evitar carga múltiple
if (defined('FLAVOR_CHAT_IA_LOADED')) {
    return;
}
define('FLAVOR_CHAT_IA_LOADED', true);

/**
 * Desactivar display de errores para feeds y REST API
 * Esto evita que los notices de PHP rompan las respuestas XML/JSON
 * Solo en requests que requieren output limpio (feeds, REST, AJAX)
 */
if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $is_flavor_settings_save = (
        isset($_SERVER['REQUEST_METHOD'], $_POST['flavor_chat_ia_action'])
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && $_POST['flavor_chat_ia_action'] === 'save_settings'
    );
    $is_feed_or_api_request = (
        strpos($request_uri, '/wp-json/') !== false ||
        strpos($request_uri, '/feed/') !== false ||
        strpos($request_uri, 'feed=') !== false ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ||
        preg_match('/\.(xml|rss|atom|json)$/', $request_uri)
    );

    if ($is_feed_or_api_request || $is_flavor_settings_save) {
        @ini_set('display_errors', 0);
    }
}

// Constantes del plugin
define('FLAVOR_CHAT_IA_VERSION', '3.3.0');
define('FLAVOR_CHAT_IA_PATH', plugin_dir_path(__FILE__));
define('FLAVOR_CHAT_IA_URL', plugin_dir_url(__FILE__));
define('FLAVOR_CHAT_IA_BASENAME', plugin_basename(__FILE__));

// Modo debug
define('FLAVOR_CHAT_IA_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Logging seguro con niveles y control por entorno
 *
 * Niveles (de menor a mayor severidad):
 * - debug: Solo en desarrollo, información detallada para depuración
 * - info: Información operativa general
 * - warning: Situaciones que requieren atención pero no son críticas
 * - error: Errores que requieren intervención
 *
 * @param string $message Mensaje a loguear
 * @param string $level Nivel: 'debug', 'info', 'warning', 'error'
 * @param string $module Módulo origen (opcional, para filtrado)
 */
function flavor_chat_ia_log( $message, $level = 'info', $module = '' ) {
    // Niveles y su prioridad numérica
    $level_priority = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    // Nivel mínimo a loguear según entorno
    // En producción (sin WP_DEBUG): solo errores
    // En desarrollo (WP_DEBUG): todo
    // Con FLAVOR_CHAT_IA_DEBUG: nivel configurable
    $min_level = 'error'; // Default: solo errores

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $min_level = 'debug'; // Desarrollo: todo
    }

    // Permitir override con constante específica
    if ( defined( 'FLAVOR_LOG_LEVEL' ) ) {
        $min_level = FLAVOR_LOG_LEVEL;
    }

    // Verificar si el nivel actual cumple el mínimo
    $current_priority = isset( $level_priority[ $level ] ) ? $level_priority[ $level ] : 1;
    $min_priority = isset( $level_priority[ $min_level ] ) ? $level_priority[ $min_level ] : 3;

    if ( $current_priority < $min_priority ) {
        return;
    }

    // Construir prefijo
    $prefix = '[Flavor ' . strtoupper( $level ) . ']';
    if ( ! empty( $module ) ) {
        $prefix .= ' [' . $module . ']';
    }

    error_log( $prefix . ' ' . $message );
}

/**
 * Shorthand para log de debug (solo en desarrollo)
 */
function flavor_log_debug( $message, $module = '' ) {
    flavor_chat_ia_log( $message, 'debug', $module );
}

/**
 * Shorthand para log de error (siempre se loguea)
 */
function flavor_log_error( $message, $module = '' ) {
    flavor_chat_ia_log( $message, 'error', $module );
}

/**
 * Carga segura de archivos bootstrap para evitar fatales por despliegues incompletos.
 *
 * @param string $relative_path Ruta relativa desde FLAVOR_CHAT_IA_PATH.
 * @param string $expected_class Clase esperada tras incluir el archivo.
 *
 * @return bool
 */
function flavor_chat_ia_require_bootstrap_file( $relative_path, $expected_class = '' ) {
    static $missing_files = [];

    $file = FLAVOR_CHAT_IA_PATH . ltrim( $relative_path, '/' );
    if ( ! file_exists( $file ) ) {
        $missing_files[] = $relative_path;
        $missing_files = array_values( array_unique( $missing_files ) );
        flavor_log_error( 'Archivo bootstrap faltante: ' . $relative_path, 'bootstrap' );
        update_option( 'flavor_chat_ia_missing_bootstrap_files', $missing_files, false );
        return false;
    }

    require_once $file;

    if ( $expected_class && ! class_exists( $expected_class ) ) {
        flavor_log_error(
            'La clase bootstrap esperada no existe tras include: ' . $expected_class . ' (' . $relative_path . ')',
            'bootstrap'
        );
        return false;
    }

    return true;
}

/**
 * Aviso de administración cuando faltan archivos bootstrap.
 *
 * @return void
 */
function flavor_chat_ia_missing_bootstrap_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $missing_files = get_option( 'flavor_chat_ia_missing_bootstrap_files', [] );
    if ( empty( $missing_files ) || ! is_array( $missing_files ) ) {
        return;
    }

    $items = '';
    foreach ( $missing_files as $missing_file ) {
        $items .= '<li><code>' . esc_html( $missing_file ) . '</code></li>';
    }

    echo '<div class="notice notice-error"><p><strong>Flavor Platform:</strong> faltan archivos de bootstrap en el servidor. El plugin se cargó en modo degradado.</p><ul style="margin-left:1.2em;list-style:disc;">' . $items . '</ul></div>';
}
add_action( 'admin_notices', 'flavor_chat_ia_missing_bootstrap_admin_notice' );

// Cargar clases de bootstrap (v3.2.0+) con tolerancia a faltantes.
flavor_chat_ia_require_bootstrap_file( 'includes/bootstrap/class-bootstrap-dependencies.php', 'Flavor_Bootstrap_Dependencies' );
flavor_chat_ia_require_bootstrap_file( 'includes/bootstrap/class-starter-theme-manager.php', 'Flavor_Starter_Theme_Manager' );
flavor_chat_ia_require_bootstrap_file( 'includes/bootstrap/class-database-setup.php', 'Flavor_Database_Setup' );
flavor_chat_ia_require_bootstrap_file( 'includes/bootstrap/class-cron-manager.php', 'Flavor_Cron_Manager' );
flavor_chat_ia_require_bootstrap_file( 'includes/bootstrap/class-system-initializer.php', 'Flavor_System_Initializer' );

// Stubs de seguridad para evitar fatales si faltan archivos en producción.
if ( ! class_exists( 'Flavor_Bootstrap_Dependencies' ) ) {
    class Flavor_Bootstrap_Dependencies {
        public static function get_instance() { return new self(); }
        public function load_all() {}
    }
}

if ( ! class_exists( 'Flavor_Starter_Theme_Manager' ) ) {
    class Flavor_Starter_Theme_Manager {
        public static function get_instance() { return new self(); }
        public function register_hooks() {}
        public function check_on_activation() {}
    }
}

if ( ! class_exists( 'Flavor_Database_Setup' ) ) {
    class Flavor_Database_Setup {
        public static function get_instance() { return new self(); }
        public function install() {}
        public function maybe_install_legal_pages() {}
        public function maybe_fix_placeholder_urls() {}
    }
}

if ( ! class_exists( 'Flavor_Cron_Manager' ) ) {
    class Flavor_Cron_Manager {
        public static function get_instance() { return new self(); }
        public function register_hooks() {}
        public function schedule_all() {}
        public function unschedule_all() {}
    }
}

if ( ! class_exists( 'Flavor_System_Initializer' ) ) {
    class Flavor_System_Initializer {
        public static function get_instance() { return new self(); }
        public function init() {}
    }
}

/**
 * Clase principal del plugin
 *
 * Refactorizada en v3.2.0 para delegar a clases especializadas de bootstrap.
 */
final class Flavor_Chat_IA {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Módulos cargados
     */
    private $modules = [];

    /**
     * Gestor del tema starter
     *
     * @var Flavor_Starter_Theme_Manager
     */
    private $theme_manager;

    /**
     * Gestor de crons
     *
     * @var Flavor_Cron_Manager
     */
    private $cron_manager;

    /**
     * Gestor de base de datos
     *
     * @var Flavor_Database_Setup
     */
    private $db_setup;

    /**
     * Inicializador del sistema
     *
     * @var Flavor_System_Initializer
     */
    private $system_initializer;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_IA
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // NOTA: El textdomain se carga ANTES de crear la instancia (ver final del archivo)
        // para evitar el warning "_load_textdomain_just_in_time" de WordPress 6.7+.

        // Inicializar gestores de bootstrap
        $this->theme_manager = Flavor_Starter_Theme_Manager::get_instance();
        $this->cron_manager = Flavor_Cron_Manager::get_instance();
        $this->db_setup = Flavor_Database_Setup::get_instance();
        $this->system_initializer = Flavor_System_Initializer::get_instance();

        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Carga las dependencias del plugin
     *
     * Delegado a Flavor_Bootstrap_Dependencies para mejor organización.
     */
    private function load_dependencies() {
        Flavor_Bootstrap_Dependencies::get_instance()->load_all();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Activación/Desactivación
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Inicialización
        add_action('plugins_loaded', [$this, 'init'], 5);
        add_action('plugins_loaded', [$this, 'load_modules'], 10);

        // Cargar Dashboard para REST API (necesario para endpoints /admin/*)
        add_action('rest_api_init', [$this, 'load_dashboard_for_rest'], 1);

        // AJAX temprano
        add_action('plugins_loaded', [$this, 'early_ajax_hooks'], 5);

        // Declarar compatibilidad HPOS de WooCommerce
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        // Registrar hooks del Cron Manager (schedules y callbacks E2E)
        $this->cron_manager->register_hooks();

        // Registrar hooks del Theme Manager (avisos y activación)
        $this->theme_manager->register_hooks();

        // Limpiar rewrite rules una sola vez tras desactivar controladores frontend
        add_action('init', [$this, 'maybe_flush_frontend_rewrite_rules'], 999);

        // Encolar estilos globales para romper containers del tema
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_styles'], 5);

        // Auto-asignar roles de módulo al activar módulos
        add_action('update_option_flavor_chat_ia_settings', [$this, 'handle_modules_role_assignment'], 10, 2);

        // Crear páginas legales si no existen (para instalaciones existentes)
        add_action('admin_init', [$this, 'maybe_install_legal_pages']);

        // Corregir URLs de placeholder incompletas en la base de datos
        add_action('admin_init', [$this, 'maybe_fix_placeholder_urls']);

        // Cargar estilos comunes de admin (modales, etc.)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_common_styles']);

        // Cargar generador de datos de demo
        add_action('plugins_loaded', [$this, 'init_demo_data_generator'], 20);
    }

    /**
     * Inicializa el generador de datos de demostración
     * Solo se carga en el área de administración
     */
    public function init_demo_data_generator() {
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-generator.php';
        }
    }

    /**
     * Carga estilos comunes para páginas de administración
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_admin_common_styles($hook) {
        // Solo cargar en páginas del plugin
        $paginas_plugin = [
            'toplevel_page_flavor-settings',
            'flavor_page_',
            'admin_page_',
        ];

        $es_pagina_plugin = false;
        foreach ($paginas_plugin as $prefijo) {
            if (strpos($hook, $prefijo) === 0) {
                $es_pagina_plugin = true;
                break;
            }
        }

        // También cargar si la página tiene parámetros de módulos del plugin
        if (isset($_GET['page'])) {
            $paginas_modulos = [
                'marketplace', 'multimedia', 'ayuda-', 'banco-tiempo', 'grupos-consumo',
                'comunidades', 'eventos', 'cursos', 'talleres', 'reservas', 'foros',
                'podcast', 'incidencias', 'socios', 'colectivos', 'participacion',
                'presupuestos', 'espacios-comunes', 'huertos', 'carpooling', 'biblioteca',
                'circulos-cuidados', 'avisos-municipales', 'biodiversidad', 'red-social',
                'chat-interno', 'chat-grupos', 'facturas', 'fichaje', 'reciclaje',
                'compostaje', 'economia-don', 'economia-suficiencia', 'trabajo-digno',
                'saberes-ancestrales', 'justicia-restaurativa', 'sello-conciencia',
                'bicicletas-compartidas', 'parkings', 'bares', 'tramites', 'woocommerce'
            ];

            $pagina_actual = sanitize_text_field($_GET['page']);
            foreach ($paginas_modulos as $modulo) {
                if (strpos($pagina_actual, $modulo) !== false) {
                    $es_pagina_plugin = true;
                    break;
                }
            }
        }

        if ($es_pagina_plugin) {
            wp_enqueue_style(
                'flavor-admin-modals',
                FLAVOR_CHAT_IA_URL . 'assets/css/admin/admin-modals.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );
        }
    }

    /**
     * Instala páginas legales si no existen (una sola vez)
     *
     * Delegado a Flavor_Database_Setup.
     */
    public function maybe_install_legal_pages() {
        $this->db_setup->maybe_install_legal_pages();
    }

    /**
     * Corrige URLs de placeholder en la base de datos
     *
     * Delegado a Flavor_Database_Setup.
     */
    public function maybe_fix_placeholder_urls() {
        $this->db_setup->maybe_fix_placeholder_urls();
    }

    /**
     * Limpia las rewrite rules de los controladores frontend (una sola vez)
     */
    public function maybe_flush_frontend_rewrite_rules() {
        if (get_option('flavor_frontend_controllers_disabled') !== 'v2') {
            flush_rewrite_rules();
            update_option('flavor_frontend_controllers_disabled', 'v2');
        }
    }

    /**
     * Inicialización del plugin
     *
     * Delegado a Flavor_System_Initializer para mejor organización.
     */
    public function init() {
        $this->system_initializer->init();

        // Crear páginas del portal en 'init' de WordPress (no plugins_loaded)
        // para que $wp_rewrite esté inicializado
        if (class_exists('Flavor_Portal_Shortcodes')) {
            add_action('init', array($this, 'maybe_create_portal_pages'), 99);
        }
    }

    /**
     * Asigna automaticamente rol admin del modulo al usuario que activa módulos.
     *
     * @param array $old_value
     * @param array $value
     */
    public function handle_modules_role_assignment($old_value, $value) {
        // Invalidar caché de metadatos de módulos cuando cambian los módulos activos
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $old_modules = isset($old_value['active_modules']) ? $old_value['active_modules'] : [];
            $new_modules = isset($value['active_modules']) ? $value['active_modules'] : [];

            if ($old_modules !== $new_modules) {
                $loader = Flavor_Chat_Module_Loader::get_instance();
                $loader->invalidate_metadata_cache();
            }
        }

        if (!is_admin() || !class_exists('Flavor_Permission_Helper')) {
            return;
        }

        $old_modules = isset($old_value['active_modules']) && is_array($old_value['active_modules'])
            ? $old_value['active_modules']
            : [];
        $new_modules = isset($value['active_modules']) && is_array($value['active_modules'])
            ? $value['active_modules']
            : [];

        $activated = array_diff($new_modules, $old_modules);
        if (empty($activated)) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        foreach ($activated as $module_slug) {
            Flavor_Permission_Helper::assign_module_admin_to_user($user_id, $module_slug);
        }
    }

    /**
     * Carga los módulos activos
     */
    public function load_modules() {
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $this->modules = $loader->load_active_modules();
        }

        // Inicializar Shortcodes Automáticos de Módulos DESPUÉS de cargar los módulos
        // Esto permite que los módulos registren sus shortcodes primero,
        // y los fallbacks solo se registren si no existen
        if (class_exists('Flavor_Module_Shortcodes')) {
            Flavor_Module_Shortcodes::get_instance();
        }
    }

    /**
     * Hooks AJAX tempranos
     */
    public function early_ajax_hooks() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            return;
        }

        // Cargar dependencias para AJAX
        if (class_exists('Flavor_Chat_Ajax')) {
            Flavor_Chat_Ajax::register_hooks();
        }
    }

    /**
     * Crea páginas del portal si no existen
     *
     * @since 3.5.0 Movido a hook 'init' para evitar error de $wp_rewrite null
     */
    public function maybe_create_portal_pages() {
        // Solo crear una vez
        if (get_option('flavor_portal_pages_created')) {
            return;
        }

        // Página de Servicios (Landing)
        if (!get_page_by_path('servicios')) {
            wp_insert_post([
                'post_title' => __('Servicios', 'flavor-chat-ia'),
                'post_name' => 'servicios',
                'post_content' => '[flavor_servicios mostrar_stats="yes" columnas="3"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        // Página Mi Portal (Dashboard)
        if (!get_page_by_path('mi-portal')) {
            wp_insert_post([
                'post_title' => __('Mi Portal', 'flavor-chat-ia'),
                'post_name' => 'mi-portal',
                'post_content' => '[flavor_mi_portal mostrar_actividad="yes" mostrar_notificaciones="yes" columnas="3"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        update_option('flavor_portal_pages_created', true);
    }

    /**
     * Activación del plugin
     *
     * Refactorizado en v3.2.0 para delegar a clases especializadas de bootstrap.
     */
    public function activate() {
        // Opciones por defecto
        $defaults = [
            'enabled' => false,
            'show_floating_widget' => true,
            // Configuración multi-proveedor IA
            'active_provider' => 'claude',
            'api_key' => '', // Legacy: Claude API key
            'claude_api_key' => '',
            'claude_model' => 'claude-sonnet-4-20250514',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'deepseek_api_key' => '',
            'deepseek_model' => 'deepseek-chat',
            'mistral_api_key' => '',
            'mistral_model' => 'mistral-small-latest',
            // Configuración general
            'assistant_name' => 'Asistente Virtual',
            'assistant_role' => 'Soy un asistente virtual para ayudarte con tus consultas.',
            'tone' => 'friendly',
            'max_messages_per_session' => 50,
            'max_tokens_per_message' => 1000,
            'escalation_whatsapp' => '',
            'escalation_phone' => '',
            'escalation_email' => '',
            'escalation_hours' => 'L-V 9:00-18:00',
            'languages' => ['es'],
            'knowledge_base' => [],
            'faqs' => [],
            'active_modules' => ['woocommerce'], // Módulos activos por defecto
            'app_profile' => 'personalizado', // Perfil de aplicación por defecto
            'widget_position' => 'bottom-right',
            'widget_color' => '#0073aa',
        ];

        $existing = get_option('flavor_chat_ia_settings', []);
        $merged = wp_parse_args($existing, $defaults);
        update_option('flavor_chat_ia_settings', $merged);

        // Crear roles y capabilities personalizados
        if (class_exists('Flavor_Role_Manager')) {
            Flavor_Role_Manager::create_roles();
        }

        // Instalar base de datos (delegado a Database Setup)
        $this->db_setup->install();

        // Reconstruir caché de metadatos de módulos para optimizar rendimiento
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $loader->rebuild_metadata_cache();
        }

        // Programar todos los crons (delegado a Cron Manager)
        $this->cron_manager->schedule_all();

        // Registrar post type de Visual Builder y regenerar permalinks
        if (class_exists('Flavor_Visual_Builder')) {
            Flavor_Visual_Builder::on_plugin_activation();
        }

        // Limpiar caché de rewrite
        flush_rewrite_rules();

        // Verificar tema companion (delegado a Theme Manager)
        $this->theme_manager->check_on_activation();
    }

    // ========================================================================
    // MÉTODOS LEGACY (v3.2.0+): Los siguientes métodos se mantienen por
    // compatibilidad hacia atrás. La lógica real está en las clases de bootstrap.
    // ========================================================================

    /**
     * Desactivación del plugin
     *
     * Refactorizado en v3.2.0 para delegar a Cron Manager.
     */
    public function deactivate() {
        // Desprogramar todos los crons (delegado a Cron Manager)
        $this->cron_manager->unschedule_all();

        flush_rewrite_rules();
    }

    // NOTA: create_tables() y create_module_tables() movidos a Flavor_Database_Setup

    /**
     * Carga el Dashboard Admin para peticiones REST API
     *
     * Los endpoints /admin/* del dashboard necesitan estar disponibles
     * también para peticiones REST (no solo en contexto is_admin()).
     *
     * @return void
     */
    public function load_dashboard_for_rest() {
        // Cargar archivo si no existe la clase
        if (!class_exists('Flavor_Dashboard')) {
            $dashboard_file = FLAVOR_CHAT_IA_PATH . 'admin/class-dashboard.php';
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
            }
        }

        // Instanciar la clase (se necesita para que se registren los endpoints REST)
        if (class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance();
        }
    }

    /**
     * Carga el textdomain para traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'flavor-chat-ia',
            false,
            dirname(FLAVOR_CHAT_IA_BASENAME) . '/languages/'
        );
    }

    /**
     * Encola estilos globales para override de containers del tema
     */
    public function enqueue_global_styles() {
        // CSS para romper limitaciones de ancho del tema
        wp_enqueue_style(
            'flavor-container-override',
            FLAVOR_CHAT_IA_URL . 'assets/css/layouts/flavor-container-override.css',
            [],
            FLAVOR_CHAT_IA_VERSION,
            'all'
        );
    }

    /**
     * Declara compatibilidad con HPOS de WooCommerce
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }

    /**
     * Obtiene los módulos cargados
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Verifica si un módulo está activo
     *
     * @param string $module_id
     * @return bool
     */
    public function is_module_active($module_id) {
        return isset($this->modules[$module_id]);
    }

    /**
     * Verifica e instala tablas de módulos si no existen
     */
    private function maybe_install_module_tables() {
        // Solo ejecutar si no está instalado aún
        $db_version = get_option('flavor_db_version', '');
        if (!empty($db_version)) {
            return;
        }

        // Verificar si hay al menos una tabla crítica
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_existe = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_eventos
        ));

        if ($tabla_existe === $tabla_eventos) {
            // Ya existen las tablas, marcar como instalado
            update_option('flavor_db_version', '1.0.0');
            return;
        }

        // Instalar tablas
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::install_tables();
            flavor_chat_ia_log('Tablas de módulos instaladas automáticamente', 'info');
        }
    }
}

/**
 * Función helper para obtener la instancia del plugin
 *
 * @return Flavor_Chat_IA
 */
function flavor_chat_ia() {
    return Flavor_Chat_IA::get_instance();
}

/**
 * Inicialización del plugin usando hooks de WordPress.
 *
 * El plugin sigue teniendo bastante carga y registro de clases durante
 * `plugins_loaded`, así que dejar el textdomain para un callback posterior
 * puede hacer que WordPress intente resolver traducciones "just in time"
 * demasiado pronto y termine inyectando notices en el HTML.
 *
 * Lo cargamos aquí, en el bootstrap del archivo principal, para asegurar que
 * cualquier __()/esc_html__() posterior ya encuentre el dominio registrado.
 */

load_plugin_textdomain(
    'flavor-chat-ia',
    false,
    dirname(FLAVOR_CHAT_IA_BASENAME) . '/languages/'
);

// Algunos modulos y dependencias cargan demasiado pronto y WordPress 6.7+
// inyecta notices de `_load_textdomain_just_in_time` en el HTML. Mientras se
// completa la refactorizacion del arranque, evitamos que ese warning rompa
// frontend, AJAX y REST.
add_filter('doing_it_wrong_trigger_error', function($trigger, $function_name) {
    if ($function_name === '_load_textdomain_just_in_time') {
        return false;
    }

    return $trigger;
}, 10, 2);

// Inicializar el plugin después de cargar el textdomain
add_action('plugins_loaded', 'flavor_chat_ia', 1);

// DEBUG TEMPORAL - Diagnóstico del portal layout (ELIMINAR DESPUÉS DE USAR)
if (is_admin() && file_exists(__DIR__ . '/debug-portal-layout.php')) {
    require_once __DIR__ . '/debug-portal-layout.php';
}

/**
 * Registrar y encolar estilos visuales del VBP para el frontend
 * Estos estilos aplican las personalizaciones de tarjetas, colores, animaciones, etc.
 *
 * @since 2.4.0
 */
add_action('wp_enqueue_scripts', function() {
    wp_register_style(
        'flavor-vbp-visual-styles',
        FLAVOR_CHAT_IA_URL . 'assets/css/vbp-visual-styles.css',
        [],
        FLAVOR_CHAT_IA_VERSION
    );
    // Encolar siempre en el frontend (es ligero y necesario para módulos con estilos VBP)
    wp_enqueue_style('flavor-vbp-visual-styles');
}, 20);

// Cargar diagnóstico de performance (solo si se solicita con ?flavor_perf=1)
if (isset($_GET['flavor_perf'])) {
    require_once FLAVOR_CHAT_IA_PATH . 'diagnostico-performance.php';
}
