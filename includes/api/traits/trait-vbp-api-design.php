<?php
/**
 * Trait para sistema de diseño VBP
 *
 * Este trait contiene todos los métodos relacionados con presets de diseño,
 * estilos globales, y sistema de temas.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Design
 *
 * Contiene métodos para:
 * - Obtener presets de diseño (get_design_presets)
 * - Obtener preset específico (get_design_preset)
 * - Crear páginas con estilo (create_styled_page)
 * - Estilos por defecto (get_default_styles)
 * - Mezclar estilos (merge_styles)
 */
trait VBP_API_Design {

    /**
     * Obtiene todos los presets de diseño disponibles
     *
     * @return WP_REST_Response
     */
    public function get_design_presets() {
        $presets = $this->get_all_design_presets();

        $result = array();
        foreach ( $presets as $key => $preset ) {
            $result[] = array(
                'id'          => $key,
                'name'        => $preset['name'],
                'description' => $preset['description'],
                'preview'     => $preset['preview'] ?? '',
                'colors'      => array(
                    'primary'    => $preset['colors']['primary'],
                    'secondary'  => $preset['colors']['secondary'],
                    'accent'     => $preset['colors']['accent'],
                    'background' => $preset['colors']['background'],
                    'text'       => $preset['colors']['text'],
                ),
                'style'       => $preset['style'] ?? 'modern',
                'animations'  => ! empty( $preset['default_animations'] ),
            );
        }

        return new WP_REST_Response( array(
            'presets' => $result,
            'total'   => count( $result ),
            'usage'   => 'Usar con POST /claude/pages/styled { "preset": "modern", "sections": [...] }',
        ), 200 );
    }

    /**
     * Obtiene un preset de diseño específico
     *
     * @param string $preset_name Nombre del preset.
     * @return array|null
     */
    private function get_design_preset( $preset_name ) {
        $presets = $this->get_all_design_presets();

        if ( ! isset( $presets[ $preset_name ] ) ) {
            return null;
        }

        $preset = $presets[ $preset_name ];

        return array(
            'primaryColor'      => $preset['colors']['primary'],
            'secondaryColor'    => $preset['colors']['secondary'],
            'accentColor'       => $preset['colors']['accent'],
            'backgroundColor'   => $preset['colors']['background'],
            'textColor'         => $preset['colors']['text'],
            'borderColor'       => $preset['colors']['border'] ?? '#e5e7eb',
            'borderRadius'      => $preset['borders']['radius_md'] ?? '0.5rem',
            'fontFamily'        => $preset['typography']['font_family'] ?? 'Inter, sans-serif',
            'sectionPadding'    => $preset['spacing']['section_padding'] ?? '4rem',
            'containerMaxWidth' => $preset['spacing']['container_max'] ?? '1200px',
        );
    }

    /**
     * Define todos los presets de diseño disponibles
     *
     * @return array
     */
    private function get_all_design_presets() {
        return array(
            'modern' => array(
                'name'        => 'Moderno',
                'description' => 'Diseño limpio con gradientes sutiles, sombras suaves y animaciones elegantes',
                'style'       => 'modern',
                'colors'      => array(
                    'primary'          => '#6366f1',
                    'primary_light'    => '#818cf8',
                    'primary_dark'     => '#4f46e5',
                    'secondary'        => '#0ea5e9',
                    'accent'           => '#f59e0b',
                    'background'       => '#ffffff',
                    'background_alt'   => '#f8fafc',
                    'surface'          => '#ffffff',
                    'text'             => '#1e293b',
                    'text_muted'       => '#64748b',
                    'border'           => '#e2e8f0',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%)',
                    'cta'     => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
                    'overlay' => 'linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.7) 100%)',
                ),
                'shadows'     => array(
                    'sm'   => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                    'md'   => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                    'lg'   => '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                    'card' => '0 4px 20px rgba(99, 102, 241, 0.15)',
                ),
                'typography'  => array(
                    'font_family'    => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                    'heading_weight' => '700',
                    'body_weight'    => '400',
                ),
                'borders'     => array(
                    'radius_sm' => '0.375rem',
                    'radius_md' => '0.5rem',
                    'radius_lg' => '1rem',
                    'radius_xl' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
                'default_animations' => array(
                    'hero'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.8s' ),
                    'section'  => array( 'entrance' => 'fadeInUp', 'duration' => '0.6s', 'trigger' => 'scroll' ),
                    'card'     => array( 'entrance' => 'fadeInUp', 'duration' => '0.5s', 'hover' => 'grow' ),
                ),
            ),
            'corporate' => array(
                'name'        => 'Corporativo',
                'description' => 'Diseño profesional y serio con colores azules, ideal para empresas',
                'style'       => 'corporate',
                'colors'      => array(
                    'primary'          => '#1e40af',
                    'primary_light'    => '#3b82f6',
                    'primary_dark'     => '#1e3a8a',
                    'secondary'        => '#0f766e',
                    'accent'           => '#dc2626',
                    'background'       => '#ffffff',
                    'background_alt'   => '#f1f5f9',
                    'surface'          => '#ffffff',
                    'text'             => '#0f172a',
                    'text_muted'       => '#475569',
                    'border'           => '#cbd5e1',
                ),
                'gradients'   => array(
                    'hero'    => 'linear-gradient(135deg, #1e40af 0%, #0f766e 100%)',
                    'cta'     => 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)',
                ),
                'shadows'     => array(
                    'md'   => '0 4px 6px -1px rgba(0, 0, 0, 0.15)',
                    'card' => '0 2px 15px rgba(30, 64, 175, 0.1)',
                ),
                'typography'  => array(
                    'font_family'    => "'Roboto', -apple-system, sans-serif",
                    'heading_weight' => '700',
                ),
                'borders'     => array(
                    'radius_sm' => '0.25rem',
                    'radius_md' => '0.375rem',
                    'radius_lg' => '0.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '6rem',
                    'container_max'   => '1140px',
                ),
                'default_animations' => array(
                    'hero'    => array( 'entrance' => 'fadeIn', 'duration' => '1s' ),
                    'section' => array( 'entrance' => 'fadeInUp', 'duration' => '0.7s', 'trigger' => 'scroll' ),
                ),
            ),
            'minimal' => array(
                'name'        => 'Minimalista',
                'description' => 'Diseño ultra limpio con mucho espacio en blanco',
                'style'       => 'minimal',
                'colors'      => array(
                    'primary'     => '#18181b',
                    'secondary'   => '#71717a',
                    'accent'      => '#18181b',
                    'background'  => '#ffffff',
                    'text'        => '#18181b',
                    'text_muted'  => '#71717a',
                    'border'      => '#e4e4e7',
                ),
                'shadows'     => array(
                    'md'   => '0 1px 2px rgba(0,0,0,0.05)',
                    'card' => '0 1px 3px rgba(0,0,0,0.04)',
                ),
                'typography'  => array(
                    'font_family'    => "'DM Sans', sans-serif",
                    'heading_weight' => '600',
                ),
                'borders'     => array(
                    'radius_sm' => '0',
                    'radius_md' => '0',
                    'radius_lg' => '0',
                ),
                'spacing'     => array(
                    'section_padding' => '8rem',
                    'container_max'   => '960px',
                ),
            ),
            'nature' => array(
                'name'        => 'Natural',
                'description' => 'Verdes terrosos, ideal para ecología y sostenibilidad',
                'style'       => 'nature',
                'colors'      => array(
                    'primary'     => '#166534',
                    'secondary'   => '#854d0e',
                    'accent'      => '#059669',
                    'background'  => '#fefce8',
                    'text'        => '#1c1917',
                    'text_muted'  => '#57534e',
                    'border'      => '#d6d3d1',
                ),
                'typography'  => array(
                    'font_family' => "'Merriweather', Georgia, serif",
                ),
                'borders'     => array(
                    'radius_md' => '0.75rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1100px',
                ),
            ),
            'community' => array(
                'name'        => 'Comunitario',
                'description' => 'Cálido y acogedor, ideal para comunidades y asociaciones',
                'style'       => 'community',
                'colors'      => array(
                    'primary'     => '#7c3aed',
                    'secondary'   => '#ea580c',
                    'accent'      => '#0d9488',
                    'background'  => '#fffbeb',
                    'text'        => '#1c1917',
                    'text_muted'  => '#78716c',
                    'border'      => '#e7e5e4',
                ),
                'typography'  => array(
                    'font_family' => "'Nunito', sans-serif",
                ),
                'borders'     => array(
                    'radius_md' => '1rem',
                    'radius_lg' => '1.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '4rem',
                    'container_max'   => '1200px',
                ),
            ),
            'cooperative' => array(
                'name'        => 'Cooperativa',
                'description' => 'Rojo solidario y azul profesional para cooperativas',
                'style'       => 'cooperative',
                'colors'      => array(
                    'primary'     => '#dc2626',
                    'secondary'   => '#1d4ed8',
                    'accent'      => '#059669',
                    'background'  => '#ffffff',
                    'text'        => '#1f2937',
                    'text_muted'  => '#6b7280',
                    'border'      => '#e5e7eb',
                ),
                'typography'  => array(
                    'font_family' => "'Source Sans Pro', sans-serif",
                ),
                'borders'     => array(
                    'radius_md' => '0.5rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
            ),
            'eco' => array(
                'name'        => 'Ecológico',
                'description' => 'Verdes naturales para proyectos ecológicos',
                'style'       => 'eco',
                'colors'      => array(
                    'primary'     => '#15803d',
                    'secondary'   => '#0e7490',
                    'accent'      => '#ca8a04',
                    'background'  => '#f0fdf4',
                    'text'        => '#14532d',
                    'text_muted'  => '#166534',
                    'border'      => '#bbf7d0',
                ),
                'typography'  => array(
                    'font_family' => "'Open Sans', sans-serif",
                ),
                'borders'     => array(
                    'radius_md' => '0.75rem',
                ),
                'spacing'     => array(
                    'section_padding' => '4rem',
                    'container_max'   => '1100px',
                ),
            ),
            'fundraising' => array(
                'name'        => 'Crowdfunding',
                'description' => 'Verde y violeta, optimizado para donaciones',
                'style'       => 'fundraising',
                'colors'      => array(
                    'primary'     => '#059669',
                    'secondary'   => '#7c3aed',
                    'accent'      => '#f59e0b',
                    'background'  => '#ffffff',
                    'text'        => '#111827',
                    'text_muted'  => '#6b7280',
                    'border'      => '#e5e7eb',
                ),
                'typography'  => array(
                    'font_family' => "'Poppins', sans-serif",
                ),
                'borders'     => array(
                    'radius_md' => '1rem',
                ),
                'spacing'     => array(
                    'section_padding' => '5rem',
                    'container_max'   => '1200px',
                ),
            ),
        );
    }

    /**
     * Obtiene los estilos por defecto para elementos
     *
     * @return array
     */
    private function get_default_styles() {
        return array(
            'desktop'  => array(
                'padding'         => array(),
                'margin'          => array(),
                'backgroundColor' => '',
                'borderRadius'    => '',
            ),
            'tablet'   => array(),
            'mobile'   => array(),
            'advanced' => array(
                'customCSS' => '',
            ),
        );
    }

    /**
     * Mezcla estilos por defecto con estilos personalizados
     *
     * @param array $default Estilos por defecto.
     * @param array $custom  Estilos personalizados.
     * @return array
     */
    private function merge_styles( $default, $custom ) {
        $merged = $default;

        foreach ( $custom as $breakpoint => $styles ) {
            if ( is_array( $styles ) ) {
                if ( ! isset( $merged[ $breakpoint ] ) ) {
                    $merged[ $breakpoint ] = array();
                }
                $merged[ $breakpoint ] = array_merge( $merged[ $breakpoint ], $styles );
            } else {
                $merged[ $breakpoint ] = $styles;
            }
        }

        return $merged;
    }

    /**
     * Crea una página con estilos aplicados
     *
     * @param WP_REST_Request $request Request con datos.
     * @return WP_REST_Response
     */
    public function create_styled_page( $request ) {
        $title = $request->get_param( 'title' );
        $preset_name = $request->get_param( 'preset' ) ?: 'modern';
        $sections = $request->get_param( 'sections' ) ?: array();
        $context = $request->get_param( 'context' ) ?: array();
        $status = $request->get_param( 'status' ) ?: 'draft';

        // Obtener preset
        $preset_settings = $this->get_design_preset( $preset_name );
        if ( ! $preset_settings ) {
            return new WP_REST_Response( array(
                'error'            => 'Preset no encontrado: ' . $preset_name,
                'available_presets' => array_keys( $this->get_all_design_presets() ),
            ), 400 );
        }

        // Generar secciones con el preset aplicado
        $elements = array();
        foreach ( $sections as $section_type ) {
            $section = $this->create_styled_section( $section_type, $context, $preset_name );
            if ( $section ) {
                $elements[] = $section;
            }
        }

        // Crear la página
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array(
                'error' => $post_id->get_error_message(),
            ), 500 );
        }

        // Guardar datos VBP con preset
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $this->prepare_elements( $elements ),
            'settings' => array_merge(
                array(
                    'pageWidth'       => 1200,
                    'backgroundColor' => $preset_settings['backgroundColor'],
                    'design_preset'   => $preset_name,
                ),
                $preset_settings
            ),
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $title,
            'preset'   => $preset_name,
            'sections' => count( $elements ),
            'status'   => $status,
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
        ), 201 );
    }

    /**
     * Crea una sección con estilos del preset
     *
     * @param string $type    Tipo de sección.
     * @param array  $context Contexto para personalización.
     * @param string $preset  Nombre del preset.
     * @return array|null
     */
    private function create_styled_section( $type, $context, $preset ) {
        $section = $this->create_section( $type, $context );

        if ( ! $section ) {
            return null;
        }

        // Aplicar colores del preset a la sección
        $preset_data = $this->get_all_design_presets()[ $preset ] ?? null;
        if ( $preset_data && ! empty( $preset_data['colors'] ) ) {
            // Aplicar color primario a CTAs
            if ( ! empty( $section['children'] ) ) {
                $section['children'] = $this->apply_preset_colors_to_elements( $section['children'], $preset_data['colors'] );
            }
        }

        return $section;
    }

    /**
     * Aplica colores del preset a los elementos
     *
     * @param array $elements Elementos a modificar.
     * @param array $colors   Colores del preset.
     * @return array
     */
    private function apply_preset_colors_to_elements( $elements, $colors ) {
        foreach ( $elements as &$element ) {
            // Aplicar color primario a botones
            if ( isset( $element['type'] ) && 'button' === $element['type'] ) {
                if ( ! isset( $element['data']['backgroundColor'] ) ) {
                    $element['data']['backgroundColor'] = $colors['primary'];
                }
                if ( ! isset( $element['data']['color'] ) ) {
                    $element['data']['color'] = '#ffffff';
                }
            }

            // Procesar children recursivamente
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->apply_preset_colors_to_elements( $element['children'], $colors );
            }
        }

        return $elements;
    }
}
