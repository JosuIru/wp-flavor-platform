<?php
/**
 * Trait para Comentarios/Feedback VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Feedback {


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
}
