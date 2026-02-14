<?php
/**
 * Template: Catálogo de biblioteca comunitaria
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flavor-container py-8">
    <!-- Header -->
    <h1 class="text-4xl font-bold mb-4">
        <?php esc_html_e('Biblioteca Comunitaria', 'flavor-chat-ia'); ?>
    </h1>
    <p class="text-lg text-gray-600 mb-8">
        <?php esc_html_e('Catálogo de libros disponibles para préstamo', 'flavor-chat-ia'); ?>
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
                    placeholder="<?php esc_attr_e('Buscar libros...', 'flavor-chat-ia'); ?>"
                    class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary"
                />
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php
            $query_args = array(
                'post_type'      => 'biblioteca',
                'posts_per_page' => 12,
            );

            $libros_query = new WP_Query($query_args);

            if ($libros_query->have_posts()) :
            ?>
                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <?php
                    while ($libros_query->have_posts()) :
                        $libros_query->the_post();

                        // Obtener metadatos
                        $autor      = get_post_meta(get_the_ID(), '_autor', true);
                        $isbn       = get_post_meta(get_the_ID(), '_isbn', true);
                        $disponible = get_post_meta(get_the_ID(), '_disponible', true);
                    ?>
                        <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('class' => 'w-full aspect-[2/3] object-cover')); ?>
                            <?php endif; ?>

                            <div class="p-4">
                                <h2 class="text-lg font-bold mb-2 line-clamp-2">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-primary">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <?php if ($autor) : ?>
                                    <p class="text-sm text-gray-600 mb-3">
                                        <?php echo esc_html($autor); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Estado de disponibilidad -->
                                <div class="mb-3">
                                    <?php if ($disponible) : ?>
                                        <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full">
                                            <?php esc_html_e('Disponible', 'flavor-chat-ia'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full">
                                            <?php esc_html_e('Prestado', 'flavor-chat-ia'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?php the_permalink(); ?>"
                                   class="block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                                    <?php esc_html_e('Ver Detalles', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

            <?php else : ?>
                <!-- Estado vacío -->
                <div class="text-center py-16">
                    <h3 class="text-2xl font-bold">
                        <?php esc_html_e('No hay libros', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>
