<?php
/**
 * Template: Carousel de Contenido Destacado
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Contenido Destacado';

$destacados = [
    ['titulo' => 'Festival de Verano 2024', 'tipo' => 'Video', 'duracion' => '15:32', 'vistas' => '3.2K', 'imagen' => 'https://picsum.photos/seed/multi1/800/450', 'autor' => 'Canal Vecinal'],
    ['titulo' => 'Concierto en el Parque', 'tipo' => 'Video', 'duracion' => '45:18', 'vistas' => '2.8K', 'imagen' => 'https://picsum.photos/seed/multi2/800/450', 'autor' => 'Eventos Locales'],
    ['titulo' => 'Exposicion de Arte Urbano', 'tipo' => 'Galeria', 'duracion' => '24 fotos', 'vistas' => '1.5K', 'imagen' => 'https://picsum.photos/seed/multi3/800/450', 'autor' => 'Arte en la Calle'],
    ['titulo' => 'Feria Gastronomica', 'tipo' => 'Video', 'duracion' => '22:45', 'vistas' => '4.1K', 'imagen' => 'https://picsum.photos/seed/multi4/800/450', 'autor' => 'Sabores del Barrio'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-fuchsia-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                    </svg>
                    Destacados
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900"><?php echo esc_html($titulo); ?></h2>
            </div>
            <div class="flex items-center gap-2 mt-4 md:mt-0">
                <button class="carousel-prev p-3 rounded-full bg-white shadow-lg hover:bg-fuchsia-50 transition-colors border border-gray-200">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button class="carousel-next p-3 rounded-full bg-white shadow-lg hover:bg-fuchsia-50 transition-colors border border-gray-200">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Carousel principal -->
        <div class="relative overflow-hidden rounded-3xl mb-8">
            <div class="carousel-track flex transition-transform duration-500 ease-out">
                <?php foreach ($destacados as $indice => $item): ?>
                    <div class="carousel-slide flex-shrink-0 w-full relative group">
                        <div class="relative aspect-video">
                            <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['titulo']); ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                            <!-- Overlay de reproduccion -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <button class="p-6 rounded-full bg-white/20 backdrop-blur-sm text-white opacity-0 group-hover:opacity-100 transition-all duration-300 hover:scale-110 hover:bg-white/30">
                                    <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Info -->
                            <div class="absolute bottom-0 left-0 right-0 p-8">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%);">
                                        <?php echo esc_html($item['tipo']); ?>
                                    </span>
                                    <span class="text-white/80 text-sm"><?php echo esc_html($item['duracion']); ?></span>
                                    <span class="text-white/60">•</span>
                                    <span class="text-white/80 text-sm"><?php echo esc_html($item['vistas']); ?> vistas</span>
                                </div>
                                <h3 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html($item['titulo']); ?></h3>
                                <p class="text-white/80"><?php echo esc_html($item['autor']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Thumbnails -->
        <div class="grid grid-cols-4 gap-4">
            <?php foreach ($destacados as $indice => $item): ?>
                <button class="carousel-thumb group relative rounded-xl overflow-hidden <?php echo $indice === 0 ? 'ring-2 ring-fuchsia-500 ring-offset-2' : ''; ?>">
                    <div class="aspect-video">
                        <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['titulo']); ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
                    </div>
                    <div class="absolute bottom-2 left-2 right-2">
                        <p class="text-white text-xs font-medium truncate"><?php echo esc_html($item['titulo']); ?></p>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
