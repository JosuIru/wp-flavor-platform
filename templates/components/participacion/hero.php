<?php
/**
 * Template: Hero Participacion Ciudadana
 *
 * Seccion hero para el modulo de participacion civica.
 * Muestra titulo, subtitulo, estadisticas y CTA principal.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$imagen_hero_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente amber/orange -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_hero_url): ?>
            <img src="<?php echo esc_url($imagen_hero_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500 to-orange-600"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Etiqueta superior -->
            <div class="inline-flex items-center bg-white bg-opacity-20 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Democracia participativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? 'Participa en tu Comunidad'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Tu voz cuenta. Vota propuestas, lanza ideas y contribuye a las decisiones que afectan a tu barrio y tu ciudad.'); ?>
            </p>

            <!-- Boton CTA principal -->
            <div class="mb-16">
                <a href="<?php echo esc_url($boton_url ?? '#propuesta'); ?>"
                   class="inline-flex items-center bg-white text-amber-600 px-8 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Hacer una Propuesta'); ?>
                </a>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_propuestas ?? '324'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Propuestas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_votos ?? '12.580'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Votos emitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_ciudadanos ?? '4.750'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Ciudadanos participando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
