<?php
/**
 * Template: Búsqueda de viajes compartidos
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$search_query = get_search_query();
?>

<div class="flavor-container py-8">
    <!-- Título de búsqueda -->
    <h1 class="text-4xl font-bold mb-6">
        <?php
        printf(
            /* translators: %s: término de búsqueda */
            esc_html__('Búsqueda de viajes: "%s"', 'flavor-chat-ia'),
            esc_html($search_query)
        );
        ?>
    </h1>

    <!-- Formulario de búsqueda -->
    <form method="get" class="mb-8 flex gap-2">
        <input
            type="text"
            name="s"
            value="<?php echo esc_attr($search_query); ?>"
            class="flex-1 px-4 py-3 border rounded-lg"
            autofocus
        />
        <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg">
            <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
        </button>
    </form>

    <?php
    // Query de búsqueda
    $search_args = array(
        'post_type' => 'carpooling',
        's'         => $search_query,
    );

    $search_results = new WP_Query($search_args);

    if ($search_results->have_posts()) :
    ?>
        <div class="space-y-6">
            <?php
            while ($search_results->have_posts()) :
                $search_results->the_post();

                // Obtener metadatos
                $origen  = get_post_meta(get_the_ID(), '_origen', true);
                $destino = get_post_meta(get_the_ID(), '_destino', true);
                $fecha   = get_post_meta(get_the_ID(), '_fecha_viaje', true);
                $precio  = get_post_meta(get_the_ID(), '_precio_plaza', true);
            ?>
                <article class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-2">
                        <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                            <?php echo esc_html($origen); ?> → <?php echo esc_html($destino); ?>
                        </a>
                    </h2>

                    <p class="text-gray-600 mb-3">
                        <?php echo date_i18n('d/m/Y', strtotime($fecha)); ?>
                    </p>

                    <p class="text-2xl font-bold text-primary">
                        <?php echo esc_html($precio); ?>€
                    </p>

                    <a href="<?php the_permalink(); ?>" class="mt-4 inline-block px-6 py-2 bg-primary text-white rounded-lg">
                        <?php esc_html_e('Ver Viaje', 'flavor-chat-ia'); ?>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

    <?php else : ?>
        <!-- Sin resultados -->
        <div class="text-center py-16">
            <h3 class="text-2xl font-bold">
                <?php esc_html_e('No se encontraron viajes', 'flavor-chat-ia'); ?>
            </h3>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
