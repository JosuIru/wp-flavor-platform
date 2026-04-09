<?php
/**
 * Template: WooCommerce Productos Grid
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? 'Productos Destacados';

$productos_ejemplo = $productos_ejemplo ?? [
    [
        'titulo'       => 'Camiseta Premium',
        'precio'       => '29.99',
        'precio_oferta' => '19.99',
        'valoracion'   => 4.5,
        'total_resenas' => 23,
        'categoria'    => 'Ropa',
        'gradiente'    => 'from-purple-400 to-indigo-500',
    ],
    [
        'titulo'       => 'Auriculares Bluetooth',
        'precio'       => '89.99',
        'precio_oferta' => '',
        'valoracion'   => 4.8,
        'total_resenas' => 56,
        'categoria'    => 'Electronica',
        'gradiente'    => 'from-indigo-400 to-purple-500',
    ],
    [
        'titulo'       => 'Mochila Urbana',
        'precio'       => '45.00',
        'precio_oferta' => '35.00',
        'valoracion'   => 4.2,
        'total_resenas' => 15,
        'categoria'    => 'Accesorios',
        'gradiente'    => 'from-violet-400 to-purple-500',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <div class="text-center mb-10">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="text-gray-500"><?php echo esc_html__('Los productos mas populares de nuestra tienda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($productos_ejemplo as $producto_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <!-- Imagen placeholder -->
                    <div class="relative h-52 bg-gradient-to-br <?php echo esc_attr($producto_item['gradiente']); ?> overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Badge categoria -->
                        <div class="absolute top-3 left-3 px-2 py-1 rounded-md bg-white/90 backdrop-blur text-purple-700 text-xs font-medium">
                            <?php echo esc_html($producto_item['categoria']); ?>
                        </div>
                        <?php if (!empty($producto_item['precio_oferta'])) : ?>
                            <div class="absolute top-3 right-3 px-2 py-1 rounded-md bg-red-500 text-white text-xs font-bold">
                                <?php echo esc_html__('OFERTA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contenido -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo esc_html($producto_item['titulo']); ?></h3>

                        <!-- Estrellas de valoracion -->
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex items-center gap-0.5">
                                <?php
                                $puntuacion_entera = floor($producto_item['valoracion']);
                                for ($contador_estrella = 1; $contador_estrella <= 5; $contador_estrella++) :
                                ?>
                                    <?php if ($contador_estrella <= $puntuacion_entera) : ?>
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xs text-gray-400">(<?php echo esc_html($producto_item['total_resenas']); ?>)</span>
                        </div>

                        <!-- Precio -->
                        <div class="flex items-center gap-2 mb-4">
                            <?php if (!empty($producto_item['precio_oferta'])) : ?>
                                <span class="text-xl font-bold text-purple-600"><?php echo esc_html($producto_item['precio_oferta']); ?>&euro;</span>
                                <span class="text-sm text-gray-400 line-through"><?php echo esc_html($producto_item['precio']); ?>&euro;</span>
                            <?php else : ?>
                                <span class="text-xl font-bold text-purple-600"><?php echo esc_html($producto_item['precio']); ?>&euro;</span>
                            <?php endif; ?>
                        </div>

                        <!-- Boton anadir al carrito -->
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-purple-500 text-white font-medium hover:bg-purple-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            <?php echo esc_html__('Anadir al Carrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
