<?php
/**
 * Trait para Responsive Design VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ResponsiveDesign {


    /**
     * Obtiene breakpoints responsive
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_responsive_breakpoints( $request ) {
        $breakpoints = get_option( 'flavor_vbp_breakpoints', array(
            array( 'id' => 'desktop', 'name' => 'Desktop', 'min_width' => 1200, 'max_width' => null ),
            array( 'id' => 'laptop', 'name' => 'Laptop', 'min_width' => 992, 'max_width' => 1199 ),
            array( 'id' => 'tablet', 'name' => 'Tablet', 'min_width' => 768, 'max_width' => 991 ),
            array( 'id' => 'mobile', 'name' => 'Mobile', 'min_width' => 0, 'max_width' => 767 ),
        ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'breakpoints' => $breakpoints,
        ), 200 );
    }

    /**
     * Actualiza breakpoints responsive
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_responsive_breakpoints( $request ) {
        $breakpoints = $request->get_param( 'breakpoints' );

        if ( ! is_array( $breakpoints ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Breakpoints inválidos.' ), 400 );
        }

        // Validar estructura
        foreach ( $breakpoints as &$bp ) {
            $bp['id'] = sanitize_title( $bp['id'] ?? '' );
            $bp['name'] = sanitize_text_field( $bp['name'] ?? '' );
            $bp['min_width'] = (int) ( $bp['min_width'] ?? 0 );
            $bp['max_width'] = isset( $bp['max_width'] ) ? (int) $bp['max_width'] : null;
        }

        update_option( 'flavor_vbp_breakpoints', $breakpoints );

        return new WP_REST_Response( array(
            'success'     => true,
            'breakpoints' => $breakpoints,
            'message'     => 'Breakpoints actualizados.',
        ), 200 );
    }

    /**
     * Obtiene estilos responsive de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $block = $this->find_element_by_id( $elements, $block_id );

        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $responsive_styles = $block['responsive'] ?? array();

        return new WP_REST_Response( array(
            'success'           => true,
            'block_id'          => $block_id,
            'responsive_styles' => $responsive_styles,
        ), 200 );
    }

    /**
     * Actualiza estilos responsive de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_block_responsive_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $breakpoint = sanitize_text_field( $request->get_param( 'breakpoint' ) );
        $styles = $request->get_param( 'styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $breakpoint, $styles ) {
            if ( ! isset( $element['responsive'] ) ) {
                $element['responsive'] = array();
            }
            $element['responsive'][ $breakpoint ] = $styles;
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'block_id'   => $block_id,
            'breakpoint' => $breakpoint,
            'message'    => 'Estilos responsive actualizados.',
        ), 200 );
    }

    /**
     * Copia estilos entre breakpoints
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function copy_styles_between_breakpoints( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $from_breakpoint = sanitize_text_field( $request->get_param( 'from_breakpoint' ) );
        $to_breakpoints = $request->get_param( 'to_breakpoints' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $block = $this->find_element_by_id( $elements, $block_id );

        if ( ! $block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $source_styles = $block['responsive'][ $from_breakpoint ] ?? $block['styles'] ?? array();

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $source_styles, $to_breakpoints ) {
            if ( ! isset( $element['responsive'] ) ) {
                $element['responsive'] = array();
            }
            foreach ( $to_breakpoints as $bp ) {
                $element['responsive'][ $bp ] = $source_styles;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'from'           => $from_breakpoint,
            'to'             => $to_breakpoints,
            'copied_to'      => count( $to_breakpoints ),
            'message'        => 'Estilos copiados a ' . count( $to_breakpoints ) . ' breakpoints.',
        ), 200 );
    }

    // =============================================
}
