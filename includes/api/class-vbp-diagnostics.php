<?php
/**
 * API de Diagnóstico para VBP (Visual Builder Pro)
 *
 * Proporciona endpoints para verificar el estado del sistema VBP,
 * diagnosticar problemas de permalinks y templates.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para diagnóstico del sistema VBP
 */
class Flavor_VBP_Diagnostics {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Diagnostics|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Diagnostics
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Estado general del sistema VBP
        register_rest_route( self::NAMESPACE, '/diagnostics/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_status' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // Verificar configuración de permalinks
        register_rest_route( self::NAMESPACE, '/diagnostics/permalinks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'check_permalinks' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // Listar templates disponibles
        register_rest_route( self::NAMESPACE, '/diagnostics/templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_templates' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // Arreglar permalinks (requiere autenticación)
        register_rest_route( self::NAMESPACE, '/diagnostics/fix-permalinks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'fix_permalinks' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // Verificar landing específica
        register_rest_route( self::NAMESPACE, '/diagnostics/landing/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'diagnose_landing' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // Listar todas las landings con su estado
        register_rest_route( self::NAMESPACE, '/diagnostics/landings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_landings_status' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );
    }

    /**
     * Verifica permisos de administrador
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function check_admin_permission( $request ) {
        $api_key = flavor_get_vbp_api_key_from_request( $request );
        if ( flavor_check_vbp_automation_access( $api_key, 'diagnostics_admin' ) ) {
            return true;
        }

        // Verificar si es usuario autenticado con permisos
        return current_user_can( 'manage_options' );
    }

    /**
     * Obtiene el estado general del sistema VBP
     *
     * @return WP_REST_Response
     */
    public function get_status() {
        $status = array(
            'timestamp'       => current_time( 'mysql' ),
            'wordpress'       => array(
                'version'     => get_bloginfo( 'version' ),
                'site_url'    => get_site_url(),
                'home_url'    => get_home_url(),
                'permalink'   => get_option( 'permalink_structure' ),
            ),
            'vbp'             => array(
                'post_type_registered'   => post_type_exists( 'flavor_landing' ),
                'post_type_public'       => $this->check_post_type_public(),
                'rewrite_rules_ok'       => $this->check_rewrite_rules(),
                'vbp_canvas_loaded'      => class_exists( 'Flavor_VBP_Canvas' ),
                'vbp_block_library'      => class_exists( 'Flavor_VBP_Block_Library' ),
                'vbp_claude_api'         => class_exists( 'Flavor_VBP_Claude_API' ),
                'visual_builder'         => class_exists( 'Flavor_Visual_Builder' ),
            ),
            'templates'       => $this->get_templates_status(),
            'assets'          => $this->get_assets_status(),
            'landings'        => $this->get_landings_summary(),
        );

        // Determinar salud general
        $status['health'] = $this->calculate_health( $status );

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * Verifica si el post type es público y queryable
     *
     * @return array
     */
    private function check_post_type_public() {
        $post_type_obj = get_post_type_object( 'flavor_landing' );

        if ( ! $post_type_obj ) {
            return array(
                'exists'            => false,
                'public'            => false,
                'publicly_queryable' => false,
            );
        }

        return array(
            'exists'            => true,
            'public'            => $post_type_obj->public,
            'publicly_queryable' => $post_type_obj->publicly_queryable,
            'rewrite'           => $post_type_obj->rewrite,
        );
    }

    /**
     * Verifica las rewrite rules para landings
     *
     * @return array
     */
    private function check_rewrite_rules() {
        global $wp_rewrite;

        $rules = get_option( 'rewrite_rules', array() );
        $landing_rules = array();

        if ( is_array( $rules ) ) {
            foreach ( $rules as $pattern => $rewrite ) {
                if ( strpos( $pattern, 'landing' ) !== false || strpos( $rewrite, 'flavor_landing' ) !== false ) {
                    $landing_rules[ $pattern ] = $rewrite;
                }
            }
        }

        return array(
            'has_rules'         => ! empty( $landing_rules ),
            'rules_count'       => count( $landing_rules ),
            'rules'             => $landing_rules,
            'using_permalinks'  => $wp_rewrite->using_permalinks(),
            'permalink_structure' => get_option( 'permalink_structure' ),
        );
    }

    /**
     * Obtiene estado de templates
     *
     * @return array
     */
    private function get_templates_status() {
        $plugin_path = FLAVOR_CHAT_IA_PATH;
        $theme_path = get_template_directory();

        $templates = array(
            'single-flavor_landing' => array(
                'plugin_path' => $plugin_path . 'templates/single-flavor_landing.php',
                'theme_path'  => $theme_path . '/single-flavor_landing.php',
            ),
            'landing-template' => array(
                'plugin_path' => $plugin_path . 'includes/visual-builder/views/landing-template.php',
            ),
            'vbp-canvas' => array(
                'plugin_path' => $plugin_path . 'includes/visual-builder-pro/views/canvas.php',
            ),
        );

        $status = array();
        foreach ( $templates as $name => $paths ) {
            $found = false;
            $location = '';

            // Verificar en tema primero
            if ( isset( $paths['theme_path'] ) && file_exists( $paths['theme_path'] ) ) {
                $found = true;
                $location = 'theme';
            } elseif ( isset( $paths['plugin_path'] ) && file_exists( $paths['plugin_path'] ) ) {
                $found = true;
                $location = 'plugin';
            }

            $status[ $name ] = array(
                'exists'   => $found,
                'location' => $location,
                'path'     => $found ? ( $location === 'theme' ? $paths['theme_path'] : $paths['plugin_path'] ) : '',
            );
        }

        return $status;
    }

    /**
     * Obtiene estado de assets CSS/JS
     *
     * @return array
     */
    private function get_assets_status() {
        $plugin_path = FLAVOR_CHAT_IA_PATH;

        $assets = array(
            'animations_css' => 'includes/visual-builder-pro/assets/css/animations.css',
            'vbp_frontend_css' => 'includes/visual-builder-pro/assets/css/frontend.css',
            'vbp_canvas_js' => 'includes/visual-builder-pro/assets/js/canvas.js',
            'visual_builder_css' => 'includes/visual-builder/assets/css/visual-builder.css',
            'visual_builder_frontend_css' => 'includes/visual-builder/assets/css/visual-builder-frontend.css',
        );

        $status = array();
        foreach ( $assets as $name => $relative_path ) {
            $full_path = $plugin_path . $relative_path;
            $status[ $name ] = array(
                'exists' => file_exists( $full_path ),
                'path'   => $relative_path,
                'size'   => file_exists( $full_path ) ? filesize( $full_path ) : 0,
            );
        }

        return $status;
    }

    /**
     * Obtiene resumen de landings
     *
     * @return array
     */
    private function get_landings_summary() {
        $counts = wp_count_posts( 'flavor_landing' );

        return array(
            'total'     => isset( $counts->publish ) ? (int) $counts->publish : 0,
            'draft'     => isset( $counts->draft ) ? (int) $counts->draft : 0,
            'pending'   => isset( $counts->pending ) ? (int) $counts->pending : 0,
            'private'   => isset( $counts->private ) ? (int) $counts->private : 0,
        );
    }

    /**
     * Calcula salud general del sistema
     *
     * @param array $status Estado del sistema.
     * @return array
     */
    private function calculate_health( $status ) {
        $issues = array();
        $warnings = array();

        // Verificar post type
        if ( ! $status['vbp']['post_type_registered'] ) {
            $issues[] = 'Post type flavor_landing no registrado';
        }

        // Verificar rewrite rules
        if ( ! $status['vbp']['rewrite_rules_ok']['has_rules'] ) {
            $issues[] = 'No hay rewrite rules para landings. Ejecutar flush_rewrite_rules()';
        }

        // Verificar clases principales
        if ( ! $status['vbp']['vbp_canvas_loaded'] ) {
            $warnings[] = 'VBP Canvas no cargado';
        }

        if ( ! $status['vbp']['vbp_block_library'] ) {
            $warnings[] = 'VBP Block Library no cargado';
        }

        // Verificar templates
        $single_template = $status['templates']['single-flavor_landing'] ?? array();
        if ( empty( $single_template['exists'] ) ) {
            $warnings[] = 'Template single-flavor_landing.php no encontrado';
        }

        // Verificar permalinks
        if ( empty( $status['wordpress']['permalink'] ) ) {
            $issues[] = 'Permalinks están en modo "Plain". Cambiar a una estructura personalizada.';
        }

        // Determinar estado
        if ( ! empty( $issues ) ) {
            $health_status = 'critical';
        } elseif ( ! empty( $warnings ) ) {
            $health_status = 'warning';
        } else {
            $health_status = 'healthy';
        }

        return array(
            'status'   => $health_status,
            'issues'   => $issues,
            'warnings' => $warnings,
        );
    }

    /**
     * Verifica configuración de permalinks
     *
     * @return WP_REST_Response
     */
    public function check_permalinks() {
        global $wp_rewrite;

        $result = array(
            'permalink_structure' => get_option( 'permalink_structure' ),
            'using_permalinks'    => $wp_rewrite->using_permalinks(),
            'rewrite_rules'       => $this->check_rewrite_rules(),
            'test_urls'           => $this->generate_test_urls(),
            'recommendations'     => array(),
        );

        // Generar recomendaciones
        if ( empty( $result['permalink_structure'] ) ) {
            $result['recommendations'][] = array(
                'type'    => 'error',
                'message' => 'Los permalinks están en modo "Plain". Ir a Ajustes > Enlaces permanentes y seleccionar otra estructura.',
            );
        }

        if ( ! $result['rewrite_rules']['has_rules'] ) {
            $result['recommendations'][] = array(
                'type'    => 'error',
                'message' => 'No hay rewrite rules para flavor_landing. Usar POST /diagnostics/fix-permalinks para regenerar.',
            );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Genera URLs de prueba para landings existentes
     *
     * @return array
     */
    private function generate_test_urls() {
        $landings = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
        ) );

        $urls = array();
        foreach ( $landings as $landing ) {
            $urls[] = array(
                'id'        => $landing->ID,
                'title'     => $landing->post_title,
                'permalink' => get_permalink( $landing->ID ),
                'expected'  => home_url( '/landing/' . $landing->post_name . '/' ),
            );
        }

        return $urls;
    }

    /**
     * Lista templates disponibles y su estado
     *
     * @return WP_REST_Response
     */
    public function list_templates() {
        return new WP_REST_Response( array(
            'templates' => $this->get_templates_status(),
            'assets'    => $this->get_assets_status(),
        ), 200 );
    }

    /**
     * Intenta arreglar los permalinks
     *
     * @return WP_REST_Response
     */
    public function fix_permalinks() {
        // Forzar flush de rewrite rules
        flush_rewrite_rules( true );

        // Verificar que se aplicó
        $rewrite_status = $this->check_rewrite_rules();

        // Verificar que el post type está registrado correctamente
        $post_type_obj = get_post_type_object( 'flavor_landing' );
        $post_type_ok = $post_type_obj && $post_type_obj->publicly_queryable;

        // Regenerar reglas del post type si es necesario
        if ( ! $rewrite_status['has_rules'] && $post_type_ok ) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules( true );
        }

        // Verificar de nuevo
        $rewrite_status_after = $this->check_rewrite_rules();

        $success = $rewrite_status_after['has_rules'];

        return new WP_REST_Response( array(
            'success'        => $success,
            'message'        => $success
                ? 'Permalinks regenerados correctamente'
                : 'No se pudieron regenerar los permalinks. Verificar configuración del post type.',
            'before'         => $rewrite_status,
            'after'          => $rewrite_status_after,
            'post_type_ok'   => $post_type_ok,
        ), $success ? 200 : 500 );
    }

    /**
     * Diagnostica una landing específica
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function diagnose_landing( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array(
                'error'   => 'Landing no encontrada',
                'post_id' => $post_id,
            ), 404 );
        }

        // Obtener datos VBP
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );
        $vb_data = get_post_meta( $post_id, '_flavor_vb_data', true );

        // Contar elementos
        $elements_count = 0;
        if ( ! empty( $vbp_data['elements'] ) ) {
            $elements_count = count( $vbp_data['elements'] );
        } elseif ( ! empty( $vb_data['content'] ) ) {
            $elements_count = count( $vb_data['content'] );
        }

        // Verificar URL
        $permalink = get_permalink( $post_id );
        $expected_url = home_url( '/landing/' . $post->post_name . '/' );

        // Verificar accesibilidad
        $url_test = $this->test_url_accessibility( $permalink );

        $diagnosis = array(
            'post'       => array(
                'id'          => $post_id,
                'title'       => $post->post_title,
                'status'      => $post->post_status,
                'post_name'   => $post->post_name,
                'post_type'   => $post->post_type,
            ),
            'urls'       => array(
                'permalink'   => $permalink,
                'expected'    => $expected_url,
                'matches'     => $permalink === $expected_url,
                'edit_url'    => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            ),
            'vbp_data'   => array(
                'has_vbp_data'    => ! empty( $vbp_data ),
                'has_vb_data'     => ! empty( $vb_data ),
                'elements_count'  => $elements_count,
                'version'         => $vbp_data['version'] ?? ( $vb_data['version'] ?? 'unknown' ),
            ),
            'accessibility' => $url_test,
            'issues'     => array(),
        );

        // Detectar problemas
        if ( $post->post_status !== 'publish' ) {
            $diagnosis['issues'][] = "La landing está en estado '{$post->post_status}', no 'publish'";
        }

        if ( ! $diagnosis['urls']['matches'] ) {
            $diagnosis['issues'][] = 'La URL no coincide con el patrón esperado';
        }

        if ( $elements_count === 0 ) {
            $diagnosis['issues'][] = 'La landing no tiene elementos VBP';
        }

        if ( ! $url_test['accessible'] && $post->post_status === 'publish' ) {
            $diagnosis['issues'][] = 'La URL no es accesible: ' . ( $url_test['error'] ?? 'Error desconocido' );
        }

        $diagnosis['health'] = empty( $diagnosis['issues'] ) ? 'healthy' : 'issues_found';

        return new WP_REST_Response( $diagnosis, 200 );
    }

    /**
     * Prueba accesibilidad de una URL
     *
     * @param string $url URL a probar.
     * @return array
     */
    private function test_url_accessibility( $url ) {
        $response = wp_remote_head( $url, array(
            'timeout'     => 5,
            'redirection' => 0,
            'sslverify'   => false,
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'accessible' => false,
                'error'      => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $headers = wp_remote_retrieve_headers( $response );
        $location = isset( $headers['location'] ) ? $headers['location'] : '';

        return array(
            'accessible'   => $status_code >= 200 && $status_code < 400,
            'status_code'  => $status_code,
            'redirects_to' => $location,
            'is_redirect'  => $status_code >= 300 && $status_code < 400,
        );
    }

    /**
     * Lista todas las landings con su estado
     *
     * @return WP_REST_Response
     */
    public function list_landings_status() {
        $landings = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => 50,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );

        $result = array();
        foreach ( $landings as $landing ) {
            $vbp_data = get_post_meta( $landing->ID, '_flavor_vbp_data', true );
            $permalink = get_permalink( $landing->ID );

            $result[] = array(
                'id'             => $landing->ID,
                'title'          => $landing->post_title,
                'status'         => $landing->post_status,
                'modified'       => $landing->post_modified,
                'permalink'      => $permalink,
                'elements_count' => count( $vbp_data['elements'] ?? array() ),
                'has_vbp_data'   => ! empty( $vbp_data ),
            );
        }

        return new WP_REST_Response( array(
            'count'    => count( $result ),
            'landings' => $result,
        ), 200 );
    }
}

// Inicializar
Flavor_VBP_Diagnostics::get_instance();
