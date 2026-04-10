<?php
/**
 * Vista de Sistema de Pagos y Distribución de Beneficios
 *
 * @package FlavorPlatform
 * @subpackage Advertising
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

global $wpdb;
$tabla_pagos = $wpdb->prefix . 'flavor_advertising_payments';

// Procesar solicitud de pago
if (isset($_POST['flavor_request_payment']) && check_admin_referer('flavor_request_payment_action', 'flavor_request_payment_nonce')) {
    $monto_solicitud = floatval($_POST['monto_solicitud']);
    $metodo_pago_seleccionado = sanitize_text_field($_POST['metodo_pago_solicitud']);

    // Insertar solicitud de pago
    $wpdb->insert(
        $tabla_pagos,
        [
            'fecha' => current_time('mysql'),
            'concepto' => 'Solicitud de pago',
            'anuncio_id' => null,
            'sitio_origen' => get_bloginfo('name'),
            'monto' => $monto_solicitud,
            'estado' => 'pending',
            'metodo_pago' => $metodo_pago_seleccionado,
            'usuario_id' => get_current_user_id()
        ],
        ['%s', '%s', '%d', '%s', '%f', '%s', '%s', '%d']
    );

    echo '<div class="notice notice-success"><p>' . esc_html__('Solicitud de pago enviada correctamente. Te notificaremos cuando sea procesada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
}

// Procesar configuración de método de pago
if (isset($_POST['flavor_payment_method']) && check_admin_referer('flavor_payment_method_action', 'flavor_payment_method_nonce')) {
    $metodo_pago = sanitize_text_field($_POST['metodo_pago']);
    $email_paypal = sanitize_email($_POST['email_paypal'] ?? '');
    $cuenta_bancaria = sanitize_text_field($_POST['cuenta_bancaria'] ?? '');
    $stripe_account_id = sanitize_text_field($_POST['stripe_account_id'] ?? '');
    $crypto_wallet = sanitize_text_field($_POST['crypto_wallet'] ?? '');

    update_option('flavor_advertising_payment_method', $metodo_pago);
    update_option('flavor_advertising_paypal_email', $email_paypal);
    update_option('flavor_advertising_bank_account', $cuenta_bancaria);
    update_option('flavor_advertising_stripe_account', $stripe_account_id);
    update_option('flavor_advertising_crypto_wallet', $crypto_wallet);

    echo '<div class="notice notice-success"><p>' . esc_html__('Método de pago actualizado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
}

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : '';

// Calcular balance actual
$balance_actual = $wpdb->get_var("SELECT SUM(monto) FROM {$tabla_pagos} WHERE estado = 'pending'") ?? 0;
$total_pagado = $wpdb->get_var("SELECT SUM(monto) FROM {$tabla_pagos} WHERE estado = 'paid'") ?? 0;
$total_cancelado = $wpdb->get_var("SELECT SUM(monto) FROM {$tabla_pagos} WHERE estado = 'cancelled'") ?? 0;

// Obtener umbral mínimo de pago
$umbral_minimo_pago = get_option('flavor_advertising_min_payout', 50);

// Construir consulta de historial de pagos
$query_historial = "SELECT * FROM {$tabla_pagos} WHERE 1=1";
$parametros_consulta = [];

if (!empty($filtro_estado)) {
    $query_historial .= " AND estado = %s";
    $parametros_consulta[] = $filtro_estado;
}

if (!empty($filtro_fecha_inicio)) {
    $query_historial .= " AND fecha >= %s";
    $parametros_consulta[] = $filtro_fecha_inicio . ' 00:00:00';
}

if (!empty($filtro_fecha_fin)) {
    $query_historial .= " AND fecha <= %s";
    $parametros_consulta[] = $filtro_fecha_fin . ' 23:59:59';
}

$query_historial .= " ORDER BY fecha DESC LIMIT 100";

if (!empty($parametros_consulta)) {
    $historial_pagos = $wpdb->get_results($wpdb->prepare($query_historial, $parametros_consulta));
} else {
    $historial_pagos = $wpdb->get_results($query_historial);
}

// Estadísticas por anunciante
$estadisticas_anunciantes = $wpdb->get_results(
    "SELECT
        sitio_origen,
        COUNT(*) as total_pagos,
        SUM(CASE WHEN estado = 'paid' THEN monto ELSE 0 END) as total_pagado,
        SUM(CASE WHEN estado = 'pending' THEN monto ELSE 0 END) as pendiente
    FROM {$tabla_pagos}
    GROUP BY sitio_origen
    ORDER BY total_pagado DESC
    LIMIT 10"
);

// Próximos pagos programados
$proximos_pagos = $wpdb->get_results(
    "SELECT * FROM {$tabla_pagos}
    WHERE estado = 'pending'
    ORDER BY fecha ASC
    LIMIT 5"
);

// Configuración actual de método de pago
$metodo_pago_actual = get_option('flavor_advertising_payment_method', 'paypal');
$email_paypal_guardado = get_option('flavor_advertising_paypal_email', '');
$cuenta_bancaria_guardada = get_option('flavor_advertising_bank_account', '');
$stripe_account_guardado = get_option('flavor_advertising_stripe_account', '');
$crypto_wallet_guardado = get_option('flavor_advertising_crypto_wallet', '');
?>

<div class="wrap">
    <h1><?php echo esc_html__('Pagos y Distribución de Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <!-- Balance y estadísticas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Balance Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 36px; font-weight: bold; color: #2271b1;">
                €<?php echo esc_html(number_format($balance_actual, 2, ',', '.')); ?>
            </p>
            <?php if ($balance_actual >= $umbral_minimo_pago) : ?>
                <p class="description" style="margin: 10px 0 0 0; color: #00a32a;">
                    <?php esc_html_e('✓ Disponible para solicitar pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            <?php else : ?>
                <p class="description" style="margin: 10px 0 0 0; color: #d63638;">
                    <?php
                    /* translators: %s: monto mínimo requerido */
                    printf(esc_html__('Mínimo requerido: €%s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html(number_format($umbral_minimo_pago, 2, ',', '.')));
                    ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Total Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 36px; font-weight: bold; color: #00a32a;">
                €<?php echo esc_html(number_format($total_pagado, 2, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Histórico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Pagos Cancelados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 36px; font-weight: bold; color: #d63638;">
                €<?php echo esc_html(number_format($total_cancelado, 2, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Histórico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Método de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(strtoupper($metodo_pago_actual)); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <a href="#payment-method-settings"><?php esc_html_e('Cambiar método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </p>
        </div>

    </div>

    <!-- Solicitar pago -->
    <?php if ($balance_actual >= $umbral_minimo_pago) : ?>
        <div class="card" style="margin: 20px 0; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
            <h2><?php esc_html_e('Solicitar Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('flavor_request_payment_action', 'flavor_request_payment_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Monto a Solicitar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="number" name="monto_solicitud" step="0.01" min="<?php echo esc_attr($umbral_minimo_pago); ?>" max="<?php echo esc_attr($balance_actual); ?>" value="<?php echo esc_attr($balance_actual); ?>" required style="width: 200px;">
                            <p class="description">
                                <?php
                                /* translators: %s: balance disponible */
                                printf(esc_html__('Balance disponible: €%s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html(number_format($balance_actual, 2, ',', '.')));
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Método de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="metodo_pago_solicitud" required>
                                <option value="paypal" <?php selected($metodo_pago_actual, 'paypal'); ?>>PayPal</option>
                                <option value="bank_transfer" <?php selected($metodo_pago_actual, 'bank_transfer'); ?>><?php esc_html_e('Transferencia Bancaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="stripe" <?php selected($metodo_pago_actual, 'stripe'); ?>>Stripe</option>
                                <option value="crypto" <?php selected($metodo_pago_actual, 'crypto'); ?>><?php esc_html_e('Criptomoneda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="flavor_request_payment" class="button button-primary button-large">
                        <?php esc_html_e('Solicitar Pago Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <!-- Filtros de historial -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Historial de Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <form method="get" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="flavor-advertising-payments">

            <label for="estado"><?php esc_html_e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="estado" id="estado">
                <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="pending" <?php selected($filtro_estado, 'pending'); ?>><?php esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="paid" <?php selected($filtro_estado, 'paid'); ?>><?php esc_html_e('Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="cancelled" <?php selected($filtro_estado, 'cancelled'); ?>><?php esc_html_e('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <label for="fecha_inicio" style="margin-left: 15px;"><?php esc_html_e('Desde:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo esc_attr($filtro_fecha_inicio); ?>">

            <label for="fecha_fin" style="margin-left: 15px;"><?php esc_html_e('Hasta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo esc_attr($filtro_fecha_fin); ?>">

            <button type="submit" class="button" style="margin-left: 15px;">
                <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-payments')); ?>" class="button">
                <?php esc_html_e('Limpiar Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Concepto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Monto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($historial_pagos)) : ?>
                    <?php foreach ($historial_pagos as $pago) : ?>
                        <tr>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($pago->fecha))); ?></td>
                            <td><?php echo esc_html($pago->concepto); ?></td>
                            <td><?php echo $pago->anuncio_id ? esc_html('#' . $pago->anuncio_id) : '-'; ?></td>
                            <td><?php echo esc_html($pago->sitio_origen); ?></td>
                            <td><strong>€<?php echo esc_html(number_format($pago->monto, 2, ',', '.')); ?></strong></td>
                            <td>
                                <?php
                                $estado_color = [
                                    'pending' => '#d63638',
                                    'paid' => '#00a32a',
                                    'cancelled' => '#999'
                                ];
                                $estado_texto = [
                                    'pending' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'paid' => __('Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'cancelled' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN)
                                ];
                                ?>
                                <span style="color: <?php echo esc_attr($estado_color[$pago->estado] ?? '#000'); ?>; font-weight: bold;">
                                    <?php echo esc_html($estado_texto[$pago->estado] ?? $pago->estado); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(strtoupper($pago->metodo_pago ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay pagos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Estadísticas por anunciante -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Estadísticas por Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Sitio / Anunciante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Total Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Total Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($estadisticas_anunciantes)) : ?>
                    <?php foreach ($estadisticas_anunciantes as $anunciante) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($anunciante->sitio_origen); ?></strong></td>
                            <td><?php echo esc_html($anunciante->total_pagos); ?></td>
                            <td><strong style="color: #00a32a;">€<?php echo esc_html(number_format($anunciante->total_pagado, 2, ',', '.')); ?></strong></td>
                            <td><strong style="color: #d63638;">€<?php echo esc_html(number_format($anunciante->pendiente, 2, ',', '.')); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay estadísticas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Próximos pagos programados -->
    <?php if (!empty($proximos_pagos)) : ?>
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2><?php esc_html_e('Próximos Pagos Programados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Concepto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Monto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximos_pagos as $pago) : ?>
                        <tr>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($pago->fecha))); ?></td>
                            <td><?php echo esc_html($pago->concepto); ?></td>
                            <td><strong>€<?php echo esc_html(number_format($pago->monto, 2, ',', '.')); ?></strong></td>
                            <td><?php echo esc_html(strtoupper($pago->metodo_pago ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Configuración de método de pago -->
    <div class="card" style="margin: 20px 0; padding: 20px;" id="payment-method-settings">
        <h2><?php esc_html_e('Configuración de Método de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <form method="post" action="">
            <?php wp_nonce_field('flavor_payment_method_action', 'flavor_payment_method_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Método Preferido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="metodo_pago" id="flavor-payment-method-select" required>
                            <option value="paypal" <?php selected($metodo_pago_actual, 'paypal'); ?>>PayPal</option>
                            <option value="bank_transfer" <?php selected($metodo_pago_actual, 'bank_transfer'); ?>><?php esc_html_e('Transferencia Bancaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="stripe" <?php selected($metodo_pago_actual, 'stripe'); ?>>Stripe</option>
                            <option value="crypto" <?php selected($metodo_pago_actual, 'crypto'); ?>><?php esc_html_e('Criptomoneda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </td>
                </tr>
                <tr id="paypal-fields" style="display: none;">
                    <th scope="row"><?php esc_html_e('Email de PayPal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="email" name="email_paypal" class="regular-text" value="<?php echo esc_attr($email_paypal_guardado); ?>">
                        <p class="description"><?php esc_html_e('Email asociado a tu cuenta de PayPal.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr id="bank-fields" style="display: none;">
                    <th scope="row"><?php esc_html_e('Cuenta Bancaria (IBAN)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="text" name="cuenta_bancaria" class="regular-text" value="<?php echo esc_attr($cuenta_bancaria_guardada); ?>" placeholder="ES00 0000 0000 0000 0000 0000">
                        <p class="description"><?php esc_html_e('IBAN de tu cuenta bancaria para transferencias.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr id="stripe-fields" style="display: none;">
                    <th scope="row"><?php esc_html_e('Stripe Account ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="text" name="stripe_account_id" class="regular-text" value="<?php echo esc_attr($stripe_account_guardado); ?>">
                        <p class="description"><?php esc_html_e('ID de tu cuenta de Stripe Connect.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr id="crypto-fields" style="display: none;">
                    <th scope="row"><?php esc_html_e('Dirección de Wallet', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="text" name="crypto_wallet" class="regular-text" value="<?php echo esc_attr($crypto_wallet_guardado); ?>">
                        <p class="description"><?php esc_html_e('Dirección de tu wallet de criptomonedas (BTC, ETH, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="flavor_payment_method" class="button button-primary">
                    <?php esc_html_e('Guardar Método de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Mostrar/ocultar campos según método de pago seleccionado
    function togglePaymentFields() {
        var metodo = $('#flavor-payment-method-select').val();

        $('#paypal-fields, #bank-fields, #stripe-fields, #crypto-fields').hide();

        if (metodo === 'paypal') {
            $('#paypal-fields').show();
        } else if (metodo === 'bank_transfer') {
            $('#bank-fields').show();
        } else if (metodo === 'stripe') {
            $('#stripe-fields').show();
        } else if (metodo === 'crypto') {
            $('#crypto-fields').show();
        }
    }

    $('#flavor-payment-method-select').on('change', togglePaymentFields);
    togglePaymentFields();
});
</script>
