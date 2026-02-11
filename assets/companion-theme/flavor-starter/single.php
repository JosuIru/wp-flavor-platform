<?php
/**
 * Template para entradas individuales
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1">

    <?php while (have_posts()): the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <!-- Hero del artículo -->
            <header class="bg-gradient-to-b from-gray-50 to-white py-12 md:py-20">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

                    <!-- Categorías -->
                    <?php if (has_category()): ?>
                        <div class="mb-4">
                            <?php
                            $categorias = get_the_category();
                            foreach ($categorias as $cat):
                            ?>
                                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full hover:bg-blue-200 transition-colors">
                                    <?php echo esc_html($cat->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Título -->
                    <h1 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">
                        <?php the_title(); ?>
                    </h1>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center justify-center gap-4 text-gray-600">
                        <!-- Autor -->
                        <div class="flex items-center gap-2">
                            <?php echo get_avatar(get_the_author_meta('ID'), 40, '', '', ['class' => 'rounded-full']); ?>
                            <span class="font-medium"><?php the_author(); ?></span>
                        </div>

                        <span class="text-gray-300">|</span>

                        <!-- Fecha -->
                        <time datetime="<?php echo get_the_date('c'); ?>" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <?php echo get_the_date(); ?>
                        </time>

                        <!-- Tiempo de lectura estimado -->
                        <?php
                        $contenido = get_the_content();
                        $palabras = str_word_count(strip_tags($contenido));
                        $minutos_lectura = ceil($palabras / 200);
                        ?>
                        <span class="text-gray-300">|</span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php printf(esc_html__('%d min de lectura', 'flavor-starter'), $minutos_lectura); ?>
                        </span>
                    </div>
                </div>
            </header>

            <!-- Imagen destacada -->
            <?php if (has_post_thumbnail()): ?>
                <figure class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 md:-mt-10 mb-10">
                    <div class="rounded-2xl overflow-hidden shadow-xl">
                        <?php the_post_thumbnail('large', ['class' => 'w-full h-auto']); ?>
                    </div>
                </figure>
            <?php endif; ?>

            <!-- Contenido -->
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="entry-content prose prose-lg max-w-none prose-headings:font-bold prose-a:text-blue-600 prose-img:rounded-xl prose-blockquote:border-l-blue-500">
                    <?php the_content(); ?>
                </div>

                <?php
                // Paginación para entradas con <!--nextpage-->
                wp_link_pages([
                    'before' => '<nav class="page-links mt-8 pt-8 border-t border-gray-200 flex items-center gap-2"><span class="text-gray-600">' . esc_html__('Páginas:', 'flavor-starter') . '</span>',
                    'after'  => '</nav>',
                ]);
                ?>

                <!-- Tags -->
                <?php if (has_tag()): ?>
                    <footer class="mt-10 pt-8 border-t border-gray-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-gray-600 font-medium"><?php esc_html_e('Etiquetas:', 'flavor-starter'); ?></span>
                            <?php the_tags('', '', ''); ?>
                        </div>
                    </footer>
                <?php endif; ?>

                <!-- Navegación entre entradas -->
                <nav class="mt-10 pt-8 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    ?>

                    <?php if ($prev_post): ?>
                        <a href="<?php echo get_permalink($prev_post); ?>" class="group p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <span class="text-sm text-gray-500 flex items-center gap-1 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                <?php esc_html_e('Anterior', 'flavor-starter'); ?>
                            </span>
                            <span class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                                <?php echo get_the_title($prev_post); ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if ($next_post): ?>
                        <a href="<?php echo get_permalink($next_post); ?>" class="group p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors text-right">
                            <span class="text-sm text-gray-500 flex items-center justify-end gap-1 mb-2">
                                <?php esc_html_e('Siguiente', 'flavor-starter'); ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                            <span class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                                <?php echo get_the_title($next_post); ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </nav>

            </div>

            <!-- Comentarios -->
            <?php if (comments_open() || get_comments_number()): ?>
                <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 border-t border-gray-200">
                    <?php comments_template(); ?>
                </section>
            <?php endif; ?>

        </article>

    <?php endwhile; ?>

</main>

<?php
get_footer();
