<?php
/**
 * Frontend: Busqueda de Bares y Restaurantes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['tapas', 'italiano', 'terraza', 'menu del dia'];
?>

<div class="flavor-frontend flavor-bares-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('🔍 Buscar bares y restaurantes', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('¿Que tipo de local buscas? (ej: tapas, italiano, terraza...)', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-amber-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-orange-600 text-white p-3 rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-amber-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_bar): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_bar); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_bar); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> local<?php echo $total_resultados !== 1 ? 'es' : ''; ?>
                para "<span class="text-orange-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-orange-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🍽️</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos locales', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('¿Conoces un buen sitio? ¡Registralo!', 'flavor-chat-ia'); ?></p>
        <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                onclick="flavorBares.registrarLocal()">
            <?php echo esc_html__('Registrar Local', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_local): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($resultado_local['imagen'])): ?>
                <img src="<?php echo esc_url($resultado_local['imagen']); ?>" alt="<?php echo esc_attr($resultado_local['nombre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">🍽️</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 left-3 <?php echo ($resultado_local['abierto'] ?? false) ? 'bg-green-500' : 'bg-red-500'; ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
                    <?php echo ($resultado_local['abierto'] ?? false) ? 'Abierto' : 'Cerrado'; ?>
                </span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="bg-amber-100 text-amber-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($resultado_local['tipo_cocina'] ?? 'Variada'); ?>
                    </span>
                    <span class="text-amber-600 font-bold text-sm">
                        <?php echo esc_html(str_repeat('€', $resultado_local['rango_precio'] ?? 1)); ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-orange-600 transition-colors">
                    <a href="<?php echo esc_url($resultado_local['url'] ?? '#'); ?>">
                        <?php echo esc_html($resultado_local['nombre']); ?>
                    </a>
                </h3>
                <div class="flex items-center gap-1 mb-2">
                    <div class="flex text-yellow-400 text-sm">
                        <?php
                        $valoracion_resultado = floatval($resultado_local['valoracion'] ?? 0);
                        for ($indice_star = 1; $indice_star <= 5; $indice_star++):
                        ?>
                        <span><?php echo $indice_star <= $valoracion_resultado ? '★' : '☆'; ?></span>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-500">(<?php echo esc_html($resultado_local['total_resenas'] ?? 0); ?>)</span>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>📍 <?php echo esc_html($resultado_local['distancia'] ?? ''); ?></span>
                    <span>⭐ <?php echo esc_html($resultado_local['valoracion'] ?? '0'); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
