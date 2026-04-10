<?php
/**
 * Template: Hero Presupuestos Participativos
 *
 * Seccion hero para el modulo de presupuestos participativos.
 * Titulo, subtitulo, estadisticas de presupuesto y proyectos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$imagen_hero_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente amber/yellow -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_hero_url): ?>
            <img src="<?php echo esc_url($imagen_hero_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500 to-yellow-500"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 right-20 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Etiqueta superior -->
            <div class="inline-flex items-center bg-white bg-opacity-20 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Gestion abierta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? 'Presupuestos Participativos'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Decide en que se invierte el dinero publico. Tu opinion define las prioridades de inversion de tu municipio.'); ?>
            </p>

            <!-- Estadisticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_presupuesto ?? '2.5M'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Presupuesto total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_proyectos ?? '48'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Proyectos aprobados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_votos ?? '15.230'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Votos ciudadanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>

            <!-- Boton CTA -->
            <div class="mt-12">
                <a href="<?php echo esc_url($boton_url ?? '#proyectos'); ?>"
                   class="inline-flex items-center bg-white text-amber-600 px-8 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Ver Proyectos'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
