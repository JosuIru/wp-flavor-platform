<?php
/**
 * Frontend: Filtros de Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters biblioteca bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Disponibilidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></h4>
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox"
                       name="solo_disponibles"
                       value="1"
                       <?php echo !empty($filtros_activos['solo_disponibles']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                    <?php echo esc_html__('Solo disponibles', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Genero -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Genero', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $generos = [
                    'novela' => 'Novela',
                    'ciencia_ficcion' => 'Ciencia Ficcion',
                    'fantasia' => 'Fantasia',
                    'thriller' => 'Thriller',
                    'romance' => 'Romance',
                    'historia' => 'Historia',
                    'biografia' => 'Biografia',
                    'ensayo' => 'Ensayo',
                    'infantil' => 'Infantil',
                    'juvenil' => 'Juvenil',
                ];
                foreach ($generos as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['genero'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="genero[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
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
                    'fr' => 'Frances',
                ];
                foreach ($idiomas as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['idioma'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="idioma[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>
