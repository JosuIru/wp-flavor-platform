<?php
/**
 * Trait para Caché y Lazy Loading VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Cache {

    /**
     * Configura caché de página
     */
    public function configure_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $ttl = (int) $request->get_param( 'ttl' );
        $vary_by = $request->get_param( 'vary_by' ) ?: array();

        $cache_config = array( 'enabled' => $enabled, 'ttl' => $ttl, 'vary_by' => $vary_by );
        update_post_meta( $page_id, '_vbp_cache_config', $cache_config );

        return new WP_REST_Response( array( 'success' => true, 'cache_config' => $cache_config ), 200 );
    }

    /**
     * Invalida caché de página
     */
    public function invalidate_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        delete_transient( 'vbp_page_cache_' . $page_id );
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Caché invalidada.' ), 200 );
    }

    /**
     * Pre-genera caché
     */
    public function pregenerate_page_cache( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $html = $this->elements_to_html( $elements );
        $cache_config = get_post_meta( $page_id, '_vbp_cache_config', true ) ?: array( 'ttl' => 3600 );
        set_transient( 'vbp_page_cache_' . $page_id, $html, $cache_config['ttl'] );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Caché pre-generada.' ), 200 );
    }

    /**
     * Configura lazy loading de bloque
     */
    public function configure_block_lazy_loading( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $enabled = (bool) $request->get_param( 'enabled' );
        $threshold = sanitize_text_field( $request->get_param( 'threshold' ) );
        $placeholder = sanitize_text_field( $request->get_param( 'placeholder' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $lazy_config = array( 'enabled' => $enabled, 'threshold' => $threshold, 'placeholder' => $placeholder );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $lazy_config ) {
            $el['data']['_lazy_load'] = $lazy_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'lazy_config' => $lazy_config ), 200 );
    }

    /**
     * Configura prioridad de carga
     */
    public function set_blocks_load_priority( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $priorities = $request->get_param( 'priorities' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        foreach ( $priorities as $item ) {
            $block_id = $item['block_id'] ?? '';
            $priority = (int) ( $item['priority'] ?? 0 );

            $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $priority ) {
                $el['data']['_load_priority'] = $priority;
                return $el;
            } );
        }

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'priorities_set' => count( $priorities ) ), 200 );
    }

    // =============================================
}
