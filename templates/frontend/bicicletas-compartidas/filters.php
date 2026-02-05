<?php
/**
 * Frontend: Filtros de Bicicletas Compartidas
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];

$opciones_disponibilidad = [
    'con_bicis' => 'Con bicicletas disponibles',
    'con_huecos' => 'Con huecos para devolver',
];

$tipos_bicicleta = [
    'normal' => 'Normal',
    'electrica' => 'Eléctrica',
    'cargo' => 'Cargo',
];

$rangos_distancia = [
    '500' => 'Menos de 500m',
    '1000' => 'Menos de 1 km',
    '2000' => 'Menos de 2 km',
    '5000' => 'Menos de 5 km',
    'cualquiera' => 'Cualquier distancia',
];

$estados_estacion = [
    'operativa' => 'Operativa',
    'mantenimiento' => 'En mantenimiento',
];
?>

<div class="flavor-frontend flavor-bicicletas-filters">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo esc_html__('Filtrar Estaciones', 'flavor-chat-ia'); ?></h2>
            <?php if (!empty($filtros_activos)) : ?>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="get" class="space-y-6">

            <!-- Disponibilidad -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($opciones_disponibilidad as $valor_disponibilidad => $etiqueta_disponibilidad) :
                        $disponibilidad_seleccionada = in_array($valor_disponibilidad, $filtros_activos['disponibilidad'] ?? []);
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="checkbox"
                            name="disponibilidad[]"
                            value="<?php echo esc_attr($valor_disponibilidad); ?>"
                            <?php checked($disponibilidad_seleccionada); ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_disponibilidad); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tipo de bicicleta -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Tipo de bicicleta', 'flavor-chat-ia'); ?></h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($tipos_bicicleta as $valor_tipo_bici => $etiqueta_tipo_bici) :
                        $tipo_activo = in_array($valor_tipo_bici, $filtros_activos['tipo_bici'] ?? []);
                        $clase_tipo_bici = $tipo_activo ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700';
                    ?>
                    <label class="cursor-pointer">
                        <input type="checkbox" name="tipo_bici[]" value="<?php echo esc_attr($valor_tipo_bici); ?>" <?php checked($tipo_activo); ?> class="sr-only peer" />
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors <?php echo esc_attr($clase_tipo_bici); ?> peer-checked:bg-blue-600 peer-checked:text-white">
                            <?php
                            $icono_tipo_bici = match($valor_tipo_bici) {
                                'electrica' => 'M13 10V3L4 14h7v7l9-11h-7z',
                                'cargo' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                                default => 'M12 8v4l3 3',
                            };
                            ?>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($icono_tipo_bici); ?>"/></svg>
                            <?php echo esc_html($etiqueta_tipo_bici); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Distancia -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Distancia', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($rangos_distancia as $valor_distancia => $etiqueta_distancia) :
                        $distancia_seleccionada = ($filtros_activos['distancia'] ?? '') === $valor_distancia;
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="radio"
                            name="distancia"
                            value="<?php echo esc_attr($valor_distancia); ?>"
                            <?php checked($distancia_seleccionada); ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_distancia); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Estado de la estación -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Estado de la estación', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($estados_estacion as $valor_estado_estacion => $etiqueta_estado_estacion) :
                        $estado_estacion_seleccionado = in_array($valor_estado_estacion, $filtros_activos['estado'] ?? []);
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="checkbox"
                            name="estado[]"
                            value="<?php echo esc_attr($valor_estado_estacion); ?>"
                            <?php checked($estado_estacion_seleccionado); ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_estado_estacion); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botón aplicar -->
            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-blue-500 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-blue-800 transition-all shadow-sm"
            >
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>

        </form>

    </div>
</div>
