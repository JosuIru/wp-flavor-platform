<?php
/**
 * Site Builder API - Orquestador de herramientas existentes
 *
 * Integra App Generator, App Profiles, Page Creator y Template Definitions
 * para crear sitios completos desde Claude Code.
 *
 * @package Flavor_Platform
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase principal del Site Builder API
 */
class Flavor_Site_Builder_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Site_Builder_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-site-builder/v1';

    /**
     * Clave de API
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Obtiene instancia singleton
     *
     * @return Flavor_Site_Builder_API
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

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra rutas REST
     */
    public function register_routes() {
        // ========== INFORMACIÓN (usa herramientas existentes) ==========

        // Listar perfiles (desde App Profiles)
        register_rest_route( self::NAMESPACE, '/profiles', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_profiles' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Listar plantillas (desde Template Definitions)
        register_rest_route( self::NAMESPACE, '/templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_templates' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Listar temas de diseño
        register_rest_route( self::NAMESPACE, '/themes', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_themes' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Listar módulos (desde App Profiles)
        register_rest_route( self::NAMESPACE, '/modules', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_modules' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Obtener plantilla de config completa
        register_rest_route( self::NAMESPACE, '/config-template/(?P<template>[a-z_-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_config_template' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // ========== CREACIÓN COMPLETA ==========

        // Crear sitio completo desde plantilla
        register_rest_route( self::NAMESPACE, '/site/create', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_site' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Analizar requisitos con IA (usa App Generator)
        register_rest_route( self::NAMESPACE, '/analyze', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'analyze_requirements' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // ========== COMPONENTES INDIVIDUALES ==========

        // Crear páginas para módulos (usa Page Creator)
        register_rest_route( self::NAMESPACE, '/pages/create-for-modules', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_pages_for_modules' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Crear/actualizar menú
        register_rest_route( self::NAMESPACE, '/menu', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_menu' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Activar módulos
        register_rest_route( self::NAMESPACE, '/modules/activate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'activate_modules' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Establecer perfil de app
        register_rest_route( self::NAMESPACE, '/profile/set', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_profile' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Aplicar tema/diseño
        register_rest_route( self::NAMESPACE, '/theme/apply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_theme' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Opciones de diseño disponibles
        register_rest_route( self::NAMESPACE, '/design/options', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_design_options' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Importar datos demo
        register_rest_route( self::NAMESPACE, '/demo-data/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_demo_data' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Estado del sitio
        register_rest_route( self::NAMESPACE, '/site/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_site_status' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Animaciones y efectos disponibles
        register_rest_route( self::NAMESPACE, '/animations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_animations' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Herramientas disponibles
        register_rest_route( self::NAMESPACE, '/tools', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_tools' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // ========== VALIDACIÓN Y EXPORTACIÓN ==========

        // Validar configuración antes de aplicar
        register_rest_route( self::NAMESPACE, '/site/validate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'validate_config' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Exportar configuración del sitio
        register_rest_route( self::NAMESPACE, '/site/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_config' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Importar configuración
        register_rest_route( self::NAMESPACE, '/site/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_config' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Health check del sistema
        register_rest_route( self::NAMESPACE, '/system/health', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'health_check' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    /**
     * Verifica permisos
     *
     * @param WP_REST_Request $request Petición.
     * @return bool
     */
    public function check_permission( $request ) {
        // Verificar header X-VBP-Key usando helper centralizado
        $auth_header = $request->get_header( 'X-VBP-Key' );
        if ( flavor_check_vbp_automation_access( $auth_header, 'site_builder' ) ) {
            return true;
        }

        return current_user_can( 'manage_options' );
    }

    // =========================================================================
    // PERFILES (desde Flavor_App_Profiles)
    // =========================================================================

    /**
     * Lista perfiles disponibles
     *
     * @return WP_REST_Response
     */
    public function list_profiles() {
        $profiles = array();

        // Intentar usar Flavor_App_Profiles si existe
        if ( class_exists( 'Flavor_App_Profiles' ) ) {
            $app_profiles = Flavor_App_Profiles::get_instance();
            $all_profiles = $app_profiles->obtener_perfiles();

            foreach ( $all_profiles as $id => $profile ) {
                $profiles[ $id ] = array(
                    'id'          => $id,
                    'name'        => $profile['nombre'] ?? $id,
                    'description' => $profile['descripcion'] ?? '',
                    'icon'        => $profile['icono'] ?? '📦',
                    'modules'     => array(
                        'required' => $profile['modulos_requeridos'] ?? array(),
                        'optional' => $profile['modulos_opcionales'] ?? array(),
                    ),
                    'color'       => $profile['color'] ?? '#6366f1',
                );
            }
        } else {
            // Fallback a perfiles básicos
            $profiles = $this->get_fallback_profiles();
        }

        return new WP_REST_Response( $profiles, 200 );
    }

    /**
     * Perfiles fallback
     *
     * @return array
     */
    private function get_fallback_profiles() {
        return array(
            'comunidad' => array(
                'id'          => 'comunidad',
                'name'        => 'Comunidad / Asociación',
                'description' => 'Para colectivos, asociaciones y movimientos',
                'icon'        => '✊',
                'modules'     => array(
                    'required' => array( 'socios', 'eventos' ),
                    'optional' => array( 'foros', 'chat-grupos', 'transparencia' ),
                ),
            ),
            'grupos_consumo' => array(
                'id'          => 'grupos_consumo',
                'name'        => 'Grupo de Consumo',
                'description' => 'Para cooperativas de consumo',
                'icon'        => '🥬',
                'modules'     => array(
                    'required' => array( 'grupos-consumo', 'socios' ),
                    'optional' => array( 'eventos', 'marketplace' ),
                ),
            ),
            'tienda' => array(
                'id'          => 'tienda',
                'name'        => 'E-commerce',
                'description' => 'Para tiendas online',
                'icon'        => '🛒',
                'modules'     => array(
                    'required' => array( 'marketplace' ),
                    'optional' => array( 'facturas', 'clientes' ),
                ),
            ),
        );
    }

    // =========================================================================
    // PLANTILLAS (desde Template_Definitions)
    // =========================================================================

    /**
     * Lista plantillas disponibles
     *
     * @return WP_REST_Response
     */
    public function list_templates() {
        $templates = array();

        // Intentar usar Flavor_Template_Definitions
        if ( class_exists( 'Flavor_Template_Definitions' ) ) {
            $template_defs = Flavor_Template_Definitions::get_instance();
            $all_templates = $template_defs->obtener_todas();

            foreach ( $all_templates as $id => $template ) {
                $templates[ $id ] = array(
                    'id'          => $id,
                    'name'        => $template['nombre'] ?? $id,
                    'description' => $template['descripcion'] ?? '',
                    'icon'        => $template['icono'] ?? '📄',
                    'color'       => $template['color'] ?? '#6366f1',
                    'modules'     => $template['modulos'] ?? array(),
                    'pages'       => array_keys( $template['paginas'] ?? array() ),
                    'has_landing' => ! empty( $template['landing']['activa'] ),
                    'has_demo'    => ! empty( $template['demo']['disponible'] ),
                );
            }
        }

        return new WP_REST_Response( $templates, 200 );
    }

    /**
     * Obtiene configuración completa de una plantilla
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_config_template( $request ) {
        $template_id = $request->get_param( 'template' );

        if ( class_exists( 'Flavor_Template_Definitions' ) ) {
            $template_defs = Flavor_Template_Definitions::get_instance();

            if ( $template_defs->existe( $template_id ) ) {
                $template = $template_defs->obtener_definicion( $template_id );

                // Formatear para fácil uso desde Claude
                $config = array(
                    'template_id' => $template_id,
                    'name'        => $template['nombre'],
                    'description' => $template['descripcion'],

                    // Módulos a activar
                    'modules' => array(
                        'required'  => $template['modulos']['requeridos'] ?? array(),
                        'optional'  => $template['modulos']['opcionales'] ?? array(),
                        'suggested' => $template['modulos']['sugeridos'] ?? array(),
                    ),

                    // Páginas a crear
                    'pages' => $template['paginas'] ?? array(),

                    // Menú
                    'menu' => $template['menu'] ?? array(),

                    // Landing page
                    'landing' => $template['landing'] ?? array(),

                    // Configuración específica de módulos
                    'module_config' => $template['configuracion'] ?? array(),

                    // Demo data
                    'demo' => $template['demo'] ?? array(),

                    // Comando sugerido para crear
                    '_create_command' => array(
                        'endpoint' => '/site/create',
                        'method'   => 'POST',
                        'body'     => array(
                            'template'     => $template_id,
                            'site_name'    => 'Mi Sitio',
                            'import_demo'  => true,
                            'create_menu'  => true,
                            'create_pages' => true,
                        ),
                    ),
                );

                return new WP_REST_Response( $config, 200 );
            }
        }

        return new WP_REST_Response( array( 'error' => 'Plantilla no encontrada' ), 404 );
    }

    // =========================================================================
    // TEMAS DE DISEÑO
    // =========================================================================

    /**
     * Lista temas disponibles
     *
     * @return WP_REST_Response
     */
    public function list_themes() {
        $themes = array(
            // Oscuros
            'dark-activism' => array(
                'name'   => 'Activismo Oscuro',
                'style'  => 'dark',
                'colors' => array(
                    'primary'    => '#b91c1c',
                    'secondary'  => '#f59e0b',
                    'background' => '#0d0d0d',
                    'surface'    => '#1a1a1a',
                    'text'       => '#ffffff',
                ),
            ),
            'dark-purple' => array(
                'name'   => 'Púrpura Nocturno',
                'style'  => 'dark',
                'colors' => array(
                    'primary'    => '#8b5cf6',
                    'secondary'  => '#ec4899',
                    'background' => '#0f0f23',
                    'surface'    => '#1a1a2e',
                    'text'       => '#ffffff',
                ),
            ),
            'dark-media' => array(
                'name'   => 'Media Bold',
                'style'  => 'dark',
                'colors' => array(
                    'primary'    => '#dc2626',
                    'secondary'  => '#fbbf24',
                    'background' => '#18181b',
                    'surface'    => '#27272a',
                    'text'       => '#fafafa',
                ),
            ),

            // Claros
            'light-eco' => array(
                'name'   => 'Ecológico',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#059669',
                    'secondary'  => '#84cc16',
                    'background' => '#f0fdf4',
                    'surface'    => '#ffffff',
                    'text'       => '#1f2937',
                ),
            ),
            'light-modern' => array(
                'name'   => 'Moderno',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#6366f1',
                    'secondary'  => '#ec4899',
                    'background' => '#ffffff',
                    'surface'    => '#f9fafb',
                    'text'       => '#111827',
                ),
            ),
            'light-institutional' => array(
                'name'   => 'Institucional',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#1d4ed8',
                    'secondary'  => '#0891b2',
                    'background' => '#ffffff',
                    'surface'    => '#f8fafc',
                    'text'       => '#1e293b',
                ),
            ),
            'light-health' => array(
                'name'   => 'Salud',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#0d9488',
                    'secondary'  => '#06b6d4',
                    'background' => '#f0fdfa',
                    'surface'    => '#ffffff',
                    'text'       => '#134e4a',
                ),
            ),
            'light-gastro' => array(
                'name'   => 'Gastronómico',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#92400e',
                    'secondary'  => '#d97706',
                    'background' => '#fffbeb',
                    'surface'    => '#ffffff',
                    'text'       => '#292524',
                ),
            ),
            'light-minimal' => array(
                'name'   => 'Minimalista',
                'style'  => 'light',
                'colors' => array(
                    'primary'    => '#18181b',
                    'secondary'  => '#f97316',
                    'background' => '#fafafa',
                    'surface'    => '#ffffff',
                    'text'       => '#18181b',
                ),
            ),
        );

        return new WP_REST_Response( $themes, 200 );
    }

    // =========================================================================
    // MÓDULOS
    // =========================================================================

    /**
     * Lista módulos disponibles
     *
     * @return WP_REST_Response
     */
    public function list_modules() {
        $modules = array();

        if ( class_exists( 'Flavor_App_Profiles' ) ) {
            $app_profiles = Flavor_App_Profiles::get_instance();
            $categorias = $app_profiles->obtener_categorias_modulos();

            foreach ( $categorias as $cat_id => $categoria ) {
                foreach ( $categoria['modulos'] ?? array() as $mod_id => $modulo ) {
                    $modules[ $mod_id ] = array(
                        'id'          => $mod_id,
                        'name'        => $modulo['nombre'] ?? $mod_id,
                        'description' => $modulo['descripcion'] ?? '',
                        'category'    => $cat_id,
                        'icon'        => $modulo['icono'] ?? '📦',
                    );
                }
            }
        }

        // Añadir módulos activos
        $active_modules = get_option( 'flavor_chat_modules', array() );
        foreach ( $modules as $id => &$module ) {
            $module['active'] = in_array( $id, $active_modules, true );
        }

        return new WP_REST_Response( $modules, 200 );
    }

    // =========================================================================
    // CREACIÓN DE SITIO COMPLETO
    // =========================================================================

    /**
     * Crea sitio completo desde plantilla
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function create_site( $request ) {
        $params = $request->get_json_params();
        $template_id = $params['template'] ?? '';
        $site_name = $params['site_name'] ?? '';
        $import_demo = $params['import_demo'] ?? false;
        $create_menu = $params['create_menu'] ?? true;
        $create_pages = $params['create_pages'] ?? true;
        $theme_id = $params['theme'] ?? '';
        $custom_config = $params['config'] ?? array();

        $results = array(
            'success' => true,
            'steps'   => array(),
            'errors'  => array(),
        );

        // 1. Obtener definición de plantilla
        $template = null;
        if ( $template_id && class_exists( 'Flavor_Template_Definitions' ) ) {
            $template_defs = Flavor_Template_Definitions::get_instance();
            if ( $template_defs->existe( $template_id ) ) {
                $template = $template_defs->obtener_definicion( $template_id );
                $results['steps']['template'] = array(
                    'loaded'  => true,
                    'name'    => $template['nombre'],
                );
            }
        }

        // 2. Configurar nombre del sitio
        if ( $site_name ) {
            update_option( 'blogname', $site_name );
            $results['steps']['site_name'] = $site_name;
        }

        // 3. Activar módulos
        if ( $template ) {
            $modules_to_activate = array_merge(
                $template['modulos']['requeridos'] ?? array(),
                $custom_config['modules'] ?? array()
            );

            if ( ! empty( $modules_to_activate ) ) {
                $modules_result = $this->activate_modules_internal( $modules_to_activate );
                $results['steps']['modules'] = $modules_result;
            }
        }

        // 4. Aplicar tema
        if ( $theme_id ) {
            $theme_result = $this->apply_theme_internal( $theme_id, $custom_config['colors'] ?? array() );
            $results['steps']['theme'] = $theme_result;
        } elseif ( isset( $template['color'] ) ) {
            // Usar color de la plantilla
            $this->apply_theme_internal( 'custom', array( 'primary' => $template['color'] ) );
        }

        // 5. Establecer perfil de app
        if ( $template_id && class_exists( 'Flavor_App_Profiles' ) ) {
            $app_profiles = Flavor_App_Profiles::get_instance();
            $app_profiles->establecer_perfil( $template_id );
            $results['steps']['profile'] = $template_id;
        }

        // 6. Crear páginas
        if ( $create_pages && $template ) {
            $pages_result = $this->create_pages_internal( $template, $custom_config );
            $results['steps']['pages'] = $pages_result;
        }

        // 7. Crear menú
        if ( $create_menu && $template && isset( $template['menu'] ) ) {
            $menu_result = $this->create_menu_internal( $template['menu'], $results['steps']['pages'] ?? array() );
            $results['steps']['menu'] = $menu_result;
        }

        // 8. Crear landing page
        if ( $template && ! empty( $template['landing']['activa'] ) ) {
            $landing_result = $this->create_landing_internal( $template, $theme_id, $site_name );
            $results['steps']['landing'] = $landing_result;
        }

        // 9. Importar datos demo
        if ( $import_demo && $template && ! empty( $template['demo']['disponible'] ) ) {
            $demo_result = $this->import_demo_internal( $template_id );
            $results['steps']['demo'] = $demo_result;
        }

        // 10. Configuración de ajustes
        if ( isset( $results['steps']['pages']['home_id'] ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $results['steps']['pages']['home_id'] );
        }

        return new WP_REST_Response( $results, 201 );
    }

    /**
     * Activa módulos internamente
     *
     * @param array $modules Módulos a activar.
     * @return array
     */
    private function activate_modules_internal( $modules ) {
        $current = get_option( 'flavor_chat_modules', array() );
        $activated = array();

        foreach ( $modules as $module ) {
            if ( ! in_array( $module, $current, true ) ) {
                $current[] = $module;
                $activated[] = $module;
            }
        }

        update_option( 'flavor_chat_modules', $current );

        return array(
            'activated' => $activated,
            'total'     => count( $current ),
        );
    }

    /**
     * Aplica tema internamente con soporte completo de personalización
     *
     * @param string $theme_id Tema base (o 'custom' para personalización completa).
     * @param array  $custom   Configuración personalizada (colores, tipografía, espaciados, etc.).
     * @return array
     */
    private function apply_theme_internal( $theme_id, $custom = array() ) {
        $themes = $this->list_themes()->get_data();
        $theme = $themes[ $theme_id ] ?? array();

        // Obtener configuración actual para preservar valores no especificados
        $current_settings = get_option( 'flavor_design_settings', array() );

        // Valores por defecto completos
        $defaults = $this->get_design_defaults();

        // Mapear colores del tema al formato de settings
        $theme_settings = array();
        if ( ! empty( $theme['colors'] ) ) {
            $color_map = array(
                'primary'    => 'primary_color',
                'secondary'  => 'secondary_color',
                'accent'     => 'accent_color',
                'background' => 'background_color',
                'surface'    => 'card_background_color',
                'text'       => 'text_color',
                'success'    => 'success_color',
                'warning'    => 'warning_color',
                'error'      => 'error_color',
            );
            foreach ( $theme['colors'] as $key => $value ) {
                $setting_key = $color_map[ $key ] ?? $key . '_color';
                $theme_settings[ $setting_key ] = $value;
            }

            // Ajustar header/footer según estilo dark/light
            if ( ( $theme['style'] ?? 'light' ) === 'dark' ) {
                $theme_settings['header_bg_color'] = $theme['colors']['background'] ?? '#0d0d0d';
                $theme_settings['header_text_color'] = $theme['colors']['text'] ?? '#ffffff';
                $theme_settings['footer_bg_color'] = $theme['colors']['surface'] ?? '#1a1a1a';
                $theme_settings['footer_text_color'] = $theme['colors']['text'] ?? '#ffffff';
            }
        }

        // Mergear: defaults -> current -> theme -> custom
        $settings = array_merge( $defaults, $current_settings, $theme_settings, $custom );

        // Guardar
        update_option( 'flavor_design_settings', $settings );

        return array(
            'theme'    => $theme_id,
            'applied'  => array_keys( array_merge( $theme_settings, $custom ) ),
            'settings' => $settings,
        );
    }

    /**
     * Obtiene valores por defecto del sistema de diseño
     *
     * @return array
     */
    private function get_design_defaults() {
        return array(
            // Colores
            'primary_color'      => '#3b82f6',
            'secondary_color'    => '#8b5cf6',
            'accent_color'       => '#f59e0b',
            'success_color'      => '#10b981',
            'warning_color'      => '#f59e0b',
            'error_color'        => '#ef4444',
            'background_color'   => '#ffffff',
            'text_color'         => '#1f2937',
            'text_muted_color'   => '#6b7280',

            // Header/Footer
            'header_bg_color'    => '#ffffff',
            'header_text_color'  => '#1f2937',
            'footer_bg_color'    => '#1f2937',
            'footer_text_color'  => '#ffffff',

            // Tipografía
            'font_family_headings' => 'Inter',
            'font_family_body'     => 'Inter',
            'font_size_base'       => 16,
            'font_size_h1'         => 48,
            'font_size_h2'         => 36,
            'font_size_h3'         => 28,
            'line_height_base'     => 1.5,
            'line_height_headings' => 1.2,

            // Espaciados
            'container_max_width'  => 1280,
            'section_padding_y'    => 80,
            'section_padding_x'    => 20,
            'grid_gap'             => 24,
            'card_padding'         => 24,

            // Botones
            'button_border_radius' => 8,
            'button_padding_y'     => 12,
            'button_padding_x'     => 24,
            'button_font_size'     => 16,
            'button_font_weight'   => 600,

            // Componentes
            'card_border_radius'   => 12,
            'card_shadow'          => 'medium',
            'hero_overlay_opacity' => 0.6,
            'image_border_radius'  => 8,

            // Portal/Dashboard
            'portal_layout'        => 'ecosystem',
            'module_card_style'    => 'elevated',
            'module_icon_style'    => 'filled',
            'module_border_style'  => 'left',
            'dashboard_density'    => 'normal',
            'widget_animations'    => 'all',
        );
    }

    /**
     * Crea páginas internamente
     *
     * @param array $template Plantilla.
     * @param array $config   Configuración adicional.
     * @return array
     */
    private function create_pages_internal( $template, $config = array() ) {
        $created = array();
        $home_id = 0;

        // Usar Page Creator si existe (métodos estáticos)
        if ( class_exists( 'Flavor_Page_Creator' ) ) {
            $modules = $template['modulos']['requeridos'] ?? array();

            if ( ! empty( $modules ) ) {
                $result = Flavor_Page_Creator::create_pages_for_modules( $modules );
                $created = $result['created'] ?? array();
            }
        }

        // Crear páginas adicionales de la plantilla
        foreach ( $template['paginas'] ?? array() as $slug => $page_data ) {
            // Verificar si ya existe
            $existing = get_page_by_path( $slug, OBJECT, array( 'page', 'flavor_landing' ) );
            if ( $existing ) {
                $created[ $slug ] = $existing->ID;
                if ( $slug === 'inicio' || $slug === 'home' ) {
                    $home_id = $existing->ID;
                }
                continue;
            }

            // Crear página
            $post_id = wp_insert_post( array(
                'post_title'  => $page_data['titulo'] ?? ucfirst( $slug ),
                'post_name'   => $slug,
                'post_type'   => $page_data['tipo'] ?? 'page',
                'post_status' => 'publish',
                'post_content'=> $page_data['contenido'] ?? '',
            ) );

            if ( ! is_wp_error( $post_id ) ) {
                $created[ $slug ] = $post_id;
                if ( $slug === 'inicio' || $slug === 'home' ) {
                    $home_id = $post_id;
                }
            }
        }

        return array(
            'created' => $created,
            'count'   => count( $created ),
            'home_id' => $home_id,
        );
    }

    /**
     * Crea menú internamente
     *
     * @param array $menu_config Configuración del menú.
     * @param array $pages       Páginas creadas.
     * @return array
     */
    private function create_menu_internal( $menu_config, $pages = array() ) {
        $menu_name = $menu_config['nombre'] ?? 'Menú Principal';

        // Crear o obtener menú
        $menu_id = wp_create_nav_menu( $menu_name );
        if ( is_wp_error( $menu_id ) ) {
            $menu_obj = wp_get_nav_menu_object( $menu_name );
            $menu_id = $menu_obj ? $menu_obj->term_id : 0;
        }

        if ( ! $menu_id ) {
            return array( 'error' => 'No se pudo crear el menú' );
        }

        // Limpiar items existentes
        $menu_items = wp_get_nav_menu_items( $menu_id );
        if ( $menu_items ) {
            foreach ( $menu_items as $item ) {
                wp_delete_post( $item->ID, true );
            }
        }

        // Añadir items
        $items_added = array();
        $position = 0;
        $created_pages = $pages['created'] ?? array();

        foreach ( $menu_config['items'] ?? array() as $item ) {
            $position++;
            $slug = $item['pagina'] ?? '';

            $item_data = array(
                'menu-item-title'    => $item['titulo'] ?? $item['label'] ?? '',
                'menu-item-url'      => $item['url'] ?? ( $slug ? "/{$slug}" : '#' ),
                'menu-item-status'   => 'publish',
                'menu-item-position' => $position,
            );

            // Si hay página creada, enlazarla
            if ( $slug && isset( $created_pages[ $slug ] ) ) {
                $item_data['menu-item-object-id'] = $created_pages[ $slug ];
                $item_data['menu-item-object'] = 'page';
                $item_data['menu-item-type'] = 'post_type';
            } else {
                $item_data['menu-item-type'] = 'custom';
            }

            wp_update_nav_menu_item( $menu_id, 0, $item_data );
            $items_added[] = $item_data['menu-item-title'];
        }

        // Asignar a ubicación
        $locations = get_theme_mod( 'nav_menu_locations', array() );
        $locations['primary'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );

        return array(
            'menu_id' => $menu_id,
            'items'   => $items_added,
        );
    }

    /**
     * Crea landing page internamente
     *
     * @param array  $template  Plantilla.
     * @param string $theme_id  Tema.
     * @param string $site_name Nombre del sitio.
     * @return array
     */
    private function create_landing_internal( $template, $theme_id, $site_name ) {
        $landing = $template['landing'];
        $themes = $this->list_themes()->get_data();
        $theme = $themes[ $theme_id ] ?? $themes['dark-activism'];
        $colors = $theme['colors'] ?? array();

        // Crear elementos VBP
        $elements = array();

        foreach ( $landing['secciones'] ?? array() as $section ) {
            $section_type = $section['tipo'] ?? 'hero';
            $element = $this->create_section_element( $section_type, $section, $colors, $site_name );
            if ( $element ) {
                $elements[] = $element;
            }
        }

        // Preparar elementos
        $prepared = $this->prepare_elements_for_vbp( $elements );

        // Crear página
        $post_id = wp_insert_post( array(
            'post_title'  => $site_name ?: $template['nombre'],
            'post_name'   => 'inicio',
            'post_type'   => 'flavor_landing',
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            return array( 'error' => $post_id->get_error_message() );
        }

        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $prepared,
            'settings' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => $colors['background'] ?? '#ffffff',
            ),
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return array(
            'id'       => $post_id,
            'url'      => get_permalink( $post_id ),
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'sections' => count( $prepared ),
        );
    }

    /**
     * Crea elemento de sección
     *
     * @param string $type      Tipo.
     * @param array  $config    Configuración.
     * @param array  $colors    Colores.
     * @param string $site_name Nombre del sitio.
     * @return array|null
     */
    private function create_section_element( $type, $config, $colors, $site_name = '' ) {
        $bg_dark = $colors['background'] ?? '#0d0d0d';
        $bg_surface = $colors['surface'] ?? '#1a1a1a';
        $primary = $colors['primary'] ?? '#b91c1c';
        $text = $colors['text'] ?? '#ffffff';

        $base = array(
            'type'    => $type,
            'name'    => $config['nombre'] ?? ucfirst( $type ),
            'data'    => $config['datos'] ?? $config['data'] ?? array(),
            'styles'  => array(
                'colors' => array(
                    'background' => $config['fondo'] ?? $bg_surface,
                    'text'       => $text,
                ),
            ),
        );

        // Personalizar datos según tipo
        switch ( $type ) {
            case 'hero':
                $base['data'] = array_merge( array(
                    'titulo'      => $site_name ?: 'Bienvenido',
                    'subtitulo'   => '',
                    'boton_texto' => 'Saber más',
                    'boton_url'   => '#contenido',
                ), $base['data'] );
                $base['styles']['colors']['background'] = $bg_dark;
                break;

            case 'cta':
                $base['styles']['colors']['background'] = $primary;
                break;

            case 'stats':
                $base['styles']['colors']['background'] = $primary;
                break;
        }

        return $base;
    }

    /**
     * Prepara elementos para VBP
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function prepare_elements_for_vbp( $elements ) {
        $prepared = array();

        foreach ( $elements as $element ) {
            $element['id'] = 'el_' . bin2hex( random_bytes( 6 ) );
            $element['visible'] = true;
            $element['locked'] = false;
            $element['children'] = array();

            // Estructura de estilos completa
            $default_styles = array(
                'spacing'    => array(
                    'margin'  => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
                    'padding' => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
                ),
                'colors'     => array( 'background' => '', 'text' => '' ),
                'typography' => array(),
                'borders'    => array(),
                'shadows'    => array(),
                'layout'     => array(),
                'advanced'   => array( 'cssId' => '', 'cssClasses' => '', 'customCss' => '' ),
            );

            $element['styles'] = array_replace_recursive( $default_styles, $element['styles'] ?? array() );
            $prepared[] = $element;
        }

        return $prepared;
    }

    /**
     * Importa datos demo internamente
     *
     * @param string $template_id ID de plantilla.
     * @return array
     */
    private function import_demo_internal( $template_id ) {
        // Usar Demo Data Generator si existe
        if ( class_exists( 'Flavor_Demo_Data_Generator' ) ) {
            $generator = new Flavor_Demo_Data_Generator();
            $result = $generator->generate_for_template( $template_id );
            return array(
                'imported' => true,
                'items'    => $result['count'] ?? 0,
            );
        }

        return array( 'imported' => false, 'reason' => 'Generator not available' );
    }

    // =========================================================================
    // ENDPOINTS INDIVIDUALES
    // =========================================================================

    /**
     * Analiza requisitos con IA
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function analyze_requirements( $request ) {
        $description = $request->get_param( 'description' );

        if ( class_exists( 'Flavor_App_Generator' ) ) {
            $generator = Flavor_App_Generator::get_instance();
            $result = $generator->analizar_requisitos( $description );

            return new WP_REST_Response( array(
                'success'  => true,
                'proposal' => $result,
            ), 200 );
        }

        return new WP_REST_Response( array(
            'error' => 'App Generator no disponible',
        ), 500 );
    }

    /**
     * Crea páginas para módulos
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function create_pages_for_modules( $request ) {
        $modules = $request->get_param( 'modules' );

        if ( class_exists( 'Flavor_Page_Creator' ) ) {
            // Usar método estático directamente
            $result = Flavor_Page_Creator::create_pages_for_modules( $modules );

            return new WP_REST_Response( $result, 201 );
        }

        return new WP_REST_Response( array( 'error' => 'Page Creator no disponible' ), 500 );
    }

    /**
     * Crea menú
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function create_menu( $request ) {
        $config = $request->get_json_params();
        $result = $this->create_menu_internal( $config );
        return new WP_REST_Response( $result, 201 );
    }

    /**
     * Activa módulos
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function activate_modules( $request ) {
        $modules = $request->get_param( 'modules' );
        $result = $this->activate_modules_internal( $modules );
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Establece perfil
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function set_profile( $request ) {
        $profile_id = $request->get_param( 'profile' );

        if ( class_exists( 'Flavor_App_Profiles' ) ) {
            $profiles = Flavor_App_Profiles::get_instance();
            $profiles->establecer_perfil( $profile_id );

            return new WP_REST_Response( array(
                'success' => true,
                'profile' => $profile_id,
            ), 200 );
        }

        return new WP_REST_Response( array( 'error' => 'App Profiles no disponible' ), 500 );
    }

    /**
     * Aplica tema con personalización completa
     *
     * Acepta un tema base y/o personalización de cualquier opción de diseño:
     * - Colores: primary_color, secondary_color, accent_color, success_color, etc.
     * - Tipografía: font_family_headings, font_family_body, font_size_base, font_size_h1, etc.
     * - Espaciados: container_max_width, section_padding_y, grid_gap, card_padding, etc.
     * - Botones: button_border_radius, button_padding_y, button_font_size, etc.
     * - Componentes: card_border_radius, card_shadow, hero_overlay_opacity, etc.
     * - Header/Footer: header_bg_color, header_text_color, footer_bg_color, etc.
     * - Portal: portal_layout, module_card_style, module_icon_style, dashboard_density, etc.
     * - Colores módulos: module_color_eventos, module_color_socios, etc.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function apply_theme( $request ) {
        $params = $request->get_json_params();
        $theme_id = $params['theme'] ?? $request->get_param( 'theme' ) ?? 'custom';

        // Extraer todas las opciones de personalización
        $custom = array();
        $design_keys = array_keys( $this->get_design_defaults() );

        // Soportar tanto 'colors' legacy como opciones directas
        if ( isset( $params['colors'] ) && is_array( $params['colors'] ) ) {
            $custom = array_merge( $custom, $params['colors'] );
        }

        // Aceptar cualquier opción de diseño válida
        foreach ( $params as $key => $value ) {
            if ( $key === 'theme' || $key === 'colors' ) {
                continue;
            }
            // Aceptar opciones conocidas o que empiecen con module_color_
            if ( in_array( $key, $design_keys, true ) || strpos( $key, 'module_color_' ) === 0 ) {
                $custom[ $key ] = $value;
            }
        }

        $result = $this->apply_theme_internal( $theme_id, $custom );
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Obtiene las opciones de diseño disponibles
     *
     * @return WP_REST_Response
     */
    public function get_design_options() {
        $defaults = $this->get_design_defaults();
        $current = get_option( 'flavor_design_settings', array() );

        return new WP_REST_Response( array(
            'defaults' => $defaults,
            'current'  => $current,
            'categories' => array(
                'colors' => array(
                    'primary_color', 'secondary_color', 'accent_color',
                    'success_color', 'warning_color', 'error_color',
                    'background_color', 'text_color', 'text_muted_color',
                ),
                'header_footer' => array(
                    'header_bg_color', 'header_text_color',
                    'footer_bg_color', 'footer_text_color',
                ),
                'typography' => array(
                    'font_family_headings', 'font_family_body',
                    'font_size_base', 'font_size_h1', 'font_size_h2', 'font_size_h3',
                    'line_height_base', 'line_height_headings',
                ),
                'spacing' => array(
                    'container_max_width', 'section_padding_y', 'section_padding_x',
                    'grid_gap', 'card_padding',
                ),
                'buttons' => array(
                    'button_border_radius', 'button_padding_y', 'button_padding_x',
                    'button_font_size', 'button_font_weight',
                ),
                'components' => array(
                    'card_border_radius', 'card_shadow',
                    'hero_overlay_opacity', 'image_border_radius',
                ),
                'portal' => array(
                    'portal_layout', 'module_card_style', 'module_icon_style',
                    'module_border_style', 'dashboard_density', 'widget_animations',
                ),
            ),
        ), 200 );
    }

    /**
     * Importa datos demo
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function import_demo_data( $request ) {
        $template_id = $request->get_param( 'template' );
        $result = $this->import_demo_internal( $template_id );
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Estado del sitio
     *
     * @return WP_REST_Response
     */
    public function get_site_status() {
        $modules = get_option( 'flavor_chat_modules', array() );
        $design = get_option( 'flavor_design_settings', array() );

        // Contar páginas
        $landing_count = wp_count_posts( 'flavor_landing' );
        $page_count = wp_count_posts( 'page' );

        return new WP_REST_Response( array(
            'site' => array(
                'name'    => get_bloginfo( 'name' ),
                'tagline' => get_bloginfo( 'description' ),
                'url'     => home_url(),
            ),
            'theme'   => $design,
            'modules' => array(
                'active' => $modules,
                'count'  => count( $modules ),
            ),
            'pages' => array(
                'landings' => $landing_count->publish ?? 0,
                'pages'    => $page_count->publish ?? 0,
            ),
            'tools_available' => array(
                'app_generator'       => class_exists( 'Flavor_App_Generator' ),
                'app_profiles'        => class_exists( 'Flavor_App_Profiles' ),
                'page_creator'        => class_exists( 'Flavor_Page_Creator' ),
                'template_definitions'=> class_exists( 'Flavor_Template_Definitions' ),
                'vbp_api'             => class_exists( 'Flavor_VBP_Claude_API' ),
            ),
        ), 200 );
    }

    /**
     * Lista herramientas disponibles
     *
     * @return WP_REST_Response
     */
    public function list_tools() {
        return new WP_REST_Response( array(
            'endpoints' => array(
                // Información
                array(
                    'method'      => 'GET',
                    'path'        => '/profiles',
                    'description' => 'Lista perfiles de app disponibles (comunidad, tienda, etc.)',
                ),
                array(
                    'method'      => 'GET',
                    'path'        => '/templates',
                    'description' => 'Lista plantillas de sitio completas',
                ),
                array(
                    'method'      => 'GET',
                    'path'        => '/themes',
                    'description' => 'Lista temas de diseño (colores, estilos)',
                ),
                array(
                    'method'      => 'GET',
                    'path'        => '/modules',
                    'description' => 'Lista módulos disponibles y activos',
                ),
                array(
                    'method'      => 'GET',
                    'path'        => '/config-template/{template_id}',
                    'description' => 'Obtiene configuración completa de una plantilla',
                ),

                // Creación
                array(
                    'method'      => 'POST',
                    'path'        => '/site/create',
                    'description' => 'Crea sitio completo desde plantilla',
                    'params'      => array(
                        'template'    => 'ID de plantilla (ver /templates)',
                        'site_name'   => 'Nombre del sitio',
                        'theme'       => 'ID de tema (ver /themes)',
                        'import_demo' => 'true/false - importar datos de ejemplo',
                    ),
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/analyze',
                    'description' => 'Analiza descripción y propone estructura con IA',
                    'params'      => array(
                        'description' => 'Descripción del proyecto',
                    ),
                ),

                // Componentes
                array(
                    'method'      => 'POST',
                    'path'        => '/modules/activate',
                    'description' => 'Activa módulos',
                    'params'      => array( 'modules' => 'Array de IDs de módulo' ),
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/profile/set',
                    'description' => 'Establece perfil de app',
                    'params'      => array( 'profile' => 'ID de perfil' ),
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/theme/apply',
                    'description' => 'Aplica tema con personalización completa',
                    'params'      => array(
                        'theme'           => 'ID de tema base (dark-activism, light-eco, etc.)',
                        'primary_color'   => 'Color primario (#hex)',
                        'secondary_color' => 'Color secundario (#hex)',
                        'font_family_headings' => 'Fuente títulos (Inter, Montserrat, etc.)',
                        'button_border_radius' => 'Radio bordes botones (px)',
                        'portal_layout'   => 'Layout portal (ecosystem, grid, list)',
                        '...'             => 'Ver GET /design/options para todas las opciones',
                    ),
                ),
                array(
                    'method'      => 'GET',
                    'path'        => '/design/options',
                    'description' => 'Lista todas las opciones de diseño personalizables con valores actuales y por defecto',
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/pages/create-for-modules',
                    'description' => 'Crea páginas para módulos activos',
                    'params'      => array( 'modules' => 'Array de IDs de módulo' ),
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/menu',
                    'description' => 'Crea/actualiza menú de navegación',
                ),
                array(
                    'method'      => 'POST',
                    'path'        => '/demo-data/import',
                    'description' => 'Importa datos de demostración',
                    'params'      => array( 'template' => 'ID de plantilla' ),
                ),

                // Estado
                array(
                    'method'      => 'GET',
                    'path'        => '/site/status',
                    'description' => 'Obtiene estado actual del sitio',
                ),

                // Animaciones
                array(
                    'method'      => 'GET',
                    'path'        => '/animations',
                    'description' => 'Lista todas las animaciones y efectos disponibles',
                    'categories'  => array(
                        'entrance'   => 'Animaciones de entrada (fade, zoom, slide, bounce, rotate)',
                        'attention'  => 'Efectos de atención (pulse, shake, heartbeat, bounce)',
                        'hover'      => 'Efectos hover (grow, float, shadow, glow, underline)',
                        'scroll'     => 'Efectos scroll (parallax, reveal, fade)',
                        'background' => 'Fondos animados (gradient, particles, waves)',
                        'text'       => 'Efectos de texto (typewriter, glitch, counter)',
                    ),
                ),
            ),
            'example_workflow' => array(
                '1. GET /templates - Ver plantillas disponibles',
                '2. GET /config-template/comunidad - Ver detalles de una plantilla',
                '3. POST /site/create - Crear sitio con esa plantilla',
            ),
        ), 200 );
    }

    /**
     * Lista animaciones y efectos disponibles
     *
     * @return WP_REST_Response
     */
    public function list_animations() {
        $animations = array(
            'entrance' => array(
                'fade' => array(
                    'fadeIn'      => array( 'name' => 'Aparecer', 'css' => 'vbp-anim-fade-in' ),
                    'fadeInUp'    => array( 'name' => 'Aparecer desde abajo', 'css' => 'vbp-anim-fade-in-up' ),
                    'fadeInDown'  => array( 'name' => 'Aparecer desde arriba', 'css' => 'vbp-anim-fade-in-down' ),
                    'fadeInLeft'  => array( 'name' => 'Aparecer desde izquierda', 'css' => 'vbp-anim-fade-in-left' ),
                    'fadeInRight' => array( 'name' => 'Aparecer desde derecha', 'css' => 'vbp-anim-fade-in-right' ),
                ),
                'zoom' => array(
                    'zoomIn'  => array( 'name' => 'Zoom entrada', 'css' => 'vbp-anim-zoom-in' ),
                    'zoomOut' => array( 'name' => 'Zoom desde grande', 'css' => 'vbp-anim-zoom-out' ),
                ),
                'slide' => array(
                    'slideInUp'    => array( 'name' => 'Deslizar desde abajo', 'css' => 'vbp-anim-slide-in-up' ),
                    'slideInDown'  => array( 'name' => 'Deslizar desde arriba', 'css' => 'vbp-anim-slide-in-down' ),
                    'slideInLeft'  => array( 'name' => 'Deslizar desde izquierda', 'css' => 'vbp-anim-slide-in-left' ),
                    'slideInRight' => array( 'name' => 'Deslizar desde derecha', 'css' => 'vbp-anim-slide-in-right' ),
                ),
                'bounce' => array(
                    'bounceIn'     => array( 'name' => 'Entrada con rebote', 'css' => 'vbp-anim-bounce-in' ),
                    'bounceInUp'   => array( 'name' => 'Rebote desde abajo', 'css' => 'vbp-anim-bounce-in-up' ),
                    'bounceInDown' => array( 'name' => 'Rebote desde arriba', 'css' => 'vbp-anim-bounce-in-down' ),
                ),
                'rotate' => array(
                    'rotateIn'     => array( 'name' => 'Rotación entrada', 'css' => 'vbp-anim-rotate-in' ),
                    'flipIn'       => array( 'name' => 'Voltear entrada', 'css' => 'vbp-anim-flip-in' ),
                ),
                'special' => array(
                    'rubberBand' => array( 'name' => 'Banda elástica', 'css' => 'vbp-anim-rubber-band' ),
                    'jello'      => array( 'name' => 'Gelatina', 'css' => 'vbp-anim-jello' ),
                    'swing'      => array( 'name' => 'Balanceo', 'css' => 'vbp-anim-swing' ),
                ),
            ),
            'attention' => array(
                'pulse'     => array( 'name' => 'Pulso', 'css' => 'vbp-anim-pulse' ),
                'shake'     => array( 'name' => 'Sacudir', 'css' => 'vbp-anim-shake' ),
                'heartbeat' => array( 'name' => 'Latido', 'css' => 'vbp-anim-heartbeat' ),
                'bounce'    => array( 'name' => 'Rebote continuo', 'css' => 'vbp-anim-bounce' ),
                'flash'     => array( 'name' => 'Destello', 'css' => 'vbp-anim-flash' ),
                'tada'      => array( 'name' => 'Tada!', 'css' => 'vbp-anim-tada' ),
                'wobble'    => array( 'name' => 'Tambaleo', 'css' => 'vbp-anim-wobble' ),
            ),
            'hover' => array(
                'grow'        => array( 'name' => 'Crecer', 'css' => 'vbp-hover-grow' ),
                'shrink'      => array( 'name' => 'Encoger', 'css' => 'vbp-hover-shrink' ),
                'pulse'       => array( 'name' => 'Pulso', 'css' => 'vbp-hover-pulse' ),
                'float'       => array( 'name' => 'Flotar', 'css' => 'vbp-hover-float' ),
                'sink'        => array( 'name' => 'Hundir', 'css' => 'vbp-hover-sink' ),
                'rotate'      => array( 'name' => 'Rotar', 'css' => 'vbp-hover-rotate' ),
                'skew'        => array( 'name' => 'Inclinar', 'css' => 'vbp-hover-skew' ),
                'shadow'      => array( 'name' => 'Sombra', 'css' => 'vbp-hover-shadow' ),
                'shadowGrow'  => array( 'name' => 'Sombra creciente', 'css' => 'vbp-hover-shadow-grow' ),
                'underline'   => array( 'name' => 'Subrayado', 'css' => 'vbp-hover-underline' ),
                'borderFade'  => array( 'name' => 'Borde fade', 'css' => 'vbp-hover-border-fade' ),
                'glow'        => array( 'name' => 'Resplandor', 'css' => 'vbp-hover-glow' ),
            ),
            'scroll' => array(
                'parallax'      => array( 'name' => 'Parallax', 'css' => 'vbp-scroll-parallax' ),
                'reveal'        => array( 'name' => 'Revelar al scroll', 'css' => 'vbp-scroll-reveal' ),
                'fadeOnScroll'  => array( 'name' => 'Fade al scroll', 'css' => 'vbp-scroll-fade' ),
                'scaleOnScroll' => array( 'name' => 'Escala al scroll', 'css' => 'vbp-scroll-scale' ),
            ),
            'background' => array(
                'gradientShift'  => array( 'name' => 'Gradiente animado', 'css' => 'vbp-bg-gradient-shift' ),
                'colorPulse'     => array( 'name' => 'Pulso de color', 'css' => 'vbp-bg-color-pulse' ),
                'particles'      => array( 'name' => 'Partículas', 'css' => 'vbp-bg-particles', 'js' => true ),
                'waves'          => array( 'name' => 'Ondas', 'css' => 'vbp-bg-waves' ),
                'noise'          => array( 'name' => 'Ruido/Grain', 'css' => 'vbp-bg-noise' ),
            ),
            'text' => array(
                'typewriter'   => array( 'name' => 'Máquina de escribir', 'css' => 'vbp-text-typewriter', 'js' => true ),
                'glitch'       => array( 'name' => 'Glitch', 'css' => 'vbp-text-glitch' ),
                'gradient'     => array( 'name' => 'Gradiente animado', 'css' => 'vbp-text-gradient' ),
                'highlight'    => array( 'name' => 'Resaltado', 'css' => 'vbp-text-highlight' ),
                'splitReveal'  => array( 'name' => 'Revelar por letras', 'css' => 'vbp-text-split-reveal', 'js' => true ),
                'countUp'      => array( 'name' => 'Contador', 'css' => 'vbp-text-count-up', 'js' => true ),
            ),
        );

        $durations = array(
            'fastest' => '0.2s',
            'fast'    => '0.3s',
            'normal'  => '0.6s',
            'slow'    => '1s',
            'slower'  => '1.5s',
            'slowest' => '2s',
        );

        $delays = array(
            'none'  => '0s',
            '100'   => '0.1s',
            '200'   => '0.2s',
            '300'   => '0.3s',
            '400'   => '0.4s',
            '500'   => '0.5s',
            '750'   => '0.75s',
            '1000'  => '1s',
            '1500'  => '1.5s',
            '2000'  => '2s',
        );

        $easings = array(
            'linear'    => 'linear',
            'ease'      => 'ease',
            'easeIn'    => 'ease-in',
            'easeOut'   => 'ease-out',
            'easeInOut' => 'ease-in-out',
            'bounce'    => 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
            'elastic'   => 'cubic-bezier(0.68, -0.6, 0.32, 1.6)',
            'smooth'    => 'cubic-bezier(0.4, 0, 0.2, 1)',
        );

        return new WP_REST_Response( array(
            'animations' => $animations,
            'options' => array(
                'durations' => $durations,
                'delays'    => $delays,
                'easings'   => $easings,
                'repeat'    => array( '1', '2', '3', 'infinite' ),
            ),
            'usage' => array(
                'element_attributes' => array(
                    'data-vbp-animation' => 'fadeInUp',
                    'data-vbp-duration'  => '0.6s',
                    'data-vbp-delay'     => '0.2s',
                    'data-vbp-easing'    => 'ease-out',
                ),
                'css_variables' => array(
                    '--vbp-anim-duration' => '0.6s',
                    '--vbp-anim-delay'    => '0s',
                    '--vbp-anim-easing'   => 'ease-out',
                ),
                'example' => '<div class="vbp-animated" data-vbp-animation="fadeInUp" data-vbp-delay="200">Contenido</div>',
            ),
        ), 200 );
    }

    // =========================================================================
    // VALIDACIÓN Y EXPORTACIÓN
    // =========================================================================

    /**
     * Valida una configuración antes de aplicarla
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function validate_config( $request ) {
        $config = $request->get_json_params();
        $errors = array();
        $warnings = array();

        // Validar template si se especifica
        if ( ! empty( $config['template'] ) ) {
            $templates = $this->get_templates_list();
            if ( ! isset( $templates[ $config['template'] ] ) ) {
                $errors[] = array(
                    'field'   => 'template',
                    'message' => sprintf( __( 'Plantilla "%s" no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ), $config['template'] ),
                    'available' => array_keys( $templates ),
                );
            }
        }

        // Validar theme si se especifica
        if ( ! empty( $config['theme'] ) ) {
            $themes = $this->get_themes_list();
            if ( ! isset( $themes[ $config['theme'] ] ) ) {
                $errors[] = array(
                    'field'   => 'theme',
                    'message' => sprintf( __( 'Tema "%s" no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), $config['theme'] ),
                    'available' => array_keys( $themes ),
                );
            }
        }

        // Validar módulos si se especifican
        if ( ! empty( $config['modules'] ) && is_array( $config['modules'] ) ) {
            $available_modules = $this->get_modules_list();
            foreach ( $config['modules'] as $module_id ) {
                $normalized_id = Flavor_Platform_Helpers::normalize_module_id( $module_id );
                $found = false;
                foreach ( array_keys( $available_modules ) as $available_id ) {
                    if ( Flavor_Platform_Helpers::module_ids_match( $module_id, $available_id ) ) {
                        $found = true;
                        break;
                    }
                }
                if ( ! $found ) {
                    $warnings[] = array(
                        'field'   => 'modules',
                        'message' => sprintf( __( 'Módulo "%s" no reconocido', FLAVOR_PLATFORM_TEXT_DOMAIN ), $module_id ),
                    );
                }
            }
        }

        // Validar colores
        if ( ! empty( $config['settings']['primary_color'] ) ) {
            if ( ! preg_match( '/^#[0-9A-Fa-f]{6}$/', $config['settings']['primary_color'] ) ) {
                $errors[] = array(
                    'field'   => 'settings.primary_color',
                    'message' => __( 'Color primario debe ser un hex válido (#RRGGBB)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                );
            }
        }

        $is_valid = empty( $errors );

        return new WP_REST_Response( array(
            'valid'    => $is_valid,
            'errors'   => $errors,
            'warnings' => $warnings,
            'config'   => $config,
        ), $is_valid ? 200 : 400 );
    }

    /**
     * Exporta la configuración actual del sitio
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_config( $request ) {
        $include_content = $request->get_param( 'include_content' ) === 'true';

        // Obtener configuración del plugin
        $plugin_settings = get_option( 'flavor_chat_ia_settings', array() );
        $design_settings = get_option( 'flavor_design_settings', array() );
        $active_modules = $plugin_settings['active_modules'] ?? array();
        $active_profile = get_option( 'flavor_app_profile', '' );
        $active_theme = get_option( 'flavor_active_theme', '' );

        $export = array(
            'version'   => FLAVOR_PLATFORM_VERSION ?? '2.0.0',
            'exported'  => current_time( 'c' ),
            'site_url'  => home_url(),
            'profile'   => $active_profile,
            'theme'     => $active_theme,
            'modules'   => $active_modules,
            'design'    => $design_settings,
            'settings'  => array(
                'site_name' => get_bloginfo( 'name' ),
                'tagline'   => get_bloginfo( 'description' ),
            ),
        );

        // Incluir menús
        $menus = wp_get_nav_menus();
        $export['menus'] = array();
        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );
            $export['menus'][ $menu->slug ] = array(
                'name'      => $menu->name,
                'locations' => array_keys( get_nav_menu_locations(), $menu->term_id ),
                'items'     => array_map( function( $item ) {
                    return array(
                        'title' => $item->title,
                        'url'   => $item->url,
                        'type'  => $item->type,
                    );
                }, $items ?: array() ),
            );
        }

        // Incluir páginas VBP si se solicita
        if ( $include_content ) {
            $landing_pages = get_posts( array(
                'post_type'   => 'flavor_landing',
                'numberposts' => -1,
                'post_status' => array( 'publish', 'draft' ),
            ) );

            $export['pages'] = array();
            foreach ( $landing_pages as $page ) {
                $vbp_data = get_post_meta( $page->ID, '_vbp_document_data', true );
                $export['pages'][] = array(
                    'title'    => $page->post_title,
                    'slug'     => $page->post_name,
                    'status'   => $page->post_status,
                    'vbp_data' => $vbp_data,
                );
            }
        }

        return new WP_REST_Response( $export, 200 );
    }

    /**
     * Importa una configuración
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function import_config( $request ) {
        $config = $request->get_json_params();
        $dry_run = $request->get_param( 'dry_run' ) === 'true';
        $results = array();

        if ( empty( $config ) ) {
            return new WP_Error(
                'invalid_config',
                __( 'Configuración inválida o vacía', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Simular o aplicar cambios
        $action_verb = $dry_run ? 'would_' : '';

        // Importar perfil
        if ( ! empty( $config['profile'] ) ) {
            if ( ! $dry_run ) {
                update_option( 'flavor_app_profile', sanitize_text_field( $config['profile'] ) );
            }
            $results['profile'] = array(
                'action' => $action_verb . 'set',
                'value'  => $config['profile'],
            );
        }

        // Importar tema
        if ( ! empty( $config['theme'] ) ) {
            if ( ! $dry_run ) {
                update_option( 'flavor_active_theme', sanitize_text_field( $config['theme'] ) );
            }
            $results['theme'] = array(
                'action' => $action_verb . 'set',
                'value'  => $config['theme'],
            );
        }

        // Importar módulos
        if ( ! empty( $config['modules'] ) && is_array( $config['modules'] ) ) {
            $modules = array_map( 'sanitize_text_field', $config['modules'] );
            if ( ! $dry_run ) {
                $settings = get_option( 'flavor_chat_ia_settings', array() );
                $settings['active_modules'] = $modules;
                update_option( 'flavor_chat_ia_settings', $settings );
            }
            $results['modules'] = array(
                'action' => $action_verb . 'activate',
                'count'  => count( $modules ),
                'list'   => $modules,
            );
        }

        // Importar diseño
        if ( ! empty( $config['design'] ) && is_array( $config['design'] ) ) {
            if ( ! $dry_run ) {
                update_option( 'flavor_design_settings', $config['design'] );
            }
            $results['design'] = array(
                'action' => $action_verb . 'apply',
                'keys'   => array_keys( $config['design'] ),
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'dry_run' => $dry_run,
            'results' => $results,
        ), 200 );
    }

    /**
     * Health check del sistema
     *
     * @return WP_REST_Response
     */
    public function health_check() {
        global $wpdb;

        $checks = array();

        // Verificar tablas del plugin
        $tables = array(
            'flavor_gc_pedidos',
            'flavor_gc_consumidores',
            'flavor_socios',
            'flavor_eventos',
        );

        $checks['database'] = array(
            'status' => 'ok',
            'tables' => array(),
        );

        foreach ( $tables as $table ) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full_table ) ) === $full_table;
            $checks['database']['tables'][ $table ] = $exists ? 'exists' : 'missing';
            if ( ! $exists ) {
                $checks['database']['status'] = 'warning';
            }
        }

        // Verificar módulos activos
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $active_modules = $settings['active_modules'] ?? array();
        $checks['modules'] = array(
            'status' => 'ok',
            'active' => count( $active_modules ),
            'list'   => $active_modules,
        );

        // Verificar VBP
        $checks['vbp'] = array(
            'status'    => class_exists( 'Flavor_VBP_Editor' ) ? 'ok' : 'not_loaded',
            'available' => class_exists( 'Flavor_VBP_Block_Library' ),
        );

        // Verificar opciones críticas
        $checks['options'] = array(
            'flavor_chat_ia_settings' => ! empty( $settings ),
            'flavor_design_settings'  => ! empty( get_option( 'flavor_design_settings' ) ),
            'flavor_app_profile'      => ! empty( get_option( 'flavor_app_profile' ) ),
        );

        // Estado general
        $overall_status = 'healthy';
        foreach ( $checks as $check ) {
            if ( isset( $check['status'] ) && $check['status'] !== 'ok' ) {
                $overall_status = 'degraded';
                break;
            }
        }

        return new WP_REST_Response( array(
            'status'    => $overall_status,
            'timestamp' => current_time( 'c' ),
            'version'   => FLAVOR_PLATFORM_VERSION ?? '2.0.0',
            'php'       => PHP_VERSION,
            'wp'        => get_bloginfo( 'version' ),
            'checks'    => $checks,
        ), 200 );
    }

    /**
     * Obtiene lista de templates
     *
     * @return array
     */
    private function get_templates_list() {
        $response = $this->list_templates();
        if ( $response instanceof WP_REST_Response ) {
            return $response->get_data();
        }
        return array();
    }

    /**
     * Obtiene lista de themes
     *
     * @return array
     */
    private function get_themes_list() {
        $response = $this->list_themes();
        if ( $response instanceof WP_REST_Response ) {
            return $response->get_data();
        }
        return array();
    }

    /**
     * Obtiene lista de módulos
     *
     * @return array
     */
    private function get_modules_list() {
        $response = $this->list_modules();
        if ( $response instanceof WP_REST_Response ) {
            return $response->get_data();
        }
        return array();
    }
}

// Inicializar
Flavor_Site_Builder_API::get_instance();
