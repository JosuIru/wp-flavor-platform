<?php
/**
 * Talleres Grid Template
 *
 * Displays a grid of upcoming workshops with filtering and sorting options
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Talleres
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Default values
$talleres_lista = $args['talleres'] ?? [];
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$columnas = $args['columnas'] ?? 3;
$titulo_seccion = $args['titulo'] ?? 'Próximos Talleres';
$subtitulo_seccion = $args['subtitulo'] ?? 'Explora nuestros talleres y reserva tu plaza';
$mostrar_paginacion = $args['mostrar_paginacion'] ?? true;

// Column classes based on grid configuration
$columnas_clases = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-2 lg:grid-cols-3',
    4 => 'md:grid-cols-2 lg:grid-cols-4',
];
$grid_clase = $columnas_clases[$columnas] ?? $columnas_clases[3];
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>
        </div>

        <?php if ($mostrar_filtros): ?>
        <!-- Filter and Sort Bar -->
        <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                <!-- Filter Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Todos
                    </button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Esta Semana
                    </button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Este Mes
                    </button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Plazas Disponibles
                    </button>
                </div>

                <!-- Sort Dropdown -->
                <div class="flex items-center gap-2">
                    <label for="ordenar-talleres" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                        Ordenar por:
                    </label>
                    <select id="ordenar-talleres" class="block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 rounded-lg">
                        <option>Fecha próxima</option>
                        <option>Más popular</option>
                        <option>Plazas disponibles</option>
                        <option>Alfabético</option>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Talleres Grid -->
        <div class="grid grid-cols-1 <?php echo esc_attr($grid_clase); ?> gap-6 lg:gap-8">
            <?php
            // Sample workshops if none provided
            if (empty($talleres_lista)) {
                $talleres_lista = [
                    [
                        'id' => 1,
                        'titulo' => 'Introducción a la Permacultura',
                        'descripcion' => 'Aprende los principios básicos del diseño permacultural y cómo aplicarlos en tu huerto o jardín.',
                        'instructor' => 'María González',
                        'fecha' => '15 Feb 2026',
                        'hora' => '10:00 - 13:00',
                        'duracion' => '3 horas',
                        'nivel' => 'Principiante',
                        'plazas_disponibles' => 8,
                        'plazas_totales' => 12,
                        'precio' => 'Gratuito',
                        'ubicacion' => 'Huerto Comunitario',
                        'categoria' => 'Agricultura',
                        'icono' => 'plant',
                    ],
                    [
                        'id' => 2,
                        'titulo' => 'Cestería con Materiales Locales',
                        'descripcion' => 'Descubre técnicas tradicionales de cestería utilizando mimbre y otros materiales naturales de la zona.',
                        'instructor' => 'Juan Martínez',
                        'fecha' => '18 Feb 2026',
                        'hora' => '16:00 - 19:00',
                        'duracion' => '3 horas',
                        'nivel' => 'Intermedio',
                        'plazas_disponibles' => 3,
                        'plazas_totales' => 10,
                        'precio' => '15€',
                        'ubicacion' => 'Taller Artesanal',
                        'categoria' => 'Artesanía',
                        'icono' => 'basket',
                    ],
                    [
                        'id' => 3,
                        'titulo' => 'Energía Solar para el Hogar',
                        'descripcion' => 'Aprende a calcular tus necesidades energéticas y cómo diseñar un sistema solar básico para tu hogar.',
                        'instructor' => 'Carlos Ruiz',
                        'fecha' => '22 Feb 2026',
                        'hora' => '11:00 - 14:00',
                        'duracion' => '3 horas',
                        'nivel' => 'Principiante',
                        'plazas_disponibles' => 12,
                        'plazas_totales' => 15,
                        'precio' => 'Gratuito',
                        'ubicacion' => 'Centro Comunitario',
                        'categoria' => 'Tecnología',
                        'icono' => 'sun',
                    ],
                    [
                        'id' => 4,
                        'titulo' => 'Conservas y Fermentados',
                        'descripcion' => 'Técnicas de conservación de alimentos: desde mermeladas hasta kimchi y chucrut casero.',
                        'instructor' => 'Ana López',
                        'fecha' => '25 Feb 2026',
                        'hora' => '10:00 - 14:00',
                        'duracion' => '4 horas',
                        'nivel' => 'Todos los niveles',
                        'plazas_disponibles' => 6,
                        'plazas_totales' => 8,
                        'precio' => '20€',
                        'ubicacion' => 'Cocina Comunitaria',
                        'categoria' => 'Alimentación',
                        'icono' => 'jar',
                    ],
                    [
                        'id' => 5,
                        'titulo' => 'Carpintería Básica',
                        'descripcion' => 'Aprende las herramientas y técnicas básicas de carpintería para reparar y crear muebles sencillos.',
                        'instructor' => 'Pedro Sánchez',
                        'fecha' => '28 Feb 2026',
                        'hora' => '15:00 - 19:00',
                        'duracion' => '4 horas',
                        'nivel' => 'Principiante',
                        'plazas_disponibles' => 5,
                        'plazas_totales' => 8,
                        'precio' => '25€',
                        'ubicacion' => 'Taller de Carpintería',
                        'categoria' => 'Oficios',
                        'icono' => 'hammer',
                    ],
                    [
                        'id' => 6,
                        'titulo' => 'Huerto Urbano en Macetas',
                        'descripcion' => 'Crea tu propio huerto en espacios pequeños: balcones, terrazas y patios urbanos.',
                        'instructor' => 'Laura Fernández',
                        'fecha' => '03 Mar 2026',
                        'hora' => '10:00 - 12:00',
                        'duracion' => '2 horas',
                        'nivel' => 'Principiante',
                        'plazas_disponibles' => 10,
                        'plazas_totales' => 15,
                        'precio' => 'Gratuito',
                        'ubicacion' => 'Azotea Comunitaria',
                        'categoria' => 'Agricultura',
                        'icono' => 'plant',
                    ],
                ];
            }

            // Icon mapping
            $iconos_svg = [
                'plant' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
                'basket' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
                'sun' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />',
                'jar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
                'hammer' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />',
            ];

            foreach ($talleres_lista as $taller):
                $taller_id = $taller['id'] ?? 0;
                $taller_titulo = $taller['titulo'] ?? 'Taller sin título';
                $taller_descripcion = $taller['descripcion'] ?? '';
                $taller_instructor = $taller['instructor'] ?? 'Instructor';
                $taller_fecha = $taller['fecha'] ?? '';
                $taller_hora = $taller['hora'] ?? '';
                $taller_duracion = $taller['duracion'] ?? '';
                $taller_nivel = $taller['nivel'] ?? 'Todos los niveles';
                $plazas_disponibles = $taller['plazas_disponibles'] ?? 0;
                $plazas_totales = $taller['plazas_totales'] ?? 0;
                $taller_precio = $taller['precio'] ?? 'Gratuito';
                $taller_ubicacion = $taller['ubicacion'] ?? '';
                $taller_categoria = $taller['categoria'] ?? 'General';
                $icono_key = $taller['icono'] ?? 'plant';
                $icono_svg = $iconos_svg[$icono_key] ?? $iconos_svg['plant'];

                // Calculate availability percentage
                $porcentaje_disponibilidad = $plazas_totales > 0 ? ($plazas_disponibles / $plazas_totales) * 100 : 0;
                $clase_disponibilidad = $porcentaje_disponibilidad > 50 ? 'bg-green-500' : ($porcentaje_disponibilidad > 20 ? 'bg-amber-500' : 'bg-red-500');
            ?>

            <!-- Workshop Card -->
            <article class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-200 flex flex-col">
                <!-- Card Header -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white relative">
                    <!-- Category Badge -->
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm">
                            <?php echo esc_html($taller_categoria); ?>
                        </span>
                    </div>

                    <!-- Icon -->
                    <div class="flex items-center justify-center w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $icono_svg; ?>
                        </svg>
                    </div>

                    <!-- Title -->
                    <h3 class="text-xl font-bold mb-2 line-clamp-2">
                        <?php echo esc_html($taller_titulo); ?>
                    </h3>

                    <!-- Instructor -->
                    <div class="flex items-center gap-2 text-emerald-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-sm"><?php echo esc_html($taller_instructor); ?></span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 flex-1 flex flex-col">
                    <!-- Description -->
                    <p class="text-gray-600 text-sm leading-relaxed mb-6 line-clamp-3">
                        <?php echo esc_html($taller_descripcion); ?>
                    </p>

                    <!-- Details Grid -->
                    <div class="space-y-3 mb-6">
                        <!-- Date & Time -->
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($taller_fecha); ?></div>
                                <div class="text-xs text-gray-500"><?php echo esc_html($taller_hora); ?></div>
                            </div>
                        </div>

                        <!-- Location -->
                        <?php if ($taller_ubicacion): ?>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm text-gray-600"><?php echo esc_html($taller_ubicacion); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Duration & Level -->
                        <div class="flex items-center gap-4 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?php echo esc_html($taller_duracion); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    <?php echo esc_html($taller_nivel); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Availability Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Plazas disponibles</span>
                            <span class="text-sm font-bold text-gray-900">
                                <?php echo esc_html($plazas_disponibles); ?> / <?php echo esc_html($plazas_totales); ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="<?php echo esc_attr($clase_disponibilidad); ?> h-2 rounded-full transition-all duration-300"
                                 style="width: <?php echo esc_attr($porcentaje_disponibilidad); ?>%"></div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 mt-auto">
                        <!-- Price -->
                        <div class="text-2xl font-bold text-emerald-600">
                            <?php echo esc_html($taller_precio); ?>
                        </div>

                        <!-- CTA Button -->
                        <a href="<?php echo esc_url(home_url('/talleres/' . $taller_id)); ?>"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <span>Reservar</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </article>

            <?php endforeach; ?>
        </div>

        <?php if ($mostrar_paginacion && count($talleres_lista) > 0): ?>
        <!-- Pagination -->
        <div class="mt-12 flex justify-center">
            <nav class="inline-flex rounded-lg shadow-sm border border-gray-200 bg-white" aria-label="Pagination">
                <a href="#" class="relative inline-flex items-center px-4 py-2 rounded-l-lg border-r border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="ml-2 hidden sm:inline">Anterior</span>
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border-r border-gray-200 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    1
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border-r border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    2
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border-r border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    3
                </a>
                <span class="relative inline-flex items-center px-4 py-2 border-r border-gray-200 text-sm font-medium text-gray-700">
                    ...
                </span>
                <a href="#" class="relative inline-flex items-center px-4 py-2 rounded-r-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="mr-2 hidden sm:inline">Siguiente</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </nav>
        </div>
        <?php endif; ?>

        <?php if (empty($talleres_lista)): ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay talleres disponibles</h3>
            <p class="text-gray-600 mb-6">En este momento no hay talleres programados. Vuelve pronto para ver nuevas opciones.</p>
            <a href="<?php echo esc_url(home_url('/talleres/proponer')); ?>"
               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Proponer un Taller</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
