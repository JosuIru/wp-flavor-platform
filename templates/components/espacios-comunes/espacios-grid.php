<?php
/**
 * Template: Grid Espacios Comunes
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var bool $mostrar_calendario
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;
?>

<section class="py-16 bg-gradient-to-b from-blue-50 to-white <?php echo esc_attr($component_classes); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <?php if (!empty($subtitulo)): ?>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        <?php echo esc_html($subtitulo); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Spaces Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Space Card 1 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="relative h-64 bg-gradient-to-br from-yellow-400 to-orange-500 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-32 h-32 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <!-- Badge -->
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-orange-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                            </svg>
                            <?php echo esc_html__('Disponible Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <!-- Capacity -->
                        <div class="absolute bottom-4 right-4 px-3 py-2 bg-black/50 backdrop-blur-sm rounded-lg text-white text-sm font-bold">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <?php echo esc_html__('Hasta 100 personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                            <?php echo esc_html__('Salón de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Espacio multiusos ideal para celebraciones, reuniones y eventos comunitarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <!-- Amenities -->
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Sistema de Sonido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Proyector', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('WiFi', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>

                        <!-- Price & Action -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div>
                                <span class="text-2xl font-bold text-blue-600">€25</span>
                                <span class="text-gray-500 text-sm"><?php echo esc_html__('/hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="#reservar" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                <?php echo esc_html__('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Space Card 2 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="relative h-64 bg-gradient-to-br from-green-400 to-emerald-500 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-32 h-32 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-green-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo esc_html__('Aire Libre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="absolute bottom-4 right-4 px-3 py-2 bg-black/50 backdrop-blur-sm rounded-lg text-white text-sm font-bold">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <?php echo esc_html__('Hasta 60 personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html__('Terraza Jardín', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Espacio exterior con zona verde, perfecto para eventos al aire libre y barbacoas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Barbacoa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Mesas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Sombra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Juegos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div>
                                <span class="text-2xl font-bold text-green-600">€15</span>
                                <span class="text-gray-500 text-sm"><?php echo esc_html__('/hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="#reservar" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                <?php echo esc_html__('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Space Card 3 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="relative h-64 bg-gradient-to-br from-pink-400 to-rose-500 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-32 h-32 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-pink-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo esc_html__('Para Niños', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="absolute bottom-4 right-4 px-3 py-2 bg-black/50 backdrop-blur-sm rounded-lg text-white text-sm font-bold">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <?php echo esc_html__('Hasta 30 niños', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html__('Ludoteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Sala equipada para fiestas infantiles con juegos, decoración y zona de merienda.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="px-3 py-1 bg-pink-100 text-pink-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Juguetes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-pink-100 text-pink-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Decoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-pink-100 text-pink-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Seguro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span class="px-3 py-1 bg-pink-100 text-pink-700 text-xs font-medium rounded-full">
                                <?php echo esc_html__('Zona Merienda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div>
                                <span class="text-2xl font-bold text-pink-600">€20</span>
                                <span class="text-gray-500 text-sm"><?php echo esc_html__('/hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="#reservar" class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-lg transition-colors">
                                <?php echo esc_html__('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="mt-12 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-2xl p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-3"><?php echo esc_html__('💡 Información Importante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="text-gray-700 mb-4 max-w-2xl mx-auto">
                    <?php echo esc_html__('Reserva con al menos 24h de antelación. Los vecinos tienen prioridad y descuentos especiales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <a href="#normas" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    <?php echo esc_html__('Ver Normas y Condiciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
