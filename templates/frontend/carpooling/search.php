<?php
/**
 * Frontend: Busqueda de Carpooling
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao'];
?>

<div class="flavor-frontend flavor-carpooling-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-lime-500 to-green-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar viajes</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="¿A donde quieres ir? (ej: Madrid, Barcelona, centro...)"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-lime-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-lime-100 text-sm">Destinos populares:</span>
            <?php foreach ($sugerencias as $sugerencia_destino): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_destino); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_destino); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> viaje<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-green-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-green-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🚗</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos viajes</h3>
        <p class="text-gray-500 mb-6">¿Por que no ofreces tu un viaje a ese destino?</p>
        <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                onclick="flavorCarpooling.ofrecerViaje()">
            Ofrecer Viaje
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_viaje): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Ruta -->
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex flex-col items-center">
                        <div class="w-3 h-3 rounded-full bg-lime-500"></div>
                        <div class="w-0.5 h-6 bg-lime-300"></div>
                        <div class="w-3 h-3 rounded-full bg-green-600"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($resultado_viaje['origen'] ?? ''); ?></p>
                        <div class="h-2"></div>
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($resultado_viaje['destino'] ?? ''); ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        📅 <?php echo esc_html($resultado_viaje['fecha'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        🕐 <?php echo esc_html($resultado_viaje['hora'] ?? ''); ?>
                    </span>
                    <span class="bg-lime-100 text-lime-700 text-xs px-3 py-1 rounded-full">
                        💺 <?php echo esc_html($resultado_viaje['plazas_libres'] ?? 0); ?> plazas
                    </span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($resultado_viaje['conductor_nombre'] ?? 'C', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($resultado_viaje['conductor_nombre'] ?? ''); ?></span>
                    </div>
                    <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($resultado_viaje['precio'] ?? '0'); ?> €
                    </span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
