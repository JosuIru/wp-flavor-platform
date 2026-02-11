<?php
/**
 * Frontend: Filtros de Podcasts
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters podcast bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-teal-600 hover:text-teal-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Categoria -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $categorias = [
                    'actualidad' => 'Actualidad local',
                    'entrevistas' => 'Entrevistas',
                    'historia' => 'Historia del barrio',
                    'cultura' => 'Cultura',
                    'deporte' => 'Deporte',
                    'gastronomia' => 'Gastronomia',
                    'naturaleza' => 'Naturaleza',
                    'educacion' => 'Educacion',
                ];
                foreach ($categorias as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['categoria'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="categoria[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-gray-700 group-hover:text-teal-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Duracion -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Duracion episodios', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $duraciones = [
                    'corto' => 'Menos de 15 min',
                    'medio' => '15-30 min',
                    'largo' => '30-60 min',
                    'extenso' => 'Mas de 60 min',
                ];
                foreach ($duraciones as $valor => $etiqueta):
                    $checked = ($filtros_activos['duracion'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="duracion"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-gray-700 group-hover:text-teal-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Idioma -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Idioma', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $idiomas = [
                    'es' => 'Espanol',
                    'eu' => 'Euskera',
                    'en' => 'Ingles',
                ];
                foreach ($idiomas as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['idioma'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="idioma[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-gray-700 group-hover:text-teal-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #14b8a6 0%, #10b981 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
