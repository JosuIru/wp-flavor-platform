<?php
/**
 * Template: Grid Libros Biblioteca
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var bool $mostrar_filtros
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;
?>

<section class="py-16 bg-gradient-to-b from-orange-50 to-white <?php echo esc_attr($component_classes); ?>">
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

            <!-- Search & Filters -->
            <div class="mb-12">
                <div class="max-w-2xl mx-auto mb-6">
                    <div class="relative">
                        <input type="text" placeholder="Buscar por título, autor, ISBN..." class="w-full px-6 py-4 pr-12 rounded-full border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 outline-none transition-all">
                        <button class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-orange-500 hover:bg-orange-600 rounded-full flex items-center justify-center text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <?php if ($mostrar_filtros): ?>
                    <div class="flex flex-wrap justify-center gap-3">
                        <button class="px-6 py-2 bg-orange-600 text-white rounded-full font-medium hover:bg-orange-700 transition-colors">
                            Todos
                        </button>
                        <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                            Ficción
                        </button>
                        <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                            No Ficción
                        </button>
                        <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                            Juvenil
                        </button>
                        <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                            Infantil
                        </button>
                        <button class="px-6 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-200 transition-colors">
                            Cómic
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Books Grid -->
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                <!-- Book Card 1 -->
                <div class="group relative">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-amber-600 via-orange-500 to-red-600 rounded-lg overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-105">
                        <!-- Book Cover Placeholder -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-white">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="font-bold text-lg text-center mb-2">Cien Años de Soledad</h3>
                            <p class="text-sm text-center opacity-90">Gabriel García Márquez</p>
                        </div>

                        <!-- Availability Badge -->
                        <div class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                            Disponible
                        </div>
                    </div>

                    <!-- Book Info -->
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                4.9
                            </span>
                            <span>Ficción</span>
                        </div>
                        <button class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>

                <!-- Book Card 2 -->
                <div class="group relative">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-blue-600 via-indigo-500 to-purple-600 rounded-lg overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-105">
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-white">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="font-bold text-lg text-center mb-2">Sapiens</h3>
                            <p class="text-sm text-center opacity-90">Yuval Noah Harari</p>
                        </div>
                        <div class="absolute top-3 right-3 px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded-full">
                            Prestado
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                5.0
                            </span>
                            <span>No Ficción</span>
                        </div>
                        <button class="w-full py-2 bg-gray-400 text-white font-semibold rounded-lg cursor-not-allowed" disabled>
                            No Disponible
                        </button>
                    </div>
                </div>

                <!-- Book Card 3 -->
                <div class="group relative">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-pink-600 via-rose-500 to-red-600 rounded-lg overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-105">
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-white">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="font-bold text-lg text-center mb-2">Harry Potter y la Piedra Filosofal</h3>
                            <p class="text-sm text-center opacity-90">J.K. Rowling</p>
                        </div>
                        <div class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                            Disponible
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                4.8
                            </span>
                            <span>Juvenil</span>
                        </div>
                        <button class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>

                <!-- Book Card 4 -->
                <div class="group relative">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-green-600 via-emerald-500 to-teal-600 rounded-lg overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-105">
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-white">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="font-bold text-lg text-center mb-2">El Principito</h3>
                            <p class="text-sm text-center opacity-90">Antoine de Saint-Exupéry</p>
                        </div>
                        <div class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                            Disponible
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                4.9
                            </span>
                            <span>Infantil</span>
                        </div>
                        <button class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>

                <!-- Book Card 5 -->
                <div class="group relative">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-gray-700 via-gray-600 to-gray-800 rounded-lg overflow-hidden shadow-lg group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-105">
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-white">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="font-bold text-lg text-center mb-2">1984</h3>
                            <p class="text-sm text-center opacity-90">George Orwell</p>
                        </div>
                        <div class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                            Disponible
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                4.8
                            </span>
                            <span>Ficción</span>
                        </div>
                        <button class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Load More -->
            <div class="text-center mt-12">
                <button class="px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                    Ver Más Libros
                </button>
            </div>
        </div>
    </div>
</section>
