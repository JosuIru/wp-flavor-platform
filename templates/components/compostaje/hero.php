<?php
/**
 * Template: Hero Compostaje
 * @package FlavorPlatform
 */

$imagen_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente tierra -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #6B4423 0%, #2D5016 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #6B4423 0%, #2D5016 100%);"></div>
        <?php endif; ?>
        <!-- Patrón de textura -->
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px); background-size: 20px 20px;"></div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono principal -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <svg class="w-20 h-20 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <div class="absolute -bottom-1 -right-1">
                        <svg class="w-8 h-8 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Compostaje Comunitario'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Transforma residuos orgánicos en abono natural'); ?></p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#mapa-composteras" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Ver Composteras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="#guia-compostaje" class="flavor-button px-8" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);"><?php echo esc_html__('Aprender a Compostar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>

            <?php if (!empty($mostrar_estadisticas)): ?>
            <div class="flavor-card max-w-3xl mx-auto" style="background: rgba(255,255,255,0.95);">
                <h3 class="text-2xl font-bold mb-6" style="color: #2D5016;"><?php echo esc_html__('Impacto Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex flex-col items-center p-4 rounded-lg" style="background: rgba(107, 68, 35, 0.1);">
                        <svg class="w-12 h-12 text-amber-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <div class="text-3xl font-bold" style="color: #6B4423;"><?php echo esc_html__('8T', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="text-sm" style="color: #57534e;"><?php echo esc_html__('Compostado/año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flex flex-col items-center p-4 rounded-lg" style="background: rgba(45, 80, 22, 0.1);">
                        <svg class="w-12 h-12 text-green-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <div class="text-3xl font-bold" style="color: #2D5016;">25+</div>
                        <div class="text-sm" style="color: #57534e;"><?php echo esc_html__('Puntos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flex flex-col items-center p-4 rounded-lg" style="background: rgba(217, 119, 6, 0.1);">
                        <svg class="w-12 h-12 text-orange-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <div class="text-3xl font-bold" style="color: #C2410C;">600</div>
                        <div class="text-sm" style="color: #57534e;"><?php echo esc_html__('Familias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flex flex-col items-center p-4 rounded-lg" style="background: rgba(34, 197, 94, 0.1);">
                        <svg class="w-12 h-12 text-green-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-3xl font-bold" style="color: #16a34a;">-40%</div>
                        <div class="text-sm" style="color: #57534e;"><?php echo esc_html__('Menos Residuos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
