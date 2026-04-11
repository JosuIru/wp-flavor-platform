<?php
/**
 * Trait para manipulación de bloques VBP
 *
 * Este trait contiene métodos para manipulación de bloques,
 * generación de CSS y análisis de rendimiento de páginas.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_BlockManipulation
 *
 * Contiene métodos para:
 * - Obtención de CSS (get_page_css)
 * - Análisis de rendimiento (get_page_performance)
 * - Generación de CSS de elementos
 */
trait VBP_API_BlockManipulation {

    /**
     * Obtiene el CSS generado de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_css( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $minify = (bool) $request->get_param( 'minify' );
        $include_base = (bool) $request->get_param( 'include_base' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $css = '';

        // Estilos base
        if ( $include_base ) {
            $css .= $this->get_vbp_base_styles();
        }

        // Estilos de la página
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );
        if ( ! empty( $styles ) ) {
            $css .= "\n/* Page Styles */\n";
            $css .= $this->convert_styles_to_css( $styles );
        }

        // Estilos de elementos
        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( ! empty( $elements ) ) {
            $element_css = $this->generate_elements_css( $elements );
            if ( $element_css ) {
                $css .= "\n/* Element Styles */\n";
                $css .= $element_css;
            }
        }

        // Minificar si se solicita
        if ( $minify ) {
            $css = $this->minify_css( $css );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'page_id'      => $page_id,
            'css'          => $css,
            'size'         => strlen( $css ),
            'size_kb'      => round( strlen( $css ) / 1024, 2 ),
            'minified'     => $minify,
            'include_base' => $include_base,
        ), 200 );
    }

    /**
     * Genera CSS de elementos recursivamente
     *
     * @param array  $elements Elementos VBP.
     * @param string $parent_selector Selector padre.
     * @return string
     */
    private function generate_elements_css( $elements, $parent_selector = '' ) {
        $css = '';

        foreach ( $elements as $index => $element ) {
            $element_id = $element['id'] ?? "el_{$index}";
            $type = $element['type'] ?? 'div';
            $props = $element['props'] ?? array();
            $selector = $parent_selector ? "{$parent_selector} .{$element_id}" : ".{$element_id}";

            $styles = array();

            // Convertir props a CSS
            if ( ! empty( $props['backgroundColor'] ) ) {
                $styles[] = "background-color: {$props['backgroundColor']}";
            }
            if ( ! empty( $props['color'] ) ) {
                $styles[] = "color: {$props['color']}";
            }
            if ( ! empty( $props['padding'] ) ) {
                $styles[] = "padding: {$props['padding']}";
            }
            if ( ! empty( $props['margin'] ) ) {
                $styles[] = "margin: {$props['margin']}";
            }
            if ( ! empty( $props['fontSize'] ) ) {
                $styles[] = "font-size: {$props['fontSize']}";
            }
            if ( ! empty( $props['fontWeight'] ) ) {
                $styles[] = "font-weight: {$props['fontWeight']}";
            }
            if ( ! empty( $props['textAlign'] ) || ! empty( $props['align'] ) ) {
                $align = $props['textAlign'] ?? $props['align'];
                $styles[] = "text-align: {$align}";
            }
            if ( ! empty( $props['borderRadius'] ) ) {
                $styles[] = "border-radius: {$props['borderRadius']}";
            }
            if ( ! empty( $props['boxShadow'] ) ) {
                $styles[] = "box-shadow: {$props['boxShadow']}";
            }

            if ( ! empty( $styles ) ) {
                $css .= "{$selector} { " . implode( '; ', $styles ) . "; }\n";
            }

            // Procesar children
            if ( ! empty( $element['children'] ) ) {
                $css .= $this->generate_elements_css( $element['children'], $selector );
            }
        }

        return $css;
    }

    /**
     * Minifica CSS
     *
     * @param string $css CSS a minificar.
     * @return string
     */
    private function minify_css( $css ) {
        // Eliminar comentarios
        $css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

        // Eliminar espacios en blanco
        $css = preg_replace( '/\s+/', ' ', $css );

        // Eliminar espacios alrededor de caracteres
        $css = preg_replace( '/\s*([\{\}\:\;\,])\s*/', '$1', $css );

        // Eliminar punto y coma antes de llave
        $css = str_replace( ';}', '}', $css );

        return trim( $css );
    }

    /**
     * Obtiene estadísticas de rendimiento de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_performance( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true ) ?: array();
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true ) ?: array();

        // Calcular métricas
        $block_count = $this->count_total_blocks( $elements );
        $image_count = $this->count_images_in_blocks( $elements );
        $text_length = $this->calculate_text_length( $elements );
        $max_depth = $this->calculate_max_depth( $elements );

        // Generar HTML y CSS para calcular tamaños
        $html = $this->render_elements_to_html( $elements );
        $css = $this->get_vbp_base_styles() . $this->convert_styles_to_css( $styles );

        $html_size = strlen( $html );
        $css_size = strlen( $css );
        $total_size = $html_size + $css_size;

        // Calcular puntuación de rendimiento
        $performance_score = 100;
        $issues = array();

        // Penalizar por muchos bloques
        if ( $block_count > 50 ) {
            $performance_score -= 10;
            $issues[] = array(
                'type'    => 'warning',
                'message' => "Muchos bloques ({$block_count}). Considerar simplificar.",
            );
        }

        // Penalizar por profundidad excesiva
        if ( $max_depth > 8 ) {
            $performance_score -= 15;
            $issues[] = array(
                'type'    => 'warning',
                'message' => "Anidación muy profunda ({$max_depth} niveles).",
            );
        }

        // Penalizar por muchas imágenes sin lazy loading info
        if ( $image_count > 10 ) {
            $performance_score -= 5;
            $issues[] = array(
                'type'    => 'info',
                'message' => "Muchas imágenes ({$image_count}). Verificar lazy loading.",
            );
        }

        // Penalizar por tamaño grande
        if ( $total_size > 100000 ) {
            $performance_score -= 10;
            $issues[] = array(
                'type'    => 'warning',
                'message' => 'Tamaño de página grande (' . round( $total_size / 1024, 1 ) . ' KB).',
            );
        }

        $performance_score = max( 0, $performance_score );

        // Determinar grade
        $grade = 'A';
        if ( $performance_score < 90 ) $grade = 'B';
        if ( $performance_score < 75 ) $grade = 'C';
        if ( $performance_score < 60 ) $grade = 'D';
        if ( $performance_score < 40 ) $grade = 'F';

        return new WP_REST_Response( array(
            'success'     => true,
            'page_id'     => $page_id,
            'score'       => $performance_score,
            'grade'       => $grade,
            'metrics'     => array(
                'block_count'   => $block_count,
                'image_count'   => $image_count,
                'text_length'   => $text_length,
                'max_depth'     => $max_depth,
                'html_size'     => $html_size,
                'html_size_kb'  => round( $html_size / 1024, 2 ),
                'css_size'      => $css_size,
                'css_size_kb'   => round( $css_size / 1024, 2 ),
                'total_size'    => $total_size,
                'total_size_kb' => round( $total_size / 1024, 2 ),
            ),
            'issues'      => $issues,
            'suggestions' => $this->get_performance_suggestions( $block_count, $max_depth, $image_count, $total_size ),
        ), 200 );
    }

    /**
     * Cuenta imágenes en bloques
     *
     * @param array $elements Elementos VBP.
     * @return int
     */
    private function count_images_in_blocks( $elements ) {
        $count = 0;

        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === 'image' ) {
                $count++;
            }

            $props = $element['props'] ?? array();
            if ( ! empty( $props['backgroundImage'] ) || ! empty( $props['src'] ) ) {
                $count++;
            }

            if ( ! empty( $element['children'] ) ) {
                $count += $this->count_images_in_blocks( $element['children'] );
            }
        }

        return $count;
    }

    /**
     * Calcula longitud total de texto
     *
     * @param array $elements Elementos VBP.
     * @return int
     */
    private function calculate_text_length( $elements ) {
        $length = 0;

        foreach ( $elements as $element ) {
            $props = $element['props'] ?? array();

            foreach ( array( 'text', 'content', 'title', 'subtitle', 'description' ) as $prop ) {
                if ( ! empty( $props[ $prop ] ) && is_string( $props[ $prop ] ) ) {
                    $length += strlen( $props[ $prop ] );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $length += $this->calculate_text_length( $element['children'] );
            }
        }

        return $length;
    }

    /**
     * Calcula profundidad máxima de anidación
     *
     * @param array $elements Elementos VBP.
     * @param int   $current_depth Profundidad actual.
     * @return int
     */
    private function calculate_max_depth( $elements, $current_depth = 1 ) {
        $max = $current_depth;

        foreach ( $elements as $element ) {
            if ( ! empty( $element['children'] ) ) {
                $child_depth = $this->calculate_max_depth( $element['children'], $current_depth + 1 );
                if ( $child_depth > $max ) {
                    $max = $child_depth;
                }
            }
        }

        return $max;
    }

    /**
     * Genera sugerencias de rendimiento
     *
     * @param int $blocks Cantidad de bloques.
     * @param int $depth Profundidad máxima.
     * @param int $images Cantidad de imágenes.
     * @param int $size Tamaño total.
     * @return array
     */
    private function get_performance_suggestions( $blocks, $depth, $images, $size ) {
        $suggestions = array();

        if ( $blocks > 30 ) {
            $suggestions[] = 'Considerar dividir la página en secciones más pequeñas.';
        }

        if ( $depth > 6 ) {
            $suggestions[] = 'Reducir la anidación de bloques para mejorar el rendimiento.';
        }

        if ( $images > 5 ) {
            $suggestions[] = 'Asegurar que las imágenes usan lazy loading y están optimizadas.';
        }

        if ( $size > 50000 ) {
            $suggestions[] = 'Considerar minificar el HTML/CSS para reducir el tamaño.';
        }

        if ( empty( $suggestions ) ) {
            $suggestions[] = '¡Buen trabajo! La página está optimizada.';
        }

        return $suggestions;
    }
}
