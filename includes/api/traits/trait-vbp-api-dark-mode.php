<?php
/**
 * Trait para Dark Mode y Temas VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_DarkMode {

    /**
     * Configura dark mode
     */
    public function configure_dark_mode( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $auto_detect = (bool) $request->get_param( 'auto_detect' );
        $toggle_visible = (bool) $request->get_param( 'toggle_visible' );
        $colors = $request->get_param( 'colors' ) ?: array();

        $dark_mode_config = array(
            'enabled' => $enabled,
            'auto_detect' => $auto_detect,
            'toggle_visible' => $toggle_visible,
            'colors' => array_merge( array(
                'background' => '#1a1a1a',
                'surface' => '#2d2d2d',
                'text' => '#ffffff',
                'text_secondary' => '#b0b0b0',
                'primary' => '#60a5fa',
                'border' => '#404040',
            ), $colors ),
        );

        update_post_meta( $page_id, '_vbp_dark_mode', $dark_mode_config );

        return new WP_REST_Response( array( 'success' => true, 'dark_mode' => $dark_mode_config ), 200 );
    }

    /**
     * Obtiene config de dark mode
     */
    public function get_dark_mode_config( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $config = get_post_meta( $page_id, '_vbp_dark_mode', true ) ?: array( 'enabled' => false );
        return new WP_REST_Response( array( 'success' => true, 'dark_mode' => $config ), 200 );
    }

    // =============================================
}
