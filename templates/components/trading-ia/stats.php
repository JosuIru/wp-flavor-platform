<?php
/**
 * Template: Trading IA Stats Dashboard
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_stats = $titulo_stats ?? 'Panel de Rendimiento';
$rendimiento_mensual = $rendimiento_mensual ?? '+12.4%';
$operaciones_activas = $operaciones_activas ?? 8;
$win_rate = $win_rate ?? '73.2%';
$drawdown_maximo = $drawdown_maximo ?? '-4.8%';

$datos_barras_mensuales = $datos_barras_mensuales ?? [
    ['mes' => 'Ene', 'valor' => 65],
    ['mes' => 'Feb', 'valor' => 78],
    ['mes' => 'Mar', 'valor' => 45],
    ['mes' => 'Abr', 'valor' => 82],
    ['mes' => 'May', 'valor' => 90],
    ['mes' => 'Jun', 'valor' => 70],
    ['mes' => 'Jul', 'valor' => 88],
    ['mes' => 'Ago', 'valor' => 55],
    ['mes' => 'Sep', 'valor' => 95],
    ['mes' => 'Oct', 'valor' => 75],
    ['mes' => 'Nov', 'valor' => 85],
    ['mes' => 'Dic', 'valor' => 92],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-950">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-3"><?php echo esc_html($titulo_stats); ?></h2>
            <p class="text-gray-400 text-lg"><?php echo esc_html__('Estadisticas en tiempo real de las operaciones de la IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <!-- KPIs principales -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-4xl mx-auto mb-10">
            <div class="p-5 rounded-xl bg-gray-800 border border-gray-700">
                <p class="text-sm text-gray-400 mb-1"><?php echo esc_html__('Rendimiento Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p class="text-2xl lg:text-3xl font-bold text-green-400"><?php echo esc_html($rendimiento_mensual); ?></p>
            </div>
            <div class="p-5 rounded-xl bg-gray-800 border border-gray-700">
                <p class="text-sm text-gray-400 mb-1"><?php echo esc_html__('Operaciones Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p class="text-2xl lg:text-3xl font-bold text-cyan-400"><?php echo esc_html($operaciones_activas); ?></p>
            </div>
            <div class="p-5 rounded-xl bg-gray-800 border border-gray-700">
                <p class="text-sm text-gray-400 mb-1"><?php echo esc_html__('Win Rate', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p class="text-2xl lg:text-3xl font-bold text-green-400"><?php echo esc_html($win_rate); ?></p>
            </div>
            <div class="p-5 rounded-xl bg-gray-800 border border-gray-700">
                <p class="text-sm text-gray-400 mb-1"><?php echo esc_html__('Drawdown Maximo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p class="text-2xl lg:text-3xl font-bold text-red-400"><?php echo esc_html($drawdown_maximo); ?></p>
            </div>
        </div>

        <!-- Grafico de barras CSS -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white"><?php echo esc_html__('Rendimiento Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <span class="text-sm text-gray-400"><?php echo esc_html__('Ultimos 12 meses', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <!-- Barras -->
                <div class="flex items-end gap-2 h-48">
                    <?php foreach ($datos_barras_mensuales as $barra_mensual) : ?>
                        <?php
                        $altura_porcentaje = max(5, min(100, (int) $barra_mensual['valor']));
                        $color_barra = ($barra_mensual['valor'] >= 70) ? 'bg-cyan-500' : (($barra_mensual['valor'] >= 50) ? 'bg-cyan-600' : 'bg-cyan-800');
                        ?>
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-[10px] text-gray-400"><?php echo esc_html($barra_mensual['valor']); ?>%</span>
                            <div class="w-full rounded-t-md <?php echo esc_attr($color_barra); ?> transition-all duration-500 hover:opacity-80" style="height: <?php echo esc_attr($altura_porcentaje); ?>%;"></div>
                            <span class="text-[10px] text-gray-500 mt-1"><?php echo esc_html($barra_mensual['mes']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Lineas de referencia -->
                <div class="flex justify-between mt-4 pt-4 border-t border-gray-700">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm bg-cyan-500"></div>
                        <span class="text-xs text-gray-400"><?php echo esc_html__('Alto rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm bg-cyan-600"></div>
                        <span class="text-xs text-gray-400"><?php echo esc_html__('Rendimiento medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm bg-cyan-800"></div>
                        <span class="text-xs text-gray-400"><?php echo esc_html__('Bajo rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
