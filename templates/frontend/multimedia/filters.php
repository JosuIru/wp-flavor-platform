<?php
/**
 * Frontend: Filtros de Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters multimedia bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-700">Limpiar</a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo</h4>
            <div class="space-y-2">
                <?php
                $tipos_multimedia = [
                    'video'     => 'Video',
                    'foto'      => 'Foto',
                    'audio'     => 'Audio',
                    'documento' => 'Documento',
                ];
                foreach ($tipos_multimedia as $valor_tipo => $etiqueta_tipo):
                    $tipo_seleccionado = in_array($valor_tipo, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor_tipo); ?>"
                               <?php echo $tipo_seleccionado; ?>
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Categoria</h4>
            <select name="categoria"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Todas las categorias</option>
                <option value="eventos" <?php echo ($filtros_activos['categoria'] ?? '') === 'eventos' ? 'selected' : ''; ?>>Eventos</option>
                <option value="cultura" <?php echo ($filtros_activos['categoria'] ?? '') === 'cultura' ? 'selected' : ''; ?>>Cultura</option>
                <option value="deportes" <?php echo ($filtros_activos['categoria'] ?? '') === 'deportes' ? 'selected' : ''; ?>>Deportes</option>
                <option value="noticias" <?php echo ($filtros_activos['categoria'] ?? '') === 'noticias' ? 'selected' : ''; ?>>Noticias</option>
                <option value="tutoriales" <?php echo ($filtros_activos['categoria'] ?? '') === 'tutoriales' ? 'selected' : ''; ?>>Tutoriales</option>
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

        <!-- Duracion (para video/audio) -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Duracion</h4>
            <select name="duracion"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Cualquier duracion</option>
                <option value="corto" <?php echo ($filtros_activos['duracion'] ?? '') === 'corto' ? 'selected' : ''; ?>>Corto (menos de 5 min)</option>
                <option value="medio" <?php echo ($filtros_activos['duracion'] ?? '') === 'medio' ? 'selected' : ''; ?>>Medio (5-20 min)</option>
                <option value="largo" <?php echo ($filtros_activos['duracion'] ?? '') === 'largo' ? 'selected' : ''; ?>>Largo (mas de 20 min)</option>
            </select>
        </div>

        <!-- Mas populares -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Ordenar</h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="mas_populares"
                       value="1"
                       <?php echo !empty($filtros_activos['mas_populares']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                    Mas populares primero
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
            Aplicar Filtros
        </button>
    </form>
</div>
