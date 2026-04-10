<?php
/**
 * Template: Como Participar
 *
 * Seccion de 3 pasos explicando el proceso de participacion ciudadana:
 * Propon, Debate y Vota.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Como Participar'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Participar es sencillo. En tres pasos puedes contribuir al futuro de tu comunidad.'); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Pasos -->
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Linea conectora (desktop) -->
                <div class="hidden md:block absolute top-16 left-0 right-0 h-0.5 bg-amber-200" style="width: calc(100% - 10rem); margin-left: 5rem;"></div>

                <!-- Paso 1: Propon -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Icono en circulo -->
                        <div class="relative z-10 w-32 h-32 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>

                        <!-- Numero -->
                        <div class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 text-amber-700 rounded-full text-sm font-bold mb-3">
                            1
                        </div>

                        <!-- Titulo -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso1_titulo ?? 'Propon'); ?>
                        </h3>

                        <!-- Descripcion -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso1_texto ?? 'Comparte tu idea para mejorar el barrio o la ciudad. Describe tu propuesta, anade detalles y sube imagenes de apoyo.'); ?>
                        </p>
                    </div>
                </div>

                <!-- Paso 2: Debate -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Icono en circulo -->
                        <div class="relative z-10 w-32 h-32 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>

                        <!-- Numero -->
                        <div class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-700 rounded-full text-sm font-bold mb-3">
                            2
                        </div>

                        <!-- Titulo -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso2_titulo ?? 'Debate'); ?>
                        </h3>

                        <!-- Descripcion -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso2_texto ?? 'Comenta y discute las propuestas con tus vecinos. Aporta mejoras, haz preguntas y enriquece las ideas entre todos.'); ?>
                        </p>
                    </div>
                </div>

                <!-- Paso 3: Vota -->
                <div class="relative">
                    <div class="flex flex-col items-center text-center">
                        <!-- Icono en circulo -->
                        <div class="relative z-10 w-32 h-32 bg-gradient-to-br from-amber-500 to-orange-500 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>

                        <!-- Numero -->
                        <div class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 text-amber-700 rounded-full text-sm font-bold mb-3">
                            3
                        </div>

                        <!-- Titulo -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            <?php echo esc_html($paso3_titulo ?? 'Vota'); ?>
                        </h3>

                        <!-- Descripcion -->
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($paso3_texto ?? 'Vota las propuestas que consideres mas importantes. Cada ciudadano tiene voz y voto para decidir las prioridades.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
