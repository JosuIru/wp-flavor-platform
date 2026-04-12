<?php
/**
 * Trait para Efectos CSS Modernos VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_CSSEffects {

    /**
     * Obtiene efectos CSS modernos disponibles
     */
    public function get_modern_css_effects( $request ) {
        $effects = array(
            'glassmorphism' => array( 'name' => 'Glassmorphism', 'description' => 'Efecto de vidrio esmerilado', 'browser_support' => '95%' ),
            'neumorphism' => array( 'name' => 'Neumorphism', 'description' => 'Efecto de relieve suave', 'browser_support' => '99%' ),
            'gradient_mesh' => array( 'name' => 'Gradient Mesh', 'description' => 'Gradientes complejos', 'browser_support' => '98%' ),
            'blend_modes' => array( 'name' => 'Blend Modes', 'description' => 'Modos de mezcla', 'browser_support' => '97%' ),
            'clip_path' => array( 'name' => 'Clip Path', 'description' => 'Recortes de forma', 'browser_support' => '96%' ),
            'filters' => array( 'name' => 'CSS Filters', 'description' => 'Filtros CSS', 'browser_support' => '98%' ),
        );
        return new WP_REST_Response( array( 'success' => true, 'effects' => $effects ), 200 );
    }

    /**
     * Aplica glassmorphism a bloque
     */
    public function apply_glassmorphism( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $blur = (int) $request->get_param( 'blur' );
        $opacity = (float) $request->get_param( 'opacity' );
        $saturation = (float) $request->get_param( 'saturation' );
        $border_opacity = (float) $request->get_param( 'border_opacity' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $glassmorphism_styles = array(
            'background' => "rgba(255, 255, 255, {$opacity})",
            'backdropFilter' => "blur({$blur}px) saturate({$saturation})",
            'webkitBackdropFilter' => "blur({$blur}px) saturate({$saturation})",
            'border' => "1px solid rgba(255, 255, 255, {$border_opacity})",
            'borderRadius' => '16px',
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $glassmorphism_styles ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $glassmorphism_styles );
            $el['data']['_effect'] = 'glassmorphism';
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'effect' => 'glassmorphism', 'styles' => $glassmorphism_styles ), 200 );
    }

    /**
     * Aplica neumorphism a bloque
     */
    public function apply_neumorphism( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $distance = (int) $request->get_param( 'distance' );
        $intensity = (float) $request->get_param( 'intensity' );
        $blur = (int) $request->get_param( 'blur' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $light_shadow = "rgba(255, 255, 255, {$intensity})";
        $dark_shadow = "rgba(0, 0, 0, {$intensity})";

        $shadows = array(
            'flat' => "{$distance}px {$distance}px {$blur}px {$dark_shadow}, -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
            'concave' => "inset {$distance}px {$distance}px {$blur}px {$dark_shadow}, inset -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
            'convex' => "{$distance}px {$distance}px {$blur}px {$dark_shadow}, -{$distance}px -{$distance}px {$blur}px {$light_shadow}, inset 2px 2px 4px {$light_shadow}",
            'pressed' => "inset {$distance}px {$distance}px {$blur}px {$dark_shadow}, inset -{$distance}px -{$distance}px {$blur}px {$light_shadow}",
        );

        $neumorphism_styles = array(
            'boxShadow' => $shadows[ $type ] ?? $shadows['flat'],
            'borderRadius' => '20px',
            'background' => '#e0e5ec',
        );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $neumorphism_styles, $type ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $neumorphism_styles );
            $el['data']['_effect'] = 'neumorphism';
            $el['data']['_neumorphism_type'] = $type;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'effect' => 'neumorphism', 'type' => $type ), 200 );
    }

    /**
     * Aplica gradiente avanzado
     */
    public function apply_advanced_gradient( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = sanitize_text_field( $request->get_param( 'type' ) );
        $colors = $request->get_param( 'colors' );
        $angle = (int) $request->get_param( 'angle' );
        $animated = (bool) $request->get_param( 'animated' );
        $target = sanitize_text_field( $request->get_param( 'target' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $color_stops = implode( ', ', $colors );
        $gradient = $this->build_gradient( $type, $angle, $color_stops, $colors );
        $gradient_styles = $this->build_gradient_styles( $gradient, $target, $animated );

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $gradient_styles, $type, $animated ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $gradient_styles );
            $el['data']['_gradient'] = array( 'type' => $type, 'animated' => $animated );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'gradient' => $gradient, 'animated' => $animated ), 200 );
    }

    private function build_gradient( $type, $angle, $color_stops, $colors ) {
        $third_color = isset( $colors[2] ) ? $colors[2] : $colors[0];
        switch ( $type ) {
            case 'linear': return "linear-gradient({$angle}deg, {$color_stops})";
            case 'radial': return "radial-gradient(circle, {$color_stops})";
            case 'conic': return "conic-gradient(from {$angle}deg, {$color_stops})";
            case 'mesh': return "radial-gradient(at 40% 20%, {$colors[0]} 0px, transparent 50%), radial-gradient(at 80% 0%, {$colors[1]} 0px, transparent 50%), radial-gradient(at 0% 50%, {$third_color} 0px, transparent 50%)";
            default: return "linear-gradient({$angle}deg, {$color_stops})";
        }
    }

    private function build_gradient_styles( $gradient, $target, $animated ) {
        $styles = array();
        if ( $target === 'background' ) {
            $styles['background'] = $gradient;
            if ( $animated ) {
                $styles['backgroundSize'] = '400% 400%';
                $styles['animation'] = 'gradientShift 15s ease infinite';
            }
        } elseif ( $target === 'text' ) {
            $styles['background'] = $gradient;
            $styles['backgroundClip'] = 'text';
            $styles['webkitBackgroundClip'] = 'text';
            $styles['webkitTextFillColor'] = 'transparent';
        } elseif ( $target === 'border' ) {
            $styles['border'] = '3px solid transparent';
            $styles['backgroundImage'] = "linear-gradient(white, white), {$gradient}";
            $styles['backgroundOrigin'] = 'border-box';
            $styles['backgroundClip'] = 'padding-box, border-box';
        }
        return $styles;
    }

    /**
     * Aplica blend mode
     */
    public function apply_blend_mode( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $mode = sanitize_text_field( $request->get_param( 'mode' ) );
        $target = sanitize_text_field( $request->get_param( 'target' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $blend_styles = array();
        if ( $target === 'background' ) {
            $blend_styles['backgroundBlendMode'] = $mode;
        } else {
            $blend_styles['mixBlendMode'] = $mode;
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $blend_styles, $mode ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $blend_styles );
            $el['data']['_blend_mode'] = $mode;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'blend_mode' => $mode ), 200 );
    }

    /**
     * Aplica clip-path
     */
    public function apply_clip_path( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $shape = sanitize_text_field( $request->get_param( 'shape' ) );
        $custom_path = $request->get_param( 'custom_path' );
        $animated = (bool) $request->get_param( 'animated' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $paths = array(
            'circle' => 'circle(50% at 50% 50%)',
            'ellipse' => 'ellipse(50% 40% at 50% 50%)',
            'polygon' => 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
            'wave-top' => 'polygon(0% 100%, 0% 85%, 10% 90%, 25% 78%, 40% 85%, 55% 75%, 70% 85%, 85% 78%, 100% 90%, 100% 100%)',
            'diagonal' => 'polygon(0 0, 100% 0, 100% 85%, 0 100%)',
            'arrow' => 'polygon(40% 0%, 100% 0%, 100% 100%, 40% 100%, 0% 50%)',
            'custom' => $custom_path ?: 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
        );

        $clip_path = $paths[ $shape ] ?? $paths['polygon'];
        $clip_styles = array( 'clipPath' => $clip_path, 'webkitClipPath' => $clip_path );
        if ( $animated ) { $clip_styles['transition'] = 'clip-path 0.5s ease-out'; }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $clip_styles, $shape ) {
            if ( ! isset( $el['styles']['advanced'] ) ) { $el['styles']['advanced'] = array(); }
            $el['styles']['advanced'] = array_merge( $el['styles']['advanced'], $clip_styles );
            $el['data']['_clip_path'] = $shape;
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );
        return new WP_REST_Response( array( 'success' => true, 'clip_path' => $clip_path ), 200 );
    }

    // =============================================
}
