<?php
/**
 * Banco de Tiempo - Archive Template
 * Displays listing of all time bank services
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flavor-container py-8">
    <!-- Breadcrumbs -->
    <nav class="flex mb-6 text-sm" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-2">
            <li class="inline-flex items-center">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-primary transition-colors">
                    Inicio
                </a>
            </li>
            <li>
                <span class="mx-2 text-gray-400">/</span>
            </li>
            <li class="text-gray-900 font-medium" aria-current="page">
                Banco de Tiempo
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Banco de Tiempo</h1>
        <p class="text-lg text-gray-600">Intercambia servicios con tu comunidad. Todos tenemos habilidades valiosas para compartir.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <aside class="lg:w-1/4">
            <?php include dirname(__FILE__) . '/filters.php'; ?>
        </aside>

        <!-- Services Grid -->
        <main class="lg:w-3/4">
            <!-- Search Bar -->
            <div class="mb-6">
                <form method="get" action="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="flex gap-2">
                    <input
                        type="text"
                        name="s"
                        value="<?php echo esc_attr(get_search_query()); ?>"
                        placeholder="Buscar servicios..."
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />
                    <button
                        type="submit"
                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                    >
                        Buscar
                    </button>
                </form>
            </div>

            <?php
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;

            $args = array(
                'post_type' => 'banco_tiempo',
                'posts_per_page' => 12,
                'paged' => $paged,
                'post_status' => 'publish',
            );

            // Apply filters
            if (!empty($_GET['categoria'])) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'categoria_servicio',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['categoria']),
                    ),
                );
            }

            if (!empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }

            $services_query = new WP_Query($args);

            if ($services_query->have_posts()) :
            ?>
                <!-- Results Count -->
                <div class="mb-4 text-gray-600">
                    Mostrando <?php echo $services_query->post_count; ?> de <?php echo $services_query->found_posts; ?> servicios
                </div>

                <!-- Services Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    <?php while ($services_query->have_posts()) : $services_query->the_post();
                        $servicio_ofrecido = get_post_meta(get_the_ID(), '_servicio_ofrecido', true);
                        $tiempo_estimado = get_post_meta(get_the_ID(), '_tiempo_estimado', true);
                        $autor_id = get_the_author_meta('ID');
                        $autor_nombre = get_the_author();
                    ?>
                        <article class="flavor-component bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            <!-- Service Image -->
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="aspect-video overflow-hidden">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium_large', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <div class="aspect-video bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <div class="p-5">
                                <!-- Category Badge -->
                                <?php
                                $categorias = get_the_terms(get_the_ID(), 'categoria_servicio');
                                if ($categorias && !is_wp_error($categorias)) :
                                ?>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold text-primary bg-primary bg-opacity-10 rounded-full mb-3">
                                        <?php echo esc_html($categorias[0]->name); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Title -->
                                <h2 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <!-- Description -->
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                    <?php echo esc_html(get_the_excerpt()); ?>
                                </p>

                                <!-- Meta Info -->
                                <div class="flex items-center justify-between text-sm border-t pt-4">
                                    <div class="flex items-center gap-2">
                                        <?php echo get_avatar($autor_id, 32, '', '', array('class' => 'rounded-full')); ?>
                                        <span class="text-gray-700 font-medium"><?php echo esc_html($autor_nombre); ?></span>
                                    </div>
                                    <?php if ($tiempo_estimado) : ?>
                                        <div class="flex items-center gap-1 text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span><?php echo esc_html($tiempo_estimado); ?>h</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- CTA Button -->
                                <a
                                    href="<?php the_permalink(); ?>"
                                    class="mt-4 block w-full text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                                >
                                    Ver Detalles
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($services_query->max_num_pages > 1) : ?>
                    <nav class="flex justify-center items-center gap-2" aria-label="Paginación">
                        <?php
                        echo paginate_links(array(
                            'total' => $services_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
                            'next_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
                            'type' => 'array',
                        ));
                        ?>
                    </nav>
                <?php endif; ?>

            <?php else : ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No hay servicios disponibles</h3>
                    <p class="text-gray-600 mb-6">No se encontraron servicios que coincidan con tu búsqueda.</p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        Ver todos los servicios
                    </a>
                </div>
            <?php endif;
            wp_reset_postdata();
            ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
