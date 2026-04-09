<?php
/**
 * Componente: Payment Form
 *
 * Formulario de pago integrado con múltiples métodos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param float  $amount       Importe a pagar
 * @param string $currency     Moneda (EUR, USD)
 * @param string $description  Descripción del pago
 * @param string $item_type    Tipo de item (curso, evento, cuota, pedido)
 * @param int    $item_id      ID del item
 * @param array  $methods      Métodos habilitados: ['card', 'paypal', 'bizum', 'transfer', 'cash']
 * @param string $return_url   URL de retorno tras pago
 * @param string $color        Color del tema
 * @param bool   $show_summary Mostrar resumen del pedido
 * @param array  $items        Items del pedido [['name' => '', 'qty' => 1, 'price' => 0]]
 */

if (!defined('ABSPATH')) {
    exit;
}

$amount = floatval($amount ?? 0);
$currency = $currency ?? 'EUR';
$description = $description ?? '';
$item_type = $item_type ?? 'item';
$item_id = intval($item_id ?? 0);
$methods = $methods ?? ['card', 'paypal'];
$return_url = $return_url ?? home_url();
$color = $color ?? 'blue';
$show_summary = $show_summary ?? true;
$items = $items ?? [];

$form_id = 'payment-' . wp_rand(1000, 9999);

// Verificar si Stripe está configurado
$stripe_enabled = get_option('flavor_stripe_enabled', false) && !empty(get_option('flavor_stripe_public_key', ''));
$paypal_enabled = get_option('flavor_paypal_enabled', false) && !empty(get_option('flavor_paypal_client_id', ''));

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg_solid' => 'bg-blue-500', 'text' => 'text-blue-600'];
}

// Símbolos de moneda
$currency_symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
$symbol = $currency_symbols[$currency] ?? $currency;

// Iconos de métodos de pago
$method_icons = [
    'card'     => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    'paypal'   => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106z"/></svg>',
    'bizum'    => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="8" font-weight="bold">B</text></svg>',
    'transfer' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
    'cash'     => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
];

$method_labels = [
    'card'     => __('Tarjeta de crédito/débito', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'paypal'   => __('PayPal', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'bizum'    => __('Bizum', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'transfer' => __('Transferencia bancaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cash'     => __('Efectivo (recoger)', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="flavor-payment-form bg-white rounded-2xl shadow-lg overflow-hidden" id="<?php echo esc_attr($form_id); ?>">

    <div class="grid md:grid-cols-5 gap-0">
        <!-- Resumen del pedido -->
        <?php if ($show_summary): ?>
            <div class="md:col-span-2 bg-gray-50 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php esc_html_e('Resumen del pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <?php if (!empty($items)): ?>
                    <div class="space-y-3 mb-4">
                        <?php foreach ($items as $item): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">
                                    <?php echo esc_html($item['name']); ?>
                                    <?php if (($item['qty'] ?? 1) > 1): ?>
                                        <span class="text-gray-400">× <?php echo esc_html($item['qty']); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="font-medium text-gray-900"><?php echo number_format($item['price'] * ($item['qty'] ?? 1), 2, ',', '.'); ?> <?php echo esc_html($symbol); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr class="border-gray-200 my-4">
                <?php endif; ?>

                <?php if ($description): ?>
                    <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($description); ?></p>
                <?php endif; ?>

                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-900"><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="text-2xl font-bold <?php echo esc_attr($color_classes['text']); ?>"><?php echo number_format($amount, 2, ',', '.'); ?> <?php echo esc_html($symbol); ?></span>
                </div>

                <!-- Seguridad -->
                <div class="mt-6 flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <?php esc_html_e('Pago 100% seguro y encriptado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario de pago -->
        <div class="<?php echo $show_summary ? 'md:col-span-3' : 'md:col-span-5'; ?> p-6">
            <h3 class="font-semibold text-gray-900 mb-4"><?php esc_html_e('Método de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <form id="<?php echo esc_attr($form_id); ?>-form" class="space-y-4">
                <input type="hidden" name="item_type" value="<?php echo esc_attr($item_type); ?>">
                <input type="hidden" name="item_id" value="<?php echo esc_attr($item_id); ?>">
                <input type="hidden" name="amount" value="<?php echo esc_attr($amount); ?>">
                <input type="hidden" name="currency" value="<?php echo esc_attr($currency); ?>">
                <?php wp_nonce_field('flavor_payment', '_wpnonce'); ?>

                <!-- Selección de método -->
                <div class="space-y-2">
                    <?php foreach ($methods as $method): ?>
                        <?php
                        // Verificar disponibilidad
                        if ($method === 'card' && !$stripe_enabled) continue;
                        if ($method === 'paypal' && !$paypal_enabled) continue;
                        ?>
                        <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-gray-300 payment-method-option"
                               data-method="<?php echo esc_attr($method); ?>">
                            <input type="radio" name="payment_method" value="<?php echo esc_attr($method); ?>"
                                   class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                   <?php echo $method === $methods[0] ? 'checked' : ''; ?>>
                            <span class="text-gray-500"><?php echo $method_icons[$method] ?? ''; ?></span>
                            <span class="font-medium text-gray-700"><?php echo esc_html($method_labels[$method] ?? ucfirst($method)); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Campos de tarjeta (Stripe) -->
                <?php if (in_array('card', $methods) && $stripe_enabled): ?>
                    <div id="<?php echo esc_attr($form_id); ?>-card-fields" class="payment-fields space-y-4" data-method="card">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e('Número de tarjeta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div id="<?php echo esc_attr($form_id); ?>-card-element" class="p-3 border border-gray-300 rounded-lg"></div>
                        </div>
                        <div id="<?php echo esc_attr($form_id); ?>-card-errors" class="text-sm text-red-600 hidden"></div>
                    </div>
                <?php endif; ?>

                <!-- Campos de transferencia -->
                <?php if (in_array('transfer', $methods)): ?>
                    <div id="<?php echo esc_attr($form_id); ?>-transfer-fields" class="payment-fields hidden" data-method="transfer">
                        <div class="bg-blue-50 rounded-xl p-4">
                            <p class="font-medium text-gray-900 mb-2"><?php esc_html_e('Datos para transferencia:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <div class="space-y-1 text-sm text-gray-700">
                                <p><strong><?php esc_html_e('IBAN:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(get_option('flavor_bank_iban', 'ES00 0000 0000 0000 0000 0000')); ?></p>
                                <p><strong><?php esc_html_e('Beneficiario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(get_option('flavor_bank_name', get_bloginfo('name'))); ?></p>
                                <p><strong><?php esc_html_e('Concepto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($item_type . '-' . $item_id); ?></p>
                            </div>
                            <p class="mt-3 text-xs text-gray-500"><?php esc_html_e('El pedido se confirmará al recibir la transferencia (1-3 días hábiles)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Campos de efectivo -->
                <?php if (in_array('cash', $methods)): ?>
                    <div id="<?php echo esc_attr($form_id); ?>-cash-fields" class="payment-fields hidden" data-method="cash">
                        <div class="bg-yellow-50 rounded-xl p-4">
                            <p class="font-medium text-gray-900 mb-2"><?php esc_html_e('Pago en efectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <p class="text-sm text-gray-700"><?php esc_html_e('Paga al recoger tu pedido. Te enviaremos los detalles de recogida por email.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botón de pago -->
                <button type="submit" id="<?php echo esc_attr($form_id); ?>-submit"
                        class="w-full py-4 px-6 text-white font-semibold rounded-xl <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span><?php printf(esc_html__('Pagar %s %s', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($amount, 2, ',', '.'), $symbol); ?></span>
                </button>

                <!-- Mensaje de estado -->
                <div id="<?php echo esc_attr($form_id); ?>-status" class="hidden text-center py-3 rounded-lg"></div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const formId = '<?php echo esc_js($form_id); ?>';
    const container = document.getElementById(formId);
    const form = document.getElementById(formId + '-form');
    const submitBtn = document.getElementById(formId + '-submit');
    const statusEl = document.getElementById(formId + '-status');

    // Toggle campos según método
    const methodOptions = container.querySelectorAll('.payment-method-option');
    const paymentFields = container.querySelectorAll('.payment-fields');

    methodOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');

        option.addEventListener('click', () => {
            radio.checked = true;
            updateFields(radio.value);
            updateStyles();
        });

        radio.addEventListener('change', () => {
            updateFields(radio.value);
            updateStyles();
        });
    });

    function updateFields(method) {
        paymentFields.forEach(fields => {
            fields.classList.toggle('hidden', fields.dataset.method !== method);
        });
    }

    function updateStyles() {
        methodOptions.forEach(option => {
            const radio = option.querySelector('input[type="radio"]');
            option.classList.toggle('border-blue-500', radio.checked);
            option.classList.toggle('bg-blue-50', radio.checked);
        });
    }

    // Inicializar
    const initialMethod = form.querySelector('input[name="payment_method"]:checked');
    if (initialMethod) {
        updateFields(initialMethod.value);
        updateStyles();
    }

    <?php if (in_array('card', $methods) && $stripe_enabled): ?>
    // Inicializar Stripe
    if (typeof Stripe !== 'undefined') {
        const stripe = Stripe('<?php echo esc_js(get_option('flavor_stripe_public_key', '')); ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#374151',
                    '::placeholder': { color: '#9CA3AF' }
                }
            }
        });

        cardElement.mount('#' + formId + '-card-element');

        cardElement.on('change', function(event) {
            const errorEl = document.getElementById(formId + '-card-errors');
            if (event.error) {
                errorEl.textContent = event.error.message;
                errorEl.classList.remove('hidden');
            } else {
                errorEl.classList.add('hidden');
            }
        });

        // Form submit con Stripe
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const method = form.querySelector('input[name="payment_method"]:checked').value;

            if (method === 'card') {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> <?php esc_html_e('Procesando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';

                // Crear PaymentIntent en el servidor
                const response = await fetch(flavorAjax.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'flavor_create_payment_intent',
                        amount: '<?php echo esc_js($amount * 100); ?>', // En centavos
                        currency: '<?php echo esc_js(strtolower($currency)); ?>',
                        item_type: '<?php echo esc_js($item_type); ?>',
                        item_id: '<?php echo esc_js($item_id); ?>',
                        _wpnonce: form.querySelector('[name="_wpnonce"]').value
                    })
                });

                const { clientSecret, error } = await response.json();

                if (error) {
                    showStatus(error, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<?php printf(esc_html__('Pagar %s %s', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($amount, 2, ',', '.'), $symbol); ?>';
                    return;
                }

                // Confirmar pago
                const { paymentIntent, error: stripeError } = await stripe.confirmCardPayment(clientSecret, {
                    payment_method: { card: cardElement }
                });

                if (stripeError) {
                    showStatus(stripeError.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<?php printf(esc_html__('Pagar %s %s', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($amount, 2, ',', '.'), $symbol); ?>';
                } else if (paymentIntent.status === 'succeeded') {
                    showStatus('<?php esc_html_e('¡Pago completado con éxito!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', 'success');
                    setTimeout(() => {
                        window.location.href = '<?php echo esc_js($return_url); ?>?payment=success&id=' + paymentIntent.id;
                    }, 1500);
                }
            } else {
                // Otros métodos
                processOtherMethod(method);
            }
        });
    }
    <?php else: ?>
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const method = form.querySelector('input[name="payment_method"]:checked').value;
        processOtherMethod(method);
    });
    <?php endif; ?>

    async function processOtherMethod(method) {
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'flavor_process_payment');
        formData.append('payment_method', method);

        const response = await fetch(flavorAjax.url, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (data.data.redirect) {
                window.location.href = data.data.redirect;
            } else {
                showStatus(data.data.message || '<?php esc_html_e('Pedido registrado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', 'success');
                setTimeout(() => window.location.href = '<?php echo esc_js($return_url); ?>', 2000);
            }
        } else {
            showStatus(data.data || '<?php esc_html_e('Error al procesar el pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', 'error');
            submitBtn.disabled = false;
        }
    }

    function showStatus(message, type) {
        statusEl.textContent = message;
        statusEl.className = 'text-center py-3 rounded-lg ' + (type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700');
        statusEl.classList.remove('hidden');
    }
})();
</script>
