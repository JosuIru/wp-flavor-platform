<?php
/**
 * Frontend: Filtros de Eventos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-eventos-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-pink-600 hover:text-pink-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Categoria -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $categorias_eventos = [
                        'musica' => '🎵 Musica',
                        'deporte' => '⚽ Deporte',
                        'cultura' => '🎭 Cultura',
                        'gastronomia' => '🍽️ Gastronomia',
                        'formacion' => '📚 Formacion',
                        'infantil' => '👶 Infantil',
                        'solidario' => '💚 Solidario',
                        'networking' => '🤝 Networking',
                    ];
                    foreach ($categorias_eventos as $valor_cat_evento => $etiqueta_cat_evento):
                        $marcado_cat_evento = in_array($valor_cat_evento, $filtros_activos['categorias'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat_evento); ?>"
                               <?php echo $marcado_cat_evento; ?>
                               class="w-4 h-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_cat_evento); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fecha -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></h4>
                <select name="fecha" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('manana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'manana' ? 'selected' : ''; ?>><?php echo esc_html__('Manana', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('esta_semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'esta_semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('este_mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'este_mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('proximo_mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'proximo_mes' ? 'selected' : ''; ?>><?php echo esc_html__('Proximo mes', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Precio -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $opciones_precio_evento = [
                        '' => 'Todos',
                        'gratis' => '🆓 Gratis',
                        'pago' => '💰 De pago',
                    ];
                    foreach ($opciones_precio_evento as $valor_precio_ev => $etiqueta_precio_ev):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="precio" value="<?php echo esc_attr($valor_precio_ev); ?>"
                               <?php echo ($filtros_activos['precio'] ?? '') === $valor_precio_ev ? 'checked' : ''; ?>
                               class="w-4 h-4 text-pink-600 border-gray-300 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_precio_ev); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tipo (formato) -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Formato', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $formatos_evento = [
                        '' => 'Todos',
                        'presencial' => '📍 Presencial',
                        'online' => '💻 Online',
                        'hibrido' => '🔄 Hibrido',
                    ];
                    foreach ($formatos_evento as $valor_formato => $etiqueta_formato):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="formato" value="<?php echo esc_attr($valor_formato); ?>"
                               <?php echo ($filtros_activos['formato'] ?? '') === $valor_formato ? 'checked' : ''; ?>
                               class="w-4 h-4 text-pink-600 border-gray-300 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 group-hover:text-pink-600 transition-colors">
                            <?php echo esc_html($etiqueta_formato); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    <option value="<?php echo esc_attr__('fecha_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'fecha_asc' ? 'selected' : ''; ?>><?php echo esc_html__('Proximos primero', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('fecha_desc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'fecha_desc' ? 'selected' : ''; ?>><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('popular', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'popular' ? 'selected' : ''; ?>><?php echo esc_html__('Mas populares', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-rose-500 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-rose-600 hover:to-pink-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
