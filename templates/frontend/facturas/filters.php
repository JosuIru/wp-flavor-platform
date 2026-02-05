<?php
/**
 * Frontend: Filtros de Facturas
 *
 * Panel lateral de filtros para el listado de facturas.
 * Incluye filtros por estado, rango de fechas, rango de importe y cliente.
 *
 * Variables esperadas:
 * @var array $filtros_activos  Filtros actualmente aplicados
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-facturas-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Estado -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Estado</h4>
                <div class="space-y-2">
                    <?php
                    $estados_factura = [
                        ''           => 'Todas',
                        'pendiente'  => 'Pendientes',
                        'pagada'     => 'Pagadas',
                        'vencida'    => 'Vencidas',
                        'borrador'   => 'Borrador',
                    ];
                    foreach ($estados_factura as $valor_estado_factura => $etiqueta_estado_factura):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="estado" value="<?php echo esc_attr($valor_estado_factura); ?>"
                               <?php echo ($filtros_activos['estado'] ?? '') === $valor_estado_factura ? 'checked' : ''; ?>
                               class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 group-hover:text-emerald-600 transition-colors">
                            <?php echo esc_html($etiqueta_estado_factura); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de fechas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Rango de fechas</h4>
                <div class="space-y-3">
                    <div>
                        <label for="filtro_fecha_desde" class="block text-xs text-gray-500 mb-1">Fecha desde</label>
                        <input type="date" id="filtro_fecha_desde" name="fecha_desde"
                               value="<?php echo esc_attr($filtros_activos['fecha_desde'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label for="filtro_fecha_hasta" class="block text-xs text-gray-500 mb-1">Fecha hasta</label>
                        <input type="date" id="filtro_fecha_hasta" name="fecha_hasta"
                               value="<?php echo esc_attr($filtros_activos['fecha_hasta'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Rango de importe -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Rango de importe</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="filtro_importe_minimo" class="block text-xs text-gray-500 mb-1">Minimo (&euro;)</label>
                        <input type="number" id="filtro_importe_minimo" name="importe_minimo" min="0" step="0.01"
                               value="<?php echo esc_attr($filtros_activos['importe_minimo'] ?? ''); ?>"
                               placeholder="0"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label for="filtro_importe_maximo" class="block text-xs text-gray-500 mb-1">Maximo (&euro;)</label>
                        <input type="number" id="filtro_importe_maximo" name="importe_maximo" min="0" step="0.01"
                               value="<?php echo esc_attr($filtros_activos['importe_maximo'] ?? ''); ?>"
                               placeholder="Sin limite"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Cliente -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Cliente</h4>
                <input type="text" name="cliente"
                       value="<?php echo esc_attr($filtros_activos['cliente'] ?? ''); ?>"
                       placeholder="Nombre o razon social..."
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-emerald-500 to-green-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-emerald-600 hover:to-green-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
