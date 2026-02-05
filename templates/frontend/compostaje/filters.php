<?php
/**
 * Frontend: Filtros de Compostaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters compostaje bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-green-600 hover:text-green-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Estado -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Estado</h4>
            <div class="space-y-2">
                <?php
                $estados_compostera = [
                    'activa'         => 'Activa',
                    'llena'          => 'Llena',
                    'mantenimiento'  => 'Mantenimiento',
                ];
                foreach ($estados_compostera as $valor_estado => $etiqueta_estado):
                    $estado_seleccionado = in_array($valor_estado, $filtros_activos['estado'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="estado[]"
                               value="<?php echo esc_attr($valor_estado); ?>"
                               <?php echo $estado_seleccionado; ?>
                               class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta_estado); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Distancia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Distancia</h4>
            <select name="distancia"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Cualquier distancia</option>
                <option value="500" <?php echo ($filtros_activos['distancia'] ?? '') === '500' ? 'selected' : ''; ?>>Menos de 500m</option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>>Menos de 1km</option>
                <option value="2000" <?php echo ($filtros_activos['distancia'] ?? '') === '2000' ? 'selected' : ''; ?>>Menos de 2km</option>
                <option value="5000" <?php echo ($filtros_activos['distancia'] ?? '') === '5000' ? 'selected' : ''; ?>>Menos de 5km</option>
            </select>
        </div>

        <!-- Capacidad disponible -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Capacidad disponible</h4>
            <select name="capacidad"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Cualquier capacidad</option>
                <option value="alta" <?php echo ($filtros_activos['capacidad'] ?? '') === 'alta' ? 'selected' : ''; ?>>Mucho espacio (menos del 50%)</option>
                <option value="media" <?php echo ($filtros_activos['capacidad'] ?? '') === 'media' ? 'selected' : ''; ?>>Espacio medio (50-80%)</option>
                <option value="baja" <?php echo ($filtros_activos['capacidad'] ?? '') === 'baja' ? 'selected' : ''; ?>>Poco espacio (mas del 80%)</option>
            </select>
        </div>

        <!-- Acepta nuevos usuarios -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Acceso</h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="acepta_nuevos"
                       value="1"
                       <?php echo !empty($filtros_activos['acepta_nuevos']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                    Acepta nuevos usuarios
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #22c55e 0%, #059669 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
