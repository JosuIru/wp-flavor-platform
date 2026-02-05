<?php
/**
 * Frontend: Filtros de Socios
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$filtros_activos = $filtros_activos ?? [];

$niveles_membresia = [
    'basico' => 'Básico',
    'premium' => 'Premium',
    'pro' => 'Pro',
];

$barrios = ['Centro', 'Norte', 'Sur', 'Este', 'Oeste', 'Ensanche', 'Casco Antiguo'];

$intereses_disponibles = [
    'tecnologia' => 'Tecnología',
    'marketing' => 'Marketing',
    'finanzas' => 'Finanzas',
    'diseno' => 'Diseño',
    'emprendimiento' => 'Emprendimiento',
    'arte' => 'Arte',
    'networking' => 'Networking',
    'fotografia' => 'Fotografía',
];

$periodos_actividad = [
    'ultima_semana' => 'Última semana',
    'ultimo_mes' => 'Último mes',
    'ultimos_3_meses' => 'Últimos 3 meses',
    'ultimo_anio' => 'Último año',
];
?>

<div class="flavor-frontend flavor-socios-filters">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo esc_html__('Filtrar Socios', 'flavor-chat-ia'); ?></h2>
            <?php if (!empty($filtros_activos)) : ?>
                <a href="/socios/" class="text-sm text-rose-600 hover:text-rose-700 font-medium">
                    <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="get" class="space-y-6">

            <!-- Nivel de membresía -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Nivel de membresía', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($niveles_membresia as $valor_nivel => $etiqueta_nivel) :
                        $nivel_seleccionado = in_array($valor_nivel, $filtros_activos['nivel'] ?? []);
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="checkbox"
                            name="nivel[]"
                            value="<?php echo esc_attr($valor_nivel); ?>"
                            <?php checked($nivel_seleccionado); ?>
                            class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_nivel); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Barrio -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Barrio', 'flavor-chat-ia'); ?></h3>
                <select
                    name="barrio"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent"
                >
                    <option value=""><?php echo esc_html__('Todos los barrios', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($barrios as $barrio) : ?>
                        <option
                            value="<?php echo esc_attr(sanitize_title($barrio)); ?>"
                            <?php selected($filtros_activos['barrio'] ?? '', sanitize_title($barrio)); ?>
                        >
                            <?php echo esc_html($barrio); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Intereses -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Intereses', 'flavor-chat-ia'); ?></h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($intereses_disponibles as $valor_interes => $etiqueta_interes) :
                        $interes_activo = in_array($valor_interes, $filtros_activos['intereses'] ?? []);
                        $clase_interes = $interes_activo ? 'bg-rose-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-rose-50 hover:text-rose-700';
                    ?>
                    <label class="cursor-pointer">
                        <input type="checkbox" name="intereses[]" value="<?php echo esc_attr($valor_interes); ?>" <?php checked($interes_activo); ?> class="sr-only peer" />
                        <span class="inline-block px-3 py-1.5 rounded-full text-sm font-medium transition-colors <?php echo esc_attr($clase_interes); ?> peer-checked:bg-rose-500 peer-checked:text-white">
                            <?php echo esc_html($etiqueta_interes); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activos recientemente -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html__('Activos recientemente', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($periodos_actividad as $valor_periodo => $etiqueta_periodo) :
                        $periodo_seleccionado = ($filtros_activos['actividad'] ?? '') === $valor_periodo;
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input
                            type="radio"
                            name="actividad"
                            value="<?php echo esc_attr($valor_periodo); ?>"
                            <?php checked($periodo_seleccionado); ?>
                            class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-400"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-900"><?php echo esc_html($etiqueta_periodo); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botón aplicar -->
            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-semibold rounded-xl hover:from-rose-600 hover:to-pink-700 transition-all shadow-sm"
            >
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>

        </form>

    </div>
</div>
