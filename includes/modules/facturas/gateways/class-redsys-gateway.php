<?php
/**
 * Gateway de pago Redsys
 * Implementación de Redsys/Servired para pagos con tarjeta en España
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/class-payment-gateway.php';

/**
 * Redsys Payment Gateway
 *
 * Integración con Redsys (TPV Virtual) para bancos españoles.
 * Compatible con: BBVA, Santander, Caixabank, Sabadell, etc.
 *
 * Documentación: https://pagosonline.redsys.es/
 */
class Flavor_Redsys_Gateway extends Flavor_Payment_Gateway {

    /**
     * Número de comercio
     *
     * @var string
     */
    private $merchant_code;

    /**
     * Terminal
     *
     * @var string
     */
    private $terminal;

    /**
     * Clave secreta para firmar
     *
     * @var string
     */
    private $secret_key;

    /**
     * URL del TPV
     *
     * @var string
     */
    private $tpv_url;

    /**
     * Inicializar gateway
     */
    protected function init() {
        $this->id = 'redsys';
        $this->name = __('Redsys / TPV Bancario', 'flavor-platform');
        $this->description = __('Pago con tarjeta a través de tu banco (Redsys)', 'flavor-platform');

        $this->load_credentials();

        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    /**
     * Cargar credenciales
     */
    private function load_credentials() {
        $this->merchant_code = $this->get_setting('merchant_code', '');
        $this->terminal = $this->get_setting('terminal', '1');
        $this->secret_key = $this->get_setting('secret_key', '');

        // URLs según entorno
        if ($this->test_mode) {
            $this->tpv_url = 'https://sis-t.redsys.es:25443/sis/realizarPago';
        } else {
            $this->tpv_url = 'https://sis.redsys.es/sis/realizarPago';
        }
    }

    /**
     * Validar credenciales
     */
    protected function validate_credentials() {
        return !empty($this->merchant_code) && !empty($this->secret_key);
    }

    /**
     * Procesar pago
     */
    public function process_payment($payment_data) {
        if (!$this->is_available()) {
            return new WP_Error('gateway_no_disponible', __('Redsys no está disponible', 'flavor-platform'));
        }

        $factura_id = absint($payment_data['factura_id']);
        $importe = floatval($payment_data['importe']);
        $factura = $payment_data['factura'];

        // Generar número de pedido único
        $order_id = 'FAC' . str_pad($factura_id, 8, '0', STR_PAD_LEFT);

        // Preparar parámetros
        $params = [
            'DS_MERCHANT_AMOUNT' => $this->format_amount($importe), // En centavos
            'DS_MERCHANT_ORDER' => $order_id,
            'DS_MERCHANT_MERCHANTCODE' => $this->merchant_code,
            'DS_MERCHANT_CURRENCY' => $this->get_currency_code(),
            'DS_MERCHANT_TRANSACTIONTYPE' => '0', // Autorización
            'DS_MERCHANT_TERMINAL' => $this->terminal,
            'DS_MERCHANT_MERCHANTURL' => $this->get_webhook_url(),
            'DS_MERCHANT_URLOK' => $this->get_return_url($factura_id),
            'DS_MERCHANT_URLKO' => $this->get_cancel_url($factura_id),
            'DS_MERCHANT_PRODUCTDESCRIPTION' => $this->create_payment_description($factura),
        ];

        // Codificar parámetros en base64
        $merchant_parameters = base64_encode(wp_json_encode($params));

        // Generar firma
        $signature = $this->generate_signature($order_id, $merchant_parameters);

        // Guardar order_id
        update_post_meta($factura_id, '_redsys_order_id', $order_id);
        update_post_meta($factura_id, '_redsys_pending_amount', $importe);

        return [
            'success' => true,
            'form_data' => [
                'action' => $this->tpv_url,
                'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
                'Ds_MerchantParameters' => $merchant_parameters,
                'Ds_Signature' => $signature,
            ],
            'order_id' => $order_id,
        ];
    }

    /**
     * Generar firma HMAC-SHA256
     *
     * @param string $order_id Número de pedido
     * @param string $merchant_parameters Parámetros codificados
     * @return string
     */
    private function generate_signature($order_id, $merchant_parameters) {
        // Cifrar clave con 3DES
        $key = base64_decode($this->secret_key);
        $iv = "\0\0\0\0\0\0\0\0";
        $cipher_key = openssl_encrypt($order_id, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Generar HMAC-SHA256
        $signature = hash_hmac('sha256', $merchant_parameters, $cipher_key, true);

        return base64_encode($signature);
    }

    /**
     * Verificar firma de notificación
     *
     * @param string $merchant_parameters Parámetros recibidos
     * @param string $signature Firma recibida
     * @return bool
     */
    private function verify_signature($merchant_parameters, $signature) {
        $params = json_decode(base64_decode($merchant_parameters), true);
        $order_id = $params['Ds_Order'] ?? '';

        $calculated_signature = $this->generate_signature($order_id, $merchant_parameters);

        return hash_equals($calculated_signature, $signature);
    }

    /**
     * Obtener código de moneda Redsys
     *
     * @return string
     */
    private function get_currency_code() {
        $currency = $this->get_currency();

        $codes = [
            'EUR' => '978',
            'USD' => '840',
            'GBP' => '826',
            'JPY' => '392',
        ];

        return $codes[$currency] ?? '978'; // EUR por defecto
    }

    /**
     * Procesar webhook (notificación de Redsys)
     */
    public function process_webhook() {
        $merchant_parameters = $_POST['Ds_MerchantParameters'] ?? '';
        $signature = $_POST['Ds_Signature'] ?? '';

        if (empty($merchant_parameters) || empty($signature)) {
            $this->log('Webhook Redsys sin parámetros', 'warning');
            return new WP_Error('webhook_invalido', 'Parámetros faltantes');
        }

        // Verificar firma
        if (!$this->verify_signature($merchant_parameters, $signature)) {
            $this->log('Firma Redsys inválida', 'error');
            return new WP_Error('firma_invalida', 'Firma no válida');
        }

        // Decodificar parámetros
        $params = json_decode(base64_decode($merchant_parameters), true);

        $order_id = $params['Ds_Order'] ?? '';
        $response_code = $params['Ds_Response'] ?? '';
        $importe = floatval($params['Ds_Amount'] ?? 0) / 100; // Convertir de centavos
        $auth_code = $params['Ds_AuthorisationCode'] ?? '';

        $this->log("Webhook Redsys - Order: {$order_id}, Response: {$response_code}", 'info');

        // Verificar si el pago fue exitoso (códigos 0000-0099 son exitosos)
        $response_code_int = intval($response_code);
        if ($response_code_int < 0 || $response_code_int > 99) {
            $this->log("Pago Redsys rechazado: código {$response_code}", 'warning');
            return ['success' => false, 'message' => 'Pago rechazado'];
        }

        // Buscar factura por order_id
        global $wpdb;
        $factura_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_redsys_order_id' AND meta_value = %s",
            $order_id
        ));

        if (!$factura_id) {
            $this->log("Order {$order_id} no encontrada", 'warning');
            return ['success' => false, 'message' => 'Order no encontrada'];
        }

        // Registrar pago
        $modulo_facturas = $this->get_facturas_module();
        if (!$modulo_facturas) {
            return new WP_Error('modulo_no_encontrado', 'Módulo facturas no disponible');
        }

        $resultado_pago = $modulo_facturas->registrar_pago([
            'factura_id' => $factura_id,
            'importe' => $importe,
            'fecha_pago' => current_time('Y-m-d'),
            'metodo_pago' => 'redsys',
            'referencia' => $auth_code,
            'notas' => sprintf(__('Pago procesado via Redsys. Order: %s, Auth: %s', 'flavor-platform'), $order_id, $auth_code),
        ]);

        if (is_wp_error($resultado_pago)) {
            $this->log('Error al registrar pago: ' . $resultado_pago->get_error_message(), 'error');
            return ['success' => false, 'message' => $resultado_pago->get_error_message()];
        }

        $this->log("Pago Redsys registrado para factura #{$factura_id}", 'info');

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
     * Obtener URL del webhook
     *
     * @return string
     */
    private function get_webhook_url() {
        return rest_url('flavor/v1/facturas/redsys-webhook');
    }

    /**
     * Registrar endpoint webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route('flavor/v1', '/facturas/redsys-webhook', [
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
            'gateway' => 'redsys',
        ], home_url('/facturas/pago-exitoso/'));
    }

    /**
     * URL de cancelación
     */
    public function get_cancel_url($factura_id) {
        return add_query_arg([
            'factura_id' => $factura_id,
            'gateway' => 'redsys',
        ], home_url('/facturas/pago-cancelado/'));
    }
}
