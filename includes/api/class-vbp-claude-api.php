<?php
/**
 * REST API para integración de VBP con Claude Code
 *
 * Endpoints para crear y gestionar páginas VBP desde herramientas externas.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de VBP para Claude Code
 */
class Flavor_VBP_Claude_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Claude_API|null
     */
    private static $instancia = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Clave de API para autenticación básica
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Claude_API
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
     * Asegura que VBP Block Library esté disponible
     *
     * @return bool
     */
    private function ensure_vbp_loaded() {
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            return true;
        }

        // Intentar cargar VBP manualmente
        $loader_path = FLAVOR_CHAT_IA_PATH . 'includes/visual-builder-pro/class-vbp-loader.php';
        if ( file_exists( $loader_path ) ) {
            require_once $loader_path;
            if ( class_exists( 'Flavor_VBP_Loader' ) ) {
                Flavor_VBP_Loader::get_instance();
            }
        }

        return class_exists( 'Flavor_VBP_Block_Library' );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Obtener schema de bloques
        register_rest_route( self::NAMESPACE, '/claude/schema', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_schema' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Listar bloques
        register_rest_route( self::NAMESPACE, '/claude/blocks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Listar módulos activos
        register_rest_route( self::NAMESPACE, '/claude/modules', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_modules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear página
        register_rest_route( self::NAMESPACE, '/claude/pages', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'elements' => array(
                    'type'    => 'array',
                    'default' => array(),
                ),
                'template' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'context' => array(
                    'type'        => 'object',
                    'default'     => array(),
                    'description' => 'Contexto para personalizar la plantilla (topic, industry, etc.)',
                ),
                'status' => array(
                    'type'    => 'string',
                    'default' => 'draft',
                    'enum'    => array( 'draft', 'publish' ),
                ),
                'settings' => array(
                    'type'        => 'object',
                    'default'     => array(),
                    'description' => 'Configuración de la página (pageWidth, backgroundColor, fullWidth, etc.)',
                ),
                'design_preset' => array(
                    'type'        => 'string',
                    'default'     => '',
                    'description' => 'Preset de diseño a aplicar (modern, corporate, nature, bold, minimal)',
                ),
            ),
        ) );

        // Obtener página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title'         => array( 'type' => 'string' ),
                'elements'      => array( 'type' => 'array' ),
                'status'        => array( 'type' => 'string' ),
                'settings'      => array(
                    'type'        => 'object',
                    'description' => 'Configuración de la página (pageWidth, backgroundColor, fullWidth, etc.)',
                ),
                'design_preset' => array(
                    'type'        => 'string',
                    'description' => 'Preset de diseño a aplicar (modern, corporate, nature, bold, minimal)',
                ),
            ),
        ) );

        // Añadir bloque a página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'data' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
                'position' => array(
                    'type'    => 'string',
                    'default' => 'end',
                ),
            ),
        ) );

        // Listar páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/pages', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'status' => array(
                    'type'    => 'string',
                    'default' => 'any',
                ),
            ),
        ) );

        // Generar sección con plantilla
        register_rest_route( self::NAMESPACE, '/claude/generate-section', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_section' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'context' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
            ),
        ) );

        // Listar plantillas disponibles
        register_rest_route( self::NAMESPACE, '/claude/templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_templates' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener presets de un bloque
        register_rest_route( self::NAMESPACE, '/claude/blocks/(?P<type>[a-z_-]+)/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Duplicar página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/duplicate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'duplicate_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Obtener tipos de sección disponibles
        register_rest_route( self::NAMESPACE, '/claude/section-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_section_types' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === NUEVOS ENDPOINTS PARA AUTOMATIZACIÓN ===

        // Publicar página directamente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/publish', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'publish_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener URL pública de la landing
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/url', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_url' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Regenerar permalinks
        register_rest_route( self::NAMESPACE, '/claude/flush-permalinks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'flush_permalinks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Validar animaciones de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/validate-animations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'validate_animations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Estado del sistema VBP
        register_rest_route( self::NAMESPACE, '/claude/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_system_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Validar datos de elementos antes de crear página
        register_rest_route( self::NAMESPACE, '/claude/validate-elements', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'validate_elements' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'elements' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // === SISTEMA DE DISEÑO Y CAPABILITIES ===

        // Obtener presets de diseño disponibles
        register_rest_route( self::NAMESPACE, '/claude/design-presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_design_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener capabilities completas del sistema
        register_rest_route( self::NAMESPACE, '/claude/capabilities', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_capabilities' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear página con preset de diseño aplicado
        register_rest_route( self::NAMESPACE, '/claude/pages/styled', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_styled_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'preset' => array(
                    'type'    => 'string',
                    'default' => 'modern',
                    'enum'    => array( 'modern', 'corporate', 'minimal', 'dark', 'vibrant', 'elegant', 'tech', 'nature', 'community', 'cooperative', 'eco', 'fundraising' ),
                ),
                'sections' => array(
                    'type'    => 'array',
                    'default' => array(),
                ),
                'context' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
                'status' => array(
                    'type'    => 'string',
                    'default' => 'draft',
                ),
            ),
        ) );

        // Obtener widgets de módulos disponibles
        register_rest_route( self::NAMESPACE, '/claude/widgets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_available_widgets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE MULTIIDIOMA / TRADUCCIÓN
        // =============================================

        // Obtener idiomas disponibles
        register_rest_route( self::NAMESPACE, '/claude/languages', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_languages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Traducir página VBP a un idioma
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/translate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'translate_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'to_lang' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Código del idioma destino (es, eu, en, fr, etc.)',
                ),
                'save' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Guardar las traducciones en la base de datos',
                ),
                'create_copy' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Crear una copia de la página traducida como nuevo post',
                ),
            ),
        ) );

        // Traducir página a múltiples idiomas
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/translate-all', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'translate_page_all_languages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'languages' => array(
                    'type'        => 'array',
                    'default'     => array(),
                    'description' => 'Lista de códigos de idioma. Vacío = todos los activos.',
                ),
                'save' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'create_copies' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Crear copias de la página traducida',
                ),
            ),
        ) );

        // Obtener traducciones existentes de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/translations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_translations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Traducir texto/elementos sueltos
        register_rest_route( self::NAMESPACE, '/claude/translate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'translate_content' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'content' => array(
                    'required'    => true,
                    'description' => 'Contenido a traducir (texto, HTML o JSON de elementos VBP)',
                ),
                'from_lang' => array(
                    'type'    => 'string',
                    'default' => 'es',
                ),
                'to_lang' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'type' => array(
                    'type'    => 'string',
                    'default' => 'text',
                    'enum'    => array( 'text', 'html', 'vbp_elements' ),
                ),
            ),
        ) );

        // Crear página desde JSON con traducciones automáticas
        register_rest_route( self::NAMESPACE, '/claude/pages/multilingual', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_multilingual_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'elements' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'base_lang' => array(
                    'type'    => 'string',
                    'default' => 'es',
                ),
                'languages' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Idiomas a los que traducir (además del base)',
                ),
                'status' => array(
                    'type'    => 'string',
                    'default' => 'publish',
                ),
                'design_preset' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PREVIEW DE WIDGETS
        // =============================================

        // Obtener preview HTML de un widget específico
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<type>[a-z_-]+)/preview', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Tipo de widget (ej: social_feed, eventos, marketplace_productos)',
                ),
                'config' => array(
                    'type'        => 'object',
                    'default'     => array(),
                    'description' => 'Configuración del widget para personalizar el preview',
                ),
            ),
        ) );
    }

    /**
     * Verifica permisos de API
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function check_api_permission( $request ) {
        // Verificar API key en header
        $auth_header = $request->get_header( 'X-VBP-Key' );
        if ( $auth_header === $this->api_key ) {
            return true;
        }

        // Verificar API key en query param
        $key_param = $request->get_param( 'api_key' );
        if ( $key_param === $this->api_key ) {
            return true;
        }

        // Verificar si es usuario autenticado con permisos
        return current_user_can( 'edit_posts' );
    }

    /**
     * Obtiene el schema completo de bloques
     *
     * @return WP_REST_Response
     */
    public function get_schema() {
        if ( ! $this->ensure_vbp_loaded() ) {
            return new WP_REST_Response( array( 'error' => 'VBP no disponible' ), 500 );
        }

        $libreria = Flavor_VBP_Block_Library::get_instance();
        $categorias = $libreria->get_categorias_con_bloques();

        $schema = array(
            'version'    => '2.1.0',
            'categories' => array(),
            'blocks'     => array(),
        );

        foreach ( $categorias as $categoria ) {
            $categoria_slug = sanitize_title( $categoria['name'] );
            $schema['categories'][ $categoria_slug ] = array(
                'name'   => $categoria['name'],
                'blocks' => array(),
            );

            foreach ( $categoria['blocks'] as $bloque ) {
                // El campo 'id' contiene el tipo del bloque (ej: 'hero', 'features')
                $block_type = $bloque['id'] ?? '';
                if ( empty( $block_type ) ) {
                    continue;
                }

                $bloque_info = array(
                    'type'        => $block_type,
                    'name'        => $bloque['name'] ?? $block_type,
                    'description' => $bloque['description'] ?? '',
                    'category'    => $categoria_slug,
                    'icon'        => $bloque['icon'] ?? '',
                    'variants'    => $bloque['variants'] ?? array(),
                    'fields'      => array(),
                    'defaults'    => $bloque['defaults'] ?? array(),
                    'presets'     => $bloque['presets'] ?? array(),
                );

                if ( isset( $bloque['fields'] ) ) {
                    foreach ( $bloque['fields'] as $key => $field ) {
                        if ( isset( $field['type'] ) && 'separator' !== $field['type'] ) {
                            $bloque_info['fields'][ $key ] = array(
                                'type'    => $field['type'] ?? 'text',
                                'label'   => $field['label'] ?? $key,
                                'default' => $field['default'] ?? null,
                            );
                            if ( isset( $field['options'] ) ) {
                                $bloque_info['fields'][ $key ]['options'] = $field['options'];
                            }
                        }
                    }
                }

                $schema['blocks'][ $block_type ] = $bloque_info;
                $schema['categories'][ $categoria_slug ]['blocks'][] = $block_type;
            }
        }

        return new WP_REST_Response( $schema, 200 );
    }

    /**
     * Lista bloques disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_blocks( $request ) {
        $category_filter = $request->get_param( 'category' );

        if ( ! $this->ensure_vbp_loaded() ) {
            return new WP_REST_Response( array( 'error' => 'VBP no disponible' ), 500 );
        }

        $libreria = Flavor_VBP_Block_Library::get_instance();
        $categorias = $libreria->get_categorias_con_bloques();
        $bloques = array();

        foreach ( $categorias as $categoria ) {
            $cat_slug = sanitize_title( $categoria['name'] );
            if ( $category_filter && $cat_slug !== $category_filter ) {
                continue;
            }

            foreach ( $categoria['blocks'] as $bloque ) {
                $block_type = $bloque['id'] ?? '';
                if ( empty( $block_type ) ) {
                    continue;
                }

                $bloques[] = array(
                    'type'        => $block_type,
                    'name'        => $bloque['name'] ?? $block_type,
                    'category'    => $cat_slug,
                    'description' => $bloque['description'] ?? '',
                    'icon'        => $bloque['icon'] ?? '',
                    'variants'    => array_keys( $bloque['variants'] ?? array() ),
                );
            }
        }

        return new WP_REST_Response( $bloques, 200 );
    }

    /**
     * Lista módulos activos
     *
     * @return WP_REST_Response
     */
    public function list_modules() {
        $modulos_activos = get_option( 'flavor_chat_modules', array() );

        $modulos = array();
        foreach ( $modulos_activos as $modulo ) {
            $modulos[] = array(
                'slug'   => $modulo,
                'active' => true,
            );
        }

        return new WP_REST_Response( $modulos, 200 );
    }

    /**
     * Crea una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_page( $request ) {
        $title = $request->get_param( 'title' );
        $elements = $request->get_param( 'elements' );
        $template = $request->get_param( 'template' );
        $status = $request->get_param( 'status' );
        $context = $request->get_param( 'context' ) ?: array();
        $settings = $request->get_param( 'settings' ) ?: array();
        $design_preset = $request->get_param( 'design_preset' );

        // Si hay template, obtener elementos predefinidos con contexto
        if ( $template && empty( $elements ) ) {
            // Usar el título de la página como topic si no se especifica
            if ( empty( $context['topic'] ) && ! empty( $title ) ) {
                $context['topic'] = $title;
            }
            $elements = $this->get_template_elements( $template, $context );
        }

        // Procesar elementos para asegurar estructura VBP completa
        $elements = $this->prepare_elements( $elements ?: array() );

        // Crear post
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
        }

        // Settings por defecto
        $default_settings = array(
            'pageWidth'       => 1200,
            'backgroundColor' => '#ffffff',
        );

        // Aplicar design preset si se especifica
        if ( $design_preset ) {
            $preset_settings = $this->get_design_preset( $design_preset );
            if ( $preset_settings ) {
                $default_settings = array_merge( $default_settings, $preset_settings );
                $default_settings['design_preset'] = $design_preset;
            }
        }

        // Merge con settings enviados en la request (tienen prioridad)
        $final_settings = array_merge( $default_settings, $settings );

        // Guardar datos VBP
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elements,
            'settings' => $final_settings,
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $title,
            'status'   => $status,
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
        ), 201 );
    }

    /**
     * Prepara los elementos para asegurar estructura VBP completa
     *
     * @param array $elements Elementos a procesar.
     * @return array Elementos procesados.
     */
    private function prepare_elements( $elements ) {
        $prepared = array();

        foreach ( $elements as $element ) {
            // Generar ID único si no existe
            if ( empty( $element['id'] ) ) {
                $element['id'] = 'el_' . bin2hex( random_bytes( 6 ) );
            }

            // Asegurar campos requeridos
            $element['type'] = $element['type'] ?? 'text';
            $element['name'] = $element['name'] ?? ucfirst( str_replace( array( '_', '-' ), ' ', $element['type'] ) );
            $element['visible'] = $element['visible'] ?? true;
            $element['locked'] = $element['locked'] ?? false;
            $element['data'] = $element['data'] ?? array();

            // NORMALIZACIÓN DE DATOS: Convertir campos inglés → español según tipo de elemento
            $element['data'] = $this->normalize_element_data( $element['type'], $element['data'] );

            // VALIDACIÓN Y DEFAULTS: Asegurar campos mínimos según tipo de elemento
            $element['data'] = $this->ensure_required_fields( $element['type'], $element['data'] );

            // Procesar animaciones si existen - guardar en styles.advanced para el renderer
            if ( ! empty( $element['animations'] ) ) {
                $anim_styles = $this->prepare_animations_for_vbp( $element['animations'] );
                if ( ! isset( $element['styles'] ) ) {
                    $element['styles'] = array();
                }
                if ( ! isset( $element['styles']['advanced'] ) ) {
                    $element['styles']['advanced'] = array();
                }
                $element['styles']['advanced'] = array_merge( $element['styles']['advanced'], $anim_styles );
                unset( $element['animations'] );
            }

            // Procesar children recursivamente
            if ( ! empty( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->prepare_elements( $element['children'] );
            } else {
                $element['children'] = array();
            }

            // Mezclar estilos proporcionados con estilos por defecto
            $default_styles = $this->get_default_styles();
            if ( isset( $element['styles'] ) && is_array( $element['styles'] ) ) {
                $element['styles'] = $this->merge_styles( $default_styles, $element['styles'] );
            } else {
                $element['styles'] = $default_styles;
            }

            $prepared[] = $element;
        }

        return $prepared;
    }

    /**
     * Normaliza los datos del elemento según su tipo
     *
     * Mapea campos entre formatos (inglés ↔ español) para asegurar compatibilidad
     * con los renderizadores VBP que esperan nombres de campo específicos.
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return array Datos normalizados.
     */
    private function normalize_element_data( $type, $data ) {
        if ( empty( $data ) || ! is_array( $data ) ) {
            return $data;
        }

        // Mapeo de campos inglés → español (el renderer espera español)
        $field_mappings = array(
            // Campos comunes a múltiples elementos
            'common' => array(
                'title'           => 'titulo',
                'subtitle'        => 'subtitulo',
                'description'     => 'descripcion',
                'content'         => 'contenido',
                'text'            => 'texto',
                'buttonText'      => 'boton_texto',
                'buttonUrl'       => 'boton_url',
                'button_text'     => 'boton_texto',
                'button_url'      => 'boton_url',
                'ctaText'         => 'boton_texto',
                'ctaUrl'          => 'boton_url',
                'cta_text'        => 'boton_texto',
                'cta_url'         => 'boton_url',
                'image'           => 'imagen',
                'backgroundImage' => 'imagen_fondo',
                'background_image'=> 'imagen_fondo',
                'backgroundColor' => 'color_fondo',
                'background_color'=> 'color_fondo',
                'icon'            => 'icono',
                'link'            => 'enlace',
                'url'             => 'enlace',
                'alignment'       => 'alineacion',
                'columns'         => 'columnas',
            ),
            // Campos específicos para hero
            'hero' => array(
                'secondButtonText' => 'boton_2_texto',
                'secondButtonUrl'  => 'boton_2_url',
                'button2Text'      => 'boton_2_texto',
                'button2Url'       => 'boton_2_url',
                'overlayColor'     => 'color_overlay',
                'overlayOpacity'   => 'opacidad_overlay',
            ),
            // Campos específicos para features
            'features' => array(
                'items'      => 'items',  // Mantener igual pero procesar internamente
                'features'   => 'items',
            ),
            // Campos específicos para testimonials
            'testimonials' => array(
                'testimonials' => 'testimonios',
                'showRating'   => 'mostrar_rating',
            ),
            // Campos específicos para pricing
            'pricing' => array(
                'plans'    => 'planes',
                'currency' => 'moneda',
                'period'   => 'periodo',
            ),
            // Campos específicos para FAQ
            'faq' => array(
                'questions' => 'faqs',
                'faqs'      => 'faqs',
            ),
            // Campos específicos para gallery
            'gallery' => array(
                'images' => 'imagenes',
            ),
            // Campos específicos para team
            'team' => array(
                'members' => 'miembros',
            ),
            // Campos específicos para contact
            'contact' => array(
                'email'       => 'email',
                'phone'       => 'telefono',
                'address'     => 'direccion',
                'showForm'    => 'mostrar_formulario',
            ),
            // Campos específicos para stats
            'stats' => array(
                'statistics' => 'stats',
            ),
            // Campos específicos para grid
            'grid' => array(
                'gap'         => 'espacio',
                'minColWidth' => 'ancho_min_columna',
            ),
            // Campos específicos para card
            'card' => array(
                'hoverEffect' => 'efecto_hover',
                'shadow'      => 'sombra',
            ),
            // Campos específicos para section
            'section' => array(
                'fullWidth'   => 'ancho_completo',
                'maxWidth'    => 'ancho_maximo',
            ),
        );

        // Obtener mapeos aplicables
        $mappings = $field_mappings['common'];
        if ( isset( $field_mappings[ $type ] ) ) {
            $mappings = array_merge( $mappings, $field_mappings[ $type ] );
        }

        // Aplicar mapeo de campos
        $normalized = array();
        foreach ( $data as $key => $value ) {
            // Si el campo tiene un mapeo, usar el nombre español
            if ( isset( $mappings[ $key ] ) ) {
                $spanish_key = $mappings[ $key ];
                // Solo mapear si el campo español no existe ya
                if ( ! isset( $data[ $spanish_key ] ) ) {
                    $normalized[ $spanish_key ] = $value;
                } else {
                    // Si existe en español, mantener el original
                    $normalized[ $key ] = $value;
                }
            } else {
                // Mantener el campo original
                $normalized[ $key ] = $value;
            }
        }

        // Copiar campos que ya están en español y no fueron mapeados
        foreach ( $data as $key => $value ) {
            if ( ! isset( $normalized[ $key ] ) && ! in_array( $key, $mappings, true ) ) {
                // Solo copiar si no es un campo inglés que ya fue mapeado
                $is_english_field = isset( $mappings[ $key ] );
                if ( ! $is_english_field ) {
                    $normalized[ $key ] = $value;
                }
            }
        }

        // Procesar arrays anidados (items, testimonios, etc.)
        $normalized = $this->normalize_nested_items( $type, $normalized );

        return $normalized;
    }

    /**
     * Normaliza arrays anidados dentro de los datos del elemento
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return array Datos con arrays anidados normalizados.
     */
    private function normalize_nested_items( $type, $data ) {
        // Mapeo de campos para items anidados
        $item_field_mappings = array(
            'title'       => 'titulo',
            'description' => 'descripcion',
            'content'     => 'contenido',
            'text'        => 'texto',
            'icon'        => 'icono',
            'image'       => 'imagen',
            'link'        => 'enlace',
            'url'         => 'enlace',
            'name'        => 'nombre',
            'position'    => 'cargo',
            'role'        => 'cargo',
            'bio'         => 'bio',
            'rating'      => 'rating',
            'author'      => 'autor',
            'question'    => 'pregunta',
            'answer'      => 'respuesta',
            'price'       => 'precio',
            'features'    => 'caracteristicas',
            'highlighted' => 'destacado',
            'label'       => 'etiqueta',
            'number'      => 'numero',
            'value'       => 'valor',
        );

        // Campos que contienen arrays de items
        $array_fields = array( 'items', 'testimonios', 'faqs', 'planes', 'miembros', 'stats', 'imagenes' );

        foreach ( $array_fields as $field ) {
            if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
                $normalized_items = array();
                foreach ( $data[ $field ] as $item ) {
                    if ( is_array( $item ) ) {
                        $normalized_item = array();
                        foreach ( $item as $key => $value ) {
                            if ( isset( $item_field_mappings[ $key ] ) ) {
                                $spanish_key = $item_field_mappings[ $key ];
                                if ( ! isset( $item[ $spanish_key ] ) ) {
                                    $normalized_item[ $spanish_key ] = $value;
                                } else {
                                    $normalized_item[ $key ] = $value;
                                }
                            } else {
                                $normalized_item[ $key ] = $value;
                            }
                        }
                        // Asegurar que campos originales en español se mantengan
                        foreach ( $item as $key => $value ) {
                            if ( ! isset( $normalized_item[ $key ] ) && ! isset( $item_field_mappings[ $key ] ) ) {
                                $normalized_item[ $key ] = $value;
                            }
                        }
                        $normalized_items[] = $normalized_item;
                    } else {
                        $normalized_items[] = $item;
                    }
                }
                $data[ $field ] = $normalized_items;
            }
        }

        return $data;
    }

    /**
     * Asegura que existen los campos requeridos para cada tipo de elemento
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return array Datos con campos requeridos asegurados.
     */
    private function ensure_required_fields( $type, $data ) {
        // Definir campos requeridos y valores por defecto para cada tipo
        $required_fields = array(
            'hero' => array(
                'titulo'      => '',
                'subtitulo'   => '',
                'boton_texto' => '',
                'boton_url'   => '#',
            ),
            'section' => array(
                'titulo' => '',
            ),
            'card' => array(
                'titulo' => '',
            ),
            'features' => array(
                'titulo'   => 'Características',
                'items'    => array(),
                'columnas' => 3,
            ),
            'cta' => array(
                'titulo'      => '',
                'boton_texto' => 'Comenzar',
                'boton_url'   => '#',
            ),
            'testimonials' => array(
                'titulo'      => 'Testimonios',
                'testimonios' => array(),
            ),
            'pricing' => array(
                'titulo'  => 'Precios',
                'moneda'  => '€',
                'periodo' => 'mes',
                'planes'  => array(),
            ),
            'faq' => array(
                'titulo' => 'Preguntas frecuentes',
                'faqs'   => array(),
            ),
            'stats' => array(
                'titulo' => '',
                'stats'  => array(),
            ),
            'team' => array(
                'titulo'   => 'Nuestro equipo',
                'miembros' => array(),
                'columnas' => 4,
            ),
            'contact' => array(
                'titulo' => 'Contacto',
            ),
            'gallery' => array(
                'titulo'   => '',
                'columnas' => 3,
                'imagenes' => array(),
            ),
            'grid' => array(
                'columnas' => 3,
                'espacio'  => '1rem',
            ),
            'text' => array(
                'contenido' => '',
            ),
        );

        // Si no hay campos requeridos definidos para este tipo, devolver datos sin cambios
        if ( ! isset( $required_fields[ $type ] ) ) {
            return $data;
        }

        // Mezclar: datos proporcionados tienen prioridad sobre defaults
        $defaults = $required_fields[ $type ];
        foreach ( $defaults as $field => $default_value ) {
            if ( ! isset( $data[ $field ] ) || ( empty( $data[ $field ] ) && $data[ $field ] !== 0 && $data[ $field ] !== false ) ) {
                // Solo establecer default si el campo no existe o está vacío
                // pero permitir valores falsy válidos como 0 o false
                if ( is_array( $default_value ) || $default_value !== '' ) {
                    $data[ $field ] = $default_value;
                }
            }
        }

        return $data;
    }

    /**
     * Prepara las animaciones para el formato VBP Canvas
     *
     * Mapea las claves simples de la API a las claves que espera el renderer:
     * - entrance → entranceAnimation
     * - trigger → animTrigger
     * - duration → animDuration
     * - delay → animDelay
     * - easing → animEasing
     * - hover → hoverAnimation
     * - loop → loopAnimation
     * - parallax → parallaxEnabled + parallaxSpeed
     *
     * @param array $animations Configuración de animaciones.
     * @return array Animaciones en formato VBP.
     */
    private function prepare_animations_for_vbp( $animations ) {
        $vbp_format = array();

        // Animación de entrada
        if ( ! empty( $animations['entrance'] ) ) {
            $vbp_format['entranceAnimation'] = $animations['entrance'];
            $vbp_format['animTrigger'] = $animations['trigger'] ?? 'scroll';
            $vbp_format['animDuration'] = $animations['duration'] ?? '0.6s';
            $vbp_format['animDelay'] = $animations['delay'] ?? '0s';
            $vbp_format['animEasing'] = $animations['easing'] ?? 'ease-out';
        }

        // Animación hover
        if ( ! empty( $animations['hover'] ) ) {
            $vbp_format['hoverAnimation'] = $animations['hover'];
        }

        // Animación en loop
        if ( ! empty( $animations['loop'] ) ) {
            $vbp_format['loopAnimation'] = $animations['loop'];
        }

        // Parallax
        if ( ! empty( $animations['parallax'] ) ) {
            $vbp_format['parallaxEnabled'] = true;
            $vbp_format['parallaxSpeed'] = is_numeric( $animations['parallax'] )
                ? floatval( $animations['parallax'] )
                : ( isset( $animations['parallaxSpeed'] ) ? floatval( $animations['parallaxSpeed'] ) : 0.5 );
        }

        // Stagger para animaciones escalonadas (usado en grids/listas)
        if ( ! empty( $animations['stagger'] ) ) {
            $vbp_format['animStagger'] = $animations['stagger'];
        }

        return $vbp_format;
    }

    /**
     * Mezcla estilos de forma recursiva
     *
     * @param array $default Estilos por defecto.
     * @param array $custom  Estilos personalizados.
     * @return array Estilos mezclados.
     */
    private function merge_styles( $default, $custom ) {
        foreach ( $custom as $key => $value ) {
            if ( is_array( $value ) && isset( $default[ $key ] ) && is_array( $default[ $key ] ) ) {
                $default[ $key ] = $this->merge_styles( $default[ $key ], $value );
            } else {
                $default[ $key ] = $value;
            }
        }
        return $default;
    }

    /**
     * Obtiene una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );

        return new WP_REST_Response( array(
            'id'       => $post_id,
            'title'    => $post->post_title,
            'status'   => $post->post_status,
            'type'     => $post->post_type,
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
            'vbp_data' => $vbp_data,
        ), 200 );
    }

    /**
     * Actualiza una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_page( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        $updates = array( 'ID' => $post_id );

        if ( $request->has_param( 'title' ) ) {
            $updates['post_title'] = $request->get_param( 'title' );
        }

        if ( $request->has_param( 'status' ) ) {
            $updates['post_status'] = $request->get_param( 'status' );
        }

        if ( count( $updates ) > 1 ) {
            wp_update_post( $updates );
        }

        // Obtener datos VBP actuales
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array(
            'version'  => '2.0.15',
            'elements' => array(),
            'settings' => array(),
        );

        $data_changed = false;

        // Actualizar elementos VBP
        if ( $request->has_param( 'elements' ) ) {
            $vbp_data['elements'] = $this->prepare_elements( $request->get_param( 'elements' ) );
            $data_changed = true;
        }

        // Actualizar settings
        if ( $request->has_param( 'settings' ) ) {
            $new_settings = $request->get_param( 'settings' );
            if ( is_array( $new_settings ) ) {
                // Merge con settings existentes
                $vbp_data['settings'] = array_merge(
                    $vbp_data['settings'] ?? array(),
                    $new_settings
                );
                $data_changed = true;
            }
        }

        // Aplicar design preset
        if ( $request->has_param( 'design_preset' ) ) {
            $preset_name = $request->get_param( 'design_preset' );
            $preset_settings = $this->get_design_preset( $preset_name );
            if ( $preset_settings ) {
                $vbp_data['settings'] = array_merge(
                    $vbp_data['settings'] ?? array(),
                    $preset_settings
                );
                $vbp_data['settings']['design_preset'] = $preset_name;
                $data_changed = true;
            }
        }

        if ( $data_changed ) {
            update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        }

        $post = get_post( $post_id );
        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $post->post_title,
            'status'   => $post->post_status,
            'settings' => $vbp_data['settings'] ?? array(),
        ), 200 );
    }

    /**
     * Obtiene configuración de un preset de diseño
     *
     * @param string $preset_name Nombre del preset.
     * @return array|null Configuración del preset o null si no existe.
     */
    private function get_design_preset( $preset_name ) {
        $presets = array(
            'modern' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'fullWidth'       => true,
                'primaryColor'    => '#3b82f6',
                'secondaryColor'  => '#8b5cf6',
                'borderRadius'    => '12px',
                'fontFamily'      => 'Inter, sans-serif',
            ),
            'corporate' => array(
                'pageWidth'       => 1140,
                'backgroundColor' => '#f8fafc',
                'fullWidth'       => false,
                'primaryColor'    => '#1e40af',
                'secondaryColor'  => '#0369a1',
                'borderRadius'    => '4px',
                'fontFamily'      => 'Roboto, sans-serif',
            ),
            'nature' => array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#f0fdf4',
                'fullWidth'       => true,
                'primaryColor'    => '#15803d',
                'secondaryColor'  => '#22c55e',
                'borderRadius'    => '16px',
                'fontFamily'      => 'Nunito, sans-serif',
            ),
            'bold' => array(
                'pageWidth'       => 1400,
                'backgroundColor' => '#0f172a',
                'fullWidth'       => true,
                'primaryColor'    => '#f97316',
                'secondaryColor'  => '#eab308',
                'borderRadius'    => '0px',
                'fontFamily'      => 'Montserrat, sans-serif',
                'textColor'       => '#ffffff',
            ),
            'minimal' => array(
                'pageWidth'       => 800,
                'backgroundColor' => '#ffffff',
                'fullWidth'       => false,
                'primaryColor'    => '#171717',
                'secondaryColor'  => '#525252',
                'borderRadius'    => '2px',
                'fontFamily'      => 'Source Sans Pro, sans-serif',
            ),
        );

        return $presets[ $preset_name ] ?? null;
    }

    /**
     * Añade un bloque a una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function add_block( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $block_type = $request->get_param( 'type' );
        $block_data = $request->get_param( 'data' );
        $position = $request->get_param( 'position' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array(
            'version'  => '2.0.15',
            'elements' => array(),
            'settings' => array(),
        );

        // Crear elemento
        $element_id = 'el_' . bin2hex( random_bytes( 6 ) );
        $elemento = array(
            'id'       => $element_id,
            'type'     => $block_type,
            'name'     => ucfirst( str_replace( '_', ' ', $block_type ) ),
            'visible'  => true,
            'locked'   => false,
            'data'     => $block_data ?: array(),
            'styles'   => $this->get_default_styles(),
            'children' => array(),
        );

        // Insertar en posición
        if ( 'start' === $position ) {
            array_unshift( $vbp_data['elements'], $elemento );
        } elseif ( 'end' === $position ) {
            $vbp_data['elements'][] = $elemento;
        } else {
            $pos = absint( $position );
            array_splice( $vbp_data['elements'], $pos, 0, array( $elemento ) );
        }

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'    => true,
            'element_id' => $element_id,
            'block_type' => $block_type,
        ), 201 );
    }

    /**
     * Lista páginas VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_pages( $request ) {
        $status = $request->get_param( 'status' );

        $args = array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => 50,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        if ( 'any' !== $status ) {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = array( 'publish', 'draft', 'pending' );
        }

        $pages = get_posts( $args );
        $result = array();

        foreach ( $pages as $page ) {
            $vbp_data = get_post_meta( $page->ID, '_flavor_vbp_data', true );
            $result[] = array(
                'id'             => $page->ID,
                'title'          => $page->post_title,
                'status'         => $page->post_status,
                'modified'       => $page->post_modified,
                'elements_count' => count( $vbp_data['elements'] ?? array() ),
                'edit_url'       => admin_url( "admin.php?page=vbp-editor&post_id={$page->ID}" ),
            );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Genera una sección con contenido plantilla
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_section( $request ) {
        $type = $request->get_param( 'type' );
        $context = $request->get_param( 'context' );

        $section = $this->create_section( $type, $context );

        if ( ! $section ) {
            return new WP_REST_Response( array( 'error' => 'Tipo de sección no válido' ), 400 );
        }

        return new WP_REST_Response( $section, 200 );
    }

    /**
     * Lista las plantillas de página disponibles
     *
     * @return WP_REST_Response
     */
    public function list_templates() {
        $templates = $this->get_all_templates();
        $result = array();

        foreach ( $templates as $slug => $template ) {
            $result[] = array(
                'slug'        => $slug,
                'name'        => $template['name'],
                'description' => $template['description'],
                'industry'    => $template['industry'],
                'sections'    => $template['sections'],
            );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Obtiene los presets de un bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_presets( $request ) {
        $block_type = $request->get_param( 'type' );

        if ( ! $this->ensure_vbp_loaded() ) {
            return new WP_REST_Response( array( 'error' => 'VBP no disponible' ), 500 );
        }

        $libreria = Flavor_VBP_Block_Library::get_instance();
        $bloque = $libreria->get_bloque( $block_type );

        if ( ! $bloque ) {
            return new WP_REST_Response( array( 'error' => 'Bloque no encontrado' ), 404 );
        }

        $presets = array();
        if ( ! empty( $bloque['presets'] ) ) {
            foreach ( $bloque['presets'] as $preset_id => $preset ) {
                $presets[] = array(
                    'id'   => $preset_id,
                    'name' => $preset['name'] ?? $preset_id,
                    'data' => $preset['data'] ?? array(),
                );
            }
        }

        return new WP_REST_Response( array(
            'block_type' => $block_type,
            'presets'    => $presets,
        ), 200 );
    }

    /**
     * Duplica una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function duplicate_page( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $new_title = $request->get_param( 'title' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        // Crear nuevo post duplicado
        $new_post_id = wp_insert_post( array(
            'post_title'  => $new_title ?: $post->post_title . ' (copia)',
            'post_type'   => $post->post_type,
            'post_status' => 'draft',
        ) );

        if ( is_wp_error( $new_post_id ) ) {
            return new WP_REST_Response( array( 'error' => $new_post_id->get_error_message() ), 500 );
        }

        // Copiar meta datos VBP
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );
        $vbp_version = get_post_meta( $post_id, '_flavor_vbp_version', true );

        if ( $vbp_data ) {
            // Regenerar IDs de elementos para evitar conflictos
            if ( ! empty( $vbp_data['elements'] ) ) {
                $vbp_data['elements'] = $this->regenerate_element_ids( $vbp_data['elements'] );
            }
            update_post_meta( $new_post_id, '_flavor_vbp_data', $vbp_data );
        }
        if ( $vbp_version ) {
            update_post_meta( $new_post_id, '_flavor_vbp_version', $vbp_version );
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'id'          => $new_post_id,
            'original_id' => $post_id,
            'title'       => get_the_title( $new_post_id ),
            'edit_url'    => admin_url( "admin.php?page=vbp-editor&post_id={$new_post_id}" ),
        ), 201 );
    }

    /**
     * Regenera IDs de elementos recursivamente
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function regenerate_element_ids( $elements ) {
        foreach ( $elements as &$element ) {
            $element['id'] = 'el_' . bin2hex( random_bytes( 6 ) );
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->regenerate_element_ids( $element['children'] );
            }
        }
        return $elements;
    }

    /**
     * Lista los tipos de sección disponibles para generación
     *
     * @return WP_REST_Response
     */
    public function list_section_types() {
        $section_types = array(
            array(
                'type'        => 'hero',
                'name'        => 'Hero',
                'description' => 'Sección de cabecera principal con título, subtítulo y CTA.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'features',
                'name'        => 'Características',
                'description' => 'Grid de características o beneficios con iconos.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'cta',
                'name'        => 'Llamada a acción',
                'description' => 'Sección para incitar a la acción.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'testimonials',
                'name'        => 'Testimonios',
                'description' => 'Testimonios de clientes o usuarios.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'faq',
                'name'        => 'Preguntas frecuentes',
                'description' => 'Acordeón de preguntas y respuestas.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'pricing',
                'name'        => 'Precios',
                'description' => 'Tabla de planes y precios.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'stats',
                'name'        => 'Estadísticas',
                'description' => 'Números y métricas destacadas.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'team',
                'name'        => 'Equipo',
                'description' => 'Presentación del equipo.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'contact',
                'name'        => 'Contacto',
                'description' => 'Formulario e información de contacto.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'gallery',
                'name'        => 'Galería',
                'description' => 'Grid de imágenes.',
                'category'    => 'sections',
            ),
            array(
                'type'        => 'text',
                'name'        => 'Texto',
                'description' => 'Bloque de texto libre.',
                'category'    => 'basic',
            ),
            array(
                'type'        => 'module_grupos_consumo',
                'name'        => 'Grupos de Consumo',
                'description' => 'Widget del módulo de grupos de consumo.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_eventos',
                'name'        => 'Eventos',
                'description' => 'Widget del módulo de eventos.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_marketplace',
                'name'        => 'Marketplace',
                'description' => 'Widget del módulo marketplace.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_cursos',
                'name'        => 'Cursos',
                'description' => 'Widget del módulo de cursos.',
                'category'    => 'modules',
            ),
            // === SECCIONES ESPECÍFICAS COMUNITARIAS ===
            array(
                'type'        => 'module_crowdfunding',
                'name'        => 'Crowdfunding',
                'description' => 'Widget de proyectos de financiación colectiva.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_socios',
                'name'        => 'Socios',
                'description' => 'Sección de captación de socios con beneficios.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_participacion',
                'name'        => 'Participación',
                'description' => 'Widget de participación ciudadana.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_transparencia',
                'name'        => 'Transparencia',
                'description' => 'Portal de transparencia con presupuestos.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_banco_tiempo',
                'name'        => 'Banco de Tiempo',
                'description' => 'Intercambio de servicios y habilidades.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'module_reservas',
                'name'        => 'Reservas',
                'description' => 'Sistema de reservas de espacios/recursos.',
                'category'    => 'modules',
            ),
            array(
                'type'        => 'como_funciona',
                'name'        => 'Cómo Funciona',
                'description' => 'Sección explicativa con pasos numerados.',
                'category'    => 'community',
            ),
            array(
                'type'        => 'valores',
                'name'        => 'Nuestros Valores',
                'description' => 'Sección de valores y principios.',
                'category'    => 'community',
            ),
            array(
                'type'        => 'impacto',
                'name'        => 'Nuestro Impacto',
                'description' => 'Estadísticas de impacto social/ambiental.',
                'category'    => 'community',
            ),
            array(
                'type'        => 'proyectos_destacados',
                'name'        => 'Proyectos Destacados',
                'description' => 'Grid de proyectos con progreso.',
                'category'    => 'community',
            ),
            array(
                'type'        => 'donacion',
                'name'        => 'Sección de Donación',
                'description' => 'CTA para donaciones con opciones.',
                'category'    => 'community',
            ),
            array(
                'type'        => 'mapa_comunidad',
                'name'        => 'Mapa de Comunidad',
                'description' => 'Mapa interactivo de puntos/miembros.',
                'category'    => 'community',
            ),
        );

        return new WP_REST_Response( $section_types, 200 );
    }

    /**
     * Crea una sección con datos predeterminados
     *
     * @param string $type    Tipo de sección.
     * @param array  $context Contexto.
     * @return array|null
     */
    private function create_section( $type, $context = array() ) {
        $topic = $context['topic'] ?? $context['titulo'] ?? 'Tu producto';
        $industry = $context['industry'] ?? 'general';
        $subtitulo = $context['subtitulo'] ?? '';
        $boton_texto = $context['boton_texto'] ?? '';

        // Mapeo de industrias a contenido
        $industry_data = $this->get_industry_defaults( $industry );

        $sections = array(
            'hero' => array(
                'titulo'        => $topic,
                'subtitulo'     => $subtitulo ?: $industry_data['hero_subtitulo'],
                'boton_texto'   => $boton_texto ?: $industry_data['cta_texto'],
                'boton_url'     => $context['boton_url'] ?? '#contacto',
                'boton_2_texto' => $context['boton_2_texto'] ?? 'Saber más',
                'boton_2_url'   => $context['boton_2_url'] ?? '#caracteristicas',
                'imagen_fondo'  => $context['imagen_fondo'] ?? '',
                'color_fondo'   => $context['color_fondo'] ?? '#1a1a2e',
            ),
            'features' => array(
                'titulo'   => $context['titulo'] ?? 'Por qué elegirnos',
                'subtitulo'=> $subtitulo ?: 'Descubre las ventajas que nos hacen únicos',
                'columnas' => $context['columnas'] ?? 3,
                'items'    => $context['items'] ?? $industry_data['features'],
            ),
            'cta' => array(
                'titulo'      => $context['titulo'] ?? '¿Listo para empezar?',
                'subtitulo'   => $subtitulo ?: 'Únete a miles de usuarios satisfechos',
                'boton_texto' => $boton_texto ?: $industry_data['cta_texto'],
                'boton_url'   => $context['boton_url'] ?? '#contacto',
            ),
            'testimonials' => array(
                'titulo'       => $context['titulo'] ?? 'Lo que dicen nuestros clientes',
                'subtitulo'    => $subtitulo ?: 'Experiencias reales de usuarios satisfechos',
                'mostrar_rating' => true,
                'testimonios'  => $context['testimonios'] ?? $industry_data['testimonios'],
            ),
            'faq' => array(
                'titulo'   => $context['titulo'] ?? 'Preguntas frecuentes',
                'subtitulo'=> $subtitulo ?: 'Resolvemos tus dudas más comunes',
                'faqs'     => $context['faqs'] ?? $industry_data['faqs'],
            ),
            'pricing' => array(
                'titulo'    => $context['titulo'] ?? 'Planes y precios',
                'subtitulo' => $subtitulo ?: 'Elige el plan que mejor se adapte a tus necesidades',
                'moneda'    => $context['moneda'] ?? '€',
                'periodo'   => $context['periodo'] ?? 'mes',
                'planes'    => $context['planes'] ?? $industry_data['planes'],
            ),
            'stats' => array(
                'titulo' => $context['titulo'] ?? 'Números que hablan',
                'stats'  => $context['stats'] ?? array(
                    array( 'numero' => '10K+', 'label' => 'Usuarios activos', 'icono' => '👥' ),
                    array( 'numero' => '98%', 'label' => 'Satisfacción', 'icono' => '⭐' ),
                    array( 'numero' => '24/7', 'label' => 'Soporte', 'icono' => '💬' ),
                    array( 'numero' => '50+', 'label' => 'Países', 'icono' => '🌍' ),
                ),
            ),
            'team' => array(
                'titulo'   => $context['titulo'] ?? 'Nuestro equipo',
                'subtitulo'=> $subtitulo ?: 'Profesionales comprometidos con tu éxito',
                'columnas' => $context['columnas'] ?? 4,
                'miembros' => $context['miembros'] ?? array(
                    array( 'nombre' => 'Ana García', 'cargo' => 'CEO & Fundadora', 'bio' => 'Líder visionaria con más de 15 años de experiencia.' ),
                    array( 'nombre' => 'Carlos López', 'cargo' => 'CTO', 'bio' => 'Experto en tecnología e innovación.' ),
                    array( 'nombre' => 'María Sánchez', 'cargo' => 'CMO', 'bio' => 'Estratega de marketing digital.' ),
                    array( 'nombre' => 'David Martín', 'cargo' => 'COO', 'bio' => 'Optimizador de operaciones.' ),
                ),
            ),
            'contact' => array(
                'titulo'            => $context['titulo'] ?? 'Contacta con nosotros',
                'subtitulo'         => $subtitulo ?: 'Estamos aquí para ayudarte',
                'mostrar_formulario'=> true,
                'email'             => $context['email'] ?? 'info@ejemplo.com',
                'telefono'          => $context['telefono'] ?? '+34 600 000 000',
                'direccion'         => $context['direccion'] ?? '',
            ),
            'gallery' => array(
                'titulo'   => $context['titulo'] ?? 'Galería',
                'columnas' => $context['columnas'] ?? 3,
                'imagenes' => $context['imagenes'] ?? array(),
            ),
            'text' => array(
                'contenido' => $context['contenido'] ?? '<h2>Título de sección</h2><p>Contenido de texto personalizable.</p>',
                'alineacion'=> $context['alineacion'] ?? 'left',
            ),
            // Widgets de módulos Flavor
            'module_grupos_consumo' => array(
                'titulo'          => $context['titulo'] ?? 'Grupos de Consumo',
                'mostrar_mapa'    => $context['mostrar_mapa'] ?? true,
                'limite'          => $context['limite'] ?? 6,
                'mostrar_filtros' => $context['mostrar_filtros'] ?? true,
            ),
            'module_eventos' => array(
                'titulo'       => $context['titulo'] ?? 'Próximos Eventos',
                'limite'       => $context['limite'] ?? 4,
                'mostrar_fecha'=> true,
            ),
            'module_marketplace' => array(
                'titulo'    => $context['titulo'] ?? 'Productos Destacados',
                'categoria' => $context['categoria'] ?? '',
                'limite'    => $context['limite'] ?? 8,
            ),
            'module_cursos' => array(
                'titulo' => $context['titulo'] ?? 'Catálogo de Cursos',
                'limite' => $context['limite'] ?? 6,
            ),
            // === SECCIONES COMUNITARIAS ===
            'module_crowdfunding' => array(
                'titulo'          => $context['titulo'] ?? 'Apoya Nuestros Proyectos',
                'subtitulo'       => $subtitulo ?: 'Financiación colectiva para proyectos comunitarios',
                'limite'          => $context['limite'] ?? 6,
                'mostrar_progreso'=> true,
                'columnas'        => 3,
            ),
            'module_socios' => array(
                'titulo'   => $context['titulo'] ?? 'Hazte Socio/a',
                'subtitulo'=> $subtitulo ?: 'Únete a nuestra comunidad y disfruta de todos los beneficios',
                'mostrar_beneficios' => true,
                'mostrar_formulario' => true,
            ),
            'module_participacion' => array(
                'titulo'   => $context['titulo'] ?? 'Participa',
                'subtitulo'=> $subtitulo ?: 'Tu voz importa. Participa en las decisiones de la comunidad',
                'mostrar_propuestas' => true,
                'mostrar_votaciones' => true,
            ),
            'module_transparencia' => array(
                'titulo'   => $context['titulo'] ?? 'Transparencia',
                'subtitulo'=> $subtitulo ?: 'Conoce cómo gestionamos los recursos de la comunidad',
                'mostrar_presupuesto' => true,
                'mostrar_actas'       => true,
            ),
            'module_banco_tiempo' => array(
                'titulo'   => $context['titulo'] ?? 'Banco de Tiempo',
                'subtitulo'=> $subtitulo ?: 'Intercambia servicios y habilidades con otros miembros',
                'limite'   => $context['limite'] ?? 8,
            ),
            'module_reservas' => array(
                'titulo'   => $context['titulo'] ?? 'Reserva Espacios',
                'subtitulo'=> $subtitulo ?: 'Consulta disponibilidad y reserva los espacios que necesites',
                'mostrar_calendario' => true,
            ),
            'como_funciona' => array(
                'titulo'   => $context['titulo'] ?? 'Cómo Funciona',
                'subtitulo'=> $subtitulo ?: 'Es muy sencillo participar',
                'pasos'    => $context['pasos'] ?? array(
                    array( 'numero' => '1', 'titulo' => 'Regístrate', 'descripcion' => 'Crea tu cuenta en menos de 2 minutos', 'icono' => '📝' ),
                    array( 'numero' => '2', 'titulo' => 'Explora', 'descripcion' => 'Descubre todo lo que tenemos para ti', 'icono' => '🔍' ),
                    array( 'numero' => '3', 'titulo' => 'Participa', 'descripcion' => 'Únete a actividades y proyectos', 'icono' => '🤝' ),
                    array( 'numero' => '4', 'titulo' => 'Disfruta', 'descripcion' => 'Forma parte de una comunidad activa', 'icono' => '🎉' ),
                ),
            ),
            'valores' => array(
                'titulo'   => $context['titulo'] ?? 'Nuestros Valores',
                'subtitulo'=> $subtitulo ?: 'Los principios que guían nuestra comunidad',
                'valores'  => $context['valores'] ?? array(
                    array( 'titulo' => 'Cooperación', 'descripcion' => 'Trabajamos juntos por objetivos comunes', 'icono' => '🤝' ),
                    array( 'titulo' => 'Sostenibilidad', 'descripcion' => 'Cuidamos el planeta y las personas', 'icono' => '🌱' ),
                    array( 'titulo' => 'Transparencia', 'descripcion' => 'Actuamos de forma abierta y honesta', 'icono' => '✨' ),
                    array( 'titulo' => 'Solidaridad', 'descripcion' => 'Nadie se queda atrás en nuestra comunidad', 'icono' => '💚' ),
                ),
            ),
            'impacto' => array(
                'titulo' => $context['titulo'] ?? 'Nuestro Impacto',
                'subtitulo'=> $subtitulo ?: 'Resultados tangibles de nuestro trabajo colectivo',
                'stats'  => $context['stats'] ?? array(
                    array( 'numero' => '500+', 'label' => 'Socios activos', 'icono' => '👥' ),
                    array( 'numero' => '50K€', 'label' => 'Recaudados', 'icono' => '💰' ),
                    array( 'numero' => '120', 'label' => 'Proyectos apoyados', 'icono' => '🎯' ),
                    array( 'numero' => '10T', 'label' => 'CO2 evitado', 'icono' => '🌍' ),
                ),
            ),
            'proyectos_destacados' => array(
                'titulo'   => $context['titulo'] ?? 'Proyectos Destacados',
                'subtitulo'=> $subtitulo ?: 'Conoce los proyectos que estamos impulsando',
                'limite'   => $context['limite'] ?? 3,
                'mostrar_progreso' => true,
            ),
            'donacion' => array(
                'titulo'      => $context['titulo'] ?? 'Apóyanos',
                'subtitulo'   => $subtitulo ?: 'Tu aportación hace posible que sigamos adelante',
                'boton_texto' => $boton_texto ?: 'Hacer una donación',
                'boton_url'   => $context['boton_url'] ?? '#donar',
                'opciones'    => $context['opciones'] ?? array(
                    array( 'cantidad' => 5, 'etiqueta' => 'Café' ),
                    array( 'cantidad' => 20, 'etiqueta' => 'Apoyo', 'destacado' => true ),
                    array( 'cantidad' => 50, 'etiqueta' => 'Impulsor' ),
                    array( 'cantidad' => 0, 'etiqueta' => 'Otra cantidad' ),
                ),
            ),
            'mapa_comunidad' => array(
                'titulo'   => $context['titulo'] ?? 'Encuéntranos',
                'subtitulo'=> $subtitulo ?: 'Puntos de encuentro y miembros de la red',
                'altura'   => $context['altura'] ?? '400px',
                'mostrar_listado' => true,
            ),
        );

        if ( ! isset( $sections[ $type ] ) ) {
            return null;
        }

        // Nombre legible para el elemento
        $nombres_legibles = array(
            'hero'                   => 'Hero',
            'features'               => 'Características',
            'cta'                    => 'Llamada a acción',
            'testimonials'           => 'Testimonios',
            'faq'                    => 'FAQ',
            'pricing'                => 'Precios',
            'stats'                  => 'Estadísticas',
            'team'                   => 'Equipo',
            'contact'                => 'Contacto',
            'gallery'                => 'Galería',
            'text'                   => 'Texto',
            'module_grupos_consumo'  => 'Grupos de Consumo',
            'module_eventos'         => 'Eventos',
            'module_marketplace'     => 'Marketplace',
            'module_cursos'          => 'Cursos',
            // Secciones comunitarias
            'module_crowdfunding'    => 'Crowdfunding',
            'module_socios'          => 'Socios',
            'module_participacion'   => 'Participación',
            'module_transparencia'   => 'Transparencia',
            'module_banco_tiempo'    => 'Banco de Tiempo',
            'module_reservas'        => 'Reservas',
            'como_funciona'          => 'Cómo Funciona',
            'valores'                => 'Nuestros Valores',
            'impacto'                => 'Nuestro Impacto',
            'proyectos_destacados'   => 'Proyectos Destacados',
            'donacion'               => 'Donación',
            'mapa_comunidad'         => 'Mapa de Comunidad',
        );

        return array(
            'id'       => 'el_' . bin2hex( random_bytes( 6 ) ),
            'type'     => $type,
            'name'     => $nombres_legibles[ $type ] ?? ucfirst( str_replace( '_', ' ', $type ) ),
            'visible'  => true,
            'locked'   => false,
            'data'     => $sections[ $type ],
            'styles'   => $this->get_default_styles(),
            'children' => array(),
        );
    }

    /**
     * Obtiene datos por defecto según la industria
     *
     * @param string $industry Industria.
     * @return array
     */
    private function get_industry_defaults( $industry ) {
        $defaults = array(
            'tech' => array(
                'hero_subtitulo' => 'La plataforma todo-en-uno para impulsar tu negocio digital',
                'cta_texto'      => 'Empieza gratis',
                'features'       => array(
                    array( 'icono' => '⚡', 'titulo' => 'Ultra rápido', 'descripcion' => 'Rendimiento optimizado para cargas instantáneas' ),
                    array( 'icono' => '🔒', 'titulo' => 'Seguro', 'descripcion' => 'Encriptación de extremo a extremo' ),
                    array( 'icono' => '🔄', 'titulo' => 'Sincronizado', 'descripcion' => 'Tus datos siempre actualizados' ),
                ),
                'testimonios'    => array(
                    array( 'texto' => 'Esta herramienta ha transformado nuestra productividad. La recomiendo totalmente.', 'nombre' => 'Carlos M.', 'cargo' => 'CTO, TechStartup', 'rating' => 5 ),
                    array( 'texto' => 'Fácil de usar y muy potente. Justo lo que necesitábamos.', 'nombre' => 'Laura P.', 'cargo' => 'Product Manager', 'rating' => 5 ),
                ),
                'faqs'           => array(
                    array( 'pregunta' => '¿Cómo empiezo?', 'respuesta' => 'Regístrate gratis y tendrás acceso inmediato a todas las funciones básicas.' ),
                    array( 'pregunta' => '¿Hay período de prueba?', 'respuesta' => 'Sí, ofrecemos 14 días de prueba gratuita con todas las funciones premium.' ),
                    array( 'pregunta' => '¿Puedo cancelar en cualquier momento?', 'respuesta' => 'Por supuesto. Sin compromisos ni penalizaciones.' ),
                ),
                'planes'         => array(
                    array( 'nombre' => 'Starter', 'precio' => 0, 'descripcion' => 'Para empezar', 'caracteristicas' => "3 proyectos\n1GB almacenamiento\nSoporte comunidad" ),
                    array( 'nombre' => 'Pro', 'precio' => 29, 'destacado' => true, 'etiqueta' => 'Popular', 'descripcion' => 'Para equipos', 'caracteristicas' => "Proyectos ilimitados\n100GB almacenamiento\nSoporte prioritario\nIntegraciones avanzadas" ),
                    array( 'nombre' => 'Enterprise', 'precio' => 99, 'descripcion' => 'Para grandes empresas', 'caracteristicas' => "Todo ilimitado\nSoporte dedicado 24/7\nSLA garantizado\nAPI personalizada" ),
                ),
            ),
            'ecommerce' => array(
                'hero_subtitulo' => 'Descubre productos únicos seleccionados especialmente para ti',
                'cta_texto'      => 'Ver productos',
                'features'       => array(
                    array( 'icono' => '🚚', 'titulo' => 'Envío gratis', 'descripcion' => 'En pedidos superiores a 50€' ),
                    array( 'icono' => '↩️', 'titulo' => 'Devolución fácil', 'descripcion' => '30 días para devolver' ),
                    array( 'icono' => '💳', 'titulo' => 'Pago seguro', 'descripcion' => 'Múltiples métodos de pago' ),
                ),
                'testimonios'    => array(
                    array( 'texto' => 'Productos de excelente calidad y envío rapidísimo. Volveré a comprar seguro.', 'nombre' => 'María L.', 'cargo' => 'Cliente verificado', 'rating' => 5 ),
                    array( 'texto' => 'Gran atención al cliente. Resolvieron mi duda en minutos.', 'nombre' => 'Pedro R.', 'cargo' => 'Cliente desde 2022', 'rating' => 5 ),
                ),
                'faqs'           => array(
                    array( 'pregunta' => '¿Cuánto tarda el envío?', 'respuesta' => 'Los pedidos se envían en 24-48h laborables.' ),
                    array( 'pregunta' => '¿Puedo hacer seguimiento?', 'respuesta' => 'Sí, recibirás un enlace de seguimiento por email.' ),
                ),
                'planes'         => array(),
            ),
            'community' => array(
                'hero_subtitulo' => 'Únete a una comunidad comprometida con el cambio positivo',
                'cta_texto'      => 'Unirse ahora',
                'features'       => array(
                    array( 'icono' => '🤝', 'titulo' => 'Colaboración', 'descripcion' => 'Trabajamos juntos por un objetivo común' ),
                    array( 'icono' => '🌱', 'titulo' => 'Sostenibilidad', 'descripcion' => 'Comprometidos con el medio ambiente' ),
                    array( 'icono' => '💚', 'titulo' => 'Comunidad', 'descripcion' => 'Más de 5000 miembros activos' ),
                ),
                'testimonios'    => array(
                    array( 'texto' => 'Encontré mi tribu. Gente increíble con valores compartidos.', 'nombre' => 'Ana S.', 'cargo' => 'Socia desde 2021', 'rating' => 5 ),
                ),
                'faqs'           => array(
                    array( 'pregunta' => '¿Cómo me hago socio/a?', 'respuesta' => 'Rellena el formulario de registro y te contactaremos.' ),
                    array( 'pregunta' => '¿Cuál es la cuota?', 'respuesta' => 'La cuota es de 10€/mes o 100€/año.' ),
                ),
                'planes'         => array(
                    array( 'nombre' => 'Simpatizante', 'precio' => 0, 'descripcion' => 'Mantente informado', 'caracteristicas' => "Newsletter mensual\nAcceso a eventos públicos" ),
                    array( 'nombre' => 'Socio/a', 'precio' => 10, 'destacado' => true, 'descripcion' => 'Participa activamente', 'caracteristicas' => "Voto en asambleas\nDescuentos exclusivos\nAcceso a todos los eventos\nGrupos de trabajo" ),
                ),
            ),
            'health' => array(
                'hero_subtitulo' => 'Tu bienestar es nuestra prioridad',
                'cta_texto'      => 'Reservar cita',
                'features'       => array(
                    array( 'icono' => '👨‍⚕️', 'titulo' => 'Profesionales', 'descripcion' => 'Equipo médico cualificado' ),
                    array( 'icono' => '🏥', 'titulo' => 'Instalaciones', 'descripcion' => 'Tecnología de última generación' ),
                    array( 'icono' => '❤️', 'titulo' => 'Atención personalizada', 'descripcion' => 'Cada paciente es único' ),
                ),
                'testimonios'    => array(
                    array( 'texto' => 'Excelente atención médica. Me sentí muy bien cuidado/a.', 'nombre' => 'Roberto M.', 'cargo' => 'Paciente', 'rating' => 5 ),
                ),
                'faqs'           => array(
                    array( 'pregunta' => '¿Aceptan seguros médicos?', 'respuesta' => 'Sí, trabajamos con las principales aseguradoras.' ),
                ),
                'planes'         => array(),
            ),
            'food' => array(
                'hero_subtitulo' => 'Sabores auténticos que conquistan el paladar',
                'cta_texto'      => 'Ver carta',
                'features'       => array(
                    array( 'icono' => '🥗', 'titulo' => 'Ingredientes frescos', 'descripcion' => 'Productos de temporada y proximidad' ),
                    array( 'icono' => '👨‍🍳', 'titulo' => 'Chef experto', 'descripcion' => 'Cocina de autor' ),
                    array( 'icono' => '🌿', 'titulo' => 'Opciones saludables', 'descripcion' => 'Vegetariano, vegano, sin gluten' ),
                ),
                'testimonios'    => array(
                    array( 'texto' => 'La mejor experiencia gastronómica de la ciudad. Volveré seguro.', 'nombre' => 'Elena G.', 'cargo' => 'Food blogger', 'rating' => 5 ),
                ),
                'faqs'           => array(
                    array( 'pregunta' => '¿Hacéis envíos a domicilio?', 'respuesta' => 'Sí, a través de las principales plataformas.' ),
                    array( 'pregunta' => '¿Tenéis menú del día?', 'respuesta' => 'Sí, de lunes a viernes de 13:00 a 16:00.' ),
                ),
                'planes'         => array(),
            ),
        );

        return $defaults[ $industry ] ?? array(
            'hero_subtitulo' => 'La mejor solución para tu negocio',
            'cta_texto'      => 'Comenzar ahora',
            'features'       => array(
                array( 'icono' => '⚡', 'titulo' => 'Rápido', 'descripcion' => 'Implementación inmediata' ),
                array( 'icono' => '🔒', 'titulo' => 'Seguro', 'descripcion' => 'Máxima protección' ),
                array( 'icono' => '📱', 'titulo' => 'Accesible', 'descripcion' => 'Desde cualquier dispositivo' ),
            ),
            'testimonios'    => array(
                array( 'texto' => 'Excelente servicio. Lo recomiendo totalmente.', 'nombre' => 'Usuario satisfecho', 'cargo' => 'Cliente', 'rating' => 5 ),
            ),
            'faqs'           => array(
                array( 'pregunta' => '¿Cómo empiezo?', 'respuesta' => 'Es muy sencillo, contáctanos y te guiamos en todo el proceso.' ),
                array( 'pregunta' => '¿Tienen soporte?', 'respuesta' => 'Sí, contamos con soporte técnico disponible.' ),
            ),
            'planes'         => array(),
        );
    }

    /**
     * Obtiene elementos de una plantilla
     *
     * @param string $template Nombre de plantilla.
     * @param array  $context  Contexto para personalización.
     * @return array
     */
    private function get_template_elements( $template, $context = array() ) {
        $templates = $this->get_all_templates();

        if ( ! isset( $templates[ $template ] ) ) {
            return array();
        }

        $template_data = $templates[ $template ];
        $industry = $template_data['industry'] ?? $context['industry'] ?? 'general';
        $context['industry'] = $industry;

        $elements = array();
        foreach ( $template_data['sections'] as $type ) {
            $section = $this->create_section( $type, $context );
            if ( $section ) {
                $elements[] = $section;
            }
        }

        return $elements;
    }

    /**
     * Obtiene todas las plantillas disponibles
     *
     * @return array
     */
    private function get_all_templates() {
        return array(
            'landing-basica' => array(
                'name'        => 'Landing Básica',
                'description' => 'Hero + Features + CTA. Perfecta para empezar.',
                'industry'    => 'general',
                'sections'    => array( 'hero', 'features', 'cta' ),
            ),
            'landing-completa' => array(
                'name'        => 'Landing Completa',
                'description' => 'Todas las secciones típicas de una landing page profesional.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'features', 'stats', 'testimonials', 'pricing', 'faq', 'cta' ),
            ),
            'landing-producto' => array(
                'name'        => 'Landing de Producto',
                'description' => 'Ideal para presentar un producto o servicio.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'features', 'gallery', 'testimonials', 'pricing', 'cta' ),
            ),
            'landing-startup' => array(
                'name'        => 'Landing Startup',
                'description' => 'Diseño moderno para startups y empresas tech.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'stats', 'features', 'team', 'testimonials', 'pricing', 'faq', 'cta' ),
            ),
            'landing-saas' => array(
                'name'        => 'Landing SaaS',
                'description' => 'Optimizada para software como servicio.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'features', 'stats', 'pricing', 'testimonials', 'faq', 'cta' ),
            ),
            'grupos-consumo' => array(
                'name'        => 'Grupos de Consumo',
                'description' => 'Landing para cooperativas y grupos de consumo.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'module_grupos_consumo', 'features', 'testimonials', 'faq', 'cta' ),
            ),
            'eventos' => array(
                'name'        => 'Portal de Eventos',
                'description' => 'Muestra los próximos eventos de tu comunidad.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'module_eventos', 'features', 'cta' ),
            ),
            'marketplace' => array(
                'name'        => 'Marketplace',
                'description' => 'Tienda online con productos destacados.',
                'industry'    => 'ecommerce',
                'sections'    => array( 'hero', 'module_marketplace', 'features', 'testimonials', 'cta' ),
            ),
            'cursos' => array(
                'name'        => 'Plataforma de Cursos',
                'description' => 'Catálogo de cursos y formación.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'module_cursos', 'features', 'testimonials', 'faq', 'cta' ),
            ),
            'comunidad' => array(
                'name'        => 'Portal Comunitario',
                'description' => 'Landing para asociaciones y comunidades.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'features', 'stats', 'team', 'testimonials', 'cta' ),
            ),
            'restaurante' => array(
                'name'        => 'Restaurante / Bar',
                'description' => 'Perfecta para negocios de hostelería.',
                'industry'    => 'food',
                'sections'    => array( 'hero', 'features', 'gallery', 'testimonials', 'contact' ),
            ),
            'clinica' => array(
                'name'        => 'Clínica / Salud',
                'description' => 'Para centros médicos y de salud.',
                'industry'    => 'health',
                'sections'    => array( 'hero', 'features', 'team', 'testimonials', 'faq', 'contact' ),
            ),
            'tienda' => array(
                'name'        => 'E-commerce',
                'description' => 'Landing para tiendas online.',
                'industry'    => 'ecommerce',
                'sections'    => array( 'hero', 'features', 'testimonials', 'faq', 'cta' ),
            ),
            'servicios' => array(
                'name'        => 'Servicios Profesionales',
                'description' => 'Para freelancers y empresas de servicios.',
                'industry'    => 'general',
                'sections'    => array( 'hero', 'features', 'stats', 'testimonials', 'pricing', 'contact' ),
            ),
            'app-movil' => array(
                'name'        => 'App Móvil',
                'description' => 'Presenta tu aplicación móvil.',
                'industry'    => 'tech',
                'sections'    => array( 'hero', 'features', 'stats', 'testimonials', 'faq', 'cta' ),
            ),
            // === PLANTILLAS COMUNITARIAS ===
            'crowdfunding' => array(
                'name'        => 'Crowdfunding / Recaudación',
                'description' => 'Landing para proyectos de financiación colectiva.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'module_crowdfunding', 'como_funciona', 'impacto', 'testimonials', 'faq', 'donacion' ),
            ),
            'cooperativa' => array(
                'name'        => 'Cooperativa',
                'description' => 'Portal completo para cooperativas de trabajo o consumo.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'valores', 'features', 'module_socios', 'module_transparencia', 'team', 'cta' ),
            ),
            'asociacion-completa' => array(
                'name'        => 'Asociación Completa',
                'description' => 'Portal completo para asociaciones y ONGs.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'features', 'impacto', 'proyectos_destacados', 'module_socios', 'team', 'testimonials', 'faq', 'cta' ),
            ),
            'barrio-vecinal' => array(
                'name'        => 'Barrio / Vecindario',
                'description' => 'Portal para comunidades de vecinos y barrios.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'features', 'module_participacion', 'module_eventos', 'mapa_comunidad', 'cta' ),
            ),
            'espacio-cultural' => array(
                'name'        => 'Espacio Cultural',
                'description' => 'Centro cultural, coworking o hub comunitario.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'features', 'module_reservas', 'module_eventos', 'gallery', 'pricing', 'contact' ),
            ),
            'economia-social' => array(
                'name'        => 'Economía Social',
                'description' => 'Proyectos de economía solidaria y colaborativa.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'valores', 'module_banco_tiempo', 'module_marketplace', 'impacto', 'testimonials', 'cta' ),
            ),
            'captacion-socios' => array(
                'name'        => 'Captación de Socios',
                'description' => 'Landing optimizada para captar nuevos socios.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'features', 'como_funciona', 'testimonials', 'pricing', 'faq', 'cta' ),
            ),
            'transparencia-municipal' => array(
                'name'        => 'Transparencia Municipal',
                'description' => 'Portal de transparencia y participación.',
                'industry'    => 'community',
                'sections'    => array( 'hero', 'module_transparencia', 'module_participacion', 'stats', 'faq', 'contact' ),
            ),
        );
    }

    /**
     * Estilos por defecto
     *
     * @return array
     */
    private function get_default_styles() {
        return array(
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
    }

    // ========================================================================
    // NUEVOS MÉTODOS PARA AUTOMATIZACIÓN (v2.1.0)
    // ========================================================================

    /**
     * Publica una página VBP directamente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function publish_page( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        if ( $post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'error' => 'Solo se pueden publicar landings VBP' ), 400 );
        }

        // Publicar la página
        $result = wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        ), true );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'error'   => 'Error al publicar',
                'message' => $result->get_error_message(),
            ), 500 );
        }

        // Forzar flush de rewrite rules para asegurar que la URL funciona
        if ( class_exists( 'Flavor_Visual_Builder' ) && method_exists( 'Flavor_Visual_Builder', 'flush_permalinks' ) ) {
            Flavor_Visual_Builder::flush_permalinks();
        }

        // Obtener URL actualizada
        $permalink = get_permalink( $post_id );

        return new WP_REST_Response( array(
            'success'   => true,
            'id'        => $post_id,
            'title'     => $post->post_title,
            'status'    => 'publish',
            'url'       => $permalink,
            'edit_url'  => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'preview'   => rest_url( "flavor-vbp/v1/preview/{$post_id}" ),
        ), 200 );
    }

    /**
     * Obtiene la URL pública de una landing
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_url( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        $permalink = get_permalink( $post_id );
        $expected_url = home_url( '/landing/' . $post->post_name . '/' );

        // Verificar si la URL redirige (problema de permalinks)
        $url_ok = true;
        $redirect_issue = false;

        if ( $post->post_status === 'publish' ) {
            $response = wp_remote_head( $permalink, array(
                'timeout'     => 5,
                'redirection' => 0,
                'sslverify'   => false,
            ) );

            if ( ! is_wp_error( $response ) ) {
                $status_code = wp_remote_retrieve_response_code( $response );
                $headers = wp_remote_retrieve_headers( $response );

                if ( $status_code >= 300 && $status_code < 400 ) {
                    $redirect_issue = true;
                    $url_ok = false;
                }
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'id'             => $post_id,
            'title'          => $post->post_title,
            'status'         => $post->post_status,
            'url'            => $permalink,
            'expected_url'   => $expected_url,
            'url_matches'    => $permalink === $expected_url,
            'url_accessible' => $url_ok,
            'redirect_issue' => $redirect_issue,
            'preview_url'    => rest_url( "flavor-vbp/v1/preview/{$post_id}" ),
            'edit_url'       => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
        ), 200 );
    }

    /**
     * Regenera los permalinks del sistema
     *
     * @return WP_REST_Response
     */
    public function flush_permalinks() {
        // Verificar que el post type está registrado
        if ( ! post_type_exists( 'flavor_landing' ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Post type flavor_landing no está registrado',
            ), 500 );
        }

        // Usar el método de Visual Builder si está disponible
        if ( class_exists( 'Flavor_Visual_Builder' ) && method_exists( 'Flavor_Visual_Builder', 'flush_permalinks' ) ) {
            $result = Flavor_Visual_Builder::flush_permalinks();
        } else {
            // Flush directo
            flush_rewrite_rules( true );
            $result = true;
        }

        // Verificar resultado
        $rules = get_option( 'rewrite_rules', array() );
        $landing_rules = array();

        if ( is_array( $rules ) ) {
            foreach ( $rules as $pattern => $rewrite ) {
                if ( strpos( $rewrite, 'flavor_landing' ) !== false ) {
                    $landing_rules[ $pattern ] = $rewrite;
                }
            }
        }

        $success = ! empty( $landing_rules );

        return new WP_REST_Response( array(
            'success'        => $success,
            'message'        => $success
                ? 'Permalinks regenerados correctamente'
                : 'No se encontraron reglas para flavor_landing después del flush',
            'rules_count'    => count( $landing_rules ),
            'rules'          => $landing_rules,
            'permalink_structure' => get_option( 'permalink_structure' ),
        ), $success ? 200 : 500 );
    }

    /**
     * Valida las animaciones de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function validate_animations( $request ) {
        $post_id = absint( $request->get_param( 'id' ) );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Página no encontrada' ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );

        if ( empty( $vbp_data ) || empty( $vbp_data['elements'] ) ) {
            return new WP_REST_Response( array(
                'success'         => true,
                'id'              => $post_id,
                'has_animations'  => false,
                'animations'      => array(),
                'message'         => 'La página no tiene elementos o datos VBP',
            ), 200 );
        }

        // Animaciones válidas soportadas
        $valid_entrance = array(
            'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
            'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight',
            'zoomIn', 'zoomInUp', 'zoomInDown',
            'bounceIn', 'bounceInUp', 'bounceInDown',
            'flipInX', 'flipInY', 'rotateIn',
        );

        $valid_hover = array(
            'pulse', 'shake', 'bounce', 'swing', 'wobble', 'tada',
            'grow', 'shrink', 'float', 'sink',
            'skew', 'skewForward', 'skewBackward',
        );

        $valid_loop = array(
            'pulse', 'bounce', 'shake', 'swing', 'wobble',
            'flash', 'rubberBand', 'heartBeat', 'spin',
        );

        $animations = array();
        $issues = array();
        $warnings = array();

        foreach ( $vbp_data['elements'] as $index => $element ) {
            $element_id = $element['id'] ?? 'unknown_' . $index;
            $element_type = $element['type'] ?? 'unknown';
            $advanced = $element['styles']['advanced'] ?? array();

            $element_animations = array(
                'element_id'   => $element_id,
                'element_type' => $element_type,
                'entrance'     => null,
                'hover'        => null,
                'loop'         => null,
                'valid'        => true,
                'issues'       => array(),
            );

            // Validar animación de entrada
            if ( ! empty( $advanced['entranceAnimation'] ) ) {
                $entrance = $advanced['entranceAnimation'];
                $element_animations['entrance'] = array(
                    'type'     => $entrance,
                    'valid'    => in_array( $entrance, $valid_entrance, true ),
                    'duration' => $advanced['animDuration'] ?? '0.6s',
                    'delay'    => $advanced['animDelay'] ?? '0s',
                    'trigger'  => $advanced['animTrigger'] ?? 'scroll',
                    'easing'   => $advanced['animEasing'] ?? 'ease-out',
                );

                if ( ! $element_animations['entrance']['valid'] ) {
                    $element_animations['valid'] = false;
                    $element_animations['issues'][] = "Animación de entrada desconocida: {$entrance}";
                    $issues[] = "[{$element_id}] Animación de entrada desconocida: {$entrance}";
                }
            }

            // Validar animación hover
            if ( ! empty( $advanced['hoverAnimation'] ) ) {
                $hover = $advanced['hoverAnimation'];
                $element_animations['hover'] = array(
                    'type'  => $hover,
                    'valid' => in_array( $hover, $valid_hover, true ),
                );

                if ( ! $element_animations['hover']['valid'] ) {
                    $element_animations['valid'] = false;
                    $element_animations['issues'][] = "Animación hover desconocida: {$hover}";
                    $issues[] = "[{$element_id}] Animación hover desconocida: {$hover}";
                }
            }

            // Validar animación en loop
            if ( ! empty( $advanced['loopAnimation'] ) ) {
                $loop = $advanced['loopAnimation'];
                $element_animations['loop'] = array(
                    'type'  => $loop,
                    'valid' => in_array( $loop, $valid_loop, true ),
                );

                if ( ! $element_animations['loop']['valid'] ) {
                    $element_animations['valid'] = false;
                    $element_animations['issues'][] = "Animación loop desconocida: {$loop}";
                    $issues[] = "[{$element_id}] Animación loop desconocida: {$loop}";
                }
            }

            // Validar parallax
            if ( ! empty( $advanced['parallaxEnabled'] ) ) {
                $element_animations['parallax'] = array(
                    'enabled' => true,
                    'speed'   => $advanced['parallaxSpeed'] ?? 0.5,
                );
            }

            // Solo añadir si tiene alguna animación
            if ( $element_animations['entrance'] || $element_animations['hover'] || $element_animations['loop'] ) {
                $animations[] = $element_animations;
            }
        }

        $has_animations = ! empty( $animations );
        $all_valid = empty( $issues );

        return new WP_REST_Response( array(
            'success'         => true,
            'id'              => $post_id,
            'title'           => $post->post_title,
            'has_animations'  => $has_animations,
            'all_valid'       => $all_valid,
            'animations_count'=> count( $animations ),
            'animations'      => $animations,
            'issues'          => $issues,
            'warnings'        => $warnings,
            'valid_animations' => array(
                'entrance' => $valid_entrance,
                'hover'    => $valid_hover,
                'loop'     => $valid_loop,
            ),
        ), 200 );
    }

    /**
     * Obtiene el estado del sistema VBP
     *
     * @return WP_REST_Response
     */
    public function get_system_status() {
        $status = array(
            'timestamp'    => current_time( 'mysql' ),
            'api_version'  => '2.1.0',
            'vbp_loaded'   => $this->ensure_vbp_loaded(),
            'post_type'    => post_type_exists( 'flavor_landing' ),
            'classes'      => array(
                'VBP_Block_Library' => class_exists( 'Flavor_VBP_Block_Library' ),
                'VBP_Canvas'        => class_exists( 'Flavor_VBP_Canvas' ),
                'VBP_Editor'        => class_exists( 'Flavor_VBP_Editor' ),
                'Visual_Builder'    => class_exists( 'Flavor_Visual_Builder' ),
            ),
            'endpoints'    => array(
                'schema'           => rest_url( self::NAMESPACE . '/claude/schema' ),
                'blocks'           => rest_url( self::NAMESPACE . '/claude/blocks' ),
                'pages'            => rest_url( self::NAMESPACE . '/claude/pages' ),
                'templates'        => rest_url( self::NAMESPACE . '/claude/templates' ),
                'flush_permalinks' => rest_url( self::NAMESPACE . '/claude/flush-permalinks' ),
                'diagnostics'      => rest_url( 'flavor-vbp/v1/diagnostics/status' ),
            ),
            'landings'     => array(
                'total'     => wp_count_posts( 'flavor_landing' )->publish ?? 0,
                'draft'     => wp_count_posts( 'flavor_landing' )->draft ?? 0,
            ),
        );

        // Verificar rewrite rules
        $rules = get_option( 'rewrite_rules', array() );
        $has_rules = false;
        if ( is_array( $rules ) ) {
            foreach ( $rules as $rewrite ) {
                if ( strpos( $rewrite, 'flavor_landing' ) !== false ) {
                    $has_rules = true;
                    break;
                }
            }
        }
        $status['rewrite_rules_ok'] = $has_rules;
        $status['permalink_structure'] = get_option( 'permalink_structure' );

        // Determinar salud
        $healthy = $status['vbp_loaded'] && $status['post_type'] && $status['rewrite_rules_ok'];
        $status['health'] = $healthy ? 'ok' : 'issues';

        if ( ! $healthy ) {
            $status['issues'] = array();
            if ( ! $status['vbp_loaded'] ) {
                $status['issues'][] = 'VBP Block Library no cargada';
            }
            if ( ! $status['post_type'] ) {
                $status['issues'][] = 'Post type flavor_landing no registrado';
            }
            if ( ! $status['rewrite_rules_ok'] ) {
                $status['issues'][] = 'Rewrite rules no configuradas - usar POST /claude/flush-permalinks';
            }
        }

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * Valida elementos antes de crear una página
     *
     * Permite verificar que los datos son correctos antes de crear la página,
     * mostrando advertencias sobre campos faltantes o formatos incorrectos.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function validate_elements( $request ) {
        $elements = $request->get_param( 'elements' );

        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return new WP_REST_Response( array(
                'valid'    => false,
                'errors'   => array( 'No se proporcionaron elementos para validar' ),
                'warnings' => array(),
                'info'     => array(),
            ), 400 );
        }

        $errors = array();
        $warnings = array();
        $info = array();
        $normalized_preview = array();

        foreach ( $elements as $index => $element ) {
            $element_id = $element['id'] ?? "elemento_{$index}";
            $type = $element['type'] ?? null;

            // Error: tipo requerido
            if ( empty( $type ) ) {
                $errors[] = "[{$element_id}] El campo 'type' es requerido";
                continue;
            }

            // Obtener datos
            $data = $element['data'] ?? array();

            // Detectar campos en formato inglés que serán normalizados
            $english_fields_found = $this->detect_english_fields( $data );
            if ( ! empty( $english_fields_found ) ) {
                $fields_list = implode( ', ', $english_fields_found );
                $info[] = "[{$element_id}] Campos en inglés detectados y normalizados automáticamente: {$fields_list}";
            }

            // Normalizar para preview
            $normalized_data = $this->normalize_element_data( $type, $data );
            $normalized_data = $this->ensure_required_fields( $type, $normalized_data );

            // Validar campos específicos del tipo
            $type_issues = $this->validate_element_type( $type, $normalized_data, $element_id );
            $errors = array_merge( $errors, $type_issues['errors'] );
            $warnings = array_merge( $warnings, $type_issues['warnings'] );

            // Guardar preview normalizado
            $normalized_preview[] = array(
                'id'              => $element_id,
                'type'            => $type,
                'original_data'   => $data,
                'normalized_data' => $normalized_data,
            );
        }

        $valid = empty( $errors );

        return new WP_REST_Response( array(
            'valid'              => $valid,
            'errors'             => $errors,
            'warnings'           => $warnings,
            'info'               => $info,
            'elements_count'     => count( $elements ),
            'normalized_preview' => $normalized_preview,
            'message'            => $valid
                ? 'Todos los elementos son válidos. Se aplicará normalización automática.'
                : 'Se encontraron errores en los elementos. Revisa la lista de errores.',
        ), $valid ? 200 : 400 );
    }

    /**
     * Detecta campos en formato inglés en los datos
     *
     * @param array $data Datos del elemento.
     * @return array Campos en inglés encontrados.
     */
    private function detect_english_fields( $data ) {
        $english_fields = array(
            'title', 'subtitle', 'description', 'content', 'text',
            'buttonText', 'buttonUrl', 'button_text', 'button_url',
            'ctaText', 'ctaUrl', 'cta_text', 'cta_url',
            'image', 'backgroundImage', 'background_image',
            'backgroundColor', 'background_color', 'icon', 'link',
            'alignment', 'columns', 'secondButtonText', 'secondButtonUrl',
            'testimonials', 'plans', 'questions', 'images', 'members',
            'showRating', 'currency', 'period', 'showForm',
        );

        $found = array();
        foreach ( array_keys( $data ) as $key ) {
            if ( in_array( $key, $english_fields, true ) ) {
                $found[] = $key;
            }
        }

        return $found;
    }

    /**
     * Valida campos específicos según el tipo de elemento
     *
     * @param string $type       Tipo de elemento.
     * @param array  $data       Datos normalizados.
     * @param string $element_id ID del elemento para mensajes.
     * @return array Array con 'errors' y 'warnings'.
     */
    private function validate_element_type( $type, $data, $element_id ) {
        $errors = array();
        $warnings = array();

        switch ( $type ) {
            case 'hero':
                if ( empty( $data['titulo'] ) && empty( $data['subtitulo'] ) ) {
                    $warnings[] = "[{$element_id}] Hero sin título ni subtítulo - considera añadir contenido";
                }
                break;

            case 'features':
                if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
                    $warnings[] = "[{$element_id}] Features sin items definidos";
                } else {
                    foreach ( $data['items'] as $i => $item ) {
                        if ( empty( $item['titulo'] ) && empty( $item['title'] ) ) {
                            $warnings[] = "[{$element_id}] Feature item #{$i} sin título";
                        }
                    }
                }
                break;

            case 'testimonials':
                if ( empty( $data['testimonios'] ) || ! is_array( $data['testimonios'] ) ) {
                    $warnings[] = "[{$element_id}] Testimonials sin testimonios definidos";
                }
                break;

            case 'pricing':
                if ( empty( $data['planes'] ) || ! is_array( $data['planes'] ) ) {
                    $warnings[] = "[{$element_id}] Pricing sin planes definidos";
                }
                break;

            case 'faq':
                if ( empty( $data['faqs'] ) || ! is_array( $data['faqs'] ) ) {
                    $warnings[] = "[{$element_id}] FAQ sin preguntas definidas";
                }
                break;

            case 'section':
            case 'card':
                // Estos tipos son flexibles, solo advertir si están completamente vacíos
                if ( empty( $data['titulo'] ) && empty( $data['contenido'] ) && empty( $data['descripcion'] ) ) {
                    $warnings[] = "[{$element_id}] {$type} sin contenido visible";
                }
                break;

            case 'grid':
                if ( ! isset( $data['columnas'] ) || $data['columnas'] < 1 || $data['columnas'] > 12 ) {
                    $warnings[] = "[{$element_id}] Grid con número de columnas inválido (debe ser 1-12)";
                }
                break;
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
        );
    }

    // ========================================================================
    // SISTEMA DE DISEÑO AVANZADO PARA CLAUDE CODE (v2.2.0)
    // ========================================================================

    /**
     * Obtiene los presets de diseño disponibles
     *
     * @return WP_REST_Response
     */
    public function get_design_presets() {
        $presets = $this->get_all_design_presets();

        $result = array();
        foreach ( $presets as $key => $preset ) {
            $result[] = array(
                'id'          => $key,
                'name'        => $preset['name'],
                'description' => $preset['description'],
                'preview'     => $preset['preview'] ?? '',
                'colors'      => array(
                    'primary'    => $preset['colors']['primary'],
                    'secondary'  => $preset['colors']['secondary'],
                    'accent'     => $preset['colors']['accent'],
                    'background' => $preset['colors']['background'],
                    'text'       => $preset['colors']['text'],
                ),
                'style'       => $preset['style'] ?? 'modern',
                'animations'  => ! empty( $preset['default_animations'] ),
            );
        }

        return new WP_REST_Response( array(
            'presets' => $result,
            'total'   => count( $result ),
            'usage'   => 'Usar con POST /claude/pages/styled { "preset": "modern", "sections": [...] }',
        ), 200 );
    }

    /**
     * Define todos los presets de diseño disponibles
     *
     * @return array
     */
    private function get_all_design_presets() {
        return array(
            'modern' => array(
                'name'        => 'Moderno',
                'description' => 'Diseño limpio con gradientes sutiles, sombras suaves y animaciones elegantes',
                'style'       => 'modern',
                'colors'      => array(
                    'primary'          => '#6366f1',
                    'primary_light'    => '#818cf8',
                    'primary_dark'     => '#4f46e5',
                    'secondary'        => '#0ea5e9',
                    'accent'           => '#f59e0b',
                    'background'       => '#ffffff',
                    'background_alt'   => '#f8fafc',
                    'surface'          => '#ffffff',
                    'text'             => '#1e293b',
                    'text_muted'       => '#64748b',
                    'border'           => '#e2e8f0',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%)',
                    'cta'     => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.7) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                    'md'   => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
                    'lg'   => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
                    'xl'   => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
                    'card' => '0 4px 20px rgba(99, 102, 241, 0.15)',
                ),
                'typography'  => array(
                    'font_family'   => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.375rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '1rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s', 'delay' => '0s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'hover' => 'grow', 'stagger' => '0.1s' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'stagger' => '0.15s' ),
                    'cta'      => array( 'entrance' => 'zoomIn', 'duration' => '0.6s' ),
                ),
            ),
            'corporate' => array(
                'name'        => 'Corporativo',
                'description' => 'Diseño profesional y serio con colores azules, ideal para empresas',
                'style'       => 'corporate',
                'colors'      => array(
                    'primary'          => '#1e40af',
                    'primary_light'    => '#3b82f6',
                    'primary_dark'     => '#1e3a8a',
                    'secondary'        => '#0f766e',
                    'accent'           => '#dc2626',
                    'background'       => '#ffffff',
                    'background_alt'   => '#f1f5f9',
                    'surface'          => '#ffffff',
                    'text'             => '#0f172a',
                    'text_muted'       => '#475569',
                    'border'           => '#cbd5e1',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #1e40af 0%, #0f766e 100%)',
                    'cta'     => 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(15,23,42,0.5) 0%, rgba(15,23,42,0.8) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                    'md'   => '0 4px 6px -1px rgba(0, 0, 0, 0.15)',
                    'lg'   => '0 10px 25px -5px rgba(0, 0, 0, 0.15)',
                    'card' => '0 2px 15px rgba(30, 64, 175, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Roboto', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.25rem',
                    'radius_md' => '0.375rem',
                    'radius_lg' => '0.5rem',
                    'radius_xl' => '0.75rem',
                ),
                'spacing'     => array(
                    'section_padding' => '6rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeIn', 'duration' => '0.6s', 'hover' => 'float' ),
                    'features' => array( 'entrance' => 'fadeInLeft', 'duration' => '0.6s', 'stagger' => '0.2s' ),
                ),
            ),
            'minimal' => array(
                'name'        => 'Minimalista',
                'description' => 'Diseño ultra limpio con mucho espacio en blanco y tipografía elegante',
                'style'       => 'minimal',
                'colors'      => array(
                    'primary'          => '#18181b',
                    'primary_light'    => '#3f3f46',
                    'primary_dark'     => '#09090b',
                    'secondary'        => '#71717a',
                    'accent'           => '#18181b',
                    'background'       => '#ffffff',
                    'background_alt'   => '#fafafa',
                    'surface'          => '#ffffff',
                    'text'             => '#18181b',
                    'text_muted'       => '#71717a',
                    'border'           => '#e4e4e7',
                ),
                'gradients'   => array(
                    'hero'    => 'none',
                    'cta'     => 'none',
                    'overlay' => 'linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.3) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => 'none',
                    'md'   => '0 1px 2px rgba(0,0,0,0.05)',
                    'lg'   => '0 4px 12px rgba(0,0,0,0.05)',
                    'card' => '0 1px 3px rgba(0,0,0,0.04)',
                ),
                'typography'  => array(
                    'font_family'   => "'DM Sans', -apple-system, sans-serif",
                    'heading_weight'=> '600',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0',
                    'radius_md' => '0',
                    'radius_lg' => '0',
                    'radius_xl' => '0',
                ),
                'spacing'     => array(
                    'section_padding' => '8rem',
                    'container_max'   => '960px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1.2s' ),
                    'section'  => array( 'entrance' => 'fadeIn', 'duration' => '0.8s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeIn', 'duration' => '0.6s' ),
                ),
            ),
            'dark' => array(
                'name'        => 'Oscuro',
                'description' => 'Diseño moderno con fondo oscuro, ideal para tech y gaming',
                'style'       => 'dark',
                'colors'      => array(
                    'primary'          => '#8b5cf6',
                    'primary_light'    => '#a78bfa',
                    'primary_dark'     => '#7c3aed',
                    'secondary'        => '#06b6d4',
                    'accent'           => '#f59e0b',
                    'background'       => '#0f0f0f',
                    'background_alt'   => '#1a1a1a',
                    'surface'          => '#262626',
                    'text'             => '#fafafa',
                    'text_muted'       => '#a1a1aa',
                    'border'           => '#3f3f46',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%)',
                    'cta'     => 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.9) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(0, 0, 0, 0.3)',
                    'md'   => '0 4px 8px rgba(0, 0, 0, 0.4)',
                    'lg'   => '0 8px 24px rgba(0, 0, 0, 0.5)',
                    'card' => '0 4px 20px rgba(139, 92, 246, 0.2)',
                    'glow' => '0 0 30px rgba(139, 92, 246, 0.3)',
                ),
                'typography'  => array(
                    'font_family'   => "'Space Grotesk', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.5rem',
                    'radius_md' => '0.75rem',
                    'radius_lg' => '1rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'zoomIn', 'duration' => '0.5s', 'hover' => 'pulse', 'stagger' => '0.1s' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'stagger' => '0.12s' ),
                    'cta'      => array( 'entrance' => 'bounceIn', 'duration' => '0.8s' ),
                ),
            ),
            'vibrant' => array(
                'name'        => 'Vibrante',
                'description' => 'Colores vivos y gradientes llamativos para proyectos creativos',
                'style'       => 'vibrant',
                'colors'      => array(
                    'primary'          => '#ec4899',
                    'primary_light'    => '#f472b6',
                    'primary_dark'     => '#db2777',
                    'secondary'        => '#8b5cf6',
                    'accent'           => '#06b6d4',
                    'background'       => '#ffffff',
                    'background_alt'   => '#fdf4ff',
                    'surface'          => '#ffffff',
                    'text'             => '#1f2937',
                    'text_muted'       => '#6b7280',
                    'border'           => '#f3e8ff',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #06b6d4 100%)',
                    'cta'     => 'linear-gradient(135deg, #f472b6 0%, #8b5cf6 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(236,72,153,0.3) 0%, rgba(139,92,246,0.6) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 8px rgba(236, 72, 153, 0.15)',
                    'md'   => '0 4px 16px rgba(236, 72, 153, 0.2)',
                    'lg'   => '0 8px 30px rgba(139, 92, 246, 0.25)',
                    'card' => '0 4px 20px rgba(236, 72, 153, 0.15)',
                ),
                'typography'  => array(
                    'font_family'   => "'Poppins', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.75rem',
                    'radius_md' => '1rem',
                    'radius_lg' => '1.5rem',
                    'radius_xl' => '2rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'bounceIn', 'duration' => '0.6s', 'hover' => 'pulse', 'stagger' => '0.1s' ),
                    'features' => array( 'entrance' => 'zoomIn', 'duration' => '0.5s', 'stagger' => '0.1s' ),
                    'cta'      => array( 'entrance' => 'bounceIn', 'duration' => '0.8s' ),
                ),
            ),
            'elegant' => array(
                'name'        => 'Elegante',
                'description' => 'Diseño sofisticado con tonos dorados, ideal para lujo y premium',
                'style'       => 'elegant',
                'colors'      => array(
                    'primary'          => '#b8860b',
                    'primary_light'    => '#daa520',
                    'primary_dark'     => '#8b6914',
                    'secondary'        => '#1a1a2e',
                    'accent'           => '#c9a227',
                    'background'       => '#fefefe',
                    'background_alt'   => '#f8f6f0',
                    'surface'          => '#ffffff',
                    'text'             => '#1a1a2e',
                    'text_muted'       => '#4a4a5a',
                    'border'           => '#e8e4d9',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%)',
                    'cta'     => 'linear-gradient(135deg, #b8860b 0%, #daa520 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(26,26,46,0.4) 0%, rgba(26,26,46,0.8) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(26, 26, 46, 0.08)',
                    'md'   => '0 4px 12px rgba(26, 26, 46, 0.12)',
                    'lg'   => '0 8px 24px rgba(26, 26, 46, 0.15)',
                    'card' => '0 4px 20px rgba(184, 134, 11, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Playfair Display', Georgia, serif",
                    'heading_weight'=> '600',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.25rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '0.75rem',
                    'radius_xl' => '1rem',
                ),
                'spacing'     => array(
                    'section_padding' => '6rem',
                    'container_max'   => '1100px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1.2s' ),
                    'section'  => array( 'entrance' => 'fadeIn', 'duration' => '0.8s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'hover' => 'float' ),
                ),
            ),
            'tech' => array(
                'name'        => 'Tech/Startup',
                'description' => 'Diseño futurista con gradientes neón, ideal para tech y startups',
                'style'       => 'tech',
                'colors'      => array(
                    'primary'          => '#00d4ff',
                    'primary_light'    => '#5ce1e6',
                    'primary_dark'     => '#00a8cc',
                    'secondary'        => '#7c3aed',
                    'accent'           => '#00ff88',
                    'background'       => '#0a0a1a',
                    'background_alt'   => '#12122a',
                    'surface'          => '#1a1a3a',
                    'text'             => '#e0e0ff',
                    'text_muted'       => '#8080a0',
                    'border'           => '#2a2a4a',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #0a0a1a 0%, #1a1a3a 50%, #0a0a1a 100%)',
                    'cta'     => 'linear-gradient(135deg, #00d4ff 0%, #7c3aed 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(10,10,26,0.8) 0%, rgba(10,10,26,0.95) 100%)',
                    'glow'    => 'radial-gradient(ellipse at center, rgba(0,212,255,0.15) 0%, transparent 70%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 8px rgba(0, 212, 255, 0.1)',
                    'md'   => '0 4px 16px rgba(0, 212, 255, 0.15)',
                    'lg'   => '0 8px 32px rgba(0, 212, 255, 0.2)',
                    'card' => '0 4px 24px rgba(0, 212, 255, 0.2)',
                    'glow' => '0 0 40px rgba(0, 212, 255, 0.3)',
                    'neon' => '0 0 20px rgba(0, 255, 136, 0.4)',
                ),
                'typography'  => array(
                    'font_family'   => "'JetBrains Mono', 'Fira Code', monospace",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.25rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '0.75rem',
                    'radius_xl' => '1rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1280px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'zoomIn', 'duration' => '0.5s', 'hover' => 'pulse', 'stagger' => '0.08s' ),
                    'features' => array( 'entrance' => 'slideInUp', 'duration' => '0.5s', 'stagger' => '0.1s' ),
                    'cta'      => array( 'entrance' => 'zoomIn', 'duration' => '0.6s' ),
                ),
            ),
            'nature' => array(
                'name'        => 'Naturaleza',
                'description' => 'Colores verdes y terrosos, ideal para ecología y bienestar',
                'style'       => 'nature',
                'colors'      => array(
                    'primary'          => '#059669',
                    'primary_light'    => '#10b981',
                    'primary_dark'     => '#047857',
                    'secondary'        => '#0d9488',
                    'accent'           => '#d97706',
                    'background'       => '#fefdfb',
                    'background_alt'   => '#f0fdf4',
                    'surface'          => '#ffffff',
                    'text'             => '#1c3829',
                    'text_muted'       => '#4d6356',
                    'border'           => '#d1e7dd',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #059669 0%, #0d9488 100%)',
                    'cta'     => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(5,150,105,0.3) 0%, rgba(5,150,105,0.7) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(5, 150, 105, 0.08)',
                    'md'   => '0 4px 12px rgba(5, 150, 105, 0.12)',
                    'lg'   => '0 8px 24px rgba(5, 150, 105, 0.15)',
                    'card' => '0 4px 20px rgba(5, 150, 105, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Nunito', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.5rem',
                    'radius_md' => '0.75rem',
                    'radius_lg' => '1rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'hover' => 'float', 'stagger' => '0.12s' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'stagger' => '0.15s' ),
                ),
            ),
            // === PRESETS COMUNITARIOS ===
            'community' => array(
                'name'        => 'Comunitario',
                'description' => 'Diseño cálido y acogedor para comunidades y asociaciones',
                'style'       => 'community',
                'colors'      => array(
                    'primary'          => '#7c3aed',
                    'primary_light'    => '#a78bfa',
                    'primary_dark'     => '#6d28d9',
                    'secondary'        => '#f59e0b',
                    'accent'           => '#10b981',
                    'background'       => '#fefefe',
                    'background_alt'   => '#faf5ff',
                    'surface'          => '#ffffff',
                    'text'             => '#1f2937',
                    'text_muted'       => '#6b7280',
                    'border'           => '#e5e7eb',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #7c3aed 0%, #a78bfa 50%, #f59e0b 100%)',
                    'cta'     => 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(124,58,237,0.4) 0%, rgba(124,58,237,0.8) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(124, 58, 237, 0.08)',
                    'md'   => '0 4px 12px rgba(124, 58, 237, 0.12)',
                    'lg'   => '0 8px 24px rgba(124, 58, 237, 0.15)',
                    'card' => '0 4px 20px rgba(124, 58, 237, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Nunito', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.5rem',
                    'radius_md' => '0.75rem',
                    'radius_lg' => '1.25rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'hover' => 'grow', 'stagger' => '0.1s' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'stagger' => '0.15s' ),
                ),
            ),
            'cooperative' => array(
                'name'        => 'Cooperativo',
                'description' => 'Colores solidarios y profesionales para cooperativas',
                'style'       => 'cooperative',
                'colors'      => array(
                    'primary'          => '#dc2626',
                    'primary_light'    => '#ef4444',
                    'primary_dark'     => '#b91c1c',
                    'secondary'        => '#1e3a8a',
                    'accent'           => '#fbbf24',
                    'background'       => '#ffffff',
                    'background_alt'   => '#fef2f2',
                    'surface'          => '#ffffff',
                    'text'             => '#1f2937',
                    'text_muted'       => '#4b5563',
                    'border'           => '#e5e7eb',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #dc2626 0%, #1e3a8a 100%)',
                    'cta'     => 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(220,38,38,0.5) 0%, rgba(30,58,138,0.8) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(220, 38, 38, 0.08)',
                    'md'   => '0 4px 12px rgba(220, 38, 38, 0.12)',
                    'lg'   => '0 8px 24px rgba(220, 38, 38, 0.15)',
                    'card' => '0 4px 20px rgba(220, 38, 38, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Source Sans 3', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.375rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '0.75rem',
                    'radius_xl' => '1rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'hover' => 'float' ),
                    'features' => array( 'entrance' => 'fadeInLeft', 'duration' => '0.6s', 'stagger' => '0.2s' ),
                ),
            ),
            'eco' => array(
                'name'        => 'Ecológico',
                'description' => 'Diseño sostenible con tonos tierra y verdes naturales',
                'style'       => 'eco',
                'colors'      => array(
                    'primary'          => '#15803d',
                    'primary_light'    => '#22c55e',
                    'primary_dark'     => '#166534',
                    'secondary'        => '#78350f',
                    'accent'           => '#eab308',
                    'background'       => '#fefdf8',
                    'background_alt'   => '#f0fdf4',
                    'surface'          => '#ffffff',
                    'text'             => '#1c3829',
                    'text_muted'       => '#4d6356',
                    'border'           => '#d1e7dd',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #15803d 0%, #78350f 100%)',
                    'cta'     => 'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(21,128,61,0.3) 0%, rgba(120,53,15,0.7) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(21, 128, 61, 0.08)',
                    'md'   => '0 4px 12px rgba(21, 128, 61, 0.12)',
                    'lg'   => '0 8px 24px rgba(21, 128, 61, 0.15)',
                    'card' => '0 4px 20px rgba(21, 128, 61, 0.1)',
                ),
                'typography'  => array(
                    'font_family'   => "'Lora', Georgia, serif",
                    'heading_weight'=> '600',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.25rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '0.75rem',
                    'radius_xl' => '1rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1100px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeIn', 'duration' => '1.2s' ),
                    'section'  => array( 'entrance' => 'fadeIn', 'duration' => '0.8s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'hover' => 'float' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'stagger' => '0.15s' ),
                ),
            ),
            'fundraising' => array(
                'name'        => 'Recaudación',
                'description' => 'Diseño optimizado para crowdfunding y donaciones',
                'style'       => 'fundraising',
                'colors'      => array(
                    'primary'          => '#059669',
                    'primary_light'    => '#34d399',
                    'primary_dark'     => '#047857',
                    'secondary'        => '#7c3aed',
                    'accent'           => '#f59e0b',
                    'background'       => '#ffffff',
                    'background_alt'   => '#ecfdf5',
                    'surface'          => '#ffffff',
                    'text'             => '#111827',
                    'text_muted'       => '#6b7280',
                    'border'           => '#d1fae5',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #059669 0%, #7c3aed 100%)',
                    'cta'     => 'linear-gradient(135deg, #34d399 0%, #059669 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(5,150,105,0.4) 0%, rgba(124,58,237,0.8) 100%)',
                    'progress'=> 'linear-gradient(90deg, #34d399 0%, #059669 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 2px 4px rgba(5, 150, 105, 0.1)',
                    'md'   => '0 4px 12px rgba(5, 150, 105, 0.15)',
                    'lg'   => '0 8px 24px rgba(5, 150, 105, 0.2)',
                    'card' => '0 4px 20px rgba(5, 150, 105, 0.12)',
                ),
                'typography'  => array(
                    'font_family'   => "'Inter', -apple-system, sans-serif",
                    'heading_weight'=> '700',
                    'body_weight'   => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.5rem',
                    'radius_md' => '0.75rem',
                    'radius_lg' => '1rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'zoomIn', 'duration' => '0.5s', 'hover' => 'pulse', 'stagger' => '0.1s' ),
                    'features' => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'stagger' => '0.12s' ),
                    'cta'      => array( 'entrance' => 'bounceIn', 'duration' => '0.8s' ),
                ),
            ),
        );
    }

    /**
     * Obtiene las capabilities completas del sistema
     *
     * @return WP_REST_Response
     */
    public function get_capabilities() {
        // Obtener módulos activos
        $modulos_activos = get_option( 'flavor_chat_modules', array() );

        // Animaciones disponibles
        $animaciones = array(
            'entrance' => array(
                'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
                'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight',
                'zoomIn', 'zoomInUp', 'zoomInDown',
                'bounceIn', 'bounceInUp', 'bounceInDown',
                'flipInX', 'flipInY', 'rotateIn',
            ),
            'hover' => array(
                'pulse', 'shake', 'bounce', 'swing', 'wobble', 'tada',
                'grow', 'shrink', 'float', 'sink',
                'skew', 'skewForward', 'skewBackward',
            ),
            'loop' => array(
                'pulse', 'bounce', 'shake', 'swing', 'wobble',
                'flash', 'rubberBand', 'heartBeat', 'spin',
            ),
        );

        // Tipos de sección disponibles
        $secciones = array(
            'hero'        => 'Sección principal con título, subtítulo y CTA',
            'features'    => 'Grid de características con iconos',
            'cta'         => 'Llamada a la acción',
            'testimonials'=> 'Testimonios de usuarios',
            'pricing'     => 'Tabla de precios',
            'faq'         => 'Preguntas frecuentes (acordeón)',
            'stats'       => 'Estadísticas/números destacados',
            'team'        => 'Equipo de trabajo',
            'contact'     => 'Formulario de contacto',
            'gallery'     => 'Galería de imágenes',
            'text'        => 'Bloque de texto libre',
            'section'     => 'Sección genérica con contenido',
            'card'        => 'Tarjeta individual',
            'grid'        => 'Grid flexible de elementos',
        );

        // Widgets de módulos disponibles
        $widgets_modulos = $this->get_module_widgets_list();

        return new WP_REST_Response( array(
            'version'     => '2.2.0',
            'endpoints'   => array(
                'pages'           => rest_url( self::NAMESPACE . '/claude/pages' ),
                'styled_pages'    => rest_url( self::NAMESPACE . '/claude/pages/styled' ),
                'design_presets'  => rest_url( self::NAMESPACE . '/claude/design-presets' ),
                'templates'       => rest_url( self::NAMESPACE . '/claude/templates' ),
                'widgets'         => rest_url( self::NAMESPACE . '/claude/widgets' ),
                'validate'        => rest_url( self::NAMESPACE . '/claude/validate-elements' ),
                'status'          => rest_url( self::NAMESPACE . '/claude/status' ),
            ),
            'design_presets' => array_keys( $this->get_all_design_presets() ),
            'section_types'  => array_keys( $secciones ),
            'section_descriptions' => $secciones,
            'animations'     => $animaciones,
            'modules'        => array(
                'active'  => $modulos_activos,
                'count'   => count( $modulos_activos ),
                'widgets' => $widgets_modulos,
            ),
            'features'       => array(
                'auto_normalization' => true,
                'multi_language_fields' => true,
                'auto_animations' => true,
                'preset_styles' => true,
                'module_widgets' => true,
            ),
            'usage_examples' => array(
                'create_styled_page' => array(
                    'endpoint' => 'POST /claude/pages/styled',
                    'body'     => array(
                        'title'    => 'Mi Landing Page',
                        'preset'   => 'modern',
                        'sections' => array( 'hero', 'features', 'testimonials', 'cta' ),
                        'context'  => array(
                            'topic'    => 'Mi Producto',
                            'industry' => 'tech',
                        ),
                    ),
                ),
                'create_with_modules' => array(
                    'endpoint' => 'POST /claude/pages/styled',
                    'body'     => array(
                        'title'    => 'Portal Comunitario',
                        'preset'   => 'nature',
                        'sections' => array( 'hero', 'module_eventos', 'module_grupos_consumo', 'cta' ),
                        'context'  => array(
                            'topic' => 'Mi Comunidad',
                        ),
                    ),
                ),
            ),
        ), 200 );
    }

    /**
     * Obtiene la lista de widgets de módulos disponibles
     *
     * @return array
     */
    private function get_module_widgets_list() {
        $modulos_activos = get_option( 'flavor_chat_modules', array() );

        $widget_mapping = array(
            'grupos-consumo'    => array( 'id' => 'module_grupos_consumo', 'name' => 'Grupos de Consumo', 'description' => 'Listado de grupos de consumo con mapa' ),
            'eventos'           => array( 'id' => 'module_eventos', 'name' => 'Eventos', 'description' => 'Próximos eventos de la comunidad' ),
            'marketplace'       => array( 'id' => 'module_marketplace', 'name' => 'Marketplace', 'description' => 'Productos del marketplace' ),
            'cursos'            => array( 'id' => 'module_cursos', 'name' => 'Cursos', 'description' => 'Catálogo de cursos' ),
            'talleres'          => array( 'id' => 'module_talleres', 'name' => 'Talleres', 'description' => 'Talleres disponibles' ),
            'socios'            => array( 'id' => 'module_socios', 'name' => 'Socios', 'description' => 'Directorio de socios' ),
            'biblioteca'        => array( 'id' => 'module_biblioteca', 'name' => 'Biblioteca', 'description' => 'Catálogo de la biblioteca' ),
            'foros'             => array( 'id' => 'module_foros', 'name' => 'Foros', 'description' => 'Foros de discusión' ),
            'campanias'         => array( 'id' => 'module_campanias', 'name' => 'Campañas', 'description' => 'Campañas activas' ),
            'encuestas'         => array( 'id' => 'module_encuestas', 'name' => 'Encuestas', 'description' => 'Encuestas participativas' ),
            'transparencia'     => array( 'id' => 'module_transparencia', 'name' => 'Transparencia', 'description' => 'Portal de transparencia' ),
            'incidencias'       => array( 'id' => 'module_incidencias', 'name' => 'Incidencias', 'description' => 'Gestión de incidencias' ),
            'carpooling'        => array( 'id' => 'module_carpooling', 'name' => 'Carpooling', 'description' => 'Viajes compartidos' ),
            'reservas'          => array( 'id' => 'module_reservas', 'name' => 'Reservas', 'description' => 'Sistema de reservas' ),
            'huertos-urbanos'   => array( 'id' => 'module_huertos', 'name' => 'Huertos Urbanos', 'description' => 'Gestión de huertos' ),
            'compostaje'        => array( 'id' => 'module_compostaje', 'name' => 'Compostaje', 'description' => 'Red de compostaje' ),
            'banco-tiempo'      => array( 'id' => 'module_banco_tiempo', 'name' => 'Banco de Tiempo', 'description' => 'Intercambio de servicios' ),
            'crowdfunding'      => array( 'id' => 'module_crowdfunding', 'name' => 'Crowdfunding', 'description' => 'Proyectos de financiación' ),
        );

        $available = array();
        foreach ( $modulos_activos as $modulo ) {
            if ( isset( $widget_mapping[ $modulo ] ) ) {
                $available[] = $widget_mapping[ $modulo ];
            }
        }

        return $available;
    }

    /**
     * Obtiene widgets disponibles con detalles
     *
     * @return WP_REST_Response
     */
    public function get_available_widgets() {
        $widgets = $this->get_module_widgets_list();

        return new WP_REST_Response( array(
            'widgets' => $widgets,
            'total'   => count( $widgets ),
            'usage'   => 'Usar el id del widget como tipo de sección, ej: "sections": ["hero", "module_eventos", "cta"]',
        ), 200 );
    }

    /**
     * Crea una página con preset de diseño aplicado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_styled_page( $request ) {
        $title = $request->get_param( 'title' );
        $preset_id = $request->get_param( 'preset' );
        $sections = $request->get_param( 'sections' );
        $context = $request->get_param( 'context' ) ?: array();
        $status = $request->get_param( 'status' );

        // Obtener preset de diseño
        $presets = $this->get_all_design_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_REST_Response( array(
                'error'   => 'Preset no encontrado',
                'valid'   => array_keys( $presets ),
            ), 400 );
        }

        $preset = $presets[ $preset_id ];

        // Añadir preset al contexto
        $context['preset'] = $preset_id;
        $context['preset_data'] = $preset;

        // Si no hay topic, usar el título
        if ( empty( $context['topic'] ) ) {
            $context['topic'] = $title;
        }

        // Generar elementos con estilos del preset
        $elements = array();
        foreach ( $sections as $section_type ) {
            $section = $this->create_styled_section( $section_type, $context, $preset );
            if ( $section ) {
                $elements[] = $section;
            }
        }

        // Procesar elementos
        $elements = $this->prepare_elements( $elements );

        // Crear post
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
        }

        // Guardar datos VBP con configuración del preset
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elements,
            'settings' => array(
                'pageWidth'       => intval( str_replace( 'px', '', $preset['spacing']['container_max'] ) ),
                'backgroundColor' => $preset['colors']['background'],
                'preset'          => $preset_id,
                'presetColors'    => $preset['colors'],
            ),
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );
        update_post_meta( $post_id, '_flavor_vbp_preset', $preset_id );

        return new WP_REST_Response( array(
            'success'      => true,
            'id'           => $post_id,
            'title'        => $title,
            'preset'       => $preset_id,
            'preset_name'  => $preset['name'],
            'sections'     => count( $sections ),
            'status'       => $status,
            'edit_url'     => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url'     => get_permalink( $post_id ),
        ), 201 );
    }

    /**
     * Crea una sección con estilos del preset aplicados
     *
     * @param string $type    Tipo de sección.
     * @param array  $context Contexto.
     * @param array  $preset  Preset de diseño.
     * @return array|null
     */
    private function create_styled_section( $type, $context, $preset ) {
        // Crear sección base
        $section = $this->create_section( $type, $context );
        if ( ! $section ) {
            return null;
        }

        // Aplicar estilos del preset
        $section['styles'] = $this->apply_preset_styles( $type, $preset, $section['styles'] ?? array() );

        // Aplicar animaciones del preset
        if ( ! empty( $preset['default_animations'][ $type ] ) ) {
            $anim = $preset['default_animations'][ $type ];
            $section['animations'] = $anim;
        } elseif ( ! empty( $preset['default_animations']['section'] ) ) {
            // Usar animación genérica de sección
            $section['animations'] = $preset['default_animations']['section'];
        }

        return $section;
    }

    /**
     * Aplica estilos del preset a un elemento
     *
     * @param string $type   Tipo de elemento.
     * @param array  $preset Preset de diseño.
     * @param array  $styles Estilos existentes.
     * @return array
     */
    private function apply_preset_styles( $type, $preset, $styles ) {
        $default = $this->get_default_styles();

        // Mezclar con estilos base
        $styles = $this->merge_styles( $default, $styles );

        // Aplicar colores del preset según el tipo
        switch ( $type ) {
            case 'hero':
                $styles['colors'] = array(
                    'background' => $preset['gradients']['hero'] !== 'none'
                        ? $preset['gradients']['hero']
                        : $preset['colors']['primary'],
                    'text'       => '#ffffff',
                );
                $styles['spacing']['padding'] = array(
                    'top'    => $preset['spacing']['section_padding'],
                    'bottom' => $preset['spacing']['section_padding'],
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
                break;

            case 'cta':
                $styles['colors'] = array(
                    'background' => $preset['gradients']['cta'],
                    'text'       => '#ffffff',
                );
                $styles['spacing']['padding'] = array(
                    'top'    => '4rem',
                    'bottom' => '4rem',
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
                $styles['borders']['radius'] = $preset['borders']['radius_lg'];
                break;

            case 'features':
            case 'testimonials':
            case 'team':
            case 'stats':
                $styles['colors'] = array(
                    'background' => $preset['colors']['background_alt'],
                    'text'       => $preset['colors']['text'],
                );
                $styles['spacing']['padding'] = array(
                    'top'    => $preset['spacing']['section_padding'],
                    'bottom' => $preset['spacing']['section_padding'],
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
                break;

            case 'pricing':
                $styles['colors'] = array(
                    'background' => $preset['colors']['background'],
                    'text'       => $preset['colors']['text'],
                );
                $styles['spacing']['padding'] = array(
                    'top'    => $preset['spacing']['section_padding'],
                    'bottom' => $preset['spacing']['section_padding'],
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
                break;

            case 'faq':
            case 'contact':
                $styles['colors'] = array(
                    'background' => $preset['colors']['background'],
                    'text'       => $preset['colors']['text'],
                );
                $styles['spacing']['padding'] = array(
                    'top'    => $preset['spacing']['section_padding'],
                    'bottom' => $preset['spacing']['section_padding'],
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
                break;

            default:
                // Secciones genéricas
                $styles['colors'] = array(
                    'background' => $preset['colors']['background'],
                    'text'       => $preset['colors']['text'],
                );
                $styles['spacing']['padding'] = array(
                    'top'    => '4rem',
                    'bottom' => '4rem',
                    'left'   => '2rem',
                    'right'  => '2rem',
                );
        }

        // Añadir variables CSS del preset
        $styles['advanced']['customCss'] = $this->generate_preset_css_vars( $preset );

        return $styles;
    }

    /**
     * Genera variables CSS del preset para inyectar
     *
     * @param array $preset Preset de diseño.
     * @return string
     */
    private function generate_preset_css_vars( $preset ) {
        $vars = array(
            '--vbp-primary'         => $preset['colors']['primary'],
            '--vbp-primary-light'   => $preset['colors']['primary_light'],
            '--vbp-primary-dark'    => $preset['colors']['primary_dark'],
            '--vbp-secondary'       => $preset['colors']['secondary'],
            '--vbp-accent'          => $preset['colors']['accent'],
            '--vbp-background'      => $preset['colors']['background'],
            '--vbp-background-alt'  => $preset['colors']['background_alt'],
            '--vbp-surface'         => $preset['colors']['surface'],
            '--vbp-text'            => $preset['colors']['text'],
            '--vbp-text-muted'      => $preset['colors']['text_muted'],
            '--vbp-border'          => $preset['colors']['border'],
            '--vbp-shadow-sm'       => $preset['shadows']['sm'],
            '--vbp-shadow-md'       => $preset['shadows']['md'],
            '--vbp-shadow-lg'       => $preset['shadows']['lg'],
            '--vbp-shadow-card'     => $preset['shadows']['card'],
            '--vbp-radius-sm'       => $preset['borders']['radius_sm'],
            '--vbp-radius-md'       => $preset['borders']['radius_md'],
            '--vbp-radius-lg'       => $preset['borders']['radius_lg'],
            '--vbp-font-family'     => $preset['typography']['font_family'],
        );

        $css = ':root {';
        foreach ( $vars as $var => $value ) {
            $css .= " {$var}: {$value};";
        }
        $css .= ' }';

        return $css;
    }

    // =============================================
    // MÉTODOS DE MULTIIDIOMA / TRADUCCIÓN
    // =============================================

    /**
     * Verifica si el addon multilingual está disponible
     *
     * @return bool
     */
    private function is_multilingual_available() {
        return class_exists( 'Flavor_Multilingual' ) && class_exists( 'Flavor_AI_Translator' );
    }

    /**
     * Obtiene los idiomas disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_languages( $request ) {
        if ( ! $this->is_multilingual_available() ) {
            // Devolver idiomas por defecto si el addon no está activo
            return new WP_REST_Response( array(
                'available'  => false,
                'message'    => 'El addon flavor-multilingual no está activo',
                'languages'  => array(
                    array( 'code' => 'es', 'name' => 'Español', 'native_name' => 'Español', 'is_default' => true ),
                    array( 'code' => 'eu', 'name' => 'Euskera', 'native_name' => 'Euskara', 'is_default' => false ),
                    array( 'code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => false ),
                    array( 'code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false ),
                ),
            ), 200 );
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_language = $core->get_default_language();

        $languages = array();
        foreach ( $active_languages as $code => $lang ) {
            $languages[] = array(
                'code'        => $code,
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'] ?? null,
                'is_rtl'      => $lang['is_rtl'] ?? false,
                'is_default'  => ( $code === $default_language ),
            );
        }

        return new WP_REST_Response( array(
            'available' => true,
            'default'   => $default_language,
            'languages' => $languages,
        ), 200 );
    }

    /**
     * Traduce una página VBP a un idioma específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $to_lang = sanitize_key( $request->get_param( 'to_lang' ) );
        $save = (bool) $request->get_param( 'save' );
        $create_copy = (bool) $request->get_param( 'create_copy' );

        $post = get_post( $page_id );
        if ( ! $post || $post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        // Obtener elementos VBP
        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( empty( $elements ) ) {
            $elements = array();
        }

        // Traducir título
        $translated_title = $this->translate_text_with_ai( $post->post_title, 'es', $to_lang );

        // Traducir elementos VBP
        $translated_elements = $this->translate_vbp_elements( $elements, 'es', $to_lang );

        $result = array(
            'original_id' => $page_id,
            'language'    => $to_lang,
            'title'       => $translated_title,
            'elements'    => $translated_elements,
        );

        // Guardar traducciones
        if ( $save && $this->is_multilingual_available() ) {
            $storage = Flavor_Translation_Storage::get_instance();

            $storage->save_translation( 'post', $page_id, $to_lang, 'title', $translated_title, array(
                'status' => 'published',
                'auto'   => true,
            ) );

            $storage->save_translation( 'post', $page_id, $to_lang, 'vbp_elements', wp_json_encode( $translated_elements ), array(
                'status' => 'published',
                'auto'   => true,
            ) );

            $result['saved'] = true;
        }

        // Crear copia de la página traducida
        if ( $create_copy ) {
            $new_page_id = $this->create_translated_page_copy( $post, $translated_title, $translated_elements, $to_lang );
            $result['new_page_id'] = $new_page_id;
            $result['new_page_url'] = get_permalink( $new_page_id );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $result,
        ), 200 );
    }

    /**
     * Traduce una página a todos los idiomas activos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_page_all_languages( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $target_languages = $request->get_param( 'languages' );
        $save = (bool) $request->get_param( 'save' );
        $create_copies = (bool) $request->get_param( 'create_copies' );

        $post = get_post( $page_id );
        if ( ! $post || $post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        // Determinar idiomas destino
        if ( empty( $target_languages ) ) {
            if ( $this->is_multilingual_available() ) {
                $core = Flavor_Multilingual_Core::get_instance();
                $active = $core->get_active_languages();
                $default = $core->get_default_language();
                $target_languages = array_filter( array_keys( $active ), function( $code ) use ( $default ) {
                    return $code !== $default;
                } );
            } else {
                $target_languages = array( 'eu', 'en', 'fr' );
            }
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( empty( $elements ) ) {
            $elements = array();
        }

        $translations = array();
        $created_pages = array();

        foreach ( $target_languages as $to_lang ) {
            $translated_title = $this->translate_text_with_ai( $post->post_title, 'es', $to_lang );
            $translated_elements = $this->translate_vbp_elements( $elements, 'es', $to_lang );

            $translations[ $to_lang ] = array(
                'title'    => $translated_title,
                'elements' => $translated_elements,
            );

            // Guardar
            if ( $save && $this->is_multilingual_available() ) {
                $storage = Flavor_Translation_Storage::get_instance();
                $storage->save_translation( 'post', $page_id, $to_lang, 'title', $translated_title, array(
                    'status' => 'published',
                    'auto'   => true,
                ) );
                $storage->save_translation( 'post', $page_id, $to_lang, 'vbp_elements', wp_json_encode( $translated_elements ), array(
                    'status' => 'published',
                    'auto'   => true,
                ) );
            }

            // Crear copias
            if ( $create_copies ) {
                $new_page_id = $this->create_translated_page_copy( $post, $translated_title, $translated_elements, $to_lang );
                $created_pages[ $to_lang ] = array(
                    'id'  => $new_page_id,
                    'url' => get_permalink( $new_page_id ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'original_id'   => $page_id,
            'languages'     => array_keys( $translations ),
            'translations'  => $translations,
            'created_pages' => $created_pages,
        ), 200 );
    }

    /**
     * Obtiene las traducciones existentes de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_translations( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $post || $post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        if ( ! $this->is_multilingual_available() ) {
            return new WP_REST_Response( array(
                'success'      => true,
                'page_id'      => $page_id,
                'translations' => array(),
                'message'      => 'Addon multilingual no disponible',
            ), 200 );
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations( 'post', $page_id );

        return new WP_REST_Response( array(
            'success'      => true,
            'page_id'      => $page_id,
            'title'        => $post->post_title,
            'translations' => $translations,
        ), 200 );
    }

    /**
     * Traduce contenido (texto, HTML o elementos VBP)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function translate_content( $request ) {
        $content = $request->get_param( 'content' );
        $from_lang = sanitize_key( $request->get_param( 'from_lang' ) );
        $to_lang = sanitize_key( $request->get_param( 'to_lang' ) );
        $type = sanitize_key( $request->get_param( 'type' ) );

        switch ( $type ) {
            case 'vbp_elements':
                // Contenido es un array de elementos VBP
                $elements = is_array( $content ) ? $content : json_decode( $content, true );
                $translated = $this->translate_vbp_elements( $elements, $from_lang, $to_lang );
                break;

            case 'html':
                $translated = $this->translate_html_with_ai( $content, $from_lang, $to_lang );
                break;

            case 'text':
            default:
                $translated = $this->translate_text_with_ai( $content, $from_lang, $to_lang );
                break;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'original'   => $content,
            'translated' => $translated,
            'from'       => $from_lang,
            'to'         => $to_lang,
            'type'       => $type,
        ), 200 );
    }

    /**
     * Crea una página multilingüe con traducciones automáticas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_multilingual_page( $request ) {
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $elements = $request->get_param( 'elements' );
        $base_lang = sanitize_key( $request->get_param( 'base_lang' ) );
        $target_languages = $request->get_param( 'languages' );
        $status = sanitize_key( $request->get_param( 'status' ) );
        $design_preset = sanitize_key( $request->get_param( 'design_preset' ) );

        // Validar elementos
        if ( ! is_array( $elements ) ) {
            $elements = json_decode( $elements, true );
        }

        // Aplicar preset si se especifica
        if ( ! empty( $design_preset ) ) {
            $elements = $this->apply_design_preset_to_elements( $elements, $design_preset );
        }

        // Crear página base
        $base_page = $this->internal_create_page( $title, $elements, $status );
        if ( is_wp_error( $base_page ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $base_page->get_error_message(),
            ), 500 );
        }

        $base_page_id = $base_page['id'];
        $created_pages = array(
            $base_lang => array(
                'id'    => $base_page_id,
                'url'   => get_permalink( $base_page_id ),
                'title' => $title,
            ),
        );

        // Traducir y crear páginas para cada idioma
        foreach ( $target_languages as $to_lang ) {
            if ( $to_lang === $base_lang ) {
                continue;
            }

            $translated_title = $this->translate_text_with_ai( $title, $base_lang, $to_lang );
            $translated_elements = $this->translate_vbp_elements( $elements, $base_lang, $to_lang );

            // Crear página traducida
            $translated_page = $this->internal_create_page( $translated_title, $translated_elements, $status );
            if ( ! is_wp_error( $translated_page ) ) {
                $translated_page_id = $translated_page['id'];

                // Marcar idioma de la página
                update_post_meta( $translated_page_id, '_flavor_language', $to_lang );
                update_post_meta( $translated_page_id, '_flavor_translation_of', $base_page_id );

                $created_pages[ $to_lang ] = array(
                    'id'    => $translated_page_id,
                    'url'   => get_permalink( $translated_page_id ),
                    'title' => $translated_title,
                );
            }
        }

        // Guardar relación de traducciones en la página base
        update_post_meta( $base_page_id, '_flavor_translations', wp_json_encode( $created_pages ) );

        return new WP_REST_Response( array(
            'success'       => true,
            'base_language' => $base_lang,
            'pages'         => $created_pages,
            'total'         => count( $created_pages ),
        ), 201 );
    }

    /**
     * Traduce texto usando la IA
     *
     * @param string $text     Texto a traducir.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return string
     */
    private function translate_text_with_ai( $text, $from_lang, $to_lang ) {
        if ( empty( $text ) || $from_lang === $to_lang ) {
            return $text;
        }

        // Usar el traductor del addon si está disponible
        if ( $this->is_multilingual_available() ) {
            $translator = Flavor_AI_Translator::get_instance();
            $result = $translator->translate_text( $text, $from_lang, $to_lang );
            if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
                return $result;
            }
        }

        // Fallback: usar el Engine Manager directamente
        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $lang_names = $this->get_language_names();
            $from_name = $lang_names[ $from_lang ] ?? $from_lang;
            $to_name = $lang_names[ $to_lang ] ?? $to_lang;

            $system_prompt = "Eres un traductor profesional. Traduce del {$from_name} al {$to_name}. Responde ÚNICAMENTE con la traducción, sin explicaciones.";
            $messages = array(
                array(
                    'role'    => 'user',
                    'content' => $text,
                ),
            );

            try {
                $engine = Flavor_Engine_Manager::get_instance();
                $response = $engine->send_message( $messages, $system_prompt );

                if ( ! empty( $response['success'] ) && ! empty( $response['content'] ) ) {
                    return trim( $response['content'] );
                }
            } catch ( Exception $e ) {
                // Fallback al texto original
            }
        }

        return $text;
    }

    /**
     * Traduce HTML usando la IA
     *
     * @param string $html      HTML a traducir.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return string
     */
    private function translate_html_with_ai( $html, $from_lang, $to_lang ) {
        if ( empty( $html ) || $from_lang === $to_lang ) {
            return $html;
        }

        if ( $this->is_multilingual_available() ) {
            $translator = Flavor_AI_Translator::get_instance();
            $result = $translator->translate_html( $html, $from_lang, $to_lang );
            if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
                return $result;
            }
        }

        // Fallback con Engine Manager
        if ( class_exists( 'Flavor_Engine_Manager' ) ) {
            $lang_names = $this->get_language_names();
            $from_name = $lang_names[ $from_lang ] ?? $from_lang;
            $to_name = $lang_names[ $to_lang ] ?? $to_lang;

            $system_prompt = "Eres un traductor profesional de HTML. Traduce de {$from_name} a {$to_name}. Mantén TODAS las etiquetas HTML intactas, solo traduce el texto visible. Responde ÚNICAMENTE con el HTML traducido.";
            $messages = array(
                array(
                    'role'    => 'user',
                    'content' => $html,
                ),
            );

            try {
                $engine = Flavor_Engine_Manager::get_instance();
                $response = $engine->send_message( $messages, $system_prompt );

                if ( ! empty( $response['success'] ) && ! empty( $response['content'] ) ) {
                    return trim( $response['content'] );
                }
            } catch ( Exception $e ) {
                // Fallback
            }
        }

        return $html;
    }

    /**
     * Traduce elementos VBP recursivamente
     *
     * @param array  $elements  Elementos VBP.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return array
     */
    private function translate_vbp_elements( $elements, $from_lang, $to_lang ) {
        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return $elements;
        }

        $translated = array();

        foreach ( $elements as $element ) {
            $translated[] = $this->translate_single_element( $element, $from_lang, $to_lang );
        }

        return $translated;
    }

    /**
     * Traduce un elemento VBP individual
     *
     * @param array  $element   Elemento VBP.
     * @param string $from_lang Idioma origen.
     * @param string $to_lang   Idioma destino.
     * @return array
     */
    private function translate_single_element( $element, $from_lang, $to_lang ) {
        if ( ! is_array( $element ) ) {
            return $element;
        }

        // Campos de texto comunes a traducir
        $text_fields = array(
            'titulo', 'title', 'subtitulo', 'subtitle',
            'descripcion', 'description', 'texto', 'text',
            'etiqueta', 'label', 'boton_texto', 'button_text',
            'pregunta', 'question', 'respuesta', 'answer',
            'nota', 'note', 'extracto', 'excerpt',
            'nombre', 'name', 'valor', 'value',
            'placeholder', 'mensaje', 'message',
        );

        // Traducir data del elemento
        if ( isset( $element['data'] ) && is_array( $element['data'] ) ) {
            $element['data'] = $this->translate_element_data( $element['data'], $from_lang, $to_lang, $text_fields );
        }

        return $element;
    }

    /**
     * Traduce los datos de un elemento VBP
     *
     * @param array  $data        Datos del elemento.
     * @param string $from_lang   Idioma origen.
     * @param string $to_lang     Idioma destino.
     * @param array  $text_fields Campos de texto a traducir.
     * @return array
     */
    private function translate_element_data( $data, $from_lang, $to_lang, $text_fields ) {
        foreach ( $data as $key => $value ) {
            // Si es un campo de texto conocido, traducir
            if ( in_array( $key, $text_fields, true ) && is_string( $value ) && ! empty( $value ) ) {
                // No traducir URLs ni valores que parecen técnicos
                if ( ! $this->is_technical_value( $value ) ) {
                    $data[ $key ] = $this->translate_text_with_ai( $value, $from_lang, $to_lang );
                }
            }
            // Si es un array, procesar recursivamente
            elseif ( is_array( $value ) ) {
                // Arrays de items (features, testimonials, etc.)
                if ( $this->is_items_array( $value ) ) {
                    $data[ $key ] = array_map( function( $item ) use ( $from_lang, $to_lang, $text_fields ) {
                        if ( is_array( $item ) ) {
                            return $this->translate_element_data( $item, $from_lang, $to_lang, $text_fields );
                        }
                        return $item;
                    }, $value );
                }
                // Objetos anidados (columna_izquierda, etc.)
                elseif ( $this->is_nested_object( $value ) ) {
                    $data[ $key ] = $this->translate_element_data( $value, $from_lang, $to_lang, $text_fields );
                }
            }
        }

        return $data;
    }

    /**
     * Verifica si un valor es técnico (URL, código, etc.)
     *
     * @param string $value Valor a verificar.
     * @return bool
     */
    private function is_technical_value( $value ) {
        // URLs
        if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
            return true;
        }
        // Emails
        if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
            return true;
        }
        // Colores hex
        if ( preg_match( '/^#[0-9A-Fa-f]{3,8}$/', $value ) ) {
            return true;
        }
        // rgba/rgb
        if ( preg_match( '/^rgba?\(/', $value ) ) {
            return true;
        }
        // gradients
        if ( strpos( $value, 'gradient' ) !== false ) {
            return true;
        }
        // Solo números/unidades CSS
        if ( preg_match( '/^[\d.]+(px|em|rem|%|vh|vw)?$/', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si es un array de items
     *
     * @param array $value Array a verificar.
     * @return bool
     */
    private function is_items_array( $value ) {
        if ( empty( $value ) ) {
            return false;
        }
        // Si el primer elemento es un array asociativo, es un array de items
        $first = reset( $value );
        return is_array( $first ) && ! isset( $first[0] );
    }

    /**
     * Verifica si es un objeto anidado
     *
     * @param array $value Array a verificar.
     * @return bool
     */
    private function is_nested_object( $value ) {
        if ( empty( $value ) ) {
            return false;
        }
        // Si tiene claves string, es un objeto
        foreach ( array_keys( $value ) as $key ) {
            if ( is_string( $key ) && ! is_numeric( $key ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crea una copia de la página traducida
     *
     * @param WP_Post $original_post     Post original.
     * @param string  $translated_title  Título traducido.
     * @param array   $translated_elements Elementos traducidos.
     * @param string  $language          Código de idioma.
     * @return int ID del nuevo post.
     */
    private function create_translated_page_copy( $original_post, $translated_title, $translated_elements, $language ) {
        // Generar slug basado en título traducido + idioma
        $slug = sanitize_title( $translated_title ) . '-' . $language;

        $new_post_id = wp_insert_post( array(
            'post_title'   => $translated_title,
            'post_name'    => $slug,
            'post_type'    => 'flavor_landing',
            'post_status'  => $original_post->post_status,
            'post_author'  => $original_post->post_author,
        ) );

        if ( ! is_wp_error( $new_post_id ) ) {
            // Copiar meta de diseño
            $design_preset = get_post_meta( $original_post->ID, '_flavor_vbp_design_preset', true );
            if ( $design_preset ) {
                update_post_meta( $new_post_id, '_flavor_vbp_design_preset', $design_preset );
            }

            // Guardar elementos traducidos
            update_post_meta( $new_post_id, '_flavor_vbp_elements', $translated_elements );

            // Marcar idioma y relación
            update_post_meta( $new_post_id, '_flavor_language', $language );
            update_post_meta( $new_post_id, '_flavor_translation_of', $original_post->ID );
        }

        return $new_post_id;
    }

    /**
     * Crea una página internamente (reutiliza lógica de create_page)
     *
     * @param string $title    Título.
     * @param array  $elements Elementos.
     * @param string $status   Estado.
     * @return array|WP_Error
     */
    private function internal_create_page( $title, $elements, $status = 'publish' ) {
        $slug = sanitize_title( $title );

        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, '_flavor_vbp_elements', $elements );

        return array(
            'id'  => $post_id,
            'url' => get_permalink( $post_id ),
        );
    }

    // =============================================
    // MÉTODOS DE PREVIEW DE WIDGETS
    // =============================================

    /**
     * Obtiene el preview HTML de un widget específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_preview( $request ) {
        $widget_type = $request->get_param( 'type' );
        $config      = $request->get_param( 'config' ) ?? array();

        // Cargar VBP Canvas si no está disponible
        if ( ! class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas_path = FLAVOR_CHAT_IA_PATH . 'includes/visual-builder-pro/class-vbp-canvas.php';
            if ( file_exists( $canvas_path ) ) {
                require_once $canvas_path;
            } else {
                return new WP_REST_Response( array(
                    'success' => false,
                    'error'   => 'VBP Canvas no disponible',
                ), 500 );
            }
        }

        // Obtener información del widget desde Block Library
        $widget_info = $this->get_widget_info( $widget_type );

        if ( ! $widget_info ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado: ' . $widget_type,
            ), 404 );
        }

        // Crear elemento simulado para el preview
        $elemento = array(
            'type'    => $widget_type,
            'id'      => 'preview_' . uniqid(),
            'name'    => $widget_info['name'] ?? ucfirst( str_replace( '_', ' ', $widget_type ) ),
            'visible' => true,
            'locked'  => false,
            'data'    => array_merge( $widget_info['defaults'] ?? array(), $config ),
            'styles'  => array(),
        );

        // Usar VBP Canvas para generar el preview
        $canvas = Flavor_VBP_Canvas::get_instance();
        $preview_html = $canvas->render_widget_preview_public( $elemento, $widget_info );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'type'      => $widget_type,
                'name'      => $widget_info['name'] ?? $widget_type,
                'module'    => $widget_info['module'] ?? '',
                'shortcode' => $widget_info['shortcode'] ?? '',
                'preview'   => $preview_html,
                'config'    => $config,
            ),
        ), 200 );
    }

    /**
     * Obtiene información de un widget específico
     *
     * @param string $widget_type Tipo de widget.
     * @return array|null
     */
    private function get_widget_info( $widget_type ) {
        $widgets = $this->get_all_registered_widgets();

        foreach ( $widgets as $widget ) {
            if ( $widget['type'] === $widget_type ) {
                return $widget;
            }
        }

        return null;
    }

    /**
     * Obtiene todos los widgets registrados del sistema
     *
     * @return array
     */
    private function get_all_registered_widgets() {
        $widgets = array();

        // Intentar cargar desde Block Library
        if ( $this->ensure_vbp_loaded() ) {
            $libreria   = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();

            foreach ( $categorias as $categoria ) {
                // Incluir widgets de módulos y de cualquier categoría que tenga shortcode
                $is_module_category = strpos( $categoria['name'], 'Módulo' ) !== false
                    || $categoria['name'] === 'Widgets'
                    || strpos( strtolower( $categoria['name'] ), 'widget' ) !== false;

                foreach ( $categoria['blocks'] as $bloque ) {
                    // Incluir si es categoría de módulos o si tiene shortcode
                    if ( $is_module_category || ! empty( $bloque['shortcode'] ) ) {
                        $widgets[] = array(
                            'type'      => $bloque['id'] ?? '',
                            'name'      => $bloque['name'] ?? '',
                            'icon'      => $bloque['icon'] ?? 'dashicons-admin-generic',
                            'module'    => $this->extract_module_from_category( $categoria['name'] ),
                            'shortcode' => $bloque['shortcode'] ?? '',
                            'defaults'  => $bloque['defaults'] ?? array(),
                            'fields'    => $bloque['fields'] ?? array(),
                            'category'  => $categoria['name'] ?? 'modules',
                        );
                    }
                }
            }
        }

        // Añadir widgets de fallback que no estén ya incluidos
        $fallback_widgets = $this->get_fallback_widgets();
        $existing_types = array_column( $widgets, 'type' );

        foreach ( $fallback_widgets as $fallback ) {
            if ( ! in_array( $fallback['type'], $existing_types, true ) ) {
                $widgets[] = $fallback;
            }
        }

        return $widgets;
    }

    /**
     * Extrae el nombre del módulo de la categoría
     *
     * @param string $category_name Nombre de la categoría.
     * @return string
     */
    private function extract_module_from_category( $category_name ) {
        // "Módulo: Marketplace" -> "marketplace"
        if ( strpos( $category_name, 'Módulo:' ) !== false ) {
            $module_name = str_replace( 'Módulo:', '', $category_name );
            return sanitize_title( trim( $module_name ) );
        }

        return sanitize_title( $category_name );
    }

    /**
     * Obtiene widgets de fallback cuando Block Library no está disponible
     *
     * @return array
     */
    private function get_fallback_widgets() {
        return array(
            array(
                'type'      => 'social_feed',
                'name'      => 'Feed Social',
                'icon'      => 'dashicons-share',
                'module'    => 'red-social',
                'shortcode' => '[flavor_social_feed]',
                'defaults'  => array( 'limit' => 10 ),
            ),
            array(
                'type'      => 'eventos',
                'name'      => 'Lista de Eventos',
                'icon'      => 'dashicons-calendar',
                'module'    => 'eventos',
                'shortcode' => '[flavor_eventos]',
                'defaults'  => array( 'limit' => 6, 'view' => 'grid' ),
            ),
            array(
                'type'      => 'marketplace_productos',
                'name'      => 'Productos Marketplace',
                'icon'      => 'dashicons-cart',
                'module'    => 'marketplace',
                'shortcode' => '[flavor_marketplace_productos]',
                'defaults'  => array( 'limit' => 12, 'columns' => 4 ),
            ),
            array(
                'type'      => 'cursos_catalogo',
                'name'      => 'Catálogo de Cursos',
                'icon'      => 'dashicons-welcome-learn-more',
                'module'    => 'cursos',
                'shortcode' => '[flavor_cursos_catalogo]',
                'defaults'  => array( 'limit' => 6 ),
            ),
            array(
                'type'      => 'encuestas_activas',
                'name'      => 'Encuestas Activas',
                'icon'      => 'dashicons-chart-bar',
                'module'    => 'encuestas',
                'shortcode' => '[flavor_encuestas_activas]',
                'defaults'  => array( 'limit' => 3 ),
            ),
            array(
                'type'      => 'transparencia_presupuesto',
                'name'      => 'Presupuesto Transparente',
                'icon'      => 'dashicons-money-alt',
                'module'    => 'transparencia',
                'shortcode' => '[flavor_transparencia_presupuesto]',
                'defaults'  => array(),
            ),
            array(
                'type'      => 'comunidades_listado',
                'name'      => 'Listado Comunidades',
                'icon'      => 'dashicons-groups',
                'module'    => 'comunidades',
                'shortcode' => '[flavor_comunidades]',
                'defaults'  => array( 'limit' => 8 ),
            ),
            array(
                'type'      => 'mapa_actores',
                'name'      => 'Mapa de Actores',
                'icon'      => 'dashicons-location',
                'module'    => 'mapa-actores',
                'shortcode' => '[flavor_mapa_actores]',
                'defaults'  => array( 'height' => '400px' ),
            ),
        );
    }

    /**
     * Obtiene nombres de idiomas
     *
     * @return array
     */
    private function get_language_names() {
        return array(
            'es' => 'Español',
            'eu' => 'Euskera',
            'en' => 'Inglés',
            'fr' => 'Francés',
            'de' => 'Alemán',
            'it' => 'Italiano',
            'pt' => 'Portugués',
            'ca' => 'Catalán',
            'gl' => 'Gallego',
            'ar' => 'Árabe',
            'zh' => 'Chino',
            'ja' => 'Japonés',
            'ko' => 'Coreano',
            'ru' => 'Ruso',
        );
    }
}

// Inicializar
Flavor_VBP_Claude_API::get_instance();
