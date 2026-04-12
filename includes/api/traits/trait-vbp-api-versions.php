<?php
/**
 * Trait para Sistema de Versiones VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Versions {


    /**
     * Lista versiones de página
     */
    /**
     * @deprecated Usar /claude/pages/{id}/snapshots
     */
    public function list_page_versions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        return new WP_REST_Response( array(
            'success'      => true,
            '_deprecated'  => true,
            '_use_instead' => '/claude/pages/{id}/snapshots',
            'versions'     => array_values( $versions ),
        ), 200 );
    }

    /**
     * @deprecated Usar /claude/pages/{id}/snapshots (POST)
     */
    public function create_page_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) ?: 'Versión ' . date( 'Y-m-d H:i' ) );
        $description = sanitize_text_field( $request->get_param( 'description' ) ?: '' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $version_id = time();
        $version = array(
            'id'          => $version_id,
            'name'        => $name,
            'description' => $description,
            'elements'    => $elements,
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
        );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();
        $versions[ $version_id ] = $version;

        if ( count( $versions ) > 20 ) {
            array_shift( $versions );
        }

        update_post_meta( $page_id, '_vbp_versions', $versions );

        return new WP_REST_Response( array(
            'success'      => true,
            '_deprecated'  => true,
            '_use_instead' => '/claude/pages/{id}/snapshots',
            'version'      => $version,
        ), 201 );
    }

    /**
     * Restaura snapshot de página
     * @deprecated Usar /claude/pages/{id}/snapshots/{id}/restore
     */
    public function restore_snapshot_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        if ( ! isset( $versions[ $version_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Versión no encontrada.' ), 404 );
        }

        $this->save_page_elements( $page_id, $versions[ $version_id ]['elements'] );

        return new WP_REST_Response( array( 'success' => true, 'restored_version' => $version_id ), 200 );
    }

    /**
     * Compara snapshots de versiones
     */
    public function compare_snapshot_versions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_a = (int) $request->get_param( 'version_a' );
        $version_b = (int) $request->get_param( 'version_b' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();

        if ( ! isset( $versions[ $version_a ] ) || ! isset( $versions[ $version_b ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Versiones no encontradas.' ), 404 );
        }

        $elements_a = $versions[ $version_a ]['elements'];
        $elements_b = $versions[ $version_b ]['elements'];

        $diff = array(
            'added'     => $this->count_elements( $elements_b ) - $this->count_elements( $elements_a ),
            'version_a' => array( 'id' => $version_a, 'name' => $versions[ $version_a ]['name'] ),
            'version_b' => array( 'id' => $version_b, 'name' => $versions[ $version_b ]['name'] ),
        );

        return new WP_REST_Response( array( 'success' => true, 'comparison' => $diff ), 200 );
    }

    /**
     * Elimina snapshot de versión
     */
    public function delete_snapshot_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $versions = get_post_meta( $page_id, '_vbp_versions', true ) ?: array();
        unset( $versions[ $version_id ] );
        update_post_meta( $page_id, '_vbp_versions', $versions );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Versión eliminada.' ), 200 );
    }

    // =============================================
}
