<?php
/**
 * Template: Detalle de curso
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos
    $duracion     = get_post_meta(get_the_ID(), '_duracion', true);
    $nivel        = get_post_meta(get_the_ID(), '_nivel', true);
    $precio       = get_post_meta(get_the_ID(), '_precio', true);
    $plazas       = get_post_meta(get_the_ID(), '_plazas_disponibles', true);
    $fecha_inicio = get_post_meta(get_the_ID(), '_fecha_inicio', true);
    $profesor     = get_post_meta(get_the_ID(), '_profesor', true);
?>

<div class="flavor-container py-8">
    <?php
    // Breadcrumbs centralizados
    echo Flavor_Breadcrumbs::render(['archive_label' => __('Cursos', 'flavor-chat-ia')]);
    ?>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2">
            <article class="bg-white rounded-xl shadow-lg overflow-hidden">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large', array('class' => 'w-full aspect-video object-cover')); ?>
                <?php endif; ?>

                <div class="p-8">
                    <h1 class="text-4xl font-bold mb-6">
                        <?php the_title(); ?>
                    </h1>

                    <?php if ($profesor) : ?>
                        <p class="text-lg text-gray-600 mb-6">
                            <?php esc_html_e('Instructor:', 'flavor-chat-ia'); ?>
                            <strong><?php echo esc_html($profesor); ?></strong>
                        </p>
                    <?php endif; ?>

                    <!-- Descripción del curso -->
                    <div class="prose max-w-none mb-8">
                        <?php the_content(); ?>
                    </div>

                    <!-- Detalles del curso -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-bold mb-4">
                            <?php esc_html_e('Detalles del curso', 'flavor-chat-ia'); ?>
                        </h3>

                        <dl class="space-y-3">
                            <?php if ($duracion) : ?>
                                <div>
                                    <dt class="text-sm font-semibold text-gray-700">
                                        <?php esc_html_e('Duración:', 'flavor-chat-ia'); ?>
                                    </dt>
                                    <dd class="text-gray-900"><?php echo esc_html($duracion); ?></dd>
                                </div>
                            <?php endif; ?>

                            <?php if ($nivel) : ?>
                                <div>
                                    <dt class="text-sm font-semibold text-gray-700">
                                        <?php esc_html_e('Nivel:', 'flavor-chat-ia'); ?>
                                    </dt>
                                    <dd class="text-gray-900"><?php echo esc_html($nivel); ?></dd>
                                </div>
                            <?php endif; ?>

                            <?php if ($fecha_inicio) : ?>
                                <div>
                                    <dt class="text-sm font-semibold text-gray-700">
                                        <?php esc_html_e('Fecha de inicio:', 'flavor-chat-ia'); ?>
                                    </dt>
                                    <dd class="text-gray-900">
                                        <?php echo date_i18n('d/m/Y', strtotime($fecha_inicio)); ?>
                                    </dd>
                                </div>
                            <?php endif; ?>

                            <?php if ($plazas) : ?>
                                <div>
                                    <dt class="text-sm font-semibold text-gray-700">
                                        <?php esc_html_e('Plazas disponibles:', 'flavor-chat-ia'); ?>
                                    </dt>
                                    <dd class="text-xl font-bold text-primary">
                                        <?php echo esc_html($plazas); ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar de inscripción -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                <h3 class="text-xl font-bold mb-4">
                    <?php esc_html_e('Inscripción', 'flavor-chat-ia'); ?>
                </h3>

                <?php if ($precio) : ?>
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <?php esc_html_e('Precio', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-4xl font-bold text-primary">
                            <?php echo esc_html($precio); ?>€
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (is_user_logged_in() && $plazas > 0) : ?>
                    <button class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold mb-3">
                        <?php esc_html_e('Inscribirse', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!is_user_logged_in()) : ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"
                       class="block text-center w-full px-6 py-3 bg-primary text-white rounded-lg font-bold">
                        <?php esc_html_e('Inicia sesión', 'flavor-chat-ia'); ?>
                    </a>
                <?php else : ?>
                    <button disabled class="w-full px-6 py-3 bg-gray-300 text-gray-600 rounded-lg font-bold cursor-not-allowed">
                        <?php esc_html_e('Plazas agotadas', 'flavor-chat-ia'); ?>
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
