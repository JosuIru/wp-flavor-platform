<?php
/**
 * REST API para configuración de APKs/Apps móviles desde Claude Code
 *
 * Endpoints para configurar apps Flutter: colores, branding, módulos, permisos.
 *
 * @package Flavor_Platform
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de configuración de Apps
 */
class Flavor_App_Config_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_Config_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * TTL para transients de cache (5 minutos)
     */
    const CACHE_TTL = 300;

    /**
     * Clave de API para autenticación
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Ruta base de mobile-apps
     *
     * @var string
     */
    private $mobile_apps_path = '';

    /**
     * Cache en memoria de la configuración de la app
     *
     * @var array|null
     */
    private $config_cache = null;

    /**
     * Cache en memoria de la versión de Flutter
     *
     * @var string|null
     */
    private $flutter_version_cache = null;

    /**
     * Cache en memoria de iconos disponibles
     *
     * @var array|null
     */
    private $icons_cache = null;

    /**
     * Módulos de app (constante estática para evitar recrear en cada llamada)
     *
     * @var array
     */
    private static $app_modules = null;

    /**
     * Presets de temas (constante estática)
     *
     * @var array
     */
    private static $theme_presets = null;

    /**
     * Layouts disponibles (constante estática)
     *
     * @var array
     */
    private static $layouts = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_Config_API
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $this->api_key = flavor_get_vbp_api_key();
        $this->mobile_apps_path = FLAVOR_PLATFORM_PATH . 'mobile-apps/';

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verificar permisos de API
     *
     * @param WP_REST_Request $request Petición.
     * @return bool|WP_Error
     */
    public function check_api_permission( $request ) {
        $api_key = flavor_get_vbp_api_key_from_request( $request );

        if ( ! flavor_check_vbp_automation_access( $api_key, 'app_config' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'API key inválida', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // === CONFIGURACIÓN DE APP ===

        // Obtener configuración actual de la app
        register_rest_route( self::NAMESPACE, '/app/config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_app_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración de la app
        register_rest_route( self::NAMESPACE, '/app/config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_app_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === BRANDING ===

        // Obtener configuración de branding
        register_rest_route( self::NAMESPACE, '/app/branding', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_branding' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar branding
        register_rest_route( self::NAMESPACE, '/app/branding', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_branding' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === TEMAS Y COLORES ===

        // Obtener tema actual
        register_rest_route( self::NAMESPACE, '/app/theme', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_theme' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar tema
        register_rest_route( self::NAMESPACE, '/app/theme', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_theme' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener presets de temas
        register_rest_route( self::NAMESPACE, '/app/theme-presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_theme_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === MÓDULOS DE APP ===

        // Listar módulos disponibles para app
        register_rest_route( self::NAMESPACE, '/app/modules', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_app_modules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar módulos activos en app
        register_rest_route( self::NAMESPACE, '/app/modules', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_app_modules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === PERMISOS Y CAPACIDADES ===

        // Obtener permisos requeridos
        register_rest_route( self::NAMESPACE, '/app/permissions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_permissions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar permisos
        register_rest_route( self::NAMESPACE, '/app/permissions', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_permissions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === BUILD SETTINGS ===

        // Obtener configuración de build
        register_rest_route( self::NAMESPACE, '/app/build-settings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_build_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración de build
        register_rest_route( self::NAMESPACE, '/app/build-settings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_build_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === GENERACIÓN DE ARCHIVOS ===

        // Generar archivo de configuración Flutter
        register_rest_route( self::NAMESPACE, '/app/generate-config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_flutter_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Generar colores Dart
        register_rest_route( self::NAMESPACE, '/app/generate-colors', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_dart_colors' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Exportar configuración completa
        register_rest_route( self::NAMESPACE, '/app/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_full_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Importar configuración
        register_rest_route( self::NAMESPACE, '/app/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === LAYOUTS DE APP ===

        // Obtener layouts disponibles
        register_rest_route( self::NAMESPACE, '/app/layouts', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_layouts' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar layout
        register_rest_route( self::NAMESPACE, '/app/layouts', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_layout' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === SINCRONIZACIÓN CON SITIO WEB ===

        // Sincronizar configuración de app desde el sitio
        register_rest_route( self::NAMESPACE, '/app/sync-from-site', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'sync_app_from_site' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configuración completa en una sola llamada
        register_rest_route( self::NAMESPACE, '/app/configure-full', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_app_full' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'sync_modules' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'sync_theme' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'sync_branding' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'sync_navigation' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Obtener estado de sincronización
        register_rest_route( self::NAMESPACE, '/app/sync-status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_sync_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * Obtener configuración actual de la app
     */
    public function get_app_config( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'config' => $config,
            'server_url' => home_url(),
            'api_version' => '2.1.0',
            'flutter_version' => $this->detect_flutter_version(),
        ) );
    }

    /**
     * Actualizar configuración de la app
     */
    public function update_app_config( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();

        // Merge recursivo
        $config = $this->array_merge_recursive_distinct( $config, $this->sanitize_config( $params ) );

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'config' => $config,
        ) );
    }

    /**
     * Obtener configuración de branding
     */
    public function get_branding( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'branding' => $config['branding'] ?? $this->get_default_branding(),
            'assets_path' => $this->mobile_apps_path . 'assets/',
            'icons' => $this->get_available_icons(),
        ) );
    }

    /**
     * Actualizar branding
     */
    public function update_branding( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();

        $allowed_fields = array(
            'app_name', 'app_id', 'app_description',
            'logo_url', 'icon_url', 'splash_url',
            'developer_name', 'developer_email', 'developer_website',
            'privacy_policy_url', 'terms_url',
            'app_store_id', 'play_store_id',
        );

        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $config['branding'][ $field ] = sanitize_text_field( $params[ $field ] );
            }
        }

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'branding' => $config['branding'],
        ) );
    }

    /**
     * Obtener tema actual
     */
    public function get_theme( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'theme' => $config['theme'] ?? $this->get_default_theme(),
            'dark_theme' => $config['dark_theme'] ?? $this->get_default_dark_theme(),
            'theme_mode' => $config['theme_mode'] ?? 'system',
        ) );
    }

    /**
     * Actualizar tema
     */
    public function update_theme( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();

        // Tema claro
        if ( isset( $params['theme'] ) && is_array( $params['theme'] ) ) {
            $config['theme'] = $this->sanitize_theme( $params['theme'] );
        }

        // Tema oscuro
        if ( isset( $params['dark_theme'] ) && is_array( $params['dark_theme'] ) ) {
            $config['dark_theme'] = $this->sanitize_theme( $params['dark_theme'] );
        }

        // Modo de tema
        if ( isset( $params['theme_mode'] ) ) {
            $valid_modes = array( 'light', 'dark', 'system' );
            if ( in_array( $params['theme_mode'], $valid_modes, true ) ) {
                $config['theme_mode'] = $params['theme_mode'];
            }
        }

        // Aplicar preset si se proporciona
        if ( isset( $params['preset'] ) ) {
            $presets = $this->get_all_theme_presets();
            if ( isset( $presets[ $params['preset'] ] ) ) {
                $config['theme'] = $presets[ $params['preset'] ]['light'];
                $config['dark_theme'] = $presets[ $params['preset'] ]['dark'];
            }
        }

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'theme' => $config['theme'],
            'dark_theme' => $config['dark_theme'],
            'theme_mode' => $config['theme_mode'],
        ) );
    }

    /**
     * Obtener presets de temas
     */
    public function get_theme_presets( $request ) {
        return rest_ensure_response( array(
            'presets' => $this->get_all_theme_presets(),
            'usage' => 'POST /app/theme { "preset": "modern-blue" }',
        ) );
    }

    /**
     * Obtener módulos disponibles para app
     */
    public function get_app_modules( $request ) {
        $config = $this->get_cached_config();
        $active_modules = $config['modules'] ?? array();

        $all_modules = $this->get_all_app_modules();

        foreach ( $all_modules as &$module ) {
            $module['enabled'] = in_array( $module['id'], $active_modules, true );
        }

        return rest_ensure_response( array(
            'modules' => $all_modules,
            'active' => $active_modules,
            'total_available' => count( $all_modules ),
            'total_active' => count( $active_modules ),
        ) );
    }

    /**
     * Configurar módulos activos en app
     */
    public function set_app_modules( $request ) {
        $modules = $request->get_param( 'modules' );

        if ( ! is_array( $modules ) ) {
            return new WP_Error( 'invalid_modules', 'Se requiere un array de módulos', array( 'status' => 400 ) );
        }

        $config = $this->get_cached_config();
        $valid_modules = array_column( $this->get_all_app_modules(), 'id' );

        // Filtrar solo módulos válidos
        $config['modules'] = array_values( array_intersect( $modules, $valid_modules ) );

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'modules' => $config['modules'],
            'count' => count( $config['modules'] ),
        ) );
    }

    /**
     * Obtener permisos requeridos
     */
    public function get_permissions( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'android' => $config['permissions']['android'] ?? $this->get_default_android_permissions(),
            'ios' => $config['permissions']['ios'] ?? $this->get_default_ios_permissions(),
            'available_android' => $this->get_all_android_permissions(),
            'available_ios' => $this->get_all_ios_permissions(),
        ) );
    }

    /**
     * Configurar permisos
     */
    public function set_permissions( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();

        if ( isset( $params['android'] ) && is_array( $params['android'] ) ) {
            $config['permissions']['android'] = array_map( 'sanitize_text_field', $params['android'] );
        }

        if ( isset( $params['ios'] ) && is_array( $params['ios'] ) ) {
            $config['permissions']['ios'] = array_map( 'sanitize_text_field', $params['ios'] );
        }

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'permissions' => $config['permissions'],
        ) );
    }

    /**
     * Obtener configuración de build
     */
    public function get_build_settings( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'build' => $config['build'] ?? $this->get_default_build_settings(),
            'flutter_path' => $this->mobile_apps_path,
        ) );
    }

    /**
     * Actualizar configuración de build
     */
    public function update_build_settings( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();

        $allowed_fields = array(
            'version_name', 'version_code',
            'android_min_sdk', 'android_target_sdk', 'android_compile_sdk',
            'ios_min_version', 'ios_deployment_target',
            'enable_proguard', 'enable_bitcode',
            'build_type', 'flavor',
        );

        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $config['build'][ $field ] = sanitize_text_field( $params[ $field ] );
            }
        }

        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'build' => $config['build'],
        ) );
    }

    /**
     * Generar archivo de configuración Flutter
     */
    public function generate_flutter_config( $request ) {
        $config = $this->get_cached_config();

        $dart_config = "// Configuración generada automáticamente\n";
        $dart_config .= "// Generado: " . current_time( 'c' ) . "\n\n";
        $dart_config .= "class AppConfig {\n";
        $dart_config .= "  static const String appName = '{$config['branding']['app_name']}';\n";
        $dart_config .= "  static const String appId = '{$config['branding']['app_id']}';\n";
        $dart_config .= "  static const String serverUrl = '" . home_url() . "';\n";
        $dart_config .= "  static const String apiVersion = '2.1.0';\n\n";

        // Módulos activos
        $dart_config .= "  static const List<String> enabledModules = [\n";
        foreach ( $config['modules'] ?? array() as $module ) {
            $dart_config .= "    '$module',\n";
        }
        $dart_config .= "  ];\n\n";

        // Configuración de tema
        $dart_config .= "  // Theme configuration\n";
        $dart_config .= "  static const String themeMode = '{$config['theme_mode']}';\n";

        $dart_config .= "}\n";

        // Guardar archivo
        $file_path = $this->mobile_apps_path . 'lib/core/config/app_config.dart';
        $dir_path = dirname( $file_path );

        if ( ! file_exists( $dir_path ) ) {
            wp_mkdir_p( $dir_path );
        }

        $saved = file_put_contents( $file_path, $dart_config );

        return rest_ensure_response( array(
            'success' => (bool) $saved,
            'file_path' => $file_path,
            'content' => $dart_config,
        ) );
    }

    /**
     * Generar colores Dart
     */
    public function generate_dart_colors( $request ) {
        $config = $this->get_cached_config();
        $theme = $config['theme'] ?? $this->get_default_theme();
        $dark_theme = $config['dark_theme'] ?? $this->get_default_dark_theme();

        $dart_colors = "import 'package:flutter/material.dart';\n\n";
        $dart_colors .= "// Colores generados automáticamente\n";
        $dart_colors .= "// Generado: " . current_time( 'c' ) . "\n\n";

        $dart_colors .= "class AppColors {\n";
        $dart_colors .= "  // Light Theme\n";
        foreach ( $theme as $key => $value ) {
            $color_name = $this->camel_case( $key );
            $hex_value = $this->hex_to_flutter( $value );
            $dart_colors .= "  static const Color $color_name = Color($hex_value);\n";
        }

        $dart_colors .= "\n  // Dark Theme\n";
        foreach ( $dark_theme as $key => $value ) {
            $color_name = $this->camel_case( $key ) . 'Dark';
            $hex_value = $this->hex_to_flutter( $value );
            $dart_colors .= "  static const Color $color_name = Color($hex_value);\n";
        }

        $dart_colors .= "}\n";

        // Guardar archivo
        $file_path = $this->mobile_apps_path . 'lib/core/theme/app_colors.dart';
        $dir_path = dirname( $file_path );

        if ( ! file_exists( $dir_path ) ) {
            wp_mkdir_p( $dir_path );
        }

        $saved = file_put_contents( $file_path, $dart_colors );

        return rest_ensure_response( array(
            'success' => (bool) $saved,
            'file_path' => $file_path,
            'content' => $dart_colors,
        ) );
    }

    /**
     * Exportar configuración completa
     */
    public function export_full_config( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'config' => $config,
            'export_date' => current_time( 'c' ),
            'wordpress_version' => get_bloginfo( 'version' ),
            'plugin_version' => defined( 'FLAVOR_PLATFORM_VERSION' ) ? FLAVOR_PLATFORM_VERSION : '2.1.0',
        ) );
    }

    /**
     * Importar configuración
     */
    public function import_config( $request ) {
        $config = $request->get_param( 'config' );

        if ( ! is_array( $config ) ) {
            return new WP_Error( 'invalid_config', 'Configuración inválida', array( 'status' => 400 ) );
        }

        // Validar estructura básica
        $required_keys = array( 'branding', 'theme' );
        foreach ( $required_keys as $key ) {
            if ( ! isset( $config[ $key ] ) ) {
                return new WP_Error( 'missing_key', "Falta la clave '$key' en la configuración", array( 'status' => 400 ) );
            }
        }

        $sanitized_config = $this->sanitize_config( $config );
        update_option( 'flavor_app_config', $sanitized_config );

        return rest_ensure_response( array(
            'success' => true,
            'config' => $sanitized_config,
        ) );
    }

    /**
     * Obtener layouts disponibles
     */
    public function get_layouts( $request ) {
        $config = $this->get_cached_config();

        return rest_ensure_response( array(
            'current_layout' => $config['layout'] ?? 'default',
            'available' => $this->get_all_layouts(),
        ) );
    }

    /**
     * Configurar layout
     */
    public function set_layout( $request ) {
        $layout = sanitize_key( $request->get_param( 'layout' ) );
        $layouts = $this->get_all_layouts();

        if ( ! isset( $layouts[ $layout ] ) ) {
            return new WP_Error( 'invalid_layout', "Layout '$layout' no existe", array( 'status' => 400 ) );
        }

        $config = $this->get_cached_config();
        $config['layout'] = $layout;
        $this->update_cached_config( $config );

        return rest_ensure_response( array(
            'success' => true,
            'layout' => $layout,
            'details' => $layouts[ $layout ],
        ) );
    }

    // ============ HELPERS ============

    /**
     * Obtiene la configuración de la app con cache en memoria
     *
     * Evita múltiples llamadas a get_option() en una misma request.
     *
     * @param bool $force_refresh Forzar lectura desde BD.
     * @return array
     */
    private function get_cached_config( $force_refresh = false ) {
        if ( null === $this->config_cache || $force_refresh ) {
            $this->config_cache = get_option( 'flavor_app_config', $this->get_default_config() );
        }
        return $this->config_cache;
    }

    /**
     * Actualiza la configuración y refresca el cache
     *
     * @param array $config Nueva configuración.
     * @return bool Resultado de update_option.
     */
    private function update_cached_config( array $config ) {
        $result = update_option( 'flavor_app_config', $config );
        if ( $result ) {
            $this->config_cache = $config;
        }
        return $result;
    }

    /**
     * Configuración por defecto
     */
    private function get_default_config() {
        return array(
            'branding' => $this->get_default_branding(),
            'theme' => $this->get_default_theme(),
            'dark_theme' => $this->get_default_dark_theme(),
            'theme_mode' => 'system',
            'modules' => array( 'eventos', 'foros', 'marketplace' ),
            'permissions' => array(
                'android' => $this->get_default_android_permissions(),
                'ios' => $this->get_default_ios_permissions(),
            ),
            'build' => $this->get_default_build_settings(),
            'layout' => 'default',
        );
    }

    /**
     * Branding por defecto
     */
    private function get_default_branding() {
        return array(
            'app_name' => get_bloginfo( 'name' ) ?: 'Flavor App',
            'app_id' => 'com.flavor.app',
            'app_description' => get_bloginfo( 'description' ) ?: '',
            'logo_url' => '',
            'icon_url' => '',
            'splash_url' => '',
            'developer_name' => '',
            'developer_email' => get_option( 'admin_email' ),
            'developer_website' => home_url(),
            'privacy_policy_url' => '',
            'terms_url' => '',
            'app_store_id' => '',
            'play_store_id' => '',
        );
    }

    /**
     * Tema claro por defecto
     */
    private function get_default_theme() {
        return array(
            'primary' => '#6366f1',
            'primary_variant' => '#4f46e5',
            'secondary' => '#10b981',
            'secondary_variant' => '#059669',
            'background' => '#ffffff',
            'surface' => '#f8fafc',
            'error' => '#ef4444',
            'on_primary' => '#ffffff',
            'on_secondary' => '#ffffff',
            'on_background' => '#1e293b',
            'on_surface' => '#334155',
            'on_error' => '#ffffff',
        );
    }

    /**
     * Tema oscuro por defecto
     */
    private function get_default_dark_theme() {
        return array(
            'primary' => '#818cf8',
            'primary_variant' => '#6366f1',
            'secondary' => '#34d399',
            'secondary_variant' => '#10b981',
            'background' => '#0f172a',
            'surface' => '#1e293b',
            'error' => '#f87171',
            'on_primary' => '#0f172a',
            'on_secondary' => '#0f172a',
            'on_background' => '#f1f5f9',
            'on_surface' => '#e2e8f0',
            'on_error' => '#0f172a',
        );
    }

    /**
     * Configuración de build por defecto
     */
    private function get_default_build_settings() {
        return array(
            'version_name' => '1.0.0',
            'version_code' => '1',
            'android_min_sdk' => '21',
            'android_target_sdk' => '34',
            'android_compile_sdk' => '34',
            'ios_min_version' => '12.0',
            'ios_deployment_target' => '12.0',
            'enable_proguard' => true,
            'enable_bitcode' => false,
            'build_type' => 'release',
            'flavor' => 'production',
        );
    }

    /**
     * Permisos Android por defecto
     */
    private function get_default_android_permissions() {
        return array(
            'INTERNET',
            'ACCESS_NETWORK_STATE',
            'CAMERA',
            'READ_EXTERNAL_STORAGE',
            'WRITE_EXTERNAL_STORAGE',
        );
    }

    /**
     * Permisos iOS por defecto
     */
    private function get_default_ios_permissions() {
        return array(
            'NSCameraUsageDescription',
            'NSPhotoLibraryUsageDescription',
            'NSLocationWhenInUseUsageDescription',
        );
    }

    /**
     * Todos los permisos Android disponibles
     */
    private function get_all_android_permissions() {
        return array(
            'INTERNET' => 'Acceso a Internet',
            'ACCESS_NETWORK_STATE' => 'Estado de la red',
            'CAMERA' => 'Cámara',
            'READ_EXTERNAL_STORAGE' => 'Leer almacenamiento',
            'WRITE_EXTERNAL_STORAGE' => 'Escribir almacenamiento',
            'ACCESS_FINE_LOCATION' => 'Ubicación precisa (GPS)',
            'ACCESS_COARSE_LOCATION' => 'Ubicación aproximada',
            'RECORD_AUDIO' => 'Grabar audio',
            'VIBRATE' => 'Vibración',
            'RECEIVE_BOOT_COMPLETED' => 'Iniciar al arrancar',
            'WAKE_LOCK' => 'Mantener activo',
            'READ_CONTACTS' => 'Leer contactos',
            'WRITE_CONTACTS' => 'Escribir contactos',
            'READ_CALENDAR' => 'Leer calendario',
            'WRITE_CALENDAR' => 'Escribir calendario',
            'SEND_SMS' => 'Enviar SMS',
            'RECEIVE_SMS' => 'Recibir SMS',
            'BLUETOOTH' => 'Bluetooth',
            'BLUETOOTH_ADMIN' => 'Administrar Bluetooth',
            'NFC' => 'NFC',
            'USE_FINGERPRINT' => 'Huella dactilar',
            'USE_BIOMETRIC' => 'Biométricos',
        );
    }

    /**
     * Todos los permisos iOS disponibles
     */
    private function get_all_ios_permissions() {
        return array(
            'NSCameraUsageDescription' => 'Uso de cámara',
            'NSPhotoLibraryUsageDescription' => 'Acceso a fotos',
            'NSPhotoLibraryAddUsageDescription' => 'Guardar fotos',
            'NSLocationWhenInUseUsageDescription' => 'Ubicación en uso',
            'NSLocationAlwaysUsageDescription' => 'Ubicación siempre',
            'NSMicrophoneUsageDescription' => 'Uso de micrófono',
            'NSContactsUsageDescription' => 'Acceso a contactos',
            'NSCalendarsUsageDescription' => 'Acceso a calendario',
            'NSFaceIDUsageDescription' => 'Face ID',
            'NSBluetoothAlwaysUsageDescription' => 'Bluetooth',
            'NSBluetoothPeripheralUsageDescription' => 'Periféricos Bluetooth',
            'NSMotionUsageDescription' => 'Sensor de movimiento',
            'NSSpeechRecognitionUsageDescription' => 'Reconocimiento de voz',
            'NSAppleMusicUsageDescription' => 'Apple Music',
            'NSHealthShareUsageDescription' => 'Datos de salud (leer)',
            'NSHealthUpdateUsageDescription' => 'Datos de salud (escribir)',
        );
    }

    /**
     * Todos los módulos de app (con cache estática)
     */
    private function get_all_app_modules() {
        if ( null !== self::$app_modules ) {
            return self::$app_modules;
        }

        self::$app_modules = array(
            // Comunidad
            array( 'id' => 'eventos', 'name' => 'Eventos', 'category' => 'comunidad', 'icon' => '📅' ),
            array( 'id' => 'foros', 'name' => 'Foros', 'category' => 'comunidad', 'icon' => '💬' ),
            array( 'id' => 'socios', 'name' => 'Socios', 'category' => 'comunidad', 'icon' => '👥' ),
            array( 'id' => 'comunidades', 'name' => 'Comunidades', 'category' => 'comunidad', 'icon' => '🏘️' ),

            // Economía
            array( 'id' => 'marketplace', 'name' => 'Marketplace', 'category' => 'economia', 'icon' => '🛒' ),
            array( 'id' => 'grupos-consumo', 'name' => 'Grupos de Consumo', 'category' => 'economia', 'icon' => '🥕' ),
            array( 'id' => 'banco-tiempo', 'name' => 'Banco de Tiempo', 'category' => 'economia', 'icon' => '⏰' ),

            // Reservas
            array( 'id' => 'reservas', 'name' => 'Reservas', 'category' => 'reservas', 'icon' => '📋' ),
            array( 'id' => 'espacios-comunes', 'name' => 'Espacios Comunes', 'category' => 'reservas', 'icon' => '🏢' ),
            array( 'id' => 'bicicletas', 'name' => 'Bicicletas', 'category' => 'reservas', 'icon' => '🚲' ),
            array( 'id' => 'parkings', 'name' => 'Parkings', 'category' => 'reservas', 'icon' => '🅿️' ),

            // Formación
            array( 'id' => 'cursos', 'name' => 'Cursos', 'category' => 'formacion', 'icon' => '📚' ),
            array( 'id' => 'talleres', 'name' => 'Talleres', 'category' => 'formacion', 'icon' => '🔧' ),
            array( 'id' => 'biblioteca', 'name' => 'Biblioteca', 'category' => 'formacion', 'icon' => '📖' ),

            // Participación
            array( 'id' => 'encuestas', 'name' => 'Encuestas', 'category' => 'participacion', 'icon' => '📊' ),
            array( 'id' => 'presupuestos-participativos', 'name' => 'Presupuestos', 'category' => 'participacion', 'icon' => '💰' ),
            array( 'id' => 'campanias', 'name' => 'Campañas', 'category' => 'participacion', 'icon' => '📢' ),

            // Social
            array( 'id' => 'red-social', 'name' => 'Red Social', 'category' => 'social', 'icon' => '🌐' ),
            array( 'id' => 'chat-interno', 'name' => 'Chat', 'category' => 'social', 'icon' => '💬' ),

            // Movilidad
            array( 'id' => 'carpooling', 'name' => 'Carpooling', 'category' => 'movilidad', 'icon' => '🚗' ),

            // Gestión
            array( 'id' => 'incidencias', 'name' => 'Incidencias', 'category' => 'gestion', 'icon' => '🎫' ),
            array( 'id' => 'tramites', 'name' => 'Trámites', 'category' => 'gestion', 'icon' => '📝' ),
            array( 'id' => 'transparencia', 'name' => 'Transparencia', 'category' => 'gestion', 'icon' => '🔍' ),

            // Cultura
            array( 'id' => 'kulturaka', 'name' => 'Cultura', 'category' => 'cultura', 'icon' => '🎭' ),
            array( 'id' => 'multimedia', 'name' => 'Multimedia', 'category' => 'cultura', 'icon' => '🎬' ),
            array( 'id' => 'radio', 'name' => 'Radio', 'category' => 'cultura', 'icon' => '📻' ),
            array( 'id' => 'podcast', 'name' => 'Podcast', 'category' => 'cultura', 'icon' => '🎙️' ),
        );

        return self::$app_modules;
    }

    /**
     * Todos los presets de temas (con cache estática)
     */
    private function get_all_theme_presets() {
        if ( null !== self::$theme_presets ) {
            return self::$theme_presets;
        }

        self::$theme_presets = array(
            'modern-blue' => array(
                'name' => 'Azul Moderno',
                'light' => array(
                    'primary' => '#3b82f6',
                    'primary_variant' => '#2563eb',
                    'secondary' => '#06b6d4',
                    'secondary_variant' => '#0891b2',
                    'background' => '#ffffff',
                    'surface' => '#f8fafc',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#0f172a',
                    'on_surface' => '#334155',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#60a5fa',
                    'primary_variant' => '#3b82f6',
                    'secondary' => '#22d3ee',
                    'secondary_variant' => '#06b6d4',
                    'background' => '#0f172a',
                    'surface' => '#1e293b',
                    'error' => '#f87171',
                    'on_primary' => '#0f172a',
                    'on_secondary' => '#0f172a',
                    'on_background' => '#f1f5f9',
                    'on_surface' => '#cbd5e1',
                    'on_error' => '#0f172a',
                ),
            ),
            'emerald-green' => array(
                'name' => 'Verde Esmeralda',
                'light' => array(
                    'primary' => '#10b981',
                    'primary_variant' => '#059669',
                    'secondary' => '#14b8a6',
                    'secondary_variant' => '#0d9488',
                    'background' => '#ffffff',
                    'surface' => '#f0fdf4',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#064e3b',
                    'on_surface' => '#166534',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#34d399',
                    'primary_variant' => '#10b981',
                    'secondary' => '#2dd4bf',
                    'secondary_variant' => '#14b8a6',
                    'background' => '#022c22',
                    'surface' => '#064e3b',
                    'error' => '#f87171',
                    'on_primary' => '#022c22',
                    'on_secondary' => '#022c22',
                    'on_background' => '#d1fae5',
                    'on_surface' => '#a7f3d0',
                    'on_error' => '#022c22',
                ),
            ),
            'purple-violet' => array(
                'name' => 'Violeta',
                'light' => array(
                    'primary' => '#8b5cf6',
                    'primary_variant' => '#7c3aed',
                    'secondary' => '#ec4899',
                    'secondary_variant' => '#db2777',
                    'background' => '#ffffff',
                    'surface' => '#faf5ff',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#4c1d95',
                    'on_surface' => '#6b21a8',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#a78bfa',
                    'primary_variant' => '#8b5cf6',
                    'secondary' => '#f472b6',
                    'secondary_variant' => '#ec4899',
                    'background' => '#1e1b4b',
                    'surface' => '#312e81',
                    'error' => '#f87171',
                    'on_primary' => '#1e1b4b',
                    'on_secondary' => '#1e1b4b',
                    'on_background' => '#ede9fe',
                    'on_surface' => '#ddd6fe',
                    'on_error' => '#1e1b4b',
                ),
            ),
            'warm-orange' => array(
                'name' => 'Naranja Cálido',
                'light' => array(
                    'primary' => '#f97316',
                    'primary_variant' => '#ea580c',
                    'secondary' => '#eab308',
                    'secondary_variant' => '#ca8a04',
                    'background' => '#ffffff',
                    'surface' => '#fffbeb',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#422006',
                    'on_background' => '#7c2d12',
                    'on_surface' => '#92400e',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fb923c',
                    'primary_variant' => '#f97316',
                    'secondary' => '#facc15',
                    'secondary_variant' => '#eab308',
                    'background' => '#431407',
                    'surface' => '#7c2d12',
                    'error' => '#f87171',
                    'on_primary' => '#431407',
                    'on_secondary' => '#422006',
                    'on_background' => '#fed7aa',
                    'on_surface' => '#fdba74',
                    'on_error' => '#431407',
                ),
            ),
            'corporate' => array(
                'name' => 'Corporativo',
                'light' => array(
                    'primary' => '#1e40af',
                    'primary_variant' => '#1e3a8a',
                    'secondary' => '#475569',
                    'secondary_variant' => '#334155',
                    'background' => '#ffffff',
                    'surface' => '#f8fafc',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#0f172a',
                    'on_surface' => '#1e293b',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#3b82f6',
                    'primary_variant' => '#1e40af',
                    'secondary' => '#94a3b8',
                    'secondary_variant' => '#64748b',
                    'background' => '#0f172a',
                    'surface' => '#1e293b',
                    'error' => '#f87171',
                    'on_primary' => '#0f172a',
                    'on_secondary' => '#0f172a',
                    'on_background' => '#f1f5f9',
                    'on_surface' => '#e2e8f0',
                    'on_error' => '#0f172a',
                ),
            ),
            'nature' => array(
                'name' => 'Naturaleza',
                'light' => array(
                    'primary' => '#65a30d',
                    'primary_variant' => '#4d7c0f',
                    'secondary' => '#0d9488',
                    'secondary_variant' => '#0f766e',
                    'background' => '#fefce8',
                    'surface' => '#f7fee7',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#365314',
                    'on_surface' => '#3f6212',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#84cc16',
                    'primary_variant' => '#65a30d',
                    'secondary' => '#14b8a6',
                    'secondary_variant' => '#0d9488',
                    'background' => '#1a2e05',
                    'surface' => '#365314',
                    'error' => '#f87171',
                    'on_primary' => '#1a2e05',
                    'on_secondary' => '#1a2e05',
                    'on_background' => '#ecfccb',
                    'on_surface' => '#d9f99d',
                    'on_error' => '#1a2e05',
                ),
            ),
            'minimal' => array(
                'name' => 'Minimalista',
                'light' => array(
                    'primary' => '#18181b',
                    'primary_variant' => '#09090b',
                    'secondary' => '#71717a',
                    'secondary_variant' => '#52525b',
                    'background' => '#ffffff',
                    'surface' => '#fafafa',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#18181b',
                    'on_surface' => '#3f3f46',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fafafa',
                    'primary_variant' => '#e4e4e7',
                    'secondary' => '#a1a1aa',
                    'secondary_variant' => '#71717a',
                    'background' => '#09090b',
                    'surface' => '#18181b',
                    'error' => '#f87171',
                    'on_primary' => '#09090b',
                    'on_secondary' => '#09090b',
                    'on_background' => '#fafafa',
                    'on_surface' => '#d4d4d8',
                    'on_error' => '#09090b',
                ),
            ),
            'rose' => array(
                'name' => 'Rosa',
                'light' => array(
                    'primary' => '#e11d48',
                    'primary_variant' => '#be123c',
                    'secondary' => '#f472b6',
                    'secondary_variant' => '#ec4899',
                    'background' => '#ffffff',
                    'surface' => '#fff1f2',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#4c0519',
                    'on_background' => '#881337',
                    'on_surface' => '#9f1239',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fb7185',
                    'primary_variant' => '#f43f5e',
                    'secondary' => '#f9a8d4',
                    'secondary_variant' => '#f472b6',
                    'background' => '#4c0519',
                    'surface' => '#881337',
                    'error' => '#f87171',
                    'on_primary' => '#4c0519',
                    'on_secondary' => '#4c0519',
                    'on_background' => '#ffe4e6',
                    'on_surface' => '#fecdd3',
                    'on_error' => '#4c0519',
                ),
            ),
        );

        return self::$theme_presets;
    }

    /**
     * Todos los layouts de app (con cache estática)
     */
    private function get_all_layouts() {
        if ( null !== self::$layouts ) {
            return self::$layouts;
        }

        self::$layouts = array(
            'default' => array(
                'name' => 'Por defecto',
                'description' => 'Bottom navigation con 4-5 tabs',
                'navigation' => 'bottom_tabs',
                'max_tabs' => 5,
            ),
            'drawer' => array(
                'name' => 'Drawer',
                'description' => 'Menú lateral deslizable',
                'navigation' => 'drawer',
                'max_items' => 10,
            ),
            'top_tabs' => array(
                'name' => 'Tabs superiores',
                'description' => 'Pestañas en la parte superior',
                'navigation' => 'top_tabs',
                'max_tabs' => 6,
            ),
            'hybrid' => array(
                'name' => 'Híbrido',
                'description' => 'Bottom nav + drawer para más opciones',
                'navigation' => 'hybrid',
                'max_tabs' => 4,
                'max_drawer_items' => 8,
            ),
        );

        return self::$layouts;
    }

    /**
     * Obtener iconos disponibles (con cache en memoria)
     */
    private function get_available_icons() {
        if ( null !== $this->icons_cache ) {
            return $this->icons_cache;
        }

        $icons_path = $this->mobile_apps_path . 'assets/icons/';
        $icons = array();

        if ( file_exists( $icons_path ) && is_dir( $icons_path ) ) {
            $files = glob( $icons_path . '*.png' );
            foreach ( $files as $file ) {
                $icons[] = basename( $file, '.png' );
            }
        }

        $this->icons_cache = $icons;
        return $this->icons_cache;
    }

    /**
     * Detectar versión de Flutter (con cache en memoria y transient)
     */
    private function detect_flutter_version() {
        // Cache en memoria primero
        if ( null !== $this->flutter_version_cache ) {
            return $this->flutter_version_cache;
        }

        // Intentar transient
        $transient_key = 'flavor_flutter_version';
        $cached_version = get_transient( $transient_key );
        if ( false !== $cached_version ) {
            $this->flutter_version_cache = $cached_version;
            return $this->flutter_version_cache;
        }

        // Leer del filesystem
        $pubspec_path = $this->mobile_apps_path . 'pubspec.yaml';
        $version = 'unknown';

        if ( file_exists( $pubspec_path ) ) {
            $content = file_get_contents( $pubspec_path );
            if ( preg_match( '/sdk:\s*["\']?>=?(\d+\.\d+\.\d+)/i', $content, $matches ) ) {
                $version = $matches[1];
            }
        }

        // Guardar en transient (1 hora)
        set_transient( $transient_key, $version, HOUR_IN_SECONDS );
        $this->flutter_version_cache = $version;

        return $this->flutter_version_cache;
    }

    /**
     * Sanitizar tema
     */
    private function sanitize_theme( $theme ) {
        $sanitized = array();
        $allowed_keys = array(
            'primary', 'primary_variant', 'secondary', 'secondary_variant',
            'background', 'surface', 'error',
            'on_primary', 'on_secondary', 'on_background', 'on_surface', 'on_error',
        );

        foreach ( $allowed_keys as $key ) {
            if ( isset( $theme[ $key ] ) ) {
                $sanitized[ $key ] = $this->sanitize_color( $theme[ $key ] );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizar color
     */
    private function sanitize_color( $color ) {
        // Formato hex
        if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
            return strtolower( $color );
        }
        return '#000000';
    }

    /**
     * Sanitizar configuración completa
     */
    private function sanitize_config( $config ) {
        $sanitized = array();

        if ( isset( $config['branding'] ) ) {
            $sanitized['branding'] = array_map( 'sanitize_text_field', $config['branding'] );
        }

        if ( isset( $config['theme'] ) ) {
            $sanitized['theme'] = $this->sanitize_theme( $config['theme'] );
        }

        if ( isset( $config['dark_theme'] ) ) {
            $sanitized['dark_theme'] = $this->sanitize_theme( $config['dark_theme'] );
        }

        if ( isset( $config['theme_mode'] ) ) {
            $valid_modes = array( 'light', 'dark', 'system' );
            $sanitized['theme_mode'] = in_array( $config['theme_mode'], $valid_modes, true )
                ? $config['theme_mode']
                : 'system';
        }

        if ( isset( $config['modules'] ) && is_array( $config['modules'] ) ) {
            $sanitized['modules'] = array_map( 'sanitize_key', $config['modules'] );
        }

        if ( isset( $config['permissions'] ) ) {
            $sanitized['permissions'] = array(
                'android' => isset( $config['permissions']['android'] )
                    ? array_map( 'sanitize_text_field', $config['permissions']['android'] )
                    : array(),
                'ios' => isset( $config['permissions']['ios'] )
                    ? array_map( 'sanitize_text_field', $config['permissions']['ios'] )
                    : array(),
            );
        }

        if ( isset( $config['build'] ) ) {
            $sanitized['build'] = array_map( 'sanitize_text_field', $config['build'] );
        }

        if ( isset( $config['layout'] ) ) {
            $sanitized['layout'] = sanitize_key( $config['layout'] );
        }

        return $sanitized;
    }

    /**
     * Convertir hex a formato Flutter
     */
    private function hex_to_flutter( $hex ) {
        $hex = ltrim( $hex, '#' );

        // Expandir formato corto
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '0xFF' . strtoupper( $hex );
    }

    /**
     * Convertir a camelCase
     */
    private function camel_case( $string ) {
        $string = str_replace( array( '-', '_' ), ' ', $string );
        $string = ucwords( $string );
        $string = str_replace( ' ', '', $string );
        return lcfirst( $string );
    }

    /**
     * Merge recursivo distinto
     */
    private function array_merge_recursive_distinct( array $array1, array $array2 ) {
        $merged = $array1;

        foreach ( $array2 as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = $this->array_merge_recursive_distinct( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }

        return $merged;
    }

    // =========================================================================
    // SINCRONIZACIÓN CON SITIO WEB
    // =========================================================================

    /**
     * Sincronizar configuración de app desde el sitio WordPress
     *
     * Lee la configuración actual del sitio (módulos activos, tema, branding)
     * y actualiza la configuración de la app móvil.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response|WP_Error
     */
    public function sync_app_from_site( $request ) {
        $config = $this->get_cached_config();
        $site_settings = get_option( 'flavor_chat_ia_settings', array() );
        $changes = array();

        // 1. Sincronizar módulos activos del sitio
        $site_modules = $this->get_site_active_modules();
        $app_compatible_modules = $this->filter_app_compatible_modules( $site_modules );

        if ( ! empty( $app_compatible_modules ) ) {
            $config['modules'] = $app_compatible_modules;
            $changes['modules'] = array(
                'synced' => count( $app_compatible_modules ),
                'list'   => $app_compatible_modules,
            );
        }

        // 2. Sincronizar branding desde el sitio
        $site_name = get_bloginfo( 'name' );
        $site_description = get_bloginfo( 'description' );
        $custom_logo_id = get_theme_mod( 'custom_logo' );

        if ( $site_name ) {
            $config['branding']['app_name'] = $site_name;
            $changes['branding']['app_name'] = $site_name;
        }

        if ( $site_description ) {
            $config['branding']['app_description'] = $site_description;
            $changes['branding']['app_description'] = $site_description;
        }

        if ( $custom_logo_id ) {
            $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
            if ( $logo_url ) {
                $config['branding']['logo_url'] = $logo_url;
                $changes['branding']['logo_url'] = $logo_url;
            }
        }

        // 3. Sincronizar colores desde customizer o plugin
        $primary_color = get_theme_mod( 'flavor_primary_color', '' );
        if ( empty( $primary_color ) ) {
            $primary_color = $site_settings['primary_color'] ?? '';
        }

        if ( $primary_color ) {
            $config['theme']['primary'] = $this->sanitize_color( $primary_color );
            $changes['theme']['primary'] = $primary_color;
        }

        // 4. Sincronizar perfil de aplicación
        $app_profile = $site_settings['app_profile'] ?? 'personalizado';
        $config['app_profile'] = $app_profile;
        $changes['app_profile'] = $app_profile;

        // Guardar configuración
        $this->update_cached_config( $config );

        // Actualizar timestamp de sincronización
        update_option( 'flavor_app_last_sync', array(
            'timestamp' => current_time( 'mysql' ),
            'source'    => 'sync_from_site',
            'changes'   => array_keys( $changes ),
        ) );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Configuración sincronizada desde el sitio',
            'changes' => $changes,
            'config'  => $config,
        ) );
    }

    /**
     * Configuración completa de app en una sola llamada
     *
     * Permite configurar todos los aspectos de la app (módulos, tema, branding,
     * navegación) en una sola petición.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response|WP_Error
     */
    public function configure_app_full( $request ) {
        $params = $request->get_json_params();
        $config = $this->get_cached_config();
        $results = array();

        // 1. Sincronizar módulos si se solicita
        if ( $request->get_param( 'sync_modules' ) !== false ) {
            if ( isset( $params['modules'] ) && is_array( $params['modules'] ) ) {
                // Usar módulos proporcionados
                $config['modules'] = array_map( 'sanitize_key', $params['modules'] );
            } else {
                // Auto-sincronizar desde sitio
                $site_modules = $this->get_site_active_modules();
                $config['modules'] = $this->filter_app_compatible_modules( $site_modules );
            }
            $results['modules'] = $config['modules'];
        }

        // 2. Sincronizar tema si se solicita
        if ( $request->get_param( 'sync_theme' ) !== false ) {
            if ( isset( $params['theme'] ) ) {
                $config['theme'] = $this->sanitize_theme( $params['theme'] );
            }
            if ( isset( $params['dark_theme'] ) ) {
                $config['dark_theme'] = $this->sanitize_theme( $params['dark_theme'] );
            }
            if ( isset( $params['theme_mode'] ) ) {
                $valid_modes = array( 'light', 'dark', 'system' );
                $config['theme_mode'] = in_array( $params['theme_mode'], $valid_modes, true )
                    ? $params['theme_mode']
                    : 'system';
            }
            if ( isset( $params['theme_preset'] ) ) {
                $presets = $this->get_all_theme_presets();
                if ( isset( $presets[ $params['theme_preset'] ] ) ) {
                    $preset = $presets[ $params['theme_preset'] ];
                    $config['theme'] = $preset['light'];
                    $config['dark_theme'] = $preset['dark'];
                }
            }
            $results['theme'] = array(
                'mode'       => $config['theme_mode'] ?? 'system',
                'preset'     => $params['theme_preset'] ?? 'custom',
                'primary'    => $config['theme']['primary'] ?? '',
            );
        }

        // 3. Sincronizar branding si se solicita
        if ( $request->get_param( 'sync_branding' ) !== false ) {
            $branding_fields = array(
                'app_name', 'app_id', 'app_description',
                'logo_url', 'icon_url', 'splash_url',
                'developer_name', 'developer_email', 'developer_website',
                'privacy_policy_url', 'terms_url',
            );

            foreach ( $branding_fields as $field ) {
                if ( isset( $params[ $field ] ) ) {
                    $config['branding'][ $field ] = sanitize_text_field( $params[ $field ] );
                }
            }

            // Si no se proporcionan, usar valores del sitio
            if ( ! isset( $params['app_name'] ) ) {
                $config['branding']['app_name'] = get_bloginfo( 'name' );
            }
            if ( ! isset( $params['app_description'] ) ) {
                $config['branding']['app_description'] = get_bloginfo( 'description' );
            }

            $results['branding'] = $config['branding'];
        }

        // 4. Sincronizar navegación si se solicita
        if ( $request->get_param( 'sync_navigation' ) !== false ) {
            $navigation = array();

            // Generar navegación basada en módulos activos
            $active_modules = $config['modules'] ?? array();
            $all_modules = $this->get_all_app_modules();

            $nav_items = array();
            $nav_order = 0;

            foreach ( $all_modules as $module ) {
                if ( in_array( $module['id'], $active_modules, true ) ) {
                    $nav_items[] = array(
                        'id'       => $module['id'],
                        'label'    => $module['name'],
                        'icon'     => $module['icon'],
                        'route'    => '/' . $module['id'],
                        'order'    => $nav_order++,
                        'visible'  => true,
                    );
                }
            }

            // Permitir navegación personalizada
            if ( isset( $params['navigation'] ) && is_array( $params['navigation'] ) ) {
                $config['navigation'] = $params['navigation'];
            } else {
                // Limitar a 5 items en bottom navigation
                $bottom_nav = array_slice( $nav_items, 0, 5 );
                $drawer_nav = array_slice( $nav_items, 5 );

                $config['navigation'] = array(
                    'bottom_nav' => $bottom_nav,
                    'drawer'     => $drawer_nav,
                    'show_home'  => true,
                );
            }

            $results['navigation'] = $config['navigation'];
        }

        // 5. Configurar info page si se proporciona
        if ( isset( $params['info_page'] ) && is_array( $params['info_page'] ) ) {
            $info_page = array();
            $allowed_sections = array( 'about', 'contact', 'social', 'legal', 'directory' );

            foreach ( $allowed_sections as $section ) {
                if ( isset( $params['info_page'][ $section ] ) ) {
                    $info_page[ $section ] = $params['info_page'][ $section ];
                }
            }

            $config['info_page'] = $info_page;
            $results['info_page'] = $info_page;
        }

        // 6. Configurar layout
        if ( isset( $params['layout'] ) ) {
            $config['layout'] = sanitize_key( $params['layout'] );
            $results['layout'] = $config['layout'];
        }

        // Guardar configuración
        $this->update_cached_config( $config );

        // Actualizar timestamp de sincronización
        update_option( 'flavor_app_last_sync', array(
            'timestamp' => current_time( 'mysql' ),
            'source'    => 'configure_full',
            'sections'  => array_keys( $results ),
        ) );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Configuración completa aplicada',
            'results' => $results,
            'config'  => $config,
        ) );
    }

    /**
     * Obtener estado de sincronización app-sitio
     *
     * Compara la configuración de la app con la del sitio y reporta diferencias.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_sync_status( $request ) {
        $config = $this->get_cached_config();
        $last_sync = get_option( 'flavor_app_last_sync', array() );
        $site_settings = get_option( 'flavor_chat_ia_settings', array() );

        // Obtener módulos del sitio
        $site_modules = $this->get_site_active_modules();
        $app_modules = $config['modules'] ?? array();

        // Calcular diferencias
        $modules_only_site = array_diff( $site_modules, $app_modules );
        $modules_only_app = array_diff( $app_modules, $site_modules );
        $modules_synced = array_intersect( $site_modules, $app_modules );

        // Verificar branding
        $site_name = get_bloginfo( 'name' );
        $app_name = $config['branding']['app_name'] ?? '';
        $branding_synced = ( $site_name === $app_name );

        // Calcular estado general
        $is_synced = empty( $modules_only_site ) && empty( $modules_only_app ) && $branding_synced;

        // Calcular próximas acciones recomendadas
        $recommendations = array();

        if ( ! empty( $modules_only_site ) ) {
            $recommendations[] = array(
                'type'    => 'add_modules',
                'message' => 'Hay ' . count( $modules_only_site ) . ' módulos activos en el sitio que no están en la app',
                'modules' => array_values( $modules_only_site ),
            );
        }

        if ( ! empty( $modules_only_app ) ) {
            $recommendations[] = array(
                'type'    => 'remove_modules',
                'message' => 'Hay ' . count( $modules_only_app ) . ' módulos en la app que no están activos en el sitio',
                'modules' => array_values( $modules_only_app ),
            );
        }

        if ( ! $branding_synced ) {
            $recommendations[] = array(
                'type'    => 'update_branding',
                'message' => 'El nombre de la app no coincide con el sitio',
                'site'    => $site_name,
                'app'     => $app_name,
            );
        }

        return rest_ensure_response( array(
            'is_synced'        => $is_synced,
            'last_sync'        => $last_sync,
            'modules'          => array(
                'site'      => $site_modules,
                'app'       => $app_modules,
                'synced'    => array_values( $modules_synced ),
                'only_site' => array_values( $modules_only_site ),
                'only_app'  => array_values( $modules_only_app ),
            ),
            'branding'         => array(
                'synced'    => $branding_synced,
                'site_name' => $site_name,
                'app_name'  => $app_name,
            ),
            'recommendations'  => $recommendations,
            'site_profile'     => $site_settings['app_profile'] ?? 'personalizado',
            'flutter_path'     => $this->mobile_apps_path,
        ) );
    }

    /**
     * Obtener módulos activos del sitio WordPress
     *
     * @return array
     */
    private function get_site_active_modules() {
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $modules = $settings['active_modules'] ?? array();

        // Si no hay módulos en settings, intentar desde perfil
        if ( empty( $modules ) && class_exists( 'Flavor_App_Profiles' ) ) {
            $profiles = Flavor_App_Profiles::get_instance();
            $modules = $profiles->obtener_modulos_requeridos();
        }

        // Normalizar IDs (guiones a guiones_bajos)
        $normalized = array();
        foreach ( $modules as $module ) {
            $normalized[] = str_replace( '-', '_', sanitize_key( $module ) );
        }

        return array_unique( $normalized );
    }

    /**
     * Filtrar módulos compatibles con la app móvil
     *
     * @param array $site_modules Módulos activos en el sitio.
     * @return array Módulos que tienen soporte en la app.
     */
    private function filter_app_compatible_modules( array $site_modules ) {
        $all_app_modules = $this->get_all_app_modules();
        $app_module_ids = array_column( $all_app_modules, 'id' );

        // Normalizar IDs de app modules
        $normalized_app_ids = array();
        foreach ( $app_module_ids as $id ) {
            $normalized_app_ids[] = str_replace( '-', '_', $id );
            $normalized_app_ids[] = str_replace( '_', '-', $id );
        }
        $normalized_app_ids = array_unique( $normalized_app_ids );

        $compatible = array();
        foreach ( $site_modules as $module ) {
            $normalized = str_replace( '_', '-', $module );
            if ( in_array( $module, $normalized_app_ids, true ) ||
                 in_array( $normalized, $normalized_app_ids, true ) ) {
                // Usar formato con guiones para la app
                $compatible[] = $normalized;
            }
        }

        return array_unique( $compatible );
    }
}

/**
 * Función helper para obtener instancia
 */
function flavor_app_config_api() {
    return Flavor_App_Config_API::get_instance();
}
