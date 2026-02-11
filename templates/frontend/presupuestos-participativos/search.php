<?php
/**
 * Frontend: Busqueda de Presupuestos Participativos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias_busqueda = $sugerencias_busqueda ?? ['parque', 'biblioteca', 'carril bici', 'iluminacion'];
?>

<div class="flavor-frontend flavor-presupuestos-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-amber-500 to-yellow-500 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('Buscar proyectos', 'flavor-chat-ia'); ?></h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Busca proyectos de presupuestos participativos...', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-amber-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-amber-600 text-white p-3 rounded-lg hover:bg-amber-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias populares -->
        <?php if (!empty($sugerencias_busqueda) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-amber-100 text-sm"><?php echo esc_html__('Sugerencias:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias_busqueda as $sugerencia_texto): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_texto); ?>"
               class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_texto); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <!-- Contador de resultados -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<?php echo esc_html($query); ?>"
            <?php else: ?>
                Sin resultados para "<?php echo esc_html($query); ?>"
            <?php endif; ?>
        </h3>
        <?php if ($total_resultados > 0): ?>
        <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            <option value="<?php echo esc_attr__('relevancia', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mas relevantes', 'flavor-chat-ia'); ?></option>
            <option value="<?php echo esc_attr__('presupuesto', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mayor presupuesto', 'flavor-chat-ia'); ?></option>
            <option value="<?php echo esc_attr__('votos', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mas votados', 'flavor-chat-ia'); ?></option>
        </select>
        <?php endif; ?>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4"><?php echo esc_html__('&#x1F4B0;', 'flavor-chat-ia'); ?></div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos proyectos', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Prueba con otros terminos o propone un nuevo proyecto', 'flavor-chat-ia'); ?></p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                    onclick="flavorPresupuestos.nuevoProyecto()">
                <?php echo esc_html__('Proponer Proyecto', 'flavor-chat-ia'); ?>
            </button>
            <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>"
               class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-300 transition-colors">
                <?php echo esc_html__('Ver todos los proyectos', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_proyecto): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-medium">
                    <?php echo esc_html(ucfirst($resultado_proyecto['fase'] ?? 'propuestas')); ?>
                </span>
                <span class="font-bold text-amber-600"><?php echo esc_html($resultado_proyecto['presupuesto'] ?? ''); ?></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                <a href="<?php echo esc_url($resultado_proyecto['url'] ?? '#'); ?>" class="hover:text-amber-600 transition-colors">
                    <?php echo esc_html($resultado_proyecto['titulo'] ?? ''); ?>
                </a>
            </h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($resultado_proyecto['descripcion'] ?? ''); ?></p>
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span><?php echo esc_html($resultado_proyecto['categoria'] ?? ''); ?></span>
                <span><?php echo esc_html($resultado_proyecto['votos'] ?? 0); ?> votos</span>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($total_resultados > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total_resultados / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
