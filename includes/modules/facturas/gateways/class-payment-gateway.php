<?php
/**
 * Clase abstracta base para gateways de pago
 * Define la interfaz común para todas las pasarelas (Stripe, PayPal, Redsys, etc.)
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Payment Gateway
 *
 * Clase base que define la estructura común para todos los gateways de pago.
 * Cada gateway (Stripe, PayPal, Redsys) extenderá esta clase.
 */
abstract class Flavor_Payment_Gateway {

    /**
     * ID único del gateway
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre del gateway
     *
     * @var string
     */
    protected $name;

    /**
     * Descripción del gateway
     *
     * @var string
     */
    protected $description;

    /**
     * Si el gateway está habilitado
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Modo test (true) o producción (false)
     *
     * @var bool
     */
    protected $test_mode = true;

    /**
     * Configuración específica del gateway
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
        $this->load_settings();
    }

    /**
     * Inicializar gateway (implementado por cada gateway específico)
     */
    abstract protected function init();

    /**
     * Procesar pago
     *
     * @param array $payment_data Datos del pago
     * @return array|WP_Error Resultado del pago o error
     */
    abstract public function process_payment($payment_data);

    /**
     * Procesar webhook de confirmación
     *
     * @return array|WP_Error Resultado del webhook
     */
    abstract public function process_webhook();

    /**
     * Obtener URL de retorno tras pago exitoso
     *
     * @param int $factura_id ID de la factura
     * @return string URL de retorno
     */
    abstract public function get_return_url($factura_id);

    /**
     * Obtener URL de cancelación
     *
     * @param int $factura_id ID de la factura
     * @return string URL de cancelación
     */
    abstract public function get_cancel_url($factura_id);

    /**
     * Cargar configuración desde opciones de WordPress
     */
    protected function load_settings() {
        $opciones_modulo = get_option('flavor_chat_modules', []);
        $configuracion_facturas = $opciones_modulo['facturas']['settings'] ?? [];
        $configuracion_gateway = $configuracion_facturas['payment_gateways'][$this->id] ?? [];

        $this->enabled = !empty($configuracion_gateway['enabled']);
        $this->test_mode = !empty($configuracion_gateway['test_mode']);
        $this->settings = $configuracion_gateway;
    }

    /**
     * Verificar si el gateway está disponible
     *
     * @return bool
     */
    public function is_available() {
        return $this->enabled && $this->validate_credentials();
    }

    /**
     * Validar credenciales del gateway
     *
     * @return bool
     */
    abstract protected function validate_credentials();

    /**
     * Obtener ID del gateway
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Obtener nombre del gateway
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Obtener descripción del gateway
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Verificar si está en modo test
     *
     * @return bool
     */
    public function is_test_mode() {
        return $this->test_mode;
    }

    /**
     * Obtener configuración específica
     *
     * @param string $key Clave de configuración
     * @param mixed  $default Valor por defecto
     * @return mixed
     */
    protected function get_setting($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Registrar log del gateway
     *
     * @param string $mensaje Mensaje a registrar
     * @param string $nivel Nivel del log (info, warning, error)
     */
    protected function log($mensaje, $nivel = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_file = WP_CONTENT_DIR . '/flavor-payment-gateways.log';
            $timestamp = current_time('Y-m-d H:i:s');
            $linea_log = "[{$timestamp}] [{$nivel}] [{$this->id}] {$mensaje}\n";
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($log_file, $linea_log, FILE_APPEND);
        }
    }

    /**
     * Formatear cantidad para el gateway
     *
     * @param float $amount Cantidad en euros
     * @return int Cantidad en centavos
     */
    protected function format_amount($amount) {
        return absint($amount * 100);
    }

    /**
     * Obtener moneda configurada
     *
     * @return string Código de moneda (EUR, USD, etc.)
     */
    protected function get_currency() {
        $opciones_modulo = get_option('flavor_chat_modules', []);
        return $opciones_modulo['facturas']['settings']['moneda'] ?? 'EUR';
    }

    /**
     * Crear descripción de pago para el gateway
     *
     * @param array $factura Datos de la factura
     * @return string
     */
    protected function create_payment_description($factura) {
        return sprintf(
            __('Pago de factura %s - %s', 'flavor-platform'),
            $factura['numero_factura'] ?? '',
            $factura['cliente_nombre'] ?? ''
        );
    }

    /**
     * Obtener metadatos del pago
     *
     * @param int   $factura_id ID de la factura
     * @param array $factura Datos de la factura
     * @return array
     */
    protected function get_payment_metadata($factura_id, $factura) {
        return [
            'factura_id' => $factura_id,
            'numero_factura' => $factura['numero_factura'] ?? '',
            'cliente_id' => $factura['cliente_id'] ?? '',
            'cliente_nombre' => $factura['cliente_nombre'] ?? '',
            'plugin' => 'flavor-platform',
            'module' => 'facturas',
        ];
    }
}
