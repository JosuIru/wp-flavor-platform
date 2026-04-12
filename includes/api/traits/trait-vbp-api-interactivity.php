<?php
/**
 * Trait para Interactividad Avanzada VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Interactivity {

    /**
     * Configura estados de hover avanzados
     */
    public function configure_hover_states( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $styles = $request->get_param( 'styles' );
        $transition = $request->get_param( 'transition' );
        $transform = $request->get_param( 'transform' );
        $filter = $request->get_param( 'filter' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $hover_config = array(
            'styles' => $styles,
            'transition' => $transition,
            'transform' => $transform,
            'filter' => $filter,
        );

        $transition_string = ( $transition['duration'] ?? '0.3s' ) . ' ' . ( $transition['easing'] ?? 'ease-out' );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $hover_config, $transition_string ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transition'] = "all {$transition_string}";
            $el['data']['_hover_states'] = $hover_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'hover_config' => $hover_config ), 200 );
    }

    /**
     * Configura scroll behavior
     */
    public function configure_scroll_behavior( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $smooth_scroll = (bool) $request->get_param( 'smooth_scroll' );
        $scroll_snap = $request->get_param( 'scroll_snap' );
        $scroll_padding = $request->get_param( 'scroll_padding' );
        $overscroll_behavior = $request->get_param( 'overscroll_behavior' );

        $scroll_config = array(
            'smooth_scroll' => $smooth_scroll,
            'scroll_snap' => $scroll_snap,
            'scroll_padding' => $scroll_padding,
            'overscroll_behavior' => $overscroll_behavior,
        );

        update_post_meta( $page_id, '_vbp_scroll_behavior', $scroll_config );

        return new WP_REST_Response( array( 'success' => true, 'scroll_behavior' => $scroll_config ), 200 );
    }

    /**
     * Configura parallax avanzado
     */
    public function configure_advanced_parallax( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $speed = (float) $request->get_param( 'speed' );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );
        $scale = (bool) $request->get_param( 'scale' );
        $rotate = (bool) $request->get_param( 'rotate' );
        $opacity = (bool) $request->get_param( 'opacity' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $parallax_config = array(
            'speed' => $speed,
            'direction' => $direction,
            'scale' => $scale,
            'rotate' => $rotate,
            'opacity' => $opacity,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $parallax_config ) {
            $el['data']['_parallax'] = $parallax_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'parallax' => $parallax_config ), 200 );
    }

    /**
     * Configura cursor personalizado
     */
    public function configure_custom_cursor( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $custom_url = $request->get_param( 'custom_url' );
        $hover_type = $request->get_param( 'hover_type' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $cursor_value = $type;
        if ( $type === 'custom' && $custom_url ) {
            $cursor_value = "url({$custom_url}), auto";
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $cursor_value, $hover_type ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['cursor'] = $cursor_value;
            if ( $hover_type ) {
                $el['data']['_cursor_hover'] = $hover_type;
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'cursor' => $cursor_value ), 200 );
    }

    // =============================================
}
