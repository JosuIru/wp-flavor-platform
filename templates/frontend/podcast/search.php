<?php
/**
 * Frontend: Busqueda de Podcasts
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search podcast">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Podcasts', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Que quieres escuchar?', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Buscar por titulo, tema o creador...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
                        <select name="categoria" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('actualidad', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Actualidad', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('entrevistas', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Entrevistas', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('historia', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Historia', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cultura', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <?php if (!empty($query)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos podcasts', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos de busqueda', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-teal-600 font-medium hover:text-teal-700"><?php echo esc_html__('Ver todos los podcasts', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($resultados as $podcast): ?>
                    <article class="group">
                        <div class="relative aspect-square rounded-xl overflow-hidden shadow-md mb-3">
                            <img src="<?php echo esc_url($podcast['portada'] ?? 'https://picsum.photos/seed/podcast' . rand(1,100) . '/200/200'); ?>"
                                 alt="<?php echo esc_attr($podcast['titulo']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <button class="absolute bottom-2 right-2 w-10 h-10 rounded-full bg-teal-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                        <h3 class="font-bold text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2 text-sm">
                            <a href="<?php echo esc_url($podcast['url'] ?? '#'); ?>">
                                <?php echo esc_html($podcast['titulo']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-500"><?php echo esc_html($podcast['episodios'] ?? 0); ?> episodios</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Podcasts destacados -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Podcasts Populares', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <article class="group">
                        <div class="relative aspect-square rounded-xl overflow-hidden shadow-md mb-3">
                            <img src="https://picsum.photos/seed/podcast<?php echo $i; ?>/200/200"
                                 alt="<?php echo esc_attr__('Podcast destacado', 'flavor-chat-ia'); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <button class="absolute bottom-2 right-2 w-10 h-10 rounded-full bg-teal-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                        <h3 class="font-bold text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2 text-sm">
                            Podcast Ejemplo <?php echo $i; ?>
                        </h3>
                        <p class="text-xs text-gray-500"><?php echo rand(5, 50); ?> episodios</p>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
