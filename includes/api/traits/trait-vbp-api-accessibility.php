<?php
/**
 * Trait para Accesibilidad VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Accessibility {


    /**
     * Analiza accesibilidad de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_accessibility( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $issues = array();
        $score = 100;

        // Analizar problemas
        $this->check_accessibility_issues( $elements, $issues );

        // Calcular puntuación
        foreach ( $issues as $issue ) {
            switch ( $issue['severity'] ) {
                case 'error':
                    $score -= 10;
                    break;
                case 'warning':
                    $score -= 5;
                    break;
                case 'info':
                    $score -= 2;
                    break;
            }
        }

        $score = max( 0, $score );

        return new WP_REST_Response( array(
            'success' => true,
            'score'   => $score,
            'grade'   => $this->score_to_grade( $score ),
            'issues'  => $issues,
            'summary' => array(
                'errors'   => count( array_filter( $issues, fn( $i ) => $i['severity'] === 'error' ) ),
                'warnings' => count( array_filter( $issues, fn( $i ) => $i['severity'] === 'warning' ) ),
                'info'     => count( array_filter( $issues, fn( $i ) => $i['severity'] === 'info' ) ),
            ),
        ), 200 );
    }

    /**
     * Verifica problemas de accesibilidad
     *
     * @param array $elements Elementos.
     * @param array $issues   Array de problemas.
     * @param int   $heading_level Nivel de heading actual.
     */
    private function check_accessibility_issues( $elements, &$issues, $heading_level = 0 ) {
        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            $data = $element['data'] ?? array();
            $id = $element['id'] ?? '';

            // Imágenes sin alt
            if ( $type === 'image' && empty( $data['alt'] ) ) {
                $issues[] = array(
                    'id'       => 'img_no_alt_' . $id,
                    'block_id' => $id,
                    'type'     => 'missing_alt',
                    'severity' => 'error',
                    'message'  => 'Imagen sin texto alternativo.',
                    'wcag'     => '1.1.1',
                    'fix'      => 'Añadir atributo alt descriptivo.',
                );
            }

            // Contraste de colores (simplificado)
            if ( ! empty( $data['color'] ) && ! empty( $data['backgroundColor'] ) ) {
                $contrast = $this->calculate_contrast_ratio( $data['color'], $data['backgroundColor'] );
                if ( $contrast < 4.5 ) {
                    $issues[] = array(
                        'id'       => 'low_contrast_' . $id,
                        'block_id' => $id,
                        'type'     => 'low_contrast',
                        'severity' => 'warning',
                        'message'  => "Contraste bajo ({$contrast}:1). Mínimo recomendado: 4.5:1.",
                        'wcag'     => '1.4.3',
                        'fix'      => 'Aumentar contraste entre texto y fondo.',
                    );
                }
            }

            // Headings fuera de orden
            if ( $type === 'heading' ) {
                $level = $data['level'] ?? 2;
                if ( $level > $heading_level + 1 && $heading_level > 0 ) {
                    $issues[] = array(
                        'id'       => 'heading_skip_' . $id,
                        'block_id' => $id,
                        'type'     => 'heading_skip',
                        'severity' => 'warning',
                        'message'  => "Heading h{$level} salta niveles (esperado h" . ( $heading_level + 1 ) . " o menor).",
                        'wcag'     => '1.3.1',
                        'fix'      => 'Usar niveles de heading secuenciales.',
                    );
                }
                $heading_level = $level;
            }

            // Links sin texto
            if ( $type === 'button' || $type === 'link' ) {
                if ( empty( $data['text'] ) && empty( $data['ariaLabel'] ) ) {
                    $issues[] = array(
                        'id'       => 'link_no_text_' . $id,
                        'block_id' => $id,
                        'type'     => 'link_no_text',
                        'severity' => 'error',
                        'message'  => 'Enlace sin texto accesible.',
                        'wcag'     => '2.4.4',
                        'fix'      => 'Añadir texto o aria-label al enlace.',
                    );
                }
            }

            // Texto demasiado pequeño
            $font_size = $data['fontSize'] ?? $element['styles']['fontSize'] ?? null;
            if ( $font_size ) {
                $size_value = (int) $font_size;
                if ( $size_value > 0 && $size_value < 14 ) {
                    $issues[] = array(
                        'id'       => 'small_text_' . $id,
                        'block_id' => $id,
                        'type'     => 'small_text',
                        'severity' => 'info',
                        'message'  => "Texto pequeño ({$size_value}px). Considerar 14px mínimo.",
                        'wcag'     => '1.4.4',
                        'fix'      => 'Aumentar tamaño de fuente.',
                    );
                }
            }

            // Recursivo
            if ( ! empty( $element['children'] ) ) {
                $this->check_accessibility_issues( $element['children'], $issues, $heading_level );
            }
        }
    }

    /**
     * Convierte puntuación a grado
     *
     * @param int $score Puntuación.
     * @return string
     */
    private function score_to_grade( $score ) {
        if ( $score >= 90 ) return 'A';
        if ( $score >= 80 ) return 'B';
        if ( $score >= 70 ) return 'C';
        if ( $score >= 60 ) return 'D';
        return 'F';
    }

    /**
     * Corrige problemas de accesibilidad automáticamente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function fix_accessibility_issues( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $issue_ids = $request->get_param( 'issues' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $fixed = 0;

        // Aplicar correcciones automáticas
        $elements = $this->auto_fix_accessibility( $elements, $issue_ids, $fixed );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Se corrigieron {$fixed} problemas.",
            'fixed'   => $fixed,
        ), 200 );
    }

    /**
     * Aplica correcciones automáticas de accesibilidad
     *
     * @param array $elements  Elementos.
     * @param array $issue_ids IDs de problemas a corregir.
     * @param int   $fixed     Contador de correcciones.
     * @return array
     */
    private function auto_fix_accessibility( $elements, $issue_ids, &$fixed ) {
        foreach ( $elements as &$element ) {
            $id = $element['id'] ?? '';
            $type = $element['type'] ?? '';

            // Auto-generar alt para imágenes
            if ( $type === 'image' && empty( $element['data']['alt'] ) ) {
                if ( empty( $issue_ids ) || in_array( 'img_no_alt_' . $id, $issue_ids ) ) {
                    $src = $element['data']['src'] ?? '';
                    $filename = pathinfo( $src, PATHINFO_FILENAME );
                    $element['data']['alt'] = ucfirst( str_replace( array( '-', '_' ), ' ', $filename ) );
                    $fixed++;
                }
            }

            // Recursivo
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->auto_fix_accessibility( $element['children'], $issue_ids, $fixed );
            }
        }

        return $elements;
    }

    // =============================================
}
