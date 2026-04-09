<?php
/**
 * Template: Mapa de Puntos de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Puntos de Reciclaje Cercanos';
$descripcion = $descripcion ?? 'Encuentra donde reciclar cada tipo de residuo en tu zona';

$tipos_contenedor = [
    ['tipo' => 'Papel/Carton', 'color' => 'blue', 'icono' => '📦', 'puntos' => 45],
    ['tipo' => 'Plastico/Envases', 'color' => 'yellow', 'icono' => '🥤', 'puntos' => 48],
    ['tipo' => 'Vidrio', 'color' => 'green', 'icono' => '🍾', 'puntos' => 32],
    ['tipo' => 'Organico', 'color' => 'brown', 'icono' => '🍂', 'puntos' => 28],
    ['tipo' => 'Punto Limpio', 'color' => 'gray', 'icono' => '♻️', 'puntos' => 3],
];

$puntos_cercanos = [
    ['nombre' => 'Contenedores Calle Mayor', 'direccion' => 'Calle Mayor, 15', 'distancia' => '120m', 'tipos' => ['Papel', 'Plastico', 'Vidrio', 'Organico']],
    ['nombre' => 'Plaza Central', 'direccion' => 'Plaza de Espana, 1', 'distancia' => '280m', 'tipos' => ['Papel', 'Plastico', 'Vidrio']],
    ['nombre' => 'Parque Municipal', 'direccion' => 'Av. del Parque, s/n', 'distancia' => '450m', 'tipos' => ['Papel', 'Plastico']],
    ['nombre' => 'Centro Comercial Norte', 'direccion' => 'C/ Comercio, 45', 'distancia' => '680m', 'tipos' => ['Papel', 'Plastico', 'Vidrio', 'Pilas', 'Aceite']],
    ['nombre' => 'Punto Limpio Municipal', 'direccion' => 'Poligono Industrial, Nave 12', 'distancia' => '2.3km', 'tipos' => ['Electronica', 'Aceite', 'Pintura', 'Muebles', 'Escombros']],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-emerald-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?php echo esc_html__('Puntos de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Filtros por tipo -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <button class="px-4 py-2 rounded-full text-sm font-medium transition-all" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php foreach ($tipos_contenedor as $tipo): ?>
                <button class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 hover:bg-emerald-50 hover:border-emerald-300 transition-colors">
                    <span><?php echo $tipo['icono']; ?></span>
                    <?php echo esc_html($tipo['tipo']); ?>
                    <span class="px-1.5 py-0.5 rounded-full text-xs bg-gray-100"><?php echo esc_html($tipo['puntos']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Mapa placeholder -->
            <div class="relative rounded-2xl overflow-hidden shadow-xl h-96 lg:h-auto bg-gradient-to-br from-emerald-100 to-teal-100">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center p-4 rounded-full bg-white shadow-lg mb-4">
                            <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <p class="text-emerald-800 font-medium"><?php echo esc_html__('Mapa interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="text-emerald-600 text-sm"><?php echo esc_html__('de puntos de reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
                <!-- Marcadores simulados -->
                <div class="absolute top-1/4 left-1/3 w-8 h-8 rounded-full bg-blue-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold animate-bounce">📦</div>
                <div class="absolute top-1/2 left-1/2 w-8 h-8 rounded-full bg-yellow-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">🥤</div>
                <div class="absolute bottom-1/3 right-1/4 w-8 h-8 rounded-full bg-green-500 border-4 border-white shadow-lg flex items-center justify-center text-white text-xs font-bold">🍾</div>
            </div>

            <!-- Lista de puntos -->
            <div class="space-y-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Puntos mas cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <button class="text-sm text-emerald-600 hover:text-emerald-700 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <?php echo esc_html__('Usar mi ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <?php foreach ($puntos_cercanos as $punto): ?>
                    <article class="group bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100 hover:border-emerald-200 cursor-pointer">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 p-3 rounded-xl bg-emerald-100 text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-bold text-gray-900 group-hover:text-emerald-600 transition-colors"><?php echo esc_html($punto['nombre']); ?></h4>
                                    <span class="text-sm font-semibold text-emerald-600"><?php echo esc_html($punto['distancia']); ?></span>
                                </div>
                                <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($punto['direccion']); ?></p>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($punto['tipos'] as $tipoPunto): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><?php echo esc_html($tipoPunto); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="flex-shrink-0 p-2 rounded-full text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="#mapa-completo" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <span><?php echo esc_html__('Ver Mapa Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>
</section>
