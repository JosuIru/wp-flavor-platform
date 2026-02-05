<?php
/**
 * Frontend: Busqueda de Publicidad
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['banner', 'video', 'promocion', 'descuento', 'local'];
?>

<div class="flavor-frontend flavor-publicidad-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-pink-500 to-rose-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar campanas</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="Busca por nombre de campana, anunciante o tipo..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-pink-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-rose-600 text-white p-3 rounded-lg hover:bg-rose-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-pink-100 text-sm">Populares:</span>
            <?php foreach ($sugerencias as $sugerencia_campania): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_campania); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_campania); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> campana<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-pink-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-pink-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">📢</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos campanas</h3>
        <p class="text-gray-500 mb-6">Intenta con otros terminos de busqueda</p>
        <button class="bg-pink-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-pink-600 transition-colors"
                onclick="flavorPublicidad.crearCampania()">
            Crear Campana
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_campania): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="bg-pink-100 text-pink-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($resultado_campania['tipo'] ?? 'Banner'); ?>
                    </span>
                    <span class="<?php
                        $estado_resultado = $resultado_campania['estado'] ?? 'activa';
                        echo $estado_resultado === 'activa' ? 'bg-green-100 text-green-700' : ($estado_resultado === 'pausada' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600');
                    ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html(ucfirst($estado_resultado)); ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-pink-600 transition-colors">
                    <a href="<?php echo esc_url($resultado_campania['url'] ?? '#'); ?>">
                        <?php echo esc_html($resultado_campania['titulo']); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo esc_html($resultado_campania['descripcion'] ?? ''); ?></p>
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        💰 <?php echo esc_html($resultado_campania['presupuesto'] ?? '0'); ?> €
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        👁️ <?php echo esc_html($resultado_campania['impresiones'] ?? 0); ?>
                    </span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-pink-100 flex items-center justify-center text-pink-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($resultado_campania['anunciante_nombre'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($resultado_campania['anunciante_nombre'] ?? ''); ?></span>
                    </div>
                    <a href="<?php echo esc_url($resultado_campania['url'] ?? '#'); ?>"
                       class="text-pink-600 hover:text-pink-700 font-medium text-sm">
                        Ver mas →
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
