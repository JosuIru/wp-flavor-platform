<?php
/**
 * Template: Grid de Viajes
 *
 * Listado de viajes disponibles en formato tarjetas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo = $data['titulo'] ?? 'Viajes Disponibles';
$columnas = $data['columnas'] ?? 3;
$limite = $data['limite'] ?? 6;
$filtro_categoria = $data['filtro_categoria'] ?? 'proximos';
$mostrar_avatares = $data['mostrar_avatares'] ?? true;

// Clases de grid según columnas
$grid_classes = [
    2 => 'grid-cols-1 lg:grid-cols-2',
    3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
];
$clase_grid = $grid_classes[$columnas] ?? $grid_classes[3];

// Obtener viajes de ejemplo (en producción vendría de la base de datos)
$viajes_ejemplo = [
    [
        'id' => 1,
        'conductor' => [
            'nombre' => 'María González',
            'avatar' => 'https://i.pravatar.cc/150?img=1',
            'valoracion' => 4.8,
            'viajes_totales' => 45
        ],
        'origen' => 'Bilbao Centro',
        'destino' => 'San Sebastián',
        'fecha_hora' => 'Hoy, 18:30',
        'plazas_disponibles' => 2,
        'precio_por_plaza' => 8.50,
        'vehiculo' => 'Toyota Corolla',
        'permite_mascotas' => false,
        'permite_equipaje' => true,
    ],
    [
        'id' => 2,
        'conductor' => [
            'nombre' => 'Carlos Ruiz',
            'avatar' => 'https://i.pravatar.cc/150?img=12',
            'valoracion' => 4.9,
            'viajes_totales' => 78
        ],
        'origen' => 'Vitoria-Gasteiz',
        'destino' => 'Pamplona',
        'fecha_hora' => 'Mañana, 09:00',
        'plazas_disponibles' => 3,
        'precio_por_plaza' => 12.00,
        'vehiculo' => 'Seat León',
        'permite_mascotas' => true,
        'permite_equipaje' => true,
    ],
    [
        'id' => 3,
        'conductor' => [
            'nombre' => 'Ana Martínez',
            'avatar' => 'https://i.pravatar.cc/150?img=5',
            'valoracion' => 5.0,
            'viajes_totales' => 120
        ],
        'origen' => 'Santander',
        'destino' => 'Bilbao',
        'fecha_hora' => 'Mañana, 16:00',
        'plazas_disponibles' => 1,
        'precio_por_plaza' => 10.00,
        'vehiculo' => 'Honda Civic',
        'permite_mascotas' => false,
        'permite_equipaje' => false,
    ],
    [
        'id' => 4,
        'conductor' => [
            'nombre' => 'Javier López',
            'avatar' => 'https://i.pravatar.cc/150?img=8',
            'valoracion' => 4.7,
            'viajes_totales' => 56
        ],
        'origen' => 'Logroño',
        'destino' => 'Zaragoza',
        'fecha_hora' => 'Jueves, 08:00',
        'plazas_disponibles' => 2,
        'precio_por_plaza' => 15.00,
        'vehiculo' => 'Volkswagen Golf',
        'permite_mascotas' => true,
        'permite_equipaje' => true,
    ],
    [
        'id' => 5,
        'conductor' => [
            'nombre' => 'Laura Sánchez',
            'avatar' => 'https://i.pravatar.cc/150?img=9',
            'valoracion' => 4.9,
            'viajes_totales' => 92
        ],
        'origen' => 'Donosti',
        'destino' => 'Iruña',
        'fecha_hora' => 'Viernes, 10:30',
        'plazas_disponibles' => 3,
        'precio_por_plaza' => 9.50,
        'vehiculo' => 'Peugeot 308',
        'permite_mascotas' => false,
        'permite_equipaje' => true,
    ],
    [
        'id' => 6,
        'conductor' => [
            'nombre' => 'Miguel Fernández',
            'avatar' => 'https://i.pravatar.cc/150?img=13',
            'valoracion' => 4.6,
            'viajes_totales' => 34
        ],
        'origen' => 'Burgos',
        'destino' => 'Valladolid',
        'fecha_hora' => 'Sábado, 11:00',
        'plazas_disponibles' => 4,
        'precio_por_plaza' => 11.00,
        'vehiculo' => 'Renault Mégane',
        'permite_mascotas' => true,
        'permite_equipaje' => true,
    ],
];

$viajes = array_slice($viajes_ejemplo, 0, $limite);
?>

<section class="py-16 sm:py-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Encabezado de sección -->
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Encuentra el viaje perfecto para tu próximo desplazamiento
            </p>

            <!-- Filtros -->
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <button class="px-5 py-2.5 rounded-full bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 transition-colors">
                    Próximos
                </button>
                <button class="px-5 py-2.5 rounded-full bg-white text-gray-700 font-medium text-sm hover:bg-gray-100 transition-colors border border-gray-200">
                    Populares
                </button>
                <button class="px-5 py-2.5 rounded-full bg-white text-gray-700 font-medium text-sm hover:bg-gray-100 transition-colors border border-gray-200">
                    Económicos
                </button>
            </div>
        </div>

        <!-- Grid de viajes -->
        <div class="grid <?php echo esc_attr($clase_grid); ?> gap-6">
            <?php foreach ($viajes as $viaje): ?>
                <article class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group border border-gray-100 hover:border-blue-200 transform hover:-translate-y-1">

                    <!-- Header de la tarjeta -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>

                        <div class="relative flex items-start justify-between">
                            <?php if ($mostrar_avatares): ?>
                                <div class="flex items-center gap-3">
                                    <img
                                        src="<?php echo esc_url($viaje['conductor']['avatar']); ?>"
                                        alt="<?php echo esc_attr($viaje['conductor']['nombre']); ?>"
                                        class="w-12 h-12 rounded-full border-2 border-white shadow-lg"
                                    >
                                    <div class="text-white">
                                        <div class="font-semibold"><?php echo esc_html($viaje['conductor']['nombre']); ?></div>
                                        <div class="flex items-center gap-1 text-xs">
                                            <svg class="w-4 h-4 text-yellow-300 fill-current" viewBox="0 0 20 20">
                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                            </svg>
                                            <span><?php echo esc_html($viaje['conductor']['valoracion']); ?></span>
                                            <span class="text-blue-200">· <?php echo esc_html($viaje['conductor']['viajes_totales']); ?> viajes</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-1.5 text-white font-bold text-lg">
                                <?php echo esc_html(number_format($viaje['precio_por_plaza'], 2)); ?>€
                            </div>
                        </div>
                    </div>

                    <!-- Contenido de la tarjeta -->
                    <div class="p-5 space-y-4">

                        <!-- Ruta -->
                        <div class="space-y-3">
                            <!-- Origen -->
                            <div class="flex items-start gap-3">
                                <div class="mt-1 w-3 h-3 rounded-full bg-green-500 flex-shrink-0 ring-4 ring-green-100"></div>
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">Origen</div>
                                    <div class="font-semibold text-gray-900"><?php echo esc_html($viaje['origen']); ?></div>
                                </div>
                            </div>

                            <!-- Línea conectora -->
                            <div class="ml-1.5 w-0.5 h-6 bg-gradient-to-b from-green-200 to-red-200"></div>

                            <!-- Destino -->
                            <div class="flex items-start gap-3">
                                <div class="mt-1 flex-shrink-0">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">Destino</div>
                                    <div class="font-semibold text-gray-900"><?php echo esc_html($viaje['destino']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del viaje -->
                        <div class="flex items-center gap-4 pt-3 border-t border-gray-100">
                            <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><?php echo esc_html($viaje['fecha_hora']); ?></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span><?php echo esc_html($viaje['plazas_disponibles']); ?> plazas</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><?php echo esc_html($viaje['vehiculo']); ?></span>
                            </div>
                        </div>

                        <!-- Características -->
                        <div class="flex items-center gap-2 pt-3">
                            <?php if ($viaje['permite_mascotas']): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-medium">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"></path>
                                    </svg>
                                    Mascotas
                                </span>
                            <?php endif; ?>

                            <?php if ($viaje['permite_equipaje']): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Equipaje
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Botón de acción -->
                        <button class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform group-hover:scale-105 flex items-center justify-center gap-2 shadow-lg shadow-blue-500/30">
                            <span>Ver Detalles</span>
                            <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Ver más -->
        <div class="text-center mt-12">
            <button class="inline-flex items-center gap-2 px-8 py-4 bg-white hover:bg-gray-50 text-blue-600 font-semibold rounded-xl border-2 border-blue-600 transition-all duration-200 transform hover:scale-105">
                <span>Ver Todos los Viajes</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </button>
        </div>
    </div>
</section>
