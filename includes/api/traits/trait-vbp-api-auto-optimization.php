<?php
/**
 * Trait para Auto-Optimización VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_AutoOptimization {

    /**
     * Detecta elementos duplicados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_duplicate_elements( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $similarity_threshold = (int) $request->get_param( 'similarity_threshold' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $duplicates = array();
        $processed_pairs = array();

        for ( $i = 0; $i < count( $elements ); $i++ ) {
            for ( $j = $i + 1; $j < count( $elements ); $j++ ) {
                $pair_key = $elements[ $i ]['id'] . '_' . $elements[ $j ]['id'];
                if ( in_array( $pair_key, $processed_pairs, true ) ) {
                    continue;
                }

                $similarity = $this->calculate_element_similarity( $elements[ $i ], $elements[ $j ] );

                if ( $similarity >= $similarity_threshold ) {
                    $duplicates[] = array(
                        'element_a'  => $elements[ $i ]['id'],
                        'element_b'  => $elements[ $j ]['id'],
                        'type'       => $elements[ $i ]['type'],
                        'similarity' => $similarity,
                    );
                }

                $processed_pairs[] = $pair_key;
            }
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'duplicates' => $duplicates,
            'count'      => count( $duplicates ),
            'threshold'  => $similarity_threshold,
        ), 200 );
    }

    /**
     * Calcula similitud entre elementos
     *
     * @param array $element_a Primer elemento.
     * @param array $element_b Segundo elemento.
     * @return int Porcentaje de similitud.
     */
    private function calculate_element_similarity( $element_a, $element_b ) {
        if ( $element_a['type'] !== $element_b['type'] ) {
            return 0;
        }

        $data_a = $element_a['data'] ?? array();
        $data_b = $element_b['data'] ?? array();

        $all_keys = array_unique( array_merge( array_keys( $data_a ), array_keys( $data_b ) ) );
        $matching_keys = 0;

        foreach ( $all_keys as $key ) {
            if ( isset( $data_a[ $key ] ) && isset( $data_b[ $key ] ) && $data_a[ $key ] === $data_b[ $key ] ) {
                $matching_keys++;
            }
        }

        if ( count( $all_keys ) === 0 ) {
            return 100;
        }

        return (int) ( ( $matching_keys / count( $all_keys ) ) * 100 );
    }

    /**
     * Limpia estilos no usados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function cleanup_unused_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $styles = json_decode( get_post_meta( $page_id, '_flavor_vbp_styles', true ), true ) ?: array();

        // Obtener IDs de elementos existentes
        $element_ids = $this->collect_element_ids( $elements );

        // Encontrar estilos huérfanos
        $orphan_styles = array();
        foreach ( $styles as $selector => $style_data ) {
            // Extraer ID del selector
            preg_match( '/el_[a-z0-9]+/', $selector, $matches );
            if ( $matches && ! in_array( $matches[0], $element_ids, true ) ) {
                $orphan_styles[] = $selector;
            }
        }

        $cleaned_count = count( $orphan_styles );

        if ( ! $dry_run && $cleaned_count > 0 ) {
            foreach ( $orphan_styles as $selector ) {
                unset( $styles[ $selector ] );
            }
            update_post_meta( $page_id, '_flavor_vbp_styles', wp_json_encode( $styles ) );
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'dry_run'       => $dry_run,
            'orphan_styles' => $orphan_styles,
            'cleaned_count' => $cleaned_count,
            'message'       => $dry_run ? 'Vista previa de limpieza.' : $cleaned_count . ' estilos eliminados.',
        ), 200 );
    }

    /**
     * Recoge IDs de elementos recursivamente
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function collect_element_ids( $elements ) {
        $ids = array();
        foreach ( $elements as $element ) {
            if ( isset( $element['id'] ) ) {
                $ids[] = $element['id'];
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $ids = array_merge( $ids, $this->collect_element_ids( $element['children'] ) );
            }
        }
        return $ids;
    }

    /**
     * Comprime elementos de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compress_page_elements( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $remove_empty = (bool) $request->get_param( 'remove_empty' );
        $merge_text = (bool) $request->get_param( 'merge_text' );
        $minify_inline_css = (bool) $request->get_param( 'minify_inline_css' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $original_count = count( $elements );

        $stats = array(
            'removed_empty'  => 0,
            'merged_text'    => 0,
            'minified_css'   => 0,
        );

        // Eliminar elementos vacíos
        if ( $remove_empty ) {
            $elements = array_filter( $elements, function( $el ) use ( &$stats ) {
                $has_content = ! empty( $el['data']['content'] ) ||
                              ! empty( $el['data']['text'] ) ||
                              ! empty( $el['data']['title'] ) ||
                              ! empty( $el['data']['image'] ) ||
                              ! empty( $el['children'] );
                if ( ! $has_content ) {
                    $stats['removed_empty']++;
                }
                return $has_content;
            } );
            $elements = array_values( $elements );
        }

        // Minificar CSS inline
        if ( $minify_inline_css ) {
            foreach ( $elements as &$element ) {
                if ( isset( $element['styles']['customCss'] ) ) {
                    $original_css = $element['styles']['customCss'];
                    $minified_css = preg_replace( '/\s+/', ' ', $original_css );
                    $minified_css = preg_replace( '/\s*([{};:,])\s*/', '$1', $minified_css );
                    if ( strlen( $minified_css ) < strlen( $original_css ) ) {
                        $element['styles']['customCss'] = $minified_css;
                        $stats['minified_css']++;
                    }
                }
            }
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'original_count' => $original_count,
            'final_count'    => count( $elements ),
            'stats'          => $stats,
            'message'        => 'Página comprimida.',
        ), 200 );
    }

    /**
     * Configura prefetch de recursos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_resource_prefetch( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dns_prefetch_urls = $request->get_param( 'dns_prefetch' ) ?: array();
        $preconnect_urls = $request->get_param( 'preconnect' ) ?: array();
        $prefetch_urls = $request->get_param( 'prefetch' ) ?: array();
        $preload_urls = $request->get_param( 'preload' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $prefetch_config = array(
            'dns_prefetch' => array_map( 'esc_url_raw', $dns_prefetch_urls ),
            'preconnect'   => array_map( 'esc_url_raw', $preconnect_urls ),
            'prefetch'     => array_map( 'esc_url_raw', $prefetch_urls ),
            'preload'      => array_map( 'esc_url_raw', $preload_urls ),
        );

        update_post_meta( $page_id, '_flavor_vbp_prefetch_config', $prefetch_config );

        return new WP_REST_Response( array(
            'success' => true,
            'config'  => $prefetch_config,
            'message' => 'Configuración de prefetch guardada.',
        ), 200 );
    }

    /**
     * Análisis de rendimiento detallado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_page_performance_detailed( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_recommendations = (bool) $request->get_param( 'include_recommendations' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        // Métricas
        $metrics = array(
            'element_count'    => count( $elements ),
            'nested_depth'     => $this->calculate_max_depth( $elements ),
            'image_count'      => count( $this->extract_media_ids_from_elements( $elements ) ),
            'styles_size'      => strlen( $styles ?? '' ),
            'estimated_dom'    => $this->estimate_dom_nodes( $elements ),
            'has_lazy_loading' => (bool) get_post_meta( $page_id, '_flavor_vbp_lazy_loading', true ),
            'has_critical_css' => (bool) get_post_meta( $page_id, '_flavor_vbp_critical_css', true ),
        );

        // Score
        $score = 100;
        if ( $metrics['element_count'] > 100 ) {
            $score -= 10;
        }
        if ( $metrics['nested_depth'] > 10 ) {
            $score -= 15;
        }
        if ( $metrics['image_count'] > 20 ) {
            $score -= 10;
        }
        if ( $metrics['estimated_dom'] > 1500 ) {
            $score -= 20;
        }
        if ( ! $metrics['has_lazy_loading'] ) {
            $score -= 10;
        }
        if ( ! $metrics['has_critical_css'] ) {
            $score -= 5;
        }

        $metrics['performance_score'] = max( 0, $score );

        // Recomendaciones
        $recommendations = array();
        if ( $include_recommendations ) {
            if ( $metrics['element_count'] > 100 ) {
                $recommendations[] = array(
                    'type'    => 'warning',
                    'message' => 'Considera reducir el número de elementos.',
                    'action'  => 'optimize/compress',
                );
            }
            if ( ! $metrics['has_lazy_loading'] ) {
                $recommendations[] = array(
                    'type'    => 'suggestion',
                    'message' => 'Activar lazy loading para imágenes.',
                    'action'  => 'optimize/lazy-loading',
                );
            }
            if ( ! $metrics['has_critical_css'] ) {
                $recommendations[] = array(
                    'type'    => 'suggestion',
                    'message' => 'Generar CSS crítico para mejorar FCP.',
                    'action'  => 'optimize/critical-css',
                );
            }
        }

        return new WP_REST_Response( array(
            'success'         => true,
            'metrics'         => $metrics,
            'recommendations' => $recommendations,
        ), 200 );
    }

    /**
     * Estima nodos DOM
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function estimate_dom_nodes( $elements ) {
        $nodes = 0;
        foreach ( $elements as $element ) {
            $nodes += 3; // wrapper + content + styles
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $nodes += $this->estimate_dom_nodes( $element['children'] );
            }
        }
        return $nodes;
    }

    /**
     * Aplica optimizaciones automáticas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function apply_auto_optimizations( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $level = sanitize_text_field( $request->get_param( 'level' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $applied = array();

        // Nivel safe
        if ( in_array( $level, array( 'safe', 'balanced', 'aggressive' ), true ) ) {
            // Activar lazy loading
            update_post_meta( $page_id, '_flavor_vbp_lazy_loading', array(
                'images'  => true,
                'iframes' => true,
            ) );
            $applied[] = 'lazy_loading';
        }

        // Nivel balanced
        if ( in_array( $level, array( 'balanced', 'aggressive' ), true ) ) {
            // Limpiar estilos huérfanos (internamente)
            $applied[] = 'cleanup_styles';

            // Configurar prefetch básico
            update_post_meta( $page_id, '_flavor_vbp_prefetch_config', array(
                'dns_prefetch' => array( '//fonts.googleapis.com', '//fonts.gstatic.com' ),
                'preconnect'   => array( '//fonts.googleapis.com' ),
            ) );
            $applied[] = 'prefetch';
        }

        // Nivel aggressive
        if ( $level === 'aggressive' ) {
            // Comprimir elementos
            $applied[] = 'compress_elements';

            // Optimizar imágenes config
            update_post_meta( $page_id, '_flavor_vbp_image_optimization', array(
                'config' => array(
                    'max_width' => 1920,
                    'quality'   => 85,
                    'format'    => 'webp',
                ),
            ) );
            $applied[] = 'image_optimization';
        }

        return new WP_REST_Response( array(
            'success'             => true,
            'level'               => $level,
            'optimizations_applied' => $applied,
            'message'             => count( $applied ) . ' optimizaciones aplicadas.',
        ), 200 );
    }

    // =============================================
}
