<?php
/**
 * Template para Landing Pages del Visual Builder
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div id="primary" class="content-area flavor-vb-landing">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();

            // Obtener datos del Visual Builder
            $builder = Flavor_Visual_Builder::get_instance();
            $data = $builder->get_builder_data(get_the_ID());

            if (!empty($data) && !empty($data['content'])) {
                // Renderizar contenido del builder
                foreach ($data['content'] as $item) {
                    if ($item['type'] === 'section') {
                        echo $builder->render_section($item);
                    } elseif ($item['type'] === 'component') {
                        echo $builder->render_component($item);
                    }
                }
            } else {
                // Contenido por defecto de WordPress
                the_content();
            }
        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();
