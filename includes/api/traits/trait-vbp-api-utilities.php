<?php
/**
 * Trait para utilidades avanzadas VBP
 *
 * Este trait contiene métodos de utilidades avanzadas como validación,
 * exportación HTML, comparación de páginas y detección de bloques huérfanos.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Utilities
 *
 * Contiene métodos para:
 * - Validación de bloques (validate_page_blocks, validate_blocks_preview)
 * - Exportación a HTML (export_page_html)
 * - Comparación de páginas (compare_pages)
 * - Detección de problemas (get_orphan_blocks)
 */
trait VBP_API_Utilities {

    /**
     * Valida la estructura de bloques de una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function validate_page_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return new WP_REST_Response( array(
                'success'  => true,
                'valid'    => true,
                'page_id'  => $page_id,
                'message'  => 'La página no tiene bloques VBP.',
                'warnings' => array(),
                'errors'   => array(),
            ), 200 );
        }

        $validation = $this->validate_blocks_structure( $elements );

        return new WP_REST_Response( array(
            'success'      => true,
            'valid'        => $validation['valid'],
            'page_id'      => $page_id,
            'total_blocks' => $validation['total_blocks'],
            'warnings'     => $validation['warnings'],
            'errors'       => $validation['errors'],
            'summary'      => array(
                'blocks_by_type' => $validation['blocks_by_type'],
                'max_depth'      => $validation['max_depth'],
                'empty_blocks'   => $validation['empty_blocks'],
            ),
        ), 200 );
    }

    /**
     * Valida bloques sin guardar (preview)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function validate_blocks_preview( $request ) {
        $blocks = $request->get_param( 'blocks' );

        if ( ! is_array( $blocks ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El parámetro blocks debe ser un array.',
            ), 400 );
        }

        $validation = $this->validate_blocks_structure( $blocks );

        return new WP_REST_Response( array(
            'success'      => true,
            'valid'        => $validation['valid'],
            'total_blocks' => $validation['total_blocks'],
            'warnings'     => $validation['warnings'],
            'errors'       => $validation['errors'],
            'summary'      => array(
                'blocks_by_type' => $validation['blocks_by_type'],
                'max_depth'      => $validation['max_depth'],
                'empty_blocks'   => $validation['empty_blocks'],
            ),
        ), 200 );
    }

    /**
     * Valida la estructura de bloques recursivamente
     *
     * @param array  $blocks Array de bloques.
     * @param int    $depth Profundidad actual.
     * @param string $path Ruta del bloque actual.
     * @return array
     */
    private function validate_blocks_structure( $blocks, $depth = 0, $path = '' ) {
        $result = array(
            'valid'          => true,
            'total_blocks'   => 0,
            'warnings'       => array(),
            'errors'         => array(),
            'blocks_by_type' => array(),
            'max_depth'      => $depth,
            'empty_blocks'   => 0,
        );

        $valid_block_types = array(
            'section', 'container', 'row', 'column', 'columns',
            'heading', 'text', 'paragraph', 'button', 'image',
            'video', 'icon', 'spacer', 'divider', 'list',
            'card', 'feature-card', 'testimonial', 'pricing-table',
            'faq', 'accordion', 'tabs', 'gallery', 'slider',
            'form', 'contact-form', 'newsletter', 'social-links',
            'map', 'embed', 'html', 'shortcode', 'module-shortcode',
            'hero', 'cta', 'features', 'team', 'stats', 'timeline',
        );

        foreach ( $blocks as $index => $block ) {
            $block_path = $path ? "{$path}[{$index}]" : "[{$index}]";
            $result['total_blocks']++;

            // Verificar tipo
            $block_type = $block['type'] ?? null;
            if ( empty( $block_type ) ) {
                $result['errors'][] = array(
                    'path'    => $block_path,
                    'message' => 'Bloque sin tipo definido.',
                    'code'    => 'missing_type',
                );
                $result['valid'] = false;
                continue;
            }

            // Contar por tipo
            if ( ! isset( $result['blocks_by_type'][ $block_type ] ) ) {
                $result['blocks_by_type'][ $block_type ] = 0;
            }
            $result['blocks_by_type'][ $block_type ]++;

            // Advertir sobre tipos desconocidos
            if ( ! in_array( $block_type, $valid_block_types, true ) ) {
                $result['warnings'][] = array(
                    'path'    => $block_path,
                    'message' => "Tipo de bloque desconocido: {$block_type}",
                    'code'    => 'unknown_type',
                );
            }

            // Verificar props
            $props = $block['props'] ?? array();
            if ( empty( $props ) && ! in_array( $block_type, array( 'spacer', 'divider' ), true ) ) {
                $result['warnings'][] = array(
                    'path'    => $block_path,
                    'type'    => $block_type,
                    'message' => 'Bloque sin propiedades.',
                    'code'    => 'empty_props',
                );
                $result['empty_blocks']++;
            }

            // Verificar contenido en bloques de texto
            $text_blocks = array( 'heading', 'text', 'paragraph', 'button' );
            if ( in_array( $block_type, $text_blocks, true ) ) {
                $text_content = $props['text'] ?? $props['content'] ?? '';
                if ( empty( trim( $text_content ) ) ) {
                    $result['warnings'][] = array(
                        'path'    => $block_path,
                        'type'    => $block_type,
                        'message' => 'Bloque de texto sin contenido.',
                        'code'    => 'empty_text',
                    );
                    $result['empty_blocks']++;
                }
            }

            // Verificar imagen
            if ( $block_type === 'image' ) {
                $src = $props['src'] ?? $props['url'] ?? '';
                if ( empty( $src ) ) {
                    $result['warnings'][] = array(
                        'path'    => $block_path,
                        'message' => 'Bloque de imagen sin URL.',
                        'code'    => 'missing_image_src',
                    );
                }
                $alt = $props['alt'] ?? '';
                if ( empty( $alt ) ) {
                    $result['warnings'][] = array(
                        'path'    => $block_path,
                        'message' => 'Imagen sin texto alternativo (accesibilidad).',
                        'code'    => 'missing_alt',
                    );
                }
            }

            // Verificar botón
            if ( $block_type === 'button' ) {
                $url = $props['url'] ?? $props['href'] ?? '';
                if ( empty( $url ) || $url === '#' ) {
                    $result['warnings'][] = array(
                        'path'    => $block_path,
                        'message' => 'Botón sin URL de destino.',
                        'code'    => 'missing_button_url',
                    );
                }
            }

            // Validar children recursivamente
            if ( isset( $block['children'] ) && is_array( $block['children'] ) ) {
                $child_result = $this->validate_blocks_structure( $block['children'], $depth + 1, $block_path );

                $result['total_blocks'] += $child_result['total_blocks'];
                $result['warnings'] = array_merge( $result['warnings'], $child_result['warnings'] );
                $result['errors'] = array_merge( $result['errors'], $child_result['errors'] );
                $result['empty_blocks'] += $child_result['empty_blocks'];

                foreach ( $child_result['blocks_by_type'] as $type => $count ) {
                    if ( ! isset( $result['blocks_by_type'][ $type ] ) ) {
                        $result['blocks_by_type'][ $type ] = 0;
                    }
                    $result['blocks_by_type'][ $type ] += $count;
                }

                if ( $child_result['max_depth'] > $result['max_depth'] ) {
                    $result['max_depth'] = $child_result['max_depth'];
                }

                if ( ! $child_result['valid'] ) {
                    $result['valid'] = false;
                }
            }
        }

        // Advertencia de profundidad excesiva
        if ( $result['max_depth'] > 10 ) {
            $result['warnings'][] = array(
                'path'    => 'root',
                'message' => "Anidación muy profunda ({$result['max_depth']} niveles). Considerar simplificar.",
                'code'    => 'deep_nesting',
            );
        }

        return $result;
    }

    /**
     * Exporta una página VBP a HTML estático
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_html( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_styles = (bool) $request->get_param( 'include_styles' );
        $include_scripts = (bool) $request->get_param( 'include_scripts' );
        $minify = (bool) $request->get_param( 'minify' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada.',
            ), 404 );
        }

        $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $styles = get_post_meta( $page_id, '_flavor_vbp_styles', true );

        // Generar HTML del contenido
        $content_html = $this->render_elements_to_html( $elements ?: array() );

        // Construir documento HTML completo
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="es">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '  <meta charset="UTF-8">' . "\n";
        $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '  <title>' . esc_html( $post->post_title ) . '</title>' . "\n";

        // Meta SEO
        $seo_desc = get_post_meta( $page_id, '_flavor_seo_description', true );
        if ( $seo_desc ) {
            $html .= '  <meta name="description" content="' . esc_attr( $seo_desc ) . '">' . "\n";
        }

        // Estilos
        if ( $include_styles ) {
            $html .= '  <style>' . "\n";
            $html .= $this->get_vbp_base_styles();
            if ( ! empty( $styles ) ) {
                $html .= $this->convert_styles_to_css( $styles );
            }
            $html .= '  </style>' . "\n";
        }

        $html .= '</head>' . "\n";
        $html .= '<body class="vbp-page vbp-exported">' . "\n";
        $html .= $content_html;

        if ( $include_scripts ) {
            $html .= '  <script>' . "\n";
            $html .= $this->get_vbp_base_scripts();
            $html .= '  </script>' . "\n";
        }

        $html .= '</body>' . "\n";
        $html .= '</html>';

        // Minificar si se solicita
        if ( $minify ) {
            $html = $this->minify_html( $html );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'page_id'   => $page_id,
            'title'     => $post->post_title,
            'html'      => $html,
            'size'      => strlen( $html ),
            'size_kb'   => round( strlen( $html ) / 1024, 2 ),
            'minified'  => $minify,
            'options'   => array(
                'styles'  => $include_styles,
                'scripts' => $include_scripts,
            ),
        ), 200 );
    }

    /**
     * Renderiza elementos VBP a HTML
     *
     * @param array $elements Array de elementos.
     * @param int   $indent Nivel de indentación.
     * @return string
     */
    private function render_elements_to_html( $elements, $indent = 0 ) {
        $html = '';
        $indent_str = str_repeat( '  ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $props = $element['props'] ?? array();
            $children = $element['children'] ?? array();

            // Mapear tipos VBP a tags HTML
            $tag_map = array(
                'section'   => 'section',
                'container' => 'div',
                'row'       => 'div',
                'column'    => 'div',
                'columns'   => 'div',
                'heading'   => 'h2',
                'text'      => 'p',
                'paragraph' => 'p',
                'button'    => 'a',
                'image'     => 'img',
                'spacer'    => 'div',
                'divider'   => 'hr',
                'list'      => 'ul',
            );

            $tag = $tag_map[ $type ] ?? 'div';

            // Construir clases
            $classes = array( "vbp-{$type}" );
            if ( ! empty( $props['className'] ) ) {
                $classes[] = $props['className'];
            }

            // Construir atributos
            $attrs = array(
                'class' => implode( ' ', $classes ),
            );

            // Atributos específicos por tipo
            if ( $type === 'heading' && isset( $props['level'] ) ) {
                $tag = 'h' . min( 6, max( 1, (int) $props['level'] ) );
            }

            if ( $type === 'button' ) {
                $attrs['href'] = $props['url'] ?? $props['href'] ?? '#';
                if ( ! empty( $props['target'] ) ) {
                    $attrs['target'] = $props['target'];
                }
            }

            if ( $type === 'image' ) {
                $attrs['src'] = $props['src'] ?? $props['url'] ?? '';
                $attrs['alt'] = $props['alt'] ?? '';
                if ( ! empty( $props['width'] ) ) {
                    $attrs['width'] = $props['width'];
                }
                if ( ! empty( $props['height'] ) ) {
                    $attrs['height'] = $props['height'];
                }
            }

            // Estilos inline
            $inline_styles = array();
            if ( ! empty( $props['color'] ) ) {
                $inline_styles[] = "color: {$props['color']}";
            }
            if ( ! empty( $props['backgroundColor'] ) ) {
                $inline_styles[] = "background-color: {$props['backgroundColor']}";
            }
            if ( ! empty( $props['padding'] ) ) {
                $inline_styles[] = "padding: {$props['padding']}";
            }
            if ( ! empty( $props['margin'] ) ) {
                $inline_styles[] = "margin: {$props['margin']}";
            }
            if ( ! empty( $props['textAlign'] ) || ! empty( $props['align'] ) ) {
                $align = $props['textAlign'] ?? $props['align'];
                $inline_styles[] = "text-align: {$align}";
            }

            if ( ! empty( $inline_styles ) ) {
                $attrs['style'] = implode( '; ', $inline_styles );
            }

            // Construir string de atributos
            $attr_str = '';
            foreach ( $attrs as $attr_name => $attr_value ) {
                $attr_str .= ' ' . $attr_name . '="' . esc_attr( $attr_value ) . '"';
            }

            // Tags auto-cerrados
            $self_closing = array( 'img', 'hr', 'br', 'input' );
            if ( in_array( $tag, $self_closing, true ) ) {
                $html .= "{$indent_str}<{$tag}{$attr_str} />\n";
            } else {
                $html .= "{$indent_str}<{$tag}{$attr_str}>";

                // Contenido de texto
                $text_content = $props['text'] ?? $props['content'] ?? '';
                if ( ! empty( $text_content ) ) {
                    $html .= esc_html( $text_content );
                }

                // Children
                if ( ! empty( $children ) ) {
                    $html .= "\n";
                    $html .= $this->render_elements_to_html( $children, $indent + 1 );
                    $html .= $indent_str;
                }

                $html .= "</{$tag}>\n";
            }
        }

        return $html;
    }

    /**
     * Obtiene estilos base de VBP
     *
     * @return string
     */
    private function get_vbp_base_styles() {
        return '
    * { box-sizing: border-box; }
    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; }
    .vbp-section { padding: 4rem 2rem; }
    .vbp-container { max-width: 1200px; margin: 0 auto; }
    .vbp-row { display: flex; flex-wrap: wrap; gap: 1rem; }
    .vbp-column { flex: 1; min-width: 250px; }
    .vbp-columns { display: grid; gap: 2rem; }
    .vbp-heading { margin: 0 0 1rem; }
    .vbp-text, .vbp-paragraph { margin: 0 0 1rem; }
    .vbp-button { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 0.375rem; transition: background 0.2s; }
    .vbp-button:hover { background: #2563eb; }
    .vbp-image { max-width: 100%; height: auto; }
    .vbp-spacer { height: 2rem; }
    .vbp-divider { border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0; }
    @media (max-width: 768px) {
      .vbp-section { padding: 2rem 1rem; }
      .vbp-columns { grid-template-columns: 1fr !important; }
    }
    ';
    }

    /**
     * Obtiene scripts base de VBP
     *
     * @return string
     */
    private function get_vbp_base_scripts() {
        return '
    document.addEventListener("DOMContentLoaded", function() {
      // Smooth scroll para anclas
      document.querySelectorAll("a[href^=\'#\']").forEach(function(anchor) {
        anchor.addEventListener("click", function(e) {
          var target = document.querySelector(this.getAttribute("href"));
          if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: "smooth" });
          }
        });
      });
    });
    ';
    }

    /**
     * Convierte estilos VBP a CSS
     *
     * @param array $styles Estilos VBP.
     * @return string
     */
    private function convert_styles_to_css( $styles ) {
        $css = '';

        if ( isset( $styles['colors'] ) ) {
            $css .= ':root {';
            foreach ( $styles['colors'] as $name => $value ) {
                $css .= " --vbp-{$name}: {$value};";
            }
            $css .= ' }';
        }

        if ( isset( $styles['advanced']['customCss'] ) ) {
            $css .= $styles['advanced']['customCss'];
        }

        return $css;
    }

    /**
     * Minifica HTML
     *
     * @param string $html HTML a minificar.
     * @return string
     */
    private function minify_html( $html ) {
        // Eliminar comentarios HTML
        $html = preg_replace( '/<!--(?!<!)[^\[>].*?-->/s', '', $html );

        // Eliminar espacios en blanco excesivos
        $html = preg_replace( '/\s+/', ' ', $html );

        // Eliminar espacios alrededor de tags
        $html = preg_replace( '/>\s+</', '><', $html );

        // Eliminar espacios al inicio y final
        $html = trim( $html );

        return $html;
    }

    /**
     * Compara dos páginas VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_pages( $request ) {
        $page_a_id = (int) $request->get_param( 'page_a' );
        $page_b_id = (int) $request->get_param( 'page_b' );
        $detail_level = $request->get_param( 'detail_level' );

        $post_a = get_post( $page_a_id );
        $post_b = get_post( $page_b_id );

        if ( ! $post_a || $post_a->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => "Página A (ID: {$page_a_id}) no encontrada.",
            ), 404 );
        }

        if ( ! $post_b || $post_b->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => "Página B (ID: {$page_b_id}) no encontrada.",
            ), 404 );
        }

        $elements_a = get_post_meta( $page_a_id, '_flavor_vbp_elements', true ) ?: array();
        $elements_b = get_post_meta( $page_b_id, '_flavor_vbp_elements', true ) ?: array();

        $blocks_count_a = $this->count_total_blocks( $elements_a );
        $blocks_count_b = $this->count_total_blocks( $elements_b );

        $comparison = array(
            'page_a' => array(
                'id'           => $page_a_id,
                'title'        => $post_a->post_title,
                'status'       => $post_a->post_status,
                'block_count'  => $blocks_count_a,
                'modified'     => $post_a->post_modified,
            ),
            'page_b' => array(
                'id'           => $page_b_id,
                'title'        => $post_b->post_title,
                'status'       => $post_b->post_status,
                'block_count'  => $blocks_count_b,
                'modified'     => $post_b->post_modified,
            ),
            'differences' => array(
                'title_same'       => $post_a->post_title === $post_b->post_title,
                'status_same'      => $post_a->post_status === $post_b->post_status,
                'block_count_diff' => $blocks_count_a - $blocks_count_b,
            ),
        );

        // Comparación de bloques por tipo
        if ( $detail_level === 'blocks' || $detail_level === 'full' ) {
            $types_a = $this->get_block_types_count( $elements_a );
            $types_b = $this->get_block_types_count( $elements_b );

            $all_types = array_unique( array_merge( array_keys( $types_a ), array_keys( $types_b ) ) );
            $type_comparison = array();

            foreach ( $all_types as $type ) {
                $count_a = $types_a[ $type ] ?? 0;
                $count_b = $types_b[ $type ] ?? 0;
                $type_comparison[ $type ] = array(
                    'page_a' => $count_a,
                    'page_b' => $count_b,
                    'diff'   => $count_a - $count_b,
                );
            }

            $comparison['blocks_by_type'] = $type_comparison;
        }

        // Comparación detallada de contenido
        if ( $detail_level === 'full' ) {
            $content_diff = $this->compare_block_content( $elements_a, $elements_b );
            $comparison['content_diff'] = $content_diff;
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'detail_level' => $detail_level,
            'comparison'   => $comparison,
        ), 200 );
    }

    /**
     * Obtiene conteo de bloques por tipo
     *
     * @param array $elements Elementos VBP.
     * @return array
     */
    private function get_block_types_count( $elements ) {
        $counts = array();

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'unknown';
            if ( ! isset( $counts[ $type ] ) ) {
                $counts[ $type ] = 0;
            }
            $counts[ $type ]++;

            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $child_counts = $this->get_block_types_count( $element['children'] );
                foreach ( $child_counts as $child_type => $child_count ) {
                    if ( ! isset( $counts[ $child_type ] ) ) {
                        $counts[ $child_type ] = 0;
                    }
                    $counts[ $child_type ] += $child_count;
                }
            }
        }

        return $counts;
    }

    /**
     * Compara contenido de bloques entre dos páginas
     *
     * @param array $elements_a Elementos de página A.
     * @param array $elements_b Elementos de página B.
     * @return array
     */
    private function compare_block_content( $elements_a, $elements_b ) {
        $texts_a = $this->extract_all_texts( $elements_a );
        $texts_b = $this->extract_all_texts( $elements_b );

        $only_in_a = array_diff( $texts_a, $texts_b );
        $only_in_b = array_diff( $texts_b, $texts_a );
        $common = array_intersect( $texts_a, $texts_b );

        return array(
            'texts_in_a'     => count( $texts_a ),
            'texts_in_b'     => count( $texts_b ),
            'common_texts'   => count( $common ),
            'only_in_a'      => array_values( $only_in_a ),
            'only_in_b'      => array_values( $only_in_b ),
            'similarity'     => count( $texts_a ) > 0 || count( $texts_b ) > 0
                ? round( ( count( $common ) * 2 ) / ( count( $texts_a ) + count( $texts_b ) ) * 100, 2 )
                : 100,
        );
    }

    /**
     * Extrae todos los textos de los bloques
     *
     * @param array $elements Elementos VBP.
     * @return array
     */
    private function extract_all_texts( $elements ) {
        $texts = array();

        foreach ( $elements as $element ) {
            $props = $element['props'] ?? array();

            foreach ( array( 'text', 'content', 'title', 'subtitle', 'description' ) as $prop ) {
                if ( ! empty( $props[ $prop ] ) && is_string( $props[ $prop ] ) ) {
                    $texts[] = trim( $props[ $prop ] );
                }
            }

            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $texts = array_merge( $texts, $this->extract_all_texts( $element['children'] ) );
            }
        }

        return array_filter( $texts );
    }

    /**
     * Obtiene bloques huérfanos o con problemas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_orphan_blocks( $request ) {
        global $wpdb;

        $issues = array();

        // Buscar páginas con elementos VBP vacíos o corruptos
        $pages_with_vbp = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'flavor_landing'
             AND p.post_status IN ('publish', 'draft', 'private')
             AND pm.meta_key = '_flavor_vbp_elements'"
        );

        foreach ( $pages_with_vbp as $page ) {
            $elements = maybe_unserialize( $page->meta_value );

            if ( $elements === false ) {
                $issues[] = array(
                    'page_id'  => $page->ID,
                    'title'    => $page->post_title,
                    'issue'    => 'corrupt_data',
                    'message'  => 'Datos de elementos VBP corruptos (no se puede deserializar).',
                    'severity' => 'error',
                );
                continue;
            }

            if ( empty( $elements ) ) {
                $issues[] = array(
                    'page_id'  => $page->ID,
                    'title'    => $page->post_title,
                    'issue'    => 'empty_elements',
                    'message'  => 'Página sin elementos VBP.',
                    'severity' => 'warning',
                );
                continue;
            }

            // Validar estructura
            $validation = $this->validate_blocks_structure( $elements );
            if ( ! empty( $validation['errors'] ) ) {
                $issues[] = array(
                    'page_id'      => $page->ID,
                    'title'        => $page->post_title,
                    'issue'        => 'validation_errors',
                    'message'      => 'Página con errores de validación.',
                    'severity'     => 'error',
                    'error_count'  => count( $validation['errors'] ),
                    'errors'       => array_slice( $validation['errors'], 0, 5 ),
                );
            } elseif ( count( $validation['warnings'] ) > 5 ) {
                $issues[] = array(
                    'page_id'       => $page->ID,
                    'title'         => $page->post_title,
                    'issue'         => 'many_warnings',
                    'message'       => 'Página con muchas advertencias.',
                    'severity'      => 'warning',
                    'warning_count' => count( $validation['warnings'] ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'total_pages'  => count( $pages_with_vbp ),
            'issues_found' => count( $issues ),
            'issues'       => $issues,
            'summary'      => array(
                'errors'   => count( array_filter( $issues, function( $i ) { return $i['severity'] === 'error'; } ) ),
                'warnings' => count( array_filter( $issues, function( $i ) { return $i['severity'] === 'warning'; } ) ),
            ),
        ), 200 );
    }
}
