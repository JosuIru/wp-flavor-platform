<?php
/**
 * Template: Hero Huertos Urbanos
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Huertos Urbanos'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Cultiva sostenible'); ?></p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#solicitar-parcela" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Solicitar Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="#ver-huertos" class="flavor-button px-8"><?php echo esc_html__('Ver Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>

            <?php if (!empty($mostrar_estadisticas)): ?>
            <div class="flavor-card max-w-3xl mx-auto">
                <h3 class="text-2xl font-bold mb-6" style="color: var(--flavor-text);"><?php echo esc_html__('Estadísticas en Vivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-lg" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1);">
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);">12</div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Huertos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1);">
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);">145</div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Hortelanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-lg" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1);">
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);">238</div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Parcelas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
