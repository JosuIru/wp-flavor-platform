<?php
/**
 * Template: Archivo de plazas de parking compartidas
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Parkings
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flavor-container py-8">
    <!-- Header -->
    <h1 class="text-4xl font-bold mb-4">
        <?php esc_html_e('Plazas de Parking Compartidas', 'flavor-chat-ia'); ?>
    </h1>
    <p class="text-lg text-gray-600 mb-8">
        <?php esc_html_e('Encuentra o comparte plazas de parking en tu zona', 'flavor-chat-ia'); ?>
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
                'post_type'      => 'parking',
                'posts_per_page' => 12,
            );

            $parkings_query = new WP_Query($query_args);

            if ($parkings_query->have_posts()) :
            ?>
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php
                    while ($parkings_query->have_posts()) :
                        $parkings_query->the_post();

                        // Obtener metadatos
                        $ubicacion  = get_post_meta(get_the_ID(), '_ubicacion', true);
                        $precio     = get_post_meta(get_the_ID(), '_precio_hora', true);
                        $disponible = get_post_meta(get_the_ID(), '_disponible', true);
                    ?>
                        <article class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition-all">
                            <h2 class="text-xl font-bold mb-3">
                                <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php if ($ubicacion) : ?>
                                <p class="text-gray-600 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <?php echo esc_html($ubicacion); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($precio) : ?>
                                <p class="text-2xl font-bold text-primary mb-4">
                                    <?php echo esc_html($precio); ?>€/<?php esc_html_e('hora', 'flavor-chat-ia'); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Estado de disponibilidad -->
                            <div class="mb-4">
                                <?php if ($disponible) : ?>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">
                                        <?php esc_html_e('Disponible', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-semibold">
                                        <?php esc_html_e('Ocupado', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="<?php the_permalink(); ?>"
                               class="block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                                <?php esc_html_e('Ver Plaza', 'flavor-chat-ia'); ?>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <h3 class="text-2xl font-bold">
                        <?php esc_html_e('No hay plazas disponibles', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
