<?php
/**
 * Trait para Sugerencias de Optimización VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_OptimizationSuggestions {

    /**
     * Analiza elementos para rendimiento
     *
     * @param array $elements Elementos.
     * @param array $metrics  Métricas.
     * @param int   $depth    Profundidad.
     */
    private function analyze_elements_performance( $elements, &$metrics, $depth ) {
        $metrics['nesting_depth'] = max( $metrics['nesting_depth'], $depth );

        foreach ( $elements as $element ) {
            $metrics['block_count']++;

            $type = $element['type'] ?? '';
            if ( in_array( $type, array( 'image', 'gallery', 'hero' ), true ) ) {
                $metrics['image_count']++;
            }
            if ( $type === 'video' ) {
                $metrics['video_count']++;
            }

            $data = $element['data'] ?? array();
            foreach ( array( 'text', 'content', 'title' ) as $field ) {
                if ( ! empty( $data[ $field ] ) ) {
                    $metrics['total_text_chars'] += strlen( $data[ $field ] );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $this->analyze_elements_performance( $element['children'], $metrics, $depth + 1 );
            }
        }
    }

    /**
     * Obtiene sugerencias de optimización
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_optimization_suggestions( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $suggestions = array();

        // Analizar imágenes sin lazy loading
        $this->check_lazy_loading( $elements, $suggestions );

        // Analizar bloques vacíos
        $this->check_empty_blocks( $elements, $suggestions );

        // Verificar fuentes
        $this->check_fonts_optimization( $elements, $suggestions );

        return new WP_REST_Response( array(
            'success'     => true,
            'suggestions' => $suggestions,
            'count'       => count( $suggestions ),
        ), 200 );
    }

    /**
     * Verifica lazy loading
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_lazy_loading( $elements, &$suggestions ) {
        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            if ( in_array( $type, array( 'image', 'gallery' ), true ) ) {
                $data = $element['data'] ?? array();
                if ( empty( $data['lazy'] ) ) {
                    $suggestions[] = array(
                        'id'       => 'lazy_' . ( $element['id'] ?? uniqid() ),
                        'type'     => 'lazy_loading',
                        'priority' => 'medium',
                        'message'  => "Imagen sin lazy loading: {$element['id']}",
                        'action'   => 'enable_lazy_loading',
                        'block_id' => $element['id'] ?? '',
                    );
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $this->check_lazy_loading( $element['children'], $suggestions );
            }
        }
    }

    /**
     * Verifica bloques vacíos
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_empty_blocks( $elements, &$suggestions ) {
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();

            if ( empty( $data ) && empty( $children ) ) {
                $suggestions[] = array(
                    'id'       => 'empty_' . ( $element['id'] ?? uniqid() ),
                    'type'     => 'empty_block',
                    'priority' => 'low',
                    'message'  => "Bloque vacío: {$element['id']}",
                    'action'   => 'remove_empty',
                    'block_id' => $element['id'] ?? '',
                );
            }

            if ( ! empty( $children ) ) {
                $this->check_empty_blocks( $children, $suggestions );
            }
        }
    }

    /**
     * Verifica optimización de fuentes
     *
     * @param array $elements    Elementos.
     * @param array $suggestions Sugerencias.
     */
    private function check_fonts_optimization( $elements, &$suggestions ) {
        $fonts_used = array();
        $this->extract_fonts_used( $elements, $fonts_used );

        if ( count( $fonts_used ) > 3 ) {
            $suggestions[] = array(
                'id'       => 'fonts_count',
                'type'     => 'fonts',
                'priority' => 'medium',
                'message'  => 'Demasiadas fuentes diferentes (' . count( $fonts_used ) . '). Considere reducir.',
                'action'   => 'reduce_fonts',
            );
        }
    }

    /**
     * Extrae fuentes usadas
     *
     * @param array $elements   Elementos.
     * @param array $fonts_used Fuentes usadas.
     */
    private function extract_fonts_used( $elements, &$fonts_used ) {
        foreach ( $elements as $element ) {
            $styles = $element['styles'] ?? array();
            if ( ! empty( $styles['fontFamily'] ) ) {
                $fonts_used[ $styles['fontFamily'] ] = true;
            }
            if ( ! empty( $element['children'] ) ) {
                $this->extract_fonts_used( $element['children'], $fonts_used );
            }
        }
    }

    /**
     * Aplica sugerencias de optimización
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_optimization_suggestions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $suggestion_ids = $request->get_param( 'suggestions' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $applied_count = 0;

        // Aplicar lazy loading a todas las imágenes si no hay filtro específico
        if ( empty( $suggestion_ids ) || in_array( 'lazy_loading', $suggestion_ids, true ) ) {
            $applied_count += $this->apply_lazy_loading_to_elements( $elements );
        }

        // Eliminar bloques vacíos
        if ( empty( $suggestion_ids ) || in_array( 'empty_block', $suggestion_ids, true ) ) {
            $original_count = count( $elements );
            $elements = $this->remove_empty_elements( $elements );
            $applied_count += $original_count - count( $elements );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Optimizaciones aplicadas: {$applied_count}",
            'applied' => $applied_count,
        ), 200 );
    }

    /**
     * Aplica lazy loading a elementos
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function apply_lazy_loading_to_elements( &$elements ) {
        $count = 0;
        foreach ( $elements as &$element ) {
            if ( in_array( $element['type'] ?? '', array( 'image', 'gallery' ), true ) ) {
                if ( ! isset( $element['data'] ) ) {
                    $element['data'] = array();
                }
                if ( empty( $element['data']['lazy'] ) ) {
                    $element['data']['lazy'] = true;
                    $count++;
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $count += $this->apply_lazy_loading_to_elements( $element['children'] );
            }
        }
        return $count;
    }

    /**
     * Elimina elementos vacíos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function remove_empty_elements( $elements ) {
        $filtered = array();
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();

            if ( ! empty( $data ) || ! empty( $children ) ) {
                if ( ! empty( $children ) ) {
                    $element['children'] = $this->remove_empty_elements( $children );
                }
                $filtered[] = $element;
            }
        }
        return $filtered;
    }

    /**
     * Comprime HTML de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compress_page_html( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $remove_comments = (bool) $request->get_param( 'remove_comments' );
        $minify_css = (bool) $request->get_param( 'minify_inline_css' );
        $minify_js = (bool) $request->get_param( 'minify_inline_js' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $compression_config = array(
            'remove_comments'   => $remove_comments,
            'minify_inline_css' => $minify_css,
            'minify_inline_js'  => $minify_js,
        );

        update_post_meta( $page_id, '_flavor_vbp_compression', $compression_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de compresión guardada.',
            'config'  => $compression_config,
        ), 200 );
    }

    /**
     * Detecta recursos pesados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_heavy_resources( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $threshold_kb = (int) $request->get_param( 'threshold_kb' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $heavy_resources = array();
        $this->find_heavy_resources( $elements, $heavy_resources, $threshold_kb * 1024 );

        return new WP_REST_Response( array(
            'success'         => true,
            'threshold_kb'    => $threshold_kb,
            'heavy_resources' => $heavy_resources,
            'count'           => count( $heavy_resources ),
        ), 200 );
    }

    /**
     * Busca recursos pesados
     *
     * @param array $elements        Elementos.
     * @param array $heavy_resources Recursos pesados.
     * @param int   $threshold       Umbral en bytes.
     */
    private function find_heavy_resources( $elements, &$heavy_resources, $threshold ) {
        foreach ( $elements as $element ) {
            $data = $element['data'] ?? array();

            // Verificar imágenes
            if ( ! empty( $data['image_url'] ) ) {
                $size = $this->estimate_resource_size( $data['image_url'] );
                if ( $size > $threshold ) {
                    $heavy_resources[] = array(
                        'type'     => 'image',
                        'url'      => $data['image_url'],
                        'size_kb'  => round( $size / 1024 ),
                        'block_id' => $element['id'] ?? '',
                    );
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $this->find_heavy_resources( $element['children'], $heavy_resources, $threshold );
            }
        }
    }

    /**
     * Estima tamaño de recurso
     *
     * @param string $url URL del recurso.
     * @return int
     */
    private function estimate_resource_size( $url ) {
        $attachment_id = attachment_url_to_postid( $url );
        if ( $attachment_id ) {
            $file_path = get_attached_file( $attachment_id );
            if ( $file_path && file_exists( $file_path ) ) {
                return filesize( $file_path );
            }
        }
        return 0;
    }

    /**
     * Optimiza fuentes de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function optimize_page_fonts( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $subset = $request->get_param( 'subset' );
        $display = $request->get_param( 'display' );
        $preload = (bool) $request->get_param( 'preload' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $font_config = array(
            'subset'  => $subset,
            'display' => $display,
            'preload' => $preload,
        );

        update_post_meta( $page_id, '_flavor_vbp_font_config', $font_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de fuentes guardada.',
            'config'  => $font_config,
        ), 200 );
    }

    /**
     * Configura lazy loading
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_lazy_loading( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $lazy_config = array(
            'images'      => (bool) $request->get_param( 'images' ),
            'iframes'     => (bool) $request->get_param( 'iframes' ),
            'videos'      => (bool) $request->get_param( 'videos' ),
            'threshold'   => sanitize_text_field( $request->get_param( 'threshold' ) ),
            'placeholder' => sanitize_text_field( $request->get_param( 'placeholder' ) ),
        );

        update_post_meta( $page_id, '_flavor_vbp_lazy_config', $lazy_config );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de lazy loading guardada.',
            'config'  => $lazy_config,
        ), 200 );
    }

    /**
     * Genera Critical CSS
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_critical_css( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $viewport_width = (int) $request->get_param( 'viewport_width' );
        $viewport_height = (int) $request->get_param( 'viewport_height' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Obtener CSS compilado de la página
        $compiled_css = get_post_meta( $page_id, '_flavor_vbp_compiled_css', true ) ?: '';

        // En un escenario real, aquí se usaría una herramienta como Critical o Penthouse
        // Por ahora, estimamos el CSS crítico basándonos en los primeros bloques
        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $critical_selectors = array();
        $above_fold_elements = array_slice( $elements, 0, 5 );
        foreach ( $above_fold_elements as $element ) {
            $critical_selectors[] = '#' . ( $element['id'] ?? '' );
            $critical_selectors[] = '.' . ( $element['type'] ?? '' );
        }

        $critical_css_meta = array(
            'viewport'           => array( $viewport_width, $viewport_height ),
            'generated_at'       => current_time( 'mysql' ),
            'critical_selectors' => $critical_selectors,
        );

        update_post_meta( $page_id, '_flavor_vbp_critical_css', $critical_css_meta );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Critical CSS generado.',
            'selectors' => count( $critical_selectors ),
        ), 200 );
    }

    /**
     * Auditoría completa de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function full_page_audit( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $categories = $request->get_param( 'categories' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $audit_results = array(
            'page_id'    => $page_id,
            'title'      => $post->post_title,
            'audited_at' => current_time( 'mysql' ),
            'categories' => array(),
            'overall'    => array(
                'score' => 0,
                'grade' => 'F',
            ),
        );

        $total_score = 0;
        $category_count = 0;

        // Performance
        if ( in_array( 'performance', $categories, true ) ) {
            $perf_response = $this->analyze_page_performance( $request );
            $perf_data = $perf_response->get_data();
            $audit_results['categories']['performance'] = array(
                'score'    => $perf_data['score'] ?? 0,
                'grade'    => $perf_data['grade'] ?? 'F',
                'warnings' => $perf_data['warnings'] ?? array(),
            );
            $total_score += $perf_data['score'] ?? 0;
            $category_count++;
        }

        // Accessibility
        if ( in_array( 'accessibility', $categories, true ) ) {
            $acc_response = $this->analyze_accessibility( $request );
            $acc_data = $acc_response->get_data();
            $audit_results['categories']['accessibility'] = array(
                'score'  => $acc_data['score'] ?? 0,
                'grade'  => $acc_data['grade'] ?? 'F',
                'issues' => count( $acc_data['issues'] ?? array() ),
            );
            $total_score += $acc_data['score'] ?? 0;
            $category_count++;
        }

        // SEO
        if ( in_array( 'seo', $categories, true ) ) {
            $seo_response = $this->analyze_page_seo( $request );
            $seo_data = $seo_response->get_data();
            $audit_results['categories']['seo'] = array(
                'score'  => $seo_data['score'] ?? 0,
                'grade'  => $seo_data['grade'] ?? 'F',
                'issues' => count( $seo_data['issues'] ?? array() ),
            );
            $total_score += $seo_data['score'] ?? 0;
            $category_count++;
        }

        // Best Practices
        if ( in_array( 'best-practices', $categories, true ) ) {
            $bp_response = $this->validate_blocks_structure( $request );
            $bp_data = $bp_response->get_data();
            $bp_score = ( $bp_data['valid'] ?? false ) ? 100 : 50;
            $audit_results['categories']['best-practices'] = array(
                'score'  => $bp_score,
                'grade'  => $this->score_to_grade( $bp_score ),
                'valid'  => $bp_data['valid'] ?? false,
                'issues' => count( $bp_data['issues'] ?? array() ),
            );
            $total_score += $bp_score;
            $category_count++;
        }

        if ( $category_count > 0 ) {
            $audit_results['overall']['score'] = round( $total_score / $category_count );
            $audit_results['overall']['grade'] = $this->score_to_grade( $audit_results['overall']['score'] );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'audit'   => $audit_results,
        ), 200 );
    }

    // =============================================
}
