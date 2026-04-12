<?php
/**
 * Trait para Transformaciones de Bloques VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_BlockTransforms {


    /**
     * Rota un bloque
     */
    public function rotate_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $angle = (float) $request->get_param( 'angle' );
        $origin = sanitize_text_field( $request->get_param( 'origin' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $transform_origin = $this->get_transform_origin( $origin, $request );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $angle, $transform_origin ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = 'rotate(' . $angle . 'deg)';
            $el['styles']['advanced']['transformOrigin'] = $transform_origin;
            $el['data']['_transform'] = array( 'rotate' => $angle, 'origin' => $transform_origin );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array(
            'success' => true,
            'angle'   => $angle,
            'origin'  => $transform_origin,
        ), 200 );
    }

    /**
     * Escala un bloque
     */
    public function scale_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $scale_x = (float) $request->get_param( 'scale_x' );
        $scale_y = (float) $request->get_param( 'scale_y' );
        $uniform = (bool) $request->get_param( 'uniform' );

        if ( $uniform ) {
            $scale_y = $scale_x;
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $scale_x, $scale_y ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "scale({$scale_x}, {$scale_y})";
            $el['data']['_transform'] = array( 'scaleX' => $scale_x, 'scaleY' => $scale_y );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'scale_x' => $scale_x, 'scale_y' => $scale_y ), 200 );
    }

    /**
     * Sesga un bloque (skew)
     */
    public function skew_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $skew_x = (float) $request->get_param( 'skew_x' );
        $skew_y = (float) $request->get_param( 'skew_y' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $skew_x, $skew_y ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "skew({$skew_x}deg, {$skew_y}deg)";
            $el['data']['_transform'] = array( 'skewX' => $skew_x, 'skewY' => $skew_y );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'skew_x' => $skew_x, 'skew_y' => $skew_y ), 200 );
    }

    /**
     * Voltea un bloque
     */
    public function flip_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );

        $scale_x = 1;
        $scale_y = 1;
        if ( $direction === 'horizontal' || $direction === 'both' ) {
            $scale_x = -1;
        }
        if ( $direction === 'vertical' || $direction === 'both' ) {
            $scale_y = -1;
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $scale_x, $scale_y, $direction ) {
            if ( ! isset( $el['styles']['advanced'] ) ) {
                $el['styles']['advanced'] = array();
            }
            $el['styles']['advanced']['transform'] = "scale({$scale_x}, {$scale_y})";
            $el['data']['_flip'] = $direction;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'direction' => $direction ), 200 );
    }

    /**
     * Resetea transformaciones de bloque
     */
    public function reset_block_transforms( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) {
            unset( $el['styles']['advanced']['transform'] );
            unset( $el['styles']['advanced']['transformOrigin'] );
            unset( $el['data']['_transform'] );
            unset( $el['data']['_flip'] );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Transformaciones reseteadas.' ), 200 );
    }

    /**
     * Obtiene transformaciones de bloque
     */
    public function get_block_transforms( $request ) {
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

        return new WP_REST_Response( array(
            'success'    => true,
            'transform'  => $block['styles']['advanced']['transform'] ?? 'none',
            'origin'     => $block['styles']['advanced']['transformOrigin'] ?? 'center',
            'data'       => $block['data']['_transform'] ?? array(),
        ), 200 );
    }

    /**
     * Helper para obtener transform origin
     */
    private function get_transform_origin( $origin, $request ) {
        $origins = array(
            'center'       => 'center center',
            'top-left'     => 'top left',
            'top-right'    => 'top right',
            'bottom-left'  => 'bottom left',
            'bottom-right' => 'bottom right',
        );

        if ( $origin === 'custom' ) {
            $origin_x = $request->get_param( 'origin_x' ) ?: '50%';
            $origin_y = $request->get_param( 'origin_y' ) ?: '50%';
            return "{$origin_x} {$origin_y}";
        }

        return $origins[ $origin ] ?? 'center center';
    }

    // =============================================
}
