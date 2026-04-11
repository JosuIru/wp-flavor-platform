<?php
/**
 * Trait para Import/Export Avanzado VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ImportExportAdvanced {


    /**
     * Exporta página a JSON
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_json( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_media = (bool) $request->get_param( 'include_media' );
        $include_styles = (bool) $request->get_param( 'include_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $styles = $include_styles ? ( json_decode( get_post_meta( $page_id, '_flavor_vbp_styles', true ), true ) ?: array() ) : array();

        $export_data = array(
            'version'      => '1.0',
            'exported_at'  => current_time( 'mysql' ),
            'page_info'    => array(
                'title'  => $post->post_title,
                'slug'   => $post->post_name,
                'status' => $post->post_status,
            ),
            'elements'     => $elements,
            'styles'       => $styles,
            'meta'         => array(
                'element_count' => count( $elements ),
            ),
        );

        if ( $include_media ) {
            $media_ids = $this->extract_media_ids_from_elements( $elements );
            $export_data['media'] = array_map( function( $media_id ) {
                $attachment = get_post( $media_id );
                return array(
                    'id'  => $media_id,
                    'url' => wp_get_attachment_url( $media_id ),
                    'alt' => get_post_meta( $media_id, '_wp_attachment_image_alt', true ),
                );
            }, $media_ids );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $export_data,
            'json'    => wp_json_encode( $export_data, JSON_PRETTY_PRINT ),
        ), 200 );
    }

    /**
     * Extrae IDs de media de elementos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function extract_media_ids_from_elements( $elements ) {
        $media_ids = array();
        foreach ( $elements as $element ) {
            if ( isset( $element['props']['image_id'] ) ) {
                $media_ids[] = (int) $element['props']['image_id'];
            }
            if ( isset( $element['props']['background_image_id'] ) ) {
                $media_ids[] = (int) $element['props']['background_image_id'];
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $media_ids = array_merge( $media_ids, $this->extract_media_ids_from_elements( $element['children'] ) );
            }
        }
        return array_unique( $media_ids );
    }

    /**
     * Importa página desde JSON
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function import_page_json( $request ) {
        $json_data = $request->get_param( 'json' );
        $create_new = (bool) $request->get_param( 'create_new' );
        $target_page_id = (int) $request->get_param( 'target_page_id' );

        $import_data = json_decode( $json_data, true );
        if ( ! $import_data || ! isset( $import_data['elements'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'JSON inválido o sin elementos.' ), 400 );
        }

        if ( $create_new || ! $target_page_id ) {
            $page_title = $import_data['page_info']['title'] ?? 'Página Importada';
            $page_slug = $import_data['page_info']['slug'] ?? sanitize_title( $page_title );

            $page_id = wp_insert_post( array(
                'post_type'   => 'flavor_landing',
                'post_title'  => $page_title,
                'post_name'   => $page_slug,
                'post_status' => 'draft',
            ) );

            if ( is_wp_error( $page_id ) ) {
                return new WP_REST_Response( array( 'success' => false, 'error' => $page_id->get_error_message() ), 500 );
            }
        } else {
            $page_id = $target_page_id;
            $post = get_post( $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                return new WP_REST_Response( array( 'success' => false, 'error' => 'Página destino no válida.' ), 404 );
            }
        }

        // Regenerar IDs de elementos
        $elements = $this->regenerate_element_ids( $import_data['elements'] );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        if ( ! empty( $import_data['styles'] ) ) {
            update_post_meta( $page_id, '_flavor_vbp_styles', wp_json_encode( $import_data['styles'] ) );
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'page_id'       => $page_id,
            'element_count' => count( $elements ),
            'message'       => 'Página importada correctamente.',
        ), 200 );
    }

    /**
     * Exporta múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_pages_bulk( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $format = $request->get_param( 'format' ) ?: 'json';

        $exported_pages = array();
        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( $post && $this->is_supported_post_type( $post->post_type ) ) {
                $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
                $styles = json_decode( get_post_meta( $page_id, '_flavor_vbp_styles', true ), true ) ?: array();

                $exported_pages[] = array(
                    'page_id'   => $page_id,
                    'title'     => $post->post_title,
                    'slug'      => $post->post_name,
                    'elements'  => $elements,
                    'styles'    => $styles,
                );
            }
        }

        $export_bundle = array(
            'version'     => '1.0',
            'exported_at' => current_time( 'mysql' ),
            'page_count'  => count( $exported_pages ),
            'pages'       => $exported_pages,
        );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $export_bundle,
            'json'    => wp_json_encode( $export_bundle, JSON_PRETTY_PRINT ),
        ), 200 );
    }

    /**
     * Clona página completa
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function clone_page_complete( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $new_title = $request->get_param( 'new_title' );
        $new_slug = $request->get_param( 'new_slug' );

        $original_post = get_post( $page_id );
        if ( ! $original_post || $original_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página original no encontrada.' ), 404 );
        }

        $clone_title = $new_title ?: $original_post->post_title . ' (Copia)';
        $clone_slug = $new_slug ?: $original_post->post_name . '-copia';

        $clone_id = wp_insert_post( array(
            'post_type'    => 'flavor_landing',
            'post_title'   => $clone_title,
            'post_name'    => $clone_slug,
            'post_status'  => 'draft',
            'post_content' => $original_post->post_content,
        ) );

        if ( is_wp_error( $clone_id ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $clone_id->get_error_message() ), 500 );
        }

        // Copiar meta de VBP con IDs regenerados
        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $elements = $this->regenerate_element_ids( $elements );
        update_post_meta( $clone_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );
        if ( $styles ) {
            update_post_meta( $clone_id, '_flavor_vbp_styles', $styles );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'clone_id' => $clone_id,
            'title'    => $clone_title,
            'slug'     => $clone_slug,
            'message'  => 'Página clonada correctamente.',
        ), 200 );
    }

    // =============================================
}
