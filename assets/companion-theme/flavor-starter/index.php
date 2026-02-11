<?php
/**
 * Template principal
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <?php if (is_home() && !is_front_page()): ?>
            <header class="mb-10">
                <h1 class="text-4xl font-bold text-gray-900">
                    <?php single_post_title(); ?>
                </h1>
            </header>
        <?php endif; ?>

        <?php if (have_posts()): ?>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php while (have_posts()): the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 group'); ?>>

                        <?php if (has_post_thumbnail()): ?>
                            <a href="<?php the_permalink(); ?>" class="block aspect-video overflow-hidden">
                                <?php the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php the_permalink(); ?>" class="block aspect-video bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </a>
                        <?php endif; ?>

                        <div class="p-6">
                            <header class="mb-3">
                                <?php if (get_post_type() === 'post'): ?>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                        <time datetime="<?php echo get_the_date('c'); ?>">
                                            <?php echo get_the_date(); ?>
                                        </time>
                                        <?php if (has_category()): ?>
                                            <span class="text-gray-300">|</span>
                                            <?php the_category(', '); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <h2 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>
                            </header>

                            <div class="text-gray-600 text-sm line-clamp-3">
                                <?php the_excerpt(); ?>
                            </div>

                            <footer class="mt-5 pt-4 border-t border-gray-100">
                                <a href="<?php the_permalink(); ?>" class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                    <?php esc_html_e('Leer artículo', 'flavor-starter'); ?>
                                    <svg class="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </a>
                            </footer>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <!-- Paginación -->
            <nav class="mt-16" aria-label="<?php esc_attr_e('Paginación', 'flavor-starter'); ?>">
                <?php
                the_posts_pagination([
                    'mid_size'  => 2,
                    'prev_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
                    'next_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
                ]);
                ?>
            </nav>

        <?php else: ?>

            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    <?php esc_html_e('No se encontraron entradas', 'flavor-starter'); ?>
                </h2>
                <p class="text-gray-600 max-w-md mx-auto">
                    <?php esc_html_e('Parece que no hay contenido disponible en este momento. Vuelve pronto para ver las novedades.', 'flavor-starter'); ?>
                </p>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
