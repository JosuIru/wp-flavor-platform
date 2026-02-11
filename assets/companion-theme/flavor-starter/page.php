<?php
/**
 * Template para páginas
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1">

    <?php while (have_posts()): the_post(); ?>

        <?php
        // Detectar si la página tiene un shortcode de landing
        $contenido = get_the_content();
        $es_landing = has_shortcode($contenido, 'flavor_landing');
        ?>

        <?php if ($es_landing): ?>
            <!-- Landing Page: sin contenedor, full width -->
            <article id="post-<?php the_ID(); ?>" <?php post_class('flavor-landing-page'); ?>>
                    <div class="entry-content">
                        <?php
                        $content = get_the_content();
                        $rendered = apply_filters('the_content', $content);
                        echo do_shortcode($rendered);
                        ?>
                    </div>
            </article>

        <?php else: ?>
            <!-- Página normal: con contenedor -->
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

                    <header class="mb-10 text-center">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                            <?php the_title(); ?>
                        </h1>

                        <?php if (has_excerpt()): ?>
                            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                                <?php echo get_the_excerpt(); ?>
                            </p>
                        <?php endif; ?>
                    </header>

                    <?php if (has_post_thumbnail()): ?>
                        <figure class="mb-10 -mx-4 sm:mx-0 sm:rounded-2xl overflow-hidden">
                            <?php the_post_thumbnail('large', ['class' => 'w-full h-auto']); ?>
                        </figure>
                    <?php endif; ?>

                    <div class="entry-content prose prose-lg max-w-none prose-headings:font-bold prose-a:text-blue-600 prose-img:rounded-xl">
                        <?php the_content(); ?>
                    </div>

                    <?php
                    // Paginación para páginas con <!--nextpage-->
                    wp_link_pages([
                        'before' => '<nav class="page-links mt-8 pt-8 border-t border-gray-200 flex items-center gap-2"><span class="text-gray-600">' . esc_html__('Páginas:', 'flavor-starter') . '</span>',
                        'after'  => '</nav>',
                        'link_before' => '<span class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 hover:bg-blue-600 hover:text-white rounded-lg transition-colors">',
                        'link_after'  => '</span>',
                    ]);
                    ?>

                </div>
            </article>
        <?php endif; ?>

    <?php endwhile; ?>

</main>

<?php
get_footer();
