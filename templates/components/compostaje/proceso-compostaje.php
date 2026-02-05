<?php
/**
 * Template: Proceso de Compostaje
 * @package FlavorChatIA
 *
 * @var string $titulo
 * @var string $descripcion
 * @var string $fase1_titulo
 * @var string $fase1_texto
 * @var string $fase2_titulo
 * @var string $fase2_texto
 * @var string $fase3_titulo
 * @var string $fase3_texto
 * @var string $fase4_titulo
 * @var string $fase4_texto
 */
?>

<section class="py-20" style="background: linear-gradient(to bottom, #fafaf8 0%, #f5f3f0 100%);">
    <div class="container mx-auto px-4">
        <!-- Encabezado -->
        <div class="text-center mb-16">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <h2 class="text-4xl font-bold mb-4" style="color: #2D5016;">
                <?php echo esc_html($titulo ?? 'El Proceso de Compostaje'); ?>
            </h2>
            <p class="text-lg" style="color: #57534e; max-width: 700px; margin: 0 auto;">
                <?php echo esc_html($descripcion ?? 'Descubre cómo los residuos orgánicos se transforman en abono natural'); ?>
            </p>
            <div class="w-20 h-1 mx-auto mt-6 rounded-full" style="background: #6B4423;"></div>
        </div>

        <!-- Proceso en Fases -->
        <div class="max-w-6xl mx-auto">
            <!-- Línea de tiempo vertical (móvil) y horizontal (desktop) -->
            <div class="relative">
                <!-- Línea conectora para desktop -->
                <div class="hidden md:block absolute top-24 left-0 right-0 h-1 z-0" style="background: linear-gradient(to right, #6B4423 0%, #2D5016 50%, #16a34a 100%); margin: 0 10%;"></div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-4 relative z-10">
                    <!-- Fase 1: Depositar -->
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Número y círculo -->
                            <div class="relative w-24 h-24 mb-6 transform hover:scale-110 transition-all duration-300">
                                <div class="absolute inset-0 rounded-full animate-pulse" style="background: rgba(107, 68, 35, 0.2);"></div>
                                <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl" style="background: linear-gradient(135deg, #6B4423 0%, #92400e 100%);">
                                    <span class="text-3xl font-bold text-white">1</span>
                                </div>
                            </div>

                            <!-- Icono -->
                            <div class="mb-4 w-20 h-20 rounded-full flex items-center justify-center" style="background: rgba(107, 68, 35, 0.1);">
                                <svg class="w-10 h-10 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>

                            <!-- Título -->
                            <h3 class="text-xl font-bold mb-3" style="color: #6B4423;">
                                <?php echo esc_html($fase1_titulo ?? 'Depositar'); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-sm leading-relaxed mb-4" style="color: #57534e;">
                                <?php echo esc_html($fase1_texto ?? 'Deposita tus residuos orgánicos en la compostera comunitaria siguiendo las indicaciones.'); ?>
                            </p>

                            <!-- Detalles -->
                            <div class="w-full bg-white rounded-lg p-4 shadow-md border" style="border-color: #d4c5b9;">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold" style="color: #6B4423;">Duración: Inmediato</span>
                                </div>
                                <ul class="text-xs space-y-1 text-left" style="color: #78716c;">
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Trocea los residuos</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Evita plásticos</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Mezcla bien</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Fase 2: Descomposición Inicial -->
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Número y círculo -->
                            <div class="relative w-24 h-24 mb-6 transform hover:scale-110 transition-all duration-300">
                                <div class="absolute inset-0 rounded-full animate-pulse" style="background: rgba(217, 119, 6, 0.2); animation-delay: 0.2s;"></div>
                                <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                                    <span class="text-3xl font-bold text-white">2</span>
                                </div>
                            </div>

                            <!-- Icono -->
                            <div class="mb-4 w-20 h-20 rounded-full flex items-center justify-center" style="background: rgba(217, 119, 6, 0.1);">
                                <svg class="w-10 h-10 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>

                            <!-- Título -->
                            <h3 class="text-xl font-bold mb-3" style="color: #d97706;">
                                <?php echo esc_html($fase2_titulo ?? 'Descomposición'); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-sm leading-relaxed mb-4" style="color: #57534e;">
                                <?php echo esc_html($fase2_texto ?? 'Los microorganismos comienzan a descomponer la materia orgánica generando calor.'); ?>
                            </p>

                            <!-- Detalles -->
                            <div class="w-full bg-white rounded-lg p-4 shadow-md border" style="border-color: #fed7aa;">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold" style="color: #d97706;">Duración: 2-4 semanas</span>
                                </div>
                                <ul class="text-xs space-y-1 text-left" style="color: #78716c;">
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Temperatura 40-60°C</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Alta actividad microbiana</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Vapor visible</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Fase 3: Maduración -->
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Número y círculo -->
                            <div class="relative w-24 h-24 mb-6 transform hover:scale-110 transition-all duration-300">
                                <div class="absolute inset-0 rounded-full animate-pulse" style="background: rgba(45, 80, 22, 0.2); animation-delay: 0.4s;"></div>
                                <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl" style="background: linear-gradient(135deg, #2D5016 0%, #14532d 100%);">
                                    <span class="text-3xl font-bold text-white">3</span>
                                </div>
                            </div>

                            <!-- Icono -->
                            <div class="mb-4 w-20 h-20 rounded-full flex items-center justify-center" style="background: rgba(45, 80, 22, 0.1);">
                                <svg class="w-10 h-10 text-green-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>

                            <!-- Título -->
                            <h3 class="text-xl font-bold mb-3" style="color: #2D5016;">
                                <?php echo esc_html($fase3_titulo ?? 'Maduración'); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-sm leading-relaxed mb-4" style="color: #57534e;">
                                <?php echo esc_html($fase3_texto ?? 'El compost se estabiliza, enfría y adquiere color oscuro y olor a tierra.'); ?>
                            </p>

                            <!-- Detalles -->
                            <div class="w-full bg-white rounded-lg p-4 shadow-md border" style="border-color: #bbf7d0;">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-green-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold" style="color: #2D5016;">Duración: 4-8 semanas</span>
                                </div>
                                <ul class="text-xs space-y-1 text-left" style="color: #78716c;">
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Temperatura desciende</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Color marrón oscuro</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Textura desmenuzable</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Fase 4: Compost Listo -->
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Número y círculo -->
                            <div class="relative w-24 h-24 mb-6 transform hover:scale-110 transition-all duration-300">
                                <div class="absolute inset-0 rounded-full animate-pulse" style="background: rgba(22, 163, 74, 0.2); animation-delay: 0.6s;"></div>
                                <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
                                    <span class="text-3xl font-bold text-white">4</span>
                                </div>
                            </div>

                            <!-- Icono -->
                            <div class="mb-4 w-20 h-20 rounded-full flex items-center justify-center" style="background: rgba(22, 163, 74, 0.1);">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>

                            <!-- Título -->
                            <h3 class="text-xl font-bold mb-3" style="color: #16a34a;">
                                <?php echo esc_html($fase4_titulo ?? 'Compost Listo'); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-sm leading-relaxed mb-4" style="color: #57534e;">
                                <?php echo esc_html($fase4_texto ?? 'El compost está listo para usar como abono natural rico en nutrientes.'); ?>
                            </p>

                            <!-- Detalles -->
                            <div class="w-full bg-white rounded-lg p-4 shadow-md border" style="border-color: #86efac;">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold" style="color: #16a34a;">Total: 2-3 meses</span>
                                </div>
                                <ul class="text-xs space-y-1 text-left" style="color: #78716c;">
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Olor a tierra húmeda</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Rico en nutrientes</span>
                                    </li>
                                    <li class="flex items-start gap-1">
                                        <span>•</span>
                                        <span>Listo para usar</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Factores que influyen en el proceso -->
            <div class="mt-20 bg-white rounded-2xl shadow-xl p-8 md:p-12 border-2" style="border-color: #d4c5b9;">
                <h3 class="text-3xl font-bold mb-8 text-center" style="color: #2D5016;">Factores Clave del Compostaje</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Aireación -->
                    <div class="text-center p-6 rounded-xl transition-all duration-300 hover:shadow-lg" style="background: rgba(45, 80, 22, 0.05);">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(45, 80, 22, 0.1);">
                            <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                            </svg>
                        </div>
                        <h4 class="font-bold mb-2" style="color: #2D5016;">Aireación</h4>
                        <p class="text-sm" style="color: #57534e;">Oxígeno necesario para microorganismos aeróbicos</p>
                    </div>

                    <!-- Humedad -->
                    <div class="text-center p-6 rounded-xl transition-all duration-300 hover:shadow-lg" style="background: rgba(6, 182, 212, 0.05);">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(6, 182, 212, 0.1);">
                            <svg class="w-8 h-8 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h4 class="font-bold mb-2" style="color: #0e7490;">Humedad</h4>
                        <p class="text-sm" style="color: #57534e;">Nivel óptimo del 40-60% para la descomposición</p>
                    </div>

                    <!-- Temperatura -->
                    <div class="text-center p-6 rounded-xl transition-all duration-300 hover:shadow-lg" style="background: rgba(217, 119, 6, 0.05);">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(217, 119, 6, 0.1);">
                            <svg class="w-8 h-8 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h4 class="font-bold mb-2" style="color: #c2410c;">Temperatura</h4>
                        <p class="text-sm" style="color: #57534e;">Entre 40-60°C en fase activa elimina patógenos</p>
                    </div>

                    <!-- pH Equilibrado -->
                    <div class="text-center p-6 rounded-xl transition-all duration-300 hover:shadow-lg" style="background: rgba(107, 68, 35, 0.05);">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(107, 68, 35, 0.1);">
                            <svg class="w-8 h-8 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h4 class="font-bold mb-2" style="color: #92400e;">pH Equilibrado</h4>
                        <p class="text-sm" style="color: #57534e;">Nivel neutro (6-8) favorece la actividad biológica</p>
                    </div>
                </div>
            </div>

            <!-- Beneficios del compost -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-8 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="text-xl font-bold mb-2">Para el Medio Ambiente</h4>
                    <p class="text-sm opacity-90">Reduce residuos en vertederos y emisiones de metano</p>
                </div>

                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-8 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="text-xl font-bold mb-2">Para la Economía</h4>
                    <p class="text-sm opacity-90">Ahorra en fertilizantes químicos y gestión de residuos</p>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-green-700 rounded-xl p-8 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <h4 class="text-xl font-bold mb-2">Para el Suelo</h4>
                    <p class="text-sm opacity-90">Mejora la estructura, fertilidad y retención de agua</p>
                </div>
            </div>
        </div>
    </div>
</section>
