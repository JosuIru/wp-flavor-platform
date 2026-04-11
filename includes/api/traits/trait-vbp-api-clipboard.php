<?php
/**
 * Trait para Portapapeles VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Clipboard {


    /**
     * Copia bloques al portapapeles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function copy_to_clipboard( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $block_ids = $request->get_param( 'block_ids' );
        $include_styles = (bool) $request->get_param( 'include_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $copied_blocks = array();
        foreach ( $elements as $element ) {
            if ( in_array( $element['id'] ?? '', $block_ids, true ) ) {
                $block = $element;
                if ( ! $include_styles ) {
                    unset( $block['styles'] );
                }
                $copied_blocks[] = $block;
            }
        }

        $user_id = get_current_user_id();
        $clipboard_key = 'vbp_clipboard_' . $user_id;

        set_transient( $clipboard_key, array(
            'blocks'      => $copied_blocks,
            'source_page' => $page_id,
            'copied_at'   => current_time( 'mysql' ),
        ), HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => count( $copied_blocks ) . ' bloques copiados.',
            'count'   => count( $copied_blocks ),
        ), 200 );
    }

    /**
     * Pega desde portapapeles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function paste_from_clipboard( $request ) {
        $target_page_id = (int) $request->get_param( 'target_page_id' );
        $position = (int) $request->get_param( 'position' );
        $paste_mode = $request->get_param( 'paste_mode' );

        $post = get_post( $target_page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página destino no encontrada.' ), 404 );
        }

        $user_id = get_current_user_id();
        $clipboard = get_transient( 'vbp_clipboard_' . $user_id );

        if ( ! $clipboard || empty( $clipboard['blocks'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Portapapeles vacío.' ), 400 );
        }

        $elements = json_decode( get_post_meta( $target_page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $pasted_blocks = array();
        foreach ( $clipboard['blocks'] as $block ) {
            $new_block = $block;
            $new_block['id'] = 'block_' . uniqid();
            $pasted_blocks[] = $new_block;
        }

        if ( $position < 0 || $position >= count( $elements ) ) {
            $elements = array_merge( $elements, $pasted_blocks );
        } else {
            array_splice( $elements, $position, 0, $pasted_blocks );
        }

        update_post_meta( $target_page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => count( $pasted_blocks ) . ' bloques pegados.',
            'block_ids' => array_column( $pasted_blocks, 'id' ),
        ), 200 );
    }

    /**
     * Obtiene contenido del portapapeles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_clipboard_contents( $request ) {
        $user_id = get_current_user_id();
        $clipboard = get_transient( 'vbp_clipboard_' . $user_id );

        if ( ! $clipboard ) {
            return new WP_REST_Response( array(
                'success' => true,
                'empty'   => true,
                'blocks'  => array(),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'empty'       => false,
            'blocks'      => $clipboard['blocks'],
            'count'       => count( $clipboard['blocks'] ),
            'source_page' => $clipboard['source_page'],
            'copied_at'   => $clipboard['copied_at'],
        ), 200 );
    }

    /**
     * Limpia portapapeles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function clear_clipboard( $request ) {
        $user_id = get_current_user_id();
        delete_transient( 'vbp_clipboard_' . $user_id );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Portapapeles limpiado.',
        ), 200 );
    }

    // =============================================
}
