<?php
/**
 * Frontend: Single Producto de Tienda Local
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$producto = $producto ?? [];
$productos_relacionados = $productos_relacionados ?? [];
?>

<div class="flavor-frontend flavor-tienda-local-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/tienda-local/')); ?>" class="hover:text-amber-600 transition-colors">
            Tienda Local
        </a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($producto['nombre'] ?? 'Producto'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Galería de imágenes -->
        <div class="space-y-4">
            <div class="aspect-square rounded-2xl overflow-hidden bg-gray-100 shadow-lg">
                <?php if (!empty($producto['imagen'])): ?>
                <img src="<?php echo esc_url($producto['imagen']); ?>"
                     alt="<?php echo esc_attr($producto['nombre']); ?>"
                     class="w-full h-full object-cover"
                     id="imagen-principal">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-8xl bg-amber-50">🥬</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($producto['galeria'])): ?>
            <div class="flex gap-2 overflow-x-auto pb-2">
                <?php foreach ($producto['galeria'] as $index => $imagen): ?>
                <button class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 border-transparent hover:border-amber-500 transition-colors"
                        onclick="flavorTienda.cambiarImagen('<?php echo esc_url($imagen); ?>')">
                    <img src="<?php echo esc_url($imagen); ?>" alt="Imagen <?php echo $index + 1; ?>" class="w-full h-full object-cover">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info del producto -->
        <div class="space-y-6">
            <!-- Badges -->
            <div class="flex flex-wrap gap-2">
                <?php if (!empty($producto['ecologico'])): ?>
                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                    🌱 Producto ecológico
                </span>
                <?php endif; ?>
                <?php if (!empty($producto['local'])): ?>
                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-sm font-medium">
                    📍 Km 0
                </span>
                <?php endif; ?>
                <?php if (!empty($producto['oferta'])): ?>
                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium">
                    🏷️ En oferta
                </span>
                <?php endif; ?>
            </div>

            <!-- Título y categoría -->
            <div>
                <span class="text-amber-600 font-medium uppercase tracking-wide text-sm">
                    <?php echo esc_html($producto['categoria'] ?? 'General'); ?>
                </span>
                <h1 class="text-3xl font-bold text-gray-800 mt-1">
                    <?php echo esc_html($producto['nombre']); ?>
                </h1>
            </div>

            <!-- Productor -->
            <?php if (!empty($producto['productor'])): ?>
            <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-xl">
                <div class="w-12 h-12 rounded-full bg-amber-200 flex items-center justify-center text-xl">
                    👨‍🌾
                </div>
                <div>
                    <p class="text-sm text-gray-500">Producido por</p>
                    <p class="font-semibold text-amber-700"><?php echo esc_html($producto['productor']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Precio -->
            <div class="border-t border-b border-gray-200 py-4">
                <?php if (!empty($producto['precio_oferta'])): ?>
                <div class="flex items-baseline gap-3">
                    <span class="text-4xl font-bold text-red-600"><?php echo esc_html($producto['precio_oferta']); ?>€</span>
                    <span class="text-xl text-gray-400 line-through"><?php echo esc_html($producto['precio']); ?>€</span>
                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-medium">
                        -<?php echo round((1 - $producto['precio_oferta'] / $producto['precio']) * 100); ?>%
                    </span>
                </div>
                <?php else: ?>
                <span class="text-4xl font-bold text-gray-800"><?php echo esc_html($producto['precio']); ?>€</span>
                <?php endif; ?>
                <span class="text-gray-500 ml-1">/<?php echo esc_html($producto['unidad'] ?? 'unidad'); ?></span>
            </div>

            <!-- Stock -->
            <?php if (isset($producto['stock'])): ?>
            <div class="flex items-center gap-2">
                <?php if ($producto['stock'] > 10): ?>
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="text-green-600 font-medium">En stock</span>
                <?php elseif ($producto['stock'] > 0): ?>
                <span class="w-3 h-3 bg-orange-500 rounded-full"></span>
                <span class="text-orange-600 font-medium">Últimas <?php echo esc_html($producto['stock']); ?> unidades</span>
                <?php else: ?>
                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                <span class="text-red-600 font-medium">Agotado</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Selector de cantidad y añadir al carrito -->
            <div class="flex items-center gap-4">
                <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                    <button class="px-4 py-3 hover:bg-gray-100 transition-colors"
                            onclick="flavorTienda.cambiarCantidad(-1)">−</button>
                    <input type="number" value="1" min="1" max="<?php echo esc_attr($producto['stock'] ?? 99); ?>"
                           class="w-16 text-center py-3 border-0 focus:ring-0" id="cantidad-producto">
                    <button class="px-4 py-3 hover:bg-gray-100 transition-colors"
                            onclick="flavorTienda.cambiarCantidad(1)">+</button>
                </div>

                <button class="flex-1 bg-gradient-to-r from-amber-500 to-yellow-500 text-white py-4 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-600 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        onclick="flavorTienda.agregarCarrito(<?php echo esc_attr($producto['id']); ?>)"
                        <?php echo ($producto['stock'] ?? 1) <= 0 ? 'disabled' : ''; ?>>
                    🛒 Añadir al carrito
                </button>
            </div>

            <!-- Descripción -->
            <?php if (!empty($producto['descripcion'])): ?>
            <div class="prose prose-amber max-w-none">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Descripción</h3>
                <p class="text-gray-600"><?php echo wp_kses_post($producto['descripcion']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Información adicional -->
            <?php if (!empty($producto['info_adicional'])): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                <h3 class="font-semibold text-gray-800">Información adicional</h3>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <?php foreach ($producto['info_adicional'] as $etiqueta => $valor): ?>
                    <dt class="text-gray-500"><?php echo esc_html($etiqueta); ?></dt>
                    <dd class="text-gray-800 font-medium"><?php echo esc_html($valor); ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Productos relacionados -->
    <?php if (!empty($productos_relacionados)): ?>
    <section class="border-t border-gray-200 pt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">También te puede interesar</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($productos_relacionados as $relacionado): ?>
            <a href="<?php echo esc_url($relacionado['url']); ?>"
               class="bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all group">
                <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 mb-3">
                    <?php if (!empty($relacionado['imagen'])): ?>
                    <img src="<?php echo esc_url($relacionado['imagen']); ?>"
                         alt="<?php echo esc_attr($relacionado['nombre']); ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-4xl">🥬</div>
                    <?php endif; ?>
                </div>
                <h3 class="font-medium text-gray-800 text-sm line-clamp-2 mb-1">
                    <?php echo esc_html($relacionado['nombre']); ?>
                </h3>
                <span class="text-amber-600 font-bold"><?php echo esc_html($relacionado['precio']); ?>€</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
