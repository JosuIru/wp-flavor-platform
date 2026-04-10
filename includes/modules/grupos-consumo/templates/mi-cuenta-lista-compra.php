<?php
/**
 * Template: Tab Lista de Compra en Mi Cuenta
 *
 * @package FlavorPlatform
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $args['items'] ?? [];
$ciclo_actual = $args['ciclo'] ?? null;
?>

<div class="gc-dashboard-lista-compra">
    <div class="gc-dashboard-header">
        <h2><?php _e('Mi Lista de la Compra', 'flavor-platform'); ?></h2>
        <?php if ($ciclo_actual): ?>
            <div class="gc-ciclo-badge">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php printf(__('Ciclo activo hasta %s', 'flavor-platform'), date_i18n('d M', strtotime($ciclo_actual['fecha_cierre']))); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="gc-dashboard-empty">
            <div class="gc-empty-icon">🛒</div>
            <h3><?php _e('Tu lista está vacía', 'flavor-platform'); ?></h3>
            <p><?php _e('Añade productos para preparar tu próximo pedido.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-primary">
                <span class="dashicons dashicons-cart"></span>
                <?php _e('Explorar productos', 'flavor-platform'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="gc-lista-items">
            <?php foreach ($items as $item): ?>
                <div class="gc-lista-item" data-id="<?php echo esc_attr($item['id']); ?>">
                    <div class="gc-item-imagen">
                        <?php if (!empty($item['imagen'])): ?>
                            <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['nombre']); ?>">
                        <?php else: ?>
                            <span class="gc-item-placeholder">🥬</span>
                        <?php endif; ?>
                    </div>

                    <div class="gc-item-info">
                        <h4><?php echo esc_html($item['nombre']); ?></h4>
                        <span class="gc-item-precio">
                            <?php echo number_format($item['precio'], 2, ',', '.'); ?> €/<?php echo esc_html($item['unidad']); ?>
                        </span>
                        <?php if (!empty($item['notas'])): ?>
                            <span class="gc-item-notas"><?php echo esc_html($item['notas']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="gc-item-cantidad">
                        <button type="button" class="gc-btn-cantidad" data-action="decrementar">−</button>
                        <input type="number" value="<?php echo esc_attr($item['cantidad']); ?>" min="1" class="gc-cantidad-input">
                        <button type="button" class="gc-btn-cantidad" data-action="incrementar">+</button>
                    </div>

                    <div class="gc-item-subtotal">
                        <?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?> €
                    </div>

                    <button type="button" class="gc-btn-eliminar" data-action="eliminar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="gc-lista-footer">
            <div class="gc-lista-total">
                <span class="gc-total-label"><?php _e('Total estimado:', 'flavor-platform'); ?></span>
                <span class="gc-total-valor">
                    <?php
                    $total = array_sum(array_map(function($item) {
                        return $item['precio'] * $item['cantidad'];
                    }, $items));
                    echo number_format($total, 2, ',', '.');
                    ?> €
                </span>
            </div>

            <div class="gc-lista-acciones">
                <?php if ($ciclo_actual && $ciclo_actual['estado'] === 'abierto'): ?>
                    <button type="button" class="gc-btn gc-btn-primary gc-btn-convertir-pedido" id="gc-convertir-pedido">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Convertir en pedido', 'flavor-platform'); ?>
                    </button>
                <?php else: ?>
                    <p class="gc-aviso-ciclo">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Guarda tu lista para el próximo ciclo de pedidos.', 'flavor-platform'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
