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
$unique_inc_filter_id = wp_unique_id('inc_filter_');
?>

<div class="flavor-frontend flavor-incidencias-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar incidencias', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-red-600 hover:text-red-700 font-medium"
                    onclick="flavorIncidencias.limpiarFiltros()"
                    aria-label="<?php esc_attr_e('Limpiar todos los filtros aplicados', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-incidencias" class="space-y-6" role="search" aria-label="<?php esc_attr_e('Filtros de incidencias', 'flavor-chat-ia'); ?>">
            <!-- Búsqueda -->
            <div>
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_busqueda" class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <div class="relative">
                    <input type="text"
                           id="<?php echo esc_attr($unique_inc_filter_id); ?>_busqueda"
                           name="busqueda"
                           value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Buscar incidencias...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Estado -->
            <fieldset>
                <legend class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></legend>
                <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Filtrar por estado', 'flavor-chat-ia'); ?>">
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_pendiente" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_pendiente"
                               name="estados[]"
                               value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('pendiente', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-red-500 rounded-full" aria-hidden="true"></span>
                            <span class="text-gray-700"><?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?></span>
                        </span>
                    </label>
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_proceso" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_proceso"
                               name="estados[]"
                               value="<?php echo esc_attr__('en_proceso', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('en_proceso', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-yellow-500 rounded-full" aria-hidden="true"></span>
                            <span class="text-gray-700"><?php echo esc_html__('En proceso', 'flavor-chat-ia'); ?></span>
                        </span>
                    </label>
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_resuelto" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_estado_resuelto"
                               name="estados[]"
                               value="<?php echo esc_attr__('resuelto', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('resuelto', $filtros_activos['estados'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-green-500 rounded-full" aria-hidden="true"></span>
                            <span class="text-gray-700"><?php echo esc_html__('Resueltas', 'flavor-chat-ia'); ?></span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <!-- Prioridad -->
            <fieldset>
                <legend class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Prioridad', 'flavor-chat-ia'); ?></legend>
                <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Filtrar por prioridad', 'flavor-chat-ia'); ?>">
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_alta" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_alta"
                               name="prioridades[]"
                               value="<?php echo esc_attr__('alta', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('alta', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                        <span class="text-gray-700"><?php echo esc_html__('🔥 Alta', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_media" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_media"
                               name="prioridades[]"
                               value="<?php echo esc_attr__('media', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('media', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500">
                        <span class="text-gray-700"><?php echo esc_html__('⚡ Media', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_baja" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_inc_filter_id); ?>_prioridad_baja"
                               name="prioridades[]"
                               value="<?php echo esc_attr__('baja', 'flavor-chat-ia'); ?>"
                               <?php echo in_array('baja', $filtros_activos['prioridades'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('💧 Baja', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </fieldset>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></label>
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
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_zona" class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Zona del barrio', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_inc_filter_id); ?>_zona"
                        name="zona"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value=""><?php echo esc_html__('Todas las zonas', 'flavor-chat-ia'); ?></option>
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
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_fecha" class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Fecha de reporte', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_inc_filter_id); ?>_fecha"
                        name="fecha"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('trimestre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>><?php echo esc_html__('Últimos 3 meses', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Ordenar por -->
            <div>
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_ordenar" class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_inc_filter_id); ?>_ordenar"
                        name="ordenar"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Más recientes', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('antiguos', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'antiguos' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Más antiguos', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('votos', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'votos' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Más apoyados', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('prioridad', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'prioridad' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Mayor prioridad', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </div>

            <!-- Mis incidencias -->
            <fieldset class="pt-4 border-t border-gray-200">
                <legend class="sr-only"><?php echo esc_html__('Mis incidencias', 'flavor-chat-ia'); ?></legend>
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_mis_incidencias" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox"
                           id="<?php echo esc_attr($unique_inc_filter_id); ?>_mis_incidencias"
                           name="mis_incidencias"
                           value="1"
                           <?php echo !empty($filtros_activos['mis_incidencias']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-gray-700"><?php echo esc_html__('Solo mis incidencias reportadas', 'flavor-chat-ia'); ?></span>
                </label>
                <label for="<?php echo esc_attr($unique_inc_filter_id); ?>_incidencias_seguidas" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox"
                           id="<?php echo esc_attr($unique_inc_filter_id); ?>_incidencias_seguidas"
                           name="incidencias_seguidas"
                           value="1"
                           <?php echo !empty($filtros_activos['incidencias_seguidas']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-gray-700"><?php echo esc_html__('Incidencias que sigo', 'flavor-chat-ia'); ?></span>
                </label>
            </fieldset>

            <!-- Botón aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-red-500 to-rose-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-red-600 hover:to-rose-600 transition-all shadow-md"
                    aria-label="<?php esc_attr_e('Aplicar filtros de búsqueda', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
