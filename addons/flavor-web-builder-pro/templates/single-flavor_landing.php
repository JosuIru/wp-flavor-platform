<?php
/**
 * Template para flavor_landing - Full width sin meta del tema
 *
 * @package FlavorChatIA
 */

get_header();
?>

<style>
    .flavor-landing-fullwidth {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: var(--flavor-bg, #ffffff) !important;
        color: var(--flavor-text, #1f2937) !important;
    }
    .flavor-landing-fullwidth .entry-content {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .flavor-landing-fullwidth .entry-content > * {
        max-width: 100%;
    }
    /* Ocultar meta del tema WP dentro de landings */
    .flavor-landing-page .entry-meta,
    .flavor-landing-page .post-meta,
    .flavor-landing-page .entry-header .entry-title,
    .flavor-landing-page header.entry-header {
        display: none !important;
    }
</style>

<main id="main-content" class="flavor-landing-fullwidth">

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
