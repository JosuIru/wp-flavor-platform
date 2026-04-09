<?php
/**
 * Template: Mapa de Composteras
 * @package FlavorChatIA
 *
 * @var string $titulo
 * @var string $descripcion
 * @var array $composteras
 * @var bool $mostrar_filtros
 */
?>

<section class="py-20" style="background: linear-gradient(to bottom, #f5f3f0 0%, #e8e3db 100%);">
    <div class="container mx-auto px-4">
        <!-- Encabezado -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
            </div>
            <h2 class="text-4xl font-bold mb-4" style="color: #2D5016;">
                <?php echo esc_html($titulo ?? 'Mapa de Composteras'); ?>
            </h2>
            <p class="text-lg" style="color: #57534e; max-width: 600px; margin: 0 auto;">
                <?php echo esc_html($descripcion ?? 'Encuentra la compostera más cercana a tu domicilio'); ?>
            </p>
            <div class="w-20 h-1 mx-auto mt-6 rounded-full" style="background: #6B4423;"></div>
        </div>

        <?php if (!empty($mostrar_filtros)): ?>
        <!-- Filtros -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: #2D5016;"><?php echo esc_html__('Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-green-700" style="border-color: #d4c5b9;">
                            <option><?php echo esc_html__('Todos los barrios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Centro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Norte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Sur', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Este', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Oeste', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: #2D5016;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-green-700" style="border-color: #d4c5b9;">
                            <option><?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Llenas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" style="color: #2D5016;"><?php echo esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-green-700" style="border-color: #d4c5b9;">
                            <option><?php echo esc_html__('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Compostadora comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Vermicompostadora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php echo esc_html__('Punto de recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mapa y Lista -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Mapa -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="aspect-video relative" style="background: linear-gradient(135deg, #d4c5b9 0%, #a89f94 100%);">
                        <!-- Placeholder para el mapa -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-24 h-24 mx-auto mb-4" style="color: #6B4423;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-lg font-semibold" style="color: #6B4423;"><?php echo esc_html__('Mapa Interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-sm" style="color: #57534e;"><?php echo esc_html__('Aquí se mostrará el mapa con las ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </div>

                        <!-- Marcadores de ejemplo -->
                        <div class="absolute top-1/4 left-1/4">
                            <div class="relative animate-bounce">
                                <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="absolute top-1/2 right-1/3">
                            <div class="relative animate-bounce" style="animation-delay: 0.2s;">
                                <svg class="w-8 h-8 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Leyenda del mapa -->
                    <div class="p-4 border-t" style="border-color: #e8e3db; background: #fafaf8;">
                        <div class="flex flex-wrap gap-4 justify-center text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background: #16a34a;"></div>
                                <span style="color: #57534e;"><?php echo esc_html__('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background: #d97706;"></div>
                                <span style="color: #57534e;"><?php echo esc_html__('Llena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background: #dc2626;"></div>
                                <span style="color: #57534e;"><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Composteras -->
            <div class="space-y-4">
                <h3 class="text-xl font-bold mb-4" style="color: #2D5016;"><?php echo esc_html__('Composteras Cercanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <!-- Compostera 1 -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-2" style="border-color: #16a34a;">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-lg" style="color: #2D5016;"><?php echo esc_html__('Plaza Mayor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full" style="background: #dcfce7; color: #16a34a;"><?php echo esc_html__('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <p class="text-sm mb-3" style="color: #57534e;"><?php echo esc_html__('Calle Principal, 123', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <div class="flex items-center gap-2 text-sm mb-2" style="color: #6B4423;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span><?php echo esc_html__('45 vecinos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm" style="color: #d97706;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span><?php echo esc_html__('Capacidad: 65%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <button class="w-full mt-4 px-4 py-2 rounded-lg font-semibold text-white transition-colors duration-300" style="background: #16a34a;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            <?php echo esc_html__('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <!-- Compostera 2 -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-2" style="border-color: #d97706;">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-lg" style="color: #2D5016;"><?php echo esc_html__('Parque Verde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full" style="background: #fed7aa; color: #c2410c;"><?php echo esc_html__('Llena', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <p class="text-sm mb-3" style="color: #57534e;"><?php echo esc_html__('Av. del Parque, 45', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <div class="flex items-center gap-2 text-sm mb-2" style="color: #6B4423;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span><?php echo esc_html__('62 vecinos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm" style="color: #dc2626;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span><?php echo esc_html__('Capacidad: 95%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <button class="w-full mt-4 px-4 py-2 rounded-lg font-semibold text-white transition-colors duration-300" style="background: #d97706;" onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">
                            <?php echo esc_html__('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <!-- Compostera 3 -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-2" style="border-color: #16a34a;">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-lg" style="color: #2D5016;"><?php echo esc_html__('Huerto Urbano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full" style="background: #dcfce7; color: #16a34a;"><?php echo esc_html__('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <p class="text-sm mb-3" style="color: #57534e;"><?php echo esc_html__('Calle Verde, 78', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <div class="flex items-center gap-2 text-sm mb-2" style="color: #6B4423;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span><?php echo esc_html__('38 vecinos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm" style="color: #16a34a;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><?php echo esc_html__('Capacidad: 40%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <button class="w-full mt-4 px-4 py-2 rounded-lg font-semibold text-white transition-colors duration-300" style="background: #16a34a;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            <?php echo esc_html__('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-12 bg-white rounded-xl shadow-lg p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(45, 80, 22, 0.1);">
                        <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="font-bold mb-2" style="color: #2D5016;"><?php echo esc_html__('Horarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p class="text-sm" style="color: #57534e;"><?php echo esc_html__('Depósito 24/7', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><br><?php echo esc_html__('Recogida de compost: Sábados 10-12h', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(107, 68, 35, 0.1);">
                        <svg class="w-8 h-8 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="font-bold mb-2" style="color: #2D5016;"><?php echo esc_html__('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p class="text-sm" style="color: #57534e;"><?php echo esc_html__('Consulta las normas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><br><?php echo esc_html__('de uso y el reglamento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(217, 119, 6, 0.1);">
                        <svg class="w-8 h-8 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h4 class="font-bold mb-2" style="color: #2D5016;"><?php echo esc_html__('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p class="text-sm" style="color: #57534e;"><?php echo esc_html__('¿Necesitas ayuda?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><br><?php echo esc_html__('Contacta con el equipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
