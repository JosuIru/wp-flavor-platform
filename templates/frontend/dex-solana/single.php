<?php
/**
 * Frontend: Single Token/Pool del DEX Solana
 *
 * Vista detallada de un token o pool de liquidez
 * con precio, volumen, chart y formulario de swap integrado.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$token = $token ?? [];
$historial_precios = $historial_precios ?? [];
$pools_disponibles = $pools_disponibles ?? [];
?>

<div class="flavor-frontend flavor-dex-solana-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/dex-solana/')); ?>" class="hover:text-cyan-600 transition-colors"><?php echo esc_html__('DEX', 'flavor-chat-ia'); ?></a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($token['nombre'] ?? 'Token'); ?> (<?php echo esc_html($token['simbolo'] ?? ''); ?>)</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informacion del token -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white text-lg font-bold shadow-md">
                            <?php echo esc_html(mb_substr($token['simbolo'] ?? '?', 0, 3)); ?>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">
                                <?php echo esc_html($token['nombre'] ?? ''); ?>
                            </h1>
                            <span class="text-sm text-gray-500"><?php echo esc_html($token['simbolo'] ?? ''); ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if (!empty($token['red'])): ?>
                        <span class="bg-teal-50 text-teal-700 text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo esc_html($token['red'] ?? 'Solana'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Metricas de precio -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($token['precio'] ?? '$0.00'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Cambio 24h', 'flavor-chat-ia'); ?></p>
                        <?php
                        $cambio_precio_24h = $token['cambio_24h'] ?? 0;
                        $color_variacion_precio = $cambio_precio_24h >= 0 ? 'text-green-600' : 'text-red-500';
                        $signo_variacion_precio = $cambio_precio_24h >= 0 ? '+' : '';
                        ?>
                        <p class="text-xl font-bold <?php echo esc_attr($color_variacion_precio); ?>">
                            <?php echo esc_html($signo_variacion_precio . $cambio_precio_24h); ?>%
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Volumen 24h', 'flavor-chat-ia'); ?></p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($token['volumen_24h'] ?? '$0'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Market Cap', 'flavor-chat-ia'); ?></p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($token['market_cap'] ?? '$0'); ?></p>
                    </div>
                </div>

                <!-- Metricas adicionales -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-3 border border-gray-100 rounded-xl">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Holders', 'flavor-chat-ia'); ?></p>
                        <p class="font-semibold text-gray-800"><?php echo esc_html($token['holders'] ?? '0'); ?></p>
                    </div>
                    <div class="text-center p-3 border border-gray-100 rounded-xl">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Supply circulante', 'flavor-chat-ia'); ?></p>
                        <p class="font-semibold text-gray-800"><?php echo esc_html($token['supply_circulante'] ?? '0'); ?></p>
                    </div>
                    <div class="text-center p-3 border border-gray-100 rounded-xl">
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Liquidez total', 'flavor-chat-ia'); ?></p>
                        <p class="font-semibold text-gray-800"><?php echo esc_html($token['liquidez_total'] ?? '$0'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Grafico de precio (placeholder) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800"><?php echo esc_html__('Grafico de precio', 'flavor-chat-ia'); ?></h2>
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                        <?php
                        $intervalos_tiempo = ['1H', '4H', '1D', '1S', '1M'];
                        foreach ($intervalos_tiempo as $intervalo):
                        ?>
                        <button class="px-3 py-1 rounded-md text-xs font-medium transition-colors <?php echo $intervalo === '1D' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>"
                                onclick="flavorDexSolana.cambiarIntervalo('<?php echo esc_attr($intervalo); ?>')">
                            <?php echo esc_html($intervalo); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl h-72 flex items-center justify-center text-gray-400" id="contenedor-grafico-precio">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        <p class="text-sm"><?php echo esc_html__('Grafico de precio', 'flavor-chat-ia'); ?></p>
                        <?php if (!empty($historial_precios)): ?>
                        <p class="text-xs text-gray-400 mt-1"><?php echo esc_html(count($historial_precios)); ?> puntos de datos</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pools disponibles -->
            <?php if (!empty($pools_disponibles)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4"><?php echo esc_html__('Pools de liquidez', 'flavor-chat-ia'); ?></h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100">
                                <th class="text-left pb-3 font-medium"><?php echo esc_html__('Pool', 'flavor-chat-ia'); ?></th>
                                <th class="text-right pb-3 font-medium"><?php echo esc_html__('TVL', 'flavor-chat-ia'); ?></th>
                                <th class="text-right pb-3 font-medium"><?php echo esc_html__('APR', 'flavor-chat-ia'); ?></th>
                                <th class="text-right pb-3 font-medium"><?php echo esc_html__('Volumen 24h', 'flavor-chat-ia'); ?></th>
                                <th class="text-right pb-3 font-medium"><?php echo esc_html__('Accion', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($pools_disponibles as $pool_item): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex -space-x-2">
                                            <span class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">
                                                <?php echo esc_html(mb_substr($pool_item['token_a'] ?? '?', 0, 1)); ?>
                                            </span>
                                            <span class="w-7 h-7 rounded-full bg-gradient-to-br from-teal-400 to-green-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">
                                                <?php echo esc_html(mb_substr($pool_item['token_b'] ?? '?', 0, 1)); ?>
                                            </span>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm">
                                            <?php echo esc_html(($pool_item['token_a'] ?? '') . '/' . ($pool_item['token_b'] ?? '')); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right py-3 text-sm text-gray-800 font-medium">
                                    <?php echo esc_html($pool_item['tvl'] ?? '$0'); ?>
                                </td>
                                <td class="text-right py-3">
                                    <span class="text-sm font-semibold text-green-600"><?php echo esc_html($pool_item['apr'] ?? '0'); ?>%</span>
                                </td>
                                <td class="text-right py-3 text-sm text-gray-600">
                                    <?php echo esc_html($pool_item['volumen_24h'] ?? '$0'); ?>
                                </td>
                                <td class="text-right py-3">
                                    <a href="<?php echo esc_url($pool_item['url'] ?? '#'); ?>"
                                       class="text-cyan-600 hover:text-cyan-700 text-sm font-medium">
                                        <?php echo esc_html__('Agregar', 'flavor-chat-ia'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: Formulario de swap -->
        <div class="space-y-6">
            <!-- Swap form -->
            <div class="bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4">Swap <?php echo esc_html($token['simbolo'] ?? ''); ?></h3>
                <div class="space-y-4">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-cyan-100"><?php echo esc_html__('De', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold"><?php echo esc_html($token['simbolo'] ?? 'SOL'); ?></span>
                            <input type="text" placeholder="0.00"
                                   class="text-right text-xl font-bold bg-transparent border-0 text-white placeholder-cyan-200 focus:ring-0 focus:outline-none w-24"
                                   id="swap-cantidad-entrada">
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-cyan-100"><?php echo esc_html__('A', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold"><?php echo esc_html__('USDC', 'flavor-chat-ia'); ?></span>
                            <span class="text-xl font-bold" id="swap-cantidad-salida">0.00</span>
                        </div>
                    </div>
                    <button class="w-full bg-white text-teal-700 py-3 px-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors shadow-md"
                            onclick="flavorDexSolana.ejecutarSwap()">
                        <?php echo esc_html__('Ejecutar Swap', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <div class="mt-4 space-y-2 text-xs text-cyan-100">
                    <div class="flex items-center justify-between">
                        <span><?php echo esc_html__('Slippage', 'flavor-chat-ia'); ?></span>
                        <span><?php echo esc_html($token['slippage'] ?? '0.5'); ?>%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span><?php echo esc_html__('Fee estimado', 'flavor-chat-ia'); ?></span>
                        <span><?php echo esc_html($token['fee_estimado'] ?? '~0.00025 SOL'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Informacion del contrato -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Informacion del contrato', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Direccion del token', 'flavor-chat-ia'); ?></p>
                        <p class="text-sm text-gray-800 font-mono bg-gray-50 px-3 py-2 rounded-lg break-all">
                            <?php echo esc_html($token['direccion_contrato'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500"><?php echo esc_html__('Decimales', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm text-gray-800 font-medium"><?php echo esc_html($token['decimales'] ?? '9'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500"><?php echo esc_html__('Programa', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm text-gray-800 font-medium"><?php echo esc_html($token['programa'] ?? 'Token Program'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Enlaces utiles -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Enlaces', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php if (!empty($token['url_explorador'])): ?>
                    <a href="<?php echo esc_url($token['url_explorador']); ?>" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2 text-sm text-cyan-600 hover:text-cyan-700 transition-colors p-2 rounded-lg hover:bg-cyan-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <?php echo esc_html__('Solscan Explorer', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($token['url_web'])): ?>
                    <a href="<?php echo esc_url($token['url_web']); ?>" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2 text-sm text-cyan-600 hover:text-cyan-700 transition-colors p-2 rounded-lg hover:bg-cyan-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                        </svg>
                        <?php echo esc_html__('Sitio web oficial', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
