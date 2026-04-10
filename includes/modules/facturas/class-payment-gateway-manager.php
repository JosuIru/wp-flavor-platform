<?php
/**
 * Payment Gateway Manager
 * Gestor centralizado de pasarelas de pago
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Gateway Manager
 *
 * Gestiona el registro y acceso a todas las pasarelas de pago disponibles.
 */
class Flavor_Payment_Gateway_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Payment_Gateway_Manager
     */
    private static $instance = null;

    /**
     * Gateways registrados
     *
     * @var array
     */
    private $gateways = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Payment_Gateway_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_gateways();
    }

    /**
     * Cargar todos los gateways disponibles
     */
    private function load_gateways() {
        $gateways_dir = dirname(__FILE__) . '/gateways/';

        // Cargar clase base
        if (file_exists($gateways_dir . 'class-payment-gateway.php')) {
            require_once $gateways_dir . 'class-payment-gateway.php';
        }

        // Cargar gateways específicos
        $gateway_files = [
            'stripe' => 'class-stripe-gateway.php',
            'paypal' => 'class-paypal-gateway.php',
            'redsys' => 'class-redsys-gateway.php',
        ];

        foreach ($gateway_files as $id => $archivo) {
            $ruta_archivo = $gateways_dir . $archivo;

            if (file_exists($ruta_archivo)) {
                require_once $ruta_archivo;

                // Instanciar gateway
                $class_name = 'Flavor_' . ucfirst($id) . '_Gateway';
                if (class_exists($class_name)) {
                    $this->gateways[$id] = new $class_name();
                }
            }
        }

        // Permitir registrar gateways personalizados
        do_action('flavor_facturas_register_payment_gateways', $this);
    }

    /**
     * Registrar un gateway personalizado
     *
     * @param string                  $id Gateway ID
     * @param Flavor_Payment_Gateway $gateway Instancia del gateway
     */
    public function register_gateway($id, $gateway) {
        if ($gateway instanceof Flavor_Payment_Gateway) {
            $this->gateways[$id] = $gateway;
        }
    }

    /**
     * Obtener todos los gateways
     *
     * @return array
     */
    public function get_all_gateways() {
        return $this->gateways;
    }

    /**
     * Obtener gateways disponibles (habilitados y con credenciales válidas)
     *
     * @return array
     */
    public function get_available_gateways() {
        $available = [];

        foreach ($this->gateways as $id => $gateway) {
            if ($gateway->is_available()) {
                $available[$id] = $gateway;
            }
        }

        return $available;
    }

    /**
     * Obtener un gateway específico
     *
     * @param string $id ID del gateway
     * @return Flavor_Payment_Gateway|null
     */
    public function get_gateway($id) {
        return $this->gateways[$id] ?? null;
    }

    /**
     * Procesar pago con un gateway específico
     *
     * @param string $gateway_id ID del gateway
     * @param array  $payment_data Datos del pago
     * @return array|WP_Error Resultado del pago
     */
    public function process_payment($gateway_id, $payment_data) {
        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway) {
            return new WP_Error('gateway_no_encontrado', __('Gateway de pago no encontrado', 'flavor-platform'));
        }

        if (!$gateway->is_available()) {
            return new WP_Error('gateway_no_disponible', __('Gateway de pago no disponible', 'flavor-platform'));
        }

        return $gateway->process_payment($payment_data);
    }

    /**
     * Obtener formulario de pago para mostrar al usuario
     *
     * @param int   $factura_id ID de la factura
     * @param float $importe Importe a pagar
     * @param array $factura Datos de la factura
     * @return string HTML del formulario
     */
    public function get_payment_form($factura_id, $importe, $factura) {
        $gateways_disponibles = $this->get_available_gateways();

        if (empty($gateways_disponibles)) {
            return '<div class="facturas-mensaje facturas-mensaje-warning">' .
                   esc_html__('No hay métodos de pago online disponibles en este momento.', 'flavor-platform') .
                   '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-payment-gateways">
            <h4><?php esc_html_e('Selecciona método de pago', 'flavor-platform'); ?></h4>

            <div class="payment-gateways-grid">
                <?php foreach ($gateways_disponibles as $id => $gateway) : ?>
                    <div class="payment-gateway-option" data-gateway="<?php echo esc_attr($id); ?>">
                        <input type="radio" name="payment_gateway" id="gateway_<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($id); ?>">
                        <label for="gateway_<?php echo esc_attr($id); ?>">
                            <span class="gateway-name"><?php echo esc_html($gateway->get_name()); ?></span>
                            <span class="gateway-description"><?php echo esc_html($gateway->get_description()); ?></span>
                            <?php if ($gateway->is_test_mode()) : ?>
                                <span class="gateway-test-badge"><?php esc_html_e('Modo Test', 'flavor-platform'); ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="payment-gateway-actions">
                <button type="button" id="btn-pagar-online" class="facturas-btn facturas-btn-primary" disabled>
                    <span class="btn-icon">💳</span>
                    <?php esc_html_e('Pagar Ahora', 'flavor-platform'); ?>
                    <span class="btn-amount"><?php echo esc_html(number_format($importe, 2, ',', '.')); ?> €</span>
                </button>
                <span class="payment-secure-badge">
                    <span class="secure-icon">🔒</span>
                    <?php esc_html_e('Pago seguro', 'flavor-platform'); ?>
                </span>
            </div>

            <div id="payment-loading" class="payment-loading" style="display:none;">
                <div class="loading-spinner"></div>
                <p><?php esc_html_e('Procesando pago...', 'flavor-platform'); ?></p>
            </div>

            <div id="payment-error" class="facturas-mensaje facturas-mensaje-error" style="display:none;"></div>
        </div>

        <script>
        (function($) {
            'use strict';

            $(document).ready(function() {
                // Habilitar botón cuando se seleccione gateway
                $('input[name="payment_gateway"]').on('change', function() {
                    $('#btn-pagar-online').prop('disabled', false);
                });

                // Procesar pago
                $('#btn-pagar-online').on('click', function() {
                    var gateway_id = $('input[name="payment_gateway"]:checked').val();

                    if (!gateway_id) {
                        alert('<?php esc_html_e('Selecciona un método de pago', 'flavor-platform'); ?>');
                        return;
                    }

                    // Mostrar loading
                    $('#payment-loading').show();
                    $('#payment-error').hide();
                    $(this).prop('disabled', true);

                    // Procesar pago via AJAX
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: {
                            action: 'flavor_facturas_process_online_payment',
                            nonce: '<?php echo esc_js(wp_create_nonce('facturas_payment_nonce')); ?>',
                            factura_id: <?php echo absint($factura_id); ?>,
                            gateway_id: gateway_id,
                            importe: <?php echo floatval($importe); ?>
                        },
                        success: function(response) {
                            if (response.success && response.data.redirect_url) {
                                // Redirigir a gateway de pago
                                window.location.href = response.data.redirect_url;
                            } else if (response.data && response.data.form_data) {
                                // Caso Redsys: crear formulario y enviarlo
                                var form = $('<form>', {
                                    method: 'POST',
                                    action: response.data.form_data.action
                                });

                                $.each(response.data.form_data, function(key, value) {
                                    if (key !== 'action') {
                                        form.append($('<input>', {
                                            type: 'hidden',
                                            name: key,
                                            value: value
                                        }));
                                    }
                                });

                                $('body').append(form);
                                form.submit();
                            } else {
                                $('#payment-loading').hide();
                                $('#payment-error').text(response.data || 'Error desconocido').show();
                                $('#btn-pagar-online').prop('disabled', false);
                            }
                        },
                        error: function() {
                            $('#payment-loading').hide();
                            $('#payment-error').text('<?php esc_html_e('Error al procesar el pago', 'flavor-platform'); ?>').show();
                            $('#btn-pagar-online').prop('disabled', false);
                        }
                    });
                });
            });
        })(jQuery);
        </script>

        <style>
        .flavor-payment-gateways {
            margin-top: 24px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .payment-gateways-grid {
            display: grid;
            gap: 12px;
            margin: 16px 0;
        }
        .payment-gateway-option {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-gateway-option:has(input:checked) {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .payment-gateway-option input[type="radio"] {
            display: none;
        }
        .payment-gateway-option label {
            display: block;
            cursor: pointer;
            margin: 0;
        }
        .gateway-name {
            display: block;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .gateway-description {
            display: block;
            font-size: 14px;
            color: #6b7280;
        }
        .gateway-test-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 2px 8px;
            background: #fef3c7;
            color: #92400e;
            font-size: 12px;
            border-radius: 4px;
        }
        .payment-gateway-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        #btn-pagar-online {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
        }
        #btn-pagar-online .btn-amount {
            margin-left: auto;
            font-weight: 700;
        }
        .payment-secure-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #059669;
        }
        .payment-loading {
            text-align: center;
            padding: 24px;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        </style>
        <?php

        return ob_get_clean();
    }
}
