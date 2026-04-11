<?php
/**
 * Trait para comentarios y notas VBP
 *
 * Este trait contiene métodos para gestión de comentarios
 * y notas en páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Comments
 *
 * Contiene métodos para:
 * - Listar comentarios (list_page_comments)
 * - Añadir comentarios (add_page_comment)
 * - Eliminar comentarios (delete_page_comment)
 * - Resolver comentarios (resolve_page_comment)
 */
trait VBP_API_Comments {

    /**
     * Lista comentarios de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_page_comments( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $comments_json = get_post_meta( $page_id, '_flavor_vbp_comments', true );
        $comments = $comments_json ? json_decode( $comments_json, true ) : array();

        // Enriquecer con datos de usuario
        foreach ( $comments as &$comment ) {
            if ( ! empty( $comment['user_id'] ) ) {
                $user = get_userdata( $comment['user_id'] );
                if ( $user ) {
                    $comment['user_name'] = $user->display_name;
                    $comment['user_avatar'] = get_avatar_url( $comment['user_id'], array( 'size' => 48 ) );
                }
            }
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'total'    => count( $comments ),
            'comments' => $comments,
        ), 200 );
    }

    /**
     * Añade comentario a una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function add_page_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $content = sanitize_textarea_field( $request->get_param( 'content' ) );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = $request->get_param( 'type' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $comments_json = get_post_meta( $page_id, '_flavor_vbp_comments', true );
        $comments = $comments_json ? json_decode( $comments_json, true ) : array();

        $comment_id = 'comment_' . uniqid();
        $new_comment = array(
            'id'         => $comment_id,
            'content'    => $content,
            'block_id'   => $block_id,
            'type'       => $type,
            'user_id'    => get_current_user_id(),
            'created_at' => current_time( 'mysql' ),
            'resolved'   => false,
        );

        $comments[] = $new_comment;
        update_post_meta( $page_id, '_flavor_vbp_comments', wp_json_encode( $comments ) );

        // Registrar actividad
        $this->log_page_activity( $page_id, 'comment_added', array(
            'comment_id' => $comment_id,
            'type'       => $type,
        ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Comentario añadido.',
            'comment' => $new_comment,
        ), 201 );
    }

    /**
     * Elimina un comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_page_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $comments_json = get_post_meta( $page_id, '_flavor_vbp_comments', true );
        $comments = $comments_json ? json_decode( $comments_json, true ) : array();

        $found = false;
        foreach ( $comments as $index => $comment ) {
            if ( $comment['id'] === $comment_id ) {
                unset( $comments[ $index ] );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Comentario no encontrado.',
            ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_comments', wp_json_encode( array_values( $comments ) ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Comentario eliminado.',
        ), 200 );
    }

    /**
     * Resuelve un comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function resolve_page_comment( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $comments_json = get_post_meta( $page_id, '_flavor_vbp_comments', true );
        $comments = $comments_json ? json_decode( $comments_json, true ) : array();

        $found = false;
        foreach ( $comments as &$comment ) {
            if ( $comment['id'] === $comment_id ) {
                $comment['resolved'] = true;
                $comment['resolved_at'] = current_time( 'mysql' );
                $comment['resolved_by'] = get_current_user_id();
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Comentario no encontrado.',
            ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_comments', wp_json_encode( $comments ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Comentario resuelto.',
        ), 200 );
    }
}
