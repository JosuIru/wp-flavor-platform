<?php
/**
 * Template: Facturas Estadisticas
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_estadisticas = $titulo_estadisticas ?? 'Resumen de Facturacion';
$total_facturado = $total_facturado ?? '45.600';
$pendiente_cobro = $pendiente_cobro ?? '8.350';
$facturas_este_mes = $facturas_este_mes ?? 12;
$tasa_cobro = $tasa_cobro ?? 87;

$tarjetas_estadisticas = $tarjetas_estadisticas ?? [
    [
        'titulo' => 'Total Facturado',
        'valor'  => $total_facturado . '&euro;',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'  => '#14B8A6',
        'fondo'  => '#F0FDFA',
    ],
    [
        'titulo' => 'Pendiente de Cobro',
        'valor'  => $pendiente_cobro . '&euro;',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'  => '#F59E0B',
        'fondo'  => '#FFFBEB',
    ],
    [
        'titulo' => 'Facturas este Mes',
        'valor'  => (string) $facturas_este_mes,
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color'  => '#3B82F6',
        'fondo'  => '#EFF6FF',
    ],
    [
        'titulo' => 'Tasa de Cobro',
        'valor'  => $tasa_cobro . '%',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'color'  => '#10B981',
        'fondo'  => '#ECFDF5',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-10">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3">
                    <?php echo esc_html($titulo_estadisticas); ?>
                </h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    <?php echo esc_html__('Visualiza el estado de tu facturacion de un vistazo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Tarjetas de Estadisticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <?php foreach ($tarjetas_estadisticas as $tarjeta_item) : ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110"
                                 style="background: <?php echo esc_attr($tarjeta_item['fondo']); ?>;">
                                <svg class="w-6 h-6" style="color: <?php echo esc_attr($tarjeta_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $tarjeta_item['icono']; ?>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-800 mb-1">
                            <?php echo $tarjeta_item['valor']; ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?php echo esc_html($tarjeta_item['titulo']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Barra de Progreso Tasa de Cobro -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 max-w-2xl mx-auto">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">
                        <?php echo esc_html__('Progreso de cobro mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <span class="text-sm font-bold" style="color: #10B981;">
                        <?php echo esc_html($tasa_cobro); ?>%
                    </span>
                </div>
                <div class="w-full h-3 rounded-full" style="background: #ECFDF5;">
                    <div class="h-3 rounded-full transition-all duration-500"
                         style="width: <?php echo esc_attr($tasa_cobro); ?>%; background: linear-gradient(90deg, #14B8A6, #10B981);"></div>
                </div>
                <div class="flex items-center justify-between mt-3 text-xs text-gray-400">
                    <span><?php echo esc_html__('Cobrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>: <?php echo esc_html($total_facturado); ?>&euro;</span>
                    <span><?php echo esc_html__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>: <?php echo esc_html($pendiente_cobro); ?>&euro;</span>
                </div>
            </div>
        </div>
    </div>
</section>
