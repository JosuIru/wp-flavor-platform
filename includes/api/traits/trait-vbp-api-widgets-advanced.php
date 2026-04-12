<?php
/**
 * Trait para Widgets Avanzados VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_WidgetsAdvanced {

    /**
     * Importa widget desde otra página
     */
    public function import_widget_from_page( $request ) {
        $source_page_id = (int) $request->get_param( 'source_page_id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_name = sanitize_text_field( $request->get_param( 'widget_name' ) );

        $source_post = get_post( $source_page_id );
        if ( ! $source_post || $source_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página origen no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $source_page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_data = null;
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_data = $element;
                break;
            }
        }

        if ( ! $block_data ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $widget_id = 'widget_' . sanitize_title( $widget_name ) . '_' . uniqid();
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $widgets[ $widget_id ] = array(
            'id'          => $widget_id,
            'name'        => $widget_name,
            'category'    => 'imported',
            'block'       => $block_data,
            'source_page' => $source_page_id,
            'created_at'  => current_time( 'mysql' ),
            'version'     => 1,
        );

        update_option( 'flavor_vbp_widgets', $widgets );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Widget importado exitosamente.',
            'widget_id' => $widget_id,
        ), 201 );
    }

    /**
     * Detecta widgets no usados
     */
    public function detect_unused_widgets( $request ) {
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $limit = flavor_safe_posts_limit( -1 );

        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
        ) );

        $used_widgets = array();
        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json ) {
                $this->find_widget_refs( json_decode( $elements_json, true ) ?: array(), $used_widgets );
            }
        }

        $unused_widgets = array();
        foreach ( $widgets as $widget_id => $widget ) {
            if ( ! isset( $used_widgets[ $widget_id ] ) ) {
                $unused_widgets[] = array(
                    'id'         => $widget_id,
                    'name'       => $widget['name'] ?? $widget_id,
                    'created_at' => $widget['created_at'] ?? null,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'total_widgets'  => count( $widgets ),
            'unused_widgets' => $unused_widgets,
            'unused_count'   => count( $unused_widgets ),
        ), 200 );
    }

    /**
     * Busca referencias a widgets
     */
    private function find_widget_refs( $elements, &$used_widgets ) {
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === 'widget_reference' && ! empty( $element['widget_id'] ) ) {
                $used_widgets[ $element['widget_id'] ] = true;
            }
            if ( ! empty( $element['children'] ) ) {
                $this->find_widget_refs( $element['children'], $used_widgets );
            }
        }
    }

    /**
     * Actualiza widget en todas las instancias
     */
    public function update_widget_all_instances( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $new_block = $request->get_param( 'block' );
        $notify_pages = (bool) $request->get_param( 'notify_pages' );

        $widgets = get_option( 'flavor_vbp_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $widgets[ $widget_id ]['block'] = $new_block;
        $widgets[ $widget_id ]['version'] = ( $widgets[ $widget_id ]['version'] ?? 0 ) + 1;
        $widgets[ $widget_id ]['updated_at'] = current_time( 'mysql' );

        update_option( 'flavor_vbp_widgets', $widgets );

        $affected_pages = array();
        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'fields'         => 'ids',
        ) );

        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json && strpos( $elements_json, $widget_id ) !== false ) {
                $affected_pages[] = $page_id;
            }
        }

        return new WP_REST_Response( array(
            'success'        => true,
            'message'        => 'Widget actualizado.',
            'version'        => $widgets[ $widget_id ]['version'],
            'affected_pages' => $affected_pages,
        ), 200 );
    }

    /**
     * Convierte bloque a referencia de widget
     */
    public function convert_block_to_widget_reference( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $widgets = get_option( 'flavor_vbp_widgets', array() );
        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $converted = false;
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $element = array(
                    'id'        => $block_id,
                    'type'      => 'widget_reference',
                    'widget_id' => $widget_id,
                );
                $converted = true;
                break;
            }
        }

        if ( ! $converted ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Bloque convertido a referencia de widget.',
            'widget_id' => $widget_id,
        ), 200 );
    }

    /**
     * Obtiene estadísticas de uso de widgets
     */
    public function get_widgets_usage_stats( $request ) {
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $pages_query = new WP_Query( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'fields'         => 'ids',
        ) );

        $usage_stats = array();
        foreach ( $widgets as $widget_id => $widget ) {
            $usage_stats[ $widget_id ] = array(
                'name'      => $widget['name'] ?? $widget_id,
                'category'  => $widget['category'] ?? 'uncategorized',
                'version'   => $widget['version'] ?? 1,
                'instances' => 0,
                'pages'     => array(),
            );
        }

        foreach ( $pages_query->posts as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( $elements_json ) {
                $elements = json_decode( $elements_json, true ) ?: array();
                $this->count_widget_refs( $elements, $usage_stats, $page_id );
            }
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'total'        => count( $widgets ),
            'usage_stats'  => array_values( $usage_stats ),
        ), 200 );
    }

    /**
     * Cuenta uso de widgets
     */
    private function count_widget_refs( $elements, &$usage_stats, $page_id ) {
        foreach ( $elements as $element ) {
            if ( ( $element['type'] ?? '' ) === 'widget_reference' && ! empty( $element['widget_id'] ) ) {
                $widget_id = $element['widget_id'];
                if ( isset( $usage_stats[ $widget_id ] ) ) {
                    $usage_stats[ $widget_id ]['instances']++;
                    if ( ! in_array( $page_id, $usage_stats[ $widget_id ]['pages'], true ) ) {
                        $usage_stats[ $widget_id ]['pages'][] = $page_id;
                    }
                }
            }
            if ( ! empty( $element['children'] ) ) {
                $this->count_widget_refs( $element['children'], $usage_stats, $page_id );
            }
        }
    }

    // =============================================
}
