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
 * @package FlavorPlatform
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
$url_catalogo = $args['url_catalogo'] ?? Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos');
?>

<div class="flavor-gc-carrito-completo" data-usuario="<?php echo esc_attr($usuario_id); ?>">

    <?php if (!is_user_logged_in()): ?>
        <!-- Estado: Usuario no autenticado -->
        <div class="flavor-gc-carrito-mensaje flavor-gc-carrito-login">
            <div class="flavor-gc-mensaje-icono">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h2><?php _e('Acceso restringido', 'flavor-platform'); ?></h2>
            <p><?php _e('Necesitas iniciar sesion para ver y gestionar tu pedido.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'mi-pedido'))); ?>" class="flavor-gc-btn flavor-gc-btn-primary flavor-gc-btn-lg">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Iniciar sesion', 'flavor-platform'); ?>
            </a>
        </div>

    <?php elseif (empty($items_lista)): ?>
        <!-- Estado: Carrito vacio -->
        <div class="flavor-gc-carrito-mensaje flavor-gc-carrito-vacio-estado">
            <div class="flavor-gc-mensaje-icono">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <h2><?php _e('Tu pedido esta vacio', 'flavor-platform'); ?></h2>
            <p><?php _e('Todavia no has anadido ningun producto a tu pedido. Explora nuestro catalogo para empezar.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url($url_catalogo); ?>" class="flavor-gc-btn flavor-gc-btn-primary flavor-gc-btn-lg">
                <span class="dashicons dashicons-carrot"></span>
                <?php _e('Ver catalogo de productos', 'flavor-platform'); ?>
            </a>
        </div>

    <?php else: ?>
        <!-- Estado: Carrito con productos -->
        <header class="flavor-gc-carrito-header">
            <div class="flavor-gc-carrito-titulo">
                <h1>
                    <span class="dashicons dashicons-cart"></span>
                    <?php _e('Mi Pedido', 'flavor-platform'); ?>
                </h1>
                <span class="flavor-gc-items-count">
                    <?php printf(
                        _n('%d producto', '%d productos', count($items_lista), 'flavor-platform'),
                        count($items_lista)
                    ); ?>
                </span>
            </div>

            <?php if ($ciclo_actual): ?>
                <div class="flavor-gc-carrito-ciclo-banner">
                    <div class="flavor-gc-ciclo-estado <?php echo $ciclo_actual['estado'] === 'abierto' ? 'abierto' : 'cerrado'; ?>">
                        <?php if ($ciclo_actual['estado'] === 'abierto'): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Ciclo abierto', 'flavor-platform'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e('Ciclo cerrado', 'flavor-platform'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-gc-ciclo-detalles">
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Ciclo', 'flavor-platform'); ?></span>
                                <strong><?php echo esc_html($ciclo_actual['titulo']); ?></strong>
                            </div>
                        </div>
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-clock"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Cierre de pedidos', 'flavor-platform'); ?></span>
                                <strong><?php echo date_i18n('l j \d\e F, H:i', strtotime($ciclo_actual['fecha_cierre'])); ?></strong>
                            </div>
                        </div>
                        <div class="flavor-gc-ciclo-detalle">
                            <span class="dashicons dashicons-location"></span>
                            <div>
                                <span class="flavor-gc-label"><?php _e('Entrega', 'flavor-platform'); ?></span>
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
                        <strong><?php _e('No hay ciclo de pedidos activo', 'flavor-platform'); ?></strong>
                        <p><?php _e('Tu lista se conserva, pero no podras confirmar el pedido hasta que se abra un nuevo ciclo.', 'flavor-platform'); ?></p>
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
                            <th class="flavor-gc-col-producto"><?php _e('Producto', 'flavor-platform'); ?></th>
                            <th class="flavor-gc-col-productor"><?php _e('Productor', 'flavor-platform'); ?></th>
                            <th class="flavor-gc-col-precio"><?php _e('Precio/ud', 'flavor-platform'); ?></th>
                            <th class="flavor-gc-col-cantidad"><?php _e('Cantidad', 'flavor-platform'); ?></th>
                            <th class="flavor-gc-col-subtotal"><?php _e('Subtotal', 'flavor-platform'); ?></th>
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
                                            <a href="<?php echo esc_url(add_query_arg('product', intval($item['producto_id']), Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos'))); ?>" class="flavor-gc-producto-nombre">
                                                <?php echo esc_html($item['nombre']); ?>
                                            </a>
                                            <?php if (!empty($item['es_ecologico'])): ?>
                                                <span class="flavor-gc-badge-eco-mini"><?php echo esc_html__('ECO', 'flavor-platform'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="flavor-gc-col-productor" data-label="<?php esc_attr_e('Productor', 'flavor-platform'); ?>">
                                    <?php if (!empty($item['productor'])): ?>
                                        <span class="flavor-gc-productor-nombre"><?php echo esc_html($item['productor']); ?></span>
                                    <?php else: ?>
                                        <span class="flavor-gc-sin-productor">-</span>
                                    <?php endif; ?>
                                </td>

                                <td class="flavor-gc-col-precio" data-label="<?php esc_attr_e('Precio', 'flavor-platform'); ?>">
                                    <span class="flavor-gc-precio-valor"><?php echo number_format($item['precio'], 2, ',', '.'); ?></span>
                                    <span class="flavor-gc-precio-moneda"><?php echo esc_html__('EUR', 'flavor-platform'); ?></span>
                                    <span class="flavor-gc-precio-unidad">/<?php echo esc_html($item['unidad'] ?? 'ud'); ?></span>
                                </td>

                                <td class="flavor-gc-col-cantidad" data-label="<?php esc_attr_e('Cantidad', 'flavor-platform'); ?>">
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
                                               aria-label="<?php esc_attr_e('Cantidad', 'flavor-platform'); ?>">
                                        <button type="button"
                                                class="flavor-gc-cantidad-btn flavor-gc-cantidad-mas"
                                                data-action="incrementar"
                                                <?php echo $item['cantidad'] >= $stock_disponible ? 'disabled' : ''; ?>>
                                            <span class="dashicons dashicons-plus"></span>
                                        </button>
                                    </div>
                                </td>

                                <td class="flavor-gc-col-subtotal" data-label="<?php esc_attr_e('Subtotal', 'flavor-platform'); ?>">
                                    <span class="flavor-gc-subtotal-valor"><?php echo number_format($subtotal_item, 2, ',', '.'); ?></span>
                                    <span class="flavor-gc-subtotal-moneda"><?php echo esc_html__('EUR', 'flavor-platform'); ?></span>
                                </td>

                                <td class="flavor-gc-col-acciones">
                                    <button type="button"
                                            class="flavor-gc-btn-eliminar"
                                            data-action="eliminar"
                                            title="<?php esc_attr_e('Eliminar producto', 'flavor-platform'); ?>">
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
                    <h3><?php _e('Resumen del pedido', 'flavor-platform'); ?></h3>

                    <div class="flavor-gc-resumen-lineas">
                        <div class="flavor-gc-resumen-linea">
                            <span><?php _e('Subtotal productos', 'flavor-platform'); ?></span>
                            <span class="flavor-gc-valor" id="gc-subtotal-productos">
                                <?php echo number_format($total_productos, 2, ',', '.'); ?> EUR
                            </span>
                        </div>

                        <?php if ($porcentaje_gestion > 0): ?>
                            <div class="flavor-gc-resumen-linea flavor-gc-linea-gestion">
                                <span>
                                    <?php printf(__('Gastos de gestion (%s%%)', 'flavor-platform'), number_format($porcentaje_gestion, 1)); ?>
                                    <button type="button" class="flavor-gc-info-btn" id="gc-info-gestion" aria-label="<?php esc_attr_e('Info gastos gestion', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-info-outline"></span>
                                    </button>
                                </span>
                                <span class="flavor-gc-valor" id="gc-gastos-gestion">
                                    <?php echo number_format($gastos_gestion, 2, ',', '.'); ?> EUR
                                </span>
                            </div>
                            <div class="flavor-gc-info-gestion-detalle" id="gc-gestion-detalle" style="display:none;">
                                <p><?php _e('Este porcentaje cubre los gastos operativos del grupo: coordinacion, local de reparto, materiales, etc.', 'flavor-platform'); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-gc-resumen-linea flavor-gc-linea-total">
                            <strong><?php _e('Total del pedido', 'flavor-platform'); ?></strong>
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
                                <span class="flavor-gc-btn-texto"><?php _e('Confirmar pedido', 'flavor-platform'); ?></span>
                                <span class="flavor-gc-btn-loading" style="display:none;">
                                    <span class="dashicons dashicons-update-alt flavor-spin"></span>
                                </span>
                            </button>
                            <p class="flavor-gc-resumen-nota">
                                <?php _e('Al confirmar, tu pedido sera procesado para la proxima entrega.', 'flavor-platform'); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="flavor-gc-resumen-bloqueado">
                            <span class="dashicons dashicons-lock"></span>
                            <p><?php _e('Espera a que se abra un nuevo ciclo para confirmar tu pedido.', 'flavor-platform'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones secundarias -->
                <div class="flavor-gc-acciones-secundarias">
                    <a href="<?php echo esc_url($url_catalogo); ?>" class="flavor-gc-btn flavor-gc-btn-secondary flavor-gc-btn-block">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Seguir comprando', 'flavor-platform'); ?>
                    </a>
                    <button type="button" class="flavor-gc-btn flavor-gc-btn-text flavor-gc-btn-block" id="gc-vaciar-pedido">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Vaciar pedido', 'flavor-platform'); ?>
                    </button>
                </div>
            </aside>
        </div>

        <?php if (!empty($notas_ciclo)): ?>
            <div class="flavor-gc-carrito-notas">
                <h4>
                    <span class="dashicons dashicons-format-aside"></span>
                    <?php _e('Notas del ciclo', 'flavor-platform'); ?>
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
        <button type="button" class="flavor-gc-modal-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-platform'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
        <div class="flavor-gc-modal-header">
            <span class="dashicons dashicons-yes-alt"></span>
            <h3><?php _e('Confirmar pedido', 'flavor-platform'); ?></h3>
        </div>
        <div class="flavor-gc-modal-body">
            <p><?php _e('Estas a punto de confirmar tu pedido. Una vez confirmado:', 'flavor-platform'); ?></p>
            <ul>
                <li><?php _e('Recibiras un email de confirmacion', 'flavor-platform'); ?></li>
                <li><?php _e('Tu pedido se agregara al consolidado del ciclo', 'flavor-platform'); ?></li>
                <li><?php _e('Podras recogerlo en la fecha y lugar indicados', 'flavor-platform'); ?></li>
            </ul>
            <div class="flavor-gc-modal-resumen">
                <span><?php _e('Total a pagar:', 'flavor-platform'); ?></span>
                <strong id="gc-modal-total"><?php echo number_format($total_final, 2, ',', '.'); ?> EUR</strong>
            </div>
        </div>
        <div class="flavor-gc-modal-footer">
            <button type="button" class="flavor-gc-btn flavor-gc-btn-secondary" id="gc-modal-cancelar">
                <?php _e('Cancelar', 'flavor-platform'); ?>
            </button>
            <button type="button" class="flavor-gc-btn flavor-gc-btn-primary" id="gc-modal-confirmar-btn">
                <span class="flavor-gc-btn-texto"><?php _e('Si, confirmar pedido', 'flavor-platform'); ?></span>
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
            <h3><?php _e('Pedido confirmado', 'flavor-platform'); ?></h3>
        </div>
        <div class="flavor-gc-modal-body">
            <p><?php _e('Tu pedido ha sido confirmado correctamente.', 'flavor-platform'); ?></p>
            <p class="flavor-gc-modal-info">
                <?php _e('Hemos enviado un email con los detalles del pedido. Podras consultarlo en cualquier momento desde tu cuenta.', 'flavor-platform'); ?>
            </p>
        </div>
        <div class="flavor-gc-modal-footer">
            <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos-consumo', 'mis-pedidos')); ?>" class="flavor-gc-btn flavor-gc-btn-primary">
                <?php _e('Ver mis pedidos', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</div>
