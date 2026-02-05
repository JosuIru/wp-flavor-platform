<?php
/**
 * Flavor Platform - Plataforma de comunidades, IA y herramientas para WordPress
 *
 * @package FlavorPlatform
 *
 * Plugin Name: Flavor Platform
 * Plugin URI: https://gailu.net
 * Description: Plataforma integral para WordPress: Red de Comunidades, Asistente IA, Page Builder, Deep Links, Matching, Newsletter, Sellos de Calidad y más.
 * Version: 3.0.0
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

// Constantes del plugin
define('FLAVOR_CHAT_IA_VERSION', '3.1.0');
define('FLAVOR_CHAT_IA_PATH', plugin_dir_path(__FILE__));
define('FLAVOR_CHAT_IA_URL', plugin_dir_url(__FILE__));
define('FLAVOR_CHAT_IA_BASENAME', plugin_basename(__FILE__));

// Modo debug
define('FLAVOR_CHAT_IA_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Logging seguro
 *
 * @param string $message Mensaje a loguear
 * @param string $level Nivel: 'info', 'warning', 'error'
 */
function flavor_chat_ia_log($message, $level = 'info') {
    if (!FLAVOR_CHAT_IA_DEBUG) {
        return;
    }

    if (!WP_DEBUG && $level !== 'error') {
        return;
    }

    $prefix = '[Flavor Platform ' . strtoupper($level) . '] ';
    error_log($prefix . $message);
}

/**
 * Clase principal del plugin
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Carga las dependencias del plugin
     */
    private function load_dependencies() {
        // Sistema de Addons (carga temprana - Versión 3.0+)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-autoloader.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-dependency-checker.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-addon-manager.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-addon-updater.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-addon-license.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-addon-sandbox.php';

        // Registrar autoloader
        Flavor_Autoloader::register();

        // Inicializar Addon Manager
        Flavor_Addon_Manager::get_instance();

        // Motores de IA (multi-proveedor)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/interface-ai-engine.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/class-engine-claude.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/class-engine-openai.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/class-engine-deepseek.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/class-engine-mistral.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/engines/class-engine-manager.php';

        // Helpers/Utilities
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-helpers.php';

        // Sistema de Performance Cache
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-performance-cache.php';

        // Sistema de Registro de Actividad (carga temprana para uso desde todos los modulos)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-activity-log.php';

        // Integración WPML
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-wpml-integration.php';

        // Integración con APKs móviles existentes
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-integration.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-business-directory.php';

        // Sistema de Deep Linking para Apps
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-deep-link-manager.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-deep-link-handler.php';

        // API de Layouts para Apps Nativas
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-layouts-api.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/trait-app-data-provider.php';

        // Sistema de CPTs para Apps
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-cpt-manager.php';

        // Panel de configuración de apps (solo admin)
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-config-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-app-cpt-settings.php';
        }

        // Core
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-session.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-knowledge-base.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-faq-cache.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-escalation.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-antispam.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-claude-engine.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-core.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-ajax.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-stream.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/core/class-chat-assets.php';

        // Interface de módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/interface-chat-module.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-module-loader.php';

        // Sistema de perfiles de aplicación
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-app-profiles.php';

        // Sistema Template Orchestrator (activación automatizada de plantillas)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/interface-template-component.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-template-definitions.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-template-orchestrator.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-components-loader.php';

        // Gestor de Roles y Capabilities
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-role-manager.php';

        // Shortcodes para Landing Pages
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-landing-shortcodes.php';

        // Push Notifications via Firebase Cloud Messaging (debe cargarse ANTES del notification manager)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-push-notification-channel.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-push-token-manager.php';

        // Sistema de Notificaciones (se auto-inicializa al cargar, necesita las clases de canales ya disponibles)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-notification-manager.php';

        // Puente de Notificaciones de Módulos (conecta módulos con el notification manager)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-notifications.php';

        // Busqueda Global Cross-Module
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-global-search.php';

        // Editor Visual Mejorado
        require_once FLAVOR_CHAT_IA_PATH . 'includes/editor/class-editor-history.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/editor/class-color-picker.php';

        // Sistema de Animaciones
        require_once FLAVOR_CHAT_IA_PATH . 'includes/animations/class-animation-manager.php';

        // Sistema de Webhooks
        require_once FLAVOR_CHAT_IA_PATH . 'includes/webhooks/class-webhook-manager.php';

        // Dashboard Administrativo
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-manager.php';
        }

        // Configuración de Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/config/class-module-config.php';

        // Sistema de Temas
        require_once FLAVOR_CHAT_IA_PATH . 'includes/themes/class-theme-manager.php';

        // Documentación API
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-api-documentation.php';

        // REST API para acciones de módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-actions-api.php';

        // Rate Limiter para endpoints REST publicos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-api-rate-limiter.php';

        // Sistema de Formularios de Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-form-processor.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-shortcodes.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-frontend-assets.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-page-creator.php';

        // Dashboard de usuario frontend (Mi Cuenta)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-user-dashboard.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-user-dashboard-api.php';

        // Gestor de Newsletter (campanas, tracking, cola de envio)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/newsletter/class-newsletter-manager.php';
        // Plantillas de Newsletter
        require_once FLAVOR_CHAT_IA_PATH . 'includes/newsletter/class-newsletter-template.php';

        // Admin para creación de páginas
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-pages-admin.php';
        }

        // Sistema de Layouts Predefinidos (menús y footers)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-registry.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-renderer.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-api.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-extras.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-forms.php';

        // Admin
        // Design settings se necesita tambien en frontend (preview handler)
        require_once FLAVOR_CHAT_IA_PATH . 'admin/class-design-settings.php';

        if (is_admin()) {
            // Gestor centralizado del menú admin (carga antes que las demás clases admin)
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-admin-menu-manager.php';

            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-chat-settings.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-chat-analytics.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-app-profile-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-layout-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-activity-log-page.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-health-check.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-export-import.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-addon-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-dashboard.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-setup-wizard.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-guided-tours.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-addon-marketplace.php';

            // Admin de Newsletter (campanas)
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-newsletter-admin.php';

            // Gestor de datos de demostración
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-manager.php';

            // Página de documentación
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-documentation-admin.php';
        }
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

        // AJAX temprano
        add_action('plugins_loaded', [$this, 'early_ajax_hooks'], 5);

        // Declarar compatibilidad HPOS de WooCommerce
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        // Internacionalización (prioridad 1 para que esté disponible antes de que los addons usen __() en plugins_loaded:5)
        add_action('plugins_loaded', [$this, 'load_textdomain'], 1);

        // Limpiar rewrite rules una sola vez tras desactivar controladores frontend
        add_action('init', [$this, 'maybe_flush_frontend_rewrite_rules'], 999);
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
     */
    public function init() {
        // Inicializar Registro de Actividad (antes que los modulos para que puedan usarlo)
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance();
        }

        // Inicializar Gestor Centralizado de Menú Admin (antes que las demás clases admin)
        if (is_admin() && class_exists('Flavor_Admin_Menu_Manager')) {
            Flavor_Admin_Menu_Manager::get_instance();
        }

        // Inicializar pagina admin del Registro de Actividad
        if (is_admin() && class_exists('Flavor_Activity_Log_Page')) {
            Flavor_Activity_Log_Page::get_instance();
        }

        // Inicializar sistema de perfiles de aplicación
        if (class_exists('Flavor_App_Profiles')) {
            Flavor_App_Profiles::get_instance();
        }

        // Inicializar Gestor de Roles
        if (class_exists('Flavor_Role_Manager')) {
            Flavor_Role_Manager::get_instance();
        }

        // Inicializar gestor de motores IA (multi-proveedor)
        if (class_exists('Flavor_Engine_Manager')) {
            Flavor_Engine_Manager::get_instance();
        }

        // Inicializar clases singleton core
        if (class_exists('Flavor_Chat_Core')) {
            Flavor_Chat_Core::get_instance();
        }

        if (class_exists('Flavor_Chat_Assets')) {
            Flavor_Chat_Assets::get_instance();
        }

        if (is_admin() && class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance();
        }

        // Inicializar Admin de Addons
        if (is_admin() && class_exists('Flavor_Addon_Admin')) {
            Flavor_Addon_Admin::get_instance();
        }

        // Inicializar Dashboard Principal
        if (is_admin() && class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance();
        }

        // Inicializar Setup Wizard
        if (is_admin() && class_exists('Flavor_Setup_Wizard')) {
            Flavor_Setup_Wizard::get_instance();
        }

        // Inicializar Sistema de Tours Guiados
        if (is_admin() && class_exists('Flavor_Guided_Tours')) {
            Flavor_Guided_Tours::get_instance();
        }

        // Inicializar Performance Cache y precarga
        if (class_exists('Flavor_Performance_Cache')) {
            $cache = Flavor_Performance_Cache::get_instance();
            // Precarga de datos comunes solo en admin
            if (is_admin()) {
                $cache->precarga();
            }
        }

        // Inicializar sistemas de Addons avanzados
        if (class_exists('Flavor_Addon_Updater')) {
            Flavor_Addon_Updater::get_instance();
        }

        if (class_exists('Flavor_Addon_License')) {
            Flavor_Addon_License::get_instance();
        }

        if (class_exists('Flavor_Addon_Sandbox')) {
            Flavor_Addon_Sandbox::get_instance();
        }

        // Inicializar Marketplace (solo admin)
        if (is_admin() && class_exists('Flavor_Addon_Marketplace')) {
            Flavor_Addon_Marketplace::get_instance();
        }

        // Inicializar Admin de perfiles de aplicación
        if (is_admin() && class_exists('Flavor_App_Profile_Admin')) {
            Flavor_App_Profile_Admin::get_instance();
        }

        // Inicializar Template Orchestrator (automatiza activación de plantillas)
        if (class_exists('Flavor_Template_Orchestrator')) {
            Flavor_Template_Orchestrator::get_instance();
        }

        // Cargar componentes del orchestrator (solo se cargan cuando se necesitan)
        if (class_exists('Flavor_Components_Loader')) {
            Flavor_Components_Loader::get_instance();
        }

        // Inicializar Gestor de datos de demostración
        if (is_admin() && class_exists('Flavor_Demo_Data_Manager')) {
            Flavor_Demo_Data_Manager::get_instance();
        }

        // Inicializar Página de Documentación
        if (is_admin() && class_exists('Flavor_Documentation_Admin')) {
            Flavor_Documentation_Admin::get_instance();
        }

        // Inicializar Health Check / Diagnostico
        if (is_admin() && class_exists('Flavor_Health_Check')) {
            Flavor_Health_Check::get_instance();
        }

        // Inicializar Pages Admin (singleton)
        if (is_admin() && class_exists('Flavor_Pages_Admin')) {
            Flavor_Pages_Admin::get_instance();
        }

        // Inicializar integración WPML
        if (class_exists('Flavor_WPML_Integration')) {
            Flavor_WPML_Integration::get_instance();
        }

        // Inicializar Shortcodes de Landing Pages (frontend y admin para previews)
        if (class_exists('Flavor_Landing_Shortcodes')) {
            Flavor_Landing_Shortcodes::get_instance();
        }

        // Inicializar Sistema de Layouts Predefinidos
        if (function_exists('flavor_layout_registry')) {
            flavor_layout_registry();
        }
        if (class_exists('Flavor_Layout_Renderer')) {
            Flavor_Layout_Renderer::get_instance();
        }
        if (class_exists('Flavor_Layout_Forms')) {
            Flavor_Layout_Forms::get_instance();
        }

        // Inicializar panel de configuración de apps (admin)
        if (is_admin() && class_exists('Flavor_App_Config_Admin')) {
            Flavor_App_Config_Admin::get_instance();
        }

        // Inicializar gestor de CPTs para apps
        if (class_exists('Flavor_App_CPT_Manager')) {
            Flavor_App_CPT_Manager::get_instance();
        }

        // Inicializar settings de CPTs para apps (admin)
        if (is_admin() && class_exists('Flavor_App_CPT_Settings')) {
            Flavor_App_CPT_Settings::get_instance();
        }

        // Registrar AJAX handlers
        if (class_exists('Flavor_Chat_Ajax')) {
            Flavor_Chat_Ajax::register_hooks();
        }

        // Registrar streaming SSE handlers
        if (class_exists('Flavor_Chat_Stream')) {
            Flavor_Chat_Stream::register_hooks();
        }

        // Inicializar Sistema de Notificaciones
        if (class_exists('Flavor_Notification_Manager')) {
            Flavor_Notification_Manager::get_instance();
        }

        // Inicializar Puente de Notificaciones de Módulos
        if (class_exists('Flavor_Module_Notifications')) {
            Flavor_Module_Notifications::get_instance();
        }

        // Inicializar Busqueda Global Cross-Module
        if (class_exists('Flavor_Global_Search')) {
            Flavor_Global_Search::get_instance();
        }

        // Inicializar Exportar/Importar
        if (is_admin() && class_exists('Flavor_Export_Import')) {
            Flavor_Export_Import::get_instance();
        }

        // Inicializar Editor Visual Mejorado
        if (class_exists('Flavor_Editor_History')) {
            Flavor_Editor_History::get_instance();
        }
        if (class_exists('Flavor_Color_Picker')) {
            Flavor_Color_Picker::get_instance();
        }

        // Inicializar Sistema de Animaciones
        if (class_exists('Flavor_Animation_Manager')) {
            Flavor_Animation_Manager::get_instance();
        }

        // Inicializar Sistema de Webhooks
        if (class_exists('Flavor_Webhook_Manager')) {
            Flavor_Webhook_Manager::get_instance();
        }

        // Inicializar Dashboard (solo admin)
        if (is_admin() && class_exists('Flavor_Dashboard_Manager')) {
            Flavor_Dashboard_Manager::get_instance();
        }

        // Inicializar Configuración de Módulos
        if (class_exists('Flavor_Module_Config')) {
            Flavor_Module_Config::get_instance();
        }

        // Inicializar Sistema de Temas
        if (class_exists('Flavor_Theme_Manager')) {
            Flavor_Theme_Manager::get_instance();
        }

        // Inicializar Documentación API
        if (class_exists('Flavor_API_Documentation')) {
            Flavor_API_Documentation::get_instance();
        }

        // Inicializar REST API de acciones de módulos
        if (class_exists('Flavor_Module_Actions_API')) {
            Flavor_Module_Actions_API::get_instance();
        }

        // Inicializar Sistema de Shortcodes de Módulos
        if (class_exists('Flavor_Module_Shortcodes')) {
            Flavor_Module_Shortcodes::get_instance();
        }

        // Inicializar Assets Frontend
        if (class_exists('Flavor_Frontend_Assets')) {
            Flavor_Frontend_Assets::get_instance();
        }

        // Inicializar Dashboard de usuario frontend (Mi Cuenta)
        if (class_exists('Flavor_User_Dashboard')) {
            new Flavor_User_Dashboard();
        }

        // Inicializar API AJAX del Dashboard de usuario
        if (class_exists('Flavor_User_Dashboard_API')) {
            new Flavor_User_Dashboard_API();
        }

        // Inicializar Gestor de Newsletter (campanas, tracking, cola de envio)
        if (class_exists('Flavor_Newsletter_Manager')) {
            Flavor_Newsletter_Manager::get_instance();
        }

        // Inicializar Motor de Plantillas de Newsletter
        if (class_exists('Flavor_Newsletter_Template')) {
            Flavor_Newsletter_Template::get_instance();
        }

        // Inicializar Admin de Newsletter (solo admin)
        if (is_admin() && class_exists('Flavor_Newsletter_Admin')) {
            Flavor_Newsletter_Admin::get_instance();
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
     * Activación del plugin
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

        // Crear tablas base
        $this->create_tables();

        // Crear tablas de módulos
        $this->create_module_tables();

        // Programar cron de cuotas periodicas de socios
        $ruta_subscriptions_activacion = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if ( file_exists( $ruta_subscriptions_activacion ) ) {
            require_once $ruta_subscriptions_activacion;
            Flavor_Socios_Subscriptions::programar_cron();
        }

        // Instalar tablas y programar cron de Newsletter
        if (class_exists('Flavor_Newsletter_Manager')) {
            Flavor_Newsletter_Manager::instalar_tablas();
            Flavor_Newsletter_Manager::programar_cron();
        }

        // Limpiar caché de rewrite
        flush_rewrite_rules();
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Desprogramar limpieza cron del registro de actividad
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::desactivar_cron();
        }

        // Desprogramar cron de notificaciones de modulos
        if (class_exists('Flavor_Module_Notifications')) {
            Flavor_Module_Notifications::desactivar_cron();
        }

        // Desprogramar cron de cuotas periodicas de socios
        $ruta_subscriptions_desactivacion = FLAVOR_CHAT_IA_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if ( file_exists( $ruta_subscriptions_desactivacion ) ) {
            require_once $ruta_subscriptions_desactivacion;
            Flavor_Socios_Subscriptions::desprogramar_cron();
        }

        // Desprogramar cron de Newsletter
        if (class_exists('Flavor_Newsletter_Manager')) {
            Flavor_Newsletter_Manager::desprogramar_cron();
        }

        flush_rewrite_rules();
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de conversaciones
        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';
        $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            language varchar(10) DEFAULT 'es',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            escalated tinyint(1) DEFAULT 0,
            escalation_reason text DEFAULT NULL,
            conversion_type varchar(50) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY started_at (started_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de mensajes
        $table_messages = $wpdb->prefix . 'flavor_chat_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            role enum('user','assistant','system') NOT NULL,
            content text NOT NULL,
            tool_calls text DEFAULT NULL,
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabla de escalaciones
        $table_escalations = $wpdb->prefix . 'flavor_chat_escalations';
        $sql_escalations = "CREATE TABLE IF NOT EXISTS $table_escalations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            reason text NOT NULL,
            summary text NOT NULL,
            contact_method varchar(20) DEFAULT NULL,
            status enum('pending','contacted','resolved') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_escalations);
    }

    /**
     * Crea las tablas de los módulos
     */
    private function create_module_tables() {
        // Banco de Tiempo
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'includes/modules/banco-tiempo/install.php')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/banco-tiempo/install.php';
            if (function_exists('flavor_banco_tiempo_install')) {
                flavor_banco_tiempo_install();
            }
        }

        // Grupos de Consumo
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/install.php')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/grupos-consumo/install.php';
            if (function_exists('flavor_grupos_consumo_install')) {
                flavor_grupos_consumo_install();
            }
        }

        // Deep Link Manager (configuraciones de empresas)
        if (class_exists('Flavor_Deep_Link_Manager')) {
            Flavor_Deep_Link_Manager::create_tables();
        }

        // Sistema de Publicidad Ética - MOVIDO A ADDON (v3.0+)
        // if (class_exists('Flavor_Advertising_System')) {
        //     $advertising = Flavor_Advertising_System::get_instance();
        //     if (method_exists($advertising, 'create_tables')) {
        //         $advertising->create_tables();
        //     }
        // }

        // Activar rewrite rules para Deep Links
        if (class_exists('Flavor_Deep_Link_Handler')) {
            Flavor_Deep_Link_Handler::activate();
        }

        // Sistema de Notificaciones
        if (class_exists('Flavor_Notification_Manager')) {
            $notifications = Flavor_Notification_Manager::get_instance();
            if (method_exists($notifications, 'create_tables')) {
                $notifications->create_tables();
            }
        }

        // Sistema de Webhooks
        if (class_exists('Flavor_Webhook_Manager')) {
            $webhooks = Flavor_Webhook_Manager::get_instance();
            if (method_exists($webhooks, 'create_tables')) {
                $webhooks->create_tables();
            }
        }

        // Sistema de Formularios de Layouts (newsletter, contacto)
        if (class_exists('Flavor_Layout_Forms')) {
            $layout_forms = Flavor_Layout_Forms::get_instance();
            if (method_exists($layout_forms, 'create_tables')) {
                $layout_forms->create_tables();
            }
        }

        // Red de Comunidades
        if (class_exists('Flavor_Network_Installer')) {
            Flavor_Network_Installer::create_tables();
        }

        // Reservas
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php';
            if (function_exists('flavor_reservas_crear_tabla')) {
                flavor_reservas_crear_tabla();
            }
        }

        // Añadir más instalaciones de módulos aquí según se vayan creando
        do_action('flavor_chat_ia_install_modules');
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
}

/**
 * Función helper para obtener la instancia del plugin
 *
 * @return Flavor_Chat_IA
 */
function flavor_chat_ia() {
    return Flavor_Chat_IA::get_instance();
}

// Iniciar el plugin
flavor_chat_ia();
