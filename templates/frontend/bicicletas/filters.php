<?php
/**
 * Frontend: Filtros de Bicicletas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters bicicletas bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-lime-600 hover:text-lime-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Disponibilidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Disponibilidad</h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="con_bicis"
                       value="1"
                       <?php echo !empty($filtros_activos['con_bicis']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                <span class="text-sm text-gray-700 group-hover:text-lime-600 transition-colors">
                    Solo con bicis disponibles
                </span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer group mt-2">
                <input type="checkbox"
                       name="con_huecos"
                       value="1"
                       <?php echo !empty($filtros_activos['con_huecos']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                <span class="text-sm text-gray-700 group-hover:text-lime-600 transition-colors">
                    Solo con huecos libres
                </span>
            </label>
        </div>

        <!-- Tipo de bicicleta -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de bicicleta</h4>
            <div class="space-y-2">
                <?php
                $tipos = [
                    'mecanica' => 'Mecanica',
                    'electrica' => 'Electrica',
                ];
                foreach ($tipos as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                        <span class="text-sm text-gray-700 group-hover:text-lime-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Distancia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Distancia maxima</h4>
            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-lime-500">
                <option value="">Cualquiera</option>
                <option value="200" <?php echo ($filtros_activos['distancia'] ?? '') === '200' ? 'selected' : ''; ?>>200m</option>
                <option value="500" <?php echo ($filtros_activos['distancia'] ?? '') === '500' ? 'selected' : ''; ?>>500m</option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>>1 km</option>
                <option value="2000" <?php echo ($filtros_activos['distancia'] ?? '') === '2000' ? 'selected' : ''; ?>>2 km</option>
            </select>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
