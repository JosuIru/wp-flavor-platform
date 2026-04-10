<?php
/**
 * Gateway de pago Stripe
 * Implementación de Stripe Payment Gateway para pagos online
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/class-payment-gateway.php';

/**
 * Stripe Payment Gateway
 *
 * Integración completa con Stripe para procesar pagos de facturas.
 * Usa Stripe Checkout para una experiencia segura y optimizada.
 *
 * Documentación: https://stripe.com/docs/checkout
 */
class Flavor_Stripe_Gateway extends Flavor_Payment_Gateway {

    /**
     * API Key secreta de Stripe
     *
     * @var string
     */
    private $secret_key;

    /**
     * API Key pública de Stripe
     *
     * @var string
     */
    private $publishable_key;

    /**
     * Webhook secret para verificar eventos
     *
     * @var string
     */
    private $webhook_secret;

    /**
     * Inicializar gateway
     */
    protected function init() {
        $this->id = 'stripe';
        $this->name = __('Stripe', 'flavor-platform');
        $this->description = __('Pago seguro con tarjeta de crédito/débito via Stripe', 'flavor-platform');

        // Cargar credenciales según modo
        $this->load_credentials();

        // Registrar webhook endpoint
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    /**
     * Cargar credenciales de Stripe
     */
    private function load_credentials() {
        if ($this->test_mode) {
            $this->secret_key = $this->get_setting('test_secret_key', '');
            $this->publishable_key = $this->get_setting('test_publishable_key', '');
            $this->webhook_secret = $this->get_setting('test_webhook_secret', '');
        } else {
            $this->secret_key = $this->get_setting('live_secret_key', '');
            $this->publishable_key = $this->get_setting('live_publishable_key', '');
            $this->webhook_secret = $this->get_setting('live_webhook_secret', '');
        }
    }

    /**
     * Validar credenciales
     */
    protected function validate_credentials() {
        return !empty($this->secret_key) && !empty($this->publishable_key);
    }

    /**
     * Procesar pago
     *
     * @param array $payment_data Datos del pago:
     *   - factura_id (int): ID de la factura
     *   - importe (float): Cantidad a pagar
     *   - factura (array): Datos completos de la factura
     *
     * @return array|WP_Error URL de checkout o error
     */
    public function process_payment($payment_data) {
        if (!$this->is_available()) {
            return new WP_Error('gateway_no_disponible', __('Stripe no está disponible', 'flavor-platform'));
        }

        $factura_id = absint($payment_data['factura_id']);
        $importe = floatval($payment_data['importe']);
        $factura = $payment_data['factura'];

        if ($importe <= 0) {
            return new WP_Error('importe_invalido', __('Importe inválido', 'flavor-platform'));
        }

        try {
            // Crear sesión de Stripe Checkout
            $session = $this->create_checkout_session($factura_id, $importe, $factura);

            if (is_wp_error($session)) {
                return $session;
            }

            return [
                'success' => true,
                'redirect_url' => $session['url'],
                'session_id' => $session['id'],
            ];
        } catch (Exception $e) {
            $this->log('Error al crear sesión de pago: ' . $e->getMessage(), 'error');
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Crear sesión de Stripe Checkout
     *
     * @param int   $factura_id ID de la factura
     * @param float $importe Cantidad a pagar
     * @param array $factura Datos de la factura
     * @return array|WP_Error Datos de la sesión o error
     */
    private function create_checkout_session($factura_id, $importe, $factura) {
        $cantidad_centavos = $this->format_amount($importe);
        $moneda = strtolower($this->get_currency());

        $datos_sesion = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $moneda,
                    'product_data' => [
                        'name' => sprintf(__('Factura %s', 'flavor-platform'), $factura['numero_factura'] ?? ''),
                        'description' => $this->create_payment_description($factura),
                    ],
                    'unit_amount' => $cantidad_centavos,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->get_return_url($factura_id) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->get_cancel_url($factura_id),
            'metadata' => $this->get_payment_metadata($factura_id, $factura),
            'customer_email' => $factura['cliente_email'] ?? '',
        ];

        // Realizar petición a Stripe API
        $response = $this->make_api_request('checkout/sessions', 'POST', $datos_sesion);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar session_id en meta temporal para tracking
        update_post_meta($factura_id, '_stripe_session_id', $response['id']);
        update_post_meta($factura_id, '_stripe_session_data', [
            'session_id' => $response['id'],
            'importe' => $importe,
            'fecha_creacion' => current_time('mysql'),
        ]);

        return $response;
    }

    /**
     * Realizar petición a la API de Stripe
     *
     * @param string $endpoint Endpoint de la API
     * @param string $metodo Método HTTP (GET, POST, etc.)
     * @param array  $datos Datos a enviar
     * @return array|WP_Error Respuesta de la API o error
     */
    private function make_api_request($endpoint, $metodo = 'GET', $datos = []) {
        $url = 'https://api.stripe.com/v1/' . $endpoint;

        $args = [
            'method' => $metodo,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 30,
        ];

        if ($metodo === 'POST' && !empty($datos)) {
            $args['body'] = $this->build_query_string($datos);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log('Error en petición API: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($response);
        $cuerpo = wp_remote_retrieve_body($response);
        $datos_respuesta = json_decode($cuerpo, true);

        if ($codigo_respuesta !== 200) {
            $mensaje_error = $datos_respuesta['error']['message'] ?? __('Error desconocido de Stripe', 'flavor-platform');
            $this->log("Error API (código {$codigo_respuesta}): {$mensaje_error}", 'error');
            return new WP_Error('stripe_api_error', $mensaje_error);
        }

        return $datos_respuesta;
    }

    /**
     * Construir query string para Stripe (formato especial)
     *
     * @param array  $datos Datos a convertir
     * @param string $prefijo Prefijo para arrays anidados
     * @return string
     */
    private function build_query_string($datos, $prefijo = '') {
        $query = [];

        foreach ($datos as $key => $value) {
            $clave_completa = empty($prefijo) ? $key : "{$prefijo}[{$key}]";

            if (is_array($value)) {
                $query[] = $this->build_query_string($value, $clave_completa);
            } else {
                $query[] = rawurlencode($clave_completa) . '=' . rawurlencode((string) $value);
            }
        }

        return implode('&', $query);
    }

    /**
     * Procesar webhook de Stripe
     *
     * @return array|WP_Error Resultado del procesamiento
     */
    public function process_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (empty($payload) || empty($sig_header)) {
            $this->log('Webhook recibido sin payload o firma', 'warning');
            return new WP_Error('webhook_invalido', 'Payload o firma faltante');
        }

        // Verificar firma del webhook
        if (!empty($this->webhook_secret)) {
            $evento = $this->verify_webhook_signature($payload, $sig_header);
            if (is_wp_error($evento)) {
                return $evento;
            }
        } else {
            // Sin webhook_secret, decodificar directamente (menos seguro)
            $evento = json_decode($payload, true);
        }

        $tipo_evento = $evento['type'] ?? '';

        $this->log("Webhook recibido: {$tipo_evento}", 'info');

        switch ($tipo_evento) {
            case 'checkout.session.completed':
                return $this->handle_checkout_completed($evento['data']['object']);

            case 'payment_intent.succeeded':
                return $this->handle_payment_succeeded($evento['data']['object']);

            case 'payment_intent.payment_failed':
                return $this->handle_payment_failed($evento['data']['object']);

            default:
                $this->log("Tipo de evento no manejado: {$tipo_evento}", 'info');
                return ['success' => true, 'message' => 'Evento ignorado'];
        }
    }

    /**
     * Verificar firma del webhook
     *
     * @param string $payload Cuerpo del webhook
     * @param string $sig_header Cabecera de firma
     * @return array|WP_Error Evento verificado o error
     */
    private function verify_webhook_signature($payload, $sig_header) {
        $timestamp = 0;
        $firma = '';

        // Parsear header de firma
        $elementos = explode(',', $sig_header);
        foreach ($elementos as $elemento) {
            list($clave, $valor) = explode('=', $elemento, 2);
            if ($clave === 't') {
                $timestamp = intval($valor);
            } elseif ($clave === 'v1') {
                $firma = $valor;
            }
        }

        if (empty($timestamp) || empty($firma)) {
            return new WP_Error('webhook_firma_invalida', 'Firma mal formada');
        }

        // Verificar tolerancia de tiempo (5 minutos)
        $diferencia_tiempo = abs(time() - $timestamp);
        if ($diferencia_tiempo > 300) {
            return new WP_Error('webhook_expirado', 'Webhook expirado');
        }

        // Calcular firma esperada
        $firma_esperada = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhook_secret);

        if (!hash_equals($firma_esperada, $firma)) {
            return new WP_Error('webhook_firma_invalida', 'Firma no válida');
        }

        return json_decode($payload, true);
    }

    /**
     * Manejar evento checkout.session.completed
     *
     * @param array $session Datos de la sesión
     * @return array Resultado del procesamiento
     */
    private function handle_checkout_completed($session) {
        $factura_id = absint($session['metadata']['factura_id'] ?? 0);

        if (!$factura_id) {
            $this->log('Checkout completado sin factura_id en metadata', 'warning');
            return ['success' => false, 'message' => 'factura_id no encontrado'];
        }

        $importe = $session['amount_total'] / 100; // Convertir de centavos a euros
        $payment_intent = $session['payment_intent'] ?? '';

        // Registrar pago en el sistema
        $modulo_facturas = $this->get_facturas_module();
        if (!$modulo_facturas) {
            return new WP_Error('modulo_no_encontrado', 'Módulo facturas no disponible');
        }

        $resultado_pago = $modulo_facturas->registrar_pago([
            'factura_id' => $factura_id,
            'importe' => $importe,
            'fecha_pago' => current_time('Y-m-d'),
            'metodo_pago' => 'stripe',
            'referencia' => $payment_intent,
            'notas' => sprintf(__('Pago procesado automáticamente via Stripe. Session ID: %s', 'flavor-platform'), $session['id']),
            'estado' => 'confirmado',
        ]);

        if (is_wp_error($resultado_pago)) {
            $this->log('Error al registrar pago: ' . $resultado_pago->get_error_message(), 'error');
            return ['success' => false, 'message' => $resultado_pago->get_error_message()];
        }

        $this->log("Pago registrado correctamente para factura #{$factura_id}", 'info');

        return [
            'success' => true,
            'message' => 'Pago procesado',
            'factura_id' => $factura_id,
            'pago_id' => $resultado_pago,
        ];
    }

    /**
     * Manejar evento payment_intent.succeeded
     *
     * @param array $payment_intent Datos del payment intent
     * @return array Resultado
     */
    private function handle_payment_succeeded($payment_intent) {
        $this->log('Payment Intent succeeded: ' . $payment_intent['id'], 'info');
        return ['success' => true, 'message' => 'Payment Intent succeeded'];
    }

    /**
     * Manejar evento payment_intent.payment_failed
     *
     * @param array $payment_intent Datos del payment intent
     * @return array Resultado
     */
    private function handle_payment_failed($payment_intent) {
        $this->log('Payment Intent failed: ' . $payment_intent['id'], 'warning');
        return ['success' => true, 'message' => 'Payment Intent failed (notificado)'];
    }

    /**
     * Obtener instancia del módulo de facturas
     *
     * @return Flavor_Platform_Module_Interface|null
     */
    private function get_facturas_module() {
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            return $loader->get_module('facturas');
        }
        return null;
    }

    /**
     * Registrar endpoint para webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route('flavor/v1', '/facturas/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook_request'],
            'permission_callback' => '__return_true', // Stripe verifica con firma
        ]);
    }

    /**
     * Manejar petición de webhook REST API
     *
     * @param WP_REST_Request $request Petición
     * @return WP_REST_Response Respuesta
     */
    public function handle_webhook_request($request) {
        $resultado = $this->process_webhook();

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * Obtener URL de retorno tras pago exitoso
     *
     * @param int $factura_id ID de la factura
     * @return string
     */
    public function get_return_url($factura_id) {
        $base_url = home_url('/facturas/pago-exitoso/');
        return add_query_arg([
            'factura_id' => $factura_id,
            'gateway' => 'stripe',
        ], $base_url);
    }

    /**
     * Obtener URL de cancelación
     *
     * @param int $factura_id ID de la factura
     * @return string
     */
    public function get_cancel_url($factura_id) {
        $base_url = home_url('/facturas/pago-cancelado/');
        return add_query_arg([
            'factura_id' => $factura_id,
            'gateway' => 'stripe',
        ], $base_url);
    }

    /**
     * Obtener publishable key para frontend
     *
     * @return string
     */
    public function get_publishable_key() {
        return $this->publishable_key;
    }
}
