<?php
/**
 * Frontend: Filtros de busqueda del DEX Solana
 *
 * Panel de filtros para tokens y pools de liquidez:
 * tipo de token, rango de liquidez, volumen 24h y ordenamiento.
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-dex-solana-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Tipo de token -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de token', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $tipos_token_disponibles = [
                        'todos'  => 'Todos',
                        'stable' => 'Stablecoins',
                        'defi'   => 'DeFi',
                        'meme'   => 'Meme',
                        'gaming' => 'Gaming',
                    ];
                    foreach ($tipos_token_disponibles as $valor_tipo_token => $etiqueta_tipo_token):
                        $seleccionado_tipo = in_array($valor_tipo_token, $filtros_activos['tipo_token'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="tipo_token[]" value="<?php echo esc_attr($valor_tipo_token); ?>"
                               <?php echo $seleccionado_tipo; ?>
                               class="w-4 h-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-sm text-gray-700 group-hover:text-cyan-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo_token); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de liquidez -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Liquidez (USD)', 'flavor-chat-ia'); ?></h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Minimo', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="liquidez_minima" placeholder="0"
                               value="<?php echo esc_attr($filtros_activos['liquidez_minima'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Maximo', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="liquidez_maxima" placeholder="<?php echo esc_attr__('Sin limite', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($filtros_activos['liquidez_maxima'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                </div>
            </div>

            <!-- Volumen 24h -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Volumen 24h (USD)', 'flavor-chat-ia'); ?></h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Minimo', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="volumen_minimo" placeholder="0"
                               value="<?php echo esc_attr($filtros_activos['volumen_minimo'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block"><?php echo esc_html__('Maximo', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="volumen_maximo" placeholder="<?php echo esc_attr__('Sin limite', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($filtros_activos['volumen_maximo'] ?? ''); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    </div>
                </div>
            </div>

            <!-- Ordenar por -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></h4>
                <select name="ordenar_por"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="<?php echo esc_attr__('precio', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar_por'] ?? '') === 'precio' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Precio', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('volumen', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar_por'] ?? '') === 'volumen' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Volumen', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('liquidez', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar_por'] ?? '') === 'liquidez' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Liquidez', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('nombre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar_por'] ?? '') === 'nombre' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </div>

            <!-- Direccion de orden -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Direccion', 'flavor-chat-ia'); ?></h4>
                <div class="flex gap-2">
                    <?php $direccion_orden_actual = $filtros_activos['direccion_orden'] ?? 'desc'; ?>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="direccion_orden" value="<?php echo esc_attr__('asc', 'flavor-chat-ia'); ?>" class="sr-only peer"
                               <?php echo $direccion_orden_actual === 'asc' ? 'checked' : ''; ?>>
                        <div class="text-center py-2 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-cyan-50 peer-checked:border-cyan-500 peer-checked:text-cyan-700 transition-colors">
                            <?php echo esc_html__('Ascendente', 'flavor-chat-ia'); ?>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="direccion_orden" value="<?php echo esc_attr__('desc', 'flavor-chat-ia'); ?>" class="sr-only peer"
                               <?php echo $direccion_orden_actual === 'desc' ? 'checked' : ''; ?>>
                        <div class="text-center py-2 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-cyan-50 peer-checked:border-cyan-500 peer-checked:text-cyan-700 transition-colors">
                            <?php echo esc_html__('Descendente', 'flavor-chat-ia'); ?>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Boton aplicar filtros -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-500 to-teal-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-cyan-600 hover:to-teal-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
