<!-- Puntos de Reciclaje Section -->
<section id="puntos-reciclaje" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 rounded-full mb-4">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-sm font-semibold text-green-800">Localiza Puntos Cercanos</span>
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Puntos de <span class="text-green-600">Reciclaje</span>
            </h2>
            <p class="text-lg md:text-xl text-gray-600">
                Encuentra el punto de reciclaje más cercano a tu ubicación. Filtra por tipo de residuo y descubre horarios de apertura.
            </p>
        </div>

        <!-- Filters Bar -->
        <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="md:col-span-2">
                    <label for="search-location" class="block text-sm font-semibold text-gray-700 mb-2">
                        Buscar por ubicación
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="search-location"
                            placeholder="Introduce tu dirección o código postal..."
                            class="w-full px-4 py-3 pl-11 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                        >
                        <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Type Filter -->
                <div>
                    <label for="waste-type" class="block text-sm font-semibold text-gray-700 mb-2">
                        Tipo de residuo
                    </label>
                    <select id="waste-type" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <option value="">Todos los tipos</option>
                        <option value="organico">Orgánico</option>
                        <option value="papel">Papel y Cartón</option>
                        <option value="envases">Envases</option>
                        <option value="vidrio">Vidrio</option>
                        <option value="electronicos">Electrónicos</option>
                        <option value="pilas">Pilas y Baterías</option>
                        <option value="ropa">Ropa y Textil</option>
                        <option value="aceite">Aceite Usado</option>
                    </select>
                </div>

                <!-- Distance Filter -->
                <div>
                    <label for="distance" class="block text-sm font-semibold text-gray-700 mb-2">
                        Distancia máxima
                    </label>
                    <select id="distance" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <option value="1">1 km</option>
                        <option value="3" selected>3 km</option>
                        <option value="5">5 km</option>
                        <option value="10">10 km</option>
                    </select>
                </div>
            </div>

            <!-- Current Location Button -->
            <div class="mt-4">
                <button class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Usar mi ubicación actual
                </button>
            </div>
        </div>

        <!-- Map and List Container -->
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Map Container -->
            <div class="order-2 lg:order-1">
                <div class="bg-gray-100 rounded-2xl overflow-hidden shadow-xl border border-gray-200 sticky top-4">
                    <!-- Map Placeholder -->
                    <div class="aspect-[4/3] bg-gradient-to-br from-green-100 to-emerald-100 relative">
                        <!-- Map will be loaded here with JavaScript -->
                        <div id="recycling-map" class="w-full h-full"></div>

                        <!-- Map Placeholder Content -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-24 h-24 text-green-600 mx-auto mb-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                                <p class="text-lg font-semibold text-gray-700">Mapa Interactivo</p>
                                <p class="text-sm text-gray-500 mt-2">Cargando puntos de reciclaje...</p>
                            </div>
                        </div>

                        <!-- Map Legend -->
                        <div class="absolute bottom-4 left-4 bg-white rounded-xl shadow-lg p-4 max-w-xs">
                            <h4 class="text-sm font-bold text-gray-900 mb-3">Leyenda</h4>
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-green-600 rounded-full"></div>
                                    <span class="text-gray-700">Punto Limpio (varios tipos)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-blue-600 rounded-full"></div>
                                    <span class="text-gray-700">Contenedor específico</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-amber-600 rounded-full"></div>
                                    <span class="text-gray-700">Centro de recogida</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Points List -->
            <div class="order-1 lg:order-2">
                <div class="space-y-4">
                    <!-- Result Count -->
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-sm text-gray-600">
                            <span class="font-bold text-gray-900">12 puntos</span> encontrados cerca de ti
                        </p>
                        <select class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                            <option>Más cercano</option>
                            <option>Mejor valorado</option>
                            <option>Más completo</option>
                        </select>
                    </div>

                    <!-- Point Card 1 -->
                    <div class="bg-white border-2 border-gray-200 hover:border-green-500 rounded-2xl p-6 transition-all duration-300 hover:shadow-xl cursor-pointer group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-600 transition-colors">
                                    <svg class="w-6 h-6 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">Punto Limpio Centro</h3>
                                    <p class="text-sm text-gray-600">Calle Mayor, 45 - Basauri</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-md">Abierto</span>
                                        <span class="text-xs text-gray-500">Cierra a las 20:00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600">0.8 km</p>
                                <p class="text-xs text-gray-500 mt-1">10 min andando</p>
                            </div>
                        </div>

                        <!-- Accepted Waste Types -->
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Acepta:</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200">Orgánico</span>
                                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">Envases</span>
                                <span class="px-3 py-1 bg-purple-50 text-purple-700 text-xs font-medium rounded-full border border-purple-200">Electrónicos</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full border border-gray-300">+5 más</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all">
                                Cómo llegar
                            </button>
                            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                                Ver detalles
                            </button>
                        </div>
                    </div>

                    <!-- Point Card 2 -->
                    <div class="bg-white border-2 border-gray-200 hover:border-green-500 rounded-2xl p-6 transition-all duration-300 hover:shadow-xl cursor-pointer group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-600 transition-colors">
                                    <svg class="w-6 h-6 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">Contenedores Plaza Euskadi</h3>
                                    <p class="text-sm text-gray-600">Plaza Euskadi - Basauri</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-md">24/7</span>
                                        <span class="text-xs text-gray-500">Siempre disponible</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600">1.2 km</p>
                                <p class="text-xs text-gray-500 mt-1">15 min andando</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Acepta:</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200">Orgánico</span>
                                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">Envases</span>
                                <span class="px-3 py-1 bg-green-50 text-green-700 text-xs font-medium rounded-full border border-green-200">Vidrio</span>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200">Papel</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all">
                                Cómo llegar
                            </button>
                            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                                Ver detalles
                            </button>
                        </div>
                    </div>

                    <!-- Point Card 3 -->
                    <div class="bg-white border-2 border-gray-200 hover:border-green-500 rounded-2xl p-6 transition-all duration-300 hover:shadow-xl cursor-pointer group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center group-hover:bg-amber-600 transition-colors">
                                    <svg class="w-6 h-6 text-amber-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">Centro Recogida Electrónicos</h3>
                                    <p class="text-sm text-gray-600">Polígono Industrial Txorierri</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-md">Cerrado</span>
                                        <span class="text-xs text-gray-500">Abre mañana a las 9:00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600">2.4 km</p>
                                <p class="text-xs text-gray-500 mt-1">8 min en coche</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Acepta:</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-purple-50 text-purple-700 text-xs font-medium rounded-full border border-purple-200">Electrónicos</span>
                                <span class="px-3 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-full border border-yellow-200">Pilas</span>
                                <span class="px-3 py-1 bg-pink-50 text-pink-700 text-xs font-medium rounded-full border border-pink-200">Baterías</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all">
                                Cómo llegar
                            </button>
                            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                                Ver detalles
                            </button>
                        </div>
                    </div>

                    <!-- Load More Button -->
                    <div class="text-center pt-6">
                        <button class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all">
                            Ver más puntos
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Banner -->
        <div class="mt-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-8 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-1">¿No encuentras lo que buscas?</h3>
                        <p class="text-white/90">Contáctanos y te ayudaremos a encontrar el punto de reciclaje adecuado</p>
                    </div>
                </div>
                <button class="px-8 py-4 bg-white hover:bg-gray-100 text-green-600 font-semibold rounded-xl shadow-lg transition-all whitespace-nowrap">
                    Contactar Soporte
                </button>
            </div>
        </div>
    </div>
</section>
