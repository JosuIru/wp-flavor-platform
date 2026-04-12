<?php
/**
 * Trait para Biblioteca de Widgets VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_WidgetLib {

    /**
     * Obtiene biblioteca de widgets
     */
    public function get_widget_library( $request ) {
        $category_filter = sanitize_text_field( $request->get_param( 'category' ) );
        $search_query = sanitize_text_field( $request->get_param( 'search' ) );

        $widgets = get_option( 'flavor_vbp_widget_library', array() );

        if ( $category_filter ) {
            $widgets = array_filter( $widgets, function( $w ) use ( $category_filter ) {
                return $w['category'] === $category_filter;
            } );
        }

        if ( $search_query ) {
            $widgets = array_filter( $widgets, function( $w ) use ( $search_query ) {
                return stripos( $w['name'], $search_query ) !== false ||
                       ( isset( $w['tags'] ) && array_filter( $w['tags'], function( $tag ) use ( $search_query ) {
                           return stripos( $tag, $search_query ) !== false;
                       } ) );
            } );
        }

        $categories = array_unique( array_column( get_option( 'flavor_vbp_widget_library', array() ), 'category' ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'widgets'    => array_values( $widgets ),
            'categories' => array_values( $categories ),
            'count'      => count( $widgets ),
        ), 200 );
    }

    /**
     * Guarda widget en biblioteca
     */
    public function save_widget_to_library( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $tags = $request->get_param( 'tags' ) ?: array();
        $elements = $request->get_param( 'elements' );

        $widget_id = 'widget_' . sanitize_title( $name ) . '_' . time();

        $widget = array(
            'id'         => $widget_id,
            'name'       => $name,
            'category'   => $category,
            'tags'       => array_map( 'sanitize_text_field', $tags ),
            'elements'   => $elements,
            'created_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
        );

        $library = get_option( 'flavor_vbp_widget_library', array() );
        $library[ $widget_id ] = $widget;
        update_option( 'flavor_vbp_widget_library', $library );

        return new WP_REST_Response( array( 'success' => true, 'widget' => $widget ), 201 );
    }

    /**
     * Sincroniza widget global
     */
    public function sync_global_widget( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $elements = $request->get_param( 'elements' );

        $library = get_option( 'flavor_vbp_widget_library', array() );

        if ( ! isset( $library[ $widget_id ] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Widget no encontrado.' ), 404 );
        }

        $library[ $widget_id ]['elements'] = $elements;
        $library[ $widget_id ]['updated_at'] = current_time( 'mysql' );

        update_option( 'flavor_vbp_widget_library', $library );

        // Buscar y actualizar instancias
        $instances_updated = $this->update_widget_instances( $widget_id, $elements );

        return new WP_REST_Response( array(
            'success'           => true,
            'widget_id'         => $widget_id,
            'instances_updated' => $instances_updated,
        ), 200 );
    }

    private function update_widget_instances( $widget_id, $elements ) {
        global $wpdb;

        $pages = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_flavor_vbp_elements'" );
        $count = 0;

        foreach ( $pages as $page_id ) {
            $page_elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            if ( ! $page_elements_json ) { continue; }

            $page_elements = json_decode( $page_elements_json, true );
            if ( ! is_array( $page_elements ) ) { continue; }

            $updated = $this->replace_widget_instances( $page_elements, $widget_id, $elements );
            if ( $updated ) {
                update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $page_elements ) );
                $count++;
            }
        }

        return $count;
    }

    private function replace_widget_instances( &$elements, $widget_id, $new_elements ) {
        $updated = false;
        foreach ( $elements as &$element ) {
            if ( isset( $element['data']['_widget_ref'] ) && $element['data']['_widget_ref'] === $widget_id ) {
                $element['children'] = $new_elements;
                $updated = true;
            }
            if ( ! empty( $element['children'] ) ) {
                if ( $this->replace_widget_instances( $element['children'], $widget_id, $new_elements ) ) {
                    $updated = true;
                }
            }
        }
        return $updated;
    }

    /**
     * Obtiene instancias de widget
     */
    public function get_widget_instances( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        global $wpdb;
        $instances = array();

        $pages = $wpdb->get_results(
            "SELECT p.ID, p.post_title FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_flavor_vbp_elements'
             AND pm.meta_value LIKE '%\"_widget_ref\":\"{$widget_id}\"%'"
        );

        foreach ( $pages as $page ) {
            $instances[] = array(
                'page_id'    => $page->ID,
                'page_title' => $page->post_title,
                'edit_url'   => get_edit_post_link( $page->ID, 'raw' ),
            );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'instances' => $instances,
            'count'     => count( $instances ),
        ), 200 );
    }

    /**
     * Obtiene presets de widgets
     */
    public function get_widget_presets( $request ) {
        $presets = array(
            'card' => array( 'name' => 'Tarjeta', 'description' => 'Tarjeta con imagen, título y texto' ),
            'cta' => array( 'name' => 'Call to Action', 'description' => 'Sección de llamada a la acción' ),
            'testimonial' => array( 'name' => 'Testimonio', 'description' => 'Tarjeta de testimonio con avatar' ),
            'pricing' => array( 'name' => 'Precio', 'description' => 'Tarjeta de plan de precios' ),
            'feature' => array( 'name' => 'Característica', 'description' => 'Icono con título y descripción' ),
            'team_member' => array( 'name' => 'Miembro equipo', 'description' => 'Tarjeta de miembro del equipo' ),
            'stat' => array( 'name' => 'Estadística', 'description' => 'Número destacado con etiqueta' ),
            'social_proof' => array( 'name' => 'Prueba social', 'description' => 'Logos de clientes o medios' ),
        );

        return new WP_REST_Response( array( 'success' => true, 'presets' => $presets ), 200 );
    }

    /**
     * Crea preset de widget
     */
    public function create_widget_preset( $request ) {
        $preset_name = sanitize_text_field( $request->get_param( 'preset' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $data = $request->get_param( 'data' ) ?: array();

        $preset_elements = $this->generate_preset_elements( $preset_name, $name, $data );

        $widget_id = 'preset_' . $preset_name . '_' . time();
        $widget = array(
            'id'         => $widget_id,
            'name'       => $name ?: ucfirst( $preset_name ),
            'category'   => 'presets',
            'preset'     => $preset_name,
            'elements'   => $preset_elements,
            'created_at' => current_time( 'mysql' ),
        );

        $library = get_option( 'flavor_vbp_widget_library', array() );
        $library[ $widget_id ] = $widget;
        update_option( 'flavor_vbp_widget_library', $library );

        return new WP_REST_Response( array( 'success' => true, 'widget' => $widget ), 201 );
    }

    private function generate_preset_elements( $preset, $name, $data ) {
        switch ( $preset ) {
            case 'card':
                return array(
                    array(
                        'id' => 'card_' . uniqid(),
                        'type' => 'container',
                        'styles' => array( 'padding' => '20px', 'borderRadius' => '8px', 'boxShadow' => '0 2px 8px rgba(0,0,0,0.1)' ),
                        'children' => array(
                            array( 'id' => 'img_' . uniqid(), 'type' => 'image', 'data' => array( 'alt' => $name ) ),
                            array( 'id' => 'h_' . uniqid(), 'type' => 'heading', 'data' => array( 'text' => $data['title'] ?? 'Título', 'level' => 3 ) ),
                            array( 'id' => 't_' . uniqid(), 'type' => 'text', 'data' => array( 'content' => $data['text'] ?? 'Descripción' ) ),
                        ),
                    ),
                );
            case 'cta':
                return array(
                    array(
                        'id' => 'cta_' . uniqid(),
                        'type' => 'section',
                        'styles' => array( 'padding' => '60px 20px', 'textAlign' => 'center', 'background' => '#f8f9fa' ),
                        'children' => array(
                            array( 'id' => 'h_' . uniqid(), 'type' => 'heading', 'data' => array( 'text' => $data['title'] ?? 'Título CTA', 'level' => 2 ) ),
                            array( 'id' => 't_' . uniqid(), 'type' => 'text', 'data' => array( 'content' => $data['subtitle'] ?? 'Subtítulo persuasivo' ) ),
                            array( 'id' => 'b_' . uniqid(), 'type' => 'button', 'data' => array( 'text' => $data['button'] ?? 'Acción', 'style' => 'primary' ) ),
                        ),
                    ),
                );
            default:
                return array();
        }
    }

    // =============================================
}
