<?php
/**
 * Frontend: Busqueda de Documentos de Transparencia
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias_busqueda = $sugerencias_busqueda ?? ['presupuesto 2026', 'contratos', 'actas pleno', 'subvenciones'];
?>

<div class="flavor-frontend flavor-transparencia-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-teal-500 to-cyan-500 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">Buscar documentos</h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="Busca documentos publicos, actas, presupuestos..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-teal-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-teal-600 text-white p-3 rounded-lg hover:bg-teal-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias populares -->
        <?php if (!empty($sugerencias_busqueda) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-teal-100 text-sm">Sugerencias:</span>
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
        <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
            <option value="relevancia">Mas relevantes</option>
            <option value="recientes">Mas recientes</option>
            <option value="antiguos">Mas antiguos</option>
        </select>
        <?php endif; ?>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">&#x1F4C4;</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos documentos</h3>
        <p class="text-gray-500 mb-6">Prueba con otros terminos o navega por las categorias</p>
        <a href="<?php echo esc_url(home_url('/transparencia/')); ?>"
           class="bg-teal-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-teal-600 transition-colors inline-block">
            Ver todos los documentos
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_documento): ?>
        <?php
        $formato_resultado = strtolower($resultado_documento['formato'] ?? 'pdf');
        $colores_formato_resultado = [
            'pdf' => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
            'xls' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'xlsx' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'csv' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
        ];
        $color_formato_resultado = $colores_formato_resultado[$formato_resultado] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 p-5">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 <?php echo esc_attr($color_formato_resultado['bg']); ?> rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="<?php echo esc_attr($color_formato_resultado['text']); ?> font-bold text-xs"><?php echo esc_html(strtoupper($formato_resultado)); ?></span>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800">
                        <a href="<?php echo esc_url($resultado_documento['url'] ?? '#'); ?>" class="hover:text-teal-600 transition-colors">
                            <?php echo esc_html($resultado_documento['titulo'] ?? ''); ?>
                        </a>
                    </h3>
                    <span class="text-xs text-gray-500"><?php echo esc_html($resultado_documento['categoria'] ?? ''); ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm text-gray-500 mt-3">
                <span><?php echo esc_html($resultado_documento['fecha'] ?? ''); ?></span>
                <span><?php echo esc_html($resultado_documento['tamano'] ?? ''); ?></span>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($total_resultados > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total_resultados / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-teal-500 text-white hover:bg-teal-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
