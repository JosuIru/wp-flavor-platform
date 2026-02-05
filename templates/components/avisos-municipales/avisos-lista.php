<?php
/**
 * Template: Lista de Avisos - Avisos Municipales
 *
 * Muestra una lista de avisos municipales con tarjetas que incluyen
 * titulo, tipo (informativo, urgente, obras), fecha, departamento y estado lectura.
 *
 * @var string $titulo_seccion
 * @var array  $avisos_ejemplo
 * @var array  $opciones_filtrar
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? 'Avisos Recientes';

$avisos_ejemplo = $avisos_ejemplo ?? [
    [
        'titulo'       => 'Corte de agua programado en calle Mayor',
        'extracto'     => 'Se informa a los vecinos del corte de suministro de agua el proximo martes de 8:00 a 14:00 por obras de mantenimiento.',
        'tipo'         => 'urgente',
        'fecha'        => '30 Ene 2026',
        'departamento' => 'Obras y Servicios',
        'leido'        => false,
    ],
    [
        'titulo'       => 'Nuevo horario de la biblioteca municipal',
        'extracto'     => 'A partir del 1 de febrero, la biblioteca amplia su horario los sabados de 9:00 a 14:00 para mejorar el servicio.',
        'tipo'         => 'informativo',
        'fecha'        => '28 Ene 2026',
        'departamento' => 'Cultura',
        'leido'        => true,
    ],
    [
        'titulo'       => 'Inicio de obras en Avenida de la Constitucion',
        'extracto'     => 'Comienzan las obras de reurbanizacion. Se produciran cortes de trafico parciales durante las proximas 6 semanas.',
        'tipo'         => 'obras',
        'fecha'        => '27 Ene 2026',
        'departamento' => 'Urbanismo',
        'leido'        => false,
    ],
    [
        'titulo'       => 'Campana de vacunacion antigripal',
        'extracto'     => 'El centro de salud municipal ofrece vacunacion gratuita para mayores de 65 anos y grupos de riesgo.',
        'tipo'         => 'informativo',
        'fecha'        => '25 Ene 2026',
        'departamento' => 'Salud',
        'leido'        => true,
    ],
];

$opciones_filtrar = $opciones_filtrar ?? [
    'todos'       => 'Todos los avisos',
    'urgente'     => 'Urgentes',
    'informativo' => 'Informativos',
    'obras'       => 'Obras',
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Cabecera con filtro -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800"><?php echo esc_html($titulo_seccion); ?></h2>
            <select name="filtrar_tipo_aviso" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-red-300">
                <?php foreach ($opciones_filtrar as $valor_filtro => $etiqueta_filtro) : ?>
                    <option value="<?php echo esc_attr($valor_filtro); ?>"><?php echo esc_html($etiqueta_filtro); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Lista de avisos -->
        <div class="space-y-4 max-w-4xl">
            <?php foreach ($avisos_ejemplo as $aviso_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border hover:shadow-lg transition-all duration-300 overflow-hidden
                    <?php if (!$aviso_item['leido']) : ?>
                        border-l-4
                        <?php if ($aviso_item['tipo'] === 'urgente') : ?>
                            border-l-red-500 border-red-100
                        <?php elseif ($aviso_item['tipo'] === 'obras') : ?>
                            border-l-orange-500 border-orange-100
                        <?php else : ?>
                            border-l-blue-500 border-blue-100
                        <?php endif; ?>
                    <?php else : ?>
                        border-gray-100
                    <?php endif; ?>">
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                            <!-- Icono de tipo -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center
                                    <?php if ($aviso_item['tipo'] === 'urgente') : ?>
                                        bg-red-100
                                    <?php elseif ($aviso_item['tipo'] === 'obras') : ?>
                                        bg-orange-100
                                    <?php else : ?>
                                        bg-blue-100
                                    <?php endif; ?>">
                                    <?php if ($aviso_item['tipo'] === 'urgente') : ?>
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    <?php elseif ($aviso_item['tipo'] === 'obras') : ?>
                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800 leading-tight">
                                        <?php if (!$aviso_item['leido']) : ?>
                                            <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2 flex-shrink-0 relative top-[-2px]"></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($aviso_item['titulo']); ?>
                                    </h3>
                                    <!-- Badge de tipo -->
                                    <span class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-bold
                                        <?php if ($aviso_item['tipo'] === 'urgente') : ?>
                                            bg-red-100 text-red-700
                                        <?php elseif ($aviso_item['tipo'] === 'obras') : ?>
                                            bg-orange-100 text-orange-700
                                        <?php else : ?>
                                            bg-blue-100 text-blue-700
                                        <?php endif; ?>">
                                        <?php echo esc_html(ucfirst($aviso_item['tipo'])); ?>
                                    </span>
                                </div>

                                <p class="text-sm text-gray-500 mb-3 line-clamp-2">
                                    <?php echo esc_html($aviso_item['extracto']); ?>
                                </p>

                                <!-- Meta info -->
                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <?php echo esc_html($aviso_item['fecha']); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                                        </svg>
                                        <?php echo esc_html($aviso_item['departamento']); ?>
                                    </span>
                                    <a href="<?php echo esc_url('/avisos-municipales/detalle/'); ?>" class="flex items-center gap-1 text-red-500 hover:text-red-700 font-medium transition-colors">
                                        <?php echo esc_html__('Leer mas', 'flavor-chat-ia'); ?>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Boton ver mas -->
        <div class="text-center mt-10">
            <a href="<?php echo esc_url('/avisos-municipales/'); ?>" class="inline-flex items-center gap-2 px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                <?php echo esc_html__('Ver Todos los Avisos', 'flavor-chat-ia'); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
