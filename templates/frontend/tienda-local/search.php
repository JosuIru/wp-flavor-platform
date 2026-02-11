<?php
/**
 * Frontend: Búsqueda de Tienda Local
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? [];
?>

<div class="flavor-frontend flavor-tienda-local-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-amber-500 to-yellow-500 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('🔍 Buscar productos', 'flavor-chat-ia'); ?></h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('¿Qué producto buscas? (ej: tomates, miel, pan...)', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-amber-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-amber-600 text-white p-3 rounded-lg hover:bg-amber-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Búsquedas populares -->
        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-amber-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia): ?>
            <a href="?q=<?php echo esc_attr($sugerencia); ?>"
               class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <!-- Resultados de búsqueda -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php if ($total_resultados > 0): ?>
                    <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                    para "<span class="text-amber-600"><?php echo esc_html($query); ?></span>"
                <?php else: ?>
                    Sin resultados para "<span class="text-amber-600"><?php echo esc_html($query); ?></span>"
                <?php endif; ?>
            </h3>

            <?php if ($total_resultados > 0): ?>
            <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500"
                    onchange="flavorTienda.ordenarResultados(this.value)">
                <option value="<?php echo esc_attr__('relevancia', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Más relevantes', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('precio_asc', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Precio: menor a mayor', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('precio_desc', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Precio: mayor a menor', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?></option>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($resultados)): ?>
    <!-- Sin resultados -->
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos productos', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Prueba con otros términos de búsqueda', 'flavor-chat-ia'); ?></p>

        <?php if (!empty($sugerencias)): ?>
        <div class="space-y-2">
            <p class="text-sm text-gray-500"><?php echo esc_html__('Quizás te interese:', 'flavor-chat-ia'); ?></p>
            <div class="flex flex-wrap justify-center gap-2">
                <?php foreach ($sugerencias as $sugerencia): ?>
                <a href="?q=<?php echo esc_attr($sugerencia); ?>"
                   class="bg-amber-100 text-amber-700 px-4 py-2 rounded-full text-sm hover:bg-amber-200 transition-colors">
                    <?php echo esc_html($sugerencia); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Grid de resultados -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($resultados as $producto): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden group border border-gray-100">
            <a href="<?php echo esc_url($producto['url']); ?>" class="block">
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
            </a>

            <div class="p-4">
                <span class="text-xs text-amber-600 font-medium uppercase tracking-wide">
                    <?php echo esc_html($producto['categoria'] ?? 'General'); ?>
                </span>
                <h3 class="font-semibold text-gray-800 mt-1 mb-2 line-clamp-2">
                    <a href="<?php echo esc_url($producto['url']); ?>" class="hover:text-amber-600 transition-colors">
                        <?php echo esc_html($producto['nombre']); ?>
                    </a>
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
                    </div>

                    <button class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl transition-colors"
                            onclick="event.preventDefault(); flavorTienda.agregarCarrito(<?php echo esc_attr($producto['id']); ?>)"
                            title="<?php echo esc_attr__('Añadir al carrito', 'flavor-chat-ia'); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_resultados > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                <?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?>
            </button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_resultados / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                <?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?>
            </button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
