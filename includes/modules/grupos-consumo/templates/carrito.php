<?php
/**
 * Template: Carrito / Lista de Compra de Grupos de Consumo
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$items_lista = $args['items'] ?? [];
$ciclo_actual = $args['ciclo'] ?? null;
$total_estimado = $args['total'] ?? 0;
?>

<div class="gc-carrito-wrapper" data-usuario="<?php echo esc_attr($usuario_id); ?>">

    <?php if (!is_user_logged_in()): ?>
        <div class="gc-carrito-login-requerido">
            <span class="dashicons dashicons-lock"></span>
            <p><?php _e('Inicia sesión para ver tu lista de compra.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="gc-btn gc-btn-primary">
                <?php _e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php elseif (empty($items_lista)): ?>
        <div class="gc-carrito-vacio">
            <span class="gc-carrito-icono-vacio">🛒</span>
            <h3><?php _e('Tu lista está vacía', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Añade productos desde el catálogo para empezar tu pedido.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('gc_producto')); ?>" class="gc-btn gc-btn-primary">
                <?php _e('Ver catálogo', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>

        <?php if ($ciclo_actual): ?>
            <div class="gc-carrito-ciclo-info">
                <span class="dashicons dashicons-calendar-alt"></span>
                <div class="gc-carrito-ciclo-texto">
                    <strong><?php echo esc_html($ciclo_actual['titulo']); ?></strong>
                    <span><?php printf(__('Cierre: %s', 'flavor-chat-ia'), esc_html($ciclo_actual['fecha_cierre'])); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="gc-carrito-lista">
            <table class="gc-carrito-tabla">
                <thead>
                    <tr>
                        <th class="gc-col-producto"><?php _e('Producto', 'flavor-chat-ia'); ?></th>
                        <th class="gc-col-precio"><?php _e('Precio', 'flavor-chat-ia'); ?></th>
                        <th class="gc-col-cantidad"><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th class="gc-col-subtotal"><?php _e('Subtotal', 'flavor-chat-ia'); ?></th>
                        <th class="gc-col-acciones"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items_lista as $item): ?>
                        <tr class="gc-carrito-item" data-id="<?php echo esc_attr($item['id']); ?>">
                            <td class="gc-col-producto">
                                <div class="gc-producto-info">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img src="<?php echo esc_url($item['imagen']); ?>"
                                             alt="<?php echo esc_attr($item['nombre']); ?>"
                                             class="gc-producto-thumb">
                                    <?php endif; ?>
                                    <div class="gc-producto-texto">
                                        <strong><?php echo esc_html($item['nombre']); ?></strong>
                                        <?php if (!empty($item['productor'])): ?>
                                            <span class="gc-productor"><?php echo esc_html($item['productor']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="gc-col-precio">
                                <span class="gc-precio"><?php echo number_format($item['precio'], 2, ',', '.'); ?> €</span>
                                <span class="gc-unidad">/<?php echo esc_html($item['unidad']); ?></span>
                            </td>
                            <td class="gc-col-cantidad">
                                <div class="gc-cantidad-control">
                                    <button type="button" class="gc-cantidad-btn gc-cantidad-menos" data-action="decrementar">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                    <input type="number"
                                           class="gc-cantidad-input"
                                           value="<?php echo esc_attr($item['cantidad']); ?>"
                                           min="<?php echo esc_attr($item['cantidad_minima'] ?? 1); ?>"
                                           step="<?php echo esc_attr($item['incremento'] ?? 1); ?>">
                                    <button type="button" class="gc-cantidad-btn gc-cantidad-mas" data-action="incrementar">
                                        <span class="dashicons dashicons-plus"></span>
                                    </button>
                                </div>
                            </td>
                            <td class="gc-col-subtotal">
                                <span class="gc-subtotal"><?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?> €</span>
                            </td>
                            <td class="gc-col-acciones">
                                <button type="button" class="gc-btn-eliminar" data-action="eliminar" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="gc-carrito-total">
                        <td colspan="3" class="gc-total-label"><?php _e('Total estimado:', 'flavor-chat-ia'); ?></td>
                        <td colspan="2" class="gc-total-valor">
                            <strong class="gc-total-amount"><?php echo number_format($total_estimado, 2, ',', '.'); ?> €</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="gc-carrito-acciones">
            <a href="<?php echo esc_url(get_post_type_archive_link('gc_producto')); ?>" class="gc-btn gc-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Seguir comprando', 'flavor-chat-ia'); ?>
            </a>

            <?php if ($ciclo_actual && $ciclo_actual['estado'] === 'abierto'): ?>
                <button type="button" class="gc-btn gc-btn-primary gc-btn-confirmar-pedido" id="gc-confirmar-pedido">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Confirmar pedido', 'flavor-chat-ia'); ?>
                </button>
            <?php else: ?>
                <div class="gc-carrito-aviso">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('No hay ciclo de pedidos abierto actualmente.', 'flavor-chat-ia'); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['notas_ciclo'])): ?>
            <div class="gc-carrito-notas">
                <h4><?php _e('Notas del ciclo:', 'flavor-chat-ia'); ?></h4>
                <p><?php echo wp_kses_post($args['notas_ciclo']); ?></p>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
