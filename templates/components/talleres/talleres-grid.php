<?php
/**
 * Template: Grid Talleres
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var bool $mostrar_filtros
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;
?>

<section class="py-16 bg-white <?php echo esc_attr($component_classes); ?>">
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

            <!-- Talleres Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Taller Card 1 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-gray-100 hover:border-fuchsia-300">
                    <div class="relative h-56 bg-gradient-to-br from-pink-500 to-rose-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 right-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-pink-600">
                            <?php echo esc_html__('Manualidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 bg-black/50 backdrop-blur-sm rounded-lg p-3">
                            <div class="flex items-center justify-between text-white text-sm">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <?php echo esc_html__('Sábados 10:00', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="font-bold"><?php echo esc_html__('4 semanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-fuchsia-600 transition-colors">
                            <?php echo esc_html__('Bisutería Creativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Aprende a crear tus propias joyas y accesorios únicos con materiales reciclados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-pink-400 to-rose-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                <?php echo esc_html__('MC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('María Castro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html__('Diseñadora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span><?php echo esc_html__('12/15 plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="/talleres/inscribirse/" class="px-5 py-2 bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 text-white font-semibold rounded-lg transition-all">
                                <?php echo esc_html__('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Taller Card 2 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-gray-100 hover:border-orange-300">
                    <div class="relative h-56 bg-gradient-to-br from-orange-500 to-red-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 right-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-orange-600">
                            <?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 bg-black/50 backdrop-blur-sm rounded-lg p-3">
                            <div class="flex items-center justify-between text-white text-sm">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <?php echo esc_html__('Miércoles 18:00', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="font-bold"><?php echo esc_html__('6 semanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors">
                            <?php echo esc_html__('Repostería Saludable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Deliciosos postres sin azúcar refinada. Recetas fáciles y nutritivas para toda la familia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                <?php echo esc_html__('LC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Laura Camps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html__('Chef Pastelera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span><?php echo esc_html__('8/12 plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <a href="/talleres/inscribirse/" class="px-5 py-2 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all">
                                <?php echo esc_html__('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Taller Card 3 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-gray-100 hover:border-blue-300">
                    <div class="relative h-56 bg-gradient-to-br from-blue-500 to-cyan-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 right-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-blue-600">
                            <?php echo esc_html__('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 bg-black/50 backdrop-blur-sm rounded-lg p-3">
                            <div class="flex items-center justify-between text-white text-sm">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <?php echo esc_html__('Lunes 17:00', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="font-bold"><?php echo esc_html__('8 semanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                            <?php echo esc_html__('Smartphone para Mayores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo esc_html__('Aprende a usar tu móvil, apps, WhatsApp y videollamadas. Ritmo adaptado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                <?php echo esc_html__('PG', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Pedro García', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html__('Instructor TIC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span><?php echo esc_html__('10/10 plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <button class="px-5 py-2 bg-gray-300 text-gray-600 font-semibold rounded-lg cursor-not-allowed" disabled>
                                <?php echo esc_html__('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Load More -->
            <div class="text-center mt-12">
                <button class="px-8 py-3 bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                    <?php echo esc_html__('Ver Más Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>
</section>
