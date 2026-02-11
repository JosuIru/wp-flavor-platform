<?php
/**
 * Template para resultados de búsqueda
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Header de búsqueda -->
        <header class="mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php
                printf(
                    esc_html__('Resultados de búsqueda para: %s', 'flavor-starter'),
                    '<span class="text-blue-600">' . get_search_query() . '</span>'
                );
                ?>
            </h1>

            <?php if (have_posts()): ?>
                <p class="text-gray-600">
                    <?php
                    global $wp_query;
                    printf(
                        esc_html(_n('%d resultado encontrado', '%d resultados encontrados', $wp_query->found_posts, 'flavor-starter')),
                        $wp_query->found_posts
                    );
                    ?>
                </p>
            <?php endif; ?>

            <!-- Formulario de búsqueda -->
            <div class="mt-6 max-w-xl">
                <?php get_search_form(); ?>
            </div>
        </header>

        <?php if (have_posts()): ?>

            <div class="space-y-6">
                <?php while (have_posts()): the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow'); ?>>
                        <div class="flex gap-6">

                            <?php if (has_post_thumbnail()): ?>
                                <a href="<?php the_permalink(); ?>" class="flex-shrink-0 hidden sm:block">
                                    <?php the_post_thumbnail('thumbnail', ['class' => 'w-24 h-24 object-cover rounded-lg']); ?>
                                </a>
                            <?php endif; ?>

                            <div class="flex-1 min-w-0">
                                <!-- Tipo de contenido -->
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">
                                    <?php echo get_post_type_object(get_post_type())->labels->singular_name; ?>
                                </div>

                                <h2 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-blue-600 transition-colors">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="text-gray-600 text-sm line-clamp-2 mb-3">
                                    <?php the_excerpt(); ?>
                                </div>

                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <time datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>

                                    <a href="<?php the_permalink(); ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                        <?php esc_html_e('Leer más', 'flavor-starter'); ?> &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <!-- Paginación -->
            <nav class="mt-12" aria-label="<?php esc_attr_e('Paginación de resultados', 'flavor-starter'); ?>">
                <?php
                the_posts_pagination([
                    'mid_size'  => 2,
                    'prev_text' => '&larr; ' . esc_html__('Anterior', 'flavor-starter'),
                    'next_text' => esc_html__('Siguiente', 'flavor-starter') . ' &rarr;',
                ]);
                ?>
            </nav>

        <?php else: ?>

            <div class="text-center py-12 bg-gray-50 rounded-2xl">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                    <?php esc_html_e('No se encontraron resultados', 'flavor-starter'); ?>
                </h2>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    <?php esc_html_e('No hemos encontrado nada que coincida con tu búsqueda. Intenta con otros términos.', 'flavor-starter'); ?>
                </p>

                <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <?php esc_html_e('Volver al inicio', 'flavor-starter'); ?>
                </a>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
