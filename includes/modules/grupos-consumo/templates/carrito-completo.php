<?php
/**
 * Template: Pagina Completa del Carrito / Pedido
 *
 * Pagina completa con todos los productos del pedido,
 * edicion de cantidades y confirmacion.
 *
 * Variables disponibles:
 * - $items_lista: Array de items en la lista
 * - $ciclo_actual: Datos del ciclo activo
 * - $total_productos: Suma de productos
 * - $porcentaje_gestion: % de gastos de gestion
 * - $notas_ciclo: Notas del ciclo actual
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
$total_productos = $args['total'] ?? 0;
$porcentaje_gestion = $args['porcentaje_gestion'] ?? 0;
$gastos_gestion = $total_productos * ($porcentaje_gestion / 100);
$total_final = $total_productos + $gastos_gestion;
$notas_ciclo = $args['notas_ciclo'] ?? '';
$url_catalogo = $args['url_catalogo'] ?? get_post_type_archive_link('gc_producto');
?>

<div class="flavor-gc-carrito-completo" data-usuario="<?php echo esc_attr($usuario_id); ?>">

    <?php if (!is_user_logged_in()): ?>
        <!-- Estado: Usuario no autenticado -->
        <div class="flavor-gc-carrito-mensaje flavor-gc-carrito-login">
            <div class="flavor-gc-mensaje-icono">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h2><?php _e('Acceso restringido', 'flavor-chat-ia'); ?></h2>
            <p><?php _e('Necesitas iniciar sesion para ver y gestionar tu pedido.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="flavor-gc-btn flavor-gc-btn-primary flavor-gc-btn-lg">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Iniciar sesion', 'flavor-chat-ia'); ?>
            </a>
        </div>

    <?php elseif (empty($items_lista)): ?>
        <!-- Estado: Carrito vacio -->
        <div class="flavor-gc-carrito-mensaje flavor-gc-carrito-vacio-estado">
            <div class="flavor-gc-mensaje-icono">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <h2><?php _e('Tu pedido esta vacio', 'flavor-chat-ia'); ?></h2>
            <p><?php _e('Todavia no has anadido ningun producto a tu pedido. Explora nuestro catalogo para empezar.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url($url_catalogo); ?>" class="flavor-gc-btn flavor-gc-btn-primary flavor-gc-btn-lg">
                <span class="dashicons dashicons-carrot"></span>
                <?php _e('Ver catalogo de productos', 'flavor-chat-ia'); ?>
            </a>
        </div>

    <?php else: ?>
        <!-- Estado: Carrito con productos -->
        <header class="flavor-gc-carrito-header">
            <div class="flavor-gc-carrito-titulo">
                <h1>
                    <span class="dashicons dashicons-cart"></span>
                    <?php _e('Mi Pedido', 'flavor-chat-ia'); ?>
                </h1>
                <span class="flavor-gc-items-count">
                    <?php printf(
                        _n('%d producto', '%d productos', count($items_lista), 'flavor-chat-ia'),
                        count($items_lista)
                    ); ?>
                </span>
            </div>

            <?php if ($ciclo_actual): ?>
                <div class="flavor-gc-carrito-ciclo-banner">
                    <div class="flavor-gc-ciclo-estado <?php echo $ciclo_actual['estado'] === 'abierto' ? 'abierto' : 'cerrado'; ?>">
                        <?php if ($ciclo_actual['estado'] === 'abierto'): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Ciclo abierto', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('Ciclo cerrado', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-gc-ciclo-detalles">
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Ciclo', 'flavor-chat-ia'); ?></span>
                                <strong><?php echo esc_html($ciclo_actual['titulo']); ?></strong>
                            </div>
                        </div>
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-clock"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Cierre de pedidos', 'flavor-chat-ia'); ?></span>
                                <strong><?php echo date_i18n('l j \d\e F, H:i', strtotime($ciclo_actual['fecha_cierre'])); ?></strong>
                            </div>
                        </div>
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-location"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Entrega', 'flavor-chat-ia'); ?></span>
                                <strong>
                                    <?php echo date_i18n('l j \d\e F', strtotime($ciclo_actual['fecha_entrega'])); ?>
                                    <?php if (!empty($ciclo_actual['lugar_entrega'])): ?>
                                        - <?php echo esc_html($ciclo_actual['lugar_entrega']); ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-gc-alerta flavor-gc-alerta-warning">
                    <span class="dashicons dashicons-warning"></span>
                    <div>
                        <strong><?php _e('No hay ciclo de pedidos activo', 'flavor-chat-ia'); ?></strong>
                        <p><?php _e('Tu lista se conserva, pero no podras confirmar el pedido hasta que se abra un nuevo ciclo.', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <div class="flavor-gc-carrito-contenido">
            <!-- Tabla de productos -->
            <div class="flavor-gc-carrito-tabla-wrapper">
                <table class="flavor-gc-carrito-tabla">
                    <thead>
                        <tr>
                            <th class="flavor-gc-col-producto"><?php _e('Producto', 'flavor-chat-ia'); ?></th>
                            <th class="flavor-gc-col-productor"><?php _e('Productor', 'flavor-chat-ia'); ?></th>
                            <th class="flavor-gc-col-precio"><?php _e('Precio/ud', 'flavor-chat-ia'); ?></th>
                            <th class="flavor-gc-col-cantidad"><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                            <th class="flavor-gc-col-subtotal"><?php _e('Subtotal', 'flavor-chat-ia'); ?></th>
                            <th class="flavor-gc-col-acciones"></th>
                        </tr>
                    </thead>
                    <tbody id="gc-carrito-items">
                        <?php foreach ($items_lista as $item): ?>
                            <?php
                            $subtotal_item = $item['precio'] * $item['cantidad'];
                            $cantidad_minima = $item['cantidad_minima'] ?? 1;
                            $stock_disponible = $item['stock'] ?? 999;
                            ?>
                            <tr class="flavor-gc-carrito-fila"
                                data-item-id="<?php echo esc_attr($item['id']); ?>"
                                data-producto-id="<?php echo esc_attr($item['producto_id']); ?>"
                                data-precio="<?php echo esc_attr($item['precio']); ?>">

                                <td class="flavor-gc-col-producto">
                                    <div class="flavor-gc-producto-info">
                                        <div class="flavor-gc-producto-imagen">
                                            <?php if (!empty($item['imagen'])): ?>
                                                <img src="<?php echo esc_url($item['imagen']); ?>"
                                                     alt="<?php echo esc_attr($item['nombre']); ?>">
                                            <?php else: ?>
                                                <span class="dashicons dashicons-carrot"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flavor-gc-producto-texto">
                                            <a href="<?php echo esc_url(get_permalink($item['producto_id'])); ?>" class="flavor-gc-producto-nombre">
                                                <?php echo esc_html($item['nombre']); ?>
                                            </a>
                                            <?php if (!empty($item['es_ecologico'])): ?>
                                                <span class="flavor-gc-badge-eco-mini"><?php echo esc_html__('ECO', 'flavor-chat-ia'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="flavor-gc-col-productor" data-label="<?php esc_attr_e('Productor', 'flavor-chat-ia'); ?>">
                                    <?php if (!empty($item['productor'])): ?>
                                        <span class="flavor-gc-productor-nombre"><?php echo esc_html($item['productor']); ?></span>
                                    <?php else: ?>
                                        <span class="flavor-gc-sin-productor">-</span>
                                    <?php endif; ?>
                                </td>

                                <td class="flavor-gc-col-precio" data-label="<?php esc_attr_e('Precio', 'flavor-chat-ia'); ?>">
                                    <span class="flavor-gc-precio-valor"><?php echo number_format($item['precio'], 2, ',', '.'); ?></span>
                                    <span class="flavor-gc-precio-moneda"><?php echo esc_html__('EUR', 'flavor-chat-ia'); ?></span>
                                    <span class="flavor-gc-precio-unidad">/<?php echo esc_html($item['unidad'] ?? 'ud'); ?></span>
                                </td>

                                <td class="flavor-gc-col-cantidad" data-label="<?php esc_attr_e('Cantidad', 'flavor-chat-ia'); ?>">
                                    <div class="flavor-gc-cantidad-control">
                                        <button type="button"
                                                class="flavor-gc-cantidad-btn flavor-gc-cantidad-menos"
                                                data-action="decrementar"
                                                <?php echo $item['cantidad'] <= $cantidad_minima ? 'disabled' : ''; ?>>
                                            <span class="dashicons dashicons-minus"></span>
                                        </button>
                                        <input type="number"
                                               class="flavor-gc-cantidad-input"
                                               value="<?php echo esc_attr($item['cantidad']); ?>"
                                               min="<?php echo esc_attr($cantidad_minima); ?>"
                                               max="<?php echo esc_attr($stock_disponible); ?>"
                                               step="1"
                                               aria-label="<?php esc_attr_e('Cantidad', 'flavor-chat-ia'); ?>">
                                        <button type="button"
                                                class="flavor-gc-cantidad-btn flavor-gc-cantidad-mas"
                                                data-action="incrementar"
                                                <?php echo $item['cantidad'] >= $stock_disponible ? 'disabled' : ''; ?>>
                                            <span class="dashicons dashicons-plus"></span>
                                        </button>
                                    </div>
                                </td>

                                <td class="flavor-gc-col-subtotal" data-label="<?php esc_attr_e('Subtotal', 'flavor-chat-ia'); ?>">
                                    <span class="flavor-gc-subtotal-valor"><?php echo number_format($subtotal_item, 2, ',', '.'); ?></span>
                                    <span class="flavor-gc-subtotal-moneda"><?php echo esc_html__('EUR', 'flavor-chat-ia'); ?></span>
                                </td>

                                <td class="flavor-gc-col-acciones">
                                    <button type="button"
                                            class="flavor-gc-btn-eliminar"
                                            data-action="eliminar"
                                            title="<?php esc_attr_e('Eliminar producto', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen del pedido -->
            <aside class="flavor-gc-carrito-resumen">
                <div class="flavor-gc-resumen-card">
                    <h3><?php _e('Resumen del pedido', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-gc-resumen-lineas">
                        <div class="flavor-gc-resumen-linea">
                            <span><?php _e('Subtotal productos', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-gc-valor" id="gc-subtotal-productos">
                                <?php echo number_format($total_productos, 2, ',', '.'); ?> EUR
                            </span>
                        </div>

                        <?php if ($porcentaje_gestion > 0): ?>
                            <div class="flavor-gc-resumen-linea flavor-gc-linea-gestion">
                                <span>
                                    <?php printf(__('Gastos de gestion (%s%%)', 'flavor-chat-ia'), number_format($porcentaje_gestion, 1)); ?>
                                    <button type="button" class="flavor-gc-info-btn" id="gc-info-gestion" aria-label="<?php esc_attr_e('Info gastos gestion', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-info-outline"></span>
                                    </button>
                                </span>
                                <span class="flavor-gc-valor" id="gc-gastos-gestion">
                                    <?php echo number_format($gastos_gestion, 2, ',', '.'); ?> EUR
                                </span>
                            </div>
                            <div class="flavor-gc-info-gestion-detalle" id="gc-gestion-detalle" style="display:none;">
                                <p><?php _e('Este porcentaje cubre los gastos operativos del grupo: coordinacion, local de reparto, materiales, etc.', 'flavor-chat-ia'); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-gc-resumen-linea flavor-gc-linea-total">
                            <strong><?php _e('Total del pedido', 'flavor-chat-ia'); ?></strong>
                            <strong class="flavor-gc-total-valor" id="gc-total-pedido">
                                <?php echo number_format($total_final, 2, ',', '.'); ?> EUR
                            </strong>
                        </div>
                    </div>

                    <?php if ($ciclo_actual && $ciclo_actual['estado'] === 'abierto'): ?>
                        <div class="flavor-gc-resumen-acciones">
                            <button type="button"
                                    class="flavor-gc-btn flavor-gc-btn-primary flavor-gc-btn-lg flavor-gc-btn-block"
                                    id="gc-confirmar-pedido">
                                <span class="flavor-gc-btn-icono">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                                <span class="flavor-gc-btn-texto"><?php _e('Confirmar pedido', 'flavor-chat-ia'); ?></span>
                                <span class="flavor-gc-btn-loading" style="display:none;">
                                    <span class="dashicons dashicons-update-alt flavor-spin"></span>
                                </span>
                            </button>
                            <p class="flavor-gc-resumen-nota">
                                <?php _e('Al confirmar, tu pedido sera procesado para la proxima entrega.', 'flavor-chat-ia'); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="flavor-gc-resumen-bloqueado">
                            <span class="dashicons dashicons-lock"></span>
                            <p><?php _e('Espera a que se abra un nuevo ciclo para confirmar tu pedido.', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones secundarias -->
                <div class="flavor-gc-acciones-secundarias">
                    <a href="<?php echo esc_url($url_catalogo); ?>" class="flavor-gc-btn flavor-gc-btn-secondary flavor-gc-btn-block">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Seguir comprando', 'flavor-chat-ia'); ?>
                    </a>
                    <button type="button" class="flavor-gc-btn flavor-gc-btn-text flavor-gc-btn-block" id="gc-vaciar-pedido">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Vaciar pedido', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </aside>
        </div>

        <?php if (!empty($notas_ciclo)): ?>
            <div class="flavor-gc-carrito-notas">
                <h4>
                    <span class="dashicons dashicons-format-aside"></span>
                    <?php _e('Notas del ciclo', 'flavor-chat-ia'); ?>
                </h4>
                <div class="flavor-gc-notas-contenido">
                    <?php echo wp_kses_post($notas_ciclo); ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Modal de confirmacion -->
<div class="flavor-gc-modal" id="gc-modal-confirmar" style="display:none;">
    <div class="flavor-gc-modal-overlay"></div>
    <div class="flavor-gc-modal-contenido">
        <button type="button" class="flavor-gc-modal-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
        <div class="flavor-gc-modal-header">
            <span class="dashicons dashicons-yes-alt"></span>
            <h3><?php _e('Confirmar pedido', 'flavor-chat-ia'); ?></h3>
        </div>
        <div class="flavor-gc-modal-body">
            <p><?php _e('Estas a punto de confirmar tu pedido. Una vez confirmado:', 'flavor-chat-ia'); ?></p>
            <ul>
                <li><?php _e('Recibiras un email de confirmacion', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Tu pedido se agregara al consolidado del ciclo', 'flavor-chat-ia'); ?></li>
                <li><?php _e('Podras recogerlo en la fecha y lugar indicados', 'flavor-chat-ia'); ?></li>
            </ul>
            <div class="flavor-gc-modal-resumen">
                <span><?php _e('Total a pagar:', 'flavor-chat-ia'); ?></span>
                <strong id="gc-modal-total"><?php echo number_format($total_final, 2, ',', '.'); ?> EUR</strong>
            </div>
        </div>
        <div class="flavor-gc-modal-footer">
            <button type="button" class="flavor-gc-btn flavor-gc-btn-secondary" id="gc-modal-cancelar">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-gc-btn flavor-gc-btn-primary" id="gc-modal-confirmar-btn">
                <span class="flavor-gc-btn-texto"><?php _e('Si, confirmar pedido', 'flavor-chat-ia'); ?></span>
                <span class="flavor-gc-btn-loading" style="display:none;">
                    <span class="dashicons dashicons-update-alt flavor-spin"></span>
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Modal de pedido confirmado -->
<div class="flavor-gc-modal flavor-gc-modal-exito" id="gc-modal-exito" style="display:none;">
    <div class="flavor-gc-modal-overlay"></div>
    <div class="flavor-gc-modal-contenido">
        <div class="flavor-gc-modal-header flavor-gc-exito">
            <span class="dashicons dashicons-yes"></span>
            <h3><?php _e('Pedido confirmado', 'flavor-chat-ia'); ?></h3>
        </div>
        <div class="flavor-gc-modal-body">
            <p><?php _e('Tu pedido ha sido confirmado correctamente.', 'flavor-chat-ia'); ?></p>
            <p class="flavor-gc-modal-info">
                <?php _e('Hemos enviado un email con los detalles del pedido. Podras consultarlo en cualquier momento desde tu cuenta.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <div class="flavor-gc-modal-footer">
            <a href="<?php echo esc_url(home_url('/mi-cuenta/?tab=gc-mis-pedidos')); ?>" class="flavor-gc-btn flavor-gc-btn-primary">
                <?php _e('Ver mis pedidos', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</div>
