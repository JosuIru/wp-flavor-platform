<?php
/**
 * System Initializer - Inicialización de singletons del sistema
 *
 * Esta clase extrae la lógica del método init() del archivo principal
 * para inicializar todos los singletons y sistemas del plugin.
 *
 * @package FlavorPlatform
 * @subpackage Bootstrap
 * @since 3.2.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona la inicialización de todos los sistemas del plugin
 */
final class Flavor_System_Initializer {

    /**
     * Instancia singleton
     *
     * @var Flavor_System_Initializer|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_System_Initializer
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
        // Vacío
    }

    /**
     * Ejecuta toda la inicialización del sistema
     *
     * @return void
     */
    public function init() {
        $this->ensure_shortcode_filter();
        $this->init_database();
        $this->init_core_systems();
        $this->init_admin_systems();
        $this->init_api_systems();
        $this->init_frontend_systems();
        $this->init_editor_systems();
        $this->init_dashboard_systems();
        $this->init_module_apis();
    }

    /**
     * Asegura que el filtro de shortcodes esté activo
     *
     * @return void
     */
    private function ensure_shortcode_filter() {
        if (false === has_filter('the_content', 'do_shortcode')) {
            add_filter('the_content', 'do_shortcode', 99);
        }
    }

    /**
     * Inicializa sistemas de base de datos
     *
     * @return void
     */
    private function init_database() {
        // Usar la nueva clase Database Setup
        if (class_exists('Flavor_Database_Setup')) {
            $db_setup = Flavor_Database_Setup::get_instance();
            $db_setup->maybe_install_tables();
            $db_setup->maybe_upgrade();
        }
    }

    /**
     * Inicializa sistemas core del plugin
     *
     * @return void
     */
    private function init_core_systems() {
        // Registro de Actividad
        $this->init_singleton('Flavor_Activity_Log');

        // Sistema de perfiles de aplicación
        $this->init_singleton('Flavor_App_Profiles');

        // API REST para apps móviles
        $this->init_singleton('Chat_IA_Mobile_API');

        // API REST para Dashboard de Cliente
        $this->init_singleton('Flavor_Client_Dashboard_API');

        // Gestor de Roles
        $this->init_singleton('Flavor_Role_Manager');

        // Control de Acceso a Módulos
        $this->init_singleton('Flavor_Module_Access_Control');

        // Control de Acceso a Páginas
        $this->init_singleton('Flavor_Page_Access_Control');

        // Gestor de motores IA
        $this->init_singleton('Flavor_Engine_Manager');

        // Core del Chat
        $this->init_singleton('Flavor_Platform_Core');
        $this->init_singleton('Flavor_Platform_Assets');

        // Integración WPML
        $this->init_singleton('Flavor_WPML_Integration');

        // Performance Cache
        if (class_exists('Flavor_Performance_Cache')) {
            $cache = Flavor_Performance_Cache::get_instance();
            if (is_admin()) {
                $cache->precarga();
            }
        }

        // Sistemas de Addons
        $this->init_singleton('Flavor_Addon_Updater');
        $this->init_singleton('Flavor_Addon_License');
        $this->init_singleton('Flavor_Addon_Sandbox');

        // Template Orchestrator
        $this->init_singleton('Flavor_Template_Orchestrator');
        $this->init_singleton('Flavor_Components_Loader');
    }

    /**
     * Inicializa sistemas de administración
     *
     * @return void
     */
    private function init_admin_systems() {
        // Gestor Centralizado de Menú Admin
        $this->init_singleton_admin('Flavor_Admin_Menu_Manager');

        // Pagina admin del Registro de Actividad
        $this->init_singleton_admin('Flavor_Activity_Log_Page');

        // Admin de Visibilidad de Módulos
        $this->init_singleton_admin('Flavor_Module_Visibility_Admin');

        // Analytics de la Red Social
        $this->init_singleton_admin('Flavor_Social_Analytics_Admin');

        // Chat Settings
        $this->init_singleton_admin(
            function_exists('flavor_get_runtime_class_name')
                ? flavor_get_runtime_class_name('Flavor_Chat_Settings')
                : 'Flavor_Chat_Settings'
        );

        // Admin de Addons
        $this->init_singleton_admin('Flavor_Addon_Admin');

        // Dashboard Principal
        $this->init_singleton_admin('Flavor_Dashboard');

        // Setup Wizard
        $this->init_singleton_admin('Flavor_Setup_Wizard');

        // Tours Guiados
        $this->init_singleton_admin('Flavor_Guided_Tours');

        // Ayuda Contextual
        $this->init_singleton_admin('Flavor_Contextual_Help');

        // Marketplace
        $this->init_singleton_admin('Flavor_Addon_Marketplace');

        // Admin de perfiles de aplicación
        $this->init_singleton_admin('Flavor_App_Profile_Admin');

        // Gestor de datos de demostración
        $this->init_singleton_admin('Flavor_Demo_Data_Manager');

        // Documentación
        $this->init_singleton_admin('Flavor_Documentation_Admin');

        // Health Check
        $this->init_singleton_admin('Flavor_Health_Check');

        // Pages Admin
        $this->init_singleton_admin('Flavor_Pages_Admin');

        // Module Gap Admin
        $this->init_singleton_admin('Flavor_Module_Gap_Admin');

        // Newsletter Admin
        $this->init_singleton_admin('Flavor_Newsletter_Admin');

        // Dashboard Manager
        $this->init_singleton_admin('Flavor_Dashboard_Manager');

        // Exportar/Importar
        $this->init_singleton_admin('Flavor_Export_Import');
    }

    /**
     * Inicializa sistemas de API
     *
     * @return void
     */
    private function init_api_systems() {
        // Registrar AJAX handlers
        if (class_exists('Flavor_Platform_Ajax')) {
            Flavor_Platform_Ajax::register_hooks();
        }

        // Registrar streaming SSE handlers
        if (class_exists('Flavor_Platform_Stream')) {
            Flavor_Platform_Stream::register_hooks();
        }

        // Documentación API
        $this->init_singleton('Flavor_API_Documentation');

        // REST API de acciones de módulos
        $this->init_singleton('Flavor_Module_Actions_API');
    }

    /**
     * Inicializa sistemas de frontend
     *
     * @return void
     */
    private function init_frontend_systems() {
        // Accesibilidad
        $this->init_singleton('Flavor_Accessibility');

        // Shortcodes de Landing Pages
        $this->init_singleton('Flavor_Landing_Shortcodes');

        // Shortcodes del Portal del Cliente
        $this->init_singleton('Flavor_Portal_Shortcodes');

        // Dashboards Frontend
        if (class_exists('Flavor_User_Dashboard')) {
            new Flavor_User_Dashboard();
        }
        $this->init_singleton('Flavor_Client_Dashboard');

        // Dashboard Unificado
        $this->init_singleton('Flavor_Unified_Dashboard');

        // Portal Unificado (sistema de layouts)
        $this->init_singleton('Flavor_Unified_Portal');

        // Páginas Dinámicas
        $this->init_singleton('Flavor_Dynamic_Pages');

        // Login Customizer
        $this->init_singleton('Flavor_Login_Customizer');

        // CRUD Dinámico
        $this->init_singleton('Flavor_Dynamic_CRUD');

        // Gestor Automático de Menús
        $this->init_singleton('Flavor_Module_Menu_Manager');

        // Centro de Notificaciones
        $this->init_singleton('Flavor_Notification_Center');

        // Page Creator V3
        $this->init_singleton('Flavor_Page_Creator_V3');

        // Sistema de Layouts Predefinidos
        if (function_exists('flavor_layout_registry')) {
            flavor_layout_registry();
        }
        $this->init_singleton('Flavor_Layout_Renderer');
        $this->init_singleton('Flavor_Layout_Forms');

        // Panel de configuración de apps
        $this->init_singleton_admin('Flavor_App_Config_Admin');

        // Gestor de CPTs para apps
        $this->init_singleton('Flavor_App_CPT_Manager');

        // Settings de CPTs para apps
        $this->init_singleton_admin('Flavor_App_CPT_Settings');

        // Panel de Gestión Unificado
        $this->init_singleton('Flavor_Unified_Admin_Panel');

        // Sistema de Notificaciones
        $this->init_singleton('Flavor_Notification_Manager');

        // Puente de Notificaciones de Módulos
        $this->init_singleton('Flavor_Module_Notifications');

        // Búsqueda Global Cross-Module
        $this->init_singleton('Flavor_Global_Search');

        // Assets Frontend
        $this->init_singleton('Flavor_Frontend_Assets');

        // API AJAX del Dashboard de usuario
        if (class_exists('Flavor_User_Dashboard_API')) {
            new Flavor_User_Dashboard_API();
        }

        // Gestor de Newsletter
        $this->init_singleton('Flavor_Newsletter_Manager');

        // Motor de Plantillas de Newsletter
        $this->init_singleton('Flavor_Newsletter_Template');
    }

    /**
     * Inicializa sistemas de editor
     *
     * @return void
     */
    private function init_editor_systems() {
        // Editor Visual Mejorado
        $this->init_singleton('Flavor_Editor_History');
        $this->init_singleton('Flavor_Color_Picker');

        // Visual Builder Pro
        if (function_exists('flavor_vbp')) {
            flavor_vbp();
        }

        // Sistema de Animaciones
        $this->init_singleton('Flavor_Animation_Manager');

        // Sistema de Webhooks
        $this->init_singleton('Flavor_Webhook_Manager');
    }

    /**
     * Inicializa sistemas de dashboard
     *
     * @return void
     */
    private function init_dashboard_systems() {
        // Dashboard Unificado (ya inicializado en frontend_systems)

        // Configuración de Módulos
        $this->init_singleton('Flavor_Module_Config');

        // Sistema de Temas
        $this->init_singleton('Flavor_Theme_Manager');
    }

    /**
     * Inicializa APIs de módulos para apps móviles
     *
     * @return void
     */
    private function init_module_apis() {
        $module_apis = [
            'Flavor_Huertos_Urbanos_API',
            'Flavor_Reciclaje_API',
            'Flavor_Bicicletas_Compartidas_API',
            'Flavor_Parkings_API',
            'Flavor_Avisos_Municipales_API',
            'Flavor_Ayuda_Vecinal_API',
            'Flavor_Banco_Tiempo_API',
            'Flavor_Marketplace_API',
            'Flavor_Grupos_Consumo_API',
            'Flavor_Incidencias_API',
            'Flavor_Cursos_API',
            'Flavor_Biblioteca_API',
            'Flavor_Talleres_API',
            'Flavor_Espacios_Comunes_API',
            'Flavor_Tramites_API',
            'Flavor_Socios_API',
            'Flavor_Facturas_API',
        ];

        foreach ($module_apis as $api_class) {
            $this->init_singleton($api_class);
        }
    }

    /**
     * Inicializa un singleton si la clase existe
     *
     * @param string $class_name Nombre de la clase
     * @return object|null Instancia o null
     */
    private function init_singleton($class_name) {
        if (class_exists($class_name)) {
            return $class_name::get_instance();
        }
        return null;
    }

    /**
     * Inicializa un singleton solo en admin si la clase existe
     *
     * @param string $class_name Nombre de la clase
     * @return object|null Instancia o null
     */
    private function init_singleton_admin($class_name) {
        if (is_admin() && class_exists($class_name)) {
            return $class_name::get_instance();
        }
        return null;
    }

    /**
     * Carga el Dashboard Admin para peticiones REST API
     *
     * @return void
     */
    public function load_dashboard_for_rest() {
        if (!class_exists('Flavor_Dashboard')) {
            $dashboard_file = FLAVOR_PLATFORM_PATH . 'admin/class-dashboard.php';
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
            }
        }

        if (class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance();
        }
    }
}
