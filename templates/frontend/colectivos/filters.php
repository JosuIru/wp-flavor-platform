<?php
/**
 * Frontend: Filtros de Colectivos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters colectivos bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-rose-600 hover:text-rose-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $categorias_colectivo = [
                    'cultural'        => 'Cultural',
                    'deportivo'       => 'Deportivo',
                    'social'          => 'Social',
                    'medioambiental'  => 'Medioambiental',
                    'vecinal'         => 'Vecinal',
                ];
                foreach ($categorias_colectivo as $valor_categoria => $etiqueta_categoria):
                    $esta_seleccionada = in_array($valor_categoria, $filtros_activos['categoria'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="categoria[]"
                               value="<?php echo esc_attr($valor_categoria); ?>"
                               <?php echo $esta_seleccionada; ?>
                               class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Barrio -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Barrio', 'flavor-chat-ia'); ?></h4>
            <select name="barrio"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                <option value=""><?php echo esc_html__('Todos los barrios', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('centro', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['barrio'] ?? '') === 'centro' ? 'selected' : ''; ?>><?php echo esc_html__('Centro', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('norte', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['barrio'] ?? '') === 'norte' ? 'selected' : ''; ?>><?php echo esc_html__('Norte', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('sur', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['barrio'] ?? '') === 'sur' ? 'selected' : ''; ?>><?php echo esc_html__('Sur', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('este', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['barrio'] ?? '') === 'este' ? 'selected' : ''; ?>><?php echo esc_html__('Este', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('oeste', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['barrio'] ?? '') === 'oeste' ? 'selected' : ''; ?>><?php echo esc_html__('Oeste', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Tamano -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tamano', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tamanos_colectivo = [
                    'pequeno' => 'Pequeno (1-20)',
                    'mediano' => 'Mediano (21-50)',
                    'grande'  => 'Grande (50+)',
                ];
                foreach ($tamanos_colectivo as $valor_tamano => $etiqueta_tamano):
                    $tamano_seleccionado = ($filtros_activos['tamano'] ?? '') === $valor_tamano ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="tamano"
                               value="<?php echo esc_attr($valor_tamano); ?>"
                               <?php echo $tamano_seleccionado; ?>
                               class="w-4 h-4 border-gray-300 text-rose-600 focus:ring-rose-500">
                        <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                            <?php echo esc_html($etiqueta_tamano); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Admite nuevos miembros -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="admite_nuevos"
                       value="1"
                       <?php echo !empty($filtros_activos['admite_nuevos']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                    <?php echo esc_html__('Admite nuevos miembros', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
