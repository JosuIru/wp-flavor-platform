<?php
/**
 * Trait para Visibilidad Condicional VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Visibility {


    /**
     * Configura reglas de visibilidad
     */
    public function set_block_visibility_rules( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $rules = $request->get_param( 'rules' );
        $logic = sanitize_text_field( $request->get_param( 'logic' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $rules, $logic ) {
            $el['data']['_visibility'] = array( 'rules' => $rules, 'logic' => $logic );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'rules' => $rules, 'logic' => $logic ), 200 );
    }

    /**
     * Obtiene reglas de visibilidad
     */
    public function get_block_visibility_rules( $request ) {
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
            'visibility' => $block['data']['_visibility'] ?? null,
        ), 200 );
    }

    /**
     * Tipos de condiciones de visibilidad
     */
    public function get_visibility_condition_types( $request ) {
        $types = array(
            array( 'id' => 'user_logged_in', 'name' => 'Usuario autenticado', 'params' => array() ),
            array( 'id' => 'user_role', 'name' => 'Rol de usuario', 'params' => array( 'role' ) ),
            array( 'id' => 'device_type', 'name' => 'Tipo de dispositivo', 'params' => array( 'device' ) ),
            array( 'id' => 'date_range', 'name' => 'Rango de fechas', 'params' => array( 'start', 'end' ) ),
            array( 'id' => 'time_range', 'name' => 'Rango horario', 'params' => array( 'start', 'end' ) ),
            array( 'id' => 'url_param', 'name' => 'Parámetro URL', 'params' => array( 'param', 'value' ) ),
            array( 'id' => 'cookie', 'name' => 'Cookie', 'params' => array( 'name', 'value' ) ),
            array( 'id' => 'referrer', 'name' => 'Referrer', 'params' => array( 'contains' ) ),
            array( 'id' => 'language', 'name' => 'Idioma', 'params' => array( 'lang' ) ),
            array( 'id' => 'geolocation', 'name' => 'Geolocalización', 'params' => array( 'country', 'region' ) ),
            array( 'id' => 'custom_field', 'name' => 'Campo personalizado', 'params' => array( 'field', 'value', 'operator' ) ),
        );

        return new WP_REST_Response( array( 'success' => true, 'condition_types' => $types ), 200 );
    }

    /**
     * Simula visibilidad con contexto
     */
    public function simulate_visibility( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $context = $request->get_param( 'context' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $visibility_results = array();
        $this->check_elements_visibility( $elements, $context, $visibility_results );

        return new WP_REST_Response( array( 'success' => true, 'results' => $visibility_results ), 200 );
    }

    /**
     * Verifica visibilidad de elementos recursivamente
     */
    private function check_elements_visibility( $elements, $context, &$results ) {
        foreach ( $elements as $element ) {
            $visibility = $element['data']['_visibility'] ?? null;
            $is_visible = true;

            if ( $visibility && ! empty( $visibility['rules'] ) ) {
                $is_visible = $this->evaluate_visibility_rules( $visibility['rules'], $visibility['logic'], $context );
            }

            $results[ $element['id'] ] = $is_visible;

            if ( ! empty( $element['children'] ) ) {
                $this->check_elements_visibility( $element['children'], $context, $results );
            }
        }
    }

    /**
     * Evalúa reglas de visibilidad
     */
    private function evaluate_visibility_rules( $rules, $logic, $context ) {
        $results = array();
        foreach ( $rules as $rule ) {
            $results[] = $this->evaluate_single_rule( $rule, $context );
        }

        if ( $logic === 'all' ) {
            return ! in_array( false, $results, true );
        }
        return in_array( true, $results, true );
    }

    /**
     * Evalúa una regla individual
     */
    private function evaluate_single_rule( $rule, $context ) {
        $type = $rule['type'] ?? '';
        switch ( $type ) {
            case 'user_logged_in':
                return ( $context['user_logged_in'] ?? false ) === true;
            case 'user_role':
                return in_array( $rule['role'] ?? '', $context['user_roles'] ?? array(), true );
            case 'device_type':
                return ( $context['device_type'] ?? 'desktop' ) === ( $rule['device'] ?? 'desktop' );
            case 'language':
                return ( $context['language'] ?? 'es' ) === ( $rule['lang'] ?? 'es' );
            default:
                return true;
        }
    }

    // =============================================
}
