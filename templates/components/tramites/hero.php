<?php
/**
 * Template: Hero Tramites Online
 *
 * Seccion hero para el modulo de tramites administrativos.
 * Titulo, subtitulo, buscador de tramites y estadisticas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$imagen_hero_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente orange/amber -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_hero_url): ?>
            <img src="<?php echo esc_url($imagen_hero_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-amber-500"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-80 h-80 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Etiqueta superior -->
            <div class="inline-flex items-center bg-white bg-opacity-20 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Administracion digital', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? 'Tramites Online'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-10" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Realiza tus gestiones sin salir de casa. Rapido, seguro y disponible las 24 horas.'); ?>
            </p>

            <!-- Buscador de tramites -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="relative">
                    <input type="text" placeholder="<?php echo esc_attr__('Buscar tramite: empadronamiento, licencias, certificados...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           class="w-full px-6 py-4 pr-14 rounded-xl text-gray-900 shadow-xl text-lg focus:outline-none focus:ring-4 focus:ring-orange-300">
                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
                <!-- Sugerencias rapidas -->
                <div class="flex flex-wrap justify-center gap-2 mt-4">
                    <span class="bg-white bg-opacity-20 text-white text-xs px-3 py-1 rounded-full cursor-pointer hover:bg-opacity-30 transition duration-300">
                        <?php echo esc_html__('Empadronamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="bg-white bg-opacity-20 text-white text-xs px-3 py-1 rounded-full cursor-pointer hover:bg-opacity-30 transition duration-300">
                        <?php echo esc_html__('Licencia de obra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="bg-white bg-opacity-20 text-white text-xs px-3 py-1 rounded-full cursor-pointer hover:bg-opacity-30 transition duration-300">
                        <?php echo esc_html__('Certificados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_tramites ?? '156'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Tramites disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_solicitudes ?? '8.340'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Solicitudes procesadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_tiempo ?? '3 dias'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Tiempo medio resolucion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
