<?php
/**
 * Frontend: Busqueda de Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search cursos">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-purple-600 to-violet-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Cursos', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Que quieres aprender?', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Cocina, idiomas, manualidades...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
                        <select name="categoria" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('manualidades', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Manualidades', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('cocina', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cocina', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('idiomas', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Idiomas', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('informatica', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Informatica', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('arte', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Arte', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Filtros rapidos -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-purple-100 transition-colors">
                        <input type="checkbox" name="gratuitos" value="1" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Gratuitos', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-purple-100 transition-colors">
                        <input type="checkbox" name="con_plazas" value="1" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Con plazas', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-purple-100 transition-colors">
                        <input type="checkbox" name="online" value="1" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Online', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar Cursos', 'flavor-chat-ia'); ?>
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
                    <?php echo esc_html($total_resultados); ?> cursos de "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos cursos', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos o categorias', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-purple-600 font-medium hover:text-purple-700"><?php echo esc_html__('Ver todos los cursos', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $curso): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/9] overflow-hidden">
                            <img src="<?php echo esc_url($curso['imagen'] ?? 'https://picsum.photos/seed/curso' . rand(1,100) . '/400/225'); ?>"
                                 alt="<?php echo esc_attr($curso['titulo']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php if (!empty($curso['gratuito'])): ?>
                                <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-700 mb-2">
                                <?php echo esc_html($curso['categoria'] ?? 'General'); ?>
                            </span>
                            <h3 class="font-bold text-gray-900 group-hover:text-purple-600 transition-colors mb-2">
                                <a href="<?php echo esc_url($curso['url'] ?? '#'); ?>">
                                    <?php echo esc_html($curso['titulo']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($curso['fecha'] ?? ''); ?></p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-purple-600"><?php echo esc_html($curso['precio'] ?? 'Gratis'); ?></span>
                                <a href="<?php echo esc_url($curso['url'] ?? '#'); ?>" class="text-purple-600 font-medium text-sm hover:text-purple-700">
                                    <?php echo esc_html__('Ver curso →', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Cursos destacados -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Proximos Cursos', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/9] overflow-hidden">
                            <img src="https://picsum.photos/seed/curso<?php echo $i; ?>/400/225"
                                 alt="<?php echo esc_attr__('Curso destacado', 'flavor-chat-ia'); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-5">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-700 mb-2">
                                <?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?>
                            </span>
                            <h3 class="font-bold text-gray-900 group-hover:text-purple-600 transition-colors mb-2">
                                Titulo Curso <?php echo $i; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3"><?php echo esc_html__('15 Feb · 18:00', 'flavor-chat-ia'); ?></p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-purple-600">25€</span>
                                <a href="#" class="text-purple-600 font-medium text-sm hover:text-purple-700"><?php echo esc_html__('Ver curso →', 'flavor-chat-ia'); ?></a>
                            </div>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
