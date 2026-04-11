<?php
/**
 * Trait para SEO VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_SEO {


    /**
     * Obtiene configuración SEO de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_seo( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $seo = array(
            'title'       => get_post_meta( $page_id, '_flavor_vbp_seo_title', true ) ?: $post->post_title,
            'description' => get_post_meta( $page_id, '_flavor_vbp_seo_description', true ),
            'keywords'    => json_decode( get_post_meta( $page_id, '_flavor_vbp_seo_keywords', true ) ?: '[]', true ),
            'og_image'    => get_post_meta( $page_id, '_flavor_vbp_og_image', true ),
            'canonical'   => get_post_meta( $page_id, '_flavor_vbp_canonical', true ) ?: get_permalink( $page_id ),
            'robots'      => get_post_meta( $page_id, '_flavor_vbp_robots', true ) ?: 'index, follow',
        );

        return new WP_REST_Response( array(
            'success' => true,
            'seo'     => $seo,
        ), 200 );
    }

    /**
     * Actualiza configuración SEO de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_page_seo( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $fields = array( 'title', 'description', 'og_image', 'canonical', 'robots' );
        foreach ( $fields as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                update_post_meta( $page_id, '_flavor_vbp_seo_' . $field, sanitize_text_field( $value ) );
            }
        }

        $keywords = $request->get_param( 'keywords' );
        if ( $keywords !== null ) {
            update_post_meta( $page_id, '_flavor_vbp_seo_keywords', wp_json_encode( $keywords ) );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'SEO actualizado.',
        ), 200 );
    }

    /**
     * Obtiene Schema.org de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_schema( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $schema_json = get_post_meta( $page_id, '_flavor_vbp_schema', true );
        $schema = $schema_json ? json_decode( $schema_json, true ) : null;

        return new WP_REST_Response( array(
            'success' => true,
            'schema'  => $schema,
        ), 200 );
    }

    /**
     * Actualiza Schema.org de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_page_schema( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $type = $request->get_param( 'type' );
        $data = $request->get_param( 'data' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => $type,
            'url'      => get_permalink( $page_id ),
            'name'     => $post->post_title,
        );

        if ( $data ) {
            $schema = array_merge( $schema, $data );
        }

        update_post_meta( $page_id, '_flavor_vbp_schema', wp_json_encode( $schema ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Schema actualizado.',
            'schema'  => $schema,
        ), 200 );
    }

    // =============================================
}
