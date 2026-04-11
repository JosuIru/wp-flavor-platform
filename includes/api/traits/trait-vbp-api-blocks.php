<?php
/**
 * Trait para operaciones de bloques VBP
 *
 * Este trait contiene todos los métodos relacionados con la manipulación
 * de bloques dentro de páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Blocks
 *
 * Contiene métodos para:
 * - Listar bloques disponibles (list_blocks)
 * - Obtener presets de bloques (get_block_presets)
 * - Añadir bloques a páginas (add_block, add_block_to_page)
 * - Obtener bloques de una página (get_page_blocks)
 * - Eliminar bloques (delete_block)
 * - Mover/reordenar bloques (move_block, reorder_page_blocks)
 * - Copiar bloques entre páginas (copy_blocks_between_pages)
 */
trait VBP_API_Blocks {

    /**
     * Lista los bloques disponibles en VBP
     *
     * @param WP_REST_Request $request Request con filtros opcionales.
     * @return WP_REST_Response
     */
    public function list_blocks( $request ) {
        if ( ! $this->ensure_vbp_loaded() ) {
            return new WP_REST_Response( array(
                'error' => 'VBP no está disponible',
            ), 500 );
        }

        $category = $request->get_param( 'category' );
        $library = Flavor_VBP_Block_Library::get_instance();
        $all_blocks = $library->get_all_blocks();

        // Filtrar por categoría si se especifica
        if ( $category ) {
            $all_blocks = array_filter( $all_blocks, function( $block ) use ( $category ) {
                return isset( $block['category'] ) && $block['category'] === $category;
            } );
        }

        // Formatear respuesta
        $blocks = array_map( function( $block ) {
            return array(
                'id'          => $block['id'] ?? '',
                'name'        => $block['name'] ?? '',
                'category'    => $block['category'] ?? 'general',
                'description' => $block['description'] ?? '',
                'icon'        => $block['icon'] ?? 'cube',
                'props'       => $block['props'] ?? array(),
            );
        }, array_values( $all_blocks ) );

        return new WP_REST_Response( array(
            'success' => true,
            'blocks'  => $blocks,
            'total'   => count( $blocks ),
        ) );
    }

    /**
     * Obtiene los presets disponibles para un tipo de bloque
     *
     * @param WP_REST_Request $request Request con tipo de bloque.
     * @return WP_REST_Response
     */
    public function get_block_presets( $request ) {
        $block_type = $request->get_param( 'type' );

        if ( ! $this->ensure_vbp_loaded() ) {
            return new WP_REST_Response( array(
                'error' => 'VBP no está disponible',
            ), 500 );
        }

        $library = Flavor_VBP_Block_Library::get_instance();
        $block_info = $library->get_block( $block_type );

        if ( ! $block_info ) {
            return new WP_REST_Response( array(
                'error' => 'Bloque no encontrado: ' . $block_type,
            ), 404 );
        }

        $presets = isset( $block_info['presets'] ) ? $block_info['presets'] : array();

        return new WP_REST_Response( array(
            'success'    => true,
            'block_type' => $block_type,
            'presets'    => $presets,
        ) );
    }

    /**
     * Añade un bloque a una página VBP (versión original)
     *
     * @param WP_REST_Request $request Request con datos del bloque.
     * @return WP_REST_Response
     */
    public function add_block( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
            ), 404 );
        }

        $block_type = $request->get_param( 'type' );
        $block_data = $request->get_param( 'data' ) ?: array();
        $position = $request->get_param( 'position' ) ?: 'end';

        // Crear elemento
        $new_element = array(
            'id'       => 'el_' . bin2hex( random_bytes( 6 ) ),
            'type'     => $block_type,
            'name'     => ucfirst( str_replace( array( '_', '-' ), ' ', $block_type ) ),
            'visible'  => true,
            'locked'   => false,
            'data'     => $block_data,
            'children' => array(),
            'styles'   => $this->get_default_styles(),
        );

        // Obtener datos VBP actuales
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();
        $elements = $vbp_data['elements'] ?? array();

        // Insertar según posición
        if ( 'start' === $position ) {
            array_unshift( $elements, $new_element );
        } else {
            $elements[] = $new_element;
        }

        $vbp_data['elements'] = $elements;
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'   => true,
            'block_id'  => $new_element['id'],
            'page_id'   => $post_id,
            'position'  => $position,
            'edit_url'  => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
        ), 201 );
    }

    /**
     * Obtiene los bloques de una página VBP
     *
     * @param WP_REST_Request $request Request con ID y opciones.
     * @return WP_REST_Response
     */
    public function get_page_blocks( $request ) {
        $post_id = $request->get_param( 'id' );
        $flat = $request->get_param( 'flat' );

        $post = get_post( $post_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );
        $elements = $vbp_data['elements'] ?? array();

        if ( $flat ) {
            $elements = $this->flatten_blocks( $elements );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'page_id' => $post_id,
            'blocks'  => $elements,
            'total'   => count( $elements ),
        ) );
    }

    /**
     * Añade un bloque a una página VBP (versión mejorada)
     *
     * @param WP_REST_Request $request Request con datos del bloque.
     * @return WP_REST_Response
     */
    public function add_block_to_page( $request ) {
        $post_id = $request->get_param( 'id' );
        $block = $request->get_param( 'block' );
        $position = $request->get_param( 'position' ) ?: 'end';
        $insert_at = $request->get_param( 'insert_at' );

        $post = get_post( $post_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada',
            ), 404 );
        }

        // Preparar bloque con ID único
        $block['id'] = $block['id'] ?? 'el_' . bin2hex( random_bytes( 6 ) );
        $block = $this->prepare_elements( array( $block ) )[0];

        // Obtener elementos actuales
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();
        $elements = $vbp_data['elements'] ?? array();

        // Insertar según posición
        switch ( $position ) {
            case 'start':
                array_unshift( $elements, $block );
                break;
            case 'index':
                if ( null !== $insert_at && $insert_at >= 0 ) {
                    array_splice( $elements, $insert_at, 0, array( $block ) );
                } else {
                    $elements[] = $block;
                }
                break;
            case 'end':
            default:
                $elements[] = $block;
                break;
        }

        $vbp_data['elements'] = $elements;
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'  => true,
            'block_id' => $block['id'],
            'page_id'  => $post_id,
            'position' => $position,
            'index'    => array_search( $block, $elements ),
        ), 201 );
    }

    /**
     * Elimina un bloque de una página
     *
     * @param WP_REST_Request $request Request con IDs.
     * @return WP_REST_Response
     */
    public function delete_block( $request ) {
        $post_id = $request->get_param( 'id' );
        $block_index = (int) $request->get_param( 'block_index' );

        $post = get_post( $post_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();
        $elements = $vbp_data['elements'] ?? array();

        if ( ! isset( $elements[ $block_index ] ) ) {
            return new WP_REST_Response( array(
                'error' => 'Bloque no encontrado en índice: ' . $block_index,
            ), 404 );
        }

        $deleted_block = $elements[ $block_index ];
        array_splice( $elements, $block_index, 1 );

        $vbp_data['elements'] = $elements;
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'       => true,
            'deleted_id'    => $deleted_block['id'] ?? null,
            'deleted_type'  => $deleted_block['type'] ?? null,
            'deleted_index' => $block_index,
            'remaining'     => count( $elements ),
        ) );
    }

    /**
     * Mueve un bloque dentro de una página
     *
     * @param WP_REST_Request $request Request con índices.
     * @return WP_REST_Response
     */
    public function move_block( $request ) {
        $post_id = $request->get_param( 'id' );
        $from_index = (int) $request->get_param( 'from_index' );
        $to_index = (int) $request->get_param( 'to_index' );

        $post = get_post( $post_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();
        $elements = $vbp_data['elements'] ?? array();

        if ( ! isset( $elements[ $from_index ] ) ) {
            return new WP_REST_Response( array(
                'error' => 'Bloque origen no encontrado',
            ), 404 );
        }

        // Extraer bloque
        $block = $elements[ $from_index ];
        array_splice( $elements, $from_index, 1 );

        // Ajustar índice destino si es necesario
        if ( $to_index > $from_index ) {
            $to_index--;
        }

        // Insertar en nueva posición
        $to_index = max( 0, min( $to_index, count( $elements ) ) );
        array_splice( $elements, $to_index, 0, array( $block ) );

        $vbp_data['elements'] = $elements;
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'    => true,
            'block_id'   => $block['id'] ?? null,
            'from_index' => $from_index,
            'to_index'   => $to_index,
        ) );
    }

    /**
     * Reordena todos los bloques de una página
     *
     * @param WP_REST_Request $request Request con nuevo orden.
     * @return WP_REST_Response
     */
    public function reorder_page_blocks( $request ) {
        $post_id = $request->get_param( 'id' );
        $new_order = $request->get_param( 'order' );

        $post = get_post( $post_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();
        $elements = $vbp_data['elements'] ?? array();

        // Validar que el orden tenga el mismo número de elementos
        if ( count( $new_order ) !== count( $elements ) ) {
            return new WP_REST_Response( array(
                'error' => 'El orden debe contener el mismo número de elementos',
                'expected' => count( $elements ),
                'received' => count( $new_order ),
            ), 400 );
        }

        // Reordenar elementos según el nuevo orden
        $reordered = array();
        foreach ( $new_order as $index ) {
            if ( isset( $elements[ $index ] ) ) {
                $reordered[] = $elements[ $index ];
            }
        }

        $vbp_data['elements'] = $reordered;
        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        return new WP_REST_Response( array(
            'success'   => true,
            'new_order' => $new_order,
            'total'     => count( $reordered ),
        ) );
    }

    /**
     * Copia bloques entre páginas
     *
     * @param WP_REST_Request $request Request con IDs y opciones.
     * @return WP_REST_Response
     */
    public function copy_blocks_between_pages( $request ) {
        $source_id = $request->get_param( 'source_page_id' );
        $target_id = $request->get_param( 'target_page_id' );
        $block_indices = $request->get_param( 'block_indices' );
        $position = $request->get_param( 'position' ) ?: 'end';
        $insert_at = $request->get_param( 'insert_at' );

        // Validar páginas
        $source_post = get_post( $source_id );
        $target_post = get_post( $target_id );

        if ( ! $this->is_valid_vbp_post( $source_post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página origen no encontrada',
            ), 404 );
        }

        if ( ! $this->is_valid_vbp_post( $target_post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página destino no encontrada',
            ), 404 );
        }

        // Obtener bloques de origen
        $source_vbp = get_post_meta( $source_id, '_flavor_vbp_data', true ) ?: array();
        $source_elements = $source_vbp['elements'] ?? array();

        // Extraer bloques a copiar
        $blocks_to_copy = array();
        foreach ( $block_indices as $index ) {
            if ( isset( $source_elements[ $index ] ) ) {
                $block = $source_elements[ $index ];
                // Regenerar ID para evitar conflictos
                $block['id'] = 'el_' . bin2hex( random_bytes( 6 ) );
                if ( ! empty( $block['children'] ) ) {
                    $block['children'] = $this->regenerate_element_ids( $block['children'] );
                }
                $blocks_to_copy[] = $block;
            }
        }

        if ( empty( $blocks_to_copy ) ) {
            return new WP_REST_Response( array(
                'error' => 'No se encontraron bloques en los índices especificados',
            ), 400 );
        }

        // Obtener elementos de destino
        $target_vbp = get_post_meta( $target_id, '_flavor_vbp_data', true ) ?: array();
        $target_elements = $target_vbp['elements'] ?? array();

        // Insertar según posición
        switch ( $position ) {
            case 'start':
                $target_elements = array_merge( $blocks_to_copy, $target_elements );
                break;
            case 'index':
                if ( null !== $insert_at && $insert_at >= 0 ) {
                    array_splice( $target_elements, $insert_at, 0, $blocks_to_copy );
                } else {
                    $target_elements = array_merge( $target_elements, $blocks_to_copy );
                }
                break;
            case 'end':
            default:
                $target_elements = array_merge( $target_elements, $blocks_to_copy );
                break;
        }

        $target_vbp['elements'] = $target_elements;
        update_post_meta( $target_id, '_flavor_vbp_data', $target_vbp );

        return new WP_REST_Response( array(
            'success'       => true,
            'copied_count'  => count( $blocks_to_copy ),
            'source_page'   => $source_id,
            'target_page'   => $target_id,
            'new_block_ids' => array_column( $blocks_to_copy, 'id' ),
        ) );
    }

    /**
     * Aplana la estructura de bloques a una lista
     *
     * @param array  $elements Elementos a aplanar.
     * @param string $path     Ruta actual.
     * @return array
     */
    private function flatten_blocks( $elements, $path = '' ) {
        $flat = array();

        foreach ( $elements as $index => $element ) {
            $current_path = $path ? $path . '.' . $index : (string) $index;

            $flat[] = array(
                'id'    => $element['id'] ?? '',
                'type'  => $element['type'] ?? '',
                'name'  => $element['name'] ?? '',
                'path'  => $current_path,
                'depth' => substr_count( $current_path, '.' ),
            );

            if ( ! empty( $element['children'] ) ) {
                $flat = array_merge( $flat, $this->flatten_blocks( $element['children'], $current_path ) );
            }
        }

        return $flat;
    }
}
