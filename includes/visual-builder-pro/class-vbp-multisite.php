<?php
/**
 * Visual Builder Pro - Soporte Multi-site
 *
 * Gestión centralizada de templates, design tokens y widgets globales
 * para redes WordPress multisite.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para soporte Multi-site de VBP
 *
 * @since 2.3.0
 */
class Flavor_VBP_Multisite {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Multisite|null
     */
    private static $instance = null;

    /**
     * Namespace de la API REST
     *
     * @var string
     */
    const REST_NAMESPACE = 'flavor-vbp/v1';

    /**
     * Option key para configuración de red
     *
     * @var string
     */
    const NETWORK_OPTION_KEY = 'vbp_network_settings';

    /**
     * Option key para templates compartidos
     *
     * @var string
     */
    const SHARED_TEMPLATES_KEY = 'vbp_shared_templates';

    /**
     * Option key para design tokens de red
     *
     * @var string
     */
    const NETWORK_TOKENS_KEY = 'vbp_network_design_tokens';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Multisite
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Solo inicializar en multisite
        if ( ! is_multisite() ) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Admin de red
        add_action( 'network_admin_menu', array( $this, 'add_network_admin_menu' ) );

        // Sincronización de tokens
        add_action( 'update_option_vbp_design_tokens', array( $this, 'maybe_sync_tokens_to_network' ), 10, 2 );

        // Assets en admin de red
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_network_admin_assets' ) );

        // Filtro para obtener tokens (incluye los de red)
        add_filter( 'vbp_get_design_tokens', array( $this, 'merge_network_tokens' ), 10, 1 );

        // Filtro para obtener templates (incluye los compartidos)
        add_filter( 'vbp_get_templates', array( $this, 'merge_shared_templates' ), 10, 1 );
    }

    /**
     * Registra las rutas REST
     */
    public function register_rest_routes() {
        // Estado de multisite
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/status',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_multisite_status' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Configuración de red
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_network_settings' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'rest_update_network_settings' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
            )
        );

        // Sitios de la red
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/sites',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_network_sites' ),
                'permission_callback' => array( $this, 'check_network_admin_permission' ),
            )
        );

        // Templates compartidos
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/templates',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_shared_templates' ),
                    'permission_callback' => array( $this, 'check_edit_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'rest_share_template' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
            )
        );

        // Template compartido específico
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/templates/(?P<id>[a-zA-Z0-9_-]+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_shared_template' ),
                    'permission_callback' => array( $this, 'check_edit_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'rest_delete_shared_template' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
            )
        );

        // Importar template compartido al sitio actual
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/templates/(?P<id>[a-zA-Z0-9_-]+)/import',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_import_shared_template' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Design tokens de red
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/tokens',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_network_tokens' ),
                    'permission_callback' => array( $this, 'check_edit_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'rest_update_network_tokens' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
            )
        );

        // Sincronizar tokens a todos los sitios
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/tokens/sync',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_sync_tokens_to_sites' ),
                'permission_callback' => array( $this, 'check_network_admin_permission' ),
            )
        );

        // Widgets globales compartidos
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/widgets',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_shared_widgets' ),
                    'permission_callback' => array( $this, 'check_edit_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'rest_share_widget' ),
                    'permission_callback' => array( $this, 'check_network_admin_permission' ),
                ),
            )
        );

        // Estadísticas de uso en la red
        register_rest_route(
            self::REST_NAMESPACE,
            '/multisite/stats',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_network_stats' ),
                'permission_callback' => array( $this, 'check_network_admin_permission' ),
            )
        );
    }

    /**
     * Verifica permiso de edición
     *
     * @return bool
     */
    public function check_edit_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permiso de administrador de red
     *
     * @return bool
     */
    public function check_network_admin_permission() {
        return is_super_admin();
    }

    // ==========================================
    // REST CALLBACKS
    // ==========================================

    /**
     * Obtiene el estado de multisite
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_multisite_status( $request ) {
        $is_multisite = is_multisite();

        $response = array(
            'is_multisite'       => $is_multisite,
            'is_network_admin'   => is_super_admin(),
            'current_site_id'    => get_current_blog_id(),
            'current_site_name'  => get_bloginfo( 'name' ),
            'network_name'       => $is_multisite ? get_network()->site_name : null,
            'total_sites'        => $is_multisite ? get_blog_count() : 1,
            'features_enabled'   => $this->get_enabled_features(),
        );

        return rest_ensure_response( $response );
    }

    /**
     * Obtiene la configuración de red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_network_settings( $request ) {
        $settings = $this->get_network_settings();
        return rest_ensure_response( $settings );
    }

    /**
     * Actualiza la configuración de red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_update_network_settings( $request ) {
        $settings = $request->get_json_params();

        $allowed_keys = array(
            'enable_shared_templates',
            'enable_network_tokens',
            'enable_shared_widgets',
            'auto_sync_tokens',
            'default_template_visibility',
            'allowed_sites_for_sharing',
        );

        $current_settings = $this->get_network_settings();

        foreach ( $allowed_keys as $key ) {
            if ( isset( $settings[ $key ] ) ) {
                $current_settings[ $key ] = $settings[ $key ];
            }
        }

        update_site_option( self::NETWORK_OPTION_KEY, $current_settings );

        return rest_ensure_response( array(
            'success'  => true,
            'settings' => $current_settings,
        ) );
    }

    /**
     * Obtiene los sitios de la red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_network_sites( $request ) {
        $sites = get_sites( array(
            'number' => 100,
            'public' => 1,
        ) );

        $sites_data = array();

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $vbp_pages_count = wp_count_posts( 'flavor_landing' );
            $templates_count = $this->count_site_templates( $site->blog_id );

            $sites_data[] = array(
                'id'              => $site->blog_id,
                'name'            => get_bloginfo( 'name' ),
                'url'             => get_home_url(),
                'admin_url'       => admin_url(),
                'vbp_pages_count' => isset( $vbp_pages_count->publish ) ? $vbp_pages_count->publish : 0,
                'templates_count' => $templates_count,
                'is_main_site'    => is_main_site( $site->blog_id ),
                'registered'      => $site->registered,
                'last_updated'    => $site->last_updated,
            );

            restore_current_blog();
        }

        return rest_ensure_response( $sites_data );
    }

    /**
     * Obtiene los templates compartidos
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_shared_templates( $request ) {
        $templates = $this->get_shared_templates();
        return rest_ensure_response( $templates );
    }

    /**
     * Comparte un template con la red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_share_template( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['template_id'] ) || empty( $params['source_site_id'] ) ) {
            return new WP_Error( 'missing_params', __( 'Parámetros requeridos faltantes', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 400 ) );
        }

        $template_id   = sanitize_text_field( $params['template_id'] );
        $source_site_id = absint( $params['source_site_id'] );
        $name          = sanitize_text_field( $params['name'] ?? '' );
        $description   = sanitize_textarea_field( $params['description'] ?? '' );
        $category      = sanitize_text_field( $params['category'] ?? 'general' );

        // Obtener el template del sitio origen
        switch_to_blog( $source_site_id );
        $template_data = $this->get_template_data( $template_id );
        $source_site_name = get_bloginfo( 'name' );
        restore_current_blog();

        if ( ! $template_data ) {
            return new WP_Error( 'template_not_found', __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        // Crear entrada de template compartido
        $shared_template = array(
            'id'              => 'shared_' . uniqid(),
            'original_id'     => $template_id,
            'source_site_id'  => $source_site_id,
            'source_site_name' => $source_site_name,
            'name'            => $name ?: $template_data['name'],
            'description'     => $description,
            'category'        => $category,
            'data'            => $template_data['data'],
            'thumbnail'       => $template_data['thumbnail'] ?? '',
            'shared_by'       => get_current_user_id(),
            'shared_by_name'  => wp_get_current_user()->display_name,
            'shared_at'       => current_time( 'mysql' ),
            'import_count'    => 0,
        );

        $templates = $this->get_shared_templates();
        $templates[] = $shared_template;
        update_site_option( self::SHARED_TEMPLATES_KEY, $templates );

        return rest_ensure_response( array(
            'success'  => true,
            'template' => $shared_template,
        ) );
    }

    /**
     * Obtiene un template compartido específico
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_shared_template( $request ) {
        $template_id = $request->get_param( 'id' );
        $templates   = $this->get_shared_templates();

        foreach ( $templates as $template ) {
            if ( $template['id'] === $template_id ) {
                return rest_ensure_response( $template );
            }
        }

        return new WP_Error( 'not_found', __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
    }

    /**
     * Elimina un template compartido
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_delete_shared_template( $request ) {
        $template_id = $request->get_param( 'id' );
        $templates   = $this->get_shared_templates();

        $found = false;
        foreach ( $templates as $index => $template ) {
            if ( $template['id'] === $template_id ) {
                unset( $templates[ $index ] );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error( 'not_found', __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        update_site_option( self::SHARED_TEMPLATES_KEY, array_values( $templates ) );

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Importa un template compartido al sitio actual
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_import_shared_template( $request ) {
        $template_id = $request->get_param( 'id' );
        $templates   = $this->get_shared_templates();

        $template = null;
        $template_index = null;
        foreach ( $templates as $index => $templateItem ) {
            if ( $templateItem['id'] === $template_id ) {
                $template = $templateItem;
                $template_index = $index;
                break;
            }
        }

        if ( ! $template ) {
            return new WP_Error( 'not_found', __( 'Template no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        // Importar al sitio actual
        $local_template = array(
            'id'          => 'imported_' . uniqid(),
            'name'        => $template['name'] . ' (Importado)',
            'description' => $template['description'],
            'category'    => $template['category'],
            'data'        => $template['data'],
            'thumbnail'   => $template['thumbnail'],
            'imported_from' => $template['source_site_name'],
            'imported_at' => current_time( 'mysql' ),
        );

        // Guardar en el sitio actual
        $local_templates = get_option( 'vbp_user_templates', array() );
        $local_templates[] = $local_template;
        update_option( 'vbp_user_templates', $local_templates );

        // Incrementar contador de importaciones
        $templates[ $template_index ]['import_count']++;
        update_site_option( self::SHARED_TEMPLATES_KEY, $templates );

        return rest_ensure_response( array(
            'success'  => true,
            'template' => $local_template,
        ) );
    }

    /**
     * Obtiene los design tokens de red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_network_tokens( $request ) {
        $tokens = get_site_option( self::NETWORK_TOKENS_KEY, $this->get_default_network_tokens() );
        return rest_ensure_response( $tokens );
    }

    /**
     * Actualiza los design tokens de red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_update_network_tokens( $request ) {
        $tokens = $request->get_json_params();

        // Validar estructura
        $validated_tokens = $this->validate_tokens( $tokens );

        update_site_option( self::NETWORK_TOKENS_KEY, $validated_tokens );

        return rest_ensure_response( array(
            'success' => true,
            'tokens'  => $validated_tokens,
        ) );
    }

    /**
     * Sincroniza los tokens de red a todos los sitios
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_sync_tokens_to_sites( $request ) {
        $params = $request->get_json_params();
        $site_ids = $params['site_ids'] ?? array(); // Array vacío = todos los sitios
        $merge_mode = $params['merge_mode'] ?? 'override'; // 'override' o 'merge'

        $network_tokens = get_site_option( self::NETWORK_TOKENS_KEY, array() );

        if ( empty( $site_ids ) ) {
            $sites = get_sites( array( 'number' => 100 ) );
            $site_ids = wp_list_pluck( $sites, 'blog_id' );
        }

        $results = array();

        foreach ( $site_ids as $site_id ) {
            switch_to_blog( $site_id );

            if ( 'merge' === $merge_mode ) {
                $local_tokens = get_option( 'vbp_design_tokens', array() );
                $merged_tokens = array_merge( $local_tokens, $network_tokens );
                update_option( 'vbp_design_tokens', $merged_tokens );
            } else {
                update_option( 'vbp_design_tokens', $network_tokens );
            }

            $results[] = array(
                'site_id'   => $site_id,
                'site_name' => get_bloginfo( 'name' ),
                'success'   => true,
            );

            restore_current_blog();
        }

        return rest_ensure_response( array(
            'success' => true,
            'results' => $results,
            'synced_count' => count( $results ),
        ) );
    }

    /**
     * Obtiene los widgets compartidos
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_shared_widgets( $request ) {
        $widgets = get_site_option( 'vbp_shared_widgets', array() );
        return rest_ensure_response( $widgets );
    }

    /**
     * Comparte un widget con la red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_share_widget( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['widget_id'] ) || empty( $params['source_site_id'] ) ) {
            return new WP_Error( 'missing_params', __( 'Parámetros requeridos faltantes', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 400 ) );
        }

        $widget_id = absint( $params['widget_id'] );
        $source_site_id = absint( $params['source_site_id'] );

        // Obtener el widget del sitio origen
        switch_to_blog( $source_site_id );
        $widget_post = get_post( $widget_id );
        $widget_data = get_post_meta( $widget_id, '_vbp_global_widget_data', true );
        $source_site_name = get_bloginfo( 'name' );
        restore_current_blog();

        if ( ! $widget_post || ! $widget_data ) {
            return new WP_Error( 'widget_not_found', __( 'Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        $shared_widget = array(
            'id'              => 'shared_widget_' . uniqid(),
            'original_id'     => $widget_id,
            'source_site_id'  => $source_site_id,
            'source_site_name' => $source_site_name,
            'title'           => $widget_post->post_title,
            'type'            => get_post_meta( $widget_id, '_vbp_widget_type', true ),
            'category'        => get_post_meta( $widget_id, '_vbp_widget_category', true ) ?: 'general',
            'data'            => $widget_data,
            'shared_by'       => get_current_user_id(),
            'shared_at'       => current_time( 'mysql' ),
        );

        $widgets = get_site_option( 'vbp_shared_widgets', array() );
        $widgets[] = $shared_widget;
        update_site_option( 'vbp_shared_widgets', $widgets );

        return rest_ensure_response( array(
            'success' => true,
            'widget'  => $shared_widget,
        ) );
    }

    /**
     * Obtiene estadísticas de la red
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_network_stats( $request ) {
        $sites = get_sites( array( 'number' => 100 ) );

        $total_vbp_pages = 0;
        $total_templates = 0;
        $total_widgets = 0;
        $active_sites = 0;

        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $vbp_pages = wp_count_posts( 'flavor_landing' );
            $total_vbp_pages += isset( $vbp_pages->publish ) ? $vbp_pages->publish : 0;

            $templates = get_option( 'vbp_user_templates', array() );
            $total_templates += count( $templates );

            $widgets_query = new WP_Query( array(
                'post_type'      => 'vbp_global_widget',
                'posts_per_page' => 100,
                'post_status'    => 'publish',
                'fields'         => 'ids',
            ) );
            $total_widgets += $widgets_query->found_posts;

            if ( $vbp_pages->publish > 0 ) {
                $active_sites++;
            }

            restore_current_blog();
        }

        $shared_templates = $this->get_shared_templates();
        $shared_widgets = get_site_option( 'vbp_shared_widgets', array() );

        return rest_ensure_response( array(
            'total_sites'        => count( $sites ),
            'active_sites'       => $active_sites,
            'total_vbp_pages'    => $total_vbp_pages,
            'total_templates'    => $total_templates,
            'total_widgets'      => $total_widgets,
            'shared_templates'   => count( $shared_templates ),
            'shared_widgets'     => count( $shared_widgets ),
            'network_tokens'     => ! empty( get_site_option( self::NETWORK_TOKENS_KEY ) ),
        ) );
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Obtiene la configuración de red
     *
     * @return array
     */
    public function get_network_settings() {
        $defaults = array(
            'enable_shared_templates'    => true,
            'enable_network_tokens'      => true,
            'enable_shared_widgets'      => true,
            'auto_sync_tokens'           => false,
            'default_template_visibility' => 'all', // 'all', 'selected', 'none'
            'allowed_sites_for_sharing'  => array(),
        );

        $settings = get_site_option( self::NETWORK_OPTION_KEY, array() );
        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Obtiene las características habilitadas
     *
     * @return array
     */
    public function get_enabled_features() {
        $settings = $this->get_network_settings();

        return array(
            'shared_templates' => $settings['enable_shared_templates'],
            'network_tokens'   => $settings['enable_network_tokens'],
            'shared_widgets'   => $settings['enable_shared_widgets'],
            'auto_sync'        => $settings['auto_sync_tokens'],
        );
    }

    /**
     * Obtiene los templates compartidos
     *
     * @return array
     */
    public function get_shared_templates() {
        return get_site_option( self::SHARED_TEMPLATES_KEY, array() );
    }

    /**
     * Cuenta los templates de un sitio
     *
     * @param int $site_id ID del sitio.
     * @return int
     */
    private function count_site_templates( $site_id ) {
        $templates = get_option( 'vbp_user_templates', array() );
        return count( $templates );
    }

    /**
     * Obtiene los datos de un template
     *
     * @param string $template_id ID del template.
     * @return array|null
     */
    private function get_template_data( $template_id ) {
        $templates = get_option( 'vbp_user_templates', array() );

        foreach ( $templates as $template ) {
            if ( $template['id'] === $template_id ) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Tokens de red por defecto
     *
     * @return array
     */
    private function get_default_network_tokens() {
        return array(
            'colors'     => array(
                'primary'   => '#6366f1',
                'secondary' => '#8b5cf6',
                'accent'    => '#f59e0b',
                'success'   => '#10b981',
                'warning'   => '#f59e0b',
                'error'     => '#ef4444',
            ),
            'typography' => array(
                'font-family-base'    => 'Inter, system-ui, sans-serif',
                'font-family-heading' => 'Inter, system-ui, sans-serif',
                'font-size-base'      => '16px',
            ),
            'spacing'    => array(
                'unit' => '8px',
            ),
            'borders'    => array(
                'radius-base' => '8px',
            ),
        );
    }

    /**
     * Valida los tokens
     *
     * @param array $tokens Tokens a validar.
     * @return array
     */
    private function validate_tokens( $tokens ) {
        $validated = array();

        if ( isset( $tokens['colors'] ) && is_array( $tokens['colors'] ) ) {
            $validated['colors'] = array();
            foreach ( $tokens['colors'] as $key => $value ) {
                $validated['colors'][ sanitize_key( $key ) ] = sanitize_hex_color( $value ) ?: $value;
            }
        }

        if ( isset( $tokens['typography'] ) && is_array( $tokens['typography'] ) ) {
            $validated['typography'] = array();
            foreach ( $tokens['typography'] as $key => $value ) {
                $validated['typography'][ sanitize_key( $key ) ] = sanitize_text_field( $value );
            }
        }

        if ( isset( $tokens['spacing'] ) && is_array( $tokens['spacing'] ) ) {
            $validated['spacing'] = array();
            foreach ( $tokens['spacing'] as $key => $value ) {
                $validated['spacing'][ sanitize_key( $key ) ] = sanitize_text_field( $value );
            }
        }

        if ( isset( $tokens['borders'] ) && is_array( $tokens['borders'] ) ) {
            $validated['borders'] = array();
            foreach ( $tokens['borders'] as $key => $value ) {
                $validated['borders'][ sanitize_key( $key ) ] = sanitize_text_field( $value );
            }
        }

        return $validated;
    }

    /**
     * Combina tokens de red con tokens locales
     *
     * @param array $tokens Tokens locales.
     * @return array
     */
    public function merge_network_tokens( $tokens ) {
        $settings = $this->get_network_settings();

        if ( ! $settings['enable_network_tokens'] ) {
            return $tokens;
        }

        $network_tokens = get_site_option( self::NETWORK_TOKENS_KEY, array() );

        if ( empty( $network_tokens ) ) {
            return $tokens;
        }

        // Los tokens locales tienen prioridad sobre los de red
        return array_replace_recursive( $network_tokens, $tokens );
    }

    /**
     * Combina templates compartidos con templates locales
     *
     * @param array $templates Templates locales.
     * @return array
     */
    public function merge_shared_templates( $templates ) {
        $settings = $this->get_network_settings();

        if ( ! $settings['enable_shared_templates'] ) {
            return $templates;
        }

        $shared_templates = $this->get_shared_templates();

        if ( empty( $shared_templates ) ) {
            return $templates;
        }

        // Marcar templates compartidos
        foreach ( $shared_templates as &$template ) {
            $template['is_shared'] = true;
        }

        return array_merge( $templates, $shared_templates );
    }

    /**
     * Sincroniza tokens cuando se actualizan (si auto-sync está habilitado)
     *
     * @param mixed $old_value Valor anterior.
     * @param mixed $new_value Nuevo valor.
     */
    public function maybe_sync_tokens_to_network( $old_value, $new_value ) {
        if ( ! is_main_site() ) {
            return;
        }

        $settings = $this->get_network_settings();

        if ( $settings['auto_sync_tokens'] ) {
            update_site_option( self::NETWORK_TOKENS_KEY, $new_value );
        }
    }

    // ==========================================
    // ADMIN DE RED
    // ==========================================

    /**
     * Añade el menú de administración de red
     */
    public function add_network_admin_menu() {
        add_submenu_page(
            'settings.php',
            __( 'Visual Builder Pro - Red', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'VBP Red', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'manage_network_options',
            'vbp-network-settings',
            array( $this, 'render_network_admin_page' )
        );
    }

    /**
     * Renderiza la página de admin de red
     */
    public function render_network_admin_page() {
        $settings = $this->get_network_settings();
        ?>
        <div class="wrap" x-data="vbpNetworkAdmin()">
            <h1><?php esc_html_e( 'Visual Builder Pro - Configuración de Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h1>

            <div class="vbp-network-admin">
                <!-- Tabs -->
                <nav class="nav-tab-wrapper">
                    <a href="#" class="nav-tab" :class="{ 'nav-tab-active': activeTab === 'overview' }" @click.prevent="activeTab = 'overview'"><?php esc_html_e( 'Resumen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                    <a href="#" class="nav-tab" :class="{ 'nav-tab-active': activeTab === 'settings' }" @click.prevent="activeTab = 'settings'"><?php esc_html_e( 'Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                    <a href="#" class="nav-tab" :class="{ 'nav-tab-active': activeTab === 'templates' }" @click.prevent="activeTab = 'templates'"><?php esc_html_e( 'Templates Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                    <a href="#" class="nav-tab" :class="{ 'nav-tab-active': activeTab === 'tokens' }" @click.prevent="activeTab = 'tokens'"><?php esc_html_e( 'Design Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                    <a href="#" class="nav-tab" :class="{ 'nav-tab-active': activeTab === 'sites' }" @click.prevent="activeTab = 'sites'"><?php esc_html_e( 'Sitios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                </nav>

                <!-- Tab Content -->
                <div class="vbp-network-content">
                    <!-- Overview Tab -->
                    <div x-show="activeTab === 'overview'" class="vbp-network-panel">
                        <h2><?php esc_html_e( 'Estadísticas de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                        <div class="vbp-stats-grid" x-show="stats">
                            <div class="vbp-stat-card">
                                <span class="vbp-stat-value" x-text="stats.total_sites"></span>
                                <span class="vbp-stat-label"><?php esc_html_e( 'Sitios Totales', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="vbp-stat-card">
                                <span class="vbp-stat-value" x-text="stats.active_sites"></span>
                                <span class="vbp-stat-label"><?php esc_html_e( 'Sitios con VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="vbp-stat-card">
                                <span class="vbp-stat-value" x-text="stats.total_vbp_pages"></span>
                                <span class="vbp-stat-label"><?php esc_html_e( 'Páginas VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="vbp-stat-card">
                                <span class="vbp-stat-value" x-text="stats.shared_templates"></span>
                                <span class="vbp-stat-label"><?php esc_html_e( 'Templates Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </div>
                        </div>
                        <p x-show="!stats" class="description"><?php esc_html_e( 'Cargando estadísticas...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                    </div>

                    <!-- Settings Tab -->
                    <div x-show="activeTab === 'settings'" class="vbp-network-panel">
                        <h2><?php esc_html_e( 'Configuración de Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Templates Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" x-model="settings.enable_shared_templates">
                                        <?php esc_html_e( 'Permitir compartir templates entre sitios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Design Tokens de Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" x-model="settings.enable_network_tokens">
                                        <?php esc_html_e( 'Habilitar design tokens a nivel de red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Widgets Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" x-model="settings.enable_shared_widgets">
                                        <?php esc_html_e( 'Permitir compartir widgets globales entre sitios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Auto-sincronización', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" x-model="settings.auto_sync_tokens">
                                        <?php esc_html_e( 'Sincronizar tokens del sitio principal a la red automáticamente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="button" class="button button-primary" @click="saveSettings()" :disabled="isSaving">
                                <span x-show="!isSaving"><?php esc_html_e( 'Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                <span x-show="isSaving"><?php esc_html_e( 'Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </button>
                        </p>
                    </div>

                    <!-- Templates Tab -->
                    <div x-show="activeTab === 'templates'" class="vbp-network-panel">
                        <h2><?php esc_html_e( 'Templates Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                        <div x-show="sharedTemplates.length === 0" class="notice notice-info">
                            <p><?php esc_html_e( 'No hay templates compartidos en la red.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        </div>
                        <table class="wp-list-table widefat fixed striped" x-show="sharedTemplates.length > 0">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Sitio Origen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Importaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="template in sharedTemplates" :key="template.id">
                                    <tr>
                                        <td x-text="template.name"></td>
                                        <td x-text="template.source_site_name"></td>
                                        <td x-text="template.category"></td>
                                        <td x-text="template.import_count"></td>
                                        <td>
                                            <button class="button button-small" @click="deleteSharedTemplate(template.id)"><?php esc_html_e( 'Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tokens Tab -->
                    <div x-show="activeTab === 'tokens'" class="vbp-network-panel">
                        <h2><?php esc_html_e( 'Design Tokens de Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Estos tokens se aplicarán como base en todos los sitios de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>

                        <h3><?php esc_html_e( 'Colores', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                        <table class="form-table">
                            <template x-for="(value, key) in networkTokens.colors" :key="'color-' + key">
                                <tr>
                                    <th scope="row" x-text="key"></th>
                                    <td>
                                        <input type="color" x-model="networkTokens.colors[key]" style="height: 38px; width: 100px;">
                                        <input type="text" x-model="networkTokens.colors[key]" style="width: 100px; margin-left: 8px;">
                                    </td>
                                </tr>
                            </template>
                        </table>

                        <p class="submit">
                            <button type="button" class="button button-primary" @click="saveNetworkTokens()" :disabled="isSaving">
                                <?php esc_html_e( 'Guardar Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                            <button type="button" class="button" @click="syncTokensToSites()" :disabled="isSyncing">
                                <span x-show="!isSyncing"><?php esc_html_e( 'Sincronizar a Todos los Sitios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                                <span x-show="isSyncing"><?php esc_html_e( 'Sincronizando...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </button>
                        </p>
                    </div>

                    <!-- Sites Tab -->
                    <div x-show="activeTab === 'sites'" class="vbp-network-panel">
                        <h2><?php esc_html_e( 'Sitios de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'URL', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Páginas VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Templates', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                    <th><?php esc_html_e( 'Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="site in sites" :key="site.id">
                                    <tr>
                                        <td>
                                            <strong x-text="site.name"></strong>
                                            <span x-show="site.is_main_site" class="dashicons dashicons-star-filled" title="<?php esc_attr_e( 'Sitio principal', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"></span>
                                        </td>
                                        <td><a :href="site.url" target="_blank" x-text="site.url"></a></td>
                                        <td x-text="site.vbp_pages_count"></td>
                                        <td x-text="site.templates_count"></td>
                                        <td>
                                            <a :href="site.admin_url + 'admin.php?page=vbp-landing-list'" class="button button-small" target="_blank"><?php esc_html_e( 'Abrir VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vbpNetworkAdmin', () => ({
                activeTab: 'overview',
                stats: null,
                settings: <?php echo wp_json_encode( $settings ); ?>,
                sharedTemplates: [],
                networkTokens: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#f59e0b',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444'
                    }
                },
                sites: [],
                isSaving: false,
                isSyncing: false,

                init() {
                    this.loadStats();
                    this.loadSharedTemplates();
                    this.loadNetworkTokens();
                    this.loadSites();
                },

                async loadStats() {
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/stats' ) ); ?>', {
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                        });
                        this.stats = await response.json();
                    } catch (e) {
                        console.error('Error loading stats:', e);
                    }
                },

                async loadSharedTemplates() {
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/templates' ) ); ?>', {
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                        });
                        this.sharedTemplates = await response.json();
                    } catch (e) {
                        console.error('Error loading templates:', e);
                    }
                },

                async loadNetworkTokens() {
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/tokens' ) ); ?>', {
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                        });
                        const tokens = await response.json();
                        if (tokens.colors) {
                            this.networkTokens = tokens;
                        }
                    } catch (e) {
                        console.error('Error loading tokens:', e);
                    }
                },

                async loadSites() {
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/sites' ) ); ?>', {
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                        });
                        this.sites = await response.json();
                    } catch (e) {
                        console.error('Error loading sites:', e);
                    }
                },

                async saveSettings() {
                    this.isSaving = true;
                    try {
                        await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/settings' ) ); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                            },
                            body: JSON.stringify(this.settings)
                        });
                        alert('<?php echo esc_js( __( 'Configuración guardada', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    }
                    this.isSaving = false;
                },

                async saveNetworkTokens() {
                    this.isSaving = true;
                    try {
                        await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/tokens' ) ); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                            },
                            body: JSON.stringify(this.networkTokens)
                        });
                        alert('<?php echo esc_js( __( 'Tokens guardados', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    }
                    this.isSaving = false;
                },

                async syncTokensToSites() {
                    if (!confirm('<?php echo esc_js( __( '¿Sincronizar tokens a todos los sitios de la red?', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>')) {
                        return;
                    }
                    this.isSyncing = true;
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/tokens/sync' ) ); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                            },
                            body: JSON.stringify({ merge_mode: 'override' })
                        });
                        const result = await response.json();
                        alert('<?php echo esc_js( __( 'Sincronizado a', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?> ' + result.synced_count + ' <?php echo esc_js( __( 'sitios', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Error al sincronizar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    }
                    this.isSyncing = false;
                },

                async deleteSharedTemplate(id) {
                    if (!confirm('<?php echo esc_js( __( '¿Eliminar este template compartido?', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>')) {
                        return;
                    }
                    try {
                        await fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/multisite/templates/' ) ); ?>' + id, {
                            method: 'DELETE',
                            headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                        });
                        this.loadSharedTemplates();
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
                    }
                }
            }));
        });
        </script>

        <style>
        .vbp-network-admin {
            margin-top: 20px;
        }
        .vbp-network-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: none;
            padding: 20px;
        }
        .vbp-network-panel {
            min-height: 300px;
        }
        .vbp-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .vbp-stat-card {
            background: #f0f0f1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .vbp-stat-value {
            display: block;
            font-size: 32px;
            font-weight: 600;
            color: #1e1e1e;
        }
        .vbp-stat-label {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: #646970;
        }
        </style>
        <?php
    }

    /**
     * Carga assets en admin de red
     *
     * @param string $hook_suffix Hook suffix.
     */
    public function enqueue_network_admin_assets( $hook_suffix ) {
        if ( 'settings_page_vbp-network-settings' !== $hook_suffix ) {
            return;
        }

        // Alpine.js
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            array(),
            '3.14.0',
            true
        );
    }
}
