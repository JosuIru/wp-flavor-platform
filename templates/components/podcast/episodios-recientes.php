<?php
/**
 * Template: Episodios Recientes de Podcast
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Episodios Recientes';
$descripcion = $descripcion ?? 'Los ultimos episodios de tus podcasts favoritos';

$episodios = [
    ['titulo' => 'El futuro del comercio local', 'podcast' => 'Economia Local', 'duracion' => '45:32', 'fecha' => 'Hace 2 horas', 'imagen' => 'https://picsum.photos/seed/ep1/200/200', 'reproducciones' => '342'],
    ['titulo' => 'Receta: Tortilla de patatas perfecta', 'podcast' => 'Sabores Vecinales', 'duracion' => '28:15', 'fecha' => 'Hace 5 horas', 'imagen' => 'https://picsum.photos/seed/ep2/200/200', 'reproducciones' => '567'],
    ['titulo' => 'Entrevista: El panadero del barrio', 'podcast' => 'Historias del Barrio', 'duracion' => '52:48', 'fecha' => 'Hace 1 dia', 'imagen' => 'https://picsum.photos/seed/ep3/200/200', 'reproducciones' => '891'],
    ['titulo' => 'Liga amateur: Resultados jornada 12', 'podcast' => 'Deportes en Comunidad', 'duracion' => '35:20', 'fecha' => 'Hace 1 dia', 'imagen' => 'https://picsum.photos/seed/ep4/200/200', 'reproducciones' => '423'],
    ['titulo' => 'Inteligencia artificial para mayores', 'podcast' => 'Tecnologia Accesible', 'duracion' => '41:05', 'fecha' => 'Hace 2 dias', 'imagen' => 'https://picsum.photos/seed/ep5/200/200', 'reproducciones' => '756'],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%); color: white;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo esc_html__('Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-2"><?php echo esc_html($titulo); ?></h2>
                <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
            </div>
            <a href="#todos-episodios" class="mt-4 md:mt-0 text-teal-600 font-semibold hover:text-teal-700 flex items-center gap-1">
                <?php echo esc_html__('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="space-y-4">
            <?php foreach ($episodios as $indice => $episodio): ?>
                <article class="group bg-gray-50 hover:bg-teal-50 rounded-2xl p-4 md:p-6 transition-all duration-300 border border-gray-100 hover:border-teal-200">
                    <div class="flex items-center gap-4 md:gap-6">
                        <div class="relative flex-shrink-0">
                            <img src="<?php echo esc_url($episodio['imagen']); ?>" alt="<?php echo esc_attr($episodio['titulo']); ?>" class="w-16 h-16 md:w-20 md:h-20 rounded-xl object-cover">
                            <button class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-teal-600 uppercase tracking-wide"><?php echo esc_html($episodio['podcast']); ?></span>
                                <span class="text-gray-300">•</span>
                                <span class="text-xs text-gray-500"><?php echo esc_html($episodio['fecha']); ?></span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-teal-600 transition-colors truncate"><?php echo esc_html($episodio['titulo']); ?></h3>
                            <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?php echo esc_html($episodio['duracion']); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?php echo esc_html($episodio['reproducciones']); ?> reproducciones
                                </span>
                            </div>
                        </div>
                        <div class="hidden md:flex items-center gap-3">
                            <button class="p-2 rounded-full text-gray-400 hover:text-teal-600 hover:bg-teal-100 transition-colors" title="<?php echo esc_attr__('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </button>
                            <button class="p-2 rounded-full text-gray-400 hover:text-teal-600 hover:bg-teal-100 transition-colors" title="<?php echo esc_attr__('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </button>
                            <button class="p-2 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-100 transition-colors" title="<?php echo esc_attr__('Favorito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                            <button class="px-4 py-2 rounded-xl font-semibold text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%);">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Mini reproductor flotante -->
        <div class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white rounded-2xl shadow-2xl p-4 border border-gray-200 hidden" id="mini-player">
            <div class="flex items-center gap-4">
                <img src="https://picsum.photos/seed/ep1/200/200" alt="" class="w-12 h-12 rounded-lg object-cover">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo esc_html__('El futuro del comercio local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Economia Local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <button class="p-2 rounded-full bg-teal-500 text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>
