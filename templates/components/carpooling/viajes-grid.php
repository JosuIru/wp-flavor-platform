<?php
/**
 * Template: Grid de Viajes
 *
 * @var string $titulo
 * @var int $columnas
 * @var int $limite
 * @var string $filtro_categoria
 * @var bool $mostrar_avatares
 * @var string $component_classes
 */

// Defaults
$titulo = $titulo ?? 'Próximos Viajes';
$columnas = $columnas ?? 3;
$limite = $limite ?? 6;
$filtro_categoria = $filtro_categoria ?? '';
$mostrar_avatares = $mostrar_avatares ?? true;
$component_classes = $component_classes ?? '';

// Obtener viajes (simulado - en producción vendría de la BD)
// En una implementación real, aquí se llamaría a la API del módulo
$viajes_ejemplo = [
    [
        'id' => 1,
        'conductor' => ['nombre' => 'María García', 'avatar' => ''],
        'origen' => 'Madrid',
        'destino' => 'Valencia',
        'fecha' => 'Mañana 09:00',
        'plazas' => 3,
        'precio' => 15.50,
        'valoracion' => 4.8,
    ],
    [
        'id' => 2,
        'conductor' => ['nombre' => 'Juan López', 'avatar' => ''],
        'origen' => 'Barcelona',
        'destino' => 'Zaragoza',
        'fecha' => 'Hoy 18:30',
        'plazas' => 2,
        'precio' => 12.00,
        'valoracion' => 4.9,
    ],
    [
        'id' => 3,
        'conductor' => ['nombre' => 'Ana Martínez', 'avatar' => ''],
        'origen' => 'Sevilla',
        'destino' => 'Málaga',
        'fecha' => 'Viernes 10:00',
        'plazas' => 1,
        'precio' => 10.00,
        'valoracion' => 5.0,
    ],
];

$grid_cols = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-3',
    4 => 'md:grid-cols-4',
];

$col_class = $grid_cols[$columnas] ?? 'md:grid-cols-3';
?>

<section class="py-16 bg-gray-50 <?php echo esc_attr($component_classes); ?>">
    <div class="container mx-auto px-4">
        <!-- Título de sección -->
        <?php if (!empty($titulo)): ?>
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <div class="w-20 h-1 bg-blue-600 mx-auto rounded-full"></div>
            </div>
        <?php endif; ?>

        <!-- Grid de viajes -->
        <div class="grid grid-cols-1 <?php echo esc_attr($col_class); ?> gap-6">
            <?php foreach (array_slice($viajes_ejemplo, 0, $limite) as $viaje): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden group">
                    <!-- Header con conductor -->
                    <?php if ($mostrar_avatares): ?>
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                    <?php echo esc_html(substr($viaje['conductor']['nombre'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">
                                        <?php echo esc_html($viaje['conductor']['nombre']); ?>
                                    </div>
                                    <div class="flex items-center text-sm text-yellow-600">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                        <span class="ml-1"><?php echo number_format($viaje['valoracion'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contenido del viaje -->
                    <div class="p-6">
                        <!-- Ruta -->
                        <div class="mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-1">
                                    <div class="flex items-center text-gray-700 mb-2">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <span class="ml-2 font-medium"><?php echo esc_html($viaje['origen']); ?></span>
                                    </div>
                                    <div class="flex items-center text-gray-700">
                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                        <span class="ml-2 font-medium"><?php echo esc_html($viaje['destino']); ?></span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Info del viaje -->
                        <div class="space-y-3 mb-4">
                            <!-- Fecha -->
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm"><?php echo esc_html($viaje['fecha']); ?></span>
                            </div>

                            <!-- Plazas -->
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="text-sm">
                                    <?php printf(_n('%d plaza disponible', '%d plazas disponibles', $viaje['plazas'], FLAVOR_PLATFORM_TEXT_DOMAIN), $viaje['plazas']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Footer con precio y botón -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <div class="text-2xl font-bold text-blue-600">
                                <?php echo number_format($viaje['precio'], 2); ?>€
                                <span class="text-sm text-gray-500 font-normal"><?php echo esc_html__('/plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-300 transform group-hover:scale-105">
                                <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ver todos -->
        <div class="text-center mt-12">
            <a href="#" class="inline-flex items-center px-8 py-3 bg-white border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition duration-300">
                <span><?php _e('Ver Todos los Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>
</section>
