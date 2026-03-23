<?php
/**
 * REST API para SEO y metadatos desde Claude Code
 *
 * Endpoints para configurar títulos, descripciones, Open Graph, Schema.org.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de SEO
 */
class Flavor_SEO_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_SEO_API|null
     */
    private static $instance = null;

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
     * Meta key prefix
     *
     * @var string
     */
    const META_PREFIX = '_flavor_seo_';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_SEO_API
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
        $this->api_key = $settings['vbp_api_key'] ?? 'flavor-vbp-2024';

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );
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
        // === SEO DE PÁGINAS ===

        // Obtener SEO de una página
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar SEO de una página
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // SEO por lote
        register_rest_route( self::NAMESPACE, '/seo/pages/batch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_batch_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === OPEN GRAPH ===

        // Obtener Open Graph de una página
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/og', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_open_graph' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar Open Graph
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/og', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_open_graph' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === TWITTER CARDS ===

        // Obtener Twitter Cards
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/twitter', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_twitter_card' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar Twitter Cards
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/twitter', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_twitter_card' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === SCHEMA.ORG ===

        // Obtener Schema.org
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/schema', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_schema' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar Schema.org
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/schema', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_schema' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener tipos de Schema disponibles
        register_rest_route( self::NAMESPACE, '/seo/schema-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_schema_types' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === CONFIGURACIÓN GLOBAL ===

        // Obtener configuración SEO global
        register_rest_route( self::NAMESPACE, '/seo/settings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_seo_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración SEO global
        register_rest_route( self::NAMESPACE, '/seo/settings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_seo_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === ANÁLISIS Y SUGERENCIAS ===

        // Analizar SEO de una página
        register_rest_route( self::NAMESPACE, '/seo/analyze/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Generar SEO automáticamente
        register_rest_route( self::NAMESPACE, '/seo/generate/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === SITEMAP ===

        // Obtener configuración del sitemap
        register_rest_route( self::NAMESPACE, '/seo/sitemap', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_sitemap_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración del sitemap
        register_rest_route( self::NAMESPACE, '/seo/sitemap', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_sitemap_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Regenerar sitemap
        register_rest_route( self::NAMESPACE, '/seo/sitemap/regenerate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'regenerate_sitemap' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === ROBOTS.TXT ===

        // Obtener robots.txt
        register_rest_route( self::NAMESPACE, '/seo/robots', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_robots' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar robots.txt
        register_rest_route( self::NAMESPACE, '/seo/robots', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_robots' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === REDIRECTS ===

        // Listar redirects
        register_rest_route( self::NAMESPACE, '/seo/redirects', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_redirects' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear redirect
        register_rest_route( self::NAMESPACE, '/seo/redirects', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_redirect' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar redirect
        register_rest_route( self::NAMESPACE, '/seo/redirects/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_redirect' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === PRESETS ===

        // Obtener presets de SEO
        register_rest_route( self::NAMESPACE, '/seo/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Aplicar preset a una página
        register_rest_route( self::NAMESPACE, '/seo/pages/(?P<id>\d+)/apply-preset', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_preset' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * Obtener SEO de una página
     */
    public function get_page_seo( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Página no encontrada', array( 'status' => 404 ) );
        }

        return rest_ensure_response( array(
            'id' => $post_id,
            'title' => get_the_title( $post_id ),
            'url' => get_permalink( $post_id ),
            'seo' => $this->get_seo_meta( $post_id ),
            'open_graph' => $this->get_og_meta( $post_id ),
            'twitter' => $this->get_twitter_meta( $post_id ),
            'schema' => $this->get_schema_meta( $post_id ),
        ) );
    }

    /**
     * Actualizar SEO de una página
     */
    public function update_page_seo( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Página no encontrada', array( 'status' => 404 ) );
        }

        // Campos SEO básicos
        $seo_fields = array(
            'title' => 'meta_title',
            'description' => 'meta_description',
            'keywords' => 'meta_keywords',
            'canonical' => 'canonical_url',
            'robots' => 'robots',
            'focus_keyword' => 'focus_keyword',
        );

        foreach ( $seo_fields as $param_key => $meta_key ) {
            if ( isset( $params[ $param_key ] ) ) {
                update_post_meta( $post_id, self::META_PREFIX . $meta_key, sanitize_text_field( $params[ $param_key ] ) );
            }
        }

        // Open Graph
        if ( isset( $params['og'] ) && is_array( $params['og'] ) ) {
            $this->save_og_meta( $post_id, $params['og'] );
        }

        // Twitter
        if ( isset( $params['twitter'] ) && is_array( $params['twitter'] ) ) {
            $this->save_twitter_meta( $post_id, $params['twitter'] );
        }

        // Schema
        if ( isset( $params['schema'] ) && is_array( $params['schema'] ) ) {
            $this->save_schema_meta( $post_id, $params['schema'] );
        }

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'seo' => $this->get_seo_meta( $post_id ),
        ) );
    }

    /**
     * Actualizar SEO por lote
     */
    public function update_batch_seo( $request ) {
        $pages = $request->get_param( 'pages' );

        if ( ! is_array( $pages ) ) {
            return new WP_Error( 'invalid_data', 'Se requiere un array de páginas', array( 'status' => 400 ) );
        }

        $results = array();

        foreach ( $pages as $page_data ) {
            if ( ! isset( $page_data['id'] ) ) {
                continue;
            }

            $post_id = absint( $page_data['id'] );
            $post = get_post( $post_id );

            if ( ! $post ) {
                $results[] = array(
                    'id' => $post_id,
                    'success' => false,
                    'error' => 'Página no encontrada',
                );
                continue;
            }

            // Aplicar SEO
            $seo_fields = array( 'title', 'description', 'keywords', 'canonical', 'robots' );
            foreach ( $seo_fields as $field ) {
                if ( isset( $page_data[ $field ] ) ) {
                    update_post_meta( $post_id, self::META_PREFIX . 'meta_' . $field, sanitize_text_field( $page_data[ $field ] ) );
                }
            }

            $results[] = array(
                'id' => $post_id,
                'success' => true,
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'results' => $results,
            'processed' => count( $results ),
        ) );
    }

    /**
     * Obtener Open Graph
     */
    public function get_open_graph( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );

        return rest_ensure_response( array(
            'id' => $post_id,
            'open_graph' => $this->get_og_meta( $post_id ),
        ) );
    }

    /**
     * Actualizar Open Graph
     */
    public function update_open_graph( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $this->save_og_meta( $post_id, $params );

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'open_graph' => $this->get_og_meta( $post_id ),
        ) );
    }

    /**
     * Obtener Twitter Card
     */
    public function get_twitter_card( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );

        return rest_ensure_response( array(
            'id' => $post_id,
            'twitter' => $this->get_twitter_meta( $post_id ),
        ) );
    }

    /**
     * Actualizar Twitter Card
     */
    public function update_twitter_card( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $this->save_twitter_meta( $post_id, $params );

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'twitter' => $this->get_twitter_meta( $post_id ),
        ) );
    }

    /**
     * Obtener Schema.org
     */
    public function get_schema( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );

        return rest_ensure_response( array(
            'id' => $post_id,
            'schema' => $this->get_schema_meta( $post_id ),
        ) );
    }

    /**
     * Actualizar Schema.org
     */
    public function update_schema( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $this->save_schema_meta( $post_id, $params );

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'schema' => $this->get_schema_meta( $post_id ),
        ) );
    }

    /**
     * Obtener tipos de Schema disponibles
     */
    public function get_schema_types( $request ) {
        return rest_ensure_response( array(
            'types' => $this->get_all_schema_types(),
        ) );
    }

    /**
     * Obtener configuración SEO global
     */
    public function get_seo_settings( $request ) {
        $settings = get_option( 'flavor_seo_settings', $this->get_default_seo_settings() );

        return rest_ensure_response( array(
            'settings' => $settings,
            'site_name' => get_bloginfo( 'name' ),
            'site_url' => home_url(),
        ) );
    }

    /**
     * Actualizar configuración SEO global
     */
    public function update_seo_settings( $request ) {
        $params = $request->get_json_params();
        $settings = get_option( 'flavor_seo_settings', $this->get_default_seo_settings() );

        $allowed_fields = array(
            'title_separator',
            'title_format',
            'default_description',
            'default_image',
            'social_profiles',
            'organization_name',
            'organization_logo',
            'organization_type',
            'local_business',
            'enable_schema',
            'enable_og',
            'enable_twitter',
            'twitter_site',
            'facebook_app_id',
            'google_verification',
            'bing_verification',
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

        update_option( 'flavor_seo_settings', $settings );

        return rest_ensure_response( array(
            'success' => true,
            'settings' => $settings,
        ) );
    }

    /**
     * Analizar SEO de una página
     */
    public function analyze_page_seo( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Página no encontrada', array( 'status' => 404 ) );
        }

        $seo = $this->get_seo_meta( $post_id );
        $content = wp_strip_all_tags( $post->post_content );
        $analysis = array(
            'score' => 0,
            'max_score' => 100,
            'issues' => array(),
            'suggestions' => array(),
            'passed' => array(),
        );

        // Análisis de título
        $title = $seo['meta_title'] ?: get_the_title( $post_id );
        $title_length = strlen( $title );

        if ( empty( $title ) ) {
            $analysis['issues'][] = array(
                'type' => 'error',
                'message' => 'Falta el título SEO',
                'field' => 'title',
            );
        } elseif ( $title_length < 30 ) {
            $analysis['issues'][] = array(
                'type' => 'warning',
                'message' => 'El título es muy corto (' . $title_length . ' caracteres). Recomendado: 50-60',
                'field' => 'title',
            );
            $analysis['score'] += 5;
        } elseif ( $title_length > 60 ) {
            $analysis['issues'][] = array(
                'type' => 'warning',
                'message' => 'El título es muy largo (' . $title_length . ' caracteres). Recomendado: 50-60',
                'field' => 'title',
            );
            $analysis['score'] += 5;
        } else {
            $analysis['passed'][] = 'Título con longitud óptima';
            $analysis['score'] += 15;
        }

        // Análisis de descripción
        $description = $seo['meta_description'];
        $desc_length = strlen( $description );

        if ( empty( $description ) ) {
            $analysis['issues'][] = array(
                'type' => 'error',
                'message' => 'Falta la meta descripción',
                'field' => 'description',
            );
            $analysis['suggestions'][] = $this->generate_description_suggestion( $content );
        } elseif ( $desc_length < 120 ) {
            $analysis['issues'][] = array(
                'type' => 'warning',
                'message' => 'La descripción es muy corta (' . $desc_length . ' caracteres). Recomendado: 150-160',
                'field' => 'description',
            );
            $analysis['score'] += 5;
        } elseif ( $desc_length > 160 ) {
            $analysis['issues'][] = array(
                'type' => 'warning',
                'message' => 'La descripción es muy larga (' . $desc_length . ' caracteres). Recomendado: 150-160',
                'field' => 'description',
            );
            $analysis['score'] += 5;
        } else {
            $analysis['passed'][] = 'Meta descripción con longitud óptima';
            $analysis['score'] += 15;
        }

        // Análisis de palabra clave
        $focus_keyword = $seo['focus_keyword'];

        if ( empty( $focus_keyword ) ) {
            $analysis['suggestions'][] = array(
                'type' => 'info',
                'message' => 'Define una palabra clave principal para optimizar el contenido',
                'field' => 'focus_keyword',
            );
        } else {
            // Verificar presencia en título
            if ( stripos( $title, $focus_keyword ) !== false ) {
                $analysis['passed'][] = 'Palabra clave presente en el título';
                $analysis['score'] += 10;
            } else {
                $analysis['issues'][] = array(
                    'type' => 'warning',
                    'message' => "La palabra clave '$focus_keyword' no está en el título",
                    'field' => 'title',
                );
            }

            // Verificar presencia en descripción
            if ( stripos( $description, $focus_keyword ) !== false ) {
                $analysis['passed'][] = 'Palabra clave presente en la descripción';
                $analysis['score'] += 10;
            } else {
                $analysis['issues'][] = array(
                    'type' => 'warning',
                    'message' => "La palabra clave '$focus_keyword' no está en la descripción",
                    'field' => 'description',
                );
            }

            // Verificar densidad en contenido
            $keyword_count = substr_count( strtolower( $content ), strtolower( $focus_keyword ) );
            $word_count = str_word_count( $content );
            $density = $word_count > 0 ? ( $keyword_count / $word_count ) * 100 : 0;

            if ( $density < 0.5 ) {
                $analysis['issues'][] = array(
                    'type' => 'warning',
                    'message' => "Densidad de palabra clave baja ({$density}%). Recomendado: 1-2%",
                    'field' => 'content',
                );
            } elseif ( $density > 3 ) {
                $analysis['issues'][] = array(
                    'type' => 'warning',
                    'message' => "Densidad de palabra clave alta ({$density}%). Puede ser penalizado",
                    'field' => 'content',
                );
            } else {
                $analysis['passed'][] = 'Densidad de palabra clave óptima';
                $analysis['score'] += 10;
            }
        }

        // Análisis de Open Graph
        $og = $this->get_og_meta( $post_id );

        if ( ! empty( $og['image'] ) ) {
            $analysis['passed'][] = 'Imagen Open Graph configurada';
            $analysis['score'] += 10;
        } else {
            $analysis['issues'][] = array(
                'type' => 'info',
                'message' => 'No hay imagen Open Graph para compartir en redes sociales',
                'field' => 'og_image',
            );
        }

        // Análisis de Schema
        $schema = $this->get_schema_meta( $post_id );

        if ( ! empty( $schema['type'] ) ) {
            $analysis['passed'][] = 'Datos estructurados Schema.org configurados';
            $analysis['score'] += 10;
        } else {
            $analysis['suggestions'][] = array(
                'type' => 'info',
                'message' => 'Añade datos estructurados Schema.org para mejorar la visibilidad en Google',
                'field' => 'schema',
            );
        }

        // Longitud del contenido
        $content_length = str_word_count( $content );

        if ( $content_length < 300 ) {
            $analysis['issues'][] = array(
                'type' => 'warning',
                'message' => "Contenido muy corto ({$content_length} palabras). Recomendado: mínimo 300",
                'field' => 'content',
            );
        } elseif ( $content_length >= 1000 ) {
            $analysis['passed'][] = "Contenido extenso ({$content_length} palabras)";
            $analysis['score'] += 10;
        } else {
            $analysis['score'] += 5;
        }

        // Calcular grado
        if ( $analysis['score'] >= 80 ) {
            $analysis['grade'] = 'A';
            $analysis['grade_label'] = 'Excelente';
        } elseif ( $analysis['score'] >= 60 ) {
            $analysis['grade'] = 'B';
            $analysis['grade_label'] = 'Bueno';
        } elseif ( $analysis['score'] >= 40 ) {
            $analysis['grade'] = 'C';
            $analysis['grade_label'] = 'Regular';
        } elseif ( $analysis['score'] >= 20 ) {
            $analysis['grade'] = 'D';
            $analysis['grade_label'] = 'Necesita mejoras';
        } else {
            $analysis['grade'] = 'F';
            $analysis['grade_label'] = 'Pobre';
        }

        return rest_ensure_response( array(
            'id' => $post_id,
            'analysis' => $analysis,
        ) );
    }

    /**
     * Generar SEO automáticamente
     */
    public function generate_seo( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Página no encontrada', array( 'status' => 404 ) );
        }

        $title = get_the_title( $post_id );
        $content = wp_strip_all_tags( $post->post_content );

        // Generar título SEO
        $seo_title = $title;
        $site_name = get_bloginfo( 'name' );
        $settings = get_option( 'flavor_seo_settings', $this->get_default_seo_settings() );
        $separator = $settings['title_separator'] ?? '|';

        if ( strlen( $seo_title ) < 50 && strlen( $seo_title . " $separator $site_name" ) <= 60 ) {
            $seo_title .= " $separator $site_name";
        }

        // Generar descripción
        $description = $this->generate_description_from_content( $content );

        // Generar palabras clave
        $keywords = $this->extract_keywords( $content );

        // Guardar
        update_post_meta( $post_id, self::META_PREFIX . 'meta_title', $seo_title );
        update_post_meta( $post_id, self::META_PREFIX . 'meta_description', $description );
        update_post_meta( $post_id, self::META_PREFIX . 'meta_keywords', implode( ', ', $keywords ) );

        // Generar Open Graph
        $og_image = get_the_post_thumbnail_url( $post_id, 'large' );

        $this->save_og_meta( $post_id, array(
            'title' => $title,
            'description' => $description,
            'image' => $og_image ?: '',
            'type' => 'article',
        ) );

        // Generar Twitter Card
        $this->save_twitter_meta( $post_id, array(
            'card' => 'summary_large_image',
            'title' => $title,
            'description' => $description,
            'image' => $og_image ?: '',
        ) );

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'generated' => array(
                'title' => $seo_title,
                'description' => $description,
                'keywords' => $keywords,
                'og_image' => $og_image,
            ),
            'seo' => $this->get_seo_meta( $post_id ),
        ) );
    }

    /**
     * Obtener configuración del sitemap
     */
    public function get_sitemap_config( $request ) {
        $config = get_option( 'flavor_sitemap_config', $this->get_default_sitemap_config() );

        return rest_ensure_response( array(
            'config' => $config,
            'sitemap_url' => home_url( 'sitemap.xml' ),
        ) );
    }

    /**
     * Actualizar configuración del sitemap
     */
    public function update_sitemap_config( $request ) {
        $params = $request->get_json_params();
        $config = get_option( 'flavor_sitemap_config', $this->get_default_sitemap_config() );

        $allowed_fields = array(
            'enabled',
            'include_posts',
            'include_pages',
            'include_categories',
            'include_tags',
            'include_authors',
            'exclude_post_types',
            'exclude_taxonomies',
            'exclude_ids',
            'max_entries',
            'change_frequency',
            'priority_homepage',
            'priority_posts',
            'priority_pages',
        );

        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                if ( is_array( $params[ $field ] ) ) {
                    $config[ $field ] = array_map( 'sanitize_text_field', $params[ $field ] );
                } elseif ( is_bool( $params[ $field ] ) ) {
                    $config[ $field ] = (bool) $params[ $field ];
                } else {
                    $config[ $field ] = sanitize_text_field( $params[ $field ] );
                }
            }
        }

        update_option( 'flavor_sitemap_config', $config );

        return rest_ensure_response( array(
            'success' => true,
            'config' => $config,
        ) );
    }

    /**
     * Regenerar sitemap
     */
    public function regenerate_sitemap( $request ) {
        // Limpiar caché de rewrite rules para forzar regeneración
        flush_rewrite_rules();

        // Si existe función de regeneración personalizada
        do_action( 'flavor_regenerate_sitemap' );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Sitemap regenerado',
            'sitemap_url' => home_url( 'sitemap.xml' ),
        ) );
    }

    /**
     * Obtener robots.txt
     */
    public function get_robots( $request ) {
        $robots = get_option( 'flavor_robots_txt', $this->get_default_robots() );

        return rest_ensure_response( array(
            'content' => $robots,
            'url' => home_url( 'robots.txt' ),
        ) );
    }

    /**
     * Actualizar robots.txt
     */
    public function update_robots( $request ) {
        $content = $request->get_param( 'content' );

        if ( empty( $content ) ) {
            return new WP_Error( 'empty_content', 'Contenido de robots.txt requerido', array( 'status' => 400 ) );
        }

        update_option( 'flavor_robots_txt', wp_kses_post( $content ) );

        return rest_ensure_response( array(
            'success' => true,
            'content' => get_option( 'flavor_robots_txt' ),
        ) );
    }

    /**
     * Listar redirects
     */
    public function get_redirects( $request ) {
        $redirects = get_option( 'flavor_redirects', array() );

        return rest_ensure_response( array(
            'redirects' => $redirects,
            'total' => count( $redirects ),
        ) );
    }

    /**
     * Crear redirect
     */
    public function create_redirect( $request ) {
        $from = sanitize_text_field( $request->get_param( 'from' ) );
        $to = esc_url_raw( $request->get_param( 'to' ) );
        $type = absint( $request->get_param( 'type' ) ) ?: 301;

        if ( empty( $from ) || empty( $to ) ) {
            return new WP_Error( 'missing_params', 'Se requieren from y to', array( 'status' => 400 ) );
        }

        if ( ! in_array( $type, array( 301, 302, 307, 308 ), true ) ) {
            $type = 301;
        }

        $redirects = get_option( 'flavor_redirects', array() );

        $redirect_id = count( $redirects ) + 1;

        $redirects[] = array(
            'id' => $redirect_id,
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'created' => current_time( 'mysql' ),
        );

        update_option( 'flavor_redirects', $redirects );

        return rest_ensure_response( array(
            'success' => true,
            'redirect' => end( $redirects ),
        ) );
    }

    /**
     * Eliminar redirect
     */
    public function delete_redirect( $request ) {
        $redirect_id = absint( $request->get_param( 'id' ) );
        $redirects = get_option( 'flavor_redirects', array() );

        $found = false;
        foreach ( $redirects as $key => $redirect ) {
            if ( $redirect['id'] === $redirect_id ) {
                unset( $redirects[ $key ] );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error( 'redirect_not_found', 'Redirect no encontrado', array( 'status' => 404 ) );
        }

        update_option( 'flavor_redirects', array_values( $redirects ) );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Redirect eliminado',
        ) );
    }

    /**
     * Obtener presets de SEO
     */
    public function get_presets( $request ) {
        return rest_ensure_response( array(
            'presets' => $this->get_all_presets(),
        ) );
    }

    /**
     * Aplicar preset a una página
     */
    public function apply_preset( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $preset_id = sanitize_key( $request->get_param( 'preset' ) );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Página no encontrada', array( 'status' => 404 ) );
        }

        $presets = $this->get_all_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_Error( 'preset_not_found', "Preset '$preset_id' no encontrado", array( 'status' => 404 ) );
        }

        $preset = $presets[ $preset_id ];

        // Aplicar Schema
        if ( isset( $preset['schema'] ) ) {
            $schema = $preset['schema'];
            $schema['name'] = get_the_title( $post_id );
            $schema['url'] = get_permalink( $post_id );
            $this->save_schema_meta( $post_id, $schema );
        }

        // Aplicar robots si existe
        if ( isset( $preset['robots'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'robots', $preset['robots'] );
        }

        return rest_ensure_response( array(
            'success' => true,
            'id' => $post_id,
            'preset_applied' => $preset_id,
            'seo' => $this->get_seo_meta( $post_id ),
            'schema' => $this->get_schema_meta( $post_id ),
        ) );
    }

    /**
     * Output meta tags en el head
     */
    public function output_meta_tags() {
        if ( ! is_singular() ) {
            return;
        }

        $post_id = get_the_ID();
        $seo = $this->get_seo_meta( $post_id );
        $og = $this->get_og_meta( $post_id );
        $twitter = $this->get_twitter_meta( $post_id );
        $settings = get_option( 'flavor_seo_settings', $this->get_default_seo_settings() );

        // Meta básicos
        if ( ! empty( $seo['meta_description'] ) ) {
            echo '<meta name="description" content="' . esc_attr( $seo['meta_description'] ) . '">' . "\n";
        }

        if ( ! empty( $seo['meta_keywords'] ) ) {
            echo '<meta name="keywords" content="' . esc_attr( $seo['meta_keywords'] ) . '">' . "\n";
        }

        if ( ! empty( $seo['robots'] ) ) {
            echo '<meta name="robots" content="' . esc_attr( $seo['robots'] ) . '">' . "\n";
        }

        if ( ! empty( $seo['canonical_url'] ) ) {
            echo '<link rel="canonical" href="' . esc_url( $seo['canonical_url'] ) . '">' . "\n";
        }

        // Open Graph
        if ( $settings['enable_og'] ?? true ) {
            $og_title = $og['title'] ?: get_the_title( $post_id );
            $og_description = $og['description'] ?: $seo['meta_description'];
            $og_image = $og['image'] ?: get_the_post_thumbnail_url( $post_id, 'large' );
            $og_type = $og['type'] ?: 'article';

            echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
            echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

            if ( $og_image ) {
                echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
            }

            if ( ! empty( $settings['facebook_app_id'] ) ) {
                echo '<meta property="fb:app_id" content="' . esc_attr( $settings['facebook_app_id'] ) . '">' . "\n";
            }
        }

        // Twitter Cards
        if ( $settings['enable_twitter'] ?? true ) {
            $twitter_card = $twitter['card'] ?: 'summary_large_image';
            $twitter_title = $twitter['title'] ?: get_the_title( $post_id );
            $twitter_description = $twitter['description'] ?: $seo['meta_description'];
            $twitter_image = $twitter['image'] ?: get_the_post_thumbnail_url( $post_id, 'large' );

            echo '<meta name="twitter:card" content="' . esc_attr( $twitter_card ) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '">' . "\n";

            if ( $twitter_image ) {
                echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
            }

            if ( ! empty( $settings['twitter_site'] ) ) {
                echo '<meta name="twitter:site" content="' . esc_attr( $settings['twitter_site'] ) . '">' . "\n";
            }
        }

        // Schema.org
        if ( $settings['enable_schema'] ?? true ) {
            $schema = $this->get_schema_meta( $post_id );
            if ( ! empty( $schema['type'] ) ) {
                $json_ld = $this->build_schema_json_ld( $post_id, $schema );
                echo '<script type="application/ld+json">' . wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
            }
        }
    }

    // ============ HELPERS ============

    /**
     * Obtener meta SEO de un post
     */
    private function get_seo_meta( $post_id ) {
        return array(
            'meta_title' => get_post_meta( $post_id, self::META_PREFIX . 'meta_title', true ),
            'meta_description' => get_post_meta( $post_id, self::META_PREFIX . 'meta_description', true ),
            'meta_keywords' => get_post_meta( $post_id, self::META_PREFIX . 'meta_keywords', true ),
            'canonical_url' => get_post_meta( $post_id, self::META_PREFIX . 'canonical_url', true ),
            'robots' => get_post_meta( $post_id, self::META_PREFIX . 'robots', true ),
            'focus_keyword' => get_post_meta( $post_id, self::META_PREFIX . 'focus_keyword', true ),
        );
    }

    /**
     * Obtener meta Open Graph
     */
    private function get_og_meta( $post_id ) {
        return array(
            'title' => get_post_meta( $post_id, self::META_PREFIX . 'og_title', true ),
            'description' => get_post_meta( $post_id, self::META_PREFIX . 'og_description', true ),
            'image' => get_post_meta( $post_id, self::META_PREFIX . 'og_image', true ),
            'type' => get_post_meta( $post_id, self::META_PREFIX . 'og_type', true ),
        );
    }

    /**
     * Guardar meta Open Graph
     */
    private function save_og_meta( $post_id, $data ) {
        $fields = array( 'title', 'description', 'image', 'type' );

        foreach ( $fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                update_post_meta( $post_id, self::META_PREFIX . 'og_' . $field, sanitize_text_field( $data[ $field ] ) );
            }
        }
    }

    /**
     * Obtener meta Twitter Card
     */
    private function get_twitter_meta( $post_id ) {
        return array(
            'card' => get_post_meta( $post_id, self::META_PREFIX . 'twitter_card', true ),
            'title' => get_post_meta( $post_id, self::META_PREFIX . 'twitter_title', true ),
            'description' => get_post_meta( $post_id, self::META_PREFIX . 'twitter_description', true ),
            'image' => get_post_meta( $post_id, self::META_PREFIX . 'twitter_image', true ),
        );
    }

    /**
     * Guardar meta Twitter Card
     */
    private function save_twitter_meta( $post_id, $data ) {
        $fields = array( 'card', 'title', 'description', 'image' );

        foreach ( $fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                update_post_meta( $post_id, self::META_PREFIX . 'twitter_' . $field, sanitize_text_field( $data[ $field ] ) );
            }
        }
    }

    /**
     * Obtener meta Schema.org
     */
    private function get_schema_meta( $post_id ) {
        return array(
            'type' => get_post_meta( $post_id, self::META_PREFIX . 'schema_type', true ),
            'properties' => get_post_meta( $post_id, self::META_PREFIX . 'schema_properties', true ) ?: array(),
        );
    }

    /**
     * Guardar meta Schema.org
     */
    private function save_schema_meta( $post_id, $data ) {
        if ( isset( $data['type'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'schema_type', sanitize_text_field( $data['type'] ) );
        }

        if ( isset( $data['properties'] ) && is_array( $data['properties'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'schema_properties', $this->sanitize_array( $data['properties'] ) );
        }
    }

    /**
     * Construir JSON-LD para Schema.org
     */
    private function build_schema_json_ld( $post_id, $schema ) {
        $type = $schema['type'];
        $properties = $schema['properties'] ?: array();

        $json_ld = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => get_the_title( $post_id ),
            'url' => get_permalink( $post_id ),
        );

        // Añadir propiedades adicionales
        foreach ( $properties as $key => $value ) {
            if ( ! empty( $value ) ) {
                $json_ld[ $key ] = $value;
            }
        }

        // Imagen si existe
        $thumbnail = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( $thumbnail ) {
            $json_ld['image'] = $thumbnail;
        }

        // Fecha de publicación
        $json_ld['datePublished'] = get_the_date( 'c', $post_id );
        $json_ld['dateModified'] = get_the_modified_date( 'c', $post_id );

        return $json_ld;
    }

    /**
     * Tipos de Schema disponibles
     */
    private function get_all_schema_types() {
        return array(
            'Article' => array(
                'name' => 'Artículo',
                'description' => 'Artículo de blog o noticia',
                'properties' => array( 'author', 'publisher', 'datePublished', 'image' ),
            ),
            'LocalBusiness' => array(
                'name' => 'Negocio Local',
                'description' => 'Negocio con ubicación física',
                'properties' => array( 'address', 'telephone', 'openingHours', 'priceRange' ),
            ),
            'Organization' => array(
                'name' => 'Organización',
                'description' => 'Empresa u organización',
                'properties' => array( 'logo', 'contactPoint', 'sameAs' ),
            ),
            'Person' => array(
                'name' => 'Persona',
                'description' => 'Página personal o perfil',
                'properties' => array( 'jobTitle', 'worksFor', 'sameAs' ),
            ),
            'Event' => array(
                'name' => 'Evento',
                'description' => 'Evento con fecha y lugar',
                'properties' => array( 'startDate', 'endDate', 'location', 'performer' ),
            ),
            'Product' => array(
                'name' => 'Producto',
                'description' => 'Producto a la venta',
                'properties' => array( 'brand', 'offers', 'sku', 'aggregateRating' ),
            ),
            'Service' => array(
                'name' => 'Servicio',
                'description' => 'Servicio ofrecido',
                'properties' => array( 'provider', 'serviceType', 'areaServed' ),
            ),
            'FAQPage' => array(
                'name' => 'Página de FAQ',
                'description' => 'Página de preguntas frecuentes',
                'properties' => array( 'mainEntity' ),
            ),
            'HowTo' => array(
                'name' => 'Tutorial',
                'description' => 'Guía paso a paso',
                'properties' => array( 'step', 'totalTime', 'tool', 'supply' ),
            ),
            'Recipe' => array(
                'name' => 'Receta',
                'description' => 'Receta de cocina',
                'properties' => array( 'ingredients', 'instructions', 'cookTime', 'nutrition' ),
            ),
            'Course' => array(
                'name' => 'Curso',
                'description' => 'Curso online o presencial',
                'properties' => array( 'provider', 'courseCode', 'hasCourseInstance' ),
            ),
            'WebPage' => array(
                'name' => 'Página Web',
                'description' => 'Página web genérica',
                'properties' => array( 'breadcrumb', 'primaryImageOfPage', 'speakable' ),
            ),
        );
    }

    /**
     * Presets de SEO
     */
    private function get_all_presets() {
        return array(
            'article' => array(
                'name' => 'Artículo de Blog',
                'schema' => array(
                    'type' => 'Article',
                    'properties' => array(
                        'articleSection' => 'Blog',
                    ),
                ),
                'robots' => 'index, follow',
            ),
            'landing-page' => array(
                'name' => 'Landing Page',
                'schema' => array(
                    'type' => 'WebPage',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'product' => array(
                'name' => 'Página de Producto',
                'schema' => array(
                    'type' => 'Product',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'service' => array(
                'name' => 'Página de Servicio',
                'schema' => array(
                    'type' => 'Service',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'local-business' => array(
                'name' => 'Negocio Local',
                'schema' => array(
                    'type' => 'LocalBusiness',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'event' => array(
                'name' => 'Evento',
                'schema' => array(
                    'type' => 'Event',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'faq' => array(
                'name' => 'Preguntas Frecuentes',
                'schema' => array(
                    'type' => 'FAQPage',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'contact' => array(
                'name' => 'Página de Contacto',
                'schema' => array(
                    'type' => 'ContactPage',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'about' => array(
                'name' => 'Sobre Nosotros',
                'schema' => array(
                    'type' => 'AboutPage',
                    'properties' => array(),
                ),
                'robots' => 'index, follow',
            ),
            'noindex' => array(
                'name' => 'No Indexar',
                'schema' => array(
                    'type' => 'WebPage',
                    'properties' => array(),
                ),
                'robots' => 'noindex, nofollow',
            ),
        );
    }

    /**
     * Configuración SEO por defecto
     */
    private function get_default_seo_settings() {
        return array(
            'title_separator' => '|',
            'title_format' => '%title% %sep% %sitename%',
            'default_description' => '',
            'default_image' => '',
            'social_profiles' => array(),
            'organization_name' => get_bloginfo( 'name' ),
            'organization_logo' => '',
            'organization_type' => 'Organization',
            'local_business' => array(),
            'enable_schema' => true,
            'enable_og' => true,
            'enable_twitter' => true,
            'twitter_site' => '',
            'facebook_app_id' => '',
            'google_verification' => '',
            'bing_verification' => '',
        );
    }

    /**
     * Configuración sitemap por defecto
     */
    private function get_default_sitemap_config() {
        return array(
            'enabled' => true,
            'include_posts' => true,
            'include_pages' => true,
            'include_categories' => true,
            'include_tags' => false,
            'include_authors' => false,
            'exclude_post_types' => array(),
            'exclude_taxonomies' => array(),
            'exclude_ids' => array(),
            'max_entries' => 1000,
            'change_frequency' => 'weekly',
            'priority_homepage' => '1.0',
            'priority_posts' => '0.8',
            'priority_pages' => '0.6',
        );
    }

    /**
     * Robots.txt por defecto
     */
    private function get_default_robots() {
        $sitemap_url = home_url( 'sitemap.xml' );

        return "User-agent: *\nAllow: /\n\nSitemap: $sitemap_url";
    }

    /**
     * Generar descripción desde contenido
     */
    private function generate_description_from_content( $content ) {
        // Limpiar y obtener primeras oraciones
        $content = preg_replace( '/\s+/', ' ', trim( $content ) );

        // Obtener primera oración o primeros 155 caracteres
        if ( preg_match( '/^(.+?\.)\s/', $content, $matches ) ) {
            $description = $matches[1];
            if ( strlen( $description ) > 160 ) {
                $description = substr( $description, 0, 157 ) . '...';
            }
        } else {
            $description = substr( $content, 0, 157 );
            if ( strlen( $content ) > 157 ) {
                $description .= '...';
            }
        }

        return $description;
    }

    /**
     * Generar sugerencia de descripción
     */
    private function generate_description_suggestion( $content ) {
        return array(
            'type' => 'suggestion',
            'message' => 'Sugerencia de descripción generada automáticamente',
            'field' => 'description',
            'value' => $this->generate_description_from_content( $content ),
        );
    }

    /**
     * Extraer palabras clave del contenido
     */
    private function extract_keywords( $content ) {
        // Limpiar contenido
        $content = strtolower( $content );
        $content = preg_replace( '/[^\w\s]/u', '', $content );

        // Obtener palabras
        $words = str_word_count( $content, 1, 'áéíóúñü' );

        // Filtrar stopwords
        $stopwords = array(
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
            'de', 'del', 'en', 'con', 'por', 'para', 'a', 'al',
            'y', 'o', 'que', 'se', 'es', 'su', 'sus', 'son',
            'como', 'más', 'pero', 'no', 'si', 'ya', 'muy',
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        );

        $words = array_filter( $words, function( $word ) use ( $stopwords ) {
            return strlen( $word ) > 3 && ! in_array( $word, $stopwords, true );
        } );

        // Contar frecuencia
        $frequency = array_count_values( $words );
        arsort( $frequency );

        // Devolver top 5
        return array_slice( array_keys( $frequency ), 0, 5 );
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
function flavor_seo_api() {
    return Flavor_SEO_API::get_instance();
}
