<?php
/**
 * Frontend: Archive de Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$libros = $libros ?? [];
$total_libros = $total_libros ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive biblioteca">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Biblioteca Comunitaria', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Comparte y descubre libros con tus vecinos', 'flavor-chat-ia'); ?></p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_libros); ?> libros disponibles</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de libros -->
            <main class="flex-1">
                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($libros); ?></span> de <?php echo esc_html($total_libros); ?> libros
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Titulo A-Z', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Autor A-Z', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mejor valorados', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <?php if (empty($libros)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay libros disponibles', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500"><?php echo esc_html__('Prueba a modificar los filtros de busqueda', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Grid de libros -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($libros as $libro): ?>
                            <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                                <div class="relative aspect-[2/3] overflow-hidden">
                                    <img src="<?php echo esc_url($libro['portada'] ?? 'https://picsum.photos/seed/libro' . rand(1,100) . '/300/450'); ?>"
                                         alt="<?php echo esc_attr($libro['titulo']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

                                    <!-- Badge disponibilidad -->
                                    <?php if (!empty($libro['disponible'])): ?>
                                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-bold bg-green-500 text-white"><?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?></span>
                                    <?php else: ?>
                                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-bold bg-orange-500 text-white"><?php echo esc_html__('Prestado', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="p-4">
                                    <h2 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 mb-1">
                                        <a href="<?php echo esc_url($libro['url'] ?? '#'); ?>">
                                            <?php echo esc_html($libro['titulo']); ?>
                                        </a>
                                    </h2>
                                    <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($libro['autor'] ?? 'Autor desconocido'); ?></p>
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                                        <?php echo esc_html($libro['genero'] ?? 'General'); ?>
                                    </span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_libros > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_libros / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-indigo-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
