<?php
/**
 * Trait para búsqueda, historial y sitemap VBP
 *
 * Este trait contiene todos los métodos relacionados con
 * búsqueda en páginas VBP, historial de versiones y generación de sitemaps.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Search
 *
 * Contiene métodos para:
 * - Búsqueda en páginas VBP (search_vbp_pages, find_text_in_blocks)
 * - Historial de versiones (get_page_history, restore_page_version)
 * - Generación de sitemaps (get_vbp_sitemap, formatos XML/HTML/JSON)
 */
trait VBP_API_Search {

    /**
     * Busca en páginas VBP por texto, título o contenido de bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function search_vbp_pages( $request ) {
        global $wpdb;

        $search_query = sanitize_text_field( $request->get_param( 'q' ) );
        $search_type = $request->get_param( 'type' );
        $post_status = $request->get_param( 'status' );
        $per_page = (int) $request->get_param( 'per_page' );
        $page = (int) $request->get_param( 'page' );
        $offset = ( $page - 1 ) * $per_page;

        if ( strlen( $search_query ) < 2 ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El término de búsqueda debe tener al menos 2 caracteres.',
            ), 400 );
        }

        $search_like = '%' . $wpdb->esc_like( $search_query ) . '%';

        // Construir query base
        $sql_where = array();
        $sql_params = array();

        // Filtrar por tipo de búsqueda
        if ( $search_type === 'title' || $search_type === 'all' ) {
            $sql_where[] = 'p.post_title LIKE %s';
            $sql_params[] = $search_like;
        }

        if ( $search_type === 'content' || $search_type === 'all' ) {
            $sql_where[] = 'p.post_content LIKE %s';
            $sql_params[] = $search_like;
        }

        // Para bloques, buscar en meta
        $search_in_blocks = ( $search_type === 'blocks' || $search_type === 'all' );

        // Filtrar por estado
        $status_clause = '';
        if ( $post_status !== 'any' ) {
            $status_clause = $wpdb->prepare( ' AND p.post_status = %s', $post_status );
        } else {
            $status_clause = " AND p.post_status IN ('publish', 'draft', 'private')";
        }

        $where_clause = implode( ' OR ', $sql_where );

        // Query principal
        $sql = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.ID, p.post_title, p.post_status, p.post_date, p.post_modified
             FROM {$wpdb->posts} p
             WHERE p.post_type = 'flavor_landing'
             {$status_clause}
             AND ({$where_clause})
             ORDER BY p.post_modified DESC
             LIMIT %d OFFSET %d",
            array_merge( $sql_params, array( $per_page, $offset ) )
        );

        $pages = $wpdb->get_results( $sql );
        $total_results = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        // Si se busca en bloques, filtrar adicionalmente
        if ( $search_in_blocks && $search_type !== 'all' ) {
            $pages_with_blocks = array();
            $meta_sql = $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_status, p.post_date, p.post_modified
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'flavor_landing'
                 {$status_clause}
                 AND pm.meta_key = '_flavor_vbp_elements'
                 AND pm.meta_value LIKE %s
                 ORDER BY p.post_modified DESC
                 LIMIT %d OFFSET %d",
                $search_like,
                $per_page,
                $offset
            );
            $pages = $wpdb->get_results( $meta_sql );
            $total_results = count( $pages );
        }

        // Enriquecer resultados
        $results = array();
        foreach ( $pages as $page_data ) {
            $page_id = $page_data->ID;
            $elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            $block_count = is_array( $elements ) ? $this->count_total_blocks( $elements ) : 0;

            // Encontrar coincidencias en bloques
            $block_matches = array();
            if ( is_array( $elements ) && $search_in_blocks ) {
                $block_matches = $this->find_text_in_blocks( $elements, $search_query );
            }

            $results[] = array(
                'id'            => $page_id,
                'title'         => $page_data->post_title,
                'status'        => $page_data->post_status,
                'url'           => get_permalink( $page_id ),
                'edit_url'      => admin_url( "post.php?post={$page_id}&action=edit" ),
                'date_created'  => $page_data->post_date,
                'date_modified' => $page_data->post_modified,
                'block_count'   => $block_count,
                'matches'       => array(
                    'title'   => stripos( $page_data->post_title, $search_query ) !== false,
                    'blocks'  => $block_matches,
                ),
            );
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'query'       => $search_query,
            'type'        => $search_type,
            'total'       => $total_results,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_results / $per_page ),
            'results'     => $results,
        ), 200 );
    }

    /**
     * Busca texto en bloques VBP recursivamente
     *
     * @param array  $elements Array de elementos VBP.
     * @param string $search_query Texto a buscar.
     * @return array
     */
    private function find_text_in_blocks( $elements, $search_query ) {
        $matches = array();
        $search_lower = strtolower( $search_query );

        foreach ( $elements as $element ) {
            $block_type = $element['type'] ?? 'unknown';
            $props = $element['props'] ?? array();

            // Buscar en propiedades de texto comunes
            $text_props = array( 'text', 'content', 'title', 'subtitle', 'description', 'label', 'placeholder' );
            foreach ( $text_props as $prop ) {
                if ( isset( $props[ $prop ] ) && is_string( $props[ $prop ] ) ) {
                    if ( stripos( $props[ $prop ], $search_query ) !== false ) {
                        $matches[] = array(
                            'block_type' => $block_type,
                            'property'   => $prop,
                            'snippet'    => $this->get_text_snippet( $props[ $prop ], $search_query, 50 ),
                        );
                    }
                }
            }

            // Buscar en children
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $child_matches = $this->find_text_in_blocks( $element['children'], $search_query );
                $matches = array_merge( $matches, $child_matches );
            }
        }

        return $matches;
    }

    /**
     * Obtiene un snippet de texto con el término de búsqueda destacado
     *
     * @param string $text Texto completo.
     * @param string $query Término buscado.
     * @param int    $context_chars Caracteres de contexto.
     * @return string
     */
    private function get_text_snippet( $text, $query, $context_chars = 50 ) {
        $position = stripos( $text, $query );
        if ( $position === false ) {
            return substr( $text, 0, $context_chars * 2 ) . '...';
        }

        $start = max( 0, $position - $context_chars );
        $length = strlen( $query ) + ( $context_chars * 2 );
        $snippet = substr( $text, $start, $length );

        if ( $start > 0 ) {
            $snippet = '...' . $snippet;
        }
        if ( $start + $length < strlen( $text ) ) {
            $snippet .= '...';
        }

        return $snippet;
    }

    /**
     * Obtiene el historial de versiones de una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_history( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $per_page = (int) $request->get_param( 'per_page' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada.',
            ), 404 );
        }

        // Obtener revisiones
        $revisions = wp_get_post_revisions( $page_id, array(
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $history = array();
        foreach ( $revisions as $revision ) {
            $revision_elements = get_post_meta( $revision->ID, '_flavor_vbp_elements', true );
            $block_count = is_array( $revision_elements ) ? $this->count_total_blocks( $revision_elements ) : 0;

            $author = get_user_by( 'id', $revision->post_author );

            $history[] = array(
                'revision_id'   => $revision->ID,
                'date'          => $revision->post_date,
                'date_gmt'      => $revision->post_date_gmt,
                'author'        => array(
                    'id'   => $revision->post_author,
                    'name' => $author ? $author->display_name : 'Desconocido',
                ),
                'title'         => $revision->post_title,
                'block_count'   => $block_count,
                'is_autosave'   => wp_is_post_autosave( $revision->ID ),
                'diff_url'      => admin_url( "revision.php?revision={$revision->ID}" ),
            );
        }

        // Estado actual
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $current_author = get_user_by( 'id', $post->post_author );

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'current'  => array(
                'title'        => $post->post_title,
                'status'       => $post->post_status,
                'date'         => $post->post_modified,
                'author'       => array(
                    'id'   => $post->post_author,
                    'name' => $current_author ? $current_author->display_name : 'Desconocido',
                ),
                'block_count'  => is_array( $current_elements ) ? $this->count_total_blocks( $current_elements ) : 0,
            ),
            'revisions_count' => count( $revisions ),
            'revisions'       => $history,
        ), 200 );
    }

    /**
     * Restaura una versión específica de una página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function restore_page_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $revision_id = (int) $request->get_param( 'revision_id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada.',
            ), 404 );
        }

        $revision = get_post( $revision_id );
        if ( ! $revision || $revision->post_parent !== $page_id ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Revisión no encontrada o no pertenece a esta página.',
            ), 404 );
        }

        // Guardar estado actual antes de restaurar (por si acaso)
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        update_post_meta( $page_id, '_flavor_vbp_elements_backup', $current_elements );
        update_post_meta( $page_id, '_flavor_vbp_backup_date', current_time( 'mysql' ) );

        // Restaurar contenido de la revisión
        $restored = wp_restore_post_revision( $revision_id );

        if ( ! $restored ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'No se pudo restaurar la revisión.',
            ), 500 );
        }

        // Restaurar meta de elementos VBP si existe
        $revision_elements = get_post_meta( $revision_id, '_flavor_vbp_elements', true );
        if ( ! empty( $revision_elements ) ) {
            update_post_meta( $page_id, '_flavor_vbp_elements', $revision_elements );
        }

        // Limpiar caché
        clean_post_cache( $page_id );

        return new WP_REST_Response( array(
            'success'         => true,
            'message'         => 'Página restaurada correctamente.',
            'page_id'         => $page_id,
            'restored_from'   => $revision_id,
            'restored_date'   => $revision->post_date,
            'backup_created'  => true,
            'current_url'     => get_permalink( $page_id ),
        ), 200 );
    }

    /**
     * Genera un sitemap de páginas VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function get_vbp_sitemap( $request ) {
        $format = $request->get_param( 'format' );
        $include_drafts = (bool) $request->get_param( 'include_drafts' );

        $post_statuses = array( 'publish' );
        if ( $include_drafts ) {
            $post_statuses[] = 'draft';
        }

        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => $post_statuses,
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );

        $sitemap_entries = array();
        foreach ( $pages as $page ) {
            $elements = get_post_meta( $page->ID, '_flavor_vbp_elements', true );
            $block_count = is_array( $elements ) ? $this->count_total_blocks( $elements ) : 0;

            $entry = array(
                'id'            => $page->ID,
                'title'         => $page->post_title,
                'slug'          => $page->post_name,
                'url'           => get_permalink( $page->ID ),
                'status'        => $page->post_status,
                'parent'        => $page->post_parent,
                'menu_order'    => $page->menu_order,
                'date_modified' => $page->post_modified,
                'block_count'   => $block_count,
            );

            // Obtener metadatos SEO si existen
            $seo_title = get_post_meta( $page->ID, '_yoast_wpseo_title', true ) ?: get_post_meta( $page->ID, '_flavor_seo_title', true );
            $seo_desc = get_post_meta( $page->ID, '_yoast_wpseo_metadesc', true ) ?: get_post_meta( $page->ID, '_flavor_seo_description', true );

            if ( $seo_title ) {
                $entry['seo_title'] = $seo_title;
            }
            if ( $seo_desc ) {
                $entry['seo_description'] = $seo_desc;
            }

            // Verificar si es homepage
            if ( (int) get_option( 'page_on_front' ) === $page->ID ) {
                $entry['is_homepage'] = true;
            }

            $sitemap_entries[] = $entry;
        }

        // Construir estructura jerárquica
        $hierarchical = $this->build_page_hierarchy( $sitemap_entries );

        // Responder según formato
        if ( $format === 'xml' ) {
            return $this->generate_xml_sitemap( $sitemap_entries );
        } elseif ( $format === 'html' ) {
            return $this->generate_html_sitemap( $hierarchical );
        }

        // Formato JSON (default)
        return new WP_REST_Response( array(
            'success'      => true,
            'total_pages'  => count( $sitemap_entries ),
            'site_url'     => home_url(),
            'generated_at' => current_time( 'c' ),
            'pages'        => $sitemap_entries,
            'hierarchy'    => $hierarchical,
        ), 200 );
    }

    /**
     * Construye una jerarquía de páginas
     *
     * @param array $pages Lista plana de páginas.
     * @return array
     */
    private function build_page_hierarchy( $pages ) {
        $hierarchy = array();
        $pages_by_id = array();

        // Indexar por ID
        foreach ( $pages as $page ) {
            $pages_by_id[ $page['id'] ] = $page;
            $pages_by_id[ $page['id'] ]['children'] = array();
        }

        // Construir jerarquía
        foreach ( $pages_by_id as $id => $page ) {
            if ( $page['parent'] > 0 && isset( $pages_by_id[ $page['parent'] ] ) ) {
                $pages_by_id[ $page['parent'] ]['children'][] = &$pages_by_id[ $id ];
            } else {
                $hierarchy[] = &$pages_by_id[ $id ];
            }
        }

        return $hierarchy;
    }

    /**
     * Genera sitemap en formato XML
     *
     * @param array $pages Lista de páginas.
     * @return WP_REST_Response
     */
    private function generate_xml_sitemap( $pages ) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $pages as $page ) {
            if ( $page['status'] !== 'publish' ) {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url( $page['url'] ) . "</loc>\n";
            $xml .= "    <lastmod>" . date( 'c', strtotime( $page['date_modified'] ) ) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";

            // Prioridad basada en jerarquía
            $priority = $page['parent'] > 0 ? '0.6' : '0.8';
            if ( isset( $page['is_homepage'] ) && $page['is_homepage'] ) {
                $priority = '1.0';
            }
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return new WP_REST_Response( array(
            'success'      => true,
            'format'       => 'xml',
            'content_type' => 'application/xml',
            'xml'          => $xml,
        ), 200, array(
            'Content-Type' => 'application/xml; charset=utf-8',
        ) );
    }

    /**
     * Genera sitemap en formato HTML
     *
     * @param array $hierarchy Jerarquía de páginas.
     * @return WP_REST_Response
     */
    private function generate_html_sitemap( $hierarchy ) {
        $html = '<nav class="vbp-sitemap" role="navigation" aria-label="Sitemap">' . "\n";
        $html .= $this->render_sitemap_list( $hierarchy );
        $html .= '</nav>';

        return new WP_REST_Response( array(
            'success'      => true,
            'format'       => 'html',
            'content_type' => 'text/html',
            'html'         => $html,
        ), 200 );
    }

    /**
     * Renderiza lista de sitemap recursivamente
     *
     * @param array $pages Lista de páginas.
     * @param int   $level Nivel de anidación.
     * @return string
     */
    private function render_sitemap_list( $pages, $level = 0 ) {
        if ( empty( $pages ) ) {
            return '';
        }

        $indent = str_repeat( '  ', $level );
        $html = "{$indent}<ul class=\"sitemap-level-{$level}\">\n";

        foreach ( $pages as $page ) {
            $classes = array( 'sitemap-item' );
            if ( isset( $page['is_homepage'] ) && $page['is_homepage'] ) {
                $classes[] = 'is-homepage';
            }
            if ( $page['status'] !== 'publish' ) {
                $classes[] = 'is-draft';
            }

            $class_attr = implode( ' ', $classes );
            $html .= "{$indent}  <li class=\"{$class_attr}\">\n";
            $html .= "{$indent}    <a href=\"" . esc_url( $page['url'] ) . "\">" . esc_html( $page['title'] ) . "</a>\n";

            if ( ! empty( $page['children'] ) ) {
                $html .= $this->render_sitemap_list( $page['children'], $level + 1 );
            }

            $html .= "{$indent}  </li>\n";
        }

        $html .= "{$indent}</ul>\n";

        return $html;
    }
}
