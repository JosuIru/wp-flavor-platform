<?php
/**
 * Template: Grid de Cursos
 *
 * Muestra una cuadrícula de cursos disponibles con filtros y paginación
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Cursos
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_seccion = $args['titulo_seccion'] ?? 'Explora Nuestros Cursos';
$subtitulo_seccion = $args['subtitulo_seccion'] ?? 'Encuentra el curso perfecto para alcanzar tus objetivos';
$cursos_disponibles = $args['cursos'] ?? [];
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$mostrar_buscador = $args['mostrar_buscador'] ?? true;
$columnas_grid = $args['columnas'] ?? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
$categorias_filtro = $args['categorias'] ?? [];
$mensaje_sin_cursos = $args['mensaje_sin_cursos'] ?? 'No se encontraron cursos disponibles en este momento.';
?>

<section id="cursos" class="py-16 bg-gray-50">
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

        <!-- Barra de búsqueda y filtros -->
        <?php if ($mostrar_buscador || $mostrar_filtros): ?>
            <div class="mb-8 bg-white rounded-xl shadow-sm p-6">
                <div class="grid md:grid-cols-2 gap-4">
                    <!-- Buscador -->
                    <?php if ($mostrar_buscador): ?>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text"
                                   id="curso-search"
                                   placeholder="Buscar cursos por título, instructor o tema..."
                                   class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    <?php endif; ?>

                    <!-- Filtro por categoría -->
                    <?php if ($mostrar_filtros && !empty($categorias_filtro)): ?>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                            </div>
                            <select id="categoria-filter"
                                    class="block w-full pl-12 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none bg-white">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias_filtro as $categoria_id => $categoria_nombre): ?>
                                    <option value="<?php echo esc_attr($categoria_id); ?>">
                                        <?php echo esc_html($categoria_nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grid de cursos -->
        <?php if (!empty($cursos_disponibles)): ?>
            <div class="grid <?php echo esc_attr($columnas_grid); ?> gap-8" id="cursos-grid">
                <?php foreach ($cursos_disponibles as $curso):
                    $curso_id = $curso['id'] ?? 0;
                    $curso_titulo = $curso['titulo'] ?? 'Curso sin título';
                    $curso_descripcion = $curso['descripcion'] ?? '';
                    $curso_imagen = $curso['imagen'] ?? '';
                    $curso_instructor = $curso['instructor'] ?? 'Instructor';
                    $curso_duracion = $curso['duracion'] ?? '';
                    $curso_nivel = $curso['nivel'] ?? 'Todos los niveles';
                    $curso_precio = $curso['precio'] ?? 0;
                    $curso_precio_descuento = $curso['precio_descuento'] ?? null;
                    $curso_estudiantes = $curso['estudiantes'] ?? 0;
                    $curso_rating = $curso['rating'] ?? 0;
                    $curso_total_reviews = $curso['total_reviews'] ?? 0;
                    $curso_url = $curso['url'] ?? '#';
                    $curso_categoria = $curso['categoria'] ?? '';
                    $curso_etiquetas = $curso['etiquetas'] ?? [];
                ?>
                    <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col"
                             data-curso-id="<?php echo esc_attr($curso_id); ?>"
                             data-categoria="<?php echo esc_attr($curso_categoria); ?>">

                        <!-- Imagen del curso -->
                        <div class="relative h-48 bg-gradient-to-br from-blue-500 to-indigo-600 overflow-hidden">
                            <?php if ($curso_imagen): ?>
                                <img src="<?php echo esc_url($curso_imagen); ?>"
                                     alt="<?php echo esc_attr($curso_titulo); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Badge de nivel -->
                            <?php if ($curso_nivel): ?>
                                <div class="absolute top-4 left-4">
                                    <span class="inline-block px-3 py-1 bg-white/90 backdrop-blur-sm text-gray-900 text-xs font-semibold rounded-full">
                                        <?php echo esc_html($curso_nivel); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Badge de categoría -->
                            <?php if ($curso_categoria): ?>
                                <div class="absolute top-4 right-4">
                                    <span class="inline-block px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full">
                                        <?php echo esc_html($curso_categoria); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Contenido del curso -->
                        <div class="p-6 flex-grow flex flex-col">
                            <!-- Título -->
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 hover:text-blue-600 transition-colors">
                                <a href="<?php echo esc_url($curso_url); ?>">
                                    <?php echo esc_html($curso_titulo); ?>
                                </a>
                            </h3>

                            <!-- Instructor -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span><?php echo esc_html($curso_instructor); ?></span>
                            </div>

                            <!-- Descripción -->
                            <?php if ($curso_descripcion): ?>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3 flex-grow">
                                    <?php echo esc_html($curso_descripcion); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Metadatos -->
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-4 pb-4 border-b border-gray-100">
                                <?php if ($curso_duracion): ?>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span><?php echo esc_html($curso_duracion); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($curso_estudiantes > 0): ?>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <span><?php echo number_format($curso_estudiantes, 0, ',', '.'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Rating y Precio -->
                            <div class="flex items-center justify-between">
                                <!-- Rating -->
                                <?php if ($curso_rating > 0): ?>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-4 h-4 <?php echo $i <= $curso_rating ? 'text-yellow-400' : 'text-gray-300'; ?>"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-600">
                                            <?php echo number_format($curso_rating, 1); ?>
                                            <?php if ($curso_total_reviews > 0): ?>
                                                <span class="text-gray-400">(<?php echo number_format($curso_total_reviews, 0, ',', '.'); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Precio -->
                                <div class="flex items-center gap-2">
                                    <?php if ($curso_precio_descuento !== null && $curso_precio_descuento < $curso_precio): ?>
                                        <span class="text-sm text-gray-400 line-through">
                                            €<?php echo number_format($curso_precio, 2, ',', '.'); ?>
                                        </span>
                                        <span class="text-xl font-bold text-blue-600">
                                            €<?php echo number_format($curso_precio_descuento, 2, ',', '.'); ?>
                                        </span>
                                    <?php elseif ($curso_precio > 0): ?>
                                        <span class="text-xl font-bold text-blue-600">
                                            €<?php echo number_format($curso_precio, 2, ',', '.'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xl font-bold text-green-600">Gratis</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Botón de acción -->
                            <a href="<?php echo esc_url($curso_url); ?>"
                               class="mt-4 w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                Ver Curso
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <div class="mt-12 flex justify-center">
                <nav class="flex items-center gap-2" aria-label="Paginación">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            disabled>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">1</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">2</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">3</button>
                    <span class="px-2 text-gray-500">...</span>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">10</button>

                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </nav>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay cursos disponibles</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    <?php echo esc_html($mensaje_sin_cursos); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Script básico para búsqueda y filtrado (puede ser mejorado con JavaScript) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('curso-search');
    const categoryFilter = document.getElementById('categoria-filter');
    const cursosGrid = document.getElementById('cursos-grid');

    if (searchInput) {
        searchInput.addEventListener('input', filtrarCursos);
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', filtrarCursos);
    }

    function filtrarCursos() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const selectedCategory = categoryFilter ? categoryFilter.value : '';
        const cursos = cursosGrid ? cursosGrid.querySelectorAll('article') : [];

        cursos.forEach(curso => {
            const titulo = curso.querySelector('h3').textContent.toLowerCase();
            const categoria = curso.dataset.categoria || '';

            const matchSearch = !searchTerm || titulo.includes(searchTerm);
            const matchCategory = !selectedCategory || categoria === selectedCategory;

            curso.style.display = (matchSearch && matchCategory) ? 'flex' : 'none';
        });
    }
});
</script>
