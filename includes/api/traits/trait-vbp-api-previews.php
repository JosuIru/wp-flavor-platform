<?php
/**
 * Trait para previsualizaciones VBP
 *
 * Este trait contiene métodos para generar previsualizaciones
 * de páginas y bloques VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Previews
 *
 * Contiene métodos para:
 * - Preview de páginas por dispositivo
 * - Preview de bloques individuales
 * - Preview temporal de cambios
 * - Generación de thumbnails
 * - Comparación de versiones de página
 */
trait VBP_API_Previews {

    /**
     * Obtiene preview de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $device = $request->get_param( 'device' );
        $custom_width = $request->get_param( 'width' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Dimensiones por dispositivo
        $device_widths = array(
            'desktop' => 1200,
            'tablet'  => 768,
            'mobile'  => 375,
        );

        $width = $custom_width ?: ( $device_widths[ $device ] ?? 1200 );

        // Generar URL de preview
        $preview_url = add_query_arg( array(
            'vbp_preview' => 1,
            'device'      => $device,
            'width'       => $width,
        ), get_permalink( $page_id ) );

        // Obtener HTML renderizado
        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $html = $this->render_elements_to_html( $elements );
        $css = $this->generate_preview_css( $elements, $width );

        return new WP_REST_Response( array(
            'success'     => true,
            'page_id'     => $page_id,
            'device'      => $device,
            'width'       => $width,
            'preview_url' => $preview_url,
            'html'        => $html,
            'css'         => $css,
        ), 200 );
    }

    /**
     * Convierte estilos a string CSS
     *
     * @param array $styles Estilos.
     * @return string
     */
    private function styles_to_css_string( $styles ) {
        $css_parts = array();

        foreach ( $styles as $property => $value ) {
            // Convertir camelCase a kebab-case
            $css_property = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $property ) );
            $css_parts[] = $css_property . ':' . $value;
        }

        return implode( ';', $css_parts );
    }

    /**
     * Genera CSS de preview
     *
     * @param array $elements Elementos.
     * @param int   $width    Ancho.
     * @return string
     */
    private function generate_preview_css( $elements, $width ) {
        $css = ".vbp-preview-container { max-width: {$width}px; margin: 0 auto; }\n";

        // Añadir estilos responsivos según ancho
        if ( $width <= 768 ) {
            $css .= ".vbp-block { padding: 10px; }\n";
            $css .= ".vbp-columns { flex-direction: column; }\n";
        }

        return $css;
    }

    /**
     * Obtiene preview de un bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $block = $this->find_block_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Bloque no encontrado.',
            ), 404 );
        }

        $html = $this->render_elements_to_html( array( $block ) );
        $css = $this->styles_to_css_string( $block['styles'] ?? array() );

        return new WP_REST_Response( array(
            'success'  => true,
            'block_id' => $block_id,
            'html'     => $html,
            'css'      => $css,
            'type'     => $block['type'] ?? 'unknown',
        ), 200 );
    }

    /**
     * Crea preview temporal
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_temp_preview( $request ) {
        $elements = $request->get_param( 'elements' );
        $settings = $request->get_param( 'settings' );
        $ttl = min( (int) $request->get_param( 'ttl' ), 86400 ); // Máximo 24h

        $preview_id = wp_generate_password( 12, false );

        $preview_data = array(
            'elements'   => $elements,
            'settings'   => $settings,
            'created_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
        );

        set_transient( 'vbp_temp_preview_' . $preview_id, $preview_data, $ttl );

        $preview_url = add_query_arg( array(
            'vbp_temp_preview' => $preview_id,
        ), home_url( '/vbp-preview/' ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_id'  => $preview_id,
            'preview_url' => $preview_url,
            'expires_in'  => $ttl,
        ), 201 );
    }

    /**
     * Obtiene preview temporal
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_temp_preview( $request ) {
        $preview_id = sanitize_text_field( $request->get_param( 'preview_id' ) );

        $preview_data = get_transient( 'vbp_temp_preview_' . $preview_id );

        if ( ! $preview_data ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Preview expirado o no encontrado.',
            ), 404 );
        }

        $html = $this->render_elements_to_html( $preview_data['elements'] );

        return new WP_REST_Response( array(
            'success'  => true,
            'elements' => $preview_data['elements'],
            'settings' => $preview_data['settings'],
            'html'     => $html,
        ), 200 );
    }

    /**
     * Obtiene thumbnail de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_thumbnail( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $width = (int) $request->get_param( 'width' );
        $height = (int) $request->get_param( 'height' );
        $regenerate = (bool) $request->get_param( 'regenerate' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $thumbnail_key = "vbp_thumbnail_{$page_id}_{$width}x{$height}";
        $thumbnail_url = get_transient( $thumbnail_key );

        if ( ! $thumbnail_url || $regenerate ) {
            // Generar placeholder SVG como thumbnail
            $thumbnail_url = $this->generate_svg_thumbnail( $page_id, $width, $height );
            set_transient( $thumbnail_key, $thumbnail_url, DAY_IN_SECONDS );
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'page_id'       => $page_id,
            'thumbnail_url' => $thumbnail_url,
            'width'         => $width,
            'height'        => $height,
        ), 200 );
    }

    /**
     * Genera thumbnail SVG
     *
     * @param int $page_id ID de la página.
     * @param int $width   Ancho.
     * @param int $height  Alto.
     * @return string
     */
    private function generate_svg_thumbnail( $page_id, $width, $height ) {
        $post = get_post( $page_id );
        $title = $post ? $post->post_title : 'Page';

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();
        $block_count = $this->count_total_blocks( $elements );

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">
                <rect fill="#f3f4f6" width="%d" height="%d"/>
                <rect fill="#e5e7eb" x="20" y="20" width="%d" height="40" rx="4"/>
                <text x="%d" y="48" font-family="system-ui" font-size="14" fill="#374151" text-anchor="middle">%s</text>
                <text x="%d" y="%d" font-family="system-ui" font-size="12" fill="#6b7280" text-anchor="middle">%d bloques</text>
            </svg>',
            $width, $height, $width, $height,
            $width, $height,
            $width - 40,
            $width / 2, esc_html( substr( $title, 0, 30 ) ),
            $width / 2, $height - 20, $block_count
        );

        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }

    /**
     * Cuenta bloques simple recursivamente
     *
     * @param array $elements Elementos.
     * @return int
     */
    private function count_total_blocks( $elements ) {
        $count = count( $elements );
        foreach ( $elements as $element ) {
            if ( ! empty( $element['children'] ) ) {
                $count += $this->count_total_blocks( $element['children'] );
            }
        }
        return $count;
    }

    /**
     * Compara versiones de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_page_versions( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $version_a = $request->get_param( 'version_a' );
        $version_b = $request->get_param( 'version_b' );
        $format = $request->get_param( 'format' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Obtener versión A
        if ( $version_a === 'current' ) {
            $elements_a = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        } else {
            $revision = get_post( (int) $version_a );
            $elements_a = $revision ? get_post_meta( $revision->ID, '_flavor_vbp_elements', true ) : null;
        }

        // Obtener versión B
        $revision_b = get_post( (int) $version_b );
        $elements_b = $revision_b ? get_post_meta( $revision_b->ID, '_flavor_vbp_elements', true ) : null;

        if ( ! $elements_a || ! $elements_b ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Una o ambas versiones no encontradas.',
            ), 404 );
        }

        $array_a = json_decode( $elements_a, true ) ?: array();
        $array_b = json_decode( $elements_b, true ) ?: array();

        $diff = $this->calculate_elements_diff( $array_a, $array_b );

        return new WP_REST_Response( array(
            'success'   => true,
            'format'    => $format,
            'version_a' => $version_a,
            'version_b' => $version_b,
            'diff'      => $diff,
            'summary'   => array(
                'added'    => $diff['added_count'],
                'removed'  => $diff['removed_count'],
                'modified' => $diff['modified_count'],
            ),
        ), 200 );
    }

    /**
     * Calcula diferencias entre elementos
     *
     * @param array $elements_a Elementos A.
     * @param array $elements_b Elementos B.
     * @return array
     */
    private function calculate_elements_diff( $elements_a, $elements_b ) {
        $ids_a = $this->extract_all_block_ids( $elements_a );
        $ids_b = $this->extract_all_block_ids( $elements_b );

        $added = array_diff( $ids_a, $ids_b );
        $removed = array_diff( $ids_b, $ids_a );
        $common = array_intersect( $ids_a, $ids_b );

        $modified = array();
        foreach ( $common as $id ) {
            $block_a = $this->find_block_by_id( $elements_a, $id );
            $block_b = $this->find_block_by_id( $elements_b, $id );
            if ( wp_json_encode( $block_a ) !== wp_json_encode( $block_b ) ) {
                $modified[] = $id;
            }
        }

        return array(
            'added'          => array_values( $added ),
            'removed'        => array_values( $removed ),
            'modified'       => $modified,
            'added_count'    => count( $added ),
            'removed_count'  => count( $removed ),
            'modified_count' => count( $modified ),
        );
    }

    /**
     * Extrae todos los IDs de bloques
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function extract_all_block_ids( $elements ) {
        $ids = array();
        foreach ( $elements as $element ) {
            if ( ! empty( $element['id'] ) ) {
                $ids[] = $element['id'];
            }
            if ( ! empty( $element['children'] ) ) {
                $ids = array_merge( $ids, $this->extract_all_block_ids( $element['children'] ) );
            }
        }
        return $ids;
    }

}
