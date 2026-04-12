<?php
/**
 * Trait para Componentes UI Modernos VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ModernUI {

    /**
     * Obtiene componentes UI modernos
     */
    public function get_modern_ui_components( $request ) {
        $components = array(
            'cards' => array( 'name' => 'Tarjetas', 'styles' => array( 'elevated', 'outlined', 'filled', 'glass' ) ),
            'buttons' => array( 'name' => 'Botones', 'styles' => array( 'solid', 'outline', 'ghost', 'gradient', 'neon' ) ),
            'badges' => array( 'name' => 'Badges', 'styles' => array( 'solid', 'subtle', 'outline', 'dot' ) ),
            'avatars' => array( 'name' => 'Avatares', 'styles' => array( 'circular', 'rounded', 'square', 'group' ) ),
            'tooltips' => array( 'name' => 'Tooltips', 'styles' => array( 'dark', 'light', 'colored' ) ),
            'pills' => array( 'name' => 'Pills', 'styles' => array( 'solid', 'outline', 'gradient' ) ),
        );
        return new WP_REST_Response( array( 'success' => true, 'components' => $components ), 200 );
    }

    /**
     * Crea tarjeta moderna
     */
    public function create_modern_card( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $parent_block_id = sanitize_text_field( $request->get_param( 'parent_block_id' ) );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $hover_effect = sanitize_text_field( $request->get_param( 'hover_effect' ) );
        $content = $request->get_param( 'content' ) ?: array();

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $card_id = 'card_' . uniqid();
        $card_styles = $this->get_card_styles( $style );
        $hover_styles = $this->get_card_hover_effect( $hover_effect );

        $card = array(
            'id' => $card_id,
            'type' => 'container',
            'styles' => array_merge( $card_styles, array( 'padding' => '24px', 'borderRadius' => '12px' ) ),
            'data' => array( '_component' => 'modern_card', '_style' => $style, '_hover' => $hover_styles ),
            'children' => array(),
        );

        if ( ! empty( $content['image'] ) ) {
            $card['children'][] = array( 'id' => 'img_' . uniqid(), 'type' => 'image', 'data' => array( 'src' => $content['image'], 'alt' => $content['title'] ?? '' ), 'styles' => array( 'borderRadius' => '8px', 'marginBottom' => '16px' ) );
        }
        if ( ! empty( $content['title'] ) ) {
            $card['children'][] = array( 'id' => 'h_' . uniqid(), 'type' => 'heading', 'data' => array( 'text' => $content['title'], 'level' => 3 ), 'styles' => array( 'marginBottom' => '8px' ) );
        }
        if ( ! empty( $content['description'] ) ) {
            $card['children'][] = array( 'id' => 't_' . uniqid(), 'type' => 'text', 'data' => array( 'content' => $content['description'] ), 'styles' => array( 'color' => '#666' ) );
        }
        if ( ! empty( $content['button'] ) ) {
            $card['children'][] = array( 'id' => 'b_' . uniqid(), 'type' => 'button', 'data' => array( 'text' => $content['button'], 'style' => 'primary' ), 'styles' => array( 'marginTop' => '16px' ) );
        }

        if ( $parent_block_id ) {
            $elements = $this->update_element_by_id( $elements, $parent_block_id, function( $el ) use ( $card ) {
                if ( ! isset( $el['children'] ) ) { $el['children'] = array(); }
                $el['children'][] = $card;
                return $el;
            } );
        } else {
            $elements[] = $card;
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'card_id' => $card_id ), 201 );
    }

    private function get_card_styles( $style ) {
        switch ( $style ) {
            case 'elevated': return array( 'background' => '#fff', 'boxShadow' => '0 4px 20px rgba(0,0,0,0.1)' );
            case 'outlined': return array( 'background' => '#fff', 'border' => '1px solid #e0e0e0' );
            case 'filled': return array( 'background' => '#f5f5f5' );
            case 'glass': return array( 'background' => 'rgba(255,255,255,0.7)', 'backdropFilter' => 'blur(10px)', 'border' => '1px solid rgba(255,255,255,0.3)' );
            default: return array( 'background' => '#fff', 'boxShadow' => '0 2px 8px rgba(0,0,0,0.08)' );
        }
    }

    private function get_card_hover_effect( $effect ) {
        switch ( $effect ) {
            case 'lift': return array( 'transform' => 'translateY(-4px)', 'boxShadow' => '0 8px 30px rgba(0,0,0,0.12)' );
            case 'scale': return array( 'transform' => 'scale(1.02)' );
            case 'glow': return array( 'boxShadow' => '0 0 20px rgba(59,130,246,0.3)' );
            default: return array();
        }
    }

    /**
     * Crea botón moderno
     */
    public function create_modern_button( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $parent_block_id = sanitize_text_field( $request->get_param( 'parent_block_id' ) );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $size = sanitize_text_field( $request->get_param( 'size' ) );
        $text = sanitize_text_field( $request->get_param( 'text' ) );
        $url = esc_url_raw( $request->get_param( 'url' ) );
        $icon = sanitize_text_field( $request->get_param( 'icon' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $button_id = 'btn_' . uniqid();
        $button_styles = $this->get_button_styles( $style, $size );

        $button = array(
            'id' => $button_id,
            'type' => 'button',
            'data' => array( 'text' => $text, 'url' => $url, 'icon' => $icon, '_component' => 'modern_button' ),
            'styles' => $button_styles,
        );

        if ( $parent_block_id ) {
            $elements = $this->update_element_by_id( $elements, $parent_block_id, function( $el ) use ( $button ) {
                if ( ! isset( $el['children'] ) ) { $el['children'] = array(); }
                $el['children'][] = $button;
                return $el;
            } );
        } else {
            $elements[] = $button;
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'button_id' => $button_id ), 201 );
    }

    private function get_button_styles( $style, $size ) {
        $base = array( 'borderRadius' => '8px', 'cursor' => 'pointer', 'transition' => 'all 0.2s' );
        $sizes = array(
            'sm' => array( 'padding' => '8px 16px', 'fontSize' => '14px' ),
            'md' => array( 'padding' => '12px 24px', 'fontSize' => '16px' ),
            'lg' => array( 'padding' => '16px 32px', 'fontSize' => '18px' ),
        );
        $styles = array(
            'solid' => array( 'background' => '#3b82f6', 'color' => '#fff', 'border' => 'none' ),
            'outline' => array( 'background' => 'transparent', 'border' => '2px solid #3b82f6', 'color' => '#3b82f6' ),
            'ghost' => array( 'background' => 'transparent', 'border' => 'none', 'color' => '#3b82f6' ),
            'gradient' => array( 'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'color' => '#fff', 'border' => 'none' ),
            'neon' => array( 'background' => 'transparent', 'border' => '2px solid #00ff88', 'color' => '#00ff88', 'boxShadow' => '0 0 10px #00ff88, inset 0 0 10px rgba(0,255,136,0.1)' ),
        );

        return array_merge( $base, $sizes[ $size ] ?? $sizes['md'], $styles[ $style ] ?? $styles['solid'] );
    }

    /**
     * Crea badge
     */
    public function create_badge( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $parent_block_id = sanitize_text_field( $request->get_param( 'parent_block_id' ) );
        $text = sanitize_text_field( $request->get_param( 'text' ) );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $color = sanitize_hex_color( $request->get_param( 'color' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $badge_id = 'badge_' . uniqid();
        $badge_styles = $this->get_badge_styles( $style, $color ?: '#3b82f6' );

        $badge = array(
            'id' => $badge_id,
            'type' => 'text',
            'data' => array( 'content' => $text, '_component' => 'badge' ),
            'styles' => $badge_styles,
        );

        if ( $parent_block_id ) {
            $elements = $this->update_element_by_id( $elements, $parent_block_id, function( $el ) use ( $badge ) {
                if ( ! isset( $el['children'] ) ) { $el['children'] = array(); }
                $el['children'][] = $badge;
                return $el;
            } );
        } else {
            $elements[] = $badge;
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'badge_id' => $badge_id ), 201 );
    }

    private function get_badge_styles( $style, $color ) {
        $base = array( 'display' => 'inline-block', 'padding' => '4px 12px', 'borderRadius' => '9999px', 'fontSize' => '12px', 'fontWeight' => '600' );
        switch ( $style ) {
            case 'solid': return array_merge( $base, array( 'background' => $color, 'color' => '#fff' ) );
            case 'subtle': return array_merge( $base, array( 'background' => $color . '20', 'color' => $color ) );
            case 'outline': return array_merge( $base, array( 'background' => 'transparent', 'border' => '1px solid ' . $color, 'color' => $color ) );
            default: return array_merge( $base, array( 'background' => $color, 'color' => '#fff' ) );
        }
    }

    /**
     * Crea avatar
     */
    public function create_avatar( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $parent_block_id = sanitize_text_field( $request->get_param( 'parent_block_id' ) );
        $image_url = esc_url_raw( $request->get_param( 'image_url' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $size = sanitize_text_field( $request->get_param( 'size' ) );
        $shape = sanitize_text_field( $request->get_param( 'shape' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $avatar_id = 'avatar_' . uniqid();
        $sizes = array( 'xs' => 24, 'sm' => 32, 'md' => 48, 'lg' => 64, 'xl' => 96 );
        $dimension = $sizes[ $size ] ?? 48;
        $border_radius = ( $shape === 'circular' ) ? '9999px' : ( ( $shape === 'rounded' ) ? '8px' : '0' );

        $avatar = array(
            'id' => $avatar_id,
            'type' => 'image',
            'data' => array( 'src' => $image_url, 'alt' => $name, '_component' => 'avatar' ),
            'styles' => array( 'width' => $dimension . 'px', 'height' => $dimension . 'px', 'borderRadius' => $border_radius, 'objectFit' => 'cover' ),
        );

        if ( $parent_block_id ) {
            $elements = $this->update_element_by_id( $elements, $parent_block_id, function( $el ) use ( $avatar ) {
                if ( ! isset( $el['children'] ) ) { $el['children'] = array(); }
                $el['children'][] = $avatar;
                return $el;
            } );
        } else {
            $elements[] = $avatar;
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'avatar_id' => $avatar_id ), 201 );
    }

    /**
     * Añade tooltip
     */
    public function add_tooltip( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $text = sanitize_text_field( $request->get_param( 'text' ) );
        $position = sanitize_text_field( $request->get_param( 'position' ) );
        $style = sanitize_text_field( $request->get_param( 'style' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $el ) use ( $text, $position, $style ) {
            $el['data']['_tooltip'] = array( 'text' => $text, 'position' => $position, 'style' => $style );
            return $el;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'tooltip' => array( 'text' => $text, 'position' => $position ) ), 200 );
    }

    // =============================================
}
