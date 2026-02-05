<?php
/**
 * Template: Podcast Grid - Rejilla de Series de Podcasts
 *
 * Muestra una rejilla de series de podcasts con información visual y acceso a episodios
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Podcast
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_seccion = $args['titulo_seccion'] ?? 'Nuestras Series de Podcast';
$descripcion_seccion = $args['descripcion_seccion'] ?? 'Explora las diferentes series creadas por miembros de nuestra comunidad';
$podcasts = $args['podcasts'] ?? [];
$columnas_grid = $args['columnas_grid'] ?? 3;
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$categorias_disponibles = $args['categorias'] ?? ['Todos', 'Entrevistas', 'Noticias', 'Cultura', 'Educación', 'Tecnología'];

// Clases de columnas según configuración
$clases_columnas_grid = match($columnas_grid) {
    2 => 'grid-cols-1 md:grid-cols-2',
    3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
};
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado de sección -->
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($descripcion_seccion); ?>
            </p>
        </div>

        <!-- Filtros de categoría -->
        <?php if ($mostrar_filtros && !empty($categorias_disponibles)): ?>
            <div class="flex flex-wrap justify-center gap-3 mb-10">
                <?php foreach ($categorias_disponibles as $categoria): ?>
                    <button class="px-5 py-2 rounded-full text-sm font-medium transition-all duration-200
                                   <?php echo $categoria === 'Todos'
                                       ? 'bg-purple-600 text-white shadow-md'
                                       : 'bg-white text-gray-700 border border-gray-300 hover:border-purple-500 hover:text-purple-600'; ?>"
                            data-filtro-categoria="<?php echo esc_attr(strtolower($categoria)); ?>">
                        <?php echo esc_html($categoria); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Grid de Podcasts -->
        <div class="grid <?php echo esc_attr($clases_columnas_grid); ?> gap-6 lg:gap-8">
            <?php if (!empty($podcasts)): ?>
                <?php foreach ($podcasts as $podcast):
                    $titulo_podcast = $podcast['titulo'] ?? 'Sin título';
                    $descripcion_podcast = $podcast['descripcion'] ?? '';
                    $imagen_portada = $podcast['imagen'] ?? '';
                    $categoria_podcast = $podcast['categoria'] ?? 'General';
                    $total_episodios = $podcast['episodios_count'] ?? 0;
                    $autor_nombre = $podcast['autor'] ?? 'Anónimo';
                    $url_podcast = $podcast['url'] ?? '#';
                    $color_acento = $podcast['color'] ?? 'purple';
                ?>
                    <article class="grupo-podcast-card bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                        <!-- Imagen de portada -->
                        <div class="relative aspect-square overflow-hidden bg-gradient-to-br from-<?php echo esc_attr($color_acento); ?>-400 to-<?php echo esc_attr($color_acento); ?>-600">
                            <?php if ($imagen_portada): ?>
                                <img src="<?php echo esc_url($imagen_portada); ?>"
                                     alt="<?php echo esc_attr($titulo_podcast); ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            <?php else: ?>
                                <!-- Icono por defecto si no hay imagen -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-24 h-24 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Badge de categoría -->
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/90 backdrop-blur-sm text-<?php echo esc_attr($color_acento); ?>-700">
                                    <?php echo esc_html($categoria_podcast); ?>
                                </span>
                            </div>

                            <!-- Botón de reproducir -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-black/40">
                                <a href="<?php echo esc_url($url_podcast); ?>"
                                   class="flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-xl transform hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-<?php echo esc_attr($color_acento); ?>-600 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Contenido de la tarjeta -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 hover:text-<?php echo esc_attr($color_acento); ?>-600 transition-colors">
                                <a href="<?php echo esc_url($url_podcast); ?>">
                                    <?php echo esc_html($titulo_podcast); ?>
                                </a>
                            </h3>

                            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                <?php echo esc_html($descripcion_podcast); ?>
                            </p>

                            <!-- Información del autor y episodios -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="font-medium"><?php echo esc_html($autor_nombre); ?></span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                    </svg>
                                    <span class="font-medium"><?php echo esc_html($total_episodios); ?> episodios</span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Estado vacío -->
                <div class="col-span-full">
                    <div class="text-center py-16 bg-white rounded-2xl shadow-md">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay podcasts disponibles</h3>
                        <p class="text-gray-600 mb-6">Sé el primero en crear un podcast para tu comunidad</p>
                        <a href="#crear-podcast"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Crear Podcast
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
