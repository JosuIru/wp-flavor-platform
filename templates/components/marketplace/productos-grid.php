<?php
/**
 * Template: Marketplace Productos Grid
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? 'Productos Destacados';

$productos_ejemplo = $productos_ejemplo ?? [
    [
        'titulo'       => 'Bicicleta de Montana',
        'descripcion'  => 'Bicicleta en perfecto estado, poco uso. Ideal para rutas de montana y ciudad.',
        'precio'       => '250',
        'vendedor'     => 'Carlos M.',
        'valoracion'   => 4.8,
        'ubicacion'    => 'Centro',
        'condicion'    => 'Como nuevo',
        'gradiente'    => 'from-lime-400 to-green-500',
    ],
    [
        'titulo'       => 'Sofa 3 Plazas',
        'descripcion'  => 'Sofa comodo de tres plazas, color gris. Se entrega desmontado para facilitar el transporte.',
        'precio'       => '180',
        'vendedor'     => 'Ana L.',
        'valoracion'   => 4.5,
        'ubicacion'    => 'Norte',
        'condicion'    => 'Usado',
        'gradiente'    => 'from-emerald-400 to-teal-500',
    ],
    [
        'titulo'       => 'iPhone 14 Pro',
        'descripcion'  => 'iPhone 14 Pro 128GB, con funda y protector de pantalla. Bateria al 92%.',
        'precio'       => '650',
        'vendedor'     => 'Pedro R.',
        'valoracion'   => 5.0,
        'ubicacion'    => 'Sur',
        'condicion'    => 'Nuevo',
        'gradiente'    => 'from-green-400 to-lime-500',
    ],
];

$opciones_ordenar = $opciones_ordenar ?? [
    'recientes'      => 'Mas recientes',
    'precio_asc'     => 'Precio: menor a mayor',
    'precio_desc'    => 'Precio: mayor a menor',
    'valoracion'     => 'Mejor valorados',
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Cabecera con ordenacion -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800"><?php echo esc_html($titulo_seccion); ?></h2>
            <select name="ordenar" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-green-300">
                <?php foreach ($opciones_ordenar as $valor_orden => $etiqueta_orden) : ?>
                    <option value="<?php echo esc_attr($valor_orden); ?>"><?php echo esc_html($etiqueta_orden); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Grid de productos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($productos_ejemplo as $producto_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <!-- Imagen placeholder con gradiente -->
                    <div class="relative h-48 bg-gradient-to-br <?php echo esc_attr($producto_item['gradiente']); ?> overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Badge de precio -->
                        <div class="absolute top-3 left-3 px-3 py-1 rounded-lg bg-white/90 backdrop-blur text-green-700 font-bold text-lg shadow-sm">
                            <?php echo esc_html($producto_item['precio']); ?>&euro;
                        </div>
                        <!-- Boton favorito -->
                        <button class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition-colors group/fav" aria-label="<?php echo esc_attr__('Anadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <svg class="w-5 h-5 text-gray-400 group-hover/fav:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                        <!-- Badge de condicion -->
                        <div class="absolute bottom-3 left-3 px-2 py-1 rounded-md bg-black/50 backdrop-blur text-white text-xs font-medium">
                            <?php echo esc_html($producto_item['condicion']); ?>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1"><?php echo esc_html($producto_item['titulo']); ?></h3>
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?php echo esc_html($producto_item['descripcion']); ?></p>

                        <!-- Vendedor y ubicacion -->
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span><?php echo esc_html($producto_item['vendedor']); ?></span>
                                <span class="text-yellow-500">&#9733; <?php echo esc_html($producto_item['valoracion']); ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span><?php echo esc_html($producto_item['ubicacion']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
