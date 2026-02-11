<?php
/**
 * Frontend: Busqueda de Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search biblioteca">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Libros', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Titulo o autor', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Buscar por titulo, autor...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Genero -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Genero', 'flavor-chat-ia'); ?></label>
                        <select name="genero" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('novela', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Novela', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('ciencia_ficcion', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Ciencia Ficcion', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('fantasia', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Fantasia', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('thriller', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Thriller', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('historia', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Historia', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Filtros rapidos -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-indigo-100 transition-colors">
                        <input type="checkbox" name="solo_disponibles" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Solo disponibles', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos ese libro', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos o navega por generos', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-indigo-600 font-medium hover:text-indigo-700"><?php echo esc_html__('Ver todos los libros', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach ($resultados as $libro): ?>
                    <article class="group">
                        <div class="relative aspect-[2/3] rounded-xl overflow-hidden shadow-md mb-3">
                            <img src="<?php echo esc_url($libro['portada'] ?? 'https://picsum.photos/seed/libro' . rand(1,100) . '/200/300'); ?>"
                                 alt="<?php echo esc_attr($libro['titulo']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php if (!empty($libro['disponible'])): ?>
                                <span class="absolute top-2 right-2 w-3 h-3 rounded-full bg-green-500"></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 text-sm">
                            <a href="<?php echo esc_url($libro['url'] ?? '#'); ?>">
                                <?php echo esc_html($libro['titulo']); ?>
                            </a>
                        </h3>
                        <p class="text-xs text-gray-500"><?php echo esc_html($libro['autor'] ?? ''); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Libros destacados -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Novedades en la Biblioteca', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <article class="group">
                        <div class="relative aspect-[2/3] rounded-xl overflow-hidden shadow-md mb-3">
                            <img src="https://picsum.photos/seed/libro<?php echo $i; ?>/200/300"
                                 alt="<?php echo esc_attr__('Libro destacado', 'flavor-chat-ia'); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <span class="absolute top-2 right-2 w-3 h-3 rounded-full bg-green-500"></span>
                        </div>
                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 text-sm">
                            Titulo Libro <?php echo $i; ?>
                        </h3>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Autor Ejemplo', 'flavor-chat-ia'); ?></p>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
