<?php
/**
 * Frontend: Archive de Trading IA
 *
 * Dashboard principal del modulo de trading con IA:
 * estado del bot, estadisticas, trades recientes y reglas activas.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$estado_bot = $estado_bot ?? [];
$estadisticas = $estadisticas ?? [];
$trades_recientes = $trades_recientes ?? [];
$reglas_activas = $reglas_activas ?? [];
?>

<div class="flavor-frontend flavor-trading-ia-archive">
    <!-- Header con gradiente amber/orange -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Trading IA</h1>
                <p class="text-amber-100">Bots inteligentes, reglas automatizadas y senales de trading</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html(count($reglas_activas)); ?> reglas activas
                </span>
            </div>
        </div>
    </div>

    <!-- Tarjeta de estado del bot -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <?php
                $bot_activo = !empty($estado_bot['activo']);
                $color_estado_bot = $bot_activo ? 'bg-green-500' : 'bg-gray-400';
                $texto_estado_bot = $bot_activo ? 'Activo' : 'Inactivo';
                ?>
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-md">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Bot de Trading</h2>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?php echo esc_attr($color_estado_bot); ?> <?php echo $bot_activo ? 'animate-pulse' : ''; ?>"></span>
                        <span class="text-sm text-gray-600"><?php echo esc_html($texto_estado_bot); ?></span>
                        <?php if (!empty($estado_bot['ultimo_trade'])): ?>
                        <span class="text-xs text-gray-400">| Ultimo trade: <?php echo esc_html($estado_bot['ultimo_trade']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Toggle de activacion -->
                <button class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors <?php echo $bot_activo ? 'bg-amber-500' : 'bg-gray-300'; ?>"
                        onclick="flavorTradingIA.toggleBot()">
                    <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform shadow-md <?php echo $bot_activo ? 'translate-x-7' : 'translate-x-1'; ?>"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">P&L Total</p>
            <?php
            $pnl_total = $estadisticas['pnl_total'] ?? 0;
            $color_pnl = $pnl_total >= 0 ? 'text-green-600' : 'text-red-500';
            $signo_pnl = $pnl_total >= 0 ? '+' : '';
            ?>
            <p class="text-2xl font-bold <?php echo esc_attr($color_pnl); ?>">
                <?php echo esc_html($signo_pnl . '$' . number_format(abs($pnl_total), 2)); ?>
            </p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Win Rate</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['win_rate'] ?? '0'); ?>%</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Total Trades</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['total_trades'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-1">Reglas Activas</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['reglas_activas'] ?? 0); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Trades recientes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Trades recientes</h2>
                    <a href="<?php echo esc_url(home_url('/trading-ia/buscar/')); ?>" class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                        Ver todos
                    </a>
                </div>
                <?php if (empty($trades_recientes)): ?>
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm">No hay trades registrados todavia</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100">
                                <th class="text-left pb-3 font-medium">Token</th>
                                <th class="text-left pb-3 font-medium">Tipo</th>
                                <th class="text-right pb-3 font-medium">Cantidad</th>
                                <th class="text-right pb-3 font-medium">P&L</th>
                                <th class="text-right pb-3 font-medium">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($trades_recientes as $trade_reciente): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                onclick="window.location.href='<?php echo esc_url($trade_reciente['url'] ?? '#'); ?>'">
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                            <?php echo esc_html(mb_substr($trade_reciente['token'] ?? '?', 0, 2)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo esc_html($trade_reciente['token'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <?php
                                    $tipo_trade = $trade_reciente['tipo'] ?? 'compra';
                                    $color_tipo_trade = $tipo_trade === 'compra' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600';
                                    $etiqueta_tipo_trade = $tipo_trade === 'compra' ? 'Compra' : 'Venta';
                                    ?>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo esc_attr($color_tipo_trade); ?>">
                                        <?php echo esc_html($etiqueta_tipo_trade); ?>
                                    </span>
                                </td>
                                <td class="text-right py-3 text-sm text-gray-800 font-medium">
                                    <?php echo esc_html($trade_reciente['cantidad'] ?? '0'); ?>
                                </td>
                                <td class="text-right py-3">
                                    <?php
                                    $pnl_trade = $trade_reciente['pnl'] ?? 0;
                                    $color_pnl_trade = $pnl_trade >= 0 ? 'text-green-600' : 'text-red-500';
                                    $signo_pnl_trade = $pnl_trade >= 0 ? '+' : '';
                                    ?>
                                    <span class="text-sm font-semibold <?php echo esc_attr($color_pnl_trade); ?>">
                                        <?php echo esc_html($signo_pnl_trade . '$' . number_format(abs($pnl_trade), 2)); ?>
                                    </span>
                                </td>
                                <td class="text-right py-3 text-xs text-gray-500">
                                    <?php echo esc_html($trade_reciente['fecha'] ?? ''); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Reglas activas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Reglas activas</h2>
                    <a href="<?php echo esc_url(home_url('/trading-ia/regla/')); ?>"
                       class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                        Crear nueva
                    </a>
                </div>
                <?php if (empty($reglas_activas)): ?>
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm mb-3">No hay reglas configuradas</p>
                    <a href="<?php echo esc_url(home_url('/trading-ia/regla/')); ?>"
                       class="inline-block bg-amber-500 text-white px-5 py-2 rounded-xl font-semibold hover:bg-amber-600 transition-colors text-sm">
                        Crear primera regla
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($reglas_activas as $regla_activa): ?>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                <?php
                                $tipo_regla = $regla_activa['tipo'] ?? 'compra';
                                $icono_regla_color = $tipo_regla === 'compra' ? 'text-green-600' : 'text-red-500';
                                ?>
                                <svg class="w-5 h-5 <?php echo esc_attr($icono_regla_color); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php if ($tipo_regla === 'compra'): ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    <?php else: ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($regla_activa['nombre'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo esc_html($regla_activa['token'] ?? ''); ?> |
                                    <?php echo esc_html($regla_activa['condicion'] ?? ''); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-400"><?php echo esc_html($regla_activa['ejecuciones'] ?? 0); ?> ejecuciones</span>
                            <a href="<?php echo esc_url($regla_activa['url'] ?? '#'); ?>"
                               class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                                Editar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Acciones rapidas -->
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4">Acciones rapidas</h3>
                <div class="space-y-3">
                    <a href="<?php echo esc_url(home_url('/trading-ia/comprar/')); ?>"
                       class="w-full bg-white text-orange-700 py-3 px-4 rounded-xl font-semibold hover:bg-amber-50 transition-colors flex items-center justify-center gap-2 shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Compra Manual
                    </a>
                    <a href="<?php echo esc_url(home_url('/trading-ia/vender/')); ?>"
                       class="w-full bg-white/20 backdrop-blur text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        Venta Manual
                    </a>
                    <a href="<?php echo esc_url(home_url('/trading-ia/regla/')); ?>"
                       class="w-full bg-white/20 backdrop-blur text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Crear Regla
                    </a>
                </div>
            </div>

            <!-- Resumen de rendimiento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Rendimiento</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">Win Rate</span>
                            <span class="text-sm font-semibold text-gray-800"><?php echo esc_html($estadisticas['win_rate'] ?? '0'); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-2 rounded-full"
                                 style="width: <?php echo esc_attr(min(100, max(0, $estadisticas['win_rate'] ?? 0))); ?>%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Trades ganados</span>
                        <span class="text-sm font-medium text-green-600"><?php echo esc_html($estadisticas['trades_ganados'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Trades perdidos</span>
                        <span class="text-sm font-medium text-red-500"><?php echo esc_html($estadisticas['trades_perdidos'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Mejor trade</span>
                        <span class="text-sm font-medium text-green-600"><?php echo esc_html($estadisticas['mejor_trade'] ?? '$0'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Peor trade</span>
                        <span class="text-sm font-medium text-red-500"><?php echo esc_html($estadisticas['peor_trade'] ?? '$0'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Senales recientes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Senales IA</h3>
                <?php if (!empty($estadisticas['senales_recientes'])): ?>
                <div class="space-y-3">
                    <?php foreach ($estadisticas['senales_recientes'] as $senal_ia): ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                        <?php
                        $tipo_senal = $senal_ia['tipo'] ?? 'neutral';
                        $color_senal = $tipo_senal === 'compra' ? 'bg-green-500' : ($tipo_senal === 'venta' ? 'bg-red-500' : 'bg-gray-400');
                        ?>
                        <span class="w-2.5 h-2.5 rounded-full <?php echo esc_attr($color_senal); ?>"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate"><?php echo esc_html($senal_ia['mensaje'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($senal_ia['fecha'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-gray-400">
                    <p class="text-sm">Sin senales recientes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
