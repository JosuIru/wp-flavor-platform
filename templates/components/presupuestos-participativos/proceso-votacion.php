<?php
/**
 * Template: Proceso de Votacion Presupuestos Participativos
 *
 * Timeline horizontal con los 4 pasos del proceso de
 * presupuestos participativos: Propuestas, Evaluacion,
 * Votacion y Ejecucion.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$pasos_proceso = [
    [
        'numero'      => 1,
        'titulo'      => 'Propuestas',
        'descripcion' => 'Los ciudadanos presentan sus proyectos e ideas para mejorar el municipio durante el periodo de propuestas.',
        'icono'       => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        'color_desde' => 'from-amber-400',
        'color_hasta' => 'to-amber-500',
    ],
    [
        'numero'      => 2,
        'titulo'      => 'Evaluacion Tecnica',
        'descripcion' => 'Un equipo tecnico analiza la viabilidad, el coste y el impacto de cada propuesta presentada por la ciudadania.',
        'icono'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'color_desde' => 'from-yellow-400',
        'color_hasta' => 'to-yellow-500',
    ],
    [
        'numero'      => 3,
        'titulo'      => 'Votacion Ciudadana',
        'descripcion' => 'Todos los vecinos empadronados pueden votar los proyectos viables. Cada persona elige sus prioridades.',
        'icono'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'color_desde' => 'from-amber-500',
        'color_hasta' => 'to-orange-500',
    ],
    [
        'numero'      => 4,
        'titulo'      => 'Ejecucion',
        'descripcion' => 'Los proyectos mas votados se incluyen en el presupuesto municipal y se ejecutan con seguimiento publico.',
        'icono'       => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'color_desde' => 'from-yellow-500',
        'color_hasta' => 'to-amber-600',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Proceso de Presupuestos Participativos'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Un proceso transparente y democratico en cuatro etapas.'); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Timeline horizontal -->
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                <!-- Linea conectora horizontal (desktop) -->
                <div class="hidden md:block absolute top-16 left-0 right-0 h-0.5 bg-amber-200" style="width: calc(100% - 6rem); margin-left: 3rem;"></div>

                <?php foreach ($pasos_proceso as $paso_actual): ?>
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Circulo con icono -->
                            <div class="relative z-10 w-32 h-32 bg-gradient-to-br <?php echo esc_attr($paso_actual['color_desde'] . ' ' . $paso_actual['color_hasta']); ?> rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($paso_actual['icono']); ?>" />
                                </svg>
                            </div>

                            <!-- Numero del paso -->
                            <div class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 text-amber-700 rounded-full text-sm font-bold mb-3">
                                <?php echo esc_html($paso_actual['numero']); ?>
                            </div>

                            <!-- Titulo -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                <?php echo esc_html($paso_actual['titulo']); ?>
                            </h3>

                            <!-- Descripcion -->
                            <p class="text-gray-600 text-sm leading-relaxed">
                                <?php echo esc_html($paso_actual['descripcion']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Fechas o periodo (opcional) -->
            <div class="mt-16 bg-amber-50 rounded-xl p-6 text-center">
                <p class="text-amber-800 font-medium">
                    <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <?php echo esc_html($periodo_texto ?? 'Periodo de votacion abierto: del 1 de marzo al 30 de abril'); ?>
                </p>
            </div>
        </div>
    </div>
</section>
