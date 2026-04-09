<?php
/**
 * Template: Hero Carpooling (v2 con Design Settings)
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var int $imagen_fondo
 * @var bool $mostrar_buscador
 * @var string $component_classes
 */

$imagen_url = $imagen_fondo ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
$primary = flavor_design_get('primary_color', '#3b82f6');
$secondary = flavor_design_get('secondary_color', '#8b5cf6');
$overlay_opacity = flavor_design_get('hero_overlay_opacity', 0.6);
?>

<section class="flavor-component relative min-h-screen flex items-center <?php echo esc_attr($component_classes); ?>">
    <!-- Imagen de fondo -->
    <?php if ($imagen_url): ?>
        <div class="absolute inset-0 z-0">
            <img src="<?php echo esc_url($imagen_url); ?>"
                 alt=""
                 class="flavor-image w-full h-full object-cover"
                 style="border-radius: 0;">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, <?php echo esc_attr($primary); ?> 0%, <?php echo esc_attr($secondary); ?> 100%); opacity: <?php echo esc_attr($overlay_opacity); ?>;"></div>
        </div>
    <?php else: ?>
        <div class="absolute inset-0 z-0" style="background: linear-gradient(135deg, <?php echo esc_attr($primary); ?> 0%, <?php echo esc_attr($secondary); ?> 100%);"></div>
    <?php endif; ?>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Título -->
            <h1 style="color: white; margin-bottom: 1.5rem;">
                <?php echo esc_html($titulo); ?>
            </h1>

            <!-- Subtítulo -->
            <p class="text-xl md:text-2xl mb-12 leading-relaxed" style="color: rgba(255, 255, 255, 0.9);">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <!-- Buscador de viajes -->
            <?php if ($mostrar_buscador): ?>
                <div class="flavor-card max-w-3xl mx-auto">
                    <form class="space-y-4" id="carpooling-search-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Origen -->
                            <div class="form-group">
                                <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                    <span class="inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" style="color: var(--flavor-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <?php _e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    name="origen"
                                    placeholder="<?php _e('¿Desde dónde viajas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                    class="w-full px-4 py-3 border rounded-lg focus:outline-none transition-colors"
                                    style="border-color: #e5e7eb; font-size: var(--flavor-font-size-base);"
                                    onfocus="this.style.borderColor='var(--flavor-primary)'"
                                    onblur="this.style.borderColor='#e5e7eb'"
                                >
                            </div>

                            <!-- Destino -->
                            <div class="form-group">
                                <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                    <span class="inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" style="color: var(--flavor-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                        </svg>
                                        <?php _e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    name="destino"
                                    placeholder="<?php _e('¿A dónde vas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                    class="w-full px-4 py-3 border rounded-lg focus:outline-none transition-colors"
                                    style="border-color: #e5e7eb; font-size: var(--flavor-font-size-base);"
                                    onfocus="this.style.borderColor='var(--flavor-primary)'"
                                    onblur="this.style.borderColor='#e5e7eb'"
                                >
                            </div>

                            <!-- Fecha -->
                            <div class="form-group">
                                <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                    <span class="inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" style="color: var(--flavor-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </label>
                                <input
                                    type="date"
                                    name="fecha"
                                    class="w-full px-4 py-3 border rounded-lg focus:outline-none transition-colors"
                                    style="border-color: #e5e7eb; font-size: var(--flavor-font-size-base);"
                                    onfocus="this.style.borderColor='var(--flavor-primary)'"
                                    onblur="this.style.borderColor='#e5e7eb'"
                                >
                            </div>

                            <!-- Pasajeros -->
                            <div class="form-group">
                                <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                    <span class="inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" style="color: var(--flavor-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <?php _e('Pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </label>
                                <select name="pasajeros" class="w-full px-4 py-3 border rounded-lg focus:outline-none transition-colors" style="border-color: #e5e7eb; font-size: var(--flavor-font-size-base);" onfocus="this.style.borderColor='var(--flavor-primary)'" onblur="this.style.borderColor='#e5e7eb'">
                                    <option value="1"><?php echo esc_html__('1 pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="2"><?php echo esc_html__('2 pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="3"><?php echo esc_html__('3 pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="4"><?php echo esc_html__('4+ pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Botón de búsqueda -->
                        <div class="mt-6">
                            <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <?php _e('Buscar Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                        <div class="text-center">
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);">2,450</div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Viajes activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);">8,340</div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('€12.5k', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Ahorrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
#carpooling-search-form button[type="submit"] {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
