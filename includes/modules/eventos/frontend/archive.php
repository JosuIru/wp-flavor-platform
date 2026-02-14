<?php
/**
 * Template: Calendario de eventos
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Eventos
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
                <?php esc_html_e('Eventos', 'flavor-chat-ia'); ?>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <h1 class="text-4xl font-bold mb-4">
        <?php esc_html_e('Calendario de Eventos', 'flavor-chat-ia'); ?>
    </h1>
    <p class="text-lg text-gray-600 mb-8">
        <?php esc_html_e('Descubre los próximos eventos de la comunidad', 'flavor-chat-ia'); ?>
    </p>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar con filtros -->
        <aside class="lg:w-1/4">
            <?php include dirname(__FILE__) . '/filters.php'; ?>
        </aside>

        <!-- Contenido principal -->
        <main class="lg:w-3/4">
            <!-- Formulario de búsqueda -->
            <form method="get" class="mb-6 flex gap-2">
                <input
                    type="text"
                    name="s"
                    placeholder="<?php esc_attr_e('Buscar eventos...', 'flavor-chat-ia'); ?>"
                    class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary"
                />
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php
            $query_args = array(
                'post_type'      => 'evento',
                'posts_per_page' => 12,
                'meta_key'       => '_fecha_evento',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => '_fecha_evento',
                        'value'   => date('Y-m-d'),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            );

            $eventos_query = new WP_Query($query_args);

            if ($eventos_query->have_posts()) :
            ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    while ($eventos_query->have_posts()) :
                        $eventos_query->the_post();

                        // Obtener metadatos
                        $fecha     = get_post_meta(get_the_ID(), '_fecha_evento', true);
                        $hora      = get_post_meta(get_the_ID(), '_hora_inicio', true);
                        $ubicacion = get_post_meta(get_the_ID(), '_ubicacion', true);
                        $precio    = get_post_meta(get_the_ID(), '_precio_entrada', true);
                    ?>
                        <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all group">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="aspect-video overflow-hidden">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium_large', array(
                                            'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform'
                                        )); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="p-5">
                                <!-- Badge de fecha -->
                                <div class="bg-primary text-white rounded-lg p-3 text-center mb-4">
                                    <div class="text-3xl font-bold">
                                        <?php echo date('d', strtotime($fecha)); ?>
                                    </div>
                                    <div class="text-sm uppercase">
                                        <?php echo date_i18n('M Y', strtotime($fecha)); ?>
                                    </div>
                                </div>

                                <h2 class="text-xl font-bold mb-2 line-clamp-2">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="text-sm text-gray-600 space-y-2 mb-4">
                                    <?php if ($hora) : ?>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <?php echo esc_html($hora); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($ubicacion) : ?>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            </svg>
                                            <?php echo esc_html($ubicacion); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($precio !== null && $precio !== '') : ?>
                                    <p class="text-2xl font-bold text-primary mb-4">
                                        <?php echo $precio == '0' ? esc_html__('Gratis', 'flavor-chat-ia') : esc_html($precio) . '€'; ?>
                                    </p>
                                <?php endif; ?>

                                <a href="<?php the_permalink(); ?>"
                                   class="block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                                    <?php esc_html_e('Ver Evento', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-2xl font-bold">
                        <?php esc_html_e('No hay eventos próximos', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
