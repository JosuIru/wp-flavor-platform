<?php
/**
 * Trait para widgets avanzados VBP
 *
 * Este trait contiene métodos avanzados para gestión
 * de widgets globales en VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_AdvancedWidgets
 *
 * Contiene métodos para:
 * - Versionado de widgets
 * - Sincronización de widgets a páginas
 * - Variables de widgets
 */
trait VBP_API_AdvancedWidgets {


    /**
     * Obtiene versiones de un widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_versions( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $versions = get_option( 'flavor_vbp_widget_versions_' . $widget_id, array() );

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'total'     => count( $versions ),
            'versions'  => $versions,
        ), 200 );
    }

    /**
     * Crea nueva versión de widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_widget_version( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $block = $request->get_param( 'block' );
        $changelog = sanitize_text_field( $request->get_param( 'changelog' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        // Guardar versión anterior
        $versions = get_option( 'flavor_vbp_widget_versions_' . $widget_id, array() );
        $version_number = count( $versions ) + 1;

        $versions[] = array(
            'version'   => $version_number,
            'block'     => $widgets[ $widget_id ]['block'],
            'changelog' => $changelog,
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time( 'mysql' ),
        );

        update_option( 'flavor_vbp_widget_versions_' . $widget_id, $versions );

        // Actualizar widget actual
        $widgets[ $widget_id ]['block'] = $block;
        $widgets[ $widget_id ]['updated_at'] = current_time( 'mysql' );
        $widgets[ $widget_id ]['current_version'] = $version_number + 1;

        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Nueva versión creada.',
            'version' => $version_number + 1,
        ), 201 );
    }

    /**
     * Restaura versión de widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function restore_widget_version( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $version = (int) $request->get_param( 'version' );

        $versions = get_option( 'flavor_vbp_widget_versions_' . $widget_id, array() );

        $version_to_restore = null;
        foreach ( $versions as $v ) {
            if ( $v['version'] === $version ) {
                $version_to_restore = $v['block'];
                break;
            }
        }

        if ( ! $version_to_restore ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Versión no encontrada.',
            ), 404 );
        }

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $widgets[ $widget_id ]['block'] = $version_to_restore;
        $widgets[ $widget_id ]['updated_at'] = current_time( 'mysql' );
        $widgets[ $widget_id ]['restored_from'] = $version;

        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Widget restaurado a versión {$version}.",
        ), 200 );
    }

    /**
     * Sincroniza widget a todas las páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function sync_widget_to_pages( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $page_ids = $request->get_param( 'page_ids' );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $widget_block = $widgets[ $widget_id ]['block'];

        // Obtener páginas que usan este widget
        global $wpdb;

        if ( empty( $page_ids ) ) {
            $pages = $wpdb->get_col( $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'flavor_landing'
                 AND pm.meta_key = '_flavor_vbp_elements'
                 AND pm.meta_value LIKE %s",
                '%' . $wpdb->esc_like( $widget_id ) . '%'
            ) );
        } else {
            $pages = array_map( 'intval', $page_ids );
        }

        $updated = 0;
        foreach ( $pages as $page_id ) {
            $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
            $elements = $elements_json ? json_decode( $elements_json, true ) : array();

            $elements = $this->update_widget_in_elements( $elements, $widget_id, $widget_block );

            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
            $updated++;
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'message'       => "Widget sincronizado en {$updated} páginas.",
            'pages_updated' => $updated,
        ), 200 );
    }

    /**
     * Actualiza widget en elementos
     *
     * @param array  $elements     Elementos.
     * @param string $widget_id    ID del widget.
     * @param array  $widget_block Bloque del widget.
     * @return array
     */
    private function update_widget_in_elements( $elements, $widget_id, $widget_block ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['widget_ref'] ?? '' ) === $widget_id ) {
                // Mantener ID y posición, actualizar contenido
                $element_id = $element['id'];
                $element = array_merge( $widget_block, array(
                    'id'         => $element_id,
                    'widget_ref' => $widget_id,
                ) );
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->update_widget_in_elements( $element['children'], $widget_id, $widget_block );
            }
        }
        return $elements;
    }

    /**
     * Obtiene variables de un widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_variables( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $variables = $widgets[ $widget_id ]['variables'] ?? array();

        return new WP_REST_Response( array(
            'success'   => true,
            'widget_id' => $widget_id,
            'variables' => $variables,
        ), 200 );
    }

    /**
     * Configura variables de un widget
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function set_widget_variables( $request ) {
        $widget_id = sanitize_text_field( $request->get_param( 'widget_id' ) );
        $variables = $request->get_param( 'variables' );

        $widgets = get_option( 'flavor_vbp_global_widgets', array() );

        if ( ! isset( $widgets[ $widget_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado.',
            ), 404 );
        }

        $widgets[ $widget_id ]['variables'] = $variables;
        update_option( 'flavor_vbp_global_widgets', $widgets );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Variables configuradas.',
        ), 200 );
    }

    // =============================================
}
