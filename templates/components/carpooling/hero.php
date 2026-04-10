<?php
/**
 * Template: Hero Carpooling
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Comparte tu Viaje'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Ahorra dinero y reduce tu huella de carbono'); ?></p>

            <?php if (!empty($mostrar_buscador)): ?>
            <div class="flavor-card max-w-3xl mx-auto">
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" placeholder="<?php echo esc_attr__('¿Desde dónde viajas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" placeholder="<?php echo esc_attr__('¿A dónde vas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="date" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                                <option><?php echo esc_html__('1 pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option><?php echo esc_html__('2 pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option><?php echo esc_html__('3 pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option><?php echo esc_html__('4+ pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12"><?php echo esc_html__('Buscar Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </form>
                <div class="grid grid-cols-3 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">2,450</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Viajes activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">8,340</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('€12.5k', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Ahorrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
