<?php
/**
 * Template: Grid de Proyectos Presupuestarios
 *
 * Grid de tarjetas de proyectos con presupuesto, votos,
 * progreso y estado de ejecucion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$proyectos_ejemplo = [
    [
        'id'          => 1,
        'titulo'      => 'Renovacion del centro deportivo municipal',
        'descripcion' => 'Modernizacion de las instalaciones deportivas incluyendo nueva piscina climatizada, pistas de padel y vestuarios accesibles.',
        'presupuesto' => 450000,
        'categoria'   => 'Deportes',
        'votos'       => 1243,
        'meta_votos'  => 2000,
        'estado'      => 'votacion',
    ],
    [
        'id'          => 2,
        'titulo'      => 'Red de puntos de recarga electrica',
        'descripcion' => 'Instalacion de 30 puntos de recarga para vehiculos electricos distribuidos por todo el municipio en zonas estrategicas.',
        'presupuesto' => 180000,
        'categoria'   => 'Movilidad',
        'votos'       => 876,
        'meta_votos'  => 1500,
        'estado'      => 'propuesta',
    ],
    [
        'id'          => 3,
        'titulo'      => 'Rehabilitacion del mercado central',
        'descripcion' => 'Reforma integral del mercado central preservando la arquitectura original y mejorando la eficiencia energetica del edificio.',
        'presupuesto' => 720000,
        'categoria'   => 'Patrimonio',
        'votos'       => 2100,
        'meta_votos'  => 2000,
        'estado'      => 'ejecucion',
    ],
];

$estados_proyecto = [
    'propuesta'  => ['etiqueta' => 'Fase Propuesta', 'color_fondo' => 'bg-blue-100',   'color_texto' => 'text-blue-800'],
    'votacion'   => ['etiqueta' => 'En Votacion',    'color_fondo' => 'bg-amber-100',  'color_texto' => 'text-amber-800'],
    'ejecucion'  => ['etiqueta' => 'En Ejecucion',   'color_fondo' => 'bg-green-100',  'color_texto' => 'text-green-800'],
    'completado' => ['etiqueta' => 'Completado',      'color_fondo' => 'bg-purple-100', 'color_texto' => 'text-purple-800'],
];
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo de seccion -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Proyectos en Curso'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Explora los proyectos propuestos y vota por los que consideres prioritarios.'); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de proyectos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($proyectos_ejemplo as $proyecto):
                $estado_actual = $estados_proyecto[$proyecto['estado']] ?? $estados_proyecto['propuesta'];
                $porcentaje_votos = min(100, round(($proyecto['votos'] / $proyecto['meta_votos']) * 100));
                $presupuesto_formateado = number_format($proyecto['presupuesto'], 0, ',', '.');
            ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden group">
                    <!-- Cabecera con presupuesto -->
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 p-4 border-b border-amber-100">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-amber-600">
                                <?php echo esc_html($presupuesto_formateado); ?>&euro;
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo esc_attr($estado_actual['color_fondo'] . ' ' . $estado_actual['color_texto']); ?>">
                                <?php echo esc_html($estado_actual['etiqueta']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <!-- Categoria -->
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                            <?php echo esc_html($proyecto['categoria']); ?>
                        </span>

                        <h3 class="text-lg font-bold text-gray-900 mt-3 mb-2 group-hover:text-amber-600 transition duration-300">
                            <?php echo esc_html($proyecto['titulo']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm leading-relaxed mb-5">
                            <?php echo esc_html($proyecto['descripcion']); ?>
                        </p>

                        <!-- Barra de votos -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-semibold text-amber-600">
                                    <?php echo esc_html(number_format($proyecto['votos'], 0, ',', '.')); ?> <?php echo esc_html__('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="text-gray-400">
                                    <?php echo esc_html($porcentaje_votos); ?>%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-amber-400 to-yellow-500 h-2.5 rounded-full transition-all duration-500"
                                     style="width: <?php echo esc_attr($porcentaje_votos); ?>%"></div>
                            </div>
                        </div>

                        <!-- Boton votar -->
                        <button class="w-full bg-amber-500 hover:bg-amber-600 text-white py-3 rounded-lg font-semibold transition duration-300 transform group-hover:scale-[1.02]">
                            <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                            <?php echo esc_html__('Votar este proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
