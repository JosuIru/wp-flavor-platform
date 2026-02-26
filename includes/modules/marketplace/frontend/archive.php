<?php
/**
 * Marketplace - Archive Template
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="flavor-container py-8">
    <nav class="flex mb-6 text-sm"><ol class="inline-flex items-center space-x-2"><li><a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-primary"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a></li><li><span class="mx-2 text-gray-400">/</span></li><li class="text-gray-900 font-medium"><?php echo esc_html__('Marketplace', 'flavor-chat-ia'); ?></li></ol></nav>
    <h1 class="text-4xl font-bold mb-4"><?php echo esc_html__('Marketplace Local', 'flavor-chat-ia'); ?></h1>
    <p class="text-lg text-gray-600 mb-8"><?php echo esc_html__('Compra y vende artículos de segunda mano en tu comunidad', 'flavor-chat-ia'); ?></p>
    <div class="flex flex-col lg:flex-row gap-8">
        <aside class="lg:w-1/4"><?php include dirname(__FILE__) . '/filters.php'; ?></aside>
        <main class="lg:w-3/4">
            <form method="get" class="mb-6 flex gap-2">
                <input type="text" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr__('Buscar artículos...', 'flavor-chat-ia'); ?>" class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary"/>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></button>
            </form>
            <?php
            $query = new WP_Query(array('post_type' => 'marketplace_item', 'posts_per_page' => 12, 'paged' => get_query_var('paged') ?: 1));
            if ($query->have_posts()) :
                echo '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">';
                while ($query->have_posts()) : $query->the_post();
                    $precio = get_post_meta(get_the_ID(), '_marketplace_precio', true);
                    $condicion = get_post_meta(get_the_ID(), '_marketplace_condicion', true);
                    echo '<article class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all overflow-hidden group">';
                    if (has_post_thumbnail()) : echo '<div class="aspect-square overflow-hidden"><a href="' . get_permalink() . '">';
                        the_post_thumbnail('medium_large', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform'));
                        echo '</a></div>';
                    endif;
                    echo '<div class="p-5"><h2 class="text-xl font-bold mb-2 line-clamp-2 group-hover:text-primary"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
                    echo '<p class="text-2xl font-bold text-primary mb-3">' . esc_html($precio) . '€</p>';
                    if ($condicion) echo '<span class="inline-block px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-700 rounded-full mb-3">' . esc_html($condicion) . '</span>';
                    echo '<a href="' . get_permalink() . '" class="block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Ver Detalles</a></div></article>';
                endwhile;
                echo '</div>';
            else :
                echo '<div class="text-center py-16"><svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><h3 class="text-2xl font-bold mb-2">No hay artículos</h3></div>';
            endif;
            wp_reset_postdata();
            ?>
        </main>
    </div>
</div>
<?php get_footer(); ?>
