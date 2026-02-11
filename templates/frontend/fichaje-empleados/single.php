<?php
/**
 * Frontend: Single Fichaje - Detalle de un dia de fichaje
 *
 * Muestra la linea temporal completa de entradas, salidas y pausas
 * para un dia concreto, junto con el resumen de horas en la sidebar.
 *
 * @package FlavorChatIA
 * @subpackage FichajeEmpleados
 *
 * @var array $fichaje       Datos generales del fichaje del dia (fecha, empleado, estado)
 * @var array $registros_dia Lista de registros individuales del dia (hora, tipo, nota)
 * @var array $resumen_dia   Resumen del dia (total_horas, pausas, horas_netas, estado)
 */
if (!defined('ABSPATH')) exit;
$fichaje = $fichaje ?? [];
$registros_dia = $registros_dia ?? [];
$resumen_dia = $resumen_dia ?? [];
?>

<div class="flavor-frontend flavor-fichaje-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/fichaje/')); ?>" class="hover:text-indigo-600 transition-colors"><?php echo esc_html__('Fichaje', 'flavor-chat-ia'); ?></a>
        <span><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></span>
        <span class="text-gray-700">Dia <?php echo esc_html($fichaje['fecha_formateada'] ?? ''); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Cabecera del dia -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm mb-1"><?php echo esc_html__('Detalle del dia', 'flavor-chat-ia'); ?></p>
                            <h1 class="text-2xl font-bold"><?php echo esc_html($fichaje['fecha_formateada'] ?? ''); ?></h1>
                            <p class="text-blue-100 text-sm mt-1"><?php echo esc_html($fichaje['dia_semana'] ?? ''); ?></p>
                        </div>
                        <?php
                        $estado_dia_fichaje = $fichaje['estado'] ?? 'pendiente';
                        $colores_badge_estado = [
                            'validado'  => 'bg-green-400/20 text-green-100 border-green-300/30',
                            'pendiente' => 'bg-amber-400/20 text-amber-100 border-amber-300/30',
                            'rechazado' => 'bg-red-400/20 text-red-100 border-red-300/30',
                            'incompleto' => 'bg-gray-400/20 text-gray-100 border-gray-300/30',
                        ];
                        $clase_badge_estado = $colores_badge_estado[$estado_dia_fichaje] ?? 'bg-gray-400/20 text-gray-100 border-gray-300/30';
                        ?>
                        <span class="<?php echo esc_attr($clase_badge_estado); ?> px-4 py-2 rounded-full font-medium text-sm border capitalize">
                            <?php echo esc_html($estado_dia_fichaje); ?>
                        </span>
                    </div>
                </div>

                <!-- Info rapida -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-blue-50">
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Primera entrada', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800 font-mono"><?php echo esc_html($fichaje['primera_entrada'] ?? '--:--'); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Ultima salida', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800 font-mono"><?php echo esc_html($fichaje['ultima_salida'] ?? '--:--'); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Total pausas', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($resumen_dia['total_pausas'] ?? '0m'); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Horas netas', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-indigo-600 font-bold"><?php echo esc_html($resumen_dia['horas_netas'] ?? '0h 0m'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Linea temporal de registros -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6"><?php echo esc_html__('Linea temporal del dia', 'flavor-chat-ia'); ?></h2>

                <?php if (empty($registros_dia)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-xl">
                    <div class="text-5xl mb-3">&#128337;</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2"><?php echo esc_html__('Sin registros', 'flavor-chat-ia'); ?></h3>
                    <p class="text-gray-500"><?php echo esc_html__('No hay registros de fichaje para este dia', 'flavor-chat-ia'); ?></p>
                </div>
                <?php else: ?>
                <div class="relative">
                    <!-- Linea vertical -->
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                    <div class="space-y-6">
                        <?php foreach ($registros_dia as $indice_registro => $registro_individual): ?>
                        <?php
                        $tipo_registro_dia = $registro_individual['tipo'] ?? 'entrada';
                        $iconos_tipo_registro = [
                            'entrada'     => ['icono' => '&#9654;', 'color_fondo' => 'bg-green-500', 'color_borde' => 'ring-green-100'],
                            'salida'      => ['icono' => '&#9724;', 'color_fondo' => 'bg-red-500', 'color_borde' => 'ring-red-100'],
                            'pausa'       => ['icono' => '&#9208;', 'color_fondo' => 'bg-amber-500', 'color_borde' => 'ring-amber-100'],
                            'reanudacion' => ['icono' => '&#9193;', 'color_fondo' => 'bg-blue-500', 'color_borde' => 'ring-blue-100'],
                        ];
                        $configuracion_icono = $iconos_tipo_registro[$tipo_registro_dia] ?? ['icono' => '&#9679;', 'color_fondo' => 'bg-gray-500', 'color_borde' => 'ring-gray-100'];
                        ?>
                        <div class="relative flex items-start gap-4 pl-2">
                            <!-- Marcador en la linea temporal -->
                            <div class="flex-shrink-0 w-9 h-9 <?php echo esc_attr($configuracion_icono['color_fondo']); ?> text-white rounded-full flex items-center justify-center text-sm ring-4 <?php echo esc_attr($configuracion_icono['color_borde']); ?> z-10">
                                <?php echo $configuracion_icono['icono']; ?>
                            </div>

                            <!-- Contenido del registro -->
                            <div class="flex-1 bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold text-gray-800 capitalize"><?php echo esc_html($tipo_registro_dia); ?></span>
                                    <span class="font-mono text-sm font-medium text-gray-600">
                                        <?php echo esc_html($registro_individual['hora'] ?? '--:--'); ?>
                                    </span>
                                </div>
                                <?php if (!empty($registro_individual['nota'])): ?>
                                <p class="text-sm text-gray-500 mt-1"><?php echo esc_html($registro_individual['nota']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($registro_individual['ubicacion'])): ?>
                                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                    &#128205; <?php echo esc_html($registro_individual['ubicacion']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Observaciones -->
            <?php if (!empty($fichaje['observaciones'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Observaciones', 'flavor-chat-ia'); ?></h2>
                <div class="prose prose-blue max-w-none">
                    <?php echo wp_kses_post($fichaje['observaciones']); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Resumen del dia -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white">
                <div class="text-center mb-4">
                    <p class="text-blue-100 text-sm mb-1"><?php echo esc_html__('Horas netas trabajadas', 'flavor-chat-ia'); ?></p>
                    <p class="text-4xl font-bold"><?php echo esc_html($resumen_dia['horas_netas'] ?? '0h 0m'); ?></p>
                </div>
                <div class="space-y-3 border-t border-white/20 pt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-100"><?php echo esc_html__('Horas brutas', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html($resumen_dia['horas_brutas'] ?? '0h 0m'); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-100"><?php echo esc_html__('Tiempo en pausas', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html($resumen_dia['total_pausas'] ?? '0m'); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-100"><?php echo esc_html__('Numero de pausas', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html($resumen_dia['numero_pausas'] ?? 0); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-100"><?php echo esc_html__('Registros totales', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html(count($registros_dia)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Estado del dia -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <?php
                $iconos_estado_dia = [
                    'validado'   => ['icono' => '&#9989;', 'color_fondo' => 'bg-green-100', 'color_texto' => 'text-green-700', 'etiqueta' => 'Validado'],
                    'pendiente'  => ['icono' => '&#9203;', 'color_fondo' => 'bg-amber-100', 'color_texto' => 'text-amber-700', 'etiqueta' => 'Pendiente de validacion'],
                    'rechazado'  => ['icono' => '&#10060;', 'color_fondo' => 'bg-red-100', 'color_texto' => 'text-red-700', 'etiqueta' => 'Rechazado'],
                    'incompleto' => ['icono' => '&#9888;', 'color_fondo' => 'bg-gray-100', 'color_texto' => 'text-gray-700', 'etiqueta' => 'Incompleto'],
                ];
                $configuracion_estado_dia = $iconos_estado_dia[$estado_dia_fichaje] ?? $iconos_estado_dia['pendiente'];
                ?>
                <div class="w-16 h-16 <?php echo esc_attr($configuracion_estado_dia['color_fondo']); ?> rounded-full flex items-center justify-center text-3xl mx-auto mb-3">
                    <?php echo $configuracion_estado_dia['icono']; ?>
                </div>
                <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Estado del dia', 'flavor-chat-ia'); ?></p>
                <h3 class="text-lg font-semibold <?php echo esc_attr($configuracion_estado_dia['color_texto']); ?>">
                    <?php echo esc_html($configuracion_estado_dia['etiqueta']); ?>
                </h3>

                <?php if (!empty($fichaje['validado_por'])): ?>
                <p class="text-sm text-gray-500 mt-3">
                    Validado por: <?php echo esc_html($fichaje['validado_por']); ?>
                </p>
                <?php endif; ?>

                <?php if ($estado_dia_fichaje === 'rechazado' && !empty($fichaje['motivo_rechazo'])): ?>
                <div class="mt-3 bg-red-50 text-red-700 text-sm p-3 rounded-lg text-left">
                    <p class="font-medium mb-1"><?php echo esc_html__('Motivo del rechazo:', 'flavor-chat-ia'); ?></p>
                    <p><?php echo esc_html($fichaje['motivo_rechazo']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <a href="<?php echo esc_url(home_url('/fichaje/solicitar-correccion/?fecha=' . ($fichaje['fecha'] ?? ''))); ?>"
                       class="block w-full text-center bg-blue-50 text-blue-700 py-3 px-4 rounded-xl font-medium hover:bg-blue-100 transition-colors text-sm">
                        <?php echo esc_html__('Solicitar correccion', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/fichaje/')); ?>"
                       class="block w-full text-center bg-gray-50 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-100 transition-colors text-sm">
                        <?php echo esc_html__('Volver al panel', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>

            <!-- Dias cercanos -->
            <?php if (!empty($fichaje['dia_anterior']) || !empty($fichaje['dia_siguiente'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Navegacion', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php if (!empty($fichaje['dia_anterior'])): ?>
                    <a href="<?php echo esc_url($fichaje['dia_anterior']['url'] ?? '#'); ?>" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <span class="text-gray-400"><?php echo esc_html__('&larr;', 'flavor-chat-ia'); ?></span>
                        <div>
                            <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($fichaje['dia_anterior']['fecha'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($fichaje['dia_anterior']['horas'] ?? '0h'); ?> trabajadas</p>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($fichaje['dia_siguiente'])): ?>
                    <a href="<?php echo esc_url($fichaje['dia_siguiente']['url'] ?? '#'); ?>" class="flex items-center justify-end gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-right">
                        <div>
                            <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($fichaje['dia_siguiente']['fecha'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($fichaje['dia_siguiente']['horas'] ?? '0h'); ?> trabajadas</p>
                        </div>
                        <span class="text-gray-400"><?php echo esc_html__('&rarr;', 'flavor-chat-ia'); ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
