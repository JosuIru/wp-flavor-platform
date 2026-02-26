<?php
/**
 * Grupos de Consumo - Single Product Template
 */

if (!defined('ABSPATH')) exit;

get_header();
while (have_posts()) : the_post();
    $precio = get_post_meta(get_the_ID(), '_precio', true);
    $unidad = get_post_meta(get_the_ID(), '_unidad', true);
    $disponibilidad = get_post_meta(get_the_ID(), '_disponibilidad', true);
    $productor = get_post_meta(get_the_ID(), '_productor', true);
    $origen = get_post_meta(get_the_ID(), '_origen', true);
    $certificacion = get_post_meta(get_the_ID(), '_certificacion', true);
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    Flavor_Breadcrumbs::render_with_back(
        ['archive_label' => __('Grupos de Consumo', 'flavor-chat-ia')],
        '',
        __('Volver al catálogo', 'flavor-chat-ia')
    );
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <?php if (has_post_thumbnail()) : ?>
                <div class="aspect-square rounded-xl overflow-hidden shadow-lg mb-4">
                    <?php the_post_thumbnail('large', array('class' => 'w-full h-full object-cover')); ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php
            $categorias = get_the_terms(get_the_ID(), 'categoria_producto');
            if ($categorias && !is_wp_error($categorias)) :
            ?>
                <span class="inline-block px-4 py-2 text-sm font-semibold text-green-700 bg-green-100 rounded-full mb-4">
                    <?php echo esc_html($categorias[0]->name); ?>
                </span>
            <?php endif; ?>

            <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php the_title(); ?></h1>

            <?php if ($productor) : ?>
                <p class="text-lg text-gray-600 mb-4"><?php echo esc_html__('Productor:', 'flavor-chat-ia'); ?> <strong><?php echo esc_html($productor); ?></strong></p>
            <?php endif; ?>

            <?php if ($precio) : ?>
                <div class="text-5xl font-bold text-primary mb-6">
                    <?php echo esc_html($precio); ?>€
                    <?php if ($unidad) : ?>
                        <span class="text-xl text-gray-600 font-normal">/<?php echo esc_html($unidad); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mb-6">
                <?php if ($disponibilidad === 'disponible') : ?>
                    <span class="inline-flex items-center gap-2 text-green-700 bg-green-100 px-4 py-2 rounded-lg font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?>
                    </span>
                <?php else : ?>
                    <span class="inline-flex items-center gap-2 text-red-700 bg-red-100 px-4 py-2 rounded-lg font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <?php echo esc_html__('Agotado', 'flavor-chat-ia'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="prose max-w-none mb-6">
                <?php the_content(); ?>
            </div>

            <?php if ($origen || $certificacion) : ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold mb-3"><?php echo esc_html__('Información adicional', 'flavor-chat-ia'); ?></h3>
                    <dl class="space-y-3">
                        <?php if ($origen) : ?>
                            <div><dt class="text-sm font-semibold text-gray-700"><?php echo esc_html__('Origen:', 'flavor-chat-ia'); ?></dt><dd class="text-gray-600"><?php echo esc_html($origen); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($certificacion) : ?>
                            <div><dt class="text-sm font-semibold text-gray-700"><?php echo esc_html__('Certificación:', 'flavor-chat-ia'); ?></dt><dd class="text-gray-600"><?php echo esc_html($certificacion); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <?php if (is_user_logged_in() && $disponibilidad === 'disponible') : ?>
                <button class="w-full px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-bold text-lg">
                    <?php echo esc_html__('Solicitar Pedido', 'flavor-chat-ia'); ?>
                </button>
            <?php elseif (!is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="block w-full text-center px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-bold text-lg">
                    <?php echo esc_html__('Inicia sesión para pedir', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>

            <?php
            // Shared features: valoraciones, favoritos, compartir
            if (function_exists('flavor_render_post_features')) {
                flavor_render_post_features(['ratings', 'favorites', 'share', 'views']);
            }
            ?>
        </div>
    </div>
</div>

<?php endwhile; get_footer(); ?>
