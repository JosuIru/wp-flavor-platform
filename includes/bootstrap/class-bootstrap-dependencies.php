<?php
/**
 * Bootstrap Dependencies - Carga centralizada de dependencias del plugin
 *
 * Esta clase extrae la lógica de load_dependencies() del archivo principal
 * para mejorar la mantenibilidad y reducir el tamaño del bootstrap.
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
 * Clase que gestiona la carga de todas las dependencias del plugin
 */
final class Flavor_Bootstrap_Dependencies {

    /**
     * Instancia singleton
     *
     * @var Flavor_Bootstrap_Dependencies|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Bootstrap_Dependencies
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
        // Vacío - la carga se hace explícitamente
    }

    /**
     * Carga todas las dependencias del plugin
     *
     * @return void
     */
    public function load_all() {
        $this->load_core_system();
        $this->load_database_system();
        $this->load_ai_engines();
        $this->load_helpers_utilities();
        $this->load_security_auth();
        $this->load_app_integration();
        $this->load_api_system();
        $this->load_network_system();
        $this->load_privacy_moderation();
        $this->load_reputation_system();
        $this->load_orchestrator_roles();
        $this->load_shortcodes_dashboards();
        $this->load_notifications_system();
        $this->load_editor_visual_builder();
        $this->load_dashboard_config();
        $this->load_navigation_forms();
        $this->load_admin_classes();
        $this->load_cli_commands();
    }

    /**
     * Carga el sistema core: autoloader, addons, módulos
     *
     * @return void
     */
    private function load_core_system() {
        // Sistema de Addons (carga temprana - Versión 3.0+)
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-autoloader.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-dependency-checker.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-addon-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-addon-updater.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-addon-license.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-addon-sandbox.php';

        // Registrar autoloader
        Flavor_Autoloader::register();

        // Inicializar Addon Manager
        Flavor_Addon_Manager::get_instance();

        // Core del Chat
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-session.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-knowledge-base.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-admin-knowledge-base.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-module-setup-assistant.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-ai-content-generator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-demo-data-generator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-auto-reply-suggester.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-content-translator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-weekly-report-generator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-data-analyzer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-module-chatbot.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-faq-cache.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-escalation.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-antispam.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-claude-engine.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-core.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-ajax.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-stream.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/core/class-chat-assets.php';

        // Interface de módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/interface-chat-module.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/class-module-loader.php';

        // Traits para módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-module-notifications.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-module-frontend-actions.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-module-admin-ui.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-dashboard-widget.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-module-integrations.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/trait-whatsapp-features.php';

        // Trait de páginas admin (debe cargarse siempre porque los módulos lo usan)
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/trait-module-admin-pages.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-module-admin-pages-helper.php';

        // Cargador de Dashboard Tabs para módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-dashboard-tabs-loader.php';

        // Sistema de Integraciones Dinámicas entre Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/class-integration-registry.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/config-integrations.php';

        // Funcionalidades Compartidas entre Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/class-shared-features.php';

        // Puente entre Integraciones y Red de Nodos
        require_once FLAVOR_PLATFORM_PATH . 'includes/modules/class-network-content-bridge.php';

        // Sistema de Control de Acceso a Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-access-control.php';

        // Sistema de perfiles de aplicación
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-app-profiles.php';
    }

    /**
     * Carga los motores de IA (multi-proveedor)
     *
     * @return void
     */
    private function load_ai_engines() {
        // Interface siempre necesaria
        require_once FLAVOR_PLATFORM_PATH . 'includes/engines/interface-ai-engine.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-manager.php';

        // En admin cargar todos los engines para la configuración
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-claude.php';
            require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-openai.php';
            require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-deepseek.php';
            require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-mistral.php';
            return;
        }

        // En frontend cargar los engines necesarios según configuración
        $settings = flavor_get_main_settings();

        $engine_map = [
            'claude'   => 'class-engine-claude.php',
            'openai'   => 'class-engine-openai.php',
            'deepseek' => 'class-engine-deepseek.php',
            'mistral'  => 'class-engine-mistral.php',
        ];

        // Recopilar todos los providers que necesitamos cargar
        $providers_to_load = [];

        // Provider por defecto
        $active_provider = $settings['active_provider'] ?? 'claude';
        $providers_to_load[] = $active_provider;

        // Provider para frontend (si está configurado y no es 'default')
        $frontend_provider = $settings['ia_provider_frontend'] ?? 'default';
        if ($frontend_provider !== 'default' && isset($engine_map[$frontend_provider])) {
            $providers_to_load[] = $frontend_provider;
        }

        // Provider para backend (si está configurado y no es 'default')
        $backend_provider = $settings['ia_provider_backend'] ?? 'default';
        if ($backend_provider !== 'default' && isset($engine_map[$backend_provider])) {
            $providers_to_load[] = $backend_provider;
        }

        // Eliminar duplicados
        $providers_to_load = array_unique($providers_to_load);

        // Cargar cada engine necesario
        foreach ($providers_to_load as $provider) {
            if (isset($engine_map[$provider])) {
                require_once FLAVOR_PLATFORM_PATH . 'includes/engines/' . $engine_map[$provider];
            }
        }

        // Fallback a Claude si no se cargó ningún engine
        if (empty($providers_to_load) || !isset($engine_map[$active_provider])) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/engines/class-engine-claude.php';
        }
    }

    /**
     * Carga helpers y utilidades
     *
     * @return void
     */
    private function load_helpers_utilities() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-helpers.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-dashboard-severity.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-database-installer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-performance-cache.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-activity-log.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-wpml-integration.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-wp-social-share.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-wp-module-integrations.php';
    }

    /**
     * Carga sistema de seguridad y autenticación
     *
     * @return void
     */
    private function load_security_auth() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/security/class-api-key-encryption.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-jwt-auth.php';
    }

    /**
     * Carga integración con apps móviles
     *
     * @return void
     */
    private function load_app_integration() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-app-integration.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-business-directory.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-deep-link-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-deep-link-handler.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-app-layouts-api.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-app-pairing.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/trait-app-data-provider.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-app-cpt-manager.php';

        // Panel de configuración de apps (solo admin)
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/app-integration/class-app-config-admin.php';
            require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-cpt-settings.php';
        }
    }

    /**
     * Carga sistema de APIs REST
     *
     * @return void
     */
    private function load_api_system() {
        // Rate Limiter para APIs
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-api-rate-limiter.php';

        // API de Configuración de Módulos para Apps Dinámicas
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-module-config-api.php';

        // API de Federación para Red Social
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-federation-api.php';

        // API de Estado de Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-module-gap-status-api.php';

        // API REST para apps móviles
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-mobile-api.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-mobile-api-extensions.php';

        // API de Contenido Nativo
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-native-content-api.php';

        // API REST para Dashboard de Cliente
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-client-dashboard-api.php';
        Flavor_Client_Dashboard_API::get_instance();

        // API REST para cifrado E2E
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-e2e-rest-api.php';

        // Documentación API
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-api-documentation.php';

        // REST API para acciones de módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-module-actions-api.php';

        // API REST para integración con Claude Code / VBP
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-vbp-claude-api.php';

        // API de Diagnóstico VBP (para verificar estado del sistema)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-vbp-diagnostics.php';

        // API de Compatibilidad de Módulos (matriz 3 niveles para APKs)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-module-compatibility-api.php';

        // API de Preview VBP (endpoints públicos para previsualizar landings)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-vbp-preview-api.php';

        // Site Builder API para creación completa de sitios
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-site-builder-api.php';

        // API de Configuración de Sitio (layouts, menús, settings)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-site-config-api.php';
        Flavor_Site_Config_API::get_instance();

        // API de Media (imágenes, iconos, placeholders, fuentes, gradientes)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-media-api.php';
        Flavor_Media_API::get_instance();

        // API de Gestión de Módulos (activar/desactivar, configurar, demo data)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-module-manager-api.php';
        Flavor_Module_Manager_API::get_instance();

        // API de Configuración de Apps/APKs (branding, temas, permisos, build)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-app-config-api.php';
        Flavor_App_Config_API::get_instance();

        // API de SEO (meta tags, Open Graph, Twitter Cards, Schema.org, sitemap)
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-seo-api.php';
        Flavor_SEO_API::get_instance();
    }

    /**
     * Carga sistema de red de comunidades
     *
     * @return void
     */
    private function load_network_system() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-installer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-node.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-api.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-webhooks.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-federation-shortcodes.php';

        // Crear tablas de red si no existen
        Flavor_Network_Installer::create_tables();

        // Inicializar gestor de red y API REST
        Flavor_Network_Manager::get_instance();
        Flavor_Network_API::get_instance();

        // Sistema de webhooks para sincronización en tiempo real
        Flavor_Network_Webhooks::get_instance();

        // Shortcodes de federación (frontend)
        Flavor_Network_Federation_Shortcodes::get_instance();

        // Panel de administración de Red (solo en admin)
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-admin.php';
            require_once FLAVOR_PLATFORM_PATH . 'includes/network/class-network-federation-admin.php';
            Flavor_Network_Admin::get_instance();
            Flavor_Network_Federation_Admin::get_instance();
            add_action('admin_menu', function() {
                Flavor_Network_Admin::get_instance()->add_admin_menus();
            }, 25);
        }
    }

    /**
     * Carga sistema de privacidad y moderación
     *
     * @return void
     */
    private function load_privacy_moderation() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/privacy/class-privacy-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/privacy/class-data-exporter.php';
        Flavor_Privacy_Manager::get_instance();

        require_once FLAVOR_PLATFORM_PATH . 'includes/moderation/class-moderation-manager.php';
        Flavor_Moderation_Manager::get_instance();
    }

    /**
     * Carga sistema de reputación y gamificación
     *
     * @return void
     */
    private function load_reputation_system() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-reputation-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/api/class-reputation-api.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-reputation-integrations.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-reputation-shortcodes.php';

        Flavor_Reputation_Manager::get_instance();
        Flavor_Reputation_API::get_instance();
        Flavor_Reputation_Integrations::get_instance();
        Flavor_Reputation_Shortcodes::get_instance();
    }

    /**
     * Carga orchestrator y sistema de roles
     *
     * @return void
     */
    private function load_orchestrator_roles() {
        // Sistema Template Orchestrator
        require_once FLAVOR_PLATFORM_PATH . 'includes/orchestrator/interface-template-component.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/orchestrator/class-template-definitions.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/orchestrator/class-template-orchestrator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/orchestrator/class-components-loader.php';

        // Gestor de Roles y Capabilities
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-role-manager.php';

        // Helper de Permisos
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-permission-helper.php';

        // Panel de Administracion de Permisos
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'admin/class-permissions-admin.php';
        }

        // Comandos WP-CLI para permisos
        if (defined('WP_CLI') && WP_CLI) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/cli/class-permission-command.php';
        }
    }

    /**
     * Carga shortcodes y dashboards frontend
     *
     * @return void
     */
    private function load_shortcodes_dashboards() {
        // Shortcodes
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-landing-shortcodes.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-portal-shortcodes.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-shortcodes.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-gc-shortcodes-fallback.php';

        // Dashboards Frontend
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-user-dashboard.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-client-dashboard.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-dynamic-pages.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-mi-red-social.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-login-customizer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-dynamic-crud.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-unified-portal.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-user-dashboard-api.php';
    }

    /**
     * Carga sistema de notificaciones
     *
     * @return void
     */
    private function load_notifications_system() {
        // Push Notifications via Firebase
        require_once FLAVOR_PLATFORM_PATH . 'includes/notifications/class-push-notification-channel.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/notifications/class-push-token-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/notifications/class-notification-manager.php';

        // Puente de Notificaciones de Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-notifications.php';

        // Widget de Notificaciones para Frontend
        require_once FLAVOR_PLATFORM_PATH . 'includes/frontend/class-notifications-widget.php';

        // Busqueda Global Cross-Module
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-global-search.php';

        // Sistema de Notificaciones legacy
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-notifications-system.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-notification-center.php';
    }

    /**
     * Carga editor visual y visual builder
     * Optimizado: componentes pesados solo en admin o contextos de edición
     *
     * @return void
     */
    private function load_editor_visual_builder() {
        // Webhooks siempre necesarios (procesan eventos)
        require_once FLAVOR_PLATFORM_PATH . 'includes/webhooks/class-webhook-manager.php';

        // Dashboard VB Widgets necesario en frontend para shortcodes
        require_once FLAVOR_PLATFORM_PATH . 'includes/visual-builder/class-dashboard-vb-widgets.php';

        // Visual Builder SIEMPRE necesario para registrar el post type flavor_landing
        // Sin esto, las URLs de landing no funcionan en frontend ni en REST API
        require_once FLAVOR_PLATFORM_PATH . 'includes/visual-builder/class-visual-builder.php';

        // VBP Loader también siempre necesario para que las landings se rendericen
        require_once FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/class-vbp-loader.php';
        Flavor_VBP_Loader::get_instance();

        // En frontend solo cargar lo mínimo adicional
        if (!is_admin()) {
            // Animaciones solo si están habilitadas
            $settings = flavor_get_main_settings();
            if (!empty($settings['enable_animations'])) {
                require_once FLAVOR_PLATFORM_PATH . 'includes/animations/class-animation-manager.php';
            }
            return;
        }

        // En admin cargar todo el sistema de edición
        require_once FLAVOR_PLATFORM_PATH . 'includes/editor/class-editor-history.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/editor/class-color-picker.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/visual-builder/class-vb-all-components.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/animations/class-animation-manager.php';
    }

    /**
     * Carga dashboard y configuración
     *
     * @return void
     */
    private function load_dashboard_config() {
        // Dashboard Administrativo
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-dashboard-manager.php';
        }

        // Dashboard Unificado
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-widget-registry.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-widget-renderer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-widget-shortcodes.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-unified-dashboard.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/class-dashboard-help.php';

        // Configuración de Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/config/class-module-config.php';

        // Sistema de Temas
        require_once FLAVOR_PLATFORM_PATH . 'includes/themes/class-theme-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/themes/class-module-colors.php';
        Flavor_Theme_Manager::get_instance();

        // Gestor de Newsletter
        require_once FLAVOR_PLATFORM_PATH . 'includes/newsletter/class-newsletter-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/newsletter/class-newsletter-template.php';
    }

    /**
     * Carga navegación y formularios
     *
     * @return void
     */
    private function load_navigation_forms() {
        // Sistema de Formularios de Módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-form-processor.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-frontend-assets.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-page-creator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-lifecycle.php';

        // Sistema de Navegación y Control de Acceso
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-breadcrumbs.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-user-messages.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-page-access-control.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-navigation.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-menu-manager.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-module-dependency-resolver.php';

        // Page Creator V2 y V3
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-page-creator-v2.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-page-migrator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-page-creator-v3.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-module-gap-admin.php';

        // Menú Adaptativo
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-adaptive-menu.php';

        // Theme Customizer
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-theme-customizer.php';
        Flavor_Theme_Customizer::get_instance();

        // Sistema de Layouts Predefinidos
        require_once FLAVOR_PLATFORM_PATH . 'includes/layouts/class-layout-registry.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/layouts/class-layout-renderer.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/layouts/class-layout-api.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/layouts/class-layout-extras.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/layouts/class-layout-forms.php';

        // Inicializar singletons de Layout inmediatamente para que los hooks
        // estén disponibles cuando el tema los necesite (antes de plugins_loaded)
        if (function_exists('flavor_layout_registry')) {
            flavor_layout_registry();
        }
        if (function_exists('flavor_layout_renderer')) {
            flavor_layout_renderer();
        }

        // Sistema de Ayuda Contextual
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-contextual-help.php';

        // Sistema de Accesibilidad
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-accessibility.php';
    }

    /**
     * Carga clases de administración
     *
     * @return void
     */
    private function load_admin_classes() {
        // Design settings se necesita tambien en frontend
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-design-settings.php';
        Flavor_Design_Settings::get_instance();

        // Panel de Gestión Unificado (también registra endpoints REST)
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-unified-admin-panel.php';

        // Panel de Estado de Módulos (solo admin)
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-module-gap-admin.php';
            Flavor_Module_Gap_Admin::get_instance();
        }

        if (is_admin()) {
            $this->load_admin_only_classes();
        }

        // Dashboard Admin para REST API
        $this->maybe_load_dashboard_for_rest();
    }

    /**
     * Carga clases que solo se necesitan en admin
     *
     * @return void
     */
    private function load_admin_only_classes() {
        // Integración de Theme Customizer con Design Settings
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-design-integration.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-admin-customizer.php';

        // Gestor centralizado del menú admin
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-admin-menu-manager.php';

        // Panel de Administración de Sistemas V3
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-flavor-systems-admin-panel.php';

        // Simplificador de Menús
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-flavor-menu-simplifier.php';

        // Clases principales de admin
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-chat-settings.php';
        // Inicializar para registrar hooks AJAX de configuración
        $settings_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Settings')
            : 'Flavor_Chat_Settings';
        $settings_class::get_instance();

        require_once FLAVOR_PLATFORM_PATH . 'admin/class-chat-analytics.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-profile-admin.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-module-dashboards-page.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-unified-modules-view.php';

        // Inicializar para registrar hooks AJAX
        Flavor_Unified_Modules_View::get_instance();

        require_once FLAVOR_PLATFORM_PATH . 'admin/class-layout-admin.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-activity-log-page.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-health-check.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-export-import.php';

        // Inicializar para registrar hooks AJAX de exportación/importación
        Flavor_Export_Import::get_instance();

        require_once FLAVOR_PLATFORM_PATH . 'admin/class-addon-admin.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-dashboard.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-setup-wizard.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-guided-tours.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-addon-marketplace.php';

        // Generador de Apps/Webs con IA
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-generator/class-app-generator.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/app-generator/class-app-generator-admin.php';

        // Gestión de APKs y Apps Móviles
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-analytics-dashboard.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-apk-builder.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-releases.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-dashboard-widget.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-keystore-manager.php';

        // Inicializar clases de gestión de apps
        Flavor_App_Analytics_Dashboard::get_instance();
        Flavor_APK_Builder::get_instance();
        Flavor_App_Releases::get_instance();
        // Widget y Keystore se auto-inicializan

        // Feature Flags para apps móviles
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-feature-flags.php';
        Flavor_Feature_Flags::get_instance();

        // Panel de usuarios de app
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-users-panel.php';
        // Se auto-inicializa

        // Configurador de menús de app drag-drop
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-app-menu-configurator.php';
        // Se auto-inicializa
        // Admin de Newsletter
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-newsletter-admin.php';

        // Gestor de datos de demostración
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-demo-data-manager.php';

        // Documentación
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-documentation-admin.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-api-docs.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-pages-admin.php';
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-pages-admin-v2.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-documentation-page.php';
        Flavor_Documentation_Page::get_instance();

        // Administración de visibilidad de módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-module-visibility-admin.php';

        // Analytics de la Red Social
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-social-analytics-admin.php';

        // Shell Navigation
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-shell-navigation-registry.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-shell-module-registrations.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-shell-favorites-recent.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/class-shell-custom-views.php';

        // Admin Shell
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-admin-shell.php';

        // Admin Breadcrumbs (navegación en páginas de edición)
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-admin-breadcrumbs.php';
        Flavor_Admin_Breadcrumbs::get_instance();

        // Herramientas IA Admin
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-ai-tools-admin.php';

        // Modal de desactivación del plugin
        require_once FLAVOR_PLATFORM_PATH . 'admin/class-deactivation-modal.php';

        // Registrador automático de páginas de dashboard de módulos
        require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-module-dashboards-registrar.php';
    }

    /**
     * Carga Dashboard para peticiones REST API si es necesario
     *
     * @return void
     */
    private function maybe_load_dashboard_for_rest() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $es_peticion_rest_admin = strpos($request_uri, '/wp-json/flavor/v1/admin/') !== false;

        if (!is_admin() && $es_peticion_rest_admin) {
            require_once FLAVOR_PLATFORM_PATH . 'admin/class-dashboard.php';
        }
    }

    /**
     * Carga el sistema de base de datos y migrations
     *
     * @return void
     */
    private function load_database_system() {
        // Sistema de Migrations (v3.3.0+)
        require_once FLAVOR_PLATFORM_PATH . 'includes/database/class-migration-base.php';
        require_once FLAVOR_PLATFORM_PATH . 'includes/database/class-migration-runner.php';

        // Instalador de base de datos original (compatibilidad)
        $installer_path = FLAVOR_PLATFORM_PATH . 'includes/class-database-installer.php';
        if (file_exists($installer_path)) {
            require_once $installer_path;
        }
    }

    /**
     * Carga comandos WP-CLI si está disponible
     *
     * @return void
     */
    private function load_cli_commands() {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        // Comandos de migrations
        require_once FLAVOR_PLATFORM_PATH . 'includes/cli/class-migration-command.php';

        // Comandos de Visual Builder Pro (para integración con Claude Code)
        require_once FLAVOR_PLATFORM_PATH . 'includes/cli/class-vbp-cli.php';
    }
}
