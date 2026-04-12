<?php
/**
 * Trait para Animaciones y Keyframes VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Keyframes {


    /**
     * Lista librería de animaciones
     */
    public function list_animations_library( $request ) {
        $category = $request->get_param( 'category' );

        $animations = array(
            'entrance' => array(
                array( 'id' => 'fadeIn', 'name' => 'Fade In', 'duration' => '0.5s' ),
                array( 'id' => 'fadeInUp', 'name' => 'Fade In Up', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInDown', 'name' => 'Fade In Down', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInLeft', 'name' => 'Fade In Left', 'duration' => '0.6s' ),
                array( 'id' => 'fadeInRight', 'name' => 'Fade In Right', 'duration' => '0.6s' ),
                array( 'id' => 'slideInUp', 'name' => 'Slide In Up', 'duration' => '0.5s' ),
                array( 'id' => 'slideInDown', 'name' => 'Slide In Down', 'duration' => '0.5s' ),
                array( 'id' => 'zoomIn', 'name' => 'Zoom In', 'duration' => '0.4s' ),
                array( 'id' => 'bounceIn', 'name' => 'Bounce In', 'duration' => '0.75s' ),
            ),
            'exit' => array(
                array( 'id' => 'fadeOut', 'name' => 'Fade Out', 'duration' => '0.5s' ),
                array( 'id' => 'fadeOutUp', 'name' => 'Fade Out Up', 'duration' => '0.6s' ),
                array( 'id' => 'fadeOutDown', 'name' => 'Fade Out Down', 'duration' => '0.6s' ),
                array( 'id' => 'zoomOut', 'name' => 'Zoom Out', 'duration' => '0.4s' ),
            ),
            'attention' => array(
                array( 'id' => 'pulse', 'name' => 'Pulse', 'duration' => '1s' ),
                array( 'id' => 'shake', 'name' => 'Shake', 'duration' => '0.8s' ),
                array( 'id' => 'bounce', 'name' => 'Bounce', 'duration' => '1s' ),
                array( 'id' => 'flash', 'name' => 'Flash', 'duration' => '1s' ),
                array( 'id' => 'wobble', 'name' => 'Wobble', 'duration' => '1s' ),
            ),
            'background' => array(
                array( 'id' => 'gradientShift', 'name' => 'Gradient Shift', 'duration' => '5s' ),
                array( 'id' => 'colorPulse', 'name' => 'Color Pulse', 'duration' => '3s' ),
            ),
        );

        // Añadir animaciones personalizadas
        $custom_animations = get_option( 'flavor_vbp_custom_animations', array() );
        if ( ! empty( $custom_animations ) ) {
            $animations['custom'] = $custom_animations;
        }

        if ( $category && isset( $animations[ $category ] ) ) {
            return new WP_REST_Response( array(
                'success'    => true,
                'category'   => $category,
                'animations' => $animations[ $category ],
            ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true, 'animations' => $animations ), 200 );
    }

    /**
     * Crea animación personalizada
     */
    public function create_custom_animation( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $keyframes = $request->get_param( 'keyframes' );
        $duration = sanitize_text_field( $request->get_param( 'duration' ) );
        $timing = sanitize_text_field( $request->get_param( 'timing' ) );
        $iterations = sanitize_text_field( $request->get_param( 'iterations' ) );

        $animation_id = 'custom_' . sanitize_title( $name ) . '_' . time();

        $animation = array(
            'id'         => $animation_id,
            'name'       => $name,
            'keyframes'  => $keyframes,
            'duration'   => $duration,
            'timing'     => $timing,
            'iterations' => $iterations,
            'created_at' => current_time( 'mysql' ),
        );

        $custom_animations = get_option( 'flavor_vbp_custom_animations', array() );
        $custom_animations[] = $animation;
        update_option( 'flavor_vbp_custom_animations', $custom_animations );

        return new WP_REST_Response( array( 'success' => true, 'animation' => $animation ), 201 );
    }

    /**
     * Aplica animación a bloque
     */
    public function apply_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $animation = sanitize_text_field( $request->get_param( 'animation' ) );
        $trigger = sanitize_text_field( $request->get_param( 'trigger' ) );
        $delay = sanitize_text_field( $request->get_param( 'delay' ) );
        $duration = $request->get_param( 'duration' );
        $threshold = (float) $request->get_param( 'threshold' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $animation_config = array(
            'name'      => $animation,
            'trigger'   => $trigger,
            'delay'     => $delay,
            'threshold' => $threshold,
        );

        if ( $duration ) {
            $animation_config['duration'] = sanitize_text_field( $duration );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $animation_config ) {
            $el['data']['_animation'] = $animation_config;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'animation' => $animation_config ), 200 );
    }

    /**
     * Obtiene animación de bloque
     */
    public function get_block_animation( $request ) {
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
            'success'   => true,
            'animation' => $block['data']['_animation'] ?? null,
        ), 200 );
    }

    /**
     * Elimina animación de bloque
     */
    public function remove_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) {
            unset( $el['data']['_animation'] );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Animación eliminada.' ), 200 );
    }

    /**
     * Preview de animación de bloque
     */
    public function preview_block_animation( $request ) {
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

        $animation = $block['data']['_animation'] ?? null;
        if ( ! $animation ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay animación configurada.' ), 404 );
        }

        $preview_url = add_query_arg( array(
            'vbp_preview'   => 1,
            'animation'     => 1,
            'block_id'      => $block_id,
            'autoplay'      => 1,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'animation'   => $animation,
            'preview_url' => $preview_url,
        ), 200 );
    }

    // =============================================
}
