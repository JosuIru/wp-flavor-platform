<?php
/**
 * Template: Mapa de Bicicletas Compartidas
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Bicicletas Disponibles';
$descripcion = $descripcion ?? 'Encuentra una bici cerca de ti y empieza a pedalear';

$estaciones = [
    ['nombre' => 'Estacion Centro', 'direccion' => 'Plaza Mayor, 1', 'bicis' => 8, 'anclajes' => 12, 'distancia' => '150m', 'electricas' => 3],
    ['nombre' => 'Estacion Parque', 'direccion' => 'Av. del Parque, 45', 'bicis' => 5, 'anclajes' => 15, 'distancia' => '280m', 'electricas' => 2],
    ['nombre' => 'Estacion Universidad', 'direccion' => 'C/ Universidad, 12', 'bicis' => 2, 'anclajes' => 20, 'distancia' => '420m', 'electricas' => 0],
    ['nombre' => 'Estacion Estacion', 'direccion' => 'Plaza Estacion, s/n', 'bicis' => 12, 'anclajes' => 18, 'distancia' => '650m', 'electricas' => 5],
];

$stats = [
    ['numero' => '45', 'texto' => 'Estaciones'],
    ['numero' => '380', 'texto' => 'Bicicletas'],
    ['numero' => '12K', 'texto' => 'Usuarios'],
    ['numero' => '98%', 'texto' => 'Disponibilidad'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-lime-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo esc_html__('BiciVecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <?php foreach ($stats as $stat): ?>
                <div class="bg-white rounded-xl p-4 shadow-md text-center">
                    <div class="text-3xl font-bold text-lime-600 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo esc_html($stat['texto']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Mapa -->
            <div class="relative rounded-2xl overflow-hidden shadow-xl h-96 lg:h-auto bg-gradient-to-br from-lime-100 to-green-100">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center p-4 rounded-full bg-white shadow-lg mb-4">
                            <svg class="w-12 h-12 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <p class="text-lime-800 font-medium"><?php echo esc_html__('Mapa de estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <!-- Marcadores -->
                <div class="absolute top-1/4 left-1/3 w-10 h-10 rounded-full bg-lime-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-sm font-bold animate-pulse">8</div>
                <div class="absolute top-1/2 right-1/4 w-10 h-10 rounded-full bg-lime-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-sm font-bold">5</div>
                <div class="absolute bottom-1/3 left-1/4 w-10 h-10 rounded-full bg-orange-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-sm font-bold">2</div>
            </div>

            <!-- Lista de estaciones -->
            <div class="space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Estaciones cercanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <button class="text-sm text-lime-600 hover:text-lime-700 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <?php echo esc_html__('Usar mi ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <?php foreach ($estaciones as $estacion): ?>
                    <?php $pocasBicis = $estacion['bicis'] < 3; ?>
                    <article class="group bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100 hover:border-lime-300">
                        <div class="flex items-center gap-4">
                            <!-- Indicador de bicis -->
                            <div class="flex-shrink-0 w-16 h-16 rounded-xl flex flex-col items-center justify-center <?php echo $pocasBicis ? 'bg-orange-100' : 'bg-lime-100'; ?>">
                                <span class="text-2xl font-bold <?php echo $pocasBicis ? 'text-orange-600' : 'text-lime-600'; ?>"><?php echo esc_html($estacion['bicis']); ?></span>
                                <span class="text-xs <?php echo $pocasBicis ? 'text-orange-500' : 'text-lime-500'; ?>"><?php echo esc_html__('bicis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-bold text-gray-900 group-hover:text-lime-600 transition-colors"><?php echo esc_html($estacion['nombre']); ?></h4>
                                    <span class="text-sm font-medium text-lime-600"><?php echo esc_html($estacion['distancia']); ?></span>
                                </div>
                                <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($estacion['direccion']); ?></p>
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="flex items-center gap-1 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        <?php echo esc_html($estacion['anclajes']); ?> anclajes
                                    </span>
                                    <?php if ($estacion['electricas'] > 0): ?>
                                        <span class="flex items-center gap-1 text-blue-600 font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <?php echo esc_html($estacion['electricas']); ?> electricas
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button class="flex-shrink-0 px-4 py-2 rounded-xl text-white font-semibold transition-all hover:scale-105" style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                                <?php echo esc_html__('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="text-center pt-4">
                    <a href="#todas-estaciones" class="inline-flex items-center gap-2 text-lime-600 font-semibold hover:text-lime-700">
                        <?php echo esc_html__('Ver todas las estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
