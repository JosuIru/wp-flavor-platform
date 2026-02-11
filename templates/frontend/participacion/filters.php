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
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('Filtrar propuestas', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-amber-600 hover:text-amber-700 font-medium"
                    onclick="flavorParticipacion.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-participacion" class="space-y-6">
            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></label>
                <select name="fecha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('trimestre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>><?php echo esc_html__('Ultimos 3 meses', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Ordenar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('votadas', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'votadas' ? 'selected' : ''; ?>><?php echo esc_html__('Mas votadas', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('comentadas', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'comentadas' ? 'selected' : ''; ?>><?php echo esc_html__('Mas comentadas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
