<?php
/**
 * Trait para Operaciones Avanzadas de Bloques VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_BlockOperations {

    /**
     * Transfiere bloque entre páginas
     */
    public function transfer_block_between_pages( $request ) {
        $source_page_id = (int) $request->get_param( 'source_page_id' );
        $target_page_id = (int) $request->get_param( 'target_page_id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $position = (int) $request->get_param( 'position' );
        $mode = $request->get_param( 'mode' );

        $source_post = get_post( $source_page_id );
        $target_post = get_post( $target_page_id );

        if ( ! $source_post || $source_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página origen no encontrada.' ), 404 );
        }
        if ( ! $target_post || $target_post->post_type !== 'flavor_landing' ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página destino no encontrada.' ), 404 );
        }

        $source_elements = json_decode( get_post_meta( $source_page_id, '_flavor_vbp_elements', true ), true ) ?: array();
        $target_elements = json_decode( get_post_meta( $target_page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_to_transfer = null;
        $block_index = null;
        foreach ( $source_elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_to_transfer = $element;
                $block_index = $index;
                break;
            }
        }

        if ( ! $block_to_transfer ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado en página origen.' ), 404 );
        }

        $new_block = $block_to_transfer;
        $new_block['id'] = 'block_' . uniqid();

        if ( $position < 0 || $position >= count( $target_elements ) ) {
            $target_elements[] = $new_block;
        } else {
            array_splice( $target_elements, $position, 0, array( $new_block ) );
        }

        update_post_meta( $target_page_id, '_flavor_vbp_elements', wp_json_encode( $target_elements ) );

        if ( $mode === 'move' ) {
            unset( $source_elements[ $block_index ] );
            $source_elements = array_values( $source_elements );
            update_post_meta( $source_page_id, '_flavor_vbp_elements', wp_json_encode( $source_elements ) );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => $mode === 'move' ? 'Bloque movido.' : 'Bloque copiado.',
            'new_block_id' => $new_block['id'],
        ), 200 );
    }

    /**
     * Convierte tipo de bloque
     */
    public function convert_block_type( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $new_type = sanitize_text_field( $request->get_param( 'new_type' ) );
        $preserve_content = (bool) $request->get_param( 'preserve_content' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $converted = false;
        $old_type = '';
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $old_type = $element['type'] ?? 'unknown';
                $element['type'] = $new_type;

                if ( ! $preserve_content ) {
                    $element['data'] = array();
                }

                $converted = true;
                break;
            }
        }

        if ( ! $converted ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => "Bloque convertido de {$old_type} a {$new_type}.",
            'old_type' => $old_type,
            'new_type' => $new_type,
        ), 200 );
    }

    /**
     * Extrae bloque como widget reutilizable
     */
    public function extract_block_as_widget( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $widget_name = sanitize_text_field( $request->get_param( 'widget_name' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $replace_with_reference = (bool) $request->get_param( 'replace_with_reference' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $block_data = null;
        $block_index = null;
        foreach ( $elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $block_data = $element;
                $block_index = $index;
                break;
            }
        }

        if ( ! $block_data ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        $widget_id = 'widget_' . sanitize_title( $widget_name ) . '_' . uniqid();
        $widgets = get_option( 'flavor_vbp_widgets', array() );

        $widgets[ $widget_id ] = array(
            'id'         => $widget_id,
            'name'       => $widget_name,
            'category'   => $category,
            'block'      => $block_data,
            'created_at' => current_time( 'mysql' ),
            'version'    => 1,
        );

        update_option( 'flavor_vbp_widgets', $widgets );

        if ( $replace_with_reference ) {
            $elements[ $block_index ] = array(
                'id'        => $block_data['id'],
                'type'      => 'widget_reference',
                'widget_id' => $widget_id,
            );
            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Widget creado exitosamente.',
            'widget_id' => $widget_id,
        ), 201 );
    }

    /**
     * Agrupa bloques
     */
    public function group_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $wrapper_type = $request->get_param( 'wrapper_type' );
        $wrapper_styles = $request->get_param( 'wrapper_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $blocks_to_group = array();
        $first_index = null;
        $indices_to_remove = array();

        foreach ( $elements as $index => $element ) {
            if ( in_array( $element['id'] ?? '', $block_ids, true ) ) {
                $blocks_to_group[] = $element;
                $indices_to_remove[] = $index;
                if ( $first_index === null ) {
                    $first_index = $index;
                }
            }
        }

        if ( count( $blocks_to_group ) < 2 ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Se necesitan al menos 2 bloques para agrupar.' ), 400 );
        }

        $group_block = array(
            'id'       => 'group_' . uniqid(),
            'type'     => $wrapper_type,
            'styles'   => $wrapper_styles,
            'children' => $blocks_to_group,
        );

        foreach ( array_reverse( $indices_to_remove ) as $idx ) {
            unset( $elements[ $idx ] );
        }
        $elements = array_values( $elements );

        array_splice( $elements, $first_index, 0, array( $group_block ) );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => 'Bloques agrupados.',
            'group_id' => $group_block['id'],
        ), 200 );
    }

    /**
     * Desagrupa bloque contenedor
     */
    public function ungroup_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $group_index = null;
        $children = array();
        foreach ( $elements as $index => $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                if ( empty( $element['children'] ) ) {
                    return new WP_REST_Response( array( 'success' => false, 'error' => 'El bloque no tiene hijos para desagrupar.' ), 400 );
                }
                $group_index = $index;
                $children = $element['children'];
                break;
            }
        }

        if ( $group_index === null ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque no encontrado.' ), 404 );
        }

        unset( $elements[ $group_index ] );
        $elements = array_values( $elements );
        array_splice( $elements, $group_index, 0, $children );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => 'Bloque desagrupado.',
            'children_ids' => array_column( $children, 'id' ),
        ), 200 );
    }

    /**
     * Clona estilos entre bloques
     */
    public function clone_block_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $source_block_id = sanitize_text_field( $request->get_param( 'source_block_id' ) );
        $target_block_ids = $request->get_param( 'target_block_ids' );
        $style_properties = $request->get_param( 'style_properties' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $source_styles = null;
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $source_block_id ) {
                $source_styles = $element['styles'] ?? array();
                break;
            }
        }

        if ( $source_styles === null ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque origen no encontrado.' ), 404 );
        }

        if ( ! empty( $style_properties ) ) {
            $filtered_styles = array();
            foreach ( $style_properties as $prop ) {
                if ( isset( $source_styles[ $prop ] ) ) {
                    $filtered_styles[ $prop ] = $source_styles[ $prop ];
                }
            }
            $source_styles = $filtered_styles;
        }

        $updated_count = 0;
        foreach ( $elements as &$element ) {
            if ( in_array( $element['id'] ?? '', $target_block_ids, true ) ) {
                $element['styles'] = array_merge( $element['styles'] ?? array(), $source_styles );
                $updated_count++;
            }
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Estilos clonados a {$updated_count} bloques.",
            'updated' => $updated_count,
        ), 200 );
    }

    /**
     * Reordena bloques en lote
     */
    public function reorder_blocks_batch( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $new_order = $request->get_param( 'new_order' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $elements_by_id = array();
        foreach ( $elements as $element ) {
            $elements_by_id[ $element['id'] ?? '' ] = $element;
        }

        $reordered_elements = array();
        foreach ( $new_order as $block_id ) {
            if ( isset( $elements_by_id[ $block_id ] ) ) {
                $reordered_elements[] = $elements_by_id[ $block_id ];
                unset( $elements_by_id[ $block_id ] );
            }
        }

        foreach ( $elements_by_id as $remaining ) {
            $reordered_elements[] = $remaining;
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $reordered_elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Bloques reordenados.',
            'count'   => count( $reordered_elements ),
        ), 200 );
    }

    /**
     * Valida estructura de bloques de página
     */
    public function validate_page_blocks_structure( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $issues = array();
        $ids_seen = array();

        $this->validate_block_elements_recursive( $elements, $issues, $ids_seen );

        return new WP_REST_Response( array(
            'success' => true,
            'valid'   => empty( $issues ),
            'issues'  => $issues,
            'stats'   => array(
                'total_blocks' => count( $ids_seen ),
                'issues_count' => count( $issues ),
            ),
        ), 200 );
    }

    /**
     * Valida elementos recursivamente
     */
    private function validate_block_elements_recursive( $elements, &$issues, &$ids_seen ) {
        foreach ( $elements as $index => $element ) {
            $element_id = $element['id'] ?? '';

            if ( empty( $element_id ) ) {
                $issues[] = array( 'type' => 'missing_id', 'index' => $index );
            } elseif ( isset( $ids_seen[ $element_id ] ) ) {
                $issues[] = array( 'type' => 'duplicate_id', 'id' => $element_id );
            } else {
                $ids_seen[ $element_id ] = true;
            }

            if ( empty( $element['type'] ) ) {
                $issues[] = array( 'type' => 'missing_type', 'id' => $element_id );
            }

            if ( ! empty( $element['children'] ) ) {
                $this->validate_block_elements_recursive( $element['children'], $issues, $ids_seen );
            }
        }
    }

    /**
     * Repara bloques rotos
     */
    public function repair_broken_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $repairs = array();
        $ids_seen = array();

        $elements = $this->repair_block_elements_recursive( $elements, $repairs, $ids_seen );

        if ( ! $dry_run && ! empty( $repairs ) ) {
            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'dry_run' => $dry_run,
            'repairs' => $repairs,
            'count'   => count( $repairs ),
        ), 200 );
    }

    /**
     * Repara elementos recursivamente
     */
    private function repair_block_elements_recursive( $elements, &$repairs, &$ids_seen ) {
        foreach ( $elements as &$element ) {
            $original_id = $element['id'] ?? '';

            if ( empty( $original_id ) ) {
                $element['id'] = 'block_' . uniqid();
                $repairs[] = array( 'type' => 'added_id', 'new_id' => $element['id'] );
            }

            if ( isset( $ids_seen[ $element['id'] ] ) ) {
                $old_id = $element['id'];
                $element['id'] = 'block_' . uniqid();
                $repairs[] = array( 'type' => 'fixed_duplicate', 'old_id' => $old_id, 'new_id' => $element['id'] );
            }

            $ids_seen[ $element['id'] ] = true;

            if ( empty( $element['type'] ) ) {
                $element['type'] = 'container';
                $repairs[] = array( 'type' => 'added_type', 'id' => $element['id'] );
            }

            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->repair_block_elements_recursive( $element['children'], $repairs, $ids_seen );
            }
        }

        return $elements;
    }

    /**
     * Obtiene árbol de bloques
     */
    public function get_blocks_tree( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_data = (bool) $request->get_param( 'include_data' );
        $include_styles = (bool) $request->get_param( 'include_styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();

        $tree = $this->build_blocks_tree_recursive( $elements, $include_data, $include_styles );

        return new WP_REST_Response( array(
            'success' => true,
            'tree'    => $tree,
        ), 200 );
    }

    /**
     * Construye árbol de bloques
     */
    private function build_blocks_tree_recursive( $elements, $include_data, $include_styles ) {
        $tree = array();
        foreach ( $elements as $element ) {
            $node = array(
                'id'   => $element['id'] ?? '',
                'type' => $element['type'] ?? 'unknown',
            );

            if ( $include_data && isset( $element['data'] ) ) {
                $node['data'] = $element['data'];
            }

            if ( $include_styles && isset( $element['styles'] ) ) {
                $node['styles'] = $element['styles'];
            }

            if ( ! empty( $element['children'] ) ) {
                $node['children'] = $this->build_blocks_tree_recursive( $element['children'], $include_data, $include_styles );
            }

            $tree[] = $node;
        }
        return $tree;
    }

    // =============================================
}
