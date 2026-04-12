<?php
/**
 * Trait para Preferencias del Editor VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_EditorPrefs {


    /**
     * Obtiene preferencias del editor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_editor_preferences( $request ) {
        $user_id = get_current_user_id();

        $preferences = get_user_meta( $user_id, 'vbp_editor_preferences', true ) ?: array(
            'theme'         => 'light',
            'grid_visible'  => true,
            'grid_size'     => 8,
            'rulers_visible' => true,
            'auto_save'     => true,
            'auto_save_interval' => 60,
            'sidebar_position' => 'right',
            'panel_sizes'   => array(
                'elements' => 300,
                'properties' => 350,
            ),
            'zoom_level'    => 100,
            'snap_to_grid'  => true,
        );

        return new WP_REST_Response( array(
            'success'     => true,
            'preferences' => $preferences,
        ), 200 );
    }

    /**
     * Guarda preferencias del editor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_editor_preferences( $request ) {
        $user_id = get_current_user_id();
        $preferences = $request->get_param( 'preferences' );

        if ( ! is_array( $preferences ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preferencias inválidas.' ), 400 );
        }

        $current = get_user_meta( $user_id, 'vbp_editor_preferences', true ) ?: array();
        $merged = array_merge( $current, $preferences );

        update_user_meta( $user_id, 'vbp_editor_preferences', $merged );

        return new WP_REST_Response( array(
            'success'     => true,
            'preferences' => $merged,
            'message'     => 'Preferencias guardadas.',
        ), 200 );
    }

    // =============================================
}
