<?php
/**
 * Frontend: Filtros de Parkings
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-parkings-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-slate-600 hover:text-slate-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Tipo de vehiculo -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de vehiculo', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $tipos_vehiculo_parking = [
                        'coche' => '🚗 Coche',
                        'moto' => '🏍️ Moto',
                        'furgoneta' => '🚐 Furgoneta',
                        'bicicleta' => '🚲 Bicicleta',
                    ];
                    foreach ($tipos_vehiculo_parking as $valor_tipo_vehiculo => $etiqueta_tipo_vehiculo):
                        $marcado_tipo_vehiculo = in_array($valor_tipo_vehiculo, $filtros_activos['tipos'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="tipos[]" value="<?php echo esc_attr($valor_tipo_vehiculo); ?>"
                               <?php echo $marcado_tipo_vehiculo; ?>
                               class="w-4 h-4 rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                        <span class="text-sm text-gray-700 group-hover:text-slate-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo_vehiculo); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Zona -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Zona', 'flavor-chat-ia'); ?></h4>
                <input type="text" name="zona" value="<?php echo esc_attr($filtros_activos['zona'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('Barrio o zona...', 'flavor-chat-ia'); ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-slate-500">
            </div>

            <!-- Horario -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Horario', 'flavor-chat-ia'); ?></h4>
                <select name="horario" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-slate-500">
                    <option value=""><?php echo esc_html__('Cualquier horario', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('manana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['horario'] ?? '') === 'manana' ? 'selected' : ''; ?>><?php echo esc_html__('Manana (8:00-14:00)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('tarde', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['horario'] ?? '') === 'tarde' ? 'selected' : ''; ?>><?php echo esc_html__('Tarde (14:00-21:00)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('noche', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['horario'] ?? '') === 'noche' ? 'selected' : ''; ?>><?php echo esc_html__('Noche (21:00-8:00)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('todo_el_dia', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['horario'] ?? '') === 'todo_el_dia' ? 'selected' : ''; ?>><?php echo esc_html__('Todo el dia', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('fines_semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['horario'] ?? '') === 'fines_semana' ? 'selected' : ''; ?>><?php echo esc_html__('Fines de semana', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Precio maximo -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio maximo (€/mes)', 'flavor-chat-ia'); ?></h4>
                <div class="relative">
                    <input type="number" name="precio_maximo" value="<?php echo esc_attr($filtros_activos['precio_maximo'] ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Ej: 80', 'flavor-chat-ia'); ?>"
                           min="0" step="5"
                           class="w-full px-3 py-2 pr-8 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-slate-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                </div>
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-slate-500">
                    <option value="<?php echo esc_attr__('cercania', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'cercania' ? 'selected' : ''; ?>><?php echo esc_html__('Mas cercanos', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('precio_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'precio_asc' ? 'selected' : ''; ?>><?php echo esc_html__('Precio mas bajo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('precio_desc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'precio_desc' ? 'selected' : ''; ?>><?php echo esc_html__('Precio mas alto', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-slate-500 to-gray-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-slate-600 hover:to-gray-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
