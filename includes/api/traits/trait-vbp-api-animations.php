<?php
/**
 * Trait para Animaciones VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Animations {


    /**
     * Obtiene animaciones de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_animations( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $animations = array();
        $this->collect_animations( $elements, $animations );

        return new WP_REST_Response( array(
            'success'    => true,
            'animations' => $animations,
            'total'      => count( $animations ),
        ), 200 );
    }

    /**
     * Recopila animaciones de elementos
     *
     * @param array $elements   Elementos.
     * @param array $animations Array de animaciones.
     */
    private function collect_animations( $elements, &$animations ) {
        foreach ( $elements as $element ) {
            if ( ! empty( $element['animation'] ) ) {
                $animations[] = array(
                    'block_id'  => $element['id'] ?? '',
                    'animation' => $element['animation'],
                );
            }
            if ( ! empty( $element['children'] ) ) {
                $this->collect_animations( $element['children'], $animations );
            }
        }
    }

    /**
     * Configura animación de bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_block_animation( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $type = $request->get_param( 'type' );
        $duration = (int) $request->get_param( 'duration' );
        $delay = (int) $request->get_param( 'delay' );
        $trigger = $request->get_param( 'trigger' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $animation_config = $type === 'none' ? null : array(
            'type'     => $type,
            'duration' => $duration,
            'delay'    => $delay,
            'trigger'  => $trigger,
        );

        $elements = $this->set_element_animation( $elements, $block_id, $animation_config );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => $type === 'none' ? 'Animación eliminada.' : 'Animación configurada.',
            'animation' => $animation_config,
        ), 200 );
    }

    /**
     * Establece animación en elemento
     *
     * @param array  $elements  Elementos.
     * @param string $block_id  ID del bloque.
     * @param array  $animation Configuración de animación.
     * @return array
     */
    private function set_element_animation( $elements, $block_id, $animation ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                if ( $animation ) {
                    $element['animation'] = $animation;
                } else {
                    unset( $element['animation'] );
                }
                return $elements;
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->set_element_animation( $element['children'], $block_id, $animation );
            }
        }
        return $elements;
    }

    /**
     * Aplica animaciones en lote
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function batch_set_animations( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $preset = $request->get_param( 'preset' );
        $block_ids = $request->get_param( 'block_ids' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $presets = $this->get_animation_preset_configs();
        if ( ! isset( $presets[ $preset ] ) && $preset !== 'none' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Preset no válido.',
            ), 400 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $applied = 0;
        $elements = $this->apply_animation_preset( $elements, $preset, $presets, $block_ids, $applied );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Preset '{$preset}' aplicado a {$applied} bloques.",
            'applied' => $applied,
        ), 200 );
    }

    /**
     * Obtiene configuraciones de presets de animación
     *
     * @return array
     */
    private function get_animation_preset_configs() {
        return array(
            'subtle' => array(
                'type'     => 'fadeIn',
                'duration' => 400,
                'delay'    => 0,
                'trigger'  => 'scroll',
            ),
            'dynamic' => array(
                'type'     => 'fadeInUp',
                'duration' => 600,
                'delay'    => 100,
                'trigger'  => 'scroll',
            ),
            'elegant' => array(
                'type'     => 'fadeInDown',
                'duration' => 800,
                'delay'    => 50,
                'trigger'  => 'scroll',
            ),
            'playful' => array(
                'type'     => 'bounce',
                'duration' => 500,
                'delay'    => 0,
                'trigger'  => 'scroll',
            ),
        );
    }

    /**
     * Aplica preset de animación
     *
     * @param array  $elements  Elementos.
     * @param string $preset    Nombre del preset.
     * @param array  $presets   Configuraciones.
     * @param array  $block_ids IDs específicos.
     * @param int    $applied   Contador.
     * @return array
     */
    private function apply_animation_preset( $elements, $preset, $presets, $block_ids, &$applied ) {
        $index = 0;
        foreach ( $elements as &$element ) {
            $id = $element['id'] ?? '';
            $should_apply = empty( $block_ids ) || in_array( $id, $block_ids );

            if ( $should_apply ) {
                if ( $preset === 'none' ) {
                    unset( $element['animation'] );
                } else {
                    $config = $presets[ $preset ];
                    $config['delay'] = $config['delay'] * $index;
                    $element['animation'] = $config;
                }
                $applied++;
                $index++;
            }

            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->apply_animation_preset( $element['children'], $preset, $presets, $block_ids, $applied );
            }
        }
        return $elements;
    }

    /**
     * Obtiene presets de animación disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_animation_presets( $request ) {
        $presets = array(
            array(
                'id'          => 'subtle',
                'name'        => 'Sutil',
                'description' => 'Animaciones suaves de aparición.',
                'preview'     => 'fadeIn',
            ),
            array(
                'id'          => 'dynamic',
                'name'        => 'Dinámico',
                'description' => 'Animaciones con movimiento.',
                'preview'     => 'fadeInUp',
            ),
            array(
                'id'          => 'elegant',
                'name'        => 'Elegante',
                'description' => 'Transiciones fluidas.',
                'preview'     => 'fadeInDown',
            ),
            array(
                'id'          => 'playful',
                'name'        => 'Divertido',
                'description' => 'Animaciones con rebote.',
                'preview'     => 'bounce',
            ),
            array(
                'id'          => 'none',
                'name'        => 'Ninguna',
                'description' => 'Eliminar animaciones.',
                'preview'     => null,
            ),
        );

        return new WP_REST_Response( array(
            'success' => true,
            'presets' => $presets,
        ), 200 );
    }

    // =============================================
}
