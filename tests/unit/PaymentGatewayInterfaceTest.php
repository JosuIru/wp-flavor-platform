<?php
/**
 * Tests unitarios para la interfaz Payment Gateway
 *
 * Verifica el contrato que deben cumplir todas las pasarelas de pago
 * que se integren con Flavor Platform.
 *
 * @package FlavorPlatform
 * @subpackage Tests
 * @since 1.0.0
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/interfaces/interface-payment-gateway.php';

/**
 * Tests del contrato de la interfaz Flavor_Payment_Gateway_Interface
 */
class PaymentGatewayInterfaceTest extends Flavor_TestCase {

    /**
     * Mock de la pasarela de pago para tests
     *
     * @var Flavor_Payment_Gateway_Interface
     */
    private $mockGateway;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();
        $this->mockGateway = $this->createMockGateway();
    }

    /**
     * Crea un mock que implementa la interfaz completa
     *
     * @return Flavor_Payment_Gateway_Interface
     */
    private function createMockGateway(): Flavor_Payment_Gateway_Interface {
        return new class implements Flavor_Payment_Gateway_Interface {
            private array $settings = [];
            private bool $testModeEnabled = true;
            private bool $gatewayEnabled = true;
            private bool $gatewayConfigured = true;

            public function get_id(): string {
                return 'test_gateway';
            }

            public function get_name(): string {
                return 'Test Payment Gateway';
            }

            public function get_description(): string {
                return 'Gateway de prueba para tests unitarios';
            }

            public function get_icon(): string {
                return 'dashicons-credit-card';
            }

            public function is_enabled(): bool {
                return $this->gatewayEnabled;
            }

            public function is_configured(): bool {
                return $this->gatewayConfigured;
            }

            public function is_available(): bool {
                return $this->is_enabled() && $this->is_configured();
            }

            public function is_test_mode(): bool {
                return $this->testModeEnabled;
            }

            public function get_supported_payment_methods(): array {
                return ['card', 'bank_transfer'];
            }

            public function get_supported_currencies(): array {
                return ['EUR', 'USD', 'GBP'];
            }

            public function process_payment(array $payment_data): array {
                if (empty($payment_data['amount']) || $payment_data['amount'] <= 0) {
                    return [
                        'success'    => false,
                        'error'      => 'Cantidad invalida',
                        'error_code' => 'invalid_amount',
                    ];
                }

                return [
                    'success'        => true,
                    'type'           => 'redirect',
                    'redirect_url'   => 'https://payment.test/checkout/123',
                    'transaction_id' => 'txn_' . uniqid(),
                ];
            }

            public function verify_payment(string $transaction_id): array {
                if (empty($transaction_id)) {
                    return [
                        'status' => 'failed',
                        'error'  => 'ID de transaccion vacio',
                    ];
                }

                return [
                    'status'   => 'completed',
                    'amount'   => 100.00,
                    'currency' => 'EUR',
                    'paid_at'  => date('c'),
                    'metadata' => [],
                    'error'    => null,
                ];
            }

            public function capture_payment(string $transaction_id, ?float $amount = null): array {
                return [
                    'success'         => true,
                    'capture_id'      => 'cap_' . uniqid(),
                    'amount_captured' => $amount ?? 100.00,
                    'error'           => null,
                ];
            }

            public function cancel_payment(string $transaction_id, string $reason = ''): array {
                return [
                    'success' => true,
                    'error'   => null,
                ];
            }

            public function supports_refunds(): bool {
                return true;
            }

            public function process_refund(string $transaction_id, float $amount, string $reason = ''): array {
                return [
                    'success'         => true,
                    'refund_id'       => 'ref_' . uniqid(),
                    'amount_refunded' => $amount,
                    'status'          => 'completed',
                    'error'           => null,
                ];
            }

            public function uses_webhooks(): bool {
                return true;
            }

            public function get_webhook_url(): string {
                return 'https://example.com/wp-json/flavor-payments/v1/webhook/test_gateway';
            }

            public function handle_webhook(array $payload, array $headers = []): array {
                return [
                    'success'        => true,
                    'event_type'     => $payload['event'] ?? 'payment.completed',
                    'transaction_id' => $payload['transaction_id'] ?? '',
                    'action_taken'   => 'payment_confirmed',
                    'error'          => null,
                ];
            }

            public function verify_webhook_signature(string $payload, string $signature, string $secret): bool {
                $expectedSignature = hash_hmac('sha256', $payload, $secret);
                return hash_equals($expectedSignature, $signature);
            }

            public function get_return_url($order_id): string {
                return 'https://example.com/checkout/success/' . $order_id;
            }

            public function get_cancel_url($order_id): string {
                return 'https://example.com/checkout/cancelled/' . $order_id;
            }

            public function get_admin_fields(): array {
                return [
                    [
                        'id'          => 'api_key',
                        'type'        => 'password',
                        'label'       => 'API Key',
                        'description' => 'Clave de API del gateway',
                        'default'     => '',
                        'required'    => true,
                    ],
                    [
                        'id'          => 'test_mode',
                        'type'        => 'checkbox',
                        'label'       => 'Modo de pruebas',
                        'description' => 'Activar modo sandbox',
                        'default'     => true,
                        'required'    => false,
                    ],
                ];
            }

            public function get_settings(): array {
                return $this->settings;
            }

            public function save_settings(array $settings): bool {
                $this->settings = $settings;
                return true;
            }

            public function validate_credentials(): array {
                return [
                    'valid'   => true,
                    'message' => 'Credenciales validas',
                    'details' => ['account_id' => 'test_account'],
                ];
            }

            public function render_checkout_fields(array $context = []): void {
                echo '<div class="test-gateway-fields">';
                echo '<input type="hidden" name="gateway" value="test_gateway" />';
                echo '</div>';
            }

            public function enqueue_checkout_assets(): void {
                // En tests no encolamos assets reales
            }

            public function get_frontend_data(): array {
                return [
                    'publishable_key' => 'pk_test_123',
                    'locale'          => 'es_ES',
                    'currency'        => 'EUR',
                ];
            }

            public function log(string $message, string $level = 'info', array $context = []): void {
                // Mock de logging - no hace nada en tests
            }

            public function get_transaction_history(int $limit = 50): array {
                return [
                    [
                        'id'       => 'txn_001',
                        'amount'   => 50.00,
                        'currency' => 'EUR',
                        'status'   => 'completed',
                        'date'     => '2024-01-15 10:30:00',
                    ],
                ];
            }

            // Metodos auxiliares para tests
            public function setTestMode(bool $enabled): void {
                $this->testModeEnabled = $enabled;
            }

            public function setEnabled(bool $enabled): void {
                $this->gatewayEnabled = $enabled;
            }

            public function setConfigured(bool $configured): void {
                $this->gatewayConfigured = $configured;
            }
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Existencia de Metodos
    |--------------------------------------------------------------------------
    */

    /**
     * Test que verifica que la interfaz define todos los metodos requeridos
     */
    public function test_interface_defines_all_required_methods(): void {
        $metodosRequeridos = [
            // Identificacion
            'get_id',
            'get_name',
            'get_description',
            'get_icon',
            // Estado
            'is_enabled',
            'is_configured',
            'is_available',
            'is_test_mode',
            'get_supported_payment_methods',
            'get_supported_currencies',
            // Procesamiento
            'process_payment',
            'verify_payment',
            'capture_payment',
            'cancel_payment',
            // Reembolsos
            'supports_refunds',
            'process_refund',
            // Webhooks
            'uses_webhooks',
            'get_webhook_url',
            'handle_webhook',
            'verify_webhook_signature',
            // URLs
            'get_return_url',
            'get_cancel_url',
            // Configuracion
            'get_admin_fields',
            'get_settings',
            'save_settings',
            'validate_credentials',
            // Frontend
            'render_checkout_fields',
            'enqueue_checkout_assets',
            'get_frontend_data',
            // Logging
            'log',
            'get_transaction_history',
        ];

        $reflectionInterface = new ReflectionClass(Flavor_Payment_Gateway_Interface::class);
        $metodosDefinidos = array_map(
            fn($method) => $method->getName(),
            $reflectionInterface->getMethods()
        );

        foreach ($metodosRequeridos as $metodo) {
            $this->assertContains(
                $metodo,
                $metodosDefinidos,
                "La interfaz debe definir el metodo: {$metodo}"
            );
        }
    }

    /**
     * Test que verifica que el mock implementa la interfaz
     */
    public function test_mock_implements_interface(): void {
        $this->assertInstanceOf(
            Flavor_Payment_Gateway_Interface::class,
            $this->mockGateway,
            'El mock debe implementar Flavor_Payment_Gateway_Interface'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Tipos de Retorno
    |--------------------------------------------------------------------------
    */

    /**
     * Test de tipo de retorno: get_id() debe devolver string
     */
    public function test_get_id_returns_string(): void {
        $identificador = $this->mockGateway->get_id();

        $this->assertIsString($identificador);
        $this->assertNotEmpty($identificador);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $identificador, 'ID debe ser un slug valido');
    }

    /**
     * Test de tipo de retorno: get_name() debe devolver string
     */
    public function test_get_name_returns_string(): void {
        $nombre = $this->mockGateway->get_name();

        $this->assertIsString($nombre);
        $this->assertNotEmpty($nombre);
    }

    /**
     * Test de tipo de retorno: get_description() debe devolver string
     */
    public function test_get_description_returns_string(): void {
        $descripcion = $this->mockGateway->get_description();

        $this->assertIsString($descripcion);
    }

    /**
     * Test de tipo de retorno: get_icon() debe devolver string
     */
    public function test_get_icon_returns_string(): void {
        $icono = $this->mockGateway->get_icon();

        $this->assertIsString($icono);
    }

    /**
     * Test de tipo de retorno: is_enabled() debe devolver bool
     */
    public function test_is_enabled_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->is_enabled());
    }

    /**
     * Test de tipo de retorno: is_configured() debe devolver bool
     */
    public function test_is_configured_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->is_configured());
    }

    /**
     * Test de tipo de retorno: is_available() debe devolver bool
     */
    public function test_is_available_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->is_available());
    }

    /**
     * Test de tipo de retorno: is_test_mode() debe devolver bool
     */
    public function test_is_test_mode_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->is_test_mode());
    }

    /**
     * Test de tipo de retorno: get_supported_payment_methods() debe devolver array
     */
    public function test_get_supported_payment_methods_returns_array(): void {
        $metodos = $this->mockGateway->get_supported_payment_methods();

        $this->assertIsArray($metodos);
        $this->assertNotEmpty($metodos);

        // Verificar que son strings validos
        foreach ($metodos as $metodo) {
            $this->assertIsString($metodo);
        }
    }

    /**
     * Test de tipo de retorno: get_supported_currencies() debe devolver array
     */
    public function test_get_supported_currencies_returns_array(): void {
        $monedas = $this->mockGateway->get_supported_currencies();

        $this->assertIsArray($monedas);
        $this->assertNotEmpty($monedas);

        // Verificar formato ISO 4217 (3 letras mayusculas)
        foreach ($monedas as $moneda) {
            $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $moneda, 'Moneda debe ser codigo ISO 4217');
        }
    }

    /**
     * Test de tipo de retorno: process_payment() debe devolver array con estructura correcta
     */
    public function test_process_payment_returns_array_with_correct_structure(): void {
        $datosPago = [
            'amount'         => 100.00,
            'currency'       => 'EUR',
            'order_id'       => 123,
            'description'    => 'Test payment',
            'customer_email' => 'test@example.com',
            'customer_name'  => 'Test User',
            'metadata'       => [],
            'return_url'     => 'https://example.com/success',
            'cancel_url'     => 'https://example.com/cancel',
        ];

        $resultado = $this->mockGateway->process_payment($datosPago);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
        $this->assertIsBool($resultado['success']);

        if ($resultado['success']) {
            $this->assertArrayHasKey('type', $resultado);
            $this->assertContains($resultado['type'], ['redirect', 'form', 'direct', 'iframe']);
        }
    }

    /**
     * Test de tipo de retorno: verify_payment() debe devolver array con estructura correcta
     */
    public function test_verify_payment_returns_array_with_correct_structure(): void {
        $resultado = $this->mockGateway->verify_payment('txn_123');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertContains(
            $resultado['status'],
            ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']
        );
    }

    /**
     * Test de tipo de retorno: capture_payment() debe devolver array
     */
    public function test_capture_payment_returns_array(): void {
        $resultado = $this->mockGateway->capture_payment('txn_123', 50.00);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
    }

    /**
     * Test de tipo de retorno: cancel_payment() debe devolver array
     */
    public function test_cancel_payment_returns_array(): void {
        $resultado = $this->mockGateway->cancel_payment('txn_123', 'Cancelado por usuario');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
    }

    /**
     * Test de tipo de retorno: supports_refunds() debe devolver bool
     */
    public function test_supports_refunds_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->supports_refunds());
    }

    /**
     * Test de tipo de retorno: process_refund() debe devolver array
     */
    public function test_process_refund_returns_array(): void {
        $resultado = $this->mockGateway->process_refund('txn_123', 25.00, 'Producto defectuoso');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);

        if ($resultado['success']) {
            $this->assertArrayHasKey('refund_id', $resultado);
            $this->assertArrayHasKey('amount_refunded', $resultado);
        }
    }

    /**
     * Test de tipo de retorno: uses_webhooks() debe devolver bool
     */
    public function test_uses_webhooks_returns_bool(): void {
        $this->assertIsBool($this->mockGateway->uses_webhooks());
    }

    /**
     * Test de tipo de retorno: get_webhook_url() debe devolver string URL valida
     */
    public function test_get_webhook_url_returns_valid_url(): void {
        $webhookUrl = $this->mockGateway->get_webhook_url();

        $this->assertIsString($webhookUrl);
        $this->assertNotEmpty($webhookUrl);
        $this->assertStringStartsWith('https://', $webhookUrl);
    }

    /**
     * Test de tipo de retorno: handle_webhook() debe devolver array
     */
    public function test_handle_webhook_returns_array(): void {
        $payload = [
            'event'          => 'payment.completed',
            'transaction_id' => 'txn_123',
        ];
        $headers = ['X-Signature' => 'test_signature'];

        $resultado = $this->mockGateway->handle_webhook($payload, $headers);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
    }

    /**
     * Test de tipo de retorno: verify_webhook_signature() debe devolver bool
     */
    public function test_verify_webhook_signature_returns_bool(): void {
        $payload = '{"event":"test"}';
        $secret = 'webhook_secret_key';
        $signature = hash_hmac('sha256', $payload, $secret);

        $esValida = $this->mockGateway->verify_webhook_signature($payload, $signature, $secret);

        $this->assertIsBool($esValida);
    }

    /**
     * Test de tipo de retorno: get_return_url() debe devolver string URL
     */
    public function test_get_return_url_returns_string(): void {
        $url = $this->mockGateway->get_return_url(123);

        $this->assertIsString($url);
        $this->assertStringContainsString('123', $url);
    }

    /**
     * Test de tipo de retorno: get_cancel_url() debe devolver string URL
     */
    public function test_get_cancel_url_returns_string(): void {
        $url = $this->mockGateway->get_cancel_url(456);

        $this->assertIsString($url);
        $this->assertStringContainsString('456', $url);
    }

    /**
     * Test de tipo de retorno: get_admin_fields() debe devolver array de campos
     */
    public function test_get_admin_fields_returns_array_of_field_definitions(): void {
        $campos = $this->mockGateway->get_admin_fields();

        $this->assertIsArray($campos);

        foreach ($campos as $campo) {
            $this->assertIsArray($campo);
            $this->assertArrayHasKey('id', $campo);
            $this->assertArrayHasKey('type', $campo);
            $this->assertArrayHasKey('label', $campo);
            $this->assertContains($campo['type'], ['text', 'password', 'checkbox', 'select', 'textarea']);
        }
    }

    /**
     * Test de tipo de retorno: get_settings() debe devolver array
     */
    public function test_get_settings_returns_array(): void {
        $this->assertIsArray($this->mockGateway->get_settings());
    }

    /**
     * Test de tipo de retorno: save_settings() debe devolver bool
     */
    public function test_save_settings_returns_bool(): void {
        $resultado = $this->mockGateway->save_settings(['api_key' => 'test_key']);

        $this->assertIsBool($resultado);
    }

    /**
     * Test de tipo de retorno: validate_credentials() debe devolver array
     */
    public function test_validate_credentials_returns_array(): void {
        $resultado = $this->mockGateway->validate_credentials();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('valid', $resultado);
        $this->assertIsBool($resultado['valid']);
        $this->assertArrayHasKey('message', $resultado);
    }

    /**
     * Test de tipo de retorno: get_frontend_data() debe devolver array
     */
    public function test_get_frontend_data_returns_array(): void {
        $datos = $this->mockGateway->get_frontend_data();

        $this->assertIsArray($datos);
    }

    /**
     * Test de tipo de retorno: get_transaction_history() debe devolver array
     */
    public function test_get_transaction_history_returns_array(): void {
        $historial = $this->mockGateway->get_transaction_history(10);

        $this->assertIsArray($historial);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Flujo Basico
    |--------------------------------------------------------------------------
    */

    /**
     * Test de flujo basico: is_available -> process_payment -> verify_payment
     */
    public function test_basic_payment_flow(): void {
        // Paso 1: Verificar disponibilidad
        $this->assertTrue(
            $this->mockGateway->is_available(),
            'Gateway debe estar disponible'
        );

        // Paso 2: Procesar pago
        $datosPago = [
            'amount'         => 99.99,
            'currency'       => 'EUR',
            'order_id'       => 'order_001',
            'description'    => 'Compra de prueba',
            'customer_email' => 'cliente@example.com',
            'customer_name'  => 'Cliente Test',
            'return_url'     => 'https://example.com/gracias',
            'cancel_url'     => 'https://example.com/cancelado',
        ];

        $resultadoPago = $this->mockGateway->process_payment($datosPago);

        $this->assertTrue($resultadoPago['success'], 'El pago debe procesarse exitosamente');
        $this->assertArrayHasKey('transaction_id', $resultadoPago);

        $transactionId = $resultadoPago['transaction_id'];

        // Paso 3: Verificar estado del pago
        $estadoPago = $this->mockGateway->verify_payment($transactionId);

        $this->assertArrayHasKey('status', $estadoPago);
        $this->assertEquals('completed', $estadoPago['status']);
    }

    /**
     * Test de flujo de error: pago con cantidad invalida
     */
    public function test_payment_flow_with_invalid_amount(): void {
        $datosPagoInvalidos = [
            'amount'   => 0,
            'currency' => 'EUR',
            'order_id' => 'order_002',
        ];

        $resultado = $this->mockGateway->process_payment($datosPagoInvalidos);

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertArrayHasKey('error_code', $resultado);
    }

    /**
     * Test de flujo con reembolso
     */
    public function test_payment_flow_with_refund(): void {
        // Verificar que soporta reembolsos
        $this->assertTrue($this->mockGateway->supports_refunds());

        // Procesar pago inicial
        $datosPago = [
            'amount'   => 150.00,
            'currency' => 'EUR',
            'order_id' => 'order_003',
        ];

        $resultadoPago = $this->mockGateway->process_payment($datosPago);
        $this->assertTrue($resultadoPago['success']);

        // Procesar reembolso parcial
        $resultadoReembolso = $this->mockGateway->process_refund(
            $resultadoPago['transaction_id'],
            50.00,
            'Devolucion parcial'
        );

        $this->assertTrue($resultadoReembolso['success']);
        $this->assertEquals(50.00, $resultadoReembolso['amount_refunded']);
        $this->assertArrayHasKey('refund_id', $resultadoReembolso);
    }

    /**
     * Test de flujo con webhook
     */
    public function test_webhook_flow(): void {
        // Verificar que usa webhooks
        $this->assertTrue($this->mockGateway->uses_webhooks());

        // Obtener URL del webhook
        $webhookUrl = $this->mockGateway->get_webhook_url();
        $this->assertStringContainsString('webhook', $webhookUrl);

        // Simular payload de webhook
        $payload = '{"event":"payment.completed","transaction_id":"txn_webhook_001"}';
        $secret = 'webhook_secret';
        $signature = hash_hmac('sha256', $payload, $secret);

        // Verificar firma
        $firmaValida = $this->mockGateway->verify_webhook_signature($payload, $signature, $secret);
        $this->assertTrue($firmaValida);

        // Procesar webhook
        $resultado = $this->mockGateway->handle_webhook(
            json_decode($payload, true),
            ['X-Signature' => $signature]
        );

        $this->assertTrue($resultado['success']);
        $this->assertEquals('payment.completed', $resultado['event_type']);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Configuracion
    |--------------------------------------------------------------------------
    */

    /**
     * Test de guardado y recuperacion de configuracion
     */
    public function test_settings_save_and_retrieve(): void {
        $nuevaConfiguracion = [
            'api_key'   => 'sk_test_abc123',
            'test_mode' => true,
            'currency'  => 'EUR',
        ];

        // Guardar configuracion
        $guardado = $this->mockGateway->save_settings($nuevaConfiguracion);
        $this->assertTrue($guardado);

        // Recuperar configuracion
        $configuracionGuardada = $this->mockGateway->get_settings();
        $this->assertEquals('sk_test_abc123', $configuracionGuardada['api_key']);
    }

    /**
     * Test de validacion de credenciales
     */
    public function test_credentials_validation(): void {
        $resultado = $this->mockGateway->validate_credentials();

        $this->assertArrayHasKey('valid', $resultado);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertArrayHasKey('details', $resultado);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Disponibilidad
    |--------------------------------------------------------------------------
    */

    /**
     * Test que is_available depende de is_enabled e is_configured
     */
    public function test_availability_depends_on_enabled_and_configured(): void {
        // Caso 1: Habilitado y configurado = disponible
        $this->mockGateway->setEnabled(true);
        $this->mockGateway->setConfigured(true);
        $this->assertTrue($this->mockGateway->is_available());

        // Caso 2: No habilitado = no disponible
        $this->mockGateway->setEnabled(false);
        $this->mockGateway->setConfigured(true);
        $this->assertFalse($this->mockGateway->is_available());

        // Caso 3: No configurado = no disponible
        $this->mockGateway->setEnabled(true);
        $this->mockGateway->setConfigured(false);
        $this->assertFalse($this->mockGateway->is_available());

        // Caso 4: Ni habilitado ni configurado = no disponible
        $this->mockGateway->setEnabled(false);
        $this->mockGateway->setConfigured(false);
        $this->assertFalse($this->mockGateway->is_available());
    }

    /**
     * Test de modo de pruebas
     */
    public function test_test_mode_toggle(): void {
        // Por defecto en modo test
        $this->assertTrue($this->mockGateway->is_test_mode());

        // Cambiar a produccion
        $this->mockGateway->setTestMode(false);
        $this->assertFalse($this->mockGateway->is_test_mode());

        // Volver a test
        $this->mockGateway->setTestMode(true);
        $this->assertTrue($this->mockGateway->is_test_mode());
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Frontend
    |--------------------------------------------------------------------------
    */

    /**
     * Test de renderizado de campos de checkout
     */
    public function test_render_checkout_fields_outputs_html(): void {
        ob_start();
        $this->mockGateway->render_checkout_fields(['amount' => 100.00]);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('test-gateway-fields', $output);
    }

    /**
     * Test de datos para frontend
     */
    public function test_frontend_data_contains_required_keys(): void {
        $datos = $this->mockGateway->get_frontend_data();

        $this->assertArrayHasKey('publishable_key', $datos);
        $this->assertArrayHasKey('locale', $datos);
        $this->assertArrayHasKey('currency', $datos);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de URLs
    |--------------------------------------------------------------------------
    */

    /**
     * Test de URLs de retorno con diferentes tipos de order_id
     */
    public function test_return_urls_with_different_order_id_types(): void {
        // Con entero
        $urlEntero = $this->mockGateway->get_return_url(123);
        $this->assertStringContainsString('123', $urlEntero);

        // Con string
        $urlString = $this->mockGateway->get_return_url('order_abc');
        $this->assertStringContainsString('order_abc', $urlString);
    }

    /**
     * Test de URLs de cancelacion
     */
    public function test_cancel_urls_are_different_from_return_urls(): void {
        $orderId = 999;

        $returnUrl = $this->mockGateway->get_return_url($orderId);
        $cancelUrl = $this->mockGateway->get_cancel_url($orderId);

        $this->assertNotEquals($returnUrl, $cancelUrl);
        $this->assertStringContainsString('success', $returnUrl);
        $this->assertStringContainsString('cancelled', $cancelUrl);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Historial
    |--------------------------------------------------------------------------
    */

    /**
     * Test de historial de transacciones
     */
    public function test_transaction_history_returns_expected_format(): void {
        $historial = $this->mockGateway->get_transaction_history(5);

        $this->assertIsArray($historial);

        if (!empty($historial)) {
            $transaccion = $historial[0];
            $this->assertArrayHasKey('id', $transaccion);
            $this->assertArrayHasKey('amount', $transaccion);
            $this->assertArrayHasKey('status', $transaccion);
        }
    }

    /**
     * Test que el limite funciona
     */
    public function test_transaction_history_respects_limit(): void {
        $historial = $this->mockGateway->get_transaction_history(1);

        $this->assertLessThanOrEqual(1, count($historial));
    }
}
