<?php
/**
 * Template: Hero Section - Huertos Urbanos
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/HuertosUrbanos
 */

defined('ABSPATH') || exit;
?>

<section class="relative bg-gradient-to-br from-green-50 via-emerald-50 to-lime-50 overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(34, 197, 94, 0.3) 35px, rgba(34, 197, 94, 0.3) 70px);"></div>
    </div>

    <!-- Content -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            <!-- Left Column: Text Content -->
            <div class="space-y-6 sm:space-y-8 text-center lg:text-left">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span>Cultiva tu propia comida</span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                    Huertos Urbanos
                    <span class="block text-green-600 mt-2">de Basabere</span>
                </h1>

                <!-- Description -->
                <p class="text-lg sm:text-xl text-gray-700 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Conecta con la naturaleza y cultiva alimentos frescos y ecológicos. Reserva tu parcela y únete a nuestra comunidad de hortelanos urbanos.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <button class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Ver parcelas disponibles</span>
                    </button>

                    <button class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white hover:bg-gray-50 text-green-700 font-semibold rounded-xl shadow-md hover:shadow-lg border-2 border-green-200 transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Más información</span>
                    </button>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 sm:gap-8 pt-8 border-t border-green-200">
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-green-600">45+</div>
                        <div class="text-sm sm:text-base text-gray-600 mt-1">Parcelas activas</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-green-600">120+</div>
                        <div class="text-sm sm:text-base text-gray-600 mt-1">Hortelanos</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-green-600">100%</div>
                        <div class="text-sm sm:text-base text-gray-600 mt-1">Ecológico</div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Visual -->
            <div class="relative">
                <!-- Main Image Container -->
                <div class="relative z-10">
                    <div class="aspect-square rounded-3xl bg-gradient-to-br from-green-400 to-emerald-600 p-8 shadow-2xl">
                        <!-- Garden Grid Illustration -->
                        <div class="w-full h-full grid grid-cols-3 gap-4">
                            <!-- Plot 1: Tomatoes -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-red-200 group-hover:text-red-300 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="8" />
                                    <path opacity="0.6" d="M12 4 L10 6 L12 8 L14 6 Z" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Tomates</span>
                            </div>

                            <!-- Plot 2: Lettuce -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-green-200 group-hover:text-green-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3C8 3 5 6 5 9c0 2 1 4 3 5-2 1-3 3-3 5 0 3 3 6 7 6s7-3 7-6c0-2-1-4-3-5 2-1 3-3 3-5 0-3-3-6-7-6z" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Lechugas</span>
                            </div>

                            <!-- Plot 3: Carrots -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-orange-200 group-hover:text-orange-300 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L10 4L8 3L9 5L7 6L9 7L8 9L10 8L11 10L12 8L13 10L14 8L16 9L15 7L17 6L15 5L16 3L14 4L12 2Z" opacity="0.6" />
                                    <path d="M12 8C10 8 9 10 9 12L9 20C9 21 10 22 12 22C14 22 15 21 15 20L15 12C15 10 14 8 12 8Z" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Zanahorias</span>
                            </div>

                            <!-- Plot 4: Peppers -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-yellow-200 group-hover:text-yellow-300 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L10 4L12 6C10 6 8 8 8 11L8 18C8 20 10 22 12 22C14 22 16 20 16 18L16 11C16 8 14 6 12 6L14 4L12 2Z" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Pimientos</span>
                            </div>

                            <!-- Plot 5: Herbs -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-lime-200 group-hover:text-lime-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C10 4 9 6 9 8C9 10 10 11 12 11C12 11 12 14 10 16M12 2C14 4 15 6 15 8C15 10 14 11 12 11C12 11 12 14 14 16M12 16V22" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Hierbas</span>
                            </div>

                            <!-- Plot 6: Strawberries -->
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 flex flex-col items-center justify-center hover:bg-white/30 transition-all duration-300 group">
                                <svg class="w-12 h-12 text-pink-200 group-hover:text-pink-300 transition-colors" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 4L10 6L12 8L14 6L12 4Z" opacity="0.6" />
                                    <path d="M12 8C9 8 7 10 7 13C7 17 9 20 12 21C15 20 17 17 17 13C17 10 15 8 12 8Z" />
                                    <circle cx="10" cy="13" r="1" fill="white" opacity="0.4" />
                                    <circle cx="14" cy="13" r="1" fill="white" opacity="0.4" />
                                    <circle cx="12" cy="16" r="1" fill="white" opacity="0.4" />
                                </svg>
                                <span class="text-xs text-white mt-2 font-medium">Fresas</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute -top-4 -right-4 w-24 h-24 bg-yellow-400 rounded-full opacity-60 blur-2xl animate-pulse"></div>
                <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-green-400 rounded-full opacity-40 blur-3xl"></div>
            </div>

        </div>

        <!-- Features Section -->
        <div class="mt-16 sm:mt-20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Feature 1 -->
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Cultivo ecológico</h3>
                <p class="text-gray-600 text-sm">Sin pesticidas ni químicos. 100% natural y sostenible.</p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Comunidad activa</h3>
                <p class="text-gray-600 text-sm">Comparte experiencias y aprende de otros hortelanos.</p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Asesoramiento</h3>
                <p class="text-gray-600 text-sm">Talleres, guías y apoyo para tus cultivos.</p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ubicación ideal</h3>
                <p class="text-gray-600 text-sm">Espacios bien situados y accesibles en Basabere.</p>
            </div>
        </div>
    </div>
</section>
