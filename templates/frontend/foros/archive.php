<?php
/**
 * Frontend: Archive de Foros de Discusion
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$temas = $temas ?? [];
$total_temas = $total_temas ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive foros">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Foros de Discusion</h1>
            <p class="text-white/90 text-lg">Participa en conversaciones con tu comunidad</p>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Estadisticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-600">124</span>
                <span class="text-sm text-gray-500">Temas activos</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-purple-600">1.8k</span>
                <span class="text-sm text-gray-500">Respuestas</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-600">342</span>
                <span class="text-sm text-gray-500">Miembros</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-purple-600">37</span>
                <span class="text-sm text-gray-500">Esta semana</span>
            </div>
        </div>

        <!-- Categorias -->
        <div class="flex flex-wrap gap-3 mb-8">
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-indigo-600 text-white">Todos</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">General</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Tecnologia</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Cultura</button>
            <button class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Deportes</button>
        </div>

        <!-- CTA Crear Tema -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600">
                Mostrando <span class="font-semibold"><?php echo count($temas); ?></span> de <?php echo esc_html($total_temas); ?> temas
            </p>
            <a href="/foros/nuevo-tema/" class="inline-flex items-center px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
               style="background: linear-gradient(135deg, #6366f1 0%, #9333ea 100%);">
                + Crear Tema
            </a>
        </div>

        <?php if (empty($temas)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay temas de discusion</h3>
                <p class="text-gray-500">Se el primero en crear un tema</p>
            </div>
        <?php else: ?>
            <!-- Lista de temas -->
            <div class="space-y-4">
                <?php foreach ($temas as $tema): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-start gap-4">
                            <!-- Avatar autor -->
                            <img src="<?php echo esc_url($tema['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                 alt="<?php echo esc_attr($tema['autor'] ?? 'Usuario'); ?>"
                                 class="w-10 h-10 rounded-full object-cover flex-shrink-0">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <?php if (!empty($tema['fijado'])): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">Fijado</span>
                                    <?php endif; ?>
                                    <?php if (!empty($tema['popular'])): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600">Tema caliente</span>
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
                                    <a href="<?php echo esc_url($tema['url'] ?? '#'); ?>">
                                        <?php echo esc_html($tema['titulo']); ?>
                                    </a>
                                </h2>

                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                    <span class="font-medium text-gray-700"><?php echo esc_html($tema['autor'] ?? 'Usuario'); ?></span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                        <?php echo esc_html($tema['respuestas'] ?? 0); ?> respuestas
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <?php echo esc_html($tema['vistas'] ?? 0); ?> vistas
                                    </span>
                                    <span><?php echo esc_html($tema['ultima_respuesta'] ?? 'hace 2h'); ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginacion -->
            <?php if ($total_temas > $por_pagina): ?>
                <nav class="flex items-center justify-center gap-2 mt-8">
                    <?php
                    $total_paginas = ceil($total_temas / $por_pagina);
                    for ($contador_pagina = 1; $contador_pagina <= $total_paginas; $contador_pagina++):
                    ?>
                        <a href="?pagina=<?php echo $contador_pagina; ?>"
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $contador_pagina === $pagina_actual ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-indigo-100'; ?>">
                            <?php echo $contador_pagina; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
