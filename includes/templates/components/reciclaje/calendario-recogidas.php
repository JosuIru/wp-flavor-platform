<!-- Calendario de Recogidas Section -->
<section id="calendario-recogidas" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 rounded-full mb-4">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm font-semibold text-green-800">Planifica tu Reciclaje</span>
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Calendario de <span class="text-green-600">Recogidas</span>
            </h2>
            <p class="text-lg md:text-xl text-gray-600">
                Consulta los días y horarios de recogida de cada tipo de residuo en tu zona. Recibe recordatorios para no olvidar ninguna fecha.
            </p>
        </div>

        <!-- Address Selector -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 md:p-8 mb-12 border border-green-200">
            <div class="max-w-2xl mx-auto">
                <label for="address-select" class="block text-lg font-bold text-gray-900 mb-4 text-center">
                    Selecciona tu dirección
                </label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input
                            type="text"
                            id="address-select"
                            placeholder="Introduce tu dirección o código postal..."
                            class="w-full px-6 py-4 pl-12 bg-white border-2 border-green-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all text-lg"
                        >
                        <svg class="absolute left-4 top-4.5 w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <button class="px-8 py-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg whitespace-nowrap">
                        Buscar
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-3 text-center">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Tu privacidad es importante. No guardamos tu dirección.
                </p>
            </div>
        </div>

        <!-- Current Location Info -->
        <div class="bg-blue-50 border-l-4 border-blue-600 rounded-lg p-4 mb-8">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-blue-900">Mostrando horarios para: <span class="text-blue-700">Basauri Centro, 48970</span></p>
                    <p class="text-sm text-blue-700 mt-1">Zona de recogida: Sector 2 - Barrio San Miguel</p>
                </div>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="grid lg:grid-cols-3 gap-8 mb-12">
            <!-- Calendar View -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <!-- Calendar Header -->
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <div class="flex items-center justify-between text-white">
                            <button class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <h3 class="text-2xl font-bold">Enero 2026</h3>
                            <button class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Body -->
                    <div class="p-6">
                        <!-- Weekday Headers -->
                        <div class="grid grid-cols-7 gap-2 mb-4">
                            <div class="text-center text-sm font-bold text-gray-700 py-2">L</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">M</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">X</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">J</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">V</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">S</div>
                            <div class="text-center text-sm font-bold text-gray-700 py-2">D</div>
                        </div>

                        <!-- Calendar Days -->
                        <div class="grid grid-cols-7 gap-2">
                            <!-- Empty cells for month start -->
                            <div class="aspect-square"></div>
                            <div class="aspect-square"></div>
                            <div class="aspect-square"></div>

                            <!-- Day 1 - Thursday -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">1</span>
                            </div>

                            <!-- Day 2 - Friday - Orgánico -->
                            <div class="aspect-square bg-orange-50 rounded-lg p-2 relative border-2 border-orange-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">2</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-orange-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 3 - Saturday - Envases -->
                            <div class="aspect-square bg-yellow-50 rounded-lg p-2 relative border-2 border-yellow-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">3</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-yellow-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 4 - Sunday -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">4</span>
                            </div>

                            <!-- Week 2 -->
                            <!-- Day 5 - Monday - Papel -->
                            <div class="aspect-square bg-blue-50 rounded-lg p-2 relative border-2 border-blue-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">5</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-blue-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 6-7 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">6</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">7</span>
                            </div>

                            <!-- Day 8 - Thursday - Orgánico + Vidrio -->
                            <div class="aspect-square bg-gradient-to-br from-orange-50 to-green-50 rounded-lg p-2 relative border-2 border-green-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">8</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-orange-500 rounded"></div>
                                    <div class="flex-1 h-1 bg-green-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 9 - Friday - Envases -->
                            <div class="aspect-square bg-yellow-50 rounded-lg p-2 relative border-2 border-yellow-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">9</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-yellow-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 10-11 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">10</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">11</span>
                            </div>

                            <!-- Week 3 -->
                            <!-- Day 12 - Monday - Papel -->
                            <div class="aspect-square bg-blue-50 rounded-lg p-2 relative border-2 border-blue-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">12</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-blue-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 13-14 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">13</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">14</span>
                            </div>

                            <!-- Day 15 - Thursday - Orgánico -->
                            <div class="aspect-square bg-orange-50 rounded-lg p-2 relative border-2 border-orange-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">15</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-orange-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 16 - Friday - Envases -->
                            <div class="aspect-square bg-yellow-50 rounded-lg p-2 relative border-2 border-yellow-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">16</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-yellow-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 17-18 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">17</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">18</span>
                            </div>

                            <!-- Week 4 -->
                            <!-- Day 19 - Monday - Papel -->
                            <div class="aspect-square bg-blue-50 rounded-lg p-2 relative border-2 border-blue-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">19</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-blue-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 20-21 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">20</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">21</span>
                            </div>

                            <!-- Day 22 - Thursday - Orgánico + Vidrio -->
                            <div class="aspect-square bg-gradient-to-br from-orange-50 to-green-50 rounded-lg p-2 relative border-2 border-green-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">22</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-orange-500 rounded"></div>
                                    <div class="flex-1 h-1 bg-green-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 23 - Friday - Envases -->
                            <div class="aspect-square bg-yellow-50 rounded-lg p-2 relative border-2 border-yellow-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">23</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-yellow-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 24-25 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">24</span>
                            </div>
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">25</span>
                            </div>

                            <!-- Week 5 -->
                            <!-- Day 26 - Monday - Papel -->
                            <div class="aspect-square bg-blue-50 rounded-lg p-2 relative border-2 border-blue-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">26</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-blue-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Days 27-28 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">27</span>
                            </div>
                            <div class="aspect-square bg-white rounded-lg p-2 relative border-2 border-green-500 shadow-lg ring-2 ring-green-200">
                                <span class="text-sm font-bold text-green-600">28</span>
                                <div class="absolute top-1 right-1">
                                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                </div>
                                <div class="absolute -top-2 -right-2 bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-lg">
                                    Hoy
                                </div>
                            </div>

                            <!-- Day 29 - Thursday - Orgánico -->
                            <div class="aspect-square bg-orange-50 rounded-lg p-2 relative border-2 border-orange-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">29</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-orange-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 30 - Friday - Envases -->
                            <div class="aspect-square bg-yellow-50 rounded-lg p-2 relative border-2 border-yellow-300 cursor-pointer hover:shadow-md transition-shadow">
                                <span class="text-sm font-semibold text-gray-900">30</span>
                                <div class="absolute bottom-1 left-1 right-1 flex gap-0.5">
                                    <div class="flex-1 h-1 bg-yellow-500 rounded"></div>
                                </div>
                            </div>

                            <!-- Day 31 -->
                            <div class="aspect-square bg-gray-50 rounded-lg p-2 relative border border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">31</span>
                            </div>
                        </div>

                        <!-- Calendar Legend -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3">Leyenda</h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-2 bg-yellow-500 rounded"></div>
                                    <span class="text-gray-700">Envases (Amarillo)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-2 bg-blue-500 rounded"></div>
                                    <span class="text-gray-700">Papel (Azul)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-2 bg-green-500 rounded"></div>
                                    <span class="text-gray-700">Vidrio (Verde)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-2 bg-orange-500 rounded"></div>
                                    <span class="text-gray-700">Orgánico (Marrón)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar - Next Collections & Reminders -->
            <div class="space-y-6">
                <!-- Next Collections -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Próximas Recogidas
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Collection Item 1 - Tomorrow -->
                        <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-sm font-bold text-orange-900">Mañana</p>
                                    <p class="text-xs text-orange-700">Jueves, 29 Enero</p>
                                </div>
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 mb-1">Orgánico</p>
                            <p class="text-xs text-gray-600">Recogida: 07:00 - 09:00</p>
                            <p class="text-xs text-orange-700 font-medium mt-2">⏰ Recordatorio activado</p>
                        </div>

                        <!-- Collection Item 2 -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-sm font-bold text-yellow-900">En 2 días</p>
                                    <p class="text-xs text-yellow-700">Viernes, 30 Enero</p>
                                </div>
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 mb-1">Envases</p>
                            <p class="text-xs text-gray-600">Recogida: 07:00 - 09:00</p>
                            <button class="text-xs text-yellow-700 font-medium mt-2 hover:text-yellow-900">+ Activar recordatorio</button>
                        </div>

                        <!-- Collection Item 3 -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-sm font-bold text-blue-900">En 5 días</p>
                                    <p class="text-xs text-blue-700">Lunes, 2 Febrero</p>
                                </div>
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 mb-1">Papel y Cartón</p>
                            <p class="text-xs text-gray-600">Recogida: 07:00 - 09:00</p>
                            <button class="text-xs text-blue-700 font-medium mt-2 hover:text-blue-900">+ Activar recordatorio</button>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Recordatorios</h4>
                            <p class="text-xs text-gray-600">Gestiona tus notificaciones</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Recordatorio el día anterior</span>
                            <div class="relative">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Email resumen semanal</span>
                            <div class="relative">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Notificación push</span>
                            <div class="relative">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </div>
                        </label>
                    </div>

                    <button class="mt-6 w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg">
                        Guardar Preferencias
                    </button>
                </div>

                <!-- Download Calendar -->
                <div class="bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl p-6 text-white">
                    <div class="text-center">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Exportar Calendario</h4>
                        <p class="text-sm text-white/90 mb-4">Añade las fechas a tu calendario personal</p>
                        <button class="w-full px-4 py-3 bg-white hover:bg-gray-100 text-green-600 font-semibold rounded-xl transition-all shadow-lg">
                            Descargar .ics
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Schedule Table -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Horarios por Tipo de Residuo
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-900">Tipo de Residuo</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-900">Días de Recogida</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-900">Horario</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-900">Frecuencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr class="hover:bg-yellow-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900">Envases (Amarillo)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">Martes, Viernes</td>
                            <td class="px-6 py-4 text-gray-700">07:00 - 09:00</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm font-medium rounded-full">2 veces/semana</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <div class="w-4 h-4 bg-blue-500 rounded"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900">Papel y Cartón (Azul)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">Lunes</td>
                            <td class="px-6 py-4 text-gray-700">07:00 - 09:00</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">Semanal</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-green-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <div class="w-4 h-4 bg-green-500 rounded"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900">Vidrio (Verde)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">Jueves (quincenal)</td>
                            <td class="px-6 py-4 text-gray-700">07:00 - 09:00</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">Quincenal</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-orange-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <div class="w-4 h-4 bg-orange-500 rounded"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900">Orgánico (Marrón)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">Jueves</td>
                            <td class="px-6 py-4 text-gray-700">07:00 - 09:00</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 text-sm font-medium rounded-full">Semanal</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                                    </div>
                                    <span class="font-semibold text-gray-900">Resto (Gris)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">Lunes, Miércoles, Viernes</td>
                            <td class="px-6 py-4 text-gray-700">07:00 - 09:00</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-gray-200 text-gray-700 text-sm font-medium rounded-full">3 veces/semana</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="mt-8 bg-amber-50 border-l-4 border-amber-500 rounded-lg p-6">
            <div class="flex items-start gap-4">
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="text-lg font-bold text-amber-900 mb-2">Notas Importantes</h4>
                    <ul class="space-y-2 text-sm text-amber-800">
                        <li class="flex items-start gap-2">
                            <span class="mt-1">•</span>
                            <span>Los residuos deben depositarse la noche anterior o antes de las 07:00 del día de recogida</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1">•</span>
                            <span>En días festivos, la recogida se realizará el siguiente día laborable</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1">•</span>
                            <span>Los residuos especiales deben llevarse al punto limpio, no se recogen en domicilio</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1">•</span>
                            <span>Para recogida de muebles y enseres, solicitar cita previa llamando al 944 123 456</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
