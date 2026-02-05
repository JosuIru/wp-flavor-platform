<?php
/**
 * Template: Indicadores Clave de Transparencia
 *
 * Dashboard con tarjetas de indicadores economicos,
 * sociales y medioambientales con tendencia y comparacion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$indicadores_lista = [
    [
        'categoria'    => 'Economicos',
        'color_borde'  => 'border-teal-500',
        'color_fondo'  => 'bg-teal-50',
        'color_texto'  => 'text-teal-700',
        'items'        => [
            ['nombre' => 'Deuda publica per capita',  'valor' => '342',      'unidad' => 'EUR',  'tendencia' => 'baja',  'variacion' => '-8.2%'],
            ['nombre' => 'Ingresos fiscales',         'valor' => '12.4M',    'unidad' => 'EUR',  'tendencia' => 'alta',  'variacion' => '+3.1%'],
            ['nombre' => 'Ejecucion presupuestaria',  'valor' => '87',       'unidad' => '%',    'tendencia' => 'alta',  'variacion' => '+5.0%'],
        ],
    ],
    [
        'categoria'    => 'Sociales',
        'color_borde'  => 'border-cyan-500',
        'color_fondo'  => 'bg-cyan-50',
        'color_texto'  => 'text-cyan-700',
        'items'        => [
            ['nombre' => 'Tasa de desempleo',         'valor' => '9.8',      'unidad' => '%',    'tendencia' => 'baja',  'variacion' => '-1.2%'],
            ['nombre' => 'Plazas de guarderia',       'valor' => '1.250',    'unidad' => '',     'tendencia' => 'alta',  'variacion' => '+150'],
            ['nombre' => 'Satisfaccion ciudadana',    'valor' => '7.6',      'unidad' => '/10',  'tendencia' => 'alta',  'variacion' => '+0.4'],
        ],
    ],
    [
        'categoria'    => 'Medioambientales',
        'color_borde'  => 'border-emerald-500',
        'color_fondo'  => 'bg-emerald-50',
        'color_texto'  => 'text-emerald-700',
        'items'        => [
            ['nombre' => 'Zonas verdes per capita',   'valor' => '14.2',     'unidad' => 'm2',   'tendencia' => 'alta',  'variacion' => '+1.8'],
            ['nombre' => 'Reciclaje sobre residuos',  'valor' => '62',       'unidad' => '%',    'tendencia' => 'alta',  'variacion' => '+4.5%'],
            ['nombre' => 'Consumo energetico mpal.',  'valor' => '3.2M',     'unidad' => 'kWh',  'tendencia' => 'baja',  'variacion' => '-12%'],
        ],
    ],
];
?>

<section class="flavor-component flavor-section py-16 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Indicadores Clave'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Principales metricas de gestion municipal actualizadas periodicamente.'); ?>
            </p>
            <div class="w-20 h-1 bg-teal-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Categorias de indicadores -->
        <div class="space-y-10">
            <?php foreach ($indicadores_lista as $grupo_indicadores): ?>
                <!-- Categoria -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-3 h-3 rounded-full <?php echo esc_attr(str_replace('border-', 'bg-', $grupo_indicadores['color_borde'])); ?> mr-3"></span>
                        <?php echo esc_html($grupo_indicadores['categoria']); ?>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($grupo_indicadores['items'] as $indicador_item): ?>
                            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 <?php echo esc_attr($grupo_indicadores['color_borde']); ?> hover:shadow-lg transition duration-300">
                                <!-- Nombre del indicador -->
                                <div class="text-sm text-gray-500 mb-2">
                                    <?php echo esc_html($indicador_item['nombre']); ?>
                                </div>

                                <!-- Valor principal -->
                                <div class="flex items-baseline space-x-1 mb-3">
                                    <span class="text-3xl font-bold text-gray-900">
                                        <?php echo esc_html($indicador_item['valor']); ?>
                                    </span>
                                    <span class="text-lg text-gray-400">
                                        <?php echo esc_html($indicador_item['unidad']); ?>
                                    </span>
                                </div>

                                <!-- Tendencia -->
                                <div class="flex items-center">
                                    <?php if ($indicador_item['tendencia'] === 'alta'): ?>
                                        <svg class="w-5 h-5 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm font-medium text-green-600">
                                            <?php echo esc_html($indicador_item['variacion']); ?>
                                        </span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm font-medium text-red-600">
                                            <?php echo esc_html($indicador_item['variacion']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-400 ml-2">
                                        <?php echo esc_html__('vs. periodo anterior', 'flavor-chat-ia'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
