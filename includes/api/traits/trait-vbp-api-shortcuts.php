<?php
/**
 * Trait para Shortcuts y Productividad VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_Shortcuts {


    /**
     * Obtiene atajos de teclado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_keyboard_shortcuts( $request ) {
        $shortcuts = get_option( 'flavor_vbp_shortcuts', array(
            'general' => array(
                array( 'keys' => 'Ctrl+S', 'action' => 'save', 'description' => 'Guardar página' ),
                array( 'keys' => 'Ctrl+Z', 'action' => 'undo', 'description' => 'Deshacer' ),
                array( 'keys' => 'Ctrl+Shift+Z', 'action' => 'redo', 'description' => 'Rehacer' ),
                array( 'keys' => 'Ctrl+C', 'action' => 'copy', 'description' => 'Copiar elemento' ),
                array( 'keys' => 'Ctrl+V', 'action' => 'paste', 'description' => 'Pegar elemento' ),
                array( 'keys' => 'Ctrl+X', 'action' => 'cut', 'description' => 'Cortar elemento' ),
                array( 'keys' => 'Delete', 'action' => 'delete', 'description' => 'Eliminar elemento' ),
                array( 'keys' => 'Ctrl+D', 'action' => 'duplicate', 'description' => 'Duplicar elemento' ),
            ),
            'navigation' => array(
                array( 'keys' => 'Ctrl+P', 'action' => 'preview', 'description' => 'Vista previa' ),
                array( 'keys' => 'Escape', 'action' => 'deselect', 'description' => 'Deseleccionar' ),
                array( 'keys' => 'F11', 'action' => 'fullscreen', 'description' => 'Pantalla completa' ),
            ),
            'blocks' => array(
                array( 'keys' => 'Ctrl+Shift+T', 'action' => 'add_text', 'description' => 'Añadir texto' ),
                array( 'keys' => 'Ctrl+Shift+I', 'action' => 'add_image', 'description' => 'Añadir imagen' ),
                array( 'keys' => 'Ctrl+Shift+B', 'action' => 'add_button', 'description' => 'Añadir botón' ),
            ),
        ) );

        $user_shortcuts = get_user_meta( get_current_user_id(), 'vbp_custom_shortcuts', true ) ?: array();

        return new WP_REST_Response( array(
            'success'   => true,
            'shortcuts' => array_merge_recursive( $shortcuts, $user_shortcuts ),
        ), 200 );
    }

    /**
     * Guarda atajos personalizados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function save_custom_shortcuts( $request ) {
        $shortcuts = $request->get_param( 'shortcuts' );

        if ( ! is_array( $shortcuts ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Atajos inválidos.' ), 400 );
        }

        update_user_meta( get_current_user_id(), 'vbp_custom_shortcuts', $shortcuts );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Atajos guardados.',
        ), 200 );
    }

    /**
     * Obtiene acciones rápidas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_quick_actions( $request ) {
        $context = $request->get_param( 'context' ) ?: 'editor';

        $quick_actions = array(
            'editor' => array(
                array( 'id' => 'add_section', 'label' => 'Añadir sección', 'icon' => 'plus' ),
                array( 'id' => 'add_columns', 'label' => 'Añadir columnas', 'icon' => 'columns' ),
                array( 'id' => 'add_text', 'label' => 'Añadir texto', 'icon' => 'text' ),
                array( 'id' => 'add_image', 'label' => 'Añadir imagen', 'icon' => 'image' ),
                array( 'id' => 'add_button', 'label' => 'Añadir botón', 'icon' => 'button' ),
                array( 'id' => 'add_form', 'label' => 'Añadir formulario', 'icon' => 'form' ),
            ),
            'element' => array(
                array( 'id' => 'edit', 'label' => 'Editar', 'icon' => 'edit' ),
                array( 'id' => 'duplicate', 'label' => 'Duplicar', 'icon' => 'copy' ),
                array( 'id' => 'move_up', 'label' => 'Mover arriba', 'icon' => 'arrow-up' ),
                array( 'id' => 'move_down', 'label' => 'Mover abajo', 'icon' => 'arrow-down' ),
                array( 'id' => 'delete', 'label' => 'Eliminar', 'icon' => 'trash' ),
            ),
        );

        return new WP_REST_Response( array(
            'success' => true,
            'actions' => $quick_actions[ $context ] ?? array(),
        ), 200 );
    }

    /**
     * Ejecuta acción rápida
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function execute_quick_action( $request ) {
        $action_id = sanitize_text_field( $request->get_param( 'action_id' ) );
        $page_id = (int) $request->get_param( 'page_id' );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $params = $request->get_param( 'params' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $result = array( 'action' => $action_id, 'executed' => true );

        switch ( $action_id ) {
            case 'duplicate':
                // Duplicar elemento
                $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
                $element = $this->find_element_by_id( $elements, $element_id );
                if ( $element ) {
                    $duplicate = $element;
                    $duplicate['id'] = 'el_' . uniqid();
                    $elements[] = $duplicate;
                    update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                    $result['new_element_id'] = $duplicate['id'];
                }
                break;

            case 'delete':
                // Eliminar elemento
                $elements = json_decode( get_post_meta( $page_id, '_flavor_vbp_elements', true ), true ) ?: array();
                $elements = $this->remove_element_by_id( $elements, $element_id );
                update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );
                break;

            default:
                $result['executed'] = false;
                $result['message'] = 'Acción no implementada.';
        }

        return new WP_REST_Response( array(
            'success' => true,
            'result'  => $result,
        ), 200 );
    }

    /**
     * Elimina elemento por ID
     *
     * @param array  $elements Elementos.
     * @param string $element_id ID del elemento.
     * @return array
     */
    private function remove_element_by_id( $elements, $element_id ) {
        $filtered = array();
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $element_id ) {
                continue;
            }
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->remove_element_by_id( $element['children'], $element_id );
            }
            $filtered[] = $element;
        }
        return $filtered;
    }

    /**
     * Obtiene historial de acciones
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_action_history( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;

        $history = get_post_meta( $page_id, '_flavor_vbp_action_history', true ) ?: array();

        return new WP_REST_Response( array(
            'success' => true,
            'history' => array_slice( $history, -$limit ),
            'total'   => count( $history ),
        ), 200 );
    }

    /**
     * Deshace última acción
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function undo_last_action( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );

        $undo_stack = get_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id() ) ?: array();

        if ( empty( $undo_stack ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay acciones para deshacer.' ), 400 );
        }

        $last_state = array_pop( $undo_stack );

        // Guardar estado actual en redo
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $redo_stack = get_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id() ) ?: array();
        $redo_stack[] = $current_elements;
        set_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id(), $redo_stack, HOUR_IN_SECONDS );

        // Restaurar estado anterior
        update_post_meta( $page_id, '_flavor_vbp_elements', $last_state );

        set_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id(), $undo_stack, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Acción deshecha.',
            'undo_count' => count( $undo_stack ),
        ), 200 );
    }

    /**
     * Rehace última acción
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function redo_action( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );

        $redo_stack = get_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id() ) ?: array();

        if ( empty( $redo_stack ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'No hay acciones para rehacer.' ), 400 );
        }

        $next_state = array_pop( $redo_stack );

        // Guardar estado actual en undo
        $current_elements = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $undo_stack = get_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id() ) ?: array();
        $undo_stack[] = $current_elements;
        set_transient( 'vbp_undo_' . $page_id . '_' . get_current_user_id(), $undo_stack, HOUR_IN_SECONDS );

        // Restaurar siguiente estado
        update_post_meta( $page_id, '_flavor_vbp_elements', $next_state );

        set_transient( 'vbp_redo_' . $page_id . '_' . get_current_user_id(), $redo_stack, HOUR_IN_SECONDS );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Acción rehecha.',
            'redo_count' => count( $redo_stack ),
        ), 200 );
    }

    // =============================================
}
