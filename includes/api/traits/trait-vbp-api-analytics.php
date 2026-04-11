<?php
/**
 * Trait para análisis y estadísticas VBP
 *
 * Este trait contiene todos los métodos relacionados con
 * análisis SEO, accesibilidad y estadísticas de uso.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Analytics
 *
 * Contiene métodos para:
 * - Análisis SEO (analyze_page_seo, suggest_seo_improvements)
 * - Análisis de accesibilidad (analyze_page_accessibility)
 * - Estadísticas de uso (get_blocks_usage_stats, get_vbp_overview_stats)
 */
trait VBP_API_Analytics {

    // =========================================================================
    // ANÁLISIS SEO
    // =========================================================================

    /**
     * Analiza SEO de una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_page_seo( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $post = get_post( $page_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $page_id, '_flavor_vbp_data', true );
        $elements = $vbp_data['elements'] ?? array();

        $analysis = array(
            'score'       => 0,
            'max_score'   => 100,
            'issues'      => array(),
            'warnings'    => array(),
            'passed'      => array(),
            'suggestions' => array(),
        );

        // Extraer contenido de texto
        $all_text = $this->extract_text_from_elements( $elements );
        $word_count = str_word_count( $all_text );

        // 1. Título (20 puntos)
        $title = $post->post_title;
        $title_length = mb_strlen( $title );
        if ( $title_length >= 30 && $title_length <= 60 ) {
            $analysis['score'] += 20;
            $analysis['passed'][] = 'Título con longitud óptima (' . $title_length . ' caracteres)';
        } elseif ( $title_length > 0 ) {
            $analysis['score'] += 10;
            $analysis['warnings'][] = 'Título debería tener entre 30-60 caracteres (actual: ' . $title_length . ')';
        } else {
            $analysis['issues'][] = 'Falta título de página';
        }

        // 2. Meta descripción (15 puntos)
        $meta_desc = get_post_meta( $page_id, '_yoast_wpseo_metadesc', true )
            ?: get_post_meta( $page_id, '_flavor_seo_description', true );
        if ( ! empty( $meta_desc ) ) {
            $desc_length = mb_strlen( $meta_desc );
            if ( $desc_length >= 120 && $desc_length <= 160 ) {
                $analysis['score'] += 15;
                $analysis['passed'][] = 'Meta descripción con longitud óptima';
            } else {
                $analysis['score'] += 8;
                $analysis['warnings'][] = 'Meta descripción debería tener 120-160 caracteres';
            }
        } else {
            $analysis['issues'][] = 'Falta meta descripción';
            $analysis['suggestions'][] = $this->generate_meta_description( $all_text );
        }

        // 3. Estructura de encabezados (15 puntos)
        $headings = $this->extract_headings_from_elements( $elements );
        $has_h1 = ! empty( $headings['h1'] );
        $has_h2 = ! empty( $headings['h2'] );

        if ( $has_h1 && count( $headings['h1'] ) === 1 ) {
            $analysis['score'] += 8;
            $analysis['passed'][] = 'Un solo H1 detectado';
        } elseif ( $has_h1 ) {
            $analysis['score'] += 4;
            $analysis['warnings'][] = 'Múltiples H1 detectados (' . count( $headings['h1'] ) . ')';
        } else {
            $analysis['issues'][] = 'Falta encabezado H1';
        }

        if ( $has_h2 ) {
            $analysis['score'] += 7;
            $analysis['passed'][] = 'Encabezados H2 detectados (' . count( $headings['h2'] ) . ')';
        } else {
            $analysis['warnings'][] = 'No hay encabezados H2 para estructurar el contenido';
        }

        // 4. Contenido (20 puntos)
        if ( $word_count >= 300 ) {
            $analysis['score'] += 20;
            $analysis['passed'][] = 'Contenido suficiente (' . $word_count . ' palabras)';
        } elseif ( $word_count >= 150 ) {
            $analysis['score'] += 12;
            $analysis['warnings'][] = 'Contenido algo corto (' . $word_count . ' palabras, recomendado: 300+)';
        } else {
            $analysis['score'] += 5;
            $analysis['issues'][] = 'Contenido muy corto (' . $word_count . ' palabras)';
        }

        // 5. Imágenes con alt (15 puntos)
        $images = $this->extract_images_from_elements( $elements );
        $images_with_alt = array_filter( $images, function( $img ) {
            return ! empty( $img['alt'] );
        } );

        if ( count( $images ) === 0 ) {
            $analysis['warnings'][] = 'No hay imágenes en la página';
        } elseif ( count( $images_with_alt ) === count( $images ) ) {
            $analysis['score'] += 15;
            $analysis['passed'][] = 'Todas las imágenes tienen texto alternativo';
        } else {
            $missing = count( $images ) - count( $images_with_alt );
            $analysis['score'] += 8;
            $analysis['warnings'][] = $missing . ' imagen(es) sin texto alternativo';
        }

        // 6. Enlaces y CTAs (15 puntos)
        $links = $this->extract_links_from_elements( $elements );
        $cta_count = count( array_filter( $links, function( $link ) {
            return ! empty( $link['is_cta'] );
        } ) );

        if ( $cta_count > 0 ) {
            $analysis['score'] += 15;
            $analysis['passed'][] = 'CTAs detectados (' . $cta_count . ')';
        } else {
            $analysis['warnings'][] = 'No se detectaron llamadas a la acción (CTAs)';
        }

        // Calcular calificación
        $percentage = round( ( $analysis['score'] / $analysis['max_score'] ) * 100 );
        if ( $percentage >= 80 ) {
            $analysis['grade'] = 'A';
            $analysis['status'] = 'excellent';
        } elseif ( $percentage >= 60 ) {
            $analysis['grade'] = 'B';
            $analysis['status'] = 'good';
        } elseif ( $percentage >= 40 ) {
            $analysis['grade'] = 'C';
            $analysis['status'] = 'needs_improvement';
        } else {
            $analysis['grade'] = 'D';
            $analysis['status'] = 'poor';
        }

        $analysis['percentage'] = $percentage;

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'title'    => $post->post_title,
            'url'      => get_permalink( $page_id ),
            'analysis' => $analysis,
            'meta'     => array(
                'word_count'    => $word_count,
                'images_count'  => count( $images ),
                'links_count'   => count( $links ),
                'headings'      => $headings,
            ),
        ), 200 );
    }

    /**
     * Sugiere mejoras SEO para contenido
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function suggest_seo_improvements( $request ) {
        $content = sanitize_textarea_field( $request->get_param( 'content' ) );
        $keywords = $request->get_param( 'keywords' ) ?: array();
        $type = sanitize_key( $request->get_param( 'type' ) );

        $suggestions = array();
        $word_count = str_word_count( $content );

        // Sugerencias de título
        $suggestions['title'] = array(
            'current_length' => mb_strlen( $content ),
            'optimal_range'  => '30-60 caracteres',
            'suggestion'     => $this->generate_title_suggestion( $content, $keywords ),
        );

        // Sugerencias de meta descripción
        $suggestions['meta_description'] = array(
            'optimal_range' => '120-160 caracteres',
            'suggestion'    => $this->generate_meta_description( $content ),
        );

        // Sugerencias de keywords
        if ( ! empty( $keywords ) ) {
            $keyword_density = array();
            foreach ( $keywords as $keyword ) {
                $count = substr_count( strtolower( $content ), strtolower( $keyword ) );
                $density = $word_count > 0 ? round( ( $count / $word_count ) * 100, 2 ) : 0;
                $keyword_density[ $keyword ] = array(
                    'count'   => $count,
                    'density' => $density . '%',
                    'status'  => $density >= 1 && $density <= 3 ? 'optimal' : ( $density < 1 ? 'low' : 'high' ),
                );
            }
            $suggestions['keywords'] = $keyword_density;
        }

        // Sugerencias de estructura
        $suggestions['structure'] = array(
            'word_count'       => $word_count,
            'recommended_min'  => 300,
            'paragraphs'       => substr_count( $content, "\n\n" ) + 1,
            'has_lists'        => strpos( $content, '<ul>' ) !== false || strpos( $content, '<ol>' ) !== false,
        );

        return new WP_REST_Response( array(
            'success'     => true,
            'suggestions' => $suggestions,
            'content_preview' => mb_substr( $content, 0, 200 ) . '...',
        ), 200 );
    }

    // =========================================================================
    // HELPERS SEO
    // =========================================================================

    /**
     * Extrae texto de elementos VBP
     */
    private function extract_text_from_elements( $elements ) {
        $text = '';
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            $text .= ' ' . ( $data['titulo'] ?? '' );
            $text .= ' ' . ( $data['subtitulo'] ?? '' );
            $text .= ' ' . ( $data['contenido'] ?? '' );
            $text .= ' ' . ( $data['texto'] ?? '' );
            $text .= ' ' . ( $data['descripcion'] ?? '' );

            if ( ! empty( $element['children'] ) ) {
                $text .= ' ' . $this->extract_text_from_elements( $element['children'] );
            }
        }
        return strip_tags( $text );
    }

    /**
     * Extrae encabezados de elementos VBP
     */
    private function extract_headings_from_elements( $elements ) {
        $headings = array( 'h1' => array(), 'h2' => array(), 'h3' => array() );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            $data = $element['data'] ?? array();

            // Hero usualmente contiene H1
            if ( $type === 'hero' && ! empty( $data['titulo'] ) ) {
                $headings['h1'][] = $data['titulo'];
            }

            // Secciones con título son H2
            if ( in_array( $type, array( 'features', 'testimonials', 'pricing', 'faq', 'cta', 'team', 'stats' ), true ) ) {
                if ( ! empty( $data['titulo'] ) ) {
                    $headings['h2'][] = $data['titulo'];
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $child_headings = $this->extract_headings_from_elements( $element['children'] );
                foreach ( $child_headings as $level => $texts ) {
                    $headings[ $level ] = array_merge( $headings[ $level ], $texts );
                }
            }
        }

        return $headings;
    }

    /**
     * Extrae imágenes de elementos VBP
     */
    private function extract_images_from_elements( $elements ) {
        $images = array();

        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();

            if ( ! empty( $data['imagen'] ) ) {
                $images[] = array(
                    'src' => $data['imagen'],
                    'alt' => $data['imagen_alt'] ?? '',
                );
            }
            if ( ! empty( $data['imagen_fondo'] ) ) {
                $images[] = array(
                    'src' => $data['imagen_fondo'],
                    'alt' => $data['imagen_fondo_alt'] ?? '',
                );
            }

            if ( ! empty( $element['children'] ) ) {
                $images = array_merge( $images, $this->extract_images_from_elements( $element['children'] ) );
            }
        }

        return $images;
    }

    /**
     * Extrae enlaces de elementos VBP
     */
    private function extract_links_from_elements( $elements ) {
        $links = array();

        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();

            if ( ! empty( $data['boton_url'] ) ) {
                $links[] = array(
                    'url'    => $data['boton_url'],
                    'text'   => $data['boton_texto'] ?? '',
                    'is_cta' => true,
                );
            }
            if ( ! empty( $data['enlace'] ) ) {
                $links[] = array(
                    'url'    => $data['enlace'],
                    'text'   => $data['enlace_texto'] ?? '',
                    'is_cta' => false,
                );
            }

            if ( ! empty( $element['children'] ) ) {
                $links = array_merge( $links, $this->extract_links_from_elements( $element['children'] ) );
            }
        }

        return $links;
    }

    /**
     * Genera sugerencia de meta descripción
     */
    private function generate_meta_description( $text ) {
        $text = trim( preg_replace( '/\s+/', ' ', $text ) );
        if ( mb_strlen( $text ) > 160 ) {
            $text = mb_substr( $text, 0, 157 ) . '...';
        }
        return array(
            'type'  => 'meta_description',
            'value' => $text,
        );
    }

    /**
     * Genera sugerencia de título
     */
    private function generate_title_suggestion( $content, $keywords ) {
        $words = explode( ' ', trim( $content ) );
        $title = implode( ' ', array_slice( $words, 0, 8 ) );

        if ( ! empty( $keywords[0] ) && stripos( $title, $keywords[0] ) === false ) {
            $title = $keywords[0] . ' - ' . $title;
        }

        if ( mb_strlen( $title ) > 60 ) {
            $title = mb_substr( $title, 0, 57 ) . '...';
        }

        return $title;
    }

    // =========================================================================
    // ANÁLISIS DE ACCESIBILIDAD
    // =========================================================================

    /**
     * Analiza accesibilidad de una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_page_accessibility( $request ) {
        $page_id = (int) $request->get_param( 'id' );
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

        $analysis = array(
            'score'    => 0,
            'max_score'=> 100,
            'level'    => 'AA', // WCAG level objetivo
            'issues'   => array(),
            'warnings' => array(),
            'passed'   => array(),
        );

        // 1. Imágenes con alt (25 puntos)
        $images = $this->extract_images_from_elements( $elements );
        $images_without_alt = array_filter( $images, function( $img ) {
            return empty( $img['alt'] );
        } );

        if ( count( $images ) === 0 ) {
            $analysis['score'] += 25;
            $analysis['passed'][] = 'No hay imágenes que necesiten texto alternativo';
        } elseif ( count( $images_without_alt ) === 0 ) {
            $analysis['score'] += 25;
            $analysis['passed'][] = 'Todas las imágenes tienen texto alternativo';
        } else {
            $analysis['score'] += max( 0, 25 - ( count( $images_without_alt ) * 5 ) );
            $analysis['issues'][] = array(
                'type'    => 'missing_alt',
                'message' => count( $images_without_alt ) . ' imagen(es) sin texto alternativo',
                'wcag'    => '1.1.1',
                'impact'  => 'critical',
            );
        }

        // 2. Contraste de colores (25 puntos)
        $color_issues = $this->check_color_contrast( $elements, $settings );
        if ( empty( $color_issues ) ) {
            $analysis['score'] += 25;
            $analysis['passed'][] = 'Contraste de colores adecuado';
        } else {
            $analysis['score'] += max( 0, 25 - ( count( $color_issues ) * 8 ) );
            foreach ( $color_issues as $issue ) {
                $analysis['warnings'][] = $issue;
            }
        }

        // 3. Estructura de encabezados (20 puntos)
        $headings = $this->extract_headings_from_elements( $elements );
        $heading_issues = $this->check_heading_structure( $headings );

        if ( empty( $heading_issues ) ) {
            $analysis['score'] += 20;
            $analysis['passed'][] = 'Estructura de encabezados correcta';
        } else {
            $analysis['score'] += max( 0, 20 - ( count( $heading_issues ) * 5 ) );
            foreach ( $heading_issues as $issue ) {
                $analysis['issues'][] = $issue;
            }
        }

        // 4. Enlaces accesibles (15 puntos)
        $links = $this->extract_links_from_elements( $elements );
        $link_issues = $this->check_links_accessibility( $links );

        if ( empty( $link_issues ) ) {
            $analysis['score'] += 15;
            $analysis['passed'][] = 'Enlaces accesibles';
        } else {
            $analysis['score'] += max( 0, 15 - ( count( $link_issues ) * 5 ) );
            foreach ( $link_issues as $issue ) {
                $analysis['warnings'][] = $issue;
            }
        }

        // 5. Formularios accesibles (15 puntos)
        $forms = $this->extract_forms_from_elements( $elements );
        $form_issues = $this->check_forms_accessibility( $forms );

        if ( empty( $forms ) ) {
            $analysis['score'] += 15;
            $analysis['passed'][] = 'No hay formularios que revisar';
        } elseif ( empty( $form_issues ) ) {
            $analysis['score'] += 15;
            $analysis['passed'][] = 'Formularios accesibles';
        } else {
            $analysis['score'] += max( 0, 15 - ( count( $form_issues ) * 5 ) );
            foreach ( $form_issues as $issue ) {
                $analysis['issues'][] = $issue;
            }
        }

        // Calcular nivel de conformidad
        $percentage = round( ( $analysis['score'] / $analysis['max_score'] ) * 100 );
        if ( $percentage >= 90 && empty( $analysis['issues'] ) ) {
            $analysis['conformance'] = 'AAA';
            $analysis['status'] = 'excellent';
        } elseif ( $percentage >= 75 ) {
            $analysis['conformance'] = 'AA';
            $analysis['status'] = 'good';
        } elseif ( $percentage >= 50 ) {
            $analysis['conformance'] = 'A';
            $analysis['status'] = 'needs_improvement';
        } else {
            $analysis['conformance'] = 'non-conformant';
            $analysis['status'] = 'poor';
        }

        $analysis['percentage'] = $percentage;

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'title'    => $post->post_title,
            'analysis' => $analysis,
        ), 200 );
    }

    // =========================================================================
    // HELPERS ACCESIBILIDAD
    // =========================================================================

    /**
     * Verifica contraste de colores
     */
    private function check_color_contrast( $elements, $settings ) {
        $issues = array();
        $bg_color = $settings['backgroundColor'] ?? '#ffffff';

        $text_colors = array();
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            if ( ! empty( $data['color'] ) ) {
                $text_colors[] = $data['color'];
            }
            if ( ! empty( $data['color_texto'] ) ) {
                $text_colors[] = $data['color_texto'];
            }
        }

        foreach ( array_unique( $text_colors ) as $text_color ) {
            $ratio = $this->calculate_contrast_ratio( $text_color, $bg_color );
            if ( $ratio < 4.5 ) {
                $issues[] = array(
                    'type'    => 'low_contrast',
                    'message' => "Contraste insuficiente ({$ratio}:1) entre {$text_color} y {$bg_color}",
                    'wcag'    => '1.4.3',
                    'impact'  => 'serious',
                );
            }
        }

        return $issues;
    }

    /**
     * Calcula ratio de contraste entre dos colores
     */
    private function calculate_contrast_ratio( $color1, $color2 ) {
        $l1 = $this->get_relative_luminance( $color1 );
        $l2 = $this->get_relative_luminance( $color2 );

        $lighter = max( $l1, $l2 );
        $darker = min( $l1, $l2 );

        return round( ( $lighter + 0.05 ) / ( $darker + 0.05 ), 2 );
    }

    /**
     * Obtiene luminancia relativa de un color
     */
    private function get_relative_luminance( $hex ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
        $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
        $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Verifica estructura de encabezados
     */
    private function check_heading_structure( $headings ) {
        $issues = array();

        if ( empty( $headings['h1'] ) ) {
            $issues[] = array(
                'type'    => 'missing_h1',
                'message' => 'La página no tiene encabezado H1',
                'wcag'    => '1.3.1',
                'impact'  => 'serious',
            );
        } elseif ( count( $headings['h1'] ) > 1 ) {
            $issues[] = array(
                'type'    => 'multiple_h1',
                'message' => 'La página tiene múltiples H1 (' . count( $headings['h1'] ) . ')',
                'wcag'    => '1.3.1',
                'impact'  => 'moderate',
            );
        }

        return $issues;
    }

    /**
     * Verifica accesibilidad de enlaces
     */
    private function check_links_accessibility( $links ) {
        $issues = array();
        $generic_texts = array( 'click aquí', 'leer más', 'más info', 'aquí', 'click', 'link' );

        foreach ( $links as $link ) {
            $text = strtolower( trim( $link['text'] ?? '' ) );
            if ( empty( $text ) ) {
                $issues[] = array(
                    'type'    => 'empty_link_text',
                    'message' => 'Enlace sin texto descriptivo',
                    'wcag'    => '2.4.4',
                    'impact'  => 'serious',
                );
            } elseif ( in_array( $text, $generic_texts, true ) ) {
                $issues[] = array(
                    'type'    => 'generic_link_text',
                    'message' => "Texto de enlace genérico: \"{$text}\"",
                    'wcag'    => '2.4.4',
                    'impact'  => 'moderate',
                );
            }
        }

        return $issues;
    }

    /**
     * Extrae formularios de elementos
     */
    private function extract_forms_from_elements( $elements ) {
        $forms = array();
        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            if ( in_array( $type, array( 'contact', 'form', 'newsletter' ), true ) ) {
                $forms[] = $element;
            }
            if ( ! empty( $element['children'] ) ) {
                $forms = array_merge( $forms, $this->extract_forms_from_elements( $element['children'] ) );
            }
        }
        return $forms;
    }

    /**
     * Verifica accesibilidad de formularios
     */
    private function check_forms_accessibility( $forms ) {
        $issues = array();
        foreach ( $forms as $form ) {
            $data = $form['data'] ?? array();
            if ( empty( $data['fields'] ) ) {
                continue;
            }
            foreach ( $data['fields'] as $field ) {
                if ( empty( $field['label'] ) ) {
                    $issues[] = array(
                        'type'    => 'missing_label',
                        'message' => 'Campo de formulario sin etiqueta',
                        'wcag'    => '1.3.1',
                        'impact'  => 'critical',
                    );
                }
            }
        }
        return $issues;
    }

    // =========================================================================
    // ESTADÍSTICAS
    // =========================================================================

    /**
     * Obtiene estadísticas de uso de bloques
     *
     * @return WP_REST_Response
     */
    public function get_blocks_usage_stats() {
        $limit = flavor_safe_posts_limit( -1 );

        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => $limit,
        ) );

        $total_pages = wp_count_posts( 'flavor_landing' );
        $total_count = ( $total_pages->publish ?? 0 ) + ( $total_pages->draft ?? 0 );
        $is_partial  = $total_count > $limit;

        $block_usage = array();
        $total_blocks = 0;

        foreach ( $pages as $page ) {
            $vbp_data = get_post_meta( $page->ID, '_flavor_vbp_data', true );
            $elements = $vbp_data['elements'] ?? array();

            $this->count_blocks_recursive( $elements, $block_usage, $total_blocks );
        }

        arsort( $block_usage );

        $stats = array();
        foreach ( $block_usage as $type => $count ) {
            $stats[] = array(
                'type'       => $type,
                'count'      => $count,
                'percentage' => $total_blocks > 0 ? round( ( $count / $total_blocks ) * 100, 1 ) : 0,
            );
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'total_pages'   => count( $pages ),
            'total_blocks'  => $total_blocks,
            'unique_types'  => count( $block_usage ),
            'blocks'        => $stats,
            'top_5'         => array_slice( $stats, 0, 5 ),
            'is_partial'    => $is_partial,
            'pages_in_site' => $total_count,
        ), 200 );
    }

    /**
     * Cuenta bloques recursivamente
     */
    private function count_blocks_recursive( $elements, &$usage, &$total ) {
        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'unknown';
            if ( ! isset( $usage[ $type ] ) ) {
                $usage[ $type ] = 0;
            }
            $usage[ $type ]++;
            $total++;

            if ( ! empty( $element['children'] ) ) {
                $this->count_blocks_recursive( $element['children'], $usage, $total );
            }
        }
    }

    /**
     * Obtiene estadísticas generales de VBP
     *
     * @return WP_REST_Response
     */
    public function get_vbp_overview_stats() {
        global $wpdb;

        $pages_count = wp_count_posts( 'flavor_landing' );

        $pages_by_status = array(
            'publish' => (int) ( $pages_count->publish ?? 0 ),
            'draft'   => (int) ( $pages_count->draft ?? 0 ),
            'trash'   => (int) ( $pages_count->trash ?? 0 ),
        );

        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => 'any',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
        ) );

        $presets_usage = array();
        $total_elements = 0;
        $pages_with_seo = 0;

        foreach ( $pages as $page ) {
            $vbp_data = get_post_meta( $page->ID, '_flavor_vbp_data', true );
            $preset = $vbp_data['settings']['design_preset'] ?? 'custom';
            $elements = $vbp_data['elements'] ?? array();

            if ( ! isset( $presets_usage[ $preset ] ) ) {
                $presets_usage[ $preset ] = 0;
            }
            $presets_usage[ $preset ]++;

            $total_elements += $this->count_elements_recursive( $elements );

            $meta_desc = get_post_meta( $page->ID, '_yoast_wpseo_metadesc', true )
                ?: get_post_meta( $page->ID, '_flavor_seo_description', true );
            if ( ! empty( $meta_desc ) ) {
                $pages_with_seo++;
            }
        }

        arsort( $presets_usage );

        $total_pages = count( $pages );
        $avg_elements = $total_pages > 0 ? round( $total_elements / $total_pages, 1 ) : 0;

        return new WP_REST_Response( array(
            'success' => true,
            'stats'   => array(
                'total_pages'      => $total_pages,
                'pages_by_status'  => $pages_by_status,
                'total_elements'   => $total_elements,
                'avg_elements'     => $avg_elements,
                'presets_usage'    => $presets_usage,
                'top_preset'       => key( $presets_usage ) ?: 'none',
                'seo_coverage'     => array(
                    'with_meta'    => $pages_with_seo,
                    'without_meta' => $total_pages - $pages_with_seo,
                    'percentage'   => $total_pages > 0 ? round( ( $pages_with_seo / $total_pages ) * 100, 1 ) : 0,
                ),
            ),
            'generated_at' => current_time( 'c' ),
        ), 200 );
    }

    /**
     * Cuenta elementos recursivamente
     */
    private function count_elements_recursive( $elements ) {
        $count = count( $elements );
        foreach ( $elements as $element ) {
            if ( ! empty( $element['children'] ) ) {
                $count += $this->count_elements_recursive( $element['children'] );
            }
        }
        return $count;
    }
}
