<?php
/**
 * Template: Hero Section - Podcast
 *
 * Hero section destacada para la página principal de podcasts comunitarios
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Podcast
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_principal = $args['titulo_principal'] ?? 'Voces de Nuestra Comunidad';
$descripcion_hero = $args['descripcion_hero'] ?? 'Escucha historias auténticas, entrevistas inspiradoras y conversaciones que conectan a nuestra comunidad. Podcasts creados por y para los vecinos.';
$boton_principal_texto = $args['boton_principal_texto'] ?? 'Explorar Podcasts';
$boton_principal_url = $args['boton_principal_url'] ?? '#podcasts';
$boton_secundario_texto = $args['boton_secundario_texto'] ?? 'Crear Mi Podcast';
$boton_secundario_url = $args['boton_secundario_url'] ?? '#crear';
$imagen_fondo = $args['imagen_fondo'] ?? '';
$mostrar_estadisticas = $args['mostrar_estadisticas'] ?? true;
$total_podcasts = $args['total_podcasts'] ?? 0;
$total_episodios = $args['total_episodios'] ?? 0;
$total_horas_audio = $args['total_horas_audio'] ?? 0;
?>

<section class="relative bg-gradient-to-br from-purple-600 via-pink-600 to-rose-700 overflow-hidden">
    <!-- Patrón de fondo decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Imagen de fondo opcional -->
    <?php if ($imagen_fondo): ?>
        <div class="absolute inset-0 opacity-20">
            <img src="<?php echo esc_url($imagen_fondo); ?>" alt="" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Contenido principal -->
            <div class="text-center lg:text-left">
                <!-- Badge decorativo -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm font-medium mb-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </svg>
                    <span>Podcasts Comunitarios</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                    <?php echo esc_html($titulo_principal); ?>
                </h1>

                <p class="text-lg sm:text-xl text-purple-100 mb-8 max-w-2xl mx-auto lg:mx-0">
                    <?php echo esc_html($descripcion_hero); ?>
                </p>

                <!-- Botones CTA -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-12">
                    <a href="<?php echo esc_url($boton_principal_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-purple-700 font-semibold rounded-lg hover:bg-purple-50 transform hover:scale-105 transition-all duration-200 shadow-xl hover:shadow-2xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo esc_html($boton_principal_texto); ?>
                    </a>

                    <a href="<?php echo esc_url($boton_secundario_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg border-2 border-white/30 hover:bg-white/20 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <?php echo esc_html($boton_secundario_texto); ?>
                    </a>
                </div>

                <!-- Estadísticas -->
                <?php if ($mostrar_estadisticas && ($total_podcasts > 0 || $total_episodios > 0 || $total_horas_audio > 0)): ?>
                    <div class="grid grid-cols-3 gap-6 max-w-md mx-auto lg:mx-0">
                        <?php if ($total_podcasts > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_podcasts, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-purple-200">Series</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_episodios > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_episodios, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-purple-200">Episodios</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_horas_audio > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_horas_audio, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-purple-200">Horas</div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ilustración decorativa -->
            <div class="hidden lg:block">
                <div class="relative">
                    <!-- Círculos decorativos de fondo -->
                    <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-96 h-96 bg-pink-400/20 rounded-full blur-3xl"></div>

                    <!-- Tarjetas flotantes -->
                    <div class="relative z-10 space-y-4">
                        <!-- Tarjeta 1 - Audio en vivo -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 ml-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Audio de Calidad</h3>
                                    <p class="text-sm text-gray-600">Producción profesional</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 2 - Historias locales -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 mr-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Historias Locales</h3>
                                    <p class="text-sm text-gray-600">Voces de tu comunidad</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 3 - Acceso libre -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 ml-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Escucha Libre</h3>
                                    <p class="text-sm text-gray-600">Disponible 24/7</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onda decorativa inferior -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-auto" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
    </div>
</section>
