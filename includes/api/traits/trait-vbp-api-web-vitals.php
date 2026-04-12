<?php
/**
 * Trait para Métricas y Web Vitals VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_WebVitals {

    /**
     * Obtiene métricas de página
     */
    public function get_page_metrics( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $metrics = array(
            'total_blocks' => $this->count_elements( $elements ),
            'images' => $this->count_elements_by_type( $elements, 'image' ),
            'videos' => $this->count_elements_by_type( $elements, 'video' ),
            'forms' => $this->count_elements_by_type( $elements, 'form' ),
            'buttons' => $this->count_elements_by_type( $elements, 'button' ),
            'headings' => $this->count_elements_by_type( $elements, 'heading' ),
        );

        return new WP_REST_Response( array( 'success' => true, 'metrics' => $metrics ), 200 );
    }

    /**
     * Estima Web Vitals
     */
    public function estimate_web_vitals( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block_count = $this->count_elements( $elements );
        $image_count = $this->count_elements_by_type( $elements, 'image' );

        $estimated = array(
            'lcp' => array( 'value' => min( 2500 + ( $image_count * 100 ), 4000 ), 'unit' => 'ms', 'rating' => 'needs-improvement' ),
            'fid' => array( 'value' => min( 50 + ( $block_count * 2 ), 300 ), 'unit' => 'ms', 'rating' => 'good' ),
            'cls' => array( 'value' => 0.1 + ( $image_count * 0.02 ), 'unit' => '', 'rating' => 'good' ),
            'ttfb' => array( 'value' => 200, 'unit' => 'ms', 'rating' => 'good' ),
        );

        foreach ( $estimated as &$metric ) {
            if ( $metric['value'] > 2500 && isset( $metric['unit'] ) && $metric['unit'] === 'ms' ) {
                $metric['rating'] = 'poor';
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'estimated_vitals' => $estimated ), 200 );
    }

    /**
     * Obtiene sugerencias de Web Vitals
     */
    public function get_web_vitals_suggestions( $request ) {
        $suggestions = array(
            array( 'id' => 'lazy_images', 'title' => 'Lazy load images', 'impact' => 'high', 'description' => 'Aplica lazy loading a imágenes fuera del viewport inicial' ),
            array( 'id' => 'preload_lcp', 'title' => 'Preload LCP image', 'impact' => 'high', 'description' => 'Pre-carga la imagen más grande visible' ),
            array( 'id' => 'reduce_blocks', 'title' => 'Reduce block count', 'impact' => 'medium', 'description' => 'Simplifica la estructura de bloques' ),
            array( 'id' => 'critical_css', 'title' => 'Inline critical CSS', 'impact' => 'medium', 'description' => 'Incluye CSS crítico en línea' ),
            array( 'id' => 'defer_js', 'title' => 'Defer non-critical JS', 'impact' => 'medium', 'description' => 'Difiere scripts no esenciales' ),
        );
        return new WP_REST_Response( array( 'success' => true, 'suggestions' => $suggestions ), 200 );
    }

    // =============================================
}
