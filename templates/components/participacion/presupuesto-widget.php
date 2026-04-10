<?php
/**
 * Template: Widget de Presupuesto Participativo
 *
 * Muestra la información del presupuesto disponible para participación ciudadana,
 * desglose por categorías y progreso de distribución.
 *
 * @package FlavorPlatform
 * @var array $args Variables pasadas al template
 */

if (!defined('ABSPATH')) exit;

// Configuración por defecto
$titulo = $args['titulo'] ?? 'Presupuesto Participativo 2025';
$subtitulo = $args['subtitulo'] ?? 'Presupuesto disponible para que decidas cómo invertirlo';
$presupuesto_total = $args['presupuesto_total'] ?? 500000;
$presupuesto_distribuido = $args['presupuesto_distribuido'] ?? 320000;
$moneda = $args['moneda'] ?? '€';
$mostrar_categorias = $args['mostrar_categorias'] ?? true;
$mostrar_historial = $args['mostrar_historial'] ?? true;
$clase_widget = $args['clase_widget'] ?? '';

// Categorías del presupuesto
$categorias = $args['categorias'] ?? [
    [
        'nombre' => 'Parques y Espacios Verdes',
        'presupuesto_asignado' => 85000,
        'presupuesto_total' => 120000,
        'icono' => 'tree',
        'color' => 'green',
        'propuestas' => 24,
        'descripcion' => 'Mejora de espacios naturales y sostenibilidad'
    ],
    [
        'nombre' => 'Infraestructura y Movilidad',
        'presupuesto_asignado' => 95000,
        'presupuesto_total' => 150000,
        'icono' => 'road',
        'color' => 'blue',
        'propuestas' => 31,
        'descripcion' => 'Carreteras, transportes y accesibilidad'
    ],
    [
        'nombre' => 'Educación y Cultura',
        'presupuesto_asignado' => 78000,
        'presupuesto_total' => 120000,
        'icono' => 'book',
        'color' => 'purple',
        'propuestas' => 19,
        'descripcion' => 'Programas educativos, museos y bibliotecas'
    ],
    [
        'nombre' => 'Salud y Bienestar',
        'presupuesto_asignado' => 62000,
        'presupuesto_total' => 110000,
        'icono' => 'heart',
        'color' => 'rose',
        'propuestas' => 22,
        'descripcion' => 'Centros de salud y programas sociales'
    ]
];

// Historial de votación
$historial_votacion = $args['historial_votacion'] ?? [
    [
        'titulo' => 'Mejora de parque central',
        'categoria' => 'Parques y Espacios Verdes',
        'votos' => 1243,
        'presupuesto' => 45000,
        'estado' => 'ganador',
        'fecha' => 'Hace 3 días'
    ],
    [
        'titulo' => 'Ciclovías en zona centro',
        'categoria' => 'Infraestructura y Movilidad',
        'votos' => 987,
        'presupuesto' => 35000,
        'estado' => 'ganador',
        'fecha' => 'Hace 3 días'
    ],
    [
        'titulo' => 'Biblioteca municipal renovada',
        'categoria' => 'Educación y Cultura',
        'votos' => 856,
        'presupuesto' => 40000,
        'estado' => 'ganador',
        'fecha' => 'Hace 2 días'
    ],
    [
        'titulo' => 'Centro de salud barrio sur',
        'categoria' => 'Salud y Bienestar',
        'votos' => 654,
        'presupuesto' => 30000,
        'estado' => 'ganador',
        'fecha' => 'Hace 1 día'
    ]
];

// Iconos SVG
$iconos = [
    'tree' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'road' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>',
    'book' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
    'heart' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>'
];

$colores_bg = [
    'green' => 'from-green-500 to-green-600',
    'blue' => 'from-blue-500 to-blue-600',
    'purple' => 'from-purple-500 to-purple-600',
    'rose' => 'from-rose-500 to-rose-600'
];

$colores_light = [
    'green' => 'bg-green-100 text-green-700',
    'blue' => 'bg-blue-100 text-blue-700',
    'purple' => 'bg-purple-100 text-purple-700',
    'rose' => 'bg-rose-100 text-rose-700'
];

// Calcular porcentaje distribuido
$porcentaje_distribuido = ($presupuesto_distribuido / $presupuesto_total) * 100;
$presupuesto_restante = $presupuesto_total - $presupuesto_distribuido;

?>

<section class="flavor-component py-12 md:py-16 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 <?php echo esc_attr($clase_widget); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">

        <!-- Header -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-2">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-slate-300">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Grid Principal -->
        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Columna Izquierda: Presupuesto General -->
            <div class="lg:col-span-1">
                <!-- Card Presupuesto Total -->
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl p-8 text-white shadow-xl overflow-hidden relative">
                    <!-- Decoración de fondo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-20 -mt-20"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold opacity-90">
                                <?php echo esc_html__('Presupuesto Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <svg class="w-6 h-6 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <div class="text-5xl font-bold mb-6">
                            <?php echo $moneda . number_format($presupuesto_total, 0, ',', '.'); ?>
                        </div>

                        <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm opacity-90"><?php echo esc_html__('Distribuido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="text-lg font-bold"><?php echo round($porcentaje_distribuido); ?>%</span>
                            </div>
                            <div class="w-full bg-white bg-opacity-20 rounded-full h-3 overflow-hidden">
                                <div class="bg-white h-full rounded-full transition-all duration-500" style="width: <?php echo $porcentaje_distribuido; ?>%"></div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-white border-opacity-20">
                            <div class="text-sm opacity-90 mb-1">
                                <?php echo esc_html__('Presupuesto Restante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div class="text-3xl font-bold">
                                <?php echo $moneda . number_format($presupuesto_restante, 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Información -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mt-6">
                    <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo esc_html__('¿Cómo Funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h4>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-3 mt-0.5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('Crea y vota propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-3 mt-0.5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('Elige dónde invertir el presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-3 mt-0.5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('Las propuestas más votadas se ejecutan', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Columna Derecha: Categorías y Historial -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Categorías del Presupuesto -->
                <?php if ($mostrar_categorias && !empty($categorias)): ?>
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v2a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                <?php echo esc_html__('Desglose por Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <?php foreach ($categorias as $categoria): ?>
                                <?php
                                $porcentaje_categoria = ($categoria['presupuesto_asignado'] / $categoria['presupuesto_total']) * 100;
                                $color_bg_clase = $colores_bg[$categoria['color']] ?? 'from-gray-500 to-gray-600';
                                $color_light_clase = $colores_light[$categoria['color']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-start gap-3 flex-1">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br <?php echo $color_bg_clase; ?> rounded-lg flex items-center justify-center text-white">
                                                <?php echo $iconos[$categoria['icono']] ?? $iconos['tree']; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-gray-900">
                                                    <?php echo esc_html($categoria['nombre']); ?>
                                                </h4>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    <?php echo esc_html($categoria['descripcion']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="<?php echo $color_light_clase; ?> text-xs font-semibold px-3 py-1 rounded-full">
                                                <?php echo $categoria['propuestas']; ?> <?php echo esc_html__('propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div class="ml-13 mb-2">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs font-medium text-gray-600">
                                                <?php echo $moneda . number_format($categoria['presupuesto_asignado'], 0, ',', '.'); ?>
                                            </span>
                                            <span class="text-xs font-medium text-gray-400">
                                                <?php echo $moneda . number_format($categoria['presupuesto_total'], 0, ',', '.'); ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-gradient-to-r <?php echo $color_bg_clase; ?> h-full rounded-full transition-all duration-500" style="width: <?php echo $porcentaje_categoria; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Historial de Votación -->
                <?php if ($mostrar_historial && !empty($historial_votacion)): ?>
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo esc_html__('Propuestas Ganadoras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                            <?php foreach ($historial_votacion as $item): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 truncate">
                                                <?php echo esc_html($item['titulo']); ?>
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <?php echo esc_html($item['categoria']); ?>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                <?php echo esc_html__('Ganador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 12a3 3 0 100-6 3 3 0 000 6z" />
                                                    <path fill-rule="evenodd" d="M17.338 14.59a.75.75 0 00-.527-1.345 6.5 6.5 0 10-9.622 0 .75.75 0 00-.527 1.345 8 8 0 1011.676 0z" clip-rule="evenodd" />
                                                </svg>
                                                <span><?php echo number_format($item['votos']); ?> <?php echo esc_html__('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span><?php echo $moneda . number_format($item['presupuesto'], 0, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                        <span class="text-gray-400"><?php echo esc_html($item['fecha']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</section>
