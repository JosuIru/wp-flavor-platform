<?php
/**
 * Template: Hero Portal de Transparencia
 *
 * Seccion hero para el modulo de transparencia.
 * Titulo, subtitulo y estadisticas de datos publicos.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$imagen_hero_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente teal/cyan -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_hero_url): ?>
            <img src="<?php echo esc_url($imagen_hero_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-teal-500 to-cyan-500"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-1/4 w-80 h-80 bg-white rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Etiqueta superior -->
            <div class="inline-flex items-center bg-white bg-opacity-20 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Gobierno abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? 'Portal de Transparencia'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Informacion publica accesible para todos. Consulta datos, documentos y la gestion de tu administracion.'); ?>
            </p>

            <!-- Buscador -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="relative">
                    <input type="text" placeholder="<?php echo esc_attr__('Buscar documentos, datos, informes...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           class="w-full px-6 py-4 pr-14 rounded-xl text-gray-900 shadow-xl text-lg focus:outline-none focus:ring-4 focus:ring-teal-300">
                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-teal-500 hover:bg-teal-600 text-white p-3 rounded-lg transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_documentos ?? '1.245'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Documentos publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_datos ?? '89'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Conjuntos de datos abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-white bg-opacity-15 backdrop-blur-sm rounded-xl p-6 text-center">
                    <div class="text-4xl font-bold text-white mb-1">
                        <?php echo esc_html($estadistica_consultas ?? '3.670'); ?>
                    </div>
                    <div class="text-white text-opacity-80 text-sm"><?php echo esc_html__('Consultas atendidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
