<?php
/**
 * Banco de Tiempo - Single Service Template
 * Displays individual service detail
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $servicio_ofrecido = get_post_meta(get_the_ID(), '_servicio_ofrecido', true);
    $tiempo_estimado = get_post_meta(get_the_ID(), '_tiempo_estimado', true);
    $disponibilidad = get_post_meta(get_the_ID(), '_disponibilidad', true);
    $ubicacion = get_post_meta(get_the_ID(), '_ubicacion', true);
    $autor_id = get_the_author_meta('ID');
    $autor_nombre = get_the_author();
    $autor_email = get_the_author_meta('user_email');
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    Flavor_Breadcrumbs::render_with_back(
        ['archive_label' => __('Banco de Tiempo', 'flavor-chat-ia'), 'archive_url' => home_url('/banco-tiempo/')],
        home_url('/banco-tiempo/'),
        __('Volver al listado', 'flavor-chat-ia')
    );
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <article class="flavor-component bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Featured Image -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="aspect-video overflow-hidden">
                        <?php the_post_thumbnail('large', array('class' => 'w-full h-full object-cover')); ?>
                    </div>
                <?php else : ?>
                    <div class="aspect-video bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                        <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="p-8">
                    <!-- Category Badge -->
                    <?php
                    $categorias = get_the_terms(get_the_ID(), 'categoria_servicio');
                    if ($categorias && !is_wp_error($categorias)) :
                    ?>
                        <span class="inline-block px-4 py-2 text-sm font-semibold text-primary bg-primary bg-opacity-10 rounded-full mb-4">
                            <?php echo esc_html($categorias[0]->name); ?>
                        </span>
                    <?php endif; ?>

                    <!-- Title -->
                    <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php the_title(); ?></h1>

                    <!-- Meta Info -->
                    <div class="flex flex-wrap items-center gap-6 text-gray-600 mb-6 pb-6 border-b">
                        <div class="flex items-center gap-2">
                            <?php echo get_avatar($autor_id, 40, '', '', array('class' => 'rounded-full')); ?>
                            <span class="font-medium"><?php echo esc_html($autor_nombre); ?></span>
                        </div>
                        <?php if ($tiempo_estimado) : ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><?php echo esc_html($tiempo_estimado); ?> horas</span>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><?php echo get_the_date(); ?></span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="prose max-w-none mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Descripción del servicio', 'flavor-chat-ia'); ?></h2>
                        <?php the_content(); ?>
                    </div>

                    <!-- Additional Details -->
                    <?php if ($servicio_ofrecido || $disponibilidad || $ubicacion) : ?>
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Detalles adicionales', 'flavor-chat-ia'); ?></h3>
                            <dl class="space-y-4">
                                <?php if ($servicio_ofrecido) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1"><?php echo esc_html__('Servicio ofrecido', 'flavor-chat-ia'); ?></dt>
                                        <dd class="text-gray-600"><?php echo esc_html($servicio_ofrecido); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($disponibilidad) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1"><?php echo esc_html__('Disponibilidad', 'flavor-chat-ia'); ?></dt>
                                        <dd class="text-gray-600"><?php echo esc_html($disponibilidad); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ubicacion) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1"><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></dt>
                                        <dd class="text-gray-600 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <?php echo esc_html($ubicacion); ?>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Shared features: valoraciones, favoritos, compartir
                    if (function_exists('flavor_render_post_features')) {
                        flavor_render_post_features(['ratings', 'favorites', 'share', 'views']);
                    }
                    ?>
                </div>
            </article>

            <?php
            // Tabs de integración de módulos de red (red_social, multimedia, foros, chat_grupos)
            Flavor_Chat_Helpers::render_integration_tabs('banco_tiempo', get_the_ID());
            ?>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <!-- Contact Card -->
            <div class="flavor-component bg-white rounded-xl shadow-lg p-6 mb-6 sticky top-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Contactar', 'flavor-chat-ia'); ?></h3>
                <div class="flex items-center gap-3 mb-4">
                    <?php echo get_avatar($autor_id, 60, '', '', array('class' => 'rounded-full')); ?>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html($autor_nombre); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html__('Miembro de la comunidad', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mb-4">
                    Contacta con <?php echo esc_html($autor_nombre); ?> para solicitar este servicio.
                </p>
                <?php if (is_user_logged_in()) : ?>
                    <a
                        href="mailto:<?php echo esc_attr($autor_email); ?>?subject=Solicitud%20de%20servicio:%20<?php echo rawurlencode(get_the_title()); ?>"
                        class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                    >
                        <?php echo esc_html__('Enviar mensaje', 'flavor-chat-ia'); ?>
                    </a>
                <?php else : ?>
                    <a
                        href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"
                        class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                    >
                        <?php echo esc_html__('Inicia sesión para contactar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Related Services -->
            <?php
            $categorias = get_the_terms(get_the_ID(), 'categoria_servicio');
            if ($categorias && !is_wp_error($categorias)) :
                $categoria_ids = wp_list_pluck($categorias, 'term_id');

                $related_args = array(
                    'post_type' => 'banco_tiempo',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'categoria_servicio',
                            'field' => 'term_id',
                            'terms' => $categoria_ids,
                        ),
                    ),
                );

                $related_query = new WP_Query($related_args);

                if ($related_query->have_posts()) :
            ?>
                <div class="flavor-component bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Servicios relacionados', 'flavor-chat-ia'); ?></h3>
                    <div class="space-y-4">
                        <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                            <article class="border-b pb-4 last:border-b-0 last:pb-0">
                                <a href="<?php the_permalink(); ?>" class="group">
                                    <h4 class="font-semibold text-gray-900 group-hover:text-primary transition-colors mb-2 line-clamp-2">
                                        <?php the_title(); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                                        <?php echo esc_html(get_the_excerpt()); ?>
                                    </p>
                                    <span class="text-primary text-sm font-medium inline-flex items-center gap-1">
                                        <?php echo esc_html__('Ver detalles', 'flavor-chat-ia'); ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </span>
                                </a>
                            </article>
                        <?php endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            <?php
                endif;
            endif;
            ?>
        </aside>
    </div>
</div>

<?php
endwhile;
get_footer();
?>
