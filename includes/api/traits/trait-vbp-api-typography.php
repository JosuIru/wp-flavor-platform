<?php
/**
 * Trait para Tipografía Avanzada VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Typography {

    /**
     * Obtiene escalas tipográficas
     */
    public function get_typography_scales( $request ) {
        $scales = array(
            'minor_second' => array( 'name' => 'Minor Second', 'ratio' => 1.067, 'use_case' => 'Muy compacto' ),
            'major_second' => array( 'name' => 'Major Second', 'ratio' => 1.125, 'use_case' => 'Compacto' ),
            'minor_third' => array( 'name' => 'Minor Third', 'ratio' => 1.2, 'use_case' => 'Legible' ),
            'major_third' => array( 'name' => 'Major Third', 'ratio' => 1.25, 'use_case' => 'Equilibrado (recomendado)' ),
            'perfect_fourth' => array( 'name' => 'Perfect Fourth', 'ratio' => 1.333, 'use_case' => 'Clásico' ),
            'augmented_fourth' => array( 'name' => 'Augmented Fourth', 'ratio' => 1.414, 'use_case' => 'Dramático' ),
            'perfect_fifth' => array( 'name' => 'Perfect Fifth', 'ratio' => 1.5, 'use_case' => 'Impactante' ),
            'golden_ratio' => array( 'name' => 'Golden Ratio', 'ratio' => 1.618, 'use_case' => 'Artístico' ),
        );

        $font_stacks = array(
            'system' => array( 'name' => 'System UI', 'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif', 'variable' => false ),
            'inter' => array( 'name' => 'Inter', 'stack' => '"Inter", system-ui, sans-serif', 'variable' => true, 'weights' => '100..900' ),
            'roboto' => array( 'name' => 'Roboto', 'stack' => '"Roboto", sans-serif', 'variable' => false ),
            'poppins' => array( 'name' => 'Poppins', 'stack' => '"Poppins", sans-serif', 'variable' => false ),
            'playfair' => array( 'name' => 'Playfair Display', 'stack' => '"Playfair Display", serif', 'variable' => false ),
            'space_grotesk' => array( 'name' => 'Space Grotesk', 'stack' => '"Space Grotesk", sans-serif', 'variable' => true, 'weights' => '300..700' ),
        );

        return new WP_REST_Response( array( 'success' => true, 'scales' => $scales, 'font_stacks' => $font_stacks ), 200 );
    }

    /**
     * Configura tipografía global
     */
    public function set_typography_config( $request ) {
        $base_size = (int) $request->get_param( 'base_size' );
        $scale_ratio = (float) $request->get_param( 'scale_ratio' );
        $line_height = (float) $request->get_param( 'line_height' );
        $heading_line_height = (float) $request->get_param( 'heading_line_height' );
        $font_family_heading = sanitize_text_field( $request->get_param( 'font_family_heading' ) );
        $font_family_body = sanitize_text_field( $request->get_param( 'font_family_body' ) );
        $variable_fonts = (bool) $request->get_param( 'variable_fonts' );

        $typography_config = array(
            'base_size' => $base_size,
            'scale_ratio' => $scale_ratio,
            'line_height' => $line_height,
            'heading_line_height' => $heading_line_height,
            'font_family_heading' => $font_family_heading,
            'font_family_body' => $font_family_body,
            'variable_fonts' => $variable_fonts,
            'sizes' => array(
                'xs' => round( $base_size / pow( $scale_ratio, 2 ), 1 ) . 'px',
                'sm' => round( $base_size / $scale_ratio, 1 ) . 'px',
                'base' => $base_size . 'px',
                'lg' => round( $base_size * $scale_ratio, 1 ) . 'px',
                'xl' => round( $base_size * pow( $scale_ratio, 2 ), 1 ) . 'px',
                '2xl' => round( $base_size * pow( $scale_ratio, 3 ), 1 ) . 'px',
                '3xl' => round( $base_size * pow( $scale_ratio, 4 ), 1 ) . 'px',
                '4xl' => round( $base_size * pow( $scale_ratio, 5 ), 1 ) . 'px',
                '5xl' => round( $base_size * pow( $scale_ratio, 6 ), 1 ) . 'px',
            ),
        );

        update_option( 'flavor_vbp_typography_config', $typography_config );

        return new WP_REST_Response( array( 'success' => true, 'typography' => $typography_config ), 200 );
    }

    /**
     * Aplica texto con gradiente
     */
    public function apply_gradient_text( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $colors = $request->get_param( 'colors' );
        $angle = (int) $request->get_param( 'angle' );
        $animated = (bool) $request->get_param( 'animated' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $color_stops = implode( ', ', $colors );
        $gradient = "linear-gradient({$angle}deg, {$color_stops})";

        $text_styles = array(
            'background' => $gradient,
            'backgroundClip' => 'text',
            'webkitBackgroundClip' => 'text',
            'webkitTextFillColor' => 'transparent',
        );

        if ( $animated ) {
            $text_styles['backgroundSize'] = '200% 200%';
            $text_styles['animation'] = 'textGradient 3s ease infinite';
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $text_styles ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $text_styles );
            $el['data']['_gradient_text'] = true;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'gradient' => $gradient ), 200 );
    }

    /**
     * Aplica efectos de texto
     */
    public function apply_text_effects( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $effect = sanitize_text_field( $request->get_param( 'effect' ) );
        $params = $request->get_param( 'params' ) ?: array();

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $text_styles = $this->get_text_effect_styles( $effect, $params );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $text_styles, $effect ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $text_styles );
            $el['data']['_text_effect'] = $effect;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'effect' => $effect ), 200 );
    }

    private function get_text_effect_styles( $effect, $params ) {
        $color = $params['color'] ?? '#000';
        $distance = $params['distance'] ?? 2;

        switch ( $effect ) {
            case 'shadow':
                return array( 'textShadow' => "{$distance}px {$distance}px 4px rgba(0,0,0,0.3)" );
            case 'outline':
                return array( 'webkitTextStroke' => "1px {$color}", 'webkitTextFillColor' => 'transparent' );
            case 'emboss':
                return array( 'textShadow' => '-1px -1px 0 rgba(255,255,255,0.3), 1px 1px 0 rgba(0,0,0,0.2)' );
            case 'neon':
                return array( 'textShadow' => "0 0 5px {$color}, 0 0 10px {$color}, 0 0 20px {$color}" );
            case '3d':
                $shadows = array();
                for ( $i = 1; $i <= 5; $i++ ) {
                    $shadows[] = "{$i}px {$i}px 0 " . $this->adjust_color_brightness( $color, -( $i * 5 ) );
                }
                return array( 'textShadow' => implode( ', ', $shadows ) );
            default:
                return array();
        }
    }

    private function adjust_color_brightness( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, min( 255, $r + ( $r * $percent / 100 ) ) );
        $g = max( 0, min( 255, $g + ( $g * $percent / 100 ) ) );
        $b = max( 0, min( 255, $b + ( $b * $percent / 100 ) ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    // =============================================
}
