<?php
/**
 * Trait para SEO y Metadata VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_SeoMeta {

    /**
     * Configura Schema.org / JSON-LD
     */
    public function configure_schema_org( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $data = $request->get_param( 'data' );

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
        );

        $schema = array_merge( $schema, $data );
        update_post_meta( $page_id, '_vbp_schema_org', $schema );

        return new WP_REST_Response( array( 'success' => true, 'schema' => $schema ), 200 );
    }

    /**
     * Configura Open Graph
     */
    public function configure_open_graph( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $description = sanitize_text_field( $request->get_param( 'description' ) );
        $image = esc_url_raw( $request->get_param( 'image' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $twitter_card = sanitize_text_field( $request->get_param( 'twitter_card' ) );

        $og_data = array(
            'og:title' => $title ?: get_the_title( $page_id ),
            'og:description' => $description,
            'og:image' => $image,
            'og:type' => $type,
            'og:url' => get_permalink( $page_id ),
            'twitter:card' => $twitter_card,
        );

        update_post_meta( $page_id, '_vbp_open_graph', $og_data );

        return new WP_REST_Response( array( 'success' => true, 'open_graph' => $og_data ), 200 );
    }

    // =============================================
}
