<?php
/**
 * Frontend: Busqueda Red Social
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$consulta_busqueda = $consulta_busqueda ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search red-social">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-pink-500 to-rose-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar personas y publicaciones', 'flavor-chat-ia'); ?></h1>

            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="<?php echo esc_attr($consulta_busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Buscar personas, publicaciones, grupos...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 text-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                </div>

                <!-- Sugerencias -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500"><?php echo esc_html__('Sugerencias:', 'flavor-chat-ia'); ?></span>
                    <a href="?q=eventos" class="px-3 py-1 rounded-full text-sm bg-pink-50 text-pink-600 hover:bg-pink-100 transition-colors"><?php echo esc_html__('eventos', 'flavor-chat-ia'); ?></a>
                    <a href="?q=proyectos" class="px-3 py-1 rounded-full text-sm bg-pink-50 text-pink-600 hover:bg-pink-100 transition-colors"><?php echo esc_html__('proyectos', 'flavor-chat-ia'); ?></a>
                    <a href="?q=vecinos" class="px-3 py-1 rounded-full text-sm bg-pink-50 text-pink-600 hover:bg-pink-100 transition-colors"><?php echo esc_html__('vecinos', 'flavor-chat-ia'); ?></a>
                    <a href="?q=actividades" class="px-3 py-1 rounded-full text-sm bg-pink-50 text-pink-600 hover:bg-pink-100 transition-colors"><?php echo esc_html__('actividades', 'flavor-chat-ia'); ?></a>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
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
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontraron resultados', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos de busqueda', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-pink-600 font-medium hover:text-pink-700"><?php echo esc_html__('Explorar la red social', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $resultado): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center gap-3 mb-3">
                            <img src="<?php echo esc_url($resultado['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                 alt="" class="w-12 h-12 rounded-full object-cover">
                            <div>
                                <p class="font-bold text-gray-900"><?php echo esc_html($resultado['nombre'] ?? 'Usuario'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($resultado['tipo'] ?? 'Persona'); ?></p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">
                            <?php echo esc_html($resultado['descripcion'] ?? ''); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Personas sugeridas -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Personas sugeridas', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($indice_persona = 1; $indice_persona <= 6; $indice_persona++): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow text-center">
                        <img src="https://i.pravatar.cc/150?img=<?php echo $indice_persona * 7; ?>" alt="<?php echo esc_attr__('Persona sugerida', 'flavor-chat-ia'); ?>"
                             class="w-16 h-16 rounded-full object-cover mx-auto mb-3">
                        <h3 class="font-bold text-gray-900 mb-1">Vecino <?php echo $indice_persona; ?></h3>
                        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html__('3 conexiones en comun', 'flavor-chat-ia'); ?></p>
                        <button class="px-4 py-2 rounded-lg text-sm font-semibold text-pink-600 bg-pink-50 hover:bg-pink-100 transition-colors">
                            <?php echo esc_html__('Conectar', 'flavor-chat-ia'); ?>
                        </button>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
