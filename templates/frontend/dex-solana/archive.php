<?php
/**
 * Frontend: Archive del DEX Solana
 *
 * Dashboard principal para operaciones DEX en Solana:
 * swaps de tokens, pools de liquidez y farming.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$portfolio = $portfolio ?? [];
$estadisticas = $estadisticas ?? [];
$tokens_populares = $tokens_populares ?? [];
$modo_actual = $modo_actual ?? 'paper';
?>

<div class="flavor-frontend flavor-dex-solana-archive">
    <!-- Header con gradiente cyan/teal -->
    <div class="bg-gradient-to-r from-cyan-500 to-teal-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">DEX Solana</h1>
                <p class="text-cyan-100">Swaps, pools de liquidez y farming en la red Solana</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Selector de modo: Paper Trading / Real Trading -->
                <div class="bg-white/10 backdrop-blur rounded-xl p-1 flex items-center">
                    <button class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo $modo_actual === 'paper' ? 'bg-white text-teal-700 shadow-md' : 'text-white hover:bg-white/10'; ?>"
                            data-modo="paper"
                            onclick="flavorDexSolana.cambiarModo('paper')">
                        Paper Trading
                    </button>
                    <button class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo $modo_actual === 'real' ? 'bg-white text-teal-700 shadow-md' : 'text-white hover:bg-white/10'; ?>"
                            data-modo="real"
                            onclick="flavorDexSolana.cambiarModo('real')">
                        Real Trading
                    </button>
                </div>
                <?php if ($modo_actual === 'paper'): ?>
                <span class="bg-yellow-400/20 backdrop-blur text-yellow-100 px-3 py-1 rounded-full text-xs font-medium">
                    Modo simulacion
                </span>
                <?php else: ?>
                <span class="bg-green-400/20 backdrop-blur text-green-100 px-3 py-1 rounded-full text-xs font-medium">
                    Modo real
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estadisticas del portfolio -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Valor del Portfolio</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['valor_portfolio'] ?? '$0.00'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Cambio 24h</p>
            <?php
            $cambio_24h = $estadisticas['cambio_24h'] ?? 0;
            $color_cambio = $cambio_24h >= 0 ? 'text-green-600' : 'text-red-500';
            $signo_cambio = $cambio_24h >= 0 ? '+' : '';
            ?>
            <p class="text-2xl font-bold <?php echo esc_attr($color_cambio); ?>">
                <?php echo esc_html($signo_cambio . $cambio_24h); ?>%
            </p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Total Swaps</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['total_swaps'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Pools Activos</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['pools_activos'] ?? 0); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Formulario rapido de swap -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Swap rapido</h2>
                <div class="space-y-4">
                    <!-- Token de origen -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-500">De</span>
                            <span class="text-xs text-gray-400">Balance: <?php echo esc_html($portfolio['balance_token_origen'] ?? '0.00'); ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg border border-gray-200 hover:border-cyan-400 transition-colors"
                                    onclick="flavorDexSolana.seleccionarToken('origen')">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-purple-500 to-cyan-400"></span>
                                <span class="font-semibold text-gray-800"><?php echo esc_html($portfolio['token_origen'] ?? 'SOL'); ?></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <input type="text" placeholder="0.00"
                                   class="flex-1 text-right text-2xl font-bold text-gray-800 bg-transparent border-0 focus:ring-0 focus:outline-none"
                                   id="cantidad-origen">
                        </div>
                    </div>

                    <!-- Boton intercambiar -->
                    <div class="flex justify-center -my-2 relative z-10">
                        <button class="w-10 h-10 bg-cyan-500 text-white rounded-full flex items-center justify-center hover:bg-cyan-600 transition-colors shadow-md"
                                onclick="flavorDexSolana.intercambiarTokens()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Token de destino -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-500">A</span>
                            <span class="text-xs text-gray-400">Balance: <?php echo esc_html($portfolio['balance_token_destino'] ?? '0.00'); ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg border border-gray-200 hover:border-cyan-400 transition-colors"
                                    onclick="flavorDexSolana.seleccionarToken('destino')">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-green-400 to-teal-500"></span>
                                <span class="font-semibold text-gray-800"><?php echo esc_html($portfolio['token_destino'] ?? 'USDC'); ?></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <input type="text" placeholder="0.00"
                                   class="flex-1 text-right text-2xl font-bold text-gray-800 bg-transparent border-0 focus:ring-0 focus:outline-none"
                                   id="cantidad-destino" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Tokens -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Tokens populares</h2>
                    <a href="<?php echo esc_url(home_url('/dex-solana/buscar/')); ?>" class="text-cyan-600 hover:text-cyan-700 text-sm font-medium">
                        Ver todos
                    </a>
                </div>
                <?php if (empty($tokens_populares)): ?>
                <div class="text-center py-8 text-gray-400">
                    <p class="text-sm">No hay datos de tokens disponibles</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100">
                                <th class="text-left pb-3 font-medium">Token</th>
                                <th class="text-right pb-3 font-medium">Precio</th>
                                <th class="text-right pb-3 font-medium">24h</th>
                                <th class="text-right pb-3 font-medium">Volumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($tokens_populares as $indice_token => $token_popular): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                onclick="window.location.href='<?php echo esc_url($token_popular['url'] ?? '#'); ?>'">
                                <td class="py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-gray-400 w-5"><?php echo esc_html($indice_token + 1); ?></span>
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                            <?php echo esc_html(mb_substr($token_popular['simbolo'] ?? '?', 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?php echo esc_html($token_popular['nombre'] ?? ''); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo esc_html($token_popular['simbolo'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right py-3">
                                    <span class="font-semibold text-gray-800 text-sm"><?php echo esc_html($token_popular['precio'] ?? '$0.00'); ?></span>
                                </td>
                                <td class="text-right py-3">
                                    <?php
                                    $cambio_token = $token_popular['cambio_24h'] ?? 0;
                                    $color_cambio_token = $cambio_token >= 0 ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50';
                                    $signo_cambio_token = $cambio_token >= 0 ? '+' : '';
                                    ?>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo esc_attr($color_cambio_token); ?>">
                                        <?php echo esc_html($signo_cambio_token . $cambio_token); ?>%
                                    </span>
                                </td>
                                <td class="text-right py-3">
                                    <span class="text-sm text-gray-600"><?php echo esc_html($token_popular['volumen'] ?? '$0'); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Acciones rapidas -->
            <div class="bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4">Acciones rapidas</h3>
                <div class="space-y-3">
                    <a href="<?php echo esc_url(home_url('/dex-solana/swap/')); ?>"
                       class="w-full bg-white text-teal-700 py-3 px-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors flex items-center justify-center gap-2 shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Hacer Swap
                    </a>
                    <a href="<?php echo esc_url(home_url('/dex-solana/liquidez/')); ?>"
                       class="w-full bg-white/20 backdrop-blur text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Liquidez
                    </a>
                </div>
            </div>

            <!-- Resumen portfolio -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Mi Portfolio</h3>
                <?php if (!empty($portfolio['tokens'])): ?>
                <div class="space-y-3">
                    <?php foreach ($portfolio['tokens'] as $token_cartera): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                <?php echo esc_html(mb_substr($token_cartera['simbolo'] ?? '?', 0, 2)); ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($token_cartera['simbolo'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($token_cartera['cantidad'] ?? '0'); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-800 text-sm"><?php echo esc_html($token_cartera['valor_usd'] ?? '$0.00'); ?></p>
                            <?php
                            $cambio_cartera = $token_cartera['cambio_24h'] ?? 0;
                            $color_cambio_cartera = $cambio_cartera >= 0 ? 'text-green-600' : 'text-red-500';
                            $signo_cambio_cartera = $cambio_cartera >= 0 ? '+' : '';
                            ?>
                            <p class="text-xs <?php echo esc_attr($color_cambio_cartera); ?>">
                                <?php echo esc_html($signo_cambio_cartera . $cambio_cartera); ?>%
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-6 text-gray-400">
                    <p class="text-sm">Sin tokens en el portfolio</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Estado de la red -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Red Solana</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Estado</span>
                        <span class="flex items-center gap-1 text-sm text-green-600 font-medium">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Operativa
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">TPS</span>
                        <span class="text-sm text-gray-800 font-medium"><?php echo esc_html($estadisticas['tps_red'] ?? '~4,000'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Gas fee</span>
                        <span class="text-sm text-gray-800 font-medium"><?php echo esc_html($estadisticas['gas_fee'] ?? '~0.00025 SOL'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginacion -->
    <?php if (count($tokens_populares) > 20): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1</span>
            <button class="px-4 py-2 rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
