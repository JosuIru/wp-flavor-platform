<?php
/**
 * Frontend: Single Producto del Marketplace
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$producto = $producto ?? [];
$vendedor = $vendedor ?? [];
$productos_relacionados = $productos_relacionados ?? [];
?>

<div class="flavor-frontend flavor-marketplace-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/marketplace/')); ?>" class="hover:text-green-600 transition-colors">Marketplace</a>
        <span>›</span>
        <?php if (!empty($producto['categoria'])): ?>
        <a href="<?php echo esc_url(home_url('/marketplace/?cat=' . ($producto['categoria_slug'] ?? ''))); ?>" class="hover:text-green-600 transition-colors">
            <?php echo esc_html($producto['categoria']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($producto['titulo'] ?? 'Producto'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Galeria de imagenes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['titulo'] ?? ''); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <span class="text-6xl">📷</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($producto['galeria'])): ?>
                <div class="flex gap-2 p-4 overflow-x-auto">
                    <?php foreach ($producto['galeria'] as $imagen_galeria): ?>
                    <div class="w-20 h-20 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0 cursor-pointer border-2 border-transparent hover:border-green-500 transition-colors">
                        <img src="<?php echo esc_url($imagen_galeria); ?>" alt="" class="w-full h-full object-cover">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Informacion del producto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="bg-lime-100 text-lime-700 px-4 py-2 rounded-full font-medium text-sm">
                            <?php echo esc_html($producto['condicion'] ?? 'Usado'); ?>
                        </span>
                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                            🏷️ <?php echo esc_html($producto['categoria'] ?? 'General'); ?>
                        </span>
                    </div>
                    <span class="text-sm text-gray-500">
                        📅 <?php echo esc_html($producto['fecha_publicacion'] ?? ''); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-2">
                    <?php echo esc_html($producto['titulo'] ?? ''); ?>
                </h1>

                <div class="flex items-center gap-3 mb-6">
                    <span class="text-3xl font-bold text-green-600"><?php echo esc_html($producto['precio'] ?? '0'); ?> €</span>
                    <?php if (!empty($producto['precio_original'])): ?>
                    <span class="text-lg text-gray-400 line-through"><?php echo esc_html($producto['precio_original']); ?> €</span>
                    <?php endif; ?>
                    <?php if (!empty($producto['negociable'])): ?>
                    <span class="bg-amber-100 text-amber-700 text-xs px-3 py-1 rounded-full">Negociable</span>
                    <?php endif; ?>
                </div>

                <div class="prose prose-green max-w-none mb-6">
                    <?php echo wp_kses_post($producto['descripcion'] ?? ''); ?>
                </div>

                <!-- Detalles del producto -->
                <?php if (!empty($producto['detalles'])): ?>
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Detalles del producto</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        <?php foreach ($producto['detalles'] as $etiqueta_detalle => $valor_detalle): ?>
                        <div>
                            <dt class="text-sm text-gray-500"><?php echo esc_html($etiqueta_detalle); ?></dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($valor_detalle); ?></dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ubicacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📍 Ubicacion</h2>
                <div class="bg-gray-100 rounded-xl h-48 flex items-center justify-center text-gray-400">
                    <span class="text-lg">Mapa de ubicacion aproximada</span>
                </div>
                <p class="text-sm text-gray-500 mt-3"><?php echo esc_html($producto['ubicacion'] ?? 'Ubicacion no especificada'); ?></p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Perfil del vendedor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($vendedor['nombre'] ?? 'V', 0, 1)); ?>
                </div>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($vendedor['nombre'] ?? 'Vendedor'); ?></h3>
                <p class="text-sm text-gray-500 mb-4">Miembro desde <?php echo esc_html($vendedor['miembro_desde'] ?? ''); ?></p>

                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                    <div>
                        <p class="text-xl font-bold text-green-600"><?php echo esc_html($vendedor['anuncios_activos'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500">Anuncios</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($vendedor['ventas'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500">Ventas</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-yellow-500">⭐ <?php echo esc_html($vendedor['valoracion'] ?? '5.0'); ?></p>
                        <p class="text-xs text-gray-500">Valoracion</p>
                    </div>
                </div>

                <?php if (!empty($vendedor['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full mb-4">
                    ✓ Vendedor verificado
                </span>
                <?php endif; ?>
            </div>

            <!-- Contactar vendedor -->
            <div class="bg-gradient-to-br from-lime-500 to-green-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2">¿Te interesa este producto?</h3>
                <p class="text-lime-100 text-sm mb-4">Contacta con <?php echo esc_html($vendedor['nombre'] ?? 'el vendedor'); ?> para mas informacion.</p>
                <button class="w-full bg-white text-green-600 py-3 px-4 rounded-xl font-semibold hover:bg-lime-50 transition-colors mb-3"
                        onclick="flavorMarketplace.contactarVendedor(<?php echo esc_attr($producto['id'] ?? 0); ?>)">
                    💬 Contactar vendedor
                </button>
                <button class="w-full bg-white/20 backdrop-blur text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors"
                        onclick="flavorMarketplace.guardarFavorito(<?php echo esc_attr($producto['id'] ?? 0); ?>)">
                    ❤️ Guardar en favoritos
                </button>
            </div>

            <!-- Productos relacionados -->
            <?php if (!empty($productos_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Productos similares</h3>
                <div class="space-y-3">
                    <?php foreach ($productos_relacionados as $producto_relacionado): ?>
                    <a href="<?php echo esc_url($producto_relacionado['url'] ?? '#'); ?>" class="flex gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-16 h-16 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <?php if (!empty($producto_relacionado['imagen'])): ?>
                            <img src="<?php echo esc_url($producto_relacionado['imagen']); ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-xl">📷</div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($producto_relacionado['titulo'] ?? ''); ?></p>
                            <p class="text-green-600 font-bold text-sm"><?php echo esc_html($producto_relacionado['precio'] ?? '0'); ?> €</p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($producto_relacionado['condicion'] ?? ''); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
