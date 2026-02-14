<?php
/**
 * Template: Archivo de viajes compartidos (Carpooling)
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Carpooling
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
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">
                <?php esc_html_e('Carpooling', 'flavor-chat-ia'); ?>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <h1 class="text-4xl font-bold mb-4">
        <?php esc_html_e('Viajes Compartidos', 'flavor-chat-ia'); ?>
    </h1>
    <p class="text-lg text-gray-600 mb-8">
        <?php esc_html_e('Encuentra o comparte viajes con tu comunidad', 'flavor-chat-ia'); ?>
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
                    placeholder="<?php esc_attr_e('Buscar destino...', 'flavor-chat-ia'); ?>"
                    class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary"
                />
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php
            // Query de viajes
            $query_args = array(
                'post_type'      => 'carpooling',
                'posts_per_page' => 12,
                'meta_query'     => array(
                    array(
                        'key'     => '_fecha_viaje',
                        'value'   => date('Y-m-d'),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            );

            $viajes_query = new WP_Query($query_args);

            if ($viajes_query->have_posts()) :
            ?>
                <div class="space-y-6">
                    <?php
                    while ($viajes_query->have_posts()) :
                        $viajes_query->the_post();

                        // Obtener metadatos del viaje
                        $origen  = get_post_meta(get_the_ID(), '_origen', true);
                        $destino = get_post_meta(get_the_ID(), '_destino', true);
                        $fecha   = get_post_meta(get_the_ID(), '_fecha_viaje', true);
                        $hora    = get_post_meta(get_the_ID(), '_hora_salida', true);
                        $plazas  = get_post_meta(get_the_ID(), '_plazas_disponibles', true);
                        $precio  = get_post_meta(get_the_ID(), '_precio_plaza', true);
                    ?>
                        <article class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition-all">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Info del viaje -->
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold mb-3 text-gray-900">
                                        <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                            <?php echo esc_html($origen); ?> → <?php echo esc_html($destino); ?>
                                        </a>
                                    </h2>

                                    <div class="flex flex-wrap gap-4 text-gray-600 mb-4">
                                        <!-- Fecha y hora -->
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>
                                                <?php echo date_i18n('d/m/Y', strtotime($fecha)); ?> - <?php echo esc_html($hora); ?>
                                            </span>
                                        </div>

                                        <!-- Plazas -->
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <span><?php echo esc_html($plazas); ?> <?php esc_html_e('plazas', 'flavor-chat-ia'); ?></span>
                                        </div>
                                    </div>

                                    <!-- Conductor -->
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <?php echo get_avatar(get_the_author_meta('ID'), 40, '', '', array('class' => 'rounded-full')); ?>
                                            <span class="font-medium text-gray-700"><?php the_author(); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precio y CTA -->
                                <div class="flex flex-col items-end justify-between">
                                    <div class="text-3xl font-bold text-primary mb-4">
                                        <?php echo esc_html($precio); ?>€
                                        <span class="text-sm text-gray-600 font-normal">/<?php esc_html_e('plaza', 'flavor-chat-ia'); ?></span>
                                    </div>
                                    <a href="<?php the_permalink(); ?>" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">
                                        <?php esc_html_e('Ver Viaje', 'flavor-chat-ia'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <h3 class="text-2xl font-bold mb-2">
                        <?php esc_html_e('No hay viajes disponibles', 'flavor-chat-ia'); ?>
                    </h3>
                    <p class="text-gray-600">
                        <?php esc_html_e('No se encontraron viajes próximos', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
