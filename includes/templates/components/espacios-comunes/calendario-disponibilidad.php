<!-- Calendario de Disponibilidad - Espacios Comunes -->
<section id="calendario" class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Calendario de Disponibilidad
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Consulta la disponibilidad en tiempo real y selecciona el horario que mejor se adapte a tus necesidades.
            </p>
        </div>

        <div class="max-w-6xl mx-auto">
            <!-- Controls and Filters -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Space Selector -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Seleccionar Espacio</label>
                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            <option>Todos los espacios</option>
                            <option>Sala de Reuniones A</option>
                            <option>Espacio Coworking</option>
                            <option>Sala Multiusos</option>
                            <option>Taller Creativo</option>
                            <option>Sala Privada</option>
                            <option>Zona de Descanso</option>
                        </select>
                    </div>

                    <!-- Month Selector -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mes</label>
                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            <option>Enero 2026</option>
                            <option selected>Febrero 2026</option>
                            <option>Marzo 2026</option>
                            <option>Abril 2026</option>
                            <option>Mayo 2026</option>
                        </select>
                    </div>

                    <!-- View Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vista</label>
                        <div class="flex gap-2">
                            <button class="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors duration-200">
                                Semana
                            </button>
                            <button class="flex-1 px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
                                Mes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                <!-- Calendar Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 flex items-center justify-between">
                    <button class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h3 class="text-xl font-bold">Semana del 3 al 9 de Febrero, 2026</h3>
                    <button class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Days Header -->
                <div class="grid grid-cols-8 border-b border-gray-200 bg-gray-50">
                    <div class="p-3 text-center font-semibold text-gray-600 text-sm border-r border-gray-200">
                        Hora
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900">Lun</div>
                        <div class="text-sm text-gray-600">3 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900">Mar</div>
                        <div class="text-sm text-gray-600">4 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900">Mié</div>
                        <div class="text-sm text-gray-600">5 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900">Jue</div>
                        <div class="text-sm text-gray-600">6 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900">Vie</div>
                        <div class="text-sm text-gray-600">7 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900 text-purple-600">Sáb</div>
                        <div class="text-sm text-purple-600">8 Feb</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="font-semibold text-gray-900 text-purple-600">Dom</div>
                        <div class="text-sm text-purple-600">9 Feb</div>
                    </div>
                </div>

                <!-- Time Slots -->
                <div class="max-h-96 overflow-y-auto">
                    <!-- 9:00 AM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            9:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>

                    <!-- 10:00 AM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            10:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-2 rounded cursor-pointer hover:bg-yellow-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-yellow-800">Pendiente</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>

                    <!-- 11:00 AM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            11:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>

                    <!-- 12:00 PM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            12:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>

                    <!-- 14:00 PM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            14:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                    </div>

                    <!-- 15:00 PM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            15:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                    </div>

                    <!-- 16:00 PM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            16:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-2 rounded cursor-pointer hover:bg-yellow-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-yellow-800">Pendiente</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                    </div>

                    <!-- 17:00 PM Row -->
                    <div class="grid grid-cols-8 border-b border-gray-100 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            17:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>

                    <!-- 18:00 PM Row -->
                    <div class="grid grid-cols-8 hover:bg-gray-50">
                        <div class="p-4 text-center font-medium text-gray-600 border-r border-gray-200 bg-gray-50">
                            18:00
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-red-100 border-l-4 border-red-500 p-2 rounded">
                                <div class="text-xs font-semibold text-red-800">Reservado</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2 border-r border-gray-100">
                            <div class="bg-green-100 border-l-4 border-green-500 p-2 rounded cursor-pointer hover:bg-green-200 transition-colors duration-200">
                                <div class="text-xs font-semibold text-green-800">Disponible</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <div class="bg-gray-200 border-l-4 border-gray-400 p-2 rounded">
                                <div class="text-xs font-semibold text-gray-600">Cerrado</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 bg-gray-50 rounded-lg p-6">
                <h4 class="font-semibold text-gray-900 mb-4">Leyenda</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Disponible</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Reservado</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Pendiente de Confirmación</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gray-400 rounded mr-2"></div>
                        <span class="text-sm text-gray-700">Cerrado</span>
                    </div>
                </div>
            </div>

            <!-- Action Info -->
            <div class="mt-6 bg-purple-50 border border-purple-200 rounded-lg p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-purple-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-purple-900 mb-2">Cómo Reservar</h4>
                        <p class="text-purple-800 text-sm">
                            Haz clic en cualquier horario disponible (verde) para iniciar tu reserva. Recibirás una confirmación inmediata por correo electrónico con todos los detalles de tu reserva.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
