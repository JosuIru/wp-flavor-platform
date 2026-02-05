<?php
/**
 * Frontend: Filtros de Fichaje Empleados
 *
 * Panel de filtros para el historial de fichajes con selector
 * de rango de fechas, tipo de registro y estado de validacion.
 *
 * @package FlavorChatIA
 * @subpackage FichajeEmpleados
 *
 * @var array $filtros_activos Filtros actualmente aplicados (fecha_desde, fecha_hasta, tipo, estado)
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-fichaje-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Rango de fechas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Rango de fechas</h4>
                <div class="space-y-3">
                    <div>
                        <label for="filtro_fecha_desde" class="block text-xs text-gray-500 mb-1">Fecha desde</label>
                        <input type="date" id="filtro_fecha_desde" name="fecha_desde"
                               value="<?php echo esc_attr($filtros_activos['fecha_desde'] ?? ''); ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="filtro_fecha_hasta" class="block text-xs text-gray-500 mb-1">Fecha hasta</label>
                        <input type="date" id="filtro_fecha_hasta" name="fecha_hasta"
                               value="<?php echo esc_attr($filtros_activos['fecha_hasta'] ?? ''); ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Tipo de registro -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de registro</h4>
                <div class="space-y-2">
                    <?php
                    $tipos_registro_fichaje = [
                        ''            => 'Todos',
                        'entrada'     => '&#9654; Entrada',
                        'salida'      => '&#9724; Salida',
                        'pausa'       => '&#9208; Pausa inicio',
                        'reanudacion' => '&#9193; Pausa fin',
                    ];
                    foreach ($tipos_registro_fichaje as $valor_tipo_fichaje => $etiqueta_tipo_fichaje):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="tipo" value="<?php echo esc_attr($valor_tipo_fichaje); ?>"
                               <?php echo ($filtros_activos['tipo'] ?? '') === $valor_tipo_fichaje ? 'checked' : ''; ?>
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                            <?php echo $etiqueta_tipo_fichaje; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Estado de validacion -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Estado</h4>
                <div class="space-y-2">
                    <?php
                    $estados_validacion_fichaje = [
                        ''          => 'Todos',
                        'validado'  => '&#9989; Validado',
                        'pendiente' => '&#9203; Pendiente',
                        'rechazado' => '&#10060; Rechazado',
                    ];
                    foreach ($estados_validacion_fichaje as $valor_estado_fichaje => $etiqueta_estado_fichaje):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="estado" value="<?php echo esc_attr($valor_estado_fichaje); ?>"
                               <?php echo ($filtros_activos['estado'] ?? '') === $valor_estado_fichaje ? 'checked' : ''; ?>
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                            <?php echo $etiqueta_estado_fichaje; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
