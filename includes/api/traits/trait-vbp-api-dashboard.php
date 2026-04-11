<?php
/**
 * Trait para dashboard y auditorías VBP
 *
 * Este trait contiene métodos para obtener estadísticas del dashboard,
 * auditorías de medios y shortcodes usados en páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Dashboard
 *
 * Contiene métodos para:
 * - Dashboard de estadísticas (get_vbp_dashboard)
 * - Auditoría de medios (get_media_audit)
 * - Auditoría de shortcodes (get_shortcodes_audit)
 */
trait VBP_API_Dashboard {

    /**
     * Obtiene dashboard de estadísticas VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_vbp_dashboard( $request ) {
        global $wpdb;

        // Contar páginas por estado
        $status_counts = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as count
             FROM {$wpdb->posts}
             WHERE post_type = 'flavor_landing'
             GROUP BY post_status",
            OBJECT_K
        );

        $total_pages = 0;
        $pages_by_status = array();
        foreach ( $status_counts as $status => $data ) {
            $pages_by_status[ $status ] = (int) $data->count;
            $total_pages += (int) $data->count;
        }

        // Obtener páginas recientes
        $recent_pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => 5,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );

        $recent = array();
        foreach ( $recent_pages as $page ) {
            $recent[] = array(
                'id'       => $page->ID,
                'title'    => $page->post_title,
                'status'   => $page->post_status,
                'modified' => $page->post_modified,
            );
        }

        // Contar bloques totales
        $total_blocks = 0;
        $block_types = array();
        $pages_meta = $wpdb->get_col(
            "SELECT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_flavor_vbp_elements'"
        );

        foreach ( $pages_meta as $meta ) {
            $elements = maybe_unserialize( $meta );
            if ( is_array( $elements ) ) {
                $count = $this->count_total_blocks( $elements );
                $total_blocks += $count;

                $types = $this->get_block_types_count( $elements );
                foreach ( $types as $type => $type_count ) {
                    if ( ! isset( $block_types[ $type ] ) ) {
                        $block_types[ $type ] = 0;
                    }
                    $block_types[ $type ] += $type_count;
                }
            }
        }

        arsort( $block_types );

        // Plantillas guardadas
        $templates = get_option( 'flavor_vbp_block_templates', array() );

        return new WP_REST_Response( array(
            'success' => true,
            'stats'   => array(
                'total_pages'       => $total_pages,
                'pages_by_status'   => $pages_by_status,
                'total_blocks'      => $total_blocks,
                'avg_blocks_per_page' => $total_pages > 0 ? round( $total_blocks / $total_pages, 1 ) : 0,
                'block_types'       => array_slice( $block_types, 0, 10, true ),
                'templates_saved'   => count( $templates ),
            ),
            'recent_pages' => $recent,
            'system'       => array(
                'vbp_version'   => '1.0.0',
                'php_version'   => PHP_VERSION,
                'wp_version'    => get_bloginfo( 'version' ),
            ),
        ), 200 );
    }

    /**
     * Auditoría de medios usados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_media_audit( $request ) {
        global $wpdb;

        $images = array();
        $pages_meta = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_flavor_vbp_elements'"
        );

        foreach ( $pages_meta as $row ) {
            $elements = maybe_unserialize( $row->meta_value );
            if ( is_array( $elements ) ) {
                $page_images = $this->extract_images_from_blocks( $elements );
                foreach ( $page_images as $image ) {
                    $image['page_id'] = $row->post_id;
                    $images[] = $image;
                }
            }
        }

        // Agrupar por URL
        $unique_images = array();
        foreach ( $images as $img ) {
            $url = $img['url'];
            if ( ! isset( $unique_images[ $url ] ) ) {
                $unique_images[ $url ] = array(
                    'url'        => $url,
                    'has_alt'    => ! empty( $img['alt'] ),
                    'alt'        => $img['alt'] ?? '',
                    'used_in'    => array(),
                );
            }
            $unique_images[ $url ]['used_in'][] = $img['page_id'];
        }

        // Estadísticas
        $total_images = count( $images );
        $unique_count = count( $unique_images );
        $missing_alt = count( array_filter( $unique_images, function( $img ) {
            return ! $img['has_alt'];
        } ) );

        return new WP_REST_Response( array(
            'success' => true,
            'stats'   => array(
                'total_uses'    => $total_images,
                'unique_images' => $unique_count,
                'missing_alt'   => $missing_alt,
            ),
            'images'  => array_values( $unique_images ),
        ), 200 );
    }

    /**
     * Extrae imágenes de bloques
     *
     * @param array $elements Elementos VBP.
     * @return array
     */
    private function extract_images_from_blocks( $elements ) {
        $images = array();

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            $props = $element['props'] ?? array();

            if ( $type === 'image' ) {
                $images[] = array(
                    'url'  => $props['src'] ?? $props['url'] ?? '',
                    'alt'  => $props['alt'] ?? '',
                    'type' => 'image_block',
                );
            }

            if ( ! empty( $props['backgroundImage'] ) ) {
                $images[] = array(
                    'url'  => $props['backgroundImage'],
                    'alt'  => '',
                    'type' => 'background',
                );
            }

            if ( ! empty( $element['children'] ) ) {
                $images = array_merge( $images, $this->extract_images_from_blocks( $element['children'] ) );
            }
        }

        return array_filter( $images, function( $img ) {
            return ! empty( $img['url'] );
        } );
    }

    /**
     * Auditoría de shortcodes usados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_shortcodes_audit( $request ) {
        global $wpdb;

        $shortcodes = array();
        $pages_meta = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_flavor_vbp_elements'"
        );

        foreach ( $pages_meta as $row ) {
            $elements = maybe_unserialize( $row->meta_value );
            if ( is_array( $elements ) ) {
                $page_shortcodes = $this->extract_shortcodes_from_blocks( $elements );
                foreach ( $page_shortcodes as $sc ) {
                    $sc['page_id'] = $row->post_id;
                    $shortcodes[] = $sc;
                }
            }
        }

        // Agrupar por nombre de shortcode
        $grouped = array();
        foreach ( $shortcodes as $sc ) {
            $name = $sc['shortcode'];
            if ( ! isset( $grouped[ $name ] ) ) {
                $grouped[ $name ] = array(
                    'shortcode' => $name,
                    'count'     => 0,
                    'pages'     => array(),
                );
            }
            $grouped[ $name ]['count']++;
            if ( ! in_array( $sc['page_id'], $grouped[ $name ]['pages'], true ) ) {
                $grouped[ $name ]['pages'][] = $sc['page_id'];
            }
        }

        usort( $grouped, function( $a, $b ) {
            return $b['count'] - $a['count'];
        } );

        return new WP_REST_Response( array(
            'success'    => true,
            'total_uses' => count( $shortcodes ),
            'unique'     => count( $grouped ),
            'shortcodes' => array_values( $grouped ),
        ), 200 );
    }

    /**
     * Extrae shortcodes de bloques
     *
     * @param array $elements Elementos VBP.
     * @return array
     */
    private function extract_shortcodes_from_blocks( $elements ) {
        $shortcodes = array();

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? '';
            $props = $element['props'] ?? array();

            // Bloque de shortcode
            if ( $type === 'shortcode' || $type === 'module-shortcode' ) {
                $shortcodes[] = array(
                    'shortcode' => $props['shortcode'] ?? $props['module'] ?? 'unknown',
                    'type'      => $type,
                );
            }

            // Buscar shortcodes en texto
            foreach ( array( 'content', 'text', 'html' ) as $prop ) {
                if ( ! empty( $props[ $prop ] ) && is_string( $props[ $prop ] ) ) {
                    preg_match_all( '/\[([a-zA-Z_][a-zA-Z0-9_-]*)/', $props[ $prop ], $matches );
                    if ( ! empty( $matches[1] ) ) {
                        foreach ( $matches[1] as $sc_name ) {
                            $shortcodes[] = array(
                                'shortcode' => $sc_name,
                                'type'      => 'embedded',
                            );
                        }
                    }
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $shortcodes = array_merge( $shortcodes, $this->extract_shortcodes_from_blocks( $element['children'] ) );
            }
        }

        return $shortcodes;
    }
}
