<?php
/**
 * Frontend: Archive de Colectivos y Asociaciones
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$colectivos = $colectivos ?? [];
$total_colectivos = $total_colectivos ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive colectivos">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-rose-500 to-red-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Colectivos y Asociaciones</h1>
            <p class="text-white/90 text-lg">Encuentra y unete a grupos de tu comunidad</p>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Estadisticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-rose-600">48</span>
                <span class="text-sm text-gray-500">Colectivos registrados</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-red-600">1.5k</span>
                <span class="text-sm text-gray-500">Miembros</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-rose-600">210</span>
                <span class="text-sm text-gray-500">Actividades</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-red-600">36</span>
                <span class="text-sm text-gray-500">Eventos</span>
            </div>
        </div>

        <!-- Categorias -->
        <div class="flex flex-wrap gap-3 mb-8">
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-rose-600 text-white">Todos</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">Cultural</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">Deportivo</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">Social</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">Medioambiental</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">Vecinal</button>
        </div>

        <!-- CTA Registrar Colectivo -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600">
                Mostrando <span class="font-semibold"><?php echo count($colectivos); ?></span> de <?php echo esc_html($total_colectivos); ?> colectivos
            </p>
            <button class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);">
                + Registrar Colectivo
            </button>
        </div>

        <?php if (empty($colectivos)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay colectivos registrados</h3>
                <p class="text-gray-500">Registra tu colectivo y empieza a conectar</p>
            </div>
        <?php else: ?>
            <!-- Grid de colectivos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($colectivos as $colectivo): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <div class="p-6">
                            <!-- Logo placeholder y nombre -->
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold text-xl">
                                        <?php echo esc_html(mb_substr($colectivo['nombre'] ?? 'C', 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900 group-hover:text-rose-600 transition-colors">
                                        <a href="<?php echo esc_url($colectivo['url'] ?? '#'); ?>">
                                            <?php echo esc_html($colectivo['nombre'] ?? 'Colectivo'); ?>
                                        </a>
                                    </h2>
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-700">
                                        <?php echo esc_html($colectivo['categoria'] ?? 'General'); ?>
                                    </span>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 line-clamp-2 mb-4">
                                <?php echo esc_html($colectivo['descripcion'] ?? ''); ?>
                            </p>

                            <div class="space-y-2 text-sm text-gray-500">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span><?php echo esc_html($colectivo['miembros'] ?? 0); ?> miembros</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span><?php echo esc_html($colectivo['reunion'] ?? 'Martes y jueves'); ?></span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="<?php echo esc_url($colectivo['url'] ?? '#'); ?>"
                                   class="block w-full py-2 rounded-xl text-center text-rose-600 font-semibold text-sm bg-rose-50 hover:bg-rose-100 transition-colors">
                                    Ver colectivo
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginacion -->
            <?php if ($total_colectivos > $por_pagina): ?>
                <nav class="flex items-center justify-center gap-2 mt-8">
                    <?php
                    $total_paginas = ceil($total_colectivos / $por_pagina);
                    for ($contador_pagina = 1; $contador_pagina <= $total_paginas; $contador_pagina++):
                    ?>
                        <a href="?pagina=<?php echo $contador_pagina; ?>"
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $contador_pagina === $pagina_actual ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-rose-100'; ?>">
                            <?php echo $contador_pagina; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
