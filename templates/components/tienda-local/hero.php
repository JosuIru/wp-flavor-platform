<?php
/**
 * Template: Hero Tienda Local
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
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Tienda Local'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Apoya al comercio local'); ?></p>

            <div class="flavor-card max-w-3xl mx-auto mb-12">
                <input type="text" placeholder="Buscar productos o tiendas..." class="w-full px-6 py-4 border rounded-lg" style="border-color: #e5e7eb;">
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <a href="#explorar" class="flavor-button flavor-button-primary px-8">Explorar Tiendas</a>
                <a href="#vender" class="flavor-button px-8">Abrir Tienda</a>
            </div>

            <div class="grid grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">150+</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Tiendas Locales</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">3.5K</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Productos</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">98%</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Satisfacción</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold" style="color: var(--flavor-primary);">24/48h</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Entrega</div>
                </div>
            </div>
        </div>
    </div>
</section>
