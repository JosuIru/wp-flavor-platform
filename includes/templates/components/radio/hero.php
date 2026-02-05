<?php
/**
 * Template: Radio Hero Section
 *
 * Hero section for community radio module
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Radio
 */

defined('ABSPATH') || exit;

// Default values
$titulo_principal = $args['titulo_principal'] ?? 'Radio Comunitaria';
$subtitulo = $args['subtitulo'] ?? 'La voz de nuestra comunidad en el aire';
$descripcion = $args['descripcion'] ?? 'Escucha contenido local, programas culturales y música creada por y para nuestra comunidad. Conecta, comparte y participa en la radio que nos une.';
$imagen_fondo = $args['imagen_fondo'] ?? '';
$mostrar_boton_reproducir = $args['mostrar_boton_reproducir'] ?? true;
$mostrar_boton_programa = $args['mostrar_boton_programa'] ?? true;
$url_reproducir = $args['url_reproducir'] ?? '#reproductor';
$url_programa = $args['url_programa'] ?? '#programacion';
?>

<section class="relative bg-gradient-to-br from-purple-900 via-indigo-800 to-blue-900 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
    </div>

    <?php if ($imagen_fondo): ?>
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('<?php echo esc_url($imagen_fondo); ?>');"></div>
    <?php endif; ?>

    <!-- Animated Radio Waves -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96">
            <div class="absolute inset-0 border-2 border-white/20 rounded-full animate-ping" style="animation-duration: 3s;"></div>
            <div class="absolute inset-0 border-2 border-white/20 rounded-full animate-ping" style="animation-duration: 3s; animation-delay: 1s;"></div>
            <div class="absolute inset-0 border-2 border-white/20 rounded-full animate-ping" style="animation-duration: 3s; animation-delay: 2s;"></div>
        </div>
    </div>

    <div class="relative container mx-auto px-4 py-16 md:py-24 lg:py-32">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icon -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <div class="w-20 h-20 md:w-24 md:h-24 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center border-2 border-white/30">
                        <svg class="w-10 h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <!-- Live indicator pulse -->
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full animate-pulse border-2 border-white"></div>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4 md:mb-6 leading-tight">
                <?php echo esc_html($titulo_principal); ?>
            </h1>

            <!-- Subtitle -->
            <p class="text-xl md:text-2xl lg:text-3xl font-light mb-6 md:mb-8 text-purple-100">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <!-- Description -->
            <p class="text-base md:text-lg lg:text-xl text-gray-200 mb-8 md:mb-12 max-w-3xl mx-auto leading-relaxed">
                <?php echo esc_html($descripcion); ?>
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <?php if ($mostrar_boton_reproducir): ?>
                    <a href="<?php echo esc_url($url_reproducir); ?>"
                       class="group inline-flex items-center gap-3 px-8 py-4 bg-white text-purple-900 font-semibold rounded-full hover:bg-purple-50 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span>Escuchar en Vivo</span>
                    </a>
                <?php endif; ?>

                <?php if ($mostrar_boton_programa): ?>
                    <a href="<?php echo esc_url($url_programa); ?>"
                       class="group inline-flex items-center gap-3 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-full hover:bg-white/20 transition-all duration-300 border-2 border-white/30 hover:border-white/50 hover:scale-105">
                        <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Ver Programación</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Stats/Features -->
            <div class="mt-12 md:mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6 md:gap-8">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">24/7</h3>
                    <p class="text-sm text-purple-100">Transmisión continua</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">100% Local</h3>
                    <p class="text-sm text-purple-100">Contenido comunitario</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">Variado</h3>
                    <p class="text-sm text-purple-100">Música y programas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Wave -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-12 md:h-16 lg:h-20" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-white"></path>
        </svg>
    </div>
</section>
