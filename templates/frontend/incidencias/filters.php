<?php
/**
 * Frontend: Filtros de Incidencias
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
$zonas = $zonas ?? [];
?>

<div class="flavor-frontend flavor-incidencias-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">🔍 Filtrar incidencias</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-red-600 hover:text-red-700 font-medium"
                    onclick="flavorIncidencias.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-incidencias" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <div class="relative">
                    <input type="text" name="busqueda"
                           value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                           placeholder="Buscar incidencias..."
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="estados[]" value="pendiente"
                               <?php echo in_array('pendiente', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                            <span class="text-gray-700">Pendientes</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="estados[]" value="en_proceso"
                               <?php echo in_array('en_proceso', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                            <span class="text-gray-700">En proceso</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="estados[]" value="resuelto"
                               <?php echo in_array('resuelto', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            <span class="text-gray-700">Resueltas</span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Prioridad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="prioridades[]" value="alta"
                               <?php echo in_array('alta', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="text-gray-700">🔥 Alta</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="prioridades[]" value="media"
                               <?php echo in_array('media', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500">
                        <span class="text-gray-700">⚡ Media</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="prioridades[]" value="baja"
                               <?php echo in_array('baja', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-gray-700">💧 Baja</span>
                    </label>
                </div>
            </div>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($categorias as $categoria): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]"
                               value="<?php echo esc_attr($categoria['slug']); ?>"
                               <?php echo in_array($categoria['slug'], $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="text-gray-700"><?php echo esc_html($categoria['nombre']); ?></span>
                        <span class="text-xs text-gray-400 ml-auto">(<?php echo esc_html($categoria['count']); ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Zona -->
            <?php if (!empty($zonas)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Zona del barrio</label>
                <select name="zona" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="">Todas las zonas</option>
                    <?php foreach ($zonas as $zona): ?>
                    <option value="<?php echo esc_attr($zona['id']); ?>"
                            <?php echo ($filtros_activos['zona'] ?? '') == $zona['id'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($zona['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de reporte</label>
                <select name="fecha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="">Cualquier fecha</option>
                    <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                    <option value="semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>>Esta semana</option>
                    <option value="mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="trimestre" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>>Últimos 3 meses</option>
                </select>
            </div>

            <!-- Ordenar por -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
                <select name="ordenar" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>
                        Más recientes
                    </option>
                    <option value="antiguos" <?php echo ($filtros_activos['ordenar'] ?? '') === 'antiguos' ? 'selected' : ''; ?>>
                        Más antiguos
                    </option>
                    <option value="votos" <?php echo ($filtros_activos['ordenar'] ?? '') === 'votos' ? 'selected' : ''; ?>>
                        Más apoyados
                    </option>
                    <option value="prioridad" <?php echo ($filtros_activos['ordenar'] ?? '') === 'prioridad' ? 'selected' : ''; ?>>
                        Mayor prioridad
                    </option>
                </select>
            </div>

            <!-- Mis incidencias -->
            <div class="pt-4 border-t border-gray-200">
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="mis_incidencias" value="1"
                           <?php echo !empty($filtros_activos['mis_incidencias']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-gray-700">Solo mis incidencias reportadas</span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="incidencias_seguidas" value="1"
                           <?php echo !empty($filtros_activos['incidencias_seguidas']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-gray-700">Incidencias que sigo</span>
                </label>
            </div>

            <!-- Botón aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-red-500 to-rose-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-red-600 hover:to-rose-600 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
