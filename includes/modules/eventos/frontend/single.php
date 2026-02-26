<?php
/**
 * Template: Detalle de evento
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos
    $fecha              = get_post_meta(get_the_ID(), '_fecha_evento', true);
    $hora_inicio        = get_post_meta(get_the_ID(), '_hora_inicio', true);
    $hora_fin           = get_post_meta(get_the_ID(), '_hora_fin', true);
    $ubicacion          = get_post_meta(get_the_ID(), '_ubicacion', true);
    $precio             = get_post_meta(get_the_ID(), '_precio_entrada', true);
    $plazas_totales     = get_post_meta(get_the_ID(), '_plazas_totales', true);
    $plazas_disponibles = get_post_meta(get_the_ID(), '_plazas_disponibles', true);
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    echo Flavor_Breadcrumbs::render(['archive_label' => __('Eventos', 'flavor-chat-ia')]);
    ?>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2">
            <article class="bg-white rounded-xl shadow-lg overflow-hidden">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="aspect-video overflow-hidden">
                        <?php the_post_thumbnail('large', array('class' => 'w-full h-full object-cover')); ?>
                    </div>
                <?php endif; ?>

                <div class="p-8">
                    <h1 class="text-4xl font-bold mb-6">
                        <?php the_title(); ?>
                    </h1>

                    <!-- Información del evento -->
                    <div class="flex flex-wrap gap-6 text-gray-600 mb-8">
                        <!-- Fecha -->
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span><?php echo date_i18n('l, d F Y', strtotime($fecha)); ?></span>
                        </div>

                        <?php if ($hora_inicio) : ?>
                            <!-- Hora -->
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>
                                    <?php echo esc_html($hora_inicio); ?>
                                    <?php if ($hora_fin) : ?>
                                        - <?php echo esc_html($hora_fin); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($ubicacion) : ?>
                            <!-- Ubicación -->
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span><?php echo esc_html($ubicacion); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción -->
                    <div class="prose max-w-none mb-8">
                        <?php the_content(); ?>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                <h3 class="text-xl font-bold mb-4">
                    <?php esc_html_e('Información', 'flavor-chat-ia'); ?>
                </h3>

                <?php if ($precio !== null && $precio !== '') : ?>
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <?php esc_html_e('Precio', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-4xl font-bold text-primary">
                            <?php echo $precio == '0' ? esc_html__('Gratis', 'flavor-chat-ia') : esc_html($precio) . '€'; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($plazas_disponibles) : ?>
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <?php esc_html_e('Plazas disponibles', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-2xl font-bold">
                            <?php echo esc_html($plazas_disponibles); ?>
                            <?php if ($plazas_totales) : ?>
                                / <?php echo esc_html($plazas_totales); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (is_user_logged_in() && $plazas_disponibles > 0) : ?>
                    <button class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold mb-3">
                        <?php esc_html_e('Reservar Entrada', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!is_user_logged_in()) : ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"
                       class="block text-center w-full px-6 py-3 bg-primary text-white rounded-lg font-bold mb-3">
                        <?php esc_html_e('Inicia sesión', 'flavor-chat-ia'); ?>
                    </a>
                <?php else : ?>
                    <button disabled class="w-full px-6 py-3 bg-gray-300 text-gray-600 rounded-lg font-bold mb-3 cursor-not-allowed">
                        <?php esc_html_e('Agotado', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>

                <?php
                // Shared features: valoraciones, favoritos, compartir
                if (function_exists('flavor_render_post_features')) {
                    flavor_render_post_features(['ratings', 'favorites', 'share', 'views']);
                }
                ?>
            </div>
        </aside>
    </div>
</div>

<?php
endwhile;

get_footer();
?>
