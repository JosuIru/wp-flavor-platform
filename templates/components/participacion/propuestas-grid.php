<?php
/**
 * Template: Grid de Propuestas Ciudadanas
 *
 * Muestra un grid de tarjetas de propuestas con filtros,
 * estado, votos, autor y progreso.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$propuestas_ejemplo = [
    [
        'id'          => 1,
        'titulo'      => 'Nuevo parque infantil en el barrio sur',
        'descripcion' => 'Propuesta para construir un parque infantil accesible con zona de juegos inclusiva para todos los ninos del barrio.',
        'autor'       => ['nombre' => 'Laura Fernandez', 'avatar' => ''],
        'estado'      => 'en-votacion',
        'votos'       => 187,
        'meta_votos'  => 250,
        'categoria'   => 'Urbanismo',
        'comentarios' => 34,
    ],
    [
        'id'          => 2,
        'titulo'      => 'Carril bici conectando centros educativos',
        'descripcion' => 'Crear una red segura de carriles bici que conecte los colegios e institutos del municipio con las zonas residenciales.',
        'autor'       => ['nombre' => 'Carlos Ruiz', 'avatar' => ''],
        'estado'      => 'abierta',
        'votos'       => 92,
        'meta_votos'  => 200,
        'categoria'   => 'Movilidad',
        'comentarios' => 18,
    ],
    [
        'id'          => 3,
        'titulo'      => 'Huerto comunitario en parcela municipal',
        'descripcion' => 'Transformar la parcela abandonada de la calle Mayor en un huerto comunitario gestionado por los vecinos.',
        'autor'       => ['nombre' => 'Ana Moreno', 'avatar' => ''],
        'estado'      => 'aprobada',
        'votos'       => 312,
        'meta_votos'  => 300,
        'categoria'   => 'Medio Ambiente',
        'comentarios' => 56,
    ],
];

$estados_config = [
    'abierta'     => ['etiqueta' => 'Abierta',      'color_fondo' => 'bg-blue-100',   'color_texto' => 'text-blue-800'],
    'en-votacion' => ['etiqueta' => 'En Votacion',   'color_fondo' => 'bg-amber-100',  'color_texto' => 'text-amber-800'],
    'aprobada'    => ['etiqueta' => 'Aprobada',       'color_fondo' => 'bg-green-100',  'color_texto' => 'text-green-800'],
    'rechazada'   => ['etiqueta' => 'Rechazada',      'color_fondo' => 'bg-red-100',    'color_texto' => 'text-red-800'],
];

$filtro_activo = $filtro ?? 'todas';
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo de seccion -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Propuestas Ciudadanas'); ?>
            </h2>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full"></div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <?php
            $opciones_filtro = [
                'todas'       => 'Todas',
                'abierta'     => 'Abiertas',
                'en-votacion' => 'En Votacion',
                'aprobada'    => 'Aprobadas',
            ];
            foreach ($opciones_filtro as $clave_filtro => $texto_filtro): ?>
                <button class="px-5 py-2 rounded-full text-sm font-medium transition duration-300 <?php echo ($filtro_activo === $clave_filtro) ? 'bg-amber-500 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-amber-50 border border-gray-200'; ?>">
                    <?php echo esc_html($texto_filtro); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de propuestas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($propuestas_ejemplo as $propuesta):
                $estado_actual = $estados_config[$propuesta['estado']] ?? $estados_config['abierta'];
                $porcentaje_votos = min(100, round(($propuesta['votos'] / $propuesta['meta_votos']) * 100));
            ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden group">
                    <!-- Cabecera con estado y categoria -->
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo esc_attr($estado_actual['color_fondo'] . ' ' . $estado_actual['color_texto']); ?>">
                            <?php echo esc_html($estado_actual['etiqueta']); ?>
                        </span>
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                            <?php echo esc_html($propuesta['categoria']); ?>
                        </span>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-amber-600 transition duration-300">
                            <?php echo esc_html($propuesta['titulo']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm leading-relaxed mb-4">
                            <?php echo esc_html($propuesta['descripcion']); ?>
                        </p>

                        <!-- Barra de progreso de votos -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-semibold text-amber-600">
                                    <?php echo esc_html($propuesta['votos']); ?> <?php echo esc_html__('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="text-gray-400">
                                    <?php echo esc_html__('Meta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($propuesta['meta_votos']); ?>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-2 rounded-full transition-all duration-500"
                                     style="width: <?php echo esc_attr($porcentaje_votos); ?>%"></div>
                            </div>
                        </div>

                        <!-- Autor y comentarios -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    <?php echo esc_html(mb_substr($propuesta['autor']['nombre'], 0, 1)); ?>
                                </div>
                                <span class="text-sm text-gray-700"><?php echo esc_html($propuesta['autor']['nombre']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-400 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <?php echo esc_html($propuesta['comentarios']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
