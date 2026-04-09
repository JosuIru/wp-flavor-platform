<?php
/**
 * Gateway de pago Stripe para módulo Socios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway Stripe para pagos de cuotas
 */
class Flavor_Socios_Gateway_Stripe {

    /**
     * ID del gateway
     *
     * @var string
     */
    public $id = 'stripe';

    /**
     * Clave pública de Stripe
     *
     * @var string
     */
    private $clave_publica;

    /**
     * Clave secreta de Stripe
     *
     * @var string
     */
    private $clave_secreta;

    /**
     * Webhook secret
     *
     * @var string
     */
    private $webhook_secret;

    /**
     * Modo sandbox
     *
     * @var bool
     */
    private $modo_test;

    /**
     * Constructor
     */
    public function __construct() {
        $opciones = get_option('flavor_socios_stripe_settings', []);

        $this->modo_test = !empty($opciones['test_mode']);

        if ($this->modo_test) {
            $this->clave_publica = $opciones['test_publishable_key'] ?? '';
            $this->clave_secreta = $opciones['test_secret_key'] ?? '';
        } else {
            $this->clave_publica = $opciones['live_publishable_key'] ?? '';
            $this->clave_secreta = $opciones['live_secret_key'] ?? '';
        }

        $this->webhook_secret = $opciones['webhook_secret'] ?? '';
    }

    /**
     * Verifica si el gateway está configurado
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->clave_publica) && !empty($this->clave_secreta);
    }

    /**
     * Obtiene la clave pública para el frontend
     *
     * @return string
     */
    public function get_publishable_key() {
        return $this->clave_publica;
    }

    /**
     * Procesa un pago
     *
     * @param object $cuota          Datos de la cuota
     * @param int    $transaccion_id ID de transacción interna
     * @param array  $datos          Datos adicionales
     * @return array
     */
    public function procesar($cuota, $transaccion_id, $datos = []) {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'error'   => __('Stripe no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        try {
            // Crear sesión de Checkout
            $session = $this->crear_checkout_session($cuota, $transaccion_id);

            if (is_wp_error($session)) {
                return [
                    'success' => false,
                    'error'   => $session->get_error_message(),
                ];
            }

            return [
                'success'         => true,
                'tipo'            => 'redirect',
                'checkout_url'    => $session['url'],
                'session_id'      => $session['id'],
                'transaccion_id'  => $transaccion_id,
            ];

        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Crea una sesión de Stripe Checkout
     *
     * @param object $cuota          Cuota a pagar
     * @param int    $transaccion_id ID de transacción
     * @return array|WP_Error
     */
    private function crear_checkout_session($cuota, $transaccion_id) {
        $url_api = 'https://api.stripe.com/v1/checkout/sessions';

        // Obtener datos del socio
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.user_email, u.display_name
             FROM $tabla_socios s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE s.id = %d",
            $cuota->socio_id
        ));

        $importe_centavos = intval($cuota->importe * 100);

        $parametros = [
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => 'eur',
            'line_items[0][price_data][product_data][name]' => sprintf(
                __('Cuota de socio - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $cuota->periodo
            ),
            'line_items[0][price_data][product_data][description]' => sprintf(
                __('Socio #%s - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $socio->numero_socio,
                $socio->display_name
            ),
            'line_items[0][price_data][unit_amount]' => $importe_centavos,
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => add_query_arg([
                'socios_pago'    => 'exitoso',
                'transaccion_id' => $transaccion_id,
                'session_id'     => '{CHECKOUT_SESSION_ID}',
            ], Flavor_Chat_Helpers::get_action_url('socios', 'pagar-cuota')),
            'cancel_url' => add_query_arg([
                'socios_pago' => 'cancelado',
            ], Flavor_Chat_Helpers::get_action_url('socios', 'pagar-cuota')),
            'metadata[transaccion_id]' => $transaccion_id,
            'metadata[cuota_id]' => $cuota->id,
            'metadata[socio_id]' => $cuota->socio_id,
            'customer_email' => $socio->user_email,
        ];

        $respuesta = wp_remote_post($url_api, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->clave_secreta . ':'),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => $parametros,
            'timeout' => 30,
        ]);

        if (is_wp_error($respuesta)) {
            return $respuesta;
        }

        $codigo = wp_remote_retrieve_response_code($respuesta);
        $cuerpo = json_decode(wp_remote_retrieve_body($respuesta), true);

        if ($codigo !== 200) {
            $mensaje_error = $cuerpo['error']['message'] ?? __('Error al crear sesión de pago.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            return new WP_Error('stripe_error', $mensaje_error);
        }

        return $cuerpo;
    }

    /**
     * Maneja webhooks de Stripe
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $firma_header = $request->get_header('Stripe-Signature');

        // Verificar firma si hay webhook secret
        if ($this->webhook_secret) {
            if (!$this->verificar_firma_webhook($payload, $firma_header)) {
                return new WP_REST_Response(['error' => 'Invalid signature'], 400);
            }
        }

        $evento = json_decode($payload, true);

        if (!$evento || !isset($evento['type'])) {
            return new WP_REST_Response(['error' => 'Invalid event'], 400);
        }

        switch ($evento['type']) {
            case 'checkout.session.completed':
                $this->handle_checkout_completed($evento['data']['object']);
                break;

            case 'payment_intent.succeeded':
                // Manejar si es necesario
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($evento['data']['object']);
                break;
        }

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Verifica la firma del webhook
     *
     * @param string $payload     Cuerpo del request
     * @param string $firma_header Header de firma
     * @return bool
     */
    private function verificar_firma_webhook($payload, $firma_header) {
        if (!$firma_header || !$this->webhook_secret) {
            return false;
        }

        $elementos = [];
        foreach (explode(',', $firma_header) as $parte) {
            $par = explode('=', $parte, 2);
            if (count($par) === 2) {
                $elementos[$par[0]] = $par[1];
            }
        }

        if (!isset($elementos['t']) || !isset($elementos['v1'])) {
            return false;
        }

        $timestamp = $elementos['t'];
        $firma_esperada = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $this->webhook_secret
        );

        return hash_equals($firma_esperada, $elementos['v1']);
    }

    /**
     * Maneja checkout completado
     *
     * @param array $session Datos de la sesión
     */
    private function handle_checkout_completed($session) {
        $transaccion_id = $session['metadata']['transaccion_id'] ?? null;

        if (!$transaccion_id) {
            return;
        }

        $payment_manager = Flavor_Socios_Payment_Manager::get_instance();
        $payment_manager->confirmar_pago(
            $transaccion_id,
            $session['payment_intent'],
            [
                'stripe_session_id' => $session['id'],
                'stripe_payment_intent' => $session['payment_intent'],
                'amount_received' => $session['amount_total'],
            ]
        );
    }

    /**
     * Maneja pago fallido
     *
     * @param array $payment_intent Datos del payment intent
     */
    private function handle_payment_failed($payment_intent) {
        $transaccion_id = $payment_intent['metadata']['transaccion_id'] ?? null;

        if (!$transaccion_id) {
            return;
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        $wpdb->update(
            $tabla_transacciones,
            [
                'estado'           => 'fallida',
                'gateway_response' => wp_json_encode($payment_intent),
            ],
            ['id' => $transaccion_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Verifica el estado de una sesión de checkout
     *
     * @param string $session_id ID de la sesión
     * @return array|WP_Error
     */
    public function verificar_sesion($session_id) {
        $url_api = 'https://api.stripe.com/v1/checkout/sessions/' . $session_id;

        $respuesta = wp_remote_get($url_api, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->clave_secreta . ':'),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($respuesta)) {
            return $respuesta;
        }

        return json_decode(wp_remote_retrieve_body($respuesta), true);
    }
}
