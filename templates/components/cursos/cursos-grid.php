<?php
/**
 * Template: Grid Cursos
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var array $filtros
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;
?>

<section class="py-16 bg-gradient-to-b from-purple-50 to-white <?php echo esc_attr($component_classes); ?>">
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

            <!-- Filters -->
            <?php if (!empty($filtros)): ?>
                <div class="flex flex-wrap justify-center gap-3 mb-12">
                    <button class="px-6 py-2 bg-purple-600 text-white rounded-full font-medium hover:bg-purple-700 transition-colors">
                        Todos
                    </button>
                    <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                        Tecnología
                    </button>
                    <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                        Idiomas
                    </button>
                    <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                        Arte
                    </button>
                    <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                        Salud
                    </button>
                    <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                        Negocios
                    </button>
                </div>
            <?php endif; ?>

            <!-- Courses Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Course Card 1 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                    <!-- Course Image -->
                    <div class="relative h-48 bg-gradient-to-br from-purple-500 to-indigo-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Badge -->
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-purple-600">
                            Tecnología
                        </div>
                        <!-- Rating -->
                        <div class="absolute top-4 right-4 px-3 py-1 bg-black/50 backdrop-blur-sm rounded-full text-xs font-bold text-white flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            4.8
                        </div>
                    </div>

                    <!-- Course Content -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
                            Programación Web para Principiantes
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            Aprende HTML, CSS y JavaScript desde cero. Perfecto para comenzar tu carrera en desarrollo web.
                        </p>

                        <!-- Meta Info -->
                        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                12 semanas
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                45 alumnos
                            </div>
                        </div>

                        <!-- Instructor -->
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                                JM
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Juan Martínez</p>
                                <p class="text-xs text-gray-500">Desarrollador Senior</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl font-bold text-purple-600">Gratis</span>
                            </div>
                            <a href="#inscribirse" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
                                Inscribirse
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Course Card 2 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                    <div class="relative h-48 bg-gradient-to-br from-blue-500 to-cyan-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-blue-600">
                            Idiomas
                        </div>
                        <div class="absolute top-4 right-4 px-3 py-1 bg-black/50 backdrop-blur-sm rounded-full text-xs font-bold text-white flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            4.9
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                            Inglés Conversacional
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            Mejora tu fluidez en inglés con práctica diaria y conversaciones reales con nativos.
                        </p>

                        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                8 semanas
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                32 alumnos
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold">
                                SL
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Sarah Lee</p>
                                <p class="text-xs text-gray-500">Profesora Nativa</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl font-bold text-blue-600">€49</span>
                                <span class="text-sm text-gray-500">/mes</span>
                            </div>
                            <a href="#inscribirse" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                Inscribirse
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Course Card 3 -->
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                    <div class="relative h-48 bg-gradient-to-br from-pink-500 to-rose-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold text-pink-600">
                            Arte
                        </div>
                        <div class="absolute top-4 right-4 px-3 py-1 bg-black/50 backdrop-blur-sm rounded-full text-xs font-bold text-white flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            4.7
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-pink-600 transition-colors">
                            Pintura al Óleo Nivel Básico
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            Descubre el arte de la pintura al óleo. Desde técnicas básicas hasta tu primera obra.
                        </p>

                        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                10 semanas
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                28 alumnos
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-pink-400 to-rose-500 rounded-full flex items-center justify-center text-white font-bold">
                                LC
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Laura Castro</p>
                                <p class="text-xs text-gray-500">Artista Plástica</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl font-bold text-pink-600">€35</span>
                                <span class="text-sm text-gray-500">/mes</span>
                            </div>
                            <a href="#inscribirse" class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-lg transition-colors">
                                Inscribirse
                            </a>
                        </div>
                    </div>
                </div>

                <!-- More courses cards would follow the same pattern -->
            </div>

            <!-- Load More -->
            <div class="text-center mt-12">
                <button class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                    Ver Más Cursos
                </button>
            </div>
        </div>
    </div>
</section>
