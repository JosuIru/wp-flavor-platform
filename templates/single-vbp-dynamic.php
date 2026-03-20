<?php
/**
 * Template: Single VBP Dynamic
 *
 * Plantilla para renderizar singles de CPTs usando Visual Builder Pro.
 *
 * @package Flavor_Chat_IA
 * @since 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

        $post      = get_queried_object();
        $templates = Flavor_VBP_Single_Templates::get_instance();
        $template  = $templates->get_template_for_cpt( $post->post_type );

        if ( $template ) :
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'vbp-single-dynamic' ); ?>>
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML renderizado por VBP
                echo $templates->render_template( $template->ID, $post );
                ?>
            </article>
            <?php
        else :
            // Fallback si no hay plantilla VBP
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>

                <footer class="entry-footer">
                    <?php
                    // Mostrar categorías, tags, etc.
                    $taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
                    foreach ( $taxonomies as $tax ) :
                        $terms = get_the_terms( $post, $tax->name );
                        if ( $terms && ! is_wp_error( $terms ) ) :
                            ?>
                            <div class="taxonomy-<?php echo esc_attr( $tax->name ); ?>">
                                <strong><?php echo esc_html( $tax->label ); ?>:</strong>
                                <?php echo get_the_term_list( $post->ID, $tax->name, '', ', ' ); ?>
                            </div>
                            <?php
                        endif;
                    endforeach;
                    ?>
                </footer>
            </article>
            <?php
        endif;

    endwhile;
endif;

get_footer();
