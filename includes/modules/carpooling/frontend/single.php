<?php
/**
 * Template: Detalle de viaje compartido
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos del viaje
    $origen       = get_post_meta(get_the_ID(), '_origen', true);
    $destino      = get_post_meta(get_the_ID(), '_destino', true);
    $fecha        = get_post_meta(get_the_ID(), '_fecha_viaje', true);
    $hora         = get_post_meta(get_the_ID(), '_hora_salida', true);
    $plazas       = get_post_meta(get_the_ID(), '_plazas_disponibles', true);
    $precio       = get_post_meta(get_the_ID(), '_precio_plaza', true);
    $conductor_id = get_the_author_meta('ID');
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    echo Flavor_Breadcrumbs::render([
        'archive_label' => __('Carpooling', 'flavor-chat-ia'),
        'archive_url' => home_url('/carpooling/')
    ]);
    ?>

    <!-- Contenido principal -->
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
        <!-- Header del viaje -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($origen); ?>
                <span class="text-primary">→</span>
                <?php echo esc_html($destino); ?>
            </h1>
            <p class="text-xl text-gray-600">
                <?php echo date_i18n('l, d F Y', strtotime($fecha)); ?>
                <?php esc_html_e('a las', 'flavor-chat-ia'); ?>
                <?php echo esc_html($hora); ?>
            </p>
        </div>

        <!-- Grid de información -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <!-- Detalles del viaje -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">
                    <?php esc_html_e('Detalles del viaje', 'flavor-chat-ia'); ?>
                </h3>

                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">
                            <?php esc_html_e('Origen:', 'flavor-chat-ia'); ?>
                        </dt>
                        <dd class="text-gray-900"><?php echo esc_html($origen); ?></dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-gray-700">
                            <?php esc_html_e('Destino:', 'flavor-chat-ia'); ?>
                        </dt>
                        <dd class="text-gray-900"><?php echo esc_html($destino); ?></dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-gray-700">
                            <?php esc_html_e('Fecha y hora:', 'flavor-chat-ia'); ?>
                        </dt>
                        <dd class="text-gray-900">
                            <?php echo date_i18n('d/m/Y', strtotime($fecha)); ?> - <?php echo esc_html($hora); ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-gray-700">
                            <?php esc_html_e('Plazas disponibles:', 'flavor-chat-ia'); ?>
                        </dt>
                        <dd class="text-gray-900 font-bold text-xl"><?php echo esc_html($plazas); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Info del conductor -->
            <div class="bg-primary bg-opacity-10 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">
                    <?php esc_html_e('Información del conductor', 'flavor-chat-ia'); ?>
                </h3>

                <div class="flex items-center gap-3 mb-4">
                    <?php echo get_avatar($conductor_id, 60, '', '', array('class' => 'rounded-full')); ?>
                    <div>
                        <p class="font-bold text-lg"><?php the_author(); ?></p>
                        <p class="text-sm text-gray-600">
                            <?php esc_html_e('Conductor verificado', 'flavor-chat-ia'); ?>
                        </p>
                    </div>
                </div>

                <!-- Precio -->
                <div class="text-4xl font-bold text-primary mb-4">
                    <?php echo esc_html($precio); ?>€
                    <span class="text-base font-normal text-gray-600">/<?php esc_html_e('plaza', 'flavor-chat-ia'); ?></span>
                </div>

                <?php if (is_user_logged_in() && get_current_user_id() !== $conductor_id) : ?>
                    <!-- Botón reservar (usuario logueado) -->
                    <button class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold">
                        <?php esc_html_e('Reservar Plaza', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!is_user_logged_in()) : ?>
                    <!-- Enlace login -->
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"
                       class="block text-center w-full px-6 py-3 bg-primary text-white rounded-lg font-bold">
                        <?php esc_html_e('Inicia sesión para reservar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Descripción -->
        <div class="prose max-w-none mb-8">
            <h2 class="text-2xl font-bold mb-4">
                <?php esc_html_e('Descripción', 'flavor-chat-ia'); ?>
            </h2>
            <?php the_content(); ?>
        </div>

        <?php
        // Shared features: valoraciones, favoritos, compartir
        if (function_exists('flavor_render_post_features')) {
            flavor_render_post_features(['ratings', 'favorites', 'share', 'views']);
        }
        ?>
    </div>
</div>

<?php
endwhile;

get_footer();
?>
