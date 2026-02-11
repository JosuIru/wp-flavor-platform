<?php
/**
 * Frontend: Filtros de Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters ayuda-vecinal bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-orange-600 hover:text-orange-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo de solicitud -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tipos = [
                    'necesito' => 'Necesitan ayuda',
                    'ofrezco' => 'Ofrecen ayuda',
                ];
                foreach ($tipos as $valor => $etiqueta):
                    $checked = ($filtros_activos['tipo'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="tipo"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $categorias = [
                    'compras' => 'Compras y recados',
                    'transporte' => 'Transporte',
                    'cuidados' => 'Cuidado de personas',
                    'mascotas' => 'Mascotas',
                    'hogar' => 'Tareas del hogar',
                    'tecnologia' => 'Tecnologia',
                    'compannia' => 'Compania',
                    'otros' => 'Otros',
                ];
                foreach ($categorias as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['categoria'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="categoria[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Urgencia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Urgencia', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="solo_urgentes"
                       value="1"
                       <?php echo !empty($filtros_activos['solo_urgentes']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-sm text-gray-700 group-hover:text-orange-600 transition-colors">
                    <?php echo esc_html__('Solo urgentes', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Distancia -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Distancia maxima', 'flavor-chat-ia'); ?></h4>
            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                <option value="500" <?php echo ($filtros_activos['distancia'] ?? '') === '500' ? 'selected' : ''; ?>><?php echo esc_html__('500m', 'flavor-chat-ia'); ?></option>
                <option value="1000" <?php echo ($filtros_activos['distancia'] ?? '') === '1000' ? 'selected' : ''; ?>><?php echo esc_html__('1 km', 'flavor-chat-ia'); ?></option>
                <option value="2000" <?php echo ($filtros_activos['distancia'] ?? '') === '2000' ? 'selected' : ''; ?>><?php echo esc_html__('2 km', 'flavor-chat-ia'); ?></option>
                <option value="5000" <?php echo ($filtros_activos['distancia'] ?? '') === '5000' ? 'selected' : ''; ?>><?php echo esc_html__('5 km', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
