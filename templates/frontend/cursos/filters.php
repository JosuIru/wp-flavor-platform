<?php
/**
 * Frontend: Filtros de Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters cursos bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-purple-600 hover:text-purple-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
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
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
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
        </div>

        <!-- Precio -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox"
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
                <select name="precio_max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-purple-500">
                    <option value=""><?php echo esc_html__('Cualquier precio', 'flavor-chat-ia'); ?></option>
                    <option value="10" <?php echo ($filtros_activos['precio_max'] ?? '') === '10' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 10€', 'flavor-chat-ia'); ?></option>
                    <option value="25" <?php echo ($filtros_activos['precio_max'] ?? '') === '25' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 25€', 'flavor-chat-ia'); ?></option>
                    <option value="50" <?php echo ($filtros_activos['precio_max'] ?? '') === '50' ? 'selected' : ''; ?>><?php echo esc_html__('Hasta 50€', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>

        <!-- Modalidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Modalidad', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $modalidades = [
                    'presencial' => 'Presencial',
                    'online' => 'Online',
                    'hibrido' => 'Hibrido',
                ];
                foreach ($modalidades as $valor => $etiqueta):
                    $checked = ($filtros_activos['modalidad'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
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
        </div>

        <!-- Disponibilidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="con_plazas"
                       value="1"
                       <?php echo !empty($filtros_activos['con_plazas']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">
                    <?php echo esc_html__('Con plazas disponibles', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
