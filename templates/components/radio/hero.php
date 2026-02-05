<?php
/**
 * Template: Hero Radio
 * @package FlavorChatIA
 */

$imagen_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Radio Comunitaria'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'En vivo 24/7'); ?></p>

            <div class="grid grid-cols-2 gap-4 mb-8 max-w-2xl mx-auto">
                <div class="flavor-card text-center">
                    <div class="text-3xl font-bold mb-1" style="color: var(--flavor-primary);">FM 102.5</div>
                    <div class="text-sm" style="color: var(--flavor-text-muted);">Frecuencia</div>
                </div>
                <div class="flavor-card text-center">
                    <div class="text-3xl font-bold mb-1" style="color: var(--flavor-primary);">5K+</div>
                    <div class="text-sm" style="color: var(--flavor-text-muted);">Oyentes/día</div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#programacion" class="flavor-button flavor-button-primary px-8">Ver Programación</a>
                <a href="#participar" class="flavor-button px-8">Participar</a>
            </div>
        </div>
    </div>
</section>
