<?php
/**
 * Template: Grid de Tiendas Locales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Tiendas de Tu Barrio';
$descripcion = $descripcion ?? 'Apoya el comercio local y descubre lo mejor de tu zona';

$tiendas = [
    ['nombre' => 'Panaderia La Espiga', 'categoria' => 'Alimentacion', 'descripcion' => 'Pan artesanal y bolleria casera desde 1985', 'direccion' => 'C/ Mayor, 23', 'horario' => '7:00 - 14:00', 'imagen' => 'https://picsum.photos/seed/tienda1/400/300', 'valoracion' => 4.8, 'distancia' => '150m'],
    ['nombre' => 'Fruteria El Huerto', 'categoria' => 'Alimentacion', 'descripcion' => 'Frutas y verduras de temporada, km 0', 'direccion' => 'Plaza Central, 5', 'horario' => '8:00 - 20:00', 'imagen' => 'https://picsum.photos/seed/tienda2/400/300', 'valoracion' => 4.6, 'distancia' => '200m'],
    ['nombre' => 'Libreria Papel y Tinta', 'categoria' => 'Cultura', 'descripcion' => 'Libros nuevos y de segunda mano', 'direccion' => 'C/ Luna, 12', 'horario' => '10:00 - 20:00', 'imagen' => 'https://picsum.photos/seed/tienda3/400/300', 'valoracion' => 4.9, 'distancia' => '320m'],
    ['nombre' => 'Floristeria Primavera', 'categoria' => 'Hogar', 'descripcion' => 'Flores frescas y arreglos florales', 'direccion' => 'Av. Parque, 8', 'horario' => '9:00 - 19:00', 'imagen' => 'https://picsum.photos/seed/tienda4/400/300', 'valoracion' => 4.7, 'distancia' => '180m'],
    ['nombre' => 'Farmacia San Jose', 'categoria' => 'Salud', 'descripcion' => 'Farmacia con servicio de urgencias', 'direccion' => 'C/ Hospital, 3', 'horario' => '24h', 'imagen' => 'https://picsum.photos/seed/tienda5/400/300', 'valoracion' => 4.5, 'distancia' => '400m'],
    ['nombre' => 'Ferreteria Martinez', 'categoria' => 'Hogar', 'descripcion' => 'Todo para bricolaje y hogar', 'direccion' => 'C/ Industria, 45', 'horario' => '9:00 - 13:30, 17:00 - 20:00', 'imagen' => 'https://picsum.photos/seed/tienda6/400/300', 'valoracion' => 4.4, 'distancia' => '550m'],
];

$categorias = ['Todas', 'Alimentacion', 'Cultura', 'Hogar', 'Salud', 'Moda', 'Servicios'];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-indigo-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Comercio Local
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <?php foreach ($categorias as $indice => $cat): ?>
                <button class="px-4 py-2 rounded-full text-sm font-medium transition-all <?php echo $indice === 0 ? '' : 'hover:bg-indigo-50'; ?>"
                        style="<?php echo $indice === 0 ? 'background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;' : 'background: white; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
                    <?php echo esc_html($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($tiendas as $tienda): ?>
                <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="<?php echo esc_url($tienda['imagen']); ?>" alt="<?php echo esc_attr($tienda['nombre']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                        <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500 text-white"><?php echo esc_html($tienda['categoria']); ?></span>
                        <div class="absolute top-4 right-4 px-2 py-1 rounded-lg bg-white/90 flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm font-bold text-gray-900"><?php echo esc_html($tienda['valoracion']); ?></span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2"><?php echo esc_html($tienda['nombre']); ?></h3>
                        <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($tienda['descripcion']); ?></p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <?php echo esc_html($tienda['direccion']); ?> · <?php echo esc_html($tienda['distancia']); ?>
                            </p>
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php echo esc_html($tienda['horario']); ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                            <a href="#tienda-<?php echo sanitize_title($tienda['nombre']); ?>" class="flex-1 py-2 rounded-xl text-center font-semibold text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                                Ver Tienda
                            </a>
                            <button class="p-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#todas-tiendas" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
                Ver Todas las Tiendas
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
