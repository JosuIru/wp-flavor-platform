<?php
/**
 * Trait para exportación e importación VBP
 *
 * Este trait contiene métodos para exportar e importar
 * páginas y configuraciones VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_ExportImport
 *
 * Contiene métodos para:
 * - Exportación de páginas (export_all_pages)
 * - Importación de páginas (import_all_pages)
 */
trait VBP_API_ExportImport {

    /**
     * Exporta todas las páginas VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_all_pages( $request ) {
        $include_drafts = (bool) $request->get_param( 'include_drafts' );
        $include_media = (bool) $request->get_param( 'include_media' );

        $statuses = array( 'publish' );
        if ( $include_drafts ) {
            $statuses[] = 'draft';
            $statuses[] = 'future';
        }

        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'post_status'    => $statuses,
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'orderby'        => 'date',
            'order'          => 'ASC',
        ) );

        $export_data = array(
            'version'     => '1.0',
            'exported_at' => current_time( 'mysql' ),
            'site_url'    => home_url(),
            'pages'       => array(),
            'widgets'     => get_option( 'flavor_vbp_global_widgets', array() ),
            'templates'   => get_option( 'flavor_vbp_block_templates', array() ),
            'styles'      => get_option( 'flavor_vbp_global_styles', array() ),
        );

        $media_urls = array();

        foreach ( $pages as $page ) {
            $elements_json = get_post_meta( $page->ID, '_flavor_vbp_elements', true );
            $elements = $elements_json ? json_decode( $elements_json, true ) : array();

            $page_data = array(
                'id'         => $page->ID,
                'title'      => $page->post_title,
                'slug'       => $page->post_name,
                'status'     => $page->post_status,
                'created'    => $page->post_date,
                'modified'   => $page->post_modified,
                'elements'   => $elements,
                'styles'     => get_post_meta( $page->ID, '_flavor_vbp_styles', true ),
                'settings'   => get_post_meta( $page->ID, '_flavor_vbp_page_settings', true ),
                'tags'       => get_post_meta( $page->ID, '_flavor_vbp_tags', true ),
            );

            $export_data['pages'][] = $page_data;

            // Recopilar URLs de medios si se solicita
            if ( $include_media ) {
                $media_urls = array_merge( $media_urls, $this->extract_media_urls( $elements ) );
            }
        }

        if ( $include_media && ! empty( $media_urls ) ) {
            $export_data['media'] = array_unique( $media_urls );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $export_data,
        ), 200 );
    }

    /**
     * Extrae URLs de medios de los elementos
     *
     * @param array $elements Elementos.
     * @return array
     */
    private function extract_media_urls( $elements ) {
        $urls = array();

        foreach ( $elements as $element ) {
            // Buscar propiedades de imagen/video
            if ( ! empty( $element['data']['src'] ) ) {
                $urls[] = $element['data']['src'];
            }
            if ( ! empty( $element['data']['backgroundImage'] ) ) {
                $urls[] = $element['data']['backgroundImage'];
            }
            if ( ! empty( $element['data']['image'] ) ) {
                $urls[] = $element['data']['image'];
            }
            if ( ! empty( $element['data']['video'] ) ) {
                $urls[] = $element['data']['video'];
            }

            // Recursivo para hijos
            if ( ! empty( $element['children'] ) ) {
                $urls = array_merge( $urls, $this->extract_media_urls( $element['children'] ) );
            }
        }

        return $urls;
    }

    /**
     * Importa páginas desde export
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function import_all_pages( $request ) {
        $data = $request->get_param( 'data' );
        $overwrite = (bool) $request->get_param( 'overwrite' );

        if ( empty( $data['pages'] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'No hay páginas para importar.',
            ), 400 );
        }

        $results = array(
            'created'  => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => array(),
        );

        foreach ( $data['pages'] as $page_data ) {
            // Buscar si existe una página con el mismo slug
            $existing = get_page_by_path( $page_data['slug'], OBJECT, 'flavor_landing' );

            if ( $existing && ! $overwrite ) {
                $results['skipped']++;
                continue;
            }

            $post_data = array(
                'post_type'    => 'flavor_landing',
                'post_title'   => $page_data['title'],
                'post_name'    => $page_data['slug'],
                'post_status'  => $page_data['status'],
                'post_content' => '',
            );

            if ( $existing && $overwrite ) {
                $post_data['ID'] = $existing->ID;
                $page_id = wp_update_post( $post_data );
                if ( ! is_wp_error( $page_id ) ) {
                    $results['updated']++;
                }
            } else {
                $page_id = wp_insert_post( $post_data );
                if ( ! is_wp_error( $page_id ) ) {
                    $results['created']++;
                }
            }

            if ( is_wp_error( $page_id ) ) {
                $results['errors'][] = array(
                    'title' => $page_data['title'],
                    'error' => $page_id->get_error_message(),
                );
                continue;
            }

            // Guardar metadatos
            if ( ! empty( $page_data['elements'] ) ) {
                update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $page_data['elements'] ) );
            }
            if ( ! empty( $page_data['styles'] ) ) {
                update_post_meta( $page_id, '_flavor_vbp_styles', $page_data['styles'] );
            }
            if ( ! empty( $page_data['settings'] ) ) {
                update_post_meta( $page_id, '_flavor_vbp_page_settings', $page_data['settings'] );
            }
            if ( ! empty( $page_data['tags'] ) ) {
                update_post_meta( $page_id, '_flavor_vbp_tags', $page_data['tags'] );
            }
        }

        // Importar widgets globales
        if ( ! empty( $data['widgets'] ) ) {
            $existing_widgets = get_option( 'flavor_vbp_global_widgets', array() );
            $merged_widgets = $overwrite ? $data['widgets'] : array_merge( $existing_widgets, $data['widgets'] );
            update_option( 'flavor_vbp_global_widgets', $merged_widgets );
        }

        // Importar templates
        if ( ! empty( $data['templates'] ) ) {
            $existing_templates = get_option( 'flavor_vbp_block_templates', array() );
            $merged_templates = $overwrite ? $data['templates'] : array_merge( $existing_templates, $data['templates'] );
            update_option( 'flavor_vbp_block_templates', $merged_templates );
        }

        // Importar estilos globales
        if ( ! empty( $data['styles'] ) && $overwrite ) {
            update_option( 'flavor_vbp_global_styles', $data['styles'] );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Importación completada.',
            'results' => $results,
        ), 200 );
    }
}
