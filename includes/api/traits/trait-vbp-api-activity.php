<?php
/**
 * Trait para historial de actividad VBP
 *
 * Este trait contiene métodos para registrar y consultar
 * actividad de páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Activity
 *
 * Contiene métodos para:
 * - Registro de actividad (log_page_activity, log_global_activity)
 * - Consulta de actividad (get_page_activity, get_global_activity)
 */
trait VBP_API_Activity {

    /**
     * Registra actividad de una página
     *
     * @param int    $page_id ID de la página.
     * @param string $action  Acción realizada.
     * @param array  $data    Datos adicionales.
     */
    private function log_page_activity( $page_id, $action, $data = array() ) {
        $activity_json = get_post_meta( $page_id, '_flavor_vbp_activity', true );
        $activity = $activity_json ? json_decode( $activity_json, true ) : array();

        $entry = array(
            'id'        => 'act_' . uniqid(),
            'action'    => $action,
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time( 'mysql' ),
            'data'      => $data,
        );

        array_unshift( $activity, $entry );

        // Mantener solo las últimas 500 entradas
        $activity = array_slice( $activity, 0, 500 );

        update_post_meta( $page_id, '_flavor_vbp_activity', wp_json_encode( $activity ) );

        // También guardar en actividad global
        $this->log_global_activity( $page_id, $action, $data );

        // Disparar webhooks si aplica
        $this->trigger_webhooks( $action, $page_id, $data );
    }

    /**
     * Registra actividad global
     *
     * @param int    $page_id ID de la página.
     * @param string $action  Acción realizada.
     * @param array  $data    Datos adicionales.
     */
    private function log_global_activity( $page_id, $action, $data = array() ) {
        $global_activity = get_option( 'flavor_vbp_global_activity', array() );

        $post = get_post( $page_id );
        $entry = array(
            'id'         => 'gact_' . uniqid(),
            'page_id'    => $page_id,
            'page_title' => $post ? $post->post_title : 'Página eliminada',
            'action'     => $action,
            'user_id'    => get_current_user_id(),
            'timestamp'  => current_time( 'mysql' ),
            'data'       => $data,
        );

        array_unshift( $global_activity, $entry );

        // Mantener solo las últimas 1000 entradas
        $global_activity = array_slice( $global_activity, 0, 1000 );

        update_option( 'flavor_vbp_global_activity', $global_activity );
    }

    /**
     * Obtiene actividad de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_activity( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $limit = (int) $request->get_param( 'limit' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $activity_json = get_post_meta( $page_id, '_flavor_vbp_activity', true );
        $activity = $activity_json ? json_decode( $activity_json, true ) : array();

        // Enriquecer con datos de usuario
        foreach ( $activity as &$entry ) {
            if ( ! empty( $entry['user_id'] ) ) {
                $user = get_userdata( $entry['user_id'] );
                if ( $user ) {
                    $entry['user_name'] = $user->display_name;
                }
            }
            $entry['action_label'] = $this->get_action_label( $entry['action'] );
        }

        $activity = array_slice( $activity, 0, $limit );

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'total'    => count( $activity ),
            'activity' => $activity,
        ), 200 );
    }

    /**
     * Obtiene etiqueta legible para una acción
     *
     * @param string $action Acción.
     * @return string
     */
    private function get_action_label( $action ) {
        $labels = array(
            'created'         => 'Página creada',
            'updated'         => 'Página actualizada',
            'published'       => 'Página publicada',
            'unpublished'     => 'Página despublicada',
            'deleted'         => 'Página eliminada',
            'duplicated'      => 'Página duplicada',
            'scheduled'       => 'Publicación programada',
            'unscheduled'     => 'Programación cancelada',
            'locked'          => 'Página bloqueada',
            'unlocked'        => 'Página desbloqueada',
            'comment_added'   => 'Comentario añadido',
            'comment_resolved' => 'Comentario resuelto',
            'blocks_added'    => 'Bloques añadidos',
            'blocks_removed'  => 'Bloques eliminados',
            'blocks_reordered' => 'Bloques reordenados',
            'styles_updated'  => 'Estilos actualizados',
            'translated'      => 'Página traducida',
            'snapshot_created' => 'Snapshot creado',
            'snapshot_restored' => 'Snapshot restaurado',
        );

        return $labels[ $action ] ?? ucfirst( str_replace( '_', ' ', $action ) );
    }

    /**
     * Obtiene actividad global
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_global_activity( $request ) {
        $limit = (int) $request->get_param( 'limit' );
        $action_filter = $request->get_param( 'action' );

        $activity = get_option( 'flavor_vbp_global_activity', array() );

        // Filtrar por acción si se especifica
        if ( $action_filter ) {
            $activity = array_filter( $activity, function( $entry ) use ( $action_filter ) {
                return $entry['action'] === $action_filter;
            } );
        }

        // Enriquecer con datos de usuario
        foreach ( $activity as &$entry ) {
            if ( ! empty( $entry['user_id'] ) ) {
                $user = get_userdata( $entry['user_id'] );
                if ( $user ) {
                    $entry['user_name'] = $user->display_name;
                }
            }
            $entry['action_label'] = $this->get_action_label( $entry['action'] );
        }

        $activity = array_slice( array_values( $activity ), 0, $limit );

        return new WP_REST_Response( array(
            'success'  => true,
            'total'    => count( $activity ),
            'activity' => $activity,
        ), 200 );
    }
}
