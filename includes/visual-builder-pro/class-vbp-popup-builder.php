<?php
/**
 * Visual Builder Pro - Popup Builder
 *
 * Sistema de popups/modales configurables.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para el sistema de Popups
 *
 * @since 2.0.0
 */
class Flavor_VBP_Popup_Builder {

    /**
     * Nombre del post type
     *
     * @var string
     */
    const POST_TYPE = 'vbp_popup';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Popup_Builder|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Popup_Builder
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
        add_action( 'init', array( $this, 'registrar_post_type' ) );
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
        add_action( 'wp_footer', array( $this, 'renderizar_popups_activos' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'cargar_assets_frontend' ) );
    }

    /**
     * Registra el CPT para popups
     */
    public function registrar_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => array(
                    'name'               => __( 'Popups', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'singular_name'      => __( 'Popup', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'add_new'            => __( 'Añadir nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'add_new_item'       => __( 'Añadir nuevo Popup', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'edit_item'          => __( 'Editar Popup', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'new_item'           => __( 'Nuevo Popup', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'view_item'          => __( 'Ver Popup', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'search_items'       => __( 'Buscar Popups', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'not_found'          => __( 'No se encontraron popups', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'not_found_in_trash' => __( 'No hay popups en la papelera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'show_ui'             => true,
                'show_in_menu'        => 'edit.php?post_type=flavor_landing',
                'show_in_rest'        => true,
                'rest_base'           => 'vbp-popups',
                'capability_type'     => 'post',
                'supports'            => array( 'title', 'author' ),
                'menu_icon'           => 'dashicons-welcome-widgets-menus',
            )
        );
    }

    /**
     * Registra las rutas REST API
     */
    public function registrar_rutas_rest() {
        $namespace = 'flavor-vbp/v1';

        // Listar todos los popups
        register_rest_route(
            $namespace,
            '/popups',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'listar_popups' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_popup' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Obtener/actualizar/eliminar popup específico
        register_rest_route(
            $namespace,
            '/popups/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_popup' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'actualizar_popup' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'eliminar_popup' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Duplicar popup
        register_rest_route(
            $namespace,
            '/popups/(?P<id>\d+)/duplicate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'duplicar_popup' ),
                'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
            )
        );
    }

    /**
     * Verifica permiso de lectura
     *
     * @return bool
     */
    public function verificar_permiso_lectura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permiso de escritura
     *
     * @return bool
     */
    public function verificar_permiso_escritura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Lista todos los popups
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function listar_popups( $request ) {
        $popups = get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'posts_per_page' => 100,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $resultado = array();
        foreach ( $popups as $popup ) {
            $config = get_post_meta( $popup->ID, '_vbp_popup_config', true ) ?: array();
            $resultado[] = array(
                'id'        => $popup->ID,
                'title'     => $popup->post_title,
                'status'    => $popup->post_status,
                'isActive'  => isset( $config['isActive'] ) ? $config['isActive'] : false,
                'trigger'   => isset( $config['trigger'] ) ? $config['trigger'] : 'time',
                'author'    => get_the_author_meta( 'display_name', $popup->post_author ),
                'modified'  => get_the_modified_date( 'd M Y H:i', $popup ),
            );
        }

        return new WP_REST_Response( $resultado, 200 );
    }

    /**
     * Crea un nuevo popup
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function crear_popup( $request ) {
        $titulo = sanitize_text_field( $request->get_param( 'title' ) );

        if ( empty( $titulo ) ) {
            $titulo = __( 'Nuevo Popup', FLAVOR_PLATFORM_TEXT_DOMAIN );
        }

        // Config por defecto
        $config_default = array(
            'isActive'       => false,
            'trigger'        => 'time', // time, scroll, exit_intent, click
            'triggerDelay'   => 3,       // segundos para trigger time
            'triggerScroll'  => 50,      // porcentaje para trigger scroll
            'triggerElement' => '',      // selector CSS para trigger click
            'frequency'      => 'once',  // once, session, always
            'showOnPages'    => 'all',   // all, specific, exclude
            'pages'          => array(),
            'animation'      => 'fade',  // fade, slide-up, slide-down, zoom, bounce
            'position'       => 'center', // center, top, bottom, left, right
            'size'           => 'medium', // small, medium, large, fullscreen
            'overlayColor'   => 'rgba(0,0,0,0.5)',
            'closeOnOverlay' => true,
            'closeOnEsc'     => true,
            'showCloseButton' => true,
        );

        // Crear el post
        $popup_id = wp_insert_post(
            array(
                'post_title'  => $titulo,
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $popup_id ) ) {
            return new WP_REST_Response(
                array( 'error' => $popup_id->get_error_message() ),
                500
            );
        }

        // Guardar config y contenido vacío
        update_post_meta( $popup_id, '_vbp_popup_config', $config_default );
        update_post_meta( $popup_id, '_vbp_popup_content', array() );

        return new WP_REST_Response(
            array(
                'id'      => $popup_id,
                'title'   => $titulo,
                'config'  => $config_default,
                'message' => __( 'Popup creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            201
        );
    }

    /**
     * Obtiene un popup específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_popup( $request ) {
        $popup_id = absint( $request->get_param( 'id' ) );
        $popup    = get_post( $popup_id );

        if ( ! $popup || self::POST_TYPE !== $popup->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Popup no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        $config  = get_post_meta( $popup_id, '_vbp_popup_config', true ) ?: array();
        $content = get_post_meta( $popup_id, '_vbp_popup_content', true ) ?: array();

        return new WP_REST_Response(
            array(
                'id'      => $popup->ID,
                'title'   => $popup->post_title,
                'status'  => $popup->post_status,
                'config'  => $config,
                'content' => $content,
            ),
            200
        );
    }

    /**
     * Actualiza un popup
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function actualizar_popup( $request ) {
        $popup_id = absint( $request->get_param( 'id' ) );
        $popup    = get_post( $popup_id );

        if ( ! $popup || self::POST_TYPE !== $popup->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Popup no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        // Actualizar título si se proporciona
        $titulo = $request->get_param( 'title' );
        if ( $titulo ) {
            wp_update_post(
                array(
                    'ID'         => $popup_id,
                    'post_title' => sanitize_text_field( $titulo ),
                )
            );
        }

        // Actualizar config si se proporciona
        $config = $request->get_param( 'config' );
        if ( $config ) {
            $config_actual = get_post_meta( $popup_id, '_vbp_popup_config', true ) ?: array();
            $config_nueva  = array_merge( $config_actual, $config );
            update_post_meta( $popup_id, '_vbp_popup_config', $config_nueva );
        }

        // Actualizar contenido si se proporciona
        $content = $request->get_param( 'content' );
        if ( $content !== null ) {
            update_post_meta( $popup_id, '_vbp_popup_content', $content );
        }

        return new WP_REST_Response(
            array(
                'id'      => $popup_id,
                'message' => __( 'Popup actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            200
        );
    }

    /**
     * Elimina un popup
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function eliminar_popup( $request ) {
        $popup_id = absint( $request->get_param( 'id' ) );
        $popup    = get_post( $popup_id );

        if ( ! $popup || self::POST_TYPE !== $popup->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Popup no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        wp_delete_post( $popup_id, true );

        return new WP_REST_Response(
            array( 'message' => __( 'Popup eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            200
        );
    }

    /**
     * Duplica un popup
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function duplicar_popup( $request ) {
        $popup_id = absint( $request->get_param( 'id' ) );
        $popup    = get_post( $popup_id );

        if ( ! $popup || self::POST_TYPE !== $popup->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Popup no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        // Crear copia
        $nuevo_popup_id = wp_insert_post(
            array(
                'post_title'  => $popup->post_title . ' ' . __( '(copia)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $nuevo_popup_id ) ) {
            return new WP_REST_Response(
                array( 'error' => $nuevo_popup_id->get_error_message() ),
                500
            );
        }

        // Copiar meta
        $config  = get_post_meta( $popup_id, '_vbp_popup_config', true );
        $content = get_post_meta( $popup_id, '_vbp_popup_content', true );

        // Desactivar el duplicado por defecto
        if ( is_array( $config ) ) {
            $config['isActive'] = false;
        }

        update_post_meta( $nuevo_popup_id, '_vbp_popup_config', $config );
        update_post_meta( $nuevo_popup_id, '_vbp_popup_content', $content );

        return new WP_REST_Response(
            array(
                'id'      => $nuevo_popup_id,
                'message' => __( 'Popup duplicado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            201
        );
    }

    /**
     * Carga assets del frontend
     */
    public function cargar_assets_frontend() {
        if ( is_admin() ) {
            return;
        }

        // Solo cargar si hay popups activos
        $popups_activos = $this->obtener_popups_activos();
        if ( empty( $popups_activos ) ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'vbp-popup-frontend',
            FLAVOR_PLATFORM_URL . 'assets/vbp/css/popup-frontend.css',
            array(),
            '2.0.0'
        );

        // JS
        wp_enqueue_script(
            'vbp-popup-frontend',
            FLAVOR_PLATFORM_URL . 'assets/vbp/js/vbp-popup.js',
            array(),
            '2.0.0',
            true
        );
    }

    /**
     * Renderiza popups activos en el footer
     */
    public function renderizar_popups_activos() {
        if ( is_admin() ) {
            return;
        }

        $popups = $this->obtener_popups_activos();
        if ( empty( $popups ) ) {
            return;
        }

        foreach ( $popups as $popup ) {
            $this->renderizar_popup( $popup );
        }
    }

    /**
     * Obtiene popups activos para la página actual
     *
     * @return array
     */
    private function obtener_popups_activos() {
        $todos_los_popups = get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'posts_per_page' => 100,
                'post_status'    => 'publish',
            )
        );

        $popups_activos = array();
        $pagina_actual  = get_the_ID();

        foreach ( $todos_los_popups as $popup ) {
            $config = get_post_meta( $popup->ID, '_vbp_popup_config', true );

            if ( empty( $config['isActive'] ) ) {
                continue;
            }

            // Verificar páginas
            $mostrar = true;
            if ( isset( $config['showOnPages'] ) ) {
                switch ( $config['showOnPages'] ) {
                    case 'specific':
                        $mostrar = ! empty( $config['pages'] ) && in_array( $pagina_actual, $config['pages'], true );
                        break;
                    case 'exclude':
                        $mostrar = empty( $config['pages'] ) || ! in_array( $pagina_actual, $config['pages'], true );
                        break;
                    default:
                        $mostrar = true;
                }
            }

            if ( $mostrar ) {
                $popups_activos[] = $popup;
            }
        }

        return $popups_activos;
    }

    /**
     * Renderiza un popup individual
     *
     * @param WP_Post $popup El popup a renderizar.
     */
    private function renderizar_popup( $popup ) {
        $config  = get_post_meta( $popup->ID, '_vbp_popup_config', true ) ?: array();
        $content = get_post_meta( $popup->ID, '_vbp_popup_content', true ) ?: array();

        // Preparar data attributes
        $data_attrs = array(
            'popup-id'        => $popup->ID,
            'trigger'         => $config['trigger'] ?? 'time',
            'trigger-delay'   => $config['triggerDelay'] ?? 3,
            'trigger-scroll'  => $config['triggerScroll'] ?? 50,
            'trigger-element' => $config['triggerElement'] ?? '',
            'frequency'       => $config['frequency'] ?? 'once',
            'animation'       => $config['animation'] ?? 'fade',
            'position'        => $config['position'] ?? 'center',
            'close-overlay'   => $config['closeOnOverlay'] ? 'true' : 'false',
            'close-esc'       => $config['closeOnEsc'] ? 'true' : 'false',
        );

        $data_string = '';
        foreach ( $data_attrs as $key => $value ) {
            $data_string .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }

        // Clases
        $clases = array(
            'vbp-popup',
            'vbp-popup--' . esc_attr( $config['size'] ?? 'medium' ),
            'vbp-popup--' . esc_attr( $config['position'] ?? 'center' ),
            'vbp-popup--' . esc_attr( $config['animation'] ?? 'fade' ),
        );

        // Estilos del overlay
        $overlay_style = '';
        if ( ! empty( $config['overlayColor'] ) ) {
            $overlay_style = 'background-color: ' . esc_attr( $config['overlayColor'] ) . ';';
        }

        // Renderizar HTML
        ?>
        <div class="vbp-popup-overlay" style="<?php echo $overlay_style; ?>" <?php echo $data_string; ?>>
            <div class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>">
                <?php if ( ! empty( $config['showCloseButton'] ) ) : ?>
                <button type="button" class="vbp-popup-close" aria-label="<?php esc_attr_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
                <?php endif; ?>
                <div class="vbp-popup-content">
                    <?php
                    // Renderizar contenido usando el canvas
                    if ( class_exists( 'Flavor_VBP_Canvas' ) && ! empty( $content ) ) {
                        $canvas = Flavor_VBP_Canvas::get_instance();
                        foreach ( $content as $elemento ) {
                            echo $canvas->renderizar_elemento( $elemento );
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los datos de un popup para el editor
     *
     * @param int $popup_id ID del popup.
     * @return array|null
     */
    public function get_popup_data( $popup_id ) {
        $popup = get_post( $popup_id );

        if ( ! $popup || self::POST_TYPE !== $popup->post_type ) {
            return null;
        }

        return array(
            'id'      => $popup->ID,
            'title'   => $popup->post_title,
            'config'  => get_post_meta( $popup_id, '_vbp_popup_config', true ) ?: array(),
            'content' => get_post_meta( $popup_id, '_vbp_popup_content', true ) ?: array(),
        );
    }
}
