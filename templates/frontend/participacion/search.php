<?php
/**
 * Frontend: Busqueda de Propuestas de Participacion
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias_busqueda = $sugerencias_busqueda ?? ['movilidad', 'parques', 'cultura', 'educacion', 'seguridad'];
?>

<div class="flavor-frontend flavor-participacion-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">Buscar propuestas</h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="Busca propuestas ciudadanas..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-amber-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-orange-600 text-white p-3 rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias populares -->
        <?php if (!empty($sugerencias_busqueda) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-amber-100 text-sm">Busquedas populares:</span>
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
            <option value="relevancia">Mas relevantes</option>
            <option value="recientes">Mas recientes</option>
            <option value="votos">Mas votadas</option>
        </select>
        <?php endif; ?>
    </div>

    <?php if (empty($resultados)): ?>
    <!-- Sin resultados -->
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">&#x1F50D;</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos propuestas</h3>
        <p class="text-gray-500 mb-6">Prueba con otros terminos o crea una nueva propuesta</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                    onclick="flavorParticipacion.nuevaPropuesta()">
                Hacer Propuesta
            </button>
            <a href="<?php echo esc_url(home_url('/participacion/')); ?>"
               class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-300 transition-colors">
                Ver todas las propuestas
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Grid de resultados -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_propuesta): ?>
        <?php
        $estado_resultado = $resultado_propuesta['estado'] ?? 'abierta';
        $colores_resultado = [
            'abierta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
            'en-debate' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'votacion'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
            'aprobada'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
            'rechazada' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
        ];
        $color_resultado = $colores_resultado[$estado_resultado] ?? $colores_resultado['abierta'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="<?php echo esc_attr($color_resultado['bg']); ?> <?php echo esc_attr($color_resultado['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $estado_resultado))); ?>
                </span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                <a href="<?php echo esc_url($resultado_propuesta['url'] ?? '#'); ?>" class="hover:text-amber-600 transition-colors">
                    <?php echo esc_html($resultado_propuesta['titulo'] ?? ''); ?>
                </a>
            </h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($resultado_propuesta['descripcion'] ?? ''); ?></p>
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span><?php echo esc_html($resultado_propuesta['autor'] ?? ''); ?></span>
                <span><?php echo esc_html($resultado_propuesta['votos_favor'] ?? 0); ?> votos a favor</span>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_resultados > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total_resultados / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
