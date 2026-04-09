<?php
/**
 * Template: Resultados Presupuestos Participativos
 *
 * Dashboard de resultados con tarjetas resumen y listado
 * de proyectos aprobados con estado y porcentaje de avance.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$proyectos_aprobados = [
    [
        'titulo'       => 'Mejora del alumbrado publico LED',
        'presupuesto'  => 120000,
        'estado'       => 'Completado',
        'completado'   => 100,
        'color_estado' => 'text-green-600',
    ],
    [
        'titulo'       => 'Ampliacion de zonas peatonales centro',
        'presupuesto'  => 350000,
        'estado'       => 'En ejecucion',
        'completado'   => 65,
        'color_estado' => 'text-amber-600',
    ],
    [
        'titulo'       => 'Placas solares en edificios municipales',
        'presupuesto'  => 280000,
        'estado'       => 'En ejecucion',
        'completado'   => 40,
        'color_estado' => 'text-amber-600',
    ],
    [
        'titulo'       => 'Parque canino vallado en zona norte',
        'presupuesto'  => 45000,
        'estado'       => 'Completado',
        'completado'   => 100,
        'color_estado' => 'text-green-600',
    ],
    [
        'titulo'       => 'Digitalizacion de tramites municipales',
        'presupuesto'  => 95000,
        'estado'       => 'En licitacion',
        'completado'   => 15,
        'color_estado' => 'text-blue-600',
    ],
];
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Resultados y Seguimiento'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Consulta el estado de los proyectos aprobados y su avance en tiempo real.'); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white rounded-xl shadow-md p-6 text-center border-t-4 border-amber-500">
                <div class="text-4xl font-bold text-amber-600 mb-2">
                    <?php echo esc_html($total_presupuesto ?? '2.500.000'); ?>&euro;
                </div>
                <div class="text-gray-600"><?php echo esc_html__('Presupuesto total asignado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center border-t-4 border-green-500">
                <div class="text-4xl font-bold text-green-600 mb-2">
                    <?php echo esc_html($total_completados ?? '23'); ?>
                </div>
                <div class="text-gray-600"><?php echo esc_html__('Proyectos completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center border-t-4 border-blue-500">
                <div class="text-4xl font-bold text-blue-600 mb-2">
                    <?php echo esc_html($participacion_porcentaje ?? '78%'); ?>
                </div>
                <div class="text-gray-600"><?php echo esc_html__('Participacion ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <!-- Tabla de proyectos aprobados -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html__('Proyectos Aprobados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>

            <!-- Cabecera tabla (desktop) -->
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 text-sm font-semibold text-gray-500">
                <div class="col-span-4"><?php echo esc_html__('Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="col-span-2 text-right"><?php echo esc_html__('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="col-span-2 text-center"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="col-span-4"><?php echo esc_html__('Avance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>

            <!-- Filas de proyectos -->
            <?php foreach ($proyectos_aprobados as $indice_proyecto => $proyecto_aprobado): ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 px-6 py-4 border-b border-gray-50 hover:bg-amber-50 transition duration-200 items-center">
                    <!-- Nombre del proyecto -->
                    <div class="md:col-span-4">
                        <span class="font-medium text-gray-900"><?php echo esc_html($proyecto_aprobado['titulo']); ?></span>
                    </div>

                    <!-- Presupuesto -->
                    <div class="md:col-span-2 md:text-right">
                        <span class="text-sm font-semibold text-gray-700">
                            <?php echo esc_html(number_format($proyecto_aprobado['presupuesto'], 0, ',', '.')); ?>&euro;
                        </span>
                    </div>

                    <!-- Estado -->
                    <div class="md:col-span-2 md:text-center">
                        <span class="text-sm font-medium <?php echo esc_attr($proyecto_aprobado['color_estado']); ?>">
                            <?php echo esc_html($proyecto_aprobado['estado']); ?>
                        </span>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="md:col-span-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-amber-400 to-yellow-500 h-2 rounded-full"
                                     style="width: <?php echo esc_attr($proyecto_aprobado['completado']); ?>%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-600 w-10 text-right">
                                <?php echo esc_html($proyecto_aprobado['completado']); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
