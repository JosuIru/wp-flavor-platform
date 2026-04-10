<?php
/**
 * Pasarela de Pago PayPal para Grupos de Consumo
 *
 * Integración con PayPal REST API (Orders v2).
 *
 * @package FlavorPlatform
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pasarela PayPal
 *
 * @since 4.1.0
 */
class Flavor_GC_Gateway_PayPal extends Flavor_GC_Payment_Gateway {

    /**
     * ID de la pasarela
     *
     * @var string
     */
    protected $id = 'paypal';

    /**
     * URL base de la API
     *
     * @var string
     */
    private $api_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'PayPal';
        $this->description = __('Paga de forma segura con PayPal.', 'flavor-platform');
        $this->icon = 'dashicons-money';

        parent::__construct();

        $this->api_url = $this->is_sandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
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
        $client_id = $this->get_credential('client_id');
        $client_secret = $this->get_credential('client_secret');

        return !empty($client_id) && !empty($client_secret);
    }

    /**
     * Obtiene una credencial según el modo
     *
     * @param string $key Clave de la credencial
     * @return string
     */
    private function get_credential(string $key): string {
        $modo = $this->is_sandbox() ? 'sandbox' : 'live';
        return $this->get_setting("{$modo}_{$key}", '');
    }

    /**
     * Obtiene token de acceso OAuth
     *
     * @return string|null
     */
    private function get_access_token(): ?string {
        $cache_key = 'gc_paypal_token_' . ($this->is_sandbox() ? 'sandbox' : 'live');
        $cached = get_transient($cache_key);

        if ($cached) {
            return $cached;
        }

        $client_id = $this->get_credential('client_id');
        $client_secret = $this->get_credential('client_secret');

        $response = wp_remote_post($this->api_url . '/v1/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'grant_type=client_credentials',
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token'])) {
            return null;
        }

        // Cachear token (menos el tiempo de expiración por seguridad)
        $expires = isset($body['expires_in']) ? $body['expires_in'] - 300 : 3300;
        set_transient($cache_key, $body['access_token'], $expires);

        return $body['access_token'];
    }

    /**
     * Procesa el pago
     *
     * @param int $entrega_id ID de la entrega
     * @param float $amount Monto del pedido
     * @return array Resultado del proceso
     */
    public function process_payment(int $entrega_id, float $amount): array {
        $access_token = $this->get_access_token();

        if (!$access_token) {
            return [
                'success' => false,
                'error' => __('Error de autenticación con PayPal.', 'flavor-platform'),
            ];
        }

        // Crear orden en PayPal
        $response = wp_remote_post($this->api_url . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => 'gc-' . $entrega_id . '-' . time(),
            ],
            'body' => wp_json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'entrega-' . $entrega_id,
                    'description' => sprintf(__('Pedido Grupos de Consumo #%d', 'flavor-platform'), $entrega_id),
                    'custom_id' => (string) $entrega_id,
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => get_bloginfo('name'),
                    'landing_page' => 'NO_PREFERENCE',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => add_query_arg([
                        'gc_paypal_return' => 1,
                        'entrega_id' => $entrega_id,
                    ], home_url('/mi-portal/grupos-consumo/checkout/')),
                    'cancel_url' => add_query_arg([
                        'gc_paypal_cancel' => 1,
                        'entrega_id' => $entrega_id,
                    ], home_url('/mi-portal/grupos-consumo/checkout/')),
                ],
            ]),
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
                'error' => $body['error_description'] ?? $body['message'] ?? __('Error de PayPal.', 'flavor-platform'),
            ];
        }

        if (empty($body['id'])) {
            return [
                'success' => false,
                'error' => __('No se pudo crear la orden en PayPal.', 'flavor-platform'),
            ];
        }

        // Obtener URL de aprobación
        $approve_url = '';
        foreach ($body['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approve_url = $link['href'];
                break;
            }
        }

        // Registrar transacción
        $transaction_id = $this->log_transaction($entrega_id, 'procesando', [
            'amount' => $amount,
            'currency' => 'EUR',
            'order_id' => $body['id'],
            'status' => $body['status'],
        ]);

        return [
            'success' => true,
            'requires_action' => true,
            'redirect_url' => $approve_url,
            'order_id' => $body['id'],
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * Captura el pago después de la aprobación
     *
     * @param string $order_id ID de la orden de PayPal
     * @return array
     */
    public function capture_payment(string $order_id): array {
        $access_token = $this->get_access_token();

        if (!$access_token) {
            return [
                'success' => false,
                'error' => __('Error de autenticación con PayPal.', 'flavor-platform'),
            ];
        }

        $response = wp_remote_post($this->api_url . "/v2/checkout/orders/{$order_id}/capture", [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => '{}',
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] !== 'COMPLETED') {
            return [
                'success' => false,
                'error' => __('El pago no se completó.', 'flavor-platform'),
            ];
        }

        // Obtener entrega_id del custom_id
        $entrega_id = 0;
        foreach ($body['purchase_units'] ?? [] as $unit) {
            if (!empty($unit['custom_id'])) {
                $entrega_id = (int) $unit['custom_id'];
                break;
            }
        }

        if (!$entrega_id) {
            return [
                'success' => false,
                'error' => __('No se encontró la entrega asociada.', 'flavor-platform'),
            ];
        }

        // Actualizar transacción
        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla_pagos}
             WHERE entrega_id = %d AND pasarela = 'paypal' AND estado = 'procesando'",
            $entrega_id
        ));

        if ($transaccion) {
            $capture = $body['purchase_units'][0]['payments']['captures'][0] ?? [];

            $this->update_transaction($transaccion->id, 'completado', [
                'transaction_id' => $capture['id'] ?? $order_id,
                'capture_id' => $capture['id'] ?? null,
                'payer_email' => $body['payer']['email_address'] ?? '',
            ]);
        }

        // Marcar entrega como pagada
        flavor_gc_payments()->mark_entrega_paid($entrega_id, $this->id);

        return [
            'success' => true,
            'message' => __('Pago completado correctamente.', 'flavor-platform'),
            'entrega_id' => $entrega_id,
        ];
    }

    /**
     * Renderiza los campos del checkout
     *
     * @return void
     */
    public function render_checkout_fields(): void {
        $client_id = $this->get_credential('client_id');
        ?>
        <div class="gc-gateway-paypal-wrapper">
            <div id="gc-paypal-button-container"></div>
            <p class="gc-paypal-message">
                <?php esc_html_e('Serás redirigido a PayPal para completar el pago de forma segura.', 'flavor-platform'); ?>
            </p>
        </div>

        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr($client_id); ?>&currency=EUR&locale=<?php echo esc_attr(str_replace('_', '-', get_locale())); ?>"></script>
        <script>
        (function() {
            if (typeof paypal === 'undefined') {
                console.error('PayPal SDK not loaded');
                return;
            }

            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal'
                },
                createOrder: function(data, actions) {
                    // Obtener el order_id del backend
                    return gcPaypalOrderId || null;
                },
                onApprove: function(data, actions) {
                    // Redirigir a la URL de captura
                    var captureUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                    var nonce = '<?php echo esc_js(wp_create_nonce('gc_paypal_capture')); ?>';
                    window.location.href = captureUrl + '?action=gc_paypal_capture&order_id=' + encodeURIComponent(data.orderID) + '&nonce=' + encodeURIComponent(nonce);
                },
                onCancel: function(data) {
                    document.getElementById('gc-paypal-message').textContent = '<?php echo esc_js(__('Pago cancelado.', 'flavor-platform')); ?>';
                },
                onError: function(err) {
                    console.error('PayPal error:', err);
                    document.getElementById('gc-paypal-message').textContent = '<?php echo esc_js(__('Error en el pago. Inténtalo de nuevo.', 'flavor-platform')); ?>';
                }
            }).render('#gc-paypal-button-container');
        })();
        </script>

        <style>
        .gc-gateway-paypal-wrapper {
            margin: 1rem 0;
        }
        #gc-paypal-button-container {
            max-width: 300px;
            margin: 0 auto;
        }
        .gc-paypal-message {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        </style>
        <?php
    }

    /**
     * Inicializa hooks específicos
     *
     * @return void
     */
    protected function init_hooks(): void {
        // AJAX para capturar pago
        add_action('wp_ajax_gc_paypal_capture', [$this, 'ajax_capture_payment']);

        // Manejar retorno de PayPal
        add_action('template_redirect', [$this, 'handle_paypal_return']);
    }

    /**
     * AJAX: Captura el pago
     *
     * @return void
     */
    public function ajax_capture_payment(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/mi-portal/grupos-consumo/?error=not_logged_in'));
            exit;
        }

        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'gc_paypal_capture')) {
            wp_safe_redirect(home_url('/mi-portal/grupos-consumo/?error=invalid_nonce'));
            exit;
        }

        $order_id = sanitize_text_field($_GET['order_id'] ?? '');

        if (empty($order_id)) {
            wp_safe_redirect(home_url('/mi-portal/grupos-consumo/?error=missing_order'));
            exit;
        }

        $resultado = $this->capture_payment($order_id);

        if ($resultado['success']) {
            wp_safe_redirect(home_url('/mi-portal/grupos-consumo/mis-pedidos/?payment=success'));
        } else {
            wp_safe_redirect(home_url('/mi-portal/grupos-consumo/checkout/?error=payment_failed'));
        }
        exit;
    }

    /**
     * Maneja el retorno desde PayPal
     *
     * @return void
     */
    public function handle_paypal_return(): void {
        if (empty($_GET['gc_paypal_return'])) {
            return;
        }

        $token = sanitize_text_field($_GET['token'] ?? '');

        if (!empty($token)) {
            $resultado = $this->capture_payment($token);

            if ($resultado['success']) {
                wp_safe_redirect(home_url('/mi-portal/grupos-consumo/mis-pedidos/?payment=success'));
                exit;
            }
        }

        wp_safe_redirect(home_url('/mi-portal/grupos-consumo/checkout/?error=payment_failed'));
        exit;
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
                'label' => __('Habilitar PayPal', 'flavor-platform'),
                'default' => false,
            ],
            [
                'id' => 'sandbox',
                'type' => 'checkbox',
                'label' => __('Modo sandbox (pruebas)', 'flavor-platform'),
                'default' => true,
            ],
            [
                'id' => 'sandbox_client_id',
                'type' => 'text',
                'label' => __('Client ID (Sandbox)', 'flavor-platform'),
            ],
            [
                'id' => 'sandbox_client_secret',
                'type' => 'password',
                'label' => __('Client Secret (Sandbox)', 'flavor-platform'),
            ],
            [
                'id' => 'live_client_id',
                'type' => 'text',
                'label' => __('Client ID (Producción)', 'flavor-platform'),
            ],
            [
                'id' => 'live_client_secret',
                'type' => 'password',
                'label' => __('Client Secret (Producción)', 'flavor-platform'),
            ],
        ];
    }

    /**
     * Procesa un reembolso
     *
     * @param int $transaction_id ID de la transacción
     * @param float $amount Monto a reembolsar
     * @param string $reason Motivo del reembolso
     * @return array
     */
    public function process_refund(int $transaction_id, float $amount, string $reason = ''): array {
        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_pagos} WHERE id = %d AND pasarela = 'paypal'",
            $transaction_id
        ));

        if (!$transaccion) {
            return [
                'success' => false,
                'error' => __('Transacción no encontrada.', 'flavor-platform'),
            ];
        }

        $datos = json_decode($transaccion->datos_pasarela, true);
        $capture_id = $datos['capture_id'] ?? '';

        if (empty($capture_id)) {
            return [
                'success' => false,
                'error' => __('No se encontró el ID de captura de PayPal.', 'flavor-platform'),
            ];
        }

        $access_token = $this->get_access_token();

        if (!$access_token) {
            return [
                'success' => false,
                'error' => __('Error de autenticación con PayPal.', 'flavor-platform'),
            ];
        }

        $response = wp_remote_post($this->api_url . "/v2/payments/captures/{$capture_id}/refund", [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'amount' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => 'EUR',
                ],
                'note_to_payer' => $reason,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] !== 'COMPLETED') {
            return [
                'success' => false,
                'error' => $body['message'] ?? __('Error al procesar el reembolso.', 'flavor-platform'),
            ];
        }

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
