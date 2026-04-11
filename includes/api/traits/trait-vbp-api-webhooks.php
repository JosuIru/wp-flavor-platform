<?php
/**
 * Trait para webhooks VBP
 *
 * Este trait contiene métodos para gestión de webhooks
 * y notificaciones externas.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Webhooks
 *
 * Contiene métodos para:
 * - CRUD de webhooks (list_webhooks, create_webhook, delete_webhook)
 * - Testing de webhooks (test_webhook)
 * - Disparo de webhooks (trigger_webhooks, send_webhook)
 */
trait VBP_API_Webhooks {

    /**
     * Lista webhooks configurados
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_webhooks( $request ) {
        $webhooks = get_option( 'flavor_vbp_webhooks', array() );

        $list = array();
        foreach ( $webhooks as $webhook_id => $webhook ) {
            $list[] = array(
                'id'         => $webhook_id,
                'name'       => $webhook['name'],
                'url'        => $webhook['url'],
                'events'     => $webhook['events'],
                'active'     => $webhook['active'] ?? true,
                'created_at' => $webhook['created_at'] ?? '',
                'last_triggered' => $webhook['last_triggered'] ?? null,
                'last_status'    => $webhook['last_status'] ?? null,
            );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'total'    => count( $list ),
            'webhooks' => $list,
        ), 200 );
    }

    /**
     * Crea webhook
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function create_webhook( $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        $url = esc_url_raw( $request->get_param( 'url' ) );
        $events = $request->get_param( 'events' );
        $secret = $request->get_param( 'secret' );

        // Validar URL
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'URL inválida.',
            ), 400 );
        }

        // Validar eventos
        $valid_events = array( 'page_created', 'page_updated', 'page_published', 'page_deleted' );
        $events = array_intersect( $events, $valid_events );

        if ( empty( $events ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Debe especificar al menos un evento válido.',
            ), 400 );
        }

        $webhooks = get_option( 'flavor_vbp_webhooks', array() );

        $webhook_id = 'webhook_' . uniqid();
        $webhooks[ $webhook_id ] = array(
            'name'       => $name,
            'url'        => $url,
            'events'     => $events,
            'secret'     => $secret ? wp_hash( $secret ) : '',
            'raw_secret' => $secret ?? '',
            'active'     => true,
            'created_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
        );

        update_option( 'flavor_vbp_webhooks', $webhooks );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Webhook creado.',
            'webhook_id' => $webhook_id,
        ), 201 );
    }

    /**
     * Elimina webhook
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_webhook( $request ) {
        $webhook_id = sanitize_text_field( $request->get_param( 'webhook_id' ) );

        $webhooks = get_option( 'flavor_vbp_webhooks', array() );

        if ( ! isset( $webhooks[ $webhook_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Webhook no encontrado.',
            ), 404 );
        }

        unset( $webhooks[ $webhook_id ] );
        update_option( 'flavor_vbp_webhooks', $webhooks );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Webhook eliminado.',
        ), 200 );
    }

    /**
     * Testea webhook
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function test_webhook( $request ) {
        $webhook_id = sanitize_text_field( $request->get_param( 'webhook_id' ) );

        $webhooks = get_option( 'flavor_vbp_webhooks', array() );

        if ( ! isset( $webhooks[ $webhook_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Webhook no encontrado.',
            ), 404 );
        }

        $webhook = $webhooks[ $webhook_id ];

        $payload = array(
            'event'     => 'test',
            'timestamp' => current_time( 'mysql' ),
            'site_url'  => home_url(),
            'message'   => 'Test webhook from VBP',
        );

        $response = $this->send_webhook( $webhook, $payload );

        // Actualizar último trigger
        $webhooks[ $webhook_id ]['last_triggered'] = current_time( 'mysql' );
        $webhooks[ $webhook_id ]['last_status'] = $response['status'];
        update_option( 'flavor_vbp_webhooks', $webhooks );

        return new WP_REST_Response( array(
            'success'       => $response['success'],
            'status_code'   => $response['status'],
            'response_body' => $response['body'],
            'message'       => $response['success'] ? 'Webhook enviado correctamente.' : 'Error al enviar webhook.',
        ), 200 );
    }

    /**
     * Dispara webhooks para un evento
     *
     * @param string $action  Acción/evento.
     * @param int    $page_id ID de la página.
     * @param array  $data    Datos adicionales.
     */
    private function trigger_webhooks( $action, $page_id, $data = array() ) {
        $webhooks = get_option( 'flavor_vbp_webhooks', array() );

        // Mapear acciones internas a eventos de webhook
        $event_map = array(
            'created'   => 'page_created',
            'updated'   => 'page_updated',
            'published' => 'page_published',
            'deleted'   => 'page_deleted',
        );

        $event = $event_map[ $action ] ?? null;
        if ( ! $event ) {
            return;
        }

        $post = get_post( $page_id );
        $payload = array(
            'event'     => $event,
            'timestamp' => current_time( 'mysql' ),
            'site_url'  => home_url(),
            'page'      => array(
                'id'     => $page_id,
                'title'  => $post ? $post->post_title : '',
                'slug'   => $post ? $post->post_name : '',
                'status' => $post ? $post->post_status : '',
                'url'    => $post ? get_permalink( $page_id ) : '',
            ),
            'data'      => $data,
        );

        foreach ( $webhooks as $webhook_id => $webhook ) {
            if ( empty( $webhook['active'] ) ) {
                continue;
            }

            if ( ! in_array( $event, $webhook['events'], true ) ) {
                continue;
            }

            // Enviar en segundo plano
            wp_schedule_single_event( time(), 'flavor_vbp_send_webhook', array( $webhook_id, $payload ) );
        }
    }

    /**
     * Envía un webhook
     *
     * @param array $webhook Configuración del webhook.
     * @param array $payload Datos a enviar.
     * @return array
     */
    private function send_webhook( $webhook, $payload ) {
        $body = wp_json_encode( $payload );

        $headers = array(
            'Content-Type' => 'application/json',
        );

        // Añadir firma si hay secret
        if ( ! empty( $webhook['raw_secret'] ) ) {
            $signature = hash_hmac( 'sha256', $body, $webhook['raw_secret'] );
            $headers['X-VBP-Signature'] = $signature;
        }

        $response = wp_remote_post( $webhook['url'], array(
            'timeout'   => 30,
            'headers'   => $headers,
            'body'      => $body,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'status'  => 0,
                'body'    => $response->get_error_message(),
            );
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body_response = wp_remote_retrieve_body( $response );

        return array(
            'success' => $status >= 200 && $status < 300,
            'status'  => $status,
            'body'    => $body_response,
        );
    }
}
