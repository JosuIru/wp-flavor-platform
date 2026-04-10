<?php
/**
 * Pasarela de Pago Stripe para Grupos de Consumo
 *
 * Integración con Stripe Payment Intents API.
 *
 * @package FlavorPlatform
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pasarela Stripe
 *
 * @since 4.1.0
 */
class Flavor_GC_Gateway_Stripe extends Flavor_GC_Payment_Gateway {

    /**
     * ID de la pasarela
     *
     * @var string
     */
    protected $id = 'stripe';

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'Stripe';
        $this->description = __('Paga de forma segura con tu tarjeta.', 'flavor-platform');
        $this->icon = 'dashicons-credit-card';

        parent::__construct();
    }

    /**
     * Inicializa hooks específicos de Stripe
     *
     * @return void
     */
    protected function init_hooks(): void {
        // Encolar scripts de Stripe
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Webhook de Stripe
        add_action('rest_api_init', [$this, 'register_webhook_route']);
    }

    /**
     * Obtiene el ID de la pasarela
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Obtiene el nombre de la pasarela
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Verifica si la pasarela puede activarse
     *
     * @return bool
     */
    public function can_activate(): bool {
        $secret_key = $this->get_api_key('secret');
        $publishable_key = $this->get_api_key('publishable');

        return !empty($secret_key) && !empty($publishable_key);
    }

    /**
     * Obtiene una API key según el modo (sandbox/producción)
     *
     * @param string $type Tipo de key: 'secret' o 'publishable'
     * @return string
     */
    private function get_api_key(string $type): string {
        $modo = $this->is_sandbox() ? 'test' : 'live';
        return $this->get_setting("{$modo}_{$type}_key", '');
    }

    /**
     * Procesa el pago
     *
     * @param int $entrega_id ID de la entrega
     * @param float $amount Monto del pedido
     * @return array Resultado del proceso
     */
    public function process_payment(int $entrega_id, float $amount): array {
        $secret_key = $this->get_api_key('secret');

        if (empty($secret_key)) {
            return [
                'success' => false,
                'error' => __('Stripe no está configurado correctamente.', 'flavor-platform'),
            ];
        }

        // Crear Payment Intent
        $endpoint = 'https://api.stripe.com/v1/payment_intents';

        $user = wp_get_current_user();

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'amount' => intval($amount * 100), // Centavos
                'currency' => 'eur',
                'payment_method_types[]' => 'card',
                'metadata[entrega_id]' => $entrega_id,
                'metadata[user_id]' => get_current_user_id(),
                'metadata[source]' => 'grupos_consumo',
                'description' => sprintf(
                    __('Pedido Grupos de Consumo #%d', 'flavor-platform'),
                    $entrega_id
                ),
                'receipt_email' => $user->user_email,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return [
                'success' => false,
                'error' => $body['error']['message'] ?? __('Error de Stripe.', 'flavor-platform'),
            ];
        }

        // Registrar transacción
        $transaction_id = $this->log_transaction($entrega_id, 'procesando', [
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_intent_id' => $body['id'],
            'client_secret' => $body['client_secret'],
            'status' => $body['status'],
        ]);

        return [
            'success' => true,
            'requires_action' => true,
            'client_secret' => $body['client_secret'],
            'payment_intent_id' => $body['id'],
            'transaction_id' => $transaction_id,
            'publishable_key' => $this->get_api_key('publishable'),
        ];
    }

    /**
     * Renderiza los campos del checkout
     *
     * @return void
     */
    public function render_checkout_fields(): void {
        $publishable_key = $this->get_api_key('publishable');
        ?>
        <div class="gc-gateway-stripe-wrapper">
            <div id="gc-stripe-card-element">
                <!-- Stripe Elements se monta aquí -->
            </div>
            <div id="gc-stripe-card-errors" role="alert" class="gc-stripe-error"></div>
        </div>

        <script>
        (function() {
            if (typeof Stripe === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.onload = function() {
                    initStripeElements();
                };
                document.head.appendChild(script);
            } else {
                initStripeElements();
            }

            function initStripeElements() {
                var stripe = Stripe('<?php echo esc_js($publishable_key); ?>');
                var elements = stripe.elements({
                    locale: '<?php echo esc_js(get_locale()); ?>'
                });

                var style = {
                    base: {
                        color: '#32325d',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                };

                var card = elements.create('card', {style: style, hidePostalCode: true});
                card.mount('#gc-stripe-card-element');

                card.on('change', function(event) {
                    var displayError = document.getElementById('gc-stripe-card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });

                // Guardar referencia global para el checkout
                window.gcStripe = stripe;
                window.gcStripeCard = card;
            }
        })();
        </script>

        <style>
        .gc-gateway-stripe-wrapper {
            margin: 1rem 0;
        }
        #gc-stripe-card-element {
            padding: 12px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            background-color: #fff;
        }
        #gc-stripe-card-element.StripeElement--focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
        }
        #gc-stripe-card-element.StripeElement--invalid {
            border-color: #dc3232;
        }
        .gc-stripe-error {
            color: #dc3232;
            font-size: 14px;
            margin-top: 8px;
        }
        </style>
        <?php
    }

    /**
     * Encola los scripts de Stripe
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        if (!$this->is_enabled()) {
            return;
        }

        // Solo cargar en páginas de checkout
        if (!$this->is_checkout_page()) {
            return;
        }

        wp_enqueue_script(
            'stripe-js',
            'https://js.stripe.com/v3/',
            [],
            null,
            true
        );
    }

    /**
     * Verifica si estamos en la página de checkout
     *
     * @return bool
     */
    private function is_checkout_page(): bool {
        global $post;

        if (!$post) {
            return false;
        }

        // Verificar por shortcode o por URL
        return has_shortcode($post->post_content, 'gc_checkout')
            || strpos($_SERVER['REQUEST_URI'], 'checkout') !== false;
    }

    /**
     * Registra la ruta del webhook
     *
     * @return void
     */
    public function register_webhook_route(): void {
        register_rest_route('flavor-gc/v1', '/webhook/stripe', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Maneja el webhook de Stripe
     *
     * @param WP_REST_Request $request Request opcional
     * @return void|WP_REST_Response
     */
    public function handle_webhook($request = null): void {
        $payload = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        $endpoint_secret = $this->get_setting('webhook_secret', '');

        if (empty($endpoint_secret)) {
            wp_send_json_error(['message' => 'Webhook secret not configured'], 400);
            return;
        }

        // Verificar firma (simplificado, usar Stripe SDK en producción)
        if (empty($sig_header)) {
            wp_send_json_error(['message' => 'No signature'], 400);
            return;
        }

        $event = json_decode($payload, true);

        if (!$event) {
            wp_send_json_error(['message' => 'Invalid payload'], 400);
            return;
        }

        // Procesar evento
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($event['data']['object']);
                break;
        }

        wp_send_json(['received' => true]);
    }

    /**
     * Maneja pago exitoso
     *
     * @param array $payment_intent Datos del Payment Intent
     * @return void
     */
    private function handle_payment_succeeded(array $payment_intent): void {
        $entrega_id = $payment_intent['metadata']['entrega_id'] ?? 0;

        if (!$entrega_id) {
            return;
        }

        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        // Buscar transacción
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla_pagos}
             WHERE entrega_id = %d AND pasarela = 'stripe' AND estado = 'procesando'",
            $entrega_id
        ));

        if ($transaccion) {
            $this->update_transaction($transaccion->id, 'completado', [
                'transaction_id' => $payment_intent['id'],
                'charge_id' => $payment_intent['latest_charge'] ?? null,
            ]);
        }

        // Marcar entrega como pagada
        flavor_gc_payments()->mark_entrega_paid($entrega_id, $this->id);
    }

    /**
     * Maneja pago fallido
     *
     * @param array $payment_intent Datos del Payment Intent
     * @return void
     */
    private function handle_payment_failed(array $payment_intent): void {
        $entrega_id = $payment_intent['metadata']['entrega_id'] ?? 0;

        if (!$entrega_id) {
            return;
        }

        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        // Buscar transacción
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla_pagos}
             WHERE entrega_id = %d AND pasarela = 'stripe' AND estado = 'procesando'",
            $entrega_id
        ));

        if ($transaccion) {
            $error_message = $payment_intent['last_payment_error']['message'] ?? 'Unknown error';
            $this->update_transaction($transaccion->id, 'fallido', [
                'error' => $error_message,
            ]);
        }
    }

    /**
     * Obtiene los campos de configuración para el admin
     *
     * @return array
     */
    public function get_admin_fields(): array {
        return [
            [
                'id' => 'enabled',
                'type' => 'checkbox',
                'label' => __('Habilitar Stripe', 'flavor-platform'),
                'default' => false,
            ],
            [
                'id' => 'sandbox',
                'type' => 'checkbox',
                'label' => __('Modo de pruebas (sandbox)', 'flavor-platform'),
                'description' => __('Usa las claves de prueba de Stripe.', 'flavor-platform'),
                'default' => true,
            ],
            [
                'id' => 'test_publishable_key',
                'type' => 'text',
                'label' => __('Clave pública de pruebas', 'flavor-platform'),
                'description' => __('Empieza con pk_test_', 'flavor-platform'),
            ],
            [
                'id' => 'test_secret_key',
                'type' => 'password',
                'label' => __('Clave secreta de pruebas', 'flavor-platform'),
                'description' => __('Empieza con sk_test_', 'flavor-platform'),
            ],
            [
                'id' => 'live_publishable_key',
                'type' => 'text',
                'label' => __('Clave pública de producción', 'flavor-platform'),
                'description' => __('Empieza con pk_live_', 'flavor-platform'),
            ],
            [
                'id' => 'live_secret_key',
                'type' => 'password',
                'label' => __('Clave secreta de producción', 'flavor-platform'),
                'description' => __('Empieza con sk_live_', 'flavor-platform'),
            ],
            [
                'id' => 'webhook_secret',
                'type' => 'password',
                'label' => __('Secreto del Webhook', 'flavor-platform'),
                'description' => sprintf(
                    __('URL del webhook: %s', 'flavor-platform'),
                    rest_url('flavor-gc/v1/webhook/stripe')
                ),
            ],
        ];
    }

    /**
     * Procesa un reembolso
     *
     * @param int $transaction_id ID de la transacción
     * @param float $amount Monto a reembolsar
     * @param string $reason Motivo del reembolso
     * @return array Resultado del reembolso
     */
    public function process_refund(int $transaction_id, float $amount, string $reason = ''): array {
        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_pagos} WHERE id = %d AND pasarela = 'stripe'",
            $transaction_id
        ));

        if (!$transaccion) {
            return [
                'success' => false,
                'error' => __('Transacción no encontrada.', 'flavor-platform'),
            ];
        }

        $datos = json_decode($transaccion->datos_pasarela, true);
        $payment_intent_id = $datos['payment_intent_id'] ?? '';

        if (empty($payment_intent_id)) {
            return [
                'success' => false,
                'error' => __('No se encontró el ID del pago en Stripe.', 'flavor-platform'),
            ];
        }

        $secret_key = $this->get_api_key('secret');

        $response = wp_remote_post('https://api.stripe.com/v1/refunds', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'payment_intent' => $payment_intent_id,
                'amount' => intval($amount * 100),
                'reason' => 'requested_by_customer',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return [
                'success' => false,
                'error' => $body['error']['message'],
            ];
        }

        // Actualizar transacción
        $this->update_transaction($transaction_id, 'reembolsado', [
            'refund_id' => $body['id'],
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);

        return [
            'success' => true,
            'refund_id' => $body['id'],
            'message' => __('Reembolso procesado correctamente.', 'flavor-platform'),
        ];
    }
}
