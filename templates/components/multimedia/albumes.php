<?php
/**
 * Template: Albumes de Fotos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Albumes de Fotos';
$descripcion = $descripcion ?? 'Momentos capturados por la comunidad';

$albumes = [
    ['titulo' => 'Fiestas Patronales 2024', 'cantidad' => 156, 'fecha' => 'Agosto 2024', 'portada' => 'https://picsum.photos/seed/alb1/600/400', 'thumbs' => ['https://picsum.photos/seed/alb1a/100/100', 'https://picsum.photos/seed/alb1b/100/100', 'https://picsum.photos/seed/alb1c/100/100']],
    ['titulo' => 'Carnavales', 'cantidad' => 234, 'fecha' => 'Febrero 2024', 'portada' => 'https://picsum.photos/seed/alb2/600/400', 'thumbs' => ['https://picsum.photos/seed/alb2a/100/100', 'https://picsum.photos/seed/alb2b/100/100', 'https://picsum.photos/seed/alb2c/100/100']],
    ['titulo' => 'Navidad en el Barrio', 'cantidad' => 89, 'fecha' => 'Diciembre 2023', 'portada' => 'https://picsum.photos/seed/alb3/600/400', 'thumbs' => ['https://picsum.photos/seed/alb3a/100/100', 'https://picsum.photos/seed/alb3b/100/100', 'https://picsum.photos/seed/alb3c/100/100']],
    ['titulo' => 'Dia del Deporte', 'cantidad' => 112, 'fecha' => 'Mayo 2024', 'portada' => 'https://picsum.photos/seed/alb4/600/400', 'thumbs' => ['https://picsum.photos/seed/alb4a/100/100', 'https://picsum.photos/seed/alb4b/100/100', 'https://picsum.photos/seed/alb4c/100/100']],
    ['titulo' => 'Mercado Artesanal', 'cantidad' => 67, 'fecha' => 'Abril 2024', 'portada' => 'https://picsum.photos/seed/alb5/600/400', 'thumbs' => ['https://picsum.photos/seed/alb5a/100/100', 'https://picsum.photos/seed/alb5b/100/100', 'https://picsum.photos/seed/alb5c/100/100']],
    ['titulo' => 'Conciertos de Verano', 'cantidad' => 198, 'fecha' => 'Julio 2024', 'portada' => 'https://picsum.photos/seed/alb6/600/400', 'thumbs' => ['https://picsum.photos/seed/alb6a/100/100', 'https://picsum.photos/seed/alb6b/100/100', 'https://picsum.photos/seed/alb6c/100/100']],
];
?>

<section class="flavor-component py-16" style="background: linear-gradient(135deg, #fdf4ff 0%, #fce7f3 100%);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <?php echo esc_html__('Albumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($albumes as $album): ?>
                <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    <!-- Portada con efecto de stack -->
                    <div class="relative">
                        <div class="absolute -top-2 left-4 right-4 h-4 bg-gray-200 rounded-t-lg"></div>
                        <div class="absolute -top-1 left-2 right-2 h-3 bg-gray-100 rounded-t-lg"></div>
                        <div class="relative aspect-[3/2] overflow-hidden rounded-t-lg">
                            <img src="<?php echo esc_url($album['portada']); ?>" alt="<?php echo esc_attr($album['titulo']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <!-- Contador de fotos -->
                            <div class="absolute top-4 right-4 px-3 py-1.5 rounded-full bg-black/50 backdrop-blur-sm text-white text-sm font-semibold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <?php echo esc_html($album['cantidad']); ?>
                            </div>

                            <!-- Boton ver album -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="px-6 py-3 rounded-xl bg-white/90 text-fuchsia-600 font-semibold hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?php echo esc_html__('Ver Album', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-fuchsia-600 transition-colors mb-2"><?php echo esc_html($album['titulo']); ?></h3>
                        <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($album['fecha']); ?></p>

                        <!-- Thumbnails preview -->
                        <div class="flex items-center gap-2">
                            <?php foreach ($album['thumbs'] as $thumb): ?>
                                <img src="<?php echo esc_url($thumb); ?>" alt="" class="w-10 h-10 rounded-lg object-cover ring-2 ring-white">
                            <?php endforeach; ?>
                            <span class="w-10 h-10 rounded-lg bg-fuchsia-100 text-fuchsia-600 flex items-center justify-center text-xs font-bold">
                                +<?php echo esc_html($album['cantidad'] - 3); ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- CTA Subir fotos -->
        <div class="mt-16 bg-white rounded-2xl p-8 shadow-xl text-center max-w-2xl mx-auto">
            <div class="inline-flex items-center justify-center p-4 rounded-full mb-4" style="background: linear-gradient(135deg, #fce7f3 0%, #fdf4ff 100%);">
                <svg class="w-10 h-10 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo esc_html__('Comparte tus Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="text-gray-600 mb-6"><?php echo esc_html__('Sube las fotos de eventos del barrio y ayudanos a crear memoria colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="#subir-fotos" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:scale-105" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <?php echo esc_html__('Subir Fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
