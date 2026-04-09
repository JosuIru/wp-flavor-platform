<?php
/**
 * Template: Hero Parkings
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Parkings Compartidos'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Gana dinero con tu plaza'); ?></p>

            <?php if (!empty($mostrar_buscador)): ?>
            <div class="flavor-card max-w-4xl mx-auto mb-8">
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" placeholder="<?php echo esc_attr__('Dirección o barrio...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="datetime-local" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="datetime-local" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                    </div>
                    <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12"><?php echo esc_html__('Buscar Parking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </form>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-3 gap-6 mt-8">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">250+</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('€2-5/h', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Precio medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">€300+</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);"><?php echo esc_html__('Gana/mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
