<?php
/**
 * Frontend: Filtros de Chat Grupos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-chat-grupos-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Categoria -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Categoria</h4>
                <div class="space-y-2">
                    <?php
                    $categorias_chat_grupo = [
                        'vecinos' => '🏘️ Vecinos',
                        'deportes' => '⚽ Deportes',
                        'ocio' => '🎮 Ocio',
                        'padres' => '👨‍👩‍👧 Padres',
                        'cultura' => '🎭 Cultura',
                        'mascotas' => '🐾 Mascotas',
                        'tecnologia' => '💻 Tecnologia',
                    ];
                    foreach ($categorias_chat_grupo as $valor_cat_grupo => $etiqueta_cat_grupo):
                        $marcado_cat_grupo = in_array($valor_cat_grupo, $filtros_activos['categorias'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat_grupo); ?>"
                               <?php echo $marcado_cat_grupo; ?>
                               class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($etiqueta_cat_grupo); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tipo de grupo -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Tipo de grupo</h4>
                <div class="space-y-2">
                    <?php
                    $tipos_grupo = [
                        '' => 'Todos',
                        'publico' => '🌐 Publico',
                        'privado' => '🔒 Privado',
                    ];
                    foreach ($tipos_grupo as $valor_tipo_grupo => $etiqueta_tipo_grupo):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="tipo" value="<?php echo esc_attr($valor_tipo_grupo); ?>"
                               <?php echo ($filtros_activos['tipo'] ?? '') === $valor_tipo_grupo ? 'checked' : ''; ?>
                               class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                        <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($etiqueta_tipo_grupo); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Miembros minimo -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Miembros minimo</h4>
                <select name="miembros_minimo" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value="">Cualquiera</option>
                    <option value="5" <?php echo ($filtros_activos['miembros_minimo'] ?? '') === '5' ? 'selected' : ''; ?>>5+ miembros</option>
                    <option value="10" <?php echo ($filtros_activos['miembros_minimo'] ?? '') === '10' ? 'selected' : ''; ?>>10+ miembros</option>
                    <option value="25" <?php echo ($filtros_activos['miembros_minimo'] ?? '') === '25' ? 'selected' : ''; ?>>25+ miembros</option>
                    <option value="50" <?php echo ($filtros_activos['miembros_minimo'] ?? '') === '50' ? 'selected' : ''; ?>>50+ miembros</option>
                    <option value="100" <?php echo ($filtros_activos['miembros_minimo'] ?? '') === '100' ? 'selected' : ''; ?>>100+ miembros</option>
                </select>
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Ordenar por</h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value="actividad" <?php echo ($filtros_activos['ordenar'] ?? '') === 'actividad' ? 'selected' : ''; ?>>Mas activos</option>
                    <option value="miembros" <?php echo ($filtros_activos['ordenar'] ?? '') === 'miembros' ? 'selected' : ''; ?>>Mas miembros</option>
                    <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>Mas recientes</option>
                    <option value="nombre" <?php echo ($filtros_activos['ordenar'] ?? '') === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-violet-500 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-violet-600 hover:to-purple-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
