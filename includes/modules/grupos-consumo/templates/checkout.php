<?php
/**
 * Template: Checkout de Grupos de Consumo
 *
 * Página de pago para entregas de grupos de consumo.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.1.0
 *
 * @var int $entrega_id ID de la entrega
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar autenticación
if (!is_user_logged_in()) {
    echo '<div class="gc-checkout-error">';
    echo '<p>' . esc_html__('Debes iniciar sesión para continuar.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'checkout'))) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesión', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

// Obtener ID de entrega
$entrega_id = isset($entrega_id) ? absint($entrega_id) : absint($_GET['entrega_id'] ?? 0);

if (!$entrega_id) {
    echo '<div class="gc-checkout-error">';
    echo '<p>' . esc_html__('No se especificó una entrega válida.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido')) . '" class="gc-btn gc-btn-secondary">';
    echo esc_html__('Ir a mi cesta', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

// Obtener manager de pagos
$payment_manager = Flavor_GC_Payment_Manager::get_instance();
$checkout_data = $payment_manager->get_checkout_summary($entrega_id);

if (!$checkout_data) {
    echo '<div class="gc-checkout-error">';
    echo '<p>' . esc_html__('La entrega no existe o no tienes permisos para acceder.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Verificar que pertenece al usuario
global $wpdb;
$tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
$entrega = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tabla_entregas} WHERE id = %d",
    $entrega_id
));

if (!$entrega || (int) $entrega->usuario_id !== get_current_user_id()) {
    echo '<div class="gc-checkout-error">';
    echo '<p>' . esc_html__('No tienes permisos para acceder a esta entrega.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Si ya está pagada
if ($checkout_data['estado_pago'] === 'completado') {
    echo '<div class="gc-checkout-success">';
    echo '<span class="dashicons dashicons-yes-alt"></span>';
    echo '<h2>' . esc_html__('Pedido ya pagado', 'flavor-chat-ia') . '</h2>';
    echo '<p>' . esc_html__('Este pedido ya ha sido pagado correctamente.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos')) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Ver mis pedidos', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

$pasarelas = $checkout_data['pasarelas'];
$selected_gateway = $pasarelas[0]['id'] ?? '';
?>

<div class="gc-checkout-container">
    <div class="gc-checkout-main">
        <h2 class="gc-checkout-title"><?php esc_html_e('Finalizar pedido', 'flavor-chat-ia'); ?></h2>

        <!-- Resumen del pedido -->
        <div class="gc-checkout-summary">
            <h3><?php esc_html_e('Resumen del pedido', 'flavor-chat-ia'); ?></h3>

            <div class="gc-checkout-ciclo">
                <strong><?php esc_html_e('Ciclo:', 'flavor-chat-ia'); ?></strong>
                <?php echo esc_html($checkout_data['ciclo']); ?>
            </div>

            <table class="gc-checkout-items">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Producto', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Subtotal', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checkout_data['items'] as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['nombre']); ?></td>
                        <td><?php echo esc_html($item['cantidad']); ?></td>
                        <td><?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?> €</td>
                        <td><?php echo number_format($item['subtotal'], 2, ',', '.'); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="gc-checkout-subtotal">
                        <td colspan="3"><?php esc_html_e('Subtotal', 'flavor-chat-ia'); ?></td>
                        <td><?php echo number_format($checkout_data['subtotal'], 2, ',', '.'); ?> €</td>
                    </tr>
                    <?php if ($checkout_data['descuento'] > 0) : ?>
                    <tr class="gc-checkout-discount">
                        <td colspan="3"><?php esc_html_e('Descuento', 'flavor-chat-ia'); ?></td>
                        <td>-<?php echo number_format($checkout_data['descuento'], 2, ',', '.'); ?> €</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="gc-checkout-total">
                        <td colspan="3"><?php esc_html_e('Total a pagar', 'flavor-chat-ia'); ?></td>
                        <td><strong><?php echo number_format($checkout_data['total'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Métodos de pago -->
        <div class="gc-checkout-payment">
            <h3><?php esc_html_e('Método de pago', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($pasarelas)) : ?>
            <div class="gc-checkout-no-gateways">
                <p><?php esc_html_e('No hay métodos de pago disponibles. Contacta con el administrador.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php else : ?>

            <form id="gc-checkout-form" method="post" class="gc-payment-form">
                <?php wp_nonce_field('gc_checkout_nonce', 'gc_checkout_nonce'); ?>
                <input type="hidden" name="entrega_id" value="<?php echo esc_attr($entrega_id); ?>">

                <div class="gc-payment-methods">
                    <?php foreach ($pasarelas as $index => $gateway) : ?>
                    <div class="gc-payment-method">
                        <label class="gc-payment-option">
                            <input type="radio"
                                   name="gateway_id"
                                   value="<?php echo esc_attr($gateway['id']); ?>"
                                   <?php checked($index, 0); ?>
                                   data-gateway="<?php echo esc_attr($gateway['id']); ?>">
                            <span class="gc-payment-label">
                                <span class="dashicons <?php echo esc_attr($gateway['icon']); ?>"></span>
                                <span class="gc-payment-name"><?php echo esc_html($gateway['name']); ?></span>
                            </span>
                            <span class="gc-payment-desc"><?php echo esc_html($gateway['description']); ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Área dinámica para campos de pasarela -->
                <div id="gc-gateway-fields" class="gc-gateway-fields">
                    <!-- Los campos se cargan dinámicamente según la pasarela seleccionada -->
                </div>

                <div class="gc-checkout-actions">
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido')); ?>" class="gc-btn gc-btn-secondary">
                        <?php esc_html_e('Volver a la cesta', 'flavor-chat-ia'); ?>
                    </a>
                    <button type="submit" class="gc-btn gc-btn-primary gc-btn-pay" id="gc-btn-pay">
                        <span class="gc-btn-text"><?php esc_html_e('Pagar', 'flavor-chat-ia'); ?> <?php echo number_format($checkout_data['total'], 2, ',', '.'); ?> €</span>
                        <span class="gc-btn-loading" style="display: none;">
                            <span class="gc-spinner"></span>
                            <?php esc_html_e('Procesando...', 'flavor-chat-ia'); ?>
                        </span>
                    </button>
                </div>
            </form>

            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar con información adicional -->
    <aside class="gc-checkout-sidebar">
        <div class="gc-checkout-secure">
            <span class="dashicons dashicons-lock"></span>
            <span><?php esc_html_e('Pago seguro', 'flavor-chat-ia'); ?></span>
        </div>

        <div class="gc-checkout-help">
            <h4><?php esc_html_e('¿Necesitas ayuda?', 'flavor-chat-ia'); ?></h4>
            <p><?php esc_html_e('Si tienes dudas sobre el proceso de pago, contacta con nosotros.', 'flavor-chat-ia'); ?></p>
        </div>
    </aside>
</div>

<script>
(function($) {
    'use strict';

    var $form = $('#gc-checkout-form');
    var $gatewayFields = $('#gc-gateway-fields');
    var $btnPay = $('#gc-btn-pay');
    var currentGateway = '';

    // Cargar campos de la pasarela seleccionada
    function loadGatewayFields(gatewayId) {
        if (gatewayId === currentGateway) return;

        currentGateway = gatewayId;
        $gatewayFields.html('<div class="gc-loading"><span class="gc-spinner"></span></div>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'gc_get_checkout_form',
                nonce: '<?php echo wp_create_nonce('gc_checkout_nonce'); ?>',
                gateway_id: gatewayId
            },
            success: function(response) {
                if (response.success) {
                    $gatewayFields.html(response.data.html);
                } else {
                    $gatewayFields.html('<p class="gc-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $gatewayFields.html('<p class="gc-error"><?php echo esc_js(__('Error al cargar el formulario.', 'flavor-chat-ia')); ?></p>');
            }
        });
    }

    // Evento: cambio de pasarela
    $form.on('change', 'input[name="gateway_id"]', function() {
        loadGatewayFields($(this).val());
    });

    // Cargar pasarela inicial
    var initialGateway = $('input[name="gateway_id"]:checked').val();
    if (initialGateway) {
        loadGatewayFields(initialGateway);
    }

    // Evento: envío del formulario
    $form.on('submit', function(e) {
        e.preventDefault();

        var $btn = $btnPay;
        $btn.prop('disabled', true);
        $btn.find('.gc-btn-text').hide();
        $btn.find('.gc-btn-loading').show();

        var formData = $form.serialize();
        formData += '&action=gc_process_checkout';

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else if (response.data.requires_action) {
                        // Manejar acciones adicionales (ej: Stripe 3D Secure)
                        handlePaymentAction(response.data);
                    } else {
                        window.location.href = '<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos') . '?payment=success'); ?>';
                    }
                } else {
                    mostrarAviso(response.data.error || '<?php echo esc_js(__('Error al procesar el pago.', 'flavor-chat-ia')); ?>', 'error');
                    $btn.prop('disabled', false);
                    $btn.find('.gc-btn-text').show();
                    $btn.find('.gc-btn-loading').hide();
                }
            },
            error: function() {
                mostrarAviso('<?php echo esc_js(__('Error de conexión. Inténtalo de nuevo.', 'flavor-chat-ia')); ?>', 'error');
                $btn.prop('disabled', false);
                $btn.find('.gc-btn-text').show();
                $btn.find('.gc-btn-loading').hide();
            }
        });
    });

    // Manejar acciones de pago (ej: confirmación 3D Secure)
    function handlePaymentAction(data) {
        if (data.client_secret && window.gcStripe && window.gcStripeCard) {
            // Stripe: confirmar pago con 3D Secure
            window.gcStripe.confirmCardPayment(data.client_secret, {
                payment_method: {
                    card: window.gcStripeCard,
                }
            }).then(function(result) {
                if (result.error) {
                    mostrarAviso(result.error.message, 'error');
                    $btnPay.prop('disabled', false);
                    $btnPay.find('.gc-btn-text').show();
                    $btnPay.find('.gc-btn-loading').hide();
                } else {
                    window.location.href = '<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos') . '?payment=success'); ?>';
                }
            });
        } else {
            $btnPay.prop('disabled', false);
            $btnPay.find('.gc-btn-text').show();
            $btnPay.find('.gc-btn-loading').hide();
        }
    }

})(jQuery);
</script>

<style>
.gc-checkout-container {
    display: flex;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}
.gc-checkout-main {
    flex: 1;
    min-width: 0;
}
.gc-checkout-sidebar {
    width: 280px;
    flex-shrink: 0;
}
.gc-checkout-title {
    margin-bottom: 30px;
    font-size: 24px;
}
.gc-checkout-summary,
.gc-checkout-payment {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.gc-checkout-summary h3,
.gc-checkout-payment h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
.gc-checkout-ciclo {
    margin-bottom: 15px;
    color: #666;
}
.gc-checkout-items {
    width: 100%;
    border-collapse: collapse;
}
.gc-checkout-items th,
.gc-checkout-items td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.gc-checkout-items th {
    font-weight: 600;
    font-size: 14px;
    color: #666;
}
.gc-checkout-total td {
    font-weight: 700;
    font-size: 18px;
    border-top: 2px solid #333;
}
.gc-checkout-discount td {
    color: #28a745;
}
.gc-payment-methods {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.gc-payment-method {
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: border-color 0.2s;
}
.gc-payment-method:has(input:checked) {
    border-color: #0073aa;
    background: #f0f7fb;
}
.gc-payment-option {
    display: block;
    padding: 15px;
    cursor: pointer;
}
.gc-payment-option input {
    margin-right: 10px;
}
.gc-payment-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}
.gc-payment-desc {
    display: block;
    margin-left: 28px;
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}
.gc-gateway-fields {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
    min-height: 50px;
}
.gc-checkout-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: background-color 0.2s;
}
.gc-btn-primary {
    background: #0073aa;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #005a87;
    color: #fff;
}
.gc-btn-secondary {
    background: #f0f0f0;
    color: #333;
}
.gc-btn-pay {
    font-size: 16px;
    padding: 14px 30px;
}
.gc-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: gc-spin 1s linear infinite;
}
@keyframes gc-spin {
    to { transform: rotate(360deg); }
}
.gc-checkout-secure {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px;
    background: #e7f5e9;
    border-radius: 6px;
    color: #28a745;
    font-weight: 600;
    margin-bottom: 20px;
}
.gc-checkout-help {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
}
.gc-checkout-help h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}
.gc-checkout-help p {
    margin: 0;
    font-size: 13px;
    color: #666;
}
.gc-checkout-error,
.gc-checkout-success {
    text-align: center;
    padding: 40px;
    background: #fff;
    border-radius: 8px;
}
.gc-checkout-success {
    color: #28a745;
}
.gc-checkout-success .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
.gc-loading {
    text-align: center;
    padding: 20px;
}
.gc-loading .gc-spinner {
    border-color: rgba(0,0,0,0.1);
    border-top-color: #0073aa;
}

@media (max-width: 768px) {
    .gc-checkout-container {
        flex-direction: column;
    }
    .gc-checkout-sidebar {
        width: 100%;
    }
    .gc-checkout-actions {
        flex-direction: column;
        gap: 10px;
    }
    .gc-checkout-actions .gc-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
