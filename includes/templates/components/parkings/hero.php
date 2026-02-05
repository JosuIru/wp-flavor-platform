<!-- Hero Section - Parkings Module -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="parking-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <rect x="0" y="0" width="2" height="2" fill="currentColor"/>
                </pattern>
            </defs>
            <rect x="0" y="0" width="100%" height="100%" fill="url(#parking-pattern)"/>
        </svg>
    </div>

    <!-- Content -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Text Content -->
            <div class="text-center lg:text-left space-y-6">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Comparte tu plaza de parking</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    Monetiza tu plaza
                    <span class="block text-blue-200 mt-2">cuando no la uses</span>
                </h1>

                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Conectamos propietarios con conductores que necesitan aparcamiento. Gana dinero extra con tu plaza vacía y ayuda a reducir el tráfico en la ciudad.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                    <a href="#parkings-disponibles"
                       class="inline-flex items-center justify-center gap-2 bg-white text-blue-700 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Buscar parking</span>
                    </a>

                    <a href="#ofrecer-plaza"
                       class="inline-flex items-center justify-center gap-2 bg-blue-800/50 backdrop-blur-sm text-white px-8 py-4 rounded-lg font-semibold text-lg border-2 border-white/30 hover:bg-blue-800/70 hover:border-white/50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Ofrecer mi plaza</span>
                    </a>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-white/20">
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold">150+</div>
                        <div class="text-blue-200 text-sm sm:text-base">Plazas activas</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold">1.2K</div>
                        <div class="text-blue-200 text-sm sm:text-base">Reservas totales</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl sm:text-4xl font-bold">4.8★</div>
                        <div class="text-blue-200 text-sm sm:text-base">Valoración media</div>
                    </div>
                </div>
            </div>

            <!-- Visual Element -->
            <div class="relative hidden lg:block">
                <div class="relative z-10">
                    <!-- Parking Icon Illustration -->
                    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/20 shadow-2xl">
                        <div class="space-y-4">
                            <!-- Parking Spot Visual -->
                            <div class="bg-gradient-to-br from-white/20 to-white/5 rounded-2xl p-6 border border-white/20">
                                <svg class="w-full h-48 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 8h4a2 2 0 012 2v0a2 2 0 01-2 2H8V8z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 12v4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>

                            <!-- Feature Pills -->
                            <div class="flex flex-wrap gap-2">
                                <div class="flex items-center gap-2 bg-green-500/20 text-green-100 px-4 py-2 rounded-full text-sm font-medium border border-green-400/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Verificado</span>
                                </div>
                                <div class="flex items-center gap-2 bg-blue-500/20 text-blue-100 px-4 py-2 rounded-full text-sm font-medium border border-blue-400/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>Seguro</span>
                                </div>
                                <div class="flex items-center gap-2 bg-purple-500/20 text-purple-100 px-4 py-2 rounded-full text-sm font-medium border border-purple-400/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span>Inmediato</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute -top-4 -right-4 w-32 h-32 bg-blue-400/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-4 -left-4 w-40 h-40 bg-indigo-400/20 rounded-full blur-3xl"></div>
            </div>
        </div>
    </div>

    <!-- Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-auto" viewBox="0 0 1440 120" fill="none" preserveAspectRatio="none">
            <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z" fill="white"/>
        </svg>
    </div>
</section>
