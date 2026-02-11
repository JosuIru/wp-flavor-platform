<?php
/**
 * Template: WooCommerce Mini Carrito
 * Muestra un mini carrito con items, total y botones de accion
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_carrito = $titulo_carrito ?? 'Tu Carrito';
$url_carrito = $url_carrito ?? '#carrito';
$url_checkout = $url_checkout ?? '#checkout';
$mostrar_contador_items = $mostrar_contador_items ?? true;

$items_carrito = $items_carrito ?? [
    [
        'nombre_producto'   => 'Camiseta Premium Negra',
        'precio_unitario'   => '29.99',
        'cantidad'          => 2,
        'precio_total'      => '59.98',
        'imagen_url'        => '',
        'gradiente'         => 'from-slate-400 to-slate-500',
        'sku'               => 'CAMISETA-NEG-001',
    ],
    [
        'nombre_producto'   => 'Auriculares Bluetooth Pro',
        'precio_unitario'   => '89.99',
        'cantidad'          => 1,
        'precio_total'      => '89.99',
        'imagen_url'        => '',
        'gradiente'         => 'from-blue-400 to-blue-500',
        'sku'               => 'AURI-BLUE-001',
    ],
    [
        'nombre_producto'   => 'Mochila Urbana Gris',
        'precio_unitario'   => '45.00',
        'cantidad'          => 1,
        'precio_total'      => '45.00',
        'imagen_url'        => '',
        'gradiente'         => 'from-gray-400 to-gray-500',
        'sku'               => 'MOCHILA-GRI-001',
    ],
];

$subtotal_carrito = $subtotal_carrito ?? '194.97';
$descuento_carrito = $descuento_carrito ?? '0.00';
$gastos_envio = $gastos_envio ?? '0.00';
$total_carrito = $total_carrito ?? '194.97';
$hay_cupones = $hay_cupones ?? false;
$envio_gratis_desde = $envio_gratis_desde ?? '50.00';

// Calcular si aplica envio gratis
$aplica_envio_gratis = floatval($subtotal_carrito) >= floatval($envio_gratis_desde);
?>

<div class="flavor-component flavor-mini-carrito">
    <!-- Encabezado del mini carrito -->
    <div class="border-b border-gray-200 pb-4 mb-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <?php echo esc_html($titulo_carrito); ?>
            </h3>
            <?php if ($mostrar_contador_items) : ?>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-500 text-white text-xs font-bold">
                    <?php
                    $total_items_carrito = array_sum(array_column($items_carrito, 'cantidad'));
                    echo esc_html($total_items_carrito);
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de productos en el carrito -->
    <div class="space-y-3 max-h-96 overflow-y-auto mb-4 pr-2">
        <?php if (!empty($items_carrito)) : ?>
            <?php foreach ($items_carrito as $item_carrito) : ?>
                <div class="flavor-item-carrito flex gap-3 pb-3 border-b border-gray-100 hover:bg-gray-50 -mx-2 px-2 py-1 rounded transition-colors">

                    <!-- Miniatura del producto -->
                    <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br <?php echo esc_attr($item_carrito['gradiente']); ?> flex items-center justify-center">
                        <?php if (!empty($item_carrito['imagen_url'])) : ?>
                            <img src="<?php echo esc_url($item_carrito['imagen_url']); ?>"
                                 alt="<?php echo esc_attr($item_carrito['nombre_producto']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else : ?>
                            <svg class="w-8 h-8 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <!-- Informacion del producto -->
                    <div class="flex-grow min-w-0">
                        <h4 class="text-sm font-semibold text-gray-800 mb-1 truncate">
                            <?php echo esc_html($item_carrito['nombre_producto']); ?>
                        </h4>
                        <p class="text-xs text-gray-500 mb-2">
                            <?php echo esc_html__('SKU:', 'flavor-chat-ia'); ?>&nbsp;<?php echo esc_html($item_carrito['sku']); ?>
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-600">
                                <?php echo esc_html($item_carrito['cantidad']); ?>&times; <?php echo esc_html($item_carrito['precio_unitario']); ?>&euro;
                            </span>
                            <span class="text-sm font-bold text-purple-600">
                                <?php echo esc_html($item_carrito['precio_total']); ?>&euro;
                            </span>
                        </div>
                    </div>

                    <!-- Botones de accion rapida -->
                    <div class="flex flex-col gap-1 flex-shrink-0">
                        <button class="p-1 rounded hover:bg-gray-200 text-gray-400 hover:text-gray-600 transition-colors" title="<?php echo esc_attr__('Editar cantidad', 'flavor-chat-ia'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors" title="<?php echo esc_attr__('Eliminar', 'flavor-chat-ia'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Carrito vacio -->
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <p class="text-gray-500 text-sm"><?php echo esc_html__('Tu carrito esta vacio', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($items_carrito)) : ?>
        <!-- Informacion de envio -->
        <?php if (!$aplica_envio_gratis && floatval($gastos_envio) > 0) : ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-xs">
                <p class="text-blue-800">
                    <strong><?php echo esc_html__('Envio Gratuito', 'flavor-chat-ia'); ?></strong>
                    <?php
                    $cantidad_faltante = floatval($envio_gratis_desde) - floatval($subtotal_carrito);
                    echo esc_html(sprintf(__('Falta %s€ para acceder a envio gratis', 'flavor-chat-ia'), number_format($cantidad_faltante, 2, ',', '.')));
                    ?>
                </p>
            </div>
        <?php elseif ($aplica_envio_gratis) : ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-xs">
                <p class="text-green-800 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <strong><?php echo esc_html__('Envio Gratuito Aplicado', 'flavor-chat-ia'); ?></strong>
                </p>
            </div>
        <?php endif; ?>

        <!-- Seccion de cupones (si esta habilitada) -->
        <?php if ($hay_cupones) : ?>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <details class="group">
                    <summary class="cursor-pointer text-sm font-medium text-purple-600 hover:text-purple-700 flex items-center gap-1 select-none">
                        <svg class="w-4 h-4 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <?php echo esc_html__('Usar Cupón', 'flavor-chat-ia'); ?>
                    </summary>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <input type="text" placeholder="<?php echo esc_attr__('Ingresa tu cupón aqui', 'flavor-chat-ia'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 mb-2">
                        <button class="w-full px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            <?php echo esc_html__('Aplicar Cupón', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </details>
            </div>
        <?php endif; ?>

        <!-- Resumen de precios -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2 mb-4 border border-gray-200">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600"><?php echo esc_html__('Subtotal', 'flavor-chat-ia'); ?></span>
                <span class="font-medium text-gray-800"><?php echo esc_html($subtotal_carrito); ?>&euro;</span>
            </div>

            <?php if (floatval($descuento_carrito) > 0) : ?>
                <div class="flex justify-between text-sm text-green-600">
                    <span><?php echo esc_html__('Descuento', 'flavor-chat-ia'); ?></span>
                    <span class="font-medium">-<?php echo esc_html($descuento_carrito); ?>&euro;</span>
                </div>
            <?php endif; ?>

            <?php if ($aplica_envio_gratis) : ?>
                <div class="flex justify-between text-sm text-green-600">
                    <span><?php echo esc_html__('Envio', 'flavor-chat-ia'); ?></span>
                    <span class="font-medium"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                </div>
            <?php elseif (floatval($gastos_envio) > 0) : ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600"><?php echo esc_html__('Envio', 'flavor-chat-ia'); ?></span>
                    <span class="font-medium text-gray-800">+<?php echo esc_html($gastos_envio); ?>&euro;</span>
                </div>
            <?php endif; ?>

            <div class="border-t border-gray-300 pt-2 mt-2 flex justify-between">
                <span class="font-bold text-gray-800"><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></span>
                <span class="text-xl font-bold text-purple-600"><?php echo esc_html($total_carrito); ?>&euro;</span>
            </div>
        </div>

        <!-- Botones de accion -->
        <div class="space-y-2">
            <a href="<?php echo esc_url($url_checkout); ?>" class="block w-full px-4 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold text-center hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <?php echo esc_html__('Proceder al Pago', 'flavor-chat-ia'); ?>
                </span>
            </a>

            <a href="<?php echo esc_url($url_carrito); ?>" class="block w-full px-4 py-3 rounded-xl border-2 border-purple-600 text-purple-600 font-semibold text-center hover:bg-purple-50 transition-colors">
                <?php echo esc_html__('Ver Carrito Completo', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Nota de seguridad -->
        <div class="mt-4 pt-4 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 111.414 1.414L7.414 9l3.293 3.293a1 1 0 01-1.414 1.414l-4-4z" clip-rule="evenodd"/>
                </svg>
                <?php echo esc_html__('Pago 100% Seguro con SSL', 'flavor-chat-ia'); ?>
            </p>
        </div>

    <?php endif; ?>
</div>
