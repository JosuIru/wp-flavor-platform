<?php
/**
 * Frontend: Busqueda de Avisos Municipales
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias_busqueda = $sugerencias_busqueda ?? ['obras', 'corte agua', 'trafico', 'limpieza', 'eventos'];
?>

<div class="flavor-frontend flavor-avisos-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">Buscar avisos</h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="Busca avisos municipales..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-sky-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias populares -->
        <?php if (!empty($sugerencias_busqueda) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-sky-100 text-sm">Sugerencias:</span>
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
        <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500">
            <option value="relevancia">Mas relevantes</option>
            <option value="recientes">Mas recientes</option>
            <option value="urgencia">Mayor urgencia</option>
        </select>
        <?php endif; ?>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">&#x1F4E2;</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos avisos</h3>
        <p class="text-gray-500 mb-6">Prueba con otros terminos o consulta todos los avisos</p>
        <a href="<?php echo esc_url(home_url('/avisos-municipales/')); ?>"
           class="bg-sky-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-sky-600 transition-colors inline-block">
            Ver todos los avisos
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_aviso): ?>
        <?php
        $urgencia_resultado = $resultado_aviso['urgencia'] ?? 'informativo';
        $colores_resultado_urgencia = [
            'informativo' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'borde' => 'border-l-sky-500'],
            'importante'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'borde' => 'border-l-amber-500'],
            'urgente'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'borde' => 'border-l-red-500'],
        ];
        $color_resultado_urgencia = $colores_resultado_urgencia[$urgencia_resultado] ?? $colores_resultado_urgencia['informativo'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 p-5 border-l-4 <?php echo esc_attr($color_resultado_urgencia['borde']); ?>">
            <div class="flex items-center justify-between mb-3">
                <span class="<?php echo esc_attr($color_resultado_urgencia['bg']); ?> <?php echo esc_attr($color_resultado_urgencia['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                    <?php echo esc_html(ucfirst($urgencia_resultado)); ?>
                </span>
                <span class="text-xs text-gray-400"><?php echo esc_html($resultado_aviso['fecha'] ?? ''); ?></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                <a href="<?php echo esc_url($resultado_aviso['url'] ?? '#'); ?>" class="hover:text-sky-600 transition-colors">
                    <?php echo esc_html($resultado_aviso['titulo'] ?? ''); ?>
                </a>
            </h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo esc_html($resultado_aviso['resumen'] ?? ''); ?></p>
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span><?php echo esc_html($resultado_aviso['zona_afectada'] ?? ''); ?></span>
                <span><?php echo esc_html($resultado_aviso['categoria'] ?? ''); ?></span>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($total_resultados > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total_resultados / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
