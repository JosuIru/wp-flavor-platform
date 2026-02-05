<?php
/**
 * Frontend: Archive de Podcasts
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$podcasts = $podcasts ?? [];
$total_podcasts = $total_podcasts ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive podcast">
    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Podcasts del Barrio</h1>
            <p class="text-white/90 text-lg">Escucha las voces de tu comunidad</p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_podcasts); ?> podcasts disponibles</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de podcasts -->
            <main class="flex-1">
                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        Mostrando <span class="font-semibold"><?php echo count($podcasts); ?></span> de <?php echo esc_html($total_podcasts); ?> podcasts
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-teal-500">
                        <option>Mas recientes</option>
                        <option>Mas escuchados</option>
                        <option>Mejor valorados</option>
                        <option>Alfabetico A-Z</option>
                    </select>
                </div>

                <?php if (empty($podcasts)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay podcasts disponibles</h3>
                        <p class="text-gray-500">Se el primero en crear un podcast</p>
                    </div>
                <?php else: ?>
                    <!-- Grid de podcasts -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($podcasts as $podcast): ?>
                            <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                                <div class="relative aspect-square overflow-hidden">
                                    <img src="<?php echo esc_url($podcast['portada'] ?? 'https://picsum.photos/seed/podcast' . rand(1,100) . '/400/400'); ?>"
                                         alt="<?php echo esc_attr($podcast['titulo']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                                    <!-- Boton play -->
                                    <button class="absolute bottom-4 right-4 w-12 h-12 rounded-full bg-teal-500 text-white flex items-center justify-center shadow-lg hover:bg-teal-600 transition-colors">
                                        <svg class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>

                                    <!-- Episodios -->
                                    <span class="absolute top-3 left-3 px-2 py-1 rounded-full text-xs font-bold bg-black/50 text-white">
                                        <?php echo esc_html($podcast['episodios'] ?? 0); ?> episodios
                                    </span>
                                </div>

                                <div class="p-5">
                                    <h2 class="text-lg font-bold text-gray-900 group-hover:text-teal-600 transition-colors mb-1">
                                        <a href="<?php echo esc_url($podcast['url'] ?? '#'); ?>">
                                            <?php echo esc_html($podcast['titulo']); ?>
                                        </a>
                                    </h2>
                                    <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($podcast['autor'] ?? 'Creador'); ?></p>
                                    <p class="text-sm text-gray-600 line-clamp-2"><?php echo esc_html($podcast['descripcion'] ?? ''); ?></p>

                                    <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <?php echo esc_html($podcast['reproducciones'] ?? '0'); ?>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <?php echo esc_html($podcast['suscriptores'] ?? '0'); ?>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_podcasts > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_podcasts / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-teal-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
