<?php
/**
 * Trait para Media VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Media {


    /**
     * Obtiene media de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_media( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $media_ids = $this->extract_media_ids_from_elements( $elements );

        $media_items = array();
        foreach ( $media_ids as $media_id ) {
            $attachment = get_post( $media_id );
            if ( $attachment ) {
                $media_items[] = array(
                    'id'        => $media_id,
                    'url'       => wp_get_attachment_url( $media_id ),
                    'thumbnail' => wp_get_attachment_image_url( $media_id, 'thumbnail' ),
                    'alt'       => get_post_meta( $media_id, '_wp_attachment_image_alt', true ),
                    'title'     => $attachment->post_title,
                    'mime_type' => $attachment->post_mime_type,
                    'filesize'  => filesize( get_attached_file( $media_id ) ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'media'   => $media_items,
            'count'   => count( $media_items ),
        ), 200 );
    }

    /**
     * Reemplaza media en página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function replace_page_media( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $old_media_id = (int) $request->get_param( 'old_media_id' );
        $new_media_id = (int) $request->get_param( 'new_media_id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $new_media = get_post( $new_media_id );
        if ( ! $new_media || $new_media->post_type !== 'attachment' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Nueva imagen no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $replacements_count = 0;

        $elements = $this->replace_media_in_elements( $elements, $old_media_id, $new_media_id, $replacements_count );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'      => true,
            'replacements' => $replacements_count,
            'new_url'      => wp_get_attachment_url( $new_media_id ),
            'message'      => $replacements_count . ' imagen(es) reemplazada(s).',
        ), 200 );
    }

    /**
     * Reemplaza media en elementos recursivamente
     *
     * @param array $elements Elementos.
     * @param int   $old_id ID de media antiguo.
     * @param int   $new_id ID de media nuevo.
     * @param int   $count Contador de reemplazos.
     * @return array
     */
    private function replace_media_in_elements( $elements, $old_id, $new_id, &$count ) {
        foreach ( $elements as &$element ) {
            if ( isset( $element['props']['image_id'] ) && (int) $element['props']['image_id'] === $old_id ) {
                $element['props']['image_id'] = $new_id;
                $element['props']['image_url'] = wp_get_attachment_url( $new_id );
                $count++;
            }
            if ( isset( $element['props']['background_image_id'] ) && (int) $element['props']['background_image_id'] === $old_id ) {
                $element['props']['background_image_id'] = $new_id;
                $element['props']['background_image_url'] = wp_get_attachment_url( $new_id );
                $count++;
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->replace_media_in_elements( $element['children'], $old_id, $new_id, $count );
            }
        }
        return $elements;
    }

    /**
     * Sube media
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function upload_media( $request ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No se proporcionó archivo.' ), 400 );
        }

        $uploaded_files = array();
        $file = $files['file'];

        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );

        if ( isset( $upload['error'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $upload['error'] ), 400 );
        }

        $attachment_data = array(
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name( pathinfo( $upload['file'], PATHINFO_FILENAME ) ),
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment_data, $upload['file'] );
        $attachment_meta = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        wp_update_attachment_metadata( $attachment_id, $attachment_meta );

        return new WP_REST_Response( array(
            'success' => true,
            'media'   => array(
                'id'        => $attachment_id,
                'url'       => $upload['url'],
                'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
            ),
        ), 200 );
    }

    /**
     * Busca imágenes de stock
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_stock_images( $request ) {
        $query = sanitize_text_field( $request->get_param( 'query' ) );
        $page = (int) $request->get_param( 'page' ) ?: 1;
        $per_page = (int) $request->get_param( 'per_page' ) ?: 20;

        // Integración con Unsplash (ejemplo)
        $unsplash_key = get_option( 'flavor_unsplash_api_key', '' );

        if ( empty( $unsplash_key ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'API de Unsplash no configurada.',
            ), 400 );
        }

        $api_url = add_query_arg( array(
            'query'    => $query,
            'page'     => $page,
            'per_page' => $per_page,
        ), 'https://api.unsplash.com/search/photos' );

        $response = wp_remote_get( $api_url, array(
            'headers' => array(
                'Authorization' => 'Client-ID ' . $unsplash_key,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $response->get_error_message() ), 500 );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $images = array_map( function( $img ) {
            return array(
                'id'          => $img['id'],
                'url'         => $img['urls']['regular'],
                'thumbnail'   => $img['urls']['thumb'],
                'download'    => $img['links']['download'],
                'author'      => $img['user']['name'],
                'description' => $img['description'] ?? $img['alt_description'],
            );
        }, $body['results'] ?? array() );

        return new WP_REST_Response( array(
            'success' => true,
            'images'  => $images,
            'total'   => $body['total'] ?? 0,
            'pages'   => $body['total_pages'] ?? 1,
        ), 200 );
    }

    /**
     * Genera placeholder de imagen
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_placeholder_image( $request ) {
        $width = (int) $request->get_param( 'width' ) ?: 800;
        $height = (int) $request->get_param( 'height' ) ?: 600;
        $color = sanitize_hex_color( $request->get_param( 'color' ) ) ?: '#cccccc';
        $text = sanitize_text_field( $request->get_param( 'text' ) ) ?: "{$width}x{$height}";

        // Generar URL de placeholder
        $placeholder_url = sprintf(
            'https://via.placeholder.com/%dx%d/%s/666?text=%s',
            $width,
            $height,
            ltrim( $color, '#' ),
            urlencode( $text )
        );

        return new WP_REST_Response( array(
            'success' => true,
            'url'     => $placeholder_url,
            'width'   => $width,
            'height'  => $height,
        ), 200 );
    }

    // =============================================
}
