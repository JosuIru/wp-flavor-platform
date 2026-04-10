<?php
/**
 * Template: Resumen de Horas Trabajadas
 *
 * Estadisticas resumidas del empleado: horas semanales,
 * mensuales, media diaria con barras de progreso
 * y mini calendario de dias trabajados.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo_resumen = $titulo_resumen ?? 'Resumen de Horas';
$horas_esta_semana = $horas_esta_semana ?? 32;
$horas_esperadas_semana = $horas_esperadas_semana ?? 40;
$horas_este_mes = $horas_este_mes ?? 148;
$horas_esperadas_mes = $horas_esperadas_mes ?? 176;
$media_diaria_horas = $media_diaria_horas ?? '7h 24m';
$dias_trabajados_mes = $dias_trabajados_mes ?? 20;
$dias_laborables_mes = $dias_laborables_mes ?? 22;

$porcentaje_semanal = min(100, round(($horas_esta_semana / $horas_esperadas_semana) * 100));
$porcentaje_mensual = min(100, round(($horas_este_mes / $horas_esperadas_mes) * 100));
$porcentaje_dias = min(100, round(($dias_trabajados_mes / $dias_laborables_mes) * 100));

$dias_calendario = $dias_calendario ?? [
    ['dia' => 'L', 'trabajado' => true],
    ['dia' => 'M', 'trabajado' => true],
    ['dia' => 'X', 'trabajado' => true],
    ['dia' => 'J', 'trabajado' => true],
    ['dia' => 'V', 'trabajado' => false],
    ['dia' => 'S', 'trabajado' => false],
    ['dia' => 'D', 'trabajado' => false],
];
?>

<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <!-- Titulo -->
        <div class="text-center mb-10">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3">
                <?php echo esc_html($titulo_resumen); ?>
            </h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                <?php echo esc_html__('Vista general de tu actividad laboral', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Grid de estadisticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto mb-10">
            <!-- Horas esta semana -->
            <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: #64748B15;">
                        <svg class="w-6 h-6" style="color: #64748B;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-400"><?php echo esc_html__('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-3xl font-bold text-gray-800"><?php echo esc_html($horas_esta_semana); ?>h</span>
                    <span class="text-gray-400 text-sm ml-1">/ <?php echo esc_html($horas_esperadas_semana); ?>h</span>
                </div>
                <!-- Barra de progreso -->
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all duration-500" style="width: <?php echo esc_attr($porcentaje_semanal); ?>%; background: linear-gradient(90deg, #64748B, #475569);"></div>
                </div>
                <div class="text-right mt-1">
                    <span class="text-xs text-gray-400"><?php echo esc_html($porcentaje_semanal); ?>%</span>
                </div>
            </div>

            <!-- Horas este mes -->
            <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: #47556915;">
                        <svg class="w-6 h-6" style="color: #475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-400"><?php echo esc_html__('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-3xl font-bold text-gray-800"><?php echo esc_html($horas_este_mes); ?>h</span>
                    <span class="text-gray-400 text-sm ml-1">/ <?php echo esc_html($horas_esperadas_mes); ?>h</span>
                </div>
                <!-- Barra de progreso -->
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all duration-500" style="width: <?php echo esc_attr($porcentaje_mensual); ?>%; background: linear-gradient(90deg, #475569, #334155);"></div>
                </div>
                <div class="text-right mt-1">
                    <span class="text-xs text-gray-400"><?php echo esc_html($porcentaje_mensual); ?>%</span>
                </div>
            </div>

            <!-- Media diaria -->
            <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: #33415515;">
                        <svg class="w-6 h-6" style="color: #334155;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-400"><?php echo esc_html__('Media diaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-3xl font-bold text-gray-800"><?php echo esc_html($media_diaria_horas); ?></span>
                </div>
                <!-- Indicador de dias -->
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-gray-500">
                        <?php echo esc_html($dias_trabajados_mes); ?>/<?php echo esc_html($dias_laborables_mes); ?> <?php echo esc_html__('dias trabajados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Mini calendario semanal -->
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php echo esc_html__('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <span class="text-sm text-gray-400">
                        <?php echo esc_html($porcentaje_dias); ?>% <?php echo esc_html__('asistencia mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>

                <!-- Dias de la semana -->
                <div class="grid grid-cols-7 gap-3">
                    <?php foreach ($dias_calendario as $dia_semana_actual): ?>
                        <div class="text-center">
                            <div class="text-xs font-medium text-gray-400 mb-2">
                                <?php echo esc_html($dia_semana_actual['dia']); ?>
                            </div>
                            <?php if ($dia_semana_actual['trabajado']): ?>
                                <div class="w-10 h-10 mx-auto rounded-xl flex items-center justify-center transition-transform duration-300 hover:scale-110" style="background: linear-gradient(135deg, #64748B, #475569);">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 mx-auto rounded-xl bg-gray-100 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Barra de progreso general del mes -->
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600"><?php echo esc_html__('Progreso mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="text-sm font-semibold text-gray-800"><?php echo esc_html($horas_este_mes); ?>h / <?php echo esc_html($horas_esperadas_mes); ?>h</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all duration-500" style="width: <?php echo esc_attr($porcentaje_mensual); ?>%; background: linear-gradient(90deg, #64748B, #334155);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
