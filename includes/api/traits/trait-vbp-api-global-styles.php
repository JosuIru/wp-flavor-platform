<?php
/**
 * Trait para estilos globales y design system VBP
 *
 * Este trait contiene métodos para gestión de variables CSS globales
 * y el sistema de diseño unificado.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_GlobalStyles
 *
 * Contiene métodos para:
 * - Variables CSS globales (get_global_styles, save_global_styles)
 * - Design system unificado (get_design_system, update_design_system)
 */
trait VBP_API_GlobalStyles {

    /**
     * Obtiene variables CSS globales
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_global_styles( $request ) {
        $styles = get_option( 'flavor_vbp_global_styles', array() );

        $defaults = array(
            'colors' => array(
                'primary'    => '#3b82f6',
                'secondary'  => '#8b5cf6',
                'accent'     => '#f59e0b',
                'background' => '#ffffff',
                'text'       => '#1f2937',
                'muted'      => '#6b7280',
            ),
            'typography' => array(
                'font_family'   => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'heading_font'  => 'inherit',
                'base_size'     => '16px',
                'line_height'   => '1.6',
            ),
            'spacing' => array(
                'section_padding' => '4rem',
                'container_max'   => '1200px',
                'gap'             => '1rem',
            ),
            'custom_css' => '',
        );

        $merged = array_merge( $defaults, $styles );

        return new WP_REST_Response( array(
            'success' => true,
            'styles'  => $merged,
            'css'     => $this->generate_global_css( $merged ),
        ), 200 );
    }

    /**
     * Guarda variables CSS globales
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_global_styles( $request ) {
        $colors = $request->get_param( 'colors' );
        $typography = $request->get_param( 'typography' );
        $spacing = $request->get_param( 'spacing' );
        $custom_css = $request->get_param( 'custom_css' );

        $current = get_option( 'flavor_vbp_global_styles', array() );

        if ( $colors ) {
            $current['colors'] = array_merge( $current['colors'] ?? array(), $colors );
        }
        if ( $typography ) {
            $current['typography'] = array_merge( $current['typography'] ?? array(), $typography );
        }
        if ( $spacing ) {
            $current['spacing'] = array_merge( $current['spacing'] ?? array(), $spacing );
        }
        if ( $custom_css !== null ) {
            $current['custom_css'] = wp_strip_all_tags( $custom_css );
        }

        update_option( 'flavor_vbp_global_styles', $current );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Estilos globales guardados.',
            'styles'  => $current,
            'css'     => $this->generate_global_css( $current ),
        ), 200 );
    }

    /**
     * Genera CSS de estilos globales
     *
     * @param array $styles Estilos.
     * @return string
     */
    private function generate_global_css( $styles ) {
        $css = ':root {';

        // Colores
        if ( ! empty( $styles['colors'] ) ) {
            foreach ( $styles['colors'] as $name => $value ) {
                $css .= " --vbp-{$name}: {$value};";
            }
        }

        // Tipografía
        if ( ! empty( $styles['typography'] ) ) {
            if ( ! empty( $styles['typography']['font_family'] ) ) {
                $css .= " --vbp-font-family: {$styles['typography']['font_family']};";
            }
            if ( ! empty( $styles['typography']['base_size'] ) ) {
                $css .= " --vbp-font-size: {$styles['typography']['base_size']};";
            }
            if ( ! empty( $styles['typography']['line_height'] ) ) {
                $css .= " --vbp-line-height: {$styles['typography']['line_height']};";
            }
        }

        // Espaciado
        if ( ! empty( $styles['spacing'] ) ) {
            foreach ( $styles['spacing'] as $name => $value ) {
                $css .= " --vbp-{$name}: {$value};";
            }
        }

        $css .= ' }';

        // CSS personalizado
        if ( ! empty( $styles['custom_css'] ) ) {
            $css .= "\n" . $styles['custom_css'];
        }

        return $css;
    }

    // =========================================================================
    // DESIGN SYSTEM UNIFICADO
    // =========================================================================

    /**
     * Obtiene el design system completo unificado.
     * Este endpoint reemplaza: global-styles, design/variables, css-variables
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_design_system( $request ) {
        $design_system = get_option( 'flavor_vbp_design_system', array() );

        $defaults = array(
            'colors' => array(
                'primary'    => '#3b82f6',
                'secondary'  => '#8b5cf6',
                'accent'     => '#06b6d4',
                'success'    => '#22c55e',
                'warning'    => '#f59e0b',
                'error'      => '#ef4444',
                'background' => '#ffffff',
                'surface'    => '#f8fafc',
                'text'       => '#1e293b',
                'muted'      => '#64748b',
            ),
            'typography' => array(
                'font_family'    => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'heading_font'   => 'inherit',
                'base_size'      => '16px',
                'line_height'    => '1.6',
                'scale_ratio'    => 1.25,
                'letter_spacing' => 'normal',
            ),
            'spacing' => array(
                'xs'  => '4px',
                'sm'  => '8px',
                'md'  => '16px',
                'lg'  => '24px',
                'xl'  => '32px',
                '2xl' => '48px',
                '3xl' => '64px',
                'section_padding' => '4rem',
                'container_max'   => '1200px',
            ),
            'borders' => array(
                'radius_none' => '0',
                'radius_sm'   => '4px',
                'radius_md'   => '8px',
                'radius_lg'   => '12px',
                'radius_xl'   => '16px',
                'radius_full' => '9999px',
            ),
            'shadows' => array(
                'sm'  => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                'md'  => '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                'lg'  => '0 10px 15px -3px rgb(0 0 0 / 0.1)',
                'xl'  => '0 20px 25px -5px rgb(0 0 0 / 0.1)',
            ),
            'custom_css' => '',
        );

        $merged = $this->deep_merge_arrays( $defaults, $design_system );

        return new WP_REST_Response( array(
            'success'       => true,
            'design_system' => $merged,
            'css'           => $this->generate_design_system_css( $merged ),
            'css_variables' => $this->generate_css_variables_output( $merged ),
        ), 200 );
    }

    /**
     * Actualiza el design system completo.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_design_system( $request ) {
        $current = get_option( 'flavor_vbp_design_system', array() );

        $sections = array( 'colors', 'typography', 'spacing', 'borders', 'shadows' );

        foreach ( $sections as $section ) {
            $value = $request->get_param( $section );
            if ( is_array( $value ) ) {
                $current[ $section ] = array_merge( $current[ $section ] ?? array(), $value );
            }
        }

        $custom_css = $request->get_param( 'custom_css' );
        if ( $custom_css !== null ) {
            $current['custom_css'] = wp_strip_all_tags( $custom_css );
        }

        update_option( 'flavor_vbp_design_system', $current );

        // También sincronizar con las opciones legacy para compatibilidad
        $this->sync_design_system_to_legacy( $current );

        return new WP_REST_Response( array(
            'success'       => true,
            'message'       => 'Design system actualizado.',
            'design_system' => $current,
            'css'           => $this->generate_design_system_css( $current ),
        ), 200 );
    }

    /**
     * Genera CSS del design system.
     *
     * @param array $design_system Design system.
     * @return string
     */
    private function generate_design_system_css( $design_system ) {
        $css = ':root {' . "\n";

        // Colores
        if ( ! empty( $design_system['colors'] ) ) {
            foreach ( $design_system['colors'] as $name => $value ) {
                $css .= "  --vbp-color-{$name}: {$value};\n";
            }
        }

        // Tipografía
        if ( ! empty( $design_system['typography'] ) ) {
            $typo = $design_system['typography'];
            if ( ! empty( $typo['font_family'] ) ) {
                $css .= "  --vbp-font-family: {$typo['font_family']};\n";
            }
            if ( ! empty( $typo['heading_font'] ) && $typo['heading_font'] !== 'inherit' ) {
                $css .= "  --vbp-font-heading: {$typo['heading_font']};\n";
            }
            if ( ! empty( $typo['base_size'] ) ) {
                $css .= "  --vbp-font-size-base: {$typo['base_size']};\n";
            }
            if ( ! empty( $typo['line_height'] ) ) {
                $css .= "  --vbp-line-height: {$typo['line_height']};\n";
            }
        }

        // Espaciado
        if ( ! empty( $design_system['spacing'] ) ) {
            foreach ( $design_system['spacing'] as $name => $value ) {
                $css .= "  --vbp-space-{$name}: {$value};\n";
            }
        }

        // Bordes
        if ( ! empty( $design_system['borders'] ) ) {
            foreach ( $design_system['borders'] as $name => $value ) {
                $css_name = str_replace( '_', '-', $name );
                $css .= "  --vbp-{$css_name}: {$value};\n";
            }
        }

        // Sombras
        if ( ! empty( $design_system['shadows'] ) ) {
            foreach ( $design_system['shadows'] as $name => $value ) {
                $css .= "  --vbp-shadow-{$name}: {$value};\n";
            }
        }

        $css .= "}\n";

        // CSS personalizado
        if ( ! empty( $design_system['custom_css'] ) ) {
            $css .= "\n" . $design_system['custom_css'];
        }

        return $css;
    }

    /**
     * Genera output de variables CSS para uso en JS/React.
     *
     * @param array $design_system Design system.
     * @return array
     */
    private function generate_css_variables_output( $design_system ) {
        $variables = array();

        if ( ! empty( $design_system['colors'] ) ) {
            foreach ( $design_system['colors'] as $name => $value ) {
                $variables["--vbp-color-{$name}"] = $value;
            }
        }

        if ( ! empty( $design_system['spacing'] ) ) {
            foreach ( $design_system['spacing'] as $name => $value ) {
                $variables["--vbp-space-{$name}"] = $value;
            }
        }

        if ( ! empty( $design_system['borders'] ) ) {
            foreach ( $design_system['borders'] as $name => $value ) {
                $css_name = str_replace( '_', '-', $name );
                $variables["--vbp-{$css_name}"] = $value;
            }
        }

        return $variables;
    }

    /**
     * Sincroniza design system con opciones legacy para compatibilidad.
     *
     * @param array $design_system Design system.
     */
    private function sync_design_system_to_legacy( $design_system ) {
        // Sincronizar con global-styles
        $global_styles = array(
            'colors'     => $design_system['colors'] ?? array(),
            'typography' => $design_system['typography'] ?? array(),
            'spacing'    => $design_system['spacing'] ?? array(),
            'custom_css' => $design_system['custom_css'] ?? '',
        );
        update_option( 'flavor_vbp_global_styles', $global_styles );

        // Sincronizar con design/variables
        update_option( 'flavor_vbp_design_variables', array(
            'colors'     => $design_system['colors'] ?? array(),
            'spacing'    => $design_system['spacing'] ?? array(),
            'typography' => $design_system['typography'] ?? array(),
            'borders'    => $design_system['borders'] ?? array(),
        ) );
    }

    /**
     * Deep merge de arrays.
     *
     * @param array $array1 Array base.
     * @param array $array2 Array a mergear.
     * @return array
     */
    private function deep_merge_arrays( $array1, $array2 ) {
        $merged = $array1;
        foreach ( $array2 as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = $this->deep_merge_arrays( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }
        return $merged;
    }
}
