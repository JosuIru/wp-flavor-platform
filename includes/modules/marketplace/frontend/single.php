<?php
/**
 * Template: Detalle de artículo del marketplace
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos
    $precio      = get_post_meta(get_the_ID(), '_precio', true);
    $condicion   = get_post_meta(get_the_ID(), '_condicion', true);
    $vendedor_id = get_the_author_meta('ID');
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
            <li>
                <a href="<?php echo get_post_type_archive_link('marketplace'); ?>" class="text-gray-600 hover:text-primary">
                    <?php esc_html_e('Marketplace', 'flavor-chat-ia'); ?>
                </a>
            </li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium"><?php the_title(); ?></li>
        </ol>
    </nav>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Imagen del producto -->
        <div>
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', array('class' => 'w-full rounded-xl shadow-lg')); ?>
            <?php endif; ?>
        </div>

        <!-- Información del producto -->
        <div>
            <h1 class="text-4xl font-bold mb-4">
                <?php the_title(); ?>
            </h1>

            <div class="text-5xl font-bold text-primary mb-6">
                <?php echo esc_html($precio); ?>€
            </div>

            <?php if ($condicion) : ?>
                <span class="inline-block px-4 py-2 bg-gray-100 rounded-lg mb-4">
                    <?php esc_html_e('Estado:', 'flavor-chat-ia'); ?> <?php echo esc_html($condicion); ?>
                </span>
            <?php endif; ?>

            <!-- Descripción -->
            <div class="prose mb-6">
                <?php the_content(); ?>
            </div>

            <!-- Vendedor -->
            <div class="flex items-center gap-3 mb-6">
                <?php echo get_avatar($vendedor_id, 48, '', '', array('class' => 'rounded-full')); ?>
                <div>
                    <p class="font-semibold"><?php the_author(); ?></p>
                    <p class="text-sm text-gray-600">
                        <?php esc_html_e('Vendedor', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>

            <!-- Botón de contacto -->
            <?php if (is_user_logged_in()) : ?>
                <a href="mailto:<?php echo antispambot(get_the_author_meta('user_email')); ?>"
                   class="block text-center px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold">
                    <?php esc_html_e('Contactar Vendedor', 'flavor-chat-ia'); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo wp_login_url(get_permalink()); ?>"
                   class="block text-center px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold">
                    <?php esc_html_e('Inicia sesión para contactar', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
endwhile;

get_footer();
?>
