<?php
/**
 * Template: Hero Foros de Discusion
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$imagen_url_fondo = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url_fondo): ?>
            <img src="<?php echo esc_url($imagen_url_fondo); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); opacity: var(--flavor-hero-overlay, 0.85);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);"></div>
        <?php endif; ?>
        <!-- Patron decorativo -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="foros-pattern" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M30 5 Q45 5 45 20 Q45 35 30 35 L15 35 L10 45 L15 35 Q15 5 30 5Z" fill="white" opacity="0.08"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#foros-pattern)"/>
            </svg>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono del modulo -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-8" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m0 0V6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H9l-4 4V10z"/>
                </svg>
            </div>

            <h1 class="text-4xl md:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo ?? __('Foros de la Comunidad', 'flavor-chat-ia')); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? __('Participa en las discusiones, comparte conocimiento y conecta con tu comunidad', 'flavor-chat-ia')); ?>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                <a href="/foros/" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-lg font-semibold transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: white; color: var(--flavor-primary, #667eea);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <?php echo esc_html__('Explorar Foros', 'flavor-chat-ia'); ?>
                </a>
                <a href="/foros/nuevo-tema/" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-lg font-semibold transition-all duration-300 hover:scale-105" style="background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php echo esc_html__('Nuevo Hilo', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-3 gap-6 max-w-lg mx-auto">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold" style="color: white;">12</div>
                    <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);"><?php echo esc_html__('Foros', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold" style="color: white;">248</div>
                    <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);"><?php echo esc_html__('Hilos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold" style="color: white;"><?php echo esc_html__('1.5K', 'flavor-chat-ia'); ?></div>
                    <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);"><?php echo esc_html__('Respuestas', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
