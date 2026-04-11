<?php
/**
 * Trait para Responsive/Breakpoints VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Responsive {


    /**
     * Obtiene estilos responsive de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $responsive_json = get_post_meta( $page_id, '_flavor_vbp_responsive_styles', true );
        $responsive = $responsive_json ? json_decode( $responsive_json, true ) : array();

        return new WP_REST_Response( array(
            'success'    => true,
            'responsive' => $responsive,
        ), 200 );
    }

    /**
     * Configura estilos responsive de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_block_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $breakpoint = $request->get_param( 'breakpoint' );
        $styles = $request->get_param( 'styles' );

        $responsive_json = get_post_meta( $page_id, '_flavor_vbp_responsive_styles', true );
        $responsive = $responsive_json ? json_decode( $responsive_json, true ) : array();

        if ( ! isset( $responsive[ $block_id ] ) ) {
            $responsive[ $block_id ] = array();
        }

        $responsive[ $block_id ][ $breakpoint ] = $styles;

        update_post_meta( $page_id, '_flavor_vbp_responsive_styles', wp_json_encode( $responsive ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Estilos para {$breakpoint} guardados.",
        ), 200 );
    }

    /**
     * Obtiene breakpoints personalizados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_custom_breakpoints( $request ) {
        $breakpoints = get_option( 'flavor_vbp_breakpoints', array(
            array( 'name' => 'mobile', 'max_width' => 767 ),
            array( 'name' => 'tablet', 'min_width' => 768, 'max_width' => 1023 ),
            array( 'name' => 'desktop', 'min_width' => 1024 ),
        ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'breakpoints' => $breakpoints,
        ), 200 );
    }

    /**
     * Configura breakpoints personalizados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_custom_breakpoints( $request ) {
        $breakpoints = $request->get_param( 'breakpoints' );

        update_option( 'flavor_vbp_breakpoints', $breakpoints );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Breakpoints guardados.',
        ), 200 );
    }

    /**
     * Configura visibilidad de bloque por breakpoint
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_block_visibility( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $visibility = array(
            'desktop' => (bool) $request->get_param( 'desktop' ),
            'tablet'  => (bool) $request->get_param( 'tablet' ),
            'mobile'  => (bool) $request->get_param( 'mobile' ),
        );

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $elements = $this->set_element_visibility( $elements, $block_id, $visibility );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Visibilidad actualizada.',
            'visibility' => $visibility,
        ), 200 );
    }

    /**
     * Establece visibilidad en elemento
     *
     * @param array  $elements   Elementos.
     * @param string $block_id   ID del bloque.
     * @param array  $visibility Configuración de visibilidad.
     * @return array
     */
    private function set_element_visibility( $elements, $block_id, $visibility ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $element['visibility'] = $visibility;
                return $elements;
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->set_element_visibility( $element['children'], $block_id, $visibility );
            }
        }
        return $elements;
    }

    // =============================================
}
