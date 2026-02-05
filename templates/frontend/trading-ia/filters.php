<?php
/**
 * Frontend: Filtros de historial de Trading IA
 *
 * Panel de filtros para el historial de trades:
 * tipo de operacion, token, rango de fechas y resultado P&L.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-trading-ia-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-amber-600 hover:text-amber-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Tipo de operacion -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de operacion</h4>
                <div class="space-y-2">
                    <?php
                    $tipos_operacion_disponibles = [
                        'todos'  => 'Todos',
                        'compra' => 'Compra',
                        'venta'  => 'Venta',
                    ];
                    foreach ($tipos_operacion_disponibles as $valor_tipo_operacion => $etiqueta_tipo_operacion):
                        $seleccionado_operacion = ($filtros_activos['tipo_operacion'] ?? 'todos') === $valor_tipo_operacion ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="tipo_operacion" value="<?php echo esc_attr($valor_tipo_operacion); ?>"
                               <?php echo $seleccionado_operacion; ?>
                               class="w-4 h-4 border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm text-gray-700 group-hover:text-amber-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo_operacion); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Token -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Token</h4>
                <input type="text" name="token" placeholder="Ej: SOL, BONK, JUP..."
                       value="<?php echo esc_attr($filtros_activos['token'] ?? ''); ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>

            <!-- Rango de fechas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Rango de fechas</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Desde</label>
                        <input type="date" name="fecha_desde"
                               value="<?php echo esc_attr($filtros_activos['fecha_desde'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Hasta</label>
                        <input type="date" name="fecha_hasta"
                               value="<?php echo esc_attr($filtros_activos['fecha_hasta'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Resultado P&L -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Resultado P&L</h4>
                <div class="space-y-2">
                    <?php
                    $opciones_resultado_pnl = [
                        'todos'    => 'Todos',
                        'positivo' => 'Positivo (ganancia)',
                        'negativo' => 'Negativo (perdida)',
                    ];
                    foreach ($opciones_resultado_pnl as $valor_resultado => $etiqueta_resultado):
                        $seleccionado_resultado = ($filtros_activos['resultado_pnl'] ?? 'todos') === $valor_resultado ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="resultado_pnl" value="<?php echo esc_attr($valor_resultado); ?>"
                               <?php echo $seleccionado_resultado; ?>
                               class="w-4 h-4 border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm text-gray-700 group-hover:text-amber-600 transition-colors">
                            <?php echo esc_html($etiqueta_resultado); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Origen del trade -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Origen</h4>
                <select name="origen_trade"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">Todos los origenes</option>
                    <option value="manual" <?php echo ($filtros_activos['origen_trade'] ?? '') === 'manual' ? 'selected' : ''; ?>>
                        Manual
                    </option>
                    <option value="regla" <?php echo ($filtros_activos['origen_trade'] ?? '') === 'regla' ? 'selected' : ''; ?>>
                        Regla automatica
                    </option>
                    <option value="senal" <?php echo ($filtros_activos['origen_trade'] ?? '') === 'senal' ? 'selected' : ''; ?>>
                        Senal IA
                    </option>
                </select>
            </div>

            <!-- Boton aplicar filtros -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
