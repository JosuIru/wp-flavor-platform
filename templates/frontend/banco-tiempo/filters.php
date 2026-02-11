<?php
/**
 * Frontend: Filtros de Banco de Tiempo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-banco-tiempo-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar servicios', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-violet-600 hover:text-violet-700 font-medium" onclick="flavorBancoTiempo.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-banco-tiempo" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('¿Qué servicio buscas?', 'flavor-chat-ia'); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            </div>

            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-500 border-gray-300 focus:ring-violet-500">
                        <span class="text-gray-700"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr__('oferta', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tipo'] ?? '') === 'oferta' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 focus:ring-green-500">
                        <span class="text-gray-700"><?php echo esc_html__('🎁 Ofrecen', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr__('demanda', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tipo'] ?? '') === 'demanda' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('🙋 Buscan', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($categorias as $cat): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($cat['slug']); ?>"
                               <?php echo in_array($cat['slug'], $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-500 border-gray-300 rounded focus:ring-violet-500">
                        <span class="text-gray-700"><?php echo esc_html($cat['icono'] ?? ''); ?> <?php echo esc_html($cat['nombre']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Horas -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Duración máxima', 'flavor-chat-ia'); ?></label>
                <select name="horas_max" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                    <option value=""><?php echo esc_html__('Sin límite', 'flavor-chat-ia'); ?></option>
                    <option value="1" <?php echo ($filtros_activos['horas_max'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 1 hora', 'flavor-chat-ia'); ?></option>
                    <option value="2" <?php echo ($filtros_activos['horas_max'] ?? '') === '2' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 2 horas', 'flavor-chat-ia'); ?></option>
                    <option value="4" <?php echo ($filtros_activos['horas_max'] ?? '') === '4' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 4 horas', 'flavor-chat-ia'); ?></option>
                    <option value="8" <?php echo ($filtros_activos['horas_max'] ?? '') === '8' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 8 horas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Ordenar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>><?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('valoracion', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'valoracion' ? 'selected' : ''; ?>><?php echo esc_html__('Mejor valorados', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('horas_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'horas_asc' ? 'selected' : ''; ?>><?php echo esc_html__('Menos horas', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('horas_desc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'horas_desc' ? 'selected' : ''; ?>><?php echo esc_html__('Más horas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-violet-500 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-violet-600 hover:to-purple-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
