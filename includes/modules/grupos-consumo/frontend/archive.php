<?php
/**
 * Grupos de Consumo - Archive Template
 * Products catalog listing
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flavor-container py-8">
    <nav class="flex mb-6 text-sm" aria-label="<?php echo esc_attr__('Breadcrumb', 'flavor-chat-ia'); ?>">
        <ol class="inline-flex items-center space-x-2">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-primary transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium" aria-current="page"><?php echo esc_html__('Grupos de Consumo', 'flavor-chat-ia'); ?></li>
        </ol>
    </nav>

    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Grupos de Consumo', 'flavor-chat-ia'); ?></h1>
        <p class="text-lg text-gray-600"><?php echo esc_html__('Productos locales y ecológicos directamente de productores de tu comunidad.', 'flavor-chat-ia'); ?></p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <aside class="lg:w-1/4">
            <?php include dirname(__FILE__) . '/filters.php'; ?>
        </aside>

        <main class="lg:w-3/4">
            <div class="mb-6">
                <form method="get" class="flex gap-2">
                    <input type="text" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr__('Buscar productos...', 'flavor-chat-ia'); ?>" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"/>
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></button>
                </form>
            </div>

            <?php
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'grupo_consumo',
                'posts_per_page' => 12,
                'paged' => $paged,
                'post_status' => 'publish',
            );

            if (!empty($_GET['categoria'])) {
                $args['tax_query'] = array(array('taxonomy' => 'categoria_producto', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['categoria'])));
            }

            $productos_query = new WP_Query($args);

            if ($productos_query->have_posts()) :
            ?>
                <div class="mb-4 text-gray-600">
                    Mostrando <?php echo $productos_query->post_count; ?> de <?php echo $productos_query->found_posts; ?> productos
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    <?php while ($productos_query->have_posts()) : $productos_query->the_post();
                        $precio = get_post_meta(get_the_ID(), '_precio', true);
                        $unidad = get_post_meta(get_the_ID(), '_unidad', true);
                        $disponibilidad = get_post_meta(get_the_ID(), '_disponibilidad', true);
                        $productor = get_post_meta(get_the_ID(), '_productor', true);
                    ?>
                        <article class="flavor-component bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="aspect-square overflow-hidden">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium_large', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <div class="aspect-square bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="p-5">
                                <?php
                                $categorias = get_the_terms(get_the_ID(), 'categoria_producto');
                                if ($categorias && !is_wp_error($categorias)) :
                                ?>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full mb-3">
                                        <?php echo esc_html($categorias[0]->name); ?>
                                    </span>
                                <?php endif; ?>

                                <h2 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>

                                <?php if ($productor) : ?>
                                    <p class="text-sm text-gray-600 mb-3">por <?php echo esc_html($productor); ?></p>
                                <?php endif; ?>

                                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html(get_the_excerpt()); ?></p>

                                <div class="flex items-center justify-between border-t pt-4">
                                    <?php if ($precio) : ?>
                                        <div class="text-2xl font-bold text-primary">
                                            <?php echo esc_html($precio); ?>€
                                            <?php if ($unidad) : ?>
                                                <span class="text-sm text-gray-600 font-normal">/<?php echo esc_html($unidad); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($disponibilidad === 'disponible') : ?>
                                        <span class="text-xs font-semibold text-green-700 bg-green-100 px-3 py-1 rounded-full"><?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?></span>
                                    <?php else : ?>
                                        <span class="text-xs font-semibold text-red-700 bg-red-100 px-3 py-1 rounded-full"><?php echo esc_html__('Agotado', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?php the_permalink(); ?>" class="mt-4 block w-full text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                                    <?php echo esc_html__('Ver Producto', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php if ($productos_query->max_num_pages > 1) : ?>
                    <nav class="flex justify-center" aria-label="<?php echo esc_attr__('Paginación', 'flavor-chat-ia'); ?>">
                        <?php echo paginate_links(array('total' => $productos_query->max_num_pages, 'current' => $paged)); ?>
                    </nav>
                <?php endif; ?>

            <?php else : ?>
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo esc_html__('No hay productos disponibles', 'flavor-chat-ia'); ?></h3>
                    <p class="text-gray-600"><?php echo esc_html__('No se encontraron productos en este momento.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
