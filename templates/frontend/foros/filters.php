<?php
/**
 * Frontend: Filtros de Foros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters foros bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Categoria</h4>
            <div class="space-y-2">
                <?php
                $categorias_foro = [
                    'general'     => 'General',
                    'tecnologia'  => 'Tecnologia',
                    'cultura'     => 'Cultura',
                    'deportes'    => 'Deportes',
                ];
                foreach ($categorias_foro as $valor_categoria => $etiqueta_categoria):
                    $esta_seleccionada = in_array($valor_categoria, $filtros_activos['categoria'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="categoria[]"
                               value="<?php echo esc_attr($valor_categoria); ?>"
                               <?php echo $esta_seleccionada; ?>
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ordenar por -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Ordenar por</h4>
            <select name="ordenar"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>Mas recientes</option>
                <option value="populares" <?php echo ($filtros_activos['ordenar'] ?? '') === 'populares' ? 'selected' : ''; ?>>Mas populares</option>
                <option value="sin_respuesta" <?php echo ($filtros_activos['ordenar'] ?? '') === 'sin_respuesta' ? 'selected' : ''; ?>>Sin respuesta</option>
            </select>
        </div>

        <!-- Fecha -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Fecha</h4>
            <select name="fecha"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Cualquier fecha</option>
                <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                <option value="semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>>Esta semana</option>
                <option value="mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                <option value="ano" <?php echo ($filtros_activos['fecha'] ?? '') === 'ano' ? 'selected' : ''; ?>>Este ano</option>
            </select>
        </div>

        <!-- Solo temas sin resolver -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Estado</h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="sin_resolver"
                       value="1"
                       <?php echo !empty($filtros_activos['sin_resolver']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                    Solo temas sin resolver
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #6366f1 0%, #9333ea 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
