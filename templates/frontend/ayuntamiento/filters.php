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
            <h3 class="text-lg font-bold text-gray-800">🔍 Filtrar</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-blue-600 hover:text-blue-700 font-medium" onclick="flavorAyuntamiento.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-ayuntamiento" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="¿Qué buscas?"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Tipo contenido -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700">Todos</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="tramite" <?php echo ($filtros_activos['tipo'] ?? '') === 'tramite' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700">📋 Trámites</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="noticia" <?php echo ($filtros_activos['tipo'] ?? '') === 'noticia' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700">📢 Noticias</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="servicio" <?php echo ($filtros_activos['tipo'] ?? '') === 'servicio' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                        <span class="text-gray-700">📍 Servicios</span>
                    </label>
                </div>
            </div>

            <!-- Categoría -->
            <?php if (!empty($categorias)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                <select name="categoria" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                <select name="departamento" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
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
                    <span class="text-gray-700">💻 Solo trámites online</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-700 text-white py-3 px-6 rounded-xl font-semibold hover:bg-blue-800 transition-colors">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
