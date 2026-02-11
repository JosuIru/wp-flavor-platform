<?php
/**
 * Frontend: Filtros de Tienda Local
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
$productores = $productores ?? [];
$precio_min = $precio_min ?? 0;
$precio_max = $precio_max ?? 100;
?>

<div class="flavor-frontend flavor-tienda-local-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar productos', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-amber-600 hover:text-amber-700 font-medium"
                    onclick="flavorTienda.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-tienda" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <div class="relative">
                    <input type="text" name="busqueda"
                           value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Nombre del producto...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Categorías -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($categorias as $categoria): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]"
                               value="<?php echo esc_attr($categoria['slug']); ?>"
                               <?php echo in_array($categoria['slug'], $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html($categoria['nombre']); ?></span>
                        <span class="text-xs text-gray-400 ml-auto">(<?php echo esc_html($categoria['count']); ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de precio -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></label>
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <input type="number" name="precio_min"
                                   value="<?php echo esc_attr($filtros_activos['precio_min'] ?? $precio_min); ?>"
                                   min="<?php echo esc_attr($precio_min); ?>"
                                   max="<?php echo esc_attr($precio_max); ?>"
                                   placeholder="<?php echo esc_attr__('Min', 'flavor-chat-ia'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                        </div>
                        <span class="text-gray-400">—</span>
                        <div class="flex-1">
                            <input type="number" name="precio_max"
                                   value="<?php echo esc_attr($filtros_activos['precio_max'] ?? $precio_max); ?>"
                                   min="<?php echo esc_attr($precio_min); ?>"
                                   max="<?php echo esc_attr($precio_max); ?>"
                                   placeholder="<?php echo esc_attr__('Max', 'flavor-chat-ia'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                        </div>
                    </div>
                    <input type="range" name="precio_slider"
                           min="<?php echo esc_attr($precio_min); ?>"
                           max="<?php echo esc_attr($precio_max); ?>"
                           value="<?php echo esc_attr($filtros_activos['precio_max'] ?? $precio_max); ?>"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-amber-500">
                </div>
            </div>

            <!-- Productor -->
            <?php if (!empty($productores)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Productor', 'flavor-chat-ia'); ?></label>
                <select name="productor" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value=""><?php echo esc_html__('Todos los productores', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($productores as $productor): ?>
                    <option value="<?php echo esc_attr($productor['id']); ?>"
                            <?php echo ($filtros_activos['productor'] ?? '') == $productor['id'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($productor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Características especiales -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Características', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="ecologico" value="1"
                               <?php echo !empty($filtros_activos['ecologico']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                        <span class="text-gray-700"><?php echo esc_html__('🌱 Ecológico', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="local" value="1"
                               <?php echo !empty($filtros_activos['local']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html__('📍 Km 0', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="oferta" value="1"
                               <?php echo !empty($filtros_activos['oferta']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="text-gray-700"><?php echo esc_html__('🏷️ En oferta', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="en_stock" value="1"
                               <?php echo !empty($filtros_activos['en_stock']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-gray-700"><?php echo esc_html__('✅ En stock', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Ordenar por -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('precio_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'precio_asc' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Precio: menor a mayor', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('precio_desc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'precio_desc' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Precio: mayor a menor', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('nombre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'nombre' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Nombre A-Z', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('popular', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'popular' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Más vendidos', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </div>

            <!-- Botón aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-600 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
