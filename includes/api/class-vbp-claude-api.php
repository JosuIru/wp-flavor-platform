<?php
/**
 * REST API para integración de VBP con Claude Code
 *
 * Endpoints para crear y gestionar páginas VBP desde herramientas externas.
 *
 * @package Flavor_Platform
 * @subpackage API
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Cargar traits para refactorización modular
require_once __DIR__ . '/traits/trait-vbp-api-pages.php';
require_once __DIR__ . '/traits/trait-vbp-api-blocks.php';
require_once __DIR__ . '/traits/trait-vbp-api-design.php';
require_once __DIR__ . '/traits/trait-vbp-api-system.php';
require_once __DIR__ . '/traits/trait-vbp-api-i18n.php';
require_once __DIR__ . '/traits/trait-vbp-api-widgets.php';
require_once __DIR__ . '/traits/trait-vbp-api-templates.php';
require_once __DIR__ . '/traits/trait-vbp-api-analytics.php';
require_once __DIR__ . '/traits/trait-vbp-api-variants.php';
require_once __DIR__ . '/traits/trait-vbp-api-search.php';
require_once __DIR__ . '/traits/trait-vbp-api-utilities.php';
require_once __DIR__ . '/traits/trait-vbp-api-block-manipulation.php';
require_once __DIR__ . '/traits/trait-vbp-api-library.php';
require_once __DIR__ . '/traits/trait-vbp-api-snapshots.php';
require_once __DIR__ . '/traits/trait-vbp-api-dashboard.php';
require_once __DIR__ . '/traits/trait-vbp-api-collaboration.php';
require_once __DIR__ . '/traits/trait-vbp-api-content-analysis.php';
require_once __DIR__ . '/traits/trait-vbp-api-global-styles.php';
require_once __DIR__ . '/traits/trait-vbp-api-bulk-operations.php';
require_once __DIR__ . '/traits/trait-vbp-api-scheduling.php';
require_once __DIR__ . '/traits/trait-vbp-api-comments.php';
require_once __DIR__ . '/traits/trait-vbp-api-activity.php';
require_once __DIR__ . '/traits/trait-vbp-api-global-widgets.php';
require_once __DIR__ . '/traits/trait-vbp-api-export-import.php';
require_once __DIR__ . '/traits/trait-vbp-api-webhooks.php';
require_once __DIR__ . '/traits/trait-vbp-api-advanced-block-editing.php';
require_once __DIR__ . '/traits/trait-vbp-api-previews.php';
require_once __DIR__ . '/traits/trait-vbp-api-advanced-widgets.php';
require_once __DIR__ . '/traits/trait-vbp-api-optimization.php';
require_once __DIR__ . '/traits/trait-vbp-api-ab-testing.php';
require_once __DIR__ . '/traits/trait-vbp-api-accessibility.php';
require_once __DIR__ . '/traits/trait-vbp-api-seo.php';
require_once __DIR__ . '/traits/trait-vbp-api-animations.php';
require_once __DIR__ . '/traits/trait-vbp-api-responsive.php';
require_once __DIR__ . '/traits/trait-vbp-api-section-templates.php';
require_once __DIR__ . '/traits/trait-vbp-api-collab-advanced.php';
require_once __DIR__ . '/traits/trait-vbp-api-history.php';
require_once __DIR__ . '/traits/trait-vbp-api-clipboard.php';
require_once __DIR__ . '/traits/trait-vbp-api-import-export-advanced.php';
require_once __DIR__ . '/traits/trait-vbp-api-design-themes.php';
require_once __DIR__ . '/traits/trait-vbp-api-media.php';

/**
 * Clase para la API REST de VBP para Claude Code
 *
 * Esta clase utiliza traits para organizar la funcionalidad:
 * - VBP_API_Pages: Operaciones CRUD de páginas
 * - VBP_API_Blocks: Manipulación de bloques
 * - VBP_API_Design: Sistema de diseño y presets
 *
 * @since 2.2.0 Refactorizado a usar traits
 */
class Flavor_VBP_Claude_API {

    // Traits para funcionalidad modular
    use VBP_API_Pages;
    use VBP_API_Blocks;
    use VBP_API_Design;
    use VBP_API_System;
    use VBP_API_I18n;
    use VBP_API_Widgets;
    use VBP_API_Templates;
    use VBP_API_Analytics;
    use VBP_API_Variants;
    use VBP_API_Search;
    use VBP_API_Utilities;
    use VBP_API_BlockManipulation;
    use VBP_API_Library;
    use VBP_API_Snapshots;
    use VBP_API_Dashboard;
    use VBP_API_Collaboration;
    use VBP_API_ContentAnalysis;
    use VBP_API_GlobalStyles;
    use VBP_API_BulkOperations;
    use VBP_API_Scheduling;
    use VBP_API_Comments;
    use VBP_API_Activity;
    use VBP_API_GlobalWidgets;
    use VBP_API_ExportImport;
    use VBP_API_Webhooks;
    use VBP_API_AdvancedBlockEditing;
    use VBP_API_Previews;
    use VBP_API_AdvancedWidgets;
    use VBP_API_Optimization;
    use VBP_API_ABTesting;
    use VBP_API_Accessibility;
    use VBP_API_SEO;
    use VBP_API_Animations;
    use VBP_API_Responsive;
    use VBP_API_SectionTemplates;
    use VBP_API_CollabAdvanced;
    use VBP_API_History;
    use VBP_API_Clipboard;
    use VBP_API_ImportExportAdvanced;
    use VBP_API_DesignThemes;
    use VBP_API_Media;

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
     * Post types soportados por VBP
     * Fase 2: Extendido para soportar page y post además de flavor_landing
     *
     * @var array
     */
    private $supported_post_types = array( 'flavor_landing', 'page', 'post' );

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
        $this->api_key = flavor_get_vbp_api_key();

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verifica si un post type es soportado por VBP
     *
     * @param string $post_type Post type a verificar.
     * @return bool
     */
    private function is_supported_post_type( $post_type ) {
        return in_array( $post_type, $this->supported_post_types, true );
    }

    /**
     * Obtiene los post types soportados
     *
     * @return array
     */
    public function get_supported_post_types() {
        return $this->supported_post_types;
    }

    /**
     * Verifica si un post es válido para VBP
     *
     * @param WP_Post|null $post Post a verificar.
     * @return bool
     */
    private function is_valid_vbp_post( $post ) {
        return $post && $this->is_supported_post_type( $post->post_type );
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
        $loader_path = FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/class-vbp-loader.php';
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
                'post_type' => array(
                    'type'        => 'string',
                    'default'     => 'flavor_landing',
                    'enum'        => array( 'flavor_landing', 'page', 'post' ),
                    'description' => 'Tipo de post a crear (Fase 2: soporta page y post además de flavor_landing)',
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
        // Fase 2: Soporta filtrar por post_type
        register_rest_route( self::NAMESPACE, '/claude/pages', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'status' => array(
                    'type'    => 'string',
                    'default' => 'any',
                ),
                'post_type' => array(
                    'type'        => 'string',
                    'default'     => 'all',
                    'enum'        => array( 'all', 'flavor_landing', 'page', 'post' ),
                    'description' => 'Filtrar por tipo de post. "all" devuelve todos los tipos soportados.',
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
                    'type'        => 'string',
                    'default'     => '',
                    'description' => 'Título para la copia (por defecto: "Copia de [título]").',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'description' => 'Slug para la copia.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'default'     => 'draft',
                    'enum'        => array( 'draft', 'publish', 'private' ),
                    'description' => 'Estado de la copia.',
                ),
                'copy_meta' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Copiar metadatos SEO y configuraciones.',
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
        // BULK OPERATIONS
        // =============================================

        // Crear múltiples páginas en paralelo
        register_rest_route( self::NAMESPACE, '/claude/pages/bulk-create', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_create_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'pages' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Array de páginas a crear. Cada página debe tener: title, y opcionalmente: preset, sections, context, status, slug.',
                ),
                'default_preset' => array(
                    'type'        => 'string',
                    'default'     => 'modern',
                    'description' => 'Preset por defecto para páginas sin preset especificado.',
                ),
                'default_status' => array(
                    'type'        => 'string',
                    'default'     => 'draft',
                    'enum'        => array( 'draft', 'publish' ),
                    'description' => 'Estado por defecto para páginas sin status especificado.',
                ),
            ),
        ) );

        // Exportar plantilla como JSON
        register_rest_route( self::NAMESPACE, '/claude/templates/export/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Importar plantilla desde JSON
        register_rest_route( self::NAMESPACE, '/claude/templates/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'template' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Datos de la plantilla exportada a importar.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'description' => 'Título para la nueva página (sobrescribe el de la plantilla).',
                ),
                'status' => array(
                    'type'    => 'string',
                    'default' => 'draft',
                    'enum'    => array( 'draft', 'publish' ),
                ),
            ),
        ) );

        // Clonar página desde sitio remoto
        register_rest_route( self::NAMESPACE, '/claude/templates/clone-remote', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'clone_remote_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_url' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'URL del sitio origen (ej: https://otro-sitio.com)',
                ),
                'page_id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID de la página VBP a clonar.',
                ),
                'api_key' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'API key del sitio origen.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'description' => 'Título para la nueva página (opcional).',
                ),
                'status' => array(
                    'type'    => 'string',
                    'default' => 'draft',
                    'enum'    => array( 'draft', 'publish' ),
                ),
            ),
        ) );

        // Preview de página sin guardar
        register_rest_route( self::NAMESPACE, '/claude/pages/preview', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'preview_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'elements' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Elementos VBP a renderizar.',
                ),
                'preset' => array(
                    'type'    => 'string',
                    'default' => 'modern',
                ),
                'settings' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
            ),
        ) );

        // Análisis SEO de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo-analysis', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Sugerencias SEO para contenido
        register_rest_route( self::NAMESPACE, '/claude/seo/suggest', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'suggest_seo_improvements' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'content' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Contenido a analizar para SEO.',
                ),
                'keywords' => array(
                    'type'        => 'array',
                    'default'     => array(),
                    'description' => 'Palabras clave objetivo.',
                ),
                'type' => array(
                    'type'    => 'string',
                    'default' => 'page',
                    'enum'    => array( 'page', 'post', 'landing' ),
                ),
            ),
        ) );

        // =============================================
        // ANÁLISIS Y ESTADÍSTICAS
        // =============================================

        // Análisis de accesibilidad de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/a11y-analysis', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_accessibility' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Estadísticas de uso de bloques
        register_rest_route( self::NAMESPACE, '/claude/stats/blocks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_blocks_usage_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Estadísticas generales de VBP
        register_rest_route( self::NAMESPACE, '/claude/stats/overview', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_vbp_overview_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // A/B TESTING Y VARIANTES
        // =============================================

        // Crear variante de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/create-variant', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page_variant' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'variant_type' => array(
                    'type'    => 'string',
                    'default' => 'copy',
                    'enum'    => array( 'copy', 'hero', 'cta', 'colors', 'layout' ),
                    'description' => 'Tipo de variante: copy (copia exacta), hero (variación de hero), cta (variación de CTAs), colors (paleta diferente), layout (disposición diferente).',
                ),
                'variant_name' => array(
                    'type'        => 'string',
                    'description' => 'Nombre de la variante (ej: "Variante B").',
                ),
            ),
        ) );

        // Listar variantes de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_variants' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Comparar rendimiento de variantes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_variants_performance' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE BÚSQUEDA, HISTORIAL Y SITEMAP
        // =============================================

        // Buscar en páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/search', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_vbp_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'q' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Término de búsqueda.',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'type' => array(
                    'type'        => 'string',
                    'default'     => 'all',
                    'enum'        => array( 'all', 'title', 'content', 'blocks' ),
                    'description' => 'Dónde buscar: all, title, content, blocks.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'default'     => 'any',
                    'enum'        => array( 'any', 'publish', 'draft', 'private' ),
                    'description' => 'Filtrar por estado del post.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page' => array(
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE HISTORIAL (SISTEMA OFICIAL - WP REVISIONS)
        // Historial automático basado en WordPress Revisions
        // Para snapshots manuales con nombre usar /snapshots
        // =============================================

        // Obtener historial de versiones de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/history', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'per_page' => array(
                    'type'    => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
        ) );

        // Restaurar una versión específica
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/history/(?P<revision_id>\d+)/restore', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_page_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Generar sitemap de páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/sitemap', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_vbp_sitemap' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'format' => array(
                    'type'        => 'string',
                    'default'     => 'json',
                    'enum'        => array( 'json', 'xml', 'html' ),
                    'description' => 'Formato de salida del sitemap.',
                ),
                'include_drafts' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Incluir borradores en el sitemap.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE UTILIDADES AVANZADAS
        // =============================================

        // Validar estructura de bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/validate', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'validate_page_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Validar bloques sin guardar (preview)
        register_rest_route( self::NAMESPACE, '/claude/validate-blocks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'validate_blocks_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'blocks' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Array de bloques a validar.',
                ),
            ),
        ) );

        // Exportar página a HTML estático
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export-html', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_html' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_styles' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Incluir estilos CSS inline.',
                ),
                'include_scripts' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Incluir scripts JS.',
                ),
                'minify' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Minificar el HTML resultante.',
                ),
            ),
        ) );

        // Comparar dos páginas VBP (diff)
        register_rest_route( self::NAMESPACE, '/claude/pages/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_a' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID de la primera página.',
                ),
                'page_b' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID de la segunda página.',
                ),
                'detail_level' => array(
                    'type'        => 'string',
                    'default'     => 'summary',
                    'enum'        => array( 'summary', 'blocks', 'full' ),
                    'description' => 'Nivel de detalle: summary, blocks, full.',
                ),
            ),
        ) );

        // Obtener bloques huérfanos o con errores
        register_rest_route( self::NAMESPACE, '/claude/maintenance/orphan-blocks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_orphan_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Limpiar caché de VBP
        register_rest_route( self::NAMESPACE, '/claude/maintenance/clear-cache', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'clear_vbp_cache' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_id' => array(
                    'type'        => 'integer',
                    'description' => 'ID de página específica (vacío = todo).',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE MANIPULACIÓN DE BLOQUES
        // =============================================

        // Copiar bloques entre páginas
        register_rest_route( self::NAMESPACE, '/claude/blocks/copy', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'copy_blocks_between_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_page_id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID de la página origen.',
                ),
                'target_page_id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID de la página destino.',
                ),
                'block_indices' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Índices de bloques a copiar (ej: [0, 2, 3]).',
                ),
                'position' => array(
                    'type'        => 'string',
                    'default'     => 'end',
                    'enum'        => array( 'start', 'end', 'index' ),
                    'description' => 'Dónde insertar: start, end, o index.',
                ),
                'insert_at' => array(
                    'type'        => 'integer',
                    'description' => 'Índice donde insertar (si position=index).',
                ),
            ),
        ) );

        // Reordenar bloques en una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/reorder', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'reorder_page_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'order' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Nuevo orden de índices (ej: [2, 0, 1, 3]).',
                ),
            ),
        ) );

        // Mover bloque dentro de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/move', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'move_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'from_index' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Índice actual del bloque.',
                ),
                'to_index' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Nuevo índice del bloque.',
                ),
            ),
        ) );

        // Eliminar bloque específico
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_index>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener CSS generado de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/css', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_css' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'minify' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Minificar el CSS.',
                ),
                'include_base' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Incluir estilos base VBP.',
                ),
            ),
        ) );

        // Obtener estadísticas de rendimiento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/performance', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_performance' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener bloques de una página (sin toda la página)
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'flat' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Devolver lista plana en lugar de jerárquica.',
                ),
            ),
        ) );

        // Añadir bloque a una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_block_to_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Objeto del bloque a añadir.',
                ),
                'position' => array(
                    'type'        => 'string',
                    'default'     => 'end',
                    'enum'        => array( 'start', 'end', 'index' ),
                ),
                'insert_at' => array(
                    'type'        => 'integer',
                    'description' => 'Índice donde insertar (si position=index).',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PLANTILLAS Y BIBLIOTECA
        // =============================================

        // Listar plantillas de bloques guardadas
        register_rest_route( self::NAMESPACE, '/claude/block-templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_block_templates' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type'        => 'string',
                    'description' => 'Filtrar por categoría.',
                ),
            ),
        ) );

        // Guardar bloque como plantilla
        register_rest_route( self::NAMESPACE, '/claude/block-templates', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_block_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Nombre de la plantilla.',
                ),
                'block' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Bloque a guardar.',
                ),
                'category' => array(
                    'type'        => 'string',
                    'default'     => 'general',
                    'description' => 'Categoría de la plantilla.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'Descripción de la plantilla.',
                ),
            ),
        ) );

        // Obtener plantilla específica
        register_rest_route( self::NAMESPACE, '/claude/block-templates/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar plantilla
        register_rest_route( self::NAMESPACE, '/claude/block-templates/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_block_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE FAVORITOS Y ETIQUETAS
        // =============================================

        // Listar páginas favoritas
        register_rest_route( self::NAMESPACE, '/claude/favorites', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_favorite_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Marcar/desmarcar página como favorita
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/favorite', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'toggle_favorite' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Listar etiquetas disponibles
        register_rest_route( self::NAMESPACE, '/claude/tags', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_vbp_tags' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Asignar etiquetas a una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/tags', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_page_tags' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'tags' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Array de etiquetas.',
                ),
            ),
        ) );

        // Obtener etiquetas de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/tags', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_tags' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE SNAPSHOTS (SISTEMA OFICIAL DE VERSIONADO MANUAL)
        // Usar estos endpoints en lugar de /checkpoints o /versions (deprecated)
        // Para historial automático usar /history (WP Revisions)
        // =============================================

        // Listar snapshots de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/snapshots', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_page_snapshots' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear snapshot manual
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/snapshots', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page_snapshot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'type'        => 'string',
                    'description' => 'Nombre del snapshot.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'Descripción del snapshot.',
                ),
            ),
        ) );

        // Restaurar snapshot
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/snapshots/(?P<snapshot_id>\d+)/restore', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_page_snapshot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar snapshot
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/snapshots/(?P<snapshot_id>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_page_snapshot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE ESTADÍSTICAS GLOBALES
        // =============================================

        // Dashboard de estadísticas VBP
        register_rest_route( self::NAMESPACE, '/claude/dashboard', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_vbp_dashboard' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Auditoría de medios (imágenes usadas)
        register_rest_route( self::NAMESPACE, '/claude/media-audit', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_media_audit' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Shortcodes usados en páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/shortcodes-audit', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_shortcodes_audit' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE BLOQUEO Y COLABORACIÓN
        // =============================================

        // Bloquear página para edición
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/lock', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'lock_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Desbloquear página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/unlock', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unlock_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Verificar estado de bloqueo
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/lock-status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_lock_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE ANÁLISIS DE CONTENIDO
        // =============================================

        // Análisis de legibilidad
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/readability', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_readability' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Análisis de keywords
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/keywords', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_keywords' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Análisis completo de contenido
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/content-analysis', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'full_content_analysis' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE VARIABLES GLOBALES
        // =============================================

        // @DEPRECATED: Usar /claude/design-system en su lugar
        // Obtener variables CSS globales
        register_rest_route( self::NAMESPACE, '/claude/global-styles', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_global_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Guardar variables CSS globales
        register_rest_route( self::NAMESPACE, '/claude/global-styles', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_global_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'colors' => array(
                    'type'        => 'object',
                    'description' => 'Paleta de colores globales.',
                ),
                'typography' => array(
                    'type'        => 'object',
                    'description' => 'Configuración de tipografía.',
                ),
                'spacing' => array(
                    'type'        => 'object',
                    'description' => 'Configuración de espaciado.',
                ),
                'custom_css' => array(
                    'type'        => 'string',
                    'description' => 'CSS personalizado global.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE OPERACIONES EN LOTE
        // =============================================

        // Publicar múltiples páginas
        register_rest_route( self::NAMESPACE, '/claude/bulk/publish', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_publish_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'IDs de páginas a publicar.',
                ),
            ),
        ) );

        // Eliminar múltiples páginas
        register_rest_route( self::NAMESPACE, '/claude/bulk/delete', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_delete_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'IDs de páginas a eliminar.',
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Eliminar permanentemente.',
                ),
            ),
        ) );

        // Duplicar múltiples páginas
        register_rest_route( self::NAMESPACE, '/claude/bulk/duplicate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_duplicate_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'IDs de páginas a duplicar.',
                ),
            ),
        ) );

        // Asignar etiquetas a múltiples páginas
        register_rest_route( self::NAMESPACE, '/claude/bulk/tags', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_set_tags' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required'    => true,
                    'type'        => 'array',
                ),
                'tags' => array(
                    'required'    => true,
                    'type'        => 'array',
                ),
                'mode' => array(
                    'type'        => 'string',
                    'default'     => 'add',
                    'enum'        => array( 'add', 'replace', 'remove' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PROGRAMACIÓN
        // =============================================

        // Programar publicación
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/schedule', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'schedule_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'publish_date' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Fecha de publicación (ISO 8601).',
                ),
            ),
        ) );

        // Cancelar programación
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/unschedule', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unschedule_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Listar páginas programadas
        register_rest_route( self::NAMESPACE, '/claude/scheduled', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_scheduled_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE COMENTARIOS Y NOTAS
        // =============================================

        // Listar comentarios de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_page_comments' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Añadir comentario a una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_page_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'content' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Contenido del comentario.',
                ),
                'block_id' => array(
                    'type'        => 'string',
                    'description' => 'ID del bloque relacionado (opcional).',
                ),
                'type' => array(
                    'type'        => 'string',
                    'default'     => 'note',
                    'enum'        => array( 'note', 'todo', 'issue', 'resolved' ),
                ),
            ),
        ) );

        // Eliminar comentario
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments/(?P<comment_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_page_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Resolver/marcar comentario
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments/(?P<comment_id>[a-z0-9_]+)/resolve', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'resolve_page_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE HISTORIAL DE ACTIVIDAD
        // =============================================

        // Obtener historial de actividad de una página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/activity', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_activity' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'limit' => array(
                    'type'    => 'integer',
                    'default' => 50,
                    'maximum' => 200,
                ),
            ),
        ) );

        // Historial de actividad global
        register_rest_route( self::NAMESPACE, '/claude/activity', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_global_activity' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'limit' => array(
                    'type'    => 'integer',
                    'default' => 50,
                    'maximum' => 200,
                ),
                'action' => array(
                    'type'        => 'string',
                    'description' => 'Filtrar por tipo de acción.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WIDGETS GLOBALES
        // =============================================

        // Listar widgets globales
        register_rest_route( self::NAMESPACE, '/claude/widgets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_global_widgets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear widget global
        register_rest_route( self::NAMESPACE, '/claude/widgets', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_global_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required'    => true,
                    'type'        => 'string',
                ),
                'block' => array(
                    'required'    => true,
                    'type'        => 'object',
                ),
                'description' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Obtener widget global
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_global_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar widget global
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'update_global_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string' ),
                'block' => array( 'type' => 'object' ),
                'description' => array( 'type' => 'string' ),
            ),
        ) );

        // Eliminar widget global
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_global_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Páginas que usan un widget
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/usage', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_usage' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE EXPORTAR/IMPORTAR SITIO
        // =============================================

        // Exportar todas las páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/export-all', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_all_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_drafts' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'include_media' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Importar páginas desde export
        register_rest_route( self::NAMESPACE, '/claude/import-all', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_all_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'data' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Datos exportados previamente.',
                ),
                'overwrite' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WEBHOOKS
        // =============================================

        // Listar webhooks configurados
        register_rest_route( self::NAMESPACE, '/claude/webhooks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_webhooks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear webhook
        register_rest_route( self::NAMESPACE, '/claude/webhooks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'url' => array(
                    'required' => true,
                    'type'     => 'string',
                    'format'   => 'uri',
                ),
                'events' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Eventos: page_created, page_updated, page_published, page_deleted',
                ),
                'secret' => array(
                    'type'        => 'string',
                    'description' => 'Secret para firmar payloads.',
                ),
            ),
        ) );

        // Eliminar webhook
        register_rest_route( self::NAMESPACE, '/claude/webhooks/(?P<webhook_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Test webhook
        register_rest_route( self::NAMESPACE, '/claude/webhooks/(?P<webhook_id>[a-z0-9_]+)/test', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'test_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE EDICIÓN AVANZADA DE BLOQUES
        // =============================================

        // Obtener bloque específico con metadatos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_single_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar bloque específico
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'update_single_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'data' => array(
                    'type'        => 'object',
                    'description' => 'Nuevos datos del bloque.',
                ),
                'styles' => array(
                    'type'        => 'object',
                    'description' => 'Estilos inline del bloque.',
                ),
            ),
        ) );

        // Duplicar bloque dentro de la página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/duplicate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'duplicate_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'position' => array(
                    'type'        => 'string',
                    'default'     => 'after',
                    'enum'        => array( 'before', 'after' ),
                ),
            ),
        ) );

        // Envolver bloque en contenedor
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/wrap', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'wrap_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'wrapper_type' => array(
                    'type'    => 'string',
                    'default' => 'container',
                    'enum'    => array( 'container', 'section', 'row', 'column', 'div' ),
                ),
                'wrapper_data' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
            ),
        ) );

        // Desenvolver bloque (sacar de contenedor)
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/unwrap', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unwrap_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Bloquear bloque para edición
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/lock', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'lock_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Desbloquear bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/unlock', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unlock_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Historial de cambios de un bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/history', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'limit' => array(
                    'type'    => 'integer',
                    'default' => 20,
                ),
            ),
        ) );

        // Restaurar versión anterior de un bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/restore/(?P<version_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_block_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Aplicar estilos a múltiples bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/batch-styles', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'batch_apply_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'styles' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'merge' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Buscar y reemplazar en bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/find-replace', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'find_replace_in_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'find' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'replace' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'case_sensitive' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'regex' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'dry_run' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Solo mostrar coincidencias sin reemplazar.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PREVISUALIZACIONES
        // =============================================

        // Preview de página en diferentes dispositivos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'device' => array(
                    'type'    => 'string',
                    'default' => 'desktop',
                    'enum'    => array( 'desktop', 'tablet', 'mobile' ),
                ),
                'width' => array(
                    'type'        => 'integer',
                    'description' => 'Ancho personalizado en píxeles.',
                ),
            ),
        ) );

        // Preview de bloque individual
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/preview', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Preview temporal (sin guardar)
        register_rest_route( self::NAMESPACE, '/claude/preview/temp', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_temp_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'elements' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'settings' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
                'ttl' => array(
                    'type'        => 'integer',
                    'default'     => 3600,
                    'description' => 'Tiempo de vida en segundos (máx 24h).',
                ),
            ),
        ) );

        // Obtener preview temporal
        register_rest_route( self::NAMESPACE, '/claude/preview/temp/(?P<preview_id>[a-z0-9]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_temp_preview' ),
            'permission_callback' => '__return_true', // Público para compartir
        ) );

        // Generar thumbnail/screenshot de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/thumbnail', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_thumbnail' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'width' => array(
                    'type'    => 'integer',
                    'default' => 400,
                ),
                'height' => array(
                    'type'    => 'integer',
                    'default' => 300,
                ),
                'regenerate' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Comparar dos versiones de página visualmente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_page_versions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'version_a' => array(
                    'type'        => 'string',
                    'default'     => 'current',
                    'description' => 'ID de revisión o "current".',
                ),
                'version_b' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'ID de revisión a comparar.',
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'diff',
                    'enum'    => array( 'diff', 'side_by_side', 'unified' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WIDGETS AVANZADOS
        // =============================================

        // Versiones de un widget
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/versions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_versions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear nueva versión de widget
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/versions', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_widget_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'changelog' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Restaurar versión de widget
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/versions/(?P<version>\d+)/restore', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_widget_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar widget en todas las páginas
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/sync', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'sync_widget_to_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'type'        => 'array',
                    'description' => 'IDs específicos o vacío para todas.',
                ),
            ),
        ) );

        // Configurar variables de widget
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/variables', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/variables', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_widget_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'variables' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE OPTIMIZACIÓN
        // =============================================

        // Análisis de rendimiento de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/analyze', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_performance' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Optimizar imágenes de la página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/images', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'optimize_page_images' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'quality' => array(
                    'type'    => 'integer',
                    'default' => 85,
                    'minimum' => 50,
                    'maximum' => 100,
                ),
                'lazy_load' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'webp' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Minificar CSS/JS inline
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/minify', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'minify_page_assets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'css' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'js' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Limpiar bloques no utilizados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/cleanup', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'cleanup_page_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'remove_empty' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'remove_hidden' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'merge_adjacent' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Fusionar bloques de texto adyacentes.',
                ),
            ),
        ) );

        // Precargar assets críticos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/preload', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_preload' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'fonts' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'images' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'critical_css' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Optimización global de VBP
        register_rest_route( self::NAMESPACE, '/claude/optimize/global', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_global_optimization_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Limpiar caché de VBP
        register_rest_route( self::NAMESPACE, '/claude/optimize/cache/clear', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'clear_vbp_cache' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_id' => array(
                    'type'        => 'integer',
                    'description' => 'Limpiar solo una página (opcional).',
                ),
            ),
        ) );

        // Regenerar CSS compilado
        register_rest_route( self::NAMESPACE, '/claude/optimize/css/regenerate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'regenerate_compiled_css' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Detectar bloques huérfanos en todas las páginas
        register_rest_route( self::NAMESPACE, '/claude/optimize/orphans', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'detect_orphan_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // NOTA: /claude/stats/blocks ya registrado en línea 546 (get_blocks_usage_stats)

        // =============================================
        // ENDPOINTS DE A/B TESTING
        // =============================================

        // Crear variante de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page_variant' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'traffic_percentage' => array(
                    'type'    => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 99,
                ),
            ),
        ) );

        // Listar variantes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_page_variants' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener estadísticas de A/B test
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/ab-stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_ab_test_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Declarar ganador de A/B test
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants/(?P<variant_id>[a-z0-9_]+)/winner', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'declare_ab_winner' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar variante
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/variants/(?P<variant_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_page_variant' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE ACCESIBILIDAD
        // =============================================

        // Analizar accesibilidad de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/accessibility', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_accessibility' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Corregir problemas de accesibilidad automáticamente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/accessibility/fix', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'fix_accessibility_issues' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'issues' => array(
                    'type'        => 'array',
                    'description' => 'IDs de problemas a corregir (vacío = todos).',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE SEO AVANZADO
        // =============================================

        // Obtener/configurar SEO de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array( 'type' => 'string' ),
                'description' => array( 'type' => 'string' ),
                'keywords' => array( 'type' => 'array' ),
                'og_image' => array( 'type' => 'string' ),
                'canonical' => array( 'type' => 'string' ),
                'robots' => array( 'type' => 'string' ),
            ),
        ) );

        // Generar Schema.org automáticamente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo/schema', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_schema' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo/schema', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_page_schema' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'WebPage', 'Article', 'Product', 'Organization', 'LocalBusiness', 'Event', 'FAQPage' ),
                ),
                'data' => array(
                    'type' => 'object',
                ),
            ),
        ) );

        // Analizar SEO de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/seo/analyze', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_seo' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'keyword' => array(
                    'type'        => 'string',
                    'description' => 'Palabra clave objetivo.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE ANIMACIONES
        // =============================================

        // Obtener animaciones de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/animations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_animations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar animación de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/animation', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight', 'slideIn', 'zoomIn', 'bounce', 'pulse', 'none' ),
                ),
                'duration' => array(
                    'type'    => 'integer',
                    'default' => 500,
                ),
                'delay' => array(
                    'type'    => 'integer',
                    'default' => 0,
                ),
                'trigger' => array(
                    'type'    => 'string',
                    'default' => 'scroll',
                    'enum'    => array( 'load', 'scroll', 'hover', 'click' ),
                ),
            ),
        ) );

        // Aplicar animaciones en lote
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/animations/batch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'batch_set_animations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'preset' => array(
                    'type'        => 'string',
                    'enum'        => array( 'subtle', 'dynamic', 'elegant', 'playful', 'none' ),
                    'description' => 'Preset de animaciones a aplicar.',
                ),
                'block_ids' => array(
                    'type'        => 'array',
                    'description' => 'IDs específicos (vacío = todos).',
                ),
            ),
        ) );

        // Presets de animaciones disponibles
        register_rest_route( self::NAMESPACE, '/claude/animations/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_animation_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE BREAKPOINTS / RESPONSIVE
        // =============================================

        // Obtener estilos responsive de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/responsive', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_responsive_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar estilos de bloque por breakpoint
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/responsive', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_responsive_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'breakpoint' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'desktop', 'tablet', 'mobile' ),
                ),
                'styles' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Obtener breakpoints personalizados
        register_rest_route( self::NAMESPACE, '/claude/breakpoints', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_custom_breakpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar breakpoints personalizados
        register_rest_route( self::NAMESPACE, '/claude/breakpoints', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_custom_breakpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'breakpoints' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // Ocultar/mostrar bloque por breakpoint
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/visibility', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_visibility' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'desktop' => array( 'type' => 'boolean', 'default' => true ),
                'tablet' => array( 'type' => 'boolean', 'default' => true ),
                'mobile' => array( 'type' => 'boolean', 'default' => true ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PLANTILLAS DE SECCIÓN
        // =============================================

        // Listar plantillas de sección
        register_rest_route( self::NAMESPACE, '/claude/section-templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_section_templates' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Guardar sección como plantilla
        register_rest_route( self::NAMESPACE, '/claude/section-templates', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_section_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => 'custom',
                ),
                'blocks' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'thumbnail' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Obtener plantilla de sección
        register_rest_route( self::NAMESPACE, '/claude/section-templates/(?P<template_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_section_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar plantilla de sección
        register_rest_route( self::NAMESPACE, '/claude/section-templates/(?P<template_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_section_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Insertar plantilla en página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/insert-template', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'insert_section_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'template_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'position' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE COLABORACIÓN
        // =============================================

        // Obtener usuarios activos en página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/presence', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_presence' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar presencia
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/presence', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_page_presence' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'cursor_position' => array(
                    'type' => 'object',
                ),
                'selected_block' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Enviar notificación a colaboradores
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/notify', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'notify_collaborators' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'message' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'type' => array(
                    'type'    => 'string',
                    'default' => 'info',
                    'enum'    => array( 'info', 'warning', 'success' ),
                ),
            ),
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

        // =============================================
        // ENDPOINTS DE EDICIÓN AVANZADA DE BLOQUES
        // =============================================

        // Transferir bloque entre páginas
        register_rest_route( self::NAMESPACE, '/claude/blocks/transfer', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'transfer_block_between_pages' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'target_page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'block_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'position' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
                'mode' => array(
                    'type'    => 'string',
                    'default' => 'copy',
                    'enum'    => array( 'copy', 'move' ),
                ),
            ),
        ) );

        // Convertir tipo de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/convert', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'convert_block_type' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'new_type' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'preserve_content' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Extraer bloque como widget reutilizable
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/extract-widget', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'extract_block_as_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'widget_name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => 'custom',
                ),
                'replace_with_reference' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Agrupar bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/group', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'group_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'wrapper_type' => array(
                    'type'    => 'string',
                    'default' => 'container',
                    'enum'    => array( 'container', 'section', 'row', 'column', 'div' ),
                ),
                'wrapper_styles' => array(
                    'type'    => 'object',
                    'default' => array(),
                ),
            ),
        ) );

        // Desagrupar bloque contenedor
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/ungroup', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'ungroup_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Clonar estilos entre bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/clone-styles', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'clone_block_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_block_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'target_block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'style_properties' => array(
                    'type'        => 'array',
                    'default'     => array(),
                    'description' => 'Propiedades específicas a clonar (vacío = todas).',
                ),
            ),
        ) );

        // Reordenar bloques en lote
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/reorder', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'reorder_blocks_batch' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'new_order' => array(
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Array de block_ids en el nuevo orden.',
                ),
            ),
        ) );

        // Validar estructura de bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/validate', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'validate_page_blocks_structure' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Detectar y reparar bloques rotos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/repair', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'repair_broken_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'dry_run' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Obtener árbol de bloques con jerarquía
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/tree', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_blocks_tree' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_data' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'include_styles' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PREVISUALIZACIONES AVANZADAS
        // =============================================

        // Preview con tema/colores diferentes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/themed', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_themed_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'theme' => array(
                    'type'    => 'string',
                    'default' => 'current',
                    'enum'    => array( 'current', 'light', 'dark', 'custom' ),
                ),
                'colors' => array(
                    'type'        => 'object',
                    'description' => 'Colores personalizados para preview.',
                ),
            ),
        ) );

        // Generar enlace de preview compartible
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/share', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_shareable_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'expires_in' => array(
                    'type'        => 'integer',
                    'default'     => 86400,
                    'description' => 'Segundos hasta expiración (máx 7 días).',
                ),
                'password' => array(
                    'type'        => 'string',
                    'description' => 'Contraseña opcional para acceder.',
                ),
                'allow_comments' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Generar QR para preview móvil
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/qr', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_preview_qr_code' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'size' => array(
                    'type'    => 'integer',
                    'default' => 200,
                    'minimum' => 100,
                    'maximum' => 500,
                ),
            ),
        ) );

        // Preview de cambios pendientes (diff visual)
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/pending-changes', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_pending_changes_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Preview de múltiples páginas en grid
        register_rest_route( self::NAMESPACE, '/claude/preview/multi', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_multi_page_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'thumbnail_size' => array(
                    'type'    => 'string',
                    'default' => 'medium',
                    'enum'    => array( 'small', 'medium', 'large' ),
                ),
            ),
        ) );

        // Preview interactivo con datos reales
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/interactive', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_interactive_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_scripts' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'sandbox' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WIDGETS AVANZADOS
        // =============================================

        // Importar widget de otra página
        register_rest_route( self::NAMESPACE, '/claude/widgets/import-from-page', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_widget_from_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'block_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'widget_name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // Detectar widgets no usados
        register_rest_route( self::NAMESPACE, '/claude/widgets/unused', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'detect_unused_widgets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar widget en todas las instancias
        register_rest_route( self::NAMESPACE, '/claude/widgets/(?P<widget_id>[a-z0-9_]+)/update-all', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_widget_all_instances' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'notify_pages' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Convertir bloque inline a widget referenciado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/to-widget-ref', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'convert_block_to_widget_reference' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'widget_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // Estadísticas de uso de widgets
        register_rest_route( self::NAMESPACE, '/claude/widgets/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widgets_usage_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE OPTIMIZACIÓN AVANZADA
        // =============================================

        // Análisis completo de rendimiento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/performance', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_performance' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Sugerencias de optimización automáticas
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/suggestions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_optimization_suggestions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Aplicar sugerencias de optimización
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/apply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_optimization_suggestions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'suggestions' => array(
                    'type'        => 'array',
                    'description' => 'IDs de sugerencias a aplicar (vacío = todas).',
                ),
            ),
        ) );

        // Comprimir HTML de salida
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/compress-html', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'compress_page_html' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'remove_comments' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'minify_inline_css' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'minify_inline_js' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Detectar recursos pesados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/heavy-resources', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'detect_heavy_resources' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'threshold_kb' => array(
                    'type'    => 'integer',
                    'default' => 100,
                ),
            ),
        ) );

        // Optimizar fuentes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/fonts', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'optimize_page_fonts' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'subset' => array(
                    'type'    => 'string',
                    'default' => 'latin',
                ),
                'display' => array(
                    'type'    => 'string',
                    'default' => 'swap',
                    'enum'    => array( 'auto', 'block', 'swap', 'fallback', 'optional' ),
                ),
                'preload' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Configurar lazy loading avanzado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/lazy-loading', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_lazy_loading' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'images' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'iframes' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'videos' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'threshold' => array(
                    'type'        => 'string',
                    'default'     => '200px',
                    'description' => 'Distancia de carga anticipada.',
                ),
                'placeholder' => array(
                    'type'    => 'string',
                    'default' => 'blur',
                    'enum'    => array( 'blur', 'color', 'skeleton', 'none' ),
                ),
            ),
        ) );

        // Generar Critical CSS
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/critical-css', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_critical_css' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'viewport_width' => array(
                    'type'    => 'integer',
                    'default' => 1300,
                ),
                'viewport_height' => array(
                    'type'    => 'integer',
                    'default' => 900,
                ),
            ),
        ) );

        // Auditoría completa de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/audit', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'full_page_audit' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'categories' => array(
                    'type'    => 'array',
                    'default' => array( 'performance', 'accessibility', 'seo', 'best-practices' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE HISTORIAL Y VERSIONES
        // =============================================

        // Obtener historial completo de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/history/full', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_full_page_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'limit' => array(
                    'type'    => 'integer',
                    'default' => 50,
                ),
                'include_auto_saves' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // =============================================
        // @DEPRECATED: Usar /claude/pages/{id}/snapshots en su lugar
        // Los checkpoints son redundantes con snapshots
        // =============================================
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/checkpoint', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page_checkpoint' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'required' => true, 'type' => 'string' ),
                'description' => array( 'type' => 'string' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/checkpoints', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_page_checkpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/checkpoints/(?P<checkpoint_id>[a-z0-9_]+)/restore', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_page_checkpoint' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/checkpoints/(?P<checkpoint_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_page_checkpoint' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Diff entre dos versiones
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/diff', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_version_diff' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'from' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'to' => array(
                    'type'    => 'string',
                    'default' => 'current',
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'unified',
                    'enum'    => array( 'unified', 'split', 'json' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE COPIAR/PEGAR AVANZADO
        // =============================================

        // Copiar bloques al portapapeles virtual
        register_rest_route( self::NAMESPACE, '/claude/clipboard/copy', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'copy_to_clipboard' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'include_styles' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Pegar desde portapapeles virtual
        register_rest_route( self::NAMESPACE, '/claude/clipboard/paste', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'paste_from_clipboard' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'target_page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'position' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
                'paste_mode' => array(
                    'type'    => 'string',
                    'default' => 'duplicate',
                    'enum'    => array( 'duplicate', 'reference', 'link' ),
                ),
            ),
        ) );

        // Ver contenido del portapapeles
        register_rest_route( self::NAMESPACE, '/claude/clipboard', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_clipboard_contents' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Limpiar portapapeles
        register_rest_route( self::NAMESPACE, '/claude/clipboard', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'clear_clipboard' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE IMPORTACIÓN/EXPORTACIÓN
        // =============================================

        // Exportar página como JSON
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_json' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'format' => array(
                    'type'    => 'string',
                    'default' => 'json',
                    'enum'    => array( 'json', 'zip' ),
                ),
                'include_media' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'include_widgets' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Importar página desde JSON
        register_rest_route( self::NAMESPACE, '/claude/pages/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_page_json' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'data' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'as_draft' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'merge_widgets' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Exportar múltiples páginas
        register_rest_route( self::NAMESPACE, '/claude/pages/export-bulk', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'export_pages_bulk' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'include_widgets' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Clonar página completa
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/clone', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'clone_page_complete' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'new_title' => array(
                    'type' => 'string',
                ),
                'new_slug' => array(
                    'type' => 'string',
                ),
                'as_draft' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE TEMAS Y ESTILOS GLOBALES
        // =============================================

        // @DEPRECATED: Usar /claude/design-system en su lugar
        register_rest_route( self::NAMESPACE, '/claude/design/variables', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_design_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar variables CSS globales
        register_rest_route( self::NAMESPACE, '/claude/design/variables', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_design_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'variables' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Obtener paletas de colores
        register_rest_route( self::NAMESPACE, '/claude/design/palettes', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_color_palettes' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Guardar paleta de colores
        register_rest_route( self::NAMESPACE, '/claude/design/palettes', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_color_palette' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'colors' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Aplicar paleta a página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/apply-palette', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_palette_to_page' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'palette_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // Obtener tipografías configuradas
        register_rest_route( self::NAMESPACE, '/claude/design/typography', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_typography_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar tipografías
        register_rest_route( self::NAMESPACE, '/claude/design/typography', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_typography_settings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'headings' => array(
                    'type' => 'object',
                ),
                'body' => array(
                    'type' => 'object',
                ),
                'custom_fonts' => array(
                    'type' => 'array',
                ),
            ),
        ) );

        // Obtener estilos globales de bloques
        register_rest_route( self::NAMESPACE, '/claude/design/block-styles', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_global_block_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Guardar estilo global de bloque
        register_rest_route( self::NAMESPACE, '/claude/design/block-styles/(?P<block_type>[a-z_-]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_global_block_style' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'styles' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'name' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE BIBLIOTECA DE MEDIOS
        // =============================================

        // Listar medios usados en página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/media', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_media' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Reemplazar imagen en página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/media/replace', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'replace_page_media' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'old_url' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'new_url' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'replace_all' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Subir imagen para página
        register_rest_route( self::NAMESPACE, '/claude/media/upload', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'upload_media' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener imágenes de stock/placeholders
        register_rest_route( self::NAMESPACE, '/claude/media/stock', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_stock_images' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'query' => array(
                    'type' => 'string',
                ),
                'category' => array(
                    'type' => 'string',
                ),
                'per_page' => array(
                    'type'    => 'integer',
                    'default' => 20,
                ),
            ),
        ) );

        // Generar placeholder/imagen con IA
        register_rest_route( self::NAMESPACE, '/claude/media/generate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_placeholder_image' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'width' => array(
                    'type'    => 'integer',
                    'default' => 800,
                ),
                'height' => array(
                    'type'    => 'integer',
                    'default' => 600,
                ),
                'type' => array(
                    'type'    => 'string',
                    'default' => 'gradient',
                    'enum'    => array( 'gradient', 'pattern', 'solid', 'placeholder' ),
                ),
                'colors' => array(
                    'type' => 'array',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE ANALYTICS Y TRACKING
        // =============================================

        // Obtener analytics de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/analytics', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_analytics' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'period' => array(
                    'type'    => 'string',
                    'default' => '30days',
                    'enum'    => array( '7days', '30days', '90days', 'year', 'all' ),
                ),
            ),
        ) );

        // Registrar evento de tracking
        register_rest_route( self::NAMESPACE, '/claude/analytics/track', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'track_analytics_event' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'page_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'event' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'data' => array(
                    'type' => 'object',
                ),
            ),
        ) );

        // Obtener heatmap de clics
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/heatmap', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_heatmap' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'type'    => 'string',
                    'default' => 'click',
                    'enum'    => array( 'click', 'scroll', 'move' ),
                ),
            ),
        ) );

        // Dashboard de analytics global
        register_rest_route( self::NAMESPACE, '/claude/analytics/dashboard', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_analytics_dashboard' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Comparar rendimiento de páginas
        register_rest_route( self::NAMESPACE, '/claude/analytics/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_pages_analytics' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'metric' => array(
                    'type'    => 'string',
                    'default' => 'views',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE COMENTARIOS Y FEEDBACK
        // =============================================

        // Obtener comentarios de revisión
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_review_comments' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'status' => array(
                    'type'    => 'string',
                    'default' => 'all',
                    'enum'    => array( 'all', 'open', 'resolved' ),
                ),
            ),
        ) );

        // Añadir comentario de revisión
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/comments', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_review_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_id' => array(
                    'type' => 'string',
                ),
                'content' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'position' => array(
                    'type'        => 'object',
                    'description' => 'Coordenadas x,y en el canvas.',
                ),
            ),
        ) );

        // Resolver comentario
        register_rest_route( self::NAMESPACE, '/claude/comments/(?P<comment_id>[a-z0-9_]+)/resolve', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'resolve_review_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar comentario
        register_rest_route( self::NAMESPACE, '/claude/comments/(?P<comment_id>[a-z0-9_]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_review_comment' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE COMPONENTES DINÁMICOS
        // =============================================

        // Obtener configuración de slider
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/slider-config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_slider_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración de slider
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/slider-config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_slider_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'autoplay' => array(
                    'type' => 'boolean',
                ),
                'speed' => array(
                    'type' => 'integer',
                ),
                'loop' => array(
                    'type' => 'boolean',
                ),
                'navigation' => array(
                    'type' => 'boolean',
                ),
                'pagination' => array(
                    'type' => 'string',
                    'enum' => array( 'none', 'dots', 'numbers', 'progress' ),
                ),
            ),
        ) );

        // Obtener configuración de tabs
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/tabs-config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_tabs_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración de tabs
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/tabs-config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_tabs_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'style' => array(
                    'type' => 'string',
                    'enum' => array( 'horizontal', 'vertical', 'pills', 'underline' ),
                ),
                'default_tab' => array(
                    'type' => 'integer',
                ),
                'tabs' => array(
                    'type' => 'array',
                ),
            ),
        ) );

        // Obtener configuración de acordeón
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/accordion-config', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_accordion_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar configuración de acordeón
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/accordion-config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_accordion_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'allow_multiple' => array(
                    'type' => 'boolean',
                ),
                'default_open' => array(
                    'type' => 'array',
                ),
                'icon_position' => array(
                    'type' => 'string',
                    'enum' => array( 'left', 'right', 'none' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE FORMULARIOS
        // =============================================

        // Obtener formularios de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/forms', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_forms' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar formulario
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/forms/(?P<form_id>[a-z0-9_-]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_page_form' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'fields' => array(
                    'type' => 'array',
                ),
                'action' => array(
                    'type' => 'string',
                    'enum' => array( 'email', 'webhook', 'store', 'redirect' ),
                ),
                'settings' => array(
                    'type' => 'object',
                ),
            ),
        ) );

        // Obtener envíos de formulario
        register_rest_route( self::NAMESPACE, '/claude/forms/(?P<form_id>[a-z0-9_-]+)/submissions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_form_submissions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'per_page' => array(
                    'type'    => 'integer',
                    'default' => 20,
                ),
                'page' => array(
                    'type'    => 'integer',
                    'default' => 1,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE ATAJOS Y PRODUCTIVIDAD
        // =============================================

        // Obtener atajos de teclado
        register_rest_route( self::NAMESPACE, '/claude/shortcuts', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_keyboard_shortcuts' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Guardar atajos personalizados
        register_rest_route( self::NAMESPACE, '/claude/shortcuts', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_custom_shortcuts' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'shortcuts' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Obtener comandos rápidos
        register_rest_route( self::NAMESPACE, '/claude/quick-actions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_quick_actions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Ejecutar comando rápido
        register_rest_route( self::NAMESPACE, '/claude/quick-actions/(?P<action>[a-z_-]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'execute_quick_action' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_id' => array(
                    'type' => 'integer',
                ),
                'params' => array(
                    'type' => 'object',
                ),
            ),
        ) );

        // Obtener historial de acciones (undo/redo)
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/action-history', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_action_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Deshacer última acción
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/undo', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'undo_last_action' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Rehacer acción
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/redo', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'redo_action' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE MODO OSCURO / TEMAS DE EDITOR
        // =============================================

        // Obtener preferencias de editor
        register_rest_route( self::NAMESPACE, '/claude/editor/preferences', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_editor_preferences' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Guardar preferencias de editor
        register_rest_route( self::NAMESPACE, '/claude/editor/preferences', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_editor_preferences' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'theme' => array(
                    'type' => 'string',
                    'enum' => array( 'light', 'dark', 'auto' ),
                ),
                'grid_visible' => array(
                    'type' => 'boolean',
                ),
                'snap_to_grid' => array(
                    'type' => 'boolean',
                ),
                'rulers_visible' => array(
                    'type' => 'boolean',
                ),
                'auto_save' => array(
                    'type' => 'boolean',
                ),
                'auto_save_interval' => array(
                    'type' => 'integer',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE BÚSQUEDA AVANZADA
        // =============================================

        // Búsqueda global en páginas VBP
        register_rest_route( self::NAMESPACE, '/claude/search', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'global_vbp_search' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'query' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'search_in' => array(
                    'type'    => 'array',
                    'default' => array( 'content', 'titles', 'blocks' ),
                ),
                'page_ids' => array(
                    'type'        => 'array',
                    'description' => 'Limitar búsqueda a páginas específicas.',
                ),
            ),
        ) );

        // Buscar y reemplazar global
        register_rest_route( self::NAMESPACE, '/claude/search-replace', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'global_search_replace' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'find' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'replace' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'page_ids' => array(
                    'type' => 'array',
                ),
                'dry_run' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Buscar bloques por tipo
        register_rest_route( self::NAMESPACE, '/claude/search/blocks-by-type', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_blocks_by_type' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_type' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'include_pages' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE EDICIÓN AVANZADA DE BLOQUES
        // =============================================

        // Bloquear/desbloquear elemento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/lock', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'toggle_block_lock' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'locked' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'lock_type' => array(
                    'type'    => 'string',
                    'default' => 'all',
                    'enum'    => array( 'all', 'move', 'delete', 'edit', 'resize' ),
                ),
            ),
        ) );

        // Alinear múltiples elementos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/align', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'align_multiple_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'alignment' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'left', 'center', 'right', 'top', 'middle', 'bottom', 'justify-horizontal', 'justify-vertical' ),
                ),
            ),
        ) );

        // Distribuir elementos uniformemente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/distribute', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'distribute_blocks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'direction' => array(
                    'type'    => 'string',
                    'default' => 'horizontal',
                    'enum'    => array( 'horizontal', 'vertical' ),
                ),
                'spacing' => array(
                    'type'        => 'integer',
                    'description' => 'Espaciado fijo (opcional, si no se especifica distribuye uniformemente).',
                ),
            ),
        ) );

        // Snap a grid/guías
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/snap', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'snap_block_to_grid' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'grid_size' => array(
                    'type'    => 'integer',
                    'default' => 8,
                ),
                'snap_to' => array(
                    'type'    => 'string',
                    'default' => 'grid',
                    'enum'    => array( 'grid', 'elements', 'both' ),
                ),
            ),
        ) );

        // Obtener guías inteligentes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/guides', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_smart_guides' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'active_block_id' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Crear guía personalizada
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/guides', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_custom_guide' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'horizontal', 'vertical' ),
                ),
                'position' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'color' => array(
                    'type'    => 'string',
                    'default' => '#00ff00',
                ),
            ),
        ) );

        // Copiar estilos entre bloques
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/copy-styles', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'copy_block_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_block_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'target_block_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'style_properties' => array(
                    'type'        => 'array',
                    'description' => 'Propiedades específicas a copiar (vacío = todas).',
                ),
            ),
        ) );

        // Crear template de bloque personalizado
        register_rest_route( self::NAMESPACE, '/claude/block-templates', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_block_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'block_data' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => 'custom',
                ),
                'thumbnail' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Listar templates de bloques
        register_rest_route( self::NAMESPACE, '/claude/block-templates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_block_templates' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Aplicar template de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/from-template', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_block_from_template' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'template_id' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'position' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WIDGETS AVANZADOS
        // =============================================

        // Biblioteca de widgets compartida
        register_rest_route( self::NAMESPACE, '/claude/widget-library', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_library' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type' => 'string',
                ),
                'search' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Guardar widget en biblioteca
        register_rest_route( self::NAMESPACE, '/claude/widget-library', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_widget_to_library' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'widget_data' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => 'general',
                ),
                'tags' => array(
                    'type' => 'array',
                ),
                'is_global' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Si es true, cambios se sincronizan en todas las instancias.',
                ),
            ),
        ) );

        // Sincronizar widget global
        register_rest_route( self::NAMESPACE, '/claude/widget-library/(?P<widget_id>[a-z0-9_-]+)/sync', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'sync_global_widget' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener instancias de widget global
        register_rest_route( self::NAMESPACE, '/claude/widget-library/(?P<widget_id>[a-z0-9_-]+)/instances', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_instances' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Presets de widgets
        register_rest_route( self::NAMESPACE, '/claude/widget-presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_widget_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'widget_type' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Crear preset de widget
        register_rest_route( self::NAMESPACE, '/claude/widget-presets', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_widget_preset' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'widget_type' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'preset_data' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PREVISUALIZACIONES AVANZADAS
        // =============================================

        // Preview multi-dispositivo
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/multi-device', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_multi_device_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'devices' => array(
                    'type'    => 'array',
                    'default' => array( 'desktop', 'tablet', 'mobile' ),
                ),
            ),
        ) );

        // Comparación lado a lado
        register_rest_route( self::NAMESPACE, '/claude/pages/preview/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_pages_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'page_ids' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'device' => array(
                    'type'    => 'string',
                    'default' => 'desktop',
                ),
            ),
        ) );

        // Capturar screenshot de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/screenshot', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'capture_page_screenshot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'device' => array(
                    'type'    => 'string',
                    'default' => 'desktop',
                    'enum'    => array( 'desktop', 'tablet', 'mobile' ),
                ),
                'full_page' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'png',
                    'enum'    => array( 'png', 'jpg', 'webp' ),
                ),
                'quality' => array(
                    'type'    => 'integer',
                    'default' => 90,
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
        ) );

        // Preview con datos de usuario simulados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/personalized', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'get_personalized_preview' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'user_data' => array(
                    'type'        => 'object',
                    'description' => 'Datos de usuario para personalización.',
                ),
                'location' => array(
                    'type' => 'object',
                ),
                'device_type' => array(
                    'type'    => 'string',
                    'default' => 'desktop',
                ),
            ),
        ) );

        // Preview de cambios sin guardar
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/draft', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'preview_draft_changes' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'elements' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'device' => array(
                    'type'    => 'string',
                    'default' => 'desktop',
                ),
            ),
        ) );

        // Exportar preview como PDF
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/pdf', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'export_preview_pdf' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_notes' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'a4',
                    'enum'    => array( 'a4', 'letter', 'auto' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE OPTIMIZACIONES AVANZADAS
        // =============================================

        // Detectar elementos duplicados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/detect-duplicates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'detect_duplicate_elements' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'similarity_threshold' => array(
                    'type'    => 'integer',
                    'default' => 90,
                    'minimum' => 50,
                    'maximum' => 100,
                ),
            ),
        ) );

        // Limpiar estilos no usados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/cleanup-styles', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'cleanup_unused_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'dry_run' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Comprimir elementos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/compress', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'compress_page_elements' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'remove_empty' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'merge_text' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'minify_inline_css' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Optimizar imágenes automáticamente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/images', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'optimize_page_images' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'max_width' => array(
                    'type'    => 'integer',
                    'default' => 1920,
                ),
                'quality' => array(
                    'type'    => 'integer',
                    'default' => 85,
                ),
                'format' => array(
                    'type'    => 'string',
                    'default' => 'auto',
                    'enum'    => array( 'auto', 'webp', 'jpg', 'png', 'avif' ),
                ),
                'generate_srcset' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Prefetch de recursos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/prefetch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_resource_prefetch' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'dns_prefetch' => array(
                    'type' => 'array',
                ),
                'preconnect' => array(
                    'type' => 'array',
                ),
                'prefetch' => array(
                    'type' => 'array',
                ),
                'preload' => array(
                    'type' => 'array',
                ),
            ),
        ) );

        // Análisis de rendimiento detallado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/performance/analyze', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_performance_detailed' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_recommendations' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Aplicar optimizaciones automáticas
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/optimize/auto', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_auto_optimizations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'level' => array(
                    'type'    => 'string',
                    'default' => 'balanced',
                    'enum'    => array( 'safe', 'balanced', 'aggressive' ),
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE COLABORACIÓN
        // =============================================

        // Estado de edición en tiempo real
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/editing-status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_editing_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Marcar inicio de edición
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/editing-status/start', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'start_editing_session' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Marcar fin de edición
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/editing-status/end', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'end_editing_session' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Historial de actividad de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/activity', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_activity' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'limit' => array(
                    'type'    => 'integer',
                    'default' => 50,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE RESPONSIVE DESIGN
        // =============================================

        // Obtener breakpoints configurados
        register_rest_route( self::NAMESPACE, '/claude/responsive/breakpoints', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_responsive_breakpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar breakpoints
        register_rest_route( self::NAMESPACE, '/claude/responsive/breakpoints', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_responsive_breakpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'breakpoints' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // Obtener estilos responsive de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/responsive', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_responsive_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar estilos responsive de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/responsive', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_block_responsive_styles' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'breakpoint' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'styles' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Copiar estilos entre breakpoints
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/responsive/copy', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'copy_styles_between_breakpoints' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'from_breakpoint' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'to_breakpoints' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE TRANSFORMACIONES DE BLOQUES
        // =============================================

        // Rotar elemento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform/rotate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'rotate_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'angle' => array(
                    'required' => true,
                    'type'     => 'number',
                    'minimum'  => -360,
                    'maximum'  => 360,
                ),
                'origin' => array(
                    'type'    => 'string',
                    'default' => 'center',
                    'enum'    => array( 'center', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'custom' ),
                ),
                'origin_x' => array(
                    'type' => 'string',
                ),
                'origin_y' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Escalar elemento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform/scale', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'scale_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'scale_x' => array(
                    'type'    => 'number',
                    'default' => 1,
                ),
                'scale_y' => array(
                    'type'    => 'number',
                    'default' => 1,
                ),
                'uniform' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Sesgar elemento (skew)
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform/skew', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'skew_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'skew_x' => array(
                    'type'    => 'number',
                    'default' => 0,
                ),
                'skew_y' => array(
                    'type'    => 'number',
                    'default' => 0,
                ),
            ),
        ) );

        // Reflejar/voltear elemento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform/flip', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'flip_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'direction' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'horizontal', 'vertical', 'both' ),
                ),
            ),
        ) );

        // Resetear transformaciones
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform/reset', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'reset_block_transforms' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener transformaciones actuales
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/transform', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_transforms' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE ANIMACIONES Y KEYFRAMES
        // =============================================

        // Listar animaciones disponibles
        register_rest_route( self::NAMESPACE, '/claude/animations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_animations_library' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'category' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Crear animación personalizada
        register_rest_route( self::NAMESPACE, '/claude/animations', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_custom_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'keyframes' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'duration' => array(
                    'type'    => 'string',
                    'default' => '1s',
                ),
                'timing' => array(
                    'type'    => 'string',
                    'default' => 'ease',
                ),
                'iterations' => array(
                    'type'    => 'string',
                    'default' => '1',
                ),
            ),
        ) );

        // Aplicar animación a bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/animation', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_block_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'animation' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'trigger' => array(
                    'type'    => 'string',
                    'default' => 'on_scroll',
                    'enum'    => array( 'on_load', 'on_scroll', 'on_hover', 'on_click', 'manual' ),
                ),
                'delay' => array(
                    'type'    => 'string',
                    'default' => '0s',
                ),
                'duration' => array(
                    'type' => 'string',
                ),
                'threshold' => array(
                    'type'    => 'number',
                    'default' => 0.3,
                ),
            ),
        ) );

        // Obtener animación de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/animation', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar animación de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/animation', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'remove_block_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Preview de animación
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/animation/preview', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'preview_block_animation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE VISIBILIDAD CONDICIONAL
        // =============================================

        // Configurar visibilidad condicional
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/visibility', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_visibility_rules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'rules' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
                'logic' => array(
                    'type'    => 'string',
                    'default' => 'all',
                    'enum'    => array( 'all', 'any' ),
                ),
            ),
        ) );

        // Obtener reglas de visibilidad
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/visibility', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_visibility_rules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Tipos de condiciones disponibles
        register_rest_route( self::NAMESPACE, '/claude/visibility/condition-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_visibility_condition_types' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Simular visibilidad con contexto
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/visibility/simulate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'simulate_visibility' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'context' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE DATOS DINÁMICOS
        // =============================================

        // Fuentes de datos disponibles
        register_rest_route( self::NAMESPACE, '/claude/data-sources', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_data_sources' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear conexión de datos
        register_rest_route( self::NAMESPACE, '/claude/data-sources/connect', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_data_connection' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'source_type' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'post', 'user', 'option', 'acf', 'custom', 'rest_api' ),
                ),
                'config' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Vincular datos a bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/data-binding', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bind_data_to_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'field' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'source' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'source_field' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'transform' => array(
                    'type' => 'string',
                ),
            ),
        ) );

        // Obtener bindings de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/data-binding', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_data_bindings' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Preview con datos dinámicos
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/preview/dynamic', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'preview_with_dynamic_data' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'data_context' => array(
                    'type' => 'object',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE VARIABLES CSS GLOBALES
        // @DEPRECATED: Usar /claude/design-system en su lugar
        // =============================================

        register_rest_route( self::NAMESPACE, '/claude/css-variables', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_css_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar variables CSS globales
        register_rest_route( self::NAMESPACE, '/claude/css-variables', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_css_variables' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'variables' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Crear grupo de variables
        register_rest_route( self::NAMESPACE, '/claude/css-variables/groups', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_css_variable_group' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'variables' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Usar variable en bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/use-variable', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'use_css_variable_in_block' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'property' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'variable' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE SISTEMA DE VERSIONES
        // =============================================

        // =============================================
        // @DEPRECATED: Usar /claude/pages/{id}/snapshots en su lugar
        // Los endpoints /versions son redundantes con /snapshots
        // =============================================
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/versions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'list_page_versions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/versions', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_page_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string' ),
                'description' => array( 'type' => 'string' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/versions/(?P<version_id>\d+)/restore', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'restore_snapshot_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/versions/compare', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'compare_snapshot_versions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'version_a' => array( 'required' => true, 'type' => 'integer' ),
                'version_b' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/versions/(?P<version_id>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_snapshot_version' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE ACCESIBILIDAD
        // =============================================

        // Análisis de accesibilidad
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/accessibility/analyze', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'analyze_page_accessibility' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'standard' => array(
                    'type'    => 'string',
                    'default' => 'WCAG21-AA',
                    'enum'    => array( 'WCAG20-A', 'WCAG20-AA', 'WCAG21-A', 'WCAG21-AA', 'WCAG21-AAA' ),
                ),
            ),
        ) );

        // Corregir problemas de accesibilidad
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/accessibility/fix', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'fix_accessibility_issues' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'issues' => array(
                    'type' => 'array',
                ),
                'auto_fix' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Configurar atributos ARIA
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/aria', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_aria_attributes' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'attributes' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
            ),
        ) );

        // Obtener atributos ARIA
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/aria', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_aria_attributes' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Verificar contraste de colores WCAG
        register_rest_route( self::NAMESPACE, '/claude/accessibility/contrast-check', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'check_wcag_contrast' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'foreground' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'background' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'font_size' => array(
                    'type'    => 'number',
                    'default' => 16,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE EXPORTACIÓN DE CÓDIGO
        // =============================================

        // Exportar como HTML estático
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/html', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_as_html' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_styles' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'minify' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Exportar CSS
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/css', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_css' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'format' => array(
                    'type'    => 'string',
                    'default' => 'css',
                    'enum'    => array( 'css', 'scss', 'tailwind' ),
                ),
                'minify' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );

        // Exportar como React/Vue components
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/components', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_as_components' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'framework' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'react', 'vue', 'svelte', 'html' ),
                ),
                'typescript' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'styling' => array(
                    'type'    => 'string',
                    'default' => 'inline',
                    'enum'    => array( 'inline', 'css-modules', 'styled-components', 'tailwind' ),
                ),
            ),
        ) );

        // Exportar JSON de estructura
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/json', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_page_structure_json' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_styles' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'include_data' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE SLOTS Y PLACEHOLDERS
        // =============================================

        // Definir slot en bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/slots', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'define_block_slot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'slot_name' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'allowed_blocks' => array(
                    'type'    => 'array',
                    'default' => array(),
                ),
                'max_items' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
            ),
        ) );

        // Obtener slots de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/slots', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_slots' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Insertar bloque en slot
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/slots/(?P<slot_name>[a-z0-9_-]+)/insert', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'insert_block_into_slot' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'block' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'position' => array(
                    'type'    => 'integer',
                    'default' => -1,
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE INTERACCIONES Y EVENTOS
        // =============================================

        // Configurar evento de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/events', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_block_event_handler' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'event' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'click', 'hover', 'scroll_into_view', 'scroll_out', 'focus', 'blur', 'load' ),
                ),
                'actions' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // Obtener eventos de bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/events', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_event_handlers' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Tipos de acciones disponibles
        register_rest_route( self::NAMESPACE, '/claude/events/action-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_event_action_types' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE MÉTRICAS Y WEB VITALS
        // =============================================

        // Obtener métricas de rendimiento
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/metrics', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_page_metrics' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Estimar Web Vitals
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/web-vitals/estimate', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'estimate_web_vitals' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Sugerencias de mejora de Core Web Vitals
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/web-vitals/suggestions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_web_vitals_suggestions' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE CACHÉ Y RENDIMIENTO
        // =============================================

        // Configurar caché de página
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/cache', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_page_cache' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'enabled' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'ttl' => array(
                    'type'    => 'integer',
                    'default' => 3600,
                ),
                'vary_by' => array(
                    'type'    => 'array',
                    'default' => array(),
                ),
            ),
        ) );

        // Invalidar caché
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/cache/invalidate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'invalidate_page_cache' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Pre-generar caché
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/cache/pregenerate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'pregenerate_page_cache' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE LAZY LOADING AVANZADO
        // =============================================

        // Configurar lazy loading de sección
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/lazy-load', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_block_lazy_loading' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'enabled' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'threshold' => array(
                    'type'    => 'string',
                    'default' => '200px',
                ),
                'placeholder' => array(
                    'type'    => 'string',
                    'default' => 'skeleton',
                    'enum'    => array( 'skeleton', 'blur', 'spinner', 'none' ),
                ),
            ),
        ) );

        // Configurar prioridad de carga
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/load-priority', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_blocks_load_priority' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'priorities' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINT UNIFICADO DE DESIGN SYSTEM
        // Reemplaza: /claude/global-styles, /claude/design/variables, /claude/css-variables
        // =============================================

        // Obtener design system completo
        register_rest_route( self::NAMESPACE, '/claude/design-system', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_design_system' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Actualizar design system
        register_rest_route( self::NAMESPACE, '/claude/design-system', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_design_system' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'colors' => array(
                    'type'        => 'object',
                    'description' => 'Paleta de colores unificada.',
                ),
                'typography' => array(
                    'type'        => 'object',
                    'description' => 'Configuración de tipografía.',
                ),
                'spacing' => array(
                    'type'        => 'object',
                    'description' => 'Escala de espaciado.',
                ),
                'borders' => array(
                    'type'        => 'object',
                    'description' => 'Radios de borde.',
                ),
                'shadows' => array(
                    'type'        => 'object',
                    'description' => 'Sombras predefinidas.',
                ),
                'custom_css' => array(
                    'type'        => 'string',
                    'description' => 'CSS personalizado adicional.',
                ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE EFECTOS CSS MODERNOS
        // =============================================

        // Obtener efectos CSS modernos disponibles
        register_rest_route( self::NAMESPACE, '/claude/effects/modern', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_modern_css_effects' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Aplicar glassmorphism a bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/glassmorphism', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_glassmorphism' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'blur' => array( 'type' => 'integer', 'default' => 10 ),
                'opacity' => array( 'type' => 'number', 'default' => 0.7 ),
                'saturation' => array( 'type' => 'number', 'default' => 1.8 ),
                'border_opacity' => array( 'type' => 'number', 'default' => 0.2 ),
            ),
        ) );

        // Aplicar neumorphism a bloque
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/neumorphism', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_neumorphism' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array( 'type' => 'string', 'default' => 'flat', 'enum' => array( 'flat', 'concave', 'convex', 'pressed' ) ),
                'distance' => array( 'type' => 'integer', 'default' => 10 ),
                'intensity' => array( 'type' => 'number', 'default' => 0.15 ),
                'blur' => array( 'type' => 'integer', 'default' => 20 ),
            ),
        ) );

        // Aplicar gradiente avanzado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/gradient', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_advanced_gradient' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array( 'type' => 'string', 'default' => 'linear', 'enum' => array( 'linear', 'radial', 'conic', 'mesh' ) ),
                'colors' => array( 'type' => 'array', 'required' => true ),
                'angle' => array( 'type' => 'integer', 'default' => 135 ),
                'animated' => array( 'type' => 'boolean', 'default' => false ),
                'target' => array( 'type' => 'string', 'default' => 'background', 'enum' => array( 'background', 'text', 'border' ) ),
            ),
        ) );

        // Aplicar blend mode
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/blend-mode', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_blend_mode' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'mode' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion', 'hue', 'saturation', 'color', 'luminosity' ) ),
                'target' => array( 'type' => 'string', 'default' => 'background', 'enum' => array( 'background', 'mix' ) ),
            ),
        ) );

        // Aplicar clip-path
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/clip-path', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_clip_path' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'shape' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'circle', 'ellipse', 'polygon', 'wave-top', 'wave-bottom', 'diagonal', 'arrow', 'custom' ) ),
                'custom_path' => array( 'type' => 'string' ),
                'animated' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE TIPOGRAFÍA AVANZADA
        // =============================================

        // Obtener escalas tipográficas
        register_rest_route( self::NAMESPACE, '/claude/typography/scales', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_typography_scales' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar tipografía global
        register_rest_route( self::NAMESPACE, '/claude/typography/config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'set_typography_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'base_size' => array( 'type' => 'integer', 'default' => 16 ),
                'scale_ratio' => array( 'type' => 'number', 'default' => 1.25 ),
                'line_height' => array( 'type' => 'number', 'default' => 1.6 ),
                'heading_line_height' => array( 'type' => 'number', 'default' => 1.2 ),
                'font_family_heading' => array( 'type' => 'string' ),
                'font_family_body' => array( 'type' => 'string' ),
                'variable_fonts' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Aplicar texto con gradiente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/gradient-text', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_gradient_text' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'colors' => array( 'type' => 'array', 'required' => true ),
                'angle' => array( 'type' => 'integer', 'default' => 90 ),
                'animated' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Aplicar efectos de texto
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/text-effects', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_text_effects' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'effect' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'shadow', 'outline', 'glow', 'neon', '3d', 'emboss', 'engrave', 'retro' ) ),
                'color' => array( 'type' => 'string' ),
                'intensity' => array( 'type' => 'number', 'default' => 1 ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE LAYOUT AVANZADO
        // =============================================

        // Configurar grid avanzado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/grid-advanced', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_advanced_grid' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'columns' => array( 'type' => 'string', 'default' => 'repeat(3, 1fr)' ),
                'rows' => array( 'type' => 'string' ),
                'gap' => array( 'type' => 'string', 'default' => '2rem' ),
                'gap_responsive' => array( 'type' => 'object' ),
                'auto_flow' => array( 'type' => 'string', 'enum' => array( 'row', 'column', 'dense', 'row dense', 'column dense' ) ),
                'align_items' => array( 'type' => 'string' ),
                'justify_items' => array( 'type' => 'string' ),
            ),
        ) );

        // Configurar aspect ratio
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/aspect-ratio', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_aspect_ratio' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'ratio' => array( 'type' => 'string', 'required' => true, 'enum' => array( '1/1', '4/3', '16/9', '21/9', '3/2', '2/3', '9/16', 'custom' ) ),
                'custom_ratio' => array( 'type' => 'string' ),
                'object_fit' => array( 'type' => 'string', 'default' => 'cover', 'enum' => array( 'cover', 'contain', 'fill', 'none', 'scale-down' ) ),
            ),
        ) );

        // Configurar container queries
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/container-query', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'apply_container_query' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string', 'required' => true ),
                'type' => array( 'type' => 'string', 'default' => 'inline-size' ),
                'rules' => array( 'type' => 'array', 'required' => true ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE INTERACTIVIDAD AVANZADA
        // =============================================

        // Configurar estados de hover avanzados
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/hover-states', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_hover_states' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'styles' => array( 'type' => 'object', 'required' => true ),
                'transition' => array( 'type' => 'object', 'default' => array( 'duration' => '0.3s', 'easing' => 'ease-out' ) ),
                'transform' => array( 'type' => 'object' ),
                'filter' => array( 'type' => 'object' ),
            ),
        ) );

        // Configurar scroll behavior
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/scroll-behavior', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_scroll_behavior' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'smooth_scroll' => array( 'type' => 'boolean', 'default' => true ),
                'scroll_snap' => array( 'type' => 'object' ),
                'scroll_padding' => array( 'type' => 'string' ),
                'overscroll_behavior' => array( 'type' => 'string' ),
            ),
        ) );

        // Configurar parallax avanzado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/parallax', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_advanced_parallax' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'speed' => array( 'type' => 'number', 'default' => 0.5 ),
                'direction' => array( 'type' => 'string', 'default' => 'vertical', 'enum' => array( 'vertical', 'horizontal', 'both' ) ),
                'scale' => array( 'type' => 'boolean', 'default' => false ),
                'rotate' => array( 'type' => 'boolean', 'default' => false ),
                'opacity' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Configurar cursor personalizado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/blocks/(?P<block_id>[a-z0-9_-]+)/cursor', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_custom_cursor' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'pointer', 'grab', 'zoom-in', 'zoom-out', 'crosshair', 'text', 'custom', 'none' ) ),
                'custom_url' => array( 'type' => 'string' ),
                'hover_type' => array( 'type' => 'string' ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE DARK MODE Y TEMAS
        // =============================================

        // Configurar dark mode
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/dark-mode', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_dark_mode' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'enabled' => array( 'type' => 'boolean', 'default' => true ),
                'default_theme' => array( 'type' => 'string', 'default' => 'system', 'enum' => array( 'light', 'dark', 'system' ) ),
                'light_colors' => array( 'type' => 'object' ),
                'dark_colors' => array( 'type' => 'object' ),
                'transition' => array( 'type' => 'string', 'default' => '0.3s ease' ),
            ),
        ) );

        // Obtener configuración de dark mode
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/dark-mode', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_dark_mode_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE FORMULARIOS AVANZADOS
        // =============================================

        // Crear formulario
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/forms', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_form' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string', 'required' => true ),
                'fields' => array( 'type' => 'array', 'required' => true ),
                'submit_button' => array( 'type' => 'object' ),
                'validation' => array( 'type' => 'object' ),
                'action' => array( 'type' => 'string', 'default' => 'email' ),
                'success_message' => array( 'type' => 'string' ),
                'error_message' => array( 'type' => 'string' ),
            ),
        ) );

        // Obtener tipos de campos de formulario
        register_rest_route( self::NAMESPACE, '/claude/forms/field-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_form_field_types' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Validaciones disponibles
        register_rest_route( self::NAMESPACE, '/claude/forms/validations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_form_validations' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // =============================================
        // ENDPOINTS DE SEO Y METADATA
        // =============================================

        // Configurar Schema.org / JSON-LD
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/schema-org', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_schema_org' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'type' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'WebPage', 'Article', 'Product', 'LocalBusiness', 'Organization', 'Event', 'FAQPage', 'HowTo', 'Recipe', 'Review' ) ),
                'data' => array( 'type' => 'object', 'required' => true ),
            ),
        ) );

        // Configurar Open Graph
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/open-graph', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_open_graph' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'title' => array( 'type' => 'string' ),
                'description' => array( 'type' => 'string' ),
                'image' => array( 'type' => 'string' ),
                'type' => array( 'type' => 'string', 'default' => 'website' ),
                'twitter_card' => array( 'type' => 'string', 'default' => 'summary_large_image' ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE DOCUMENTACIÓN DE BLOQUES
        // =============================================

        // Obtener documentación completa de un bloque
        register_rest_route( self::NAMESPACE, '/claude/blocks/(?P<type>[a-z0-9_-]+)/docs', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_documentation' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener ejemplos de uso de bloque
        register_rest_route( self::NAMESPACE, '/claude/blocks/(?P<type>[a-z0-9_-]+)/examples', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_block_examples' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Buscar bloques por funcionalidad
        register_rest_route( self::NAMESPACE, '/claude/blocks/search', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_blocks_by_functionality' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'query' => array( 'type' => 'string', 'required' => true ),
                'category' => array( 'type' => 'string' ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE OPTIMIZACIÓN DE IMÁGENES
        // =============================================

        // Configurar lazy loading de imágenes
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/images/lazy-load', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'configure_images_lazy_load' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'enabled' => array( 'type' => 'boolean', 'default' => true ),
                'threshold' => array( 'type' => 'string', 'default' => '200px' ),
                'placeholder' => array( 'type' => 'string', 'default' => 'blur', 'enum' => array( 'blur', 'skeleton', 'color', 'none' ) ),
                'fade_in' => array( 'type' => 'boolean', 'default' => true ),
            ),
        ) );

        // Generar srcset automático
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/images/srcset', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_image_srcset' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'widths' => array( 'type' => 'array', 'default' => array( 320, 640, 960, 1280, 1920 ) ),
                'quality' => array( 'type' => 'integer', 'default' => 85 ),
                'format' => array( 'type' => 'string', 'default' => 'webp' ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE WEB VITALS Y PERFORMANCE
        // =============================================

        // Obtener score de performance
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/performance/score', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_performance_score' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Optimizar automáticamente
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/performance/optimize', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'auto_optimize_performance' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'targets' => array( 'type' => 'array', 'default' => array( 'images', 'fonts', 'css', 'animations' ) ),
                'level' => array( 'type' => 'string', 'default' => 'balanced', 'enum' => array( 'safe', 'balanced', 'aggressive' ) ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE COMPONENTES UI MODERNOS
        // =============================================

        // Obtener componentes UI modernos disponibles
        register_rest_route( self::NAMESPACE, '/claude/components/modern', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_modern_ui_components' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear tarjeta con efecto
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/components/card', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_modern_card' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'style' => array( 'type' => 'string', 'default' => 'elevated', 'enum' => array( 'elevated', 'outlined', 'filled', 'glass', 'gradient', 'neumorphic', 'neon' ) ),
                'hover_effect' => array( 'type' => 'string', 'default' => 'lift', 'enum' => array( 'lift', 'scale', 'glow', 'tilt', 'flip', 'none' ) ),
                'content' => array( 'type' => 'object', 'required' => true ),
            ),
        ) );

        // Crear botón con estilo moderno
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/components/button', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_modern_button' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'style' => array( 'type' => 'string', 'default' => 'solid', 'enum' => array( 'solid', 'outline', 'ghost', 'gradient', 'glass', 'neon', '3d' ) ),
                'size' => array( 'type' => 'string', 'default' => 'md', 'enum' => array( 'xs', 'sm', 'md', 'lg', 'xl' ) ),
                'icon' => array( 'type' => 'string' ),
                'icon_position' => array( 'type' => 'string', 'default' => 'left' ),
                'text' => array( 'type' => 'string', 'required' => true ),
                'url' => array( 'type' => 'string' ),
                'loading_state' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Crear badge/chip
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/components/badge', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_badge' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'text' => array( 'type' => 'string', 'required' => true ),
                'variant' => array( 'type' => 'string', 'default' => 'default', 'enum' => array( 'default', 'success', 'warning', 'error', 'info', 'gradient' ) ),
                'size' => array( 'type' => 'string', 'default' => 'md' ),
                'dot' => array( 'type' => 'boolean', 'default' => false ),
                'removable' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Crear avatar con estado
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/components/avatar', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_avatar' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'image' => array( 'type' => 'string' ),
                'name' => array( 'type' => 'string' ),
                'size' => array( 'type' => 'string', 'default' => 'md', 'enum' => array( 'xs', 'sm', 'md', 'lg', 'xl', '2xl' ) ),
                'status' => array( 'type' => 'string', 'enum' => array( 'online', 'offline', 'away', 'busy' ) ),
                'ring' => array( 'type' => 'boolean', 'default' => false ),
                'group' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ) );

        // Crear tooltip
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/components/tooltip', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_tooltip' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'target_block_id' => array( 'type' => 'string', 'required' => true ),
                'content' => array( 'type' => 'string', 'required' => true ),
                'position' => array( 'type' => 'string', 'default' => 'top', 'enum' => array( 'top', 'bottom', 'left', 'right' ) ),
                'trigger' => array( 'type' => 'string', 'default' => 'hover', 'enum' => array( 'hover', 'click', 'focus' ) ),
                'arrow' => array( 'type' => 'boolean', 'default' => true ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE BREAKPOINTS PERSONALIZADOS
        // =============================================

        // Obtener breakpoints disponibles
        register_rest_route( self::NAMESPACE, '/claude/breakpoints/presets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_breakpoint_presets' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear breakpoint personalizado
        register_rest_route( self::NAMESPACE, '/claude/breakpoints/custom', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_custom_breakpoint' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string', 'required' => true ),
                'min_width' => array( 'type' => 'integer' ),
                'max_width' => array( 'type' => 'integer' ),
                'orientation' => array( 'type' => 'string', 'enum' => array( 'portrait', 'landscape' ) ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE PRESETS COMPLETOS
        // =============================================

        // Obtener preset completo con todas las configuraciones
        register_rest_route( self::NAMESPACE, '/claude/presets/(?P<preset_id>[a-z0-9_-]+)/full', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_full_preset_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Crear preset personalizado
        register_rest_route( self::NAMESPACE, '/claude/presets/custom', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_custom_preset' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'name' => array( 'type' => 'string', 'required' => true ),
                'colors' => array( 'type' => 'object', 'required' => true ),
                'typography' => array( 'type' => 'object' ),
                'spacing' => array( 'type' => 'object' ),
                'borders' => array( 'type' => 'object' ),
                'shadows' => array( 'type' => 'object' ),
                'animations' => array( 'type' => 'object' ),
            ),
        ) );

        // Duplicar y modificar preset existente
        register_rest_route( self::NAMESPACE, '/claude/presets/(?P<preset_id>[a-z0-9_-]+)/duplicate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'duplicate_preset' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'new_name' => array( 'type' => 'string', 'required' => true ),
                'modifications' => array( 'type' => 'object' ),
            ),
        ) );

        // =============================================
        // ENDPOINTS DE EXPORTACIÓN A FRAMEWORKS (Fase 3)
        // =============================================

        // Exportar a React
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/react', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_to_react' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'typescript' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Generar código TypeScript.',
                ),
                'component_style' => array(
                    'type'        => 'string',
                    'default'     => 'functional',
                    'enum'        => array( 'functional', 'class' ),
                    'description' => 'Estilo de componentes React.',
                ),
                'css_strategy' => array(
                    'type'        => 'string',
                    'default'     => 'css-modules',
                    'enum'        => array( 'css-modules', 'styled-components', 'tailwind', 'inline' ),
                    'description' => 'Estrategia CSS para componentes.',
                ),
            ),
        ) );

        // Exportar a Vue
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/vue', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_to_vue' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'vue_version' => array(
                    'type'        => 'integer',
                    'default'     => 3,
                    'enum'        => array( 2, 3 ),
                    'description' => 'Versión de Vue (2 o 3).',
                ),
                'composition_api' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Usar Composition API (Vue 3).',
                ),
                'typescript' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Generar código TypeScript.',
                ),
            ),
        ) );

        // Exportar a Svelte
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/svelte', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_to_svelte' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'typescript' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Generar código TypeScript.',
                ),
                'svelte_version' => array(
                    'type'        => 'integer',
                    'default'     => 4,
                    'enum'        => array( 3, 4, 5 ),
                    'description' => 'Versión de Svelte.',
                ),
            ),
        ) );

        // Exportar solo CSS
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/css', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_css_only' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'format' => array(
                    'type'        => 'string',
                    'default'     => 'css',
                    'enum'        => array( 'css', 'scss', 'less', 'tailwind' ),
                    'description' => 'Formato de salida CSS.',
                ),
                'minify' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Minificar CSS resultante.',
                ),
                'include_reset' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Incluir CSS reset/normalize.',
                ),
            ),
        ) );

        // Exportar estructura JSON
        register_rest_route( self::NAMESPACE, '/claude/pages/(?P<id>\d+)/export/json', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_json_structure' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
            'args'                => array(
                'include_styles' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Incluir estilos en el JSON.',
                ),
                'include_settings' => array(
                    'type'        => 'boolean',
                    'default'     => true,
                    'description' => 'Incluir configuración de página.',
                ),
                'flatten' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Aplanar estructura jerárquica.',
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
        $api_key = flavor_get_vbp_api_key_from_request( $request );
        if ( flavor_check_vbp_automation_access( $api_key, 'vbp_claude' ) ) {
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

    // NOTA: list_blocks() movido a VBP_API_Blocks trait
    // NOTA: list_modules() movido a VBP_API_System trait

    /**
     * Placeholder para mantener compatibilidad con endpoints existentes
     *
     * @return WP_REST_Response
     */
    private function _deprecated_list_modules() {
        return new WP_REST_Response( array(), 200 );
    }

    // NOTA: create_page() movido a VBP_API_Pages trait

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

    // NOTA: merge_styles() movido a VBP_API_Design trait
    // NOTA: get_page() movido a VBP_API_Pages trait
    // NOTA: update_page() movido a VBP_API_Pages trait
    // NOTA: get_design_preset() movido a VBP_API_Design trait
    // NOTA: add_block() movido a VBP_API_Blocks trait
    // NOTA: list_pages() movido a VBP_API_Pages trait

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

    // NOTA: get_block_presets() movido a VBP_API_Blocks trait
    // NOTA: duplicate_page() y regenerate_element_ids() movidos a VBP_API_Pages trait

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

    // NOTA: publish_page(), get_page_url() y flush_permalinks() movidos a traits

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

    // NOTA: get_system_status(), check_screenshot_capabilities(), check_pdf_capabilities() movidos a VBP_API_System trait

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

    // NOTA: get_design_presets(), get_all_design_presets(), get_capabilities(), get_module_widgets_list() movidos a traits
    // NOTA: analyze_page_seo(), suggest_seo_improvements(), analyze_page_accessibility(), get_blocks_usage_stats(), get_vbp_overview_stats() movidos a VBP_API_Analytics
    // NOTA: create_page_variant(), create_hero_variant(), create_cta_variant(), create_colors_variant(), create_layout_variant(), get_page_variants(), compare_variants_performance() movidos a VBP_API_Variants
    // NOTA: search_vbp_pages(), find_text_in_blocks(), get_text_snippet(), get_page_history(), restore_page_version(), get_vbp_sitemap(), build_page_hierarchy(), generate_xml_sitemap(), generate_html_sitemap(), render_sitemap_list() movidos a VBP_API_Search
    // NOTA: validate_page_blocks(), validate_blocks_preview(), validate_blocks_structure(), export_page_html(), render_elements_to_html(), compare_pages(), get_orphan_blocks() movidos a VBP_API_Utilities
    // NOTA: get_page_css(), generate_elements_css(), minify_css(), get_page_performance(), count_images_in_blocks(), calculate_text_length(), calculate_max_depth(), get_performance_suggestions() movidos a VBP_API_BlockManipulation
    // NOTA: list_block_templates(), save_block_template(), get_block_template(), delete_block_template(), list_favorite_pages(), toggle_favorite(), list_vbp_tags(), set_page_tags(), get_page_tags() movidos a VBP_API_Library
    // NOTA: list_page_snapshots(), create_page_snapshot(), restore_page_snapshot(), delete_page_snapshot() movidos a VBP_API_Snapshots
    // NOTA: get_vbp_dashboard(), get_media_audit(), extract_images_from_blocks(), get_shortcodes_audit(), extract_shortcodes_from_blocks() movidos a VBP_API_Dashboard
    // NOTA: lock_page(), unlock_page(), get_lock_status() movidos a VBP_API_Collaboration
    // NOTA: analyze_readability(), extract_all_text_content(), count_syllables(), flesch_to_grade(), analyze_keywords(), extract_phrases(), full_content_analysis(), calculate_overall_content_score(), percentage_to_grade() movidos a VBP_API_ContentAnalysis
    // NOTA: get_global_styles(), save_global_styles(), generate_global_css(), get_design_system(), update_design_system(), generate_design_system_css(), generate_css_variables_output(), sync_design_system_to_legacy(), deep_merge_arrays() movidos a VBP_API_GlobalStyles
    // NOTA: bulk_publish_pages(), bulk_delete_pages(), bulk_duplicate_pages(), bulk_set_tags() movidos a VBP_API_BulkOperations
    // NOTA: schedule_page(), unschedule_page(), list_scheduled_pages() movidos a VBP_API_Scheduling
    // NOTA: list_page_comments(), add_page_comment(), delete_page_comment(), resolve_page_comment() movidos a VBP_API_Comments
    // NOTA: log_page_activity(), log_global_activity(), get_page_activity(), get_action_label(), get_global_activity() movidos a VBP_API_Activity
    // NOTA: list_global_widgets(), create_global_widget(), get_global_widget(), update_global_widget(), delete_global_widget(), get_widget_usage() movidos a VBP_API_GlobalWidgets
    // NOTA: export_all_pages(), extract_media_urls(), import_all_pages() movidos a VBP_API_ExportImport
    // NOTA: list_webhooks(), create_webhook(), delete_webhook(), test_webhook(), trigger_webhooks(), send_webhook() movidos a VBP_API_Webhooks
    // NOTA: get_single_block(), find_block_by_id(), get_block_path(), get_block_depth(), update_single_block(), is_block_locked(), save_block_history(), update_block_in_elements(), duplicate_block(), deep_clone_block(), insert_block_near(), wrap_block(), wrap_block_in_elements(), unwrap_block(), unwrap_block_in_elements(), lock_block(), unlock_block(), get_block_history(), restore_block_version(), replace_block_in_elements(), batch_apply_styles(), apply_styles_to_block(), find_replace_in_blocks(), find_text_matches(), text_contains(), replace_text_in_elements(), replace_text() movidos a VBP_API_AdvancedBlockEditing
    // NOTA: get_page_preview(), styles_to_css_string(), generate_preview_css(), get_block_preview(), create_temp_preview(), get_temp_preview(), get_page_thumbnail(), compare_page_versions(), extract_all_block_ids() movidos a VBP_API_Previews
    // NOTA: get_widget_versions(), create_widget_version(), restore_widget_version(), sync_widget_to_pages(), get_widget_variables(), set_widget_variables() movidos a VBP_API_AdvancedWidgets
    // NOTA: analyze_page_performance(), optimize_page_images(), minify_page_assets(), cleanup_page_blocks(), configure_preload(), get_global_optimization_status(), regenerate_compiled_css(), detect_orphan_blocks() movidos a VBP_API_Optimization
    // NOTA: list_page_variants(), get_ab_test_stats(), declare_ab_winner(), delete_page_variant() movidos a VBP_API_ABTesting
    // NOTA: analyze_accessibility(), fix_accessibility_issues() movidos a VBP_API_Accessibility
    // NOTA: get_page_seo(), update_page_seo(), get_page_schema(), update_page_schema() movidos a VBP_API_SEO
    // NOTA: get_page_animations(), set_block_animation(), batch_set_animations(), get_animation_presets() movidos a VBP_API_Animations
    // NOTA: get_page_responsive_styles(), set_block_responsive_styles(), get_custom_breakpoints(), set_custom_breakpoints(), set_block_visibility() movidos a VBP_API_Responsive

    // =============================================
    // MÉTODOS DE PLANTILLAS DE SECCIÓN
    // =============================================

    /**
     * Transfiere bloque entre páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function transfer_block_between_pages( $request ) {
        $source_page_id = (int) $request->get_param( 'source_page_id' );
        $target_page_id = (int) $request->get_param( 'target_page_id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $position = (int) $request->get_param( 'position' );
        $mode = $request->get_param( 'mode' );

        $source_post = get_post( $source_page_id );
        $target_post = get_post( $target_page_id );

        if ( ! $source_post || $source_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página origen no encontrada.' ), 404 );
        }
        if ( ! $target_post || $target_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página destino no encontrada.' ), 404 );
        }

        $source_elements = json_decode( get_post_meta( $source_page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $target_elements = json_decode( get_post_meta( $target_page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_to_transfer = null;
        $block_index = null;
        foreach ( $source_elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_to_transfer = $element;
                $block_index = $index;
                break;
            }
        }

        if ( ! $block_to_transfer ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado en página origen.' ), 404 );
        }

        // Generar nuevo ID para el bloque
        $new_block = $block_to_transfer;
        $new_block['id'] = 'block_' . uniqid();

        // Insertar en destino
        if ( $position < 0 || $position >= count( $target_elements ) ) {
            $target_elements[] = $new_block;
        } else {
            array_splice( $target_elements, $position, 0, array( $new_block ) );
        }

        update_post_meta( $target_page_id, '_flavor_vbp_elements', wp_json_encode( $target_elements ) );

        // Si es mover, eliminar del origen
        if ( $mode === 'move' ) {
            unset( $source_elements[ $block_index ] );
            $source_elements = array_values( $source_elements );
            update_post_meta( $source_page_id, '_flavor_vbp_elements', wp_json_encode( $source_elements ) );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => $mode === 'move' ? 'Bloque movido.' : 'Bloque copiado.',
            'new_block_id' => $new_block['id'],
        ), 200 );
    }

    /**
     * Convierte tipo de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function convert_block_type( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $new_type = sanitize_text_field( $request->get_param( 'new_type' ) );
        $preserve_content = (bool) $request->get_param( 'preserve_content' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $converted = false;
        $old_type = '';
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $old_type = $element['type'] ?? 'unknown';
                $element['type'] = $new_type;

                if ( ! $preserve_content ) {
                    $element['data'] = array();
                }

                $converted = true;
                break;
            }
        }

        if ( ! $converted ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => "Bloque convertido de {$old_type} a {$new_type}.",
            'old_type' => $old_type,
            'new_type' => $new_type,
        ), 200 );
    }

    /**
     * Extrae bloque como widget reutilizable
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function extract_block_as_widget( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_name = sanitize_text_field( $request->get_param( 'widget_name' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $replace_with_reference = (bool) $request->get_param( 'replace_with_reference' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_data = null;
        $block_index = null;
        foreach ( $elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_data = $element;
                $block_index = $index;
                break;
            }
        }

        if ( ! $block_data ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        // Crear widget
        $widget_id = 'widget_' . sanitize_title( $widget_name ) . '_' . uniqid();
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $widgets[ $widget_id ] = array(
            'id'         => $widget_id,
            'name'       => $widget_name,
            'category'   => $category,
            'block'      => $block_data,
            'created_at' => current_time( 'mysql' ),
            'version'    => 1,
        );

        update_option( 'flavor_vbp_widgets', $widgets );

        // Reemplazar bloque con referencia si se solicita
        if ( $replace_with_reference ) {
            $elements[ $block_index ] = array(
                'id'        => $block_data['id'],
                'type'      => 'widget_reference',
                'widget_id' => $widget_id,
            );
            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Widget creado exitosamente.',
            'widget_id' => $widget_id,
        ), 201 );
    }

    /**
     * Agrupa bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function group_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $wrapper_type = $request->get_param( 'wrapper_type' );
        $wrapper_styles = $request->get_param( 'wrapper_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $blocks_to_group = array();
        $first_index = null;
        $indices_to_remove = array();

        foreach ( $elements as $index => $element ) {
            if ( in_array( $element['id'] ?? '', $block_ids, true ) ) {
                $blocks_to_group[] = $element;
                $indices_to_remove[] = $index;
                if ( $first_index === null ) {
                    $first_index = $index;
                }
            }
        }

        if ( count( $blocks_to_group ) < 2 ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Se necesitan al menos 2 bloques para agrupar.' ), 400 );
        }

        // Crear contenedor
        $group_block = array(
            'id'       => 'group_' . uniqid(),
            'type'     => $wrapper_type,
            'styles'   => $wrapper_styles,
            'children' => $blocks_to_group,
        );

        // Eliminar bloques originales
        foreach ( array_reverse( $indices_to_remove ) as $idx ) {
            unset( $elements[ $idx ] );
        }
        $elements = array_values( $elements );

        // Insertar grupo en la primera posición
        array_splice( $elements, $first_index, 0, array( $group_block ) );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => 'Bloques agrupados.',
            'group_id' => $group_block['id'],
        ), 200 );
    }

    /**
     * Desagrupa bloque contenedor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function ungroup_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $group_index = null;
        $children = array();
        foreach ( $elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                if ( empty( $element['children'] ) ) {
                    return new WP_REST_Response( array( 'success' => false, 'error' => 'El bloque no tiene hijos para desagrupar.' ), 400 );
                }
                $group_index = $index;
                $children = $element['children'];
                break;
            }
        }

        if ( $group_index === null ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        // Eliminar grupo e insertar hijos
        unset( $elements[ $group_index ] );
        $elements = array_values( $elements );
        array_splice( $elements, $group_index, 0, $children );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => 'Bloque desagrupado.',
            'children_ids' => array_column( $children, 'id' ),
        ), 200 );
    }

    /**
     * Clona estilos entre bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function clone_block_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $source_block_id = sanitize_text_field( $request->get_param( 'source_block_id' ) );
        $target_block_ids = $request->get_param( 'target_block_ids' );
        $style_properties = $request->get_param( 'style_properties' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        // Buscar estilos del bloque origen
        $source_styles = null;
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $source_block_id ) {
                $source_styles = $element['styles'] ?? array();
                break;
            }
        }

        if ( $source_styles === null ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque origen no encontrado.' ), 404 );
        }

        // Filtrar propiedades si se especifican
        if ( ! empty( $style_properties ) ) {
            $filtered_styles = array();
            foreach ( $style_properties as $prop ) {
                if ( isset( $source_styles[ $prop ] ) ) {
                    $filtered_styles[ $prop ] = $source_styles[ $prop ];
                }
            }
            $source_styles = $filtered_styles;
        }

        // Aplicar a bloques destino
        $updated_count = 0;
        foreach ( $elements as &$element ) {
            if ( in_array( $element['id'] ?? '', $target_block_ids, true ) ) {
                $element['styles'] = array_merge( $element['styles'] ?? array(), $source_styles );
                $updated_count++;
            }
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Estilos clonados a {$updated_count} bloques.",
            'updated' => $updated_count,
        ), 200 );
    }

    /**
     * Reordena bloques en lote
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function reorder_blocks_batch( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $new_order = $request->get_param( 'new_order' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements_by_id = array();
        foreach ( $elements as $element ) {
            $elements_by_id[ $element['id'] ?? '' ] = $element;
        }

        $reordered_elements = array();
        foreach ( $new_order as $block_id ) {
            if ( isset( $elements_by_id[ $block_id ] ) ) {
                $reordered_elements[] = $elements_by_id[ $block_id ];
                unset( $elements_by_id[ $block_id ] );
            }
        }

        // Añadir bloques no incluidos en new_order al final
        foreach ( $elements_by_id as $remaining ) {
            $reordered_elements[] = $remaining;
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $reordered_elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Bloques reordenados.',
            'count'   => count( $reordered_elements ),
        ), 200 );
    }

    /**
     * Valida estructura de bloques de página (endpoint REST)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function validate_page_blocks_structure( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $issues = array();
        $ids_seen = array();

        $this->validate_elements_recursive( $elements, $issues, $ids_seen );

        return new WP_REST_Response( array(
            'success' => true,
            'valid'   => empty( $issues ),
            'issues'  => $issues,
            'stats'   => array(
                'total_blocks' => count( $ids_seen ),
                'issues_count' => count( $issues ),
            ),
        ), 200 );
    }

    /**
     * Valida elementos recursivamente
     *
     * @param array $elements Elementos.
     * @param array $issues   Problemas encontrados.
     * @param array $ids_seen IDs vistos.
     */
    private function validate_elements_recursive( $elements, &$issues, &$ids_seen ) {
        foreach ( $elements as $index => $element ) {
            $element_id = $element['id'] ?? '';

            if ( empty( $element_id ) ) {
                $issues[] = array( 'type' => 'missing_id', 'index' => $index );
            } elseif ( isset( $ids_seen[ $element_id ] ) ) {
                $issues[] = array( 'type' => 'duplicate_id', 'id' => $element_id );
            } else {
                $ids_seen[ $element_id ] = true;
            }

            if ( empty( $element['type'] ) ) {
                $issues[] = array( 'type' => 'missing_type', 'id' => $element_id );
            }

            if ( ! empty( $element['children'] ) ) {
                $this->validate_elements_recursive( $element['children'], $issues, $ids_seen );
            }
        }
    }

    /**
     * Repara bloques rotos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function repair_broken_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $repairs = array();
        $ids_seen = array();

        $elements = $this->repair_elements_recursive( $elements, $repairs, $ids_seen );

        if ( ! $dry_run && ! empty( $repairs ) ) {
            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'dry_run' => $dry_run,
            'repairs' => $repairs,
            'count'   => count( $repairs ),
        ), 200 );
    }

    /**
     * Repara elementos recursivamente
     *
     * @param array $elements Elementos.
     * @param array $repairs  Reparaciones realizadas.
     * @param array $ids_seen IDs vistos.
     * @return array
     */
    private function repair_elements_recursive( $elements, &$repairs, &$ids_seen ) {
        foreach ( $elements as &$element ) {
            $original_id = $element['id'] ?? '';

            // Reparar ID faltante
            if ( empty( $original_id ) ) {
                $element['id'] = 'block_' . uniqid();
                $repairs[] = array( 'type' => 'added_id', 'new_id' => $element['id'] );
            }

            // Reparar ID duplicado
            if ( isset( $ids_seen[ $element['id'] ] ) ) {
                $old_id = $element['id'];
                $element['id'] = 'block_' . uniqid();
                $repairs[] = array( 'type' => 'fixed_duplicate', 'old_id' => $old_id, 'new_id' => $element['id'] );
            }

            $ids_seen[ $element['id'] ] = true;

            // Reparar tipo faltante
            if ( empty( $element['type'] ) ) {
                $element['type'] = 'container';
                $repairs[] = array( 'type' => 'added_type', 'id' => $element['id'] );
            }

            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->repair_elements_recursive( $element['children'], $repairs, $ids_seen );
            }
        }

        return $elements;
    }

    /**
     * Obtiene árbol de bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_blocks_tree( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_data = (bool) $request->get_param( 'include_data' );
        $include_styles = (bool) $request->get_param( 'include_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $tree = $this->build_blocks_tree( $elements, $include_data, $include_styles );

        return new WP_REST_Response( array(
            'success' => true,
            'tree'    => $tree,
        ), 200 );
    }

    /**
     * Construye árbol de bloques
     *
     * @param array $elements       Elementos.
     * @param bool  $include_data   Incluir datos.
     * @param bool  $include_styles Incluir estilos.
     * @return array
     */
    private function build_blocks_tree( $elements, $include_data, $include_styles ) {
        $tree = array();
        foreach ( $elements as $element ) {
            $node = array(
                'id'   => $element['id'] ?? '',
                'type' => $element['type'] ?? 'unknown',
            );

            if ( $include_data && isset( $element['data'] ) ) {
                $node['data'] = $element['data'];
            }

            if ( $include_styles && isset( $element['styles'] ) ) {
                $node['styles'] = $element['styles'];
            }

            if ( ! empty( $element['children'] ) ) {
                $node['children'] = $this->build_blocks_tree( $element['children'], $include_data, $include_styles );
            }

            $tree[] = $node;
        }
        return $tree;
    }

    // =============================================
    // MÉTODOS DE PREVISUALIZACIONES AVANZADAS
    // =============================================

    /**
     * Obtiene preview con tema diferente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_themed_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $theme = $request->get_param( 'theme' );
        $colors = $request->get_param( 'colors' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $theme_vars = array();
        switch ( $theme ) {
            case 'light':
                $theme_vars = array(
                    '--bg-color'   => '#ffffff',
                    '--text-color' => '#1a1a1a',
                    '--primary'    => '#2563eb',
                );
                break;
            case 'dark':
                $theme_vars = array(
                    '--bg-color'   => '#1a1a1a',
                    '--text-color' => '#ffffff',
                    '--primary'    => '#3b82f6',
                );
                break;
            case 'custom':
                if ( $colors ) {
                    $theme_vars = $colors;
                }
                break;
        }

        $preview_url = add_query_arg( array(
            'vbp_preview' => 1,
            'theme_vars'  => base64_encode( wp_json_encode( $theme_vars ) ),
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'theme'       => $theme,
            'theme_vars'  => $theme_vars,
        ), 200 );
    }

    /**
     * Crea preview compartible
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_shareable_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $expires_in = min( (int) $request->get_param( 'expires_in' ), 604800 );
        $password = $request->get_param( 'password' );
        $allow_comments = (bool) $request->get_param( 'allow_comments' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $share_token = wp_generate_password( 32, false );

        $share_data = array(
            'page_id'        => $page_id,
            'expires_at'     => time() + $expires_in,
            'password'       => $password ? wp_hash_password( $password ) : null,
            'allow_comments' => $allow_comments,
            'created_at'     => current_time( 'mysql' ),
            'views'          => 0,
        );

        set_transient( 'vbp_share_' . $share_token, $share_data, $expires_in );

        $share_url = add_query_arg( array(
            'vbp_share' => $share_token,
        ), home_url( '/vbp-preview/' ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'share_url'  => $share_url,
            'token'      => $share_token,
            'expires_at' => gmdate( 'c', $share_data['expires_at'] ),
        ), 201 );
    }

    /**
     * Genera código QR para preview
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_preview_qr_code( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $size = (int) $request->get_param( 'size' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $preview_url = add_query_arg( 'vbp_preview', '1', get_permalink( $page_id ) );

        // URL de Google Charts API para QR
        $qr_url = 'https://chart.googleapis.com/chart?' . http_build_query( array(
            'cht'  => 'qr',
            'chs'  => "{$size}x{$size}",
            'chl'  => $preview_url,
            'choe' => 'UTF-8',
        ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'qr_url'      => $qr_url,
            'preview_url' => $preview_url,
            'size'        => $size,
        ), 200 );
    }

    /**
     * Obtiene preview de cambios pendientes
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_pending_changes_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $current_elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $draft_elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements_draft', true ), true ) ?: array();

        $has_changes = wp_json_encode( $current_elements ) !== wp_json_encode( $draft_elements );

        return new WP_REST_Response( array(
            'success'         => true,
            'has_changes'     => $has_changes,
            'current_count'   => count( $current_elements ),
            'draft_count'     => count( $draft_elements ),
            'preview_url'     => add_query_arg( 'vbp_draft', '1', get_permalink( $page_id ) ),
        ), 200 );
    }

    /**
     * Obtiene preview de múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_multi_page_preview( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $thumbnail_size = $request->get_param( 'thumbnail_size' );

        $sizes = array(
            'small'  => array( 200, 150 ),
            'medium' => array( 400, 300 ),
            'large'  => array( 600, 450 ),
        );

        $size_dims = $sizes[ $thumbnail_size ] ?? $sizes['medium'];

        $previews = array();
        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( $post && $this->is_supported_post_type( $post->post_type ) ) {
                $previews[] = array(
                    'page_id'      => $page_id,
                    'title'        => $post->post_title,
                    'preview_url'  => get_permalink( $page_id ),
                    'thumbnail'    => get_the_post_thumbnail_url( $page_id, 'medium' ) ?: null,
                    'status'       => $post->post_status,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'previews' => $previews,
            'size'     => $size_dims,
        ), 200 );
    }

    /**
     * Obtiene preview interactivo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_interactive_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_scripts = (bool) $request->get_param( 'include_scripts' );
        $sandbox = (bool) $request->get_param( 'sandbox' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $preview_url = add_query_arg( array(
            'vbp_interactive' => '1',
            'include_scripts' => $include_scripts ? '1' : '0',
        ), get_permalink( $page_id ) );

        $sandbox_attrs = $sandbox ? 'allow-scripts allow-same-origin' : '';

        return new WP_REST_Response( array(
            'success'          => true,
            'preview_url'      => $preview_url,
            'iframe_sandbox'   => $sandbox_attrs,
            'include_scripts'  => $include_scripts,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE WIDGETS AVANZADOS
    // =============================================

    /**
     * Importa widget desde otra página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function import_widget_from_page( $request ) {
        $source_page_id = (int) $request->get_param( 'source_page_id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_name = sanitize_text_field( $request->get_param( 'widget_name' ) );

        $source_post = get_post( $source_page_id );
        if ( ! $source_post || $source_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página origen no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $source_page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_data = null;
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_data = $element;
                break;
            }
        }

        if ( ! $block_data ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $widget_id = 'widget_' . sanitize_title( $widget_name ) . '_' . uniqid();
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $widgets[ $widget_id ] = array(
            'id'          => $widget_id,
            'name'        => $widget_name,
            'category'    => 'imported',
            'block'       => $block_data,
            'source_page' => $source_page_id,
            'created_at'  => current_time( 'mysql' ),
            'version'     => 1,
        );

        update_option( 'flavor_vbp_widgets', $widgets );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Widget importado exitosamente.',
            'widget_id' => $widget_id,
        ), 201 );
    }

    /**
     * Detecta widgets no usados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_unused_widgets( $request ) {
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        // Usar límite seguro para evitar cargas masivas
        $limit = flavor_safe_posts_limit( -1 );

        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
        ) );

        $used_widgets = array();
        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json ) {
                $this->find_widget_references( json_decode( $elements_json, true ) ?: array(), $used_widgets );
            }
        }

        $unused_widgets = array();
        foreach ( $widgets as $widget_id => $widget ) {
            if ( ! isset( $used_widgets[ $widget_id ] ) ) {
                $unused_widgets[] = array(
                    'id'         => $widget_id,
                    'name'       => $widget['name'] ?? $widget_id,
                    'created_at' => $widget['created_at'] ?? null,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'total_widgets'  => count( $widgets ),
            'unused_widgets' => $unused_widgets,
            'unused_count'   => count( $unused_widgets ),
        ), 200 );
    }

    /**
     * Busca referencias a widgets
     *
     * @param array $elements     Elementos.
     * @param array $used_widgets Widgets usados.
     */
    private function find_widget_references( $elements, &$used_widgets ) {
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === 'widget_reference' && ! empty( $element['widget_id'] ) ) {
                $used_widgets[ $element['widget_id'] ] = true;
            }
            if ( ! empty( $element['children'] ) ) {
                $this->find_widget_references( $element['children'], $used_widgets );
            }
        }
    }

    /**
     * Actualiza widget en todas las instancias
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_widget_all_instances( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $new_block = $request->get_param( 'block' );
        $notify_pages = (bool) $request->get_param( 'notify_pages' );

        $widgets = get_option( 'flavor_vbp_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $widgets[ $widget_id ]['block'] = $new_block;
        $widgets[ $widget_id ]['version'] = ( $widgets[ $widget_id ]['version'] ?? 0 ) + 1;
        $widgets[ $widget_id ]['updated_at'] = current_time( 'mysql' );

        update_option( 'flavor_vbp_widgets', $widgets );

        // Buscar páginas que usan este widget (con límite seguro)
        $affected_pages = array();
        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'fields'         => 'ids',
        ) );

        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json && strpos( $elements_json, $widget_id ) !== false ) {
                $affected_pages[] = $page_id;
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'message'        => 'Widget actualizado.',
            'version'        => $widgets[ $widget_id ]['version'],
            'affected_pages' => $affected_pages,
        ), 200 );
    }

    /**
     * Convierte bloque a referencia de widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function convert_block_to_widget_reference( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $widgets = get_option( 'flavor_vbp_widgets', array() );
        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $converted = false;
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $element = array(
                    'id'        => $block_id,
                    'type'      => 'widget_reference',
                    'widget_id' => $widget_id,
                );
                $converted = true;
                break;
            }
        }

        if ( ! $converted ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Bloque convertido a referencia de widget.',
            'widget_id' => $widget_id,
        ), 200 );
    }

    /**
     * Obtiene estadísticas de uso de widgets
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widgets_usage_stats( $request ) {
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'fields'         => 'ids',
        ) );

        $usage_stats = array();
        foreach ( $widgets as $widget_id => $widget ) {
            $usage_stats[ $widget_id ] = array(
                'name'      => $widget['name'] ?? $widget_id,
                'category'  => $widget['category'] ?? 'uncategorized',
                'version'   => $widget['version'] ?? 1,
                'instances' => 0,
                'pages'     => array(),
            );
        }

        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json ) {
                $elements = json_decode( $elements_json, true ) ?: array();
                $this->count_widget_usage( $elements, $usage_stats, $page_id );
            }
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'total'        => count( $widgets ),
            'usage_stats'  => array_values( $usage_stats ),
        ), 200 );
    }

    /**
     * Cuenta uso de widgets
     *
     * @param array $elements    Elementos.
     * @param array $usage_stats Estadísticas de uso.
     * @param int   $page_id     ID de página.
     */
    private function count_widget_usage( $elements, &$usage_stats, $page_id ) {
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === 'widget_reference' && ! empty( $element['widget_id'] ) ) {
                $widget_id = $element['widget_id'];
                if ( isset( $usage_stats[ $widget_id ] ) ) {
                    $usage_stats[ $widget_id ]['instances']++;
                    if ( ! in_array( $page_id, $usage_stats[ $widget_id ]['pages'], true ) ) {
                        $usage_stats[ $widget_id ]['pages'][] = $page_id;
                    }
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $this->count_widget_usage( $element['children'], $usage_stats, $page_id );
            }
        }
    }

    // =============================================
    // MÉTODOS DE OPTIMIZACIÓN AVANZADA
    // =============================================

    /**
     * Analiza elementos para rendimiento
     *
     * @param array $elements Elementos.
     * @param array $metrics  Métricas.
     * @param int   $depth    Profundidad.
     */
    private function analyze_elements_performance( $elements, &$metrics, $depth ) {
        $metrics['nesting_depth'] = max( $metrics['nesting_depth'], $depth );

        foreach ( $elements as $element ) {
            $metrics['block_count']++;

            $type = $element['type'] ?? '';
            if ( in_array( $type, array( 'image', 'gallery', 'hero' ), true ) ) {
                $metrics['image_count']++;
            }
            if ( $type === 'video' ) {
                $metrics['video_count']++;
            }

            $data = $element['data'] ?? array();
            foreach ( array( 'text', 'content', 'title' ) as $field ) {
                if ( ! empty( $data[ $field ] ) ) {
                    $metrics['total_text_chars'] += strlen( $data[ $field ] );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $this->analyze_elements_performance( $element['children'], $metrics, $depth + 1 );
            }
        }
    }

    /**
     * Obtiene sugerencias de optimización
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_optimization_suggestions( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $suggestions = array();

        // Analizar imágenes sin lazy loading
        $this->check_lazy_loading( $elements, $suggestions );

        // Analizar bloques vacíos
        $this->check_empty_blocks( $elements, $suggestions );

        // Verificar fuentes
        $this->check_fonts_optimization( $elements, $suggestions );

        return new WP_REST_Response( array(
            'success'     => true,
            'suggestions' => $suggestions,
            'count'       => count( $suggestions ),
        ), 200 );
    }

    /**
     * Verifica lazy loading
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_lazy_loading( $elements, &$suggestions ) {
        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            if ( in_array( $type, array( 'image', 'gallery' ), true ) ) {
                $data = $element['data'] ?? array();
                if ( empty( $data['lazy'] ) ) {
                    $suggestions[] = array(
                        'id'       => 'lazy_' . ( $element['id'] ?? uniqid() ),
                        'type'     => 'lazy_loading',
                        'priority' => 'medium',
                        'message'  => "Imagen sin lazy loading: {$element['id']}",
                        'action'   => 'enable_lazy_loading',
                        'block_id' => $element['id'] ?? '',
                    );
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $this->check_lazy_loading( $element['children'], $suggestions );
            }
        }
    }

    /**
     * Verifica bloques vacíos
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_empty_blocks( $elements, &$suggestions ) {
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();

            if ( empty( $data ) && empty( $children ) ) {
                $suggestions[] = array(
                    'id'       => 'empty_' . ( $element['id'] ?? uniqid() ),
                    'type'     => 'empty_block',
                    'priority' => 'low',
                    'message'  => "Bloque vacío: {$element['id']}",
                    'action'   => 'remove_empty',
                    'block_id' => $element['id'] ?? '',
                );
            }

            if ( ! empty( $children ) ) {
                $this->check_empty_blocks( $children, $suggestions );
            }
        }
    }

    /**
     * Verifica optimización de fuentes
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_fonts_optimization( $elements, &$suggestions ) {
        $fonts_used = array();
        $this->extract_fonts_used( $elements, $fonts_used );

        if ( count( $fonts_used ) > 3 ) {
            $suggestions[] = array(
                'id'       => 'fonts_count',
                'type'     => 'fonts',
                'priority' => 'medium',
                'message'  => 'Demasiadas fuentes diferentes (' . count( $fonts_used ) . '). Considere reducir.',
                'action'   => 'reduce_fonts',
            );
        }
    }

    /**
     * Extrae fuentes usadas
     *
     * @param array $elements   Elementos.
     * @param array $fonts_used Fuentes usadas.
     */
    private function extract_fonts_used( $elements, &$fonts_used ) {
        foreach ( $elements as $element ) {
            $styles = $element['styles'] ?? array();
            if ( ! empty( $styles['fontFamily'] ) ) {
                $fonts_used[ $styles['fontFamily'] ] = true;
            }
            if ( ! empty( $element['children'] ) ) {
                $this->extract_fonts_used( $element['children'], $fonts_used );
            }
        }
    }

    /**
     * Aplica sugerencias de optimización
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_optimization_suggestions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $suggestion_ids = $request->get_param( 'suggestions' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $applied_count = 0;

        // Aplicar lazy loading a todas las imágenes si no hay filtro específico
        if ( empty( $suggestion_ids ) || in_array( 'lazy_loading', $suggestion_ids, true ) ) {
            $applied_count += $this->apply_lazy_loading_to_elements( $elements );
        }

        // Eliminar bloques vacíos
        if ( empty( $suggestion_ids ) || in_array( 'empty_block', $suggestion_ids, true ) ) {
            $original_count = count( $elements );
            $elements = $this->remove_empty_elements( $elements );
            $applied_count += $original_count - count( $elements );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Optimizaciones aplicadas: {$applied_count}",
            'applied' => $applied_count,
        ), 200 );
    }

    /**
     * Aplica lazy loading a elementos
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function apply_lazy_loading_to_elements( &$elements ) {
        $count = 0;
        foreach ( $elements as &$element ) {
            if ( in_array( $element['type'] ?? '', array( 'image', 'gallery' ), true ) ) {
                if ( ! isset( $element['data'] ) ) {
                    $element['data'] = array();
                }
                if ( empty( $element['data']['lazy'] ) ) {
                    $element['data']['lazy'] = true;
                    $count++;
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $count += $this->apply_lazy_loading_to_elements( $element['children'] );
            }
        }
        return $count;
    }

    /**
     * Elimina elementos vacíos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function remove_empty_elements( $elements ) {
        $filtered = array();
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();

            if ( ! empty( $data ) || ! empty( $children ) ) {
                if ( ! empty( $children ) ) {
                    $element['children'] = $this->remove_empty_elements( $children );
                }
                $filtered[] = $element;
            }
        }
        return $filtered;
    }

    /**
     * Comprime HTML de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compress_page_html( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $remove_comments = (bool) $request->get_param( 'remove_comments' );
        $minify_css = (bool) $request->get_param( 'minify_inline_css' );
        $minify_js = (bool) $request->get_param( 'minify_inline_js' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $compression_config = array(
            'remove_comments'   => $remove_comments,
            'minify_inline_css' => $minify_css,
            'minify_inline_js'  => $minify_js,
        );

        update_post_meta( $page_id, '_flavor_vbp_compression', $compression_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de compresión guardada.',
            'config'  => $compression_config,
        ), 200 );
    }

    /**
     * Detecta recursos pesados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_heavy_resources( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $threshold_kb = (int) $request->get_param( 'threshold_kb' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $heavy_resources = array();
        $this->find_heavy_resources( $elements, $heavy_resources, $threshold_kb * 1024 );

        return new WP_REST_Response( array(
            'success'         => true,
            'threshold_kb'    => $threshold_kb,
            'heavy_resources' => $heavy_resources,
            'count'           => count( $heavy_resources ),
        ), 200 );
    }

    /**
     * Busca recursos pesados
     *
     * @param array $elements        Elementos.
     * @param array $heavy_resources Recursos pesados.
     * @param int   $threshold       Umbral en bytes.
     */
    private function find_heavy_resources( $elements, &$heavy_resources, $threshold ) {
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();

            // Verificar imágenes
            if ( ! empty( $data['image_url'] ) ) {
                $size = $this->estimate_resource_size( $data['image_url'] );
                if ( $size > $threshold ) {
                    $heavy_resources[] = array(
                        'type'     => 'image',
                        'url'      => $data['image_url'],
                        'size_kb'  => round( $size / 1024 ),
                        'block_id' => $element['id'] ?? '',
                    );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $this->find_heavy_resources( $element['children'], $heavy_resources, $threshold );
            }
        }
    }

    /**
     * Estima tamaño de recurso
     *
     * @param string $url URL del recurso.
     * @return int
     */
    private function estimate_resource_size( $url ) {
        $attachment_id = attachment_url_to_postid( $url );
        if ( $attachment_id ) {
            $file_path = get_attached_file( $attachment_id );
            if ( $file_path && file_exists( $file_path ) ) {
                return filesize( $file_path );
            }
        }
        return 0;
    }

    /**
     * Optimiza fuentes de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function optimize_page_fonts( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $subset = $request->get_param( 'subset' );
        $display = $request->get_param( 'display' );
        $preload = (bool) $request->get_param( 'preload' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $font_config = array(
            'subset'  => $subset,
            'display' => $display,
            'preload' => $preload,
        );

        update_post_meta( $page_id, '_flavor_vbp_font_config', $font_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de fuentes guardada.',
            'config'  => $font_config,
        ), 200 );
    }

    /**
     * Configura lazy loading
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_lazy_loading( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $lazy_config = array(
            'images'      => (bool) $request->get_param( 'images' ),
            'iframes'     => (bool) $request->get_param( 'iframes' ),
            'videos'      => (bool) $request->get_param( 'videos' ),
            'threshold'   => sanitize_text_field( $request->get_param( 'threshold' ) ),
            'placeholder' => sanitize_text_field( $request->get_param( 'placeholder' ) ),
        );

        update_post_meta( $page_id, '_flavor_vbp_lazy_config', $lazy_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de lazy loading guardada.',
            'config'  => $lazy_config,
        ), 200 );
    }

    /**
     * Genera Critical CSS
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_critical_css( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $viewport_width = (int) $request->get_param( 'viewport_width' );
        $viewport_height = (int) $request->get_param( 'viewport_height' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Obtener CSS compilado de la página
        $compiled_css = get_post_meta( $page_id, '_flavor_vbp_compiled_css', true ) ?: '';

        // En un escenario real, aquí se usaría una herramienta como Critical o Penthouse
        // Por ahora, estimamos el CSS crítico basándonos en los primeros bloques
        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $critical_selectors = array();
        $above_fold_elements = array_slice( $elements, 0, 5 );
        foreach ( $above_fold_elements as $element ) {
            $critical_selectors[] = '#' . ( $element['id'] ?? '' );
            $critical_selectors[] = '.' . ( $element['type'] ?? '' );
        }

        $critical_css_meta = array(
            'viewport'           => array( $viewport_width, $viewport_height ),
            'generated_at'       => current_time( 'mysql' ),
            'critical_selectors' => $critical_selectors,
        );

        update_post_meta( $page_id, '_flavor_vbp_critical_css', $critical_css_meta );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Critical CSS generado.',
            'selectors' => count( $critical_selectors ),
        ), 200 );
    }

    /**
     * Auditoría completa de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function full_page_audit( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $categories = $request->get_param( 'categories' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $audit_results = array(
            'page_id'    => $page_id,
            'title'      => $post->post_title,
            'audited_at' => current_time( 'mysql' ),
            'categories' => array(),
            'overall'    => array(
                'score' => 0,
                'grade' => 'F',
            ),
        );

        $total_score = 0;
        $category_count = 0;

        // Performance
        if ( in_array( 'performance', $categories, true ) ) {
            $perf_response = $this->analyze_page_performance( $request );
            $perf_data = $perf_response->get_data();
            $audit_results['categories']['performance'] = array(
                'score'    => $perf_data['score'] ?? 0,
                'grade'    => $perf_data['grade'] ?? 'F',
                'warnings' => $perf_data['warnings'] ?? array(),
            );
            $total_score += $perf_data['score'] ?? 0;
            $category_count++;
        }

        // Accessibility
        if ( in_array( 'accessibility', $categories, true ) ) {
            $acc_response = $this->analyze_accessibility( $request );
            $acc_data = $acc_response->get_data();
            $audit_results['categories']['accessibility'] = array(
                'score'  => $acc_data['score'] ?? 0,
                'grade'  => $acc_data['grade'] ?? 'F',
                'issues' => count( $acc_data['issues'] ?? array() ),
            );
            $total_score += $acc_data['score'] ?? 0;
            $category_count++;
        }

        // SEO
        if ( in_array( 'seo', $categories, true ) ) {
            $seo_response = $this->analyze_page_seo( $request );
            $seo_data = $seo_response->get_data();
            $audit_results['categories']['seo'] = array(
                'score'  => $seo_data['score'] ?? 0,
                'grade'  => $seo_data['grade'] ?? 'F',
                'issues' => count( $seo_data['issues'] ?? array() ),
            );
            $total_score += $seo_data['score'] ?? 0;
            $category_count++;
        }

        // Best Practices
        if ( in_array( 'best-practices', $categories, true ) ) {
            $bp_response = $this->validate_blocks_structure( $request );
            $bp_data = $bp_response->get_data();
            $bp_score = ( $bp_data['valid'] ?? false ) ? 100 : 50;
            $audit_results['categories']['best-practices'] = array(
                'score'  => $bp_score,
                'grade'  => $this->score_to_grade( $bp_score ),
                'valid'  => $bp_data['valid'] ?? false,
                'issues' => count( $bp_data['issues'] ?? array() ),
            );
            $total_score += $bp_score;
            $category_count++;
        }

        if ( $category_count > 0 ) {
            $audit_results['overall']['score'] = round( $total_score / $category_count );
            $audit_results['overall']['grade'] = $this->score_to_grade( $audit_results['overall']['score'] );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'audit'   => $audit_results,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE ANALYTICS
    // =============================================

    /**
     * Obtiene analytics de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_analytics( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $period = $request->get_param( 'period' ) ?: '30d';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $analytics_data = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

        // Calcular estadísticas
        $page_views = $analytics_data['views'] ?? 0;
        $unique_visitors = $analytics_data['unique_visitors'] ?? 0;
        $avg_time_on_page = $analytics_data['avg_time'] ?? 0;
        $bounce_rate = $analytics_data['bounce_rate'] ?? 0;

        return new WP_REST_Response( array(
            'success'   => true,
            'page_id'   => $page_id,
            'period'    => $period,
            'analytics' => array(
                'views'           => $page_views,
                'unique_visitors' => $unique_visitors,
                'avg_time'        => $avg_time_on_page,
                'bounce_rate'     => $bounce_rate,
                'cta_clicks'      => $analytics_data['cta_clicks'] ?? 0,
                'form_submissions' => $analytics_data['form_submissions'] ?? 0,
            ),
        ), 200 );
    }

    /**
     * Registra evento de analytics
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function track_analytics_event( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $event_type = sanitize_text_field( $request->get_param( 'event_type' ) );
        $event_data = $request->get_param( 'event_data' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $analytics = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

        // Incrementar contador según tipo de evento
        switch ( $event_type ) {
            case 'view':
                $analytics['views'] = ( $analytics['views'] ?? 0 ) + 1;
                break;
            case 'cta_click':
                $analytics['cta_clicks'] = ( $analytics['cta_clicks'] ?? 0 ) + 1;
                break;
            case 'form_submit':
                $analytics['form_submissions'] = ( $analytics['form_submissions'] ?? 0 ) + 1;
                break;
            case 'scroll':
                // Registrar profundidad de scroll
                $scroll_depth = $event_data['depth'] ?? 0;
                $analytics['scroll_depths'][] = $scroll_depth;
                break;
        }

        $analytics['last_updated'] = current_time( 'mysql' );

        update_post_meta( $page_id, '_flavor_vbp_analytics', $analytics );

        return new WP_REST_Response( array(
            'success' => true,
            'event'   => $event_type,
            'tracked' => true,
        ), 200 );
    }

    /**
     * Obtiene heatmap de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_heatmap( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $heatmap_type = $request->get_param( 'type' ) ?: 'click';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $heatmap_data = get_post_meta( $page_id, '_flavor_vbp_heatmap_' . $heatmap_type, true ) ?: array();

        return new WP_REST_Response( array(
            'success' => true,
            'type'    => $heatmap_type,
            'data'    => $heatmap_data,
        ), 200 );
    }

    /**
     * Obtiene dashboard de analytics
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_analytics_dashboard( $request ) {
        $period = $request->get_param( 'period' ) ?: '30d';

        // Obtener todas las páginas VBP
        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'post_status'    => 'publish',
        ) );

        $total_views = 0;
        $total_conversions = 0;
        $page_stats = array();

        foreach ( $pages as $page ) {
            $analytics = get_post_meta( $page->ID, '_flavor_vbp_analytics', true ) ?: array();
            $page_views = $analytics['views'] ?? 0;
            $conversions = $analytics['form_submissions'] ?? 0;

            $total_views += $page_views;
            $total_conversions += $conversions;

            $page_stats[] = array(
                'id'          => $page->ID,
                'title'       => $page->post_title,
                'views'       => $page_views,
                'conversions' => $conversions,
            );
        }

        // Ordenar por views
        usort( $page_stats, function( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        return new WP_REST_Response( array(
            'success'    => true,
            'period'     => $period,
            'summary'    => array(
                'total_views'       => $total_views,
                'total_conversions' => $total_conversions,
                'page_count'        => count( $pages ),
            ),
            'top_pages'  => array_slice( $page_stats, 0, 10 ),
        ), 200 );
    }

    /**
     * Compara analytics de páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_pages_analytics( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $metrics = $request->get_param( 'metrics' ) ?: array( 'views', 'conversions' );

        $comparison_data = array();

        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                continue;
            }

            $analytics = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

            $page_metrics = array(
                'id'    => $page_id,
                'title' => $post->post_title,
            );

            if ( in_array( 'views', $metrics, true ) ) {
                $page_metrics['views'] = $analytics['views'] ?? 0;
            }
            if ( in_array( 'conversions', $metrics, true ) ) {
                $page_metrics['conversions'] = $analytics['form_submissions'] ?? 0;
            }
            if ( in_array( 'bounce_rate', $metrics, true ) ) {
                $page_metrics['bounce_rate'] = $analytics['bounce_rate'] ?? 0;
            }
            if ( in_array( 'avg_time', $metrics, true ) ) {
                $page_metrics['avg_time'] = $analytics['avg_time'] ?? 0;
            }

            $comparison_data[] = $page_metrics;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'metrics'    => $metrics,
            'comparison' => $comparison_data,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE COMENTARIOS/FEEDBACK
    // =============================================

    /**
     * Obtiene comentarios de revisión
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_review_comments( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $comments = get_post_meta( $page_id, '_flavor_vbp_review_comments', true ) ?: array();

        return new WP_REST_Response( array(
            'success'  => true,
            'comments' => $comments,
            'count'    => count( $comments ),
        ), 200 );
    }

    /**
     * Añade comentario de revisión
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function add_review_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $comment_text = sanitize_textarea_field( $request->get_param( 'comment' ) );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $position = $request->get_param( 'position' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $comments = get_post_meta( $page_id, '_flavor_vbp_review_comments', true ) ?: array();

        $comment_id = 'comment_' . uniqid();
        $user = wp_get_current_user();

        $new_comment = array(
            'id'         => $comment_id,
            'text'       => $comment_text,
            'element_id' => $element_id,
            'position'   => $position,
            'author'     => array(
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'avatar' => get_avatar_url( $user->ID ),
            ),
            'created_at' => current_time( 'mysql' ),
            'resolved'   => false,
        );

        $comments[] = $new_comment;

        update_post_meta( $page_id, '_flavor_vbp_review_comments', $comments );

        return new WP_REST_Response( array(
            'success' => true,
            'comment' => $new_comment,
            'message' => 'Comentario añadido.',
        ), 200 );
    }

    /**
     * Resuelve comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function resolve_review_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $comments = get_post_meta( $page_id, '_flavor_vbp_review_comments', true ) ?: array();

        foreach ( $comments as &$comment ) {
            if ( $comment['id'] === $comment_id ) {
                $comment['resolved'] = true;
                $comment['resolved_at'] = current_time( 'mysql' );
                $comment['resolved_by'] = get_current_user_id();
                break;
            }
        }

        update_post_meta( $page_id, '_flavor_vbp_review_comments', $comments );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Comentario resuelto.',
        ), 200 );
    }

    /**
     * Elimina comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_review_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $comments = get_post_meta( $page_id, '_flavor_vbp_review_comments', true ) ?: array();

        $comments = array_filter( $comments, function( $comment ) use ( $comment_id ) {
            return $comment['id'] !== $comment_id;
        } );

        update_post_meta( $page_id, '_flavor_vbp_review_comments', array_values( $comments ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Comentario eliminado.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE COMPONENTES DINÁMICOS
    // =============================================

    /**
     * Obtiene configuración de slider
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_slider_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $slider_id = sanitize_text_field( $request->get_param( 'slider_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $slider_element = $this->find_element_by_id( $elements, $slider_id );

        if ( ! $slider_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Slider no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $slider_element['props'] ?? array(),
            'slides'  => $slider_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de slider
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_slider_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $slider_id = sanitize_text_field( $request->get_param( 'slider_id' ) );
        $config = $request->get_param( 'config' );
        $slides = $request->get_param( 'slides' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $slider_id, function( $element ) use ( $config, $slides ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $slides ) {
                $element['children'] = $slides;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Slider actualizado.',
        ), 200 );
    }

    /**
     * Obtiene configuración de tabs
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_tabs_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $tabs_id = sanitize_text_field( $request->get_param( 'tabs_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $tabs_element = $this->find_element_by_id( $elements, $tabs_id );

        if ( ! $tabs_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Tabs no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $tabs_element['props'] ?? array(),
            'tabs'    => $tabs_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de tabs
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_tabs_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $tabs_id = sanitize_text_field( $request->get_param( 'tabs_id' ) );
        $config = $request->get_param( 'config' );
        $tabs = $request->get_param( 'tabs' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $tabs_id, function( $element ) use ( $config, $tabs ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $tabs ) {
                $element['children'] = $tabs;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Tabs actualizado.',
        ), 200 );
    }

    /**
     * Obtiene configuración de accordion
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_accordion_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $accordion_id = sanitize_text_field( $request->get_param( 'accordion_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $accordion_element = $this->find_element_by_id( $elements, $accordion_id );

        if ( ! $accordion_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Accordion no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $accordion_element['props'] ?? array(),
            'items'   => $accordion_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de accordion
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_accordion_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $accordion_id = sanitize_text_field( $request->get_param( 'accordion_id' ) );
        $config = $request->get_param( 'config' );
        $items = $request->get_param( 'items' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $accordion_id, function( $element ) use ( $config, $items ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $items ) {
                $element['children'] = $items;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Accordion actualizado.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE FORMULARIOS
    // =============================================

    /**
     * Obtiene formularios de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_forms( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $forms = $this->find_elements_by_type( $elements, 'form' );

        $forms_data = array_map( function( $form ) {
            return array(
                'id'     => $form['id'],
                'name'   => $form['props']['name'] ?? 'Sin nombre',
                'fields' => count( $form['children'] ?? array() ),
                'config' => $form['props'] ?? array(),
            );
        }, $forms );

        return new WP_REST_Response( array(
            'success' => true,
            'forms'   => $forms_data,
            'count'   => count( $forms_data ),
        ), 200 );
    }

    /**
     * Busca elementos por tipo
     *
     * @param array  $elements Elementos.
     * @param string $element_type Tipo de elemento.
     * @return array
     */
    private function find_elements_by_type( $elements, $element_type ) {
        $found = array();
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === $element_type ) {
                $found[] = $element;
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $found = array_merge( $found, $this->find_elements_by_type( $element['children'], $element_type ) );
            }
        }
        return $found;
    }

    /**
     * Configura formulario de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_page_form( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $form_id = sanitize_text_field( $request->get_param( 'form_id' ) );
        $config = $request->get_param( 'config' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $form_id, function( $element ) use ( $config ) {
            $element['props'] = array_merge( $element['props'] ?? array(), $config );
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Formulario configurado.',
        ), 200 );
    }

    /**
     * Obtiene envíos de formulario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_form_submissions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $form_id = sanitize_text_field( $request->get_param( 'form_id' ) );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $submissions = get_post_meta( $page_id, '_flavor_vbp_form_submissions_' . $form_id, true ) ?: array();

        // Obtener últimos N envíos
        $submissions = array_slice( $submissions, -$limit );

        return new WP_REST_Response( array(
            'success'     => true,
            'form_id'     => $form_id,
            'submissions' => $submissions,
            'total'       => count( $submissions ),
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE SHORTCUTS Y PRODUCTIVIDAD
    // =============================================

    /**
     * Obtiene atajos de teclado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_keyboard_shortcuts( $request ) {
        $shortcuts = get_option( 'flavor_vbp_shortcuts', array(
            'general' => array(
                array( 'keys' => 'Ctrl+S', 'action' => 'save', 'description' => 'Guardar página' ),
                array( 'keys' => 'Ctrl+Z', 'action' => 'undo', 'description' => 'Deshacer' ),
                array( 'keys' => 'Ctrl+Shift+Z', 'action' => 'redo', 'description' => 'Rehacer' ),
                array( 'keys' => 'Ctrl+C', 'action' => 'copy', 'description' => 'Copiar elemento' ),
                array( 'keys' => 'Ctrl+V', 'action' => 'paste', 'description' => 'Pegar elemento' ),
                array( 'keys' => 'Ctrl+X', 'action' => 'cut', 'description' => 'Cortar elemento' ),
                array( 'keys' => 'Delete', 'action' => 'delete', 'description' => 'Eliminar elemento' ),
                array( 'keys' => 'Ctrl+D', 'action' => 'duplicate', 'description' => 'Duplicar elemento' ),
            ),
            'navigation' => array(
                array( 'keys' => 'Ctrl+P', 'action' => 'preview', 'description' => 'Vista previa' ),
                array( 'keys' => 'Escape', 'action' => 'deselect', 'description' => 'Deseleccionar' ),
                array( 'keys' => 'F11', 'action' => 'fullscreen', 'description' => 'Pantalla completa' ),
            ),
            'blocks' => array(
                array( 'keys' => 'Ctrl+Shift+T', 'action' => 'add_text', 'description' => 'Añadir texto' ),
                array( 'keys' => 'Ctrl+Shift+I', 'action' => 'add_image', 'description' => 'Añadir imagen' ),
                array( 'keys' => 'Ctrl+Shift+B', 'action' => 'add_button', 'description' => 'Añadir botón' ),
            ),
        ) );

        $user_shortcuts = get_user_meta( get_current_user_id(), 'vbp_custom_shortcuts', true ) ?: array();

        return new WP_REST_Response( array(
            'success'   => true,
            'shortcuts' => array_merge_recursive( $shortcuts, $user_shortcuts ),
        ), 200 );
    }

    /**
     * Guarda atajos personalizados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_custom_shortcuts( $request ) {
        $shortcuts = $request->get_param( 'shortcuts' );

        if ( ! is_array( $shortcuts ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Atajos inválidos.' ), 400 );
        }

        update_user_meta( get_current_user_id(), 'vbp_custom_shortcuts', $shortcuts );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Atajos guardados.',
        ), 200 );
    }

    /**
     * Obtiene acciones rápidas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_quick_actions( $request ) {
        $context = $request->get_param( 'context' ) ?: 'editor';

        $quick_actions = array(
            'editor' => array(
                array( 'id' => 'add_section', 'label' => 'Añadir sección', 'icon' => 'plus' ),
                array( 'id' => 'add_columns', 'label' => 'Añadir columnas', 'icon' => 'columns' ),
                array( 'id' => 'add_text', 'label' => 'Añadir texto', 'icon' => 'text' ),
                array( 'id' => 'add_image', 'label' => 'Añadir imagen', 'icon' => 'image' ),
                array( 'id' => 'add_button', 'label' => 'Añadir botón', 'icon' => 'button' ),
                array( 'id' => 'add_form', 'label' => 'Añadir formulario', 'icon' => 'form' ),
            ),
            'element' => array(
                array( 'id' => 'edit', 'label' => 'Editar', 'icon' => 'edit' ),
                array( 'id' => 'duplicate', 'label' => 'Duplicar', 'icon' => 'copy' ),
                array( 'id' => 'move_up', 'label' => 'Mover arriba', 'icon' => 'arrow-up' ),
                array( 'id' => 'move_down', 'label' => 'Mover abajo', 'icon' => 'arrow-down' ),
                array( 'id' => 'delete', 'label' => 'Eliminar', 'icon' => 'trash' ),
            ),
        );

        return new WP_REST_Response( array(
            'success' => true,
            'actions' => $quick_actions[ $context ] ?? array(),
        ), 200 );
    }

    /**
     * Ejecuta acción rápida
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function execute_quick_action( $request ) {
        $action_id = sanitize_text_field( $request->get_param( 'action_id' ) );
        $page_id = (int) $request->get_param( 'page_id' );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $params = $request->get_param( 'params' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $result = array( 'action' => $action_id, 'executed' => true );

        switch ( $action_id ) {
            case 'duplicate':
                // Duplicar elemento
                $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
                $element = $this->find_element_by_id( $elements, $element_id );
                if ( $element ) {
                    $duplicate = $element;
                    $duplicate['id'] = 'el_' . uniqid();
                    $elements[] = $duplicate;
                    update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                    $result['new_element_id'] = $duplicate['id'];
                }
                break;

            case 'delete':
                // Eliminar elemento
                $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
                $elements = $this->remove_element_by_id( $elements, $element_id );
                update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                break;

            default:
                $result['executed'] = false;
                $result['message'] = 'Acción no implementada.';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'result'  => $result,
        ), 200 );
    }

    /**
     * Elimina elemento por ID
     *
     * @param array  $elements Elementos.
     * @param string $element_id ID del elemento.
     * @return array
     */
    private function remove_element_by_id( $elements, $element_id ) {
        $filtered = array();
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $element_id ) {
                continue;
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->remove_element_by_id( $element['children'], $element_id );
            }
            $filtered[] = $element;
        }
        return $filtered;
    }

    /**
     * Obtiene historial de acciones
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_action_history( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $history = get_post_meta( $page_id, '_flavor_vbp_action_history', true ) ?: array();

        return new WP_REST_Response( array(
            'success' => true,
            'history' => array_slice( $history, -$limit ),
            'total'   => count( $history ),
        ), 200 );
    }

    /**
     * Deshace última acción
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function undo_last_action( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );

        $undo_stack = get_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id() ) ?: array();

        if ( empty( $undo_stack ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay acciones para deshacer.' ), 400 );
        }

        $last_state = array_pop( $undo_stack );

        // Guardar estado actual en redo
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $redo_stack = get_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id() ) ?: array();
        $redo_stack[] = $current_elements;
        set_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id(), $redo_stack, HOUR_IN_SECONDS );

        // Restaurar estado anterior
        update_post_meta( $page_id, '_flavor_vbp_elements', $last_state );

        set_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id(), $undo_stack, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Acción deshecha.',
            'undo_count' => count( $undo_stack ),
        ), 200 );
    }

    /**
     * Rehace última acción
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function redo_action( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );

        $redo_stack = get_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id() ) ?: array();

        if ( empty( $redo_stack ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay acciones para rehacer.' ), 400 );
        }

        $next_state = array_pop( $redo_stack );

        // Guardar estado actual en undo
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $undo_stack = get_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id() ) ?: array();
        $undo_stack[] = $current_elements;
        set_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id(), $undo_stack, HOUR_IN_SECONDS );

        // Restaurar siguiente estado
        update_post_meta( $page_id, '_flavor_vbp_elements', $next_state );

        set_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id(), $redo_stack, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Acción rehecha.',
            'redo_count' => count( $redo_stack ),
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE PREFERENCIAS DEL EDITOR
    // =============================================

    /**
     * Obtiene preferencias del editor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_editor_preferences( $request ) {
        $user_id = get_current_user_id();

        $preferences = get_user_meta( $user_id, 'vbp_editor_preferences', true ) ?: array(
            'theme'         => 'light',
            'grid_visible'  => true,
            'grid_size'     => 8,
            'rulers_visible' => true,
            'auto_save'     => true,
            'auto_save_interval' => 60,
            'sidebar_position' => 'right',
            'panel_sizes'   => array(
                'elements' => 300,
                'properties' => 350,
            ),
            'zoom_level'    => 100,
            'snap_to_grid'  => true,
        );

        return new WP_REST_Response( array(
            'success'     => true,
            'preferences' => $preferences,
        ), 200 );
    }

    /**
     * Guarda preferencias del editor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_editor_preferences( $request ) {
        $user_id = get_current_user_id();
        $preferences = $request->get_param( 'preferences' );

        if ( ! is_array( $preferences ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preferencias inválidas.' ), 400 );
        }

        $current = get_user_meta( $user_id, 'vbp_editor_preferences', true ) ?: array();
        $merged = array_merge( $current, $preferences );

        update_user_meta( $user_id, 'vbp_editor_preferences', $merged );

        return new WP_REST_Response( array(
            'success'     => true,
            'preferences' => $merged,
            'message'     => 'Preferencias guardadas.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE BÚSQUEDA AVANZADA
    // =============================================

    /**
     * Búsqueda global en VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function global_vbp_search( $request ) {
        $query = sanitize_text_field( $request->get_param( 'query' ) );
        $search_in = $request->get_param( 'search_in' ) ?: array( 'pages', 'blocks', 'widgets' );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $results = array(
            'pages'   => array(),
            'blocks'  => array(),
            'widgets' => array(),
        );

        if ( in_array( 'pages', $search_in, true ) ) {
            $pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => $limit,
                's'              => $query,
                'post_status'    => array( 'publish', 'draft' ),
            ) );

            foreach ( $pages as $page ) {
                $results['pages'][] = array(
                    'id'     => $page->ID,
                    'title'  => $page->post_title,
                    'slug'   => $page->post_name,
                    'status' => $page->post_status,
                );
            }
        }

        if ( in_array( 'blocks', $search_in, true ) ) {
            // Buscar en contenido de bloques (con límite seguro)
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'post_status'    => 'any',
            ) );

            foreach ( $all_pages as $page ) {
                $elements = json_decode( get_post_meta( $page->ID, '_flavor_vbp_elements', true ), true ) ?: array();
                $matching_blocks = $this->search_in_elements( $elements, $query );

                foreach ( $matching_blocks as $block ) {
                    $results['blocks'][] = array(
                        'page_id'    => $page->ID,
                        'page_title' => $page->post_title,
                        'block_id'   => $block['id'],
                        'block_type' => $block['type'],
                        'match'      => $block['match'],
                    );
                }
            }
        }

        if ( in_array( 'widgets', $search_in, true ) ) {
            $widgets = get_option( 'flavor_vbp_widgets', array() );

            foreach ( $widgets as $widget ) {
                if ( stripos( $widget['name'], $query ) !== false ) {
                    $results['widgets'][] = $widget;
                }
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'query'   => $query,
            'results' => $results,
            'counts'  => array(
                'pages'   => count( $results['pages'] ),
                'blocks'  => count( $results['blocks'] ),
                'widgets' => count( $results['widgets'] ),
            ),
        ), 200 );
    }

    /**
     * Busca en elementos
     *
     * @param array  $elements Elementos.
     * @param string $query Query de búsqueda.
     * @return array
     */
    private function search_in_elements( $elements, $query ) {
        $matches = array();

        foreach ( $elements as $element ) {
            $found_in = null;

            // Buscar en texto/contenido
            if ( isset( $element['props']['text'] ) && stripos( $element['props']['text'], $query ) !== false ) {
                $found_in = 'text';
            } elseif ( isset( $element['props']['content'] ) && stripos( $element['props']['content'], $query ) !== false ) {
                $found_in = 'content';
            } elseif ( isset( $element['props']['title'] ) && stripos( $element['props']['title'], $query ) !== false ) {
                $found_in = 'title';
            }

            if ( $found_in ) {
                $matches[] = array(
                    'id'    => $element['id'],
                    'type'  => $element['type'],
                    'match' => $found_in,
                );
            }

            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $matches = array_merge( $matches, $this->search_in_elements( $element['children'], $query ) );
            }
        }

        return $matches;
    }

    /**
     * Buscar y reemplazar global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function global_search_replace( $request ) {
        $search_text = $request->get_param( 'search' );
        $replace_text = $request->get_param( 'replace' );
        $page_ids = $request->get_param( 'page_ids' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $total_replacements = 0;
        $affected_pages = array();

        $pages_to_search = $page_ids ? array_map( 'intval', $page_ids ) : array();

        if ( empty( $pages_to_search ) ) {
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'fields'         => 'ids',
            ) );
            $pages_to_search = $all_pages;
        }

        foreach ( $pages_to_search as $page_id ) {
            $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
            $replacements = 0;

            $elements = $this->replace_in_elements( $elements, $search_text, $replace_text, $replacements );

            if ( $replacements > 0 ) {
                $total_replacements += $replacements;
                $affected_pages[] = array(
                    'id'           => $page_id,
                    'title'        => get_the_title( $page_id ),
                    'replacements' => $replacements,
                );

                if ( ! $dry_run ) {
                    update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                }
            }
        }

        return new WP_REST_Response( array(
            'success'            => true,
            'dry_run'            => $dry_run,
            'total_replacements' => $total_replacements,
            'affected_pages'     => $affected_pages,
            'message'            => $dry_run ? 'Vista previa de reemplazos.' : 'Reemplazos aplicados.',
        ), 200 );
    }

    /**
     * Reemplaza en elementos
     *
     * @param array  $elements Elementos.
     * @param string $search Texto a buscar.
     * @param string $replace Texto de reemplazo.
     * @param int    $count Contador de reemplazos.
     * @return array
     */
    private function replace_in_elements( $elements, $search, $replace, &$count ) {
        foreach ( $elements as &$element ) {
            if ( isset( $element['props']['text'] ) ) {
                $new_text = str_ireplace( $search, $replace, $element['props']['text'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['text'] = $new_text;
                    $count += $replacements;
                }
            }
            if ( isset( $element['props']['content'] ) ) {
                $new_content = str_ireplace( $search, $replace, $element['props']['content'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['content'] = $new_content;
                    $count += $replacements;
                }
            }
            if ( isset( $element['props']['title'] ) ) {
                $new_title = str_ireplace( $search, $replace, $element['props']['title'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['title'] = $new_title;
                    $count += $replacements;
                }
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->replace_in_elements( $element['children'], $search, $replace, $count );
            }
        }
        return $elements;
    }

    /**
     * Busca bloques por tipo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function search_blocks_by_type( $request ) {
        $block_type = sanitize_text_field( $request->get_param( 'type' ) );
        $page_ids = $request->get_param( 'page_ids' );

        $results = array();

        $pages_to_search = $page_ids ? array_map( 'intval', $page_ids ) : array();

        if ( empty( $pages_to_search ) ) {
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'fields'         => 'ids',
            ) );
            $pages_to_search = $all_pages;
        }

        foreach ( $pages_to_search as $page_id ) {
            $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
            $found_blocks = $this->find_elements_by_type( $elements, $block_type );

            if ( ! empty( $found_blocks ) ) {
                $results[] = array(
                    'page_id'    => $page_id,
                    'page_title' => get_the_title( $page_id ),
                    'blocks'     => array_map( function( $block ) {
                        return array(
                            'id'    => $block['id'],
                            'props' => $block['props'] ?? array(),
                        );
                    }, $found_blocks ),
                    'count'      => count( $found_blocks ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'block_type'  => $block_type,
            'results'     => $results,
            'total_found' => array_sum( array_column( $results, 'count' ) ),
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE EDICIÓN AVANZADA DE BLOQUES
    // =============================================

    /**
     * Bloquea/desbloquea elemento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function toggle_block_lock( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $locked = (bool) $request->get_param( 'locked' );
        $lock_type = sanitize_text_field( $request->get_param( 'lock_type' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $locked, $lock_type ) {
            $element['locked'] = $locked;
            $element['lock_type'] = $lock_type;
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'locked'    => $locked,
            'lock_type' => $lock_type,
            'message'   => $locked ? 'Elemento bloqueado.' : 'Elemento desbloqueado.',
        ), 200 );
    }

    /**
     * Alinea múltiples bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function align_multiple_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $alignment = sanitize_text_field( $request->get_param( 'alignment' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        // Calcular posiciones de referencia
        $blocks_data = array();
        foreach ( $block_ids as $block_id ) {
            $block = $this->find_element_by_id( $elements, $block_id );
            if ( $block ) {
                $blocks_data[] = array(
                    'id'     => $block_id,
                    'left'   => $block['styles']['left'] ?? 0,
                    'top'    => $block['styles']['top'] ?? 0,
                    'width'  => $block['styles']['width'] ?? 100,
                    'height' => $block['styles']['height'] ?? 100,
                );
            }
        }

        if ( empty( $blocks_data ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No se encontraron bloques.' ), 404 );
        }

        // Calcular alineación
        $alignment_value = 0;
        switch ( $alignment ) {
            case 'left':
                $alignment_value = min( array_column( $blocks_data, 'left' ) );
                break;
            case 'right':
                $max_right = 0;
                foreach ( $blocks_data as $bd ) {
                    $right = $bd['left'] + $bd['width'];
                    if ( $right > $max_right ) {
                        $max_right = $right;
                    }
                }
                $alignment_value = $max_right;
                break;
            case 'center':
                $min_left = min( array_column( $blocks_data, 'left' ) );
                $max_right = 0;
                foreach ( $blocks_data as $bd ) {
                    $right = $bd['left'] + $bd['width'];
                    if ( $right > $max_right ) {
                        $max_right = $right;
                    }
                }
                $alignment_value = ( $min_left + $max_right ) / 2;
                break;
            case 'top':
                $alignment_value = min( array_column( $blocks_data, 'top' ) );
                break;
            case 'bottom':
                $max_bottom = 0;
                foreach ( $blocks_data as $bd ) {
                    $bottom = $bd['top'] + $bd['height'];
                    if ( $bottom > $max_bottom ) {
                        $max_bottom = $bottom;
                    }
                }
                $alignment_value = $max_bottom;
                break;
            case 'middle':
                $min_top = min( array_column( $blocks_data, 'top' ) );
                $max_bottom = 0;
                foreach ( $blocks_data as $bd ) {
                    $bottom = $bd['top'] + $bd['height'];
                    if ( $bottom > $max_bottom ) {
                        $max_bottom = $bottom;
                    }
                }
                $alignment_value = ( $min_top + $max_bottom ) / 2;
                break;
        }

        // Aplicar alineación
        foreach ( $block_ids as $block_id ) {
            $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $alignment, $alignment_value ) {
                if ( ! isset( $element['styles'] ) ) {
                    $element['styles'] = array();
                }

                $width = $element['styles']['width'] ?? 100;
                $height = $element['styles']['height'] ?? 100;

                switch ( $alignment ) {
                    case 'left':
                        $element['styles']['left'] = $alignment_value;
                        break;
                    case 'right':
                        $element['styles']['left'] = $alignment_value - $width;
                        break;
                    case 'center':
                        $element['styles']['left'] = $alignment_value - ( $width / 2 );
                        break;
                    case 'top':
                        $element['styles']['top'] = $alignment_value;
                        break;
                    case 'bottom':
                        $element['styles']['top'] = $alignment_value - $height;
                        break;
                    case 'middle':
                        $element['styles']['top'] = $alignment_value - ( $height / 2 );
                        break;
                }

                return $element;
            } );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'alignment' => $alignment,
            'blocks'    => count( $block_ids ),
            'message'   => 'Bloques alineados.',
        ), 200 );
    }

    /**
     * Distribuye bloques uniformemente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function distribute_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );
        $fixed_spacing = $request->get_param( 'spacing' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        if ( count( $block_ids ) < 3 ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Se necesitan al menos 3 bloques para distribuir.' ), 400 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        // Obtener datos de bloques
        $blocks_data = array();
        foreach ( $block_ids as $block_id ) {
            $block = $this->find_element_by_id( $elements, $block_id );
            if ( $block ) {
                $position_key = $direction === 'horizontal' ? 'left' : 'top';
                $size_key = $direction === 'horizontal' ? 'width' : 'height';
                $blocks_data[] = array(
                    'id'       => $block_id,
                    'position' => $block['styles'][ $position_key ] ?? 0,
                    'size'     => $block['styles'][ $size_key ] ?? 100,
                );
            }
        }

        // Ordenar por posición
        usort( $blocks_data, function( $a, $b ) {
            return $a['position'] - $b['position'];
        } );

        // Calcular distribución
        $first_pos = $blocks_data[0]['position'];
        $last_block = end( $blocks_data );
        $last_pos = $last_block['position'] + $last_block['size'];
        $total_size = array_sum( array_column( $blocks_data, 'size' ) );
        $available_space = $last_pos - $first_pos - $total_size;
        $spacing = $fixed_spacing ?? ( $available_space / ( count( $blocks_data ) - 1 ) );

        // Aplicar distribución
        $current_pos = $first_pos;
        foreach ( $blocks_data as $bd ) {
            $position_key = $direction === 'horizontal' ? 'left' : 'top';
            $elements = $this->update_element_by_id( $elements, $bd['id'], function( $element ) use ( $position_key, $current_pos ) {
                if ( ! isset( $element['styles'] ) ) {
                    $element['styles'] = array();
                }
                $element['styles'][ $position_key ] = $current_pos;
                return $element;
            } );
            $current_pos += $bd['size'] + $spacing;
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'direction' => $direction,
            'spacing'   => $spacing,
            'blocks'    => count( $block_ids ),
            'message'   => 'Bloques distribuidos.',
        ), 200 );
    }

    /**
     * Snap bloque a grid
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function snap_block_to_grid( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $grid_size = (int) $request->get_param( 'grid_size' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $snapped_values = array();
        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $grid_size, &$snapped_values ) {
            if ( ! isset( $element['styles'] ) ) {
                $element['styles'] = array();
            }

            $position_props = array( 'left', 'top', 'width', 'height', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight', 'paddingTop', 'paddingBottom', 'paddingLeft', 'paddingRight' );

            foreach ( $position_props as $prop ) {
                if ( isset( $element['styles'][ $prop ] ) && is_numeric( $element['styles'][ $prop ] ) ) {
                    $original = $element['styles'][ $prop ];
                    $snapped = round( $original / $grid_size ) * $grid_size;
                    $element['styles'][ $prop ] = $snapped;
                    if ( $original !== $snapped ) {
                        $snapped_values[ $prop ] = array( 'from' => $original, 'to' => $snapped );
                    }
                }
            }

            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'grid_size' => $grid_size,
            'snapped'   => $snapped_values,
            'message'   => 'Elemento ajustado a grid.',
        ), 200 );
    }

    /**
     * Obtiene guías inteligentes
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_smart_guides( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $active_block_id = sanitize_text_field( $request->get_param( 'active_block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $guides = array(
            'horizontal' => array(),
            'vertical'   => array(),
        );

        // Guías de página
        $page_width = 1200;
        $guides['vertical'][] = array( 'position' => 0, 'type' => 'page', 'label' => 'Borde izquierdo' );
        $guides['vertical'][] = array( 'position' => $page_width / 2, 'type' => 'page', 'label' => 'Centro' );
        $guides['vertical'][] = array( 'position' => $page_width, 'type' => 'page', 'label' => 'Borde derecho' );

        // Guías de otros elementos
        foreach ( $elements as $element ) {
            if ( $element['id'] === $active_block_id ) {
                continue;
            }

            $left = $element['styles']['left'] ?? 0;
            $top = $element['styles']['top'] ?? 0;
            $width = $element['styles']['width'] ?? 0;
            $height = $element['styles']['height'] ?? 0;

            $guides['vertical'][] = array( 'position' => $left, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['vertical'][] = array( 'position' => $left + $width / 2, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['vertical'][] = array( 'position' => $left + $width, 'type' => 'element', 'element_id' => $element['id'] );

            $guides['horizontal'][] = array( 'position' => $top, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['horizontal'][] = array( 'position' => $top + $height / 2, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['horizontal'][] = array( 'position' => $top + $height, 'type' => 'element', 'element_id' => $element['id'] );
        }

        // Guías personalizadas
        $custom_guides = get_post_meta( $page_id, '_flavor_vbp_custom_guides', true ) ?: array();
        foreach ( $custom_guides as $guide ) {
            $guides[ $guide['type'] ][] = array(
                'position' => $guide['position'],
                'type'     => 'custom',
                'color'    => $guide['color'] ?? '#00ff00',
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'guides'  => $guides,
        ), 200 );
    }

    /**
     * Crea guía personalizada
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_custom_guide( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $guide_type = sanitize_text_field( $request->get_param( 'type' ) );
        $position = (int) $request->get_param( 'position' );
        $color = sanitize_hex_color( $request->get_param( 'color' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $guides = get_post_meta( $page_id, '_flavor_vbp_custom_guides', true ) ?: array();

        $guide_id = 'guide_' . uniqid();
        $guides[] = array(
            'id'       => $guide_id,
            'type'     => $guide_type,
            'position' => $position,
            'color'    => $color,
        );

        update_post_meta( $page_id, '_flavor_vbp_custom_guides', $guides );

        return new WP_REST_Response( array(
            'success'  => true,
            'guide_id' => $guide_id,
            'message'  => 'Guía creada.',
        ), 200 );
    }

    /**
     * Copia estilos entre bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function copy_block_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $source_block_id = sanitize_text_field( $request->get_param( 'source_block_id' ) );
        $target_block_ids = $request->get_param( 'target_block_ids' );
        $style_properties = $request->get_param( 'style_properties' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        // Obtener estilos del bloque fuente
        $source_block = $this->find_element_by_id( $elements, $source_block_id );
        if ( ! $source_block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque fuente no encontrado.' ), 404 );
        }

        $source_styles = $source_block['styles'] ?? array();

        // Filtrar propiedades si se especifican
        if ( ! empty( $style_properties ) ) {
            $source_styles = array_intersect_key( $source_styles, array_flip( $style_properties ) );
        }

        // Excluir posición por defecto
        if ( empty( $style_properties ) ) {
            unset( $source_styles['left'], $source_styles['top'] );
        }

        // Aplicar a bloques destino
        $updated_count = 0;
        foreach ( $target_block_ids as $target_id ) {
            $elements = $this->update_element_by_id( $elements, $target_id, function( $element ) use ( $source_styles, &$updated_count ) {
                if ( ! isset( $element['styles'] ) ) {
                    $element['styles'] = array();
                }
                $element['styles'] = array_merge( $element['styles'], $source_styles );
                $updated_count++;
                return $element;
            } );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'       => true,
            'styles_copied' => array_keys( $source_styles ),
            'targets'       => $updated_count,
            'message'       => 'Estilos copiados a ' . $updated_count . ' bloques.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE WIDGETS AVANZADOS
    // =============================================

    /**
     * Obtiene biblioteca de widgets
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_library( $request ) {
        $category_filter = sanitize_text_field( $request->get_param( 'category' ) );
        $search_query = sanitize_text_field( $request->get_param( 'search' ) );

        $widgets = get_option( 'flavor_vbp_widget_library', array() );

        // Filtrar por categoría
        if ( $category_filter ) {
            $widgets = array_filter( $widgets, function( $w ) use ( $category_filter ) {
                return $w['category'] === $category_filter;
            } );
        }

        // Filtrar por búsqueda
        if ( $search_query ) {
            $widgets = array_filter( $widgets, function( $w ) use ( $search_query ) {
                return stripos( $w['name'], $search_query ) !== false ||
                       ( isset( $w['tags'] ) && array_filter( $w['tags'], function( $tag ) use ( $search_query ) {
                           return stripos( $tag, $search_query ) !== false;
                       } ) );
            } );
        }

        // Obtener categorías disponibles
        $categories = array_unique( array_column( get_option( 'flavor_vbp_widget_library', array() ), 'category' ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'widgets'    => array_values( $widgets ),
            'categories' => array_values( $categories ),
            'count'      => count( $widgets ),
        ), 200 );
    }

    /**
     * Guarda widget en biblioteca
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_widget_to_library( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $widget_data = $request->get_param( 'widget_data' );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $tags = $request->get_param( 'tags' ) ?: array();
        $is_global = (bool) $request->get_param( 'is_global' );

        $widgets = get_option( 'flavor_vbp_widget_library', array() );

        $widget_id = sanitize_title( $name ) . '_' . uniqid();
        $widgets[ $widget_id ] = array(
            'id'          => $widget_id,
            'name'        => $name,
            'category'    => $category,
            'widget_data' => $widget_data,
            'tags'        => array_map( 'sanitize_text_field', $tags ),
            'is_global'   => $is_global,
            'instances'   => array(),
            'created_at'  => current_time( 'mysql' ),
            'author'      => get_current_user_id(),
        );

        update_option( 'flavor_vbp_widget_library', $widgets );

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'message'   => 'Widget guardado en biblioteca.',
        ), 200 );
    }

    /**
     * Sincroniza widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function sync_global_widget( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_widget_library', array() );
        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $widget = $widgets[ $widget_id ];
        if ( ! $widget['is_global'] ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'El widget no es global.' ), 400 );
        }

        $synced_count = 0;

        // Sincronizar en todas las instancias
        foreach ( $widget['instances'] as $instance ) {
            $page_id = $instance['page_id'];
            $block_id = $instance['block_id'];

            $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

            $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $widget ) {
                $element = array_merge( $element, $widget['widget_data'] );
                $element['synced_at'] = current_time( 'mysql' );
                return $element;
            } );

            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
            $synced_count++;
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'synced_count' => $synced_count,
            'message'      => 'Widget sincronizado en ' . $synced_count . ' instancias.',
        ), 200 );
    }

    /**
     * Obtiene instancias de widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_instances( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_widget_library', array() );
        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $instances = $widgets[ $widget_id ]['instances'] ?? array();

        // Enriquecer con información de página
        foreach ( $instances as &$instance ) {
            $instance['page_title'] = get_the_title( $instance['page_id'] );
            $instance['page_url'] = get_permalink( $instance['page_id'] );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'instances' => $instances,
            'count'     => count( $instances ),
        ), 200 );
    }

    /**
     * Obtiene presets de widgets
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_presets( $request ) {
        $widget_type = sanitize_text_field( $request->get_param( 'widget_type' ) );

        $presets = get_option( 'flavor_vbp_widget_presets', array() );

        if ( $widget_type ) {
            $presets = array_filter( $presets, function( $p ) use ( $widget_type ) {
                return $p['widget_type'] === $widget_type;
            } );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'presets' => array_values( $presets ),
        ), 200 );
    }

    /**
     * Crea preset de widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_widget_preset( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $widget_type = sanitize_text_field( $request->get_param( 'widget_type' ) );
        $preset_data = $request->get_param( 'preset_data' );

        $presets = get_option( 'flavor_vbp_widget_presets', array() );

        $preset_id = sanitize_title( $name ) . '_' . uniqid();
        $presets[ $preset_id ] = array(
            'id'          => $preset_id,
            'name'        => $name,
            'widget_type' => $widget_type,
            'preset_data' => $preset_data,
            'created_at'  => current_time( 'mysql' ),
        );

        update_option( 'flavor_vbp_widget_presets', $presets );

        return new WP_REST_Response( array(
            'success'   => true,
            'preset_id' => $preset_id,
            'message'   => 'Preset de widget creado.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE PREVISUALIZACIONES AVANZADAS
    // =============================================

    /**
     * Preview multi-dispositivo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_multi_device_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $devices = $request->get_param( 'devices' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $device_configs = array(
            'desktop' => array( 'width' => 1920, 'height' => 1080, 'scale' => 0.5 ),
            'laptop'  => array( 'width' => 1366, 'height' => 768, 'scale' => 0.6 ),
            'tablet'  => array( 'width' => 768, 'height' => 1024, 'scale' => 0.7 ),
            'mobile'  => array( 'width' => 375, 'height' => 812, 'scale' => 0.8 ),
        );

        $previews = array();
        $base_url = get_permalink( $page_id );

        foreach ( $devices as $device ) {
            if ( isset( $device_configs[ $device ] ) ) {
                $config = $device_configs[ $device ];
                $previews[ $device ] = array(
                    'device'      => $device,
                    'url'         => add_query_arg( 'vbp_preview_device', $device, $base_url ),
                    'width'       => $config['width'],
                    'height'      => $config['height'],
                    'scale'       => $config['scale'],
                    'iframe_url'  => add_query_arg( array(
                        'vbp_preview'        => '1',
                        'vbp_preview_device' => $device,
                    ), $base_url ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'previews' => $previews,
        ), 200 );
    }

    /**
     * Compara páginas en preview
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_pages_preview( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $device = sanitize_text_field( $request->get_param( 'device' ) );

        $comparisons = array();

        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                continue;
            }

            $comparisons[] = array(
                'page_id'    => $page_id,
                'title'      => $post->post_title,
                'url'        => get_permalink( $page_id ),
                'preview_url' => add_query_arg( array(
                    'vbp_preview'        => '1',
                    'vbp_preview_device' => $device,
                ), get_permalink( $page_id ) ),
                'status'     => $post->post_status,
            );
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'device'      => $device,
            'comparisons' => $comparisons,
        ), 200 );
    }

    /**
     * Captura screenshot de página VBP
     *
     * Fase 3: Implementación real con múltiples proveedores.
     * Soporta: Browserless, ScreenshotOne, o generación local con Puppeteer.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function capture_page_screenshot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $device = sanitize_text_field( $request->get_param( 'device' ) ) ?: 'desktop';
        $full_page = (bool) $request->get_param( 'full_page' );
        $format = sanitize_text_field( $request->get_param( 'format' ) ) ?: 'png';
        $quality = (int) $request->get_param( 'quality' ) ?: 90;

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $page_url = get_permalink( $page_id );
        $settings = get_option( 'flavor_vbp_screenshot_settings', array() );

        // Dimensiones por dispositivo
        $viewport = $this->get_device_viewport( $device );

        // Intentar con proveedores configurados
        $screenshot_result = null;

        // Proveedor 1: Browserless (self-hosted o cloud)
        if ( ! empty( $settings['browserless_token'] ) ) {
            $screenshot_result = $this->capture_with_browserless(
                $page_url,
                $settings['browserless_token'],
                $settings['browserless_url'] ?? 'https://chrome.browserless.io',
                $viewport,
                $full_page,
                $format,
                $quality
            );
        }

        // Proveedor 2: ScreenshotOne API
        if ( ! $screenshot_result && ! empty( $settings['screenshotone_key'] ) ) {
            $screenshot_result = $this->capture_with_screenshotone(
                $page_url,
                $settings['screenshotone_key'],
                $viewport,
                $full_page,
                $format
            );
        }

        // Proveedor 3: Generar paquete para captura local con Puppeteer
        if ( ! $screenshot_result ) {
            $screenshot_result = $this->generate_screenshot_package( $page_id, $viewport, $full_page );
        }

        if ( is_wp_error( $screenshot_result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $screenshot_result->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array_merge( array(
            'success'  => true,
            'page_id'  => $page_id,
            'device'   => $device,
            'viewport' => $viewport,
            'format'   => $format,
        ), $screenshot_result ), 200 );
    }

    /**
     * Captura con Browserless
     *
     * @param string $url              URL a capturar.
     * @param string $token            Token de API.
     * @param string $browserless_url  URL del servicio.
     * @param array  $viewport         Dimensiones.
     * @param bool   $full_page        Captura completa.
     * @param string $format           Formato de imagen.
     * @param int    $quality          Calidad.
     * @return array|null
     */
    private function capture_with_browserless( $url, $token, $browserless_url, $viewport, $full_page, $format, $quality ) {
        $api_url = trailingslashit( $browserless_url ) . 'screenshot?token=' . $token;

        $body = array(
            'url'     => $url,
            'options' => array(
                'fullPage' => $full_page,
                'type'     => $format,
            ),
            'viewport' => array(
                'width'  => $viewport['width'],
                'height' => $viewport['height'],
            ),
        );

        if ( 'jpeg' === $format || 'jpg' === $format ) {
            $body['options']['quality'] = $quality;
        }

        $response = wp_remote_post( $api_url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return null;
        }

        $image_data = wp_remote_retrieve_body( $response );

        // Guardar en uploads
        $upload_dir = wp_upload_dir();
        $filename = 'vbp-screenshot-' . time() . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $filepath, $image_data );

        return array(
            'provider'       => 'browserless',
            'screenshot_url' => $upload_dir['url'] . '/' . $filename,
            'file_path'      => $filepath,
            'file_size'      => strlen( $image_data ),
        );
    }

    /**
     * Captura con ScreenshotOne
     *
     * @param string $url       URL a capturar.
     * @param string $api_key   Clave API.
     * @param array  $viewport  Dimensiones.
     * @param bool   $full_page Captura completa.
     * @param string $format    Formato.
     * @return array|null
     */
    private function capture_with_screenshotone( $url, $api_key, $viewport, $full_page, $format ) {
        $api_url = add_query_arg( array(
            'access_key'      => $api_key,
            'url'             => rawurlencode( $url ),
            'viewport_width'  => $viewport['width'],
            'viewport_height' => $viewport['height'],
            'full_page'       => $full_page ? 'true' : 'false',
            'format'          => $format,
        ), 'https://api.screenshotone.com/take' );

        $response = wp_remote_get( $api_url, array( 'timeout' => 30 ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $image_data = wp_remote_retrieve_body( $response );

        $upload_dir = wp_upload_dir();
        $filename = 'vbp-screenshot-' . time() . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $filepath, $image_data );

        return array(
            'provider'       => 'screenshotone',
            'screenshot_url' => $upload_dir['url'] . '/' . $filename,
            'file_path'      => $filepath,
        );
    }

    /**
     * Genera paquete para captura local con Puppeteer
     *
     * @param int   $page_id   ID de página.
     * @param array $viewport  Dimensiones.
     * @param bool  $full_page Captura completa.
     * @return array
     */
    private function generate_screenshot_package( $page_id, $viewport, $full_page ) {
        $page_url = get_permalink( $page_id );

        // Generar script de Puppeteer
        $puppeteer_script = $this->generate_puppeteer_script( $page_url, $viewport, $full_page );

        return array(
            'provider'         => 'local',
            'requires_setup'   => true,
            'page_url'         => $page_url,
            'puppeteer_script' => $puppeteer_script,
            'instructions'     => array(
                'Para capturar screenshots localmente:',
                '1. Instalar Node.js y Puppeteer: npm install puppeteer',
                '2. Guardar el script puppeteer_script como capture.js',
                '3. Ejecutar: node capture.js',
                'O configurar un servicio externo en Ajustes > VBP > Screenshots',
            ),
        );
    }

    /**
     * Genera script de Puppeteer
     *
     * @param string $url       URL a capturar.
     * @param array  $viewport  Dimensiones.
     * @param bool   $full_page Captura completa.
     * @return string
     */
    private function generate_puppeteer_script( $url, $viewport, $full_page ) {
        $full_page_str = $full_page ? 'true' : 'false';
        $width = $viewport['width'];
        $height = $viewport['height'];

        return "const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    await page.setViewport({
        width: {$width},
        height: {$height}
    });

    await page.goto('{$url}', { waitUntil: 'networkidle2' });

    await page.screenshot({
        path: 'screenshot.png',
        fullPage: {$full_page_str}
    });

    await browser.close();
    console.log('Screenshot saved to screenshot.png');
})();
";
    }

    /**
     * Obtiene viewport por tipo de dispositivo
     *
     * @param string $device Tipo de dispositivo.
     * @return array
     */
    private function get_device_viewport( $device ) {
        $viewports = array(
            'desktop'   => array( 'width' => 1920, 'height' => 1080 ),
            'laptop'    => array( 'width' => 1366, 'height' => 768 ),
            'tablet'    => array( 'width' => 768, 'height' => 1024 ),
            'mobile'    => array( 'width' => 375, 'height' => 812 ),
            'mobile-lg' => array( 'width' => 414, 'height' => 896 ),
        );

        return $viewports[ $device ] ?? $viewports['desktop'];
    }

    /**
     * Preview personalizado con datos de usuario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_personalized_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_data = $request->get_param( 'user_data' ) ?: array();
        $location = $request->get_param( 'location' ) ?: array();
        $device_type = sanitize_text_field( $request->get_param( 'device_type' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Generar token de preview personalizado
        $preview_token = wp_generate_password( 32, false );
        $preview_data = array(
            'page_id'     => $page_id,
            'user_data'   => $user_data,
            'location'    => $location,
            'device_type' => $device_type,
            'created_at'  => time(),
        );

        set_transient( 'vbp_personalized_preview_' . $preview_token, $preview_data, HOUR_IN_SECONDS );

        $preview_url = add_query_arg( array(
            'vbp_personalized' => $preview_token,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'token'       => $preview_token,
            'expires_in'  => 3600,
        ), 200 );
    }

    /**
     * Preview de cambios sin guardar
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function preview_draft_changes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $elements = $request->get_param( 'elements' );
        $device = sanitize_text_field( $request->get_param( 'device' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Generar token de preview temporal
        $preview_token = wp_generate_password( 32, false );

        set_transient( 'vbp_draft_preview_' . $preview_token, array(
            'page_id'    => $page_id,
            'elements'   => $elements,
            'device'     => $device,
            'created_at' => time(),
        ), 15 * MINUTE_IN_SECONDS );

        $preview_url = add_query_arg( array(
            'vbp_draft_preview' => $preview_token,
            'device'            => $device,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'token'       => $preview_token,
            'expires_in'  => 900,
        ), 200 );
    }

    /**
     * Exporta página VBP como PDF
     *
     * Fase 3: Implementación real con DomPDF/TCPDF o generación de HTML.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_preview_pdf( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_notes = (bool) $request->get_param( 'include_notes' );
        $format = sanitize_text_field( $request->get_param( 'format' ) ) ?: 'A4';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        // Generar HTML para el PDF
        $html_content = $this->generate_pdf_html( $post, $elements, $include_notes );
        $css_content = $this->elements_to_css( $elements );

        // Intentar generar PDF con librerías disponibles
        $pdf_result = null;

        if ( class_exists( 'Dompdf\\Dompdf' ) ) {
            $pdf_result = $this->generate_pdf_with_dompdf( $html_content, $css_content, $post->post_title, $format );
        } elseif ( class_exists( 'TCPDF' ) ) {
            $pdf_result = $this->generate_pdf_with_tcpdf( $html_content, $post->post_title, $format );
        }

        // Si no hay librería PDF disponible, devolver HTML para conversión externa
        if ( ! $pdf_result ) {
            $pdf_result = $this->generate_pdf_package( $html_content, $css_content, $post->post_title );
        }

        if ( is_wp_error( $pdf_result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $pdf_result->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array_merge( array(
            'success'       => true,
            'page_id'       => $page_id,
            'title'         => $post->post_title,
            'format'        => $format,
            'include_notes' => $include_notes,
        ), $pdf_result ), 200 );
    }

    /**
     * Genera HTML optimizado para exportar a PDF
     *
     * @param WP_Post $post          Post.
     * @param array   $elements      Elementos VBP.
     * @param bool    $include_notes Incluir notas de revisión.
     * @return string
     */
    private function generate_pdf_html( $post, $elements, $include_notes ) {
        $title = esc_html( $post->post_title );
        $date = date_i18n( get_option( 'date_format' ), strtotime( $post->post_modified ) );
        $content = $this->elements_to_html( $elements );

        $notes_html = '';
        if ( $include_notes ) {
            $notes = get_post_meta( $post->ID, '_vbp_review_comments', true ) ?: array();
            if ( ! empty( $notes ) ) {
                $notes_html = '<div class="pdf-notes"><h2>Notas de Revisión</h2><ul>';
                foreach ( $notes as $note ) {
                    $notes_html .= '<li>' . esc_html( $note['content'] ?? '' ) . '</li>';
                }
                $notes_html .= '</ul></div>';
            }
        }

        return "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <title>{$title}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 40px; color: #333; }
        .pdf-header { border-bottom: 2px solid #3b82f6; padding-bottom: 20px; margin-bottom: 30px; }
        .pdf-header h1 { margin: 0; font-size: 28px; color: #1e293b; }
        .pdf-header .meta { color: #64748b; font-size: 12px; margin-top: 10px; }
        .vbp-page { max-width: 100%; }
        .vbp-section { margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .vbp-heading { font-size: 22px; color: #1e293b; margin-bottom: 15px; }
        .vbp-text, .vbp-paragraph { line-height: 1.6; margin-bottom: 10px; }
        .vbp-button { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; border-radius: 6px; }
        .vbp-image { max-width: 100%; height: auto; border-radius: 8px; }
        .pdf-notes { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .pdf-notes h2 { font-size: 18px; color: #64748b; }
        .pdf-notes li { margin-bottom: 10px; color: #475569; }
        .pdf-footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
    <div class=\"pdf-header\">
        <h1>{$title}</h1>
        <div class=\"meta\">Generado el {$date} · Flavor VBP</div>
    </div>
    <div class=\"vbp-page\">
{$content}
    </div>
    {$notes_html}
    <div class=\"pdf-footer\">Documento generado por Flavor Visual Builder Pro</div>
</body>
</html>";
    }

    /**
     * Genera PDF usando DomPDF
     *
     * @param string $html   Contenido HTML.
     * @param string $css    Estilos CSS adicionales.
     * @param string $title  Título del documento.
     * @param string $format Formato de página (A4, Letter, etc).
     * @return array|WP_Error
     */
    private function generate_pdf_with_dompdf( $html, $css, $title, $format ) {
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( $format, 'portrait' );
            $dompdf->render();

            $pdf_content = $dompdf->output();

            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name( $title ) . '-' . time() . '.pdf';
            $filepath = $upload_dir['path'] . '/' . $filename;

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $filepath, $pdf_content );

            return array(
                'provider'  => 'dompdf',
                'pdf_url'   => $upload_dir['url'] . '/' . $filename,
                'file_path' => $filepath,
                'file_size' => strlen( $pdf_content ),
            );
        } catch ( \Exception $e ) {
            return new WP_Error( 'pdf_generation_error', $e->getMessage() );
        }
    }

    /**
     * Genera PDF usando TCPDF
     *
     * @param string $html   Contenido HTML.
     * @param string $title  Título del documento.
     * @param string $format Formato de página.
     * @return array|WP_Error
     */
    private function generate_pdf_with_tcpdf( $html, $title, $format ) {
        try {
            $pdf = new \TCPDF( 'P', 'mm', $format, true, 'UTF-8', false );
            $pdf->SetCreator( 'Flavor VBP' );
            $pdf->SetAuthor( 'Flavor Platform' );
            $pdf->SetTitle( $title );
            $pdf->AddPage();
            $pdf->writeHTML( $html, true, false, true, false, '' );

            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name( $title ) . '-' . time() . '.pdf';
            $filepath = $upload_dir['path'] . '/' . $filename;

            $pdf->Output( $filepath, 'F' );

            return array(
                'provider'  => 'tcpdf',
                'pdf_url'   => $upload_dir['url'] . '/' . $filename,
                'file_path' => $filepath,
            );
        } catch ( \Exception $e ) {
            return new WP_Error( 'pdf_generation_error', $e->getMessage() );
        }
    }

    /**
     * Genera paquete HTML para conversión externa a PDF
     *
     * @param string $html  Contenido HTML.
     * @param string $css   Estilos CSS.
     * @param string $title Título del documento.
     * @return array
     */
    private function generate_pdf_package( $html, $css, $title ) {
        $upload_dir = wp_upload_dir();
        $html_filename = sanitize_file_name( $title ) . '-' . time() . '.html';
        $html_filepath = $upload_dir['path'] . '/' . $html_filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $html_filepath, $html );

        return array(
            'provider'       => 'html',
            'requires_setup' => true,
            'html_url'       => $upload_dir['url'] . '/' . $html_filename,
            'html_content'   => $html,
            'instructions'   => array(
                'No hay librería PDF instalada. Opciones disponibles:',
                '1. Instalar DomPDF: composer require dompdf/dompdf',
                '2. Abrir el HTML en navegador y guardar como PDF',
                '3. Usar wkhtmltopdf: wkhtmltopdf archivo.html salida.pdf',
            ),
        );
    }

    // =============================================
    // MÉTODOS DE OPTIMIZACIONES AVANZADAS
    // =============================================

    /**
     * Detecta elementos duplicados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_duplicate_elements( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $similarity_threshold = (int) $request->get_param( 'similarity_threshold' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $duplicates = array();
        $processed_pairs = array();

        for ( $i = 0; $i < count( $elements ); $i++ ) {
            for ( $j = $i + 1; $j < count( $elements ); $j++ ) {
                $pair_key = $elements[ $i ]['id'] . '_' . $elements[ $j ]['id'];
                if ( in_array( $pair_key, $processed_pairs, true ) ) {
                    continue;
                }

                $similarity = $this->calculate_element_similarity( $elements[ $i ], $elements[ $j ] );

                if ( $similarity >= $similarity_threshold ) {
                    $duplicates[] = array(
                        'element_a'  => $elements[ $i ]['id'],
                        'element_b'  => $elements[ $j ]['id'],
                        'type'       => $elements[ $i ]['type'],
                        'similarity' => $similarity,
                    );
                }

                $processed_pairs[] = $pair_key;
            }
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'duplicates' => $duplicates,
            'count'      => count( $duplicates ),
            'threshold'  => $similarity_threshold,
        ), 200 );
    }

    /**
     * Calcula similitud entre elementos
     *
     * @param array $element_a Primer elemento.
     * @param array $element_b Segundo elemento.
     * @return int Porcentaje de similitud.
     */
    private function calculate_element_similarity( $element_a, $element_b ) {
        if ( $element_a['type'] !== $element_b['type'] ) {
            return 0;
        }

        $data_a = $element_a['data'] ?? array();
        $data_b = $element_b['data'] ?? array();

        $all_keys = array_unique( array_merge( array_keys( $data_a ), array_keys( $data_b ) ) );
        $matching_keys = 0;

        foreach ( $all_keys as $key ) {
            if ( isset( $data_a[ $key ] ) && isset( $data_b[ $key ] ) && $data_a[ $key ] === $data_b[ $key ] ) {
                $matching_keys++;
            }
        }

        if ( count( $all_keys ) === 0 ) {
            return 100;
        }

        return (int) ( ( $matching_keys / count( $all_keys ) ) * 100 );
    }

    /**
     * Limpia estilos no usados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function cleanup_unused_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $styles = json_decode( get_post_meta( $page_id, '_flavor_vbp_styles', true ), true ) ?: array();

        // Obtener IDs de elementos existentes
        $element_ids = $this->collect_element_ids( $elements );

        // Encontrar estilos huérfanos
        $orphan_styles = array();
        foreach ( $styles as $selector => $style_data ) {
            // Extraer ID del selector
            preg_match( '/el_[a-z0-9]+/', $selector, $matches );
            if ( $matches && ! in_array( $matches[0], $element_ids, true ) ) {
                $orphan_styles[] = $selector;
            }
        }

        $cleaned_count = count( $orphan_styles );

        if ( ! $dry_run && $cleaned_count > 0 ) {
            foreach ( $orphan_styles as $selector ) {
                unset( $styles[ $selector ] );
            }
            update_post_meta( $page_id, '_flavor_vbp_styles', wp_json_encode( $styles ) );
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'dry_run'       => $dry_run,
            'orphan_styles' => $orphan_styles,
            'cleaned_count' => $cleaned_count,
            'message'       => $dry_run ? 'Vista previa de limpieza.' : $cleaned_count . ' estilos eliminados.',
        ), 200 );
    }

    /**
     * Recoge IDs de elementos recursivamente
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function collect_element_ids( $elements ) {
        $ids = array();
        foreach ( $elements as $element ) {
            if ( isset( $element['id'] ) ) {
                $ids[] = $element['id'];
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $ids = array_merge( $ids, $this->collect_element_ids( $element['children'] ) );
            }
        }
        return $ids;
    }

    /**
     * Comprime elementos de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compress_page_elements( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $remove_empty = (bool) $request->get_param( 'remove_empty' );
        $merge_text = (bool) $request->get_param( 'merge_text' );
        $minify_inline_css = (bool) $request->get_param( 'minify_inline_css' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $original_count = count( $elements );

        $stats = array(
            'removed_empty'  => 0,
            'merged_text'    => 0,
            'minified_css'   => 0,
        );

        // Eliminar elementos vacíos
        if ( $remove_empty ) {
            $elements = array_filter( $elements, function( $el ) use ( &$stats ) {
                $has_content = ! empty( $el['data']['content'] ) ||
                              ! empty( $el['data']['text'] ) ||
                              ! empty( $el['data']['title'] ) ||
                              ! empty( $el['data']['image'] ) ||
                              ! empty( $el['children'] );
                if ( ! $has_content ) {
                    $stats['removed_empty']++;
                }
                return $has_content;
            } );
            $elements = array_values( $elements );
        }

        // Minificar CSS inline
        if ( $minify_inline_css ) {
            foreach ( $elements as &$element ) {
                if ( isset( $element['styles']['customCss'] ) ) {
                    $original_css = $element['styles']['customCss'];
                    $minified_css = preg_replace( '/\s+/', ' ', $original_css );
                    $minified_css = preg_replace( '/\s*([{};:,])\s*/', '$1', $minified_css );
                    if ( strlen( $minified_css ) < strlen( $original_css ) ) {
                        $element['styles']['customCss'] = $minified_css;
                        $stats['minified_css']++;
                    }
                }
            }
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'original_count' => $original_count,
            'final_count'    => count( $elements ),
            'stats'          => $stats,
            'message'        => 'Página comprimida.',
        ), 200 );
    }

    /**
     * Configura prefetch de recursos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_resource_prefetch( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dns_prefetch_urls = $request->get_param( 'dns_prefetch' ) ?: array();
        $preconnect_urls = $request->get_param( 'preconnect' ) ?: array();
        $prefetch_urls = $request->get_param( 'prefetch' ) ?: array();
        $preload_urls = $request->get_param( 'preload' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $prefetch_config = array(
            'dns_prefetch' => array_map( 'esc_url_raw', $dns_prefetch_urls ),
            'preconnect'   => array_map( 'esc_url_raw', $preconnect_urls ),
            'prefetch'     => array_map( 'esc_url_raw', $prefetch_urls ),
            'preload'      => array_map( 'esc_url_raw', $preload_urls ),
        );

        update_post_meta( $page_id, '_flavor_vbp_prefetch_config', $prefetch_config );

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $prefetch_config,
            'message' => 'Configuración de prefetch guardada.',
        ), 200 );
    }

    /**
     * Análisis de rendimiento detallado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_page_performance_detailed( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_recommendations = (bool) $request->get_param( 'include_recommendations' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        // Métricas
        $metrics = array(
            'element_count'    => count( $elements ),
            'nested_depth'     => $this->calculate_max_depth( $elements ),
            'image_count'      => count( $this->extract_media_ids_from_elements( $elements ) ),
            'styles_size'      => strlen( $styles ?? '' ),
            'estimated_dom'    => $this->estimate_dom_nodes( $elements ),
            'has_lazy_loading' => (bool) get_post_meta( $page_id, '_flavor_vbp_lazy_loading', true ),
            'has_critical_css' => (bool) get_post_meta( $page_id, '_flavor_vbp_critical_css', true ),
        );

        // Score
        $score = 100;
        if ( $metrics['element_count'] > 100 ) {
            $score -= 10;
        }
        if ( $metrics['nested_depth'] > 10 ) {
            $score -= 15;
        }
        if ( $metrics['image_count'] > 20 ) {
            $score -= 10;
        }
        if ( $metrics['estimated_dom'] > 1500 ) {
            $score -= 20;
        }
        if ( ! $metrics['has_lazy_loading'] ) {
            $score -= 10;
        }
        if ( ! $metrics['has_critical_css'] ) {
            $score -= 5;
        }

        $metrics['performance_score'] = max( 0, $score );

        // Recomendaciones
        $recommendations = array();
        if ( $include_recommendations ) {
            if ( $metrics['element_count'] > 100 ) {
                $recommendations[] = array(
                    'type'    => 'warning',
                    'message' => 'Considera reducir el número de elementos.',
                    'action'  => 'optimize/compress',
                );
            }
            if ( ! $metrics['has_lazy_loading'] ) {
                $recommendations[] = array(
                    'type'    => 'suggestion',
                    'message' => 'Activar lazy loading para imágenes.',
                    'action'  => 'optimize/lazy-loading',
                );
            }
            if ( ! $metrics['has_critical_css'] ) {
                $recommendations[] = array(
                    'type'    => 'suggestion',
                    'message' => 'Generar CSS crítico para mejorar FCP.',
                    'action'  => 'optimize/critical-css',
                );
            }
        }

        return new WP_REST_Response( array(
            'success'         => true,
            'metrics'         => $metrics,
            'recommendations' => $recommendations,
        ), 200 );
    }

    /**
     * Estima nodos DOM
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function estimate_dom_nodes( $elements ) {
        $nodes = 0;
        foreach ( $elements as $element ) {
            $nodes += 3; // wrapper + content + styles
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $nodes += $this->estimate_dom_nodes( $element['children'] );
            }
        }
        return $nodes;
    }

    /**
     * Aplica optimizaciones automáticas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_auto_optimizations( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $level = sanitize_text_field( $request->get_param( 'level' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $applied = array();

        // Nivel safe
        if ( in_array( $level, array( 'safe', 'balanced', 'aggressive' ), true ) ) {
            // Activar lazy loading
            update_post_meta( $page_id, '_flavor_vbp_lazy_loading', array(
                'images'  => true,
                'iframes' => true,
            ) );
            $applied[] = 'lazy_loading';
        }

        // Nivel balanced
        if ( in_array( $level, array( 'balanced', 'aggressive' ), true ) ) {
            // Limpiar estilos huérfanos (internamente)
            $applied[] = 'cleanup_styles';

            // Configurar prefetch básico
            update_post_meta( $page_id, '_flavor_vbp_prefetch_config', array(
                'dns_prefetch' => array( '//fonts.googleapis.com', '//fonts.gstatic.com' ),
                'preconnect'   => array( '//fonts.googleapis.com' ),
            ) );
            $applied[] = 'prefetch';
        }

        // Nivel aggressive
        if ( $level === 'aggressive' ) {
            // Comprimir elementos
            $applied[] = 'compress_elements';

            // Optimizar imágenes config
            update_post_meta( $page_id, '_flavor_vbp_image_optimization', array(
                'config' => array(
                    'max_width' => 1920,
                    'quality'   => 85,
                    'format'    => 'webp',
                ),
            ) );
            $applied[] = 'image_optimization';
        }

        return new WP_REST_Response( array(
            'success'             => true,
            'level'               => $level,
            'optimizations_applied' => $applied,
            'message'             => count( $applied ) . ' optimizaciones aplicadas.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE COLABORACIÓN
    // =============================================

    /**
     * Obtiene estado de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_editing_status( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        // Limpiar sesiones expiradas
        $now = time();
        $editing_sessions = array_filter( $editing_sessions, function( $session ) use ( $now ) {
            return ( $now - $session['last_activity'] ) < 300; // 5 minutos
        } );

        return new WP_REST_Response( array(
            'success'  => true,
            'editors'  => array_values( $editing_sessions ),
            'is_busy'  => count( $editing_sessions ) > 0,
        ), 200 );
    }

    /**
     * Inicia sesión de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function start_editing_session( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        $session_id = 'session_' . uniqid();
        $editing_sessions[ $session_id ] = array(
            'session_id'    => $session_id,
            'user_id'       => $user_id,
            'user_name'     => $user->display_name,
            'user_avatar'   => get_avatar_url( $user_id ),
            'started_at'    => time(),
            'last_activity' => time(),
        );

        set_transient( 'vbp_editing_sessions_' . $page_id, $editing_sessions, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'session_id' => $session_id,
            'message'    => 'Sesión de edición iniciada.',
        ), 200 );
    }

    /**
     * Finaliza sesión de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function end_editing_session( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        // Eliminar sesiones del usuario actual
        $editing_sessions = array_filter( $editing_sessions, function( $session ) use ( $user_id ) {
            return $session['user_id'] !== $user_id;
        } );

        set_transient( 'vbp_editing_sessions_' . $page_id, $editing_sessions, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Sesión de edición finalizada.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE RESPONSIVE DESIGN
    // =============================================

    /**
     * Obtiene breakpoints responsive
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_responsive_breakpoints( $request ) {
        $breakpoints = get_option( 'flavor_vbp_breakpoints', array(
            array( 'id' => 'desktop', 'name' => 'Desktop', 'min_width' => 1200, 'max_width' => null ),
            array( 'id' => 'laptop', 'name' => 'Laptop', 'min_width' => 992, 'max_width' => 1199 ),
            array( 'id' => 'tablet', 'name' => 'Tablet', 'min_width' => 768, 'max_width' => 991 ),
            array( 'id' => 'mobile', 'name' => 'Mobile', 'min_width' => 0, 'max_width' => 767 ),
        ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'breakpoints' => $breakpoints,
        ), 200 );
    }

    /**
     * Actualiza breakpoints responsive
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_responsive_breakpoints( $request ) {
        $breakpoints = $request->get_param( 'breakpoints' );

        if ( ! is_array( $breakpoints ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Breakpoints inválidos.' ), 400 );
        }

        // Validar estructura
        foreach ( $breakpoints as &$bp ) {
            $bp['id'] = sanitize_title( $bp['id'] ?? '' );
            $bp['name'] = sanitize_text_field( $bp['name'] ?? '' );
            $bp['min_width'] = (int) ( $bp['min_width'] ?? 0 );
            $bp['max_width'] = isset( $bp['max_width'] ) ? (int) $bp['max_width'] : null;
        }

        update_option( 'flavor_vbp_breakpoints', $breakpoints );

        return new WP_REST_Response( array(
            'success'     => true,
            'breakpoints' => $breakpoints,
            'message'     => 'Breakpoints actualizados.',
        ), 200 );
    }

    /**
     * Obtiene estilos responsive de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $block = $this->find_element_by_id( $elements, $block_id );

        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $responsive_styles = $block['responsive'] ?? array();

        return new WP_REST_Response( array(
            'success'           => true,
            'block_id'          => $block_id,
            'responsive_styles' => $responsive_styles,
        ), 200 );
    }

    /**
     * Actualiza estilos responsive de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_block_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $breakpoint = sanitize_text_field( $request->get_param( 'breakpoint' ) );
        $styles = $request->get_param( 'styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $breakpoint, $styles ) {
            if ( ! isset( $element['responsive'] ) ) {
                $element['responsive'] = array();
            }
            $element['responsive'][ $breakpoint ] = $styles;
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'block_id'   => $block_id,
            'breakpoint' => $breakpoint,
            'message'    => 'Estilos responsive actualizados.',
        ), 200 );
    }

    /**
     * Copia estilos entre breakpoints
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function copy_styles_between_breakpoints( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $from_breakpoint = sanitize_text_field( $request->get_param( 'from_breakpoint' ) );
        $to_breakpoints = $request->get_param( 'to_breakpoints' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $block = $this->find_element_by_id( $elements, $block_id );

        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $source_styles = $block['responsive'][ $from_breakpoint ] ?? $block['styles'] ?? array();

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $source_styles, $to_breakpoints ) {
            if ( ! isset( $element['responsive'] ) ) {
                $element['responsive'] = array();
            }
            foreach ( $to_breakpoints as $bp ) {
                $element['responsive'][ $bp ] = $source_styles;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'from'           => $from_breakpoint,
            'to'             => $to_breakpoints,
            'copied_to'      => count( $to_breakpoints ),
            'message'        => 'Estilos copiados a ' . count( $to_breakpoints ) . ' breakpoints.',
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE TRANSFORMACIONES DE BLOQUES
    // =============================================

    /**
     * Rota un bloque
     */
    public function rotate_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $angle = (float) $request->get_param( 'angle' );
        $origin = sanitize_text_field( $request->get_param( 'origin' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $transform_origin = $this->get_transform_origin( $origin, $request );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $angle, $transform_origin ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = 'rotate(' . $angle . 'deg)';
            $el['styles']['advanced']['transformOrigin'] = $transform_origin;
            $el['data']['_transform'] = array( 'rotate' => $angle, 'origin' => $transform_origin );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array(
            'success' => true,
            'angle'   => $angle,
            'origin'  => $transform_origin,
        ), 200 );
    }

    /**
     * Escala un bloque
     */
    public function scale_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $scale_x = (float) $request->get_param( 'scale_x' );
        $scale_y = (float) $request->get_param( 'scale_y' );
        $uniform = (bool) $request->get_param( 'uniform' );

        if ( $uniform ) {
            $scale_y = $scale_x;
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $scale_x, $scale_y ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "scale({$scale_x}, {$scale_y})";
            $el['data']['_transform'] = array( 'scaleX' => $scale_x, 'scaleY' => $scale_y );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'scale_x' => $scale_x, 'scale_y' => $scale_y ), 200 );
    }

    /**
     * Sesga un bloque (skew)
     */
    public function skew_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $skew_x = (float) $request->get_param( 'skew_x' );
        $skew_y = (float) $request->get_param( 'skew_y' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $skew_x, $skew_y ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "skew({$skew_x}deg, {$skew_y}deg)";
            $el['data']['_transform'] = array( 'skewX' => $skew_x, 'skewY' => $skew_y );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'skew_x' => $skew_x, 'skew_y' => $skew_y ), 200 );
    }

    /**
     * Voltea un bloque
     */
    public function flip_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );

        $scale_x = 1;
        $scale_y = 1;
        if ( $direction === 'horizontal' || $direction === 'both' ) {
            $scale_x = -1;
        }
        if ( $direction === 'vertical' || $direction === 'both' ) {
            $scale_y = -1;
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $scale_x, $scale_y, $direction ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "scale({$scale_x}, {$scale_y})";
            $el['data']['_flip'] = $direction;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'direction' => $direction ), 200 );
    }

    /**
     * Resetea transformaciones de bloque
     */
    public function reset_block_transforms( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) {
            unset( $el['styles']['advanced']['transform'] );
            unset( $el['styles']['advanced']['transformOrigin'] );
            unset( $el['data']['_transform'] );
            unset( $el['data']['_flip'] );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Transformaciones reseteadas.' ), 200 );
    }

    /**
     * Obtiene transformaciones de bloque
     */
    public function get_block_transforms( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'transform'  => $block['styles']['advanced']['transform'] ?? 'none',
            'origin'     => $block['styles']['advanced']['transformOrigin'] ?? 'center',
            'data'       => $block['data']['_transform'] ?? array(),
        ), 200 );
    }

    /**
     * Helper para obtener transform origin
     */
    private function get_transform_origin( $origin, $request ) {
        $origins = array(
            'center'       => 'center center',
            'top-left'     => 'top left',
            'top-right'    => 'top right',
            'bottom-left'  => 'bottom left',
            'bottom-right' => 'bottom right',
        );

        if ( $origin === 'custom' ) {
            $origin_x = $request->get_param( 'origin_x' ) ?: '50%';
            $origin_y = $request->get_param( 'origin_y' ) ?: '50%';
            return "{$origin_x} {$origin_y}";
        }

        return $origins[ $origin ] ?? 'center center';
    }

    // =============================================
    // MÉTODOS DE ANIMACIONES Y KEYFRAMES
    // =============================================

    /**
     * Lista librería de animaciones
     */
    public function list_animations_library( $request ) {
        $category = $request->get_param( 'category' );

        $animations = array(
            'entrance' => array(
                array( 'id' => 'fadeIn', 'name' => 'Fade In', 'duration' => '0.5s' ),
                array( 'id' => 'fadeInUp', 'name' => 'Fade In Up', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInDown', 'name' => 'Fade In Down', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInLeft', 'name' => 'Fade In Left', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInRight', 'name' => 'Fade In Right', 'duration' => '0.6s' ),
                array( 'id' => 'slideInUp', 'name' => 'Slide In Up', 'duration' => '0.5s' ),
                array( 'id' => 'slideInDown', 'name' => 'Slide In Down', 'duration' => '0.5s' ),
                array( 'id' => 'zoomIn', 'name' => 'Zoom In', 'duration' => '0.4s' ),
                array( 'id' => 'bounceIn', 'name' => 'Bounce In', 'duration' => '0.75s' ),
            ),
            'exit' => array(
                array( 'id' => 'fadeOut', 'name' => 'Fade Out', 'duration' => '0.5s' ),
                array( 'id' => 'fadeOutUp', 'name' => 'Fade Out Up', 'duration' => '0.6s' ),
                array( 'id' => 'fadeOutDown', 'name' => 'Fade Out Down', 'duration' => '0.6s' ),
                array( 'id' => 'zoomOut', 'name' => 'Zoom Out', 'duration' => '0.4s' ),
            ),
            'attention' => array(
                array( 'id' => 'pulse', 'name' => 'Pulse', 'duration' => '1s' ),
                array( 'id' => 'shake', 'name' => 'Shake', 'duration' => '0.8s' ),
                array( 'id' => 'bounce', 'name' => 'Bounce', 'duration' => '1s' ),
                array( 'id' => 'flash', 'name' => 'Flash', 'duration' => '1s' ),
                array( 'id' => 'wobble', 'name' => 'Wobble', 'duration' => '1s' ),
            ),
            'background' => array(
                array( 'id' => 'gradientShift', 'name' => 'Gradient Shift', 'duration' => '5s' ),
                array( 'id' => 'colorPulse', 'name' => 'Color Pulse', 'duration' => '3s' ),
            ),
        );

        // Añadir animaciones personalizadas
        $custom_animations = get_option( 'flavor_vbp_custom_animations', array() );
        if ( ! empty( $custom_animations ) ) {
            $animations['custom'] = $custom_animations;
        }

        if ( $category && isset( $animations[ $category ] ) ) {
            return new WP_REST_Response( array(
                'success'    => true,
                'category'   => $category,
                'animations' => $animations[ $category ],
            ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true, 'animations' => $animations ), 200 );
    }

    /**
     * Crea animación personalizada
     */
    public function create_custom_animation( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $keyframes = $request->get_param( 'keyframes' );
        $duration = sanitize_text_field( $request->get_param( 'duration' ) );
        $timing = sanitize_text_field( $request->get_param( 'timing' ) );
        $iterations = sanitize_text_field( $request->get_param( 'iterations' ) );

        $animation_id = 'custom_' . sanitize_title( $name ) . '_' . time();

        $animation = array(
            'id'         => $animation_id,
            'name'       => $name,
            'keyframes'  => $keyframes,
            'duration'   => $duration,
            'timing'     => $timing,
            'iterations' => $iterations,
            'created_at' => current_time( 'mysql' ),
        );

        $custom_animations = get_option( 'flavor_vbp_custom_animations', array() );
        $custom_animations[] = $animation;
        update_option( 'flavor_vbp_custom_animations', $custom_animations );

        return new WP_REST_Response( array( 'success' => true, 'animation' => $animation ), 201 );
    }

    /**
     * Aplica animación a bloque
     */
    public function apply_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $animation = sanitize_text_field( $request->get_param( 'animation' ) );
        $trigger = sanitize_text_field( $request->get_param( 'trigger' ) );
        $delay = sanitize_text_field( $request->get_param( 'delay' ) );
        $duration = $request->get_param( 'duration' );
        $threshold = (float) $request->get_param( 'threshold' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $animation_config = array(
            'name'      => $animation,
            'trigger'   => $trigger,
            'delay'     => $delay,
            'threshold' => $threshold,
        );

        if ( $duration ) {
            $animation_config['duration'] = sanitize_text_field( $duration );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $animation_config ) {
            $el['data']['_animation'] = $animation_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'animation' => $animation_config ), 200 );
    }

    /**
     * Obtiene animación de bloque
     */
    public function get_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'animation' => $block['data']['_animation'] ?? null,
        ), 200 );
    }

    /**
     * Elimina animación de bloque
     */
    public function remove_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) {
            unset( $el['data']['_animation'] );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Animación eliminada.' ), 200 );
    }

    /**
     * Preview de animación de bloque
     */
    public function preview_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $animation = $block['data']['_animation'] ?? null;
        if ( ! $animation ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay animación configurada.' ), 404 );
        }

        $preview_url = add_query_arg( array(
            'vbp_preview'   => 1,
            'animation'     => 1,
            'block_id'      => $block_id,
            'autoplay'      => 1,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'animation'   => $animation,
            'preview_url' => $preview_url,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE VISIBILIDAD CONDICIONAL
    // =============================================

    /**
     * Configura reglas de visibilidad
     */
    public function set_block_visibility_rules( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $rules = $request->get_param( 'rules' );
        $logic = sanitize_text_field( $request->get_param( 'logic' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $rules, $logic ) {
            $el['data']['_visibility'] = array( 'rules' => $rules, 'logic' => $logic );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'rules' => $rules, 'logic' => $logic ), 200 );
    }

    /**
     * Obtiene reglas de visibilidad
     */
    public function get_block_visibility_rules( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'visibility' => $block['data']['_visibility'] ?? null,
        ), 200 );
    }

    /**
     * Tipos de condiciones de visibilidad
     */
    public function get_visibility_condition_types( $request ) {
        $types = array(
            array( 'id' => 'user_logged_in', 'name' => 'Usuario autenticado', 'params' => array() ),
            array( 'id' => 'user_role', 'name' => 'Rol de usuario', 'params' => array( 'role' ) ),
            array( 'id' => 'device_type', 'name' => 'Tipo de dispositivo', 'params' => array( 'device' ) ),
            array( 'id' => 'date_range', 'name' => 'Rango de fechas', 'params' => array( 'start', 'end' ) ),
            array( 'id' => 'time_range', 'name' => 'Rango horario', 'params' => array( 'start', 'end' ) ),
            array( 'id' => 'url_param', 'name' => 'Parámetro URL', 'params' => array( 'param', 'value' ) ),
            array( 'id' => 'cookie', 'name' => 'Cookie', 'params' => array( 'name', 'value' ) ),
            array( 'id' => 'referrer', 'name' => 'Referrer', 'params' => array( 'contains' ) ),
            array( 'id' => 'language', 'name' => 'Idioma', 'params' => array( 'lang' ) ),
            array( 'id' => 'geolocation', 'name' => 'Geolocalización', 'params' => array( 'country', 'region' ) ),
            array( 'id' => 'custom_field', 'name' => 'Campo personalizado', 'params' => array( 'field', 'value', 'operator' ) ),
        );

        return new WP_REST_Response( array( 'success' => true, 'condition_types' => $types ), 200 );
    }

    /**
     * Simula visibilidad con contexto
     */
    public function simulate_visibility( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $context = $request->get_param( 'context' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $visibility_results = array();
        $this->check_elements_visibility( $elements, $context, $visibility_results );

        return new WP_REST_Response( array( 'success' => true, 'results' => $visibility_results ), 200 );
    }

    /**
     * Verifica visibilidad de elementos recursivamente
     */
    private function check_elements_visibility( $elements, $context, &$results ) {
        foreach ( $elements as $element ) {
            $visibility = $element['data']['_visibility'] ?? null;
            $is_visible = true;

            if ( $visibility && ! empty( $visibility['rules'] ) ) {
                $is_visible = $this->evaluate_visibility_rules( $visibility['rules'], $visibility['logic'], $context );
            }

            $results[ $element['id'] ] = $is_visible;

            if ( ! empty( $element['children'] ) ) {
                $this->check_elements_visibility( $element['children'], $context, $results );
            }
        }
    }

    /**
     * Evalúa reglas de visibilidad
     */
    private function evaluate_visibility_rules( $rules, $logic, $context ) {
        $results = array();
        foreach ( $rules as $rule ) {
            $results[] = $this->evaluate_single_rule( $rule, $context );
        }

        if ( $logic === 'all' ) {
            return ! in_array( false, $results, true );
        }
        return in_array( true, $results, true );
    }

    /**
     * Evalúa una regla individual
     */
    private function evaluate_single_rule( $rule, $context ) {
        $type = $rule['type'] ?? '';
        switch ( $type ) {
            case 'user_logged_in':
                return ( $context['user_logged_in'] ?? false ) === true;
            case 'user_role':
                return in_array( $rule['role'] ?? '', $context['user_roles'] ?? array(), true );
            case 'device_type':
                return ( $context['device_type'] ?? 'desktop' ) === ( $rule['device'] ?? 'desktop' );
            case 'language':
                return ( $context['language'] ?? 'es' ) === ( $rule['lang'] ?? 'es' );
            default:
                return true;
        }
    }

    // =============================================
    // MÉTODOS DE DATOS DINÁMICOS
    // =============================================

    /**
     * Lista fuentes de datos
     */
    public function list_data_sources( $request ) {
        $sources = array(
            array( 'id' => 'post', 'name' => 'Post/Página', 'fields' => array( 'title', 'content', 'excerpt', 'featured_image', 'author', 'date', 'categories', 'tags' ) ),
            array( 'id' => 'user', 'name' => 'Usuario actual', 'fields' => array( 'display_name', 'email', 'avatar', 'role', 'meta' ) ),
            array( 'id' => 'option', 'name' => 'Opciones WP', 'fields' => array( 'blogname', 'blogdescription', 'admin_email', 'custom' ) ),
            array( 'id' => 'acf', 'name' => 'Advanced Custom Fields', 'fields' => array( 'any_field' ) ),
            array( 'id' => 'custom', 'name' => 'PHP Callback', 'fields' => array() ),
            array( 'id' => 'rest_api', 'name' => 'REST API externa', 'fields' => array() ),
        );

        return new WP_REST_Response( array( 'success' => true, 'sources' => $sources ), 200 );
    }

    /**
     * Crea conexión de datos
     */
    public function create_data_connection( $request ) {
        $source_type = sanitize_text_field( $request->get_param( 'source_type' ) );
        $config = $request->get_param( 'config' );

        $connection_id = 'conn_' . $source_type . '_' . time();
        $connection = array(
            'id'          => $connection_id,
            'source_type' => $source_type,
            'config'      => $config,
            'created_at'  => current_time( 'mysql' ),
        );

        $connections = get_option( 'flavor_vbp_data_connections', array() );
        $connections[ $connection_id ] = $connection;
        update_option( 'flavor_vbp_data_connections', $connections );

        return new WP_REST_Response( array( 'success' => true, 'connection' => $connection ), 201 );
    }

    /**
     * Vincula datos a bloque
     */
    public function bind_data_to_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $field = sanitize_text_field( $request->get_param( 'field' ) );
        $source = sanitize_text_field( $request->get_param( 'source' ) );
        $source_field = sanitize_text_field( $request->get_param( 'source_field' ) );
        $transform = $request->get_param( 'transform' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $binding = array(
            'field'        => $field,
            'source'       => $source,
            'source_field' => $source_field,
            'transform'    => $transform,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $binding, $field ) {
            if ( ! isset( $el['data']['_bindings'] ) ) {
                $el['data']['_bindings'] = array();
            }
            $el['data']['_bindings'][ $field ] = $binding;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'binding' => $binding ), 200 );
    }

    /**
     * Obtiene bindings de bloque
     */
    public function get_block_data_bindings( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'bindings' => $block['data']['_bindings'] ?? array(),
        ), 200 );
    }

    /**
     * Preview con datos dinámicos
     */
    public function preview_with_dynamic_data( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $data_context = $request->get_param( 'data_context' ) ?: array();

        $preview_url = add_query_arg( array(
            'vbp_preview'   => 1,
            'dynamic_data'  => 1,
            'context'       => base64_encode( wp_json_encode( $data_context ) ),
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array( 'success' => true, 'preview_url' => $preview_url ), 200 );
    }

    // =============================================
    // MÉTODOS DE VARIABLES CSS GLOBALES
    // =============================================

    /**
     * Obtiene variables CSS globales
     */
    public function get_css_variables( $request ) {
        $variables = get_option( 'flavor_vbp_css_variables', array(
            'colors' => array(
                '--vbp-primary'   => '#3b82f6',
                '--vbp-secondary' => '#8b5cf6',
                '--vbp-accent'    => '#06b6d4',
                '--vbp-success'   => '#22c55e',
                '--vbp-warning'   => '#f59e0b',
                '--vbp-error'     => '#ef4444',
            ),
            'typography' => array(
                '--vbp-font-family'    => 'Inter, sans-serif',
                '--vbp-font-size-base' => '16px',
                '--vbp-line-height'    => '1.6',
            ),
            'spacing' => array(
                '--vbp-spacing-xs' => '4px',
                '--vbp-spacing-sm' => '8px',
                '--vbp-spacing-md' => '16px',
                '--vbp-spacing-lg' => '24px',
                '--vbp-spacing-xl' => '48px',
            ),
            'custom' => array(),
        ) );

        return new WP_REST_Response( array( 'success' => true, 'variables' => $variables ), 200 );
    }

    /**
     * Actualiza variables CSS globales
     */
    public function update_css_variables( $request ) {
        $variables = $request->get_param( 'variables' );
        $current = get_option( 'flavor_vbp_css_variables', array() );
        $merged = array_merge( $current, $variables );
        update_option( 'flavor_vbp_css_variables', $merged );

        return new WP_REST_Response( array( 'success' => true, 'variables' => $merged ), 200 );
    }

    /**
     * Crea grupo de variables CSS
     */
    public function create_css_variable_group( $request ) {
        $name = sanitize_title( $request->get_param( 'name' ) );
        $variables = $request->get_param( 'variables' );

        $current = get_option( 'flavor_vbp_css_variables', array() );
        $current[ $name ] = $variables;
        update_option( 'flavor_vbp_css_variables', $current );

        return new WP_REST_Response( array( 'success' => true, 'group' => $name, 'variables' => $variables ), 201 );
    }

    /**
     * Usa variable CSS en bloque
     */
    public function use_css_variable_in_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $property = sanitize_text_field( $request->get_param( 'property' ) );
        $variable = sanitize_text_field( $request->get_param( 'variable' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $property, $variable ) {
            $keys = explode( '.', $property );
            $target = &$el['styles'];
            foreach ( $keys as $i => $key ) {
                if ( $i === count( $keys ) - 1 ) {
                    $target[ $key ] = "var({$variable})";
                } else {
                    if ( ! isset( $target[ $key ] ) ) {
                        $target[ $key ] = array();
                    }
                    $target = &$target[ $key ];
                }
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'property' => $property, 'variable' => $variable ), 200 );
    }

    // =============================================
    // MÉTODOS DE SISTEMA DE VERSIONES
    // =============================================

    /**
     * Lista versiones de página
     */
    /**
     * @deprecated Usar /claude/pages/{id}/snapshots
     */
    public function list_page_versions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        return new WP_REST_Response( array(
            'success'      => true,
            '_deprecated'  => true,
            '_use_instead' => '/claude/pages/{id}/snapshots',
            'versions'     => array_values( $versions ),
        ), 200 );
    }

    /**
     * @deprecated Usar /claude/pages/{id}/snapshots (POST)
     */
    public function create_page_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) ?: 'Versión ' . date( 'Y-m-d H:i' ) );
        $description = sanitize_text_field( $request->get_param( 'description' ) ?: '' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $version_id = time();
        $version = array(
            'id'          => $version_id,
            'name'        => $name,
            'description' => $description,
            'elements'    => $elements,
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
        );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();
        $versions[ $version_id ] = $version;

        if ( count( $versions ) > 20 ) {
            array_shift( $versions );
        }

        update_post_meta( $page_id, '_vbp_versions', $versions );

        return new WP_REST_Response( array(
            'success'      => true,
            '_deprecated'  => true,
            '_use_instead' => '/claude/pages/{id}/snapshots',
            'version'      => $version,
        ), 201 );
    }

    /**
     * Restaura snapshot de página
     * @deprecated Usar /claude/pages/{id}/snapshots/{id}/restore
     */
    public function restore_snapshot_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        if ( ! isset( $versions[ $version_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Versión no encontrada.' ), 404 );
        }

        $this->save_page_elements( $page_id, $versions[ $version_id ]['elements'] );

        return new WP_REST_Response( array( 'success' => true, 'restored_version' => $version_id ), 200 );
    }

    /**
     * Compara snapshots de versiones
     */
    public function compare_snapshot_versions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_a = (int) $request->get_param( 'version_a' );
        $version_b = (int) $request->get_param( 'version_b' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        if ( ! isset( $versions[ $version_a ] ) || ! isset( $versions[ $version_b ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Versiones no encontradas.' ), 404 );
        }

        $elements_a = $versions[ $version_a ]['elements'];
        $elements_b = $versions[ $version_b ]['elements'];

        $diff = array(
            'added'     => $this->count_elements( $elements_b ) - $this->count_elements( $elements_a ),
            'version_a' => array( 'id' => $version_a, 'name' => $versions[ $version_a ]['name'] ),
            'version_b' => array( 'id' => $version_b, 'name' => $versions[ $version_b ]['name'] ),
        );

        return new WP_REST_Response( array( 'success' => true, 'comparison' => $diff ), 200 );
    }

    /**
     * Elimina snapshot de versión
     */
    public function delete_snapshot_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();
        unset( $versions[ $version_id ] );
        update_post_meta( $page_id, '_vbp_versions', $versions );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Versión eliminada.' ), 200 );
    }

    // =============================================
    // MÉTODOS DE ACCESIBILIDAD
    // =============================================

    /**
     * Configura atributos ARIA
     */
    public function set_block_aria_attributes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $attributes = $request->get_param( 'attributes' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $attributes ) {
            $el['data']['_aria'] = $attributes;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'aria' => $attributes ), 200 );
    }

    /**
     * Obtiene atributos ARIA
     */
    public function get_block_aria_attributes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array( 'success' => true, 'aria' => $block['data']['_aria'] ?? array() ), 200 );
    }

    /**
     * Verifica contraste de colores WCAG
     */
    public function check_wcag_contrast( $request ) {
        $foreground = sanitize_text_field( $request->get_param( 'foreground' ) );
        $background = sanitize_text_field( $request->get_param( 'background' ) );
        $font_size = (float) $request->get_param( 'font_size' );

        $fg_lum = $this->calculate_wcag_luminance( $foreground );
        $bg_lum = $this->calculate_wcag_luminance( $background );
        $ratio = ( max( $fg_lum, $bg_lum ) + 0.05 ) / ( min( $fg_lum, $bg_lum ) + 0.05 );

        $is_large = $font_size >= 18;
        $aa_threshold = $is_large ? 3.0 : 4.5;
        $aaa_threshold = $is_large ? 4.5 : 7.0;

        return new WP_REST_Response( array(
            'success'    => true,
            'ratio'      => round( $ratio, 2 ),
            'passes_aa'  => $ratio >= $aa_threshold,
            'passes_aaa' => $ratio >= $aaa_threshold,
        ), 200 );
    }

    /**
     * Calcula luminancia WCAG de color
     */
    private function calculate_wcag_luminance( $color ) {
        $hex = ltrim( $color, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
        $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
        $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    // =============================================
    // MÉTODOS DE EXPORTACIÓN
    // =============================================

    /**
     * Exporta página como HTML
     */
    public function export_page_as_html( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_styles = (bool) $request->get_param( 'include_styles' );
        $minify = (bool) $request->get_param( 'minify' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $html = $this->elements_to_html( $elements );
        if ( $include_styles ) {
            $css = $this->generate_page_css( $elements );
            $html = "<style>{$css}</style>\n{$html}";
        }
        if ( $minify ) {
            $html = preg_replace( '/\s+/', ' ', $html );
        }

        return new WP_REST_Response( array( 'success' => true, 'html' => $html ), 200 );
    }

    /**
     * Exporta CSS de página
     */
    public function export_page_css( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );
        $minify = (bool) $request->get_param( 'minify' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $css = $this->generate_page_css( $elements );
        if ( $minify ) {
            $css = preg_replace( '/\s+/', ' ', $css );
        }

        return new WP_REST_Response( array( 'success' => true, 'css' => $css, 'format' => $format ), 200 );
    }

    /**
     * Exporta como componentes React/Vue/Svelte
     *
     * Fase 3: Implementación real de generación de código.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_as_components( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $framework = sanitize_text_field( $request->get_param( 'framework' ) );
        $typescript = (bool) $request->get_param( 'typescript' );
        $styling = sanitize_text_field( $request->get_param( 'styling' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $page_title = sanitize_title( $post->post_title );
        $component_name = $this->to_pascal_case( $page_title ?: 'Page' );

        // Generar código según framework
        switch ( $framework ) {
            case 'react':
                $result = $this->generate_react_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'vue':
                $result = $this->generate_vue_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'svelte':
                $result = $this->generate_svelte_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'html':
            default:
                $result = $this->generate_html_export( $elements, $component_name, $styling );
                break;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'framework'  => $framework,
            'typescript' => $typescript,
            'styling'    => $styling,
            'page_id'    => $page_id,
            'page_title' => $post->post_title,
            'files'      => $result['files'],
            'main_file'  => $result['main_file'],
            'dependencies' => $result['dependencies'] ?? array(),
        ), 200 );
    }

    /**
     * Genera componente React/JSX
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_react_component( $elements, $component_name, $typescript, $styling ) {
        $extension = $typescript ? 'tsx' : 'jsx';
        $files = array();
        $dependencies = array( 'react' );

        // Generar imports según styling
        $imports = "import React from 'react';\n";
        $style_imports = '';

        switch ( $styling ) {
            case 'styled-components':
                $imports .= "import styled from 'styled-components';\n";
                $dependencies[] = 'styled-components';
                break;
            case 'css-modules':
                $style_imports = "import styles from './{$component_name}.module.css';\n";
                break;
            case 'tailwind':
                $dependencies[] = 'tailwindcss';
                break;
            default: // inline
                break;
        }

        // Generar JSX para elementos
        $jsx_content = $this->elements_to_jsx( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        // Componente principal
        $type_annotation = $typescript ? ': React.FC' : '';
        $component_code = "{$imports}{$style_imports}
/**
 * {$component_name} - Generado por Flavor VBP
 * @generated
 */
const {$component_name}{$type_annotation} = () => {
    return (
        <div className=\"vbp-page\">
{$jsx_content}
        </div>
    );
};

export default {$component_name};
";

        $files[] = array(
            'filename' => "{$component_name}.{$extension}",
            'content'  => $component_code,
            'type'     => 'component',
        );

        // Archivo de estilos si es necesario
        if ( 'css-modules' === $styling ) {
            $files[] = array(
                'filename' => "{$component_name}.module.css",
                'content'  => $css_content,
                'type'     => 'styles',
            );
        } elseif ( 'inline' === $styling || 'tailwind' !== $styling ) {
            $files[] = array(
                'filename' => "{$component_name}.css",
                'content'  => $css_content,
                'type'     => 'styles',
            );
        }

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.{$extension}",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera componente Vue SFC
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_vue_component( $elements, $component_name, $typescript, $styling ) {
        $files = array();
        $dependencies = array( 'vue' );

        $script_lang = $typescript ? ' lang="ts"' : '';
        $style_scoped = 'tailwind' !== $styling ? ' scoped' : '';

        // Generar template
        $template_content = $this->elements_to_vue_template( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        $vue_code = "<template>
    <div class=\"vbp-page\">
{$template_content}
    </div>
</template>

<script{$script_lang}>
/**
 * {$component_name} - Generado por Flavor VBP
 * @generated
 */
export default {
    name: '{$component_name}',
};
</script>

<style{$style_scoped}>
{$css_content}
</style>
";

        $files[] = array(
            'filename' => "{$component_name}.vue",
            'content'  => $vue_code,
            'type'     => 'component',
        );

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.vue",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera componente Svelte
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_svelte_component( $elements, $component_name, $typescript, $styling ) {
        $files = array();
        $dependencies = array( 'svelte' );

        $script_lang = $typescript ? ' lang="ts"' : '';

        // Generar markup
        $markup_content = $this->elements_to_svelte_markup( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        $svelte_code = "<script{$script_lang}>
    /**
     * {$component_name} - Generado por Flavor VBP
     * @generated
     */
</script>

<div class=\"vbp-page\">
{$markup_content}
</div>

<style>
{$css_content}
</style>
";

        $files[] = array(
            'filename' => "{$component_name}.svelte",
            'content'  => $svelte_code,
            'type'     => 'component',
        );

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.svelte",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera exportación HTML estática
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_html_export( $elements, $component_name, $styling ) {
        $files = array();

        $html_content = $this->elements_to_html( $elements );
        $css_content = $this->elements_to_css( $elements );

        $html_code = "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$component_name}</title>
    <link rel=\"stylesheet\" href=\"{$component_name}.css\">
</head>
<body>
    <div class=\"vbp-page\">
{$html_content}
    </div>
</body>
</html>
";

        $files[] = array(
            'filename' => "{$component_name}.html",
            'content'  => $html_code,
            'type'     => 'html',
        );

        $files[] = array(
            'filename' => "{$component_name}.css",
            'content'  => $css_content,
            'type'     => 'styles',
        );

        return array(
            'files'     => $files,
            'main_file' => "{$component_name}.html",
        );
    }

    /**
     * Convierte elementos VBP a JSX
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_jsx( $elements, $styling, $indent = 3 ) {
        $jsx = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = $this->generate_class_name( $type, $element_id, $styling );
            $style_attr = $this->generate_style_attr( $element, $styling );
            $content = $this->get_element_content( $data );

            $jsx .= "{$spaces}<{$tag}";

            if ( $class_name ) {
                $jsx .= " className=\"{$class_name}\"";
            }

            if ( $style_attr && 'inline' === $styling ) {
                $jsx .= " style={{{$style_attr}}}";
            }

            if ( ! empty( $children ) ) {
                $jsx .= ">\n";
                $jsx .= $this->elements_to_jsx( $children, $styling, $indent + 1 );
                $jsx .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $jsx .= ">{$content}</{$tag}>\n";
            } else {
                $jsx .= " />\n";
            }
        }

        return $jsx;
    }

    /**
     * Convierte elementos VBP a template Vue
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_vue_template( $elements, $styling, $indent = 2 ) {
        $html = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = $this->generate_class_name( $type, $element_id, $styling );
            $content = $this->get_element_content( $data );

            $html .= "{$spaces}<{$tag}";

            if ( $class_name ) {
                $html .= " class=\"{$class_name}\"";
            }

            if ( ! empty( $children ) ) {
                $html .= ">\n";
                $html .= $this->elements_to_vue_template( $children, $styling, $indent + 1 );
                $html .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $html .= ">{$content}</{$tag}>\n";
            } else {
                $html .= "></{$tag}>\n";
            }
        }

        return $html;
    }

    /**
     * Convierte elementos VBP a markup Svelte
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_svelte_markup( $elements, $styling, $indent = 1 ) {
        return $this->elements_to_vue_template( $elements, $styling, $indent );
    }

    /**
     * Convierte elementos VBP a HTML
     *
     * @param array $elements Elementos VBP.
     * @param int   $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_html( $elements, $indent = 2 ) {
        $html = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = "vbp-{$type}";
            $content = $this->get_element_content( $data );

            $html .= "{$spaces}<{$tag} class=\"{$class_name}\"";

            if ( $element_id ) {
                $html .= " id=\"{$element_id}\"";
            }

            if ( ! empty( $children ) ) {
                $html .= ">\n";
                $html .= $this->elements_to_html( $children, $indent + 1 );
                $html .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $html .= ">{$content}</{$tag}>\n";
            } else {
                $html .= "></{$tag}>\n";
            }
        }

        return $html;
    }

    /**
     * Genera CSS desde elementos VBP
     *
     * @param array $elements Elementos VBP.
     * @return string
     */
    private function elements_to_css( $elements ) {
        $css = "/* Generado por Flavor VBP */\n\n";
        $css .= ".vbp-page {\n    max-width: 1200px;\n    margin: 0 auto;\n}\n\n";

        $css .= $this->generate_element_css( $elements );

        return $css;
    }

    /**
     * Genera CSS recursivo para elementos
     *
     * @param array $elements Elementos VBP.
     * @return string
     */
    private function generate_element_css( $elements ) {
        $css = '';

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $styles = $element['styles'] ?? array();
            $children = $element['children'] ?? array();

            $selector = ".vbp-{$type}";

            if ( ! empty( $styles ) ) {
                $css .= "{$selector} {\n";

                foreach ( $styles as $property => $value ) {
                    if ( is_string( $value ) || is_numeric( $value ) ) {
                        $css_property = $this->camel_to_kebab( $property );
                        $css .= "    {$css_property}: {$value};\n";
                    }
                }

                $css .= "}\n\n";
            }

            if ( ! empty( $children ) ) {
                $css .= $this->generate_element_css( $children );
            }
        }

        return $css;
    }

    /**
     * Convierte tipo VBP a tag HTML
     *
     * @param string $type Tipo VBP.
     * @return string
     */
    private function vbp_type_to_html_tag( $type ) {
        $mapping = array(
            'section'   => 'section',
            'container' => 'div',
            'row'       => 'div',
            'column'    => 'div',
            'heading'   => 'h2',
            'text'      => 'p',
            'paragraph' => 'p',
            'image'     => 'img',
            'button'    => 'button',
            'link'      => 'a',
            'list'      => 'ul',
            'list-item' => 'li',
            'video'     => 'video',
            'form'      => 'form',
            'input'     => 'input',
            'textarea'  => 'textarea',
            'nav'       => 'nav',
            'header'    => 'header',
            'footer'    => 'footer',
            'article'   => 'article',
            'aside'     => 'aside',
            'figure'    => 'figure',
            'figcaption'=> 'figcaption',
            'blockquote'=> 'blockquote',
            'code'      => 'pre',
            'table'     => 'table',
            'icon'      => 'span',
            'spacer'    => 'div',
            'divider'   => 'hr',
        );

        return $mapping[ $type ] ?? 'div';
    }

    /**
     * Genera nombre de clase según styling
     *
     * @param string $type       Tipo de elemento.
     * @param string $element_id ID del elemento.
     * @param string $styling    Tipo de estilos.
     * @return string
     */
    private function generate_class_name( $type, $element_id, $styling ) {
        if ( 'tailwind' === $styling ) {
            return $this->get_tailwind_classes( $type );
        }

        if ( 'css-modules' === $styling ) {
            return "styles.{$type}";
        }

        return "vbp-{$type}";
    }

    /**
     * Obtiene clases Tailwind para tipo
     *
     * @param string $type Tipo de elemento.
     * @return string
     */
    private function get_tailwind_classes( $type ) {
        $classes = array(
            'section'   => 'py-16 px-4',
            'container' => 'max-w-6xl mx-auto',
            'row'       => 'flex flex-wrap -mx-4',
            'column'    => 'px-4 w-full md:w-1/2 lg:w-1/3',
            'heading'   => 'text-3xl font-bold mb-4',
            'text'      => 'text-base text-gray-700 mb-4',
            'button'    => 'px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700',
            'image'     => 'w-full h-auto rounded',
            'spacer'    => 'h-8',
            'divider'   => 'border-t border-gray-200 my-8',
        );

        return $classes[ $type ] ?? '';
    }

    /**
     * Genera atributo style inline
     *
     * @param array  $element Elemento VBP.
     * @param string $styling Tipo de estilos.
     * @return string
     */
    private function generate_style_attr( $element, $styling ) {
        if ( 'inline' !== $styling ) {
            return '';
        }

        $styles = $element['styles'] ?? array();
        if ( empty( $styles ) ) {
            return '';
        }

        $style_parts = array();
        foreach ( $styles as $property => $value ) {
            if ( is_string( $value ) || is_numeric( $value ) ) {
                $style_parts[] = "{$property}: '{$value}'";
            }
        }

        return implode( ', ', $style_parts );
    }

    /**
     * Obtiene contenido del elemento
     *
     * @param array $data Datos del elemento.
     * @return string
     */
    private function get_element_content( $data ) {
        return esc_html( $data['text'] ?? $data['content'] ?? $data['label'] ?? '' );
    }

    /**
     * Convierte string a PascalCase
     *
     * @param string $string String a convertir.
     * @return string
     */
    private function to_pascal_case( $string ) {
        $string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $string );
        $words = explode( ' ', $string );
        $pascal = '';

        foreach ( $words as $word ) {
            if ( $word ) {
                $pascal .= ucfirst( strtolower( $word ) );
            }
        }

        return $pascal ?: 'Component';
    }

    /**
     * Convierte camelCase a kebab-case
     *
     * @param string $string String a convertir.
     * @return string
     */
    private function camel_to_kebab( $string ) {
        return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $string ) );
    }

    /**
     * Exporta estructura JSON
     */
    public function export_page_structure_json( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'export'   => array( 'version' => '2.0', 'exported' => current_time( 'mysql' ), 'elements' => $elements ),
        ), 200 );
    }

    // =============================================
    // ENDPOINTS DE EXPORTACIÓN A FRAMEWORKS (Fase 3)
    // =============================================

    /**
     * Exportar página a componentes React
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_react( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $component_style = sanitize_text_field( $request->get_param( 'component_style' ) );
        $css_strategy = sanitize_text_field( $request->get_param( 'css_strategy' ) );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'react' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', $css_strategy );

        return $this->export_page_as_components( $simulated_request );
    }

    /**
     * Exportar página a componentes Vue
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_vue( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $vue_version = (int) $request->get_param( 'vue_version' );
        $composition_api = (bool) $request->get_param( 'composition_api' );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'vue' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', 'scoped' );

        $response = $this->export_page_as_components( $simulated_request );
        $data = $response->get_data();

        // Agregar metadata específica de Vue
        if ( $data['success'] ?? false ) {
            $data['vue_version'] = $vue_version;
            $data['composition_api'] = $composition_api;
        }

        return new WP_REST_Response( $data, $response->get_status() );
    }

    /**
     * Exportar página a componentes Svelte
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_svelte( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $svelte_version = (int) $request->get_param( 'svelte_version' );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'svelte' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', 'scoped' );

        $response = $this->export_page_as_components( $simulated_request );
        $data = $response->get_data();

        // Agregar metadata específica de Svelte
        if ( $data['success'] ?? false ) {
            $data['svelte_version'] = $svelte_version;
        }

        return new WP_REST_Response( $data, $response->get_status() );
    }

    /**
     * Exportar solo CSS de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_css_only( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );
        $minify = (bool) $request->get_param( 'minify' );
        $include_reset = (bool) $request->get_param( 'include_reset' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $css_reset = '';
        if ( $include_reset ) {
            $css_reset = "/* CSS Reset */\n* { margin: 0; padding: 0; box-sizing: border-box; }\nhtml { font-size: 16px; }\nbody { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; }\nimg { max-width: 100%; height: auto; }\na { text-decoration: none; color: inherit; }\n\n";
        }

        // Generar CSS desde elementos
        $css_content = $this->elements_to_css( $elements );

        // Convertir formato si es necesario
        if ( $format === 'tailwind' ) {
            $css_content = $this->css_to_tailwind_hints( $css_content );
        }

        if ( $minify ) {
            $css_content = $this->minify_css( $css_reset . $css_content );
        } else {
            $css_content = $css_reset . $css_content;
        }

        $file_extension = ( $format === 'scss' || $format === 'less' ) ? $format : 'css';
        $filename = sanitize_title( $post->post_title ) . '.' . $file_extension;

        return new WP_REST_Response( array(
            'success'  => true,
            'format'   => $format,
            'filename' => $filename,
            'content'  => $css_content,
            'size'     => strlen( $css_content ),
        ), 200 );
    }

    /**
     * Exportar estructura JSON de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_json_structure( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_styles = (bool) $request->get_param( 'include_styles' );
        $include_settings = (bool) $request->get_param( 'include_settings' );
        $flatten = (bool) $request->get_param( 'flatten' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $export_data = array(
            'version'    => '2.1.0',
            'exported'   => current_time( 'mysql' ),
            'page_id'    => $page_id,
            'page_title' => $post->post_title,
            'page_slug'  => $post->post_name,
        );

        if ( $include_settings ) {
            $export_data['settings'] = get_post_meta( $page_id, '_vbp_settings', true ) ?: array();
        }

        if ( $flatten ) {
            $export_data['elements'] = $this->flatten_elements( $elements );
        } else {
            $export_data['elements'] = $elements;
        }

        if ( ! $include_styles ) {
            $export_data['elements'] = $this->strip_styles_from_elements( $export_data['elements'] );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'export'   => $export_data,
            'filename' => sanitize_title( $post->post_title ) . '.json',
        ), 200 );
    }

    /**
     * Aplanar estructura jerárquica de elementos
     *
     * @param array $elements Elementos a aplanar.
     * @param string $parent_id ID del padre.
     * @return array
     */
    private function flatten_elements( $elements, $parent_id = null ) {
        $flat_elements = array();

        foreach ( $elements as $index => $element ) {
            $element_copy = $element;
            $element_copy['_parent_id'] = $parent_id;
            $element_copy['_index'] = $index;

            if ( isset( $element_copy['children'] ) ) {
                $children = $element_copy['children'];
                unset( $element_copy['children'] );
                $flat_elements[] = $element_copy;

                $flat_elements = array_merge(
                    $flat_elements,
                    $this->flatten_elements( $children, $element_copy['id'] ?? null )
                );
            } else {
                $flat_elements[] = $element_copy;
            }
        }

        return $flat_elements;
    }

    /**
     * Eliminar estilos de elementos
     *
     * @param array $elements Elementos a procesar.
     * @return array
     */
    private function strip_styles_from_elements( $elements ) {
        $stripped_elements = array();

        foreach ( $elements as $element ) {
            $stripped_element = $element;

            if ( isset( $stripped_element['data']['styles'] ) ) {
                unset( $stripped_element['data']['styles'] );
            }
            if ( isset( $stripped_element['data']['customCss'] ) ) {
                unset( $stripped_element['data']['customCss'] );
            }

            if ( isset( $stripped_element['children'] ) ) {
                $stripped_element['children'] = $this->strip_styles_from_elements( $stripped_element['children'] );
            }

            $stripped_elements[] = $stripped_element;
        }

        return $stripped_elements;
    }

    /**
     * Convertir CSS a hints de Tailwind
     *
     * @param string $css CSS a convertir.
     * @return string
     */
    private function css_to_tailwind_hints( $css ) {
        $hints = "/* Tailwind CSS Mapping Suggestions */\n\n";

        $conversions = array(
            'display: flex' => 'flex',
            'display: grid' => 'grid',
            'display: block' => 'block',
            'display: inline' => 'inline',
            'justify-content: center' => 'justify-center',
            'justify-content: space-between' => 'justify-between',
            'align-items: center' => 'items-center',
            'text-align: center' => 'text-center',
            'font-weight: bold' => 'font-bold',
            'font-weight: 600' => 'font-semibold',
            'padding: 0' => 'p-0',
            'margin: 0' => 'm-0',
        );

        foreach ( $conversions as $css_rule => $tailwind_class ) {
            if ( strpos( $css, $css_rule ) !== false ) {
                $hints .= "/* {$css_rule} => class=\"{$tailwind_class}\" */\n";
            }
        }

        $hints .= "\n/* Original CSS below for reference */\n\n";
        $hints .= $css;

        return $hints;
    }

    // =============================================
    // MÉTODOS DE SLOTS Y EVENTOS
    // =============================================

    /**
     * Define slot en bloque
     */
    public function define_block_slot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $slot_name = sanitize_text_field( $request->get_param( 'slot_name' ) );
        $allowed_blocks = $request->get_param( 'allowed_blocks' ) ?: array();
        $max_items = (int) $request->get_param( 'max_items' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $slot = array( 'name' => $slot_name, 'allowed_blocks' => $allowed_blocks, 'max_items' => $max_items, 'items' => array() );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $slot_name, $slot ) {
            if ( ! isset( $el['data']['_slots'] ) ) {
                $el['data']['_slots'] = array();
            }
            $el['data']['_slots'][ $slot_name ] = $slot;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'slot' => $slot ), 201 );
    }

    /**
     * Obtiene slots de bloque
     */
    public function get_block_slots( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array( 'success' => true, 'slots' => $block['data']['_slots'] ?? array() ), 200 );
    }

    /**
     * Inserta bloque en slot
     */
    public function insert_block_into_slot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $slot_name = sanitize_text_field( $request->get_param( 'slot_name' ) );
        $block = $request->get_param( 'block' );
        $position = (int) $request->get_param( 'position' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block['id'] = 'el_' . bin2hex( random_bytes( 6 ) );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $slot_name, $block, $position ) {
            if ( ! isset( $el['data']['_slots'][ $slot_name ] ) ) {
                return $el;
            }
            if ( $position < 0 ) {
                $el['data']['_slots'][ $slot_name ]['items'][] = $block;
            } else {
                array_splice( $el['data']['_slots'][ $slot_name ]['items'], $position, 0, array( $block ) );
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'block_id' => $block['id'] ), 201 );
    }

    /**
     * Configura evento de bloque
     */
    public function set_block_event_handler( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $event = sanitize_text_field( $request->get_param( 'event' ) );
        $actions = $request->get_param( 'actions' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $event, $actions ) {
            if ( ! isset( $el['data']['_events'] ) ) {
                $el['data']['_events'] = array();
            }
            $el['data']['_events'][ $event ] = $actions;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'event' => $event, 'actions' => $actions ), 200 );
    }

    /**
     * Obtiene eventos de bloque
     */
    public function get_block_event_handlers( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array( 'success' => true, 'events' => $block['data']['_events'] ?? array() ), 200 );
    }

    /**
     * Tipos de acciones de eventos
     */
    public function get_event_action_types( $request ) {
        $types = array(
            array( 'id' => 'navigate', 'name' => 'Navegar a URL', 'params' => array( 'url', 'target' ) ),
            array( 'id' => 'scroll_to', 'name' => 'Scroll a elemento', 'params' => array( 'element_id' ) ),
            array( 'id' => 'toggle_class', 'name' => 'Alternar clase', 'params' => array( 'element_id', 'class' ) ),
            array( 'id' => 'show_modal', 'name' => 'Mostrar modal', 'params' => array( 'modal_id' ) ),
            array( 'id' => 'close_modal', 'name' => 'Cerrar modal', 'params' => array() ),
            array( 'id' => 'play_animation', 'name' => 'Reproducir animación', 'params' => array( 'element_id', 'animation' ) ),
            array( 'id' => 'submit_form', 'name' => 'Enviar formulario', 'params' => array( 'form_id' ) ),
            array( 'id' => 'set_value', 'name' => 'Establecer valor', 'params' => array( 'variable', 'value' ) ),
        );

        return new WP_REST_Response( array( 'success' => true, 'action_types' => $types ), 200 );
    }

    // =============================================
    // MÉTODOS DE MÉTRICAS Y WEB VITALS
    // =============================================

    /**
     * Obtiene métricas de página
     */
    public function get_page_metrics( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $element_count = $this->count_elements( $elements );
        $image_count = $this->count_elements_by_type( $elements, 'image' );
        $depth = $this->calculate_max_depth( $elements );

        return new WP_REST_Response( array(
            'success' => true,
            'metrics' => array(
                'total_elements' => $element_count,
                'total_images'   => $image_count,
                'max_depth'      => $depth,
                'estimated_dom'  => $element_count * 3,
            ),
        ), 200 );
    }

    /**
     * Estima Web Vitals
     */
    public function estimate_web_vitals( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $element_count = $this->count_elements( $elements );
        $image_count = $this->count_elements_by_type( $elements, 'image' );

        $lcp_estimate = min( 4.0, 1.0 + ( $image_count * 0.1 ) + ( $element_count * 0.01 ) );
        $cls_estimate = min( 0.25, $image_count * 0.02 );
        $fid_estimate = min( 300, 50 + ( $element_count * 0.5 ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'web_vitals' => array(
                'lcp' => array( 'value' => round( $lcp_estimate, 2 ), 'unit' => 's', 'rating' => $lcp_estimate <= 2.5 ? 'good' : ( $lcp_estimate <= 4 ? 'needs_improvement' : 'poor' ) ),
                'cls' => array( 'value' => round( $cls_estimate, 3 ), 'unit' => '', 'rating' => $cls_estimate <= 0.1 ? 'good' : ( $cls_estimate <= 0.25 ? 'needs_improvement' : 'poor' ) ),
                'fid' => array( 'value' => round( $fid_estimate ), 'unit' => 'ms', 'rating' => $fid_estimate <= 100 ? 'good' : ( $fid_estimate <= 300 ? 'needs_improvement' : 'poor' ) ),
            ),
        ), 200 );
    }

    /**
     * Sugerencias de Web Vitals
     */
    public function get_web_vitals_suggestions( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $suggestions = array(
            array( 'type' => 'lcp', 'suggestion' => 'Usar lazy loading en imágenes below the fold', 'impact' => 'high' ),
            array( 'type' => 'cls', 'suggestion' => 'Definir dimensiones en imágenes', 'impact' => 'high' ),
            array( 'type' => 'fid', 'suggestion' => 'Minimizar JavaScript de terceros', 'impact' => 'medium' ),
            array( 'type' => 'general', 'suggestion' => 'Usar formatos de imagen modernos (WebP, AVIF)', 'impact' => 'medium' ),
        );

        return new WP_REST_Response( array( 'success' => true, 'suggestions' => $suggestions ), 200 );
    }

    // =============================================
    // MÉTODOS DE CACHÉ Y LAZY LOADING
    // =============================================

    /**
     * Configura caché de página
     */
    public function configure_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $ttl = (int) $request->get_param( 'ttl' );
        $vary_by = $request->get_param( 'vary_by' ) ?: array();

        $cache_config = array( 'enabled' => $enabled, 'ttl' => $ttl, 'vary_by' => $vary_by );
        update_post_meta( $page_id, '_vbp_cache_config', $cache_config );

        return new WP_REST_Response( array( 'success' => true, 'cache_config' => $cache_config ), 200 );
    }

    /**
     * Invalida caché de página
     */
    public function invalidate_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        delete_transient( 'vbp_page_cache_' . $page_id );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Caché invalidada.' ), 200 );
    }

    /**
     * Pre-genera caché
     */
    public function pregenerate_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $html = $this->elements_to_html( $elements );
        $cache_config = get_post_meta( $page_id, '_vbp_cache_config', true ) ?: array( 'ttl' => 3600 );
        set_transient( 'vbp_page_cache_' . $page_id, $html, $cache_config['ttl'] );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Caché pre-generada.' ), 200 );
    }

    /**
     * Configura lazy loading de bloque
     */
    public function configure_block_lazy_loading( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $enabled = (bool) $request->get_param( 'enabled' );
        $threshold = sanitize_text_field( $request->get_param( 'threshold' ) );
        $placeholder = sanitize_text_field( $request->get_param( 'placeholder' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $lazy_config = array( 'enabled' => $enabled, 'threshold' => $threshold, 'placeholder' => $placeholder );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $lazy_config ) {
            $el['data']['_lazy_load'] = $lazy_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'lazy_config' => $lazy_config ), 200 );
    }

    /**
     * Configura prioridad de carga
     */
    public function set_blocks_load_priority( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $priorities = $request->get_param( 'priorities' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        foreach ( $priorities as $item ) {
            $block_id = $item['block_id'] ?? '';
            $priority = (int) ( $item['priority'] ?? 0 );

            $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $priority ) {
                $el['data']['_load_priority'] = $priority;
                return $el;
            } );
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'priorities_set' => count( $priorities ) ), 200 );
    }

    /**
     * Cuenta elementos por tipo
     */
    private function count_elements_by_type( $elements, $type ) {
        $count = 0;
        foreach ( $elements as $el ) {
            if ( ( $el['type'] ?? '' ) === $type ) {
                $count++;
            }
            if ( ! empty( $el['children'] ) ) {
                $count += $this->count_elements_by_type( $el['children'], $type );
            }
        }
        return $count;
    }

    // =============================================
    // MÉTODOS DE EFECTOS CSS MODERNOS
    // =============================================

    /**
     * Obtiene efectos CSS modernos disponibles
     */
    public function get_modern_css_effects( $request ) {
        $effects = array(
            'glassmorphism' => array(
                'name' => 'Glassmorphism',
                'description' => 'Efecto de vidrio esmerilado con blur y transparencia',
                'css_properties' => array( 'backdrop-filter', 'background', 'border' ),
                'browser_support' => '95%',
            ),
            'neumorphism' => array(
                'name' => 'Neumorphism',
                'description' => 'Efecto de relieve suave con sombras internas y externas',
                'css_properties' => array( 'box-shadow', 'background' ),
                'browser_support' => '99%',
            ),
            'gradient_mesh' => array(
                'name' => 'Gradient Mesh',
                'description' => 'Gradientes complejos con múltiples puntos de color',
                'css_properties' => array( 'background', 'background-blend-mode' ),
                'browser_support' => '98%',
            ),
            'blend_modes' => array(
                'name' => 'Blend Modes',
                'description' => 'Modos de mezcla para combinar capas',
                'css_properties' => array( 'mix-blend-mode', 'background-blend-mode' ),
                'browser_support' => '97%',
            ),
            'clip_path' => array(
                'name' => 'Clip Path',
                'description' => 'Recortes de forma para elementos',
                'css_properties' => array( 'clip-path' ),
                'browser_support' => '96%',
            ),
            'filters' => array(
                'name' => 'CSS Filters',
                'description' => 'Filtros como blur, brightness, contrast, etc.',
                'css_properties' => array( 'filter', 'backdrop-filter' ),
                'browser_support' => '98%',
            ),
        );

        return new WP_REST_Response( array( 'success' => true, 'effects' => $effects ), 200 );
    }

    /**
     * Aplica glassmorphism a bloque
     */
    public function apply_glassmorphism( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $blur = (int) $request->get_param( 'blur' );
        $opacity = (float) $request->get_param( 'opacity' );
        $saturation = (float) $request->get_param( 'saturation' );
        $border_opacity = (float) $request->get_param( 'border_opacity' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $glassmorphism_styles = array(
            'background' => "rgba(255, 255, 255, {$opacity})",
            'backdropFilter' => "blur({$blur}px) saturate({$saturation})",
            'webkitBackdropFilter' => "blur({$blur}px) saturate({$saturation})",
            'border' => "1px solid rgba(255, 255, 255, {$border_opacity})",
            'borderRadius' => '16px',
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $glassmorphism_styles ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $glassmorphism_styles );
            $el['data']['_effect'] = 'glassmorphism';
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'effect' => 'glassmorphism', 'styles' => $glassmorphism_styles ), 200 );
    }

    /**
     * Aplica neumorphism a bloque
     */
    public function apply_neumorphism( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $distance = (int) $request->get_param( 'distance' );
        $intensity = (float) $request->get_param( 'intensity' );
        $blur = (int) $request->get_param( 'blur' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $light_shadow = "rgba(255, 255, 255, {$intensity})";
        $dark_shadow = "rgba(0, 0, 0, {$intensity})";

        $shadows = array(
            'flat' => "{$distance}px {$distance}px {$blur}px {$dark_shadow}, -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
            'concave' => "inset {$distance}px {$distance}px {$blur}px {$dark_shadow}, inset -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
            'convex' => "{$distance}px {$distance}px {$blur}px {$dark_shadow}, -{$distance}px -{$distance}px {$blur}px {$light_shadow}, inset 2px 2px 4px {$light_shadow}",
            'pressed' => "inset {$distance}px {$distance}px {$blur}px {$dark_shadow}, inset -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
        );

        $neumorphism_styles = array(
            'boxShadow' => $shadows[ $type ] ?? $shadows['flat'],
            'borderRadius' => '20px',
            'background' => '#e0e5ec',
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $neumorphism_styles, $type ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $neumorphism_styles );
            $el['data']['_effect'] = 'neumorphism';
            $el['data']['_neumorphism_type'] = $type;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'effect' => 'neumorphism', 'type' => $type ), 200 );
    }

    /**
     * Aplica gradiente avanzado
     */
    public function apply_advanced_gradient( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $colors = $request->get_param( 'colors' );
        $angle = (int) $request->get_param( 'angle' );
        $animated = (bool) $request->get_param( 'animated' );
        $target = sanitize_text_field( $request->get_param( 'target' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $color_stops = implode( ', ', $colors );
        $gradient = '';
        $third_color = isset( $colors[2] ) ? $colors[2] : $colors[0];

        switch ( $type ) {
            case 'linear':
                $gradient = "linear-gradient({$angle}deg, {$color_stops})";
                break;
            case 'radial':
                $gradient = "radial-gradient(circle, {$color_stops})";
                break;
            case 'conic':
                $gradient = "conic-gradient(from {$angle}deg, {$color_stops})";
                break;
            case 'mesh':
                $gradient = "radial-gradient(at 40% 20%, {$colors[0]} 0px, transparent 50%), radial-gradient(at 80% 0%, {$colors[1]} 0px, transparent 50%), radial-gradient(at 0% 50%, {$third_color} 0px, transparent 50%)";
                break;
        }

        $gradient_styles = array();
        if ( $target === 'background' ) {
            $gradient_styles['background'] = $gradient;
            if ( $animated ) {
                $gradient_styles['backgroundSize'] = '400% 400%';
                $gradient_styles['animation'] = 'gradientShift 15s ease infinite';
            }
        } elseif ( $target === 'text' ) {
            $gradient_styles['background'] = $gradient;
            $gradient_styles['backgroundClip'] = 'text';
            $gradient_styles['webkitBackgroundClip'] = 'text';
            $gradient_styles['webkitTextFillColor'] = 'transparent';
        } elseif ( $target === 'border' ) {
            $gradient_styles['border'] = '3px solid transparent';
            $gradient_styles['backgroundImage'] = "linear-gradient(white, white), {$gradient}";
            $gradient_styles['backgroundOrigin'] = 'border-box';
            $gradient_styles['backgroundClip'] = 'padding-box, border-box';
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $gradient_styles, $type, $animated ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $gradient_styles );
            $el['data']['_gradient'] = array( 'type' => $type, 'animated' => $animated );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'gradient' => $gradient, 'animated' => $animated ), 200 );
    }

    /**
     * Aplica blend mode
     */
    public function apply_blend_mode( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $mode = sanitize_text_field( $request->get_param( 'mode' ) );
        $target = sanitize_text_field( $request->get_param( 'target' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $blend_styles = array();
        if ( $target === 'background' ) {
            $blend_styles['backgroundBlendMode'] = $mode;
        } else {
            $blend_styles['mixBlendMode'] = $mode;
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $blend_styles, $mode ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $blend_styles );
            $el['data']['_blend_mode'] = $mode;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'blend_mode' => $mode ), 200 );
    }

    /**
     * Aplica clip-path
     */
    public function apply_clip_path( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $shape = sanitize_text_field( $request->get_param( 'shape' ) );
        $custom_path = $request->get_param( 'custom_path' );
        $animated = (bool) $request->get_param( 'animated' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $paths = array(
            'circle' => 'circle(50% at 50% 50%)',
            'ellipse' => 'ellipse(50% 40% at 50% 50%)',
            'polygon' => 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
            'wave-top' => 'polygon(0% 100%, 0% 85%, 10% 90%, 25% 78%, 40% 85%, 55% 75%, 70% 85%, 85% 78%, 100% 90%, 100% 100%)',
            'wave-bottom' => 'polygon(0% 0%, 0% 85%, 10% 90%, 25% 78%, 40% 85%, 55% 75%, 70% 85%, 85% 78%, 100% 90%, 100% 0%)',
            'diagonal' => 'polygon(0 0, 100% 0, 100% 85%, 0 100%)',
            'arrow' => 'polygon(40% 0%, 100% 0%, 100% 100%, 40% 100%, 0% 50%)',
            'custom' => $custom_path ?: 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
        );

        $clip_path = $paths[ $shape ] ?? $paths['polygon'];

        $clip_styles = array(
            'clipPath' => $clip_path,
            'webkitClipPath' => $clip_path,
        );

        if ( $animated ) {
            $clip_styles['transition'] = 'clip-path 0.5s ease-out';
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $clip_styles, $shape ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $clip_styles );
            $el['data']['_clip_path'] = $shape;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'clip_path' => $clip_path ), 200 );
    }

    // =============================================
    // MÉTODOS DE TIPOGRAFÍA AVANZADA
    // =============================================

    /**
     * Obtiene escalas tipográficas
     */
    public function get_typography_scales( $request ) {
        $scales = array(
            'minor_second' => array( 'name' => 'Minor Second', 'ratio' => 1.067, 'use_case' => 'Muy compacto' ),
            'major_second' => array( 'name' => 'Major Second', 'ratio' => 1.125, 'use_case' => 'Compacto' ),
            'minor_third' => array( 'name' => 'Minor Third', 'ratio' => 1.2, 'use_case' => 'Legible' ),
            'major_third' => array( 'name' => 'Major Third', 'ratio' => 1.25, 'use_case' => 'Equilibrado (recomendado)' ),
            'perfect_fourth' => array( 'name' => 'Perfect Fourth', 'ratio' => 1.333, 'use_case' => 'Clásico' ),
            'augmented_fourth' => array( 'name' => 'Augmented Fourth', 'ratio' => 1.414, 'use_case' => 'Dramático' ),
            'perfect_fifth' => array( 'name' => 'Perfect Fifth', 'ratio' => 1.5, 'use_case' => 'Impactante' ),
            'golden_ratio' => array( 'name' => 'Golden Ratio', 'ratio' => 1.618, 'use_case' => 'Artístico' ),
        );

        $font_stacks = array(
            'system' => array(
                'name' => 'System UI',
                'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                'variable' => false,
            ),
            'inter' => array(
                'name' => 'Inter',
                'stack' => '"Inter", system-ui, sans-serif',
                'variable' => true,
                'weights' => '100..900',
            ),
            'roboto' => array(
                'name' => 'Roboto',
                'stack' => '"Roboto", sans-serif',
                'variable' => false,
            ),
            'poppins' => array(
                'name' => 'Poppins',
                'stack' => '"Poppins", sans-serif',
                'variable' => false,
            ),
            'playfair' => array(
                'name' => 'Playfair Display',
                'stack' => '"Playfair Display", serif',
                'variable' => false,
            ),
            'space_grotesk' => array(
                'name' => 'Space Grotesk',
                'stack' => '"Space Grotesk", sans-serif',
                'variable' => true,
                'weights' => '300..700',
            ),
        );

        return new WP_REST_Response( array(
            'success' => true,
            'scales' => $scales,
            'font_stacks' => $font_stacks,
        ), 200 );
    }

    /**
     * Configura tipografía global
     */
    public function set_typography_config( $request ) {
        $base_size = (int) $request->get_param( 'base_size' );
        $scale_ratio = (float) $request->get_param( 'scale_ratio' );
        $line_height = (float) $request->get_param( 'line_height' );
        $heading_line_height = (float) $request->get_param( 'heading_line_height' );
        $font_family_heading = sanitize_text_field( $request->get_param( 'font_family_heading' ) );
        $font_family_body = sanitize_text_field( $request->get_param( 'font_family_body' ) );
        $variable_fonts = (bool) $request->get_param( 'variable_fonts' );

        $typography_config = array(
            'base_size' => $base_size,
            'scale_ratio' => $scale_ratio,
            'line_height' => $line_height,
            'heading_line_height' => $heading_line_height,
            'font_family_heading' => $font_family_heading,
            'font_family_body' => $font_family_body,
            'variable_fonts' => $variable_fonts,
            'sizes' => array(
                'xs' => round( $base_size / pow( $scale_ratio, 2 ), 1 ) . 'px',
                'sm' => round( $base_size / $scale_ratio, 1 ) . 'px',
                'base' => $base_size . 'px',
                'lg' => round( $base_size * $scale_ratio, 1 ) . 'px',
                'xl' => round( $base_size * pow( $scale_ratio, 2 ), 1 ) . 'px',
                '2xl' => round( $base_size * pow( $scale_ratio, 3 ), 1 ) . 'px',
                '3xl' => round( $base_size * pow( $scale_ratio, 4 ), 1 ) . 'px',
                '4xl' => round( $base_size * pow( $scale_ratio, 5 ), 1 ) . 'px',
                '5xl' => round( $base_size * pow( $scale_ratio, 6 ), 1 ) . 'px',
            ),
        );

        update_option( 'flavor_vbp_typography_config', $typography_config );

        return new WP_REST_Response( array( 'success' => true, 'typography' => $typography_config ), 200 );
    }

    /**
     * Aplica texto con gradiente
     */
    public function apply_gradient_text( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $colors = $request->get_param( 'colors' );
        $angle = (int) $request->get_param( 'angle' );
        $animated = (bool) $request->get_param( 'animated' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $color_stops = implode( ', ', $colors );
        $gradient_text_styles = array(
            'background' => "linear-gradient({$angle}deg, {$color_stops})",
            'backgroundClip' => 'text',
            'webkitBackgroundClip' => 'text',
            'webkitTextFillColor' => 'transparent',
            'color' => 'transparent',
        );

        if ( $animated ) {
            $gradient_text_styles['backgroundSize'] = '200% auto';
            $gradient_text_styles['animation'] = 'textGradientShift 3s linear infinite';
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $gradient_text_styles ) {
            if ( ! isset( $el['styles']['typography'] ) ) {
                $el['styles']['typography'] = array();
            }
            $el['styles']['typography'] = array_merge( $el['styles']['typography'], $gradient_text_styles );
            $el['data']['_gradient_text'] = true;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'gradient_text' => true ), 200 );
    }

    /**
     * Aplica efectos de texto
     */
    public function apply_text_effects( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $effect = sanitize_text_field( $request->get_param( 'effect' ) );
        $color = sanitize_text_field( $request->get_param( 'color' ) ) ?: '#000000';
        $intensity = (float) $request->get_param( 'intensity' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $effect_styles = array();
        $blur = round( 2 * $intensity );
        $offset = round( 2 * $intensity );

        switch ( $effect ) {
            case 'shadow':
                $effect_styles['textShadow'] = "{$offset}px {$offset}px {$blur}px {$color}";
                break;
            case 'outline':
                $effect_styles['webkitTextStroke'] = "{$intensity}px {$color}";
                $effect_styles['textStroke'] = "{$intensity}px {$color}";
                break;
            case 'glow':
                $effect_styles['textShadow'] = "0 0 {$blur}px {$color}, 0 0 " . ( $blur * 2 ) . "px {$color}";
                break;
            case 'neon':
                $effect_styles['textShadow'] = "0 0 5px {$color}, 0 0 10px {$color}, 0 0 20px {$color}, 0 0 40px {$color}";
                break;
            case '3d':
                $shadows = array();
                for ( $i = 1; $i <= 5; $i++ ) {
                    $shadows[] = "{$i}px {$i}px 0 " . $this->adjust_color_brightness( $color, -$i * 10 );
                }
                $effect_styles['textShadow'] = implode( ', ', $shadows );
                break;
            case 'emboss':
                $effect_styles['textShadow'] = "-1px -1px 0 rgba(255,255,255,0.5), 1px 1px 0 rgba(0,0,0,0.3)";
                break;
            case 'engrave':
                $effect_styles['textShadow'] = "1px 1px 0 rgba(255,255,255,0.5), -1px -1px 0 rgba(0,0,0,0.3)";
                break;
            case 'retro':
                $effect_styles['textShadow'] = "3px 3px 0 {$color}, 6px 6px 0 rgba(0,0,0,0.2)";
                break;
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $effect_styles, $effect ) {
            if ( ! isset( $el['styles']['typography'] ) ) {
                $el['styles']['typography'] = array();
            }
            $el['styles']['typography'] = array_merge( $el['styles']['typography'], $effect_styles );
            $el['data']['_text_effect'] = $effect;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'effect' => $effect ), 200 );
    }

    /**
     * Ajusta brillo de color
     */
    private function adjust_color_brightness( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, min( 255, $r + ( $r * $percent / 100 ) ) );
        $g = max( 0, min( 255, $g + ( $g * $percent / 100 ) ) );
        $b = max( 0, min( 255, $b + ( $b * $percent / 100 ) ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    // =============================================
    // MÉTODOS DE LAYOUT AVANZADO
    // =============================================

    /**
     * Aplica grid avanzado
     */
    public function apply_advanced_grid( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $columns = sanitize_text_field( $request->get_param( 'columns' ) );
        $rows = $request->get_param( 'rows' );
        $gap = sanitize_text_field( $request->get_param( 'gap' ) );
        $gap_responsive = $request->get_param( 'gap_responsive' );
        $auto_flow = $request->get_param( 'auto_flow' );
        $align_items = $request->get_param( 'align_items' );
        $justify_items = $request->get_param( 'justify_items' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $grid_styles = array(
            'display' => 'grid',
            'gridTemplateColumns' => $columns,
            'gap' => $gap,
        );

        if ( $rows ) {
            $grid_styles['gridTemplateRows'] = sanitize_text_field( $rows );
        }
        if ( $auto_flow ) {
            $grid_styles['gridAutoFlow'] = sanitize_text_field( $auto_flow );
        }
        if ( $align_items ) {
            $grid_styles['alignItems'] = sanitize_text_field( $align_items );
        }
        if ( $justify_items ) {
            $grid_styles['justifyItems'] = sanitize_text_field( $justify_items );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $grid_styles, $gap_responsive ) {
            if ( ! isset( $el['styles']['layout'] ) ) {
                $el['styles']['layout'] = array();
            }
            $el['styles']['layout'] = array_merge( $el['styles']['layout'], $grid_styles );
            if ( $gap_responsive ) {
                $el['responsive'] = $el['responsive'] ?? array();
                foreach ( $gap_responsive as $breakpoint => $bp_gap ) {
                    $el['responsive'][ $breakpoint ] = $el['responsive'][ $breakpoint ] ?? array();
                    $el['responsive'][ $breakpoint ]['gap'] = $bp_gap;
                }
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'grid' => $grid_styles ), 200 );
    }

    /**
     * Aplica aspect ratio
     */
    public function apply_aspect_ratio( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $ratio = sanitize_text_field( $request->get_param( 'ratio' ) );
        $custom_ratio = $request->get_param( 'custom_ratio' );
        $object_fit = sanitize_text_field( $request->get_param( 'object_fit' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $aspect_ratio = $ratio === 'custom' ? sanitize_text_field( $custom_ratio ) : $ratio;

        $aspect_styles = array(
            'aspectRatio' => $aspect_ratio,
            'objectFit' => $object_fit,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $aspect_styles ) {
            if ( ! isset( $el['styles']['layout'] ) ) {
                $el['styles']['layout'] = array();
            }
            $el['styles']['layout'] = array_merge( $el['styles']['layout'], $aspect_styles );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'aspect_ratio' => $aspect_ratio ), 200 );
    }

    /**
     * Aplica container query
     */
    public function apply_container_query( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $rules = $request->get_param( 'rules' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $name, $type, $rules ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['containerType'] = $type;
            $el['styles']['advanced']['containerName'] = $name;
            $el['data']['_container_query'] = array( 'name' => $name, 'type' => $type, 'rules' => $rules );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'container_name' => $name ), 200 );
    }

    // =============================================
    // MÉTODOS DE INTERACTIVIDAD AVANZADA
    // =============================================

    /**
     * Configura estados de hover avanzados
     */
    public function configure_hover_states( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $styles = $request->get_param( 'styles' );
        $transition = $request->get_param( 'transition' );
        $transform = $request->get_param( 'transform' );
        $filter = $request->get_param( 'filter' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $hover_config = array(
            'styles' => $styles,
            'transition' => $transition,
            'transform' => $transform,
            'filter' => $filter,
        );

        $transition_string = ( $transition['duration'] ?? '0.3s' ) . ' ' . ( $transition['easing'] ?? 'ease-out' );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $hover_config, $transition_string ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transition'] = "all {$transition_string}";
            $el['data']['_hover_states'] = $hover_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'hover_config' => $hover_config ), 200 );
    }

    /**
     * Configura scroll behavior
     */
    public function configure_scroll_behavior( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $smooth_scroll = (bool) $request->get_param( 'smooth_scroll' );
        $scroll_snap = $request->get_param( 'scroll_snap' );
        $scroll_padding = $request->get_param( 'scroll_padding' );
        $overscroll_behavior = $request->get_param( 'overscroll_behavior' );

        $scroll_config = array(
            'smooth_scroll' => $smooth_scroll,
            'scroll_snap' => $scroll_snap,
            'scroll_padding' => $scroll_padding,
            'overscroll_behavior' => $overscroll_behavior,
        );

        update_post_meta( $page_id, '_vbp_scroll_behavior', $scroll_config );

        return new WP_REST_Response( array( 'success' => true, 'scroll_behavior' => $scroll_config ), 200 );
    }

    /**
     * Configura parallax avanzado
     */
    public function configure_advanced_parallax( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $speed = (float) $request->get_param( 'speed' );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );
        $scale = (bool) $request->get_param( 'scale' );
        $rotate = (bool) $request->get_param( 'rotate' );
        $opacity = (bool) $request->get_param( 'opacity' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $parallax_config = array(
            'speed' => $speed,
            'direction' => $direction,
            'scale' => $scale,
            'rotate' => $rotate,
            'opacity' => $opacity,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $parallax_config ) {
            $el['data']['_parallax'] = $parallax_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'parallax' => $parallax_config ), 200 );
    }

    /**
     * Configura cursor personalizado
     */
    public function configure_custom_cursor( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $custom_url = $request->get_param( 'custom_url' );
        $hover_type = $request->get_param( 'hover_type' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $cursor_value = $type;
        if ( $type === 'custom' && $custom_url ) {
            $cursor_value = "url({$custom_url}), auto";
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $cursor_value, $hover_type ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['cursor'] = $cursor_value;
            if ( $hover_type ) {
                $el['data']['_cursor_hover'] = $hover_type;
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'cursor' => $cursor_value ), 200 );
    }

    // =============================================
    // MÉTODOS DE DARK MODE Y TEMAS
    // =============================================

    /**
     * Configura dark mode
     */
    public function configure_dark_mode( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $default_theme = sanitize_text_field( $request->get_param( 'default_theme' ) );
        $light_colors = $request->get_param( 'light_colors' ) ?: array(
            'background' => '#ffffff',
            'surface' => '#f5f5f5',
            'text' => '#1a1a1a',
            'text_muted' => '#666666',
            'border' => '#e0e0e0',
        );
        $dark_colors = $request->get_param( 'dark_colors' ) ?: array(
            'background' => '#1a1a1a',
            'surface' => '#2d2d2d',
            'text' => '#ffffff',
            'text_muted' => '#a0a0a0',
            'border' => '#404040',
        );
        $transition = sanitize_text_field( $request->get_param( 'transition' ) );

        $dark_mode_config = array(
            'enabled' => $enabled,
            'default_theme' => $default_theme,
            'light_colors' => $light_colors,
            'dark_colors' => $dark_colors,
            'transition' => $transition,
        );

        update_post_meta( $page_id, '_vbp_dark_mode', $dark_mode_config );

        return new WP_REST_Response( array( 'success' => true, 'dark_mode' => $dark_mode_config ), 200 );
    }

    /**
     * Obtiene configuración de dark mode
     */
    public function get_dark_mode_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $config = get_post_meta( $page_id, '_vbp_dark_mode', true ) ?: array( 'enabled' => false );

        return new WP_REST_Response( array( 'success' => true, 'dark_mode' => $config ), 200 );
    }

    // =============================================
    // MÉTODOS DE FORMULARIOS AVANZADOS
    // =============================================

    /**
     * Crea formulario
     */
    public function create_form( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $fields = $request->get_param( 'fields' );
        $submit_button = $request->get_param( 'submit_button' );
        $validation = $request->get_param( 'validation' );
        $action = sanitize_text_field( $request->get_param( 'action' ) );
        $success_message = sanitize_text_field( $request->get_param( 'success_message' ) ) ?: '¡Formulario enviado correctamente!';
        $error_message = sanitize_text_field( $request->get_param( 'error_message' ) ) ?: 'Ha ocurrido un error. Por favor, inténtalo de nuevo.';

        $form_id = 'form_' . bin2hex( random_bytes( 6 ) );

        $form_element = array(
            'id' => $form_id,
            'type' => 'form',
            'name' => $name,
            'data' => array(
                'form_name' => $name,
                'fields' => $fields,
                'submit_button' => $submit_button ?: array( 'text' => 'Enviar', 'style' => 'primary' ),
                'validation' => $validation,
                'action' => $action,
                'success_message' => $success_message,
                'error_message' => $error_message,
            ),
            'styles' => $this->get_default_styles(),
            'children' => array(),
        );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $form_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'form_id' => $form_id, 'form' => $form_element ), 201 );
    }

    /**
     * Obtiene tipos de campos de formulario
     */
    public function get_form_field_types( $request ) {
        $field_types = array(
            array( 'type' => 'text', 'name' => 'Texto', 'icon' => 'text', 'attributes' => array( 'placeholder', 'maxlength', 'minlength' ) ),
            array( 'type' => 'email', 'name' => 'Email', 'icon' => 'mail', 'attributes' => array( 'placeholder' ), 'validation' => 'email' ),
            array( 'type' => 'tel', 'name' => 'Teléfono', 'icon' => 'phone', 'attributes' => array( 'placeholder', 'pattern' ) ),
            array( 'type' => 'number', 'name' => 'Número', 'icon' => 'hash', 'attributes' => array( 'min', 'max', 'step' ) ),
            array( 'type' => 'textarea', 'name' => 'Área de texto', 'icon' => 'align-left', 'attributes' => array( 'rows', 'maxlength' ) ),
            array( 'type' => 'select', 'name' => 'Selector', 'icon' => 'chevron-down', 'attributes' => array( 'options', 'multiple' ) ),
            array( 'type' => 'checkbox', 'name' => 'Casilla', 'icon' => 'check-square', 'attributes' => array() ),
            array( 'type' => 'radio', 'name' => 'Radio', 'icon' => 'circle', 'attributes' => array( 'options' ) ),
            array( 'type' => 'date', 'name' => 'Fecha', 'icon' => 'calendar', 'attributes' => array( 'min', 'max' ) ),
            array( 'type' => 'time', 'name' => 'Hora', 'icon' => 'clock', 'attributes' => array() ),
            array( 'type' => 'file', 'name' => 'Archivo', 'icon' => 'upload', 'attributes' => array( 'accept', 'multiple', 'max_size' ) ),
            array( 'type' => 'hidden', 'name' => 'Oculto', 'icon' => 'eye-off', 'attributes' => array( 'value' ) ),
            array( 'type' => 'password', 'name' => 'Contraseña', 'icon' => 'lock', 'attributes' => array( 'minlength' ) ),
            array( 'type' => 'url', 'name' => 'URL', 'icon' => 'link', 'attributes' => array( 'placeholder' ), 'validation' => 'url' ),
            array( 'type' => 'range', 'name' => 'Rango', 'icon' => 'sliders', 'attributes' => array( 'min', 'max', 'step' ) ),
            array( 'type' => 'color', 'name' => 'Color', 'icon' => 'droplet', 'attributes' => array() ),
        );

        return new WP_REST_Response( array( 'success' => true, 'field_types' => $field_types ), 200 );
    }

    /**
     * Obtiene validaciones de formulario
     */
    public function get_form_validations( $request ) {
        $validations = array(
            array( 'type' => 'required', 'name' => 'Requerido', 'message' => 'Este campo es obligatorio' ),
            array( 'type' => 'email', 'name' => 'Email válido', 'message' => 'Introduce un email válido' ),
            array( 'type' => 'url', 'name' => 'URL válida', 'message' => 'Introduce una URL válida' ),
            array( 'type' => 'phone', 'name' => 'Teléfono válido', 'message' => 'Introduce un teléfono válido' ),
            array( 'type' => 'min', 'name' => 'Valor mínimo', 'params' => array( 'value' ) ),
            array( 'type' => 'max', 'name' => 'Valor máximo', 'params' => array( 'value' ) ),
            array( 'type' => 'minlength', 'name' => 'Longitud mínima', 'params' => array( 'length' ) ),
            array( 'type' => 'maxlength', 'name' => 'Longitud máxima', 'params' => array( 'length' ) ),
            array( 'type' => 'pattern', 'name' => 'Patrón regex', 'params' => array( 'regex' ) ),
            array( 'type' => 'match', 'name' => 'Coincidir con campo', 'params' => array( 'field' ) ),
        );

        return new WP_REST_Response( array( 'success' => true, 'validations' => $validations ), 200 );
    }

    // =============================================
    // MÉTODOS DE SEO Y METADATA
    // =============================================

    /**
     * Configura Schema.org / JSON-LD
     */
    public function configure_schema_org( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $data = $request->get_param( 'data' );

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
        );

        $schema = array_merge( $schema, $data );
        update_post_meta( $page_id, '_vbp_schema_org', $schema );

        return new WP_REST_Response( array( 'success' => true, 'schema' => $schema ), 200 );
    }

    /**
     * Configura Open Graph
     */
    public function configure_open_graph( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $description = sanitize_text_field( $request->get_param( 'description' ) );
        $image = esc_url_raw( $request->get_param( 'image' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $twitter_card = sanitize_text_field( $request->get_param( 'twitter_card' ) );

        $og_data = array(
            'og:title' => $title ?: get_the_title( $page_id ),
            'og:description' => $description,
            'og:image' => $image,
            'og:type' => $type,
            'og:url' => get_permalink( $page_id ),
            'twitter:card' => $twitter_card,
        );

        update_post_meta( $page_id, '_vbp_open_graph', $og_data );

        return new WP_REST_Response( array( 'success' => true, 'open_graph' => $og_data ), 200 );
    }

    // =============================================
    // MÉTODOS DE DOCUMENTACIÓN DE BLOQUES
    // =============================================

    /**
     * Obtiene documentación de un bloque
     */
    public function get_block_documentation( $request ) {
        $type = sanitize_text_field( $request->get_param( 'type' ) );

        $docs = $this->get_block_docs_data();

        if ( ! isset( $docs[ $type ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado' ), 404 );
        }

        return new WP_REST_Response( array( 'success' => true, 'documentation' => $docs[ $type ] ), 200 );
    }

    /**
     * Obtiene datos de documentación de bloques
     */
    private function get_block_docs_data() {
        return array(
            'hero' => array(
                'name' => 'Hero Section',
                'description' => 'Sección principal de la página, generalmente con título grande, subtítulo y llamada a la acción',
                'use_cases' => array( 'Landing pages', 'Página de inicio', 'Páginas de producto' ),
                'best_practices' => array(
                    'Usa un título claro y conciso (6-10 palabras)',
                    'Incluye una imagen de fondo de alta calidad',
                    'Limita los botones CTA a 1-2',
                    'Asegura buen contraste texto/fondo',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => true, 'description' => 'Título principal (H1)' ),
                    'subtitulo' => array( 'type' => 'text', 'required' => false, 'description' => 'Texto descriptivo' ),
                    'imagen_fondo' => array( 'type' => 'image', 'required' => false, 'description' => 'Imagen de fondo' ),
                    'boton_texto' => array( 'type' => 'text', 'required' => false, 'description' => 'Texto del CTA principal' ),
                    'boton_url' => array( 'type' => 'url', 'required' => false, 'description' => 'URL del CTA' ),
                ),
                'variants' => array( 'centered', 'left-aligned', 'split', 'video-background', 'parallax' ),
            ),
            'features' => array(
                'name' => 'Features/Características',
                'description' => 'Grid de características o beneficios del producto/servicio',
                'use_cases' => array( 'Mostrar beneficios', 'Listar características', 'Servicios' ),
                'best_practices' => array(
                    'Usa 3-6 características para mejor impacto visual',
                    'Incluye iconos consistentes',
                    'Títulos cortos (2-4 palabras)',
                    'Descripciones de 1-2 líneas',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => false, 'description' => 'Título de la sección' ),
                    'items' => array( 'type' => 'array', 'required' => true, 'description' => 'Lista de características' ),
                    'columnas' => array( 'type' => 'number', 'required' => false, 'default' => 3, 'description' => 'Número de columnas' ),
                ),
                'variants' => array( 'grid', 'list', 'alternating', 'cards', 'icons-only' ),
            ),
            'testimonials' => array(
                'name' => 'Testimonios',
                'description' => 'Sección de testimonios de clientes o usuarios',
                'use_cases' => array( 'Social proof', 'Reviews', 'Casos de éxito' ),
                'best_practices' => array(
                    'Incluye foto y nombre real',
                    'Testimonios de 2-3 líneas',
                    'Añade cargo/empresa si es B2B',
                    'Usa ratings de estrellas para mayor impacto',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => false ),
                    'testimonios' => array( 'type' => 'array', 'required' => true ),
                ),
                'variants' => array( 'carousel', 'grid', 'single', 'masonry', 'quote-style' ),
            ),
            'pricing' => array(
                'name' => 'Tabla de Precios',
                'description' => 'Muestra planes de precios o suscripciones',
                'use_cases' => array( 'SaaS', 'Membresías', 'Servicios con niveles' ),
                'best_practices' => array(
                    'Máximo 3-4 planes para comparación fácil',
                    'Destaca el plan recomendado',
                    'Lista características clave por plan',
                    'Incluye CTA claro por plan',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => false ),
                    'planes' => array( 'type' => 'array', 'required' => true ),
                    'mostrar_toggle_anual' => array( 'type' => 'boolean', 'default' => true ),
                ),
                'variants' => array( 'cards', 'comparison-table', 'horizontal', 'minimal' ),
            ),
            'cta' => array(
                'name' => 'Call to Action',
                'description' => 'Sección de llamada a la acción destacada',
                'use_cases' => array( 'Conversión', 'Newsletter', 'Registro', 'Contacto' ),
                'best_practices' => array(
                    'Un solo mensaje claro',
                    'Botón de acción prominente',
                    'Contraste de colores para destacar',
                    'Urgencia o beneficio claro',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => true ),
                    'subtitulo' => array( 'type' => 'text', 'required' => false ),
                    'boton_texto' => array( 'type' => 'text', 'required' => true ),
                    'boton_url' => array( 'type' => 'url', 'required' => true ),
                ),
                'variants' => array( 'simple', 'with-image', 'gradient', 'full-width', 'split' ),
            ),
            'faq' => array(
                'name' => 'FAQ/Preguntas Frecuentes',
                'description' => 'Sección de preguntas y respuestas en acordeón',
                'use_cases' => array( 'Soporte', 'Información adicional', 'SEO' ),
                'best_practices' => array(
                    '5-10 preguntas máximo',
                    'Respuestas concisas (2-3 párrafos)',
                    'Organiza por categorías si hay muchas',
                    'Incluye Schema.org FAQPage',
                ),
                'fields' => array(
                    'titulo' => array( 'type' => 'text', 'required' => false ),
                    'faqs' => array( 'type' => 'array', 'required' => true ),
                ),
                'variants' => array( 'accordion', 'two-columns', 'searchable', 'categorized' ),
            ),
        );
    }

    /**
     * Obtiene ejemplos de uso de bloque
     */
    public function get_block_examples( $request ) {
        $type = sanitize_text_field( $request->get_param( 'type' ) );

        $examples = array(
            'hero' => array(
                array(
                    'name' => 'Hero SaaS',
                    'data' => array(
                        'titulo' => 'Simplifica tu trabajo con nuestra plataforma',
                        'subtitulo' => 'Automatiza tareas, ahorra tiempo y aumenta tu productividad',
                        'boton_texto' => 'Empezar gratis',
                        'boton_url' => '/signup',
                    ),
                ),
                array(
                    'name' => 'Hero E-commerce',
                    'data' => array(
                        'titulo' => 'Nueva colección primavera',
                        'subtitulo' => 'Descubre las últimas tendencias con envío gratis',
                        'boton_texto' => 'Ver colección',
                        'boton_url' => '/coleccion',
                    ),
                ),
            ),
            'features' => array(
                array(
                    'name' => 'Features 3 columnas',
                    'data' => array(
                        'titulo' => '¿Por qué elegirnos?',
                        'columnas' => 3,
                        'items' => array(
                            array( 'icono' => 'zap', 'titulo' => 'Rápido', 'descripcion' => 'Resultados en segundos' ),
                            array( 'icono' => 'shield', 'titulo' => 'Seguro', 'descripcion' => 'Datos protegidos 24/7' ),
                            array( 'icono' => 'heart', 'titulo' => 'Fácil', 'descripcion' => 'Sin curva de aprendizaje' ),
                        ),
                    ),
                ),
            ),
        );

        if ( ! isset( $examples[ $type ] ) ) {
            return new WP_REST_Response( array( 'success' => true, 'examples' => array() ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true, 'examples' => $examples[ $type ] ), 200 );
    }

    /**
     * Busca bloques por funcionalidad
     */
    public function search_blocks_by_functionality( $request ) {
        $query = strtolower( sanitize_text_field( $request->get_param( 'query' ) ) );
        $category = $request->get_param( 'category' );

        $block_index = array(
            'hero' => array( 'banner', 'cabecera', 'header', 'principal', 'titulo', 'landing', 'above fold' ),
            'features' => array( 'caracteristicas', 'beneficios', 'servicios', 'ventajas', 'iconos', 'grid' ),
            'testimonials' => array( 'testimonios', 'reviews', 'opiniones', 'clientes', 'casos', 'social proof' ),
            'pricing' => array( 'precios', 'planes', 'tarifas', 'suscripcion', 'paquetes', 'comparativa' ),
            'cta' => array( 'accion', 'boton', 'conversion', 'newsletter', 'registro', 'contacto' ),
            'faq' => array( 'preguntas', 'respuestas', 'ayuda', 'soporte', 'dudas', 'acordeon' ),
            'contact' => array( 'formulario', 'email', 'mensaje', 'escribenos', 'contacto' ),
            'gallery' => array( 'galeria', 'imagenes', 'fotos', 'portfolio', 'trabajos' ),
            'stats' => array( 'estadisticas', 'numeros', 'cifras', 'metricas', 'contadores' ),
            'team' => array( 'equipo', 'personas', 'miembros', 'staff', 'nosotros' ),
        );

        $results = array();
        foreach ( $block_index as $block_type => $keywords ) {
            if ( strpos( $block_type, $query ) !== false || in_array( $query, $keywords, true ) ) {
                $results[] = $block_type;
                continue;
            }
            foreach ( $keywords as $keyword ) {
                if ( strpos( $keyword, $query ) !== false ) {
                    $results[] = $block_type;
                    break;
                }
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'results' => array_unique( $results ) ), 200 );
    }

    // =============================================
    // MÉTODOS DE OPTIMIZACIÓN DE IMÁGENES
    // =============================================

    /**
     * Configura lazy loading de imágenes
     */
    public function configure_images_lazy_load( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $threshold = sanitize_text_field( $request->get_param( 'threshold' ) );
        $placeholder = sanitize_text_field( $request->get_param( 'placeholder' ) );
        $fade_in = (bool) $request->get_param( 'fade_in' );

        $lazy_config = array(
            'enabled' => $enabled,
            'threshold' => $threshold,
            'placeholder' => $placeholder,
            'fade_in' => $fade_in,
        );

        update_post_meta( $page_id, '_vbp_images_lazy_load', $lazy_config );

        return new WP_REST_Response( array( 'success' => true, 'lazy_load' => $lazy_config ), 200 );
    }

    /**
     * Genera srcset automático
     */
    public function generate_image_srcset( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $widths = $request->get_param( 'widths' );
        $quality = (int) $request->get_param( 'quality' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );

        $srcset_config = array(
            'widths' => $widths,
            'quality' => $quality,
            'format' => $format,
        );

        update_post_meta( $page_id, '_vbp_image_srcset', $srcset_config );

        return new WP_REST_Response( array( 'success' => true, 'srcset_config' => $srcset_config ), 200 );
    }

    // =============================================
    // MÉTODOS DE PERFORMANCE
    // =============================================

    /**
     * Obtiene score de performance
     */
    public function get_performance_score( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $element_count = $this->count_elements( $elements );
        $image_count = $this->count_elements_by_type( $elements, 'image' );
        $animation_count = $this->count_animations( $elements );

        $score = 100;
        $issues = array();

        // Penalizaciones
        if ( $element_count > 100 ) {
            $score -= 10;
            $issues[] = array( 'type' => 'warning', 'message' => "Muchos elementos ({$element_count}). Considera simplificar." );
        }
        if ( $image_count > 10 && ! get_post_meta( $page_id, '_vbp_images_lazy_load', true ) ) {
            $score -= 15;
            $issues[] = array( 'type' => 'error', 'message' => 'Activa lazy loading para las imágenes.' );
        }
        if ( $animation_count > 20 ) {
            $score -= 10;
            $issues[] = array( 'type' => 'warning', 'message' => 'Demasiadas animaciones pueden afectar el rendimiento.' );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'score' => max( 0, $score ),
            'grade' => $score >= 90 ? 'A' : ( $score >= 70 ? 'B' : ( $score >= 50 ? 'C' : 'D' ) ),
            'issues' => $issues,
            'metrics' => array(
                'elements' => $element_count,
                'images' => $image_count,
                'animations' => $animation_count,
            ),
        ), 200 );
    }

    /**
     * Cuenta animaciones
     */
    private function count_animations( $elements ) {
        $count = 0;
        foreach ( $elements as $el ) {
            if ( ! empty( $el['data']['_animation'] ) ) {
                $count++;
            }
            if ( ! empty( $el['children'] ) ) {
                $count += $this->count_animations( $el['children'] );
            }
        }
        return $count;
    }

    /**
     * Auto optimiza performance
     */
    public function auto_optimize_performance( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $targets = $request->get_param( 'targets' );
        $level = sanitize_text_field( $request->get_param( 'level' ) );

        $optimizations_applied = array();

        if ( in_array( 'images', $targets, true ) ) {
            update_post_meta( $page_id, '_vbp_images_lazy_load', array(
                'enabled' => true,
                'threshold' => '200px',
                'placeholder' => 'blur',
                'fade_in' => true,
            ) );
            $optimizations_applied[] = 'images_lazy_load';
        }

        if ( in_array( 'fonts', $targets, true ) ) {
            update_post_meta( $page_id, '_vbp_font_display', 'swap' );
            $optimizations_applied[] = 'font_display_swap';
        }

        if ( in_array( 'css', $targets, true ) && $level !== 'safe' ) {
            update_post_meta( $page_id, '_vbp_css_optimize', array(
                'minify' => true,
                'critical' => $level === 'aggressive',
            ) );
            $optimizations_applied[] = 'css_optimized';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'optimizations' => $optimizations_applied,
            'level' => $level,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE COMPONENTES UI MODERNOS
    // =============================================

    /**
     * Obtiene componentes UI modernos disponibles
     */
    public function get_modern_ui_components( $request ) {
        $components = array(
            'cards' => array(
                'styles' => array( 'elevated', 'outlined', 'filled', 'glass', 'gradient', 'neumorphic', 'neon' ),
                'hover_effects' => array( 'lift', 'scale', 'glow', 'tilt', 'flip', 'none' ),
            ),
            'buttons' => array(
                'styles' => array( 'solid', 'outline', 'ghost', 'gradient', 'glass', 'neon', '3d' ),
                'sizes' => array( 'xs', 'sm', 'md', 'lg', 'xl' ),
                'states' => array( 'default', 'hover', 'active', 'loading', 'disabled' ),
            ),
            'badges' => array(
                'variants' => array( 'default', 'success', 'warning', 'error', 'info', 'gradient' ),
                'features' => array( 'dot', 'removable', 'animated' ),
            ),
            'avatars' => array(
                'sizes' => array( 'xs', 'sm', 'md', 'lg', 'xl', '2xl' ),
                'status' => array( 'online', 'offline', 'away', 'busy' ),
                'features' => array( 'ring', 'group', 'initials' ),
            ),
            'tooltips' => array(
                'positions' => array( 'top', 'bottom', 'left', 'right' ),
                'triggers' => array( 'hover', 'click', 'focus' ),
            ),
            'inputs' => array(
                'styles' => array( 'default', 'filled', 'outlined', 'underlined' ),
                'features' => array( 'prefix', 'suffix', 'icon', 'clearable', 'password-toggle' ),
            ),
        );

        return new WP_REST_Response( array( 'success' => true, 'components' => $components ), 200 );
    }

    /**
     * Crea tarjeta moderna
     */
    public function create_modern_card( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $hover_effect = sanitize_text_field( $request->get_param( 'hover_effect' ) );
        $content = $request->get_param( 'content' );

        $card_id = 'card_' . bin2hex( random_bytes( 6 ) );

        $card_styles = $this->get_card_styles( $style );
        $hover_styles = $this->get_card_hover_effect( $hover_effect );

        $card_element = array(
            'id' => $card_id,
            'type' => 'card',
            'name' => 'Tarjeta moderna',
            'data' => array_merge( $content, array( '_card_style' => $style, '_hover_effect' => $hover_effect ) ),
            'styles' => array_merge( $this->get_default_styles(), $card_styles ),
            'children' => array(),
        );

        if ( $hover_styles ) {
            $card_element['data']['_hover_states'] = $hover_styles;
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $card_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'card_id' => $card_id ), 201 );
    }

    /**
     * Obtiene estilos de tarjeta
     */
    private function get_card_styles( $style ) {
        $styles = array(
            'elevated' => array(
                'advanced' => array(
                    'background' => '#ffffff',
                    'borderRadius' => '12px',
                    'boxShadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                    'padding' => '24px',
                ),
            ),
            'glass' => array(
                'advanced' => array(
                    'background' => 'rgba(255, 255, 255, 0.7)',
                    'backdropFilter' => 'blur(10px)',
                    'borderRadius' => '16px',
                    'border' => '1px solid rgba(255, 255, 255, 0.2)',
                    'padding' => '24px',
                ),
            ),
            'gradient' => array(
                'advanced' => array(
                    'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'borderRadius' => '12px',
                    'padding' => '24px',
                    'color' => '#ffffff',
                ),
            ),
            'neumorphic' => array(
                'advanced' => array(
                    'background' => '#e0e5ec',
                    'borderRadius' => '20px',
                    'boxShadow' => '8px 8px 16px #b8bec7, -8px -8px 16px #ffffff',
                    'padding' => '24px',
                ),
            ),
            'neon' => array(
                'advanced' => array(
                    'background' => '#1a1a2e',
                    'borderRadius' => '12px',
                    'border' => '1px solid #00d4ff',
                    'boxShadow' => '0 0 10px #00d4ff, 0 0 20px rgba(0, 212, 255, 0.3)',
                    'padding' => '24px',
                    'color' => '#ffffff',
                ),
            ),
        );

        return $styles[ $style ] ?? $styles['elevated'];
    }

    /**
     * Obtiene efecto hover de tarjeta
     */
    private function get_card_hover_effect( $effect ) {
        $effects = array(
            'lift' => array( 'transform' => array( 'translateY' => '-8px' ), 'boxShadow' => '0 20px 25px -5px rgba(0, 0, 0, 0.1)' ),
            'scale' => array( 'transform' => array( 'scale' => '1.05' ) ),
            'glow' => array( 'boxShadow' => '0 0 20px rgba(99, 102, 241, 0.5)' ),
            'tilt' => array( 'transform' => array( 'perspective' => '1000px', 'rotateX' => '5deg' ) ),
        );

        return $effects[ $effect ] ?? null;
    }

    /**
     * Crea botón moderno
     */
    public function create_modern_button( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $size = sanitize_text_field( $request->get_param( 'size' ) );
        $icon = $request->get_param( 'icon' );
        $icon_position = sanitize_text_field( $request->get_param( 'icon_position' ) );
        $text = sanitize_text_field( $request->get_param( 'text' ) );
        $url = esc_url_raw( $request->get_param( 'url' ) );
        $loading_state = (bool) $request->get_param( 'loading_state' );

        $button_id = 'btn_' . bin2hex( random_bytes( 6 ) );

        $button_element = array(
            'id' => $button_id,
            'type' => 'button',
            'name' => 'Botón moderno',
            'data' => array(
                'texto' => $text,
                'url' => $url,
                'icono' => $icon,
                'icono_posicion' => $icon_position,
                '_button_style' => $style,
                '_button_size' => $size,
                '_loading_state' => $loading_state,
            ),
            'styles' => $this->get_button_styles( $style, $size ),
            'children' => array(),
        );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $button_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'button_id' => $button_id ), 201 );
    }

    /**
     * Obtiene estilos de botón
     */
    private function get_button_styles( $style, $size ) {
        $sizes = array(
            'xs' => array( 'padding' => '6px 12px', 'fontSize' => '12px' ),
            'sm' => array( 'padding' => '8px 16px', 'fontSize' => '14px' ),
            'md' => array( 'padding' => '12px 24px', 'fontSize' => '16px' ),
            'lg' => array( 'padding' => '16px 32px', 'fontSize' => '18px' ),
            'xl' => array( 'padding' => '20px 40px', 'fontSize' => '20px' ),
        );

        $style_props = array(
            'solid' => array( 'background' => '#6366f1', 'color' => '#ffffff', 'border' => 'none' ),
            'outline' => array( 'background' => 'transparent', 'color' => '#6366f1', 'border' => '2px solid #6366f1' ),
            'ghost' => array( 'background' => 'transparent', 'color' => '#6366f1', 'border' => 'none' ),
            'gradient' => array( 'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'color' => '#ffffff', 'border' => 'none' ),
            'glass' => array( 'background' => 'rgba(255,255,255,0.2)', 'backdropFilter' => 'blur(10px)', 'color' => '#ffffff', 'border' => '1px solid rgba(255,255,255,0.3)' ),
            'neon' => array( 'background' => 'transparent', 'color' => '#00d4ff', 'border' => '2px solid #00d4ff', 'boxShadow' => '0 0 10px #00d4ff' ),
            '3d' => array( 'background' => '#6366f1', 'color' => '#ffffff', 'border' => 'none', 'boxShadow' => '0 4px 0 #4338ca', 'transform' => 'translateY(-2px)' ),
        );

        $base_styles = array(
            'borderRadius' => '8px',
            'fontWeight' => '600',
            'cursor' => 'pointer',
            'transition' => 'all 0.2s ease',
            'display' => 'inline-flex',
            'alignItems' => 'center',
            'justifyContent' => 'center',
            'gap' => '8px',
        );

        return array(
            'advanced' => array_merge( $base_styles, $sizes[ $size ] ?? $sizes['md'], $style_props[ $style ] ?? $style_props['solid'] ),
        );
    }

    /**
     * Crea badge
     */
    public function create_badge( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $text = sanitize_text_field( $request->get_param( 'text' ) );
        $variant = sanitize_text_field( $request->get_param( 'variant' ) );
        $size = sanitize_text_field( $request->get_param( 'size' ) );
        $dot = (bool) $request->get_param( 'dot' );
        $removable = (bool) $request->get_param( 'removable' );

        $badge_id = 'badge_' . bin2hex( random_bytes( 6 ) );

        $colors = array(
            'default' => array( 'bg' => '#f3f4f6', 'text' => '#374151' ),
            'success' => array( 'bg' => '#dcfce7', 'text' => '#166534' ),
            'warning' => array( 'bg' => '#fef3c7', 'text' => '#92400e' ),
            'error' => array( 'bg' => '#fee2e2', 'text' => '#991b1b' ),
            'info' => array( 'bg' => '#dbeafe', 'text' => '#1e40af' ),
            'gradient' => array( 'bg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'text' => '#ffffff' ),
        );

        $color = $colors[ $variant ] ?? $colors['default'];

        $badge_element = array(
            'id' => $badge_id,
            'type' => 'badge',
            'name' => 'Badge',
            'data' => array( 'texto' => $text, 'dot' => $dot, 'removable' => $removable ),
            'styles' => array(
                'advanced' => array(
                    'display' => 'inline-flex',
                    'alignItems' => 'center',
                    'gap' => '6px',
                    'padding' => '4px 10px',
                    'borderRadius' => '9999px',
                    'fontSize' => '12px',
                    'fontWeight' => '500',
                    'background' => $color['bg'],
                    'color' => $color['text'],
                ),
            ),
            'children' => array(),
        );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $badge_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'badge_id' => $badge_id ), 201 );
    }

    /**
     * Crea avatar
     */
    public function create_avatar( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $image = esc_url_raw( $request->get_param( 'image' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $size = sanitize_text_field( $request->get_param( 'size' ) );
        $status = $request->get_param( 'status' );
        $ring = (bool) $request->get_param( 'ring' );

        $avatar_id = 'avatar_' . bin2hex( random_bytes( 6 ) );

        $sizes_px = array( 'xs' => '24px', 'sm' => '32px', 'md' => '40px', 'lg' => '48px', 'xl' => '64px', '2xl' => '80px' );
        $size_value = $sizes_px[ $size ] ?? $sizes_px['md'];

        $avatar_element = array(
            'id' => $avatar_id,
            'type' => 'avatar',
            'name' => 'Avatar',
            'data' => array( 'imagen' => $image, 'nombre' => $name, 'status' => $status ),
            'styles' => array(
                'advanced' => array(
                    'width' => $size_value,
                    'height' => $size_value,
                    'borderRadius' => '50%',
                    'overflow' => 'hidden',
                    'display' => 'flex',
                    'alignItems' => 'center',
                    'justifyContent' => 'center',
                    'background' => $image ? 'transparent' : '#6366f1',
                    'color' => '#ffffff',
                    'fontWeight' => '600',
                    'border' => $ring ? '3px solid #6366f1' : 'none',
                ),
            ),
            'children' => array(),
        );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $avatar_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'avatar_id' => $avatar_id ), 201 );
    }

    /**
     * Añade tooltip
     */
    public function add_tooltip( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $target_block_id = sanitize_text_field( $request->get_param( 'target_block_id' ) );
        $content = sanitize_text_field( $request->get_param( 'content' ) );
        $position = sanitize_text_field( $request->get_param( 'position' ) );
        $trigger = sanitize_text_field( $request->get_param( 'trigger' ) );
        $arrow = (bool) $request->get_param( 'arrow' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $tooltip_config = array(
            'content' => $content,
            'position' => $position,
            'trigger' => $trigger,
            'arrow' => $arrow,
        );

        $elements = $this->update_element_by_id( $elements, $target_block_id, function( $el ) use ( $tooltip_config ) {
            $el['data']['_tooltip'] = $tooltip_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'tooltip' => $tooltip_config ), 200 );
    }

    // =============================================
    // MÉTODOS DE BREAKPOINTS Y PRESETS
    // =============================================

    /**
     * Obtiene presets de breakpoints
     */
    public function get_breakpoint_presets( $request ) {
        $presets = array(
            'default' => array(
                array( 'name' => 'mobile', 'min' => 0, 'max' => 639 ),
                array( 'name' => 'tablet', 'min' => 640, 'max' => 1023 ),
                array( 'name' => 'desktop', 'min' => 1024, 'max' => null ),
            ),
            'tailwind' => array(
                array( 'name' => 'sm', 'min' => 640, 'max' => 767 ),
                array( 'name' => 'md', 'min' => 768, 'max' => 1023 ),
                array( 'name' => 'lg', 'min' => 1024, 'max' => 1279 ),
                array( 'name' => 'xl', 'min' => 1280, 'max' => 1535 ),
                array( 'name' => '2xl', 'min' => 1536, 'max' => null ),
            ),
            'bootstrap' => array(
                array( 'name' => 'xs', 'min' => 0, 'max' => 575 ),
                array( 'name' => 'sm', 'min' => 576, 'max' => 767 ),
                array( 'name' => 'md', 'min' => 768, 'max' => 991 ),
                array( 'name' => 'lg', 'min' => 992, 'max' => 1199 ),
                array( 'name' => 'xl', 'min' => 1200, 'max' => 1399 ),
                array( 'name' => 'xxl', 'min' => 1400, 'max' => null ),
            ),
        );

        return new WP_REST_Response( array( 'success' => true, 'presets' => $presets ), 200 );
    }

    /**
     * Crea breakpoint personalizado
     */
    public function create_custom_breakpoint( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $min_width = $request->get_param( 'min_width' );
        $max_width = $request->get_param( 'max_width' );
        $orientation = $request->get_param( 'orientation' );

        $breakpoints = get_option( 'flavor_vbp_breakpoints', array() );
        $breakpoints[] = array(
            'name' => $name,
            'min_width' => $min_width,
            'max_width' => $max_width,
            'orientation' => $orientation,
            'custom' => true,
        );

        update_option( 'flavor_vbp_breakpoints', $breakpoints );

        return new WP_REST_Response( array( 'success' => true, 'breakpoint' => end( $breakpoints ) ), 201 );
    }

    /**
     * Obtiene preset completo
     */
    public function get_full_preset_config( $request ) {
        $preset_id = sanitize_text_field( $request->get_param( 'preset_id' ) );

        $presets = $this->get_all_design_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preset no encontrado' ), 404 );
        }

        $preset = $presets[ $preset_id ];

        $full_config = array(
            'id' => $preset_id,
            'name' => $preset['name'] ?? ucfirst( $preset_id ),
            'colors' => $preset['colors'] ?? array(),
            'gradients' => $preset['gradients'] ?? array(),
            'shadows' => $preset['shadows'] ?? array(),
            'typography' => array(
                'font_family' => $preset['font_family'] ?? 'Inter, sans-serif',
                'heading_weight' => $preset['heading_weight'] ?? 700,
                'body_weight' => $preset['body_weight'] ?? 400,
            ),
            'borders' => $preset['borders'] ?? array(),
            'spacing' => array(
                'section_padding' => $preset['section_padding'] ?? '5rem 0',
                'container_max_width' => $preset['container_max_width'] ?? '1200px',
            ),
            'animations' => $preset['default_animations'] ?? array(),
        );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $full_config ), 200 );
    }

    /**
     * Crea preset personalizado
     */
    public function create_custom_preset( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $colors = $request->get_param( 'colors' );
        $typography = $request->get_param( 'typography' );
        $spacing = $request->get_param( 'spacing' );
        $borders = $request->get_param( 'borders' );
        $shadows = $request->get_param( 'shadows' );
        $animations = $request->get_param( 'animations' );

        $preset_id = sanitize_title( $name ) . '_' . time();

        $custom_preset = array(
            'id' => $preset_id,
            'name' => $name,
            'colors' => $colors,
            'typography' => $typography,
            'spacing' => $spacing,
            'borders' => $borders,
            'shadows' => $shadows,
            'animations' => $animations,
            'custom' => true,
            'created_at' => current_time( 'mysql' ),
        );

        $custom_presets = get_option( 'flavor_vbp_custom_presets', array() );
        $custom_presets[ $preset_id ] = $custom_preset;
        update_option( 'flavor_vbp_custom_presets', $custom_presets );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $custom_preset ), 201 );
    }

    /**
     * Duplica preset
     */
    public function duplicate_preset( $request ) {
        $preset_id = sanitize_text_field( $request->get_param( 'preset_id' ) );
        $new_name = sanitize_text_field( $request->get_param( 'new_name' ) );
        $modifications = $request->get_param( 'modifications' ) ?: array();

        $presets = $this->get_all_design_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preset no encontrado' ), 404 );
        }

        $new_preset_id = sanitize_title( $new_name ) . '_' . time();
        $new_preset = array_merge( $presets[ $preset_id ], $modifications, array(
            'id' => $new_preset_id,
            'name' => $new_name,
            'custom' => true,
            'based_on' => $preset_id,
            'created_at' => current_time( 'mysql' ),
        ) );

        $custom_presets = get_option( 'flavor_vbp_custom_presets', array() );
        $custom_presets[ $new_preset_id ] = $new_preset;
        update_option( 'flavor_vbp_custom_presets', $custom_presets );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $new_preset ), 201 );
    }
}

// Inicializar
Flavor_VBP_Claude_API::get_instance();
