<?php
/**
 * Template: Hero Ayuda Vecinal
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Ayuda Vecinal'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Comunidad que se ayuda'); ?></p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#pedir-ayuda" class="flavor-button flavor-button-primary px-8">Pedir Ayuda</a>
                <a href="#ofrecer-ayuda" class="flavor-button px-8">Ofrecer Ayuda</a>
                <a href="#solicitudes" class="flavor-button px-8">Ver Solicitudes</a>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">128</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Ayudas este mes</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">95%</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Cumplidas</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">4.9★</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Rating</div>
                </div>
            </div>
        </div>
    </div>
</section>
