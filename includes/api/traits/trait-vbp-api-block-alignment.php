<?php
/**
 * Trait para Alineación y Distribución de Bloques VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_BlockAlignment {

    /**
     * Bloquea/desbloquea elemento
     */
    public function toggle_block_lock( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $locked = (bool) $request->get_param( 'locked' );
        $lock_type = sanitize_text_field( $request->get_param( 'lock_type' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $locked, $lock_type ) {
            $element['locked'] = $locked;
            $element['lock_type'] = $lock_type;
            return $element;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array(
            'success'   => true,
            'locked'    => $locked,
            'lock_type' => $lock_type,
            'message'   => $locked ? 'Elemento bloqueado.' : 'Elemento desbloqueado.',
        ), 200 );
    }

    /**
     * Alinea múltiples bloques
     */
    public function align_multiple_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $alignment = sanitize_text_field( $request->get_param( 'alignment' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $blocks_data = array();
        foreach ( $block_ids as $block_id ) {
            $block = $this->find_element_by_id( $elements, $block_id );
            if ( $block ) {
                $blocks_data[] = array(
                    'id'     => $block_id,
                    'left'   => $block['styles']['left'] ?? 0,
                    'top'    => $block['styles']['top'] ?? 0,
                    'width'  => $block['styles']['width'] ?? 100,
                    'height' => $block['styles']['height'] ?? 100,
                );
            }
        }

        if ( empty( $blocks_data ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No se encontraron bloques.' ), 404 );
        }

        $alignment_value = $this->calculate_alignment_value( $blocks_data, $alignment );

        foreach ( $block_ids as $block_id ) {
            $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $alignment, $alignment_value ) {
                if ( ! isset( $element['styles'] ) ) {
                    $element['styles'] = array();
                }
                $width = $element['styles']['width'] ?? 100;
                $height = $element['styles']['height'] ?? 100;
                $this->apply_alignment_to_element( $element, $alignment, $alignment_value, $width, $height );
                return $element;
            } );
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array(
            'success'   => true,
            'alignment' => $alignment,
            'blocks'    => count( $block_ids ),
        ), 200 );
    }

    private function calculate_alignment_value( $blocks_data, $alignment ) {
        switch ( $alignment ) {
            case 'left':
                return min( array_column( $blocks_data, 'left' ) );
            case 'right':
                return max( array_map( function( $b ) { return $b['left'] + $b['width']; }, $blocks_data ) );
            case 'center':
                $min_left = min( array_column( $blocks_data, 'left' ) );
                $max_right = max( array_map( function( $b ) { return $b['left'] + $b['width']; }, $blocks_data ) );
                return ( $min_left + $max_right ) / 2;
            case 'top':
                return min( array_column( $blocks_data, 'top' ) );
            case 'bottom':
                return max( array_map( function( $b ) { return $b['top'] + $b['height']; }, $blocks_data ) );
            case 'middle':
                $min_top = min( array_column( $blocks_data, 'top' ) );
                $max_bottom = max( array_map( function( $b ) { return $b['top'] + $b['height']; }, $blocks_data ) );
                return ( $min_top + $max_bottom ) / 2;
            default:
                return 0;
        }
    }

    private function apply_alignment_to_element( &$element, $alignment, $value, $width, $height ) {
        switch ( $alignment ) {
            case 'left': $element['styles']['left'] = $value; break;
            case 'right': $element['styles']['left'] = $value - $width; break;
            case 'center': $element['styles']['left'] = $value - ( $width / 2 ); break;
            case 'top': $element['styles']['top'] = $value; break;
            case 'bottom': $element['styles']['top'] = $value - $height; break;
            case 'middle': $element['styles']['top'] = $value - ( $height / 2 ); break;
        }
    }

    /**
     * Distribuye bloques uniformemente
     */
    public function distribute_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $direction = sanitize_text_field( $request->get_param( 'direction' ) );
        $fixed_spacing = $request->get_param( 'spacing' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        if ( count( $block_ids ) < 3 ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Se necesitan al menos 3 bloques.' ), 400 );
        }

        $blocks_data = array();
        foreach ( $block_ids as $block_id ) {
            $block = $this->find_element_by_id( $elements, $block_id );
            if ( $block ) {
                $position_key = $direction === 'horizontal' ? 'left' : 'top';
                $size_key = $direction === 'horizontal' ? 'width' : 'height';
                $blocks_data[] = array(
                    'id'       => $block_id,
                    'position' => $block['styles'][ $position_key ] ?? 0,
                    'size'     => $block['styles'][ $size_key ] ?? 100,
                );
            }
        }

        usort( $blocks_data, function( $a, $b ) { return $a['position'] - $b['position']; } );

        $first_pos = $blocks_data[0]['position'];
        $last_block = end( $blocks_data );
        $last_pos = $last_block['position'] + $last_block['size'];
        $total_size = array_sum( array_column( $blocks_data, 'size' ) );
        $spacing = $fixed_spacing ?? ( ( $last_pos - $first_pos - $total_size ) / ( count( $blocks_data ) - 1 ) );

        $current_pos = $first_pos;
        foreach ( $blocks_data as $bd ) {
            $position_key = $direction === 'horizontal' ? 'left' : 'top';
            $elements = $this->update_element_by_id( $elements, $bd['id'], function( $element ) use ( $position_key, $current_pos ) {
                if ( ! isset( $element['styles'] ) ) { $element['styles'] = array(); }
                $element['styles'][ $position_key ] = $current_pos;
                return $element;
            } );
            $current_pos += $bd['size'] + $spacing;
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'direction' => $direction, 'spacing' => $spacing ), 200 );
    }

    /**
     * Snap bloque a grid
     */
    public function snap_block_to_grid( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $grid_size = (int) $request->get_param( 'grid_size' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $snapped_values = array();
        $elements = $this->update_element_by_id( $elements, $block_id, function( $element ) use ( $grid_size, &$snapped_values ) {
            if ( ! isset( $element['styles'] ) ) { $element['styles'] = array(); }

            $props = array( 'left', 'top', 'width', 'height', 'marginTop', 'marginBottom', 'marginLeft', 'marginRight' );
            foreach ( $props as $prop ) {
                if ( isset( $element['styles'][ $prop ] ) && is_numeric( $element['styles'][ $prop ] ) ) {
                    $original = $element['styles'][ $prop ];
                    $snapped = round( $original / $grid_size ) * $grid_size;
                    $element['styles'][ $prop ] = $snapped;
                    if ( $original !== $snapped ) {
                        $snapped_values[ $prop ] = array( 'from' => $original, 'to' => $snapped );
                    }
                }
            }
            return $element;
        } );

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'grid_size' => $grid_size, 'snapped' => $snapped_values ), 200 );
    }

    /**
     * Obtiene guías inteligentes
     */
    public function get_smart_guides( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $active_block_id = sanitize_text_field( $request->get_param( 'active_block_id' ) );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $guides = array( 'horizontal' => array(), 'vertical' => array() );
        $page_width = 1200;
        $guides['vertical'][] = array( 'position' => 0, 'type' => 'page', 'label' => 'Borde izquierdo' );
        $guides['vertical'][] = array( 'position' => $page_width / 2, 'type' => 'page', 'label' => 'Centro' );
        $guides['vertical'][] = array( 'position' => $page_width, 'type' => 'page', 'label' => 'Borde derecho' );

        foreach ( $elements as $element ) {
            if ( $element['id'] === $active_block_id ) { continue; }
            $left = $element['styles']['left'] ?? 0;
            $top = $element['styles']['top'] ?? 0;
            $width = $element['styles']['width'] ?? 0;
            $height = $element['styles']['height'] ?? 0;

            $guides['vertical'][] = array( 'position' => $left, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['vertical'][] = array( 'position' => $left + $width / 2, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['vertical'][] = array( 'position' => $left + $width, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['horizontal'][] = array( 'position' => $top, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['horizontal'][] = array( 'position' => $top + $height / 2, 'type' => 'element', 'element_id' => $element['id'] );
            $guides['horizontal'][] = array( 'position' => $top + $height, 'type' => 'element', 'element_id' => $element['id'] );
        }

        $custom_guides = get_post_meta( $page_id, '_flavor_vbp_custom_guides', true ) ?: array();
        foreach ( $custom_guides as $guide ) {
            $guides[ $guide['type'] ][] = array( 'position' => $guide['position'], 'type' => 'custom', 'color' => $guide['color'] ?? '#00ff00' );
        }

        return new WP_REST_Response( array( 'success' => true, 'guides' => $guides ), 200 );
    }

    /**
     * Crea guía personalizada
     */
    public function create_custom_guide( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $guide_type = sanitize_text_field( $request->get_param( 'type' ) );
        $position = (int) $request->get_param( 'position' );
        $color = sanitize_hex_color( $request->get_param( 'color' ) );

        $post = get_post( $page_id );
        if ( ! $post ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $guides = get_post_meta( $page_id, '_flavor_vbp_custom_guides', true ) ?: array();
        $guide_id = 'guide_' . uniqid();
        $guides[] = array( 'id' => $guide_id, 'type' => $guide_type, 'position' => $position, 'color' => $color );
        update_post_meta( $page_id, '_flavor_vbp_custom_guides', $guides );

        return new WP_REST_Response( array( 'success' => true, 'guide_id' => $guide_id ), 200 );
    }

    /**
     * Copia estilos entre bloques
     */
    public function copy_block_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $source_block_id = sanitize_text_field( $request->get_param( 'source_block_id' ) );
        $target_block_ids = $request->get_param( 'target_block_ids' );
        $style_properties = $request->get_param( 'style_properties' ) ?: array();

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $source_block = $this->find_element_by_id( $elements, $source_block_id );
        if ( ! $source_block ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Bloque fuente no encontrado.' ), 404 );
        }

        $source_styles = $source_block['styles'] ?? array();
        if ( ! empty( $style_properties ) ) {
            $source_styles = array_intersect_key( $source_styles, array_flip( $style_properties ) );
        }
        if ( empty( $style_properties ) ) {
            unset( $source_styles['left'], $source_styles['top'] );
        }

        $updated_count = 0;
        foreach ( $target_block_ids as $target_id ) {
            $elements = $this->update_element_by_id( $elements, $target_id, function( $element ) use ( $source_styles, &$updated_count ) {
                if ( ! isset( $element['styles'] ) ) { $element['styles'] = array(); }
                $element['styles'] = array_merge( $element['styles'], $source_styles );
                $updated_count++;
                return $element;
            } );
        }

        $this->save_page_elements( $page_id, $elements );

        return new WP_REST_Response( array( 'success' => true, 'styles_copied' => array_keys( $source_styles ), 'targets' => $updated_count ), 200 );
    }

    // =============================================
}
