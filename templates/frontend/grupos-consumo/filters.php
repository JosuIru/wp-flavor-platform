<?php
/**
 * Frontend: Filtros de Grupos de Consumo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$zonas = $zonas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-grupos-consumo-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar grupos', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-lime-600 hover:text-lime-700 font-medium" onclick="flavorGruposConsumo.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-grupos-consumo" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <div class="relative">
                    <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Nombre del grupo...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Zona -->
            <?php if (!empty($zonas)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Zona', 'flavor-chat-ia'); ?></label>
                <select name="zona" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                    <option value=""><?php echo esc_html__('Todas las zonas', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($zonas as $zona): ?>
                    <option value="<?php echo esc_attr($zona['slug']); ?>" <?php echo ($filtros_activos['zona'] ?? '') === $zona['slug'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($zona['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Categorías de productos -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Tipo de productos', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($categorias as $cat): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($cat['slug']); ?>"
                               <?php echo in_array($cat['slug'], $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-lime-500 border-gray-300 rounded focus:ring-lime-500">
                        <span class="text-gray-700"><?php echo esc_html($cat['nombre']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="abierto" value="1"
                               <?php echo !empty($filtros_activos['abierto']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-lime-500 border-gray-300 rounded focus:ring-lime-500">
                        <span class="text-gray-700"><?php echo esc_html__('Abierto a nuevos miembros', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="ecologico" value="1"
                               <?php echo !empty($filtros_activos['ecologico']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                        <span class="text-gray-700"><?php echo esc_html__('🌱 Productos ecológicos', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Ordenar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>><?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('miembros', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'miembros' ? 'selected' : ''; ?>><?php echo esc_html__('Más miembros', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('nombre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'nombre' ? 'selected' : ''; ?>><?php echo esc_html__('Nombre A-Z', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-lime-500 to-green-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-lime-600 hover:to-green-600 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
