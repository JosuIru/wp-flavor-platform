<?php
/**
 * Frontend: Busqueda de Foros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$consulta_busqueda = $consulta_busqueda ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search foros">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Buscar en foros</h1>

            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="<?php echo esc_attr($consulta_busqueda); ?>"
                           placeholder="Buscar temas, respuestas, usuarios..."
                           class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Sugerencias -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500">Sugerencias:</span>
                    <a href="?q=ayuda" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">ayuda</a>
                    <a href="?q=recomendaciones" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">recomendaciones</a>
                    <a href="?q=debate" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">debate</a>
                    <a href="?q=opinion" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">opinion</a>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #6366f1 0%, #9333ea 100%);">
                        Buscar en Foros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <?php if (!empty($consulta_busqueda)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($consulta_busqueda); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($consulta_busqueda)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron resultados</h3>
                <p class="text-gray-500 mb-4">Prueba con otros terminos de busqueda</p>
                <a href="?" class="text-indigo-600 font-medium hover:text-indigo-700">Ver todos los temas</a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="space-y-4">
                <?php foreach ($resultados as $resultado): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-start gap-4">
                            <img src="<?php echo esc_url($resultado['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                 alt="" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
                                    <a href="<?php echo esc_url($resultado['url'] ?? '#'); ?>">
                                        <?php echo esc_html($resultado['titulo'] ?? 'Tema'); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                                    <?php echo esc_html($resultado['extracto'] ?? ''); ?>
                                </p>
                                <div class="flex items-center gap-3 text-xs text-gray-500">
                                    <span><?php echo esc_html($resultado['autor'] ?? 'Usuario'); ?></span>
                                    <span><?php echo esc_html($resultado['respuestas'] ?? 0); ?> respuestas</span>
                                    <span><?php echo esc_html($resultado['fecha'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Temas populares -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Temas populares</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($indice_tema = 1; $indice_tema <= 6; $indice_tema++): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700 mb-2">General</span>
                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">
                            Tema de discusion <?php echo $indice_tema; ?>
                        </h3>
                        <p class="text-sm text-gray-500">12 respuestas - hace 2 dias</p>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
