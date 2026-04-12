<?php
/**
 * Trait para Colaboración Realtime VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_CollabRealtime {


    /**
     * Obtiene estado de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_editing_status( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        // Limpiar sesiones expiradas
        $now = time();
        $editing_sessions = array_filter( $editing_sessions, function( $session ) use ( $now ) {
            return ( $now - $session['last_activity'] ) < 300; // 5 minutos
        } );

        return new WP_REST_Response( array(
            'success'  => true,
            'editors'  => array_values( $editing_sessions ),
            'is_busy'  => count( $editing_sessions ) > 0,
        ), 200 );
    }

    /**
     * Inicia sesión de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function start_editing_session( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        $session_id = 'session_' . uniqid();
        $editing_sessions[ $session_id ] = array(
            'session_id'    => $session_id,
            'user_id'       => $user_id,
            'user_name'     => $user->display_name,
            'user_avatar'   => get_avatar_url( $user_id ),
            'started_at'    => time(),
            'last_activity' => time(),
        );

        set_transient( 'vbp_editing_sessions_' . $page_id, $editing_sessions, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'session_id' => $session_id,
            'message'    => 'Sesión de edición iniciada.',
        ), 200 );
    }

    /**
     * Finaliza sesión de edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function end_editing_session( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $editing_sessions = get_transient( 'vbp_editing_sessions_' . $page_id ) ?: array();

        // Eliminar sesiones del usuario actual
        $editing_sessions = array_filter( $editing_sessions, function( $session ) use ( $user_id ) {
            return $session['user_id'] !== $user_id;
        } );

        set_transient( 'vbp_editing_sessions_' . $page_id, $editing_sessions, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Sesión de edición finalizada.',
        ), 200 );
    }

    // =============================================
}
