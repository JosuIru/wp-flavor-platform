<?php
/**
 * Frontend: Filtros de Publicidad
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-publicidad-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-pink-600 hover:text-pink-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Tipo de campana -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de campana', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $tipos_anuncio = [
                        'banner' => '🖼️ Banner',
                        'video' => '🎬 Video',
                        'nativo' => '📝 Nativo',
                    ];
                    foreach ($tipos_anuncio as $valor_tipo_anuncio => $etiqueta_tipo_anuncio):
                        $marcado_tipo_anuncio = in_array($valor_tipo_anuncio, $filtros_activos['tipos'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="tipos[]" value="<?php echo esc_attr($valor_tipo_anuncio); ?>"
                               <?php echo $marcado_tipo_anuncio; ?>
                               class="w-4 h-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo_anuncio); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Estado -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $opciones_estado_campania = [
                        '' => 'Todos',
                        'activa' => '🟢 Activa',
                        'pausada' => '⏸️ Pausada',
                        'finalizada' => '🔴 Finalizada',
                    ];
                    foreach ($opciones_estado_campania as $valor_estado_camp => $etiqueta_estado_camp):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="estado" value="<?php echo esc_attr($valor_estado_camp); ?>"
                               <?php echo ($filtros_activos['estado'] ?? '') === $valor_estado_camp ? 'checked' : ''; ?>
                               class="w-4 h-4 text-pink-600 border-gray-300 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_estado_camp); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de fechas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Rango de fechas', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Desde', 'flavor-chat-ia'); ?></label>
                        <input type="date" name="fecha_desde" value="<?php echo esc_attr($filtros_activos['fecha_desde'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Hasta', 'flavor-chat-ia'); ?></label>
                        <input type="date" name="fecha_hasta" value="<?php echo esc_attr($filtros_activos['fecha_hasta'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('impresiones', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'impresiones' ? 'selected' : ''; ?>><?php echo esc_html__('Mas impresiones', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('clics', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'clics' ? 'selected' : ''; ?>><?php echo esc_html__('Mas clics', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('presupuesto', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'presupuesto' ? 'selected' : ''; ?>><?php echo esc_html__('Mayor presupuesto', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-pink-500 to-rose-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-pink-600 hover:to-rose-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
