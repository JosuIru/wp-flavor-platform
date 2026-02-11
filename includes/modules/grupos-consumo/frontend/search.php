<?php
/**
 * Grupos de Consumo - Search Template
 */
if (!defined('ABSPATH')) exit;
get_header();
$busqueda = get_search_query();
?>
<div class="flavor-container py-8">
    <h1 class="text-4xl font-bold mb-6">Resultados: "<?php echo esc_html($busqueda); ?>"</h1>
    <form method="get" class="mb-8 max-w-2xl flex gap-2">
        <input type="text" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php echo esc_attr__('Buscar productos...', 'flavor-chat-ia'); ?>" class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" autofocus/>
        <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></button>
    </form>
    <?php
    $query = new WP_Query(array('post_type' => 'grupo_consumo', 's' => $busqueda, 'posts_per_page' => 12));
    if ($query->have_posts()) :
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        while ($query->have_posts()) : $query->the_post();
            $precio = get_post_meta(get_the_ID(), '_precio', true);
            echo '<article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all">';
            if (has_post_thumbnail()) the_post_thumbnail('medium', array('class' => 'w-full aspect-square object-cover'));
            echo '<div class="p-5"><h2 class="text-xl font-bold mb-2"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
            echo '<p class="text-2xl font-bold text-primary">' . esc_html($precio) . '€</p>';
            echo '<a href="' . get_permalink() . '" class="mt-4 block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Ver</a></div></article>';
        endwhile;
        echo '</div>';
    else :
        echo '<div class="text-center py-16"><h3 class="text-2xl font-bold mb-2">Sin resultados</h3><p class="text-gray-600">No se encontraron productos.</p></div>';
    endif;
    wp_reset_postdata();
    ?>
</div>
<?php get_footer(); ?>
