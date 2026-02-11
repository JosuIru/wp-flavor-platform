<?php
/**
 * Frontend: Filtros de Presupuestos Participativos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
$distritos = $distritos ?? [];
?>

<div class="flavor-frontend flavor-presupuestos-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('Filtrar proyectos', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-amber-600 hover:text-amber-700 font-medium"
                    onclick="flavorPresupuestos.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-presupuestos" class="space-y-6">
            <!-- Fase -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Fase', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <?php
                    $opciones_fase = [
                        'propuestas' => 'Propuestas',
                        'evaluacion' => 'Evaluacion',
                        'votacion'   => 'Votacion',
                        'ejecucion'  => 'Ejecucion',
                        'completado' => 'Completado',
                    ];
                    foreach ($opciones_fase as $valor_fase => $etiqueta_fase): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="fases[]" value="<?php echo esc_attr($valor_fase); ?>"
                               <?php echo in_array($valor_fase, $filtros_activos['fases'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_fase); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Presupuesto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Presupuesto', 'flavor-chat-ia'); ?></label>
                <select name="presupuesto" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value=""><?php echo esc_html__('Cualquier importe', 'flavor-chat-ia'); ?></option>
                    <option value="0-10000" <?php echo ($filtros_activos['presupuesto'] ?? '') === '0-10000' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 10.000', 'flavor-chat-ia'); ?></option>
                    <option value="10000-50000" <?php echo ($filtros_activos['presupuesto'] ?? '') === '10000-50000' ? 'selected' : ''; ?>>10.000 - 50.000</option>
                    <option value="50000-100000" <?php echo ($filtros_activos['presupuesto'] ?? '') === '50000-100000' ? 'selected' : ''; ?>>50.000 - 100.000</option>
                    <option value="100000+" <?php echo ($filtros_activos['presupuesto'] ?? '') === '100000+' ? 'selected' : ''; ?>><?php echo esc_html__('Mas de 100.000', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Distrito -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Distrito', 'flavor-chat-ia'); ?></label>
                <select name="distrito" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value=""><?php echo esc_html__('Todos los distritos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($distritos as $distrito_opcion): ?>
                    <option value="<?php echo esc_attr($distrito_opcion['id'] ?? ''); ?>"
                            <?php echo ($filtros_activos['distrito'] ?? '') == ($distrito_opcion['id'] ?? '') ? 'selected' : ''; ?>>
                        <?php echo esc_html($distrito_opcion['nombre'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($categorias as $categoria_opcion): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]"
                               value="<?php echo esc_attr($categoria_opcion['slug'] ?? ''); ?>"
                               <?php echo in_array($categoria_opcion['slug'] ?? '', $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html($categoria_opcion['nombre'] ?? ''); ?></span>
                        <span class="text-xs text-gray-400 ml-auto">(<?php echo esc_html($categoria_opcion['count'] ?? 0); ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-600 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
