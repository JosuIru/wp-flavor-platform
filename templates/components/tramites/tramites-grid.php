<?php
/**
 * Template: Grid de Tramites Disponibles
 *
 * Grid de tarjetas de tramites con icono, nombre, descripcion,
 * tiempo estimado, requisitos y modalidad (online/presencial).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$tramites_ejemplo = [
    [
        'titulo'       => 'Certificado de empadronamiento',
        'descripcion'  => 'Obtener certificado o volante de empadronamiento para acreditar tu domicilio en el municipio.',
        'icono'        => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
        'tiempo'       => 'Inmediato',
        'requisitos'   => ['DNI/NIE en vigor', 'Estar empadronado'],
        'modalidad'    => 'online',
        'categoria'    => 'Padron',
    ],
    [
        'titulo'       => 'Licencia de actividad',
        'descripcion'  => 'Solicitar licencia para apertura de establecimiento comercial, industrial o de servicios.',
        'icono'        => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'tiempo'       => '15-30 dias',
        'requisitos'   => ['Proyecto tecnico', 'DNI/NIF', 'Alta IAE'],
        'modalidad'    => 'online',
        'categoria'    => 'Licencias',
    ],
    [
        'titulo'       => 'Reserva de instalaciones deportivas',
        'descripcion'  => 'Reservar pistas deportivas, pabellones y piscina municipal para uso individual o colectivo.',
        'icono'        => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'tiempo'       => 'Inmediato',
        'requisitos'   => ['Estar registrado', 'Abono vigente'],
        'modalidad'    => 'online',
        'categoria'    => 'Deportes',
    ],
    [
        'titulo'       => 'Solicitud de vado',
        'descripcion'  => 'Tramitar la autorizacion de vado permanente para acceso de vehiculos a garajes y fincas.',
        'icono'        => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        'tiempo'       => '30-45 dias',
        'requisitos'   => ['Escritura propiedad', 'Plano ubicacion', 'DNI'],
        'modalidad'    => 'presencial',
        'categoria'    => 'Via Publica',
    ],
    [
        'titulo'       => 'Registro de animales domesticos',
        'descripcion'  => 'Dar de alta mascotas en el censo municipal de animales de compania, obligatorio por normativa.',
        'icono'        => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        'tiempo'       => '5 dias',
        'requisitos'   => ['Cartilla veterinaria', 'Microchip', 'DNI propietario'],
        'modalidad'    => 'online',
        'categoria'    => 'Registro',
    ],
    [
        'titulo'       => 'Bonificacion de IBI',
        'descripcion'  => 'Solicitar bonificaciones en el Impuesto sobre Bienes Inmuebles por familia numerosa u otros supuestos.',
        'icono'        => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z',
        'tiempo'       => '10-15 dias',
        'requisitos'   => ['Titulo familia numerosa', 'Recibo IBI', 'DNI'],
        'modalidad'    => 'online',
        'categoria'    => 'Tributos',
    ],
];

$categorias_tramites = ['Todos', 'Padron', 'Licencias', 'Deportes', 'Tributos', 'Via Publica', 'Registro'];
?>

<section class="flavor-component flavor-section py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Tramites Disponibles'); ?>
            </h2>
            <div class="w-20 h-1 bg-orange-500 mx-auto rounded-full"></div>
        </div>

        <!-- Filtros por categoria -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <?php foreach ($categorias_tramites as $indice_categoria => $nombre_categoria): ?>
                <button class="px-4 py-2 rounded-full text-sm font-medium transition duration-300 <?php echo ($indice_categoria === 0) ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-orange-50 border border-gray-200'; ?>">
                    <?php echo esc_html($nombre_categoria); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de tramites -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tramites_ejemplo as $tramite_actual): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden group">
                    <div class="p-6">
                        <!-- Icono y modalidad -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-amber-500 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition duration-300">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($tramite_actual['icono']); ?>" />
                                </svg>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo ($tramite_actual['modalidad'] === 'online') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo ($tramite_actual['modalidad'] === 'online') ? esc_html__('Online', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Presencial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>

                        <!-- Titulo y descripcion -->
                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition duration-300">
                            <?php echo esc_html($tramite_actual['titulo']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm leading-relaxed mb-4">
                            <?php echo esc_html($tramite_actual['descripcion']); ?>
                        </p>

                        <!-- Tiempo estimado -->
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="w-4 h-4 mr-2 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo esc_html__('Tiempo estimado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($tramite_actual['tiempo']); ?>
                        </div>

                        <!-- Requisitos -->
                        <div class="mb-4">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide"><?php echo esc_html__('Requisitos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <ul class="mt-1 space-y-1">
                                <?php foreach ($tramite_actual['requisitos'] as $requisito_texto): ?>
                                    <li class="flex items-center text-sm text-gray-600">
                                        <svg class="w-3 h-3 mr-2 text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <?php echo esc_html($requisito_texto); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Boton iniciar -->
                        <button class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold transition duration-300">
                            <?php echo esc_html__('Iniciar Tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
