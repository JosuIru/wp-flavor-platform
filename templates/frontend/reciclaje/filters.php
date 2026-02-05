<?php
/**
 * Frontend: Filtros de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters reciclaje bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-emerald-600 hover:text-emerald-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo de contenedor -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de contenedor</h4>
            <div class="space-y-2">
                <?php
                $contenedores = [
                    'amarillo' => ['nombre' => 'Plasticos', 'color' => 'bg-yellow-400'],
                    'azul' => ['nombre' => 'Papel/carton', 'color' => 'bg-blue-500'],
                    'verde' => ['nombre' => 'Vidrio', 'color' => 'bg-green-600'],
                    'marron' => ['nombre' => 'Organico', 'color' => 'bg-amber-700'],
                    'gris' => ['nombre' => 'Resto', 'color' => 'bg-gray-500'],
                ];
                foreach ($contenedores as $valor => $info):
                    $checked = in_array($valor, $filtros_activos['contenedor'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="contenedor[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="w-4 h-4 rounded <?php echo $info['color']; ?>"></span>
                        <span class="text-sm text-gray-700 group-hover:text-emerald-600 transition-colors">
                            <?php echo esc_html($info['nombre']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Servicios especiales -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Servicios especiales</h4>
            <div class="space-y-2">
                <?php
                $servicios = [
                    'aceite' => 'Aceite usado',
                    'pilas' => 'Pilas y baterias',
                    'ropa' => 'Ropa y textil',
                    'electronica' => 'Electronica',
                    'muebles' => 'Muebles',
                    'escombros' => 'Escombros',
                ];
                foreach ($servicios as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['servicio'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="servicio[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 group-hover:text-emerald-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Distancia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Distancia maxima</h4>
            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-emerald-500">
                <option value="">Cualquiera</option>
                <option value="200" <?php echo ($filtros_activos['distancia'] ?? '') === '200' ? 'selected' : ''; ?>>200m</option>
                <option value="500" <?php echo ($filtros_activos['distancia'] ?? '') === '500' ? 'selected' : ''; ?>>500m</option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>>1 km</option>
            </select>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
