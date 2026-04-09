<?php
/**
 * Template: Cómo Funciona
 *
 * @var string $titulo
 * @var string $paso1_titulo
 * @var string $paso1_texto
 * @var string $paso2_titulo
 * @var string $paso2_texto
 * @var string $paso3_titulo
 * @var string $paso3_texto
 * @var string $component_classes
 */

// Defaults
$titulo = $titulo ?? 'Cómo Funciona';
$paso1_titulo = $paso1_titulo ?? 'Busca tu viaje';
$paso1_texto = $paso1_texto ?? 'Introduce tu origen, destino y fecha para encontrar viajes disponibles.';
$paso2_titulo = $paso2_titulo ?? 'Reserva tu plaza';
$paso2_texto = $paso2_texto ?? 'Selecciona el viaje que mejor se adapte y reserva tu plaza al instante.';
$paso3_titulo = $paso3_titulo ?? '¡A viajar!';
$paso3_texto = $paso3_texto ?? 'Contacta con el conductor y disfruta del viaje compartido.';
$component_classes = $component_classes ?? '';
?>

<section class="py-20 bg-white <?php echo esc_attr($component_classes); ?>">
    <div class="container mx-auto px-4">
        <!-- Título -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo); ?>
            </h2>
            <div class="w-20 h-1 bg-blue-600 mx-auto rounded-full"></div>
        </div>

        <!-- Pasos -->
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Línea conectora (desktop) -->
                <div class="hidden md:block absolute top-12 left-0 right-0 h-1 bg-blue-200" style="width: calc(100% - 8rem); margin-left: 4rem;"></div>

                <!-- Paso 1 -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Número -->
                        <div class="relative z-10 w-24 h-24 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <span class="text-3xl font-bold text-white">1</span>
                        </div>

                        <!-- Icono -->
                        <div class="mb-4">
                            <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Título -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso1_titulo); ?>
                        </h3>

                        <!-- Texto -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso1_texto); ?>
                        </p>
                    </div>
                </div>

                <!-- Paso 2 -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Número -->
                        <div class="relative z-10 w-24 h-24 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <span class="text-3xl font-bold text-white">2</span>
                        </div>

                        <!-- Icono -->
                        <div class="mb-4">
                            <svg class="w-16 h-16 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Título -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso2_titulo); ?>
                        </h3>

                        <!-- Texto -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso2_texto); ?>
                        </p>
                    </div>
                </div>

                <!-- Paso 3 -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Número -->
                        <div class="relative z-10 w-24 h-24 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <span class="text-3xl font-bold text-white">3</span>
                        </div>

                        <!-- Icono -->
                        <div class="mb-4">
                            <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                            </svg>
                        </div>

                        <!-- Título -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso3_titulo); ?>
                        </h3>

                        <!-- Texto -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso3_texto); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Beneficios adicionales -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2">100%</div>
                    <div class="text-gray-700"><?php _e('Seguro y Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-green-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">-40%</div>
                    <div class="text-gray-700"><?php _e('Ahorro Medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-purple-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">24/7</div>
                    <div class="text-gray-700"><?php _e('Soporte Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
