<?php
/**
 * Trait para operaciones en lote VBP
 *
 * Este trait contiene métodos para operaciones masivas sobre
 * múltiples páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_BulkOperations
 *
 * Contiene métodos para:
 * - Publicación masiva (bulk_publish_pages)
 * - Eliminación masiva (bulk_delete_pages)
 * - Duplicación masiva (bulk_duplicate_pages)
 * - Etiquetado masivo (bulk_set_tags)
 */
trait VBP_API_BulkOperations {

    /**
     * Publica múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function bulk_publish_pages( $request ) {
        $page_ids = $request->get_param( 'page_ids' );

        $results = array(
            'success' => array(),
            'failed'  => array(),
        );

        foreach ( $page_ids as $page_id ) {
            $post = get_post( $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                $results['failed'][] = array(
                    'id'    => $page_id,
                    'error' => 'Página no encontrada.',
                );
                continue;
            }

            $updated = wp_update_post( array(
                'ID'          => $page_id,
                'post_status' => 'publish',
            ) );

            if ( is_wp_error( $updated ) ) {
                $results['failed'][] = array(
                    'id'    => $page_id,
                    'error' => $updated->get_error_message(),
                );
            } else {
                $results['success'][] = $page_id;
            }
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'published' => count( $results['success'] ),
            'failed'    => count( $results['failed'] ),
            'results'   => $results,
        ), 200 );
    }

    /**
     * Elimina múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function bulk_delete_pages( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $force = (bool) $request->get_param( 'force' );

        $results = array(
            'success' => array(),
            'failed'  => array(),
        );

        foreach ( $page_ids as $page_id ) {
            $post = get_post( $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                $results['failed'][] = array(
                    'id'    => $page_id,
                    'error' => 'Página no encontrada.',
                );
                continue;
            }

            $deleted = wp_delete_post( $page_id, $force );

            if ( ! $deleted ) {
                $results['failed'][] = array(
                    'id'    => $page_id,
                    'error' => 'Error al eliminar.',
                );
            } else {
                $results['success'][] = $page_id;
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'deleted' => count( $results['success'] ),
            'failed'  => count( $results['failed'] ),
            'force'   => $force,
            'results' => $results,
        ), 200 );
    }

    /**
     * Duplica múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function bulk_duplicate_pages( $request ) {
        $page_ids = $request->get_param( 'page_ids' );

        $results = array(
            'success' => array(),
            'failed'  => array(),
        );

        foreach ( $page_ids as $page_id ) {
            // Crear request para duplicar
            $dup_request = new WP_REST_Request( 'POST' );
            $dup_request->set_param( 'id', $page_id );
            $dup_request->set_param( 'status', 'draft' );

            $response = $this->duplicate_page( $dup_request );
            $data = $response->get_data();

            if ( ! empty( $data['success'] ) ) {
                $results['success'][] = array(
                    'original_id' => $page_id,
                    'new_id'      => $data['id'],
                );
            } else {
                $results['failed'][] = array(
                    'id'    => $page_id,
                    'error' => $data['error'] ?? 'Error desconocido.',
                );
            }
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'duplicated' => count( $results['success'] ),
            'failed'     => count( $results['failed'] ),
            'results'    => $results,
        ), 200 );
    }

    /**
     * Asigna etiquetas a múltiples páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function bulk_set_tags( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $tags = $request->get_param( 'tags' );
        $mode = $request->get_param( 'mode' );

        $clean_tags = array_map( 'sanitize_text_field', $tags );
        $clean_tags = array_filter( $clean_tags );

        $results = array(
            'success' => array(),
            'failed'  => array(),
        );

        foreach ( $page_ids as $page_id ) {
            $post = get_post( $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                $results['failed'][] = $page_id;
                continue;
            }

            $current_tags_json = get_post_meta( $page_id, '_flavor_vbp_tags', true );
            $current_tags = $current_tags_json ? json_decode( $current_tags_json, true ) : array();

            switch ( $mode ) {
                case 'replace':
                    $new_tags = $clean_tags;
                    break;
                case 'remove':
                    $new_tags = array_diff( $current_tags, $clean_tags );
                    break;
                case 'add':
                default:
                    $new_tags = array_unique( array_merge( $current_tags, $clean_tags ) );
                    break;
            }

            update_post_meta( $page_id, '_flavor_vbp_tags', wp_json_encode( array_values( $new_tags ) ) );
            $results['success'][] = $page_id;
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'mode'     => $mode,
            'tags'     => $clean_tags,
            'updated'  => count( $results['success'] ),
            'failed'   => count( $results['failed'] ),
            'results'  => $results,
        ), 200 );
    }
}
