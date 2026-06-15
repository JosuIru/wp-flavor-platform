<?php
/**
 * Gateway de pago PayPal
 * Implementación de PayPal para pagos online (Smart Payment Buttons)
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/class-payment-gateway.php';

/**
 * PayPal Payment Gateway
 *
 * Integración con PayPal usando Smart Payment Buttons API.
 * Documentación: https://developer.paypal.com/docs/checkout/
 */
class Flavor_PayPal_Gateway extends Flavor_Payment_Gateway {

    /**
     * Client ID de PayPal
     *
     * @var string
     */
    private $client_id;

    /**
     * Client Secret de PayPal
     *
     * @var string
     */
    private $client_secret;

    /**
     * Inicializar gateway
     */
    protected function init() {
        $this->id = 'paypal';
        $this->name = __('PayPal', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->description = __('Pago seguro con PayPal o tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN);

        $this->load_credentials();

        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    /**
     * Cargar credenciales
     */
    private function load_credentials() {
        if ($this->test_mode) {
            $this->client_id = $this->get_setting('test_client_id', '');
            $this->client_secret = $this->get_setting('test_client_secret', '');
        } else {
            $this->client_id = $this->get_setting('live_client_id', '');
            $this->client_secret = $this->get_setting('live_client_secret', '');
        }
    }

    /**
     * Validar credenciales
     */
    protected function validate_credentials() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Procesar pago
     */
    public function process_payment($payment_data) {
        if (!$this->is_available()) {
            return new WP_Error('gateway_no_disponible', __('PayPal no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $factura_id = absint($payment_data['factura_id']);
        $importe = floatval($payment_data['importe']);
        $factura = $payment_data['factura'];

        try {
            $access_token = $this->get_access_token();
            if (is_wp_error($access_token)) {
                return $access_token;
            }

            $order = $this->create_order($factura_id, $importe, $factura, $access_token);
            if (is_wp_error($order)) {
                return $order;
            }

            // Obtener approve URL
            $approve_url = '';
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approve_url = $link['href'];
                    break;
                }
            }

            if (empty($approve_url)) {
                return new WP_Error('paypal_error', __('No se pudo obtener URL de aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            // Guardar order_id
            update_post_meta($factura_id, '_paypal_order_id', $order['id']);

            return [
                'success' => true,
                'redirect_url' => $approve_url,
                'order_id' => $order['id'],
            ];
        } catch (Exception $e) {
            $this->log('Error PayPal: ' . $e->getMessage(), 'error');
            return new WP_Error('paypal_error', $e->getMessage());
        }
    }

    /**
     * Obtener access token de PayPal
     *
     * @return string|WP_Error
     */
    private function get_access_token() {
        $api_base = $this->test_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        $url = $api_base . '/v1/oauth2/token';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'grant_type=client_credentials',
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['access_token'] ?? new WP_Error('paypal_auth_error', __('Error al autenticar con PayPal', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * Crear order en PayPal
     */
    private function create_order($factura_id, $importe, $factura, $access_token) {
        $api_base = $this->test_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        $url = $api_base . '/v2/checkout/orders';

        $moneda = $this->get_currency();

        $order_data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'factura_' . $factura_id,
                'description' => $this->create_payment_description($factura),
                'amount' => [
                    'currency_code' => $moneda,
                    'value' => number_format($importe, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $this->get_return_url($factura_id),
                'cancel_url' => $this->get_cancel_url($factura_id),
                'brand_name' => get_bloginfo('name'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($order_data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('paypal_create_order_error', $body['error']['message'] ?? __('Error al crear order', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        return $body;
    }

    /**
     * Procesar webhook
     */
    public function process_webhook() {
        $payload = @file_get_contents('php://input');

        // Verificar la autenticidad del webhook ANTES de confiar en su contenido.
        // Sin esta comprobación, cualquiera podría marcar facturas como pagadas.
        $verificacion = $this->verify_webhook_signature($payload);
        if (is_wp_error($verificacion)) {
            $this->log('Webhook PayPal rechazado: ' . $verificacion->get_error_message(), 'warning');
            return $verificacion;
        }

        $evento = json_decode($payload, true);

        $tipo_evento = $evento['event_type'] ?? '';

        $this->log("Webhook PayPal recibido: {$tipo_evento}", 'info');

        if ($tipo_evento === 'CHECKOUT.ORDER.COMPLETED' || $tipo_evento === 'PAYMENT.CAPTURE.COMPLETED') {
            return $this->handle_order_completed($evento);
        }

        return ['success' => true, 'message' => 'Evento ignorado'];
    }

    /**
     * Verificar la firma del webhook contra la API de PayPal.
     *
     * Usa el endpoint oficial verify-webhook-signature de PayPal con las
     * cabeceras de transmisión y el webhook ID configurado. Fail-closed:
     * si falta el webhook ID o la verificación no es SUCCESS, se rechaza.
     *
     * @param string $payload Cuerpo crudo recibido.
     * @return true|WP_Error
     */
    private function verify_webhook_signature($payload) {
        $webhook_id = $this->test_mode
            ? $this->get_setting('test_webhook_id', '')
            : $this->get_setting('live_webhook_id', '');
        if (empty($webhook_id)) {
            $webhook_id = $this->get_setting('webhook_id', '');
        }

        if (empty($webhook_id)) {
            return new WP_Error('paypal_webhook_no_config', __('Webhook ID de PayPal no configurado; no se puede verificar la firma.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $cabeceras = [
            'transmission_id'   => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '',
            'transmission_time' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '',
            'cert_url'          => $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '',
            'auth_algo'         => $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '',
            'transmission_sig'  => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '',
        ];
        foreach ($cabeceras as $valor) {
            if (empty($valor)) {
                return new WP_Error('paypal_webhook_sin_firma', __('Faltan cabeceras de firma de PayPal.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }
        }

        $access_token = $this->get_access_token();
        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $api_base = $this->test_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        $cuerpo_verificacion = [
            'transmission_id'   => sanitize_text_field(wp_unslash($cabeceras['transmission_id'])),
            'transmission_time' => sanitize_text_field(wp_unslash($cabeceras['transmission_time'])),
            'cert_url'          => esc_url_raw(wp_unslash($cabeceras['cert_url'])),
            'auth_algo'         => sanitize_text_field(wp_unslash($cabeceras['auth_algo'])),
            'transmission_sig'  => sanitize_text_field(wp_unslash($cabeceras['transmission_sig'])),
            'webhook_id'        => $webhook_id,
            'webhook_event'     => json_decode($payload, true),
        ];

        $response = wp_remote_post($api_base . '/v1/notifications/verify-webhook-signature', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($cuerpo_verificacion),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (($body['verification_status'] ?? '') !== 'SUCCESS') {
            return new WP_Error('paypal_webhook_invalido', __('La firma del webhook de PayPal no se pudo verificar.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        return true;
    }

    /**
     * Manejar order completada
     */
    private function handle_order_completed($evento) {
        $order_id = $evento['resource']['id'] ?? '';

        // Buscar factura por order_id
        global $wpdb;
        $factura_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_paypal_order_id' AND meta_value = %s",
            $order_id
        ));

        if (!$factura_id) {
            $this->log("Order {$order_id} no encontrada en sistema", 'warning');
            return ['success' => false, 'message' => 'Order no encontrada'];
        }

        $purchase_units = $evento['resource']['purchase_units'] ?? [];
        $importe = floatval($purchase_units[0]['amount']['value'] ?? 0);

        if ($importe <= 0) {
            return ['success' => false, 'message' => 'Importe inválido'];
        }

        $modulo_facturas = $this->get_facturas_module();
        if (!$modulo_facturas) {
            return new WP_Error('modulo_no_encontrado', 'Módulo facturas no disponible');
        }

        $resultado_pago = $modulo_facturas->registrar_pago([
            'factura_id' => $factura_id,
            'importe' => $importe,
            'fecha_pago' => current_time('Y-m-d'),
            'metodo_pago' => 'paypal',
            'referencia' => $order_id,
            'notas' => sprintf(__('Pago procesado automáticamente via PayPal. Order ID: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $order_id),
        ]);

        if (is_wp_error($resultado_pago)) {
            $this->log('Error al registrar pago: ' . $resultado_pago->get_error_message(), 'error');
            return ['success' => false, 'message' => $resultado_pago->get_error_message()];
        }

        $this->log("Pago PayPal registrado para factura #{$factura_id}", 'info');

        return ['success' => true, 'message' => 'Pago procesado', 'factura_id' => $factura_id];
    }

    /**
     * Obtener instancia del módulo de facturas
     */
    private function get_facturas_module() {
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            return $loader->get_module('facturas');
        }
        return null;
    }

    /**
     * Registrar endpoint webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route('flavor/v1', '/facturas/paypal-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook_request'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Manejar petición webhook
     */
    public function handle_webhook_request($request) {
        $resultado = $this->process_webhook();

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado->get_error_message()], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * URL de retorno
     */
    public function get_return_url($factura_id) {
        return add_query_arg([
            'factura_id' => $factura_id,
            'gateway' => 'paypal',
        ], home_url('/facturas/pago-exitoso/'));
    }

    /**
     * URL de cancelación
     */
    public function get_cancel_url($factura_id) {
        return add_query_arg([
            'factura_id' => $factura_id,
            'gateway' => 'paypal',
        ], home_url('/facturas/pago-cancelado/'));
    }
}
