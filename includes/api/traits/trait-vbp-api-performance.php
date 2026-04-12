<?php
/**
 * Trait para Performance y Optimización VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Performance {

    // =============================================
    // MÉTODOS DE OPTIMIZACIÓN DE IMÁGENES
    // =============================================

    /**
     * Configura lazy loading de imágenes
     */
    public function configure_images_lazy_load( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $enabled = (bool) $request->get_param( 'enabled' );
        $threshold = sanitize_text_field( $request->get_param( 'threshold' ) );
        $placeholder = sanitize_text_field( $request->get_param( 'placeholder' ) );
        $fade_in = (bool) $request->get_param( 'fade_in' );

        $lazy_config = array(
            'enabled' => $enabled,
            'threshold' => $threshold,
            'placeholder' => $placeholder,
            'fade_in' => $fade_in,
        );

        update_post_meta( $page_id, '_vbp_images_lazy_load', $lazy_config );

        return new WP_REST_Response( array( 'success' => true, 'lazy_load' => $lazy_config ), 200 );
    }

    /**
     * Genera srcset automático
     */
    public function generate_image_srcset( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $widths = $request->get_param( 'widths' );
        $quality = (int) $request->get_param( 'quality' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );

        $srcset_config = array(
            'widths' => $widths,
            'quality' => $quality,
            'format' => $format,
        );

        update_post_meta( $page_id, '_vbp_image_srcset', $srcset_config );

        return new WP_REST_Response( array( 'success' => true, 'srcset_config' => $srcset_config ), 200 );
    }

    // =============================================
    // MÉTODOS DE PERFORMANCE
    // =============================================

    /**
     * Obtiene score de performance
     */
    public function get_performance_score( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $element_count = $this->count_elements( $elements );
        $image_count = $this->count_elements_by_type( $elements, 'image' );
        $animation_count = $this->count_animations( $elements );

        $score = 100;
        $issues = array();

        // Penalizaciones
        if ( $element_count > 100 ) {
            $score -= 10;
            $issues[] = array( 'type' => 'warning', 'message' => "Muchos elementos ({$element_count}). Considera simplificar." );
        }
        if ( $image_count > 10 && ! get_post_meta( $page_id, '_vbp_images_lazy_load', true ) ) {
            $score -= 15;
            $issues[] = array( 'type' => 'error', 'message' => 'Activa lazy loading para las imágenes.' );
        }
        if ( $animation_count > 20 ) {
            $score -= 10;
            $issues[] = array( 'type' => 'warning', 'message' => 'Demasiadas animaciones pueden afectar el rendimiento.' );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'score' => max( 0, $score ),
            'grade' => $score >= 90 ? 'A' : ( $score >= 70 ? 'B' : ( $score >= 50 ? 'C' : 'D' ) ),
            'issues' => $issues,
            'metrics' => array(
                'elements' => $element_count,
                'images' => $image_count,
                'animations' => $animation_count,
            ),
        ), 200 );
    }

    /**
     * Cuenta animaciones
     */
    private function count_animations( $elements ) {
        $count = 0;
        foreach ( $elements as $el ) {
            if ( ! empty( $el['data']['_animation'] ) ) {
                $count++;
            }
            if ( ! empty( $el['children'] ) ) {
                $count += $this->count_animations( $el['children'] );
            }
        }
        return $count;
    }

    /**
     * Auto optimiza performance
     */
    public function auto_optimize_performance( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $targets = $request->get_param( 'targets' );
        $level = sanitize_text_field( $request->get_param( 'level' ) );

        $optimizations_applied = array();

        if ( in_array( 'images', $targets, true ) ) {
            update_post_meta( $page_id, '_vbp_images_lazy_load', array(
                'enabled' => true,
                'threshold' => '200px',
                'placeholder' => 'blur',
                'fade_in' => true,
            ) );
            $optimizations_applied[] = 'images_lazy_load';
        }

        if ( in_array( 'fonts', $targets, true ) ) {
            update_post_meta( $page_id, '_vbp_font_display', 'swap' );
            $optimizations_applied[] = 'font_display_swap';
        }

        if ( in_array( 'css', $targets, true ) && $level !== 'safe' ) {
            update_post_meta( $page_id, '_vbp_css_optimize', array(
                'minify' => true,
                'critical' => $level === 'aggressive',
            ) );
            $optimizations_applied[] = 'css_optimized';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'optimizations' => $optimizations_applied,
            'level' => $level,
        ), 200 );
    }

    // =============================================
    // MÉTODOS DE BREAKPOINTS Y PRESETS
    // =============================================

    /**
     * Obtiene presets de breakpoints
     */
    public function get_breakpoint_presets( $request ) {
        $presets = array(
            'default' => array(
                array( 'name' => 'mobile', 'min' => 0, 'max' => 639 ),
                array( 'name' => 'tablet', 'min' => 640, 'max' => 1023 ),
                array( 'name' => 'desktop', 'min' => 1024, 'max' => null ),
            ),
            'tailwind' => array(
                array( 'name' => 'sm', 'min' => 640, 'max' => 767 ),
                array( 'name' => 'md', 'min' => 768, 'max' => 1023 ),
                array( 'name' => 'lg', 'min' => 1024, 'max' => 1279 ),
                array( 'name' => 'xl', 'min' => 1280, 'max' => 1535 ),
                array( 'name' => '2xl', 'min' => 1536, 'max' => null ),
            ),
            'bootstrap' => array(
                array( 'name' => 'xs', 'min' => 0, 'max' => 575 ),
                array( 'name' => 'sm', 'min' => 576, 'max' => 767 ),
                array( 'name' => 'md', 'min' => 768, 'max' => 991 ),
                array( 'name' => 'lg', 'min' => 992, 'max' => 1199 ),
                array( 'name' => 'xl', 'min' => 1200, 'max' => 1399 ),
                array( 'name' => 'xxl', 'min' => 1400, 'max' => null ),
            ),
        );

        return new WP_REST_Response( array( 'success' => true, 'presets' => $presets ), 200 );
    }

    /**
     * Crea breakpoint personalizado
     */
    public function create_custom_breakpoint( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $min_width = $request->get_param( 'min_width' );
        $max_width = $request->get_param( 'max_width' );
        $orientation = $request->get_param( 'orientation' );

        $breakpoints = get_option( 'flavor_vbp_breakpoints', array() );
        $breakpoints[] = array(
            'name' => $name,
            'min_width' => $min_width,
            'max_width' => $max_width,
            'orientation' => $orientation,
            'custom' => true,
        );

        update_option( 'flavor_vbp_breakpoints', $breakpoints );

        return new WP_REST_Response( array( 'success' => true, 'breakpoint' => end( $breakpoints ) ), 201 );
    }

    /**
     * Obtiene preset completo
     */
    public function get_full_preset_config( $request ) {
        $preset_id = sanitize_text_field( $request->get_param( 'preset_id' ) );

        $presets = $this->get_all_design_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preset no encontrado' ), 404 );
        }

        $preset = $presets[ $preset_id ];

        $full_config = array(
            'id' => $preset_id,
            'name' => $preset['name'] ?? ucfirst( $preset_id ),
            'colors' => $preset['colors'] ?? array(),
            'gradients' => $preset['gradients'] ?? array(),
            'shadows' => $preset['shadows'] ?? array(),
            'typography' => array(
                'font_family' => $preset['font_family'] ?? 'Inter, sans-serif',
                'heading_weight' => $preset['heading_weight'] ?? 700,
                'body_weight' => $preset['body_weight'] ?? 400,
            ),
            'borders' => $preset['borders'] ?? array(),
            'spacing' => array(
                'section_padding' => $preset['section_padding'] ?? '5rem 0',
                'container_max_width' => $preset['container_max_width'] ?? '1200px',
            ),
            'animations' => $preset['default_animations'] ?? array(),
        );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $full_config ), 200 );
    }

    /**
     * Crea preset personalizado
     */
    public function create_custom_preset( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $colors = $request->get_param( 'colors' );
        $typography = $request->get_param( 'typography' );
        $spacing = $request->get_param( 'spacing' );
        $borders = $request->get_param( 'borders' );
        $shadows = $request->get_param( 'shadows' );
        $animations = $request->get_param( 'animations' );

        $preset_id = sanitize_title( $name ) . '_' . time();

        $custom_preset = array(
            'id' => $preset_id,
            'name' => $name,
            'colors' => $colors,
            'typography' => $typography,
            'spacing' => $spacing,
            'borders' => $borders,
            'shadows' => $shadows,
            'animations' => $animations,
            'custom' => true,
            'created_at' => current_time( 'mysql' ),
        );

        $custom_presets = get_option( 'flavor_vbp_custom_presets', array() );
        $custom_presets[ $preset_id ] = $custom_preset;
        update_option( 'flavor_vbp_custom_presets', $custom_presets );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $custom_preset ), 201 );
    }

    /**
     * Duplica preset
     */
    public function duplicate_preset( $request ) {
        $preset_id = sanitize_text_field( $request->get_param( 'preset_id' ) );
        $new_name = sanitize_text_field( $request->get_param( 'new_name' ) );
        $modifications = $request->get_param( 'modifications' ) ?: array();

        $presets = $this->get_all_design_presets();
        if ( ! isset( $presets[ $preset_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Preset no encontrado' ), 404 );
        }

        $new_preset_id = sanitize_title( $new_name ) . '_' . time();
        $new_preset = array_merge( $presets[ $preset_id ], $modifications, array(
            'id' => $new_preset_id,
            'name' => $new_name,
            'custom' => true,
            'based_on' => $preset_id,
            'created_at' => current_time( 'mysql' ),
        ) );

        $custom_presets = get_option( 'flavor_vbp_custom_presets', array() );
        $custom_presets[ $new_preset_id ] = $new_preset;
        update_option( 'flavor_vbp_custom_presets', $custom_presets );

        return new WP_REST_Response( array( 'success' => true, 'preset' => $new_preset ), 201 );
    }

    // =============================================
}
