<?php
/**
 * Template Name: Página en Blanco
 * Template Post Type: page
 *
 * Template completamente en blanco sin header ni footer.
 * Útil para páginas que necesitan control total del diseño.
 *
 * @package Flavor_Starter
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
</head>

<body <?php body_class('flavor-starter-blank antialiased'); ?>>
<?php wp_body_open(); ?>

<?php while (have_posts()): the_post(); ?>

    <main id="main-content">
        <?php the_content(); ?>
    </main>

<?php endwhile; ?>

<?php wp_footer(); ?>

</body>
</html>
