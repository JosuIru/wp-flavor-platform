<?php
/**
 * Template: Mapa de Estaciones - Bicicletas Compartidas
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var bool $mostrar_filtros
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;

// Datos de ejemplo de estaciones (en producción vendrían de la BD)
$estaciones_ejemplo = [
    [
        'id' => 1,
        'nombre' => 'Plaza Mayor',
        'direccion' => 'Plaza Mayor, 1',
        'bicicletas_disponibles' => 8,
        'plazas_libres' => 4,
        'total_plazas' => 12,
        'estado' => 'activa',
        'lat' => 40.4165,
        'lng' => -3.7026,
    ],
    [
        'id' => 2,
        'nombre' => 'Parque Central',
        'direccion' => 'Av. del Parque, 45',
        'bicicletas_disponibles' => 12,
        'plazas_libres' => 3,
        'total_plazas' => 15,
        'estado' => 'activa',
        'lat' => 40.4200,
        'lng' => -3.7050,
    ],
    [
        'id' => 3,
        'nombre' => 'Estación de Tren',
        'direccion' => 'Estación Central, s/n',
        'bicicletas_disponibles' => 5,
        'plazas_libres' => 10,
        'total_plazas' => 15,
        'estado' => 'activa',
        'lat' => 40.4150,
        'lng' => -3.7000,
    ],
    [
        'id' => 4,
        'nombre' => 'Centro Comercial',
        'direccion' => 'C/ Comercio, 88',
        'bicicletas_disponibles' => 2,
        'plazas_libres' => 8,
        'total_plazas' => 10,
        'estado' => 'baja-disponibilidad',
        'lat' => 40.4180,
        'lng' => -3.7080,
    ],
];
?>

<section class="py-16 bg-gradient-to-b from-white to-gray-50 <?php echo esc_attr($component_classes); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">
                    <?php echo esc_html($titulo ?? 'Mapa de Estaciones'); ?>
                </h2>
                <?php if (!empty($subtitulo)): ?>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        <?php echo esc_html($subtitulo ?? 'Encuentra la estación más cercana'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Filtros -->
            <?php if (!empty($mostrar_filtros)): ?>
            <div class="mb-8 flex flex-wrap gap-4 justify-center">
                <button class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-lg">
                    Todas las Estaciones
                </button>
                <button class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-md border border-gray-200">
                    Solo con Bicis
                </button>
                <button class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-md border border-gray-200">
                    Solo con Plazas
                </button>
                <button class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-md border border-gray-200">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Más Cercanas
                </button>
            </div>
            <?php endif; ?>

            <!-- Mapa placeholder y lista de estaciones -->
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Mapa interactivo -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                        <!-- Placeholder del mapa -->
                        <div class="relative h-[600px] bg-gradient-to-br from-emerald-100 via-cyan-50 to-blue-100">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-32 h-32 mx-auto text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                    <p class="text-gray-600 font-medium">Mapa Interactivo de Estaciones</p>
                                    <p class="text-gray-400 text-sm mt-2">Integración con Google Maps / OpenStreetMap</p>
                                </div>
                            </div>

                            <!-- Marcadores de ejemplo simulados -->
                            <div class="absolute top-1/4 left-1/3 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-4 border-white animate-pulse">
                                    8
                                </div>
                            </div>
                            <div class="absolute top-1/3 right-1/3 transform translate-x-1/2 -translate-y-1/2">
                                <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-4 border-white">
                                    12
                                </div>
                            </div>
                            <div class="absolute bottom-1/3 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-4 border-white">
                                    2
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de estaciones -->
                <div class="space-y-4 lg:max-h-[600px] lg:overflow-y-auto">
                    <?php foreach ($estaciones_ejemplo as $estacion):
                        $porcentaje_disponibilidad = ($estacion['bicicletas_disponibles'] / $estacion['total_plazas']) * 100;
                        $color_estado = $porcentaje_disponibilidad > 40 ? 'emerald' : ($porcentaje_disponibilidad > 20 ? 'yellow' : 'red');
                    ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-5 border-l-4 border-<?php echo $color_estado; ?>-500">
                        <!-- Header de la estación -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">
                                    <?php echo esc_html($estacion['nombre']); ?>
                                </h3>
                                <p class="text-sm text-gray-500 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    <?php echo esc_html($estacion['direccion']); ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <div class="w-12 h-12 bg-<?php echo $color_estado; ?>-100 rounded-full flex items-center justify-center">
                                    <svg class="w-7 h-7 text-<?php echo $color_estado; ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Información de disponibilidad -->
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="bg-emerald-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-emerald-600">
                                    <?php echo esc_html($estacion['bicicletas_disponibles']); ?>
                                </div>
                                <div class="text-xs text-gray-600">Bicis disponibles</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    <?php echo esc_html($estacion['plazas_libres']); ?>
                                </div>
                                <div class="text-xs text-gray-600">Plazas libres</div>
                            </div>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Capacidad</span>
                                <span><?php echo round($porcentaje_disponibilidad); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-<?php echo $color_estado; ?>-500 h-2 rounded-full transition-all duration-300" style="width: <?php echo $porcentaje_disponibilidad; ?>%"></div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-2">
                            <button class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Ver Ruta
                            </button>
                            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="mt-12 grid md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-6 text-white">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Horario 24/7</h3>
                    </div>
                    <p class="text-emerald-100">Acceso a bicicletas en cualquier momento del día o la noche.</p>
                </div>

                <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-6 text-white">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">App Móvil</h3>
                    </div>
                    <p class="text-cyan-100">Desbloquea y paga desde tu smartphone de forma sencilla.</p>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Precio Justo</h3>
                    </div>
                    <p class="text-blue-100">Solo €0.50 cada 30 minutos. Abono mensual desde €15.</p>
                </div>
            </div>
        </div>
    </div>
</section>
