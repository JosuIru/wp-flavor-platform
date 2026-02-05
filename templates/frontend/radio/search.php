<?php
/**
 * Frontend: Busqueda de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search radio">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-red-600 to-rose-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Buscar en Radio</h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar programas</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="Nombre del programa, locutor, tema..."
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                        <select name="tipo" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Todos</option>
                            <option value="magazine">Magazine</option>
                            <option value="informativo">Informativo</option>
                            <option value="musical">Musical</option>
                            <option value="deportes">Deportes</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #dc2626 0%, #e11d48 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Buscar
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
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos programas</h3>
                <p class="text-gray-500 mb-4">Prueba con otros terminos de busqueda</p>
                <a href="?" class="text-red-600 font-medium hover:text-red-700">Ver todos los programas</a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $programa): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/9] overflow-hidden">
                            <img src="<?php echo esc_url($programa['imagen'] ?? 'https://picsum.photos/seed/radio' . rand(1,100) . '/400/225'); ?>"
                                 alt="<?php echo esc_attr($programa['titulo']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-red-600 transition-colors mb-2">
                                <a href="<?php echo esc_url($programa['url'] ?? '#'); ?>">
                                    <?php echo esc_html($programa['titulo']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo esc_html($programa['horario'] ?? 'L-V 10:00'); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h2 class="text-xl font-bold text-gray-900 mb-6">Programas Destacados</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/9] overflow-hidden">
                            <img src="https://picsum.photos/seed/radio<?php echo $i; ?>/400/225"
                                 alt="Programa destacado"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-red-600 transition-colors mb-2">
                                Programa <?php echo $i; ?>
                            </h3>
                            <p class="text-sm text-gray-500">L-V 10:00-12:00</p>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
