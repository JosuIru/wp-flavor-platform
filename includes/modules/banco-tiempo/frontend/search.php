<?php
/**
 * Banco de Tiempo - Search Results Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$busqueda_termino = get_search_query();
?>

<div class="flavor-container py-8">
    <!-- Breadcrumbs -->
    <nav class="flex mb-6 text-sm" aria-label="<?php echo esc_attr__('Breadcrumb', 'flavor-chat-ia'); ?>">
        <ol class="inline-flex items-center space-x-2">
            <li class="inline-flex items-center">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-primary transition-colors">
                    <?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?>
                </a>
            </li>
            <li>
                <span class="mx-2 text-gray-400">/</span>
            </li>
            <li class="inline-flex items-center">
                <a href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="text-gray-600 hover:text-primary transition-colors">
                    <?php echo esc_html__('Banco de Tiempo', 'flavor-chat-ia'); ?>
                </a>
            </li>
            <li>
                <span class="mx-2 text-gray-400">/</span>
            </li>
            <li class="text-gray-900 font-medium" aria-current="page">
                <?php echo esc_html__('Búsqueda', 'flavor-chat-ia'); ?>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Resultados de búsqueda
            <?php if ($busqueda_termino) : ?>
                <span class="text-primary">: "<?php echo esc_html($busqueda_termino); ?>"</span>
            <?php endif; ?>
        </h1>
    </div>

    <!-- Search Bar -->
    <div class="mb-8 max-w-3xl">
        <form method="get" action="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="flex gap-2">
            <input
                type="text"
                name="s"
                value="<?php echo esc_attr($busqueda_termino); ?>"
                placeholder="<?php echo esc_attr__('Buscar servicios...', 'flavor-chat-ia'); ?>"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                autofocus
            />
            <button
                type="submit"
                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
            >
                <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <?php
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    $args = array(
        'post_type' => 'banco_tiempo',
        'posts_per_page' => 12,
        'paged' => $paged,
        's' => $busqueda_termino,
        'post_status' => 'publish',
    );

    $busqueda_query = new WP_Query($args);

    if ($busqueda_query->have_posts()) :
    ?>
        <!-- Results Count -->
        <div class="mb-6">
            <p class="text-lg text-gray-700">
                <?php echo esc_html__('Se encontraron', 'flavor-chat-ia'); ?> <strong class="text-primary"><?php echo $busqueda_query->found_posts; ?></strong> <?php echo esc_html__('servicios', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Results Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($busqueda_query->have_posts()) : $busqueda_query->the_post();
                $tiempo_estimado = get_post_meta(get_the_ID(), '_tiempo_estimado', true);
                $autor_id = get_the_author_meta('ID');
                $autor_nombre = get_the_author();
            ?>
                <article class="flavor-component bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <!-- Service Image -->
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="aspect-video overflow-hidden">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium_large', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="aspect-video bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <div class="p-5">
                        <!-- Category Badge -->
                        <?php
                        $categorias = get_the_terms(get_the_ID(), 'categoria_servicio');
                        if ($categorias && !is_wp_error($categorias)) :
                        ?>
                            <span class="inline-block px-3 py-1 text-xs font-semibold text-primary bg-primary bg-opacity-10 rounded-full mb-3">
                                <?php echo esc_html($categorias[0]->name); ?>
                            </span>
                        <?php endif; ?>

                        <!-- Title with highlighted search term -->
                        <h2 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                            <a href="<?php the_permalink(); ?>">
                                <?php
                                $titulo = get_the_title();
                                if ($busqueda_termino) {
                                    $titulo = preg_replace('/(' . preg_quote($busqueda_termino, '/') . ')/i', '<mark class="bg-yellow-200">$1</mark>', $titulo);
                                }
                                echo $titulo;
                                ?>
                            </a>
                        </h2>

                        <!-- Excerpt with highlighted search term -->
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            <?php
                            $extracto = get_the_excerpt();
                            if ($busqueda_termino) {
                                $extracto = preg_replace('/(' . preg_quote($busqueda_termino, '/') . ')/i', '<mark class="bg-yellow-200">$1</mark>', $extracto);
                            }
                            echo $extracto;
                            ?>
                        </p>

                        <!-- Meta Info -->
                        <div class="flex items-center justify-between text-sm border-t pt-4">
                            <div class="flex items-center gap-2">
                                <?php echo get_avatar($autor_id, 32, '', '', array('class' => 'rounded-full')); ?>
                                <span class="text-gray-700 font-medium"><?php echo esc_html($autor_nombre); ?></span>
                            </div>
                            <?php if ($tiempo_estimado) : ?>
                                <div class="flex items-center gap-1 text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span><?php echo esc_html($tiempo_estimado); ?>h</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CTA Button -->
                        <a
                            href="<?php the_permalink(); ?>"
                            class="mt-4 block w-full text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                        >
                            <?php echo esc_html__('Ver Detalles', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($busqueda_query->max_num_pages > 1) : ?>
            <nav class="flex justify-center items-center gap-2" aria-label="<?php echo esc_attr__('Paginación', 'flavor-chat-ia'); ?>">
                <?php
                echo paginate_links(array(
                    'total' => $busqueda_query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
                    'next_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
                    'type' => 'array',
                ));
                ?>
            </nav>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo esc_html__('No se encontraron resultados', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-600 mb-6">
                No encontramos servicios que coincidan con tu búsqueda
                <?php if ($busqueda_termino) : ?>
                    <strong>"<?php echo esc_html($busqueda_termino); ?>"</strong>
                <?php endif; ?>
            </p>
            <div class="space-y-4">
                <p class="text-gray-600"><?php echo esc_html__('Intenta con:', 'flavor-chat-ia'); ?></p>
                <ul class="text-gray-600 space-y-2">
                    <li><?php echo esc_html__('Palabras clave diferentes', 'flavor-chat-ia'); ?></li>
                    <li><?php echo esc_html__('Términos más generales', 'flavor-chat-ia'); ?></li>
                    <li><?php echo esc_html__('Verificar la ortografía', 'flavor-chat-ia'); ?></li>
                </ul>
                <a href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors mt-4">
                    <?php echo esc_html__('Ver todos los servicios', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    <?php endif;
    wp_reset_postdata();
    ?>
</div>

<?php get_footer(); ?>
