<?php
/**
 * REST API para configuración de sitio desde Claude Code
 *
 * Endpoints para configurar layouts, menús, footers y ajustes del sitio.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de configuración del sitio
 */
class Flavor_Site_Config_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Site_Config_API|null
     */
    private static $instancia = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Clave de API para autenticación
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Site_Config_API
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $this->api_key = $settings['vbp_api_key'] ?? 'flavor-vbp-2024';

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verificar permisos de API
     *
     * @param WP_REST_Request $request Petición.
     * @return bool|WP_Error
     */
    public function check_api_permission( $request ) {
        $api_key = $request->get_header( 'X-VBP-Key' );

        if ( empty( $api_key ) ) {
            $api_key = $request->get_param( 'api_key' );
        }

        if ( $api_key !== $this->api_key ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'API key inválida', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // === LAYOUTS ===

        // Obtener configuración actual de layouts
        register_rest_route( self::NAMESPACE, '/site/layouts', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_layouts' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar layout activo
        register_rest_route( self::NAMESPACE, '/site/layouts/active', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_active_layout' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar ajustes de layout
        register_rest_route( self::NAMESPACE, '/site/layouts/settings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_layout_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === MENÚS ===

        // Listar menús existentes
        register_rest_route( self::NAMESPACE, '/site/menus', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_menus' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear menú de navegación
        register_rest_route( self::NAMESPACE, '/site/menus', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_menu' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar menú existente
        register_rest_route( self::NAMESPACE, '/site/menus/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_menu' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Asignar menú a ubicación
        register_rest_route( self::NAMESPACE, '/site/menus/locations', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'assign_menu_location' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === SITE SETTINGS ===

        // Obtener configuración del sitio
        register_rest_route( self::NAMESPACE, '/site/settings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_site_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración del sitio
        register_rest_route( self::NAMESPACE, '/site/settings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_site_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === CONFIGURACIÓN COMPLETA ===

        // Aplicar configuración completa de sitio (layout + menú + settings)
        register_rest_route( self::NAMESPACE, '/site/apply-config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_full_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Generar script de configuración
        register_rest_route( self::NAMESPACE, '/site/generate-script', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_config_script' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * Obtener configuración de layouts disponibles y activos
     */
    public function get_layouts( $request ) {
        $registry = function_exists( 'flavor_layout_registry' ) ? flavor_layout_registry() : null;

        if ( ! $registry ) {
            return new WP_Error( 'no_registry', 'Layout registry no disponible', array( 'status' => 500 ) );
        }

        $settings = get_option( 'flavor_layout_settings', array() );
        $active_layout = $registry->get_active_layout();

        return rest_ensure_response( array(
            'active' => $active_layout,
            'available_menus' => array_keys( $registry->get_menus() ),
            'available_footers' => array_keys( $registry->get_footers() ),
            'presets' => array_keys( $registry->get_layouts() ),
            'current_settings' => $settings,
            'menu_details' => $registry->get_menus(),
            'footer_details' => $registry->get_footers(),
        ) );
    }

    /**
     * Configurar layout activo
     */
    public function set_active_layout( $request ) {
        $menu = sanitize_key( $request->get_param( 'menu' ) );
        $footer = sanitize_key( $request->get_param( 'footer' ) );
        $preset = sanitize_key( $request->get_param( 'preset' ) );

        $registry = function_exists( 'flavor_layout_registry' ) ? flavor_layout_registry() : null;

        if ( ! $registry ) {
            return new WP_Error( 'no_registry', 'Layout registry no disponible', array( 'status' => 500 ) );
        }

        // Si se especifica preset, obtener menu y footer del preset
        if ( $preset ) {
            $layouts = $registry->get_layouts();
            if ( isset( $layouts[ $preset ] ) ) {
                $menu = $layouts[ $preset ]['menu'];
                $footer = $layouts[ $preset ]['footer'];
            }
        }

        // Validar que existen
        if ( $menu && ! $registry->get_menu( $menu ) ) {
            return new WP_Error( 'invalid_menu', "Menú '$menu' no existe", array( 'status' => 400 ) );
        }
        if ( $footer && ! $registry->get_footer( $footer ) ) {
            return new WP_Error( 'invalid_footer', "Footer '$footer' no existe", array( 'status' => 400 ) );
        }

        // Guardar
        $settings = get_option( 'flavor_layout_settings', array() );
        if ( $menu ) {
            $settings['active_menu'] = $menu;
        }
        if ( $footer ) {
            $settings['active_footer'] = $footer;
        }
        update_option( 'flavor_layout_settings', $settings );

        return rest_ensure_response( array(
            'success' => true,
            'active_menu' => $settings['active_menu'] ?? 'classic',
            'active_footer' => $settings['active_footer'] ?? 'multi-column',
        ) );
    }

    /**
     * Actualizar ajustes de layout
     */
    public function update_layout_settings( $request ) {
        $settings = get_option( 'flavor_layout_settings', array() );
        $params = $request->get_json_params();

        // Campos permitidos
        $allowed_fields = array(
            'cta_text', 'cta_url', 'cta_style',
            'logo_url', 'logo_dark_url', 'logo_width',
            'social_links', 'contact_phone', 'contact_email', 'contact_address',
            'business_hours', 'copyright_text',
            'app_store_url', 'play_store_url',
            'inject_in_any_theme', 'show_on_landings',
            'header_bg_color', 'header_text_color',
            'footer_bg_color', 'footer_text_color',
            'sponsors',
        );

        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                if ( is_array( $params[ $field ] ) ) {
                    $settings[ $field ] = $this->sanitize_array( $params[ $field ] );
                } else {
                    $settings[ $field ] = sanitize_text_field( $params[ $field ] );
                }
            }
        }

        update_option( 'flavor_layout_settings', $settings );

        return rest_ensure_response( array(
            'success' => true,
            'settings' => $settings,
        ) );
    }

    /**
     * Obtener menús de WordPress
     */
    public function get_menus( $request ) {
        $menus = wp_get_nav_menus();
        $locations = get_nav_menu_locations();
        $registered_locations = get_registered_nav_menus();

        $result = array(
            'menus' => array(),
            'locations' => $locations,
            'registered_locations' => $registered_locations,
        );

        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );
            $result['menus'][] = array(
                'id' => $menu->term_id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'items_count' => count( $items ?: array() ),
                'items' => $this->format_menu_items( $items ?: array() ),
            );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Crear menú de navegación
     */
    public function create_menu( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $items = $request->get_param( 'items' ) ?: array();
        $location = sanitize_key( $request->get_param( 'location' ) );

        if ( empty( $name ) ) {
            return new WP_Error( 'missing_name', 'Nombre del menú requerido', array( 'status' => 400 ) );
        }

        // Verificar si ya existe
        $existing = wp_get_nav_menu_object( $name );
        if ( $existing ) {
            // Actualizar existente
            $menu_id = $existing->term_id;
            // Eliminar items antiguos
            $old_items = wp_get_nav_menu_items( $menu_id );
            foreach ( $old_items as $item ) {
                wp_delete_post( $item->ID, true );
            }
        } else {
            // Crear nuevo
            $menu_id = wp_create_nav_menu( $name );
            if ( is_wp_error( $menu_id ) ) {
                return $menu_id;
            }
        }

        // Añadir items
        $created_items = array();
        foreach ( $items as $index => $item ) {
            $item_data = array(
                'menu-item-title' => sanitize_text_field( $item['title'] ?? $item['label'] ?? '' ),
                'menu-item-url' => esc_url_raw( $item['url'] ?? '' ),
                'menu-item-status' => 'publish',
                'menu-item-position' => $index + 1,
                'menu-item-type' => 'custom',
            );

            // Si es un post/page
            if ( ! empty( $item['object_id'] ) ) {
                $item_data['menu-item-object-id'] = absint( $item['object_id'] );
                $item_data['menu-item-object'] = $item['object'] ?? 'page';
                $item_data['menu-item-type'] = 'post_type';
            }

            $item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );

            if ( ! is_wp_error( $item_id ) ) {
                // Guardar icono si existe
                if ( ! empty( $item['icon'] ) ) {
                    update_post_meta( $item_id, '_menu_item_icon', sanitize_text_field( $item['icon'] ) );
                }
                $created_items[] = $item_id;
            }
        }

        // Asignar a ubicación si se especifica
        if ( $location ) {
            $locations = get_nav_menu_locations();
            $locations[ $location ] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }

        return rest_ensure_response( array(
            'success' => true,
            'menu_id' => $menu_id,
            'items_created' => count( $created_items ),
            'location' => $location,
        ) );
    }

    /**
     * Actualizar menú existente
     */
    public function update_menu( $request ) {
        $menu_id = absint( $request->get_param( 'id' ) );
        $items = $request->get_param( 'items' );
        $name = $request->get_param( 'name' );

        $menu = wp_get_nav_menu_object( $menu_id );
        if ( ! $menu ) {
            return new WP_Error( 'menu_not_found', 'Menú no encontrado', array( 'status' => 404 ) );
        }

        // Actualizar nombre si se proporciona
        if ( $name ) {
            wp_update_nav_menu_object( $menu_id, array( 'menu-name' => sanitize_text_field( $name ) ) );
        }

        // Actualizar items si se proporcionan
        if ( is_array( $items ) ) {
            // Eliminar items antiguos
            $old_items = wp_get_nav_menu_items( $menu_id );
            foreach ( $old_items as $item ) {
                wp_delete_post( $item->ID, true );
            }

            // Añadir nuevos items
            foreach ( $items as $index => $item ) {
                $item_data = array(
                    'menu-item-title' => sanitize_text_field( $item['title'] ?? $item['label'] ?? '' ),
                    'menu-item-url' => esc_url_raw( $item['url'] ?? '' ),
                    'menu-item-status' => 'publish',
                    'menu-item-position' => $index + 1,
                    'menu-item-type' => 'custom',
                );

                $item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );

                if ( ! is_wp_error( $item_id ) && ! empty( $item['icon'] ) ) {
                    update_post_meta( $item_id, '_menu_item_icon', sanitize_text_field( $item['icon'] ) );
                }
            }
        }

        return rest_ensure_response( array(
            'success' => true,
            'menu_id' => $menu_id,
        ) );
    }

    /**
     * Asignar menú a ubicación
     */
    public function assign_menu_location( $request ) {
        $menu_id = absint( $request->get_param( 'menu_id' ) );
        $location = sanitize_key( $request->get_param( 'location' ) );

        if ( ! $menu_id || ! $location ) {
            return new WP_Error( 'missing_params', 'menu_id y location son requeridos', array( 'status' => 400 ) );
        }

        $locations = get_nav_menu_locations();
        $locations[ $location ] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );

        return rest_ensure_response( array(
            'success' => true,
            'location' => $location,
            'menu_id' => $menu_id,
        ) );
    }

    /**
     * Obtener configuración del sitio
     */
    public function get_site_settings( $request ) {
        return rest_ensure_response( array(
            'site_name' => get_bloginfo( 'name' ),
            'site_description' => get_bloginfo( 'description' ),
            'site_url' => home_url(),
            'admin_email' => get_option( 'admin_email' ),
            'layout_settings' => get_option( 'flavor_layout_settings', array() ),
            'design_settings' => get_option( 'flavor_design_settings', array() ),
            'chat_settings' => get_option( 'flavor_chat_ia_settings', array() ),
        ) );
    }

    /**
     * Actualizar configuración del sitio
     */
    public function update_site_settings( $request ) {
        $params = $request->get_json_params();
        $updated = array();

        // Actualizar nombre del sitio
        if ( isset( $params['site_name'] ) ) {
            update_option( 'blogname', sanitize_text_field( $params['site_name'] ) );
            $updated['site_name'] = true;
        }

        // Actualizar descripción
        if ( isset( $params['site_description'] ) ) {
            update_option( 'blogdescription', sanitize_text_field( $params['site_description'] ) );
            $updated['site_description'] = true;
        }

        // Actualizar design settings
        if ( isset( $params['design_settings'] ) && is_array( $params['design_settings'] ) ) {
            $design = get_option( 'flavor_design_settings', array() );
            $design = array_merge( $design, $this->sanitize_array( $params['design_settings'] ) );
            update_option( 'flavor_design_settings', $design );
            $updated['design_settings'] = true;
        }

        return rest_ensure_response( array(
            'success' => true,
            'updated' => $updated,
        ) );
    }

    /**
     * Aplicar configuración completa de sitio
     *
     * Recibe un JSON con toda la configuración y la aplica de una vez
     */
    public function apply_full_config( $request ) {
        $config = $request->get_json_params();
        $results = array();

        // 1. Configurar layout activo
        if ( isset( $config['layout'] ) ) {
            $layout_request = new WP_REST_Request( 'POST' );
            $layout_request->set_body_params( $config['layout'] );
            $results['layout'] = $this->set_active_layout( $layout_request );
        }

        // 2. Configurar ajustes de layout
        if ( isset( $config['layout_settings'] ) ) {
            $settings_request = new WP_REST_Request( 'POST' );
            $settings_request->set_header( 'Content-Type', 'application/json' );
            $settings_request->set_body( wp_json_encode( $config['layout_settings'] ) );
            $results['layout_settings'] = $this->update_layout_settings( $settings_request );
        }

        // 3. Crear/actualizar menú principal
        if ( isset( $config['primary_menu'] ) ) {
            $menu_request = new WP_REST_Request( 'POST' );
            $menu_request->set_body_params( array_merge(
                $config['primary_menu'],
                array( 'location' => 'primary' )
            ) );
            $results['primary_menu'] = $this->create_menu( $menu_request );
        }

        // 4. Crear menús de footer
        if ( isset( $config['footer_menus'] ) && is_array( $config['footer_menus'] ) ) {
            $results['footer_menus'] = array();
            foreach ( $config['footer_menus'] as $index => $footer_menu ) {
                $location = 'footer-' . ( $index + 1 );
                $menu_request = new WP_REST_Request( 'POST' );
                $menu_request->set_body_params( array_merge(
                    $footer_menu,
                    array( 'location' => $location )
                ) );
                $results['footer_menus'][ $location ] = $this->create_menu( $menu_request );
            }
        }

        // 5. Actualizar configuración del sitio
        if ( isset( $config['site_settings'] ) ) {
            $site_request = new WP_REST_Request( 'POST' );
            $site_request->set_header( 'Content-Type', 'application/json' );
            $site_request->set_body( wp_json_encode( $config['site_settings'] ) );
            $results['site_settings'] = $this->update_site_settings( $site_request );
        }

        return rest_ensure_response( array(
            'success' => true,
            'results' => $results,
        ) );
    }

    /**
     * Generar script PHP de configuración
     *
     * Genera un script que se puede ejecutar para aplicar la configuración
     */
    public function generate_config_script( $request ) {
        $config = $request->get_json_params();

        $script = "<?php\n";
        $script .= "/**\n * Script de configuración generado automáticamente\n";
        $script .= " * Generado: " . current_time( 'mysql' ) . "\n */\n\n";
        $script .= "if ( ! defined( 'ABSPATH' ) ) {\n";
        $script .= "    require_once dirname( __FILE__ ) . '/wp-load.php';\n";
        $script .= "}\n\n";

        // Layout activo
        if ( isset( $config['layout'] ) ) {
            $script .= "// Configurar layout activo\n";
            $script .= "\$layout_settings = get_option( 'flavor_layout_settings', array() );\n";
            if ( isset( $config['layout']['menu'] ) ) {
                $script .= "\$layout_settings['active_menu'] = '" . esc_attr( $config['layout']['menu'] ) . "';\n";
            }
            if ( isset( $config['layout']['footer'] ) ) {
                $script .= "\$layout_settings['active_footer'] = '" . esc_attr( $config['layout']['footer'] ) . "';\n";
            }
            $script .= "update_option( 'flavor_layout_settings', \$layout_settings );\n\n";
        }

        // Ajustes de layout
        if ( isset( $config['layout_settings'] ) ) {
            $script .= "// Configurar ajustes de layout\n";
            $script .= "\$layout_settings = get_option( 'flavor_layout_settings', array() );\n";
            foreach ( $config['layout_settings'] as $key => $value ) {
                if ( is_array( $value ) ) {
                    $script .= "\$layout_settings['" . esc_attr( $key ) . "'] = " . var_export( $value, true ) . ";\n";
                } else {
                    $script .= "\$layout_settings['" . esc_attr( $key ) . "'] = '" . esc_attr( $value ) . "';\n";
                }
            }
            $script .= "update_option( 'flavor_layout_settings', \$layout_settings );\n\n";
        }

        // Menú principal
        if ( isset( $config['primary_menu'] ) ) {
            $script .= "// Crear menú principal\n";
            $script .= "\$menu_name = '" . esc_attr( $config['primary_menu']['name'] ) . "';\n";
            $script .= "\$existing_menu = wp_get_nav_menu_object( \$menu_name );\n";
            $script .= "if ( \$existing_menu ) {\n";
            $script .= "    \$menu_id = \$existing_menu->term_id;\n";
            $script .= "    \$old_items = wp_get_nav_menu_items( \$menu_id );\n";
            $script .= "    foreach ( \$old_items as \$item ) { wp_delete_post( \$item->ID, true ); }\n";
            $script .= "} else {\n";
            $script .= "    \$menu_id = wp_create_nav_menu( \$menu_name );\n";
            $script .= "}\n\n";

            $script .= "\$menu_items = " . var_export( $config['primary_menu']['items'], true ) . ";\n";
            $script .= "foreach ( \$menu_items as \$index => \$item ) {\n";
            $script .= "    wp_update_nav_menu_item( \$menu_id, 0, array(\n";
            $script .= "        'menu-item-title' => \$item['title'],\n";
            $script .= "        'menu-item-url' => \$item['url'],\n";
            $script .= "        'menu-item-status' => 'publish',\n";
            $script .= "        'menu-item-position' => \$index + 1,\n";
            $script .= "        'menu-item-type' => 'custom',\n";
            $script .= "    ) );\n";
            $script .= "}\n\n";

            $script .= "// Asignar a ubicación primary\n";
            $script .= "\$locations = get_nav_menu_locations();\n";
            $script .= "\$locations['primary'] = \$menu_id;\n";
            $script .= "set_theme_mod( 'nav_menu_locations', \$locations );\n\n";
        }

        $script .= "echo 'Configuración aplicada correctamente.';\n";

        // Guardar script
        $script_path = FLAVOR_CHAT_IA_PATH . 'tools/apply-config-' . time() . '.php';
        $script_dir = dirname( $script_path );

        if ( ! file_exists( $script_dir ) ) {
            wp_mkdir_p( $script_dir );
        }

        file_put_contents( $script_path, $script );

        return rest_ensure_response( array(
            'success' => true,
            'script_path' => $script_path,
            'script_content' => $script,
            'execute_command' => 'php ' . $script_path,
        ) );
    }

    /**
     * Formatear items de menú para la respuesta
     */
    private function format_menu_items( $items ) {
        $formatted = array();
        foreach ( $items as $item ) {
            $formatted[] = array(
                'id' => $item->ID,
                'title' => $item->title,
                'url' => $item->url,
                'type' => $item->type,
                'object' => $item->object,
                'object_id' => $item->object_id,
                'parent' => $item->menu_item_parent,
                'icon' => get_post_meta( $item->ID, '_menu_item_icon', true ),
            );
        }
        return $formatted;
    }

    /**
     * Sanitizar array recursivamente
     */
    private function sanitize_array( $array ) {
        $sanitized = array();
        foreach ( $array as $key => $value ) {
            $key = sanitize_key( $key );
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_array( $value );
            } elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                $sanitized[ $key ] = esc_url_raw( $value );
            } else {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }
        return $sanitized;
    }
}

/**
 * Función helper para obtener instancia
 */
function flavor_site_config_api() {
    return Flavor_Site_Config_API::get_instance();
}
