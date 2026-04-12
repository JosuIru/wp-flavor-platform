<?php
/**
 * Trait para Layout Avanzado VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_LayoutAdvanced {

    /**
     * Aplica grid avanzado
     */
    public function apply_advanced_grid( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $columns = sanitize_text_field( $request->get_param( 'columns' ) );
        $rows = $request->get_param( 'rows' );
        $gap = sanitize_text_field( $request->get_param( 'gap' ) );
        $gap_responsive = $request->get_param( 'gap_responsive' );
        $auto_flow = $request->get_param( 'auto_flow' );
        $align_items = $request->get_param( 'align_items' );
        $justify_items = $request->get_param( 'justify_items' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $grid_styles = array(
            'display' => 'grid',
            'gridTemplateColumns' => $columns,
            'gap' => $gap,
        );

        if ( $rows ) {
            $grid_styles['gridTemplateRows'] = sanitize_text_field( $rows );
        }
        if ( $auto_flow ) {
            $grid_styles['gridAutoFlow'] = sanitize_text_field( $auto_flow );
        }
        if ( $align_items ) {
            $grid_styles['alignItems'] = sanitize_text_field( $align_items );
        }
        if ( $justify_items ) {
            $grid_styles['justifyItems'] = sanitize_text_field( $justify_items );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $grid_styles, $gap_responsive ) {
            if ( ! isset( $el['styles']['layout'] ) ) {
                $el['styles']['layout'] = array();
            }
            $el['styles']['layout'] = array_merge( $el['styles']['layout'], $grid_styles );
            if ( $gap_responsive ) {
                $el['responsive'] = $el['responsive'] ?? array();
                foreach ( $gap_responsive as $breakpoint => $bp_gap ) {
                    $el['responsive'][ $breakpoint ] = $el['responsive'][ $breakpoint ] ?? array();
                    $el['responsive'][ $breakpoint ]['gap'] = $bp_gap;
                }
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'grid' => $grid_styles ), 200 );
    }

    /**
     * Aplica aspect ratio
     */
    public function apply_aspect_ratio( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $ratio = sanitize_text_field( $request->get_param( 'ratio' ) );
        $custom_ratio = $request->get_param( 'custom_ratio' );
        $object_fit = sanitize_text_field( $request->get_param( 'object_fit' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $aspect_ratio = $ratio === 'custom' ? sanitize_text_field( $custom_ratio ) : $ratio;

        $aspect_styles = array(
            'aspectRatio' => $aspect_ratio,
            'objectFit' => $object_fit,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $aspect_styles ) {
            if ( ! isset( $el['styles']['layout'] ) ) {
                $el['styles']['layout'] = array();
            }
            $el['styles']['layout'] = array_merge( $el['styles']['layout'], $aspect_styles );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'aspect_ratio' => $aspect_ratio ), 200 );
    }

    /**
     * Aplica container query
     */
    public function apply_container_query( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $rules = $request->get_param( 'rules' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $name, $type, $rules ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['containerType'] = $type;
            $el['styles']['advanced']['containerName'] = $name;
            $el['data']['_container_query'] = array( 'name' => $name, 'type' => $type, 'rules' => $rules );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'container_name' => $name ), 200 );
    }

    // =============================================
}
