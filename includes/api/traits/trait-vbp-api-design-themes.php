<?php
/**
 * Trait para Diseño y Temas VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_DesignThemes {


    /**
     * Obtiene variables de diseño
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_design_variables( $request ) {
        $variables = get_option( 'flavor_vbp_design_variables', array(
            'colors' => array(
                'primary'    => '#3b82f6',
                'secondary'  => '#64748b',
                'accent'     => '#8b5cf6',
                'success'    => '#22c55e',
                'warning'    => '#f59e0b',
                'error'      => '#ef4444',
                'background' => '#ffffff',
                'text'       => '#1e293b',
            ),
            'spacing' => array(
                'xs' => '4px',
                'sm' => '8px',
                'md' => '16px',
                'lg' => '24px',
                'xl' => '32px',
            ),
            'typography' => array(
                'font_family' => 'Inter, sans-serif',
                'base_size'   => '16px',
                'scale_ratio' => 1.25,
            ),
            'borders' => array(
                'radius_sm' => '4px',
                'radius_md' => '8px',
                'radius_lg' => '12px',
            ),
        ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'variables' => $variables,
        ), 200 );
    }

    /**
     * Actualiza variables de diseño
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_design_variables( $request ) {
        $variables = $request->get_param( 'variables' );

        if ( ! is_array( $variables ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Variables inválidas.' ), 400 );
        }

        $current = get_option( 'flavor_vbp_design_variables', array() );
        $merged = array_replace_recursive( $current, $variables );

        update_option( 'flavor_vbp_design_variables', $merged );

        return new WP_REST_Response( array(
            'success'   => true,
            'variables' => $merged,
            'message'   => 'Variables actualizadas.',
        ), 200 );
    }

    /**
     * Obtiene paletas de colores
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_color_palettes( $request ) {
        $palettes = get_option( 'flavor_vbp_color_palettes', array(
            array(
                'id'     => 'default',
                'name'   => 'Default',
                'colors' => array( '#3b82f6', '#64748b', '#22c55e', '#f59e0b', '#ef4444' ),
            ),
            array(
                'id'     => 'nature',
                'name'   => 'Nature',
                'colors' => array( '#22c55e', '#16a34a', '#84cc16', '#a3e635', '#4ade80' ),
            ),
            array(
                'id'     => 'sunset',
                'name'   => 'Sunset',
                'colors' => array( '#f97316', '#fb923c', '#fbbf24', '#facc15', '#fcd34d' ),
            ),
            array(
                'id'     => 'ocean',
                'name'   => 'Ocean',
                'colors' => array( '#0ea5e9', '#06b6d4', '#14b8a6', '#22d3ee', '#67e8f9' ),
            ),
        ) );

        return new WP_REST_Response( array(
            'success'  => true,
            'palettes' => $palettes,
        ), 200 );
    }

    /**
     * Guarda paleta de colores
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_color_palette( $request ) {
        $palette_name = sanitize_text_field( $request->get_param( 'name' ) );
        $palette_colors = $request->get_param( 'colors' );

        if ( ! $palette_name || ! is_array( $palette_colors ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Datos de paleta inválidos.' ), 400 );
        }

        $palette_id = sanitize_title( $palette_name ) . '_' . uniqid();

        $palettes = get_option( 'flavor_vbp_color_palettes', array() );
        $palettes[] = array(
            'id'     => $palette_id,
            'name'   => $palette_name,
            'colors' => array_map( 'sanitize_hex_color', $palette_colors ),
        );

        update_option( 'flavor_vbp_color_palettes', $palettes );

        return new WP_REST_Response( array(
            'success'    => true,
            'palette_id' => $palette_id,
            'message'    => 'Paleta guardada.',
        ), 200 );
    }

    /**
     * Aplica paleta a página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_palette_to_page( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $palette_id = sanitize_text_field( $request->get_param( 'palette_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $palettes = get_option( 'flavor_vbp_color_palettes', array() );
        $selected_palette = null;

        foreach ( $palettes as $palette ) {
            if ( $palette['id'] === $palette_id ) {
                $selected_palette = $palette;
                break;
            }
        }

        if ( ! $selected_palette ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Paleta no encontrada.' ), 404 );
        }

        $styles = json_decode( get_post_meta( $page_id, '_flavor_vbp_styles', true ), true ) ?: array();
        $styles['applied_palette'] = $palette_id;
        $styles['palette_colors'] = $selected_palette['colors'];

        update_post_meta( $page_id, '_flavor_vbp_styles', wp_json_encode( $styles ) );

        return new WP_REST_Response( array(
            'success' => true,
            'palette' => $selected_palette,
            'message' => 'Paleta aplicada.',
        ), 200 );
    }

    /**
     * Obtiene configuración de tipografía
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_typography_settings( $request ) {
        $typography = get_option( 'flavor_vbp_typography', array(
            'fonts' => array(
                array( 'id' => 'inter', 'name' => 'Inter', 'family' => 'Inter, sans-serif' ),
                array( 'id' => 'roboto', 'name' => 'Roboto', 'family' => 'Roboto, sans-serif' ),
                array( 'id' => 'open-sans', 'name' => 'Open Sans', 'family' => '"Open Sans", sans-serif' ),
                array( 'id' => 'lato', 'name' => 'Lato', 'family' => 'Lato, sans-serif' ),
                array( 'id' => 'montserrat', 'name' => 'Montserrat', 'family' => 'Montserrat, sans-serif' ),
            ),
            'scales' => array(
                'minor-second'  => 1.067,
                'major-second'  => 1.125,
                'minor-third'   => 1.2,
                'major-third'   => 1.25,
                'perfect-fourth' => 1.333,
                'golden-ratio'  => 1.618,
            ),
            'defaults' => array(
                'heading_font'  => 'inter',
                'body_font'     => 'inter',
                'scale'         => 'major-third',
                'base_size'     => 16,
                'line_height'   => 1.5,
            ),
        ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'typography' => $typography,
        ), 200 );
    }

    /**
     * Actualiza tipografía
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_typography_settings( $request ) {
        $settings = $request->get_param( 'settings' );

        if ( ! is_array( $settings ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Configuración inválida.' ), 400 );
        }

        $current = get_option( 'flavor_vbp_typography', array() );
        $merged = array_replace_recursive( $current, $settings );

        update_option( 'flavor_vbp_typography', $merged );

        return new WP_REST_Response( array(
            'success'    => true,
            'typography' => $merged,
            'message'    => 'Tipografía actualizada.',
        ), 200 );
    }

    /**
     * Obtiene estilos globales de bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_global_block_styles( $request ) {
        $block_styles = get_option( 'flavor_vbp_global_block_styles', array() );

        return new WP_REST_Response( array(
            'success' => true,
            'styles'  => $block_styles,
        ), 200 );
    }

    /**
     * Guarda estilo global de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_global_block_style( $request ) {
        $block_type = sanitize_text_field( $request->get_param( 'block_type' ) );
        $style_name = sanitize_text_field( $request->get_param( 'style_name' ) );
        $style_definition = $request->get_param( 'styles' );

        if ( ! $block_type || ! $style_name || ! is_array( $style_definition ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Datos inválidos.' ), 400 );
        }

        $block_styles = get_option( 'flavor_vbp_global_block_styles', array() );

        if ( ! isset( $block_styles[ $block_type ] ) ) {
            $block_styles[ $block_type ] = array();
        }

        $style_id = sanitize_title( $style_name ) . '_' . uniqid();
        $block_styles[ $block_type ][ $style_id ] = array(
            'name'       => $style_name,
            'styles'     => $style_definition,
            'created_at' => current_time( 'mysql' ),
        );

        update_option( 'flavor_vbp_global_block_styles', $block_styles );

        return new WP_REST_Response( array(
            'success'  => true,
            'style_id' => $style_id,
            'message'  => 'Estilo guardado.',
        ), 200 );
    }

    // =============================================
}
