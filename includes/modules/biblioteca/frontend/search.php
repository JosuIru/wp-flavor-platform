<?php
/**
 * Template: Búsqueda de libros
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$search_query = get_search_query();
?>

<div class="flavor-container py-8">
    <!-- Título de búsqueda -->
    <h1 class="text-4xl font-bold mb-6">
        <?php
        printf(
            /* translators: %s: término de búsqueda */
            esc_html__('Búsqueda: "%s"', 'flavor-chat-ia'),
            esc_html($search_query)
        );
        ?>
    </h1>

    <?php
    $search_args = array(
        'post_type' => 'biblioteca',
        's'         => $search_query,
    );

    $search_results = new WP_Query($search_args);

    if ($search_results->have_posts()) :
    ?>
        <div class="grid md:grid-cols-4 gap-6">
            <?php
            while ($search_results->have_posts()) :
                $search_results->the_post();

                $autor = get_post_meta(get_the_ID(), '_autor', true);
            ?>
                <article class="bg-white rounded-xl shadow-md overflow-hidden">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium', array('class' => 'w-full aspect-[2/3] object-cover')); ?>
                    <?php endif; ?>

                    <div class="p-4">
                        <h2 class="text-lg font-bold mb-2">
                            <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                <?php the_title(); ?>
                            </a>
                        </h2>

                        <?php if ($autor) : ?>
                            <p class="text-sm text-gray-600">
                                <?php echo esc_html($autor); ?>
                            </p>
                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>"
                           class="mt-3 block text-center px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary-dark">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

    <?php else : ?>
        <!-- Sin resultados -->
        <div class="text-center py-16">
            <h3 class="text-2xl font-bold">
                <?php esc_html_e('Sin resultados', 'flavor-chat-ia'); ?>
            </h3>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
