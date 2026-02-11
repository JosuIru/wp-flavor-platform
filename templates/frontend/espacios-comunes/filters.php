<?php
/**
 * Frontend: Filtros de Espacios Comunes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-filters espacios-comunes bg-white rounded-2xl p-5 shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
        <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-rose-600 hover:text-rose-700"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        <?php endif; ?>
    </div>

    <form method="get" class="space-y-6">
        <!-- Tipo de espacio -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Tipo de Espacio', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $tipos = [
                    'salon' => 'Salon de Actos',
                    'sala_reuniones' => 'Sala de Reuniones',
                    'aula' => 'Aula de Formacion',
                    'cocina' => 'Cocina Comunitaria',
                    'exterior' => 'Espacio Exterior',
                    'estudio' => 'Estudio de Grabacion',
                ];
                foreach ($tipos as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['tipo'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="tipo[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Capacidad -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Capacidad', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $capacidades = [
                    '1-10' => '1-10 personas',
                    '11-25' => '11-25 personas',
                    '26-50' => '26-50 personas',
                    '51-100' => '51-100 personas',
                    '100+' => 'Mas de 100',
                ];
                foreach ($capacidades as $valor => $etiqueta):
                    $checked = ($filtros_activos['capacidad'] ?? '') === $valor ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio"
                               name="capacidad"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 border-gray-300 text-rose-600 focus:ring-rose-500">
                        <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                            <?php echo esc_html($etiqueta); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Precio -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio por hora', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <input type="number"
                           name="precio_min"
                           placeholder="<?php echo esc_attr__('Min', 'flavor-chat-ia'); ?>"
                           value="<?php echo esc_attr($filtros_activos['precio_min'] ?? ''); ?>"
                           class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <span class="text-gray-400">-</span>
                    <input type="number"
                           name="precio_max"
                           placeholder="<?php echo esc_attr__('Max', 'flavor-chat-ia'); ?>"
                           value="<?php echo esc_attr($filtros_activos['precio_max'] ?? ''); ?>"
                           class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                </div>
                <!-- Presets de precio -->
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="setPrecio(0, 20)" class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-600 hover:bg-rose-100 hover:text-rose-600">
                        0-20€
                    </button>
                    <button type="button" onclick="setPrecio(20, 50)" class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-600 hover:bg-rose-100 hover:text-rose-600">
                        20-50€
                    </button>
                    <button type="button" onclick="setPrecio(50, 100)" class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-600 hover:bg-rose-100 hover:text-rose-600">
                        50-100€
                    </button>
                </div>
            </div>
        </div>

        <!-- Equipamiento -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Equipamiento', 'flavor-chat-ia'); ?></h4>
            <div class="space-y-2">
                <?php
                $equipamientos = [
                    'proyector' => 'Proyector',
                    'wifi' => 'WiFi',
                    'tv' => 'TV/Pantalla',
                    'microfono' => 'Microfono',
                    'pizarra' => 'Pizarra',
                    'cocina' => 'Cocina equipada',
                    'aire' => 'Aire acondicionado',
                    'accesible' => 'Acceso PMR',
                ];
                foreach ($equipamientos as $valor => $etiqueta):
                    $checked = in_array($valor, $filtros_activos['equipamiento'] ?? []) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox"
                               name="equipamiento[]"
                               value="<?php echo esc_attr($valor); ?>"
                               <?php echo $checked; ?>
                               class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
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
                       name="solo_disponibles"
                       value="1"
                       <?php echo !empty($filtros_activos['solo_disponibles']) ? 'checked' : ''; ?>
                       class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                <span class="text-sm text-gray-700 group-hover:text-rose-600 transition-colors">
                    <?php echo esc_html__('Solo mostrar disponibles', 'flavor-chat-ia'); ?>
                </span>
            </label>
        </div>

        <!-- Boton aplicar -->
        <button type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
            <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
        </button>
    </form>
</div>

<script>
function setPrecio(min, max) {
    document.querySelector('input[name="precio_min"]').value = min;
    document.querySelector('input[name="precio_max"]').value = max;
}
</script>
