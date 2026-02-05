<?php
/**
 * Frontend: Busqueda de Marketplace
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['muebles', 'bicicleta', 'libros', 'ropa', 'electronica'];
?>

<div class="flavor-frontend flavor-marketplace-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-lime-500 to-green-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar productos</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="¿Que producto estas buscando? (ej: bicicleta, muebles, movil...)"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-lime-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-lime-100 text-sm">Populares:</span>
            <?php foreach ($sugerencias as $sugerencia): ?>
            <a href="?q=<?php echo esc_attr($sugerencia); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> producto<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-green-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-green-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos productos</h3>
        <p class="text-gray-500 mb-6">¿Tienes algo que vender? ¡Publica tu anuncio!</p>
        <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                onclick="flavorMarketplace.publicarAnuncio()">
            Publicar Anuncio
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $producto_resultado): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($producto_resultado['imagen'])): ?>
                <img src="<?php echo esc_url($producto_resultado['imagen']); ?>" alt="<?php echo esc_attr($producto_resultado['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">📷</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 right-3 bg-green-500 text-white font-bold px-3 py-1 rounded-full text-sm shadow">
                    <?php echo esc_html($producto_resultado['precio'] ?? '0'); ?> €
                </span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($producto_resultado['condicion'] ?? 'Usado'); ?>
                    </span>
                    <span class="text-xs text-gray-500">📍 <?php echo esc_html($producto_resultado['ubicacion'] ?? ''); ?></span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-green-600 transition-colors">
                    <a href="<?php echo esc_url($producto_resultado['url'] ?? '#'); ?>">
                        <?php echo esc_html($producto_resultado['titulo']); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo esc_html($producto_resultado['descripcion'] ?? ''); ?></p>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span><?php echo esc_html($producto_resultado['vendedor_nombre'] ?? 'Vendedor'); ?></span>
                    <span>⭐ <?php echo esc_html($producto_resultado['vendedor_valoracion'] ?? '5.0'); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
