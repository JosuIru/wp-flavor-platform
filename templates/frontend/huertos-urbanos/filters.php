<?php
/**
 * Frontend: Filtros de Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters huertos-urbanos bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-green-600 hover:text-green-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Disponibilidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="solo_disponibles"
                       value="1"
                       <?php echo !empty($filtros_activos['solo_disponibles']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                    <?php echo esc_html__('Solo con parcelas libres', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Tamano de parcela -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tamano de Parcela', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tamanos = [
                    '15-25' => '15-25 m²',
                    '26-50' => '26-50 m²',
                    '51-100' => '51-100 m²',
                    '100+' => 'Mas de 100 m²',
                ];
                foreach ($tamanos as $valor => $etiqueta):
                    $checked = ($filtros_activos['tamano'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="tamano"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Precio -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio maximo mensual', 'flavor-chat-ia'); ?></h4>
            <select name="precio_max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value=""><?php echo esc_html__('Sin limite', 'flavor-chat-ia'); ?></option>
                <option value="15" <?php echo ($filtros_activos['precio_max'] ?? '') === '15' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 15€', 'flavor-chat-ia'); ?></option>
                <option value="25" <?php echo ($filtros_activos['precio_max'] ?? '') === '25' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 25€', 'flavor-chat-ia'); ?></option>
                <option value="40" <?php echo ($filtros_activos['precio_max'] ?? '') === '40' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 40€', 'flavor-chat-ia'); ?></option>
                <option value="60" <?php echo ($filtros_activos['precio_max'] ?? '') === '60' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 60€', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Servicios -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Servicios', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $servicios = [
                    'agua' => 'Agua de riego',
                    'herramientas' => 'Herramientas',
                    'compostera' => 'Compostera comun',
                    'formacion' => 'Talleres formativos',
                    'almacen' => 'Caseta almacen',
                    'invernadero' => 'Invernadero',
                ];
                foreach ($servicios as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['servicios'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="servicios[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Distancia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Distancia maxima', 'flavor-chat-ia'); ?></h4>
            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>><?php echo esc_html__('1 km', 'flavor-chat-ia'); ?></option>
                <option value="2000" <?php echo ($filtros_activos['distancia'] ?? '') === '2000' ? 'selected' : ''; ?>><?php echo esc_html__('2 km', 'flavor-chat-ia'); ?></option>
                <option value="5000" <?php echo ($filtros_activos['distancia'] ?? '') === '5000' ? 'selected' : ''; ?>><?php echo esc_html__('5 km', 'flavor-chat-ia'); ?></option>
                <option value="10000" <?php echo ($filtros_activos['distancia'] ?? '') === '10000' ? 'selected' : ''; ?>><?php echo esc_html__('10 km', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
