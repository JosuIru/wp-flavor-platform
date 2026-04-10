<?php
/**
 * Template: Mapa de Parkings
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Parkings Disponibles';
$descripcion = $descripcion ?? 'Encuentra aparcamiento cerca de ti';

$parkings = [
    ['nombre' => 'Parking Centro', 'direccion' => 'C/ Mayor, 45', 'plazas_libres' => 23, 'plazas_total' => 150, 'precio' => '2.50€/h', 'distancia' => '120m', 'servicios' => ['24h', 'Vigilancia', 'Carga EV']],
    ['nombre' => 'Parking Plaza', 'direccion' => 'Plaza de Espana, s/n', 'plazas_libres' => 5, 'plazas_total' => 80, 'precio' => '2.00€/h', 'distancia' => '250m', 'servicios' => ['24h', 'Lavado']],
    ['nombre' => 'Parking Estacion', 'direccion' => 'Av. Estacion, 12', 'plazas_libres' => 45, 'plazas_total' => 200, 'precio' => '1.80€/h', 'distancia' => '450m', 'servicios' => ['24h', 'Vigilancia', 'PMR']],
    ['nombre' => 'Parking Comercial', 'direccion' => 'C/ Comercio, 78', 'plazas_libres' => 0, 'plazas_total' => 120, 'precio' => '2.20€/h', 'distancia' => '380m', 'servicios' => ['Horario comercial']],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-slate-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #475569 0%, #334155 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?php echo esc_html__('Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Mapa placeholder -->
            <div class="relative rounded-2xl overflow-hidden shadow-xl h-96 lg:h-auto bg-gradient-to-br from-slate-200 to-slate-300 order-2 lg:order-1">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center p-4 rounded-full bg-white shadow-lg mb-4">
                            <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <p class="text-slate-700 font-medium"><?php echo esc_html__('Mapa de parkings', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <!-- Marcadores simulados -->
                <div class="absolute top-1/4 left-1/3 w-10 h-10 rounded-full bg-green-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">23</div>
                <div class="absolute top-1/3 right-1/3 w-10 h-10 rounded-full bg-orange-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">5</div>
                <div class="absolute bottom-1/3 left-1/4 w-10 h-10 rounded-full bg-green-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">45</div>
                <div class="absolute bottom-1/4 right-1/4 w-10 h-10 rounded-full bg-red-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">0</div>
            </div>

            <!-- Lista de parkings -->
            <div class="space-y-4 order-1 lg:order-2">
                <?php foreach ($parkings as $parking): ?>
                    <?php
                    $porcentajeOcupado = (($parking['plazas_total'] - $parking['plazas_libres']) / $parking['plazas_total']) * 100;
                    $colorEstado = $parking['plazas_libres'] === 0 ? 'red' : ($parking['plazas_libres'] < 10 ? 'orange' : 'green');
                    ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-slate-300 cursor-pointer">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center font-bold text-2xl text-white" style="background: linear-gradient(135deg, <?php echo $parking['plazas_libres'] === 0 ? '#ef4444, #dc2626' : ($parking['plazas_libres'] < 10 ? '#f97316, #ea580c' : '#22c55e, #16a34a'); ?>);">
                                <?php echo esc_html($parking['plazas_libres']); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-slate-600 transition-colors"><?php echo esc_html($parking['nombre']); ?></h3>
                                    <span class="text-lg font-bold text-slate-600"><?php echo esc_html($parking['precio']); ?></span>
                                </div>
                                <p class="text-sm text-gray-500 flex items-center gap-1 mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <?php echo esc_html($parking['direccion']); ?> · <?php echo esc_html($parking['distancia']); ?>
                                </p>

                                <!-- Barra de ocupacion -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                        <span><?php echo esc_html($parking['plazas_libres']); ?> plazas libres de <?php echo esc_html($parking['plazas_total']); ?></span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all bg-<?php echo $colorEstado; ?>-500" style="width: <?php echo 100 - $porcentajeOcupado; ?>%"></div>
                                    </div>
                                </div>

                                <!-- Servicios -->
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($parking['servicios'] as $servicio): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600"><?php echo esc_html($servicio); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="flex-shrink-0 p-3 rounded-xl text-white transition-all hover:scale-105 <?php echo $parking['plazas_libres'] === 0 ? 'bg-gray-300 cursor-not-allowed' : ''; ?>" style="<?php echo $parking['plazas_libres'] > 0 ? 'background: linear-gradient(135deg, #475569 0%, #334155 100%);' : ''; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="text-center pt-4">
                    <a href="#todos-parkings" class="inline-flex items-center gap-2 text-slate-600 font-semibold hover:text-slate-800">
                        <?php echo esc_html__('Ver todos los parkings', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
