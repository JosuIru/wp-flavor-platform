<?php
/**
 * Template: Detalle de libro
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos
    $autor      = get_post_meta(get_the_ID(), '_autor', true);
    $isbn       = get_post_meta(get_the_ID(), '_isbn', true);
    $editorial  = get_post_meta(get_the_ID(), '_editorial', true);
    $anio       = get_post_meta(get_the_ID(), '_anio_publicacion', true);
    $disponible = get_post_meta(get_the_ID(), '_disponible', true);
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    echo Flavor_Breadcrumbs::render([
        'archive_label' => __('Biblioteca', 'flavor-chat-ia'),
        'archive_url' => home_url('/biblioteca/')
    ]);
    ?>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Portada del libro -->
        <div class="lg:col-span-1">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', array('class' => 'w-full rounded-xl shadow-lg')); ?>
            <?php endif; ?>
        </div>

        <!-- Información del libro -->
        <div class="lg:col-span-2">
            <h1 class="text-4xl font-bold mb-4">
                <?php the_title(); ?>
            </h1>

            <?php if ($autor) : ?>
                <p class="text-xl text-gray-600 mb-6">
                    <?php esc_html_e('por', 'flavor-chat-ia'); ?> <?php echo esc_html($autor); ?>
                </p>
            <?php endif; ?>

            <!-- Estado de disponibilidad -->
            <div class="mb-6">
                <?php if ($disponible) : ?>
                    <span class="bg-green-100 text-green-700 px-4 py-2 rounded-lg font-semibold">
                        <?php esc_html_e('Disponible para préstamo', 'flavor-chat-ia'); ?>
                    </span>
                <?php else : ?>
                    <span class="bg-red-100 text-red-700 px-4 py-2 rounded-lg font-semibold">
                        <?php esc_html_e('Actualmente prestado', 'flavor-chat-ia'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Descripción -->
            <div class="prose mb-8">
                <?php the_content(); ?>
            </div>

            <!-- Información técnica -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold mb-4">
                    <?php esc_html_e('Información del libro', 'flavor-chat-ia'); ?>
                </h3>

                <dl class="space-y-3">
                    <?php if ($editorial) : ?>
                        <div>
                            <dt class="text-sm font-semibold text-gray-700">
                                <?php esc_html_e('Editorial:', 'flavor-chat-ia'); ?>
                            </dt>
                            <dd class="text-gray-900"><?php echo esc_html($editorial); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($anio) : ?>
                        <div>
                            <dt class="text-sm font-semibold text-gray-700">
                                <?php esc_html_e('Año:', 'flavor-chat-ia'); ?>
                            </dt>
                            <dd class="text-gray-900"><?php echo esc_html($anio); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($isbn) : ?>
                        <div>
                            <dt class="text-sm font-semibold text-gray-700">
                                <?php esc_html_e('ISBN:', 'flavor-chat-ia'); ?>
                            </dt>
                            <dd class="text-gray-900"><?php echo esc_html($isbn); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Botón de acción -->
            <?php if (is_user_logged_in() && $disponible) : ?>
                <button class="px-8 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold text-lg">
                    <?php esc_html_e('Solicitar Préstamo', 'flavor-chat-ia'); ?>
                </button>
            <?php elseif (!is_user_logged_in()) : ?>
                <a href="<?php echo wp_login_url(get_permalink()); ?>"
                   class="inline-block px-8 py-4 bg-primary text-white rounded-lg font-bold text-lg hover:bg-primary-dark">
                    <?php esc_html_e('Inicia sesión', 'flavor-chat-ia'); ?>
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

<?php
endwhile;

get_footer();
?>
