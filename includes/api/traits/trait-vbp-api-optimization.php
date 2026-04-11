<?php
/**
 * Trait para optimización VBP
 *
 * Este trait contiene métodos para optimización de
 * rendimiento de páginas y bloques VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Optimization
 *
 * Contiene métodos para:
 * - Análisis de rendimiento de páginas
 * - Optimización de imágenes
 * - Minificación de assets
 * - Limpieza de bloques huérfanos
 * - Configuración de preload
 * - Estado global de optimización
 */
trait VBP_API_Optimization {


    /**
     * Analiza rendimiento de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function analyze_page_performance( $request ) {
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

        // Análisis
        $block_count = $this->count_total_blocks( $elements );
        $max_depth = $this->calculate_max_nesting_depth( $elements );
        $images = $this->extract_all_images( $elements );
        $text_length = $this->calculate_total_text_length( $elements );
        $json_size = strlen( $elements_json );

        // Detectar problemas
        $issues = array();
        $score = 100;

        if ( $block_count > 100 ) {
            $issues[] = array(
                'type'    => 'warning',
                'message' => 'La página tiene muchos bloques (' . $block_count . '). Considera simplificar.',
            );
            $score -= 10;
        }

        if ( $max_depth > 10 ) {
            $issues[] = array(
                'type'    => 'warning',
                'message' => 'Anidamiento muy profundo (' . $max_depth . ' niveles). Puede afectar el rendimiento.',
            );
            $score -= 15;
        }

        if ( count( $images ) > 20 ) {
            $issues[] = array(
                'type'    => 'info',
                'message' => 'Muchas imágenes (' . count( $images ) . '). Asegúrate de que estén optimizadas.',
            );
            $score -= 5;
        }

        $large_images = array_filter( $images, function( $img ) {
            return empty( $img['lazy'] );
        } );
        if ( count( $large_images ) > 5 ) {
            $issues[] = array(
                'type'    => 'warning',
                'message' => count( $large_images ) . ' imágenes sin lazy loading.',
            );
            $score -= 10;
        }

        if ( $json_size > 500000 ) {
            $issues[] = array(
                'type'    => 'error',
                'message' => 'El JSON de la página es muy grande (' . round( $json_size / 1024 ) . ' KB).',
            );
            $score -= 20;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'page_id' => $page_id,
            'score'   => max( 0, $score ),
            'metrics' => array(
                'block_count'   => $block_count,
                'max_depth'     => $max_depth,
                'image_count'   => count( $images ),
                'text_length'   => $text_length,
                'json_size'     => $json_size,
                'json_size_kb'  => round( $json_size / 1024, 2 ),
            ),
            'issues' => $issues,
            'recommendations' => $this->get_optimization_recommendations( $score, $issues ),
        ), 200 );
    }

    /**
     * Calcula profundidad máxima de anidamiento
     *
     * @param array $elements Elementos.
     * @param int   $depth    Profundidad actual.
     * @return int
     */
    private function calculate_max_nesting_depth( $elements, $depth = 0 ) {
        $max = $depth;
        foreach ( $elements as $element ) {
            if ( ! empty( $element['children'] ) ) {
                $child_depth = $this->calculate_max_nesting_depth( $element['children'], $depth + 1 );
                $max = max( $max, $child_depth );
            }
        }
        return $max;
    }

    /**
     * Extrae todas las imágenes
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function extract_all_images( $elements ) {
        $images = array();

        foreach ( $elements as $element ) {
            if ( $element['type'] === 'image' ) {
                $images[] = array(
                    'src'  => $element['data']['src'] ?? $element['data']['url'] ?? '',
                    'alt'  => $element['data']['alt'] ?? '',
                    'lazy' => $element['data']['loading'] === 'lazy' || ! empty( $element['data']['lazy'] ),
                );
            }
            if ( ! empty( $element['data']['backgroundImage'] ) ) {
                $images[] = array(
                    'src'        => $element['data']['backgroundImage'],
                    'background' => true,
                );
            }
            if ( ! empty( $element['children'] ) ) {
                $images = array_merge( $images, $this->extract_all_images( $element['children'] ) );
            }
        }

        return $images;
    }

    /**
     * Calcula longitud total de texto
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function calculate_total_text_length( $elements ) {
        $length = 0;

        foreach ( $elements as $element ) {
            $text_props = array( 'text', 'content', 'title', 'subtitle' );
            foreach ( $text_props as $prop ) {
                if ( ! empty( $element['data'][ $prop ] ) ) {
                    $length += strlen( strip_tags( $element['data'][ $prop ] ) );
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $length += $this->calculate_total_text_length( $element['children'] );
            }
        }

        return $length;
    }

    /**
     * Obtiene recomendaciones de optimización
     *
     * @param int   $score  Puntuación.
     * @param array $issues Problemas.
     * @return array
     */
    private function get_optimization_recommendations( $score, $issues ) {
        $recommendations = array();

        if ( $score < 80 ) {
            $recommendations[] = 'Considera usar el endpoint /optimize/cleanup para eliminar bloques vacíos.';
        }
        if ( $score < 60 ) {
            $recommendations[] = 'Usa /optimize/images para optimizar imágenes automáticamente.';
        }
        if ( $score < 40 ) {
            $recommendations[] = 'La página necesita una revisión significativa de estructura.';
        }

        return $recommendations;
    }

    /**
     * Optimiza imágenes de la página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function optimize_page_images( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $lazy_load = (bool) $request->get_param( 'lazy_load' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $optimized_count = 0;

        if ( $lazy_load ) {
            list( $elements, $optimized_count ) = $this->add_lazy_loading( $elements );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'message'        => "Optimización completada. {$optimized_count} imágenes actualizadas.",
            'optimized'      => $optimized_count,
        ), 200 );
    }

    /**
     * Añade lazy loading a imágenes
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function add_lazy_loading( $elements ) {
        $count = 0;

        foreach ( $elements as &$element ) {
            if ( $element['type'] === 'image' && empty( $element['data']['loading'] ) ) {
                $element['data']['loading'] = 'lazy';
                $count++;
            }
            if ( ! empty( $element['children'] ) ) {
                list( $element['children'], $child_count ) = $this->add_lazy_loading( $element['children'] );
                $count += $child_count;
            }
        }

        return array( $elements, $count );
    }

    /**
     * Minifica assets de la página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function minify_page_assets( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $minify_css = (bool) $request->get_param( 'css' );
        $minify_js = (bool) $request->get_param( 'js' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $css_before = 0;
        $css_after = 0;

        if ( $minify_css ) {
            $custom_css = get_post_meta( $page_id, '_flavor_vbp_custom_css', true );
            if ( $custom_css ) {
                $css_before = strlen( $custom_css );
                $minified_css = $this->minify_css_content( $custom_css );
                $css_after = strlen( $minified_css );
                update_post_meta( $page_id, '_flavor_vbp_custom_css', $minified_css );
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Assets minificados.',
            'css'     => array(
                'before' => $css_before,
                'after'  => $css_after,
                'saved'  => $css_before - $css_after,
            ),
        ), 200 );
    }

    /**
     * Minifica CSS
     *
     * @param string $css CSS.
     * @return string
     */
    private function minify_css_content( $css ) {
        // Eliminar comentarios
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );
        // Eliminar espacios extra
        $css = preg_replace( '/\s+/', ' ', $css );
        // Eliminar espacios alrededor de símbolos
        $css = preg_replace( '/\s*([{};:,])\s*/', '$1', $css );
        // Eliminar punto y coma antes de }
        $css = preg_replace( '/;}/', '}', $css );

        return trim( $css );
    }

    /**
     * Limpia bloques de la página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function cleanup_page_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $remove_empty = (bool) $request->get_param( 'remove_empty' );
        $remove_hidden = (bool) $request->get_param( 'remove_hidden' );
        $merge_adjacent = (bool) $request->get_param( 'merge_adjacent' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $before_count = $this->count_total_blocks( $elements );

        if ( $remove_empty ) {
            $elements = $this->remove_empty_blocks( $elements );
        }

        if ( $remove_hidden ) {
            $elements = $this->remove_hidden_blocks( $elements );
        }

        if ( $merge_adjacent ) {
            $elements = $this->merge_adjacent_text_blocks( $elements );
        }

        $after_count = $this->count_total_blocks( $elements );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Limpieza completada.',
            'before'  => $before_count,
            'after'   => $after_count,
            'removed' => $before_count - $after_count,
        ), 200 );
    }

    /**
     * Elimina bloques vacíos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function remove_empty_blocks( $elements ) {
        $result = array();

        foreach ( $elements as $element ) {
            $is_empty = $this->is_block_empty( $element );

            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->remove_empty_blocks( $element['children'] );
            }

            if ( ! $is_empty || ! empty( $element['children'] ) ) {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * Verifica si bloque está vacío
     *
     * @param array $element Elemento.
     * @return bool
     */
    private function is_block_empty( $element ) {
        $text_props = array( 'text', 'content', 'title', 'src', 'url' );
        foreach ( $text_props as $prop ) {
            if ( ! empty( $element['data'][ $prop ] ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Elimina bloques ocultos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function remove_hidden_blocks( $elements ) {
        $result = array();

        foreach ( $elements as $element ) {
            $is_hidden = ( $element['data']['hidden'] ?? false ) ||
                         ( $element['styles']['display'] ?? '' ) === 'none' ||
                         ( $element['styles']['visibility'] ?? '' ) === 'hidden';

            if ( ! $is_hidden ) {
                if ( ! empty( $element['children'] ) ) {
                    $element['children'] = $this->remove_hidden_blocks( $element['children'] );
                }
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * Fusiona bloques de texto adyacentes
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function merge_adjacent_text_blocks( $elements ) {
        $result = array();
        $previous_text = null;

        foreach ( $elements as $element ) {
            $is_text = in_array( $element['type'], array( 'text', 'paragraph' ), true );

            if ( $is_text && $previous_text !== null ) {
                // Fusionar con el anterior
                $previous_text['data']['content'] = ( $previous_text['data']['content'] ?? '' ) .
                    ' ' . ( $element['data']['content'] ?? $element['data']['text'] ?? '' );
            } else {
                if ( $previous_text !== null ) {
                    $result[] = $previous_text;
                }

                if ( $is_text ) {
                    $previous_text = $element;
                } else {
                    $previous_text = null;
                    if ( ! empty( $element['children'] ) ) {
                        $element['children'] = $this->merge_adjacent_text_blocks( $element['children'] );
                    }
                    $result[] = $element;
                }
            }
        }

        if ( $previous_text !== null ) {
            $result[] = $previous_text;
        }

        return $result;
    }

    /**
     * Configura preload de assets
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function configure_preload( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $preload_fonts = (bool) $request->get_param( 'fonts' );
        $preload_images = (bool) $request->get_param( 'images' );
        $critical_css = (bool) $request->get_param( 'critical_css' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $preload_config = array(
            'fonts'        => $preload_fonts,
            'images'       => $preload_images,
            'critical_css' => $critical_css,
        );

        update_post_meta( $page_id, '_flavor_vbp_preload_config', wp_json_encode( $preload_config ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Configuración de preload guardada.',
            'config'  => $preload_config,
        ), 200 );
    }

    /**
     * Obtiene estado de optimización global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_global_optimization_status( $request ) {
        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => 'any',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
        ) );

        $total_blocks = 0;
        $total_images = 0;
        $total_json_size = 0;
        $pages_needing_optimization = array();

        foreach ( $pages as $page ) {
            $elements_json = get_post_meta( $page->ID, '_flavor_vbp_elements', true );
            $elements = $elements_json ? json_decode( $elements_json, true ) : array();

            $block_count = $this->count_total_blocks( $elements );
            $images = $this->extract_all_images( $elements );
            $json_size = strlen( $elements_json );

            $total_blocks += $block_count;
            $total_images += count( $images );
            $total_json_size += $json_size;

            if ( $block_count > 100 || $json_size > 500000 ) {
                $pages_needing_optimization[] = array(
                    'id'          => $page->ID,
                    'title'       => $page->post_title,
                    'blocks'      => $block_count,
                    'json_size'   => $json_size,
                );
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'summary' => array(
                'total_pages'    => count( $pages ),
                'total_blocks'   => $total_blocks,
                'total_images'   => $total_images,
                'total_json_kb'  => round( $total_json_size / 1024, 2 ),
                'avg_blocks'     => count( $pages ) > 0 ? round( $total_blocks / count( $pages ) ) : 0,
            ),
            'pages_needing_optimization' => $pages_needing_optimization,
        ), 200 );
    }

    /**
     * Regenera CSS compilado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function regenerate_compiled_css( $request ) {
        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => 'publish',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
        ) );

        $regenerated = 0;

        foreach ( $pages as $page ) {
            $elements_json = get_post_meta( $page->ID, '_flavor_vbp_elements', true );
            $elements = $elements_json ? json_decode( $elements_json, true ) : array();

            $css = $this->generate_page_css( $elements );
            update_post_meta( $page->ID, '_flavor_vbp_compiled_css', $css );
            $regenerated++;
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'message'     => "CSS regenerado para {$regenerated} páginas.",
            'regenerated' => $regenerated,
        ), 200 );
    }

    /**
     * Genera CSS de página
     *
     * @param array $elements Elementos.
     * @return string
     */
    private function generate_page_css( $elements ) {
        $css = '';

        foreach ( $elements as $element ) {
            $id = $element['id'] ?? '';
            $styles = $element['styles'] ?? array();

            if ( $id && ! empty( $styles ) ) {
                $css .= "#{$id} { " . $this->styles_to_css_string( $styles ) . " }\n";
            }

            if ( ! empty( $element['children'] ) ) {
                $css .= $this->generate_page_css( $element['children'] );
            }
        }

        return $css;
    }

    /**
     * Detecta bloques huérfanos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function detect_orphan_blocks( $request ) {
        $widgets = get_option( 'flavor_vbp_global_widgets', array() );
        $widget_ids = array_keys( $widgets );

        $orphan_widgets = array();

        foreach ( $widget_ids as $widget_id ) {
            global $wpdb;
            $usage = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                 WHERE meta_key = '_flavor_vbp_elements'
                 AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $widget_id ) . '%'
            ) );

            if ( $usage == 0 ) {
                $orphan_widgets[] = array(
                    'id'   => $widget_id,
                    'name' => $widgets[ $widget_id ]['name'] ?? 'Sin nombre',
                );
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'orphan_widgets' => $orphan_widgets,
            'total'          => count( $orphan_widgets ),
        ), 200 );
    }

    // NOTA: get_block_usage_stats eliminado - usar get_blocks_usage_stats() (línea ~11437)

    // =============================================
}
