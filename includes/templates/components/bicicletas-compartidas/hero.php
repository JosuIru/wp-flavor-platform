<?php
/**
 * Template: Hero Bicicletas Compartidas
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
            <div class="absolute inset-0" style="background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);"></div>
        <?php endif; ?>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono de bicicleta -->
            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
            </div>

            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Bicicletas Compartidas'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Movilidad sostenible y saludable para toda la comunidad'); ?></p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#mapa-estaciones" class="flavor-button flavor-button-primary px-8">Ver Estaciones</a>
                <a href="#tipos-bicicletas" class="flavor-button px-8" style="background: white; color: var(--flavor-primary);">Tipos de Bicis</a>
                <a href="#desbloquear" class="flavor-button px-8" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white;">Desbloquear</a>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold mb-2" style="color: white;">45</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Bicicletas</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold mb-2" style="color: white;">12</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Estaciones</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold mb-2" style="color: white;">2.5k</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Viajes/mes</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold mb-2" style="color: white;">€0.50</div>
                    <div class="text-sm" style="color: rgba(255,255,255,0.8);">Por 30min</div>
                </div>
            </div>

            <!-- Beneficios rápidos -->
            <div class="mt-12 flex flex-wrap justify-center gap-4">
                <div class="flex items-center gap-2 px-4 py-2 rounded-full" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-white text-sm font-medium">24/7 Disponible</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 rounded-full" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-white text-sm font-medium">Sin Registro</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 rounded-full" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-white text-sm font-medium">Ecológico</span>
                </div>
            </div>
        </div>
    </div>
</section>
