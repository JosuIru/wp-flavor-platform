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
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-pink-600 hover:text-pink-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Categoria -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Categoria</h4>
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
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Fecha</h4>
                <select name="fecha" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    <option value="">Cualquier fecha</option>
                    <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                    <option value="manana" <?php echo ($filtros_activos['fecha'] ?? '') === 'manana' ? 'selected' : ''; ?>>Manana</option>
                    <option value="esta_semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'esta_semana' ? 'selected' : ''; ?>>Esta semana</option>
                    <option value="este_mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'este_mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="proximo_mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'proximo_mes' ? 'selected' : ''; ?>>Proximo mes</option>
                </select>
            </div>

            <!-- Precio -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Precio</h4>
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
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Formato</h4>
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
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Ordenar por</h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-pink-500">
                    <option value="fecha_asc" <?php echo ($filtros_activos['ordenar'] ?? '') === 'fecha_asc' ? 'selected' : ''; ?>>Proximos primero</option>
                    <option value="fecha_desc" <?php echo ($filtros_activos['ordenar'] ?? '') === 'fecha_desc' ? 'selected' : ''; ?>>Mas recientes</option>
                    <option value="popular" <?php echo ($filtros_activos['ordenar'] ?? '') === 'popular' ? 'selected' : ''; ?>>Mas populares</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-rose-500 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-rose-600 hover:to-pink-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
