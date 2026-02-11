<?php
/**
 * Frontend: Busqueda de Trades y Senales de Trading IA
 *
 * Buscador de operaciones de trading con resultados
 * en formato de tarjetas de trades.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['SOL', 'BONK', 'compra', 'stop-loss', 'regla'];
?>

<div class="flavor-frontend flavor-trading-ia-search">
    <!-- Buscador con gradiente amber -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('Buscar trades y senales', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por token, tipo de operacion, regla...', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-amber-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-orange-600 text-white p-3 rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias de busqueda -->
        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-amber-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_busqueda): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_busqueda); ?>"
               class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_busqueda); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Resultados de busqueda -->
    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-amber-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-amber-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="mb-4">
            <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontraron trades', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Intenta con otro termino de busqueda', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/trading-ia/')); ?>"
           class="inline-block bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors">
            <?php echo esc_html__('Volver al Trading IA', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $trade_resultado): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera del trade -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold shadow-md">
                            <?php echo esc_html(mb_substr($trade_resultado['token'] ?? '?', 0, 3)); ?>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-amber-600 transition-colors">
                                <a href="<?php echo esc_url($trade_resultado['url'] ?? '#'); ?>">
                                    <?php echo esc_html($trade_resultado['token'] ?? ''); ?>
                                </a>
                            </h3>
                            <p class="text-xs text-gray-500">
                                <?php if (!empty($trade_resultado['id'])): ?>
                                Trade #<?php echo esc_html($trade_resultado['id']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php
                    $tipo_resultado_trade = $trade_resultado['tipo'] ?? 'compra';
                    $color_tipo_resultado = $tipo_resultado_trade === 'compra' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600';
                    $etiqueta_tipo_resultado = $tipo_resultado_trade === 'compra' ? 'Compra' : 'Venta';
                    ?>
                    <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo esc_attr($color_tipo_resultado); ?>">
                        <?php echo esc_html($etiqueta_tipo_resultado); ?>
                    </span>
                </div>

                <!-- Metricas del trade -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($trade_resultado['cantidad'] ?? '0'); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500"><?php echo esc_html__('P&L', 'flavor-chat-ia'); ?></p>
                        <?php
                        $pnl_resultado_trade = $trade_resultado['pnl'] ?? 0;
                        $color_pnl_resultado = $pnl_resultado_trade >= 0 ? 'text-green-600' : 'text-red-500';
                        $signo_pnl_resultado = $pnl_resultado_trade >= 0 ? '+' : '';
                        ?>
                        <p class="text-sm font-bold <?php echo esc_attr($color_pnl_resultado); ?>">
                            <?php echo esc_html($signo_pnl_resultado . '$' . number_format(abs($pnl_resultado_trade), 2)); ?>
                        </p>
                    </div>
                </div>

                <!-- Informacion adicional -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <?php
                        $origen_resultado = $trade_resultado['origen'] ?? 'manual';
                        $color_origen = $origen_resultado === 'regla' ? 'bg-amber-50 text-amber-700' : ($origen_resultado === 'senal' ? 'bg-purple-50 text-purple-700' : 'bg-gray-50 text-gray-600');
                        ?>
                        <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo esc_attr($color_origen); ?>">
                            <?php echo esc_html(ucfirst($origen_resultado)); ?>
                        </span>
                        <span class="text-xs text-gray-400"><?php echo esc_html($trade_resultado['fecha'] ?? ''); ?></span>
                    </div>
                    <a href="<?php echo esc_url($trade_resultado['url'] ?? '#'); ?>"
                       class="text-amber-600 hover:text-amber-700 font-medium text-sm">
                        <?php echo esc_html__('Ver detalle', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginacion -->
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
