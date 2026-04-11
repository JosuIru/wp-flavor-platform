<?php
/**
 * Trait para biblioteca de plantillas, favoritos y etiquetas VBP
 *
 * Este trait contiene métodos para gestión de plantillas de bloques,
 * favoritos de usuario y etiquetas de páginas.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Library
 *
 * Contiene métodos para:
 * - Plantillas de bloques (list_block_templates, save_block_template, etc.)
 * - Favoritos de usuario (list_favorite_pages, toggle_favorite)
 * - Etiquetas de páginas (list_vbp_tags, set_page_tags, get_page_tags)
 */
trait VBP_API_Library {

    // =========================================================================
    // PLANTILLAS DE BLOQUES
    // =========================================================================

    /**
     * Lista plantillas de bloques guardadas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_block_templates( $request ) {
        $category = $request->get_param( 'category' );

        $templates = get_option( 'flavor_vbp_block_templates', array() );

        if ( $category ) {
            $templates = array_filter( $templates, function( $tpl ) use ( $category ) {
                return ( $tpl['category'] ?? 'general' ) === $category;
            } );
        }

        // Añadir índices
        $indexed_templates = array();
        foreach ( $templates as $id => $template ) {
            $template['id'] = $id;
            $indexed_templates[] = $template;
        }

        // Obtener categorías únicas
        $all_templates = get_option( 'flavor_vbp_block_templates', array() );
        $categories = array_unique( array_map( function( $tpl ) {
            return $tpl['category'] ?? 'general';
        }, $all_templates ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'total'      => count( $indexed_templates ),
            'categories' => array_values( $categories ),
            'templates'  => $indexed_templates,
        ), 200 );
    }

    /**
     * Guarda un bloque como plantilla
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_block_template( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $block = $request->get_param( 'block' );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $description = sanitize_textarea_field( $request->get_param( 'description' ) );

        if ( empty( $name ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El nombre es requerido.',
            ), 400 );
        }

        $templates = get_option( 'flavor_vbp_block_templates', array() );

        $template_id = 'tpl_' . bin2hex( random_bytes( 6 ) );
        $templates[ $template_id ] = array(
            'name'        => $name,
            'description' => $description,
            'category'    => $category ?: 'general',
            'block'       => $block,
            'block_type'  => $block['type'] ?? 'unknown',
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
        );

        update_option( 'flavor_vbp_block_templates', $templates );

        return new WP_REST_Response( array(
            'success'     => true,
            'message'     => 'Plantilla guardada correctamente.',
            'template_id' => $template_id,
            'name'        => $name,
            'category'    => $category,
        ), 201 );
    }

    /**
     * Obtiene una plantilla específica
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_template( $request ) {
        $template_id = $request->get_param( 'id' );

        $templates = get_option( 'flavor_vbp_block_templates', array() );

        if ( ! isset( $templates[ $template_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Plantilla no encontrada.',
            ), 404 );
        }

        $template = $templates[ $template_id ];
        $template['id'] = $template_id;

        // Regenerar IDs del bloque para uso
        $template['block_fresh'] = $this->regenerate_element_ids( array( $template['block'] ) )[0];

        return new WP_REST_Response( array(
            'success'  => true,
            'template' => $template,
        ), 200 );
    }

    /**
     * Elimina una plantilla
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_block_template( $request ) {
        $template_id = $request->get_param( 'id' );

        $templates = get_option( 'flavor_vbp_block_templates', array() );

        if ( ! isset( $templates[ $template_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Plantilla no encontrada.',
            ), 404 );
        }

        $deleted_name = $templates[ $template_id ]['name'];
        unset( $templates[ $template_id ] );

        update_option( 'flavor_vbp_block_templates', $templates );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Plantilla '{$deleted_name}' eliminada.",
        ), 200 );
    }

    // =========================================================================
    // FAVORITOS
    // =========================================================================

    /**
     * Lista páginas favoritas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_favorite_pages( $request ) {
        $user_id = get_current_user_id();
        $favorites = get_user_meta( $user_id, 'flavor_vbp_favorites', true ) ?: array();

        $pages = array();
        foreach ( $favorites as $page_id ) {
            $post = get_post( $page_id );
            if ( $post && $this->is_supported_post_type( $post->post_type ) ) {
                $pages[] = array(
                    'id'       => $page_id,
                    'title'    => $post->post_title,
                    'status'   => $post->post_status,
                    'url'      => get_permalink( $page_id ),
                    'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$page_id}" ),
                    'modified' => $post->post_modified,
                );
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'total'   => count( $pages ),
            'pages'   => $pages,
        ), 200 );
    }

    /**
     * Marca/desmarca página como favorita
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function toggle_favorite( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $favorites = get_user_meta( $user_id, 'flavor_vbp_favorites', true ) ?: array();

        $is_favorite = in_array( $page_id, $favorites, true );

        if ( $is_favorite ) {
            $favorites = array_diff( $favorites, array( $page_id ) );
            $action = 'removed';
        } else {
            $favorites[] = $page_id;
            $action = 'added';
        }

        update_user_meta( $user_id, 'flavor_vbp_favorites', array_values( $favorites ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'page_id'     => $page_id,
            'is_favorite' => ! $is_favorite,
            'action'      => $action,
            'message'     => $action === 'added' ? 'Añadido a favoritos' : 'Eliminado de favoritos',
        ), 200 );
    }

    // =========================================================================
    // ETIQUETAS
    // =========================================================================

    /**
     * Lista etiquetas VBP disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_vbp_tags( $request ) {
        global $wpdb;

        // Obtener todas las etiquetas usadas
        $results = $wpdb->get_col(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_flavor_vbp_tags'
             AND meta_value != ''"
        );

        $all_tags = array();
        foreach ( $results as $tags_json ) {
            $tags = json_decode( $tags_json, true );
            if ( is_array( $tags ) ) {
                $all_tags = array_merge( $all_tags, $tags );
            }
        }

        $tag_counts = array_count_values( $all_tags );
        arsort( $tag_counts );

        $tags = array();
        foreach ( $tag_counts as $tag => $count ) {
            $tags[] = array(
                'name'  => $tag,
                'count' => $count,
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'total'   => count( $tags ),
            'tags'    => $tags,
        ), 200 );
    }

    /**
     * Asigna etiquetas a una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_page_tags( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $tags = $request->get_param( 'tags' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Sanitizar etiquetas
        $clean_tags = array_map( 'sanitize_text_field', $tags );
        $clean_tags = array_filter( $clean_tags );
        $clean_tags = array_unique( $clean_tags );

        update_post_meta( $page_id, '_flavor_vbp_tags', wp_json_encode( array_values( $clean_tags ) ) );

        return new WP_REST_Response( array(
            'success' => true,
            'page_id' => $page_id,
            'tags'    => array_values( $clean_tags ),
            'count'   => count( $clean_tags ),
        ), 200 );
    }

    /**
     * Obtiene etiquetas de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_tags( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $tags_json = get_post_meta( $page_id, '_flavor_vbp_tags', true );
        $tags = $tags_json ? json_decode( $tags_json, true ) : array();

        return new WP_REST_Response( array(
            'success' => true,
            'page_id' => $page_id,
            'tags'    => $tags ?: array(),
        ), 200 );
    }
}
