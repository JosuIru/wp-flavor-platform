<?php
/**
 * Template: Grid de Series de Podcast
 *
 * Muestra las series/shows de podcast disponibles en formato grid.
 *
 * @package FlavorPlatform
 * @var string $titulo Título de la sección
 * @var string $subtitulo Subtítulo descriptivo
 * @var int $columnas Número de columnas (2, 3, 4)
 * @var bool $mostrar_filtros Mostrar filtros de categoría
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? __('Nuestras Series', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $subtitulo ?? __('Explora todas las series de podcast disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN);
$columnas = $columnas ?? 3;
$mostrar_filtros = $mostrar_filtros ?? true;

$series = [
    [
        'titulo' => 'Voces del Barrio',
        'descripcion' => 'Historias y testimonios de vecinos que construyen comunidad cada día',
        'imagen' => 'https://picsum.photos/seed/serie1/600/600',
        'episodios' => 48,
        'temporadas' => 3,
        'categoria' => 'Comunidad',
        'frecuencia' => 'Semanal',
        'duracion_media' => '45 min',
        'destacada' => true,
    ],
    [
        'titulo' => 'Economía Vecinal',
        'descripcion' => 'Análisis económico local y consejos para pequeños comercios',
        'imagen' => 'https://picsum.photos/seed/serie2/600/600',
        'episodios' => 32,
        'temporadas' => 2,
        'categoria' => 'Negocios',
        'frecuencia' => 'Quincenal',
        'duracion_media' => '30 min',
        'destacada' => false,
    ],
    [
        'titulo' => 'Sabores Locales',
        'descripcion' => 'Recetas tradicionales, chefs locales y cultura gastronómica',
        'imagen' => 'https://picsum.photos/seed/serie3/600/600',
        'episodios' => 56,
        'temporadas' => 4,
        'categoria' => 'Gastronomía',
        'frecuencia' => 'Semanal',
        'duracion_media' => '25 min',
        'destacada' => true,
    ],
    [
        'titulo' => 'Deporte en Comunidad',
        'descripcion' => 'Actualidad deportiva local, entrevistas a deportistas del barrio',
        'imagen' => 'https://picsum.photos/seed/serie4/600/600',
        'episodios' => 78,
        'temporadas' => 5,
        'categoria' => 'Deportes',
        'frecuencia' => 'Bisemanal',
        'duracion_media' => '40 min',
        'destacada' => false,
    ],
    [
        'titulo' => 'Tech para Todos',
        'descripcion' => 'Tecnología explicada de forma sencilla para todas las edades',
        'imagen' => 'https://picsum.photos/seed/serie5/600/600',
        'episodios' => 24,
        'temporadas' => 2,
        'categoria' => 'Tecnología',
        'frecuencia' => 'Semanal',
        'duracion_media' => '35 min',
        'destacada' => false,
    ],
    [
        'titulo' => 'Bienestar Diario',
        'descripcion' => 'Consejos de salud, meditación y vida saludable',
        'imagen' => 'https://picsum.photos/seed/serie6/600/600',
        'episodios' => 65,
        'temporadas' => 3,
        'categoria' => 'Salud',
        'frecuencia' => 'Diario',
        'duracion_media' => '15 min',
        'destacada' => true,
    ],
];

$categorias = array_unique(array_column($series, 'categoria'));
$grid_class = 'grid-cols-1 md:grid-cols-2';
if ($columnas >= 3) $grid_class .= ' lg:grid-cols-3';
if ($columnas >= 4) $grid_class .= ' xl:grid-cols-4';
?>

<section class="flavor-component flavor-podcast-grid py-16 bg-gradient-to-br from-slate-50 via-white to-purple-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <?php echo esc_html__('Series de Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($subtitulo); ?></p>
        </div>

        <?php if ($mostrar_filtros && count($categorias) > 1): ?>
        <!-- Filtros de categoría -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <button class="filter-btn active px-5 py-2 rounded-full text-sm font-medium bg-purple-600 text-white transition-all hover:bg-purple-700" data-filter="all">
                <?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 transition-all hover:border-purple-300 hover:text-purple-600" data-filter="<?php echo esc_attr(sanitize_title($cat)); ?>">
                    <?php echo esc_html($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Grid de Series -->
        <div class="grid <?php echo esc_attr($grid_class); ?> gap-8">
            <?php foreach ($series as $index => $serie): ?>
                <article class="serie-card group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 border border-gray-100" data-category="<?php echo esc_attr(sanitize_title($serie['categoria'])); ?>">

                    <!-- Imagen -->
                    <div class="relative aspect-square overflow-hidden">
                        <img
                            src="<?php echo esc_url($serie['imagen']); ?>"
                            alt="<?php echo esc_attr($serie['titulo']); ?>"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

                        <?php if ($serie['destacada']): ?>
                            <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-lg">
                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php echo esc_html__('Destacada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>

                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-white/95 text-gray-700 shadow">
                            <?php echo esc_html($serie['categoria']); ?>
                        </span>

                        <!-- Botón Play -->
                        <button class="absolute bottom-4 right-4 p-4 rounded-full bg-white text-purple-600 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 shadow-xl hover:scale-110 hover:bg-purple-600 hover:text-white">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>

                        <!-- Stats overlay -->
                        <div class="absolute bottom-4 left-4 flex items-center gap-3 text-white text-sm">
                            <span class="flex items-center gap-1 bg-black/40 px-2 py-1 rounded-full backdrop-blur-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                                <?php echo esc_html($serie['episodios']); ?> ep.
                            </span>
                            <span class="flex items-center gap-1 bg-black/40 px-2 py-1 rounded-full backdrop-blur-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php echo esc_html($serie['duracion_media']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($serie['titulo']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            <?php echo esc_html($serie['descripcion']); ?>
                        </p>

                        <!-- Meta info -->
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <?php printf(esc_html__('%d temporadas', FLAVOR_PLATFORM_TEXT_DOMAIN), $serie['temporadas']); ?>
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <?php echo esc_html($serie['frecuencia']); ?>
                            </span>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-3">
                            <a href="#" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg shadow-purple-200 hover:shadow-purple-300">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <?php echo esc_html__('Escuchar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <button class="p-3 rounded-xl border-2 border-gray-200 text-gray-600 hover:border-purple-300 hover:text-purple-600 transition-all" title="<?php echo esc_attr__('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Ver más -->
        <div class="text-center mt-12">
            <a href="#" class="inline-flex items-center gap-2 px-8 py-4 rounded-full font-semibold text-purple-600 bg-purple-50 hover:bg-purple-100 transition-all border-2 border-purple-200 hover:border-purple-300">
                <?php echo esc_html__('Ver todas las series', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-podcast-grid .filter-btn.active {
    background: linear-gradient(135deg, #9333ea 0%, #6366f1 100%);
    color: white;
    border-color: transparent;
}
.flavor-podcast-grid .serie-card.hidden {
    display: none;
}
.flavor-podcast-grid .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.flavor-podcast-grid .filter-btn');
    const cards = document.querySelectorAll('.flavor-podcast-grid .serie-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;

            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            cards.forEach(card => {
                if (filter === 'all' || card.dataset.category === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });
});
</script>
