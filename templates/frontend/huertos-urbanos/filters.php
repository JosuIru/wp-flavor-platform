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
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-green-600 hover:text-green-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Disponibilidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Disponibilidad</h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="solo_disponibles"
                       value="1"
                       <?php echo !empty($filtros_activos['solo_disponibles']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                    Solo con parcelas libres
                </span>
            </label>
        </div>

        <!-- Tamano de parcela -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Tamano de Parcela</h4>
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
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Precio maximo mensual</h4>
            <select name="precio_max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">Sin limite</option>
                <option value="15" <?php echo ($filtros_activos['precio_max'] ?? '') === '15' ? 'selected' : ''; ?>>Hasta 15€</option>
                <option value="25" <?php echo ($filtros_activos['precio_max'] ?? '') === '25' ? 'selected' : ''; ?>>Hasta 25€</option>
                <option value="40" <?php echo ($filtros_activos['precio_max'] ?? '') === '40' ? 'selected' : ''; ?>>Hasta 40€</option>
                <option value="60" <?php echo ($filtros_activos['precio_max'] ?? '') === '60' ? 'selected' : ''; ?>>Hasta 60€</option>
            </select>
        </div>

        <!-- Servicios -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Servicios</h4>
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
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Distancia maxima</h4>
            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">Cualquiera</option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>>1 km</option>
                <option value="2000" <?php echo ($filtros_activos['distancia'] ?? '') === '2000' ? 'selected' : ''; ?>>2 km</option>
                <option value="5000" <?php echo ($filtros_activos['distancia'] ?? '') === '5000' ? 'selected' : ''; ?>>5 km</option>
                <option value="10000" <?php echo ($filtros_activos['distancia'] ?? '') === '10000' ? 'selected' : ''; ?>>10 km</option>
            </select>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
