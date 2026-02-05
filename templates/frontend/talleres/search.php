<?php
/**
 * Frontend: Busqueda de Talleres
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['ceramica', 'fotografia', 'cocina vegana', 'programacion'];
?>

<div class="flavor-frontend flavor-talleres-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-purple-500 to-violet-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar talleres</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="¿Que quieres aprender? (ej: ceramica, fotografia, cocina...)"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-purple-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-violet-600 text-white p-3 rounded-lg hover:bg-violet-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-purple-100 text-sm">Populares:</span>
            <?php foreach ($sugerencias as $sugerencia_taller): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_taller); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_taller); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> taller<?php echo $total_resultados !== 1 ? 'es' : ''; ?>
                para "<span class="text-violet-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-violet-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🎨</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos talleres</h3>
        <p class="text-gray-500 mb-6">¿Quieres impartir un taller? ¡Crealo ahora!</p>
        <button class="bg-purple-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-purple-600 transition-colors"
                onclick="flavorTalleres.crearTaller()">
            Crear Taller
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_taller): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($resultado_taller['imagen'])): ?>
                <img src="<?php echo esc_url($resultado_taller['imagen']); ?>" alt="<?php echo esc_attr($resultado_taller['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">🎨</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 right-3 <?php echo ($resultado_taller['nivel'] ?? '') === 'Principiante' ? 'bg-green-500' : (($resultado_taller['nivel'] ?? '') === 'Intermedio' ? 'bg-amber-500' : 'bg-red-500'); ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
                    <?php echo esc_html($resultado_taller['nivel'] ?? 'Todos'); ?>
                </span>
            </div>
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-violet-600 transition-colors">
                    <a href="<?php echo esc_url($resultado_taller['url'] ?? '#'); ?>">
                        <?php echo esc_html($resultado_taller['titulo']); ?>
                    </a>
                </h3>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-medium">
                        <?php echo esc_html(mb_substr($resultado_taller['instructor_nombre'] ?? 'I', 0, 1)); ?>
                    </div>
                    <span class="text-sm text-gray-600"><?php echo esc_html($resultado_taller['instructor_nombre'] ?? ''); ?></span>
                </div>
                <div class="flex flex-wrap gap-2 mb-3 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-1 rounded-full">📅 <?php echo esc_html($resultado_taller['fecha'] ?? ''); ?></span>
                    <span class="bg-gray-100 px-2 py-1 rounded-full">⏱️ <?php echo esc_html($resultado_taller['duracion'] ?? ''); ?></span>
                    <span class="bg-gray-100 px-2 py-1 rounded-full">💺 <?php echo esc_html($resultado_taller['plazas_disponibles'] ?? 0); ?> plazas</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="font-bold <?php echo ($resultado_taller['precio'] ?? 0) == 0 ? 'text-green-600' : 'text-violet-600'; ?>">
                        <?php echo ($resultado_taller['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($resultado_taller['precio']) . ' €'; ?>
                    </span>
                    <a href="<?php echo esc_url($resultado_taller['url'] ?? '#'); ?>"
                       class="text-violet-600 hover:text-violet-700 font-medium text-sm">
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
