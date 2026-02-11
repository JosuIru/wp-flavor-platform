<?php
/**
 * Frontend: Busqueda de Tokens en el DEX Solana
 *
 * Buscador de tokens con sugerencias populares
 * y resultados en formato grid de tarjetas.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['SOL', 'USDC', 'RAY', 'JUP', 'BONK'];
?>

<div class="flavor-frontend flavor-dex-solana-search">
    <!-- Buscador con gradiente cyan -->
    <div class="bg-gradient-to-r from-cyan-500 to-teal-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('Buscar tokens', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por nombre, simbolo o direccion del token...', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-cyan-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-teal-600 text-white p-3 rounded-lg hover:bg-teal-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Sugerencias de tokens populares -->
        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-cyan-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_token): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_token); ?>"
               class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_token); ?>
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
                <?php echo esc_html($total_resultados); ?> token<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-cyan-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-cyan-600"><?php echo esc_html($query); ?></span>"
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
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontraron tokens', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Intenta con otro nombre, simbolo o direccion de contrato', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/dex-solana/')); ?>"
           class="inline-block bg-cyan-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-cyan-600 transition-colors">
            <?php echo esc_html__('Volver al DEX', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $token_resultado): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera del token -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white font-bold shadow-md">
                        <?php echo esc_html(mb_substr($token_resultado['simbolo'] ?? '?', 0, 3)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 truncate group-hover:text-cyan-600 transition-colors">
                            <a href="<?php echo esc_url($token_resultado['url'] ?? '#'); ?>">
                                <?php echo esc_html($token_resultado['nombre'] ?? ''); ?>
                            </a>
                        </h3>
                        <p class="text-sm text-gray-500"><?php echo esc_html($token_resultado['simbolo'] ?? ''); ?></p>
                    </div>
                    <?php if (!empty($token_resultado['tipo'])): ?>
                    <span class="bg-cyan-50 text-cyan-700 text-xs font-medium px-2 py-1 rounded-full">
                        <?php echo esc_html($token_resultado['tipo']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Precio y cambio -->
                <div class="flex items-end justify-between mb-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($token_resultado['precio'] ?? '$0.00'); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('24h', 'flavor-chat-ia'); ?></p>
                        <?php
                        $cambio_resultado = $token_resultado['cambio_24h'] ?? 0;
                        $color_cambio_resultado = $cambio_resultado >= 0 ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50';
                        $signo_cambio_resultado = $cambio_resultado >= 0 ? '+' : '';
                        ?>
                        <span class="text-sm font-semibold px-2 py-1 rounded-full <?php echo esc_attr($color_cambio_resultado); ?>">
                            <?php echo esc_html($signo_cambio_resultado . $cambio_resultado); ?>%
                        </span>
                    </div>
                </div>

                <!-- Metricas del token -->
                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Volumen 24h', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($token_resultado['volumen_24h'] ?? '$0'); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Liquidez', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($token_resultado['liquidez'] ?? '$0'); ?></p>
                    </div>
                </div>

                <!-- Boton de accion -->
                <div class="mt-4">
                    <a href="<?php echo esc_url($token_resultado['url'] ?? '#'); ?>"
                       class="w-full block text-center bg-cyan-50 text-cyan-700 py-2 px-4 rounded-xl font-medium hover:bg-cyan-100 transition-colors text-sm">
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
            <button class="px-4 py-2 rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
