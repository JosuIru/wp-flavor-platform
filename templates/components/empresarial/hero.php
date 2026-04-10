<?php
/**
 * Template: Hero Corporativo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$imagen_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative overflow-hidden" style="padding-top: 0; padding-bottom: 0; min-height: 100vh;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>

        <!-- Patrón decorativo -->
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 flex items-center" style="min-height: 100vh;">
        <div class="w-full py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Texto -->
                <div class="text-left">
                    <h1 class="mb-6 leading-tight" style="color: white; font-size: 3rem; font-weight: 700;">
                        <?php echo esc_html($titulo ?? 'Soluciones Empresariales de Calidad'); ?>
                    </h1>
                    <p class="text-xl mb-8 leading-relaxed" style="color: rgba(255,255,255,0.95);">
                        <?php echo esc_html($subtitulo ?? 'Potencia tu negocio con nuestros servicios profesionales y tecnología de vanguardia'); ?>
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <?php if (!empty($texto_boton_principal)): ?>
                            <a href="<?php echo esc_url($url_boton_principal ?? '#'); ?>"
                               class="flavor-button flavor-button-primary inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-lg"
                               style="background: white; color: var(--flavor-primary);">
                                <?php echo esc_html($texto_boton_principal); ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($texto_boton_secundario)): ?>
                            <a href="<?php echo esc_url($url_boton_secundario ?? '#'); ?>"
                               class="flavor-button inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105"
                               style="background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(10px); border: 2px solid white;">
                                <?php echo esc_html($texto_boton_secundario); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Indicadores de confianza -->
                    <div class="mt-12 flex flex-wrap items-center gap-8">
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-white font-semibold"><?php echo esc_html__('4.9/5 Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-semibold"><?php echo esc_html__('+500 Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Video o imagen decorativa -->
                <div class="relative">
                    <?php if (!empty($mostrar_video) && !empty($url_video)): ?>
                        <div class="aspect-w-16 aspect-h-9 rounded-xl overflow-hidden shadow-2xl">
                            <?php
                            // Detectar tipo de video
                            $video_id = '';
                            $video_type = '';

                            if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url_video, $matches)) {
                                $video_id = $matches[1];
                                $video_type = 'youtube';
                            } elseif (preg_match('/youtu\.be\/([^?]+)/', $url_video, $matches)) {
                                $video_id = $matches[1];
                                $video_type = 'youtube';
                            } elseif (preg_match('/vimeo\.com\/(\d+)/', $url_video, $matches)) {
                                $video_id = $matches[1];
                                $video_type = 'vimeo';
                            }

                            if ($video_type === 'youtube'): ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                        class="w-full h-full"></iframe>
                            <?php elseif ($video_type === 'vimeo'): ?>
                                <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($video_id); ?>"
                                        frameborder="0"
                                        allow="autoplay; fullscreen; picture-in-picture"
                                        allowfullscreen
                                        class="w-full h-full"></iframe>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Decoración 3D -->
                        <div class="relative">
                            <div class="absolute top-0 right-0 w-72 h-72 bg-white opacity-10 rounded-full blur-3xl"></div>
                            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>

                            <div class="relative bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg rounded-2xl p-8 shadow-2xl">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white bg-opacity-20 rounded-lg p-6 text-center">
                                        <div class="text-4xl font-bold text-white mb-2">99%</div>
                                        <div class="text-sm text-white opacity-90"><?php echo esc_html__('Satisfacción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-6 text-center">
                                        <div class="text-4xl font-bold text-white mb-2">24/7</div>
                                        <div class="text-sm text-white opacity-90"><?php echo esc_html__('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-6 text-center">
                                        <div class="text-4xl font-bold text-white mb-2">15+</div>
                                        <div class="text-sm text-white opacity-90"><?php echo esc_html__('Años', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-6 text-center">
                                        <div class="text-4xl font-bold text-white mb-2">500+</div>
                                        <div class="text-sm text-white opacity-90"><?php echo esc_html__('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10">
        <div class="flex flex-col items-center gap-2">
            <span class="text-white text-sm opacity-75"><?php echo esc_html__('Descubre más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <svg class="w-6 h-6 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </div>
</section>
