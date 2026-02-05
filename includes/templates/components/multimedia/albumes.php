<!-- Álbumes Grid View -->
<div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
    <!-- Header de Álbumes -->
    <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <div>
                    <h2 class="text-2xl font-bold text-white">Álbumes Comunitarios</h2>
                    <p class="text-pink-100 text-sm">Colecciones organizadas de momentos especiales</p>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center px-4 py-2 bg-white text-rose-600 font-semibold rounded-lg hover:bg-pink-50 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crear Álbum
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
        <div class="flex flex-wrap items-center gap-3">
            <button class="px-4 py-2 bg-rose-600 text-white font-medium rounded-lg hover:bg-rose-700 transition-colors">
                Todos
            </button>
            <button class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                Eventos
            </button>
            <button class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                Actividades
            </button>
            <button class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                Proyectos
            </button>
            <button class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                Paisajes
            </button>
        </div>
    </div>

    <!-- Grid de Álbumes -->
    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Álbum 1 -->
            <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer transform hover:-translate-y-2">
                <!-- Portada del álbum con preview de imágenes -->
                <div class="relative aspect-square overflow-hidden bg-gray-100">
                    <!-- Grid 2x2 de preview de imágenes -->
                    <div class="grid grid-cols-2 gap-1 h-full">
                        <div class="bg-gradient-to-br from-pink-300 to-rose-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-purple-300 to-indigo-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-amber-300 to-orange-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-300 to-teal-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                        <button class="px-4 py-2 bg-white text-rose-600 font-semibold rounded-lg hover:bg-pink-50 transition-colors">
                            Ver Álbum
                        </button>
                    </div>
                    <!-- Contador de fotos -->
                    <div class="absolute top-3 right-3 px-3 py-1 bg-black/60 backdrop-blur-sm text-white text-sm font-semibold rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                        42 fotos
                    </div>
                </div>

                <!-- Información del álbum -->
                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-rose-600 transition-colors">
                        Fiestas del Barrio 2026
                    </h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                        Las mejores fotos de nuestras fiestas patronales. Música, baile y diversión en comunidad.
                    </p>

                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            María García
                        </span>
                        <span class="text-xs text-gray-400">15 Ene 2026</span>
                    </div>

                    <!-- Estadísticas -->
                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            234
                        </span>
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            1.2k
                        </span>
                    </div>
                </div>
            </div>

            <!-- Álbum 2 -->
            <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer transform hover:-translate-y-2">
                <div class="relative aspect-square overflow-hidden bg-gray-100">
                    <div class="grid grid-cols-2 gap-1 h-full">
                        <div class="bg-gradient-to-br from-blue-300 to-cyan-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-green-300 to-emerald-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-lime-300 to-green-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-teal-300 to-cyan-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                        <button class="px-4 py-2 bg-white text-rose-600 font-semibold rounded-lg hover:bg-pink-50 transition-colors">
                            Ver Álbum
                        </button>
                    </div>
                    <div class="absolute top-3 right-3 px-3 py-1 bg-black/60 backdrop-blur-sm text-white text-sm font-semibold rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                        28 fotos
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-rose-600 transition-colors">
                        Huerto Urbano Comunitario
                    </h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                        Proceso de creación y cultivo en nuestro huerto compartido. De la semilla a la cosecha.
                    </p>

                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Carlos Ruiz
                        </span>
                        <span class="text-xs text-gray-400">10 Ene 2026</span>
                    </div>

                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            189
                        </span>
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            856
                        </span>
                    </div>
                </div>
            </div>

            <!-- Álbum 3 -->
            <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer transform hover:-translate-y-2">
                <div class="relative aspect-square overflow-hidden bg-gray-100">
                    <div class="grid grid-cols-2 gap-1 h-full">
                        <div class="bg-gradient-to-br from-red-300 to-pink-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-orange-300 to-red-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-300 to-orange-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-amber-300 to-yellow-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                        <button class="px-4 py-2 bg-white text-rose-600 font-semibold rounded-lg hover:bg-pink-50 transition-colors">
                            Ver Álbum
                        </button>
                    </div>
                    <div class="absolute top-3 right-3 px-3 py-1 bg-black/60 backdrop-blur-sm text-white text-sm font-semibold rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                        35 fotos
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-rose-600 transition-colors">
                        Mercado Local de Verano
                    </h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                        Nuestro mercado de productos locales y artesanías. Apoyando a los productores del barrio.
                    </p>

                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Ana López
                        </span>
                        <span class="text-xs text-gray-400">5 Ene 2026</span>
                    </div>

                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            312
                        </span>
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            1.5k
                        </span>
                    </div>
                </div>
            </div>

            <!-- Álbum 4 -->
            <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer transform hover:-translate-y-2">
                <div class="relative aspect-square overflow-hidden bg-gray-100">
                    <div class="grid grid-cols-2 gap-1 h-full">
                        <div class="bg-gradient-to-br from-violet-300 to-purple-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-fuchsia-300 to-violet-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-purple-300 to-fuchsia-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-300 to-purple-400 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                        <button class="px-4 py-2 bg-white text-rose-600 font-semibold rounded-lg hover:bg-pink-50 transition-colors">
                            Ver Álbum
                        </button>
                    </div>
                    <div class="absolute top-3 right-3 px-3 py-1 bg-black/60 backdrop-blur-sm text-white text-sm font-semibold rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                        51 fotos
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-rose-600 transition-colors">
                        Concierto de Música Local
                    </h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                        Una noche mágica con artistas del barrio. Música en vivo bajo las estrellas.
                    </p>

                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Pedro Martín
                        </span>
                        <span class="text-xs text-gray-400">28 Dic 2025</span>
                    </div>

                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                            567
                        </span>
                        <span class="flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            2.3k
                        </span>
                    </div>
                </div>
            </div>

            <!-- Álbum 5 - Crear Nuevo -->
            <div class="group bg-gradient-to-br from-pink-50 to-rose-100 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer transform hover:-translate-y-2 border-2 border-dashed border-pink-300">
                <div class="aspect-square flex flex-col items-center justify-center p-6">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Crear Nuevo Álbum</h3>
                    <p class="text-sm text-gray-600 text-center">
                        Organiza y comparte tus fotos en una nueva colección
                    </p>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="flex items-center justify-center gap-2 mt-8 pt-8 border-t border-gray-200">
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button class="px-4 py-2 bg-rose-600 text-white font-medium rounded-lg">1</button>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">2</button>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">3</button>
            <span class="px-2 text-gray-500">...</span>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">8</button>
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>
