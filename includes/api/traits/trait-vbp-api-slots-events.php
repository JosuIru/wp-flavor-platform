<?php
/**
 * Trait para Slots y Eventos VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_SlotsEvents {

    /**
     * Define slot en bloque
     */
    public function define_block_slot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $slot_name = sanitize_text_field( $request->get_param( 'slot_name' ) );
        $allowed_blocks = $request->get_param( 'allowed_blocks' ) ?: array();
        $max_items = (int) $request->get_param( 'max_items' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $slot = array( 'name' => $slot_name, 'allowed_blocks' => $allowed_blocks, 'max_items' => $max_items, 'items' => array() );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $slot_name, $slot ) {
            if ( ! isset( $el['data']['_slots'] ) ) { $el['data']['_slots'] = array(); }
            $el['data']['_slots'][ $slot_name ] = $slot;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'slot' => $slot ), 201 );
    }

    /**
     * Obtiene slots de bloque
     */
    public function get_block_slots( $request ) {
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

        return new WP_REST_Response( array( 'success' => true, 'slots' => $block['data']['_slots'] ?? array() ), 200 );
    }

    /**
     * Inserta bloque en slot
     */
    public function insert_block_into_slot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $slot_name = sanitize_text_field( $request->get_param( 'slot_name' ) );
        $block = $request->get_param( 'block' );
        $position = (int) $request->get_param( 'position' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $block['id'] = 'el_' . bin2hex( random_bytes( 6 ) );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $slot_name, $block, $position ) {
            if ( ! isset( $el['data']['_slots'][ $slot_name ] ) ) { return $el; }
            if ( $position < 0 ) {
                $el['data']['_slots'][ $slot_name ]['items'][] = $block;
            } else {
                array_splice( $el['data']['_slots'][ $slot_name ]['items'], $position, 0, array( $block ) );
            }
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'block_id' => $block['id'] ), 201 );
    }

    /**
     * Configura evento de bloque
     */
    public function set_block_event_handler( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $event = sanitize_text_field( $request->get_param( 'event' ) );
        $actions = $request->get_param( 'actions' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $event, $actions ) {
            if ( ! isset( $el['data']['_events'] ) ) { $el['data']['_events'] = array(); }
            $el['data']['_events'][ $event ] = $actions;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'event' => $event, 'actions' => $actions ), 200 );
    }

    /**
     * Obtiene eventos de bloque
     */
    public function get_block_event_handlers( $request ) {
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

        return new WP_REST_Response( array( 'success' => true, 'events' => $block['data']['_events'] ?? array() ), 200 );
    }

    /**
     * Tipos de acciones de eventos
     */
    public function get_event_action_types( $request ) {
        $types = array(
            array( 'id' => 'navigate', 'name' => 'Navegar a URL', 'params' => array( 'url', 'target' ) ),
            array( 'id' => 'scroll_to', 'name' => 'Scroll a elemento', 'params' => array( 'element_id' ) ),
            array( 'id' => 'toggle_class', 'name' => 'Alternar clase', 'params' => array( 'element_id', 'class' ) ),
            array( 'id' => 'show_modal', 'name' => 'Mostrar modal', 'params' => array( 'modal_id' ) ),
            array( 'id' => 'close_modal', 'name' => 'Cerrar modal', 'params' => array() ),
            array( 'id' => 'play_animation', 'name' => 'Reproducir animación', 'params' => array( 'element_id', 'animation' ) ),
            array( 'id' => 'submit_form', 'name' => 'Enviar formulario', 'params' => array( 'form_id' ) ),
            array( 'id' => 'set_value', 'name' => 'Establecer valor', 'params' => array( 'variable', 'value' ) ),
        );
        return new WP_REST_Response( array( 'success' => true, 'action_types' => $types ), 200 );
    }

    // =============================================
}
