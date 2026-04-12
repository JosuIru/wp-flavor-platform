<?php
/**
 * Trait para Formularios VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Forms {


    /**
     * Obtiene formularios de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_forms( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $forms = $this->find_elements_by_type( $elements, 'form' );

        $forms_data = array_map( function( $form ) {
            return array(
                'id'     => $form['id'],
                'name'   => $form['props']['name'] ?? 'Sin nombre',
                'fields' => count( $form['children'] ?? array() ),
                'config' => $form['props'] ?? array(),
            );
        }, $forms );

        return new WP_REST_Response( array(
            'success' => true,
            'forms'   => $forms_data,
            'count'   => count( $forms_data ),
        ), 200 );
    }

    /**
     * Busca elementos por tipo
     *
     * @param array  $elements Elementos.
     * @param string $element_type Tipo de elemento.
     * @return array
     */
    private function find_elements_by_type( $elements, $element_type ) {
        $found = array();
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === $element_type ) {
                $found[] = $element;
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $found = array_merge( $found, $this->find_elements_by_type( $element['children'], $element_type ) );
            }
        }
        return $found;
    }

    /**
     * Configura formulario de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_page_form( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $form_id = sanitize_text_field( $request->get_param( 'form_id' ) );
        $config = $request->get_param( 'config' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements = $this->update_element_by_id( $elements, $form_id, function( $element ) use ( $config ) {
            $element['props'] = array_merge( $element['props'] ?? array(), $config );
            return $element;
        } );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Formulario configurado.',
        ), 200 );
    }

    /**
     * Obtiene envíos de formulario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_form_submissions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $form_id = sanitize_text_field( $request->get_param( 'form_id' ) );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $submissions = get_post_meta( $page_id, '_flavor_vbp_form_submissions_' . $form_id, true ) ?: array();

        // Obtener últimos N envíos
        $submissions = array_slice( $submissions, -$limit );

        return new WP_REST_Response( array(
            'success'     => true,
            'form_id'     => $form_id,
            'submissions' => $submissions,
            'total'       => count( $submissions ),
        ), 200 );
    }

    /**
     * Crea formulario
     */
    public function create_form( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $fields = $request->get_param( 'fields' );
        $submit_button = $request->get_param( 'submit_button' );
        $validation = $request->get_param( 'validation' );
        $action = sanitize_text_field( $request->get_param( 'action' ) );
        $success_message = sanitize_text_field( $request->get_param( 'success_message' ) ) ?: '¡Formulario enviado correctamente!';
        $error_message = sanitize_text_field( $request->get_param( 'error_message' ) ) ?: 'Ha ocurrido un error. Por favor, inténtalo de nuevo.';

        $form_id = 'form_' . bin2hex( random_bytes( 6 ) );

        $form_element = array(
            'id' => $form_id,
            'type' => 'form',
            'name' => $name,
            'data' => array(
                'form_name' => $name,
                'fields' => $fields,
                'submit_button' => $submit_button ?: array( 'text' => 'Enviar', 'style' => 'primary' ),
                'validation' => $validation,
                'action' => $action,
                'success_message' => $success_message,
                'error_message' => $error_message,
            ),
            'styles' => $this->get_default_styles(),
            'children' => array(),
        );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements[] = $form_element;
        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'form_id' => $form_id, 'form' => $form_element ), 201 );
    }

    /**
     * Obtiene tipos de campos de formulario
     */
    public function get_form_field_types( $request ) {
        $field_types = array(
            array( 'type' => 'text', 'name' => 'Texto', 'icon' => 'text', 'attributes' => array( 'placeholder', 'maxlength', 'minlength' ) ),
            array( 'type' => 'email', 'name' => 'Email', 'icon' => 'mail', 'attributes' => array( 'placeholder' ), 'validation' => 'email' ),
            array( 'type' => 'tel', 'name' => 'Teléfono', 'icon' => 'phone', 'attributes' => array( 'placeholder', 'pattern' ) ),
            array( 'type' => 'number', 'name' => 'Número', 'icon' => 'hash', 'attributes' => array( 'min', 'max', 'step' ) ),
            array( 'type' => 'textarea', 'name' => 'Área de texto', 'icon' => 'align-left', 'attributes' => array( 'rows', 'maxlength' ) ),
            array( 'type' => 'select', 'name' => 'Selector', 'icon' => 'chevron-down', 'attributes' => array( 'options', 'multiple' ) ),
            array( 'type' => 'checkbox', 'name' => 'Casilla', 'icon' => 'check-square', 'attributes' => array() ),
            array( 'type' => 'radio', 'name' => 'Radio', 'icon' => 'circle', 'attributes' => array( 'options' ) ),
            array( 'type' => 'date', 'name' => 'Fecha', 'icon' => 'calendar', 'attributes' => array( 'min', 'max' ) ),
            array( 'type' => 'time', 'name' => 'Hora', 'icon' => 'clock', 'attributes' => array() ),
            array( 'type' => 'file', 'name' => 'Archivo', 'icon' => 'upload', 'attributes' => array( 'accept', 'multiple', 'max_size' ) ),
            array( 'type' => 'hidden', 'name' => 'Oculto', 'icon' => 'eye-off', 'attributes' => array( 'value' ) ),
            array( 'type' => 'password', 'name' => 'Contraseña', 'icon' => 'lock', 'attributes' => array( 'minlength' ) ),
            array( 'type' => 'url', 'name' => 'URL', 'icon' => 'link', 'attributes' => array( 'placeholder' ), 'validation' => 'url' ),
            array( 'type' => 'range', 'name' => 'Rango', 'icon' => 'sliders', 'attributes' => array( 'min', 'max', 'step' ) ),
            array( 'type' => 'color', 'name' => 'Color', 'icon' => 'droplet', 'attributes' => array() ),
        );

        return new WP_REST_Response( array( 'success' => true, 'field_types' => $field_types ), 200 );
    }

    /**
     * Obtiene validaciones de formulario
     */
    public function get_form_validations( $request ) {
        $validations = array(
            array( 'type' => 'required', 'name' => 'Requerido', 'message' => 'Este campo es obligatorio' ),
            array( 'type' => 'email', 'name' => 'Email válido', 'message' => 'Introduce un email válido' ),
            array( 'type' => 'url', 'name' => 'URL válida', 'message' => 'Introduce una URL válida' ),
            array( 'type' => 'phone', 'name' => 'Teléfono válido', 'message' => 'Introduce un teléfono válido' ),
            array( 'type' => 'min', 'name' => 'Valor mínimo', 'params' => array( 'value' ) ),
            array( 'type' => 'max', 'name' => 'Valor máximo', 'params' => array( 'value' ) ),
            array( 'type' => 'minlength', 'name' => 'Longitud mínima', 'params' => array( 'length' ) ),
            array( 'type' => 'maxlength', 'name' => 'Longitud máxima', 'params' => array( 'length' ) ),
            array( 'type' => 'pattern', 'name' => 'Patrón regex', 'params' => array( 'regex' ) ),
            array( 'type' => 'match', 'name' => 'Coincidir con campo', 'params' => array( 'field' ) ),
        );

        return new WP_REST_Response( array( 'success' => true, 'validations' => $validations ), 200 );
    }

    // =============================================
}
