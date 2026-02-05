<?php
/**
 * Frontend: Filtros de Tramites
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-tramites-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Filtrar tramites</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-orange-600 hover:text-orange-700 font-medium"
                    onclick="flavorTramites.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-tramites" class="space-y-6">
            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                <div class="space-y-2">
                    <?php
                    $opciones_categoria_tramite = [
                        'empadronamiento' => 'Empadronamiento',
                        'licencias'       => 'Licencias',
                        'impuestos'       => 'Impuestos',
                        'certificados'    => 'Certificados',
                    ];
                    foreach ($opciones_categoria_tramite as $valor_cat => $etiqueta_cat): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat); ?>"
                               <?php echo in_array($valor_cat, $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_cat); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Modalidad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad</label>
                <div class="space-y-2">
                    <?php
                    $opciones_modalidad = [
                        'online'     => 'Online',
                        'presencial' => 'Presencial',
                        'ambos'      => 'Ambos',
                    ];
                    foreach ($opciones_modalidad as $valor_modalidad => $etiqueta_modalidad): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="modalidad[]" value="<?php echo esc_attr($valor_modalidad); ?>"
                               <?php echo in_array($valor_modalidad, $filtros_activos['modalidad'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_modalidad); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tiempo estimado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo estimado</label>
                <select name="tiempo_estimado" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Cualquier duracion</option>
                    <option value="inmediato" <?php echo ($filtros_activos['tiempo_estimado'] ?? '') === 'inmediato' ? 'selected' : ''; ?>>Inmediato</option>
                    <option value="1-dia" <?php echo ($filtros_activos['tiempo_estimado'] ?? '') === '1-dia' ? 'selected' : ''; ?>>1 dia</option>
                    <option value="1-semana" <?php echo ($filtros_activos['tiempo_estimado'] ?? '') === '1-semana' ? 'selected' : ''; ?>>Hasta 1 semana</option>
                    <option value="1-mes" <?php echo ($filtros_activos['tiempo_estimado'] ?? '') === '1-mes' ? 'selected' : ''; ?>>Hasta 1 mes</option>
                </select>
            </div>

            <!-- Requiere cita previa -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cita previa</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="requiere_cita" value="1"
                               <?php echo !empty($filtros_activos['requiere_cita']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                        <span class="text-gray-700">Requiere cita previa</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="sin_cita" value="1"
                               <?php echo !empty($filtros_activos['sin_cita']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                        <span class="text-gray-700">Sin cita previa</span>
                    </label>
                </div>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-orange-500 to-amber-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-orange-600 hover:to-amber-600 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
