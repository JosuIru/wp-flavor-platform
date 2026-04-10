<?php
/**
 * Template: CTA Voluntario
 *
 * Call-to-action para que los vecinos se unan como voluntarios
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo_principal = $data['titulo'] ?? '¿Quieres ser parte del cambio?';
$subtitulo_principal = $data['subtitulo'] ?? 'Únete a nuestra red de voluntarios y ayuda a construir una comunidad más solidaria';
$texto_boton = $data['texto_boton'] ?? 'Hacerme Voluntario';
$link_registro = $data['link_registro'] ?? '#registro-voluntario';
$mostrar_beneficios = $data['mostrar_beneficios'] ?? true;
$mostrar_testimonios = $data['mostrar_testimonios'] ?? true;
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-br from-rose-50 via-pink-50 to-red-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- CTA Principal -->
        <div class="bg-gradient-to-br from-rose-600 via-pink-600 to-red-600 rounded-3xl shadow-2xl overflow-hidden relative">
            <!-- Elementos decorativos -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>

            <div class="relative grid lg:grid-cols-2 gap-8 lg:gap-12 items-center p-8 sm:p-12 lg:p-16">

                <!-- Contenido -->
                <div class="text-white space-y-6">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium border border-white/30">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Voluntariado Vecinal
                    </div>

                    <!-- Título -->
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">
                        <?php echo esc_html($titulo_principal); ?>
                    </h2>

                    <!-- Subtítulo -->
                    <p class="text-lg sm:text-xl text-rose-100 leading-relaxed">
                        <?php echo esc_html($subtitulo_principal); ?>
                    </p>

                    <!-- Características rápidas -->
                    <div class="space-y-3 pt-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-base sm:text-lg">Elige cuándo y cómo ayudar</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-base sm:text-lg">Sin compromiso permanente</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-base sm:text-lg">Conoce a tus vecinos</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-base sm:text-lg">Impacto real en tu comunidad</span>
                        </div>
                    </div>

                    <!-- Botón CTA -->
                    <div class="pt-4">
                        <a
                            href="<?php echo esc_url($link_registro); ?>"
                            class="inline-flex items-center gap-3 px-8 py-5 bg-white text-rose-600 font-bold text-lg rounded-xl hover:bg-rose-50 transition-all duration-200 transform hover:scale-105 shadow-2xl hover:shadow-3xl"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span><?php echo esc_html($texto_boton); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Nota informativa -->
                    <p class="text-sm text-rose-100 pt-2">
                        Proceso de registro simple y rápido. Solo necesitas 2 minutos.
                    </p>
                </div>

                <!-- Ilustración/Estadísticas -->
                <div class="hidden lg:block">
                    <div class="space-y-4">
                        <!-- Tarjeta 1 -->
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-white">
                                    <div class="text-3xl font-bold">280+</div>
                                    <div class="text-rose-100">Voluntarios activos</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 2 -->
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all duration-300 ml-8">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </div>
                                <div class="text-white">
                                    <div class="text-3xl font-bold">1,450+</div>
                                    <div class="text-rose-100">Ayudas realizadas</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 3 -->
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-white">
                                    <div class="text-3xl font-bold">98%</div>
                                    <div class="text-rose-100">Satisfacción</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Beneficios de ser voluntario -->
        <?php if ($mostrar_beneficios): ?>
            <div class="mt-16 grid md:grid-cols-3 gap-6 lg:gap-8">

                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-2 border-rose-100 hover:border-rose-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Flexibilidad Total</h3>
                    <p class="text-gray-600">
                        Tú decides cuándo y cuánto tiempo dedicar. Ayuda cuando puedas, sin presiones ni obligaciones.
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-2 border-rose-100 hover:border-rose-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Conexión Comunitaria</h3>
                    <p class="text-gray-600">
                        Conoce a tus vecinos, crea lazos significativos y forma parte de una red de apoyo mutuo.
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-2 border-rose-100 hover:border-rose-200">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Impacto Real</h3>
                    <p class="text-gray-600">
                        Cada pequeña acción cuenta. Ayuda a crear una comunidad más unida, solidaria y feliz.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Testimonios -->
        <?php if ($mostrar_testimonios): ?>
            <div class="mt-16">
                <div class="text-center mb-10">
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                        Lo que dicen nuestros voluntarios
                    </h3>
                    <p class="text-gray-600">Experiencias reales de quienes ya forman parte de la red</p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 lg:gap-8">

                    <!-- Testimonio 1 -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-1 mb-4">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600 mb-4 italic">
                            "Ayudar a mis vecinos me ha dado mucha más satisfacción de la que esperaba. Es increíble cómo pequeñas acciones pueden cambiar el día de alguien."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                M
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">María López</div>
                                <div class="text-sm text-gray-500">Voluntaria desde hace 6 meses</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonio 2 -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-1 mb-4">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600 mb-4 italic">
                            "He conocido a personas maravillosas y me siento parte de una comunidad de verdad. Lo recomiendo totalmente."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                J
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Javier Ruiz</div>
                                <div class="text-sm text-gray-500">Voluntario desde hace 1 año</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonio 3 -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center gap-1 mb-4">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600 mb-4 italic">
                            "La flexibilidad es lo mejor. Ayudo cuando puedo y nunca me siento presionada. Es perfecto para mi ritmo de vida."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                A
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Ana García</div>
                                <div class="text-sm text-gray-500">Voluntaria desde hace 3 meses</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>
