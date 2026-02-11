<?php
/**
 * Frontend: Filtros de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters radio bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-red-600 hover:text-red-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo de programa -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de programa', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tipos = [
                    'magazine' => 'Magazine',
                    'informativo' => 'Informativo',
                    'musical' => 'Musical',
                    'deportes' => 'Deportes',
                    'tertulia' => 'Tertulia',
                    'cultural' => 'Cultural',
                    'infantil' => 'Infantil',
                ];
                foreach ($tipos as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-gray-700 group-hover:text-red-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dia de emision -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Dia de emision', 'flavor-chat-ia'); ?></h4>
            <div class="grid grid-cols-4 gap-2">
                <?php
                $dias = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
                $dias_completos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                foreach ($dias as $indice => $dia):
                    $checked = in_array($dias_completos[$indice], $filtros_activos['dias'] ?? []) ? 'checked' : '';
                ?>
                    <label class="cursor-pointer">
                        <input type="checkbox"
                               name="dias[]"
                               value="<?php echo esc_attr($dias_completos[$indice]); ?>"
                               <?php echo $checked; ?>
                               class="peer hidden">
                        <span class="block w-full py-2 text-center rounded-lg border text-sm font-medium peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 hover:bg-red-50 transition-colors">
                            <?php echo $dia; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Horario -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Franja horaria', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $horarios = [
                    'manana' => 'Manana (6-12h)',
                    'mediodia' => 'Mediodia (12-15h)',
                    'tarde' => 'Tarde (15-20h)',
                    'noche' => 'Noche (20-24h)',
                ];
                foreach ($horarios as $valor => $etiqueta):
                    $checked = ($filtros_activos['horario'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="horario"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-gray-700 group-hover:text-red-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #dc2626 0%, #e11d48 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
