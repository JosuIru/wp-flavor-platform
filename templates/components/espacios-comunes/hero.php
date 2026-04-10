<?php
/**
 * Template: Hero Espacios Comunes
 * @package FlavorPlatform
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Espacios Comunes'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Reserva fácil y rápido'); ?></p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#ver-espacios" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Explorar Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="#mis-reservas" class="flavor-button px-8"><?php echo esc_html__('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">5</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">450</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Reservas/mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Para vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
