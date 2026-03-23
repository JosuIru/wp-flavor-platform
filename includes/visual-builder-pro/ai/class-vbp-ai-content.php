<?php
/**
 * Visual Builder Pro - AI Content Generator
 *
 * Controlador principal para generación de contenido con IA.
 *
 * @package FlavorChatIA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de AI Content para el Visual Builder Pro
 */
class Flavor_VBP_AI_Content {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_AI_Content|null
     */
    private static $instancia = null;

    /**
     * Instancia de prompts
     *
     * @var Flavor_VBP_AI_Prompts|null
     */
    private $prompts = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_AI_Content
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_dependencies();
        $this->register_rest_routes();
    }

    /**
     * Carga dependencias
     */
    private function load_dependencies() {
        require_once __DIR__ . '/class-vbp-ai-prompts.php';
        require_once __DIR__ . '/class-vbp-ai-suggestions.php';

        $this->prompts = Flavor_VBP_AI_Prompts::get_instance();
    }

    /**
     * Registra rutas REST
     */
    private function register_rest_routes() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST de AI
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Generar contenido nuevo
        register_rest_route(
            $namespace,
            '/ai/generate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generate_content' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'type'    => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context' => array(
                        'required'          => false,
                        'type'              => 'object',
                        'default'           => array(),
                    ),
                ),
            )
        );

        // Mejorar contenido existente
        register_rest_route(
            $namespace,
            '/ai/improve',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'improve_content' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'content' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'action'  => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context' => array(
                        'required'          => false,
                        'type'              => 'object',
                        'default'           => array(),
                    ),
                ),
            )
        );

        // Obtener sugerencias contextuales
        register_rest_route(
            $namespace,
            '/ai/suggestions',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'get_suggestions' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'element_type' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'page_context' => array(
                        'required'          => false,
                        'type'              => 'object',
                        'default'           => array(),
                    ),
                ),
            )
        );

        // Traducir contenido
        register_rest_route(
            $namespace,
            '/ai/translate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'translate_content' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'content'         => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'target_language' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener opciones disponibles (tipos, industrias, tonos)
        register_rest_route(
            $namespace,
            '/ai/options',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_options' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Generar página completa con IA
        register_rest_route(
            $namespace,
            '/ai/generate-page',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'generate_full_page' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'page_type' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'sections'  => array(
                        'required'          => false,
                        'type'              => 'array',
                        'default'           => array(),
                    ),
                    'context'   => array(
                        'required'          => false,
                        'type'              => 'object',
                        'default'           => array(),
                    ),
                ),
            )
        );

        // Obtener tipos de página disponibles
        register_rest_route(
            $namespace,
            '/ai/page-types',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_page_types' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Verifica permisos
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Genera contenido nuevo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_content( $request ) {
        $type = $request->get_param( 'type' );
        $context = $request->get_param( 'context' ) ?: array();

        // Obtener el prompt según el tipo
        $prompt = $this->get_prompt_for_type( $type, $context );

        if ( is_wp_error( $prompt ) ) {
            return $prompt;
        }

        // Enviar a la IA
        $response = $this->send_to_ai( $prompt );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Procesar respuesta según el tipo
        $content = $this->process_ai_response( $response, $type );

        return new WP_REST_Response(
            array(
                'success' => true,
                'content' => $content,
                'type'    => $type,
            ),
            200
        );
    }

    /**
     * Mejora contenido existente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function improve_content( $request ) {
        $content = $request->get_param( 'content' );
        $action = $request->get_param( 'action' );
        $context = $request->get_param( 'context' ) ?: array();

        // Validar acción
        $valid_actions = array( 'rewrite', 'shorten', 'expand', 'formal', 'casual', 'persuasive' );
        if ( ! in_array( $action, $valid_actions, true ) ) {
            return new WP_Error(
                'invalid_action',
                __( 'Acción no válida', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        // Obtener prompt de mejora
        $prompt = $this->prompts->get_improve_prompt( $content, $action, $context );

        // Enviar a la IA
        $response = $this->send_to_ai( $prompt );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'content' => trim( $response ),
                'action'  => $action,
            ),
            200
        );
    }

    /**
     * Traduce contenido
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function translate_content( $request ) {
        $content = $request->get_param( 'content' );
        $target_language = $request->get_param( 'target_language' );

        // Obtener prompt de traducción
        $prompt = $this->prompts->get_translate_prompt( $content, $target_language );

        // Enviar a la IA
        $response = $this->send_to_ai( $prompt );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return new WP_REST_Response(
            array(
                'success'  => true,
                'content'  => trim( $response ),
                'language' => $target_language,
            ),
            200
        );
    }

    /**
     * Obtiene sugerencias contextuales
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function get_suggestions( $request ) {
        $element_type = $request->get_param( 'element_type' );
        $page_context = $request->get_param( 'page_context' ) ?: array();

        $suggestions_handler = Flavor_VBP_AI_Suggestions::get_instance();
        $suggestions = $suggestions_handler->get_suggestions_for_element( $element_type, $page_context );

        return new WP_REST_Response(
            array(
                'success'     => true,
                'suggestions' => $suggestions,
                'element'     => $element_type,
            ),
            200
        );
    }

    /**
     * Obtiene opciones disponibles
     *
     * @return WP_REST_Response
     */
    public function get_options() {
        return new WP_REST_Response(
            array(
                'content_types' => $this->prompts->get_content_types(),
                'industries'    => $this->prompts->get_industries(),
                'tones'         => $this->prompts->get_tones(),
                'actions'       => array(
                    'rewrite'    => __( 'Reescribir (más persuasivo)', 'flavor-chat-ia' ),
                    'shorten'    => __( 'Acortar', 'flavor-chat-ia' ),
                    'expand'     => __( 'Expandir', 'flavor-chat-ia' ),
                    'formal'     => __( 'Hacer más formal', 'flavor-chat-ia' ),
                    'casual'     => __( 'Hacer más casual', 'flavor-chat-ia' ),
                    'persuasive' => __( 'Hacer más persuasivo', 'flavor-chat-ia' ),
                ),
                'languages'     => array(
                    'es' => __( 'Español', 'flavor-chat-ia' ),
                    'en' => __( 'Inglés', 'flavor-chat-ia' ),
                    'fr' => __( 'Francés', 'flavor-chat-ia' ),
                    'de' => __( 'Alemán', 'flavor-chat-ia' ),
                    'it' => __( 'Italiano', 'flavor-chat-ia' ),
                    'pt' => __( 'Portugués', 'flavor-chat-ia' ),
                    'ca' => __( 'Catalán', 'flavor-chat-ia' ),
                    'eu' => __( 'Euskera', 'flavor-chat-ia' ),
                    'gl' => __( 'Gallego', 'flavor-chat-ia' ),
                ),
            ),
            200
        );
    }

    /**
     * Obtiene el prompt según el tipo de contenido
     *
     * @param string $type Tipo de contenido.
     * @param array  $context Contexto.
     * @return string|WP_Error
     */
    private function get_prompt_for_type( $type, $context ) {
        switch ( $type ) {
            case 'hero_title':
                return $this->prompts->get_hero_title_prompt( $context );

            case 'hero_subtitle':
                return $this->prompts->get_hero_subtitle_prompt( $context );

            case 'cta_button':
                return $this->prompts->get_cta_button_prompt( $context );

            case 'feature':
                return $this->prompts->get_feature_prompt( $context );

            case 'features_list':
                return $this->prompts->get_features_list_prompt( $context );

            case 'testimonial':
                return $this->prompts->get_testimonial_prompt( $context );

            case 'stats':
                return $this->prompts->get_stats_prompt( $context );

            case 'faq':
                return $this->prompts->get_faq_prompt( $context );

            case 'description':
                return $this->prompts->get_description_prompt( $context );

            default:
                return new WP_Error(
                    'invalid_type',
                    __( 'Tipo de contenido no válido', 'flavor-chat-ia' ),
                    array( 'status' => 400 )
                );
        }
    }

    /**
     * Envía prompt a la IA
     *
     * @param string $prompt Prompt a enviar.
     * @return string|WP_Error
     */
    private function send_to_ai( $prompt ) {
        // Verificar que el Engine Manager esté disponible
        if ( ! class_exists( 'Flavor_Engine_Manager' ) ) {
            return new WP_Error(
                'ai_not_available',
                __( 'El motor de IA no está disponible', 'flavor-chat-ia' ),
                array( 'status' => 500 )
            );
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $system_prompt = $this->prompts->get_system_prompt();

        $messages = array(
            array(
                'role'    => 'user',
                'content' => $prompt,
            ),
        );

        $response = $engine_manager->send_backend_message( $messages, $system_prompt );

        if ( ! $response['success'] ) {
            return new WP_Error(
                'ai_error',
                $response['error'] ?? __( 'Error al generar contenido', 'flavor-chat-ia' ),
                array( 'status' => 500 )
            );
        }

        // Extraer contenido de la respuesta
        $content = '';
        if ( isset( $response['response'] ) ) {
            $content = $response['response'];
        } elseif ( isset( $response['content'] ) ) {
            $content = $response['content'];
        } elseif ( isset( $response['message'] ) ) {
            $content = $response['message'];
        }

        return $content;
    }

    /**
     * Procesa la respuesta de la IA según el tipo
     *
     * @param string $response Respuesta de la IA.
     * @param string $type Tipo de contenido.
     * @return mixed
     */
    private function process_ai_response( $response, $type ) {
        $response = trim( $response );

        // Tipos que devuelven JSON
        $json_types = array( 'cta_button', 'feature', 'features_list', 'testimonial', 'stats', 'faq' );

        if ( in_array( $type, $json_types, true ) ) {
            // Intentar extraer JSON de la respuesta
            $json_match = array();
            if ( preg_match( '/\[[\s\S]*\]|\{[\s\S]*\}/', $response, $json_match ) ) {
                $decoded = json_decode( $json_match[0], true );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    return $decoded;
                }
            }

            // Si falla el parse, devolver como texto
            return $response;
        }

        // Para tipos de texto simple, limpiar comillas si las hay
        $response = trim( $response, '"\'' );

        return $response;
    }

    /**
     * Verifica si la IA está configurada y disponible
     *
     * @return bool
     */
    public function is_ai_available() {
        if ( ! class_exists( 'Flavor_Engine_Manager' ) ) {
            return false;
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_backend_engine();

        return $engine && $engine->is_configured();
    }

    /**
     * Obtiene información del estado de la IA
     *
     * @return array
     */
    public function get_ai_status() {
        $available = $this->is_ai_available();

        $status = array(
            'available' => $available,
            'message'   => $available
                ? __( 'IA configurada y lista', 'flavor-chat-ia' )
                : __( 'IA no configurada. Configura un proveedor en Ajustes > Chat IA', 'flavor-chat-ia' ),
        );

        if ( $available && class_exists( 'Flavor_Engine_Manager' ) ) {
            $engine_manager = Flavor_Engine_Manager::get_instance();
            $config = $engine_manager->get_context_config( Flavor_Engine_Manager::CONTEXT_BACKEND );
            $status['provider'] = $config['provider'];
            $status['model'] = $config['model'];
        }

        return $status;
    }

    /**
     * Genera una página completa con IA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_full_page( $request ) {
        $page_type = $request->get_param( 'page_type' );
        $sections = $request->get_param( 'sections' ) ?: array();
        $context = $request->get_param( 'context' ) ?: array();

        // Validar tipo de página
        $valid_page_types = array_keys( $this->prompts->get_page_types() );
        if ( ! in_array( $page_type, $valid_page_types, true ) ) {
            return new WP_Error(
                'invalid_page_type',
                __( 'Tipo de página no válido', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        // Si no se especifican secciones, usar las predeterminadas del tipo
        if ( empty( $sections ) ) {
            $sections = $this->prompts->get_default_sections_for_page_type( $page_type );
        }

        // Obtener prompt para página completa
        $prompt = $this->prompts->get_full_page_prompt( $page_type, $sections, $context );

        // Enviar a la IA
        $response = $this->send_to_ai( $prompt );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Procesar respuesta como estructura de bloques VBP
        $page_structure = $this->process_page_response( $response );

        if ( is_wp_error( $page_structure ) ) {
            return $page_structure;
        }

        return new WP_REST_Response(
            array(
                'success'   => true,
                'page_type' => $page_type,
                'sections'  => $sections,
                'content'   => $page_structure,
            ),
            200
        );
    }

    /**
     * Procesa la respuesta de IA para páginas completas
     *
     * @param string $response Respuesta de la IA.
     * @return array|WP_Error
     */
    private function process_page_response( $response ) {
        $response = trim( $response );

        // Intentar extraer JSON de la respuesta
        $json_match = array();
        if ( preg_match( '/\{[\s\S]*\}/', $response, $json_match ) ) {
            $decoded = json_decode( $json_match[0], true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $decoded;
            }
        }

        // Si no hay JSON válido, intentar parsear como array
        if ( preg_match( '/\[[\s\S]*\]/', $response, $json_match ) ) {
            $decoded = json_decode( $json_match[0], true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return array( 'blocks' => $decoded );
            }
        }

        return new WP_Error(
            'parse_error',
            __( 'Error al procesar la respuesta de IA', 'flavor-chat-ia' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Obtiene los tipos de página disponibles
     *
     * @return WP_REST_Response
     */
    public function get_page_types() {
        return new WP_REST_Response(
            array(
                'page_types'    => $this->prompts->get_page_types(),
                'section_types' => $this->prompts->get_section_types(),
            ),
            200
        );
    }
}

// Inicializar
Flavor_VBP_AI_Content::get_instance();
