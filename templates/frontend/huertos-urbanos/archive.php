<?php
/**
 * Frontend: Archive de Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$huertos = $huertos ?? [];
$total_huertos = $total_huertos ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive huertos-urbanos">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Huertos Urbanos</h1>
            <p class="text-white/90 text-lg">Cultiva tus propios alimentos en la comunidad</p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_huertos); ?> parcelas disponibles</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de huertos -->
            <main class="flex-1">
                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        Mostrando <span class="font-semibold"><?php echo count($huertos); ?></span> de <?php echo esc_html($total_huertos); ?> huertos
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option>Mas cercanos</option>
                        <option>Mas parcelas libres</option>
                        <option>Mas recientes</option>
                        <option>Mejor valorados</option>
                    </select>
                </div>

                <?php if (empty($huertos)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay huertos disponibles</h3>
                        <p class="text-gray-500">Prueba a modificar los filtros de busqueda</p>
                    </div>
                <?php else: ?>
                    <!-- Grid de huertos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($huertos as $huerto): ?>
                            <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                                <div class="relative aspect-[16/10] overflow-hidden">
                                    <img src="<?php echo esc_url($huerto['imagen'] ?? 'https://picsum.photos/seed/huerto' . rand(1,100) . '/600/400'); ?>"
                                         alt="<?php echo esc_attr($huerto['nombre']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                                    <!-- Badge parcelas -->
                                    <?php if (!empty($huerto['parcelas_libres'])): ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                                            <?php echo esc_html($huerto['parcelas_libres']); ?> libres
                                        </span>
                                    <?php else: ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white">Completo</span>
                                    <?php endif; ?>

                                    <!-- Ubicacion -->
                                    <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        <span><?php echo esc_html($huerto['ubicacion'] ?? 'Sin ubicacion'); ?></span>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h2 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                            <a href="<?php echo esc_url($huerto['url'] ?? '#'); ?>">
                                                <?php echo esc_html($huerto['nombre']); ?>
                                            </a>
                                        </h2>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo esc_html($huerto['descripcion'] ?? ''); ?>
                                    </p>

                                    <!-- Info -->
                                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                            </svg>
                                            <?php echo esc_html($huerto['tamano_parcela'] ?? '25'); ?>m²/parcela
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <?php echo esc_html($huerto['precio'] ?? '20€'); ?>/mes
                                        </span>
                                    </div>

                                    <a href="<?php echo esc_url($huerto['url'] ?? '#'); ?>"
                                       class="block w-full py-2.5 rounded-xl text-center font-semibold text-white transition-all hover:scale-105"
                                       style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                                        Ver Huerto
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_huertos > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_huertos / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-100'; ?>">
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
