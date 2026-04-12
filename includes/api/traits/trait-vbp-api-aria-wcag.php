<?php
/**
 * Trait para ARIA y WCAG VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_AriaWcag {

    /**
     * Configura atributos ARIA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_block_aria_attributes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $attributes = $request->get_param( 'attributes' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $attributes ) {
            $el['data']['_aria'] = $attributes;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'aria' => $attributes ), 200 );
    }

    /**
     * Obtiene atributos ARIA
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_aria_attributes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block = $this->find_element_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array( 'success' => true, 'aria' => $block['data']['_aria'] ?? array() ), 200 );
    }

    /**
     * Verifica contraste de colores WCAG
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function check_wcag_contrast( $request ) {
        $foreground = sanitize_text_field( $request->get_param( 'foreground' ) );
        $background = sanitize_text_field( $request->get_param( 'background' ) );
        $font_size = (float) $request->get_param( 'font_size' );

        $fg_lum = $this->calculate_wcag_luminance( $foreground );
        $bg_lum = $this->calculate_wcag_luminance( $background );
        $ratio = ( max( $fg_lum, $bg_lum ) + 0.05 ) / ( min( $fg_lum, $bg_lum ) + 0.05 );

        $is_large = $font_size >= 18;
        $aa_threshold = $is_large ? 3.0 : 4.5;
        $aaa_threshold = $is_large ? 4.5 : 7.0;

        return new WP_REST_Response( array(
            'success'    => true,
            'ratio'      => round( $ratio, 2 ),
            'passes_aa'  => $ratio >= $aa_threshold,
            'passes_aaa' => $ratio >= $aaa_threshold,
        ), 200 );
    }

    /**
     * Calcula luminancia WCAG de color
     *
     * @param string $color Color en hexadecimal.
     * @return float
     */
    private function calculate_wcag_luminance( $color ) {
        $hex = ltrim( $color, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
        $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
        $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    // =============================================
}
