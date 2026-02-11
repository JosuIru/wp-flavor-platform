<?php
/**
 * Frontend: Archive de Tienda Local
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$productos = $productos ?? [];
$categorias = $categorias ?? [];
$total_productos = $total_productos ?? 0;
?>

<div class="flavor-frontend flavor-tienda-local-archive">
    <!-- Header con gradiente amber -->
    <div class="bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('🛒 Tienda Local', 'flavor-chat-ia'); ?></h1>
                <p class="text-amber-100"><?php echo esc_html__('Productos locales y de proximidad del barrio', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_productos); ?> productos disponibles
                </span>
                <button class="bg-white text-amber-600 px-6 py-3 rounded-xl font-semibold hover:bg-amber-50 transition-all shadow-md"
                        onclick="flavorTienda.abrirCarrito()">
                    <?php echo esc_html__('🛍️ Ver carrito', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros rápidos por categoría -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-amber-100 text-amber-700 font-medium hover:bg-amber-200 transition-colors filter-active"
                data-categoria="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($categorias as $categoria): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-categoria="<?php echo esc_attr($categoria['slug']); ?>">
            <?php echo esc_html($categoria['nombre']); ?>
            <span class="ml-1 text-xs">(<?php echo esc_html($categoria['count']); ?>)</span>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de productos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (empty($productos)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🏪</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay productos disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500"><?php echo esc_html__('Vuelve pronto para ver las novedades de la tienda local', 'flavor-chat-ia'); ?></p>
        </div>
        <?php else: ?>
        <?php foreach ($productos as $producto): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden group border border-gray-100">
            <!-- Imagen del producto -->
            <div class="relative aspect-square overflow-hidden bg-gray-100">
                <?php if (!empty($producto['imagen'])): ?>
                <img src="<?php echo esc_url($producto['imagen']); ?>"
                     alt="<?php echo esc_attr($producto['nombre']); ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-6xl bg-amber-50">🥬</div>
                <?php endif; ?>

                <?php if (!empty($producto['oferta'])): ?>
                <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                    <?php echo esc_html__('OFERTA', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>

                <?php if (!empty($producto['ecologico'])): ?>
                <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                    <?php echo esc_html__('🌱 ECO', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Info del producto -->
            <div class="p-4">
                <span class="text-xs text-amber-600 font-medium uppercase tracking-wide">
                    <?php echo esc_html($producto['categoria'] ?? 'General'); ?>
                </span>
                <h3 class="font-semibold text-gray-800 mt-1 mb-2 line-clamp-2">
                    <?php echo esc_html($producto['nombre']); ?>
                </h3>

                <?php if (!empty($producto['productor'])): ?>
                <p class="text-sm text-gray-500 mb-3">
                    <?php echo esc_html__('Por', 'flavor-chat-ia'); ?> <span class="text-amber-600"><?php echo esc_html($producto['productor']); ?></span>
                </p>
                <?php endif; ?>

                <div class="flex items-center justify-between">
                    <div>
                        <?php if (!empty($producto['precio_oferta'])): ?>
                        <span class="text-lg font-bold text-red-600"><?php echo esc_html($producto['precio_oferta']); ?>€</span>
                        <span class="text-sm text-gray-400 line-through ml-1"><?php echo esc_html($producto['precio']); ?>€</span>
                        <?php else: ?>
                        <span class="text-lg font-bold text-gray-800"><?php echo esc_html($producto['precio']); ?>€</span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500">/<?php echo esc_html($producto['unidad'] ?? 'ud'); ?></span>
                    </div>

                    <button class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl transition-colors"
                            onclick="flavorTienda.agregarCarrito(<?php echo esc_attr($producto['id']); ?>)"
                            title="<?php echo esc_attr__('Añadir al carrito', 'flavor-chat-ia'); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                </div>

                <?php if (isset($producto['stock']) && $producto['stock'] <= 5): ?>
                <p class="text-xs text-orange-600 mt-2">
                    ⚠️ Solo quedan <?php echo esc_html($producto['stock']); ?> unidades
                </p>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_productos > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                <?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?>
            </button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_productos / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                <?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?>
            </button>
        </nav>
    </div>
    <?php endif; ?>
</div>
