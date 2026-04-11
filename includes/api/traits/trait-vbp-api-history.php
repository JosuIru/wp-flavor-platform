<?php
/**
 * Trait para Historial y Versiones VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_History {


    /**
     * Obtiene historial completo de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_full_page_history( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $limit = (int) $request->get_param( 'limit' );
        $include_auto_saves = (bool) $request->get_param( 'include_auto_saves' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $revisions = wp_get_post_revisions( $page_id, array(
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $history = array();
        foreach ( $revisions as $revision ) {
            if ( ! $include_auto_saves && strpos( $revision->post_name, 'autosave' ) !== false ) {
                continue;
            }

            $history[] = array(
                'id'        => $revision->ID,
                'date'      => $revision->post_modified,
                'author'    => get_the_author_meta( 'display_name', $revision->post_author ),
                'is_auto'   => strpos( $revision->post_name, 'autosave' ) !== false,
            );
        }

        // Añadir checkpoints
        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();

        return new WP_REST_Response( array(
            'success'     => true,
            'history'     => $history,
            'checkpoints' => array_values( $checkpoints ),
            'total'       => count( $history ),
        ), 200 );
    }

    /**
     * Crea checkpoint de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_page_checkpoint( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $description = sanitize_text_field( $request->get_param( 'description' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        $checkpoint_id = 'checkpoint_' . uniqid();
        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();

        $checkpoints[ $checkpoint_id ] = array(
            'id'          => $checkpoint_id,
            'name'        => $name,
            'description' => $description,
            'elements'    => $elements,
            'styles'      => $styles,
            'created_at'  => current_time( 'mysql' ),
            'author'      => get_current_user_id(),
        );

        update_post_meta( $page_id, '_flavor_vbp_checkpoints', $checkpoints );

        return new WP_REST_Response( array(
            'success'       => true,
            '_deprecated'   => true,
            '_use_instead'  => '/claude/pages/{id}/snapshots',
            'message'       => 'Checkpoint creado. Nota: Este endpoint está deprecated, usar /snapshots.',
            'checkpoint_id' => $checkpoint_id,
        ), 201 );
    }

    /**
     * Lista checkpoints de página
     * @deprecated Usar /claude/pages/{id}/snapshots
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_page_checkpoints( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();

        $list = array();
        foreach ( $checkpoints as $checkpoint ) {
            $user = get_userdata( $checkpoint['author'] ?? 0 );
            $list[] = array(
                'id'          => $checkpoint['id'],
                'name'        => $checkpoint['name'],
                'description' => $checkpoint['description'] ?? '',
                'created_at'  => $checkpoint['created_at'],
                'author'      => $user ? $user->display_name : 'Desconocido',
            );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            '_deprecated'  => true,
            '_use_instead' => '/claude/pages/{id}/snapshots',
            'checkpoints'  => $list,
            'count'        => count( $list ),
        ), 200 );
    }

    /**
     * Restaura checkpoint de página
     * @deprecated Usar /claude/pages/{id}/snapshots/{id}/restore
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function restore_page_checkpoint( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $checkpoint_id = sanitize_text_field( $request->get_param( 'checkpoint_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();

        if ( ! isset( $checkpoints[ $checkpoint_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Checkpoint no encontrado.' ), 404 );
        }

        $checkpoint = $checkpoints[ $checkpoint_id ];

        // Crear backup del estado actual antes de restaurar
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $backup_id = 'backup_pre_restore_' . uniqid();
        $checkpoints[ $backup_id ] = array(
            'id'          => $backup_id,
            'name'        => 'Backup antes de restaurar: ' . $checkpoint['name'],
            'elements'    => $current_elements,
            'created_at'  => current_time( 'mysql' ),
            'author'      => get_current_user_id(),
        );
        update_post_meta( $page_id, '_flavor_vbp_checkpoints', $checkpoints );

        // Restaurar
        update_post_meta( $page_id, '_flavor_vbp_elements', $checkpoint['elements'] );
        if ( ! empty( $checkpoint['styles'] ) ) {
            update_post_meta( $page_id, '_flavor_vbp_styles', $checkpoint['styles'] );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Checkpoint restaurado.',
            'backup_id' => $backup_id,
        ), 200 );
    }

    /**
     * Elimina checkpoint de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_page_checkpoint( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $checkpoint_id = sanitize_text_field( $request->get_param( 'checkpoint_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();

        if ( ! isset( $checkpoints[ $checkpoint_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Checkpoint no encontrado.' ), 404 );
        }

        unset( $checkpoints[ $checkpoint_id ] );
        update_post_meta( $page_id, '_flavor_vbp_checkpoints', $checkpoints );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Checkpoint eliminado.',
        ), 200 );
    }

    /**
     * Obtiene diff entre versiones
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_version_diff( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $from_version = sanitize_text_field( $request->get_param( 'from' ) );
        $to_version = sanitize_text_field( $request->get_param( 'to' ) );
        $format = $request->get_param( 'format' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Obtener elementos de cada versión
        $from_elements = $this->get_version_elements( $page_id, $from_version );
        $to_elements = $this->get_version_elements( $page_id, $to_version );

        $diff = array(
            'added'    => array(),
            'removed'  => array(),
            'modified' => array(),
        );

        $from_ids = array_column( $from_elements, 'id' );
        $to_ids = array_column( $to_elements, 'id' );

        $diff['added'] = array_values( array_diff( $to_ids, $from_ids ) );
        $diff['removed'] = array_values( array_diff( $from_ids, $to_ids ) );

        // Detectar modificados
        foreach ( $to_elements as $to_el ) {
            $to_id = $to_el['id'] ?? '';
            if ( in_array( $to_id, $from_ids, true ) ) {
                $from_el = $from_elements[ array_search( $to_id, $from_ids ) ];
                if ( wp_json_encode( $to_el ) !== wp_json_encode( $from_el ) ) {
                    $diff['modified'][] = $to_id;
                }
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'from'    => $from_version,
            'to'      => $to_version,
            'diff'    => $diff,
            'summary' => array(
                'added'    => count( $diff['added'] ),
                'removed'  => count( $diff['removed'] ),
                'modified' => count( $diff['modified'] ),
            ),
        ), 200 );
    }

    /**
     * Obtiene elementos de una versión
     *
     * @param int    $page_id ID de página.
     * @param string $version Versión.
     * @return array
     */
    private function get_version_elements( $page_id, $version ) {
        if ( $version === 'current' ) {
            return json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        }

        // Buscar en checkpoints
        $checkpoints = get_post_meta( $page_id, '_flavor_vbp_checkpoints', true ) ?: array();
        if ( isset( $checkpoints[ $version ] ) ) {
            return json_decode( $checkpoints[ $version ]['elements'], true ) ?: array();
        }

        // Buscar en revisiones
        $revision = get_post( (int) $version );
        if ( $revision && $revision->post_parent === $page_id ) {
            return json_decode( get_post_meta( $revision->ID, '_flavor_vbp_elements', true ), true ) ?: array();
        }

        return array();
    }

    // =============================================
}
