<?php
/**
 * Template: Mapa Interactivo de Huertos
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/HuertosUrbanos
 */

defined('ABSPATH') || exit;

// Sample data - Replace with actual data from database
$huertos_ubicaciones = [
    [
        'id' => 1,
        'nombre' => 'Huerto Norte',
        'direccion' => 'Calle de los Jardines, 15',
        'parcelas_totales' => 20,
        'parcelas_disponibles' => 5,
        'latitud' => 42.8467,
        'longitud' => -2.6724,
        'servicios' => ['agua', 'herramientas', 'compost'],
    ],
    [
        'id' => 2,
        'nombre' => 'Huerto Central',
        'direccion' => 'Plaza Verde, 8',
        'parcelas_totales' => 15,
        'parcelas_disponibles' => 2,
        'latitud' => 42.8487,
        'longitud' => -2.6744,
        'servicios' => ['agua', 'herramientas', 'caseta'],
    ],
    [
        'id' => 3,
        'nombre' => 'Huerto Sur',
        'direccion' => 'Avenida del Campo, 32',
        'parcelas_totales' => 25,
        'parcelas_disponibles' => 8,
        'latitud' => 42.8447,
        'longitud' => -2.6704,
        'servicios' => ['agua', 'compost', 'parking'],
    ],
    [
        'id' => 4,
        'nombre' => 'Huerto Este',
        'direccion' => 'Camino del Sol, 5',
        'parcelas_totales' => 12,
        'parcelas_disponibles' => 0,
        'latitud' => 42.8477,
        'longitud' => -2.6684,
        'servicios' => ['agua', 'herramientas'],
    ],
];
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="text-center mb-10 sm:mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Ubicaciones</span>
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Mapa de Huertos Urbanos
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Encuentra el huerto más cercano a ti y consulta la disponibilidad de parcelas en cada ubicación
            </p>
        </div>

        <!-- Map and Locations Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Side: Locations List -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Huertos disponibles
                    </h3>

                    <!-- Filter -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por disponibilidad</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            <option value="all">Todos los huertos</option>
                            <option value="available">Con parcelas disponibles</option>
                            <option value="full">Completos</option>
                        </select>
                    </div>

                    <!-- Locations List -->
                    <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                        <?php foreach ($huertos_ubicaciones as $huerto): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-green-500 hover:shadow-md transition-all duration-200 cursor-pointer group"
                                 data-huerto-id="<?php echo esc_attr($huerto['id']); ?>">

                                <!-- Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 group-hover:text-green-600 transition-colors">
                                            <?php echo esc_html($huerto['nombre']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <?php echo esc_html($huerto['direccion']); ?>
                                        </p>
                                    </div>
                                    <div class="w-10 h-10 bg-green-100 group-hover:bg-green-600 rounded-full flex items-center justify-center transition-colors">
                                        <svg class="w-5 h-5 text-green-600 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L4 7v10c0 5 8 9 8 9s8-4 8-9V7l-8-5z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Availability -->
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm text-gray-600">Disponibilidad:</span>
                                    <span class="font-semibold <?php echo $huerto['parcelas_disponibles'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo esc_html($huerto['parcelas_disponibles']); ?> / <?php echo esc_html($huerto['parcelas_totales']); ?> parcelas
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                    <?php
                                    $porcentaje_ocupacion = (($huerto['parcelas_totales'] - $huerto['parcelas_disponibles']) / $huerto['parcelas_totales']) * 100;
                                    ?>
                                    <div class="<?php echo $porcentaje_ocupacion < 70 ? 'bg-green-600' : ($porcentaje_ocupacion < 90 ? 'bg-yellow-600' : 'bg-red-600'); ?> h-2 rounded-full transition-all duration-300"
                                         style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%"></div>
                                </div>

                                <!-- Services -->
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($huerto['servicios'] as $servicio): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-md">
                                            <?php
                                            $icono_servicio = '';
                                            switch($servicio) {
                                                case 'agua':
                                                    $icono_servicio = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>';
                                                    break;
                                                case 'herramientas':
                                                    $icono_servicio = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M22 9v2l-10 10l-5.5-5.5L7 15l4 4l9-9V9h2z"/></svg>';
                                                    break;
                                                case 'compost':
                                                    $icono_servicio = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>';
                                                    break;
                                                case 'caseta':
                                                    $icono_servicio = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
                                                    break;
                                                case 'parking':
                                                    $icono_servicio = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M13 3H6v18h4v-6h3c3.3 0 6-2.7 6-6s-2.7-6-6-6zm0 8h-3V7h3c1.7 0 3 1.3 3 3s-1.3 3-3 3z"/></svg>';
                                                    break;
                                            }
                                            echo $icono_servicio;
                                            ?>
                                            <?php echo esc_html(ucfirst($servicio)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <!-- CTA Button -->
                                <?php if ($huerto['parcelas_disponibles'] > 0): ?>
                                    <button class="w-full mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors duration-200">
                                        Reservar parcela
                                    </button>
                                <?php else: ?>
                                    <button class="w-full mt-4 px-4 py-2 bg-gray-300 text-gray-600 text-sm font-semibold rounded-lg cursor-not-allowed" disabled>
                                        Sin disponibilidad
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side: Map -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <!-- Map Container -->
                    <div class="relative h-[400px] lg:h-[700px] bg-gradient-to-br from-green-100 to-emerald-100">
                        <!-- Placeholder Map - Replace with actual map implementation (Google Maps, Leaflet, etc.) -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center p-8">
                                <svg class="w-24 h-24 mx-auto text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <p class="text-gray-600 text-lg font-medium">Mapa interactivo</p>
                                <p class="text-gray-500 text-sm mt-2">Integrar con Google Maps o Leaflet</p>
                            </div>
                        </div>

                        <!-- Map Markers Simulation -->
                        <?php foreach ($huertos_ubicaciones as $index => $huerto): ?>
                            <div class="absolute group cursor-pointer"
                                 style="top: <?php echo 20 + ($index * 20); ?>%; left: <?php echo 20 + ($index * 15); ?>%;"
                                 data-huerto-id="<?php echo esc_attr($huerto['id']); ?>">
                                <!-- Marker -->
                                <div class="relative">
                                    <svg class="w-10 h-10 <?php echo $huerto['parcelas_disponibles'] > 0 ? 'text-green-600' : 'text-red-600'; ?> drop-shadow-lg hover:scale-110 transition-transform duration-200"
                                         fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                    </svg>

                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                                        <div class="bg-gray-900 text-white text-sm rounded-lg py-2 px-4 whitespace-nowrap shadow-xl">
                                            <p class="font-semibold"><?php echo esc_html($huerto['nombre']); ?></p>
                                            <p class="text-xs text-gray-300 mt-1">
                                                <?php echo esc_html($huerto['parcelas_disponibles']); ?> parcelas disponibles
                                            </p>
                                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Map Controls -->
                        <div class="absolute top-4 right-4 flex flex-col gap-2">
                            <button class="bg-white p-3 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>
                            <button class="bg-white p-3 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <button class="bg-white p-3 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Legend -->
                        <div class="absolute bottom-4 left-4 bg-white rounded-lg shadow-md p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Leyenda</h4>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-xs text-gray-700">Parcelas disponibles</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-xs text-gray-700">Sin disponibilidad</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Info -->
                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="font-semibold text-blue-900 mb-1">Visita antes de reservar</h4>
                            <p class="text-sm text-blue-800">
                                Te recomendamos visitar el huerto antes de reservar tu parcela. Contacta con nosotros para concertar una visita guiada gratuita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Summary Stats -->
        <div class="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2">4</div>
                <div class="text-sm text-gray-600">Huertos activos</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2">72</div>
                <div class="text-sm text-gray-600">Parcelas totales</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2">15</div>
                <div class="text-sm text-gray-600">Parcelas disponibles</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2">79%</div>
                <div class="text-sm text-gray-600">Ocupación media</div>
            </div>
        </div>

    </div>
</section>

<script>
// Add interactivity for map markers and list items
document.addEventListener('DOMContentLoaded', function() {
    // Sync hover between list items and map markers
    const huertoItems = document.querySelectorAll('[data-huerto-id]');

    huertoItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const huertoId = this.getAttribute('data-huerto-id');
            // Highlight corresponding marker or list item
            huertoItems.forEach(el => {
                if (el.getAttribute('data-huerto-id') === huertoId) {
                    el.classList.add('ring-2', 'ring-green-500');
                }
            });
        });

        item.addEventListener('mouseleave', function() {
            huertoItems.forEach(el => {
                el.classList.remove('ring-2', 'ring-green-500');
            });
        });
    });
});
</script>
