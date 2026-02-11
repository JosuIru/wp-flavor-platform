<?php
/**
 * Frontend: Filtros de Red Social
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters red-social bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-pink-600 hover:text-pink-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tipos_contenido = [
                    'personas'       => 'Personas',
                    'publicaciones'  => 'Publicaciones',
                    'grupos'         => 'Grupos',
                ];
                foreach ($tipos_contenido as $valor_tipo => $etiqueta_tipo):
                    $tipo_seleccionado = in_array($valor_tipo, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor_tipo); ?>"
                               <?php echo $tipo_seleccionado; ?>
                               class="w-4 h-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Fecha -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></h4>
            <select name="fecha"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Cerca de mi -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ubicacion', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="cerca_de_mi"
                       value="1"
                       <?php echo !empty($filtros_activos['cerca_de_mi']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                    <?php echo esc_html__('Cerca de mi', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Con multimedia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Contenido', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="con_multimedia"
                       value="1"
                       <?php echo !empty($filtros_activos['con_multimedia']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                    <?php echo esc_html__('Con multimedia', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
