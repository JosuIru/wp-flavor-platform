<?php
/**
 * Template: Galeria Grid de Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Galeria Multimedia';
$descripcion = $descripcion ?? 'Videos, fotos y contenido de la comunidad';

$filtros = ['todos' => 'Todos', 'videos' => 'Videos', 'fotos' => 'Fotos', 'eventos' => 'Eventos', 'entrevistas' => 'Entrevistas'];
$filtro_activo = $filtro_activo ?? 'todos';

$items = [
    ['titulo' => 'Inauguracion Centro Cultural', 'tipo' => 'video', 'duracion' => '8:45', 'imagen' => 'https://picsum.photos/seed/gal1/400/300', 'vistas' => '1.2K', 'fecha' => 'Hace 2 dias'],
    ['titulo' => 'Maraton Solidaria', 'tipo' => 'foto', 'duracion' => '32 fotos', 'imagen' => 'https://picsum.photos/seed/gal2/400/300', 'vistas' => '890', 'fecha' => 'Hace 3 dias'],
    ['titulo' => 'Entrevista Alcaldesa', 'tipo' => 'video', 'duracion' => '22:15', 'imagen' => 'https://picsum.photos/seed/gal3/400/300', 'vistas' => '2.3K', 'fecha' => 'Hace 4 dias'],
    ['titulo' => 'Mercadillo Navidad', 'tipo' => 'foto', 'duracion' => '48 fotos', 'imagen' => 'https://picsum.photos/seed/gal4/400/300', 'vistas' => '1.8K', 'fecha' => 'Hace 5 dias'],
    ['titulo' => 'Concierto Bandas Locales', 'tipo' => 'video', 'duracion' => '1:15:30', 'imagen' => 'https://picsum.photos/seed/gal5/400/300', 'vistas' => '3.1K', 'fecha' => 'Hace 1 semana'],
    ['titulo' => 'Taller de Ceramica', 'tipo' => 'video', 'duracion' => '12:20', 'imagen' => 'https://picsum.photos/seed/gal6/400/300', 'vistas' => '456', 'fecha' => 'Hace 1 semana'],
    ['titulo' => 'Festival Gastronomico', 'tipo' => 'foto', 'duracion' => '65 fotos', 'imagen' => 'https://picsum.photos/seed/gal7/400/300', 'vistas' => '2.1K', 'fecha' => 'Hace 2 semanas'],
    ['titulo' => 'Competencia de Skate', 'tipo' => 'video', 'duracion' => '18:40', 'imagen' => 'https://picsum.photos/seed/gal8/400/300', 'vistas' => '987', 'fecha' => 'Hace 2 semanas'],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <?php echo esc_html__('Galeria', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <?php foreach ($filtros as $slug => $nombre): ?>
                <button class="px-5 py-2 rounded-full text-sm font-medium transition-all duration-300 <?php echo $slug === $filtro_activo ? '' : 'hover:bg-fuchsia-50'; ?>"
                        style="<?php echo $slug === $filtro_activo ? 'background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;' : 'background: white; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
                    <?php echo esc_html($nombre); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($items as $item): ?>
                <article class="group relative bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['titulo']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                        <!-- Badge de tipo -->
                        <span class="absolute top-3 left-3 px-2 py-1 rounded-md text-xs font-bold uppercase <?php echo $item['tipo'] === 'video' ? 'bg-fuchsia-500 text-white' : 'bg-pink-500 text-white'; ?>">
                            <?php if ($item['tipo'] === 'video'): ?>
                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <?php else: ?>
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <?php endif; ?>
                            <?php echo esc_html($item['tipo']); ?>
                        </span>

                        <!-- Duracion/Cantidad -->
                        <span class="absolute top-3 right-3 px-2 py-1 rounded-md text-xs font-semibold bg-black/50 text-white backdrop-blur-sm">
                            <?php echo esc_html($item['duracion']); ?>
                        </span>

                        <!-- Play overlay -->
                        <?php if ($item['tipo'] === 'video'): ?>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="p-4 rounded-full bg-white/90 text-fuchsia-600 hover:scale-110 transition-transform shadow-xl">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 group-hover:text-fuchsia-600 transition-colors line-clamp-2 mb-2"><?php echo esc_html($item['titulo']); ?></h3>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <?php echo esc_html($item['vistas']); ?>
                            </span>
                            <span><?php echo esc_html($item['fecha']); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#toda-galeria" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%); color: white;">
                <span><?php echo esc_html__('Ver Todo el Contenido', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
