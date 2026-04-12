<?php
/**
 * Trait para Variables CSS Globales VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_CSSVariables {


    /**
     * Obtiene variables CSS globales
     */
    public function get_css_variables( $request ) {
        $variables = get_option( 'flavor_vbp_css_variables', array(
            'colors' => array(
                '--vbp-primary'   => '#3b82f6',
                '--vbp-secondary' => '#8b5cf6',
                '--vbp-accent'    => '#06b6d4',
                '--vbp-success'   => '#22c55e',
                '--vbp-warning'   => '#f59e0b',
                '--vbp-error'     => '#ef4444',
            ),
            'typography' => array(
                '--vbp-font-family'    => 'Inter, sans-serif',
                '--vbp-font-size-base' => '16px',
                '--vbp-line-height'    => '1.6',
            ),
            'spacing' => array(
                '--vbp-spacing-xs' => '4px',
                '--vbp-spacing-sm' => '8px',
                '--vbp-spacing-md' => '16px',
                '--vbp-spacing-lg' => '24px',
                '--vbp-spacing-xl' => '48px',
            ),
            'custom' => array(),
        ) );

        return new WP_REST_Response( array( 'success' => true, 'variables' => $variables ), 200 );
    }

    /**
     * Actualiza variables CSS globales
     */
    public function update_css_variables( $request ) {
        $variables = $request->get_param( 'variables' );
        $current = get_option( 'flavor_vbp_css_variables', array() );
        $merged = array_merge( $current, $variables );
        update_option( 'flavor_vbp_css_variables', $merged );

        return new WP_REST_Response( array( 'success' => true, 'variables' => $merged ), 200 );
    }

    /**
     * Crea grupo de variables CSS
     */
    public function create_css_variable_group( $request ) {
        $name = sanitize_title( $request->get_param( 'name' ) );
        $variables = $request->get_param( 'variables' );

        $current = get_option( 'flavor_vbp_css_variables', array() );
        $current[ $name ] = $variables;
        update_option( 'flavor_vbp_css_variables', $current );

        return new WP_REST_Response( array( 'success' => true, 'group' => $name, 'variables' => $variables ), 201 );
    }

    /**
     * Usa variable CSS en bloque
     */
    public function use_css_variable_in_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $property = sanitize_text_field( $request->get_param( 'property' ) );
        $variable = sanitize_text_field( $request->get_param( 'variable' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $property, $variable ) {
            $keys = explode( '.', $property );
            $target = &$el['styles'];
            foreach ( $keys as $i => $key ) {
                if ( $i === count( $keys ) - 1 ) {
                    $target[ $key ] = "var({$variable})";
                } else {
                    if ( ! isset( $target[ $key ] ) ) {
                        $target[ $key ] = array();
                    }
                    $target = &$target[ $key ];
                }
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'property' => $property, 'variable' => $variable ), 200 );
    }

    // =============================================
}
