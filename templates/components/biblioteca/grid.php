<?php
/**
 * Template: Grid de Libros Disponibles
 *
 * Muestra una cuadrícula de libros disponibles en la biblioteca
 * con opciones de filtrado y búsqueda.
 *
 * @package FlavorChatIA
 * @var array $args Variables pasadas al template
 */

if (!defined('ABSPATH')) exit;

// Configuración por defecto
$titulo = $args['titulo'] ?? 'Nuestros Libros';
$subtitulo = $args['subtitulo'] ?? 'Descubre la colección completa de nuestra biblioteca';
$columnas = $args['columnas'] ?? 4;
$limite = $args['limite'] ?? 12;
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$mostrar_busqueda = $args['mostrar_busqueda'] ?? true;
$clase_componente = $args['clase_componente'] ?? '';

// Datos de ejemplo de libros
$libros = $args['libros'] ?? [
    [
        'id' => 1,
        'titulo' => 'Cien Años de Soledad',
        'autor' => 'Gabriel García Márquez',
        'genero' => 'Realismo Mágico',
        'año' => 1967,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.8,
        'reseñas' => 342,
        'copias' => 3,
        'edad_recomendada' => '16+'
    ],
    [
        'id' => 2,
        'titulo' => 'El Principito',
        'autor' => 'Antoine de Saint-Exupéry',
        'genero' => 'Fantasía Infantil',
        'año' => 1943,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.9,
        'reseñas' => 521,
        'copias' => 5,
        'edad_recomendada' => '6+'
    ],
    [
        'id' => 3,
        'titulo' => '1984',
        'autor' => 'George Orwell',
        'genero' => 'Distopía',
        'año' => 1949,
        'disponible' => false,
        'portada' => '',
        'valoracion' => 4.7,
        'reseñas' => 428,
        'copias' => 2,
        'edad_recomendada' => '14+'
    ],
    [
        'id' => 4,
        'titulo' => 'Sapiens',
        'autor' => 'Yuval Noah Harari',
        'genero' => 'No Ficción',
        'año' => 2011,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.6,
        'reseñas' => 315,
        'copias' => 2,
        'edad_recomendada' => '15+'
    ],
    [
        'id' => 5,
        'titulo' => 'La Sombra del Viento',
        'autor' => 'Carlos Ruiz Zafón',
        'genero' => 'Misterio',
        'año' => 2001,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.5,
        'reseñas' => 287,
        'copias' => 3,
        'edad_recomendada' => '14+'
    ],
    [
        'id' => 6,
        'titulo' => 'El Quijote',
        'autor' => 'Miguel de Cervantes',
        'genero' => 'Clásico',
        'año' => 1605,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.3,
        'reseñas' => 198,
        'copias' => 2,
        'edad_recomendada' => '14+'
    ],
    [
        'id' => 7,
        'titulo' => 'Orgullo y Prejuicio',
        'autor' => 'Jane Austen',
        'genero' => 'Romance',
        'año' => 1813,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.7,
        'reseñas' => 378,
        'copias' => 3,
        'edad_recomendada' => '13+'
    ],
    [
        'id' => 8,
        'titulo' => 'Matar un Ruiseñor',
        'autor' => 'Harper Lee',
        'genero' => 'Drama',
        'año' => 1960,
        'disponible' => false,
        'portada' => '',
        'valoracion' => 4.8,
        'reseñas' => 412,
        'copias' => 1,
        'edad_recomendada' => '13+'
    ],
    [
        'id' => 9,
        'titulo' => 'El Alquimista',
        'autor' => 'Paulo Coelho',
        'genero' => 'Aventura',
        'año' => 1988,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.4,
        'reseñas' => 356,
        'copias' => 4,
        'edad_recomendada' => '12+'
    ],
    [
        'id' => 10,
        'titulo' => 'Dune',
        'autor' => 'Frank Herbert',
        'genero' => 'Ciencia Ficción',
        'año' => 1965,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.6,
        'reseñas' => 289,
        'copias' => 2,
        'edad_recomendada' => '15+'
    ],
    [
        'id' => 11,
        'titulo' => 'Harry Potter',
        'autor' => 'J.K. Rowling',
        'genero' => 'Fantasía',
        'año' => 1998,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.8,
        'reseñas' => 632,
        'copias' => 5,
        'edad_recomendada' => '9+'
    ],
    [
        'id' => 12,
        'titulo' => 'El Señor de los Anillos',
        'autor' => 'J.R.R. Tolkien',
        'genero' => 'Fantasía Épica',
        'año' => 1954,
        'disponible' => true,
        'portada' => '',
        'valoracion' => 4.7,
        'reseñas' => 521,
        'copias' => 3,
        'edad_recomendada' => '13+'
    ]
];

// Mapeo de columnas CSS
$grid_cols = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-3',
    4 => 'md:grid-cols-4',
    5 => 'md:grid-cols-5',
];

$col_class = $grid_cols[$columnas] ?? 'md:grid-cols-4';

// Géneros únicos para filtros
$generos_unicos = array_unique(array_column($libros, 'genero'));

?>

<section class="flavor-component py-16 md:py-20 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 <?php echo esc_attr($clase_componente); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">

        <!-- Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo); ?>
            </p>
            <div class="w-20 h-1 bg-blue-600 mx-auto rounded-full mt-6"></div>
        </div>

        <!-- Buscador y Filtros -->
        <?php if ($mostrar_busqueda || $mostrar_filtros): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-10">
                <div class="grid md:grid-cols-3 gap-4">
                    <!-- Búsqueda -->
                    <?php if ($mostrar_busqueda): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <?php echo esc_html__('Buscar Libro', 'flavor-chat-ia'); ?>
                            </label>
                            <div class="relative">
                                <input type="text" placeholder="<?php echo esc_attr__('Título, autor...', 'flavor-chat-ia'); ?>"
                                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <svg class="absolute right-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Filtro por Género -->
                    <?php if ($mostrar_filtros && !empty($generos_unicos)): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <?php echo esc_html__('Género', 'flavor-chat-ia'); ?>
                            </label>
                            <select class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value=""><?php echo esc_html__('Todos los géneros', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($generos_unicos as $genero): ?>
                                    <option value="<?php echo esc_attr($genero); ?>"><?php echo esc_html($genero); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Filtro por Disponibilidad -->
                    <?php if ($mostrar_filtros): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?>
                            </label>
                            <select class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="disponible"><?php echo esc_html__('Disponibles', 'flavor-chat-ia'); ?></option>
                                <option value="prestado"><?php echo esc_html__('En préstamo', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grid de Libros -->
        <div class="grid grid-cols-1 sm:grid-cols-2 <?php echo esc_attr($col_class); ?> gap-6 mb-12">
            <?php
            $libros_mostrados = array_slice($libros, 0, $limite);
            foreach ($libros_mostrados as $libro):
            ?>
                <div class="flavor-libro-card bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group cursor-pointer">

                    <!-- Portada -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100 aspect-[3/4]">
                        <!-- Placeholder de portada -->
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-blue-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="text-xs text-blue-600 font-semibold"><?php echo esc_html($libro['genero']); ?></p>
                            </div>
                        </div>

                        <!-- Badge de disponibilidad -->
                        <div class="absolute top-3 right-3">
                            <?php if ($libro['disponible']): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-lg">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-500 text-white shadow-lg">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?php echo esc_html__('Prestado', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Overlay interactivo -->
                        <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-30 transition-all duration-300"></div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-5">
                        <!-- Título -->
                        <h3 class="font-bold text-gray-900 mb-1 line-clamp-2 group-hover:text-blue-600 transition">
                            <?php echo esc_html($libro['titulo']); ?>
                        </h3>

                        <!-- Autor -->
                        <p class="text-sm text-gray-600 mb-3">
                            <?php echo esc_html($libro['autor']); ?>
                        </p>

                        <!-- Año de publicación -->
                        <p class="text-xs text-gray-500 mb-3 pb-3 border-b border-gray-100">
                            <?php echo esc_html($libro['año']); ?> · <?php echo esc_html__('Edad recomendada', 'flavor-chat-ia'); ?>: <span class="font-semibold"><?php echo esc_html($libro['edad_recomendada']); ?></span>
                        </p>

                        <!-- Valoración -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <svg class="w-4 h-4 <?php echo $i < floor($libro['valoracion']) ? 'text-yellow-400' : 'text-gray-300'; ?> fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs font-semibold text-gray-700"><?php echo number_format($libro['valoracion'], 1); ?></span>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo number_format($libro['reseñas']); ?> <?php echo esc_html__('reseñas', 'flavor-chat-ia'); ?></span>
                        </div>

                        <!-- Copias disponibles -->
                        <div class="bg-blue-50 rounded-lg p-2 mb-4 border border-blue-100">
                            <p class="text-xs text-blue-700 font-semibold">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <?php printf(
                                    esc_html__('%d copias disponibles', 'flavor-chat-ia'),
                                    $libro['copias']
                                ); ?>
                            </p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="space-y-2">
                            <?php if ($libro['disponible']): ?>
                                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition duration-300 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    <?php echo esc_html__('Solicitar Préstamo', 'flavor-chat-ia'); ?>
                                </button>
                            <?php else: ?>
                                <button class="w-full bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    <?php echo esc_html__('Reservar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                            <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg transition duration-300 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <?php echo esc_html__('Ver Detalles', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ver Todos -->
        <div class="text-center">
            <a href="#" class="inline-flex items-center px-8 py-3 bg-white border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition duration-300 transform hover:scale-105">
                <span><?php echo esc_html__('Ver Todos los Libros', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>

    </div>
</section>
