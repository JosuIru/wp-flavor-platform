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
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></h4>
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
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
            <select name="categoria"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value=""><?php echo esc_html__('Todas las categorias', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('eventos', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === 'eventos' ? 'selected' : ''; ?>><?php echo esc_html__('Eventos', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === 'cultura' ? 'selected' : ''; ?>><?php echo esc_html__('Cultura', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('deportes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === 'deportes' ? 'selected' : ''; ?>><?php echo esc_html__('Deportes', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('noticias', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === 'noticias' ? 'selected' : ''; ?>><?php echo esc_html__('Noticias', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('tutoriales', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['categoria'] ?? '') === 'tutoriales' ? 'selected' : ''; ?>><?php echo esc_html__('Tutoriales', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Fecha -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></h4>
            <select name="fecha"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('ano', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'ano' ? 'selected' : ''; ?>><?php echo esc_html__('Este ano', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Duracion (para video/audio) -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Duracion', 'flavor-chat-ia'); ?></h4>
            <select name="duracion"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value=""><?php echo esc_html__('Cualquier duracion', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('corto', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['duracion'] ?? '') === 'corto' ? 'selected' : ''; ?>><?php echo esc_html__('Corto (menos de 5 min)', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('medio', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['duracion'] ?? '') === 'medio' ? 'selected' : ''; ?>><?php echo esc_html__('Medio (5-20 min)', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('largo', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['duracion'] ?? '') === 'largo' ? 'selected' : ''; ?>><?php echo esc_html__('Largo (mas de 20 min)', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Mas populares -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="mas_populares"
                       value="1"
                       <?php echo !empty($filtros_activos['mas_populares']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                    <?php echo esc_html__('Mas populares primero', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
