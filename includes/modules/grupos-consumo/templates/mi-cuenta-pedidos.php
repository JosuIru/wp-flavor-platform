<?php
/**
 * Template: Tab Mis Pedidos en Mi Cuenta
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$pedidos = $args['pedidos'] ?? [];
$pedido_actual = $args['pedido_actual'] ?? null;
?>

<div class="gc-dashboard-pedidos">
    <div class="gc-dashboard-header">
        <h2><?php _e('Historial', 'flavor-platform'); ?></h2>
    </div>

    <?php if ($pedido_actual): ?>
        <!-- Pedido del ciclo actual -->
        <div class="gc-pedido-actual">
            <div class="gc-pedido-actual-header">
                <span class="gc-badge gc-badge-success"><?php _e('Pedido activo', 'flavor-platform'); ?></span>
                <h3><?php echo esc_html($pedido_actual['ciclo_titulo']); ?></h3>
            </div>

            <div class="gc-pedido-actual-info">
                <div class="gc-info-item">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php printf(__('Entrega: %s', 'flavor-platform'), date_i18n('d M Y', strtotime($pedido_actual['fecha_entrega']))); ?></span>
                </div>
                <div class="gc-info-item">
                    <span class="dashicons dashicons-location"></span>
                    <span><?php echo esc_html($pedido_actual['lugar_entrega']); ?></span>
                </div>
                <div class="gc-info-item">
                    <span class="dashicons dashicons-clock"></span>
                    <span><?php echo esc_html($pedido_actual['hora_entrega']); ?></span>
                </div>
            </div>

            <div class="gc-pedido-actual-productos">
                <h4><?php _e('Productos pedidos:', 'flavor-platform'); ?></h4>
                <ul>
                    <?php foreach ($pedido_actual['productos'] as $producto): ?>
                        <li>
                            <span class="gc-producto-nombre"><?php echo esc_html($producto['nombre']); ?></span>
                            <span class="gc-producto-cantidad">x<?php echo esc_html($producto['cantidad']); ?></span>
                            <span class="gc-producto-precio"><?php echo number_format($producto['subtotal'], 2, ',', '.'); ?> €</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="gc-pedido-actual-footer">
                <div class="gc-pedido-total">
                    <span><?php _e('Total:', 'flavor-platform'); ?></span>
                    <strong><?php echo number_format($pedido_actual['total'], 2, ',', '.'); ?> €</strong>
                </div>
                <div class="gc-pedido-estado">
                    <span class="gc-estado gc-estado-<?php echo esc_attr($pedido_actual['estado_pago']); ?>">
                        <?php echo $pedido_actual['estado_pago'] === 'pagado' ? __('Pagado', 'flavor-platform') : __('Pendiente de pago', 'flavor-platform'); ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($pedidos) && !$pedido_actual): ?>
        <div class="gc-dashboard-empty">
            <div class="gc-empty-icon">📦</div>
            <h3><?php _e('No tienes pedidos', 'flavor-platform'); ?></h3>
            <p><?php _e('Tu historial de pedidos aparecerá aquí.', 'flavor-platform'); ?></p>
        </div>
    <?php elseif (!empty($pedidos)): ?>
        <!-- Historial -->
        <div class="gc-pedidos-historial">
            <h3><?php _e('Historial', 'flavor-platform'); ?></h3>

            <div class="gc-pedidos-lista">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="gc-pedido-mini" data-id="<?php echo esc_attr($pedido['id']); ?>">
                        <div class="gc-pedido-mini-fecha">
                            <span class="gc-dia"><?php echo date('d', strtotime($pedido['fecha'])); ?></span>
                            <span class="gc-mes"><?php echo date_i18n('M', strtotime($pedido['fecha'])); ?></span>
                        </div>

                        <div class="gc-pedido-mini-info">
                            <strong><?php echo esc_html($pedido['ciclo_titulo']); ?></strong>
                            <span><?php echo count($pedido['productos']); ?> <?php _e('productos', 'flavor-platform'); ?></span>
                        </div>

                        <div class="gc-pedido-mini-total">
                            <?php echo number_format($pedido['total'], 2, ',', '.'); ?> €
                        </div>

                        <span class="gc-pedido-mini-estado gc-estado-<?php echo esc_attr($pedido['estado']); ?>">
                            <?php
                            $estados = [
                                'recogido' => '✓',
                                'pagado' => '€',
                                'pendiente' => '⏳',
                            ];
                            echo $estados[$pedido['estado']] ?? '•';
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="<?php echo esc_url(add_query_arg('gc_section', 'historial')); ?>" class="gc-btn gc-btn-secondary gc-btn-full">
                <?php _e('Ver historial completo', 'flavor-platform'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
