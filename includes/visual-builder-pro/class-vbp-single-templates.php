<?php
/**
 * Visual Builder Pro - Sistema de Plantillas para Singles de CPTs
 *
 * Permite diseñar visualmente las páginas single de cualquier CPT
 * usando el Visual Builder Pro.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar plantillas de singles dinámicas
 *
 * @since 3.4.0
 */
class Flavor_VBP_Single_Templates {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Single_Templates|null
     */
    private static $instance = null;

    /**
     * CPT para plantillas de single
     */
    const POST_TYPE = 'vbp_single_tpl';

    /**
     * Meta key para datos del canvas
     */
    const META_CANVAS = '_vbp_single_canvas';

    /**
     * Meta key para el CPT objetivo
     */
    const META_TARGET_CPT = '_vbp_target_post_type';

    /**
     * Meta key para mapeo de campos
     */
    const META_FIELD_MAP = '_vbp_field_mapping';

    /**
     * Cache de plantillas por CPT
     *
     * @var array
     */
    private $templates_cache = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Single_Templates
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_filter( 'template_include', array( $this, 'maybe_use_vbp_template' ), 99 );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Registrar bloques dinámicos directamente si Block Library ya está cargada
        if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
            $this->register_dynamic_blocks( Flavor_VBP_Block_Library::get_instance() );
        }
    }

    /**
     * Registra el CPT para plantillas de single
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Plantillas Single', 'flavor-chat-ia' ),
            'singular_name'      => __( 'Plantilla Single', 'flavor-chat-ia' ),
            'add_new'            => __( 'Nueva Plantilla', 'flavor-chat-ia' ),
            'add_new_item'       => __( 'Nueva Plantilla Single', 'flavor-chat-ia' ),
            'edit_item'          => __( 'Editar Plantilla', 'flavor-chat-ia' ),
            'view_item'          => __( 'Ver Plantilla', 'flavor-chat-ia' ),
            'all_items'          => __( 'Todas las Plantillas', 'flavor-chat-ia' ),
            'search_items'       => __( 'Buscar Plantillas', 'flavor-chat-ia' ),
            'not_found'          => __( 'No se encontraron plantillas', 'flavor-chat-ia' ),
            'not_found_in_trash' => __( 'No hay plantillas en la papelera', 'flavor-chat-ia' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'flavor-chat-ia',
            'show_in_rest'        => true,
            'rest_base'           => 'vbp-single-templates',
            'capability_type'     => 'page',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'custom-fields' ),
            'menu_icon'           => 'dashicons-layout',
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Registra rutas REST para gestión de plantillas
     */
    public function register_rest_routes() {
        $namespace = 'flavor-vbp/v1';

        // Listar plantillas de single
        register_rest_route(
            $namespace,
            '/single-templates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_list_templates' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'rest_create_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                ),
            )
        );

        // Obtener/actualizar plantilla específica
        register_rest_route(
            $namespace,
            '/single-templates/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_get_template' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'rest_update_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'rest_delete_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                ),
            )
        );

        // Obtener plantilla por CPT
        register_rest_route(
            $namespace,
            '/single-templates/by-cpt/(?P<post_type>[a-z0-9_-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_get_template_by_cpt' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Obtener CPTs disponibles con sus campos
        register_rest_route(
            $namespace,
            '/single-templates/available-cpts',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_get_available_cpts' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Previsualizar plantilla con datos de un post específico
        register_rest_route(
            $namespace,
            '/single-templates/(?P<id>\d+)/preview/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_preview_template' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );
    }

    /**
     * Verifica permiso de lectura
     */
    public function check_read_permission() {
        return current_user_can( 'read' );
    }

    /**
     * Verifica permiso de escritura
     */
    public function check_write_permission() {
        return current_user_can( 'edit_pages' );
    }

    /**
     * Lista todas las plantillas de single
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_list_templates( $request ) {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $posts     = get_posts( $args );
        $templates = array();

        foreach ( $posts as $post ) {
            $templates[] = $this->format_template_response( $post );
        }

        return new WP_REST_Response( $templates, 200 );
    }

    /**
     * Crea una nueva plantilla de single
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_create_template( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['title'] ) || empty( $params['post_type'] ) ) {
            return new WP_REST_Response(
                array( 'error' => 'Se requiere título y post_type' ),
                400
            );
        }

        // Verificar que el CPT existe
        if ( ! post_type_exists( $params['post_type'] ) ) {
            return new WP_REST_Response(
                array( 'error' => 'El post type no existe: ' . $params['post_type'] ),
                400
            );
        }

        // Crear el post
        $post_id = wp_insert_post(
            array(
                'post_title'  => sanitize_text_field( $params['title'] ),
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response(
                array( 'error' => $post_id->get_error_message() ),
                500
            );
        }

        // Guardar meta
        update_post_meta( $post_id, self::META_TARGET_CPT, sanitize_key( $params['post_type'] ) );

        if ( ! empty( $params['canvas'] ) ) {
            update_post_meta( $post_id, self::META_CANVAS, wp_json_encode( $params['canvas'] ) );
        }

        if ( ! empty( $params['field_mapping'] ) ) {
            update_post_meta( $post_id, self::META_FIELD_MAP, wp_json_encode( $params['field_mapping'] ) );
        }

        // Limpiar cache
        $this->clear_template_cache( $params['post_type'] );

        $post = get_post( $post_id );

        return new WP_REST_Response( $this->format_template_response( $post ), 201 );
    }

    /**
     * Obtiene una plantilla específica
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_template( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new WP_REST_Response( array( 'error' => 'Plantilla no encontrada' ), 404 );
        }

        return new WP_REST_Response( $this->format_template_response( $post, true ), 200 );
    }

    /**
     * Actualiza una plantilla
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_update_template( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new WP_REST_Response( array( 'error' => 'Plantilla no encontrada' ), 404 );
        }

        $params   = $request->get_json_params();
        $old_cpt  = get_post_meta( $post->ID, self::META_TARGET_CPT, true );

        // Actualizar título si se proporciona
        if ( ! empty( $params['title'] ) ) {
            wp_update_post(
                array(
                    'ID'         => $post->ID,
                    'post_title' => sanitize_text_field( $params['title'] ),
                )
            );
        }

        // Actualizar CPT objetivo
        if ( ! empty( $params['post_type'] ) ) {
            if ( ! post_type_exists( $params['post_type'] ) ) {
                return new WP_REST_Response(
                    array( 'error' => 'El post type no existe: ' . $params['post_type'] ),
                    400
                );
            }
            update_post_meta( $post->ID, self::META_TARGET_CPT, sanitize_key( $params['post_type'] ) );
            $this->clear_template_cache( $old_cpt );
            $this->clear_template_cache( $params['post_type'] );
        }

        // Actualizar canvas
        if ( isset( $params['canvas'] ) ) {
            update_post_meta( $post->ID, self::META_CANVAS, wp_json_encode( $params['canvas'] ) );
        }

        // Actualizar mapeo de campos
        if ( isset( $params['field_mapping'] ) ) {
            update_post_meta( $post->ID, self::META_FIELD_MAP, wp_json_encode( $params['field_mapping'] ) );
        }

        $post = get_post( $post->ID );

        return new WP_REST_Response( $this->format_template_response( $post, true ), 200 );
    }

    /**
     * Elimina una plantilla
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_delete_template( $request ) {
        $post = get_post( $request['id'] );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new WP_REST_Response( array( 'error' => 'Plantilla no encontrada' ), 404 );
        }

        $cpt = get_post_meta( $post->ID, self::META_TARGET_CPT, true );
        wp_delete_post( $post->ID, true );
        $this->clear_template_cache( $cpt );

        return new WP_REST_Response( array( 'deleted' => true ), 200 );
    }

    /**
     * Obtiene plantilla por CPT
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_template_by_cpt( $request ) {
        $post_type = sanitize_key( $request['post_type'] );
        $template  = $this->get_template_for_cpt( $post_type );

        if ( ! $template ) {
            return new WP_REST_Response(
                array(
                    'found'     => false,
                    'post_type' => $post_type,
                    'message'   => 'No hay plantilla VBP para este CPT',
                ),
                200
            );
        }

        return new WP_REST_Response(
            array(
                'found'    => true,
                'template' => $this->format_template_response( $template, true ),
            ),
            200
        );
    }

    /**
     * Obtiene CPTs disponibles con sus campos
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_get_available_cpts( $request ) {
        $post_types = get_post_types(
            array(
                'public' => true,
            ),
            'objects'
        );

        // Añadir CPTs privados de Flavor
        $flavor_cpts = get_post_types(
            array(
                '_builtin' => false,
            ),
            'objects'
        );

        $post_types = array_merge( $post_types, $flavor_cpts );

        $result = array();

        foreach ( $post_types as $pt ) {
            // Excluir algunos CPTs del sistema
            if ( in_array( $pt->name, array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', self::POST_TYPE ), true ) ) {
                continue;
            }

            $fields   = $this->get_cpt_fields( $pt->name );
            $template = $this->get_template_for_cpt( $pt->name );

            $result[] = array(
                'name'         => $pt->name,
                'label'        => $pt->label,
                'description'  => $pt->description,
                'has_template' => ! empty( $template ),
                'template_id'  => $template ? $template->ID : null,
                'fields'       => $fields,
                'supports'     => get_all_post_type_supports( $pt->name ),
            );
        }

        // Ordenar: primero los que empiezan con prefijos de Flavor
        usort(
            $result,
            function ( $a, $b ) {
                $a_flavor = preg_match( '/^(gc_|flavor_|bt_|ev_|rs_|bl_|jr_|cc_|ed_)/', $a['name'] );
                $b_flavor = preg_match( '/^(gc_|flavor_|bt_|ev_|rs_|bl_|jr_|cc_|ed_)/', $b['name'] );

                if ( $a_flavor && ! $b_flavor ) {
                    return -1;
                }
                if ( ! $a_flavor && $b_flavor ) {
                    return 1;
                }

                return strcmp( $a['label'], $b['label'] );
            }
        );

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Previsualiza una plantilla con datos de un post específico
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function rest_preview_template( $request ) {
        $template = get_post( $request['id'] );
        $post     = get_post( $request['post_id'] );

        if ( ! $template || self::POST_TYPE !== $template->post_type ) {
            return new WP_REST_Response( array( 'error' => 'Plantilla no encontrada' ), 404 );
        }

        if ( ! $post ) {
            return new WP_REST_Response( array( 'error' => 'Post no encontrado' ), 404 );
        }

        $html = $this->render_template( $template->ID, $post );

        return new WP_REST_Response(
            array(
                'html'      => $html,
                'post_data' => $this->get_post_data_for_template( $post ),
            ),
            200
        );
    }

    /**
     * Formatea respuesta de plantilla
     *
     * @param WP_Post $post          Post object.
     * @param bool    $include_canvas Incluir datos del canvas.
     * @return array
     */
    private function format_template_response( $post, $include_canvas = false ) {
        $response = array(
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'post_type'  => get_post_meta( $post->ID, self::META_TARGET_CPT, true ),
            'created'    => $post->post_date,
            'modified'   => $post->post_modified,
            'edit_url'   => admin_url( 'admin.php?page=vbp-editor&post_id=' . $post->ID . '&type=single_template' ),
        );

        if ( $include_canvas ) {
            $canvas_json = get_post_meta( $post->ID, self::META_CANVAS, true );
            $response['canvas'] = $canvas_json ? json_decode( $canvas_json, true ) : array( 'elements' => array(), 'settings' => array() );

            $mapping_json = get_post_meta( $post->ID, self::META_FIELD_MAP, true );
            $response['field_mapping'] = $mapping_json ? json_decode( $mapping_json, true ) : array();
        }

        return $response;
    }

    /**
     * Obtiene plantilla para un CPT
     *
     * @param string $post_type Post type.
     * @return WP_Post|null
     */
    public function get_template_for_cpt( $post_type ) {
        if ( isset( $this->templates_cache[ $post_type ] ) ) {
            return $this->templates_cache[ $post_type ];
        }

        $templates = get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => self::META_TARGET_CPT,
                        'value' => $post_type,
                    ),
                ),
            )
        );

        $template = ! empty( $templates ) ? $templates[0] : null;
        $this->templates_cache[ $post_type ] = $template;

        return $template;
    }

    /**
     * Limpia cache de plantillas
     *
     * @param string $post_type Post type.
     */
    private function clear_template_cache( $post_type ) {
        unset( $this->templates_cache[ $post_type ] );
    }

    /**
     * Obtiene campos de un CPT
     *
     * @param string $post_type Post type.
     * @return array
     */
    public function get_cpt_fields( $post_type ) {
        $fields = array(
            // Campos estándar de WordPress
            array(
                'key'     => 'post_title',
                'label'   => __( 'Título', 'flavor-chat-ia' ),
                'type'    => 'text',
                'source'  => 'core',
            ),
            array(
                'key'     => 'post_content',
                'label'   => __( 'Contenido', 'flavor-chat-ia' ),
                'type'    => 'html',
                'source'  => 'core',
            ),
            array(
                'key'     => 'post_excerpt',
                'label'   => __( 'Extracto', 'flavor-chat-ia' ),
                'type'    => 'text',
                'source'  => 'core',
            ),
            array(
                'key'     => 'post_date',
                'label'   => __( 'Fecha de publicación', 'flavor-chat-ia' ),
                'type'    => 'date',
                'source'  => 'core',
            ),
            array(
                'key'     => 'post_author',
                'label'   => __( 'Autor', 'flavor-chat-ia' ),
                'type'    => 'author',
                'source'  => 'core',
            ),
            array(
                'key'     => 'featured_image',
                'label'   => __( 'Imagen destacada', 'flavor-chat-ia' ),
                'type'    => 'image',
                'source'  => 'core',
            ),
        );

        // Obtener meta keys registradas para este CPT
        global $wpdb;

        $meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.meta_key
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = %s
                AND pm.meta_key NOT LIKE '\_%'
                ORDER BY pm.meta_key",
                $post_type
            )
        );

        foreach ( $meta_keys as $meta_key ) {
            $fields[] = array(
                'key'    => $meta_key,
                'label'  => ucfirst( str_replace( '_', ' ', $meta_key ) ),
                'type'   => 'meta',
                'source' => 'meta',
            );
        }

        // Obtener campos registrados con register_meta
        $registered_meta = get_registered_meta_keys( 'post', $post_type );
        foreach ( $registered_meta as $key => $args ) {
            // Evitar duplicados
            $exists = array_filter( $fields, function ( $f ) use ( $key ) {
                return $f['key'] === $key;
            });

            if ( empty( $exists ) ) {
                $fields[] = array(
                    'key'         => $key,
                    'label'       => $args['description'] ?? ucfirst( str_replace( '_', ' ', $key ) ),
                    'type'        => $args['type'] ?? 'string',
                    'source'      => 'registered_meta',
                );
            }
        }

        // Taxonomías asociadas
        $taxonomies = get_object_taxonomies( $post_type, 'objects' );
        foreach ( $taxonomies as $tax ) {
            $fields[] = array(
                'key'    => 'taxonomy_' . $tax->name,
                'label'  => $tax->label,
                'type'   => 'taxonomy',
                'source' => 'taxonomy',
            );
        }

        return $fields;
    }

    /**
     * Obtiene datos de un post para usar en plantilla
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    public function get_post_data_for_template( $post ) {
        $data = array(
            'ID'              => $post->ID,
            'post_title'      => $post->post_title,
            'post_content'    => apply_filters( 'the_content', $post->post_content ),
            'post_excerpt'    => $post->post_excerpt ?: wp_trim_words( $post->post_content, 55 ),
            'post_date'       => $post->post_date,
            'post_date_human' => human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ),
            'post_author'     => $post->post_author,
            'author_name'     => get_the_author_meta( 'display_name', $post->post_author ),
            'author_avatar'   => get_avatar_url( $post->post_author, array( 'size' => 96 ) ),
            'permalink'       => get_permalink( $post ),
            'post_type'       => $post->post_type,
        );

        // Imagen destacada
        $thumbnail_id = get_post_thumbnail_id( $post );
        if ( $thumbnail_id ) {
            $data['featured_image']       = wp_get_attachment_url( $thumbnail_id );
            $data['featured_image_id']    = $thumbnail_id;
            $data['featured_image_large'] = wp_get_attachment_image_url( $thumbnail_id, 'large' );
            $data['featured_image_medium'] = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
        } else {
            $data['featured_image']       = '';
            $data['featured_image_id']    = 0;
            $data['featured_image_large'] = '';
            $data['featured_image_medium'] = '';
        }

        // Meta fields
        $meta = get_post_meta( $post->ID );
        foreach ( $meta as $key => $values ) {
            if ( strpos( $key, '_' ) !== 0 ) { // Ignorar meta privadas
                $data[ 'meta_' . $key ] = is_array( $values ) && count( $values ) === 1 ? $values[0] : $values;
            }
        }

        // Taxonomías
        $taxonomies = get_object_taxonomies( $post->post_type );
        foreach ( $taxonomies as $tax ) {
            $terms = get_the_terms( $post, $tax );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $data[ 'taxonomy_' . $tax ] = array_map(
                    function ( $term ) {
                        return array(
                            'id'   => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug,
                            'link' => get_term_link( $term ),
                        );
                    },
                    $terms
                );
            } else {
                $data[ 'taxonomy_' . $tax ] = array();
            }
        }

        return $data;
    }

    /**
     * Intercepta template_include para usar plantilla VBP
     *
     * @param string $template Template path.
     * @return string
     */
    public function maybe_use_vbp_template( $template ) {
        if ( ! is_singular() ) {
            return $template;
        }

        $post      = get_queried_object();
        $vbp_tpl   = $this->get_template_for_cpt( $post->post_type );

        if ( ! $vbp_tpl ) {
            return $template;
        }

        // Usar nuestra plantilla
        return FLAVOR_CHAT_IA_PATH . 'templates/single-vbp-dynamic.php';
    }

    /**
     * Renderiza una plantilla con datos de un post
     *
     * @param int     $template_id ID de la plantilla.
     * @param WP_Post $post        Post a renderizar.
     * @return string HTML renderizado.
     */
    public function render_template( $template_id, $post ) {
        $canvas_json = get_post_meta( $template_id, self::META_CANVAS, true );

        if ( ! $canvas_json ) {
            return '<p>' . esc_html__( 'Plantilla vacía', 'flavor-chat-ia' ) . '</p>';
        }

        $canvas_data = json_decode( $canvas_json, true );
        $post_data   = $this->get_post_data_for_template( $post );

        // Procesar elementos reemplazando placeholders
        $elements = $this->process_elements( $canvas_data['elements'] ?? array(), $post_data );

        // Renderizar usando VBP Canvas
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            return $canvas->renderizar_documento(
                array(
                    'elements' => $elements,
                    'settings' => $canvas_data['settings'] ?? array(),
                )
            );
        }

        return '<p>' . esc_html__( 'Error: VBP Canvas no disponible', 'flavor-chat-ia' ) . '</p>';
    }

    /**
     * Procesa elementos reemplazando placeholders con datos del post
     *
     * @param array $elements  Elementos del canvas.
     * @param array $post_data Datos del post.
     * @return array Elementos procesados.
     */
    private function process_elements( $elements, $post_data ) {
        foreach ( $elements as &$element ) {
            // Procesar data del elemento
            if ( ! empty( $element['data'] ) ) {
                $element['data'] = $this->replace_placeholders( $element['data'], $post_data );
            }

            // Procesar hijos recursivamente
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->process_elements( $element['children'], $post_data );
            }
        }

        return $elements;
    }

    /**
     * Reemplaza placeholders en los datos
     *
     * @param mixed $data      Datos a procesar.
     * @param array $post_data Datos del post.
     * @return mixed Datos con placeholders reemplazados.
     */
    private function replace_placeholders( $data, $post_data ) {
        if ( is_string( $data ) ) {
            // Reemplazar {{field_name}} con el valor del campo
            return preg_replace_callback(
                '/\{\{([a-zA-Z0-9_]+)\}\}/',
                function ( $matches ) use ( $post_data ) {
                    $field = $matches[1];
                    return isset( $post_data[ $field ] ) ? $post_data[ $field ] : $matches[0];
                },
                $data
            );
        }

        if ( is_array( $data ) ) {
            foreach ( $data as $key => &$value ) {
                $value = $this->replace_placeholders( $value, $post_data );
            }
        }

        return $data;
    }

    /**
     * Registra bloques dinámicos para singles
     *
     * @param Flavor_VBP_Block_Library $library Block library instance.
     */
    public function register_dynamic_blocks( $library ) {
        // Bloque: Campo dinámico
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-field',
                'name'     => __( 'Campo Dinámico', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
                'fields'   => array(
                    'field_key'  => array(
                        'type'    => 'text',
                        'label'   => __( 'Campo', 'flavor-chat-ia' ),
                        'default' => 'post_title',
                        'description' => __( 'Usar: post_title, post_content, featured_image, meta_*, taxonomy_*', 'flavor-chat-ia' ),
                    ),
                    'tag'        => array(
                        'type'    => 'select',
                        'label'   => __( 'Etiqueta HTML', 'flavor-chat-ia' ),
                        'options' => array(
                            'div'  => 'div',
                            'p'    => 'p',
                            'span' => 'span',
                            'h1'   => 'h1',
                            'h2'   => 'h2',
                            'h3'   => 'h3',
                            'h4'   => 'h4',
                        ),
                        'default' => 'div',
                    ),
                    'fallback'   => array(
                        'type'    => 'text',
                        'label'   => __( 'Texto por defecto', 'flavor-chat-ia' ),
                        'default' => '',
                    ),
                ),
            )
        );

        // Bloque: Imagen destacada
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-featured-image',
                'name'     => __( 'Imagen Destacada', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
                'fields'   => array(
                    'size'       => array(
                        'type'    => 'select',
                        'label'   => __( 'Tamaño', 'flavor-chat-ia' ),
                        'options' => array(
                            'thumbnail' => __( 'Miniatura', 'flavor-chat-ia' ),
                            'medium'    => __( 'Mediano', 'flavor-chat-ia' ),
                            'large'     => __( 'Grande', 'flavor-chat-ia' ),
                            'full'      => __( 'Completo', 'flavor-chat-ia' ),
                        ),
                        'default' => 'large',
                    ),
                    'aspect_ratio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Proporción', 'flavor-chat-ia' ),
                        'options' => array(
                            'auto'  => __( 'Auto', 'flavor-chat-ia' ),
                            '16/9'  => '16:9',
                            '4/3'   => '4:3',
                            '1/1'   => '1:1',
                            '3/2'   => '3:2',
                        ),
                        'default' => 'auto',
                    ),
                    'fallback_image' => array(
                        'type'    => 'image',
                        'label'   => __( 'Imagen por defecto', 'flavor-chat-ia' ),
                    ),
                ),
            )
        );

        // Bloque: Meta del autor
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-author',
                'name'     => __( 'Autor', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'fields'   => array(
                    'show_avatar'  => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar avatar', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'show_name'    => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar nombre', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'show_bio'     => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar biografía', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                    'avatar_size'  => array(
                        'type'    => 'number',
                        'label'   => __( 'Tamaño avatar (px)', 'flavor-chat-ia' ),
                        'default' => 48,
                    ),
                ),
            )
        );

        // Bloque: Fecha
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-date',
                'name'     => __( 'Fecha', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
                'fields'   => array(
                    'format'       => array(
                        'type'    => 'select',
                        'label'   => __( 'Formato', 'flavor-chat-ia' ),
                        'options' => array(
                            'human'   => __( 'Hace X tiempo', 'flavor-chat-ia' ),
                            'full'    => __( 'Fecha completa', 'flavor-chat-ia' ),
                            'short'   => __( 'Fecha corta', 'flavor-chat-ia' ),
                            'custom'  => __( 'Personalizado', 'flavor-chat-ia' ),
                        ),
                        'default' => 'full',
                    ),
                    'custom_format' => array(
                        'type'    => 'text',
                        'label'   => __( 'Formato personalizado', 'flavor-chat-ia' ),
                        'default' => 'j F, Y',
                        'description' => __( 'Formato PHP: j=día, F=mes, Y=año', 'flavor-chat-ia' ),
                    ),
                    'prefix'       => array(
                        'type'    => 'text',
                        'label'   => __( 'Prefijo', 'flavor-chat-ia' ),
                        'default' => '',
                    ),
                ),
            )
        );

        // Bloque: Taxonomías
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-terms',
                'name'     => __( 'Categorías/Tags', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1"/></svg>',
                'fields'   => array(
                    'taxonomy'     => array(
                        'type'    => 'text',
                        'label'   => __( 'Taxonomía', 'flavor-chat-ia' ),
                        'default' => 'category',
                        'description' => __( 'category, post_tag, o taxonomía personalizada', 'flavor-chat-ia' ),
                    ),
                    'style'        => array(
                        'type'    => 'select',
                        'label'   => __( 'Estilo', 'flavor-chat-ia' ),
                        'options' => array(
                            'links' => __( 'Enlaces', 'flavor-chat-ia' ),
                            'badges' => __( 'Badges', 'flavor-chat-ia' ),
                            'pills' => __( 'Pills', 'flavor-chat-ia' ),
                            'plain' => __( 'Texto plano', 'flavor-chat-ia' ),
                        ),
                        'default' => 'badges',
                    ),
                    'separator'    => array(
                        'type'    => 'text',
                        'label'   => __( 'Separador', 'flavor-chat-ia' ),
                        'default' => ', ',
                    ),
                ),
            )
        );

        // Bloque: Posts relacionados
        $library->registrar_bloque(
            array(
                'id'       => 'dynamic-related-posts',
                'name'     => __( 'Posts Relacionados', 'flavor-chat-ia' ),
                'category' => 'dynamic',
                'icon'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
                'fields'   => array(
                    'count'        => array(
                        'type'    => 'number',
                        'label'   => __( 'Cantidad', 'flavor-chat-ia' ),
                        'default' => 3,
                    ),
                    'columns'      => array(
                        'type'    => 'select',
                        'label'   => __( 'Columnas', 'flavor-chat-ia' ),
                        'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
                        'default' => '3',
                    ),
                    'show_image'   => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar imagen', 'flavor-chat-ia' ),
                        'default' => true,
                    ),
                    'show_excerpt' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Mostrar extracto', 'flavor-chat-ia' ),
                        'default' => false,
                    ),
                    'relation'     => array(
                        'type'    => 'select',
                        'label'   => __( 'Relación', 'flavor-chat-ia' ),
                        'options' => array(
                            'category' => __( 'Misma categoría', 'flavor-chat-ia' ),
                            'tag'      => __( 'Mismo tag', 'flavor-chat-ia' ),
                            'author'   => __( 'Mismo autor', 'flavor-chat-ia' ),
                            'random'   => __( 'Aleatorio', 'flavor-chat-ia' ),
                        ),
                        'default' => 'category',
                    ),
                ),
            )
        );
    }
}

// Inicializar
Flavor_VBP_Single_Templates::get_instance();
