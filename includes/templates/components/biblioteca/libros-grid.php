<!-- Grid de Libros -->
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado de la sección -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Catálogo de Libros</h2>
                <p class="text-gray-600">Explora nuestra colección de lecturas disponibles</p>
            </div>

            <!-- Filtros y Ordenación -->
            <div class="flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
                <div class="relative">
                    <select class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm font-medium text-gray-700 hover:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        <option>Todos los géneros</option>
                        <option>Literatura</option>
                        <option>Ciencia</option>
                        <option>Historia</option>
                        <option>Arte</option>
                        <option>Tecnología</option>
                    </select>
                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <div class="relative">
                    <select class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm font-medium text-gray-700 hover:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        <option>Más recientes</option>
                        <option>Más populares</option>
                        <option>Mejor valorados</option>
                        <option>Alfabético A-Z</option>
                    </select>
                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Grid de Libros -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 md:gap-6">
            <!-- Libro 1 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <!-- Portada del libro con gradiente -->
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">Cien Años</p>
                            <p class="text-xs font-bold uppercase tracking-wider">de Soledad</p>
                        </div>
                    </div>
                    <!-- Badge de disponibilidad -->
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">Cien Años de Soledad</h3>
                <p class="text-xs text-gray-600 mb-1">Gabriel García Márquez</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Literatura</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.8</span>
                    </div>
                </div>
            </div>

            <!-- Libro 2 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">Breve Historia</p>
                            <p class="text-xs font-bold uppercase tracking-wider">del Tiempo</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">Breve Historia del Tiempo</h3>
                <p class="text-xs text-gray-600 mb-1">Stephen Hawking</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Ciencia</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.7</span>
                    </div>
                </div>
            </div>

            <!-- Libro 3 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">El Principito</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        En préstamo
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">El Principito</h3>
                <p class="text-xs text-gray-600 mb-1">Antoine de Saint-Exupéry</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Literatura</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.9</span>
                    </div>
                </div>
            </div>

            <!-- Libro 4 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-500 via-pink-600 to-fuchsia-700 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm-1 17.93c-3.95-.49-7-3.85-7-7.93V9.41l7-3.03v13.55z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">Sapiens</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">Sapiens: De animales a dioses</h3>
                <p class="text-xs text-gray-600 mb-1">Yuval Noah Harari</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Historia</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.6</span>
                    </div>
                </div>
            </div>

            <!-- Libro 5 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-600 via-gray-700 to-zinc-800 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">Don Quijote</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">Don Quijote de la Mancha</h3>
                <p class="text-xs text-gray-600 mb-1">Miguel de Cervantes</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Literatura</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.5</span>
                    </div>
                </div>
            </div>

            <!-- Libro 6 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-violet-500 via-purple-600 to-indigo-700 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.5 3.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2v14H3v3c0 1.66 1.34 3 3 3h12c1.66 0 3-1.34 3-3V2l-1.5 1.5zM19 19c0 .55-.45 1-1 1s-1-.45-1-1v-3H8V5h11v14z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">1984</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">1984</h3>
                <p class="text-xs text-gray-600 mb-1">George Orwell</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Literatura</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.7</span>
                    </div>
                </div>
            </div>

            <!-- Libro 7 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-lime-500 via-green-600 to-emerald-700 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.09 4.56c-.7-1.03-1.5-1.99-2.4-2.85-.35-.34-.94-.02-.84.46.19.94.39 2.18.39 3.29 0 2.06-1.35 3.73-3.41 3.73-1.54 0-2.8-.93-3.35-2.26-.1-.2-.14-.32-.2-.54-.11-.42-.66-.55-.9-.18C5.3 8.03 4.75 9.88 4.75 11.5c0 4.14 3.36 7.5 7.5 7.5s7.5-3.36 7.5-7.5c0-2.2-.81-4.21-2.16-5.94z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">El Alquimista</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        Disponible
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">El Alquimista</h3>
                <p class="text-xs text-gray-600 mb-1">Paulo Coelho</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Literatura</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.4</span>
                    </div>
                </div>
            </div>

            <!-- Libro 8 -->
            <div class="group cursor-pointer">
                <div class="relative aspect-[2/3] mb-3 overflow-hidden rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-2xl group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-500 via-blue-600 to-cyan-700 flex items-center justify-center p-4">
                        <div class="text-center text-white">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5.5-2.5l7.51-3.49L17.5 6.5 9.99 9.99 6.5 17.5zm5.5-6.6c.61 0 1.1.49 1.1 1.1s-.49 1.1-1.1 1.1-1.1-.49-1.1-1.1.49-1.1 1.1-1.1z"/>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider">El Origen</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        En préstamo
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2 group-hover:text-purple-600 transition-colors">El Origen de las Especies</h3>
                <p class="text-xs text-gray-600 mb-1">Charles Darwin</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-purple-600 font-medium">Ciencia</span>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-xs ml-1">4.6</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="flex items-center justify-center gap-2 mt-12">
            <button class="px-4 py-2 text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-50" disabled>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium shadow-md">1</button>
            <button class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors">2</button>
            <button class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors">3</button>
            <span class="px-2 text-gray-400">...</span>
            <button class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors">10</button>

            <button class="px-4 py-2 text-purple-600 hover:text-purple-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>
