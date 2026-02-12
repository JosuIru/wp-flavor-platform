<?php
/**
 * Frontend: Filtros de Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
$unique_curso_filter_id = wp_unique_id('curso_filter_');
?>

<div class="flavor-filters cursos bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?"
               class="text-sm text-purple-600 hover:text-purple-700"
               aria-label="<?php esc_attr_e('Limpiar todos los filtros aplicados', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6" role="search" aria-label="<?php esc_attr_e('Filtros de cursos', 'flavor-chat-ia'); ?>">
        <!-- Categoria -->
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></legend>
            <div class="space-y-2" role="group" aria-label="<?php esc_attr_e('Categorías de cursos', 'flavor-chat-ia'); ?>">
                <?php
                $categorias = [
                    'manualidades' => 'Manualidades',
                    'cocina' => 'Cocina',
                    'idiomas' => 'Idiomas',
                    'informatica' => 'Informatica',
                    'arte' => 'Arte y Pintura',
                    'musica' => 'Musica',
                    'deporte' => 'Deporte y Salud',
                    'jardineria' => 'Jardineria',
                    'fotografia' => 'Fotografia',
                ];
                foreach ($categorias as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['categoria'] ?? []) ? 'checked' : '';
                ?>
                    <label for="<?php echo esc_attr($unique_curso_filter_id); ?>_cat_<?php echo esc_attr($valor); ?>" class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               id="<?php echo esc_attr($unique_curso_filter_id); ?>_cat_<?php echo esc_attr($valor); ?>"
                               name="categoria[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Precio -->
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></legend>
            <div class="space-y-2">
                <label for="<?php echo esc_attr($unique_curso_filter_id); ?>_gratuitos" class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox"
                           id="<?php echo esc_attr($unique_curso_filter_id); ?>_gratuitos"
                           name="gratuitos"
                           value="1"
                           <?php echo !empty($filtros_activos['gratuitos']) ? 'checked' : ''; ?>
                           class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                        <?php echo esc_html__('Solo gratuitos', 'flavor-chat-ia'); ?>
                    </span>
                </label>
            </div>
            <div class="mt-3">
                <label for="<?php echo esc_attr($unique_curso_filter_id); ?>_precio_max" class="sr-only"><?php echo esc_html__('Precio máximo', 'flavor-chat-ia'); ?></label>
                <select id="<?php echo esc_attr($unique_curso_filter_id); ?>_precio_max"
                        name="precio_max"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value=""><?php echo esc_html__('Cualquier precio', 'flavor-chat-ia'); ?></option>
                    <option value="10" <?php echo ($filtros_activos['precio_max'] ?? '') === '10' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 10€', 'flavor-chat-ia'); ?></option>
                    <option value="25" <?php echo ($filtros_activos['precio_max'] ?? '') === '25' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 25€', 'flavor-chat-ia'); ?></option>
                    <option value="50" <?php echo ($filtros_activos['precio_max'] ?? '') === '50' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 50€', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </fieldset>

        <!-- Modalidad -->
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Modalidad', 'flavor-chat-ia'); ?></legend>
            <div class="space-y-2" role="radiogroup" aria-label="<?php esc_attr_e('Modalidad del curso', 'flavor-chat-ia'); ?>">
                <?php
                $modalidades = [
                    'presencial' => 'Presencial',
                    'online' => 'Online',
                    'hibrido' => 'Hibrido',
                ];
                foreach ($modalidades as $valor => $etiqueta):
                    $checked = ($filtros_activos['modalidad'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label for="<?php echo esc_attr($unique_curso_filter_id); ?>_mod_<?php echo esc_attr($valor); ?>" class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               id="<?php echo esc_attr($unique_curso_filter_id); ?>_mod_<?php echo esc_attr($valor); ?>"
                               name="modalidad"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Disponibilidad -->
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></legend>
            <label for="<?php echo esc_attr($unique_curso_filter_id); ?>_con_plazas" class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       id="<?php echo esc_attr($unique_curso_filter_id); ?>_con_plazas"
                       name="con_plazas"
                       value="1"
                       <?php echo !empty($filtros_activos['con_plazas']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                    <?php echo esc_html__('Con plazas disponibles', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </fieldset>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);"
                aria-label="<?php esc_attr_e('Aplicar filtros de búsqueda', 'flavor-chat-ia'); ?>">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
