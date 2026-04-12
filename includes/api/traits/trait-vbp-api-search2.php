<?php
/**
 * Trait para Búsqueda Avanzada VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_SearchAdvanced {


    /**
     * Búsqueda global en VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function global_vbp_search( $request ) {
        $query = sanitize_text_field( $request->get_param( 'query' ) );
        $search_in = $request->get_param( 'search_in' ) ?: array( 'pages', 'blocks', 'widgets' );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $results = array(
            'pages'   => array(),
            'blocks'  => array(),
            'widgets' => array(),
        );

        if ( in_array( 'pages', $search_in, true ) ) {
            $pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => $limit,
                's'              => $query,
                'post_status'    => array( 'publish', 'draft' ),
            ) );

            foreach ( $pages as $page ) {
                $results['pages'][] = array(
                    'id'     => $page->ID,
                    'title'  => $page->post_title,
                    'slug'   => $page->post_name,
                    'status' => $page->post_status,
                );
            }
        }

        if ( in_array( 'blocks', $search_in, true ) ) {
            // Buscar en contenido de bloques (con límite seguro)
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'post_status'    => 'any',
            ) );

            foreach ( $all_pages as $page ) {
                $elements = json_decode( get_post_meta( $page->ID, '_flavor_vbp_elements', true ), true ) ?: array();
                $matching_blocks = $this->search_in_elements( $elements, $query );

                foreach ( $matching_blocks as $block ) {
                    $results['blocks'][] = array(
                        'page_id'    => $page->ID,
                        'page_title' => $page->post_title,
                        'block_id'   => $block['id'],
                        'block_type' => $block['type'],
                        'match'      => $block['match'],
                    );
                }
            }
        }

        if ( in_array( 'widgets', $search_in, true ) ) {
            $widgets = get_option( 'flavor_vbp_widgets', array() );

            foreach ( $widgets as $widget ) {
                if ( stripos( $widget['name'], $query ) !== false ) {
                    $results['widgets'][] = $widget;
                }
            }
        }

        return new WP_REST_Response( array(
            'success' => true,
            'query'   => $query,
            'results' => $results,
            'counts'  => array(
                'pages'   => count( $results['pages'] ),
                'blocks'  => count( $results['blocks'] ),
                'widgets' => count( $results['widgets'] ),
            ),
        ), 200 );
    }

    /**
     * Busca en elementos
     *
     * @param array  $elements Elementos.
     * @param string $query Query de búsqueda.
     * @return array
     */
    private function search_in_elements( $elements, $query ) {
        $matches = array();

        foreach ( $elements as $element ) {
            $found_in = null;

            // Buscar en texto/contenido
            if ( isset( $element['props']['text'] ) && stripos( $element['props']['text'], $query ) !== false ) {
                $found_in = 'text';
            } elseif ( isset( $element['props']['content'] ) && stripos( $element['props']['content'], $query ) !== false ) {
                $found_in = 'content';
            } elseif ( isset( $element['props']['title'] ) && stripos( $element['props']['title'], $query ) !== false ) {
                $found_in = 'title';
            }

            if ( $found_in ) {
                $matches[] = array(
                    'id'    => $element['id'],
                    'type'  => $element['type'],
                    'match' => $found_in,
                );
            }

            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $matches = array_merge( $matches, $this->search_in_elements( $element['children'], $query ) );
            }
        }

        return $matches;
    }

    /**
     * Buscar y reemplazar global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function global_search_replace( $request ) {
        $search_text = $request->get_param( 'search' );
        $replace_text = $request->get_param( 'replace' );
        $page_ids = $request->get_param( 'page_ids' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $total_replacements = 0;
        $affected_pages = array();

        $pages_to_search = $page_ids ? array_map( 'intval', $page_ids ) : array();

        if ( empty( $pages_to_search ) ) {
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'fields'         => 'ids',
            ) );
            $pages_to_search = $all_pages;
        }

        foreach ( $pages_to_search as $page_id ) {
            $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
            $replacements = 0;

            $elements = $this->replace_in_elements( $elements, $search_text, $replace_text, $replacements );

            if ( $replacements > 0 ) {
                $total_replacements += $replacements;
                $affected_pages[] = array(
                    'id'           => $page_id,
                    'title'        => get_the_title( $page_id ),
                    'replacements' => $replacements,
                );

                if ( ! $dry_run ) {
                    update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                }
            }
        }

        return new WP_REST_Response( array(
            'success'            => true,
            'dry_run'            => $dry_run,
            'total_replacements' => $total_replacements,
            'affected_pages'     => $affected_pages,
            'message'            => $dry_run ? 'Vista previa de reemplazos.' : 'Reemplazos aplicados.',
        ), 200 );
    }

    /**
     * Reemplaza en elementos
     *
     * @param array  $elements Elementos.
     * @param string $search Texto a buscar.
     * @param string $replace Texto de reemplazo.
     * @param int    $count Contador de reemplazos.
     * @return array
     */
    private function replace_in_elements( $elements, $search, $replace, &$count ) {
        foreach ( $elements as &$element ) {
            if ( isset( $element['props']['text'] ) ) {
                $new_text = str_ireplace( $search, $replace, $element['props']['text'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['text'] = $new_text;
                    $count += $replacements;
                }
            }
            if ( isset( $element['props']['content'] ) ) {
                $new_content = str_ireplace( $search, $replace, $element['props']['content'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['content'] = $new_content;
                    $count += $replacements;
                }
            }
            if ( isset( $element['props']['title'] ) ) {
                $new_title = str_ireplace( $search, $replace, $element['props']['title'], $replacements );
                if ( $replacements > 0 ) {
                    $element['props']['title'] = $new_title;
                    $count += $replacements;
                }
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->replace_in_elements( $element['children'], $search, $replace, $count );
            }
        }
        return $elements;
    }

    /**
     * Busca bloques por tipo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function search_blocks_by_type( $request ) {
        $block_type = sanitize_text_field( $request->get_param( 'type' ) );
        $page_ids = $request->get_param( 'page_ids' );

        $results = array();

        $pages_to_search = $page_ids ? array_map( 'intval', $page_ids ) : array();

        if ( empty( $pages_to_search ) ) {
            $all_pages = get_posts( array(
                'post_type'      => 'flavor_landing',
                'posts_per_page' => flavor_safe_posts_limit( -1 ),
                'fields'         => 'ids',
            ) );
            $pages_to_search = $all_pages;
        }

        foreach ( $pages_to_search as $page_id ) {
            $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
            $found_blocks = $this->find_elements_by_type( $elements, $block_type );

            if ( ! empty( $found_blocks ) ) {
                $results[] = array(
                    'page_id'    => $page_id,
                    'page_title' => get_the_title( $page_id ),
                    'blocks'     => array_map( function( $block ) {
                        return array(
                            'id'    => $block['id'],
                            'props' => $block['props'] ?? array(),
                        );
                    }, $found_blocks ),
                    'count'      => count( $found_blocks ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'block_type'  => $block_type,
            'results'     => $results,
            'total_found' => array_sum( array_column( $results, 'count' ) ),
        ), 200 );
    }

    // =============================================
}
