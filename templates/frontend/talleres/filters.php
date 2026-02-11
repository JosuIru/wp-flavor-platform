<?php
/**
 * Frontend: Filtros de Talleres
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-talleres-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-violet-600 hover:text-violet-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Categoria -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $categorias_talleres = [
                        'arte' => '🎨 Arte',
                        'cocina' => '🍳 Cocina',
                        'tecnologia' => '💻 Tecnologia',
                        'manualidades' => '✂️ Manualidades',
                        'musica' => '🎵 Musica',
                        'fotografia' => '📸 Fotografia',
                        'idiomas' => '🌍 Idiomas',
                        'bienestar' => '🧘 Bienestar',
                    ];
                    foreach ($categorias_talleres as $valor_cat_taller => $etiqueta_cat_taller):
                        $marcado_cat_taller = in_array($valor_cat_taller, $filtros_activos['categorias'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat_taller); ?>"
                               <?php echo $marcado_cat_taller; ?>
                               class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 group-hover:text-violet-600 transition-colors">
                            <?php echo esc_html($etiqueta_cat_taller); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nivel -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Nivel', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $niveles_taller = [
                        '' => 'Todos los niveles',
                        'principiante' => '🟢 Principiante',
                        'intermedio' => '🟡 Intermedio',
                        'avanzado' => '🔴 Avanzado',
                    ];
                    foreach ($niveles_taller as $valor_nivel => $etiqueta_nivel):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="nivel" value="<?php echo esc_attr($valor_nivel); ?>"
                               <?php echo ($filtros_activos['nivel'] ?? '') === $valor_nivel ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-600 border-gray-300 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 group-hover:text-violet-600 transition-colors">
                            <?php echo esc_html($etiqueta_nivel); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Precio -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $opciones_precio_taller = [
                        '' => 'Todos',
                        'gratis' => '🆓 Gratis',
                        'pago' => '💰 De pago',
                    ];
                    foreach ($opciones_precio_taller as $valor_precio_taller => $etiqueta_precio_taller):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="precio" value="<?php echo esc_attr($valor_precio_taller); ?>"
                               <?php echo ($filtros_activos['precio'] ?? '') === $valor_precio_taller ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-600 border-gray-300 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 group-hover:text-violet-600 transition-colors">
                            <?php echo esc_html($etiqueta_precio_taller); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Formato -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Formato', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $formatos_taller = [
                        '' => 'Todos',
                        'presencial' => '📍 Presencial',
                        'online' => '💻 Online',
                    ];
                    foreach ($formatos_taller as $valor_formato_taller => $etiqueta_formato_taller):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="formato" value="<?php echo esc_attr($valor_formato_taller); ?>"
                               <?php echo ($filtros_activos['formato'] ?? '') === $valor_formato_taller ? 'checked' : ''; ?>
                               class="w-4 h-4 text-violet-600 border-gray-300 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 group-hover:text-violet-600 transition-colors">
                            <?php echo esc_html($etiqueta_formato_taller); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dia de la semana -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Dia de la semana', 'flavor-chat-ia'); ?></h4>
                <div class="space-y-2">
                    <?php
                    $dias_semana_taller = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miercoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sabado',
                        'domingo' => 'Domingo',
                    ];
                    foreach ($dias_semana_taller as $valor_dia => $etiqueta_dia):
                        $marcado_dia = in_array($valor_dia, $filtros_activos['dia_semana'] ?? []) ? 'checked' : '';
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="dia_semana[]" value="<?php echo esc_attr($valor_dia); ?>"
                               <?php echo $marcado_dia; ?>
                               class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 group-hover:text-violet-600 transition-colors">
                            <?php echo esc_html($etiqueta_dia); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-purple-500 to-violet-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-purple-600 hover:to-violet-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
