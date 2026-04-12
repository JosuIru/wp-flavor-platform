<?php
/**
 * Trait para Previsualizaciones Avanzadas VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_PreviewsAdvanced {

    /**
     * Obtiene preview con tema diferente
     */
    public function get_themed_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $theme = $request->get_param( 'theme' );
        $colors = $request->get_param( 'colors' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $theme_vars = array();
        switch ( $theme ) {
            case 'light':
                $theme_vars = array(
                    '--bg-color'   => '#ffffff',
                    '--text-color' => '#1a1a1a',
                    '--primary'    => '#2563eb',
                );
                break;
            case 'dark':
                $theme_vars = array(
                    '--bg-color'   => '#1a1a1a',
                    '--text-color' => '#ffffff',
                    '--primary'    => '#3b82f6',
                );
                break;
            case 'custom':
                if ( $colors ) {
                    $theme_vars = $colors;
                }
                break;
        }

        $preview_url = add_query_arg( array(
            'vbp_preview' => 1,
            'theme_vars'  => base64_encode( wp_json_encode( $theme_vars ) ),
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'theme'       => $theme,
            'theme_vars'  => $theme_vars,
        ), 200 );
    }

    /**
     * Crea preview compartible
     */
    public function create_shareable_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $expires_in = min( (int) $request->get_param( 'expires_in' ), 604800 );
        $password = $request->get_param( 'password' );
        $allow_comments = (bool) $request->get_param( 'allow_comments' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $share_token = wp_generate_password( 32, false );

        $share_data = array(
            'page_id'        => $page_id,
            'expires_at'     => time() + $expires_in,
            'password'       => $password ? wp_hash_password( $password ) : null,
            'allow_comments' => $allow_comments,
            'created_at'     => current_time( 'mysql' ),
            'views'          => 0,
        );

        set_transient( 'vbp_share_' . $share_token, $share_data, $expires_in );

        $share_url = add_query_arg( array(
            'vbp_share' => $share_token,
        ), home_url( '/vbp-preview/' ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'share_url'  => $share_url,
            'token'      => $share_token,
            'expires_at' => gmdate( 'c', $share_data['expires_at'] ),
        ), 201 );
    }

    /**
     * Genera código QR para preview
     */
    public function get_preview_qr_code( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $size = (int) $request->get_param( 'size' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $preview_url = add_query_arg( 'vbp_preview', '1', get_permalink( $page_id ) );

        $qr_url = 'https://chart.googleapis.com/chart?' . http_build_query( array(
            'cht'  => 'qr',
            'chs'  => "{$size}x{$size}",
            'chl'  => $preview_url,
            'choe' => 'UTF-8',
        ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'qr_url'      => $qr_url,
            'preview_url' => $preview_url,
            'size'        => $size,
        ), 200 );
    }

    /**
     * Obtiene preview de cambios pendientes
     */
    public function get_pending_changes_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $current_elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $draft_elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements_draft', true ), true ) ?: array();

        $has_changes = wp_json_encode( $current_elements ) !== wp_json_encode( $draft_elements );

        return new WP_REST_Response( array(
            'success'         => true,
            'has_changes'     => $has_changes,
            'current_count'   => count( $current_elements ),
            'draft_count'     => count( $draft_elements ),
            'preview_url'     => add_query_arg( 'vbp_draft', '1', get_permalink( $page_id ) ),
        ), 200 );
    }

    /**
     * Obtiene preview de múltiples páginas
     */
    public function get_multi_page_preview( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $thumbnail_size = $request->get_param( 'thumbnail_size' );

        $sizes = array(
            'small'  => array( 200, 150 ),
            'medium' => array( 400, 300 ),
            'large'  => array( 600, 450 ),
        );

        $size_dims = $sizes[ $thumbnail_size ] ?? $sizes['medium'];

        $previews = array();
        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( $post && $this->is_supported_post_type( $post->post_type ) ) {
                $previews[] = array(
                    'page_id'      => $page_id,
                    'title'        => $post->post_title,
                    'preview_url'  => get_permalink( $page_id ),
                    'thumbnail'    => get_the_post_thumbnail_url( $page_id, 'medium' ) ?: null,
                    'status'       => $post->post_status,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'previews' => $previews,
            'size'     => $size_dims,
        ), 200 );
    }

    /**
     * Obtiene preview interactivo
     */
    public function get_interactive_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_scripts = (bool) $request->get_param( 'include_scripts' );
        $sandbox = (bool) $request->get_param( 'sandbox' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $preview_url = add_query_arg( array(
            'vbp_interactive' => '1',
            'include_scripts' => $include_scripts ? '1' : '0',
        ), get_permalink( $page_id ) );

        $sandbox_attrs = $sandbox ? 'allow-scripts allow-same-origin' : '';

        return new WP_REST_Response( array(
            'success'          => true,
            'preview_url'      => $preview_url,
            'iframe_sandbox'   => $sandbox_attrs,
            'include_scripts'  => $include_scripts,
        ), 200 );
    }

    // =============================================
}
