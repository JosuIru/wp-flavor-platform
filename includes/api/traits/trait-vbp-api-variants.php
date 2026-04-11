<?php
/**
 * Trait para A/B Testing y variantes VBP
 *
 * Este trait contiene todos los métodos relacionados con
 * creación y gestión de variantes de páginas para A/B testing.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Variants
 *
 * Contiene métodos para:
 * - Creación de variantes de páginas (create_page_variant)
 * - Variantes específicas (hero, CTA, colores, layout)
 * - Obtención de variantes (get_page_variants)
 * - Comparación de rendimiento (compare_variants_performance)
 */
trait VBP_API_Variants {

    /**
     * Crea una variante de página para A/B testing
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_page_variant( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $variant_type = sanitize_key( $request->get_param( 'variant_type' ) );
        $variant_name = sanitize_text_field( $request->get_param( 'variant_name' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $page_id, '_flavor_vbp_data', true );
        $elements = $vbp_data['elements'] ?? array();
        $settings = $vbp_data['settings'] ?? array();

        // Determinar nombre de variante
        $existing_variants = get_post_meta( $page_id, '_flavor_ab_variants', true ) ?: array();
        $variant_letter = chr( 66 + count( $existing_variants ) ); // B, C, D...
        $variant_name = $variant_name ?: "Variante {$variant_letter}";

        // Aplicar variaciones según tipo
        switch ( $variant_type ) {
            case 'hero':
                $elements = $this->create_hero_variant( $elements );
                break;

            case 'cta':
                $elements = $this->create_cta_variant( $elements );
                break;

            case 'colors':
                $settings = $this->create_colors_variant( $settings );
                break;

            case 'layout':
                $elements = $this->create_layout_variant( $elements );
                break;

            case 'copy':
            default:
                // Copia exacta, no modificar
                break;
        }

        // Regenerar IDs
        $elements = $this->regenerate_element_ids( $elements );

        // Crear nueva página
        $variant_title = $post->post_title . ' - ' . $variant_name;
        $variant_id = wp_insert_post( array(
            'post_title'  => $variant_title,
            'post_type'   => 'flavor_landing',
            'post_status' => 'draft',
        ) );

        if ( is_wp_error( $variant_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $variant_id->get_error_message(),
            ), 500 );
        }

        // Guardar datos VBP
        $new_vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elements,
            'settings' => $settings,
        );

        update_post_meta( $variant_id, '_flavor_vbp_data', $new_vbp_data );
        update_post_meta( $variant_id, '_flavor_vbp_version', '2.0.15' );
        update_post_meta( $variant_id, '_flavor_ab_original', $page_id );
        update_post_meta( $variant_id, '_flavor_ab_variant_type', $variant_type );
        update_post_meta( $variant_id, '_flavor_ab_variant_name', $variant_name );

        // Registrar en página original
        $existing_variants[] = array(
            'id'   => $variant_id,
            'name' => $variant_name,
            'type' => $variant_type,
            'created_at' => current_time( 'c' ),
        );
        update_post_meta( $page_id, '_flavor_ab_variants', $existing_variants );

        return new WP_REST_Response( array(
            'success'      => true,
            'variant_id'   => $variant_id,
            'variant_name' => $variant_name,
            'variant_type' => $variant_type,
            'original_id'  => $page_id,
            'edit_url'     => admin_url( "admin.php?page=vbp-editor&post_id={$variant_id}" ),
            'view_url'     => get_permalink( $variant_id ),
        ), 201 );
    }

    /**
     * Crea variante de hero
     *
     * @param array $elements Elementos de la página.
     * @return array
     */
    private function create_hero_variant( $elements ) {
        foreach ( $elements as &$element ) {
            if ( $element['type'] === 'hero' ) {
                $data = &$element['data'];
                // Intercambiar orden de botones
                if ( ! empty( $data['boton_texto'] ) && ! empty( $data['boton_2_texto'] ) ) {
                    $temp = $data['boton_texto'];
                    $data['boton_texto'] = $data['boton_2_texto'];
                    $data['boton_2_texto'] = $temp;

                    $temp_url = $data['boton_url'] ?? '';
                    $data['boton_url'] = $data['boton_2_url'] ?? '';
                    $data['boton_2_url'] = $temp_url;
                }
                break;
            }
        }
        return $elements;
    }

    /**
     * Crea variante de CTAs
     *
     * @param array $elements Elementos de la página.
     * @return array
     */
    private function create_cta_variant( $elements ) {
        $cta_texts = array(
            'Empezar ahora', 'Comenzar gratis', 'Prueba gratuita',
            'Únete ya', 'Descubre más', 'Solicitar demo',
        );

        foreach ( $elements as &$element ) {
            if ( ! empty( $element['data']['boton_texto'] ) ) {
                $current = $element['data']['boton_texto'];
                // Seleccionar texto diferente
                $alternatives = array_filter( $cta_texts, function( $t ) use ( $current ) {
                    return strtolower( $t ) !== strtolower( $current );
                } );
                if ( ! empty( $alternatives ) ) {
                    $element['data']['boton_texto'] = $alternatives[ array_rand( $alternatives ) ];
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->create_cta_variant( $element['children'] );
            }
        }

        return $elements;
    }

    /**
     * Crea variante de colores
     *
     * @param array $settings Configuración de la página.
     * @return array
     */
    private function create_colors_variant( $settings ) {
        $color_schemes = array(
            array( 'primary' => '#2563eb', 'secondary' => '#7c3aed' ),
            array( 'primary' => '#059669', 'secondary' => '#0d9488' ),
            array( 'primary' => '#dc2626', 'secondary' => '#ea580c' ),
            array( 'primary' => '#7c3aed', 'secondary' => '#db2777' ),
        );

        $scheme = $color_schemes[ array_rand( $color_schemes ) ];

        if ( ! empty( $settings['colors'] ) ) {
            $settings['colors']['primary'] = $scheme['primary'];
            $settings['colors']['secondary'] = $scheme['secondary'];
        } else {
            $settings['colors'] = $scheme;
        }

        return $settings;
    }

    /**
     * Crea variante de layout
     *
     * @param array $elements Elementos de la página.
     * @return array
     */
    private function create_layout_variant( $elements ) {
        // Intercambiar orden de secciones (excepto hero)
        $hero = null;
        $rest = array();

        foreach ( $elements as $element ) {
            if ( $element['type'] === 'hero' ) {
                $hero = $element;
            } else {
                $rest[] = $element;
            }
        }

        // Invertir orden de secciones
        $rest = array_reverse( $rest );

        // Reconstruir con hero al inicio
        $new_elements = array();
        if ( $hero ) {
            $new_elements[] = $hero;
        }
        $new_elements = array_merge( $new_elements, $rest );

        return $new_elements;
    }

    /**
     * Obtiene variantes de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_variants( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $post = get_post( $page_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        $variants = get_post_meta( $page_id, '_flavor_ab_variants', true ) ?: array();

        // Enriquecer datos de variantes
        $enriched_variants = array();
        foreach ( $variants as $variant ) {
            $variant_post = get_post( $variant['id'] );
            if ( $variant_post ) {
                $enriched_variants[] = array_merge( $variant, array(
                    'title'    => $variant_post->post_title,
                    'status'   => $variant_post->post_status,
                    'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$variant['id']}" ),
                    'view_url' => get_permalink( $variant['id'] ),
                ) );
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'original_id'    => $page_id,
            'original_title' => $post->post_title,
            'variants_count' => count( $enriched_variants ),
            'variants'       => $enriched_variants,
        ), 200 );
    }

    /**
     * Compara rendimiento de variantes (placeholder)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_variants_performance( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $variants = get_post_meta( $page_id, '_flavor_ab_variants', true ) ?: array();

        // Datos simulados - en producción integrar con analytics
        $comparison = array(
            'original' => array(
                'id'          => $page_id,
                'views'       => 0,
                'conversions' => 0,
                'rate'        => '0%',
            ),
        );

        foreach ( $variants as $variant ) {
            $comparison[ 'variant_' . $variant['id'] ] = array(
                'id'          => $variant['id'],
                'name'        => $variant['name'],
                'type'        => $variant['type'],
                'views'       => 0,
                'conversions' => 0,
                'rate'        => '0%',
            );
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Datos de analytics no disponibles. Integrar con sistema de tracking.',
            'comparison' => $comparison,
            'recommendation' => 'Instalar tracking para medir rendimiento de variantes.',
        ), 200 );
    }
}
