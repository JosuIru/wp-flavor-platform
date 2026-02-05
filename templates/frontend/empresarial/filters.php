<?php
/**
 * Frontend: Filtros de Empresarial
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];

$sectores_disponibles = [
    'tecnologia' => 'Tecnología',
    'consultoria' => 'Consultoría',
    'marketing' => 'Marketing',
    'diseno' => 'Diseño',
    'finanzas' => 'Finanzas',
    'salud' => 'Salud',
    'educacion' => 'Educación',
    'construccion' => 'Construcción',
    'hosteleria' => 'Hostelería',
    'logistica' => 'Logística',
];

$tamanos_empresa = [
    'freelance' => 'Freelance',
    '1-10' => '1-10 empleados',
    '11-50' => '11-50 empleados',
    '51-200' => '51-200 empleados',
    '200+' => '200+ empleados',
];

$ubicaciones = ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao', 'Málaga', 'Zaragoza'];
?>

<div class="flavor-frontend flavor-empresarial-filters">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo esc_html__('Filtrar Empresas', 'flavor-chat-ia'); ?></h2>
            <?php if (!empty($filtros_activos)) : ?>
                <a href="#" class="text-sm text-slate-600 hover:text-slate-700 font-medium">
                    <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="get" class="space-y-6">

            <!-- Sector -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Sector', 'flavor-chat-ia'); ?></h3>
                <select
                    name="sector"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent"
                >
                    <option value=""><?php echo esc_html__('Todos los sectores', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($sectores_disponibles as $valor_sector => $etiqueta_sector) : ?>
                        <option
                            value="<?php echo esc_attr($valor_sector); ?>"
                            <?php selected($filtros_activos['sector'] ?? '', $valor_sector); ?>
                        >
                            <?php echo esc_html($etiqueta_sector); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tamaño -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Tamaño', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($tamanos_empresa as $valor_tamano => $etiqueta_tamano) :
                        $tamano_seleccionado = in_array($valor_tamano, $filtros_activos['tamano'] ?? []);
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="checkbox"
                            name="tamano[]"
                            value="<?php echo esc_attr($valor_tamano); ?>"
                            <?php checked($tamano_seleccionado); ?>
                            class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_tamano); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ubicación -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></h3>
                <select
                    name="ubicacion"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent"
                >
                    <option value=""><?php echo esc_html__('Todas las ubicaciones', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($ubicaciones as $ubicacion) : ?>
                        <option
                            value="<?php echo esc_attr(sanitize_title($ubicacion)); ?>"
                            <?php selected($filtros_activos['ubicacion'] ?? '', sanitize_title($ubicacion)); ?>
                        >
                            <?php echo esc_html($ubicacion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Con ofertas de empleo -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Ofertas de empleo', 'flavor-chat-ia'); ?></h3>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input
                        type="checkbox"
                        name="con_ofertas"
                        value="1"
                        <?php checked(!empty($filtros_activos['con_ofertas'])); ?>
                        class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-400"
                    />
                    <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html__('Solo empresas con ofertas de empleo activas', 'flavor-chat-ia'); ?></span>
                </label>
            </div>

            <!-- Botón aplicar -->
            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-gray-500 to-slate-600 text-white font-semibold rounded-xl hover:from-gray-600 hover:to-slate-700 transition-all shadow-sm"
            >
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>

        </form>

    </div>
</div>
