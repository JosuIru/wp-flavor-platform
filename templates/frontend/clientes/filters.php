<?php
/**
 * Frontend: Filtros de Clientes
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];

$estados_cliente = [
    'activo' => 'Activo',
    'inactivo' => 'Inactivo',
    'potencial' => 'Potencial',
];

$sectores = [
    'tecnologia' => 'Tecnología',
    'consultoria' => 'Consultoría',
    'marketing' => 'Marketing',
    'diseno' => 'Diseño',
    'finanzas' => 'Finanzas',
    'salud' => 'Salud',
    'educacion' => 'Educación',
    'construccion' => 'Construcción',
];

$periodos_alta = [
    'ultimo_mes' => 'Último mes',
    'ultimos_3_meses' => 'Últimos 3 meses',
    'ultimos_6_meses' => 'Últimos 6 meses',
    'ultimo_anio' => 'Último año',
    'mas_de_un_anio' => 'Más de un año',
];

$periodos_interaccion = [
    'hoy' => 'Hoy',
    'ultima_semana' => 'Última semana',
    'ultimo_mes' => 'Último mes',
    'ultimos_3_meses' => 'Últimos 3 meses',
    'sin_interaccion' => 'Sin interacción reciente',
];
?>

<div class="flavor-frontend flavor-clientes-filters">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo esc_html__('Filtrar Clientes', 'flavor-chat-ia'); ?></h2>
            <?php if (!empty($filtros_activos)) : ?>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="get" class="space-y-6">

            <!-- Estado -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($estados_cliente as $valor_estado => $etiqueta_estado) :
                        $estado_seleccionado = in_array($valor_estado, $filtros_activos['estado'] ?? []);
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="checkbox"
                            name="estado[]"
                            value="<?php echo esc_attr($valor_estado); ?>"
                            <?php checked($estado_seleccionado); ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_estado); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sector -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Sector', 'flavor-chat-ia'); ?></h3>
                <select
                    name="sector"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                >
                    <option value=""><?php echo esc_html__('Todos los sectores', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($sectores as $valor_sector => $etiqueta_sector) : ?>
                        <option
                            value="<?php echo esc_attr($valor_sector); ?>"
                            <?php selected($filtros_activos['sector'] ?? '', $valor_sector); ?>
                        >
                            <?php echo esc_html($etiqueta_sector); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Fecha de alta -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Fecha de alta', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($periodos_alta as $valor_periodo_alta => $etiqueta_periodo_alta) :
                        $alta_seleccionada = ($filtros_activos['fecha_alta'] ?? '') === $valor_periodo_alta;
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="radio"
                            name="fecha_alta"
                            value="<?php echo esc_attr($valor_periodo_alta); ?>"
                            <?php checked($alta_seleccionada); ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_periodo_alta); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Última interacción -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Última interacción', 'flavor-chat-ia'); ?></h3>
                <select
                    name="ultima_interaccion"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                >
                    <option value=""><?php echo esc_html__('Cualquier momento', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($periodos_interaccion as $valor_interaccion => $etiqueta_interaccion) : ?>
                        <option
                            value="<?php echo esc_attr($valor_interaccion); ?>"
                            <?php selected($filtros_activos['ultima_interaccion'] ?? '', $valor_interaccion); ?>
                        >
                            <?php echo esc_html($etiqueta_interaccion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botón aplicar -->
            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-slate-500 to-blue-600 text-white font-semibold rounded-xl hover:from-slate-600 hover:to-blue-700 transition-all shadow-sm"
            >
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>

        </form>

    </div>
</div>
