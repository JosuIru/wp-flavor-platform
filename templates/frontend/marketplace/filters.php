<?php
/**
 * Frontend: Filtros de Marketplace
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$unique_mk_filter_id = wp_unique_id('mk_filter_');
?>

<div class="flavor-frontend flavor-marketplace-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?"
               class="text-sm text-green-600 hover:text-green-700 font-medium"
               aria-label="<?php esc_attr_e('Limpiar todos los filtros aplicados', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
            </a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6" role="search" aria-label="<?php esc_attr_e('Filtros de productos', 'flavor-chat-ia'); ?>">
            <!-- Categoria -->
            <fieldset>
                <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></legend>
                <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Categorías de productos', 'flavor-chat-ia'); ?>">
                    <?php
                    $categorias_marketplace = [
                        'electronica' => '💻 Electronica',
                        'hogar' => '🏠 Hogar',
                        'ropa' => '👗 Ropa',
                        'deportes' => '⚽ Deportes',
                        'motor' => '🚗 Motor',
                        'libros' => '📚 Libros',
                        'jardin' => '🌿 Jardin',
                        'juguetes' => '🧸 Juguetes',
                    ];
                    foreach ($categorias_marketplace as $valor_categoria => $etiqueta_categoria):
                        $marcado_categoria = in_array($valor_categoria, $filtros_activos['categorias'] ?? []) ? 'checked' : '';
                    ?>
                    <label for="<?php echo esc_attr($unique_mk_filter_id); ?>_cat_<?php echo esc_attr($valor_categoria); ?>" class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_mk_filter_id); ?>_cat_<?php echo esc_attr($valor_categoria); ?>"
                               name="categorias[]"
                               value="<?php echo esc_attr($valor_categoria); ?>"
                               <?php echo $marcado_categoria; ?>
                               class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta_categoria); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <!-- Condicion -->
            <fieldset>
                <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Condicion', 'flavor-chat-ia'); ?></legend>
                <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Condición del producto', 'flavor-chat-ia'); ?>">
                    <?php
                    $condiciones_producto = [
                        'nuevo' => 'Nuevo',
                        'como_nuevo' => 'Como nuevo',
                        'usado' => 'Usado',
                    ];
                    foreach ($condiciones_producto as $valor_condicion => $etiqueta_condicion):
                        $marcado_condicion = in_array($valor_condicion, $filtros_activos['condicion'] ?? []) ? 'checked' : '';
                    ?>
                    <label for="<?php echo esc_attr($unique_mk_filter_id); ?>_cond_<?php echo esc_attr($valor_condicion); ?>" class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_mk_filter_id); ?>_cond_<?php echo esc_attr($valor_condicion); ?>"
                               name="condicion[]"
                               value="<?php echo esc_attr($valor_condicion); ?>"
                               <?php echo $marcado_condicion; ?>
                               class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta_condicion); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <!-- Rango de precio -->
            <div>
                <label for="<?php echo esc_attr($unique_mk_filter_id); ?>_precio" class="text-sm font-semibold text-gray-900 mb-3 block"><?php echo esc_html__('Rango de precio', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_mk_filter_id); ?>_precio"
                        name="precio"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                    <option value=""><?php echo esc_html__('Cualquier precio', 'flavor-chat-ia'); ?></option>
                    <option value="0-10" <?php echo ($filtros_activos['precio'] ?? '') === '0-10' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 10 €', 'flavor-chat-ia'); ?></option>
                    <option value="10-50" <?php echo ($filtros_activos['precio'] ?? '') === '10-50' ? 'selected' : ''; ?>>10 € - 50 €</option>
                    <option value="50-100" <?php echo ($filtros_activos['precio'] ?? '') === '50-100' ? 'selected' : ''; ?>>50 € - 100 €</option>
                    <option value="100-500" <?php echo ($filtros_activos['precio'] ?? '') === '100-500' ? 'selected' : ''; ?>>100 € - 500 €</option>
                    <option value="500+" <?php echo ($filtros_activos['precio'] ?? '') === '500+' ? 'selected' : ''; ?>><?php echo esc_html__('Mas de 500 €', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Distancia -->
            <div>
                <label for="<?php echo esc_attr($unique_mk_filter_id); ?>_distancia" class="text-sm font-semibold text-gray-900 mb-3 block"><?php echo esc_html__('Distancia', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_mk_filter_id); ?>_distancia"
                        name="distancia"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                    <option value=""><?php echo esc_html__('Cualquier distancia', 'flavor-chat-ia'); ?></option>
                    <option value="1" <?php echo ($filtros_activos['distancia'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo esc_html__('Menos de 1 km', 'flavor-chat-ia'); ?></option>
                    <option value="5" <?php echo ($filtros_activos['distancia'] ?? '') === '5' ? 'selected' : ''; ?>><?php echo esc_html__('Menos de 5 km', 'flavor-chat-ia'); ?></option>
                    <option value="10" <?php echo ($filtros_activos['distancia'] ?? '') === '10' ? 'selected' : ''; ?>><?php echo esc_html__('Menos de 10 km', 'flavor-chat-ia'); ?></option>
                    <option value="25" <?php echo ($filtros_activos['distancia'] ?? '') === '25' ? 'selected' : ''; ?>><?php echo esc_html__('Menos de 25 km', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Tipo de anuncio -->
            <fieldset>
                <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de anuncio', 'flavor-chat-ia'); ?></legend>
                <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Tipo de anuncio', 'flavor-chat-ia'); ?>">
                    <?php
                    $tipos_anuncio = [
                        'venta' => '🏷️ Venta',
                        'intercambio' => '🔄 Intercambio',
                        'regalo' => '🎁 Regalo',
                    ];
                    foreach ($tipos_anuncio as $valor_tipo => $etiqueta_tipo):
                        $marcado_tipo = in_array($valor_tipo, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                    ?>
                    <label for="<?php echo esc_attr($unique_mk_filter_id); ?>_tipo_<?php echo esc_attr($valor_tipo); ?>" class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_mk_filter_id); ?>_tipo_<?php echo esc_attr($valor_tipo); ?>"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor_tipo); ?>"
                               <?php echo $marcado_tipo; ?>
                               class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-lime-500 to-green-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-lime-600 hover:to-green-700 transition-all shadow-md"
                    aria-label="<?php esc_attr_e('Aplicar filtros de búsqueda', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
