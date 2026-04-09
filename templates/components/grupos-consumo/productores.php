<?php
/**
 * Template: Grid de Productores Locales
 *
 * Muestra un grid con los productores locales disponibles.
 * Cada card muestra foto del productor, nombre, ubicación y productos que ofrecen.
 *
 * @var array  $productores_disponibles Array con datos de los productores
 * @var string $url_detalles_productor URL para ver detalles de un productor
 * @var string $component_classes Clases CSS adicionales para el componente
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$productores_disponibles = $productores_disponibles ?? [
    [
        'id'              => 1,
        'nombre'          => 'Huerta Sostenible',
        'foto'            => '',
        'ubicacion'       => 'Barrio Antiguo',
        'productos'       => ['Verduras', 'Frutas', 'Hierbas aromáticas'],
        'calificacion'    => 4.8,
        'total_resenas'   => 42,
    ],
    [
        'id'              => 2,
        'nombre'          => 'Lechería La Vaca Feliz',
        'foto'            => '',
        'ubicacion'       => 'Zona Rural',
        'productos'       => ['Leche', 'Queso', 'Yogur'],
        'calificacion'    => 4.9,
        'total_resenas'   => 58,
    ],
    [
        'id'              => 3,
        'nombre'          => 'Panadería Artesanal',
        'foto'            => '',
        'ubicacion'       => 'Centro',
        'productos'       => ['Pan', 'Pasteles', 'Bollería'],
        'calificacion'    => 4.7,
        'total_resenas'   => 35,
    ],
    [
        'id'              => 4,
        'nombre'          => 'Carnicería Local',
        'foto'            => '',
        'ubicacion'       => 'Barrio Nuevo',
        'productos'       => ['Carnes ecológicas', 'Embutidos'],
        'calificacion'    => 4.6,
        'total_resenas'   => 28,
    ],
    [
        'id'              => 5,
        'nombre'          => 'Conservas Caseras',
        'foto'            => '',
        'ubicacion'       => 'Periferia',
        'productos'       => ['Conservas', 'Mermeladas', 'Encurtidos'],
        'calificacion'    => 4.8,
        'total_resenas'   => 31,
    ],
    [
        'id'              => 6,
        'nombre'          => 'Huevería del Pueblo',
        'foto'            => '',
        'ubicacion'       => 'Zona Rural',
        'productos'       => ['Huevos', 'Aves de corral'],
        'calificacion'    => 4.9,
        'total_resenas'   => 45,
    ],
];

$url_detalles_productor = $url_detalles_productor ?? '/grupos-consumo/productor/';
$component_classes      = $component_classes ?? '';
?>
<section class="flavor-component flavor-productores py-16 lg:py-24 bg-gray-50 <?php echo esc_attr($component_classes); ?>">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="max-w-3xl mx-auto text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html__('Nuestros Productores Locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="text-lg text-gray-600">
                <?php echo esc_html__('Conoce a los productores que hacen posible nuestros grupos de consumo responsable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Grid de productores -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php foreach ($productores_disponibles as $productor_item) : ?>
                <div class="flavor-productor-card rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 bg-white group">
                    <!-- Foto del productor -->
                    <div class="relative h-56 bg-gradient-to-br from-amber-100 to-yellow-200 overflow-hidden">
                        <?php if (!empty($productor_item['foto'])) : ?>
                            <img
                                src="<?php echo esc_url($productor_item['foto']); ?>"
                                alt="<?php echo esc_attr($productor_item['nombre']); ?>"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                            />
                        <?php else : ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-32 h-32 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Badge de calificación -->
                        <div class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-2 rounded-full flex items-center gap-1 shadow-lg">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                            </svg>
                            <span class="font-semibold text-sm"><?php echo esc_html($productor_item['calificacion']); ?></span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($productor_item['nombre']); ?>
                        </h3>

                        <!-- Ubicación -->
                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span><?php echo esc_html($productor_item['ubicacion']); ?></span>
                        </div>

                        <!-- Productos ofrecidos -->
                        <div class="mb-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                                <?php echo esc_html__('Productos que ofrece', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($productor_item['productos'] as $producto_nombre) : ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <?php echo esc_html($producto_nombre); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Reseñas y botón -->
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold"><?php echo esc_html($productor_item['total_resenas']); ?></span>
                                <?php echo esc_html__(' reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <a
                                href="<?php echo esc_url($url_detalles_productor . $productor_item['id'] . '/'); ?>"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white font-semibold text-sm hover:bg-green-700 transition-colors"
                            >
                                <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
