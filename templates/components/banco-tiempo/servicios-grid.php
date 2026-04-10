<?php
/**
 * Template: Grid de Servicios - Banco de Tiempo
 *
 * Muestra una cuadricula de servicios disponibles para intercambiar.
 * Cada tarjeta incluye titulo, categoria, duracion, usuario y valoracion.
 *
 * @var string $titulo_seccion
 * @var array  $servicios_ejemplo
 * @var array  $opciones_ordenar
 * @var string $component_classes
 *
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? 'Servicios Disponibles';

$servicios_ejemplo = $servicios_ejemplo ?? [
    [
        'titulo'      => 'Clases de Guitarra',
        'descripcion' => 'Clases particulares de guitarra acustica para principiantes y nivel intermedio.',
        'categoria'   => 'Educacion',
        'duracion'    => '1 hora',
        'usuario'     => 'Miguel A.',
        'valoracion'  => 4.9,
        'gradiente'   => 'from-amber-500 to-yellow-600',
    ],
    [
        'titulo'      => 'Reparacion de Ordenadores',
        'descripcion' => 'Diagnostico y reparacion de problemas informaticos, software y hardware basico.',
        'categoria'   => 'Tecnologia',
        'duracion'    => '2 horas',
        'usuario'     => 'Laura P.',
        'valoracion'  => 4.7,
        'gradiente'   => 'from-orange-500 to-amber-600',
    ],
    [
        'titulo'      => 'Cuidado de Mascotas',
        'descripcion' => 'Paseo y cuidado de perros y gatos durante el dia o fines de semana.',
        'categoria'   => 'Hogar',
        'duracion'    => '1.5 horas',
        'usuario'     => 'Elena R.',
        'valoracion'  => 5.0,
        'gradiente'   => 'from-yellow-500 to-orange-500',
    ],
];

$opciones_ordenar = $opciones_ordenar ?? [
    'recientes'    => 'Mas recientes',
    'valoracion'   => 'Mejor valorados',
    'duracion_asc' => 'Menor duracion',
    'duracion_desc'=> 'Mayor duracion',
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Cabecera con ordenacion -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800"><?php echo esc_html($titulo_seccion); ?></h2>
            <select name="ordenar_servicios" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                <?php foreach ($opciones_ordenar as $valor_orden => $etiqueta_orden) : ?>
                    <option value="<?php echo esc_attr($valor_orden); ?>"><?php echo esc_html($etiqueta_orden); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Grid de servicios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($servicios_ejemplo as $servicio_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <!-- Cabecera con gradiente -->
                    <div class="relative h-40 bg-gradient-to-br <?php echo esc_attr($servicio_item['gradiente']); ?> overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <!-- Badge de categoria -->
                        <div class="absolute top-3 right-3 px-3 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-bold text-amber-700">
                            <?php echo esc_html($servicio_item['categoria']); ?>
                        </div>
                        <!-- Badge de duracion -->
                        <div class="absolute bottom-3 left-3 px-3 py-1 bg-black/50 backdrop-blur rounded-lg text-white text-xs font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html($servicio_item['duracion']); ?>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-amber-600 transition-colors">
                            <?php echo esc_html($servicio_item['titulo']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2">
                            <?php echo esc_html($servicio_item['descripcion']); ?>
                        </p>

                        <!-- Usuario y valoracion -->
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    <?php echo esc_html(mb_substr($servicio_item['usuario'], 0, 2)); ?>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo esc_html($servicio_item['usuario']); ?></span>
                            </div>
                            <div class="flex items-center gap-1 text-sm">
                                <span class="text-yellow-500">&#9733;</span>
                                <span class="font-semibold text-gray-700"><?php echo esc_html($servicio_item['valoracion']); ?></span>
                            </div>
                        </div>

                        <!-- Boton de solicitar -->
                        <a href="<?php echo esc_url('/banco-tiempo/solicitar/'); ?>" class="block w-full text-center px-5 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-600 hover:from-amber-600 hover:to-yellow-700 text-white font-semibold rounded-lg transition-all">
                            <?php echo esc_html__('Solicitar Intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Boton ver mas -->
        <div class="text-center mt-10">
            <a href="<?php echo esc_url('/banco-tiempo/servicios/'); ?>" class="inline-flex items-center gap-2 px-8 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                <?php echo esc_html__('Ver Todos los Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
