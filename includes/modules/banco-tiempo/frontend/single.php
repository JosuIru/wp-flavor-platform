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
            <li class="inline-flex items-center">
                <a href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="text-gray-600 hover:text-primary transition-colors">
                    Banco de Tiempo
                </a>
            </li>
            <li>
                <span class="mx-2 text-gray-400">/</span>
            </li>
            <li class="text-gray-900 font-medium line-clamp-1" aria-current="page">
                <?php the_title(); ?>
            </li>
        </ol>
    </nav>

    <!-- Back Button -->
    <div class="mb-6">
        <a href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al listado
        </a>
    </div>

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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Descripción del servicio</h2>
                        <?php the_content(); ?>
                    </div>

                    <!-- Additional Details -->
                    <?php if ($servicio_ofrecido || $disponibilidad || $ubicacion) : ?>
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Detalles adicionales</h3>
                            <dl class="space-y-4">
                                <?php if ($servicio_ofrecido) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1">Servicio ofrecido</dt>
                                        <dd class="text-gray-600"><?php echo esc_html($servicio_ofrecido); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($disponibilidad) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1">Disponibilidad</dt>
                                        <dd class="text-gray-600"><?php echo esc_html($disponibilidad); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ubicacion) : ?>
                                    <div>
                                        <dt class="text-sm font-semibold text-gray-700 mb-1">Ubicación</dt>
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

                    <!-- Share Buttons -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Compartir</h3>
                        <div class="flex gap-3">
                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Facebook
                            </a>
                            <a
                                href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                                Twitter
                            </a>
                            <button
                                onclick="navigator.clipboard.writeText('<?php echo esc_js(get_permalink()); ?>'); alert('Enlace copiado al portapapeles');"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Copiar enlace
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <!-- Contact Card -->
            <div class="flavor-component bg-white rounded-xl shadow-lg p-6 mb-6 sticky top-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Contactar</h3>
                <div class="flex items-center gap-3 mb-4">
                    <?php echo get_avatar($autor_id, 60, '', '', array('class' => 'rounded-full')); ?>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo esc_html($autor_nombre); ?></p>
                        <p class="text-sm text-gray-600">Miembro de la comunidad</p>
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
                        Enviar mensaje
                    </a>
                <?php else : ?>
                    <a
                        href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"
                        class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
                    >
                        Inicia sesión para contactar
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
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Servicios relacionados</h3>
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
                                        Ver detalles
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
