<?php
/**
 * Template: Hero Talleres
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Talleres Comunitarios'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Aprende haciendo'); ?></p>

            <?php if (!empty($mostrar_filtros)): ?>
            <div class="flavor-card max-w-4xl mx-auto mb-8">
                <h3 class="text-lg font-bold mb-4" style="color: var(--flavor-text);"><?php echo esc_html__('Explora por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/talleres/?cat=manualidades" class="flavor-button text-center"><?php echo esc_html__('Manualidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    <a href="/talleres/?cat=cocina" class="flavor-button text-center"><?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    <a href="/talleres/?cat=tecnologia" class="flavor-button text-center"><?php echo esc_html__('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    <a href="/talleres/?cat=bienestar" class="flavor-button text-center"><?php echo esc_html__('Bienestar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <a href="/talleres/" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Ver Todos los Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="/talleres/crear/" class="flavor-button px-8"><?php echo esc_html__('Organizar Taller', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">80+</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('1.2K', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">45+</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Instructores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">4.8★</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
