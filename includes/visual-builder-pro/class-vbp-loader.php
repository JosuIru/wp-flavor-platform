<?php
/**
 * Visual Builder Pro - Loader
 *
 * Carga e inicializa todos los componentes del Visual Builder Pro.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase de carga del Visual Builder Pro
 *
 * @since 2.0.0
 */
class Flavor_VBP_Loader {

    /**
     * Versión del VBP
     *
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Loader|null
     */
    private static $instancia = null;

    /**
     * Ruta base del VBP
     *
     * @var string
     */
    private $ruta_base;

    /**
     * URL base del VBP
     *
     * @var string
     */
    private $url_base;

    /**
     * Indica si el VBP está inicializado
     *
     * @var bool
     */
    private $inicializado = false;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Loader
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
        $this->ruta_base = FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/';
        $this->url_base  = FLAVOR_PLATFORM_URL . 'includes/visual-builder-pro/';

        $this->cargar_archivos();
        $this->inicializar_componentes();
    }

    /**
     * Carga los archivos necesarios
     */
    private function cargar_archivos() {
        $archivos = array(
            'class-vbp-editor.php',
            'class-vbp-block-library.php',
            'class-vbp-canvas.php',
            'class-vbp-rest-api.php',
            'class-vbp-form-handler.php',
            'class-vbp-global-widgets.php',
            'class-vbp-unsplash.php',
            'class-vbp-popup-builder.php',
            'class-vbp-ab-testing.php',
            'class-vbp-version-history.php',
            'class-vbp-branching.php',
            'class-vbp-single-templates.php',
            'class-vbp-component-library.php',
            'class-vbp-design-presets.php',
            'class-vbp-comments.php',
            'class-vbp-collaboration-api.php',
            'class-vbp-realtime-server.php',
            'class-vbp-audit-log.php',
            'class-vbp-workflows.php',
            'class-vbp-multisite.php',
            'class-vbp-settings.php',
            'class-vbp-symbols.php',
            'class-vbp-symbols-api.php',
            'class-vbp-asset-manager.php',
            'class-vbp-global-styles.php',
            'class-vbp-plugin-system.php',
            'class-vbp-figma-tokens.php',
            'class-vbp-claude-api.php',
            'class-vbp-code-components.php',
            'ai/class-vbp-ai-content.php',
            'ai/class-vbp-ai-layout.php',
        );

        foreach ( $archivos as $archivo ) {
            $ruta_archivo = $this->ruta_base . $archivo;
            if ( file_exists( $ruta_archivo ) ) {
                require_once $ruta_archivo;
            }
        }

        // Herramienta de migración (solo admin)
        if ( is_admin() ) {
            $migration_tool_path = FLAVOR_PLATFORM_PATH . 'includes/tools/class-vbp-migration-tool.php';
            if ( file_exists( $migration_tool_path ) ) {
                require_once $migration_tool_path;
            }
        }

        // Exporters de código (React, Vue, Svelte)
        $exporter_path = $this->ruta_base . 'exporters/class-vbp-code-exporter.php';
        if ( file_exists( $exporter_path ) ) {
            require_once $exporter_path;
        }

        // Importador de Figma
        $figma_path = $this->ruta_base . 'importers/class-vbp-figma-importer.php';
        if ( file_exists( $figma_path ) ) {
            require_once $figma_path;
        }
    }

    /**
     * Inicializa los componentes
     */
    private function inicializar_componentes() {
        if ( $this->inicializado ) {
            return;
        }

        // Inicializar en el momento adecuado
        add_action( 'init', array( $this, 'inicializar' ), 5 );

        // Añadir headers de seguridad a respuestas REST de VBP
        add_filter( 'rest_post_dispatch', array( $this, 'add_security_headers' ), 10, 3 );
    }

    /**
     * Añade headers de seguridad a las respuestas REST de VBP
     *
     * @since 3.5.1
     * @param WP_REST_Response $response Response REST.
     * @param WP_REST_Server   $server   Server REST.
     * @param WP_REST_Request  $request  Request REST.
     * @return WP_REST_Response Response modificado.
     */
    public function add_security_headers( $response, $server, $request ) {
        $route = $request->get_route();

        // Solo añadir headers a rutas de VBP
        if ( strpos( $route, '/flavor-vbp/' ) !== false ||
             strpos( $route, '/flavor-site-builder/' ) !== false ) {

            // Prevenir MIME-type sniffing
            $response->header( 'X-Content-Type-Options', 'nosniff' );

            // Prevenir clickjacking (solo SAMEORIGIN, no bloquear iframes del editor)
            $response->header( 'X-Frame-Options', 'SAMEORIGIN' );

            // Desactivar caché para datos sensibles
            if ( strpos( $route, '/claude/' ) !== false ) {
                $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
                $response->header( 'Pragma', 'no-cache' );
            }
        }

        return $response;
    }

    /**
     * Inicializa el VBP
     */
    public function inicializar() {
        if ( $this->inicializado ) {
            return;
        }

        // Editor principal
        if ( class_exists( 'Flavor_VBP_Editor' ) ) {
            Flavor_VBP_Editor::get_instance();
        }

        // Librería de bloques
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            Flavor_VBP_Block_Library::get_instance();
        }

        // Canvas renderer
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            Flavor_VBP_Canvas::get_instance();
        }

        // REST API
        if ( class_exists( 'Flavor_VBP_REST_API' ) ) {
            Flavor_VBP_REST_API::get_instance();
        }

        // Form Handler (procesa formularios de contacto)
        if ( class_exists( 'Flavor_VBP_Form_Handler' ) ) {
            Flavor_VBP_Form_Handler::get_instance();
        }

        // Global Widgets (widgets reutilizables)
        if ( class_exists( 'Flavor_VBP_Global_Widgets' ) ) {
            Flavor_VBP_Global_Widgets::get_instance();
        }

        // Unsplash Integration
        if ( class_exists( 'Flavor_VBP_Unsplash' ) ) {
            Flavor_VBP_Unsplash::get_instance();
        }

        // Popup Builder
        if ( class_exists( 'Flavor_VBP_Popup_Builder' ) ) {
            Flavor_VBP_Popup_Builder::get_instance();
        }

        // A/B Testing
        if ( class_exists( 'Flavor_VBP_AB_Testing' ) ) {
            Flavor_VBP_AB_Testing::get_instance();
        }

        // Version History
        if ( class_exists( 'Flavor_VBP_Version_History' ) ) {
            Flavor_VBP_Version_History::get_instance();
        }

        // Branching System (ramas de diseño)
        if ( class_exists( 'Flavor_VBP_Branching' ) ) {
            Flavor_VBP_Branching::get_instance();
        }

        // Herramienta de migración de landings legacy (solo admin)
        if ( is_admin() && class_exists( 'Flavor_VBP_Migration_Tool' ) ) {
            Flavor_VBP_Migration_Tool::get_instance();
        }

        // Single Templates (plantillas VBP para singles de cualquier CPT)
        if ( class_exists( 'Flavor_VBP_Single_Templates' ) ) {
            Flavor_VBP_Single_Templates::get_instance();
        }

        // Biblioteca de componentes reutilizables
        if ( class_exists( 'Flavor_VBP_Component_Library' ) ) {
            Flavor_VBP_Component_Library::get_instance();
        }

        // Presets de diseño (colores, tipografía, espaciado)
        if ( class_exists( 'Flavor_VBP_Design_Presets' ) ) {
            Flavor_VBP_Design_Presets::get_instance();
        }

        // Sistema de comentarios colaborativos
        if ( class_exists( 'Flavor_VBP_Comments' ) ) {
            Flavor_VBP_Comments::get_instance();
        }

        // API de colaboración en tiempo real
        if ( class_exists( 'Flavor_VBP_Collaboration_API' ) ) {
            Flavor_VBP_Collaboration_API::get_instance();
        }

        // Servidor de colaboración en tiempo real (WebSockets/Heartbeat)
        if ( class_exists( 'Flavor_VBP_Realtime_Server' ) ) {
            Flavor_VBP_Realtime_Server::get_instance();
        }

        // Audit Log (Enterprise)
        if ( class_exists( 'Flavor_VBP_Audit_Log' ) ) {
            Flavor_VBP_Audit_Log::get_instance();
        }

        // Workflows (Enterprise)
        if ( class_exists( 'Flavor_VBP_Workflows' ) ) {
            Flavor_VBP_Workflows::get_instance();
        }

        // Multi-site Support (Enterprise)
        if ( class_exists( 'Flavor_VBP_Multisite' ) ) {
            Flavor_VBP_Multisite::get_instance();
        }

        // Code Exporter (exportación a React, Vue, Svelte)
        if ( class_exists( 'Flavor_VBP_Code_Exporter' ) ) {
            Flavor_VBP_Code_Exporter::get_instance();
        }

        // Figma Importer (importación de diseños)
        if ( class_exists( 'Flavor_VBP_Figma_Importer' ) ) {
            Flavor_VBP_Figma_Importer::get_instance();
        }

        // Sistema de Símbolos con Instancias Sincronizadas
        if ( class_exists( 'Flavor_VBP_Symbols' ) ) {
            Flavor_VBP_Symbols::get_instance();
        }

        // API REST de Símbolos
        if ( class_exists( 'Flavor_VBP_Symbols_API' ) ) {
            Flavor_VBP_Symbols_API::get_instance();
        }

        // Asset Manager (gestión centralizada de medios)
        if ( class_exists( 'Flavor_VBP_Asset_Manager' ) ) {
            Flavor_VBP_Asset_Manager::get_instance();
        }

        // Global Styles (estilos reutilizables)
        if ( class_exists( 'Flavor_VBP_Global_Styles' ) ) {
            Flavor_VBP_Global_Styles::get_instance();
        }

        // Plugin System (sistema de extensiones)
        if ( class_exists( 'Flavor_VBP_Plugin_System' ) ) {
            Flavor_VBP_Plugin_System::get_instance();
        }

        // Figma Tokens Sync (sincronizacion de Design Tokens)
        if ( class_exists( 'VBP_Figma_Tokens' ) ) {
            VBP_Figma_Tokens::instance();
        }

        // Claude API (endpoints optimizados para automatizacion)
        if ( class_exists( 'Flavor_VBP_Claude_API' ) ) {
            Flavor_VBP_Claude_API::get_instance();
        }

        // Code Components (React/Vue/Svelte/Vanilla)
        if ( class_exists( 'Flavor_VBP_Code_Components' ) ) {
            Flavor_VBP_Code_Components::get_instance();
        }

        // Registrar CPT de templates
        $this->registrar_cpt_templates();

        // Hook para extensiones
        do_action( 'vbp_loaded', $this );

        $this->inicializado = true;
    }

    /**
     * Registra el CPT de templates
     */
    private function registrar_cpt_templates() {
        register_post_type(
            'vbp_template',
            array(
                'labels'              => array(
                    'name'          => __( 'Templates VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'singular_name' => __( 'Template VBP', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_rest'        => false,
                'capability_type'     => 'post',
                'supports'            => array( 'title' ),
            )
        );
    }

    /**
     * Verifica si el VBP está activo para un post
     *
     * @param int $post_id ID del post.
     * @return bool
     */
    public function esta_activo_para_post( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post ) {
            return false;
        }

        $tipos_soportados = Flavor_VBP_Editor::POST_TYPES_SOPORTADOS;

        return in_array( $post->post_type, $tipos_soportados, true );
    }

    /**
     * Obtiene la URL del editor para un post
     *
     * @param int $post_id ID del post.
     * @return string
     */
    public function get_url_editor( $post_id ) {
        return add_query_arg(
            array(
                'page'    => 'vbp-editor',
                'post_id' => $post_id,
            ),
            admin_url( 'admin.php' )
        );
    }

    /**
     * Verifica si estamos en el editor VBP
     *
     * @return bool
     */
    public function esta_en_editor() {
        if ( ! is_admin() ) {
            return false;
        }

        $pantalla = get_current_screen();
        return $pantalla && 'admin_page_vbp-editor' === $pantalla->id;
    }

    /**
     * Obtiene la versión del VBP
     *
     * @return string
     */
    public function get_version() {
        return self::VERSION;
    }

    /**
     * Obtiene la ruta base
     *
     * @return string
     */
    public function get_ruta_base() {
        return $this->ruta_base;
    }

    /**
     * Obtiene la URL base
     *
     * @return string
     */
    public function get_url_base() {
        return $this->url_base;
    }
}

/**
 * Función helper para obtener la instancia del VBP Loader
 *
 * @return Flavor_VBP_Loader
 */
function flavor_vbp() {
    return Flavor_VBP_Loader::get_instance();
}
