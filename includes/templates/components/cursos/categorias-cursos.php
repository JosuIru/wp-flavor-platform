<?php
/**
 * Template: Categorías de Cursos
 *
 * Muestra las categorías de cursos disponibles con navegación visual
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Cursos
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_seccion = $args['titulo_seccion'] ?? 'Explora por Categorías';
$subtitulo_seccion = $args['subtitulo_seccion'] ?? 'Descubre cursos organizados por áreas de conocimiento';
$categorias_disponibles = $args['categorias'] ?? [];
$mostrar_contador_cursos = $args['mostrar_contador'] ?? true;
$layout_tipo = $args['layout'] ?? 'grid'; // grid o carousel
$columnas_grid = $args['columnas'] ?? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
?>

<section id="categorias" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado de sección -->
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <?php if ($subtitulo_seccion): ?>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    <?php echo esc_html($subtitulo_seccion); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($categorias_disponibles)): ?>
            <!-- Grid de categorías -->
            <div class="grid <?php echo esc_attr($columnas_grid); ?> gap-6">
                <?php foreach ($categorias_disponibles as $categoria):
                    $categoria_id = $categoria['id'] ?? 0;
                    $categoria_nombre = $categoria['nombre'] ?? 'Sin categoría';
                    $categoria_descripcion = $categoria['descripcion'] ?? '';
                    $categoria_icono = $categoria['icono'] ?? 'book';
                    $categoria_color = $categoria['color'] ?? 'blue';
                    $categoria_total_cursos = $categoria['total_cursos'] ?? 0;
                    $categoria_url = $categoria['url'] ?? '#';
                    $categoria_imagen = $categoria['imagen'] ?? '';

                    // Colores predefinidos
                    $colores_disponibles = [
                        'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'hover' => 'group-hover:bg-blue-600'],
                        'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'hover' => 'group-hover:bg-green-600'],
                        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'hover' => 'group-hover:bg-purple-600'],
                        'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'hover' => 'group-hover:bg-red-600'],
                        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'hover' => 'group-hover:bg-yellow-600'],
                        'pink' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600', 'hover' => 'group-hover:bg-pink-600'],
                        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'hover' => 'group-hover:bg-indigo-600'],
                        'teal' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-600', 'hover' => 'group-hover:bg-teal-600'],
                    ];

                    $color_config = $colores_disponibles[$categoria_color] ?? $colores_disponibles['blue'];

                    // Iconos SVG predefinidos
                    $iconos_svg = [
                        'book' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>',
                        'code' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>',
                        'design' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>',
                        'business' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
                        'science' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>',
                        'art' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>',
                        'music' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>',
                        'language' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>',
                        'health' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>',
                        'marketing' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>',
                    ];

                    $icono_svg_path = $iconos_svg[$categoria_icono] ?? $iconos_svg['book'];
                ?>
                    <a href="<?php echo esc_url($categoria_url); ?>"
                       class="group relative bg-white rounded-xl border-2 border-gray-200 hover:border-transparent hover:shadow-xl transition-all duration-300 overflow-hidden">

                        <!-- Imagen de fondo opcional -->
                        <?php if ($categoria_imagen): ?>
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity duration-300">
                                <img src="<?php echo esc_url($categoria_imagen); ?>"
                                     alt="<?php echo esc_attr($categoria_nombre); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                        <?php endif; ?>

                        <div class="relative p-6 flex flex-col items-center text-center">
                            <!-- Icono -->
                            <div class="w-16 h-16 <?php echo esc_attr($color_config['bg']); ?> rounded-xl flex items-center justify-center mb-4 transform group-hover:scale-110 transition-all duration-300 <?php echo esc_attr($color_config['hover']); ?>">
                                <svg class="w-8 h-8 <?php echo esc_attr($color_config['text']); ?> group-hover:text-white transition-colors duration-300"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <?php echo $icono_svg_path; ?>
                                </svg>
                            </div>

                            <!-- Nombre -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                                <?php echo esc_html($categoria_nombre); ?>
                            </h3>

                            <!-- Descripción -->
                            <?php if ($categoria_descripcion): ?>
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?php echo esc_html($categoria_descripcion); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Contador de cursos -->
                            <?php if ($mostrar_contador_cursos && $categoria_total_cursos > 0): ?>
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 text-gray-700 text-sm font-medium rounded-full group-hover:bg-blue-100 group-hover:text-blue-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span>
                                        <?php echo number_format($categoria_total_cursos, 0, ',', '.'); ?>
                                        <?php echo $categoria_total_cursos === 1 ? 'curso' : 'cursos'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Flecha decorativa -->
                            <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Botón Ver todas las categorías -->
            <div class="mt-12 text-center">
                <a href="#categorias-completas"
                   class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Ver Todas las Categorías
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay categorías disponibles</h3>
                <p class="text-gray-600">Las categorías de cursos aparecerán aquí próximamente.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sección de categorías destacadas (alternativa con diseño de tarjetas grandes) -->
<?php if (!empty($categorias_disponibles) && $layout_tipo === 'featured'): ?>
<section class="py-16 bg-gradient-to-br from-gray-50 to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach (array_slice($categorias_disponibles, 0, 3) as $categoria_destacada):
                $categoria_nombre_destacada = $categoria_destacada['nombre'] ?? 'Sin categoría';
                $categoria_descripcion_destacada = $categoria_destacada['descripcion'] ?? '';
                $categoria_total_cursos_destacada = $categoria_destacada['total_cursos'] ?? 0;
                $categoria_url_destacada = $categoria_destacada['url'] ?? '#';
                $categoria_color_destacada = $categoria_destacada['color'] ?? 'blue';

                $gradientes_disponibles = [
                    'blue' => 'from-blue-500 to-blue-600',
                    'green' => 'from-green-500 to-green-600',
                    'purple' => 'from-purple-500 to-purple-600',
                    'red' => 'from-red-500 to-red-600',
                    'yellow' => 'from-yellow-500 to-yellow-600',
                    'pink' => 'from-pink-500 to-pink-600',
                    'indigo' => 'from-indigo-500 to-indigo-600',
                    'teal' => 'from-teal-500 to-teal-600',
                ];

                $gradiente_categoria = $gradientes_disponibles[$categoria_color_destacada] ?? $gradientes_disponibles['blue'];
            ?>
                <a href="<?php echo esc_url($categoria_url_destacada); ?>"
                   class="group relative bg-gradient-to-br <?php echo esc_attr($gradiente_categoria); ?> rounded-2xl p-8 text-white overflow-hidden hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300">

                    <!-- Patrón de fondo -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                    </div>

                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-3">
                            <?php echo esc_html($categoria_nombre_destacada); ?>
                        </h3>

                        <?php if ($categoria_descripcion_destacada): ?>
                            <p class="text-white/90 mb-6 line-clamp-3">
                                <?php echo esc_html($categoria_descripcion_destacada); ?>
                            </p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between">
                            <?php if ($categoria_total_cursos_destacada > 0): ?>
                                <span class="text-white/90 text-sm font-medium">
                                    <?php echo number_format($categoria_total_cursos_destacada, 0, ',', '.'); ?> cursos disponibles
                                </span>
                            <?php endif; ?>

                            <span class="inline-flex items-center gap-2 text-white font-semibold group-hover:gap-3 transition-all">
                                Explorar
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
