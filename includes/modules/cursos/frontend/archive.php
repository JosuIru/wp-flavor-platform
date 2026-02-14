<?php
/**
 * Template: Catálogo de cursos
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flavor-container py-8">
    <!-- Breadcrumbs -->
    <nav class="flex mb-6 text-sm">
        <ol class="inline-flex items-center space-x-2">
            <li>
                <a href="<?php echo home_url('/'); ?>" class="text-gray-600 hover:text-primary">
                    <?php esc_html_e('Inicio', 'flavor-chat-ia'); ?>
                </a>
            </li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">
                <?php esc_html_e('Cursos', 'flavor-chat-ia'); ?>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <h1 class="text-4xl font-bold mb-4">
        <?php esc_html_e('Catálogo de Cursos', 'flavor-chat-ia'); ?>
    </h1>
    <p class="text-lg text-gray-600 mb-8">
        <?php esc_html_e('Aprende nuevas habilidades con cursos de la comunidad', 'flavor-chat-ia'); ?>
    </p>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar con filtros -->
        <aside class="lg:w-1/4">
            <?php include dirname(__FILE__) . '/filters.php'; ?>
        </aside>

        <!-- Contenido principal -->
        <main class="lg:w-3/4">
            <?php
            $query_args = array(
                'post_type'      => 'curso',
                'posts_per_page' => 12,
            );

            $cursos_query = new WP_Query($query_args);

            if ($cursos_query->have_posts()) :
            ?>
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php
                    while ($cursos_query->have_posts()) :
                        $cursos_query->the_post();

                        // Obtener metadatos
                        $duracion = get_post_meta(get_the_ID(), '_duracion', true);
                        $nivel    = get_post_meta(get_the_ID(), '_nivel', true);
                        $precio   = get_post_meta(get_the_ID(), '_precio', true);
                        $plazas   = get_post_meta(get_the_ID(), '_plazas_disponibles', true);

                        // Obtener categorías
                        $categorias = get_the_terms(get_the_ID(), 'categoria_curso');
                    ?>
                        <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array('class' => 'w-full aspect-video object-cover')); ?>
                            <?php endif; ?>

                            <div class="p-5">
                                <?php if ($categorias && !is_wp_error($categorias)) : ?>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full mb-3">
                                        <?php echo esc_html($categorias[0]->name); ?>
                                    </span>
                                <?php endif; ?>

                                <h2 class="text-xl font-bold mb-2 line-clamp-2">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="text-sm text-gray-600 space-y-2 mb-4">
                                    <?php if ($duracion) : ?>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <?php echo esc_html($duracion); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($nivel) : ?>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <?php echo esc_html($nivel); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($plazas) : ?>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <?php echo esc_html($plazas); ?> <?php esc_html_e('plazas', 'flavor-chat-ia'); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($precio) : ?>
                                    <p class="text-2xl font-bold text-primary mb-4">
                                        <?php echo esc_html($precio); ?>€
                                    </p>
                                <?php endif; ?>

                                <a href="<?php the_permalink(); ?>"
                                   class="block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                                    <?php esc_html_e('Ver Curso', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <h3 class="text-2xl font-bold">
                        <?php esc_html_e('No hay cursos disponibles', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
