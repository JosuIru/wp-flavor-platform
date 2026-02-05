<?php
/**
 * Template: Grid de Podcasts
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Podcasts Destacados';
$descripcion = $descripcion ?? 'Descubre las voces de tu comunidad';

$podcasts = [
    ['titulo' => 'Historias del Barrio', 'autor' => 'Maria Garcia', 'descripcion' => 'Relatos de vecinos que hacen historia', 'episodios' => 45, 'oyentes' => '2.3K', 'imagen' => 'https://picsum.photos/seed/pod1/400/400', 'categoria' => 'Cultura', 'nuevo' => true],
    ['titulo' => 'Economia Local', 'autor' => 'Carlos Ruiz', 'descripcion' => 'Noticias economicas que te afectan', 'episodios' => 32, 'oyentes' => '1.8K', 'imagen' => 'https://picsum.photos/seed/pod2/400/400', 'categoria' => 'Negocios', 'nuevo' => false],
    ['titulo' => 'Sabores Vecinales', 'autor' => 'Ana Lopez', 'descripcion' => 'Recetas y gastronomia de la zona', 'episodios' => 28, 'oyentes' => '1.5K', 'imagen' => 'https://picsum.photos/seed/pod3/400/400', 'categoria' => 'Cocina', 'nuevo' => true],
    ['titulo' => 'Deportes en Comunidad', 'autor' => 'Pedro Martinez', 'descripcion' => 'Todo sobre deporte local y amateur', 'episodios' => 56, 'oyentes' => '3.1K', 'imagen' => 'https://picsum.photos/seed/pod4/400/400', 'categoria' => 'Deportes', 'nuevo' => false],
    ['titulo' => 'Tecnologia Accesible', 'autor' => 'Laura Sanchez', 'descripcion' => 'Tech explicada de forma sencilla', 'episodios' => 41, 'oyentes' => '2.7K', 'imagen' => 'https://picsum.photos/seed/pod5/400/400', 'categoria' => 'Tecnologia', 'nuevo' => false],
    ['titulo' => 'Bienestar Diario', 'autor' => 'Sofia Torres', 'descripcion' => 'Salud mental y fisica para todos', 'episodios' => 38, 'oyentes' => '2.1K', 'imagen' => 'https://picsum.photos/seed/pod6/400/400', 'categoria' => 'Salud', 'nuevo' => true],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-teal-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                Podcasts
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($podcasts as $podcast): ?>
                <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                    <div class="relative aspect-square overflow-hidden">
                        <img src="<?php echo esc_url($podcast['imagen']); ?>" alt="<?php echo esc_attr($podcast['titulo']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <?php if ($podcast['nuevo']): ?>
                            <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-bold bg-teal-500 text-white">NUEVO</span>
                        <?php endif; ?>
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-white/90 text-gray-700"><?php echo esc_html($podcast['categoria']); ?></span>
                        <button class="absolute bottom-4 right-4 p-4 rounded-full bg-teal-500 text-white opacity-0 group-hover:opacity-100 transition-all duration-300 hover:scale-110 hover:bg-teal-600 shadow-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-teal-600 transition-colors"><?php echo esc_html($podcast['titulo']); ?></h3>
                        <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($podcast['descripcion']); ?></p>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white text-xs font-bold">
                                <?php echo esc_html(substr($podcast['autor'], 0, 1)); ?>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?php echo esc_html($podcast['autor']); ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-gray-100">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                                <?php echo esc_html($podcast['episodios']); ?> episodios
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <?php echo esc_html($podcast['oyentes']); ?> oyentes
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#todos-podcasts" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%); color: white;">
                <span>Ver Todos los Podcasts</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
