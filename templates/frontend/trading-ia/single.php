<?php
/**
 * Frontend: Single Trade/Regla de Trading IA
 *
 * Vista detallada de un trade individual o una regla de trading,
 * con historial de ejecucion y metricas de rendimiento.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$trade = $trade ?? [];
$regla = $regla ?? [];
$historial = $historial ?? [];
?>

<div class="flavor-frontend flavor-trading-ia-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/trading-ia/')); ?>" class="hover:text-amber-600 transition-colors">Trading IA</a>
        <span>&#8250;</span>
        <?php if (!empty($trade['id'])): ?>
        <span class="text-gray-700">Trade #<?php echo esc_html($trade['id']); ?></span>
        <?php elseif (!empty($regla['id'])): ?>
        <span class="text-gray-700">Regla: <?php echo esc_html($regla['nombre'] ?? ''); ?></span>
        <?php else: ?>
        <span class="text-gray-700">Detalle</span>
        <?php endif; ?>
    </nav>

    <?php if (!empty($trade['id'])): ?>
    <!-- ========== VISTA DE TRADE ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal del trade -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informacion del trade -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-lg font-bold shadow-md">
                            <?php echo esc_html(mb_substr($trade['token'] ?? '?', 0, 3)); ?>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">
                                Trade #<?php echo esc_html($trade['id']); ?>
                            </h1>
                            <span class="text-sm text-gray-500"><?php echo esc_html($trade['token'] ?? ''); ?></span>
                        </div>
                    </div>
                    <?php
                    $tipo_trade_detalle = $trade['tipo'] ?? 'compra';
                    $color_tipo_detalle = $tipo_trade_detalle === 'compra' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-600 border-red-200';
                    $etiqueta_tipo_detalle = $tipo_trade_detalle === 'compra' ? 'Compra' : 'Venta';
                    ?>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold border <?php echo esc_attr($color_tipo_detalle); ?>">
                        <?php echo esc_html($etiqueta_tipo_detalle); ?>
                    </span>
                </div>

                <!-- Metricas del trade -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Cantidad</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($trade['cantidad'] ?? '0'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Precio entrada</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($trade['precio_entrada'] ?? '$0.00'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Precio salida</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($trade['precio_salida'] ?? '-'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">P&L</p>
                        <?php
                        $pnl_detalle = $trade['pnl'] ?? 0;
                        $color_pnl_detalle = $pnl_detalle >= 0 ? 'text-green-600' : 'text-red-500';
                        $signo_pnl_detalle = $pnl_detalle >= 0 ? '+' : '';
                        ?>
                        <p class="text-xl font-bold <?php echo esc_attr($color_pnl_detalle); ?>">
                            <?php echo esc_html($signo_pnl_detalle . '$' . number_format(abs($pnl_detalle), 2)); ?>
                        </p>
                    </div>
                </div>

                <!-- Detalles adicionales del trade -->
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Detalles de la operacion</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Fecha apertura</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['fecha_apertura'] ?? ''); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha cierre</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['fecha_cierre'] ?? 'Abierto'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fee pagado</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['fee'] ?? '$0.00'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Origen</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['origen'] ?? 'Manual'); ?></dd>
                        </div>
                        <?php if (!empty($trade['stop_loss'])): ?>
                        <div>
                            <dt class="text-sm text-gray-500">Stop Loss</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['stop_loss']); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($trade['take_profit'])): ?>
                        <div>
                            <dt class="text-sm text-gray-500">Take Profit</dt>
                            <dd class="text-gray-800 font-medium"><?php echo esc_html($trade['take_profit']); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Notas de la IA -->
                <?php if (!empty($trade['notas_ia'])): ?>
                <div class="border-t border-gray-100 pt-6 mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Analisis IA</h2>
                    <div class="bg-amber-50 rounded-xl p-4 text-sm text-gray-700">
                        <?php echo wp_kses_post($trade['notas_ia']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar del trade -->
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4">Resultado</h3>
                <div class="text-center mb-4">
                    <?php
                    $pnl_porcentaje = $trade['pnl_porcentaje'] ?? 0;
                    $color_resultado = $pnl_porcentaje >= 0 ? 'text-green-200' : 'text-red-200';
                    $signo_resultado = $pnl_porcentaje >= 0 ? '+' : '';
                    ?>
                    <p class="text-4xl font-bold <?php echo esc_attr($color_resultado); ?>">
                        <?php echo esc_html($signo_resultado . $pnl_porcentaje); ?>%
                    </p>
                    <p class="text-amber-100 text-sm mt-1">Retorno de la operacion</p>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Inversion</span>
                        <span class="text-white font-medium"><?php echo esc_html($trade['inversion_total'] ?? '$0.00'); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Valor actual</span>
                        <span class="text-white font-medium"><?php echo esc_html($trade['valor_actual'] ?? '$0.00'); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Duracion</span>
                        <span class="text-white font-medium"><?php echo esc_html($trade['duracion'] ?? '-'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Regla asociada -->
            <?php if (!empty($trade['regla_asociada'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Regla asociada</h3>
                <a href="<?php echo esc_url($trade['regla_asociada']['url'] ?? '#'); ?>"
                   class="block p-4 rounded-xl border border-amber-200 bg-amber-50/30 hover:bg-amber-50 transition-colors">
                    <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($trade['regla_asociada']['nombre'] ?? ''); ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($trade['regla_asociada']['condicion'] ?? ''); ?></p>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif (!empty($regla['id'])): ?>
    <!-- ========== VISTA DE REGLA ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal de la regla -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-amber-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">
                                <?php echo esc_html($regla['nombre'] ?? 'Regla'); ?>
                            </h1>
                            <span class="text-sm text-gray-500">ID: <?php echo esc_html($regla['id']); ?></span>
                        </div>
                    </div>
                    <?php
                    $regla_habilitada = !empty($regla['activa']);
                    $color_estado_regla = $regla_habilitada ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200';
                    $texto_estado_regla = $regla_habilitada ? 'Activa' : 'Inactiva';
                    ?>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold border <?php echo esc_attr($color_estado_regla); ?>">
                        <?php echo esc_html($texto_estado_regla); ?>
                    </span>
                </div>

                <!-- Configuracion de la regla -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Configuracion</h2>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500">Token</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['token'] ?? ''); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Tipo de accion</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['tipo_accion'] ?? ''); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Condicion</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['condicion'] ?? ''); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Cantidad por operacion</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['cantidad_operacion'] ?? ''); ?></dd>
                            </div>
                            <?php if (!empty($regla['stop_loss'])): ?>
                            <div>
                                <dt class="text-sm text-gray-500">Stop Loss</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['stop_loss']); ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($regla['take_profit'])): ?>
                            <div>
                                <dt class="text-sm text-gray-500">Take Profit</dt>
                                <dd class="text-gray-800 font-medium"><?php echo esc_html($regla['take_profit']); ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <!-- Descripcion de la regla -->
                    <?php if (!empty($regla['descripcion'])): ?>
                    <div class="border-t border-gray-100 pt-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Descripcion</h2>
                        <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700">
                            <?php echo wp_kses_post($regla['descripcion']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de ejecuciones de la regla -->
            <?php if (!empty($historial)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Historial de ejecuciones</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100">
                                <th class="text-left pb-3 font-medium">Fecha</th>
                                <th class="text-left pb-3 font-medium">Accion</th>
                                <th class="text-right pb-3 font-medium">Cantidad</th>
                                <th class="text-right pb-3 font-medium">Precio</th>
                                <th class="text-right pb-3 font-medium">P&L</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($historial as $entrada_historial): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 text-sm text-gray-600"><?php echo esc_html($entrada_historial['fecha'] ?? ''); ?></td>
                                <td class="py-3">
                                    <?php
                                    $tipo_accion_historial = $entrada_historial['tipo'] ?? 'compra';
                                    $color_accion_historial = $tipo_accion_historial === 'compra' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600';
                                    ?>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo esc_attr($color_accion_historial); ?>">
                                        <?php echo esc_html(ucfirst($tipo_accion_historial)); ?>
                                    </span>
                                </td>
                                <td class="text-right py-3 text-sm text-gray-800"><?php echo esc_html($entrada_historial['cantidad'] ?? '0'); ?></td>
                                <td class="text-right py-3 text-sm text-gray-800"><?php echo esc_html($entrada_historial['precio'] ?? '$0.00'); ?></td>
                                <td class="text-right py-3">
                                    <?php
                                    $pnl_historial = $entrada_historial['pnl'] ?? 0;
                                    $color_pnl_historial = $pnl_historial >= 0 ? 'text-green-600' : 'text-red-500';
                                    $signo_pnl_historial = $pnl_historial >= 0 ? '+' : '';
                                    ?>
                                    <span class="text-sm font-semibold <?php echo esc_attr($color_pnl_historial); ?>">
                                        <?php echo esc_html($signo_pnl_historial . '$' . number_format(abs($pnl_historial), 2)); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar de la regla -->
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4">Rendimiento de la regla</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Ejecuciones</span>
                        <span class="text-white font-medium"><?php echo esc_html($regla['total_ejecuciones'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-amber-100">
                        <span>P&L acumulado</span>
                        <?php
                        $pnl_regla = $regla['pnl_acumulado'] ?? 0;
                        $signo_pnl_regla = $pnl_regla >= 0 ? '+' : '';
                        ?>
                        <span class="text-white font-medium"><?php echo esc_html($signo_pnl_regla . '$' . number_format(abs($pnl_regla), 2)); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Win rate</span>
                        <span class="text-white font-medium"><?php echo esc_html($regla['win_rate'] ?? '0'); ?>%</span>
                    </div>
                    <div class="flex items-center justify-between text-amber-100">
                        <span>Creada</span>
                        <span class="text-white font-medium"><?php echo esc_html($regla['fecha_creacion'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-white/20 space-y-2">
                    <button class="w-full bg-white text-orange-700 py-2 px-4 rounded-xl font-semibold hover:bg-amber-50 transition-colors text-sm"
                            onclick="flavorTradingIA.editarRegla(<?php echo esc_attr($regla['id'] ?? 0); ?>)">
                        Editar regla
                    </button>
                    <button class="w-full bg-white/20 backdrop-blur text-white py-2 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors text-sm"
                            onclick="flavorTradingIA.eliminarRegla(<?php echo esc_attr($regla['id'] ?? 0); ?>)">
                        Eliminar regla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Sin datos -->
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Elemento no encontrado</h3>
        <p class="text-gray-500 mb-6">El trade o regla que buscas no existe o ha sido eliminado</p>
        <a href="<?php echo esc_url(home_url('/trading-ia/')); ?>"
           class="inline-block bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors">
            Volver al Trading IA
        </a>
    </div>
    <?php endif; ?>
</div>
