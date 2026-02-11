<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
</head>

<body <?php body_class('flavor-starter-minimal antialiased'); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content">
    <?php esc_html_e('Saltar al contenido', 'flavor-starter'); ?>
</a>

<?php
/**
 * Renderizar header
 * Si el plugin Flavor Chat IA está activo, usa su sistema de layouts
 * Si no, usa el fallback del tema
 */
flavor_starter_header();
?>
