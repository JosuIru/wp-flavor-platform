<?php
/**
 * Trait para Plantillas de Sección VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_SectionTemplates {


    /**
     * Lista plantillas de sección
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_section_templates( $request ) {
        $category = $request->get_param( 'category' );

        $templates = get_option( 'flavor_vbp_section_templates', array() );

        if ( $category ) {
            $templates = array_filter( $templates, fn( $t ) => ( $t['category'] ?? '' ) === $category );
        }

        $list = array();
        foreach ( $templates as $id => $template ) {
            $list[] = array(
                'id'        => $id,
                'name'      => $template['name'],
                'category'  => $template['category'] ?? 'custom',
                'thumbnail' => $template['thumbnail'] ?? null,
                'blocks'    => count( $template['blocks'] ?? array() ),
            );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'templates' => $list,
        ), 200 );
    }

    /**
     * Guarda sección como plantilla
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_section_template( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $blocks = $request->get_param( 'blocks' );
        $thumbnail = esc_url_raw( $request->get_param( 'thumbnail' ) );

        $templates = get_option( 'flavor_vbp_section_templates', array() );

        $template_id = 'section_' . sanitize_title( $name ) . '_' . uniqid();
        $templates[ $template_id ] = array(
            'name'       => $name,
            'category'   => $category,
            'blocks'     => $blocks,
            'thumbnail'  => $thumbnail,
            'created_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
        );

        update_option( 'flavor_vbp_section_templates', $templates );

        return new WP_REST_Response( array(
            'success'     => true,
            'message'     => 'Plantilla guardada.',
            'template_id' => $template_id,
        ), 201 );
    }

    /**
     * Obtiene plantilla de sección
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_section_template( $request ) {
        $template_id = sanitize_text_field( $request->get_param( 'template_id' ) );

        $templates = get_option( 'flavor_vbp_section_templates', array() );

        if ( ! isset( $templates[ $template_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Plantilla no encontrada.',
            ), 404 );
        }

        $template = $templates[ $template_id ];
        $template['id'] = $template_id;

        return new WP_REST_Response( array(
            'success'  => true,
            'template' => $template,
        ), 200 );
    }

    /**
     * Elimina plantilla de sección
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_section_template( $request ) {
        $template_id = sanitize_text_field( $request->get_param( 'template_id' ) );

        $templates = get_option( 'flavor_vbp_section_templates', array() );

        if ( ! isset( $templates[ $template_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Plantilla no encontrada.',
            ), 404 );
        }

        unset( $templates[ $template_id ] );
        update_option( 'flavor_vbp_section_templates', $templates );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Plantilla eliminada.',
        ), 200 );
    }

    /**
     * Inserta plantilla en página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function insert_section_template( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $template_id = sanitize_text_field( $request->get_param( 'template_id' ) );
        $position = (int) $request->get_param( 'position' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $templates = get_option( 'flavor_vbp_section_templates', array() );

        if ( ! isset( $templates[ $template_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Plantilla no encontrada.',
            ), 404 );
        }

        $template_blocks = $templates[ $template_id ]['blocks'];

        // Clonar bloques con nuevos IDs
        $new_blocks = array_map( array( $this, 'deep_clone_block' ), $template_blocks );

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        // Insertar en posición
        if ( $position < 0 || $position >= count( $elements ) ) {
            $elements = array_merge( $elements, $new_blocks );
        } else {
            array_splice( $elements, $position, 0, $new_blocks );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Plantilla insertada.',
            'blocks'  => count( $new_blocks ),
        ), 200 );
    }

    // =============================================
}
