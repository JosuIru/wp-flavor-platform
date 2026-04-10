<?php
/**
 * Template: Hero Biblioteca
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

        <!-- Book Pattern -->
        <div class="absolute inset-0 opacity-5">
            <svg width="100%" height="100%">
                <pattern id="books" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                    <rect x="10" y="20" width="20" height="30" fill="white" rx="2"/>
                    <rect x="35" y="15" width="20" height="35" fill="white" rx="2"/>
                    <rect x="60" y="25" width="20" height="25" fill="white" rx="2"/>
                </pattern>
                <rect width="100%" height="100%" fill="url(#books)"/>
            </svg>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Biblioteca Comunitaria'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Comparte y descubre libros'); ?></p>

            <?php if (!empty($mostrar_buscador)): ?>
            <div class="flavor-card max-w-3xl mx-auto">
                <form class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" placeholder="<?php echo esc_attr__('Buscar por título, autor o ISBN...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full px-6 py-4 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <button type="submit" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </div>

                    <div class="flex flex-wrap gap-2 justify-center">
                        <span class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Popular:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <a href="?genero=novela" class="px-3 py-1 rounded-full text-sm transition-colors" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary);"><?php echo esc_html__('Novela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <a href="?genero=ciencia-ficcion" class="px-3 py-1 rounded-full text-sm transition-colors" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary);"><?php echo esc_html__('Ciencia Ficción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <a href="?genero=historia" class="px-3 py-1 rounded-full text-sm transition-colors" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary);"><?php echo esc_html__('Historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <a href="?genero=infantil" class="px-3 py-1 rounded-full text-sm transition-colors" style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary);"><?php echo esc_html__('Infantil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    </div>
                </form>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('3.5K', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">850</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">30</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Días préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);">100%</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
