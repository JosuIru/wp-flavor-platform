<?php
/**
 * Template: Navegación por Géneros Literarios
 *
 * Muestra tarjetas interactivas para navegar por los diferentes géneros
 * de libros disponibles en la biblioteca.
 *
 * @package FlavorChatIA
 * @var array $args Variables pasadas al template
 */

if (!defined('ABSPATH')) exit;

// Configuración por defecto
$titulo = $args['titulo'] ?? 'Explora por Géneros';
$subtitulo = $args['subtitulo'] ?? 'Descubre historias increíbles en tus géneros favoritos';
$columnas = $args['columnas'] ?? 3;
$clase_componente = $args['clase_componente'] ?? '';

// Géneros disponibles
$generos = $args['generos'] ?? [
    [
        'nombre' => 'Realismo Mágico',
        'descripcion' => 'Historias donde la magia se entrelaza con la realidad cotidiana',
        'libros' => 24,
        'color' => 'from-purple-500 to-purple-600',
        'icono' => 'wand',
        'destacado' => 'Cien Años de Soledad'
    ],
    [
        'nombre' => 'Distopía',
        'descripcion' => 'Mundos futuros oscuros que cuestionan nuestra realidad',
        'libros' => 18,
        'color' => 'from-red-500 to-red-600',
        'icono' => 'warning',
        'destacado' => '1984'
    ],
    [
        'nombre' => 'Fantasía',
        'descripcion' => 'Aventuras en mundos imaginarios llenos de magia',
        'libros' => 42,
        'color' => 'from-indigo-500 to-indigo-600',
        'icono' => 'star',
        'destacado' => 'El Señor de los Anillos'
    ],
    [
        'nombre' => 'Romance',
        'descripcion' => 'Historias de amor, pasión y conexiones humanas',
        'libros' => 35,
        'color' => 'from-rose-500 to-rose-600',
        'icono' => 'heart',
        'destacado' => 'Orgullo y Prejuicio'
    ],
    [
        'nombre' => 'Ciencia Ficción',
        'descripcion' => 'Futuros posibles y tecnologías revolucionarias',
        'libros' => 28,
        'color' => 'from-blue-500 to-blue-600',
        'icono' => 'rocket',
        'destacado' => 'Dune'
    ],
    [
        'nombre' => 'Misterio y Thriller',
        'descripcion' => 'Intriga, secretos y giros inesperados',
        'libros' => 32,
        'color' => 'from-gray-700 to-gray-800',
        'icono' => 'magnifying-glass',
        'destacado' => 'La Sombra del Viento'
    ],
    [
        'nombre' => 'Drama',
        'descripcion' => 'Historias profundas que exploran la condición humana',
        'libros' => 26,
        'color' => 'from-orange-500 to-orange-600',
        'icono' => 'theater-masks',
        'destacado' => 'Matar un Ruiseñor'
    ],
    [
        'nombre' => 'No Ficción',
        'descripcion' => 'Historias reales que educan e inspiran',
        'libros' => 19,
        'color' => 'from-green-500 to-green-600',
        'icono' => 'book-open',
        'destacado' => 'Sapiens'
    ],
    [
        'nombre' => 'Aventura',
        'descripcion' => 'Viajes épicos y desafíos extraordinarios',
        'libros' => 31,
        'color' => 'from-yellow-500 to-yellow-600',
        'icono' => 'compass',
        'destacado' => 'El Alquimista'
    ]
];

// Mapeo de columnas CSS
$grid_cols = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-3',
    4 => 'md:grid-cols-4',
];

$col_class = $grid_cols[$columnas] ?? 'md:grid-cols-3';

// Iconos SVG
$iconos_svg = [
    'wand' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'warning' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M7.08 6.47a7 7 0 1 1 9.84 0" /></svg>',
    'star' => '<svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>',
    'heart' => '<svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>',
    'rocket' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>',
    'magnifying-glass' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>',
    'theater-masks' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" /></svg>',
    'book-open' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
    'compass' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l3-5m0 0l3 5m-3-5v5m0-15a9 9 0 110 18 9 9 0 010-18z" /></svg>'
];

?>

<section class="flavor-component py-16 md:py-20 bg-gradient-to-br from-slate-50 via-indigo-50 to-blue-50 <?php echo esc_attr($clase_componente); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">

        <!-- Header -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo); ?>
            </p>
            <div class="w-20 h-1 bg-indigo-600 mx-auto rounded-full mt-6"></div>
        </div>

        <!-- Grid de Géneros -->
        <div class="grid grid-cols-1 sm:grid-cols-2 <?php echo esc_attr($col_class); ?> gap-6">
            <?php foreach ($generos as $genero): ?>
                <div class="group cursor-pointer">
                    <!-- Card Principal -->
                    <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden h-full flex flex-col">

                        <!-- Fondo con gradiente -->
                        <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-br <?php echo $genero['color']; ?> opacity-0 group-hover:opacity-100 transition-all duration-300"></div>

                        <!-- Contenido -->
                        <div class="relative z-10 p-8 flex flex-col h-full">

                            <!-- Icono -->
                            <div class="mb-4 flex justify-center">
                                <div class="w-20 h-20 bg-gradient-to-br <?php echo $genero['color']; ?> rounded-2xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <?php echo $iconos_svg[$genero['icono']] ?? $iconos_svg['star']; ?>
                                </div>
                            </div>

                            <!-- Título -->
                            <h3 class="text-xl font-bold text-gray-900 text-center mb-2 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r <?php echo $genero['color']; ?> transition-all duration-300">
                                <?php echo esc_html($genero['nombre']); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-gray-600 text-center text-sm mb-6 flex-grow">
                                <?php echo esc_html($genero['descripcion']); ?>
                            </p>

                            <!-- Información -->
                            <div class="space-y-3 pt-4 border-t border-gray-200">

                                <!-- Número de libros -->
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span class="font-semibold text-gray-900"><?php echo number_format($genero['libros']); ?> <?php echo esc_html__('libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>

                                <!-- Destacado -->
                                <div class="bg-gradient-to-r from-slate-50 to-gray-50 rounded-lg p-3 border border-gray-200">
                                    <p class="text-xs text-gray-600 mb-1 font-semibold">
                                        <?php echo esc_html__('Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?php echo esc_html($genero['destacado']); ?>
                                    </p>
                                </div>

                                <!-- Botón de explorar -->
                                <button class="w-full bg-gradient-to-r <?php echo $genero['color']; ?> hover:shadow-lg text-white font-semibold py-3 rounded-xl transition-all duration-300 transform group-hover:scale-105 flex items-center justify-center gap-2">
                                    <span><?php echo esc_html__('Explorar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>

                            </div>

                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sección adicional: Recomendaciones -->
        <div class="mt-16 bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="grid md:grid-cols-3 gap-8">

                <!-- Card 1: Novedades -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Novedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo esc_html__('Descubre los libros agregados recientemente a nuestra colección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <a href="#" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold gap-2">
                        <span><?php echo esc_html__('Ver Novedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Card 2: Más Prestados -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Más Prestados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo esc_html__('Los libros favoritos de la comunidad durante este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <a href="#" class="inline-flex items-center text-purple-600 hover:text-purple-700 font-semibold gap-2">
                        <span><?php echo esc_html__('Ver Ranking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Card 3: Recomendaciones Personalizadas -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl flex items-center justify-center text-white shadow-lg mx-auto mb-4">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Para Ti', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo esc_html__('Recomendaciones personalizadas según tus preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <a href="#" class="inline-flex items-center text-rose-600 hover:text-rose-700 font-semibold gap-2">
                        <span><?php echo esc_html__('Ver Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

            </div>
        </div>

    </div>
</section>
