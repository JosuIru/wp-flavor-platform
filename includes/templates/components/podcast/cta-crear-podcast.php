<?php
/**
 * Template: CTA Crear Podcast - Call-to-Action para crear podcasts
 *
 * Sección motivacional para animar a usuarios a crear sus propios podcasts
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Podcast
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_principal = $args['titulo'] ?? '¿Tienes una Historia que Contar?';
$subtitulo_cta = $args['subtitulo'] ?? 'Crea tu propio podcast y comparte tu voz con la comunidad';
$descripcion_cta = $args['descripcion'] ?? 'No necesitas experiencia profesional. Te proporcionamos las herramientas y el apoyo necesario para que puedas grabar, editar y publicar tu podcast de manera fácil y accesible.';
$boton_texto = $args['boton_texto'] ?? 'Comenzar Mi Podcast';
$boton_url = $args['boton_url'] ?? '#crear-podcast';
$boton_secundario_texto = $args['boton_secundario_texto'] ?? 'Ver Guía';
$boton_secundario_url = $args['boton_secundario_url'] ?? '#guia';
$mostrar_caracteristicas = $args['mostrar_caracteristicas'] ?? true;
$fondo_estilo = $args['fondo_estilo'] ?? 'gradiente'; // 'gradiente', 'solido', 'patron'
?>

<section class="relative py-16 sm:py-20 lg:py-24 overflow-hidden
    <?php echo $fondo_estilo === 'gradiente' ? 'bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600' : ''; ?>
    <?php echo $fondo_estilo === 'solido' ? 'bg-purple-700' : ''; ?>
    <?php echo $fondo_estilo === 'patron' ? 'bg-purple-800' : ''; ?>">

    <!-- Patrón de fondo decorativo -->
    <?php if ($fondo_estilo === 'patron'): ?>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
    <?php endif; ?>

    <!-- Elementos decorativos flotantes -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-20 left-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-80 h-80 bg-pink-300/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-purple-400/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Contenido principal -->
            <div class="text-center lg:text-left space-y-8">
                <!-- Badge decorativo -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <span>Herramientas Gratuitas</span>
                </div>

                <!-- Título principal -->
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
                    <?php echo esc_html($titulo_principal); ?>
                </h2>

                <!-- Subtítulo -->
                <p class="text-xl sm:text-2xl text-purple-100 font-medium">
                    <?php echo esc_html($subtitulo_cta); ?>
                </p>

                <!-- Descripción -->
                <p class="text-lg text-purple-100 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    <?php echo esc_html($descripcion_cta); ?>
                </p>

                <!-- Botones CTA -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($boton_url); ?>"
                       class="grupo-boton-principal inline-flex items-center justify-center gap-3 px-8 py-5 bg-white text-purple-700 font-bold text-lg rounded-xl hover:bg-purple-50 transform hover:scale-105 transition-all duration-200 shadow-2xl hover:shadow-3xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <?php echo esc_html($boton_texto); ?>
                    </a>

                    <a href="<?php echo esc_url($boton_secundario_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-5 bg-white/10 backdrop-blur-sm text-white font-semibold text-lg rounded-xl border-2 border-white/30 hover:bg-white/20 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <?php echo esc_html($boton_secundario_texto); ?>
                    </a>
                </div>
            </div>

            <!-- Características y beneficios -->
            <?php if ($mostrar_caracteristicas): ?>
                <div class="space-y-4">
                    <!-- Característica 1 -->
                    <div class="grupo-caracteristica bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Graba con Facilidad</h3>
                                <p class="text-gray-600">Herramientas intuitivas para grabar desde tu ordenador o móvil. Sin necesidad de equipamiento profesional.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Característica 2 -->
                    <div class="grupo-caracteristica bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Edición con IA</h3>
                                <p class="text-gray-600">Nuestra IA te ayuda a mejorar la calidad del audio, eliminar silencios y crear transcripciones automáticas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Característica 3 -->
                    <div class="grupo-caracteristica bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Alcanza a tu Audiencia</h3>
                                <p class="text-gray-600">Publica y comparte tu podcast con toda la comunidad. Conecta con oyentes que comparten tus intereses.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Característica 4 -->
                    <div class="grupo-caracteristica bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-orange-500 to-amber-500 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Soporte Continuo</h3>
                                <p class="text-gray-600">Accede a tutoriales, recursos y una comunidad activa dispuesta a ayudarte en cada paso del camino.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estadísticas adicionales -->
        <div class="mt-16 pt-12 border-t border-white/20">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-white mb-2">100%</div>
                    <div class="text-sm text-purple-200">Gratuito</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-white mb-2">24/7</div>
                    <div class="text-sm text-purple-200">Disponible</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-white mb-2">∞</div>
                    <div class="text-sm text-purple-200">Sin Límites</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-white mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="text-sm text-purple-200">Calidad Pro</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onda decorativa -->
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg class="w-full h-12 sm:h-16 lg:h-20 text-white" preserveAspectRatio="none" viewBox="0 0 1440 120" fill="currentColor">
            <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
        </svg>
    </div>
</section>
