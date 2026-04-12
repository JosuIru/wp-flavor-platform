<?php
/**
 * Trait para Datos Dinámicos VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_DynamicData {


    /**
     * Lista fuentes de datos
     */
    public function list_data_sources( $request ) {
        $sources = array(
            array( 'id' => 'post', 'name' => 'Post/Página', 'fields' => array( 'title', 'content', 'excerpt', 'featured_image', 'author', 'date', 'categories', 'tags' ) ),
            array( 'id' => 'user', 'name' => 'Usuario actual', 'fields' => array( 'display_name', 'email', 'avatar', 'role', 'meta' ) ),
            array( 'id' => 'option', 'name' => 'Opciones WP', 'fields' => array( 'blogname', 'blogdescription', 'admin_email', 'custom' ) ),
            array( 'id' => 'acf', 'name' => 'Advanced Custom Fields', 'fields' => array( 'any_field' ) ),
            array( 'id' => 'custom', 'name' => 'PHP Callback', 'fields' => array() ),
            array( 'id' => 'rest_api', 'name' => 'REST API externa', 'fields' => array() ),
        );

        return new WP_REST_Response( array( 'success' => true, 'sources' => $sources ), 200 );
    }

    /**
     * Crea conexión de datos
     */
    public function create_data_connection( $request ) {
        $source_type = sanitize_text_field( $request->get_param( 'source_type' ) );
        $config = $request->get_param( 'config' );

        $connection_id = 'conn_' . $source_type . '_' . time();
        $connection = array(
            'id'          => $connection_id,
            'source_type' => $source_type,
            'config'      => $config,
            'created_at'  => current_time( 'mysql' ),
        );

        $connections = get_option( 'flavor_vbp_data_connections', array() );
        $connections[ $connection_id ] = $connection;
        update_option( 'flavor_vbp_data_connections', $connections );

        return new WP_REST_Response( array( 'success' => true, 'connection' => $connection ), 201 );
    }

    /**
     * Vincula datos a bloque
     */
    public function bind_data_to_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $field = sanitize_text_field( $request->get_param( 'field' ) );
        $source = sanitize_text_field( $request->get_param( 'source' ) );
        $source_field = sanitize_text_field( $request->get_param( 'source_field' ) );
        $transform = $request->get_param( 'transform' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $binding = array(
            'field'        => $field,
            'source'       => $source,
            'source_field' => $source_field,
            'transform'    => $transform,
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $binding, $field ) {
            if ( ! isset( $el['data']['_bindings'] ) ) {
                $el['data']['_bindings'] = array();
            }
            $el['data']['_bindings'][ $field ] = $binding;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'binding' => $binding ), 200 );
    }

    /**
     * Obtiene bindings de bloque
     */
    public function get_block_data_bindings( $request ) {
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
            'success'  => true,
            'bindings' => $block['data']['_bindings'] ?? array(),
        ), 200 );
    }

    /**
     * Preview con datos dinámicos
     */
    public function preview_with_dynamic_data( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $data_context = $request->get_param( 'data_context' ) ?: array();

        $preview_url = add_query_arg( array(
            'vbp_preview'   => 1,
            'dynamic_data'  => 1,
            'context'       => base64_encode( wp_json_encode( $data_context ) ),
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array( 'success' => true, 'preview_url' => $preview_url ), 200 );
    }

    // =============================================
}
