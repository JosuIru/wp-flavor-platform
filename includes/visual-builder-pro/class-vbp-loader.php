<?php
/**
 * Visual Builder Pro - Loader
 *
 * Carga e inicializa todos los componentes del Visual Builder Pro.
 *
 * @package Flavor_Chat_IA
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
        $this->ruta_base = FLAVOR_CHAT_IA_PATH . 'includes/visual-builder-pro/';
        $this->url_base  = FLAVOR_CHAT_IA_URL . 'includes/visual-builder-pro/';

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
            'class-vbp-single-templates.php',
            'ai/class-vbp-ai-content.php',
        );

        foreach ( $archivos as $archivo ) {
            $ruta_archivo = $this->ruta_base . $archivo;
            if ( file_exists( $ruta_archivo ) ) {
                require_once $ruta_archivo;
            }
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

        // Single Templates (plantillas VBP para singles de cualquier CPT)
        if ( class_exists( 'Flavor_VBP_Single_Templates' ) ) {
            Flavor_VBP_Single_Templates::get_instance();
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
                    'name'          => __( 'Templates VBP', 'flavor-chat-ia' ),
                    'singular_name' => __( 'Template VBP', 'flavor-chat-ia' ),
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
