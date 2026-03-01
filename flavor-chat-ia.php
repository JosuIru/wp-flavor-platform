<?php
/**
 * Flavor Platform - Plataforma de comunidades, IA y herramientas para WordPress
 *
 * @package FlavorPlatform
 *
 * Plugin Name: Flavor Platform
 * Plugin URI: https://gailu.net
 * Description: Plataforma integral para WordPress: Red de Comunidades, Asistente IA, Page Builder, Deep Links, Matching, Newsletter, Sellos de Calidad y más.
 * Version: 3.1.1
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
    $is_feed_or_api_request = (
        strpos($request_uri, '/wp-json/') !== false ||
        strpos($request_uri, '/feed/') !== false ||
        strpos($request_uri, 'feed=') !== false ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ||
        preg_match('/\.(xml|rss|atom|json)$/', $request_uri)
    );

    if ($is_feed_or_api_request) {
        @ini_set('display_errors', 0);
    }
}

// Constantes del plugin
define('FLAVOR_CHAT_IA_VERSION', '3.1.1');
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
        // NOTA: El textdomain se carga ANTES de crear la instancia (ver final del archivo)
        // para evitar el warning "_load_textdomain_just_in_time" de WordPress 6.7+.

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

        // Instalador de Base de Datos (tablas de módulos)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-database-installer.php';

        // Sistema de Seguridad - Encriptación de API Keys
        require_once FLAVOR_CHAT_IA_PATH . 'includes/security/class-api-key-encryption.php';

        // Autenticación JWT para REST API (debe cargarse temprano)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-jwt-auth.php';

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
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-pairing.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/trait-app-data-provider.php';

        // API de Configuración de Módulos para Apps Dinámicas
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-config-api.php';

        // Sistema de CPTs para Apps
        require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-cpt-manager.php';

        // Panel de configuración de apps (solo admin)
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/app-integration/class-app-config-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-app-cpt-settings.php';

            // Panel de Gestión Unificado (para módulos)
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/trait-module-admin-pages.php';
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-unified-admin-panel.php';

            // Panel de Estado de Módulos (gaps/TODOs)
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-module-gap-admin.php';
            Flavor_Module_Gap_Admin::get_instance();
        }

        // API de Estado de Módulos (disponible para REST)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-module-gap-status-api.php';

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

        // Traits para módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-notifications.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-frontend-actions.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-admin-ui.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-dashboard-widget.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-integrations.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-whatsapp-features.php';
        // Trait de páginas admin (debe cargarse siempre porque los módulos lo usan)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/trait-module-admin-pages.php';

        // Cargador de Dashboard Tabs para módulos (tabs del dashboard de cliente)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-dashboard-tabs-loader.php';

        // Sistema de Integraciones Dinámicas entre Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-integration-registry.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/config-integrations.php';

        // Funcionalidades Compartidas entre Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-shared-features.php';

        // Puente entre Integraciones y Red de Nodos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-network-content-bridge.php';

        // Sistema de Control de Acceso a Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-access-control.php';

        // Sistema de perfiles de aplicación
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-app-profiles.php';

        // Rate Limiter para APIs (debe cargarse antes de la API móvil)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-api-rate-limiter.php';

        // API REST para apps móviles
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-mobile-api-extensions.php';

        // API de Contenido Nativo (renderizado nativo en apps, sin WebViews)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-native-content-api.php';

        // API REST para Dashboard de Cliente (estadisticas, actividad, widgets)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-client-dashboard-api.php';
        Flavor_Client_Dashboard_API::get_instance();

        // Sistema de Red de Comunidades (Network)
        // NOTA: Las clases de Network se cargan desde el addon flavor-network-communities
        // El addon debe estar activo para usar el sistema de Network

        // Sistema de Privacidad y RGPD
        require_once FLAVOR_CHAT_IA_PATH . 'includes/privacy/class-privacy-manager.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/privacy/class-data-exporter.php';
        Flavor_Privacy_Manager::get_instance();

        // Sistema de Moderación de Contenido
        require_once FLAVOR_CHAT_IA_PATH . 'includes/moderation/class-moderation-manager.php';
        Flavor_Moderation_Manager::get_instance();

        // Sistema de Reputación y Gamificación
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-reputation-manager.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/api/class-reputation-api.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-reputation-integrations.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-reputation-shortcodes.php';
        Flavor_Reputation_Manager::get_instance();
        Flavor_Reputation_API::get_instance();
        Flavor_Reputation_Integrations::get_instance();
        Flavor_Reputation_Shortcodes::get_instance();

        // Sistema Template Orchestrator (activación automatizada de plantillas)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/interface-template-component.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-template-definitions.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-template-orchestrator.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-components-loader.php';

        // Gestor de Roles y Capabilities
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-role-manager.php';

        // Helper de Permisos (sistema granular)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-permission-helper.php';

        // Panel de Administracion de Permisos
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-permissions-admin.php';
        }

        // Comandos WP-CLI para permisos
        if (defined('WP_CLI') && WP_CLI) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/cli/class-permission-command.php';
        }

        // Shortcodes para Landing Pages
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-landing-shortcodes.php';
        // Shortcodes del Portal del Cliente
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-portal-shortcodes.php';
        // Shortcodes Automáticos de Módulos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-shortcodes.php';
        // Fallback de shortcodes GC cuando el módulo no está cargado
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-gc-shortcodes-fallback.php';

        // Dashboards Frontend (Mi Cuenta, Dashboard Cliente)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-user-dashboard.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-client-dashboard.php';

        // Sistema de Páginas Dinámicas (una sola página para todos los módulos)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-dynamic-pages.php';

        // Mi Red Social - Interfaz unificada de módulos sociales (carga temprana para AJAX handlers)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-mi-red-social.php';

        // Personalización de la página de Login de WordPress
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-login-customizer.php';

        // Sistema de CRUD Dinámico (formularios y listados automáticos para módulos)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-dynamic-crud.php';

        // Push Notifications via Firebase Cloud Messaging (debe cargarse ANTES del notification manager)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-push-notification-channel.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-push-token-manager.php';

        // Sistema de Notificaciones (se auto-inicializa al cargar, necesita las clases de canales ya disponibles)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/notifications/class-notification-manager.php';

        // Puente de Notificaciones de Módulos (conecta módulos con el notification manager)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-notifications.php';

        // Widget de Notificaciones para Frontend (shortcodes y dropdown)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-notifications-widget.php';

        // Busqueda Global Cross-Module
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-global-search.php';

        // Editor Visual Mejorado (utilidades compartidas)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/editor/class-editor-history.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/editor/class-color-picker.php';
        // DESACTIVADO: Landing Editor antiguo - reemplazado por Visual Builder
        // require_once FLAVOR_CHAT_IA_PATH . 'includes/editor/class-landing-editor.php';

        // Visual Builder Unificado (v3.0+) - Sistema único de construcción visual
        // Sistema base del Visual Builder
        require_once FLAVOR_CHAT_IA_PATH . 'includes/visual-builder/class-visual-builder.php';
        // Componentes unificados: Landing sections + Themacle + Básicos
        require_once FLAVOR_CHAT_IA_PATH . 'includes/visual-builder/class-vb-all-components.php';
        // Widgets de Dashboard (separado por complejidad)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/visual-builder/class-dashboard-vb-widgets.php';

        // Visual Builder Pro (v2.0+) - Editor fullscreen tipo Photoshop/Figma
        require_once FLAVOR_CHAT_IA_PATH . 'includes/visual-builder-pro/class-vbp-loader.php';

        // Sistema de Animaciones
        require_once FLAVOR_CHAT_IA_PATH . 'includes/animations/class-animation-manager.php';

        // Sistema de Webhooks
        require_once FLAVOR_CHAT_IA_PATH . 'includes/webhooks/class-webhook-manager.php';

        // Dashboard Administrativo
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-manager.php';
        }

        // Dashboard Unificado (frontend y admin - widgets de todos los módulos)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-widget-registry.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-widget-renderer.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-unified-dashboard.php';

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
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-lifecycle.php';

        // Sistema de Navegación y Control de Acceso a Páginas
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-breadcrumbs.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-user-messages.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-page-access-control.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-navigation.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-menu-manager.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-module-dependency-resolver.php';

        // Page Creator V2 y Migrador
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-page-creator-v2.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-page-migrator.php';

        // Page Creator V3 - Sistema modular de páginas
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-page-creator-v3.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-module-gap-admin.php';

        // Menú Adaptativo
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-adaptive-menu.php';

        // Sistema de Notificaciones
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-notifications-system.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-notification-center.php';

        // Theme Customizer (Dark Mode + Colores)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-theme-customizer.php';

        // Dashboard de usuario frontend (Mi Cuenta)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-user-dashboard.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-user-dashboard-api.php';

        // Dashboard de Cliente (panel completo con widgets, estadisticas, actividad)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-client-dashboard.php';

        // Gestor de Newsletter (campanas, tracking, cola de envio)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/newsletter/class-newsletter-manager.php';
        // Plantillas de Newsletter
        require_once FLAVOR_CHAT_IA_PATH . 'includes/newsletter/class-newsletter-template.php';

        // Admin para creación de páginas
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-pages-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-pages-admin-v2.php';

            // Página de documentación en admin
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-documentation-page.php';
            Flavor_Documentation_Page::get_instance();
        }

        // Sistema de Layouts Predefinidos (menús y footers)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-registry.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-renderer.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-api.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-extras.php';
        require_once FLAVOR_CHAT_IA_PATH . 'includes/layouts/class-layout-forms.php';

        // Sistema de Ayuda Contextual con Tooltips
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-contextual-help.php';

        // Sistema de Accesibilidad (WCAG 2.4.1 - Skip Links)
        require_once FLAVOR_CHAT_IA_PATH . 'includes/class-accessibility.php';

        // Admin
        // Design settings se necesita tambien en frontend (preview handler)
        require_once FLAVOR_CHAT_IA_PATH . 'admin/class-design-settings.php';

        // Integración de Theme Customizer con Design Settings
        if (is_admin()) {
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-design-integration.php';

            // Personalización del Admin de WordPress con estilos del plugin
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-admin-customizer.php';
        }

        if (is_admin()) {
            // Gestor centralizado del menú admin (carga antes que las demás clases admin)
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-admin-menu-manager.php';

            // Panel de Administración de Sistemas V3
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-flavor-systems-admin-panel.php';

            // Simplificador de Menús (limpia menús duplicados)
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-flavor-menu-simplifier.php';

            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-chat-settings.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-chat-analytics.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-app-profile-admin.php';
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-unified-modules-view.php';
            // Inicializar para registrar hooks AJAX (necesario para peticiones AJAX)
            Flavor_Unified_Modules_View::get_instance();
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

            // Documentación interactiva de la API REST
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-api-docs.php';

            // Administración de visibilidad de módulos
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-module-visibility-admin.php';

            // Analytics de la Red Social
            require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-social-analytics-admin.php';

            // Personalización del Admin de WordPress con estilos del plugin
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-admin-customizer.php';

            // Admin Shell - Navegación elegante que reemplaza el sidebar de WordPress
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-admin-shell.php';
        }

        // Dashboard Admin: cargar también para REST API (endpoints /admin/*)
        // Detectar peticiones REST por URL ya que REST_REQUEST no está definido aún
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $es_peticion_rest_admin = strpos($request_uri, '/wp-json/flavor/v1/admin/') !== false;
        if (!is_admin() && $es_peticion_rest_admin) {
            require_once FLAVOR_CHAT_IA_PATH . 'admin/class-dashboard.php';
            // No instanciar aquí - se hace via rest_api_init hook
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

        // Cargar Dashboard para REST API (necesario para endpoints /admin/*)
        add_action('rest_api_init', [$this, 'load_dashboard_for_rest'], 1);

        // AJAX temprano
        add_action('plugins_loaded', [$this, 'early_ajax_hooks'], 5);

        // Declarar compatibilidad HPOS de WooCommerce
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        // Añadir schedules personalizados para crons
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        // NOTA: El textdomain se carga directamente en el constructor, antes de load_dependencies(),
        // para evitar el warning "_load_textdomain_just_in_time" de WordPress 6.7+.
        // No es necesario registrar un hook adicional aquí.

        // Limpiar rewrite rules una sola vez tras desactivar controladores frontend
        add_action('init', [$this, 'maybe_flush_frontend_rewrite_rules'], 999);

        // Encolar estilos globales para romper containers del tema
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_styles'], 5);

        // Aviso y activación opcional del tema companion
        add_action('admin_notices', [$this, 'maybe_show_starter_theme_notice']);
        add_action('admin_post_flavor_activate_starter_theme', [$this, 'handle_activate_starter_theme']);
        add_action('admin_post_flavor_install_starter_theme', [$this, 'handle_install_starter_theme']);
        add_action('admin_post_flavor_dismiss_starter_theme_notice', [$this, 'handle_dismiss_starter_theme_notice']);

        // Auto-asignar roles de módulo al activar módulos
        add_action('update_option_flavor_chat_ia_settings', [$this, 'handle_modules_role_assignment'], 10, 2);

        // Crear páginas legales si no existen (para instalaciones existentes)
        add_action('admin_init', [$this, 'maybe_install_legal_pages']);

        // Corregir URLs de placeholder incompletas en la base de datos
        add_action('admin_init', [$this, 'maybe_fix_placeholder_urls']);

        // Cargar estilos comunes de admin (modales, etc.)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_common_styles']);
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
                FLAVOR_CHAT_IA_URL . 'assets/css/admin-modals.css',
                [],
                FLAVOR_CHAT_IA_VERSION
            );
        }
    }

    /**
     * Instala páginas legales si no existen (una sola vez)
     */
    public function maybe_install_legal_pages() {
        if (get_option('flavor_legal_pages_installed') !== '1') {
            if (class_exists('Flavor_Database_Installer')) {
                Flavor_Database_Installer::install_legal_pages();
                update_option('flavor_legal_pages_installed', '1');
            }
        }
    }

    /**
     * Corrige URLs de placeholder en la base de datos convirtiéndolas a SVG data URIs
     *
     * Busca URLs del tipo "400x250?text=..." o "via.placeholder.com/..." y las
     * convierte a SVG data URIs locales que no requieren conexión externa.
     */
    public function maybe_fix_placeholder_urls() {
        // Versión 2: Convertir a SVG
        if (get_option('flavor_placeholder_urls_fixed') === '2') {
            return;
        }

        global $wpdb;

        // Tablas y columnas que pueden contener URLs de imágenes
        $tablas_columnas = [
            $wpdb->prefix . 'flavor_incidencias' => ['imagen'],
            $wpdb->prefix . 'flavor_incidencias_categorias' => ['imagen', 'imagen_url'],
            $wpdb->prefix . 'flavor_eventos' => ['imagen'],
            $wpdb->prefix . 'flavor_espacios' => ['imagen'],
            $wpdb->prefix . 'flavor_cursos' => ['imagen'],
            $wpdb->prefix . 'flavor_talleres' => ['imagen'],
            $wpdb->prefix . 'flavor_marketplace' => ['imagen'],
            $wpdb->prefix . 'flavor_podcast_episodios' => ['imagen'],
        ];

        foreach ($tablas_columnas as $tabla => $columnas) {
            // Verificar si la tabla existe
            $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla));
            if (!$tabla_existe) {
                continue;
            }

            foreach ($columnas as $columna) {
                // Verificar si la columna existe
                $columna_existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $tabla,
                    $columna
                ));

                if (!$columna_existe) {
                    continue;
                }

                // Obtener registros con URLs de placeholder
                $registros = $wpdb->get_results(
                    "SELECT id, {$columna} as url FROM {$tabla} WHERE {$columna} LIKE '%placeholder%' OR {$columna} REGEXP '^[0-9]+x[0-9]+\\\\?text='"
                );

                foreach ($registros as $registro) {
                    $url_original = $registro->url;
                    $url_corregida = Flavor_Chat_Helpers::fix_placeholder_url($url_original);

                    if ($url_corregida !== $url_original) {
                        $wpdb->update(
                            $tabla,
                            [$columna => $url_corregida],
                            ['id' => $registro->id]
                        );
                    }
                }
            }
        }

        update_option('flavor_placeholder_urls_fixed', '2');
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
        // Ensure shortcodes render in content even if another theme/plugin removed the filter.
        add_filter('the_content', 'do_shortcode', 99);

        // Verificar e instalar tablas de módulos si no existen
        $this->maybe_install_module_tables();

        // Verificar actualizaciones de BD (índices, migraciones)
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::maybe_upgrade();
        }

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

        // Inicializar API REST para apps móviles
        if (class_exists('Chat_IA_Mobile_API')) {
            Chat_IA_Mobile_API::get_instance();
        }

        // Inicializar API REST para Dashboard de Cliente
        if (class_exists('Flavor_Client_Dashboard_API')) {
            Flavor_Client_Dashboard_API::get_instance();
        }

        // Inicializar Gestor de Roles
        if (class_exists('Flavor_Role_Manager')) {
            Flavor_Role_Manager::get_instance();
        }

        // Inicializar Control de Acceso a Módulos
        if (class_exists('Flavor_Module_Access_Control')) {
            Flavor_Module_Access_Control::get_instance();
        }

        // Inicializar Control de Acceso a Páginas (middleware de login/permisos)
        if (class_exists('Flavor_Page_Access_Control')) {
            Flavor_Page_Access_Control::get_instance();
        }

        // Inicializar Admin de Visibilidad de Módulos (solo admin)
        if (is_admin() && class_exists('Flavor_Module_Visibility_Admin')) {
            Flavor_Module_Visibility_Admin::get_instance();
        }

        // Inicializar Analytics de la Red Social (solo admin)
        if (is_admin() && class_exists('Flavor_Social_Analytics_Admin')) {
            Flavor_Social_Analytics_Admin::get_instance();
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

        // Inicializar Sistema de Red de Comunidades
        // NOTA: La inicialización se hace desde el addon flavor-network-communities

        if (is_admin() && class_exists('Flavor_Chat_Settings')) {
            Flavor_Chat_Settings::get_instance();
        }

        // Inicializar Admin de Addons
        if (is_admin() && class_exists('Flavor_Addon_Admin')) {
            Flavor_Addon_Admin::get_instance();
        }

        // Inicializar Dashboard Principal
        // Nota: Para REST API se inicializa via hook rest_api_init en load_dashboard_for_rest()
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

        // Inicializar Sistema de Ayuda Contextual
        if (is_admin() && class_exists('Flavor_Contextual_Help')) {
            Flavor_Contextual_Help::get_instance();
        }

        // Inicializar Sistema de Accesibilidad (Skip Links WCAG 2.4.1)
        if (class_exists('Flavor_Accessibility')) {
            Flavor_Accessibility::get_instance();
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

        if (is_admin() && class_exists('Flavor_Module_Gap_Admin')) {
            Flavor_Module_Gap_Admin::get_instance();
        }

        // Inicializar integración WPML
        if (class_exists('Flavor_WPML_Integration')) {
            Flavor_WPML_Integration::get_instance();
        }

        // Inicializar Shortcodes de Landing Pages (frontend y admin para previews)
        if (class_exists('Flavor_Landing_Shortcodes')) {
            Flavor_Landing_Shortcodes::get_instance();
        }

        // Inicializar Shortcodes del Portal del Cliente
        if (class_exists('Flavor_Portal_Shortcodes')) {
            Flavor_Portal_Shortcodes::get_instance();
            // Crear páginas del portal si no existen
            $this->maybe_create_portal_pages();
        }

        // NOTA: Flavor_Module_Shortcodes se inicializa en load_modules()
        // DESPUÉS de cargar los módulos para evitar conflictos de shortcodes

        // Inicializar Dashboards Frontend (Mi Cuenta, Dashboard Cliente)
        if (class_exists('Flavor_User_Dashboard')) {
            new Flavor_User_Dashboard();
        }
        if (class_exists('Flavor_Client_Dashboard')) {
            Flavor_Client_Dashboard::get_instance();
        }

        // Inicializar Dashboard Unificado (frontend con widgets de todos los módulos)
        if (class_exists('Flavor_Unified_Dashboard')) {
            Flavor_Unified_Dashboard::get_instance();
        }

        // Inicializar Sistema de Páginas Dinámicas (una sola página para todos los módulos)
        if (class_exists('Flavor_Dynamic_Pages')) {
            Flavor_Dynamic_Pages::get_instance();
        }

        // Personalización de Login de WordPress (aplica estilos del plugin)
        if (class_exists('Flavor_Login_Customizer')) {
            Flavor_Login_Customizer::get_instance();
        }

        // Inicializar Sistema de CRUD Dinámico (formularios y listados automáticos)
        if (class_exists('Flavor_Dynamic_CRUD')) {
            Flavor_Dynamic_CRUD::get_instance();
        }

        // Inicializar Gestor Automático de Menús
        if (class_exists('Flavor_Module_Menu_Manager')) {
            Flavor_Module_Menu_Manager::get_instance();
        }

        // Inicializar Centro de Notificaciones
        if (class_exists('Flavor_Notification_Center')) {
            Flavor_Notification_Center::get_instance();
        }

        // Inicializar Page Creator V3 (sistema modular de páginas)
        if (class_exists('Flavor_Page_Creator_V3')) {
            Flavor_Page_Creator_V3::get_instance();
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

        // Inicializar Panel de Gestión Unificado
        // Sistema dinámico que muestra las páginas de admin de todos los módulos activos
        // Se inicializa siempre porque también registra endpoints REST
        if (class_exists('Flavor_Unified_Admin_Panel')) {
            Flavor_Unified_Admin_Panel::get_instance();
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

        // Inicializar Visual Builder Pro (editor fullscreen tipo Photoshop/Figma)
        if (function_exists('flavor_vbp')) {
            flavor_vbp();
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

        // Inicializar Dashboard Unificado (frontend y admin)
        if (class_exists('Flavor_Unified_Dashboard')) {
            Flavor_Unified_Dashboard::get_instance();
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

        // Inicializar APIs de Módulos para Apps Móviles
        if (class_exists('Flavor_Huertos_Urbanos_API')) {
            Flavor_Huertos_Urbanos_API::get_instance();
        }
        if (class_exists('Flavor_Reciclaje_API')) {
            Flavor_Reciclaje_API::get_instance();
        }
        if (class_exists('Flavor_Bicicletas_Compartidas_API')) {
            Flavor_Bicicletas_Compartidas_API::get_instance();
        }
        if (class_exists('Flavor_Parkings_API')) {
            Flavor_Parkings_API::get_instance();
        }
        if (class_exists('Flavor_Avisos_Municipales_API')) {
            Flavor_Avisos_Municipales_API::get_instance();
        }
        if (class_exists('Flavor_Ayuda_Vecinal_API')) {
            Flavor_Ayuda_Vecinal_API::get_instance();
        }
        if (class_exists('Flavor_Banco_Tiempo_API')) {
            Flavor_Banco_Tiempo_API::get_instance();
        }
        if (class_exists('Flavor_Marketplace_API')) {
            Flavor_Marketplace_API::get_instance();
        }
        if (class_exists('Flavor_Grupos_Consumo_API')) {
            Flavor_Grupos_Consumo_API::get_instance();
        }
        if (class_exists('Flavor_Incidencias_API')) {
            Flavor_Incidencias_API::get_instance();
        }
        if (class_exists('Flavor_Cursos_API')) {
            Flavor_Cursos_API::get_instance();
        }
        if (class_exists('Flavor_Biblioteca_API')) {
            Flavor_Biblioteca_API::get_instance();
        }
        if (class_exists('Flavor_Talleres_API')) {
            Flavor_Talleres_API::get_instance();
        }
        if (class_exists('Flavor_Espacios_Comunes_API')) {
            Flavor_Espacios_Comunes_API::get_instance();
        }
        if (class_exists('Flavor_Tramites_API')) {
            Flavor_Tramites_API::get_instance();
        }
        if (class_exists('Flavor_Socios_API')) {
            Flavor_Socios_API::get_instance();
        }
        if (class_exists('Flavor_Facturas_API')) {
            Flavor_Facturas_API::get_instance();
        }

        // NOTA: Flavor_Module_Shortcodes se inicializa en load_modules()

        // Inicializar Assets Frontend
        if (class_exists('Flavor_Frontend_Assets')) {
            Flavor_Frontend_Assets::get_instance();
        }

        // Inicializar Dashboard de usuario frontend (Mi Cuenta)
        if (class_exists('Flavor_User_Dashboard')) {
            new Flavor_User_Dashboard();
        }

        // Inicializar Dashboard de cliente frontend
        if (class_exists('Flavor_Client_Dashboard')) {
            Flavor_Client_Dashboard::get_instance();
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
     */
    private function maybe_create_portal_pages() {
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

        // Instalar tablas de todos los módulos (sistema centralizado)
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::install_tables();
            // Crear páginas legales (cookies, términos) si no existen
            Flavor_Database_Installer::install_legal_pages();
        }

        // Nota: Los badges predeterminados se insertan automáticamente
        // en Flavor_Database_Installer::install_tables()

        // Programar crons de reputación (reset semanal y mensual de puntos)
        if (!wp_next_scheduled('flavor_reset_puntos_semanales')) {
            wp_schedule_event(strtotime('next monday'), 'weekly', 'flavor_reset_puntos_semanales');
        }
        if (!wp_next_scheduled('flavor_reset_puntos_mensuales')) {
            wp_schedule_event(strtotime('first day of next month'), 'monthly', 'flavor_reset_puntos_mensuales');
        }

        // Reconstruir caché de metadatos de módulos para optimizar rendimiento
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $loader->rebuild_metadata_cache();
        }

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

        // Si el tema companion existe o está empaquetado y no está activo, mostrar aviso
        $starter_theme = wp_get_theme('flavor-starter');
        if (($starter_theme->exists() || $this->starter_theme_is_bundled()) && get_stylesheet() !== 'flavor-starter') {
            update_option('flavor_show_starter_theme_notice', 1);
        }
    }

    /**
     * Añade schedules personalizados para crons
     *
     * @param array $schedules Schedules existentes
     * @return array Schedules modificados
     */
    public function add_cron_schedules($schedules) {
        // Weekly (si no existe)
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 604800, // 7 días
                'display'  => __('Una vez a la semana', 'flavor-chat-ia'),
            ];
        }

        // Monthly
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = [
                'interval' => 2635200, // 30.5 días aproximadamente
                'display'  => __('Una vez al mes', 'flavor-chat-ia'),
            ];
        }

        return $schedules;
    }

    /**
     * Ruta del tema companion dentro del plugin
     */
    private function get_starter_theme_bundle_path() {
        return FLAVOR_CHAT_IA_PATH . 'assets/companion-theme/flavor-starter';
    }

    /**
     * Comprueba si el tema companion está empaquetado en el plugin
     */
    private function starter_theme_is_bundled() {
        $bundle_path = $this->get_starter_theme_bundle_path();
        return file_exists($bundle_path . '/style.css');
    }

    /**
     * Muestra aviso para activar el tema companion
     */
    public function maybe_show_starter_theme_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!get_option('flavor_show_starter_theme_notice')) {
            return;
        }

        $starter_theme = wp_get_theme('flavor-starter');
        if (get_stylesheet() === 'flavor-starter') {
            delete_option('flavor_show_starter_theme_notice');
            return;
        }

        $is_installed = $starter_theme->exists();
        $is_bundled = $this->starter_theme_is_bundled();

        $activate_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_activate_starter_theme'),
            'flavor_activate_starter_theme'
        );
        $install_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_install_starter_theme'),
            'flavor_install_starter_theme'
        );
        $dismiss_url = wp_nonce_url(
            admin_url('admin-post.php?action=flavor_dismiss_starter_theme_notice'),
            'flavor_dismiss_starter_theme_notice'
        );

        $error_message = get_option('flavor_starter_theme_notice_error');
        if (!empty($error_message)) {
            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e('Flavor Starter disponible', 'flavor-chat-ia'); ?></strong><br>
                <?php
                if ($is_installed) {
                    esc_html_e('El tema companion "Flavor Starter" está instalado y optimizado para este plugin. ¿Quieres activarlo ahora?', 'flavor-chat-ia');
                } elseif ($is_bundled) {
                    esc_html_e('El tema companion "Flavor Starter" está incluido en el plugin. ¿Quieres instalarlo y activarlo ahora?', 'flavor-chat-ia');
                } else {
                    esc_html_e('El tema companion "Flavor Starter" no está instalado. Puedes instalarlo manualmente desde Apariencia > Temas.', 'flavor-chat-ia');
                }
                ?>
            </p>
            <p>
                <?php if ($is_installed): ?>
                    <a href="<?php echo esc_url($activate_url); ?>" class="button button-primary">
                        <?php esc_html_e('Activar tema', 'flavor-chat-ia'); ?>
                    </a>
                <?php elseif ($is_bundled): ?>
                    <a href="<?php echo esc_url($install_url); ?>" class="button button-primary">
                        <?php esc_html_e('Instalar y activar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url($dismiss_url); ?>" class="button button-secondary">
                    <?php esc_html_e('Ahora no', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Activa el tema Flavor Starter
     */
    public function handle_activate_starter_theme() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_admin_action_nonce('flavor_activate_starter_theme')) {
            // Fallback permitido solo para admins si el nonce caducó
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        if ($starter_theme->exists()) {
            $this->ensure_theme_network_enabled('flavor-starter');
            switch_theme('flavor-starter');
        }

        delete_option('flavor_starter_theme_notice_error');
        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url('themes.php'));
        exit;
    }

    /**
     * Instala y activa el tema Flavor Starter desde el bundle del plugin
     */
    public function handle_install_starter_theme() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_admin_action_nonce('flavor_install_starter_theme')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        $theme_root = get_theme_root();
        $dest = trailingslashit($theme_root) . 'flavor-starter';
        $source = $this->get_starter_theme_bundle_path();

        if (!$starter_theme->exists()) {
            if (!file_exists($source)) {
                update_option('flavor_starter_theme_notice_error', __('No se encontró el bundle del tema en el plugin.', 'flavor-chat-ia'));
                wp_safe_redirect(admin_url());
                exit;
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();

            $result = copy_dir($source, $dest);
            if (is_wp_error($result)) {
                update_option('flavor_starter_theme_notice_error', $result->get_error_message());
                wp_safe_redirect(admin_url());
                exit;
            }
        }

        $starter_theme = wp_get_theme('flavor-starter');
        if ($starter_theme->exists()) {
            $this->ensure_theme_network_enabled('flavor-starter');
            switch_theme('flavor-starter');
        }

        delete_option('flavor_starter_theme_notice_error');
        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url('themes.php'));
        exit;
    }

    /**
     * Descarta el aviso del tema companion
     */
    public function handle_dismiss_starter_theme_notice() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', 'flavor-chat-ia'));
        }

        if (!$this->verify_admin_action_nonce('flavor_dismiss_starter_theme_notice')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Enlace caducado. Intenta de nuevo.', 'flavor-chat-ia'));
            }
        }
        delete_option('flavor_show_starter_theme_notice');
        wp_safe_redirect(admin_url());
        exit;
    }

    /**
     * Verifica nonce de acciones admin con fallback seguro para admins.
     *
     * @param string $action
     * @return bool
     */
    private function verify_admin_action_nonce($action) {
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if ($nonce && wp_verify_nonce($nonce, $action)) {
            return true;
        }
        // Si el nonce no es válido, permitir solo si es admin
        return current_user_can('manage_options');
    }

    /**
     * Asegura que el tema esté habilitado en multisite.
     *
     * @param string $stylesheet
     */
    private function ensure_theme_network_enabled($stylesheet) {
        if (!is_multisite()) {
            return;
        }

        $allowed = get_site_option('allowedthemes', []);
        if (!isset($allowed[$stylesheet]) || !$allowed[$stylesheet]) {
            $allowed[$stylesheet] = true;
            update_site_option('allowedthemes', $allowed);
        }
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar crons de reputación
        $crons_reputacion = [
            'flavor_reset_puntos_semanales',
            'flavor_reset_puntos_mensuales',
        ];
        foreach ($crons_reputacion as $cron_hook) {
            $timestamp = wp_next_scheduled($cron_hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $cron_hook);
            }
        }

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

        // Nota: Flavor_App_Pairing usa transients que se limpian automáticamente,
        // no requiere desprogramación de cron

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

        // Centro de Notificaciones
        if (class_exists('Flavor_Notification_Center')) {
            $notification_center = Flavor_Notification_Center::get_instance();
            if (method_exists($notification_center, 'maybe_create_table')) {
                $notification_center->maybe_create_table();
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
        // NOTA: Las tablas se crean desde el addon flavor-network-communities

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
            FLAVOR_CHAT_IA_URL . 'assets/css/flavor-container-override.css',
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
 * WordPress 6.7+ muestra el notice "_load_textdomain_just_in_time" cuando
 * se usa __() antes del hook 'init' y el textdomain no está cargado.
 *
 * Para evitar esto:
 * 1. Cargamos el textdomain en 'plugins_loaded' con prioridad 0 (muy temprano)
 * 2. Inicializamos el plugin en 'plugins_loaded' con prioridad 1 (justo después)
 */

// Cargar textdomain lo más temprano posible durante plugins_loaded
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'flavor-chat-ia',
        false,
        dirname(FLAVOR_CHAT_IA_BASENAME) . '/languages/'
    );
}, 0);

// Inicializar el plugin después de cargar el textdomain
add_action('plugins_loaded', 'flavor_chat_ia', 1);
