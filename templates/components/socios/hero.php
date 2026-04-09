<?php
/**
 * Template: Hero Hazte Socio
 *
 * Seccion hero para el modulo de socios/membresia.
 * Titulo, subtitulo y estadisticas de la comunidad.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$imagen_hero_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente rose/pink -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_hero_url): ?>
            <img src="<?php echo esc_url($imagen_hero_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-rose-500 to-pink-500"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Etiqueta superior -->
            <div class="inline-flex items-center bg-white bg-opacity-20 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Comunidad exclusiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? 'Hazte Socio'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Unete a nuestra comunidad y disfruta de ventajas exclusivas. Descuentos, eventos, acceso prioritario y mucho mas.'); ?>
            </p>

            <!-- CTA principal -->
            <div class="mb-16">
                <a href="<?php echo esc_url($boton_url ?? '/socios/unirme/'); ?>"
                   class="inline-flex items-center bg-white text-rose-600 px-8 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Ver Planes'); ?>
                </a>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_socios ?? '2.340'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_eventos ?? '48'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Eventos exclusivos/ano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_descuentos ?? '25%'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Descuento medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
