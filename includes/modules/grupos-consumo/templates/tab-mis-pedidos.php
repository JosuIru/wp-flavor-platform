<?php
/**
 * Template: Mis Pedidos - Grupos de Consumo
 *
 * Listado de pedidos del usuario con historial.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="gc-pedidos-login">';
    echo '<p>' . esc_html__('Inicia sesión para ver tus pedidos.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesión', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

$user_id = get_current_user_id();
global $wpdb;

// Obtener entregas del usuario
$tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

$entregas = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.post_title as ciclo_nombre,
            cm_fecha.meta_value as fecha_entrega
     FROM {$tabla_entregas} e
     LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
     LEFT JOIN {$wpdb->postmeta} cm_fecha ON e.ciclo_id = cm_fecha.post_id AND cm_fecha.meta_key = '_gc_fecha_entrega'
     WHERE e.usuario_id = %d
     ORDER BY e.fecha_creacion DESC",
    $user_id
));

// Mensaje de éxito/error
$mensaje = '';
$payment_status = isset($_GET['payment']) ? sanitize_key($_GET['payment']) : '';
if ($payment_status === 'success') {
    $mensaje = '<div class="gc-notice gc-notice-success"><span class="dashicons dashicons-yes"></span> ' . esc_html__('Pago realizado correctamente.', 'flavor-chat-ia') . '</div>';
} elseif ($payment_status === 'cancelled') {
    $mensaje = '<div class="gc-notice gc-notice-warning"><span class="dashicons dashicons-info"></span> ' . esc_html__('El pago fue cancelado.', 'flavor-chat-ia') . '</div>';
}
?>

<div class="gc-pedidos-container">
    <h2 class="gc-pedidos-title">
        <span class="dashicons dashicons-clipboard"></span>
        <?php esc_html_e('Mis Pedidos', 'flavor-chat-ia'); ?>
    </h2>

    <?php echo $mensaje; ?>

    <?php if (empty($entregas)) : ?>
    <div class="gc-pedidos-empty">
        <span class="dashicons dashicons-clipboard"></span>
        <p><?php esc_html_e('Aún no tienes ningún pedido.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/catalogo/')); ?>" class="gc-btn gc-btn-primary">
            <?php esc_html_e('Explorar catálogo', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else : ?>

    <div class="gc-pedidos-list">
        <?php foreach ($entregas as $entrega) :
            // Obtener items del pedido
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, prod.post_title as producto_nombre
                 FROM {$tabla_pedidos} p
                 LEFT JOIN {$wpdb->posts} prod ON p.producto_id = prod.ID
                 WHERE p.ciclo_id = %d AND p.usuario_id = %d",
                $entrega->ciclo_id,
                $user_id
            ));

            $estado_pago_class = '';
            $estado_pago_label = '';
            switch ($entrega->estado_pago) {
                case 'completado':
                    $estado_pago_class = 'gc-status-success';
                    $estado_pago_label = __('Pagado', 'flavor-chat-ia');
                    break;
                case 'pendiente':
                case 'pendiente_recogida':
                    $estado_pago_class = 'gc-status-warning';
                    $estado_pago_label = __('Pendiente', 'flavor-chat-ia');
                    break;
                case 'procesando':
                    $estado_pago_class = 'gc-status-info';
                    $estado_pago_label = __('Procesando', 'flavor-chat-ia');
                    break;
                default:
                    $estado_pago_class = 'gc-status-secondary';
                    $estado_pago_label = ucfirst($entrega->estado_pago);
            }

            $estado_recogida_class = '';
            $estado_recogida_label = '';
            switch ($entrega->estado_recogida ?? 'pendiente') {
                case 'recogido':
                    $estado_recogida_class = 'gc-status-success';
                    $estado_recogida_label = __('Recogido', 'flavor-chat-ia');
                    break;
                case 'pendiente':
                    $estado_recogida_class = 'gc-status-warning';
                    $estado_recogida_label = __('Por recoger', 'flavor-chat-ia');
                    break;
                default:
                    $estado_recogida_class = 'gc-status-secondary';
                    $estado_recogida_label = ucfirst($entrega->estado_recogida ?? 'pendiente');
            }
        ?>
        <div class="gc-pedido-card">
            <div class="gc-pedido-header">
                <div class="gc-pedido-info">
                    <span class="gc-pedido-id">#<?php echo esc_html($entrega->id); ?></span>
                    <span class="gc-pedido-ciclo"><?php echo esc_html($entrega->ciclo_nombre ?: __('Ciclo', 'flavor-chat-ia')); ?></span>
                </div>
                <div class="gc-pedido-estados">
                    <span class="gc-status-badge <?php echo esc_attr($estado_pago_class); ?>">
                        <?php echo esc_html($estado_pago_label); ?>
                    </span>
                    <?php if ($entrega->estado_pago === 'completado') : ?>
                    <span class="gc-status-badge <?php echo esc_attr($estado_recogida_class); ?>">
                        <?php echo esc_html($estado_recogida_label); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gc-pedido-body">
                <div class="gc-pedido-items">
                    <?php
                    $items_mostrar = array_slice($items, 0, 3);
                    foreach ($items_mostrar as $item) :
                    ?>
                    <div class="gc-pedido-item">
                        <span class="gc-item-cantidad"><?php echo esc_html($item->cantidad); ?>x</span>
                        <span class="gc-item-nombre"><?php echo esc_html($item->producto_nombre); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($items) > 3) : ?>
                    <div class="gc-pedido-mas">
                        +<?php echo (count($items) - 3); ?> <?php esc_html_e('más', 'flavor-chat-ia'); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="gc-pedido-meta">
                    <?php if ($entrega->fecha_entrega) : ?>
                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Entrega:', 'flavor-chat-ia'); ?>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entrega->fecha_entrega))); ?>
                    </div>
                    <?php endif; ?>
                    <div class="gc-meta-item">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Pedido:', 'flavor-chat-ia'); ?>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entrega->fecha_creacion))); ?>
                    </div>
                </div>
            </div>

            <div class="gc-pedido-footer">
                <div class="gc-pedido-total">
                    <span class="gc-total-label"><?php esc_html_e('Total:', 'flavor-chat-ia'); ?></span>
                    <span class="gc-total-value"><?php echo number_format($entrega->total_final, 2, ',', '.'); ?> €</span>
                </div>

                <div class="gc-pedido-actions">
                    <?php if ($entrega->estado_pago === 'pendiente') : ?>
                    <a href="<?php echo esc_url(add_query_arg('entrega_id', $entrega->id, home_url('/mi-portal/grupos-consumo/checkout/'))); ?>"
                       class="gc-btn gc-btn-primary gc-btn-sm">
                        <?php esc_html_e('Pagar ahora', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>

                    <button type="button"
                            class="gc-btn gc-btn-secondary gc-btn-sm gc-btn-toggle-details"
                            data-entrega="<?php echo esc_attr($entrega->id); ?>">
                        <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <!-- Detalles expandibles -->
            <div class="gc-pedido-details" id="gc-details-<?php echo esc_attr($entrega->id); ?>" style="display: none;">
                <h4><?php esc_html_e('Detalle del pedido', 'flavor-chat-ia'); ?></h4>
                <table class="gc-details-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Producto', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Cantidad', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Subtotal', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) :
                            $item_subtotal = (float) $item->cantidad * (float) $item->precio_unitario;
                        ?>
                        <tr>
                            <td><?php echo esc_html($item->producto_nombre); ?></td>
                            <td><?php echo esc_html($item->cantidad); ?></td>
                            <td><?php echo number_format($item->precio_unitario, 2, ',', '.'); ?> €</td>
                            <td><?php echo number_format($item_subtotal, 2, ',', '.'); ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><?php esc_html_e('Total pedido', 'flavor-chat-ia'); ?></td>
                            <td><strong><?php echo number_format($entrega->total_final, 2, ',', '.'); ?> €</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($entrega->notas) : ?>
                <div class="gc-pedido-notas">
                    <strong><?php esc_html_e('Notas:', 'flavor-chat-ia'); ?></strong>
                    <p><?php echo esc_html($entrega->notas); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<script>
(function($) {
    'use strict';

    // Toggle detalles
    $('.gc-btn-toggle-details').on('click', function() {
        var entregaId = $(this).data('entrega');
        var $details = $('#gc-details-' + entregaId);
        var $btn = $(this);

        $details.slideToggle(200, function() {
            if ($details.is(':visible')) {
                $btn.text('<?php echo esc_js(__('Ocultar detalles', 'flavor-chat-ia')); ?>');
            } else {
                $btn.text('<?php echo esc_js(__('Ver detalles', 'flavor-chat-ia')); ?>');
            }
        });
    });

})(jQuery);
</script>

<style>
.gc-pedidos-container {
    max-width: 800px;
}
.gc-pedidos-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.gc-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.gc-notice-success {
    background: #e7f5e9;
    color: #28a745;
}
.gc-notice-warning {
    background: #fff3cd;
    color: #856404;
}
.gc-pedidos-empty {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 8px;
}
.gc-pedidos-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
}
.gc-pedidos-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.gc-pedido-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}
.gc-pedido-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}
.gc-pedido-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.gc-pedido-id {
    font-weight: 700;
    color: #333;
}
.gc-pedido-ciclo {
    color: #666;
    font-size: 14px;
}
.gc-pedido-estados {
    display: flex;
    gap: 8px;
}
.gc-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.gc-status-success {
    background: #e7f5e9;
    color: #28a745;
}
.gc-status-warning {
    background: #fff3cd;
    color: #856404;
}
.gc-status-info {
    background: #d1ecf1;
    color: #0c5460;
}
.gc-status-secondary {
    background: #e9ecef;
    color: #495057;
}
.gc-pedido-body {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    gap: 20px;
}
.gc-pedido-items {
    flex: 1;
}
.gc-pedido-item {
    padding: 5px 0;
    color: #555;
}
.gc-item-cantidad {
    font-weight: 600;
    margin-right: 5px;
}
.gc-pedido-mas {
    color: #999;
    font-size: 13px;
    font-style: italic;
}
.gc-pedido-meta {
    text-align: right;
}
.gc-meta-item {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    color: #666;
    font-size: 13px;
    margin-bottom: 5px;
}
.gc-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.gc-pedido-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fafafa;
    border-top: 1px solid #eee;
}
.gc-total-label {
    color: #666;
    margin-right: 10px;
}
.gc-total-value {
    font-size: 18px;
    font-weight: 700;
    color: #333;
}
.gc-pedido-actions {
    display: flex;
    gap: 10px;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}
.gc-btn-sm {
    padding: 8px 14px;
    font-size: 14px;
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
    background: #e9ecef;
    color: #495057;
}
.gc-btn-secondary:hover {
    background: #ddd;
}
.gc-pedido-details {
    padding: 15px;
    background: #fff;
    border-top: 1px solid #eee;
}
.gc-pedido-details h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    color: #666;
}
.gc-details-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.gc-details-table th,
.gc-details-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.gc-details-table th {
    font-weight: 600;
    color: #666;
}
.gc-details-table tfoot td {
    font-weight: 600;
    border-top: 2px solid #333;
}
.gc-pedido-notas {
    margin-top: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}
.gc-pedidos-login {
    text-align: center;
    padding: 40px;
}
@media (max-width: 600px) {
    .gc-pedido-header,
    .gc-pedido-body,
    .gc-pedido-footer {
        flex-direction: column;
        gap: 10px;
    }
    .gc-pedido-meta {
        text-align: left;
    }
    .gc-meta-item {
        justify-content: flex-start;
    }
    .gc-pedido-actions {
        width: 100%;
    }
    .gc-pedido-actions .gc-btn {
        flex: 1;
        justify-content: center;
    }
}
</style>
