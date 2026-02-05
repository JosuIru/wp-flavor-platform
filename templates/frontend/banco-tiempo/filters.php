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
            <h3 class="text-lg font-bold text-gray-800">🔍 Filtrar servicios</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-violet-600 hover:text-violet-700 font-medium" onclick="flavorBancoTiempo.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-banco-tiempo" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="¿Qué servicio buscas?"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            </div>

            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-500 border-gray-300 focus:ring-violet-500">
                        <span class="text-gray-700">Todos</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="oferta" <?php echo ($filtros_activos['tipo'] ?? '') === 'oferta' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 focus:ring-green-500">
                        <span class="text-gray-700">🎁 Ofrecen</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="demanda" <?php echo ($filtros_activos['tipo'] ?? '') === 'demanda' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700">🙋 Buscan</span>
                    </label>
                </div>
            </div>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Duración máxima</label>
                <select name="horas_max" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                    <option value="">Sin límite</option>
                    <option value="1" <?php echo ($filtros_activos['horas_max'] ?? '') === '1' ? 'selected' : ''; ?>>Hasta 1 hora</option>
                    <option value="2" <?php echo ($filtros_activos['horas_max'] ?? '') === '2' ? 'selected' : ''; ?>>Hasta 2 horas</option>
                    <option value="4" <?php echo ($filtros_activos['horas_max'] ?? '') === '4' ? 'selected' : ''; ?>>Hasta 4 horas</option>
                    <option value="8" <?php echo ($filtros_activos['horas_max'] ?? '') === '8' ? 'selected' : ''; ?>>Hasta 8 horas</option>
                </select>
            </div>

            <!-- Ordenar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                    <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                    <option value="valoracion" <?php echo ($filtros_activos['ordenar'] ?? '') === 'valoracion' ? 'selected' : ''; ?>>Mejor valorados</option>
                    <option value="horas_asc" <?php echo ($filtros_activos['ordenar'] ?? '') === 'horas_asc' ? 'selected' : ''; ?>>Menos horas</option>
                    <option value="horas_desc" <?php echo ($filtros_activos['ordenar'] ?? '') === 'horas_desc' ? 'selected' : ''; ?>>Más horas</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-violet-500 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-violet-600 hover:to-purple-700 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
