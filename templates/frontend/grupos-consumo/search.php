<?php
/**
 * Frontend: Búsqueda de Grupos de Consumo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['verduras', 'fruta', 'huevos', 'pan', 'carne'];
?>

<div class="flavor-frontend flavor-grupos-consumo-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar grupos de consumo</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="Busca por nombre, zona o tipo de productos..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-lime-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-lime-600 text-white p-3 rounded-lg hover:bg-lime-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-lime-100 text-sm">Populares:</span>
            <?php foreach ($sugerencias as $sug): ?>
            <a href="?q=<?php echo esc_attr($sug); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sug); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-lime-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-lime-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos grupos</h3>
        <p class="text-gray-500 mb-6">Prueba con otros términos o crea tu propio grupo</p>
        <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                onclick="flavorGruposConsumo.crearGrupo()">
            Crear grupo de consumo
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $grupo): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100">
            <div class="h-32 bg-gradient-to-br from-lime-400 to-green-500 flex items-center justify-center">
                <span class="text-5xl opacity-50">🥕</span>
            </div>
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($grupo['url']); ?>" class="hover:text-lime-600 transition-colors">
                        <?php echo esc_html($grupo['nombre']); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($grupo['descripcion']); ?></p>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>👥 <?php echo esc_html($grupo['num_miembros'] ?? 0); ?> miembros</span>
                    <span>📍 <?php echo esc_html($grupo['zona'] ?? ''); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
