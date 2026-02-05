<?php
/**
 * Frontend: Filtros de Participacion Ciudadana
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-participacion-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Filtrar propuestas</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-amber-600 hover:text-amber-700 font-medium"
                    onclick="flavorParticipacion.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-participacion" class="space-y-6">
            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <div class="space-y-2">
                    <?php
                    $opciones_estado = [
                        'abierta'   => 'Abierta',
                        'en-debate' => 'En debate',
                        'votacion'  => 'Votacion',
                        'aprobada'  => 'Aprobada',
                        'rechazada' => 'Rechazada',
                    ];
                    foreach ($opciones_estado as $valor_estado => $etiqueta_estado): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="estados[]" value="<?php echo esc_attr($valor_estado); ?>"
                               <?php echo in_array($valor_estado, $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_estado); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                <div class="space-y-2">
                    <?php
                    $opciones_categoria = [
                        'urbanismo'  => 'Urbanismo',
                        'movilidad'  => 'Movilidad',
                        'educacion'  => 'Educacion',
                        'cultura'    => 'Cultura',
                        'seguridad'  => 'Seguridad',
                    ];
                    foreach ($opciones_categoria as $valor_categoria => $etiqueta_categoria): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_categoria); ?>"
                               <?php echo in_array($valor_categoria, $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_categoria); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                <select name="fecha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">Cualquier fecha</option>
                    <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                    <option value="semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>>Esta semana</option>
                    <option value="mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="trimestre" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>>Ultimos 3 meses</option>
                </select>
            </div>

            <!-- Ordenar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>Mas recientes</option>
                    <option value="votadas" <?php echo ($filtros_activos['ordenar'] ?? '') === 'votadas' ? 'selected' : ''; ?>>Mas votadas</option>
                    <option value="comentadas" <?php echo ($filtros_activos['ordenar'] ?? '') === 'comentadas' ? 'selected' : ''; ?>>Mas comentadas</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
