<?php
/**
 * Trait para Colaboración Avanzada VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_CollabAdvanced {


    /**
     * Obtiene usuarios activos en página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_presence( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $presence_key = 'vbp_presence_' . $page_id;
        $presence = get_transient( $presence_key );
        $presence = $presence ? $presence : array();

        // Limpiar usuarios inactivos (más de 30 segundos)
        $now = time();
        $active_users = array();
        foreach ( $presence as $user_id => $data ) {
            if ( $now - $data['timestamp'] < 30 ) {
                $user = get_userdata( $user_id );
                $active_users[] = array(
                    'user_id'         => $user_id,
                    'name'            => $user ? $user->display_name : 'Usuario',
                    'avatar'          => get_avatar_url( $user_id, array( 'size' => 32 ) ),
                    'cursor_position' => $data['cursor_position'] ?? null,
                    'selected_block'  => $data['selected_block'] ?? null,
                    'last_active'     => $data['timestamp'],
                );
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'users'   => $active_users,
            'total'   => count( $active_users ),
        ), 200 );
    }

    /**
     * Actualiza presencia en página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_page_presence( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $cursor_position = $request->get_param( 'cursor_position' );
        $selected_block = $request->get_param( 'selected_block' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Usuario no autenticado.',
            ), 401 );
        }

        $presence_key = 'vbp_presence_' . $page_id;
        $presence = get_transient( $presence_key );
        $presence = $presence ? $presence : array();

        $presence[ $user_id ] = array(
            'cursor_position' => $cursor_position,
            'selected_block'  => $selected_block,
            'timestamp'       => time(),
        );

        set_transient( $presence_key, $presence, 60 );

        return new WP_REST_Response( array(
            'success' => true,
        ), 200 );
    }

    /**
     * Envía notificación a colaboradores
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function notify_collaborators( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $message = sanitize_text_field( $request->get_param( 'message' ) );
        $type = $request->get_param( 'type' );

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Guardar notificación
        $notifications_key = 'vbp_notifications_' . $page_id;
        $notifications = get_transient( $notifications_key );
        $notifications = $notifications ? $notifications : array();

        $notification = array(
            'id'        => 'notif_' . uniqid(),
            'message'   => $message,
            'type'      => $type,
            'from_user' => $user ? $user->display_name : 'Sistema',
            'timestamp' => current_time( 'mysql' ),
        );

        array_unshift( $notifications, $notification );
        $notifications = array_slice( $notifications, 0, 50 );

        set_transient( $notifications_key, $notifications, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => 'Notificación enviada.',
            'notification' => $notification,
        ), 200 );
    }

    // =============================================
}
