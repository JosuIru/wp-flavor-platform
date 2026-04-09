<?php
/**
 * Template: Hero Bares y Hosteleria
 *
 * Seccion hero con titulo, subtitulo, buscador y estadisticas
 * para la landing de bares y restaurantes.
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var string $imagen_fondo
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$url_imagen_fondo = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($url_imagen_fondo): ?>
            <img src="<?php echo esc_url($url_imagen_fondo); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>

        <!-- Pattern Overlay decorativo -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;80&quot; height=&quot;80&quot; viewBox=&quot;0 0 80 80&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Ccircle cx=&quot;40&quot; cy=&quot;40&quot; r=&quot;4&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono decorativo -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-8" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-10 h-10" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </div>

            <h1 class="text-4xl md:text-6xl font-black mb-6" style="color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                <?php echo esc_html($titulo ?? 'Bares y Restaurantes'); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo ?? 'Descubre los mejores locales de hosteleria de tu zona'); ?>
            </p>

            <!-- Buscador -->
            <div class="flavor-card max-w-3xl mx-auto" style="background: rgba(255,255,255,0.97); backdrop-filter: blur(20px);">
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                <?php _e('Buscar establecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <input type="text" placeholder="<?php esc_attr_e('Nombre, tipo de cocina, zona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:outline-none" style="border-color: #e5e7eb; focus: ring-color: var(--flavor-primary);">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);">
                                <?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <select class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                                <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('bar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Bar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('cafeteria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cafeteria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('pub', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Pub', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('terraza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('cocteleria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cocteleria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12">
                        <?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </form>

                <!-- Estadisticas -->
                <div class="grid grid-cols-3 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">85+</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Establecimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('1.2K+', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">4.6</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php _e('Valoracion media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>

            <!-- Tipos rapidos -->
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <?php
                $tipos_rapidos = [
                    'bar'         => ['label' => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '🍺'],
                    'restaurante' => ['label' => __('Restaurantes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '🍽️'],
                    'cafeteria'   => ['label' => __('Cafeterias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '☕'],
                    'pub'         => ['label' => __('Pubs', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '🎵'],
                    'terraza'     => ['label' => __('Terrazas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '☀️'],
                    'cocteleria'  => ['label' => __('Coctelerias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'emoji' => '🍸'],
                ];
                foreach ($tipos_rapidos as $clave_tipo => $datos_tipo): ?>
                    <a href="#<?php echo esc_attr($clave_tipo); ?>"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold transition-all duration-200 hover:scale-105"
                       style="background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                        <span><?php echo $datos_tipo['emoji']; ?></span>
                        <?php echo esc_html($datos_tipo['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
