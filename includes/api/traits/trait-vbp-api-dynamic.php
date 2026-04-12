<?php
/**
 * Trait para Componentes Dinámicos VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_DynamicComponents {


    /**
     * Obtiene configuración de slider
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_slider_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $slider_id = sanitize_text_field( $request->get_param( 'slider_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $slider_element = $this->find_element_by_id( $elements, $slider_id );

        if ( ! $slider_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Slider no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $slider_element['props'] ?? array(),
            'slides'  => $slider_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de slider
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_slider_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $slider_id = sanitize_text_field( $request->get_param( 'slider_id' ) );
        $config = $request->get_param( 'config' );
        $slides = $request->get_param( 'slides' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $slider_id, function( $element ) use ( $config, $slides ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $slides ) {
                $element['children'] = $slides;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Slider actualizado.',
        ), 200 );
    }

    /**
     * Obtiene configuración de tabs
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_tabs_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $tabs_id = sanitize_text_field( $request->get_param( 'tabs_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $tabs_element = $this->find_element_by_id( $elements, $tabs_id );

        if ( ! $tabs_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Tabs no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $tabs_element['props'] ?? array(),
            'tabs'    => $tabs_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de tabs
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_tabs_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $tabs_id = sanitize_text_field( $request->get_param( 'tabs_id' ) );
        $config = $request->get_param( 'config' );
        $tabs = $request->get_param( 'tabs' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $tabs_id, function( $element ) use ( $config, $tabs ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $tabs ) {
                $element['children'] = $tabs;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Tabs actualizado.',
        ), 200 );
    }

    /**
     * Obtiene configuración de accordion
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_accordion_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $accordion_id = sanitize_text_field( $request->get_param( 'accordion_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $accordion_element = $this->find_element_by_id( $elements, $accordion_id );

        if ( ! $accordion_element ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Accordion no encontrado.' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $accordion_element['props'] ?? array(),
            'items'   => $accordion_element['children'] ?? array(),
        ), 200 );
    }

    /**
     * Actualiza configuración de accordion
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_accordion_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $accordion_id = sanitize_text_field( $request->get_param( 'accordion_id' ) );
        $config = $request->get_param( 'config' );
        $items = $request->get_param( 'items' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $accordion_id, function( $element ) use ( $config, $items ) {
            if ( $config ) {
                $element['props'] = array_merge( $element['props'] ?? array(), $config );
            }
            if ( $items ) {
                $element['children'] = $items;
            }
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Accordion actualizado.',
        ), 200 );
    }

    // =============================================
}
