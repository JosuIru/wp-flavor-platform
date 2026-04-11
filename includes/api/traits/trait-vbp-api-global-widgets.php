<?php
/**
 * Trait para widgets globales VBP
 *
 * Este trait contiene métodos para gestión de widgets globales
 * reutilizables en páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_GlobalWidgets
 *
 * Contiene métodos para:
 * - CRUD de widgets globales
 * - Consulta de uso de widgets
 */
trait VBP_API_GlobalWidgets {

    /**
     * Lista widgets globales
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_global_widgets( $request ) {
        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        $list = array();
        foreach ( $widgets as $widget_id => $widget ) {
            $list[] = array(
                'id'          => $widget_id,
                'name'        => $widget['name'],
                'description' => $widget['description'] ?? '',
                'block_type'  => $widget['block']['type'] ?? 'unknown',
                'created_at'  => $widget['created_at'] ?? '',
                'updated_at'  => $widget['updated_at'] ?? '',
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'total'   => count( $list ),
            'widgets' => $list,
        ), 200 );
    }

    /**
     * Crea widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_global_widget( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $block = $request->get_param( 'block' );
        $description = sanitize_text_field( $request->get_param( 'description' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        $widget_id = 'widget_' . sanitize_title( $name ) . '_' . uniqid();
        $widgets[ $widget_id ] = array(
            'name'        => $name,
            'description' => $description,
            'block'       => $block,
            'created_at'  => current_time( 'mysql' ),
            'created_by'  => get_current_user_id(),
        );

        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Widget creado.',
            'widget_id' => $widget_id,
        ), 201 );
    }

    /**
     * Obtiene widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_global_widget( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $widget = $widgets[ $widget_id ];
        $widget['id'] = $widget_id;

        return new WP_REST_Response( array(
            'success' => true,
            'widget'  => $widget,
        ), 200 );
    }

    /**
     * Actualiza widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_global_widget( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $name = $request->get_param( 'name' );
        $block = $request->get_param( 'block' );
        $description = $request->get_param( 'description' );

        if ( $name !== null ) {
            $widgets[ $widget_id ]['name'] = sanitize_text_field( $name );
        }
        if ( $block !== null ) {
            $widgets[ $widget_id ]['block'] = $block;
        }
        if ( $description !== null ) {
            $widgets[ $widget_id ]['description'] = sanitize_text_field( $description );
        }

        $widgets[ $widget_id ]['updated_at'] = current_time( 'mysql' );
        $widgets[ $widget_id ]['updated_by'] = get_current_user_id();

        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Widget actualizado.',
        ), 200 );
    }

    /**
     * Elimina widget global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_global_widget( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        unset( $widgets[ $widget_id ] );
        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Widget eliminado.',
        ), 200 );
    }

    /**
     * Obtiene páginas que usan un widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_usage( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        // Buscar páginas que contienen este widget
        global $wpdb;
        $pages = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_status
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'flavor_landing'
             AND pm.meta_key = '_flavor_vbp_elements'
             AND pm.meta_value LIKE %s",
            '%' . $wpdb->esc_like( $widget_id ) . '%'
        ) );

        $usage = array();
        foreach ( $pages as $page ) {
            $usage[] = array(
                'id'     => $page->ID,
                'title'  => $page->post_title,
                'status' => $page->post_status,
            );
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'total'     => count( $usage ),
            'pages'     => $usage,
        ), 200 );
    }
}
