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
require_once __DIR__ . '/traits/trait-vbp-api-analytics2.php';
require_once __DIR__ . '/traits/trait-vbp-api-feedback.php';
require_once __DIR__ . '/traits/trait-vbp-api-dynamic.php';
require_once __DIR__ . '/traits/trait-vbp-api-forms.php';
require_once __DIR__ . '/traits/trait-vbp-api-shortcuts.php';
require_once __DIR__ . '/traits/trait-vbp-api-prefs.php';
require_once __DIR__ . '/traits/trait-vbp-api-search2.php';
require_once __DIR__ . '/traits/trait-vbp-api-collab3.php';
require_once __DIR__ . '/traits/trait-vbp-api-responsive2.php';
require_once __DIR__ . '/traits/trait-vbp-api-transforms.php';
require_once __DIR__ . '/traits/trait-vbp-api-keyframes.php';
require_once __DIR__ . '/traits/trait-vbp-api-visibility.php';
require_once __DIR__ . '/traits/trait-vbp-api-dyndata.php';
require_once __DIR__ . '/traits/trait-vbp-api-cssvars.php';
require_once __DIR__ . '/traits/trait-vbp-api-versions.php';
require_once __DIR__ . '/traits/trait-vbp-api-block-alignment.php';
require_once __DIR__ . '/traits/trait-vbp-api-css-effects.php';
require_once __DIR__ . '/traits/trait-vbp-api-typography.php';
require_once __DIR__ . '/traits/trait-vbp-api-widget-lib.php';
require_once __DIR__ . '/traits/trait-vbp-api-modern-ui.php';
require_once __DIR__ . '/traits/trait-vbp-api-slots-events.php';
require_once __DIR__ . '/traits/trait-vbp-api-web-vitals.php';
require_once __DIR__ . '/traits/trait-vbp-api-cache.php';
require_once __DIR__ . '/traits/trait-vbp-api-dark-mode.php';
require_once __DIR__ . '/traits/trait-vbp-api-block-docs.php';
require_once __DIR__ . '/traits/trait-vbp-api-layout-advanced.php';
require_once __DIR__ . '/traits/trait-vbp-api-interactivity.php';
require_once __DIR__ . '/traits/trait-vbp-api-seo-meta.php';
require_once __DIR__ . '/traits/trait-vbp-api-performance.php';
require_once __DIR__ . '/traits/trait-vbp-api-block-operations.php';
require_once __DIR__ . '/traits/trait-vbp-api-previews-advanced.php';
require_once __DIR__ . '/traits/trait-vbp-api-widgets-advanced.php';
require_once __DIR__ . '/traits/trait-vbp-api-optimization-suggestions.php';
require_once __DIR__ . '/traits/trait-vbp-api-screenshots-pdf.php';
require_once __DIR__ . '/traits/trait-vbp-api-auto-optimization.php';
require_once __DIR__ . '/traits/trait-vbp-api-aria-wcag.php';
require_once __DIR__ . '/traits/trait-vbp-api-export-frameworks.php';

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
    use VBP_API_AnalyticsAdvanced;
    use VBP_API_Feedback;
    use VBP_API_DynamicComponents;
    use VBP_API_Forms;
    use VBP_API_Shortcuts;
    use VBP_API_EditorPrefs;
    use VBP_API_SearchAdvanced;
    use VBP_API_CollabRealtime;
    use VBP_API_ResponsiveDesign;
    use VBP_API_BlockTransforms;
    use VBP_API_Keyframes;
    use VBP_API_Visibility;
    use VBP_API_DynamicData;
    use VBP_API_CSSVariables;
    use VBP_API_Versions;
    use VBP_API_BlockAlignment;
    use VBP_API_CSSEffects;
    use VBP_API_Typography;
    use VBP_API_WidgetLib;
    use VBP_API_ModernUI;
    use VBP_API_SlotsEvents;
    use VBP_API_WebVitals;
    use VBP_API_Cache;
    use VBP_API_DarkMode;
    use VBP_API_BlockDocs;
    use VBP_API_LayoutAdvanced;
    use VBP_API_Interactivity;
    use VBP_API_SeoMeta;
    use VBP_API_Performance;
    use VBP_API_BlockOperations;
    use VBP_API_PreviewsAdvanced;
    use VBP_API_WidgetsAdvanced;
    use VBP_API_OptimizationSuggestions;
    use VBP_API_ScreenshotsPDF;
    use VBP_API_AutoOptimization;
    use VBP_API_AriaWcag;
    use VBP_API_ExportFrameworks;

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
}

// Inicializar
Flavor_VBP_Claude_API::get_instance();
