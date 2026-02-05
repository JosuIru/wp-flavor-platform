<?php
/**
 * Workshop Categories Template
 *
 * Displays workshop categories with icons and descriptions
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Talleres
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Default values
$categorias_lista = $args['categorias'] ?? [];
$titulo_seccion = $args['titulo'] ?? 'Explora Nuestras Categorías';
$subtitulo_seccion = $args['subtitulo'] ?? 'Encuentra talleres organizados por temática y área de interés';
$layout = $args['layout'] ?? 'grid'; // 'grid' or 'list'
$mostrar_contador = $args['mostrar_contador'] ?? true;
$columnas = $args['columnas'] ?? 4;
?>

<section class="py-12 sm:py-16 lg:py-20 bg-white">
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

        <?php
        // Sample categories if none provided
        if (empty($categorias_lista)) {
            $categorias_lista = [
                [
                    'id' => 'agricultura',
                    'nombre' => 'Agricultura Ecológica',
                    'descripcion' => 'Aprende técnicas de cultivo sostenible, permacultura y manejo ecológico del huerto.',
                    'icono' => 'plant',
                    'color' => 'green',
                    'total_talleres' => 12,
                    'url' => '/talleres/categoria/agricultura',
                ],
                [
                    'id' => 'artesania',
                    'nombre' => 'Artesanía y Oficios',
                    'descripcion' => 'Recupera técnicas tradicionales de cestería, cerámica, carpintería y tejido.',
                    'icono' => 'hammer',
                    'color' => 'amber',
                    'total_talleres' => 8,
                    'url' => '/talleres/categoria/artesania',
                ],
                [
                    'id' => 'alimentacion',
                    'nombre' => 'Alimentación Consciente',
                    'descripcion' => 'Cocina saludable, conservas, fermentados y elaboración de productos artesanales.',
                    'icono' => 'chef',
                    'color' => 'orange',
                    'total_talleres' => 10,
                    'url' => '/talleres/categoria/alimentacion',
                ],
                [
                    'id' => 'tecnologia',
                    'nombre' => 'Tecnología Sostenible',
                    'descripcion' => 'Energías renovables, construcción ecológica y tecnologías apropiadas para la comunidad.',
                    'icono' => 'lightbulb',
                    'color' => 'blue',
                    'total_talleres' => 6,
                    'url' => '/talleres/categoria/tecnologia',
                ],
                [
                    'id' => 'salud',
                    'nombre' => 'Salud y Bienestar',
                    'descripcion' => 'Plantas medicinales, remedios naturales, yoga y prácticas de bienestar holístico.',
                    'icono' => 'heart',
                    'color' => 'pink',
                    'total_talleres' => 9,
                    'url' => '/talleres/categoria/salud',
                ],
                [
                    'id' => 'educacion',
                    'nombre' => 'Educación Ambiental',
                    'descripcion' => 'Talleres sobre ecología, biodiversidad y educación para la sostenibilidad.',
                    'icono' => 'book',
                    'color' => 'purple',
                    'total_talleres' => 7,
                    'url' => '/talleres/categoria/educacion',
                ],
                [
                    'id' => 'economia',
                    'nombre' => 'Economía Local',
                    'descripcion' => 'Monedas locales, trueque, cooperativismo y modelos económicos alternativos.',
                    'icono' => 'coins',
                    'color' => 'teal',
                    'total_talleres' => 5,
                    'url' => '/talleres/categoria/economia',
                ],
                [
                    'id' => 'arte',
                    'nombre' => 'Arte y Cultura',
                    'descripcion' => 'Expresión artística, música tradicional, teatro comunitario y creación cultural.',
                    'icono' => 'palette',
                    'color' => 'indigo',
                    'total_talleres' => 11,
                    'url' => '/talleres/categoria/arte',
                ],
            ];
        }

        // Color mapping for Tailwind classes
        $colores_mapa = [
            'green' => ['bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600', 'text' => 'text-green-600', 'border' => 'border-green-200', 'bg-light' => 'bg-green-50'],
            'amber' => ['bg' => 'bg-amber-500', 'hover' => 'hover:bg-amber-600', 'text' => 'text-amber-600', 'border' => 'border-amber-200', 'bg-light' => 'bg-amber-50'],
            'orange' => ['bg' => 'bg-orange-500', 'hover' => 'hover:bg-orange-600', 'text' => 'text-orange-600', 'border' => 'border-orange-200', 'bg-light' => 'bg-orange-50'],
            'blue' => ['bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600', 'text' => 'text-blue-600', 'border' => 'border-blue-200', 'bg-light' => 'bg-blue-50'],
            'pink' => ['bg' => 'bg-pink-500', 'hover' => 'hover:bg-pink-600', 'text' => 'text-pink-600', 'border' => 'border-pink-200', 'bg-light' => 'bg-pink-50'],
            'purple' => ['bg' => 'bg-purple-500', 'hover' => 'hover:bg-purple-600', 'text' => 'text-purple-600', 'border' => 'border-purple-200', 'bg-light' => 'bg-purple-50'],
            'teal' => ['bg' => 'bg-teal-500', 'hover' => 'hover:bg-teal-600', 'text' => 'text-teal-600', 'border' => 'border-teal-200', 'bg-light' => 'bg-teal-50'],
            'indigo' => ['bg' => 'bg-indigo-500', 'hover' => 'hover:bg-indigo-600', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200', 'bg-light' => 'bg-indigo-50'],
        ];

        // Icon mapping
        $iconos_svg = [
            'plant' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
            'hammer' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />',
            'chef' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />',
            'lightbulb' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />',
            'heart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />',
            'book' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
            'coins' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'palette' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />',
        ];

        // Grid columns class
        $columnas_clases = [
            2 => 'md:grid-cols-2',
            3 => 'md:grid-cols-2 lg:grid-cols-3',
            4 => 'md:grid-cols-2 lg:grid-cols-4',
        ];
        $grid_clase = $columnas_clases[$columnas] ?? $columnas_clases[4];
        ?>

        <?php if ($layout === 'grid'): ?>
        <!-- Grid Layout -->
        <div class="grid grid-cols-1 <?php echo esc_attr($grid_clase); ?> gap-6 lg:gap-8">
            <?php foreach ($categorias_lista as $categoria):
                $categoria_id = $categoria['id'] ?? '';
                $categoria_nombre = $categoria['nombre'] ?? 'Categoría';
                $categoria_descripcion = $categoria['descripcion'] ?? '';
                $icono_key = $categoria['icono'] ?? 'book';
                $color_key = $categoria['color'] ?? 'green';
                $total_talleres = $categoria['total_talleres'] ?? 0;
                $categoria_url = $categoria['url'] ?? '#';

                $colores = $colores_mapa[$color_key] ?? $colores_mapa['green'];
                $icono_svg = $iconos_svg[$icono_key] ?? $iconos_svg['book'];
            ?>
            <!-- Category Card -->
            <a href="<?php echo esc_url($categoria_url); ?>"
               class="group bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden border-2 <?php echo esc_attr($colores['border']); ?> hover:border-transparent transform hover:-translate-y-1">
                <!-- Card Header with Icon -->
                <div class="<?php echo esc_attr($colores['bg-light']); ?> p-6 text-center relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-5">
                        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"40\" height=\"40\" viewBox=\"0 0 40 40\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"%23000000\" fill-opacity=\"1\" fill-rule=\"evenodd\"%3E%3Cpath d=\"M0 40L40 0H20L0 20M40 40V20L20 40\"/%3E%3C/g%3E%3C/svg%3E');"></div>
                    </div>

                    <!-- Icon Container -->
                    <div class="relative inline-flex items-center justify-center w-20 h-20 <?php echo esc_attr($colores['bg']); ?> rounded-2xl shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $icono_svg; ?>
                        </svg>
                    </div>

                    <?php if ($mostrar_contador && $total_talleres > 0): ?>
                    <!-- Badge Counter -->
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-bold <?php echo esc_attr($colores['bg']); ?> text-white shadow-md">
                            <?php echo esc_html($total_talleres); ?> talleres
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card Body -->
                <div class="p-6">
                    <!-- Category Name -->
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:<?php echo esc_attr($colores['text']); ?> transition-colors">
                        <?php echo esc_html($categoria_nombre); ?>
                    </h3>

                    <!-- Description -->
                    <p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-3">
                        <?php echo esc_html($categoria_descripcion); ?>
                    </p>

                    <!-- Explore Link -->
                    <div class="flex items-center gap-2 <?php echo esc_attr($colores['text']); ?> font-semibold text-sm group-hover:gap-3 transition-all">
                        <span>Explorar talleres</span>
                        <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- List Layout -->
        <div class="space-y-4">
            <?php foreach ($categorias_lista as $categoria):
                $categoria_id = $categoria['id'] ?? '';
                $categoria_nombre = $categoria['nombre'] ?? 'Categoría';
                $categoria_descripcion = $categoria['descripcion'] ?? '';
                $icono_key = $categoria['icono'] ?? 'book';
                $color_key = $categoria['color'] ?? 'green';
                $total_talleres = $categoria['total_talleres'] ?? 0;
                $categoria_url = $categoria['url'] ?? '#';

                $colores = $colores_mapa[$color_key] ?? $colores_mapa['green'];
                $icono_svg = $iconos_svg[$icono_key] ?? $iconos_svg['book'];
            ?>
            <!-- Category List Item -->
            <a href="<?php echo esc_url($categoria_url); ?>"
               class="group flex items-center gap-6 bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-6 border-2 <?php echo esc_attr($colores['border']); ?> hover:border-transparent transform hover:scale-[1.02]">
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <div class="inline-flex items-center justify-center w-16 h-16 <?php echo esc_attr($colores['bg']); ?> rounded-xl shadow-md transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $icono_svg; ?>
                        </svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:<?php echo esc_attr($colores['text']); ?> transition-colors">
                        <?php echo esc_html($categoria_nombre); ?>
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed line-clamp-2">
                        <?php echo esc_html($categoria_descripcion); ?>
                    </p>
                </div>

                <!-- Counter and Arrow -->
                <div class="flex-shrink-0 flex items-center gap-4">
                    <?php if ($mostrar_contador && $total_talleres > 0): ?>
                    <div class="text-center">
                        <div class="text-2xl font-bold <?php echo esc_attr($colores['text']); ?>">
                            <?php echo esc_html($total_talleres); ?>
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Talleres</div>
                    </div>
                    <?php endif; ?>

                    <svg class="w-6 h-6 <?php echo esc_attr($colores['text']); ?> transform group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="mt-16 text-center bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-8 sm:p-12 border-2 border-emerald-200">
            <div class="max-w-2xl mx-auto">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-600 rounded-2xl shadow-lg mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                    ¿Tienes conocimientos que compartir?
                </h3>
                <p class="text-gray-700 text-lg mb-8">
                    Propón un taller y ayuda a fortalecer el aprendizaje en nuestra comunidad. Todos tenemos algo valioso que enseñar.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo esc_url(home_url('/talleres/proponer')); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Proponer un Taller</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/talleres/instructores')); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white hover:bg-gray-50 text-emerald-600 font-semibold rounded-lg border-2 border-emerald-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Ver Instructores</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
