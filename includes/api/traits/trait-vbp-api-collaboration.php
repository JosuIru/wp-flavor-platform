<?php
/**
 * Trait para bloqueo y colaboración VBP
 *
 * Este trait contiene métodos para gestión de bloqueos de páginas
 * y colaboración entre usuarios editando VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Collaboration
 *
 * Contiene métodos para:
 * - Bloqueo de páginas (lock_page)
 * - Desbloqueo de páginas (unlock_page)
 * - Estado de bloqueo (get_lock_status)
 */
trait VBP_API_Collaboration {

    /**
     * Bloquea una página para edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function lock_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Verificar si ya está bloqueada
        $lock = get_post_meta( $page_id, '_flavor_vbp_lock', true );
        if ( $lock && $lock['user_id'] !== $user_id ) {
            // Verificar si el bloqueo ha expirado (15 minutos)
            $lock_time = strtotime( $lock['locked_at'] );
            if ( time() - $lock_time < 900 ) {
                $locked_by = get_user_by( 'id', $lock['user_id'] );
                return new WP_REST_Response( array(
                    'success'   => false,
                    'error'     => 'Página bloqueada por otro usuario.',
                    'locked_by' => array(
                        'id'   => $lock['user_id'],
                        'name' => $locked_by ? $locked_by->display_name : 'Desconocido',
                    ),
                    'locked_at' => $lock['locked_at'],
                    'expires_in'=> 900 - ( time() - $lock_time ),
                ), 423 );
            }
        }

        // Crear bloqueo
        $lock_data = array(
            'user_id'   => $user_id,
            'locked_at' => current_time( 'mysql' ),
        );

        update_post_meta( $page_id, '_flavor_vbp_lock', $lock_data );

        $current_user = wp_get_current_user();

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Página bloqueada para edición.',
            'page_id'    => $page_id,
            'locked_by'  => array(
                'id'   => $user_id,
                'name' => $current_user->display_name,
            ),
            'locked_at'  => $lock_data['locked_at'],
            'expires_in' => 900,
        ), 200 );
    }

    /**
     * Desbloquea una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function unlock_page( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $lock = get_post_meta( $page_id, '_flavor_vbp_lock', true );

        if ( ! $lock ) {
            return new WP_REST_Response( array(
                'success' => true,
                'message' => 'La página no estaba bloqueada.',
            ), 200 );
        }

        // Solo el usuario que bloqueó o admin puede desbloquear
        if ( $lock['user_id'] !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'No tienes permiso para desbloquear esta página.',
            ), 403 );
        }

        delete_post_meta( $page_id, '_flavor_vbp_lock' );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Página desbloqueada.',
            'page_id' => $page_id,
        ), 200 );
    }

    /**
     * Obtiene estado de bloqueo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_lock_status( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $lock = get_post_meta( $page_id, '_flavor_vbp_lock', true );

        if ( ! $lock ) {
            return new WP_REST_Response( array(
                'success'   => true,
                'is_locked' => false,
                'page_id'   => $page_id,
            ), 200 );
        }

        // Verificar expiración
        $lock_time = strtotime( $lock['locked_at'] );
        $expired = time() - $lock_time >= 900;

        if ( $expired ) {
            delete_post_meta( $page_id, '_flavor_vbp_lock' );
            return new WP_REST_Response( array(
                'success'   => true,
                'is_locked' => false,
                'page_id'   => $page_id,
                'message'   => 'Bloqueo expirado.',
            ), 200 );
        }

        $locked_by = get_user_by( 'id', $lock['user_id'] );

        return new WP_REST_Response( array(
            'success'     => true,
            'is_locked'   => true,
            'is_mine'     => $lock['user_id'] === $user_id,
            'page_id'     => $page_id,
            'locked_by'   => array(
                'id'   => $lock['user_id'],
                'name' => $locked_by ? $locked_by->display_name : 'Desconocido',
            ),
            'locked_at'   => $lock['locked_at'],
            'expires_in'  => 900 - ( time() - $lock_time ),
        ), 200 );
    }
}
