<?php
/**
 * Template Name: Landing Page
 * Template Post Type: page
 *
 * Template para landing pages con ancho completo y sin elementos del tema.
 * Ideal para páginas con shortcode [flavor_landing module="..."]
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1">

    <?php while (have_posts()): the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('flavor-landing-page'); ?>>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>

    <?php endwhile; ?>

</main>

<?php
get_footer();
