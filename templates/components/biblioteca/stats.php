<?php
/**
 * Template: Estadísticas de la Biblioteca
 *
 * Muestra estadísticas generales, gráficos de actividad y datos sobre
 * la colección de la biblioteca.
 *
 * @package FlavorChatIA
 * @var array $args Variables pasadas al template
 */

if (!defined('ABSPATH')) exit;

// Configuración por defecto
$titulo = $args['titulo'] ?? 'Estadísticas de Nuestra Biblioteca';
$subtitulo = $args['subtitulo'] ?? 'Datos y métricas del sistema de préstamos';
$mostrar_metricas_clave = $args['mostrar_metricas_clave'] ?? true;
$mostrar_actividad = $args['mostrar_actividad'] ?? true;
$mostrar_generos = $args['mostrar_generos'] ?? true;
$clase_componente = $args['clase_componente'] ?? '';

// Métricas clave
$metricas = $args['metricas'] ?? [
    [
        'numero' => '5,240',
        'texto' => 'Total de Libros',
        'icono' => 'book',
        'color' => 'blue',
        'cambio' => '+45 este mes'
    ],
    [
        'numero' => '1,348',
        'texto' => 'Préstamos Activos',
        'icono' => 'arrow-right',
        'color' => 'green',
        'cambio' => '+156 esta semana'
    ],
    [
        'numero' => '892',
        'texto' => 'Usuarios Activos',
        'icono' => 'users',
        'color' => 'purple',
        'cambio' => '+23 este mes'
    ],
    [
        'numero' => '456',
        'texto' => 'Reservas Pendientes',
        'icono' => 'bookmark',
        'color' => 'amber',
        'cambio' => '+12 esta semana'
    ],
    [
        'numero' => '3,892',
        'texto' => 'Devoluciones Exitosas',
        'icono' => 'check-circle',
        'color' => 'teal',
        'cambio' => '99.2% tasa'
    ],
    [
        'numero' => '28',
        'texto' => 'Géneros Diferentes',
        'icono' => 'collection',
        'color' => 'rose',
        'cambio' => 'Completamente cubiertos'
    ]
];

// Actividad reciente por género
$actividad_generos = $args['actividad_generos'] ?? [
    ['nombre' => 'Fantasía', 'prestamos' => 245, 'devoluciones' => 218, 'reservas' => 34],
    ['nombre' => 'Ficción Contemporánea', 'prestamos' => 189, 'devoluciones' => 176, 'reservas' => 28],
    ['nombre' => 'Misterio y Thriller', 'prestamos' => 167, 'devoluciones' => 152, 'reservas' => 22],
    ['nombre' => 'Ciencia Ficción', 'prestamos' => 143, 'devoluciones' => 131, 'reservas' => 18],
    ['nombre' => 'Romance', 'prestamos' => 128, 'devoluciones' => 115, 'reservas' => 15],
];

// Tendencias mensuales (últimos 6 meses)
$tendencias_mensuales = $args['tendencias_mensuales'] ?? [
    ['mes' => 'Agosto', 'prestamos' => 245, 'devoluciones' => 238, 'nuevos_usuarios' => 12],
    ['mes' => 'Septiembre', 'prestamos' => 289, 'devoluciones' => 276, 'nuevos_usuarios' => 18],
    ['mes' => 'Octubre', 'prestamos' => 312, 'devoluciones' => 305, 'nuevos_usuarios' => 24],
    ['mes' => 'Noviembre', 'prestamos' => 356, 'devoluciones' => 341, 'nuevos_usuarios' => 31],
    ['mes' => 'Diciembre', 'prestamos' => 389, 'devoluciones' => 378, 'nuevos_usuarios' => 42],
    ['mes' => 'Enero', 'prestamos' => 421, 'devoluciones' => 412, 'nuevos_usuarios' => 38],
];

// Iconos SVG
$iconos = [
    'book' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
    'arrow-right' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>',
    'users' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
    'bookmark' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>',
    'check-circle' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'collection' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v2a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>'
];

// Colores para las tarjetas
$colores_bg = [
    'blue' => 'from-blue-500 to-blue-600',
    'green' => 'from-green-500 to-green-600',
    'purple' => 'from-purple-500 to-purple-600',
    'amber' => 'from-amber-500 to-amber-600',
    'teal' => 'from-teal-500 to-teal-600',
    'rose' => 'from-rose-500 to-rose-600'
];

// Calcular total máximo para gráfico
$max_prestamos = max(array_column($tendencias_mensuales, 'prestamos'));

?>

<section class="flavor-component py-16 md:py-20 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 <?php echo esc_attr($clase_componente); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">

        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-sm font-medium mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <?php echo esc_html__('Panel de Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </div>

        <!-- Métricas Clave -->
        <?php if ($mostrar_metricas_clave && !empty($metricas)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-10">
                <?php foreach ($metricas as $metrica): ?>
                    <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br <?php echo $colores_bg[$metrica['color']] ?? 'from-gray-500 to-gray-600'; ?> rounded-xl">
                                <?php echo $iconos[$metrica['icono']] ?? $iconos['book']; ?>
                            </div>
                            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">
                                <?php echo esc_html($metrica['cambio']); ?>
                            </span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
                            <?php echo esc_html($metrica['numero']); ?>
                        </div>
                        <div class="text-xs md:text-sm text-gray-500">
                            <?php echo esc_html($metrica['texto']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Grid Principal: Gráficos y Actividad -->
        <div class="grid lg:grid-cols-3 gap-8 mb-10">

            <!-- Gráfico de Tendencias (2 columnas) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <?php echo esc_html__('Tendencia de Préstamos (Últimos 6 Meses)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                </div>

                <div class="space-y-6">
                    <?php foreach ($tendencias_mensuales as $dato): ?>
                        <?php $porcentaje = ($dato['prestamos'] / $max_prestamos) * 100; ?>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700"><?php echo esc_html($dato['mes']); ?></span>
                                <span class="text-sm font-bold text-indigo-600"><?php echo number_format($dato['prestamos']); ?> <?php echo esc_html__('préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="relative h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-500" style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Estadísticas resumen -->
                <div class="grid grid-cols-2 gap-4 mt-8 pt-8 border-t border-gray-200">
                    <?php
                    $total_prestamos_semestre = array_sum(array_column($tendencias_mensuales, 'prestamos'));
                    $total_devoluciones_semestre = array_sum(array_column($tendencias_mensuales, 'devoluciones'));
                    $total_nuevos_usuarios_semestre = array_sum(array_column($tendencias_mensuales, 'nuevos_usuarios'));
                    $tasa_devolucion = ($total_devoluciones_semestre / $total_prestamos_semestre) * 100;
                    ?>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600 mb-1">
                            <?php echo number_format($total_prestamos_semestre); ?>
                        </div>
                        <p class="text-xs text-gray-600"><?php echo esc_html__('Total Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 mb-1">
                            <?php echo round($tasa_devolucion, 1); ?>%
                        </div>
                        <p class="text-xs text-gray-600"><?php echo esc_html__('Tasa Devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </div>

            <!-- Actividad por Género -->
            <?php if ($mostrar_generos && !empty($actividad_generos)): ?>
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center mb-6">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v2a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                        <?php echo esc_html__('Top Géneros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>

                    <div class="space-y-4">
                        <?php foreach ($actividad_generos as $genero): ?>
                            <div class="bg-gradient-to-r from-slate-50 to-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900"><?php echo esc_html($genero['nombre']); ?></h4>
                                    <span class="text-sm font-bold text-purple-600"><?php echo number_format($genero['prestamos']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <?php echo number_format($genero['devoluciones']); ?>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                        <?php echo number_format($genero['reservas']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Información Adicional -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-lg text-white p-8 md:p-12">
            <div class="grid md:grid-cols-2 gap-8">

                <div>
                    <h3 class="text-2xl font-bold mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <?php echo esc_html__('Nuestro Compromiso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="text-indigo-100 leading-relaxed">
                        <?php echo esc_html__('Nos comprometemos a proporcionar acceso equitativo a la información y la lectura, promoviendo la educación y el crecimiento personal de nuestra comunidad a través de un sistema de préstamos eficiente y accesible.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-2xl font-bold mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo esc_html__('Logros Este Año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <ul class="space-y-2 text-indigo-100">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('1,245 nuevos libros adquiridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('3,450 nuevos usuarios registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span><?php echo esc_html__('99.8% tasa de satisfacción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

    </div>
</section>
