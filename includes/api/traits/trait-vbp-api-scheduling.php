<?php
/**
 * Trait para programación de publicaciones VBP
 *
 * Este trait contiene métodos para programar y gestionar
 * publicaciones futuras de páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Scheduling
 *
 * Contiene métodos para:
 * - Programar publicación (schedule_page)
 * - Cancelar programación (unschedule_page)
 * - Listar páginas programadas (list_scheduled_pages)
 */
trait VBP_API_Scheduling {

    /**
     * Programa publicación de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function schedule_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $publish_date = $request->get_param( 'publish_date' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Validar fecha
        $timestamp = strtotime( $publish_date );
        if ( ! $timestamp || $timestamp <= time() ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'La fecha debe ser futura.',
            ), 400 );
        }

        $gmt_date = gmdate( 'Y-m-d H:i:s', $timestamp );
        $local_date = date( 'Y-m-d H:i:s', $timestamp );

        $updated = wp_update_post( array(
            'ID'            => $page_id,
            'post_status'   => 'future',
            'post_date'     => $local_date,
            'post_date_gmt' => $gmt_date,
        ) );

        if ( is_wp_error( $updated ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $updated->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => 'Página programada.',
            'page_id'      => $page_id,
            'publish_date' => $local_date,
            'publish_gmt'  => $gmt_date,
        ), 200 );
    }

    /**
     * Cancela programación
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function unschedule_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        if ( $post->post_status !== 'future' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'La página no está programada.',
            ), 400 );
        }

        $updated = wp_update_post( array(
            'ID'          => $page_id,
            'post_status' => 'draft',
        ) );

        if ( is_wp_error( $updated ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $updated->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Programación cancelada.',
            'page_id' => $page_id,
            'status'  => 'draft',
        ), 200 );
    }

    /**
     * Lista páginas programadas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_scheduled_pages( $request ) {
        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => 'future',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'orderby'        => 'date',
            'order'          => 'ASC',
        ) );

        $scheduled = array();
        foreach ( $pages as $page ) {
            $scheduled[] = array(
                'id'           => $page->ID,
                'title'        => $page->post_title,
                'publish_date' => $page->post_date,
                'publish_gmt'  => $page->post_date_gmt,
                'time_until'   => human_time_diff( time(), strtotime( $page->post_date ) ),
                'edit_url'     => admin_url( "admin.php?page=vbp-editor&post_id={$page->ID}" ),
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'total'   => count( $scheduled ),
            'pages'   => $scheduled,
        ), 200 );
    }
}
