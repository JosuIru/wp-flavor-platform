<?php
/**
 * Trait para snapshots VBP
 *
 * Este trait contiene métodos para gestión de snapshots manuales
 * de páginas VBP (versionado manual independiente de WP revisions).
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Snapshots
 *
 * Contiene métodos para:
 * - Listar snapshots (list_page_snapshots)
 * - Crear snapshots manuales (create_page_snapshot)
 * - Restaurar snapshots (restore_page_snapshot)
 * - Eliminar snapshots (delete_page_snapshot)
 */
trait VBP_API_Snapshots {

    /**
     * Lista snapshots de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_page_snapshots( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $snapshots = get_post_meta( $page_id, '_flavor_vbp_snapshots', true ) ?: array();

        // Añadir info adicional
        foreach ( $snapshots as &$snapshot ) {
            $author = get_user_by( 'id', $snapshot['created_by'] ?? 0 );
            $snapshot['author_name'] = $author ? $author->display_name : 'Desconocido';
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'page_id'   => $page_id,
            'total'     => count( $snapshots ),
            'snapshots' => array_values( $snapshots ),
        ), 200 );
    }

    /**
     * Crea snapshot manual
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_page_snapshot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $description = sanitize_textarea_field( $request->get_param( 'description' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        $snapshot_id = 'snap_' . bin2hex( random_bytes( 6 ) );

        $snapshots = get_post_meta( $page_id, '_flavor_vbp_snapshots', true ) ?: array();

        // Limitar a 20 snapshots por página
        if ( count( $snapshots ) >= 20 ) {
            array_shift( $snapshots );
        }

        $snapshots[ $snapshot_id ] = array(
            'id'          => $snapshot_id,
            'name'        => $name ?: 'Snapshot ' . date( 'Y-m-d H:i' ),
            'description' => $description,
            'elements'    => $elements,
            'styles'      => $styles,
            'title'       => $post->post_title,
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
            'block_count' => is_array( $elements ) ? $this->count_total_blocks( $elements ) : 0,
        );

        update_post_meta( $page_id, '_flavor_vbp_snapshots', $snapshots );

        return new WP_REST_Response( array(
            'success'     => true,
            'message'     => 'Snapshot creado correctamente.',
            'page_id'     => $page_id,
            'snapshot_id' => $snapshot_id,
            'name'        => $snapshots[ $snapshot_id ]['name'],
        ), 201 );
    }

    /**
     * Restaura un snapshot
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function restore_page_snapshot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $snapshot_id = $request->get_param( 'snapshot_id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $snapshots = get_post_meta( $page_id, '_flavor_vbp_snapshots', true ) ?: array();

        if ( ! isset( $snapshots[ $snapshot_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Snapshot no encontrado.',
            ), 404 );
        }

        $snapshot = $snapshots[ $snapshot_id ];

        // Crear snapshot de respaldo antes de restaurar
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $current_styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        $backup_id = 'backup_' . bin2hex( random_bytes( 4 ) );
        $snapshots[ $backup_id ] = array(
            'id'          => $backup_id,
            'name'        => 'Backup antes de restaurar ' . $snapshot['name'],
            'description' => 'Creado automáticamente',
            'elements'    => $current_elements,
            'styles'      => $current_styles,
            'title'       => $post->post_title,
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
            'is_backup'   => true,
        );

        update_post_meta( $page_id, '_flavor_vbp_snapshots', $snapshots );

        // Restaurar
        update_post_meta( $page_id, '_flavor_vbp_elements', $snapshot['elements'] );
        if ( ! empty( $snapshot['styles'] ) ) {
            update_post_meta( $page_id, '_flavor_vbp_styles', $snapshot['styles'] );
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'message'        => 'Snapshot restaurado correctamente.',
            'page_id'        => $page_id,
            'restored_from'  => $snapshot_id,
            'snapshot_name'  => $snapshot['name'],
            'backup_created' => $backup_id,
        ), 200 );
    }

    /**
     * Elimina un snapshot
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_page_snapshot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $snapshot_id = $request->get_param( 'snapshot_id' );

        $snapshots = get_post_meta( $page_id, '_flavor_vbp_snapshots', true ) ?: array();

        if ( ! isset( $snapshots[ $snapshot_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Snapshot no encontrado.',
            ), 404 );
        }

        $deleted_name = $snapshots[ $snapshot_id ]['name'];
        unset( $snapshots[ $snapshot_id ] );

        update_post_meta( $page_id, '_flavor_vbp_snapshots', $snapshots );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Snapshot '{$deleted_name}' eliminado.",
        ), 200 );
    }
}
