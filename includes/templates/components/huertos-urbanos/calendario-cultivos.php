<?php
/**
 * Template: Calendario de Cultivos
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/HuertosUrbanos
 */

defined('ABSPATH') || exit;

// Cultivos data - Replace with actual data from database
$cultivos_data = [
    'tomates' => [
        'nombre' => 'Tomates',
        'icono' => 'tomato',
        'color' => 'red',
        'siembra' => [3, 4, 5],
        'trasplante' => [5, 6],
        'cosecha' => [7, 8, 9],
        'dificultad' => 'media',
        'consejos' => 'Necesitan mucho sol y riego regular',
    ],
    'lechugas' => [
        'nombre' => 'Lechugas',
        'icono' => 'lettuce',
        'color' => 'green',
        'siembra' => [2, 3, 4, 9, 10],
        'trasplante' => [3, 4, 5, 10, 11],
        'cosecha' => [4, 5, 6, 11, 12],
        'dificultad' => 'facil',
        'consejos' => 'Crecimiento rápido, ideal para principiantes',
    ],
    'zanahorias' => [
        'nombre' => 'Zanahorias',
        'icono' => 'carrot',
        'color' => 'orange',
        'siembra' => [3, 4, 5, 6],
        'trasplante' => [],
        'cosecha' => [7, 8, 9, 10],
        'dificultad' => 'media',
        'consejos' => 'Requieren suelo profundo y suelto',
    ],
    'pimientos' => [
        'nombre' => 'Pimientos',
        'icono' => 'pepper',
        'color' => 'yellow',
        'siembra' => [2, 3, 4],
        'trasplante' => [5, 6],
        'cosecha' => [7, 8, 9],
        'dificultad' => 'media',
        'consejos' => 'Sensibles al frío, plantar después de heladas',
    ],
    'calabacines' => [
        'nombre' => 'Calabacines',
        'icono' => 'zucchini',
        'color' => 'lime',
        'siembra' => [4, 5, 6],
        'trasplante' => [5, 6, 7],
        'cosecha' => [6, 7, 8, 9],
        'dificultad' => 'facil',
        'consejos' => 'Producción abundante, necesitan espacio',
    ],
    'cebollas' => [
        'nombre' => 'Cebollas',
        'icono' => 'onion',
        'color' => 'purple',
        'siembra' => [2, 3, 4, 9, 10],
        'trasplante' => [3, 4, 10, 11],
        'cosecha' => [6, 7, 8],
        'dificultad' => 'facil',
        'consejos' => 'Cultivo de larga duración, bajo mantenimiento',
    ],
    'fresas' => [
        'nombre' => 'Fresas',
        'icono' => 'strawberry',
        'color' => 'pink',
        'siembra' => [8, 9],
        'trasplante' => [9, 10],
        'cosecha' => [4, 5, 6],
        'dificultad' => 'media',
        'consejos' => 'Plantar en otoño para cosechar en primavera',
    ],
    'hierbas' => [
        'nombre' => 'Hierbas aromáticas',
        'icono' => 'herbs',
        'color' => 'teal',
        'siembra' => [3, 4, 5, 6],
        'trasplante' => [4, 5, 6, 7],
        'cosecha' => [5, 6, 7, 8, 9, 10],
        'dificultad' => 'facil',
        'consejos' => 'Fáciles de cultivar, perfectas en macetas',
    ],
];

$meses = [
    1 => 'Ene',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Abr',
    5 => 'May',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ago',
    9 => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dic',
];

$mes_actual = (int) date('n');
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-br from-green-50 via-white to-emerald-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="text-center mb-10 sm:mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Planificación</span>
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Calendario de Cultivos
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Descubre cuándo sembrar, trasplantar y cosechar cada cultivo para aprovechar al máximo tu huerto
            </p>
        </div>

        <!-- Current Month Highlight -->
        <div class="mb-8 bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-left">
                    <h3 class="text-2xl font-bold mb-2">Estamos en <?php echo $meses[$mes_actual]; ?></h3>
                    <p class="text-green-100">Te mostramos qué puedes hacer este mes en tu huerto</p>
                </div>
                <div class="flex items-center gap-4">
                    <button class="p-3 bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="text-center">
                        <div class="text-4xl font-bold"><?php echo $mes_actual; ?></div>
                        <div class="text-sm text-green-100">Mes</div>
                    </div>
                    <button class="p-3 bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter and View Options -->
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium shadow-md">
                    Todos los cultivos
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Fáciles
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    De temporada
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Vista:</span>
                <button class="p-2 bg-green-100 text-green-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
                <button class="p-2 bg-white text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Calendar Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-4 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-700 uppercase">Cultivo</span>
                                </div>
                            </th>
                            <?php foreach ($meses as $num_mes => $nombre_mes): ?>
                                <th class="px-2 py-4 text-center min-w-[60px]">
                                    <div class="<?php echo $num_mes === $mes_actual ? 'text-green-600 font-bold' : 'text-gray-600'; ?>">
                                        <div class="text-sm font-semibold"><?php echo $nombre_mes; ?></div>
                                        <?php if ($num_mes === $mes_actual): ?>
                                            <div class="mt-1 w-2 h-2 bg-green-600 rounded-full mx-auto"></div>
                                        <?php endif; ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($cultivos_data as $cultivo_key => $cultivo): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <!-- Cultivo Name Column -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-<?php echo $cultivo['color']; ?>-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <span class="text-2xl">
                                                <?php
                                                $iconos = [
                                                    'tomato' => '🍅',
                                                    'lettuce' => '🥬',
                                                    'carrot' => '🥕',
                                                    'pepper' => '🫑',
                                                    'zucchini' => '🥒',
                                                    'onion' => '🧅',
                                                    'strawberry' => '🍓',
                                                    'herbs' => '🌿',
                                                ];
                                                echo $iconos[$cultivo['icono']] ?? '🌱';
                                                ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900"><?php echo esc_html($cultivo['nombre']); ?></div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                Dificultad:
                                                <span class="<?php echo $cultivo['dificultad'] === 'facil' ? 'text-green-600' : ($cultivo['dificultad'] === 'media' ? 'text-yellow-600' : 'text-red-600'); ?> font-medium">
                                                    <?php echo $cultivo['dificultad'] === 'facil' ? 'Fácil' : ($cultivo['dificultad'] === 'media' ? 'Media' : 'Difícil'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Month Columns -->
                                <?php foreach (range(1, 12) as $mes): ?>
                                    <td class="px-2 py-4 text-center">
                                        <div class="flex flex-col gap-1">
                                            <!-- Siembra -->
                                            <?php if (in_array($mes, $cultivo['siembra'])): ?>
                                                <div class="group relative">
                                                    <div class="w-8 h-8 mx-auto bg-blue-500 rounded-md flex items-center justify-center cursor-pointer hover:bg-blue-600 transition-colors shadow-sm">
                                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                    </div>
                                                    <div class="hidden group-hover:block absolute z-10 -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                                        Siembra
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Trasplante -->
                                            <?php if (in_array($mes, $cultivo['trasplante'])): ?>
                                                <div class="group relative">
                                                    <div class="w-8 h-8 mx-auto bg-purple-500 rounded-md flex items-center justify-center cursor-pointer hover:bg-purple-600 transition-colors shadow-sm">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                        </svg>
                                                    </div>
                                                    <div class="hidden group-hover:block absolute z-10 -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                                        Trasplante
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Cosecha -->
                                            <?php if (in_array($mes, $cultivo['cosecha'])): ?>
                                                <div class="group relative">
                                                    <div class="w-8 h-8 mx-auto bg-green-500 rounded-md flex items-center justify-center cursor-pointer hover:bg-green-600 transition-colors shadow-sm">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <div class="hidden group-hover:block absolute z-10 -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                                        Cosecha
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Leyenda</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-md flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Siembra</div>
                        <div class="text-xs text-gray-500">Plantar semillas</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-500 rounded-md flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Trasplante</div>
                        <div class="text-xs text-gray-500">Mover al huerto</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-md flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">Cosecha</div>
                        <div class="text-xs text-gray-500">Recolectar frutos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Crop Cards -->
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Cultivos destacados este mes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $cultivos_destacados = array_filter($cultivos_data, function($cultivo) use ($mes_actual) {
                    return in_array($mes_actual, $cultivo['siembra']) ||
                           in_array($mes_actual, $cultivo['trasplante']) ||
                           in_array($mes_actual, $cultivo['cosecha']);
                });

                $contador = 0;
                foreach ($cultivos_destacados as $cultivo):
                    if ($contador >= 3) break;
                    $contador++;
                ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-2 border-gray-200 hover:border-<?php echo $cultivo['color']; ?>-500">
                        <!-- Header -->
                        <div class="bg-gradient-to-br from-<?php echo $cultivo['color']; ?>-100 to-<?php echo $cultivo['color']; ?>-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-16 h-16 bg-white rounded-xl flex items-center justify-center shadow-md">
                                    <span class="text-4xl">
                                        <?php
                                        $iconos = [
                                            'tomato' => '🍅',
                                            'lettuce' => '🥬',
                                            'carrot' => '🥕',
                                            'pepper' => '🫑',
                                            'zucchini' => '🥒',
                                            'onion' => '🧅',
                                            'strawberry' => '🍓',
                                            'herbs' => '🌿',
                                        ];
                                        echo $iconos[$cultivo['icono']] ?? '🌱';
                                        ?>
                                    </span>
                                </div>
                                <div class="px-3 py-1 bg-white rounded-full text-xs font-semibold text-<?php echo $cultivo['color']; ?>-600">
                                    <?php echo $cultivo['dificultad'] === 'facil' ? 'Fácil' : ($cultivo['dificultad'] === 'media' ? 'Media' : 'Difícil'); ?>
                                </div>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900"><?php echo esc_html($cultivo['nombre']); ?></h4>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <!-- Actions this month -->
                            <div class="mb-4">
                                <h5 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Tareas de <?php echo $meses[$mes_actual]; ?></h5>
                                <div class="space-y-2">
                                    <?php if (in_array($mes_actual, $cultivo['siembra'])): ?>
                                        <div class="flex items-center gap-2 text-sm text-gray-700 bg-blue-50 px-3 py-2 rounded-lg">
                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <span>Época de siembra</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (in_array($mes_actual, $cultivo['trasplante'])): ?>
                                        <div class="flex items-center gap-2 text-sm text-gray-700 bg-purple-50 px-3 py-2 rounded-lg">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                            <span>Trasplantar al huerto</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (in_array($mes_actual, $cultivo['cosecha'])): ?>
                                        <div class="flex items-center gap-2 text-sm text-gray-700 bg-green-50 px-3 py-2 rounded-lg">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Momento de cosecha</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tips -->
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-700 mb-1">Consejo</div>
                                        <div class="text-sm text-gray-600"><?php echo esc_html($cultivo['consejos']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <button class="w-full px-4 py-3 bg-<?php echo $cultivo['color']; ?>-600 hover:bg-<?php echo $cultivo['color']; ?>-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                                Ver guía completa
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Download and Share Section -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl p-8 text-white shadow-xl">
            <div class="text-center max-w-3xl mx-auto">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-2xl font-bold mb-3">Descarga el calendario completo</h3>
                <p class="text-green-100 mb-6">
                    Obtén el calendario en PDF para imprimirlo y tenerlo siempre a mano en tu huerto
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="px-8 py-3 bg-white hover:bg-gray-100 text-green-600 font-semibold rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar PDF
                    </button>
                    <button class="px-8 py-3 bg-green-500 hover:bg-green-400 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        Compartir
                    </button>
                </div>
            </div>
        </div>

    </div>
</section>
