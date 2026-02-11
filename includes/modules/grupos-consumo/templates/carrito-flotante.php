<?php
/**
 * Template: Carrito Flotante para Grupos de Consumo
 *
 * Mini-carrito fijo en esquina inferior derecha.
 * Se incluye automaticamente en el frontend cuando hay sesion activa.
 *
 * Variables disponibles:
 * - $items_carrito: Items en el carrito del usuario
 * - $total_carrito: Total del carrito
 * - $total_items: Numero de items
 * - $ciclo_activo: Datos del ciclo activo
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$items_carrito = $args['items_carrito'] ?? [];
$total_carrito = $args['total_carrito'] ?? 0;
$total_items = $args['total_items'] ?? 0;
$ciclo_activo = $args['ciclo_activo'] ?? null;
$url_carrito = $args['url_carrito'] ?? home_url('/mi-cuenta/?tab=gc-lista-compra');
$porcentaje_gestion = $args['porcentaje_gestion'] ?? 0;
$gastos_gestion = $total_carrito * ($porcentaje_gestion / 100);
$total_final = $total_carrito + $gastos_gestion;
?>

<div class="flavor-gc-carrito-flotante <?php echo $total_items > 0 ? 'tiene-items' : ''; ?>"
     id="gc-carrito-flotante"
     data-visible="false">

    <!-- Boton del carrito (siempre visible) -->
    <button type="button" class="flavor-gc-carrito-boton" id="gc-carrito-toggle" aria-label="<?php esc_attr_e('Ver carrito', 'flavor-chat-ia'); ?>">
        <span class="dashicons dashicons-cart"></span>
        <span class="flavor-gc-carrito-contador" id="gc-carrito-contador">
            <?php echo esc_html($total_items); ?>
        </span>
    </button>

    <!-- Panel expandido del carrito -->
    <div class="flavor-gc-carrito-panel" id="gc-carrito-panel">
        <header class="flavor-gc-carrito-panel-header">
            <h3>
                <span class="dashicons dashicons-cart"></span>
                <?php _e('Mi pedido', 'flavor-chat-ia'); ?>
            </h3>
            <button type="button" class="flavor-gc-carrito-cerrar" id="gc-carrito-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </header>

        <?php if ($ciclo_activo): ?>
            <div class="flavor-gc-carrito-ciclo">
                <span class="dashicons dashicons-calendar-alt"></span>
                <div class="flavor-gc-carrito-ciclo-info">
                    <span class="flavor-gc-ciclo-nombre"><?php echo esc_html($ciclo_activo['titulo']); ?></span>
                    <span class="flavor-gc-ciclo-cierre">
                        <?php printf(__('Cierre: %s', 'flavor-chat-ia'), date_i18n('j M, H:i', strtotime($ciclo_activo['fecha_cierre']))); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="flavor-gc-carrito-contenido" id="gc-carrito-contenido">
            <?php if (empty($items_carrito)): ?>
                <div class="flavor-gc-carrito-vacio">
                    <span class="dashicons dashicons-products"></span>
                    <p><?php _e('Tu pedido esta vacio', 'flavor-chat-ia'); ?></p>
                    <span class="flavor-gc-carrito-vacio-hint">
                        <?php _e('Anade productos desde el catalogo', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            <?php else: ?>
                <ul class="flavor-gc-carrito-items">
                    <?php foreach (array_slice($items_carrito, 0, 5) as $item): ?>
                        <li class="flavor-gc-carrito-item" data-item-id="<?php echo esc_attr($item['id']); ?>" data-producto-id="<?php echo esc_attr($item['producto_id']); ?>">
                            <div class="flavor-gc-item-imagen">
                                <?php if (!empty($item['imagen'])): ?>
                                    <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['nombre']); ?>">
                                <?php else: ?>
                                    <span class="dashicons dashicons-carrot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-gc-item-info">
                                <span class="flavor-gc-item-nombre"><?php echo esc_html($item['nombre']); ?></span>
                                <span class="flavor-gc-item-detalle">
                                    <?php echo esc_html($item['cantidad']); ?> x <?php echo number_format($item['precio'], 2, ',', '.'); ?> EUR
                                </span>
                            </div>
                            <div class="flavor-gc-item-subtotal">
                                <?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?> EUR
                            </div>
                            <button type="button" class="flavor-gc-item-eliminar" data-action="eliminar-item" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (count($items_carrito) > 5): ?>
                    <div class="flavor-gc-carrito-mas">
                        <?php printf(__('+ %d productos mas', 'flavor-chat-ia'), count($items_carrito) - 5); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($items_carrito)): ?>
            <div class="flavor-gc-carrito-resumen">
                <div class="flavor-gc-resumen-linea">
                    <span><?php _e('Subtotal', 'flavor-chat-ia'); ?></span>
                    <span class="flavor-gc-subtotal-valor" id="gc-carrito-subtotal">
                        <?php echo number_format($total_carrito, 2, ',', '.'); ?> EUR
                    </span>
                </div>

                <?php if ($porcentaje_gestion > 0): ?>
                    <div class="flavor-gc-resumen-linea flavor-gc-gestion">
                        <span>
                            <?php printf(__('Gastos gestion (%s%%)', 'flavor-chat-ia'), $porcentaje_gestion); ?>
                            <span class="dashicons dashicons-info flavor-gc-tooltip" title="<?php esc_attr_e('Porcentaje para cubrir gastos operativos del grupo', 'flavor-chat-ia'); ?>"></span>
                        </span>
                        <span class="flavor-gc-gestion-valor" id="gc-carrito-gestion">
                            <?php echo number_format($gastos_gestion, 2, ',', '.'); ?> EUR
                        </span>
                    </div>
                <?php endif; ?>

                <div class="flavor-gc-resumen-linea flavor-gc-total">
                    <strong><?php _e('Total', 'flavor-chat-ia'); ?></strong>
                    <strong class="flavor-gc-total-valor" id="gc-carrito-total">
                        <?php echo number_format($total_final, 2, ',', '.'); ?> EUR
                    </strong>
                </div>
            </div>

            <footer class="flavor-gc-carrito-panel-footer">
                <a href="<?php echo esc_url($url_carrito); ?>" class="flavor-gc-btn flavor-gc-btn-secondary">
                    <?php _e('Ver pedido completo', 'flavor-chat-ia'); ?>
                </a>
                <?php if ($ciclo_activo && $ciclo_activo['estado'] === 'abierto'): ?>
                    <button type="button" class="flavor-gc-btn flavor-gc-btn-primary" id="gc-confirmar-pedido-flotante">
                        <?php _e('Confirmar pedido', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </footer>

            <div class="flavor-gc-carrito-acciones-secundarias">
                <button type="button" class="flavor-gc-btn-text" id="gc-vaciar-carrito">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Vaciar pedido', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Overlay para cerrar el panel -->
    <div class="flavor-gc-carrito-overlay" id="gc-carrito-overlay"></div>
</div>

<!-- Template para items dinamicos (usado por JS) -->
<template id="gc-template-item-carrito">
    <li class="flavor-gc-carrito-item" data-item-id="" data-producto-id="">
        <div class="flavor-gc-item-imagen">
            <span class="dashicons dashicons-carrot"></span>
        </div>
        <div class="flavor-gc-item-info">
            <span class="flavor-gc-item-nombre"></span>
            <span class="flavor-gc-item-detalle"></span>
        </div>
        <div class="flavor-gc-item-subtotal"></div>
        <button type="button" class="flavor-gc-item-eliminar" data-action="eliminar-item">
            <span class="dashicons dashicons-no"></span>
        </button>
    </li>
</template>
