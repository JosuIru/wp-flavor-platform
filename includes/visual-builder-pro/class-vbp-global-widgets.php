<?php
/**
 * Visual Builder Pro - Global Widgets
 *
 * Sistema de widgets globales reutilizables.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar widgets globales
 *
 * @since 2.0.0
 */
class Flavor_VBP_Global_Widgets {

    /**
     * Nombre del post type
     *
     * @var string
     */
    const POST_TYPE = 'vbp_global_widget';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Global_Widgets|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Global_Widgets
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
    }

    /**
     * Registra el CPT para widgets globales
     */
    public function registrar_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => array(
                    'name'               => __( 'Widgets Globales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'singular_name'      => __( 'Widget Global', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'add_new'            => __( 'Añadir nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'add_new_item'       => __( 'Añadir nuevo Widget Global', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'edit_item'          => __( 'Editar Widget Global', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'new_item'           => __( 'Nuevo Widget Global', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'view_item'          => __( 'Ver Widget Global', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'search_items'       => __( 'Buscar Widgets Globales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'not_found'          => __( 'No se encontraron widgets globales', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'not_found_in_trash' => __( 'No hay widgets globales en la papelera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'show_ui'             => true,
                'show_in_menu'        => 'edit.php?post_type=flavor_landing',
                'show_in_rest'        => true,
                'rest_base'           => 'vbp-global-widgets',
                'capability_type'     => 'post',
                'supports'            => array( 'title', 'author' ),
                'menu_icon'           => 'dashicons-screenoptions',
            )
        );
    }

    /**
     * Registra las rutas REST API
     */
    public function registrar_rutas_rest() {
        $namespace = 'flavor-vbp/v1';

        // Listar todos los widgets globales
        register_rest_route(
            $namespace,
            '/global-widgets',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'listar_widgets' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_widget' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Obtener/actualizar/eliminar un widget específico
        register_rest_route(
            $namespace,
            '/global-widgets/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_widget' ),
                    'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'actualizar_widget' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'eliminar_widget' ),
                    'permission_callback' => array( $this, 'verificar_permiso_escritura' ),
                ),
            )
        );

        // Obtener instancias donde se usa un widget global
        register_rest_route(
            $namespace,
            '/global-widgets/(?P<id>\d+)/instances',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_instancias' ),
                'permission_callback' => array( $this, 'verificar_permiso_lectura' ),
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
     * Lista todos los widgets globales
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function listar_widgets( $request ) {
        $widgets = get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'posts_per_page' => 100,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $resultado = array();
        foreach ( $widgets as $widget ) {
            $elemento = get_post_meta( $widget->ID, '_vbp_widget_element', true );
            $resultado[] = array(
                'id'          => $widget->ID,
                'title'       => $widget->post_title,
                'type'        => isset( $elemento['type'] ) ? $elemento['type'] : 'unknown',
                'category'    => get_post_meta( $widget->ID, '_vbp_widget_category', true ) ?: 'general',
                'thumbnail'   => get_post_meta( $widget->ID, '_vbp_widget_thumbnail', true ),
                'usageCount'  => $this->contar_usos( $widget->ID ),
                'author'      => get_the_author_meta( 'display_name', $widget->post_author ),
                'date'        => get_the_date( 'd M Y', $widget ),
                'modified'    => get_the_modified_date( 'd M Y H:i', $widget ),
            );
        }

        return new WP_REST_Response( $resultado, 200 );
    }

    /**
     * Crea un nuevo widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function crear_widget( $request ) {
        $titulo   = sanitize_text_field( $request->get_param( 'title' ) );
        $elemento = $request->get_param( 'element' );
        $category = sanitize_text_field( $request->get_param( 'category' ) ) ?: 'general';

        if ( empty( $titulo ) || empty( $elemento ) ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Título y elemento son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        // Crear el post
        $widget_id = wp_insert_post(
            array(
                'post_title'  => $titulo,
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $widget_id ) ) {
            return new WP_REST_Response(
                array( 'error' => $widget_id->get_error_message() ),
                500
            );
        }

        // Guardar el elemento y categoría
        update_post_meta( $widget_id, '_vbp_widget_element', $elemento );
        update_post_meta( $widget_id, '_vbp_widget_category', $category );

        return new WP_REST_Response(
            array(
                'id'      => $widget_id,
                'title'   => $titulo,
                'type'    => isset( $elemento['type'] ) ? $elemento['type'] : 'unknown',
                'message' => __( 'Widget global creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            201
        );
    }

    /**
     * Obtiene un widget global específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_widget( $request ) {
        $widget_id = absint( $request->get_param( 'id' ) );
        $widget    = get_post( $widget_id );

        if ( ! $widget || self::POST_TYPE !== $widget->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        $elemento = get_post_meta( $widget_id, '_vbp_widget_element', true );

        return new WP_REST_Response(
            array(
                'id'         => $widget->ID,
                'title'      => $widget->post_title,
                'element'    => $elemento,
                'category'   => get_post_meta( $widget_id, '_vbp_widget_category', true ) ?: 'general',
                'usageCount' => $this->contar_usos( $widget_id ),
            ),
            200
        );
    }

    /**
     * Actualiza un widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function actualizar_widget( $request ) {
        $widget_id = absint( $request->get_param( 'id' ) );
        $widget    = get_post( $widget_id );

        if ( ! $widget || self::POST_TYPE !== $widget->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        // Actualizar título si se proporciona
        $titulo = $request->get_param( 'title' );
        if ( $titulo ) {
            wp_update_post(
                array(
                    'ID'         => $widget_id,
                    'post_title' => sanitize_text_field( $titulo ),
                )
            );
        }

        // Actualizar elemento si se proporciona
        $elemento = $request->get_param( 'element' );
        if ( $elemento ) {
            update_post_meta( $widget_id, '_vbp_widget_element', $elemento );
        }

        // Actualizar categoría si se proporciona
        $category = $request->get_param( 'category' );
        if ( $category ) {
            update_post_meta( $widget_id, '_vbp_widget_category', sanitize_text_field( $category ) );
        }

        return new WP_REST_Response(
            array(
                'id'      => $widget_id,
                'message' => __( 'Widget global actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            200
        );
    }

    /**
     * Elimina un widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function eliminar_widget( $request ) {
        $widget_id = absint( $request->get_param( 'id' ) );
        $widget    = get_post( $widget_id );

        if ( ! $widget || self::POST_TYPE !== $widget->post_type ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                404
            );
        }

        // Verificar si hay instancias
        $uso_count = $this->contar_usos( $widget_id );
        if ( $uso_count > 0 ) {
            return new WP_REST_Response(
                array(
                    'error'      => __( 'No se puede eliminar un widget en uso', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'usageCount' => $uso_count,
                ),
                400
            );
        }

        wp_delete_post( $widget_id, true );

        return new WP_REST_Response(
            array( 'message' => __( 'Widget global eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            200
        );
    }

    /**
     * Obtiene las instancias donde se usa un widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_instancias( $request ) {
        $widget_id  = absint( $request->get_param( 'id' ) );
        $instancias = $this->buscar_instancias( $widget_id );

        return new WP_REST_Response( $instancias, 200 );
    }

    /**
     * Cuenta cuántas veces se usa un widget global
     *
     * @param int $widget_id ID del widget global.
     * @return int
     */
    private function contar_usos( $widget_id ) {
        global $wpdb;

        // Buscar en meta de landings
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                 WHERE meta_key = '_vbp_document_data'
                 AND meta_value LIKE %s",
                '%"globalWidgetId":' . $widget_id . '%'
            )
        );

        return absint( $count );
    }

    /**
     * Busca las instancias de un widget global
     *
     * @param int $widget_id ID del widget global.
     * @return array
     */
    private function buscar_instancias( $widget_id ) {
        global $wpdb;

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_vbp_document_data'
                 AND pm.meta_value LIKE %s
                 AND p.post_status != 'trash'",
                '%"globalWidgetId":' . $widget_id . '%'
            )
        );

        $resultado = array();
        foreach ( $posts as $post ) {
            $resultado[] = array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'editUrl'  => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
            );
        }

        return $resultado;
    }

    /**
     * Obtiene los datos de un widget global para renderizar
     *
     * @param int $widget_id ID del widget global.
     * @return array|null
     */
    public function get_widget_data( $widget_id ) {
        $widget = get_post( $widget_id );

        if ( ! $widget || self::POST_TYPE !== $widget->post_type ) {
            return null;
        }

        return get_post_meta( $widget_id, '_vbp_widget_element', true );
    }
}
