<?php
/**
 * Frontend: Filtros Ayuntamiento
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
$departamentos = $departamentos ?? [];
?>

<div class="flavor-frontend flavor-ayuntamiento-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-blue-600 hover:text-blue-700 font-medium" onclick="flavorAyuntamiento.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-ayuntamiento" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('¿Qué buscas?', 'flavor-chat-ia'); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Tipo contenido -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr__('tramite', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tipo'] ?? '') === 'tramite' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('📋 Trámites', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr__('noticia', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tipo'] ?? '') === 'noticia' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('📢 Noticias', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr__('servicio', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tipo'] ?? '') === 'servicio' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700"><?php echo esc_html__('📍 Servicios', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></label>
                <select name="categoria" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo esc_attr($cat['slug']); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($cat['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Departamento -->
            <?php if (!empty($departamentos)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Departamento', 'flavor-chat-ia'); ?></label>
                <select name="departamento" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($departamentos as $dep): ?>
                    <option value="<?php echo esc_attr($dep['id']); ?>" <?php echo ($filtros_activos['departamento'] ?? '') == $dep['id'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($dep['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Trámite online -->
            <div>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="tramite_online" value="1"
                           <?php echo !empty($filtros_activos['tramite_online']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-gray-700"><?php echo esc_html__('💻 Solo trámites online', 'flavor-chat-ia'); ?></span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-700 text-white py-3 px-6 rounded-xl font-semibold hover:bg-blue-800 transition-colors">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
